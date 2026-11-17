#!/usr/bin/env bash
# MCP protocol smoke test against the generated data files (no network). Requires Node 18+ and a built gomo-mcp/dist.
set -u
R="$(cd "$(dirname "$0")/.." && pwd)"
DATA_PORT=8931; MCP_PORT=3918; fail=0
python3 -m http.server $DATA_PORT -d "$R/gomo-api/data" >/dev/null 2>&1 & DATA_PID=$!
GOMO_API_BASE=http://localhost:$DATA_PORT PORT=$MCP_PORT node "$R/gomo-mcp/dist/index.js" >/dev/null 2>&1 & MCP_PID=$!
trap 'kill $DATA_PID $MCP_PID 2>/dev/null' EXIT
for _ in $(seq 50); do curl -s "localhost:$MCP_PORT/mcp/health" >/dev/null && break; sleep 0.2; done
rpc() { curl -s -X POST "localhost:$MCP_PORT/mcp" -H 'Content-Type: application/json' -H 'Accept: application/json, text/event-stream' -d "$1" | sed -n 's/^data: //p'; }
check() { if [ "$2" = "0" ]; then echo "PASS $1"; else echo "FAIL $1"; fail=1; fi; }
curl -s "localhost:$MCP_PORT/mcp/health" | grep -q '"status":"ok"'; check "health ok" $?
[ "$(curl -s localhost:$MCP_PORT/mcp/tools | python3 -c 'import json,sys;print(len(json.load(sys.stdin)["tools"]))')" = "12" ]; check "discovery lists 12 tools" $?
curl -fsS "localhost:$MCP_PORT/tools" | python3 -c 'import json,sys; d=json.load(sys.stdin); assert d["endpoint"]=="https://agent-mcp.gomogroup.com/mcp" and len(d["tools"])==12'; check "root tools advertises new MCP hostname" $?
curl -fsS "localhost:$MCP_PORT/health" | grep -q '"status":"ok"'; check "root health alias works" $?
[ "$(rpc '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | python3 -c 'import json,sys;print(len(json.load(sys.stdin)["result"]["tools"]))')" = "12" ]; check "tools/list returns 12" $?
rpc '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"get_company","arguments":{}}}' | python3 -c 'import json,sys;r=json.load(sys.stdin);d=json.loads(r["result"]["content"][0]["text"]);assert d["data"]["name"]=="GO MO Group"'; check "get_company returns data" $?
rpc '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"list_insights","arguments":{}}}' | python3 -c 'import json,sys;r=json.load(sys.stdin);d=json.loads(r["result"]["content"][0]["text"]);assert len(d["data"])>100'; check "list_insights returns data" $?
rpc '{"jsonrpc":"2.0","id":4,"method":"tools/call","params":{"name":"submit_contact_inquiry","arguments":{"person":{"name":"T","email":"t@example.com"},"company":{"name":"X"},"inquiry":{"service_interest":"GEO","project_summary":"consent gate test"},"agent_context":{"user_authorized_submission":false}}}}' | grep -q 'expected true'; check "contact without consent rejected by schema" $?
codes=$(for i in $(seq 110); do curl -s -o /dev/null -w '%{http_code}\n' "localhost:$MCP_PORT/mcp/health"; done | sort -u | tr '\n' ' ')
[ "$codes" = "200 " ]; check "health is not rate-limited ($codes)" $?
codes=$(for i in $(seq 105); do curl -s -o /dev/null -w '%{http_code}\n' -X POST "localhost:$MCP_PORT/mcp" -H 'Content-Type: application/json' -H 'Accept: application/json, text/event-stream' -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'; done | grep -c 429)
[ "$codes" -ge 1 ]; check "/mcp rate-limited after 100/min" $?
# Proxy trust: with TRUST_PROXY=1 (Render), clients behind the proxy get separate rate-limit buckets.
PROXY_PORT=3919
GOMO_API_BASE=http://localhost:$DATA_PORT PORT=$PROXY_PORT TRUST_PROXY=1 node "$R/gomo-mcp/dist/index.js" >/dev/null 2>&1 & PROXY_PID=$!
trap 'kill $DATA_PID $MCP_PID $PROXY_PID 2>/dev/null' EXIT
for _ in $(seq 50); do curl -s "localhost:$PROXY_PORT/mcp/health" >/dev/null && break; sleep 0.2; done
remaining() { curl -s -o /dev/null -D - -X POST "localhost:$PROXY_PORT/mcp" -H "X-Forwarded-For: $1" -H 'Content-Type: application/json' -H 'Accept: application/json, text/event-stream' -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | tr -d '\r' | awk -F': ' 'tolower($1)=="ratelimit-remaining"{print $2}'; }
a1=$(remaining 203.0.113.10); a2=$(remaining 203.0.113.10); b1=$(remaining 198.51.100.20)
[ "$a1" = "99" ] && [ "$a2" = "98" ] && [ "$b1" = "99" ]; check "TRUST_PROXY=1 gives each client its own bucket ($a1/$a2/$b1)" $?
TRUST_PROXY=true PORT=3920 node "$R/gomo-mcp/dist/index.js" >/dev/null 2>&1; [ $? -ne 0 ]; check "TRUST_PROXY=true is refused" $?
exit $fail
