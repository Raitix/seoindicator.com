# SPEC-009: Keyword Annotations - Technical Specification

## STATUS
`To Break Into Tasks`

## PRIORITY
`Low`

## RISK
`Low`

## Related
- Feature: [FEAT-009](../FEATURES/FEAT-009.md)
- Tasks: (To be created by SpecSystemAnalyst)
- Enhances: SPEC-001 (context for rankings), SPEC-004 (reports include annotations)

---

## Overview

Allow users to add date-stamped notes and annotations to keywords, creating a narrative timeline that helps correlate SEO activities with ranking changes. Simple CRUD feature with category tagging.

---

## Architecture

### System Design

```
┌─────────────────────────────────────────────────────────────────┐
│                        Frontend (Next.js)                        │
├─────────────────────────────────────────────────────────────────┤
│  Components:                                                    │
│  ├── AnnotationList (keyword detail page)                       │
│  ├── AnnotationForm (add/edit modal)                            │
│  ├── AnnotationMarker (chart integration - Phase 2)             │
│  └── AnnotationTimeline (project-wide view - Phase 2)           │
├─────────────────────────────────────────────────────────────────┤
│  Integration Points:                                            │
│  ├── KeywordDetailPage (annotation list section)                │
│  └── ReportExport (include annotations in PDF)                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Backend API (Laravel)                        │
├─────────────────────────────────────────────────────────────────┤
│  Controllers:                                                   │
│  └── AnnotationController (CRUD + filtering)                    │
├─────────────────────────────────────────────────────────────────┤
│  Resources:                                                     │
│  └── AnnotationResource (JSON transformation)                   │
├─────────────────────────────────────────────────────────────────┤
│  Services:                                                      │
│  └── AnnotationService (business logic)                         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       Database (MySQL)                           │
├─────────────────────────────────────────────────────────────────┤
│  annotations (NEW)                                              │
│  └── Linked to keywords via foreign key                         │
└─────────────────────────────────────────────────────────────────┘
```

### Data Model

```
┌──────────────────┐     ┌──────────────────┐
│     users        │     │    projects      │
├──────────────────┤     ├──────────────────┤
│ id               │◄────│ user_id          │
│ ...              │     │ id               │
└──────────────────┘     │ ...              │
                         └────────┬─────────┘
                                  │
         ┌────────────────────────┼────────────────────────┐
         │                        │                        │
         ▼                        ▼                        ▼
┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│    keywords      │     │   competitors    │     │     reports      │
├──────────────────┤     ├──────────────────┤     ├──────────────────┤
│ id               │     │ id               │     │ id               │
│ project_id   ────┼────►│ project_id       │     │ project_id       │
│ ...              │     │ ...              │     │ ...              │
└────────┬─────────┘     └──────────────────┘     └──────────────────┘
         │
         ▼
┌──────────────────┐
│   annotations    │
├──────────────────┤
│ id               │
│ keyword_id   ────┼────► keywords.id
│ project_id   ────┼────► projects.id (denormalized for queries)
│ date             │
│ title            │
│ description      │
│ category         │
│ created_at       │
│ updated_at       │
└──────────────────┘
```

---

## Database Schema

### New Table: annotations

```sql
CREATE TABLE annotations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_annotations_keyword_id (keyword_id),
    INDEX idx_annotations_project_id (project_id),
    INDEX idx_annotations_date (date),
    INDEX idx_annotations_category (category)
);
```

### Category Enum (Application Level)

```php
enum AnnotationCategory: string
{
    case CONTENT_UPDATE = 'content_update';
    case LINK_BUILDING = 'link_building';
    case TECHNICAL_FIX = 'technical_fix';
    case ALGORITHM_UPDATE = 'algorithm_update';
    case COMPETITOR_ACTION = 'competitor_action';
    case SEASONAL = 'seasonal';
    case MANUAL_ACTION = 'manual_action';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::CONTENT_UPDATE => 'Content Update',
            self::LINK_BUILDING => 'Link Building',
            self::TECHNICAL_FIX => 'Technical Fix',
            self::ALGORITHM_UPDATE => 'Algorithm Update',
            self::COMPETITOR_ACTION => 'Competitor Action',
            self::SEASONAL => 'Seasonal',
            self::MANUAL_ACTION => 'Manual Action',
            self::OTHER => 'Other',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::CONTENT_UPDATE => '✏️',
            self::LINK_BUILDING => '🔗',
            self::TECHNICAL_FIX => '🔧',
            self::ALGORITHM_UPDATE => '🤖',
            self::COMPETITOR_ACTION => '👀',
            self::SEASONAL => '📅',
            self::MANUAL_ACTION => '⚠️',
            self::OTHER => '📝',
        };
    }
}
```

