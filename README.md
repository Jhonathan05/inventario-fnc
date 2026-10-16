# Inventario de Equipos — FNC

Sistema de gestión de inventario / CMDB y activos TI para la **Federación Nacional de Cafeteros de Colombia (FNC)**.

Monolito **Laravel 12 + Blade** (SSR, sin API REST) que cubre el ciclo de vida de equipos de cómputo: registro CMDB, custodia y asignaciones con actas, mesa de ayuda, licenciamiento, auditoría y reportes.

## Módulos

- **CMDB:** equipos (serial, placa, activo fijo, specs), tipos de recurso, complementos, periféricos, campos personalizados, importación/exportación Excel masiva.
- **Custodia:** asignaciones (asignación/reemplazo/retiro/mantenimiento/baja/restauración), actas PDF/DOCX/XLSX firmables, asignación bajo responsabilidad, autorizaciones, préstamos con control de vencimientos.
- **Operación:** mesa de ayuda (tickets, diagnósticos, seguimientos, evidencias), historiales técnico/administrativo/complementos, checklists, garantías y mantenimientos.
- **Licenciamiento:** módulo unificado de licencias (seriales, cupos, asignaciones, historial, reportes).
- **Administración:** usuarios, roles/permisos (Spatie), auditoría, plantillas PDF con variables, cambio obligatorio de contraseña al primer login.

## Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP `^8.2`, Laravel `^12` |
| Auth | laravel/breeze `^2.4` |
| Roles/permisos | spatie/laravel-permission `^6.25` |
| Excel | maatwebsite/excel `^3.1` + phpspreadsheet |
| PDF | barryvdh/laravel-dompdf `^3.1` |
| Frontend | Blade + Tailwind `^3` + Bootstrap 5 (CDN) + Alpine.js + Vite `^7` |
| BD | MySQL `8.0` (`utf8mb4_unicode_ci`) |
| Tests | PHPUnit `^11` + Playwright `@playwright/test` |

## Requisitos

- PHP 8.2+ con extensiones `pdo_mysql`, `gd`, `zip`, `mbstring`, `bcmath`, `exif`, `pcntl`
- Composer 2
- Node.js 20+ / npm
- MySQL 8.0 (o Docker)

## Instalación (local)

```bash
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# editar .env: DB_*, SYSTEM_ADMIN_* (solo primera instalación)
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=TipoRecursoSeeder
npm install && npm run build
```

El script de composer `setup` agrupa instalación + key + migrate + build (`composer setup`).

## Instalación (Docker)

```bash
docker compose up -d                          # desarrollo  -> http://localhost:3300
docker compose -f docker-compose.test.yml up -d   # MySQL de test (:3308)
```

## Desarrollo

```bash
composer dev      # server + queue:listen + pail + vite (concurrently)
# o manual:
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
```

Puertos por defecto: app `http://localhost:3300`, MySQL `localhost:3307`.

## Tests

```bash
composer test              # config:clear + php artisan test
npm run test:e2e           # Playwright sobre tests/e2e (baseURL :3300)
npm run test:e2e:headed
npm run test:e2e:report
```

> `phpunit.xml` usa `DB_CONNECTION=mysql`: requiere una base de datos de pruebas viva (p. ej. `docker-compose.test.yml`, puerto `3308`).

## Estructura

```
app/Http/Controllers/   controladores (equipos, asignaciones, tickets, licencias, admin...)
app/Models/             modelos Eloquent
app/Services/           PDF/DOCX, historial, importadores
app/Exports/ app/Imports/  Excel (providers por bloque de columnas)
routes/web.php          rutas web (auth + verified + permisos Spatie)
routes/debug.php        rutas de depuración (solo entornos no productivos)
resources/views/        vistas Blade + componentes UI
database/migrations/    esquema (equipos, licencias, auditoría, complementos...)
tests/{Unit,Feature}/   pruebas PHPUnit
tests/e2e/              pruebas Playwright
docs/contexto.md        contexto técnico detallado del proyecto
```

## Seguridad

- No versionar `.env` ni secretos. `docker-compose.prod.yml` contiene valores de ejemplo: **rotar** `APP_KEY`, contraseñas de DB y `SYSTEM_ADMIN_PASSWORD` antes de desplegar.
- Las rutas de depuración (`/test-export`, `/test-logo`, `/instalar-plantilla-fnc`) viven en `routes/debug.php` y **no se registran cuando `APP_ENV=production`**.
- Los scripts auxiliares de depuración están en `scripts_y_backups_temporales/` (fuera de `public/`).

## Documentación

Ver `docs/contexto.md` para arquitectura, modelo de datos, permisos, flujos y comandos en detalle.
