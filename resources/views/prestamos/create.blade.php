@extends('layouts.inventario')

@section('title', 'Registrar Préstamo')

@section('content')
<div class="container-fluid py-4">
    <x-ui.toolbar
        title="Registrar Nuevo Préstamo"
        icon="plus-circle"
        subtitle="Asignar un activo de bodega en calidad de préstamo temporal"
        :backRoute="route('prestamos.index')"
    />

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="m-0 font-weight-bold text-primary">Detalles del Préstamo Temporal</h6>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-primary small mb-4">
                <i class="bi bi-file-earmark-text-fill me-2"></i>
                Al registrar este préstamo se <strong>emitirá automáticamente el acta de novedad</strong> (tipo «Préstamo»)
                y el activo quedará marcado como <strong>Préstamo</strong> en el inventario hasta su devolución.
                Si la persona ya tiene un computador asignado, se aplicará la regla de un computador por funcionario.
            </div>
            <form action="{{ route('prestamos.store') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-lg-12">
                        <label class="form-label fw-bold" for="equipo_id">Activo Fijo a Prestar <span class="text-danger">*</span></label>
                        <select name="equipo_id" id="equipo_id" class="form-select" required aria-describedby="equipo_id_help">
                            <option value="">Seleccione un activo disponible...</option>
                            @if($equipoPreseleccionado)
                                <option value="{{ $equipoPreseleccionado->id }}" selected>
                                    {{ $equipoPreseleccionado->placa_visual }} - [{{ $equipoPreseleccionado->tipoRecurso ? $equipoPreseleccionado->tipoRecurso->nombre : 'Sin Tipo' }}] - {{ $equipoPreseleccionado->nombre_equipo }} (Serial: {{ $equipoPreseleccionado->serial_visual }})
                                </option>
                            @else
                                @foreach($equiposDisponibles as $equipo)
                                    <option value="{{ $equipo->id }}" {{ old('equipo_id') == $equipo->id ? 'selected' : '' }}>
                                        {{ $equipo->placa_visual }} - [{{ $equipo->tipoRecurso ? $equipo->tipoRecurso->nombre : 'Sin Tipo' }}] - {{ $equipo->nombre_equipo }} (Serial: {{ $equipo->serial_visual }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('equipo_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <small class="text-muted d-block mt-1" id="equipo_id_help">Solo se muestran los activos que se encuentran disponibles y sin préstamos ni asignaciones vigentes.</small>
                    </div>

                    <div class="col-lg-6">
<x-ui.input name="persona_nombre" label="Nombre de la Persona que Recibe *" type="text" required="true" value="{{ old('persona_nombre') }}" placeholder="Ej: Juan Pérez" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" autocomplete="off" containerClass="" />
                    </div>

                    <div class="col-lg-6">
<x-ui.input name="persona_documento" label="Documento / Identificación" type="text" value="{{ old('persona_documento') }}" placeholder="Opcional" oninput="this.value = this.value.replace(/[^0-9]/g, '')" autocomplete="off" containerClass="" />
                    </div>

                    <div class="col-lg-4">
                        <x-ui.input name="fecha_inicio" label="Fecha de Inicio *" type="datetime-local" required="true" value="{{ old('fecha_inicio', now()->format('Y-m-d\TH:i')) }}" min="{{ now()->format('Y-m-d\T00:00') }}" containerClass="" />
                    </div>

                    <div class="col-lg-4">
                        <x-ui.input name="fecha_devolucion_prevista" label="Fecha de Devolución Prevista *" type="datetime-local" required="true" value="{{ old('fecha_devolucion_prevista') }}" min="{{ now()->format('Y-m-d\T00:00') }}" containerClass="" />
                    </div>
                    
                    <div class="col-lg-4">
<x-ui.input name="duracion" label="Duración" type="text" value="{{ old('duracion') }}" placeholder="Ej: 3 días, 1 semana, 5 horas..." containerClass="" />
                    </div>

                    <div class="col-lg-12">
<x-ui.input name="motivo" label="Motivo del Préstamo" type="text" value="{{ old('motivo') }}" placeholder="Ej: Trabajo en campo, evento especial, préstamo temporal por reparación..." containerClass="" />
                    </div>


                </div>

                <div class="mt-4 pt-3 border-top text-end">
                    <a href="{{ route('prestamos.index') }}" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Registrar Préstamo</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

