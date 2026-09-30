<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate authenticated user
$user = \App\Models\User::first();
Auth::guard('web')->login($user);

// Simulate request to /login?_tab=ABC
$request = Illuminate\Http\Request::create('/login?_tab=ABC', 'GET');
$request->setLaravelSession(session());

$response = app()->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Location: " . $response->headers->get('Location') . "\n";
