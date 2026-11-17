# Changelog: GO MO Group Agentic Web Package

## v1.4.0 (agentic-web-2026-11)

Built from a Firecrawl crawl of www.gomogroup.com on **2026-11-17**.

**Coverage.** The crawl covered every URL in the live sitemaps, the Firecrawl site map and the v1.3.0 page index: 345 URLs in total. It was then cross-checked against:
- every link in the raw HTML of every crawled page (menus and footers included), giving 444 unique internal URLs; the 106 not in the first crawl were all scraped;
- every published page in the WordPress REST API (51 pages in both languages).

Each URL is either in the index, redirects to a URL in the index, returns 404, or is marked `noindex`.

**Change detection.** Every page was compared with v1.3.0 on modification date, title, meta description and page-specific text, after removing site-wide widgets.

Compared with v1.3.0 (crawled 2026-10-19), the page index grows **from 314 to 327 URLs (133 English, 194 Swedish)**. That is 14 pages added and 1 removed. All other canonical URLs are unchanged, so existing integrations keep working.

### Pages added (14)

| URL | Language | Type | Published |
|---|---|---|---|
| https://www.gomogroup.com/en/services/chatgpt-ads/ | en | service | 2026-10-27 |
| https://www.gomogroup.com/vara-tjanster/chatgpt-ads/ | sv | service | 2026-10-27 |
| https://www.gomogroup.com/en/case/translation-agency-agentic-web-layer/ | en | case | 2026-11-03 |
| https://www.gomogroup.com/case/forsta-svenska-b2b-webbplatsen-med-agentiskt-lager/ | sv | case (translation of `first-swedish-b2b-website-full-agentic-layer`) | 2026-11-05 |
| https://www.gomogroup.com/case/medtech-webbinarium-b2b-eventmarknadsforing/ | sv | case (translation of `medtech-webinar-b2b-event-marketing`) | 2026-10-23 |
| https://www.gomogroup.com/en/insight/how-to-win-organically-on-linkedin/ | en | insight | 2026-10-20 |
| https://www.gomogroup.com/insikter/linkedin-algoritmen-2026-sa-nar-ni-ut-organiskt-enligt-linkedin-sjalva/ | sv | insight | 2026-10-21 |
| https://www.gomogroup.com/insikter/ai-first-marknadsforing-de-nya-reglerna-for-beslutsfattande-inom-b2b-2026/ | sv | insight (translation) | 2026-05-07 |
| https://www.gomogroup.com/insikter/chatgpt-ads-vs-google-ads-tidiga-b2b-resultat-go-mo-group/ | sv | insight (translation) | 2026-10-19 |
| https://www.gomogroup.com/insikter/er-webbplats-far-ai-trafik-ni-kan-bara-inte-se-den-an/ | sv | insight (translation) | 2026-06-16 |
| https://www.gomogroup.com/insikter/era-kopare-fragar-ai-finns-ert-varumarke-med-i-svaret/ | sv | insight (translation) | 2026-11-05 |
| https://www.gomogroup.com/insikter/google-guide-ai-sokoptimering/ | sv | insight (translation) | 2026-10-14 |
| https://www.gomogroup.com/insikter/konverterar-besokare-fran-chatgpt-och-andra-ai-assistenter-battre/ | sv | insight (translation) | 2026-10-15 |
| https://www.gomogroup.com/insikter/vi-testade-llms-txt-har-ar-vad-datan-visar/ | sv | insight (translation) | 2026-05-06 |

Seven new URLs were found and left out because the site marks them `noindex`:
- `/en/category/uncategorized/` and `/vara-tjanster/google-ads-stockholm/`.
- The new team profiles `/en/team/oliver-wilkenson/`, `/medarbetare/oliver-wilkenson/` and `/medarbetare/ville-olsson/`. Both people are still listed in the team data.
- `/boka-analys/`, a booking page published on 2026-11-16 with a Google Calendar canonical. It was found through the WordPress REST API and is not in the sitemap.
- `/category/pressmeddelande/`.

`/en/about-us-new/` (a noindex draft) was edited on 2026-11-12 and is still excluded.

### Pages removed (1)

