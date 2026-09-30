@props([
    'action' => '',
    'method' => 'GET',
    'clearRoute' => null,
    'id' => null,
])

<x-ui.card noPadding="true" class="mb-4">
    <div class="p-3">
        <form @if($id) id="{{ $id }}" @endif method="{{ $method }}" action="{{ $action }}" class="row g-3 align-items-end">
            @if(request()->has('_tab'))
                <input type="hidden" name="_tab" value="{{ request('_tab') }}">
            @endif
            
            {{ $slot }}

            <div class="col-12 col-md-auto ms-auto d-flex gap-2">
                <x-ui.button type="submit" color="primary" class="flex-fill" icon="funnel" text="Filtrar" />
                @if($clearRoute && count(array_filter(request()->query())) > 0)
                    <x-ui.button href="{{ $clearRoute }}" color="secondary" outline="true" icon="eraser" title="Limpiar Filtros" />
                @endif
            </div>
        </form>
    </div>
</x-ui.card>
