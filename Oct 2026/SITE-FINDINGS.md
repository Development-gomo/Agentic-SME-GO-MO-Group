# gomogroup.com: issues found during the v1.3 crawl (2026-10-19)

These are issues on the live website itself. The agentic package works around them, but they should be fixed in WordPress.

## Needs a decision from GO MO
1. **GEO tier 1 price.** The English page says *LLM Audit & Strategy* is from **23,500 SEK**. The Swedish page says *GEO-analys & strategi* is from **27,500 SEK**, and lists different contents. The package publishes 23,500 and mentions 27,500.
2. **Anonymised client named on the Swedish page.** The English enterprise case anonymises the client ("Swedish manufacturing giant"), but the Swedish page names Atlas Copco. The package keeps it anonymised. Confirm whether the client can be named.
3. **AI training policy.** `robots.txt` says `Content-Signal: ai-train=no`, but `ai.txt` says `Allow-Training: yes`.
4. **Founding year.** The navigation says "SEO agency since 2013", but Ida E. Ghavami's profile says GO MO started in 2012. The package uses 2013.

## SEO and agent-readiness gaps
- **No hreflang tags on any page.** WPML is active, but no `<link rel="alternate" hreflang>` tags are output. Language pairs exist only through the "Switch to…" menu link, and only about 80 pages have one. This is the single biggest bilingual SEO gap.
- **No author information on insights.** There are no bylines and no `author` Person schema on any of the 165 articles. That is an E-E-A-T gap for GEO.
- **The three English event pages (`/en/event/…`) are missing from the sitemap**, although they return 200. The sitemap lists the Swedish `/event/` paths instead, and one of them (`/event/b2b-geo-seminar-may-2026/`) 301-redirects to the English page.
- **Shared meta title.** `demand-generation` uses the meta title and description of `b2b-lead-generation`.
- **Unrelated SEO title on the Volvo case (SV).** The Swedish page's title and description are copied from the education case.
- **Team profiles that don't exist.** 10 people are linked from the about, contact and careers pages at `/en/team/…` and `/medarbetare/…`, but their profiles 301-redirect to the About page. That's 20 URLs (for example `/en/team/joshua-amirose/`). `/en/team/` itself also redirects.
- **8 sitemap URLs redirect.** The sitemap should list only final URLs:
  - `/en/case/building-a-genai-fueled-content-supply-chain-…/` → `/en/insight/ai-manufacturing-case-study/`
  - `/en/insight/cmos-of-tomorrow-niklas-digiexam/` → `/en/case/cmos-of-tomorrow-niklas-digiexam/`
  - `/en/insight/seo-for-global-giants-volvo-group/` → `/en/case/seo-for-global-giants-volvo-group/`
  - `/en/services/website-design-and-development/` → `/en/services/website-development/`
  - `/en/team/joshua-amirose/` → `/en/about-us/`
  - `/event/b2b-geo-seminar-may-2026/` → `/en/event/b2b-geo-seminar-may-2026/`
  - `/insikter/foga-foretags-gar-digital/` → `/case/inredningsbolaget-blev-digitalt/`
  - `/vara-tjanster/hemsida-design-och-utveckling/` → `/vara-tjanster/webbutveckling/`
- **8 live pages are set to noindex.** The package leaves 6 of them out to respect that setting. The 2 team profiles are included on request and tagged `noindex`. Check that each one is intended:
  - `/en/services/generative-engine-optimization-geo-2/` is a published "(copy)" duplicate of the GEO page. Unpublish it.
  - `/vara-tjanster/google-ads-stockholm/` is a service page set to noindex, nofollow.
  - `/en/logo-brand-guidelines/` and `/logo-brand-guidelines/` are brand guidelines (noindex, nofollow).
  - The Isabelle Coquet and Oliver Sträng Paschetto profiles, in EN and SV (noindex, follow). Both people are linked from the contact page, but the profile pages have no content beyond the site header and footer: no bio and no role text. Add bios, then remove noindex. **Included in the package**, with role and contact details taken from the contact page.
