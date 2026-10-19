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

## Lote 14 — retiro del flujo viejo AsignacionResponsabilidad (commit 9afa74d)
- Rutas `/equipos/{id}/asignacion-responsabilidad*`, `AsignacionResponsabilidadController`
  y sus Requests eliminados. El MODELO y las migraciones (tabla histórica) se conservan.
- Tablero: KPI "Bajo responsabilidad" retirado; `DashboardService::kpis()` ya no
  recibe `responsabilidades()` ni expone la llave.
- `equipos/index`: quitados chips, fallbacks, dropdown "Gestionar Asignación Bajo
  Respons.", modal completo + modal de finalización, JS (abrirModalResponsabilidad*,
  branches del selector) y filtros proyecto/usuario_temporal/empresa_temporal;
  búsqueda por name/doc AR eliminada; filtro `estado=responsabilidad` mapea ahora a
  `estado_operativo = asignacion_responsable` (legacy).
- `equipos/show`: tarjeta AR reemplazada por "Responsable del Activo" (snapshot del
  CMDB: responsable_nombre/cédula + estado).
- Importador CMDB: ya no crea/cierra la capa AR; la modalidad vive en responsable_*;
  métricas `responsabilidades_creadas/actualizadas` fuera del Excel de importación.
- Export XLSX: provider `AsignacionResponsabilidadProvider` eliminado del listado.
  Verificado: export CMDB responde XLSX válido (52 KB, `file` = Microsoft Excel 2007+).
- Seeder demo y test `CmdbImportRelationshipTest` ajustados (sin capa AR).
- Playwright: 26/26 passed. Humo HTTP: dashboard/equipos (incl. filtro
  responsabilidad y búsqueda)/asignaciones/show → 200; KPI del tablero ya no
  aparece; `show` sin bloque AR.

## Lote 15 — préstamo como estado + préstamos emiten actas
- Migración `2026_10_18_000001_add_acta_id_to_prestamos` (FK opcional a actas).
- `PrestamoController::store` ahora EMITE el acta de novedad (tipo `prestamo`)
  vía `ActaService::registrar` DENTRO de la misma transacción: valida la regla de
  un computador por funcionario, crea acta + ítem y deja el activo en estado
  operativo `prestamo`. El préstamo guarda `acta_id` y el historial menciona el acta.
- Devolución y cancelación liberan el activo: `estado_operativo` vuelve a
  `disponible` cuando estaba en `prestamo`.
- `prestamos.show`: nueva tarjeta "Acta de novedad asociada" con botones Ver acta /
  PDF / Word / Paquete (ZIP), guardados para préstamos históricos sin acta.
- `prestamos.create`: aviso informativo de la emisión automática del acta.
- `Equipo::estado_label/estado_badge` cubren `en_operacion`, `asignacion_responsable`,
  `prestamo` y `reparación`; filtro de equipos incluye opción "Préstamo".
