<?php

namespace App\Services\Importadores;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

/**
 * CMDBMapperService
 *
 * Servicio centralizado para mapear columnas de distintos formatos Excel
 * (CMDB corporativo, formato propio del sistema, etc.) a los campos internos
 * del sistema de inventario.
 *
 * Responsabilidades:
 *  - Detectar automáticamente el formato del archivo subido.
 *  - Resolver qué clave del Excel corresponde a cada campo interno.
 *  - Proveer valores por defecto cuando un campo no existe.
 *  - Generar un reporte de columnas reconocidas / ignoradas / faltantes.
 *  - Limpiar y normalizar datos (N/A, vacíos, fechas numéricas, etc.).
 *
 * Preparado para agregar nuevos formatos en el futuro sin tocar EquiposImport.
 */
class CMDBMapperService
{
    // ── Formatos soportados ──────────────────────────────────────────────────

    public const FORMAT_CMDB    = 'cmdb';
    public const FORMAT_PROPIO  = 'propio';
    public const FORMAT_UNKNOWN = 'desconocido';

    // ── Columnas exclusivas del CMDB para detección ──────────────────────────

    /**
     * Encabezados (normalizados a snake_case) que aparecen
     * exclusivamente en el CMDB corporativo.
     */
    private const CMDB_FINGERPRINTS = [
        'empleado_o_contratista',
        'nombres_y_apellidos',
        'empresa_propietario_del_equipo',
        'cedula_del_funcionariocontratista',
        'fuente_de_recurso',
    ];

    /**
     * Encabezados (normalizados) propios del formato exportado por el sistema
     * que no suelen estar en un CMDB externo.
     */
    private const PROPIO_FINGERPRINTS = [
        'serial',
        'placa',
        'marca',
        'modelo',
        'nombre_de_equipo',
        'tipo_de_recurso',
    ];

    // ── Mapa maestro de alias por campo interno ──────────────────────────────
    //
    // Cada campo interno tiene una lista ORDENADA de posibles nombres de
    // columna (ya normalizados a snake_case sin acentos).  Se toma la
    // PRIMERA coincidencia encontrada en los headers del archivo.
    //
    // IMPORTANTE: El orden importa.  Poner primero los alias más
    // específicos para evitar colisiones (ej. «marca_equipo» antes de
    // «marca» para que no capture la columna «marca» genérica cuando
    // existe una «marca_equipo» más precisa en el CMDB).

