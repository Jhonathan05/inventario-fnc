<?php

namespace App\Http\Controllers;

use App\Exports\EquiposExport;
use App\Http\Requests\EquipoRequest;
use App\Imports\EquiposImport;
use App\Models\Equipo;
use App\Models\Funcionario;
use App\Models\TipoRecurso;
use App\Models\User;
use App\Services\HistorialService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class EquipoController extends Controller
{
    public function __construct(
        private readonly HistorialService $historialService
    ) {}

    /**
     * Listado de equipos con búsqueda y filtros.
     */
    public function index(Request $request)
    {
        // Retener filtros en sesión
        if ($request->has('clear')) {
            session()->forget('equipos_filtros');
            return redirect()->route('equipos.index');
        }

        if (count($request->query()) > 0) {
            session(['equipos_filtros' => $request->query()]);
        } elseif (session()->has('equipos_filtros')) {
            // Filtrar valores nulos o vacíos que Laravel omitiría en la URL, causando un loop infinito
            $filtros = array_filter(session('equipos_filtros'), fn($v) => $v !== null && $v !== '');
            if (count($filtros) > 0) {
                return redirect()->route('equipos.index', $filtros);
            }
        }

        $buscar = trim((string) $request->input('buscar', ''));
        $filtroFuncionario = trim((string) $request->input('funcionario', ''));

        $filtroProyecto = trim((string) $request->input('proyecto', ''));
        $filtroTemporal = trim((string) $request->input('usuario_temporal', ''));
        $filtroEmpresa = trim((string) $request->input('empresa_temporal', ''));
        
        $query = Equipo::select([
                'id',
                'tipo_recurso_id',
                'serial',
                'activo_fijo',
                'placa',
                'marca',
                'modelo',
                'nombre_equipo',
            'responsable_nombre',
            'responsable_cedula',
                'estado_operativo',
                'created_at',
            ])
            ->with([
                'tipoRecurso:id,nombre',
                'usuarioAsignado',
                'asignacionResponsabilidadActiva'
            ])
            ->when($buscar !== '', function ($q) use ($buscar) {
            $palabras = array_filter(explode(' ', trim($buscar)), fn($p) => strlen(trim($p)) > 0);
            $parsedId = null;
            if (preg_match('/^[a-zA-Z0-9]+-(?:[SP])?0*(\d+)$/i', trim($buscar), $matches)) {
                $parsedId = $matches[1];
            }

            foreach ($palabras as $palabra) {
                $termino = '%' . $palabra . '%';
                $q->where(function ($sub) use ($termino, $parsedId) {
                    $sub->where('serial', 'like', $termino)
                        ->orWhere('nombre_equipo', 'like', $termino)
                        ->orWhere('marca', 'like', $termino)
                        ->orWhere('modelo', 'like', $termino)
                        ->orWhere('activo_fijo', 'like', $termino)
                        ->orWhere('placa', 'like', $termino)
                        ->orWhere('estado_operativo', 'like', $termino)
                        ->orWhere('responsable_nombre', 'like', $termino)
                        ->orWhere('responsable_cedula', 'like', $termino)
                        ->orWhereHas('usuarioAsignado', fn($u) => $u->where('nombre', 'like', $termino)->orWhere('cedula', 'like', $termino))
                        ->orWhereHas('tipoRecurso', fn($t) => $t->where('nombre', 'like', $termino))
                        ->orWhereHas('asignacionResponsabilidadActiva', function ($ar) use ($termino) {
                            $ar->where('nombre_usuario', 'like', $termino)
                               ->orWhere('documento', 'like', $termino)
                               ->orWhere('empresa', 'like', $termino)
                               ->orWhere('proyecto', 'like', $termino)
                               ->orWhere('cargo', 'like', $termino)
                               ->orWhere('correo', 'like', $termino);
                        });
                    
                    if ($parsedId !== null) {
                        $sub->orWhere('equipos.id', $parsedId);
                    }
                });
            }
            })
            ->when($request->filled('tipo'), fn($q) => $q->where('tipo_recurso_id', $request->tipo))
            ->when($request->filled('estado'), function ($q) use ($request) {
                if ($request->estado === 'responsabilidad') {
                    $q->whereHas('asignacionesResponsabilidad', function ($q2) {
                        $q2->where('estado', 'activa');
                    });
                } else {
                    $q->where('estado_operativo', $request->estado);
                }
            })
            ->when($filtroFuncionario !== '', fn($q) => $q->whereHas('usuarioAsignado', fn($u) => $u->where('nombre', 'like', '%' . $filtroFuncionario . '%')))
            ->when($filtroProyecto !== '', fn($q) => $q->whereHas('asignacionResponsabilidadActiva', fn($ar) => $ar->where('proyecto', 'like', '%' . $filtroProyecto . '%')))
            ->when($filtroTemporal !== '', fn($q) => $q->whereHas('asignacionResponsabilidadActiva', fn($ar) => $ar->where('nombre_usuario', 'like', '%' . $filtroTemporal . '%')))
            ->when($filtroEmpresa !== '', fn($q) => $q->whereHas('asignacionResponsabilidadActiva', fn($ar) => $ar->where('empresa', 'like', '%' . $filtroEmpresa . '%')))
            ->latest();

        // Cargar campos personalizados que deben mostrarse en la grilla
        $camposDinamicos = \App\Models\CampoPersonalizado::where('modulo', 'equipos')
            ->where('activo', true)
            ->where('mostrar_en_grilla', true)
            ->get();

        $camposDinamicos->each(function($cd) {
            if ($cd->posicion_grilla_despues_de) {
                $cd->posicion_grilla_despues_de = \App\Http\Controllers\CampoPersonalizadoController::mapearCmdbAGrilla($cd->posicion_grilla_despues_de);
            } else {
                $cd->posicion_grilla_despues_de = 'estado_operativo'; // default
            }
        });

        if ($camposDinamicos->isNotEmpty()) {
            $query->with('camposPersonalizadosValores');
        }

        $equipos      = $query->paginate(6)->withQueryString();
        $tipoRecursos = TipoRecurso::select('id', 'nombre')->orderBy('nombre')->get();

        return view('equipos.index', compact('equipos', 'tipoRecursos', 'camposDinamicos'));
    }

    /**
     * Autocompletado en tiempo real para búsqueda de equipos.
     */
    public function searchAutocomplete(Request $request): \Illuminate\Http\JsonResponse
    {
        $buscar = trim((string) $request->input('q', ''));
        if ($buscar === '') {
            return response()->json([]);
        }
        
        $termino = '%' . $buscar . '%';
        $equipos = Equipo::select(['id', 'nombre_equipo', 'serial', 'marca', 'modelo', 'tipo_recurso_id', 'estado_operativo', 'placa', 'responsable_nombre'])
            ->with([
                'tipoRecurso:id,nombre',
                'usuarioAsignado:id,equipo_id,nombre'
            ])
            ->where(function ($sub) use ($termino, $buscar) {
                $parsedId = null;
                if (preg_match('/^[a-zA-Z0-9]+-(?:[SP])?0*(\d+)$/i', trim($buscar), $matches)) {
                    $parsedId = $matches[1];
                }

                $sub->where('serial', 'like', $termino)
                    ->orWhere('nombre_equipo', 'like', $termino)
                    ->orWhere('marca', 'like', $termino)
                    ->orWhere('modelo', 'like', $termino)
                    ->orWhere('activo_fijo', 'like', $termino)
                    ->orWhere('placa', 'like', $termino)
                    ->orWhere('estado_operativo', 'like', $termino)
                    ->orWhereHas('usuarioAsignado', fn($u) => $u->where('nombre', 'like', $termino))
                    ->orWhereHas('tipoRecurso', fn($t) => $t->where('nombre', 'like', $termino))
                    ->orWhereHas('asignacionResponsabilidadActiva', function ($ar) use ($termino) {
                        $ar->where('nombre_usuario', 'like', $termino)
                           ->orWhere('documento', 'like', $termino)
                           ->orWhere('empresa', 'like', $termino)
                           ->orWhere('proyecto', 'like', $termino);
                    });
                
                if ($parsedId !== null) {
                    $sub->orWhere('equipos.id', $parsedId);
                }
            })
            ->limit(10)
            ->get();
            
        return response()->json($equipos);
    }

    /**
     * Formulario de creación.
     */
    public function create(): View
    {
        $tipoRecursos = TipoRecurso::select('id', 'nombre')->orderBy('nombre')->get();
        $camposPersonalizados = \App\Models\CampoPersonalizado::select([
                                    'id',
                                    'nombre',
                                    'descripcion',
                                    'tipo',
                                    'obligatorio',
                                    'orden',
                                ])
                                ->with(['opciones:id,campo_personalizado_id,valor,orden'])
                                ->where('modulo', 'equipos')
                                ->where('activo', true)
                                ->orderBy('orden')->get();
        $catalogoComplementos = \App\Models\CatalogoComplemento::orderBy('nombre')->get();
        return view('equipos.create', compact('tipoRecursos', 'camposPersonalizados', 'catalogoComplementos'));
    }

    /**
     * Guardar nuevo equipo con usuario y periféricos.
     */
    public function store(EquipoRequest $request): RedirectResponse
    {
        $datosEquipo = $request->only([
            'tipo_recurso_id', 'serial', 'activo_fijo', 'placa', 'marca', 'modelo',
            'nombre_equipo', 'estado_operativo', 'razon_estado',
            'procesador', 'ram', 'disco', 'sistema_operativo',
            'fecha_compra', 'fin_garantia', 'tiempo_uso',
            'responsable_cedula', 'responsable_nombre', 'responsable_cargo',
            'responsable_ciudad', 'responsable_area', 'responsable_tipo_recurso',
            'fecha_inicio_responsable', 'fecha_fin_responsable'
        ]);

        // Completar responsable administrativo desde el usuario autenticado
        $usuarioAutenticado = Auth::user();
        if ($usuarioAutenticado) {
            if (empty($datosEquipo['responsable_nombre'])) {
                $datosEquipo['responsable_nombre'] = $usuarioAutenticado->name;
            }

            if (empty($datosEquipo['responsable_cargo'])) {
                $datosEquipo['responsable_cargo'] = $usuarioAutenticado->cargo ?? 'Analista TIC';
            }

            if (empty($datosEquipo['fecha_inicio_responsable'])) {
                $datosEquipo['fecha_inicio_responsable'] = now()->toDateString();
            }
        }

        if ($request->sin_serial_fisico && empty($datosEquipo['serial'])) {
            $datosEquipo['serial'] = 'SIN_SERIAL_' . strtoupper(uniqid());
        }

        // Regla: si no hay funcionario asignado en este flujo, no puede quedar como activo/asignado.
        if (empty($request->usuario_nombre) && empty($request->usuario_cedula)) {
            if (in_array(($datosEquipo['estado_operativo'] ?? null), ['activo', 'asignado'], true)) {
                $datosEquipo['estado_operativo'] = 'disponible';
                $datosEquipo['razon_estado'] = null;
            }
        } else {
            // Si se asigna un funcionario al crear, el equipo queda automáticamente asignado
            $datosEquipo['estado_operativo'] = 'asignado';
            $datosEquipo['razon_estado'] = null;
        }

        $equipo = Equipo::create($datosEquipo);

        $equipo->periferico()->create([
            'telefono' => $request->periferico_telefono,
            'teclado'  => $request->periferico_teclado,
            'mouse'    => $request->periferico_mouse,
            'camara'   => $request->periferico_camara,
        ]);

        if (!empty($request->usuario_nombre) || !empty($request->usuario_cedula)) {
            $upper = fn($v) => $v ? mb_strtoupper((string) $v, 'UTF-8') : null;
            $equipo->usuarioAsignado()->create([
                'nombre' => $upper($request->usuario_nombre),
                'cedula' => trim((string) $request->usuario_cedula),
                'empresa_propietaria' => $upper($request->usuario_empresa_propietaria),
                'dependencia' => $upper($request->usuario_dependencia),
                'fuente_recurso' => $upper($request->usuario_fuente_recurso),
                'empresa_funcionario' => $upper($request->usuario_empresa_funcionario),
                'tipo_vinculacion' => $upper($request->usuario_tipo_vinculacion),
                'shortname' => $upper($request->usuario_shortname),
                'departamento' => $upper($request->usuario_departamento),
                'ciudad' => $upper($request->usuario_ciudad),
                'cargo' => $upper($request->usuario_cargo),
                'area' => $upper($request->usuario_area),
                'piso' => $upper($request->usuario_piso),
                'distrito' => $upper($request->usuario_distrito),
                'seccional' => $upper($request->usuario_seccional),
            ]);
            $this->sincronizarFuncionario($request);
        }

        if ($request->has('campos_personalizados')) {
            foreach ($request->campos_personalizados as $campo_id => $valor) {
                // Si es un array (multiselect), lo guardamos como JSON
                $valorGuardar = is_array($valor) ? json_encode($valor) : $valor;
                $equipo->camposPersonalizadosValores()->create([
                    'campo_personalizado_id' => $campo_id,
                    'valor' => $valorGuardar
                ]);
            }
        }

        // Complementos del Activo
        if ($request->has('complementos')) {
            foreach ($request->complementos as $compData) {
                if (!empty($compData['catalogo_complemento_id']) && !empty($compData['estado'])) {
                    $equipo->complementos()->create([
                        'catalogo_complemento_id' => $compData['catalogo_complemento_id'],
                        'estado'                  => $compData['estado'],
                        'marca'                   => $compData['marca'] ?? null,
                        'modelo'                  => $compData['modelo'] ?? null,
                        'serial'                  => $compData['serial'] ?? null,
                        'observaciones'           => $compData['observaciones'] ?? null,
                        'cantidad'                => $compData['cantidad'] ?? 1,
                        'fecha_registro'          => now()->toDateString(),
                    ]);
                }
            }
        }

        $this->historialService->registrarCambio(
            $equipo,
            'creacion',
            null,
            $equipo->serial,
            "Equipo '{$equipo->nombre_equipo}' registrado en el sistema.",
            Auth::user()
        );

        return redirect()->route('equipos.index')
            ->with('success', 'Equipo registrado correctamente.');
    }

    /**
     * Detalle de un equipo.
     */
    public function show(Equipo $equipo): View
    {
        $equipo->load([
            'tipoRecurso',
            'usuarioAsignado',
            'periferico',
            'checklists',
            'complementos.catalogoComplemento',
            'licenciaAsignaciones.licencia',
            'camposPersonalizadosValores.campoPersonalizado',
            'asignaciones' => fn($q) => $q->latest('fecha_accion')->limit(5),
            'historialTecnico' => fn($q) => $q->latest('fecha_evento')->limit(5),
        ]);
        return view('equipos.show', compact('equipo'));
    }

    /**
     * Formulario de edición.
     */
    public function edit(Equipo $equipo): View
    {
        $equipo->load([
            'usuarioAsignado:id,equipo_id,nombre,cedula,empresa_propietaria,dependencia,fuente_recurso,empresa_funcionario,tipo_vinculacion,shortname,departamento,ciudad,cargo,area,piso,distrito,seccional',
            'periferico:id,equipo_id,telefono,teclado,mouse,camara',
            'complementos.catalogoComplemento',
            'camposPersonalizadosValores:id,entidad_id,campo_personalizado_id,valor',
        ]);
        $tipoRecursos = TipoRecurso::select('id', 'nombre')->orderBy('nombre')->get();
        $catalogoComplementos = \App\Models\CatalogoComplemento::orderBy('nombre')->get();
        $camposPersonalizados = \App\Models\CampoPersonalizado::select([
                                    'id',
                                    'nombre',
                                    'descripcion',
                                    'tipo',
                                    'obligatorio',
                                    'orden',
                                ])
                                ->with(['opciones:id,campo_personalizado_id,valor,orden'])
                                ->where('modulo', 'equipos')
                                ->where('activo', true)
                                ->orderBy('orden')->get();
        return view('equipos.edit', compact('equipo', 'tipoRecursos', 'camposPersonalizados', 'catalogoComplementos'));
    }

    /**
     * Actualizar equipo existente con registro de historial de cambios.
     */
    public function update(EquipoRequest $request, Equipo $equipo): RedirectResponse
    {
        $camposAnteriores = $equipo->only([
            'serial', 'activo_fijo', 'estado_operativo', 'marca', 'modelo',
            'nombre_equipo', 'procesador', 'ram', 'disco', 'sistema_operativo',
            'responsable_cedula', 'responsable_nombre', 'responsable_cargo',
            'responsable_ciudad', 'responsable_area', 'responsable_tipo_recurso',
        ]);

        $datosEquipo = $request->only([
            'tipo_recurso_id', 'serial', 'activo_fijo', 'placa', 'marca', 'modelo',
            'nombre_equipo', 'estado_operativo', 'razon_estado',
            'procesador', 'ram', 'disco', 'sistema_operativo',
            'fecha_compra', 'fin_garantia', 'tiempo_uso',
            'responsable_cedula', 'responsable_nombre', 'responsable_cargo',
            'responsable_ciudad', 'responsable_area', 'responsable_tipo_recurso',
            'fecha_inicio_responsable', 'fecha_fin_responsable'
        ]);

        if ($request->sin_serial_fisico && empty($datosEquipo['serial'])) {
            $datosEquipo['serial'] = 'SIN_SERIAL_' . strtoupper(uniqid());
        }

        // Regla: un activo sin funcionario asignado no puede quedar como activo/asignado.
        if (empty($request->usuario_nombre) && empty($request->usuario_cedula) && !$equipo->usuarioAsignado()->exists() && in_array(($datosEquipo['estado_operativo'] ?? null), ['activo', 'asignado'], true)) {
            $datosEquipo['estado_operativo'] = 'disponible';
            $datosEquipo['razon_estado'] = null;
        } elseif (!empty($request->usuario_nombre) || !empty($request->usuario_cedula)) {
            // Si se está asignando un funcionario en este momento, forzar a estado asignado
            $datosEquipo['estado_operativo'] = 'asignado';
            $datosEquipo['razon_estado'] = null;
        }

        $equipo->update($datosEquipo);

        $camposNuevos = $equipo->fresh()->only(array_keys($camposAnteriores));
        
        // Detectar cambios en responsable_* y registrar UN ÚNICO evento consolidado
        $camposResponsable = [
            'responsable_cedula' => 'Cédula',
            'responsable_nombre' => 'Nombre',
            'responsable_cargo' => 'Cargo',
            'responsable_ciudad' => 'Ciudad',
            'responsable_area' => 'Área',
            'responsable_tipo_recurso' => 'Tipo de Recurso'
        ];
        
        $cambiosResponsable = [];
        foreach ($camposResponsable as $campo => $etiqueta) {
            if (array_key_exists($campo, $camposAnteriores) && 
                array_key_exists($campo, $camposNuevos) && 
                $camposAnteriores[$campo] !== $camposNuevos[$campo]) {
                $valorAnterior = $camposAnteriores[$campo] ?? 'Sin valor';
                $valorNuevo = $camposNuevos[$campo] ?? 'Sin valor';
                $cambiosResponsable[] = "- {$etiqueta}: {$valorAnterior} → {$valorNuevo}";
            }
        }
        
        // Registrar un único evento si hubo cambios en responsable
        if (!empty($cambiosResponsable)) {
            $descripcion = "Cambio de Responsable del Activo.\n\nCampos modificados:\n" . implode("\n", $cambiosResponsable);
            $this->historialService->registrarCambio(
                $equipo,
                'cambio_responsable',
                json_encode($camposAnteriores),
                json_encode($camposNuevos),
                $descripcion,
                Auth::user()
            );
        }
        
        $this->historialService->registrarCambiosCampos(
            $equipo,
            $camposAnteriores,
            $camposNuevos,
            Auth::user()
        );

        $equipo->periferico()->updateOrCreate(
            ['equipo_id' => $equipo->id],
            [
                'telefono' => $request->periferico_telefono,
                'teclado'  => $request->periferico_teclado,
                'mouse'    => $request->periferico_mouse,
                'camara'   => $request->periferico_camara,
            ]
        );

        if ($request->has('campos_personalizados')) {
            foreach ($request->campos_personalizados as $campo_id => $valor) {
                $valorGuardar = is_array($valor) ? json_encode($valor) : $valor;
                $equipo->camposPersonalizadosValores()->updateOrCreate(
                    ['campo_personalizado_id' => $campo_id],
                    ['valor' => $valorGuardar]
                );
            }
        }

        // Sincronizar funcionario en la tabla de funcionarios y UsuarioAsignado
        if (!empty($request->usuario_nombre) || !empty($request->usuario_cedula)) {
            $upper = fn($v) => $v ? mb_strtoupper((string) $v, 'UTF-8') : null;
            $equipo->usuarioAsignado()->updateOrCreate(
                ['equipo_id' => $equipo->id],
                [
                    'nombre' => $upper($request->usuario_nombre),
                    'cedula' => trim((string) $request->usuario_cedula),
                    'empresa_propietaria' => $upper($request->usuario_empresa_propietaria),
                    'dependencia' => $upper($request->usuario_dependencia),
                    'fuente_recurso' => $upper($request->usuario_fuente_recurso),
                    'empresa_funcionario' => $upper($request->usuario_empresa_funcionario),
                    'tipo_vinculacion' => $upper($request->usuario_tipo_vinculacion),
                    'shortname' => $upper($request->usuario_shortname),
                    'departamento' => $upper($request->usuario_departamento),
                    'ciudad' => $upper($request->usuario_ciudad),
                    'cargo' => $upper($request->usuario_cargo),
                    'area' => $upper($request->usuario_area),
                    'piso' => $upper($request->usuario_piso),
                    'distrito' => $upper($request->usuario_distrito),
                    'seccional' => $upper($request->usuario_seccional),
                ]
            );
            $this->sincronizarFuncionario($request);
        } else {
            $equipo->usuarioAsignado()->delete();
        }

        $returnTo = (string) $request->input('return_to', '');
        if (is_string($returnTo) && $returnTo !== '') {
            $path = parse_url($returnTo, PHP_URL_PATH);
            if (is_string($path)) {
                $path = strtolower($path);
                if (str_contains($path, '/equipos') || str_contains($path, '/historial-tecnico')) {
                    return redirect($returnTo)->with('success', 'Equipo actualizado correctamente.');
                }
            }
        }

        return redirect()->route('equipos.index')
            ->with('success', 'Equipo actualizado correctamente.');
    }

    /**
     * Eliminar equipo (soft delete) con registro en historial.
     */
    public function destroy(Equipo $equipo): RedirectResponse
    {
        $this->historialService->registrarCambio(
            $equipo,
            'eliminacion',
            'activo',
            'eliminado',
            "Equipo '{$equipo->nombre_equipo}' eliminado del sistema.",
            Auth::user()
        );

        $equipo->delete();

        return redirect()->route('equipos.index')
            ->with('success', 'Equipo eliminado correctamente.');
    }

    /**
     * Exportar equipos a Excel.
     */
    public function exportar(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $modoExportacion = strtolower((string) $request->input('modo_exportacion', 'personalizada'));
        
        \Log::info('INICIO EXPORTAR:', ['modo' => $modoExportacion, 'url' => $request->fullUrl()]);
        
        $columnasEstandar = array_map('strtolower', (array) $request->input('columnas_estandar', []));
        $columnasPersonalizadas = (array) $request->input('columnas_personalizadas', []);
        $baseCmdbPrincipal = $request->boolean('base_cmdb_principal');

        $nombreArchivo = 'inventario_personalizado_' . date('Ymd_His') . '.xlsx';

        if (in_array($modoExportacion, ['cmdb', 'cmdb_principal'], true)) {
            $columnasEstandar = EquiposExport::columnasCmdbPrincipal();
            // Solo campos que el usuario marcó para participar en Exportación CMDB Y mostrar_en_grilla
            $columnasPersonalizadas = \App\Models\CampoPersonalizado::where('modulo', 'equipos')
                ->where('mostrar_en_grilla', 1)
                ->where('participa_exportacion_cmdb', 1)
                ->pluck('id')
                ->toArray();
                
            \Log::info('Campos CMDB encontrados en Controlador:', [
                'campos' => \App\Models\CampoPersonalizado::whereIn('id', $columnasPersonalizadas)->pluck('nombre')->toArray(),
                'ids' => $columnasPersonalizadas,
                'modoExportacion' => $modoExportacion
            ]);

            $nombreArchivo = 'cmdb_principal_' . date('Ymd_His') . '.xlsx';
        }

        if ($modoExportacion === 'completa') {
            // Se solicitó explícitamente "TODOS los Campos Personalizados" sin importar configuración
            $columnasPersonalizadas = \App\Models\CampoPersonalizado::where('modulo', 'equipos')
                ->pluck('id')
                ->toArray();
            $nombreArchivo = 'inventario_completo_' . date('Ymd_His') . '.xlsx';
            
            return Excel::download(
                new \App\Exports\EquiposExport(['COMPLETA'], $columnasPersonalizadas, $request->all()), 
                $nombreArchivo
            );
        }

        if ($modoExportacion === 'personalizada') {
            // El usuario pidió explícitamente que "si quiero exportar solo una columna no exporte la cmdb principal"
            // Por lo tanto, se respetará ÚNICAMENTE lo que el usuario haya seleccionado en el modal.
            // No se forzará la base CMDB.
            
            $nombreArchivo = 'inventario_personalizado_' . date('Ymd_His') . '.xlsx';
        }

        // Guardar plantilla si se solicita
        if ($modoExportacion === 'personalizada' && $request->input('guardar_plantilla') && $request->filled('nombre_plantilla')) {
            \App\Models\PlantillaExportacion::create([
                'nombre' => $request->nombre_plantilla,
                'modulo' => 'equipos',
                'configuracion_json' => [
                    'columnas_estandar' => $columnasEstandar,
                    'columnas_personalizadas' => $columnasPersonalizadas,
                ],
            ]);
        }

        // Si no se selecciona nada (ej. llamada directa sin modal), exportar todo lo por defecto
        if (empty($columnasEstandar) && empty($columnasPersonalizadas)) {
            $columnasEstandar = $baseCmdbPrincipal
                ? EquiposExport::columnasCmdbPrincipal()
                : EquiposExport::columnasCmdbPorDefecto();
        }

        return Excel::download(new EquiposExport($columnasEstandar, $columnasPersonalizadas, $request->all()), $nombreArchivo);
    }

    /**
     * Formulario de importación desde Excel.
     */
    public function importarForm(): View
    {
        return view('equipos.importar');
    }

    /**
     * Procesar el archivo Excel subido.
     * Detección automática del formato (CMDB / propio).
     */
    public function importar(Request $request): RedirectResponse
    {
        ini_set('memory_limit', '-1');
        set_time_limit(300);

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'archivo.required' => 'Debes seleccionar un archivo Excel.',
            'archivo.file'     => 'El archivo no es válido.',
            'archivo.mimes'    => 'Solo se permiten archivos .xlsx o .xls.',
            'archivo.max'      => 'El archivo no puede superar 10 MB.',
        ]);

        try {
            $responsableInstitucional = $this->obtenerNombreAnalistaTicInstitucional();
        } catch (\RuntimeException $e) {
            return redirect()->route('equipos.importar.form')
                ->withErrors(['archivo' => $e->getMessage()]);
        }

        $filePath = $request->file('archivo')->getRealPath();
        $selector = new \App\Imports\EquiposImportSelector($filePath, $responsableInstitucional);
        Excel::import($selector, $request->file('archivo'));

        $import = $selector->getImport();
        if (!$import) {
            return redirect()->route('equipos.importar.form')
                ->withErrors(['archivo' => 'No se pudo procesar ninguna hoja del archivo.']);
        }

        $rowFailures  = $import->getRowFailures();
        $phpErrors    = $import->errors();
        $insertados   = $import->getInsertados();
        $omitidos     = $import->getOmitidos();
        $metricas     = $import->getMetricas();
        $columnReport = $import->getMapper()->getColumnReport();

        $errorsData = collect($phpErrors)->map(fn($e) => [
            'mensaje' => class_basename(get_class($e)) . ': ' . $e->getMessage(),
        ])->take(50)->toArray();

        $fallosFila = collect($rowFailures)->take(50)->toArray();
        $totalFallos = count($rowFailures);

        if (isset($columnReport['ignoradas']) && is_array($columnReport['ignoradas'])) {
            $columnReport['total_ignoradas'] = count($columnReport['ignoradas']);
            $columnReport['ignoradas'] = array_slice($columnReport['ignoradas'], 0, 50);
        }

        $reportData = [
            'import_insertados'    => $insertados,
            'import_metricas'      => $metricas,
            'import_omitidos'      => $omitidos,
            'import_failures'      => $fallosFila,
            'import_total_fallos'  => $totalFallos,
            'import_errors'        => $errorsData,
            'import_column_report' => $columnReport,
        ];

        $cacheKey = 'import_report_' . (auth()->id() ?? 'guest') . '_' . time();
        \Illuminate\Support\Facades\Cache::put($cacheKey, $reportData, now()->addMinutes(15));

        return redirect()->route('equipos.importar.form')
            ->with('import_cache_key', $cacheKey);
    }

    /**
     * Vista de historial de vida del equipo (timeline combinado).
     */
    public function historialVida(Equipo $equipo, HistorialService $historialService): View
    {
        $eventos = $historialService->obtenerLineaDeTiempo($equipo);
        return view('equipos.historial_vida', compact('equipo', 'eventos'));
    }

    /**
     * Generar Acta de Entrega PDF
     */
    public function descargarActa(Equipo $equipo, \App\Services\PdfService $pdfService)
    {
        $equipo->load(['tipoRecurso', 'usuarioAsignado']);
        
        if (!$equipo->usuarioAsignado) {
            return back()->with('error', 'El equipo no tiene un funcionario asignado actualmente.');
        }

        return $pdfService->generarActaDesdeEquipo($equipo);
    }

    /**
     * Sincroniza el usuario asignado del equipo con la tabla de funcionarios.
     * Busca por cédula; si existe actualiza, si no crea un nuevo registro.
     */
    private function sincronizarFuncionario(Request $request): void
    {
        $cedula = trim((string) $request->usuario_cedula);

        if (empty($cedula)) {
            return;
        }

        $upper = fn($v) => $v ? mb_strtoupper((string) $v, 'UTF-8') : null;

        // Separar nombre completo en nombres y apellidos (por el primer espacio)
        $nombreCompleto = trim((string) $request->usuario_nombre);
        $partes         = explode(' ', $nombreCompleto, 2);
        $nombres        = $upper($partes[0] ?? $nombreCompleto);
        $apellidos      = $upper($partes[1] ?? null);

        $identificacionNormalizada = \App\Services\Importadores\CMDBMapperService::normalizeIdentifier($cedula, true) ?? '';
        $hash = hash_hmac('sha256', $identificacionNormalizada, config('app.key'));

        $funcionario = Funcionario::withTrashed()->updateOrCreate(
            ['identificacion_hash' => $hash],
            [
                'identificacion'      => $cedula,
                'nombres'             => $nombres,
                'apellidos'           => $apellidos,
                'cargo'               => $upper($request->usuario_cargo),
                'area'                => $upper($request->usuario_area),
                'departamento'        => $upper($request->usuario_departamento),
                'ciudad'              => $upper($request->usuario_ciudad),
                'empresa_funcionario' => $upper($request->usuario_empresa_funcionario),
                'tipo_vinculacion'    => $upper($request->usuario_tipo_vinculacion),
                'seccional'           => $upper($request->usuario_seccional),
                'distrito'            => $upper($request->usuario_distrito),
                'estado'              => 'Activo',
            ]
        );
        
        // Si el funcionario estaba eliminado, lo restauramos
        if ($funcionario->trashed()) {
            $funcionario->restore();
        }
    }

    // ── Complementos del Activo ──────────────────────────────────────────────

    public function getComplementosPorTipo(\App\Models\TipoRecurso $tipoRecurso)
    {
        $complementos = $tipoRecurso->complementosDefinidos()->select('catalogo_complementos.id', 'nombre', 'requiere_serial', 'usa_estado', 'cantidad_default')->get();
        return response()->json($complementos);
    }

    public function storeComplemento(Request $request, Equipo $equipo, \App\Services\HistorialService $historialService)
    {
        \Illuminate\Support\Facades\Log::info('Store Complemento Payload:', $request->all());
        $modo = strtolower($request->input('modo_ingreso', 'nuevo'));

        if ($modo === 'existente') {
            $request->validate([
                'complemento_existente_id' => 'required|exists:activo_complementos,id'
            ]);

            $complemento = \App\Models\ActivoComplemento::findOrFail($request->complemento_existente_id);
            
            // Reasignarlo a este equipo
            $complemento->equipo_id = $equipo->id;
            $complemento->estado = 'Asignado'; // Cambiar estado automáticamente a Asignado
            $complemento->save();

            $historialService->registrarCambio(
                $equipo,
                'complemento_agregado',
                null,
                $complemento->id,
                "Complemento existente '{$complemento->nombre}' asignado desde la bolsa global.",
                Auth::user()
            );

            return back()->with('success', 'Complemento existente asignado correctamente.');
        }

        // Modo nuevo
        $request->validate([
            'catalogo_complemento_id' => 'required|exists:catalogo_complementos,id',
            'estado' => 'required|string|max:50',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'serial' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $data = $request->only([
            'catalogo_complemento_id', 'estado', 'marca', 'modelo', 'serial', 'observaciones'
        ]);
        $data['cantidad'] = 1; // Forzar a 1

        $complemento = $equipo->complementos()->create($data);

        $historialService->registrarCambio(
            $equipo,
            'complemento_agregado',
            null,
            $complemento->id,
            "Complemento nuevo '{$complemento->nombre}' agregado.",
            Auth::user()
        );

        return back()->with('success', 'Complemento nuevo registrado correctamente.');
    }

    public function updateComplemento(Request $request, Equipo $equipo, \App\Models\ActivoComplemento $complemento, \App\Services\HistorialService $historialService)
    {
        $request->validate([
            'estado' => 'required|string|max:50',
            'cantidad' => 'required|integer|min:1',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'serial' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $complemento->fill($request->only([
            'estado', 'cantidad', 'marca', 'modelo', 'serial', 'observaciones'
        ]));
        
        if (in_array($complemento->estado, ['Disponible', 'Dañado'])) {
            $complemento->equipo_id = null;
        }

        $complemento->save();

        $historialService->registrarCambio(
            $equipo,
            'complemento_editado',
            null,
            $complemento->id,
            "Complemento '{$complemento->nombre}' actualizado.",
            Auth::user()
        );

        return back()->with('success', 'Complemento actualizado correctamente.');
    }

    public function destroyComplemento(Equipo $equipo, \App\Models\ActivoComplemento $complemento, \App\Services\HistorialService $historialService)
    {
        $nombre = $complemento->nombre;
        $complemento->delete();

        $historialService->registrarCambio(
            $equipo,
            'complemento_eliminado',
            $complemento->id,
            null,
            "Complemento '{$nombre}' eliminado.",
            Auth::user()
        );

        return back()->with('success', 'Complemento eliminado correctamente.');
    }

    public function transferirComplemento(Request $request, Equipo $equipo, \App\Models\ActivoComplemento $complemento, \App\Services\HistorialService $historialService)
    {
        $request->validate([
            'equipo_destino_id' => 'required|exists:equipos,id',
        ]);

        if ($request->equipo_destino_id == $equipo->id) {
            return back()->with('error', 'El activo destino no puede ser el mismo.');
        }

        $equipoDestino = Equipo::findOrFail($request->equipo_destino_id);
        
        $esCompatible = $equipoDestino->tipoRecurso
            ->complementosDefinidos()
            ->where('catalogo_complementos.id', $complemento->catalogo_complemento_id)
            ->exists();

        if (!$esCompatible) {
            return back()->with('error', 'Este complemento no es compatible con el tipo de recurso del equipo destino.');
        }

        $historialService->registrarTransferenciaComplemento(
            $equipo,
            $equipoDestino,
            $complemento,
            Auth::user(),
            $request->observaciones
        );

        $complemento->equipo_id = $equipoDestino->id;
        $complemento->save();

        return back()->with('success', 'Complemento transferido correctamente.');
    }

    /**
     * Obtiene de forma inequívoca el Analista TIC institucional por rol.
     */
    private function obtenerNombreAnalistaTicInstitucional(): string
    {
        $analistas = User::role('Analista Tic')->select('id', 'name')->get();

        if ($analistas->count() !== 1) {
            throw new \RuntimeException(
                'No se puede determinar el Analista TIC institucional. Debe existir exactamente un usuario con rol Analista Tic.'
            );
        }

        return (string) $analistas->first()->name;
    }

    /**
     * Búsqueda por placa para el lector de código de barras.
     * Devuelve JSON con la URL del detalle del equipo encontrado.
     */
    public function buscarPorPlaca(Request $request): JsonResponse
    {
        $placa = trim((string) $request->query('placa', ''));

        if ($placa === '') {
            return response()->json(['found' => false, 'message' => 'No se proporcionó una placa o serial.'], 422);
        }

        // Buscar por placa o por serial
        $equipo = Equipo::where('placa', $placa)
                        ->orWhere('serial', $placa)
                        ->first();

        if (!$equipo) {
            return response()->json([
                'found' => false,
                'placa' => $placa,
                'message' => 'No encontramos ningún activo con la placa o serial: ' . $placa,
            ]);
        }

        return response()->json([
            'found' => true,
            'placa' => $equipo->placa,
            'serial' => $equipo->serial,
            'url' => route('equipos.show', $equipo->id)
        ]);
    }
}
