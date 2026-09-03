# GO MO Group — Agentic Web Package update (2026-09-02)

Rebuilt with Firecrawl from the live site. 80 English content pages + 5 Swedish
case pages scraped across core / services / solutions / team / careers / cases /
insights, plus the full sitemap (321 URLs) via Firecrawl `map`.

## Files in this folder

| File | Deploy to | Status |
|---|---|---|
| `llms.txt` | `https://www.gomogroup.com/llms.txt` | rebuilt — ~330 lines; Markdown-link parity verified (`https://` count == `](https://` count); all 55 English insights listed in full |
| `ai.txt` | `https://www.gomogroup.com/ai.txt` | rebuilt — ~200 lines; Conflict Resolution section present |
| `agent/index.html` | `https://www.gomogroup.com/agent/` | rebuilt — ~1,130 lines, 21 sections, 19 FAQs, 8 JSON-LD @graph entries |
| `agent/index.json` | `https://www.gomogroup.com/agent/index.json` | new — JSON twin of the agent-page reference data; 19 FAQ entries (mirrors the HTML) |
| `robots-addition.txt` | paste into `https://www.gomogroup.com/robots.txt` | crawler allowlist + `Sitemap:` directive; non-standard `LLM-*`/`AI-File` hints kept commented out |

(Line counts are approximate packaging notes, not validated invariants. The verified checks are: llms.txt Markdown-link parity, JSON validity of index.json, JSON-LD validity + FAQ parity (HTML = JSON-LD = index.json = 19), and every scraped page present in both llms.txt and agent/index.html.)

## Follow-up additions (2026-09-03)

- Added the agent page's original publication date (`2026-05-26`) to the visible HTML metadata, HTML meta tags, `WebPage` and `Dataset` JSON-LD, `agent/index.json`, and `ai.txt`.
- **`llms.txt`:** the Insights section now lists **all 55 English `/en/insight/` articles** (was a ~24-item selection), retitled "Insights / Blog — full list".
- **3 Swedish-only case studies** scraped and folded into `llms.txt`, `agent/index.html` (`#cases` + page-index), and `agent/index.json`:
  - Fru Bohlin — interior-design agency goes fully digital (revenue SEK 2.7M→~16M; organic traffic +1,180%, paid-search +3,800%)
  - Income-insurance company — digital sales process (+70% digital sales in 3 months, +50% qualified leads)
  - Hotel — reclaiming digital business with SEO (older case; "long-term parking" content +815%, "book" clicks +462%)
- Also linked, with a note that they are Swedish variants of already-covered English cases: `some-innehall-med-genomslag` (= Social content with impact) and `tillverkningsbolag-okad-komplexitet-i-b2b-forsaljning` (= System Edström digital-shift case).
- Still not enumerated individually: the ~110-article Swedish `/insikter/` blog archive and the Swedish translations of the English case pages (linked via their index pages).

### Review-pass fixes (2026-09-03)

- **Events** reframed as past/held (all three 2026 dates are before today) in `ai.txt` (`Recent-Events`), `llms.txt`, `agent/index.html`, and `agent/index.json` (`status: "held"`, plus an `eventsNote`), each pointing to `/en/events/` for current dates.
- **`agent/index.json`** FAQ array expanded 18 → 19 to match the HTML (added "What is the GEO Playbook?").
- **`robots-addition.txt`**: kept `Sitemap:` as the one real directive; the non-standard `LLM-Readable`/`LLM-Text`/`AI-File` lines are commented out with a note (RFC 9309 parsers ignore unknown lines; the real discovery files are `/llms.txt`, `/ai.txt`, `/agent/`). Crawler allowlist also refreshed (added OAI-SearchBot, Claude-User, Meta-ExternalAgent, Amazonbot, DuckAssistBot).

Not regenerated (unchanged, still valid): `api-catalog.json`, `openapi.json`, the
WordPress REST API plugin, and the MCP server. All three files still reference them.

## What changed vs the previous package

