<?php

namespace App\Domain\Activos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * EstadoOperativo — catálogo real del CMDB (6 estados).
 *
 * Fuente: `docs/Formatos/CMDB USUARIO FINAL.xlsx` → hoja "Listas De Validación
 * Equipos" (contrato §4). Única fuente de verdad para labels, tonos, iconos y
 * reglas de custodia de los estados operativos; la persistencia vive en
 * `catalogo_estados` (editable desde el módulo Catálogos).
 */
class EstadoOperativo
{
    /** Metadatos por código (tono e icono del design system). */
    private const META = [
        'almacenado'       => ['tone' => 'slate',   'icon' => 'archive',        'requiere_custodia' => false],
        'transito'         => ['tone' => 'slate',   'icon' => 'truck',          'requiere_custodia' => false],
        'en_operacion'     => ['tone' => 'success', 'icon' => 'laptop',         'requiere_custodia' => true],
        'baja'             => ['tone' => 'danger',  'icon' => 'archive',        'requiere_custodia' => false],
        'hurto'            => ['tone' => 'danger',  'icon' => 'triangle-alert', 'requiere_custodia' => false],
        'pendiente_estado' => ['tone' => 'warning', 'icon' => 'hourglass',      'requiere_custodia' => false],
    ];

    /** Estados del CMDB (códigos). Orden oficial. */
    public const TODOS = ['almacenado', 'transito', 'en_operacion', 'baja', 'hurto', 'pendiente_estado'];

    public static function todos(): array
    {
        return self::TODOS;
    }

    public static function existe(string $codigo): bool
    {
        return in_array($codigo, self::TODOS, true);
    }

    /** Etiqueta legible (desde el catálogo editable; cacheada 60 s). */
    public static function label(?string $codigo): string
    {
        if (!$codigo) return '—';
        return Cache::remember("estado_label_{$codigo}", 60, fn () =>
            DB::table('catalogo_estados')->where('codigo', $codigo)->value('nombre')
                ?? mb_convert_case($codigo, MB_CASE_TITLE, 'UTF-8'));
    }

    public static function tone(?string $codigo): string
    {
        return self::META[$codigo]['tone'] ?? 'slate';
    }

    public static function icon(?string $codigo): string
    {
        return self::META[$codigo]['icon'] ?? 'circle';
    }

    /** ¿El estado exige custodio funcional (asignado)? Solo EN OPERACIÓN. */
    public static function requiereCustodia(string $codigo): bool
    {
        return self::META[$codigo]['requiere_custodia'] ?? false;
    }

    /**
     * Lista [codigo => nombre] para selects (catálogo editable en vivo).
     *
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        return Cache::remember('estado_opciones', 60, fn () =>
            DB::table('catalogo_estados')->orderBy('id')->pluck('nombre', 'codigo')->all());
    }

    /**
     * Normaliza un valor del archivo/legacy al código del catálogo real.
     * Alias legacy: activo/asignado → en_operacion; disponible → almacenado;
     * mantenimiento → almacenado (razón alistamiento_soporte); procesar_baja → baja.
     */
    public static function normalizar(?string $valor): ?string
    {
        $v = mb_strtoupper(trim((string) $valor));
        if ($v === '') return null;

        $mapa = [
            'ALMACENADO'       => 'almacenado',
            'TRÁNSITO'         => 'transito',
            'TRANSITO'         => 'transito',
            'EN OPERACIÓN'     => 'en_operacion',
            'EN OPERACION'     => 'en_operacion',
            'BAJA'             => 'baja',
            'HURTO'            => 'hurto',
            'PENDIENTE ESTADO' => 'pendiente_estado',
            // Legacy del sistema
            'ACTIVO'           => 'en_operacion',
            'ASIGNADO'         => 'en_operacion',
            'ASIGNACION RESPONSABLE' => 'en_operacion',
            'DISPONIBLE'       => 'almacenado',
            'PRESTAMO'         => 'en_operacion',
            'PRÉSTAMO'         => 'en_operacion',
            'REPARACION'       => 'almacenado',
            'MANTENIMIENTO'    => 'almacenado',
            'PROCESAR BAJA'     => 'baja',
        ];

        if (isset($mapa[$v])) return $mapa[$v];

        // Coincidencia parcial con el nombre oficial del catálogo
        foreach (self::opciones() as $codigo => $nombre) {
            if (str_contains($v, mb_strtoupper($nombre))) return $codigo;
        }

        return 'pendiente_estado';
    }
}
