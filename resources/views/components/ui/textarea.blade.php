@props([
    'name',
    'label' => null,
    'id' => null,
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'rows' => 3
])

@php
    $id = $id ?? $name;
@endphp

<div class="mb-3">
    @if($label)
        <label for="{{ $id }}" id="{{ $id }}_label" class="form-label fw-medium">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <textarea 
        name="{{ $name }}" 
        id="{{ $id }}" 
        rows="{{ $rows }}"
        class="form-control @error($name) is-invalid @enderror" 
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        @error($name) aria-invalid="true" aria-describedby="{{ $id }}_error" @enderror
        {{ $attributes }}
    >{{ old($name, $slot) }}</textarea>
    @error($name)
        <div class="invalid-feedback" id="{{ $id }}_error">{{ $message }}</div>
    @enderror
</div>
