# GO MO Group: Agentic Web Package v1.4.0

This release refreshes the live v1.3.0 package and extends it. It was built from a full Firecrawl crawl on 2026-11-17.

It covers **327 public URLs: 323 indexable pages plus 4 noindex team-profile URLs** (133 English, 194 Swedish), kept on request.

It adds an **Agentic Resource Discovery (ARD) manifest** at `/.well-known/ard.json`. All existing canonical URLs and API routes are unchanged, so existing integrations keep working.

See `CHANGELOG.md` for every page added, updated or removed, and `SITE-FINDINGS.md` for issues on the live website.

## What's in this folder

| File | Deploys to | Notes |
|---|---|---|
| `agent-page.html` | `/agent/` | 465 KB of semantic HTML with JSON-LD. Bilingual: 25 services, 6 solutions, 31 cases, 174 insights (57 EN, 117 SV), 34 people, and a 327-URL page index (noindex URLs are tagged). |
| `agent-index.json` | `/agent/index.json` | Single-file JSON summary. It keeps every v1.2 key, adds the ARD manifest under `machineReadable`, and includes 50 FAQs. |
| `llms.txt` | `/llms.txt` | 349 Markdown links, no bare URLs. Lists every EN and SV insight. |
| `ai.txt` | `/ai.txt` | Permissions, canonical sources (now including `ARD:`), language rules and the consent rule. |
| `api-catalog.json` | `/api-catalog.json` | APIs.json 0.16, including the ARD manifest entry. |
| `ard.json` | `/.well-known/ard.json` | **New.** ARD v0.91 manifest with two entries: the MCP server (inline server card, 12 tools) and the REST API (OpenAPI). Served by the plugin. |
| `robots-addition.txt` | paste into the live `robots.txt` | AI crawler allowlist, `Sitemap:`, the new `Agentmap:` directive, and `Content-Signal`. |
| `gomo-api/` | `wp-content/plugins/gomo-api/` | WordPress plugin v1.4.0. Serves `/agent/v1/*`, `/openapi.json`, `/.well-known/ard.json` and, optionally, `/agent/`, `/llms.txt`, `/ai.txt` and `/api-catalog.json`. |
| `gomo-mcp/` | Render service at `https://agent-mcp.gomogroup.com/mcp` | MCP server v1.4.0 with 12 tools. Bundled read snapshots keep reads working when the WordPress origin is blocked. |
| `tests/`, `run-tests.sh` | not deployed | Plugin router and contact, sync merge, snapshot publication, generated outputs (including ARD conformance) and MCP protocol. Run `./run-tests.sh` before every deploy. |
| `_source/` | not deployed | Crawl data (`pages.json`, `md/`), curated JSON, `build.py`, `build_dataset.py` and `build_insights.py`, which regenerate every file above. |

### New in v1.4
- **`/.well-known/ard.json`** is an Agentic Resource Discovery manifest that Google Lighthouse checks in its Agentic Browsing audits. The site advertises it in four ways:
  - an `Agentmap:` line in `robots.txt`;
  - a `<link rel="ard">` tag on every page;
  - an HTTP `Link: <https://www.gomogroup.com/.well-known/ard.json>; rel="ard"` header;
  - the well-known path itself.
- **Content refresh:**
  - 14 new pages, including ChatGPT Ads (EN + SV), a new agentic web case and 8 Swedish insights.
  - 1 removed Swedish case.
  - Rewritten GEO, Agentic Web and SEO automation content.
  - Team changes.
- **Language pairs follow the site's hreflang tags.** WPML now outputs them on 324 pages.
- **Insights carry an `author`** where the page marks one up.

## Deployment checklist

### 0. Before you start
- [ ] **Confirm the founding year.** The package publishes 2013. The site's own structured data says 2014, and one profile says 2012 (see `SITE-FINDINGS.md`). If 2013 is wrong, change `founded` in `_source/curated/company.json`, run `python3 build.py` and `./run-tests.sh`, and deploy the regenerated files.
- [ ] Run `./run-tests.sh` (after `npm ci` in `gomo-mcp/`). Every suite must pass.

### 1. WordPress plugin
- [ ] Back up the entire current `wp-content/plugins/gomo-api/` folder, including `snapshots/`. Then replace it with this `gomo-api/` folder.
- [ ] **Remove the active snapshot pointer `gomo-api/snapshots/current.json`** so the new bundled files become the baseline. Otherwise the v1.3.0 snapshot stays active and `/.well-known/ard.json` returns 404.
- [ ] Make sure nothing physical sits at the plugin's URLs. Physical files take precedence over rewrite rules. Check for:
  - `/agent/index.json`;
  - root copies of `llms.txt`, `ai.txt`, `api-catalog.json` and `openapi.json`;
  - any physical `/agent/index.html`;
  - any `/.well-known/ard.json`.
