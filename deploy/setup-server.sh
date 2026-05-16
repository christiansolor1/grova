#!/usr/bin/env bash
# =============================================================================
# setup-server.sh — Grova production setup para Ubuntu 24.04 LTS
# Servidor: Hetzner 5.161.218.49 | Dominio: grovaapp.com
#
# Uso:
#   chmod +x setup-server.sh
#   sudo bash setup-server.sh
#
# Qué hace este script:
#   1. Actualiza el sistema
#   2. Instala Nginx, PHP 8.2-FPM, MariaDB, Certbot, Composer, Git
#   3. Crea usuario del sistema y estructura de directorios
#   4. Configura MariaDB (BD + usuario)
#   5. Clona el repositorio
#   6. Configura .env.local de producción
#   7. Instala dependencias PHP y calienta caché
#   8. Genera llaves JWT
#   9. Corre migraciones
#  10. Configura Nginx con el virtualhost
#  11. Obtiene certificado SSL con Certbot
#  12. Configura permisos finales
# =============================================================================

set -euo pipefail

# ── Colores ───────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()    { echo -e "${BLUE}[INFO]${NC} $*"; }
success() { echo -e "${GREEN}[OK]${NC}   $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

# ── Variables — EDITAR ANTES DE EJECUTAR ─────────────────────────────────────
SERVER_IP="5.161.218.49"
DOMAIN="grovaapp.com"
APP_DIR="/var/www/grova"
SYSTEM_USER="www-data"

# Base de datos
DB_NAME="grova_core"
DB_USER="grova_prod"
DB_PASS="Grova2026#DB"          # Cambia esto antes de ejecutar en prod
DB_ROOT_PASS="$(openssl rand -hex 20)"   # Root de MariaDB — se guarda en /root/.my.cnf

# Repositorio — cambia por la URL real de tu repo
GIT_REPO="git@github.com:TU_USUARIO/grova.git"   # o https://github.com/...
GIT_BRANCH="main"

# Email para Certbot
CERTBOT_EMAIL="christian@grovaapp.com"

# PHP
PHP_VERSION="8.2"

# ── Verificaciones previas ────────────────────────────────────────────────────
[[ $EUID -ne 0 ]] && error "Ejecuta este script como root: sudo bash setup-server.sh"
[[ -z "${DOMAIN}" ]] && error "DOMAIN no puede estar vacío"
[[ -z "${DB_PASS}" ]] && error "DB_PASS no puede estar vacío"

info "Iniciando setup de ${DOMAIN} en ${SERVER_IP}"
echo ""

# =============================================================================
# PASO 1 — Actualizar sistema
# =============================================================================
info "Paso 1/12 — Actualizando sistema..."
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq \
    curl wget git unzip zip \
    software-properties-common apt-transport-https \
    ca-certificates gnupg lsb-release \
    ufw fail2ban htop
success "Sistema actualizado"

# =============================================================================
# PASO 2 — Instalar PHP 8.2 + extensiones
# =============================================================================
info "Paso 2/12 — Instalando PHP ${PHP_VERSION}..."
add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-pdo \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-opcache \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-apcu

# Configurar PHP-FPM para prod
PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
sed -i 's/^;*opcache.enable=.*/opcache.enable=1/'                          $PHP_INI
sed -i 's/^;*opcache.memory_consumption=.*/opcache.memory_consumption=256/' $PHP_INI
sed -i 's/^;*opcache.interned_strings_buffer=.*/opcache.interned_strings_buffer=16/' $PHP_INI
sed -i 's/^;*opcache.max_accelerated_files=.*/opcache.max_accelerated_files=20000/' $PHP_INI
sed -i 's/^;*opcache.validate_timestamps=.*/opcache.validate_timestamps=0/' $PHP_INI
sed -i 's/^upload_max_filesize.*/upload_max_filesize = 20M/'                $PHP_INI
sed -i 's/^post_max_size.*/post_max_size = 21M/'                            $PHP_INI
sed -i 's/^memory_limit.*/memory_limit = 256M/'                             $PHP_INI
sed -i 's/^max_execution_time.*/max_execution_time = 120/'                  $PHP_INI
sed -i 's/^expose_php.*/expose_php = Off/'                                  $PHP_INI

systemctl enable php${PHP_VERSION}-fpm
systemctl start php${PHP_VERSION}-fpm
success "PHP ${PHP_VERSION} instalado y configurado"

# =============================================================================
# PASO 3 — Instalar Nginx
# =============================================================================
info "Paso 3/12 — Instalando Nginx..."
apt-get install -y -qq nginx
systemctl enable nginx
systemctl start nginx
success "Nginx instalado"

# =============================================================================
# PASO 4 — Instalar MariaDB
# =============================================================================
info "Paso 4/12 — Instalando MariaDB..."
apt-get install -y -qq mariadb-server mariadb-client

systemctl enable mariadb
systemctl start mariadb

# Securizar MariaDB (equivalente a mysql_secure_installation no-interactivo)
mysql -u root <<-SQL
    ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_ROOT_PASS}';
    DELETE FROM mysql.user WHERE User='';
    DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');
    DROP DATABASE IF EXISTS test;
    DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
    FLUSH PRIVILEGES;
