<?php
/**
 * GoMoDataSync — pulls live data from WordPress and writes it to the JSON data files.
 *
 * Supports:
 *  - Custom Post Types (auto-detected)
 *  - Page hierarchies as fallback
 *  - ACF fields (optional — falls back to post meta and post content)
 *  - Backup before overwrite
 *  - Detailed log for debugging
 */

defined('ABSPATH') || exit;

class GoMoDataSync {

    private string $data_dir;
    private array  $log = [];

    /**
     * Known CPT slugs to check per data type, in order of preference.
     * Add your own CPT slugs here if GO MO Group uses custom ones.
     */
    private const CPT_CANDIDATES = [
        'cases'    => ['case', 'case-study', 'case_study', 'cases'],
        'services' => ['service', 'gomo_service', 'services'],
        'team'     => ['team-member', 'team_member', 'medarbetare', 'team', 'person'],
    ];

    public function __construct(string $data_dir) {
        $this->data_dir = trailingslashit($data_dir);
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Sync all data types. Returns a summary array.
     */
    public function sync_all(): array {
        $results = [
            'case-studies' => $this->sync_cases(),
            'services'     => $this->sync_services(),
            'team'         => $this->sync_team(),
            'company'      => $this->sync_company(),
            'offices'      => $this->sync_offices(),
        ];

        update_option('gomo_api_last_sync', [
            'time'    => current_time('c'),
            'results' => $results,
            'log'     => $this->log,
        ]);

        return $results;
    }

    public function get_log(): array {
        return $this->log;
    }

    // ── Case Studies ──────────────────────────────────────────────────────────

    private function sync_cases(): int {
        $cpt   = $this->detect_cpt('cases');
        $args  = [
            'post_type'      => $cpt ? [$cpt] : ['page'],
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        // If falling back to pages, scope to the case studies section
        if (!$cpt) {
            $parent = $this->find_parent_page(['case', 'en/case', 'case-studies', 'en/case-studies']);
            if (!$parent) {
                $this->log("cases: no CPT detected and no parent page found — JSON unchanged");
                return 0;
            }
            $args['post_parent'] = $parent->ID;
        }

        $posts = get_posts($args);
        if (empty($posts)) {
            $this->log("cases: query returned 0 posts — JSON unchanged");
            return 0;
        }

        $data = [];
        foreach ($posts as $post) {
            $results_raw = $this->acf('results', $post->ID, []);
            // Normalise results: ensure it's an array of {metric, value, period}
            $results = [];
            if (is_array($results_raw)) {
                foreach ($results_raw as $r) {
                    if (is_array($r)) {
                        $results[] = [
                            'metric' => $r['metric'] ?? $r[0] ?? '',
                            'value'  => $r['value']  ?? $r[1] ?? '',
                            'period' => $r['period']  ?? $r[2] ?? '',
                        ];
                    }
                }
            }

            $data[] = [
                'id'       => $post->post_name,
                'client'   => $this->acf('client_name',  $post->ID, $post->post_title),
                'industry' => $this->acf('industry',     $post->ID, ''),
                'segment'  => $this->acf('segment',      $post->ID, ''),
                'services' => (array) $this->acf('services_used', $post->ID, []),
                'headline' => $this->acf('headline',     $post->ID, $post->post_title),
                'results'  => $results,
                'url'      => get_permalink($post->ID),
            ];
        }

        $this->write_json('case-studies', $data);
        $this->log("cases: synced " . count($data) . " items (post_type=" . ($cpt ?? 'page') . ")");
        return count($data);
    }

    // ── Services ──────────────────────────────────────────────────────────────

    private function sync_services(): int {
        $cpt  = $this->detect_cpt('services');
        $args = [
            'post_type'      => $cpt ? [$cpt] : ['page'],
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ];

        if (!$cpt) {
            $parent = $this->find_parent_page(['services', 'en/services', 'vara-tjanster']);
            if (!$parent) {
                $this->log("services: no CPT detected and no parent page found — JSON unchanged");
                return 0;
            }
            $args['post_parent'] = $parent->ID;
        }

        $posts = get_posts($args);
        if (empty($posts)) {
            $this->log("services: query returned 0 posts — JSON unchanged");
            return 0;
        }

        $data = [];
        foreach ($posts as $post) {
            $price_from = $this->acf('price_from', $post->ID);
            $excerpt    = $post->post_excerpt
                ? wp_strip_all_tags($post->post_excerpt)
                : wp_strip_all_tags(wp_trim_words($post->post_content, 60));

            $data[] = [
                'id'          => $post->post_name,
                'name'        => $post->post_title,
                'slug'        => $post->post_name,
                'category'    => $this->acf('category',    $post->ID, ''),
                'description' => $this->acf('description', $post->ID, $excerpt),
                'pricing'     => [
                    'from'     => $price_from !== null ? (int) $price_from : null,
                    'currency' => $this->acf('price_currency', $post->ID, null),
                    'model'    => $this->acf('price_model',    $post->ID, 'contact for pricing'),
                ],
                'url'         => get_permalink($post->ID),
                'tags'        => (array) $this->acf('tags', $post->ID, []),
            ];
        }

        $this->write_json('services', $data);
        $this->log("services: synced " . count($data) . " items (post_type=" . ($cpt ?? 'page') . ")");
        return count($data);
    }

    // ── Team ──────────────────────────────────────────────────────────────────

    private function sync_team(): int {
        $cpt = $this->detect_cpt('team');
        if (!$cpt) {
            $this->log("team: no team CPT found — JSON unchanged");
            return 0;
        }

        $posts = get_posts([
            'post_type'      => $cpt,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ]);

        if (empty($posts)) {
            $this->log("team: CPT '{$cpt}' exists but has 0 published posts — JSON unchanged");
            return 0;
        }

        $data = [];
        foreach ($posts as $post) {
            $data[] = [
                'id'         => $post->post_name,
                'name'       => $post->post_title,
                'role'       => $this->acf('role',       $post->ID, ''),
                'email'      => $this->acf('email',      $post->ID, null),
                'telephone'  => $this->acf('telephone',  $post->ID, null),
                'location'   => $this->acf('location',   $post->ID, ''),
                'profileUrl' => get_permalink($post->ID),
                'linkedin'   => $this->acf('linkedin',   $post->ID, null),
            ];
        }

        $this->write_json('team', $data);
        $this->log("team: synced " . count($data) . " items from CPT '{$cpt}'");
        return count($data);
    }

    // ── Company ───────────────────────────────────────────────────────────────

    private function sync_company(): int {
        $data = [
            'name'        => $this->acf_option('company_name',        get_bloginfo('name')),
            'legalName'   => $this->acf_option('legal_name',          'GO MO Group AB'),
            'url'         => home_url(),
            'founded'     => $this->acf_option('founded',             '2013'),
            'description' => $this->acf_option('company_description', get_bloginfo('description')),
            'industry'    => $this->acf_option('industry',            'B2B Digital Marketing, Generative Engine Optimization, AI Implementation'),
            'employees'   => $this->acf_option('employees',           '150+'),
            'headquarters' => [
                'streetAddress' => $this->acf_option('hq_street',   'Lennart Torstenssonsgatan 8'),
                'postalCode'    => $this->acf_option('hq_postcode',  '412 56'),
                'addressLocality' => $this->acf_option('hq_city',   'Gothenburg'),
                'addressCountry'  => $this->acf_option('hq_country', 'SE'),
            ],
            'certifications' => (array) $this->acf_option('certifications', []),
            'contact'        => [
                'general'     => $this->acf_option('email_general',      get_bloginfo('admin_email')),
                'newBusiness' => $this->acf_option('email_new_business',  null),
                'ceo'         => $this->acf_option('email_ceo',           null),
            ],
            'socialMedia'    => [
                'linkedin' => $this->acf_option('social_linkedin', null),
                'twitter'  => $this->acf_option('social_twitter',  null),
                'facebook' => $this->acf_option('social_facebook', null),
                'youtube'  => $this->acf_option('social_youtube',  null),
            ],
            'keyPages' => [
                'home'        => home_url('/en/'),
                'about'       => home_url('/en/about-us/'),
                'services'    => home_url('/en/services/'),
                'caseStudies' => home_url('/en/case/'),
                'blog'        => home_url('/en/insights/'),
                'careers'     => home_url('/en/career/'),
                'contact'     => home_url('/en/contact-us/'),
            ],
        ];

        $path = $this->data_dir . 'company.json';
        if (file_exists($path)) copy($path, $path . '.bak');

        file_put_contents($path, json_encode([
            'status'    => 'ok',
            'version'   => '1.2',
            'endpoint'  => 'company',
            'generated' => gmdate('c'),
            'source'    => 'https://www.gomogroup.com/agent/v1/company.json',
            'data'      => $data,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->log("company: synced from site options" . (function_exists('get_field') ? " + ACF options" : " (ACF not active)"));
        return 1;
    }

    // ── Offices ───────────────────────────────────────────────────────────────

    private function sync_offices(): int {
        if (!function_exists('get_field')) {
            $this->log("offices: ACF not active — JSON unchanged");
            return 0;
        }

        $offices_field = get_field('offices', 'option');
        if (empty($offices_field) || !is_array($offices_field)) {
            $this->log("offices: ACF options field 'offices' not found — JSON unchanged");
            return 0;
        }

        $data = [];
        foreach ($offices_field as $office) {
            $data[] = [
                'id'            => sanitize_title($office['city']           ?? ''),
                'label'         => $office['label']          ?? '',
                'city'          => $office['city']           ?? '',
                'country'       => $office['country']        ?? '',
                'countryCode'   => $office['country_code']   ?? '',
                'streetAddress' => $office['street_address'] ?? '',
                'postalCode'    => $office['postal_code']    ?? '',
                'email'         => $office['email']          ?? '',
                'telephone'     => $office['telephone']      ?? null,
                'timezone'      => $office['timezone']       ?? '',
                'mapsUrl'       => $office['maps_url']       ?? null,
            ];
        }

        $this->write_json('offices', $data);
        $this->log("offices: synced " . count($data) . " offices from ACF options");
        return count($data);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Detect the first registered CPT that matches the candidate list.
     */
    private function detect_cpt(string $type): ?string {
        $registered = get_post_types(['public' => true], 'names');
        foreach (self::CPT_CANDIDATES[$type] ?? [] as $slug) {
            if (isset($registered[$slug])) return $slug;
        }
        return null;
    }

    /**
     * Find a WordPress page by trying a list of possible paths.
     */
    private function find_parent_page(array $paths): ?WP_Post {
        foreach ($paths as $path) {
            $page = get_page_by_path($path);
            if ($page) return $page;
        }
        return null;
    }

    /**
     * Read an ACF field, or fall back to post meta, or return $default.
     */
    private function acf(string $key, int $post_id, mixed $default = null): mixed {
        if (function_exists('get_field')) {
            $val = get_field($key, $post_id);
            if ($val !== false && $val !== null && $val !== '') return $val;
        }
        $val = get_post_meta($post_id, $key, true);
        return ($val !== '' && $val !== false) ? $val : $default;
    }

    /**
     * Read from ACF options page, or fall back to a WP option, or return $default.
     */
    private function acf_option(string $key, mixed $default = null): mixed {
        if (function_exists('get_field')) {
            $val = get_field($key, 'option');
            if ($val !== false && $val !== null && $val !== '') return $val;
        }
        $val = get_option('gomo_' . $key, null);
        return $val !== null ? $val : $default;
    }

    /**
     * Write a list of items to a JSON data file, with backup.
     */
    private function write_json(string $file, array $items): void {
        $path = $this->data_dir . $file . '.json';

        // Backup existing file
        if (file_exists($path)) copy($path, $path . '.bak');

        $payload = [
            'status'    => 'ok',
            'version'   => '1.2',
            'endpoint'  => $file,
            'generated' => gmdate('c'),
            'source'    => 'https://www.gomogroup.com/agent/v1/' . $file . '.json',
            'data'      => $items,
            'meta'      => [
                'total'   => count($items),
                'license' => 'Content © GO MO Group AB.',
                'contact' => 'hello@gomogroup.com',
            ],
        ];

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function log(string $message): void {
        $this->log[] = '[' . gmdate('H:i:s') . '] ' . $message;
    }
}
