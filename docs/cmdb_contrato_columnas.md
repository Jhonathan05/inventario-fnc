# Contrato CMDB — `CMDB USUARIO FINAL.xlsx` + `Novedad activo.xlsx` (Fase 0, congelado)

> **Fecha de análisis:** 23-10-2026 (actualizado 24-10-2026 con el formato de acta)
> **Fuentes:**
> - `docs/Formatos/CMDB USUARIO FINAL.xlsx` (1.6 MB)
>   SHA-256: `3a48972b0b37b9a05bca9328b6839e70813e50d220ff1029804be424c36a08de`
> - `docs/Formatos/Novedad activo.xlsx` (58 KB), formato institucional
>   **FE-BS-F-0069** “NOVEDAD DE ACTIVO” (v1, 11/04/2017)
> **Estado:** contrato congelado para la importación (Fase 0 del plan
> `session/plan_flujo_novedades_cmdb.md`). Cualquier cambio del archivo fuente
> debe re-validar este documento antes de importar.

---

## 1. Hojas del archivo (13)

| Hoja | Filas | Clasificación | Tratamiento |
|---|---|---|---|
| `Microinformatica` | 306 (2 encabezado + **303 datos**) | **Fuente principal de activos** | Importar |
| `Listado funcionarios` | 4244 (1 encabezado + **4243 datos**) | **Maestro de personas** | Importar |
| `Listas De Validación Equipos ` | 992 | **Catálogos oficiales** (columnas pares = lista) | Importar a catálogos |
| `Equipo en Estado Baja` | 24 | Plantilla con encabezados, **sin datos** | Ignorar (las bajas vienen en Microinformatica) |
| `Información Licencias` | 1000 | Plantilla con encabezados, **sin datos** | Ignorar (fase futura de licencias) |
| `Asignación Licencias` | 5 | Plantilla, **sin datos** | Ignorar (fase futura) |
| `Listas De Validación Licencias` | 999 | Catálogo de licencias | Ignorar (fase futura) |
| `Hoja 25`, `Hoja 22`, `Hoja 19`, `Hoja 20` | — | Hojas de trabajo | Ignorar |
| `Tabla dinámica 4`, `Tabla dinámica 2` | — | Pivots | Ignorar |

## 2. Reglas de lectura generales

1. **Microinformatica:** fila 1 = banner de bloques, fila 2 = encabezados, datos desde fila 3.
   Filas completamente vacías se descartan (hay 1).
2. **Listado funcionarios:** fila 1 = encabezados, datos desde fila 2.
3. **`N/A`** = valor vacío (aparece sobre todo en el bloque 15-20).
4. **Cédulas, placas y seriales numéricos llegan en notación científica** (`1.075310355E9`,
   `1.006215E7`, `2.4358524702446E13`). Regla: si `float(x)` es entero → `str(int(x))`;
   si no, conservar el texto tal cual. Prohibido truncar.
5. La cédula puede venir como **texto** (`PENDIENTE`) → se trata como **sin cédula**.
6. **Fechas** en serial Excel (sistema 1900): `46182.0` → fecha; `fin_garantia` mezcla
   seriales con textos `SIN REGISTRO` (125), `PENDIENTE` (59) y vacíos (19) → los textos
   se guardan como `NULL` y se anotan en observaciones.
7. Normalizar NBSP, espacios repetidos, mayúsculas en catálogos.
8. **No existe columna `ACTIVO FIJO`** en este formato: la identidad del activo es
   **`PLACA` (col 23) y `SERIAL` (col 22)**. La columna `equipos.activo_fijo` existente
   queda sin fuente (vacía) en la importación CMDB.

## 3. Mapa de columnas — `Microinformatica` (51 columnas → destino interno)

### Bloque A — Custodia (cols 1-14) → `asignaciones.usuario_*` + `funcionarios`

