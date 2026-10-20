# GO MO Group — Agentic Web Package v1.3.0

Refresh and extension of the live v1.2 package. It was built from a full crawl on 2026-10-19 and covers **314 public URLs: all 310 indexable pages (130 English, 180 Swedish) plus 4 noindex team-profile URLs**, added on request. All canonical URLs are unchanged, so existing integrations keep working.

## What's in this folder

| File | Deploys to | Notes |
|---|---|---|
| `agent-page.html` | `/agent/` | 450 KB semantic HTML with JSON-LD. Bilingual: 24 services, 6 solutions, 30 cases, 165 insights (56 EN, 109 SV), 35 people, a 314-URL page index (noindex URLs are tagged) |
| `agent-index.json` | `/agent/index.json` | Single-file JSON summary. Replaces the stale live file from 2026-09-03 and keeps every v1.2 key (organization, offices, leadership, services, solutions, caseStudies, events, openRoles, keyPages, machineReadable, faq). Adds Swedish URLs, insights and 37 site FAQs |
| `llms.txt` | `/llms.txt` | 334 Markdown links, no bare URLs, lists every EN and SV insight |
| `ai.txt` | `/ai.txt` | Permissions, canonical sources, language rules, consent rule |
| `api-catalog.json` | `/api-catalog.json` | APIs.json 0.16 |
| `robots-addition.txt` | paste into live `robots.txt` | Fixes the `Content-Signal` contradiction (see below) |
| `gomo-api/` | `wp-content/plugins/gomo-api/` | WordPress plugin v1.3.0. Serves `/agent/v1/*`, `/openapi.json`, and optionally `/agent/`, `/llms.txt`, `/ai.txt`, `/api-catalog.json` |
| `gomo-mcp/` | Render service at `https://agent-mcp.gomogroup.com/mcp` | 12 tools (8 existing + `list_insights`, `list_events`, `list_careers`, `get_page_index`); bundled read snapshots allow safe reads when the WordPress origin is blocked |
| `tests/`, `run-tests.sh` | not deployed | Plugin router/contact (71 cases), sync merge, snapshot publication, generated outputs, MCP protocol. Run `./run-tests.sh` before every deploy |
| `_source/` | not deployed | Crawl data, curated JSON and `build.py` (regenerates every file above) |

### New in v1.3
- **Swedish coverage:**
  - Services and cases are one bilingual item each, with `url` / `url_sv`.
  - Insights exist per language, with an `alternate` link to the translation.
  - Every endpoint accepts `?lang=en|sv|all`.
- **New endpoints:**
  - `insights.json` (with `?topic=` and `?since=`) and `insights/{id}.json`
  - `events.json`, `careers.json`
  - `pages.json`, the full URL index with EN↔SV pairs
- **Data quality fixes** (v1.2 published bad data):
  - Empty descriptions, empty roles and empty case results.
  - "(copy)" draft duplicates, a 69-person team list and `gaurav.k@` as the general email.
  - Broken `keyPages` URLs (`/about-us/`, `/services/`, …).
- **Sync generator rewritten.** The cause of the bad data was the v1.2 sync rebuilding curated files from raw post fields. `class-sync.php` now:
  - regenerates only insights, events, careers and pages;
  - refreshes dates on curated files and coverage counts in company data;
  - lists any new service or case under **"NEEDS CURATION"** instead of publishing empty entries.
- **Plugin serves the discovery files.** `/llms.txt`, `/ai.txt` and `/api-catalog.json` come from the plugin with `charset=utf-8`. The live files are currently served as `text/plain` with no charset, which can garble å/ä/ö. The plugin also adds `<link rel="alternate">` discovery tags to every page.

## Deployment checklist

### 1. WordPress plugin
- [ ] Back up the current `wp-content/plugins/gomo-api/` folder, then replace it with this `gomo-api/`.
- [ ] **Remove the physical file `/agent/index.json`** so the plugin serves it from the active snapshot. Physical files take precedence over rewrite rules and would prevent future syncs from appearing.
- [ ] Remove static root copies of `llms.txt`, `ai.txt`, `api-catalog.json` and `openapi.json`. Remove any physical `/agent/index.html` if the plugin is serving `/agent/`. All these URLs must reach the plugin for snapshot publication to work.
- [ ] The plugin serves `/agent/` from `agent-page.html` by default. The current `/agent/` is a WordPress page inside the Enfold theme. To keep that page, untick the setting under **Tools → GO MO Agent API**.
- [ ] **Settings → Permalinks → Save**. Without this, every new endpoint returns 404.
- [ ] **Tools → GO MO Agent API → Sync now**. Check the report for "NEEDS CURATION".
- [ ] Confirm the report ends with **Published complete snapshot**. A `FAILED` report means the previous publication is still active; resolve the reported error before retrying.
- [ ] Exclude these machine-readable routes from host page caching and purge old CDN/page-cache entries. The plugin uses `max-age=0, must-revalidate` so API and discovery files do not remain fresh in separate caches after a sync.
- [ ] Verify:

