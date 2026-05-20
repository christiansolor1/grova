#!/bin/bash
# Inicia entorno de desarrollo local con HTTPS
# Puerto HTTP  : 8082
# Puerto HTTPS : 8083

DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DIR" || exit 1

echo "▶️  PHP dev server (HTTP) → http://0.0.0.0:8082"
php -S 0.0.0.0:8082 -t public/ router.php &
PID_PHP=$!

echo "▶️  SSL proxy (HTTPS) → https://0.0.0.0:8083"
socat OPENSSL-LISTEN:8083,cert=ssl/local.pem,key=ssl/local-key.pem,verify=0,fork,reuseaddr TCP:localhost:8082 &
PID_SOCAT=$!

echo ""
echo "✅ Servidor listo"
echo "   Desde tu Mac:  https://MacBook-Pro-de-Christian.local:8083"
echo "   Desde iPhone:  https://MacBook-Pro-de-Christian.local:8083"
echo "   Local:         http://localhost:8082"
echo ""
echo "⚠️  En tu iPhone, instala el perfil CA de mkcert:"
echo "   Comparte el archivo ssl/local.pem contigo mismo y ábrelo"
echo ""
echo "Presiona Ctrl+C para detener ambos servidores"

trap "kill $PID_PHP $PID_SOCAT 2>/dev/null; exit 0" INT TERM
wait
