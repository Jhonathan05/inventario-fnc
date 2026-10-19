@extends('layouts.inventario')

@section('title', 'Roles y Permisos')

@section('content')
<x-ui.toolbar 
    title="Roles y Permisos" 
    icon="shield-lock"
>
    @can('roles.crear')
    <x-ui.button href="{{ route('roles.create') }}" color="primary" icon="plus-lg" text="Nuevo Rol" />
    @endcan
</x-ui.toolbar>

<x-ui.card noPadding="true">
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th>Nombre</th>
                <th>Usuarios</th>
                <th>Fecha Creación</th>
                <th class="text-center">Acciones</th>
            </tr>
        </x-slot>
        @forelse($roles as $role)
        <tr>
            <td>
                <div class="fnc-udash__asset">
                    <span class="fnc-udash__ic" aria-hidden="true">
                        <x-fnc.icon name="shield-check" :size="16" />
                    </span>
                    <span class="fnc-udash__names">
                        <span class="fnc-udash__name">{{ $role->name }}</span>
                        <span class="fnc-udash__sub">Rol del sistema</span>
                    </span>
                </div>
            </td>
            <td class="text-nowrap">
                <x-fnc.pill :tone="$role->users_count > 0 ? 'info' : 'slate'">{{ $role->users_count }} {{ Str::plural('usuario', $role->users_count) }}</x-fnc.pill>
            </td>
            <td class="text-nowrap">
                <span class="fnc-udash__when">
                    <span class="fnc-udash__whenrel">{{ $role->created_at->diffForHumans() }}</span>
                    <span class="fnc-udash__sub">{{ $role->created_at->format('d/m/Y') }}</span>
                </span>
            </td>
            <td class="text-center text-nowrap">
                <div class="d-flex gap-1 justify-content-center">
                    @can('roles.editar')
                    @if($role->name === \App\Models\User::ROLE_ADMIN && !auth()->user()->isAdmin())
                        <x-ui.button type="button" outline="true" color="secondary" class="btn-sm" icon="pencil" disabled title="No puedes editar el rol de Administrador" />
                    @else
                        <x-ui.button href="{{ route('roles.edit', $role->id) }}" outline="true" color="warning" class="btn-sm" icon="pencil" title="Editar" />
                    @endif
                    @endcan
                    
                    @can('roles.eliminar')
                    @if($role->name === \App\Models\User::ROLE_ADMIN && !auth()->user()->isAdmin())
                        <x-ui.button type="button" size="sm" outline="true" color="secondary" icon="trash" disabled title="No puedes eliminar el rol de Administrador" />
                    @elseif($role->users_count == 0)
                    <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este rol?');">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" outline="true" color="danger" class="btn-sm" icon="trash" title="Eliminar" />
                    </form>
                    @else
                    <x-ui.button type="button" size="sm" outline="true" color="secondary" icon="trash" disabled title="No se puede eliminar porque tiene usuarios asociados" />
                    @endif
                    @endcan
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="text-center py-4 text-muted">No hay roles registrados.</td>
        </tr>
        @endforelse
    </x-ui.table>
</x-ui.card>
@endsection
