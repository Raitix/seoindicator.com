# FEAT-010: Visibility Score

## STATUS
`To Break Into Tasks`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Calculate and track a composite "Visibility Score" that represents overall SEO health in a single number. This metric combines keyword rankings, SERP features, and search volume into an easy-to-understand score (0-100). Users can quickly assess SEO performance and track improvements over time without analyzing individual keyword positions.

## User Story
As an SEO professional, I want a single visibility score that summarizes my overall SEO performance so that I can quickly understand if my SEO is improving or declining, and communicate results to stakeholders who don't want to analyze individual keyword data.

## Business Impact
### Revenue
- **Executive-friendly metric**: CMOs and executives want one number - makes the tool valuable at higher organizational levels
- **Client reporting staple**: Agencies use visibility scores in client presentations as headline metrics
- **Differentiator positioning**: Unique visibility algorithm can differentiate from competitors

### User Behavior
- **Quick health check**: Users glance at score to know if everything is okay (like a credit score)
- **Trend awareness**: Score trend indicates overall direction without diving into details
- **Goal setting**: Users can set "increase visibility score by X points" as a measurable goal

### Retention
- **Benchmark value**: Score provides baseline to measure all future efforts against
- **Addictive metric**: Users check score regularly, creating habit
- **Success validation**: Score increases validate SEO efforts, encouraging continued use

### Legal & Compliance
- **No concerns**: Score is calculated from user's own ranking data
- **Algorithm transparency**: Document how score is calculated so users understand it

### Complexity & Cost
- **Calculation logic**: Develop weighted formula considering position, volume, SERP features
- **Historical recalculation**: Calculate historical scores for trend charts
- **No external dependencies**: Pure calculation from existing data
- **Low implementation risk**: Standard scoring algorithm development

## Acceptance Criteria
- [ ] System calculates visibility score (0-100) for each project
- [ ] Score appears prominently on project dashboard
- [ ] Score trend chart shows historical visibility over time (30d, 90d, 1y)
- [ ] Score change indicator (↑ 5 points this week)
- [ ] Score breakdown shows contributing factors (rankings 60%, SERP features 25%, volume 15%)
- [ ] Score comparison between projects (for "All Projects" view)
- [ ] Score comparison vs competitors (competitor visibility scores)
- [ ] Alerts for significant score changes (threshold configurable)
- [ ] Score included in PDF reports as headline metric
- [ ] Score visible in API responses
- [ ] Explanation of score calculation available to users

## Related
- Specification: [SPEC-010](../SPECIFICATIONS/SPEC-010.md)
- Tasks: (To be created by Developer)
- Uses: FEAT-001 (rankings), FEAT-007 (SERP features)

## Notes
### Visibility Score Formula
```
Visibility Score = WeightedSum(
  Position Score × 0.50,
  SERP Feature Score × 0.25,
  Volume Distribution Score × 0.25
)

Position Score:
- Position 1-3: 100 points each
- Position 4-10: 80-99 points (linear decrease)
- Position 11-20: 50-79 points (linear decrease)
- Position 21-50: 20-49 points (linear decrease)
- Position 51-100: 1-19 points (linear decrease)
- Not in top 100: 0 points
→ Average across all keywords, weighted by search volume

SERP Feature Score:
- Featured snippet: +20 bonus
- Local pack: +15 bonus
- Sitelinks: +10 bonus
- Reviews/ratings: +5 bonus
→ Sum of bonuses / keywords with features

Volume Distribution Score:
- Percentage of tracked keywords where user ranks in top 10
- Rewards breadth of visibility over depth
```

### Score Interpretation Guide
| Score Range | Interpretation | Action |
|-------------|----------------|--------|
| 80-100 | Excellent | Maintain, watch for competitors |
| 60-79 | Good | Optimize underperforming keywords |
| 40-59 | Average | Significant improvement opportunities |
| 20-39 | Below Average | Strategic overhaul needed |
| 0-19 | Poor | Fundamental SEO issues |

### Database Schema
```sql
-- Store calculated scores for historical tracking
visibility_scores: id, project_id, score, position_component, serp_component, volume_component, calculated_at

-- Calculate daily and store for trend charts
```

### UI Mockup
```
┌─────────────────────────────────────────┐
│  Project: Client A                      │
├─────────────────────────────────────────┤
│                                         │
│   VISIBILITY SCORE                      │
│   ┌─────────────────────────────────┐   │
│   │          67                     │   │
│   │        ↑ +5 this week           │   │
│   │                                  │   │
│   │   [Trend Chart: 30d ▼]          │   │
│   │   ~~~~~~~~~~~~~~~~~~~~~~~~       │   │
│   └─────────────────────────────────┘   │
│                                         │
│   Score Breakdown:                      │
│   • Rankings: 60/100 (60% weight)      │
│   • SERP Features: 75/100 (25% weight) │
│   • Volume Dist: 72/100 (15% weight)   │
│                                         │
└─────────────────────────────────────────┘
```

### MVP Scope
- Basic visibility score calculation
- Single project view
- 30-day trend chart
- Score change indicator
- No competitor score comparison (Phase 2)
- No configurable weights (Phase 2)

---

## Comments
_Agent communication log_

### [2026-02-19 19:29] Agent: Innovator
Created as tenth feature. Visibility Score is the "credit score of SEO" - a single metric that executives and clients understand immediately. It transforms complex ranking data into a simple health indicator that drives engagement and makes the tool valuable at all organizational levels.

### [2026-02-19 19:29] Agent: Innovator
Priority Medium because while not essential for core functionality, it significantly enhances user experience and client reporting. Risk Low as it's a pure calculation from existing data with no external dependencies.

### [2026-02-20 17:15] Agent: Architect
Created technical specification SPEC-010. Risk level confirmed as Low - internal scoring calculation based on rankings, SERP features, and volume breadth.

Key design decisions:
- Weighted formula prioritizes rankings (50%), SERP feature presence (25%), and breadth (25%).
- Separate database table for storing daily scores simplifies trend analysis.
- Transparent calculation breakdown to build user trust.
Ready for SpecSystemAnalyst task breakdown.
