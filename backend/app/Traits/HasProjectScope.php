<?php

namespace App\Traits;

use App\Scopes\ProjectScope;

/**
 * HasProjectScope Trait.
 *
 * Provides automatic project-based data isolation for Eloquent models.
 * Models using this trait will automatically have their queries filtered
 * by the current project context.
 *
 * @see TASK-8-5
 * @see \App\Scopes\ProjectScope
 *
 * @example
 * class Keyword extends Model
 * {
 *     use HasProjectScope;
 * }
 */
trait HasProjectScope
{
    /**
     * Boot the HasProjectScope trait for a model.
     *
     * Automatically registers the ProjectScope global scope.
     *
     * @return void
     */
    protected static function bootHasProjectScope(): void
    {
        static::addGlobalScope(new ProjectScope());
    }
}
