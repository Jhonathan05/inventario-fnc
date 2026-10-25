<?php

namespace App\Services;

use App\Models\Correccion;
use App\Models\Equipo;
use App\Models\Funcionario;
use App\Models\TipoRecurso;
use App\Models\UsuarioAsignado;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CorreccionService — detección y corrección de faltantes de información.
 *
 * Contrato (Fase 0, docs/cmdb_contrato_columnas.md §12):
 *  - La importación es NO condicionada: las filas importan aunque estén
 *    incompletas y cada faltante queda como corrección pendiente.
 *  - Cada pendiente es corregible (o justificable) directamente desde la
 *    pestaña "Correcciones" del módulo Importar.
 *  - Objetivo: completar el 100 % de la información real.
 *
 * Tipos de hallazgo:
 *  1. Valor vacío o placeholder (PENDIENTE / N/A / SIN REGISTRO / …).
 *  2. Valor fuera del catálogo oficial (estados, razones, diccionarios).
 *  3. Custodia: activo EN OPERACIÓN sin custodio, o custodio sin cédula/nombre.
 */
class CorreccionService
{
    /**
     * Placeholders que cuentan como valor faltante.
     *
     * Incluye los rellenos que genera el propio importador
     * (`SIN NOMBRE` / `SIN MARCA` / `SIN MODELO`, ver
     * `CMDBMapperService::DEFAULTS`): son valores de pantalla, no datos
     * reales, así que deben aparecer en Correcciones como cualquier vacío.
     */
    public const PLACEHOLDERS = [
        '', 'PENDIENTE', 'PENDIENTE ESTADO', 'N/A', 'NA', 'SIN REGISTRO',
        'SIN ASIGNAR', 'NO APLICA', 'NO APLICA.', 'NULL', 'NINGUNO', '-', '--', '.',
        'SIN NOMBRE', 'SIN MARCA', 'SIN MODELO',
    ];

    /**
     * Campos del activo (ficha CMDB) que se auditan.
     * campo => [etiqueta, severidad, tipo_dato, catalogo|null]
     */
    public const CAMPOS_EQUIPO = [
        'serial'              => ['Serial (CMDB col 22)',            'alta',  'texto',    null],
        'placa'               => ['Placa (CMDB col 23)',             'alta',  'texto',    null],
        'tipo'                => ['Tipo de recurso (CMDB col 21)',   'media', 'catalogo', 'tipo_recursos'],
        // La marca se valida contra el catálogo oficial (`diccionarios`
        // grupo `marca`): un valor fuera de lista es un dato a corregir,
        // no texto libre.
        'marca'               => ['Marca (CMDB col 24)',             'media', 'catalogo', 'marca'],
        'modelo'              => ['Modelo (CMDB col 25)',            'media', 'texto',    null],
        'nombre_equipo'       => ['Nombre de equipo (CMDB col 27)',  'media', 'texto',    null],
        'estado_operativo'    => ['Estado operativo (CMDB col 28)', 'alta',  'catalogo', 'catalogo_estados'],
        'razon_estado'        => ['Razón del estado (CMDB col 29)', 'media', 'catalogo', 'catalogo_razones'],
        'estado_uso'          => ['Estado de uso (CMDB col 26)',     'media', 'catalogo', 'estado_uso'],
        'administrado'       => ['Administrado/Controlado (col 30)','media', 'catalogo', 'administrado'],
        'procesador'          => ['Procesador (CMDB col 31)',        'media', 'catalogo', 'procesador'],
        'ram'                 => ['Memoria RAM (CMDB col 32)',       'media', 'catalogo', 'memoria_ram'],
        'disco'               => ['Disco duro (CMDB col 33)',        'media', 'catalogo', 'disco_duro'],
        'sistema_operativo'   => ['Sistema operativo (CMDB col 34)','media', 'catalogo', 'sistema_operativo'],
        'fecha_compra'        => ['Fecha de compra (CMDB col 35)',   'media', 'fecha',    null],
        'fin_garantia'        => ['Fin de garantía (CMDB col 36)',   'media', 'fecha',    null],
        'tiempo_uso'          => ['Tiempo de uso (CMDB col 37)',     'baja',  'texto',    null],
        'tipo_propiedad'      => ['Tipo de propiedad (CMDB col 38)','media', 'texto',    null],
        'checklist_responsable' => ['Checklist responsable TI (col 39)', 'media', 'texto', null],
        'orden_remision'      => ['Orden de remisión (CMDB col 40)','baja',  'texto',    null],
        'observaciones'       => ['Observaciones (CMDB col 41)',     'baja',  'texto',    null],
    ];

