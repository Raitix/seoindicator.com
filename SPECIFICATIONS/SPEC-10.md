# SPEC-010: Visibility Score - Technical Specification

## STATUS
`Done`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: [FEAT-010](../FEATURES/FEAT-010.md)
- Tasks: (To be created by SpecSystemAnalyst)
- Uses: SPEC-001 (rankings), SPEC-007 (SERP features)

---

## Overview

A composite "Visibility Score" (0-100) that represents overall SEO health in a single number. Combines keyword rankings, SERP features, and search volume distribution into an easy-to-understand metric for quick performance assessment and stakeholder reporting.

---

## Architecture

### System Design

```
┌─────────────────────────────────────────────────────────────────┐
│                        Frontend (Next.js)                        │
├─────────────────────────────────────────────────────────────────┤
│  Components:                                                    │
│  ├── VisibilityScoreCard (prominent dashboard display)          │
│  ├── VisibilityTrendChart (historical chart - 30d/90d/1y)       │
│  ├── VisibilityBreakdown (contributing factors breakdown)       │
│  └── VisibilityComparison (multi-project comparison - Phase 2)  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Backend API (Laravel)                        │
├─────────────────────────────────────────────────────────────────┤
│  Controllers:                                                   │
│  └── VisibilityController (score + trends)                      │
├─────────────────────────────────────────────────────────────────┤
│  Services:                                                      │
│  ├── VisibilityScoreCalculator (core algorithm)                 │
│  └── VisibilityTrendService (historical data)                   │
├─────────────────────────────────────────────────────────────────┤
│  Jobs:                                                          │
│  └── CalculateVisibilityScore (scheduled daily)                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       Database (MySQL)                           │
├─────────────────────────────────────────────────────────────────┤
│  visibility_scores (NEW)                                        │
│  └── Stores daily calculated scores for trends                  │
└─────────────────────────────────────────────────────────────────┘
```

### Score Calculation Flow

```
Daily Scheduled Job
        │
        ▼
┌─────────────────────────────────┐
│ For each active project:        │
│                                 │
│ 1. Fetch all keywords with:     │
│    - Current position           │
│    - Search volume              │
│    - SERP features              │
│                                 │
│ 2. Calculate Position Score     │
│    (volume-weighted average)    │
│                                 │
│ 3. Calculate SERP Feature Score │
│    (bonus points)               │
│                                 │
│ 4. Calculate Volume Dist. Score │
│    (top 10 percentage)          │
│                                 │
│ 5. Combine with weights:        │
│    50% + 25% + 25%              │
│                                 │
│ 6. Store result with timestamp  │
└─────────────────────────────────┘
```

---

## Database Schema

### New Table: visibility_scores

```sql
CREATE TABLE visibility_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    score TINYINT UNSIGNED NOT NULL, -- 0-100
    position_component DECIMAL(5,2) NOT NULL,
    serp_component DECIMAL(5,2) NOT NULL,
    volume_component DECIMAL(5,2) NOT NULL,
    keywords_count INT UNSIGNED NOT NULL DEFAULT 0,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_visibility_project_id (project_id),
    INDEX idx_visibility_calculated_at (calculated_at),
    UNIQUE KEY unique_project_date (project_id, DATE(calculated_at))
);
```

---

## Score Algorithm

### Position Score Calculation

```php
class PositionScoreCalculator
{
    /**
     * Calculate position score for a keyword.
     * Returns 0-100 based on ranking position.
     */
    public function calculate(int $position): int
    {
        return match(true) {
            $position >= 1 && $position <= 3 => 100,
            $position >= 4 && $position <= 10 => (int) (100 - ($position - 3) * 3.33),
            $position >= 11 && $position <= 20 => (int) (80 - ($position - 10) * 3),
            $position >= 21 && $position <= 50 => (int) (50 - ($position - 20) * 1),
            $position >= 51 && $position <= 100 => (int) (20 - ($position - 50) * 0.38),
            default => 0, // Not in top 100
        };
    }

    /**
     * Calculate volume-weighted average position score.
     */
    public function calculateWeightedAverage(Collection $keywords): float
    {
        $weightedSum = 0;
        $totalVolume = 0;

        foreach ($keywords as $keyword) {
            $positionScore = $this->calculate($keyword->current_position);
            $volume = $keyword->search_volume ?? 1; // Default volume if not set
            
            $weightedSum += $positionScore * $volume;
            $totalVolume += $volume;
        }

        return $totalVolume > 0 ? $weightedSum / $totalVolume : 0;
    }
}
```

