# TASK-33-9: Create ScoreOverview Component

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Create the ScoreOverview component that displays a large circular score indicator with color coding and status text. This is the hero visual for public audit pages.

## Acceptance Criteria
- [ ] Displays circular progress indicator (0-100)
- [ ] Score color-coded by range (Green/Blue/Yellow/Orange/Red)
- [ ] Shows status text (Excellent/Good/Needs Work/Poor/Critical Issues)
- [ ] Shows site URL and audit date
- [ ] Includes "Get your free audit" CTA button
- [ ] Animated progress fill (optional, performance-conscious)
- [ ] Responsive sizing
- [ ] Accessible: score read by screen readers
- [ ] Unit tests for all score ranges and states

## Dependencies
- None (standalone UI component)

## Effort Estimate
**2 hours** - Visual component with animation

## Technical Notes
- Use CSS conic-gradient for circular progress
- Or use SVG circle with stroke-dashoffset
- Color mapping matches badge design
- Use shadcn/ui Card component as container

## Score Color Mapping
| Score Range | Color | Hex | Status |
|-------------|-------|-----|--------|
| 90-100 | Green | #22c55e | Excellent |
| 70-89 | Blue | #3b82f6 | Good |
| 50-69 | Yellow | #eab308 | Needs Work |
| 30-49 | Orange | #f97316 | Poor |
| 0-29 | Red | #ef4444 | Critical Issues |

## Files to Create
- `frontend/components/audit/ScoreOverview.tsx`
- `frontend/__tests__/components/audit/ScoreOverview.test.tsx`
