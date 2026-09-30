@extends('layouts.inventario')
@section('title', 'Crear Licencia Vitalicia')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <h4 class="fw-bold mb-0"><i class="bi bi-award me-2 text-primary"></i>Nueva Licencia Vitalicia</h4>
        <a href="{{ route('vitalicias.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('vitalicias.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre de la licencia *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fabricante</label>
                        <input type="text" name="fabricante" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo</label>
                        <input type="text" name="tipo" class="form-control" placeholder="Ej: OEM, Retail...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cantidad comprada *</label>
                        <input type="number" name="cantidad_comprada" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Número de factura</label>
                        <input type="text" name="numero_factura" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado *</label>
                        <select name="estado" class="form-select" required>
                            <option value="Activa">Activa</option>
                            <option value="Inactiva">Inactiva</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Licencia Vitalicia</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

