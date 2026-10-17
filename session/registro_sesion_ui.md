# Registro de Sesión - Modernización UI & Integración de Documentos

## Fecha y Hora
- **Fecha:** 29 de Septiembre, 2026
- **Rama Git Activa:** `UI`
- **Repositorio:** `https://github.com/Jhonathan05/inventario-fnc.git`

---

## Resumen de Avances

### 1. Integración de Servicios de Documentación (DOCX / XLSX)
- Implementación del servicio `DocumentGeneratorService` (`App\Services\DocumentGeneratorService.php`).
- Incorporación de soporte para descarga de actas institucionales editable (`.docx`) y hoja de novedades (`.xlsx`).
- Plantillas institucionales almacenadas en `storage/app/formatos/` (`responsabilidad.docx`, `novedad.xlsx`).
- Rutas integradas y expuestas en el controlador de asignaciones.

### 2. Modernización Visual y Estructural de Interfaz (UI)
- **Sistema de Tokens CSS (`resources/css/global/app.css`):**
  - Incorporación de variables dinámicas de color institucionales (`--primary-color: #9e052b`, `--brand-gradient`).
  - Escala de sombras de elevación (`--shadow-xs` a `--shadow-xl`) y bordes redondeados adaptativos.
  - Implementación del sistema de variables para Tema Oscuro (`[data-bs-theme="dark"]`).
- **Sidebar Modernizado:**
  - Fondo gradiente oscuro profesional (`#0f172a` a `#1e293b`).
  - Íconos con efectos hover dinámicos y animación al expandir/colapsar.
  - Badges translúcidos y destaque visual de navegación activa.
- **Topbar con Glassmorphism & Gestor de Tema Oscuro:**
  - Barra superior con efecto traslúcido y desenfoque (`backdrop-filter: blur(12px)`).
  - Botón selector de Tema Oscuro / Claro en el Topbar con persistencia mediante `localStorage`.
- **Dashboard KPI Rediseñado (`resources/css/dashboard/dashboard.css` & `dashboard.blade.php`):**
  - Reemplazo de inline styles rígidos por tarjetas dinámicas de alto impacto.
  - Íconos de tarjetas KPI con sombras y gradientes (*Emerald*, *Amber*, *Rose*, *Indigo*, *Purple*).
  - Adaptabilidad fluida tanto en tema claro como oscuro.

---

## Estado del Repositorio y Versionado
- **Tag Oficial Base:** `v1.0.0` (cargado en `main`).
- **Rama Actual de Trabajo:** `UI`
- **Commits Realizados en `UI`:**
  - `feat: integracion de servicio de generacion de documentos DOCX y XLSX`
  - `style(ui): actualizacion de tokens css, sidebar gradiente y glassmorphism en topbar con soporte dark mode`
  - `style(dashboard): tarjetas KPI modernizadas con gradientes y soporte para dark mode`

---

## Próximos Pasos (Fases Restantes del Plan UI)
1. Modernización de componentes de tablas (diseño elevado, paginación estilizada).
2. Rediseño de formularios (floating labels, validaciones visuales).
3. Rediseño de la pantalla de inicio de sesión (`login.blade.php`) en pantalla dividida institucional.
4. Micro-animaciones adicionales y verificación responsive en dispositivos móviles.

---

# Registro de Sesión — Rediseño de shell y tablero (réplica de la referencia)

## Fecha y Hora
- **Fecha:** 17 de Octubre, 2026
- **Rama Git Activa:** `main`
- **Alcance:** maqueta visual del shell completo (rail + topbar + cabecera de contenido) y
  del tablero, con los datos actuales. La reorganización de categorías se valida aparte.

## Decisiones de diseño
- Rail de iconos (~74 px; 66 px en modo compacto) que se expande a ~252 px (236 px) con
  etiquetas. Estado en `localStorage` (`inv_nav`) aplicado antes del primer pintado y
  botón de alternancia en la cabecera del rail. Por defecto: rail colapsado.
- Flyout en árbol para *Activos* y *Configuración*: clona el `<ul class="nav">` del menú,
  se ancla al borde del rail y se limita al viewport. Los ítems hoja navegan directo.
