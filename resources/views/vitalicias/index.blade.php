@extends('layouts.inventario')
@section('title', 'Vitalicias')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <h4 class="fw-bold mb-0"><i class="bi bi-award me-2 text-primary"></i>Gestión de Licencias Vitalicias</h4>
        @can('vitalicias.crear')
        <a href="{{ route('vitalicias.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nueva Vitalicia
        </a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Vitalicias</h6>
                    <h3 class="mb-0 text-primary">0</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Fabricante</th>
                            <th>Compradas</th>
                            <th>Disponibles</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No hay licencias vitalicias registradas</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

