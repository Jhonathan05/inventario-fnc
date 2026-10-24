<?php

namespace App\Domain\Activos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * RazonEstado — catálogo real de las 13 razones del estado del CMDB.
 *
 * Fuente: `docs/Formatos/CMDB USUARIO FINAL.xlsx` → hoja "Listas De Validación
 * Equipos" (contrato §4). Persistencia en `catalogo_razones` (editable desde el
 * módulo Catálogos). `equipos.razon_estado` guarda el CÓDIGO de la razón.
 */
class RazonEstado
{
    public const TODOS = [
        'disponible', 'custodia', 'alistamiento_soporte', 'renovacion', 'procesar_baja',
        'despacho', 'retorno', 'asignacion_responsable', 'prestamo', 'hurto_perdida',
        'devuelto_a_proveedor', 'donacion', 'baja',
    ];

    /** Razones que representan la salida definitiva del activo (retiro). */
    public const SALIDAS_RETIRO = ['baja', 'procesar_baja', 'donacion', 'devuelto_a_proveedor', 'hurto_perdida'];

    /** Razones que dejan el activo en bodega listo para asignar. */
    public const EN_BODEGA = ['disponible', 'custodia', 'retorno', 'renovacion'];

    public static function todos(): array
    {
        return self::TODOS;
    }

    public static function existe(string $codigo): bool
    {
        return in_array($codigo, self::TODOS, true);
    }

    /** Etiqueta legible (catálogo editable; cacheada 60 s). */
    public static function label(?string $codigo): string
    {
        if (!$codigo) return '—';
        return Cache::remember("razon_label_{$codigo}", 60, fn () =>
            DB::table('catalogo_razones')->where('codigo', $codigo)->value('nombre') ?? $codigo);
    }

    /**
     * Lista [codigo => nombre] para selects (catálogo en vivo).
     *
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        return Cache::remember('razon_opciones', 60, fn () =>
            DB::table('catalogo_razones')->orderBy('id')->pluck('nombre', 'codigo')->all());
    }

    /**
     * Normaliza un valor del archivo (texto en mayúsculas) o legacy al código.
     * Acepta también códigos ya normalizados.
     */
    public static function normalizar(?string $valor): ?string
    {
        $v = mb_strtoupper(trim((string) $valor));
        if ($v === '') return null;

        foreach (self::opciones() as $codigo => $nombre) {
            if ($v === mb_strtoupper($codigo) || $v === mb_strtoupper($nombre)) return $codigo;
        }

        // Tolerancias del archivo real (tildes/palabras sueltas)
        $parcial = [
            'DISPONIBLE' => 'disponible', 'CUSTODIA' => 'custodia',
            'ALISTAMIENTO / SOPORTE' => 'alistamiento_soporte', 'ALISTAMIENTO' => 'alistamiento_soporte',
            'SOPORTE' => 'alistamiento_soporte', 'RENOVACION' => 'renovacion', 'RENOVACIÓN' => 'renovacion',
            'PROCESAR BAJA' => 'procesar_baja', 'DESPACHO' => 'despacho', 'RETORNO' => 'retorno',
            'ASIGNACION RESPONSABLE' => 'asignacion_responsable', 'ASIGNACIÓN RESPONSABLE' => 'asignacion_responsable',
            'PRESTAMO' => 'prestamo', 'PRÉSTAMO' => 'prestamo', 'HURTO / PERDIDA' => 'hurto_perdida',
            'HURTO / PÉRDIDA' => 'hurto_perdida', 'HURTO' => 'hurto_perdida', 'PERDIDA' => 'hurto_perdida',
            'DEVUELTO A PROVEEDOR' => 'devuelto_a_proveedor', 'DONACION' => 'donacion', 'DONACIÓN' => 'donacion',
            'BAJA' => 'baja',
        ];
        foreach ($parcial as $texto => $codigo) {
            if ($v === $texto || str_contains($v, $texto)) return $codigo;
        }

        return null; // desconocida → la decide el flujo (pendiente de corrección)
    }
}