- Topbar en tres zonas: marca + título (marca movida desde el sidebar, que queda solo
  móvil), centro intercambiable por vista (`@section('topbar-center')`) y acciones. El
  tablero pone ahí el segmentado 15/30/60/90 días + buscador; el resto de vistas conserva
  el buscador global, así que `Ctrl+K` sigue funcionando.
- Cabecera de contenido de `x-ui.toolbar` unificada (título grande + acciones): aplica a
  las 63 vistas que usan el componente.
- Tablero con 6 paneles y **container queries** (mide el ancho real disponible, no el del
  viewport): 4 columnas, 2 por debajo de 1000 px de tablero y 1 por debajo de 640 px.
- Sin degradado de fondo: se mantiene el fondo neutro actual.
- Tipografía Plus Jakarta Sans autoalojada (`public/fonts/*.woff2`).
- Sin archivos específicos de podman y sin datos de demo: no se inventan activos.

## Fases
- [x] F0 Shell: rail + flyout + alternancia + topbar + cabecera de contenido.
- [x] F1 Tablero: retícula de 6 paneles con datos reales.
- [x] F2 Componentes: tarjetas de categoría, barras, filas de evento/ticket, estados vacíos.
- [x] F3 Responsive: 4 / 2 / 1 columnas, modo compacto, móvil con nav inferior.
- [x] F4 Verificación headless + navegador real.

## Verificación
- 1440 / 1280 / 1024 / 500 px → 4 / 4 / 2 / 1 columnas, sin overflow horizontal y sin
  texto truncado; rail expandido a 1280 px (tablero de 966 px) → 2 columnas por container query.
- Fuente `"Plus Jakarta Sans"` aplicada y woff2 servido con 200.
- Alternancia de rail: clase + `localStorage` (`expanded` / `rail`), ancho 66 ↔ 236/252 px.
- Flyout: se abre con Inventario / Complementos / Préstamos, etiquetas visibles y dentro del viewport.
- Vistas de listado (equipos, tickets, usuarios, licencias, préstamos): 200, sin errores.
- Commit del rediseño: `b27b341` (respaldo previo en `0c3eb3c`).

## Lote 2 — controles de navegación y tarjetas (17 de Octubre, 2026)

Ajustes pedidos sobre cómo se muestra la información y los controles de navegación:

- **Rail:** cada icono del menú vive en su propio círculo (39 px) sin fondo de fila,
  como la referencia; el ítem activo rellena el círculo (oscuro en tema claro, claro en
  tema oscuro). El botón de alternancia y los botones del pie también son circulares.
- **Topbar:** sólo "Inventario ITAM" (se elimina el subtítulo institucional). Se esconde
  al bajar (> 180 px de scroll) y reaparece al subir o al llegar al tope.
- **Paleta Ctrl+K:** acompaña al header — si estaba abierta, se cierra con el mismo flujo
  de Escape de `fnc-shell.js` (desbloquea el body y devuelve el foco).
- **Botón volver arriba:** `#scrollTopFab` circular, fijo abajo a la derecha; aparece
  tras 320 px de scroll y sube al tope con scroll suave. En móvil se acomoda sobre la
  barra inferior y el botón de escaneo.
- **Tarjetas de categoría:** calcadas de las "My Tasks" de la referencia — icono suelto
  (sin contenedor) en la esquina superior izquierda, cifra en la superior derecha y
  debajo el título con su mini descripción, todo sobre el pastel del tono. El contenido
  (los 10 KPI) se mantiene igual.

Verificación del lote:
- Medición headless en 1440/1280/1024/500 px: círculos de 39 px con radio completo, fila
  activa sin fondo, tarjetas de 76 px con icono/cifra en la fila superior y título debajo;
  sin overflow horizontal ni texto truncado.
- Navegador real: al bajar 700 px la topbar sale del viewport (top -76) y aparece el
  botón (44 px, abajo-derecha); abrir la paleta y bajar la cierra (body desbloqueado);
  al subir la topbar vuelve a top 10; el clic en el botón devuelve a y = 0.

## Lote 3 — rail sin tarjeta: círculos flotantes (17 de Octubre, 2026)

Análisis por píxeles de la referencia adjunta (equivale a `docs/layout1.webp` y un recorte de la
misma familia): su nav es una columna de **círculos blancos planos** (~28 px, apilados casi
contiguos) flotando **directamente sobre el fondo gris del aplicativo**; sin tarjeta contenedora,
sin borde, sin sombra y sin degradado; el rail no marca el ítem activo.

