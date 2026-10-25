<?php

namespace App\Console\Commands;

use App\Models\Correccion;
use App\Models\Equipo;
use App\Services\CorreccionService;
use App\Services\Importadores\CMDBMapperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Auto-reparación del CMDB local (Lote B7).
 *
 * Identifica y repara automáticamente lo que los propios datos permiten
 * resolver, con reglas VALIDADAS contra el archivo antes de aplicar:
 *
 *  S1 Shortname del custodio — patrón [inicial 1er nombre][primer apellido]
 *     [últimos 3 dígitos de cédula], validado en el 87 % de los shortnames
 *     reales del archivo (93/106); se genera también para 1 valor numérico
 *     corrupto (cédula mal ubicada en la columna shortname).
 *
 *  S2 Tipo de propiedad — regla del PROGRAMA del usuario (col 14 del CMDB),
 *     validada 100 %/100 %/93 %: PAC → PROPIO · PROYECTOS → PROYECTO -
 *     GOBERNACION DEL TOLIMA · RECURSOS PROPIOS → LEASING BANCOLOMBIA 2.
 *
 *  S3 Especificaciones y fechas — inferencia por hermanos (misma marca y
 *     modelo; moda/mediana) con umbral n≥2 y ≥70 % de acuerdo: disco, RAM,
 *     procesador, sistema operativo, fin de garantía, tipo de propiedad
 *     (para los que la regla S2 no cubra).
 *
 *  S4 «NO APLICA» declarado por el archivo — el propio CMDB declara el dato
 *     como no aplicable (p. ej. hostnames de tablets/impresoras): se archiva
 *     con la causa literal, no queda pendiente.
 *
 *  S5 Custodia parcial — fila con cédula placeholder pero nombre válido
 *     (p. ej. placa 10095976): se crea la custodia con el bloque 1-14 del
 *     archivo y sólo la cédula queda pendiente.
 *
 *  S6 Checklist constante — si el 100 % de los `checklist_responsable` no
 *     vacíos del inventario coinciden en un mismo valor, los vacíos se
 *     completan con ese valor (276/276 filas del CMDB real).
 *
 * Todo lo aplicado queda con origen 'inferido' (badge en la UI) y es
 * reversible editando el activo. `--dry-run` muestra el plan sin escribir.
 */
class AutoRepararCmdb extends Command
{
    protected $signature = 'cmdb:auto-reparar
        {archivo? : Ruta del Excel del CMDB (para las reglas que leen el archivo)}
        {--dry-run : Analiza y muestra el plan sin escribir}';

    protected $description = 'Auto-repara el CMDB: shortname por patrón, tipo de propiedad por regla del programa, specs por hermanos, NO APLICA justificado y custodia parcial';

    private const ORIGEN = 'inferido';

    /** Reglas S2: programa del usuario → tipo de propiedad (validadas en análisis). */
    private const REGLAS_PROGRAMA = [
        'PAC'                => ['valor' => 'PROPIO',                            'confianza' => 1.0,  'evidencia' => '117/117 filas'],
        'PROYECTOS'          => ['valor' => 'PROYECTO - GOBERNACION DEL TOLIMA', 'confianza' => 1.0,  'evidencia' => '9/9 filas'],
        'PROYECTO'           => ['valor' => 'PROYECTO - GOBERNACION DEL TOLIMA', 'confianza' => 1.0,  'evidencia' => '9/9 filas'],
        'RECURSOS PROPIOS'   => ['valor' => 'LEASING BANCOLOMBIA 2',             'confianza' => 0.93, 'evidencia' => '27/29 filas'],
        'RECURSO PROPIO'     => ['valor' => 'LEASING BANCOLOMBIA 2',             'confianza' => 0.93, 'evidencia' => '27/29 filas'],
    ];