    /**
     * Campos de la custodia (usuario_asignados) que se auditan.
     * campo interno => [etiqueta, severidad]
     */
    public const CAMPOS_CUSTODIA = [
        'cedula'       => ['Cédula del custodio (CMDB col 6)',  'alta'],
        'nombre'       => ['Nombre del custodio (CMDB col 8)',  'alta'],
        'shortname'    => ['Shortname (CMDB col 7)',            'media'],
        'cargo'        => ['Cargo del custodio (CMDB col 11)',  'media'],
        'area'         => ['Área del custodio (CMDB col 12)',    'media'],
        'ciudad'       => ['Ciudad del custodio (CMDB col 10)',  'media'],
        'departamento' => ['Departamento (CMDB col 9)',          'baja'],
        'piso'         => ['Ubicación y piso (CMDB col 13)',    'baja'],
    ];

    /* ════════════════════════════════════════════════════════════════
       Detección
       ════════════════════════════════════════════════════════════════ */

    /**
     * Escanea un activo (ficha + custodia) y deja registrados sus faltantes.
     * - No duplica: un campo con pendiente abierto no genera otro.
     * - Auto-resuelve: pendientes cuyo campo ya pasó la validación.
     *
     * @return int número de pendientes nuevos creados.
     */
    public function generarParaEquipo(Equipo $equipo, string $origen = 'import'): int
    {
        $creadas = 0;
        $validados = []; // campo => true cuando pasó la validación

        /* ── 1. Ficha CMDB ─────────────────────────────────────────── */
        foreach (self::CAMPOS_EQUIPO as $campo => [$etiqueta, $severidad, $tipoDato, $catalogo]) {
            $valor = $this->valorEquipo($equipo, $campo);
            $problema = $this->diagnostico($valor, $tipoDato, $catalogo, $campo);

            if ($problema === null) {
                $validados[$campo] = true;
                continue;
            }

            [$tipo, $mensaje] = $problema;
            $creadas += $this->registrar(
                $equipo,
                $this->seccionDe($campo),
                $campo,
                $etiqueta,
                $valor,
                $mensaje,
                $severidad,
                $tipoDato,
                $catalogo,
                $origen
            );
        }

        /* ── 1b. Coherencia entre fechas ──────────────────────────── */
        // Un fin de garantía anterior a la compra es imposible. Se registra
        // sobre `fin_garantia` (el dato a corregir) y se invalida el campo
        // para que `autoResolver` no cierre el pendiente en el mismo barrido.
        if ($equipo->fecha_compra !== null && $equipo->fin_garantia !== null
            && $equipo->fin_garantia->lt($equipo->fecha_compra)) {
            unset($validados['fin_garantia']);

            $creadas += $this->registrar(
                $equipo,
                'ficha',
                'fin_garantia',
                'Fin de garantía (CMDB col 36)',
                $equipo->fin_garantia->toDateString(),
                'La fecha de fin de garantía (' . $equipo->fin_garantia->toDateString()
                    . ') es anterior a la fecha de compra (' . $equipo->fecha_compra->toDateString() . ').',
                'media',
                'fecha',
                null,
                $origen
            );
        }

        /* ── 2. Custodia ──────────────────────────────────────────── */
        $ua = $equipo->usuarioAsignado()->first();

        if (!$ua && trim((string) $equipo->estado_operativo) === 'en_operacion') {
            // EN OPERACIÓN sin custodio: los dos faltantes críticos.
            foreach (['cedula', 'nombre'] as $campo) {
                [$etiqueta, $severidad] = self::CAMPOS_CUSTODIA[$campo];
                $creadas += $this->registrar(
                    $equipo,
                    'custodia',
                    "custodia.{$campo}",
                    $etiqueta,
                    null,
                    'El activo está EN OPERACIÓN y no tiene custodio asignado.',
                    $severidad,
                    'texto',
                    null,
                    $origen
                );
            }
        } elseif ($ua) {
            foreach (self::CAMPOS_CUSTODIA as $campo => [$etiqueta, $severidad]) {
                $valor = (string) ($ua->{$campo} ?? '');
                if ($this->esFaltante($valor)) {
                    $creadas += $this->registrar(
                        $equipo,
                        'custodia',
                        "custodia.{$campo}",
                        $etiqueta,
                        $valor,
                        'El custodio tiene este dato vacío o con placeholder.',
                        $severidad,
                        'texto',
                        null,
                        $origen
                    );
                } else {
                    $validados["custodia.{$campo}"] = true;
                }
            }
        } else {
            // Sin custodia y sin exigencia de custodia: nada pendiente aquí.
            foreach (array_keys(self::CAMPOS_CUSTODIA) as $campo) {
                $validados["custodia.{$campo}"] = true;
            }
        }

        /* ── 3. Auto-resolución de pendientes que ya pasan ─────────── */
        $this->autoResolver($equipo, $validados);

        return $creadas;
    }

