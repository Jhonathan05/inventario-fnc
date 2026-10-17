{{--
    Panel de vidrio del sistema de diseño.
    Slots: contenido por defecto, `actions` (cabecera) y `footer`.
--}}
@props([
    'title' => null,
    'sub' => null,
    'icon' => null,
    'flush' => false,
])

<section {{ $attributes->merge(['class' => 'fnc-panel' . ($flush ? ' fnc-panel--nopad' : '')]) }}>
    @if ($title || isset($actions))
        <header class="fnc-panel__head">
            <div class="fnc-panel__titles">
                @if ($title)
                    <h3 class="fnc-panel__title">
                        @if ($icon)
                            <x-fnc.icon :name="$icon" :size="16" />
                        @endif
                        {{ $title }}
                    </h3>
                @endif
                @if ($sub)
                    <p class="fnc-panel__sub">{{ $sub }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="fnc-panel__actions">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="fnc-panel__body">
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="fnc-panel__foot">{{ $footer }}</footer>
    @endisset
</section>
