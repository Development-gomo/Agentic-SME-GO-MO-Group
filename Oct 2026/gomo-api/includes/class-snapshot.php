<?php
if (!defined('ABSPATH')) { exit; }

/** Immutable generations; one atomic pointer switch publishes every endpoint together. */
final class GOMO_API_Snapshot
{
    private static $root;

    public static function root(): string
    {
        if (self::$root !== null) { return self::$root; }
        $pointer = GOMO_API_DIR . 'snapshots/current.json';
        if (!is_file($pointer)) { return self::$root = GOMO_API_DIR; }
        $current = json_decode((string) file_get_contents($pointer), true);
        $generation = $current['generation'] ?? '';
        if (!preg_match('/^[a-f0-9]{24}$/D', $generation) || !is_dir(GOMO_API_DIR . "snapshots/$generation")) {
            throw new RuntimeException('Invalid published snapshot pointer.');
        }
        return self::$root = GOMO_API_DIR . "snapshots/$generation/";
    }

    public static function encode(array $data): string
    {
        $json = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) { throw new RuntimeException('JSON encoding failed.'); }
        return $json . "\n";
    }

    public static function write(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents, LOCK_EX) !== strlen($contents)) {
            throw new RuntimeException('Failed writing ' . basename($path));
        }
    }

    /** Caller holds the sync lock. Existing generations are retained for rollback/in-flight readers. */
    public static function publish(array $files): string
    {
        $base = GOMO_API_DIR . 'snapshots/';
        if (!is_dir($base) && !mkdir($base, 0755, true)) { throw new RuntimeException('Cannot create snapshot directory.'); }
        $id = bin2hex(random_bytes(12));
        $dir = $base . $id . '/';
        $pointerTemp = $base . $id . '.pointer';
        if (!mkdir($dir . 'data', 0755, true)) { throw new RuntimeException('Cannot stage snapshot.'); }
        try {
            foreach ($files as $name => $contents) {
                if (!preg_match('#^(data/)?[a-z-]+\.(json|html|txt)$#D', $name)) { throw new RuntimeException('Invalid snapshot filename.'); }
                if (substr($name, -5) === '.json' && !is_array(json_decode($contents, true))) {
                    throw new RuntimeException('Invalid staged JSON: ' . $name);
                }
                self::write($dir . $name, $contents);
            }
            foreach (['company', 'services', 'case-studies', 'team', 'offices', 'insights', 'events', 'careers', 'pages'] as $ep) {
                if (!isset($files["data/$ep.json"])) { throw new RuntimeException('Incomplete snapshot: ' . $ep); }
            }
            foreach (['agent-page.html', 'agent-index.json', 'llms.txt', 'ai.txt', 'api-catalog.json', 'openapi.json'] as $name) {
                if (empty($files[$name])) { throw new RuntimeException('Incomplete snapshot: ' . $name); }
            }
            $previous = is_file($base . 'current.json') ? json_decode(file_get_contents($base . 'current.json'), true)['generation'] : null;
            self::write($pointerTemp, self::encode(['generation' => $id, 'previous' => $previous, 'published' => gmdate('c')]));
            if (!rename($pointerTemp, $base . 'current.json')) { throw new RuntimeException('Cannot activate snapshot.'); }
            return $id;
        } catch (Throwable $e) {
            @unlink($pointerTemp);
            foreach (glob($dir . 'data/*') ?: [] as $path) { unlink($path); }
            foreach (glob($dir . '*') ?: [] as $path) { if (is_file($path)) { unlink($path); } }
            @rmdir($dir . 'data');
            @rmdir($dir);
            throw $e;
        }
    }
}
