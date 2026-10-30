# PreTester.md — Project Lessons Learned

Knowledge base for PreTester agent working on SEOIndicator project.

---

## Test Patterns

### TypeScript Type Testing (TASK-7-2)
- Test type definitions with validation scenarios (valid/invalid values)
- Test interface required vs optional fields separately
- Test constant structure and content independently
- Include model class serialization/deserialization tests
- Mock model class in tests if actual implementation doesn't exist yet
- Use Chai's `expect().to.include()` for array membership tests

### SERP Feature Types
All 10 feature types:
1. `featured_snippet` - Answer box at top of results
2. `local_pack` - Map with 3 local business listings
3. `image_pack` - Horizontal row of images
4. `video` - Video thumbnail in results
5. `people_also_ask` - Expandable related questions
6. `knowledge_panel` - Entity information sidebar
7. `sitelinks` - Additional page links under result
8. `reviews` - Star ratings with result
9. `faq` - FAQ accordion in results
10. `product` - Product rich result

---

## Edge Cases

### SERP Feature Types (TASK-7-2)
- Types are case-sensitive (uppercase rejected)
- `isPresent` must be boolean (not "true" string or 1 number)
- URL max length is 2083 characters (HTTP spec)
- Position can be 0 (position zero = featured snippet)
- Unicode/emoji should be supported in title/snippet
- Newlines should be preserved in snippet text
- Extra properties on objects should be handled gracefully

---

## Test Documentation Format

Use `TESTS/SPEC-X-Y.md` with sections:
1. Task Reference (links)
2. Overview
3. Test Files table
4. Test Scenarios (tables with ID, Scenario, Input, Expected Output)
5. Mock Data samples
6. Coverage Targets
7. Test Execution commands
8. Dependencies
9. Notes for Developer
10. Comments (agent log)

---

## CSS Testing

### CSS Variables Testing (TASK-28-1)
- Parse CSS files with regex to extract variable definitions
- Use `fs.readFileSync` to read CSS file content
- Match `:root` and `.dark` blocks separately
- Match variable patterns: `/--([\w-]+)\s*:\s*([^;]+);/g`
- Parse oklch values with: `/oklch\s*\(\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)/`
- Test for:
  - Variable existence in correct scope
  - Valid oklch format
  - Lightness range (0-1)
  - Chroma range (0+)
  - Hue range (0-360)
  - Semantic hue ranges (green ≈ 120-160, red ≈ 0-50, blue ≈ 200-260)

### oklch Color Space
- Format: `oklch(L C H)` where:
  - L = Lightness (0-1)
  - C = Chroma (0+, higher = more saturated)
  - H = Hue (0-360 degrees)
- Neutral colors have C = 0
- Modern browsers support oklch (no IE support)

---

## Integration Testing

### Tool Page Integration (TASK-29-8)
- Mock external components (ExportResultsButton, ExportModal) when testing integration
- Create mock result components that mirror expected implementation pattern
- Test each tool individually plus cross-tool consistency
- Test loading states separately for each tool
- Verify data mapping for each tool type
- Test accessibility requirements (aria-labels, regions)
- Use `within()` to scope queries to specific containers
- Test consistent UX across all tools (same text, aria-labels, placement)

### Cross-Tool Consistency Testing
- Create test data for all 4 tools upfront
- Test same aspect across all tools in single test (e.g., all button texts)
- Use test IDs consistently across components
- Test placement patterns (justify-end, mt-4)
- Verify unique tool names passed correctly

### Loading State Testing
- Test that button is NOT rendered during loading
- Test that button IS rendered after loading completes
- Test transition from loading to ready state
- Use separate renders/unmounts for loading vs ready tests

---

## AI Suggestion Component Testing (TASK-21-11)

### Component Testing Patterns
- Mock components for TDD approach - create mock implementation that matches expected interface
- Test all states: default, loading, error, hasResults
- Test accessibility attributes on each state
- Test data attributes for visual regression hooks

### AISuggestionPanel Testing
- Test score display (null, 0, 100, regular values)
- Test issue severity rendering (critical, warning, info)
- Test suggestion type rendering (improve, rewrite, add, remove)
- Test provider fallback indicator (static_fallback shows indicator)
- Test debug mode visibility toggle
- Test loading skeleton with aria-live="polite"
- Test error state with role="alert"

### AISuggestionButton Testing
- Test disabled states: no context, loading, rate limit reached
- Test loading state: spinner, text change, disabled
- Test rate limit UI: remaining count, reset time tooltip
- Test hasResults state: "Refresh Suggestions" text, refresh icon
- Test all 10 indicator types (title, meta_description, robots, sitemap, headings, image_alt, filenames, url, links, 404)

### API Service Testing
- Mock fetch globally for service tests
- Test request body structure
- Test response parsing
- Test error handling (422, 429, 500, 503)
- Test rate limit header parsing
- Test caching behavior