    /**
     * Re-escanea todos los activos (botón "Regenerar" o tras importar).
     *
     * @return int pendientes nuevos en total.
     */
    public function regenerarTodo(string $origen = 'escaneo'): int
    {
        $total = 0;
        Equipo::with(['usuarioAsignado', 'tipoRecurso'])
            ->orderBy('id')
            ->chunkById(200, function ($equipos) use (&$total, $origen) {
                foreach ($equipos as $equipo) {
                    try {
                        $total += $this->generarParaEquipo($equipo, $origen);
                    } catch (\Throwable $e) {
                        Log::warning('CORRECCION_SCAN_EQUIPO', ['equipo' => $equipo->id, 'msg' => $e->getMessage()]);
                    }
                }
            });

        return $total;
    }

    /* ════════════════════════════════════════════════════════════════
       Corrección
       ════════════════════════════════════════════════════════════════ */

    /**
     * Aplica la corrección al registro origen (equipo o custodia) y marca
     * la corrección como resuelta.
     *
     * @param bool $reescanear Lote B5: el lote masivo desactiva el re-escaneo
     *                          por fila y re-escanea una sola vez por activo.
     */
    public function resolver(Correccion $correccion, string $valor, ?User $usuario, bool $reescanear = true, ?string $origen = null): void
    {
        $valor = trim($valor);

        if ($correccion->campo === 'tipo') {
            $tipo = TipoRecurso::whereRaw('UPPER(nombre) = ?', [mb_strtoupper($valor)])->first();
            if (!$tipo) {
                throw new \InvalidArgumentException("El tipo de recurso «{$valor}» no existe en el catálogo.");
            }
            $correccion->equipo->update(['tipo_recurso_id' => $tipo->id]);

        } elseif (str_starts_with($correccion->campo, 'custodia.')) {
            $campo = substr($correccion->campo, strlen('custodia.'));
            if (!array_key_exists($campo, self::CAMPOS_CUSTODIA)) {
                throw new \InvalidArgumentException('Campo de custodia no reconocido.');
            }
            $ua = UsuarioAsignado::firstOrNew(['equipo_id' => $correccion->equipo_id]);
            if (!$ua->exists) {
                // Fila nueva: los NOT NULL arrancan vacíos y el escaneo los
                // marcará como pendientes corregibles.
                $ua->nombre = '';
                $ua->cedula = '';
            }
            $ua->{$campo} = $valor;
            $ua->save();

        } elseif ($correccion->tipo_dato === 'fecha') {
            $fecha = $this->parsearFecha($valor);
            if ($fecha === null) {
                throw new \InvalidArgumentException('Fecha inválida. Usa dd/mm/aaaa o aaaa-mm-dd.');
            }
            $correccion->equipo->update([$correccion->campo => $fecha]);

        } elseif ($correccion->tipo_dato === 'catalogo') {
            $opciones = $this->opcionesPara($correccion->catalogo);
            $elegida = $opciones->first(fn ($o) => mb_strtoupper($o['valor']) === mb_strtoupper($valor));
            if (!$elegida) {
                throw new \InvalidArgumentException('El valor no pertenece al catálogo oficial.');
            }
            $correccion->equipo->update([$correccion->campo => $elegida['valor']]);

        } else {
            if (!array_key_exists($correccion->campo, self::CAMPOS_EQUIPO)) {
                throw new \InvalidArgumentException('Campo no corregible.');
            }
            $correccion->equipo->update([$correccion->campo => $valor]);
        }

        $correccion->update(array_filter([
            'estado'          => 'resuelta',
            'valor_corregido' => $valor,
            'resuelta_por'    => $usuario?->id,
            'resuelta_en'     => now(),
            'origen'          => $origen,
        ], fn ($v) => $v !== null));

        // Re-escaneo en cascada: el arreglo puede habilitar (o pedir) más campos.
        if ($reescanear) {
            $this->generarParaEquipo($correccion->equipo->refresh(), 'escaneo');
        }
    }

