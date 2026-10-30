<?php

namespace App\Services\ContentDecay;

use App\Models\ContentDecayCandidate;
use Carbon\Carbon;
use Illuminate\Support\Arr;

/**
 * Service for generating actionable recommendations and task suggestions
 * based on content decay reason codes and evidence.
 *
 * This service maps decay triggers to concrete refresh actions and
 * generates prioritized task suggestions with due dates.
 *
 * @see TASK-36-5
 * @see SPEC-036
 */
class ContentRefreshPlannerService implements ContentRefreshPlannerServiceInterface
{
    /**
     * Recommendation templates indexed by reason code.
     *
     * Each template contains:
     * - title: Actionable task title
     * - description: Detailed guidance with placeholders
     * - due_days: Suggested timeline based on severity
     * - priority: Default priority (can be escalated)
     * - recommendation_type: Category of task
     * - source: Data source attribution
     */
    protected array $templates = [
        'audit_issues_present' => [
            'title' => 'Fix technical SEO issues',
            'description' => 'This page has {count} unresolved audit issues. Address technical problems before investing in content updates.',
            'due_days' => 3,
            'priority' => 'critical',
            'recommendation_type' => 'technical_fix',
            'source' => 'audit',
        ],
        'clicks_down' => [
            'title' => 'Investigate click loss',
            'description' => 'Clicks have dropped by {percent}%. Analyze the cause and implement recovery strategies.',
            'due_days' => 7,
            'priority' => 'high',
            'recommendation_type' => 'traffic_recovery',
            'source' => 'gsc',
        ],
        'ctr_down' => [
            'title' => 'Rewrite title and meta description',
            'description' => 'Your CTR has dropped by {percent}%. Update your title tag to be more compelling and ensure your meta description accurately reflects the page content.',
            'due_days' => 7,
            'priority' => 'high',
            'recommendation_type' => 'meta_update',
            'source' => 'gsc',
        ],
        'avg_position_down' => [
            'title' => 'Refresh core content sections',
            'description' => 'Average position has declined by {positions} positions. Refresh content sections targeting affected queries.',
            'due_days' => 10,
            'priority' => 'medium',
            'recommendation_type' => 'ranking_recovery',
            'source' => 'gsc',
        ],
        'tracked_rankings_down' => [
            'title' => 'Optimize for tracked keywords',
            'description' => 'Visibility has declined for {count} tracked keywords. Optimize content for these specific terms.',
            'due_days' => 10,
            'priority' => 'medium',
            'recommendation_type' => 'keyword_optimization',
            'source' => 'rankings',
        ],
        'impressions_down' => [
            'title' => 'Investigate visibility loss',
            'description' => 'Impressions have dropped by {percent}%. Check for indexing issues or SERP feature changes.',
            'due_days' => 14,
            'priority' => 'medium',
            'recommendation_type' => 'visibility_recovery',
            'source' => 'gsc',
        ],
        'stale_content' => [
            'title' => 'Update outdated content',
            'description' => 'This content is {age_days} days old. Update examples, screenshots, statistics, and date references to maintain relevance.',
            'due_days' => 14,
            'priority' => 'high',
            'recommendation_type' => 'content_refresh',
            'source' => 'content_audit',
        ],
        'ai_visibility_down' => [
            'title' => 'Optimize for AI visibility',
            'description' => 'AI visibility has declined. Add definitions, FAQs, citations, quotes, and structured summaries to improve GEO.',
            'due_days' => 21,
            'priority' => 'medium',
            'recommendation_type' => 'geo_optimization',
            'source' => 'ai_analytics',
        ],
        'competitor_gain_suspected' => [
            'title' => 'Add competitor comparison',
            'description' => 'Competitor may be gaining ground. Add comparison sections, alternatives, and differentiation content.',
            'due_days' => 14,
            'priority' => 'medium',
            'recommendation_type' => 'competitive_response',
            'source' => 'competitive_analysis',
        ],
        'internal_link_weakness' => [
            'title' => 'Strengthen internal linking',
            'description' => 'This page needs more internal link support. Add links from related stronger pages.',
            'due_days' => 21,
            'priority' => 'low',
            'recommendation_type' => 'internal_linking',
            'source' => 'site_audit',
        ],
    ];

