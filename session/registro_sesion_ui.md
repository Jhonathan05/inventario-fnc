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

## Lote 7 — retícula lateral con altura según contenido (17 de Octubre, 2026)

**Problema:** con la columna de categorías a ~1300 px (tarjetas de 110), el panel que abarcaba
las dos filas del grid inflaba éstas a ~640 px y los paneles de las columnas 2 y 3 (dona,
gráfico, barras) quedaban llenos de aire.

**Solución — desacoplar la columna de las filas:**
- El tablero pasa de grid de 4 columnas a **flex de dos bloques**: el panel de categorías
  (200 px fijos, altura natural) y `.fnc-board__side`, una retícula interna con los otros
  cinco paneles cuyas filas se dimensionan por contenido (`auto auto` + `align-content: start`).
- Composición interna intacta: dona/gráfico/vencimientos en fila 1, barras ocupando dos
  columnas en fila 2 y tickets en la tercera columna. Dentro de cada fila los paneles se
  igualan al más alto de la fila (diferencias pequeñas, estándar de grid).
- Los estados vacíos del tablero (vencimientos/tickets) ahora se centran en vertical dentro
  de su panel (regla `:has(> .fnc-empty)` extendida desde la franja inferior).
- Responsive: <1000 px el side pasa a 2 columnas (dona+gráfico / vencimientos+tickets /
  barras completa); <640 px el tablero se apila en una columna (categorías a ancho completo
  con tarjetas fluidas y `flex-basis` neutralizado).
- Consecuencia asumida con el usuario: la zona derecha queda compacta (~750 px) y el resto
  hasta el pie de la columna de categorías es fondo de página.

Verificación: 1440/1280 → side de 739/759 px con dona/gráfico/vencimientos a 434/453 px y
barras/tickets a 289 px (antes filas de ~640 px con vacío); 1024 → side a 2 columnas; 500 →
todo apilado y con alturas naturales; sin overflow ni texto truncado en ningún ancho.

## Lote 8 — columna Disponibilidad y reordenamiento en tres columnas (17 de Octubre, 2026)

