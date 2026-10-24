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
    /** Placeholders que cuentan como valor faltante. */
    public const PLACEHOLDERS = [
        '', 'PENDIENTE', 'PENDIENTE ESTADO', 'N/A', 'NA', 'SIN REGISTRO',
        'SIN ASIGNAR', 'NO APLICA', 'NO APLICA.', 'NULL', 'NINGUNO', '-', '--', '.',
    ];

    /**
     * Campos del activo (ficha CMDB) que se auditan.
     * campo => [etiqueta, severidad, tipo_dato, catalogo|null]
     */
    public const CAMPOS_EQUIPO = [
        'serial'              => ['Serial (CMDB col 22)',            'alta',  'texto',    null],
        'placa'               => ['Placa (CMDB col 23)',             'alta',  'texto',    null],
        'tipo'                => ['Tipo de recurso (CMDB col 21)',   'media', 'catalogo', 'tipo_recursos'],
        'marca'               => ['Marca (CMDB col 24)',             'media', 'texto',    null],
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
            $problema = $this->diagnostico($valor, $tipoDato, $catalogo);

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
     */
    public function resolver(Correccion $correccion, string $valor, ?User $usuario): void
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

        $correccion->update([
            'estado'          => 'resuelta',
            'valor_corregido' => $valor,
            'resuelta_por'    => $usuario?->id,
            'resuelta_en'     => now(),
        ]);

        // Re-escaneo en cascada: el arreglo puede habilitar (o pedir) más campos.
        $this->generarParaEquipo($correccion->equipo->refresh(), 'escaneo');
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
                ->map(fn ($r) => ['valor' => $r->nombre, 'etiqueta' => $r->nombre]),
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
     */
    private function diagnostico(?string $valor, string $tipoDato, ?string $catalogo): ?array
    {
        if ($this->esFaltante($valor)) {
            $vacio = trim((string) $valor) === '';
            return ['faltante', $vacio
                ? 'El dato está vacío en el archivo.'
                : 'El dato trae el placeholder «' . trim((string) $valor) . '» en lugar del valor real.'];
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
        $existe = Correccion::where('equipo_id', $equipo->id)
            ->where('campo', $campo)
            ->where('estado', 'pendiente')
            ->exists();

        if ($existe) return 0;

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
}
