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
                <th class="fnc-fhd-only">Correo verificado</th>
                <th class="fnc-fhd-only">Cambio de clave</th>
                <th>Fecha Creación</th>
                <th class="text-center">Acciones</th>
            </tr>
        </x-slot>
        
        @forelse($users as $user)
        @php
            $inicialesUser = collect(explode(' ', trim((string) $user->name)))
                ->reject(fn ($p) => mb_strlen($p) < 2)
                ->map(fn ($p) => mb_substr($p, 0, 1))
                ->take(2)
                ->implode('');
        @endphp
        <tr>
            <td>
                <span class="fnc-udash__user">
                    <span class="fnc-udash__avatar" aria-hidden="true">{{ $inicialesUser ?: '·' }}</span>
                    <span class="fnc-udash__uname">
                        <span class="fnc-udash__unombre">{{ $user->name }}</span>
                    </span>
                </span>
            </td>
            <td class="text-nowrap"><span class="fnc-udash__code">{{ $user->email }}</span></td>
            <td class="text-nowrap">
                @if($user->roles->isNotEmpty())
                    <x-fnc.pill tone="info">{{ $user->roles->first()->name }}</x-fnc.pill>
                    @foreach($user->roles->skip(1) as $rolExtra)
                        <span class="fnc-fhd-only d-inline-block ms-1">
                            <x-fnc.pill tone="info">{{ $rolExtra->name }}</x-fnc.pill>
                        </span>
                    @endforeach
                @else
                    <x-fnc.pill tone="slate">Sin Rol</x-fnc.pill>
                @endif
            </td>
            <td class="fnc-fhd-only text-nowrap">
                <x-fnc.pill :tone="$user->email_verified_at ? 'success' : 'warning'" dot>
                    {{ $user->email_verified_at ? 'Verificado' : 'Pendiente' }}
                </x-fnc.pill>
            </td>
            <td class="fnc-fhd-only text-nowrap">
                @if($user->force_password_change)
                    <x-fnc.pill tone="warning">Forzado</x-fnc.pill>
                @else
                    <span class="fnc-udash__sub">Normal</span>
                @endif
            </td>
            <td class="text-nowrap">
                <span class="fnc-udash__when">
                    <span class="fnc-udash__whenrel">{{ $user->created_at->diffForHumans() }}</span>
                    <span class="fnc-udash__sub">{{ $user->created_at->format('d/m/Y') }}</span>
                </span>
            </td>
            <td class="text-center text-nowrap">
                <div class="d-flex gap-1 justify-content-center">
                    @can('roles.editar')
                    @if($user->hasRole(\App\Models\User::ROLE_ADMIN) && !auth()->user()->isAdmin())
                        <x-ui.button type="button" size="sm" outline="true" color="secondary" icon="pencil" disabled title="No puedes editar a un administrador" />
                    @else
                        <x-ui.button href="{{ route('usuarios.edit', $user->id) }}" size="sm" outline="true" color="warning" icon="pencil" title="Editar" />
                    @endif
                    @endcan
                    
                    @can('roles.eliminar')
                    @if($user->hasRole(\App\Models\User::ROLE_ADMIN) && !auth()->user()->isAdmin())
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
            <td colspan="7" class="text-center py-4 text-muted">No hay usuarios registrados.</td>
        </tr>
        @endforelse
    </x-ui.table>
</x-ui.card>
@endsection
