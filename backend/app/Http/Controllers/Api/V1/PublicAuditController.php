<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PublicAuditLeadRequest;
use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\PublicAuditView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Public Audit Controller for public-facing audit pages.
 *
 * Provides endpoints for:
 * - Retrieving public audit data
 * - Lead capture for anonymous users
 * - Badge configuration for embeds
 *
 * TASK-33-5, SPEC-33
 */
class PublicAuditController extends Controller
{
    /**
     * Get public audit data by hash.
     *
     * @param string $hash The public hash identifier
     * @return JsonResponse
     */
    public function show(string $hash): JsonResponse
    {
        // Find the audit by public hash
        $audit = Audit::findByPublicHash($hash);

        if (!$audit) {
            return response()->json([
                'success' => false,
                'error' => 'Audit not found',
            ], 404);
        }

        // Track the view
        $this->trackView($audit, $hash);

        // Check if user has full access (authenticated and owns the audit)
        $isPartial = $this->isPartialAccess($audit);

        // Get audit issues for score calculation
        $issues = $audit->issues()->with('issueType')->get();

        // Calculate scores
        $overallScore = $this->calculateOverallScore($issues);
        $categoryScores = $this->calculateCategoryScores($issues);
        $topIssues = $this->getTopIssues($issues);

        return response()->json([
            'success' => true,
            'data' => [
                'site_url' => $audit->site->url ?? '',
                'audited_at' => $audit->completed_at?->toIso8601String(),
                'overall_score' => $overallScore,
                'category_scores' => $categoryScores,
                'top_issues' => $topIssues,
                'is_partial' => $isPartial,
            ],
        ]);
    }

