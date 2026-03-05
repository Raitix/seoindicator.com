# TASK-33-6: Implement BadgeService for SVG Generation

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Create the BadgeService that generates SVG badges in three sizes (small, medium, large) with score visualization, color coding, and branding. This is the core badge generation logic.

## Acceptance Criteria
- [ ] `generate(int $score, string $size, ?string $theme = null)` method creates SVG
- [ ] Three sizes supported: 'small' (120x40), 'medium' (150x100), 'large' (300x150)
- [ ] Two themes supported: 'light', 'dark'
- [ ] Score color-coded by range:
  - 90-100: Green (#22c55e)
  - 70-89: Blue (#3b82f6)
  - 50-69: Yellow (#eab308)
  - 30-49: Orange (#f97316)
  - 0-29: Red (#ef4444)
- [ ] All badges include "Powered by SEOIndicator" text
- [ ] Large badge includes category breakdown
- [ ] SVG is valid, accessible (aria-label, role)
- [ ] Cache generated SVGs for performance
- [ ] Unit tests for all badge variations

## Dependencies
- TASK-33-1 (visibility columns for audit data)

## Effort Estimate
**4 hours** - SVG generation with multiple variants

## Technical Notes
- Use Laravel's Cache facade for SVG caching
- Cache key: `badge:{hash}:{size}:{theme}`
- Cache TTL: 1 hour (score may change on re-audit)
- SVG templates stored in backend/resources/views/badges/
- Use Blade for SVG rendering

## Files to Create
- `backend/app/Services/BadgeService.php`
- `backend/resources/views/badges/small.svg.blade.php`
- `backend/resources/views/badges/medium.svg.blade.php`
- `backend/resources/views/badges/large.svg.blade.php`
- `backend/tests/Unit/Services/BadgeServiceTest.php`
