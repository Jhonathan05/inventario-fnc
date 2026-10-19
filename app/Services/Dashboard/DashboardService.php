<?php

namespace App\Services\Dashboard;

use App\Models\Equipo;
use App\Models\Funcionario;
use App\Models\Licencia;
use App\Models\Prestamo;
use App\Models\Tarea;
use App\Models\Ticket;
use App\Models\TipoRecurso;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Agregados del panel de control.
 *
 * Toda la lógica SQL del dashboard vive aquí: el controlador sólo resuelve
 * permisos/periodo y la vista sólo pinta. Las métricas se derivan únicamente de
 * datos existentes en el esquema (sin indicadores simulados) y cada bloque
 * respeta el permiso del módulo del que proviene.
 *
 * @phpstan-type Kpi array{
 *     key: string, label: string, value: int|string, meta: string,
 *     icon: string, tone: string, url: string|null
 * }
 */
final class DashboardService
{
    /** Ventanas de vencimiento disponibles en el control segmentado (días). */
    public const VENTANAS = [15, 30, 60, 90];

    public const VENTANA_DEFAULT = 30;

    /** Días de antelación con los que se avisa de una devolución de préstamo. */
    private const DIAS_AVISO_PRESTAMO = 7;

    /** Meses mostrados en la serie de tickets. */
    private const MESES_SERIE = 6;

    /** Días usados para el MTTR operativo. */
    private const DIAS_MTTR = 90;

    /** Paleta de gráficos (hex explícito: válido en SVG y en modo oscuro). */
    private const PALETA = [
        '#9e052b',
        '#2563eb',
        '#0f7b43',
        '#d97706',
        '#7c3aed',
        '#0d9488',
        '#e11d48',
        '#64748b',
    ];

    /**
     * Metadatos de estado operativo (catálogo oficial del CMDB).
     *
     * @var array<string, array{label: string, tone: string, icon: string}>
     */
    private const ESTADO_META = [
        'en_operacion' => ['label' => 'En operación', 'tone' => 'success', 'icon' => 'laptop'],
        'asignacion_responsable' => ['label' => 'Asig. responsable', 'tone' => 'violet', 'icon' => 'user-round'],
        'disponible' => ['label' => 'Disponible', 'tone' => 'info', 'icon' => 'package'],
        'almacenado' => ['label' => 'Almacenado', 'tone' => 'slate', 'icon' => 'archive'],
        'transito' => ['label' => 'Tránsito', 'tone' => 'slate', 'icon' => 'truck'],
        'prestamo' => ['label' => 'Préstamo', 'tone' => 'warning', 'icon' => 'arrow-right-left'],
        'reparacion' => ['label' => 'En reparación', 'tone' => 'warning', 'icon' => 'wrench'],
        'baja' => ['label' => 'Baja', 'tone' => 'danger', 'icon' => 'archive'],
        'procesar_baja' => ['label' => 'Procesar baja', 'tone' => 'danger', 'icon' => 'archive'],
        'hurto' => ['label' => 'Hurto', 'tone' => 'danger', 'icon' => 'triangle-alert'],
        'pendiente_estado' => ['label' => 'Pendiente', 'tone' => 'slate', 'icon' => 'hourglass'],
    ];

    /** Estados de préstamo considerados vigentes. */
    private const PRESTAMO_VIGENTE = ['Pendiente', 'Activo'];

    /** Estados de ticket considerados abiertos. */
    private const TICKET_ABIERTO = ['Abierto', 'En Proceso', 'En Diagnóstico', 'En Diagnostico'];

    /** Estados de ticket considerados cerrados. */
    private const TICKET_CERRADO = ['Resuelto', 'Cerrado'];

