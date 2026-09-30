<?php

use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\HistorialAdministrativoController;
use App\Http\Controllers\HistorialTecnicoController;
use App\Http\Controllers\PlantillaPdfController;
use App\Http\Controllers\TipoRecursoController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\LicenciaController;
use App\Http\Controllers\LicenciaSerialController;
use App\Http\Controllers\LicenciaAsignacionController;
use App\Http\Controllers\LicenciaHistorialController;
use App\Http\Controllers\PrestamoController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;

// Ruta raíz redirige a la pantalla principal (o al login si no está autenticado)
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/test-export', function() {
    return \App\Models\CampoPersonalizado::select('id', 'nombre', 'mostrar_en_grilla', 'participa_exportacion_cmdb', 'exportar_excel_despues_de')->get();
});


// === Ruta de cambio obligatorio de contraseña (primer inicio) ===
Route::middleware(['auth'])->group(function () {
    Route::get('/cambiar-contraseña', [ForcePasswordChangeController::class, 'show'])->name('password.force-change');
    Route::put('/cambiar-contraseña', [ForcePasswordChangeController::class, 'update'])->name('password.force-change.update');
});

// === Rutas protegidas por autenticación ===
Route::middleware(['auth', 'verified', 'prevent-back-history', 'force-password-change'])->group(function () {

    // Pantalla principal
    Route::get('/inicio', [DashboardController::class, 'index'])->name('inicio')->middleware('permission:dashboard.ver');
    Route::get('/inicia', [DashboardController::class, 'index'])->name('inicia')->middleware('permission:dashboard.ver');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:dashboard.ver');

    // Rutas de perfil removidas por no integrarse con el módulo unificado de Usuarios

    // ====== CAMPOS PERSONALIZADOS ======
    Route::resource('campos-personalizados', \App\Http\Controllers\CampoPersonalizadoController::class)
        ->except(['show', 'create', 'edit'])
        ->middleware('permission:campos_personalizados.ver');
    Route::post('/campos-personalizados/reorder', [\App\Http\Controllers\CampoPersonalizadoController::class, 'reorder'])
        ->name('campos-personalizados.reorder')
        ->middleware('permission:campos_personalizados.editar');

    // Módulo Usuarios y Asignación de Roles (solo administradores o con permiso)
    Route::resource('usuarios', UserController::class)
        ->middlewareFor(['index', 'show'], 'permission:roles.ver')
        ->middlewareFor(['create', 'store'], 'permission:roles.crear')
        ->middlewareFor(['edit', 'update'], 'permission:roles.editar')
        ->middlewareFor(['destroy'], 'permission:roles.eliminar');
    
    // Módulo de Roles y Permisos (solo administradores o con permiso)
    Route::resource('roles', RoleController::class)
        ->middlewareFor(['index', 'show'], 'permission:roles.ver')
        ->middlewareFor(['create', 'store'], 'permission:roles.crear')
        ->middlewareFor(['edit', 'update'], 'permission:roles.editar')
        ->middlewareFor(['destroy'], 'permission:roles.eliminar');
    
    // Módulo de Auditoría
    Route::get('/auditoria', [AuditController::class, 'index'])->name('auditoria.index')->middleware('permission:roles.ver');

    // Exportar debe declararse ANTES del resource para evitar conflicto con 'show'
    Route::get('/equipos/exportar', [EquipoController::class, 'exportar'])
        ->name('equipos.exportar')
        ->middleware(['permission:equipos.exportar', 'throttle:10,1']);

    // Importación y Exportación de Equipos
    Route::get('/equipos/importar', [EquipoController::class, 'importarForm'])->name('equipos.importar.form')->middleware('permission:equipos.importar');
    Route::post('/equipos/importar', [EquipoController::class, 'importar'])
        ->name('equipos.importar')
        ->middleware(['permission:equipos.importar', 'throttle:2,1']);
    Route::get('/equipos/{equipo}/acta', [EquipoController::class, 'descargarActa'])->name('equipos.acta')->middleware('permission:equipos.ver');

    // Historial de vida del equipo (ANTES del resource)
    Route::get('/equipos/{equipo}/historial-vida', [EquipoController::class, 'historialVida'])
        ->name('equipos.historial-vida')->middleware('permission:equipos.ver');

    // ── Complementos del Activo Global (Debe ir antes de resource equipos) ───
    Route::get('/equipos/complementos-global', [\App\Http\Controllers\ComplementoGlobalController::class, 'index'])
        ->name('equipos.complementos.global')->middleware('permission:equipos.ver');

    // Historial de Complementos
    Route::get('/equipos/complementos/historial-global', [\App\Http\Controllers\HistorialComplementoController::class, 'global'])
        ->name('equipos.complementos.historial_global')->middleware('permission:equipos.ver');
    Route::get('/equipos/complementos/{id}/historial', [\App\Http\Controllers\HistorialComplementoController::class, 'individual'])
        ->name('equipos.complementos.historial_individual')->middleware('permission:equipos.ver');

    // Equipos (CRUD) con permisos por acción
    Route::resource('equipos', EquipoController::class)
        ->middlewareFor(['index', 'show'], 'permission:equipos.ver')
        ->middlewareFor(['create', 'store'], 'permission:equipos.crear')
        ->middlewareFor(['edit', 'update'], 'permission:equipos.editar')
        ->middlewareFor(['destroy'], 'permission:equipos.eliminar');

    // ── Complementos del Activo ──────────────────────────────────────────────
    Route::post('/equipos/{equipo}/complementos', [EquipoController::class, 'storeComplemento'])->name('equipos.complementos.store')->middleware('permission:equipos.crear');
    Route::put('/equipos/{equipo}/complementos/{complemento}', [EquipoController::class, 'updateComplemento'])->name('equipos.complementos.update')->middleware('permission:equipos.editar');
    Route::delete('/equipos/{equipo}/complementos/{complemento}', [EquipoController::class, 'destroyComplemento'])->name('equipos.complementos.destroy')->middleware('permission:equipos.eliminar');
    Route::post('/equipos/{equipo}/complementos/{complemento}/transferir', [EquipoController::class, 'transferirComplemento'])->name('equipos.complementos.transferir')->middleware('permission:equipos.editar');
    
    // API interna para JS
    Route::get('/tipo-recursos/{tipoRecurso}/complementos-definidos', [EquipoController::class, 'getComplementosPorTipo'])->name('tipo-recursos.complementos-definidos')->middleware('permission:equipos.ver');
    Route::post('/catalogo-complementos', [TipoRecursoController::class, 'storeCatalogoComplemento'])->name('catalogo-complementos.store')->middleware('permission:equipos.crear');
    Route::put('/catalogo-complementos/{catalogoComplemento}', [TipoRecursoController::class, 'updateCatalogoComplemento'])->name('catalogo-complementos.update')->middleware('permission:equipos.crear');

    // ── Lector de código de barras (búsqueda por placa) ──
    Route::get('/equipos-buscar-placa', [EquipoController::class, 'buscarPorPlaca'])->name('equipos.buscar-placa')->middleware('permission:equipos.ver');

    // ── Asignaciones ──────────────────────────────────────────────────────────
    Route::get('/asignaciones', [AsignacionController::class, 'index'])
        ->name('asignaciones.index')->middleware('permission:equipos.ver');

    Route::post('/asignaciones', [AsignacionController::class, 'store'])
        ->name('asignaciones.store')->middleware('permission:equipos.crear');

    Route::get('/asignaciones/funcionarios-elegibles', [AsignacionController::class, 'funcionariosElegibles'])
        ->name('asignaciones.funcionarios-elegibles')->middleware('permission:equipos.ver');

    Route::get('/asignaciones/{asignacion}', [AsignacionController::class, 'show'])
        ->name('asignaciones.show')->middleware('permission:equipos.ver');

    Route::get('/equipos/{equipo}/asignaciones', [AsignacionController::class, 'porEquipo'])
        ->name('asignaciones.por-equipo')->middleware('permission:equipos.ver');

    Route::get('/asignaciones/{asignacion}/pdf', [AsignacionController::class, 'generarPdf'])
        ->name('asignaciones.pdf')->middleware('permission:equipos.exportar');

    // ── Préstamos ─────────────────────────────────────────────────────────────
    Route::post('/prestamos/{prestamo}/devolver', [PrestamoController::class, 'registrarDevolucion'])
        ->name('prestamos.devolver')->middleware('permission:equipos.crear');
    Route::post('/prestamos/{prestamo}/cancelar', [PrestamoController::class, 'cancelar'])
        ->name('prestamos.cancelar')->middleware('permission:equipos.crear');
    Route::resource('prestamos', PrestamoController::class)
        ->except(['destroy'])
        ->middlewareFor(['index', 'show'], 'permission:equipos.ver')
        ->middlewareFor(['create', 'store', 'edit', 'update'], 'permission:equipos.crear');

    // ── Actas Firmadas ────────────────────────────────────────────────────────
    Route::get('/actas-firmadas', [\App\Http\Controllers\ActaFirmadaController::class, 'index'])->name('actas-firmadas.index')->middleware('permission:equipos.ver');
    Route::post('/actas-firmadas', [\App\Http\Controllers\ActaFirmadaController::class, 'store'])->name('actas-firmadas.store')->middleware('permission:equipos.crear');
    Route::post('/actas-firmadas/zip', [\App\Http\Controllers\ActaFirmadaController::class, 'downloadZip'])->name('actas-firmadas.zip')->middleware('permission:equipos.ver');
    Route::put('/actas-firmadas/{id}', [\App\Http\Controllers\ActaFirmadaController::class, 'update'])->name('actas-firmadas.update')->middleware('permission:equipos.crear');
    Route::get('/actas-firmadas/{id}/download', [\App\Http\Controllers\ActaFirmadaController::class, 'download'])->name('actas-firmadas.download')->middleware('permission:equipos.ver');
    Route::get('/actas-firmadas/{id}/view', [\App\Http\Controllers\ActaFirmadaController::class, 'showFile'])->name('actas-firmadas.show-file')->middleware('permission:equipos.ver');
    Route::get('/actas-firmadas/versions/{id}/download', [\App\Http\Controllers\ActaFirmadaController::class, 'downloadVersion'])->name('actas-firmadas.download-version')->middleware('permission:equipos.ver');
    Route::get('/actas-firmadas/{id}/history', [\App\Http\Controllers\ActaFirmadaController::class, 'history'])->name('actas-firmadas.history')->middleware('permission:equipos.ver');

    // ── Historial Técnico ─────────────────────────────────────────────────────
    Route::get('/equipos/{equipo}/historial-tecnico', [HistorialTecnicoController::class, 'porEquipo'])
        ->name('historial-tecnico.por-equipo')->middleware('permission:historial.ver');

    Route::resource('historial-tecnico', HistorialTecnicoController::class)
        ->parameters(['historial-tecnico' => 'historialTecnico'])->middleware('permission:historial.ver');

    // ── Historial Administrativo (solo lectura) ───────────────────────────────
    Route::get('/historial-administrativo', [HistorialAdministrativoController::class, 'index'])
        ->name('historial-administrativo.index')->middleware('permission:historial.ver');

    Route::get('/equipos/{equipo}/historial-administrativo', [HistorialAdministrativoController::class, 'porEquipo'])
        ->name('historial-administrativo.por-equipo')->middleware('permission:historial.ver');

    // ── Plantillas PDF ────────────────────────────────────────────────────────
    Route::resource('plantillas-pdf', PlantillaPdfController::class)
        ->parameters(['plantillas-pdf' => 'plantillasPdf'])->middleware('permission:configuracion.editar');

    // ── Tipos de Recurso ──────────────────────────────────────────────────────
    Route::resource('tipo-recursos', TipoRecursoController::class)->middleware('permission:configuracion.editar');

    // ── Checklists ────────────────────────────────────────────────────────────
    Route::resource('checklists', ChecklistController::class)
        ->middlewareFor(['index', 'show'], 'permission:checklist.ver')
        ->middlewareFor(['create', 'store'], 'permission:checklist.crear')
        ->middlewareFor(['edit', 'update'], 'permission:checklist.editar')
        ->middlewareFor(['destroy'], 'permission:checklist.eliminar');

    // ── Funcionarios ──────────────────────────────────────────────────────────
    Route::resource('funcionarios', FuncionarioController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middlewareFor(['index', 'show'], 'permission:usuarios.ver')
        ->middlewareFor(['create', 'store'], 'permission:usuarios.crear');
    Route::get('/funcionarios/{funcionario}/edit', [FuncionarioController::class, 'edit'])
        ->name('funcionarios.edit')
        ->middleware('permission:usuarios.editar');
    Route::put('/funcionarios/{funcionario}', [FuncionarioController::class, 'update'])
        ->name('funcionarios.update')
        ->middleware('permission:usuarios.editar');
    Route::post('/funcionarios/{funcionario}/autorizaciones', [FuncionarioController::class, 'storeAutorizacion'])
        ->name('funcionarios.autorizaciones.store')
        ->middleware('permission:usuarios.crear');
    Route::patch('/funcionarios/{funcionario}/autorizaciones/{autorizacion}/anular', [FuncionarioController::class, 'anularAutorizacion'])
        ->name('funcionarios.autorizaciones.anular')
        ->middleware('permission:usuarios.editar');
    Route::get('/funcionarios/{funcionario}/autorizaciones/{autorizacion}/descargar', [FuncionarioController::class, 'descargarAutorizacion'])
        ->name('funcionarios.autorizaciones.descargar')
        ->middleware('permission:usuarios.ver');

    // ── HelpDesk (Tickets) ────────────────────────────────────────────────────
    Route::put('/tickets/{ticket}/estado', [TicketController::class, 'cambiarEstado'])->name('tickets.estado')->middleware('permission:mesaayuda.editar');
    Route::put('/tickets/{ticket}/diagnostico', [TicketController::class, 'updateDiagnostico'])->name('tickets.diagnostico')->middleware('permission:mesaayuda.editar');
    Route::post('/tickets/{ticket}/seguimiento', [TicketController::class, 'storeSeguimiento'])->name('tickets.seguimiento')->middleware('permission:mesaayuda.editar');
    Route::put('/tickets/{ticket}/solucion', [TicketController::class, 'updateSolucion'])->name('tickets.solucion')->middleware('permission:mesaayuda.editar');
    Route::post('/tickets/{ticket}/evidencia', [TicketController::class, 'uploadEvidencia'])->name('tickets.evidencia')->middleware('permission:mesaayuda.editar');
    Route::get('/tickets/{ticket}/evidencia/{index}/descargar', [TicketController::class, 'descargarEvidencia'])->name('tickets.evidencia.descargar')->middleware('permission:mesaayuda.ver');
    Route::resource('tickets', TicketController::class)
        ->middlewareFor(['index', 'show'], 'permission:mesaayuda.ver')
        ->middlewareFor(['create', 'store'], 'permission:mesaayuda.crear')
        ->middlewareFor(['edit', 'update'], 'permission:mesaayuda.editar')
        ->middlewareFor(['destroy'], 'permission:mesaayuda.eliminar');

    // ── Reportes ──────────────────────────────────────────────────────────────
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index')->middleware('permission:dashboard.ver');
    Route::get('/reportes/activos-funcionario', [ReporteController::class, 'activosPorFuncionario'])->name('reportes.activos-funcionario')->middleware('permission:dashboard.ver');
    Route::get('/reportes/asignaciones', [ReporteController::class, 'asignaciones'])->name('reportes.asignaciones')->middleware('permission:dashboard.ver');
    Route::get('/reportes/mantenimientos', [ReporteController::class, 'mantenimientos'])->name('reportes.mantenimientos')->middleware('permission:dashboard.ver');
    Route::get('/reportes/garantias', [ReporteController::class, 'garantias'])->name('reportes.garantias')->middleware('permission:dashboard.ver');
    Route::get('/reportes/estadisticas-pdf', [ReporteController::class, 'estadisticasPdf'])->name('reportes.estadisticas-pdf')->middleware('permission:dashboard.ver');

    // ── Licencias ─────────────────────────────────────────────────────────────
    Route::get('/licencias/reportes', [LicenciaController::class, 'reportes'])->name('licencias.reportes')->middleware('permission:licencias.ver');
    Route::get('/licencias/exportar', [LicenciaController::class, 'exportar'])
        ->name('licencias.exportar')
        ->middleware(['permission:licencias.exportar', 'throttle:10,1']);
    Route::get('/licencias/historial', [LicenciaHistorialController::class, 'index'])->name('licencias.historial')->middleware('permission:licencias.ver');
    Route::resource('licencias', LicenciaController::class)
        ->middlewareFor(['index', 'show'], 'permission:licencias.ver')
        ->middlewareFor(['create', 'store'], 'permission:licencias.crear')
        ->middlewareFor(['edit', 'update'], 'permission:licencias.editar')
        ->middlewareFor(['destroy'], 'permission:licencias.eliminar');
    Route::resource('licencias.seriales', LicenciaSerialController::class)->only(['store', 'update', 'destroy'])->middleware('permission:licencias.editar');
    Route::resource('licencia-asignaciones', LicenciaAsignacionController::class)
        ->parameters(['licencia-asignaciones' => 'licencia_asignacion'])
        ->middleware('permission:licencias.editar');

    // ── Suscripciones y Vitalicias (Redirigen al módulo unificado) ────────────
    Route::redirect('/suscripciones', '/licencias');
    Route::redirect('/vitalicias', '/licencias');


    Route::get('/test-logo', function() {
        $pngPath = public_path('imagenes/federacion cafeteros logo.png');
        if (!file_exists($pngPath)) {
            return 'NOT_FOUND: ' . $pngPath;
        }
        return 'EXISTS. Base64 length: ' . strlen(base64_encode(file_get_contents($pngPath)));
    });

    Route::get('/instalar-plantilla-fnc', function() {
        \App\Models\PlantillaPdf::where('activa', true)->update(['activa' => false]);

        $html = '<style>
    @page { margin: 15px 20px; size: letter landscape; font-family: Arial, sans-serif; font-size: 10px; }
    body { font-family: Arial, sans-serif; font-size: 10px; color: #000; padding: 0; }
    table { width: 100%; border-collapse: collapse; }
    td, th { border: 1px solid #000; padding: 3px; text-align: center; vertical-align: middle; }
    .no-border, .no-border td { border: none !important; }
    .header-table { margin-bottom: 8px; }
    .header-table td { font-weight: bold; }
    .logo-cell { width: 15%; padding: 2px; }
    .title-cell { width: 65%; font-size: 13px; }
    .meta-cell { width: 20%; font-size: 9px; text-align: left; padding-left: 5px; }
    .info-section { margin-bottom: 8px; }
    .info-section td { text-align: center; }
    .info-label { font-weight: bold; font-size: 10px; display: inline-block; margin-right: 5px; }
    .info-box { border: 1px solid #000; padding: 2px 10px; display: inline-block; min-width: 100px; text-align: center; font-weight: normal; }
    .main-table { margin-bottom: 8px; }
    .main-table th { background-color: #d9d9d9; font-weight: bold; font-size: 9px; }
    .main-table td { font-size: 9px; height: 16px; text-transform: uppercase; }
    .text-na { color: #888; text-transform: none !important; }
    .fw-bold { font-weight: bold; }
    .signatures { margin-top: 15px; width: 100%; page-break-inside: avoid; }
    .sig-box { border: 1px solid #000; text-align: center; padding: 3px; margin-bottom: 2px; font-weight: bold; font-size: 9px; min-height: 12px; text-transform: uppercase; }
    .sig-label { text-align: center; font-weight: bold; font-size: 9px; margin-bottom: 8px; }
    .sig-col { width: 45%; }
    .sig-spacer { width: 10%; }
</style>

<table class="header-table">
    <tr>
        <td rowspan="2" class="logo-cell"><img src="{{logo_fnc}}" alt="Logo" width="165" height="110" style="object-fit: contain;"></td>
        <td class="title-cell">FEDERACIÓN NACIONAL DE CAFETEROS DE COLOMBIA</td>
        <td class="meta-cell">Código: FE-BS-F-0069<br><br>Fecha: 11/04/2017</td>
    </tr>
    <tr>
        <td class="title-cell">NOVEDAD DE ACTIVO</td>
        <td class="meta-cell">Versión: 1</td>
    </tr>
</table>

<table class="no-border info-section">
    <tr>
        <td style="width: 33%;">
            <span class="info-label">Tipo de Novedad</span>
            <div class="info-box" style="color: #666;">Inventario Físico</div>
        </td>
        <td style="width: 33%;">
            <span class="info-label">Ubicación:</span>
            <div class="info-box">{{ciudad}}</div>
        </td>
        <td style="width: 33%;">
            <span class="info-label">Fecha:</span>
            <div class="info-box">{{fecha_generacion}}</div>
        </td>
    </tr>
</table>

<table class="main-table">
    <thead>
        <tr>
            <th style="width: 10%;">Activo fijo</th>
            <th style="width: 6%;">SN°</th>
            <th style="width: 22%;">Denominación del activo fijo</th>
            <th style="width: 10%;">Marca</th>
            <th style="width: 12%;">Modelo</th>
            <th style="width: 12%;">Serie</th>
            <th style="width: 10%;">Placa de inventario</th>
            <th style="width: 18%;">Observaciones</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="text-na">N/A</td>
            <td class="text-na">N/A</td>
            <td class="fw-bold">{{tipo_recurso}}</td>
            <td class="fw-bold">{{marca}}</td>
            <td class="fw-bold">{{modelo}}</td>
            <td class="fw-bold">{{serial}}</td>
            <td class="fw-bold">{{activo_fijo}}</td>
            <td class="fw-bold">ENTREGA DE EQUIPO</td>
        </tr>
        <tr><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td></tr>
        <tr><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td></tr>
        <tr><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td></tr>
        <tr><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td></tr>
        <tr><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td></tr>
        <tr><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td></tr>
        <tr><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td></tr>
        <tr><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td></tr>
        <tr><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td><td class="text-na">N/A</td></tr>
    </tbody>
</table>

<table class="no-border signatures">
    <tr>
        <td class="sig-col" style="vertical-align: top;">
            <div class="sig-box">TI</div>
            <div class="sig-label">DEPENDENCIA</div>

            <div class="sig-box" style="margin-top: 15px;">{{usuario_sistema}}</div>
            <div class="sig-label">NOMBRE Y FIRMA DE QUIEN ENTREGA</div>

            <table class="no-border" style="width: 100%; margin-top: 15px;">
                <tr>
                    <td style="width: 30%; padding: 0 5px 0 0;">
                        <div class="sig-box"></div>
                        <div class="sig-label" style="margin-bottom: 0;">Cod. Personal</div>
                    </td>
                    <td style="width: 70%; padding: 0 0 0 5px;">
                        <div class="sig-box">ANALISTA TIC</div>
                        <div class="sig-label" style="margin-bottom: 0;">Cargo</div>
                    </td>
                </tr>
            </table>
        </td>
        
        <td class="sig-spacer"></td>

        <td class="sig-col" style="vertical-align: top;">
            <div class="sig-box">{{seccional}}</div>
            <div class="sig-label">DEPENDENCIA</div>

            <div class="sig-box" style="margin-top: 15px;">{{nombre_usuario}}</div>
            <div class="sig-label">NOMBRE Y FIRMA DE QUIEN RECIBE</div>

            <table class="no-border" style="width: 100%; margin-top: 15px;">
                <tr>
                    <td style="width: 30%; padding: 0 5px 0 0;">
                        <div class="sig-box"></div>
                        <div class="sig-label" style="margin-bottom: 0;">Cod. Personal</div>
                    </td>
                    <td style="width: 70%; padding: 0 0 0 5px;">
                        <div class="sig-box">{{cargo}}</div>
                        <div class="sig-label" style="margin-bottom: 0;">Cargo</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>';
        
        \App\Models\PlantillaPdf::create([
            'nombre' => 'Acta Oficial FNC',
            'tipo' => 'acta_entrega',
            'contenido' => $html,
            'activa' => true,
            'user_id' => auth()->id() ?? 1
        ]);
        return redirect()->route('plantillas-pdf.index')->with('success', 'Plantilla Oficial FNC instalada y activada. La anterior fue desactivada.');
    });
});

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'force-password-change'])->group(function () {
    // Asignacion Bajo Responsabilidad
    Route::post('equipos/{equipo}/asignacion-responsabilidad', [App\Http\Controllers\AsignacionResponsabilidadController::class, 'store'])->name('equipos.asignacion-responsabilidad.store')->middleware('permission:equipos.crear');
    Route::put('equipos/{equipo}/asignacion-responsabilidad/{asignacion}', [App\Http\Controllers\AsignacionResponsabilidadController::class, 'update'])->name('equipos.asignacion-responsabilidad.update')->middleware('permission:equipos.crear');
    Route::delete('equipos/{equipo}/asignacion-responsabilidad/{asignacion}', [App\Http\Controllers\AsignacionResponsabilidadController::class, 'destroy'])->name('equipos.asignacion-responsabilidad.destroy')->middleware('permission:equipos.crear');
});