    /** Marca la corrección como ignorada con justificación obligatoria. */
    public function ignorar(Correccion $correccion, string $justificacion, ?User $usuario): void
    {
        if (mb_strlen(trim($justificacion)) < 5) {
            throw new \InvalidArgumentException('La justificación debe tener al menos 5 caracteres.');
        }

        $correccion->update([
            'estado'        => 'ignorada',
            'justificacion' => trim($justificacion),
            'resuelta_por'  => $usuario?->id,
            'resuelta_en'   => now(),
        ]);
    }

    /**
     * Opciones para el select de la corrección (según su catálogo).
     *
     * @return Collection<int, array{valor: string, etiqueta: string}>
     */
    public function opcionesPara(?string $catalogo): Collection
    {
        return match ($catalogo) {
            'catalogo_estados' => DB::table('catalogo_estados')
                ->orderBy('id')->get()
                ->map(fn ($r) => ['valor' => $r->codigo, 'etiqueta' => $r->nombre . ' (' . $r->codigo . ')']),
            'catalogo_razones' => DB::table('catalogo_razones')
                ->orderBy('id')->get()
                // El equipo guarda el CÓDIGO de la razón (Lote B1): el select
                // ofrece nombre legible pero devuelve el código.
                ->map(fn ($r) => ['valor' => $r->codigo, 'etiqueta' => $r->nombre . ' (' . $r->codigo . ')']),
            'tipo_recursos' => TipoRecurso::orderBy('nombre')->get()
                ->map(fn ($t) => ['valor' => $t->nombre, 'etiqueta' => $t->nombre]),
            null, '' => collect(),
            default => DB::table('diccionarios')
                ->where('grupo', $catalogo)->where('activo', true)
                ->orderBy('valor')->get()
                ->map(fn ($d) => ['valor' => $d->valor, 'etiqueta' => $d->valor]),
        };
    }

    /* ════════════════════════════════════════════════════════════════
       Apoyo interno
       ════════════════════════════════════════════════════════════════ */

    private function valorEquipo(Equipo $equipo, string $campo): ?string
    {
        if ($campo === 'tipo') {
            return $equipo->tipoRecurso?->nombre;
        }
        $valor = $equipo->{$campo} ?? null;
        return $valor === null ? null : (string) $valor;
    }

