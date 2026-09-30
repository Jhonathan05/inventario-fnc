@extends('layouts.inventario')

@php
    $usuarioForm = $usuario ?? $user ?? null;
    $esEdicion = !is_null($usuarioForm);
@endphp

@section('title', $esEdicion ? 'Editar Usuario' : 'Crear Usuario')

@section('content')
<x-ui.toolbar 
    title="{{ $esEdicion ? 'Editar Usuario: ' . $usuarioForm->name : 'Crear Nuevo Usuario' }}" 
    backRoute="{{ route('usuarios.index') }}"
/>

<x-ui.card>
    <form id="usuario-form" action="{{ $esEdicion ? route('usuarios.update', $usuarioForm->id) : route('usuarios.store') }}" method="POST" autocomplete="off" novalidate>
        @csrf
        @if($esEdicion)
            @method('PUT')
        @endif

        <div class="row g-4 mb-4">
            <x-ui.input 
                name="name" 
                label="Nombre Completo" 
                value="{{ $esEdicion ? old('name', $usuarioForm->name ?? '') : '' }}" 
                autocomplete="off" 
                required="true"
                containerClass="col-md-6"
                oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
            />

            <div class="col-md-6">
                <x-ui.input 
                    name="email" 
                    type="email"
                    label="Correo Electrónico" 
                    value="{{ $esEdicion ? old('email', $usuarioForm->email ?? '') : '' }}" 
                    autocomplete="off" 
                    maxlength="255"
                    required="true"
                    containerClass="mb-0"
                />
                <div class="invalid-feedback d-none" id="email-feedback">Debe ingresar un correo electrónico válido.</div>
            </div>

            <div class="col-md-6">
                <label for="password" class="form-label fw-medium">Contraseña @if(!$esEdicion)<span class="text-danger">*</span>@endif</label>
                <div class="input-group mb-1">
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-control"
                        autocomplete="new-password"
                        minlength="8"
                        {{ $esEdicion ? '' : 'required' }}
                    >
                    <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @if($esEdicion) <div class="text-muted fw-normal small mb-1">(Dejar en blanco para mantener la actual)</div> @endif
                <small class="text-muted d-block mt-1">Mínimo 8 caracteres, incluyendo mayúscula, minúscula, número y símbolo.</small>
                <div class="invalid-feedback d-none" id="password-feedback">La contraseña no cumple la complejidad requerida.</div>
            </div>

            <div class="col-md-6">
                <label for="password_confirmation" class="form-label fw-medium">Confirmar Contraseña @if(!$esEdicion)<span class="text-danger">*</span>@endif</label>
                <div class="input-group mb-0">
                    <input 
                        type="password" 
                        name="password_confirmation" 
                        id="password_confirmation" 
                        class="form-control"
                        autocomplete="new-password"
                        minlength="8"
                        {{ $esEdicion ? '' : 'required' }}
                    >
                    <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="invalid-feedback d-none" id="password-confirmation-feedback">La confirmación de la contraseña no coincide.</div>
            </div>

            <x-ui.select 
                name="role" 
                label="Rol del Sistema" 
                required="true"
                containerClass="col-md-6"
            >
                <option value="">Seleccione un rol...</option>
                @foreach($roles as $role)
                    @if(Auth::user()->roles->first()->name !== 'Administrador' && $role->name === 'Administrador')
                        @continue
                    @endif
                    <option value="{{ $role->name }}" {{ ($esEdicion ? old('role', $usuarioForm->roles->first()->name ?? '') : '') == $role->name ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="text-end border-top pt-4">
            <x-ui.button href="{{ route('usuarios.index') }}" color="light" class="me-2" text="Cancelar" />
            <x-ui.button type="submit" color="primary" icon="save" text="{{ $esEdicion ? 'Actualizar Usuario' : 'Guardar Usuario' }}" />
        </div>
    </form>
</x-ui.card>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('usuario-form');
        if (!form) {
            return;
        }

        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const passwordConfirmationInput = document.getElementById('password_confirmation');

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/;

        const setInvalid = function (input, message) {
            input.classList.add('is-invalid');
            input.setCustomValidity(message);
        };

        const setValid = function (input) {
            input.classList.remove('is-invalid');
            input.setCustomValidity('');
        };

        const validateEmail = function () {
            const value = (emailInput.value || '').trim();
            if (!value || emailRegex.test(value)) {
                setValid(emailInput);
                return true;
            }
            setInvalid(emailInput, 'Debe ingresar un correo electrónico válido.');
            return false;
        };

        const validatePassword = function () {
            const value = passwordInput.value || '';
            if (!value && !passwordInput.required) {
                setValid(passwordInput);
                return true;
            }
            if (passwordRegex.test(value)) {
                setValid(passwordInput);
                return true;
            }
            setInvalid(passwordInput, 'La contraseña no cumple la complejidad requerida.');
            return false;
        };

        const validatePasswordConfirmation = function () {
            const passwordValue = passwordInput.value || '';
            const confirmationValue = passwordConfirmationInput.value || '';

            if (!passwordValue && !passwordConfirmationInput.required) {
                setValid(passwordConfirmationInput);
                return true;
            }

            if (passwordValue === confirmationValue) {
                setValid(passwordConfirmationInput);
                return true;
            }

            setInvalid(passwordConfirmationInput, 'La confirmación de la contraseña no coincide.');
            return false;
        };

        emailInput.addEventListener('input', validateEmail);
        passwordInput.addEventListener('input', function () {
            validatePassword();
            validatePasswordConfirmation();
        });
        passwordConfirmationInput.addEventListener('input', validatePasswordConfirmation);

        form.addEventListener('submit', function (event) {
            const validEmail = validateEmail();
            const validPassword = validatePassword();
            const validConfirmation = validatePasswordConfirmation();

            if (!validEmail || !validPassword || !validConfirmation) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        // Lógica para visualizar contraseña
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
    });
</script>
@endpush
