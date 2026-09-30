<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/login?_tab=ABC', 'GET');
$response = app()->handle($request);

$content = $response->getContent();
preg_match('/<meta name="current-tab-id" content="(.*?)">/', $content, $matches);
echo "Meta tab: " . ($matches[1] ?? 'NOT FOUND') . "\n";
