@extends('layouts.inventario')

@section('title', 'Historial Global de Complementos')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-clock-history me-2 text-primary"></i>
            Historial Global de Complementos
        </h4>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('equipos.complementos.global') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<x-ui.card>
            <form method="GET" action="{{ route('equipos.complementos.historial_global') }}" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="buscar" class="form-control" placeholder="Buscar por serial, marca..." value="{{ request('buscar') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="evento" class="form-select">
                            <option value="">Todos los eventos</option>
                            @foreach($eventos as $ev)
                                <option value="{{ $ev }}" {{ request('evento') == $ev ? 'selected' : '' }}>{{ $ev }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped text-sm">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Evento</th>
                            <th>Complemento</th>
                            <th>Usuario</th>
                            <th>Activo Origen</th>
                            <th>Activo Destino</th>
                            <th>Estado Ant. -> Nuevo</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historial as $registro)
                            <tr>
                                <td>{{ $registro->fecha->format('Y-m-d H:i') }}</td>
                                <td><span class="badge bg-info">{{ $registro->evento }}</span></td>
                                <td>
                                    <a href="{{ route('equipos.complementos.historial_individual', $registro->complemento_id) }}">
                                        {{ $registro->complemento->nombre ?? 'N/A' }} 
                                        <small class="d-block text-muted">{{ $registro->complemento->serial ?? '' }}</small>
                                    </a>
                                </td>
                                <td>{{ $registro->usuario->name ?? 'Sistema' }}</td>
                                <td>{{ $registro->activoOrigen->placa_visual ?? '-' }}</td>
                                <td>{{ $registro->activoDestino->placa_visual ?? '-' }}</td>
                                <td>
                                    @if($registro->estado_anterior || $registro->estado_nuevo)
                                        {{ $registro->estado_anterior ?? '-' }} &rarr; {{ $registro->estado_nuevo ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {{ $registro->observacion }}
                                    @if($registro->campo_modificado)
                                        <br><small class="text-muted">{{ $registro->campo_modificado }}: {{ $registro->valor_anterior }} &rarr; {{ $registro->valor_nuevo }}</small>
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