- E2E real por curl con jar autenticado: POST `prestamos` (302 → show #2) → acta #24
  creada (tipo prestamo, cédula del receptor) + ítem #711 + equipo 321 en estado
  `prestamo`; devolución → préstamo "Devuelto" + equipo `disponible`; `prestamos.show`
  renderiza la tarjeta del acta. Datos de prueba eliminados después.
- Playwright completo tras el lote: 26/26 passed.

## Fijo extra — vistas faltantes (500) de licencias/reportes e historial-administrativo
- `LicenciaController::reportes`: ahora sí calcula datos (licencias por estado,
  distribución por tipo, próximas a vencer en 60 días, vencidas recientes y las
  más asignadas con `withCount`).
- Nueva vista `licencias/reportes.blade.php`: KPIs con borde de color, cuatro
  paneles con tablas y enlaces a cada licencia; botón Exportar Excel.
- Nuevas vistas `historial_administrativo/index.blade.php` y
  `por_equipo.blade.php` (listas con filtros por búsqueda/tipo de cambio,
  badges del `TIPOS_CAMBIO`, crossover tabla valor_anterior → valor_nuevo).
- HTTP: `/licencias/reportes` 200, `/historial-administrativo` 200,
  `/equipos/{id}/historial-administrativo` 200. Playwright completo: 26/26.

## Lote 16 — la importación de la CMDB entra por el flujo completo (acta inicial por funcionario)
- Decisión del usuario: al importar el Excel de la CMDB que alimenta el inventario,
  cada funcionario recibe UN acta tipo `inventario_fisico` con todos sus activos
  (el inventario queda alimentado y documentado igual que el flujo manual).
- `ActaService::registrarCargaInicial(datos, equiposCarga, ?User)`: transaccional;
  sin tocar estados de equipos (el importador ya los dejó en `en_operacion`) y sin
  la restricción de un computador por funcionario (documenta la realidad actual).
  Idempotente: si el funcionario ya tiene ítems de `inventario_fisico` ligados a un
  acta, no genera otra; los ítems legacy sin acta quedan ENLAZADOS a la nueva acta
  y los activos de la carga sin ítem reciben uno nuevo (motivo "Importado del CMDB").
- `EquiposImport` (importador web): acumula por cédula al final de cada fila
  (`acumularParaActaInicial`) y tras `Excel::import` el controller llama
  `emitirActasIniciales(auth()->user())`; métricas nuevas
  `actas_iniciales_emitidas`/`actas_iniciales_fallos` y bloque de avisos con los
  fallos en `equipos/importar.blade.php`.
- Comando `cmdb:importar`: ya no crea ítems directos por fila; acumula grupos por
  funcionario y emite las actas al final (resumen con "Actas iniciales emitidas" y
  "Funcionarios ya documentados").
- E2E real con XLSX sintético (2 funcionarios, 3 activos, formato hoja
  Microinformatica): comando creó 3 equipos + 3 ítems agrupados en 2 actas
  (#27 CARLOS x2 activos, #28 LUISA x1); segunda corrida idempotente (0 actas);
  prueba de ítem legacy sin acta → enlazado a la nueva acta (#29). Camino web con
  formato cabecera CMDB: 2 activos de un funcionario → acta #30 con 2 ítems;
  re-ejecución → 0 actas nuevas. Datos de prueba borrados de la BD.
- Fix intermedio: `pluck('equipo.id')` no resuelve en arreglos anidados (data_get
  sobre clave dentro de scalar); sustituido por `map` que extrae el id del equipo.
- Incidente de prueba (no regresión): la primera corrida desalineada del XLSX de
  prueba dejó un `TipoRecurso` basura "ASIGNADO" (id 21) que el test Playwright de
  creación de activos tomaba por primera opción y fallaba la validación de
  `nombre_equipo`. Eliminado el tipo; suite completa 26/26 vigente.

## Pasada premium/combobox a vistas legacy — retiro de select2 (Lote 17)
- `prestamos/create` era la última vista con select2+jQuery: retirados CDN de
  select2 (CSS/JS y jQuery y su init). El select `equipo_id` queda nativo con
  `form-select` y lo auto-mejora el combobox global FNC (`fnc-combobox.js`),
  manteniendo el placeholder de la primera opción vacía.
- Pulido premium: ids + `label[for]` asociados (el label enfoca el campo visible
  del combobox), `aria-describedby` en el activo, y errores inline por campo con
  `@error(...) is-invalid/invalid-feedback` para equipo_id, persona_nombre,
  persona_documento, fecha_inicio y fecha_devolucion_prevista.
- Nota: no existe `prestamos/edit` (sólo create/index/show); el resto de vistas
  legacy con selects ya heredan el auto-mejora global.
- Playwright ampliado: el test de préstamos verifica el wrapper
  `.fnc-combo > select[name="equipo_id"]` y que no queden artefactos select2.
- Suite completa: 26/26 passed.

## Lote 18 — anulación de actas con reversión de efectos (+ impresión PDF con marca)
- Migración `2026_10_19_000001_add_anulacion_to_actas`: `actas` suma
  `estado_acta` (valida/anulada), `anulada_en`, `anulada_por`, `anulada_motivo`.
- `ActaService::anular(acta, motivo, user)`: transaccional; exige acta vigente,
  motivo obligatorio y que el acta sea el ÚLTIMO movimiento de todos sus activos
  (si hay ítems posteriores se rechaza todo-o-nada). Reversión según tipo:
  traslado/cambio_responsable no tocan estado; prestamo/reparación regresan a
  `disponible` si el equipo sigue en su estado de salida; inventario_fisico y
  retiro vuelven a `disponible` (el retiro de baja también recupera el activo).
  Préstamo del acta → `Cancelado` (los ya devueltos/cancelados no se tocan).
  Historial administrativo por activo (`prestamo_cancelado` + `acta_anulada`).
  Los ítems de la anulación anterior a la migración? No existe: los ítems nunca se
  borran, quedan como trazabilidad, con acta en estado `anulada`.
- `ActaController::anular` (POST `actas/{acta}/anular`, motivo min:10) y ruta.
- `actas/show`: banner rojo ANULADA (fecha, usuario y motivo), badge en el
  toolbar, botón "Anular acta" sólo para actas vigentes con modal de
  confirmación (bootstrap +等形式 doble confirmación JS) y aviso de efectos.
- Documentos con marca `ANULADA`: modulo PDF de novedad (bloque destacado con
  motivo), DOCX desde cero (línea superior) y XLSX desde cero (fila roja A4).
- `prestamos/show`: tarjeta del acta con badge/gris si está anulada.
- E2E real (jar autenticado):
  1) préstamo vía curl → préstamo #3 + acta #31 + equipo 311 en `prestamo`;
  2) POST anular → acta `anulada`, préstamo `Cancelado`, equipo `disponible`,
     historial con los dos cambios; el anuncio aparece en actas/show;
  3) segunda anulación → rechazada ("ya está anulada");
  4) guarda de movimientos posteriores → rechazada vía tinker;
  5) anulación de acta tipo inventario_fisico → equipo vuelve a `disponible`;
  6) PDF de acta anulada marca "ACTA ANULADA" (verificado sobre el blade).
  Datos de prueba eliminados (actas 31–33, préstamo 3, ítem fantasma, equipo 311
  restaurado a `disponible`).
