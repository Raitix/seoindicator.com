# SPEC-008: Multi-Site Project Management - Technical Specification

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: [FEAT-008](../FEATURES/FEAT-008.md)
- Tasks: (To be created by SpecSystemAnalyst)

---

## Overview

Organize keywords, competitors, and reports into separate projects/sites for agency and multi-site users. Each project maintains isolated data while allowing cross-project aggregate views.

---

## Architecture

### System Design

```
┌─────────────────────────────────────────────────────────────────┐
│                        Frontend (Next.js)                        │
├─────────────────────────────────────────────────────────────────┤
│  ProjectContext (React Context)                                 │
│  ├── currentProject state                                       │
│  ├── projectList state                                          │
│  └── switchProject() / refreshProjects()                        │
├─────────────────────────────────────────────────────────────────┤
│  Components:                                                    │
│  ├── ProjectSelector (navigation dropdown)                      │
│  ├── ProjectDashboard (project-scoped metrics)                  │
│  ├── ProjectSettings (CRUD for projects)                        │
│  └── AllProjectsView (aggregate metrics)                        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Backend API (Laravel)                        │
├─────────────────────────────────────────────────────────────────┤
│  Middleware:                                                    │
│  ├── SetProjectContext (from header/session)                    │
│  └── EnsureProjectAccess (authorization)                        │
├─────────────────────────────────────────────────────────────────┤
│  Controllers:                                                   │
│  ├── ProjectController (CRUD)                                   │
│  ├── ProjectDashboardController (metrics)                       │
│  └── Existing controllers modified to scope by project          │
├─────────────────────────────────────────────────────────────────┤
│  Services:                                                      │
│  ├── ProjectService (business logic)                            │
│  └── ProjectMetricsService (aggregation)                        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       Database (MySQL)                           │
├─────────────────────────────────────────────────────────────────┤
│  projects (NEW)                                                 │
│  keywords (MODIFY - add project_id)                             │
│  competitors (MODIFY - add project_id)                          │
│  reports (MODIFY - add project_id)                              │
│  alert_settings (MODIFY - add project_id)                       │
└─────────────────────────────────────────────────────────────────┘
```

### Data Flow

```
User selects project
        │
        ▼
Frontend sets ProjectContext
        │
        ▼
API request includes X-Project-ID header
        │
        ▼
Backend middleware validates project access
        │
        ▼
Query scope automatically filters by project_id
        │
        ▼
Response scoped to selected project
```

---

## Database Schema

### New Table: projects

```sql
CREATE TABLE projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(500) NULL,
    notes TEXT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    archived_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_projects_user_id (user_id),
    INDEX idx_projects_archived (archived_at)
);
```

### Modify Existing Tables

```sql
-- Add project_id to keywords
ALTER TABLE keywords 
    ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD INDEX idx_keywords_project_id (project_id),
    ADD CONSTRAINT fk_keywords_project 
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE;

-- Add project_id to competitors
ALTER TABLE competitors 
    ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD INDEX idx_competitors_project_id (project_id),
    ADD CONSTRAINT fk_competitors_project 
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE;

-- Add project_id to reports
ALTER TABLE reports 
    ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD INDEX idx_reports_project_id (project_id),
    ADD CONSTRAINT fk_reports_project 
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE;

-- Add project_id to alert_settings (if exists)
ALTER TABLE alert_settings 
    ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD INDEX idx_alert_settings_project_id (project_id),
    ADD CONSTRAINT fk_alert_settings_project 
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE;
```

### Data Migration Strategy

```sql
-- Step 1: Create default project for each existing user
INSERT INTO projects (user_id, name, domain, is_default, created_at, updated_at)
SELECT 
    id, 
    'Default Project' as name, 
    NULL as domain,
    TRUE as is_default,
    NOW() as created_at,
    NOW() as updated_at
FROM users;

-- Step 2: Assign existing data to default projects
UPDATE keywords k
JOIN projects p ON p.user_id = k.user_id AND p.is_default = TRUE
SET k.project_id = p.id;

UPDATE competitors c
JOIN projects p ON p.user_id = c.user_id AND p.is_default = TRUE
SET c.project_id = p.id;

UPDATE reports r
JOIN projects p ON p.user_id = r.user_id AND p.is_default = TRUE
SET r.project_id = p.id;

-- Step 3: Make project_id NOT NULL after migration
ALTER TABLE keywords MODIFY project_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE competitors MODIFY project_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE reports MODIFY project_id BIGINT UNSIGNED NOT NULL;
```

---

## API Design

### Project CRUD