    public function handle(): int
    {
        $this->info('════ AUTO-REPARACIÓN DEL CMDB ' . ($this->option('dry-run') ? '(DRY-RUN)' : '') . ' ════');

        $antes = $this->conteoPendientes();

        $reporte = [
            'S1_shortname_patron'  => 0,
            'S2_tipo_propiedad_regla' => 0,
            'S3_hermanos' => [],
            'S4_no_aplica_justificado' => 0,
            'S5_custodia_parcial' => 0,
            'S6_checklist_constante' => 0,
        ];

        $archivo = $this->argument('archivo');
        $filasCmdb = $archivo ? $this->leerCmdb($archivo) : null;
        if ($archivo && $filasCmdb === null) {
            $this->error('No se pudo leer el archivo: ' . $archivo);

            return self::FAILURE;
        }

        /* ── S1: shortname por patrón (nombre + cédula ya están en la BD) ── */
        $reporte['S1_shortname_patron'] = $this->s1Shortname();

        /* ── S2: tipo de propiedad por regla del programa (col 14 del archivo) ── */
        $reporte['S2_tipo_propiedad_regla'] = $this->s2TipoPropiedad($filasCmdb);

        /* ── S3: especificaciones y fechas por hermanos ── */
        $svc = app(CorreccionService::class);
        foreach (['disco', 'ram', 'procesador', 'sistema_operativo', 'fin_garantia', 'tipo_propiedad'] as $campo) {
            if ($this->option('dry-run')) {
                // Contar sin escribir: cuántos pendientes tendrían inferencia sólida
                $solidas = 0;
                Correccion::where('campo', $campo)->where('estado', 'pendiente')
                    ->with('equipo')->chunk(200, function ($correcciones) use (&$solidas, $svc, $campo) {
                        foreach ($correcciones as $c) {
                            $i = $svc->inferirPorHermanos($c->equipo, $campo);
                            if ($i && $i['n'] >= CorreccionService::MIN_HERMANOS && $i['confianza'] >= CorreccionService::MIN_CONFIANZA) $solidas++;
                        }
                    });
                if ($solidas > 0) $reporte['S3_hermanos'][$campo] = $solidas;
            } else {
                $r = $svc->aplicarSugerencias($campo, null);
                if ($r['aplicadas'] > 0) {
                    $reporte['S3_hermanos'][$campo] = $r['aplicadas'];
                }
            }
        }

        /* ── S4: «NO APLICA» declarado por el archivo ── */
        $reporte['S4_no_aplica_justificado'] = $this->s4NoAplica($filasCmdb);

        /* ── S5: custodia parcial (cédula placeholder, nombre válido) ── */
        $reporte['S5_custodia_parcial'] = $this->s5CustodiaParcial($filasCmdb);

        /* ── S6: checklist responsable constante en todo el inventario ── */
        $reporte['S6_checklist_constante'] = $this->s6ChecklistConstante();

        /* ── Re-escaneo y resumen ── */
        if (! $this->option('dry-run')) {
            $svc->regenerarTodo('escaneo');
        }
        $despues = $this->conteoPendientes();

        $this->newLine();
        $this->line('── PLAN/APLICADO ──');
        $this->line(sprintf('  S1 shortname por patrón (87%% validado):        %d', $reporte['S1_shortname_patron']));
        $this->line(sprintf('  S2 tipo de propiedad por regla del programa:    %d', $reporte['S2_tipo_propiedad_regla']));
        $this->line('  S3 specs/fechas por hermanos:                   ' . collect($reporte['S3_hermanos'])->map(fn ($n, $c) => "$c=$n")->implode(' · '));
        $this->line(sprintf('  S4 «NO APLICA» del archivo justificado:         %d', $reporte['S4_no_aplica_justificado']));
        $this->line(sprintf('  S5 custodia parcial creada:                    %d', $reporte['S5_custodia_parcial']));
        $this->line(sprintf('  S6 checklist responsable constante:            %d', $reporte['S6_checklist_constante']));

        $this->newLine();
        $this->line('── PENDIENTES ──');
        $this->table(
            ['campo', 'antes', 'después'],
            collect($antes)->map(fn ($n, $campo) => [$campo, $n, $despues[$campo] ?? 0])->values()->toArray()
        );
        $totalAntes = array_sum($antes); $totalDespues = array_sum($despues);
        $this->newLine();
        $this->info(sprintf('TOTAL: %d → %d pendientes (−%d reparados automáticamente)', $totalAntes, $totalDespues, $totalAntes - $totalDespues));

        return self::SUCCESS;
    }

    /* ═══════════════════════ S1: shortname por patrón ═══════════════════════ */