    /**
     * Construye todos los datos del tablero.
     *
     * @return array<string, mixed>
     */
    public function build(User $user, int $ventanaDias = self::VENTANA_DEFAULT): array
    {
        $ventana = in_array($ventanaDias, self::VENTANAS, true) ? $ventanaDias : self::VENTANA_DEFAULT;

        $hoy = Carbon::today();
        $ahora = Carbon::now();

        $puede = [
            'equipos' => $user->can('equipos.ver'),
            'licencias' => $user->can('licencias.ver'),
            'tickets' => $user->can('mesaayuda.ver'),
            'historial' => $user->can('historial.ver'),
        ];

        $inventario = [];
        $kpis = [];
        $alertas = [];
        $tickets = [];
        $licencias = [];
        $prestamos = [];
        $garantias = [];
        $disponibilidad = [];

        if ($puede['equipos']) {
            $inventario = $this->inventario($hoy);
        }

        if ($puede['licencias']) {
            $licencias = $this->licencias($hoy, $ventana);
        }

        if ($puede['tickets']) {
            $tickets = $this->tickets($ahora);
        }

        if ($puede['equipos']) {
            $prestamos = $this->prestamos($hoy, $ahora, $ventana);
            $garantias = $this->garantias($hoy, $ventana);
            $disponibilidad = $this->disponibilidad();
        }

        $kpis = $this->kpis($puede, $inventario, $licencias, $tickets, $prestamos);
        $alertas = $this->alertas($puede, $licencias, $tickets, $prestamos);
        $tareasPendientes = $this->tareasPendientes($puede['equipos']);

        return [
            'ventana' => $ventana,
            'ventanas' => self::VENTANAS,
            'generadoEn' => $ahora,
            'puede' => $puede,
            'inventario' => $inventario,
            'disponibilidad' => $disponibilidad,
            'kpis' => $kpis,
            'alertas' => $alertas,
            'tickets' => $tickets,
            'licencias' => $licencias,
            'prestamos' => $prestamos,
            'garantias' => $garantias,
            'ultimosEquipos' => $puede['equipos'] ? $this->ultimosEquipos() : new Collection(),
            'tareasPendientes' => $tareasPendientes,
        ];
    }

    // -----------------------------------------------------------------
    // Bloques
    // -----------------------------------------------------------------

    /**
     * Existencias por estado operativo, distribución por tipo y antigüedad.
     *
     * @return array<string, mixed>
     */
    private function inventario(Carbon $hoy): array
    {
        $porEstado = Equipo::query()
            ->select('estado_operativo', DB::raw('COUNT(*) as total'))
            ->groupBy('estado_operativo')
            ->pluck('total', 'estado_operativo');

        $total = (int) $porEstado->sum();

        $asignados = (int) ($porEstado['en_operacion'] ?? 0) + (int) ($porEstado['asignacion_responsable'] ?? 0);

        $filas = [
            ['key' => 'asignados', 'label' => 'En uso', 'tone' => 'success', 'icon' => 'laptop', 'value' => $asignados, 'estado' => 'en_operacion'],
            ['key' => 'disponible', 'label' => 'Disponible', 'tone' => 'info', 'icon' => 'package', 'value' => (int) ($porEstado['disponible'] ?? 0), 'estado' => 'disponible'],
            ['key' => 'reparacion', 'label' => 'En reparación', 'tone' => 'warning', 'icon' => 'wrench', 'value' => (int) ($porEstado['reparacion'] ?? 0), 'estado' => 'reparacion'],
            ['key' => 'baja', 'label' => 'Dados de baja', 'tone' => 'danger', 'icon' => 'archive', 'value' => (int) ($porEstado['baja'] ?? 0) + (int) ($porEstado['procesar_baja'] ?? 0), 'estado' => 'baja'],
        ];

        foreach ($filas as $indice => $fila) {
            $filas[$indice]['pct'] = $total > 0 ? round($fila['value'] / $total * 100, 1) : 0.0;
        }

        return [
            'total' => $total,
            'asignados' => $asignados,
            'disponibles' => (int) ($porEstado['disponible'] ?? 0),
            'reparacion' => (int) ($porEstado['reparacion'] ?? 0),
            'baja' => (int) ($porEstado['baja'] ?? 0) + (int) ($porEstado['procesar_baja'] ?? 0),
            'filas' => $filas,
            'porTipo' => $this->porTipo(),
            'antiguedad' => $this->antiguedad($hoy),
        ];
    }

    /**
     * Distribución por tipo de recurso (top 6 + Otros) lista para el donut.
     *
     * @return array{total: int, segmentos: array<int, array<string, mixed>>}
     */
    private function porTipo(): array
    {
        $tipos = TipoRecurso::query()
            ->select(['id', 'nombre'])
            ->withCount('equipos')
            ->orderByDesc('equipos_count')
            ->limit(7)
            ->get()
            ->filter(static fn (TipoRecurso $tipo): bool => (int) $tipo->equipos_count > 0)
            ->values();

        $total = (int) $tipos->sum(static fn (TipoRecurso $tipo): int => (int) $tipo->equipos_count);

        $segmentos = [];
        foreach ($tipos as $indice => $tipo) {
            $segmentos[] = [
                'label' => (string) $tipo->nombre,
                'value' => (int) $tipo->equipos_count,
                'color' => self::PALETA[$indice % count(self::PALETA)],
                'url' => route('equipos.index', ['tipo' => $tipo->id], false),
                'pct' => $total > 0 ? round((int) $tipo->equipos_count / $total * 100, 1) : 0.0,
            ];
        }

        return ['total' => $total, 'segmentos' => $segmentos];
    }