### Tool Integration Testing
- Test each tool individually plus cross-tool consistency
- Create toolConfigs array with all tool/indicator mappings
- Test consistent UX: same button text, aria-labels, positioning
- Test full flows: click → loading → results

### Test Data Patterns
```typescript
// Mock suggestion
const mockSuggestion = {
  type: 'rewrite' as const,
  text: 'New Title',
  reason: 'Includes keyword'
};

// Mock issue
const mockIssue = {
  severity: 'warning' as const,
  message: 'Title too short'
};

// Mock response
const mockResponse = {
  success: true,
  data: {
    indicator: 'title',
    score: 75,
    issues: [mockIssue],
    suggestions: [mockSuggestion],
    best_practices: ['Keep under 60 chars'],
    provider: 'openrouter',
    model: 'llama-3.1-8b-instruct',
    generated_at: '2026-03-04T12:00:00Z'
  }
};
```

---

## Icon Registry Testing (TASK-28-14)

### Registry Structure Testing
- Test constant exists and has expected key count
- Use `as const` for TypeScript type inference
- Test kebab-case naming convention for keys
- Verify all values are valid Lucide components (check displayName)
- Test type export matches registry keys

### Indicator-to-Icon Mapping
- Test each indicator maps to correct Lucide icon (by displayName)
- Test invalid indicator returns undefined
- Verify semantic alignment between indicator purpose and icon meaning

### Tree-Shaking Verification
- Test icons are individually importable from lucide-react
- Verify direct Lucide references (Icon === lucide-react[name])
- Test named exports support destructuring
- Ensure module has no side effects (pure exports only)

### Integration Testing
- Test registry icons work with IndicatorIcon component via customIcon prop
- Render all registry icons to verify they're valid React components
- Test barrel export maintains existing exports

### Icon Mapping Table
| Indicator | Lucide Icon | Reason |
|-----------|-------------|--------|
| keyword-rankings | TrendingUp | Growth/position tracking |
| visibility-score | Eye | Visibility |
| competitor-analysis | Users | Multiple people/competitors |
| technical-health | Wrench | Tools/fixing |
| backlink-profile | Link | Links |
| content-opportunities | FileText | Content/documents |
| serp-features | Star | Featured/highlighted |
| page-speed | Zap | Performance/fast |

### Test Categories for Icon Registry
| Category | Tests | Description |
|----------|-------|-------------|
| Registry Structure | 5 | Constant, count, naming, types |
| Indicator Mapping | 9 | Each indicator + invalid |
| Type Export | 4 | Type exists, matches registry |
| Tree-Shaking | 4 | Import patterns |
| Naming Convention | 5 | Kebab-case, semantic alignment |
| Barrel Exports | 4 | Export verification |
| Acceptance Criteria | 6 | All ACs tested |
| Edge Cases | 7 | Non-existent, immutability |
| Integration | 2 | Component compatibility |

---

## Internal Linking Tests (TASK-32-16)

### Component Testing Patterns
- Mock components in integration tests to demonstrate expected interfaces
- Test link presence, href attributes, and accessible text separately
- Use `data-testid` attributes for reliable element selection
- Test empty states and edge cases (empty arrays, duplicates)

### RelatedComparisons Component
- Props: `slugs` (required), `currentSlug` (optional), `maxItems` (optional), `className` (optional)
- Filter out current slug to prevent self-referencing
- Apply max items limit for display control
- Handle empty array with friendly message
- Generate URLs in `/compare/[slug]` format

### Cross-Page Integration Testing
- Create mock components for each page (Homepage, Footer, Pricing, Comparison)
- Test link presence on each page independently
- Test cross-page consistency (URL patterns, link text)
- Verify all acceptance criteria across multiple components

### Link Testing Checklist
- [ ] Link renders correctly
- [ ] Correct href attribute
- [ ] Accessible link text
- [ ] aria-label for screen readers
- [ ] Keyboard navigable
- [ ] Valid URL pattern

### Test Data for Comparisons
```typescript
const mockComparisonSlugs = [
  'seoindicator-vs-ahrefs',
  'seoindicator-vs-semrush',
  'seoindicator-vs-moz',
  'seoindicator-vs-ubersuggest',
];
```

### URL Pattern Tests
- Comparison index: `/compare`
- Specific comparison: `/compare/[slug]` where slug contains `-vs-`
- Use regex `/^\/compare(\/[\w-]+)?$/` for validation

---

## Comparison Component Tests (TASK-32-19)

### TDD Test Pattern for Components
- Include mock component implementation in test file for reference
- Use data-testid attributes for reliable element selection
- Test all variants, props, accessibility, and edge cases
- Mock external dependencies (icons, analytics) with jest.mock

### Test Organization per Component
| Category | Tests | Purpose |
|----------|-------|---------|
| Rendering | 5+ | Component structure, elements |
| Variants/Props | 5+ | Different configurations |
| Accessibility | 4+ | ARIA, roles, keyboard |
| Edge Cases | 5+ | Empty, unicode, boundaries |
| Integration | 3+ | Full flows |

