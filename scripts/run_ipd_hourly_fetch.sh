#!/usr/bin/env bash
# รันดึง IPD census รายชั่วโมง — ใช้กับ cron: 0 * * * *
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
LOG_DIR="$PROJECT_ROOT/writable/logs"
LOG_FILE="$LOG_DIR/ipd_hourly_fetch.log"
LOCK_FILE="$PROJECT_ROOT/writable/cache/ipd_hourly_fetch.lock"
VENV_PYTHON="$PROJECT_ROOT/.venv/bin/python"
FETCH_SCRIPT="$SCRIPT_DIR/fetch_ipd_hourly.py"

mkdir -p "$LOG_DIR" "$(dirname "$LOCK_FILE")"

log() {
    printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S %z')" "$*" >> "$LOG_FILE"
}

if ! mkdir "$LOCK_FILE" 2>/dev/null; then
    log "SKIP already running (lock: $LOCK_FILE)"
    exit 0
fi
trap 'rmdir "$LOCK_FILE" 2>/dev/null || true' EXIT

if [[ ! -f "$FETCH_SCRIPT" ]]; then
    log "ERROR fetch script not found: $FETCH_SCRIPT"
    exit 1
fi

PYTHON=""
if [[ -x "$VENV_PYTHON" ]]; then
    PYTHON="$VENV_PYTHON"
elif command -v python3 >/dev/null 2>&1; then
    PYTHON="python3"
else
    log "ERROR python3 not found"
    exit 1
fi

if ! "$PYTHON" -c "import pymysql" 2>/dev/null; then
    if [[ -x "$PROJECT_ROOT/.venv/bin/pip" ]]; then
        log "Installing pymysql into .venv"
        "$PROJECT_ROOT/.venv/bin/pip" install -q -r "$SCRIPT_DIR/requirements.txt"
    else
        log "ERROR pymysql not installed. Run: python3 -m venv .venv && .venv/bin/pip install -r scripts/requirements.txt"
        exit 1
    fi
fi

log "START fetch via $PYTHON"
if "$PYTHON" "$FETCH_SCRIPT" --quiet >> "$LOG_FILE" 2>&1; then
    log "OK completed"
    exit 0
fi

log "ERROR fetch failed (exit $?)"
exit 1
