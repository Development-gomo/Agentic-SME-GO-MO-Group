<?php
/**
 * Plugin Name: GO MO Group Public API
 * Plugin URI:  https://www.gomogroup.com
 * Description: Public REST API serving clean URLs at /agent/v1/. GET endpoints for company data; POST /contact.json for agent-submitted B2B inquiries. Uses WordPress rewrite rules and template_redirect.
 * Version:     1.2.0
 * Author:      GO MO Group
 * Author URI:  https://www.gomogroup.com
 * License:     Proprietary
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-sync.php';

class GoMoPublicAPI {

    const VERSION  = '1.2.0';
    const BASE_URL = 'https://www.gomogroup.com';
    const API_BASE = 'agent/v1';

    public function __construct() {
        add_action('init',               [$this, 'register_rewrites']);
        add_filter('query_vars',         [$this, 'add_query_vars']);
        add_action('template_redirect',  [$this, 'handle_request'], 1);
    }

    // ── Activation / deactivation ─────────────────────────────────────────────

    public static function activate(): void {
        (new self)->register_rewrites();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    // ── Rewrite rules — fires before WordPress page routing ───────────────────

    public function register_rewrites(): void {
        // /openapi.json — clean root URL serving the plugin's OpenAPI spec
        add_rewrite_rule(
            '^openapi\.json$',
            'index.php?gomo_endpoint=openapi_spec',
            'top'
        );
        // /agent/v1/index.json
        add_rewrite_rule(
            '^agent/v1/index\.json$',
            'index.php?gomo_endpoint=index',
            'top'
        );
        // /agent/v1/ (bare — redirect to index.json)
        add_rewrite_rule(
            '^agent/v1/?$',
            'index.php?gomo_endpoint=index',
            'top'
        );
        // /agent/v1/schema.json
        add_rewrite_rule(
            '^agent/v1/schema\.json$',
            'index.php?gomo_endpoint=schema',
            'top'
        );
        // /agent/v1/{resource}/{id}.json  e.g. /agent/v1/services/geo.json
        add_rewrite_rule(
            '^agent/v1/([a-z-]+)/([a-z0-9-]+)\.json$',
            'index.php?gomo_endpoint=$matches[1]&gomo_id=$matches[2]',
            'top'
        );
        // /agent/v1/{resource}.json  e.g. /agent/v1/services.json
        add_rewrite_rule(
            '^agent/v1/([a-z-]+)\.json$',
            'index.php?gomo_endpoint=$matches[1]',
            'top'
        );
    }

    public function add_query_vars(array $vars): array {
        $vars[] = 'gomo_endpoint';
        $vars[] = 'gomo_id';
        return $vars;
    }

    // ── Request handler ───────────────────────────────────────────────────────

    public function handle_request(): void {
        $endpoint = get_query_var('gomo_endpoint');
        if (!$endpoint) return;

        $this->send_headers();

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            status_header(204);
            exit;
        }

        // openapi.json — serve raw file (avoid re-encoding overhead)
        if ($endpoint === 'openapi_spec') {
            header('Cache-Control: public, max-age=86400');
            $path = plugin_dir_path(__FILE__) . 'openapi.json';
            if (!file_exists($path)) {
                status_header(404);
                echo wp_json_encode($this->error('not_found', 'openapi.json not found.'));
            } else {
                readfile($path);
            }
            exit;
        }

        $id = get_query_var('gomo_id') ?: null;

        switch ($endpoint) {
            case 'index':
                $body = $this->get_index();
                break;
            case 'schema':
                $body = $this->get_schema();
                break;
            case 'company':
                $body = $this->company_response();
                break;
            case 'services':
                $body = $id ? $this->single('services', $id) : $this->services_list();
                break;
            case 'case-studies':
                $body = $id ? $this->single('case-studies', $id) : $this->cases_list();
                break;
            case 'team':
                $body = $this->simple_list('team');
                break;
            case 'offices':
                $body = $this->simple_list('offices');
                break;
            case 'contact':
                $body = $this->contact_submission();
                break;
            default:
                status_header(404);
                $body = $this->error('not_found', "Unknown endpoint '{$endpoint}'.");
        }

        echo wp_json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ── Headers ───────────────────────────────────────────────────────────────

    private function send_headers(): void {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        header('Cache-Control: public, max-age=3600');
        header('X-API-Version: ' . self::VERSION);
        header('X-Powered-By: GO MO Group Public API');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function data_path(string $file): string {
        return plugin_dir_path(__FILE__) . 'data/' . $file . '.json';
    }

    private function load_json(string $file): array|false {
        $path = $this->data_path($file);
        if (!file_exists($path)) return false;
        $decoded = json_decode(file_get_contents($path), true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : false;
    }

    private function meta(): array {
        return [
            '_meta' => [
                'source'    => 'GO MO Group',
                'canonical' => self::BASE_URL,
                'api'       => self::BASE_URL . '/' . self::API_BASE,
                'generated' => gmdate('c'),
                'version'   => self::VERSION,
                'license'   => 'Content © GO MO Group AB. Do not republish as original or fabricate statistics.',
            ],
        ];
    }

    private function wrap(array $data): array {
        return array_merge(['status' => 'ok'], $data, $this->meta());
    }

    private function error(string $code, string $message): array {
        return ['status' => 'error', 'code' => $code, 'message' => $message];
    }

    private function paginate(array $items): array {
        $page     = max(1, (int) sanitize_text_field($_GET['page']     ?? 1));
        $per_page = min(100, max(1, (int) sanitize_text_field($_GET['per_page'] ?? 100)));
        $total    = count($items);
        return [
            'items'      => array_values(array_slice($items, ($page - 1) * $per_page, $per_page)),
            'pagination' => [
                'page'        => $page,
                'per_page'    => $per_page,
                'total'       => $total,
                'total_pages' => (int) ceil($total / $per_page),
            ],
        ];
    }

    // ── Endpoint handlers ─────────────────────────────────────────────────────

    private function get_index(): array {
        $base = self::BASE_URL . '/' . self::API_BASE;
        return $this->wrap([
            'name'        => 'GO MO Group Public API',
            'version'     => self::VERSION,
            'description' => 'Public read-only REST API for GO MO Group. No authentication required.',
            'endpoints'   => [
                ['route' => '/index.json',              'url' => $base . '/index.json',              'description' => 'This index'],
                ['route' => '/schema.json',             'url' => $base . '/schema.json',             'description' => 'Endpoint schema and filter definitions'],
                ['route' => '/company.json',            'url' => $base . '/company.json',            'description' => 'Company identity and overview'],
                ['route' => '/services.json',           'url' => $base . '/services.json',           'description' => 'All services — filter by ?category= or ?tag='],
                ['route' => '/services/{id}.json',      'url' => $base . '/services/{id}.json',      'description' => 'Single service by ID'],
                ['route' => '/case-studies.json',       'url' => $base . '/case-studies.json',       'description' => 'Case studies — filter by ?industry=, ?segment=, ?service='],
                ['route' => '/case-studies/{id}.json',  'url' => $base . '/case-studies/{id}.json',  'description' => 'Single case study by ID'],
                ['route' => '/team.json',               'url' => $base . '/team.json',               'description' => 'Leadership team profiles'],
                ['route' => '/offices.json',            'url' => $base . '/offices.json',            'description' => 'Office locations'],
                ['route' => '/contact.json',            'url' => $base . '/contact.json',            'method' => 'POST', 'description' => 'Submit a qualified B2B inquiry (POST only — requires user_authorized_submission: true)'],
            ],
            'openapi' => self::BASE_URL . '/openapi.json',
        ]);
    }

    private function get_schema(): array {
        return $this->wrap([
            'schema' => [
                'company'      => ['route' => '/company.json',      'filters' => [],                                              'pagination' => false],
                'services'     => ['route' => '/services.json',     'filters' => ['category' => 'string', 'tag' => 'string'],     'pagination' => true, 'single' => '/services/{id}.json'],
                'case-studies' => ['route' => '/case-studies.json', 'filters' => ['industry' => 'string', 'segment' => 'string', 'service' => 'string'], 'pagination' => true, 'single' => '/case-studies/{id}.json'],
                'team'         => ['route' => '/team.json',         'filters' => [],                                              'pagination' => true],
                'offices'      => ['route' => '/offices.json',      'filters' => [],                                              'pagination' => true],
                'contact'      => [
                    'route'   => '/contact.json',
                    'method'  => 'POST',
                    'filters' => [],
                    'pagination' => false,
                    'required_fields' => ['contact_person.name', 'contact_person.email', 'company.name', 'inquiry.service_interest', 'inquiry.project_summary', 'agent_context.user_authorized_submission'],
                    'allowed_service_interest' => ['GEO / AI Search Visibility', 'SEO', 'Paid Media / SEM', 'UX / CRO', 'Website Design & Development', 'AI / Automation', 'Digital Strategy', 'Analytics & Reporting', 'Other'],
                    'agent_instructions' => [
                        'Submit only after explicit user consent.',
                        'Do not submit without user permission.',
                        'Do not invent missing contact or company details.',
                        'Do not submit personal data unless provided by the user.',
                    ],
                ],
            ],
        ]);
    }

    private function company_response(): array {
        $json = $this->load_json('company');
        if (!$json) return $this->error('unavailable', 'Company data not found.');
        return $this->wrap(['data' => $json['data']]);
    }

    private function services_list(): array {
        $json = $this->load_json('services');
        if (!$json) return $this->error('unavailable', 'Services data not found.');

        $items = $json['data'];

        if (!empty($_GET['category'])) {
            $cat   = strtolower(sanitize_text_field($_GET['category']));
            $items = array_filter($items, fn($s) => strtolower($s['category'] ?? '') === $cat);
        }
        if (!empty($_GET['tag'])) {
            $tag   = strtolower(sanitize_text_field($_GET['tag']));
            $items = array_filter($items, fn($s) => in_array($tag, $s['tags'] ?? [], true));
        }

        $paged = $this->paginate(array_values($items));
        return $this->wrap(['data' => $paged['items'], 'pagination' => $paged['pagination']]);
    }

    private function cases_list(): array {
        $json = $this->load_json('case-studies');
        if (!$json) return $this->error('unavailable', 'Case studies data not found.');

        $items = $json['data'];

        if (!empty($_GET['industry'])) {
            $v     = strtolower(sanitize_text_field($_GET['industry']));
            $items = array_filter($items, fn($c) => strtolower($c['industry'] ?? '') === $v);
        }
        if (!empty($_GET['segment'])) {
            $v     = strtolower(sanitize_text_field($_GET['segment']));
            $items = array_filter($items, fn($c) => strtolower($c['segment'] ?? '') === $v);
        }
        if (!empty($_GET['service'])) {
            $v     = strtolower(sanitize_text_field($_GET['service']));
            $items = array_filter($items, fn($c) => in_array($v, $c['services'] ?? [], true));
        }

        $paged = $this->paginate(array_values($items));
        return $this->wrap(['data' => $paged['items'], 'pagination' => $paged['pagination']]);
    }

    private function simple_list(string $file): array {
        $json = $this->load_json($file);
        if (!$json) return $this->error('unavailable', ucfirst($file) . ' data not found.');
        $paged = $this->paginate($json['data']);
        return $this->wrap(['data' => $paged['items'], 'pagination' => $paged['pagination']]);
    }

    private function single(string $file, string $id): array {
        $json = $this->load_json($file);
        if (!$json) return $this->error('unavailable', ucfirst($file) . ' data not found.');
        foreach ($json['data'] as $item) {
            if (($item['id'] ?? '') === $id) return $this->wrap(['data' => $item]);
        }
        status_header(404);
        return $this->error('not_found', "No item found with id '{$id}' in {$file}.");
    }

    // ── Contact submission (POST /agent/v1/contact.json) ─────────────────────

    private function contact_submission(): array {
        header('Cache-Control: no-store, no-cache');

        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method !== 'POST') {
            status_header(405);
            header('Allow: POST, OPTIONS');
            return $this->error('method_not_allowed', 'This endpoint only accepts POST requests.');
        }

        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
            status_header(400);
            return $this->error('invalid_json', 'Request body must be valid JSON.');
        }

        // Validate user authorisation first — must be explicit true
        if (($body['agent_context']['user_authorized_submission'] ?? null) !== true) {
            status_header(403);
            return $this->error('not_authorized', 'agent_context.user_authorized_submission must be true. Do not submit without explicit user consent.');
        }

        // Check required fields
        $required = [
            'contact_person.name',
            'contact_person.email',
            'company.name',
            'inquiry.service_interest',
            'inquiry.project_summary',
        ];
        $missing = [];
        foreach ($required as $field) {
            [$section, $key] = explode('.', $field, 2);
            $v = $body[$section][$key] ?? '';
            if ($v === null || $v === '') {
                $missing[] = $field;
            }
        }
        if ($missing) {
            status_header(400);
            return ['status' => 'error', 'message' => 'Missing required fields.', 'missing_fields' => $missing];
        }

        // Validate email
        $email = sanitize_email($body['contact_person']['email']);
        if (!is_email($email)) {
            status_header(400);
            return $this->error('invalid_email', 'contact_person.email is not a valid email address.');
        }

        // Sanitize all inputs
        $name     = sanitize_text_field($body['contact_person']['name']         ?? '');
        $phone    = sanitize_text_field($body['contact_person']['phone']         ?? '');
        $job      = sanitize_text_field($body['contact_person']['job_title']     ?? '');
        $co_name  = sanitize_text_field($body['company']['name']                 ?? '');
        $website  = esc_url_raw($body['company']['website']                      ?? '');
        $country  = sanitize_text_field($body['company']['country']              ?? '');
        $industry = sanitize_text_field($body['company']['industry']             ?? '');
        $co_size  = sanitize_text_field($body['company']['company_size']         ?? '');
        $service  = sanitize_text_field($body['inquiry']['service_interest']     ?? '');
        $summary  = sanitize_textarea_field($body['inquiry']['project_summary']  ?? '');
        $challenge = sanitize_textarea_field($body['inquiry']['business_challenge'] ?? '');
        $timeline = sanitize_text_field($body['inquiry']['timeline']             ?? '');
        $budget   = sanitize_text_field($body['inquiry']['budget_range']         ?? '');
        $pref     = sanitize_text_field($body['inquiry']['preferred_contact_method'] ?? 'email');
        $agent    = sanitize_text_field($body['agent_context']['agent_name']     ?? '');
        $source   = sanitize_text_field($body['agent_context']['source']         ?? '');

        $lead_id = 'gomo-lead-' . gmdate('Y') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));

        $subject = "[Agent Lead] {$co_name} — {$service}";
        $message  = "New B2B inquiry via the GO MO Agent API.\n\n";
        $message .= "Lead ID:   {$lead_id}\n";
        $message .= "Submitted: " . gmdate('Y-m-d H:i:s') . " UTC\n\n";
        $message .= "── Contact ─────────────────────────\n";
        $message .= "Name:      {$name}\n";
        $message .= "Email:     {$email}\n";
        $message .= "Phone:     {$phone}\n";
        $message .= "Job title: {$job}\n\n";
        $message .= "── Company ─────────────────────────\n";
        $message .= "Company:   {$co_name}\n";
        $message .= "Website:   {$website}\n";
        $message .= "Country:   {$country}\n";
        $message .= "Industry:  {$industry}\n";
        $message .= "Size:      {$co_size}\n\n";
        $message .= "── Inquiry ─────────────────────────\n";
        $message .= "Service:   {$service}\n";
        $message .= "Summary:   {$summary}\n";
        $message .= "Challenge: {$challenge}\n";
        $message .= "Timeline:  {$timeline}\n";
        $message .= "Budget:    {$budget}\n";
        $message .= "Contact by: {$pref}\n\n";
        $message .= "── Agent Context ───────────────────\n";
        $message .= "Agent:     {$agent}\n";
        $message .= "Source:    {$source}\n";
        $message .= "User authorised: Yes\n";

        wp_mail('ha@gomogroup.com', $subject, $message, ["Reply-To: {$name} <{$email}>"]);

        return [
            'status'    => 'success',
            'message'   => 'Inquiry submitted successfully.',
            'lead_id'   => $lead_id,
            'next_step' => 'GO MO Group will review the inquiry and respond via the preferred contact method within 1 business day.',
        ];
    }
}

// ── Activation hooks ──────────────────────────────────────────────────────────
register_activation_hook(__FILE__, ['GoMoPublicAPI', 'activate']);
register_deactivation_hook(__FILE__, ['GoMoPublicAPI', 'deactivate']);

new GoMoPublicAPI();


// ════════════════════════════════════════════════════════════════
//  DATA SYNC — pulls live WordPress data into JSON files
// ════════════════════════════════════════════════════════════════

function gomo_data_dir(): string {
    return plugin_dir_path(__FILE__) . 'data/';
}

function gomo_run_sync(): array {
    $sync = new GoMoDataSync(gomo_data_dir());
    return $sync->sync_all();
}

// ── 1. Auto-sync on post save (debounced) ────────────────────────────────────
add_action('save_post', function (int $post_id): void {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    if (get_transient('gomo_sync_debounce')) return;
    set_transient('gomo_sync_debounce', true, 30);
    add_action('shutdown', function (): void {
        gomo_run_sync();
    });
});

// ── 2. Admin page ─────────────────────────────────────────────────────────────
add_action('admin_menu', function (): void {
    add_management_page('GO MO API Sync', 'GO MO API', 'manage_options', 'gomo-api-sync', 'gomo_admin_page');
});

function gomo_admin_page(): void {
    $last   = get_option('gomo_api_last_sync', null);
    $base   = 'https://www.gomogroup.com/agent/v1';
    $routes     = ['/index.json', '/company.json', '/services.json', '/case-studies.json', '/team.json', '/offices.json', '/schema.json', '/openapi.json (via root)'];
    $post_routes = ['/contact.json'];
    ?>
    <div class="wrap">
        <h1>GO MO Group — Public API</h1>
        <?php if (isset($_GET['gomo_synced'])): ?>
            <div class="notice notice-success is-dismissible"><p>Sync complete. JSON files updated.</p></div>
        <?php endif; ?>

        <p>API base: <code><?php echo esc_html($base); ?></code></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('gomo_sync', 'gomo_nonce'); ?>
            <input type="hidden" name="action" value="gomo_sync_now">
            <?php submit_button('Sync Now', 'primary', 'submit', false); ?>
        </form>

        <?php if ($last): ?>
            <h2>Last sync — <?php echo esc_html($last['time']); ?></h2>
            <table class="widefat striped" style="max-width:420px;margin-top:12px;">
                <thead><tr><th>Type</th><th>Items</th></tr></thead>
                <tbody>
                <?php foreach ($last['results'] as $type => $count): ?>
                    <tr><td><?php echo esc_html($type); ?></td><td><?php echo (int)$count; ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!empty($last['log'])): ?>
                <h3 style="margin-top:20px;">Log</h3>
                <pre style="background:#f6f7f7;padding:12px;max-width:680px;"><?php echo esc_html(implode("\n", $last['log'])); ?></pre>
            <?php endif; ?>
        <?php endif; ?>

        <h2 style="margin-top:28px;">Live endpoints — GET</h2>
        <ul>
        <?php foreach ($routes as $r): $url = $base . $r; ?>
            <li><a href="<?php echo esc_url($url); ?>" target="_blank"><?php echo esc_html($url); ?></a></li>
        <?php endforeach; ?>
        </ul>

        <h2 style="margin-top:16px;">Live endpoints — POST</h2>
        <ul>
        <?php foreach ($post_routes as $r): $url = $base . $r; ?>
            <li><code><?php echo esc_html($url); ?></code> — POST only (agent-submitted B2B inquiries)</li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

// ── 3. Handle admin form POST ─────────────────────────────────────────────────
add_action('admin_post_gomo_sync_now', function (): void {
    if (!current_user_can('manage_options')) wp_die('Not allowed.');
    check_admin_referer('gomo_sync', 'gomo_nonce');
    gomo_run_sync();
    wp_redirect(add_query_arg(['page' => 'gomo-api-sync', 'gomo_synced' => '1'], admin_url('tools.php')));
    exit;
});

// ── 4. WP-CLI: wp gomo sync ──────────────────────────────────────────────────
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('gomo sync', function (): void {
        WP_CLI::log('Syncing GO MO data...');
        $sync    = new GoMoDataSync(gomo_data_dir());
        $results = $sync->sync_all();
        foreach ($results as $type => $count) {
            WP_CLI::success("{$type}: {$count} item(s)");
        }
        foreach ($sync->get_log() as $line) {
            WP_CLI::log($line);
        }
    });
}
