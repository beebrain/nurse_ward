#!/usr/bin/env bash
# Export nurse_ward database for deployment.
# Set the same values as in app .env (CI4 .env is not bash-sourceable).
#
# Usage:
#   export DB_HOST=127.0.0.1 DB_USER=root DB_PASS=secret DB_NAME=nurse_ward
#   ./scripts/export-db.sh [output.sql]
#
# On Docker server (php connects to service "mariadb"):
#   export DB_HOST=mariadb DB_USER=root DB_PASS=... DB_NAME=nurse_ward
#   docker exec -i mariadb sh -lc 'mysqldump ...'   # or run this script on host with -h127.0.0.1 -P3306

set -euo pipefail
OUT="${1:-nurse_ward_export_$(date +%Y%m%d_%H%M%S).sql}"

HOST="${DB_HOST:-localhost}"
USER="${DB_USER:-root}"
PASS="${DB_PASS:-}"
PORT="${DB_PORT:-3306}"
NAME="${DB_NAME:-nurse_ward}"

if command -v mysqldump >/dev/null 2>&1; then
  MYSQLDUMP=(mysqldump)
else
  echo "mysqldump not found. Install mariadb-client or mysql-client." >&2
  exit 1
fi

echo "Exporting ${NAME} -> ${OUT}" >&2
MYSQL_PWD="${PASS}" "${MYSQLDUMP[@]}" \
  --single-transaction \
  --routines \
  --default-character-set=utf8mb4 \
  -h"${HOST}" -P"${PORT}" -u"${USER}" \
  "${NAME}" > "${OUT}"

echo "Done: ${OUT}" >&2