**Company / offices**
- Stockholm office address updated: **Upplandsgatan 7, 111 23 Stockholm** (was Svartmangatan 16, 111 29).
- Pune office updated: **WeWork, Fourth Floor** (was Third Floor).
- Added affiliation detail: GO MO Group is part of **Collaboration Art**, a consortium / "house of brands" for European performance agencies.
- Added "100+ successful B2B client projects"; team framed as "150+ colleagues across Europe and India".
- Henrik Anderberg is now described as **EVP / Chief Commercial Officer (CCO)** (was "EVP / Chief Client Officer").
- Added team members: Joshua Amirose (Copywriter). Anuvinder Singh clarified as Director & Partner of GO MO DBS / head of Online Acceleration.

**Services**
- Added four service pages that now exist on the site: **Demand Generation** ("Online Acceleration"), **B2B YouTube Marketing**, standalone **B2B Lead Generation (GO MO DBS)**, and **Website Design and Development** (combined).
- Clarified **GO MO DBS** is a *sister company* within the group, not just a service line.
- **GEO pricing updated**: LLM Audit & Strategy **from 23,500 SEK** (was "GEO Audit & Strategy 27,500 SEK"); Audit, Strategy & Implementation **from 44,000 SEK**; **Enterprise Content & GEO Retainer** (custom) replaces the old "360° Solution" naming.
- Added GEO facts: Nordics' first **SEMrush Enterprise AIO partner**; baseline across **5 AI engines**; **213M+ LLM-prompt** database; 63% of B2B buyers use AI tools during research; 1-page summary within 48h; GEO Playbook is 12 pages, used by 200+ Nordic marketers.
- Added YouTube stats (2.7B users, 70% of B2B buyers watch video, ~5% invest in organic YouTube, ~3x engagement).

**Case studies**
- SEO/GenAI e-commerce result corrected to **+195% organic visibility within 120 days** (previously stated as 90 days).
- Added verified figures: Swedish manufacturer 43% CPL drop YoY; Dell 3% prospect conversion / 13% with 6-month timeline; Coor deals closed 30% faster; alarm/security ~30% bounce-rate drop; System Edström up to +700% conversions; Adnavem +80% inquiries Y1; TÜV NORD +175% organic traffic / +100% unique visitors / +23% contact requests; data-driven PR client "$50B USD sales 2024, 190+ countries"; GenAI content case BCG stat (Nordics 19% vs 61% global weekly GenAI use).
- Added newer case pages: AI-powered website designs, AI-generated brand images, AI-powered brand videos, Brand systems and identity, Social content with impact, Digital marketing & growth audit, CMOs of Tomorrow – Niklas (Digiexam).

**Insights & events**
- Refreshed the insights list with 2026 GEO/AI articles ("Your buyers are asking AI", "Closing the B2B commerce discovery gap", "We tested llms.txt", "AI-First Marketing", the 75-term GEO glossary, "Henrik promoted to CCO", etc.).
- Added the 2026 event series: Future of Search seminar (07 Apr 2026, Göteborg, recap) and B2B GEO Seminars on 06 May 2026 and 10 Jun 2026.

**Structure / schema (agent/index.html)**
- Added `BreadcrumbList` and `Dataset` JSON-LD entries.
- Added `knowsAbout` and `hasOfferCatalog` to the Organization entity.
- Added dedicated `Service` schema entries for GEO (with priced offers), Generative AI Implementation, and ASBX.
- New `#capabilities` and `#comparisons` sections; `#geo` and `#generative-ai` deep-dive sections.
- `agent/index.json` referenced only now that the file exists in this package.

## Scope note

The package is English-primary with a Swedish appendix (as before). The Swedish
`/insikter/` archive (100+ articles) and the Swedish-language case/URL variants are
linked via their index pages rather than enumerated individually.

## Verification run

- llms.txt: `https://` count == `](https://` count (242 == 242).
- agent/index.html: JSON-LD parses; 19 `<details>` FAQs == 19 `FAQPage` Q&As; every
  `WebPage.hasPart` anchor exists in the body; every `Dataset.distribution` URL is a
  file in this package.
- All 80 scraped live pages appear in both `llms.txt` and `agent/index.html`.
