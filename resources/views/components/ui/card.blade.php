@props([
    'title' => null,
    'icon' => null,
    'headerClass' => 'bg-light',
    'iconColor' => 'primary',
    'noPadding' => false,
])

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm mb-4']) }}>
    @if($title || isset($header))
        <div class="card-header {{ $headerClass }} bg-opacity-10 fw-semibold border-0 py-3 d-flex justify-content-between align-items-center">
            <div>
                @if($icon)
                    <i class="bi bi-{{ $icon }} me-2 text-{{ $iconColor }}"></i>
                @endif
                {{ $title ?? $header }}
            </div>
            @if(isset($headerActions))
                <div>
                    {{ $headerActions }}
                </div>
            @endif
        </div>
    @endif
    
    <div class="card-body {{ $noPadding ? 'p-0' : '' }}">
        {{ $slot }}
    </div>
    
    @if(isset($footer))
        <div class="card-footer bg-white border-top">
            {{ $footer }}
        </div>
    @endif
</div>
