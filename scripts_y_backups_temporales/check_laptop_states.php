<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$stats = \App\Models\Equipo::whereHas('tipoRecurso', function($q) {
    $q->where('nombre', 'like', '%PORT%');
})->select('estado_operativo')->selectRaw('count(*) as count')
  ->groupBy('estado_operativo')->get();

print_r($stats->toArray());
