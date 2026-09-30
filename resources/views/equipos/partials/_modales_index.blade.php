{{-- â•â•â• MODAL: AcciÃ³n con datos de usuario (prÃ©stamo / reemplazo) â•â•â•â•â•â•â•â•â•â• --}}
<x-ui.modal id="modalAsignacion" title="Registrar AsignaciÃ³n" size="modal-lg modal-dialog-scrollable modal-fullscreen-md-down" formId="formAsignacion" action="{{ route('asignaciones.store') }}" submitId="btnConfirmarAsig">
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
                                Selecciona un funcionario elegible. Si tiene activos, debe tener autorizaciones cargadas en el mÃ³dulo Funcionarios.
                            </small>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAbrirSelectorFuncionario">
                                <i class="bi bi-people me-1"></i>Seleccionar funcionario elegible
                            </button>
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-3 bg-light" id="resumenFuncionarioSeleccionado">
                                <div class="text-muted">AÃºn no has seleccionado un funcionario.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <x-ui.input type="text" name="entregado_por" label="Entregado Por" value="{{ auth()->user()->name }}" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="date" name="fecha_accion" label="Fecha de PrÃ©stamo" value="{{ date('Y-m-d') }}" />
                        </div>
                    </div>
</x-ui.modal>

{{-- â•â•â• MODAL: Selector de funcionarios elegibles â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<x-ui.modal id="modalSelectorFuncionario" title="Funcionarios elegibles" size="modal-lg modal-dialog-scrollable modal-fullscreen-md-down">
                <div class="mb-3">
                    <input type="text" id="filtroFuncionarioElegible" class="form-control" placeholder="Buscar por nombre, cÃ©dula, cargo o Ã¡rea...">
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>CÃ©dula</th>
                                <th>Cargo / Ãrea</th>
                                <th>Estado de elegibilidad</th>
                                <th class="text-end">AcciÃ³n</th>
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

{{-- â•â•â• MODAL: AcciÃ³n simple (retiro, baja, mantenimiento) â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<x-ui.modal id="modalSimple" title="AcciÃ³n" size="modal-dialog-scrollable modal-fullscreen-md-down" formId="formSimple" action="{{ route('asignaciones.store') }}" submitText="Confirmar" submitIcon="check-lg" submitId="btnConfirmarSimple">
    <input type="hidden" name="equipo_id" id="simple_equipo_id">
    <input type="hidden" name="tipo_accion" id="simple_tipo_accion">
    <input type="hidden" name="return_to" id="simple_return_to" value="{{ request()->fullUrl() }}">
                    <p class="text-muted small mb-3">
                        <i class="bi bi-laptop me-1"></i>
                        Equipo: <strong id="simple_nombre_equipo"></strong>
                    </p>
                    <div class="mb-3" id="simple_motivo_wrap">
                        <x-ui.textarea name="motivo" id="simple_motivo" label="Motivo" required="true" rows="3" placeholder="Describa el motivo de esta acciÃ³n..." />
                    </div>
                    <div class="mb-3">
                        <x-ui.textarea name="observaciones" id="simple_observaciones" label="Observaciones" rows="2" />
                    </div>
</x-ui.modal>

{{-- â•â•â• MODAL: AsignaciÃ³n Bajo Responsabilidad â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<x-ui.modal id="modalResponsabilidad" title="AsignaciÃ³n Bajo Responsabilidad" size="modal-lg modal-dialog-scrollable modal-fullscreen-md-down" formId="formResponsabilidad" action="">
    <input type="hidden" name="_method" id="resp_method" value="POST">
                    <p class="text-muted small mb-3">
                        <i class="bi bi-laptop me-1"></i>
                        Equipo: <strong id="resp_nombre_equipo"></strong>
                    </p>
                    <div class="row g-3">
                        {{-- SECCIÃ“N: RESPONSABLE ADMINISTRATIVO --}}
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
                                <div class="text-muted">AÃºn no has seleccionado un responsable.</div>
                            </div>
                        </div>

                        {{-- SECCIÃ“N: USUARIO QUE UTILIZARÃ EL ACTIVO --}}
                        <div class="col-12">
                            <h6 class="text-info"><i class="bi bi-person-badge me-2"></i>Usuario que utilizarÃ¡ el activo</h6>
                            <small class="text-muted mb-3 d-block">Este NO es responsable administrativo. Es simplemente quien utilizarÃ¡ el equipo.</small>
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
                            <x-ui.input type="text" name="area" id="resp_area" label="Ãrea" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="email" name="correo" id="resp_correo" label="Correo" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="text" name="telefono" id="resp_telefono" label="TelÃ©fono" />
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
                <i class="bi bi-x-circle me-1"></i>Finalizar AsignaciÃ³n
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

{{-- â•â•â• MODAL: Finalizar AsignaciÃ³n Bajo Responsabilidad â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<x-ui.modal id="modalFinalizarResponsabilidad" title="Finalizar AsignaciÃ³n" formId="formFinalizarRespReal" action="" method="POST" submitText="Confirmar FinalizaciÃ³n" submitIcon="check2-circle">
    @method('DELETE')
                    <div class="mb-3">
                        <x-ui.input type="date" name="fecha_final_real" label="Fecha Final Real" required="true" value="{{ date('Y-m-d') }}" />
                    </div>
                    <div class="mb-3">
                        <x-ui.select name="motivo_finalizacion" label="Motivo de FinalizaciÃ³n" required="true">
<option value="">Seleccione un motivo...</option>
                            <option value="DevoluciÃ³n por fin de contrato">DevoluciÃ³n por fin de contrato</option>
                            <option value="ReasignaciÃ³n a otro usuario">ReasignaciÃ³n a otro usuario</option>
                            <option value="Renuncia / Retiro">Renuncia / Retiro</option>
                            <option value="DaÃ±o / PÃ©rdida del equipo">DaÃ±o / PÃ©rdida del equipo</option>
                            <option value="Cambio de proyecto">Cambio de proyecto</option>
                            <option value="Otro">Otro</option>
                            </x-ui.select>
                    </div>
    <x-slot name="footer">
        <x-ui.button type="button" color="secondary" outline="true" data-bs-dismiss="modal" text="Cancelar" />
        <x-ui.button type="submit" color="danger" icon="check2-circle" text="Confirmar FinalizaciÃ³n" />
    </x-slot>
</x-ui.modal>

@endsection
