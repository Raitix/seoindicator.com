<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Annotation;
use App\Models\Keyword;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Annotation Policy
 *
 * Authorization rules for annotation operations.
 *
 * @see TASK-9-5
 */
class AnnotationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any annotations for a keyword.
     *
     * @param User $user
     * @param Keyword $keyword
     * @return bool
     */
    public function viewAny(User $user, Keyword $keyword): bool
    {
        return $this->userOwnsKeyword($user, $keyword);
    }

    /**
     * Determine whether the user can view the annotation.
     *
     * @param User $user
     * @param Annotation $annotation
     * @return bool
     */
    public function view(User $user, Annotation $annotation): bool
    {
        return $this->userOwnsAnnotation($user, $annotation);
    }

    /**
     * Determine whether the user can create annotations for a keyword.
     *
     * @param User $user
     * @param Keyword $keyword
     * @return bool
     */
    public function create(User $user, Keyword $keyword): bool
    {
        return $this->userOwnsKeyword($user, $keyword);
    }

    /**
     * Determine whether the user can update the annotation.
     *
     * @param User $user
     * @param Annotation $annotation
     * @return bool
     */
    public function update(User $user, Annotation $annotation): bool
    {
        return $this->userOwnsAnnotation($user, $annotation);
    }

    /**
     * Determine whether the user can delete the annotation.
     *
     * @param User $user
     * @param Annotation $annotation
     * @return bool
     */
    public function delete(User $user, Annotation $annotation): bool
    {
        return $this->userOwnsAnnotation($user, $annotation);
    }

    /**
     * Check if user owns the keyword.
     *
     * @param User $user
     * @param Keyword $keyword
     * @return bool
     */
    protected function userOwnsKeyword(User $user, Keyword $keyword): bool
    {
        // Check direct ownership
        if ($keyword->user_id === $user->id) {
            return true;
        }

        // Check ownership through site
        if ($keyword->site && $keyword->site->user_id === $user->id) {
            return true;
        }

        // Check project membership
        if ($keyword->site && $keyword->site->project_id) {
            return $user->projects()
                ->where('projects.id', $keyword->site->project_id)
                ->exists();
        }

        return false;
    }

    /**
     * Check if user owns the annotation through keyword/project.
     *
     * @param User $user
     * @param Annotation $annotation
     * @return bool
     */
    protected function userOwnsAnnotation(User $user, Annotation $annotation): bool
    {
        // Check through project ownership
        if ($annotation->project_id) {
            $projectOwned = $user->projects()
                ->where('projects.id', $annotation->project_id)
                ->exists();
            
            if ($projectOwned) {
                return true;
            }
        }

        // Check through keyword ownership
        if ($annotation->keyword) {
            return $this->userOwnsKeyword($user, $annotation->keyword);
        }

        return false;
    }
}
