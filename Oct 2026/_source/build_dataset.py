"""Merge Firecrawl markdown + raw-HTML metadata into one page dataset (pages.json)."""
import json, re, sys
S = sys.argv[1]
fc = json.load(open(f"{S}/fc.json"))
meta = json.load(open(f"{S}/meta.json"))
B = "https://www.gomogroup.com/"

def key(url):
    k = url.replace(B, "").strip("/").replace("/", "__")
    return (k or "home") + ".html"

def classify(path):
    p = path.strip("/").split("/")
    lang = "en" if p and p[0] == "en" else "sv"
    if lang == "en": p = p[1:]
    head = p[0] if p else ""
    t = {"services": "service", "vara-tjanster": "service", "solution": "solution",
         "case": "case", "insight": "insight", "insikter": "insight", "team": "team",
         "medarbetare": "team", "career": "career", "karriar": "career",
         "event": "event", "videos": "video"}.get(head, "page")
    if len(p) == 1 and t != "page":  # archive/index pages like /en/case/, /karriar/
        t = "page"
    if head in ("privacy-policy", "cookie-policy", "code-of-conduct", "gdpr", "cookies-regler"):
        t = "legal"
    return lang, t, (p[-1] if p else "home")

pages = []
for d in fc:
    md = d.get("metadata", {})
    url = md.get("sourceURL") or md.get("url")
    if md.get("statusCode", 200) >= 400: continue
    m = meta.get(key(url), {})
    path = url.replace(B, "/")
    lang, typ, slug = classify(path)
    pages.append({
        "url": m.get("canonical") or url, "lang": lang, "type": typ, "slug": slug,
        "title": (m.get("title") or md.get("title") or "").replace(" | GO MO Group", "").replace(" - GO MO Group", "").strip(),
        "description": m.get("desc") or md.get("description"),
        "published": (m.get("datePublished") or "")[:10] or None,
        "modified": (m.get("dateModified") or m.get("modified") or "")[:10] or None,
        "author": m.get("author"),
        "alternate": m.get("alt"),
        "markdown": d.get("markdown", ""),
    })
pages.sort(key=lambda p: (p["type"], p["lang"], p["url"]))
json.dump(pages, open("pages.json", "w"), ensure_ascii=False, indent=1)
from collections import Counter
print(len(pages), Counter((p["lang"], p["type"]) for p in pages))
