@extends('layouts.inventario')

@section('title', 'Nueva acta de novedad')

@push('styles')
<style>
    .fnc-acta-section { margin-bottom: 16px; }
    .fnc-acta-section .fnc-panel__body { padding: 18px 20px; }
    .fnc-acta-destino, .fnc-acta-prestamo { display: none; }
    .fnc-acta-destino.is-visible, .fnc-acta-prestamo.is-visible { display: block; }
</style>
@endpush

@section('content')
<x-ui.toolbar title="Nueva acta de novedad de activos" icon="clipboard-check" />

<form method="POST" action="{{ route('actas.store') }}" id="formActa">
    @csrf
    <input type="hidden" name="return_to" value="{{ $returnTo }}">

    {{-- ==================== Movimiento ==================== --}}
    <div class="fnc-acta-section">
        <x-fnc.panel title="Movimiento" icon="arrow-right-left">
            <div class="fnc-acta-campos">
                <div class="fnc-campo">
                    <label for="tipo_accion">Tipo de novedad *</label>
                    <select name="tipo_accion" id="tipo_accion" class="fnc-input" required>
                        @foreach ($tipos as $codigo => $etiqueta)
                            <option value="{{ $codigo }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fnc-campo">
                    <label for="ubicacion">Ubicación</label>
                    <input type="text" name="ubicacion" id="ubicacion" class="fnc-input" placeholder="Ibagué" value="{{ old('ubicacion') }}">
                </div>
                <div class="fnc-campo">
                    <label for="fecha_accion">Fecha</label>
                    <input type="date" name="fecha_accion" id="fecha_accion" class="fnc-input" value="{{ old('fecha_accion', now()->format('Y-m-d')) }}">
                </div>
                <div class="fnc-campo fnc-acta-destino" id="campoDestino">
                    <label for="destino">Destino</label>
                    <select name="destino" id="destino" class="fnc-input">
                        <option value="disponible">Disponible (devolver al pool)</option>
                        <option value="baja">Baja (disposición final)</option>
                    </select>
                </div>
            </div>
            <div class="fnc-acta-prestamo mt-3" id="campoPrestamo">
                <div class="fnc-acta-campos">
                    <div class="fnc-campo">
                        <label for="fecha_inicio">Fecha de inicio *</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="fnc-input" value="{{ old('fecha_inicio') }}">
                    </div>
                    <div class="fnc-campo">
                        <label for="fecha_fin_probable">Fecha probable de fin *</label>
                        <input type="date" name="fecha_fin_probable" id="fecha_fin_probable" class="fnc-input" value="{{ old('fecha_fin_probable') }}">
                    </div>
                </div>
            </div>
        </x-fnc.panel>
    </div>

    {{-- ==================== Responsable ==================== --}}
    <div class="fnc-acta-section">
        <x-fnc.panel title="Responsable del acta" icon="user-round" sub="Snapshot del CMDB (columnas 1-14)">
            <div class="fnc-acta-campos">
                <div class="fnc-campo">
                    <label>Cédula</label>
                    <input type="text" name="usuario_cedula" class="fnc-input" value="{{ old('usuario_cedula') }}" placeholder="C.C." data-snapshot="cedula">
                </div>
                <div class="fnc-campo">
                    <label>Nombres y apellidos</label>
                    <input type="text" name="usuario_nombre" class="fnc-input" value="{{ old('usuario_nombre') }}" placeholder="Nombre completo" data-snapshot="nombre">
                </div>
                <div class="fnc-campo">
                    <label>Empresa propietaria</label>
                    <input type="text" name="usuario_empresa_propietaria" class="fnc-input" value="{{ old('usuario_empresa_propietaria') }}" placeholder="FEDERACIÓN / FONDO NACIONAL / ALMACAFÉ">
                </div>
                <div class="fnc-campo">
                    <label>Dependencia</label>
                    <input type="text" name="usuario_dependencia" class="fnc-input" value="{{ old('usuario_dependencia') }}" placeholder="COMITÉ TOLIMA">
                </div>
                <div class="fnc-campo">
                    <label>Fuente de recurso</label>
                    <input type="text" name="usuario_fuente_recurso" class="fnc-input" value="{{ old('usuario_fuente_recurso') }}" placeholder="FON1 / FON2">
                </div>
                <div class="fnc-campo">
                    <label>Empresa del funcionario</label>
                    <input type="text" name="usuario_empresa_funcionario" class="fnc-input" value="{{ old('usuario_empresa_funcionario') }}" placeholder="FEDERACIÓN / TIVIT">
                </div>
                <div class="fnc-campo">
                    <label>Tipo de vinculación</label>
                    <select name="usuario_tipo_vinculacion" class="fnc-input">
                        <option value="EMPLEADO" {{ old('usuario_tipo_vinculacion') === 'EMPLEADO' ? 'selected' : '' }}>Empleado</option>
                        <option value="CONTRATISTA" {{ old('usuario_tipo_vinculacion') === 'CONTRATISTA' ? 'selected' : '' }}>Contratista</option>
                        <option value="APRENDIZ" {{ old('usuario_tipo_vinculacion') === 'APRENDIZ' ? 'selected' : '' }}>Aprendiz</option>
                    </select>
                </div>
                <div class="fnc-campo">
                    <label>Cargo</label>
                    <input type="text" name="usuario_cargo" class="fnc-input" value="{{ old('usuario_cargo') }}" placeholder="Cargo del funcionario">
                </div>
                <div class="fnc-campo">
                    <label>Área</label>
                    <input type="text" name="usuario_area" class="fnc-input" value="{{ old('usuario_area') }}" placeholder="Área o dependencia">
                </div>
                <div class="fnc-campo">
                    <label>Ciudad</label>
                    <input type="text" name="usuario_ciudad" class="fnc-input" value="{{ old('usuario_ciudad') }}" placeholder="Ibagué">
                </div>
                <div class="fnc-campo">
                    <label>Departamento</label>
                    <input type="text" name="usuario_departamento" class="fnc-input" value="{{ old('usuario_departamento') }}" placeholder="TOLIMA">
                </div>
                <div class="fnc-campo">
                    <label>Ubicación y piso</label>
                    <input type="text" name="usuario_piso" class="fnc-input" value="{{ old('usuario_piso') }}" placeholder="Piso / ubicación">
                </div>
                <div class="fnc-campo">
                    <label>Distrito</label>
                    <input type="text" name="usuario_distrito" class="fnc-input" value="{{ old('usuario_distrito') }}">
                </div>
                <div class="fnc-campo">
                    <label>Seccional</label>
                    <input type="text" name="usuario_seccional" class="fnc-input" value="{{ old('usuario_seccional') }}">
                </div>
                <div class="fnc-campo">
                    <label>Shortname</label>
                    <input type="text" name="usuario_shortname" class="fnc-input" value="{{ old('usuario_shortname') }}" placeholder="Usuario de red">
                </div>
            </div>
        </x-fnc.panel>
    </div>

    {{-- ==================== Activos del acta ==================== --}}
    <div class="fnc-acta-section">
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
                           placeholder="Buscar activo por nombre, serial, placa, marca o tipo…"
                           autocomplete="off">
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
                            <th class="fnc-items__field">Checklist TI</th>
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

    {{-- ==================== Firmas ==================== --}}
    <div class="fnc-acta-section">
        <x-fnc.panel title="Firmas del acta" icon="pen-line" sub="Doble bloque del formato FE-BS-F-0069">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fnc-label mb-3">Quien entrega</h6>
                    <div class="fnc-acta-campos">
                        <div class="fnc-campo">
                            <label>Dependencia</label>
                            <input type="text" name="entregador_dependencia" class="fnc-input" value="{{ old('entregador_dependencia') }}" placeholder="TIC">
                        </div>
                        <div class="fnc-campo">
                            <label>Nombre</label>
                            <input type="text" name="entregador_nombre" class="fnc-input" value="{{ old('entregador_nombre') }}" placeholder="Nombre y firma de quien entrega">
                        </div>
                        <div class="fnc-campo">
                            <label>Código</label>
                            <input type="text" name="entregador_codigo" class="fnc-input" value="{{ old('entregador_codigo') }}" placeholder="Cod. Personal">
                        </div>
                        <div class="fnc-campo">
                            <label>Cargo</label>
                            <input type="text" name="entregador_cargo" class="fnc-input" value="{{ old('entregador_cargo') }}" placeholder="Cargo">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="fnc-label mb-3">Quien recibe</h6>
                    <div class="fnc-acta-campos">
                        <div class="fnc-campo">
                            <label>Dependencia</label>
                            <input type="text" name="recibe_dependencia" class="fnc-input" value="{{ old('recibe_dependencia') }}" placeholder="Dependencia">
                        </div>
                        <div class="fnc-campo">
                            <label>Nombre</label>
                            <input type="text" name="recibe_nombre" class="fnc-input" value="{{ old('recibe_nombre') }}" placeholder="Nombre y firma de quien recibe">
                        </div>
                        <div class="fnc-campo">
                            <label>Código</label>
                            <input type="text" name="recibe_codigo" class="fnc-input" value="{{ old('recibe_codigo') }}" placeholder="Cod. Personal">
                        </div>
                        <div class="fnc-campo">
                            <label>Cargo</label>
                            <input type="text" name="recibe_cargo" class="fnc-input" value="{{ old('recibe_cargo') }}" placeholder="Cargo">
                        </div>
                    </div>
                </div>
            </div>
        </x-fnc.panel>
    </div>

    {{-- ==================== Observaciones ==================== --}}
    <div class="fnc-acta-section">
        <x-fnc.panel title="Observaciones" icon="message-square">
            <textarea name="observaciones" class="fnc-input" rows="3" placeholder="Observaciones del acta…">{{ old('observaciones') }}</textarea>
        </x-fnc.panel>
    </div>

    {{-- ==================== Acciones ==================== --}}
    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="{{ $returnTo }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" id="btnGuardarActa">
            <i class="bi bi-clipboard-check me-1"></i> Registrar acta
        </button>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var REGLAS = @json($reglas);
    var seleccionados = [];
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
        buscar();
    });
    sincronizarCampos();

    // ---- Buscador de activos ----
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
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.fnc-picker')) resultadosDiv.classList.remove('is-open');
    });

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
                + '<td class="fnc-items__field"><input type="text" class="fnc-input--cell" name="ficha[' + e.id + '][estado_uso]" placeholder="Funciones propias"></td>'
                + '<td class="fnc-items__field"><select class="fnc-input--cell" name="ficha[' + e.id + '][administrado]"><option value=""></option><option value="ADMINISTRADO">Administrado</option><option value="CONTROLADO">Controlado</option></select></td>'
                + '<td class="fnc-items__field"><input type="text" class="fnc-input--cell" name="ficha[' + e.id + '][tipo_propiedad]" placeholder="Leasing / Propio"></td>'
                + '<td class="fnc-items__field"><input type="text" class="fnc-input--cell" name="ficha[' + e.id + '][orden_remision]" placeholder="N/A"></td>'
                + '<td class="fnc-items__field"><input type="text" class="fnc-input--cell" name="ficha[' + e.id + '][checklist_responsable]" placeholder="Responsable TI"></td>'
                + '<td><button type="button" class="fnc-items__remove" data-id="' + e.id + '" title="Quitar activo"><svg class="fnc-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><use href="#fnc-i-x"></use></svg></button></td>'
                + '</tr>';
        }).join('');

        // Botones de quitar
        cuerpoTabla.querySelectorAll('.fnc-items__remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(this.dataset.id);
                seleccionados = seleccionados.filter(function (s) { return s.id !== id; });
                renderTabla();
            });
        });

        // Inputs ocultos con los IDs
        form.querySelectorAll('input[name="equipos[]"]').forEach(function (inp) { inp.remove(); });
        seleccionados.forEach(function (e) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'equipos[]';
            inp.value = e.id;
            form.appendChild(inp);
        });
    }

    // Validación antes de enviar
    form.addEventListener('submit', function (e) {
        if (seleccionados.length === 0) {
            e.preventDefault();
            alert('El acta debe incluir al menos un activo. Busca y agrega activos desde el buscador.');
            buscarInput.focus();
        }
    });
});
</script>
@endpush
@endsection
