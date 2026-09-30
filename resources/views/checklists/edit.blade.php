@extends('layouts.inventario')

@section('title', 'Editar Checklist')

@section('content')
@php
    $equipoBackId = old('equipo_id', $checklist->equipo_id);
    $volverUrl = $equipoBackId ? route('equipos.show', $equipoBackId) : route('checklists.index');
@endphp
<x-ui.toolbar 
    title="Editar Checklist" 
    icon="pencil"
    iconColor="warning"
>
    <x-ui.button href="{{ $volverUrl }}" outline="true" color="secondary" icon="arrow-left" text="Volver" id="btnVolverChecklist" />
</x-ui.toolbar>

<form method="POST" action="{{ route('checklists.update', $checklist) }}" novalidate>
    @csrf
    @method('PUT')
    @include('checklists._form')

    <div class="d-flex justify-content-end gap-2 flex-wrap">
        <x-ui.button href="{{ $volverUrl }}" color="secondary" text="Cancelar" id="btnCancelarChecklist" />
        <x-ui.button type="submit" color="primary" icon="floppy" text="Actualizar" />
    </div>
</form>

<script>
    (() => {
        const equipoSelect = document.querySelector('select[name="equipo_id"]');
        if (!equipoSelect) return;

        const defaultUrl = @json(route('checklists.index'));
        const equipoShowTemplate = @json(route('equipos.show', ['equipo' => '__ID__']));
        const targets = [
            document.getElementById('btnVolverChecklist'),
            document.getElementById('btnCancelarChecklist'),
        ].filter(Boolean);

        const updateBackLinks = () => {
            const id = equipoSelect.value;
            const href = id ? equipoShowTemplate.replace('__ID__', id) : defaultUrl;
            targets.forEach((el) => {
                el.setAttribute('href', href);
            });
        };

        equipoSelect.addEventListener('change', updateBackLinks);
        updateBackLinks();
    })();
</script>
@endsection
