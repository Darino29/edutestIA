#!/bin/sh
set -e

echo "==> Warming up Symfony cache..."
APP_ENV=prod php bin/console cache:warmup --no-interaction 2>/dev/null || true

echo "==> Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>/dev/null || true

echo "==> Starting services..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
