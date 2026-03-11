<?php

namespace App\Jobs\ContentDecay;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Services\AI\Contracts\AIAnalysisServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generate Refresh Brief Job.
 *
 * Background job that uses AI to generate refresh recommendations
 * for a content decay candidate.
 *
 * @see TASK-36-9
 * @see SPEC-036
 */
class GenerateRefreshBriefJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * The candidate ID to generate brief for.
     */
    public int $candidateId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $candidateId)
    {
        $this->candidateId = $candidateId;
        $this->onQueue('ai-tasks');
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->candidateId;
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 180]; // 1 min, 3 min
    }

    /**
     * Determine if the job should be unique until processing begins.
     */
    public function uniqueFor(): int
    {
        return 3600; // 1 hour
    }

    /**
     * Execute the job.
     */
    public function handle(AIAnalysisServiceInterface $aiService): void
    {
        Log::info('Generate refresh brief job started', [
            'candidate_id' => $this->candidateId,
            'attempt' => $this->attempts(),
        ]);

        // Validate candidate ID
        if ($this->candidateId <= 0) {
            Log::error('Invalid candidate ID for brief generation', [
                'candidate_id' => $this->candidateId,
            ]);
            return;
        }

        // Load candidate
        $candidate = ContentDecayCandidate::find($this->candidateId);

        if (!$candidate) {
            Log::warning('Candidate not found for brief generation', [
                'candidate_id' => $this->candidateId,
            ]);
            return;
        }

        // Check if brief already exists
        $evidence = $candidate->evidence_json ?? [];
        if (isset($evidence['refresh_brief'])) {
            Log::info('Brief already exists for candidate, skipping', [
                'candidate_id' => $this->candidateId,
            ]);
            return;
        }

        // Load project for context
        $project = ContentDecayProject::find($candidate->project_id);

        if (!$project) {
            Log::warning('Project not found for brief generation', [
                'candidate_id' => $this->candidateId,
                'project_id' => $candidate->project_id,
            ]);
            return;
        }

        // Build AI prompt from signals and reason codes
        $prompt = $this->buildPrompt($candidate, $project);

        // Generate brief using AI service
        Log::info('Generating refresh brief with AI', [
            'candidate_id' => $this->candidateId,
            'page_url' => $candidate->page_url,
        ]);

        try {
            // Use AI service to generate brief
            // We'll use a simple text analysis with a generated HTML structure
            $briefResult = $this->generateBriefWithAI($aiService, $prompt, $candidate);

            // Store brief in evidence_json
            $evidence['refresh_brief'] = $briefResult;
            $evidence['brief_generated_at'] = now()->toIso8601String();

            $candidate->update(['evidence_json' => $evidence]);

            Log::info('Refresh brief generated successfully', [
                'candidate_id' => $this->candidateId,
                'brief_length' => strlen($briefResult['outline'] ?? ''),
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to generate refresh brief', [
                'candidate_id' => $this->candidateId,
                'error' => $e->getMessage(),
            ]);

            // Store error in evidence for debugging
            $evidence['refresh_brief_error'] = [
                'error' => $e->getMessage(),
                'failed_at' => now()->toIso8601String(),
            ];
            $candidate->update(['evidence_json' => $evidence]);

            throw $e;
        }
    }

    /**
     * Build the AI prompt from candidate signals.
     */
    protected function buildPrompt(ContentDecayCandidate $candidate, ContentDecayProject $project): string
    {
        $reasonCodes = $candidate->reason_codes ?? [];
        $primaryReason = $candidate->primary_reason_code ?? 'unknown';

        $prompt = "Generate a content refresh brief for the following page:\n\n";
        $prompt .= "Page URL: {$candidate->page_url}\n";
        $prompt .= "Page Title: " . ($candidate->page_title ?? 'Unknown') . "\n";
        $prompt .= "Page Type: " . ($candidate->page_type ?? 'unknown') . "\n";
        $prompt .= "Severity: {$candidate->severity}\n";
        $prompt .= "Decay Score: {$candidate->decay_score}\n\n";

        $prompt .= "Primary Reason: {$primaryReason}\n";
        if (!empty($reasonCodes)) {
            $prompt .= "All Detected Issues:\n";
            foreach ($reasonCodes as $code) {
                $prompt .= "- {$code}\n";
            }
        }

        $prompt .= "\nLost Clicks Estimate: {$candidate->lost_click_estimate}\n";
        $prompt .= "Recovery Upside Estimate: {$candidate->recovery_upside_estimate}\n\n";

        $prompt .= "Please provide:\n";
        $prompt .= "1. A brief outline of recommended content updates\n";
        $prompt .= "2. Priority actions (3-5 items)\n";
        $prompt .= "3. Suggested improvements for each detected issue\n";

        return $prompt;
    }

    /**
     * Generate brief using AI service.
     */
    protected function generateBriefWithAI(
        AIAnalysisServiceInterface $aiService,
        string $prompt,
        ContentDecayCandidate $candidate
    ): array {
        // Create a minimal HTML structure for the AI analysis
        $html = $this->createHtmlFromPrompt($prompt);

        // Use AI service to analyze
        $result = $aiService->analyze(
            $candidate->page_url,
            $html,
            [
                'model' => $aiService->getDefaultModel(),
                'timeout' => 90,
            ]
        );

        // Parse the result into a structured brief
        return [
            'outline' => $this->extractOutline($result),
            'priority_actions' => $this->extractPriorityActions($result),
            'suggestions' => $this->extractSuggestions($result, $candidate),
            'generated_at' => now()->toIso8601String(),
            'model_used' => $aiService->getDefaultModel(),
        ];
    }

    /**
     * Create HTML from prompt for AI analysis.
     */
    protected function createHtmlFromPrompt(string $prompt): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head><title>Content Refresh Brief Request</title></head>
