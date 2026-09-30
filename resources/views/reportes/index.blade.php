@extends('layouts.inventario')

@section('title', 'Panel de Reportes')

@section('content')
<x-ui.toolbar 
    title="Panel de Reportes" 
    subtitle="Generación de informes de inventario TIC" 
/>

<div class="row g-4">

    <div class="col-md-4">
        <x-ui.card class="h-100 text-center shadow-sm border-0 transition-hover">
            <div class="mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 report-icon-wrapper">
                <i class="bi bi-laptop fs-3"></i>
            </div>
            <h5 class="fw-bold">Inventario General</h5>
            <p class="text-muted small mb-3">Exportación centralizada de inventario: CMDB principal fijo, información completa o CMDB principal con columnas adicionales.</p>
            <div class="mt-auto">
                @can('equipos.exportar')
                <div class="d-grid gap-2">
                    <x-ui.button href="{{ route('equipos.exportar', ['modo_exportacion' => 'cmdb_principal']) }}" outline="true" color="danger" class="rounded-pill px-4 w-100 btn-sm" icon="file-earmark-spreadsheet" text="Exportar CMDB Principal" />
                    <x-ui.button href="{{ route('equipos.exportar', ['modo_exportacion' => 'completa']) }}" outline="true" color="danger" class="rounded-pill px-4 w-100 btn-sm" icon="collection" text="Exportar Toda la Información" />
                    <button type="button" class="btn btn-outline-danger rounded-pill px-4 btn-sm w-100" data-bs-toggle="modal" data-bs-target="#modalExportacionReportes">
                        <i class="bi bi-sliders me-1"></i>CMDB Principal + Columnas Adicionales
                    </button>
                </div>
                @else
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 btn-sm w-100" disabled>
                    Sin permisos de exportación
                </button>
                @endcan
            </div>
        </x-ui.card>
    </div>
    

    <div class="col-md-4">
        <x-ui.card class="h-100 text-center shadow-sm border-0">
            <div class="mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 report-icon-wrapper">
                <i class="bi bi-person-badge fs-3"></i>
            </div>
            <h5 class="fw-bold">Activos por Funcionario</h5>
            <p class="text-muted small mb-4">Reporte de equipos asignados a cada empleado del Comité Tolima.</p>
            <div class="mt-auto">
                <x-ui.button href="{{ route('reportes.activos-funcionario') }}" outline="true" color="danger" class="rounded-pill px-4 w-100 btn-sm" icon="file-earmark-excel" text="Descargar Excel" />
            </div>
        </x-ui.card>
    </div>


    <div class="col-md-4">
        <x-ui.card class="h-100 text-center shadow-sm border-0">
            <div class="mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 report-icon-wrapper">
                <i class="bi bi-person-gear fs-3"></i>
            </div>
            <h5 class="fw-bold">Asignaciones / Movimientos</h5>
            <p class="text-muted small mb-4">Histórico de actas de entrega, devoluciones y traslados.</p>
            <div class="mt-auto">
                <x-ui.button href="{{ route('reportes.asignaciones') }}" outline="true" color="danger" class="rounded-pill px-4 w-100 btn-sm" icon="file-earmark-excel" text="Descargar Excel" />
            </div>
        </x-ui.card>
    </div>


    <div class="col-md-4">
        <x-ui.card class="h-100 text-center shadow-sm border-0">
            <div class="mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 report-icon-wrapper">
                <i class="bi bi-tools fs-3"></i>
            </div>
            <h5 class="fw-bold">Mantenimientos</h5>
            <p class="text-muted small mb-4">Registro de mantenimientos preventivos y correctivos ejecutados.</p>
            <div class="mt-auto">
                <x-ui.button href="{{ route('reportes.mantenimientos') }}" outline="true" color="danger" class="rounded-pill px-4 w-100 btn-sm" icon="file-earmark-excel" text="Descargar Excel" />
            </div>
        </x-ui.card>
    </div>


    <div class="col-md-4">
        <x-ui.card class="h-100 text-center shadow-sm border-0">
            <div class="mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 report-icon-wrapper">
                <i class="bi bi-shield-check fs-3"></i>
            </div>
            <h5 class="fw-bold">Garantías</h5>
            <p class="text-muted small mb-4">Equipos con garantía vigente y próximos a expirar.</p>
            <div class="mt-auto">
                <x-ui.button href="{{ route('reportes.garantias') }}" outline="true" color="danger" class="rounded-pill px-4 w-100 btn-sm" icon="file-earmark-excel" text="Descargar Excel" />
            </div>
        </x-ui.card>
    </div>


    <div class="col-md-4">
        <x-ui.card class="h-100 text-center shadow-sm border-0">
            <div class="mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 report-icon-wrapper">
                <i class="bi bi-pie-chart fs-3"></i>
            </div>
            <h5 class="fw-bold">Estadísticas</h5>
            <p class="text-muted small mb-4">Métricas generales de la plataforma y hardware del sistema.</p>
            <div class="mt-auto">
                <a href="{{ route('reportes.estadisticas-pdf') }}" target="_blank" class="btn btn-outline-danger rounded-pill px-4 btn-sm w-100"><i class="bi bi-file-earmark-pdf me-1"></i> Generar PDF</a>
            </div>
        </x-ui.card>
    </div>
