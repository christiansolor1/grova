#!/bin/sh
set -e

# Marcar instalación como completada (en prod el setup se hace por CLI, no por web)
if [ ! -f .env.local ]; then
    touch .env.local
    echo "==> .env.local creado (instalación marcada como lista)"
fi

echo "==> Limpiando cache de Symfony..."
php bin/console cache:clear --env=prod --no-debug

echo "==> Calentando cache..."
php bin/console cache:warmup --env=prod --no-debug

echo "==> Permisos en var/..."
chown -R www-data:www-data var/
chmod -R 775 var/

echo "==> Iniciando servicios..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