### Component Test Files Created
- ComparisonTable.test.tsx: 72 tests
- PricingComparison.test.tsx: 81 tests
- ProsConsCard.test.tsx: 62 tests
- UseCaseSection.test.tsx: 46 tests
- ComparisonCTA.test.tsx: 84 tests

### Mock Lucide Icons Pattern
```typescript
jest.mock('lucide-react', () => ({
  Check: () => <span data-testid="check-icon">✓</span>,
  X: () => <span data-testid="x-icon">✗</span>,
  Star: () => <span data-testid="star-icon">★</span>,
  ArrowRight: () => <span data-testid="arrow-icon">→</span>,
  HelpCircle: () => <span data-testid="help-icon">?</span>,
}));
```

### Analytics Mock Pattern
```typescript
const mockTrackEvent = jest.fn();
jest.mock('@/lib/analytics', () => ({
  trackEvent: (...args: unknown[]) => mockTrackEvent(...args),
}));

// In test
beforeEach(() => {
  mockTrackEvent.mockClear();
});

// Verify tracking
expect(mockTrackEvent).toHaveBeenCalledWith('event_name', expect.objectContaining({
  property: 'value',
}));
```

### Toggle State Testing
- Use useState in mock component
- Test both states (e.g., monthly/annual)
- Verify aria-pressed attribute changes
- Test keyboard activation (Enter key)

### Star Rating Testing
- Test 0-5 range
- Test decimal values (e.g., 4.5)
- Verify correct number of filled stars
- Test boundary values (0, 5)

---

## SVG Badge Route Testing (TASK-33-15)

### Route Handler Testing Pattern
- Test route structure separately from business logic
- Mock service layer interfaces (BadgeService, PublicAudit)
- Test parameter validation (hash, size, theme)
- Test response headers (Content-Type, Cache-Control, ETag)
- Test error responses with correct status codes and JSON format

### Hash Validation Testing
- Test 32-char alphanumeric regex: `/^[a-zA-Z0-9]{32}$/`
- Test boundary cases: too short, too long, special chars, unicode
- Test case sensitivity (uppercase, lowercase, mixed all valid)
- Test non-existent hashes (return null from mock)
- Test non-public audits (is_public: false)

### Size Validation Testing
- Valid sizes: `small` (120x40), `medium` (150x100), `large` (300x150)
- Invalid sizes return 400 Bad Request
- Size is case-sensitive (SMALL is invalid)
- Test dimension values match size parameter

### Theme Parameter Testing
- Valid themes: `light`, `dark`
- Default theme: `light` (when missing or empty)
- Invalid theme falls back to `light`
- Theme is case-sensitive (DARK is invalid)

### SVG Response Testing
- Content-Type: `image/svg+xml`
- Cache-Control: `public, max-age=3600` (1 hour)
- ETag format: `"{score}-{size}-{theme}"`
- SVG must have valid XML structure with xmlns attribute
- Test score display in SVG content
- Test accessibility attributes (aria-label, role="img")

### Error Response Testing
- 404 for invalid/non-existent/non-public hash
- 400 for invalid size
- Error Content-Type: `application/json`
- Error format: `{ error: "message" }`

### Security Testing for Routes
- Test SQL injection prevention in parameters
- Test path traversal prevention
- Test XSS prevention (no scripts in SVG output)
- Verify no sensitive data in responses (no URLs, IDs in badge)

### Mock Service Pattern
```typescript
const mockBadgeService = {
  generate: jest.fn((options: BadgeGenerateOptions): BadgeGenerateResult => {
    const { score, size, theme } = options;
    // Return mock SVG with dimensions based on size
    return { svg: '<svg>...</svg>', width: 150, height: 100 };
  }),
};

const mockPublicAudit = {
  findByHash: jest.fn((hash: string): PublicAuditData | null => {
    // Return valid audit, null, or non-public audit based on hash
    if (hash === 'nonexistent') return null;
    if (hash === 'private') return { ...audit, is_public: false };
    return { ...audit, is_public: true };
  }),
};
```

---

## Visibility Toggle Settings Testing (TASK-33-22)

### Settings Page Integration
- Test permission-based rendering (isOwner check)
- Settings page should show access denied for non-owners
- Unauthenticated users should be redirected to login
- Multiple settings sections should use consistent Card components

### Toggle Switch Component
- Test both public and private initial states
- Test publish flow (private → public) with optimistic UI
- Test unpublish flow (public → private) with confirmation dialog
- Warning message about embedded badges is required
- Toast notifications on success/error
- Loading states during API calls

### Public URL Display
- Only visible when isPublic is true
- URL input is readonly
- Copy button with clipboard API mock
- Success feedback (checkmark icon) after copy
- Full URL with domain (not just path)

### Confirmation Dialog Pattern
- Show confirmation before unpublish (not before publish)
- Include warning about embedded badges breaking
- Focus trap within dialog
- Focus restored to trigger on close
- Cancel button closes dialog without action
- Confirm button calls onUnpublish callback

