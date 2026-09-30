@extends('layouts.inventario')

@section('title', 'Tipos de Recurso')

@section('content')
<x-ui.toolbar 
    title="Tipos de Recurso" 
    icon="tags" 
    createRoute="{{ route('tipo-recursos.create') }}" 
    createText="Nuevo Tipo" 
>
    <x-ui.button color="info" class="text-white" data-bs-toggle="modal" data-bs-target="#modalCatalogo" icon="box-seam" text="Catálogo Complementos" />
</x-ui.toolbar>

<x-ui.card noPadding="true">
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th>#</th>
                <th>Nombre</th>
                <th class="text-center">Complementos</th>
                <th class="text-center">N&deg; Equipos</th>
                <th class="text-center">Acciones</th>
            </tr>
        </x-slot>
        
        @forelse($tipoRecursos as $tipo)
            <tr>
                <td class="text-muted small">{{ $tipo->id }}</td>
                <td class="fw-medium">{{ $tipo->nombre }}</td>
                <td class="text-center text-nowrap">
                    <x-ui.badge color="info text-white rounded-pill">{{ $tipo->complementosDefinidos->count() }}</x-ui.badge>
                </td>
                <td class="text-center text-nowrap">
                    <x-ui.badge color="primary rounded-pill">{{ $tipo->equipos_count }}</x-ui.badge>
                </td>
                <td class="text-center text-nowrap">
                    <div class="btn-group btn-group-sm">
                        <x-ui.button href="{{ route('tipo-recursos.edit', $tipo) }}" outline="true" color="warning" icon="pencil" title="Editar" />
                        <x-ui.button type="button" outline="true" color="danger" icon="trash" title="Eliminar"
                                data-delete-url="{{ route('tipo-recursos.destroy', $tipo) }}"
                                data-delete-name="{{ $tipo->nombre }}"
                                data-count="{{ $tipo->equipos_count }}" />
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    No hay tipos de recurso registrados.
                </td>
            </tr>
        @endforelse
    </x-ui.table>
    @if($tipoRecursos->hasPages())
        <div class="card-footer bg-white border-0">
            {{ $tipoRecursos->links('pagination::bootstrap-5') }}
        </div>
    @endif
</x-ui.card>

@endsection

