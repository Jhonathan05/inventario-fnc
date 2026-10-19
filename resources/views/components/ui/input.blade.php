@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'id' => null,
    'placeholder' => '',
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
    <input 
        type="{{ $type }}" 
        name="{{ $name }}" 
        id="{{ $id }}" 
        value="{{ old($name, $value) }}" 
        class="form-control @error($name) is-invalid @enderror" 
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        @error($name) aria-invalid="true" aria-describedby="{{ $id }}_error" @enderror
        {{ $attributes }}
    >
    @error($name)
        <div class="invalid-feedback" id="{{ $id }}_error">{{ $message }}</div>
    @enderror
</div>
