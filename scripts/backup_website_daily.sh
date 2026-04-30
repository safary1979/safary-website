#!/usr/bin/env bash

set -euo pipefail

APP_DIR="/home/ubuntu/website"
BACKUP_ROOT="/home/ubuntu/backups/website"
DAILY_ROOT="$BACKUP_ROOT/daily"
KEEP_DAYS="${KEEP_DAYS:-30}"
TIMESTAMP="$(date -u +%Y%m%d_%H%M%S)"
TARGET_DIR="$DAILY_ROOT/$TIMESTAMP"
LOG_DIR="$BACKUP_ROOT/logs"
DB_NAME="laravel_cabinet"
DB_USER="laravel"
DB_HOST="127.0.0.1"
BOT_DB="/home/ubuntu/SafaryEngine/live/data/live_dashboard.db"
BOT_CONFIGS_DIR="/home/ubuntu/SafaryEngine/live/configs"

mkdir -p "$TARGET_DIR" "$LOG_DIR"

DB_PASS="$(grep '^DB_PASSWORD=' "$APP_DIR/.env" | cut -d= -f2-)"

cp "$APP_DIR/.env" "$TARGET_DIR/.env"
MYSQL_PWD="$DB_PASS" mysqldump --single-transaction --quick -h"$DB_HOST" -u"$DB_USER" "$DB_NAME" | gzip > "$TARGET_DIR/${DB_NAME}.sql.gz"
cp "$BOT_DB" "$TARGET_DIR/live_dashboard.db"
tar -czf "$TARGET_DIR/bot_configs.tar.gz" -C "$(dirname "$BOT_CONFIGS_DIR")" "$(basename "$BOT_CONFIGS_DIR")"
git -C "$APP_DIR" status --short --branch > "$TARGET_DIR/git-status.txt"
sha256sum \
    "$TARGET_DIR/.env" \
    "$TARGET_DIR/${DB_NAME}.sql.gz" \
    "$TARGET_DIR/live_dashboard.db" \
    "$TARGET_DIR/bot_configs.tar.gz" \
    "$TARGET_DIR/git-status.txt" \
    > "$TARGET_DIR/SHA256SUMS"

find "$DAILY_ROOT" -mindepth 1 -maxdepth 1 -type d -mtime +"$KEEP_DAYS" -exec rm -rf {} +

printf '%s daily backup complete: %s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$TARGET_DIR" >> "$LOG_DIR/daily.log"
