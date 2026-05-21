#!/bin/bash
# =============================================================================
# Entrypoint para microservicios Laravel
# =============================================================================
set -e

echo "🚀 Iniciando microservicio Laravel: ${SERVICE_NAME:-unknown}"

# 1. Configurar socket Unix para php-fpm
mkdir -p /var/run
cat > /usr/local/etc/php-fpm.d/zz-socket.conf <<EOF
[www]
listen = /var/run/php-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
user = www-data
group = www-data
EOF

# 2. Si existe código Laravel, hacer setup
if [ -f /var/www/html/artisan ]; then
    cd /var/www/html

    echo "📁 Configurando permisos..."
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

    if [ -n "$DB_HOST" ]; then
        echo "⏳ Esperando a $DB_HOST:${DB_PORT:-5432}..."
        for i in $(seq 1 30); do
            if nc -z "$DB_HOST" "${DB_PORT:-5432}" 2>/dev/null; then
                echo "✓ DB disponible"
                break
            fi
            sleep 1
        done
    fi

    if [ -n "$APP_KEY" ] && [ -f /var/www/html/composer.json ]; then
        echo "🔄 Ejecutando migrate..."
        su -s /bin/sh www-data -c "php artisan migrate --force --no-interaction" || echo "⚠️  migrate falló (posiblemente normal en primer arranque)"
    fi

    echo "✓ Setup Laravel completo"
else
    echo "ℹ️  Sin código Laravel todavía (placeholder mode)"
fi

echo "▶️  Pasando control a: $@"
exec "$@"
