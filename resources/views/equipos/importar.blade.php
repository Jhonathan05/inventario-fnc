@extends('layouts.inventario')

@section('title', 'Importar Equipos')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="mb-0 fw-bold"><i class="bi bi-file-earmark-arrow-up me-2 equipo-text-primary"></i>Importar Equipos desde Excel</h2>
        <p class="text-muted mb-0 mt-1">Sube un archivo <strong>.xlsx</strong> o <strong>.xls</strong> para registrar equipos en masa.</p>
    </div>
</div>

{{-- ░░ RESUMEN RESULTADO ░░ --}}
@if (session()->has('import_cache_key') || session()->has('import_insertados'))
    @php
        if (session()->has('import_cache_key')) {
            $cacheKey = session('import_cache_key');
            $reportData = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
        } else {
            $reportData = session()->all();
        }
        
        $insertados    = $reportData['import_insertados'] ?? 0;
        $omitidos      = $reportData['import_omitidos'] ?? 0;
        $failures      = $reportData['import_failures'] ?? [];
        $importErrors  = $reportData['import_errors'] ?? [];
        $columnReport  = $reportData['import_column_report'] ?? [];
        $totalFallos   = $reportData['import_total_fallos'] ?? count($failures);
        $fallidas      = $totalFallos + count($importErrors);
        $metricas       = $reportData['import_metricas'] ?? [];
        $total         = $metricas['procesados'] ?? ($insertados + $fallidas + $omitidos);
    @endphp

    {{-- Formato detectado --}}
    @if (!empty($columnReport['formato']))
        <div class="alert {{ $columnReport['formato'] === 'cmdb' ? 'alert-info' : ($columnReport['formato'] === 'propio' ? 'alert-success' : 'alert-warning') }} d-flex align-items-center mb-3">
            <i class="bi bi-magic me-2 fs-5"></i>
            <div>
                <strong>Formato detectado:</strong>
                @if ($columnReport['formato'] === 'cmdb')
                    <span class="badge bg-info text-dark">CMDB Corporativo</span>
                @elseif ($columnReport['formato'] === 'propio')
                    <span class="badge bg-success">Formato Propio del Sistema</span>
                @else
                    <span class="badge bg-warning text-dark">Formato Desconocido</span>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-1 fw-bold text-secondary">{{ $total }}</div>
                <div class="text-muted small">Filas procesadas</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3 border-success">
                <div class="fs-1 fw-bold text-success">{{ $insertados }}</div>
                <div class="text-muted small">Activos creados</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-1 fw-bold text-warning">{{ $omitidos }}</div>
                <div class="text-muted small">Periféricos omitidos</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3 {{ $fallidas > 0 ? 'border-danger' : '' }}">
                <div class="fs-1 fw-bold {{ $fallidas > 0 ? 'text-danger' : 'text-muted' }}">{{ $fallidas }}</div>
                <div class="text-muted small">Con errores</div>
            </div>
        </div>
    </div>

    @if (!empty($metricas))
        <div class="alert alert-light border small">
            <strong>Resultado de sincronización:</strong>
            Activos creados: {{ $metricas['creados'] ?? 0 }} ·
            actualizados: {{ $metricas['actualizados'] ?? 0 }} ·
            funcionarios creados: {{ $metricas['funcionarios_creados'] ?? 0 }} ·
            actualizados: {{ $metricas['funcionarios_actualizados'] ?? 0 }} ·
            asignaciones creadas: {{ $metricas['asignaciones_creadas'] ?? 0 }} ·
            actualizadas: {{ $metricas['asignaciones_actualizadas'] ?? 0 }} ·
            responsabilidades creadas: {{ $metricas['responsabilidades_creadas'] ?? 0 }} ·
            actualizadas: {{ $metricas['responsabilidades_actualizadas'] ?? 0 }}
        </div>
    @endif

    {{-- ░░ REPORTE DE COLUMNAS ░░ --}}
    @if (!empty($columnReport))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom py-3">
                <i class="bi bi-layout-three-columns me-2 equipo-text-primary"></i>Reporte de Columnas
                <button class="btn btn-sm btn-outline-secondary float-end" type="button" data-bs-toggle="collapse" data-bs-target="#colReporte">
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
            <div class="collapse" id="colReporte" style="visibility: visible;">
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Columnas reconocidas --}}
                        <div class="col-md-4">
                            <h6 class="text-success fw-bold mb-2">
                                <i class="bi bi-check-circle-fill me-1"></i>
                                Reconocidas ({{ count($columnReport['reconocidas'] ?? []) }})
                            </h6>
                            @if (!empty($columnReport['reconocidas']))
                                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Campo</th>
                                                <th>Columna Excel</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($columnReport['reconocidas'] as $col)
                                                <tr>
                                                    <td><code>{{ $col['campo_interno'] }}</code></td>
                                                    <td class="text-muted">{{ $col['columna_excel'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted small">Ninguna</p>
                            @endif
                        </div>

                        {{-- Columnas ignoradas --}}
                        <div class="col-md-4">
                            <h6 class="text-secondary fw-bold mb-2">
                                <i class="bi bi-dash-circle me-1"></i>
                                Ignoradas ({{ $columnReport['total_ignoradas'] ?? count($columnReport['ignoradas'] ?? []) }})
                            </h6>
                            @if (!empty($columnReport['ignoradas']))
                                <div style="max-height: 250px; overflow-y: auto;">
                                    @php $ignoradasTotal = $columnReport['total_ignoradas'] ?? count($columnReport['ignoradas']); @endphp
                                    @foreach ($columnReport['ignoradas'] as $col)
                                        <span class="badge bg-light text-dark border me-1 mb-1">{{ Str::limit((string)$col, 30) }}</span>
                                    @endforeach
                                    @if ($ignoradasTotal > 50)
                                        <span class="badge bg-secondary me-1 mb-1">+{{ $ignoradasTotal - 50 }} más</span>
                                    @endif
                                </div>
                            @else
                                <p class="text-muted small">Ninguna</p>
                            @endif
                        </div>

                        {{-- Campos faltantes --}}
                        <div class="col-md-4">
                            <h6 class="text-warning fw-bold mb-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Faltantes ({{ count($columnReport['faltantes'] ?? []) }})
                            </h6>
                            @if (!empty($columnReport['faltantes']))
                                <div style="max-height: 250px; overflow-y: auto;">
                                    @foreach ($columnReport['faltantes'] as $campo)
                                        <span class="badge bg-warning-subtle text-warning-emphasis me-1 mb-1">{{ $campo }}</span>
                                    @endforeach
                                </div>
                                <p class="text-muted small mt-2">Se usarán valores por defecto para estos campos.</p>
                            @else
                                <p class="text-success small"><i class="bi bi-check me-1"></i>Todos los campos fueron encontrados.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Detalles de filas con fallo de validación --}}
    @if (count($failures) > 0)
        <div class="card border-danger mb-4 shadow-sm">
            <div class="card-header bg-danger text-white fw-bold">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>Filas con errores de validación
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="equipo-import-col-fila">Fila #</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($failures as $f)
                                <tr>
                                    <td class="fw-bold text-danger">{{ $f['fila'] }}</td>
                                    <td>
                                        @foreach ($f['errores'] as $err)
                                            <span class="badge bg-danger-subtle text-danger-emphasis me-1 mb-1">{{ $err }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Errores de excepción --}}
    @if (count($importErrors) > 0)
        <div class="card border-warning mb-4 shadow-sm">
            <div class="card-header bg-warning text-dark fw-bold">
                <i class="bi bi-bug-fill me-2"></i>Errores inesperados durante la importación
            </div>
            <ul class="list-group list-group-flush">
                @foreach ($importErrors as $e)
                    <li class="list-group-item text-muted small">{{ $e['mensaje'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endif

{{-- ░░ FORMULARIO ░░ --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-semibold border-bottom py-3">
        <i class="bi bi-upload me-2 equipo-text-primary"></i>Seleccionar archivo Excel
    </div>
    <div class="card-body">
        <form action="{{ route('equipos.importar') }}" method="POST" enctype="multipart/form-data" id="formImportar">
            @csrf

            <div class="mb-4">
                <label for="archivo" class="form-label fw-semibold">Archivo Excel <span class="text-danger">*</span></label>
                <input type="file"
                       class="form-control @error('archivo') is-invalid @enderror"
                       id="archivo"
                       name="archivo"
                       accept=".xlsx,.xls"
                       required>
                @error('archivo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text text-muted">Formatos admitidos: <strong>.xlsx</strong>, <strong>.xls</strong>. Tamaño máximo: 10 MB.</div>
            </div>

            <div class="alert alert-light border mb-4">
                <p class="fw-semibold mb-2 equipo-text-primary">
                    <i class="bi bi-magic me-1"></i>
                    El sistema detecta automáticamente el formato del archivo (CMDB corporativo o formato propio).
                </p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <p class="fw-semibold mb-1 small equipo-text-primary"><i class="bi bi-laptop-fill me-1"></i>Equipo</p>
                        <table class="table table-sm table-borderless mb-0 small">
                            <tbody>
                                <tr><td><code>tipo_recurso</code></td><td class="text-muted">TIPO DE RECURSO</td></tr>
                                <tr><td><code>serial</code></td><td class="text-muted">SERIAL</td></tr>
                                <tr><td><code>placa</code></td><td class="text-muted">PLACA</td></tr>
                                <tr><td><code>marca</code></td><td class="text-muted">MARCA, MARCA EQUIPO</td></tr>
                                <tr><td><code>modelo</code></td><td class="text-muted">MODELO</td></tr>
                                <tr><td><code>nombre_equipo</code></td><td class="text-muted">NOMBRE DE EQUIPO</td></tr>
                                <tr><td><code>estado_operativo</code></td><td class="text-muted">ESTADO OPERATIVO</td></tr>
                                <tr><td><code>procesador</code></td><td class="text-muted">PROCESADOR</td></tr>
                                <tr><td><code>ram</code></td><td class="text-muted">MEMORIA RAM</td></tr>
                                <tr><td><code>disco</code></td><td class="text-muted">TAMAÑO DISCO DURO</td></tr>
                                <tr><td><code>sistema_operativo</code></td><td class="text-muted">SISTEMA OPERATIVO</td></tr>
                                <tr><td><code>fecha_compra</code></td><td class="text-muted">FECHA DE COMPRA</td></tr>
                                <tr><td><code>fin_garantia</code></td><td class="text-muted">FIN DE GARANTÍA</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <p class="fw-semibold mb-1 small equipo-text-primary"><i class="bi bi-person-fill me-1"></i>Usuario asignado</p>
                        <table class="table table-sm table-borderless mb-0 small">
                            <tbody>
                                <tr><td><code>nombre</code></td><td class="text-muted">NOMBRES Y APELLIDOS</td></tr>
                                <tr><td><code>cedula</code></td><td class="text-muted">CÉDULA DEL FUNCIONARIO</td></tr>
                                <tr><td><code>empresa_propietaria</code></td><td class="text-muted">EMPRESA PROPIETARIO</td></tr>
                                <tr><td><code>dependencia</code></td><td class="text-muted">Departamento</td></tr>
                                <tr><td><code>fuente_recurso</code></td><td class="text-muted">FUENTE DE RECURSO</td></tr>
                                <tr><td><code>empresa_funcionario</code></td><td class="text-muted">EMPRESA FUNCIONARIO</td></tr>
                                <tr><td><code>tipo_vinculacion</code></td><td class="text-muted">EMPLEADO O CONTRATISTA</td></tr>
                                <tr><td><code>shortname</code></td><td class="text-muted">SHORTNAME</td></tr>
                                <tr><td><code>departamento</code></td><td class="text-muted">DEPARTAMENTO</td></tr>
                                <tr><td><code>ciudad</code></td><td class="text-muted">Ciudad</td></tr>
                                <tr><td><code>cargo</code></td><td class="text-muted">CARGO</td></tr>
                                <tr><td><code>area</code></td><td class="text-muted">Área</td></tr>
                                <tr><td><code>piso</code></td><td class="text-muted">UBICACIÓN Y PISO</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <p class="fw-semibold mb-1 small text-warning-emphasis"><i class="bi bi-slash-circle me-1"></i>Se ignoran automáticamente</p>
                        <ul class="list-unstyled small text-muted mb-3">
                            <li>Filas con <code>tipo_recurso</code> que contenga:</li>
                            <li class="ms-2">• <em>telefono</em></li>
                            <li class="ms-2">• <em>teclado</em></li>
                            <li class="ms-2">• <em>mouse</em></li>
                            <li class="ms-2">• <em>camara</em></li>
                            <li class="mt-1">Filas completamente vacías</li>
                            <li>Seriales duplicados (se actualizan)</li>
                        </ul>
                        <p class="fw-semibold mb-1 small text-success-emphasis"><i class="bi bi-check2-circle me-1"></i>Normalización automática</p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li>Mayúsculas, acentos, espacios → sin problema</li>
                            <li>Serial vacío → <code>SIN_SERIAL_xxx</code></li>
                            <li>Marca vacía → <code>Sin Marca</code></li>
                            <li>Nombre vacío → <code>Sin Asignar</code></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('equipos.index') }}" class="btn text-nowrap px-3 btn-outline-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn text-nowrap px-3 text-white equipo-bg-primary" id="btnImportar">
                    <i class="bi bi-cloud-upload me-2"></i>Importar Excel
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('formImportar').addEventListener('submit', function () {
    const btn = document.getElementById('btnImportar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
});
</script>
@endpush