---

## API Design

### Annotation CRUD

```yaml
# List annotations for a keyword
GET /api/keywords/{keywordId}/annotations
Query Params:
  - from: date (optional)
  - to: date (optional)
  - category: string (optional)
Response: {
    "data": [
        {
            "id": 1,
            "date": "2026-02-15",
            "title": "Updated meta title",
            "description": "Changed meta title to include target keyword",
            "category": "content_update",
            "category_label": "Content Update",
            "category_icon": "✏️",
            "created_at": "2026-02-15T10:30:00Z"
        }
    ],
    "meta": { "total": 1 }
}

# Create annotation
POST /api/keywords/{keywordId}/annotations
Body: {
    "date": "2026-02-15",
    "title": "Updated meta title",
    "description": "Changed meta title to include target keyword",
    "category": "content_update"
}
Response: {
    "id": 1,
    "keyword_id": 123,
    "date": "2026-02-15",
    "title": "Updated meta title",
    ...
}

# Get single annotation
GET /api/annotations/{id}
Response: { ... }

# Update annotation
PUT /api/annotations/{id}
Body: {
    "date": "2026-02-15",
    "title": "Updated title",
    "description": "Revised description",
    "category": "content_update"
}
Response: { ... }

# Delete annotation
DELETE /api/annotations/{id}
Response: { "success": true }
```

### Project-Level Annotations

```yaml
# Get all annotations for project (across all keywords)
GET /api/projects/{projectId}/annotations
Query Params:
  - from: date (optional)
  - to: date (optional)
  - category: string (optional)
  - keyword_id: int (optional)
Response: {
    "data": [
        {
            "id": 1,
            "keyword_id": 123,
            "keyword": { "id": 123, "keyword": "seo tools" },
            "date": "2026-02-15",
            "title": "Updated meta title",
            "category": "content_update",
            ...
        }
    ],
    "meta": { "total": 5 }
}
```

---

## Frontend Components

### AnnotationList Component

```typescript
interface Annotation {
  id: number;
  keywordId: number;
  date: string;
  title: string;
  description: string | null;
  category: string;
  categoryLabel: string;
  categoryIcon: string;
  createdAt: string;
}

interface AnnotationListProps {
  keywordId: number;
  annotations: Annotation[];
  onEdit: (annotation: Annotation) => void;
  onDelete: (id: number) => void;
}

// Usage in KeywordDetailPage
<AnnotationList 
  keywordId={keyword.id}
  annotations={annotations}
  onEdit={handleEdit}
  onDelete={handleDelete}
/>
```

### AnnotationForm Component

```typescript
interface AnnotationFormProps {
  keywordId: number;
  annotation?: Annotation; // undefined = create mode
  onSave: (data: CreateAnnotationDTO) => void;
  onCancel: () => void;
}

interface CreateAnnotationDTO {
  date: string;
  title: string;
  description?: string;
  category: string;
}

// Categories dropdown
const CATEGORIES = [
  { value: 'content_update', label: 'Content Update', icon: '✏️' },
  { value: 'link_building', label: 'Link Building', icon: '🔗' },
  { value: 'technical_fix', label: 'Technical Fix', icon: '🔧' },
  { value: 'algorithm_update', label: 'Algorithm Update', icon: '🤖' },
  { value: 'competitor_action', label: 'Competitor Action', icon: '👀' },
  { value: 'seasonal', label: 'Seasonal', icon: '📅' },
  { value: 'manual_action', label: 'Manual Action', icon: '⚠️' },
  { value: 'other', label: 'Other', icon: '📝' },
];
```

---

## Backend Implementation

