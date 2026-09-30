@extends('layouts.inventario')

@section('title', 'Nueva Plantilla PDF')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-plus-circle me-2 text-primary"></i>Nueva Plantilla PDF
    </h4>
    <a href="{{ route('plantillas-pdf.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="row g-4">
    {{-- Formulario --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="{{ route('plantillas-pdf.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="nombre" class="form-label fw-medium">
                                Nombre de la Plantilla <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nombre" id="nombre"
                                   class="form-control @error('nombre') is-invalid @enderror"
                                   value="{{ old('nombre') }}"
                                   placeholder="Ej: Acta de Entrega Estándar" required maxlength="150">
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="tipo" class="form-label fw-medium">
                                Tipo <span class="text-danger">*</span>
                            </label>
                            <select name="tipo" id="tipo"
                                    class="form-select @error('tipo') is-invalid @enderror" required>
                                @foreach($tipos as $key => $label)
                                    <option value="{{ $key }}" {{ old('tipo') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input type="hidden" name="activa" value="0">
                                <input class="form-check-input" type="checkbox"
                                       name="activa" id="activa" value="1"
                                       {{ old('activa') ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="activa">
                                    Marcar como plantilla activa
                                    <small class="text-muted">(desactivará otras del mismo tipo)</small>
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="contenido" class="form-label fw-medium">
                                Contenido de la Plantilla <span class="text-danger">*</span>
                                <small class="text-muted ms-2">
                                    Use las variables de la columna derecha para datos dinámicos
                                </small>
                            </label>
                            <textarea name="contenido" id="contenido" rows="20"
                                      class="form-control font-monospace @error('contenido') is-invalid @enderror"
                                      placeholder="Escriba el contenido HTML de la plantilla con variables @{{nombre_equipo}}, @{{nombre_usuario}}, etc."
                                      required>{{ old('contenido') }}</textarea>
                            @error('contenido')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Guardar Plantilla
                        </button>
                        <a href="{{ route('plantillas-pdf.index') }}" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Variables disponibles --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm sticky-top config-sticky-card">
            <div class="card-header bg-info bg-opacity-10 fw-semibold border-0 py-3">
                <i class="bi bi-braces me-2 text-info"></i>Variables Disponibles
            </div>
            <div class="card-body p-0">
                <div class="config-scrollable-500">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="small">Variable</th>
                                <th class="small">Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($variables as $var => $desc)
                            <tr>
                                <td>
                                    <code class="small config-code-clickable"
                                          onclick="insertar('{{ $var }}')"
                                          title="Click para insertar">{{ $var }}</code>
                                </td>
                                <td class="small text-muted">{{ $desc }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function insertar(variable) {
    const textarea = document.getElementById('contenido');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + variable + text.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + variable.length;
    textarea.focus();
}
</script>
@endpush

