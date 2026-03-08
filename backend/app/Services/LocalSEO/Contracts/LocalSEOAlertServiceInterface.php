<?php

namespace App\Services\LocalSEO\Contracts;

use App\Models\BusinessLocation;
use App\Models\LocalSEOAlert;
use Illuminate\Support\Collection;

interface LocalSEOAlertServiceInterface
{
    /**
     * Run all alert checks for a business location.
     */
    public function checkForAlerts(?BusinessLocation $location): void;

    /**
     * Create a new alert with deduplication.
     *
     * @throws \InvalidArgumentException If severity is invalid
     */
    public function createAlert(
        int $userId,
        int $locationId,
        string $type,
        string $severity,
        string $title,
        string $message,
        array $data = []
    ): LocalSEOAlert;

    /**
     * Get unread alerts for a user.
     */
    public function getUnreadAlerts(int $userId, ?int $locationId = null): Collection;

    /**
     * Mark a specific alert as read.
     */
    public function markAsRead(int $alertId): bool;

    /**
     * Mark all alerts as read for a user.
     */
    public function markAllAsRead(int $userId, ?int $locationId = null): int;
}
