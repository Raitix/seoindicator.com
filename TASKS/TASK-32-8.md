# TASK-32-8: UseCaseSection Component

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Low`

## Specification Reference
**SPEC-32**: Competitor Comparison Landing Pages (Programmatic SEO)

## Description
Create a use-case section component that recommends which tool is best suited for different user scenarios, team sizes, and budgets.

## Deliverables

### 1. Component File (`frontend/components/compare/UseCaseSection.tsx`)

```typescript
import { UseCase, UseCaseSectionProps } from '@/lib/compare/types';

export function UseCaseSection({ seoindicator, competitor }: UseCaseSectionProps) {
  return (
    <section className="use-case-section">
      <h2>Who Should Use Each Tool?</h2>
      
      <div className="use-case-grid">
        {/* SEOIndicator use cases */}
        <div className="use-case-column">
          <h3>SEOIndicator is Best For</h3>
          {seoindicator.map((useCase, i) => (
            <UseCaseCard key={i} useCase={useCase} />
          ))}
        </div>
        
        {/* Competitor use cases */}
        <div className="use-case-column">
          <h3>Competitor is Best For</h3>
          {competitor.map((useCase, i) => (
            <UseCaseCard key={i} useCase={useCase} />
          ))}
        </div>
      </div>
    </section>
  );
}
```

### 2. UseCase Card Sub-component

```typescript
function UseCaseCard({ useCase }: { useCase: UseCase }) {
  return (
    <Card>
      <h4>{useCase.title}</h4>
      <p>{useCase.description}</p>
      <div className="tags">
        <Badge>{AUDIENCE_LABELS[useCase.audience]}</Badge>
        <Badge variant="outline">{BUDGET_LABELS[useCase.budget]}</Badge>
      </div>
    </Card>
  );
}
```

### 3. Audience and Budget Labels

```typescript
const AUDIENCE_LABELS = {
  'solo': 'Solo Users',
  'small-team': 'Small Teams',
  'enterprise': 'Enterprise',
  'agency': 'Agencies'
};

const BUDGET_LABELS = {
  'free': 'Free',
  'budget': 'Budget-Friendly',
  'mid-range': 'Mid-Range',
  'premium': 'Premium'
};
```

### 4. Example Use Cases

**SEOIndicator:**
- "Budget-conscious solopreneurs who need core SEO tools without the enterprise price tag"
- "Small marketing teams starting their SEO journey"
- "Freelancers who need reliable rank tracking on a budget"

**Competitor (e.g., Ahrefs):**
- "Agencies managing 50+ client sites with budget for premium tools"
- "Enterprise SEO teams with dedicated resources"
- "Link-building specialists who need extensive backlink databases"

## Acceptance Criteria
- [ ] Both tools' use cases displayed side-by-side
- [ ] Each use case has title, description, audience, and budget tags
- [ ] Tags use appropriate badge styling
- [ ] Mobile responsive layout
- [ ] Clear visual separation between tools
- [ ] Honest, balanced recommendations

## Dependencies
- TASK-32-1 (Types)

## Effort Estimate
`Small` (2-3 hours)

## Files to Create/Modify
| File | Action |
|------|--------|
| `frontend/components/compare/UseCaseSection.tsx` | Create |

## Technical Notes
- Use existing Card and Badge components
- Ensure balanced presentation
- Consider adding icons for audience types
- Follow existing section styling patterns

---

## Comments
### [2026-03-05 14:55 GMT+2] Agent: SpecSystemAnalyst
Task created as part of SPEC-32 breakdown. Helps users self-select based on their situation.

### [2026-03-06 17:14 GMT+2] Agent: ProjectManager
**Priority Assessment:** MEDIUM (confirmed)
- User-facing display component for comparison landing pages
- Not foundation infrastructure (types already exist)
- Not direct CTA/conversion component (that's TASK-32-9)
- Not SEO schema (that's TASK-32-10)
- Valuable for user self-selection but not critical-path for SEO or conversion

**Risk Assessment:** LOW (confirmed)
- Frontend-only React component, no external APIs
- Uses existing Card and Badge components
- Mobile responsive layout required
- Well-defined interfaces from TASK-32-1

**Dependency Status:**
- TASK-32-1 (Types): Done ✅ - All interfaces available
- `UseCaseSectionProps`, `UseCase`, `AUDIENCE_LABELS`, `BUDGET_LABELS` interfaces defined

**TDD Ready:** PreTester can design tests immediately:
- UseCaseSectionProps interface available
- Component structure well-defined in task description
- Acceptance criteria clear (6 items)
- Mobile responsive testing required

**Phase Context:** Phase 2 Components - after foundation infrastructure

**Status:** Updated to `To Write Tests` for PreTester pickup
