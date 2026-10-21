# ==============================================================================
# MULTI-STAGE BUILD — Inventario de Equipos (Enterprise)
# ==============================================================================
# Stage 1: Composer dependencies
# Stage 2: Assets frontend (Vite + Tailwind)
# Stage 3: Production image (PHP 8.2-FPM)
# ==============================================================================

# ------------------------------------------------------------------------------
# STAGE 1: Composer (instalar dependencias en una capa temporal)
# ------------------------------------------------------------------------------
FROM composer:latest AS composer-deps

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader \
    --no-scripts \
    --no-dev \
    --ignore-platform-reqs

# ------------------------------------------------------------------------------
# STAGE 2: Assets frontend (Vite + Tailwind)
# ------------------------------------------------------------------------------
# `public/build` está en .gitignore: si no se compila aquí, la imagen sirve los
# assets de la última compilación local del host (o ninguno). Este stage
# garantiza que cada build incluya el CSS/JS del código que se empaqueta.
# IMPORTANTE: no usa variables de entorno ni la base de datos.
FROM node:22-bookworm AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources

RUN npm run build

# ------------------------------------------------------------------------------
# STAGE 3: Imagen final de producción
# ------------------------------------------------------------------------------
FROM php:8.2-apache

# Instalar dependencias del sistema + extensiones PHP en una sola capa
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    && docker-php-ext-install \
    zip \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache \
    && a2enmod rewrite \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Configuración OPcache optimizada para producción
RUN printf "%s\n" \
    "opcache.enable=1" \
    "opcache.enable_cli=1" \
    "opcache.memory_consumption=256" \
    "opcache.interned_strings_buffer=32" \
    "opcache.max_accelerated_files=30000" \
    "opcache.validate_timestamps=0" \
    "opcache.revalidate_freq=0" \
    "opcache.file_update_protection=0" \
    "opcache.save_comments=1" \
    "opcache.jit=1255" \
    "opcache.jit_buffer_size=128M" \
    > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Configuración PHP para producción
RUN printf "%s\n" \
    "upload_max_filesize=64M" \
    "post_max_size=64M" \
    "memory_limit=256M" \
    "max_execution_time=60" \
    "expose_php=Off" \
    > /usr/local/etc/php/conf.d/production.ini

WORKDIR /var/www

# Copiar dependencias de Composer desde Stage 1
COPY --from=composer-deps /app/vendor ./vendor

# Copiar código fuente de la aplicación
COPY . .

# Assets compilados por el stage de frontend: pisan cualquier `public/build`
# que venga del contexto de build (esa carpeta está ignorada por git y puede
# estar obsoleta en la máquina que construye la imagen).
COPY --from=frontend /app/public/build ./public/build

# Generar autoload optimizado y descubrir paquetes
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-interaction \
    && php artisan package:discover --ansi \
    && rm -f /usr/bin/composer

# El bind mount de desarrollo queda como "dubious ownership" para git al correr
# comandos como www-data (Composer lo consulta y falla con un warning).
RUN git config --system --add safe.directory /var/www

# Crear directorios de Laravel y asignar permisos correctos
RUN mkdir -p \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/framework/testing \
    /var/www/storage/logs \
    /var/www/storage/app/public \
    /var/www/storage/app/actas_firmadas \
    /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Copiar entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copiar configuración de Apache
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]