<body>
<article>
<h1>Content Refresh Brief Request</h1>
<section>
<pre>{$prompt}</pre>
</section>
</article>
</body>
</html>
HTML;
    }

    /**
     * Extract outline from AI result.
     */
    protected function extractOutline($result): string
    {
        // Extract from AI result's suggestions or overall analysis
        if (method_exists($result, 'getSuggestions')) {
            $suggestions = $result->getSuggestions();
            if (!empty($suggestions)) {
                return implode("\n", array_slice($suggestions, 0, 5));
            }
        }

        // Fallback: Generate outline from result data
        $outline = [];

        if (isset($result->indicators)) {
            $indicators = $result->indicators;

            if (isset($indicators['keyword_density'])) {
                $outline[] = "Review keyword optimization";
            }
            if (isset($indicators['readability'])) {
                $outline[] = "Improve content readability";
            }
            if (isset($indicators['content_quality'])) {
                $outline[] = "Enhance content quality and depth";
            }
        }

        return implode("\n", $outline) ?: "Review and refresh content based on detected issues";
    }

    /**
     * Extract priority actions from AI result.
     */
    protected function extractPriorityActions($result): array
    {
        $actions = [];

        // Try to extract from AI result
        if (method_exists($result, 'getPriorityActions')) {
            return $result->getPriorityActions();
        }

        // Fallback: Generate from indicators
        if (isset($result->indicators)) {
            foreach ($result->indicators as $key => $value) {
                if (is_array($value) && isset($value['score']) && $value['score'] < 70) {
                    $actions[] = $this->getActionForKey($key);
                }
            }
        }

        return array_slice($actions, 0, 5) ?: [
            "Review and update outdated content",
            "Add fresh insights and examples",
            "Optimize meta description",
            "Improve internal linking",
            "Update publish date if significantly refreshed",
        ];
    }

    /**
     * Get action suggestion for a specific key.
     */
    protected function getActionForKey(string $key): string
    {
        return match ($key) {
            'keyword_density' => "Optimize keyword usage and density",
            'readability' => "Improve content readability and structure",
            'content_quality' => "Enhance content quality and comprehensiveness",
            'meta_tags' => "Update and optimize meta tags",
            'images' => "Add or optimize images with alt text",
            'internal_links' => "Add relevant internal links",
            'external_links' => "Add authoritative external references",
            default => "Review and improve {$key}",
        };
    }

    /**
     * Extract suggestions from AI result.
     */
    protected function extractSuggestions($result, ContentDecayCandidate $candidate): array
    {
        $suggestions = [];

        // Get reason-specific suggestions
        $reasonCodes = $candidate->reason_codes ?? [];

        foreach ($reasonCodes as $code) {
            $suggestions[$code] = $this->getSuggestionForReason($code);
        }

        return $suggestions;
    }

    /**
     * Get suggestion for a specific reason code.
     */
    protected function getSuggestionForReason(string $code): string
    {
        return match ($code) {
            'clicks_down' => "Focus on improving title tags and meta descriptions to increase CTR",
            'impressions_down' => "Consider expanding content to target additional keywords",
            'ctr_down' => "Improve snippet appearance with structured data and compelling titles",
            'avg_position_down' => "Build more quality backlinks and improve content depth",
            'rank_visibility_down' => "Optimize for featured snippets and position tracking",
            'ai_visibility_down' => "Add structured data and FAQ sections for AI discoverability",
            default => "Review and address the detected issue",
        };
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Generate refresh brief job failed', [
            'candidate_id' => $this->candidateId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'candidate:' . $this->candidateId,
            'content-decay:brief',
        ];
    }
}
