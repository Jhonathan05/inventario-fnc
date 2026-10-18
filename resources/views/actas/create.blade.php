@extends('layouts.inventario')

@section('title', 'Nueva acta de novedad')

@push('styles')
<style>
    .fnc-acta-campos { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 14px; }
    .fnc-campo { min-width: 0; }
    .fnc-campo--full { grid-column: 1 / -1; }
    @media (max-width: 575.98px) { .fnc-acta-campos { grid-template-columns: minmax(0, 1fr); } }
    .fnc-acta-destino, .fnc-acta-prestamo { display: none; }
    .fnc-acta-destino.is-visible, .fnc-acta-prestamo.is-visible { display: block; }
    .fnc-func-warning { display: none; padding: 10px 14px; border-radius: 10px; font-size: .8rem; margin: 12px 0; }
    .fnc-func-warning.is-visible { display: block; }
    .fnc-func-warning--prestamo { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .fnc-func-warning--autorizacion { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .fnc-autorizacion { display: none; }
    .fnc-autorizacion.is-visible { display: block; }
    .fnc-step-nav { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; }
    .fnc-review-label { font-size: .66rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--fnc-text-3); margin-bottom: 3px; }
    .fnc-review-value { font-size: .86rem; font-weight: 500; color: var(--fnc-text); margin-bottom: 10px; }
    /* Botón editar en la tarjeta de datos */
    .fnc-data-edit-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border: 1px solid var(--fnc-hairline);
        border-radius: 9px;
        background: var(--fnc-surface-solid);
        color: var(--fnc-text-2);
        font-size: .76rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s, color .15s, border-color .15s;
    }
    .fnc-data-edit-btn:hover { background: var(--fnc-surface-sunken); color: var(--fnc-text); border-color: var(--fnc-hairline-strong); }

    /* Slide-over drawer desde la derecha */
    .fnc-drawer-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1080;
        background: rgba(8, 11, 18, 0.4);
        opacity: 0;
        transition: opacity .25s;
    }
    .fnc-drawer-overlay.is-open { display: block; opacity: 1; }

    .fnc-drawer {
        position: fixed;
        top: 0;
        right: -420px;
        bottom: 0;
        width: 400px;
        max-width: 90vw;
        z-index: 1090;
        background: var(--fnc-surface-solid);
        border-left: 1px solid var(--fnc-hairline);
        box-shadow: -20px 0 60px -20px rgba(15, 23, 42, 0.4);
        display: flex;
        flex-direction: column;
        transition: right .28s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .fnc-drawer.is-open { right: 0; }

    .fnc-drawer__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--fnc-hairline);
        flex: 0 0 auto;
    }
    .fnc-drawer__title { font-size: .95rem; font-weight: 700; color: var(--fnc-text); }
    .fnc-drawer__sub { font-size: .72rem; color: var(--fnc-text-3); margin-top: 2px; }

    .fnc-drawer__body {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 16px 20px;
    }

    .fnc-drawer__foot {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding: 14px 20px;
        border-top: 1px solid var(--fnc-hairline);
        flex: 0 0 auto;
    }
    .fnc-data-card {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 10px 14px;
        padding: 14px 16px;
        border-radius: 12px;
        background: var(--fnc-surface-sunken);
    }
    .fnc-data-card__item { min-width: 0; }
    .fnc-data-card__label {
        display: block;
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--fnc-text-3);
        margin-bottom: 2px;
    }
    .fnc-data-card__value {
        display: block;
        font-size: .84rem;
        font-weight: 600;
        color: var(--fnc-text);
        line-height: 1.3;
    }
    .fnc-data-card__value:empty::before { content: '—'; color: var(--fnc-text-3); }
    .modal-backdrop { z-index: 1050 !important; }
    .modal { z-index: 1055 !important; }
    .modal-dialog { z-index: 1060; }
</style>
@endpush

@section('content')
<x-ui.toolbar title="Nueva acta de novedad de activos" icon="clipboard-check" />

{{-- ======== Indicador de progreso ======== --}}
<nav class="fnc-stepper" aria-label="Progreso del acta" id="stepperBar">
    <div class="fnc-stepper__item is-active" data-step="1">
        <span class="fnc-stepper__num">1</span>
        <span class="fnc-stepper__label">Movimiento</span>
    </div>
    <div class="fnc-stepper__item" data-step="2">
        <span class="fnc-stepper__num">2</span>
        <span class="fnc-stepper__label">Responsable</span>
    </div>
    <div class="fnc-stepper__item" data-step="3">
        <span class="fnc-stepper__num">3</span>
        <span class="fnc-stepper__label">Activos</span>
    </div>
    <div class="fnc-stepper__item" data-step="4">
        <span class="fnc-stepper__num">4</span>
        <span class="fnc-stepper__label">Revisión y firmas</span>
    </div>
</nav>