    /**
     * Page-type-specific configuration overrides.
     *
     * Each page type can override:
     * - description_suffix: Additional context for the page type
     * - focus_areas: Specific areas to focus on for this page type
     */
    protected array $pageTypeConfigs = [
        'blog' => [
            'description_suffix' => 'Focus on updating examples, statistics, and maintaining topical authority.',
            'focus_areas' => ['content_refresh', 'examples_update', 'topical_authority'],
        ],
        'product' => [
            'description_suffix' => 'Prioritize feature documentation, pricing accuracy, and competitive positioning.',
            'focus_areas' => ['feature_docs', 'pricing_update', 'competitive_response'],
        ],
        'landing' => [
            'description_suffix' => 'Focus on CTA optimization, conversion paths, and offer clarity.',
            'focus_areas' => ['cta_optimization', 'conversion_focus', 'offer_update'],
        ],
        'tool' => [
            'description_suffix' => 'Update feature documentation, usage examples, and help resources.',
            'focus_areas' => ['feature_docs', 'usage_examples', 'help_resources'],
        ],
    ];

    /**
     * Priority escalation thresholds.
     *
     * When evidence magnitude exceeds these thresholds, priority is escalated.
     */
    protected array $escalationThresholds = [
        'clicks_down' => ['threshold' => 30, 'escalate_to' => 'critical'],
        'ctr_down' => ['threshold' => 40, 'escalate_to' => 'critical'],
        'avg_position_down' => ['threshold' => 10, 'escalate_to' => 'high'],
        'tracked_rankings_down' => ['threshold' => 5, 'escalate_to' => 'high'],
        'impressions_down' => ['threshold' => 50, 'escalate_to' => 'high'],
        'stale_content' => ['threshold' => 365, 'escalate_to' => 'critical'],
        'ai_visibility_down' => ['threshold' => 50, 'escalate_to' => 'high'],
    ];

    /**
     * Priority order for sorting tasks (lower = higher priority).
     */
    protected const PRIORITY_ORDER = [
        'critical' => 1,
        'high' => 2,
        'medium' => 3,
        'low' => 4,
    ];

    /**
     * {@inheritDoc}
     */
    public function generateRecommendations(ContentDecayCandidate $candidate): array
    {
        $reasonCodes = $candidate->reason_codes ?? [];
        $evidence = $candidate->evidence_json ?? [];
        $severity = $candidate->severity ?? 'medium';
        $pageType = $candidate->page_type ?? null;

        // Handle empty reason codes
        if (empty($reasonCodes)) {
            return $this->emptyRecommendations();
        }

        // Filter to known reason codes
        $validReasonCodes = array_filter($reasonCodes, fn($code) => isset($this->templates[$code]));

        if (empty($validReasonCodes)) {
            return $this->emptyRecommendations();
        }

        // Generate task suggestions
        $taskSuggestions = $this->generateTaskSuggestions($validReasonCodes, $evidence, $severity, $pageType);

        // Sort tasks by priority
        usort($taskSuggestions, function ($a, $b) {
            $priorityA = self::PRIORITY_ORDER[$a['priority']] ?? 99;
            $priorityB = self::PRIORITY_ORDER[$b['priority']] ?? 99;
            return $priorityA <=> $priorityB;
        });

        // Generate primary and supporting recommendations
        $primaryRecommendation = $this->generatePrimaryRecommendation($taskSuggestions, $validReasonCodes);
        $supportingRecommendations = $this->generateSupportingRecommendations($taskSuggestions, $validReasonCodes);

        return [
            'primary_recommendation' => $primaryRecommendation,
            'supporting_recommendations' => $supportingRecommendations,
            'task_suggestions' => $taskSuggestions,
        ];
    }

    /**
     * Get the recommendation templates.
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * Get the page type configurations.
     */
    public function getPageTypeConfigs(): array
    {
        return $this->pageTypeConfigs;
    }

    /**
     * Generate task suggestions for all valid reason codes.
     */
    protected function generateTaskSuggestions(array $reasonCodes, array $evidence, string $severity, ?string $pageType): array
    {
        $tasks = [];

        foreach ($reasonCodes as $reasonCode) {
            $task = $this->createTaskFromTemplate($reasonCode, $evidence, $severity, $pageType);
            if ($task !== null) {
                $tasks[] = $task;
            }
        }

        return $tasks;
    }

