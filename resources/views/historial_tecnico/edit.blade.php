@extends('layouts.inventario')

@section('title', 'Editar Evento Técnico')

@section('content')
<x-ui.toolbar 
    title="Editar Evento Técnico" 
    icon="pencil"
    iconClass="text-warning"
>
    <x-ui.button href="{{ $volverUrl }}" outline="true" color="secondary" icon="arrow-left" text="Volver" />
</x-ui.toolbar>

<x-ui.card class="border-0 shadow-sm">
    <form action="{{ route('historial-tecnico.update', $historialTecnico) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="return_to" value="{{ $volverUrl }}">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-medium">Equipo</label>
                <input type="hidden" name="equipo_id" value="{{ $historialTecnico->equipo_id }}">
                <input type="text" class="form-control" readonly
                       value="{{ $historialTecnico->equipo?->nombre_equipo }} ({{ $historialTecnico->equipo?->serial }})">
            </div>

            <x-ui.select name="tipo_evento" id="tipo_evento" label="Tipo de Evento" required="true" containerClass="col-md-6">
                    @foreach($tiposEvento as $key => $label)
                        <option value="{{ $key }}"
                            {{ old('tipo_evento', $historialTecnico->tipo_evento) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-ui.select>

            <x-ui.input type="date" name="fecha_evento" id="fecha_evento" label="Fecha del Evento" required="true" value="{{ old('fecha_evento', $historialTecnico->fecha_evento?->format('Y-m-d')) }}" max="{ date('Y-m-d') }" containerClass="col-md-6" />

            <x-ui.input type="text" name="usuario_responsable" id="usuario_responsable" label="Técnico Responsable" required="true" value="{{ old('usuario_responsable', $historialTecnico->usuario_responsable) }}" maxlength="150" containerClass="col-md-6" />

            <x-ui.textarea name="observaciones" id="observaciones" label="Observaciones" rows="3" required="true" value="{{ old('observaciones', $historialTecnico->observaciones) }}" containerClass="col-12" />
        </div>

        <hr class="my-4">

        <div class="d-flex gap-2">
            <x-ui.button type="submit" color="warning" class="text-white" icon="check-lg" text="Actualizar Evento" />
            <x-ui.button href="{{ $volverUrl }}" outline="true" color="secondary" text="Cancelar" />
        </div>
    </form>
</x-ui.card>
@endsection
