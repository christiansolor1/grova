#!/bin/sh
set -e

echo "==> Limpiando cache de Symfony..."
php bin/console cache:clear --env=prod --no-debug

echo "==> Calentando cache..."
php bin/console cache:warmup --env=prod --no-debug

echo "==> Permisos en var/..."
chown -R www-data:www-data var/
chmod -R 775 var/

echo "==> Iniciando servicios..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
