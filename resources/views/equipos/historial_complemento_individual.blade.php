@extends('layouts.inventario')

@section('title', 'Historial del Complemento')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-clock-history me-2 text-primary"></i>
            Historial del Complemento
        </h4>
        <small class="text-muted">{{ $complemento->nombre }} — Serial: {{ $complemento->serial ?? 'N/A' }}</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('equipos.complementos.global') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<x-ui.card>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><th>Tipo:</th><td>{{ $complemento->catalogoComplemento->nombre ?? 'N/A' }}</td></tr>
                        <tr><th>Nombre:</th><td>{{ $complemento->nombre }}</td></tr>
                        <tr><th>Marca:</th><td>{{ $complemento->marca ?? 'N/A' }}</td></tr>
                        <tr><th>Modelo:</th><td>{{ $complemento->modelo ?? 'N/A' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><th>Serial:</th><td>{{ $complemento->serial ?? 'N/A' }}</td></tr>
                        <tr><th>Estado Actual:</th><td><span class="badge bg-primary">{{ $complemento->estado }}</span></td></tr>
                        <tr><th>Activo Asociado:</th><td>{{ $complemento->equipo->placa_visual ?? 'Ninguno' }}</td></tr>
                        <tr><th>Fecha Registro:</th><td>{{ $complemento->fecha_registro ? $complemento->fecha_registro->format('Y-m-d') : '-' }}</td></tr>
                    </table>
                </div>
            </div>

            <h5 class="mb-3 border-bottom pb-2">Línea de Tiempo</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-striped text-sm">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Evento</th>
                            <th>Usuario</th>
                            <th>Activo Origen</th>
                            <th>Activo Destino</th>
                            <th>Estado Anterior</th>
                            <th>Estado Nuevo</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historial as $registro)
                            <tr>
                                <td>{{ $registro->fecha->format('Y-m-d H:i') }}</td>
                                <td><span class="badge bg-info">{{ $registro->evento }}</span></td>
                                <td>{{ $registro->usuario->name ?? 'Sistema' }}</td>
                                <td>{{ $registro->activoOrigen->placa_visual ?? '-' }}</td>
                                <td>{{ $registro->activoDestino->placa_visual ?? '-' }}</td>
                                <td>{{ $registro->estado_anterior ?? '-' }}</td>
                                <td>{{ $registro->estado_nuevo ?? '-' }}</td>
                                <td>
                                    {{ $registro->observacion }}
                                    @if($registro->campo_modificado)
                                        <br><small class="text-muted"><strong>{{ $registro->campo_modificado }}:</strong> {{ $registro->valor_anterior }} &rarr; {{ $registro->valor_nuevo }}</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No hay registros en el historial.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $historial->links() }}
            </div>
            
</x-ui.card>
@endsection
