<?php

namespace App\Imports;

use App\Models\Equipo;
use App\Models\TipoRecurso;
use App\Models\UsuarioAsignado;
use App\Models\AsignacionResponsabilidad;
use App\Services\Importadores\CMDBMapperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * EquiposImport — Importador de equipos desde Excel.
 *
 * Delega toda la lógica de mapeo de columnas al CMDBMapperService.
 * Soporta automáticamente el formato propio del sistema y el CMDB corporativo.
 *
 * Cada fila del Excel se convierte en:
 *   1 Equipo  +  1 UsuarioAsignado
 *
 * Transacciones DB por fila. Errores individuales NO detienen la importación.
 */
class EquiposImport implements ToModel, WithStartRow, WithChunkReading, SkipsOnError
{
    use SkipsErrors;

    // ── Tipos que se ignoran silenciosamente ──────────────────────────────────
    private const TIPOS_PERIFERICO = ['telefono', 'teclado', 'mouse', 'mause', 'camara'];

    // ── Estado interno ───────────────────────────────────────────────────────

    private CMDBMapperService $mapper;
    private int   $insertados  = 0;
    private int   $actualizados = 0;
    private int   $procesados = 0;
    private int   $funcionariosCreados = 0;
    private int   $funcionariosActualizados = 0;
    private int   $asignacionesCreadas = 0;
    private int   $asignacionesActualizadas = 0;
    private int   $responsabilidadesCreadas = 0;
    private int   $responsabilidadesActualizadas = 0;
    private int   $omitidos    = 0;
    private int   $currentRow  = 1;
    private array $rowFailures = [];
    private array $rawHeaders  = [];
    private int   $detectedHeadingRow = 1;
    private string $responsableInstitucional;
    private ?string $sheetName;

    // ── Constructor ──────────────────────────────────────────────────────────

    public function __construct(string $filePath, string $responsableInstitucional, ?string $sheetName = null)
    {
        $this->mapper = new CMDBMapperService();
        $this->responsableInstitucional = $responsableInstitucional;
        $this->sheetName = $sheetName;
        $this->detectHeadingRow($filePath);
    }
    
    public function __destruct()
    {
        Log::info("AUDITORIA IMPORTACIÓN - 4. Cantidad total de filas procesadas: " . ($this->currentRow - 1));
    }

