<?php

namespace Database\Seeders;

use App\Models\Territorio;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * TerritorioSeeder — jerarquía Circunscripción → Seccional → Municipio.
 *
 * Fuente: `docs/Formatos/territorio.xlsx` (mapeo confirmado por el usuario,
 * 24-10-2026: 6 circunscripciones que agrupan 8 seccionales; 34 municipios).
 * Idempotente: upsert por municipio.
 */
class TerritorioSeeder extends Seeder
{
    use WithoutModelEvents;

    /** [circunscripcion, seccional, municipio] */
    private const FILAS = [
        // Circunscripción CHAPARRAL — Seccional Chaparral
        ['CHAPARRAL', 'CHAPARRAL', 'CHAPARRAL'],
        ['CHAPARRAL', 'CHAPARRAL', 'ORTEGA'],

        // Circunscripción FRESNO — Seccional Fresno
        ['FRESNO', 'FRESNO', 'ARMERO'],
        ['FRESNO', 'FRESNO', 'FALAN'],
        ['FRESNO', 'FRESNO', 'FRESNO'],
        ['FRESNO', 'FRESNO', 'HERVEO'],
        ['FRESNO', 'FRESNO', 'MARIQUITA'],
        ['FRESNO', 'FRESNO', 'PALOCABILDO'],

        // Circunscripción IBAGUE — Seccionales Ibagué y Rovira
        ['IBAGUE', 'IBAGUE', 'ALVARADO'],
        ['IBAGUE', 'IBAGUE', 'ANZOATEGUI'],
        ['IBAGUE', 'IBAGUE', 'IBAGUE'],
        ['IBAGUE', 'IBAGUE', 'VENADILLO'],
        ['IBAGUE', 'ROVIRA', 'CAJAMARCA'],
        ['IBAGUE', 'ROVIRA', 'ROVIRA'],
        ['IBAGUE', 'ROVIRA', 'SAN LUIS'],
        ['IBAGUE', 'ROVIRA', 'VALLE DE SAN JUAN'],

        // Circunscripción LIBANO — Seccional Líbano
        ['LIBANO', 'LIBANO', 'CASABIANCA'],
        ['LIBANO', 'LIBANO', 'LERIDA'],
        ['LIBANO', 'LIBANO', 'LIBANO'],
        ['LIBANO', 'LIBANO', 'SANTA ISABEL'],
        ['LIBANO', 'LIBANO', 'VILLAHERMOSA'],

        // Circunscripción PLANADAS — Seccionales Planadas y Rioblanco
        ['PLANADAS', 'PLANADAS', 'ATACO'],
        ['PLANADAS', 'PLANADAS', 'PLANADAS'],
        ['PLANADAS', 'RIOBLANCO', 'RIOBLANCO'],
        ['PLANADAS', 'RIOBLANCO', 'RONCESVALLES'],
        ['PLANADAS', 'RIOBLANCO', 'SAN ANTONIO'],

        // Circunscripción VILLARRICA — Seccional Villarrica
        ['VILLARRICA', 'VILLARRICA', 'ALPUJARRA'],
        ['VILLARRICA', 'VILLARRICA', 'CUNDAY'],
        ['VILLARRICA', 'VILLARRICA', 'DOLORES'],
        ['VILLARRICA', 'VILLARRICA', 'ICONONZO'],
        ['VILLARRICA', 'VILLARRICA', 'MELGAR'],
        ['VILLARRICA', 'VILLARRICA', 'PRADO'],
        ['VILLARRICA', 'VILLARRICA', 'PURIFICACION'],
        ['VILLARRICA', 'VILLARRICA', 'VILLARRICA'],
    ];

    public function run(): void
    {
        foreach (self::FILAS as [$circunscripcion, $seccional, $municipio]) {
            Territorio::updateOrCreate(
                ['municipio' => $municipio],
                ['circunscripcion' => $circunscripcion, 'seccional' => $seccional, 'activo' => true]
            );
        }

        $circuns = count(array_unique(array_column(self::FILAS, 0)));
        $secciones = count(array_unique(array_map(fn ($f) => $f[0] . '|' . $f[1], self::FILAS)));
        $this->command->info("✔ Territorio: {$circuns} circunscripciones, {$secciones} seccionales, " . count(self::FILAS) . ' municipios.');
    }
}
