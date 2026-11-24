#!/bin/bash
set -euo pipefail

cd /var/www/html

PORT="${PORT:-8080}"
export PORT

# Render injects the stable public HTTPS URL.
if [[ -n "${RENDER_EXTERNAL_URL:-}" ]]; then
  export APP_URL="${RENDER_EXTERNAL_URL}"
fi

# Local/dev fallback when APP_KEY is missing (Render Blueprint uses generateValue).
if [[ -z "${APP_KEY:-}" ]]; then
  export APP_KEY="base64:$(head -c 32 /dev/urandom | base64 | tr -d '\n')"
  echo "entrypoint: generated ephemeral APP_KEY for this boot"
fi

# SQLite fallback for local docker verify (Render uses Postgres via DATABASE_URL).
if [[ "${DB_CONNECTION:-}" == "sqlite" ]]; then
  DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
  mkdir -p "$(dirname "$DB_FILE")"
  if [[ ! -f "$DB_FILE" ]]; then
    touch "$DB_FILE"
  fi
  chown www-data:www-data "$DB_FILE" || true
  export DB_DATABASE="$DB_FILE"
fi

mkdir -p \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/framework/testing \
  storage/logs \
  bootstrap/cache \
  /var/run/apache2 \
  /var/lock/apache2 \
  /var/log/apache2

# Writable runtime dirs for non-root Apache (www-data).
chown -R www-data:www-data storage bootstrap/cache /var/run/apache2 /var/lock/apache2 /var/log/apache2 || true
chmod -R ug+rwX storage bootstrap/cache /var/run/apache2 /var/lock/apache2 /var/log/apache2 || true
touch storage/logs/apache-error.log storage/logs/apache-access.log
chown www-data:www-data storage/logs/apache-error.log storage/logs/apache-access.log || true

# Apache must listen on Render's assigned PORT (non-privileged).
printf 'Listen %s\n' "$PORT" > /etc/apache2/ports.conf
sed "s/__PORT__/${PORT}/g" /etc/apache2/sites-available/000-default.conf.template \
  > /etc/apache2/sites-available/000-default.conf
chown www-data:www-data /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf || true

# Clear any stale bootstrap caches before migrate/seed (fresh env each deploy).
rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php bootstrap/cache/packages.php bootstrap/cache/services.php 2>/dev/null || true

echo "entrypoint: storage:link"
gosu www-data php artisan storage:link --force

echo "entrypoint: migrate"
migrate_ok=0
for attempt in 1 2 3 4 5 6 7 8 9 10; do
  if gosu www-data php artisan migrate --force; then
    migrate_ok=1
    break
  fi
  echo "entrypoint: migrate attempt ${attempt} failed; retrying in 3s…"
  sleep 3
done
if [[ "$migrate_ok" -ne 1 ]]; then
  echo "entrypoint: migrate failed after retries" >&2
  exit 1
fi

if [[ "${RUN_PREVIEW_SEED:-false}" == "true" ]]; then
  echo "entrypoint: fatoosh:preview:bootstrap"
  gosu www-data php artisan fatoosh:preview:bootstrap
fi

echo "entrypoint: config/route/view cache"
gosu www-data php artisan config:cache
gosu www-data php artisan route:cache
gosu www-data php artisan view:cache

# Apache master starts as root (opens logs / binds PORT) then drops workers to www-data.
# All artisan/migrate/seed above already ran as www-data; app files stay owned by www-data.
export APACHE_RUN_USER=www-data
export APACHE_RUN_GROUP=www-data
export APACHE_PID_FILE=/var/run/apache2/apache2.pid
export APACHE_RUN_DIR=/var/run/apache2
export APACHE_LOCK_DIR=/var/lock/apache2
export APACHE_LOG_DIR=/var/log/apache2

# Global ErrorLog still points at ${APACHE_LOG_DIR}/error.log (symlink to stderr in this image).
sed -i 's#ErrorLog ${APACHE_LOG_DIR}/error.log#ErrorLog /var/www/html/storage/logs/apache-error.log#' /etc/apache2/apache2.conf || true

echo "entrypoint: starting Apache on :${PORT} (workers: www-data)"
exec "$@"