- Playwright completo tras el lote: 26/26 passed.

## Lote 19 — Analista TIC con los mismos privilegios que Administrador
- `RolesAndPermissionsSeeder`: el rol `Analista Tic` ahora hace
  `syncPermissions(Permission::all())` (antes le faltaban licencias.*,
  configuracion.*, campos_personalizados.* y roles.*). Idempotente.
- `PermisosNuevosModulosSeeder`: `licencias.exportar` también para Analista Tic.
- Base de datos del despliegue actualizada ejecutando ambos seeders: el rol
  quedó con 37/37 permisos (igual que Administrador).
- Protección centralizada: nuevo helper `User::isAdmin()` (ROLE_ADMIN +
  ROLE_ANALISTA) y constantes `User::ROLE_ADMIN`/`ROLE_ANALISTA`; reemplaza en
  controllers y vistas los checks por nombre a mano:
  - `RoleController` (edit/update/destroy del rol Administrador),
  - `UserController` (asignar rol Administrador, editar/actualizar un
    usuario admin),
  - `SolicitudCambioPasswordPolicy` (todas las capacidades),
  - vistas `roles/index` y `usuarios/index` (edición de fila protegida).
- Nota: el listado de *solicitudes de cambio de contraseña* no tiene rutas
  activas en el despliegue actual (sólo el store público); el cambio de
  policy queda listo para cuando se reactiven.
- Verificado como mock `analista-tic`: /usuarios 200, /roles 200,
  /roles/1/edit 200 (rol Administrador editable por el analista), /licencias y
  /licencias/create 200, /tipo-recursos 200, /plantillas-pdf 200,
  /campos-personalizados 200, /equipos/importar 200, /actas/buscar 200.
  Tinker: `isAdmin=true`, `roles.editar=true`, `licencias.exportar=true`.
