<?php
if (!defined('ABSPATH')) { exit; }

/** Refresh derived content while preserving curated company facts, pricing and presentation. */
final class GOMO_API_Discovery
{
    private static function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function link($url, $title): string
    {
        if (!$url || !preg_match('#^https?://#i', $url)) { return self::e($title); }
        return '<a href="' . self::e($url) . '">' . self::e($title) . '</a>';
    }

    private static function markdown($title, $url): string
    {
        $label = str_replace(["\r", "\n", '\\', '[', ']', '<', '>'], [' ', ' ', '\\\\', '\\[', '\\]', '&lt;', '&gt;'], (string) $title);
        if (!$url || !preg_match('#^https?://#i', $url)) { return $label; }
        return '[' . $label . '](' . str_replace([' ', '(', ')', "\r", "\n"], ['%20', '%28', '%29', '', ''], $url) . ')';
    }

    private static function section(string $id, array $items): string
    {
        $labels = ['insights' => 'Insights', 'careers' => 'Careers', 'events' => 'Events', 'page-index' => 'Page index'];
        $out = '<section id="' . $id . '"><h2>' . $labels[$id] . ' (' . count($items) . ')</h2>';
        if (!$items) { $out .= '<p>No entries currently published.</p>'; }
        if ($id === 'page-index') {
            $out .= '<ul>';
            foreach ($items as $item) {
                $out .= '<li>' . self::e($item['lang']) . ' · ' . self::e($item['type']) . ' · ' . self::link($item['url'], $item['title']);
                if (!empty($item['alternate'])) { $out .= ' · ' . self::link($item['alternate'], 'Alternate language'); }
                if (!empty($item['robots'])) { $out .= ' <span class="tag">' . self::e($item['robots']) . '</span>'; }
                $out .= '</li>';
            }
            return $out . '</ul></section>';
        }
        $prefix = ['insights' => 'post-', 'careers' => 'job-', 'events' => 'event-'][$id];
        if ($id === 'insights') {
            $groups = ['en' => [], 'sv' => []];
            foreach ($items as $item) { $groups[$item['lang']][] = $item; }
            // Preserve the language anchors used by existing readers.
            $items = [];
            foreach ($groups as $language => $group) {
                $items[] = ['language_heading' => $language];
                $items = array_merge($items, $group);
            }
        }
        $languageOpen = false;
        foreach ($items as $item) {
            if (isset($item['language_heading'])) {
                if ($languageOpen) { $out .= '</section>'; }
                $languageOpen = true;
                $language = $item['language_heading'];
                $out .= '<section id="insights-' . self::e($language) . '"><h3>' . ($language === 'en' ? 'English' : 'Swedish / Svenska') . '</h3>';
                continue;
            }
            $out .= '<article id="' . self::e($prefix . $item['id']) . '"'
                . (isset($item['lang']) ? ' lang="' . self::e($item['lang']) . '"' : '') . '><h3>'
                . self::link($item['url'] ?? $item['url_sv'] ?? null, $item['title']) . '</h3><dl>';
            foreach (['summary' => 'Summary', 'description' => 'About', 'datePublished' => 'Published', 'dateModified' => 'Updated',
                'startDate' => 'When', 'status' => 'Status', 'location' => 'Location', 'employmentType' => 'Employment type',
                'format' => 'Format', 'language' => 'Language', 'author' => 'Author', 'readingMinutes' => 'Reading time (minutes)'] as $field => $label) {
                if (!empty($item[$field])) {
                    $out .= '<dt>' . $label . '</dt><dd>' . self::e($item[$field]) . '</dd>';
                }
            }
            if (!empty($item['topics'])) { $out .= '<dt>Topics</dt><dd>' . self::e(implode(', ', $item['topics'])) . '</dd>'; }
            foreach (['url' => 'Page', 'url_sv' => 'Swedish page', 'alternate' => 'Alternate language', 'apply_url' => 'Apply', 'registration_url' => 'Register'] as $field => $label) {
                if (!empty($item[$field])) { $out .= '<dt>' . $label . '</dt><dd>' . self::link($item[$field], $item[$field]) . '</dd>'; }
            }
            $out .= '</dl></article>';
        }
        if ($languageOpen) { $out .= '</section>'; }
        return $out . '</section>';
    }

