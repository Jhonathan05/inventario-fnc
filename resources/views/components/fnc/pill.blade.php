{{--
    Etiqueta de estado. `tone`: brand|success|warning|danger|info|violet|slate.
--}}
@props([
    'tone' => 'slate',
    'dot' => false,
    'plain' => false,
])

<span {{ $attributes->merge(['class' => 'fnc-pill fnc-tone-' . $tone . ($plain ? ' fnc-pill--plain' : '')]) }}>
    @if ($dot)
        <span class="fnc-dot" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