- Playwright actualizado: el test que esperaba 403 en licencias para el
  analista ahora valida 200 en licencias, roles y tipo-recursos. Suite 26/26.

## Lote 20 — Dashboard: hero de préstamos, tickets al fondo y gráfico Chart.js
- `resources/views/dashboard.blade.php` reorganizado:
  - **Préstamos por devolver** es ahora un panel **hero** a todo el ancho al
    inicio de la columna central (`fnc-board__hero`): 4 tarjetas de cifras
    grandes (Vigentes / Vencidos / Por vencer / Devueltos, cada una enlazada al
    listado con su filtro) + la cola de devoluciones en dos columnas. El panel
    duplicado que quedaba en `fnc-dash__rest` se eliminó.
  - **Tickets de soporte** (serie de 6 meses) y **Tickets abiertos** salieron
    del tablero a una **zona inferior propia** (`fnc-dash__foot`,_section_
    después de "Últimos activos registrados").
  - La columna central queda: hero → Por categoría / Próximos vencimientos →
    Licencias / Garantías → Antigüedad (span 2). `fnc-dash__rest` desapareció.
- **Por categoría reinventado con Chart.js**: nuevo componente
  `fnc/composition.blade.php` — dona Chart.js 4 vía jsdelivr (cutout 68%,
  bordes redondeados, tooltips con % — `rgba` del tooltip por callbacks), cifra
  total superpuesta al centro y leyenda generada en el servidor (queda legible
  si el CDN no carga; los registros `canvas[data-fnc-compo]` se inicializan con
  guardas + listener `load` como fallback). La dona SVG (`fnc/donut`) se
  conserva para otros usos.
- `resources/css/dashboard/dashboard-v2.css`: retícula del lado actualizada
  (`donut/venc` fila 1, `lic/gar` fila 2, `ant` span 2 fila 3), estilos del
  hero (`fnc-hero__stats/num/label/list/empty`, barra de acento y halo
  `radial-gradient`), zona `fnc-dash__foot`, layout `fnc-compo` (figura 200px +
  leyenda) y ajustes container-query (699.98px / 379.98px).
- Recompilados assets con Vite (`npm run build` en el contenedor node).
- `view:clear` + reinicio del servicio; verificado en navegador (admin mock):
  hero arriba con sus 4 KPI, canvases de Chart.js instanciados (dona con 6
  categorías, centro "318 Activos"), serie y cola de tickets al fondo.
- Playwright completo tras el lote: 26/26 passed.

### Ajustes del Lote 20 (post-validación)
- Se retiró el borde superior rojo (`border-top: 3px solid var(--fnc-brand)`)
  del hero "Préstamos por devolver".
- "Por categoría" reorganizado: la dona queda centrada arriba y el legendario
  server-rendered pasa a una columna a lo ancho debajo (etiqueta completa con
  `flex: 1 1 auto` y la cifra "valor (pct)" anclada a la derecha); en dos
  columnas y en el layout previo los nombres largos se truncan a una letra.
- Assets recompilados con Vite; Playwright 26/26 tras los ajustes.

### Lote 20 — refine 2: licencias junto a la dona, KPI de cupos y dona sin leyenda
- `DashboardService::licencias()`: ahora consulta TODAS las licencias con
  `cantidad_maxima` para calcular agregados de cupos (`cupos.usados` clamp a
  la capacidad, `cupos.disponibles = max(0, totales - usados)`,
  `cupos.totales`); el detalle `cumplimiento` sigue siendo el top 6 mapeado a
  filas de bar-list.
- Panel **Licencias** reconstruido: 4 tarjetas KPI enlazadas al listado
  (Usadas X de Y cupos · Disponibles libres de asignar · Vencidas con estado
  "flat" gris cuando es 0 · Por vencer en N días) + detalle "Cupos por
  licencia" con bar-list (usados/max, %, tono por saturación).
