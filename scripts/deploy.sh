#!/usr/bin/env bash
# Deploy nurse_ward to production (git pull + migrate + hourly cron)
set -euo pipefail

REMOTE_HOST="${REMOTE_HOST:-beebrain.duckdns.org}"
REMOTE_USER="${REMOTE_USER:-beebrain}"
REMOTE_PORT="${REMOTE_PORT:-1112}"
REMOTE_PATH="${REMOTE_PATH:-/home/beebrain/caddy-docker/site/nurse_ward}"
PHP_CONTAINER="${PHP_CONTAINER:-php-fpm}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Load SSH password from .env if present
ENV_FILE="$PROJECT_ROOT/.env"
if [[ -f "$ENV_FILE" ]]; then
    while IFS='=' read -r key val; do
        [[ "$key" =~ ^#.*$ || -z "$key" ]] && continue
        key="${key// /}"
        val="${val// /}"
        val="${val#\'}"; val="${val%\'}"
        val="${val#\"}"; val="${val%\"}"
        case "$key" in
            SSH_PASSWORD) SSH_PASS="${SSH_PASS:-$val}" ;;
        esac
    done < "$ENV_FILE"
fi

ssh_cmd() {
    if command -v sshpass &>/dev/null && [[ -n "${SSH_PASS:-}" ]]; then
        sshpass -p "$SSH_PASS" ssh -p "$REMOTE_PORT" -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" "$@"
    else
        ssh -p "$REMOTE_PORT" -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" "$@"
    fi
}

echo "==> Deploy to ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}"

ssh_cmd "set -e
  cd '$REMOTE_PATH'
  git fetch origin master
  git pull origin master
  docker exec $PHP_CONTAINER php /var/www/html/nurse_ward/spark migrate --all
  chmod +x scripts/run_ipd_hourly_fetch.sh scripts/install-hourly-cron.sh scripts/fetch_ipd_hourly.py 2>/dev/null || true
  if command -v python3 >/dev/null; then
    python3 -m venv .venv 2>/dev/null || true
    .venv/bin/pip install -q -r scripts/requirements.txt 2>/dev/null || true
  fi
  bash scripts/install-hourly-cron.sh 2>/dev/null || true
  bash scripts/run_ipd_hourly_fetch.sh
  echo '--- migrations (last 5) ---'
  docker exec $PHP_CONTAINER php /var/www/html/nurse_ward/spark migrate:status 2>/dev/null | tail -8 || true
"

echo "==> Deploy complete"
