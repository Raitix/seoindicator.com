# TASK-33-6: Implement BadgeService for SVG Generation

## STATUS
`To Develop`

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

### [2026-03-06 16:25 GMT+2] PreTester - Test Coverage Complete

**Status:** To Develop ✅

**Tests Created:**
- `backend/tests/Unit/Services/BadgeServiceTest.php` (120+ tests)
- `PROJECT-SEOINDICATOR/TESTS/TEST-33-6.md` (test documentation)

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| SVC | 3 | Service instantiation |
| SVG | 6 | Basic SVG generation |
| SIZE-SML | 3 | Small badge dimensions (120x40) |
| SIZE-MED | 3 | Medium badge dimensions (150x100) |
| SIZE-LRG | 3 | Large badge dimensions (300x150) |
| THM-LGT | 3 | Light theme (#ffffff bg, #000000 text) |
| THM-DRK | 4 | Dark theme (#1a1a1a bg, #ffffff text) |
| CLR-GRN | 3 | Green range 90-100 (#22c55e) |
| CLR-BLU | 3 | Blue range 70-89 (#3b82f6) |
| CLR-YLW | 3 | Yellow range 50-69 (#eab308) |
| CLR-ORG | 3 | Orange range 30-49 (#f97316) |
| CLR-RED | 3 | Red range 0-29 (#ef4444) |
| SCR | 5 | Score display (rounding, boundaries) |
| BRD | 3 | Branding ("Powered by SEOIndicator") |
| CAT | 4 | Category breakdown for large badges |
| A11Y | 6 | Accessibility (role, aria-label) |
| CACHE | 5 | Caching behavior, TTL |
| VAL | 5 | Input validation, error handling |
| SVG-STR | 5 | SVG structure, no XSS |
| EDGE | 10 | Boundary conditions |
| PERF | 2 | Performance benchmarks |
| DATA | 30 | Data provider tests |
| SEC | 3 | Security (no external resources) |
| INT | 2 | Integration workflows |
| AC | 9 | Acceptance criteria verification |

**Key Test Patterns:**
- Data providers for size/dimension mapping
- Data providers for all 16 score color boundaries
- Data providers for theme color verification
- Cache mocking with Cache::shouldReceive()
- XML validation via DOMDocument
- Score clamping tests (negative → 0, >100 → 100)
- InvalidArgumentException for invalid sizes
- Theme fallback to 'light' for invalid values

**TDD Approach:**
- Tests expect BadgeService class to be created
- Tests expect generate() method with specified signature
- Tests expect InvalidArgumentException for invalid sizes
- Tests expect Blade templates for SVG rendering
- Tests expect Cache facade with 3600 second TTL

**Handoff to Developer:**
- Service interface fully specified in tests
- All 9 acceptance criteria covered
- Blade templates need: small.svg.blade.php, medium.svg.blade.php, large.svg.blade.php
- Category scores only displayed in large badge