SQL

# Guardar credenciales root de forma segura
cat > /root/.my.cnf <<-EOF
[client]
user=root
password=${DB_ROOT_PASS}
EOF
chmod 600 /root/.my.cnf

# Crear BD y usuario de la app
mysql -u root -p"${DB_ROOT_PASS}" <<-SQL
    CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci;

    CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost'
        IDENTIFIED BY '${DB_PASS}';

    GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
    FLUSH PRIVILEGES;
SQL

success "MariaDB configurado — BD: ${DB_NAME}, usuario: ${DB_USER}"
warn "Contraseña root de MariaDB guardada en /root/.my.cnf — protege ese archivo"

# =============================================================================
# PASO 5 — Instalar Composer
# =============================================================================
info "Paso 5/12 — Instalando Composer..."
EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
    rm -f composer-setup.php
    error "Checksum de Composer inválido — abortando"
fi

php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
rm -f composer-setup.php
success "Composer instalado: $(composer --version 2>&1 | head -1)"

# =============================================================================
# PASO 6 — Clonar repositorio y estructura de directorios
# =============================================================================
info "Paso 6/12 — Clonando repositorio..."

# Si ya existe, hacer pull en lugar de clonar
if [ -d "${APP_DIR}/.git" ]; then
    warn "Directorio ya existe — haciendo git pull en lugar de clone"
    git -C "${APP_DIR}" pull origin "${GIT_BRANCH}"
else
    git clone --branch "${GIT_BRANCH}" "${GIT_REPO}" "${APP_DIR}"
fi

# Directorios necesarios
mkdir -p "${APP_DIR}/var/cache"
mkdir -p "${APP_DIR}/var/log"
mkdir -p "${APP_DIR}/var/share"
mkdir -p "${APP_DIR}/config/jwt"

success "Repositorio clonado en ${APP_DIR}"

# =============================================================================
# PASO 7 — Configurar .env.local de producción
# =============================================================================
info "Paso 7/12 — Generando .env.local de producción..."

# Generar secretos únicos para esta instalación
PROD_APP_SECRET="$(openssl rand -hex 32)"
PROD_JWT_PASSPHRASE="$(openssl rand -hex 32)"
PROD_MERCURE_SECRET="$(openssl rand -hex 32)"
GIT_SHA="$(git -C ${APP_DIR} rev-parse --short HEAD 2>/dev/null || echo 'unknown')"

cat > "${APP_DIR}/.env.local" <<-EOF
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=${PROD_APP_SECRET}
APP_SHARE_DIR=var/share
APP_VERSION=0.1.0
APP_GIT_SHA=${GIT_SHA}

DEFAULT_URI=https://${DOMAIN}

