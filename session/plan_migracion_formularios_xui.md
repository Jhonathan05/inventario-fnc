# Plan — Migración de formularios crudos a `x-ui.*` y consolidación a11y

> **Origen:** pendiente #1/#2 de la sesión `fnc-forms` (19-10-2026). Creado 20-10-2026.
> **Skill de referencia:** `infra-fnc/skills/collections/fnc/skills/fnc-forms` v1.0.1.
> **Base:** `inventario-fnc` `main` `51b9319` (componentes ya emiten
> `aria-invalid`/`aria-describedby`); árbol limpio. **Re-verificado 20-10** tras
> el pull de Backups/Tareas (`8e93397`): `tipo_recursos`, `dashboard`,
> `equipos/_form` y rutas sin cambios sustantivos; los módulos nuevos solo
> tienen `index` (sin formularios).

---

## 0. Objetivo y criterio de éxito

Que **todo formulario activo** del inventario cumpla §2.5 de `fnc-forms`
(label `for`/`id`, `aria-invalid` + `aria-describedby` en error, mensaje visible
bajo el campo) **sin duplicar validación** y **sin tocar lógica de negocio**
(FormRequest, controladores, rutas, RBAC `@can`, tema `data-bs-theme`).

**Criterio de éxito por formulario:** enviar vacío → el HTML renderizado contiene
`aria-invalid="true"` en el campo y `aria-describedby` apuntando a un elemento
existente con el mensaje. Segunda corrida del mismo cambio → diff cero.

---

## 1. Estado actual (medido 2026-10-20)

### Ya conformes (referencia, no se tocan)
`funcionarios/create|edit`, `tickets/create`, `usuarios/form`,
`historial_tecnico/create|edit`, `equipos/partials/_modales_index`,
`licencias/show` — usan `x-ui.*` (12–35 usos c/u).

### Pendientes de migración (campos crudos contados)

| Formulario | Crudos | `x-ui` ya | Patrón | Riesgo | Lote |
|---|---:|---:|---|---|---|
| `checklists/_form` | 8 | 0 | estándar (label+input+feedback) | Bajo | L1 |
| `licencias_asignaciones/create` | 8 | 0 | estándar; **labels sin `for`**; hints `form-text` | Bajo | L2 |
| `licencias_asignaciones/edit` | 8 | 0 | gemelo del anterior | Bajo | L2 |
| `prestamos/create` | 9 | 0 | estándar; **`equipo_id` ya usa `aria-describedby` de hint** | Bajo | L3 |
| `equipos/_form` (655 líne.) | ~13 | 35 | mixto; **28 refs JS por id/name** (estado↔razón, serial↔sin_serial, acordeón, plantilla de complementos) | **Alto** | L4 |
| `equipos/importar` | 2 | 0 | file input | Bajo | L4 |
| `plantillas_pdf/create` | 6 | 0 | estándar + 1 checkbox + `novalidate` | Medio | L5 |
| `plantillas_pdf/edit` | 4 | 0 | ídem | Medio | L5 |
| `licencias/create` | 1 | 9 | solo `correo_compra` crudo | Trivial | L6 |
| `licencias/edit` | 1 | 9 | ídem | Trivial | L6 |
| `tipo_recursos/create` | 1 | 0 | diseño custom (input-group+icono, hint, `oninput`, grilla de complementos con JS) | Bajo (parche aria, **no** migrar diseño) | L6 |
| `tipo_recursos/edit` | 1 | 0 | ídem | Bajo | L6 |
| `roles/create` | 1 | 1 | **error de grupo** (grilla de permisos/switches) | Bajo (parche aria de grupo) | L6 |
| `roles/edit` | 1 | 1 | ídem | Bajo | L6 |
| `dashboard` (Nueva tarea) | 1 | 0 | quick-create compacto `fnc-*` con `aria-label` | Bajo (parche aria mínimo) | L6 |

### Fuera de alcance (justificado)
- `solicitudes_password/*` — el módulo **no tiene rutas** en `routes/web.php`
  (verificado 20-10); vistas inalcanzables.
- `actas/create` — subsistema propio (`fnc-input` + `fnc-field-error` + JS
  `mostrarError`) que **ya cumple** §2.5 (`aria-invalid`, `aria-describedby`,
  `role="alert"`, 7 puntos de error). Ver decisión en §5.

---

## 2. Reglas de migración (derivadas de `fnc-forms`)

1. Se reemplaza **solo el markup del campo** (label + input/select/textarea +
   feedback). `<form>`, CSRF, action, botones y estructura de secciones/acordeón
   quedan intactos.
2. **Preservar `id` y `name` exactos.** En `equipos/_form` el JS referencia
   `estadoSelect`, `razonEstadoWrap`, `serialInput`, `sinSerialCheck` y consultas
   por `[name=...]`: el componente acepta `id=` explícito y `{{ $attributes }}`.
3. Preservar `old()` (valores y defaults), `required`, `pattern`, `oninput`,
   `min/max`, `placeholder`, `autocomplete`, `maxlength`.
4. `containerClass` del componente hereda las clases de columna actuales
   (`col-lg-6`, `col-12`, etc.).
5. Selects: el slot conserva todas las opciones, `@selected(old(...))` y
   `disabled` condicionales.
6. Campo sin label visible (compactos) conserva `aria-label` vía atributos y no
   se le añade label artificial.
