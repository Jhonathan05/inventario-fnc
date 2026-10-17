{{--
    Tarjeta KPI. Si recibe `url` se renderiza como enlace (drill-down).
    `spark` acepta la serie [{label, value}] para dibujar una minigráfica.
--}}
@props([
    'label',
    'value',
    'meta' => null,
    'icon' => 'activity',
    'tone' => 'brand',
    'url' => null,
    'pill' => null,
    'spark' => null,
])

@php
    $tag = $url ? 'a' : 'div';
    $display = is_numeric($value) ? number_format((float) $value) : $value;
@endphp

<{{ $tag }}
    class="fnc-kpi fnc-tone-{{ $tone }}"
    @if ($url) href="{{ $url }}" @endif
>
    <span class="fnc-kpi__rail" aria-hidden="true"></span>

    <span class="fnc-kpi__top">
        <span class="fnc-kpi__icon">
            <x-fnc.icon :name="$icon" :size="17" />
        </span>
        <span class="fnc-kpi__label">{{ $label }}</span>
    </span>

    <span class="fnc-kpi__value fnc-num">{{ $display }}</span>

    @if ($spark)
        <span class="fnc-kpi__spark">
            <x-fnc.sparkline :points="$spark" :tone="$tone" />
        </span>
    @endif

    @if ($meta || $pill)
        <span class="fnc-kpi__meta">
            @if ($pill)
                <span class="fnc-pill fnc-tone-{{ $tone }}">{{ $pill }}</span>
            @endif
            {{ $meta }}
        </span>
    @endif
</{{ $tag }}>
