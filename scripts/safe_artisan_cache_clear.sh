#!/usr/bin/env bash
set -euo pipefail
umask 0002

cd "$(dirname "$0")/.."

php artisan optimize:clear
php artisan view:clear

if command -v sudo >/dev/null 2>&1; then
    sudo -n chgrp -R www-data bootstrap/cache storage/framework storage/logs 2>/dev/null || true
    sudo -n chmod -R ug+rwX bootstrap/cache storage/framework storage/logs 2>/dev/null || true
    sudo -n find bootstrap/cache storage/framework storage/logs -type f -exec chmod 664 {} + 2>/dev/null || true
    sudo -n find bootstrap/cache storage/framework storage/logs -type d -exec chmod 775 {} + 2>/dev/null || true
else
    chgrp -R www-data bootstrap/cache storage/framework storage/logs 2>/dev/null || true
    chmod -R ug+rwX bootstrap/cache storage/framework storage/logs 2>/dev/null || true
    find bootstrap/cache storage/framework storage/logs -type f -exec chmod 664 {} + 2>/dev/null || true
    find bootstrap/cache storage/framework storage/logs -type d -exec chmod 775 {} + 2>/dev/null || true
fi

php artisan route:list --path=chart-api >/dev/null
