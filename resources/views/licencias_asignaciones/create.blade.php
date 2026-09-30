@extends('layouts.inventario')

@section('title', 'Nueva Asignación de Licencia')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-link-45deg text-primary me-2"></i>Asignar Licencia
    </h4>
    <a href="{{ route('licencia-asignaciones.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('licencia-asignaciones.store') }}">
            @csrf
            
            <div class="row g-4">
                <div class="col-md-12">
                    <label class="form-label fw-medium">Licencia <span class="text-danger">*</span></label>
                    <select name="licencia_id" class="form-select @error('licencia_id') is-invalid @enderror" required>
                        <option value="">Seleccione una licencia...</option>
                        @foreach($licencias as $licencia)
                            <option value="{{ $licencia->id }}" {{ old('licencia_id') == $licencia->id ? 'selected' : '' }} {{ $licencia->cupos_disponibles <= 0 ? 'disabled' : '' }}>
                                {{ $licencia->nombre }} (Cupos disponibles: {{ $licencia->cupos_disponibles }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text text-primary mt-1">
                        <i class="bi bi-info-circle me-1"></i> El sistema asignará automáticamente el primer serial disponible (si aplica).
                    </div>
                    @error('licencia_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Funcionario <span class="text-danger">*</span></label>
                    <select name="funcionario_id" id="funcionario_id" class="form-select @error('funcionario_id') is-invalid @enderror" required>
                        <option value="" data-identificacion="">Seleccione un funcionario...</option>
                        @foreach($funcionarios as $funcionario)
                            <option value="{{ $funcionario->id }}" data-identificacion="{{ $funcionario->identificacion }}" {{ old('funcionario_id') == $funcionario->id ? 'selected' : '' }}>
                                {{ $funcionario->nombre_completo }} ({{ $funcionario->identificacion }})
                            </option>
                        @endforeach
                    </select>
                    @error('funcionario_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Equipo <span class="text-danger">*</span></label>
                    <select name="equipo_id" id="equipo_id" class="form-select @error('equipo_id') is-invalid @enderror" required>
                        <option value="" data-cedula="">Seleccione un equipo...</option>
                        @foreach($equipos as $equipo)
                            <option value="{{ $equipo->id }}" data-cedula="{{ $equipo->usuarioAsignado->cedula ?? '' }}" {{ old('equipo_id') == $equipo->id ? 'selected' : '' }}>
                                {{ $equipo->nombre_equipo }} - Placa: {{ $equipo->activo_fijo ?? 'N/A' }} (Serial: {{ $equipo->serial_visual ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                    @error('equipo_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-md-12">
                    <label class="form-label fw-medium">Correo de Activación</label>
                    <input type="email" name="correo_activacion" class="form-control @error('correo_activacion') is-invalid @enderror" value="{{ old('correo_activacion') }}" placeholder="Correo al que fue entregada (si aplica)" oninput="this.value = this.value.replace(/[^a-zA-Z0-9@._-]/g, '')" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}">
                    <div class="form-text">Si la licencia se activó con un correo específico, ingréselo aquí.</div>
                    @error('correo_activacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-medium">Fecha de Asignación <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_asignacion" class="form-control @error('fecha_asignacion') is-invalid @enderror" value="{{ old('fecha_asignacion', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
                    @error('fecha_asignacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-medium">Fecha de Vencimiento</label>
                    <input type="date" name="fecha_vencimiento" class="form-control @error('fecha_vencimiento') is-invalid @enderror" value="{{ old('fecha_vencimiento') }}">
                    @error('fecha_vencimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-medium">Estado <span class="text-danger">*</span></label>
                    <select name="estado" class="form-select @error('estado') is-invalid @enderror" required>
                        <option value="Activa" {{ old('estado', 'Activa') == 'Activa' ? 'selected' : '' }}>Activa</option>
                        <option value="Vencida" {{ old('estado') == 'Vencida' ? 'selected' : '' }}>Vencida</option>
                        <option value="Retirada" {{ old('estado') == 'Retirada' ? 'selected' : '' }}>Retirada</option>
                        <option value="Suspendida" {{ old('estado') == 'Suspendida' ? 'selected' : '' }}>Suspendida</option>
                    </select>
                    @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-medium">Observaciones</label>
                    <textarea name="observaciones" class="form-control @error('observaciones') is-invalid @enderror" rows="2">{{ old('observaciones') }}</textarea>
                    @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-2"></i>Guardar Asignación
                </button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const funcionarioSelect = document.getElementById('funcionario_id');
    const equipoSelect = document.getElementById('equipo_id');
    
    // Guardar todas las opciones originales de equipo
    const todasLasOpcionesEquipo = Array.from(equipoSelect.options).map(opt => ({
        value: opt.value,
        text: opt.text,
        cedula: opt.getAttribute('data-cedula') || '',
        selected: opt.selected,
        isPlaceholder: opt.value === ''
    }));

    function filtrarEquipos() {
        const funcionarioOption = funcionarioSelect.options[funcionarioSelect.selectedIndex];
        const identificacionFuncionario = funcionarioOption ? (funcionarioOption.getAttribute('data-identificacion') || '') : '';
        
        // Limpiar el select actual
        equipoSelect.innerHTML = '';
        
        // Si no hay funcionario seleccionado, mostrar todos
        if (!identificacionFuncionario) {
            todasLasOpcionesEquipo.forEach(opt => {
                const newOption = new Option(opt.text, opt.value);
                newOption.setAttribute('data-cedula', opt.cedula);
                if (opt.selected) newOption.selected = true;
                equipoSelect.add(newOption);
            });
            return;
        }
        
        // Filtrar solo las opciones que correspondan a la cédula, y el placeholder
        const opcionesFiltradas = todasLasOpcionesEquipo.filter(opt => 
            opt.isPlaceholder || opt.cedula === identificacionFuncionario
        );
        
        opcionesFiltradas.forEach(opt => {
            const newOption = new Option(opt.text, opt.value);
            newOption.setAttribute('data-cedula', opt.cedula);
            if (opt.selected) newOption.selected = true;
            equipoSelect.add(newOption);
        });

        // Autoseleccionar si solo hay 1 equipo (además del placeholder)
        if (opcionesFiltradas.length === 2 && !opcionesFiltradas[1].selected) {
            equipoSelect.value = opcionesFiltradas[1].value;
        }
    }

    funcionarioSelect.addEventListener('change', filtrarEquipos);
    
    // Ejecutar al inicio por si ya hay un funcionario seleccionado (por old() input)
    filtrarEquipos();
});
</script>
@endpush
@endsection

