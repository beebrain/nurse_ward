#!/usr/bin/env bash
# ติดตั้ง cron ดึง IPD ทุกชั่วโมง (นาฬิกาไทย)
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
RUNNER="$SCRIPT_DIR/run_ipd_hourly_fetch.sh"
MARKER="# nurse_ward ipd hourly fetch"

chmod +x "$RUNNER" "$SCRIPT_DIR/fetch_ipd_hourly.py"

# เตรียม venv ถ้ายังไม่มี
if [[ ! -x "$PROJECT_ROOT/.venv/bin/python" ]]; then
    echo "Creating Python venv..."
    python3 -m venv "$PROJECT_ROOT/.venv"
    "$PROJECT_ROOT/.venv/bin/pip" install -q -r "$SCRIPT_DIR/requirements.txt"
fi

CRON_LINE="CRON_TZ=Asia/Bangkok"
CRON_LINE="$CRON_LINE"$'\n'"0 * * * * $RUNNER $MARKER"

EXISTING="$(crontab -l 2>/dev/null || true)"
if echo "$EXISTING" | grep -Fq "$MARKER"; then
    echo "Cron entry already installed."
else
    {
        echo "$EXISTING" | sed '/^$/d'
        echo "$CRON_LINE"
    } | crontab -
    echo "Installed hourly cron (Asia/Bangkok, minute 0 every hour)."
fi

echo ""
echo "Runner: $RUNNER"
echo "Log:    $PROJECT_ROOT/writable/logs/ipd_hourly_fetch.log"
echo ""
echo "Test now:"
echo "  $RUNNER"