    private const COLUMN_MAP = [

        'tipo_asignacion' => [
            'tipo_de_asignacion', 'tipo_asignacion', 'tipo_de_asignacion_responsabilidad', 'asignacion',
            'modalidad_asignacion',
        ],

        // ─── Campos del equipo ───────────────────────────────────────────

        'tipo_recurso' => [
            'tipo',                 // CMDB (ej: EQUIPO ESCRITORIO)
            'tipo_de_recurso',      // propio
            'tipo_recurso',
            'tipo_de_recurso_1',
            'device_type',
            'tipo_equipo',
            'category',
            'categoria',
        ],

        'serial' => [
            'serial',               // CMDB & propio
            'serial_number',
            'numero_serial',
            'numero_de_serial',
            'no_serial',
            'n_serial',
            'nro_serial',
            'numero_serial',
            'nro._serial',
            'asset_serial',
        ],

        'placa' => [
            'placa',                // CMDB & propio
            'asset_tag',
            'activo_fijo',
            'placa_inventario',
            'tag',
        ],

        'activo_fijo' => [
            'activo_fijo', 'activo_fijo_numero', 'numero_activo_fijo',
            'codigo_activo', 'codigo_de_activo', 'activo', 'asset_number',
        ],

        'marca' => [
            'marca',                // propio / CMDB columna "MARCA" (R)
            'marca_equipo',         // CMDB: columna "MARCA EQUIPO" (AO)
            'brand',
            'fabricante',
            'manufacturer',
        ],

        'modelo' => [
            'modelo',               // CMDB & propio
            'model',
            'model_number',
        ],

        'nombre_equipo' => [
            'nombre_de_equipo',     // CMDB & propio
            'nombre_equipo',
            'hostname',
            'equipo',
            'computer_name',
            'device_name',
            'nombre_host',
        ],

        'estado_operativo' => [
            'estado_operativo',     // CMDB & propio
            'estado',
            'status',
            'state',
            'operational_status',
        ],

        'razon_estado' => [
            'razon_del_estado',     // CMDB & propio
            'razon_estado',
            'razon',
            'reason',
            'observacion_estado',
            'motivo',
        ],

        'procesador' => [
            'procesador',           // CMDB & propio
            'processor',
            'cpu',
            'procesador_cpu',
        ],

        'ram' => [
            'memoria_ram',          // CMDB & propio
            'ram',
            'memory',
            'memoria',
            'ram_gb',
        ],

        'disco' => [
            'tamano_disco_duro',    // CMDB
            'disco',
            'disk',
            'storage',
            'almacenamiento',
            'hdd',
            'ssd',
            'disco_duro',
        ],

        'sistema_operativo' => [
            'sistema_operativo',    // CMDB & propio
            'so',
            'os',
            'operating_system',
            'sistema',
            'version_so',
        ],

        'fecha_compra' => [
            'fecha_de_compra',      // CMDB & propio
            'fecha_compra',
            'purchase_date',
            'fecha_adquisicion',
        ],

        'fin_garantia' => [
            'fin_de_garantia',      // CMDB & propio
            'fin_garantia',
            'warranty_end',
            'garantia',
            'fecha_garantia',
            'warranty_expiry',
        ],

        'tiempo_uso' => [
            'tiempo_uso_anos',      // CMDB
            'tiempo_uso',
            'age',
            'antiguedad',
            'anos_uso',
        ],

        // ─── Campos del responsable del activo ────────────────────────────

        'responsable_cedula' => [
            'responsable_del_activo_cedula',
            'responsable_cedula',
            'cedula_responsable',
            'cedula_1', // Segunda columna CEDULA si hubiera
        ],

        'responsable_nombre' => [
            'responsable_del_activo_nombre',
            'responsable_nombre',
            'nombre_responsable',
            'nombres_y_apellidos_1', // Segunda columna NOMBRES Y APELLIDOS del CMDB
            'cheklist_responsable_ti',
            'responsable_administrativo',
            'funcionario_responsable',
            'funcionario_responsable_nombre',
            'nombre_funcionario_responsable',
        ],

        'responsable_cargo' => [
            'responsable_del_activo_cargo',
            'responsable_cargo',
            'cargo_responsable',
            'cargo_1', // Segunda columna CARGO del CMDB
        ],

        'responsable_ciudad' => [
            'responsable_del_activo_ciudad',
            'responsable_ciudad',
            'ciudad_responsable',
            'ciudad_1', // Segunda columna CIUDAD del CMDB
        ],

        'responsable_area' => [
            'responsable_del_activo_area',
            'responsable_area',
            'area_responsable',
            'area_1', // Segunda columna AREA del CMDB
        ],

        'responsable_tipo_recurso' => [
            'responsable_del_activo_tipo_recurso',
            'responsable_tipo_recurso',
            'tipo_recurso_responsable',
            'tipo_de_recurso_1', // Segunda columna TIPO DE RECURSO del CMDB
        ],

        'fecha_inicio_responsable' => [
            'responsable_desde',
            'fecha_inicio_responsable',
            'inicio_responsable',
        ],

        'fecha_fin_responsable' => [
            'responsable_hasta',
            'fecha_fin_responsable',
            'fin_responsable',
        ],

        // ─── Campos del usuario asignado ─────────────────────────────────

        'nombre_usuario' => [
            'usuario_asignado_nombre',
            'nombres_y_apellidos',  // CMDB
            'nombre_usuario',
            'full_name',
            'nombre_completo',
            'nombre_funcionario',
            'funcionario',
            'funcionario_asignado',
            'nombre_del_funcionario',
            'nombres_apellidos',
        ],

        'cedula' => [
            'usuario_asignado_cedula',
            'cedula_de_funcionario_contratista', // CMDB exact match
            'cedula_del_funcionariocontratista',  // CMDB (normalizado antiguo)
            'cedula_del_funcionariocont',
            'cedula_del_funcionario',
            'cedula',
            'documento',
            'identificacion',
            'id_number',
            'cedula_ciudadania',
            'cc',
            'identificacion_funcionario',
            'identificacion_responsable',
            'documento_funcionario',
            'cc_funcionario',
            'cc_responsable',
            'funcionario_responsable_cedula',
            'cedula_funcionario_responsable',
        ],

        'empresa_propietaria' => [
            'usuario_asignado_empresa_propietaria',
            'empresa_propietario_del_equipo',     // CMDB
            'empresa_propietaria_del_equipo',
            'empresa_propietario_del',
            'empresa_propietaria',
            'propietario',
            'owner_company',
            'empresa_duena',
        ],

        'dependencia' => [
            'usuario_asignado_dependencia',
            'dependencia',          // propio
            'dependency',
            'area_funcional',
            'gerencia',
        ],

        'fuente_recurso' => [
            'usuario_asignado_fuente_recurso',
            'fuente_de_recurso',    // CMDB
            'fuente_recurso',
            'fuente',
            'funding_source',
        ],

        'empresa_funcionario' => [
            'usuario_asignado_empresa_funcionario',
            'empresa_funcionario',  // CMDB & propio
            'empresa_empleado',
            'employer',
            'empresa_contratante',
        ],

        'tipo_vinculacion' => [
            'usuario_asignado_tipo_vinculacion',
            'empleado_o_contratista',  // CMDB
            'tipo_vinculacion',
            'vinculacion',
            'employment_type',
            'tipo_contrato',
        ],

        'shortname' => [
            'usuario_asignado_shortname',
            'shortname',            // CMDB & propio
            'short_name',
            'usuario_red',
            'login',
            'network_user',
            'username',
        ],

        'departamento' => [
            'usuario_asignado_departamento',
            'departamento',         // CMDB & propio
            'department',
            'depto',
            'dpto',
        ],

        'ciudad' => [
            'usuario_asignado_ciudad',
            'ciudad',               // CMDB & propio
            'city',
            'municipio',
            'location',
        ],

        'cargo' => [
            'usuario_asignado_cargo',
            'cargo',                // CMDB & propio
            'position',
            'job_title',
            'titulo',
            'puesto',
        ],

        'area' => [
            'usuario_asignado_area',
            'area',                 // CMDB & propio
            'area_trabajo',
            'work_area',
        ],

        'piso' => [
            'usuario_asignado_piso',
            'ubicacion_y_piso',     // CMDB
            'piso',
            'floor',
            'ubicacion',
            'nivel',
        ],

        'distrito' => ['distrito', 'usuario_distrito', 'district'],
        'seccional' => ['seccional', 'seccion', 'usuario_seccional', 'branch'],
    ];

