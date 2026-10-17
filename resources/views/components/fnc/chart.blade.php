{{--
    Gráfico de línea/área con ejes y puntos accesibles.
    `points`: [{ label, value }]
--}}
@props([
    'points' => [],
    'tone' => 'brand',
    'unit' => '',
    'ariaLabel' => null,
])

@php
    $valores = array_map(static fn ($p) => (float) ($p['value'] ?? 0), $points);
    $n = count($valores);
    $maximo = max(1.0, $valores ? max($valores) : 0.0);

    $ancho = 600.0;
    $alto = 190.0;
    $padX = 10.0;
    $padTop = 14.0;
    $padBottom = 24.0;
    $anchoUtil = $ancho - ($padX * 2);
    $altoUtil = $alto - $padTop - $padBottom;
    $base = $padTop + $altoUtil;

    $x = static fn (int $i): float => $n > 1 ? $padX + ($anchoUtil * ($i / ($n - 1))) : $padX + ($anchoUtil / 2);
    $y = static fn (float $v): float => $base - (($v / $maximo) * $altoUtil);

    $coords = [];
    foreach ($valores as $i => $valor) {
        $coords[] = round($x($i), 2) . ',' . round($y($valor), 2);
    }

    $linea = $coords !== [] ? 'M' . implode(' L', $coords) : '';
    $area = $coords !== [] ? $linea . ' L' . round($x($n - 1), 2) . ',' . $base . ' L' . round($x(0), 2) . ',' . $base . ' Z' : '';
@endphp

<div {{ $attributes->merge(['class' => 'fnc-chart fnc-tone-' . $tone]) }}>
    <svg viewBox="0 0 {{ $ancho }} {{ $alto }}"
         role="img"
         aria-label="{{ $ariaLabel ?? 'Evolución de los últimos ' . $n . ' períodos' }}">
        @foreach ([1.0, 0.75, 0.5, 0.25] as $nivel)
            <line class="fnc-chart__grid"
                  x1="{{ $padX }}" x2="{{ $ancho - $padX }}"
                  y1="{{ round($padTop + $altoUtil * (1 - $nivel), 2) }}"
                  y2="{{ round($padTop + $altoUtil * (1 - $nivel), 2) }}" />
        @endforeach
        <line class="fnc-chart__grid"
              x1="{{ $padX }}" x2="{{ $ancho - $padX }}"
              y1="{{ $base }}" y2="{{ $base }}" />

        @if ($area)
            <path class="fnc-chart__area" d="{{ $area }}" />
            <path class="fnc-chart__line" d="{{ $linea }}" />
        @endif

        @foreach ($valores as $i => $valor)
            <circle class="fnc-chart__dot" cx="{{ round($x($i), 2) }}" cy="{{ round($y($valor), 2) }}" r="3.6">
                <title>{{ ($points[$i]['label'] ?? ('#' . ($i + 1))) . ': ' . number_format($valor) . $unit }}</title>
            </circle>
        @endforeach
    </svg>

    <div class="fnc-chart__labels" aria-hidden="true">
        @foreach ($points as $punto)
            <span>{{ $punto['label'] ?? '' }}</span>
        @endforeach
    </div>

    <span class="fnc-sr">
        @foreach ($points as $punto)
            {{ $punto['label'] ?? '' }}: {{ number_format((float) ($punto['value'] ?? 0)) }}{{ $unit }}.
        @endforeach
    </span>
</div>
