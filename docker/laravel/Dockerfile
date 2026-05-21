# =============================================================================
# Imagen base reutilizable para microservicios Laravel
# =============================================================================
# Un solo contenedor con:
#   - PHP 8.4 FPM (procesa requests Laravel)
#   - Nginx (sirve HTTP en :80, hace proxy a php-fpm)
#   - Supervisor (orquesta ambos procesos)
#   - Composer (para instalar dependencias durante build)
# =============================================================================

FROM php:8.4-fpm-alpine

# Dependencias del sistema
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    curl \
    wget \
    git \
    libpq-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    autoconf \
    g++ \
    make \
    linux-headers

# Extensiones PHP necesarias para Laravel + Postgres + Redis
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    bcmath \
    intl \
    opcache \
    pcntl \
    zip

# Redis vía PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Composer (desde imagen oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuraciones
COPY docker/laravel/nginx.conf /etc/nginx/nginx.conf
COPY docker/laravel/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/laravel/php.ini /usr/local/etc/php/conf.d/zz-custom.ini
COPY docker/laravel/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Crear estructura de carpetas que nginx necesita
RUN mkdir -p /run/nginx /var/log/supervisor

# Directorio de trabajo: aquí montaremos el código Laravel del servicio
WORKDIR /var/www/html

# Permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