DATABASE_URL="mysql://${DB_USER}:${DB_PASS}@127.0.0.1:3306/${DB_NAME}?serverVersion=mariadb-10.11.16&charset=utf8mb4"
DATABASE_CORE_URL="mysql://${DB_USER}:${DB_PASS}@127.0.0.1:3306/${DB_NAME}?serverVersion=mariadb-10.11.16&charset=utf8mb4"

JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=${PROD_JWT_PASSPHRASE}

CORS_ALLOW_ORIGIN='^https://${DOMAIN}$'

STORMGLASS_API_KEY=PENDIENTE_CONFIGURAR

MAILER_DSN=null://null
MAILER_FROM_EMAIL=noreply@${DOMAIN}

MERCURE_URL=https://${DOMAIN}/.well-known/mercure
MERCURE_PUBLIC_URL=https://${DOMAIN}/.well-known/mercure
MERCURE_JWT_SECRET=${PROD_MERCURE_SECRET}
EOF

chmod 640 "${APP_DIR}/.env.local"
success ".env.local generado con secretos únicos"

# Guardar los secretos generados en /root para referencia
cat > /root/grova-secrets.txt <<-EOF
# Grova — Secretos generados el $(date)
# GUARDA ESTO EN UN LUGAR SEGURO Y LUEGO BORRA ESTE ARCHIVO
# rm /root/grova-secrets.txt

APP_SECRET=${PROD_APP_SECRET}
JWT_PASSPHRASE=${PROD_JWT_PASSPHRASE}
MERCURE_JWT_SECRET=${PROD_MERCURE_SECRET}

DB_ROOT_PASS=${DB_ROOT_PASS}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
EOF
chmod 600 /root/grova-secrets.txt
warn "Secretos generados guardados en /root/grova-secrets.txt — cópialos y borra el archivo"

# =============================================================================
# PASO 8 — Instalar dependencias y calentar caché
# =============================================================================
info "Paso 8/12 — Instalando dependencias PHP..."
cd "${APP_DIR}"
sudo -u ${SYSTEM_USER} composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    2>&1

info "Calentando caché de prod..."
sudo -u ${SYSTEM_USER} php bin/console cache:clear --env=prod --no-debug
sudo -u ${SYSTEM_USER} php bin/console cache:warmup --env=prod --no-debug
success "Dependencias instaladas y caché caliente"

# =============================================================================
# PASO 9 — Generar llaves JWT
# =============================================================================
info "Paso 9/12 — Generando llaves JWT..."
sudo -u ${SYSTEM_USER} php bin/console lexik:jwt:generate-keypair --overwrite --env=prod
success "Llaves JWT generadas en config/jwt/"

# =============================================================================
# PASO 10 — Correr migraciones
# =============================================================================
info "Paso 10/12 — Corriendo migraciones de BD..."
sudo -u ${SYSTEM_USER} php bin/console doctrine:migrations:migrate \
    --no-interaction \
    --env=prod \
    2>&1
success "Migraciones aplicadas"

# =============================================================================
# PASO 11 — Configurar Nginx
# =============================================================================
info "Paso 11/12 — Configurando Nginx..."

# Usar la config del repo si existe, si no generarla aquí
NGINX_SOURCE="${APP_DIR}/deploy/nginx.conf"
NGINX_DEST="/etc/nginx/sites-available/${DOMAIN}"

if [ -f "${NGINX_SOURCE}" ]; then
    cp "${NGINX_SOURCE}" "${NGINX_DEST}"
else
    # Config inline de respaldo
    cat > "${NGINX_DEST}" <<-NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};
    location /.well-known/acme-challenge/ { root /var/www/certbot; }
    location / { return 301 https://${DOMAIN}\$request_uri; }
}
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;
    index index.php;
    ssl_certificate     /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    client_max_body_size 20M;
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2)$ {
        expires 1y; add_header Cache-Control "public, immutable"; access_log off;
        try_files \$uri =404;
    }
    location / { try_files \$uri /index.php\$is_args\$args; }
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_read_timeout 120;
        internal;
    }
    location ~ \.php$ { return 404; }
    location ~ /\. { deny all; }
}
NGINX
fi

