# SPEC-010: Visibility Score - Technical Specification

## STATUS
`To Break Into Tasks`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: [FEAT-010](../FEATURES/FEAT-010.md)
- Tasks: (To be created by SpecSystemAnalyst)
- Uses Data From: SPEC-001 (rankings), SPEC-007 (SERP features)

---

## Overview

Calculate and track a composite "Visibility Score" (0-100) to represent SEO health, making it easier for users to assess performance, track trends, and communicate results. The calculation combines keyword rankings, SERP feature presence, and search volume into a weighted formula.

---

## Architecture

### System Design

```
┌──────────────────────────────────────┐
│          Frontend (Next.js)          │
├──────────────────────────────────────┤
│    Components:                      │
│    ├── VisibilityTrendChart          │
│    ├── ScoreBreakdownCard            │
│    └── AlertThresholdForm            │
├──────────────────────────────────────┤
│    Integration Points:               │
│    ├── ProjectDashboard              │
│    ├── PDFReportGenerator            │
│    └── AllProjectsComparison         │
└──────────────────────────────────────┘
          │                                
          ▼                                
┌──────────────────────────────────────┐
│         Backend API (Laravel)        │
├──────────────────────────────────────┤
│    Controllers:                     │
│    ├── VisibilityController          │
│    └── ProjectDashboardController    │
├──────────────────────────────────────┤
│    Resources:                       │
│    └── ScoreResource (JSON output)  │
├──────────────────────────────────────┤
│    Services:                        │
│    └── VisibilityScoreService       │
└──────────────────────────────────────┘
          │                                
          ▼                                
┌──────────────────────────────────────┐
│            Database (MySQL)          │
├──────────────────────────────────────┤
│    Table: visibility_scores          │
│    Columns:                          │
│    ├── score (int)                   │
│    ├── position_component (float)    │
│    ├── serp_component (float)        │
│    ├── volume_component (float)      │
│    └── calculated_at (datetime)      │
└──────────────────────────────────────┘
```

---

## Database Schema

### New Table: visibility_scores

```sql
CREATE TABLE visibility_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    score FLOAT NOT NULL,
    position_component FLOAT NOT NULL,
    serp_component FLOAT NOT NULL,
    volume_component FLOAT NOT NULL,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_visibility_scores_project_id (project_id),
    INDEX idx_visibility_scores_calculated_at (calculated_at)
);
```

### Daily Score Calculation Example

```sql
-- Storing daily scores simplifies trend analysis
INSERT INTO visibility_scores (project_id, score, position_component, serp_component, volume_component, calculated_at)
VALUES (1, 67.5, 40.0, 17.5, 10.0, NOW());
```

---

## API Design

### Project-Level Visibility

```yaml
# Get current visibility score for a project
GET /api/projects/{projectId}/visibility
Response: {
    "score": 67,
    "trend": { "30d": 12, "90d": 17 },
    "breakdown": {
        "positions": 60,
        "serp_features": 25,
        "volume_distribution": 15
    },
    "last_updated": "2026-02-20T00:00:00Z"
}

# Visibility trend chart (30d)
GET /api/projects/{projectId}/visibility/trend
Response: {
    "data": [
        { "date": "2026-02-01", "score": 60 },
        { "date": "2026-02-05", "score": 62 },
        { "date": "2026-02-19", "score": 67 }
    ]
}

# Compare visibility scores (all projects)
GET /api/visibility/compare
Response: {
    "projects": [
        { "id": 1, "name": "Client A", "score": 67 },
        { "id": 2, "name": "Client B", "score": 54 }
    ]
}
```

---

## Formula Design

### Weighted Formula

```
Visibility Score = WeightedSum(
  Position Score × 0.50,
  SERP Feature Score × 0.25,
  Volume Distribution Score × 0.25
)
```

#### Position Score
- Position 1-3: 100 points each
- Position 4-10: Linear scale from 99 to 80 points
- Position 11-20: Linear scale from 79 to 50 points
- Position 21-50: Linear scale from 49 to 20 points
- Position 51-100: Linear scale from 19 to 1 point
- Not in top 100: 0 points

#### SERP Feature Score
- Featured snippet: +20 bonus
- Local pack: +15 bonus
- Reviews: +5 bonus
- Sitelinks: +10 bonus
- Total bonus normalized for the number of keywords with features.

#### Volume Distribution Score
- % of tracked keywords in top 10.
- Rewards breadth over depth.

---

## Frontend Components

### VisibilityScoreCard
```typescript
interface VisibilityScoreCardProps {
    score: number;
    trend: { "30d": number; "90d": number };
    breakdown: { positions: number; serp_features: number; volume_distribution: number };
    lastUpdated: string;
}

<View>
    <Text>Score: {props.score}</Text>
    <Text>30d Trend: {props.trend["30d"]}</Text>
    <Text>Rankings: {props.breakdown.positions}</Text>
    <Text>Features: {props.breakdown.serp_features}</Text>
    <Text>Volume: {props.breakdown.volume_distribution}</Text>
</View>;
```

### VisibilityTrendChart
```typescript
interface VisibilityTrendChartProps {
    data: Array<{ date: string; score: number }>;
}

<LineChart data={props.data} />;
```

---

## Security and Transparency

1. **Transparency**: Users can see the full score breakdown.
2. **Data Privacy**: Visibility score is calculated on user data; no external data.
3. **Algorithm Documentation**: Users informed of weightings to avoid confusion.

---

## Testing Requirements

### Unit Tests
- Calculate position score from rankings.
- Calculate SERP feature score.
- Weighted formula correctness.

### Integration Tests
- Visibility score endpoint responses.
- Authorization for score access.
- API trend chart generation.

### Frontend Tests
- Visibility trend chart rendering.
- Score card handling edge cases.

---

## Rollout Plan

1. Implement database schema changes (visibility_scores table).
2. Develop score calculation logic in `VisibilityScoreService`.
3. Expose API endpoints for score data and trends.
4. Create frontend components for score display and trends.
5. Update dashboards to include visibility score prominently.
6. Gradual rollout with feature flag for user feedback.

---

## Comments
_Agent communication log_

### [2026-02-20 17:15] Agent: Architect
Created technical specification SPEC-010 for Visibility Score (FEAT-010). Feature confirmed as Low risk - internal metric calculation with no external dependencies.

### [2026-02-20 17:15] Agent: Architect
Key design decisions:
- Weighted formula emphasizes rankings (50%), SERP feature presence (25%), volume breadth (25%).
- Separate database table for daily scores simplifies trend analysis.
- Full user transparency with breakdown explanations.
Ready for SpecSystemAnalyst to break into tasks.
