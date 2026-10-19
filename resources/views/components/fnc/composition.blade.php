{{--
    Gráfico de composición "Por categoría": dona Chart.js 4 con tooltip
    interactivo. Las categorías no se rotulan fuera del lienzo: se revelan al
    pasar el cursor (tooltip) y quedan descritas para lectores de pantalla vía
    aria-label.

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
    $datos = collect($segments)
        ->filter(fn ($s) => (float) ($s['value'] ?? 0) > 0)
        ->values()
        ->map(function ($s) use ($suma) {
            $valor = (float) $s['value'];

            return [
                'label' => (string) $s['label'],
                'value' => $valor,
                'color' => (string) ($s['color'] ?? 'var(--fnc-brand)'),
                'url' => $s['url'] ?? null,
                'pct' => $suma > 0 ? round($valor / $suma * 100, 1) : 0.0,
            ];
        });
    $id = 'fnc-compo-' . uniqid();
@endphp

<div class="fnc-compo">
    <div class="fnc-compo__figure">
        <canvas id="{{ $id }}"
                role="img"
                aria-label="{{ $ariaLabel ?? 'Distribución de ' . number_format($suma) . ' ' . $centerLabel }}"
                data-fnc-compo="{{ $datos->toJson(JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                data-fnc-compo-center="{{ number_format($suma) }}|{{ $centerLabel }}"></canvas>
        <div class="fnc-compo__center" aria-hidden="true">
            <strong>{{ number_format($suma) }}</strong>
            <span>{{ $centerLabel }}</span>
        </div>
    </div>

    <ul class="fnc-compo__chips" aria-label="Porcentajes por categoría">
        @foreach ($datos as $segmento)
            <li class="fnc-compo__chip" title="{{ $segmento['label'] }}" aria-label="{{ $segmento['label'] }}: {{ number_format((float) $segmento['value']) }} activos">
                <i class="fnc-compo__swatch" style="background: {{ $segmento['color'] }}" aria-hidden="true"></i>
                <span>{{ $segmento['pct'] }}%</span>
            </li>
        @endforeach
    </ul>
</div>

@push('scripts')
    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            // Dona "Por categoría": se construye desde los data-attributes que
            // deja el componente, fuera de la lógica del dashboard.
            function fncInitComposiciones() {
                if (typeof window.Chart !== 'function') { return; }

                var superficie = getComputedStyle(document.documentElement)
                    .getPropertyValue('--fnc-surface') || '#fff';

                document.querySelectorAll('canvas[data-fnc-compo]').forEach(function (lienzo) {
                    if (lienzo.dataset.fncCompoReady) { return; }
                    lienzo.dataset.fncCompoReady = '1';

                    var segmentos;
                    try { segmentos = JSON.parse(lienzo.dataset.fncCompo); } catch (e) { return; }
                    if (!Array.isArray(segmentos) || segmentos.length === 0) { return; }

                    new window.Chart(lienzo, {
                        type: 'doughnut',
                        data: {
                            labels: segmentos.map(function (s) { return s.label; }),
                            datasets: [{
                                data: segmentos.map(function (s) { return s.value; }),
                                backgroundColor: segmentos.map(function (s) { return s.color; }),
                                borderWidth: 2,
                                borderColor: superficie.trim() || '#fff',
                                borderRadius: 5,
                                hoverOffset: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '68%',
                            layout: { padding: 4 },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function (ctx) {
                                            var total = ctx.dataset.data.reduce(function (a, b) { return a + Number(b); }, 0);
                                            var pct = total > 0 ? Math.round((Number(ctx.parsed) / total) * 100) : 0;
                                            return ' ' + ctx.label + ': ' + Number(ctx.parsed).toLocaleString('es-CO') + ' (' + pct + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', fncInitComposiciones);
            // Fallback por si el CDN llegó después del DOMContentLoaded.
            window.addEventListener('load', fncInitComposiciones);
        </script>
    @endonce
@endpush
