@extends('layouts.inventario')

@section('title', 'Historial Administrativo · ' . ($equipo->nombre_equipo ?? $equipo->serial))

@section('content')
<x-ui.toolbar
    title="Historial Administrativo"
    icon="clock-history"
>
    <x-slot name="subtitle">{{ $equipo->nombre_equipo ?: $equipo->serial }} · {{ $equipo->serial_visual ?? '' }} ({{ $registros->total() }} registros)</x-slot>
    <x-ui.button href="{{ route('equipos.show', $equipo) }}" outline="true" color="secondary" icon="box" text="Ver activo" />
</x-ui.toolbar>

<x-ui.card noPadding="true">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Tipo de cambio</th>
                    <th>Campo</th>
                    <th>Detalle</th>
                    <th>Realizado por</th>
                    <th class="text-end pe-4">Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registros as $reg)
                    <tr>
                        <td class="ps-4">
                            <span class="badge bg-light text-dark border">{{ $reg->tipo_cambio_label }}</span>
                        </td>
                        <td class="small text-muted">{{ $reg->campo_modificado ?? '—' }}</td>
                        <td class="small" style="max-width: 420px;">
                            @if($reg->valor_anterior || $reg->valor_nuevo)
                                <span class="text-muted">{{ $reg->valor_anterior ?? '—' }}</span>
                                <i class="bi bi-arrow-right mx-1 text-secondary"></i>
                                <span class="fw-medium">{{ $reg->valor_nuevo ?? '—' }}</span>
                            @endif
                            @if($reg->descripcion)
                                <br><span class="text-muted">{{ $reg->descripcion }}</span>
                            @endif
                        </td>
                        <td>{{ $reg->realizadoPor?->name ?? '—' }}</td>
                        <td class="text-end pe-4 text-nowrap">{{ $reg->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-clock-history fs-2 d-block mb-2 opacity-50"></i>
                            Este activo no tiene cambios administrativos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

<div class="mt-4">
    {{ $registros->links() }}
</div>
@endsection