```yaml
# List all user's projects
GET /api/projects
Response: {
    "data": [
        { "id": 1, "name": "Client A", "domain": "example.com", "is_default": true },
        { "id": 2, "name": "Client B", "domain": "clientb.com", "is_default": false }
    ],
    "meta": { "total": 2, "active": 2, "archived": 0 }
}

# Create project
POST /api/projects
Body: { "name": "Client A", "domain": "https://example.com", "notes": "SEO for..." }
Response: { "id": 1, "name": "Client A", ... }

# Get single project
GET /api/projects/{id}
Response: { "id": 1, "name": "Client A", "domain": "...", "notes": "...", ... }

# Update project
PUT /api/projects/{id}
Body: { "name": "New Name", "domain": "https://newdomain.com", "notes": "Updated" }
Response: { "id": 1, "name": "New Name", ... }

# Archive project (soft delete)
POST /api/projects/{id}/archive
Response: { "id": 1, "archived_at": "2026-02-20T..." }

# Restore archived project
POST /api/projects/{id}/restore
Response: { "id": 1, "archived_at": null }

# Delete project (hard delete with confirmation)
DELETE /api/projects/{id}?confirm=true
Response: { "success": true, "deleted_id": 1 }

# Set default project
POST /api/projects/{id}/set-default
Response: { "id": 1, "is_default": true }
```

### Project Dashboard

```yaml
# Get project metrics
GET /api/projects/{id}/metrics
Response: {
    "project_id": 1,
    "total_keywords": 150,
    "average_position": 12.4,
    "top_10_keywords": 23,
    "position_changes_7d": { "improved": 12, "declined": 8, "unchanged": 130 },
    "serp_features_count": 15,
    "competitors_count": 5,
    "last_updated": "2026-02-20T10:00:00Z"
}

# Get all projects aggregate metrics
GET /api/projects/aggregate
Response: {
    "total_projects": 5,
    "total_keywords": 750,
    "average_position_all": 14.2,
    "top_10_all": 89,
    "projects_breakdown": [
        { "id": 1, "name": "Client A", "keywords": 150, "avg_position": 12.4 },
        ...
    ]
}
```

### Modified Existing Endpoints

All existing endpoints now require project context and filter results:

```yaml
# Keywords - scoped to project
GET /api/keywords
Header: X-Project-ID: 1
Response: { "data": [...] } // Only keywords for project 1

# Competitors - scoped to project
GET /api/competitors
Header: X-Project-ID: 1
Response: { "data": [...] } // Only competitors for project 1

# Reports - scoped to project
GET /api/reports
Header: X-Project-ID: 1
Response: { "data": [...] } // Only reports for project 1
```

---

## Frontend Components

### ProjectContext (React Context)

```typescript
interface ProjectContextType {
  projects: Project[];
  currentProject: Project | null;
  isLoading: boolean;
  switchProject: (projectId: number) => void;
  refreshProjects: () => Promise<void>;
  createProject: (data: CreateProjectDTO) => Promise<Project>;
  updateProject: (id: number, data: UpdateProjectDTO) => Promise<Project>;
  deleteProject: (id: number) => Promise<void>;
}

interface Project {
  id: number;
  name: string;
  domain: string | null;
  notes: string | null;
  is_default: boolean;
  archived_at: string | null;
}
```

### ProjectSelector Component

```typescript
// Navigation dropdown for project switching
<ProjectSelector 
  projects={projects}
  currentProject={currentProject}
  onSelect={switchProject}
  onCreateNew={() => setShowCreateModal(true)}
  showAllProjectsOption={true}
/>
```

### ProjectDashboard Component

```typescript
// Dashboard scoped to current project
<ProjectDashboard 
  projectId={currentProject?.id}
  metrics={projectMetrics}
/>
```

### AllProjectsView Component

```typescript
// Aggregate view across all projects
<AllProjectsView 
  aggregateMetrics={aggregateMetrics}
  projectsBreakdown={projectsBreakdown}
/>
```

---

## Backend Implementation