<form method="POST" action="{{ route('actas.store') }}" id="formActa" enctype="multipart/form-data" novalidate>
    @csrf
    <input type="hidden" name="return_to" value="{{ $returnTo }}">

    {{-- ============================================================ PASO 1: MOVIMIENTO --}}
    <div class="fnc-step is-active" id="paso1" data-step="1">
        <x-fnc.panel title="Movimiento" icon="arrow-right-left" sub="Define el tipo de novedad y el estado resultante de todos los activos">
            <div class="fnc-acta-campos">
                <div class="fnc-campo fnc-campo--full">
                    <label for="tipo_accion" class="fnc-label">Tipo de novedad *</label>
                    <select name="tipo_accion" id="tipo_accion" class="fnc-input" required aria-describedby="err_tipo">
                        @foreach ($tipos as $codigo => $etiqueta)
                            <option value="{{ $codigo }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <p class="fnc-field-error" id="err_tipo" role="alert">Selecciona el tipo de novedad.</p>
                </div>
                <div class="fnc-campo">
                    <label for="ubicacion" class="fnc-label">Ubicación</label>
                    <input type="text" name="ubicacion" id="ubicacion" class="fnc-input" value="Ibagué">
                </div>
                <div class="fnc-campo">
                    <label for="fecha_accion" class="fnc-label">Fecha *</label>
                    <input type="date" name="fecha_accion" id="fecha_accion" class="fnc-input" required
                           value="{{ old('fecha_accion', now()->format('Y-m-d')) }}" aria-describedby="err_fecha">
                    <p class="fnc-field-error" id="err_fecha" role="alert">La fecha es obligatoria.</p>
                </div>
                <div class="fnc-campo fnc-acta-destino" id="campoDestino">
                    <label for="destino" class="fnc-label">Destino</label>
                    <select name="destino" id="destino" class="fnc-input">
                        <option value="disponible">Disponible (devolver al pool)</option>
                        <option value="baja">Baja (disposición final)</option>
                    </select>
                </div>
            </div>
            <div class="fnc-acta-prestamo mt-2" id="campoPrestamo">
                <div class="fnc-acta-campos">
                    <div class="fnc-campo">
                        <label for="fecha_inicio" class="fnc-label">Fecha de inicio *</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="fnc-input" aria-describedby="err_inicio">
                        <p class="fnc-field-error" id="err_inicio" role="alert">Fecha de inicio requerida para préstamos.</p>
                    </div>
                    <div class="fnc-campo">
                        <label for="fecha_fin_probable" class="fnc-label">Fecha probable de fin *</label>
                        <input type="date" name="fecha_fin_probable" id="fecha_fin_probable" class="fnc-input" aria-describedby="err_fin">
                        <p class="fnc-field-error" id="err_fin" role="alert">Fecha de fin requerida para préstamos.</p>
                    </div>
                </div>
            </div>
        </x-fnc.panel>
        <div class="fnc-step-nav">
            <button type="button" class="btn btn-primary" data-next="2">Siguiente →</button>
        </div>
    </div>

    {{-- ============================================================ PASO 2: RESPONSABLE --}}
    <div class="fnc-step" id="paso2" data-step="2">
        <x-fnc.panel title="Responsable del acta" icon="user-round" sub="Busca al funcionario en el maestro — los datos se rellenan automáticamente">
            <div class="fnc-picker" id="pickerFuncionario" style="position:relative;z-index:21">
                <div class="fnc-picker__search">
                    <x-fnc.icon name="search" :size="18" />
                    <input type="text" id="buscarFuncionario" class="fnc-picker__input"
                           placeholder="Buscar funcionario por cédula o nombre…" autocomplete="off"
                           aria-label="Buscar funcionario" aria-describedby="err_funcionario">
                </div>
                <div class="fnc-picker__results" id="resultadosFuncionario"></div>
            </div>
            <p class="fnc-field-error" id="err_funcionario" role="alert">Selecciona un funcionario del maestro.</p>

            {{-- Avisos de la regla de computador (aparecen al seleccionar) --}}
            <div class="fnc-func-warning fnc-func-warning--prestamo" id="avisoPrestamo">
                ⚠️ El funcionario tiene un computador en <strong>reparación</strong>.
                Registre este movimiento como <strong>Préstamo</strong> (no como Inventario Físico).
            </div>
            <div class="fnc-func-warning fnc-func-warning--autorizacion" id="avisoAutorizacion">
                🔒 El funcionario ya tiene un computador <strong>activo</strong>.
                Debe adjuntar la <strong>autorización del jefe superior</strong> (PDF) en el paso de revisión.
                <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="btnVerActivosFunc"
                        style="font-size:.76rem;padding:3px 12px;border-radius:8px">
                    Ver activos asignados
                </button>
            </div>

            {{-- Datos del funcionario: tarjeta de texto (sin inputs) --}}
            <div id="datosFuncionario" style="display:none" class="mt-3">
                <div id="activosFuncionario" class="mb-3"></div>

                {{-- Hidden inputs: llevan los datos al servidor --}}
                <input type="hidden" name="usuario_cedula" data-h-func="cedula">
                <input type="hidden" name="usuario_nombre" data-h-func="nombre">
                <input type="hidden" name="usuario_cargo" data-h-func="cargo">
                <input type="hidden" name="usuario_area" data-h-func="area">
                <input type="hidden" name="usuario_departamento" data-h-func="departamento">
                <input type="hidden" name="usuario_ciudad" data-h-func="ciudad">
                <input type="hidden" name="usuario_empresa_funcionario" data-h-func="empresa_funcionario">
                <input type="hidden" name="usuario_tipo_vinculacion" data-h-func="tipo_vinculacion">
                <input type="hidden" name="usuario_seccional" data-h-func="seccional">
                <input type="hidden" name="usuario_distrito" data-h-func="distrito">

                {{-- Tarjeta de texto con los datos del maestro --}}
                <div class="fnc-data-card">
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Cédula</span>
                        <span class="fnc-data-card__value" data-t-func="cedula"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Nombres y apellidos</span>
                        <span class="fnc-data-card__value" data-t-func="nombre"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Cargo</span>
                        <span class="fnc-data-card__value" data-t-func="cargo"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Área</span>
                        <span class="fnc-data-card__value" data-t-func="area"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Departamento</span>
                        <span class="fnc-data-card__value" data-t-func="departamento"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Ciudad</span>
                        <span class="fnc-data-card__value" data-t-func="ciudad"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Empresa funcionario</span>
                        <span class="fnc-data-card__value" data-t-func="empresa_funcionario"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Tipo vinculación</span>
                        <span class="fnc-data-card__value" data-t-func="tipo_vinculacion"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Seccional</span>
                        <span class="fnc-data-card__value" data-t-func="seccional"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Distrito</span>
                        <span class="fnc-data-card__value" data-t-func="distrito"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Empresa propietaria</span>
                        <span class="fnc-data-card__value" data-t-func="empresa_propietaria"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Fuente de recurso</span>
                        <span class="fnc-data-card__value" data-t-func="fuente_recurso"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Ubicación y piso</span>
                        <span class="fnc-data-card__value" data-t-func="piso"></span>
                    </div>
                    <div class="fnc-data-card__item">
                        <span class="fnc-data-card__label">Shortname</span>
                        <span class="fnc-data-card__value" data-t-func="shortname"></span>
                    </div>
                </div>

                {{-- Hidden inputs de los campos derivados --}}
                <input type="hidden" name="usuario_empresa_propietaria" data-h-func="empresa_propietaria">
                <input type="hidden" name="usuario_fuente_recurso" data-h-func="fuente_recurso">
                <input type="hidden" name="usuario_piso" data-h-func="piso">
                <input type="hidden" name="usuario_shortname" data-h-func="shortname">
            </div>
        </x-fnc.panel>
        <div class="fnc-step-nav">
            <button type="button" class="btn btn-secondary" data-prev="1">← Anterior</button>
            <button type="button" class="btn btn-primary" data-next="3">Siguiente →</button>
        </div>
    </div>

    {{-- ============================================================ PASO 3: ACTIVOS --}}
    <div class="fnc-step" id="paso3" data-step="3">
        <x-fnc.panel>
            <x-slot:title>
                Activos del acta
                <span class="fnc-items__count ms-2" id="contadorActivos" style="display:none">
                    <x-fnc.icon name="package" :size="12" />
                    <span id="contadorNumero">0</span>
                </span>
            </x-slot:title>
            <x-slot:sub>Sólo se ofrecen activos en el estado requerido por el tipo de movimiento</x-slot:sub>
            <x-slot:icon>monitor</x-slot:icon>

            <div class="fnc-picker mb-3">
                <div class="fnc-picker__search">
                    <x-fnc.icon name="search" :size="18" />
                    <input type="text" id="buscarActivo" class="fnc-picker__input"
                           placeholder="Buscar activo por nombre, serial, placa, marca o tipo…" autocomplete="off"
                           aria-label="Buscar activo">
                </div>
                <div class="fnc-picker__results" id="resultadosActivo"></div>
            </div>
            <p class="fnc-field-error" id="err_activos" role="alert">Agrega al menos un activo al acta.</p>

            <div id="tablaActivos" style="display:none">
                <table class="fnc-items">
                    <thead>
                        <tr>
                            <th style="min-width:220px">Activo</th>
                            <th class="fnc-items__field">Estado de uso</th>
                            <th class="fnc-items__field">Admin./Control.</th>
                            <th class="fnc-items__field">Tipo propiedad</th>
                            <th class="fnc-items__field">Orden remisión</th>
                            <th class="fnc-items__field">Observaciones</th>
                            <th style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaActivos"></tbody>
                </table>
            </div>
            <div id="sinActivos" class="fnc-empty">
                <span class="fnc-empty__icon"><x-fnc.icon name="monitor" :size="20" /></span>
                <span class="fnc-empty__title">Sin activos seleccionados</span>
                <p class="fnc-empty__text">Busca y agrega los activos que hacen parte de este movimiento.</p>
            </div>
        </x-fnc.panel>
        <div class="fnc-step-nav">
            <button type="button" class="btn btn-secondary" data-prev="2">← Anterior</button>
            <button type="button" class="btn btn-primary" data-next="4">Siguiente →</button>
        </div>
    </div>

    {{-- ============================================================ PASO 4: REVISIÓN Y FIRMAS --}}
    <div class="fnc-step" id="paso4" data-step="4">
        {{-- Resumen del movimiento --}}
        <x-fnc.panel title="Resumen del movimiento" icon="clipboard-check">
            <div id="resumenMovimiento"></div>
        </x-fnc.panel>

        {{-- Autorización condicional --}}
        <div class="fnc-autorizacion mb-3" id="campoAutorizacion">
            <x-fnc.panel title="Autorización del jefe superior" icon="file-check" sub="Requerida para asignar un segundo computador">
                <div class="fnc-acta-campos">
                    <div class="fnc-campo">
                        <label for="autorizacion_pdf" class="fnc-label">Documento PDF *</label>
                        <input type="file" id="autorizacion_pdf" name="autorizacion_pdf" accept=".pdf" class="fnc-input" style="padding:6px">
                        <p class="fnc-field-error" id="err_autorizacion" role="alert">Adjunta la autorización en PDF.</p>
                    </div>
                    <div class="fnc-campo">
                        <label for="autorizacion_comentario" class="fnc-label">Comentario</label>
                        <input type="text" id="autorizacion_comentario" name="autorizacion_comentario" class="fnc-input" placeholder="Nombre del jefe que autoriza">
                    </div>
                </div>
            </x-fnc.panel>
        </div>

        {{-- Firmas: tarjeta de texto con hidden inputs --}}
        <x-fnc.panel title="Firmas del acta" icon="pen-line" sub="Doble bloque del formato FE-BS-F-0069 — auto-rellenadas" class="mb-3">
            {{-- Hidden inputs: llevan los datos al servidor --}}
            <input type="hidden" name="entregador_dependencia" value="TIC">
            <input type="hidden" name="entregador_nombre" value="{{ auth()->user()->name }}">
            <input type="hidden" name="entregador_codigo" value="{{ auth()->id() }}">
            <input type="hidden" name="entregador_cargo" value="{{ auth()->user()->roles->first()?->name ?? 'Analista TIC' }}">
            <input type="hidden" name="recibe_dependencia" data-h-firma="dependencia">
            <input type="hidden" name="recibe_nombre" data-h-firma="nombre">
            <input type="hidden" name="recibe_codigo" data-h-firma="codigo">
            <input type="hidden" name="recibe_cargo" data-h-firma="cargo">

            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fnc-label mb-2">Quien entrega</h6>
                    <div class="fnc-data-card">
                        <div class="fnc-data-card__item">
                            <span class="fnc-data-card__label">Dependencia</span>
                            <span class="fnc-data-card__value">TIC</span>
                        </div>
                        <div class="fnc-data-card__item">
                            <span class="fnc-data-card__label">Código</span>
                            <span class="fnc-data-card__value">{{ auth()->id() }}</span>
                        </div>
                        <div class="fnc-data-card__item">
                            <span class="fnc-data-card__label">Cargo</span>
                            <span class="fnc-data-card__value">{{ auth()->user()->roles->first()?->name ?? 'Analista TIC' }}</span>
                        </div>
                        <div class="fnc-data-card__item" style="grid-column: 1 / -1">
                            <span class="fnc-data-card__label">Nombre y firma</span>
                            <span class="fnc-data-card__value">{{ auth()->user()->name }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="fnc-label mb-2">Quien recibe</h6>
                    <div class="fnc-data-card">
                        <div class="fnc-data-card__item">
                            <span class="fnc-data-card__label">Dependencia</span>
                            <span class="fnc-data-card__value" data-t-firma="dependencia"></span>
                        </div>
                        <div class="fnc-data-card__item">
                            <span class="fnc-data-card__label">Código</span>
                            <span class="fnc-data-card__value" data-t-firma="codigo"></span>
                        </div>
                        <div class="fnc-data-card__item">
                            <span class="fnc-data-card__label">Cargo</span>
                            <span class="fnc-data-card__value" data-t-firma="cargo"></span>
                        </div>
                        <div class="fnc-data-card__item" style="grid-column: 1 / -1">
                            <span class="fnc-data-card__label">Nombre y firma</span>
                            <span class="fnc-data-card__value" data-t-firma="nombre"></span>
                        </div>
                    </div>
                </div>
            </div>
        </x-fnc.panel>

        {{-- Observaciones --}}
        <x-fnc.panel title="Observaciones" icon="message-square" class="mb-3">
            <label for="observaciones" class="fnc-label sr-only">Observaciones del acta</label>
            <textarea name="observaciones" id="observaciones" class="fnc-input" rows="4"
                      style="width:100%;resize:vertical;min-height:80px"
                      placeholder="Observaciones del acta…">{{ old('observaciones') }}</textarea>
        </x-fnc.panel>

        <div class="fnc-step-nav">
            <button type="button" class="btn btn-secondary" data-prev="3">← Anterior</button>
            <button type="submit" class="btn btn-primary" id="btnGuardarActa">
                <i class="bi bi-clipboard-check me-1"></i> Registrar acta y actualizar CMDB
            </button>
        </div>
    </div>
</form>

{{-- Botón editar dentro de la tarjeta de datos (se inserta por JS tras el fnc-data-card) --}}

{{-- Drawer: editar datos del responsable --}}
<div class="fnc-drawer-overlay" id="drawerOverlay"></div>
<aside class="fnc-drawer" id="drawerEditar" role="dialog" aria-modal="true" aria-labelledby="drawerTitulo">
    <div class="fnc-drawer__head">
        <div>
            <div class="fnc-drawer__title" id="drawerTitulo">Editar datos del responsable</div>
            <div class="fnc-drawer__sub" id="drawerSubtitulo">Los cambios se aplican al acta actual y al maestro</div>
        </div>
        <button type="button" class="fnc-data-edit-btn" id="btnCerrarDrawer" aria-label="Cerrar" style="border:none;background:none;font-size:1.2rem;padding:4px 8px">&times;</button>
    </div>
    <div class="fnc-drawer__body">
        <div class="fnc-acta-campos" style="grid-template-columns: 1fr">
            <div class="fnc-campo"><label class="fnc-label" for="ed_ced">Cédula</label><input type="text" id="ed_ced" class="fnc-input" readonly></div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_nom">Nombres y apellidos *</label><input type="text" id="ed_nom" class="fnc-input"></div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_cargo">Cargo</label><input type="text" id="ed_cargo" class="fnc-input"></div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_area">Área</label><input type="text" id="ed_area" class="fnc-input"></div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_dep">Departamento</label><input type="text" id="ed_dep" class="fnc-input"></div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_ciud">Ciudad</label><input type="text" id="ed_ciud" class="fnc-input"></div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_prop">Empresa propietaria</label>
                <select id="ed_prop" class="fnc-input">
                    <option value="FEDERACIÓN">Federación</option>
                    <option value="FONDO NACIONAL">Fondo Nacional</option>
                    <option value="ALMACAFÉ">Almacafé</option>
                    <option value="BANCOLOMBIA">Bancolombia</option>
                </select>
            </div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_emp">Empresa del funcionario</label><input type="text" id="ed_emp" class="fnc-input"></div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_frec">Fuente de recurso</label>
                <select id="ed_frec" class="fnc-input">
                    <option value="FON1">FON1</option>
                    <option value="FON2">FON2</option>
                    <option value="FEPC">FEPC</option>
                </select>
            </div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_vinc">Tipo vinculación</label>
                <select id="ed_vinc" class="fnc-input">
                    <option value="EMPLEADO">Empleado</option>
                    <option value="CONTRATISTA">Contratista</option>
                    <option value="APRENDIZ">Aprendiz</option>
                </select>
            </div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_piso">Ubicación y piso</label><input type="text" id="ed_piso" class="fnc-input" placeholder="Piso / ubicación"></div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_short">Shortname</label><input type="text" id="ed_short" class="fnc-input" placeholder="Usuario de red"></div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_secc">Seccional</label><input type="text" id="ed_secc" class="fnc-input"></div>
            <div class="fnc-campo"><label class="fnc-label" for="ed_dist">Distrito</label><input type="text" id="ed_dist" class="fnc-input"></div>
        </div>
    </div>
    <div class="fnc-drawer__foot">
        <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarDrawer">Cancelar</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarDrawer">
            <i class="bi bi-check-lg me-1"></i> Guardar cambios
        </button>
    </div>
</aside>

{{-- Modal: activos del funcionario --}}
<div class="modal fade" id="modalActivosFunc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:1px solid var(--fnc-hairline)">
            <div class="modal-header" style="border-bottom:1px solid var(--fnc-hairline)">
                <h5 class="modal-title" style="font-size:.95rem;font-weight:700">
                    Activos de <span id="modalActivosNombre"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" style="padding:16px">
                <table class="fnc-items">
                    <thead><tr><th style="min-width:200px">Activo</th><th>Estado</th></tr></thead>
                    <tbody id="modalActivosCuerpo"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var REGLAS = @json($reglas);
    var pasoActual = 1;
    var seleccionados = [];
    var funcionarioSeleccionado = null;
    var busquedaTimer = null;

    // ---- Utilidades ----
    function $(sel) { return document.querySelector(sel); }
    function $$(sel) { return document.querySelectorAll(sel); }

    // ---- Mostrar error inline ----
    function mostrarError(id, mostrar) {
        var err = document.getElementById(id);
        var input = err ? document.getElementById(id.replace('err_', '')) : null;
        if (err) err.classList.toggle('is-visible', mostrar);
        if (input) {
            if (mostrar) { input.setAttribute('aria-invalid', 'true'); input.setAttribute('aria-describedby', id); }
            else { input.removeAttribute('aria-invalid'); input.removeAttribute('aria-describedby'); }
        }
    }

    // ---- Validación on-blur ----
    function validarOnBlur(inputId, errorId, validar) {
        var input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('blur', function () {
            mostrarError(errorId, !validar());
        });
    }

    // ---- Stepper ----
    function irAPaso(num) {
        pasoActual = num;
        $$('.fnc-step').forEach(function (el) {
            el.classList.toggle('is-active', parseInt(el.dataset.step) === num);
        });
        $$('.fnc-stepper__item').forEach(function (el) {
            var n = parseInt(el.dataset.step);
            el.classList.toggle('is-active', n === num);
            el.classList.toggle('is-done', n < num);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
        if (num === 4) renderResumen();
    }

    function validarPaso(num) {
        switch (num) {
            case 1:
                var tipo = document.getElementById('tipo_accion');
                var fecha = document.getElementById('fecha_accion');
                var ok = true;
                mostrarError('err_tipo', !tipo.value); if (!tipo.value) ok = false;
                mostrarError('err_fecha', !fecha.value); if (!fecha.value) ok = false;
                if (tipo.value === 'prestamo') {
                    var fi = document.getElementById('fecha_inicio');
                    var ff = document.getElementById('fecha_fin_probable');
                    mostrarError('err_inicio', !fi.value); if (!fi.value) ok = false;
                    mostrarError('err_fin', !ff.value); if (!ff.value) ok = false;
                }
                return ok;
            case 2:
                var ok2 = !!funcionarioSeleccionado;
                mostrarError('err_funcionario', !ok2);
                return ok2;
            case 3:
                var ok3 = seleccionados.length > 0;
                mostrarError('err_activos', !ok3);
                if (!ok3) return false;
                // Validar selects obligatorios de ficha CMDB
                var vacios = 0;
                cuerpoTabla.querySelectorAll('select[required]').forEach(function (sel) {
                    if (!sel.value) {
                        sel.style.borderColor = '#dc2626';
                        sel.setAttribute('aria-invalid', 'true');
                        vacios++;
                    } else {
                        sel.style.borderColor = '';
                        sel.removeAttribute('aria-invalid');
                    }
                });
                if (vacios > 0) {
                    alert('Hay ' + vacios + ' campo(s) obligatorio(s) sin seleccionar en la ficha CMDB de los activos (estado de uso / tipo de propiedad).');
                    return false;
                }
                return true;
            case 4:
                return true;
        }
        return false;
    }

    $$('[data-next]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var next = parseInt(this.dataset.next);
            if (validarPaso(pasoActual)) irAPaso(next);
        });
    });
    $$('[data-prev]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            irAPaso(parseInt(this.dataset.prev));
        });
    });

    // ---- Movimiento: campos condicionales ----
    var tipoSelect = document.getElementById('tipo_accion');
    var campoDestino = document.getElementById('campoDestino');
    var campoPrestamo = document.getElementById('campoPrestamo');

    function sincronizarCampos() {
        var tipo = tipoSelect.value;
        campoDestino.classList.toggle('is-visible', tipo === 'retiro_definitivo');
        campoPrestamo.classList.toggle('is-visible', tipo === 'prestamo');
    }
    tipoSelect.addEventListener('change', sincronizarCampos);
    sincronizarCampos();

    // ---- Búsqueda de funcionario ----
    var funcionarioInput = document.getElementById('buscarFuncionario');
    var funcionarioResultados = document.getElementById('resultadosFuncionario');
    var datosFunc = document.getElementById('datosFuncionario');
    var activosFunc = document.getElementById('activosFuncionario');
    var avisoPrestamo = document.getElementById('avisoPrestamo');
    var avisoAutorizacion = document.getElementById('avisoAutorizacion');
    var campoAutorizacion = document.getElementById('campoAutorizacion');
    var funcTimer = null;

    funcionarioInput.addEventListener('input', function () {
        clearTimeout(funcTimer);
        var q = this.value.trim();
        if (q.length < 2) { funcionarioResultados.classList.remove('is-open'); return; }
        funcTimer = setTimeout(function () {
            fetch("{{ route('actas.buscar-funcionario') }}?q=" + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var items = data.resultados || [];
                if (!items.length) {
                    funcionarioResultados.innerHTML = '<div class="fnc-picker__empty">Sin resultados.</div>';
                    funcionarioResultados.classList.add('is-open');
                    return;
                }
                funcionarioResultados.innerHTML = items.map(function (f) {
                    return '<button type="button" class="fnc-picker__result" data-func-id="' + f.id + '">'
                        + '<span class="fnc-picker__result-icon"><svg class="fnc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><use href="#fnc-i-user-round"></use></svg></span>'
                        + '<span><span class="fnc-picker__result-name">' + f.nombre + '</span>'
                        + '<br><span class="fnc-picker__result-meta">CC ' + f.cedula + ' · ' + (f.cargo || '') + '</span></span>'
                        + '</button>';
                }).join('');
                funcionarioResultados.classList.add('is-open');
                funcionarioResultados.querySelectorAll('.fnc-picker__result').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var id = parseInt(this.dataset.funcId);
                        var f = items.find(function (x) { return x.id === id; });
                        if (f) seleccionarFuncionario(f);
                    });
                });
            });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#pickerFuncionario')) funcionarioResultados.classList.remove('is-open');
        if (!e.target.closest('.fnc-picker')) resultadosActivoDiv.classList.remove('is-open');
    });

    function seleccionarFuncionario(f) {
        funcionarioSeleccionado = f;
        funcionarioResultados.classList.remove('is-open');
        funcionarioInput.value = f.nombre;
        mostrarError('err_funcionario', false);

        // Auto-rellenar hidden inputs y spans de texto
        var campos = ['cedula','nombre','cargo','area','departamento','ciudad','empresa_funcionario','tipo_vinculacion','seccional','distrito',
                      'empresa_propietaria','fuente_recurso','piso','shortname'];
        var derivados = {
            empresa_propietaria: f.empresa_funcionario || 'FEDERACIÓN',
            fuente_recurso: 'FON1',
            piso: f.area || f.distrito || '',
            shortname: f.nombre ? f.nombre.toLowerCase().replace(/[^a-z]/g, '').slice(0, 12) : ''
        };
        campos.forEach(function (k) {
            var h = document.querySelector('[data-h-func="' + k + '"]');
            var t = document.querySelector('[data-t-func="' + k + '"]');
            var val = f[k] || derivados[k] || '';
            if (h) h.value = val;
            if (t) t.textContent = val || '—';
        });

        // Firmas: quien recibe (hidden inputs y spans)
        var firmas = { dependencia: f.area || '', nombre: f.nombre || '', codigo: f.cedula || '', cargo: f.cargo || '' };
        Object.keys(firmas).forEach(function (k) {
            var h = document.querySelector('[data-h-firma="' + k + '"]');
            var t = document.querySelector('[data-t-firma="' + k + '"]');
            if (h) h.value = firmas[k];
            if (t) t.textContent = firmas[k] || '—';
        });

        datosFunc.style.display = 'block';

        // Insertar el botón editar al final de la tarjeta de datos
        var cardExisting = datosFunc.querySelector('.fnc-data-card');
        if (cardExisting && !cardExisting.querySelector('#btnEditarFunc')) {
            btnEditar.style.marginTop = '10px';
            cardExisting.appendChild(btnEditar);
        }
        avisoPrestamo.classList.remove('is-visible');
        avisoAutorizacion.classList.remove('is-visible');
        campoAutorizacion.classList.remove('is-visible');

        if (f.computador) {
            if (f.computador.estado === 'reparacion') {
                avisoPrestamo.classList.add('is-visible');
                if (tipoSelect.value === 'inventario_fisico') { tipoSelect.value = 'prestamo'; sincronizarCampos(); }
            } else {
                avisoAutorizacion.classList.add('is-visible');
                campoAutorizacion.classList.add('is-visible');
            }
        }

        // Activos del funcionario
        if (f.activos && f.activos.length > 0) {
            var html = '<div class="fnc-label mb-2">Activos asignados (' + f.activos.length + ')</div>';
            html += '<table class="fnc-items" style="font-size:.82rem"><thead><tr><th>Activo</th><th>Estado</th></tr></thead><tbody>';
            f.activos.forEach(function (a) {
                var estColor = a.estado === 'en_operacion' ? '#16a34a' : a.estado === 'reparacion' ? '#d97706' : '#2563eb';
                html += '<tr><td><span style="font-weight:650">' + a.nombre + '</span> <span style="font-size:.72rem;color:var(--fnc-text-3)">' + a.tipo + '</span></td>'
                    + '<td><span style="font-size:.76rem;font-weight:600;color:' + estColor + '">' + a.estado.replace(/_/g, ' ') + '</span></td></tr>';
            });
            html += '</tbody></table>';
            activosFunc.innerHTML = html;
        } else {
            activosFunc.innerHTML = '';
        }

        var btnVer = document.getElementById('btnVerActivosFunc');
        if (btnVer) btnVer.onclick = abrirModalActivos;
    }

    // ---- Drawer: editar datos del responsable ----
    var drawer = document.getElementById('drawerEditar');
    var drawerOverlay = document.getElementById('drawerOverlay');
    var edCampos = ['ced','nom','cargo','area','dep','ciud','prop','emp','frec','vinc','piso','short','secc','dist'];
    var edMap = { ced:'cedula', nom:'nombre', cargo:'cargo', area:'area', dep:'departamento', ciud:'ciudad',
                  prop:'empresa_propietaria', emp:'empresa_funcionario', frec:'fuente_recurso',
                  vinc:'tipo_vinculacion', piso:'piso', short:'shortname', secc:'seccional', dist:'distrito' };

    function abrirDrawer() {
        if (!funcionarioSeleccionado) return;
        // Rellenar el drawer con los valores actuales
        edCampos.forEach(function (k) {
            var campo = edMap[k];
            var input = document.getElementById('ed_' + k);
            if (!input) return;
            var actual = $('[data-h-func="' + campo + '"]') ? $('[data-h-func="' + campo + '"]').value : '';
            if (input.tagName === 'SELECT') {
                // Buscar la option que coincida (case-insensitive) o añadir una
                var found = false;
                for (var i = 0; i < input.options.length; i++) {
                    if (input.options[i].value.toUpperCase() === (actual || '').toUpperCase()) {
                        input.selectedIndex = i; found = true; break;
                    }
                }
                if (!found && actual) {
                    var opt = document.createElement('option');
                    opt.value = actual; opt.textContent = actual; opt.selected = true;
                    input.appendChild(opt);
                }
            } else {
                input.value = actual || '';
            }
        });
        document.getElementById('drawerSubtitulo').textContent = funcionarioSeleccionado.nombre + ' · CC ' + funcionarioSeleccionado.cedula;
        drawer.classList.add('is-open');
        drawerOverlay.classList.add('is-open');
    }

    function cerrarDrawer() {
        drawer.classList.remove('is-open');
        drawerOverlay.classList.remove('is-open');
    }

    function guardarDrawer() {
        // Doble check 1: confirmar con el usuario
        var cambios = [];
        edCampos.forEach(function (k) {
            var input = document.getElementById('ed_' + k);
            var campo = edMap[k];
            var actual = $('[data-h-func="' + campo + '"]') ? $('[data-h-func="' + campo + '"]').value : '';
            var nuevo = input ? input.value : '';
            if (actual !== nuevo) cambios.push(edMap[k] + ': "' + actual + '" → "' + nuevo + '"');
        });

        if (cambios.length === 0) { cerrarDrawer(); return; }

        var resumen = cambios.length + ' cambio(s):\n\n' + cambios.join('\n') + '\n\n¿Confirmar?';
        if (!confirm(resumen)) return;

        // Doble check 2: el nombre no puede quedar vacío
        if (!document.getElementById('ed_nom').value.trim()) {
            alert('El nombre del responsable no puede quedar vacío.');
            document.getElementById('ed_nom').focus();
            return;
        }

        // Aplicar los cambios a los hidden inputs y tarjetas de texto
        edCampos.forEach(function (k) {
            var campo = edMap[k];
            var input = document.getElementById('ed_' + k);
            var val = input ? input.value : '';
            var h = document.querySelector('[data-h-func="' + campo + '"]');
            var t = document.querySelector('[data-t-func="' + campo + '"]');
            if (h) h.value = val;
            if (t) t.textContent = val || '—';
        });

        // Actualizar las firmas de quien recibe
        var nuevos = {
            dependencia: document.querySelector('[data-h-func="area"]').value,
            nombre: document.querySelector('[data-h-func="nombre"]').value,
            codigo: document.querySelector('[data-h-func="cedula"]').value,
            cargo: document.querySelector('[data-h-func="cargo"]').value
        };
        Object.keys(nuevos).forEach(function (k) {
            var h = document.querySelector('[data-h-firma="' + k + '"]');
            var t = document.querySelector('[data-t-firma="' + k + '"]');
            if (h) h.value = nuevos[k];
            if (t) t.textContent = nuevos[k] || '—';
        });

        // Actualizar la variable del funcionario
        if (funcionarioSeleccionado) {
            funcionarioSeleccionado.nombre = document.querySelector('[data-h-func="nombre"]').value;
            funcionarioSeleccionado.cargo = document.querySelector('[data-h-func="cargo"]').value;
            funcionarioSeleccionado.area = document.querySelector('[data-h-func="area"]').value;
        }

        cerrarDrawer();
    }

    // Inyectar botón "Editar" dentro de la tarjeta de datos
    var btnEditar = document.createElement('button');
    btnEditar.type = 'button';
    btnEditar.className = 'fnc-data-edit-btn';
    btnEditar.id = 'btnEditarFunc';
    btnEditar.innerHTML = '<svg class="fnc-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><use href="#fnc-i-pencil"></use></svg> Editar';
    btnEditar.addEventListener('click', abrirDrawer);

    document.getElementById('btnCerrarDrawer').addEventListener('click', cerrarDrawer);
    document.getElementById('btnCancelarDrawer').addEventListener('click', cerrarDrawer);
    document.getElementById('btnGuardarDrawer').addEventListener('click', guardarDrawer);
    drawerOverlay.addEventListener('click', cerrarDrawer);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer.classList.contains('is-open')) cerrarDrawer();
    });

    // ---- Modal de activos ----
    function abrirModalActivos() {
        var f = funcionarioSeleccionado;
        if (!f) return;
        document.getElementById('modalActivosNombre').textContent = f.nombre;
        var cuerpo = document.getElementById('modalActivosCuerpo');
        var activos = f.activos || [];
        if (activos.length === 0) {
            cuerpo.innerHTML = '<tr><td colspan="2" style="text-align:center;padding:20px;color:var(--fnc-text-3)">Sin activos asignados</td></tr>';
        } else {
            cuerpo.innerHTML = activos.map(function (a) {
                var estColor = a.estado === 'en_operacion' ? '#16a34a' : a.estado === 'reparacion' ? '#d97706' : '#2563eb';
                return '<tr><td><span style="font-weight:650">' + a.nombre + '</span></td>'
                    + '<td><span style="font-size:.78rem;font-weight:600;color:' + estColor + '">' + a.estado.replace(/_/g, ' ') + '</span></td></tr>';
            }).join('');
        }
        new bootstrap.Modal(document.getElementById('modalActivosFunc')).show();
    }

    // ---- Búsqueda de activos ----
    var buscarInput = document.getElementById('buscarActivo');
    var resultadosActivoDiv = document.getElementById('resultadosActivo');
    var tablaDiv = document.getElementById('tablaActivos');
    var cuerpoTabla = document.getElementById('cuerpoTablaActivos');
    var sinActivos = document.getElementById('sinActivos');
    var contador = document.getElementById('contadorActivos');
    var contadorNum = document.getElementById('contadorNumero');
    var form = document.getElementById('formActa');

    function buscarActivos() {
        var q = buscarInput.value.trim();
        var tipo = tipoSelect.value;
        fetch("{{ route('actas.buscar') }}?q=" + encodeURIComponent(q) + "&tipo=" + encodeURIComponent(tipo), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) { renderResultados(data.resultados || []); });
    }

    function renderResultados(items) {
        if (!items.length) {
            resultadosActivoDiv.innerHTML = '<div class="fnc-picker__empty">Sin resultados en el estado requerido.</div>';
            resultadosActivoDiv.classList.add('is-open');
            return;
        }
        resultadosActivoDiv.innerHTML = items.map(function (e) {
            var ya = seleccionados.some(function (s) { return s.id === e.id; });
            return '<button type="button" class="fnc-picker__result" data-id="' + e.id + '" style="' + (ya ? 'opacity:0.4;pointer-events:none' : '') + '">'
                + '<span class="fnc-picker__result-icon"><svg class="fnc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><use href="#fnc-i-monitor"></use></svg></span>'
                + '<span><span class="fnc-picker__result-name">' + e.nombre + '</span>'
                + '<br><span class="fnc-picker__result-meta">' + (e.tipo || '') + ' · ' + (e.serial || '') + '</span></span>'
                + '</button>';
        }).join('');
        resultadosActivoDiv.classList.add('is-open');
        resultadosActivoDiv.querySelectorAll('.fnc-picker__result').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(this.dataset.id);
                var item = items.find(function (e) { return e.id === id; });
                if (item && !seleccionados.some(function (s) { return s.id === id; })) {
                    seleccionados.push(item); renderTabla();
                    buscarInput.value = ''; resultadosActivoDiv.classList.remove('is-open');
                    mostrarError('err_activos', false);
                }
            });
        });
    }

    buscarInput.addEventListener('input', function () {
        clearTimeout(busquedaTimer); busquedaTimer = setTimeout(buscarActivos, 250);
    });
    buscarInput.addEventListener('focus', buscarActivos);

    function renderTabla() {
        var hay = seleccionados.length > 0;
        tablaDiv.style.display = hay ? 'block' : 'none';
        sinActivos.style.display = hay ? 'none' : 'block';
        contador.style.display = hay ? 'inline-flex' : 'none';
        contadorNum.textContent = seleccionados.length;

        cuerpoTabla.innerHTML = seleccionados.map(function (e) {
            return '<tr>'
                + '<td><div class="fnc-items__id">'
                + '<span class="fnc-items__icon"><svg class="fnc-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><use href="#fnc-i-monitor"></use></svg></span>'
                + '<div class="fnc-items__body">'
                + '<div class="fnc-items__name">' + e.nombre + '</div>'
                + '<div class="fnc-items__serial">' + (e.serial || '') + ' · ' + (e.placa || '') + '</div>'
                + '<div class="fnc-items__marca">' + (e.marca || '') + ' ' + (e.modelo || '') + '</div>'
                + '</div></div></td>'
                + '<td class="fnc-items__field"><select class="fnc-input--cell" name="ficha[' + e.id + '][estado_uso]" required aria-label="Estado de uso">'
                + ['FUNCIONES PROPIAS','SALA DE SISTEMAS / LABORATORIO','PRUEBAS','MULTIUSUARIO','TEMPORAL / PRESTAMO','OBSOLETO / ALMACENADO'].map(function (op) {
                    var sel = (e.estado_uso === op || (!e.estado_uso && op === 'FUNCIONES PROPIAS')) ? ' selected' : '';
                    return '<option value="' + op + '"' + sel + '>' + op + '</option>';
                }).join('')
                + '</select></td>'
                + '<td class="fnc-items__field"><select class="fnc-input--cell" name="ficha[' + e.id + '][administrado]"><option value=""></option><option value="ADMINISTRADO"' + (e.administrado === 'ADMINISTRADO' ? ' selected' : '') + '>Administrado</option><option value="CONTROLADO"' + (e.administrado === 'CONTROLADO' ? ' selected' : '') + '>Controlado</option></select></td>'
                + '<td class="fnc-items__field"><select class="fnc-input--cell" name="ficha[' + e.id + '][tipo_propiedad]" required aria-label="Tipo de propiedad">'
                + '<option value="">Seleccionar…</option>'
                + ['PROPIO','LEASING','ALMACAFÉ','FEDERACIÓN','FONDO NACIONAL','BANCOLOMBIA','PROYECTO - GOBERNACION DEL TOLIMA'].map(function (op) {
                    var sel = (e.tipo_propiedad || '').toUpperCase().includes(op.toUpperCase()) ? ' selected' : '';
                    return '<option value="' + op + '"' + sel + '>' + op + '</option>';
                }).join('')
                + '</select></td>'
                + '<td class="fnc-items__field"><input type="text" class="fnc-input--cell" name="ficha[' + e.id + '][orden_remision]" placeholder="N/A" value="' + (e.orden_remision || '') + '"></td>'
                + '<td class="fnc-items__field"><input type="text" class="fnc-input--cell" name="ficha[' + e.id + '][observaciones]" placeholder="Nota…" value="' + (e.observaciones || '') + '"></td>'
                + '<td><button type="button" class="fnc-items__remove" data-id="' + e.id + '" title="Quitar activo" aria-label="Quitar ' + e.nombre + '"><svg class="fnc-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><use href="#fnc-i-x"></use></svg></button></td>'
                + '</tr>';
        }).join('');

        cuerpoTabla.querySelectorAll('.fnc-items__remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(this.dataset.id);
                seleccionados = seleccionados.filter(function (s) { return s.id !== id; });
                renderTabla();
            });
        });

        form.querySelectorAll('input[name="equipos[]"]').forEach(function (inp) { inp.remove(); });
        seleccionados.forEach(function (e) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'equipos[]'; inp.value = e.id;
            form.appendChild(inp);
        });
    }

    // ---- Resumen del paso 4 ----
    function renderResumen() {
        var tipo = tipoSelect.options[tipoSelect.selectedIndex].text;
        var fecha = document.getElementById('fecha_accion').value;
        var ubic = document.getElementById('ubicacion').value;
        var f = funcionarioSeleccionado;
        var destino = document.getElementById('destino');
        var destTxt = campoDestino.classList.contains('is-visible') && destino
            ? destino.options[destino.selectedIndex].text : null;

        var html = '<div class="fnc-acta-campos">';
        html += '<div class="fnc-campo"><div class="fnc-review-label">Tipo de novedad</div><div class="fnc-review-value">' + tipo + '</div></div>';
        html += '<div class="fnc-campo"><div class="fnc-review-label">Fecha</div><div class="fnc-review-value">' + fecha + '</div></div>';
        if (ubic) html += '<div class="fnc-campo"><div class="fnc-review-label">Ubicación</div><div class="fnc-review-value">' + ubic + '</div></div>';
        if (destTxt) html += '<div class="fnc-campo"><div class="fnc-review-label">Destino</div><div class="fnc-review-value">' + destTxt + '</div></div>';
        if (f) {
            html += '<div class="fnc-campo"><div class="fnc-review-label">Responsable</div><div class="fnc-review-value">' + f.nombre + ' · CC ' + f.cedula + '</div></div>';
            html += '<div class="fnc-campo"><div class="fnc-review-label">Cargo</div><div class="fnc-review-value">' + (f.cargo || '—') + '</div></div>';
        }
        html += '</div>';

        if (seleccionados.length > 0) {
            html += '<div class="fnc-label mt-3 mb-2">Activos (' + seleccionados.length + ')</div>';
            html += '<table class="fnc-items"><thead><tr><th>Activo</th><th>Serial</th></tr></thead><tbody>';
            seleccionados.forEach(function (e) {
                html += '<tr><td style="font-weight:650">' + e.nombre + '</td><td>' + (e.serial || '') + '</td></tr>';
            });
            html += '</tbody></table>';
        }

        document.getElementById('resumenMovimiento').innerHTML = html;
    }

    // ---- Submit: validar paso 4 antes de enviar ----
    form.addEventListener('submit', function (e) {
        if (!validarPaso(1) || !validarPaso(2) || !validarPaso(3)) {
            e.preventDefault();
            irAPaso(!funcionarioSeleccionado ? 2 : (seleccionados.length === 0 ? 3 : 1));
            return;
        }
        // Validar autorización si aplica
        if (campoAutorizacion.classList.contains('is-visible')) {
            var pdf = document.getElementById('autorizacion_pdf');
            if (!pdf.files.length) {
                e.preventDefault();
                mostrarError('err_autorizacion', true);
            }
        }
    });

    // ---- Validación on-blur inicial ----
    validarOnBlur('tipo_accion', 'err_tipo', function () { return !!tipoSelect.value; });
    validarOnBlur('fecha_accion', 'err_fecha', function () { return !!document.getElementById('fecha_accion').value; });
    validarOnBlur('fecha_inicio', 'err_inicio', function () { return !!document.getElementById('fecha_inicio').value; });
    validarOnBlur('fecha_fin_probable', 'err_fin', function () { return !!document.getElementById('fecha_fin_probable').value; });
});
</script>
@endpush
@endsection
