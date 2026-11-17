"""Build _source/pages.json and _source/md/ from a Firecrawl crawl.

Usage:  python3 build_dataset.py <crawl-dir>
<crawl-dir>/records.json holds one record per scraped URL (Firecrawl batch scrape with
markdown + rawHtml, metadata pulled from the raw HTML: canonical, robots, title,
description, dates, hreflang). Only final, indexable 200 URLs are kept, plus the
noindex team profiles listed in KEEP_NOINDEX. Language pairs come from WPML hreflang;
where a page carries no hreflang pair, the previous build's pair is kept if both URLs
are still live.
"""
import json, re, sys
from pathlib import Path
HERE = Path(__file__).resolve().parent
B = "https://www.gomogroup.com/"
KEEP_NOINDEX = {B + p for p in ("en/team/isabelle-coquet/", "medarbetare/isabelle-coquet/",
                                "en/team/oliver-strang-paschetto/", "medarbetare/oliver-strang-paschetto/")}

def classify(path):
    p = path.strip("/").split("/")
    lang = "en" if p and p[0] == "en" else "sv"
    if lang == "en": p = p[1:]
    head = p[0] if p else ""
    t = {"services": "service", "vara-tjanster": "service", "solution": "solution",
         "case": "case", "insight": "insight", "insikter": "insight", "team": "team",
         "medarbetare": "team", "career": "career", "karriar": "career",
         "event": "event", "videos": "video"}.get(head, "page")
    if len(p) == 1 and t != "page":
        t = "page"
    if head in ("privacy-policy", "cookie-policy", "code-of-conduct", "gdpr", "cookies-regler"):
        t = "legal"
    return lang, t, (p[-1] if p else "home")

def persons(node, out):
    if isinstance(node, dict):
        if node.get("@type") == "Person" and node.get("name"):
            out.append(node["name"])
        for v in node.values(): persons(v, out)
    elif isinstance(node, list):
        for v in node: persons(v, out)
    return out

def slash(u): return (u or "").split("#")[0].split("?")[0].rstrip("/") + "/"

recs = json.load(open(Path(sys.argv[1]) / "records.json", encoding="utf-8"))
previous = {p["url"]: p for p in json.load(open(HERE / "pages.json", encoding="utf-8"))}
pages = []
for r in recs:
    u = slash(r["source"])
    if (r["status"] or 0) != 200 or slash(r["final"]) != u or r.get("challenge"):
        continue
    noindex = "noindex" in (r.get("robots") or "")
    if noindex and u not in KEEP_NOINDEX:
        continue
    lang, typ, slug = classify(u.replace(B, "/"))
    title = re.sub(r"\s*[|–-]\s*GO MO Group\s*$", "", r.get("title") or "").strip()
    row = {"url": u, "lang": lang, "type": typ, "slug": slug, "title": title,
           "description": r.get("description") or None, "published": r.get("published"),
           "modified": r.get("modified"),
           "author": (lambda n: n[0] if typ == "insight" and len(set(n)) == 1 else None)(persons(r.get("ld"), [])), "alternate": None,
           "_hreflang": {l: slash(h) for l, h in r.get("hreflang") or [] if l in ("en", "sv")},
           "markdown": r.get("markdown", "")}
    if noindex: row["robots"] = "noindex, follow"
    pages.append(row)

by = {p["url"]: p for p in pages}
for p in pages:
    other = "sv" if p["lang"] == "en" else "en"
    target = p["_hreflang"].get(other)
    if target in by and target != p["url"]:
        p["alternate"] = {"lang": other, "url": target}
    elif not target or target not in by:
        old = (previous.get(p["url"]) or {}).get("alternate")
        if old and old.get("url") in by:
            p["alternate"] = old
for p in pages:  # symmetric pairs
    a = p["alternate"]
    if a and not by[a["url"]]["alternate"]:
        by[a["url"]]["alternate"] = {"lang": p["lang"], "url": p["url"]}
for p in pages:
    p.pop("_hreflang")

pages.sort(key=lambda p: (p["type"], p["lang"], p["url"]))
json.dump(pages, open(HERE / "pages.json", "w", encoding="utf-8"), ensure_ascii=False, indent=1)
md = HERE / "md"
for f in md.glob("*.md"): f.unlink()
for p in pages:
    key = p["url"].replace(B, "").strip("/").replace("/", "__") or "home"
    (md / f"{key}.md").write_text(p["markdown"], encoding="utf-8")
from collections import Counter
print(len(pages), sorted(Counter((p["lang"], p["type"]) for p in pages).items()))
print("paired:", sum(1 for p in pages if p["alternate"]))