### Model: Project.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'domain',
        'notes',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'archived_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function keywords()
    {
        return $this->hasMany(Keyword::class);
    }

    public function competitors()
    {
        return $this->hasMany(Competitor::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
```

### Middleware: SetProjectContext.php

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Project;

class SetProjectContext
{
    public function handle(Request $request, Closure $next)
    {
        $projectId = $request->header('X-Project-ID') 
                     ?? session('current_project_id');

        if ($projectId) {
            $project = Project::where('user_id', auth()->id())
                              ->where('id', $projectId)
                              ->first();
            
            if ($project) {
                app()->instance('current_project', $project);
            }
        }

        return $next($request);
    }
}
```

### Global Scope: ProjectScope.php

```php
<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ProjectScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $project = app('current_project');
        
        if ($project) {
            $builder->where('project_id', $project->id);
        }
    }
}
```

---

## Security Considerations

### Data Isolation

1. **Row-Level Security**: Every query scoped to user's projects only
2. **Middleware Validation**: Ensure user owns project before access
3. **API Authorization**: Policy-based checks on all project operations

### Authorization Policy

```php
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
}
```

### Input Validation

```php
$rules = [
    'name' => 'required|string|max:255',
    'domain' => 'nullable|url|max:500',
    'notes' => 'nullable|string|max:5000',
];
```

---

## Performance Considerations

### Database Indexes

- `idx_projects_user_id` for user's project list
- `idx_keywords_project_id` for project-scoped keyword queries
- `idx_competitors_project_id` for project-scoped competitor queries
- `idx_reports_project_id` for project-scoped report queries

### Caching Strategy

```php
// Cache project list per user
Cache::remember("user.{$userId}.projects", 3600, function() use ($userId) {
    return Project::forUser($userId)->active()->get();
});

// Cache project metrics
Cache::remember("project.{$projectId}.metrics", 1800, function() use ($projectId) {
    return ProjectMetricsService::calculate($projectId);
});
```

### Query Optimization

- Use eager loading for project relationships
- Implement lazy loading for project selector
- Pagination for large keyword lists within projects

---

## Free Tools Compliance

✅ **No paid services required**

All functionality is implemented using:
- Laravel Eloquent ORM (free)
- MySQL database (free)
- Next.js frontend (free)
- Standard React patterns (free)

---

## Dependencies

### None External

This feature is self-contained and uses only the existing tech stack.

### Internal Dependencies

- User authentication system (existing)
- Keywords, Competitors, Reports models (existing - modified)

---

## Testing Requirements

### Unit Tests
- Project model relationships
- Project policy authorization
- ProjectScope application
- ProjectService CRUD operations

### Feature Tests
- Project CRUD API endpoints
- Project switching flow
- Data isolation between projects
- Aggregate metrics calculation

### Integration Tests
- Project context in keyword queries
- Project context in competitor queries
- Project context in report queries
- Archive/restore/delete flows

---

## Migration Rollout Plan

1. **Phase 1**: Create projects table
2. **Phase 2**: Add project_id columns (nullable)
3. **Phase 3**: Create default projects for existing users
4. **Phase 4**: Migrate existing data to default projects
5. **Phase 5**: Make project_id NOT NULL
6. **Phase 6**: Deploy frontend changes
7. **Phase 7**: Enable project selection UI

---

## Comments
_Agent communication log_

### [2026-02-20 17:00] Agent: Architect
Created technical specification for FEAT-008. Risk level confirmed as Low - standard multi-tenancy pattern with well-documented approach. No paid services required. Database migration strategy includes safe rollout with nullable columns first, then data migration, then NOT NULL constraint.

### [2026-02-20 17:00] Agent: Architect
Key architectural decisions:
1. Soft deletes for archive functionality (archived_at column)
2. Global scope pattern for automatic project filtering
3. React Context for frontend project state
4. X-Project-ID header for API project context
5. Default project per user for backward compatibility

### [2026-02-20 17:00] Agent: Architect
Ready for SpecSystemAnalyst to break into tasks.

### [2026-02-28 14:10 GMT+2] Agent: ProjectManager
**Prioritization Status Review:**
- All 10 tasks have been previously prioritized and are in progress or complete
- **Completed (5):** TASK-8-1, TASK-8-4, TASK-8-6, TASK-8-7, TASK-8-9
- **In Progress (4):** TASK-8-2, TASK-8-3, TASK-8-5, TASK-8-10 (To Write Tests)
- **Documentation (1):** TASK-8-8 (Frontend Components)

**Priority Summary:**
- All tasks: **High Priority**
- Risk distribution: Low (2), Medium (7), Low (1)

**Dependency Chain Verified:**
1. TASK-8-1 (Projects Table) → Done ✅
2. TASK-8-2 (Modify Tables) → To Write Tests (depends on 8-1)
3. TASK-8-3 (Data Migration) → To Write Tests (depends on 8-1, 8-2)
4. TASK-8-4 (Middleware) → Done ✅
5. TASK-8-5 (Global Scope) → To Write Tests (depends on 8-4)
6. TASK-8-6 (Policies) → Done ✅
7. TASK-8-7 (CRUD Endpoints) → Done ✅
8. TASK-8-8 (Frontend) → Documentation (depends on 8-7)
9. TASK-8-9 (Unit Tests) → Done ✅
10. TASK-8-10 (Integration Tests) → To Write Tests (depends on 8-7, 8-8)

**Status updated:** `To Prioritize` → `To Write Tests` (tasks already prioritized by previous session)
