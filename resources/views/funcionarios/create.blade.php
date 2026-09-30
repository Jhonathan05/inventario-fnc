@extends('layouts.inventario')

@section('title', 'Nuevo Funcionario')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        
        <x-ui.toolbar 
            title="Nuevo Funcionario" 
            backRoute="{{ route('funcionarios.index') }}"
        />

        <x-ui.card class="border-0 shadow-sm">
            <form action="{{ route('funcionarios.store') }}" method="POST">
                @csrf

                <h6 class="text-uppercase fw-bold text-dark mb-4 pb-2 border-bottom d-flex align-items-center gap-2 funcionario-section-title">
                    <i class="bi bi-person text-primary"></i> DATOS PERSONALES
                </h6>

                <div class="row mb-4">
                    <x-ui.input 
                        name="identificacion" 
                        label="Identificación (Cédula)" 
                        required="true"
                        value="{{ old('identificacion') }}"
                        containerClass="col-12 col-md-6"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    />
                </div>

                <div class="row mb-4">
                    <x-ui.input 
                        name="nombres" 
                        label="Nombres" 
                        required="true"
                        value="{{ old('nombres') }}"
                        containerClass="col-12 col-md-6"
                        oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
                    />
                    <x-ui.input 
                        name="apellidos" 
                        label="Apellidos" 
                        value="{{ old('apellidos') }}"
                        containerClass="col-12 col-md-6"
                        oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
                    />
                </div>

                <h6 class="text-uppercase fw-bold text-dark mb-4 mt-5 pb-2 border-bottom d-flex align-items-center gap-2 funcionario-section-title">
                    <i class="bi bi-briefcase text-dark"></i> DATOS DE VINCULACIÓN
                </h6>

                <div class="row mb-4">
                    <x-ui.input 
                        name="cargo" 
                        label="Cargo" 
                        value="{{ old('cargo') }}"
                        containerClass="col-12 col-md-4"
                        oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
                    />
                    <x-ui.input 
                        name="area" 
                        label="Área / Gestión" 
                        value="{{ old('area') }}"
                        containerClass="col-12 col-md-4"
                        oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
                    />
                    <x-ui.input 
                        name="seccional" 
                        label="Seccional" 
                        value="{{ old('seccional') }}"
                        containerClass="col-12 col-md-4"
                        oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
                    />
                </div>

                <div class="row mb-4">
                    <x-ui.input 
                        name="empresa_funcionario" 
                        label="Empresa Funcionario" 
                        value="{{ old('empresa_funcionario') }}"
                        containerClass="col-12 col-md-6"
                        oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
                    />
                    <x-ui.input 
                        name="tipo_vinculacion" 
                        label="Tipo de Vinculación" 
                        value="{{ old('tipo_vinculacion') }}"
                        containerClass="col-12 col-md-6"
                        oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
                    />
                </div>
                
                <div class="row mb-5">
                    <x-ui.input 
                        name="departamento" 
                        label="Departamento" 
                        value="{{ old('departamento') }}"
                        containerClass="col-12 col-md-3"
                        oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
                    />
                    <x-ui.input 
                        name="distrito" 
                        label="Distrito" 
                        value="{{ old('distrito') }}"
                        containerClass="col-12 col-md-3"
                        oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
                    />
                    <x-ui.input 
                        name="ciudad" 
                        label="Ciudad" 
                        value="{{ old('ciudad') }}"
                        containerClass="col-12 col-md-3"
                        oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
                    />
                    <x-ui.select 
                        name="estado" 
                        label="Estado" 
                        required="true"
                        containerClass="col-12 col-md-3"
                    >
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </x-ui.select>
                </div>

                <div class="d-flex justify-content-end gap-3 pt-3 border-top">
                    <x-ui.button href="{{ route('funcionarios.index') }}" color="light" class="border px-4 rounded-pill" text="Cancelar" />
                    <x-ui.button type="submit" color="primary" class="px-4 rounded-pill" icon="check2" text="Guardar Funcionario" />
                </div>

            </form>
        </x-ui.card>
    </div>
</div>
@endsection
