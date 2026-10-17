{{--
    Tarjeta de categoría del tablero (calcada de las "My Tasks" de la
    referencia): icono suelto arriba a la izquierda, cifra arriba a la derecha
    y debajo el título con su mini descripción, todo sobre el pastel del tono.
--}}
@props([
    'label',
    'value',
    'meta' => null,
    'icon' => 'activity',
    'tone' => 'brand',
    'url' => null,
])

@php
    $tag = $url ? 'a' : 'div';
    $display = is_numeric($value) ? number_format((float) $value) : $value;
@endphp

<{{ $tag }}
    class="fnc-cat fnc-tone-{{ $tone }}"
    @if ($url) href="{{ $url }}" @endif
>
    <span class="fnc-cat__icon" aria-hidden="true">
        <x-fnc.icon :name="$icon" :size="18" />
    </span>

    <span class="fnc-cat__value fnc-num">{{ $display }}</span>

    <span class="fnc-cat__body">
        <span class="fnc-cat__label">{{ $label }}</span>
        @if ($meta)
            <span class="fnc-cat__meta">{{ $meta }}</span>
        @endif
    </span>
</{{ $tag }}>
