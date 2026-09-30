@props([
    'title',
    'icon' => null,
    'createRoute' => null,
    'createText' => 'Nuevo',
    'exportRoute' => null,
    'importRoute' => null,
    'backRoute' => null,
])

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-0">
            @if($icon)
                <i class="bi bi-{{ $icon }} me-2 {{ $iconClass ?? 'text-primary' }}"></i>
            @endif
            {{ $title }}
            {{ $titleSuffix ?? '' }}
        </h4>
        @if(isset($subtitle))
            <p class="text-muted mb-0 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-2 w-100 justify-content-start justify-content-md-end" style="max-width: 100%;">
        {{ $slot }}
        
        @if($backRoute)
            <x-ui.button href="{{ $backRoute }}" color="secondary" outline="true" icon="arrow-left" text="Volver" />
        @endif

        @if($exportRoute)
            <x-ui.button href="{{ $exportRoute }}" color="success" icon="file-earmark-excel" text="Exportar" />
        @endif
        
        @if($importRoute)
            <x-ui.button href="{{ $importRoute }}" color="info" class="text-white" icon="cloud-arrow-up" text="Importar" />
        @endif

        @if($createRoute)
            <x-ui.button href="{{ $createRoute }}" color="primary" icon="plus-lg" text="{{ $createText }}" />
        @endif
    </div>
</div>
