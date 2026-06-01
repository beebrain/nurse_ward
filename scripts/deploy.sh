#!/usr/bin/env bash
# Deploy: git pull + spark migrate on production
# Usage: bash scripts/deploy.sh
# Requires: ssh beebrain  (see ~/.ssh/config)
set -euo pipefail

REMOTE_SSH="${REMOTE_SSH:-beebrain}"
REMOTE_PATH="${REMOTE_PATH:-/home/beebrain/caddy-docker/site/nurse_ward}"
PHP_CONTAINER="${PHP_CONTAINER:-php-fpm}"

echo "==> Deploy via ssh $REMOTE_SSH"

ssh "$REMOTE_SSH" "set -e
  cd '$REMOTE_PATH'
  echo '--- git pull ---'
  git pull origin master
  echo '--- migrate ---'
  docker exec $PHP_CONTAINER php /var/www/html/nurse_ward/spark migrate
  echo '--- done ---'
  git log --oneline -1
"

echo "==> Deploy complete"
