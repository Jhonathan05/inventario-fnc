@extends('layouts.inventario')

@section('title', 'Nuevo Equipo')

@section('content')
<x-ui.toolbar 
    title="Registrar Nuevo Equipo" 
    icon="plus-circle" 
    backRoute="{{ route('equipos.index') }}"
/>

<form method="POST" action="{{ route('equipos.store') }}" novalidate id="formEquipo">
    @csrf
    @php $equipo = new \App\Models\Equipo(); @endphp
    @include('equipos._form')

    <div class="d-flex justify-content-end gap-2 mt-2">
        <x-ui.button href="{{ route('equipos.index') }}" color="secondary" text="Cancelar" />
        <x-ui.button type="submit" color="primary" icon="floppy" text="Guardar Equipo" />
    </div>
</form>
@endsection
