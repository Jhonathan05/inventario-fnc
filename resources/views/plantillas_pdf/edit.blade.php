@extends('layouts.inventario')

@section('title', 'Editar Plantilla PDF')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-pencil me-2 text-warning"></i>Editar Plantilla PDF
    </h4>
    <a href="{{ route('plantillas-pdf.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="{{ route('plantillas-pdf.update', $plantillasPdf) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="nombre" class="form-label fw-medium">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nombre" id="nombre"
                                   class="form-control @error('nombre') is-invalid @enderror"
                                   value="{{ old('nombre', $plantillasPdf->nombre) }}"
                                   required maxlength="150">
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="tipo" class="form-label fw-medium">Tipo</label>
                            <select name="tipo" id="tipo" class="form-select">
                                @foreach($tipos as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('tipo', $plantillasPdf->tipo) === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input type="hidden" name="activa" value="0">
                                <input class="form-check-input" type="checkbox"
                                       name="activa" id="activa" value="1"
                                       {{ old('activa', $plantillasPdf->activa) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="activa">
                                    Marcar como plantilla activa
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="contenido" class="form-label fw-medium">
                                Contenido <span class="text-danger">*</span>
                            </label>
                            <textarea name="contenido" id="contenido" rows="20"
                                      class="form-control font-monospace @error('contenido') is-invalid @enderror"
                                      required>{{ old('contenido', $plantillasPdf->contenido) }}</textarea>
                            @error('contenido')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning text-white">
                            <i class="bi bi-check-lg me-1"></i>Actualizar Plantilla
                        </button>
                        <a href="{{ route('plantillas-pdf.index') }}" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm sticky-top config-sticky-card">
            <div class="card-header bg-info bg-opacity-10 fw-semibold border-0 py-3">
                <i class="bi bi-braces me-2 text-info"></i>Variables Disponibles
            </div>
            <div class="card-body p-0">
                <div class="config-scrollable-500">
                    <table class="table table-sm table-hover mb-0">
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
    textarea.value = textarea.value.substring(0, start) + variable + textarea.value.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + variable.length;
    textarea.focus();
}
</script>
@endpush

