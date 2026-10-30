<?php

// Simple debug script for ModelPricingService

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AIModel;
use App\Services\Billing\ModelPricingService;

// Clear existing models
AIModel::query()->delete();

// Create test model
$model = AIModel::factory()->create([
    'provider' => 'openrouter',
    'model_id' => 'test/model',
    'display_name' => 'Test Model',
    'cost_per_1k_input' => 0.01,
    'cost_per_1k_output' => 0.03,
    'is_active' => true,
]);

echo "Created model:\n";
print_r($model->toArray());

// Test the service
$service = app(ModelPricingService::class);
$result = $service->calculateAnalysisCost('test/model', 500, 'quick');

echo "\nResult:\n";
print_r($result);
