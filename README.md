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

## Interfaz (design system macOS)

La capa visual sigue las guías de Apple (macOS) descritas en `docs/macos.md` y las maquetas `docs/layoutv2.png` / `docs/layout1..7.webp`:

- `resources/css/design-system/macos.css` se importa **al final** de `resources/css/app.css`; sus clases usan el prefijo `.fnc-` y sus tokens `--fnc-*` (radios, sombras, vidrio, tonos) se redefinen en `[data-bs-theme='dark']`.
- Modo claro/oscuro conmutable (`fnc_theme` en `localStorage`) y control de tamaño de letra; el sidebar de vidrio sigue al tema.
- Iconos Lucide locales (`resources/views/partials/lucide-sprite.blade.php`, subset de `lucide-static`, ISC) vía `<x-fnc.icon name="..."/>`; sin CDN ni dependencias nuevas (gráficos en SVG/CSS renderizados en servidor).
- Paleta de comandos (`Ctrl`/`⌘`+`K`) cliente-servidor: destinos filtrados por `@can`, sin endpoints nuevos (`public/js/fnc-shell.js`).
- El tablero (`/dashboard`, `?ventana=15|30|60|90`) se alimenta de `App\Services\Dashboard\DashboardService` con métricas derivadas sólo del esquema real.

## Tests

```bash
# Composer no está instalado globalmente (el Dockerfile lo elimina de la imagen):
# se usa el phar versionado-ignorado del proyecto, dentro del contenedor.
# Atajo sugerido:  alias com="docker exec -u www-data laravel_app php composer.phar"

# PHPUnit: levantar antes la base de datos de pruebas (mysql_test, puerto 3308)
docker compose -f docker-compose.test.yml up -d
docker exec -u www-data laravel_app php composer.phar test       # config:clear + artisan test

npm run test:e2e           # Playwright sobre tests/e2e (baseURL :3300)
npm run test:e2e:headed
npm run test:e2e:report

docker exec -u www-data laravel_app php composer.phar analyse    # PHPStan + larastan (nivel 1, 0 errores)
docker exec -e COMPOSER_ALLOW_SUPERUSER=1 laravel_app php composer.phar audit
```

> `phpunit.xml` fuerza `APP_ENV=testing` y apunta a `inventario_testing`
> (`mysql_test`, puerto `3308`). `Tests\TestCase::createApplication()` aborta antes de
> migrar si la conexión no termina en `_test`/`_testing`, así la suite nunca puede borrar
> datos de desarrollo o producción. (El blindaje no puede ir en
> `beforeRefreshingDatabase()`: el trait `RefreshDatabase` de cada test sombrea los
> overrides del `TestCase` base y ese hook nunca se ejecutaría.)
>
> Cada override del bloque `<php>` se declara **además** como `<server>`: el contenedor
> arranca con `env_file: .env` y Laravel resuelve `env()` con `$_SERVER` antes que con
> `$_ENV`, mientras que PHPUnit sólo escribe `$_ENV`/`putenv()`. Sin las entradas
> `<server>`, dentro de Docker se ignoraría `phpunit.xml` (y `composer test` limpia
> `config:cache` por el mismo motivo).

### Entorno local para E2E

1. Levantar la aplicación y la base de datos:

   ```bash
   docker compose up -d --build      # app :3300, MySQL :3307
   ```

2. Crear `.env` (no se versiona) con los valores que consumen los tests. Las credenciales del administrador deben coincidir con las de `tests/e2e`:

   ```dotenv
   APP_ENV=local
   APP_DEBUG=true
   APP_KEY=base64:...            # php artisan key:generate
   APP_URL=http://localhost:3300

   DB_CONNECTION=mysql
   DB_HOST=db                    # nombre del servicio en docker-compose
   DB_PORT=3306
   DB_DATABASE=inventario
   DB_USERNAME=inventario
   DB_PASSWORD=inventario
   DB_ROOT_PASSWORD=root

   SYSTEM_ADMIN_EMAIL=admin@example.com
   SYSTEM_ADMIN_PASSWORD=password
   SYSTEM_FORCE_PASSWORD_CHANGE=false   # los tests esperan entrar directo al panel

   # Acceso: en local/E2E se entra con el mock por perfiles (ver "Autenticación institucional")
   AUTH_PROVIDER=mock
   MOCK_AUTH=true
   LOCAL_LOGIN_ENABLED=false
   KEYCLOAK_ENABLED=false
   ```

