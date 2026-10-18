@extends('layouts.inventario')

@section('title', 'Nueva acta de novedad')

@push('styles')
<style>
    .fnc-acta-section { margin-bottom: 16px; }
    .fnc-acta-section .fnc-panel__body { padding: 18px 20px; }
    /* Movimiento y Responsable una frente a la otra */
    .fnc-acta-dual {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr);
        gap: 16px;
        align-items: stretch;
    }
    .fnc-acta-dual .fnc-acta-section { margin-bottom: 0; }
    @media (max-width: 991.98px) { .fnc-acta-dual { grid-template-columns: minmax(0, 1fr); } }
    /* Campos de movimiento: llenan el ancho del contenedor */
    .fnc-campo--half { grid-column: span 6; }
    @media (max-width: 575.98px) { .fnc-campo--half { grid-column: 1 / -1; } }
    .fnc-acta-destino, .fnc-acta-prestamo { display: none; }
    .fnc-acta-destino.is-visible, .fnc-acta-prestamo.is-visible { display: block; }
    .fnc-funcionario-info { display: none; }
    .fnc-funcionario-info.is-visible { display: block; }
    .fnc-func-warning { display: none; padding: 10px 14px; border-radius: 10px; font-size: .8rem; margin: 12px 0 20px 0; }
    .fnc-func-warning.is-visible { display: block; }
    .fnc-func-warning--prestamo { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .fnc-func-warning--autorizacion { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .fnc-autorizacion { display: none; }
    .fnc-autorizacion.is-visible { display: block; }
    /* backdrop-filter en .fnc-panel crea un contexto de apilamiento que
       encierra el dropdown; esta sección necesita z-index mayor que la
       siguiente para que el picker se vea encima. */
    .fnc-acta-section--responsable { position: relative; z-index: 20; }
    .fnc-acta-section--activos { position: relative; z-index: 1; }
    /* Modal por encima del backdrop y de cualquier elemento del shell */
    .modal-backdrop { z-index: 1050 !important; }
    .modal { z-index: 1055 !important; }
    .modal-dialog { z-index: 1060; }
    /* Sin scrollbar visible (scroll de overlay) */
    html { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar { display: none; width: 0; height: 0; }
    body::-webkit-scrollbar { display: none; }
</style>
@endpush

@section('content')
<x-ui.toolbar title="Nueva acta de novedad de activos" icon="clipboard-check" />

<form method="POST" action="{{ route('actas.store') }}" id="formActa" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="return_to" value="{{ $returnTo }}">

    {{-- ======== Movimiento y responsable (sección unificada) ======== --}}
    <div class="fnc-acta-section fnc-acta-section--responsable" style="position:relative;z-index:20">
        <x-fnc.panel title="Movimiento y responsable del acta" icon="clipboard-check" sub="Define el movimiento y busca al funcionario en el maestro">
            <div class="fnc-acta-campos">
                <div class="fnc-campo fnc-campo--half" style="grid-column: 1 / -1">
                    <label for="tipo_accion">Tipo de novedad *</label>
                    <select name="tipo_accion" id="tipo_accion" class="fnc-input" required>
                        @foreach ($tipos as $codigo => $etiqueta)
                            <option value="{{ $codigo }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fnc-campo fnc-campo--half">
                    <label for="ubicacion">Ubicación</label>
                    <input type="text" name="ubicacion" id="ubicacion" class="fnc-input" value="Ibagué">
                </div>
                <div class="fnc-campo fnc-campo--half">
                    <label for="fecha_accion">Fecha</label>
                    <input type="date" name="fecha_accion" id="fecha_accion" class="fnc-input" value="{{ old('fecha_accion', now()->format('Y-m-d')) }}">
                </div>
                <div class="fnc-campo fnc-acta-destino fnc-campo--half" id="campoDestino">
                    <label for="destino">Destino</label>
                    <select name="destino" id="destino" class="fnc-input">
                        <option value="disponible">Disponible (devolver al pool)</option>
                        <option value="baja">Baja (disposición final)</option>
                    </select>
                </div>
            </div>
            <div class="fnc-acta-prestamo mt-2" id="campoPrestamo">
                <div class="fnc-acta-campos">
                    <div class="fnc-campo fnc-campo--half">
                        <label for="fecha_inicio">Fecha de inicio *</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="fnc-input" value="{{ old('fecha_inicio') }}">
                    </div>
                    <div class="fnc-campo fnc-campo--half">
                        <label for="fecha_fin_probable">Fecha probable de fin *</label>
                        <input type="date" name="fecha_fin_probable" id="fecha_fin_probable" class="fnc-input" value="{{ old('fecha_fin_probable') }}">
                    </div>
                </div>
            </div>

            <div class="fnc-picker mt-3" id="pickerFuncionario" style="position:relative;z-index:21">
                <div class="fnc-picker__search">
                    <x-fnc.icon name="search" :size="18" />
                    <input type="text" id="buscarFuncionario" class="fnc-picker__input"
                           placeholder="Buscar funcionario por cédula o nombre…" autocomplete="off">
                </div>
                <div class="fnc-picker__results" id="resultadosFuncionario"></div>
            </div>
        </x-fnc.panel>
    </div>

    {{-- ======== Datos del funcionario seleccionado (sección independiente) ======== --}}
    <div class="fnc-acta-section fnc-funcionario-info" id="infoFuncionario" style="margin-top:16px">
        <x-fnc.panel title="Datos del responsable" icon="user-round" sub="Auto-rellenados desde el maestro de funcionarios">
            {{-- Activos actuales del funcionario --}}
            <div id="activosFuncionario" class="mb-3"></div>

            {{-- Avisos de la regla de un computador --}}
            <div class="fnc-func-warning fnc-func-warning--prestamo" id="avisoPrestamo">
                ⚠️ El funcionario tiene un computador en <strong>reparación</strong>.
                Registre este movimiento como <strong>Préstamo</strong> (no como Inventario Físico).
            </div>
            <div class="fnc-func-warning fnc-func-warning--autorizacion" id="avisoAutorizacion">
                🔒 El funcionario ya tiene un computador <strong>activo</strong>.
                Para asignar otro computador debe adjuntar la <strong>autorización del jefe superior</strong> (PDF).
                <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="btnVerActivosFunc"
                        style="font-size:.76rem;padding:3px 12px;border-radius:8px">
                    Ver activos asignados
                </button>
            </div>

            {{-- Campos auto-rellenados (sólo lectura) --}}
            <div class="fnc-acta-campos">
                <div class="fnc-campo fnc-campo--xs"><label>Cédula</label><input type="text" name="usuario_cedula" class="fnc-input" readonly data-func="cedula"></div>
                <div class="fnc-campo fnc-campo--lg"><label>Nombres y apellidos</label><input type="text" name="usuario_nombre" class="fnc-input" readonly data-func="nombre"></div>
                <div class="fnc-campo fnc-campo--md"><label>Cargo</label><input type="text" name="usuario_cargo" class="fnc-input" readonly data-func="cargo"></div>
                <div class="fnc-campo fnc-campo--md"><label>Área</label><input type="text" name="usuario_area" class="fnc-input" readonly data-func="area"></div>
                <div class="fnc-campo fnc-campo--md"><label>Departamento</label><input type="text" name="usuario_departamento" class="fnc-input" readonly data-func="departamento"></div>
                <div class="fnc-campo fnc-campo--sm"><label>Ciudad</label><input type="text" name="usuario_ciudad" class="fnc-input" readonly data-func="ciudad"></div>
                <div class="fnc-campo fnc-campo--md"><label>Empresa funcionario</label><input type="text" name="usuario_empresa_funcionario" class="fnc-input" readonly data-func="empresa_funcionario"></div>
                <div class="fnc-campo fnc-campo--sm"><label>Tipo vinculación</label><input type="text" name="usuario_tipo_vinculacion" class="fnc-input" readonly data-func="tipo_vinculacion"></div>
                <div class="fnc-campo fnc-campo--sm"><label>Seccional</label><input type="text" name="usuario_seccional" class="fnc-input" readonly data-func="seccional"></div>
                <div class="fnc-campo fnc-campo--sm"><label>Distrito</label><input type="text" name="usuario_distrito" class="fnc-input" readonly data-func="distrito"></div>
            </div>

            {{-- Campos editables (no están en el maestro) --}}
            <div class="fnc-acta-campos mt-3">
                <div class="fnc-campo fnc-campo--md">
                    <label>Empresa propietaria</label>
                    <select name="usuario_empresa_propietaria" class="fnc-input">
                        <option value="FEDERACIÓN">Federación</option>
                        <option value="FONDO NACIONAL">Fondo Nacional</option>
                        <option value="ALMACAFÉ">Almacafé</option>
                        <option value="BANCOLOMBIA">Bancolombia</option>
                    </select>
                </div>
                <div class="fnc-campo fnc-campo--sm">
                    <label>Fuente de recurso</label>
                    <select name="usuario_fuente_recurso" class="fnc-input">
                        <option value="FON1">FON1</option>
                        <option value="FON2">FON2</option>
                        <option value="FEPC">FEPC</option>
                    </select>
                </div>
                <div class="fnc-campo fnc-campo--xs">
                    <label>Ubicación y piso</label>
                    <input type="text" name="usuario_piso" class="fnc-input" placeholder="Piso / ubicación">
                </div>
                <div class="fnc-campo fnc-campo--sm">
                    <label>Shortname</label>
                    <input type="text" name="usuario_shortname" class="fnc-input" placeholder="Usuario de red">
                </div>
            </div>
        </x-fnc.panel>
    </div>

    {{-- ==================== Autorización del jefe superior (condicional) ==================== --}}
    <div class="fnc-acta-section fnc-autorizacion" id="campoAutorizacion">
        <x-fnc.panel title="Autorización del jefe superior" icon="file-check" sub="Requerida para asignar un segundo computador a un funcionario que ya tiene uno activo">
            <div class="fnc-acta-campos">
                <div class="fnc-campo fnc-campo--lg">
                    <label>Documento PDF *</label>
                    <input type="file" name="autorizacion_pdf" accept=".pdf" class="fnc-input" style="padding:6px">
                    <small class="text-muted">Adjunte el PDF firmado por el jefe superior.</small>
                </div>
                <div class="fnc-campo fnc-campo--md">
                    <label>Comentario</label>
                    <input type="text" name="autorizacion_comentario" class="fnc-input" placeholder="Nombre del jefe que autoriza">
                </div>
            </div>
        </x-fnc.panel>
    </div>

    {{-- ==================== Activos del acta ==================== --}}
    <div class="fnc-acta-section fnc-acta-section--activos">
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
                           placeholder="Buscar activo por nombre, serial, placa, marca o tipo…" autocomplete="off">
                </div>
                <div class="fnc-picker__results" id="resultadosActivo"></div>
            </div>

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
    </div>

    {{-- ==================== Firmas (auto-rellenadas) ==================== --}}
    <div class="fnc-acta-section">
        <x-fnc.panel title="Firmas del acta" icon="pen-line" sub="Doble bloque del formato FE-BS-F-0069 — se rellenan automáticamente">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fnc-label mb-3">Quien entrega</h6>
                    <div class="fnc-acta-campos">
                        <div class="fnc-campo fnc-campo--lg">
                            <label>Dependencia</label>
                            <input type="text" name="entregador_dependencia" class="fnc-input" value="TIC" readonly>
                        </div>
                        <div class="fnc-campo fnc-campo--xs">
                            <label>Código</label>
                            <input type="text" name="entregador_codigo" class="fnc-input" value="{{ auth()->id() }}" readonly>
                        </div>
                        <div class="fnc-campo fnc-campo--md">
                            <label>Cargo</label>
                            <input type="text" name="entregador_cargo" class="fnc-input" value="{{ auth()->user()->roles->first()?->name ?? 'Analista TIC' }}" readonly>
                        </div>
                        <div class="fnc-campo fnc-campo--xl" style="grid-column: 1 / -1">
                            <label>Nombre y firma</label>
                            <input type="text" name="entregador_nombre" class="fnc-input" value="{{ auth()->user()->name }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="fnc-label mb-3">Quien recibe</h6>
                    <div class="fnc-acta-campos">
                        <div class="fnc-campo fnc-campo--lg">
                            <label>Dependencia</label>
                            <input type="text" name="recibe_dependencia" class="fnc-input" readonly data-firma="dependencia">
                        </div>
                        <div class="fnc-campo fnc-campo--xs">
                            <label>Código</label>
                            <input type="text" name="recibe_codigo" class="fnc-input" readonly data-firma="codigo">
                        </div>
                        <div class="fnc-campo fnc-campo--md">
                            <label>Cargo</label>
                            <input type="text" name="recibe_cargo" class="fnc-input" readonly data-firma="cargo">
                        </div>
                        <div class="fnc-campo fnc-campo--xl" style="grid-column: 1 / -1">
                            <label>Nombre y firma</label>
                            <input type="text" name="recibe_nombre" class="fnc-input" readonly data-firma="nombre">
                        </div>
                    </div>
                </div>
            </div>
        </x-fnc.panel>
    </div>

    {{-- ==================== Observaciones ==================== --}}
    <div class="fnc-acta-section">
        <x-fnc.panel title="Observaciones" icon="message-square">
            <textarea name="observaciones" class="fnc-input" rows="4" style="width:100%;resize:vertical;min-height:80px" placeholder="Observaciones del acta…">{{ old('observaciones') }}</textarea>
        </x-fnc.panel>
    </div>

    {{-- ==================== Acciones ==================== --}}
    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="{{ $returnTo }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" id="btnGuardarActa">
            <i class="bi bi-clipboard-check me-1"></i> Registrar acta y actualizar CMDB
        </button>
    </div>
</form>

{{-- Modal: activos asignados al funcionario — fuera del panel (backdrop-filter
     crea un contexto de apilamiento que lo encierra) y fuera del form --}}
<div class="modal fade" id="modalActivosFunc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:1px solid var(--fnc-hairline)">
            <div class="modal-header" style="border-bottom:1px solid var(--fnc-hairline)">
                <h5 class="modal-title" style="font-size:.95rem;font-weight:700">
                    Activos de <span id="modalActivosNombre"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:16px">
                <table class="fnc-items">
                    <thead>
                        <tr>
                            <th style="min-width:200px">Activo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
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
    var seleccionados = [];
    var funcionarioSeleccionado = null;
    var busquedaTimer = null;
    var tipoSelect = document.getElementById('tipo_accion');
    var buscarInput = document.getElementById('buscarActivo');
    var resultadosDiv = document.getElementById('resultadosActivo');
    var tablaDiv = document.getElementById('tablaActivos');
    var cuerpoTabla = document.getElementById('cuerpoTablaActivos');
    var sinActivos = document.getElementById('sinActivos');
    var contador = document.getElementById('contadorActivos');
    var contadorNum = document.getElementById('contadorNumero');
    var form = document.getElementById('formActa');
    var campoDestino = document.getElementById('campoDestino');
    var campoPrestamo = document.getElementById('campoPrestamo');
    var infoFunc = document.getElementById('infoFuncionario');
    var avisoPrestamo = document.getElementById('avisoPrestamo');
    var avisoAutorizacion = document.getElementById('avisoAutorizacion');
    var campoAutorizacion = document.getElementById('campoAutorizacion');
    var activosFunc = document.getElementById('activosFuncionario');
    var funcionarioInput = document.getElementById('buscarFuncionario');
    var funcionarioResultados = document.getElementById('resultadosFuncionario');

    // ---- Utilidades ----
    function $(sel) { return document.querySelector(sel); }

    function rellenarFuncionario(f) {
        funcionarioSeleccionado = f;
        $('[data-func="cedula"]').value = f.cedula || '';
        $('[data-func="nombre"]').value = f.nombre || '';
        $('[data-func="cargo"]').value = f.cargo || '';
        $('[data-func="area"]').value = f.area || '';
        $('[data-func="departamento"]').value = f.departamento || '';
        $('[data-func="ciudad"]').value = f.ciudad || '';
        $('[data-func="empresa_funcionario"]').value = f.empresa_funcionario || '';
        $('[data-func="tipo_vinculacion"]').value = f.tipo_vinculacion || '';
        $('[data-func="seccional"]').value = f.seccional || '';
        $('[data-func="distrito"]').value = f.distrito || '';

        // Firmas: quien recibe = el funcionario
        $('[data-firma="dependencia"]').value = f.area || '';
        $('[data-firma="nombre"]').value = f.nombre || '';
        $('[data-firma="codigo"]').value = f.cedula || '';
        $('[data-firma="cargo"]').value = f.cargo || '';

        infoFunc.classList.add('is-visible');
        funcionarioResultados.classList.remove('is-open');
        funcionarioInput.value = f.nombre;

        // Guardar los activos para el modal
        window._activosFuncionario = f.activos || [];
        window._nombreFuncionario = f.nombre;
        var btnVerActivos = document.getElementById('btnVerActivosFunc');
        if (btnVerActivos) {
            btnVerActivos.onclick = function () {
                abrirModalActivos();
            };
        }

        // Avisos de computador
        avisoPrestamo.classList.remove('is-visible');
        avisoAutorizacion.classList.remove('is-visible');
        campoAutorizacion.classList.remove('is-visible');

        if (f.computador) {
            if (f.computador.estado === 'reparacion') {
                avisoPrestamo.classList.add('is-visible');
                if (tipoSelect.value === 'inventario_fisico') {
                    tipoSelect.value = 'prestamo';
                    sincronizarCampos();
                }
            } else {
                avisoAutorizacion.classList.add('is-visible');
                campoAutorizacion.classList.add('is-visible');
            }
        }

        // Cargar activos del funcionario desde la respuesta de buscarFuncionario
        if (f.activos && f.activos.length > 0) {
            var html = '<div class="fnc-label mb-2">Activos asignados (' + f.activos.length + ')</div>';
            html += '<table class="fnc-items" style="font-size:.82rem">';
            html += '<thead><tr><th>Activo</th><th>Estado</th></tr></thead><tbody>';
            f.activos.forEach(function (a) {
                var estColor = a.estado === 'en_operacion' ? 'var(--fnc-success)'
                    : a.estado === 'reparacion' ? 'var(--fnc-warning)'
                    : a.estado === 'prestamo' ? 'var(--fnc-info)'
                    : 'var(--fnc-text-3)';
                var iconoComp = a.es_computador
                    ? ' <span style="display:inline-flex;align-items:center;gap:2px;font-size:.64rem;color:var(--fnc-brand);font-weight:700">💻</span>'
                    : '';
                html += '<tr>'
                    + '<td><div style="display:flex;align-items:center;gap:8px">'
                    + '<span style="font-weight:650">' + a.nombre + '</span>' + iconoComp
                    + '<span style="font-size:.7rem;color:var(--fnc-text-3)">' + (a.tipo || '') + '</span>'
                    + '</div></td>'
                    + '<td><span style="font-size:.74rem;font-weight:600;color:' + estColor + '">' + a.estado.replace(/_/g, ' ') + '</span></td>'
                    + '</tr>';
            });
            html += '</tbody></table>';
            activosFunc.innerHTML = html;
        } else {
            activosFunc.innerHTML = '<div class="fnc-label mb-1" style="color:var(--fnc-text-3);font-weight:500">Sin activos asignados</div>';
        }
    }

    // ---- Búsqueda de funcionario ----
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
                    funcionarioResultados.innerHTML = '<div class="fnc-picker__empty">Sin resultados. Verifique la cédula o el nombre.</div>';
                    funcionarioResultados.classList.add('is-open');
                    return;
                }
                funcionarioResultados.innerHTML = items.map(function (f) {
                    return '<button type="button" class="fnc-picker__result" data-func-id="' + f.id + '">'
                        + '<span class="fnc-picker__result-icon"><svg class="fnc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><use href="#fnc-i-user-round"></use></svg></span>'
                        + '<span><span class="fnc-picker__result-name">' + f.nombre + '</span>'
                        + '<br><span class="fnc-picker__result-meta">CC ' + f.cedula + ' · ' + (f.cargo || '') + ' · ' + (f.area || '') + '</span></span>'
                        + '</button>';
                }).join('');
                funcionarioResultados.classList.add('is-open');

                funcionarioResultados.querySelectorAll('.fnc-picker__result').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var id = parseInt(this.dataset.funcId);
                        var f = items.find(function (x) { return x.id === id; });
                        if (f) rellenarFuncionario(f);
                    });
                });
            });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#pickerFuncionario')) funcionarioResultados.classList.remove('is-open');
        if (!e.target.closest('.fnc-picker')) resultadosDiv.classList.remove('is-open');
    });

    // ---- Tipo de movimiento ----
    function estadoRequerido() {
        var tipo = tipoSelect.value;
        return (REGLAS[tipo] || {}).requiere || ['disponible'];
    }

    function sincronizarCampos() {
        var tipo = tipoSelect.value;
        campoDestino.classList.toggle('is-visible', tipo === 'retiro_definitivo');
        campoPrestamo.classList.toggle('is-visible', tipo === 'prestamo');
    }

    tipoSelect.addEventListener('change', function () {
        sincronizarCampos();
        // Si hay aviso de préstamo y el usuario cambia a inventario_fisico, mostrar el aviso
        if (funcionarioSeleccionado && funcionarioSeleccionado.computador) {
            if (funcionarioSeleccionado.computador.estado === 'reparacion' && tipoSelect.value === 'inventario_fisico') {
                avisoPrestamo.classList.add('is-visible');
            } else if (funcionarioSeleccionado.computador.estado === 'reparacion') {
                avisoPrestamo.classList.remove('is-visible');
            }
        }
        buscar();
    });
    sincronizarCampos();

    // ---- Búsqueda de activos ----
    function buscar() {
        var q = buscarInput.value.trim();
        var tipo = tipoSelect.value;
        fetch("{{ route('actas.buscar') }}?q=" + encodeURIComponent(q) + "&tipo=" + encodeURIComponent(tipo), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) { renderResultados(data.resultados || []); })
        .catch(function () { resultadosDiv.classList.remove('is-open'); });
    }

    function renderResultados(items) {
        if (!items.length) {
            resultadosDiv.innerHTML = '<div class="fnc-picker__empty">Sin resultados en el estado requerido por este movimiento.</div>';
            resultadosDiv.classList.add('is-open');
            return;
        }
        var html = items.map(function (e) {
            var yaSeleccionado = seleccionados.some(function (s) { return s.id === e.id; });
            var opacidad = yaSeleccionado ? 'opacity:0.4;pointer-events:none' : '';
            return '<button type="button" class="fnc-picker__result" data-id="' + e.id + '" style="' + opacidad + '">'
                + '<span class="fnc-picker__result-icon"><svg class="fnc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><use href="#fnc-i-monitor"></use></svg></span>'
                + '<span><span class="fnc-picker__result-name">' + e.nombre + '</span>'
                + '<br><span class="fnc-picker__result-meta">' + (e.tipo || 'N/A') + ' · Serial: ' + (e.serial || 'N/A') + ' · Placa: ' + (e.placa || 'N/A') + '</span></span>'
                + '</button>';
        }).join('');
        resultadosDiv.innerHTML = html;
        resultadosDiv.classList.add('is-open');

        resultadosDiv.querySelectorAll('.fnc-picker__result').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(this.dataset.id);
                var item = items.find(function (e) { return e.id === id; });
                if (item && !seleccionados.some(function (s) { return s.id === id; })) {
                    seleccionados.push(item);
                    renderTabla();
                    buscarInput.value = '';
                    resultadosDiv.classList.remove('is-open');
                    buscarInput.focus();
                }
            });
        });
    }

    buscarInput.addEventListener('input', function () {
        clearTimeout(busquedaTimer);
        busquedaTimer = setTimeout(buscar, 250);
    });
    buscarInput.addEventListener('focus', buscar);

    // ---- Tabla de seleccionados ----
    function renderTabla() {
        var hayItems = seleccionados.length > 0;
        tablaDiv.style.display = hayItems ? 'block' : 'none';
        sinActivos.style.display = hayItems ? 'none' : 'block';
        contador.style.display = hayItems ? 'inline-flex' : 'none';
        contadorNum.textContent = seleccionados.length;

        cuerpoTabla.innerHTML = seleccionados.map(function (e) {
            return '<tr>'
                + '<td><div class="fnc-items__id">'
                + '<span class="fnc-items__icon"><svg class="fnc-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><use href="#fnc-i-monitor"></use></svg></span>'
                + '<div class="fnc-items__body">'
                + '<div class="fnc-items__name">' + e.nombre + '</div>'
                + '<div class="fnc-items__serial">Serial: ' + (e.serial || 'N/A') + ' · Placa: ' + (e.placa || 'N/A') + '</div>'
                + '<div class="fnc-items__marca">' + (e.marca || '') + ' ' + (e.modelo || '') + '</div>'
                + '</div></div></td>'
                + '<td class="fnc-items__field"><input type="text" class="fnc-input--cell" name="ficha[' + e.id + '][estado_uso]" placeholder="Funciones propias" value="' + (e.estado_uso || '') + '"></td>'
                + '<td class="fnc-items__field"><select class="fnc-input--cell" name="ficha[' + e.id + '][administrado]"><option value=""></option><option value="ADMINISTRADO"' + (e.administrado === 'ADMINISTRADO' ? ' selected' : '') + '>Administrado</option><option value="CONTROLADO"' + (e.administrado === 'CONTROLADO' ? ' selected' : '') + '>Controlado</option></select></td>'
                + '<td class="fnc-items__field"><input type="text" class="fnc-input--cell" name="ficha[' + e.id + '][tipo_propiedad]" placeholder="Leasing / Propio" value="' + (e.tipo_propiedad || '') + '"></td>'
                + '<td class="fnc-items__field"><input type="text" class="fnc-input--cell" name="ficha[' + e.id + '][orden_remision]" placeholder="N/A" value="' + (e.orden_remision || '') + '"></td>'
                + '<td class="fnc-items__field"><input type="text" class="fnc-input--cell" name="ficha[' + e.id + '][observaciones]" placeholder="Nota del acta…" value="' + (e.observaciones || '') + '"></td>'
                + '<td><button type="button" class="fnc-items__remove" data-id="' + e.id + '" title="Quitar activo"><svg class="fnc-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><use href="#fnc-i-x"></use></svg></button></td>'
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
            inp.type = 'hidden';
            inp.name = 'equipos[]';
            inp.value = e.id;
            form.appendChild(inp);
        });
    }

    // ---- Modal: activos del funcionario ----
    function abrirModalActivos() {
        var nombre = window._nombreFuncionario || '';
        var activos = window._activosFuncionario || [];
        document.getElementById('modalActivosNombre').textContent = nombre;
        var cuerpo = document.getElementById('modalActivosCuerpo');
        if (activos.length === 0) {
            cuerpo.innerHTML = '<tr><td colspan="2" style="text-align:center;padding:20px;color:var(--fnc-text-3)">Sin activos asignados</td></tr>';
        } else {
            cuerpo.innerHTML = activos.map(function (a) {
                var estColor = a.estado === 'en_operacion' ? '#16a34a'
                    : a.estado === 'reparacion' ? '#d97706'
                    : a.estado === 'prestamo' ? '#2563eb'
                    : '#6b7280';
                var compBadge = a.es_computador
                    ? ' <span style="display:inline-flex;padding:1px 6px;border-radius:6px;background:#fee2e2;color:#991b1b;font-size:.62rem;font-weight:700">💻</span>'
                    : '';
                return '<tr>'
                    + '<td><div class="fnc-items__id">'
                    + '<span class="fnc-items__icon"><svg class="fnc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><use href="#fnc-i-monitor"></use></svg></span>'
                    + '<div class="fnc-items__body">'
                    + '<div class="fnc-items__name">' + a.nombre + compBadge + '</div>'
                    + '<div class="fnc-items__serial">' + (a.tipo || '') + ' · ' + (a.marca_modelo || '') + '</div>'
                    + '</div></div></td>'
                    + '<td><span style="font-size:.78rem;font-weight:600;color:' + estColor + '">' + a.estado.replace(/_/g, ' ') + '</span></td>'
                    + '</tr>';
            }).join('');
        }
        var modal = new bootstrap.Modal(document.getElementById('modalActivosFunc'));
        modal.show();
    }

    // ---- Validación antes de enviar ----
    form.addEventListener('submit', function (e) {
        if (seleccionados.length === 0) {
            e.preventDefault();
            alert('El acta debe incluir al menos un activo. Busca y agrega activos desde el buscador.');
            buscarInput.focus();
            return;
        }
        if (!funcionarioSeleccionado) {
            e.preventDefault();
            alert('Debe seleccionar un funcionario del maestro antes de registrar el acta.');
            funcionarioInput.focus();
            return;
        }
    });
});
</script>
@endpush
@endsection