- Posición intercambiada: **Licencias** sube a la fila 1 (columna 2, junto a
  la dona "Por categoría") y **Próximos vencimientos** baja a la fila 2
  (columna 1). Antigüedad sigue a dos columnas.
- Dona "Por categoría" sin leyenda: las categorías solo se muestran en el
  tooltip al pasar el cursor; aria-label describe la distribución. Figura
  ampliada a 216px; CSS de la leyenda (`fnc-compo__legend`) eliminado.
- Playwright 26/26 tras los cambios.

## Lote 21 — Panel "Tareas" en la columna derecha (dividida verticalmente)
- **Migración** `2026_10_19_000002_create_tareas_table` (aplicada): tabla
  `tareas` para tareas manuales (titulo, descripcion, modulo
  general|equipos|funcionarios, prioridad Alta|Media|Baja, estado
  Pendiente|Hecha, FK equipo/funcionario/created_by/hecha_por, hecha_en,
  softDeletes, índice estado+prioridad). Aplicada con rollback+re-migrate
  (primera corrida salió sin `deleted_at`, requerido por el modelo).
- **Modelo** `App\Models\Tarea`: scopes `pendientes` y `ordenPrioridad`
  (FIELD Alta→Media→Baja, más recientes primero).
- **Servicio** `DashboardService::tareasPendientes($verEquipos)`: mezcla tres
  orígenes — (1) manual persistidas, (2) activos CMDB con campos vacíos o
  "PENDIENTE" (nombre_equipo, marca, modelo, serial, procesador) — se derivan
  al vuelo con enlaces a equipos.edit, (3) funcionarios con vacíos (cargo,
  area, departamento, ciudad, tipo_vinculación) → funcionarios.edit. Devuelve
  items (capados a 10), total y resta para el "+N más". Las derivadas no se
  persisten: desaparecen al corregir el dato de origen. También se corrigió la
  clave duplicada `'prestamos'` en el retorno del servicio.
- **Controller + rutas** (`TareaController`, permiso dashboard.ver):
  `POST /tareas` (store), `POST /tareas/{tarea}/alternar` (toggle hecha),
  `DELETE /tareas/{tarea}`. `route:clear` + restart.
- **UI** (`dashboard.blade.php` + CSS): la columna derecha ahora es
  `.fnc-dash__right` (flex column) con **Tareas arriba** y **Disponibilidad
  abajo**. Panel Tareas: pill de total en la cabecera, filas con icono/check
  (manual) o icono de módulo (CMDB), meta "Faltan: …" (3 campos + "y más"),
  pill de origen (prioridad o CMDB), botón eliminar en las manuales, bloque
  "y N tareas más…" y **alta rápida** al pie (input + select prioridad nativo
  `data-fnc-native` + botón Crear, error inline `@error`). Estilos
  `.fnc-tarea*` y responsive del contenedor (<999.98px apila a ancho completo).
- Probado en vivo (admin mock): panel con 87 derivadas + alta manual
  (prioridad Alta, aparece primera con pill Alta, botones check/x), toggle →
  "Hecha", DELETE → soft-delete (verificado en BD; tabla quedó vacía).
- Playwright 26/26.

### Lote 21 — ajuste: columnas extremas igualadas a lo largo del tablero
- `.fnc-dash__right > .fnc-board__dispo { flex: 1 1 auto }`: la columna
  derecha (Tareas + Disponibilidad) llena el mismo alto que las internas,
  heredando el `align-items: stretch` del board.
- Las tarjetas de Categorías (izquierda) y de Disponibilidad (derecha) se
  reparten con `justify-content: space-between` dentro del alto disponible,
  así ambas columnas terminan al ras con el borde inferior del tablero.
