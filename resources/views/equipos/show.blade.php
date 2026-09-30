@extends('layouts.inventario')

@section('title', 'Detalle del Equipo')

@section('content')
<x-ui.toolbar 
    title="{{ $equipo->identificador_interno }} - {{ $equipo->nombre_equipo }}" 
    icon="info-circle" 
    backRoute="{{ route('equipos.index') }}"
>
    <x-slot name="titleSuffix">
        <x-ui.badge color="{{ $equipo->estado_badge }}" class="ms-2 fs-6" text="{{ $equipo->estado_label }}" />
    </x-slot>

    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-journal-text me-1"></i> Historiales
        </button>
        <ul class="dropdown-menu shadow-sm">
            <li><a class="dropdown-item" href="{{ route('equipos.historial-vida', ['equipo' => $equipo->id, 'return_to' => url()->current()]) }}"><i class="bi bi-clock-history text-primary me-2"></i> Historial de Vida</a></li>
            <li><a class="dropdown-item" href="{{ route('historial-tecnico.por-equipo', ['equipo' => $equipo->id, 'return_to' => url()->current()]) }}"><i class="bi bi-tools text-warning me-2"></i> Historial Técnico</a></li>
            <li><a class="dropdown-item" href="{{ route('asignaciones.por-equipo', ['equipo' => $equipo->id, 'return_to' => url()->current()]) }}"><i class="bi bi-person-fill-gear text-success me-2"></i> Historial Asignaciones</a></li>
        </ul>
    </div>
    <x-ui.button href="{{ route('equipos.edit', ['equipo' => $equipo->id, 'return_to' => url()->current()]) }}" color="warning" class="text-white" icon="pencil" text="Editar" />
</x-ui.toolbar>

