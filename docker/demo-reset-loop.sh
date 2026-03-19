#!/bin/sh
set -e

echo "[demo-reset] Starting demo reset worker..."

echo "[demo-reset] Running initial reset at $(date)"
php /app/bin/console app:demo:reset --no-interaction || echo "[demo-reset] Initial reset failed"

while true; do
    echo "[demo-reset] Sleeping for 600 seconds..."
    sleep 600

    echo "[demo-reset] Running periodic reset at $(date)"
    php /app/bin/console app:demo:reset --no-interaction
done
