<?php
/** Integration-level sync tests with WordPress content fixtures; all writes use an isolated copy. */
define('ABSPATH', '/');
define('GOMO_API_VERSION', '1.3.0');
define('GOMO_API_BASE', 'https://www.gomogroup.com/agent/v1');
$root = dirname(__DIR__, 2);
$tmp = sys_get_temp_dir() . '/gomo-snapshot-' . getmypid();
exec('cp -R ' . escapeshellarg("$root/gomo-api") . ' ' . escapeshellarg($tmp));
define('GOMO_API_DIR', $tmp . '/');
require "$root/gomo-api/includes/class-sync.php";
$wpdb = (object) ['last_error' => ''];
$mode = 'normal'; $missing = false; $allowEmpty = false;
class WP_Post {
    public $ID, $post_type, $post_name, $post_title, $post_author = 1, $post_content = 'A useful summary of the published content.';
    function __construct($type, $n) {
        $this->ID = $n; $this->post_type = $type; $this->post_name = "fixture-$type-$n";
        $this->post_title = "Fixture $type $n <script>alert(1)</script>";
    }
}
class WP_Query {
    public $posts;
    function __construct($args) {
        global $mode;
        $this->posts = $mode === 'empty' ? [] : [new WP_Post($args['post_type'], 1)];
        if ($mode === 'normal') { $this->posts[] = new WP_Post($args['post_type'], 3); }
    }
}
function post_type_exists($type) { return !$GLOBALS['missing'] || $type !== 'insights'; }
function do_action(...$args) {}
function apply_filters($name, $value, ...$args) {
    if ($name === 'gomo_api_allow_empty_sync') { return $GLOBALS['allowEmpty']; }
    if ($name === 'wpml_post_language_details') { return ['language_code' => 'en']; }
    return null;
}
function get_post_meta(...$args) { return ''; }
function get_permalink($post) { return 'https://www.gomogroup.com/en/' . $post->post_type . '/' . $post->post_name . '/'; }
function get_the_title($post) { return $post->post_title; }
function get_post_modified_time(...$args) { return '2026-10-20'; }
function get_post_time(...$args) { return '2026-10-20'; }
function has_excerpt($p) { return false; }
function wp_trim_words($s, ...$args) { return $s; }
function wp_strip_all_tags($s) { return strip_tags($s); }
function strip_shortcodes($s) { return $s; }
function remove_accents($s) { return $s; }
function get_the_author_meta(...$args) { return 'Author'; }
function untrailingslashit($s) { return rtrim($s, '/'); }
function trailingslashit($s) { return rtrim($s, '/') . '/'; }
function home_url($path) { return 'https://www.gomogroup.com' . $path; }
function url_to_postid($url) { return 0; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
$fail = 0; $n = 0;
function check($label, $ok) {
    global $fail, $n; $n++; if (!$ok) { $fail++; }
    echo ($ok ? 'PASS ' : 'FAIL ') . $label . "\n";
}
function fresh_request() {
    $property = new ReflectionProperty(GOMO_API_Snapshot::class, 'root');
    if (PHP_VERSION_ID < 80100) { $property->setAccessible(true); }
    $property->setValue(null, null);
}
function current_id() {
    return json_decode(file_get_contents(GOMO_API_DIR . 'snapshots/current.json'), true)['generation'];
}
function artifact($name) { return file_get_contents(GOMO_API_DIR . 'snapshots/' . current_id() . '/' . $name); }

$original = hash_file('sha256', "$tmp/data/insights.json");
$missing = true;
$report = (new GOMO_API_Sync())->run();
check('missing post type aborts without publication', strpos($report, 'FAILED:') === 0 && !file_exists("$tmp/snapshots/current.json") && hash_file('sha256', "$tmp/data/insights.json") === $original);
$missing = false; $mode = 'empty';
$report = (new GOMO_API_Sync())->run();
check('unexpected empty collection preserves populated feed', strpos($report, 'Refusing to empty') !== false && !file_exists("$tmp/snapshots/current.json"));
$mode = 'normal';
$report = (new GOMO_API_Sync())->run();
check('complete sync publishes a snapshot', strpos($report, 'Published complete snapshot:') !== false);
if (!file_exists("$tmp/snapshots/current.json")) { echo $report; exit(1); }
$first = current_id();
$insights = json_decode(artifact('data/insights.json'), true)['data'];
$index = json_decode(artifact('agent-index.json'), true);
$html = artifact('agent-page.html');
$markdown = artifact('llms.txt');
check('sync updates both page dates while preserving publication and crawl provenance',
    substr_count($html, '<time data-page-updated datetime=') === 2
    && substr_count($html, '>' . gmdate('Y-m-d') . '</time>') >= 2
    && strpos($html, '<dt>Page published</dt><dd><time datetime="2026-05-26">2026-05-26</time>') !== false
    && strpos($html, '<dt>Site crawled</dt><dd><time datetime="2026-10-19">2026-10-19</time>') !== false);
check('all discovery summaries agree with synced data', count($insights) === 2 && $index['insights']['total'] === 2
    && count($index['events']) === 2 && count($index['openRoles']) === 2
    && strpos($html, 'fixture-insights-3') !== false && strpos($markdown, 'fixture-insights-3') !== false);
preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);
$ld = json_decode($match[1], true);
$lists = array_filter($ld['@graph'], fn($node) => substr($node['@id'] ?? '', -9) === '#insights');
check('JSON-LD is regenerated and HTML escapes content', current($lists)['numberOfItems'] === 2
    && strpos($html, '<script>alert(1)</script>') === false && strpos($html, '&lt;script&gt;') !== false);
