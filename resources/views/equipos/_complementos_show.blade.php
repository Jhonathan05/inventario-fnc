<div class="col-12 mt-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-info bg-opacity-10 fw-semibold border-0 py-3 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-box-seam me-2 text-info"></i>Complementos del Activo</span>
            @can('equipos.editar')
                <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalAgregarComplemento">
                    <i class="bi bi-plus-lg"></i> Agregar
                </button>
            @endcan
        </div>
        <div class="card-body p-0">
            @if($equipo->complementos->isEmpty())
                <p class="text-muted p-3 mb-0 text-center">Este equipo no tiene complementos asociados.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Cant.</th>
                                <th>Marca / Modelo</th>
                                <th>Serial</th>
                                @can('equipos.editar')
                                    <th class="text-end">Acciones</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($equipo->complementos as $comp)
                                <tr>
                                    <td class="fw-medium">{{ $comp->nombre }}</td>
                                    <td>
                                        @php
                                            $color = match($comp->estado) {
                                                'Bueno', 'Nuevo' => 'success',
                                                'Regular' => 'warning',
                                                'Malo' => 'orange',
                                                'Dañado' => 'danger',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ $comp->estado }}</span>
                                    </td>
                                    <td>{{ $comp->cantidad }}</td>
                                    <td>
                                        <div class="small">{{ $comp->marca ?: '—' }}</div>
                                        <div class="small text-muted">{{ $comp->modelo ?: '—' }}</div>
                                    </td>
                                    <td>{{ $comp->serial ?: '—' }}</td>
                                    @can('equipos.editar')
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2"
                                                title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditarComplemento{{ $comp->id }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info py-0 px-2"
                                                title="Transferir" data-bs-toggle="modal" data-bs-target="#modalTransferirComplemento{{ $comp->id }}">
                                                <i class="bi bi-arrow-left-right"></i>
                                            </button>
                                            @can('equipos.eliminar')
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" title="Eliminar" data-delete-url="{{ route('equipos.complementos.destroy', [$equipo, $comp]) }}" data-delete-name="el complemento {{ $comp->nombre }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @endcan
                                        </td>
                                    @endcan
                                </tr>

                                {{-- Modals for each complemento --}}
                                @can('equipos.editar')
                                <!-- Editar Modal -->
                                <div class="modal fade" id="modalEditarComplemento{{ $comp->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Editar Complemento: {{ $comp->nombre }}</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('equipos.complementos.update', [$equipo, $comp]) }}" method="POST">
                                                @csrf @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Estado</label>
                                                        <select name="estado" class="form-select" required>
                                                            @foreach(\App\Models\ActivoComplemento::ESTADOS as $estado)
                                                                <option value="{{ $estado }}" {{ $comp->estado == $estado ? 'selected' : '' }}>{{ $estado }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <input type="hidden" name="cantidad" value="1">
                                                    <div class="mb-3">
                                                        <label class="form-label">Marca</label>
                                                        <input type="text" name="marca" class="form-control" value="{{ $comp->marca }}" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Modelo</label>
                                                        <input type="text" name="modelo" class="form-control" value="{{ $comp->modelo }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Serial</label>
                                                        <input type="text" name="serial" class="form-control" value="{{ $comp->serial }}">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Transferir Modal -->
                                <div class="modal fade" id="modalTransferirComplemento{{ $comp->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title">Transferir: {{ $comp->nombre }}</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('equipos.complementos.transferir', [$equipo, $comp]) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="alert alert-warning small">
                                                        Se moverá el complemento <strong>{{ $comp->nombre }}</strong> (SN: {{ $comp->serial ?: 'N/A' }}) del activo actual a otro activo.
                                                        <br>El activo destino debe tener soporte para este complemento en su tipo de recurso.
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">ID del Activo Destino</label>
                                                        <input type="number" name="equipo_destino_id" class="form-control" required placeholder="Ej: 154">
                                                        <small class="text-muted">Escriba el ID interno del activo destino.</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Observaciones de Transferencia</label>
                                                        <textarea name="observaciones" class="form-control" rows="2"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-info text-white">Transferir</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endcan
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@can('equipos.editar')
<!-- Agregar Modal -->
<div class="modal fade" id="modalAgregarComplemento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Agregar Complemento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('equipos.complementos.store', $equipo) }}" method="POST">
                @csrf
                <div class="modal-body">
                    @php
                        $definidos = $equipo->tipoRecurso->complementosDefinidos;
                        $disponibles = \App\Models\ActivoComplemento::disponibles()
                            ->whereIn('catalogo_complemento_id', $definidos->pluck('id'))
                            ->with('catalogoComplemento')
                            ->get();
                    @endphp

                    <div class="mb-4 btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="modo_ingreso" id="modo_existente" value="existente" {{ old('modo_ingreso', 'existente') === 'existente' ? 'checked' : '' }} autocomplete="off">
                        <label class="btn btn-outline-info fw-semibold" for="modo_existente">Asignar Disponible</label>

                        <input type="radio" class="btn-check" name="modo_ingreso" id="modo_nuevo" value="nuevo" {{ old('modo_ingreso') === 'nuevo' ? 'checked' : '' }} autocomplete="off">
                        <label class="btn btn-outline-info fw-semibold" for="modo_nuevo">Registrar Nuevo</label>
                    </div>

                    <!-- Panel: Asignar Existente -->
                    <div id="panelExistente">
                        <div class="mb-3">
                            <label class="form-label">Complemento Disponible <span class="text-danger">*</span></label>
                            <select name="complemento_existente_id" class="form-select" id="selectExistente">
                                <option value="">Seleccione un complemento de la bolsa...</option>
                                @foreach($disponibles as $disp)
                                    <option value="{{ $disp->id }}">
                                        {{ $disp->nombre }} 
                                        {{ $disp->marca ? '- '.$disp->marca : '' }} 
                                        (SN: {{ $disp->serial ?: 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            @if($disponibles->isEmpty())
                                <small class="text-danger mt-1 d-block"><i class="bi bi-exclamation-triangle"></i> No hay complementos disponibles de este tipo en el inventario global.</small>
                            @endif
                        </div>
                    </div>

                    <!-- Panel: Registrar Nuevo -->
                    <div id="panelNuevo" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Complemento del Catálogo <span class="text-danger">*</span></label>
                            <select name="catalogo_complemento_id" class="form-select" id="selectNuevo">
                                <option value="">Seleccione...</option>
                                @foreach($definidos as $def)
                                    <option value="{{ $def->id }}">{{ $def->nombre }}</option>
                                @endforeach
                            </select>
                            @if($definidos->isEmpty())
                                <small class="text-danger mt-1 d-block">Este tipo de recurso no tiene complementos configurados.</small>
                            @endif
                        </div>
                        <input type="hidden" name="estado" value="Asignado">
                        <input type="hidden" name="cantidad" value="1">
                        <div class="mb-3">
                            <label class="form-label">Marca</label>
                            <input type="text" name="marca" class="form-control" oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Modelo</label>
                            <input type="text" name="modelo" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Serial</label>
                            <input type="text" name="serial" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white" id="btnSubmitAgregar" {{ $disponibles->isEmpty() ? 'disabled' : '' }}>Asignar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="modo_ingreso"]');
    const panelExistente = document.getElementById('panelExistente');
    const panelNuevo = document.getElementById('panelNuevo');
    const selectExistente = document.getElementById('selectExistente');
    const selectNuevo = document.getElementById('selectNuevo');
    const btnSubmit = document.getElementById('btnSubmitAgregar');
    
    const hasDisponibles = {{ $disponibles->isNotEmpty() ? 'true' : 'false' }};
    const hasDefinidos = {{ $definidos->isNotEmpty() ? 'true' : 'false' }};

    function updateView() {
        const isExistente = document.getElementById('modo_existente').checked;
        if (isExistente) {
            panelExistente.style.display = 'block';
            panelNuevo.style.display = 'none';
            selectExistente.required = true;
            selectNuevo.required = false;
            btnSubmit.disabled = !hasDisponibles;
            btnSubmit.textContent = 'Asignar';
        } else {
            panelExistente.style.display = 'none';
            panelNuevo.style.display = 'block';
            selectExistente.required = false;
            selectNuevo.required = true;
            btnSubmit.disabled = !hasDefinidos;
            btnSubmit.textContent = 'Registrar';
        }
    }

    radios.forEach(r => r.addEventListener('change', updateView));
    
    // Si no hay disponibles, autoseleccionar "Nuevo"
    if(!hasDisponibles && hasDefinidos) {
        document.getElementById('modo_nuevo').checked = true;
    }
    
    updateView();
});
</script>
@endcan