| Col | Encabezado | Destino | Observación |
|---|---|---|---|
| 1 | EMPRESA PROPIETARIO DEL EQUIPO | `asignaciones.usuario_empresa_propietaria` | Catálogo: 5 valores |
| 2 | DEPENDENCIA | `asignaciones.usuario_dependencia` | Catálogo: 39 valores |
| 3 | FUENTE DE RECURSO | `asignaciones.usuario_fuente_recurso` | FON1/FON2/FEPC/ALMACAFÉ |
| 4 | EMPRESA FUNCIONARIO | `asignaciones.usuario_empresa_funcionario` | FEDERACIÓN/TIVIT/SAMTEL/TECNOFILE |
| 5 | EMPLEADO O CONTRATISTA | `asignaciones.usuario_tipo_vinculacion` | EMPLEADO (301) / CONTRATISTA (2) |
| 6 | CEDULA DEL FUNCIONARIO/CONTRATISTA | `asignaciones.usuario_cedula` + `funcionarios.identificacion` (hash) | Científica o texto |
| 7 | SHORTNAME | `asignaciones.usuario_shortname` | Puede ser `PENDIENTE` |
| 8 | NOMBRES Y APELLIDOS | `asignaciones.usuario_nombre` + `funcionarios.nombres/apellidos` | Separar con el maestro |
| 9 | DEPARTAMENTO | `asignaciones.usuario_departamento` | Geografía (TOLIMA…) |
| 10 | CIUDAD | `asignaciones.usuario_ciudad` | |
| 11 | CARGO | `asignaciones.usuario_cargo` + `funcionarios.cargo` | |
| 12 | ÁREA | `asignaciones.usuario_area` + `funcionarios.area` | EXTENSION RURAL, TIC… |
| 13 | UBICACIÓN Y PISO | `asignaciones.usuario_piso` + `equipos.ubicacion` (nuevo) | Mezcla pisos (`2.0`) y sedes |
| 14 | TIPO DE RECURSO | **NO es tipo de equipo**: es programa de financiación (PAC/RECURSOS PROPIOS/PROYECTOS) | Nuevo campo `equipos.programa` |

### Bloque B — Responsable del activo (cols 15-20)

| Col | Encabezado | Destino | Observación |
|---|---|---|---|
| 15 | CEDULA | `asignaciones` snapshot alternativo (solo si ≠ N/A) | **N/A en 301/303 filas** |
| 16 | NOMBRES Y APELLIDOS | ídem | N/A en 301/303 |
| 17 | CARGO | ídem | N/A en 301/303 |
| 18 | CIUDAD | ídem | N/A en 301/303 |
| 19 | AREA | ídem | N/A en 301/303 |
| 20 | TIPO DE RECURSO | Igual que col 14 | **col20 == col14 en 303/303 filas** |

> **Regla congelada:** el **custodio del activo es el bloque 1-14**. El bloque 15-20 es un
> override casi siempre vacío; el único caso distinto (1 fila) se audita, no se usa como
> custodio. El snapshot `equipos.responsable_*` se proyecta desde el bloque 1-14.

### Bloque C — Activo (cols 21-41) → `equipos`

| Col | Encabezado | Destino | Observación |
|---|---|---|---|
| 21 | TIPO | `tipo_recursos.nombre` | Catálogo oficial de 9 tipos |
| 22 | SERIAL | `equipos.serial` | Normalizar científico; 1 fila sin serial |
| 23 | PLACA | `equipos.placa` | **Clave primaria de conciliación**; universal (0 vacíos) |
| 24 | MARCA | `equipos.marca` | |
| 25 | MODELO | `equipos.modelo` | |
| 26 | ESTADO DE USO | `equipos.estado_uso` | Catálogo de 6 valores |
| 27 | NOMBRE DE EQUIPO | `equipos.nombre_equipo` | `PENDIENTE` en 14 filas |
| 28 | ESTADO OPERATIVO | `equipos.estado_operativo` | Catálogo de **6 estados** (ver §4) |
| 29 | RAZÓN DEL ESTADO | `equipos.razon_estado` | Catálogo de **13 razones** (ver §4) |
| 30 | ADMINISTRADO/CONTROLADO | `equipos.administrado` | ADMINISTRADO/CONTROLADO (303 = CONTROLADO) |
| 31 | PROCESADOR | `equipos.procesador` | |
| 32 | MEMORIA RAM | `equipos.ram` | |
| 33 | TAMAÑO DISCO DURO | `equipos.disco` | |
| 34 | SISTEMA OPERATIVO | `equipos.sistema_operativo` | |
| 35 | FECHA DE COMPRA | `equipos.fecha_compra` | Serial Excel |
| 36 | FIN DE GARANTIA | `equipos.fin_garantia` | Serial o `SIN REGISTRO`/`PENDIENTE` → NULL |
| 37 | TIEMPO USO (AÑOS) | `equipos.tiempo_uso` | Decimal en años |
| 38 | TIPO DE PROPIEDAD | `equipos.tipo_propiedad` | PROPIO/LEASING/PROYECTO/PENDIENTE |
| 39 | CHEKLIST (RESPONSABLE TI) | `equipos.checklist_responsable` | Nombre del analista TI |
| 40 | ORDEN DE REMISIÓN | `equipos.orden_remision` | |
| 41 | OBSERVACIONES | `equipos.observaciones` | |