check('company coverage and HTML coverage refreshed', json_decode(artifact('data/company.json'), true)['data']['counts']['insights'] === 2 && strpos($html, '2 insights (2 en / 0 sv)') !== false);
check('bundled baseline remains intact for rollback', hash_file('sha256', "$tmp/data/insights.json") === $original);
foreach (['gomo_endpoint' => 'insights', 'gomo_static' => 'agent_json'] as $key => $value) {
    $env = array_merge(getenv(), ['PLUGIN' => "$tmp/gomo-api.php", 'STATE' => "$tmp/test-state",
        'QV' => json_encode([$key => $value])]);
    $proc = proc_open([PHP_BINARY, __DIR__ . '/wp-stub-harness.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
    $response = json_decode(stream_get_contents($pipes[1]), true);
    $err = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); proc_close($proc);
    check("router serves active $value snapshot", strpos($err, 'STATUS 200') !== false
        && ($key === 'gomo_endpoint' ? count($response['data'] ?? []) === 2 : ($response['insights']['total'] ?? 0) === 2));
}

// Requests already reading the baseline keep it; subsequent requests pin the active generation.
check('request pins one consistent root', GOMO_API_Snapshot::root() === GOMO_API_DIR);
fresh_request();
check('next request resolves published generation', GOMO_API_Snapshot::root() === "$tmp/snapshots/$first/");
$mode = 'reduced';
$report = (new GOMO_API_Sync())->run();
$second = current_id();
check('second publication switches every artifact and keeps rollback generation', $second !== $first && is_dir("$tmp/snapshots/$first"));
foreach (['agent-page.html', 'agent-index.json', 'llms.txt', 'data/pages.json', 'data/insights.json', 'data/events.json', 'data/careers.json'] as $file) {
    check("removed fixture disappears from $file", strpos(artifact($file), 'fixture-insights-3') === false && strpos(artifact($file), 'fixture-job-posts-3') === false);
}
check('reader pinned to previous generation remains valid', GOMO_API_Snapshot::root() === "$tmp/snapshots/$first/");
fresh_request();
$lock = fopen("$tmp/.sync.lock", 'c'); flock($lock, LOCK_EX);
check('overlapping sync rejected', strpos((new GOMO_API_Sync())->run(), 'FAILED:') === 0 && current_id() === $second);
flock($lock, LOCK_UN); fclose($lock);

// Incomplete/invalid staging never changes the pointer.
foreach ([['data/bad.json' => '{broken'], ['agent-page.html' => 'only one file']] as $files) {
    try { GOMO_API_Snapshot::publish($files); $caught = false; } catch (Throwable $e) { $caught = true; }
    check('invalid/incomplete staged generation cannot activate', $caught && current_id() === $second);
}
// Portable write failure: attempt to write a file through a directory at the destination.
mkdir("$tmp/write-target");
set_error_handler(static fn() => true);
try { GOMO_API_Snapshot::write("$tmp/write-target", 'data'); $caught = false; } catch (Throwable $e) { $caught = true; }
restore_error_handler();
check('write errors throw rather than reporting success', $caught && current_id() === $second);
// A reader repeatedly resolves the pointer while new complete generations are published.
$files = [];
foreach (glob("$tmp/snapshots/$second/data/*.json") as $path) { $files['data/' . basename($path)] = file_get_contents($path); }
foreach (['agent-page.html', 'agent-index.json', 'llms.txt', 'ai.txt', 'api-catalog.json', 'openapi.json'] as $file) {
    $files[$file] = artifact($file);
}
$reader = <<<'PHP'
$base = $argv[1];
for ($i = 0; $i < 250; $i++) {
    $p = json_decode(file_get_contents($base . '/current.json'), true);
    if (!isset($p['generation'])) { exit(1); }
    $dir = $base . '/' . $p['generation'] . '/';
    foreach (['data/insights.json', 'data/company.json', 'agent-index.json', 'api-catalog.json'] as $name) {
        if (!is_array(json_decode(file_get_contents($dir . $name), true))) { exit(2); }
    }
    foreach (['agent-page.html', 'llms.txt', 'ai.txt'] as $name) {
        if (!is_file($dir . $name) || filesize($dir . $name) === 0) { exit(3); }
    }
    usleep(1000);
}
PHP;
$proc = proc_open([PHP_BINARY, '-r', $reader, "$tmp/snapshots"], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
for ($i = 0; $i < 8; $i++) { GOMO_API_Snapshot::publish($files); }
stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
check('concurrent readers never observe partial publication', proc_close($proc) === 0 && $err === '');
fresh_request();
$mode = 'empty'; $allowEmpty = true;
$report = (new GOMO_API_Sync())->run();
check('explicitly authorized empty feeds update discovery too', strpos($report, 'Published complete snapshot:') !== false
    && json_decode(artifact('agent-index.json'), true)['openRoles'] === []
    && strpos(artifact('llms.txt'), 'fixture-job-posts-1') === false);

exec('rm -rf ' . escapeshellarg($tmp));
printf("\n%d/%d passed\n", $n - $fail, $n);
exit($fail ? 1 : 0);