### Model: Annotation.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Annotation extends Model
{
    protected $fillable = [
        'keyword_id',
        'project_id',
        'date',
        'title',
        'description',
        'category',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Relationships
    public function keyword()
    {
        return $this->belongsTo(Keyword::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Scopes
    public function scopeForDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessor
    public function getCategoryLabelAttribute(): string
    {
        return AnnotationCategory::tryFrom($this->category)?->label() ?? 'Other';
    }

    public function getCategoryIconAttribute(): string
    {
        return AnnotationCategory::tryFrom($this->category)?->icon() ?? '📝';
    }
}
```

### Controller: AnnotationController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\Annotation;
use App\Models\Keyword;
use App\Http\Requests\StoreAnnotationRequest;
use App\Http\Requests\UpdateAnnotationRequest;
use App\Http\Resources\AnnotationResource;

class AnnotationController extends Controller
{
    public function index(Keyword $keyword)
    {
        $this->authorize('view', $keyword);

        $annotations = $keyword->annotations()
            ->when(request('from'), fn($q, $from) => $q->where('date', '>=', $from))
            ->when(request('to'), fn($q, $to) => $q->where('date', '<=', $to))
            ->when(request('category'), fn($q, $cat) => $q->where('category', $cat))
            ->orderBy('date', 'desc')
            ->get();

        return AnnotationResource::collection($annotations);
    }

    public function store(Keyword $keyword, StoreAnnotationRequest $request)
    {
        $this->authorize('update', $keyword);

        $annotation = $keyword->annotations()->create([
            ...$request->validated(),
            'project_id' => $keyword->project_id,
        ]);

        return new AnnotationResource($annotation);
    }

    public function show(Annotation $annotation)
    {
        $this->authorize('view', $annotation);

        return new AnnotationResource($annotation);
    }

    public function update(Annotation $annotation, UpdateAnnotationRequest $request)
    {
        $this->authorize('update', $annotation);

        $annotation->update($request->validated());

        return new AnnotationResource($annotation);
    }

    public function destroy(Annotation $annotation)
    {
        $this->authorize('delete', $annotation);

        $annotation->delete();

        return response()->json(['success' => true]);
    }
}
```

---

## Security Considerations

### Authorization

```php
class AnnotationPolicy
{
    public function view(User $user, Annotation $annotation): bool
    {
        return $user->id === $annotation->keyword->project->user_id;
    }

    public function create(User $user, Keyword $keyword): bool
    {
        return $user->id === $keyword->project->user_id;
    }

    public function update(User $user, Annotation $annotation): bool
    {
        return $user->id === $annotation->keyword->project->user_id;
    }

    public function delete(User $user, Annotation $annotation): bool
    {
        return $user->id === $annotation->keyword->project->user_id;
    }
}
```

### Input Validation

```php
class StoreAnnotationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'string', 'in:content_update,link_building,technical_fix,algorithm_update,competitor_action,seasonal,manual_action,other'],
        ];
    }
}
```

---

## Performance Considerations

### Database Indexes

- `idx_annotations_keyword_id` for keyword-scoped queries
- `idx_annotations_project_id` for project-wide queries
- `idx_annotations_date` for date range filtering
- `idx_annotations_category` for category filtering

### Query Optimization

- Eager load keyword relationship for project-wide views
- Pagination for large annotation lists (20 per page default)

---

## Free Tools Compliance

✅ **No paid services required**

All functionality uses existing stack:
- Laravel Eloquent ORM (free)
- MySQL database (free)
- Next.js + React (free)

---

## Testing Requirements

### Unit Tests
- Annotation model relationships
- Category enum methods
- AnnotationService CRUD

### Feature Tests
- Create/update/delete annotations via API
- Authorization checks
- Date range filtering
- Category filtering

### Integration Tests
- Annotations included in keyword detail response
- Project-wide annotation list

---

## MVP Scope

### Included
- ✅ Add/edit/delete annotations
- ✅ Keyword detail page annotation list
- ✅ 8 default categories
- ✅ Project-level annotation view
- ✅ Date range filtering
- ✅ Category filtering

### Phase 2 (Future)
- ⏳ Chart integration (markers on ranking history)
- ⏳ Bulk annotations (multiple keywords)
- ⏳ Annotation export in PDF reports
- ⏳ Custom categories (user-defined)

---

## Comments
_Agent communication log_

### [2026-02-20 17:05] Agent: Architect
Created technical specification for FEAT-009. Risk level confirmed as Low - simple CRUD with no external dependencies. Implementation is straightforward with clear data model.

### [2026-02-20 17:05] Agent: Architect
Key design decisions:
1. `project_id` denormalized on annotations for efficient project-wide queries
2. Category enum at application level (not database enum for flexibility)
3. Annotations cascade delete with keywords
4. Date validation: must be today or past (no future annotations)

### [2026-02-20 17:05] Agent: Architect
Ready for SpecSystemAnalyst to break into tasks.
