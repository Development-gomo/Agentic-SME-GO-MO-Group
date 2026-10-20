<?php
/** Sync merge test: bilingual WordPress posts fold into curated items without duplicates. */
define('ABSPATH', '/');
$root = dirname(__DIR__, 2);
$tmp  = sys_get_temp_dir() . '/gomo-sync-' . getmypid() . '/';
@mkdir($tmp . 'data', 0777, true);
copy("$root/gomo-api/data/events.json", $tmp . 'data/events.json');
define('GOMO_API_DIR', $tmp);
function untrailingslashit($s) { return rtrim($s, '/'); }
require "$root/gomo-api/includes/class-sync.php";

$B = 'https://www.gomogroup.com';
$existing = json_decode(file_get_contents($tmp . 'data/events.json'), true)['data'][0];
$en = $existing['url']; $sv = $existing['url_sv'] ?? null;
$fresh = [
    ['id' => 'x', 'lang' => 'en', 'title' => 'raw', 'url' => $en, 'alternate' => $sv, 'datePublished' => '2026-01-01', 'dateModified' => '2099-01-01'],
    ['id' => 'sv-x', 'lang' => 'sv', 'title' => 'raw sv', 'url' => $sv ?: "$B/event/x/", 'alternate' => $en, 'datePublished' => '2026-01-01', 'dateModified' => '2099-01-01'],
    ['id' => 'new', 'lang' => 'en', 'title' => 'New', 'url' => "$B/en/event/new/", 'alternate' => "$B/event/ny/", 'datePublished' => '2026-10-20', 'dateModified' => '2026-10-20'],
    ['id' => 'sv-ny', 'lang' => 'sv', 'title' => 'Ny', 'url' => "$B/event/ny/", 'alternate' => "$B/en/event/new/", 'datePublished' => '2026-10-20', 'dateModified' => '2026-10-20'],
    ['id' => 'sv-solo', 'lang' => 'sv', 'title' => 'Solo', 'url' => "$B/event/solo/", 'alternate' => null, 'datePublished' => '2026-10-20', 'dateModified' => '2026-10-20'],
];
$merge = Closure::bind(fn($e, $f) => $this->merge_curated($e, $f), new GOMO_API_Sync(), GOMO_API_Sync::class);
$out = $merge('events', $fresh);
$byId = array_column($out, null, 'id');
$fail = 0;
$t = function ($name, $ok) use (&$fail) { echo ($ok ? 'PASS ' : 'FAIL ') . "$name\n"; $fail += $ok ? 0 : 1; };
$t('5 posts → 3 items', count($out) === 3);
$t('existing item keeps curated title/id', isset($byId[$existing['id']]) && $byId[$existing['id']]['title'] === $existing['title']);
$t('existing item gets newer dateModified', ($byId[$existing['id']]['dateModified'] ?? '') === '2099-01-01');
$t('new pair merged with url_sv', ($byId['new']['url_sv'] ?? null) === "$B/event/ny/");
$t('sv-only item has url null', array_key_exists('sv-solo', $byId) && $byId['sv-solo']['url'] === null && $byId['sv-solo']['url_sv'] === "$B/event/solo/");
$t('no lang/alternate keys leak', !array_filter($out, fn($i) => array_key_exists('alternate', $i) || array_key_exists('lang', $i)));
exec('rm -rf ' . escapeshellarg($tmp));
exit($fail ? 1 : 0);