### Bloque D — Auditoría y despachos (cols 42-51)

| Col | Encabezado | Destino | Observación |
|---|---|---|---|
| 42 | Tipo Aprobado | `equipos.flags_auditoria` (nuevo, JSON) o campo personalizado | PERFILES 1/2/3 |
| 43 | FNC | `equipos.flags_auditoria` | `ok`/comentarios |
| 44 | DIRECCION | `actas.recibe_*` (despachos) | Vacía en el archivo actual |
| 45 | NOMBRE CONTACTO QUIEN RECIBE EQUIPOS | `actas.recibe_nombre` | Vacía (0 usos) |
| 46 | NUMERO DE CONTACTO QUIEN RECIBE EQUIPO | `actas.recibe_contacto` (nuevo si se requiere) | Vacía (0 usos) |
| 47 | NO CRUZA CON NOMINA | `equipos.flags_auditoria` | 28 filas |
| 48 | NO CRUZA CON SAP | `equipos.flags_auditoria` | 10 filas |
| 49 | NO CRUZA CON AV | `equipos.flags_auditoria` | 15 filas + fechas sueltas (46226…) |
| 50 | USO EQUIPOS > 15 AÑOS | `equipos.flags_auditoria` | 7 filas con comentario |
| 51 | OBSERVACIONES | concatenar a `equipos.observaciones` | 1 uso |

## 4. Catálogos oficiales (extraídos de `Listas De Validación Equipos`)

### Estados operativos (6) — `catalogo_estados`

| Valor CMDB | Código interno | ¿Custodia funcional? |
|---|---|---|
| ALMACENADO | `almacenado` | No |
| TRÁNSITO | `transito` | No |
| EN OPERACIÓN | `en_operacion` | **Sí** |
| BAJA | `baja` | No |
| HURTO | `hurto` | No |
| PENDIENTE ESTADO | `pendiente_estado` | No |

> ⚠️ **Corrección importante:** el CMDB real **no** tiene `DISPONIBLE`, `PRÉSTAMO` ni
> `REPARACIÓN` como estados. PRÉSTAMO es una **razón**; el taller se expresa con
> `ALMACENADO`/`TRÁNSITO` + razón. La decisión previa de “adoptar los 11 estados” se
> reformula a **6 estados + 13 razones** (ver plan §2-D3).

### Razones del estado (13) — `catalogo_razones`

`DISPONIBLE`, `CUSTODIA`, `ALISTAMIENTO / SOPORTE`, `RENOVACIÓN`, `PROCESAR BAJA`,
`DESPACHO`, `RETORNO`, `ASIGNACIÓN RESPONSABLE`, `PRÉSTAMO`, `HURTO / PERDIDA`,
`DEVUELTO A PROVEEDOR`, `DONACIÓN`, `BAJA`

### Tipos de equipo (9) — `tipo_recursos`

