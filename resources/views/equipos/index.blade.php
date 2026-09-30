@extends('layouts.inventario')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-control { min-height: 38px; padding: 6px 12px; }
    .ts-control > input { padding: 0 !important; }
</style>
@endpush

@section('title', 'Equipos')

@section('content')
<x-ui.toolbar 
    title="Inventario de Equipos" 
    icon="laptop" 
    createRoute="{{ auth()->user()->can('equipos.crear') ? route('equipos.create') : null }}" 
    createText="Nuevo Activo" 
/>

{{-- Filtros --}}
<x-ui.filter-container action="{{ route('equipos.index') }}" clearRoute="{{ route('equipos.index', ['clear' => 1]) }}">
    <div class="col-12 col-md-5">
        <label class="form-label fw-medium small mb-1">Buscar</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" enterkeyhint="search" name="buscar" id="buscadorEquipos" value="{{ request('buscar') }}"
                   class="form-control" placeholder="Marca, tipo, modelo, serial, placa, usuario..." autocomplete="off">
        </div>
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label fw-medium small mb-1">Tipo</label>
        <select name="tipo" id="filtroTipo" class="form-select">
            <option value="">Todos los tipos</option>
            @foreach($tipoRecursos as $tipo)
                <option value="{{ $tipo->id }}" {{ request('tipo') == $tipo->id ? 'selected' : '' }}>
                    {{ $tipo->nombre }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-2">
        <label class="form-label fw-medium small mb-1">Estado</label>
        <select name="estado" id="filtroEstado" class="form-select">
            <option value="">Todos</option>
            <option value="activo" {{ request('estado') === 'activo' ? 'selected' : '' }}>Asignado</option>
            <option value="responsabilidad" {{ request('estado') === 'responsabilidad' ? 'selected' : '' }}>Asignado Bajo Responsabilidad</option>
            <option value="disponible" {{ request('estado') === 'disponible' ? 'selected' : '' }}>Disponible</option>
            <option value="mantenimiento" {{ request('estado') === 'mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
            <option value="baja" {{ request('estado') === 'baja' ? 'selected' : '' }}>Baja</option>
        </select>
    </div>
</x-ui.filter-container>

{{-- Tabla --}}
<x-ui.card noPadding="true">
    <x-ui.table>
        <x-slot name="head">
                    @php
                        $renderHeader = function($posicion) use ($camposDinamicos) {
                            if(!isset($camposDinamicos)) return '';
                            $html = '';
                            foreach($camposDinamicos->where('posicion_grilla_despues_de', $posicion) as $cd) {
                                $nombreAMostrar = $cd->nombre === 'Estado De Uso' ? 'Distrito' : $cd->nombre;
                                $html .= "<th class='text-primary border-primary border-bottom-2'>{$nombreAMostrar}</th>";
                            }
                            return $html;
                        };
                    @endphp
                    <tr>
                        <th>#</th>
                        {!! $renderHeader('id') !!}
                        <th>Equipo / Serial</th>
                        {!! $renderHeader('equipo') !!}
                        <th>Serial Interno / Placa</th>
                        {!! $renderHeader('activo_fijo') !!}
                        <th>Tipo</th>
                        {!! $renderHeader('tipo_recurso_id') !!}
                        <th>Marca / Modelo</th>
                        {!! $renderHeader('marca') !!}
                        <th>Responsable</th>
                        {!! $renderHeader('responsable_cedula') !!}
                        <th>Funcionario Asignado</th>
                        {!! $renderHeader('funcionario_asignado') !!}
                        <th>Estado</th>
                        {!! $renderHeader('estado_operativo') !!}
                        {!! $renderHeader('') !!}
                        <th class="text-center" style="min-width: 130px;">Acciones</th>
                    </tr>
        </x-slot>
                    @forelse($equipos as $equipo)
                        <tr data-show-url="{{ route('equipos.show', $equipo) }}">
                            @php
                                $renderValor = function($posicion) use ($camposDinamicos, $equipo) {
                                    if(!isset($camposDinamicos)) return '';
                                    $html = '';
                                    foreach($camposDinamicos->where('posicion_grilla_despues_de', $posicion) as $cd) {
                                        if ($cd->nombre === 'Estado De Uso') {
                                            $distrito = $equipo->usuarioAsignado->distrito ?? '<span class="text-muted fst-italic">N/A</span>';
                                            $html .= "<td><span class='fw-medium text-dark'>{$distrito}</span></td>";
                                            continue;
                                        }

                                        $valorObj = $equipo->camposPersonalizadosValores->where('campo_personalizado_id', $cd->id)->first();
                                        $valor = $valorObj ? $valorObj->valor : '<span class="text-muted fst-italic">N/A</span>';
                                        
                                        // Formateo visual si es color o URL
                                        if($cd->tipo === 'url' && $valorObj) {
                                            $valor = "<a href='{$valor}' target='_blank' class='text-decoration-none text-primary'>Ver Link <i class='bi bi-box-arrow-up-right ms-1'></i></a>";
                                        }
                                        
                                        $html .= "<td><span class='fw-medium text-dark'>{$valor}</span></td>";
                                    }
                                    return $html;
                                };
                            @endphp
                            <td class="text-muted small">{{ $equipo->id }}</td>
                            {!! $renderValor('id') !!}
                            <td>
                                <span class="fw-bold fs-6">{{ $equipo->identificador_interno }}</span>
                                <br>
                                <span class="fw-medium text-dark">{{ $equipo->nombre_equipo }}</span>
                                <br><small class="text-muted">Serial: <span class="{{ $equipo->serial_visual === 'Sin serial' ? 'fst-italic' : 'font-monospace' }}">{{ $equipo->serial_visual }}</span></small>
                            </td>
                            {!! $renderValor('equipo') !!}
                            <td>
                                @php
                                    $invalidos = ['PENDIENTE', 'N/A', 'NA', 'NO TIENE', 'SIN PLACA', 'SIN REGISTRO'];
                                    $activoFijoStr = strtoupper(trim((string) $equipo->activo_fijo));
                                    $activoValido = !empty($equipo->activo_fijo) && !in_array($activoFijoStr, $invalidos, true);
                                @endphp
                                <span class="badge bg-secondary bg-opacity-50 font-monospace">
                                    {{ $equipo->placa ? $equipo->placa_visual : ($activoValido ? $equipo->activo_fijo : $equipo->placa_visual) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $tipoName = strtolower($equipo->tipoRecurso?->nombre ?? '');
                                    $bgClass = 'bg-secondary bg-opacity-75';
                                    if (str_contains($tipoName, 'portatil') || str_contains($tipoName, 'laptop')) {
                                        $bgClass = 'bg-primary';
                                    } elseif (str_contains($tipoName, 'escritorio') || str_contains($tipoName, 'desktop')) {
                                        $bgClass = 'bg-success';
                                    } elseif (str_contains($tipoName, 'impresora') || str_contains($tipoName, 'escaner')) {
                                        $bgClass = 'bg-info text-dark';
                                    } elseif (str_contains($tipoName, 'microfono') || str_contains($tipoName, 'camara')) {
                                        $bgClass = 'bg-warning text-dark';
                                    } elseif (str_contains($tipoName, 'servidor') || str_contains($tipoName, 'switch') || str_contains($tipoName, 'router')) {
                                        $bgClass = 'bg-danger';
                                    } elseif (str_contains($tipoName, 'telefono') || str_contains($tipoName, 'movil') || str_contains($tipoName, 'tablet')) {
                                        $bgClass = 'bg-dark';
                                    } elseif ($tipoName) {
                                        $bgClass = 'bg-secondary';
                                    }
                                @endphp
                                <span class="badge {{ $bgClass }}">
                                    {{ $equipo->tipoRecurso?->nombre ?? '—' }}
                                </span>
                            </td>
                            {!! $renderValor('tipo_recurso_id') !!}
                            <td>
                                {{ $equipo->marca }}
                                <br><small class="text-muted">{{ $equipo->modelo }}</small>
                            </td>
                            {!! $renderValor('marca') !!}
                            <td>
                                @php
                                    if ($equipo->asignacionResponsabilidadActiva && !empty($equipo->asignacionResponsabilidadActiva->responsable_nombre)) {
                                        $responsableNombre = trim((string) $equipo->asignacionResponsabilidadActiva->responsable_nombre);
                                        $responsableCedula = trim((string) $equipo->asignacionResponsabilidadActiva->responsable_cedula);
                                        $responsableRol = '';
                                    } else {
                                        $responsableNombre = trim((string) ($equipo->responsable_nombre ?? ''));
                                        $responsableCedula = trim((string) ($equipo->responsable_cedula ?? ''));
                                        $responsableRol = '';
                                    }
                                    
                                    // Fallback solicitado por el usuario: mostrar el usuario logueado si no hay responsable
                                    if ($responsableNombre === '') {
                                        $user = auth()->user();
                                        if ($user) {
                                            $responsableNombre = trim($user->name);
                                            $responsableRol = $user->roles->first()->name ?? '';
                                        }
                                    }
                                @endphp
                                @if($responsableNombre !== '')
                                    <span class="fw-medium">{{ $responsableNombre }}</span>
                                @else
                                    <span class="text-muted fst-italic">Sin responsable</span>
                                @endif
                                @if($responsableRol !== '')
                                    <br><small class="text-muted">{{ $responsableRol }}</small>
                                @elseif($responsableCedula !== '')
                                    <br><small class="text-muted">CC: {{ $responsableCedula }}</small>
                                @endif
                            </td>
                            {!! $renderValor('responsable_cedula') !!}
                            <td>
                                @if($equipo->usuarioAsignado)
                                    @php
                                        $nombreMostrar = trim((string) ($equipo->usuarioAsignado->nombre ?? ''));
                                        $cedulaAsignada = trim((string) ($equipo->usuarioAsignado->cedula ?? ''));
                                        $placeholders = ['SIN ASIGNAR', 'N/A', 'NA', 'NO APLICA', 'NULL', '-'];
                                        $nombreNormalizado = strtoupper($nombreMostrar);
                                        $tieneAsignacionReal =
                                            $nombreMostrar !== '' &&
                                            !in_array($nombreNormalizado, $placeholders, true);
                                    @endphp
                                    @if($tieneAsignacionReal)
                                        <span class="fw-medium">{{ $nombreMostrar }}</span>
                                        <br><small class="text-muted">CC: {{ $cedulaAsignada }}</small>
                                    @else
                                        <span class="text-muted fst-italic">Sin Asignar</span>
                                    @endif
                                @else
                                    <span class="text-muted fst-italic">Sin Asignar</span>
                                @endif

                                @if($equipo->asignacionResponsabilidadActiva)
                                    <div class="mt-2 pt-2 border-top">
                                        <span class="fw-medium text-info"><i class="bi bi-person-badge"></i> {{ $equipo->asignacionResponsabilidadActiva->nombre_usuario }}</span>
                                        <br><small class="text-muted">Doc: {{ $equipo->asignacionResponsabilidadActiva->documento ?? 'N/A' }} | {{ $equipo->asignacionResponsabilidadActiva->tipo_usuario ?? 'Temp.' }}</small>
                                        @if($equipo->asignacionResponsabilidadActiva->proyecto)
                                            <br><small class="text-primary" style="font-size: 0.75rem;"><i class="bi bi-briefcase me-1"></i>{{ $equipo->asignacionResponsabilidadActiva->proyecto }}</small>
                                        @endif
                                        
                                        @php
                                            $asignacionTemp = $equipo->asignacionResponsabilidadActiva;
                                            if ($asignacionTemp->fecha_final_estimada) {
                                                $diasRestantes = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($asignacionTemp->fecha_final_estimada)->startOfDay(), false);
                                                if ($diasRestantes < 0) {
                                                    echo '<br><span class="badge bg-danger mt-1">Vencida (' . abs($diasRestantes) . ' días)</span>';
                                                } elseif ($diasRestantes <= 3) {
                                                    echo '<br><span class="badge bg-danger mt-1">Vence en ' . $diasRestantes . ' días</span>';
                                                } elseif ($diasRestantes <= 7) {
                                                    echo '<br><span class="badge bg-warning text-dark mt-1">Vence en ' . $diasRestantes . ' días</span>';
                                                }
                                            }
                                        @endphp
                                    </div>
                                @endif
                            </td>
                            {!! $renderValor('funcionario_asignado') !!}
                            <td>
                                @php
                                    $nombreAsignado = trim((string) ($equipo->usuarioAsignado->nombre ?? ''));
                                    $placeholdersAsignacion = ['SIN ASIGNAR', 'N/A', 'NA', 'NO APLICA', 'NULL', '-'];
                                    $tieneFuncionarioReal =
                                        $nombreAsignado !== '' &&
                                        !in_array(strtoupper($nombreAsignado), $placeholdersAsignacion, true);

                                    $estadoMostrado = $equipo->estado_label;
                                    $badgeClass = $equipo->estado_badge;
                                    
                                    if ($equipo->asignacionResponsabilidadActiva && !$tieneFuncionarioReal) {
                                        // Solo mostrar "Asignado Bajo Responsabilidad" si NO hay
                                        // un funcionario asignado normalmente. Cuando ambos
                                        // coexisten, la asignación normal es el estado primario.
                                        $estadoMostrado = 'Asignado Bajo Responsabilidad';
                                        $badgeClass = 'info text-white';
                                    } elseif ($equipo->estado_operativo === 'mantenimiento' && !$tieneFuncionarioReal) {
                                        $estadoMostrado = 'Disponible / Mantenimiento';
                                    }
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">
                                    {{ $estadoMostrado }}
                                </span>
                            </td>
                            {!! $renderValor('estado_operativo') !!}
                            {!! $renderValor('') !!}
                            <td class="text-center align-middle">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        {{-- Acciones siempre disponibles --}}
                                        <li><a class="dropdown-item" href="{{ route('equipos.show', $equipo) }}"><i class="bi bi-eye text-info me-2"></i> Ver detalle</a></li>
                                        
                                        @can('equipos.editar')
                                        <li><a class="dropdown-item" href="{{ route('equipos.edit', $equipo) }}"><i class="bi bi-pencil text-warning me-2"></i> Editar</a></li>
                                        @endcan

                                        @can('equipos.crear')
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        @if(!$equipo->usuarioAsignado)
                                            {{-- Sin préstamo: mostrar botón Registrar préstamo solo si está activo --}}
                                            @if(in_array($equipo->estado_operativo, ['activo', 'disponible'], true))
                                            <li><button class="dropdown-item" type="button" onclick="abrirModalAsignacion({{ $equipo->id }}, '{{ addslashes($equipo->nombre_equipo) }}', 'asignacion')"><i class="bi bi-person-plus text-success me-2"></i> Asignar Funcionario</button></li>
                                            @else
                                            <li><button class="dropdown-item" type="button" onclick="abrirModalSimple({{ $equipo->id }}, '{{ addslashes($equipo->nombre_equipo) }}', 'restauracion')"><i class="bi bi-arrow-repeat text-success me-2"></i> Restaurar a Activo</button></li>
                                            @endif
                                        @else
                                            {{-- Ya asignado normalmente: opciones de gestión y ACTA --}}
                                            @if(in_array($equipo->estado_operativo, ['activo', 'asignado'], true))
                                            <li><button class="dropdown-item" type="button" onclick="abrirModalAsignacion({{ $equipo->id }}, '{{ addslashes($equipo->nombre_equipo) }}', 'reemplazo')"><i class="bi bi-arrow-left-right text-primary me-2"></i> Reemplazar Funcionario</button></li>
                                            
                                            <li><a class="dropdown-item" href="{{ route('equipos.acta', $equipo->id) }}" target="_blank"><i class="bi bi-file-earmark-pdf text-dark me-2"></i> Generar Acta de Entrega PDF</a></li>

                                            <li><button class="dropdown-item" type="button" onclick="abrirModalSimple({{ $equipo->id }}, '{{ addslashes($equipo->nombre_equipo) }}', 'mantenimiento')"><i class="bi bi-tools text-warning me-2"></i> Pasar a mantenimiento</button></li>
                                            @else
                                            <li><button class="dropdown-item" type="button" onclick="abrirModalSimple({{ $equipo->id }}, '{{ addslashes($equipo->nombre_equipo) }}', 'restauracion')"><i class="bi bi-arrow-repeat text-success me-2"></i> Restaurar a Activo</button></li>
                                            @endif
                                            
                                            <li><button class="dropdown-item" type="button" onclick="abrirModalSimple({{ $equipo->id }}, '{{ addslashes($equipo->nombre_equipo) }}', 'retiro')"><i class="bi bi-person-dash text-secondary me-2"></i> Retiro de funcionario</button></li>
                                            <li><button class="dropdown-item text-danger" type="button" onclick="abrirModalSimple({{ $equipo->id }}, '{{ addslashes($equipo->nombre_equipo) }}', 'baja')"><i class="bi bi-x-circle text-danger me-2"></i> Retiro definitivo del activo</button></li>
                                        @endif

                                        @if(!$equipo->asignacionResponsabilidadActiva)
                                            @if(in_array($equipo->estado_operativo, ['activo', 'disponible', 'asignado'], true))
                                            <li><button class="dropdown-item" type="button" onclick="abrirModalResponsabilidad({{ $equipo->id }}, '{{ addslashes($equipo->nombre_equipo) }}')"><i class="bi bi-person-badge text-info me-2"></i> Asignación Bajo Respons.</button></li>
                                            @endif
                                        @else
                                            <li><button class="dropdown-item" type="button" onclick="abrirModalResponsabilidadEdit({{ $equipo->id }}, '{{ addslashes($equipo->nombre_equipo) }}', {{ json_encode($equipo->asignacionResponsabilidadActiva) }})"><i class="bi bi-person-badge-fill text-info me-2"></i> Gestionar Asignación Bajo Respons.</button></li>
                                        @endif
                                        @endcan

                                        @can('equipos.eliminar')
                                        <li><hr class="dropdown-divider"></li>
                                        <li><button class="dropdown-item text-danger delete-btn-dropdown" type="button" data-delete-url="{{ route('equipos.destroy', $equipo) }}" data-delete-name="{{ $equipo->nombre_equipo }}" data-delete-require-confirm="true"><i class="bi bi-trash text-danger me-2"></i> Eliminar</button></li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No hay equipos que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
    </x-ui.table>
    @if($equipos->hasPages())
        <x-slot name="footer">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <small class="text-muted text-center text-md-start">
                    Mostrando {{ $equipos->firstItem() }}–{{ $equipos->lastItem() }} de {{ $equipos->total() }} equipos
                </small>
                <div class="pagination-compact-mobile">
                    {{ $equipos->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </x-slot>
    @endif
</x-ui.card>

{{-- ═══ MODAL: Acción con datos de usuario (préstamo / reemplazo) ══════════ --}}
<x-ui.modal id="modalAsignacion" title="Registrar Asignación" size="modal-lg modal-dialog-scrollable modal-fullscreen-md-down" formId="formAsignacion" action="{{ route('asignaciones.store') }}" submitId="btnConfirmarAsig">
    <input type="hidden" name="equipo_id" id="asig_equipo_id">
    <input type="hidden" name="tipo_accion" id="asig_tipo_accion">
    <input type="hidden" name="return_to" id="asig_return_to" value="{{ request()->fullUrl() }}">
                    <p class="text-muted small mb-3">
                        <i class="bi bi-laptop me-1"></i>
                        Equipo: <strong id="asig_nombre_equipo"></strong>
                    </p>

                    <div class="row g-3">
                        <input type="hidden" name="nombre" id="asig_nombre_hidden" required>
                        <input type="hidden" name="cedula" id="asig_cedula_hidden" required>
                        <input type="hidden" name="cargo" id="asig_cargo_hidden">
                        <input type="hidden" name="area" id="asig_area_hidden">
                        <input type="hidden" name="dependencia" id="asig_dependencia_hidden">
                        <input type="hidden" name="distrito" id="asig_distrito_hidden">
                        <input type="hidden" name="seccional" id="asig_seccional_hidden">
                        <input type="hidden" name="ciudad" id="asig_ciudad_hidden">
                        <input type="hidden" name="departamento" id="asig_departamento_hidden">
                        <input type="hidden" name="empresa_propietaria" id="asig_empresa_propietaria_hidden">
                        <input type="hidden" name="empresa_funcionario" id="asig_empresa_funcionario_hidden">
                        <input type="hidden" name="tipo_vinculacion" id="asig_tipo_vinculacion_hidden">
                        <input type="hidden" name="shortname" id="asig_shortname_hidden">

                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Selecciona un funcionario elegible. Si tiene activos, debe tener autorizaciones cargadas en el módulo Funcionarios.
                            </small>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAbrirSelectorFuncionario">
                                <i class="bi bi-people me-1"></i>Seleccionar funcionario elegible
                            </button>
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-3 bg-light" id="resumenFuncionarioSeleccionado">
                                <div class="text-muted">Aún no has seleccionado un funcionario.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <x-ui.input type="text" name="entregado_por" label="Entregado Por" value="{{ auth()->user()->name }}" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="date" name="fecha_accion" label="Fecha de Préstamo" value="{{ date('Y-m-d') }}" />
                        </div>
                    </div>
</x-ui.modal>

{{-- ═══ MODAL: Selector de funcionarios elegibles ══════════════════════════ --}}
<x-ui.modal id="modalSelectorFuncionario" title="Funcionarios elegibles" size="modal-lg modal-dialog-scrollable modal-fullscreen-md-down">
                <div class="mb-3">
                    <input type="text" id="filtroFuncionarioElegible" class="form-control" placeholder="Buscar por nombre, cédula, cargo o área...">
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>Cédula</th>
                                <th>Cargo / Área</th>
                                <th>Estado de elegibilidad</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tablaFuncionariosElegibles">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Cargando funcionarios...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
    <x-slot name="footer">
        <x-ui.button type="button" color="secondary" outline="true" data-bs-dismiss="modal" text="Cerrar" />
    </x-slot>
</x-ui.modal>

{{-- ═══ MODAL: Acción simple (retiro, baja, mantenimiento) ════════════════ --}}
<x-ui.modal id="modalSimple" title="Acción" size="modal-dialog-scrollable modal-fullscreen-md-down" formId="formSimple" action="{{ route('asignaciones.store') }}" submitText="Confirmar" submitIcon="check-lg" submitId="btnConfirmarSimple">
    <input type="hidden" name="equipo_id" id="simple_equipo_id">
    <input type="hidden" name="tipo_accion" id="simple_tipo_accion">
    <input type="hidden" name="return_to" id="simple_return_to" value="{{ request()->fullUrl() }}">
                    <p class="text-muted small mb-3">
                        <i class="bi bi-laptop me-1"></i>
                        Equipo: <strong id="simple_nombre_equipo"></strong>
                    </p>
                    <div class="mb-3" id="simple_motivo_wrap">
                        <x-ui.textarea name="motivo" id="simple_motivo" label="Motivo" required="true" rows="3" placeholder="Describa el motivo de esta acción..." />
                    </div>
                    <div class="mb-3">
                        <x-ui.textarea name="observaciones" id="simple_observaciones" label="Observaciones" rows="2" />
                    </div>
</x-ui.modal>

{{-- ═══ MODAL: Asignación Bajo Responsabilidad ════════════════ --}}
<x-ui.modal id="modalResponsabilidad" title="Asignación Bajo Responsabilidad" size="modal-lg modal-dialog-scrollable modal-fullscreen-md-down" formId="formResponsabilidad" action="">
    <input type="hidden" name="_method" id="resp_method" value="POST">
                    <p class="text-muted small mb-3">
                        <i class="bi bi-laptop me-1"></i>
                        Equipo: <strong id="resp_nombre_equipo"></strong>
                    </p>
                    <div class="row g-3">
                        {{-- SECCIÓN: RESPONSABLE ADMINISTRATIVO --}}
                        <div class="col-12 border-bottom pb-3 mb-2">
                            <h6 class="text-primary"><i class="bi bi-person-check me-2"></i>Responsable Administrativo</h6>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    Quien responde oficialmente por el activo. Obligatorio.
                                </small>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAbrirSelectorResponsable">
                                    <i class="bi bi-search me-1"></i>Buscar Responsable
                                </button>
                            </div>
                            <input type="hidden" name="responsable_id" id="resp_responsable_id" required>
                            <input type="hidden" name="responsable_nombre" id="resp_responsable_nombre" required>
                            <input type="hidden" name="responsable_cedula" id="resp_responsable_cedula" required>
                            <div class="border rounded p-3 bg-light" id="resumenResponsableSeleccionado">
                                <div class="text-muted">Aún no has seleccionado un responsable.</div>
                            </div>
                        </div>

                        {{-- SECCIÓN: USUARIO QUE UTILIZARÁ EL ACTIVO --}}
                        <div class="col-12">
                            <h6 class="text-info"><i class="bi bi-person-badge me-2"></i>Usuario que utilizará el activo</h6>
                            <small class="text-muted mb-3 d-block">Este NO es responsable administrativo. Es simplemente quien utilizará el equipo.</small>
                        </div>
                        
                        <div class="col-md-6">
                            <x-ui.select name="tipo_usuario" id="resp_tipo_usuario" label="Tipo de usuario" required="true">
<option value="">Seleccione...</option>
                                <option value="Temporal">Temporal</option>
                                <option value="Contratista">Contratista</option>
                                <option value="Practicante">Practicante</option>
                                <option value="Consultor">Consultor</option>
                                <option value="Visitante">Visitante</option>
                                <option value="Otro">Otro</option>
                            </x-ui.select>
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="text" name="nombre_usuario" id="resp_nombre_usuario" label="Nombre de Usuario" required="true" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="text" name="documento" id="resp_documento" label="Documento" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="text" name="empresa" id="resp_empresa" label="Empresa" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="text" name="cargo" id="resp_cargo" label="Cargo" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="text" name="proyecto" id="resp_proyecto" label="Proyecto" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="text" name="area" id="resp_area" label="Área" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="email" name="correo" id="resp_correo" label="Correo" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="text" name="telefono" id="resp_telefono" label="Teléfono" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="date" name="fecha_inicio" id="resp_fecha_inicio" label="Fecha de Inicio" required="true" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="date" name="fecha_final_estimada" id="resp_fecha_final_estimada" label="Fecha Final Estimada" />
                        </div>
                    </div>
    <x-slot name="footer">
        <div class="w-100 d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-danger d-none" id="btnFinalizarResponsabilidad">
                <i class="bi bi-x-circle me-1"></i>Finalizar Asignación
            </button>
            <div>
                <x-ui.button type="button" color="secondary" outline="true" data-bs-dismiss="modal" text="Cancelar" />
                <x-ui.button type="submit" color="info" class="text-white" icon="check-lg" text="Guardar" />
            </div>
        </div>
    </x-slot>
</x-ui.modal>

<form id="formFinalizarResponsabilidad" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

{{-- ═══ MODAL: Finalizar Asignación Bajo Responsabilidad ════════════════ --}}
<x-ui.modal id="modalFinalizarResponsabilidad" title="Finalizar Asignación" formId="formFinalizarRespReal" action="" method="POST" submitText="Confirmar Finalización" submitIcon="check2-circle">
    @method('DELETE')
                    <div class="mb-3">
                        <x-ui.input type="date" name="fecha_final_real" label="Fecha Final Real" required="true" value="{{ date('Y-m-d') }}" />
                    </div>
                    <div class="mb-3">
                        <x-ui.select name="motivo_finalizacion" label="Motivo de Finalización" required="true">
<option value="">Seleccione un motivo...</option>
                            <option value="Devolución por fin de contrato">Devolución por fin de contrato</option>
                            <option value="Reasignación a otro usuario">Reasignación a otro usuario</option>
                            <option value="Renuncia / Retiro">Renuncia / Retiro</option>
                            <option value="Daño / Pérdida del equipo">Daño / Pérdida del equipo</option>
                            <option value="Cambio de proyecto">Cambio de proyecto</option>
                            <option value="Otro">Otro</option>
                            </x-ui.select>
                    </div>
    <x-slot name="footer">
        <x-ui.button type="button" color="secondary" outline="true" data-bs-dismiss="modal" text="Cancelar" />
        <x-ui.button type="submit" color="danger" icon="check2-circle" text="Confirmar Finalización" />
    </x-slot>
</x-ui.modal>

@endsection

@push('scripts')
<script>
const LABELS_ACCION = {
    asignacion:    'Registrar Asignación',
    reemplazo:     'Reemplazar Funcionario',
    retiro:        'Retiro de funcionario',
    mantenimiento: 'Pasar a Mantenimiento',
    restauracion:  'Restaurar Equipo',
    baja:          'Retiro definitivo del activo',
};

const COLORES_ACCION = {
    asignacion:    'btn-success',
    reemplazo:     'btn-primary',
    retiro:        'btn-secondary',
    mantenimiento: 'btn-warning',
    restauracion:  'btn-success',
    baja:          'btn-danger',
};

const FUNCIONARIOS_ELEGIBLES_URL = @json(route('asignaciones.funcionarios-elegibles'));
let funcionariosElegiblesCache = [];
let funcionariosBloqueadosCache = [];

function poblarFormularioFuncionario(funcionario) {
    if (!funcionario) return;

    const setHidden = (id, value) => {
        const input = document.getElementById(id);
        if (input) input.value = value ?? '';
    };

    if (window.selectorTarget === 'responsable') {
        setHidden('resp_responsable_id', funcionario.id);
        setHidden('resp_responsable_nombre', funcionario.nombre);
        setHidden('resp_responsable_cedula', funcionario.identificacion);

        const resumenResp = document.getElementById('resumenResponsableSeleccionado');
        if (resumenResp) {
            resumenResp.innerHTML = `
                <div class="fw-semibold text-primary"><i class="bi bi-person-check me-2"></i>${funcionario.nombre || '—'}</div>
                <div class="small text-muted">
                    <i class="bi bi-card-text me-1"></i>CC: ${funcionario.identificacion || '—'} |
                    <i class="bi bi-building me-1"></i>${funcionario.cargo || '—'}
                </div>
            `;
        }
        return; // Termina aquí para el responsable
    }

    // Default: 'asignacion'
    setHidden('asig_nombre_hidden', funcionario.nombre);
    setHidden('asig_cedula_hidden', funcionario.identificacion);
    setHidden('asig_cargo_hidden', funcionario.cargo);
    setHidden('asig_area_hidden', funcionario.area);
    setHidden('asig_dependencia_hidden', funcionario.dependencia);
    setHidden('asig_distrito_hidden', funcionario.distrito);
    setHidden('asig_seccional_hidden', funcionario.seccional);
    setHidden('asig_ciudad_hidden', funcionario.ciudad);
    setHidden('asig_departamento_hidden', funcionario.departamento);
    setHidden('asig_empresa_propietaria_hidden', funcionario.empresa_propietaria);
    setHidden('asig_empresa_funcionario_hidden', funcionario.empresa_funcionario);
    setHidden('asig_tipo_vinculacion_hidden', funcionario.tipo_vinculacion);
    setHidden('asig_shortname_hidden', funcionario.shortname);

    const resumen = document.getElementById('resumenFuncionarioSeleccionado');
    if (resumen) {
        const estado = funcionario.activos_count === 0
            ? 'Sin activos'
            : `${funcionario.autorizaciones_count} acta(s) disponible(s)`;

        resumen.innerHTML = `
            <div class="fw-semibold">${funcionario.nombre || '—'}</div>
            <div class="small text-muted">CC: ${funcionario.identificacion || '—'}</div>
            <div class="small text-muted">${funcionario.cargo || '—'} / ${funcionario.area || '—'}</div>
            <div class="small mt-1"><span class="badge bg-info text-dark">${estado}</span></div>
        `;
    }
}

function renderFuncionariosElegibles(lista) {
    const tbody = document.getElementById('tablaFuncionariosElegibles');
    if (!tbody) return;

    if (!Array.isArray(lista) || lista.length === 0) {
        const filtro = (document.getElementById('filtroFuncionarioElegible')?.value || '').trim();
        if (filtro !== '' && funcionariosBloqueadosCache.length > 0) {
            const sugerencias = funcionariosBloqueadosCache.slice(0, 3).map((f) => {
                return `${f.nombre} (CC ${f.identificacion}): faltan ${f.autorizaciones_faltantes} autorizacion(es).`;
            }).join('<br>');

            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-warning py-4">No hay elegibles con ese criterio.<br><small>${sugerencias}</small></td></tr>`;
            return;
        }

        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No se encontraron funcionarios elegibles.</td></tr>';
        return;
    }

    tbody.innerHTML = lista.map((f) => {
        let estado = '';
        if (window.selectorTarget === 'responsable') {
            estado = `<span class="badge bg-info text-dark">${f.autorizaciones_count} autorización(es)</span>`;
        } else {
            if (f.autorizaciones_count > 0) {
                estado = `<span class="badge bg-info text-dark">${f.autorizaciones_count} acta(s) disponible(s)</span>`;
            } else if (f.activos_count === 0) {
                estado = '<span class="badge bg-success">Sin activos</span>';
            } else {
                estado = '<span class="badge bg-secondary">No elegible</span>';
            }
        }

        const cargoArea = [f.cargo, f.area].filter(Boolean).join(' / ') || '—';

        return `
            <tr>
                <td>${f.nombre || '—'}</td>
                <td>${f.identificacion || '—'}</td>
                <td>${cargoArea}</td>
                <td>${estado}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-primary" data-funcionario-id="${f.id}">
                        Seleccionar
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function abrirModalAsignacion(equipoId, nombreEquipo, tipo) {
    document.getElementById('asig_equipo_id').value   = equipoId;
    document.getElementById('asig_tipo_accion').value = tipo;
    document.getElementById('asig_return_to').value   = window.location.href;
    document.getElementById('asig_nombre_equipo').textContent = nombreEquipo;
    document.getElementById('modalAsignacionLabel').textContent = LABELS_ACCION[tipo] || tipo;

    const btn = document.getElementById('btnConfirmarAsig');
    btn.className = 'btn ' + (COLORES_ACCION[tipo] || 'btn-primary');
    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Confirmar';

    // Limpiar formulario
    document.getElementById('formAsignacion').querySelectorAll('input:not([type=hidden]):not([type=date]), textarea').forEach(el => {
        if (el.name !== 'entregado_por') el.value = '';
    });

    ['asig_nombre_hidden','asig_cedula_hidden','asig_cargo_hidden','asig_area_hidden','asig_dependencia_hidden','asig_distrito_hidden','asig_seccional_hidden','asig_ciudad_hidden','asig_departamento_hidden','asig_empresa_propietaria_hidden','asig_empresa_funcionario_hidden','asig_tipo_vinculacion_hidden','asig_shortname_hidden']
        .forEach((id) => {
            const input = document.getElementById(id);
            if (input) input.value = '';
        });

    const resumen = document.getElementById('resumenFuncionarioSeleccionado');
    if (resumen) {
        resumen.innerHTML = '<div class="text-muted">Aún no has seleccionado un funcionario.</div>';
    }

    new bootstrap.Modal(document.getElementById('modalAsignacion')).show();
}

function abrirModalSimple(equipoId, nombreEquipo, tipo) {
    document.getElementById('simple_equipo_id').value   = equipoId;
    document.getElementById('simple_tipo_accion').value = tipo;
    document.getElementById('simple_return_to').value   = window.location.href;
    document.getElementById('simple_nombre_equipo').textContent = nombreEquipo;
    document.getElementById('modalSimpleLabel').textContent = LABELS_ACCION[tipo] || tipo;

    const btn = document.getElementById('btnConfirmarSimple');
    btn.className = 'btn ' + (COLORES_ACCION[tipo] || 'btn-danger');

    const motivoWrap = document.getElementById('simple_motivo_wrap');
    const motivoInput = document.getElementById('simple_motivo');
    const observacionesInput = document.getElementById('simple_observaciones');
    const observacionesLabel = document.getElementById('simple_observaciones_label');

    if (['retiro', 'baja', 'mantenimiento', 'restauracion'].includes(tipo)) {
        motivoWrap.classList.add('d-none');
        motivoInput.required = false;
        motivoInput.value = '';
        observacionesInput.required = true;
        observacionesInput.placeholder = tipo === 'baja'
            ? 'Describa la observacion del retiro definitivo del activo...'
            : (tipo === 'mantenimiento'
                ? 'Describa la observacion del envío a mantenimiento...'
            : (tipo === 'retiro'
                ? 'Describa la observacion del retiro de funcionario...'
                : 'Describa la observacion de la restauracion...'));
        observacionesLabel.innerHTML = 'Observaciones <span class="text-danger">*</span>';
    } else {
        motivoWrap.classList.remove('d-none');
        motivoInput.required = true;
        observacionesInput.required = false;
        observacionesInput.placeholder = '';
        observacionesLabel.textContent = 'Observaciones';
    }

    document.getElementById('formSimple').querySelectorAll('textarea').forEach(el => el.value = '');

    new bootstrap.Modal(document.getElementById('modalSimple')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    const modalSelectorFuncionarioEl = document.getElementById('modalSelectorFuncionario');
    const modalSelectorFuncionario = modalSelectorFuncionarioEl ? new bootstrap.Modal(modalSelectorFuncionarioEl) : null;
    const btnAbrirSelectorFuncionario = document.getElementById('btnAbrirSelectorFuncionario');
    const filtroFuncionarioElegible = document.getElementById('filtroFuncionarioElegible');

    const cargarFuncionariosElegibles = (termino = '') => {
        const tbody = document.getElementById('tablaFuncionariosElegibles');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Cargando funcionarios...</td></tr>';
        }

        const url = new URL(FUNCIONARIOS_ELEGIBLES_URL, window.location.origin);
        if (termino.trim() !== '') {
            url.searchParams.set('q', termino.trim());
        }

        const equipoIdAsig = document.getElementById('asig_equipo_id')?.value;
        if (equipoIdAsig) {
            url.searchParams.set('equipo_id', equipoIdAsig);
        }

        if (window.selectorTarget === 'responsable') {
            url.searchParams.set('contexto', 'responsabilidad');
        }

        fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.json())
            .then((payload) => {
                funcionariosElegiblesCache = Array.isArray(payload.data) ? payload.data : [];
                funcionariosBloqueadosCache = Array.isArray(payload.bloqueados) ? payload.bloqueados : [];
                renderFuncionariosElegibles(funcionariosElegiblesCache);
            })
            .catch(() => {
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">No se pudo cargar la lista de funcionarios elegibles.</td></tr>';
                }
            });
    };

    const btnAbrirSelectorResponsable = document.getElementById('btnAbrirSelectorResponsable');

    if (btnAbrirSelectorFuncionario && modalSelectorFuncionario) {
        btnAbrirSelectorFuncionario.addEventListener('click', function() {
            window.selectorTarget = 'asignacion';
            if (filtroFuncionarioElegible) {
                filtroFuncionarioElegible.value = '';
            }
            modalSelectorFuncionario.show();
            cargarFuncionariosElegibles('');
        });
    }

    if (btnAbrirSelectorResponsable && modalSelectorFuncionario) {
        btnAbrirSelectorResponsable.addEventListener('click', function() {
            window.selectorTarget = 'responsable';
            if (filtroFuncionarioElegible) {
                filtroFuncionarioElegible.value = '';
            }
            // Ocultar modal principal para que funcione como hijo (modal sobre modal)
            const modalPrincipal = bootstrap.Modal.getInstance(document.getElementById('modalResponsabilidad'));
            if (modalPrincipal) {
                modalPrincipal.hide();
            }

            modalSelectorFuncionario.show();
            cargarFuncionariosElegibles('');
        });
    }

    if (modalSelectorFuncionarioEl) {
        modalSelectorFuncionarioEl.addEventListener('hidden.bs.modal', function () {
            if (window.selectorTarget === 'responsable') {
                // Volver a mostrar el modal principal y forzar scrollbar
                const modalRespEl = document.getElementById('modalResponsabilidad');
                if (modalRespEl) {
                    new bootstrap.Modal(modalRespEl).show();
                    setTimeout(() => {
                        document.body.classList.add('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 400);
                }
            }
        });
    }

    if (filtroFuncionarioElegible) {
        filtroFuncionarioElegible.addEventListener('input', function() {
            const termino = this.value.trim().toLowerCase();
            if (termino === '') {
                renderFuncionariosElegibles(funcionariosElegiblesCache);
                return;
            }

            const filtrados = funcionariosElegiblesCache.filter((f) => {
                const texto = [
                    f.nombre,
                    f.identificacion,
                    f.cargo,
                    f.area,
                    f.departamento,
                    f.ciudad,
                ].join(' ').toLowerCase();

                return texto.includes(termino);
            });

            renderFuncionariosElegibles(filtrados);
        });
    }

    const tablaFuncionariosElegibles = document.getElementById('tablaFuncionariosElegibles');
    if (tablaFuncionariosElegibles && modalSelectorFuncionario) {
        tablaFuncionariosElegibles.addEventListener('click', function(e) {
            const button = e.target.closest('button[data-funcionario-id]');
            if (!button) return;

            const funcionarioId = Number(button.getAttribute('data-funcionario-id'));
            const funcionario = funcionariosElegiblesCache.find((f) => Number(f.id) === funcionarioId) || null;
            if (!funcionario) return;

            poblarFormularioFuncionario(funcionario);
            modalSelectorFuncionario.hide();
        });
    }

    const formAsignacion = document.getElementById('formAsignacion');
    if(formAsignacion) {
        formAsignacion.addEventListener('submit', function(e) {
            const tipoAccion = document.getElementById('asig_tipo_accion').value;
            const cedulaSeleccionada = (document.getElementById('asig_cedula_hidden')?.value || '').trim();

            if (['asignacion', 'reemplazo'].includes(tipoAccion) && cedulaSeleccionada === '') {
                e.preventDefault();
                Swal.fire('Funcionario requerido', 'Debes seleccionar un funcionario elegible antes de confirmar.', 'warning');
                return;
            }

            if (tipoAccion === 'reemplazo') {
                e.preventDefault();
                Swal.fire({
                    title: 'Devolución requerida',
                    text: "Este activo actualmente se encuentra asignado. ¿Ya fue recibida y validada el Acta de Devolución?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, registrar devolución',
                    cancelButtonText: 'No'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const equipoId = document.getElementById('asig_equipo_id').value;
                        const token = document.querySelector('input[name="_token"]').value;

                        // Ejecutar silenciosamente el retiro
                        fetch("{{ route('asignaciones.store') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                equipo_id: equipoId,
                                tipo_accion: 'retiro',
                                motivo: 'Acta de devolución validada',
                                observaciones: 'Generado automáticamente antes de reasignar',
                                fecha_accion: new Date().toISOString().split('T')[0]
                            })
                        }).then(response => {
                            if(response.ok) {
                                // Cambiar el tipo a asignacion para el nuevo flujo
                                document.getElementById('asig_tipo_accion').value = 'asignacion';
                                formAsignacion.submit();
                            } else {
                                Swal.fire('Error', 'No se pudo registrar la devolución.', 'error');
                            }
                        }).catch(error => {
                            Swal.fire('Error', 'Ocurrió un error en la solicitud.', 'error');
                        });
                    } else {
                        Swal.fire('Operación cancelada', 'Debe validar la devolución antes de asignar a un nuevo funcionario.', 'info');
                    }
                });
            }
        });
    }

    const formSimple = document.getElementById('formSimple');
    if(formSimple) {
        formSimple.addEventListener('submit', function(e) {
            const tipoAccion = document.getElementById('simple_tipo_accion').value;
            if (tipoAccion === 'retiro') {
                e.preventDefault();
                Swal.fire({
                    title: 'Devolución requerida',
                    text: "¿Ya fue recibida y validada el Acta de Devolución?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, la entregó',
                    cancelButtonText: 'No'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Cambiamos a submit nativo para evitar ciclo infinito
                        formSimple.submit();
                    } else {
                        Swal.fire('Operación cancelada', 'Debe validar el Acta de Devolución antes de registrar el retiro.', 'info');
                    }
                });
            }
        });
    }



    const formResponsabilidad = document.getElementById('formResponsabilidad');
    if(formResponsabilidad) {
        formResponsabilidad.addEventListener('submit', function(e) {
            const responsableId = document.getElementById('resp_responsable_id').value;
            if (!responsableId) {
                e.preventDefault();
                Swal.fire('Atención', 'Debe buscar y seleccionar un Responsable Administrativo.', 'warning');
            }
        });
    }

});

function abrirModalResponsabilidad(equipoId, nombreEquipo) {
    const form = document.getElementById('formResponsabilidad');
    form.action = `/equipos/${equipoId}/asignacion-responsabilidad`;
    document.getElementById('resp_method').value = 'POST';
    document.getElementById('resp_nombre_equipo').textContent = nombreEquipo;
    document.getElementById('modalResponsabilidadLabel').innerHTML = '<i class="bi bi-person-badge me-2"></i>Nueva Asignación Bajo Responsabilidad';
    
    // Limpiar form
    form.reset();
    document.getElementById('resp_responsable_id').value = '';
    document.getElementById('resp_responsable_nombre').value = '';
    document.getElementById('resp_responsable_cedula').value = '';
    const resumenResp = document.getElementById('resumenResponsableSeleccionado');
    if (resumenResp) {
        resumenResp.innerHTML = '<div class="text-muted">Aún no has seleccionado un responsable.</div>';
    }
    document.getElementById('resp_fecha_inicio').value = new Date().toISOString().split('T')[0];
    
    document.getElementById('btnFinalizarResponsabilidad').classList.add('d-none');
    
    new bootstrap.Modal(document.getElementById('modalResponsabilidad')).show();
}

function abrirModalResponsabilidadEdit(equipoId, nombreEquipo, asignacion) {
    const form = document.getElementById('formResponsabilidad');
    form.action = `/equipos/${equipoId}/asignacion-responsabilidad/${asignacion.id}`;
    document.getElementById('resp_method').value = 'PUT';
    document.getElementById('resp_nombre_equipo').textContent = nombreEquipo;
    document.getElementById('modalResponsabilidadLabel').innerHTML = '<i class="bi bi-person-badge-fill me-2"></i>Gestionar Asignación Bajo Responsabilidad';
    
    // Llenar datos
    document.getElementById('resp_responsable_id').value = asignacion.responsable_id || '';
    document.getElementById('resp_responsable_nombre').value = asignacion.responsable_nombre || '';
    document.getElementById('resp_responsable_cedula').value = asignacion.responsable_cedula || '';
    
    const resumenResp = document.getElementById('resumenResponsableSeleccionado');
    if (resumenResp) {
        resumenResp.innerHTML = `
            <div class="fw-semibold text-primary"><i class="bi bi-person-check me-2"></i>${asignacion.responsable_nombre || '—'}</div>
            <div class="small text-muted">
                <i class="bi bi-card-text me-1"></i>CC: ${asignacion.responsable_cedula || '—'}
            </div>
        `;
    }
    
    document.getElementById('resp_tipo_usuario').value = asignacion.tipo_usuario || '';
    document.getElementById('resp_nombre_usuario').value = asignacion.nombre_usuario || '';
    document.getElementById('resp_documento').value = asignacion.documento || '';
    document.getElementById('resp_empresa').value = asignacion.empresa || '';
    document.getElementById('resp_cargo').value = asignacion.cargo || '';
    document.getElementById('resp_proyecto').value = asignacion.proyecto || '';
    document.getElementById('resp_area').value = asignacion.area || '';
    document.getElementById('resp_correo').value = asignacion.correo || '';
    document.getElementById('resp_telefono').value = asignacion.telefono || '';
    document.getElementById('resp_fecha_inicio').value = asignacion.fecha_inicio ? asignacion.fecha_inicio.split('T')[0] : '';
    document.getElementById('resp_fecha_final_estimada').value = asignacion.fecha_final_estimada ? asignacion.fecha_final_estimada.split('T')[0] : '';
    
    // Botón de finalizar
    const btnFin = document.getElementById('btnFinalizarResponsabilidad');
    btnFin.classList.remove('d-none');
    btnFin.onclick = function() {
        // Ocultar modal de edición y abrir el de finalización
        bootstrap.Modal.getInstance(document.getElementById('modalResponsabilidad')).hide();
        const formFin = document.getElementById('formFinalizarRespReal');
        formFin.action = `/equipos/${equipoId}/asignacion-responsabilidad/${asignacion.id}`;
        new bootstrap.Modal(document.getElementById('modalFinalizarResponsabilidad')).show();
    };
    
    new bootstrap.Modal(document.getElementById('modalResponsabilidad')).show();
}

// ====== FILTRO EN VIVO DE LA TABLA ======
let searchTimeoutId;
const searchInput = document.getElementById('buscadorEquipos');
const formFiltros = searchInput ? searchInput.closest('form') : null;
const tablaContainer = document.querySelector('.table-responsive');
const footerContainer = document.querySelector('.card-footer') ? document.querySelector('.card-footer').parentElement : null;

if (searchInput && formFiltros && tablaContainer) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeoutId);
        
        searchTimeoutId = setTimeout(() => {
            // Mostrar indicador de carga visualmente
            tablaContainer.style.opacity = '0.5';
            
            // Construir la URL con todos los filtros actuales del formulario
            const formData = new FormData(formFiltros);
            const urlObj = new URL(formFiltros.action);
            for (const [key, value] of formData.entries()) {
                if (value) {
                    urlObj.searchParams.set(key, value);
                } else {
                    urlObj.searchParams.delete(key);
                }
            }
            const url = urlObj.toString();

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.text())
            .then(html => {
                // Parsear el HTML devuelto
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Extraer el nuevo tbody
                const newTbody = doc.querySelector('.table-responsive tbody');
                const currentTbody = tablaContainer.querySelector('tbody');
                
                if (newTbody && currentTbody) {
                    currentTbody.innerHTML = newTbody.innerHTML;
                }
                
                // Extraer e inyectar la paginación (card-footer)
                const newFooter = doc.querySelector('.card-footer');
                const currentFooter = document.querySelector('.card-footer');
                
                if (newFooter && footerContainer) {
                    if (currentFooter) {
                        currentFooter.outerHTML = newFooter.outerHTML;
                    } else {
                        footerContainer.appendChild(newFooter);
                    }
                } else if (!newFooter && currentFooter) {
                    currentFooter.remove();
                }

                // Restaurar opacidad
                tablaContainer.style.opacity = '1';
                
                // Ya no actualizamos la URL en el navegador porque el usuario solicitó ocultar las rutas
                // window.history.pushState({}, '', url);
            })
            .catch(err => {
                console.error(err);
                tablaContainer.style.opacity = '1';
            });
        }, 400); // 400ms de retraso para evitar sobrecargar el servidor
    });
}

// Inicializar TomSelect en filtros
document.addEventListener('DOMContentLoaded', function() {
    new TomSelect('#filtroTipo', {
        placeholder: 'Todos los tipos',
        searchField: ['text'],
        maxOptions: null,
        onChange: function() {
            if (formFiltros) formFiltros.submit();
        }
    });
    
    new TomSelect('#filtroEstado', {
        placeholder: 'Todos',
        searchField: ['text'],
        onChange: function() {
            if (formFiltros) formFiltros.submit();
        }
    });
    
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchInput.blur();
            }
        });
    }

    // Entrar directamente a Ver (detalle) al tocar la fila en dispositivos móviles
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 768) {
            const tr = e.target.closest('tr[data-show-url]');
            if (tr && tr.closest('.table-responsive')) {
                if (e.target.closest('button') || e.target.closest('a') || e.target.closest('.dropdown-menu') || e.target.closest('input')) {
                    return;
                }
                let url = tr.getAttribute('data-show-url');
                if (url) {
                    try {
                        const tabId = sessionStorage.getItem('_tab_id');
                        if (tabId) {
                            const u = new URL(url, window.location.origin);
                            u.searchParams.set('_tab', tabId);
                            url = u.toString();
                        }
                    } catch (err) {}
                    
                    // Mostrar un toast para dar feedback inmediato al usuario
                    if (typeof showToast === 'function') {
                        showToast('Cargando detalle del equipo...', 'info');
                    } else {
                        document.body.style.opacity = '0.7';
                    }
                    
                    window.location.href = url;
                }
            }
        }
    });
});


</script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
@endpush
