<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetProjectContext Middleware.
 *
 * Injects the current project's context into the application at runtime.
 * This middleware is the backbone of multi-tenant project scoping, ensuring
 * all subsequent queries and operations are scoped to the active project.
 *
 * Project ID Sources (in order of precedence):
 * 1. X-Project-ID header (explicit request)
 * 2. current_project_id session value (persistent selection)
 *
 * Security:
 * - Only authenticated users can have project context
 * - Users can only access their own projects (user_id === auth()->id())
 * - Invalid/missing project IDs fail gracefully (no exception)
 * - Archived projects ARE accessible (business decision)
 */
class SetProjectContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process if user is authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        // Get project ID from header (priority) or session
        $projectId = $this->getProjectId($request);

        if ($projectId !== null && $projectId > 0) {
            $this->bindProject($projectId);
        }

        return $next($request);
    }

    /**
     * Extract project ID from request (header takes precedence).
     */
    private function getProjectId(Request $request): ?int
    {
        // Check header first (explicit request wins)
        $headerValue = $request->header('X-Project-ID');
        if ($headerValue !== null && $headerValue !== '') {
            $id = $this->sanitizeProjectId($headerValue);
            if ($id !== null) {
                return $id;
            }
        }

        // Fall back to session (only if session is available)
        if ($request->hasSession()) {
            $sessionValue = $request->session()->get('current_project_id');
            if ($sessionValue !== null) {
                return $this->sanitizeProjectId($sessionValue);
            }
        }

        return null;
    }

    /**
     * Sanitize and validate project ID.
     *
     * - Trims whitespace from strings
     * - Validates as positive integer
     * - Returns null for invalid values (no exceptions)
     */
    private function sanitizeProjectId(mixed $value): ?int
    {
        // Handle string with whitespace
        if (is_string($value)) {
            $value = trim($value);
        }

        // Convert to integer using filter_var for safe validation
        $id = filter_var($value, FILTER_VALIDATE_INT);

        // Must be positive integer
        return ($id !== false && $id > 0) ? (int) $id : null;
    }

    /**
     * Bind project to application container if user owns it.
     *
     * Security: Only binds project if it belongs to the authenticated user.
     * This prevents IDOR attacks where one user tries to access another's project.
     */
    private function bindProject(int $projectId): void
    {
        $project = Project::where('id', $projectId)
            ->where('user_id', Auth::id())
            ->first();

        if ($project) {
            app()->instance('current_project', $project);
        }
    }
}