<div class="row g-4">
    {{-- COLUMNA IZQUIERDA --}}
    <div class="col-lg-6 d-flex flex-column gap-4">
        {{-- Datos del equipo --}}
        <x-ui.card title="Datos del Equipo" icon="laptop" headerClass="bg-primary" iconColor="primary">

                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Tipo</dt>
                    <dd class="col-sm-8">{{ $equipo->tipoRecurso?->nombre ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">ID Sistema</dt>
                    <dd class="col-sm-8 fw-bold">{{ $equipo->identificador_interno }}</dd>
                    <dt class="col-sm-4 text-muted">Serial</dt>
                    <dd class="col-sm-8">
                        <span class="font-monospace">{{ $equipo->serial_visual }}</span>
                    </dd>
                    <dt class="col-sm-4 text-muted">Activo Fijo</dt>
                    <dd class="col-sm-8 font-monospace fw-bold text-dark">
                        @php
                            $invalidos = ['PENDIENTE', 'N/A', 'NA', 'NO TIENE', 'SIN PLACA', 'SIN REGISTRO'];
                            $activoFijoStr = strtoupper(trim((string) $equipo->activo_fijo));
                            $activoValido = !empty($equipo->activo_fijo) && !in_array($activoFijoStr, $invalidos, true);
                        @endphp
                        {{ $activoValido ? $equipo->activo_fijo : '—' }}
                    </dd>
                    <dt class="col-sm-4 text-muted">Placa</dt>
                    <dd class="col-sm-8 font-monospace">{{ $equipo->placa_visual }}</dd>
                    <dt class="col-sm-4 text-muted">Marca</dt>
                    <dd class="col-sm-8">{{ $equipo->marca }}</dd>
                    <dt class="col-sm-4 text-muted">Modelo</dt>
                    <dd class="col-sm-8">{{ $equipo->modelo }}</dd>
                    <dt class="col-sm-4 text-muted">Procesador</dt>
                    <dd class="col-sm-8">{{ $equipo->procesador ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">RAM</dt>
                    <dd class="col-sm-8">{{ $equipo->ram ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Disco</dt>
                    <dd class="col-sm-8">{{ $equipo->disco ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Sistema Op.</dt>
                    <dd class="col-sm-8">{{ $equipo->sistema_operativo ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Fecha Compra</dt>
                    <dd class="col-sm-8">{{ $equipo->fecha_compra?->format('d/m/Y') ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Fin Garantía</dt>
                    <dd class="col-sm-8">{{ $equipo->fin_garantia?->format('d/m/Y') ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Tiempo de Uso</dt>
                    <dd class="col-sm-8">{{ $equipo->tiempo_uso ?? '—' }}</dd>
                    @if($equipo->razon_estado)
                        <dt class="col-sm-4 text-muted">Razón Estado</dt>
                        <dd class="col-sm-8">{{ $equipo->razon_estado }}</dd>
                    @endif
                </dl>
</x-ui.card>

        {{-- Campos Personalizados (Información Adicional) --}}
        @if($equipo->camposPersonalizadosValores->isNotEmpty())
        <x-ui.card title="Información Adicional" icon="ui-checks-grid" headerClass="bg-dark" iconColor="dark">

                <dl class="row mb-0">
                    @foreach($equipo->camposPersonalizadosValores as $cv)
                        @if($cv->campoPersonalizado && $cv->campoPersonalizado->visible)
                            <dt class="col-sm-4 text-muted">{{ $cv->campoPersonalizado->nombre }}</dt>
                            <dd class="col-sm-8">
                                @if($cv->campoPersonalizado->tipo === 'boolean')
                                    <x-ui.badge color="{{ $cv->valor == '1' ? 'success' : 'secondary' }}" text="{{ $cv->valor == '1' ? 'Sí' : 'No' }}" />
                                @elseif($cv->campoPersonalizado->tipo === 'url' && filter_var($cv->valor, FILTER_VALIDATE_URL))
                                    <a href="{{ $cv->valor }}" target="_blank" class="text-break"><i class="bi bi-link-45deg"></i> Ver Enlace</a>
                                @elseif($cv->campoPersonalizado->tipo === 'multiselect')
                                    @php
                                        $valores = is_string($cv->valor) ? json_decode($cv->valor, true) : $cv->valor;
                                    @endphp
                                    @if(is_array($valores))
                                        {{ implode(', ', $valores) }}
                                    @else
                                        {{ $cv->valor ?: '—' }}
                                    @endif
                                @else
                                    {{ $cv->valor ?: '—' }}
                                @endif
                            </dd>
                        @endif
                    @endforeach
                </dl>
</x-ui.card>
        @endif

        {{-- Complementos del Activo --}}
        @if($equipo->complementos->isNotEmpty() || auth()->user()->can('equipos.editar'))
            @include('equipos._complementos_show')
        @endif

        {{-- Periféricos --}}
        <x-ui.card title="Periféricos" icon="usb-plug" headerClass="bg-warning" iconColor="warning">

                @if($equipo->periferico)
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Teléfono Fijo</dt>
                        <dd class="col-sm-8">{{ $equipo->periferico->telefono ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Teclado</dt>
                        <dd class="col-sm-8">{{ $equipo->periferico->teclado ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Mouse</dt>
                        <dd class="col-sm-8">{{ $equipo->periferico->mouse ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Cámara</dt>
                        <dd class="col-sm-8">{{ $equipo->periferico->camara ?? '—' }}</dd>
                    </dl>
                @else
                    <p class="text-muted mb-0">Sin periféricos registrados.</p>
                @endif
</x-ui.card>
    </div>

    {{-- COLUMNA DERECHA --}}
    <div class="col-lg-6 d-flex flex-column gap-4">
        {{-- Funcionario Asignado / Asignación Bajo Responsabilidad --}}
        @if($equipo->asignacionResponsabilidadActiva)
        <div class="card border-0 shadow-sm border-start border-info border-3">
            <div class="card-header bg-info bg-opacity-10 fw-semibold border-0 py-3 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-badge me-2 text-info"></i>Asignación Bajo Responsabilidad</span>
                <x-ui.badge color="info" class="text-white" text="Temporal" />
            </div>
            <div class="card-body">
                <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-person-check me-2"></i>Responsable Administrativo</h6>
                <dl class="row mb-4">
                    <dt class="col-sm-4 text-muted">Responsable</dt>
                    <dd class="col-sm-8 fw-bold">{{ $equipo->asignacionResponsabilidadActiva->responsable_nombre ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Cédula</dt>
                    <dd class="col-sm-8">{{ $equipo->asignacionResponsabilidadActiva->responsable_cedula ?? '—' }}</dd>
                </dl>

                <h6 class="text-info border-bottom pb-2 mb-3"><i class="bi bi-person-badge me-2"></i>Usuario Temporal</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Tipo de usuario</dt>
                    <dd class="col-sm-8">{{ $equipo->asignacionResponsabilidadActiva->tipo_usuario ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Usuario Temporal</dt>
                    <dd class="col-sm-8 fw-bold text-info">{{ $equipo->asignacionResponsabilidadActiva->nombre_usuario }}</dd>
                    <dt class="col-sm-4 text-muted">Documento</dt>
                    <dd class="col-sm-8">{{ $equipo->asignacionResponsabilidadActiva->documento ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Empresa</dt>
                    <dd class="col-sm-8">{{ $equipo->asignacionResponsabilidadActiva->empresa ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Proyecto</dt>
                    <dd class="col-sm-8 fw-bold">{{ $equipo->asignacionResponsabilidadActiva->proyecto ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Cargo</dt>
                    <dd class="col-sm-8">{{ $equipo->asignacionResponsabilidadActiva->cargo ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Área</dt>
                    <dd class="col-sm-8">{{ $equipo->asignacionResponsabilidadActiva->area ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Correo</dt>
                    <dd class="col-sm-8">{{ $equipo->asignacionResponsabilidadActiva->correo ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Teléfono</dt>
                    <dd class="col-sm-8">{{ $equipo->asignacionResponsabilidadActiva->telefono ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Fecha Inicio</dt>
                    <dd class="col-sm-8">{{ $equipo->asignacionResponsabilidadActiva->fecha_inicio?->format('d/m/Y') ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Fecha Estimada Fin</dt>
                    <dd class="col-sm-8">
                        @php
                            $asignacionTemp = $equipo->asignacionResponsabilidadActiva;
                            if ($asignacionTemp->fecha_final_estimada) {
                                echo $asignacionTemp->fecha_final_estimada->format('d/m/Y');
                                $diasRestantes = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($asignacionTemp->fecha_final_estimada)->startOfDay(), false);
                                if ($diasRestantes < 0) {
                                    echo ' <span class="badge bg-danger ms-2">Vencida (' . abs($diasRestantes) . ' días)</span>';
                                } elseif ($diasRestantes <= 3) {
                                    echo ' <span class="badge bg-danger ms-2">Vence en ' . $diasRestantes . ' días</span>';
                                } elseif ($diasRestantes <= 7) {
                                    echo ' <span class="badge bg-warning text-dark ms-2">Vence en ' . $diasRestantes . ' días</span>';
                                }
                            } else {
                                echo '—';
                            }
                        @endphp
                    </dd>
                    <dt class="col-sm-4 text-muted">Tiempo Restante</dt>
                    <dd class="col-sm-8">
                        @if($asignacionTemp->fecha_final_estimada)
                            @php
                                $dias = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($asignacionTemp->fecha_final_estimada)->startOfDay(), false);
                                if ($dias < 0) echo '<span class="text-danger fw-bold">Vencido hace ' . abs($dias) . ' días</span>';
                                elseif ($dias == 0) echo '<span class="text-warning fw-bold">Vence hoy</span>';
                                else echo $dias . ' días';
                            @endphp
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="col-sm-4 text-muted">Estado</dt>
                    <dd class="col-sm-8"><x-ui.badge color="success" text="Activa" /></dd>
                    <dt class="col-sm-4 text-muted">Observaciones</dt>
                    <dd class="col-sm-8">{{ $equipo->asignacionResponsabilidadActiva->observaciones ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted mt-3">Registrado por</dt>
                    <dd class="col-sm-8 mt-3"><small><i class="bi bi-person me-1"></i>{{ $equipo->asignacionResponsabilidadActiva->registradoPor->name ?? '—' }}</small></dd>
                    <dt class="col-sm-4 text-muted">Fecha Creación</dt>
                    <dd class="col-sm-8"><small><i class="bi bi-calendar3 me-1"></i>{{ $equipo->asignacionResponsabilidadActiva->created_at?->format('d/m/Y H:i A') ?? '—' }}</small></dd>
                </dl>
            </div>
        </div>
        @endif

        {{-- Préstamo Temporal --}}
        @if($equipo->prestamoVigente)
        <div class="card border-0 shadow-sm border-start border-primary border-3 mb-4">
            <div class="card-header bg-primary bg-opacity-10 fw-semibold border-0 py-3 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar2-range me-2 text-primary"></i>Préstamo Temporal Activo</span>
                <x-ui.badge color="{{ $equipo->prestamoVigente->estado_badge }}" class="text-white" text="{{ $equipo->prestamoVigente->estado }}" />
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Persona Receptora</dt>
                    <dd class="col-sm-8 fw-bold text-primary">{{ $equipo->prestamoVigente->persona_nombre }}</dd>
                    
                    @if($equipo->prestamoVigente->persona_documento)
                    <dt class="col-sm-4 text-muted">Documento</dt>
                    <dd class="col-sm-8">{{ $equipo->prestamoVigente->persona_documento }}</dd>
                    @endif
                    
                    <dt class="col-sm-4 text-muted">Desde</dt>
                    <dd class="col-sm-8">{{ $equipo->prestamoVigente->fecha_inicio->format('d/m/Y H:i') }}</dd>
                    
                    <dt class="col-sm-4 text-muted">Devolución Prevista</dt>
                    <dd class="col-sm-8">
                        {{ $equipo->prestamoVigente->fecha_devolucion_prevista->format('d/m/Y H:i') }}
                        @if($equipo->prestamoVigente->fecha_devolucion_prevista < now())
                            <span class="badge bg-danger ms-2">Vencido</span>
                        @endif
                    </dd>
                    
                    @if($equipo->prestamoVigente->motivo)
                    <dt class="col-sm-4 text-muted">Motivo</dt>
                    <dd class="col-sm-8">{{ $equipo->prestamoVigente->motivo }}</dd>
                    @endif
                </dl>
                
                <div class="mt-3 text-end border-top pt-3">
                    <a href="{{ route('prestamos.show', $equipo->prestamoVigente) }}" class="btn btn-sm btn-outline-primary">
                        Ver Detalles del Préstamo
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Funcionario Asignado (Normal) --}}
        <x-ui.card title="Funcionario Asignado" icon="person" headerClass="bg-success" iconColor="success">
    <x-slot name="headerActions">
        @if($equipo->usuarioAsignado)
                    <x-ui.badge color="success" text="Asignado" />
                @else
                    <x-ui.badge color="secondary" text="Sin Asignar" />
                @endif
    </x-slot>

                @if($equipo->usuarioAsignado)
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Nombre</dt>
                        <dd class="col-sm-8 fw-bold text-success">{{ $equipo->usuarioAsignado->nombre }}</dd>
                        <dt class="col-sm-4 text-muted">Cédula</dt>
                        <dd class="col-sm-8">{{ $equipo->usuarioAsignado->cedula }}</dd>
                        <dt class="col-sm-4 text-muted">Empresa Propietaria</dt>
                        <dd class="col-sm-8">{{ $equipo->usuarioAsignado->empresa_propietaria ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Dependencia</dt>
                        <dd class="col-sm-8">{{ $equipo->usuarioAsignado->dependencia ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Fuente de Recurso</dt>
                        <dd class="col-sm-8">{{ $equipo->usuarioAsignado->fuente_recurso ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Empresa Funcionario</dt>
                        <dd class="col-sm-8">{{ $equipo->usuarioAsignado->empresa_funcionario ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Emp. o Contratista</dt>
                        <dd class="col-sm-8">{{ $equipo->usuarioAsignado->tipo_vinculacion ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Shortname</dt>
                        <dd class="col-sm-8">{{ $equipo->usuarioAsignado->shortname ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Departamento</dt>
                        <dd class="col-sm-8">{{ $equipo->usuarioAsignado->departamento ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Ciudad</dt>
                        <dd class="col-sm-8">{{ $equipo->usuarioAsignado->ciudad ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Cargo</dt>
                        <dd class="col-sm-8">{{ $equipo->usuarioAsignado->cargo ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Área</dt>
                        <dd class="col-sm-8">{{ $equipo->usuarioAsignado->area ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Piso</dt>
                        <dd class="col-sm-8">{{ $equipo->usuarioAsignado->piso ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Distrito</dt>
                        <dd class="col-sm-8 fw-bold">{{ $equipo->usuarioAsignado->distrito ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Seccional</dt>
                        <dd class="col-sm-8 fw-bold">{{ $equipo->usuarioAsignado->seccional ?? '—' }}</dd>
                    </dl>
                @else
                    <p class="text-muted mb-0">Sin funcionario asignado.</p>
                @endif
</x-ui.card>

        {{-- Responsable del Activo --}}
        @if($equipo->responsable_nombre)
        <x-ui.card title="Responsable del Activo" icon="person-badge" headerClass="bg-info" iconColor="info" class="border-start border-info border-3">
    <x-slot name="header">
        <div>
            <i class="bi bi-person-badge me-2 text-info"></i>
            <span class="fw-bold">Responsable del Activo</span>
            <small class="text-muted d-block ps-4">Persona responsable de la administración y control del activo durante su ciclo de vida. 
                    Esta responsabilidad es independiente del usuario al que se encuentre asignado el equipo.</small>
        </div>
    </x-slot>

                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Nombre</dt>
                    <dd class="col-sm-8 fw-bold">{{ $equipo->responsable_nombre }}</dd>
                    <dt class="col-sm-4 text-muted">Cédula</dt>
                    <dd class="col-sm-8">{{ $equipo->responsable_cedula ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Cargo</dt>
                    <dd class="col-sm-8">{{ $equipo->responsable_cargo ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Ciudad</dt>
                    <dd class="col-sm-8">{{ $equipo->responsable_ciudad ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Área</dt>
                    <dd class="col-sm-8">{{ $equipo->responsable_area ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Tipo Recurso</dt>
                    <dd class="col-sm-8">{{ $equipo->responsable_tipo_recurso ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Fechas</dt>
                    <dd class="col-sm-8">
                        {{ $equipo->fecha_inicio_responsable?->format('d/m/Y') ?? '—' }}
                        al
                        {{ $equipo->fecha_fin_responsable?->format('d/m/Y') ?? '—' }}
                    </dd>
                </dl>
</x-ui.card>
        @endif

    </div>
</div>

<div class="row g-4 mt-1">
    {{-- Últimas asignaciones --}}
    @if($equipo->asignaciones->isNotEmpty())
    <div class="col-lg-6">
        <x-ui.card title="Últimas Asignaciones" icon="person-fill-gear" headerClass="bg-success" iconColor="success" noPadding="true">
    <x-slot name="headerActions">
        <x-ui.button href="{{ route('asignaciones.por-equipo', $equipo) }}" color="success" outline="true" size="sm" text="Ver todas" />
    </x-slot>

                <x-ui.table class="table table-sm table-hover mb-0">
    <x-slot name="head">
        <tr>
                                <th>Tipo</th>
                                <th>Usuario</th>
                                <th>Fecha</th>
                            </tr>
    </x-slot>
    @foreach($equipo->asignaciones as $asig)
                            <tr>
                                <td>
                                    <x-ui.badge color="{{ $asig->tipo_accion_color }}" text="{{ $asig->tipo_accion_label }}" />
                                </td>
                                <td class="small">{{ $asig->usuario_nombre ?? '—' }}</td>
                                <td class="small">{{ $asig->fecha_accion?->format('d/m/Y') }}</td>
                            </tr>
                            @endforeach
</x-ui.table>
</x-ui.card>
    </div>
    @endif

    {{-- Últimos eventos técnicos --}}
    @if($equipo->historialTecnico->isNotEmpty())
    <div class="col-lg-6">
        <x-ui.card title="Últimos Eventos Técnicos" icon="tools" headerClass="bg-warning" iconColor="warning" noPadding="true">
    <x-slot name="headerActions">
        <x-ui.button href="{{ route('historial-tecnico.por-equipo', $equipo) }}" color="warning" outline="true" size="sm" text="Ver todos" />
    </x-slot>

                <x-ui.table class="table table-sm table-hover mb-0">
    <x-slot name="head">
        <tr>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Fecha</th>
                            </tr>
    </x-slot>
    @foreach($equipo->historialTecnico as $ht)
                            <tr>
                                <td>
                                    <x-ui.badge color="{{ $ht->tipo_evento_color }}"><i class="bi {{ $ht->tipo_evento_icono }}"></i></x-ui.badge>
                                </td>
                                <td class="small">{{ Str::limit($ht->descripcion, 50) }}</td>
                                <td class="small">{{ $ht->fecha_evento?->format('d/m/Y') }}</td>
                            </tr>
                            @endforeach
</x-ui.table>
</x-ui.card>
    </div>
    @endif

    {{-- Licencias Asignadas --}}
    @if($equipo->licenciaAsignaciones && $equipo->licenciaAsignaciones->isNotEmpty())
    <div class="col-lg-12">
        <x-ui.card title="Licencias Asignadas" icon="key" headerClass="bg-info" iconColor="info" noPadding="true">

                <x-ui.table class="table table-sm table-hover mb-0">
    <x-slot name="head">
        <tr>
                                <th>Licencia</th>
                                <th>Tipo</th>
                                <th>Fecha Asignación</th>
                                <th>Estado</th>
                            </tr>
    </x-slot>
    @foreach($equipo->licenciaAsignaciones as $la)
                            <tr>
                                <td>
                                    @if($la->licencia)
                                        <a href="{{ route('licencias.show', $la->licencia) }}" class="text-decoration-none fw-medium">
                                            {{ $la->licencia->nombre }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $la->licencia ? $la->licencia->tipo_licencia : 'N/A' }}</td>
                                <td class="small">{{ $la->fecha_asignacion?->format('d/m/Y') }}</td>
                                <td>
                                    <x-ui.badge color="{{ $la->estado === 'Activa' ? 'success' : 'secondary' }}" text="{{ $la->estado }}" />
                                </td>
                            </tr>
                            @endforeach
</x-ui.table>
</x-ui.card>
    </div>
    @endif

    {{-- Checklists --}}
    <div class="col-12">
        <x-ui.card title="Checklists Técnicos" icon="clipboard-check" headerClass="bg-secondary" iconColor="secondary" noPadding="true">
    <x-slot name="headerActions">
        <x-ui.button href="{{ route('checklists.create') }}?equipo_id={{ $equipo->id }}" color="primary" outline="true" size="sm" icon="plus-lg" text="Nuevo Checklist" />
    </x-slot>

                <x-ui.table class="table table-hover mb-0">
    <x-slot name="head">
        <tr>
                                <th>Responsable TI</th>
                                <th>Orden de Trabajo</th>
                                <th>Resultado</th>
                                <th>FNC</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
    </x-slot>
    @forelse($equipo->checklists as $cl)
                                <tr>
                                    <td>{{ $cl->responsable_ti ?? '—' }}</td>
                                    <td>{{ $cl->orden_trabajo ?? '—' }}</td>
                                    <td>{{ $cl->resultado ?? '—' }}</td>
                                    <td>{{ $cl->fnc ?? '—' }}</td>
                                    <td>{{ $cl->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <x-ui.button href="{{ route('checklists.show', $cl) }}" color="info" outline="true" size="sm" icon="eye" />
                                        <x-ui.button href="{{ route('checklists.edit', $cl) }}" color="warning" outline="true" size="sm" icon="pencil" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Sin checklists registrados.</td>
                                </tr>
                            @endforelse
</x-ui.table>
</x-ui.card>
    </div>
</div>
@endsection