- **Broken GA4 landing page.** `/ga4/` and `/ga4-landing-page/` redirect to each other and end on the homepage.
- **24 orphan pages.** These are published WordPress pages that no page links to and no sitemap lists. All are noindex, so the package leaves them out:
  - 17 thank-you pages (`/en/thank-you*`, `/tack*`).
  - Internal documents: `/chatgpt-kurs/`, `/vinga-hiss/` (ChatGPT prompts) and `/en/email-signature/`.
  - `/en/logo-brand-guidelines/` and `/logo-brand-guidelines/`.
  - An `/en/about-us-new/` draft and an unlaunched `/knowledge-hub/`.
  
  Unpublish anything that shouldn't be public. They are all reachable through `/wp-json/wp/v2/pages`.
- **Stale `/agent/index.json`.** It was generated on 2026-09-03 and still claims "50+ specialist marketers", which the site no longer supports. v1.3 regenerates it (see DEPLOYMENT.md).
- **Videos sitemap returns 500.** Both URLs in `videostype-sitemap.xml` (`/videos/client-video-testimonials/`, `/videos/meet-the-marketer/`) return HTTP 500.
- **Bot-protection page served to crawlers.** The SiteGround "Checking the site connection security" page was served instead of content on 2 pages during the crawl. AI crawlers may get the same. Consider allowlisting AI user-agents in SiteGround's bot protection.

## Broken links and content errors
- **Links to `/page-404/`.** The SV SaaS/Tech page (both "Kontakta oss" CTAs) and the SV SME page (hero CTA).
- **Links to staging pages.** The website-design and website-development pages link to `website-design-new` and `website-development-new`.
- **Missing contact form.** The SV YouTube page shows "Fel: Kontaktformulär hittades inte." where the form should be.
- **Wrong city.** `google-ads-goteborg` says "Google Ads i Stockholm" in its H1 and intro.
- **Copy from other pages.**
  - The EN security-company case uses the Alfa Laval H1.
  - The West Sweden Chamber of Commerce case contains "All four companies now have:" (copied from HighFive).
  - The SV hotel case quote is credited to "…Adnavem".
  - `seo-byra-goteborg` contains SEA copy.
  - Several service pages keep leftover "wireframe your new website" text.
- **Inconsistent figures.**
  - The Swedish-manufacturer case's meta description says 2% → **11%**, while the page says 2% → **7%**.
  - The SEO-automation case's description says 5,000+ *product pages*, while the body says 3,000+ pages and 5,000+ content pieces.
  - The Fru Bohlin traffic uplift is given three ways: 1,200%, 1,255% and 1,180%.
- **Wrong or inconsistent names.**
  - The Digiexam interviewee is captioned "Niklas Vernersson" but is Niklas Borgström.
  - Anja Simonsson's title differs between pages (Munters vs Alfa Laval).
  - "Jens Gustafsson/Gustavsson" is spelled both ways.
- **Inconsistent job titles.**
  - Henrik Anderberg is "EVP / Vice VD" on the card but "Chief Client Officer" in the page title.
  - Puja Kumari is "Vice Managing Director" on the card but "User Experience Manager" in the title and bio.
- **Untranslated Swedish profiles.** The SV team profiles show English bios; only Anuvinder's is translated. Rickard's SV profile has a garbled phone link.
- **Stale career form.** The career form defaults to an unlisted role, "GÖTEBORG | Head of Client Success". `/karriar/` lists no open roles.
- **Typos.** "Generetativ" (SV enterprise title) and a stray `/div>` on the SV solution pages.

## Data problems in the live v1.2 API (fixed in v1.3)
- **Junk entries.** `services.json` included "(copy)" drafts with empty descriptions and pricing. `team.json` had 69 entries with empty roles. Case results were all empty.
- **Wrong company data.** `company.json` gave `gaurav.k@gomogroup.com` as the general contact and had broken `keyPages` (`/about-us/`, `/services/`, `/case/`, `/insights/`, `/career/`).
- **Outdated office details.** `offices.json` had the old Stockholm address (Svartmangatan 16), and the Gothenburg phone number was Henrik's mobile.
- **Wrong figures and names in v1.2 llms.txt.** "5,000+ product pages", "Niklas Vernersson", "Bing Ads" on SEA, and "Python/FastAPI, Azure" on web development are not supported by the live pages.
