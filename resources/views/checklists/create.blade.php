@extends('layouts.inventario')

@section('title', 'Nuevo Checklist')

@section('content')
@php
    $equipoBackId = old('equipo_id', request('equipo_id'));
    $volverUrl = $equipoBackId ? route('equipos.edit', $equipoBackId) : route('checklists.index');
@endphp
<x-ui.toolbar 
    title="Nuevo Checklist Técnico" 
    icon="plus-circle"
>
    <x-ui.button href="{{ $volverUrl }}" outline="true" color="secondary" icon="arrow-left" text="Volver" id="btnVolverChecklist" />
</x-ui.toolbar>

<form method="POST" action="{{ route('checklists.store') }}" novalidate>
    @csrf
    @php $checklist = new \App\Models\Checklist(); @endphp
    @include('checklists._form')

    <div class="d-flex justify-content-end gap-2 flex-wrap">
        <x-ui.button href="{{ $volverUrl }}" color="secondary" text="Cancelar" id="btnCancelarChecklist" />
        <x-ui.button type="submit" color="primary" icon="floppy" text="Guardar" />
    </div>
</form>

<script>
    (() => {
        const equipoSelect = document.querySelector('select[name="equipo_id"]');
        if (!equipoSelect) return;

        const defaultUrl = @json(route('checklists.index'));
        const equipoEditTemplate = @json(route('equipos.edit', ['equipo' => '__ID__']));
        const targets = [
            document.getElementById('btnVolverChecklist'),
            document.getElementById('btnCancelarChecklist'),
        ].filter(Boolean);

        const updateBackLinks = () => {
            const id = equipoSelect.value;
            const href = id ? equipoEditTemplate.replace('__ID__', id) : defaultUrl;
            targets.forEach((el) => {
                el.setAttribute('href', href);
            });
        };

        equipoSelect.addEventListener('change', updateBackLinks);
        updateBackLinks();
    })();
</script>
@endsection
