# backend/Dockerfile

FROM php:8.5-fpm-alpine

# ── System dependencies ───────────────────────────────────────────────────────
RUN apk add --no-cache \
    nginx \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    bash \
    shadow \
    git \
    unzip \
    mailcap \
    curl \
    g++ \
    make \
    autoconf

# ── PHP extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    intl \
    zip

# ── Install Composer (FROM official image) ────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── PHP & Nginx configuration ────────────────────────────────────────────────
COPY backend/docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY backend/docker/nginx.conf /etc/nginx/nginx.conf

# ── Set working directory ────────────────────────────────────────────────────
WORKDIR /var/www

# ── Composer dependencies (cache-friendly) ───────────────────────────────────
# Copy only composer files first
COPY backend/composer.json backend/composer.lock ./

# Install dependencies (no dev, optimized autoloader)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --classmap-authoritative \
    --apcu-autoloader

# ── Copy app source after dependencies (changes often) ───────────────────────
COPY backend/ .
RUN composer install --no-dev -o

# ── Storage directories & permissions ───────────────────────────────────────
RUN mkdir -p \
        storage/logs \
        storage/rate_limits \
    && touch storage/logs/app.log \
    && chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod 664 /var/www/config/config.php

# ── Entrypoint ───────────────────────────────────────────────────────────────
COPY backend/docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# ── Healthcheck ─────────────────────────────────────────────────────────────
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -qO- http://localhost/status.php || exit 1

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]