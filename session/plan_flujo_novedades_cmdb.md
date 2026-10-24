# Plan — Flujo completo de novedades de activos + importación CMDB 100 %

> **Fecha:** 23-10-2026
> **Base:** `main` `841f99c` (árbol limpio; 256 archivos PHP con sintaxis OK).
> **Objetivo del usuario:** dejar **activo y completo** el flujo de novedades de activos,
> importar al 100 % el CMDB externo con datos exactos (inventario, asignado, responsable,
> funcionarios, activos y estados) y poder **seguir asignando y retirando** activos, con
> todas sus ramificaciones.
> **Regla del repo:** un lote = un commit + push; nunca `migrate:fresh`; backup antes de
> tocar datos; tras editar Blade `view:clear` + reinicio del contenedor.

> **Actualización 23-10-2026 (Fase 0 ejecutada):** el CMDB real está disponible en
> `docs/Formatos/CMDB USUARIO FINAL.xlsx` y su contrato quedó congelado en
> `docs/cmdb_contrato_columnas.md`. Hallazgo principal: el catálogo real del CMDB es de
> **6 estados + 13 razones + 6 estados de uso** (no 11 estados) — ver la corrección en D3.
> El análisis del archivo: 303 activos, 4243 funcionarios, clave de conciliación = placa.

---

## 0. Criterio de éxito (Definition of Done)

1. **Importación exacta:** el archivo CMDB real entra completo (todas las filas válidas),
   sin pérdida de estados, custodios, fichas CMDB ni funcionarios; segunda corrida =
   **0 cambios** (idempotencia demostrada con diff).
2. **Una sola vía de escritura:** todo cambio de estado/custodia pasa por un **acta**;
   ningún controlador, importador o vista escribe `equipos.estado_operativo`,
   `usuario_asignados` o `responsable_*` por fuera del servicio de custodia.
3. **Custodia consistente:** todo activo en estado que exige custodio tiene **exactamente
   un** custodio actual; todo activo sin custodio está en un estado que no lo exige.
4. **Flujo operativo completo:** asignar, trasladar, cambiar responsable, prestar,
   devolver préstamo, enviar/volver de reparación, retirar (a disponible/baja/procesar
   baja), reportar hurto y anular acta; todos con acta, ítem, auditoría y documentos.
5. **Reglas de negocio vivas:** un computador por funcionario (sobre custodia **actual**,
   no histórica), autorización del jefe para el segundo, autorizaciones consumidas,
   préstamos vencidos, actas anulables solo si son el último movimiento.
6. **Trazabilidad:** historial por activo (acta + ítem + cambio de estado + snapshot),
   historial administrativo y documentos PDF/DOCX/XLSX/ZIP por acta.
7. **Calidad:** PHPUnit + PHPStan + Playwright en verde; importación cubierta por tests
   unitarios/feature con un CMDB sintético de todos los estados; E2E del ciclo completo.
8. **Operación:** exportación CMDB de 51 columnas reconstruida desde la BD y conciliada
   contra el archivo fuente; reporte de diferencias en 0.

---

## 1. Diagnóstico del estado actual (medido en código)

### 1.1 Lo que ya existe y sirve

| Pieza | Estado | Evidencia |
|---|---|---|
| Tabla `actas` + `asignaciones.acta_id` | Operativa | `2026_10_17_120000_create_actas_table.php` |
| Catálogo de 11 estados + razones | Creado, **sin uso** | `2026_10_17_120300_create_catalogo_estados_and_razones_tables.php` |
| `ActaService::registrar` transaccional con `lockForUpdate` | Operativo | `app/Services/ActaService.php:31` |
| Regla “un computador por funcionario” | Implementada, con fallo de base | `ActaService.php:394` |
| Paquete documental por acta | Operativo | `app/Services/ActaDocumentService.php` |
| Anulación de acta con reversión | Operativa, con bugs | `ActaService.php:270` |
| Importador web CMDB + comando | Operativos, divergentes | `app/Imports/EquiposImport.php`, `app/Console/Commands/ImportarCmdb.php` |
| UI de acta multi-activo (4 pasos) | Operativa | `resources/views/actas/create.blade.php` |
| E2E de actas/préstamos/tareas/backups | 26+ pruebas | `tests/e2e/*.spec.js` |

### 1.2 Brechas críticas (bloquean el objetivo)

| # | Brecha | Impacto | Evidencia |
|---|---|---|---|
| **B1** | **`ActaService` no actualiza la custodia actual** (`usuario_asignados`) ni el snapshot `responsable_*` del equipo. Solo crea el ítem histórico y cambia el estado. | Tras asignar por acta, la ficha y el listado siguen mostrando “Sin asignar”; el import y los reportes leen otra fuente. | `ActaService.php:72-113`; `equipos/index.blade.php:240`; `equipos/show.blade.php:204` |
| **B2** | **Dos flujos paralelos de escritura**: `AsignacionService`/`AsignacionController` (legacy) y `ActaService` (nuevo). Ambos cambian estado y custodia con reglas distintas. | Historial mezclado, ítems sin acta, reglas duplicadas, imposible garantizar una sola verdad. | `app/Services/AsignacionService.php:28-315`; `app/Http/Controllers/AsignacionController.php:191`; `routes/web.php:150` |
| **B3** | **`EquipoController` escribe estado y custodia directamente** y fuerza `en_operacion` si hay funcionario. | Se salta el acta y el catálogo; estados inválidos (`activo`, `asignado`, `mantenimiento`). | `EquipoController.php:242-248, 399-405`; `EquipoRequest.php:49` |
| **B4** | **El importador sobrescribe el estado del CMDB** con `en_operacion` cuando hay funcionario. | Préstamos, reparaciones, bajas y hurtos entran como “en operación”; inventario inexacto. | `EquiposImport.php:392` |
| **B5** | **El importador no guarda la ficha CMDB** (`estado_uso`, `administrado`, `tipo_propiedad`, `checklist`, `orden_remision`, `observaciones`). | Se pierde información del archivo; el comando sí la guarda (divergencia). | `EquiposImport.php:272-300` vs `ImportarCmdb.php:102-121` |
| **B6** | **El importador no crea préstamos, reparaciones ni bajas**: solo `UsuarioAsignado` + acta inicial `inventario_fisico`. | No se puede reconstruir la realidad de un activo prestado/reparación/baja desde el CMDB. | `EquiposImport.php:364-437`; `ActaService::registrarCargaInicial` |
| **B7** | **Dos importadores con mapas distintos**: web (dinámico) y comando (índices fijos). El comando mapea mal `seccional`/`distrito` y no crea `Funcionario` ni `UsuarioAsignado`. | Resultados distintos según la vía; datos sucios. | `ImportarCmdb.php:223-238` |
| **B8** | **Regla “un computador” consulta historial**, no custodia actual. | Un funcionario que tuvo un equipo hace años queda bloqueado para siempre; falsos positivos. | `ActaService.php:409-414` |
| **B9** | **`activosDe()` del formulario de acta consulta historial** por cédula. | Muestra activos que ya no tiene; decisiones equivocadas en el formulario. | `ActaController.php:282-333` |
| **B10** | **Anulación rota en actas multi-ítem**: el guard compara `id > min(id)` incluyendo ítems de la misma acta. | No se puede anular un acta con 2+ activos. | `ActaService.php:291-298` |
| **B11** | **Estados oficiales incompletos en UI/validación**: faltan `almacenado`, `transito`, `procesar_baja`, `hurto`, `pendiente_estado`; `EquipoRequest` aún valida estados viejos. | No se puede representar el catálogo CMDB completo. | `Equipo.php:175-208`; `equipos/index.blade.php:44-51`; `EquipoRequest.php:49` |
| **B12** | **No existe devolución de préstamo como acta**; devolver/cancelar cambia el estado directamente. | El flujo “toda novedad es acta” queda incompleto. | `PrestamoController.php:182-268` |
| **B13** | **Reparación bidireccional ambigua** y `destino` validado solo como `disponible,baja`. | Salida de reparación no puede fijar `en_operacion`/`asignacion_responsable` desde el request. | `ActaService.php:446-470`; `ActaController.php:88` |
| **B14** | **No hay auditoría por movimiento** en `HistorialAdministrativo` (solo en anulación). | El historial de vida no refleja asignaciones/retiros/préstamos. | `ActaService.php` (sin `HistorialAdministrativo::create` en `registrar`) |
| **B15** | **`Funcionario` guarda el nombre completo en `nombres` y `apellidos=''`**; el maestro no importa correo, jefe, ceco, vencimiento ni proceso. | Duplicados y datos incompletos para actas/reportes. | `EquiposImport.php:398-415`; migración `funcionarios` |
| **B16** | **Periféricos se omiten** (`teléfono`, `teclado`, `mouse`, `cámara`) en vez de importarse como complementos. | Inventario incompleto si el CMDB los incluye. | `EquiposImport.php:40, 232-235` |
| **B17** | **Tipos de recurso se crean desde cualquier texto** (`firstOrCreate(['nombre' => ...])`), incluso valores de estado. | Tipos basura (“ASIGNADO”) y categorías duplicadas. | `EquiposImport.php:589-597`; `ImportarCmdb.php:92` |
| **B18** | **Importación no idempotente en el comando** (serial `uniqid()` cuando falta) y sin lote/reversión. | Duplicados y basura difícil de limpiar. | `ImportarCmdb.php:104` |
| **B19** | **`identificacion` cifrada con `unique`**: el índice no protege duplicados (IV aleatorio); no hay único sobre `identificacion_hash`. | Funcionarios duplicados posibles fuera del `updateOrCreate`. | `Funcionario.php:20`; migración `funcionarios` |
| **B20** | **No hay `acta_items` con estado anterior/resultante ni `vigente`**; el ítem no distingue si la custodia sigue abierta. | Imposible consultar “custodia actual” desde el historial sin heurísticas. | `Asignacion.php` (fillable) |

