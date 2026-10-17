{{--
    Minigráfica (sparkline) en SVG puro.
    `points`: [{ label, value }]
--}}
@props([
    'points' => [],
    'tone' => 'brand',
    'label' => null,
])

@php
    $valores = array_map(static fn ($p) => (float) ($p['value'] ?? 0), $points);
    $n = count($valores);
    $maximo = max(1.0, $valores ? max($valores) : 0.0);
    $largo = 100.0;
    $alto = 28.0;

    $coords = [];
    foreach ($valores as $i => $valor) {
        $px = $n > 1 ? ($largo * ($i / ($n - 1))) : $largo / 2;
        $py = $alto - (($valor / $maximo) * ($alto - 4)) - 2;
        $coords[] = round($px, 2) . ',' . round($py, 2);
    }

    $linea = $coords !== [] ? 'M' . implode(' L', $coords) : '';
    $area = $coords !== [] ? $linea . ' L' . $largo . ',' . $alto . ' L0,' . $alto . ' Z' : '';
@endphp

<span {{ $attributes->merge(['class' => 'fnc-spark fnc-tone-' . $tone]) }}>
    <svg viewBox="0 0 {{ $largo }} {{ $alto }}" preserveAspectRatio="none"
         role="img"
         aria-label="{{ $label ?? 'Tendencia de los últimos ' . $n . ' períodos' }}">
        @if ($area)
            <path class="fnc-spark__area" d="{{ $area }}" />
            <path class="fnc-spark__line" d="{{ $linea }}" />
        @endif
    </svg>
</span>