    /**
     * Disponibilidad por tipo de recurso: equipos en uso y disponibles.
     *
     * Sólo categorías con existencias, en orden descendente por tamaño de parque.
     *
     * @return array<int, array<string, int|string>>
     */
    private function disponibilidad(): array
    {
        $tipos = TipoRecurso::query()
            ->select(['id', 'nombre'])
            ->withCount([
                'equipos',
                'equipos as usados' => static fn ($query) => $query->whereIn('estado_operativo', ['en_operacion', 'asignacion_responsable']),
                'equipos as disponibles' => static fn ($query) => $query->where('estado_operativo', 'disponible'),
            ])
            ->orderByDesc('equipos_count')
            ->get()
            ->filter(static fn (TipoRecurso $tipo): bool => (int) $tipo->equipos_count > 0)
            ->values();

        return $tipos
            ->map(static fn (TipoRecurso $tipo): array => [
                'label' => (string) $tipo->nombre,
                'total' => (int) $tipo->equipos_count,
                'usados' => (int) $tipo->usados,
                'disponibles' => (int) $tipo->disponibles,
            ])
            ->all();
    }

    /**
     * Antigüedad del parque a partir de la fecha de compra.
     *
     * @return array<int, array<string, mixed>>
     */
    private function antiguedad(Carbon $hoy): array
    {
        $buckets = Equipo::query()
            ->selectRaw(
                "CASE
                    WHEN fecha_compra IS NULL THEN 'sin_dato'
                    WHEN fecha_compra > ? THEN 'lt1'
                    WHEN fecha_compra > ? THEN 'a1_2'
                    WHEN fecha_compra > ? THEN 'a2_3'
                    WHEN fecha_compra > ? THEN 'a3_5'
                    ELSE 'gt5'
                END AS bucket, COUNT(*) AS total",
                [
                    $hoy->copy()->subYear()->toDateString(),
                    $hoy->copy()->subYears(2)->toDateString(),
                    $hoy->copy()->subYears(3)->toDateString(),
                    $hoy->copy()->subYears(5)->toDateString(),
                ]
            )
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $total = (int) $buckets->sum();

        $orden = [
            ['key' => 'lt1', 'label' => 'Menos de 1 año', 'tone' => 'success'],
            ['key' => 'a1_2', 'label' => '1 a 2 años', 'tone' => 'info'],
            ['key' => 'a2_3', 'label' => '2 a 3 años', 'tone' => 'violet'],
            ['key' => 'a3_5', 'label' => '3 a 5 años', 'tone' => 'warning'],
            ['key' => 'gt5', 'label' => 'Más de 5 años', 'tone' => 'danger'],
            ['key' => 'sin_dato', 'label' => 'Sin fecha de compra', 'tone' => 'slate'],
        ];

        $filas = [];
        foreach ($orden as $fila) {
            $valor = (int) ($buckets[$fila['key']] ?? 0);
            $filas[] = $fila + [
                'value' => $valor,
                'pct' => $total > 0 ? round($valor / $total * 100, 1) : 0.0,
            ];
        }

        return $filas;
    }