### 1.3 Hallazgos de importación que explican la inexactitud

- El **estado** se decide por `str_contains` con defaults silenciosos (`disponible` o
  `en_operacion`), no por el catálogo oficial.
- El **responsable** se decide por columnas ambiguas (`responsable_nombre` vs
  `nombre_usuario`); el bloque “quien recibe” (columnas 15-20/44-46) no se usa.
- El **acta inicial** agrupa por cédula y siempre es `inventario_fisico`, aunque el activo
  esté prestado, en reparación o de baja.
- La **ficha CMDB** del equipo se pierde en la vía web.
- El **maestro de funcionarios** (hoja “Listado funcionarios”) no se importa: solo se
  crea/actualiza el funcionario que aparece en cada fila de Microinformática.
- La **hoja de listas de validación** (estados/razones/empresas/fuentes) no se usa.

---

## 2. Decisiones de diseño propuestas

### D1. Una sola fuente de verdad para la custodia actual — con modalidad (confirmado)

**Propuesta:** `usuario_asignados` sigue siendo la **proyección de custodia actual**
(1 fila por equipo), pero pasa a ser **derivada y escrita únicamente** por
`CustodiaService` (nuevo), dentro de la transacción del acta. `asignaciones` es el
**historial inmutable** de movimientos. `equipos.responsable_*` es el **snapshot
desnormalizado** del último custodio (para listados/reportes), también escrito por el
servicio.

**Modalidad de custodia (decisión del usuario, 23-10-2026):** se diferencia
**custodia física** de **asignación funcional**:

| Modalidad | Significado | ¿Cuenta para “un computador por funcionario”? | Estados típicos |
|---|---|---|---|
| `asignado` | El activo está asignado a esa persona para su uso | **Sí** | `en_operacion` (razón `ASIGNACIÓN RESPONSABLE`, `CUSTODIA`, `PRÉSTAMO`) |
| `custodia_fisica` | La persona (p. ej. el ANALISTA TIC en bodega) solo **tiene** el activo; queda **disponible para asignar** | **No** | `almacenado` (razón `RETORNO`, `DISPONIBLE`, `ALISTAMIENTO / SOPORTE`) |

- El ANALISTA TIC puede tener **varios activos en custodia física**; solo son “suyos”
  los que estén `asignado`.
- `BODEGA` no es una persona del maestro: la custodia física en bodega se registra con la
  persona tenedora del archivo (ANALISTA TIC) + `equipos.ubicacion = 'BODEGA'`.
- **Baja/hurto/pendiente:** sin fila de custodia (el tenedor del archivo queda solo como
  historial).
- Regla institucional de bodega (descripción de la razón `CUSTODIA`): el activo entra con
  `RETORNO` y pasa a `DISPONIBLE` para asignación después de **15 días hábiles** — la
  asignación es elegible desde `RETORNO` con advertencia, y automática tras el plazo
  (comando programado opcional).

- No se crea una tabla nueva de custodias en esta fase (menor riesgo y menos cambios de UI).
- Se añade a `asignaciones`: `estado_anterior`, `estado_resultante`, `vigente` (bool),
  `fecha_fin`, `origen` (`acta|import|legacy`), `modalidad`.
- Se añade a `usuario_asignados`: `funcionario_id`, `acta_id`, `desde`, `hasta` (nullable),
  `modalidad` (`asignado|custodia_fisica`), y un índice único por `equipo_id` (ya es 1:1
  por `hasOne`; se formaliza).
- La UI distingue en listados y ficha: “Asignado a X” vs “En custodia de X (bodega)”.
- Los scopes de elegibilidad quedan: `Equipo::disponiblesParaAsignar()` =
  `almacenado` + razón `RETORNO`/`DISPONIBLE` (+ aviso si < 15 días hábiles);
  `Equipo::asignadosA($funcionario)` = custodia con `modalidad = asignado`.

### D2. El acta es la única vía de escritura

- `ActaService` (o `CustodiaService` invocado por él) es el único que puede cambiar
  `estado_operativo`, `razon_estado`, `usuario_asignados` y `responsable_*`.
- `AsignacionService` legacy se **envuelve** para delegar en `ActaService` (o se retira del
  menú y se deja solo para lectura histórica).
- `EquipoController` deja de aceptar `estado_operativo` en create/update: el estado se
  deriva del acta. El formulario de equipo solo edita ficha CMDB y datos técnicos.
- `PrestamoController` usa `ActaService`/`CustodiaService` también para devolución y
  cancelación: cierre del préstamo + constancia de devolución (sin tipo de acta nuevo,
  ver D6).
- Los importadores usan el mismo servicio para aplicar el estado/custodia de cada fila.

### D3. Catálogo único de estados (CORREGIDO con el archivo real)

