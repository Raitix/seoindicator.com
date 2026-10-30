<?php

namespace App\Repositories;

use App\Models\IssueType;
use Illuminate\Support\Facades\Cache;

/**
 * Repository for IssueType model operations.
 *
 * @see TASK-14-8
 */
class IssueTypeRepository
{
    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 3600;

    /**
     * Find an issue type by its type identifier.
     *
     * @param string $type The issue type identifier
     * @return object|null Object with id, severity, and other properties
     */
    public function findByType(string $type): ?object
    {
        $cacheKey = "issue_type:{$type}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($type) {
            $issueType = IssueType::where('type', $type)
                ->orWhere('name', $type)
                ->first();

            if (!$issueType) {
                return null;
            }

            return (object) [
                'id' => $issueType->id,
                'type' => $issueType->type ?? $issueType->name,
                'category' => $issueType->category,
                'severity' => $issueType->severity ?? $issueType->severity_default ?? 'notice',
                'description' => $issueType->description,
                'recommendation_template' => $issueType->recommendation_template,
            ];
        });
    }

    /**
     * Get the default severity for an issue type.
     *
     * @param string $type The issue type identifier
     * @return string The severity level (critical, warning, notice)
     */
    public function getDefaultSeverity(string $type): string
    {
        $issueType = $this->findByType($type);

        return $issueType?->severity ?? 'notice';
    }

    /**
     * Get all issue types.
     *
     * @return array Array of issue type objects
     */
    public function getAll(): array
    {
        return Cache::remember('issue_types:all', $this->cacheTtl, function () {
            return IssueType::all()->map(function ($type) {
                return (object) [
                    'id' => $type->id,
                    'type' => $type->type ?? $type->name,
                    'category' => $type->category,
                    'severity' => $type->severity ?? $type->severity_default ?? 'notice',
                    'description' => $type->description,
                    'recommendation_template' => $type->recommendation_template,
                ];
            })->all();
        });
    }

    /**
     * Clear the cache for issue types.
     */
    public function clearCache(): void
    {
        Cache::forget('issue_types:all');
        
        // Clear individual type caches
        $types = IssueType::all();
        foreach ($types as $type) {
            Cache::forget("issue_type:{$type->type}");
            Cache::forget("issue_type:{$type->name}");
        }
    }
}
