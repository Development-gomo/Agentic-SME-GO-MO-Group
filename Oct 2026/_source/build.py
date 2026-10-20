"""
GO MO Group — Agentic Web Package v1.3 generator.

Reads the curated dataset in _source/curated/ (+ pages.json) and writes every deliverable,
so URLs, counts and facts stay identical across all files:

  ../agent-page.html           /agent/
  ../llms.txt                  /llms.txt
  ../ai.txt                    /ai.txt
  ../api-catalog.json          /api-catalog.json
  ../robots-addition.txt       lines for the live robots.txt
  ../gomo-api/openapi.json     /openapi.json
  ../gomo-api/data/*.json      /agent/v1/*.json
  ../gomo-api/{agent-page.html,llms.txt,ai.txt,api-catalog.json}  (copies the plugin can serve)

Run:  python3 build.py
"""
import json, shutil, html, re
from collections import Counter, OrderedDict
from datetime import date
from pathlib import Path

HERE = Path(__file__).resolve().parent
OUT = HERE.parent
API_DIR = OUT / "gomo-api"
CUR = HERE / "curated"

VERSION = "1.3.0"
TODAY = date.today().isoformat()
SITE = "https://www.gomogroup.com"
API = f"{SITE}/agent/v1"
URLS = OrderedDict([
    ("agent_page", f"{SITE}/agent/"),
    ("agent_json", f"{SITE}/agent/index.json"),
    ("llms_txt", f"{SITE}/llms.txt"),
    ("ai_txt", f"{SITE}/ai.txt"),
    ("api_index", f"{API}/index.json"),
    ("openapi", f"{SITE}/openapi.json"),
    ("api_catalog", f"{SITE}/api-catalog.json"),
    ("mcp", f"{SITE}/mcp"),
    ("sitemap", f"{SITE}/sitemap_index.xml"),
])
LICENSE = "Content © GO MO Group AB. Cite gomogroup.com as the source; do not alter or fabricate figures."

def load(name):
    return json.load(open(CUR / name, encoding="utf-8"))

C = load("company.json")
COMPANY, OFFICES, TEAM = C["company"], C["offices"], C["team"]
EVENTS, CAREERS = C.get("events", []), C.get("careers", [])
FAQ, TESTIMONIALS = C.get("faq", []), C.get("testimonials", [])
SERVICES = load("services.json")
SOLUTIONS = load("solutions.json")
CASES = load("case-studies.json")
INSIGHTS = load("insights.json")
PAGES = json.load(open(HERE / "pages.json", encoding="utf-8"))

SERVICE_NAME = {s["id"]: s["name"] for s in SERVICES}
CORE_SERVICES = [s for s in SERVICES if s["category"] != "local"]
LOCAL_SERVICES = [s for s in SERVICES if s["category"] == "local"]
LEADERS = [p for p in TEAM if p.get("leadership")]
EN_INSIGHTS = [i for i in INSIGHTS if i["lang"] == "en"]
SV_INSIGHTS = [i for i in INSIGHTS if i["lang"] == "sv"]
for _c in CASES:  # normalise optional fields used in labels
    _c["client"] = _c.get("client") or "Unnamed client"
    _c["headline"] = _c.get("headline") or _c.get("headline_sv") or _c["id"]
    _c["results"] = _c.get("results") or []
for _x in SERVICES + SOLUTIONS:
    _x.setdefault("pricing", {"model": "quote-based", "currency": None, "tiers": []})
REAL_CASES = [c for c in CASES if c.get("kind", "case-study") == "case-study"]

def e(s):
    return html.escape(str(s if s is not None else ""), quote=True)

def langs(item):
    return item.get("languages") or [l for l, k in (("en", "url"), ("sv", "url_sv")) if item.get(k)]

def primary_url(item):
    return item.get("url") or item.get("url_sv")

for _ev in EVENTS:  # status is relative to the build date, not the extraction date
    if _ev.get("startDate"):
        _ev["status"] = "upcoming" if (_ev.get("endDate") or _ev["startDate"])[:10] >= TODAY else "past"

def sorted_events():
    return sorted(EVENTS, key=lambda x: x.get("startDate") or "", reverse=True)

# ──────────────────────────────────────────────────────────────────────────────
# 1. API data files
# ──────────────────────────────────────────────────────────────────────────────

def envelope(endpoint, data):
    env = OrderedDict(status="ok", data=data)
    if isinstance(data, list):
        env["pagination"] = {"total": len(data), "page": 1, "per_page": len(data), "pages": 1}
    env["_meta"] = OrderedDict(
        source=f"{API}/{endpoint}.json", endpoint=endpoint, canonical=SITE, api=API,
        generated=f"{TODAY}T00:00:00+00:00", version=VERSION, languages=["en", "sv"], license=LICENSE,
    )
    return env

def page_index():
    rows = []
    for p in PAGES:
        rows.append(OrderedDict(url=p["url"], lang=p["lang"], type=p["type"], title=p["title"],
                                alternate=(p["alternate"] or {}).get("url"), dateModified=p["modified"],
                                **({"robots": p["robots"]} if p.get("robots") else {})))
    slash = lambda u: u if not u or u.endswith("/") else u + "/"
    homes = {SITE + "/", SITE + "/en/"}
    for r in rows:
        r["alternate"] = slash(r["alternate"])
        # WPML's switcher falls back to the other homepage when a page has no translation; that isn't an alternate.
        if r["alternate"] in homes and r["url"] not in homes:
            r["alternate"] = None
    by = {r["url"]: r for r in rows}
    for r in rows:  # symmetric hreflang pairs
        if r["alternate"] in by and not by[r["alternate"]]["alternate"]:
            by[r["alternate"]]["alternate"] = r["url"]
    return rows

def company_payload():
    c = OrderedDict(COMPANY)
    c["url"] = SITE + "/"
    c["languages"] = ["en", "sv"]
    c["counts"] = {"services": len(SERVICES), "case_studies": len(CASES), "insights": len(INSIGHTS),
                   "insights_en": len(EN_INSIGHTS), "insights_sv": len(SV_INSIGHTS), "team_profiles": len(TEAM),
                   "offices": len(OFFICES), "public_urls": len(PAGES)}
    c["machine_readable"] = dict(URLS)
    return c

