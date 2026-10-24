@extends('layouts.inventario')

@section('title', 'Catálogos CMDB')

@section('content')
<div class="container-fluid py-4">
    <x-ui.toolbar
        title="Catálogos"
        icon="list-check"
        subtitle="Edite las listas del CMDB sin tocar código: todos los selects del sistema las leen en vivo. Si el formato cambia, actualice aquí."
    />

    {{-- Pestañas por lista --}}
    <ul class="nav nav-pills mb-3">
        @foreach($listas as $l)
            <li class="nav-item">
                <a class="nav-link {{ $l['key'] === $lista ? 'active' : '' }}"
                   href="{{ route('catalogos.index', ['lista' => $l['key']]) }}">
                    {{ $l['titulo'] }}
                    <span class="badge text-bg-secondary ms-1">{{ $conteos[$l['key']] }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    @can('catalogos.gestionar')
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-primary btn-sm" data-abrir-crear>
                <i class="bi bi-plus-lg me-1"></i> Agregar valor
            </button>
        </div>
    @endcan

    <x-ui.filter-container action="{{ route('catalogos.index', ['lista' => $lista]) }}">
        <div class="col-12 col-lg-5">
            <label class="form-label fw-medium small mb-1">Buscar</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Buscar en {{ mb_strtolower($def['titulo']) }}..." autocomplete="off">
            </div>
        </div>
    </x-ui.filter-container>

    <x-ui.card noPadding="true">
        <x-ui.table>
            <x-slot:head>
                <tr>
                    @foreach($def['campos'] as $campo => $info)
                        <th class="{{ $loop->first ? 'ps-4' : '' }}">{{ $info[0] }}</th>
                    @endforeach
                    <th class="text-end pe-4">Acciones</th>
                </tr>
            </x-slot:head>

            @forelse($filas as $fila)
                <tr>
                    @foreach($def['campos'] as $campo => $info)
                        <td class="{{ $loop->first ? 'ps-4' : '' }}">
                            @if($campo === 'activo' || $campo === 'es_computador' || $campo === 'es_catalogo_cmdb')
                                <x-fnc.pill :tone="$fila->{$campo} ? 'success' : 'slate'" dot>{{ $fila->{$campo} ? 'Sí' : 'No' }}</x-fnc.pill>
                            @elseif($campo === 'descripcion')
                                <span class="fnc-udash__sub d-block text-truncate" style="max-width: 380px;" title="{{ $fila->{$campo} }}">{{ $fila->{$campo} ?: '—' }}</span>
                            @else
                                <span class="fnc-udash__name">{{ $fila->{$campo} }}</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="text-end pe-4 text-nowrap">
                        @can('catalogos.gestionar')
                            <button type="button" class="btn btn-sm btn-primary me-1"
                                    data-editar
                                    data-id="{{ $fila->id }}"
                                    @foreach($def['campos'] as $campo => $i) data-{{ $campo }}="{{ $fila->{$campo} }}" @endforeach
                                    title="Editar">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <form method="POST" action="{{ route('catalogos.toggle', ['lista' => $lista, 'id' => $fila->id, '_tab' => request('_tab')]) }}" class="d-inline"
                                  onsubmit="{{ in_array('activo', array_keys($def['campos']), true) ? "return confirm('¿Cambiar el estado activo de este valor?')" : "return confirm('¿Retirar este valor del catálogo? (se rechazará si está en uso por activos)')" }};">
                                @csrf
                                @method(in_array('activo', array_keys($def['campos']), true) ? 'POST' : 'POST')
                                <button type="submit" class="btn btn-sm {{ (in_array('activo', array_keys($def['campos']), true) ? !$fila->activo : false) ? 'btn-outline-success' : 'btn-outline-danger' }}"
                                        title="{{ in_array('activo', array_keys($def['campos']), true) ? ($fila->activo ? 'Desactivar' : 'Activar') : 'Retirar del catálogo' }}">
                                    <i class="bi {{ (in_array('activo', array_keys($def['campos']), true) ? !$fila->activo : false) ? 'bi-check-lg' : 'bi-slash-circle' }}"></i>
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($def['campos']) + 1 }}" class="text-center py-5 text-muted">
                        <i class="bi bi-list-check fs-2 d-block mb-2 opacity-50"></i>
                        No hay valores con estos filtros.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>

    <div class="text-muted small mt-2">
        <i class="bi bi-info-circle me-1"></i>
        Los valores en uso por activos no se borran (solo se desactivan/retiran si no hay referencias).
        Las ediciones aplican de inmediato en formularios, actas, correcciones e importador.
    </div>
