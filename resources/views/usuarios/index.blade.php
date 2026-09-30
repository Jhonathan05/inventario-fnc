@extends('layouts.inventario')

@section('title', 'Usuarios Sistema')

@section('content')
<x-ui.toolbar 
    title="Usuarios del Sistema" 
    icon="person-badge" 
    createRoute="{{ auth()->user()->can('roles.crear') ? route('usuarios.create') : null }}" 
    createText="Nuevo Usuario" 
/>

<x-ui.card noPadding="true">
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Rol Asignado</th>
                <th>Fecha Creación</th>
                <th class="text-center">Acciones</th>
            </tr>
        </x-slot>
        
        @forelse($users as $user)
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-weight: bold;">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="fw-bold text-dark">{{ $user->name }}</div>
                    </div>
                </div>
            </td>
            <td class="text-nowrap">{{ $user->email }}</td>
            <td class="text-nowrap">
                @if($user->roles->isNotEmpty())
                    <x-ui.badge color="info text-dark rounded-pill px-3">{{ $user->roles->first()->name }}</x-ui.badge>
                @else
                    <x-ui.badge color="secondary rounded-pill px-3">Sin Rol</x-ui.badge>
                @endif
            </td>
            <td class="text-muted small text-nowrap">{{ $user->created_at->format('d/m/Y') }}</td>
            <td class="text-center text-nowrap">
                <div class="d-flex gap-1 justify-content-center">
                    @can('roles.editar')
                    @if($user->hasRole('Administrador') && !auth()->user()->hasRole('Administrador'))
                        <x-ui.button type="button" size="sm" outline="true" color="secondary" icon="pencil" disabled title="No puedes editar a un administrador" />
                    @else
                        <x-ui.button href="{{ route('usuarios.edit', $user->id) }}" size="sm" outline="true" color="warning" icon="pencil" title="Editar" />
                    @endif
                    @endcan
                    
                    @can('roles.eliminar')
                    @if($user->hasRole('Administrador') && !auth()->user()->hasRole('Administrador'))
                        <x-ui.button type="button" size="sm" outline="true" color="secondary" icon="trash" disabled title="No puedes eliminar a un administrador" />
                    @elseif(auth()->id() !== $user->id)
                    <form action="{{ route('usuarios.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este usuario?');">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" size="sm" outline="true" color="danger" icon="trash" title="Eliminar" />
                    </form>
                    @else
                    <x-ui.button type="button" size="sm" outline="true" color="secondary" icon="trash" disabled title="No puedes eliminar tu propio usuario" />
                    @endif
                    @endcan
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-center py-4 text-muted">No hay usuarios registrados.</td>
        </tr>
        @endforelse
    </x-ui.table>
</x-ui.card>
@endsection
