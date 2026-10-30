<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BadgeClickRequest;
use App\Http\Requests\Api\V1\BadgeImpressionRequest;
use App\Models\Audit;
use App\Models\BadgeImpression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Badge Tracking Controller (TASK-33-17, TASK-33-18, SPEC-33)
 * 
 * Handles badge impression and click tracking for public audit badges.
 * Used by embeddable widgets on external websites.
 */
class BadgeTrackingController extends Controller
{
    /**
     * Record a badge impression.
     * 
     * Called when a badge is viewed on an external website.
     * Used for analytics and engagement metrics.
     *
     * @param BadgeImpressionRequest $request
     * @return JsonResponse|Response
     */
    public function impression(BadgeImpressionRequest $request): JsonResponse|Response
    {
        $hash = strtolower($request->validated('hash'));
        
        // Find the audit by public_hash where visibility is public
        $audit = Audit::where('public_hash', $hash)
            ->where('visibility', 'public')
            ->first();
        
        // Return 404 for non-existent or non-public audits
        if (!$audit) {
            return response()->json([
                'success' => false,
                'error' => 'Audit not found',
            ], 404);
        }
        
        // Extract referrer domain from request body or Referer header
        $referrerDomain = $request->validated('referrer_domain');
        
        if (!$referrerDomain && $request->header('Referer')) {
            $referrerDomain = BadgeImpression::extractDomain($request->header('Referer'));
        }
        
        // Get client IP and hash it
        $clientIp = $request->ip();
        $ipHash = $clientIp ? BadgeImpression::hashIp($clientIp) : null;
        
        // Create the impression record
        BadgeImpression::create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'referrer_domain' => $referrerDomain,
            'impression_at' => now(),
            'ip_hash' => $ipHash,
        ]);
        
        // Return 204 No Content on success
        return response()->noContent();
    }

    /**
     * Record a badge click.
     * 
     * Called when a user clicks on a badge on an external website.
     * Used for analytics and tracking click-through rates.
     *
     * Correlates clicks with existing impressions by IP hash + hash within 24 hours.
     * Creates new impression if no existing one found (with clicked_at already set).
     *
     * @param BadgeClickRequest $request
     * @return JsonResponse|Response|RedirectResponse
     */
    public function click(BadgeClickRequest $request): JsonResponse|Response|RedirectResponse
    {
        $hash = $request->getHash();
        $clickTarget = $request->getClickTarget();
        
        // Find the audit by public_hash (case-insensitive) where visibility is public
        $audit = Audit::whereRaw('LOWER(public_hash) = ?', [$hash])
            ->where('visibility', 'public')
            ->first();
        
        // Return 404 for non-existent or non-public audits
        if (!$audit) {
            return response()->json([
                'success' => false,
                'error' => 'Audit not found',
            ], 404);
        }
        
        // Get client IP and hash it
        $clientIp = $request->ip();
        $ipHash = $clientIp ? BadgeImpression::hashIp($clientIp) : null;
        
        // Find existing impression within last 24 hours by IP hash + hash
        // Using > instead of >= to exclude impressions exactly 24h old (boundary is exclusive)
        $impression = null;
        if ($ipHash) {
            $impression = BadgeImpression::where('public_hash', $hash)
                ->where('ip_hash', $ipHash)
                ->where('impression_at', '>', now()->subDay())
                ->latest('impression_at')
                ->first();
        }
        
        if ($impression) {
            // Update existing impression with click data
            $impression->update([
                'clicked_at' => now(),
                'click_target' => $clickTarget,
            ]);
        } else {
            // Create new impression with clicked_at already set
            BadgeImpression::create([
                'audit_id' => $audit->id,
                'public_hash' => $hash,
                'ip_hash' => $ipHash,
                'impression_at' => now(),
                'clicked_at' => now(),
                'click_target' => $clickTarget,
            ]);
        }
        
        // Check if redirect is requested
        if ($request->query('redirect')) {
            $url = $clickTarget === 'audit'
                ? url("/audit/{$hash}/")
                : 'https://seoindicator.com/';
            
            return redirect($url);
        }
        
        // Return 204 No Content on success
        return response()->noContent();
    }
}
