{{--
    Barras horizontales con etiqueta y valor.
    `rows`: [{ label, value, pct, tone?, url?, max?, hint? }]
--}}
@props([
    'rows' => [],
    'showPct' => false,
])

<ul {{ $attributes->merge(['class' => 'fnc-bars']) }}>
    @foreach ($rows as $fila)
        <li class="fnc-bar fnc-tone-{{ $fila['tone'] ?? 'brand' }}">
            <div class="fnc-bar__head">
                <span class="fnc-bar__label">
                    @if (! empty($fila['url']))
                        <a href="{{ $fila['url'] }}">{{ $fila['label'] }}</a>
                    @else
                        {{ $fila['label'] }}
                    @endif
                </span>
                <span class="fnc-bar__value">
                    {{ number_format((float) $fila['value']) }}
                    @if (! empty($fila['max']))
                        <small class="fnc-muted">/ {{ number_format((float) $fila['max']) }}</small>
                    @endif
                    @if ($showPct && isset($fila['pct']))
                        <small class="fnc-muted">{{ $fila['pct'] }}%</small>
                    @endif
                </span>
            </div>
            <div class="fnc-bar__track" role="presentation">
                <div class="fnc-bar__fill fnc-bar__fill--gradient"
                     style="width: {{ max(0, min(100, (float) ($fila['pct'] ?? 0))) }}%"></div>
            </div>
        </li>
    @endforeach
</ul>