3. Migrar, sembrar y compilar assets:

   ```bash
   docker compose exec -u www-data app php artisan migrate --force
   docker compose exec -u www-data app php artisan db:seed --class=DatabaseSeeder --force
   npm install && npm run build
   npx playwright install chromium webkit
   ```

   > Ejecutar `artisan` con `-u www-data` es importante: si se corre como `root`, los
   > directorios generados en `storage/framework/cache` quedan sin permiso de escritura
   > para Apache y la aplicación responde **500**.

4. Ejecutar la suite:

   ```bash
   npm run test:e2e            # o: npx playwright test
   ```

Notas para escribir nuevos tests (ver `tests/e2e/helpers/app.js`):

- **Acceso**: ya no hay formulario de correo/contraseña. `login(page)` (o
  `loginAs(page, PROFILES.analistaTic)`) entra por el selector de perfiles mock y devuelve el `_tab`.
- La sesión está **particionada por pestaña** (`_tab`, `App\Auth\TabSessionGuard`). Tras
  `login()` hay que reutilizar el `_tab` devuelto en cada navegación (`gotoTab`); una URL
  sin `_tab` abre un contexto nuevo **sin autenticar**.
- Las vistas incluyen botones `type="submit"` ocultos (menú de usuario): usar
  `submitButton()` para evitar *strict mode violations*.
- La capa global muestra los errores de validación y los avisos con SweetAlert2
  (`public/js/app-core.js`, `initAlerts`). El modal de errores bloquea los clics, por lo
  que hay que cerrarlo con `dismissAlert()`.

> **Importante al cambiar PHP o vistas**: la imagen habilita OPcache con
> `opcache.validate_timestamps=0`, así que el contenedor **no recarga el código**.
> Tras editar PHP o Blade hay que reiniciar: `docker compose restart app`
> (el `entrypoint` reconstruye `config:cache`, `route:cache` y `view:cache`).
>
> **Importante al cambiar `.env`**: `env_file` se lee al **crear** el contenedor, así que
> `docker compose restart app` **no** aplica los cambios de `.env` (y Laravel no
> sobreescribe variables ya presentes en el entorno). Hay que recrear:
> `docker compose up -d --force-recreate app`.


## Autenticación institucional

El acceso se hace **solo** con identidad institucional (Keycloak / OIDC). No hay login
local de correo+contraseña: el formulario queda deshabilitado por defecto y `POST /login`
responde **403** salvo que se ponga `LOCAL_LOGIN_ENABLED=true` (soporte/transición).

Tampoco hay registro ni recuperación de contraseña propios: los retiré junto con los flujos
de Breeze (`/register`, `/forgot-password`, `/reset-password/*`, `/confirm-password`,
`PUT /password`) y el módulo `profile`, que quedaron sin uso. El alta de usuarios y la
recuperación de credenciales los administra Keycloak desde su propia consola. Se conserva
`POST /solicitudes-cambio-password` (flujo de soporte de la app).

Implementado según las skills `fnc-keycloak-oidc-agenda`, `fnc-login` y `fnc-mock-auth`
(canon corporativo FNC, 55/45).

### Modos

| `AUTH_PROVIDER` | Entrada de acceso | Uso |
|---|---|---|
| `keycloak` | `/login` → botón **Continuar con Comité Tolima** → `/auth/keycloak` | producción |
| `mock` | `/login` → selector de perfiles sintéticos | desarrollo / E2E |

El mock tiene **doble candado**: `MOCK_AUTH=true` **y** entorno distinto de `production`.
Si cualquiera falla, todas las rutas mock responden `403`.

### Rutas

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/login` | Pantalla de acceso (selector mock o botón institucional) |
| `GET` | `/auth/keycloak` | Inicia OIDC (state + nonce + PKCE S256) |
| `GET` | `/auth/keycloak/callback` | Valida, resuelve rol y crea la sesión |
| `GET` | `/login-mock` | Selector de perfiles (`mock.auth`, dev-only) |
| `POST` | `/mock-auth/mock-login` | Autentica un perfil (`role=administrador\|analista-tic`) |
| `POST` | `/mock-auth/mock-logout` | Cierra la sesión mock |
| `POST` | `/logout` | Cierre de sesión (con Keycloak hace *RP-initiated logout*) |

### Perfiles mock

| Perfil | Rol local (Spatie) | Correo sintético |
|---|---|---|
| Administrador | `Administrador` | `administrador.mock@example.test` |
| Analista Tic | `Analista Tic` | `analista.tic.mock@example.test` |

Los usuarios mock se crean/actualizan de forma idempotente en el primer acceso y sólo
cambian el **origen de la sesión**: los roles y permisos (gate RBAC) son exactamente los
mismos que en producción. Mientras hay sesión mock se muestra el distintivo **Modo Mock**.

### Keycloak (OIDC)

Topología dual de red (ver `infra/keycloak-fnc`):

- `KEYCLOAK_URL` — *backchannel*, discovery/token por la red interna (`http://fnc-keycloak-s4:8080/auth`).
- `KEYCLOAK_PUBLIC_URL` — *frontchannel*, lo que ve el navegador (Cloudflare Tunnel).

