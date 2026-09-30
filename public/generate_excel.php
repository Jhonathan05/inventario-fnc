<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Exports\EquiposExport;
use Maatwebsite\Excel\Facades\Excel;

$columnasCmdb = EquiposExport::columnasCmdbPrincipal();
$camposCmdb = \App\Models\CampoPersonalizado::where('modulo', 'equipos')
    ->where('exportable', true)
    ->where('mostrar_en_grilla', true)
    ->pluck('id')
    ->toArray();

$exportCmdb = new EquiposExport($columnasCmdb, $camposCmdb, []);
Excel::store($exportCmdb, 'test_cmdb.xlsx', 'local');

echo "Saved storage/app/test_cmdb.xlsx\n";
