<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('prestamos:check-vencidos')->hourly();

// Backup diario de la base de datos (se activa con BACKUPS_PROGRAMADO=true).
Schedule::command('backups:crear')
    ->daily()
    ->at('02:00')
    ->when(fn () => (bool) config('backups.programado', false));

// Lote B: regla institucional de bodega — 15 días hábiles tras el retorno,
// la razón pasa a DISPONIBLE para su asignación.
Schedule::command('bodega:liberar-disponibles')
    ->daily()
    ->at('02:30');