`EQUIPO ESCRITORIO`, `EQUIPO PORTATIL`, `EQUIPO TODO EN UNO`, `TABLETA`, `ESCANER`,
`MONITOR`, `IMPRESORA TERMICA`, `IMPRESORA MULTIFUNCIONAL`, `IMPRESORA MONOFUNCIONAL`

### Estados de uso (6) — `equipos.estado_uso`

`FUNCIONES PROPIAS`, `SALA DE SISTEMAS / LABORATORIO`, `PRUEBAS`, `MULTIUSUARIO`,
`TEMPORAL / PRÉSTAMO`, `OBSOLETO / ALMACENADO`

### Otros catálogos

- Empresa propietario (5): `ALMACAFÉ`, `FEDERACIÓN`, `BANCOLOMBIA`, `CAFECERT`, `FONDO NACIONAL`
- Fuente de recurso (4): `FON1`, `FON2`, `FEPC`, `ALMACAFÉ`
- Empresa funcionario (5): `ALMACAFÉ`, `FEDERACIÓN`, `TIVIT`, `SAMTEL`, `TECNOFILE`
- Dependencia (39): comités, sucursales, agencias, oficinas, planta (lista completa en hoja 10)
- Administrado/Controlado (2): `ADMINISTRADO`, `CONTROLADO`

## 5. Matriz ESTADO × RAZÓN observada en el archivo (303 filas)

| Estado | Razón | Filas |
|---|---|---|
| EN OPERACIÓN | ASIGNACIÓN RESPONSABLE | 218 |
| ALMACENADO | RETORNO | 52 |
| BAJA | PROCESAR BAJA | 14 |
| EN OPERACIÓN | CUSTODIA | 9 |
| ALMACENADO | BAJA | 4 |
| BAJA | ASIGNACIÓN RESPONSABLE | 1 |
| BAJA | BAJA | 1 |
| ALMACENADO | ALISTAMIENTO / SOPORTE | 2 |
| ALMACENADO | PROCESAR BAJA | 1 |
| PENDIENTE ESTADO | ASIGNACIÓN RESPONSABLE | 1 |

> Combinaciones no observadas se aceptan si ambas piezas pertenecen a los catálogos;
> si no, la fila entra como `pendiente_estado` + error de fila.

## 6. Regla de custodia (congelada, con modalidad)

Se diferencia **asignación funcional** de **custodia física** (decisión del usuario,
24-10-2026):

| Modalidad | Significado | ¿Cuenta para “un computador”? |
|---|---|---|
| `asignado` | El activo está asignado a esa persona para su uso | Sí |
| `custodia_fisica` | La persona solo **tiene** el activo (bodega); queda **disponible para asignar** | No |

1. **`asignado`** (fila en `usuario_asignados` con `modalidad = asignado` + snapshot
   `responsable_*`) cuando `estado = EN OPERACIÓN` y la cédula del bloque 1-14 es válida
   (≠ `PENDIENTE`/vacía). Es lo observado: 218 + 9 filas con custodio real.
2. **`custodia_fisica`** cuando `estado = ALMACENADO` y el tenedor del bloque 1-14 es el
   ANALISTA TIC de bodega (59 filas): se registra quién la tiene, con
   `equipos.ubicacion = BODEGA`, y el activo queda **disponible para asignar**. El
   ANALISTA TIC puede tener muchos activos en custodia; solo son “suyos” los asignados.
3. **Sin custodia** en `BAJA`, `HURTO`, `PENDIENTE ESTADO`, `TRÁNSITO`: la persona del
   bloque 1-14 se guarda solo como **referencia histórica** (historial + snapshot), no
   como custodia actual. Caso: las 16 filas `BAJA` conservan la cédula del último usuario.
4. `EN OPERACIÓN | CUSTODIA` (9 portátiles de un mismo analista): **sí** es custodia
   `asignado` (el estado EN OPERACIÓN manda sobre la razón).
5. Cédula textual `PENDIENTE` (1 fila EN OPERACIÓN): activo se importa **sin custodio**
   + hallazgo en la pestaña Corrección.