Estándares de sesión aplicados (`fnc-auth`): sesión de 8 h con expiración por inactividad,
fingerprint, CSRF, `state`/`nonce`/`code_verifier` en cookies httpOnly de 15 min y
`prompt=login` (sin SSO implícito) salvo cuando se envía `kc_action`.

Mapeo de roles Keycloak → rol local en `config/keycloak.php` (`role_map`, `role_hierarchy`):

```
ADMIN/DIRECTOR/DIRECCION → Administrador
LIDER/COORDINADOR/ASISTENCIAL/FUNCIONARIO → Analista Tic
```

> El `id_token` se verifica **localmente**: firma RS256 contra el JWKS del realm
> (`jwks_uri`, cacheado) usando OpenSSL, más `iss`, `aud`, `exp`/`nbf` y `nonce`
> (`KeycloakClient::verifyIdToken`, sin dependencias JWT). Falla cerrado: cualquier
> anomalía rechaza el acceso.

### Variables de entorno

```dotenv
AUTH_PROVIDER=keycloak          # keycloak | mock
LOCAL_LOGIN_ENABLED=false       # habilita POST /login (email+contraseña)
MOCK_AUTH=false                 # dev-only; en producción SIEMPRE false

KEYCLOAK_ENABLED=true
KEYCLOAK_URL=http://fnc-keycloak-s4:8080/auth
KEYCLOAK_PUBLIC_URL=https://auth-sandbox.comitetolima.com/auth
KEYCLOAK_REALM=fnc-realm
KEYCLOAK_CLIENT_ID=inventario-fnc
KEYCLOAK_CLIENT_SECRET=
KEYCLOAK_DEFAULT_ROLE="Analista Tic"
KEYCLOAK_POST_LOGOUT_REDIRECT=/login
```

En Keycloak, el cliente debe tener como *Valid redirect URI*
`https://<host>/auth/keycloak/callback*` (el `*` permite conservar `_tab`, que mantiene el
aislamiento de sesión por pestaña).


## Estructura

```
app/Http/Controllers/   controladores (equipos, asignaciones, tickets, licencias, admin...)
app/Services/           PDF/DOCX, historial, importadores, Keycloak OIDC y provisión de identidad
app/Mock/               perfiles sintéticos del mock auth (dev-only)
app/Models/             modelos Eloquent
app/Exports/ app/Imports/  Excel (providers por bloque de columnas)
routes/web.php          rutas web (auth + verified + permisos Spatie)
routes/auth.php         acceso: Keycloak OIDC + mock por perfiles + logout
routes/debug.php        rutas de depuración (solo entornos no productivos)
resources/views/        vistas Blade + componentes UI
public/css/login-fnc.css  firma corporativa del login (generada por la skill fnc-login)
database/migrations/    esquema (equipos, licencias, auditoría, complementos...)
tests/{Unit,Feature}/   pruebas PHPUnit
tests/e2e/              pruebas Playwright
docs/contexto.md        contexto técnico detallado del proyecto
```

## Seguridad

- No versionar `.env` ni secretos. `docker-compose.prod.yml` contiene valores de ejemplo: **rotar** `APP_KEY`, contraseñas de DB y `SYSTEM_ADMIN_PASSWORD` antes de desplegar.
- El login es **institucional (Keycloak OIDC)**. El login local está deshabilitado (`LOCAL_LOGIN_ENABLED=false`) y el mock (`MOCK_AUTH`) **solo** funciona fuera de `production`.
- Dependencias auditadas: `composer audit` está limpio y `composer.json` no ignora avisos (`block-insecure` en su valor por defecto), de modo que `composer install/update` falla ante una vulnerabilidad conocida.
- Las rutas de depuración (`/test-export`, `/test-logo`, `/instalar-plantilla-fnc`) viven en `routes/debug.php` y **no se registran cuando `APP_ENV=production`**.
- Los scripts auxiliares de depuración están en `scripts_y_backups_temporales/` (fuera de `public/`).

## Documentación

Ver `docs/contexto.md` para arquitectura, modelo de datos, permisos, flujos y comandos en detalle.