7. **Excepción aria-describedby preexistente:** si el campo ya usa
   `aria-describedby` para un hint (hoy: `equipo_id` en `prestamos/create`),
   **no migrar ese campo**: aplicar parche de 2 líneas (id en el feedback +
   merge del describedby), porque dos atributos `aria-describedby` duplicados
   dejan ganando al primero y se perdería el enlace del error.
8. No añadir features a los componentes salvo necesidad demostrada en un lote
   (hoy: ninguna; `x-ui.checkbox` solo si aparece un checkbox con `@error`
   propio — verificar en READ de cada lote).
9. Un lote = un commit + push. Nunca `migrate:fresh`. Tras editar Blade:
   `docker restart laravel_app` + `php artisan view:clear`.

---

## 3. Lote 0 — Arnés de verificación ( prerequisites )

1. **Spec E2E** `tests/e2e/forms-aria.spec.js` (proyecto Tablet/Desktop): por
   cada página del alcance, enviar el formulario vacío y afirmar:
   `[aria-invalid="true"]` visible y su `aria-describedby` apunta a un nodo con
   texto. **Nota técnica:** la mayoría de forms no llevan `novalidate`, por lo
   que el `required` HTML bloquea el submit; el spec debe poner
   `form.noValidate = true` por `page.evaluate` antes de enviar.
   Páginas: `/checklists/create`(?), `/licencia-asignaciones/create`,
   `/prestamos/create`, `/equipos/create`, `/equipos/1/edit`,
   `/plantillas-pdf/create`, `/licencias/create` (confirmar rutas exactas por
   lote con `routes/web.php`).
2. **Baseline visual** (claro/oscuro) de los formularios a migrar, para comparar
   tras cada lote (mismo método del smoke FHD: capturas a 1440).

---

## 4. Lotes de migración

| Lote | Contenido | Validación específica | Estimación |
|---|---|---|---|
| **L1** | `checklists/_form` (piloto, 8 campos) | E2E aria + smoke visual del form | ½ sesión |
| **L2** | `licencias_asignaciones/create` + `edit` | E2E aria + smoke; verificar que `licencia_id` gana `id` y su label `for` | ½ sesión |
| **L3** | `prestamos/create` (migra 8; parchea `equipo_id` por regla 7) | E2E aria + smoke funcional del hint | ½ sesión |
| **L4** | `equipos/_form` (~13 campos) + `equipos/importar` | **Antes de editar:** grep de las 28 refs JS. Después: smoke funcional del toggle estado↔razón, serial↔sin_serial, acordeón y plantilla de complementos + E2E aria + smoke visual (incluye FHD) | 1 sesión |
| **L5** | `plantillas_pdf/create` + `edit` | E2E aria + smoke (checkbox y `novalidate` intactos) | ½ sesión |
| **L6** | Parches a11y sin migración: `tipo_recursos` create/edit (`nombre`), `roles` create/edit (error de grupo), `licencias` create/edit (`correo_compra`), dashboard (Nueva tarea) | E2E aria adaptado (grupo de permisos: `aria-describedby` en el contenedor) | ½ sesión |
| **L7** | Decisión `actas/create` (§5) | — | 0 (B) |
| **Cierre** | Segunda corrida de `fnc-forms` sobre 2 formularios migrados (punto fijo en uso real); bump de lecciones a v1.0.2 en la skill + bitácoras de ambos repos | reporte §5 de la skill | ½ sesión |

**DoD por lote:** diff mínimo (solo vistas) · render/E2E aria verde · PHPUnit
en verde (baseline crece con cada módulo: 62 + BackupsTest/TareasTest) ·
PHPStan OK · Playwright en verde (Tablet + Desktop FHD; baseline ampliado con
shell/tareas/backups) · barrido HTTP sin 500 · smoke visual claro/oscuro del
formulario · commit+push · bitácora al día.

**Total estimado:** ~3–4 sesiones (L7 en opción B no consume).

---

## 5. Decisión `actas/create` (cierra pendiente #2)

- **Opción A — migrar campos a `x-ui.*`:** exige portar el stepper (JS
  `mostrarError` → ids `{id}_error`), reemplazar `fnc-field-error` por
  `invalid-feedback` (cambio visual institucional) y mantener los selects
  dinámicos CMDB por fila. Alto costo/riesgo, **ganancia de comportamiento:
  nula** (ya cumple §2.5).
- **Opción B — no-op definitivo (recomendada):** se documenta como conforme y
  se revisa solo si el módulo se refactoriza por otras razones. El parche JS
  existente queda como implementación válida del contrato.

---

## 6. Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Romper JS de `equipos/_form` (ids/names) | grep de refs antes de editar; `id=` explícito en el componente; smoke funcional del lote |
| Regresión visual (wrappers `mb-3`, columnas) | `containerClass` con las clases actuales; baseline + comparación de capturas |
| Doble `aria-describedby` (hint + error) | regla 7: parche en vez de migración en campos con hint descrito |
| `required` HTML bloquea el submit del spec E2E | `form.noValidate = true` por `evaluate` antes de enviar |
| Selects con opciones dinámicas/disabled | el slot se copia íntegro; sin lógica nueva |
| Caché (OPcache `validate_timestamps=0`, vistas compiladas) | `docker restart laravel_app` + `view:clear` tras cada lote |
| Duplicar validación | FormRequest/controladores intocables; el spec de cada lote lo afirma (mismos errores del bag) |

---

## 7. Fuera de alcance permanente

`solicitudes_password/*` (sin rutas) · rediseños visuales · cambios a
FormRequest/rutas/permisos · nuevas features de componentes sin necesidad
demostrada · hook JS de zoom FHD (backlog separado).