    // ── Valores por defecto cuando el campo no existe o es vacío ──────────

    private const DEFAULTS = [
        'serial'          => null,   // Se autogenera con SIN_SERIAL_xxx
        'marca'           => 'Sin Marca',
        'modelo'          => 'Sin Modelo',
        'nombre_equipo'   => 'Sin Nombre',
        'nombre_usuario'  => 'Sin Asignar',
        'cedula'          => 'Sin Asignar',
        'estado_operativo'=> 'activo',
    ];

    // ── Estado interno ───────────────────────────────────────────────────────

    private ?array  $resolvedMap     = null;
    private array   $resolvedCustomFields = [];
    private string  $detectedFormat  = self::FORMAT_UNKNOWN;
    private array   $recognizedCols  = [];
    private array   $ignoredCols     = [];
    private array   $missingFields   = [];

    // ══════════════════════════════════════════════════════════════════════════
    //  API PÚBLICA
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Inicializa el mapeo analizando la primera fila de datos.
     * Debe llamarse UNA sola vez antes de procesar filas.
     */
    public function initialize(array $firstRow): void
    {
        $rowKeys = array_keys($firstRow);

        // 1. Detectar formato
        $this->detectedFormat = $this->detectFormat($rowKeys);
        Log::info("IMPORT: Formato detectado → {$this->detectedFormat}");

        // 2. Resolver columnas
        $this->resolveColumns($firstRow, $rowKeys);

        // 3. Log de resumen
        Log::info('IMPORT: Columnas reconocidas: ' . count($this->recognizedCols));
        Log::info('IMPORT: Columnas ignoradas: ' . count($this->ignoredCols));
        Log::info('IMPORT: Campos faltantes: ' . count($this->missingFields));
    }

    /**
     * ¿Ya fue inicializado?
     */
    public function isInitialized(): bool
    {
        return $this->resolvedMap !== null;
    }

