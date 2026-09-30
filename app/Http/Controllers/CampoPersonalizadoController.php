<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampoPersonalizadoRequest;
use App\Http\Requests\UpdateCampoPersonalizadoRequest;
use App\Models\CampoPersonalizado;
use App\Models\CampoPersonalizadoOpcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class CampoPersonalizadoController extends Controller
{
    public function index()
    {
        $campos = CampoPersonalizado::with('opciones')->orderBy('modulo')->orderBy('orden')->get();
        $columnasCMDB = \App\Exports\EquiposExport::columnasCmdbPrincipalEtiquetas();
        asort($columnasCMDB); // Ordenar alfabéticamente por las etiquetas
        $columnasGrilla = $columnasCMDB; // Utilizar las mismas columnas reales
        return view('campos_personalizados.index', compact('campos', 'columnasCMDB', 'columnasGrilla'));
    }

    public function store(StoreCampoPersonalizadoRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['obligatorio'] = $request->boolean('obligatorio');
            $data['editable'] = $request->boolean('editable', true);
            $data['visible'] = $request->boolean('visible', true);
            $data['importable'] = $request->boolean('importable', true);
            $data['exportable'] = $request->boolean('exportable', true);
            $data['exportar_por_defecto'] = $request->boolean('exportar_por_defecto');
            $data['activo'] = $request->boolean('activo', true);
            $data['mostrar_en_grilla'] = $request->boolean('mostrar_en_grilla');
            $data['participa_exportacion_cmdb'] = $request->boolean('participa_exportacion_cmdb', true);
            $data['participa_exportacion_completa'] = $request->boolean('participa_exportacion_completa', true);
            $data['participa_reportes'] = $request->boolean('participa_reportes', true);
            $data['participa_filtros'] = $request->boolean('participa_filtros', false);

            if (!$request->boolean('mostrar_en_grilla')) {
                $data['posicion_grilla_despues_de'] = null;
            }
            if (!$request->boolean('participa_exportacion_cmdb')) {
                $data['exportar_excel_despues_de'] = null;
            }



            $maxOrden = CampoPersonalizado::where('modulo', $data['modulo'])->max('orden');
            $data['orden'] = $maxOrden !== null ? $maxOrden + 1 : 0;

            $campo = CampoPersonalizado::create($data);

            if (in_array($campo->tipo, ['select', 'multiselect']) && !empty($data['opciones'])) {
                $opciones = array_map('trim', explode(',', $data['opciones']));
                foreach ($opciones as $index => $valor) {
                    if ($valor !== '') {
                        $campo->opciones()->create([
                            'valor' => $valor,
                            'orden' => $index
                        ]);
                    }
                }
            }

            if ($request->boolean('asignar_valor_inicial') && !empty($data['valor_inicial_masivo']) && $campo->modulo === 'equipos' && in_array($campo->tipo, ['select', 'multiselect'])) {
                $valorInicial = trim($data['valor_inicial_masivo']);
                $modo = $request->input('modo_asignacion_masiva', 'solo_vacios');
                
                $query = \App\Models\Equipo::query();
                if ($modo === 'solo_vacios') {
                    $equiposConValor = \App\Models\CampoPersonalizadoValor::where('campo_personalizado_id', $campo->id)
                        ->pluck('entidad_id')->toArray();
                    if (!empty($equiposConValor)) {
                        $query->whereNotIn('id', $equiposConValor);
                    }
                }
                
                $query->chunkById(200, function ($equipos) use ($campo, $valorInicial, $modo) {
                    if ($modo === 'sobrescribir_todos') {
                        \App\Models\CampoPersonalizadoValor::where('campo_personalizado_id', $campo->id)
                            ->whereIn('entidad_id', $equipos->pluck('id')->toArray())
                            ->delete();
                    }
                    $insertData = [];
                    foreach ($equipos as $equipo) {
                        $insertData[] = [
                            'campo_personalizado_id' => $campo->id,
                            'entidad_id' => $equipo->id,
                            'valor' => $valorInicial,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    \App\Models\CampoPersonalizadoValor::insert($insertData);
                });
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'CREAR_CAMPO_PERSONALIZADO',
                'details' => json_encode(['nombre' => $campo->nombre, 'modulo' => $campo->modulo])
            ]);

            DB::commit();
            return redirect()->route('campos-personalizados.index')->with('success', 'Campo personalizado creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al crear el campo: ' . $e->getMessage())->withInput();
        }
    }

    public function update(UpdateCampoPersonalizadoRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $campo = CampoPersonalizado::findOrFail($id);
            $data = $request->validated();
            $data['obligatorio'] = $request->boolean('obligatorio');
            $data['editable'] = $request->boolean('editable', true);
            $data['visible'] = $request->boolean('visible', true);
            $data['importable'] = $request->boolean('importable', true);
            $data['exportable'] = $request->boolean('exportable', true);
            $data['exportar_por_defecto'] = $request->boolean('exportar_por_defecto');
            $data['activo'] = $request->boolean('activo', true);
            $data['mostrar_en_grilla'] = $request->boolean('mostrar_en_grilla');
            $data['participa_exportacion_cmdb'] = $request->boolean('participa_exportacion_cmdb', true);
            $data['participa_exportacion_completa'] = $request->boolean('participa_exportacion_completa', true);
            $data['participa_reportes'] = $request->boolean('participa_reportes', true);
            $data['participa_filtros'] = $request->boolean('participa_filtros', false);

            if (!$request->boolean('mostrar_en_grilla')) {
                $data['posicion_grilla_despues_de'] = null;
            }
            if (!$request->boolean('participa_exportacion_cmdb')) {
                $data['exportar_excel_despues_de'] = null;
            }

            $campo->update($data);

            if (in_array($campo->tipo, ['select', 'multiselect'])) {
                $campo->opciones()->delete();
                if (!empty($data['opciones'])) {
                    $opciones = array_map('trim', explode(',', $data['opciones']));
                    foreach ($opciones as $index => $valor) {
                        if ($valor !== '') {
                            $campo->opciones()->create([
                                'valor' => $valor,
                                'orden' => $index
                            ]);
                        }
                    }
                }
            }

            if ($request->boolean('asignar_valor_inicial') && !empty($data['valor_inicial_masivo']) && $campo->modulo === 'equipos' && in_array($campo->tipo, ['select', 'multiselect'])) {
                $valorInicial = trim($data['valor_inicial_masivo']);
                $modo = $request->input('modo_asignacion_masiva', 'solo_vacios');
                
                $query = \App\Models\Equipo::query();
                if ($modo === 'solo_vacios') {
                    $equiposConValor = \App\Models\CampoPersonalizadoValor::where('campo_personalizado_id', $campo->id)
                        ->pluck('entidad_id')->toArray();
                    if (!empty($equiposConValor)) {
                        $query->whereNotIn('id', $equiposConValor);
                    }
                }
                
                $query->chunkById(200, function ($equipos) use ($campo, $valorInicial, $modo) {
                    if ($modo === 'sobrescribir_todos') {
                        \App\Models\CampoPersonalizadoValor::where('campo_personalizado_id', $campo->id)
                            ->whereIn('entidad_id', $equipos->pluck('id')->toArray())
                            ->delete();
                    }
                    $insertData = [];
                    foreach ($equipos as $equipo) {
                        $insertData[] = [
                            'campo_personalizado_id' => $campo->id,
                            'entidad_id' => $equipo->id,
                            'valor' => $valorInicial,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    \App\Models\CampoPersonalizadoValor::insert($insertData);
                });
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'ACTUALIZAR_CAMPO_PERSONALIZADO',
                'details' => json_encode(['nombre' => $campo->nombre, 'modulo' => $campo->modulo])
            ]);

            DB::commit();
            return redirect()->route('campos-personalizados.index')->with('success', 'Campo personalizado actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al actualizar el campo: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $campo = CampoPersonalizado::findOrFail($id);
        $campo->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'ELIMINAR_CAMPO_PERSONALIZADO',
            'details' => json_encode(['nombre' => $campo->nombre, 'modulo' => $campo->modulo])
        ]);

        return redirect()->route('campos-personalizados.index')->with('success', 'Campo personalizado eliminado exitosamente.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:campos_personalizados,id',
            'order.*.orden' => 'required|integer',
        ]);

        foreach ($request->order as $item) {
            CampoPersonalizado::where('id', $item['id'])->update(['orden' => $item['orden']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mapea una clave de columna CMDB (Excel) a su posición equivalente en la grilla web.
     * Las columnas de la grilla web son: id, equipo, activo_fijo, tipo_recurso_id, marca, responsable_cedula, funcionario_asignado, estado_operativo
     */
    public static function mapearCmdbAGrilla(string $cmdbKey): string
    {
        $mapa = [
            // Columnas que van después de #
            'cmdb_empresa_propietario_equipo' => 'id',
            'cmdb_dependencia' => 'id',
            'cmdb_fuente_recurso' => 'id',
            // Columnas de funcionario -> después de equipo
            'cmdb_empresa_funcionario' => 'equipo',
            'cmdb_empleado_contratista' => 'equipo',
            'cmdb_cedula_funcionario' => 'equipo',
            'cmdb_shortname' => 'equipo',
            'cmdb_nombres_apellidos' => 'equipo',
            'cmdb_departamento' => 'equipo',
            'cmdb_ciudad' => 'equipo',
            'cmdb_cargo' => 'equipo',
            'cmdb_area' => 'equipo',
            'cmdb_ubicacion_piso' => 'equipo',
            // Tipo
            'cmdb_tipo_recurso' => 'tipo_recurso_id',
            'cmdb_tipo' => 'tipo_recurso_id',
            // Serial / placa
            'cmdb_serial' => 'activo_fijo',
            'cmdb_placa' => 'activo_fijo',
            // Marca / Modelo
            'cmdb_marca' => 'marca',
            'cmdb_modelo' => 'marca',
            'cmdb_marca_equipo' => 'marca',
            // Nombre equipo y estado
            'cmdb_nombre_equipo' => 'marca',
            'cmdb_estado_operativo' => 'estado_operativo',
            'cmdb_razon_estado' => 'estado_operativo',
            // Hardware/specs -> al final (después de estado)
            'cmdb_administrador_controlado' => 'estado_operativo',
            'cmdb_procesador' => 'estado_operativo',
            'cmdb_memoria_ram' => 'estado_operativo',
            'cmdb_tamano_disco_duro' => 'estado_operativo',
            'cmdb_sistema_operativo' => 'estado_operativo',
            'cmdb_fecha_compra' => 'estado_operativo',
            'cmdb_fin_garantia' => 'estado_operativo',
            'cmdb_tiempo_uso_anos' => 'estado_operativo',
            'cmdb_tipo_propiedad' => 'estado_operativo',
            'cmdb_checklist_responsable_ti' => 'estado_operativo',
            'cmdb_orden_remision' => 'estado_operativo',
            'cmdb_observaciones' => 'estado_operativo',
            'cmdb_cruce_at' => 'estado_operativo',
            'cmdb_cruce_sistema' => 'estado_operativo',
            'cmdb_resultado_cruce_at' => 'estado_operativo',
            'cmdb_tipo_aprobado' => 'estado_operativo',
            'cmdb_fnc' => 'estado_operativo',
            'cmdb_version_windows' => 'estado_operativo',
        ];

        return $mapa[$cmdbKey] ?? 'estado_operativo';
    }
}