    /**
     * Diagnóstico de un valor. Devuelve [tipoFallo, mensaje] o null si está bien.
     *
     * `$campo` habilita reglas propias del identificador que no se pueden
     * expresar solo con el tipo de dato (placa `0`, serial sintético…).
     */
    private function diagnostico(?string $valor, string $tipoDato, ?string $catalogo, ?string $campo = null): ?array
    {
        if ($this->esFaltante($valor)) {
            $vacio = trim((string) $valor) === '';
            return ['faltante', $vacio
                ? 'El dato está vacío en el archivo.'
                : 'El dato trae el placeholder «' . trim((string) $valor) . '» en lugar del valor real.'];
        }

        // Placa de relleno: «0» o cualquier secuencia de ceros no identifica
        // el activo (caso CRVBJV2 del CMDB).
        if ($campo === 'placa' && preg_match('/^0+$/', trim((string) $valor)) === 1) {
            return ['faltante', 'La placa trae «' . trim((string) $valor) . '» como relleno en lugar del número real.'];
        }

        // Serial sintético generado por el importador cuando el CMDB no trae
        // serial: el activo se conserva por placa, pero el serial sigue
        // faltando (caso F101951).
        if ($campo === 'serial' && str_starts_with(mb_strtoupper(trim((string) $valor)), 'SIN_SERIAL_')) {
            return ['faltante', 'El activo no trae serial en el CMDB; se guardó un identificador interno.'];
        }

        if ($tipoDato === 'catalogo' && $catalogo) {
            $opciones = $this->opcionesPara($catalogo);
            if ($opciones->isNotEmpty()
                && !$opciones->contains(fn ($o) => mb_strtoupper($o['valor']) === mb_strtoupper(trim((string) $valor)))) {
                return ['catalogo', 'El valor «' . trim((string) $valor) . '» no pertenece al catálogo oficial.'];
            }
        }

        return null;
    }

    private function esFaltante(?string $valor): bool
    {
        $v = mb_strtoupper(trim((string) ($valor ?? '')));
        $v = preg_replace('/[\x00-\x1F\x7F\x{00A0}]+/u', ' ', $v) ?? '';
        return in_array(trim($v), self::PLACEHOLDERS, true);
    }

    private function seccionDe(string $campo): string
    {
        return match ($campo) {
            'serial', 'placa', 'tipo' => 'identidad',
            'estado_operativo', 'razon_estado', 'estado_uso', 'administrado' => 'catalogos',
            default => 'ficha',
        };
    }

    private function registrar(
        Equipo $equipo,
        string $seccion,
        string $campo,
        string $etiqueta,
        ?string $valorActual,
        string $mensaje,
        string $severidad,
        string $tipoDato,
        ?string $catalogo,
        string $origen
    ): int {
        // Duplicados: no se re-abre lo que ya está pendiente, pero SÍ se
        // refresca el diagnóstico. Un pendiente pudo nacer con un mensaje
        // que dejó de aplicar (p. ej. S5 creó la custodia y ya no es
        // «sin custodio», solo falta la cédula).
        $pendiente = Correccion::where('equipo_id', $equipo->id)
            ->where('campo', $campo)
            ->where('estado', 'pendiente')
            ->first();

        if ($pendiente !== null) {
            $pendiente->update([
                'valor_actual' => $valorActual,
                'mensaje'      => $mensaje,
                'severidad'    => $severidad,
                'tipo_dato'    => $tipoDato,
                'catalogo'     => $catalogo,
            ]);

            return 0;
        }

        // …ni lo que el analista YA JUSTIFICÓ como N/A (justificación
        // pegajosa): re-marcar un faltante archivado obligaría a repetir
        // el trabajo en cada escaneo. Si el dato cambia, la corrección de
        // ese campo se registra de nuevo al editar el activo.
        $justificada = Correccion::where('equipo_id', $equipo->id)
            ->where('campo', $campo)
            ->where('estado', 'ignorada')
            ->exists();

        if ($justificada) return 0;

        Correccion::create([
            'equipo_id'    => $equipo->id,
            'seccion'      => $seccion,
            'campo'        => $campo,
            'etiqueta'     => $etiqueta,
            'valor_actual' => $valorActual,
            'mensaje'      => $mensaje,
            'severidad'    => $severidad,
            'tipo_dato'    => $tipoDato,
            'catalogo'     => $catalogo,
            'estado'       => 'pendiente',
            'origen'       => $origen,
        ]);

        return 1;
    }

    /** Cierra pendientes cuyo campo ya pasó la validación en este escaneo. */
    private function autoResolver(Equipo $equipo, array $validados): void
    {
        if (!$validados) return;

        Correccion::where('equipo_id', $equipo->id)
            ->where('estado', 'pendiente')
            ->whereIn('campo', array_keys($validados))
            ->update([
                'estado'          => 'resuelta',
                'valor_corregido' => DB::raw('valor_actual'),
                'justificacion'   => 'Auto-resuelta: el campo ya contiene un valor válido.',
                'resuelta_en'     => now(),
            ]);
    }

