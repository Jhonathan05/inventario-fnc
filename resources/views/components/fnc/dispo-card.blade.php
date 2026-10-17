{{--
    Tarjeta de la columna Disponibilidad: misma piel que las de categoría
    (160 x 110), con la cantidad de equipos disponibles del tipo de recurso y
    un botón que alterna el valor hacia los equipos en uso (y de vuelta).
--}}
@props([
    'label',
    'disponibles',
    'usados',
])

<div
    class="fnc-cat fnc-dispo fnc-tone-info"
    data-disponibles="{{ $disponibles }}"
    data-usados="{{ $usados }}"
>
    <span class="fnc-cat__icon" aria-hidden="true">
        <x-fnc.icon name="package" :size="20" />
    </span>

    <span class="fnc-cat__value fnc-num" data-dispo-value>{{ number_format($disponibles) }}</span>

    <span class="fnc-cat__body">
        <span class="fnc-cat__label">{{ $label }}</span>
        <span class="fnc-cat__meta" data-dispo-meta>equipos disponibles</span>
    </span>

    <button class="fnc-dispo__toggle" type="button" aria-pressed="false"
            title="Alternar entre disponibles y en uso"
            aria-label="Alternar entre equipos disponibles y en uso">
        <x-fnc.icon name="arrow-right-left" :size="13" />
    </button>
</div>
