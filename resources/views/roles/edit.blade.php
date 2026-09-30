@extends('layouts.inventario')

@section('title', 'Editar Rol')

@section('content')
<x-ui.toolbar 
    title="Editar Rol: {{ $role->name }}" 
    backRoute="{{ route('roles.index') }}"
/>

<x-ui.card>
    <form action="{{ route('roles.update', $role->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <x-ui.input name="name" id="name" label="Nombre del Rol" required="true" value="{{ old('name', $role->name) }}" containerClass="mb-4" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />

        <div class="mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Asignar Permisos</h5>
            
            <div class="row g-4">
                @foreach($permissions as $module => $modulePermissions)
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="bg-light h-100 border-0 shadow-sm" noPadding="false">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h6 class="card-title text-uppercase fw-bold text-primary mb-0">{{ str_replace('_', ' ', $module) }}</h6>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input select-all-module" type="checkbox" id="selectAll_{{ $module }}">
                                <label class="form-check-label small text-muted" for="selectAll_{{ $module }}">Todos</label>
                            </div>
                        </div>
                        
                        <div class="d-flex flex-column gap-2">
                            @foreach($modulePermissions as $permission)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}" {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                                <label class="form-check-label" for="perm_{{ $permission->id }}">
                                    {{ explode('.', $permission->name)[1] ?? $permission->name }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                </div>
                @endforeach
            </div>
            @error('permissions')
                <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="text-end border-top pt-4">
            <x-ui.button href="{{ route('roles.index') }}" color="light" class="me-2" text="Cancelar" />
            <x-ui.button type="submit" color="primary" icon="save" text="Actualizar Rol" />
        </div>
    </form>
</x-ui.card>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.select-all-module').forEach(function(toggle) {
            toggle.addEventListener('change', function() {
                const card = this.closest('.card');
                const checkboxes = card.querySelectorAll('.form-check-input:not(.select-all-module)');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        });

        document.querySelectorAll('.card').forEach(function(card) {
            const toggle = card.querySelector('.select-all-module');
            if (toggle) {
                const checkboxes = Array.from(card.querySelectorAll('.form-check-input:not(.select-all-module)'));
                if(checkboxes.length > 0) {
                    toggle.checked = checkboxes.every(cb => cb.checked);
                    checkboxes.forEach(cb => {
                        cb.addEventListener('change', function() {
                            toggle.checked = checkboxes.every(c => c.checked);
                        });
                    });
                }
            }
        });
    });
</script>
@endpush