<!-- Modal Catálogo Complementos -->
<x-ui.modal id="modalCatalogo" title="Catálogo de Complementos" size="modal-xl">
    <form id="formCatalogo" action="{{ route('catalogo-complementos.store') }}" method="POST" class="mb-4 bg-light p-3 border rounded shadow-sm">
        @csrf
        <input type="hidden" name="_method" id="methodCatalogo" value="POST">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Complemento</label>
                <input type="text" name="nombre" id="nombreCatalogo" class="form-control form-control-sm" required placeholder="Ej: Cargador, Batería..." oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Compatible con</label>
                <select name="tipo_recursos_ids[]" id="tipoRecursosCatalogo" class="form-select form-select-sm" multiple style="height: 60px;">
                    @foreach(\App\Models\TipoRecurso::orderBy('nombre')->get() as $tr)
                        <option value="{{ $tr->id }}">{{ $tr->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <div class="form-check pt-4">
                    <input class="form-check-input" type="checkbox" name="requiere_serial" value="1" id="reqSerial">
                    <label class="form-check-label small" for="reqSerial">Serial</label>
                </div>
            </div>
            <div class="col-md-1">
                <div class="form-check pt-4">
                    <input class="form-check-input" type="checkbox" name="usa_estado" value="1" id="usaEstado" checked>
                    <label class="form-check-label small" for="usaEstado">Estado</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="activo" value="1" id="activoCatalogo" checked>
                    <label class="form-check-label small" for="activoCatalogo">Activo</label>
                </div>
            </div>
            <div class="col-md-2 text-end pb-1 d-flex flex-column gap-1">
                <x-ui.button type="submit" id="btnSubmitCatalogo" color="info" size="sm" class="text-white w-100" text="Guardar" />
                <x-ui.button type="button" id="btnCancelCatalogo" color="secondary" size="sm" class="w-100 d-none" text="Cancelar" onclick="resetCatalogoForm()" />
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12 text-muted small">
                <i class="bi bi-info-circle me-1"></i> Mantén presionada la tecla Ctrl (o Cmd) para seleccionar múltiples tipos de recurso.
            </div>
        </div>
    </form>

    <x-ui.table class="table-sm table-hover border">
        <x-slot name="head">
            <tr>
                <th>Nombre</th>
                <th>Compatible con</th>
                <th class="text-center">Req. Serial</th>
                <th class="text-center">Usa Estado</th>
                <th class="text-center">Activo</th>
                <th class="text-center">Acciones</th>
            </tr>
        </x-slot>
        
        @forelse($catalogoComplementos ?? [] as $comp)
            <tr>
                <td class="fw-medium">{{ $comp->nombre }}</td>
                <td>
                    @if($comp->tipoRecursos->count() > 0)
                        <span class="text-muted small">{{ $comp->tipoRecursos->pluck('nombre')->join(', ') }}</span>
                    @else
                        <span class="text-danger small">Ninguno</span>
                    @endif
                </td>
                <td class="text-center text-nowrap">
                    @if($comp->requiere_serial)
                        <x-ui.badge color="success">Sí</x-ui.badge>
                    @else
                        <x-ui.badge color="secondary">No</x-ui.badge>
                    @endif
                </td>
                <td class="text-center text-nowrap">
                    @if($comp->usa_estado)
                        <x-ui.badge color="success">Sí</x-ui.badge>
                    @else
                        <x-ui.badge color="secondary">No</x-ui.badge>
                    @endif
                </td>
                <td class="text-center text-nowrap">
                    @if($comp->activo)
                        <x-ui.badge color="primary">Sí</x-ui.badge>
                    @else
                        <x-ui.badge color="danger">No</x-ui.badge>
                    @endif
                </td>
                <td class="text-center text-nowrap">
                    <x-ui.button type="button" size="sm" outline="true" color="warning" icon="pencil" 
                        onclick="editCatalogo({{ $comp->id }}, '{{ $comp->nombre }}', {{ json_encode($comp->tipoRecursos->pluck('id')) }}, {{ $comp->requiere_serial ? 1 : 0 }}, {{ $comp->usa_estado ? 1 : 0 }}, {{ $comp->activo ? 1 : 0 }})" />
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted">No hay complementos en el catálogo</td></tr>
        @endforelse
    </x-ui.table>
    
    <x-slot name="footer">
        <x-ui.button type="button" color="secondary" data-bs-dismiss="modal" text="Cerrar" />
    </x-slot>
</x-ui.modal>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-control { min-height: 38px; padding: 6px 12px; }
    .ts-control > input { padding: 0 !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    let tomSelectInstance;
    document.addEventListener('DOMContentLoaded', function() {
        tomSelectInstance = new TomSelect('#tipoRecursosCatalogo', {
            plugins: ['remove_button'],
            placeholder: 'Selecciona tipos de recurso...',
            searchField: ['text'],
            hideSelected: true
        });
    });

    const urlStore = "{{ route('catalogo-complementos.store') }}";
    function editCatalogo(id, nombre, tipoRecursos, reqSerial, usaEstado, activo) {
        document.getElementById('formCatalogo').action = `/catalogo-complementos/${id}`;
        document.getElementById('methodCatalogo').value = 'PUT';
        document.getElementById('nombreCatalogo').value = nombre;
        document.getElementById('reqSerial').checked = reqSerial === 1;
        document.getElementById('usaEstado').checked = usaEstado === 1;
        document.getElementById('activoCatalogo').checked = activo === 1;
        
        if (tomSelectInstance) {
            tomSelectInstance.setValue(tipoRecursos);
        }

        document.getElementById('btnSubmitCatalogo').textContent = 'Actualizar';
        document.getElementById('btnCancelCatalogo').classList.remove('d-none');
    }

    function resetCatalogoForm() {
        document.getElementById('formCatalogo').action = urlStore;
        document.getElementById('methodCatalogo').value = 'POST';
        document.getElementById('formCatalogo').reset();
        
        if (tomSelectInstance) {
            tomSelectInstance.clear();
        }

        document.getElementById('btnSubmitCatalogo').textContent = 'Guardar';
        document.getElementById('btnCancelCatalogo').classList.add('d-none');
    }
</script>
@endpush
