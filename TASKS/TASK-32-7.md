# TASK-32-7: ProsConsCard Component

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Low`

## Specification Reference
**SPEC-32**: Competitor Comparison Landing Pages (Programmatic SEO)

## Description
Create a pros/cons card component that displays balanced, honest assessment of each tool's strengths and weaknesses.

## Deliverables

### 1. Component File (`frontend/components/compare/ProsConsCard.tsx`)

```typescript
import { ProsConsCardProps } from '@/lib/compare/types';

export function ProsConsCard({ 
  tool, 
  pros, 
  cons, 
  overallRating 
}: ProsConsCardProps) {
  return (
    <Card className="pros-cons-card">
      <CardHeader>
        <h3>{tool}</h3>
        {overallRating && <Rating value={overallRating} />}
      </CardHeader>
      <CardContent>
        <div className="pros">
          <h4>Pros</h4>
          <ul>
            {pros.map((pro, i) => (
              <li key={i}>
                <CheckIcon />
                <span>{pro}</span>
              </li>
            ))}
          </ul>
        </div>
        <div className="cons">
          <h4>Cons</h4>
          <ul>
            {cons.map((con, i) => (
              <li key={i}>
                <XIcon />
                <span>{con}</span>
              </li>
            ))}
          </ul>
        </div>
      </CardContent>
    </Card>
  );
}
```

### 2. Features

| Feature | Description |
|---------|-------------|
| Side-by-side layout | Pros and cons in clear sections |
| Icon indicators | Check for pros, X for cons |
| Balanced assessment | Honest representation of both tools |
| Overall rating | Optional rating display (1-5) |
| Color coding | Green for pros, red/gray for cons |

### 3. Styling

```css
/* Visual hierarchy */
.pros-cons-card {
  /* Card styling */
}

.pros {
  /* Green accent */
}

.cons {
  /* Gray/red accent */
}
```

## Acceptance Criteria
- [ ] Tool name displayed in header
- [ ] Pros listed with green check icons
- [ ] Cons listed with X icons (subtle color)
- [ ] Overall rating displayed when provided
- [ ] Mobile responsive (stacked or scrollable)
- [ ] Balanced presentation (no bias in layout)
- [ ] Accessible (proper heading structure)

## Dependencies
- TASK-32-1 (Types)

## Effort Estimate
`Small` (2-3 hours)

## Files to Create/Modify
| File | Action |
|------|--------|
| `frontend/components/compare/ProsConsCard.tsx` | Create |

## Technical Notes
- Use existing Card component from shadcn/ui
- Use Lucide icons (Check, X)
- Keep styling balanced - no visual bias toward SEOIndicator
- Consider adding expand/collapse for long lists

---

## Comments
### [2026-03-05 14:55 GMT+2] Agent: SpecSystemAnalyst
Task created as part of SPEC-32 breakdown. Builds trust through honest comparison.

### [2026-03-05 21:19 GMT+2] Agent: ProjectManager
**Priority Assessment:** MEDIUM (confirmed)
- UI component for comparison landing pages - presentation layer, not foundation infrastructure
- Enhances user experience through balanced pros/cons display
- Phase 2 Components task - builds on foundation (types complete)
- No downstream tasks blocked by this component

**Risk Assessment:** LOW (confirmed)
- Frontend-only React/TypeScript component
- No external APIs, no database changes
- Uses existing shadcn/ui Card component
- Well-defined props interface from TASK-32-1

**Dependency Status:**
- TASK-32-1 (Types): Done ✅ - `ProsConsCardProps` interface available

**TDD Ready:** PreTester can design tests immediately using the ProsConsCardProps interface:
- Component renders tool name, pros list, cons list
- Rating display (optional)
- Mobile responsive
- Accessibility (heading structure)
- Icon indicators (Check for pros, X for cons)

**Key Features:**
- Side-by-side layout for pros/cons
- Green check icons for pros, subtle X for cons
- Optional overall rating (1-5)
- Mobile responsive (stacked or scrollable)
- Balanced presentation (no visual bias)

**Status:** Updated to `To Write Tests`
