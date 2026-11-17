<?php
/** Plugin router tests: php tests/php/router_test.php  (needs PHP 7.4+ CLI) */
$root   = dirname(__DIR__, 2);
$plugin = "$root/gomo-api/gomo-api.php";
$tmp    = sys_get_temp_dir() . '/gomo-router-' . getmypid();
@mkdir($tmp);
// A copy of the plugin without agent-page.html, to test the missing-file path.
$noagent = "$tmp/noagent";
exec('cp -R ' . escapeshellarg("$root/gomo-api") . ' ' . escapeshellarg($noagent));
@unlink("$noagent/agent-page.html");

$fail = 0; $n = 0;
function req(array $env): array {
    global $plugin, $tmp;
    $env += ['PLUGIN' => $plugin, 'STATE' => "$tmp/state.json"];
    $spec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $envs = array_merge(getenv(), array_map(fn($v) => is_array($v) ? json_encode($v) : (string) $v, $env));
    $p = proc_open([PHP_BINARY, __DIR__ . '/wp-stub-harness.php'], $spec, $pipes, null, $envs);
    $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]); proc_close($p);
    preg_match_all('/STATUS (\d+)/', $err, $m);
    return ['status' => (int) (end($m[1]) ?: 0), 'json' => json_decode($out, true), 'raw' => $out, 'err' => $err];
}
function check(string $name, bool $ok, string $detail = '') {
    global $fail, $n; $n++;
    if (!$ok) $fail++;
    printf("%s %s%s\n", $ok ? 'PASS' : 'FAIL', $name, $ok || $detail === '' ? '' : "  — $detail");
}
function reset_state() { global $tmp; @unlink("$tmp/state.json"); @unlink("$tmp/state.json.db"); }
$count = fn($r) => is_array($r['json']['data'] ?? null) ? count($r['json']['data']) : -1;
$data  = fn($f) => json_decode(file_get_contents("$GLOBALS[root]/gomo-api/data/$f.json"), true)['data'];

// ── Read endpoints ──
$r = req(['QV' => ['gomo_endpoint' => 'index']]);
check('index 200 with 15 endpoints', $r['status'] === 200 && count($r['json']['endpoints'] ?? []) === 15);
foreach (['company', 'services', 'case-studies', 'team', 'offices', 'insights', 'events', 'careers', 'pages'] as $ep) {
    $r = req(['QV' => ['gomo_endpoint' => $ep]]);
    check("$ep.json 200 + valid JSON", $r['status'] === 200 && is_array($r['json']), substr($r['raw'], 0, 80));
}
$sv = array_filter($data('services'), fn($s) => in_array('sv', $s['languages'], true));
$r = req(['QV' => ['gomo_endpoint' => 'services'], 'GET' => ['lang' => 'sv']]);
check('services ?lang=sv count', $count($r) === count($sv), "got {$count($r)}, want " . count($sv));
$r = req(['QV' => ['gomo_endpoint' => 'services'], 'GET' => ['category' => 'local']]);
check('services ?category=local', $count($r) > 0 && !array_filter($r['json']['data'], fn($s) => $s['category'] !== 'local'));
$r = req(['QV' => ['gomo_endpoint' => 'services', 'gomo_id' => 'geo']]);
check('services/geo', $r['status'] === 200 && ($r['json']['data']['id'] ?? '') === 'geo');
$r = req(['QV' => ['gomo_endpoint' => 'services', 'gomo_id' => 'generative-engine-optimization-geo']]);
check('services/{alias} resolves', ($r['json']['data']['id'] ?? '') === 'geo');
$r = req(['QV' => ['gomo_endpoint' => 'services', 'gomo_id' => 'does-not-exist']]);
check('unknown service 404', $r['status'] === 404);
$r = req(['QV' => ['gomo_endpoint' => 'case-studies'], 'GET' => ['service' => 'seo']]);
check('cases ?service=seo all tagged seo', $count($r) > 0 && !array_filter($r['json']['data'], fn($c) => !in_array('seo', $c['services'], true)));
$r = req(['QV' => ['gomo_endpoint' => 'insights'], 'GET' => ['lang' => 'sv', 'per_page' => '5', 'page' => '2']]);
check('insights sv paginated', $count($r) === 5 && $r['json']['pagination']['page'] === 2 && !array_filter($r['json']['data'], fn($i) => $i['lang'] !== 'sv'));
$r = req(['QV' => ['gomo_endpoint' => 'insights'], 'GET' => ['since' => '2026-01-01']]);
check('insights ?since', $count($r) > 0 && !array_filter($r['json']['data'], fn($i) => $i['datePublished'] < '2026-01-01'));
$svIns = current(array_filter($data('insights'), fn($i) => $i['lang'] === 'sv'));
$r = req(['QV' => ['gomo_endpoint' => 'insights', 'gomo_id' => $svIns['id']]]);
check('insights/sv-… by id', $r['status'] === 200 && $r['json']['data']['id'] === $svIns['id']);
$withSv = count(array_filter($data('events'), fn($e) => !empty($e['url_sv'])));
$r = req(['QV' => ['gomo_endpoint' => 'events'], 'GET' => ['lang' => 'sv']]);
check('events ?lang=sv uses url_sv', $count($r) === $withSv, "got {$count($r)}, want $withSv");
$r = req(['QV' => ['gomo_endpoint' => 'pages'], 'GET' => ['page' => '2']]);
check('pages paginate past 200', $r['json']['pagination']['pages'] === (int) ceil(count($data('pages')) / 200));
$r = req(['QV' => ['gomo_endpoint' => 'nope']]);
check('unknown endpoint 404', $r['status'] === 404);
$r = req(['QV' => ['gomo_endpoint' => 'services'], 'METHOD' => 'POST']);
check('POST to read endpoint 405', $r['status'] === 405);
$r = req(['QV' => ['gomo_endpoint' => 'services'], 'METHOD' => 'OPTIONS']);
check('OPTIONS preflight 204', $r['status'] === 204);