6. **Regla de bodega (15 días hábiles):** el activo entra con razón `RETORNO` y pasa a
   `DISPONIBLE` para asignación después de 15 días hábiles (descripción institucional de
   la razón `CUSTODIA`). Asignación desde `RETORNO` es elegible con advertencia.

## 7. Clave de conciliación e idempotencia

1. Clave natural: **`placa` normalizada** (numérica científica → entero) **si es un valor
   real**; si la placa es placeholder (`PENDIENTE` fila 112, `N/A` fila 130) o falta, se
   usa el **`serial`** normalizado (1 fila sin serial, fila 301, usa su placa
   `F101951`). Fila sin ambas reales → fila inválida (no ocurre con placa+serial).
2. Upsert por clave: nunca duplicar; **no sobrescribir valores con vacíos**; campos
   ausentes en el archivo no se tocan.
3. Hash de fila (sha256 de campos canónicos) para detectar cambios reales; segunda
   corrida = 0 altas, 0 actas nuevas, 0 cambios de estado.
4. Toda corrida crea un `import_batch` con diff por fila y permite rollback.

## 8. Anomalías conocidas (deben quedar en el reporte de importación)

| # | Anomalía | Detalle | Tratamiento |
|---|---|---|---|
| A1 | Placa + serial duplicados | `placa 10062150` / `serial 43XXDX1` aparece en 2 filas (2 funcionarias distintas, una con hostname PENDIENTE) | Importar 1 activo con el custodio de la primera fila; la segunda fila queda como **conflicto** en el reporte |
| A2 | Custodios ausentes del maestro | 16 de 203 custodios de Micro no están en `Listado funcionarios` (15 EMPLEADO + 1 CONTRATISTA) | Crearlos como `Funcionario` desde el bloque 1-14 |
| A3 | Cédulas duplicadas en maestro | `1088240970` (idéntica ×2) y `1054990408` (cargo/área distintos) | Dedupe por hash; el caso conflictivo se reporta y se conserva un registro |
| A4 | Cédula textual | 1 fila EN OPERACIÓN con `CEDULA = PENDIENTE` | Sin custodio + hallazgo en pestaña Corrección |
| A5 | Serial faltante | fila 301 (`placa F101951`, EN OPERACIÓN) sin serial | Importar por placa |
| A6 | Nombre de equipo PENDIENTE | 67 filas `PENDIENTE` + 14 `N/A` (81 en total) | Guardar placeholder, hallazgo en pestaña Corrección |
| A7 | Garantías textuales | `SIN REGISTRO` (125), `PENDIENTE` (59), vacíos (19) | `fin_garantia = NULL` + hallazgo en pestaña Corrección |
| A8 | Ubicación mixta | Col 13 mezcla pisos (`2.0`) y sedes (`ABECHUCOS`) | Guardar tal cual; separación piso/sede queda para limpieza |
| A9 | Banners con datos sueltos | Col 49 tiene fechas (`46226`…) mezcladas con banderas | Se guarda como texto en flags_auditoria |
| A10 | Placa placeholder | fila 112 `placa = PENDIENTE`, fila 130 `placa = N/A` (ambas del ANALISTA TIC) | Clave por serial; hallazgo en pestaña Corrección |
| A11 | `SHORTNAME` incompleto | 181 `PENDIENTE` + 15 vacíos (196/303) | Hallazgo en pestaña Corrección (no bloquea importación) |
| A12 | RAM/DISCO `PENDIENTE` | 28 filas cada una | Hallazgo en pestaña Corrección |

## 9. Mapa de columnas — `Listado funcionarios` (18 columnas)