    /**
     * Tareas pendientes del tablero. Mezcla tres orígenes, en orden:
     *
     *  1. Tareas creadas manualmente (persistidas en `tareas`).
     *  2. Activos con datos incompletos de la CMDB (vacíos o "PENDIENTE").
     *  3. Funcionarios con datos vacíos.
     *
     * Los dos últimos orígenes se derivan al vuelo: desaparecen cuando el dato
     * de origen se corrige, sin estado extra que mantener.
     *
     * @return array{items: list<array<string, mixed>>, total: int, resta: int}
     */
    private function tareasPendientes(bool $verEquipos, int $limite = 10): array
    {
        $camposEquipo = [
            'nombre_equipo' => 'Nombre del equipo',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'serial' => 'Serie / Placa',
            'procesador' => 'Procesador',
        ];

        $camposFuncionario = [
            'cargo' => 'Cargo',
            'area' => 'Área',
            'departamento' => 'Departamento',
            'ciudad' => 'Ciudad',
            'tipo_vinculacion' => 'Tipo de vinculación',
        ];

        // Vacío o marcador "PENDIENTE" heredado de la importación CMDB.
        $detalleIncompleto = function (array $campos, object $fila): string {
            $faltantes = [];
            foreach ($campos as $campo => $etiqueta) {
                $valor = $fila->{$campo} ?? null;
                if ($valor === null || trim((string) $valor) === '' || str_contains(mb_strtoupper((string) $valor), 'PENDIENTE')) {
                    $faltantes[] = $etiqueta;
                }
            }

            return collect($faltantes)->slice(0, 3)->implode(', ') . (count($faltantes) > 3 ? ' y más' : '');
        };

        $items = collect();

        // 1. Manuales: prioridad Alta → Media → Baja, más recientes primero.
        $manuales = Tarea::query()->pendientes()->ordenPrioridad()->get();
        foreach ($manuales as $tarea) {
            $items->push([
                'kind' => 'manual',
                'tarea_id' => $tarea->id,
                'titulo' => (string) $tarea->titulo,
                'meta' => 'Tarea manual · ' . match ($tarea->modulo) {
                    'equipos' => 'Equipos',
                    'funcionarios' => 'Funcionarios',
                    default => 'General',
                },
                'pill' => $tarea->prioridad,
                'tone' => match ($tarea->prioridad) {
                    'Alta' => 'danger',
                    'Media' => 'warning',
                    default => 'slate',
                },
                'url' => null,
            ]);
        }

        $total = $manuales->count();

        if ($verEquipos) {
            // 2. Activos CMDB con datos incompletos.
            $equipos = Equipo::query()
                ->select(['id', 'nombre_equipo', 'placa', 'marca', 'modelo', 'serial', 'procesador'])
                ->where(function ($q) use ($camposEquipo): void {
                    foreach ($camposEquipo as $campo => $etiqueta) {
                        $q->orWhere(function ($q2) use ($campo): void {
                            $q2->whereNull($campo)
                                ->orWhere($campo, '')
                                ->orWhere($campo, 'like', '%PENDIENTE%');
                        });
                    }
                })
                ->get();

            foreach ($equipos as $equipo) {
                $items->push([
                    'kind' => 'cmdb_equipo',
                    'tarea_id' => null,
                    'titulo' => 'Actualizar datos del activo: ' . ($equipo->nombre_equipo ?: ($equipo->placa ?: 'Activo #' . $equipo->id)),
                    'meta' => 'Faltan: ' . $detalleIncompleto($camposEquipo, $equipo),
                    'pill' => 'CMDB',
                    'tone' => 'warning',
                    'url' => route('equipos.edit', $equipo, false),
                ]);
            }
            $total += $equipos->count();

            // 3. Funcionarios con datos vacíos.
            $funcionarios = Funcionario::query()
                ->select(['id', 'nombres', 'apellidos', ...array_keys($camposFuncionario)])
                ->where(function ($q) use ($camposFuncionario): void {
                    foreach ($camposFuncionario as $campo => $etiqueta) {
                        $q->orWhere(function ($q2) use ($campo): void {
                            $q2->whereNull($campo)->orWhere($campo, '');
                        });
                    }
                })
                ->get();

            foreach ($funcionarios as $funcionario) {
                $nombre = trim((string) $funcionario->nombres . ' ' . (string) $funcionario->apellidos);
                $items->push([
                    'kind' => 'cmdb_funcionario',
                    'tarea_id' => null,
                    'titulo' => 'Actualizar datos del funcionario: ' . ($nombre ?: 'Funcionario #' . $funcionario->id),
                    'meta' => 'Faltan: ' . $detalleIncompleto($camposFuncionario, $funcionario),
                    'pill' => 'CMDB',
                    'tone' => 'info',
                    'url' => route('funcionarios.edit', $funcionario, false),
                ]);
            }
            $total += $funcionarios->count();
        }

        return [
            'items' => $items->take($limite)->values()->all(),
            'total' => $total,
            'resta' => (int) max(0, $total - $limite),
        ];
    }

