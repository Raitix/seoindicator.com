<?php

namespace App\Services\ContentDecay\Contracts;

use App\Models\ContentDecayProject;
use App\Models\User;

/**
 * Interface for ContentDecayDigestService.
 *
 * The weekly digest service that summarizes newly detected decay candidates
 * and recovered pages for notification delivery.
 */
interface ContentDecayDigestServiceInterface
{
    /**
     * Generate a digest for a project.
     *
     * @param ContentDecayProject $project The project to generate digest for
     * @return array Digest data containing:
     *   - new_decay_count: int
     *   - critical_count: int
     *   - recovered_count: int
     *   - total_lost_clicks: int
     *   - total_upside: int
     *   - top_candidates: array
     *   - recovered_pages: array
     */
    public function generateDigest(ContentDecayProject $project): array;

    /**
     * Send the digest notification to a user.
     *
     * @param User $user The user to send the digest to
     * @param array $digest The digest data
     * @return void
     */
    public function sendDigest(User $user, array $digest): void;

    /**
     * Check if a digest should be sent for a project.
     *
     * @param ContentDecayProject $project The project to check
     * @return bool True if digest should be sent, false otherwise
     */
    public function shouldSendDigest(ContentDecayProject $project): bool;
}
