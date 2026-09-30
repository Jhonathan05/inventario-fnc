@props([
    'type' => 'button',
    'color' => 'primary',
    'icon' => null,
    'text' => null,
    'outline' => false,
    'size' => '',
    'href' => null
])

@php
    $btnClass = $outline ? "btn-outline-{$color}" : "btn-{$color}";
    $sizeClass = $size ? "btn-{$size}" : '';
    $classes = "btn {$btnClass} {$sizeClass}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <i class="bi bi-{{ $icon }} {{ ($text || !$slot->isEmpty()) ? 'me-1' : '' }}"></i>
        @endif
        {{ $text ?? $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <i class="bi bi-{{ $icon }} {{ ($text || !$slot->isEmpty()) ? 'me-1' : '' }}"></i>
        @endif
        {{ $text ?? $slot }}
    </button>
@endif