    /**
     * Licencias: vencidas, próximas a vencer y cumplimiento de cupos.
     *
     * @return array<string, mixed>
     */
    private function licencias(Carbon $hoy, int $ventana): array
    {
        $vencidas = Licencia::query()
            ->where(function ($q) use ($hoy): void {
                $q->where('estado', 'Vencida')
                    ->orWhere(function ($q2) use ($hoy): void {
                        $q2->whereNotNull('fecha_vencimiento')
                            ->where('fecha_vencimiento', '<', $hoy->toDateString());
                    });
            })
            ->count();

        $porVencer = Licencia::query()
            ->where('estado', 'Activa')
            ->whereNotNull('fecha_vencimiento')
            ->whereBetween('fecha_vencimiento', [$hoy->toDateString(), $hoy->copy()->addDays($ventana)->toDateString()])
            ->count();

        $criticas = Licencia::query()
            ->select(['id', 'nombre', 'cantidad_maxima', 'fecha_vencimiento', 'estado'])
            ->whereIn('estado', ['Activa', 'Vencida'])
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<=', $hoy->copy()->addDays($ventana)->toDateString())
            ->orderBy('fecha_vencimiento')
            ->limit(6)
            ->get();

        // Cupos sobre TODAS las licencias parametrizadas: la suma alimenta los
        // KPI "usadas"/"disponibles" del panel; el detalle va al top 6.
        $baseCupos = Licencia::query()
            ->select(['id', 'nombre', 'cantidad_maxima'])
            ->whereNotNull('cantidad_maxima')
            ->where('cantidad_maxima', '>', 0)
            ->withCount([
                'asignaciones as cupos_asignados_count' => static fn ($q) => $q->where('estado', 'Activa'),
            ])
            ->orderByDesc('cupos_asignados_count')
            ->get();

        $cuposUsados = (int) $baseCupos->sum(static fn (Licencia $l) => min((int) $l->cantidad_maxima, (int) $l->cupos_asignados_count));
        $cuposTotales = (int) $baseCupos->sum('cantidad_maxima');

        $cumplimiento = $baseCupos
            ->take(6)
            ->map(static function (Licencia $licencia): array {
                $maximos = (int) $licencia->cantidad_maxima;
                $usados = (int) $licencia->cupos_asignados_count;

                return [
                    'label' => (string) $licencia->nombre,
                    'value' => $usados,
                    'max' => $maximos,
                    'pct' => $maximos > 0 ? min(100.0, round($usados / $maximos * 100, 1)) : 0.0,
                    'tone' => $maximos > 0 && $usados >= $maximos ? 'danger' : ($maximos > 0 && $usados / $maximos >= 0.8 ? 'warning' : 'success'),
                    'url' => route('licencias.index', ['estado' => 'Activa'], false),
                ];
            })
            ->values()
            ->all();

        return [
            'vencidas' => $vencidas,
            'porVencer' => $porVencer,
            'criticas' => $criticas,
            'cumplimiento' => $cumplimiento,
            'cupos' => [
                'usados' => $cuposUsados,
                'disponibles' => max(0, $cuposTotales - $cuposUsados),
                'totales' => $cuposTotales,
            ],
        ];
    }

