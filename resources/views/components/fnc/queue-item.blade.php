{{--
    Elemento de lista operativa (cola). Si recibe `url` es navegable.
--}}
@props([
    'title',
    'meta' => null,
    'icon' => 'activity',
    'avatar' => null,
    'tone' => 'slate',
    'value' => null,
    'pill' => null,
    'pillTone' => null,
    'url' => null,
])

@php $tag = $url ? 'a' : 'div'; @endphp

<{{ $tag }}
    class="fnc-list__item fnc-tone-{{ $tone }}"
    @if ($url) href="{{ $url }}" @endif
>
    <span class="fnc-list__avatar" aria-hidden="true">
        @if ($avatar)
            {{ $avatar }}
        @else
            <x-fnc.icon :name="$icon" :size="17" />
        @endif
    </span>

    <span class="fnc-list__body">
        <span class="fnc-list__title">{{ $title }}</span>
        @if ($meta)
            <span class="fnc-list__meta">{{ $meta }}</span>
        @endif
    </span>

    <span class="fnc-list__aside">
        @if ($pill)
            <span class="fnc-pill fnc-tone-{{ $pillTone ?? $tone }}">{{ $pill }}</span>
        @endif
        @if ($value !== null)
            <span class="fnc-list__value">{{ $value }}</span>
        @endif
    </span>
</{{ $tag }}>