| Col | Encabezado | Destino | Cobertura (vacíos/4243) |
|---|---|---|---|
| 1 | Empresa | `funcionarios.empresa` | 0% |
| 2 | Div | `funcionarios.div` (nuevo) | 0% |
| 3 | Dependencia | `funcionarios.departamento` | 0% |
| 4 | Cedula | `funcionarios.identificacion` + `identificacion_hash` | 0% (4241 únicas, 2 dup) |
| 5 | Apellidos | `funcionarios.apellidos` | 0% |
| 6 | Nombres | `funcionarios.nombres` | 0% |
| 7 | Correo | `funcionarios.email` (nuevo) | 0% |
| 8 | cargo | `funcionarios.cargo` | 0% |
| 9 | Area | `funcionarios.area` | 0% |
| 10 | cc_jefe | `funcionarios.jefe_cedula` (nuevo) | 0% |
| 11 | Nombres_jefe | `funcionarios.jefe_nombre` (nuevo) | 0% |
| 12 | Genero trabajador | `funcionarios.genero` (nuevo) | 0% |
| 13 | Sede de trabajo | `funcionarios.ciudad` (sede) | 1% |
| 14 | Ceco SAP | `funcionarios.ceco` (nuevo) | 0% |
| 15 | Fecha de vencimiento | `funcionarios.fecha_vencimiento` (nuevo) | 13% |
| 16 | Proceso | `funcionarios.proceso` (nuevo) | 0% |
| 17 | Tipo de recurso | `funcionarios.tipo_recurso` (nuevo) | 0% |
| 18 | Email jefe | `funcionarios.jefe_email` (nuevo) | 0% |

> Campos existentes sin fuente en el maestro: `estado` (activo/inactivo — no viene),
> `seccional`/`distrito` (se mantienen de Microinformatica o vacíos).
> **Regla:** el maestro manda para datos de persona; Microinformatica manda para
> dependencia/área de la custodia del activo.

## 10. Formato institucional FE-BS-F-0069 — `Novedad activo.xlsx`

El formulario “NOVEDAD DE ACTIVO” (v1, 11/04/2017) es la fuente de los **tipos de
novedad exactos** y de la estructura del acta.

### 10.1 Tipos de novedad (hoja “Hoja3” — lista del formulario)

| # | Nombre exacto | Código interno |
|---|---|---|
| 1 | Inventario Físico | `inventario_fisico` |
| 2 | Traslado | `traslado` |
| 3 | Cambio de responsable | `cambio_responsable` |
| 4 | Retiro definitivo | `retiro_definitivo` |
| 5 | Préstamo | `prestamo` |
| 6 | Reparación | `reparacion` |

> El sistema ya usa estos 6 códigos — **no se crean tipos nuevos**. Ramificaciones:
> hurto = `retiro_definitivo` + destino `HURTO / PERDIDA`; ajustes = `inventario_fisico`;
> devolución de préstamo = cierre del `Prestamo` + constancia de devolución;
> reparación entrada/salida = `sentido`.

### 10.2 Estructura del acta (hoja “Planilla”)

- **Cabecera:** Tipo de Novedad · Ubicación · Fecha (aaaa/mm/dd) · Empresa
  (Federacafé | Fondo Nacional | Almacafé | Procafecol | Otro — lista del formulario).
- **Ítems (8 columnas):** Activo fijo · SNº · Denominación del activo fijo · Marca ·
  Modelo · Serie · Placa de inventario · Observaciones.
  - La “Denominación” incluye periféricos y complementos (MONITOR, TECLADO, MOUSE,
    CABLE DE ENERGÍA, CABLE DE VIDEO…): el acta puede llevar ítems sin activo fijo ni
    placa (`N/A`).
  - Campo `Activo fijo` presente en el acta aunque el CMDB no lo traiga: se deja `N/A`
    o se toma de `equipos.activo_fijo` cuando exista.
- **Firmas (2 bloques):** quien entrega / quien recibe, cada uno con Dependencia ·
  Nombre y firma · **Cod. Personal** · Cargo.
  - Nuevo campo en el snapshot de custodia: `codigo_personal` (ej. `2007130`,
    cargo `APRENDIZ` → caso `asignacion_responsable`).

## 11. Resumen de volúmenes esperados en la importación

