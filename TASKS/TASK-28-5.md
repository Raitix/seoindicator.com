# TASK-28-5: IndicatorCard Component

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
[SPEC-028](../SPECIFICATIONS/SPEC-028.md) - Modern UI/UX + Indicator Catalog

## Dependencies
- TASK-28-4 (IndicatorIcon Wrapper)
- TASK-28-3 (shadcn/ui Components - badge, tooltip)

## Description
Create the IndicatorCard component that displays individual SEO indicators with icon, title, value, status, and hover interactions.

## Acceptance Criteria
- [ ] Card displays icon, title, value, and description
- [ ] Status indicator (good/warning/critical) with color coding
- [ ] Hover animations (scale, subtle glow effect)
- [ ] Click navigates to relevant dashboard section
- [ ] Responsive design (works on mobile and desktop)
- [ ] Skeleton loading state supported
- [ ] Badge component for status display
- [ ] Tooltip on hover for additional info
- [ ] TypeScript types properly defined

## Implementation Notes
```typescript
interface IndicatorCardProps {
  id: string;
  icon: LucideIconName | React.ReactNode;
  title: string;
  value: string | number;
  description: string;
  status: 'good' | 'warning' | 'critical' | 'neutral';
  href: string;
  tooltip?: string;
  isLoading?: boolean;
}
```

### Indicator Types (Initial Set)
| Indicator | Icon | Description | Link |
|-----------|------|-------------|------|
| Keyword Rankings | TrendingUp | Track SERP positions | /dashboard |
| Visibility Score | Eye | Overall SEO visibility | /dashboard |
| Competitor Analysis | Users | Compare with competitors | /comparison |
| Technical Health | Wrench | Site audit score | /tools |
| Backlink Profile | Link | Link analysis | /dashboard |
| Content Opportunities | FileText | Keyword gaps | /dashboard |
| SERP Features | Star | Featured snippets, etc. | /dashboard |
| Page Speed | Zap | Performance metrics | /tools |

## Files to Create
- `frontend/components/indicators/IndicatorCard.tsx`

## Effort Estimate
`Medium` (2-4 hours)

## Comments
_Agent log_

---
*Created by SpecSystemAnalyst from SPEC-028*