// ── Static files ──
foreach (['llms_txt', 'ai_txt', 'api_catalog', 'openapi', 'agent_page', 'agent_json'] as $k) {
    $r = req(['QV' => ['gomo_static' => $k]]);
    check("static $k 200", $r['status'] === 200 && strlen($r['raw']) > 1000);
}
$r = req(['QV' => ['gomo_static' => 'agent_page'], 'PLUGIN' => "$noagent/gomo-api.php"]);
check('missing static file 404 (not homepage)', $r['status'] === 404);
$r = req(['QV' => []]);
check('non-plugin request falls through to WordPress', str_contains($r['err'], 'FELL THROUGH'));

// ── Contact ──
$valid = ['agent_context' => ['user_authorized_submission' => true, 'agent_name' => 'test'],
          'person' => ['name' => 'Test Person', 'email' => 't@example.com'], 'company' => ['name' => 'Test Co'],
          'inquiry' => ['service_interest' => 'GEO', 'project_summary' => 'We want to be cited in AI answers.']];
$post = fn(array $body, array $extra = []) => req(['QV' => ['gomo_endpoint' => 'contact'], 'METHOD' => 'POST', 'BODY' => json_encode($body)] + $extra);
reset_state();
check('GET contact 405', req(['QV' => ['gomo_endpoint' => 'contact']])['status'] === 405);
check('invalid JSON 400', req(['QV' => ['gomo_endpoint' => 'contact'], 'METHOD' => 'POST', 'BODY' => 'nope'])['status'] === 400);
check('consent "true" (string) 403', $post(array_replace_recursive($valid, ['agent_context' => ['user_authorized_submission' => 'true']]))['status'] === 403);
check('consent missing 403', $post(array_diff_key($valid, ['agent_context' => 1]))['status'] === 403);
$r = $post(array_replace_recursive($valid, ['person' => ['name' => ['x']]]));
check('array where string expected 422 (no fatal)', $r['status'] === 422 && in_array('person.name', $r['json']['fields'] ?? [], true), $r['err']);
check('too many links 422', $post(array_replace_recursive($valid, ['inquiry' => ['project_summary' => 'see http://a.x http://b.x http://c.x http://d.x']]))['status'] === 422);
foreach (['person' => ['name' => 120, 'email' => 254, 'phone' => 40, 'job_title' => 120],
    'company' => ['name' => 160, 'website' => 2048, 'country' => 80, 'size' => 40],
    'inquiry' => ['service_interest' => 120, 'project_summary' => 4000, 'budget_range' => 80, 'timeline' => 80],
    'agent_context' => ['agent_name' => 80, 'conversation_summary' => 2000]] as $group => $fields) {
    foreach ($fields as $field => $max) {
        $r = $post(array_replace_recursive($valid, [$group => [$field => str_repeat('x', $max + 1)]]));
        check("oversized $group.$field rejected before mail", $r['status'] === 422 && !str_contains($r['err'], 'MAIL'));
    }
}
check('invalid language rejected', $post(array_replace_recursive($valid, ['inquiry' => ['preferred_language' => 'xx']]))['status'] === 422);
check('invalid website rejected', $post(array_replace_recursive($valid, ['company' => ['website' => 'not-a-url']]))['status'] === 422);
$r = $post($valid, ['SERVER' => ['CONTENT_LENGTH' => '32769']]);
check('oversized declared body 413', $r['status'] === 413 && !str_contains($r['err'], 'MAIL'));
$r = $post($valid + ['padding' => str_repeat('x', 33000)]);
check('oversized actual body without Content-Length 413', $r['status'] === 413 && !str_contains($r['err'], 'MAIL'));
check('database lock failure fails closed', $post($valid, ['DB_FAIL' => '1'])['status'] === 503);
$r = $post($valid, ['DB_WRITE_FAIL' => '1']);
check('database write failure sends no mail', $r['status'] === 503 && !str_contains($r['err'], 'MAIL'));
$r = $post($valid);
check('valid submission 201 + mail to ha@', $r['status'] === 201 && preg_match('/^gomo-lead-\d{4}-[A-F0-9]{8}$/', $r['json']['lead_id'] ?? '') && str_contains($r['err'], 'MAIL to=ha@gomogroup.com'));
$r2 = $post($valid);
check('duplicate within 24h returns same lead, no mail', $r2['status'] === 200 && ($r2['json']['lead_id'] ?? '') === $r['json']['lead_id'] && !str_contains($r2['err'], 'MAIL'));
reset_state();
check('mail failure 502', $post($valid, ['MAIL_FAILS' => '1'])['status'] === 502);
check('known failed delivery can retry', $post($valid)['status'] === 201);
// Per-client limit (5/hour by default)
reset_state();
for ($i = 1; $i <= 5; $i++) $post(array_replace_recursive($valid, ['person' => ['email' => "u$i@example.com"]]));
check('6th submission from one IP 429', $post(array_replace_recursive($valid, ['person' => ['email' => 'u6@example.com']]))['status'] === 429);
check('other IP still allowed', $post(array_replace_recursive($valid, ['person' => ['email' => 'u7@example.com']]), ['SERVER' => ['REMOTE_ADDR' => '198.51.100.7']])['status'] === 201);
// MCP-signed client IP is honoured only with the right secret
$def = ['GOMO_MCP_SHARED_SECRET' => 's3cret'];
check('MCP-signed agent IP gets its own bucket', $post(array_replace_recursive($valid, ['person' => ['email' => 'm1@example.com']]),
    ['DEFINES' => $def, 'SERVER' => ['HTTP_X_GOMO_MCP_KEY' => 's3cret', 'HTTP_X_GOMO_CLIENT_IP' => '192.0.2.44']])['status'] === 201);
