#!/usr/bin/env bash
# Runs every test: plugin router (PHP), sync merge (PHP), generated outputs (Python), MCP protocol (Node).
# Needs: php 7.4+, python3, node 18+ (and `npm install && npm run build` in gomo-mcp/ once).
cd "$(dirname "$0")" || exit 1
status=0
run() { echo; echo "═══ $1"; shift; "$@" || status=1; }
run "Plugin router"     php tests/php/router_test.php
run "Sync merge"        php tests/php/sync_merge_test.php
run "Snapshot publication" php tests/php/snapshot_test.php
run "Generated outputs" python3 tests/test_outputs.py
run "MCP protocol"      bash tests/mcp_smoke.sh
echo; [ $status -eq 0 ] && echo "ALL TESTS PASSED" || echo "SOME TESTS FAILED"
exit $status
