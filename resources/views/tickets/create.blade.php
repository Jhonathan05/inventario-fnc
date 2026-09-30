@extends('layouts.inventario')

@section('title', 'Nuevo Caso de Soporte')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        
        <x-ui.toolbar 
            title="Nuevo Caso de Soporte" 
            subtitle="Registra un incidente o requerimiento"
        >
            <x-ui.button href="{{ route('tickets.index') }}" color="light" class="rounded-circle" icon="x-lg" />
        </x-ui.toolbar>

        <x-ui.card class="border-0 shadow-sm">
        <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data">
            @csrf


            <h6 class="text-uppercase fw-bold text-dark mb-4 pb-2 border-bottom d-flex align-items-center gap-2 ticket-section-title">
                <i class="bi bi-info-circle text-primary"></i> DETALLES DEL REQUERIMIENTO
            </h6>

            <x-ui.input name="titulo" label="Título" required="true" placeholder="Ej. PROBLEMA CON ACCESO AL CORREO" value="{{ old('titulo') }}" containerClass="mb-4" />

            <div class="row mb-4">
                <x-ui.select name="tipo" label="Tipo" required="true" containerClass="col-md-6">
                        <option value="Requerimiento (Solicitud nueva)">Requerimiento (Solicitud nueva)</option>
                        <option value="Incidente (Falla de servicio)">Incidente (Falla de servicio)</option>
                        <option value="Soporte Técnico">Soporte Técnico</option>
                    </x-ui.select>
                <x-ui.select name="prioridad" label="Prioridad" required="true" containerClass="col-md-6">
                        <option value="Baja">Baja</option>
                        <option value="Media" selected>Media</option>
                        <option value="Alta">Alta</option>
                        <option value="Crítica">Crítica</option>
                    </x-ui.select>
            </div>

            <x-ui.textarea name="descripcion" label="Descripción" rows="4" required="true" placeholder="Describe el problema con el mayor detalle posible..." containerClass="mb-5" />



            <h6 class="text-uppercase fw-bold text-dark mb-4 pb-2 border-bottom d-flex align-items-center gap-2 ticket-section-title">
                <i class="bi bi-link-45deg text-dark"></i> ASOCIACIÓN
            </h6>

            <div class="row mb-5">
                <x-ui.select name="funcionario_id" label="Funcionario Solicitante" required="true" containerClass="col-md-6">
                        <option value="">Buscar solicitante...</option>
                        @foreach($funcionarios as $func)
                            <option value="{{ $func->id }}">{{ $func->nombres }} {{ $func->apellidos }} ({{ $func->identificacion }})</option>
                        @endforeach
                    </x-ui.select>
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted">Activo Relacionado (Opcional)</label>
                    <select name="equipo_id" class="form-select px-3 py-2 border-light-subtle rounded-3 bg-light text-muted" disabled>
                        <option value="">Selecciona un funcionario primero...</option>
                    </select>
                    <div class="form-text mt-2 ticket-hint"><i class="bi bi-tag text-primary"></i> Asocia un funcionario para ver sus equipos.</div>
                </div>
            </div>




            <div class="d-flex justify-content-end gap-3 pt-3 border-top">
                <x-ui.button href="{{ route('tickets.index') }}" color="light" class="border rounded-pill px-4" text="Cancelar" />
                <x-ui.button type="submit" color="primary" class="rounded-pill px-4 ticket-btn-blue" icon="check2" text="Crear Caso" />
            </div>

        </form>
    </x-ui.card>
    </div>
</div>

@endsection
