{{--
    Estado vacío / sin novedades.
--}}
@props([
    'icon' => 'circle-check',
    'title' => 'Sin novedades',
    'text' => null,
    'tone' => 'success',
])

<div {{ $attributes->merge(['class' => 'fnc-empty fnc-tone-' . $tone]) }}>
    <span class="fnc-empty__icon" aria-hidden="true">
        <x-fnc.icon :name="$icon" :size="20" />
    </span>
    <span class="fnc-empty__title">{{ $title }}</span>
    @if ($text)
        <p class="fnc-empty__text">{{ $text }}</p>
    @endif
</div>