def write_data():
    d = API_DIR / "data"
    d.mkdir(parents=True, exist_ok=True)
    files = {
        "company": company_payload(),
        "services": SERVICES,
        "case-studies": CASES,
        "team": TEAM,
        "offices": OFFICES,
        "insights": INSIGHTS,
        "events": sorted_events(),
        "careers": CAREERS,
        "pages": page_index(),
    }
    for name, data in files.items():
        (d / f"{name}.json").write_text(json.dumps(envelope(name, data), ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    return {k: (len(v) if isinstance(v, list) else 1) for k, v in files.items()}

# ──────────────────────────────────────────────────────────────────────────────
# 2. OpenAPI 3.0.3 (true JSON)
# ──────────────────────────────────────────────────────────────────────────────

def openapi():
    ref = lambda n: {"$ref": f"#/components/schemas/{n}"}
    s = lambda **kw: dict(type="string", **kw)
    arr = lambda items: {"type": "array", "items": items}
    nstr = {"type": "string", "nullable": True}
    lang_items = {"type": "array", "items": {"type": "string", "enum": ["en", "sv"]}}

    def list_resp(item):
        return {"type": "object", "required": ["status", "data", "_meta"], "properties": {
            "status": s(example="ok"), "data": arr(ref(item)), "pagination": ref("Pagination"), "_meta": ref("Meta")}}

    def single_resp(item):
        return {"type": "object", "required": ["status", "data", "_meta"], "properties": {
            "status": s(example="ok"), "data": ref(item), "_meta": ref("Meta")}}

    schemas = OrderedDict()
    schemas["Meta"] = {"type": "object", "properties": {
        "source": s(format="uri"), "endpoint": s(), "canonical": s(format="uri"), "api": s(format="uri"),
        "generated": s(format="date-time"), "version": s(example=VERSION), "languages": lang_items, "license": s(),
        "filters": {"type": "object", "additionalProperties": {"type": "string"}}}}
    schemas["Pagination"] = {"type": "object", "properties": {k: {"type": "integer"} for k in ("total", "page", "per_page", "pages")}}
    schemas["Error"] = {"type": "object", "required": ["status", "error", "message"], "properties": {
        "status": s(example="error"), "error": s(), "message": s()}}
    schemas["PricingTier"] = {"type": "object", "properties": {"name": s(), "price_from": {"type": "number", "nullable": True}, "includes": s()}}
    schemas["Pricing"] = {"type": "object", "properties": {"model": s(enum=["published", "quote-based"]), "currency": nstr, "tiers": arr(ref("PricingTier"))}}
    schemas["Result"] = {"type": "object", "properties": {"metric": s(), "value": s(), "context": nstr, "period": nstr}}
    schemas["Service"] = {"type": "object", "required": ["id", "name", "category", "summary", "languages"], "properties": {
        "id": s(example="geo"), "name": s(), "name_sv": nstr, "category": s(enum=["search", "ai", "paid", "content", "outbound", "web", "strategy", "local"]),
        "tags": arr(s()), "summary": s(), "summary_sv": nstr, "key_points": arr(s()), "pricing": ref("Pricing"),
        "segments": arr(s(enum=["enterprise", "saas-tech", "sme"])), "related_cases": arr(s()),
        "url": {"type": "string", "format": "uri", "nullable": True}, "url_sv": {"type": "string", "format": "uri", "nullable": True},
        "languages": lang_items, "aliases": arr(s()), "dateModified": s(format="date")}}
    schemas["CaseStudy"] = {"type": "object", "required": ["id", "client", "headline", "languages"], "properties": {
        "id": s(), "aliases": arr(s()), "client": s(), "headline": s(), "headline_sv": nstr, "industry": s(),
        "segment": {"type": "string", "nullable": True, "enum": ["enterprise", "saas-tech", "sme", None]}, "services": arr(s()),
        "challenge": s(), "solution": s(), "summary_sv": nstr, "results": arr(ref("Result")),
        "quote": {"type": "object", "nullable": True, "properties": {"text": s(), "author": s(), "role": s()}},
        "url": {"type": "string", "format": "uri", "nullable": True}, "url_sv": {"type": "string", "format": "uri", "nullable": True},
        "languages": lang_items, "datePublished": s(format="date"), "dateModified": s(format="date"),
        "kind": s(enum=["case-study", "showcase", "interview"])}}
    schemas["TeamMember"] = {"type": "object", "required": ["id", "name"], "properties": {
        "id": s(), "name": s(), "role": s(), "role_sv": nstr, "bio": s(), "email": nstr, "telephone": nstr,
        "linkedin": nstr, "location": nstr, "url": nstr, "url_sv": nstr, "languages": lang_items, "leadership": {"type": "boolean"}}}
    schemas["Office"] = {"type": "object", "required": ["id", "city", "country"], "properties": {
        k: (nstr if k in ("addressRegion", "email", "telephone") else s()) for k in
        ("id", "label", "city", "country", "countryCode", "streetAddress", "postalCode", "addressRegion", "email", "telephone", "timezone")}}
    schemas["Insight"] = {"type": "object", "required": ["id", "lang", "title", "url"], "properties": {
        "id": s(), "lang": s(enum=["en", "sv"]), "title": s(), "seo_title": s(), "summary": s(), "url": s(format="uri"),
        "alternate": {"type": "string", "format": "uri", "nullable": True, "description": "Same article in the other language"},
        "datePublished": s(format="date"), "dateModified": s(format="date"), "readingMinutes": {"type": "integer", "nullable": True},
        "topics": arr(s(enum=["geo", "ai", "seo", "paid", "b2b-marketing", "content", "leadership", "company", "careers"]))}}
    schemas["Event"] = {"type": "object", "required": ["id", "title"], "properties": {
        "id": s(), "title": s(), "title_sv": nstr, "startDate": s(), "endDate": nstr, "location": nstr, "format": nstr,
        "language": nstr, "description": s(), "status": s(enum=["past", "upcoming"]), "url": nstr, "url_sv": nstr, "registration_url": nstr}}
    schemas["Career"] = {"type": "object", "required": ["id", "title"], "properties": {
        "id": s(), "title": s(), "title_sv": nstr, "location": nstr, "employmentType": nstr, "summary": s(), "url": nstr, "url_sv": nstr, "apply_url": nstr}}
    schemas["PageEntry"] = {"type": "object", "required": ["url", "lang", "type"], "properties": {
        "url": s(format="uri"), "lang": s(enum=["en", "sv"]), "type": s(enum=["page", "service", "solution", "case", "insight", "team", "career", "event", "legal"]),
        "title": s(), "alternate": {"type": "string", "format": "uri", "nullable": True}, "dateModified": {"type": "string", "nullable": True},
        "robots": s(description="Present only when the page is set to noindex on the site")}}
    schemas["Company"] = {"type": "object", "properties": {
        "name": s(), "legalName": s(), "founded": s(), "description": s(), "description_sv": s(), "url": s(format="uri"),
        "employees": s(), "headquarters": {}, "certifications": arr(s()), "partners": arr(s()), "contact": {"type": "object"},
        "social": {"type": "object"}, "key_pages": {"type": "object"}, "counts": {"type": "object"}, "machine_readable": {"type": "object"}}}
    schemas["IndexResponse"] = {"type": "object", "properties": {
        "status": s(), "name": s(), "version": s(), "description": s(), "languages": lang_items,
        "endpoints": arr({"type": "object", "properties": {"route": s(), "method": s(), "url": s(), "description": s()}}),
        "openapi": s(format="uri"), "discovery": {"type": "object"}, "_meta": ref("Meta")}}
    schemas["SchemaResponse"] = {"type": "object", "properties": {"status": s(), "schema": {"type": "object"}, "_meta": ref("Meta")}}
    schemas["CompanyResponse"] = single_resp("Company")
    for name, item in (("Services", "Service"), ("CaseStudies", "CaseStudy"), ("Team", "TeamMember"), ("Offices", "Office"),
                       ("Insights", "Insight"), ("Events", "Event"), ("Careers", "Career"), ("Pages", "PageEntry")):
        schemas[f"{name}Response"] = list_resp(item)
    schemas["SingleServiceResponse"] = single_resp("Service")
    schemas["SingleCaseStudyResponse"] = single_resp("CaseStudy")
    schemas["SingleInsightResponse"] = single_resp("Insight")
    schemas["ContactPerson"] = {"type": "object", "required": ["name", "email"], "properties": {
        "name": s(minLength=2, maxLength=120), "email": s(format="email", maxLength=254), "phone": s(maxLength=40), "job_title": s(maxLength=120)}}
    schemas["ContactCompany"] = {"type": "object", "required": ["name"], "properties": {
        "name": s(minLength=1, maxLength=160), "website": s(format="uri", maxLength=2048), "country": s(maxLength=80), "size": s(maxLength=40)}}
    schemas["ContactInquiry"] = {"type": "object", "required": ["service_interest", "project_summary"], "properties": {
        "service_interest": s(minLength=2, maxLength=120, example="GEO"), "project_summary": s(minLength=10, maxLength=4000),
        "budget_range": s(maxLength=80), "timeline": s(maxLength=80), "preferred_language": s(enum=["en", "sv"])}}
    schemas["AgentContext"] = {"type": "object", "required": ["user_authorized_submission"], "properties": {
        "user_authorized_submission": {"type": "boolean", "enum": [True], "description": "Must be true: confirms the human user explicitly asked for and approved this submission."},
        "agent_name": s(maxLength=80), "conversation_summary": s(maxLength=2000)}}
    schemas["ContactRequest"] = {"type": "object", "required": ["person", "company", "inquiry", "agent_context"], "properties": {
        "person": ref("ContactPerson"), "company": ref("ContactCompany"), "inquiry": ref("ContactInquiry"), "agent_context": ref("AgentContext")}}
    schemas["ContactResponse"] = {"type": "object", "properties": {
        "status": s(example="received"), "message": s(), "lead_id": s(example="gomo-lead-2026-A3F7B2C1"), "next_step": s()}}
    schemas["ContactErrorResponse"] = {"allOf": [ref("Error"), {"type": "object", "properties": {"fields": arr(s())}}]}

    P = lambda name, desc, enum=None, fmt=None, typ="string": {"name": name, "in": "query", "required": False, "description": desc,
                                                              "schema": dict({"type": typ}, **({"enum": enum} if enum else {}), **({"format": fmt} if fmt else {}))}
    LANG = P("lang", "Language filter (default all).", ["en", "sv", "all"])
    PAGE, PER = P("page", "Page number", typ="integer"), P("per_page", "Items per page (max 200)", typ="integer")
    IDP = {"name": "id", "in": "path", "required": True, "schema": {"type": "string", "pattern": "^[a-z0-9-]+$"}}
    ok = lambda sch, d="OK": {"200": {"description": d, "content": {"application/json": {"schema": ref(sch)}}}}
    nf = {"404": {"description": "Not found", "content": {"application/json": {"schema": ref("Error")}}}}
    get = lambda op, summ, sch, tag, params=(), extra=None: {"get": dict(operationId=op, summary=summ, tags=[tag], parameters=list(params), responses=dict(ok(sch), **(extra or {})))}

    paths = OrderedDict()
    paths["/index.json"] = get("getIndex", "API index and discovery links", "IndexResponse", "Discovery")
    paths["/schema.json"] = get("getSchema", "Filter definitions per endpoint", "SchemaResponse", "Discovery")
    paths["/company.json"] = get("getCompany", "Company profile (EN + SV)", "CompanyResponse", "Company")
    paths["/services.json"] = get("listServices", "All services", "ServicesResponse", "Services", [
        P("category", "Service category", ["search", "ai", "paid", "content", "outbound", "web", "strategy", "local"]), P("tag", "Tag"), LANG])
    paths["/services/{id}.json"] = get("getService", "One service by ID or alias", "SingleServiceResponse", "Services", [IDP], nf)
    paths["/case-studies.json"] = get("listCaseStudies", "Case studies with measured results", "CaseStudiesResponse", "Case studies", [
        P("industry", "Industry (substring)"), P("segment", "Segment", ["enterprise", "saas-tech", "sme"]), P("service", "Service ID"), LANG])
    paths["/case-studies/{id}.json"] = get("getCaseStudy", "One case study by ID or alias", "SingleCaseStudyResponse", "Case studies", [IDP], nf)
    paths["/team.json"] = get("listTeam", "Team and leadership", "TeamResponse", "Company")
    paths["/offices.json"] = get("listOffices", "Office locations", "OfficesResponse", "Company")
    paths["/insights.json"] = get("listInsights", "Insight/blog articles (EN + SV)", "InsightsResponse", "Insights", [
        LANG, P("topic", "Topic", ["geo", "ai", "seo", "paid", "b2b-marketing", "content", "leadership", "company", "careers"]),
        P("since", "Published on/after (YYYY-MM-DD)", fmt="date"), PAGE, PER])
    paths["/insights/{id}.json"] = get("getInsight", "One insight by ID (Swedish IDs are prefixed sv-)", "SingleInsightResponse", "Insights", [IDP], nf)
    paths["/events.json"] = get("listEvents", "Seminars and events", "EventsResponse", "Company", [LANG])
    paths["/careers.json"] = get("listCareers", "Open roles", "CareersResponse", "Company", [LANG])
    paths["/pages.json"] = get("listPages", "Every public URL with language and EN/SV alternate", "PagesResponse", "Discovery", [
        LANG, P("type", "Page type", ["page", "service", "solution", "case", "insight", "team", "career", "event", "legal"]), PAGE, PER])
    paths["/contact.json"] = {"post": {
        "operationId": "submitContactInquiry", "tags": ["Actions"],
        "summary": "Submit a qualified B2B inquiry (consent-gated)",
        "description": "Delivers an inquiry to GO MO Group's commercial team. Agents must only call this after the human user explicitly asked to contact GO MO Group and approved the details; agent_context.user_authorized_submission must be true or the request is rejected with 403. Maximum body size 32768 bytes. Defaults: 5 attempts per client per hour and 20 site-wide; failed delivery attempts consume quota. Identical inquiries are reserved for 24 hours; an uncertain delivery remains pending to prevent duplicate mail.",
        "requestBody": {"required": True, "content": {"application/json": {"schema": ref("ContactRequest")}}},
        "responses": {
            "200": {"description": "Duplicate inquiry already received", "content": {"application/json": {"schema": ref("ContactResponse")}}},
            "201": {"description": "Inquiry delivered", "content": {"application/json": {"schema": ref("ContactResponse")}}},
            **{c: {"description": d, "content": {"application/json": {"schema": ref("ContactErrorResponse")}}} for c, d in
               (("400", "Invalid JSON"), ("403", "Consent flag missing or not true"), ("409", "An identical submission is already being processed; do not resubmit."), ("413", "Request body exceeds 32768 bytes."), ("422", "Validation failed"), ("429", "Rate limited"), ("502", "Delivery failed"), ("503", "Contact disabled, reservation unavailable, or delivery outcome uncertain."))}}}}

    return OrderedDict(
        openapi="3.0.3",
        info={"title": "GO MO Group Public API", "version": VERSION,
              "description": "Public, read-only JSON API for GO MO Group (gomogroup.com) — the B2B digital marketing, GEO and Generative AI agency — plus one consent-gated contact action. Bilingual (English + Swedish). No authentication. Companion resources: agent page " + URLS["agent_page"] + ", llms.txt " + URLS["llms_txt"] + ", MCP server " + URLS["mcp"] + ".",
              "contact": {"name": "GO MO Group", "email": COMPANY["contact"]["general_email"], "url": SITE + "/en/contact-us/"},
              "license": {"name": "Content © GO MO Group AB — citation required"}},
        servers=[{"url": API, "description": "Production"}],
        tags=[{"name": t} for t in ("Discovery", "Company", "Services", "Case studies", "Insights", "Actions")],
        paths=paths, components={"schemas": schemas},
    )

# ──────────────────────────────────────────────────────────────────────────────
# 3. llms.txt (Markdown links everywhere a page is listed)
# ──────────────────────────────────────────────────────────────────────────────

def link(text, url):
    return f"[{text}]({url})"

def result_line(c):
    rs = "; ".join(f"{r['value']} {r['metric']}" + (f" ({r['context']})" if r.get("context") else "") for r in c["results"][:3])
    return rs

def llms_txt():
    co = COMPANY
    L = [f"# {co['name']}", f"> {co['tagline']}", "", co["description"], ""]
    if co.get("description_sv"):
        L += [f"På svenska: {co['description_sv']}", ""]
    L += ["## Summary", "",
          f"- Legal name: {co['legalName']}; founded {co['founded']}",
          f"- Headquarters: {co['headquarters'] if isinstance(co['headquarters'], str) else OFFICES[0]['city'] + ', ' + OFFICES[0]['country']}",
          "- Offices: " + "; ".join(f"{o['city']} ({o['country']})" for o in OFFICES),
          f"- Size: {co.get('employees')} colleagues" + (f"; {co['specialists']}" if co.get("specialists") else "") + (f"; {co['projects']}" if co.get("projects") else ""),
          ]
    if co.get("parent"): L.append(f"- Group affiliation: {co['parent']}")
    if co.get("certifications"): L.append("- Certifications & partnerships: " + ", ".join(co["certifications"] + [p for p in co.get("partners", []) if p not in co["certifications"]]))
    if co.get("clients_named"): L.append("- Named clients: " + ", ".join(co["clients_named"]))
    L.append(f"- Languages: English ({SITE}/en/) and Swedish ({SITE}/) — every listing below marks Swedish alternates with (sv)")
    L.append(f"- General contact: {co['contact']['general_email']}")
    L += ["", "## Services", ""]
    for s in CORE_SERVICES:
        price = ""
        tiers = [t for t in s["pricing"].get("tiers", []) if t.get("price_from")]
        if tiers:
            price = " Pricing: " + "; ".join(f"{t['name']} from {t['price_from']:,} {s['pricing'].get('currency') or ''}".strip() for t in tiers) + "."
        sv = f" (sv: {link(s['name_sv'] or s['name'], s['url_sv'])})" if s.get("url_sv") and s.get("url") else ""
        L.append(f"- {link(s['name'], primary_url(s))}{sv}: {s['summary']}{price}")
    if LOCAL_SERVICES:
        L += ["", "### Local service pages (Swedish)", ""]
        L += [f"- {link(s['name_sv'] or s['name'], s['url_sv'])}: {s['summary']}" for s in LOCAL_SERVICES]
    L += ["", "## Solutions", ""]
    for s in SOLUTIONS:
        sv = f" (sv: {link(s.get('name_sv') or s['name'], s['url_sv'])})" if s.get("url_sv") and s.get("url") else ""
        L.append(f"- {link(s['name'], primary_url(s))}{sv}: {s['summary']}")
    L += ["", "## Key Results", ""]
    for c in REAL_CASES:
        if c["results"]:
            L.append(f"- {link(c['client'] + ' — ' + c['headline'], primary_url(c))}: {result_line(c)}")
    L += ["", "## Leadership", ""]
    for p in LEADERS:
        bits = [x for x in (p.get("email"), p.get("telephone")) if x]
        L.append(f"- {link(p['name'], primary_url(p))} — {p['role']}" + (" | " + " | ".join(bits) if bits else ""))
    L += ["", "## Offices", ""]
    for o in OFFICES:
        L.append(f"- {o['city']}, {o['country']} ({o['label']}): {o['streetAddress']}, {o['postalCode']} {o['city']}" + (f" | {o['email']}" if o.get("email") else "") + (f" | {o['telephone']}" if o.get("telephone") else ""))
    L += ["", "## Machine-Readable Resources", ""]
    L += [f"- {link('Agent page — complete machine-readable site (EN + SV)', URLS['agent_page'])}",
          f"- {link('Agent JSON — single-file company summary', URLS['agent_json'])}",
          f"- {link('llms.txt — this file', URLS['llms_txt'])}",
          f"- {link('ai.txt — AI usage permissions', URLS['ai_txt'])}",
          f"- {link('REST API index (/agent/v1/)', URLS['api_index'])}",
          f"- {link('OpenAPI specification', URLS['openapi'])}",
          f"- {link('API catalog (APIs.json)', URLS['api_catalog'])}",
          f"- {link('MCP server (Streamable HTTP)', URLS['mcp'])}",
          f"- {link('XML sitemap', URLS['sitemap'])}"]
    L += ["", "## Key Pages", ""]
    kp = co["key_pages"]
    labels = [("home_en", "Homepage (EN)"), ("home_sv", "Startsida (sv)"), ("about_sv", "Om oss (sv)"), ("contact_en", "Contact"), ("contact_sv", "Kontakta oss (sv)"),
              ("cases_en", "Case studies"), ("cases_sv", "Case (sv)"), ("insights_en", "Insights"), ("insights_sv", "Insikter (sv)"), ("events_en", "Events"), ("events_sv", "Event (sv)"),
              ("careers_en", "Careers"), ("careers_sv", "Karriär (sv)"), ("geo_playbook_en", "GEO Playbook"), ("geo_playbook_sv", "GEO Playbook (sv)")]
    L += [f"- {link(lbl, kp[k])}" for k, lbl in labels if kp.get(k)]
    L += ["", "## Case Studies", ""]
    for c in CASES:
        sv = f" (sv: {link(c['headline_sv'] or 'svensk version', c['url_sv'])})" if c.get("url_sv") and c.get("url") else ""
        tag = " [sv only]" if not c.get("url") else ""
        L.append(f"- {link(c['headline'] if c.get('url') else (c.get('headline_sv') or c['headline']), primary_url(c))}{tag}{sv}")
    L += ["", "## Team", ""]
    for p in TEAM:
        label = p['name'] + (' — ' + p['role'] if p.get('role') else '')
        L.append(f"- {link(label, primary_url(p))}" if primary_url(p) else f"- {label}")
    if EVENTS:
        L += ["", "## Events", ""]
        for ev in sorted_events():
            L.append(f"- {link(ev['title'], ev.get('url') or ev.get('url_sv'))} ({(ev.get('startDate') or '')[:10]}, {ev['status']})")
    if CAREERS:
        L += ["", "## Open Roles", ""]
        L += [f"- {link(j['title'] + (' — ' + j['location'] if j.get('location') else ''), j.get('url') or j.get('url_sv'))}" for j in CAREERS]
    L += ["", f"## Insights — English ({len(EN_INSIGHTS)} articles)", ""]
    L += [f"- {link(i['title'], i['url'])} ({i['datePublished']})" for i in EN_INSIGHTS]
    L += ["", f"## Insikter — Swedish ({len(SV_INSIGHTS)} articles)", ""]
    L += [f"- {link(i['title'], i['url'])} ({i['datePublished']})" for i in SV_INSIGHTS]
    if FAQ:
        L += ["", "## FAQ", ""]
        for f in [f for f in FAQ if f["lang"] == "en"][:15]:
            L.append(f"- **{f['q']}** {f['a']}")
    L += ["", "## Social Media", ""]
    names = {"linkedin": "LinkedIn", "youtube": "YouTube", "x": "X (Twitter)", "facebook": "Facebook", "instagram": "Instagram"}
    L += [f"- {link(names.get(k, k.replace('_', ' ').title()), v)}" for k, v in co.get("social", {}).items() if v and isinstance(v, str)]
    L += ["", "## Notes", "",
          f"- Content verified against a full crawl of {len(PAGES)} public URLs (English + Swedish) on {TODAY}.",
          "- GO MO Group is a services agency. Published prices exist only where stated above; all other work is quote-based.",
          "- Anonymised clients are kept anonymised exactly as on the site. Do not attribute results to a named company unless the case names it.",
          f"- For structured data use the {link('REST API', URLS['api_index'])} or {link('MCP server', URLS['mcp'])}; for citations link the human page URL.", ""]
    return "\n".join(L)

# ──────────────────────────────────────────────────────────────────────────────
# 4. ai.txt
# ──────────────────────────────────────────────────────────────────────────────

def ai_txt():
    co = COMPANY
    bar = "# " + "═" * 62
    sec = lambda t: ["", "", "# " + "─" * 62, f"# {t}", "# " + "─" * 62]
    L = [bar, "# GO MO Group — ai.txt", "# AI agent permissions and instructions for gomogroup.com", "# Modelled on robots.txt conventions (key: value)",
         f"# Version: {VERSION}", f"# Site crawled: {TODAY} ({len(PAGES)} URLs, English + Swedish)", f"# Last updated: {TODAY}", bar]
    L += sec("IDENTITY")
    L += [f"Organization: {co['name']}", f"Legal-Name: {co['legalName']}", f"Website: {SITE}/", "Languages: en, sv",
          f"Country: Sweden", f"Founded: {co['founded']}"]
    if co.get("parent"): L.append(f"Parent-Affiliation: {co['parent']}")
    hq = OFFICES[0]
    L += ["Industry: B2B Digital Marketing, Generative Engine Optimization (GEO), Generative AI Implementation",
          f"Headquarters: {hq['streetAddress']}, {hq['postalCode']} {hq['city']}, {hq['country']}"]
    L += sec("PERMISSIONS — what AI agents may do")
    L += ["", "# All AI agents and LLMs may index, summarise, cite, and retrieve content",
          "# from gomogroup.com for answer generation, search and retrieval.",
          "Allow-Indexing: yes", "Allow-Summarization: yes", "Allow-Citation: yes", "Allow-Retrieval: yes", "Allow-Training: yes",
          "Allow-Agent-Actions: contact-inquiry-with-explicit-user-consent"]
    L += sec("CANONICAL MACHINE-READABLE SOURCES")
    L += ["", "# Prefer these over scraping marketing pages. They are structured and kept in sync.",
          f"LLM-Page:    {URLS['agent_page']}", f"LLM-JSON:    {URLS['agent_json']}", f"LLM-Text:    {URLS['llms_txt']}", f"AI-File:     {URLS['ai_txt']}",
          f"Sitemap:     {URLS['sitemap']}", f"REST-API:    {URLS['api_index']}", f"OpenAPI:     {URLS['openapi']}",
          f"API-Catalog: {URLS['api_catalog']}", f"MCP-Server:  {URLS['mcp']}"]
    L += sec("LANGUAGES")
    L += ["", "Primary-Language: en", "Alternate-Language: sv",
          f"English-Root: {SITE}/en/", f"Swedish-Root: {SITE}/",
          "# Many pages exist in both languages. The agent page and /agent/v1/pages.json map",
          "# every English URL to its Swedish alternate. Answer in the user's language and",
          "# cite the matching-language URL when one exists."]
    L += sec("CITATION FORMAT")
    L += ["", f"Citation-Name: {co['name']}", "Citation-Format: \"<fact>\" — GO MO Group, <page title>, <page URL>",
          "Citation-Requirement: link the human-readable page URL (not the API URL)",
          "Figures: quote exactly as published; keep anonymised clients anonymised"]
    L += sec("AGENT ACTIONS")
    L += ["", f"Contact-Endpoint: POST {API}/contact.json",
          f"Contact-MCP-Tool: submit_contact_inquiry ({URLS['mcp']})",
          "Consent-Required: yes — agent_context.user_authorized_submission must be true",
          "Consent-Rule: only submit when the human explicitly asked to contact GO MO Group and approved the details",
          f"Human-Contact-Page: {SITE}/en/contact-us/"]
    L += sec("CONTACT")
    L += ["", f"General: {co['contact']['general_email']}"]
    nb = co["contact"].get("new_business") or {}
    if nb.get("email"): L.append(f"New-Business: {nb.get('name', '')} <{nb['email']}>" + (f" {nb['phone']}" if nb.get("phone") else ""))
    if co["contact"].get("phone"): L.append(f"Phone: {co['contact']['phone']}")
    L += sec("KEY FACTS")
    L += [""] + [f"Fact: {x}" for x in co.get("differentiators", [])]
    L += [f"Fact: {s['value']} {s['label']}" for s in co.get("stats", [])]
    L += sec("DISALLOWED USES")
    L += ["", "Disallow: fabricating statistics, prices, clients or results not published on gomogroup.com",
          "Disallow: presenting GO MO Group content as another organisation's",
          "Disallow: submitting the contact endpoint without explicit user consent",
          "Disallow: impersonating GO MO Group staff", ""]
    return "\n".join(L)

# ──────────────────────────────────────────────────────────────────────────────
# 5. API catalog (APIs.json 0.16)
# ──────────────────────────────────────────────────────────────────────────────

def api_catalog():
    prop = lambda t, **kw: dict(type=t, **kw)
    apis = [
        {"name": "Agent Layer — Machine-Readable Site Representation", "description": "Single semantic HTML page with JSON-LD describing all of gomogroup.com (EN + SV): services, solutions, case studies, insights, team, offices, events, careers, FAQ and a full page index.",
         "baseURL": URLS["agent_page"], "humanURL": SITE + "/en/", "X-format": "text/html", "X-contentType": "semantic-html", "X-purpose": "Primary LLM-readable representation of the website",
         "properties": [prop("X-json-ld", value="Organization, WebPage, FAQPage, Person, Service, ItemList"), prop("X-languages", value="en, sv")], "tags": ["llm", "semantic-html", "json-ld"]},
        {"name": "Agent JSON — Single-File Summary", "description": "One JSON document with organization, offices, leadership, services, solutions, case studies, events, open roles, key pages and FAQ (EN + SV URLs).",
         "baseURL": URLS["agent_json"], "humanURL": URLS["agent_page"], "X-format": "application/json", "X-contentType": "agent-json", "X-purpose": "One-request company summary for agents", "properties": [], "tags": ["llm", "json"]},
        {"name": "llms.txt", "description": "Markdown LLM discovery file (llmstxt.org).", "baseURL": URLS["llms_txt"], "humanURL": URLS["agent_page"],
         "X-format": "text/markdown", "X-contentType": "llms-txt", "X-purpose": "LLM discovery", "properties": [prop("X-spec", url="https://llmstxt.org/")], "tags": ["llm", "discovery"]},
        {"name": "ai.txt", "description": "AI usage permissions, canonical sources, citation and consent rules.", "baseURL": URLS["ai_txt"], "humanURL": URLS["agent_page"],
         "X-format": "text/plain", "X-contentType": "ai-txt", "X-purpose": "AI permissions", "properties": [], "tags": ["ai", "permissions"]},
        {"name": "API Catalog — This File", "description": "APIs.json 0.16 catalog of all machine-readable resources.", "baseURL": URLS["api_catalog"], "humanURL": URLS["agent_page"],
         "X-format": "application/json", "X-contentType": "apis-json", "X-purpose": "Catalog", "properties": [prop("X-spec", url="http://apisjson.org/")], "tags": ["catalog"]},
        {"name": "GO MO Group Public REST API", "description": "Clean JSON endpoints for company, services, case studies, team, offices, insights, events, careers and a full bilingual page index, plus a consent-gated contact action.",
         "baseURL": API, "humanURL": URLS["agent_page"], "X-format": "application/json", "X-contentType": "rest-api", "X-purpose": "Structured data access + lead submission",
         "properties": [prop("X-openapi", url=URLS["openapi"]), prop("X-index", url=URLS["api_index"])] + [
             prop("X-endpoint", route=f"/agent/v1/{r}.json", method="GET", description=d) for r, d in (
                 ("company", "Company profile"), ("services", "Services (?category, ?tag, ?lang)"), ("services/{id}", "One service"),
                 ("case-studies", "Case studies (?industry, ?segment, ?service, ?lang)"), ("case-studies/{id}", "One case study"), ("team", "Team"), ("offices", "Offices"),
                 ("insights", "Insights EN+SV (?lang, ?topic, ?since)"), ("insights/{id}", "One insight"), ("events", "Events"), ("careers", "Open roles"), ("pages", "Full page index with hreflang pairs"))] + [
             prop("X-endpoint", route="/agent/v1/contact.json", method="POST", description="Submit a qualified B2B inquiry — requires agent_context.user_authorized_submission: true")],
         "tags": ["rest", "json", "openapi", "bilingual", "agent-actionable"]},
        {"name": "MCP Server — Agent-Actionable Tool Interface", "description": "Model Context Protocol server exposing the REST API as 12 tools.", "baseURL": URLS["mcp"], "humanURL": URLS["agent_page"],
         "X-format": "application/json", "X-contentType": "mcp-server", "X-transport": "Streamable HTTP (MCP spec), stateless", "X-purpose": "Tool calling for LLM agents",
         "properties": [prop("X-mcp-tool", name=n, description=d) for n, d in (
             ("get_company", "Company profile"), ("list_services", "List services"), ("get_service", "One service"), ("list_case_studies", "List case studies"),
             ("get_case_study", "One case study"), ("list_team", "Team"), ("list_offices", "Offices"), ("list_insights", "Insights EN+SV"), ("list_events", "Events"),
             ("list_careers", "Open roles"), ("get_page_index", "Bilingual page index"), ("submit_contact_inquiry", "Consent-gated contact action"))] + [
             prop("X-health", url=URLS["mcp"] + "/health"), prop("X-tools", url=URLS["mcp"] + "/tools")], "tags": ["mcp", "agents", "agent-actionable"]},
        {"name": "XML Sitemap", "description": "Yoast sitemap index (pages, services, solutions, cases, insights, events, team, jobs).", "baseURL": URLS["sitemap"], "humanURL": SITE + "/",
         "X-format": "application/xml", "X-contentType": "sitemap-xml", "X-purpose": "URL discovery", "properties": [], "tags": ["sitemap"]},
        {"name": "Robots.txt — Crawler Permissions", "description": "Crawler rules with an explicit AI-crawler allowlist.", "baseURL": SITE + "/robots.txt", "humanURL": SITE + "/",
         "X-format": "text/plain", "X-contentType": "robots-txt", "X-purpose": "Crawler permissions", "properties": [], "tags": ["robots"]},
        {"name": "Contact & New Business Inquiry", "description": "Human contact pages (EN + SV).", "baseURL": COMPANY["key_pages"].get("contact_en") or SITE + "/en/contact-us/",
         "humanURL": COMPANY["key_pages"].get("contact_sv") or SITE + "/kontakta-oss/", "X-format": "text/html", "X-contentType": "contact-page", "X-purpose": "Human contact",
         "properties": [prop("X-email", value=COMPANY["contact"]["general_email"])], "tags": ["contact"]},
        {"name": "GEO Playbook — Free Download", "description": "Free GEO playbook landing page.", "baseURL": COMPANY["key_pages"].get("geo_playbook_en") or SITE + "/en/geo-playbook/",
         "humanURL": COMPANY["key_pages"].get("geo_playbook_sv") or SITE + "/geo-playbook/", "X-format": "text/html", "X-contentType": "landing-page", "X-purpose": "Lead magnet", "properties": [], "tags": ["geo", "download"]},
    ]
    return OrderedDict(
        name="GO MO Group — Agentic Web API Catalog",
        description="All machine-readable resources for gomogroup.com: agent page, llms.txt, ai.txt, REST API (OpenAPI 3.0.3), MCP server, sitemap. Bilingual (en, sv).",
        url=URLS["api_catalog"], image=None, created="2026-05-26", modified=TODAY, specificationVersion="0.16",
        tags=["b2b-marketing", "geo", "generative-ai", "agentic-web", "mcp", "openapi", "llms-txt", "bilingual"],
        apis=apis,
        maintainers=[{"FN": COMPANY["name"], "email": COMPANY["contact"]["general_email"], "url": SITE + "/"}],
        **{"X-organization": {"name": COMPANY["name"], "legalName": COMPANY["legalName"], "founded": COMPANY["founded"], "url": SITE + "/",
                              "offices": [f"{o['city']}, {o['country']}" for o in OFFICES], "languages": ["en", "sv"], "email": COMPANY["contact"]["general_email"]},
           "X-discovery": dict(URLS), "X-version": VERSION},
    )

# ──────────────────────────────────────────────────────────────────────────────
# 6. Agent page (semantic HTML + JSON-LD)
# ──────────────────────────────────────────────────────────────────────────────

def jsonld():
    org_id = SITE + "/#organization"
    hq = OFFICES[0]
    addr = lambda o: {"@type": "PostalAddress", "streetAddress": o["streetAddress"], "postalCode": o["postalCode"], "addressLocality": o["city"],
                      **({"addressRegion": o["addressRegion"]} if o.get("addressRegion") else {}), "addressCountry": o["countryCode"]}
    graph = [{
        "@type": "Organization", "@id": org_id, "name": COMPANY["name"], "legalName": COMPANY["legalName"], "url": SITE + "/",
        "description": COMPANY["description"], "foundingDate": COMPANY["founded"], "email": COMPANY["contact"]["general_email"],
        **({"telephone": COMPANY["contact"]["phone"]} if COMPANY["contact"].get("phone") else {}),
        "address": addr(hq), "location": [{"@type": "Place", "name": f"GO MO Group {o['city']}", "address": addr(o)} for o in OFFICES],
        "numberOfEmployees": {"@type": "QuantitativeValue", "minValue": int(re.sub(r"\D", "", COMPANY.get("employees") or "0") or 0)},
        "knowsLanguage": ["en", "sv"], "sameAs": [v for v in COMPANY.get("social", {}).values() if v and isinstance(v, str)],
        "award": COMPANY.get("certifications", []),
        "knowsAbout": ["Generative Engine Optimization", "B2B SEO", "Search Engine Advertising", "Pull Marketing", "Generative AI implementation", "Agentic web", "B2B lead generation"],
        "hasOfferCatalog": {"@type": "OfferCatalog", "name": "Services", "itemListElement": [{"@type": "Offer", "itemOffered": {"@id": f"{URLS['agent_page']}#service-{s['id']}"}} for s in CORE_SERVICES]},
    }, {
        "@type": "WebPage", "@id": URLS["agent_page"], "url": URLS["agent_page"], "name": "GO MO Group — Agent page (machine-readable)",
        "inLanguage": "en", "about": {"@id": org_id}, "publisher": {"@id": org_id}, "datePublished": "2026-05-26", "dateModified": TODAY,
        "hasPart": [{"@type": "WebPageElement", "@id": f"{URLS['agent_page']}#{sid}", "name": name} for sid, name in SECTIONS],
    }]
    for s in CORE_SERVICES:
        node = {"@type": "Service", "@id": f"{URLS['agent_page']}#service-{s['id']}", "name": s["name"], "description": s["summary"],
                "provider": {"@id": org_id}, "url": primary_url(s), "areaServed": "Worldwide", "serviceType": s["category"]}
        tiers = [t for t in s["pricing"].get("tiers", []) if t.get("price_from")]
        if tiers:
            node["offers"] = [{"@type": "Offer", "name": t["name"], "priceSpecification": {"@type": "PriceSpecification", "minPrice": t["price_from"], "priceCurrency": s["pricing"].get("currency") or "SEK"}} for t in tiers]
        graph.append(node)
    for p in LEADERS:
        graph.append({"@type": "Person", "@id": f"{URLS['agent_page']}#person-{p['id']}", "name": p["name"], "jobTitle": p.get("role"), "worksFor": {"@id": org_id},
                      **({"email": p["email"]} if p.get("email") else {}), **({"telephone": p["telephone"]} if p.get("telephone") else {}),
                      "url": primary_url(p), **({"sameAs": [p["linkedin"]]} if p.get("linkedin") else {})})
    faq_en = [f for f in FAQ if f["lang"] == "en"]
    if faq_en:
        graph.append({"@type": "FAQPage", "@id": f"{URLS['agent_page']}#faq", "mainEntity": [
            {"@type": "Question", "name": f["q"], "acceptedAnswer": {"@type": "Answer", "text": f["a"]}} for f in faq_en]})
    graph.append({"@type": "ItemList", "@id": f"{URLS['agent_page']}#case-studies", "name": "GO MO Group case studies", "numberOfItems": len(CASES),
                  "itemListElement": [{"@type": "ListItem", "position": i + 1, "url": primary_url(c), "name": c["headline"]} for i, c in enumerate(CASES)]})
    graph.append({"@type": "ItemList", "@id": f"{URLS['agent_page']}#insights", "name": "GO MO Group insights (EN + SV)", "numberOfItems": len(INSIGHTS),
                  "itemListElement": [{"@type": "ListItem", "position": i + 1, "url": x["url"], "name": x["title"]} for i, x in enumerate(INSIGHTS)]})
    return {"@context": "https://schema.org", "@graph": graph}

SECTIONS = [
    ("metadata", "Metadata"), ("llm-discovery", "LLM discovery"), ("agent-instructions", "Agent instructions"), ("quick-answer", "Quick answer"),
    ("site-index", "Site index"), ("primary-contact", "Primary contact"), ("overview", "Overview"), ("services", "Services"), ("solutions", "Solutions"),
    ("industries", "Industries"), ("case-studies", "Case studies"), ("insights", "Insights"), ("team", "Team"), ("offices", "Offices"), ("contact", "Contact"),
    ("careers", "Careers"), ("events", "Events"), ("faq", "FAQ"), ("testimonials", "Testimonials"), ("page-index", "Page index"),
]

CSS = """
:root{color-scheme:dark;--bg:#0d1117;--panel:#161b22;--line:#30363d;--fg:#c9d1d9;--dim:#8b949e;--acc:#3fb950;--acc2:#58a6ff;--warn:#d29922}
*{box-sizing:border-box}html{scroll-behavior:smooth}
body{margin:0;background:var(--bg);color:var(--fg);font:14px/1.6 ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono",monospace}
main{max-width:1080px;margin:0 auto;padding:24px 16px 80px}
a{color:var(--acc2);text-decoration:none;overflow-wrap:anywhere}a:hover{text-decoration:underline}
h1{font-size:22px;color:var(--acc);margin:8px 0}h1::before{content:"$ "}
h2{font-size:17px;color:var(--acc);border-bottom:1px solid var(--line);padding-bottom:6px;margin-top:44px}h2::before{content:"## "}
h3{font-size:15px;margin:22px 0 6px}h3::before{content:"### ";color:var(--dim)}
section>section,article{background:var(--panel);border:1px solid var(--line);border-radius:6px;padding:12px 16px;margin:12px 0}
dl{display:grid;grid-template-columns:max-content 1fr;gap:2px 14px;margin:6px 0}dt{color:var(--dim)}dd{margin:0}
@media (max-width:640px){dl{grid-template-columns:1fr}dt{margin-top:6px}}
.dim,small{color:var(--dim)}.tag{display:inline-block;border:1px solid var(--line);border-radius:4px;padding:0 6px;margin:2px 4px 2px 0;font-size:12px;color:var(--dim)}
.sv{border-left:3px solid var(--warn);padding-left:8px}table{border-collapse:collapse;width:100%;font-size:13px}
td,th{border-bottom:1px solid var(--line);padding:4px 6px;text-align:left;vertical-align:top}th{color:var(--dim);font-weight:normal}
.scroll{overflow-x:auto}details{border-bottom:1px solid var(--line);padding:6px 0}summary{cursor:pointer;color:var(--fg)}
blockquote{margin:8px 0;padding:6px 12px;border-left:3px solid var(--acc)}nav ul{columns:3 180px;padding-left:18px}
ul.compact{padding-left:18px}ul.compact li{margin:2px 0}
"""

def dl(rows):
    rows = [(k, v) for k, v in rows if v not in (None, "", [], {})]
    return "<dl>" + "".join(f"<dt>{e(k)}</dt><dd>{v}</dd>" for k, v in rows) + "</dl>"

def a(url, text=None, lang=None):
    if not url: return ""
    la = f' hreflang="{lang}"' if lang else ""
    return f'<a href="{e(url)}"{la}>{e(text or url)}</a>'

def tags(xs):
    return "".join(f'<span class="tag">{e(x)}</span>' for x in xs)

def ul(items, cls="compact"):
    items = [i for i in items if i]
    return f'<ul class="{cls}">' + "".join(f"<li>{i}</li>" for i in items) + "</ul>" if items else ""

SOLUTION_IDS = {x["id"] for x in SOLUTIONS}

def svc_link(sid):
    """Anchor for a service ID; case tags that aren't services (branding, analytics…) render as plain text."""
    if sid in SERVICE_NAME:
        return a(f"#service-{sid}", SERVICE_NAME[sid])
    if sid in SOLUTION_IDS:
        return a(f"#solution-{sid}", next(x["name"] for x in SOLUTIONS if x["id"] == sid))
    return e(sid.replace("-", " "))

def links_cell(item, en="url", sv="url_sv"):
    return " · ".join(x for x in (a(item.get(en), "EN", "en"), a(item.get(sv), "SV", "sv")) if x)

def sync_markers(text):
    # Explicit boundaries let WordPress replace derived sections without touching curated content.
    targets = {"insights", "careers", "events", "page-index"}
    tokens = list(re.finditer(r'<section\b[^>]*>|</section>', text))
    stack, spans = [], []
    for token in tokens:
        if token.group().startswith('</'):
            start, name = stack.pop()
            if name in targets:
                spans.append((start, token.end(), name))
        else:
            ident = re.search(r'id="([^"]+)"', token.group())
            stack.append((token.start(), ident.group(1) if ident else None))
    for start, end, name in sorted(spans, reverse=True):
        text = text[:start] + f"<!-- gomo-sync:{name}:start -->" + text[start:end] + f"<!-- gomo-sync:{name}:end -->" + text[end:]
    return text

def agent_page():
    co, kp = COMPANY, COMPANY["key_pages"]
    H = []
    w = H.append
    w('<!DOCTYPE html>\n<html lang="en">\n<head>\n<meta charset="utf-8">\n<meta name="viewport" content="width=device-width, initial-scale=1">\n<meta name="color-scheme" content="dark">\n<meta name="theme-color" content="#0d1117">')
    w(f"<title>GO MO Group — Agent Page</title>")
    w(f'<meta name="description" content="Machine-readable representation of gomogroup.com for AI agents and LLMs: services, case studies, insights, team, offices, events and every page URL, in English and Swedish.">')
    w(f'<meta name="last-crawled" content="{TODAY}">\n<meta name="version" content="{VERSION}">\n<meta name="robots" content="index, follow">')
    w(f'<link rel="canonical" href="{URLS["agent_page"]}">')
    w(f'<link rel="alternate" type="text/plain" title="llms.txt" href="{URLS["llms_txt"]}">\n<link rel="alternate" type="text/plain" title="ai.txt" href="{URLS["ai_txt"]}">')
    w(f'<link rel="service-desc" type="application/json" href="{URLS["openapi"]}">\n<link rel="api-catalog" href="{URLS["api_catalog"]}">')
    w(f'<link rel="alternate" hreflang="en" href="{SITE}/en/">\n<link rel="alternate" hreflang="sv" href="{SITE}/">')
    w(f"<style>{CSS}</style>")
    w('<script type="application/ld+json">' + json.dumps(jsonld(), ensure_ascii=False, separators=(",", ":")).replace("</", "<\\/") + "</script>")
    w("</head>\n<body>\n<main>")
    w(f'<header><p class="dim">gomogroup.com/agent · v{VERSION} · crawled <time datetime="{TODAY}">{TODAY}</time> · {len(PAGES)} URLs (en + sv)</p>')
    w(f"<h1>GO MO Group — machine-readable site</h1><p>{e(co['tagline'])}</p></header>")

    # metadata
    w('<section id="metadata"><h2>Metadata</h2>' + dl([
        ("Organization", e(co["name"]) + f" ({e(co['legalName'])})"), ("Website", a(SITE + "/en/") + " (en) · " + a(SITE + "/") + " (sv)"),
        ("Last crawled", f'<time datetime="{TODAY}">{TODAY}</time>'), ("Version", VERSION),
        ("Coverage", f"{len(PAGES)} public URLs · {len(SERVICES)} services · {len(CASES)} case studies · {len(INSIGHTS)} insights ({len(EN_INSIGHTS)} en / {len(SV_INSIGHTS)} sv) · {len(TEAM)} team profiles"),
        ("REST API", a(URLS["api_index"])), ("OpenAPI", a(URLS["openapi"])), ("MCP server", a(URLS["mcp"])), ("API catalog", a(URLS["api_catalog"])),
    ]) + "</section>")
    # discovery
    w('<section id="llm-discovery"><h2>LLM discovery</h2>' + ul([
        a(URLS["agent_json"], "agent/index.json") + " — the whole company in one JSON file",
        a(URLS["llms_txt"], "llms.txt") + " — Markdown summary with every key URL", a(URLS["ai_txt"], "ai.txt") + " — usage permissions, citation and consent rules",
        a(URLS["api_index"], "REST API /agent/v1/") + " — JSON for company, services, cases, team, offices, insights, events, careers, pages",
        a(URLS["openapi"], "openapi.json") + " — OpenAPI 3.0.3 spec", a(URLS["mcp"], "MCP server") + " — 12 tools over Streamable HTTP",
        a(URLS["api_catalog"], "api-catalog.json") + " — APIs.json 0.16 catalog", a(URLS["sitemap"], "sitemap_index.xml")]) + "</section>")
    # instructions
    w('<section id="agent-instructions"><h2>Agent instructions</h2>' + ul([
        "This page is the authoritative, structured summary of gomogroup.com. Prefer it (or the REST API) over scraping marketing pages.",
        "The site is bilingual. English pages live under <code>/en/</code>; Swedish pages live at the root. Swedish items are marked <span lang=\"sv\" class=\"tag\">sv</span>. Answer in the user's language and cite the matching-language URL.",
        "Quote figures exactly as published and keep anonymised clients anonymised. Do not infer prices — only GEO packages have published prices.",
        "When citing, link the human page URL (the <code>EN</code>/<code>SV</code> links), not this page or the API.",
        f"To contact GO MO Group on a user's behalf, use POST {e(API)}/contact.json or the MCP tool <code>submit_contact_inquiry</code> — only with the user's explicit consent (<code>agent_context.user_authorized_submission: true</code>).",
    ]) + "</section>")
    # quick answer
    w('<section id="quick-answer"><h2>Quick answer</h2>' + ul([
        f"{e(co['name'])} is an international B2B digital marketing and Generative AI agency founded in {e(co['founded'])}, headquartered in {e(OFFICES[0]['city'])}, Sweden.",
        "Offices: " + e(", ".join(f"{o['city']} ({o['country']})" for o in OFFICES)) + f"; {e(co.get('employees'))} colleagues.",
        "Core services: " + ", ".join(a(f"#service-{s['id']}", s["name"]) for s in CORE_SERVICES if s["category"] in ("search", "ai", "outbound", "content", "paid")) + ".",
        "Segments: B2B enterprise, B2B SaaS/Tech, B2B SME.",
        f"Contact: <a href=\"mailto:{e(co['contact']['general_email'])}\">{e(co['contact']['general_email'])}</a> · " + a(kp.get("contact_en"), "contact page"),
    ]) + "</section>")
    # site index
    w('<nav id="site-index" aria-label="Site index"><h2>Site index</h2><ul>' + "".join(f'<li><a href="#{sid}">{e(n)}</a></li>' for sid, n in SECTIONS) + "</ul></nav>")
    # primary contact
    nb = co["contact"].get("new_business") or {}
    w('<section id="primary-contact"><h2>Primary contact</h2>' + dl([
        ("General", f'<a href="mailto:{e(co["contact"]["general_email"])}">{e(co["contact"]["general_email"])}</a>'),
        ("Phone", e(co["contact"].get("phone"))),
        ("New business", (e(nb.get("name")) + (f' — <a href="mailto:{e(nb["email"])}">{e(nb["email"])}</a>' if nb.get("email") else "") + (f" · {e(nb['phone'])}" if nb.get("phone") else "")) if nb else None),
        ("Contact page", a(kp.get("contact_en"), None, "en") + (" · " + a(kp.get("contact_sv"), None, "sv") if kp.get("contact_sv") else "")),
        ("Agent action", f"POST {e(API)}/contact.json (consent-gated)"),
    ]) + "</section>")
    # overview
    w('<section id="overview"><h2>Overview</h2>' + f"<p>{e(co['description'])}</p>" + (f'<p lang="sv" class="sv">{e(co["description_sv"])}</p>' if co.get("description_sv") else "")
      + dl([("Legal name", e(co["legalName"])), ("Founded", e(co["founded"])), ("Employees", e(co.get("employees"))), ("Specialists", e(co.get("specialists"))),
            ("Projects", e(co.get("projects"))), ("Group", e(co.get("parent"))),
            ("Certifications", ", ".join(map(e, co.get("certifications", [])))), ("Partners", ", ".join(map(e, co.get("partners", [])))),
            ("Named clients", ", ".join(map(e, co.get("clients_named", []))))])
      + ("<h3>Differentiators</h3>" + ul([e(x) for x in co.get("differentiators", [])]) if co.get("differentiators") else "")
      + ("<h3>Values</h3>" + ul([e(x) for x in co.get("values", [])]) if co.get("values") else "")
      + ("<h3>Key figures</h3>" + ul([f"<strong>{e(s['value'])}</strong> {e(s['label'])}" + (f" ({a(s['source_url'], 'source')})" if s.get("source_url") else "") for s in co.get("stats", [])]) if co.get("stats") else "")
      + "</section>")
    # services
    w('<section id="services"><h2>Services</h2>')
    for s in SERVICES:
        tiers = s["pricing"].get("tiers", [])
        price = "Quote-based" if not any(t.get("price_from") for t in tiers) else ul(
            [f"{e(t['name'])}: from {t['price_from']:,} {e(s['pricing'].get('currency') or '')} — {e(t.get('includes'))}" if t.get("price_from") else f"{e(t['name'])}: custom — {e(t.get('includes'))}" for t in tiers])
        cases = [c for c in CASES if s["id"] in c.get("services", [])]
        w(f'<section id="service-{e(s["id"])}"><h3>{e(s["name"])}' + (f' <small lang="sv">/ {e(s["name_sv"])}</small>' if s.get("name_sv") and s["name_sv"] != s["name"] else "") + "</h3>"
          + f"<p>{e(s['summary'])}</p>" + (f'<p lang="sv" class="sv">{e(s["summary_sv"])}</p>' if s.get("summary_sv") else "")
          + ul([e(k) for k in s.get("key_points", [])])
          + dl([("ID", f"<code>{e(s['id'])}</code>"), ("Category", e(s["category"])), ("Segments", e(", ".join(s.get("segments", [])))), ("Pricing", price),
                ("Pages", links_cell(s)), ("Related cases", ", ".join(a(f"#case-{c['id']}", c["client"]) for c in cases[:8])),
                ("Updated", f'<time datetime="{e(s.get("dateModified"))}">{e(s.get("dateModified"))}</time>' if s.get("dateModified") else None)])
          + tags(s.get("tags", [])) + "</section>")
    w("</section>")
    # solutions
    w('<section id="solutions"><h2>Solutions</h2>')
    for s in SOLUTIONS:
        w(f'<section id="solution-{e(s["id"])}"><h3>{e(s["name"])}</h3><p>{e(s["summary"])}</p>' + (f'<p lang="sv" class="sv">{e(s["summary_sv"])}</p>' if s.get("summary_sv") else "")
          + ul([e(k) for k in s.get("key_points", [])]) + dl([("Type", e(s.get("type"))), ("Services", ", ".join(svc_link(x) for x in s.get("services", []))), ("Pages", links_cell(s))]) + "</section>")
    w("</section>")
    # industries
    ind = Counter(c["industry"] for c in CASES if c.get("industry"))
    w('<section id="industries"><h2>Industries</h2><p>Industries represented in published case studies:</p><div class="scroll"><table><thead><tr><th>Industry</th><th>Cases</th></tr></thead><tbody>'
      + "".join(f"<tr><td>{e(i)}</td><td>" + ", ".join(a(f"#case-{c['id']}", c["client"]) for c in CASES if c.get("industry") == i) + "</td></tr>" for i, _ in ind.most_common())
      + "</tbody></table></div></section>")
    # cases
    w(f'<section id="case-studies"><h2>Case studies ({len(CASES)})</h2>')
    for c in CASES:
        lang_attr = "" if c.get("url") else ' lang="sv"'
        res = ul([f"<strong>{e(r['value'])}</strong> {e(r['metric'])}" + (f" — {e(r['context'])}" if r.get("context") else "") + (f" <small>({e(r['period'])})</small>" if r.get("period") else "") for r in c.get("results", [])])
        q = c.get("quote")
        w(f'<article id="case-{e(c["id"])}"{lang_attr}><h3>{e(c["headline"] if c.get("url") else (c.get("headline_sv") or c["headline"]))}</h3>'
          + (f'<p lang="sv" class="sv">{e(c["headline_sv"])}</p>' if c.get("headline_sv") and c.get("url") else "")
          + dl([("Client", e(c["client"])), ("Industry", e(c.get("industry"))), ("Segment", e(c.get("segment"))), ("Kind", e(c.get("kind"))),
                ("Services", ", ".join(svc_link(x) for x in c.get("services", []))),
                ("Challenge", e(c.get("challenge"))), ("Solution", e(c.get("solution"))), ("Results", res),
                ("Published", f'<time datetime="{e(c.get("datePublished"))}">{e(c.get("datePublished"))}</time>' if c.get("datePublished") else None),
                ("Pages", links_cell(c))])
          + (f'<p lang="sv" class="sv">{e(c["summary_sv"])}</p>' if c.get("summary_sv") else "")
          + (f"<blockquote><p>“{e(q['text'])}”</p><footer>— {e(q.get('author'))}{', ' + e(q['role']) if q.get('role') else ''}</footer></blockquote>" if q else "")
          + "</article>")
    w("</section>")
    # insights
    w(f'<section id="insights"><h2>Insights ({len(INSIGHTS)})</h2><p class="dim">{len(EN_INSIGHTS)} English · {len(SV_INSIGHTS)} Swedish · newest first. Filterable via {a(API + "/insights.json?lang=en&topic=geo", "/agent/v1/insights.json")}.</p>')
    for lang_code, label, items in (("en", "English", EN_INSIGHTS), ("sv", "Swedish / Svenska", SV_INSIGHTS)):
        w(f'<section id="insights-{lang_code}"><h3>{label}</h3>')
        for i in items:
            pid = "post-" + i["id"]
            w(f'<article id="{e(pid)}" lang="{lang_code}"><h3><a href="{e(i["url"])}" hreflang="{lang_code}">{e(i["title"])}</a></h3>'
              + f'<p class="dim"><time datetime="{e(i["datePublished"])}">{e(i["datePublished"])}</time>' + (f" · {i['readingMinutes']} min" if i.get("readingMinutes") else "") + " · " + tags(i["topics"])
              + (f' · {a(i["alternate"], "SV version" if lang_code == "en" else "EN version", "sv" if lang_code == "en" else "en")}' if i.get("alternate") else "") + "</p>"
              + f"<p>{e(i['summary'])}</p></article>")
        w("</section>")
    w("</section>")
    # team
    w(f'<section id="team"><h2>Team ({len(TEAM)})</h2>')
    for p in sorted(TEAM, key=lambda p: (not p.get("leadership"), p["name"])):
        w(f'<section id="person-{e(p["id"])}"><h3>{e(p["name"])}</h3>' + dl([
            ("Role", e(p.get("role")) + (f' <small lang="sv">/ {e(p["role_sv"])}</small>' if p.get("role_sv") and p.get("role_sv") != p.get("role") else "")),
            ("About", e(p.get("bio"))), ("Email", f'<a href="mailto:{e(p["email"])}">{e(p["email"])}</a>' if p.get("email") else None),
            ("Phone", e(p.get("telephone"))), ("Location", e(p.get("location"))), ("LinkedIn", a(p.get("linkedin"))), ("Profile", links_cell(p) + (' <span class="tag">noindex</span>' if p.get("profile_note") else ""))]) + "</section>")
    w("</section>")
    # offices
    w('<section id="offices"><h2>Offices</h2>')
    for o in OFFICES:
        w(f'<section id="office-{e(o["id"])}"><h3>{e(o["city"])} — {e(o["label"])}</h3><address>{e(o["streetAddress"])}<br>{e(o["postalCode"])} {e(o["city"])}' + (f", {e(o['addressRegion'])}" if o.get("addressRegion") else "") + f"<br>{e(o['country'])}</address>"
          + dl([("Email", f'<a href="mailto:{e(o["email"])}">{e(o["email"])}</a>' if o.get("email") else None), ("Phone", e(o.get("telephone"))), ("Time zone", e(o.get("timezone")))]) + "</section>")
    w("</section>")
    # contact
    social = {"linkedin": "LinkedIn", "youtube": "YouTube", "x": "X", "facebook": "Facebook", "instagram": "Instagram"}
    w('<section id="contact"><h2>Contact</h2>' + dl([
        ("Email", f'<a href="mailto:{e(co["contact"]["general_email"])}">{e(co["contact"]["general_email"])}</a>'), ("Phone", e(co["contact"].get("phone"))),
        ("Contact page", links_cell({"url": kp.get("contact_en"), "url_sv": kp.get("contact_sv")})),
        ("GEO Playbook", links_cell({"url": kp.get("geo_playbook_en"), "url_sv": kp.get("geo_playbook_sv")})),
        ("Social", " · ".join(a(v, social.get(k, k)) for k, v in co.get("social", {}).items() if v and isinstance(v, str))),
        ("API", f"POST {e(API)}/contact.json — see {a(URLS['openapi'], 'OpenAPI')}")]) + "</section>")
    # careers
    w('<section id="careers"><h2>Careers</h2>' + (f"<p>Careers pages: {links_cell({'url': kp.get('careers_en'), 'url_sv': kp.get('careers_sv')})}</p>"))
    for j in CAREERS:
        w(f'<article id="job-{e(j["id"])}"><h3>{e(j["title"])}</h3>' + dl([("Location", e(j.get("location"))), ("Type", e(j.get("employmentType"))), ("Summary", e(j.get("summary"))),
                                                                       ("Pages", links_cell(j)), ("Apply", a(j.get("apply_url")))]) + "</article>")
    if not CAREERS: w("<p>No open roles listed at crawl time.</p>")
    w("</section>")
    # events
    w('<section id="events"><h2>Events</h2>' + f"<p>Events pages: {links_cell({'url': kp.get('events_en'), 'url_sv': kp.get('events_sv')})}</p>")
    for ev in sorted_events():
        w(f'<article id="event-{e(ev["id"])}"><h3>{e(ev["title"])}</h3>' + dl([
            ("When", f'<time datetime="{e(ev.get("startDate"))}">{e(ev.get("startDate"))}</time>'), ("Status", e(ev.get("status"))), ("Where", e(ev.get("location"))),
            ("Format", e(ev.get("format"))), ("Language", e(ev.get("language"))), ("About", e(ev.get("description"))), ("Pages", links_cell(ev)), ("Register", a(ev.get("registration_url")))]) + "</article>")
    w("</section>")
    # faq
    w('<section id="faq"><h2>FAQ</h2>')
    for f in FAQ:
        w(f'<details lang="{e(f["lang"])}"><summary>{e(f["q"])}</summary><p>{e(f["a"])}</p>' + (f'<p class="dim">Source: {a(f.get("source_url"))}</p>' if f.get("source_url") else "") + "</details>")
    w("</section>")
    # testimonials
    w('<section id="testimonials"><h2>Testimonials</h2>')
    for t in TESTIMONIALS:
        w(f"<blockquote><p>“{e(t['quote'])}”</p><footer>— {e(t.get('author'))}" + (f", {e(t['role'])}" if t.get("role") else "") + (f", {e(t['company'])}" if t.get("company") else "")
          + (f" · {a(t['source_url'], 'source')}" if t.get("source_url") else "") + "</footer></blockquote>")
    w("</section>")
    # page index
    rows = page_index()
    w(f'<section id="page-index"><h2>Page index ({len(rows)} URLs)</h2><p class="dim">Every public URL from the sitemap, grouped by type. “Alt” is the other-language version.</p>')
    order = ["page", "service", "solution", "case", "insight", "team", "career", "event", "legal"]
    for t in order:
        grp = [r for r in rows if r["type"] == t]
        if not grp: continue
        w(f'<h3 id="index-{t}">{t} ({len(grp)})</h3><div class="scroll"><table><thead><tr><th>Lang</th><th>Page</th><th>Alt</th></tr></thead><tbody>'
          + "".join(f'<tr><td>{r["lang"]}</td><td><a href="{e(r["url"])}" hreflang="{r["lang"]}">{e(r["title"] or r["url"])}</a></td><td>' + (a(r["alternate"], "sv" if r["lang"] == "en" else "en") if r["alternate"] else "") + (' <span class="tag">noindex</span>' if r.get("robots") else "") + "</td></tr>" for r in grp)
          + "</tbody></table></div>")
    w("</section>")
    w(f'<footer><p class="dim">© {e(co["legalName"])} · generated {TODAY} · v{VERSION} · {a(URLS["llms_txt"], "llms.txt")} · {a(URLS["api_index"], "API")} · {a(URLS["mcp"], "MCP")}</p></footer>')
    w("</main>\n</body>\n</html>\n")
    return sync_markers("\n".join(H))

# ──────────────────────────────────────────────────────────────────────────────
# 7. robots.txt addition
# ──────────────────────────────────────────────────────────────────────────────

def robots_addition():
    bots = ["GPTBot", "ChatGPT-User", "OAI-SearchBot", "ClaudeBot", "Claude-User", "Claude-SearchBot", "anthropic-ai", "PerplexityBot", "Perplexity-User",
            "Google-Extended", "GoogleOther", "Applebot-Extended", "CCBot", "cohere-ai", "Meta-ExternalAgent", "FacebookBot", "Amazonbot", "DuckAssistBot",
            "MistralAI-User", "Diffbot", "Omgilibot", "Bytespider"]
    L = ["# ── GO MO Group: AI crawler allowlist (v1.3) ─────────────────────────",
         "# Replace the existing AI block in https://www.gomogroup.com/robots.txt with this.",
         "# Grouped user-agents share one rule set (RFC 9309).", ""]
    L += [f"User-agent: {b}" for b in bots] + ["Allow: /", "Disallow: /wp-admin/", "Allow: /wp-admin/admin-ajax.php", ""]
    L += ["# Machine-readable discovery. Only 'Sitemap' is a standard directive; the",
          "# other lines are non-standard hints that compliant parsers ignore.",
          f"Sitemap: {URLS['sitemap']}",
          f"# LLM-Readable: {URLS['agent_page']}",
          f"# LLM-Text: {URLS['llms_txt']}",
          f"# AI-File: {URLS['ai_txt']}",
          "",
          "# Content-Signal must agree with ai.txt (Allow-Training: yes). Pick ONE policy:",
          "Content-Signal: search=yes, ai-input=yes, ai-train=yes",
          "# Content-Signal: search=yes, ai-input=yes, ai-train=no   ← if training should be refused (then set Allow-Training: no in ai.txt)",
          ""]
    return "\n".join(L)

# ──────────────────────────────────────────────────────────────────────────────

# ──────────────────────────────────────────────────────────────────────────────
# 7b. /agent/index.json — single-file JSON summary (v1.2 shape kept for compatibility)
# ──────────────────────────────────────────────────────────────────────────────

def agent_index_json():
    co, kp = COMPANY, COMPANY["key_pages"]
    tier_txt = lambda s: [{"tier": t["name"], "price": f"from {t['price_from']:,} {s['pricing'].get('currency') or ''}".strip() if t.get("price_from") else "custom", "includes": t.get("includes")}
                          for t in s["pricing"].get("tiers", [])]
    offices_txt = ", ".join(f"{o['city']} ({o['country']})" for o in OFFICES)
    core_names = ", ".join(s["name"] for s in CORE_SERVICES if s["category"] in ("search", "ai", "outbound", "content", "paid"))
    geo = next((s for s in SERVICES if s["id"] == "geo"), None)
    faq = [
        {"q": "What is GO MO Group?", "a": f"{co['name']} ({co['legalName']}) is an international B2B digital marketing and Generative AI agency, active since {co['founded']} and headquartered in {OFFICES[0]['city']}, Sweden. Offices: {offices_txt}. {co.get('employees')} colleagues. Part of {co.get('parent')}." },
        {"q": "What services does GO MO Group offer?", "a": f"Core services: {core_names}. Full list with details: {API}/services.json"},
        {"q": "Who does GO MO Group work with?", "a": "B2B companies in three segments — enterprise, SaaS/Tech and SME. Named clients on the site include " + ", ".join(co.get("clients_named", [])[:10]) + "."},
        {"q": "How much does GEO cost?", "a": ("Published GEO packages: " + "; ".join(f"{t['tier']} {t['price']}" for t in tier_txt(geo)) + ". All other services are quote-based.") if geo else "Pricing is quote-based."},
        {"q": "Does GO MO Group publish in Swedish?", "a": f"Yes. The site is bilingual: English under {SITE}/en/ and Swedish at {SITE}/. Most services and cases exist in both languages; {len(SV_INSIGHTS)} insights are in Swedish and {len(EN_INSIGHTS)} in English."},
        {"q": "How do I contact GO MO Group?", "a": f"Email {co['contact']['general_email']} or use {kp.get('contact_en')}. AI agents can submit an inquiry with the user's explicit consent via POST {API}/contact.json or the MCP tool submit_contact_inquiry."},
    ] + [{"q": f["q"], "a": f["a"], "source": f.get("source_url")} for f in FAQ if f["lang"] == "en"]
    return OrderedDict([
        ("$schema", URLS["agent_json"]),
        ("datePublished", "2026-05-26"), ("generated", TODAY), ("version", VERSION), ("source", SITE + "/"),
        ("languages", ["en", "sv"]),
        ("note", f"Machine-readable reference data for GO MO Group, compiled from a full crawl of gomogroup.com ({len(PAGES)} URLs, English + Swedish). Quote figures exactly; keep anonymised clients anonymised. For filterable data use the REST API."),
        ("organization", OrderedDict(name=co["name"], legalName=co["legalName"], url=SITE + "/", founded=co["founded"], slogan=co.get("tagline"), slogan_sv=co.get("tagline_sv"),
                                     description=co["description"], description_sv=co.get("description_sv"),
                                     industry="B2B Digital Marketing, Generative Engine Optimization, Generative AI Implementation",
                                     parentAffiliation=co.get("parent"), employees=co.get("employees"), trackRecord=co.get("projects"),
                                     certifications=co.get("certifications", []), clientsNamed=co.get("clients_named", []),
                                     email=co["contact"]["general_email"], social={k: v for k, v in co.get("social", {}).items() if v and isinstance(v, str)})),
        ("offices", [{"city": o["city"], "country": o["countryCode"], "role": o["label"], "address": f"{o['streetAddress']}, {o['postalCode']} {o['city']}, {o['country']}", "email": o.get("email")} for o in OFFICES]),
        ("leadership", [{"name": p["name"], "role": p.get("role"), "email": p.get("email"), "phone": p.get("telephone"), "url": p.get("url"), "url_sv": p.get("url_sv")} for p in LEADERS]),
        ("services", [{"id": s["id"], "name": s["name"], "name_sv": s.get("name_sv"), "url": s.get("url"), "url_sv": s.get("url_sv"), "category": s["category"], "summary": s["summary"],
                       "pricing": tier_txt(s) if any(t.get("price_from") for t in s["pricing"].get("tiers", [])) else "quote-based"} for s in SERVICES]),
        ("solutions", [{"id": s["id"], "name": s["name"], "url": s.get("url"), "url_sv": s.get("url_sv"), "summary": s["summary"]} for s in SOLUTIONS]),
        ("caseStudies", [{"id": c["id"], "name": c["headline"], "client": c["client"], "url": c.get("url"), "url_sv": c.get("url_sv"), "segment": c.get("segment"), "industry": c.get("industry"),
                          "results": [f"{r['value']} {r['metric']}" + (f" ({r['context']})" if r.get("context") else "") for r in c["results"]]} for c in CASES]),
        ("industries", [i for i, _ in Counter(c["industry"] for c in CASES if c.get("industry")).most_common()]),
        ("eventsNote", f"Status as of {TODAY}. For current events see {kp.get('events_en')}"),
        ("events", [{"name": ev["title"], "date": (ev.get("startDate") or "")[:10], "location": ev.get("location"), "status": ev.get("status"), "url": ev.get("url"), "url_sv": ev.get("url_sv")} for ev in sorted_events()]),
        ("openRoles", [{"title": j["title"], "location": j.get("location"), "url": j.get("url"), "apply": j.get("apply_url")} for j in CAREERS]),
        ("insights", {"total": len(INSIGHTS), "en": len(EN_INSIGHTS), "sv": len(SV_INSIGHTS), "latest": [{"title": i["title"], "lang": i["lang"], "date": i["datePublished"], "url": i["url"]} for i in INSIGHTS[:10]],
                      "all": f"{API}/insights.json"}),
        ("keyPages", OrderedDict(home=kp.get("home_en"), home_sv=kp.get("home_sv"), about=kp.get("about_en"), about_sv=kp.get("about_sv"), contact=kp.get("contact_en"), contact_sv=kp.get("contact_sv"),
                                 careers=kp.get("careers_en"), careers_sv=kp.get("careers_sv"), insights=kp.get("insights_en"), insights_sv=kp.get("insights_sv"), cases=kp.get("cases_en"), cases_sv=kp.get("cases_sv"),
                                 events=kp.get("events_en"), geoPlaybook=kp.get("geo_playbook_en"), geoPlaybook_sv=kp.get("geo_playbook_sv"),
                                 scaleups=next((s["url"] for s in SOLUTIONS if s["id"] == "scale-ups"), None))),
        ("machineReadable", OrderedDict(agentPage=URLS["agent_page"], agentJson=URLS["agent_json"], llmsTxt=URLS["llms_txt"], aiTxt=URLS["ai_txt"], sitemap=URLS["sitemap"],
                                        restApiIndex=URLS["api_index"], openapi=URLS["openapi"], apiCatalog=URLS["api_catalog"], mcpServer=URLS["mcp"])),
        ("faq", faq),
    ])

def main():
    counts = write_data()
    (API_DIR / "openapi.json").write_text(json.dumps(openapi(), ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    outputs = {
        "agent-page.html": agent_page(),
        "agent-index.json": json.dumps(agent_index_json(), ensure_ascii=False, indent=2) + "\n",
        "llms.txt": llms_txt(),
        "ai.txt": ai_txt(),
        "api-catalog.json": json.dumps(api_catalog(), ensure_ascii=False, indent=2) + "\n",
        "robots-addition.txt": robots_addition(),
    }
    for name, text in outputs.items():
        (OUT / name).write_text(text, encoding="utf-8")
        if name != "robots-addition.txt":
            shutil.copyfile(OUT / name, API_DIR / name)
    print("data:", counts)
    for name in list(outputs) + ["gomo-api/openapi.json"]:
        p = OUT / name
        print(f"{name}: {p.stat().st_size:,} bytes")

if __name__ == "__main__":
    main()