    /**
     * Tickets: cola abierta, críticos, distribución y MTTR.
     *
     * @return array<string, mixed>
     */
    private function tickets(Carbon $ahora): array
    {
        $abiertos = Ticket::query()->whereIn('estado', self::TICKET_ABIERTO)->count();

        $criticos = Ticket::query()
            ->where('prioridad', 'Crítica')
            ->whereNotIn('estado', self::TICKET_CERRADO)
            ->count();

        $porPrioridad = Ticket::query()
            ->select('prioridad', DB::raw('COUNT(*) as total'))
            ->whereNotIn('estado', self::TICKET_CERRADO)
            ->groupBy('prioridad')
            ->pluck('total', 'prioridad');

        $cola = Ticket::query()
            ->select(['id', 'titulo', 'prioridad', 'estado', 'fecha_solicitud', 'funcionario_id', 'equipo_id', 'user_id'])
            ->with([
                'funcionario:id,nombres,apellidos',
                'responsable:id,name',
                'equipo:id,nombre_equipo,placa',
            ])
            ->whereNotIn('estado', self::TICKET_CERRADO)
            ->orderByRaw("FIELD(prioridad, 'Crítica', 'Alta', 'Media', 'Baja')")
            ->orderBy('fecha_solicitud')
            ->limit(6)
            ->get();

        $serie = $this->serieTickets($ahora);

        $mttr = Ticket::query()
            ->whereNotNull('fecha_solicitud')
            ->where('fecha_solicitud', '>=', $ahora->copy()->subDays(self::DIAS_MTTR))
            ->whereRaw('COALESCE(fecha_cierre, fecha_solucion) IS NOT NULL')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, fecha_solicitud, COALESCE(fecha_cierre, fecha_solucion))) as horas')
            ->first();

        $mttrHoras = $mttr?->horas;

        return [
            'abiertos' => $abiertos,
            'criticos' => $criticos,
            'porPrioridad' => $porPrioridad,
            'cola' => $cola,
            'serie' => $serie,
            'mttrHoras' => $mttrHoras !== null ? round((float) $mttrHoras, 1) : null,
            'diasMttr' => self::DIAS_MTTR,
        ];
    }

    /**
     * Serie de tickets creados por mes (últimos N meses, huecos rellenados).
     *
     * @return array<int, array{ym: string, label: string, value: int}>
     */
    private function serieTickets(Carbon $ahora): array
    {
        $inicio = $ahora->copy()->startOfMonth()->subMonths(self::MESES_SERIE - 1);

        $totales = Ticket::query()
            ->where('fecha_solicitud', '>=', $inicio)
            ->selectRaw("DATE_FORMAT(fecha_solicitud, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $serie = [];
        for ($i = 0; $i < self::MESES_SERIE; $i++) {
            $mes = $inicio->copy()->addMonths($i);
            $clave = $mes->format('Y-m');

            $serie[] = [
                'ym' => $clave,
                'label' => $mes->translatedFormat('M'),
                'value' => (int) ($totales[$clave] ?? 0),
            ];
        }

        return $serie;
    }

    /**
     * Préstamos: vigentes, vencidos y cola de devoluciones inmediatas.
     *
     * @return array<string, mixed>
     */
    private function prestamos(Carbon $hoy, Carbon $ahora, int $ventana): array
    {
        $activos = Prestamo::query()->whereIn('estado', self::PRESTAMO_VIGENTE)->count();

        $vencidos = Prestamo::query()
            ->where(function ($q) use ($ahora): void {
                $q->where('estado', 'Vencido')
                    ->orWhere(function ($q2) use ($ahora): void {
                        $q2->whereIn('estado', self::PRESTAMO_VIGENTE)
                            ->whereNotNull('fecha_devolucion_prevista')
                            ->where('fecha_devolucion_prevista', '<', $ahora);
                    });
            })
            ->count();

        $porVencer = Prestamo::query()
            ->whereIn('estado', self::PRESTAMO_VIGENTE)
            ->whereNotNull('fecha_devolucion_prevista')
            ->whereBetween('fecha_devolucion_prevista', [$ahora, $ahora->copy()->addDays($ventana)])
            ->count();

        $cola = Prestamo::query()
            ->select(['id', 'equipo_id', 'persona_nombre', 'fecha_devolucion_prevista', 'estado'])
            ->with(['equipo:id,nombre_equipo,placa,serial'])
            ->whereIn('estado', self::PRESTAMO_VIGENTE)
            ->whereNotNull('fecha_devolucion_prevista')
            ->where('fecha_devolucion_prevista', '<=', $ahora->copy()->addDays(self::DIAS_AVISO_PRESTAMO))
            ->orderBy('fecha_devolucion_prevista')
            ->limit(6)
            ->get();

        $devueltos = Prestamo::query()->where('estado', 'Devuelto')->count();

        return [
            'activos' => $activos,
            'vencidos' => $vencidos,
            'porVencer' => $porVencer,
            'cola' => $cola,
            'devueltos' => $devueltos,
            'total' => $activos + $vencidos + $devueltos,
            'diasAviso' => self::DIAS_AVISO_PRESTAMO,
            'hoy' => $hoy,
        ];
    }

    /**
     * Garantías que expiran dentro de la ventana seleccionada.
     *
     * @return array<string, mixed>
     */
    private function garantias(Carbon $hoy, int $ventana): array
    {
        $porVencer = Equipo::query()
            ->whereNotNull('fin_garantia')
            ->whereBetween('fin_garantia', [$hoy->toDateString(), $hoy->copy()->addDays($ventana)->toDateString()])
            ->count();

        $cola = Equipo::query()
            ->select(['id', 'tipo_recurso_id', 'nombre_equipo', 'placa', 'serial', 'marca', 'modelo', 'fin_garantia', 'estado_operativo'])
            ->with('tipoRecurso:id,nombre')
            ->whereNotNull('fin_garantia')
            ->whereBetween('fin_garantia', [$hoy->toDateString(), $hoy->copy()->addDays($ventana)->toDateString()])
            ->orderBy('fin_garantia')
            ->limit(6)
            ->get();

        return [
            'porVencer' => $porVencer,
            'cola' => $cola,
        ];
    }

    /**
     * Últimos activos registrados.
     *
     * @return Collection<int, Equipo>
     */
    private function ultimosEquipos(): Collection
    {
        return Equipo::query()
            ->select(['id', 'tipo_recurso_id', 'nombre_equipo', 'serial', 'placa', 'marca', 'modelo', 'estado_operativo', 'fin_garantia', 'created_at'])
            ->with([
                'tipoRecurso:id,nombre',
                'usuarioAsignado:id,equipo_id,nombre,cedula,distrito,ciudad',
            ])
            ->latest()
            ->limit(6)
            ->get();
    }

    // -----------------------------------------------------------------
    // Tarjetas y alertas
    // -----------------------------------------------------------------

    /**
     * Tarjetas KPI visibles según permisos.
     *
     * @param  array<string, bool>  $puede
     * @param  array<string, mixed>  $inventario
     * @param  array<string, mixed>  $licencias
     * @param  array<string, mixed>  $tickets
     * @param  array<string, mixed>  $prestamos
     * @return array<int, array<string, mixed>>
     */
    private function kpis(
        array $puede,
        array $inventario,
        array $licencias,
        array $tickets,
        array $prestamos
    ): array {
        $kpis = [];

        if ($puede['equipos']) {
            $total = (int) ($inventario['total'] ?? 0);

            $kpis[] = [
                'key' => 'total',
                'label' => 'Activos registrados',
                'value' => $total,
                'meta' => count($inventario['porTipo']['segmentos'] ?? []) . ' categorías con existencias',
                'icon' => 'boxes',
                'tone' => 'brand',
                'url' => route('equipos.index', [], false),
            ];
            $kpis[] = [
                'key' => 'asignados',
                'label' => 'En uso',
                'value' => (int) ($inventario['asignados'] ?? 0),
                'meta' => 'equipos en operación por funcionarios',
                'icon' => 'laptop',
                'tone' => 'success',
                'url' => route('equipos.index', ['estado' => 'en_operacion'], false),
            ];
            $kpis[] = [
                'key' => 'disponibles',
                'label' => 'Disponibles',
                'value' => (int) ($inventario['disponibles'] ?? 0),
                'meta' => 'listos para asignar o prestar',
                'icon' => 'package',
                'tone' => 'info',
                'url' => route('equipos.index', ['estado' => 'disponible'], false),
            ];
            $kpis[] = [
                'key' => 'reparacion',
                'label' => 'En reparación',
                'value' => (int) ($inventario['reparacion'] ?? 0),
                'meta' => 'fuera de servicio temporalmente',
                'icon' => 'wrench',
                'tone' => 'warning',
                'url' => route('equipos.index', ['estado' => 'reparacion'], false),
            ];
            $kpis[] = [
                'key' => 'baja',
                'label' => 'Dados de baja',
                'value' => (int) ($inventario['baja'] ?? 0),
                'meta' => 'pendientes de disposición final',
                'icon' => 'archive',
                'tone' => 'danger',
                'url' => route('equipos.index', ['estado' => 'baja'], false),
            ];
        }

        if ($puede['equipos'] && $prestamos !== []) {
            $kpis[] = [
                'key' => 'prestamos_activos',
                'label' => 'Préstamos vigentes',
                'value' => (int) ($prestamos['activos'] ?? 0),
                'meta' => (int) ($prestamos['porVencer'] ?? 0) . ' vencen en los próximos días',
                'icon' => 'arrow-right-left',
                'tone' => 'info',
                'url' => route('prestamos.index', ['estado' => 'Activo'], false),
            ];
            $kpis[] = [
                'key' => 'prestamos_vencidos',
                'label' => 'Préstamos vencidos',
                'value' => (int) ($prestamos['vencidos'] ?? 0),
                'meta' => (int) ($prestamos['vencidos'] ?? 0) > 0
                    ? 'requieren gestión de devolución'
                    : 'sin devoluciones atrasadas',
                'icon' => 'calendar-x',
                'tone' => (int) ($prestamos['vencidos'] ?? 0) > 0 ? 'danger' : 'success',
                'url' => route('prestamos.index', ['estado' => 'Activo'], false),
            ];
        }

        if ($puede['tickets'] && $tickets !== []) {
            $kpis[] = [
                'key' => 'tickets_abiertos',
                'label' => 'Tickets abiertos',
                'value' => (int) ($tickets['abiertos'] ?? 0),
                'meta' => (int) ($tickets['criticos'] ?? 0) . ' con prioridad crítica',
                'icon' => 'headset',
                'tone' => (int) ($tickets['criticos'] ?? 0) > 0 ? 'warning' : 'success',
                'url' => route('tickets.index', ['estado' => 'Abierto'], false),
            ];
        }

        if ($puede['licencias'] && $licencias !== []) {
            $kpis[] = [
                'key' => 'licencias_vencidas',
                'label' => 'Licencias vencidas',
                'value' => (int) ($licencias['vencidas'] ?? 0),
                'meta' => (int) ($licencias['porVencer'] ?? 0) . ' vencen en la ventana activa',
                'icon' => 'key-round',
                'tone' => (int) ($licencias['vencidas'] ?? 0) > 0 ? 'danger' : 'success',
                'url' => route('licencias.index', ['estado' => 'Vencida'], false),
            ];
        }

        return $kpis;
    }

    /**
     * Alertas accionables (sólo se muestran las que tienen incidencias).
     *
     * @param  array<string, bool>  $puede
     * @param  array<string, mixed>  $licencias
     * @param  array<string, mixed>  $tickets
     * @param  array<string, mixed>  $prestamos
     * @return array<int, array<string, mixed>>
     */
    private function alertas(
        array $puede,
        array $licencias,
        array $tickets,
        array $prestamos
    ): array {
        $alertas = [];

        if ($puede['licencias'] && (int) ($licencias['vencidas'] ?? 0) > 0) {
            $alertas[] = [
                'key' => 'licencias_vencidas',
                'tone' => 'danger',
                'icon' => 'octagon-alert',
                'title' => 'Licencias vencidas',
                'meta' => 'Renovación o retiro pendiente',
                'count' => (int) $licencias['vencidas'],
                'url' => route('licencias.index', ['estado' => 'Vencida'], false),
            ];
        }

        if ($puede['licencias'] && (int) ($licencias['porVencer'] ?? 0) > 0) {
            $alertas[] = [
                'key' => 'licencias_por_vencer',
                'tone' => 'warning',
                'icon' => 'triangle-alert',
                'title' => 'Licencias próximas a vencer',
                'meta' => 'Dentro de la ventana seleccionada',
                'count' => (int) $licencias['porVencer'],
                'url' => route('licencias.index', ['estado' => 'Activa'], false),
            ];
        }

        if ($puede['equipos'] && (int) ($prestamos['vencidos'] ?? 0) > 0) {
            $alertas[] = [
                'key' => 'prestamos_vencidos',
                'tone' => 'danger',
                'icon' => 'calendar-x',
                'title' => 'Préstamos con devolución atrasada',
                'meta' => 'Contactar al responsable del préstamo',
                'count' => (int) $prestamos['vencidos'],
                'url' => route('prestamos.index', ['estado' => 'Activo'], false),
            ];
        }

        if ($puede['equipos'] && (int) ($prestamos['porVencer'] ?? 0) > 0) {
            $alertas[] = [
                'key' => 'prestamos_por_vencer',
                'tone' => 'info',
                'icon' => 'clock',
                'title' => 'Préstamos por devolver',
                'meta' => 'Próximos ' . self::DIAS_AVISO_PRESTAMO . ' días',
                'count' => (int) $prestamos['porVencer'],
                'url' => route('prestamos.index', ['estado' => 'Activo'], false),
            ];
        }

        if ($puede['tickets'] && (int) ($tickets['criticos'] ?? 0) > 0) {
            $alertas[] = [
                'key' => 'tickets_criticos',
                'tone' => 'danger',
                'icon' => 'siren',
                'title' => 'Tickets de prioridad crítica',
                'meta' => 'Sin resolver en HelpDesk',
                'count' => (int) $tickets['criticos'],
                'url' => route('tickets.index', ['estado' => 'Abierto'], false),
            ];
        }

        return $alertas;
    }

    /**
     * Etiqueta legible de un estado operativo.
     */
    public static function estadoLabel(string $estado): string
    {
        return self::ESTADO_META[$estado]['label'] ?? ucfirst($estado);
    }

    /**
     * Tono semántico de un estado operativo.
     */
    public static function estadoTone(string $estado): string
    {
        return self::ESTADO_META[$estado]['tone'] ?? 'slate';
    }
}
