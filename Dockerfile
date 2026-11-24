# Preview/staging image for Render (not restaurant production launch).
# Assets are Livewire + static public/ files — no Vite/npm build in this repo.

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction

FROM php:8.3-apache-bookworm

ARG APP_USER=www-data
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    PORT=8080 \
    TZ=Asia/Kuwait

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        gosu \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libsqlite3-dev \
        libxml2-dev \
        libzip-dev \
        tzdata \
        unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        zip \
    && a2enmod rewrite headers \
    && ln -snf /usr/share/zoneinfo/Asia/Kuwait /etc/localtime \
    && echo Asia/Kuwait > /etc/timezone \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY docker/apache/000-default.conf.template /etc/apache2/sites-available/000-default.conf.template
COPY docker/apache/ports.conf /etc/apache2/ports.conf

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && printf 'APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=\nAPP_ENV=production\n' > .env \
    && php artisan package:discover --ansi \
    && rm -f .env \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/logs \
        bootstrap/cache \
        /var/run/apache2 \
        /var/lock/apache2 \
        /var/log/apache2 \
    && printf 'ServerName localhost\n' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && chown -R ${APP_USER}:${APP_USER} \
        /var/www/html \
        /var/run/apache2 \
        /var/lock/apache2 \
        /var/log/apache2 \
    && chmod -R ug+rwX /var/run/apache2 /var/lock/apache2 /var/log/apache2

# Runtime listens on Render $PORT. PHP/app runs as www-data; Apache master needs root to bind logs.
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
