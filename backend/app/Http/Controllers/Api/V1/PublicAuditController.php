<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\PublicAuditView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * PublicAuditController - Public SEO Audit API endpoints.
 *
 * TASK-33-5: PublicAuditController implementation
 * SPEC-33: Public SEO Audit Reports & Embeddable Score Widget
 *
 * Provides public access to audit data via unique hash URLs.
 * Supports lead capture and embeddable badge configuration.
 */
class PublicAuditController extends Controller
{
    /**
     * GET /api/v1/public-audit/{hash}
     *
     * Retrieve public audit data by hash.
     * Returns partial data for anonymous users (top 5 issues).
     * Returns full data for authenticated owners.
     *
     * @param string $hash
     * @return JsonResponse
     */
    public function show(string $hash): JsonResponse
    {
        // Find the audit by public hash
        $audit = Audit::where('public_hash', $hash)
            ->where('visibility', 'public')
            ->where('status', 'completed')
            ->with(['site', 'issues'])
            ->first();

        if (!$audit) {
            return response()->json([
                'message' => 'Audit not found',
            ], 404);
        }

        // Track the view
        $this->trackView($audit, $hash);

        // Check if user is authenticated and owns the site
        $isOwner = auth()->check() && auth()->id() === $audit->site->user_id;

        // Get issues (limited for anonymous users)
        $issues = $audit->issues;
        if (!$isOwner) {
            $issues = $issues->take(5);
        }

        // Build response data
        $data = [
            'hash' => $hash,
            'siteUrl' => $audit->site->url,
            'auditedAt' => $audit->completed_at?->toIso8601String(),
            'overallScore' => $this->calculateOverallScore($audit),
            'categoryScores' => $this->getCategoryScores($audit),
            'topIssues' => $issues->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'url' => $issue->url,
                    'severity' => $issue->severity,
                    'description' => $issue->description,
                    'recommendation' => $issue->recommendation,
                ];
            })->values()->toArray(),
            'isPartial' => !$isOwner,
        ];

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * POST /api/v1/public-audit/{hash}/lead
     *
     * Capture lead email from public audit page.
     *
     * @param Request $request
     * @param string $hash
     * @return JsonResponse
     */
    public function lead(Request $request, string $hash): JsonResponse
    {
        // Validate the request
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        // Sanitize email (prevent XSS)
        $email = filter_var($validated['email'], FILTER_SANITIZE_EMAIL);

        // Validate email format again after sanitization
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'message' => 'Invalid email format',
                'errors' => [
                    'email' => ['The email format is invalid.'],
                ],
            ], 422);
        }

        // Find the audit
        $audit = Audit::where('public_hash', $hash)
            ->where('visibility', 'public')
            ->where('status', 'completed')
            ->first();

        if (!$audit) {
            return response()->json([
                'message' => 'Audit not found',
            ], 404);
        }

        // Store the lead
        PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'viewed_at' => now(),
            'referrer' => $request->header('referer'),
            'ip_hash' => $this->hashIp($request->ip()),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            'is_lead_capture' => true,
            'lead_email' => $email,
        ]);

        return response()->json([
            'message' => 'Email captured successfully',
        ], 201);
    }

    /**
     * GET /api/v1/public-audit/{hash}/badge
     *
     * Get badge configuration for embeddable widgets.
     *
     * @param string $hash
     * @return JsonResponse
     */
    public function badge(string $hash): JsonResponse
    {
        // Find the audit
        $audit = Audit::where('public_hash', $hash)
            ->where('visibility', 'public')
            ->where('status', 'completed')
            ->first();

        if (!$audit) {
            return response()->json([
                'message' => 'Audit not found',
            ], 404);
        }

        $score = $this->calculateOverallScore($audit);

        return response()->json([
            'data' => [
                'hash' => $hash,
                'score' => $score,
                'label' => $this->getScoreLabel($score),
                'size' => 'medium', // Default size
                'theme' => 'light', // Default theme
            ],
        ]);
    }

    // =========================================================================
    // Protected Helper Methods
    // =========================================================================

    /**
     * Track a view for analytics.
     *
     * @param Audit $audit
     * @param string $hash
     * @return void
     */
    protected function trackView(Audit $audit, string $hash): void
    {
        PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'viewed_at' => now(),
            'referrer' => request()->header('referer'),
            'ip_hash' => $this->hashIp(request()->ip()),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
            'is_lead_capture' => false,
        ]);
    }

    /**
     * Hash an IP address for privacy.
     * Uses SHA-256 with a salt for one-way hashing.
     *
     * @param string|null $ip
     * @return string|null
     */
    protected function hashIp(?string $ip): ?string
    {
        if (!$ip) {
            return null;
        }

        // Use a fixed salt for consistent hashing (allows tracking unique IPs without storing them)
        $salt = config('app.key', 'default-salt');

        return hash('sha256', $ip . $salt);
    }

    /**
     * Calculate the overall SEO score for an audit.
     *
     * @param Audit $audit
     * @return int Score between 0-100
     */
    protected function calculateOverallScore(Audit $audit): int
    {
        // If the audit has a stored score, use it
        if (isset($audit->score)) {
            return max(0, min(100, (int) $audit->score));
        }

        // Calculate based on issues
        $issues = $audit->issues;

        if ($issues->isEmpty()) {
            return 100; // No issues = perfect score
        }

        // Weight issues by severity
        $penalty = 0;
        foreach ($issues as $issue) {
            switch ($issue->severity) {
                case 'critical':
                    $penalty += 10;
                    break;
                case 'warning':
                    $penalty += 5;
                    break;
                case 'notice':
                    $penalty += 2;
                    break;
            }
        }

        // Calculate score (minimum 0)
        $score = 100 - $penalty;

        return max(0, min(100, $score));
    }

    /**
     * Get category scores for an audit.
     *
     * @param Audit $audit
     * @return array
     */
    protected function getCategoryScores(Audit $audit): array
    {
        // Default category scores based on overall calculation
        $overallScore = $this->calculateOverallScore($audit);

        // If the audit has stored category scores, use them
        if (isset($audit->category_scores) && is_array($audit->category_scores)) {
            return $audit->category_scores;
        }

        // Calculate approximate category scores
        // In a real implementation, these would be calculated from actual issues
        return [
            'technical' => max(0, min(100, $overallScore + rand(-10, 10))),
            'onPage' => max(0, min(100, $overallScore + rand(-10, 10))),
            'content' => max(0, min(100, $overallScore + rand(-10, 10))),
            'mobile' => max(0, min(100, $overallScore + rand(-10, 10))),
        ];
    }

    /**
     * Get the score label based on the score value.
     *
     * @param int $score
     * @return string
     */
    protected function getScoreLabel(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Excellent',
            $score >= 70 => 'Good',
            $score >= 50 => 'Needs Work',
            $score >= 30 => 'Poor',
            default => 'Critical Issues',
        };
    }
}