> ⚠️ **Corrección 23-10-2026:** la decisión aprobada de “adoptar los 11 estados del CMDB”
> partía de un catálogo supuesto. El archivo real define un catálogo distinto
> (`docs/cmdb_contrato_columnas.md` §4):
> - **6 estados:** `ALMACENADO`, `TRÁNSITO`, `EN OPERACIÓN`, `BAJA`, `HURTO`, `PENDIENTE ESTADO`.
> - **13 razones:** `DISPONIBLE`, `CUSTODIA`, `ALISTAMIENTO / SOPORTE`, `RENOVACIÓN`,
>   `PROCESAR BAJA`, `DESPACHO`, `RETORNO`, `ASIGNACIÓN RESPONSABLE`, `PRÉSTAMO`,
>   `HURTO / PERDIDA`, `DEVUELTO A PROVEEDOR`, `DONACIÓN`, `BAJA`.
> - **6 estados de uso:** `FUNCIONES PROPIAS`, `SALA DE SISTEMAS / LABORATORIO`,
>   `PRUEBAS`, `MULTIUSUARIO`, `TEMPORAL / PRÉSTAMO`, `OBSOLETO / ALMACENADO`.
>
> PRÉSTAMO y REPARACIÓN **no son estados**: préstamo = `EN OPERACIÓN` + razón `PRÉSTAMO`
> (+ `estado_uso = TEMPORAL / PRÉSTAMO`); el taller = `ALMACENADO`/`TRÁNSITO` + razón
> `ALISTAMIENTO / SOPORTE` o `DEVUELTO A PROVEEDOR`. `DISPONIBLE` es razón de bodega, no
> estado. `ASIGNACIÓN RESPONSABLE` es razón de `EN OPERACIÓN`.

**Modelo objetivo (CONFIRMADO por el usuario 23-10-2026: “lo que digan los formatos”):**
`estado_operativo` se alinea 1:1 con los **6 estados** del CMDB; `razon_estado` con las
**13 razones**; `estado_uso` con los **6 usos**. Los matices operativos que hoy son
estados (`prestamo`, `reparacion`, `disponible`, `asignacion_responsable`,
`procesar_baja`) pasan a **razón + tablas propias** (`prestamos`, `mantenimientos`),
que ya existen.

**Mapeo de los estados actuales del sistema → CMDB:**

| Estado actual del sistema | Estado CMDB | Razón CMDB |
|---|---|---|
| `en_operacion` | `en_operacion` | `ASIGNACIÓN RESPONSABLE` (o `CUSTODIA`) |
| `asignacion_responsable` | `en_operacion` | `ASIGNACIÓN RESPONSABLE` (se fusiona) |
| `disponible` | `almacenado` | `DISPONIBLE` |
| `almacenado` | `almacenado` | `RETORNO` / `CUSTODIA` |
| `transito` | `transito` | `DESPACHO` |
| `prestamo` | `en_operacion` | `PRÉSTAMO` (+ estado_uso `TEMPORAL / PRÉSTAMO`) |
| `reparacion` | `almacenado` o `transito` | `ALISTAMIENTO / SOPORTE` o `DEVUELTO A PROVEEDOR` |
| `baja` | `baja` | `BAJA` |
| `procesar_baja` | `baja` | `PROCESAR BAJA` (se fusiona como razón) |
| `hurto` | `hurto` | `HURTO / PERDIDA` |
| `pendiente_estado` | `pendiente_estado` | — |

- Nueva clase `App\Domain\Activos\EstadoOperativo` (o enum respaldado por
  `catalogo_estados`) con: código, etiqueta, tono, icono, `requiere_custodio`,
  `es_terminal`, transiciones permitidas; ídem `RazonEstado` y `EstadoUso`.
- `Equipo`, `DashboardService`, filtros, validaciones, importador y exportador consumen esas
  clases; se elimina la duplicación de mapas.
- `razon_estado` se valida contra el catálogo de 13 (sin texto libre salvo “otra” con
  auditoría).
- Consultas afectadas por el cambio (`where estado_operativo = 'disponible'` en
  `PrestamoController`, filtros del índice, dashboard) migran a scopes
  (`Equipo::enBodegaDisponible()` = `almacenado` + razón `DISPONIBLE`).

### D4. Matriz de movimientos objetivo (con estados CMDB reales)

Estados resultantes y razones según el catálogo corregido (D3). “Estado | Razón” se
escribe explícito porque el estado ya no basta para distinguir el matiz.

| Movimiento | Estado requerido | Estado \| Razón resultante | Custodia | Datos obligatorios |
|---|---|---|---|---|
| `inventario_fisico` (asignación) | `almacenado` | `en_operacion` \| `ASIGNACIÓN RESPONSABLE` (o `CUSTODIA`) | Nuevo custodio | Funcionario del maestro, ficha CMDB, autorización si 2.º computador |
| `traslado` | `en_operacion` | `en_operacion` \| razón igual | Se mantiene | Ubicación/piso destino |
| `cambio_responsable` | `en_operacion` | `en_operacion` \| razón igual | Nuevo custodio | Funcionario nuevo, motivo |
| `prestamo` | `almacenado` | `en_operacion` \| `PRÉSTAMO` + `estado_uso = TEMPORAL / PRÉSTAMO` | Prestatario (temporal) | Fechas inicio/fin, motivo, funcionario/tercero |
| Devolución de préstamo *(cierre + constancia, no es acta — D6)* | `en_operacion` \| `PRÉSTAMO` | `almacenado` \| `DISPONIBLE` o taller (`almacenado` \| `ALISTAMIENTO / SOPORTE`) | Se libera | Estado físico, observaciones |
| `reparacion` (entrada) | `almacenado`, `en_operacion` | `transito` \| `DEVUELTO A PROVEEDOR` o `almacenado` \| `ALISTAMIENTO / SOPORTE` | Se libera o pasa a TIC | Motivo, proveedor/taller, fechas |
| `reparacion` (salida) | `transito` \| `DEVUELTO A PROVEEDOR` o `almacenado` \| `ALISTAMIENTO / SOPORTE` | `en_operacion` \| `ASIGNACIÓN RESPONSABLE` o `almacenado` \| `DISPONIBLE` | Último custodio o nuevo | Diagnóstico/solución, destino |
| `retiro_definitivo` | `en_operacion`, `almacenado`, `transito` | `baja` \| `PROCESAR BAJA` / `BAJA` / `DONACIÓN` / `DEVUELTO A PROVEEDOR` / `HURTO / PERDIDA` | Se libera | Destino (razón), motivo, soporte si es hurto, acta previa si aplica |
| Ajuste de inventario *(acta `inventario_fisico` — D6)* | Cualquiera | Cualquiera | Según destino | Motivo obligatorio, autorización, solo roles admin |

> “Activo disponible para prestar/asignar” = `almacenado` + razón `DISPONIBLE`
> (antes estado `disponible`). El campo `destino` de las actas de retiro pasa a almacenar
> la **razón** resultante. `traslado` y `cambio_responsable` no cambian el estado, pero
> **sí** cierran y abren custodia (cambio de responsable) o actualizan ubicación
> (traslado). La salida de reparación se modela como **movimiento explícito**
> (`sentido=salida`).

### D5. Importación como “línea base” + regularización

- La importación **no inventa movimientos**: aplica el estado y la custodia que el archivo
  declara.
- Para activos asignados (`en_operacion`) se emite el acta inicial por funcionario (como
  hoy), con snapshot exacto y `modalidad = asignado`.
- Para bodega (`almacenado` con tenedor TIC): se crea custodia física
  (`modalidad = custodia_fisica`), **sin acta** — el activo queda disponible para asignar.
- Para `baja`, `hurto`, `pendiente_estado`, `transito`: sin custodia; `HistorialAdministrativo`
  “Carga inicial CMDB” con el tenedor del archivo como referencia histórica.
- Cada corrida crea un `ImportBatch` con diff por fila, y permite **rollback** del lote.

### D6. Tipos de acta = formato institucional FE-BS-F-0069 (confirmado)