### Test Mock Patterns
```typescript
// Mock sonner toast
jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn(),
  },
}));

// Mock clipboard API
const mockClipboardWrite = jest.fn();
Object.assign(navigator, {
  clipboard: {
    writeText: mockClipboardWrite,
  },
});

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  Globe: () => <span data-testid="globe-icon">Globe</span>,
  Lock: () => <span data-testid="lock-icon">Lock</span>,
  Copy: () => <span data-testid="copy-icon">Copy</span>,
  Check: () => <span data-testid="check-icon">Check</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loading</span>,
  AlertTriangle: () => <span data-testid="alert-icon">Alert</span>,
}));
```

### Acceptance Criteria Testing
- Visibility section added to settings page
- Toggle switch shows current state (Public/Private)
- Public URL displayed when public (with copy button)
- Warning shown when changing from public to private
- "This will break any embedded badges" message
- Success toast on save
- Audit settings page respects permissions
- Unit tests for settings UI

---

## Badge Analytics Dashboard Testing (TASK-33-29)

### Dashboard Component Testing
- Test all 4 states: Loading, Error, Data, Empty
- Mock charting libraries (Recharts) for deterministic testing
- Mock icons (Lucide) for consistent test output
- Use `data-testid` attributes for reliable element selection
- Test number formatting (K, M suffixes) in stats cards
- Test change indicators (positive/negative) with data attributes

### Chart Testing with Mocked Recharts
- Mock LineChart, Line, XAxis, YAxis, ResponsiveContainer
- Pass data points via data attributes for verification
- Test empty data arrays render without crash
- Test ResponsiveContainer usage for responsiveness

### Table Testing Patterns
- Test table headers separately from row content
- Use `within()` to scope queries to specific table
- Test sorting behavior (highest values first)
- Test empty state messages
- Test URL truncation in cells
- Test links to detail pages

### Date Range Selector Testing
- Test preset options (7d, 30d, 90d, custom)
- Test custom date input visibility toggle
- Test onChange callback with correct parameters
- Test accessible label on select element

### Mock Data Patterns for Analytics
```typescript
const mockStats = {
  totalImpressions: 5600,
  totalClicks: 234,
  avgCTR: 4.2,
  impressionsChange: 12.5,  // Can be positive or negative
  clicksChange: 8.3,
  ctrChange: -2.1,
};

const mockTimeSeriesData = [
  { date: '2026-03-01', impressions: 450, clicks: 18 },
  { date: '2026-03-02', impressions: 520, clicks: 22 },
];

const mockTopReferrer = {
  domain: 'example.com',
  impressions: 1200,
  clicks: 48,
  ctr: 4.0,
};

const mockTopAudit = {
  auditId: '1',
  url: 'https://example.com/page',
  hash: 'abc123def456',
  impressions: 890,
  clicks: 38,
  ctr: 4.3,
  score: 92,
};
```

### Number Formatting Test Cases
| Input | Expected Output |
|-------|-----------------|
| 0 | "0" |
| 234 | "234" |
| 5600 | "5.6K" |
| 12345 | "12.3K" |
| 2500000 | "2.5M" |
| 1000000000 | "1000.0M" |

### Acceptance Criteria to Test ID Mapping
- Create a mapping table in test documentation
- Each AC should have at least one test ID
- Group tests by AC for easy verification

---

## Laravel API Controller Testing (TASK-34-6)

### Controller Test Patterns
- Mock service interfaces with Mockery for isolation
- Use `Sanctum::actingAs($user)` for authentication in tests
- Use `LengthAwarePaginator` for paginated response mocks
- Test all CRUD operations plus custom actions
- Test HTTP method restrictions (405 for wrong method)
- Test rate limiting with multiple sequential requests

### Form Request Testing
- Test each validation rule independently
- Test boundary values (min, max, exact)
- Test required vs optional fields
- Test array validation (type, count, item validation)
- Test enum validation (valid values, invalid values, case sensitivity)
- Test that authorize() returns true for authenticated users
- Test custom error messages exist

### Policy Testing
- Test user can access their own resources
- Test user cannot access another user's resources
- Test all policy methods exist (view, update, delete, custom)
- Test with same user object and different objects with same ID
- Test denial of all actions for non-owner

### Mock Project Pattern
```php
protected function mockProject(array $attributes = []): object
{
    return new class($attributes) {
        public array $attributes;

        public function __construct(array $attributes = [])
        {
            $this->attributes = array_merge([
                'id' => 1,
                'user_id' => 1,
                'brand_name' => 'Test Brand',
                // ... default values
            ], $attributes);
        }

        public function __get(string $name)
        {
            return $this->attributes[$name] ?? null;
        }
    };
}
```

### Service Mock Pattern
```php
$this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
    $mock->shouldReceive('getProject')->once()->andReturn($project);
    $mock->shouldReceive('runQueries')->once()->andReturn(['job_ids' => ['uuid-1']]);
});
```

