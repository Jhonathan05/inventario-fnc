@props([
    'color' => 'primary',
    'message' => '',
    'icon' => null,
    'dismissible' => false,
])

<div {{ $attributes->merge(['class' => "alert alert-{$color} " . ($dismissible ? 'alert-dismissible fade show' : '')]) }} role="alert">
    @if($icon)
        <i class="bi bi-{{ $icon }} me-2"></i>
    @endif
    {{ $message ?? $slot }}
    @if($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