# Activar el site
ln -sf "${NGINX_DEST}" /etc/nginx/sites-enabled/${DOMAIN}
rm -f /etc/nginx/sites-enabled/default

nginx -t || error "Config de Nginx inválida — revisa ${NGINX_DEST}"
systemctl reload nginx
success "Nginx configurado para ${DOMAIN}"

# =============================================================================
# PASO 12 — SSL con Certbot
# =============================================================================
info "Paso 12/12 — Obteniendo certificado SSL..."
apt-get install -y -qq certbot python3-certbot-nginx

certbot --nginx \
    --non-interactive \
    --agree-tos \
    --email "${CERTBOT_EMAIL}" \
    --domains "${DOMAIN},www.${DOMAIN}" \
    --redirect \
    2>&1 || warn "Certbot falló — si el dominio aún no apunta a este servidor, ejecuta certbot manualmente después"

# Auto-renovación (ya viene con el paquete, pero verificamos)
systemctl enable certbot.timer 2>/dev/null || true
success "SSL configurado"

# =============================================================================
# PERMISOS FINALES
# =============================================================================
info "Aplicando permisos finales..."
chown -R www-data:www-data "${APP_DIR}/var"
chown -R www-data:www-data "${APP_DIR}/public"
chown    www-data:www-data "${APP_DIR}/.env.local"
chown -R www-data:www-data "${APP_DIR}/config/jwt"
chmod -R 775 "${APP_DIR}/var"
chmod    640 "${APP_DIR}/.env.local"
chmod    640 "${APP_DIR}/config/jwt/private.pem"
chmod    644 "${APP_DIR}/config/jwt/public.pem"

# =============================================================================
# FIREWALL
# =============================================================================
info "Configurando firewall (UFW)..."
ufw --force reset > /dev/null 2>&1
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
success "Firewall activo — SSH, 80, 443 abiertos"

# =============================================================================
# FAIL2BAN
# =============================================================================
info "Configurando Fail2Ban..."
cat > /etc/fail2ban/jail.local <<-EOF
[DEFAULT]
bantime  = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port    = ssh
logpath = %(sshd_log)s
backend = %(syslog_backend)s

[nginx-http-auth]
enabled = true
EOF

systemctl enable fail2ban
systemctl restart fail2ban
success "Fail2Ban activo"

# =============================================================================
# RESUMEN FINAL
# =============================================================================
echo ""
echo -e "${GREEN}============================================================${NC}"
echo -e "${GREEN}  Setup completado para ${DOMAIN}${NC}"
echo -e "${GREEN}============================================================${NC}"
echo ""
echo "  App dir:    ${APP_DIR}"
echo "  PHP:        ${PHP_VERSION}"
echo "  MariaDB:    ${DB_NAME} / ${DB_USER}"
echo "  URL:        https://${DOMAIN}"
echo ""
echo -e "${YELLOW}Pasos manuales pendientes:${NC}"
echo "  1. cat /root/grova-secrets.txt  → copia los secretos a un gestor de passwords"
echo "  2. rm /root/grova-secrets.txt   → borra el archivo del servidor"
echo "  3. Edita ${APP_DIR}/.env.local  → completa STORMGLASS_API_KEY y MAILER_DSN"
echo "  4. Si el dominio no apuntaba al servidor durante el setup, ejecuta:"
echo "     sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
echo ""
echo -e "${YELLOW}Comandos útiles:${NC}"
echo "  Ver logs app:    tail -f ${APP_DIR}/var/log/prod.log"
echo "  Ver logs nginx:  tail -f /var/log/nginx/grovaapp.error.log"
echo "  Reiniciar PHP:   systemctl restart php${PHP_VERSION}-fpm"
echo "  Reiniciar Nginx: systemctl reload nginx"
echo "  Deploy nuevo:    cd ${APP_DIR} && git pull && composer install --no-dev && php bin/console cache:warmup --env=prod"
echo ""
echo -e "${GREEN}Hecho.${NC}"