```bash
curl -s https://www.gomogroup.com/agent/v1/index.json | head -20
```

```bash
curl -s "https://www.gomogroup.com/agent/v1/insights.json?lang=sv&per_page=3"
```

```bash
curl -sI https://www.gomogroup.com/llms.txt | grep -i content-type
```

```bash
curl -X POST https://www.gomogroup.com/agent/v1/contact.json -H "Content-Type: application/json" -d '{"agent_context":{"user_authorized_submission":true},"person":{"name":"Test","email":"test@example.com"},"company":{"name":"Test Co"},"inquiry":{"service_interest":"GEO","project_summary":"Deployment test submission"}}'
```

The test lead goes to **ha@gomogroup.com**, with Reply-To set to the submitter.

### Contact endpoint safeguards
`agent_context.user_authorized_submission` is a promise made by the calling agent, not an authentication check: anyone can set it. A CAPTCHA or login would block the AI agents this endpoint exists for, so abuse is limited instead:

| Control | Default | Where to change it |
|---|---|---|
| Off switch | On | **Tools → GO MO Agent API → Accept agent contact submissions** |
| Per-client limit | 5 delivery attempts per hour | `define('GOMO_CONTACT_PER_IP_HOUR', 5);` in `wp-config.php` |
| Site-wide limit | 20 delivery attempts per hour | `define('GOMO_CONTACT_GLOBAL_HOUR', 20);` |
| Links allowed in the summary | 3 | `define('GOMO_CONTACT_MAX_LINKS', 3);` |
| Duplicate suppression | Same email, company and service within 24 h returns the original lead ID and sends no second email | — |
| Request body | 32,768 bytes, including requests without Content-Length | PHP and MCP |
| Field limits | Names, email, URLs and text are bounded; summary ≤ 4,000 characters; conversation summary ≤ 2,000 | OpenAPI and MCP schemas |

Quota and duplicate reservations are persisted in one non-autoloaded WordPress options row, protected by a MySQL/MariaDB advisory lock. The host must support `GET_LOCK` / `RELEASE_LOCK` on the same primary database connection as the options reads/writes. Do not route these queries through separate replicas or transaction-pooling connections. If locking or persistence fails, the endpoint returns 503 without sending mail.

Quota is reserved **before** mail delivery. Known failed deliveries return 502 and can be retried, but still consume attempt quota. An identical inquiry already in progress returns 409. If delivery outcome or reservation completion is uncertain, the reservation remains pending for up to 24 hours to avoid sending twice; the response directs the caller to email instead. This is duplicate prevention, not a durable mail queue. Confirm the database behavior and actual mail receipt on staging.

### Snapshot storage and recovery

Sync requires the plugin directory to be writable by the WordPress PHP process, on a filesystem supporting advisory `flock` and atomic same-directory rename. All WordPress workers must share this filesystem; deployments with isolated per-host disks need shared storage before enabling sync.

Sync validates required post types, prepares every API/discovery file in `gomo-api/snapshots/<generation>/`, then atomically replaces `snapshots/current.json`. Requests pin one generation, so they never mix files during publication. Bundled files remain the initial baseline and are not overwritten. Earlier generations are retained for rollback and in-flight readers; monitor disk usage and remove older, unreferenced generations during scheduled maintenance.

Unexpected empty results for a previously populated feed abort the entire sync. For a verified intentional removal of the last item (for example, the last open job), temporarily allow only that endpoint:

```php
add_filter('gomo_api_allow_empty_sync', static function ($allow, $endpoint) {
    return $endpoint === 'careers';
}, 10, 2);
```

Remove the filter after that sync. Missing post types and query failures cannot be overridden by this filter.

Back up the **entire plugin directory including snapshots** before updates. The active generation and its predecessor are recorded in `snapshots/current.json`. To roll back, an operator can atomically replace that pointer with a validated previous generation; to restore the bundled baseline, remove the pointer during a maintenance window. Purge external caches after either action. Updating bundled files alone does not change an already active snapshot.

Two settings matter for the per-client limit:
- **Behind a proxy or CDN.** If `REMOTE_ADDR` is the proxy's address rather than the visitor's, every request shares one bucket. In that case set `define('GOMO_TRUSTED_PROXY_HEADER', 'HTTP_X_FORWARDED_FOR');`, but only if your proxy overwrites that header. On SiteGround, check `REMOTE_ADDR` in a test request first.
- **MCP server.** Without this setting, every MCP submission comes from the MCP host's IP and shares its 5 per hour. Put the same random value in `wp-config.php` (`define('GOMO_MCP_SHARED_SECRET', '…');`) and in the MCP server's `GOMO_MCP_SHARED_SECRET` environment variable. The MCP server then passes each agent's real IP, signed with the secret.