- **Nueva columna "Disponibilidad"** en el extremo derecho, con la misma piel que
  Categorías (200 px, tarjetas de 160 x 110): una tarjeta por tipo de recurso con
  existencias, con la **cantidad de equipos disponibles** y un **botón por tarjeta** que
  alterna hacia los **equipos en uso** (y de vuelta): cambia cifra, texto ("equipos
  disponibles" / "equipos en uso"), tono (celeste/verde) e icono (package/laptop), con
  `aria-pressed` para accesibilidad.
- **Nuevo bloque en `DashboardService::disponibilidad()`**: por tipo de recurso, total,
  usados (activo/asignado) y disponibles; sólo categorías con existencias. Con los datos
  actuales son 6 tarjetas (Tableta 0/117, Portátil 0/99, Escritorio 0/74, Impresora 0/7,
  Todo en Uno 0/5 y Cajón 1/0 — este último creado por la suite E2E).
- **Tablero a tres columnas:** Categorías (200 px) | columna central | Disponibilidad (200 px).
  La central (`.fnc-dash__middle`, contenedor propio `middle`) contiene ahora todo lo
  demás: retícula 2 x 2 (dona + gráfico / vencimientos + tickets), franja de paneles
  (Licencias, Antigüedad, Responsabilidades, Garantías, Préstamos) y la tabla de últimos
  activos. El pie con la fecha de cálculo queda a todo el ancho.
- **Fuera el panel "Disponibilidad del parque"** de barras por estado: su nombre y concepto
  pasan a la nueva columna (el dato por estado ya vive en las tarjetas de Categorías).
- Responsive: el tablero se apila por debajo de 1000 px de contenedor (tarjetas laterales
  fluidas) y la retícula central pasa a una columna por debajo de 520 px de central.
- Incidente corregido durante el lote: los closures de `withCount` declarados con `: void`
  provocaron un fatal ("A void function must not return a value"); sin el tipo de retorno.

Verificación: 1440/1280 → tres columnas (categorías 200 px / centro 848-704 px /
disponibilidad 200 px con 6 tarjetas de 160 x 110 y botón); alternancia probada en
navegador real (0 ↔ 117 con tono, icono y `aria-pressed`); sin errores de consola;
1000/500 → apilado con tarjetas fluidas; sin overflow ni truncados.

## Lote 9 — tabla de últimos activos a todo el ancho (17 de Octubre, 2026)

- La tabla "Últimos activos registrados" sale de la columna central y pasa a ocupar
  **todo el ancho del contenedor, bajo las tres columnas** (posición de las 3), justo
  antes del pie con la fecha de cálculo.
- La columna central queda con la retícula 2 x 2 + la franja de 5 paneles.

Mapa medido a 1280 tras el cambio: banda de 3 columnas de 1663 px (categorías 1297,
centro 1663 = retícula 704 + franja 944, disponibilidad 800); tabla a 1136 px de ancho
y 466 px de alto, bajo la banda. Pendiente de decisión del usuario: qué hacer con el
desequilibrio de los flancos (366 px de fondo bajo Categorías y 863 bajo Disponibilidad).

## Lote 10 — sin "Responsabilidades por vencer" y columnas al alto uniforme (17 de Octubre, 2026)

**Redefinición del flujo acordada con el usuario:** el acta de responsabilidad no dictamina
fecha de fin ni fecha fin estimada; es sólo el formato que acompaña a la única acta de
custodia, que es el acta de asignación por medio del acta de novedad de activos. El acta de
responsabilidad debe emitirse en conjunto con el acta de novedad de activos con tipo de
novedad "Inventario Físico" (pendiente de implementar como flujo).

**Aplicado en el tablero:**
- Fuera el panel "Responsabilidades por vencer" de la franja central (la franja queda con
  4 paneles: Licencias, Antigüedad, Garantías, Préstamos).
- `DashboardService::responsabilidades()` simplificado a sólo contar actas activas; fuera
  `porVencer`, `vencidas`, `cola`, la constante de aviso y la alerta "Responsabilidades con
  fecha cumplida"; el KPI pasa a "actas de responsabilidad activas"; la firma de `alertas()`
  pierde el parámetro que quedó sin uso.
- **Columnas de los extremos fijadas al alto uniforme** (`align-items: stretch` en la banda):
  las cuatro columnas cierran en la misma línea.

Mapa medido a 1280: banda de 1136 x 1359 px con categorías, centro (retícula 704 + franja
639) y disponibilidad terminando juntos en 1359; tabla de últimos activos a todo el ancho
(1136 x 466) bajo la banda; 6 tarjetas de disponibilidad; sin errores.

## Fase 2 — Definición de flujos del aplicativo (17 de Octubre, 2026)

### Flujo 1: Asignación de activos (acta de novedad "Inventario Físico") — DEFINIDO

**Definición de dominio acordada:**
- La única acta de custodia es el **acta de asignación por medio del acta de novedad de
  activos**; el acta de responsabilidad es el formato que la acompaña (sin fecha de fin ni
  estimada).
- Un acta incluye **uno o varios activos sin relación entre sí** (portátil + teclado +
  mouse + impresora + video beam en un solo movimiento).
- El activo debe estar **`disponible`** para poder asignarse; al asignar pasa a
  **`asignado`**.
- **Un único estado por movimiento**: el tipo de novedad define el estado resultante de
  todos los activos del acta.
- **Préstamo es otro estado** (custodia temporal), con fecha de inicio y fecha probable de
  fin; la asignación por Inventario Físico no lleva fechas.
- Modelo de datos: **tabla nueva `actas`** (cabecera con responsable y snapshot) y las
  asignaciones pasan a ser ítems del acta.

**Estados por movimiento (catálogo corregido por el usuario — v2):**

| Movimiento | Estado requerido | Estado resultante | Fechas |
|---|---|---|---|
| Inventario Físico (asignación) | disponible | asignado | sin fecha de fin |
| Traslado | asignado | asignado | — |
| Cambio de Responsable | asignado | asignado | — |
| Retiro definitivo | asignado, reparación | disponible **o** baja (se elige en el acta) | — |
| Préstamo | disponible | prestamo (nuevo) | inicio + fin probable |
| Reparación (antes "mantenimiento") | disponible, asignado | reparación | — |

**Renombrado global:** todo lo que diga "mantenimiento" pasa a "reparación" (valor del
`estado_operativo`, etiquetas, KPI, filtros y tablero). El estado `activo` legacy se trata
como sinónimo de `asignado`.

**Resueltos por el usuario (v3):** (1) Retiro definitivo: un solo destino (disponible o
baja) para todos los activos del acta; (2) la salida de reparación usa el MISMO movimiento
Reparación invirtiendo quien entrega/quien recibe — entrada: el usuario asignado entrega al
Analista TIC (→ reparación); salida: el Analista TIC entrega al último funcionario asignado
(→ asignado) — por eso el historial por activo individual es clave; (3) Traslado cambia
ubicación con el mismo responsable y Cambio de Responsable cambia el custodio; (4)
reemplazo y restauración desaparecen del catálogo; (5) el módulo del menú también se llama
"Reparaciones" (hegemonía semántica).

**Paso a paso:** (1) nueva novedad con su tipo; (2) responsable con snapshot (empresa,
dependencia, cargo, área, piso, ciudad, vinculación); (3) activos — el buscador sólo ofrece
los del estado requerido por el tipo; (4) motivo, observaciones y entregado por; (5)
validación transaccional con `lockForUpdate` (todo-o-nada); (6) se crea el acta con un ítem
por activo, cada activo cambia al estado del movimiento y queda en el historial; (7) paquete
documental del acta: acta de asignación (PDF/Word) con todos los ítems, **acta de
responsabilidad** (Word) con la lista de activos, planilla de novedades (Excel) con un
renglón por ítem; (8) firmas y descarga desde el detalle del acta.

**Modelo de datos objetivo:** `actas` (tipo_accion, fecha_accion, snapshot del responsable,
motivo, observaciones, entregado_por, fecha_inicio y fecha_fin_probable nulas para
préstamo, acta_previa_id para enlazar reemplazos, user_id, soft deletes) + `asignaciones`
como ítems (`acta_id`, `equipo_id`). Se retira `asignaciones_responsabilidad` y
`estado_operativo` gana el valor `prestamo`.

**Plan de implementación acordado (lotes):**
- Lote 11 — Fundación: migraciones (`actas`, `asignaciones.acta_id`, `estado_operativo` con
  `prestamo`), modelos y `ActaService::registrar` transaccional. Aditivo, nada se rompe.
- Lote 12 — UI del acta: `ActaController` (create/store/show), rutas y formulario
  multi-activo (buscador sobre activos en el estado requerido según el tipo).
- Lote 13 — Paquete documental desde el acta: PDF de asignación, acta de responsabilidad y
  planilla de novedades listando todos los ítems.
- Lote 14 — Retiro del flujo viejo: `AsignacionResponsabilidadController`, sus formularios
  en equipos y el KPI "Bajo responsabilidad" del tablero (recableado a los activos
  asignados o eliminado).
- Lote 15 — Préstamo como estado: el módulo de préstamos emite actas tipo préstamo con sus
  fechas y la devolución devuelve a `disponible`.

### Flujo 2: CMDB + acta de novedad en un solo movimiento — ANÁLISIS Y DISEÑO

**El formato CMDB** (`CMDB USUARIO FINAL.xlsx`, 13 hojas). La hoja "Microinformática" (306
filas) es la fila plana del activo con 51 columnas en 4 bloques:
- **Custodia (1-14)**: empresa propietario, dependencia, fuente de recurso, empresa del
  funcionario, empleado/contratista, cédula, shortname, nombres, departamento, ciudad,
  cargo, área, ubicación y piso — coincide con el snapshot `usuario_*` de `asignaciones`.
- **Quien recibe (15-20, 44-46)**: cédula/nombres/cargo/ciudad/área + FNC, dirección,
  contacto y teléfono de quien recibe (despachos).
- **Activo (21-41)**: tipo, serial, placa, marca, modelo, estado de uso, nombre de equipo,
  estado operativo, razón del estado, administrado/controlado, procesador, RAM, disco, SO,
  fecha de compra, fin de garantía, tiempo de uso, tipo de propiedad, checklist responsable
  TI, orden de remisión, observaciones.
- **Auditoría (42-51)**: tipo aprobado, no cruza con nómina/SAP/AV, uso > 15 años.

La hoja 10 trae los **dominios validados** (empresas, dependencias, fuentes, tipos,
**catálogo oficial de estados con descripción** — ALMACENADO, DISPONIBLE, TRÁNSITO, EN
OPERACIÓN, ASIGNACIÓN RESPONSABLE, PRÉSTAMO, BAJA, PROCESAR BAJA, HURTO, PENDIENTE — y
**razones del estado**: CUSTODIA, ALISTAMIENTO/SOPORTE, RENOVACIÓN, DESPACHO, RETORNO,
OBSOLETO, TEMPORAL…). La hoja 3 es el **maestro de funcionarios** (nómina: empresa, cédula,
apellidos/nombres, correo, cargo, área, jefe, sede, Ceco SAP, vencimiento, proceso).

**Brechas frente al esquema actual:** la ficha del activo ya existe en `equipos` (serial,
placa, marca, modelo, nombre, procesador, RAM, disco, SO, fechas) y el bloque de custodia
vive en el snapshot de `asignaciones` (el bloque `responsable_*` de `equipos` es el
duplicado legacy a retirar). **Faltan en `equipos`:** estado de uso, administrado/controlado,
tipo de propiedad, checklist responsable TI, orden de remisión, observaciones y
ubicación/piso; y no existe el bloque "quien recibe" ni las banderas de auditoría.

**Diseño propuesto — una sola fuente de verdad:**
1. El **acta es la única vía de escritura del CMDB**: todo cambio de estado o custodia pasa
   por un acta y, en la misma transacción, actualiza los campos CMDB del activo.
2. El formulario del acta tiene tres secciones: (a) movimiento — tipo, responsable
   (snapshot del maestro), quien entrega, quien recibe, fechas; (b) activos del acta
   (multi-selección sobre el estado requerido); (c) **ficha CMDB por activo** con los
   campos que el movimiento puede actualizar (razón del estado, estado de uso,
   administrado/controlado, tipo de propiedad, checklist, orden de remisión,
   ubicación/piso, observaciones) → se escriben en `equipos` al confirmar el acta.
   **Un solo movimiento rellena las dos fuentes.**
3. **Exportación CMDB**: vista `equipos ⨝ última acta` al formato de 51 columnas — la hoja
   manual queda reemplazada por el export del app, siempre sincronizado.
4. **Importación CMDB**: el mismo formato hace upsert de activos y funcionarios y, cuando la
   fila trae custodio, genera el acta de Inventario Físico correspondiente — el inventario
   importado nace con su historial de movimientos.
5. **Catálogos alineados**: las listas de validación de la hoja 10 se vuelven catálogos del
   app (estados + razones, empresas, fuentes, administrado/controlado, marcas).

**Abiertos por decidir:** (1) ¿se adopta el catálogo completo de estados/razones del CMDB
(distingue EN OPERACIÓN de ASIGNACIÓN RESPONSABLE para terceros/aprendices) o se mantienen
los 6 estados definidos con mapeo al exportar?; (2) ¿se sincroniza el maestro de
funcionarios desde la hoja de nómina?; (3) ¿las banderas de auditoría (no cruza
nómina/SAP/AV, uso > 15 años) son campos editables del activo o columnas calculadas del
export?

## Lote 12 — UI del acta de novedad con inputs y tablas premium (17 de Octubre, 2026)

- **Rutas:** `actas/create`, `actas` (POST), `actas/{acta}`, `actas/buscar` (AJAX).
- **ActaController** con create (formulario), store (valida y llama `ActaService`),
  show (detalle) y buscar (JSON de activos filtrados por el estado requerido del
  tipo de movimiento, con normalización a minúsculas).
- **CSS premium** (`resources/css/global/premium.css`, importado en app.css):
  - `.fnc-input`: input premium con borde fino, focus ring de marca y tipografía
    consistente; `.fnc-input--cell` para celdas de tabla (compacto, sin borde
    hasta hover, se vuelve input real al enfocar).
  - `.fnc-items`: tabla premium donde la **primera columna agrupa la identificación**
    (icono circular + nombre en negrita + serial/placa + marca/modelo), las
    **siguientes columnas llevan los campos CMDB** como inputs inline (estado de
    uso, administrado/controlado, tipo propiedad, orden remisión, checklist TI)
    y la **columna de acciones** es un botón circular plano que se pinta de rojo
    al hover.
  - `.fnc-picker`: buscador de activos con dropdown de resultados (icono + nombre +
    metadatos), que filtra por el estado requerido del tipo de movimiento.
- **Vista create:** 5 secciones — Movimiento (tipo + ubicación + fecha + destino
  condicional para retiro + fechas condicionales para préstamo), Responsable (snapshot
  CMDB de 14 campos), Activos (buscador + tabla premium con ficha CMDB inline por
  activo), Firmas (doble bloque: quien entrega / quien recibe), Observaciones.
- **Vista show:** detalle del acta con sus ítems, estado del equipo y botones del
  paquete documental (PDF, Word, Excel).

Verificado: rutas 200 sin errores; buscar retorna 15 activos en `en_operacion` para
`retiro_definitivo`, 0 para `inventario_fisico` (no hay `disponible`) y 15 para
`reparacion`; create renderiza el formulario completo; lint sin errores.

## E2E del módulo de actas — 24/24 OK (17 de Octubre, 2026)

Pruebas ejecutadas con datos reales del maestro de funcionarios (203) y equipos de
prueba creados en `disponible` dentro de una transacción que se revierte al final.

| Test | Escenario | Resultado |
|---|---|---|
| 1 | Asignar tableta (no computador) a funcionario sin computador | ✅ |
| 2 | Asignar portátil (computador) a funcionario sin computador | ✅ |
| 3 | Segundo computador SIN autorización | ✅ BLOQUEADO |
| 3b | Segundo computador CON autorización PDF | ✅ |
| 4 | Retiro definitivo → disponible | ✅ |
| 5 | Reparación — entrada | ✅ |
| 6 | Préstamo con computador actual en reparación | ✅ |
| 7a | Tableta a funcionario con computador activo | ✅ (regla sólo aplica a computadores) |
| 7b | Computador a funcionario con computador activo sin autorización | ✅ BLOQUEADO |
| 8 | Activos del funcionario visibles | ✅ (3 activos) |

Verificado además: snapshot del maestro copiado a los ítems, ficha CMDB actualizada en
la misma transacción, autorización guardada en el acta, fechas de préstamo persistentes,
rollback limpio (0 actas, 0 equipos de prueba).

## Próximos pasos
1. **Lote 13 — Paquete documental desde el acta**: PDF, Word y Excel listando todos
   los ítems del acta (hoy los botones en show usan el primer ítem).
2. **Lote 14 — Retiro del flujo viejo** (`AsignacionResponsabilidad` + KPI del tablero).
3. **Lote 15 — Préstamo como estado** (el módulo de préstamos emite actas).
4. **Aplicar el CSS premium a las demás vistas** (equipos/index, formularios, etc.).

## 2026-10-18 · Combobox premium global (estilo del buscador de referencia)
- `FNCCombobox` (`public/js/fnc-combobox.js`): reemplaza visualmente todos los
  `<select>` del aplicativo (incluye layouts inventario y guest) por un campo
  buscador redondeado + panel flotante con iconos (lupa; reloj para recientes),
  según la captura de referencia. El select nativo sigue en el DOM oculto:
  se conserva el envío del form, la degradación elegante y el `selectOption`
  de Playwright (elemento "visible" por bounding box, opacity 0).
- Interceptación de `value`/`selectedIndex` del select: cualquier asignación
  programática del código existente (`tipoSelect.value = 'prestamo'`, etc.)
  refresca la UI. `FNCCombobox.sync()` para rellenarSelect del drawer.
- Recientes por select en localStorage (`fnc-combo-recents:<name>`), máx. 3,
  con icono de reloj y sección "Recientes".
- Panel: `position: fixed` (nada lo recorta), z-index 1095 (bajo drawer 1090
  sí se muestra encima: 1095 > 1090), flip arriba/abajo según espacio,
  filtro sin acentos, navegación ArrowUp/Down + Enter/Esc/Tab, animación de
  entrada y variante compacta para celdas (`fnc-input--cell` → panel
  compacto).
- CSS nuevo `resources/css/global/combobox.css` (import tras stepper.css):
  `.fnc-combo`, `.fnc-combo__input`, `__chev`, `__panel`, `__opt`,
  `--compact`, estados `aria-invalid`/`is-invalid` (rojo) y fix del
  `.invalid-feedback` de Bootstrap, que perdió su hermano directo al envolver
  el select (ahora `.fnc-combo:has(> select.is-invalid) ~ .invalid-feedback`).
- Regresión encontrada y corregida en E2E: "Creación de activo con
  validaciones" fallaba porque el feedback quedaba hidden tras el envoltorio.
- Toggle: clic en campo ya abierto cierra; clic en campo enfocado reabre
  (supresión de focus 250 ms tras commit).
- Pruebas: nueva spec `tests/e2e/combobox.spec.js` (3 casos: acta/filtro/
  teclado/recientes, compatibilidad selectOption, mejora de selects
  dinámicos vía MutationObserver). Suite completa: **26/26 passed**.

## 2026-10-18 · Paquete documental del acta + modal de activos con identificación
### Modal "Ver activos asignados" (paso 2)
- `activosDe()` en ActaController ahora devuelve identificación completa por
  activo: consecutivo, activo_fijo, marca/modelo, serial, placa, tipo,
  es_computador, estado operativo, ficha CMDB (estado_uso, tipo_propiedad,
  administrado), ubicación (área/ciudad) y fecha de asignación
  (fecha_inicio_responsable). Verificado con SHOW COLUMNS.
- Render del modal reescrito: tarjetas por activo con chips de estado, badge
  "COMPUTADOR" cuando aplica, línea de identificación (consecutivo/serial/
  placa/activo fijo/tipo/marca), línea de ubicación (área · ciudad · desde
  fecha) y línea de ficha CMDB separada por guiones. En el header, conteo
  "N activos asignados · M computadores".

### Paquete documental (pendiente #2 del backlog)
- Nuevo `App\Services\ActaDocumentService` genera los documentos desde el
  ACTA COMPLETA (todos los ítems) y no del primer ítem:
  - `pdf()`  → dompdf sobre la nueva vista `pdf/acta_novedad.blade.php`:
    responsable, tabla consolidada (tipo, hostname, marca/modelo, serial,
    placa inv., activo fijo, ficha CMDB) y firmas. Letter, stream inline.
  - `docx()` → plantilla responsabilidad.docx REEMPLAZANDO la tabla de datos
    e insertando un bloque por ítem antes de "Declaro y acepto" (helpers
    replicados de DocumentGeneratorService). Si la plantilla no existe en
    storage/app/formatos (no está en este despliegue), genera un DOCX válido
    desde cero (ZipArchive, OOXML mínimo).
  - `xlsx()` → plantilla novedad.xlsx con insertNewRowBefore (N-1) filas y
    duplicado de estilos; firmas desplazadas 24+N / 27+N. Sin plantilla,
    construye la planilla con PhpSpreadsheet (título, responsable, tabla de
    12 columnas con auto-anchos, firmas).
  - `zip()`  → BinaryFileResponse con PDF + DOCX + XLSX y, si existe, el
    adjunto de autorización del jefe superior (disk local).
- `Asignacion::$fillable` ahora incluye `acta_id` (los ítems del acta).
- Rutas nuevas `actas/{acta}/pdf|docx|xlsx|paquete` con permiso
  `equipos.exportar`; botones de `actas.show` cambiados de la asignación
  del primer ítem al acta completa (+ botón "Paquete completo (ZIP)").
- E2E servicio (11 comprobaciones OK): PDF %PDF en streaming, DOCX/XLSX ZIP
  válidos, relectura del XLSX (cédula del responsable, numeración, 12
  cabeceras), DOCX con "Equipo N° 1/2" y firmas. HTTP con acta real sembrada
  (luego borrada + sus 2 equipos): 4/4 endpoints 200 con magia %PDF/PK y ZIP
  con los 3 archivos. Playwright completa: 26/26 passed.