### SERP Feature Score Calculation

```php
class SerpFeatureScoreCalculator
{
    private const BONUS_POINTS = [
        'featured_snippet' => 20,
        'local_pack' => 15,
        'sitelinks' => 10,
        'reviews' => 5,
        'image_pack' => 5,
        'video' => 5,
        'people_also_ask' => 3,
    ];

    /**
     * Calculate SERP feature bonus score.
     * Returns average bonus per keyword with features.
     */
    public function calculate(Collection $keywords): float
    {
        $totalBonus = 0;
        $keywordsWithFeatures = 0;

        foreach ($keywords as $keyword) {
            $keywordBonus = 0;
            $features = $keyword->serp_features ?? [];

            foreach ($features as $feature) {
                $keywordBonus += self::BONUS_POINTS[$feature] ?? 0;
            }

            if ($keywordBonus > 0) {
                $totalBonus += min($keywordBonus, 50); // Cap at 50 per keyword
                $keywordsWithFeatures++;
            }
        }

        // Normalize to 0-100 scale
        $avgBonus = $keywords->count() > 0 ? $totalBonus / $keywords->count() : 0;
        return min(100, $avgBonus * 2); // Scale to 0-100
    }
}
```

### Volume Distribution Score Calculation

```php
class VolumeDistributionScoreCalculator
{
    /**
     * Calculate volume distribution score.
     * Percentage of search volume where user ranks in top 10.
     */
    public function calculate(Collection $keywords): float
    {
        $topTenVolume = 0;
        $totalVolume = 0;

        foreach ($keywords as $keyword) {
            $volume = $keyword->search_volume ?? 1;
            $totalVolume += $volume;

            if ($keyword->current_position <= 10) {
                $topTenVolume += $volume;
            }
        }

        return $totalVolume > 0 ? ($topTenVolume / $totalVolume) * 100 : 0;
    }
}
```

### Combined Visibility Score

```php
class VisibilityScoreCalculator
{
    private const WEIGHTS = [
        'position' => 0.50,
        'serp' => 0.25,
        'volume' => 0.25,
    ];

    public function __construct(
        private PositionScoreCalculator $positionCalculator,
        private SerpFeatureScoreCalculator $serpCalculator,
        private VolumeDistributionScoreCalculator $volumeCalculator,
    ) {}

    /**
     * Calculate visibility score for a project.
     */
    public function calculate(Project $project): VisibilityScoreResult
    {
        $keywords = $project->keywords()
            ->withCurrentPosition()
            ->withSearchVolume()
            ->withSerpFeatures()
            ->get();

        if ($keywords->isEmpty()) {
            return VisibilityScoreResult::empty();
        }

        $positionComponent = $this->positionCalculator->calculateWeightedAverage($keywords);
        $serpComponent = $this->serpCalculator->calculate($keywords);
        $volumeComponent = $this->volumeCalculator->calculate($keywords);

        $score = (
            $positionComponent * self::WEIGHTS['position'] +
            $serpComponent * self::WEIGHTS['serp'] +
            $volumeComponent * self::WEIGHTS['volume']
        );

        return new VisibilityScoreResult(
            score: (int) round($score),
            positionComponent: round($positionComponent, 2),
            serpComponent: round($serpComponent, 2),
            volumeComponent: round($volumeComponent, 2),
            keywordsCount: $keywords->count(),
        );
    }
}
```

---

## API Design

### Visibility Score Endpoints

