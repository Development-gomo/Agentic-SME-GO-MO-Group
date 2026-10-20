<?php
/**
 * Plugin Name: GO MO Agent API
 * Description: Machine-readable agent layer for gomogroup.com — clean JSON endpoints at /agent/v1/, the OpenAPI spec at /openapi.json, the /agent/ page and the llms.txt / ai.txt / api-catalog.json discovery files (served with correct charset). Bilingual (EN + SV).
 * Version: 1.3.0
 * Author: GO MO Group
 * Requires PHP: 7.4
 * License: Proprietary
 *
 * After activating or updating: Settings → Permalinks → Save (flushes rewrite rules).
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GOMO_API_VERSION', '1.3.0');
define('GOMO_API_DIR', plugin_dir_path(__FILE__));
define('GOMO_API_BASE', 'https://www.gomogroup.com/agent/v1');
define('GOMO_LEAD_EMAIL', 'ha@gomogroup.com');

// Contact abuse limits. Override any of these in wp-config.php.
// GOMO_TRUSTED_PROXY_HEADER: e.g. 'HTTP_X_FORWARDED_FOR' when WordPress sits behind a reverse proxy/CDN
//   that does not already put the visitor's IP in REMOTE_ADDR. Only set it if that proxy overwrites the header.
// GOMO_MCP_SHARED_SECRET: same value as the MCP server's env var; lets it pass through the agent's real IP.
defined('GOMO_CONTACT_PER_IP_HOUR') || define('GOMO_CONTACT_PER_IP_HOUR', 5);
defined('GOMO_CONTACT_GLOBAL_HOUR') || define('GOMO_CONTACT_GLOBAL_HOUR', 20);
defined('GOMO_CONTACT_MAX_LINKS') || define('GOMO_CONTACT_MAX_LINKS', 3);

require_once GOMO_API_DIR . 'includes/class-snapshot.php';
require_once GOMO_API_DIR . 'includes/class-sync.php';
require_once GOMO_API_DIR . 'includes/class-contact-store.php';

final class GOMO_Agent_API
{
    /** Data-backed list endpoints → data file. */
    private const DATA_ENDPOINTS = [
        'company'      => 'company.json',
        'services'     => 'services.json',
        'case-studies' => 'case-studies.json',
        'team'         => 'team.json',
        'offices'      => 'offices.json',
        'insights'     => 'insights.json',
        'events'       => 'events.json',
        'careers'      => 'careers.json',
        'pages'        => 'pages.json',
    ];

    /** Collections that support /{collection}/{id}.json. */
    private const SINGLE_ENDPOINTS = ['services', 'case-studies', 'insights'];

    /** Static discovery files served from the plugin root (path → [file, content type]). */
    private const STATIC_FILES = [
        'llms_txt'    => ['llms.txt', 'text/plain; charset=utf-8'],
        'ai_txt'      => ['ai.txt', 'text/plain; charset=utf-8'],
        'api_catalog' => ['api-catalog.json', 'application/json; charset=utf-8'],
        'openapi'     => ['openapi.json', 'application/json; charset=utf-8'],
        'agent_page'  => ['agent-page.html', 'text/html; charset=utf-8'],
        'agent_json'  => ['agent-index.json', 'application/json; charset=utf-8'],
    ];

    public static function init(): void
    {
        $self = new self();
        add_action('init', [$self, 'add_rewrite_rules']);
        add_filter('query_vars', [$self, 'add_query_vars']);
        add_action('template_redirect', [$self, 'route'], 0);
        add_filter('redirect_canonical', [$self, 'no_trailing_slash_redirect'], 10, 2);
        add_action('wp_head', [$self, 'discovery_links'], 1);
        GOMO_API_Sync::init();
    }

    public function add_rewrite_rules(): void
    {
        // Dots are escaped; all rules at 'top' so they win over WordPress page/post rules.
        add_rewrite_rule('^agent/v1/(services|case-studies|insights)/([a-z0-9-]+)\.json$', 'index.php?gomo_endpoint=$matches[1]&gomo_id=$matches[2]', 'top');
        add_rewrite_rule('^agent/v1/([a-z-]+)\.json$', 'index.php?gomo_endpoint=$matches[1]', 'top');
        add_rewrite_rule('^agent/v1/?$', 'index.php?gomo_endpoint=index', 'top');
        add_rewrite_rule('^openapi\.json$', 'index.php?gomo_static=openapi', 'top');
        add_rewrite_rule('^api-catalog\.json$', 'index.php?gomo_static=api_catalog', 'top');
        add_rewrite_rule('^llms\.txt$', 'index.php?gomo_static=llms_txt', 'top');
        add_rewrite_rule('^ai\.txt$', 'index.php?gomo_static=ai_txt', 'top');
        add_rewrite_rule('^agent/index\.json$', 'index.php?gomo_static=agent_json', 'top');
        if (get_option('gomo_api_serve_agent_page', '1') === '1') {
            add_rewrite_rule('^agent/?$', 'index.php?gomo_static=agent_page', 'top');
        }
    }

    public function add_query_vars(array $vars): array
    {
        $vars[] = 'gomo_endpoint';
        $vars[] = 'gomo_id';
        $vars[] = 'gomo_static';
        return $vars;
    }

    /** Stop WordPress from 301-ing /agent/v1/x.json → /agent/v1/x.json/ */
    public function no_trailing_slash_redirect($redirect_url, $requested_url)
    {
        if (get_query_var('gomo_endpoint') || get_query_var('gomo_static')) {
            return false;
        }
        return $redirect_url;
    }

    /** <link rel="alternate"> discovery hints on every public page. */
    public function discovery_links(): void
    {
        echo "<link rel=\"alternate\" type=\"text/plain\" title=\"llms.txt\" href=\"https://www.gomogroup.com/llms.txt\">\n";
        echo "<link rel=\"alternate\" type=\"text/plain\" title=\"ai.txt\" href=\"https://www.gomogroup.com/ai.txt\">\n";
        echo "<link rel=\"alternate\" type=\"text/html\" title=\"Agent page (machine-readable)\" href=\"https://www.gomogroup.com/agent/\">\n";
        echo "<link rel=\"alternate\" type=\"application/json\" title=\"Agent JSON\" href=\"https://www.gomogroup.com/agent/index.json\">\n";
        echo "<link rel=\"service-desc\" type=\"application/json\" href=\"https://www.gomogroup.com/openapi.json\">\n";
        echo "<link rel=\"api-catalog\" href=\"https://www.gomogroup.com/api-catalog.json\">\n";
    }

    // ── Router ───────────────────────────────────────────────────────────────

    public function route(): void
    {
        $static = get_query_var('gomo_static');
        if ($static || get_query_var('gomo_endpoint')) {
            try { GOMO_API_Snapshot::root(); } catch (Throwable $e) {
                $this->send_headers(true);
                $this->respond($this->error('unavailable', 'Published snapshot unavailable.'), 503);
            }
        }
        if ($static) {
            $this->serve_static($static);
        }

        $endpoint = get_query_var('gomo_endpoint');
        if (!$endpoint) {
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->send_headers($endpoint === 'contact' || $method !== 'GET');

        if ($method === 'OPTIONS') {
            status_header(204);
            exit;
        }

        if ($endpoint === 'contact') {
            if ($method !== 'POST') {
                $this->respond(['status' => 'error', 'error' => 'method_not_allowed', 'message' => 'Use POST with a JSON body. See https://www.gomogroup.com/openapi.json'], 405);
            }
            $this->contact_submission();
        }

        if ($method !== 'GET' && $method !== 'HEAD') {
            $this->respond($this->error('method_not_allowed', 'This endpoint is read-only (GET).'), 405);
        }

        if ($endpoint === 'index') {
            $this->respond($this->get_index());
        }
        if ($endpoint === 'schema') {
            $this->respond($this->get_schema());
        }
        if (!isset(self::DATA_ENDPOINTS[$endpoint])) {
            $this->respond($this->error('not_found', "Unknown endpoint '{$endpoint}'. See " . GOMO_API_BASE . '/index.json'), 404);
        }

        $id = get_query_var('gomo_id');
        if ($id && in_array($endpoint, self::SINGLE_ENDPOINTS, true)) {
            $this->serve_single($endpoint, sanitize_key($id));
        }

        $filters = $this->filters_for($endpoint);
        if (!$filters) {
            // No filter requested: stream the file byte-for-byte.
            $path = GOMO_API_Snapshot::root() . 'data/' . self::DATA_ENDPOINTS[$endpoint];
            if (!is_readable($path)) {
                $this->respond($this->error('unavailable', 'Data file missing — run Tools → GO MO Agent API → Sync.'), 503);
            }
            status_header(200);
            readfile($path);
            exit;
        }

        $this->serve_filtered($endpoint, $filters);
    }

    private function serve_static(string $key): void
    {
        if (!isset(self::STATIC_FILES[$key])) {
            return;
        }
        [$file, $type] = self::STATIC_FILES[$key];
        $path = GOMO_API_Snapshot::root() . $file;
        if (!is_readable($path)) {
            // The rewrite already claimed this URL, so falling through would render the homepage with a 200.
            status_header(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Not found: {$file} is missing from the gomo-api plugin folder.";
            exit;
        }
        status_header(200);
        header('Content-Type: ' . $type);
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: public, max-age=0, must-revalidate');
        header('X-Robots-Tag: ' . ($key === 'agent_page' ? 'index, follow' : 'noindex'));
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT');
        readfile($path);
        exit;
    }

    // ── Filtering ────────────────────────────────────────────────────────────

    /** Allowed query filters per endpoint (only non-empty ones are returned). */
    private function filters_for(string $endpoint): array
    {
        $allowed = [
            'services'     => ['category', 'tag', 'lang'],
            'case-studies' => ['industry', 'segment', 'service', 'lang'],
            'insights'     => ['lang', 'topic', 'since', 'page', 'per_page'],
            'events'       => ['lang'],
            'careers'      => ['lang'],
            'pages'        => ['lang', 'type', 'page', 'per_page'],
        ][$endpoint] ?? [];

        $out = [];
        foreach ($allowed as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $out[$key] = sanitize_text_field(wp_unslash($_GET[$key]));
            }
        }
        if (($out['lang'] ?? '') === 'all') {
            unset($out['lang']);
        }
        return $out;
    }

    private function load(string $endpoint): array
    {
        $path = GOMO_API_Snapshot::root() . 'data/' . self::DATA_ENDPOINTS[$endpoint];
        $json = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;
        if (!is_array($json)) {
            $this->respond($this->error('unavailable', 'Data file missing or invalid.'), 503);
        }
        return $json;
    }

    private function serve_filtered(string $endpoint, array $f): void
    {
        $json  = $this->load($endpoint);
        $items = $json['data'] ?? [];

        $items = array_values(array_filter($items, static function ($item) use ($f) {
            $lc = static fn($v) => strtolower((string) $v);
            if (isset($f['lang'])) {
                // Bilingual items (services, cases) list available languages; per-language items carry 'lang'.
                // Items without either (events, careers) are matched on which language URLs they have.
                $langs = $item['languages'] ?? (isset($item['lang']) ? [$item['lang']] : array_keys(array_filter(['en' => $item['url'] ?? null, 'sv' => $item['url_sv'] ?? null])));
                if (!in_array($lc($f['lang']), $langs, true)) {
                    return false;
                }
            }
            if (isset($f['category']) && $lc($item['category'] ?? '') !== $lc($f['category'])) {
                return false;
            }
            if (isset($f['type']) && $lc($item['type'] ?? '') !== $lc($f['type'])) {
                return false;
            }
            if (isset($f['segment']) && $lc($item['segment'] ?? '') !== $lc($f['segment'])) {
                return false;
            }
            if (isset($f['industry']) && strpos($lc($item['industry'] ?? ''), $lc($f['industry'])) === false) {
                return false;
            }
            foreach (['tag' => 'tags', 'service' => 'services', 'topic' => 'topics'] as $param => $field) {
                if (isset($f[$param]) && !in_array($lc($f[$param]), array_map($lc, $item[$field] ?? []), true)) {
                    return false;
                }
            }
            if (isset($f['since']) && strcmp((string) ($item['datePublished'] ?? ''), $f['since']) < 0) {
                return false;
            }
            return true;
        }));

        $total    = count($items);
        $per_page = max(1, min(200, (int) ($f['per_page'] ?? 200)));
        $page     = max(1, (int) ($f['page'] ?? 1));
        $items    = array_slice($items, ($page - 1) * $per_page, $per_page);

        $json['data']       = $items;
        $json['pagination'] = ['total' => $total, 'page' => $page, 'per_page' => $per_page, 'pages' => (int) ceil($total / $per_page)];
        $json['_meta']['filters'] = $f;
        $this->respond($json);
    }

    private function serve_single(string $endpoint, string $id): void
    {
        $json = $this->load($endpoint);
        foreach ($json['data'] ?? [] as $item) {
            if (($item['id'] ?? '') === $id || in_array($id, $item['aliases'] ?? [], true)) {
                $meta           = $json['_meta'];
                $meta['source'] = GOMO_API_BASE . "/{$endpoint}/{$id}.json";
                $this->respond(['status' => 'ok', 'data' => $item, '_meta' => $meta]);
            }
        }
        $this->respond($this->error('not_found', "No item '{$id}' in {$endpoint}. List IDs at " . GOMO_API_BASE . "/{$endpoint}.json"), 404);
    }

    // ── Index & schema ───────────────────────────────────────────────────────

    private function get_index(): array
    {
        $ep = static fn(string $route, string $desc, string $method = 'GET') => [
            'route' => $route, 'method' => $method, 'url' => GOMO_API_BASE . $route, 'description' => $desc,
        ];
        return [
            'status'      => 'ok',
            'name'        => 'GO MO Group Public API',
            'version'     => GOMO_API_VERSION,
            'description' => 'Public read API for GO MO Group (gomogroup.com), plus one consent-gated contact action. No authentication. Bilingual: English (en) and Swedish (sv) — use ?lang=en|sv|all.',
            'languages'   => ['en', 'sv'],
            'endpoints'   => [
                $ep('/index.json', 'This index'),
                $ep('/schema.json', 'Endpoint schema and filter definitions'),
                $ep('/company.json', 'Company identity and overview'),
                $ep('/services.json', 'All services — filter by ?category=, ?tag=, ?lang='),
                $ep('/services/{id}.json', 'Single service by ID'),
                $ep('/case-studies.json', 'Case studies — filter by ?industry=, ?segment=, ?service=, ?lang='),
                $ep('/case-studies/{id}.json', 'Single case study by ID'),
                $ep('/team.json', 'Leadership and team'),
                $ep('/offices.json', 'Office locations'),
                $ep('/insights.json', 'Insight/blog articles (EN + SV) — filter by ?lang=, ?topic=, ?since=YYYY-MM-DD; paginate with ?page=&per_page='),
                $ep('/insights/{id}.json', 'Single insight article by ID'),
                $ep('/events.json', 'Seminars and events'),
                $ep('/careers.json', 'Open roles'),
                $ep('/pages.json', 'Every public URL with type, language and EN/SV alternate — filter by ?lang=, ?type='),
                $ep('/contact.json', 'Submit a qualified B2B inquiry — requires agent_context.user_authorized_submission: true', 'POST'),
            ],
            'openapi'   => 'https://www.gomogroup.com/openapi.json',
            'discovery' => [
                'agent_page'  => 'https://www.gomogroup.com/agent/',
                'agent_json'  => 'https://www.gomogroup.com/agent/index.json',
                'llms_txt'    => 'https://www.gomogroup.com/llms.txt',
                'ai_txt'      => 'https://www.gomogroup.com/ai.txt',
                'api_catalog' => 'https://www.gomogroup.com/api-catalog.json',
                'mcp_server'  => 'https://agent-mcp.gomogroup.com/mcp',
                'sitemap'     => 'https://www.gomogroup.com/sitemap_index.xml',
            ],
            '_meta' => $this->meta('index'),
        ];
    }

    private function get_schema(): array
    {
        return [
            'status' => 'ok',
            'schema' => [
                'common_filters' => ['lang' => 'en | sv | all (default all). Bilingual items match if they exist in that language.'],
                'services'       => ['filters' => ['category' => 'search | ai | paid | content | outbound | web | strategy | local', 'tag' => 'free-text tag'], 'id_examples' => ['geo', 'seo', 'sea', 'pull-marketing']],
                'case-studies'   => ['filters' => ['industry' => 'substring match', 'segment' => 'enterprise | saas-tech | sme', 'service' => 'service ID']],
                'insights'       => ['filters' => ['topic' => 'geo | ai | seo | paid | b2b-marketing | content | leadership | company | careers', 'since' => 'YYYY-MM-DD', 'page' => 'int', 'per_page' => 'int ≤ 200']],
                'pages'          => ['filters' => ['type' => 'page | service | solution | case | insight | team | career | event | legal']],
                'contact'        => ['method' => 'POST', 'required' => ['person.name', 'person.email', 'company.name', 'inquiry.service_interest', 'inquiry.project_summary', 'agent_context.user_authorized_submission (must be true)']],
            ],
            '_meta' => $this->meta('schema'),
        ];
    }

    // ── Contact (consent-gated action) ───────────────────────────────────────

    private function contact_submission(): void
    {
        $limit = 32768;
        if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $limit) {
            $this->respond($this->error('payload_too_large', 'Maximum request size is 32768 bytes.'), 413);
        }
        $raw = file_get_contents('php://input', false, null, 0, $limit + 1);
        if ($raw === false || strlen($raw) > $limit) {
            $this->respond($this->error('payload_too_large', 'Maximum request size is 32768 bytes.'), 413);
        }
        $body = json_decode($raw, true);
        if (!is_array($body)) {
            $this->respond($this->error('invalid_json', 'Request body must be JSON.'), 400);
        }

        // Safety gate (layer 3 of 3 — the MCP tool description and Zod schema are layers 1 and 2).
        if (($body['agent_context']['user_authorized_submission'] ?? null) !== true) {
            $this->respond($this->error('not_authorized', 'agent_context.user_authorized_submission must be true.'), 403);
        }

        // The consent flag is a protocol contract with the calling agent, not an authentication control.
        // Abuse is limited below instead: an off switch, a site-wide cap, a per-client cap and duplicate suppression.
        if (get_option('gomo_api_contact_enabled', '1') !== '1') {
            $this->respond($this->error('contact_disabled', 'Agent submissions are paused. Please email hello@gomogroup.com.'), 503);
        }
        // Read nested scalars defensively: a non-string value (array, object) becomes ''.
        $str = static function (array $path) use ($body): string {
            $v = $body;
            foreach ($path as $k) {
                $v = is_array($v) ? ($v[$k] ?? null) : null;
            }
            return is_scalar($v) ? (string) $v : '';
        };
        $name    = sanitize_text_field($str(['person', 'name']));
        $email   = sanitize_email($str(['person', 'email']));
        $company = sanitize_text_field($str(['company', 'name']));
        $service = sanitize_text_field($str(['inquiry', 'service_interest']));
        $summary = sanitize_textarea_field($str(['inquiry', 'project_summary']));

        $missing = [];
        $limits = [
            'person' => ['name' => [2, 120], 'email' => [1, 254], 'phone' => [0, 40], 'job_title' => [0, 120]],
            'company' => ['name' => [1, 160], 'website' => [1, 2048], 'country' => [0, 80], 'size' => [0, 40]],
            'inquiry' => ['service_interest' => [2, 120], 'project_summary' => [10, 4000], 'budget_range' => [0, 80], 'timeline' => [0, 80], 'preferred_language' => [2, 2]],
            'agent_context' => ['agent_name' => [0, 80], 'conversation_summary' => [0, 2000]],
        ];
        foreach ($limits as $group => $fields) {
            if (!isset($body[$group]) || !is_array($body[$group])) {
                $missing[] = $group;
                continue;
            }
            foreach ($fields as $field => [$min, $max]) {
                if (!array_key_exists($field, $body[$group])) { continue; }
                $value = $body[$group][$field];
                if (!is_string($value) || mb_strlen(trim($value)) < $min || mb_strlen($value) > $max) {
                    $missing[] = "$group.$field";
                }
            }
        }
        $website = $str(['company', 'website']);
        if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) { $missing[] = 'company.website'; }
        if (isset($body['inquiry']['preferred_language']) && !in_array($body['inquiry']['preferred_language'], ['en', 'sv'], true)) {
            $missing[] = 'inquiry.preferred_language';
        }
        if (mb_strlen($name) < 2) { $missing[] = 'person.name'; }
        if (mb_strlen($service) < 2) { $missing[] = 'inquiry.service_interest'; }
        foreach (['person.name' => $name, 'company.name' => $company, 'inquiry.service_interest' => $service] as $field => $value) {
            if ($value === '') {
                $missing[] = $field;
            }
        }
        if (!is_email($email)) {
            $missing[] = 'person.email (valid address)';
        }
        if (mb_strlen($summary) < 10) {
            $missing[] = 'inquiry.project_summary (≥ 10 characters)';
        }
        if ($missing) {
            $this->respond(array_merge($this->error('validation_failed', 'Missing or invalid fields.'), ['fields' => $missing]), 422);
        }
        if (preg_match_all('#https?://#i', $summary) > GOMO_CONTACT_MAX_LINKS) {
            $this->respond($this->error('rejected', 'The project summary contains too many links.'), 422);
        }

        $dupe_key = hash('sha256', strtolower($email) . '|' . strtolower($company) . '|' . strtolower($service));
        $lead_id = 'gomo-lead-' . gmdate('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $store = new GOMO_Contact_Store();
        try {
            $reservation = $store->reserve(hash('sha256', $this->client_ip()), $dupe_key, $lead_id);
        } catch (Throwable $e) {
            $this->respond($this->error('unavailable', 'Contact submissions are temporarily unavailable.'), 503);
        }
        if ($reservation['status'] === 'limited') {
            $this->respond($this->error('rate_limited', 'Too many submissions. Try again later.'), 429);
        }
        if ($reservation['status'] === 'pending') {
            $this->respond($this->error('submission_pending', 'This inquiry is already being processed. Do not resubmit.'), 409);
        }
        if ($reservation['status'] === 'sent') {
            $this->respond(['status' => 'duplicate', 'message' => 'This inquiry was already received; it has not been sent again.',
                'lead_id' => $reservation['lead_id'], 'next_step' => 'A GO MO Group specialist will reply by email, normally within one business day.']);
        }
        $opt     = static fn(array $path) => sanitize_text_field($str($path));

        $lines = [
            "New agent-submitted inquiry ({$lead_id})",
            '',
            "Name:        {$name}",
            "Email:       {$email}",
            'Phone:       ' . $opt(['person', 'phone']),
            'Job title:   ' . $opt(['person', 'job_title']),
            "Company:     {$company}",
            'Website:     ' . $opt(['company', 'website']),
            'Country:     ' . $opt(['company', 'country']),
            'Size:        ' . $opt(['company', 'size']),
            '',
            "Service:     {$service}",
            'Budget:      ' . $opt(['inquiry', 'budget_range']),
            'Timeline:    ' . $opt(['inquiry', 'timeline']),
            'Language:    ' . $opt(['inquiry', 'preferred_language']),
            '',
            'Project summary:',
            $summary,
            '',
            'Agent:       ' . $opt(['agent_context', 'agent_name']),
            'Conversation summary:',
            sanitize_textarea_field($str(['agent_context', 'conversation_summary'])),
            '',
            'Submitted via ' . GOMO_API_BASE . '/contact.json at ' . gmdate('c'),
        ];

        $subject = "[Agent lead] {$company} — {$service} ({$lead_id})";
        try {
            $sent = wp_mail(GOMO_LEAD_EMAIL, $subject, implode("\n", $lines), ["Reply-To: {$name} <{$email}>"]);
            $store->complete($dupe_key, $lead_id, (bool) $sent);
        } catch (Throwable $e) {
            // Retain the pending reservation if delivery outcome is uncertain; never send twice automatically.
            $this->respond($this->error('delivery_uncertain', 'Delivery status is uncertain. Please contact hello@gomogroup.com instead of resubmitting.'), 503);
        }

        if (!$sent) {
            $this->respond($this->error('delivery_failed', 'The inquiry could not be delivered. Please email hello@gomogroup.com.'), 502);
        }


        $this->respond([
            'status'    => 'received',
            'message'   => 'Thank you — the inquiry has been delivered to GO MO Group\'s commercial team.',
            'lead_id'   => $lead_id,
            'next_step' => 'A GO MO Group specialist will reply by email, normally within one business day.',
        ], 201);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Visitor IP for rate limiting; proxy headers are only trusted when configured or signed by the MCP server. */
    private function client_ip(): string
    {
        $secret = defined('GOMO_MCP_SHARED_SECRET') ? (string) GOMO_MCP_SHARED_SECRET : '';
        $sent   = (string) ($_SERVER['HTTP_X_GOMO_MCP_KEY'] ?? '');
        if ($secret !== '' && $sent !== '' && hash_equals($secret, $sent)) {
            $ip = trim((string) ($_SERVER['HTTP_X_GOMO_CLIENT_IP'] ?? ''));
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return 'mcp:' . $ip;
            }
        }
        if (defined('GOMO_TRUSTED_PROXY_HEADER') && !empty($_SERVER[GOMO_TRUSTED_PROXY_HEADER])) {
            $ip = trim(explode(',', (string) $_SERVER[GOMO_TRUSTED_PROXY_HEADER])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }

    private function send_headers(bool $no_store = false): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        header('X-Robots-Tag: noindex');
        header('Cache-Control: ' . ($no_store ? 'no-store' : 'public, max-age=0, must-revalidate'));
    }

    private function meta(string $endpoint): array
    {
        return [
            'source'    => GOMO_API_BASE . "/{$endpoint}.json",
            'canonical' => 'https://www.gomogroup.com',
            'api'       => GOMO_API_BASE,
            'generated' => gmdate('c'),
            'version'   => GOMO_API_VERSION,
        ];
    }

    private function error(string $code, string $message): array
    {
        return ['status' => 'error', 'error' => $code, 'message' => $message];
    }

    private function respond(array $data, int $status = 200): void
    {
        status_header($status);
        echo wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

GOMO_Agent_API::init();

register_activation_hook(__FILE__, static function () {
    add_option('gomo_api_serve_agent_page', '1');
    // The plugin was not loaded during this request's 'init', so register the rules before flushing.
    (new GOMO_Agent_API())->add_rewrite_rules();
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