    public static function render(array $files): array
    {
        $read = static function ($name) use ($files) {
            $value = json_decode($files[$name] ?? '', true);
            if (!is_array($value)) { throw new RuntimeException('Missing/invalid discovery input: ' . $name); }
            return $value;
        };
        $insights = $read('data/insights.json')['data'];
        $events = $read('data/events.json')['data'];
        $careers = $read('data/careers.json')['data'];
        $pages = $read('data/pages.json')['data'];
        $en = count(array_filter($insights, static fn($i) => $i['lang'] === 'en'));
        $sv = count(array_filter($insights, static fn($i) => $i['lang'] === 'sv'));
        $date = gmdate('c');
        $company = $read('data/company.json');
        $company['data']['counts'] = array_merge($company['data']['counts'] ?? [], [
            'insights' => count($insights), 'insights_en' => $en, 'insights_sv' => $sv, 'public_urls' => count($pages),
        ]);
        $company['_meta']['generated'] = $date;
        $files['data/company.json'] = GOMO_API_Snapshot::encode($company);

        $index = $read('agent-index.json');
        $index['generated'] = $date;
        $index['note'] = 'Curated company facts with WordPress-synced insights, events, careers and page index. Quote figures exactly; keep anonymised clients anonymised.';
        $index['eventsNote'] = 'Synced from WordPress at ' . $date;
        $index['events'] = array_map(static fn($e) => ['name' => $e['title'], 'date' => substr($e['startDate'] ?? '', 0, 10),
            'location' => $e['location'] ?? null, 'status' => $e['status'] ?? null, 'url' => $e['url'] ?? null, 'url_sv' => $e['url_sv'] ?? null], $events);
        $index['openRoles'] = array_map(static fn($j) => ['title' => $j['title'], 'location' => $j['location'] ?? null,
            'url' => $j['url'] ?? null, 'url_sv' => $j['url_sv'] ?? null, 'apply' => $j['apply_url'] ?? null], $careers);
        $index['insights'] = ['total' => count($insights), 'en' => $en, 'sv' => $sv,
            'latest' => array_map(static fn($i) => ['title' => $i['title'], 'lang' => $i['lang'], 'date' => $i['datePublished'], 'url' => $i['url']], array_slice($insights, 0, 10)),
            'all' => GOMO_API_BASE . '/insights.json'];
        foreach ($index['faq'] ?? [] as $i => $faq) {
            if ($faq['q'] === 'Does GO MO Group publish in Swedish?') {
                $index['faq'][$i]['a'] = "Yes. The site is bilingual: {$en} insights in English and {$sv} in Swedish.";
            }
        }
        $files['agent-index.json'] = GOMO_API_Snapshot::encode($index);
        $catalog = $read('api-catalog.json');
        $catalog['modified'] = gmdate('Y-m-d');
        $files['api-catalog.json'] = GOMO_API_Snapshot::encode($catalog);

        $html = $files['agent-page.html'];
        foreach ($read('data/services.json')['data'] as $service) {
            if (empty($service['dateModified'])) { continue; }
            $html = preg_replace_callback('#<section id="service-' . preg_quote($service['id'], '#') . '">.*?</section>#s',
                static function ($match) use ($service) {
                    return preg_replace_callback('#(<dt>Updated</dt><dd>).*?</dd>#s',
                        static fn($m) => $m[1] . '<time datetime="' . self::e($service['dateModified']) . '">'
                            . self::e($service['dateModified']) . '</time></dd>', $match[0]);
                }, $html);
        }
        foreach (['insights' => $insights, 'events' => $events, 'careers' => $careers, 'page-index' => $pages] as $id => $items) {
            $start = "<!-- gomo-sync:$id:start -->";
            $end = "<!-- gomo-sync:$id:end -->";
            if (substr_count($html, $start) !== 1 || substr_count($html, $end) !== 1) {
                throw new RuntimeException('Missing discovery template boundary: ' . $id);
            }
            $html = preg_replace_callback('#' . preg_quote($start, '#') . '.*?' . preg_quote($end, '#') . '#s',
                static fn() => $start . self::section($id, $items) . $end, $html);
        }
        $html = preg_replace_callback('#<script type="application/ld\+json">(.*?)</script>#s', static function ($m) use ($insights, $date) {
            $ld = json_decode($m[1], true);
            if (!is_array($ld)) { throw new RuntimeException('Invalid JSON-LD template.'); }
            foreach ($ld['@graph'] as &$node) {
                if (($node['@type'] ?? '') === 'WebPage') { $node['dateModified'] = $date; }
                if (substr($node['@id'] ?? '', -9) === '#insights') {
                    $node['numberOfItems'] = count($insights);
                    $node['itemListElement'] = [];
                    foreach ($insights as $i => $item) {
                        $node['itemListElement'][] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $item['url'], 'name' => $item['title']];
                    }
                }
            }
            return '<script type="application/ld+json">' . str_replace('<', '\\u003c', GOMO_API_Snapshot::encode($ld)) . '</script>';
        }, $html);
        $coverage = count($pages) . ' public URLs · ' . count($read('data/services.json')['data']) . ' services · '
            . count($read('data/case-studies.json')['data']) . ' case studies · ' . count($insights) . " insights ($en en / $sv sv) · "
            . count($read('data/team.json')['data']) . ' team profiles';
        $html = preg_replace('#(<dt>Coverage</dt><dd>).*?</dd>#s', '${1}' . $coverage . '</dd>', $html);
        $html = preg_replace('#\d+ URLs \(en \+ sv\)#', count($pages) . ' URLs (en + sv)', $html);
        $html = preg_replace('#<dt>Last synced</dt><dd>.*?</dd>#s', '', $html);
        $html = str_replace('<dt>Coverage</dt>', '<dt>Last synced</dt><dd>' . $date . '</dd><dt>Coverage</dt>', $html);
        $html = preg_replace('#· generated \d{4}-\d{2}-\d{2}(?:T[^ <]+)?#', '· generated ' . $date, $html);
        $html = preg_replace('#<meta name="last-synced"[^>]*>\s*#', '', $html);
        $html = str_replace('</head>', '<meta name="last-synced" content="' . $date . '">' . "\n</head>", $html);
        $files['agent-page.html'] = $html;

