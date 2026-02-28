# TEST-8-5: ProjectScope Global Scope Tests

## Overview
Test documentation for the ProjectScope global Eloquent scope implementation. This scope automatically filters queries by the current project when set via the SetProjectContext middleware.

## Test File Location
`backend/tests/Unit/Scopes/ProjectScopeTest.php`

## Test Categories

### PS-001: Scope Application (Basic)
| Test ID | Scenario | Expected Result |
|---------|----------|-----------------|
| PS-001-01 | Query with project context set | Adds `WHERE project_id = ?` clause |
| PS-001-02 | Query without project context | No filter applied |
| PS-001-03 | Query with null current_project | No filter applied |
| PS-001-04 | Query with project_id = null in context | No filter applied |

### PS-002: Model Integration
| Test ID | Scenario | Expected Result |
|---------|----------|-----------------|
| PS-002-01 | Keyword model uses ProjectScope | Keyword queries filtered by project |
| PS-002-02 | Competitor model uses ProjectScope | Competitor queries filtered by project |
| PS-002-03 | Report model uses ProjectScope | Report queries filtered by project |
| PS-002-04 | AlertSettings model uses ProjectScope | AlertSettings queries filtered by project |

### PS-003: Scope Removal (Admin Override)
| Test ID | Scenario | Expected Result |
|---------|----------|-----------------|
| PS-003-01 | `withoutGlobal(ProjectScope::class)` | Removes filter from query |
| PS-003-02 | `withoutGlobalScope('project')` | Removes filter from query |
| PS-003-03 | Admin user can bypass scope | Returns all records |
| PS-003-04 | Multiple scope removal methods work | All methods remove filter |

### PS-004: Edge Cases
| Test ID | Scenario | Expected Result |
|---------|----------|-----------------|
| PS-004-01 | Query with soft-deleted models | Scope still applies |
| PS-004-02 | Query with trashed records | Scope applies to trashed queries |
| PS-005-03 | Query with multiple conditions | Scope ANDed with existing conditions |
| PS-004-04 | Query with eager loading | Scope applies to eager loaded relations |
| PS-004-05 | Query with joins | Scope applies correctly with joins |
| PS-004-06 | Query with subqueries | Scope applies to main query |
| PS-004-07 | Query with unions | Scope applies to each union part |

### PS-005: Security Tests
| Test ID | Scenario | Expected Result |
|---------|----------|-----------------|
| PS-005-01 | Cross-project data access blocked | No records from other projects returned |
| PS-005-02 | SQL injection via project ID | Blocked (uses parameterized query) |
| PS-005-03 | Project ID manipulation | Ignored, uses app('current_project') only |
| PS-005-04 | Mass assignment with project_id | Scope overrides any project_id in query |

### PS-006: Performance Tests
| Test ID | Scenario | Expected Result |
|---------|----------|-----------------|
| PS-006-01 | Large dataset query | Index used for project_id |
| PS-006-02 | Complex query with scope | No N+1 issues |
| PS-006-03 | Scope overhead measurement | <1ms overhead per query |

### PS-007: Integration with Other Scopes
| Test ID | Scenario | Expected Result |
|---------|----------|-----------------|
| PS-007-01 | Combined with SoftDeletingScope | Both scopes apply correctly |
| PS-007-02 | Combined with custom scope | Both scopes AND together |
| PS-007-03 | Order of scope application | ProjectScope applies before SoftDeletingScope |

### PS-008: Context Binding
| Test ID | Scenario | Expected Result |
|---------|----------|-----------------|
| PS-008-01 | Project bound via app()->instance() | Scope uses bound project |
| PS-008-02 | Project not bound in container | No filter applied |
| PS-008-03 | Project bound but user_id mismatch | Scope uses bound project (trust middleware) |

## Test Implementation Details

### Mock Strategy
- Mock `app('current_project')` to return controlled Project instances
- Use in-memory SQLite for query builder tests
- Create test models that use ProjectScope trait

### Test Model Setup
```php
// Test model for scope testing
class TestKeyword extends Model
{
    use HasProjectScope;
    
    protected $table = 'keywords';
    protected $fillable = ['project_id', 'keyword'];
}
```

### Scope Implementation Template
```php
namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ProjectScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $project = app('current_project');
        
        if ($project && $project->id) {
            $builder->where('project_id', $project->id);
        }
    }
}
```

### Model Trait Alternative
```php
namespace App\Traits;

use App\Scopes\ProjectScope;

trait HasProjectScope
{
    protected static function bootHasProjectScope(): void
    {
        static::addGlobalScope(new ProjectScope());
    }
}
```

## Dependencies
- TASK-8-2: Requires `project_id` column on target tables (for integration tests)
- TASK-8-4: SetProjectContext middleware sets `app('current_project')`

## Test Execution Commands
```bash
# Run all ProjectScope tests
php artisan test --filter=ProjectScopeTest

# Run with coverage
php artisan test --filter=ProjectScopeTest --coverage

# Run specific test category
php artisan test --filter="PS_001"
```

## Expected Test Count
- Total tests: 40+
- Assertions: 80+

## Status
- Created: 2026-02-28
- Test file: Pending creation
