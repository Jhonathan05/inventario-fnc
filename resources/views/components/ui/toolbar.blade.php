{{--
    Cabecera de contenido de la referencia: título grande a la izquierda y
    acciones (slot + botones estándar) a la derecha.

    Se usa en todas las vistas de listado, así que mantiene la misma API:
      title, icon, createRoute, createText, exportRoute, importRoute, backRoute
    y los opcionales `subtitle` / `titleSuffix`.
--}}
@props([
    'title',
    'icon' => null,
    'createRoute' => null,
    'createText' => 'Nuevo',
    'exportRoute' => null,
    'importRoute' => null,
    'backRoute' => null,
])

<header class="fnc-page-head">
    <div class="fnc-page-head__titles">
        <h1 class="fnc-page-head__title">
            @if($icon)
                <i class="bi bi-{{ $icon }} fnc-page-head__icon {{ $iconClass ?? '' }}" aria-hidden="true"></i>
            @endif
            {{ $title }}
            {{ $titleSuffix ?? '' }}
        </h1>
        @if(isset($subtitle))
            <p class="fnc-page-head__sub">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="fnc-page-head__actions">
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
</header>