    /**
     * Capture lead email for a public audit.
     *
     * @param string $hash The public hash identifier
     * @param PublicAuditLeadRequest $request The validated request
     * @return JsonResponse
     */
    public function lead(string $hash, PublicAuditLeadRequest $request): JsonResponse
    {
        // Find the audit by public hash
        $audit = Audit::findByPublicHash($hash);

        if (!$audit) {
            return response()->json([
                'success' => false,
                'error' => 'Audit not found',
            ], 404);
        }

        $email = strtolower(trim($request->email));

        // Store the lead capture
        PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'viewed_at' => now(),
            'ip_hash' => $this->hashIp($request),
            'user_agent' => $request->userAgent(),
            'is_lead_capture' => true,
            'lead_email' => $email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email captured successfully',
        ], 201);
    }

    /**
     * Get badge configuration for a public audit.
     *
     * @param string $hash The public hash identifier
     * @return JsonResponse
     */
    public function badge(string $hash): JsonResponse
    {
        // Find the audit by public hash
        $audit = Audit::findByPublicHash($hash);

        if (!$audit) {
            return response()->json([
                'success' => false,
                'error' => 'Audit not found',
            ], 404);
        }

        // Calculate score
        $issues = $audit->issues()->with('issueType')->get();
        $score = $this->calculateOverallScore($issues);

        // Get site URL without protocol for display
        $siteUrl = $audit->site->url ?? '';
        $displayUrl = preg_replace('#^https?://#', '', $siteUrl);

        $auditUrl = url("/audit/public/{$hash}");

        return response()->json([
            'success' => true,
            'data' => [
                'hash' => $hash,
                'score' => $score,
                'site_url' => $displayUrl,
                'audit_url' => $auditUrl,
                'impressions_endpoint' => url('/api/v1/badge/impression'),
                'clicks_endpoint' => url('/api/v1/badge/click'),
                'sizes' => ['small', 'medium', 'large'],
                'themes' => ['light', 'dark'],
                'embed_code' => $this->generateEmbedCode($hash, $score, $auditUrl),
            ],
        ]);
    }

    /**
     * Track a view for analytics.
     *
     * @param Audit $audit
     * @param string $hash
     */
    protected function trackView(Audit $audit, string $hash): void
    {
        PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'viewed_at' => now(),
            'ip_hash' => $this->hashIp(request()),
            'user_agent' => request()->userAgent(),
            'is_lead_capture' => false,
        ]);
    }

    /**
     * Check if the current user has partial access (anonymous).
     *
     * @param Audit $audit
     * @return bool
     */
    protected function isPartialAccess(Audit $audit): bool
    {
        $user = Auth::user();

        // If not authenticated, access is partial
        if (!$user) {
            return true;
        }

        // If authenticated but doesn't own the audit, access is partial
        if ($audit->site && $audit->site->user_id !== $user->id) {
            return true;
        }

        // Owner has full access
        return false;
    }

    /**
     * Calculate overall score from issues.
     *
     * Score starts at 100 and is reduced based on issue severity:
     * - Critical: -10 points
     * - Warning: -5 points
     * - Notice: -2 points
     *
     * @param \Illuminate\Database\Eloquent\Collection $issues
     * @return int
     */
    protected function calculateOverallScore($issues): int
    {
        $score = 100;

        foreach ($issues as $issue) {
            $severity = $issue->severity ?? 'notice';

            match ($severity) {
                'critical' => $score -= 10,
                'warning' => $score -= 5,
                'notice' => $score -= 2,
                default => $score -= 0,
            };
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate category scores from issues.
     *
     * @param \Illuminate\Database\Eloquent\Collection $issues
     * @return array
     */
    protected function calculateCategoryScores($issues): array
    {
        $categories = [
            'technical' => 100,
            'on_page' => 100,
            'content' => 100,
            'mobile' => 100,
        ];

        $categoryPenalties = [
            'technical' => 0,
            'on_page' => 0,
            'content' => 0,
            'mobile' => 0,
        ];

        foreach ($issues as $issue) {
            $severity = $issue->severity ?? 'notice';
            $category = $issue->issueType?->category ?? 'technical';

            // Map category names
            $mappedCategory = match ($category) {
                'meta' => 'on_page',
                'content' => 'content',
                'backlink' => 'technical',
                'performance' => 'technical',
                default => $category,
            };

            if (!isset($categoryPenalties[$mappedCategory])) {
                $mappedCategory = 'technical';
            }

            $penalty = match ($severity) {
                'critical' => 10,
                'warning' => 5,
                'notice' => 2,
                default => 0,
            };

            $categoryPenalties[$mappedCategory] += $penalty;
        }

        foreach ($categories as $category => $score) {
            $categories[$category] = max(0, 100 - $categoryPenalties[$category]);
        }

        return $categories;
    }

    /**
     * Get top issues sorted by severity.
     *
     * @param \Illuminate\Database\Eloquent\Collection $issues
     * @return array
     */
    protected function getTopIssues($issues): array
    {
        $severityOrder = ['critical' => 1, 'warning' => 2, 'notice' => 3];

        $sortedIssues = $issues->sortBy(function ($issue) use ($severityOrder) {
            return $severityOrder[$issue->severity ?? 'notice'] ?? 4;
        })->take(10);

        return $sortedIssues->map(function ($issue) {
            return [
                'category' => $issue->issueType?->category ?? 'technical',
                'issue' => $issue->description ?? $issue->issueType?->name ?? 'Unknown issue',
                'severity' => $issue->severity ?? 'notice',
            ];
        })->values()->toArray();
    }

    /**
     * Hash the IP address for privacy.
     *
     * @param Request $request
     * @return string|null
     */
    protected function hashIp(Request $request): ?string
    {
        $ip = $request->ip();

        if (!$ip) {
            return null;
        }

        return hash('sha256', $ip . config('app.key'));
    }

    /**
     * Generate embed code for the badge.
     *
     * @param string $hash
     * @param int $score
     * @param string $auditUrl
     * @return string
     */
    protected function generateEmbedCode(string $hash, int $score, string $auditUrl): string
    {
        return <<<HTML
<a href="{$auditUrl}" target="_blank" rel="noopener">
  <img src="{$auditUrl}/badge/small.svg" alt="SEO Score: {$score}" />
</a>
HTML;
    }
}
