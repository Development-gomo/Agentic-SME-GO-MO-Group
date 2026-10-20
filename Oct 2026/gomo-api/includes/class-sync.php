<?php
/**
 * GO MO Agent API — admin sync tool (Tools → GO MO Agent API).
 *
 * v1.3 design rule: the sync NEVER overwrites curated data.
 *   - company / services / case-studies / team / offices are curated files (data/*.json).
 *     The sync only refreshes their dateModified values and reports WordPress posts that
 *     are missing from them, so a human can curate the entry. (v1.2 rebuilt these from raw
 *     post fields and published empty descriptions, "(copy)" drafts and wrong contacts.)
 *   - insights / events / careers / pages are fully derivable and are regenerated.
 *
 * Every file written carries the canonical source URL https://www.gomogroup.com/agent/v1/{endpoint}.json.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-snapshot.php';
require_once __DIR__ . '/class-discovery.php';

final class GOMO_API_Sync
{
    private $pending = [];

    /** WordPress post types on gomogroup.com (from the Yoast sitemap index). */
    private const POST_TYPES = [
        'insight' => 'insights',
        'case'    => 'case',
        'service' => 'services',
        'solution'=> 'solutions',
        'event'   => 'events',
        'team'    => 'team',
        'career'  => 'job-posts',
    ];

    /** Topic keywords for insights (checked against slug + title, lowercase). */
    private const TOPICS = [
        'geo'           => ['geo', 'generative-engine', 'ai-search', 'llms-txt', 'llm', 'ai-overview', 'aeo', 'answer-engine', 'chatgpt', 'perplexity', 'ai-traffic', 'synlig-i-ai', 'ai-sok', 'agentic', 'agent'],
        'ai'            => ['ai', 'generative', 'generativ', 'gpt', 'copilot', 'gemini'],
        'seo'           => ['seo', 'sokmotor', 'google', 'migration', 'bounce', 'links', 'lankar', 'voice', 'rost', 'mum', 'ux'],
        'paid'          => ['sea', 'ads', 'annons', 'programmatic', 'ppc', 'cookieless', 'cookie'],
        'b2b-marketing' => ['b2b', 'pull', 'push', 'demand', 'asbx', 'lead', 'sales', 'forsaljning', 'marketing', 'marknadsforing', 'people-based', 'abm'],
        'content'       => ['content', 'innehall', 'thought-leadership', 'tankeledarskap', 'youtube', 'video'],
        'leadership'    => ['cmo', 'leadership', 'ledarskap', 'interview', 'intervju', 'mindset'],
        'company'       => ['go-mo', 'gomo', 'promoted', 'partnership', 'india', 'indien', 'client-survey', 'kundundersokning', 'gender'],
        'careers'       => ['job', 'jobb', 'career', 'karriar', 'trainee', 'associate', 'engineering'],
    ];

    private const CURATED  = ['company', 'services', 'case-studies', 'team', 'offices'];
    private const DERIVED  = ['insights', 'events', 'careers', 'pages'];

    public static function init(): void
    {
        $self = new self();
        add_action('admin_menu', [$self, 'menu']);
        add_action('admin_post_gomo_api_sync', [$self, 'handle_sync']);
        add_action('admin_post_gomo_api_settings', [$self, 'handle_settings']);
    }

    public function menu(): void
    {
        add_management_page('GO MO Agent API', 'GO MO Agent API', 'manage_options', 'gomo-agent-api', [$this, 'page']);
    }

    // ── Admin page ───────────────────────────────────────────────────────────

    public function page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $report = get_transient('gomo_api_sync_report');
        $serve  = get_option('gomo_api_serve_agent_page', '1') === '1';
        $leads  = get_option('gomo_api_contact_enabled', '1') === '1';
        echo '<div class="wrap"><h1>GO MO Agent API <small>v' . esc_html(GOMO_API_VERSION) . '</small></h1>';

        if ($report) {
            echo '<div class="notice notice-info"><pre style="white-space:pre-wrap">' . esc_html($report) . '</pre></div>';
            delete_transient('gomo_api_sync_report');
        }

        echo '<h2>Data files</h2><table class="widefat striped" style="max-width:900px"><thead><tr><th>Endpoint</th><th>Kind</th><th>Items</th><th>Generated</th></tr></thead><tbody>';
        foreach (array_merge(self::CURATED, self::DERIVED) as $ep) {
            $json  = $this->read($ep);
            $count = isset($json['data']) && is_array($json['data']) ? (array_is_list_compat($json['data']) ? count($json['data']) : 1) : '—';
            $url   = GOMO_API_BASE . "/{$ep}.json";
            printf(
                '<tr><td><a href="%s" target="_blank">%s</a></td><td>%s</td><td>%s</td><td>%s</td></tr>',
                esc_url($url), esc_html("/{$ep}.json"),
                in_array($ep, self::CURATED, true) ? 'curated (never overwritten)' : 'auto-generated',
                esc_html((string) $count), esc_html($json['_meta']['generated'] ?? 'missing')
            );
        }
        echo '</tbody></table>';

        echo '<h2>Sync</h2><p>Regenerates insights, events, careers and the page index from published WordPress content (EN + SV via WPML), refreshes modified dates on curated files, and lists published services/cases that still need a curated entry.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('gomo_api_sync');
        echo '<input type="hidden" name="action" value="gomo_api_sync">';
        submit_button('Sync now', 'primary', 'submit', false);
        echo '</form>';

        echo '<h2>Settings</h2><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('gomo_api_settings');
        echo '<input type="hidden" name="action" value="gomo_api_settings">';
        echo '<label><input type="checkbox" name="serve_agent_page" value="1" ' . checked($serve, true, false) . '> Serve <code>agent-page.html</code> at <code>/agent/</code> (overrides any WordPress page with that slug)</label><br><br>';
        echo '<label><input type="checkbox" name="contact_enabled" value="1" ' . checked($leads, true, false) . '> Accept agent contact submissions at <code>/agent/v1/contact.json</code> (sent to ' . esc_html(GOMO_LEAD_EMAIL) . '; limits: ' . (int) GOMO_CONTACT_PER_IP_HOUR . '/client/hour, ' . (int) GOMO_CONTACT_GLOBAL_HOUR . '/hour site-wide)</label><br><br>';
        submit_button('Save settings', 'secondary', 'submit', false);
        echo '</form>';

        echo '<h2>Endpoints</h2><ul>';
        foreach (['https://www.gomogroup.com/agent/', GOMO_API_BASE . '/index.json', 'https://www.gomogroup.com/openapi.json', 'https://www.gomogroup.com/llms.txt', 'https://www.gomogroup.com/ai.txt', 'https://www.gomogroup.com/api-catalog.json', 'https://www.gomogroup.com/mcp/health'] as $u) {
            echo '<li><a href="' . esc_url($u) . '" target="_blank">' . esc_html($u) . '</a></li>';
        }
        echo '</ul><p><strong>After updating the plugin:</strong> Settings → Permalinks → Save.</p></div>';
    }

    public function handle_settings(): void
    {
        if (!current_user_can('manage_options') || !check_admin_referer('gomo_api_settings')) {
            wp_die('Not allowed');
        }
        update_option('gomo_api_serve_agent_page', isset($_POST['serve_agent_page']) ? '1' : '0');
        update_option('gomo_api_contact_enabled', isset($_POST['contact_enabled']) ? '1' : '0');
        // Rules registered on this request's 'init' still reflect the old setting, so flushing here would
        // re-save them. Deleting the cache makes WordPress rebuild the rules on the next request instead.
        delete_option('rewrite_rules');
        wp_safe_redirect(admin_url('tools.php?page=gomo-agent-api'));
        exit;
    }

    public function handle_sync(): void
    {
        if (!current_user_can('manage_options') || !check_admin_referer('gomo_api_sync')) {
            wp_die('Not allowed');
        }
        set_transient('gomo_api_sync_report', $this->run(), 300);
        wp_safe_redirect(admin_url('tools.php?page=gomo-agent-api'));
        exit;
    }

    // ── Sync ─────────────────────────────────────────────────────────────────

    public function run(): string
    {
        $lock = @fopen(GOMO_API_DIR . '.sync.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) { fclose($lock); }
            return 'FAILED: Sync is already running or the publication directory is not writable. Previous snapshot retained.';
        }
        try {
            foreach (array_merge(['page'], array_values(self::POST_TYPES)) as $type) {
                if (!post_type_exists($type)) { throw new RuntimeException('Required post type missing: ' . $type); }
            }
            $this->pending = [];
            $root = GOMO_API_Snapshot::root();
            foreach (array_merge(array_map(static fn($e) => "data/$e.json", array_merge(self::CURATED, self::DERIVED)),
                ['agent-page.html', 'agent-index.json', 'llms.txt', 'ai.txt', 'api-catalog.json', 'openapi.json']) as $file) {
                $contents = @file_get_contents($root . $file);
                if ($contents === false) { throw new RuntimeException('Missing snapshot input: ' . $file); }
                if (substr($file, -5) === '.json' && !is_array(json_decode($contents, true))) {
                    throw new RuntimeException('Invalid snapshot input: ' . $file);
                }
                $this->pending[$file] = $contents;
            }
            $report = $this->prepare();
            $files = GOMO_API_Discovery::render($this->pending);
            $id = GOMO_API_Snapshot::publish($files);
            return $report . "\nPublished complete snapshot: " . $id;
        } catch (Throwable $e) {
            return 'FAILED: ' . $e->getMessage() . ' Previous snapshot retained.';
        } finally {
            $this->pending = [];
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function prepare(): string
    {
        $log = ['Sync ' . gmdate('c')];

        $insights = $this->collect('insight');
        $this->write_json('insights', $insights);
        $log[] = sprintf('insights: %d articles (%d en, %d sv)', count($insights), $this->count_lang($insights, 'en'), $this->count_lang($insights, 'sv'));

        $events = $this->merge_curated('events', $this->collect('event'));
        $this->write_json('events', $events);
        $log[] = 'events: ' . count($events);

        $careers = $this->merge_curated('careers', $this->collect('career'));
        $this->write_json('careers', $careers);
        $log[] = 'careers: ' . count($careers);

        $pages = $this->collect_pages();
        $this->write_json('pages', $pages);
        $log[] = 'pages: ' . count($pages) . ' URLs';

        // Curated files: refresh dates, report gaps — never rewrite text.
        foreach (['services' => 'service', 'case-studies' => 'case'] as $ep => $kind) {
            $json = $this->read($ep);
            if (!$json) {
                $log[] = "{$ep}: data file missing — restore it from the plugin package.";
                continue;
            }
            $known = [];
            foreach ($json['data'] as $i => $item) {
                foreach (['url', 'url_sv'] as $f) {
                    if (!empty($item[$f])) {
                        $known[untrailingslashit($item[$f])] = $i;
                    }
                }
            }
            $missing = [];
            foreach ($this->posts(self::POST_TYPES[$kind]) as $post) {
                $url = untrailingslashit(get_permalink($post));
                if (isset($known[$url])) {
                    $json['data'][$known[$url]]['dateModified'] = get_post_modified_time('Y-m-d', true, $post);
                } else {
                    $missing[] = '  - ' . get_the_title($post) . ' → ' . get_permalink($post);
                }
            }
            $this->write_json($ep, $json['data'], $json);
            $log[] = "{$ep}: dates refreshed" . ($missing ? '; NEEDS CURATION (published but not in data file):' . "\n" . implode("\n", $missing) : '; all published posts covered');
        }

        return implode("\n", $log);
    }

    /** Published, public, non-duplicate posts of a type (all WPML languages). */
    private function posts(string $post_type): array
    {
        if (!post_type_exists($post_type)) {
            throw new RuntimeException('Required post type missing: ' . $post_type);
        }
        // WPML: return every language, not just the admin's current one.
        do_action('wpml_switch_language', 'all');
        try {
            global $wpdb;
            $q = new WP_Query([
                'post_type'        => $post_type,
                'post_status'      => 'publish',
                'posts_per_page'   => -1,
                'suppress_filters' => false,
                'orderby'          => 'date',
                'order'            => 'DESC',
            ]);
            if (!empty($wpdb->last_error)) { throw new RuntimeException('WordPress query failed: ' . $post_type); }
            $posts = array_filter($q->posts, static function (WP_Post $p) {
                // Duplicated drafts published by mistake ("… (copy)", slug-2) were the source of v1.2 junk entries.
                if (stripos($p->post_title, '(copy)') !== false || preg_match('/-2$/', $p->post_name)) {
                    return false;
                }
                return get_post_meta($p->ID, '_yoast_wpseo_meta-robots-noindex', true) !== '1';
            });
            return array_values($posts);
        } finally {
            do_action('wpml_switch_language', null);
        }
    }

    private function lang_of(WP_Post $p): string
    {
        $d = apply_filters('wpml_post_language_details', null, $p->ID);
        $code = is_array($d) ? ($d['language_code'] ?? '') : '';
        if (!$code) {
            $code = strpos((string) get_permalink($p), '/en/') !== false ? 'en' : 'sv';
        }
        return $code === 'en' ? 'en' : 'sv';
    }

    private function alternate_of(WP_Post $p, string $lang): ?string
    {
        $other = $lang === 'en' ? 'sv' : 'en';
        $id    = apply_filters('wpml_object_id', $p->ID, $p->post_type, false, $other);
        return ($id && (int) $id !== $p->ID && get_post_status($id) === 'publish') ? get_permalink($id) : null;
    }

    private function summary(WP_Post $p): string
    {
        $s = (string) get_post_meta($p->ID, '_yoast_wpseo_metadesc', true);
        if ($s === '') {
            $s = has_excerpt($p) ? get_the_excerpt($p) : wp_trim_words(wp_strip_all_tags(strip_shortcodes($p->post_content)), 45, '…');
        }
        return trim(html_entity_decode(wp_strip_all_tags($s), ENT_QUOTES, 'UTF-8'));
    }

    private function topics(WP_Post $p): array
    {
        $hay = strtolower(remove_accents($p->post_name . ' ' . $p->post_title));
        $out = [];
        foreach (self::TOPICS as $topic => $words) {
            foreach ($words as $w) {
                if (preg_match('/(^|[^a-z])' . preg_quote($w, '/') . '([^a-z]|$)/', str_replace(' ', '-', $hay))) {
                    $out[] = $topic;
                    break;
                }
            }
        }
        return $out ?: ['b2b-marketing'];
    }

    private function collect(string $kind): array
    {
        $items = [];
        foreach ($this->posts(self::POST_TYPES[$kind]) as $p) {
            $lang = $this->lang_of($p);
            $item = [
                'id'            => ($lang === 'sv' ? 'sv-' : '') . $p->post_name,
                'lang'          => $lang,
                'title'         => html_entity_decode(get_the_title($p), ENT_QUOTES, 'UTF-8'),
                'summary'       => $this->summary($p),
                'url'           => get_permalink($p),
                'alternate'     => $this->alternate_of($p, $lang),
                'datePublished' => get_post_time('Y-m-d', true, $p),
                'dateModified'  => get_post_modified_time('Y-m-d', true, $p),
            ];
            if ($kind === 'insight') {
                $item['author'] = get_the_author_meta('display_name', (int) $p->post_author) ?: null;
                $item['topics'] = $this->topics($p);
            }
            $items[] = $item;
        }
        usort($items, static fn($a, $b) => strcmp($b['datePublished'], $a['datePublished']));
        return $items;
    }

    /**
     * Keep hand-curated fields (dates, location, registration links…) for items that still exist.
     * Curated items are bilingual (url + url_sv) while WordPress has one post per language, so both
     * posts of a pair fold into the same item instead of producing a duplicate.
     */
    private function merge_curated(string $endpoint, array $fresh): array
    {
        $prev  = $this->read($endpoint)['data'] ?? [];
        $index = [];
        foreach ($prev as $i => $item) {
            foreach (['url', 'url_sv'] as $f) {
                if (!empty($item[$f])) {
                    $index[untrailingslashit($item[$f])] = $i;
                }
            }
        }
        $fresh_urls = [];
        foreach ($fresh as $item) {
            $fresh_urls[untrailingslashit($item['url'])] = true;
        }

        $out  = [];
        $used = [];
        foreach ($fresh as $item) {
            $key = untrailingslashit($item['url']);
            $alt = !empty($item['alternate']) ? untrailingslashit($item['alternate']) : null;
            $i   = $index[$key] ?? ($alt !== null ? ($index[$alt] ?? null) : null);

            if ($i !== null) {
                if (isset($used[$i])) {
                    continue; // the other-language post of an item already merged
                }
                $used[$i] = true;
                $merged   = array_merge($item, $prev[$i]); // curated fields win (id, url, url_sv, dates…)
                $merged['dateModified'] = max((string) ($prev[$i]['dateModified'] ?? ''), (string) $item['dateModified']);
                unset($merged['alternate'], $merged['lang']);
                $out[] = $merged;
                continue;
            }
            // New item: a Swedish post whose English translation is also new is folded into that one.
            if ($item['lang'] === 'sv' && $alt !== null && isset($fresh_urls[$alt])) {
                continue;
            }
            if ($item['lang'] === 'en') {
                $item['url_sv'] = $item['alternate'];
            } else {
                $item['url_sv'] = $item['url'];
                $item['url']    = null;
            }
            unset($item['alternate'], $item['lang']);
            $out[] = $item;
        }
        return $out;
    }

    private function collect_pages(): array
    {
        $types = array_merge(['page'], array_values(self::POST_TYPES));
        $map   = array_flip(self::POST_TYPES) + ['page' => 'page'];
        $rows  = [];
        foreach ($types as $pt) {
            foreach ($this->posts($pt) as $p) {
                $lang  = $this->lang_of($p);
                $url   = get_permalink($p);
                $kind  = $map[$pt] ?? 'page';
                if ($pt === 'page' && preg_match('#/(privacy-policy|cookie-policy|code-of-conduct|gdpr|cookies-regler)/#', $url)) {
                    $kind = 'legal';
                }
                $rows[] = [
                    'url'          => $url,
                    'lang'         => $lang,
                    'type'         => $kind,
                    'title'        => html_entity_decode(get_the_title($p), ENT_QUOTES, 'UTF-8'),
                    'alternate'    => $this->alternate_of($p, $lang),
                    'dateModified' => get_post_modified_time('Y-m-d', true, $p),
                ];
            }
        }
        // Rows added by hand to the curated index (they carry 'robots', e.g. noindex team profiles)
        // are skipped by posts(), so carry them over while they are still published.
        $have = array_flip(array_column($rows, 'url'));
        foreach (($this->read('pages')['data'] ?? []) as $old) {
            if (!empty($old['robots']) && !isset($have[$old['url']]) && url_to_postid($old['url'])) {
                $rows[] = $old;
            }
        }
        // WPML falls back to the other language's homepage when a page has no translation; that isn't an alternate.
        $homes = [trailingslashit(home_url('/')), trailingslashit(home_url('/en/'))];
        foreach ($rows as &$row) {
            if (!empty($row['alternate']) && in_array(trailingslashit($row['alternate']), $homes, true) && !in_array(trailingslashit($row['url']), $homes, true)) {
                $row['alternate'] = null;
            }
        }
        unset($row);
        usort($rows, static fn($a, $b) => [$a['type'], $a['lang'], $a['url']] <=> [$b['type'], $b['lang'], $b['url']]);
        return $rows;
    }

    // ── File IO ──────────────────────────────────────────────────────────────

    private function read(string $endpoint): ?array
    {
        $path = GOMO_API_Snapshot::root() . "data/{$endpoint}.json";
        $json = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;
        return is_array($json) ? $json : null;
    }

    private function count_lang(array $items, string $lang): int
    {
        return count(array_filter($items, static fn($i) => ($i['lang'] ?? '') === $lang));
    }

    /**
     * Write a data file with the standard envelope. Source URLs are always the canonical
     * public endpoint (fixes the v1.0/1.1 "api/v1/…" and "synced from WordPress" values).
     */
    private function write_json(string $endpoint, array $data, ?array $existing = null): void
    {
        $envelope = [
            'status' => 'ok',
            'data'   => $data,
            '_meta'  => array_merge($existing['_meta'] ?? [], [
                'source'    => GOMO_API_BASE . "/{$endpoint}.json",
                'endpoint'  => $endpoint,
                'canonical' => 'https://www.gomogroup.com',
                'api'       => GOMO_API_BASE,
                'generated' => gmdate('c'),
                'version'   => GOMO_API_VERSION,
                'languages' => ['en', 'sv'],
                'license'   => 'Content © GO MO Group AB. Cite gomogroup.com as the source; do not alter or fabricate figures.',
            ]),
        ];
        if (array_is_list_compat($data)) {
            $envelope['pagination'] = ['total' => count($data), 'page' => 1, 'per_page' => count($data), 'pages' => 1];
        }
        $previous = $this->read($endpoint);
        if ($data === [] && !empty($previous['data']) && !apply_filters('gomo_api_allow_empty_sync', false, $endpoint)) {
            throw new RuntimeException('Refusing to empty populated collection: ' . $endpoint . '. Verify removals and explicitly allow this endpoint with gomo_api_allow_empty_sync.');
        }
        $this->pending["data/{$endpoint}.json"] = GOMO_API_Snapshot::encode($envelope);
    }
}

/** array_is_list() polyfill for PHP < 8.1. */
function array_is_list_compat(array $a): bool
{
    return $a === [] || array_keys($a) === range(0, count($a) - 1);
}
