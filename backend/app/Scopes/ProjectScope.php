<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * ProjectScope - Global Eloquent Scope for Project-Based Data Isolation.
 *
 * This global scope automatically filters queries by the current project
 * when set via the SetProjectContext middleware (TASK-8-4).
 *
 * The scope checks for a bound 'current_project' in the service container
 * and adds a WHERE clause to filter by project_id.
 *
 * @see TASK-8-5
 * @see TASK-8-4 (SetProjectContext middleware)
 */
class ProjectScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Get the current project from the service container
        // This is set by SetProjectContext middleware
        try {
            $project = app('current_project');
        } catch (\Exception $e) {
            // No project binding exists - do not filter
            return;
        }

        // Only apply the scope if a valid project exists with an ID
        if ($project && $project->id) {
            $builder->where('project_id', $project->id);
        }
    }
}