- [ ] The plugin serves `/agent/` from `agent-page.html` by default. To keep a WordPress page at `/agent/` instead, untick the setting under **Tools → GO MO Agent API**.
- [ ] **Settings → Permalinks → Save.** Without this, `/.well-known/ard.json` and every other plugin route returns 404.
- [ ] **Tools → GO MO Agent API → Sync now.** Check the report for "NEEDS CURATION", and confirm it ends with **Published complete snapshot**. A `FAILED` report means the previous publication is still active; fix the reported error before retrying.
- [ ] Exclude the machine-readable routes from host page caching (including `/.well-known/ard.json`), then purge CDN and page caches. The plugin sends `max-age=0, must-revalidate`.
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
curl -s https://www.gomogroup.com/.well-known/ard.json | python3 -m json.tool | head -20
```

```bash
curl -sI https://www.gomogroup.com/en/ | grep -i '^link:'
```

```bash
curl -X POST https://www.gomogroup.com/agent/v1/contact.json -H "Content-Type: application/json" -d '{"agent_context":{"user_authorized_submission":true},"person":{"name":"Test","email":"test@example.com"},"company":{"name":"Test Co"},"inquiry":{"service_interest":"GEO","project_summary":"Deployment test submission"}}'
```

The test lead goes to **ha@gomogroup.com**, with Reply-To set to the submitter. Only send it with the inbox owner's approval, and confirm that it arrives. Then send the identical request again: it must return the same `lead_id` and send no second email. That confirms the database locking and duplicate suppression work on this host.

- [ ] Confirm snapshot publication on this host. After **Sync now**, `gomo-api/snapshots/` must contain a new generation folder and `snapshots/current.json` must name it. If the report says `FAILED`, check that the plugin folder is writable and that all PHP workers share the same filesystem.

- [ ] Run PageSpeed Insights on `https://www.gomogroup.com/en/`. The **Agentic Browsing** category should list the ARD manifest as found and passing.

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

Quota and duplicate reservations are stored in one non-autoloaded WordPress options row, protected by a MySQL/MariaDB advisory lock.
- The database must support `GET_LOCK` / `RELEASE_LOCK` on the same primary connection as the options reads and writes. Don't route these queries through separate replicas or transaction-pooling connections.
- If locking or persistence fails, the endpoint returns 503 without sending mail.

Quota is reserved **before** mail delivery.
- Known failed deliveries return 502 and can be retried, but they still use up attempt quota.
- An identical inquiry that is already in progress returns 409.
- If the delivery outcome or reservation completion is uncertain, the reservation stays pending for up to 24 hours to avoid sending twice, and the response tells the caller to email instead.

This prevents duplicates; it is not a durable mail queue.

Two settings matter for the per-client limit:
- **Behind a proxy or CDN.** If `REMOTE_ADDR` holds the proxy's address rather than the visitor's, every request shares one bucket. In that case set `define('GOMO_TRUSTED_PROXY_HEADER', 'HTTP_X_FORWARDED_FOR');`, but only if your proxy overwrites that header. On SiteGround, check `REMOTE_ADDR` in a test request first.
- **MCP server.** Without this setting, every MCP submission comes from the MCP host's IP and shares its 5 per hour. Put the same random value in `wp-config.php` (`define('GOMO_MCP_SHARED_SECRET', '…');`) and in the MCP server's `GOMO_MCP_SHARED_SECRET` environment variable. The MCP server then passes on each agent's real IP, signed with the secret.

Watch the ha@ inbox during the first weeks. If spam gets through, lower the limits or switch the endpoint off; the rest of the API is unaffected.

### Snapshot storage and recovery

Sync needs the plugin directory to be writable by the WordPress PHP process. The filesystem must support advisory `flock` and atomic rename within the same directory, and all WordPress workers must share it.

How publication works:
- Sync checks that the required post types exist.
- It prepares every API and discovery file (including `ard.json`) in `gomo-api/snapshots/<generation>/`.
- It then atomically replaces `snapshots/current.json`.
- Each request uses one generation, so it never mixes files from two publications.
- Bundled files stay as the initial baseline and are not overwritten. Earlier generations are kept for rollback and in-flight readers. Remove older, unreferenced generations during scheduled maintenance.

An unexpectedly empty result for a feed that previously had items aborts the entire sync. To remove the last item on purpose (for example, the last open job), temporarily allow only that endpoint:

```php
add_filter('gomo_api_allow_empty_sync', static function ($allow, $endpoint) {
    return $endpoint === 'careers';
}, 10, 2);
```

Remove the filter after that sync. Missing post types and query failures cannot be overridden by this filter.

To roll back:
- Atomically replace `snapshots/current.json` with a pointer to a previous generation that you have checked.
- To return to the bundled baseline, remove the pointer during a maintenance window.
- Purge external caches after either action.

Updating the bundled files alone does not change a snapshot that is already active.

### 2. MCP server: dedicated Render custom domain

The full-build repository holds this package's contents at its root. The MCP repository holds the contents of `gomo-mcp/` at its root. Tag the release `november-2026.1` in both when you push it.

