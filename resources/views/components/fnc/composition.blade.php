{{--
    Gráfico de composición "Por categoría" (Lote 20): dona dibujada con
    Chart.js 4 (bordes redondeados, tooltip interactivo) sobre un legendario
    generado en el servidor. Si el CDN no carga, el legendario sigue mostrando
    la distribución completa: el dato nunca se pierde.

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
        ->map(fn ($s) => [
            'label' => (string) $s['label'],
            'value' => (float) $s['value'],
            'color' => (string) ($s['color'] ?? 'var(--fnc-brand)'),
            'url' => $s['url'] ?? null,
        ]);
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

    <ul class="fnc-legend fnc-compo__legend">
        @foreach ($segments as $segmento)
            <li class="fnc-legend__row{{ (float) ($segmento['value'] ?? 0) <= 0 ? ' is-empty' : '' }}">
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
