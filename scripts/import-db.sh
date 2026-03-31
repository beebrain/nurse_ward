#!/usr/bin/env bash
# Import SQL into nurse_ward (target server).
# Usage:
#   DB_HOST=mariadb DB_USER=root DB_PASS=secret DB_NAME=nurse_ward ./scripts/import-db.sh dump.sql

set -euo pipefail
SQL="${1:-}"
if [[ -z "${SQL}" ]] || [[ ! -f "${SQL}" ]]; then
  echo "Usage: $0 path/to/dump.sql" >&2
  exit 1
fi

HOST="${DB_HOST:-localhost}"
USER="${DB_USER:-root}"
PASS="${DB_PASS:-}"
PORT="${DB_PORT:-3306}"
NAME="${DB_NAME:-nurse_ward}"

if ! command -v mysql >/dev/null 2>&1; then
  echo "mysql client not found. Install mariadb-client or mysql-client." >&2
  exit 1
fi

echo "Importing ${SQL} -> ${NAME} @ ${HOST}" >&2
MYSQL_PWD="${PASS}" mysql \
  --default-character-set=utf8mb4 \
  -h"${HOST}" -P"${PORT}" -u"${USER}" \
  "${NAME}" < "${SQL}"

echo "Done." >&2