check('wrong MCP secret falls back to server IP (limited)', $post(array_replace_recursive($valid, ['person' => ['email' => 'm2@example.com']]),
    ['DEFINES' => $def, 'SERVER' => ['HTTP_X_GOMO_MCP_KEY' => 'wrong', 'HTTP_X_GOMO_CLIENT_IP' => '192.0.2.45']])['status'] === 429);
// Global cap
reset_state();
$ok = 0;
for ($i = 1; $i <= 21; $i++) {
    $r = $post(array_replace_recursive($valid, ['person' => ['email' => "g$i@example.com"]]), ['SERVER' => ['REMOTE_ADDR' => "198.51.100.$i"]]);
    if ($r['status'] === 201) $ok++;
}
check('site-wide cap: 20 accepted, 21st 429', $ok === 20 && $r['status'] === 429, "accepted $ok");

// Run real concurrent PHP processes; slow mail exposes check-then-increment races.
function burst($valid, $size, $sameInquiry, $differentIPs = false) {
    global $tmp, $plugin;
    $workers = [];
    for ($i = 0; $i < $size; $i++) {
        $body = $valid;
        if (!$sameInquiry) { $body['person']['email'] = "burst$i@example.com"; }
        $env = array_merge(getenv(), ['PLUGIN' => $plugin, 'STATE' => "$tmp/state.json",
            'QV' => '{"gomo_endpoint":"contact"}', 'METHOD' => 'POST', 'BODY' => json_encode($body), 'MAIL_DELAY' => '150000',
            'SERVER' => json_encode(['REMOTE_ADDR' => $differentIPs ? "198.51.100.$i" : '203.0.113.10'])]);
        $proc = proc_open([PHP_BINARY, __DIR__ . '/wp-stub-harness.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
        $workers[] = [$proc, $pipes];
    }
    $statuses = []; $mails = 0;
    foreach ($workers as [$proc, $pipes]) {
        stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]); proc_close($proc);
        preg_match_all('/STATUS (\d+)/', $err, $matches);
        $statuses[] = (int) end($matches[1]);
        $mails += substr_count($err, 'MAIL to=');
    }
    return [$statuses, $mails];
}
reset_state();
[$statuses, $mails] = burst($valid, 12, false);
check('concurrent client burst sends at most five emails', $mails === 5 && count(array_filter($statuses, fn($s) => $s === 429)) === 7);
reset_state();
[$statuses, $mails] = burst($valid, 25, false, true);
check('concurrent global burst sends at most twenty emails', $mails === 20 && count(array_filter($statuses, fn($s) => $s === 429)) === 5);
reset_state();
[$statuses, $mails] = burst($valid, 8, true);
check('concurrent duplicates send one email', $mails === 1 && count(array_filter($statuses, fn($s) => $s === 201)) === 1);
// Off switch
reset_state();
file_put_contents("$tmp/state.json", json_encode(['opt' => ['gomo_api_contact_enabled' => '0']]));
check('contact disabled → 503', $post($valid)['status'] === 503);

exec('rm -rf ' . escapeshellarg($tmp));
printf("\n%d/%d passed\n", $n - $fail, $n);
exit($fail ? 1 : 0);