    /**
     * Obtiene el valor de un campo interno desde una fila del Excel.
     * Aplica limpieza automática.
     */
    public function get(array $row, string $campo): ?string
    {
        if ($this->resolvedMap !== null && isset($this->resolvedMap[$campo])) {
            $key = $this->resolvedMap[$campo];
            return $this->limpiar($row[$key] ?? null);
        }

        return null;
    }

    public function has(string $campo): bool
    {
        return $this->resolvedMap !== null && isset($this->resolvedMap[$campo]);
    }

    /**
     * La modalidad proviene de un valor explícito de la fila. La sola presencia
     * de una columna llamada "responsable" no convierte una asignación normal
     * en una asignación bajo responsabilidad.
     */
    public function isResponsibilityRow(array $row): bool
    {
        // Valores exactos que indican explícitamente una asignación bajo responsabilidad.
        // No se usa str_contains para evitar falsos positivos con valores como
        // "ASIGNACIÓN NORMAL" o "RESPONSABLE ADMINISTRATIVO".
        $valoresResponsabilidad = [
            'ASIGNACION RESPONSABLE',
            'ASIGNACION BAJO RESPONSABILIDAD',
            'RESPONSABILIDAD',
        ];

        foreach (['tipo_asignacion', 'razon_estado'] as $campo) {
            $valor = $this->get($row, $campo);
            if ($valor === null) {
                continue;
            }

            $normalizado = $this->normalizeRelationshipValue($valor);

            foreach ($valoresResponsabilidad as $patron) {
                if ($normalizado === $patron) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function normalizeIdentifier(mixed $value, bool $document = false): ?string
    {
        if ($value === null) return null;
        $value = trim(preg_replace('/[\x00-\x1F\x7F\x{00A0}]+/u', ' ', (string) $value));
        if ($value === '') return null;
        if (preg_match('/^\d+\.0+$/', $value)) $value = strstr($value, '.', true);
        $value = strtoupper($value);
        if ($document) $value = preg_replace('/[.\s-]+/', '', $value);
        return $value !== '' ? $value : null;
    }

    /**
     * Obtiene el valor de un campo, o su default si es null.
     */
    public function getOrDefault(array $row, string $campo): ?string
    {
        $value = $this->get($row, $campo);
        if ($value !== null) {
            return $value;
        }
        return self::DEFAULTS[$campo] ?? null;
    }

    /**
     * Obtiene y parsea una fecha.
     */
    public function getDate(array $row, string $campo): ?string
    {
        if ($this->resolvedMap !== null && isset($this->resolvedMap[$campo])) {
            $key = $this->resolvedMap[$campo];
            $raw = $row[$key] ?? null;
            return $this->parseDate($raw);
        }
        return null;
    }

    /**
     * Obtiene todos los campos personalizados detectados en la fila.
     * Retorna array: [campo_id => valor]
     */
    public function getCustomFields(array $row): array
    {
        $valores = [];
        foreach ($this->resolvedCustomFields as $campoId => $key) {
            $valores[$campoId] = $this->limpiar($row[$key] ?? null);
        }
        return $valores;
    }

    /**
     * Formato detectado.
     */
    public function getDetectedFormat(): string
    {
        return $this->detectedFormat;
    }

    /**
     * Reporte completo de columnas para mostrar en la vista.
     */
    public function getColumnReport(): array
    {
        return [
            'formato'      => $this->detectedFormat,
            'reconocidas'  => $this->recognizedCols,
            'ignoradas'    => $this->ignoredCols,
            'faltantes'    => $this->missingFields,
        ];
    }

    /**
     * Array de valores por defecto.
     */
    public static function getDefaults(): array
    {
        return self::DEFAULTS;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  DETECCIÓN DE FORMATO
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Analiza las claves del Excel para determinar si es CMDB o propio.
     */
    private function detectFormat(array $rowKeys): string
    {
        $cmdbScore   = 0;
        $propioScore = 0;

        foreach (self::CMDB_FINGERPRINTS as $fp) {
            if (in_array($fp, $rowKeys, true)) {
                $cmdbScore++;
            }
        }

        foreach (self::PROPIO_FINGERPRINTS as $fp) {
            if (in_array($fp, $rowKeys, true)) {
                $propioScore++;
            }
        }

        // Si tiene ≥3 huellas del CMDB → es CMDB
        if ($cmdbScore >= 3) {
            return self::FORMAT_CMDB;
        }

        // Si tiene ≥3 huellas propias → es formato propio
        if ($propioScore >= 3) {
            return self::FORMAT_PROPIO;
        }

        // Si tiene alguna huella de cualquiera, usar la que más tenga
        if ($cmdbScore > 0 || $propioScore > 0) {
            return $cmdbScore >= $propioScore ? self::FORMAT_CMDB : self::FORMAT_PROPIO;
        }

        return self::FORMAT_UNKNOWN;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  RESOLUCIÓN DE COLUMNAS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Pre-resuelve el mapeo campo_interno → clave_excel.
     * Se ejecuta UNA sola vez.
     */
    private function resolveColumns(array $row, array $rowKeys): void
    {
        $this->resolvedMap    = [];
        $this->recognizedCols = [];
        $this->ignoredCols    = [];
        $this->missingFields  = [];

        $usedKeys = [];

        foreach (self::COLUMN_MAP as $campo => $aliases) {
            $found = false;

            foreach ($aliases as $alias) {
                $key = $this->normalizeKey($alias);

                if (array_key_exists($key, $row)) {
                    $this->resolvedMap[$campo] = $key;
                    $this->recognizedCols[] = [
                        'campo_interno' => $campo,
                        'columna_excel' => $key,
                        'valor_ejemplo' => $this->truncate($row[$key]),
                    ];
                    $usedKeys[$key] = true;
                    $found = true;

                    Log::info("IMPORT MAP: '{$campo}' → '{$key}' (ejemplo: " . json_encode($this->truncate($row[$key])) . ")");
                    break;
                }
            }

            if (!$found) {
                $this->missingFields[] = $campo;
                Log::warning("IMPORT MAP: '{$campo}' → NO ENCONTRADO");
            }
        }

        // Mapear campos personalizados (Dinámicos)
        $camposPersonalizados = \App\Models\CampoPersonalizado::where('modulo', 'equipos')->get();
        foreach ($camposPersonalizados as $campo) {
            $key = $this->normalizeKey($campo->nombre);
            
            // Si la columna existe en el Excel
            if (array_key_exists($key, $row)) {
                $this->resolvedCustomFields[$campo->id] = $key;
                $this->recognizedCols[] = [
                    'campo_interno' => $campo->nombre . ' (Personalizado)',
                    'columna_excel' => $key,
                    'valor_ejemplo' => $this->truncate($row[$key]),
                ];
                $usedKeys[$key] = true;
                Log::info("IMPORT MAP: Personalizado '{$campo->nombre}' → '{$key}' (ejemplo: " . json_encode($this->truncate($row[$key])) . ")");
            }
        }

        // Determinar columnas ignoradas (presentes en el Excel pero no mapeadas)
        foreach ($rowKeys as $key) {
            if (!isset($usedKeys[$key]) && !is_numeric($key)) {
                $this->ignoredCols[] = $key;
            }
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  HELPERS DE LIMPIEZA Y NORMALIZACIÓN
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Normaliza un alias al formato que usa WithHeadingRow de Laravel Excel:
     * minúsculas, guiones bajos en vez de espacios, sin caracteres especiales.
     */
    private function normalizeKey(string $alias): string
    {
        return \Illuminate\Support\Str::slug($alias, '_');
    }

    private function normalizeRelationshipValue(string $value): string
    {
        return strtoupper(str_replace('_', ' ', \Illuminate\Support\Str::ascii(trim($value))));
    }

    /**
     * Limpia un valor: elimina espacios extra, N/A → null, vacío → null.
     */
    private function limpiar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = trim(preg_replace('/\s+/', ' ', (string) $value));
        $vLower = strtolower($v);

        if ($v === '' || $vLower === 'n/a' || $vLower === 'no aplica') {
            return null;
        }

        return $v;
    }

    /**
     * Parsea fechas: numéricas de Excel (serial) o strings (dd/mm/yyyy, etc.)
     */
    public function parseDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        // Fecha numérica de Excel
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Exception) {
                return null;
            }
        }

        // Fecha como string
        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Trunca un valor para logs (máx 50 chars).
     */
    private function truncate(mixed $value, int $max = 50): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = (string) $value;
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max) . '…' : $s;
    }
}
