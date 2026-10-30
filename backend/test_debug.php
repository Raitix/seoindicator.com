<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Http::fake([
    'openrouter.ai/*' => Http::response([
        'choices' => [['message' => ['content' => 'Test response']]]
    ], 200),
]);

$response = Http::withHeaders(['Authorization' => 'Bearer test'])
    ->post('https://openrouter.ai/api/v1/chat/completions', ['model' => 'test']);

echo 'Status: ' . $response->status() . PHP_EOL;
echo 'Body: ' . $response->body() . PHP_EOL;
