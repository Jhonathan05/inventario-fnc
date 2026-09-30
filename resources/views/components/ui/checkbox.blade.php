@props([
    'name',
    'id' => null,
    'label' => null,
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'containerClass' => 'form-check'
])

@php
    $id = $id ?? $name . '_' . uniqid();
@endphp

<div class="{{ $containerClass }}">
    <input 
        class="form-check-input" 
        type="checkbox" 
        name="{{ $name }}" 
        value="{{ $value }}" 
        id="{{ $id }}"
        {{ $checked ? 'checked' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes }}
    >
    @if($label)
        <label class="form-check-label" for="{{ $id }}">
            {{ $label }}
        </label>
    @endif
</div>
