@extends('layouts.inventario')
@section('title', 'Crear Suscripción')

@section('content')
<div class="container-fluid">
    <x-ui.toolbar 
        title="Nueva Suscripción" 
        icon="calendar-plus"
        backRoute="{{ route('suscripciones.index') }}"
    />

    <x-ui.card class="shadow-sm border-0">
        <form action="{{ route('suscripciones.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre de la suscripción *</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fabricante</label>
                    <input type="text" name="fabricante" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cantidad comprada *</label>
                    <input type="number" name="cantidad_comprada" class="form-control" value="1" min="1" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha de Compra</label>
                    <input type="date" name="fecha_compra" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha de Vencimiento Global</label>
                    <input type="date" name="fecha_vencimiento_global" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado *</label>
                    <select name="estado" class="form-select" required>
                        <option value="Activa">Activa</option>
                        <option value="Próxima a vencer">Próxima a vencer</option>
                        <option value="Vencida">Vencida</option>
                        <option value="Suspendida">Suspendida</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="mt-4">
                <x-ui.button type="submit" color="primary" icon="save" text="Guardar Suscripción" />
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
