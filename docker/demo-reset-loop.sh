#!/bin/sh

echo "[demo-reset] Running initial reset at $(date)"
php /app/bin/console app:demo:reset --no-interaction || echo "[demo-reset] Initial reset failed"

echo "*/10 * * * * php /app/bin/console app:demo:reset --no-interaction >> /var/log/demo-reset.log 2>&1" | crontab -

echo "[demo-reset] Cron installed (every 10 minutes). Starting crond..."
crond -f -l 2
