@extends('layouts.inventario')

@section('title', 'Auditoría')

@section('content')
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>Registro de Auditoría</h4>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
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
                        $inicialesLog = collect(explode(' ', trim((string) $log->user_name)))
                            ->reject(fn ($p) => mb_strlen($p) < 2)
                            ->map(fn ($p) => mb_substr($p, 0, 1))
                            ->take(2)
                            ->implode('');
                    @endphp
                    <tr>
                        <td class="text-nowrap">
                            <span class="fnc-udash__when">
                                <span class="fnc-udash__whenrel">{{ $log->created_at->diffForHumans() }}</span>
                                <span class="fnc-udash__sub">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                            </span>
                        </td>
                        <td>
                            <span class="fnc-udash__user">
                                <span class="fnc-udash__avatar" aria-hidden="true">{{ $inicialesLog ?: '·' }}</span>
                                <span class="fnc-udash__unombre">{{ $log->user_name }}</span>
                            </span>
                        </td>
                        <td>
                            <x-fnc.pill :tone="$toneAccion">{{ $log->action }}</x-fnc.pill>
                        </td>
                        <td class="text-secondary small">
                            {{ $log->details }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No hay registros de auditoría disponibles.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4">
    {{ $logs->links('pagination::bootstrap-5') }}
</div>
@endsection

