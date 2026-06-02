#!/usr/bin/env bash
# Pull nurse_ward database from production (beebrain) into local shared_mysql.
#
# Usage:
#   bash scripts/pull-db.sh
#
# Requires: ssh beebrain, docker (shared_mysql), .env with database.default.*

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
ENV_FILE="$ROOT/.env"
BACKUP_DIR="$ROOT/backups"

REMOTE_SSH="${REMOTE_SSH:-beebrain}"
REMOTE_MARIADB="${REMOTE_MARIADB:-}"
LOCAL_MYSQL="${LOCAL_MYSQL:-shared_mysql}"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing $ENV_FILE" >&2
  exit 1
fi

DB_PASS="$(grep -E '^database\.default\.password' "$ENV_FILE" | head -1 | cut -d= -f2- | tr -d ' \r\n' | sed 's/^=//')"
DB_NAME="$(grep -E '^database\.default\.database' "$ENV_FILE" | head -1 | awk -F= '{print $2}' | tr -d ' \r\n')"
DB_NAME="${DB_NAME:-nurse_ward}"

mkdir -p "$BACKUP_DIR"
DUMP="$BACKUP_DIR/nurse_ward_prod_$(date +%Y%m%d_%H%M%S).sql"

if [[ -z "$REMOTE_MARIADB" ]]; then
  REMOTE_MARIADB="$(ssh "$REMOTE_SSH" "docker ps --format '{{.Names}}' | grep -i mariadb | head -1")"
fi
[[ -n "$REMOTE_MARIADB" ]] || { echo "No mariadb container on $REMOTE_SSH" >&2; exit 1; }

echo "==> Dump $DB_NAME from $REMOTE_SSH ($REMOTE_MARIADB) -> $DUMP"
ssh "$REMOTE_SSH" "docker exec $REMOTE_MARIADB mysqldump -uroot --single-transaction --routines --default-character-set=utf8mb4 $DB_NAME" > "$DUMP"
echo "    $(du -sh "$DUMP" | cut -f1)"

echo "==> Import into local $LOCAL_MYSQL"
docker exec "$LOCAL_MYSQL" mysql -uroot -p"${DB_PASS}" -e \
  "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
docker exec -i "$LOCAL_MYSQL" mysql -uroot -p"${DB_PASS}" "$DB_NAME" < "$DUMP"

echo "==> Verify ward_api_aliases"
docker exec "$LOCAL_MYSQL" mysql -uroot -p"${DB_PASS}" "$DB_NAME" -e \
  "SELECT COUNT(*) AS ward_api_aliases_rows FROM ward_api_aliases;"

echo "==> Done. Dump kept at: $DUMP"