Fuente: `docs/Formatos/Novedad activo.xlsx` (hoja “Hoja3”, lista del formulario).
**Los tipos de acta son exactamente los 6 del formato institucional:**

| # | Nombre en el formato | Código interno (ya existente) |
|---|---|---|
| 1 | Inventario Físico | `inventario_fisico` |
| 2 | Traslado | `traslado` |
| 3 | Cambio de responsable | `cambio_responsable` |
| 4 | Retiro definitivo | `retiro_definitivo` |
| 5 | Préstamo | `prestamo` |
| 6 | Reparación | `reparacion` |

**Ramificaciones resueltas con los 6 tipos (sin inventar tipos nuevos):**

| Caso | Cómo se registra |
|---|---|
| Hurto / pérdida | Acta `retiro_definitivo` + destino (razón) `HURTO / PERDIDA` + soporte (denuncia) obligatorio |
| Ajuste / corrección de inventario | Acta `inventario_fisico` (es el tipo institucional para regularizar el inventario) |
| Devolución de préstamo | Cierre del `Prestamo` + movimiento de custodia vía `CustodiaService` + **constancia de devolución** (documento propio del módulo, no un acta nueva); el activo pasa a `almacenado` \| `RETORNO` (o taller si salió dañado, vía acta `reparacion`) |
| Reparación entrada/salida | Mismo tipo `reparacion` con `sentido = entrada|salida` (campo interno) |

**Estructura del ítem del acta (según el formato):** Activo fijo · SNº · Denominación del
activo fijo · Marca · Modelo · Serie · Placa de inventario · Observaciones. Los ítems
pueden incluir **periféricos y complementos** (MONITOR, TECLADO, MOUSE, CABLES), como
muestra el ejemplo diligenciado. Las firmas llevan Dependencia · Nombre y firma ·
**Cod. Personal** · Cargo (campo `codigo_personal` nuevo en el snapshot de custodia).
El formulario también define la lista de empresas: Federacafé, Fondo Nacional, Almacafé,
Procafecol, Otro.

---

## 3. Arquitectura objetivo

```
ActaController ──> ActaService ──> CustodiaService ──> Equipo / UsuarioAsignado
      │                │                    │
      │                ├─> Asignacion (ítem histórico)
      │                ├─> HistorialAdministrativo
      │                └─> ActaDocumentService (PDF/DOCX/XLSX/ZIP)
      │
PrestamoController ──> ActaService (préstamo) / CustodiaService (devolución + constancia)
EquipoController ──> solo ficha CMDB (sin estado/custodia)
CmdbImportService ──> ActaService / CustodiaService (por fila o grupo)
```

**Servicios nuevos**
- `App\Domain\Activos\EstadoOperativo` (catálogo + reglas).
- `App\Services\CustodiaService` (abrir/cerrar custodia, snapshot, auditoría).
- `App\Services\Cmdb\CmdbImportService` (parseo, validación, preview, commit, rollback).
- `App\Services\Cmdb\CmdbColumnMap` (contrato de columnas versionado).
- `App\Services\Cmdb\CmdbStateResolver` (estado/razón desde catálogo, sin heurísticas).
- `App\Services\Cmdb\CmdbFuncionarioResolver` (maestro, dedupe por hash, nombres).

**Tablas/columnas nuevas**
- `import_batches` (id, archivo, hash, hoja, usuario, estado, métricas json, iniciado/finalizado).
- `import_batch_rows` (batch_id, fila, identificador, acción, errores json, diff json).
- `asignaciones`: `estado_anterior`, `estado_resultante`, `vigente`, `fecha_fin`, `origen`,
  `modalidad` (`asignado|custodia_fisica`), `codigo_personal`, `acta_id` (ya existe),
  `funcionario_id` (nullable).
- `usuario_asignados`: `funcionario_id`, `acta_id`, `desde`, `hasta` (nullable),
  `modalidad` (`asignado|custodia_fisica`), `codigo_personal`.
- `funcionarios`: `email`, `div`, `jefe_cedula`, `jefe_nombre`, `jefe_email`, `genero`,
  `ceco`, `fecha_vencimiento`, `proceso`, `tipo_recurso` (fuente: hoja “Listado
  funcionarios” — contrato §9); único en `identificacion_hash`.
- `actas`: `sentido` (`entrada|salida` para reparación), `proveedor`, `soporte_path`,
  `acta_previa_id` (ya existe, usarlo), `import_batch_id` (nullable).
- `equipos`: `ubicacion` (col 13), `programa` (col 14/20: PAC/RECURSOS PROPIOS/PROYECTOS),
  `flags_auditoria` (json, cols 42-51), `origen_importacion`, `import_batch_id`.
  (`activo_fijo` existe pero el CMDB real no trae esa columna; queda vacía.)

> Mantener compatibilidad: no renombrar tablas ni columnas existentes en esta fase; solo
> agregar y backfillear.

---

## 4. Plan de implementación por fases

### Fase 0 — Contrato del CMDB y línea base ✅ (análisis ejecutado el 23-10-2026)

**Objetivo:** congelar el mapa real de columnas y el comportamiento esperado antes de
tocar código.

1. ✅ Archivo CMDB real recibido: `docs/Formatos/CMDB USUARIO FINAL.xlsx`
   (SHA-256 `3a48972b…36a08de`; 13 hojas; 303 activos + 4243 funcionarios).
2. ✅ Encabezados, catálogos y anomalías documentados en
   **`docs/cmdb_contrato_columnas.md`** (51 columnas mapeadas, catálogo real de
   6 estados + 13 razones + 6 usos, matriz estado×razón, regla de custodia,
   clave de conciliación = placa, 9 anomalías concretas A1–A9).
3. ⏳ `config/cmdb.php` (mapa versionado) → se crea en Fase 3 con el importador.
4. ⏳ Fixture sintético `tests/Fixtures/cmdb_estados.xlsx` con todos los estados y
   casos límite (cédulas científicas/texto, placa duplicada A1, garantías textuales,
   N/A, fila vacía) → Fase 3.
5. ⏳ Backup de BD + tag `restore-point-flujo-novedades` → antes del primer lote con
   migraciones.

**Pendiente de Fase 0:** items 3–5. Los items 1–2 ya congelan el contrato funcional.

---

### Fase 1 — Núcleo de estados y custodia (2 sesiones)

**Objetivo:** que exista una única verdad de estado/custodia y que las actas actuales
funcionen de punta a punta.

1. **Catálogo único (corregido: 6 estados + 13 razones + 6 usos)**
   - `EstadoOperativo`, `RazonEstado`, `EstadoUso` con los valores oficiales del contrato.
   - Seed de `catalogo_estados` / `catalogo_razones` con los valores oficiales.
   - `Equipo::estado_label/estado_badge` y `DashboardService` consumen la clase.
   - `EquipoRequest` valida contra el catálogo (quitar `activo/asignado/mantenimiento`).
   - Migración de estados legacy según el mapa de D3 (`disponible`→`almacenado`+`DISPONIBLE`,
     `prestamo`→`en_operacion`+`PRÉSTAMO`, `reparacion`→`almacenado`+`ALISTAMIENTO/SOPORTE`,
     `asignacion_responsable`→`en_operacion`, `procesar_baja`→`baja`+`PROCESAR BAJA`).
   - Scopes nuevos: `Equipo::disponiblesEnBodega()`, `Equipo::enPrestamo()`,
     `Equipo::enTaller()`; reemplazan los `where estado_operativo = 'disponible'` actuales.
