<?php

namespace App\Jobs;

use App\Services\SuggestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to generate keyword suggestions asynchronously.
 *
 * This job is dispatched by the GenerateSuggestions endpoint
 * and calls the SuggestionService to perform the actual work.
 *
 * @see TASK-5-8
 * @see TEST-5-8
 */
class GenerateSuggestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $siteId,
        public string $seedKeyword,
        public array $sources,
        public int $maxResults = 50,
        public string $jobId = ''
    ) {
        if (empty($this->jobId)) {
            $this->jobId = (string) \Illuminate\Support\Str::uuid();
        }
    }

    /**
     * Execute the job.
     */
    public function handle(SuggestionService $suggestionService): void
    {
        // Implementation by Developer
        // Should call $suggestionService->generateSuggestions()
    }
}