Watch the ha@ inbox during the first weeks. If spam gets through, lower the limits or switch the endpoint off. The rest of the API is unaffected.

### 2. MCP server — dedicated Render custom domain

Release tag: `october-2026.2` in both GitHub repositories. The full-build repository contains this package's contents at its root; the MCP repository contains the contents of `gomo-mcp/` at its root.

- [ ] Render service source: `Development-gomo/Agentic-MCP-SME-GO-MO-Group`, branch `main`, root directory blank (repository root).
- [ ] Build with `npm ci && npm run build`; start with `npm start`. Render supplies `PORT`.
- [ ] Set `GOMO_API_BASE=https://www.gomogroup.com/agent/v1` (no trailing slash), `API_TIMEOUT_MS=10000`, and `GOMO_MCP_SHARED_SECRET` to the same secret configured in WordPress when enabling contact forwarding. Optional cache settings are `MCP_CACHE_REFRESH_MS` (default 300000), `MCP_CACHE_RETRY_MS` (60000), `MCP_CACHE_MAX_AGE_MS` (604800000; seven days), and `MCP_CACHE_VOLATILE_MAX_AGE_MS` (172800000; 48 hours for events and careers). Values must be positive integers in milliseconds.
- [ ] Add `agent-mcp.gomogroup.com` under Render's custom domains. Create a DNS CNAME named `agent-mcp` pointing to `gomo-mcp-layer.onrender.com`; verify the domain and TLS certificate in Render. Do not modify the existing `mcp.gomogroup.com` service or its DNS records.
- [ ] Test the default Render hostname first, then the custom hostname:

```bash
curl -fsS https://agent-mcp.gomogroup.com/health
curl -fsS https://agent-mcp.gomogroup.com/tools
```

The health response should identify `gomo-group`, version `1.3.0`, and report each read collection's cache status. `/ready` returns 200 only while every read collection has usable data; use it as the readiness probe. The tool directory should list 12 tools and advertise `https://agent-mcp.gomogroup.com/mcp`. MCP clients connect to that `/mcp` endpoint. `/mcp/health` and `/mcp/tools` remain supported aliases.

The nine `gomo-mcp/data/*.json` files are validated public seed snapshots generated from the plugin's bundled data. They ship with the server and make read tools work after a cold start even if SiteGround serves challenge HTML to Render. The server refreshes them from the live API in the background; it checks the response status, content type, size, timeout and collection completeness before replacing a last-known-good snapshot. Tool results include `_meta.freshness` with the source, generation/verification time and any stale-data warning. Ordinary collections stop serving after seven days without a successful refresh; events and careers stop after 48 hours. If the age limit is exceeded, reads return an error instead of presenting expired data as current. Rebuild and deploy after changing WordPress's bundled data so the cold-start snapshots are updated. The cache is process-local, so every Render instance/restart starts from those bundled snapshots.

Contact submissions are deliberately live-only: they need the WordPress endpoint to accept and confirm the lead. If the origin returns a challenge or a delivery response cannot be verified, the MCP reports that delivery is unconfirmed and does not claim cached success. Do not retry automatically; have the user verify through the configured contact channel.

If hosting yourself instead of using Render, route the entire dedicated hostname to the Node process:

```nginx
location / {
  proxy_pass http://localhost:3000;
  proxy_set_header Host $host;
  proxy_set_header X-Forwarded-For $remote_addr;   # the MCP server rate-limits and forwards per client IP
  proxy_buffering off;
}
```

**Existing WordPress snapshot migration:** back up the entire plugin directory and remove the active `snapshots/current.json` pointer during deployment so the updated bundled discovery files become the baseline. Then run Sync now to publish a new snapshot with the new MCP hostname. Otherwise a previously published snapshot will continue advertising the old endpoint. Purge external caches.

The `_source/live-v1.2-snapshot/` directory is historical crawl evidence, not deployable content; its original URLs are deliberately preserved.

### 3. robots.txt
- [ ] Replace the AI block with `robots-addition.txt`. The live file has two problems:
  - It says `Content-Signal: ai-train=no` while `ai.txt` says `Allow-Training: yes`. Pick one policy and set both files to match.
  - Its comment says the non-standard `LLM-*` lines are commented out, but they aren't.

## Regenerating

```bash
cd _source && python3 build.py
```

Edit `_source/curated/*.json` and re-run. Every output file is regenerated from the same data, so URLs and counts can't drift. The agent page preserves its original publication date and the actual source crawl date (`PAGE_PUBLISHED` and `SITE_CRAWLED` in `build.py`). Change `SITE_CRAWLED` only after a new crawl; rebuilding or syncing updates the page's last-updated date.
