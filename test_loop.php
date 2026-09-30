<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/login', 'GET');
$response = app()->handle($request);

echo "Status 1: " . $response->getStatusCode() . "\n";
echo "Location 1: " . $response->headers->get('Location') . "\n";

if ($response->getStatusCode() == 302) {
    $request2 = Illuminate\Http\Request::create($response->headers->get('Location'), 'GET');
    $response2 = app()->handle($request2);
    echo "Status 2: " . $response2->getStatusCode() . "\n";
    echo "Location 2: " . $response2->headers->get('Location') . "\n";
}