| URL | Status | Note |
|---|---|---|
| https://www.gomogroup.com/case/generativ-ai-fran-hype-till-faktisk-affarsnytta/ | 404 | The English case `/en/case/generative-ai-from-hype-to-real-business-value/` is still live. It is kept, now without a Swedish alternate. |

None of the v1.3.0 URLs now redirect. Known redirecting URLs stay out of the index.

### Pages updated

**Body content rewritten or substantially changed (13)**

| URL | What changed |
|---|---|
| https://www.gomogroup.com/en/services/generative-engine-optimization-geo/ | Full rewrite. New free Mini GEO Audit (Executive AI Score, platform coverage, GEO and agent readiness). Four fixed-price tiers. New title and description. |
| https://www.gomogroup.com/vara-tjanster/generative-engine-optimization-geo/ | Full rewrite. The Swedish price now matches English: 23 500 kr and 44 000 kr (was 27 500 kr). New title and description. |
| https://www.gomogroup.com/en/services/agentic-web/ | Rewritten around three-way measurement (Agentic Browsing, five AI platforms, live MCP action). The MCP server is hosted separately. |
| https://www.gomogroup.com/vara-tjanster/agentic-web/ | Rewritten as above. New title and description. |
| https://www.gomogroup.com/en/case/seo-automation-gen-ai-b2b-ecommerce/ | New results: 32× organic clicks (250 → ~8,100 a month), 43× impressions, average position 15 → 7, ~12,000 pages. Replaces the "+195% / 5,000+ product pages" figures. New title and description. |
| https://www.gomogroup.com/case/seo-automation-gen-ai-b2b-e-handel/ | Swedish version of the above. |
| https://www.gomogroup.com/en/case/how-segmented-b2b-outreach-generated-15-sales-qualified-leads/ | Expanded with reply-rate benchmarks and an FAQ. New title and description. |
| https://www.gomogroup.com/case/sa-gav-en-segmenterad-b2b-kampanj-15-kvalificerade-leads/ | Swedish version of the above. |
| https://www.gomogroup.com/en/insight/the-ethical-frontier-shaping-the-future-with-generative-ai/ | Rewritten as "AI and Ethics: Shaping the Future of Business", updated 2026-11-09, EU AI Act section, Article markup. |
| https://www.gomogroup.com/insikter/the-ethical-frontier-generative-ai/ | Swedish rewrite: "AI och etik: att forma framtidens affärer". |
| https://www.gomogroup.com/case/vastsvenska-handelskammaren-fran-spretig-textproduktion-till-ai-styrd-precision/ | Rewritten. New title "Egen GPT hos Västsvenska Handelskammaren" and new description. |
| https://www.gomogroup.com/en/insight/chatgpt-ads-vs-google-ads-what-weve-learned-so-far/ | Results table updated (modified 2026-10-21). Now paired with its Swedish translation. |
| https://www.gomogroup.com/en/case/first-swedish-b2b-website-full-agentic-layer/ | Demo updated (modified 2026-10-27). Now paired with its Swedish translation. |

**Team listings changed (4)**

| URL | What changed |
|---|---|
| https://www.gomogroup.com/en/about-us/ | Joshua Amirose, Sanghapriya Waghmare and Aditi Rawal removed. Oliver Wilkenson (Digital Creator) added. Now paired with `/om-oss/` by hreflang. |
| https://www.gomogroup.com/om-oss/ | Sanghapriya Waghmare and Aditi Rawal removed. Oliver Wilkenson and Ville Olsson (Copywriter) added. |
| https://www.gomogroup.com/en/contact-us/ | Joshua Amirose removed. Oliver Wilkenson added. |
| https://www.gomogroup.com/kontakta-oss/ | Oliver Wilkenson and Ville Olsson added. |

**Archive pages listing the new posts and cases (3)**

- https://www.gomogroup.com/case/
- https://www.gomogroup.com/insikter/
- https://www.gomogroup.com/en/insights/

**Content now captured in full (5).** The 2026-10-19 crawl captured only part of these pages. Their edit dates are unchanged.

- https://www.gomogroup.com/insikter/fyra-skal-varfor-ditt-seo-arbete-inte-kan-vanta-langre/
- https://www.gomogroup.com/insikter/satsa-pa-seo-intervju/
- https://www.gomogroup.com/insikter/valkommen-till-den-agentiska-eran-nar-din-nasta-kund-kan-vara-en-ai/
- https://www.gomogroup.com/en/insight/website-security-and-rankings/
- https://www.gomogroup.com/en/insight/b2b-digital-marketing-workshop/