- Sin efecto colateral en apilado (<1000px): las alturas quedan por contenido.
- Playwright 26/26.
### Lote 21 — ajuste 2: el largo del tablero lo marcan las internas
- Causa raíz del desbordamiento: el contenido alto de la columna derecha
  (lista de tareas + tarjetas de disponibilidad) empujaba la altura de todo
  el board y las extremas terminaban más abajo que las internas.
- Tope con scroll interno: `ul.fnc-tareas` max-height 338px y
  `.fnc-dash__right .fnc-board__dispo .fnc-cats` max-height 430px (ambas con
  `overflow-y: auto` + `overscroll-behavior: contain`); así definido por el
  contenido de las columnas internas (middle).
- Disponibilidad conserva `flex: 1` + `min-height: 0` dentro de la columna
  derecha, y Categorías/Disponibilidad reparten con space-between.
- Verificado emulando el layout de 3 columnas con estilos temporales via
  browser.evaluate: board 2497px y los 3 `bottom` (cats/right/middle)
  exactamente iguales, luego se removió el estilo de prueba.
- Playwright 26/26.
### Lote 21 — ajuste 3: reparto flexible del sobrante en la columna 4
- Con topes fijos (338/430px) quedaba blanqueo dentro del panel
  Disponibilidad cuando la columna sobraba altura.
- Reemplazo por reparto flexible: Tareas y Disponibilidad quedan flexibles
  (flex 1 1 auto + min-height 0, con el body en flex column); el sobrante se
  divide entre las dos secciones. La lista de tareas crece con base 240px
  (scroll interno sólo si no alcanza); el listado de categorías de
  Disponibilidad toma el espacio restante con base 300px.
- Tarjetas de Disponibilidad: `flex: 1 1 84px` + `height: auto` y tope
  `max-height: 150px` — altura por tarjeta = espacio disponible ÷ n.º de
  categorías (piso 84px, scroll si no cabe); el `space-between` de la lista
  reparte el excedente entre tarjetas si tocan el tope.
- Verificación con la emulación de 3 columnas: los tres bordes inferiores
  coinciden (2497px) y la brecha bajo la última tarjeta es 0; alturas
  uniformes de 150px por tarjeta. Estilo temporal removido al final.
