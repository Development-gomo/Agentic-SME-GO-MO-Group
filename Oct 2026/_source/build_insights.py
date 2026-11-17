"""Build curated/insights.json from pages.json + raw HTML (H1, reading time). Topic rules mirror class-sync.php."""
import json, re, sys, html, unicodedata
S = sys.argv[1]
TOPICS = {
 'geo': ['geo','generative-engine','ai-search','llms-txt','llm','ai-overview','aeo','answer-engine','chatgpt','perplexity','ai-traffic','synlig-i-ai','ai-sok','agentic','agent','agentiska','ai-agenter'],
 'ai': ['ai','generative','generativ','gpt','copilot','gemini'],
 'seo': ['seo','sokmotor','google','migration','bounce','links','lankar','voice','rost','mum','ux'],
 'paid': ['sea','ads','annons','programmatic','ppc','cookieless','cookie'],
 'b2b-marketing': ['b2b','pull','push','demand','asbx','lead','sales','forsaljning','marketing','marknadsforing','people-based','abm'],
 'content': ['content','innehall','thought-leadership','tankeledarskap','youtube','video'],
 'leadership': ['cmo','cmos','leadership','ledarskap','interview','intervju','mindset'],
 'company': ['go-mo','gomo','promoted','partnership','india','indien','client-survey','kundundersokning','gender'],
 'careers': ['job','jobb','career','karriar','trainee','associate','engineering'],
}
def norm(s): return unicodedata.normalize('NFKD', s.lower()).encode('ascii','ignore').decode()
def topics(slug, title):
    hay = '-' + re.sub(r'[^a-z0-9]+', '-', norm(slug + ' ' + title)) + '-'
    out = [t for t, ws in TOPICS.items() if any(f'-{w}-' in hay for w in ws)]
    return out or ['b2b-marketing']
pages = json.load(open('pages.json'))
out = []
for p in pages:
    if p['type'] != 'insight': continue
    k = p['url'].replace('https://www.gomogroup.com/', '').strip('/').replace('/', '__')
    t = open(f'{S}/html/{k}.html', encoding='utf-8', errors='replace').read()
    h1 = re.search(r'<h1[^>]*>(.*?)</h1>', t, re.S)
    title = html.unescape(re.sub(r'<[^>]+>', '', h1.group(1))).strip() if h1 else p['title']
    rt = re.search(r'twitter:data1" content="(\d+) min', t)
    out.append({
        'id': ('sv-' if p['lang'] == 'sv' else '') + p['slug'],
        'lang': p['lang'], 'title': re.sub(r'\s+', ' ', title), 'seo_title': p['title'],
        'summary': p['description'], 'url': p['url'],
        'alternate': p['alternate']['url'] if p['alternate'] else None,
        'datePublished': p['published'], 'dateModified': p['modified'],
        'readingMinutes': int(rt.group(1)) if rt else None,
        'topics': topics(p['slug'], title),
    })
# make alternates symmetric
by = {o['url']: o for o in out}
for o in out:
    if o['alternate'] and o['alternate'] in by and not by[o['alternate']]['alternate']:
        by[o['alternate']]['alternate'] = o['url']
out.sort(key=lambda o: o['datePublished'] or '', reverse=True)
json.dump(out, open('curated/insights.json', 'w'), ensure_ascii=False, indent=2)
from collections import Counter
print(len(out), Counter(o['lang'] for o in out), Counter(t for o in out for t in o['topics']))
print(sum(1 for o in out if o['alternate']), 'paired;', [o['title'] for o in out[:5]])