    /**
     * Create a task suggestion from a template.
     */
    protected function createTaskFromTemplate(string $reasonCode, array $evidence, string $severity, ?string $pageType): ?array
    {
        $template = $this->templates[$reasonCode] ?? null;

        if ($template === null) {
            return null;
        }

        // Get evidence for this reason code
        $reasonEvidence = $evidence[$reasonCode] ?? [];

        // Interpolate description with evidence values
        $description = $this->interpolateDescription($template['description'], $reasonEvidence);

        // Apply page-type suffix if applicable
        if ($pageType !== null && isset($this->pageTypeConfigs[$pageType])) {
            $config = $this->pageTypeConfigs[$pageType];
            if (!empty($config['description_suffix'])) {
                $description .= ' ' . $config['description_suffix'];
            }
        }

        // Calculate priority (with escalation)
        $priority = $this->calculatePriority($template['priority'], $severity, $reasonCode, $reasonEvidence);

        // Calculate due date
        $dueDays = $template['due_days'];
        if ($severity === 'critical') {
            $dueDays = max(1, (int) ceil($dueDays * 0.5));
        } elseif ($severity === 'high') {
            $dueDays = max(3, (int) ceil($dueDays * 0.75));
        }
        $dueDate = Carbon::now()->addDays($dueDays);

        // Build metadata
        $metadata = $this->buildMetadata($reasonCode, $reasonEvidence);

        return [
            'title' => $template['title'],
            'recommendation_type' => $template['recommendation_type'],
            'description' => $description,
            'due_date' => $dueDate,
            'priority' => $priority,
            'source' => $template['source'],
            'metadata_json' => $metadata,
        ];
    }

    /**
     * Interpolate description placeholders with evidence values.
     */
    protected function interpolateDescription(string $description, array $evidence): string
    {
        // Handle {count} placeholder
        if (str_contains($description, '{count}')) {
            $count = $evidence['count'] ?? $evidence['evidence']['count'] ?? 0;
            $description = str_replace('{count}', (string) $count, $description);
        }

        // Handle {percent} placeholder
        if (str_contains($description, '{percent}')) {
            $changePercent = abs($evidence['change_percent'] ?? $evidence['evidence']['change_percent'] ?? 0);
            $description = str_replace('{percent}', number_format($changePercent, 1), $description);
        }

        // Handle {age_days} placeholder
        if (str_contains($description, '{age_days}')) {
            $ageDays = $evidence['content_age_days'] ?? $evidence['evidence']['current'] ?? $evidence['current'] ?? 0;
            $description = str_replace('{age_days}', (string) (int) $ageDays, $description);
        }

        // Handle {positions} placeholder
        if (str_contains($description, '{positions}')) {
            $positions = abs($evidence['change_absolute'] ?? $evidence['evidence']['change_absolute'] ?? 0);
            $description = str_replace('{positions}', number_format($positions, 1), $description);
        }

        return $description;
    }

    /**
     * Calculate priority with escalation rules.
     */
    protected function calculatePriority(string $basePriority, string $severity, string $reasonCode, array $evidence): string
    {
        // Severity escalation
        if ($severity === 'critical') {
            return 'critical';
        }

        // Check escalation thresholds based on evidence magnitude
        if (isset($this->escalationThresholds[$reasonCode])) {
            $escalation = $this->escalationThresholds[$reasonCode];
            $threshold = $escalation['threshold'];
            $escalateTo = $escalation['escalate_to'];

            // Get magnitude from evidence
            $magnitude = $this->getMagnitudeFromEvidence($reasonCode, $evidence);

            if ($magnitude >= $threshold) {
                return $escalateTo;
            }
        }

        // High severity escalates medium to high
        if ($severity === 'high' && $basePriority === 'medium') {
            return 'high';
        }

        return $basePriority;
    }

    /**
     * Get magnitude value from evidence for escalation checks.
     */
    protected function getMagnitudeFromEvidence(string $reasonCode, array $evidence): float
    {
        $inner = $evidence['evidence'] ?? $evidence;

        return match ($reasonCode) {
            'clicks_down', 'impressions_down', 'ai_visibility_down' => abs($inner['change_percent'] ?? 0),
            'ctr_down' => abs($inner['change_absolute'] ?? 0),
            'avg_position_down' => abs($inner['change_absolute'] ?? 0),
            'tracked_rankings_down' => $inner['keywords_lost_count'] ?? 0,
            'stale_content' => $inner['content_age_days'] ?? $inner['current'] ?? 0,
            default => 0,
        };
    }

