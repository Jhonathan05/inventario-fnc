{{--
    Control segmentado por enlaces (preserva el resto de la query, incluido _tab).
--}}
@props([
    'items' => [],
    'current' => null,
    'param' => 'ventana',
    'ariaLabel' => 'Opciones',
])

<nav {{ $attributes->merge(['class' => 'fnc-segmented']) }} aria-label="{{ $ariaLabel }}">
    @foreach ($items as $item)
        @php $activo = (string) $current === (string) $item['value']; @endphp
        <a class="fnc-segmented__item {{ $activo ? 'is-active' : '' }}"
           href="{{ request()->fullUrlWithQuery([$param => $item['value']]) }}"
           @if ($activo) aria-current="true" @endif>
            @if (! empty($item['icon']))
                <x-fnc.icon :name="$item['icon']" :size="14" />
            @endif
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
