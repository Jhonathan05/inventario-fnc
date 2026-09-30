@extends('layouts.inventario')

@section('title', 'Detalle de Préstamo')

@section('content')
<x-ui.toolbar 
    title="Detalle de Préstamo #{{ str_pad($asignacion->id, 5, '0', STR_PAD_LEFT) }}" 
    icon="person-fill-gear"
    backRoute="{{ route('equipos.show', $asignacion->equipo_id) }}"
>
    <x-slot name="title">
        <h4 class="fw-bold mb-0">
            <i class="bi bi-person-fill-gear me-2 text-primary"></i>
            Detalle de Préstamo #{{ str_pad($asignacion->id, 5, '0', STR_PAD_LEFT) }}
        </h4>
        <small class="text-muted">
            {{ $asignacion->equipo?->nombre_equipo }} — {{ $asignacion->fecha_accion?->format('d/m/Y H:i') }}
        </small>
    </x-slot>
    @if(in_array($asignacion->tipo_accion, ['asignacion','reemplazo']))
        <x-ui.button href="{{ route('asignaciones.pdf', $asignacion) }}" color="danger" icon="file-pdf" text="Descargar PDF" />
    @endif
    <x-ui.button href="{{ route('equipos.show', $asignacion->equipo_id) }}" outline="true" color="secondary" icon="arrow-left" text="Volver al equipo" />
</x-ui.toolbar>

<div class="row g-4">
    {{-- Tipo de acción --}}
    <div class="col-12">
        <x-ui.alert type="{{ $asignacion->tipo_accion_color }}" class="d-flex align-items-center gap-3 mb-0" icon="info-circle-fill">
            <div>
                <strong>{{ $asignacion->tipo_accion_label }}</strong>
                @if($asignacion->motivo)
                    — {{ $asignacion->motivo }}
                @endif
            </div>
        </x-ui.alert>
    </div>

    {{-- Datos del equipo --}}
    <div class="col-lg-6">
        <x-ui.card noPadding="true" class="h-100 border-0 shadow-sm">
            <div class="card-header bg-primary bg-opacity-10 fw-semibold border-0 py-3">
                <i class="bi bi-laptop me-2 text-primary"></i>Datos del Equipo
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted">Equipo</dt>
                    <dd class="col-sm-7 fw-bold">{{ $asignacion->equipo?->nombre_equipo }}</dd>
                    <dt class="col-sm-5 text-muted">Serial</dt>
                    <dd class="col-sm-7 font-monospace">{{ $asignacion->equipo?->serial_visual }}</dd>
                    <dt class="col-sm-5 text-muted">Activo Fijo</dt>
                    <dd class="col-sm-7">
                        @php
                            $invalidos = ['PENDIENTE', 'N/A', 'NA', 'NO TIENE', 'SIN PLACA', 'SIN REGISTRO'];
                            $activoFijoStr = strtoupper(trim((string) $asignacion->equipo?->activo_fijo));
                            $activoValido = !empty($asignacion->equipo?->activo_fijo) && !in_array($activoFijoStr, $invalidos, true);
                        @endphp
                        {{ $activoValido ? $asignacion->equipo->activo_fijo : '—' }}
                    </dd>
                    <dt class="col-sm-5 text-muted">Tipo</dt>
                    <dd class="col-sm-7">{{ $asignacion->equipo?->tipoRecurso?->nombre ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">Marca / Modelo</dt>
                    <dd class="col-sm-7">{{ $asignacion->equipo?->marca }} / {{ $asignacion->equipo?->modelo }}</dd>
                </dl>
            </div>
        </x-ui.card>
    </div>

    {{-- Datos del usuario --}}
    <div class="col-lg-6">
        <x-ui.card noPadding="true" class="h-100 border-0 shadow-sm">
            <div class="card-header bg-success bg-opacity-10 fw-semibold border-0 py-3">
                <i class="bi bi-person me-2 text-success"></i>Usuario en el Momento
            </div>
            <div class="card-body">
                @if($asignacion->usuario_nombre)
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted">Nombre</dt>
                    <dd class="col-sm-7 fw-bold">{{ $asignacion->usuario_nombre }}</dd>
                    <dt class="col-sm-5 text-muted">Cédula</dt>
                    <dd class="col-sm-7">{{ $asignacion->usuario_cedula }}</dd>
                    <dt class="col-sm-5 text-muted">Cargo</dt>
                    <dd class="col-sm-7">{{ $asignacion->usuario_cargo ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">Área</dt>
                    <dd class="col-sm-7">{{ $asignacion->usuario_area ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">Dependencia</dt>
                    <dd class="col-sm-7">{{ $asignacion->usuario_dependencia ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">Distrito</dt>
                    <dd class="col-sm-7">{{ $asignacion->usuario_distrito ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">Seccional</dt>
                    <dd class="col-sm-7">{{ $asignacion->usuario_seccional ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">Ciudad</dt>
                    <dd class="col-sm-7">{{ $asignacion->usuario_ciudad ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">Departamento</dt>
                    <dd class="col-sm-7">{{ $asignacion->usuario_departamento ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">Empresa</dt>
                    <dd class="col-sm-7">{{ $asignacion->usuario_empresa_propietaria ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">Shortname</dt>
                    <dd class="col-sm-7">{{ $asignacion->usuario_shortname ?? '—' }}</dd>
                </dl>
                @else
                    <p class="text-muted mb-0">Sin datos de usuario para este evento.</p>
                @endif
            </div>
        </x-ui.card>
    </div>

    {{-- Datos de la acción --}}
    @if($asignacion->observaciones || $asignacion->entregado_por)
    <div class="col-12">
        <x-ui.card noPadding="true" class="border-0 shadow-sm">
            <div class="card-header bg-secondary bg-opacity-10 fw-semibold border-0 py-3">
                <i class="bi bi-info-circle me-2 text-secondary"></i>Información Adicional
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    @if($asignacion->entregado_por)
                    <dt class="col-sm-3 text-muted">Entregado por</dt>
                    <dd class="col-sm-9">{{ $asignacion->entregado_por }}</dd>
                    @endif
                    @if($asignacion->observaciones)
                    <dt class="col-sm-3 text-muted">Observaciones</dt>
                    <dd class="col-sm-9">{{ $asignacion->observaciones }}</dd>
                    @endif
                    <dt class="col-sm-3 text-muted">Registrado por</dt>
                    <dd class="col-sm-9">{{ $asignacion->registradoPor?->name ?? '—' }}</dd>
                </dl>
            </div>
        </x-ui.card>
    </div>
    @endif
</div>
@endsection
