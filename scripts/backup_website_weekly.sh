#!/usr/bin/env bash

set -euo pipefail

APP_DIR="/home/ubuntu/website"
BACKUP_ROOT="/home/ubuntu/backups/website"
WEEKLY_ROOT="$BACKUP_ROOT/weekly"
KEEP_WEEKS="${KEEP_WEEKS:-8}"
TIMESTAMP="$(date -u +%Y%m%d_%H%M%S)"
TARGET_DIR="$WEEKLY_ROOT/$TIMESTAMP"
LOG_DIR="$BACKUP_ROOT/logs"

mkdir -p "$TARGET_DIR" "$LOG_DIR"

tar \
    --exclude='.git' \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/logs/*' \
    --exclude='public/build' \
    -czf "$TARGET_DIR/website_code.tar.gz" \
    -C /home/ubuntu website

git -C "$APP_DIR" status --short --branch > "$TARGET_DIR/git-status.txt"
git -C "$APP_DIR" log --oneline -5 > "$TARGET_DIR/git-log.txt"
sha256sum \
    "$TARGET_DIR/website_code.tar.gz" \
    "$TARGET_DIR/git-status.txt" \
    "$TARGET_DIR/git-log.txt" \
    > "$TARGET_DIR/SHA256SUMS"

find "$WEEKLY_ROOT" -mindepth 1 -maxdepth 1 -type d -mtime +$((KEEP_WEEKS * 7)) -exec rm -rf {} +

printf '%s weekly backup complete: %s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$TARGET_DIR" >> "$LOG_DIR/weekly.log"
