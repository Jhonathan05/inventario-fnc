@extends('layouts.inventario')

@section('title', 'Editar Equipo')

@section('content')
@php
    $backUrl = route('equipos.index');
    $returnTo = request('return_to');
    if (is_string($returnTo) && $returnTo !== '') {
        $path = parse_url($returnTo, PHP_URL_PATH);
        if (is_string($path)) {
            $path = strtolower($path);
            if (str_contains($path, '/equipos') || str_contains($path, '/historial-tecnico')) {
                $backUrl = $returnTo;
            }
        }
    }
@endphp
<x-ui.toolbar 
    title="Editar Equipo: {{ $equipo->nombre_equipo }}" 
    icon="pencil" 
    backRoute="{{ $backUrl }}"
/>

<form method="POST" action="{{ route('equipos.update', ['equipo' => $equipo->id, 'return_to' => request('return_to')]) }}" novalidate id="formEquipo">
    @csrf
    @method('PUT')
    @include('equipos._form')

    <div class="d-flex justify-content-end gap-2 mt-2">
        <x-ui.button href="{{ $backUrl }}" color="secondary" text="Cancelar" />
        <x-ui.button type="submit" color="primary" icon="floppy" text="Actualizar Equipo" />
    </div>
</form>
@endsection