    private function s1Shortname(): int
    {
        $aplicados = 0;

        Correccion::where('campo', 'custodia.shortname')
            ->where('estado', 'pendiente')
            ->with('equipo.usuarioAsignado')
            ->chunkById(100, function ($correcciones) use (&$aplicados) {
                foreach ($correcciones as $c) {
                    $ua = $c->equipo?->usuarioAsignado;
                    $shortname = $ua ? $this->generarShortname($ua->nombre, $ua->cedula) : null;

                    if ($shortname === null) {
                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line(sprintf('  S1 %s ← %s (de %s / cédula %s)', $ua->equipo->placa ?: $ua->equipo->serial, $shortname, $ua->nombre, $ua->cedula));
                        $aplicados++;
                        continue;
                    }

                    try {
                        $ua->forceFill(['shortname' => $shortname])->save();
                        app(CorreccionService::class)->resolver($c, $shortname, null, reescanear: false, origen: self::ORIGEN);
                        $aplicados++;
                    } catch (\Throwable $e) {
                        Log::warning('AUTO_S1', ['id' => $c->id, 'msg' => $e->getMessage()]);
                    }
                }
            });

        // Basura numérica en shortname (cédula mal ubicada en el archivo)
        if (! $this->option('dry-run')) {
            foreach (\App\Models\UsuarioAsignado::whereRaw("shortname REGEXP '^[0-9.]+$'")->get() as $ua) {
                $shortname = $this->generarShortname($ua->nombre, $ua->cedula);
                if ($shortname !== null) {
                    $ua->forceFill(['shortname' => $shortname])->save();
                }
            }
        }

        return $aplicados;
    }

    /** [inicial 1er nombre][primer apellido sin tildes][últimos 3 de la cédula] */
    private function generarShortname(?string $nombre, ?string $cedula): ?string
    {
        $partes = preg_split('/\s+/', mb_strtoupper(trim((string) $nombre)));
        $partes = array_values(array_filter($partes));
        $cedula = preg_replace('/\D/', '', (string) $cedula);

        if (count($partes) < 2 || $partes[0] === '' || strlen($cedula) < 5) {
            return null;
        }

        $inicial = mb_substr($this->sinTildes($partes[0]), 0, 1);
        $apellido = $this->sinTildes(count($partes) >= 3 ? $partes[count($partes) - 2] : $partes[1]);

        if (mb_strlen($apellido) < 3) {
            return null;
        }

        return $inicial . $apellido . substr($cedula, -3);
    }

    private function sinTildes(string $s): string
    {
        return strtr($s, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);
    }

    /* ══════════════════ S2: tipo de propiedad por regla ══════════════════ */

    private function s2TipoPropiedad(?array $filasCmdb): int
    {
        if ($filasCmdb === null) {
            $this->warn('  S2 omitida: requiere el archivo del CMDB (pásalo como argumento).');

            return 0;
        }

        $aplicados = 0;
        $reportes = [];

        Correccion::where('campo', 'tipo_propiedad')
            ->where('estado', 'pendiente')
            ->with('equipo')
            ->chunkById(100, function ($correcciones) use ($filasCmdb, &$aplicados, &$reportes) {
                foreach ($correcciones as $c) {
                    $fila = $this->buscarFilaPorEquipo($filasCmdb, $c->equipo);
                    $programa = $fila['programa'] ?? null;

                    if ($programa === null || ! isset(self::REGLAS_PROGRAMA[$programa])) {
                        continue;
                    }

                    $regla = self::REGLAS_PROGRAMA[$programa];

                    if ($this->option('dry-run')) {
                        $reportes[] = sprintf('%s ← %s (regla %s, %s)', $c->equipo?->placa ?: $c->equipo?->serial, $regla['valor'], $programa, $regla['evidencia']);
                        $aplicados++;
                        continue;
                    }

                    try {
                        app(CorreccionService::class)->resolver($c, $regla['valor'], null, reescanear: false, origen: self::ORIGEN);
                        $aplicados++;
                    } catch (\Throwable $e) {
                        Log::warning('AUTO_S2', ['id' => $c->id, 'msg' => $e->getMessage()]);
                    }
                }
            });

        foreach (array_slice($reportes, 0, 5) as $r) {
            $this->line('  S2 ' . $r);
        }

        return $aplicados;
    }

    /* ══════════════════ S4: «NO APLICA» del archivo ══════════════════ */

