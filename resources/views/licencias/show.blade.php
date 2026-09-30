@extends('layouts.inventario')

@section('title', 'Detalle de Licencia')

@section('content')
<x-ui.toolbar 
    title="{{ $licencia->nombre }}" 
    icon="key" 
    backRoute="{{ route('licencias.index') }}"
>
    <x-ui.button href="{{ route('licencias.edit', $licencia) }}" color="warning" icon="pencil" text="Editar" />
</x-ui.toolbar>

<div class="row">
    <div class="col-md-4">
        <x-ui.card noPadding="true" class="mb-4">
            <div class="card-header bg-light fw-bold">Información de la Licencia</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted">Tipo:</dt>
                    <dd class="col-sm-7">{{ $licencia->tipo_licencia }}</dd>

                    <dt class="col-sm-5 text-muted">Estado:</dt>
                    <dd class="col-sm-7">
                        <x-ui.badge color="{{ $licencia->estado === 'Activa' ? 'success' : ($licencia->estado === 'Suspendida' ? 'warning' : 'danger') }}">
                            {{ $licencia->estado }}
                        </x-ui.badge>
                    </dd>

                    @if($licencia->tipo_licencia === 'Suscripción')
                        <dt class="col-sm-5 text-muted">Cupos:</dt>
                        <dd class="col-sm-7">
                            <x-ui.badge color="{{ $licencia->cupos_disponibles > 0 ? 'success' : 'danger' }}">
                                {{ $licencia->cupos_asignados }} / {{ $licencia->cantidad_maxima }} Asignados
                            </x-ui.badge>
                        </dd>

                        <dt class="col-sm-5 text-muted">Inicio:</dt>
                        <dd class="col-sm-7">{{ $licencia->fecha_inicio ? $licencia->fecha_inicio->format('d/m/Y') : 'N/A' }}</dd>

                        <dt class="col-sm-5 text-muted">Vencimiento:</dt>
                        <dd class="col-sm-7 {{ $licencia->fecha_vencimiento && $licencia->fecha_vencimiento->isPast() ? 'text-danger fw-bold' : '' }}">
                            {{ $licencia->fecha_vencimiento ? $licencia->fecha_vencimiento->format('d/m/Y') : 'N/A' }}
                        </dd>

                        <dt class="col-sm-5 text-muted">Renovación:</dt>
                        <dd class="col-sm-7">{{ $licencia->fecha_renovacion ? $licencia->fecha_renovacion->format('d/m/Y') : 'N/A' }}</dd>
                        @endif

                        <dt class="col-sm-5 text-muted">Requiere Correo:</dt>
                        <dd class="col-sm-7">{{ $licencia->requiere_correo ? 'Sí' : 'No' }}</dd>

                    @elseif($licencia->tipo_licencia === 'Vitalicia')

                    @endif
                    
                    <dt class="col-sm-5 text-muted">Correo Compra:</dt>
                    <dd class="col-sm-7">{{ $licencia->correo_compra ?: 'N/A' }}</dd>
                    
                    <dt class="col-sm-5 text-muted">Fecha Compra:</dt>
                    <dd class="col-sm-7">{{ $licencia->fecha_compra ? $licencia->fecha_compra->format('d/m/Y') : 'N/A' }}</dd>
                </dl>
                @if($licencia->observaciones)
                <hr>
                <div class="text-muted small"><strong>Observaciones:</strong><br>{{ $licencia->observaciones }}</div>
                @endif
            </div>
        </x-ui.card>
    </div>
    
    <div class="col-md-8">
        <ul class="nav nav-tabs" id="licenciaTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="asignaciones-tab" data-bs-toggle="tab" data-bs-target="#asignaciones" type="button" role="tab">
                    <i class="bi bi-people me-1"></i>Asignaciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="seriales-tab" data-bs-toggle="tab" data-bs-target="#seriales" type="button" role="tab">
                    <i class="bi bi-123 me-1"></i>Seriales
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button" role="tab">
                    <i class="bi bi-clock-history me-1"></i>Historial
                </button>
            </li>
        </ul>
        <div class="tab-content border-start border-end border-bottom bg-white p-3 mb-4" id="licenciaTabsContent">

            <div class="tab-pane fade show active" id="asignaciones" role="tabpanel">
                @if($licencia->asignaciones->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Funcionario</th>
                                <th>Equipo</th>
                                <th>Serial / Correo Activación</th>
                                <th>Fecha Asig.</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($licencia->asignaciones as $asignacion)
                            <tr>
                                <td>{{ $asignacion->funcionario ? $asignacion->funcionario->nombre_completo : 'N/A' }}</td>
                                <td>
                                    @if($asignacion->equipo)
                                        <a href="{{ route('equipos.show', $asignacion->equipo) }}" class="fw-medium text-decoration-none">{{ $asignacion->equipo->placa_visual ?? $asignacion->equipo->nombre_equipo ?? 'Ver Equipo' }}</a><br>
                                        <span class="text-muted small">Equipo SN: {{ $asignacion->equipo->serial_visual ?? 'N/A' }}</span>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    <strong>Serial:</strong> {{ $asignacion->serial ? $asignacion->serial->serial : 'N/A' }}<br>
                                    <strong>Correo:</strong> {{ $asignacion->correo_activacion ?: 'N/A' }}
                                </td>
                                <td>{{ $asignacion->fecha_asignacion ? $asignacion->fecha_asignacion->format('d/m/Y') : 'N/A' }}</td>
                                <td>
                                    <x-ui.badge color="{{ $asignacion->estado === 'Activa' ? 'success' : 'secondary' }}">
                                        {{ $asignacion->estado }}
                                    </x-ui.badge>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted mb-0">No hay asignaciones para esta licencia.</p>
                @endif
            </div>


            <div class="tab-pane fade" id="seriales" role="tabpanel">
                <div class="mb-3">
                    <form method="POST" action="{{ route('licencias.seriales.store', $licencia) }}" class="row g-2 align-items-center">
                        @csrf
                        <div class="col-12 col-md-5">
                            <input type="text" name="serial" class="form-control form-control-sm" placeholder="Ingresar nuevo serial" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <select name="estado" class="form-select form-select-sm" required>
                                <option value="Disponible">Disponible</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <x-ui.button type="submit" color="primary" size="sm" class="w-100" icon="plus-circle" text="Agregar Serial" />
                        </div>
                    </form>
                </div>
                
                @if($licencia->seriales && $licencia->seriales->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Serial</th>
                                <th>Estado</th>
                                <th>Observaciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($licencia->seriales as $serialItem)
                            <tr>
                                <td class="fw-medium font-monospace">{{ $serialItem->serial }}</td>
                                <td>
                                    <x-ui.badge color="{{ $serialItem->estado === 'Disponible' ? 'success' : ($serialItem->estado === 'Asignado' ? 'primary' : ($serialItem->estado === 'Reservado' ? 'warning' : 'secondary')) }}">
                                        {{ $serialItem->estado }}
                                    </x-ui.badge>
                                </td>
                                <td>{{ $serialItem->observaciones ?: 'Ninguna' }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editSerialModal{{ $serialItem->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @if($serialItem->estado !== 'Asignado' && $serialItem->estado !== 'Reservado')
                                    <form method="POST" action="{{ route('licencias.seriales.destroy', [$licencia, $serialItem]) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que desea eliminar este serial?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            

                            <x-ui.modal id="editSerialModal{{ $serialItem->id }}" title="Editar Serial">
                                <form method="POST" action="{{ route('licencias.seriales.update', [$licencia, $serialItem]) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <x-ui.input 
                                            name="serial" 
                                            label="Serial" 
                                            value="{{ $serialItem->serial }}" 
                                            readonly="{{ $serialItem->estado === 'Asignado' ? 'true' : 'false' }}"
                                            required="{{ $serialItem->estado === 'Asignado' ? 'false' : 'true' }}"
                                            containerClass="mb-3"
                                        />
                                        <div class="mb-3">
                                            <label class="form-label">Estado</label>
                                            <select name="estado" class="form-select" {{ $serialItem->estado === 'Asignado' ? 'disabled' : 'required' }}>
                                                <option value="Disponible" {{ $serialItem->estado === 'Disponible' ? 'selected' : '' }}>Disponible</option>
                                                <option value="Inactivo" {{ $serialItem->estado === 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                                                @if($serialItem->estado === 'Asignado' || $serialItem->estado === 'Reservado')
                                                <option value="{{ $serialItem->estado }}" selected>{{ $serialItem->estado }}</option>
                                                @endif
                                            </select>
                                            @if($serialItem->estado === 'Asignado')
                                            <input type="hidden" name="estado" value="Asignado">
                                            @endif
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Observaciones</label>
                                            <textarea name="observaciones" class="form-control" rows="2">{{ $serialItem->observaciones }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <x-ui.button type="button" color="secondary" data-bs-dismiss="modal" text="Cancelar" />
                                        <x-ui.button type="submit" color="primary" text="Guardar" />
                                    </div>
                                </form>
                            </x-ui.modal>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted mb-0">No hay seriales registrados para esta licencia.</p>
                @endif
            </div>


            <div class="tab-pane fade" id="historial" role="tabpanel">
                @if($historial->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historial as $log)
                            <tr>
                                <td>{{ $log->fecha->format('d/m/Y H:i') }}</td>
                                <td>{{ $log->usuario ? $log->usuario->name : 'Sistema' }}</td>
                                <td>{{ $log->accion }}</td>
                                <td>{{ $log->observacion }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted mb-0">No hay historial registrado.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