- [ ] Render service source: `Development-gomo/Agentic-MCP-SME-GO-MO-Group`, branch `main`, root directory blank (repository root).
- [ ] Build with `npm ci && npm run build`; start with `npm start`. Render supplies `PORT`.
- [ ] Environment variables:
  - `GOMO_API_BASE=https://www.gomogroup.com/agent/v1` (no trailing slash).
  - **`TRUST_PROXY=1`** (new in v1.4). Render's load balancer is one proxy hop. Without this setting, every client shares one MCP rate-limit bucket, and WordPress receives the proxy's address instead of the agent's. Never set it to `true`; the server refuses to start with that value because it lets clients forge their address.
  - `API_TIMEOUT_MS=10000`.
  - `GOMO_MCP_SHARED_SECRET`, set to the same secret configured in WordPress.
  - Optional cache settings: `MCP_CACHE_REFRESH_MS` (300000), `MCP_CACHE_RETRY_MS` (60000), `MCP_CACHE_MAX_AGE_MS` (604800000) and `MCP_CACHE_VOLATILE_MAX_AGE_MS` (172800000).
- [ ] `agent-mcp.gomogroup.com` stays on its existing CNAME to `gomo-mcp-layer.onrender.com`. Do not modify the separate `mcp.gomogroup.com` service or its DNS records.
- [ ] Verify:

```bash
curl -fsS https://agent-mcp.gomogroup.com/health
```

```bash
curl -fsS https://agent-mcp.gomogroup.com/tools
```

- `/health` should identify `gomo-group`, version `1.4.0`, and report each read collection's cache status.
- `/ready` returns 200 only while every read collection has usable data; use it as the readiness probe. Since v1.4 the server refreshes every collection at startup and keeps them fresh in the background, whether or not tools are being called: healthy collections every 5 minutes, failed ones retried every minute. A failing `/ready` also triggers a (throttled) refresh. An instance that starts with expired bundled data becomes ready by itself once WordPress answers.
- [ ] In `/health`, check that each collection reports `"source": "upstream"` within a few minutes of deploy. If they stay `"bundled"` with an `upstream_error` that mentions HTML or a challenge, SiteGround is blocking Render. Allowlist the `GomoGroup-Agent/1.0` user agent or Render's outbound IPs in SiteGround's bot protection.
- [ ] Check proxy handling. Run this twice from one network and once from another (for example, office and a mobile hotspot):

```bash
curl -s -o /dev/null -D - -X POST https://agent-mcp.gomogroup.com/mcp -H 'Content-Type: application/json' -H 'Accept: application/json, text/event-stream' -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | grep -i ratelimit-remaining
```

  On the first network, `RateLimit-Remaining` should fall from 99 to 98. On the second network it must start again at 99. If it continues from 98, all clients share one bucket: check `TRUST_PROXY`.
- `/tools` should list 12 tools and advertise `https://agent-mcp.gomogroup.com/mcp`.

**Bundled data.** The nine `gomo-mcp/data/*.json` files are public seed snapshots generated from the plugin's bundled data. They let read tools work after a cold start even if SiteGround serves challenge HTML to Render.
- The server refreshes them from the live API in the background (at startup, then every `MCP_CACHE_RETRY_MS` / `MCP_CACHE_REFRESH_MS`). It checks status, content type, size, timeout and completeness before replacing a last-known-good snapshot.
- Tool results include `_meta.freshness`.
- Ordinary collections stop serving after seven days without a successful refresh; events and careers stop after 48 hours.
- Rebuild and deploy whenever WordPress's bundled data changes.

**Contact submissions** are live-only. If the origin returns a challenge or delivery can't be confirmed, the MCP reports that delivery is unconfirmed and does not claim success.

If you host the server yourself instead of on Render, route the entire dedicated hostname to the Node process:

```nginx
location / {
  proxy_pass http://localhost:3000;
  proxy_set_header Host $host;
  proxy_set_header X-Forwarded-For $remote_addr;   # the MCP server rate-limits and forwards per client IP
  proxy_buffering off;
}
```

### 3. robots.txt
- [ ] Replace the AI block in the live `robots.txt` with `robots-addition.txt`. Compared with v1.3, it adds `Agentmap: https://www.gomogroup.com/.well-known/ard.json`, which Lighthouse uses to find the ARD manifest.
- [ ] The live file already matches `ai.txt` on training (`Content-Signal: ai-train=yes` and `Allow-Training: yes`). Keep the two in agreement if the policy changes.

## Regenerating

```bash
cd _source && python3 build.py
```

Edit `_source/curated/*.json` and run it again. Every output file is regenerated from the same data, so URLs and counts can't drift.

`PAGE_PUBLISHED` (2026-05-26) and `SITE_CRAWLED` (2026-11-17) in `build.py` record provenance. Change `SITE_CRAWLED` only after a new crawl.

After a new crawl, follow these steps:
1. Save the crawl records.
2. Run `python3 build_dataset.py <crawl-dir>`. It rebuilds `pages.json` and `md/` from the crawl.
3. Run `python3 build_insights.py <crawl-dir>` (it needs `<crawl-dir>/html/`).
4. Update the curated files and run `build.py`.
5. Run `./run-tests.sh`.

`_source/live-v1.3-snapshot/` holds the discovery files that were live before this release. It is reference only and is not deployed.
