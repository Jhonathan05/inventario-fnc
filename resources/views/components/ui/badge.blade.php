@props([
    'color' => 'primary',
    'text' => null,
])

<span {{ $attributes->merge(['class' => "badge bg-{$color}"]) }}>
    {{ $text ?? $slot }}
</span>
