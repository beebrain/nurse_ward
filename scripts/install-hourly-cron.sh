#!/usr/bin/env bash
# ติดตั้ง cron ดึง IPD ทุก 30 นาที (นาฬิกาไทย)
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
RUNNER="$SCRIPT_DIR/run_ipd_hourly_fetch.sh"
MARKER="# nurse_ward ipd hourly fetch"

chmod +x "$RUNNER" "$SCRIPT_DIR/fetch_ipd_hourly.py"

if [[ ! -x "$PROJECT_ROOT/.venv/bin/python" ]]; then
    echo "Creating Python venv..."
    python3 -m venv "$PROJECT_ROOT/.venv"
    "$PROJECT_ROOT/.venv/bin/pip" install -q -r "$SCRIPT_DIR/requirements.txt"
fi

CRON_BODY="*/30 * * * * $RUNNER $MARKER"

EXISTING="$(crontab -l 2>/dev/null | grep -Fv "$MARKER" | grep -Fv "$RUNNER" || true)"
HAS_TZ="$(echo "$EXISTING" | grep -c '^CRON_TZ=Asia/Bangkok' || true)"

{
    echo "$EXISTING" | sed '/^$/d'
    if [[ "$HAS_TZ" -eq 0 ]]; then
        echo "CRON_TZ=Asia/Bangkok"
    fi
    echo "$CRON_BODY"
} | crontab -

echo "Installed cron: every 30 minutes (Asia/Bangkok)."
echo ""
echo "Runner: $RUNNER"
echo "Log:    $PROJECT_ROOT/writable/logs/ipd_hourly_fetch.log"
echo ""
echo "Test now:"
echo "  $RUNNER"
