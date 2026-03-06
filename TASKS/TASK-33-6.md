# TASK-33-6: Implement BadgeService for SVG Generation

## STATUS
`To Write Tests`

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

## Comments

### [2026-03-06 14:38 GMT+2] ProjectManager - Prioritization Assessment

**Decision:** HIGH priority confirmed ✅

**Priority Assessment:**
- **Foundation Service:** Core service for entire badge generation system
- **Enables Downstream:** TASK-33-15 (SVG Route Handler), TASK-33-16 (badge.js embed), all badge-related features
- **Critical Path:** Phase 1 Backend - essential for MVP badge functionality
- **Effort:** 4 hours (moderate effort, high impact)

**Risk Assessment: LOW (confirmed)**
- Standard Laravel service implementation
- No external API dependencies
- SVG generation with Blade templates (well-documented pattern)
- Cache layer for performance (standard Laravel Cache facade)
- All acceptance criteria are testable with unit tests

**Dependency Status:**
- TASK-33-1 (visibility columns): Done ✅ - SATISFIED

**Downstream Impact:**
- TASK-33-15 (SVG Badge Route Handler) - BLOCKED until this service exists
- TASK-33-17/33-18 (Badge impression/click tracking) - Need badge URL structure
- All Phase 3 Badge System tasks depend on this service

**Key Technical Points:**
- `generate(int $score, string $size, ?string $theme = null)` - Main method
- Three sizes: small (120x40), medium (150x100), large (300x150)
- Two themes: light, dark
- Score color-coded by range: Green (90-100), Blue (70-89), Yellow (50-69), Orange (30-49), Red (0-29)
- Cache key: `badge:{hash}:{size}:{theme}`, TTL: 1 hour
- SVG templates in Blade for server-side rendering

**TDD Ready:** PreTester can design tests immediately
- Test all three sizes render correct dimensions
- Test both themes (light/dark)
- Test all five score color ranges
- Test SVG structure and accessibility attributes
- Test cache hit/miss scenarios
- Test "Powered by SEOIndicator" branding text

**Handoff to PreTester:** Ready for test design. All dependencies satisfied.
