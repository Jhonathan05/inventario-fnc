<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Exports\EquiposExport;

$columnasCmdb = EquiposExport::columnasCmdbPrincipal();
$camposCmdb = \App\Models\CampoPersonalizado::where('modulo', 'equipos')
    ->where('exportable', true)
    ->where('mostrar_en_grilla', true)
    ->pluck('id')
    ->toArray();

$exportCmdb = new EquiposExport($columnasCmdb, $camposCmdb, []);
$headingsCmdb = $exportCmdb->headings();

echo "--- CMDB HEADINGS ---\n";
$posCmdb = array_search('MODELO', $headingsCmdb);
echo "Posición de MODELO: " . $posCmdb . "\n";
echo "Elemento en pos + 1: " . ($headingsCmdb[$posCmdb + 1] ?? 'N/A') . "\n";
echo "Elemento en pos - 1: " . ($headingsCmdb[$posCmdb - 1] ?? 'N/A') . "\n";
echo "Total columnas: " . count($headingsCmdb) . "\n\n";

$columnasCompleta = EquiposExport::columnasCompletas();
$camposCompleta = \App\Models\CampoPersonalizado::where('modulo', 'equipos')
    ->where('exportable', true)
    ->pluck('id')
    ->toArray();

$exportCompleta = new EquiposExport($columnasCompleta, $camposCompleta, []);
$headingsCompleta = $exportCompleta->headings();

echo "--- COMPLETA HEADINGS ---\n";
$posCompleta = array_search('MODELO', $headingsCompleta);
echo "Posición de MODELO: " . $posCompleta . "\n";
echo "Elemento en pos + 1: " . ($headingsCompleta[$posCompleta + 1] ?? 'N/A') . "\n";
echo "Total columnas: " . count($headingsCompleta) . "\n\n";
