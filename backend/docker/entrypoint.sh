#!/usr/bin/env sh
set -e

# Ensure writable directories exist at runtime (handles volume mounts that
# may shadow the directories created during the image build).
mkdir -p /var/www/storage/logs /var/www/storage/rate_limits
touch /var/www/storage/logs/app.log
chown -R www-data:www-data /var/www/storage /var/www/config
chmod 664 /var/www/config/config.php 2>/dev/null || true

# Start PHP-FPM in the background
php-fpm -D

# Start Nginx in the foreground (keeps the container alive)
exec nginx -g "daemon off;"
