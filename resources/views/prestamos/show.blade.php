@extends('layouts.inventario')

@section('title', 'Detalle de Préstamo #' . str_pad($prestamo->id, 4, '0', STR_PAD_LEFT))

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <a href="{{ route('prestamos.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left me-1"></i>Volver</a>
            <h2 class="h3 mb-0 text-gray-800">Préstamo #{{ str_pad($prestamo->id, 4, '0', STR_PAD_LEFT) }}</h2>
        </div>
        <div>
            @if(in_array($prestamo->estado, ['Activo', 'Pendiente', 'Vencido']))
                @can('equipos.crear')
                    <button type="button" class="btn btn-info text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAmpliar">
                        <i class="bi bi-calendar-plus me-1"></i> Ampliar
                    </button>
                    <button type="button" class="btn btn-success shadow-sm ms-2" data-bs-toggle="modal" data-bs-target="#modalDevolver">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Registrar Devolución
                    </button>
                    <button type="button" class="btn btn-outline-danger shadow-sm ms-2" data-bs-toggle="modal" data-bs-target="#modalCancelar">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                @endcan
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Columna Izquierda -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-info-circle me-2"></i>Información del Préstamo</h6>
                    <span class="badge bg-{{ $prestamo->estado_badge }} px-3 py-2 fs-6">{{ $prestamo->estado }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <label class="text-muted small fw-bold text-uppercase">Equipo Prestado</label>
                            <p class="mb-0 fw-medium">
                                @if($prestamo->equipo)
                                    <a href="{{ route('equipos.show', $prestamo->equipo) }}" class="text-decoration-none">
                                        {{ $prestamo->equipo->placa_visual }} - {{ $prestamo->equipo->nombre_equipo }}
                                    </a>
                                @else
                                    <span class="text-danger">Equipo Eliminado</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-bold text-uppercase">Persona que Recibe</label>
                            <p class="mb-0 fw-medium">{{ $prestamo->persona_nombre }}</p>
                            @if($prestamo->persona_documento)
                                <small class="text-muted">Doc: {{ $prestamo->persona_documento }}</small>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-bold text-uppercase">Fecha de Inicio</label>
                            <p class="mb-0">{{ $prestamo->fecha_inicio->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-bold text-uppercase">Devolución Prevista</label>
                            <p class="mb-0">{{ $prestamo->fecha_devolucion_prevista->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small fw-bold text-uppercase">Motivo del Préstamo</label>
                            <p class="mb-0">{{ $prestamo->motivo ?: 'No especificado' }}</p>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small fw-bold text-uppercase">Observaciones Iniciales</label>
                            <p class="mb-0">{{ $prestamo->observaciones ?: 'Ninguna' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if(in_array($prestamo->estado, ['Devuelto']))
                <div class="card shadow-sm border-0 border-start border-success border-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="m-0 font-weight-bold text-success"><i class="bi bi-check-circle me-2"></i>Información de Devolución</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-sm-6">
                                <label class="text-muted small fw-bold text-uppercase">Fecha Real Devolución</label>
                                <p class="mb-0">{{ $prestamo->fecha_devolucion_real ? $prestamo->fecha_devolucion_real->format('d/m/Y H:i') : 'N/A' }}</p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small fw-bold text-uppercase">Recibido Por</label>
                                <p class="mb-0">{{ $prestamo->devueltoPor ? $prestamo->devueltoPor->name : 'N/A' }}</p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small fw-bold text-uppercase">Estado Físico al Recibir</label>
                                <p class="mb-0 fw-medium">
                                    <span class="badge bg-{{ $prestamo->estado_fisico_devolucion == 'Bueno' ? 'success' : ($prestamo->estado_fisico_devolucion == 'Regular' ? 'warning' : 'danger') }}">
                                        {{ $prestamo->estado_fisico_devolucion ?: 'N/A' }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-bold text-uppercase">Observaciones de Devolución</label>
                                <p class="mb-0">{{ $prestamo->observaciones_devolucion ?: 'Ninguna' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Columna Derecha -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="m-0 font-weight-bold text-secondary"><i class="bi bi-clock-history me-2"></i>Auditoría</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <span class="text-muted fw-bold d-block">Registrado Por</span>
                            {{ $prestamo->registradoPor ? $prestamo->registradoPor->name : 'Sistema' }}
                        </li>
                        <li class="mb-2">
                            <span class="text-muted fw-bold d-block">Fecha de Registro</span>
                            {{ $prestamo->created_at->format('d/m/Y H:i') }}
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="alert alert-info">
                Para ver el historial completo de eventos (Ampliaciones, vencimientos, devoluciones), visita el 
                <a href="{{ route('equipos.show', $prestamo->equipo) }}#historial" class="alert-link">Historial del Activo</a>.
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@if(in_array($prestamo->estado, ['Activo', 'Pendiente', 'Vencido']))
    @can('equipos.crear')
        <!-- Modal Ampliar -->
        <div class="modal fade" id="modalAmpliar" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">Ampliar Préstamo</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('prestamos.update', $prestamo) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Fecha de Devolución Actual</label>
                                <input type="text" class="form-control" value="{{ $prestamo->fecha_devolucion_prevista->format('Y-m-d\TH:i') }}" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nueva Fecha de Devolución <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="nueva_fecha_devolucion" class="form-control" required min="{{ now()->format('Y-m-d\TH:i') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Motivo de Ampliación <span class="text-danger">*</span></label>
                                <textarea name="motivo_ampliacion" class="form-control" rows="3" required placeholder="Ej: Requiere más tiempo para terminar el proyecto..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-info text-white">Confirmar Ampliación</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Devolver -->
        <div class="modal fade" id="modalDevolver" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Registrar Devolución</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('prestamos.devolver', $prestamo) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-success small">
                                Está a punto de registrar la devolución del activo <strong>{{ $prestamo->equipo->placa_visual }}</strong> por parte de <strong>{{ $prestamo->persona_nombre }}</strong>.
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Estado Físico del Activo <span class="text-danger">*</span></label>
                                <select name="estado_fisico_devolucion" class="form-select" required>
                                    <option value="" disabled selected>Seleccione el estado...</option>
                                    <option value="Bueno">Bueno</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Malo">Malo</option>
                                    <option value="Dañado">Dañado</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Observaciones de Devolución</label>
                                <textarea name="observaciones_devolucion" class="form-control" rows="3" placeholder="Detalles sobre complementos faltantes, rayones, novedades..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Registrar Devolución</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Modal Cancelar -->
        <div class="modal fade" id="modalCancelar" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Cancelar Préstamo</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('prestamos.cancelar', $prestamo) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-danger small">
                                ¿Está seguro de cancelar este préstamo? Esta acción anulará el proceso.
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Motivo de Cancelación <span class="text-danger">*</span></label>
                                <textarea name="motivo_cancelacion" class="form-control" rows="3" required placeholder="Indique la razón por la que se cancela el préstamo..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-danger">Confirmar Cancelación</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endif

@endsection