**Title, description or date only (10)**

| URL | What changed |
|---|---|
| https://www.gomogroup.com/en/services/demand-generation/ | Own meta title and description. It previously reused the B2B Lead Generation meta. |
| https://www.gomogroup.com/vara-tjanster/b2b-youtube-marketing/ | New title and description |
| https://www.gomogroup.com/case/digital-marknadsforing-forsakringsbolag/ | New title "Digital tillväxt för försäkringsbranschen" |
| https://www.gomogroup.com/en/insight/from-ai-hype-to-real-implementation-inside-modern-organisations/ | Shorter title. Modified 2026-11-02. |
| https://www.gomogroup.com/en/insight/ai-first-marketing-the-new-rules-of-b2b-decision-making-in-2026/ | Reading time added. Modified 2026-11-08. Now paired with its Swedish translation. |
| https://www.gomogroup.com/en/career/jr-recruitment-specialist/ | Meta description added |
| https://www.gomogroup.com/en/career/sr-paid-marketing-analyst/ | Meta description added |
| https://www.gomogroup.com/medarbetare/lina-nygren/ | "Meet Lina" heading removed |
| https://www.gomogroup.com/en/event/b2b-geo-seminar-june-2026/ | Modified 2026-10-20 |
| https://www.gomogroup.com/event/b2b-geo-seminar-june-2026/ | Modified 2026-10-20 |

**Language pairs changed in the page index.** The site now outputs WPML hreflang tags on 324 pages, and the index follows them.

- Now paired with a Swedish translation:
  - `/en/insight/do-visitors-from-chatgpt-and-other-ai-assistants-convert-better/`
  - `/en/insight/google-ai-search-optimisation-guide/`
  - `/en/insight/we-tested-llms-txt-so-you-dont-have-to-heres-what-the-data-actually-says/`
  - `/en/insight/your-buyers-are-asking-ai-is-your-brand-in-the-answer/`
  - `/en/insight/your-website-is-getting-ai-traffic-you-just-cant-see-it-yet/`
  - `/en/case/medtech-webinar-b2b-event-marketing/`
  - Plus the pages listed in the tables above.
- No longer paired: `/en/case/generative-ai-from-hype-to-real-business-value/`, because its Swedish page was removed.

**Author boxes added to 118 insights.** The site now shows an "About the author" box on 118 of 174 insights; the 2026-10-19 crawl found none. The package records each author and title:

| Author | Title shown | Articles |
|---|---|---|
| Gabriel Ghavami | CEO / Founder & CEO | 70 |
| Henrik Anderberg | Executive Vice President & Vice vd | 18 |
| Krish Mayani Dadlani | Digital Marketing Specialist & GEO Product Lead | 4 |
| Shweta Dabholkar | Sr. Content Writer | 4 |
| Ida E. Ghavami | Sr. Project Manager | 4 |
| Reza Ghazizadeh | Client Success Manager | 4 |
| Maurvi Narvekar | Content Writer | 4 |
| Avichal Singh | HR Manager | 3 |
| Sam Toosi | Client Success Manager | 2 |
| Ellen Håkman | Client Success Manager Trainee | 2 |
| Lina Nygren | VP of SME Strategy & Success | 2 |
| Aditi Rawal | Sr. Analyst | 1 |

**Every other indexed page (≈270)** changed only in site-wide elements: the rotating related-posts widget, and a "Read more about ChatGPT Ads" link on service pages. Their crawl text in `_source/` is refreshed. Their package entries are unchanged apart from refreshed dates. Site-suffix cleanup ("| GO MO Group") was applied to page-index titles.

### Data changes

- **Services: 25 (was 24).**
  - New `chatgpt-ads` service with published pilot pricing: SEK 50,000 media + SEK 20,000 setup and management = SEK 70,000 for 30 days.
  - `geo` has new pricing tiers and facts. The 27,500 SEK Swedish discrepancy is removed.
  - `agentic-web` facts are refreshed and it is linked to its two agentic cases.
