@extends('layouts.inventario')

@section('title', 'Actas Firmadas')

@section('content')
<x-ui.toolbar 
    title="Actas Firmadas" 
    icon="file-earmark-pdf text-danger" 
>
    <x-ui.button type="button" color="success" outline="true" class="text-nowrap" icon="file-earmark-zip" onclick="submitZipForm();" text="Descargar ZIP" />
    <x-ui.button type="button" color="primary" class="text-nowrap" data-bs-toggle="modal" data-bs-target="#uploadModal" icon="upload" text="Subir Acta Firmada" />
</x-ui.toolbar>

<!-- Filtros -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route('actas-firmadas.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Funcionario que firma</label>
                <input type="text" name="numero_acta" class="form-control" value="{{ request('numero_acta') }}" placeholder="Nombre del funcionario...">
            </div>
            <div class="col-md-4">
                <label class="form-label">Fecha del Documento</label>
                <input type="date" name="fecha_documento" class="form-control" value="{{ request('fecha_documento') }}">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-search me-2"></i>Buscar</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <form id="formDescargaZip" action="{{ route('actas-firmadas.zip') }}" method="POST" class="d-none">
                @csrf
            </form>
            <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th style="width: 40px;" class="text-center">
                                <input class="form-check-input select-all-actas" type="checkbox" id="selectAllActas">
                            </th>
                            <th>Funcionario que firma</th>
                            <th>Fecha Documento</th>
                            <th>Subido Por</th>
                        <th>Observaciones</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($actas as $acta)
                        <tr>
                            <td class="text-center">
                                <input class="form-check-input acta-checkbox" type="checkbox" name="actas_ids[]" value="{{ $acta->id }}">
                            </td>
                            <td class="fw-bold text-nowrap">{{ $acta->numero_acta }}</td>
                            <td class="text-nowrap">{{ $acta->fecha_documento->format('d/m/Y') }}</td>
                            <td class="text-nowrap">{{ $acta->user->name ?? 'Sistema' }}</td>
                            <td><small class="text-muted">{{ Str::limit($acta->observaciones, 50) }}</small></td>
                            <td class="text-center text-nowrap">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('actas-firmadas.show-file', $acta->id) }}" target="_blank" class="btn btn-outline-success" title="Ver Acta">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('actas-firmadas.download', $acta->id) }}" class="btn btn-outline-primary" title="Descargar PDF Actual">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-warning" title="Reemplazar PDF" onclick="openReplaceModal({{ $acta->id }}, '{{ $acta->numero_acta }}')">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                    <a href="{{ route('actas-firmadas.history', $acta->id) }}" class="btn btn-outline-info" title="Historial de Versiones" style="border-top-right-radius: var(--bs-border-radius-sm); border-bottom-right-radius: var(--bs-border-radius-sm);">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>No hay actas firmadas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($actas->hasPages())
        <div class="card-footer bg-white border-0 pt-3">
            {{ $actas->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

<!-- Modal Upload -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('actas-firmadas.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="uploadModalLabel">Subir Acta Firmada</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label text-danger">Archivo PDF *</label>
                <input type="file" name="archivo_pdf" class="form-control" accept=".pdf" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Funcionario que firma *</label>
                <input type="text" name="numero_acta" class="form-control" placeholder="Ej. Juan Pérez" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Fecha del Documento *</label>
                <input type="date" name="fecha_documento" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload me-1"></i>Guardar Acta</button>
          </div>
        </div>
    </form>
  </div>
</div>

<!-- Modal Replace -->
<div class="modal fade" id="replaceModal" tabindex="-1" aria-labelledby="replaceModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="replaceForm" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="replaceModalLabel">Reemplazar Acta Firmada</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Se reemplazará el archivo para el acta de: <strong id="replaceNumActa"></strong></p>
            <p class="text-muted small">El PDF actual se guardará en el historial como una versión anterior.</p>
            
            <div class="mb-3">
                <label class="form-label text-danger">Nuevo Archivo PDF *</label>
                <input type="file" name="archivo_pdf" class="form-control" accept=".pdf" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-danger">Motivo del Cambio *</label>
                <textarea name="motivo_cambio" class="form-control" rows="3" placeholder="Ej. El acta anterior no tenía la firma del jefe..." required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-repeat me-1"></i>Reemplazar</button>
          </div>
        </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
    function openReplaceModal(id, numero) {
        document.getElementById('replaceNumActa').innerText = numero;
        document.getElementById('replaceForm').action = '/actas-firmadas/' + id;
        new bootstrap.Modal(document.getElementById('replaceModal')).show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Lógica para seleccionar todas las actas
        const selectAllCheckbox = document.getElementById('selectAllActas');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.acta-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
            });
        }
    });

    function submitZipForm() {
        const checkedBoxes = document.querySelectorAll('.acta-checkbox:checked');
        if (checkedBoxes.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Selecciona al menos un acta para descargar.'
            });
            return;
        }

        const form = document.getElementById('formDescargaZip');
        
        // Remove old hidden inputs if any
        form.querySelectorAll('.acta-hidden-input').forEach(el => el.remove());

        // Create new hidden inputs for each selected acta
        checkedBoxes.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'actas_ids[]';
            input.value = cb.value;
            input.className = 'acta-hidden-input';
            form.appendChild(input);
        });

        form.submit();
    }
</script>
@endpush

