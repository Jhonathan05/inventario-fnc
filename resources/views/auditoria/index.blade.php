@extends('layouts.inventario')

@section('title', 'Auditoría')

@section('content')
<div class="container-fluid py-4">
    <x-ui.toolbar
        title="Auditoría"
        icon="journal-text"
        subtitle="Registro de acciones de los usuarios del sistema"
    />

    <x-ui.card noPadding="true">
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th class="ps-4">Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th class="pe-4">Detalles</th>
                </tr>
            </x-slot:head>
            @forelse($logs as $log)
            @php
                $accionTxt = mb_strtolower((string) $log->action);
                $toneAccion = match (true) {
                    str_contains($accionTxt, 'cre') || str_contains($accionTxt, 'creat') => 'success',
                    str_contains($accionTxt, 'elimin') || str_contains($accionTxt, 'delet') || str_contains($accionTxt, 'borr') => 'danger',
                    str_contains($accionTxt, 'actualiz') || str_contains($accionTxt, 'updat') || str_contains($accionTxt, 'edit') => 'info',
                    str_contains($accionTxt, 'login') || str_contains($accionTxt, 'sesion') || str_contains($accionTxt, 'sesión') => 'brand',
                    default => 'slate',
                };
            @endphp
            <tr>
                <td class="ps-4 text-nowrap">
                    <span class="fnc-udash__when">
                        <span class="fnc-udash__whenrel">{{ $log->created_at->diffForHumans() }}</span>
                        <span class="fnc-udash__sub">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                    </span>
                </td>
                <td>
                    <div class="fnc-udash__names">
                        <span class="fnc-udash__name">{{ $log->user?->name ?? 'Sistema' }}</span>
                        <span class="fnc-udash__sub">{{ $log->user?->email ?? '—' }}</span>
                    </div>
                </td>
                <td class="text-nowrap">
                    <x-fnc.pill :tone="$toneAccion" dot>{{ $log->action }}</x-fnc.pill>
                </td>
                <td class="pe-4">
                    <span class="fnc-udash__sub d-block text-truncate" style="max-width: 420px;" title="{{ $log->description }}">
                        {{ $log->description }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x fs-2 d-block mb-2 opacity-50"></i>
                    No hay registros de auditoría todavía.
                </td>
            </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>
</div>
@endsection