</div>

@can('equipos.exportar')
<div class="modal fade" id="modalExportacionReportes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formExportacionReportes" method="GET" action="{{ route('equipos.exportar') }}">
                <input type="hidden" name="modo_exportacion" value="personalizada">
                <input type="hidden" name="base_cmdb_principal" value="1">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-excel me-2"></i>Exportación Avanzada</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        El CMDB Principal conserva siempre el orden fijo institucional. Las columnas que selecciones aquí se agregarán al final del archivo, sin alterar ese orden base.
                    </p>

                    <div class="row mb-3 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-primary">Plantillas Guardadas</label>
                            <select class="form-select form-select-sm" id="plantillaExportacionSelect">
                                <option value="">-- Seleccionar Plantilla (Opcional) --</option>
                                @foreach($plantillasExportacion as $plantilla)
                                    <option value="{{ $plantilla->id }}" data-estandar='@json($plantilla->columnas_estandar)' data-personalizadas='@json($plantilla->columnas_personalizadas)'>
                                        {{ $plantilla->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card mb-3 border-secondary border-opacity-25">
                        <div class="card-header bg-light fw-bold py-2">
                            <i class="bi bi-list-check me-1"></i>Columnas Adicionales del Sistema
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                @foreach($columnasAdicionalesCmdb as $key => $label)
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="columnas_estandar[]" value="{{ $key }}" id="col_est_{{ $key }}">
                                        <label class="form-check-label small" for="col_est_{{ $key }}">{{ $label }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2 border-secondary border-opacity-25">
                        <div class="card-header bg-light fw-bold py-2">
                            <i class="bi bi-ui-checks-grid me-1"></i>Campos Personalizados (Dinámicos)
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2" id="contenedorCamposExportables">
                                @if($camposExportables->isEmpty())
                                    <div class="col-12 text-muted small fst-italic">No hay campos personalizados configurados como exportables.</div>
                                @else
                                    @foreach($camposExportables as $campo)
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="columnas_personalizadas[]" value="{{ $campo->id }}" id="col_pers_{{ $campo->id }}" {{ $campo->exportar_por_defecto ? 'checked' : '' }}>
                                            <label class="form-check-label small text-primary" for="col_pers_{{ $campo->id }}">{{ $campo->nombre }}</label>
                                        </div>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="guardar_plantilla" name="guardar_plantilla" value="1">
                            <label class="form-check-label fw-bold text-success" for="guardar_plantilla">Guardar esta selección como nueva plantilla</label>
                        </div>
                        <div class="mt-2" id="div_nombre_plantilla" style="display: none;">
                            <input type="text" class="form-control form-control-sm" name="nombre_plantilla" placeholder="Ej: Reporte Mensual Gerencia">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-download me-1"></i>Generar y Descargar Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkGuardarPlantilla = document.getElementById('guardar_plantilla');
    if (checkGuardarPlantilla) {
        checkGuardarPlantilla.addEventListener('change', function() {
            const divNombre = document.getElementById('div_nombre_plantilla');
            const inputNombre = document.querySelector('input[name="nombre_plantilla"]');

            if (!divNombre || !inputNombre) return;

            divNombre.style.display = this.checked ? 'block' : 'none';

            if (this.checked) {
                inputNombre.setAttribute('required', 'required');
            } else {
                inputNombre.removeAttribute('required');
                inputNombre.value = '';
            }
        });
    }

    const selectPlantilla = document.getElementById('plantillaExportacionSelect');
    if (selectPlantilla) {
        selectPlantilla.addEventListener('change', function() {
            if (!this.value) return;

            const estandar = JSON.parse(this.options[this.selectedIndex].getAttribute('data-estandar') || '[]');
            const personalizadas = JSON.parse(this.options[this.selectedIndex].getAttribute('data-personalizadas') || '[]');

            document.querySelectorAll('input[name="columnas_estandar[]"]').forEach((cb) => cb.checked = false);
            document.querySelectorAll('input[name="columnas_personalizadas[]"]').forEach((cb) => cb.checked = false);

            estandar.forEach((val) => {
                const cb = document.getElementById('col_est_' + val);
                if (cb) cb.checked = true;
            });

            personalizadas.forEach((val) => {
                const cb = document.getElementById('col_pers_' + val);
                if (cb) cb.checked = true;
            });
        });
    }
});
</script>
@endpush
