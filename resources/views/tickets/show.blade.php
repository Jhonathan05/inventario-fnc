@extends('layouts.inventario')

@section('title', 'Ticket #' . $ticket->id)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        

        <x-ui.toolbar>
            <x-slot name="title">Ticket #{{ $ticket->id }}: {{ $ticket->titulo }}</x-slot>
            <x-slot name="subtitle">
                <span class="badge 
                    @if($ticket->estado == 'Abierto') bg-primary 
                    @elseif($ticket->estado == 'En Diagnóstico') bg-info
                    @elseif($ticket->estado == 'En Proceso') bg-warning
                    @elseif($ticket->estado == 'Resuelto') bg-success
                    @elseif($ticket->estado == 'Cerrado') bg-secondary
                    @else bg-dark @endif">
                    {{ $ticket->estado }}
                </span>
                
                @php
                    $badgePrioridad = 'bg-success'; // Baja
                    if($ticket->prioridad == 'Media') $badgePrioridad = 'bg-warning text-dark';
                    if($ticket->prioridad == 'Alta') $badgePrioridad = 'bg-orange'; // Asumiendo estilo o usar bg-danger bg-opacity-75
                    if($ticket->prioridad == 'Crítica') $badgePrioridad = 'bg-danger';
                @endphp
                <span class="badge {{ $badgePrioridad }} ms-1">{{ $ticket->prioridad }}</span>
                
                <span class="ms-2">
                    &bull; 
                    @if(in_array($ticket->estado, ['Resuelto', 'Cerrado']) && $ticket->fecha_cierre)
                        Tiempo de resolución: {{ Carbon\Carbon::parse($ticket->created_at)->diffForHumans($ticket->fecha_cierre, true) }}
                    @else
                        Abierto hace {{ Carbon\Carbon::parse($ticket->created_at)->diffForHumans(null, true) }}
                    @endif
                </span>
            </x-slot>
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#cambiarEstadoModal">
                <i class="bi bi-arrow-repeat"></i> Cambiar Estado
            </button>
            <x-ui.button href="{{ route('tickets.index') }}" color="light" class="border" icon="arrow-left" text="Volver" />
        </x-ui.toolbar>

        @if($errors->any())
        <div class="alert alert-danger shadow-sm border-0 mb-4">
            <h6 class="fw-bold"><i class="bi bi-exclamation-triangle"></i> Revisa los siguientes errores:</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="accordion shadow-sm" id="accordionTicket">
            

            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingInfo">
                    <button class="accordion-button bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInfo" aria-expanded="true" aria-controls="collapseInfo">
                        <i class="bi bi-info-circle text-primary me-2"></i> INFORMACIÓN GENERAL Y EVIDENCIAS
                    </button>
                </h2>
                <div id="collapseInfo" class="accordion-collapse collapse show" aria-labelledby="headingInfo">
                    <div class="accordion-body">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Solicitante</span>
                                <div>{{ $ticket->funcionario->nombres }} {{ $ticket->funcionario->apellidos }}</div>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Tipo de Caso</span>
                                <div>{{ $ticket->tipo }}</div>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Técnico Asignado</span>
                                <div>{{ $ticket->responsable ? $ticket->responsable->name : 'Sin asignar' }}</div>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Fecha Solicitud</span>
                                <div>{{ $ticket->fecha_solicitud ? $ticket->fecha_solicitud->format('d/m/Y H:i') : $ticket->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                        
                        <div class="border-top pt-3 mb-4">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-2">Descripción del Requerimiento</span>
                            <div class="bg-light p-3 rounded text-dark">
                                {!! nl2br(e($ticket->descripcion)) !!}
                            </div>
                        </div>


                        <div class="border-top pt-3">
                            <h6 class="fw-bold mb-3"><i class="bi bi-paperclip text-primary"></i> Evidencias Adjuntas</h6>
                            
                            @if(is_array($ticket->archivos) && count($ticket->archivos) > 0)
                                <div class="row g-3 mb-4">
                                    @foreach($ticket->archivos as $index => $archivo)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="card border shadow-sm h-100">
                                                <div class="card-body d-flex align-items-center gap-3 p-3">
                                                    <div class="display-6 text-primary">
                                                        @if(str_contains($archivo['mime_type'], 'image'))
                                                            <i class="bi bi-file-image"></i>
                                                        @elseif(str_contains($archivo['mime_type'], 'pdf'))
                                                            <i class="bi bi-file-pdf text-danger"></i>
                                                        @elseif(str_contains($archivo['mime_type'], 'word'))
                                                            <i class="bi bi-file-word text-primary"></i>
                                                        @else
                                                            <i class="bi bi-file-earmark"></i>
                                                        @endif
                                                    </div>
                                                    <div class="overflow-hidden">
                                                        <h6 class="mb-1 text-truncate" title="{{ $archivo['name'] }}">{{ $archivo['name'] }}</h6>
                                                        <small class="text-muted d-block">{{ round($archivo['size'] / 1024, 1) }} KB</small>
                                                        <a href="{{ route('tickets.evidencia.descargar', [$ticket, $index]) }}" class="btn btn-sm btn-link p-0 text-decoration-none mt-1">Descargar <i class="bi bi-download"></i></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted small mb-4">No hay archivos adjuntos en este ticket.</p>
                            @endif

                            @if($ticket->estado != 'Cerrado')
                            <form action="{{ route('tickets.evidencia', $ticket) }}" method="POST" enctype="multipart/form-data" class="bg-light p-3 rounded border border-dashed">
                                @csrf
                                <div class="d-flex align-items-center gap-3">
                                    <input type="file" name="archivos[]" class="form-control form-control-sm" multiple required>
                                    <button type="submit" class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-cloud-upload"></i> Subir Archivos</button>
                                </div>
                                <small class="text-muted mt-2 d-block">Soporta múltiples archivos. Máximo 5MB por archivo.</small>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>


            @if($ticket->equipo)
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingActivo">
                    <button class="accordion-button collapsed bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseActivo" aria-expanded="false" aria-controls="collapseActivo">
                        <i class="bi bi-pc-display text-primary me-2"></i> ACTIVO RELACIONADO: {{ $ticket->equipo->placa_visual }}
                    </button>
                </h2>
                <div id="collapseActivo" class="accordion-collapse collapse" aria-labelledby="headingActivo">
                    <div class="accordion-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <p class="mb-1 text-muted small fw-bold">Tipo y Modelo</p>
                                <p class="mb-2">{{ $ticket->equipo->tipoRecurso->nombre ?? 'N/A' }} - {{ $ticket->equipo->marca }} {{ $ticket->equipo->modelo }}</p>
                                
                                <p class="mb-1 text-muted small fw-bold">Identificador</p>
                                <p class="mb-2">{{ $ticket->equipo->placa_visual }} / S/N: {{ $ticket->equipo->serial_visual }}</p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1 text-muted small fw-bold">Responsable Actual</p>
                                <p class="mb-2">
                                    @if($ticket->equipo->asignaciones->count() > 0)
                                        {{ $ticket->equipo->asignaciones->first()->funcionario->nombres ?? 'Desconocido' }}
                                    @else
                                        Sin asignación activa
                                    @endif
                                </p>
                                
                                <p class="mb-1 text-muted small fw-bold">Estado Operativo</p>
                                <p class="mb-2"><span class="badge bg-secondary">{{ $ticket->equipo->estado_operativo }}</span></p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1 text-muted small fw-bold">Garantía</p>
                                <p class="mb-2">
                                    @if($ticket->equipo->fin_garantia)
                                        {{ $ticket->equipo->fin_garantia->format('d/m/Y') }}
                                        @if($ticket->equipo->fin_garantia->isPast())
                                            <span class="badge bg-danger ms-1">Vencida</span>
                                        @else
                                            <span class="badge bg-success ms-1">Activa</span>
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </p>

                                <a href="{{ route('equipos.show', $ticket->equipo->id) }}" class="btn btn-sm btn-outline-primary mt-2" target="_blank">
                                    Ver hoja de vida completa <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 border-end">
                                <p class="mb-2 text-muted small fw-bold">Últimos Eventos Técnicos</p>
                                @forelse($ticket->equipo->historialTecnico as $ht)
                                    <div class="mb-2 small border-bottom pb-1">
                                        <strong>{{ $ht->fecha_evento->format('d/m/Y') }}:</strong> {{ $ht->tipo_evento_label }}
                                    </div>
                                @empty
                                    <p class="small text-muted">No hay mantenimientos recientes.</p>
                                @endforelse
                            </div>
                            <div class="col-md-6 ps-md-4">
                                <p class="mb-2 text-muted small fw-bold">Licencias Instaladas</p>
                                @forelse($ticket->equipo->licenciaAsignaciones as $la)
                                    <div class="mb-2 small">
                                        <i class="bi bi-key text-success"></i> {{ $la->licencia->nombre_software ?? 'Desconocida' }}
                                    </div>
                                @empty
                                    <p class="small text-muted">Sin licencias asignadas.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif


            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingDiag">
                    <button class="accordion-button collapsed bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDiag" aria-expanded="false" aria-controls="collapseDiag">
                        <i class="bi bi-clipboard2-pulse text-info me-2"></i> DIAGNÓSTICO DEL CASO
                    </button>
                </h2>
                <div id="collapseDiag" class="accordion-collapse collapse" aria-labelledby="headingDiag">
                    <div class="accordion-body">
                        <form action="{{ route('tickets.diagnostico', $ticket) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label fw-bold">Diagnóstico Inicial</label>
                                <textarea name="diagnostico_inicial" rows="3" class="form-control" required {{ $ticket->estado == 'Cerrado' ? 'readonly' : '' }}>{{ old('diagnostico_inicial', $ticket->diagnostico_inicial) }}</textarea>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Causa Probable</label>
                                    <input type="text" name="causa_probable" class="form-control" value="{{ old('causa_probable', $ticket->causa_probable) }}" {{ $ticket->estado == 'Cerrado' ? 'readonly' : '' }}>
                                </div>
                                @if($ticket->fecha_diagnostico)
                                <div class="col-md-6 text-end pt-4">
                                    <span class="text-muted small bg-light p-2 rounded border"><i class="bi bi-person"></i> Diagnosticado el: {{ $ticket->fecha_diagnostico->format('d/m/Y H:i') }} por {{ $ticket->diagnosticoPor->name ?? 'Sistema' }}</span>
                                </div>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Observaciones Técnicas (Opcional)</label>
                                <textarea name="observaciones_tecnicas" rows="2" class="form-control" {{ $ticket->estado == 'Cerrado' ? 'readonly' : '' }}>{{ old('observaciones_tecnicas', $ticket->observaciones_tecnicas) }}</textarea>
                            </div>
                            @if($ticket->estado != 'Cerrado')
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Diagnóstico</button>
                            @endif
                        </form>
                    </div>
                </div>
            </div>


            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingSeg">
                    <button class="accordion-button bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeg" aria-expanded="true" aria-controls="collapseSeg">
                        <i class="bi bi-chat-left-text text-warning me-2"></i> SEGUIMIENTO TÉCNICO
                    </button>
                </h2>
                <div id="collapseSeg" class="accordion-collapse collapse show" aria-labelledby="headingSeg">
                    <div class="accordion-body bg-white">
                        

                        <div class="timeline px-2 py-3 mb-4 ms-3">
                            @forelse($ticket->seguimientos->where('is_system', false) as $seg)
                                <div class="position-relative mb-4 ps-4">
                                    <div class="position-absolute start-0 translate-middle-x" style="top: 0px;">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($seg->user->name ?? 'U') }}&background=0D8ABC&color=fff&size=32" class="rounded-circle shadow-sm" alt="Avatar">
                                    </div>
                                    <div class="card shadow-sm border-0 border-start border-4 border-primary bg-light">
                                        <div class="card-body py-2 px-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <strong class="text-primary small">
                                                    {{ $seg->user->name ?? 'Usuario Desconocido' }} &bull; {{ $seg->tipo_avance }}
                                                </strong>
                                                <span class="text-muted" style="font-size: 0.75rem;">
                                                    {{ $seg->created_at->format('d M y, h:i A') }}
                                                </span>
                                            </div>
                                            <p class="mb-0 text-dark">
                                                {!! nl2br(e($seg->comentario)) !!}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted small py-3">Aún no hay comentarios técnicos registrados.</div>
                            @endforelse
                        </div>


                        @if($ticket->estado != 'Cerrado')
                        <div class="card border border-light shadow-sm bg-light">
                            <div class="card-body p-3">
                                <h6 class="fw-bold mb-3"><i class="bi bi-pencil-square text-primary"></i> Escribir Avance</h6>
                                <form action="{{ route('tickets.seguimiento', $ticket) }}" method="POST">
                                    @csrf
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Etiqueta del Avance</label>
                                            <input type="text" name="tipo_avance" class="form-control form-control-sm border" placeholder="Ej. Esperando repuesto..." required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <textarea name="comentario" rows="2" class="form-control form-control-sm border" placeholder="Escribe aquí los detalles del avance..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send"></i> Registrar Avance</button>
                                </form>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>


            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingSol">
                    <button class="accordion-button collapsed bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSol" aria-expanded="false" aria-controls="collapseSol">
                        <i class="bi bi-check-circle text-success me-2"></i> SOLUCIÓN Y CIERRE
                    </button>
                </h2>
                <div id="collapseSol" class="accordion-collapse collapse" aria-labelledby="headingSol">
                    <div class="accordion-body">
                        
                        @if(in_array($ticket->estado, ['Resuelto', 'Cerrado']))
                            <div class="alert alert-success mb-4 border-0 shadow-sm">
                                <h6 class="alert-heading fw-bold"><i class="bi bi-check-all"></i> CASO {{ strtoupper($ticket->estado) }}</h6>
                                <p class="mb-0 small">Este ticket ha sido marcado como solucionado el {{ $ticket->fecha_solucion?->format('d/m/Y H:i') }}.</p>
                            </div>
                        @endif

                        <form action="{{ route('tickets.solucion', $ticket) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label fw-bold">Solución Aplicada <span class="text-danger">*</span></label>
                                <textarea name="solucion_aplicada" rows="4" class="form-control" required {{ $ticket->estado == 'Cerrado' ? 'readonly' : '' }}>{{ old('solucion_aplicada', $ticket->solucion_aplicada) }}</textarea>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Tiempo Invertido <span class="text-danger">*</span></label>
                                    <input type="text" name="tiempo_invertido" class="form-control" placeholder="Ej. 2 horas, 45 minutos" value="{{ old('tiempo_invertido', $ticket->tiempo_invertido) }}" required {{ $ticket->estado == 'Cerrado' ? 'readonly' : '' }}>
                                    <small class="text-muted">Requerido para poder cerrar el ticket.</small>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Observaciones Finales <span class="text-danger">*</span></label>
                                <textarea name="observaciones_finales" rows="2" class="form-control" required {{ $ticket->estado == 'Cerrado' ? 'readonly' : '' }}>{{ old('observaciones_finales', $ticket->observaciones_finales) }}</textarea>
                            </div>
                            
                            @if($ticket->estado != 'Cerrado')
                            <div class="form-check mb-4 bg-light p-3 rounded border">
                                <input class="form-check-input ms-1" type="checkbox" name="cerrar_ticket" value="1" id="cerrarCheck">
                                <label class="form-check-label fw-bold text-danger ms-2" for="cerrarCheck">
                                    Cerrar Ticket Definitivamente (Se validarán todos los campos)
                                </label>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Guardar Solución</button>
                            @endif
                        </form>
                    </div>
                </div>
            </div>


            <div class="accordion-item border-0">
                <h2 class="accordion-header" id="headingHist">
                    <button class="accordion-button collapsed bg-light fw-bold text-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHist" aria-expanded="false" aria-controls="collapseHist">
                        <i class="bi bi-clock-history me-2"></i> HISTORIAL DE CAMBIOS (AUDITORÍA AUTOMÁTICA)
                    </button>
                </h2>
                <div id="collapseHist" class="accordion-collapse collapse" aria-labelledby="headingHist">
                    <div class="accordion-body bg-light">
                        <div class="timeline px-2 py-2 border-start border-3 border-secondary ms-2">
                            @forelse($ticket->seguimientos->where('is_system', true) as $seg)
                                <div class="position-relative mb-3 ps-3">
                                    <div class="position-absolute start-0 translate-middle-x" style="top: 0px;">
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center border border-2 border-white shadow-sm" style="width: 20px; height: 20px;">
                                            <i class="bi bi-robot" style="font-size: 9px;"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-muted small me-2">{{ $seg->created_at->format('d/m/Y H:i:s') }}</span>
                                        <strong class="text-secondary small">{{ $seg->tipo_avance }}:</strong> 
                                        <span class="small text-dark fst-italic">{{ $seg->comentario }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted small py-2">No hay registros de sistema.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>


<div class="modal fade" id="cambiarEstadoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tickets.estado', $ticket) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Actualizar Información General</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Estado del Ticket</label>
                        <select name="estado" class="form-select">
                            <option value="Abierto" {{ $ticket->estado == 'Abierto' ? 'selected' : '' }}>Abierto</option>
                            <option value="En Diagnóstico" {{ $ticket->estado == 'En Diagnóstico' ? 'selected' : '' }}>En Diagnóstico</option>
                            <option value="En Proceso" {{ $ticket->estado == 'En Proceso' ? 'selected' : '' }}>En Proceso</option>
                            <option value="Pendiente" {{ $ticket->estado == 'Pendiente' ? 'selected' : '' }}>Pendiente (Usuario/Proveedor)</option>
                            <option value="Resuelto" {{ $ticket->estado == 'Resuelto' ? 'selected' : '' }}>Resuelto</option>
                            <option value="Cerrado" {{ $ticket->estado == 'Cerrado' ? 'selected' : '' }}>Cerrado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reasignar Técnico</label>
                        <select name="user_id" class="form-select">
                            <option value="">-- Sin Asignar --</option>
                            @foreach($tecnicos as $t)
                                <option value="{{ $t->id }}" {{ $ticket->user_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
