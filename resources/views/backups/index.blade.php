@extends('layouts.inventario')

@section('title', 'Backups')

@section('content')
<div class="container-fluid py-4">
    <x-ui.toolbar
        title="Backups"
        icon="database"
        subtitle="Volcados comprimidos de la base de datos del inventario, guardados en disco privado"
    />

    {{-- Resumen del módulo --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.08em;">Backups guardados</div>
                    <div class="fs-4 fw-bold">{{ $backups->count() }}</div>
                    <div class="text-muted small">de {{ $retencion }} que conserva la retención</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.08em;">Programación</div>
                    <div class="fs-6 fw-bold mt-1">
                        @if ($programado)
                            <x-fnc.pill tone="success" dot>Diaria a las 02:00</x-fnc.pill>
                        @else
                            <x-fnc.pill tone="slate" dot>Solo manual</x-fnc.pill>
                        @endif
                    </div>
                    <div class="text-muted small mt-1">
                        @if ($programado)
                            El job <code>backups:crear</code> corre en el scheduler.
                        @else
                            Actívala con <code>BACKUPS_PROGRAMADO=true</code>.
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.08em;">Formato</div>
                    <div class="fs-6 fw-bold mt-1"><x-fnc.pill tone="info">MySQL · gzip</x-fnc.pill></div>
                    <div class="text-muted small mt-1"><code>{{ $prefijo }}_backup_<fecha>.sql.gz</code></div>
                </div>
            </div>
        </div>
    </div>

    <x-ui.card noPadding="true" title="Backups disponibles" icon="database">
        <x-slot:headerActions>
            <form method="POST" action="{{ route('backups.crear') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm" title="Genera un volcado gzip de la base de datos actual">
                    <i class="bi bi-plus-lg me-1"></i> Crear backup ahora
                </button>
            </form>
        </x-slot:headerActions>

        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <th class="ps-4">Backup</th>
                    <th>Tamaño</th>
                    <th>Creado</th>
                    <th class="text-end pe-4">Acciones</th>
                </tr>
            </x-slot>

            @forelse ($backups as $backup)
                @php $cuando = \Carbon\Carbon::createFromTimestamp($backup['fecha']); @endphp
                <tr>
                    <td class="ps-4">
                        <div class="fnc-udash__asset">
                            <span class="fnc-udash__ic" aria-hidden="true">
                                <x-fnc.icon name="database" :size="16" />
                            </span>
                            <span class="fnc-udash__names">
                                <span class="fnc-udash__name">{{ $backup['nombre'] }}</span>
                                <span class="fnc-udash__sub">Volcado MySQL comprimido (gzip)</span>
                            </span>
                        </div>
                    </td>
                    <td class="text-nowrap">
                        <x-fnc.pill tone="info">
                            {{ $backup['bytes'] >= 1048576
                                ? number_format($backup['bytes'] / 1048576, 1) . ' MB'
                                : number_format($backup['bytes'] / 1024, 1) . ' KB' }}
                        </x-fnc.pill>
                    </td>
                    <td class="text-nowrap">
                        <span class="fnc-udash__when">
                            <span class="fnc-udash__whenrel">{{ $cuando->diffForHumans() }}</span>
                            <span class="fnc-udash__sub">{{ $cuando->format('d/m/Y H:i') }}</span>
                        </span>
                    </td>
                    <td class="text-end pe-4 text-nowrap">
                        <span class="d-inline-flex gap-1 justify-content-end">
                            <a href="{{ route('backups.descargar', ['archivo' => $backup['nombre'], '_tab' => request('_tab')]) }}"
                               class="fnc-udash__ver" title="Descargar" aria-label="Descargar {{ $backup['nombre'] }}">
                                <x-fnc.icon name="download" :size="15" />
                            </a>
                            <form method="POST" action="{{ route('backups.eliminar', ['archivo' => $backup['nombre'], '_tab' => request('_tab')]) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Eliminar el backup {{ $backup['nombre'] }}? Esta acción no se puede deshacer.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="fnc-udash__ver text-danger" title="Eliminar" aria-label="Eliminar {{ $backup['nombre'] }}">
                                    <x-fnc.icon name="trash" :size="15" />
                                </button>
                            </form>
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-5 text-muted">
                        <i class="bi bi-database fs-2 d-block mb-2 opacity-50"></i>
                        No hay backups todavía. Crea el primero con «Crear backup ahora».
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>
</div>
@endsection