    /**
     * Build metadata array for a task.
     */
    protected function buildMetadata(string $reasonCode, array $evidence): array
    {
        $inner = $evidence['evidence'] ?? $evidence;
        $metadata = ['reason_code' => $reasonCode];

        switch ($reasonCode) {
            case 'audit_issues_present':
                $metadata['issue_count'] = $inner['count'] ?? 0;
                if (isset($inner['types'])) {
                    $metadata['issue_types'] = $inner['types'];
                }
                break;

            case 'ctr_down':
                $metadata['ctr_drop_percent'] = $inner['change_percent'] ?? 0;
                $metadata['previous_ctr'] = $inner['previous_value'] ?? $inner['previous'] ?? 0;
                $metadata['current_ctr'] = $inner['current_value'] ?? $inner['current'] ?? 0;
                break;

            case 'clicks_down':
            case 'impressions_down':
                $metadata['drop_percent'] = abs($inner['change_percent'] ?? 0);
                $metadata['previous_value'] = $inner['previous_value'] ?? $inner['previous'] ?? 0;
                $metadata['current_value'] = $inner['current_value'] ?? $inner['current'] ?? 0;
                break;

            case 'avg_position_down':
                $metadata['position_change'] = $inner['change_absolute'] ?? 0;
                $metadata['previous_position'] = $inner['previous_value'] ?? $inner['previous'] ?? 0;
                $metadata['current_position'] = $inner['current_value'] ?? $inner['current'] ?? 0;
                break;

            case 'tracked_rankings_down':
                $metadata['keywords_lost_count'] = $inner['keywords_lost_count'] ?? 0;
                break;

            case 'stale_content':
                $metadata['content_age_days'] = $inner['content_age_days'] ?? $inner['current'] ?? 0;
                break;

            case 'ai_visibility_down':
                $metadata['drop_percent'] = abs($inner['change_percent'] ?? 0);
                break;

            case 'internal_link_weakness':
                $metadata['current_count'] = $inner['current_count'] ?? $inner['current'] ?? 0;
                break;
        }

        return $metadata;
    }

    /**
     * Generate primary recommendation from tasks and reason codes.
     */
    protected function generatePrimaryRecommendation(array $tasks, array $reasonCodes): string
    {
        if (empty($tasks)) {
            return 'No action required';
        }

        // Single trigger - use the task title
        if (count($reasonCodes) === 1) {
            return $tasks[0]['title'];
        }

        // Multiple triggers - generate composite primary
        $hasAudit = in_array('audit_issues_present', $reasonCodes);
        $hasCtr = in_array('ctr_down', $reasonCodes);
        $hasStale = in_array('stale_content', $reasonCodes);
        $hasPosition = in_array('avg_position_down', $reasonCodes);
        $hasCompetitor = in_array('competitor_gain_suspected', $reasonCodes);

        // Prioritize audit issues in composite
        if ($hasAudit) {
            return 'Fix technical SEO issues before content refresh';
        }

        // CTR + Stale combination
        if ($hasCtr && $hasStale) {
            return 'Comprehensive page refresh needed';
        }

        // Position + Competitor combination
        if ($hasPosition && $hasCompetitor) {
            return 'Competitive ranking recovery needed';
        }

        // Multiple traffic-related issues
        $trafficTriggers = ['clicks_down', 'ctr_down', 'impressions_down', 'avg_position_down'];
        $trafficCount = count(array_intersect($reasonCodes, $trafficTriggers));
        if ($trafficCount >= 2) {
            return 'Comprehensive traffic recovery needed';
        }

        // Multiple content-related issues
        $contentTriggers = ['stale_content', 'ai_visibility_down'];
        $contentCount = count(array_intersect($reasonCodes, $contentTriggers));
        if ($contentCount >= 2) {
            return 'Content modernization needed';
        }

        // Default composite
        return 'Multiple improvements recommended';
    }

    /**
     * Generate supporting recommendations (max 3).
     */
    protected function generateSupportingRecommendations(array $tasks, array $reasonCodes): array
    {
        if (count($reasonCodes) <= 1) {
            return [];
        }

        $supporting = [];

        // Skip the first task (primary), take up to 3 supporting
        for ($i = 1; $i < min(4, count($tasks)); $i++) {
            $supporting[] = $tasks[$i]['title'];
        }

        return $supporting;
    }

    /**
     * Return empty recommendations structure.
     */
    protected function emptyRecommendations(): array
    {
        return [
            'primary_recommendation' => 'No decay detected',
            'supporting_recommendations' => [],
            'task_suggestions' => [],
        ];
    }
}
