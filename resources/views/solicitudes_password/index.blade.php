@extends('layouts.inventario')

@section('title', 'Solicitudes de Cambio de Contraseña')

@section('content')
<x-ui.toolbar 
    title="Solicitudes de Cambio de Contraseña" 
    icon="shield-exclamation"
/>

<x-ui.card class="mb-4">
    <form method="GET" action="{{ route('solicitudes-password.index') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label for="estado" class="form-label fw-semibold">Estado</label>
            <select name="estado" id="estado" class="form-select">
                <option value="">Todos</option>
                <option value="Pendiente" {{ request('estado') === 'Pendiente' ? 'selected' : '' }}>Pendientes</option>
                <option value="Atendida" {{ request('estado') === 'Atendida' ? 'selected' : '' }}>Atendidas</option>
                <option value="Rechazada" {{ request('estado') === 'Rechazada' ? 'selected' : '' }}>Rechazadas</option>
            </select>
        </div>

        <div class="col-md-3">
            <label for="fecha" class="form-label fw-semibold">Fecha</label>
            <input type="date" name="fecha" id="fecha" value="{{ request('fecha') }}" class="form-control">
        </div>

        <div class="col-md-4">
            <label for="buscar" class="form-label fw-semibold">Buscar (nombre, correo o estado)</label>
            <input type="text" name="buscar" id="buscar" value="{{ request('buscar') }}" class="form-control" placeholder="Ej: Juan, correo@dominio.com, Pendiente">
        </div>

        <div class="col-md-2 d-grid gap-2">
            <x-ui.button type="submit" color="primary" icon="search" text="Filtrar" />
            <x-ui.button href="{{ route('solicitudes-password.index') }}" color="secondary" outline="true" text="Limpiar" />
        </div>
    </form>
</x-ui.card>

<x-ui.card noPadding="true">
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </x-slot>
        @forelse($solicitudes as $solicitud)
            @php
                $claseEstado = match ($solicitud->estado) {
                    'Atendida' => 'is-atendida',
                    'Rechazada' => 'is-rechazada',
                    default => 'is-pendiente',
                };
            @endphp
            <tr>
                <td class="fw-semibold">#{{ $solicitud->id }}</td>
                <td>{{ $solicitud->usuario?->name ?? 'Usuario eliminado' }}</td>
                <td>{{ $solicitud->email }}</td>
                <td>{{ optional($solicitud->created_at)->format('d/m/Y') }}</td>
                <td>{{ optional($solicitud->created_at)->format('H:i:s') }}</td>
                <td>
                    <span class="solicitud-password-badge {{ $claseEstado }}">{{ $solicitud->estado }}</span>
                </td>
                <td class="text-end">
                    <x-ui.button href="{{ route('solicitudes-password.show', $solicitud) }}" outline="true" color="primary" class="btn-sm" text="Ver detalle" />
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">No hay solicitudes registradas.</td>
            </tr>
        @endforelse
    </x-ui.table>
</x-ui.card>

<div class="mt-4">
    {{ $solicitudes->links('pagination::bootstrap-5') }}
</div>
@endsection