```yaml
# Get current visibility score for project
GET /api/projects/{projectId}/visibility
Response: {
    "score": 67,
    "change": {
        "value": 5,
        "direction": "up",
        "period": "7d"
    },
    "breakdown": {
        "position": {
            "value": 60.5,
            "weight": 0.50,
            "label": "Rankings"
        },
        "serp": {
            "value": 75.0,
            "weight": 0.25,
            "label": "SERP Features"
        },
        "volume": {
            "value": 72.3,
            "weight": 0.25,
            "label": "Volume Distribution"
        }
    },
    "keywords_count": 150,
    "interpretation": "Good - Optimize underperforming keywords",
    "calculated_at": "2026-02-20T00:00:00Z"
}

# Get visibility trend
GET /api/projects/{projectId}/visibility/trend
Query Params:
  - period: 30d|90d|1y (default: 30d)
Response: {
    "data": [
        { "date": "2026-01-21", "score": 62 },
        { "date": "2026-01-22", "score": 63 },
        ...
        { "date": "2026-02-20", "score": 67 }
    ],
    "period": "30d",
    "start_score": 62,
    "end_score": 67,
    "change": 5,
    "trend": "up"
}

# Get all projects visibility (for dashboard overview)
GET /api/visibility/overview
Response: {
    "data": [
        {
            "project_id": 1,
            "project_name": "Client A",
            "score": 67,
            "change": 5
        },
        {
            "project_id": 2,
            "project_name": "Client B",
            "score": 45,
            "change": -2
        }
    ]
}
```

---

## Frontend Components

### VisibilityScoreCard Component

```typescript
interface VisibilityScoreCardProps {
  score: number;
  change: { value: number; direction: 'up' | 'down' | 'stable' };
  breakdown: {
    position: { value: number; weight: number };
    serp: { value: number; weight: number };
    volume: { value: number; weight: number };
  };
  interpretation: string;
}

// Prominent dashboard card showing the score
<div className="visibility-score-card">
  <h3>Visibility Score</h3>
  <div className="score-value">{score}</div>
  <div className="score-change">
    {change.direction === 'up' ? '↑' : '↓'} {change.value} this week
  </div>
  <div className="interpretation">{interpretation}</div>
</div>
```

### VisibilityTrendChart Component

```typescript
interface VisibilityTrendChartProps {
  data: Array<{ date: string; score: number }>;
  period: '30d' | '90d' | '1y';
  onPeriodChange: (period: string) => void;
}

// Line chart showing score history
// Period selector: 30d | 90d | 1y
```

### VisibilityBreakdown Component

```typescript
interface VisibilityBreakdownProps {
  position: { value: number; weight: number };
  serp: { value: number; weight: number };
  volume: { value: number; weight: number };
}

// Shows breakdown with progress bars:
// Rankings:     ████████░░ 60.5 (50% weight)
// SERP Feats:   ████████░░ 75.0 (25% weight)
// Volume Dist:  ███████░░░ 72.3 (25% weight)
```

---

## Backend Implementation

