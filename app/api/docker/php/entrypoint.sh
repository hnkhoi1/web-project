#!/bin/sh
set -e

cd /var/www/html

# Application key: persisted in the shared app_key volume when APP_KEY is not provided.
if [ -z "${APP_KEY:-}" ]; then
    KEY_FILE=/run/app-key/key
    if [ ! -s "$KEY_FILE" ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$KEY_FILE"
    fi
    APP_KEY=$(cat "$KEY_FILE")
    export APP_KEY
fi

# Fresh checkout: install PHP dependencies into the empty vendor volume.
if [ -f composer.json ] && [ -z "$(ls -A vendor 2>/dev/null)" ]; then
    composer install --no-interaction --prefer-dist
fi

# Production: bring the database up to date on every start and seed it once when it is empty.
# Only for the long-running FPM process, never for one-off `docker compose run ... php artisan ...`.
# Dev keeps migrations manual (AUTO_MIGRATE=false in compose.override.yaml).
if [ "${AUTO_MIGRATE:-false}" = "true" ] && [ "$1" = "php-fpm" ]; then
    echo "[entrypoint] php artisan migrate --force"
    php artisan migrate --force
    if [ "${AUTO_SEED:-true}" = "true" ]; then
        php artisan app:init-data
    fi
fi

exec "$@"
