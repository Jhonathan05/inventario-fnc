@extends('layouts.inventario')

@section('title', 'Nuevo Evento Técnico')

@section('content')
<x-ui.toolbar 
    title="Registrar Evento Técnico" 
    icon="plus-circle"
>
    <x-ui.button href="{{ $volverUrl }}" outline="true" color="secondary" icon="arrow-left" text="Volver" />
</x-ui.toolbar>

<x-ui.card class="border-0 shadow-sm">
    <form action="{{ route('historial-tecnico.store') }}" method="POST">
        @csrf

        <div class="row g-3">
            {{-- Equipo --}}
            <x-ui.select name="equipo_id" id="equipo_id" label="Equipo" required="true" containerClass="col-md-6">
                    <option value="">Seleccione un equipo...</option>
                    @foreach($equipos as $eq)
                        <option value="{{ $eq->id }}"
                            {{ (old('equipo_id', $equipo?->id) == $eq->id) ? 'selected' : '' }}>
                            {{ $eq->nombre_equipo }} ({{ $eq->serial }})
                        </option>
                    @endforeach
                </x-ui.select>

            {{-- Tipo de evento --}}
            <x-ui.select name="tipo_evento" id="tipo_evento" label="Tipo de Evento" required="true" containerClass="col-md-6">
                    <option value="">Seleccione...</option>
                    @foreach($tiposEvento as $key => $label)
                        <option value="{{ $key }}" {{ old('tipo_evento') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </x-ui.select>

            {{-- Fecha del evento --}}
            <x-ui.input type="date" name="fecha_evento" id="fecha_evento" label="Fecha del Evento" required="true" value="{{ old('fecha_evento', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" containerClass="col-md-6" />

            {{-- Técnico Responsable --}}
            <x-ui.input type="text" name="usuario_responsable" id="usuario_responsable" label="Técnico Responsable" required="true" value="{{ old('usuario_responsable', $responsableSugerido) }}" maxlength="150" containerClass="col-md-6" />

            {{-- Observaciones --}}
            <x-ui.textarea name="observaciones" id="observaciones" label="Observaciones" rows="3" required="true" placeholder="Describe el requerimiento o incidente..." containerClass="col-12" />

        </div>

        <hr class="my-4">

        <div class="d-flex gap-2">
            <x-ui.button type="submit" color="primary" icon="check-lg" text="Guardar Evento" />
            <x-ui.button href="{{ $volverUrl }}" outline="true" color="secondary" text="Cancelar" />
        </div>
    </form>
</x-ui.card>
@endsection