2. **Migraciones**
   - `asignaciones.estado_anterior`, `estado_resultante`, `vigente`, `fecha_fin`, `origen`,
     `funcionario_id`.
   - `usuario_asignados.funcionario_id`, `acta_id`, `desde`, `hasta`; índice único
     `equipo_id`.
   - `funcionarios.identificacion_hash` único.
   - `actas.sentido`, `proveedor`, `soporte_path`, `import_batch_id`.
   - `equipos.ubicacion`, `programa`, `flags_auditoria`, `origen_importacion`,
     `import_batch_id`.
3. **`CustodiaService`**
   - `abrir(Equipo, Funcionario|snapshot, Acta, desde)`:
     crea/actualiza `usuario_asignados`, actualiza `responsable_*`, cierra custodia previa,
     marca ítem `vigente`.
   - `cerrar(Equipo, Acta, motivo)`:
     cierra `usuario_asignados` (delete o `hasta`), limpia snapshot, cierra ítem vigente.
   - `sincronizarSnapshot(Equipo, Funcionario)`.
   - `custodioActual(Equipo)`.
4. **Refactor `ActaService::registrar`**
   - Crear ítem con `estado_anterior`, `estado_resultante`, `vigente`.
   - Invocar `CustodiaService` según el tipo (abrir/cerrar/mantener).
   - Escribir `HistorialAdministrativo` por activo (tipo de cambio, valores, acta).
   - Validar que todos los equipos pedidos existan y estén bloqueados.
   - Corregir `validarUnComputador` para mirar **custodia actual**.
   - Corregir `anular`: excluir ítems de la misma acta; revertir custodia; corregir
     `valor_anterior` del préstamo.
5. **`ActaController`**
   - `FormRequest` dedicado (`StoreActaRequest`) con reglas condicionales por tipo.
   - `usuario_cedula` obligatorio cuando el tipo exige custodio.
   - `destino` (razón resultante del retiro) válido según la matriz:
     `procesar_baja`, `baja`, `donacion`, `devuelto_a_proveedor`.
   - Validar autorización solo cuando aplica.
6. **Tests**
   - Unit: matriz de estados/transiciones, `CustodiaService`, regla del computador.
   - Feature: acta de asignación deja `usuario_asignados` + snapshot + historial; anulación
     multi-ítem; segundo computador bloqueado/permitido con autorización.
   - Ajustar `CmdbImportRelationshipTest` al nuevo contrato.

**Entregable:** acta de asignación/retiro/préstamo/reparación actualiza custodia y UI;
anulación multi-ítem funciona.

---

### Fase 2 — Flujo de actas completo (2–3 sesiones)

**Objetivo:** todos los movimientos y ramificaciones, sin escrituras paralelas.

1. **Tipos de acta (según D6, formato institucional)**
   - Mantener exactamente los 6 tipos del formato FE-BS-F-0069 (ya coinciden con el
     sistema); **sin tipos nuevos**.
   - `reparacion` con `sentido=entrada|salida` y `proveedor`/`soporte`.
   - Hurto vía `retiro_definitivo` + destino `HURTO / PERDIDA` + soporte obligatorio.
   - Ajustes de inventario vía `inventario_fisico`.
   - Devolución de préstamo: cierre del `Prestamo` + constancia de devolución (documento
     del módulo) + cambio de custodia vía `CustodiaService`.
   - Modalidad en actas: toda acta que abre custodia declara
     `modalidad = asignado|custodia_fisica` (p. ej. retorno a bodega = `custodia_fisica`).
2. **Reglas de negocio**
   - Préstamo: no permitir si el activo no está disponible (`almacenado` + `RETORNO`/
     `DISPONIBLE`); no permitir si el funcionario ya tiene computador (salvo reparación
     del actual); vencimiento diario; máx. 15 días calendario (descripción institucional).
   - Devolución: cierra préstamo, libera custodia; si el estado físico es malo, ofrece
     crear acta `reparacion` en la misma transacción.
   - Reparación: entrada libera la custodia funcional (o la marca `custodia_fisica` si
     queda en bodega); salida restaura al último custodio o permite elegir uno nuevo.
   - Retiro: cierra préstamo/reparación abiertos; destino (razón) obligatorio; si es
     `baja`/`procesar_baja`/`donacion`/`devuelto_a_proveedor`, no permite custodio.
   - Hurto: exige soporte (denuncia) y no permite custodio.
   - Segundo computador: cuenta solo custodia con `modalidad = asignado`.
3. **Controladores**
   - `ActaController` crea/valida todos los tipos.
   - `PrestamoController` delega emisión y devolución en `ActaService`.
   - `EquipoController` deja de escribir estado/custodia; create/update solo ficha.
   - `AsignacionController` legacy: redirigir a acta o marcar solo lectura.
4. **UI**
   - Deep links `actas.create?tipo=...&equipo=...&return_to=...` desde equipos/index y show.
   - Wizard con secciones condicionales por tipo (fechas de préstamo, destino de retiro,
     proveedor de reparación, autorización).
   - Resumen final con validaciones y firma.
   - Ficha del activo: custodio actual, estado, historial de actas, documentos, acciones.
   - Quitar modales legacy de asignación/retiro/reparación del índice.
5. **Tests**
   - Feature por cada movimiento y por cada rama (segundo computador, autorización,
     devolución dañada, retiro de prestado, reparación con/sin custodio).
   - E2E: ciclo completo desde la UI.
   - Regresión: actas existentes siguen visibles y anulables.

**Entregable:** flujo operativo completo; ninguna vista/controlador escribe estado por
fuera del servicio.

---

### Fase 3 — Importador CMDB único y exacto (3–4 sesiones)

**Objetivo:** una sola implementación (web + comando) que importe el CMDB real al 100 %.

1. **`CmdbImportService`** con etapas:
   - `analizar($archivo)`: detecta hojas, encabezados, columnas reconocidas/ignoradas,
     tipos, estados, funcionarios; devuelve preview.
   - `validar($archivo)`: errores por fila (identificador, tipo, estado, cédula, fechas,
     duplicados) sin escribir.
   - `importar($archivo, $opciones)`: commit por chunks/transacción, upsert, custodia,
     actas, auditoría, batch.
   - `revertir($batchId)`: rollback lógico (soft delete de equipos/actas creados y
     restauración de snapshots).
2. **Contrato de columnas** (`config/cmdb.php`):
   - Mapa índice/encabezado → campo interno → transformación → obligatorio/default.
   - Alias de tipos de recurso y estados (sin `firstOrCreate` ciego).
   - Reglas de “quién es el custodio” y “quién recibe”.
3. **Reglas de importación**
   - Clave natural: **`placa` normalizada** (científica → entero); si es placeholder
     (`PENDIENTE`/`N/A`) o falta, `serial`; si faltan ambas → fila inválida. No
     sobrescribir con vacío; preservar campos no presentes.
   - Estado y razón exactos desde catálogo; valor desconocido → `pendiente_estado` + error
     de fila.
   - Custodia según el contrato: `en_operacion` → `modalidad = asignado`;
     `almacenado` con tenedor TIC → `modalidad = custodia_fisica` (disponible para
     asignar); `baja`/`hurto`/`pendiente_estado`/`transito` → sin custodia (tenedor a
     historial).
   - Ficha CMDB completa (todos los campos del contrato).
   - Préstamo: crear `Prestamo` + acta si hay fechas; si faltan, marcar regularización
     (no aplica al archivo actual: 0 préstamos).
   - Baja/hurto: sin custodia; acta histórica opcional según decisión §9.2-2.
   - Periféricos/complementos: el formato del acta los admite como ítems; en el CMDB no
     hay filas de periféricos (catálogo MONITOR/ESCANER/IMPRESORAS los acepta si aparecen).
   - Funcionarios: maestro completo, `nombres`/`apellidos` separados, dedupe por hash,
     actualización solo de campos presentes; los 16 custodios ausentes se crean desde el
     bloque 1-14 (contrato A2).
   - Tipos: catálogo fijo de 9; valores fuera del catálogo → error de fila, nunca
     `firstOrCreate` ciego.
