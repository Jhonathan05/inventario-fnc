@extends('layouts.inventario')

@section('title', 'Editar Licencia')

@section('content')
<x-ui.toolbar 
    title="Editar Licencia" 
    icon="pencil-square" 
    backRoute="{{ route('licencias.index') }}"
/>

<x-ui.card>
    <form method="POST" action="{{ route('licencias.update', $licencia) }}">
        @csrf
        @method('PUT')
        
        <div class="row g-4">
            <x-ui.input 
                name="nombre" 
                label="Nombre de Licencia" 
                required="true"
                value="{{ old('nombre', $licencia->nombre) }}"
                containerClass="col-12 col-md-6"
            />
            
            <x-ui.select 
                name="tipo_licencia" 
                id="tipo_licencia"
                label="Tipo" 
                required="true"
                containerClass="col-12 col-md-6"
            >
                <option value="Suscripción" {{ old('tipo_licencia', $licencia->tipo_licencia) == 'Suscripción' ? 'selected' : '' }}>Suscripción</option>
                <option value="Vitalicia" {{ old('tipo_licencia', $licencia->tipo_licencia) == 'Vitalicia' ? 'selected' : '' }}>Licencia Vitalicia</option>
            </x-ui.select>
            
            <x-ui.select 
                name="estado" 
                label="Estado" 
                required="true"
                containerClass="col-12 col-md-6"
            >
                <option value="Activa" {{ old('estado', $licencia->estado) == 'Activa' ? 'selected' : '' }}>Activa</option>
                <option value="Suspendida" {{ old('estado', $licencia->estado) == 'Suspendida' ? 'selected' : '' }}>Suspendida</option>
                <option value="Vencida" {{ old('estado', $licencia->estado) == 'Vencida' ? 'selected' : '' }}>Vencida</option>
            </x-ui.select>


            <x-ui.input 
                name="cantidad_maxima" 
                id="cantidad_maxima"
                type="number"
                label="Cantidad Máxima de Cupos" 
                required="true"
                value="{{ old('cantidad_maxima', $licencia->cantidad_maxima) }}"
                min="1"
                containerClass="col-12 col-md-4 campo-suscripcion"
            />
            
            <x-ui.input 
                name="fecha_inicio" 
                id="fecha_inicio"
                type="date"
                label="Fecha de Inicio" 
                value="{{ old('fecha_inicio', $licencia->fecha_inicio ? $licencia->fecha_inicio->format('Y-m-d') : '') }}"
                min="{{ $licencia->fecha_inicio && $licencia->fecha_inicio->isPast() ? $licencia->fecha_inicio->format('Y-m-d') : date('Y-m-d') }}"
                containerClass="col-12 col-md-4 campo-suscripcion"
            />
            
            <x-ui.input 
                name="fecha_vencimiento" 
                id="fecha_vencimiento"
                type="date"
                label="Fecha de Vencimiento" 
                value="{{ old('fecha_vencimiento', $licencia->fecha_vencimiento ? $licencia->fecha_vencimiento->format('Y-m-d') : '') }}"
                containerClass="col-12 col-md-4 campo-suscripcion"
            />

            <x-ui.select 
                name="requiere_correo" 
                id="requiere_correo"
                label="¿Requiere correo electrónico?" 
                required="true"
                containerClass="col-12 col-md-6 campo-suscripcion"
            >
                <option value="0" {{ old('requiere_correo', $licencia->requiere_correo) == false ? 'selected' : '' }}>No</option>
                <option value="1" {{ old('requiere_correo', $licencia->requiere_correo) == true ? 'selected' : '' }}>Sí</option>
            </x-ui.select>


            <div class="col-12 col-md-6" id="container_correo_asociado" style="display: none;">
                <label class="form-label fw-medium">Correo de Compra <span class="text-danger">*</span></label>
                <input type="email" name="correo_compra" id="correo_compra" class="form-control @error('correo_compra') is-invalid @enderror" value="{{ old('correo_compra', $licencia->correo_compra) }}" placeholder="Correo con el que se adquirió">
                @error('correo_compra') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <x-ui.input 
                name="fecha_compra" 
                type="date"
                label="Fecha de Compra" 
                value="{{ old('fecha_compra', $licencia->fecha_compra ? $licencia->fecha_compra->format('Y-m-d') : '') }}"
                containerClass="col-12 col-md-3"
            >
                <x-slot name="append">
                    <div class="form-text">Informativa</div>
                </x-slot>
            </x-ui.input>

            <x-ui.input 
                name="fecha_renovacion" 
                type="date"
                label="Fecha de Renovación" 
                value="{{ old('fecha_renovacion', $licencia->fecha_renovacion ? $licencia->fecha_renovacion->format('Y-m-d') : '') }}"
                containerClass="col-12 col-md-3 campo-suscripcion"
            />

        </div>

        <div class="mt-4 pt-3 border-top text-end">
            <x-ui.button type="submit" color="primary" class="px-4" icon="save" text="Actualizar Licencia" />
        </div>
    </form>
</x-ui.card>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoSelect = document.getElementById('tipo_licencia');
    const requiereCorreoSelect = document.getElementById('requiere_correo');
    
    const camposSuscripcion = document.querySelectorAll('.campo-suscripcion');
    const camposVitalicia = document.querySelectorAll('.campo-vitalicia');
    const containerCorreo = document.getElementById('container_correo_asociado');
    
    function updateForm() {
        const tipo = tipoSelect.value;
        const requiereCorreo = requiereCorreoSelect.value === '1';
        
        if (tipo === 'Suscripción') {
            camposSuscripcion.forEach(el => el.style.display = 'block');
            camposVitalicia.forEach(el => el.style.display = 'none');
            
            if (requiereCorreo) {
                containerCorreo.style.display = 'block';
            } else {
                containerCorreo.style.display = 'none';
            }
        } else if (tipo === 'Vitalicia') {
            camposSuscripcion.forEach(el => el.style.display = 'none');
            camposVitalicia.forEach(el => el.style.display = 'block');
            
            containerCorreo.style.display = 'block';
        }
    }
    
    tipoSelect.addEventListener('change', updateForm);
    requiereCorreoSelect.addEventListener('change', updateForm);
    
    // Initial load
    updateForm();
});
</script>
@endpush
@endsection
