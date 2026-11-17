# gomogroup.com: issues found during the v1.4 crawl (2026-11-17)

These are issues on the live website itself. The agentic package works around them, but they should be fixed in WordPress.

Items marked *(re-checked)* were confirmed again in the 2026-11-17 crawl. Items marked *(from 2026-10-19)* were reported last month and were not specifically re-tested.

## Resolved since the v1.3 crawl
- **GEO tier 1 price.** The English and Swedish pages now both show 23,500 SEK and 44,000 SEK, and both have the same free Mini GEO Audit tier.
- **hreflang.** WPML now outputs `<link rel="alternate" hreflang>` on 324 pages. The package uses these pairs.
- **AI training policy.** `robots.txt` now says `Content-Signal: ai-train=yes`, which matches `ai.txt` (`Allow-Training: yes`).
- **Shared meta title.** `demand-generation` now has its own meta title and description.
- **Author information.** 118 of 174 insights now show an "About the author" box. The package uses these bylines.
- **Stale `/agent/index.json` and v1.2 API data.** Replaced by v1.3.0, which is live.

## Needs a decision from GO MO
1. **Atlas Copco is named in the main menu.** Both languages now say "Atlas Copco" in the Case mega-menu, and the Swedish case page names the client. The English case page still anonymises it ("Swedish manufacturing giant"). The package keeps the client anonymised. Confirm whether the client may be named, and make the site consistent.

## New in this crawl
- **Founding year conflicts with the confirmed year.** GO MO has confirmed 2013 (2026-11-17), and the package publishes 2013. Two places on the site disagree and should be corrected:
  - the Organization structured data says `foundingDate: 2014`;
  - Ida E. Ghavami's profile says GO MO started in 2012.
- **The English event pages declare Swedish canonicals.** All three `/en/event/…` pages set `<link rel="canonical">` to the Swedish `/event/…` path. One of those Swedish paths, `/event/b2b-geo-seminar-may-2026/`, 301-redirects back to the English page, so the canonical goes in a loop. Each event page should be canonical to itself. The package keeps the English URLs.
- **The site's Organization structured data is out of date.** The custom Organization block on every page says:
  - "a team of 50+ specialists" (the about page says about 70 employees, 150+ with GO MO DBS);
  - `foundingDate: 2014`;
  - the old Stockholm address, Svartmangatan 16, 111 29 (now Upplandsgatan 7, 111 23).

  Update it, or remove it in favour of Yoast's Organization node.
- **Broken menu links:**
  - EN mega-menu "GEO Service Overview" → `/en/?post_type=services&p=41769` (404).
  - SV "Västsvenska Handelskammaren" → `/?post_type=case&p=47687` (404).
  - SV "Industrial Manufacturing (Enterprise)" → `/case/fran-genai-ide-till-handling-…/`, which 301-redirects to `/insikter/…`.
  - EN "Industrial Manufacturing (Enterprise)" → a long `/en/case/building-a-genai-fueled-content-supply-chain-…/` URL that redirects to `/en/insight/ai-manufacturing-case-study/`.
- **Old SEO automation figures still appear in case teasers.** The homepage case carousel and the "next case" teaser on about 10 English service and solution pages still say "195% increase in organic clicks over 90 days" and "saved 400+ hours". The case page itself was rewritten on 2026-11-12 and now reports 32× organic clicks (250 → ~8,100 a month). The package uses the case page's figures. Update the teasers.
- **A removed Swedish case is still linked.** `/case/generativ-ai-fran-hype-till-faktisk-affarsnytta/` now returns 404. Check the internal links to it and decide whether to redirect it to the English case.
- **Language pairs missing in hreflang:**
  - The West Sweden Chamber of Commerce case (EN ↔ SV) and the events have no hreflang pair. The package keeps the earlier pairs.
  - `/insikter/cmos-of-tomorrow-niklas-digiexam/` points its English hreflang at `/en/insight/cmos-of-tomorrow-niklas-digiexam/`, which redirects. It should point at `/en/case/cmos-of-tomorrow-niklas-digiexam/`.
- **New team profiles are noindex and empty.**
  - Oliver Wilkenson (`/en/team/oliver-wilkenson/`, `/medarbetare/oliver-wilkenson/`) and Ville Olsson (`/medarbetare/ville-olsson/`) have profile pages set to noindex, and they are missing from the sitemap.
  - The Isabelle Coquet and Oliver Sträng Paschetto profiles are still noindex with no bio.
  - Add bios, then remove noindex.
- **Swedish YouTube page title typo.** It reads "**2B** YouTube-marknadsföring för industriföretag" (missing "B").
- **Legacy internal links.** Older posts link to 87 retired URLs (for example `/insikter/gabriel-ghavami/…`, `/digital-marknadsforing/`, `/en/services/web-development/`).
  - 82 of them redirect.
  - `/en/services/` and `/vara-tjanster/` return 404.
  - Update the links to the final URLs.
