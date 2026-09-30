@props([
    'name',
    'label' => null,
    'id' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'containerClass' => 'mb-3',
])

@php
    $id = $id ?? $name;
@endphp

<div class="{{ $containerClass }}">
    @if($label)
        <label for="{{ $id }}" class="form-label fw-medium">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <select 
        name="{{ $name }}" 
        id="{{ $id }}" 
        class="form-select @error($name) is-invalid @enderror" 
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        {{ $attributes }}
    >
        {{ $slot }}
    </select>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