    private function s4NoAplica(?array $filasCmdb = null): int
    {
        $justificados = 0;
        $causa = 'El propio CMDB declara «NO APLICA» para este dato del activo.';

        // El importador normaliza «NO APLICA» a null, así que el valor
        // original se recupera del archivo (por placa/serial).
        $mapaFila = [];
        if ($filasCmdb !== null) {
            foreach ($filasCmdb as $f) {
                $mapaFila[$f['placa'] ?? ''] = $f;
                $mapaFila['S:' . ($f['serial'] ?? '')] = $f;
            }
        }

        Correccion::where('estado', 'pendiente')
            ->whereIn('campo', ['nombre_equipo', 'orden_remision', 'observaciones'])
            ->with('equipo')
            ->chunkById(100, function ($correcciones) use (&$justificados, $causa, $filasCmdb, &$mapaFila) {
                foreach ($correcciones as $c) {
                    $delArchivo = $filasCmdb !== null && $this->filaDeclaraNoAplica($mapaFila, $c->equipo, $c->campo);
                    $deLaBd = in_array(strtoupper(trim((string) $c->valor_actual)), ['NO APLICA', 'N/A'], true);

                    if (! $delArchivo && ! $deLaBd) {
                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line(sprintf('  S4 %s · %s ← justificar (NO APLICA del archivo)', $c->equipo?->placa ?: '?', $c->campo));
                        $justificados++;
                        continue;
                    }

                    try {
                        app(CorreccionService::class)->ignorar($c, $causa, null);
                        $justificados++;
                    } catch (\Throwable $e) {
                        Log::warning('AUTO_S4', ['id' => $c->id, 'msg' => $e->getMessage()]);
                    }
                }
            });

        return $justificados;
    }

    /** ¿La fila original del archivo declara «NO APLICA» para el campo? */
    private function filaDeclaraNoAplica(array $mapaFila, ?Equipo $equipo, string $campo): bool
    {
        if ($equipo === null) {
            return false;
        }

        $fila = $mapaFila[mb_strtoupper(trim((string) $equipo->placa))]
            ?? $mapaFila['S:' . mb_strtoupper(trim((string) $equipo->serial))]
            ?? null;

        if ($fila === null) {
            return false;
        }

        $columna = match ($campo) {
            'nombre_equipo' => $fila['nombre_equipo_archivo'] ?? null,
            default => null,
        };

        return $columna !== null && strtoupper(trim($columna)) === 'NO APLICA';
    }

    /* ══════════════════ S5: custodia parcial ══════════════════ */

    private function s5CustodiaParcial(?array $filasCmdb): int
    {
        if ($filasCmdb === null) {
            return 0;
        }

        $creadas = 0;

        // Activos EN OPERACIÓN sin custodia cuya fila del archivo tiene
        // nombre válido pero cédula placeholder (p. ej. placa 10095976).
        $sinCustodia = Equipo::where('estado_operativo', 'en_operacion')
            ->whereDoesntHave('usuarioAsignado')
            ->get();

        foreach ($sinCustodia as $equipo) {
            $fila = $this->buscarFilaPorEquipo($filasCmdb, $equipo);

            if ($fila === null || trim((string) $fila['nombre_usuario']) === '' || $fila['cedula_valida']) {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line(sprintf('  S5 %s ← custodia para %s (cédula queda pendiente)', $equipo->placa ?: $equipo->serial, $fila['nombre_usuario']));
                $creadas++;
                continue;
            }

            try {
                \App\Models\UsuarioAsignado::create([
                    'equipo_id' => $equipo->id,
                    'nombre' => $fila['nombre_usuario'],
                    'cedula' => '',
                    'modalidad' => 'asignado',
                    'cargo' => $fila['cargo'] ?? null,
                    'area' => $fila['area'] ?? null,
                    'ciudad' => $fila['ciudad'] ?? null,
                    'departamento' => $fila['departamento'] ?? null,
                    'desde' => now(),
                ]);

                app(\App\Services\CustodiaService::class)->sincronizarSnapshot($equipo, $equipo->usuarioAsignado()->first());
                $creadas++;
            } catch (\Throwable $e) {
                Log::warning('AUTO_S5', ['equipo' => $equipo->id, 'msg' => $e->getMessage()]);
            }
        }

        return $creadas;
    }

    /* ══════════════════ S6: checklist responsable constante ══════════════════ */

