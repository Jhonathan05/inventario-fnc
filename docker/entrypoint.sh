#!/bin/sh
set -e

# ==============================================================================
# ENTRYPOINT — Inventario de Equipos (Producción)
# ==============================================================================
# Este script se ejecuta cada vez que el contenedor arranca.
# Es completamente idempotente: puede ejecutarse múltiples veces sin riesgo.
#
# NUNCA ejecuta: migrate:fresh, db:wipe, db:seed, schema:drop,
#                optimize:clear, cache:clear, config:clear,
#                route:clear, view:clear.
# ==============================================================================

echo "================================================="
echo "  Inventario de Equipos — Iniciando contenedor"
echo "================================================="

# 1. Crear directorios de Laravel si no existen
echo "[entrypoint] Verificando directorios de storage..."
mkdir -p \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/framework/testing \
    /var/www/storage/logs \
    /var/www/storage/app/public \
    /var/www/storage/app/actas_firmadas \
    /var/www/bootstrap/cache

# 2. Permisos
echo "[entrypoint] Asignando permisos..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# 3. Storage link (idempotente)
echo "[entrypoint] Creando storage link..."
php artisan storage:link --force 2>/dev/null || true

# 4. Cachear configuración (producción)
echo "[entrypoint] Cacheando configuración..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

# 5. Descubrir paquetes
echo "[entrypoint] Descubriendo paquetes..."
php artisan package:discover --ansi 2>/dev/null || true

# 6. Migraciones condicionales (controladas por RUN_MIGRATIONS)
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "[entrypoint] RUN_MIGRATIONS=true → Ejecutando migraciones pendientes..."
    php artisan migrate --force --no-interaction 2>/dev/null || echo "[entrypoint] AVISO: No se pudieron ejecutar las migraciones."
else
    echo "[entrypoint] RUN_MIGRATIONS=false → Migraciones omitidas."
fi

echo "================================================="
echo "  Inventario de Equipos — Contenedor listo"
echo "================================================="

# Ejecutar el comando principal (php-fpm)
exec "$@"