### Rate Limiting Tests
- Test rate limit enforcement (n requests succeed, n+1 fails with 429)
- Test rate limit headers if present
- Test rate limit reset after time period

### Error Response Testing
- 401 Unauthorized: `{ message: "Unauthenticated" }`
- 403 Forbidden: `{ success: false, error: "Forbidden" }`
- 404 Not Found: `{ success: false, error: "Not found" }`
- 422 Validation Error: `{ success: false, errors: {...} }`
- 429 Rate Limited: `{ success: false, error: "Too many requests", retry_after: 3600 }`

### Security Test Categories
- SQL injection in input fields (stored as literal string)
- SQL injection in URL parameters (404, no exploitation)
- XSS in text fields (stored but escaped in output)
- Path traversal in URL (404, no file access)
- Mass assignment protection (user_id, is_admin ignored)
- CSRF protection (token-based auth)
- No sensitive data in responses (no passwords, tokens)
- Enumeration protection (same error for non-existent and unauthorized)
- Input length limits enforced
- JSON parsing errors handled (400 for invalid JSON)

---

## Frontend Comparison Page Testing (TASK-34-17)

### TDD Pattern for Complex Pages
- Create test files for each sub-component separately
- Mock API hooks (useComparisonData) before implementation
- Mock charting libraries (Recharts) for deterministic output
- Mock icon libraries (Lucide) for consistent assertions
- Use data-testid attributes for element selection
- Use data-* attributes for state verification (data-trend, data-is-brand, data-fill)

### Test Organization for Multi-Component Pages
| Test File | Tests | Description |
|-----------|-------|-------------|
| `page.test.tsx` | 48 | Main page, states, navigation |
| `ComparisonRankingTable.test.tsx` | 62 | Table component |
| `HeadToHeadComparison.test.tsx` | 71 | Comparison cards |
| `CompetitiveAdvantages.test.tsx` | 54 | Category analysis |
| `PlatformBreakdown.test.tsx` | 48 | Platform charts |
| `CompetitorDetailPanel.test.tsx` | 56 | Accordion panel |
| `ComparisonActions.test.tsx` | 41 | Action buttons |
| **Total** | **380** | Full coverage |

### Mock Data Patterns for Comparison Pages
```typescript
const mockComparisonData = {
  projectId: '1',
  brandName: 'SEOIndicator',
  competitors: [
    { id: 'comp-1', name: 'Ahrefs', keywords: ['ahrefs'] },
  ],
  rankings: {
    rankings: [
      { position: 1, name: 'Ahrefs', rate: 38.5, trend: 'up' },
      { position: 2, name: 'SEOIndicator', rate: 23.5, trend: 'up', isBrand: true },
    ],
    brand_position: 2,
  },
  headToHead: {
    brand: { mentions: 15, rate: 23.5, sentiment: 0.45 },
    competitor: { mentions: 25, rate: 38.5, sentiment: 0.30 },
    co_mentions: 5,
  },
};
```

### Color Coding via Data Attributes
- Brand: `data-fill="#3b82f6"` (blue)
- Trend Up: `data-trend="up"` with green icon
- Trend Down: `data-trend="down"` with red icon
- Brand Row: `data-is-brand="true"`

### Responsive Testing Breakpoints
- Mobile: 375px (stacked layout)
- Tablet: 768px (2-column cards)
- Desktop: 1024px+ (full comparison view)

### Chart Mocking Pattern
```typescript
vi.mock('recharts', () => ({
  ResponsiveContainer: ({ children }) => <div data-testid="responsive-container">{children}</div>,
  RadarChart: ({ children, data }) => <div data-testid="radar-chart" data-points={data?.length}>{children}</div>,
  BarChart: ({ children, data }) => <div data-testid="bar-chart" data-items={data?.length}>{children}</div>,
  Radar: ({ data, fill }) => <div data-testid="radar" data-points={data?.length} data-fill={fill} />,
  Bar: ({ fill, dataKey }) => <div data-testid="bar" data-fill={fill} data-key={dataKey} />,
}));
```

---

## Laravel Eloquent Model Testing (TASK-35-3)

### Model Test Categories
Standard categories for Eloquent model tests:
1. **Model Instantiation** (3 tests) - Constructor, fillable, guarded
2. **Casts** (5-8 tests) - All type casts verified
3. **Relationships** (3 tests) - Relationship exists, returns correct type, data accessible
4. **Scopes** (5-8 tests) - Each scope with multiple scenarios
5. **Methods** (3-6 tests) - Business logic methods
6. **Factory** (5-8 tests) - Factory creation, all states
7. **Unique Constraints** (2-3 tests) - Duplicates fail, variations succeed
8. **Edge Cases** (4-6 tests) - Nulls, max values, boundaries
9. **Security** (3 tests) - SQL injection, XSS, invalid values
10. **Accessors** (3-5 tests) - Computed attributes
11. **Integration** (2 tests) - CRUD operations, cascade delete
12. **Performance** (2 tests) - Index usage, bulk operations