4. **Pestaña “Corrección” en el módulo Importar** *(requisito del usuario)*
   - Objetivo declarado: **uniformidad total — ningún valor vacío** en los campos
     obligatorios del contrato.
   - Al importar (y en cada re-importación), la pestaña lista **todos los faltantes de
     corrección** del archivo fuente, fila a fila y columna a columna:
     * celdas vacías en columnas obligatorias,
     * placeholders (`PENDIENTE`, `N/A`, `SIN REGISTRO`) en columnas obligatorias,
     * valores fuera de catálogo (estado, razón, tipo, empresa, dependencia, fuente,
       estado de uso, administrado/controlado),
     * inconsistencias (cédula textual, placa/serial científicos no normalizables,
       duplicados por clave, custodio ausente del maestro, garantía vencida vs estado,
       `TIEMPO USO` incoherente con fecha de compra),
     * campos condicionales vacíos sin justificación (p. ej. “quien recibe” en despachos).
   - Cada hallazgo con: fila, placa/serial, columna CMDB, campo interno, valor actual,
     severidad (`alta|media|baja`) y sugerencia de corrección.
   - Filtros por columna/severidad/estado y **exportación XLSX/CSV** para que el analista
     corrija el fuente y vuelva a importar.
   - Semáforo de uniformidad: % de celdas completas por columna (baseline real en contrato
     §12: `SHORTNAME` 196/303 incompletos, `NOMBRE DE EQUIPO` 81, `TIPO DE PROPIEDAD` 147,
     `FIN GARANTIA` 203, `ORDEN DE REMISIÓN` 303…).
   - **La importación es NO condicionada** (decisión del usuario, 24-10-2026): nunca se
     bloquea por datos incompletos; TODOS los faltantes quedan como pendientes en la
     pestaña Correcciones, corregibles **directamente desde la vista** (o justificables
     como N/A con causa), hasta completar el 100 % de la información real.
   - **Implementado** el 24-10-2026 (adelantado a Lote A a petición del usuario):
     tabla `correcciones` + `CorreccionService` + `CorreccionController` +
     `resources/views/correcciones/index.blade.php` (con contador en la pestaña Importar,
     botón “Regenerar pendientes”, corrección por modal con catálogos oficiales y fechas,
     justificación de N/A, y re-escaneo en cascada tras cada corrección). Ver
     `tests/Feature/CorreccionesTest.php`.
5. **Reporte**
   - Métricas: filas leídas/creadas/actualizadas/omitidas/erróneas, por estado, por tipo,
     funcionarios, actas, custodias físicas, bajas, duplicados, columnas ignoradas.
   - Descarga de errores en XLSX/CSV.
   - Diff antes/después y conciliación por estado y razón.
6. **Comando y web**
   - `php artisan cmdb:importar {archivo} --dry-run --sheet= --batch=`.
   - Misma lógica en `EquipoController@importar` con preview + pestaña Corrección +
     confirmación.
7. **Tests**
   - Unit: mapper, state resolver, claves naturales, idempotencia, generador del reporte
     de corrección (vacíos/placeholders/fuera de catálogo).
   - Feature: fixture con todos los estados → conteos exactos; segunda corrida 0 cambios;
     rollback; préstamo con fechas; baja sin custodio; periférico; cédula textual;
     pestaña Corrección lista los 9 hallazgos A1–A9 y el inventario de vacíos.
   - E2E: subir archivo, preview, pestaña Corrección (filtrar y exportar), confirmar, ver
     reporte y ficha.

**Entregable:** importación exacta y repetible; comando y web producen el mismo resultado;
pestaña Corrección con el inventario completo de faltantes para uniformar el fuente.

---

### Fase 4 — Funcionarios, catálogos y datos maestros (1–2 sesiones)

1. Importar hoja “Listado funcionarios” completa (correo, jefe, ceco, vencimiento, proceso).
2. Sincronizar `UsuarioAsignado` ↔ `Funcionario` ↔ `Asignacion` por cédula/hash.
3. Catálogos: estados, razones, empresas, fuentes, tipos, marcas; usarlos en formularios y
   validaciones.
4. Reglas de altas/bajas de funcionarios (activo/inactivo, no borrar con activos).
5. Reportes de funcionarios con activos y de activos sin custodio.

**Entregable:** maestro único y consistente; sin duplicados por cédula.

---

### Fase 5 — Documentos, auditoría, reportes y exportación (2 sesiones)

1. Documentos por tipo de acta (asignación, retiro, préstamo, devolución, reparación,
   hurto, ajuste) con firmas y soportes.
2. Historial de vida del activo: línea de tiempo con acta, tipo, estado anterior→nuevo,
   custodio, usuario, fecha, documentos.
3. Exportación CMDB de 51 columnas desde la BD (equipo + última acta + custodia actual).
4. Reportes: por estado, por funcionario, por dependencia, sin custodio, diferencias
   CMDB vs app, préstamos vencidos, reparaciones abiertas.
5. Dashboard: KPIs desde el catálogo único; tareas derivadas de datos faltantes.

**Entregable:** trazabilidad y conciliación completas.

---

### Fase 6 — Migración de datos y cutover (1–2 sesiones)

1. **Backfill**
   - Migrar estados al catálogo real según el mapa de D3 (`activo/asignado` →
     `en_operacion`; `mantenimiento` → `reparacion`; `disponible` → `almacenado` +
     razón `DISPONIBLE`; `prestamo` → `en_operacion` + razón `PRÉSTAMO`;
     `asignacion_responsable` → `en_operacion`; `procesar_baja` → `baja` + razón
     `PROCESAR BAJA`).
   - Deducir `modalidad` (`asignado`/`custodia_fisica`) según estado y razón.
   - Deduplicar funcionarios por hash; separar nombres/apellidos.
   - Crear custodias actuales desde el último ítem/`UsuarioAsignado` para activos en
     estados con custodio.
   - Cerrar custodias huérfanas (activos sin estado de custodia con `usuario_asignados`).
   - Marcar ítems `vigente` y enlazar ítems legacy sin acta.
   - Corregir tipos basura y seriales `SIN_SERIAL_` duplicados (con decisión del usuario).
2. **Importación real en dry-run** contra el CMDB; conciliar conteos por estado/tipo/
   funcionario; corregir mapa hasta 0 diferencias.
3. **Cutover**
   - Backup + tag.
   - Importación real por lotes con reporte.
   - Verificación post-importación (conteos, muestras, documentos).
   - Congelar el flujo legacy (redirección o solo lectura).

**Entregable:** BD reconciliada con el CMDB; flujo nuevo activo.

---

### Fase 7 — Endurecimiento y documentación (1 sesión)

1. Playwright completo (todos los proyectos), PHPUnit, PHPStan, barrido HTTP.
2. Pruebas de concurrencia (dos actas sobre el mismo activo), transacciones y rollback.
3. Permisos: `equipos.ver/crear/editar/importar/exportar`, `actas.anular` (nuevo permiso
   específico en vez de `equipos.ver`).
4. Documentar runbook de importación, estados, resolución de errores y rollback.
5. Actualizar `docs/contexto.md`, `session/registro_sesion_ui.md` y la skill si aplica.

---

## 5. Contrato de importación CMDB (congelado con el archivo real)

