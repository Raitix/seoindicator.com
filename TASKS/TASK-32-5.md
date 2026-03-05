# TASK-32-5: ComparisonTable Component

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Specification Reference
**SPEC-32**: Competitor Comparison Landing Pages (Programmatic SEO)

## Description
Create a responsive feature comparison table component that displays feature availability for both tools being compared.

## Deliverables

### 1. Component File (`frontend/components/compare/ComparisonTable.tsx`)

```typescript
import { FeatureCategory, ComparisonTableProps } from '@/lib/compare/types';

export function ComparisonTable({ 
  features, 
  highlightWinner = true, 
  showTooltips = true 
}: ComparisonTableProps) {
  return (
    <div className="comparison-table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Feature</th>
            <th>SEOIndicator</th>
            <th>Competitor</th>
          </tr>
        </thead>
        <tbody>
          {features.map((category) => (
            <FeatureCategoryRow key={category.name} category={category} />
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

### 2. Features

| Feature | Description |
|---------|-------------|
| Responsive table | Horizontal scroll on mobile, full width on desktop |
| Feature categories | Grouped by category (On-Page, Technical, Backlinks, etc.) |
| Check/X icons | Visual indicators for feature availability |
| Highlighted cells | Competitive advantages highlighted |
| Expandable details | Click to expand feature details |
| Tooltips | Hover for additional information |

### 3. Sub-components

```typescript
// Feature category row with collapsible features
function FeatureCategoryRow({ category }: { category: FeatureCategory });

// Individual feature row
function FeatureRow({ feature }: { feature: Feature });

// Feature value cell (check, X, or text)
function FeatureCell({ value }: { value: boolean | string });

// Tooltip wrapper
function FeatureTooltip({ content, children }: { content: string; children: React.ReactNode });
```

### 4. Styling

- Use Tailwind CSS for styling
- Mobile: Stacked cards with horizontal scroll
- Desktop: Traditional table layout
- Highlight SEOIndicator advantages with accent color
- Striped rows for readability

## Acceptance Criteria
- [ ] Table renders all feature categories
- [ ] Check/X icons display correctly for boolean values
- [ ] String values display as text (e.g., "50/day", "Unlimited")
- [ ] Category headers are visually distinct
- [ ] Horizontal scroll on mobile (< 768px)
- [ ] Winner highlighting works when enabled
- [ ] Tooltips show on hover when enabled
- [ ] Keyboard accessible (Tab navigation)
- [ ] Screen reader friendly

## Dependencies
- TASK-32-1 (Types)

## Effort Estimate
`Medium` (3-4 hours)

## Files to Create/Modify
| File | Action |
|------|--------|
| `frontend/components/compare/ComparisonTable.tsx` | Create |

## Technical Notes
- Use existing Icon components (Check, X from Lucide)
- Follow shadcn/ui patterns for table styling
- Consider using TanStack Table for complex features
- Implement memo for performance

---

## Comments
### [2026-03-05 14:55 GMT+2] Agent: SpecSystemAnalyst
Task created as part of SPEC-32 breakdown. Core comparison visualization component.

### [2026-03-05 16:40 GMT+2] Agent: ProjectManager
**Priority Assessment:** HIGH (confirmed)
- Core comparison visualization component - user-facing, SEO-critical
- Displays feature availability for both tools being compared
- Central component for comparison landing pages (programmatic SEO)

**Risk Assessment:** LOW (confirmed)
- Frontend-only React component, no external APIs
- Uses existing Tailwind CSS and Lucide icons
- No database changes, no authentication

**Dependency Status:**
- TASK-32-1 (Types): Done ✅ - All interfaces available (CompetitorData, FeatureCategory, Feature, etc.)
- Dependency SATISFIED - PreTester can proceed immediately

**TDD Ready:** PreTester CAN design tests immediately using:
- `ComparisonTableProps` interface from TASK-32-1
- `FeatureCategory`, `Feature` types with `seoindicator`/`competitor` boolean or string values
- Mock feature data based on existing type definitions

**Phase Context:** Phase 2 Components - main comparison visualization
**Deliverables:** ComparisonTable.tsx + 4 sub-components (FeatureCategoryRow, FeatureRow, FeatureCell, FeatureTooltip)
**Key Features:** Responsive design, check/X icons, category grouping, winner highlighting, tooltips

**Status:** Updated to `To Write Tests`
