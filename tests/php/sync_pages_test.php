<?php
/**
 * Page-index regression tests for "Sync now", modelled on what the first live sync (2026-11-17) got wrong:
 * redirecting posts, 404 templates and /en/home/ were added, while /en/ and the English events were dropped.
 * All writes use an isolated copy of the plugin. Run: php tests/php/sync_pages_test.php
 */
define('ABSPATH', '/');
define('GOMO_API_VERSION', '1.4.1');
define('GOMO_API_BASE', 'https://www.gomogroup.com/agent/v1');
$root = dirname(__DIR__, 2);
$tmp = sys_get_temp_dir() . '/gomo-pages-' . getmypid();
exec('cp -R ' . escapeshellarg("$root/gomo-api") . ' ' . escapeshellarg($tmp));
define('GOMO_API_DIR', $tmp . '/');
require "$root/gomo-api/includes/class-sync.php";
const SITE = 'https://www.gomogroup.com';

class WP_Post {
    public $ID, $post_type, $post_name, $post_title, $post_author = 1, $post_content = 'A useful summary of the published content.';
    function __construct($id, $type, $name) { $this->ID = $id; $this->post_type = $type; $this->post_name = $name; $this->post_title = ucfirst($name); }
}
// id => [post type, slug, language, raw permalink as get_permalink() returns it]
$POSTS = [
    10 => ['page', 'home', 'sv', SITE . '/home/'],                 // front page (Swedish)
    11 => ['page', 'home', 'en', SITE . '/en/home/'],              // its English translation
    12 => ['page', 'page-404', 'sv', SITE . '/page-404/'],         // 404 template
    13 => ['page', 'om-oss', 'sv', SITE . '/om-oss/'],
    14 => ['page', 'about-us', 'en', SITE . '/en/about-us/'],
    15 => ['page', 'old-landing', 'en', SITE . '/en/old-landing/'],       // Redirection plugin source
    16 => ['page', 'brand-new', 'en', SITE . '/en/brand-new/'],           // new, answers 200
    17 => ['page', 'server-redirect', 'en', SITE . '/en/server-redirect/'], // new, server-level 301
    18 => ['page', 'challenged', 'en', SITE . '/en/challenged/'],         // new, bot protection answers 202
    20 => ['insights', 'ins-ok', 'en', SITE . '/en/insight/ins-ok/'],
    21 => ['insights', 'ins-moved', 'en', SITE . '/en/insight/ins-moved/'], // Redirection plugin source
    30 => ['events', 'seminar', 'en', SITE . '/event/seminar/'],          // English event: permalink lacks /en/
    31 => ['events', 'seminar', 'sv', SITE . '/event/seminar/'],
    40 => ['case', 'a-case', 'en', SITE . '/en/case/a-case/'],
    41 => ['services', 'a-service', 'en', SITE . '/en/services/a-service/'],
    42 => ['solutions', 'a-solution', 'en', SITE . '/en/solution/a-solution/'],
    43 => ['team', 'a-person', 'en', SITE . '/en/team/a-person/'],
    44 => ['job-posts', 'a-job', 'en', SITE . '/en/career/a-job/'],
];
$PROBE = [SITE . '/en/brand-new/' => 200, SITE . '/en/server-redirect/' => 301, SITE . '/en/challenged/' => 202];
class WP_Query {
    public $posts = [];
    function __construct($args) {
        foreach ($GLOBALS['POSTS'] as $id => [$type, $name]) {
            if ($type === $args['post_type']) { $this->posts[] = new WP_Post($id, $type, $name); }
        }
    }
}
class FakeWpdb {
    public $prefix = 'wp_', $last_error = '';
    function prepare($sql, ...$args) { return vsprintf(str_replace('%s', "'%s'", $sql), $args); }
    function get_var($sql) { return strpos($sql, 'redirection_items') !== false ? 'wp_redirection_items' : null; }
    function get_results($sql, $output = null) {
        return [['url' => '/en/old-landing', 'regex' => '0'], ['url' => '/en/insight/ins-moved/', 'regex' => '0'],
                ['url' => '^/en/legacy/.*', 'regex' => '1'], ['url' => '/page-404/', 'regex' => '0']];
    }
}
$wpdb = new FakeWpdb();
define('ARRAY_A', 'ARRAY_A');
function post_type_exists($type) { return true; }
function do_action(...$args) {}
function apply_filters($name, $value, ...$args) {
    global $POSTS;
    if ($name === 'wpml_post_language_details') { return ['language_code' => $POSTS[$args[0]][2]]; }
    if ($name === 'wpml_permalink') {   // WPML adds the language prefix get_permalink() can omit
        return ($args[0] === 'en' && strpos($value, '/en/') === false) ? str_replace(SITE . '/', SITE . '/en/', $value) : $value;
    }
    if ($name === 'wpml_default_language') { return 'sv'; }
    if ($name === 'wpml_object_id') { return $value === 11 ? 10 : null; }
    if ($name === 'gomo_api_allow_empty_sync') { return false; }
    return null;
}
function get_option($name, $default = false) { return $name === 'page_on_front' ? 10 : $default; }
function get_post_meta(...$args) { return ''; }
function get_permalink($post) { return $GLOBALS['POSTS'][is_object($post) ? $post->ID : $post][3]; }
function get_the_title($post) { return $post->post_title; }
function get_post_modified_time(...$args) { return '2026-11-18'; }
function get_post_time(...$args) { return '2026-11-18'; }
function get_post($id) {
    global $POSTS;
    return isset($POSTS[$id]) ? new WP_Post($id, $POSTS[$id][0], $POSTS[$id][1]) : null;
}
function get_post_status($id) { return ($id === 99 || isset($GLOBALS['POSTS'][$id])) ? 'publish' : false; }
function url_to_postid($url) { return [SITE . '/en/event/carried/' => 99, SITE . '/en/home/' => 11, SITE . '/en/page-404/' => 12][$url] ?? 0; }
function has_excerpt($p) { return false; }
function wp_trim_words($s, ...$args) { return $s; }
function wp_strip_all_tags($s) { return strip_tags($s); }
function strip_shortcodes($s) { return $s; }
function remove_accents($s) { return $s; }
function untrailingslashit($s) { return rtrim($s, '/'); }
function trailingslashit($s) { return rtrim($s, '/') . '/'; }
function home_url($path) { return SITE . $path; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function is_wp_error($thing) { return false; }
function wp_remote_head($url, $args = []) { return ['code' => $GLOBALS['PROBE'][$url] ?? 200]; }
function wp_remote_retrieve_response_code($r) { return $r['code']; }

// The published index before this sync: the language roots, a live page, a page WordPress no longer
// produces under its live URL, a deleted page and a URL that has since become a redirect.
$prev = json_decode(file_get_contents("$tmp/data/pages.json"), true);
$prev['data'] = [
    ['url' => SITE . '/', 'lang' => 'sv', 'type' => 'page', 'title' => 'Hem', 'alternate' => SITE . '/en/', 'dateModified' => '2026-11-01'],
    ['url' => SITE . '/en/', 'lang' => 'en', 'type' => 'page', 'title' => 'Home', 'alternate' => SITE . '/', 'dateModified' => '2026-11-01'],
    ['url' => SITE . '/om-oss/', 'lang' => 'sv', 'type' => 'page', 'title' => 'Om oss', 'alternate' => null, 'dateModified' => '2026-11-01'],
    ['url' => SITE . '/en/event/carried/', 'lang' => 'en', 'type' => 'event', 'title' => 'Carried', 'alternate' => null, 'dateModified' => '2026-11-01'],
    ['url' => SITE . '/en/gone/', 'lang' => 'en', 'type' => 'page', 'title' => 'Gone', 'alternate' => null, 'dateModified' => '2026-11-01'],
    ['url' => SITE . '/en/legacy/thing/', 'lang' => 'en', 'type' => 'page', 'title' => 'Legacy', 'alternate' => null, 'dateModified' => '2026-11-01'],
    // Left behind by the faulty v1.4.0 sync: both still resolve to published posts.
    ['url' => SITE . '/en/home/', 'lang' => 'en', 'type' => 'page', 'title' => 'Home', 'alternate' => null, 'dateModified' => '2026-11-01'],
    ['url' => SITE . '/en/page-404/', 'lang' => 'en', 'type' => 'page', 'title' => '404', 'alternate' => null, 'dateModified' => '2026-11-01'],
];
file_put_contents("$tmp/data/pages.json", json_encode($prev));

$fail = 0; $n = 0;
function check($label, $ok) { global $fail, $n; $n++; if (!$ok) { $fail++; } echo ($ok ? 'PASS ' : 'FAIL ') . $label . "\n"; }

$report = (new GOMO_API_Sync())->run();
check('sync publishes', strpos($report, 'Published complete snapshot:') !== false);
if (strpos($report, 'Published complete snapshot:') === false) { echo $report, "\n"; exit(1); }
$current = json_decode(file_get_contents("$tmp/snapshots/current.json"), true)['generation'];
$read = static fn($name) => json_decode(file_get_contents("$tmp/snapshots/$current/data/$name.json"), true)['data'];
$pages = $read('pages');
$urls = array_column($pages, 'url');
$has = static fn($u) => in_array(SITE . $u, $urls, true);

check('front pages are the language roots, never /home/ or /en/home/', $has('/') && $has('/en/') && !$has('/home/') && !$has('/en/home/'));
check('404 template is left out', !$has('/page-404/'));
check('Redirection-plugin sources are left out', !$has('/en/old-landing/'));
check('English event uses its /en/ URL and the Swedish one is kept', $has('/en/event/seminar/') && $has('/event/seminar/'));
check('each URL appears once', count($urls) === count(array_unique($urls)));
check('new URL answering 200 is added', $has('/en/brand-new/'));
check('new URL behind a server-level redirect is left out', !$has('/en/server-redirect/'));
check('new URL that cannot be checked is added and flagged for review', $has('/en/challenged/') && strpos($report, 'NEEDS REVIEW') !== false && strpos($report, '/en/challenged/') !== false);
check('published row WordPress no longer produces is kept while it still resolves', $has('/en/event/carried/'));
check('deleted page is removed and reported', !$has('/en/gone/') && strpos($report, '/en/gone/') !== false);
check('row that became a redirect is removed and reported', !$has('/en/legacy/thing/') && strpos($report, '/en/legacy/thing/ (redirects elsewhere)') !== false);
check('bad rows from an earlier faulty sync do not come back', !$has('/en/home/') && !$has('/en/page-404/')
    && strpos($report, '/en/home/ (front page lives at the language root)') !== false && strpos($report, '/en/page-404/ (404 page)') !== false);
check('report names every left-out URL with its reason', strpos($report, '/page-404/ (404 page)') !== false
    && strpos($report, '/en/old-landing/ (redirects elsewhere)') !== false && strpos($report, '/en/server-redirect/ (HTTP 301)') !== false);
$insights = array_column($read('insights'), 'url');
check('redirecting insight is left out of insights', in_array(SITE . '/en/insight/ins-ok/', $insights, true) && !in_array(SITE . '/en/insight/ins-moved/', $insights, true));
$html = file_get_contents("$tmp/snapshots/$current/agent-page.html");
check('agent page links neither 404 nor /en/home/', strpos($html, SITE . '/page-404/') === false && strpos($html, SITE . '/en/home/') === false);

exec('rm -rf ' . escapeshellarg($tmp));
echo "\n" . ($n - $fail) . "/$n passed\n";
exit($fail ? 1 : 0);