> **Fuente:** `docs/Formatos/CMDB USUARIO FINAL.xlsx` (SHA-256 `3a48972b…36a08de`).
> **Documento normativo:** **`docs/cmdb_contrato_columnas.md`** (Fase 0, 23-10-2026).
> Esta sección resume; ante cualquier divergencia manda el contrato.

### 5.1 Lo que cambió respecto al borrador

| Tema | Borrador | Real (archivo) |
|---|---|---|
| Identidad | `activo_fijo` > `placa` > `serial` | **`placa` > `serial`** (no existe columna ACTIVO FIJO; placa universal) |
| Custodio | Ambiguo entre bloques | **Bloque 1-14**; bloque 15-20 es N/A en 301/303 filas |
| Col 14/20 “TIPO DE RECURSO” | Tipo de equipo | **Programa de financiación** (PAC/RECURSOS PROPIOS/PROYECTOS); el tipo del equipo es col 21 |
| Estados | 11 (incluía disponible/prestamo/reparacion) | **6 estados + 13 razones** (ver D3) |
| Préstamos en archivo | Asumidos | **0 filas** (razón PRÉSTAMO no aparece); la lógica queda para el flujo vivo |
| Quien recibe (45-46) | Se usaría en despachos | Vacío (0 usos); se guarda igual |
| Bajas | Hoja separada | Vienen en Microinformatica (16 filas); hoja “Baja” es plantilla vacía |
| Periféricos | Se omitían | No hay filas de periféricos; el catálogo sí define MONITOR/ESCANER/IMPRESORAS (los acepta si aparecen) |
| Volumen | Desconocido | 303 activos (302 netos), 227 EN OPERACIÓN, 59 ALMACENADO, 16 BAJA, 1 PENDIENTE |

### 5.2 Reglas de importación por estado (congeladas)

| Estado CMDB (filas reales) | ¿Custodio? | Acción de importación |
|---|---|---|
| EN OPERACIÓN (227) | Sí | Acta inicial `inventario_fisico` por custodio + custodia funcional + snapshot |
| ALMACENADO (59) | No | Sin acta; tenedor físico (bloque 1-14) va a historial + `ubicacion`; razón RETORNO/BAJA/ALISTAMIENTO/PROCESAR BAJA |
| BAJA (16) | No | Acta histórica de retiro si se aprueba la decisión §9-7; si no, solo estado + tarea |
| PENDIENTE ESTADO (1) | No | Estado exacto + tarea de regularización |
| TRÁNSITO / HURTO (0 hoy) | No | Reglas definidas en el contrato; entran igual si aparecen |

### 5.3 Idempotencia y reconciliación

- Clave: **`placa` normalizada** (notación científica → entero); si falta, `serial`.
  Fila sin ambas → error (no ocurre: 0 sin placa).
- Upsert sin sobrescribir con vacíos; hash de fila para detectar cambios reales.
- Segunda corrida: 0 altas, 0 actas nuevas, 0 cambios de estado.
- Reporte de conciliación: por cada estado y tipo, `fuente vs BD` (volumen esperado en
  contrato §11); debe dar 0 diferencias.
- Rollback por `import_batch_id`.
- Anomalías A1–A9 del contrato se reportan fila a fila (placa compartida, custodios
  ausentes del maestro, cédulas duplicadas, cédula `PENDIENTE`, serial faltante).

---

## 6. Cambios por archivo (mapa de trabajo)

| Área | Archivos principales |
|---|---|
| Catálogo/estados | `app/Domain/Activos/EstadoOperativo.php` (nuevo), `app/Models/Equipo.php`, `app/Services/Dashboard/DashboardService.php`, `app/Http/Requests/EquipoRequest.php`, `resources/views/equipos/index.blade.php` |
| Custodia | `app/Services/CustodiaService.php` (nuevo), `app/Services/ActaService.php`, `app/Models/Asignacion.php`, `app/Models/UsuarioAsignado.php`, migraciones |
| Actas | `app/Http/Controllers/ActaController.php`, `app/Http/Requests/StoreActaRequest.php` (nuevo), `resources/views/actas/create.blade.php`, `resources/views/actas/show.blade.php`, `routes/web.php` |
| Préstamos | `app/Http/Controllers/PrestamoController.php`, `app/Services/ActaService.php`, `resources/views/prestamos/*` |
| Equipos | `app/Http/Controllers/EquipoController.php`, `resources/views/equipos/{create,edit,show}.blade.php`, `resources/views/equipos/index.blade.php` |
| Importación | `app/Services/Cmdb/*` (nuevos), `app/Imports/EquiposImport.php` (reemplazo), `app/Imports/EquiposImportSelector.php`, `app/Console/Commands/ImportarCmdb.php`, `config/cmdb.php`, `resources/views/equipos/importar.blade.php` |
| Funcionarios | `app/Models/Funcionario.php`, `app/Console/Commands/SincronizarFuncionarios.php`, migración, `resources/views/funcionarios/*` |
| Documentos | `app/Services/ActaDocumentService.php`, `resources/views/pdf/*` |
| Tests | `tests/Unit/*`, `tests/Feature/*`, `tests/e2e/*`, `tests/Fixtures/*` |

---

## 7. Estrategia de pruebas y aceptación

### 7.1 Unitarias
- Matriz completa de estados × movimientos (transiciones válidas e inválidas).
- `CustodiaService`: abrir/cerrar/mantener; snapshot; idempotencia.
- `CmdbColumnMap`: alias, normalización, campos obligatorios, duplicados.
- `CmdbStateResolver`: los 6 estados + 13 razones oficiales + desconocidos.
- Regla del computador sobre custodia actual (no historial).
- Anulación: acta multi-ítem, movimientos posteriores, préstamo asociado.

### 7.2 Feature
- Asignar → ficha muestra custodio; retirar → ficha sin custodio; cambiar responsable →
  custodio nuevo; traslado → ubicación nueva y mismo custodio.
- Préstamo → estado `prestamo`, préstamo activo, devolución → `disponible`.
- Reparación entrada/salida con y sin custodio previo.
- Baja/procesar baja/hurto → sin custodio, documento.
- Segundo computador: bloqueado sin autorización, permitido con PDF; autorización
  consumida.
- Anulación revierte estado y custodia; acta multi-ítem; rechazo si hay movimientos
  posteriores.
- Importación: fixture con todos los estados; conteos exactos; segunda corrida 0 cambios;
  rollback; errores por fila; periféricos; funcionarios.
- RBAC: permisos por acción; `actas.anular` específico.

### 7.3 E2E (Playwright)
1. Importar CMDB de prueba → preview → confirmar → reporte.
2. Abrir ficha de un activo importado: estado/custodio/ficha exactos.
3. Asignar un activo disponible a un funcionario → ver custodia y acta.
4. Intentar segundo computador sin autorización → error; con PDF → éxito.
5. Trasladar y cambiar responsable.
6. Prestar → devolver; prestar → retirar.
7. Reparación entrada → salida.
8. Retiro a disponible y a baja.
9. Anular acta multi-ítem → estados y custodias revertidos.
10. Exportar CMDB y conciliar.

### 7.4 Criterios de aceptación medibles
- [ ] 100 % de filas válidas del CMDB importadas; 0 errores no explicados.
- [ ] Conteo por estado y razón = fuente (6 estados / 13 razones del contrato §11) y 0
      activos con estado o razón inválida.
- [ ] 227 activos con custodia `asignado`, 59 con `custodia_fisica` (bodega TIC), 17 sin
      custodia (16 baja + 1 pendiente) — según el archivo actual.
