{{--
    Tarjeta de ticket (equivalente a "Open Tickets" de la referencia):
    avatar con iniciales, nombre, texto y botón "Ver ›".
--}}
@props([
    'name',
    'text' => null,
    'initials' => null,
    'tone' => 'slate',
    'url' => null,
    'cta' => 'Ver',
])

@php
    $tag = $url ? 'a' : 'div';
    $base = trim((string) $name);
    $ini = $initials ?: ($base !== ''
        ? mb_strtoupper(mb_substr($base, 0, 1) . (mb_strpos($base, ' ') !== false ? mb_substr($base, mb_strpos($base, ' ') + 1, 1) : ''))
        : '?');
@endphp

<{{ $tag }}
    class="fnc-ticket fnc-tone-{{ $tone }}"
    @if ($url) href="{{ $url }}" @endif
>
    <span class="fnc-ticket__avatar" aria-hidden="true">{{ $ini }}</span>

    <span class="fnc-ticket__body">
        <span class="fnc-ticket__name">{{ $name }}</span>
        @if ($text)
            <span class="fnc-ticket__text">{{ $text }}</span>
        @endif
    </span>

    <span class="fnc-ticket__cta">
        {{ $cta }}
        <x-fnc.icon name="chevron-right" :size="13" />
    </span>
</{{ $tag }}>