    /**
     * Detección dinámica de la fila de encabezados.
     * Lee las primeras 5 filas del Excel y busca las palabras clave.
     */
    private function detectHeadingRow(string $filePath): void
    {
        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            
            if ($this->sheetName) {
                $sheet = $spreadsheet->getSheetByName($this->sheetName);
            } else {
                $sheet = $spreadsheet->getActiveSheet();
            }
            
            for ($row = 1; $row <= 5; $row++) {
                $cellIterator = $sheet->getRowIterator($row, $row)->current()->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $score = 0;
                $tempRaw = [];
                
                foreach ($cellIterator as $cell) {
                    $valRaw = $cell->getValue();
                    $val = strtolower(trim((string) $valRaw));
                    if ($valRaw !== null && $valRaw !== '') {
                        // Cell no expone un índice numérico directo en la versión instalada.
                        // getColumn() devuelve la letra real (A, B, ..., AA), que
                        // se convierte a índice numérico sin perder celdas vacías.
                        $columnIndex = Coordinate::columnIndexFromString($cell->getColumn()) - 1;
                        $tempRaw[$columnIndex] = $valRaw;
                    }
                    if ($val) {
                        // Puntaje si contiene palabras clave del CMDB o del sistema
                        if (preg_match('/serial|marca|modelo|tipo.*recurso|estado|funcionario|responsable|cedula|identificaci|nombre.*equipo/i', $val)) {
                            $score++;
                        }
                    }
                }
                
                // Si encontramos al menos 3 encabezados clave, esta es nuestra fila
                if ($score >= 3) {
                    $this->detectedHeadingRow = $row;
                    $this->currentRow = $row;
                    $this->rawHeaders = $tempRaw;
                    Log::info("IMPORT: Fila de encabezados detectada dinámicamente en la fila {$row}");
                    return;
                }
            }
        } catch (\Exception $e) {
            Log::warning("IMPORT: Error al detectar fila de encabezados: " . $e->getMessage());
        }
    }

    /**
     * Devuelve la fila dinámica detectada.
     */
    public function headingRow(): int
    {
        return $this->detectedHeadingRow;
    }

    /**
     * Tamaño de chunk para lectura eficiente en memoria.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return $this->detectedHeadingRow + 1;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  PROCESAMIENTO PRINCIPAL
    // ══════════════════════════════════════════════════════════════════════════

    public function model(array $numericRow): ?Equipo
    {
        // 1. Convertir la fila numérica en un array asociativo usando rawHeaders y manejando duplicados
        $row = [];
        $headerCounts = [];
        
        // Evitar error si la fila numérica es más larga que los headers detectados
        $lastHeaderIndex = empty($this->rawHeaders) ? -1 : max(array_keys($this->rawHeaders));
        $maxIndex = max($lastHeaderIndex + 1, count($numericRow));
        
        for ($index = 0; $index < $maxIndex; $index++) {
            $rawHeader = $this->rawHeaders[$index] ?? strval($index);
            $slug = \Illuminate\Support\Str::slug($rawHeader, '_');
            
            if ($slug === '') {
                $slug = 'columna_' . $index;
            }

            if (isset($headerCounts[$slug])) {
                $headerCounts[$slug]++;
                $slug = $slug . '_' . $headerCounts[$slug];
            } else {
                $headerCounts[$slug] = 0;
            }
            
            $row[$slug] = $numericRow[$index] ?? null;
        }

        $this->currentRow++;
        $this->procesados++;

        // Inicializar el mapper con la primera fila de datos (una sola vez)
        if (!$this->mapper->isInitialized()) {
            Log::info("=========================================================================");
            Log::info("AUDITORIA IMPORTACIÓN - INICIO DE DIAGNÓSTICO");
            Log::info("=========================================================================");
            Log::info("1. Fila utilizada como encabezado: " . $this->headingRow());
            Log::info("2. Lista completa de columnas detectadas (normalizadas): " . json_encode(array_keys($row), JSON_UNESCAPED_UNICODE));
            Log::info("3. Nombre exacto de cada encabezado (sin normalizar): " . json_encode($this->rawHeaders, JSON_UNESCAPED_UNICODE));
            Log::info("5. Primera fila de datos leída: " . json_encode($row, JSON_UNESCAPED_UNICODE));
            
            $this->mapper->initialize($row);
            
            if ($this->mapper->getDetectedFormat() === CMDBMapperService::FORMAT_UNKNOWN) {
                throw new \Exception('La hoja seleccionada no contiene las columnas requeridas (serial, marca, modelo, etc.). Por favor revisa el archivo Excel.');
            }
            
            Log::info("6. Valores extraídos de la primera fila:");
            Log::info("   - nombre: " . $this->mapper->getOrDefault($row, 'nombre_usuario'));
            Log::info("   - cedula: " . $this->mapper->getOrDefault($row, 'cedula'));
            Log::info("   - serial: " . $this->mapper->get($row, 'serial'));
            Log::info("   - marca: " . $this->mapper->getOrDefault($row, 'marca'));
            Log::info("   - modelo: " . $this->mapper->getOrDefault($row, 'modelo'));
            Log::info("   - nombre_equipo: " . $this->mapper->getOrDefault($row, 'nombre_equipo'));
            Log::info("=========================================================================");
        }

        // Ignorar filas completamente vacías
        if ($this->filaVacia($row)) {
            $this->procesados--;
            return null;
        }

        $tipoNombre = $this->mapper->get($row, 'tipo_recurso');
        $serial     = $this->mapper->get($row, 'serial');

        // Periféricos → omitir sin error
        if ($tipoNombre !== null && $this->esPeriferico($tipoNombre)) {
            $this->omitidos++;
            return null;
        }

        try {
            $equipoEraNuevo = false;
            $equipo = DB::transaction(function () use ($row, $tipoNombre, $serial, &$equipoEraNuevo) {

                // 1. Resolver tipo_recurso
                $tipoId = $this->resolverTipoRecurso($tipoNombre);
                $tipoModelo = \App\Models\TipoRecurso::find($tipoId);
                $visibles = \App\Services\ConfiguracionActivosService::getCamposVisibles($tipoModelo->prefijo);

                // 2. Resolver identificadores estables del activo.
                $serialFinal = CMDBMapperService::normalizeIdentifier($this->resolverSerial($serial)) ?? '';
                $activoFijo = CMDBMapperService::normalizeIdentifier($this->mapper->get($row, 'activo_fijo'));
                $placa = CMDBMapperService::normalizeIdentifier($this->mapper->get($row, 'placa'));
                if ($serialFinal === '' && !$activoFijo && !$placa) {
                    throw new \InvalidArgumentException('No existe serial, placa ni activo fijo para identificar el activo.');
                }
                if ($serialFinal === '') {
                    $serialFinal = 'SIN_SERIAL_' . strtoupper(substr(sha1((string) ($activoFijo ?: $placa)), 0, 16));
                }

                // 3. El responsable institucional no representa ni al funcionario
                // asignado ni a una responsabilidad de la fila CMDB.
                $responsableNombre = $this->mapper->get($row, 'responsable_nombre');

                $marca = $this->mapper->getOrDefault($row, 'marca');
                $modelo = $this->mapper->getOrDefault($row, 'modelo');
                $nombreEquipo = $this->mapper->getOrDefault($row, 'nombre_equipo');

                // Si no requiere nombre de equipo (ej. Cajón), auto-generarlo semánticamente para DB
                if (!in_array('nombre_equipo', $visibles) && empty($nombreEquipo)) {
                    $nombreEquipo = mb_strtoupper(trim($tipoModelo->nombre . ' ' . $marca . ' ' . $modelo));
                }

                // 4. Resolver el activo por serial; si no existe serial, usar placa.
                $equipo = $this->buscarEquipo($activoFijo, $placa, $serialFinal);
                $datosEquipo = [
                        'serial'            => $serialFinal !== '' ? $serialFinal : null,
                        'activo_fijo'       => $activoFijo,
                        'tipo_recurso_id'   => $tipoId,
                        'placa'             => $placa,
                        'marca'             => $marca,
                        'modelo'            => $modelo,
                        'nombre_equipo'     => $nombreEquipo,
                        'estado_operativo'  => $this->mapearEstadoOperativo($this->mapper->get($row, 'estado_operativo')),
                        'razon_estado'      => $this->mapper->get($row, 'razon_estado'),
                        // Filtrado estricto por matriz
                        'procesador'        => in_array('procesador', $visibles) ? $this->mapper->get($row, 'procesador') : null,
                        'ram'               => in_array('ram', $visibles) ? $this->mapper->get($row, 'ram') : null,
                        'disco'             => in_array('disco', $visibles) ? $this->mapper->get($row, 'disco') : null,
                        'sistema_operativo' => in_array('sistema_operativo', $visibles) ? $this->mapper->get($row, 'sistema_operativo') : null,
                        'fecha_compra'      => in_array('fecha_compra', $visibles) ? $this->mapper->getDate($row, 'fecha_compra') : null,
                        'fin_garantia'      => in_array('fin_garantia', $visibles) ? $this->mapper->getDate($row, 'fin_garantia') : null,
                        'tiempo_uso'        => in_array('tiempo_uso', $visibles) ? $this->mapper->get($row, 'tiempo_uso') : null,
                        // Responsable
                        'responsable_cedula'=> $this->mapper->get($row, 'responsable_cedula'),
                        'responsable_nombre'=> $responsableNombre,
                        'responsable_cargo' => $this->mapper->get($row, 'responsable_cargo'),
                        'responsable_ciudad'=> $this->mapper->get($row, 'responsable_ciudad'),
                        'responsable_area'  => $this->mapper->get($row, 'responsable_area'),
                        'responsable_tipo_recurso'=> $this->mapper->get($row, 'responsable_tipo_recurso'),
                        'fecha_inicio_responsable'=> $this->mapper->getDate($row, 'fecha_inicio_responsable'),
                        'fecha_fin_responsable'   => $this->mapper->getDate($row, 'fecha_fin_responsable'),
                        'deleted_at'        => null,
                    ];
                if ($equipo) {
                    // Una plantilla parcial no debe borrar información que ya existe.
                    $datosActualizacion = array_filter($datosEquipo, static fn ($valor) => $valor !== null);
                    foreach ([
                        'serial' => 'serial', 'activo_fijo' => 'activo_fijo', 'placa' => 'placa',
                        'tipo_recurso_id' => 'tipo_recurso', 'marca' => 'marca', 'modelo' => 'modelo',
                        'nombre_equipo' => 'nombre_equipo', 'estado_operativo' => 'estado_operativo',
                        'razon_estado' => 'razon_estado', 'procesador' => 'procesador', 'ram' => 'ram',
                        'disco' => 'disco', 'sistema_operativo' => 'sistema_operativo',
                        'fecha_compra' => 'fecha_compra', 'fin_garantia' => 'fin_garantia',
                        'tiempo_uso' => 'tiempo_uso', 'responsable_cedula' => 'responsable_cedula',
                        'responsable_nombre' => 'responsable_nombre', 'responsable_cargo' => 'responsable_cargo',
                        'responsable_ciudad' => 'responsable_ciudad', 'responsable_area' => 'responsable_area',
                        'responsable_tipo_recurso' => 'responsable_tipo_recurso',
                        'fecha_inicio_responsable' => 'fecha_inicio_responsable',
                        'fecha_fin_responsable' => 'fecha_fin_responsable',
                    ] as $campo => $interno) {
                        if (!$this->mapper->has($interno)) unset($datosActualizacion[$campo]);
                    }
                    if (!$serial) unset($datosActualizacion['serial']);
                    $equipo->update($datosActualizacion);
                    Log::info('CMDB_IMPORT_EQUIPMENT_UPDATE', ['row' => $this->currentRow, 'equipo_id' => $equipo->id]);
                    if ($equipo->trashed()) $equipo->restore();
                } else {
                    $datosEquipo['serial'] = $serialFinal;
                    $equipo = Equipo::create($datosEquipo);
                    $equipoEraNuevo = true;
                    Log::info('CMDB_IMPORT_EQUIPMENT_CREATE', ['row' => $this->currentRow, 'equipo_id' => $equipo->id]);
                }

                // 5. Guardar campos personalizados dinámicamente
                $customFields = $this->mapper->getCustomFields($row);
                foreach ($customFields as $campoId => $valor) {
                    if ($valor !== null) {
                        $equipo->camposPersonalizadosValores()->updateOrCreate(
                            ['campo_personalizado_id' => $campoId],
                            ['valor' => $valor]
                        );
                    }
                }

                // 6. Resolver funcionario y modalidad antes de persistir la relación.
                //
                // REGLA FUNDAMENTAL: El funcionario del CMDB (cedula/nombre_usuario)
                // siempre es el usuario asignado al equipo. La columna razon_del_estado
                // indica la MODALIDAD de esa asignación:
                //   - "ASIGNACIÓN NORMAL" → solo UsuarioAsignado
                //   - "ASIGNACIÓN RESPONSABLE" → UsuarioAsignado + AsignacionResponsabilidad
                //
                // La AsignacionResponsabilidad es una capa ADICIONAL (como en la UI),
                // NO un reemplazo de la asignación normal.
                $esResponsabilidad = $this->esResponsabilidad($row);
                $cedula = $this->mapper->get($row, 'cedula');
                $nombre = $this->mapper->get($row, 'nombre_usuario');

                $placeholders = ['SIN ASIGNAR', 'PENDIENTE', 'N/A', 'NA', 'NO APLICA', 'NULL', '-'];
                $nombreNormalizado = strtoupper(trim((string) $nombre));
                // Normalización estricta de la cédula: eliminar espacios, puntos, guiones
                $cedulaNormalizada = CMDBMapperService::normalizeIdentifier($cedula, true) ?? '';
                
                $tieneFuncionarioValido =
                    !empty($nombreNormalizado) &&
                    !empty($cedulaNormalizada) &&
                    !in_array($nombreNormalizado, $placeholders, true) &&
                    !in_array($cedulaNormalizada, $placeholders, true);

                if ($tieneFuncionarioValido) {
                    // 6a. SIEMPRE crear/actualizar UsuarioAsignado con el funcionario del CMDB.
                    // El funcionario listado en el CMDB ES el usuario del equipo,
                    // independientemente de la modalidad de asignación.
                    $asignadoExistente = UsuarioAsignado::where('equipo_id', $equipo->id)->exists();
                    UsuarioAsignado::updateOrCreate(
                        ['equipo_id' => $equipo->id],
                        [
                            'nombre'              => $nombre,
                            'cedula'              => $cedulaNormalizada,
                            'empresa_propietaria' => $this->mapper->get($row, 'empresa_propietaria'),
                            'dependencia'         => $this->mapper->get($row, 'dependencia'),
                            'fuente_recurso'      => $this->mapper->get($row, 'fuente_recurso'),
                            'empresa_funcionario' => $this->mapper->get($row, 'empresa_funcionario'),
                            'tipo_vinculacion'    => $this->mapper->get($row, 'tipo_vinculacion'),
                            'shortname'           => $this->mapper->get($row, 'shortname'),
                            'departamento'        => $this->mapper->get($row, 'departamento'),
                            'ciudad'              => $this->mapper->get($row, 'ciudad'),
                            'cargo'               => $this->mapper->get($row, 'cargo'),
                            'area'                => $this->mapper->get($row, 'area'),
                            'piso'                => $this->mapper->get($row, 'piso'),
                            'distrito'            => $this->mapper->get($row, 'distrito'),
                            'seccional'           => $this->mapper->get($row, 'seccional'),
                        ]
                    );
                    if ($asignadoExistente) $this->asignacionesActualizadas++;
                    else $this->asignacionesCreadas++;

                    $equipo->update(['estado_operativo' => 'asignado']);

                    // 6b. Sincronizar con el catálogo de Funcionarios
                    $hash = hash_hmac('sha256', $cedulaNormalizada, config('app.key'));
                    $funcionarioAnterior = \App\Models\Funcionario::withTrashed()->where('identificacion_hash', $hash)->first();
                    $funcionarioExistente = (bool) $funcionarioAnterior;
                    $funcionario = \App\Models\Funcionario::withTrashed()->updateOrCreate(
                        ['identificacion_hash' => $hash],
                        [
                            'identificacion' => $cedulaNormalizada,
                            'nombres' => $nombre,
                            'apellidos' => '',
                            'cargo' => $this->mapper->get($row, 'cargo'),
                            'area' => $this->mapper->get($row, 'area'),
                            'departamento' => $this->mapper->get($row, 'departamento'),
                            'ciudad' => $this->mapper->get($row, 'ciudad'),
                            'seccional' => $this->mapper->get($row, 'seccional'),
                            'distrito' => $this->mapper->get($row, 'distrito'),
                            'empresa_funcionario' => $this->mapper->get($row, 'empresa_funcionario'),
                            'tipo_vinculacion' => $this->mapper->get($row, 'tipo_vinculacion'),
                            'estado' => 'Activo',
                            'deleted_at' => null,
                        ]
                    );
                    if ($funcionario->trashed()) $funcionario->restore();
                    if ($funcionarioAnterior) {
                        $preservar = [];
                        foreach (['nombres' => 'nombre_usuario', 'apellidos' => null, 'cargo' => 'cargo', 'area' => 'area', 'departamento' => 'departamento', 'ciudad' => 'ciudad', 'seccional' => 'seccional', 'distrito' => 'distrito', 'empresa_funcionario' => 'empresa_funcionario', 'tipo_vinculacion' => 'tipo_vinculacion'] as $campo => $interno) {
                            if (($interno === null || !$this->mapper->has($interno)) && $funcionarioAnterior->{$campo} !== null) $preservar[$campo] = $funcionarioAnterior->{$campo};
                        }
                        if ($preservar) $funcionario->update($preservar);
                    }
                    if ($funcionarioExistente) $this->funcionariosActualizados++;
                    else $this->funcionariosCreados++;

                    // 6c. Gestionar la modalidad de responsabilidad.
                    if ($esResponsabilidad) {
                        // El responsable administrativo se toma de columnas dedicadas
                        $respNombre = $this->mapper->get($row, 'responsable_nombre');
                        $respCedula = $this->mapper->get($row, 'responsable_cedula');
                        
                        // Normalizamos la cédula del responsable si existe
                        $respCedulaNormalizada = $respCedula ? CMDBMapperService::normalizeIdentifier($respCedula, true) : null;

                        // Solo creamos la responsabilidad real si hay un responsable distinto al usuario
                        // O si no hay usuario asignado normal. 
                        // Si el usuario es el mismo responsable (ej. empleado FNC con su propio equipo), 
                        // es una asignación normal pura y se ignora el flag del CMDB.
                        $esResponsabilidadReal = false;
                        if (!empty($respCedulaNormalizada) && $respCedulaNormalizada !== $cedulaNormalizada) {
                            $esResponsabilidadReal = true;
                        } elseif (!empty($responsableNombre) && empty($respCedulaNormalizada)) {
                            // Si tenemos un responsable institucional por defecto y queremos usarlo
                            // para contratistas temporales, validamos si la fila amerita.
                            // Pero para evitar falsos positivos con empleados normales marcados como
                            // "ASIGNACION RESPONSABLE", preferimos no crearla a menos que haya columnas claras.
                            // En este flujo, si el CMDB dice "Responsabilidad" pero no provee un responsable
                            // distinto, lo tratamos como asignación normal.
                            $esResponsabilidadReal = false;
                        }

                        if ($esResponsabilidadReal) {
                            $datosResponsabilidad = [
                                'nombre_usuario' => $nombre,
                                'documento' => $cedulaNormalizada,
                                'responsable_nombre' => $respNombre ?: $responsableNombre,
                                'responsable_cedula' => $respCedula,
                                'empresa' => $this->mapper->get($row, 'empresa_funcionario'),
                                'cargo' => $this->mapper->get($row, 'responsable_cargo') ?: $this->mapper->get($row, 'cargo'),
                                'area' => $this->mapper->get($row, 'responsable_area') ?: $this->mapper->get($row, 'area'),
                                'fecha_inicio' => $this->mapper->getDate($row, 'fecha_inicio_responsable') ?: now()->toDateString(),
                                'fecha_final_estimada' => $this->mapper->getDate($row, 'fecha_fin_responsable'),
                                'estado' => 'activa',
                            ];
                            $responsabilidad = $equipo->asignacionesResponsabilidad()->where('estado', 'activa')->first();
                            if ($responsabilidad) {
                                $responsabilidad->update($datosResponsabilidad);
                                $this->responsabilidadesActualizadas++;
                            } else {
                                $equipo->asignacionesResponsabilidad()->create($datosResponsabilidad);
                                $this->responsabilidadesCreadas++;
                            }
                            Log::info('CMDB_IMPORT_RESPONSIBILITY', ['row' => $this->currentRow, 'equipo_id' => $equipo->id, 'funcionario' => $cedulaNormalizada]);
                        } else {
                            // Se asume como asignación normal, cerramos responsabilidades activas
                            AsignacionResponsabilidad::where('equipo_id', $equipo->id)
                                ->where('estado', 'activa')
                                ->update([
                                    'estado' => 'finalizada',
                                    'fecha_final_real' => now()->toDateString(),
                                    'motivo_finalizacion' => 'Sincronización CMDB: asignación normal (falsa responsabilidad)',
                                ]);
                        }
                    } else {
                        // Asignación normal: cerrar cualquier responsabilidad activa
                        // anterior de importaciones previas.
                        AsignacionResponsabilidad::where('equipo_id', $equipo->id)
                            ->where('estado', 'activa')
                            ->update([
                                'estado' => 'finalizada',
                                'fecha_final_real' => now()->toDateString(),
                                'motivo_finalizacion' => 'Sincronización CMDB: asignación normal',
                            ]);
                    }
                } else {
                    // Sin funcionario válido: liberar asignación y cerrar responsabilidades.
                    UsuarioAsignado::where('equipo_id', $equipo->id)->delete();
                    AsignacionResponsabilidad::where('equipo_id', $equipo->id)
                        ->where('estado', 'activa')
                        ->update([
                            'estado' => 'finalizada',
                            'fecha_final_real' => now()->toDateString(),
                            'motivo_finalizacion' => 'Sincronización CMDB: sin funcionario',
                        ]);
                }

                return $equipo;
            });

            if ($equipoEraNuevo) $this->insertados++;
            else $this->actualizados++;

            return $equipo;

        } catch (\Exception $e) {
            $this->rowFailures[] = [
                'fila'    => $this->currentRow,
                'activo'  => $this->mapper->get($row, 'activo_fijo') ?: $this->mapper->get($row, 'placa') ?: $this->mapper->get($row, 'serial'),
                'funcionario' => $this->mapper->get($row, 'cedula') ?: $this->mapper->get($row, 'nombre_usuario'),
                'errores' => ['Error interno: ' . $e->getMessage()],
            ];
            Log::error("Import excepción fila {$this->currentRow}", ['msg' => $e->getMessage()]);
            return null;
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  GETTERS PARA EL CONTROLADOR
    // ══════════════════════════════════════════════════════════════════════════

    public function getInsertados(): int           { return $this->insertados; }
    public function getActualizados(): int         { return $this->actualizados; }
    public function getProcesados(): int           { return $this->procesados; }
    public function getMetricas(): array { return [
        'procesados' => $this->procesados, 'creados' => $this->insertados,
        'actualizados' => $this->actualizados,
        'funcionarios_creados' => $this->funcionariosCreados,
        'funcionarios_actualizados' => $this->funcionariosActualizados,
        'asignaciones_creadas' => $this->asignacionesCreadas,
        'asignaciones_actualizadas' => $this->asignacionesActualizadas,
        'responsabilidades_creadas' => $this->responsabilidadesCreadas,
        'responsabilidades_actualizadas' => $this->responsabilidadesActualizadas,
    ]; }
    public function getOmitidos(): int             { return $this->omitidos; }
    public function getRowFailures(): array        { return $this->rowFailures; }
    public function getMapper(): CMDBMapperService { return $this->mapper; }

    private function esResponsabilidad(array $row): bool
    {
        return $this->mapper->isResponsibilityRow($row);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  HELPERS INTERNOS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Buscar o crear el tipo de recurso por nombre.
     * Si no se proporcionó, usa un default genérico.
     */
    private function resolverTipoRecurso(?string $nombre): int
    {
        if ($nombre) {
            return TipoRecurso::firstOrCreate(['nombre' => $nombre])->id;
        }

        // Buscar o crear un tipo por defecto
        return TipoRecurso::firstOrCreate(['nombre' => 'SIN CLASIFICAR'])->id;
    }

    /**
     * Resuelve el serial: si es vacío, N/A, PENDIENTE, etc. → autogenerar.
     */
    private function resolverSerial(?string $serial): string
    {
        // Limpiar espacios regulares, espacios irrompibles (NBSP) y caracteres de control
        $s = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', (string) $serial);
        $sUpper = strtoupper($s);

        $invalidos = ['', 'NO TIENE', 'PENDIENTE', 'SIN ASIGNAR', 'N/A', 'NA', 'SIN SERIAL', 'SIN REGISTRO'];

        if (in_array($sUpper, $invalidos, true)) {
            return '';
        }

        return $s;
    }

    private function buscarEquipo(?string $activoFijo, ?string $placa, ?string $serial): ?Equipo
    {
        foreach ([['activo_fijo', $activoFijo], ['placa', $placa], ['serial', $serial]] as [$campo, $valor]) {
            if ($valor !== null && $valor !== '') {
                $normalizado = CMDBMapperService::normalizeIdentifier($valor);
                $equipo = Equipo::withTrashed()->where(function ($query) use ($campo, $valor, $normalizado) {
                    $query->where($campo, $valor);
                    if ($normalizado !== $valor) $query->orWhere($campo, $normalizado);
                })->first();
                if ($equipo) return $equipo;
            }
        }
        return null;
    }

    /**
     * Detecta si el tipo corresponde a un periférico que se debe omitir.
     */
    private function esPeriferico(string $tipo): bool
    {
        $n = strtolower($tipo);
        foreach (self::TIPOS_PERIFERICO as $p) {
            if (str_contains($n, $p)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica si una fila está completamente vacía.
     */
    private function filaVacia(array $row): bool
    {
        foreach ($row as $v) {
            if (trim((string) $v) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Mapea los estados operativos del Excel a los valores ENUM de la BD.
     */
    private function mapearEstadoOperativo(?string $estado): string
    {
        if (!$estado) {
            return 'disponible';
        }

        $e = strtolower(trim($estado));

        if (str_contains($e, 'disponible')) {
            return 'disponible';
        }
        if (str_contains($e, 'almacenado') || str_contains($e, 'almacen')) {
            return 'disponible';
        }
        if (str_contains($e, 'mantenimiento') || str_contains($e, 'alistamiento')) {
            return 'mantenimiento';
        }
        if (str_contains($e, 'asignado')) {
            return 'asignado';
        }
        if (str_contains($e, 'operaci') || str_contains($e, 'activo')) {
            return 'activo';
        }
        if (str_contains($e, 'baja') || str_contains($e, 'desechado') || str_contains($e, 'obsoleto')) {
            return 'baja';
        }
        if (str_contains($e, 'pendiente')) {
            return 'disponible';
        }

        return 'disponible';
    }
}