### Model: VisibilityScore.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisibilityScore extends Model
{
    protected $fillable = [
        'project_id',
        'score',
        'position_component',
        'serp_component',
        'volume_component',
        'keywords_count',
        'calculated_at',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeForPeriod($query, string $period)
    {
        $days = match($period) {
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };

        return $query->where('calculated_at', '>=', now()->subDays($days));
    }
}
```

### Scheduled Job: CalculateVisibilityScore

```php
<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\VisibilityScoreCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalculateVisibilityScore implements ShouldQueue
{
    use Queueable;

    public function handle(VisibilityScoreCalculator $calculator): void
    {
        $projects = Project::active()
            ->with(['keywords.currentPosition', 'keywords.serpFeatures'])
            ->get();

        foreach ($projects as $project) {
            $result = $calculator->calculate($project);

            VisibilityScore::create([
                'project_id' => $project->id,
                'score' => $result->score,
                'position_component' => $result->positionComponent,
                'serp_component' => $result->serpComponent,
                'volume_component' => $result->volumeComponent,
                'keywords_count' => $result->keywordsCount,
                'calculated_at' => now(),
            ]);
        }
    }
}
```

### Console Command

```php
// app/Console/Commands/CalculateVisibilityScores.php
php artisan visibility:calculate

// Schedule in Kernel.php
$schedule->command('visibility:calculate')->daily();
```

---

## Score Interpretation

```php
class ScoreInterpreter
{
    public static function interpret(int $score): string
    {
        return match(true) {
            $score >= 80 => 'Excellent - Maintain, watch for competitors',
            $score >= 60 => 'Good - Optimize underperforming keywords',
            $score >= 40 => 'Average - Significant improvement opportunities',
            $score >= 20 => 'Below Average - Strategic overhaul needed',
            default => 'Poor - Fundamental SEO issues',
        };
    }

    public static function getColor(int $score): string
    {
        return match(true) {
            $score >= 80 => 'green',
            $score >= 60 => 'blue',
            $score >= 40 => 'yellow',
            $score >= 20 => 'orange',
            default => 'red',
        };
    }
}
```

---

## Security Considerations

### Authorization

```php
class VisibilityController extends Controller
{
    public function show(Project $project)
    {
        $this->authorize('view', $project);
        // ...
    }
}
```

### No Sensitive Data

Visibility score is derived from user's own data - no privacy concerns.

---

## Performance Considerations

### Caching

```php
// Cache current score for 1 hour (recalculated daily anyway)
Cache::remember("visibility.{$projectId}", 3600, function() use ($projectId) {
    return VisibilityScore::where('project_id', $projectId)
        ->latest('calculated_at')
        ->first();
});
```

### Query Optimization

- Index on `(project_id, calculated_at)` for trend queries
- Batch calculation via queued job to avoid blocking
- Eager load keyword relationships

---

## Free Tools Compliance

✅ **No paid services required**

All calculations use existing data:
- Laravel queue system (free)
- MySQL for storage (free)
- No external APIs needed

---

## Testing Requirements

### Unit Tests
- PositionScoreCalculator with various positions
- SerpFeatureScoreCalculator with feature combinations
- VolumeDistributionScoreCalculator
- Combined visibility score calculation
- ScoreInterpreter

### Feature Tests
- API endpoint returns correct score
- Trend data returns correct history
- Authorization checks
- Empty project handling (score = 0)

---

## MVP Scope

### Included
- ✅ Basic visibility score calculation
- ✅ Daily scheduled calculation
- ✅ Score display on project dashboard
- ✅ 30-day trend chart
- ✅ Score change indicator
- ✅ Score breakdown

### Phase 2 (Future)
- ⏳ Competitor visibility comparison
- ⏳ Configurable weights per project
- ⏳ Alerts for significant changes
- ⏳ 90-day and 1-year trends
- ⏳ Multi-project comparison view

---

## Comments
_Agent communication log_

### [2026-02-20 17:10] Agent: Architect
Created technical specification for FEAT-010. Risk level confirmed as Low - pure calculation from existing data, no external dependencies.

### [2026-02-20 17:10] Agent: Architect
Key design decisions:
1. Daily scheduled calculation (not real-time) for performance
2. Volume-weighted position scoring (prioritizes high-volume keywords)
3. SERP feature bonuses capped at 50 per keyword to prevent skewing
4. Unique constraint on (project_id, date) prevents duplicate calculations
5. Score interpretation provides actionable guidance

### [2026-02-20 17:10] Agent: Architect
Ready for SpecSystemAnalyst to break into tasks.

### [2026-02-28 22:48] DocSystemAnalyst: All Tasks Complete ✅
- **TASK-10-1:** Visibility Scores Table - Done
- **TASK-10-2:** Position Score Algorithm - Done
- **TASK-10-3:** SERP Feature Score Calculator - Done
- **Status:** Specification marked as Done
- **Documentation:** All components documented in DOCUMENTATION.md
