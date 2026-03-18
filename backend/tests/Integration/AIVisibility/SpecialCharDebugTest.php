<?php

namespace Tests\Integration\AIVisibility;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIMention;
use App\Models\AIVisibility\AIQueryResult;
use App\Jobs\AIVisibility\RunQueryJob;
use App\Services\AIVisibility\AIQueryEngine;
use App\Services\AIVisibility\MentionParser;

class SpecialCharDebugTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function debug_job_flow(): void
    {
        $user = User::factory()->create();
        
        $specialProject = AIVisibilityProject::factory()->create([
            'user_id' => $user->id,
            'brand_name' => 'C++ Tools',
            'keywords' => ['c++ tools'],
        ]);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'C++ Tools is great for development.'
                    ]
                ]]
            ], 200),
        ]);

        $job = new RunQueryJob($specialProject->id, 'chatgpt', 'Dev tools?');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        // Check what was created
        $queryResults = AIQueryResult::where('project_id', $specialProject->id)->get();
        $mentions = AIMention::where('project_id', $specialProject->id)->get();
        
        dd([
            'project_id' => $specialProject->id,
            'query_results_count' => $queryResults->count(),
            'query_results' => $queryResults->toArray(),
            'mentions_count' => $mentions->count(),
            'mentions' => $mentions->toArray(),
        ]);
    }
}
