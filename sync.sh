#!/usr/bin/env bash
# =============================================================================
# sync.sh — Sync nurse_ward from local (XAMPP) to remote server
#
# Usage:
#   bash sync.sh              # interactive: asks for server if not configured
#   bash sync.sh db           # sync database only
#   bash sync.sh files        # sync files only
#   bash sync.sh all          # sync database + files (default)
#
# Config (edit below or set as environment variables before running):
#   REMOTE_HOST   — server IP or hostname
#   REMOTE_USER   — SSH username (default: root)
#   REMOTE_PORT   — SSH port (default: 22)
#   REMOTE_PATH   — destination path on server (default: /var/www/html/nurse_ward)
#   DB_NAME       — database name (default: nurse_ward)
#   DB_USER       — local MySQL user (default: root)
#   DB_PASS       — local MySQL password (default: empty)
#   DB_REMOTE_USER — remote MySQL user (default: root)
#   DB_REMOTE_PASS — remote MySQL password
#   SSH_PASS      — SSH password (uses sshpass if set; prefer SSH key)
# =============================================================================

set -euo pipefail

# ── Load .env if present ─────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="$SCRIPT_DIR/.env"

if [[ -f "$ENV_FILE" ]]; then
    # Parse key=value lines (ignore comments and blank lines)
    while IFS='=' read -r key val; do
        [[ "$key" =~ ^#.*$ || -z "$key" ]] && continue
        key="${key// /}"
        val="${val// /}"
        # Only export known sync-related vars if not already set
        case "$key" in
            SSH_PASSWORD)    export SSH_PASS="${SSH_PASS:-$val}" ;;
            DB_REMOTE_PASSWORD) export DB_REMOTE_PASS="${DB_REMOTE_PASS:-$val}" ;;
        esac
    done < "$ENV_FILE"
fi

# ── Configuration (override via env vars or edit defaults here) ───────────────
REMOTE_HOST="${REMOTE_HOST:-}"
REMOTE_USER="${REMOTE_USER:-root}"
REMOTE_PORT="${REMOTE_PORT:-22}"
REMOTE_PATH="${REMOTE_PATH:-/var/www/html/nurse_ward}"
DB_NAME="${DB_NAME:-nurse_ward}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_REMOTE_USER="${DB_REMOTE_USER:-root}"
DB_REMOTE_PASS="${DB_REMOTE_PASS:-}"
SSH_PASS="${SSH_PASS:-}"

# ── Colors ────────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()    { echo -e "${CYAN}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

# ── Prompt for server if not configured ───────────────────────────────────────
if [[ -z "$REMOTE_HOST" ]]; then
    echo ""
    echo -e "${CYAN}=== Nurse Ward Sync Tool ===${NC}"
    read -rp "Remote server IP / hostname: " REMOTE_HOST
    [[ -z "$REMOTE_HOST" ]] && error "Remote host is required."
fi

MODE="${1:-all}"
DUMP_FILE="/tmp/nurse_ward_$(date +%Y%m%d_%H%M%S).sql"

# ── SSH helper (with or without sshpass) ─────────────────────────────────────
ssh_cmd() {
    if command -v sshpass &>/dev/null && [[ -n "$SSH_PASS" ]]; then
        sshpass -p "$SSH_PASS" ssh -p "$REMOTE_PORT" -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" "$@"
    else
        ssh -p "$REMOTE_PORT" "$REMOTE_USER@$REMOTE_HOST" "$@"
    fi
}

rsync_cmd() {
    if command -v sshpass &>/dev/null && [[ -n "$SSH_PASS" ]]; then
        sshpass -p "$SSH_PASS" rsync "$@"
    else
        rsync "$@"
    fi
}

