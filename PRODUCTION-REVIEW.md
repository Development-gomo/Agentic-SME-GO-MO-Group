# October 2026 package — production review

Reviewed 20 October 2026. Recommendation: **hold full production deployment; stage and address the findings below first**.

Implementation update: the first five code fixes requested after this review have been implemented: complete snapshot publication of API/discovery data, missing/empty-source guards, checked writes and atomic activation, database-backed contact reservations, and bounded PHP/MCP inputs. Regression tests now exercise simultaneous submissions and snapshot readers. The findings below retain the original review context; event metadata and hosting/content release checks remain outstanding. See DEPLOYMENT.md for the new storage and database requirements.

Scope: local v1.3.0 package, PHP router and sync implementation, MCP source, bundled data, deployment instructions and existing tests. No production changes or real contact submissions were made. Production WordPress/WPML behavior, reverse proxy, mail delivery and caching were not exercised. SITE-FINDINGS.md describes an earlier crawl; its live-site claims were not independently rechecked in this review.

## Findings

### 1. High — sync does not update the discovery artifacts

`gomo-api/includes/class-sync.php:140–188` updates API data files but never regenerates `agent-page.html`, `agent-index.json`, `llms.txt` or `api-catalog.json`. Adding/removing an insight, event or job and running the documented “Sync now” step therefore changes the JSON API while the discovery documents keep the old content. Removed jobs can remain advertised to agents.

Use one validated publication pipeline for all artifacts, or explicitly operate this version as a static snapshot with a documented rebuild and redeploy process. Test adding and removing content across both the API and discovery files.

### 2. High — sync can overwrite valid data with empty results and does not publish atomically

`class-sync.php:190–194` returns an empty collection if a configured post type is absent; `run()` writes that result over existing data. A post-type registration problem can therefore wipe populated feeds during sync.

`class-sync.php:419–423` writes directly to the file used by requests, ignores the write result, and reports success through the surrounding sync log. `LOCK_EX` does not protect readers that do not take a shared lock. The router streams unfiltered files directly (`gomo-api.php:163–172`), so requests during a write can receive empty/partial JSON with HTTP 200. Filtered reads can return 503.

Validate required post types and collection results before publishing. Write validated JSON to temporary files, check errors, then atomically rename; retain the previous good snapshot and serialize concurrent syncs. Test failed writes, missing post types and concurrent readers.

### 3. High for the public contact endpoint — rate limits and duplicate checks are non-atomic

`gomo-api.php:379–388` reads transient counters; `466–474` sends mail before updating them. Concurrent requests can all read the same available allowance, send mail and overwrite the counter with the same increment. Duplicate checks have the same race. The advertised five/client/hour and twenty/site/hour limits are therefore not reliable under bursts.

Reserve quota and idempotency atomically before sending, using a suitable database/cache primitive or enforced edge controls. Test concurrent submissions. Keep contact submissions disabled until this is resolved if releasing the read API first.

### 4. Medium — PHP contact validation does not enforce the MCP field limits

`gomo-api.php:398–460` checks presence, email format, minimum summary length and link count, but does not enforce the maximum lengths present in the MCP schema. Direct callers of the public PHP endpoint bypass MCP validation and can send oversized names, subjects, summaries and conversation text. The plugin also reads the complete request body without an application-level size bound.

Enforce body size and field limits at the public endpoint; keep PHP, OpenAPI and MCP validation aligned. Verify oversized requests are rejected before mail is attempted.

### 5. Medium — new events cannot acquire event-specific data through sync

`class-sync.php:260–278` collects generic post fields only. Existing events retain curated dates/location through `merge_curated`, but newly published events lack `startDate`, location and registration fields. A WordPress publication date is not an event date.

Read the actual event metadata or require curation before exposing new events as complete records. Verify a newly created bilingual event end to end.

## Checks performed

- PHP router: **47/47 passed**.
- Sync merge: **6/6 passed**; this tests one merge helper, not an actual WordPress sync.
- Generated outputs: **23 checks passed**, including JSON parsing, duplicate IDs, language pairs, anchors, JSON-LD and matching plugin copies.
- MCP smoke tests: **8/8 passed** with local fixture data after allowing local listening ports.
- TypeScript: `node node_modules/typescript/bin/tsc --noEmit` passed.
- `npm audit --omit=dev --json`: **0 reported vulnerabilities** at review time.
- `npm run build` failed because the supplied node_modules lacks the `tsc` executable shim. Direct compiler invocation worked. Perform a clean `npm ci` and build in staging; do not deploy the copied node_modules directory.

The MCP smoke fixture is Python's static file server. It does not exercise WordPress query filtering, actual proxy behavior, successful contact forwarding or mail delivery. Passing this suite is useful evidence, but not a full deployment acceptance test.

## Release conditions

1. Resolve the sync publication issues and contact controls above.
2. Install dependencies cleanly, build and run the suite on the intended hosting runtime.
3. Exercise sync in a WordPress/WPML staging copy; compare counts, URLs, translations, event fields and removed-content handling before and after.
4. Verify physical-file precedence, rewrite rules, cache purging, UTF-8 headers and rollback with the actual host configuration.
5. Verify MCP through the actual HTTPS proxy, and verify mail delivery using an authorized staging recipient.
6. Resolve the content decisions already documented in SITE-FINDINGS.md, especially the GEO price discrepancy and AI training policy, before publishing those claims.

The initial review made no application changes. The subsequent implementation updates the plugin, MCP validation, generator, tests and deployment instructions.
