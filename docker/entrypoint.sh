#!/bin/bash
set -e

# ── Contraseña SSH root (temporal) ───────────────────────────────────────────
SSH_ROOT_PASSWORD="${SSH_ROOT_PASSWORD:-GrovaTmp2026#}"
echo "root:${SSH_ROOT_PASSWORD}" | chpasswd

# ── Inicializar MariaDB si es primera vez ─────────────────────────────────────
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "==> Inicializando MariaDB por primera vez..."
    mysql_install_db --user=root --datadir=/var/lib/mysql --skip-test-db > /dev/null 2>&1

    # Arrancar MariaDB temporalmente para configurar acceso root
    mysqld_safe --skip-networking --datadir=/var/lib/mysql &
    MYSQL_PID=$!

    # Esperar a que MariaDB esté lista
    for i in $(seq 1 30); do
        if mysqladmin ping --silent 2>/dev/null; then
            break
        fi
        sleep 1
    done

    # Configurar root sin contraseña — socket y TCP (127.0.0.1)
    mysql -u root <<-SQL
        SET PASSWORD FOR 'root'@'localhost' = PASSWORD('');
        CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
        DELETE FROM mysql.user WHERE User='';
        DROP DATABASE IF EXISTS test;
        FLUSH PRIVILEGES;
SQL

    # Detener MariaDB temporal
    kill $MYSQL_PID 2>/dev/null || true
    wait $MYSQL_PID 2>/dev/null || true
    echo "==> MariaDB inicializado."
fi

# ── Caché Symfony ─────────────────────────────────────────────────────────────
echo "==> Limpiando cache de Symfony..."
php bin/console cache:clear --env=prod --no-debug

echo "==> Calentando cache..."
php bin/console cache:warmup --env=prod --no-debug

# ── Permisos ──────────────────────────────────────────────────────────────────
echo "==> Permisos en var/..."
chown -R www-data:www-data var/
chmod -R 775 var/

# ── Arrancar todos los servicios vía supervisord ──────────────────────────────
echo "==> Iniciando servicios (MariaDB + SSH + PHP-FPM + Nginx)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
