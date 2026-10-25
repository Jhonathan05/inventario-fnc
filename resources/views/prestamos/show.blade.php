@extends('layouts.inventario')

@section('title', 'Detalle de Préstamo #' . str_pad($prestamo->id, 4, '0', STR_PAD_LEFT))

@section('content')
<div class="container-fluid py-4">
    <x-ui.toolbar
        title="Préstamo #{{ str_pad($prestamo->id, 4, '0', STR_PAD_LEFT) }}"
        icon="calendar2-range"
        :backRoute="route('prestamos.index')"
        subtitle="{{ $prestamo->equipo?->nombre_equipo ?: 'Equipo eliminado' }} · {{ $prestamo->persona_nombre }}"
    >
        @if(in_array($prestamo->estado, ['Activo', 'Pendiente', 'Vencido']))
            @can('equipos.crear')
                <button type="button" class="btn btn-info text-white me-1" data-bs-toggle="modal" data-bs-target="#modalAmpliar">
                    <i class="bi bi-calendar-plus me-1"></i> Ampliar
                </button>
                <button type="button" class="btn btn-success me-1" data-bs-toggle="modal" data-bs-target="#modalDevolver">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Registrar Devolución
                </button>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar">
                    <i class="bi bi-x-circle me-1"></i> Cancelar
                </button>
            @endcan
        @endif
    </x-ui.toolbar>

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
        <div class="col-lg-8">
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

            {{-- Lote 15: acta de novedad emitida por el préstamo --}}
            @if($prestamo->acta)
            <div class="card shadow-sm border-0 border-start {{ $prestamo->acta->anulada() ? 'border-secondary' : 'border-primary' }} border-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold {{ $prestamo->acta->anulada() ? 'text-secondary' : 'text-primary' }}"><i class="bi bi-file-earmark-text me-2"></i>Acta de novedad asociada</h6>
                    <span class="badge {{ $prestamo->acta->anulada() ? 'bg-secondary' : 'bg-primary' }}">{{ $prestamo->acta->anulada() ? 'ANULADA' : (\App\Models\Acta::TIPOS_ACCION[$prestamo->acta->tipo_accion] ?? $prestamo->acta->tipo_accion) }}</span>
                </div>
                <div class="card-body small">
                    <p class="mb-2"><strong>Acta #{{ $prestamo->acta->id }}</strong> · {{ $prestamo->acta->fecha_accion?->format('d/m/Y') ?? '—' }}@if($prestamo->acta->anulada()) · <span class="text-secondary">anulada el {{ $prestamo->acta->anulada_en?->format('d/m/Y H:i') ?? '—' }}</span>@endif</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('actas.show', $prestamo->acta->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>Ver acta
                        </a>
                        @can('equipos.exportar')
                            <a href="{{ route('actas.pdf', $prestamo->acta->id) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
                            <a href="{{ route('actas.docx', $prestamo->acta->id) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-word me-1"></i>Word</a>
                            <a href="{{ route('actas.paquete', $prestamo->acta->id) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-zip me-1"></i>Paquete (ZIP)</a>
                        @endcan
                    </div>
                </div>
            </div>
            @endif

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
        <div class="col-lg-4">
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

