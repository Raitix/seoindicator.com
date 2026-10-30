<?php

use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIMention;
use App\Jobs\AIVisibility\RunQueryJob;

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set up in-memory database
config(['database.connections.sqlite.database' => ':memory:']);
config(['database.default' => 'sqlite']);

// Run migrations
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

// Fake HTTP
Http::fake([
    'openrouter.ai/*' => Http::response([
        'choices' => [[
            'message' => [
                'content' => 'Some of the best SEO tools include SEOIndicator, Ahrefs, and SEMrush. I recommend SEOIndicator for its comprehensive features.'
            ]
        ]]
    ], 200),
]);

// Create user and project
$user = User::factory()->create();
$project = AIVisibilityProject::factory()->create([
    'user_id' => $user->id,
    'brand_name' => 'SEOIndicator',
]);

echo "Project created: {$project->id}\n";
echo "Brand name: {$project->brand_name}\n";

// Dispatch the job
try {
    $job = new RunQueryJob($project->id, 'chatgpt', 'What are the best SEO tools?');
    $job->handle(app(\App\Services\AIVisibility\AIQueryEngine::class), app(\App\Services\AIVisibility\MentionParser::class));
    echo "Job completed successfully\n";
} catch (\Exception $e) {
    echo "Job failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Check results
$queryResult = AIQueryResult::where('project_id', $project->id)->first();
if ($queryResult) {
    echo "Query result created:\n";
    echo "  ID: {$queryResult->id}\n";
    echo "  Platform: {$queryResult->platform}\n";
    echo "  Success: " . ($queryResult->success ? 'true' : 'false') . "\n";
    echo "  Response text: " . ($queryResult->response_text ?? 'NULL') . "\n";
    echo "  Error: " . ($queryResult->error_message ?? 'NULL') . "\n";
} else {
    echo "No query result created!\n";
}

$mentions = AIMention::where('project_id', $project->id)->count();
echo "Mentions created: {$mentions}\n";
