{{--
    Icono Lucide local (sprite en resources/views/partials/lucide-sprite.blade.php).

    Uso:  <x-fnc.icon name="house" />
          <x-fnc.icon name="triangle-alert" size="20" class="text-danger" label="Advertencia" />
--}}
@props([
    'name',
    'size' => 18,
    'label' => null,
    'stroke' => 1.75,
])

@php
    $symbol = 'fnc-i-' . $name;
@endphp

<svg {{ $attributes->merge(['class' => 'fnc-icon']) }}
     width="{{ $size }}"
     height="{{ $size }}"
     viewBox="0 0 24 24"
     fill="none"
     stroke="currentColor"
     stroke-width="{{ $stroke }}"
     stroke-linecap="round"
     stroke-linejoin="round"
     @if ($label !== null)
        role="img" aria-label="{{ $label }}"
     @else
        aria-hidden="true" focusable="false"
     @endif>
    {{-- Inset 10%: el glifo (y su trazo) nunca toca el borde del viewBox,
         así no hay recortes en las esquinas en iconos extensos. --}}
    <use href="#{{ $symbol }}" transform="translate(1.2 1.2) scale(0.9)"></use>
</svg>
