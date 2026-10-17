{{--
    Fila del centro de alertas: siempre accionable (lleva a la vista filtrada).
--}}
@props([
    'tone' => 'info',
    'icon' => 'triangle-alert',
    'title',
    'meta' => null,
    'count' => null,
    'url' => null,
])

<a {{ $attributes->merge(['class' => 'fnc-alert-row fnc-tone-' . $tone]) }} href="{{ $url }}">
    <span class="fnc-alert-row__icon" aria-hidden="true">
        <x-fnc.icon :name="$icon" :size="17" />
    </span>

    <span class="fnc-alert-row__body">
        <span class="fnc-alert-row__title">{{ $title }}</span>
        @if ($meta)
            <span class="fnc-alert-row__meta">{{ $meta }}</span>
        @endif
    </span>

    @if ($count !== null)
        <span class="fnc-alert-row__count fnc-num">{{ number_format((int) $count) }}</span>
    @endif

    <x-fnc.icon name="chevron-right" :size="16" />
</a>
