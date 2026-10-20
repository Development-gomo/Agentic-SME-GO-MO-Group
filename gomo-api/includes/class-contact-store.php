<?php
if (!defined('ABSPATH')) { exit; }

/** Database-backed reservations. No transients/object cache: all workers share one authoritative row. */
final class GOMO_Contact_Store
{
    private function transaction(callable $change): array
    {
        global $wpdb;
        $key = 'gomo_contact_reservations_v1';
        $lock = 'gomo_contact_' . md5($wpdb->options);
        if ((string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 3)', $lock)) !== '1') {
            throw new RuntimeException('Contact reservation lock unavailable.');
        }
        try {
            $raw = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key));
            if ($wpdb->last_error) { throw new RuntimeException('Cannot read contact reservations.'); }
            $state = $raw === null ? [] : json_decode($raw, true);
            if (!is_array($state)) { throw new RuntimeException('Invalid contact reservation state.'); }
            $now = time();
            foreach (['clients', 'duplicates'] as $group) {
                foreach ($state[$group] ?? [] as $id => $entry) {
                    if ($entry['expires'] <= $now) { unset($state[$group][$id]); }
                }
            }
            if (($state['global']['expires'] ?? 0) <= $now) {
                $state['global'] = ['count' => 0, 'expires' => $now + HOUR_IN_SECONDS];
            }
            $result = $change($state, $now);
            $encoded = wp_json_encode($state);
            if ($encoded === false || $wpdb->query($wpdb->prepare(
                "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no') ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
                $key, $encoded
            )) === false) {
                throw new RuntimeException('Cannot persist contact reservations.');
            }
            return $result;
        } finally {
            $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
        }
    }

    public function reserve(string $ip, string $duplicate, string $lead): array
    {
        return $this->transaction(static function (&$state, $now) use ($ip, $duplicate, $lead) {
            if (isset($state['duplicates'][$duplicate])) {
                return $state['duplicates'][$duplicate];
            }
            $client = $state['clients'][$ip] ?? ['count' => 0, 'expires' => $now + HOUR_IN_SECONDS];
            if ($client['count'] >= GOMO_CONTACT_PER_IP_HOUR || $state['global']['count'] >= GOMO_CONTACT_GLOBAL_HOUR) {
                return ['status' => 'limited'];
            }
            $client['count']++;
            $state['clients'][$ip] = $client;
            $state['global']['count']++;
            $state['duplicates'][$duplicate] = ['status' => 'pending', 'lead_id' => $lead, 'expires' => $now + DAY_IN_SECONDS];
            return ['status' => 'reserved', 'lead_id' => $lead];
        });
    }

    public function complete(string $duplicate, string $lead, bool $sent): void
    {
        $this->transaction(static function (&$state) use ($duplicate, $lead, $sent) {
            if (($state['duplicates'][$duplicate]['lead_id'] ?? '') !== $lead) {
                throw new RuntimeException('Contact reservation was lost.');
            }
            if ($sent) {
                $state['duplicates'][$duplicate]['status'] = 'sent';
            } else {
                unset($state['duplicates'][$duplicate]);
            }
            // Failed deliveries still consume attempt quota, preventing unlimited mail retries.
            return [];
        });
    }
}