    private function parsearFecha(string $valor): ?string
    {
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y'] as $formato) {
            try {
                $f = Carbon::createFromFormat($formato, $valor);
                if ($f && $f->format($formato) === $valor) {
                    return $f->toDateString();
                }
            } catch (\Throwable) {
                continue;
            }
        }
        return null;
    }

    /* ════════════════════════════════════════════════════════════════
       Organización de la corrección masiva (Lote B5)
       ════════════════════════════════════════════════════════════════ */

    /**
     * Resumen de pendientes AGRUPADOS por campo: convierte miles de filas
     * en ~20 frentes de trabajo con acción masiva.
     *
     * @return Collection<int, object{campo: string, etiqueta: string, seccion: string, severidad: string, tipo_dato: string, catalogo: ?string, pendientes: int, ejemplo: ?string}>
     */
    public function resumenPorCampo(): Collection
    {
        return Correccion::query()
            ->where('estado', 'pendiente')
            ->selectRaw('campo, etiqueta, seccion, severidad, tipo_dato, catalogo, COUNT(*) as pendientes, MIN(valor_actual) as ejemplo')
            ->groupBy('campo', 'etiqueta', 'seccion', 'severidad', 'tipo_dato', 'catalogo')
            ->orderByRaw("CASE severidad WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END")
            ->orderByDesc('pendientes')
            ->get();
    }

    /**
     * Activos con más pendientes (peor primero): permite completar la ficha
     * de un activo de una sola vez.
     *
     * @return Collection<int, object{equipo_id: int, pendientes: int, altas: int, equipo: ?Equipo}>
     */
    public function topActivos(int $limite = 100): Collection
    {
        $filas = Correccion::query()
            ->where('estado', 'pendiente')
            ->whereNotNull('equipo_id')
            ->selectRaw('equipo_id, COUNT(*) as pendientes, SUM(severidad = \'alta\') as altas')
            ->groupBy('equipo_id')
            ->orderByDesc('pendientes')
            ->limit($limite)
            ->get();

        $equipos = Equipo::with('tipoRecurso:id,nombre')
            ->whereIn('id', $filas->pluck('equipo_id'))
            ->get()
            ->keyBy('id');

        return $filas->map(fn ($f) => (object) [
            'equipo_id' => $f->equipo_id,
            'pendientes' => (int) $f->pendientes,
            'altas' => (int) $f->altas,
            'equipo' => $equipos->get($f->equipo_id),
        ]);
    }

    /**
     * Aplica una corrección (o justificación) EN LOTE a todos los pendientes
     * de un campo — p. ej. «justificar las 303 órdenes de remisión vacías» o
     * «poner FUNCIONES PROPIAS como estado de uso en las 74 filas».
     *
     * Re-escanea una sola vez por activo afectado (no por fila).
     *
     * @param array<int, int>|null $ids Restringe el lote a correcciones concretas.
     * @return array{aplicadas: int, errores: array<int, string>, total: int}
     */
    public function aplicarMasivo(string $campo, string $modo, ?string $valor, ?string $justificacion, ?array $ids, ?User $usuario): array
    {
        $query = Correccion::query()
            ->where('campo', $campo)
            ->where('estado', 'pendiente');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $correcciones = $query->orderBy('id')->get();

        $aplicadas = 0;
        $errores = [];
        $equiposAfectados = [];

        foreach ($correcciones as $correccion) {
            try {
                if ($modo === 'ignorar') {
                    $this->ignorar($correccion, (string) $justificacion, $usuario);
                } else {
                    $this->resolver($correccion, (string) $valor, $usuario, reescanear: false, origen: 'lote');
                    if ($correccion->equipo_id) $equiposAfectados[$correccion->equipo_id] = true;
                }
                $aplicadas++;
            } catch (\Throwable $e) {
                $errores[] = sprintf('#%d (%s): %s', $correccion->id, $correccion->equipo?->placa ?? '?', $e->getMessage());
            }
        }

        // Un solo re-escaneo por activo afectado.
        Equipo::whereIn('id', array_keys($equiposAfectados))
            ->with('usuarioAsignado', 'tipoRecurso')
            ->chunkById(100, function ($equipos) {
                foreach ($equipos as $equipo) {
                    try {
                        $this->generarParaEquipo($equipo, 'escaneo');
                    } catch (\Throwable $e) {
                        Log::warning('CORRECCION_MASIVA_RESCAN', ['equipo' => $equipo->id, 'msg' => $e->getMessage()]);
                    }
                }
            });

        return ['aplicadas' => $aplicadas, 'errores' => $errores, 'total' => $correcciones->count()];
    }

    /* ════════════════════════════════════════════════════════════════
       Inferencia por equipos similares (Lote B6)
       ════════════════════════════════════════════════════════════════ */

    /** Campos inferibles de los hermanos (misma marca y modelo). */
    public const CAMPOS_INFERIBLES = [
        'procesador', 'ram', 'disco', 'sistema_operativo',
        'tipo_propiedad', 'estado_uso', 'administrado',
        'fecha_compra', 'fin_garantia',
    ];

    /** Umbral mínimo para aplicar automáticamente una sugerencia. */
    public const MIN_HERMANOS = 2;
    public const MIN_CONFIANZA = 0.7;

    /**
     * Infiere el valor probable de un campo a partir de los «hermanos»:
     * activos del MISMO modelo y marca (fallback: mismo modelo; luego mismo
     * tipo y marca) que sí tienen el dato.
     *
     *  - Campos de texto/catálogo → el valor MÁS COMÚN (moda).
     *  - Fechas → la mediana de los hermanos (lotes de compra comparten
     *    garantía/compra).
     *
     * @return null|array{valor: string, confianza: float, n: int, nivel: string}
     */
    public function inferirPorHermanos(Equipo $equipo, string $campo): ?array
    {
        if (! in_array($campo, self::CAMPOS_INFERIBLES, true)) {
            return null;
        }

        $niveles = [
            'marca+modelo' => fn ($q) => $q->where('marca', $equipo->marca)->where('modelo', $equipo->modelo),
            'modelo'        => fn ($q) => $q->where('modelo', $equipo->modelo),
            'tipo+marca'    => fn ($q) => $q->where('tipo_recurso_id', $equipo->tipo_recurso_id)->where('marca', $equipo->marca),
        ];

        $esFecha = in_array($campo, ['fecha_compra', 'fin_garantia'], true);

        foreach ($niveles as $nivel => $aplicar) {
            if ($nivel === 'marca+modelo' && (trim((string) $equipo->marca) === '' || trim((string) $equipo->modelo) === '')) {
                continue;
            }
            if ($nivel === 'modelo' && trim((string) $equipo->modelo) === '') {
                continue;
            }

            $valores = Equipo::query()
                ->whereKeyNot($equipo->id)
                ->whereNull('deleted_at')
                ->where($campo, '!=', '')
                ->whereNotNull($campo)
                ->when($esFecha, fn ($q) => $q->whereDate($campo, '>=', '2000-01-01'))
                ->where($aplicar)
                ->pluck($campo)
                ->map(fn ($v) => trim((string) $v))
                ->filter(fn ($v) => $v !== '' && ! $this->esFaltante($v))
                ->values();

            if ($valores->count() < self::MIN_HERMANOS) {
                continue; // prueba el siguiente nivel
            }

            if ($esFecha) {
                $fechas = $valores->sort()->values();
                $mediana = $fechas->count() % 2 === 1
                    ? $fechas[$fechas->count() >> 1]
                    : $fechas[$fechas->count() / 2 - 1]; // conservador: la menor de las dos centrales
                $candidata = substr((string) $mediana, 0, 10);

                // Coherencia entre fechas: nunca se infiere una garantía
                // anterior a la compra (ni una compra posterior a la
                // garantía). Si este nivel de hermanos no la respeta, se
                // prueba el siguiente; si ninguno sirve, no hay inferencia.
                if ($campo === 'fin_garantia' && $equipo->fecha_compra !== null
                    && $candidata < $equipo->fecha_compra->toDateString()) {
                    continue;
                }
                if ($campo === 'fecha_compra' && $equipo->fin_garantia !== null
                    && $candidata > $equipo->fin_garantia->toDateString()) {
                    continue;
                }

                return [
                    'valor' => $candidata,
                    'confianza' => 1.0,
                    'n' => $valores->count(),
                    'nivel' => $nivel,
                ];
            }

            $conteo = $valores->countBy();
            $valor = $conteo->keys()->first();
            $n = $conteo[$valor];
            $confianza = $n / $valores->count();

            return [
                'valor' => (string) $valor,
                'confianza' => round($confianza, 2),
                'n' => $valores->count(),
                'nivel' => $nivel,
            ];
        }

        return null;
    }

    /**
     * Sugerencias para una página de correcciones visibles (vista Lista):
     * [correccion_id => {valor, confianza, n, nivel}].
     *
     * @param iterable<Correccion> $correcciones
     * @return array<int, array{valor: string, confianza: float, n: int, nivel: string}>
     */
    public function sugerenciasPara($correcciones): array
    {
        $mapa = [];
        $cache = [];

        foreach ($correcciones as $correccion) {
            if (! in_array($correccion->campo, self::CAMPOS_INFERIBLES, true)) {
                continue;
            }

            $equipo = $correccion->equipo;
            if (! $equipo) {
                continue;
            }

            $clave = $equipo->id . '|' . $correccion->campo;

            $mapa[$correccion->id] = $cache[$clave] ??= $this->inferirPorHermanos($equipo, $correccion->campo);
        }

        return array_filter($mapa);
    }

    /**
     * Aplica las sugerencias de un frente SOLO donde la inferencia es sólida
     * (≥ MIN_HERMANOS hermanos y confianza ≥ MIN_CONFIANZA). Cada pendiente
     * recibe SU PROPIO valor inferido (no uno compartido). Los demás quedan
     * pendientes para revisión manual.
     *
     * @return array{aplicadas: int, sin_inferencia: int, errores: array<int, string>, detalle: array<int, string>}
     */
    public function aplicarSugerencias(string $campo, ?User $usuario): array
    {
        $correcciones = Correccion::query()
            ->where('campo', $campo)
            ->where('estado', 'pendiente')
            ->with('equipo')
            ->orderBy('id')
            ->get();

        $aplicadas = 0;
        $sinInferencia = 0;
        $errores = [];
        $detalle = [];
        $equiposAfectados = [];

        foreach ($correcciones as $correccion) {
            $inferencia = $this->inferirPorHermanos($correccion->equipo, $campo);

            if ($inferencia === null || $inferencia['n'] < self::MIN_HERMANOS || $inferencia['confianza'] < self::MIN_CONFIANZA) {
                $sinInferencia++;
                continue;
            }

            try {
                $this->resolver($correccion, $inferencia['valor'], $usuario, reescanear: false, origen: 'inferido');
                if ($correccion->equipo_id) $equiposAfectados[$correccion->equipo_id] = true;
                $aplicadas++;
                $detalle[] = sprintf('%s ← %s (%d hermanos, %s, %.0f%%)', $correccion->equipo?->placa ?: '#' . $correccion->equipo_id, $inferencia['valor'], $inferencia['n'], $inferencia['nivel'], $inferencia['confianza'] * 100);
            } catch (\Throwable $e) {
                $errores[] = sprintf('#%d: %s', $correccion->id, $e->getMessage());
            }
        }

        // Un solo re-escaneo por activo afectado.
        Equipo::whereIn('id', array_keys($equiposAfectados))
            ->with('usuarioAsignado', 'tipoRecurso')
            ->chunkById(100, function ($equipos) {
                foreach ($equipos as $equipo) {
                    try {
                        $this->generarParaEquipo($equipo, 'escaneo');
                    } catch (\Throwable $e) {
                        Log::warning('SUGERENCIA_RESCAN', ['equipo' => $equipo->id, 'msg' => $e->getMessage()]);
                    }
                }
            });

        return ['aplicadas' => $aplicadas, 'sin_inferencia' => $sinInferencia, 'errores' => $errores, 'detalle' => $detalle];
    }
}