        // Rebuild the contiguous derived Markdown sections, including initially empty collections.
        $markdown = $files['llms.txt'];
        $markdown = preg_replace('/^## (?:Events|Open Roles|Insights — English[^\n]*|Insikter — Swedish[^\n]*)\n.*?(?=^## |\z)/ms', '', $markdown);
        $dynamic = '';
        foreach (['Events' => $events, 'Open Roles' => $careers, "Insights — English ($en articles)" => array_filter($insights, static fn($i) => $i['lang'] === 'en'),
            "Insikter — Swedish ($sv articles)" => array_filter($insights, static fn($i) => $i['lang'] === 'sv')] as $label => $items) {
            $dynamic .= "## $label\n\n";
            foreach ($items as $item) {
                $details = $label === 'Events' ? array_filter([$item['startDate'] ?? null, $item['status'] ?? null, $item['location'] ?? null])
                    : ($label === 'Open Roles' ? array_filter([$item['location'] ?? null]) : array_filter([$item['datePublished'] ?? null]));
                $dynamic .= '- ' . self::markdown($item['title'], $item['url'] ?? $item['url_sv'] ?? null)
                    . ($details ? ' (' . self::markdown(implode(', ', $details), null) . ')' : '') . "\n";
            }
            if (!$items) { $dynamic .= "No entries currently published.\n"; }
            $dynamic .= "\n";
        }
        $markdown = preg_replace('/^- Content verified against a full crawl.*$/m', '- Derived content synced from WordPress at ' . $date . '; current page index: ' . count($pages) . ' URLs. Curated company facts retain their original crawl provenance.', $markdown);
        $files['llms.txt'] = rtrim($markdown) . "\n\n" . $dynamic;
        $ai = preg_replace('/^# Last synced:.*\n/m', '', $files['ai.txt']);
        $ai = preg_replace('/^# Last updated:.*$/m', '# Last updated: ' . $date, $ai);
        $files['ai.txt'] = $ai . "\n# Last synced: $date (" . count($pages) . " current public URLs)\n";
        return $files;
    }
}