Cambios aplicados en el nuestro:

- **El rail deja de ser tarjeta:** `#sidebar` transparente, sin borde, sin radio y sin sombra;
  los círculos flotan sobre el fondo del aplicativo (que ya es el gris neutro actual).
  Sin líneas divisorias (sólo aire entre grupos) y paso vertical compactado a ~49 px.
- **Círculos blancos planos** (`--fnc-surface-solid`) con glifo `--ink-500`; hover `--ink-soft`.
- **Activo:** círculo relleno `--ink-900` plano (sin sombra) y glifo invertido. Los tokens
  invierten solos en modo oscuro, así que se elimina el override manual de tema.
- **Toggle y botones del pie** (A− / A+ / tema): círculos planos sin borde, 39 y 34 px.
- El flyout y el sidebar móvil no se tocan (el primero sigue siendo un menú emergente con
  tarjeta; el segundo vive sólo en <769 px).

Verificación del lote:
- Medición headless (1440/1280/800): sidebar `rgba(0,0,0,0)`, borde 0, radio 0, sombra none;
  iconos 39 px con radio completo; activo `#1f2937` con sombra "none" y glifo blanco; fila
  activa transparente; toggle 39 px sin borde; paso 49 px; sin overflow ni texto truncado.
- Navegador real (CSS nuevo cargado): ítems normales con círculo `#ffffff` y glifo `#6b7280`;
  activo `#1f2937`; flyout de Activos operativo (Inventario / Complementos / Préstamos).

## Lote 4 — nav sólo iconos, header sin fondo y accesibilidad con hover (17 de Octubre, 2026)

- **Icono activo plano:** sin borde ni sombra también a nivel de fila (app.css proyectaba
  un `box-shadow` rojizo sobre `.nav-link.active`; se neutraliza desde shell.css).
- **Fuera el nav expandido:** el rail queda sólo con iconos y las subcategorías se
  alcanzan con el flyout al pasar el cursor. Se eliminan el botón de alternancia, su JS,
  el estado `is-nav-expanded` y todas las reglas asociadas.
- **Header sin fondo:** la topbar toma el color de página (`--bg-light`) sin borde ni
  sombra. El logo pasa a ser la versión PNG sin fondo de `docs/brand/itms.jpeg`
  (generada por flood-fill del blanco sobre el JPEG; servida desde
  `public/imagenes/itms.png`).
- **Accesibilidad:** círculo plano al pie del rail que despliega por hover/foco una
  tarjeta con A−, A+ y el tema claro/oscuro (que se muda aquí desde la topbar). En móvil
  el interruptor de tema queda en un botón propio de la topbar porque el drawer del
  sidebar está desactivado en ese breakpoint.
- **Sesión de usuario:** píldora plana con avatar + nombre + cheurón, sin rol ni fondo,
  como los círculos planos del header de la referencia.

Verificación del lote:
- Medición headless en 1440/1280/800/500: topbar `rgb(236, 239, 246)` (= `--bg-light`)
  con borde 0 y sin sombra; a11y trigger 39 px circular; logo `itms.png` con
  naturalWidth 512; sin overflow ni texto truncado.
- Navegador real: tarjeta de accesibilidad abre por foco con A− / A+ / Tema; el tema
  conmuta light→dark→light actualizando el glifo (sol/luna) en la tarjeta y en el botón
  móvil y persistiendo en localStorage; flyout de Activos operativo; sesión "A
  Administrador Mock" en píldora plana; sin `navToggleBtn` ni `is-nav-expanded`; sin
  errores de consola.

## Lote 5 — header transparente con controles blancos y perfil reorganizado (17 de Octubre, 2026)

- **Eyebrow fuera del tablero:** se elimina "ITAM · Federación Nacional de Cafeteros — Tolima"
  de la cabecera del dashboard (queda sólo "Panel de control" + subtítulo).
- **Header sin fondo:** la topbar pasa a `transparent` — antes llevaba un plano `--bg-light`
  que se notaba como banda sobre el degradado real del canvas (radiales fijos del body).
  Los controles pasan a blanco: buscador, botones circulares y el segmentado de ventana
  (con el ítem activo ahora en gris hundido para no perder contraste).
