<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Territorio — jerarquía Circunscripción → Seccional → Municipio.
 *
 * Fuente: `docs/Formatos/territorio.xlsx` (Comité Tolima: 6 circunscripciones,
 * 8 seccionales, 34 municipios). Una fila por municipio; editable desde el
 * módulo Catálogos para incorporar otros comités o cambios del formato.
 */
class Territorio extends Model
{
    protected $table = 'territorios';

    protected $fillable = ['circunscripcion', 'seccional', 'municipio', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    /* ── Consultas jerárquicas para selects dependientes ─────────────── */

    /** @return array<string, array<string, array<int, string>>> circunscripción → [seccional → municipios] */
    public static function arbol(): array
    {
        return static::query()->where('activo', true)
            ->orderBy('circunscripcion')
            ->orderBy('seccional')
            ->orderBy('municipio')
            ->get()
            ->groupBy(['circunscripcion', 'seccional'])
            ->map(fn ($grupos) => $grupos
                ->map(fn ($filas) => $filas->pluck('municipio')->all())
                ->toArray())
            ->toArray();
    }

    /** Seccionales únicas (para el select de seccional). */
    public static function seccionales(): array
    {
        return static::query()->where('activo', true)
            ->distinct()->orderBy('seccional')->pluck('seccional')->all();
    }

    /** Municipios de una seccional (para el select dependiente). */
    public static function municipiosDe(string $seccional): array
    {
        return static::query()->where('activo', true)
            ->where('seccional', $seccional)
            ->orderBy('municipio')->pluck('municipio')->all();
    }
}
