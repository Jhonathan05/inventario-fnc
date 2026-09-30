@extends('layouts.inventario')

@section('title', 'Checklists Técnicos')

@section('content')
<x-ui.toolbar 
    title="Checklists Técnicos" 
    icon="clipboard-check"
>
    <x-ui.button href="{{ route('checklists.create') }}" color="primary" icon="plus-lg" text="Nuevo Checklist" />
</x-ui.toolbar>

<x-ui.card noPadding="true">
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th>Equipo</th>
                <th>Responsable TI</th>
                <th>Orden de Trabajo</th>
                <th>Resultado</th>
                <th>Tipo Aprobado</th>
                <th>FNC</th>
                <th>Fecha</th>
                <th class="text-center">Acciones</th>
            </tr>
        </x-slot>
        @forelse($checklists as $checklist)
            <tr>
                <td>
                    @if($checklist->equipo)
                        <a href="{{ route('equipos.show', $checklist->equipo) }}"
                           class="text-decoration-none fw-medium">
                            {{ $checklist->equipo->nombre_equipo }}
                        </a>
                        <br><small class="text-muted">{{ $checklist->equipo->serial_visual }}</small>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $checklist->responsable_ti ?? '—' }}</td>
                <td>{{ $checklist->orden_trabajo ?? '—' }}</td>
                <td>{{ $checklist->resultado ?? '—' }}</td>
                <td>{{ $checklist->tipo_aprobado ?? '—' }}</td>
                <td>{{ $checklist->fnc ?? '—' }}</td>
                <td>{{ $checklist->created_at->format('d/m/Y') }}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <x-ui.button href="{{ route('checklists.show', $checklist) }}" outline="true" color="info" title="Ver" icon="eye" />
                        <x-ui.button href="{{ route('checklists.edit', $checklist) }}" outline="true" color="warning" title="Editar" icon="pencil" />
                        <button type="button"
                                class="btn btn-outline-danger"
                                data-delete-url="{{ route('checklists.destroy', $checklist) }}"
                                data-delete-name="{{ $checklist->equipo?->nombre_equipo ?? 'este checklist' }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>Sin checklists registrados.
                </td>
            </tr>
        @endforelse
    </x-ui.table>
    @if($checklists->hasPages())
        <div class="card-footer bg-white border-0">
            {{ $checklists->links('pagination::bootstrap-5') }}
        </div>
    @endif
</x-ui.card>

@endsection