    /**
     * Si el 100 % de los `checklist_responsable` no vacíos del inventario
     * coinciden, los vacíos se completan con ese valor. La regla solo aplica
     * cuando hay un único valor observado (constante real), nunca con dos o
     * más candidatos: en ese caso el dato exige intervención humana.
     */
    private function s6ChecklistConstante(): int
    {
        $valores = Equipo::query()
            ->whereNotNull('checklist_responsable')
            ->where('checklist_responsable', '<>', '')
            ->select('checklist_responsable', DB::raw('COUNT(*) as total'))
            ->groupBy('checklist_responsable')
            ->orderByDesc('total')
            ->get();

        // 0 valores ⇒ nada que inferir; ≥2 valores ⇒ no es constante.
        if ($valores->count() !== 1) {
            return 0;
        }

        $constante = trim((string) $valores->first()->checklist_responsable);
        if ($constante === '') {
            return 0;
        }

        $aplicados = 0;

        Correccion::where('campo', 'checklist_responsable')
            ->where('estado', 'pendiente')
            ->with('equipo')
            ->chunkById(100, function ($correcciones) use (&$aplicados, $constante) {
                foreach ($correcciones as $c) {
                    $equipo = $c->equipo;
                    if ($equipo === null) {
                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line(sprintf('  S6 %s ← checklist «%s»', $equipo->placa ?: $equipo->serial, $constante));
                        $aplicados++;
                        continue;
                    }

                    try {
                        $equipo->forceFill(['checklist_responsable' => $constante])->save();
                        app(CorreccionService::class)->resolver(
                            $c,
                            $constante,
                            null,
                            reescanear: false,
                            origen: self::ORIGEN
                        );
                        $aplicados++;
                    } catch (\Throwable $e) {
                        Log::warning('AUTO_S6', ['correccion' => $c->id, 'msg' => $e->getMessage()]);
                    }
                }
            });

        return $aplicados;
    }

    /* ══════════════════ Apoyo: lectura del archivo CMDB ══════════════════ */

    private function leerCmdb(string $ruta): ?array
    {
        try {
            $hoja = IOFactory::createReaderForFile($ruta)
                ->setReadDataOnly(true)
                ->load($ruta)
                ->getSheetByName('Microinformatica');

            if ($hoja === null) {
                return null;
            }

            $filas = [];
            foreach ($hoja->toArray(null, true, true, true) as $indice => $fila) {
                if ($indice < 3) {
                    continue;
                }

                $g = fn (string $col) => trim((string) ($fila[$col] ?? ''));
                $placa = CMDBMapperService::normalizeIdentifier($g('W')); // col 23
                $serial = CMDBMapperService::normalizeIdentifier($g('V')); // col 22

                if ($placa === null && $serial === null) {
                    continue;
                }

                $filas[] = [
                    'placa' => $placa,
                    'serial' => $serial,
                    'nombre_equipo_archivo' => $g('AA'),        // col 27 (original, antes de normalizar)
                    'programa' => mb_strtoupper($g('N')),       // col 14 (tipo de recurso del usuario)
                    'nombre_usuario' => $g('H'),                 // col 8
                    'cedula_valida' => (bool) preg_match('/^\d{5,}$/', CMDBMapperService::normalizeIdentifier($g('F'), true) ?: ''), // col 6
                    'cargo' => $g('K') ?: null,                  // col 11
                    'area' => $g('L') ?: null,                   // col 12
                    'ciudad' => $g('J') ?: null,                 // col 10
                    'departamento' => $g('I') ?: null,           // col 9
                ];
            }

            return $filas;
        } catch (\Throwable $e) {
            Log::error('AUTO_LEER_CMDB', ['msg' => $e->getMessage()]);

            return null;
        }
    }

    private function buscarFilaPorEquipo(array $filas, ?Equipo $equipo): ?array
    {
        if ($equipo === null) {
            return null;
        }

        $placa = mb_strtoupper(trim((string) $equipo->placa));
        $serial = mb_strtoupper(trim((string) $equipo->serial));

        foreach ($filas as $f) {
            if (($f['placa'] !== null && mb_strtoupper((string) $f['placa']) === $placa)
                || ($f['serial'] !== null && mb_strtoupper((string) $f['serial']) === $serial)) {
                return $f;
            }
        }

        return null;
    }

    /** @return array<string, int> pendientes por campo antes/después */
    private function conteoPendientes(): array
    {
        return Correccion::where('estado', 'pendiente')
            ->selectRaw('campo, COUNT(*) as n')
            ->groupBy('campo')
            ->pluck('n', 'campo')
            ->all();
    }
}
