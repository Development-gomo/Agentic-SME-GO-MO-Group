<?php
/**
 * Runs one request through GOMO_Agent_API::route() with minimal WordPress stand-ins.
 * Input via env: QV (query vars JSON), GET (JSON), METHOD, BODY, SERVER (extra $_SERVER JSON),
 * PLUGIN (path to gomo-api.php), STATE (file for transients/options), DEFINES (JSON of constants).
 * Output: response body on stdout; "STATUS nnn" and "MAIL …" lines on stderr.
 */
define('ABSPATH', '/'); define('HOUR_IN_SECONDS', 3600); define('DAY_IN_SECONDS', 86400);
foreach (json_decode(getenv('DEFINES') ?: '{}', true) as $k => $v) define($k, $v);
$GLOBALS['qv']   = json_decode(getenv('QV') ?: '{}', true);
$_GET            = json_decode(getenv('GET') ?: '{}', true);
$_SERVER['REQUEST_METHOD'] = getenv('METHOD') ?: 'GET';
$_SERVER['REMOTE_ADDR']    = '203.0.113.10';
$_SERVER = array_merge($_SERVER, json_decode(getenv('SERVER') ?: '{}', true));
$GLOBALS['body']  = getenv('BODY') ?: '';
$GLOBALS['state_file'] = getenv('STATE') ?: sys_get_temp_dir() . '/gomo-test-state.json';
$GLOBALS['state'] = is_file($GLOBALS['state_file']) ? json_decode(file_get_contents($GLOBALS['state_file']), true) : [];
function save_state() { file_put_contents($GLOBALS['state_file'], json_encode($GLOBALS['state'])); }
register_shutdown_function('save_state');

function add_action(...$a) {} function add_filter(...$a) {} function add_rewrite_rule(...$a) {}
function register_activation_hook(...$a) {} function register_deactivation_hook(...$a) {}
function plugin_dir_path($f) { return dirname($f) . '/'; }
function get_option($k, $d = false) { return $GLOBALS['state']['opt'][$k] ?? $d; }
function add_option($k, $v) { $GLOBALS['state']['opt'][$k] ??= $v; }
function update_option($k, $v) { $GLOBALS['state']['opt'][$k] = $v; }
function get_transient($k) { return $GLOBALS['state']['t'][$k] ?? false; }
function set_transient($k, $v, $ttl) { $GLOBALS['state']['t'][$k] = $v; }
function get_query_var($k) { return $GLOBALS['qv'][$k] ?? ''; }
function status_header($c) { fwrite(STDERR, "STATUS $c\n"); }
function sanitize_text_field($s) { if (!is_string($s)) throw new TypeError('sanitize_text_field got ' . gettype($s)); return trim(preg_replace('/[\r\n\t ]+/', ' ', strip_tags($s))); }
function sanitize_textarea_field($s) { if (!is_string($s)) throw new TypeError('sanitize_textarea_field got ' . gettype($s)); return trim(strip_tags($s)); }
function sanitize_email($s) { return filter_var($s, FILTER_SANITIZE_EMAIL); }
function is_email($s) { return (bool) filter_var($s, FILTER_VALIDATE_EMAIL); }
function sanitize_key($s) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower($s)); }
function wp_unslash($s) { return $s; }
function wp_json_encode($d, $f = 0) { return json_encode($d, $f); }
function wp_mail($to, $sub, $msg, $h) {
    if (getenv('MAIL_DELAY')) { usleep((int) getenv('MAIL_DELAY')); }
    fwrite(STDERR, "MAIL to=$to\n");
    return getenv('MAIL_FAILS') ? false : true;
}

// Emulates the database advisory lock and durable row across independent PHP workers.
// Production SQL must also be checked against the staging MySQL/MariaDB server.
class ContactTestDB {
    public $options = 'wp_options';
    public $last_error = '';
    private $lock;
    function prepare($sql, ...$args) { return [$sql, $args]; }
    function get_var($statement) {
        [$sql, $args] = $statement;
        if (strpos($sql, 'GET_LOCK') !== false) {
            if (getenv('DB_FAIL')) { return '0'; }
            $this->lock = fopen($GLOBALS['state_file'] . '.lock', 'c');
            return flock($this->lock, LOCK_EX) ? '1' : '0';
        }
        if (strpos($sql, 'RELEASE_LOCK') !== false) {
            flock($this->lock, LOCK_UN); fclose($this->lock); return '1';
        }
        $path = $GLOBALS['state_file'] . '.db';
        return is_file($path) ? file_get_contents($path) : null;
    }
    function query($statement) {
        if (getenv('DB_WRITE_FAIL')) { return false; }
        return file_put_contents($GLOBALS['state_file'] . '.db', $statement[1][1]);
    }
}
$wpdb = new ContactTestDB();

class InputStub { public $context; private $p = 0; private $d = '';
  function stream_open($path, $mode, $opt, &$opened) { $this->d = $path === 'php://input' ? $GLOBALS['body'] : ''; return true; }
  function stream_read($n) { $r = substr($this->d, $this->p, $n); $this->p += strlen($r); return $r; }
  function stream_eof() { return $this->p >= strlen($this->d); }
  function stream_stat() { return []; } }
stream_wrapper_unregister('php');
stream_wrapper_register('php', 'InputStub');

require getenv('PLUGIN');
(new GOMO_Agent_API())->route();
fwrite(STDERR, "FELL THROUGH\n");