</div>

{{-- ── Modal crear/editar (campos según la lista) ───────────────────── --}}
<div class="modal fade" id="modalCatalogo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" id="formCatalogo" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="catalogoTitulo">{{ $def['titulo'] }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body row g-3" id="catalogoCampos">
                {{-- Los inputs los genera el JS según la lista activa --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var LISTA = @json($lista);
    var CAMPOS = @json(collect($def['campos'])->map(fn ($c) => ['etiqueta' => $c[0], 'regla' => $c[1]])->all());
    var TAB = @json(request('_tab'));
    var modal = null;

    function inputDe(campo, def, valor) {
        var es = def.regla || '';
        var id = 'cat_' + campo;
        var valorStr = (valor === null || valor === undefined) ? '' : String(valor);

        if (es.indexOf('boolean') !== -1) {
            var sel = valorStr === '1' || valorStr.toLowerCase() === 'true' ? ' selected' : '';
            var selNo = sel ? '' : ' selected';
            return '<div class="col-12"><label class="form-label fw-medium" for="' + id + '">' + def.etiqueta + '</label>' +
                '<select class="form-select" id="' + id + '" name="' + campo + '">' +
                '<option value="1"' + sel + '>Sí</option><option value="0"' + selNo + '>No</option></select></div>';
        }
        if (campo === 'descripcion') {
            return '<div class="col-12"><label class="form-label fw-medium" for="' + id + '">' + def.etiqueta + '</label>' +
                '<textarea class="form-control" id="' + id + '" name="' + campo + '" rows="2">' + valorStr + '</textarea></div>';
        }
        return '<div class="col-12"><label class="form-label fw-medium" for="' + id + '">' + def.etiqueta + '</label>' +
            '<input type="text" class="form-control" id="' + id + '" name="' + campo + '" value="' + valorStr.replace(/"/g, '"') + '"></div>';
    }

    function abrir(fila) {
        var form = document.getElementById('formCatalogo');
        var campos = document.getElementById('catalogoCampos');
        var html = '';

        Object.keys(CAMPOS).forEach(function (campo) {
            var valor = fila ? (fila.getAttribute('data-' + campo) || '') : '';
            // inputs con valor "1"/"0" llegan como atributos data-*
            html += inputDe(campo, CAMPOS[campo], valor);
        });

        campos.innerHTML = html;
        document.getElementById('catalogoTitulo').textContent = @json($def['titulo']) + (fila ? ' — editar' : ' — nuevo valor');

        var qs = TAB ? '?_tab=' + encodeURIComponent(TAB) : '';
        form.action = fila
            ? '/catalogos/' + LISTA + '/' + fila.getAttribute('data-id') + qs
            : '/catalogos/' + LISTA + qs;
        form.method = 'POST';

        // POST para crear; PUT para editar (spoofing de método de Laravel)
        form.querySelector('input[name="_method"]')?.remove();
        if (fila) {
            var m = document.createElement('input');
            m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
            form.prepend(m);
        }

        if (!modal) modal = new bootstrap.Modal('#modalCatalogo');
        modal.show();
    }

    document.addEventListener('click', function (ev) {
        var crear = ev.target.closest('[data-abrir-crear]');
        var editar = ev.target.closest('[data-editar]');
        if (crear) abrir(null);
        if (editar) abrir(editar);
    });
})();
</script>
@endpush
@endsection