### Factory State Patterns
```php
// Location-based state
public function forLocation(int $locationId): static
{
    return $this->state(fn (array $attributes) => [
        'location_id' => $locationId,
    ]);
}

// Date-based state
public function withDate(string $date): static
{
    return $this->state(fn (array $attributes) => [
        'insights_date' => $date,
    ]);
}

// High-value state
public function highEngagement(): static
{
    return $this->state(fn (array $attributes) => [
        'views' => $this->faker->numberBetween(500, 5000),
        // ... high values
    ]);
}

// Zero-value state
public function noEngagement(): static
{
    return $this->state(fn (array $attributes) => [
        'views' => 0,
        // ... all zeros
    ]);
}
```

### Scope Testing Pattern
```php
/** @test */
public function scope_for_date_range_filters_by_date(): void
{
    $location = BusinessLocation::factory()->create();
    
    // Create records at different dates
    GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-01')->create();
    GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-05')->create();
    GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-10')->create();
    
    // Test scope
    $results = GBPInsight::forDateRange('2026-03-03', '2026-03-12')->get();
    
    // Verify filtering
    $this->assertCount(1, $results);
    $this->assertEquals('2026-03-05', $results->first()->insights_date->format('Y-m-d'));
}
```

### Accessor Testing Pattern
```php
/** @test */
public function get_star_rating_attribute_returns_star_string(): void
{
    $review1 = GBPReview::factory()->create(['rating' => 1]);
    $review5 = GBPReview::factory()->create(['rating' => 5]);
    
    $this->assertEquals('★☆☆☆☆', $review1->star_rating);
    $this->assertEquals('★★★★★', $review5->star_rating);
}
```

### Unique Constraint Testing
- Test duplicate fails (QueryException expected)
- Test same location/field with different date succeeds
- Test different location with same date succeeds

### TDD for Models with Dependencies
- Tests can use factories that create parent models
- Parent model doesn't need to exist - factory handles it
- RefreshDatabase trait handles cleanup
- Tests run against migrations (schema must exist)

### Post Type Labels Map
| post_type | Label | Description |
|-----------|-------|-------------|
| EVENT | Event | Time-based promotional event |
| OFFER | Offer | Special discount or promotion |
| UPDATE | Update | General business update |
| COVID | Covid Update | COVID-related information |

### Sentiment Labels
| sentiment | Label | Use Case |
|-----------|-------|----------|
| positive | Positive | Rating 4-5 |
| negative | Negative | Rating 1-2 |
| neutral | Neutral | Rating 3 or null |

---

## Content Decay Dashboard Widget Testing (TASK-36-14)

### Widget Component Testing Pattern
- Test all 4 states: Loading, Error, Empty, Data
- Use mock component implementation in test file for TDD reference
- Follow existing widget patterns (AuditWidget from TASK-14-17)
- Test state transitions: Loading → Error/Empty/Data

### Widget State Machine
```
Loading → Error (on API failure)
Loading → Empty (totalCandidates === 0)
Loading → Data (totalCandidates > 0)
```

### Dashboard Widget Test Categories
| Category | Tests | Description |
|----------|-------|-------------|
| Rendering | 10 | Component structure, attributes |
| Empty State | 8 | No decay detected |
| Loading State | 8 | Initial load with skeletons |
| Summary Card | 12 | Severity badges, totals, upside |
| Top Candidates | 15 | List items, actions, truncation |
| Timestamp | 8 | Relative time formatting |
| Navigation Links | 8 | View All, hrefs |
| Quick Actions | 10 | View, Plan, Ignore buttons |
| Error State | 6 | API failure handling |
| Accessibility | 10 | ARIA, keyboard, contrast |