- Playwright 26/26.
### Lote 21 — ajuste 4: sin overlay ni desangre en las tarjetas de Disponibilidad
- Al piso (84px), los labels de dos líneas ("Equipo Portátil", "Impresora
  Multifuncional") hacían que la meta "disponibles" saliera por el borde.
- Piso subido a 92px + `overflow: hidden` + tipografía compacta (label
  0.78rem/1.18, clamp 2 líneas) y meta en una sola línea recortada con
  ellipsis (display block + nowrap + text-overflow), scoped a la columna
  derecha.
- Verificación forzando el piso (6 tarjetas a 92px) y el techo (150px):
  `algunaDesangra: false` en ambos casos, scroll interno solo si no cabe.
- Playwright 26/26.
### Lote 21 — ajuste 5: sin scrollbar visible en la columna 4
- `scrollbar-width: none` + `::-webkit-scrollbar { display: none }` en
  `.fnc-tareas` y en `.fnc-board__dispo .fnc-cats` (scoped a la columna
  derecha): el scroll interno funciona con rueda/gesto pero sin barra.
- Verificado en navegador: `scrollbar-width: none` computado y ambas listas
  desplazables. Playwright 26/26.
### Lote 20 — ajuste 3: chips de porcentaje en "Por categoría"
- Debajo de la dona se añade una fila de chips: cuadrito del color de la
  categoría + el porcentaje que ocupa, sin texto de nombre (el nombre queda
  como tooltip del chip y del segmento en la dona).
- El porcentaje se calcula en el componente (`valor / suma * 100`, 1 decimal)
  y los chips llevan `aria-label` + `title` con el nombre para lectores de
  pantalla y hover; estilos `.fnc-compo__chips/__chip/__swatch` (pill
  compacta, swatch 9px, flex wrap centrado).
- Verificado en navegador: 6 chips (35.8/30.3/23.2/7/2.1/1.5) con los mismos
  colores de la dona. Playwright 26/26.
### Lote 20 — ajuste 4: tabla "Últimos activos registrados" corporativa premium
- Siguiendo el skill de frontend (jerarquía visual + mínima carga cognitiva +
  a11y) se rediseñó la tabla de últimos activos (`resources/views/dashboard.blade.php`).
- Panel: pill contador "N recientes" + enlace "Ver inventario".
- Fila: cuadro con icono del tipo de recurso (mapeo nombre→lucide:
  portátil→laptop, escritorio/todo-en-uno/monitor→monitor, tableta→tablet,
  impresora→printer, cajón→package, servidor→server, teléfono→smartphone,
  teclado/mouse/headset→…) con tinte de marca; nombre como enlace + sub-línea
  "marca · modelo"; categoría en micro-píldora; serie/placa monoespaciada
  tabular; asignado con monograma circular (iniciales, o círculo punteado
  "—" si va sin asignar) + cédula como sub-línea; estado igual en píldora
  con punto; "Registrado" relativo ("Hoy", "Hace N d") + fecha exacta;
  acción como botón de icono chevron con aria-label descriptivo.
- Responsive con container query (`dash`): ≤980px oculta Categoría;
  ≤680px oculta además Asignado y Registrado (esos datos viven en la ficha).
- Hover de fila con tinte de marca al 4%; sin zébra; hairlines finas.
- Verificado: 6 filas, icono/mono/píldora/relativo presentes, columnas
  colapsan en contenedor estrecho y reaparecen a 1161px emulados.
- Playwright 26/26.
### Lote 22 — nav: fuera el despliegue inline de subcategorías en Activos y Configuración
- "Activos" y "Configuración" tenían `data-bs-toggle="collapse"`: al hacer
  clic el submenú (los 3 y 2 iconos) se desplegaba en el propio nav (y la
  sección activa quedaba abierta con la clase `show`).
- Eliminado el toggle de Bootstrap y el chevron; el padre ahora navega a su
  destino (Activos → /equipos; Configuración → tipo-recursos, o campos
  personalizados si no puede editar configuración).
- El submenú vive como `<div hidden>`: sólo es la fuente de datos de la
  card flotante (`nav-flyout`), que se sigue mostrando al hacer hover en el
  rail (verificado: Activos → Inventario/Complementos/Préstamos;
  Configuración → Categorías/Campos Personalizados).
- 0 toggles inline en el sidebar; la navegación móvil usa el sheet "Más".
- Playwright 26/26.
### Lote 23 — tema de iconos premium sin recortes + fin del flash de modo claro
- **Iconos**: trazo del sprite Lucide aligerado de 1.75 a 1.5 (129 símbolos)
  y `transform="translate(1.2 1.2) scale(0.9)"` en el `<use>` del componente
  `x-fnc.icon`: el glifo se inseta 10% dentro del viewBox y ningún trazo
  vuelve a tocar el borde (key-round llegaba a x≈23.4 y se recortaba la
  esquina derecha; wrench/users también iban al límite).
- Icono del rail subido a 21px (círculo 41px) para conservar el peso visual
  con el glifo más pequeño; verificado con zoom 3.2x en claro y oscuro:
  wrench, engranaje y llave completos, más aire y líneas finas.
- `iconoTema()` del layout alineado al mismo trazo/inset.
- **Flash de modo claro**: el tema se aplicaba en `DOMContentLoaded` (tras
  el primer paint). Ahora un script inline en el `<head>` (antes de los
  CSS, línea 21) lee `fnc_theme` de localStorage y fija `data-bs-theme`
  en `<html>` antes de cualquier pintado; el toggle del body sólo
  sincroniza. Verificado: tras recargar en dark, fondo oscuro inmediato.
- Playwright 26/26.
### Lote 24 — módulos de gestión agrupados en la card de Configuración
- Usuarios Sistema, Roles y Permisos, Auditoría, Backups e Importar dejan de
  ser ítems sueltos del rail (y con ellos la sección "Seguridad") y pasan a
  la card flotante de Configuración junto a Categorías y Campos
  Personalizados (7 ítems).
- El rail queda en 11 iconos; cada ítem del flyout conserva su @can
  (roles.ver, configuracion.editar, equipos.importar).
- El ícono padre se ilumina en cualquiera de los módulos agrupados
  ($isConfigActive ampliado) y navega al primer destino permitido
  (tipo-recursos → campos-personalizados → usuarios → importar).
- Plantillas de Actas sigue como ítem propio ("Actas"), sin doble activo.
- El sheet "Más" del móvil ya listaba estos módulos; se mantiene.
- Verificado: flyout con 7 enlaces correctos y captura visual; rail sin
  ítems sueltos. Playwright 26/26.
### Lote 25 — paneles de hover con gracia: accesibilidad y perfil
- Los paneles flotantes de accesibilidad (rail) y de perfil (header) se
  ocultaban al cruzar el hueco entre el disparador y el panel (12px en
  accesibilidad, sin puente; el de perfil tenía puente corto).
- Puente invisible ampliado en ambos (::before de 14px sobre el hueco) +
  gracia JS de 220 ms (.is-open con mouseenter/mouseleave y timer
  cancelable), el mismo patrón del flyout del nav.
- Verificado con eventos sintéticos: enter abre; leave mantiene abierto a
  los 100 ms; re-entrar cancela el cierre; a los ~450-550 ms cierra si el
  cursor no llegó. Playwright 26/26.
### Lote 26 — piel corporativa premium para las tablas de todos los módulos
- **Base global** (macos.css): todas las `.table` heredan la lengua del
  tablero — micro-encabezados mayúsculas 0.64rem/800, hairlines finos, sin
  cebra (--bs-table-accent-bg neutralizado), hover con tinte de marca al 4%,
  fondo transparente (--bs-table-bg) y densidades compactas para .table-sm.
  `x-ui.table` adelgazado (sin bg-white/rounded/shadow; thead transparente).
- **Tratamiento ficha** (clases fnc-udash__*, ya globales) en 15 módulos:
  - equipos/index: icono por tipo + serial sub; monogramas de responsable y
    asignado (punteado "—" si sin asignar); tipo en micro-píldora neutra;
    estado en x-fnc.pill con estadoTone; th dinámicos sin clases chillonas.
  - prestamos: ficha de activo con icono, monograma de persona, inicio y
    vencimiento relativos + exactos, pill de estado, acción chevron.
  - funcionarios: monograma + nombre enlazado, cédula mono, cargo/área a dos
    niveles, conteos en pills (estado, activos, autorizaciones).
  - tickets: número mono, monograma de solicitante y responsable, estado y
    prioridad en pills semánticas, tiempo abierto relativo, acción chevron.
  - licencias: chip key-round + tipo, cupos y vencimiento en pills/relativo.
  - licencia-asignaciones e historial de licencias: monogramas, pills de
    acción semánticas, fechas relativas, placa mono.
  - auditoría: acción con tono semántico (crear/eliminar/editar/sesión),
    relativa + exacta, monograma.
  - usuarios: monograma del kit (reemplaza círculo Bootstrap), rol en pill,
    alta relativa + exacta. roles: chip escudo, conteo en pill.
  - actas firmadas: chip file-badge, fecha relativa, monograma.
  - historial administrativo y técnico: fichas, pills de tipo/estado,
    monogramas y relativas.
  - tipo-recursos: chip tags + conteos en pills; campos-personalizados:
    pills de módulo/tipo/config con drag intacto.
- Vistas secundarias (complementos, importar, plantillas, reportes, history)
  quedan con la piel base; theads table-light retirados.
- Nueva novedad (actas/create) intacta por petición expresa.
- Verificado módulo a módulo (ficha/pills/monogramas renderizan, 0 errores);
  capturas de equipos, funcionarios, usuarios, historial y categorías.
  Playwright 26/26.