- **Off-domain link in the Swedish TÜV NORD case.** The "B2B digital marknadsföring" link points to an external non-public domain instead of `https://www.gomogroup.com/b2b-digital-marknadsforing/`.

## SEO and agent-readiness gaps
- **Author markup lags the visible bylines.**
  - Only 70 of the 118 articles with an author box also carry the author as Person structured data. Henrik Anderberg's 18 articles and the 30 articles by other colleagues have none.
  - 56 insights have no author at all.
  - Adding `author` Person markup wherever the box is shown would strengthen E-E-A-T signals for GEO.
- **New booking page with an external canonical.** `/boka-analys/` (published 2026-11-16) is `noindex` and sets its canonical to a Google Calendar booking URL. That is fine for a booking page, but link to it only from CTAs and keep it out of the sitemap.
- **The English event pages are missing from the sitemap.** The sitemap lists only the Swedish `/event/` paths *(re-checked)*.
- **Unrelated SEO title on the Volvo case (SV)** *(from 2026-10-19)*.
- **Sitemap URLs that redirect** *(re-checked)*. The sitemap still lists:
  - `/en/insight/cmos-of-tomorrow-niklas-digiexam/`
  - `/en/insight/seo-for-global-giants-volvo-group/`
  - `/en/services/website-design-and-development/`
  - `/event/b2b-geo-seminar-may-2026/`
  - `/insikter/foga-foretags-gar-digital/`
  - `/vara-tjanster/hemsida-design-och-utveckling/`
  - the long `/en/case/building-a-genai-fueled-content-supply-chain-…/` URL

  The sitemap should list only final URLs. The "(copy)" GEO page `/en/services/generative-engine-optimization-geo-2/` now redirects to the GEO page and is no longer in the sitemap.
- **Noindex pages.** `/vara-tjanster/google-ads-stockholm/` (noindex, nofollow) and `/en/category/uncategorized/` are left out. Check that this is intended.
- **Broken GA4 landing page.** `/ga4/` redirects to the homepage *(re-checked)*.
- **The videos sitemap entries redirect.** `/videos/client-video-testimonials/` and `/videos/meet-the-marketer/` now redirect to the homepage *(re-checked)*. Remove them from `videostype-sitemap.xml`.
- **Orphan pages.** These are noindex and are left out *(from 2026-10-19)*: 17 thank-you pages, `/chatgpt-kurs/`, `/vinga-hiss/`, `/en/email-signature/`, the logo and brand guideline pages, `/en/about-us-new/` and `/knowledge-hub/`. Unpublish anything that shouldn't be public.
- **Bot protection served to crawlers.** During this crawl, the SiteGround "Checking the site connection security" page was served once for `/insikter/ar-ai-framtiden-for-bygg-och-fastighetsbranschen-go-mo-group/` and once for `/medarbetare/`; both loaded on a normal retry. Consider allowlisting AI user agents in SiteGround's bot protection.

## Broken links and content errors
- **Links to `/page-404/`** from `/solution/segment-b2b-saas-tech/` and `/solution/segment-sme/` *(re-checked)*.
- **Links to unpublished draft pages** (`website-design-new`, `website-development-new`) from the website design and development pages (EN and SV) *(re-checked)*.
- **Missing contact form.** The Swedish YouTube page shows "Fel: Kontaktformulär hittades inte." *(re-checked)*.
- **Wrong city.** The `google-ads-goteborg` H1 says "Annonsering: Google Ads i Stockholm" *(re-checked)*.
- **Copy carried over from other pages:**
  - "All four companies now have:" appears on the HighFive and West Sweden Chamber of Commerce cases *(re-checked)*.
  - Four pages still contain "wireframe your new website" *(re-checked)*.
  - The EN security-company case uses the Alfa Laval H1, and `seo-byra-goteborg` contains SEA copy *(from 2026-10-19)*.
- **Inconsistent figures:**
  - The Swedish-manufacturer case's meta description says 2% → **11%**, while the page says 2% → **7%** *(re-checked)*.
  - The Fru Bohlin traffic uplift is given as 1,200%, 1,255% and 1,180% *(from 2026-10-19)*.
- **Wrong names.** The Digiexam interviewee is captioned "Niklas Vernersson" on three pages but is Niklas Borgström *(re-checked)*. Anja Simonsson's title and the Gustafsson/Gustavsson spelling also differ between pages *(from 2026-10-19)*.
- **Inconsistent job titles.** Henrik Anderberg is "EVP / Vice VD" on cards but "Chief Client Officer" in his page title. Puja Kumari is "Vice Managing Director" on cards but "User Experience Manager" in her title *(re-checked)*.
- **Untranslated Swedish profiles.** The Swedish team profiles show English bios *(from 2026-10-19)*.
- **Stale career form.** The career form still defaults to "Head of Client Success". `/karriar/` lists no open roles *(re-checked)*.
- **Typos.** "Generetativ" on the SV enterprise solution page *(re-checked)*. A stray `/div>` on the SV solution pages *(from 2026-10-19)*.
