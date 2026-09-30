@extends('layouts.inventario')
@section('title', 'Suscripciones')

@section('content')
<div class="container-fluid">
    <x-ui.toolbar 
        title="Gestión de Suscripciones" 
        icon="calendar-check"
    >
        @can('suscripciones.crear')
        <x-ui.button href="{{ route('suscripciones.create') }}" color="primary" icon="plus-lg" text="Nueva Suscripción" />
        @endcan
    </x-ui.toolbar>
    
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <x-ui.card class="bg-light">
                <h6 class="text-muted mb-1">Total Suscripciones</h6>
                <h3 class="mb-0 text-primary">0</h3>
            </x-ui.card>
        </div>
    </div>

    <x-ui.card noPadding="true" class="shadow-sm border-0">
        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <th>Nombre</th>
                    <th>Fabricante</th>
                    <th>Vencimiento</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </x-slot>
            <tr>
                <td colspan="5" class="text-center text-muted py-4">No hay suscripciones registradas</td>
            </tr>
        </x-ui.table>
    </x-ui.card>
</div>
@endsection