# ── Sync database ─────────────────────────────────────────────────────────────
sync_db() {
    info "Dumping local database '${DB_NAME}'..."

    local MYSQL_PASS_ARG=""
    [[ -n "$DB_PASS" ]] && MYSQL_PASS_ARG="-p${DB_PASS}"

    if ! command -v mysqldump &>/dev/null; then
        # Try XAMPP path on Windows Git Bash
        MYSQLDUMP="/c/xampp/mysql/bin/mysqldump.exe"
        [[ ! -f "$MYSQLDUMP" ]] && error "mysqldump not found. Add MySQL bin to PATH."
    else
        MYSQLDUMP="mysqldump"
    fi

    "$MYSQLDUMP" -u "$DB_USER" $MYSQL_PASS_ARG \
        --single-transaction \
        --routines \
        --triggers \
        "$DB_NAME" > "$DUMP_FILE"

    success "Dump saved to $DUMP_FILE ($(du -sh "$DUMP_FILE" | cut -f1))"

    info "Uploading dump to server..."
    if command -v sshpass &>/dev/null && [[ -n "$SSH_PASS" ]]; then
        sshpass -p "$SSH_PASS" scp -P "$REMOTE_PORT" -o StrictHostKeyChecking=no \
            "$DUMP_FILE" "$REMOTE_USER@$REMOTE_HOST:/tmp/"
    else
        scp -P "$REMOTE_PORT" "$DUMP_FILE" "$REMOTE_USER@$REMOTE_HOST:/tmp/"
    fi

    info "Importing database on server..."
    local REMOTE_SQL="/tmp/$(basename "$DUMP_FILE")"
    local REMOTE_PASS_ARG=""
    [[ -n "$DB_REMOTE_PASS" ]] && REMOTE_PASS_ARG="-p${DB_REMOTE_PASS}"

    ssh_cmd "mysql -u \"$DB_REMOTE_USER\" $REMOTE_PASS_ARG -e \"CREATE DATABASE IF NOT EXISTS \\\`$DB_NAME\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\" && \
             mysql -u \"$DB_REMOTE_USER\" $REMOTE_PASS_ARG \"$DB_NAME\" < \"$REMOTE_SQL\" && \
             rm -f \"$REMOTE_SQL\""

    rm -f "$DUMP_FILE"
    success "Database synced successfully."
}

# ── Sync files ────────────────────────────────────────────────────────────────
sync_files() {
    info "Syncing project files to ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH} ..."

    rsync_cmd -avz --progress \
        -e "ssh -p ${REMOTE_PORT} -o StrictHostKeyChecking=no" \
        --exclude='.git' \
        --exclude='.env' \
        --exclude='vendor/' \
        --exclude='writable/cache/*' \
        --exclude='writable/logs/*' \
        --exclude='writable/session/*' \
        --exclude='writable/uploads/*' \
        --exclude='node_modules/' \
        --exclude='*.xlsx' \
        "$SCRIPT_DIR/" \
        "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/"

    # Set permissions on server
    info "Setting file permissions..."
    ssh_cmd "find \"$REMOTE_PATH/writable\" -type d -exec chmod 775 {} \; 2>/dev/null || true"
    ssh_cmd "chown -R www-data:www-data \"$REMOTE_PATH\" 2>/dev/null || true"

    success "Files synced successfully."
}

# ── Main ──────────────────────────────────────────────────────────────────────
echo ""
echo -e "${CYAN}==================================================${NC}"
echo -e "${CYAN}  Nurse Ward Sync  →  ${REMOTE_USER}@${REMOTE_HOST}${NC}"
echo -e "${CYAN}  Mode: ${MODE}${NC}"
echo -e "${CYAN}==================================================${NC}"
echo ""

case "$MODE" in
    db)
        sync_db
        ;;
    files)
        sync_files
        ;;
    all)
        sync_db
        echo ""
        sync_files
        ;;
    *)
        error "Unknown mode '$MODE'. Use: db | files | all"
        ;;
esac

echo ""
success "Sync complete! $(date '+%Y-%m-%d %H:%M:%S')"
