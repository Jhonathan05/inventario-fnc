@extends('layouts.inventario')

@section('title', 'Detalle de Funcionario')

@section('content')
<x-ui.toolbar 
    title="Detalle de Funcionario" 
    backRoute="{{ route('funcionarios.index') }}"
>
    <x-ui.button href="{{ route('funcionarios.edit', $funcionario) }}" color="warning" class="text-white" icon="pencil" text="Editar" />
</x-ui.toolbar>

<div class="row g-4">
    <div class="col-lg-6">
        <x-ui.card noPadding="true" class="h-100">
            <div class="card-header bg-primary bg-opacity-10 fw-semibold border-0 py-3">
                <i class="bi bi-person-vcard me-2 text-primary"></i>Datos del Funcionario
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted">Nombre</dt>
                    <dd class="col-sm-7 fw-semibold">{{ $funcionario->nombre_completo }}</dd>

                    <dt class="col-sm-5 text-muted">Identificación</dt>
                    <dd class="col-sm-7">{{ $funcionario->identificacion }}</dd>

                    <dt class="col-sm-5 text-muted">Cargo</dt>
                    <dd class="col-sm-7">{{ $funcionario->cargo ?? '—' }}</dd>

                    <dt class="col-sm-5 text-muted">Área</dt>
                    <dd class="col-sm-7">{{ $funcionario->area ?? '—' }}</dd>

                    <dt class="col-sm-5 text-muted">Departamento</dt>
                    <dd class="col-sm-7">{{ $funcionario->departamento ?? '—' }}</dd>

                    <dt class="col-sm-5 text-muted">Ciudad</dt>
                    <dd class="col-sm-7">{{ $funcionario->ciudad ?? '—' }}</dd>

                    <dt class="col-sm-5 text-muted">Distrito</dt>
                    <dd class="col-sm-7">{{ $funcionario->distrito ?? '—' }}</dd>

                    <dt class="col-sm-5 text-muted">Seccional</dt>
                    <dd class="col-sm-7">{{ $funcionario->seccional ?? '—' }}</dd>

                    <dt class="col-sm-5 text-muted">Estado</dt>
                    <dd class="col-sm-7">
                        <x-ui.badge color="{{ $funcionario->estado === 'Activo' ? 'success' : 'secondary' }}">
                            {{ $funcionario->estado }}
                        </x-ui.badge>
                    </dd>
                </dl>
            </div>
        </x-ui.card>
    </div>

    <div class="col-lg-6">
        <x-ui.card noPadding="true" class="h-100">
            <div class="card-header bg-info bg-opacity-10 fw-semibold border-0 py-3">
                <i class="bi bi-bar-chart me-2 text-info"></i>Resumen
            </div>
            <div class="card-body">
                <div class="d-flex gap-3 flex-wrap mb-3">
                    <x-ui.badge color="primary" class="fs-6">Activos: {{ $funcionario->equipos_asignados_count }}</x-ui.badge>
                    <x-ui.badge color="success" class="fs-6">Autorizaciones totales: {{ $funcionario->autorizaciones_activos_count }}</x-ui.badge>
                </div>
                <small class="text-muted">Cada activo adicional consume una autorización disponible (estado cargada).</small>
            </div>
        </x-ui.card>
    </div>

    <div class="col-12">
        <x-ui.card noPadding="true">
            <div class="card-header bg-success bg-opacity-10 fw-semibold border-0 py-3 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-earmark-check me-2 text-success"></i>Actas de autorización</span>
                <x-ui.badge color="light text-dark border">Disponibles: {{ $funcionario->autorizacionesActivos->where('estado', 'cargada')->count() }}</x-ui.badge>
            </div>
            <x-ui.table>
                <x-slot name="head">
                    <tr>
                        <th>#</th>
                        <th>Archivo</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Uso</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </x-slot>
                
                @forelse($funcionario->autorizacionesActivos as $acta)
                    <tr>
                        <td>{{ $acta->id }}</td>
                        <td class="small">{{ basename($acta->archivo) }}</td>
                        <td>
                            @if($acta->estado === 'cargada')
                                <x-ui.badge color="success">Cargada</x-ui.badge>
                            @elseif($acta->estado === 'consumida')
                                <x-ui.badge color="primary">Consumida</x-ui.badge>
                            @else
                                <x-ui.badge color="secondary">Anulada</x-ui.badge>
                            @endif
                        </td>
                        <td class="small">{{ optional($acta->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="small">
                            @if($acta->estado === 'consumida' && $acta->asignacion_id)
                                Asignación #{{ $acta->asignacion_id }}
                            @elseif($acta->estado === 'anulada')
                                {{ $acta->motivo_anulacion ?? 'Sin motivo' }}
                            @else
                                Pendiente de uso
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('funcionarios.autorizaciones.descargar', [$funcionario, $acta]) }}" target="_blank" class="btn btn-sm btn-outline-info me-1" title="Ver Acta">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($acta->estado === 'cargada')
                                <form method="POST" action="{{ route('funcionarios.autorizaciones.anular', [$funcionario, $acta]) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.button type="submit" outline="true" color="danger" size="sm" text="Anular" />
                                </form>
                            @else
                                <span class="text-muted small">No editable</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Sin actas registradas.</td>
                    </tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>
    </div>

    <div class="col-12">
        <x-ui.card noPadding="true">
            <div class="card-header bg-light fw-semibold border-0 py-3">
                <i class="bi bi-laptop me-2 text-secondary"></i>Activos Asignados
            </div>
            <x-ui.table>
                <x-slot name="head">
                    <tr>
                        <th>Equipo</th>
                        <th>Serial</th>
                        <th>Estado</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </x-slot>
                @forelse($funcionario->equiposAsignados as $asignado)
                    <tr>
                        <td>{{ $asignado->equipo?->nombre_equipo ?? '—' }}</td>
                        <td class="font-monospace">{{ $asignado->equipo?->serial_visual ?? '—' }}</td>
                        <td>{{ $asignado->equipo?->estado_label ?? '—' }}</td>
                        <td class="text-end pe-3">
                            @if($asignado->equipo)
                                <x-ui.button href="{{ route('equipos.show', $asignado->equipo) }}" outline="true" color="info" size="sm" title="Ver equipo" icon="eye" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Sin activos asignados.</td>
                    </tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>
    </div>
</div>
@endsection
