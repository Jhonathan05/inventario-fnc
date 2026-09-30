<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/equipos?_tab=ABC', 'GET');
$response = app()->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Location: " . $response->headers->get('Location') . "\n";

if ($response->getStatusCode() == 302) {
    $request2 = Illuminate\Http\Request::create($response->headers->get('Location'), 'GET');
    $response2 = app()->handle($request2);
    echo "Status 2: " . $response2->getStatusCode() . "\n";
    echo "Location 2: " . $response2->headers->get('Location') . "\n";
    
    if ($response2->getStatusCode() == 302) {
        $request3 = Illuminate\Http\Request::create($response2->headers->get('Location'), 'GET');
        $response3 = app()->handle($request3);
        echo "Status 3: " . $response3->getStatusCode() . "\n";
        echo "Location 3: " . $response3->headers->get('Location') . "\n";
    }
}
