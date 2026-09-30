<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\URL::defaults(['_tab' => 'XYZ']);
echo "Route equipos.index: " . route('equipos.index') . "\n";
echo "Route equipos.show: " . route('equipos.show', 1) . "\n";
