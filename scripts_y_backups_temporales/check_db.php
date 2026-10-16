<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$campos = \App\Models\CampoPersonalizado::all();
foreach ($campos as $c) {
    echo "ID: {$c->id}, Nombre: {$c->nombre}, mostrar_en_grilla: {$c->mostrar_en_grilla}, exportable: {$c->exportable}, exportar_excel_despues_de: {$c->exportar_excel_despues_de}, posicion_grilla_despues_de: {$c->posicion_grilla_despues_de}\n";
}