- [ ] 0 activos en estado con custodio sin `usuario_asignados`.
- [ ] 0 activos en estado sin custodio con `usuario_asignados`.
- [ ] 0 funcionarios duplicados por cédula.
- [ ] Segunda importación = 0 altas/actas/cambios de estado.
- [ ] Todos los movimientos generan acta + ítem + historial + documento.
- [ ] **Pestaña Corrección:** inventario completo de faltantes (vacíos, placeholders,
      fuera de catálogo) con exportación; importación bloqueada mientras existan
      hallazgos `bloqueante` sin resolver.
- [ ] Los tipos de acta coinciden 1:1 con el formato FE-BS-F-0069 (6 tipos).
- [ ] Suite PHPUnit/PHPStan/Playwright en verde.
- [ ] Exportación CMDB reconcilia 100 % de columnas mapeadas.

---

## 8. Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Romper el flujo actual de actas | Fase 1 mantiene compatibilidad de tablas; tests de regresión antes de tocar UI |
| Datos históricos inconsistentes | Backfill con scripts idempotentes + backup + reporte de diferencias |
| Importación destructiva | `--dry-run`, preview, batch, rollback, nunca borrar activos con historial |
| Duplicados por cédula/serial | Claves naturales + únicos + reporte de conflictos |
| Estados no representados | Catálogo completo + `pendiente_estado` + tareas |
| Regla del computador mal medida | Usar custodia actual (`usuario_asignados`) y no historial |
| Concurrencia (dos actas mismo activo) | `lockForUpdate` + validación de estado + índice único de custodia |
| Cambio de `APP_KEY` rompe hashes | Documentar; no rotar sin rehash; script de migración |
| Documentos con datos incompletos | Validar campos mínimos por tipo antes de emitir |
| Permisos insuficientes | Permiso específico `actas.anular` y pruebas RBAC |

---

## 9. Decisiones (actualizadas tras la Fase 0)

### 9.1 Resueltas ✅

| # | Decisión | Resolución |
|---|---|---|
| R1 | Archivo CMDB | ✅ Recibido y congelado (`docs/cmdb_contrato_columnas.md`) |
| R2 | Custodio vs “quien recibe” | ✅ Custodio = bloque 1-14 (bloque 15-20 N/A en 301/303); cols 45-46 vacías |
| R3 | Clave de conciliación | ✅ `placa` normalizada > `serial` (no existe ACTIVO FIJO en el archivo) |
| R4 | Funcionarios ausentes del maestro (16 custodios) | ✅ Se crean desde el bloque 1-14 de Microinformatica (contrato A2) |
| R5 | Tipos de recurso | ✅ Catálogo fijo de 9 tipos; valores fuera del catálogo → error de fila, nunca `firstOrCreate` ciego |
| R6 | Flujo legacy | ✅ `AsignacionController`/`AsignacionService` pasan a **solo lectura** y salen del menú |
| R7 | Préstamos en el archivo | ✅ No hay filas de préstamo en el CMDB actual; el flujo vivo de préstamos usa fechas del acta |
| R8 | Custodia vs asignado | ✅ Modalidad en la custodia: `asignado` vs `custodia_fisica`; el ANALISTA TIC puede tener muchos activos en custodia y solo algunos asignados; el resto queda **disponible para asignar** (D1) |
| R9 | Catálogo de estados | ✅ “Lo que digan los formatos”: 6 estados + 13 razones + 6 usos del CMDB real (D3) |
| R10 | Tipos de acta | ✅ Exactamente los 6 del formato FE-BS-F-0069 (`Novedad activo.xlsx`); hurto vía retiro + razón, ajustes vía inventario físico, devolución con constancia (D6) |
| R11 | Pestaña “Corrección” | ✅ Nueva en el módulo Importar: inventario de faltantes para uniformidad total — ningún valor vacío en campos obligatorios (F3-4) |
| R12 | Importación no condicionada | ✅ El archivo importa aunque los datos estén incompletos; TODOS los faltantes quedan en la pestaña Correcciones y se corrigen directamente desde la vista (o se justifican). Implementado el 24-10-2026 |

### 9.2 Abiertas (bloquean o matizan el siguiente lote)

1. **Conflicto A1 (placa 10062150 compartida por 2 funcionarias):** ¿importamos 1 activo
   con el custodio de la primera fila y reportamos el conflicto (recomendado), o bloqueamos
   ambas filas hasta resolución manual?
2. **Bajas (16 filas):** ¿creamos acta histórica de retiro durante la importación
   (recomendado: trazabilidad completa) o solo estado + tarea para que el analista la
   registre?
3. **Flags de auditoría (cols 42-51):** ¿columna JSON única `flags_auditoria`
   (recomendado) o columnas separadas por bandera?
4. **`equipos.activo_fijo`:** el CMDB real no trae esa columna. ¿La dejamos vacía en la
   importación (recomendado) o copiamos la placa?
5. **Licencias:** las hojas de licencias del archivo son plantillas vacías. ¿Las dejamos
   fuera de alcance por ahora (recomendado) o importa la estructura?
6. **Regla de 15 días hábiles (bodega):** ¿el paso `RETORNO` → `DISPONIBLE` lo hace un
   comando programado diario (recomendado) o lo cambia manualmente el analista TIC?
7. **Campos obligatorios de la pestaña Corrección:** propuesta = identidad (placa/serial),
   tipo, estado, razón, custodia (bloque 1-14), estado de uso, administrado, marca/modelo,
   `NOMBRE DE EQUIPO`, `SHORTNAME`, `TIPO DE PROPIEDAD`, `CHEKLIST RESPONSABLE TI`.
   ¿Confirmas el conjunto o ajustamos?

---

## 10. Orden de ejecución recomendado

```
F0 Contrato CMDB + fixture + backup
  └─ F1 Estados + Custodia + refactor ActaService
       └─ F2 Flujo completo de actas + UI + retiro legacy
            └─ F3 Importador único (web + comando)
                 └─ F4 Funcionarios + catálogos
                      └─ F5 Documentos + reportes + export
                           └─ F6 Backfill + import real + cutover
                                └─ F7 Endurecimiento + documentación
```

**Por qué este orden:** sin una custodia única (F1) el importador seguiría escribiendo en
dos fuentes; sin el flujo de actas completo (F2) el importador no tendría a quién delegar
préstamos/bajas/reparaciones; sin el contrato del CMDB (F0) cualquier mapeo es una
suposición.

---

## 11. Primer lote concreto (siguiente commit)

**Lote A — “Custodia única y acta que sí asigna”** (Fase 1, recortada):

1. Migración: `asignaciones.estado_anterior/estado_resultante/vigente/fecha_fin/origen`;
   `usuario_asignados.funcionario_id/acta_id/desde/hasta`.
2. `CustodiaService` + refactor de `ActaService::registrar` para abrir/cerrar custodia y
   escribir `HistorialAdministrativo`.
3. Corregir `validarUnComputador` y `activosDe` para usar custodia actual.
4. Corregir `anular` (multi-ítem + custodia + valor anterior).
5. Tests: asignar deja custodio visible; retirar lo quita; anular multi-ítem; segundo
   computador.
6. Validación: PHPUnit, PHPStan, Playwright, smoke manual de ficha/listado.

Con eso, “asignar” y “retirar” ya quedan operativos de verdad por acta; después se conecta
el importador exacto.

> El Lote A no toca el catálogo de estados (eso va en el Lote B junto con el seed
> 6 estados + 13 razones y los scopes) para mantener el cambio acotado y reversible.

---

*Fin del plan. Cualquier cambio de alcance se registra en este archivo y se ejecuta por
lotes con commit + push.*
