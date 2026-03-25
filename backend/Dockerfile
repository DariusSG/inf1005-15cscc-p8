# backend/Dockerfile
# Build context: repository root (docker-bake.hcl sets context=.)
# For local dev with docker compose, set context: . in docker-compose.yml

# ─────────────────────────────────────────────────────────────────────────────
# STAGE 1: Install Composer dependencies
# ─────────────────────────────────────────────────────────────────────────────
FROM composer:2 AS composer

WORKDIR /app

# Copy only the manifest files first so Docker can cache the vendor layer
COPY backend/composer.json backend/composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --classmap-authoritative

# ─────────────────────────────────────────────────────────────────────────────
# STAGE 2: Runtime image
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine

# ── System dependencies ───────────────────────────────────────────────────────
RUN apk add --no-cache \
    nginx \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    bash \
    shadow \
    # needed for mime.types in nginx
    mailcap

# ── PHP extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    intl \
    zip \
    opcache

# ── PHP configuration ─────────────────────────────────────────────────────────
COPY backend/docker/php.ini /usr/local/etc/php/conf.d/app.ini

# ── Nginx configuration ───────────────────────────────────────────────────────
COPY backend/docker/nginx.conf /etc/nginx/nginx.conf

# ── Application ───────────────────────────────────────────────────────────────
WORKDIR /var/www

# Copy vendor from composer stage
COPY --from=composer /app/vendor ./vendor

# Copy application source (excludes files listed in .dockerignore)
COPY backend/ .

# ── Storage directories ───────────────────────────────────────────────────────
# Create writable directories for logs, rate-limit counters, and the
# config/config.php installed-flag write-back.
RUN mkdir -p \
        storage/logs \
        storage/rate_limits \
    && touch storage/logs/app.log \
    # config/config.php must be writable so AppServiceProvider can persist
    # the 'installed = true' flag on first boot.
    && chown -R www-data:www-data \
        /var/www/storage \
        /var/www/config \
    && chmod -R 755 /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod 664 /var/www/config/config.php

# ── Entrypoint ────────────────────────────────────────────────────────────────
COPY backend/docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# ── Healthcheck ───────────────────────────────────────────────────────────────
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -qO- http://localhost/status.php || exit 1

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
