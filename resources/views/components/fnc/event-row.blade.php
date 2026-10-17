{{--
    Fila de evento / vencimiento (equivalente a "My Meetings" de la referencia):
    fecha a la izquierda, título con punto de color y meta, y flecha diagonal.
--}}
@props([
    'title',
    'day' => null,
    'kicker' => null,
    'meta' => null,
    'tone' => 'slate',
    'url' => null,
])

@php $tag = $url ? 'a' : 'div'; @endphp

<{{ $tag }}
    class="fnc-event fnc-tone-{{ $tone }}"
    @if ($url) href="{{ $url }}" @endif
>
    @if ($day)
        <span class="fnc-event__date">
            @if ($kicker)
                <span class="fnc-event__kicker">{{ $kicker }}</span>
            @endif
            <span class="fnc-event__day fnc-num">{{ $day }}</span>
        </span>
    @endif

    <span class="fnc-event__body">
        <span class="fnc-event__title">
            <span class="fnc-dot" aria-hidden="true"></span>
            <span class="fnc-event__title-text">{{ $title }}</span>
        </span>
        @if ($meta)
            <span class="fnc-event__meta">{{ $meta }}</span>
        @endif
    </span>

    <span class="fnc-event__arrow" aria-hidden="true">
        <x-fnc.icon name="arrow-up-right" :size="15" />
    </span>
</{{ $tag }}>
