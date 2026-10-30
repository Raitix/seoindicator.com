<?php

namespace App\Repositories;

use App\Models\ReportBranding;
use Illuminate\Support\Collection;

/**
 * Repository for ReportBranding model operations.
 */
class ReportBrandingRepository
{
    /**
     * Create or update branding for a site.
     */
    public function updateOrCreate(int $siteId, array $data): ReportBranding
    {
        return ReportBranding::updateOrCreate(
            ['site_id' => $siteId],
            $data
        );
    }

    /**
     * Find branding for a site.
     */
    public function findBySite(int $siteId): ?ReportBranding
    {
        return ReportBranding::where('site_id', $siteId)->first();
    }

    /**
     * Delete branding for a site.
     */
    public function deleteBySite(int $siteId): bool
    {
        return ReportBranding::where('site_id', $siteId)->delete() > 0;
    }
}