- **Perfil reorganizado:** fuera el cheurón de flechas; el bloque de texto pasa a la
  izquierda con el **nombre arriba y el rol debajo**, y el avatar a la derecha.
- **Avatar plano:** sin sombra (conserva el sólido oscuro del sistema, radio completo).

Verificación del lote:
- Medición headless (1440/1280): topbar `rgba(0,0,0,0)` con borde 0 y sin sombra; buscador,
  botones y segmentado `rgb(255,255,255)`; avatar `#1f2937` con sombra `none` y radio 50%;
  rol "Administrador" bajo el nombre; sin cheurón; sin eyebrow; sin overflow ni truncados.

## E2E completo (17 de Octubre, 2026)

**Suite Playwright (`tests/e2e`), 23/23 en verde** tras corregir un test que el rediseño
volvió ambiguo: `page.locator('header')` resolvía a dos elementos (la topbar y la nueva
cabecera de contenido); ahora apunta a `header.topbar`. Cobertura: selector mock, login por
perfil, contexto de pestaña, RBAC (analista 403 en licencias), logout, login local
deshabilitado, listado/búsqueda de activos, creación de activo con validaciones,
préstamos, complementos e historial, y 404.

Ejecución en contenedor efímero (`node:22-bookworm` con `--network=host`, chromium con
`--no-sandbox`, artefactos fuera del repo). Nota para reproducirlo: los binarios del
navegador persisten en el volumen `e2e_pw_browsers`, pero las librerías del sistema no —
cada corrida necesita `npx playwright install --with-deps chromium` antes de los tests.

**Barrido HTTP de 93 rutas GET como administrador:** 86 sanas. 7 rotas (500), todas
preexistentes y **huérfanas** (ninguna enlazada desde la UI):

| Ruta | Causa |
|---|---|
| `historial-administrativo` | vista `historial_administrativo.index` no existe |
| `equipos/{equipo}/historial-administrativo` | vista `historial_administrativo.por_equipo` no existe |
| `licencias/reportes` | vista `licencias.reportes` no existe |
| `roles/{role}` | `RoleController::show()` no implementado |
| `usuarios/{usuario}` | `UserController::show()` no implementado |
| `tipo-recursos/{tipo_recurso}` | `TipoRecursoController::show()` no implementado |
| `tickets/{ticket}/edit` | `TicketController::edit()` no implementado |

Sin errores de consola en las páginas clave y sin regresiones del rediseño en los flujos
ejercitados.

## Lote 6 — layout de la columna de categorías (17 de Octubre, 2026)

- **Columna izquierda a 200 px** (pista fija de la retícula; en dos columnas se mantiene
  200 px + fluida, y en una columna el panel ocupa el ancho completo).
- **Tarjetas a 160 x 110 px**, centradas en el panel: icono suelto y cifra arriba, título
  y mini descripción **anclados al pie** (fila flexible intermedia). La descripción puede
  ocupar dos líneas (line-clamp) en lugar de truncarse. Cifra a 1.3 rem, icono a 20 px,
  separación entre tarjetas a 10 px y cuerpo del panel a 12 px.
- En móvil (tablero de una columna) las tarjetas fluyen al 100% del ancho.
- Nota técnica: la base `.fnc-cat` se declara después de los container queries, por lo que
  el ajuste de una columna necesita especificidad extra (`.fnc-cats .fnc-cat`).

Verificación: 1280/1024 → panel 200 px y tarjetas 160 x 110; 500 → tarjeta fluida (435 px)
sin overflow; etiqueta a 19 px bajo el icono y meta pegada a la etiqueta; sin texto
truncado; tablero a 4 / 2 / 1 columnas según el ancho del contenedor.

## Próximos pasos
1. **Siguiente lote de mejoras:** el usuario sigue iterando el layout del tablero.
2. Con las tarjetas a 110 px, la columna de categorías mide ~1300 px y estira los paneles
   de la derecha (dona, vencimientos, tickets quedan con aire de sobra). Pendiente de
   validación visual del usuario; si molesta, se puede repensar el reparto de filas.
3. **Decidir qué hacer con las 7 rutas huérfanas** del barrido E2E (implementar o retirar).
4. Gráfico de tickets: se ve aireado porque el ancho limita la escala del SVG.
