#!/usr/bin/env sh
set -e

# ── Storage directories ───────────────────────────────────────────────────────
# Re-create writable directories at runtime.  This handles Docker volume mounts
# that shadow the directories created during the image build.
mkdir -p /var/www/storage/logs /var/www/storage/rate_limits
touch /var/www/storage/logs/app.log
chown -R www-data:www-data /var/www/storage

# ── config/config.php write permissions ──────────────────────────────────────
# Migrator::run() writes 'installed = true' back into config/config.php on
# first boot.  When the file is bind-mounted from the host, the host user owns
# it; we attempt to make it group-writable so www-data can write to it.
# Failures are non-fatal (the app will still run; only the installed flag
# write-back will fail silently and retry on the next boot).
chmod g+w /var/www/config/config.php 2>/dev/null || true

# ── Start PHP-FPM ─────────────────────────────────────────────────────────────
php-fpm -D

# ── Start Nginx (foreground — keeps the container alive) ─────────────────────
exec nginx -g "daemon off;"