| Concepto | Valor |
|---|---|
| Filas de activos | 303 |
| Activos a crear (proyecto nuevo) | 303 − duplicados reales (1 conflicto) = **302** |
| Custodias `asignado` | 227 EN OPERACIÓN − 1 cédula `PENDIENTE` = **226** |
| Custodias `custodia_fisica` (bodega TIC) | **59** (ALMACENADO) |
| Sin custodia (referencia histórica) | 16 BAJA + 1 PENDIENTE = **17** |
| Custodios únicos con custodia | 203 (187 del maestro + 16 nuevos) |
| Estados | EN OPERACIÓN 227 · ALMACENADO 59 · BAJA 16 · PENDIENTE 1 |
| Tipos | TABLETA 117 · PORTATIL 99 · ESCRITORIO 75 · IMPRESORA MULTIFUNCIONAL 7 · TODO EN UNO 5 |
| Funcionarios maestro | 4243 filas → 4241 únicos |
| Actas iniciales esperadas | 1 por custodio con activos EN OPERACIÓN (~200) |
| Préstamos / reparaciones en archivo | 0 (razón PRÉSTAMO no aparece; ALISTAMIENTO/SOPORTE 2 en bodega) |

## 12. Inventario de vacíos — base para la pestaña “Corrección”

Objetivo del usuario: **uniformidad total, ningún valor vacío** en los campos
obligatorios. Baseline medido sobre las 303 filas de `Microinformatica`
(vacíos + placeholders `PENDIENTE`/`N/A`/`SIN REGISTRO`):

| Col | Encabezado | Incompletos | % | Severidad propuesta |
|---|---|---|---|---|
| 40 | ORDEN DE REMISIÓN | 303 | 100 % | Informativa (documento físico) |
| 44-46 | DIRECCION / QUIEN RECIBE | 303 | 100 % | Condicional (solo despachos) |
| 51 | OBSERVACIONES 2 | 302 | 100 % | Informativa |
| 43 | FNC | 279 | 92 % | Informativa |
| 47-50 | NO CRUZA NOMINA/SAP/AV, >15 AÑOS | 206-296 | 68-98 % | Informativa (flags) |
| 42 | Tipo Aprobado | 231 | 76 % | Informativa |
| 15-19 | Bloque responsable | 301 N/A | 99 % | Excluida (override; custodio = 1-14) |
| 36 | FIN DE GARANTIA | 203 (125 SIN REGISTRO + 59 PENDIENTE + 19 vacíos) | 67 % | **Obligatoria** |
| 7 | SHORTNAME | 196 (181 PENDIENTE + 15 vacíos) | 65 % | **Obligatoria** |
| 38 | TIPO DE PROPIEDAD | 147 (129 PENDIENTE + 18 vacíos) | 49 % | **Obligatoria** |
| 41 | OBSERVACIONES | 96 vacíos | 32 % | Condicional |
| 27 | NOMBRE DE EQUIPO | 81 (67 PENDIENTE + 14 N/A) | 27 % | **Obligatoria** |
| 39 | CHEKLIST (RESPONSABLE TI) | 27 vacíos | 9 % | **Obligatoria** |
| 32-33 | MEMORIA RAM / DISCO | 28 PENDIENTE c/u | 9 % | **Obligatoria** |
| 22-23 | SERIAL / PLACA | 1 sin serial; 2 placas placeholder | 1 % | **Bloqueante** |
| 6 | CEDULA custodio | 1 `PENDIENTE` | 0,3 % | **Bloqueante** (para EN OPERACIÓN) |

- **Bloqueante:** impide la importación limpia de la fila (identidad o custodia).
- **Obligatoria:** la fila importa, pero queda en la pestaña Corrección hasta resolver.
- **Condicional/Informativa:** se acepta vacía; si la regla de negocio la exige
  (p. ej. “quien recibe” en despachos) se marca como hallazgo.
- La pestaña muestra el semáforo % por columna, el detalle fila a fila y exporta
  XLSX/CSV para corregir el fuente y re-importar (ver plan F3-4).

---

*Congelado el 23-10-2026; ampliado el 24-10-2026 con el formato FE-BS-F-0069, la
modalidad de custodia y el inventario de vacíos. Cambios al archivo fuente ⇒
re-ejecutar el análisis y actualizar este documento antes de importar.*
