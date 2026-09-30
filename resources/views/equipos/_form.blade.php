{{-- Partial compartido: campos de equipo para create/edit --}}

@push('styles')
<style>
    /* Corrección de conflicto entre Tailwind (.collapse = visibility: collapse) y Bootstrap Accordion */
    .accordion-collapse {
        visibility: visible !important;
    }
</style>
@endpush

{{-- === DATOS DEL EQUIPO === --}}
<div class="card mb-4">
    <div class="card-header bg-primary bg-opacity-10 border-0 fw-semibold py-3">
        <i class="bi bi-laptop me-2 text-primary"></i>Datos del Equipo
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-medium">Tipo de Recurso <span class="text-danger">*</span></label>
                <select name="tipo_recurso_id"
                        class="form-select @error('tipo_recurso_id') is-invalid @enderror"
                        required>
                    <option value="">Seleccione...</option>
                    @foreach($tipoRecursos as $tipo)
                        <option value="{{ $tipo->id }}"
                            data-prefijo="{{ $tipo->prefijo }}"
                            {{ old('tipo_recurso_id', $equipo->tipo_recurso_id ?? '') == $tipo->id ? 'selected' : '' }}>
                            {{ $tipo->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('tipo_recurso_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-medium">Serial</label>
                @php
                    $isSinSerial = false;
                    $serialValue = old('serial', $equipo->serial ?? '');
                    if (str_starts_with((string)$serialValue, 'SIN_SERIAL_') || old('sin_serial_fisico')) {
                        $isSinSerial = true;
                        $serialValue = ''; // Limpiar visualmente si es autogenerado
                    }
                @endphp
                <input type="text" name="serial" id="serialInput"
                       class="form-control @error('serial') is-invalid @enderror"
                       value="{{ $serialValue }}"
                       maxlength="100">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="sin_serial_fisico" id="sinSerialCheck" value="1" {{ $isSinSerial ? 'checked' : '' }}>
                    <label class="form-check-label text-muted small" for="sinSerialCheck">
                        Este equipo no posee serial físico
                    </label>
                </div>
                @error('serial')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-4">
                <x-ui.input name="activo_fijo" value="{{ old('activo_fijo', $equipo->activo_fijo ?? '') }}" label="Activo Fijo" required="false" maxlength="100" containerClass="" />
            </div>

            <div class="col-12 col-md-4">
                <x-ui.input name="placa" value="{{ old('placa', $equipo->placa ?? '') }}" label="Placa" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
            </div>

            <div class="col-12 col-md-4">
                <x-ui.input name="marca" value="{{ old('marca', $equipo->marca ?? '') }}" label="Marca" required="true" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
            </div>

            <div class="col-12 col-md-4">
                <x-ui.input name="modelo" value="{{ old('modelo', $equipo->modelo ?? '') }}" label="Modelo" required="true" maxlength="100" containerClass="" />
            </div>

            <div class="col-12 col-md-4">
                <x-ui.input name="nombre_equipo" value="{{ old('nombre_equipo', $equipo->nombre_equipo ?? '') }}" label="Nombre del Equipo" required="true" maxlength="150" containerClass="" />
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-medium">Estado Operativo <span class="text-danger">*</span></label>
                <select name="estado_operativo"
                        class="form-select @error('estado_operativo') is-invalid @enderror"
                        required id="estadoSelect">
                    <option value="activo" {{ old('estado_operativo', $equipo->estado_operativo ?? 'activo') === 'activo' ? 'selected' : '' }}>Asignado</option>
                    <option value="asignado" {{ old('estado_operativo', $equipo->estado_operativo ?? '') === 'asignado' ? 'selected' : '' }}>Asignado</option>
                    <option value="disponible" {{ old('estado_operativo', $equipo->estado_operativo ?? '') === 'disponible' ? 'selected' : '' }}>Disponible</option>
                    <option value="mantenimiento" {{ old('estado_operativo', $equipo->estado_operativo ?? '') === 'mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
                    <option value="baja" {{ old('estado_operativo', $equipo->estado_operativo ?? '') === 'baja' ? 'selected' : '' }}>Baja</option>
                </select>
                @error('estado_operativo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-8 {{ in_array(old('estado_operativo', $equipo->estado_operativo ?? 'activo'), ['mantenimiento','baja']) ? '' : 'd-none' }}" id="razonEstadoWrap">
                <label class="form-label fw-medium">Razón del Estado</label>
                <textarea name="razon_estado"
                          class="form-control @error('razon_estado') is-invalid @enderror"
                          rows="2" maxlength="500">{{ old('razon_estado', $equipo->razon_estado ?? '') }}</textarea>
                @error('razon_estado')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="accordion mb-4" id="accordionForm">

{{-- === ESPECIFICACIONES TÉCNICAS === --}}
<div class="accordion-item border shadow-sm mb-3 rounded" style="overflow: hidden;">
    <h2 class="accordion-header" id="headingSpecs">
        <button class="accordion-button collapsed bg-info bg-opacity-10 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSpecs" aria-expanded="false" aria-controls="collapseSpecs">
            <i class="bi bi-cpu me-2 text-info"></i>Especificaciones Técnicas
            <span class="ms-2 fw-normal text-muted small" style="font-size: 0.85em;">- Agregar información del hardware y sistema operativo</span>
        </button>
    </h2>
    <div id="collapseSpecs" class="accordion-collapse collapse" aria-labelledby="headingSpecs">
        <div class="accordion-body">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <x-ui.input name="procesador" value="{{ old('procesador', $equipo->procesador ?? '') }}" label="Procesador" required="false" maxlength="150" containerClass="" />
                </div>
                <div class="col-12 col-md-4">
                    <x-ui.input name="ram" value="{{ old('ram', $equipo->ram ?? '') }}" label="RAM" required="false" maxlength="50" placeholder="Ej: 16 GB" containerClass="" />
                </div>
                <div class="col-12 col-md-4">
                    <x-ui.input name="disco" value="{{ old('disco', $equipo->disco ?? '') }}" label="Disco" required="false" maxlength="50" placeholder="Ej: 512 GB SSD" containerClass="" />
                </div>
                <div class="col-12 col-md-4">
                    <x-ui.input name="sistema_operativo" value="{{ old('sistema_operativo', $equipo->sistema_operativo ?? '') }}" label="Sistema Operativo" required="false" maxlength="100" containerClass="" />
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-medium">Fecha de Compra</label>
                    <input type="date" name="fecha_compra"
                           class="form-control @error('fecha_compra') is-invalid @enderror"
                           value="{{ old('fecha_compra', isset($equipo->fecha_compra) ? $equipo->fecha_compra->format('Y-m-d') : '') }}">
                    @error('fecha_compra') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-medium">Fin de Garantía</label>
                    <input type="date" name="fin_garantia"
                           class="form-control @error('fin_garantia') is-invalid @enderror"
                           value="{{ old('fin_garantia', isset($equipo->fin_garantia) ? $equipo->fin_garantia->format('Y-m-d') : '') }}">
                    @error('fin_garantia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-4">
                    <x-ui.input name="tiempo_uso" value="{{ old('tiempo_uso', $equipo->tiempo_uso ?? '') }}" label="Tiempo de Uso" required="false" maxlength="100" placeholder="Ej: 2 años" containerClass="" />
                </div>
            </div>
        </div>
    </div>
</div>

{{-- === USUARIO ASIGNADO === --}}
<div class="accordion-item border shadow-sm mb-3 rounded" style="overflow: hidden;">
    <h2 class="accordion-header" id="headingUser">
        <button class="accordion-button collapsed bg-success bg-opacity-10 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUser" aria-expanded="false" aria-controls="collapseUser">
            <i class="bi bi-person me-2 text-success"></i>Usuario Asignado
            <span class="ms-2 fw-normal text-muted small" style="font-size: 0.85em;">- Registrar el funcionario responsable del activo</span>
        </button>
    </h2>
    <div id="collapseUser" class="accordion-collapse collapse" aria-labelledby="headingUser">
        <div class="accordion-body">
            @if (!$equipo->exists)
                <div class="alert alert-light border mb-3">
                    <strong>Registro inicial sin funcionario asignado.</strong>
                    El funcionario se registra posteriormente desde el modulo de préstamos.
                </div>
            @endif
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <x-ui.input name="usuario_empresa_propietaria" value="{{ old('usuario_empresa_propietaria', $equipo->usuarioAsignado->empresa_propietaria ?? '') }}" label="Empresa Propietaria del Equipo" required="false" maxlength="150" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-4">
                    <x-ui.input name="usuario_dependencia" value="{{ old('usuario_dependencia', $equipo->usuarioAsignado->dependencia ?? '') }}" label="Dependencia" required="false" maxlength="150" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-4">
                    <x-ui.input name="usuario_fuente_recurso" value="{{ old('usuario_fuente_recurso', $equipo->usuarioAsignado->fuente_recurso ?? '') }}" label="Fuente de Recurso" required="false" maxlength="150" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-4">
                    <x-ui.input name="usuario_empresa_funcionario" value="{{ old('usuario_empresa_funcionario', $equipo->usuarioAsignado->empresa_funcionario ?? '') }}" label="Empresa Funcionario" required="false" maxlength="150" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-4">
                    <x-ui.input name="usuario_tipo_vinculacion" value="{{ old('usuario_tipo_vinculacion', $equipo->usuarioAsignado->tipo_vinculacion ?? '') }}" label="Empleado o Contratista" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-4">
                    <x-ui.input name="usuario_shortname" value="{{ old('usuario_shortname', $equipo->usuarioAsignado->shortname ?? '') }}" label="Shortname" required="false" maxlength="100" containerClass="" />
                </div>
                <div class="col-12 col-md-6">
                      <x-ui.input name="usuario_nombre" value="{{ old('usuario_nombre', $equipo->usuarioAsignado->nombre ?? '') }}" label="Nombre" required="false" maxlength="150" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-3">
                      <x-ui.input name="usuario_cedula" value="{{ old('usuario_cedula', $equipo->usuarioAsignado->cedula ?? '') }}" label="Cédula" required="false" maxlength="20" containerClass="" oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                </div>
                <div class="col-12 col-md-3">
                    <x-ui.input name="usuario_ciudad" value="{{ old('usuario_ciudad', $equipo->usuarioAsignado->ciudad ?? '') }}" label="Ciudad" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-3">
                    <x-ui.input name="usuario_departamento" value="{{ old('usuario_departamento', $equipo->usuarioAsignado->departamento ?? '') }}" label="Departamento" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-3">
                    <x-ui.input name="usuario_cargo" value="{{ old('usuario_cargo', $equipo->usuarioAsignado->cargo ?? '') }}" label="Cargo" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-3">
                    <x-ui.input name="usuario_area" value="{{ old('usuario_area', $equipo->usuarioAsignado->area ?? '') }}" label="Área" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-3">
                    <x-ui.input name="usuario_piso" value="{{ old('usuario_piso', $equipo->usuarioAsignado->piso ?? '') }}" label="Piso" required="false" maxlength="20" containerClass="" oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                </div>
                <div class="col-12 col-md-4">
                    <x-ui.input name="usuario_distrito" value="{{ old('usuario_distrito', $equipo->usuarioAsignado->distrito ?? '') }}" label="Distrito" required="false" maxlength="150" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-4">
                    <x-ui.input name="usuario_seccional" value="{{ old('usuario_seccional', $equipo->usuarioAsignado->seccional ?? '') }}" label="Seccional" required="false" maxlength="150" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
            </div>
        </div>
    </div>
</div>

{{-- === RESPONSABLE DEL ACTIVO === --}}
<div class="accordion-item border shadow-sm mb-3 rounded" style="overflow: hidden;">
    <h2 class="accordion-header" id="headingResp">
        <button class="accordion-button collapsed bg-warning bg-opacity-10 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseResp" aria-expanded="false" aria-controls="collapseResp">
            <i class="bi bi-person-badge me-2 text-warning"></i>Responsable del Activo
            <span class="ms-2 fw-normal text-muted small" style="font-size: 0.85em;">- Asignar el empleado a cargo (inventario)</span>
        </button>
    </h2>
    <div id="collapseResp" class="accordion-collapse collapse" aria-labelledby="headingResp">
        <div class="accordion-body">
            @if (!$equipo->exists)
                {{-- CREATE: Mostrar tarjeta informativa --}}
                <div class="alert alert-info border-0 rounded d-flex align-items-center">
                    <i class="bi bi-info-circle me-3" style="font-size: 1.25rem;"></i>
                    <div>
                        <strong>Responsable del Activo (Asignación Automática):</strong><br>
                        El responsable inicial se asigna automáticamente al Analista TIC institucional (rol Soporte TI). 
                        Esta responsabilidad puede ser modificada posteriormente por usuarios con permisos de edición, 
                        según los cambios administrativos en el control del activo.
                    </div>
                </div>
            @else
                {{-- EDIT: Mostrar campos editables --}}
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <x-ui.input name="responsable_cedula" value="{{ old('responsable_cedula', $equipo->responsable_cedula ?? '') }}" label="Cédula" required="false" maxlength="20" containerClass="" oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                    </div>
                    <div class="col-12 col-md-5">
                        <x-ui.input name="responsable_nombre" value="{{ old('responsable_nombre', $equipo->responsable_nombre ?? '') }}" label="Nombre" required="false" maxlength="150" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                    </div>
                    <div class="col-12 col-md-4">
                        <x-ui.input name="responsable_cargo" value="{{ old('responsable_cargo', $equipo->responsable_cargo ?? '') }}" label="Cargo" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                    </div>
                    <div class="col-12 col-md-3">
                        <x-ui.input name="responsable_ciudad" value="{{ old('responsable_ciudad', $equipo->responsable_ciudad ?? '') }}" label="Ciudad" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                    </div>
                    <div class="col-12 col-md-3">
                        <x-ui.input name="responsable_area" value="{{ old('responsable_area', $equipo->responsable_area ?? '') }}" label="Área" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                    </div>
                    <div class="col-12 col-md-3">
                        <x-ui.input name="responsable_tipo_recurso" value="{{ old('responsable_tipo_recurso', $equipo->responsable_tipo_recurso ?? '') }}" label="Tipo de Recurso" required="false" maxlength="100" containerClass="" />
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-medium">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio_responsable"
                               class="form-control @error('fecha_inicio_responsable') is-invalid @enderror"
                               value="{{ old('fecha_inicio_responsable', isset($equipo->fecha_inicio_responsable) ? $equipo->fecha_inicio_responsable->format('Y-m-d') : '') }}">
                        @error('fecha_inicio_responsable') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-medium">Fecha Fin</label>
                        <input type="date" name="fecha_fin_responsable"
                               class="form-control @error('fecha_fin_responsable') is-invalid @enderror"
                               value="{{ old('fecha_fin_responsable', isset($equipo->fecha_fin_responsable) ? $equipo->fecha_fin_responsable->format('Y-m-d') : '') }}">
                        @error('fecha_fin_responsable') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- === PERIFÉRICOS === --}}
<div class="accordion-item border shadow-sm mb-3 rounded" style="overflow: hidden;">
    <h2 class="accordion-header" id="headingPeri">
        <button class="accordion-button collapsed bg-secondary bg-opacity-10 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePeri" aria-expanded="false" aria-controls="collapsePeri">
            <i class="bi bi-usb-plug me-2 text-secondary"></i>Periféricos
            <span class="ms-2 fw-normal text-muted small" style="font-size: 0.85em;">- Registrar mouse, teclado, monitor, cámara y accesorios</span>
        </button>
    </h2>
    <div id="collapsePeri" class="accordion-collapse collapse" aria-labelledby="headingPeri">
        <div class="accordion-body">
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <x-ui.input name="periferico_telefono" value="{{ old('periferico_telefono', $equipo->periferico->telefono ?? '') }}" label="Teléfono Fijo" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-3">
                    <x-ui.input name="periferico_teclado" value="{{ old('periferico_teclado', $equipo->periferico->teclado ?? '') }}" label="Teclado" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-3">
                    <x-ui.input name="periferico_mouse" value="{{ old('periferico_mouse', $equipo->periferico->mouse ?? '') }}" label="Mouse" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
                <div class="col-12 col-md-3">
                    <x-ui.input name="periferico_camara" value="{{ old('periferico_camara', $equipo->periferico->camara ?? '') }}" label="Cámara" required="false" maxlength="100" containerClass="" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" />
                </div>
            </div>
        </div>
    </div>
</div>

{{-- === COMPLEMENTOS DEL ACTIVO === --}}
@if(!isset($equipo))
<div class="accordion-item border shadow-sm mb-3 rounded" id="accordionItemComplementos" style="display: none; overflow: hidden;">
    <h2 class="accordion-header" id="headingComplementos">
        <button class="accordion-button collapsed bg-info bg-opacity-10 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseComplementos" aria-expanded="false" aria-controls="collapseComplementos">
            <i class="bi bi-box-seam me-2 text-info"></i>Complementos del Activo
            <span class="ms-2 fw-normal text-muted small" style="font-size: 0.85em;">- Elementos que vienen siempre con este activo</span>
        </button>
    </h2>
    <div id="collapseComplementos" class="accordion-collapse collapse" aria-labelledby="headingComplementos">
        <div class="accordion-body">
            <div id="complementosContainer">
                <div class="text-center text-muted small"><i class="bi bi-arrow-clockwise spin"></i> Cargando complementos configurados...</div>
            </div>
            
            <template id="complementoTemplate">
                <div class="card mb-2 border-info shadow-sm complemento-card">
                    <div class="card-header bg-info bg-opacity-10 py-2 d-flex justify-content-between align-items-center">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input check-incluir-complemento" type="checkbox" role="switch">
                            <label class="form-check-label fw-bold text-dark comp-nombre-label"></label>
                            <input type="hidden" class="comp-id-input">
                        </div>
                        <span class="badge bg-secondary comp-req-badge" style="display:none;">Obligatorio</span>
                    </div>
                    <div class="card-body bg-light collapse comp-details-collapse">
                        <div class="row g-2">
                            <div class="col-12 col-md-3 col-estado">
                                <label class="form-label small fw-medium">Estado <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm comp-estado-input">
                                    @foreach(\App\Models\ActivoComplemento::ESTADOS as $estado)
                                        <option value="{{ $estado }}">{{ $estado }}</option>
                                    @endforeach
                                </select>
                            </div>
                                <input type="hidden" class="comp-cantidad-input" value="1">
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-medium">Marca</label>
                                <input type="text" class="form-control form-control-sm comp-marca-input" maxlength="100">
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label small fw-medium">Modelo</label>
                                <input type="text" class="form-control form-control-sm comp-modelo-input" maxlength="100">
                            </div>
                            <div class="col-12 col-md-2 col-serial">
                                <label class="form-label small fw-medium">Serial <span class="text-danger req-serial-star" style="display:none;">*</span></label>
                                <input type="text" class="form-control form-control-sm comp-serial-input" maxlength="100">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-medium">Observaciones</label>
                                <input type="text" class="form-control form-control-sm comp-obs-input" maxlength="500">
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endif

{{-- === CAMPOS PERSONALIZADOS (DINÁMICOS) === --}}
@if(isset($camposPersonalizados) && $camposPersonalizados->count() > 0)
<div class="accordion-item border shadow-sm mb-3 rounded" style="overflow: hidden;">
    <h2 class="accordion-header" id="headingCustom">
        <button class="accordion-button collapsed bg-dark bg-opacity-10 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCustom" aria-expanded="false" aria-controls="collapseCustom">
            <i class="bi bi-ui-checks-grid me-2 text-dark"></i>Información Adicional (Personalizada)
            <span class="ms-2 fw-normal text-muted small" style="font-size: 0.85em;">- Campos adicionales dinámicos para este tipo de recurso</span>
        </button>
    </h2>
    <div id="collapseCustom" class="accordion-collapse collapse" aria-labelledby="headingCustom">
        <div class="accordion-body">
            <div class="row g-3">
                @foreach($camposPersonalizados as $campo)
                    @php
                        $valorActual = null;
                        if (isset($equipo)) {
                            $valorGuardado = $equipo->camposPersonalizadosValores->where('campo_personalizado_id', $campo->id)->first();
                            $valorActual = $valorGuardado ? $valorGuardado->valor : null;
                        }
                        $valorOld = old("campos_personalizados.{$campo->id}", $valorActual);
                    @endphp
                    
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-medium">{{ $campo->nombre }} {!! $campo->obligatorio ? '<span class="text-danger">*</span>' : '' !!}</label>
                        
                        @if($campo->tipo === 'texto')
                            <input type="text" name="campos_personalizados[{{ $campo->id }}]" class="form-control" value="{{ $valorOld }}" {{ $campo->obligatorio ? 'required' : '' }}>
                        
                        @elseif($campo->tipo === 'textarea')
                            <textarea name="campos_personalizados[{{ $campo->id }}]" class="form-control" rows="2" {{ $campo->obligatorio ? 'required' : '' }}>{{ $valorOld }}</textarea>
                        
                        @elseif($campo->tipo === 'numero')
                            <input type="number" step="any" name="campos_personalizados[{{ $campo->id }}]" class="form-control" value="{{ $valorOld }}" {{ $campo->obligatorio ? 'required' : '' }}>
                        
                        @elseif($campo->tipo === 'fecha')
                            <input type="date" name="campos_personalizados[{{ $campo->id }}]" class="form-control" value="{{ $valorOld }}" {{ $campo->obligatorio ? 'required' : '' }}>
                        
                        @elseif($campo->tipo === 'boolean')
                            <select name="campos_personalizados[{{ $campo->id }}]" class="form-select" {{ $campo->obligatorio ? 'required' : '' }}>
                                <option value="">Seleccione...</option>
                                <option value="1" {{ $valorOld == '1' ? 'selected' : '' }}>Sí</option>
                                <option value="0" {{ $valorOld == '0' ? 'selected' : '' }}>No</option>
                            </select>
                            
                        @elseif($campo->tipo === 'select')
                            <select name="campos_personalizados[{{ $campo->id }}]" class="form-select" {{ $campo->obligatorio ? 'required' : '' }}>
                                <option value="">Seleccione...</option>
                                @foreach($campo->opciones as $opcion)
                                    <option value="{{ $opcion->valor }}" {{ $valorOld == $opcion->valor ? 'selected' : '' }}>{{ $opcion->valor }}</option>
                                @endforeach
                            </select>
                            
                        @elseif($campo->tipo === 'multiselect')
                            @php
                                // Intentar decodificar si es JSON
                                $valoresSeleccionados = is_string($valorOld) ? json_decode($valorOld, true) : $valorOld;
                                if(!is_array($valoresSeleccionados)) $valoresSeleccionados = [];
                            @endphp
                            <select name="campos_personalizados[{{ $campo->id }}][]" class="form-select" multiple {{ $campo->obligatorio ? 'required' : '' }} size="3">
                                @foreach($campo->opciones as $opcion)
                                    <option value="{{ $opcion->valor }}" {{ in_array($opcion->valor, $valoresSeleccionados) ? 'selected' : '' }}>{{ $opcion->valor }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Mantén presionado Ctrl (Windows) o Cmd (Mac) para seleccionar varios.</small>
                        @else
                            <input type="text" name="campos_personalizados[{{ $campo->id }}]" class="form-control" value="{{ $valorOld }}" {{ $campo->obligatorio ? 'required' : '' }}>
                        @endif
                        
                        @if($campo->descripcion)
                            <small class="text-muted fst-italic">{{ $campo->descripcion }}</small>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

</div> {{-- Fin accordion --}}

@push('scripts')
<script>
    // Mostrar/ocultar razón de estado
    document.getElementById('estadoSelect').addEventListener('change', function () {
        const wrap = document.getElementById('razonEstadoWrap');
        wrap.style.display = ['mantenimiento', 'baja'].includes(this.value) ? 'block' : 'none';
    });

    // Lógica para checkbox "sin serial"
    document.addEventListener('DOMContentLoaded', function() {
        const serialInput = document.getElementById('serialInput');
        const sinSerialCheck = document.getElementById('sinSerialCheck');
        
        function toggleSerialInput() {
            if (sinSerialCheck.checked) {
                serialInput.value = '';
                serialInput.disabled = true;
            } else {
                serialInput.disabled = false;
            }
        }
        
        if(sinSerialCheck && serialInput) {
            sinSerialCheck.addEventListener('change', toggleSerialInput);
            toggleSerialInput(); // init
        }

        // Lógica Dinámica de Formularios (Basada en la Matriz Centralizada)
        const tipoRecursoSelect = document.querySelector('select[name="tipo_recurso_id"]');
        
        @php
            $configCampos = [];
            foreach($tipoRecursos as $tr) {
                $configCampos[$tr->prefijo] = \App\Services\ConfiguracionActivosService::getCamposVisibles($tr->prefijo);
            }
        @endphp
        const configCampos = @json($configCampos);
        const fallbackCampos = @json(\App\Services\ConfiguracionActivosService::getCamposVisibles('*'));
        
        const dynamicFields = [
            'serial', 'activo_fijo', 'placa', 'marca', 'modelo', 'nombre_equipo',
            'procesador', 'ram', 'disco', 'sistema_operativo',
            'fecha_compra', 'fin_garantia', 'tiempo_uso',
            'periferico_telefono', 'periferico_teclado', 'periferico_mouse', 'periferico_camara'
        ];

        function actualizarCamposDinamicos() {
            if(!tipoRecursoSelect) return;
            const option = tipoRecursoSelect.options[tipoRecursoSelect.selectedIndex];
            if (!option || !option.value) {
                // Si no hay nada seleccionado, mostrar todo por defecto o esconder?
                // Mostraremos todo para que no se vea vacío o roto
                aplicarVisibilidad(fallbackCampos);
                return;
            }
            const prefijo = option.dataset.prefijo;
            const visibles = configCampos[prefijo] || fallbackCampos;
            aplicarVisibilidad(visibles);
        }

        function aplicarVisibilidad(visibles) {
            dynamicFields.forEach(fieldName => {
                const el = document.querySelector(`[name="${fieldName}"]`);
                if (el) {
                    const wrapper = el.closest('.col-12');
                    if (wrapper) {
                        if (visibles.includes(fieldName)) {
                            wrapper.classList.remove('d-none');
                            if (fieldName === 'nombre_equipo') el.required = true;
                        } else {
                            wrapper.classList.add('d-none');
                            if (el.tagName === 'INPUT' && el.type !== 'checkbox' && el.type !== 'radio') el.value = '';
                            else if (el.tagName === 'SELECT') el.value = '';
                            
                            if (fieldName === 'nombre_equipo') el.required = false;
                        }
                    }
                }
            });

            // Ocultar acordeones enteros si todos sus campos están ocultos
            const checkAccordion = (collapseId) => {
                const wrapper = document.getElementById(collapseId);
                if (wrapper) {
                    const hasVisible = Array.from(wrapper.querySelectorAll('.col-12')).some(c => !c.classList.contains('d-none'));
                    const accordionItem = wrapper.closest('.accordion-item');
                    if(accordionItem) accordionItem.style.display = hasVisible ? 'block' : 'none';
                }
            };

            checkAccordion('collapseSpecs');
            checkAccordion('collapsePeri');
        }

        if (tipoRecursoSelect) {
            tipoRecursoSelect.addEventListener('change', async function() {
                actualizarCamposDinamicos();
                await cargarComplementosTipoRecurso(this.value);
            });
            actualizarCamposDinamicos(); // Run on init for edit views
            if (tipoRecursoSelect.value) {
                cargarComplementosTipoRecurso(tipoRecursoSelect.value);
            }
        }

        // Lógica de Complementos (Solo en creación)
        @if(!isset($equipo))
        const complementosContainer = document.getElementById('complementosContainer');
        const accordionItemComplementos = document.getElementById('accordionItemComplementos');
        const template = document.getElementById('complementoTemplate');

        async function cargarComplementosTipoRecurso(tipoId) {
            if (!tipoId || !complementosContainer || !accordionItemComplementos) {
                if(accordionItemComplementos) accordionItemComplementos.style.display = 'none';
                return;
            }

            try {
                const response = await fetch(`/tipo-recursos/${tipoId}/complementos-definidos`);
                if (!response.ok) throw new Error('Network error');
                const complementosDefinidos = await response.json();

                complementosContainer.innerHTML = ''; // Limpiar

                if (complementosDefinidos.length === 0) {
                    accordionItemComplementos.style.display = 'none';
                    return;
                }

                accordionItemComplementos.style.display = 'block';

                complementosDefinidos.forEach((comp, index) => {
                    const clone = template.content.cloneNode(true);
                    
                    const check = clone.querySelector('.check-incluir-complemento');
                    const detailsCollapse = clone.querySelector('.comp-details-collapse');
                    const inputId = clone.querySelector('.comp-id-input');
                    const labelNombre = clone.querySelector('.comp-nombre-label');
                    const badgeReq = clone.querySelector('.comp-req-badge');
                    const inputEstado = clone.querySelector('.comp-estado-input');
                    const inputCantidad = clone.querySelector('.comp-cantidad-input');
                    const inputMarca = clone.querySelector('.comp-marca-input');
                    const inputModelo = clone.querySelector('.comp-modelo-input');
                    const inputSerial = clone.querySelector('.comp-serial-input');
                    const inputObs = clone.querySelector('.comp-obs-input');

                    // Set names with index
                    const prefix = `complementos[${index}]`;
                    inputId.name = `${prefix}[catalogo_complemento_id]`;
                    inputEstado.name = `${prefix}[estado]`;
                    inputCantidad.name = `${prefix}[cantidad]`;
                    inputMarca.name = `${prefix}[marca]`;
                    inputModelo.name = `${prefix}[modelo]`;
                    inputSerial.name = `${prefix}[serial]`;
                    inputObs.name = `${prefix}[observaciones]`;

                    // Config
                    inputId.value = comp.id;
                    labelNombre.textContent = comp.nombre;
                    inputCantidad.value = comp.cantidad_default || 1;

                    if (!comp.usa_estado) clone.querySelector('.col-estado').style.display = 'none';
                    if (comp.requiere_serial) {
                        clone.querySelector('.req-serial-star').style.display = 'inline';
                        // if included, make required later
                    }

                    // Toggle logic
                    check.addEventListener('change', function() {
                        if (this.checked) {
                            detailsCollapse.classList.add('show');
                            inputId.disabled = false;
                            inputEstado.disabled = false;
                            if(comp.requiere_serial) inputSerial.required = true;
                        } else {
                            detailsCollapse.classList.remove('show');
                            // Clear inputs
                            inputMarca.value = '';
                            inputModelo.value = '';
                            inputSerial.value = '';
                            inputObs.value = '';
                            inputCantidad.value = comp.cantidad_default || 1;
                            
                            // To prevent submitting empty unused fields
                            if(comp.requiere_serial) inputSerial.required = false;
                        }
                    });

                    // Init disabled state if not checked
                    if (!check.checked) {
                        if(comp.requiere_serial) inputSerial.required = false;
                    }

                    complementosContainer.appendChild(clone);
                });

            } catch (error) {
                console.error("Error loading complementos", error);
                complementosContainer.innerHTML = '<div class="alert alert-danger p-2">Error al cargar complementos.</div>';
            }
        }
        @else
        async function cargarComplementosTipoRecurso(tipoId) {
            // En modo edición no se cargan aquí, se administran desde la vista show
            return;
        }
        @endif

        // Auto-cambiar estado operativo según si hay usuario o no
        const inputNombre = document.querySelector('input[name="usuario_nombre"]');
        const inputCedula = document.querySelector('input[name="usuario_cedula"]');
        const estadoSelect = document.getElementById('estadoSelect');
        
        function actualizarEstadoAutomatico() {
            if (!estadoSelect || !inputNombre || !inputCedula) return;
            
            // Si el estado actual es mantenimiento o baja, no forzamos el cambio
            if (['mantenimiento', 'baja'].includes(estadoSelect.value)) return;

            const tieneUsuario = inputNombre.value.trim().length > 0 || inputCedula.value.trim().length > 0;
            
            if (tieneUsuario) {
                estadoSelect.value = 'asignado';
            } else {
                estadoSelect.value = 'disponible';
            }
            // Disparar evento change para que se oculte/muestre la razón
            estadoSelect.dispatchEvent(new Event('change'));
        }

        if (inputNombre && inputCedula) {
            inputNombre.addEventListener('input', actualizarEstadoAutomatico);
            inputCedula.addEventListener('input', actualizarEstadoAutomatico);
            
            // Ejecutar al cargar la página si estamos creando (o si está disponible/asignado)
            if (['activo', 'asignado', 'disponible'].includes(estadoSelect.value)) {
                actualizarEstadoAutomatico();
            }
        }

    });
</script>
@endpush