- **Case studies: 31 (was 30).**
  - New `translation-agency-agentic-web-layer`.
  - `seo-automation-gen-ai-b2b-ecommerce` has new results.
  - Swedish URLs added for `first-swedish-b2b-website-full-agentic-layer` and `medtech-webinar-b2b-event-marketing`.
  - Swedish URL removed from `generative-ai-from-hype-to-real-business-value`.
- **Insights: 174 (57 en, 117 sv; was 165).** New `author` and `authorRole` fields on 118 articles, taken from the visible author box (or the Person markup where there is no box). Shown on the agent page and documented in the OpenAPI `Insight` schema.
- **Team: 34 (was 35).** Joshua Amirose, Sanghapriya Waghmare and Aditi Rawal removed. Oliver Wilkenson and Ville Olsson added.
- **FAQ:** the Mini GEO Audit answer is updated, and four ChatGPT Ads FAQs are added.
- Contact details, offices, careers (3 roles) and events (3, all past) are unchanged.
- **Company stats:** the "195% more organic clicks in 90 days … 400+ hours saved" stat is replaced with the SEO automation case's current figure (32× organic clicks, 250 → ~8,100 a month).
- **Solutions:** the SaaS/Tech solution now names the Digiexam interviewee correctly as Niklas Borgström, not Niklas Vernersson.

### Fixes from the build review
- **Free tiers were shown as "custom".** A tier priced at 0 (the free Mini GEO Audit and a free channel audit) was treated as "no price". It showed as "custom" in `agent-index.json` and was left out of `llms.txt` and the JSON-LD offers. October had the same bug. Free tiers now render as "free", with `price: 0` in JSON-LD.
- **Fixed prices were shown as "from".** The GEO and ChatGPT Ads pages state fixed prices, but every tier rendered as "from 23,500 SEK" and similar. Services now carry `pricing.price_type: "fixed"`, which prints exact prices and uses `Offer.price` instead of `minPrice`. The OpenAPI `Pricing` schema documents `price_type` and `vat`.
- **The crawl date followed the build date.** `ai.txt` ("Site crawled") and the `llms.txt` verification line used the build date, so a later rebuild without a new crawl would have misstated the crawl date. Both now use `SITE_CRAWLED`.
- Regression checks for all three fixes are added to `tests/test_outputs.py`.

### Package changes

- **New `ard.json`:** an Agentic Resource Discovery v0.91 manifest served at `/.well-known/ard.json`.
  - It has two entries: the MCP server (inline server card, 12 tools) and the REST API (OpenAPI).
  - Each entry has domain-anchored `urn:air:gomogroup.com:…` identifiers and 4–5 representative queries.
  - It validates against the official ARD entry schema with no conformance warnings.
- **The plugin serves and advertises `ard.json`:**
  - A rewrite rule for `/.well-known/ard.json`.
  - `<link rel="ard">` on every page and an HTTP `Link: <…>; rel="ard"` header.
  - The file is included in snapshot publication and sync.
- `robots-addition.txt` adds the `Agentmap:` directive. `ai.txt`, `llms.txt`, `api-catalog.json`, `agent-index.json` and the agent page metadata list the manifest.
- Version 1.4.0 across the plugin, MCP server, OpenAPI, data files and discovery files. Site crawled 2026-11-17.
- **"Sync now" keeps page-level article fields.** It no longer overwrites the published author, author title and reading time with WordPress `post_author` values; these come from the page template, not the post author.
- `_source/build_dataset.py` now builds `pages.json` from a Firecrawl crawl record file, taking language pairs from hreflang. `_source/build_insights.py` reads the visible author box.
- `_source/live-v1.3-snapshot/` holds the discovery files that were live before this release. It replaces the v1.2 snapshot.
- Tests:
  - New ARD checks in `tests/test_outputs.py`.
  - `ard` added to the plugin static-route tests.
  - `ard.json` added to snapshot publication tests.
- `PRODUCTION-REVIEW.md` from v1.3.0 is not carried over. Its code findings were resolved in v1.3.0, and the open items are folded into `DEPLOYMENT.md` and `SITE-FINDINGS.md`.

## v1.3.0 (Oct 2026)

Crawled 2026-10-19: 314 URLs. Swedish coverage, insights/events/careers/pages endpoints, rewritten sync, snapshot publication and the dedicated MCP host. See the `Oct 2026/` package.
