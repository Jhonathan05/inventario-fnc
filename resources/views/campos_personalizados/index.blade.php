@extends('layouts.inventario')

@section('title', 'Campos Personalizados')

@push('styles')
<style>
    .sortable-ghost {
        opacity: 0.4;
        background-color: #f8f9fa;
    }
    .handle {
        cursor: grab;
    }
    .handle:active {
        cursor: grabbing;
    }
    .opciones-wrapper {
        display: none;
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-ui-checks-grid me-2 text-primary"></i>Campos Personalizados</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCampo" onclick="openCreateModal()">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Campo
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="camposTable">
                <thead class="table-light text-nowrap">
                    <tr>
                        <th width="50"></th>
                        <th>Módulo</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Opciones de Config.</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="sortable-list">
                    @forelse($campos as $campo)
                        <tr data-id="{{ $campo->id }}">
                            <td class="text-center text-muted handle"><i class="bi bi-grip-vertical fs-5"></i></td>
                            <td><span class="badge bg-secondary">{{ strtoupper($campo->modulo) }}</span></td>
                            <td>
                                <span class="fw-bold d-block">{{ $campo->nombre }}</span>
                                <small class="text-muted">{{ Str::limit($campo->descripcion, 50) }}</small>
                            </td>
                            <td><span class="badge bg-info text-dark">{{ ucfirst($campo->tipo) }}</span></td>
                            <td>
                                @if($campo->obligatorio) <span class="badge bg-danger ms-1" title="Obligatorio">OBL</span> @endif
                                @if($campo->visible) <span class="badge bg-success ms-1" title="Visible">VIS</span> @endif
                                @if($campo->importable) <span class="badge bg-primary ms-1" title="Importable">IMP</span> @endif
                                @if($campo->exportable) <span class="badge bg-warning text-dark ms-1" title="Exportable">EXP</span> @endif
                            </td>
                            <td>
                                @if($campo->activo)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="openEditModal({{ $campo->toJson() }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('campos-personalizados.destroy', $campo->id) }}" method="POST" style="display:inline-block;" class="form-eliminar">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-ui-radios fs-2 d-block mb-2"></i>No hay campos personalizados registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Create/Edit -->
<div class="modal fade" id="modalCampo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-md-down">
        <form id="formCampo" method="POST" action="{{ route('campos-personalizados.store') }}">
            @csrf
            <input type="hidden" name="_method" id="methodCampo" value="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Campo Personalizado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Módulo *</label>
                            <select name="modulo" id="modulo" class="form-select" required>
                                <option value="equipos" {{ old('modulo') == 'equipos' ? 'selected' : '' }}>Activos</option>
                                <option value="licencias" {{ old('modulo') == 'licencias' ? 'selected' : '' }}>Licencias</option>
                                <option value="suscripciones" {{ old('modulo') == 'suscripciones' ? 'selected' : '' }}>Suscripciones</option>
                                <option value="vitalicias" {{ old('modulo') == 'vitalicias' ? 'selected' : '' }}>Vitalicias</option>
                                <option value="actas" {{ old('modulo') == 'actas' ? 'selected' : '' }}>Actas</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Tipo de Campo *</label>
                            <select name="tipo" id="tipo" class="form-select" required onchange="toggleOpciones()">
                                <option value="texto" {{ old('tipo') == 'texto' ? 'selected' : '' }}>Texto corto</option>
                                <option value="textarea" {{ old('tipo') == 'textarea' ? 'selected' : '' }}>Texto largo (Textarea)</option>
                                <option value="numero" {{ old('tipo') == 'numero' ? 'selected' : '' }}>Número</option>
                                <option value="fecha" {{ old('tipo') == 'fecha' ? 'selected' : '' }}>Fecha</option>
                                <option value="correo" {{ old('tipo') == 'correo' ? 'selected' : '' }}>Correo Electrónico</option>
                                <option value="telefono" {{ old('tipo') == 'telefono' ? 'selected' : '' }}>Teléfono</option>
                                <option value="boolean" {{ old('tipo') == 'boolean' ? 'selected' : '' }}>Sí / No (Boolean)</option>
                                <option value="select" {{ old('tipo') == 'select' ? 'selected' : '' }}>Selección Única (Select)</option>
                                <option value="multiselect" {{ old('tipo') == 'multiselect' ? 'selected' : '' }}>Selección Múltiple (Multiselect)</option>
                                <option value="url" {{ old('tipo') == 'url' ? 'selected' : '' }}>URL</option>
                            </select>
                        </div>
                        
                        <div class="col-12 col-md-12">
                            <label class="form-label">Nombre del Campo *</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" required placeholder="Ej: Centro de Costos" value="{{ old('nombre') }}" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                        </div>
                        
                        <div class="col-12 col-md-12 opciones-wrapper">
                            <label class="form-label text-primary">Opciones de la Lista</label>
                            <textarea name="opciones" id="opciones" class="form-control" rows="3" placeholder="Opción A, Opción B, Opción C... (Separadas por comas)">{{ old('opciones') }}</textarea>
                            <small class="text-muted">Ingresa las opciones separadas por comas.</small>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <div class="card border-0 shadow-sm border-top border-primary border-3">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0 text-primary fw-bold">
                                        <i class="bi bi-gear-fill me-2"></i> Configuración Avanzada
                                    </h6>
                                </div>
                                <div class="card-body">
                                        <div class="row g-3">
                                            <!-- Switches Básicos -->
                                            <div class="col-12 col-md-4">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="obligatorio" id="obligatorio" value="1" {{ old('obligatorio') ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold text-danger" for="obligatorio"><i class="bi bi-asterisk me-1"></i> Obligatorio</label>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="editable" id="editable" value="1" {{ old('editable', '1') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="editable"><i class="bi bi-pencil-square me-1"></i> Editable</label>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="activo" id="activo" value="1" {{ old('activo', '1') ? 'checked' : '' }}>
                                                    <label class="form-check-label text-success fw-bold" for="activo"><i class="bi bi-check-circle-fill me-1"></i> Activo</label>
                                                </div>
                                            </div>

                                            <!-- Configuración de Participación de Campo -->
                                            <div class="col-12"><hr></div>
                                            <h6 class="text-primary mb-3">Participación del Campo</h6>
                                            <div class="col-12 col-md-4">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="visible" id="visible" value="1" {{ old('visible', '1') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="visible"><i class="bi bi-eye me-1"></i> Mostrar en Formularios</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="mostrar_en_grilla" id="mostrar_en_grilla" value="1" {{ old('mostrar_en_grilla') ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold" for="mostrar_en_grilla"><i class="bi bi-hdd-network me-1"></i> Mostrar en CMDB Principal</label>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-4">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="participa_exportacion_completa" id="participa_exportacion_completa" value="1" {{ old('participa_exportacion_completa', '1') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="participa_exportacion_completa"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Participar en Exportación Completa</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="participa_exportacion_cmdb" id="participa_exportacion_cmdb" value="1" onchange="toggleExcel()" {{ old('participa_exportacion_cmdb', '1') ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold" for="participa_exportacion_cmdb"><i class="bi bi-file-earmark-excel me-1"></i> Participar en Exportación CMDB</label>
                                                </div>
                                                <div id="excel_opciones" style="display: none;" class="ms-4 mb-3">
                                                    <label class="form-label text-muted small mb-1">Exportar después de:</label>
                                                    <select name="exportar_excel_despues_de" id="exportar_excel_despues_de" class="form-select form-select-sm">
                                                        <option value="">(Al final por defecto)</option>
                                                        @if(isset($columnasCMDB))
                                                            @foreach($columnasCMDB as $key => $label)
                                                                <option value="{{ $key }}">{{ $label }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-4">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="participa_reportes" id="participa_reportes" value="1" {{ old('participa_reportes', '1') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="participa_reportes"><i class="bi bi-bar-chart-line me-1"></i> Participar en Reportes</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="importable" id="importable" value="1" {{ old('importable', '1') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="importable"><i class="bi bi-cloud-upload me-1"></i> Participar en Importaciones</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="participa_filtros" id="participa_filtros" value="1" {{ old('participa_filtros') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="participa_filtros"><i class="bi bi-funnel me-1"></i> Participar en Filtros del Sistema</label>
                                                </div>
                                            </div>

                                            <div class="col-12" id="divisor_asignacion" style="display: none;"><hr></div>

                                            <!-- Asignación Masiva (Solo Select) -->
                                            <div class="col-12" id="asignacion_masiva_wrapper" style="display: none;">
                                                <h6 class="text-danger mb-3">Valor inicial para equipos existentes</h6>
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="asignar_valor_inicial" id="asignar_valor_inicial" value="1" onchange="toggleAsignacion()">
                                                    <label class="form-check-label fw-bold text-danger" for="asignar_valor_inicial"><i class="bi bi-tags-fill me-1"></i> Asignar un valor masivamente a los equipos existentes</label>
                                                </div>
                                                <div id="asignacion_opciones" style="display: none;" class="p-3 bg-light rounded border border-danger">
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3 mb-md-0">
                                                            <label class="form-label fw-bold text-dark small mb-2">Selecciona el valor inicial:</label>
                                                            <select name="valor_inicial_masivo" id="valor_inicial_masivo" class="form-select">
                                                                <option value="">-- Escribe opciones arriba primero --</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold text-dark small mb-2">Modo de asignación:</label>
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="radio" name="modo_asignacion_masiva" id="modo_solo_vacios" value="solo_vacios" checked onchange="toggleWarning()">
                                                                <label class="form-check-label text-success" for="modo_solo_vacios">
                                                                    Solo asignar a equipos sin valor (Seguro)
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="modo_asignacion_masiva" id="modo_sobrescribir" value="sobrescribir_todos" onchange="toggleWarning()">
                                                                <label class="form-check-label text-danger fw-bold" for="modo_sobrescribir">
                                                                    Sobrescribir todos los equipos
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar Campo</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    function toggleOpciones() {
        const tipo = document.getElementById('tipo').value;
        const wrapper = document.querySelector('.opciones-wrapper');
        const modulo = document.getElementById('modulo').value;
        const divisorAsignacion = document.getElementById('divisor_asignacion');
        const asignacionWrapper = document.getElementById('asignacion_masiva_wrapper');
        
        if (tipo === 'select' || tipo === 'multiselect') {
            wrapper.style.display = 'block';
            if (modulo === 'equipos') {
                divisorAsignacion.style.display = 'block';
                asignacionWrapper.style.display = 'block';
            } else {
                divisorAsignacion.style.display = 'none';
                asignacionWrapper.style.display = 'none';
            }
        } else {
            wrapper.style.display = 'none';
            divisorAsignacion.style.display = 'none';
            asignacionWrapper.style.display = 'none';
            document.getElementById('asignar_valor_inicial').checked = false;
            if (document.getElementById('participa_exportacion_cmdb').checked) {
                document.getElementById('excel_opciones').style.display = 'block';
            } else {
                document.getElementById('excel_opciones').style.display = 'none';
                document.getElementById('exportar_excel_despues_de').value = '';
            }
        }
    }

    function toggleExcel() {
        const isChecked = document.getElementById('participa_exportacion_cmdb').checked;
        document.getElementById('excel_opciones').style.display = isChecked ? 'block' : 'none';
    }

    function toggleAsignacion() {
        const isChecked = document.getElementById('asignar_valor_inicial').checked;
        document.getElementById('asignacion_opciones').style.display = isChecked ? 'block' : 'none';
        updateValoresIniciales();
    }

    function toggleWarning() {
        if (document.getElementById('modo_sobrescribir').checked) {
            Swal.fire({
                title: '¡Advertencia de Sobrescritura!',
                text: "Estás a punto de sobrescribir y eliminar la información existente de este campo en todos los activos. ¿Estás seguro de querer continuar con esta opción destructiva?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, entiendo el riesgo',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) {
                    document.getElementById('modo_solo_vacios').checked = true;
                }
            });
        }
    }

    function updateValoresIniciales() {
        const opcionesRaw = document.getElementById('opciones').value;
        const selectValor = document.getElementById('valor_inicial_masivo');
        
        selectValor.innerHTML = '';
        
        if (!opcionesRaw.trim()) {
            selectValor.innerHTML = '<option value="">-- Escribe opciones arriba primero --</option>';
            return;
        }

        // Soporta opciones separadas por coma o por salto de línea
        const opciones = opcionesRaw.split(/[,|\n]+/).map(o => o.trim()).filter(o => o);
        
        if (opciones.length === 0) {
            selectValor.innerHTML = '<option value="">-- Escribe opciones arriba primero --</option>';
            return;
        }

        selectValor.innerHTML = '<option value="">-- Selecciona el valor --</option>';
        opciones.forEach(opcion => {
            const opt = document.createElement('option');
            opt.value = opcion;
            opt.textContent = opcion;
            selectValor.appendChild(opt);
        });
    }

    document.getElementById('opciones').addEventListener('input', function() {
        if (document.getElementById('asignar_valor_inicial').checked) {
            updateValoresIniciales();
        }
    });

    function openCreateModal() {
        document.getElementById('formCampo').reset();
        document.getElementById('formCampo').action = "{{ route('campos-personalizados.store') }}";
        document.getElementById('methodCampo').value = "POST";
        document.getElementById('modalTitle').innerText = "Nuevo Campo Personalizado";
        toggleOpciones();
        toggleExcel();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCampo')).show();
    }

    function openEditModal(campo) {
        document.getElementById('formCampo').action = `/campos-personalizados/${campo.id}`;
        document.getElementById('methodCampo').value = "PUT";
        document.getElementById('modalTitle').innerText = "Editar Campo Personalizado";
        
        document.getElementById('modulo').value = campo.modulo;
        document.getElementById('tipo').value = campo.tipo;
        document.getElementById('nombre').value = campo.nombre;
        
        document.getElementById('obligatorio').checked = campo.obligatorio;
        document.getElementById('editable').checked = campo.editable;
        document.getElementById('visible').checked = campo.visible;
        document.getElementById('importable').checked = campo.importable;
        document.getElementById('activo').checked = campo.activo;
        
        document.getElementById('mostrar_en_grilla').checked = campo.mostrar_en_grilla;
        document.getElementById('participa_exportacion_cmdb').checked = campo.participa_exportacion_cmdb !== undefined ? campo.participa_exportacion_cmdb : campo.mostrar_en_grilla;
        document.getElementById('participa_exportacion_completa').checked = campo.participa_exportacion_completa !== undefined ? campo.participa_exportacion_completa : true;
        document.getElementById('participa_reportes').checked = campo.participa_reportes !== undefined ? campo.participa_reportes : true;
        document.getElementById('participa_filtros').checked = campo.participa_filtros !== undefined ? campo.participa_filtros : false;
        
        document.getElementById('exportar_excel_despues_de').value = campo.exportar_excel_despues_de ? campo.exportar_excel_despues_de.toLowerCase() : '';
        
        if(campo.opciones && campo.opciones.length > 0) {
            const opcionesStr = campo.opciones.map(o => o.valor).join(', ');
            document.getElementById('opciones').value = opcionesStr;
        } else {
            document.getElementById('opciones').value = '';
        }
        
        toggleOpciones();
        toggleExcel();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCampo')).show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar Sortable
        const el = document.getElementById('sortable-list');
        if (el) {
            new Sortable(el, {
                handle: '.handle',
                animation: 150,
                onEnd: function (evt) {
                    let order = [];
                    document.querySelectorAll('#sortable-list tr').forEach((row, index) => {
                        order.push({
                            id: row.getAttribute('data-id'),
                            orden: index
                        });
                    });
                    
                    fetch("{{ route('campos-personalizados.reorder') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ order: order })
                    }).then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            // Optional Toast for success
                        }
                    });
                }
            });
        }

        // SweetAlert para eliminar
        document.querySelectorAll('.form-eliminar').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Eliminar campo?',
                    text: "No se eliminarán los valores guardados en los equipos, pero el campo dejará de ser visible y gestionable.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        @if($errors->any())
            var errorModal = new bootstrap.Modal(document.getElementById('modalCampo'));
            errorModal.show();
            toggleOpciones();
            toggleGrilla();
            toggleExcel();
        @endif
    });
</script>
@endpush

