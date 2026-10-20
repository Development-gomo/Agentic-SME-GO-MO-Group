"""Validates the generated package: python3 tests/test_outputs.py"""
import json, re, glob, collections, filecmp, sys
from pathlib import Path
R = Path(__file__).resolve().parent.parent
fails = []
def check(name, ok, detail=""):
    print(("PASS " if ok else "FAIL ") + name + ("" if ok or not detail else f"  — {detail}"))
    if not ok: fails.append(name)

files = list(R.glob("*.json")) + list((R / "gomo-api").glob("*.json")) + list((R / "gomo-api/data").glob("*.json"))
bad = []
for f in files:
    try: json.load(open(f))
    except Exception as e: bad.append(f"{f.name}: {e}")
check(f"{len(files)} JSON files parse", not bad, "; ".join(bad))
D = lambda n: json.load(open(R / f"gomo-api/data/{n}.json"))["data"]
for n, key in (("services", "id"), ("case-studies", "id"), ("insights", "id"), ("team", "id"), ("pages", "url")):
    c = collections.Counter(x[key] for x in D(n)); d = [k for k, v in c.items() if v > 1]
    check(f"{n}: unique {key}s", not d, str(d[:5]))
check("ids are URL-safe", all(re.fullmatch(r"[a-z0-9-]+", x["id"]) for n in ("services", "case-studies", "insights") for x in D(n)))
pg = D("pages"); pu = {x["url"]: x for x in pg}
check("page alternates exist and are symmetric", all(not x.get("alternate") or pu.get(x["alternate"], {}).get("alternate") == x["url"] for x in pg))
L = (R / "llms.txt").read_text()
links = re.findall(r"\[[^\]]*\]\(https?://[^)]+\)", L)
check("llms.txt starts with H1", L.startswith("# "))
check("llms.txt has no bare URLs", not [l for l in L.splitlines() if re.search(r"(?<!\()https?://", l)])
H = (R / "agent-page.html").read_text()
ids = re.findall(r'\sid="([^"]+)"', H)
check("agent page: unique ids", len(ids) == len(set(ids)))
check("agent page: in-page anchors resolve", not [h for h in set(re.findall(r'href="#([^"]+)"', H)) if h not in set(ids)])
ld = json.loads(re.search(r'application/ld\+json">(.*?)</script>', H, re.S).group(1).replace("<\\/", "</"))
check("agent page: JSON-LD parses with Organization", ld["@graph"][0]["@type"] == "Organization")
check("agent page: dark only", "prefers-color-scheme" not in H and 'content="dark"' in H)
check("agent page lists every page-index URL", not [u for u in pu if u not in H])
for f in ("agent-page.html", "agent-index.json", "llms.txt", "ai.txt", "api-catalog.json"):
    check(f"plugin copy of {f} in sync", filecmp.cmp(R / f, R / "gomo-api" / f, shallow=False))
legacy = ("wp-content/plugins", '"api/v1/', "synced from WordPress")
offenders = [p.name for p in [R / f for f in ("agent-page.html", "agent-index.json", "llms.txt", "ai.txt", "api-catalog.json", "robots-addition.txt")] + list((R / "gomo-api/data").glob("*.json")) if any(s in p.read_text(errors="ignore") for s in legacy)]
check("no legacy URLs in outputs", not offenders, str(offenders))
dep = (R / "DEPLOYMENT.md").read_text()
m = re.search(r"(\d+) Markdown links", dep)
check("DEPLOYMENT.md llms link count matches", m and int(m.group(1)) == len(links), f"doc {m and m.group(1)} vs actual {len(links)}")
m = re.search(r"(\d+)-URL page index", dep)
check("DEPLOYMENT.md page-index count matches", m and int(m.group(1)) == len(pg), f"doc {m and m.group(1)} vs actual {len(pg)}")
print(f"\n{'FAILED: ' + ', '.join(fails) if fails else 'all output checks passed'}")
sys.exit(1 if fails else 0)
