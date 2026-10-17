{{--
    Gráfico de dona en SVG puro (sin dependencias JS).
    `segments`: [{ label, value, color, url?, pct? }]
--}}
@props([
    'segments' => [],
    'total' => null,
    'centerLabel' => 'Total',
    'ariaLabel' => null,
])

@php
    $suma = $total !== null ? (float) $total : (float) array_sum(array_column($segments, 'value'));
    $offset = 0.0;
    $radio = 15.9155; // circunferencia = 100 → los porcentajes se usan directo
@endphp

<div class="fnc-donut">
    <div class="fnc-donut__figure">
        <svg viewBox="0 0 42 42"
             role="img"
             aria-label="{{ $ariaLabel ?? 'Distribución de ' . number_format($suma) . ' ' . $centerLabel }}">
            <circle class="fnc-donut__track" cx="21" cy="21" r="{{ $radio }}" />
            @foreach ($segments as $segmento)
                @php
                    $largo = $suma > 0 ? round(((float) $segmento['value'] / $suma) * 100, 4) : 0.0;
                @endphp
                @if ($largo > 0)
                    <circle class="fnc-donut__seg"
                            cx="21" cy="21" r="{{ $radio }}"
                            stroke="{{ $segmento['color'] ?? 'var(--fnc-brand)' }}"
                            stroke-dasharray="{{ $largo }} {{ round(100 - $largo, 4) }}"
                            stroke-dashoffset="{{ round(-$offset, 4) }}" />
                    @php $offset += $largo; @endphp
                @endif
            @endforeach
        </svg>
        <div class="fnc-donut__center" aria-hidden="true">
            <strong>{{ number_format($suma) }}</strong>
            <span>{{ $centerLabel }}</span>
        </div>
    </div>

    <ul class="fnc-legend">
        @foreach ($segments as $segmento)
            <li class="fnc-legend__row">
                <span class="fnc-swatch" style="background: {{ $segmento['color'] ?? 'var(--fnc-brand)' }}" aria-hidden="true"></span>
                @if (! empty($segmento['url']))
                    <a href="{{ $segmento['url'] }}" class="fnc-truncate">{{ $segmento['label'] }}</a>
                @else
                    <span class="fnc-truncate">{{ $segmento['label'] }}</span>
                @endif
                <span class="fnc-legend__value">
                    {{ number_format((int) $segmento['value']) }}
                    @isset($segmento['pct'])
                        <small class="fnc-muted">({{ $segmento['pct'] }}%)</small>
                    @endisset
                </span>
            </li>
        @endforeach
    </ul>
</div>