### Severity Color Coding
- Critical: Red (#ef4444) - data-severity="critical"
- High: Orange (#f97316) - data-severity="high"
- Medium: Yellow (#eab308) - data-severity="medium"

### Navigation Integration Testing
Test 5 integration points:
1. **SiteNav** - "Content Decay" link in SEO Tools menu
2. **QuickActionsDropdown** - "View Decay Queue" action
3. **GlobalSearch** - Decay candidates in search results
4. **SiteSettings** - Toggle + configure link
5. **Dashboard** - Widget placement and layout

### Mock Widget Props Pattern
```typescript
interface ContentDecayWidgetProps {
  siteId: string;
  summary?: DecaySummary;
  topCandidates?: DecayCandidate[];
  isLoading?: boolean;
  error?: string | null;
  onRefresh?: () => void;
  onViewCandidate?: (id: number) => void;
  onMarkPlanned?: (id: number) => void;
  onIgnore?: (id: number) => void;
  className?: string;
}
```

### Mock Decay Data Pattern
```typescript
const mockSummary = {
  criticalCount: 5,
  highCount: 12,
  mediumCount: 8,
  totalCandidates: 25,
  estimatedRecoveryUpside: 1500,
  recoveredCount: 3,
  lastScannedAt: '2026-03-10T15:30:00Z',
};

const mockTopCandidates = [
  {
    id: 1,
    pageUrl: 'https://example.com/blog/seo-tips',
    pageTitle: '10 SEO Tips for 2024',
    decayScore: 85,
    severity: 'critical',
    primaryReason: 'clicks_down',
    lostClicks: 450,
  },
];
```

### Number Formatting for Recovery Upside
| Input | Expected Output |
|-------|-----------------|
| 0 | "0" |
| 500 | "500" |
| 1500 | "1.5K" |
| 2500000 | "2.5M" |

### Reason Code Formatting
| Code | Display |
|------|---------|
| clicks_down | "Click loss" |
| impressions_down | "Impression loss" |
| ctr_down | "CTR drop" |
| avg_position_down | "Position decline" |
| tracked_rankings_down | "Ranking decline" |
| audit_issues_present | "Technical issues" |
| stale_content | "Stale content" |

### URL Truncation Pattern
- Default max length: 40 characters
- Truncate with ellipsis: `url.substring(0, 37) + '...'`
- Full URL in title attribute for tooltip

### Relative Time Formatting
| Time Diff | Display |
|-----------|---------|
| < 1 min | "Just now" |
| < 60 min | "X minutes ago" |
| < 24 hours | "X hours ago" |
| 1 day | "Yesterday" |
| < 7 days | "X days ago" |
| >= 7 days | "Mar 5, 2026" |

### Navigation Structure
```
SEO Tools
├── Keyword Rankings
├── Site Audits
├── Content Decay  ← NEW (after Audits)
├── AI Visibility
└── Annotations
```

### API Endpoints for Widget
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/sites/{siteId}/content-decay/summary` | Summary stats |
| GET | `/api/v1/sites/{siteId}/content-decay/candidates?limit=3&sort=decay_score` | Top 3 candidates |
| PATCH | `/api/v1/content-decay/candidates/{id}` | Update status |

---

## Stripe Webhook Handler Testing (TASK-26-10)

### TDD with Missing Dependencies
- When dependencies don't exist (CreditService, StripeGatewayService), use `markTestSkipped()`
- Create tests with mocked interfaces that show expected behavior
- Document which tests need real implementation vs can run with mocks
- Tests should still verify structure (class exists, method exists)

### Security Testing for Webhooks
- Always test signature verification with timing-constant comparison
- Test invalid/missing/expired signatures return 401
- Test that no sensitive data appears in error responses
- Test idempotency to prevent double-processing
- Test 5-second response timeout (Stripe requirement)

### Test Organization for Webhook Handlers
| Test File | Purpose |
|-----------|---------|
| Controller Test | HTTP endpoint, routing, response codes |
| Service Test | Business logic, event routing, processing |
| Verifier Test | Security-focused signature verification |

### Stripe Webhook Test Categories
- Signature verification (valid, invalid, missing, expired)
- Event routing (payment succeeded, failed, refund)
- Idempotency (duplicate prevention, concurrent handling)
- Error handling (missing payment intent, user not found)
- Edge cases (large payload, zero amount, currency)
- Security (timing-constant comparison, no sensitive data)
- HTTP method restrictions (405 for non-POST)
- Response format (200/400/401/500)

### Mock Patterns for Stripe
```php
// Create valid signature
$timestamp = time();
$signedPayload = "{$timestamp}.{$payload}";
$signature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);
$stripeSignature = "t={$timestamp},v1={$signature}";

// Check for Stripe SDK availability
if (class_exists(\Stripe\Webhook::class)) {
    // Use Stripe SDK verification
}
```

### Stripe Signature Format
- Format: `t={timestamp},v1={signature}`
- Timestamp in seconds since epoch
- Signature is HMAC-SHA256 of `{timestamp}.{payload}`
- Must use timing-constant comparison (hash_equals)

---

## Stripe Payment Testing (TASK-26-23)

### Mock Stripe.js Pattern
```typescript
jest.mock('@stripe/react-stripe-js', () => ({
  CardElement: ({ onChange, options }: any) => (
    <div data-testid="card-element" data-complete={options?.disabled ? 'false' : 'true'}>
      <input
        data-testid="card-number-input"
        placeholder="Card number"
        onChange={(e) => onChange?.({ 
          complete: e.target.value.length === 16,
          error: null,
          empty: e.target.value.length === 0,
        })}
      />
    </div>
  ),
  useElements: () => ({
    getElement: jest.fn(() => ({ mock: true })),
  }),
  useStripe: () => ({
    confirmCardPayment: jest.fn(),
    createPaymentMethod: jest.fn(),
  }),
}));
```

### Card Type Detection Tests
- Visa: starts with 4
- Mastercard: starts with 55 or 2221-2720
- Amex: starts with 34 or 37
- Discover: starts with 6011, 65, 644-649

### Payment Flow Test Categories
| Category | Tests | Description |
|----------|-------|-------------|
| Rendering | 10+ | Component structure, states |
| Card Element | 15 | Input handling, validation |
| Submission | 15 | Success, failure, processing |
| Validation | 10 | Form fields, real-time |
| Security | 10 | PCI compliance, CSRF |
| Edge Cases | 10 | Network, timeouts, unicode |
| Accessibility | 10 | ARIA, keyboard, screen reader |
| Integration | 5+ | Full flows |

### Stripe Error Codes to Test
| Code | Message | Test Scenario |
|------|---------|---------------|
| `card_declined` | Card was declined | PF-028 |
| `insufficient_funds` | Insufficient funds | PF-029 |
| `expired_card` | Card has expired | PC-023 |
| `incorrect_cvc` | Invalid security code | PC-024 |
| `processing_error` | Processing error | PC-025 |
| `rate_limit` | Too many attempts | PC-027 |

### Security Testing for Payments
- No card data in localStorage
- No card data in console logs
- No card data in DOM (use Stripe Elements)
- CSRF token in request headers
- Idempotency key for duplicate prevention
- HTTPS enforcement
- PCI compliance via Stripe Elements

### Payment State Machine
```
Initial → Card Input → Processing → Success
                   ↓
                 Error → Retry → Processing
```

### Mock Data for Payment Tests
```typescript
const mockPackage = {
  id: 'pkg_pro',
  name: 'Pro Credit Package',
  credits: 100,
  price: 29.99,
  currency: 'USD',
  bonus_credits: 10,
};

const mockPaymentIntent = {
  id: 'pi_1234567890',
  client_secret: 'pi_123_secret_abc',
  amount: 2999,
  currency: 'usd',
  status: 'requires_payment_method',
};

const mockTransaction = {
  id: 'txn_123456',
  amount: 29.99,
  currency: 'USD',
  credits: 110,
  status: 'completed',
  payment_method: 'Visa ****4242',
  created_at: '2026-03-14T19:30:00Z',
};
```

### API Hook Testing Pattern (React Query)
```typescript
import { renderHook, waitFor, act } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const createWrapper = () => {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return ({ children }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
};

// Test usage
const { result } = renderHook(() => useCreatePaymentIntent(), {
  wrapper: createWrapper(),
});

await act(async () => {
  await result.current.mutateAsync({ packageId: 'pkg_pro' });
});
```

### Test Coverage Targets for Payments
| Component | Statements | Branches | Functions | Lines |
|-----------|------------|----------|-----------|-------|
| PaymentForm | 90% | 85% | 95% | 90% |
| PaymentMethodSelector | 90% | 85% | 95% | 90% |
| PaymentConfirmation | 90% | 85% | 95% | 90% |
| PaymentHistory | 90% | 85% | 95% | 90% |
| API Hooks | 95% | 90% | 100% | 95% |

---

## Credit Aggregation Service Testing (TASK-27-9)

### Query Builder Mutation Bug
When building services that calculate multiple metrics from the same base query, **do not reuse query builder instances**. Each `where*` method modifies the builder in place AND returns `$this`.

**Bug Pattern:**
```php
// BUGGY - conditions accumulate!
$query = Model::where('type', 'usage');
return [
    'total' => $query->sum('amount'),                    // WHERE type = 'usage'
    'today' => $query->whereDate(...)->sum('amount'),   // WHERE type = 'usage' AND DATE(...)
    'thisWeek' => $query->whereBetween(...)->sum('amount'), // ALL PREVIOUS + BETWEEN!
];
```

**Fix Pattern:**
```php
// FIXED - fresh query for each metric
return [
    'total' => Model::where('type', 'usage')->sum('amount'),
    'today' => Model::where('type', 'usage')->whereDate(...)->sum('amount'),
    'thisWeek' => Model::where('type', 'usage')->whereBetween(...)->sum('amount'),
];
```

### Service Test Categories (29 tests)
| Category | Tests | Description |
|----------|-------|-------------|
| Service Instantiation | 1 | Container resolution |
| getStats() Structure | 2 | Return keys, empty database |
| Balance Metrics | 5 | totalHeld, average, usersWithBalance, lowBalance |
| Spend Metrics | 5 | total, today, thisWeek, thisMonth, filtering |
| Revenue Metrics | 5 | total, today, thisWeek, thisMonth, filtering |
| Recent Transactions | 3 | Pagination, ordering, relationships |
| Performance | 2 | <100ms, efficient queries |
| Edge Cases | 6 | Negative amounts, large values, precision, timezone, PII |

### Duplicate Migration Detection
When tests fail with "table already exists" errors:
1. Check for duplicate migration files (same table name)
2. Remove duplicates, keeping only the earliest
3. Run tests with in-memory SQLite to catch this early

### Negative Amount Handling
When service uses `abs()` to ensure positive values:
- Tests should expect positive results
- Document that negative amounts are converted (intentional)
- Example: `usage(-50)` results in `spend.total = 50`

### Cache Testing Notes
- Tests don't explicitly test cache behavior
- Service uses `Cache::remember()` with 5-minute TTL
- RefreshDatabase trait clears cache between tests
- Performance tests verify caching is effective

---

Last updated: 2026-03-18
