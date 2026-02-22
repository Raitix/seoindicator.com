<?php

namespace App\Repositories;

use App\Models\ReportTemplate;
use Illuminate\Database\Eloquent\Collection;

/**
 * Report Template Repository for SPEC-004-3.
 */
class ReportTemplateRepository
{
    /**
     * Create a template.
     */
    public function create(array $data): ReportTemplate
    {
        return ReportTemplate::create($data);
    }

    /**
     * Find template by ID.
     */
    public function find(int $id): ?ReportTemplate
    {
        return ReportTemplate::find($id);
    }

    /**
     * Find templates by site.
     */
    public function findBySite(?int $siteId): Collection
    {
        return ReportTemplate::where('site_id', $siteId)->orderByDesc('created_at')->get();
    }

    /**
     * Find default template by site.
     */
    public function findDefault(int $siteId): ?ReportTemplate
    {
        return ReportTemplate::where('site_id', $siteId)->where('is_default', true)->first();
    }

    /**
     * Update template.
     */
    public function update(int $id, array $data): bool
    {
        $template = ReportTemplate::find($id);

        if (!$template) {
            return false;
        }

        return $template->update($data);
    }

    /**
     * Delete template.
     */
    public function delete(int $id): bool
    {
        $template = ReportTemplate::find($id);

        if (!$template) {
            return false;
        }

        return (bool) $template->delete();
    }
}
