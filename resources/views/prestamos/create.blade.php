@extends('layouts.inventario')

@section('title', 'Registrar Préstamo')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
        <h2 class="h3 mb-0 text-gray-800"><i class="bi bi-plus-circle me-2 text-primary"></i>Registrar Nuevo Préstamo</h2>
        <a href="{{ route('prestamos.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Volver a Préstamos</a>
    </div>

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
                        <label class="form-label fw-bold" for="persona_nombre">Nombre de la Persona que Recibe <span class="text-danger">*</span></label>
                        <input type="text" name="persona_nombre" id="persona_nombre" class="form-control @error('persona_nombre') is-invalid @enderror" required value="{{ old('persona_nombre') }}" placeholder="Ej: Juan Pérez" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" autocomplete="off">
                        @error('persona_nombre')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label fw-bold" for="persona_documento">Documento / Identificación</label>
                        <input type="text" name="persona_documento" id="persona_documento" class="form-control @error('persona_documento') is-invalid @enderror" value="{{ old('persona_documento') }}" placeholder="Opcional" oninput="this.value = this.value.replace(/[^0-9]/g, '')" autocomplete="off">
                        @error('persona_documento')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-bold" for="fecha_inicio">Fecha de Inicio <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="fecha_inicio" id="fecha_inicio" class="form-control @error('fecha_inicio') is-invalid @enderror" required value="{{ old('fecha_inicio', now()->format('Y-m-d\TH:i')) }}" min="{{ now()->format('Y-m-d\T00:00') }}">
                        @error('fecha_inicio')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-bold" for="fecha_devolucion_prevista">Fecha de Devolución Prevista <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="fecha_devolucion_prevista" id="fecha_devolucion_prevista" class="form-control @error('fecha_devolucion_prevista') is-invalid @enderror" required value="{{ old('fecha_devolucion_prevista') }}" min="{{ now()->format('Y-m-d\T00:00') }}">
                        @error('fecha_devolucion_prevista')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-lg-4">
                        <label class="form-label fw-bold" for="duracion">Duración</label>
                        <input type="text" name="duracion" id="duracion" class="form-control" value="{{ old('duracion') }}" placeholder="Ej: 3 días, 1 semana, 5 horas...">
                        <small class="text-muted">Texto informativo opcional.</small>
                    </div>

                    <div class="col-lg-12">
                        <label class="form-label fw-bold" for="motivo">Motivo del Préstamo</label>
                        <input type="text" name="motivo" id="motivo" class="form-control" value="{{ old('motivo') }}" placeholder="Ej: Trabajo en campo, evento especial, préstamo temporal por reparación...">
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

