# TEST-29-8: Tool Page Integration Tests

## Task Reference
- Task: [TASK-29-8](../TASKS/TASK-29-8.md)
- Specification: [SPEC-029](../SPECIFICATIONS/SPEC-029.md)
- Feature: [FEAT-029](../FEATURES/FEAT-029.md)

## Overview
Integration tests for adding ExportResultsButton to tool result pages:
- Meta Tag Analyzer
- Keyword Density Checker
- Mobile-Friendly Checker
- Social Media Preview

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `frontend/__tests__/integration/tool-export-integration.test.tsx` | 80+ | Full integration tests |

## Test Scenarios

### Meta Tag Analyzer Integration Tests (MTI-001 to MTI-008)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| MTI-001 | Renders export button in results | Button in document |
| MTI-002 | Button has correct tool name | data-tool="meta-tag-analyzer" |
| MTI-003 | Button has correct URL | data-url matches analyzed URL |
| MTI-004 | Button appears after analysis completes | No button during loading, visible after |
| MTI-005 | Button positioned in results header area | Parent has justify-end class |
| MTI-006 | Button has results data | data-has-results="true" |
| MTI-007 | Button is accessible | aria-label present |
| MTI-008 | Data structure matches ToolResultData | Correct mapping |

### Keyword Density Checker Integration Tests (KDI-001 to KDI-008)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| KDI-001 | Renders export button in results | Button in document |
| KDI-002 | Button has correct tool name | data-tool="keyword-density-checker" |
| KDI-003 | Button has correct URL | data-url matches analyzed URL |
| KDI-004 | Button appears after analysis completes | Visible only after loading |
| KDI-005 | Button positioned in results header area | Parent has justify-end class |
| KDI-006 | Button has results data | data-has-results="true" |
| KDI-007 | Button is accessible | aria-label present |
| KDI-008 | Different URL values work | Various URLs accepted |

### Mobile-Friendly Checker Integration Tests (MFI-001 to MFI-008)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| MFI-001 | Renders export button in results | Button in document |
| MFI-002 | Button has correct tool name | data-tool="mobile-friendly-checker" |
| MFI-003 | Button has correct URL | data-url matches analyzed URL |
| MFI-004 | Button appears after analysis completes | Visible only after loading |
| MFI-005 | Button positioned in results header area | Parent has justify-end class |
| MFI-006 | Button has results data | data-has-results="true" |
| MFI-007 | Button is accessible | aria-label present |
| MFI-008 | Handles low mobile score correctly | Still renders with data |

### Social Media Preview Integration Tests (SMI-001 to SMI-008)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SMI-001 | Renders export button in results | Button in document |
| SMI-002 | Button has correct tool name | data-tool="social-media-preview" |
| SMI-003 | Button has correct URL | data-url matches analyzed URL |
| SMI-004 | Button appears after analysis completes | Visible only after loading |
| SMI-005 | Button positioned in results header area | Parent has justify-end class |
| SMI-006 | Button has results data | data-has-results="true" |
| SMI-007 | Button is accessible | aria-label present |
| SMI-008 | Handles missing OG tags correctly | Still renders with data |

### Consistent UX Tests (CUX-001 to CUX-005)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CUX-001 | All 4 tools have export button | All render correctly |
| CUX-002 | Same button style across all tools | "Export Results" text |
| CUX-003 | Same aria-label across all tools | "Export analysis results" |
| CUX-004 | Consistent positioning pattern | justify-end class |
| CUX-005 | Consistent data structure accepted | ToolResultData interface |

### Mobile Responsive Tests (MRT-001 to MRT-005)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| MRT-001 | Button visible on mobile | Button visible |
| MRT-002 | Proper touch target spacing | min-h/w-[44px] classes |
| MRT-003 | Button text readable on mobile | Text visible |
| MRT-004 | Button position adapts on mobile | flex-col/flex-row |
| MRT-005 | Button works with touch events | Clickable |

### Data Mapping Tests (DMT-001 to DMT-007)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| DMT-001 | Meta Tag data maps correctly | has-results="true" |
| DMT-002 | Keyword Density data maps correctly | has-results="true" |
| DMT-003 | Mobile Friendly data maps correctly | has-results="true" |
| DMT-004 | Social Media data maps correctly | has-results="true" |
| DMT-005 | Empty results handled correctly | has-results="false" |
| DMT-006 | Null data handled correctly | has-results="false" |
| DMT-007 | Undefined data handled correctly | has-results="false" |

### Edge Cases Tests (ECT-001 to ECT-007)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| ECT-001 | Very long URL handled | URL accepted |
| ECT-002 | Unicode in data handled | Renders correctly |
| ECT-003 | Large data object handled | 500+ indicators work |
| ECT-004 | URL with query parameters | URL accepted |
| ECT-005 | URL with fragment | URL accepted |
| ECT-006 | Special chars in tool name | Name accepted |
| ECT-007 | Rapid state changes | No errors |

### Accessibility Tests (ACC-001 to ACC-007)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| ACC-001 | Button has aria-label | Attribute present |
| ACC-002 | Button is focusable | Tab focus works |
| ACC-003 | Enter key triggers click | Keyboard works |
| ACC-004 | Space key triggers click | Keyboard works |
| ACC-005 | Button has role attribute | role="button" |
| ACC-006 | Disabled state announced | Disabled attribute |
| ACC-007 | Results region accessible | Region with label |

### Integration Tests (INT-001 to INT-005)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| INT-001 | Complete export flow - Meta Tag | Full flow works |
| INT-002 | Complete export flow - Keyword | Full flow works |
| INT-003 | Complete export flow - Mobile | Full flow works |
| INT-004 | Complete export flow - Social | Full flow works |
| INT-005 | All tools render simultaneously | All 4 work together |

### Snapshot Tests (SNT-001 to SNT-004)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SNT-001 | Meta Tag Analyzer snapshot | Matches snapshot |
| SNT-002 | Keyword Density Checker snapshot | Matches snapshot |
| SNT-003 | Mobile-Friendly Checker snapshot | Matches snapshot |
| SNT-004 | Social Media Preview snapshot | Matches snapshot |

## Mock Data

```typescript
const mockMetaTagData = {
  analyzedAt: '2026-03-04T10:00:00Z',
  title: 'Example Page Title',
  titleLength: 19,
  description: 'This is an example meta description.',
  descriptionLength: 31,
  warnings: [{ type: 'warning', message: 'Consider adding Open Graph tags' }],
  results: {
    title: { content: 'Example Page Title', length: 19, status: 'ok' },
    description: { content: 'This is an example meta description.', length: 31, status: 'ok' },
  },
  summary: { score: 85, passCount: 2, warningCount: 1, failCount: 0 },
};

const mockKeywordDensityData = {
  keyword: 'example',
  density: 2.5,
  occurrences: 5,
  totalWords: 200,
  status: 'good',
  headings: [
    { tag: 'h1', text: 'Example Heading', keywordFound: true },
  ],
  warnings: [],
  analyzedAt: '2026-03-04T10:00:00Z',
  results: { density: { value: 2.5, status: 'ok' } },
  summary: { score: 90, passCount: 3, warningCount: 0, failCount: 0 },
};

const mockMobileFriendlyData = {
  mobileScore: 85,
  viewport: { present: true, valid: true, content: 'width=device-width' },
  responsiveIndicators: { hasMediaQueries: true, hasFlexibleGrid: true },
  issues: [],
  analyzedAt: '2026-03-04T10:00:00Z',
  results: { viewport: { value: 'width=device-width', status: 'ok' } },
  summary: { score: 85, passCount: 4, warningCount: 0, failCount: 0 },
};

const mockSocialMediaData = {
  openGraph: { title: 'Example OG Title', description: 'Example OG Description' },
  twitterCard: { card: 'summary_large_image', site: '@example' },
  missing: [],
  warnings: [],
  analyzedAt: '2026-03-04T10:00:00Z',
  results: { openGraph: { status: 'ok' }, twitterCard: { status: 'ok' } },
  summary: { score: 100, passCount: 2, warningCount: 0, failCount: 0 },
};
```

## Integration Pattern

```tsx
// In tool result page component
{analysisComplete && (
  <div className="flex justify-end mb-4">
    <ExportResultsButton
      toolName="meta-tag-analyzer"
      url={analyzedUrl}
      data={resultsData}
    />
  </div>
)}
```

## Files to Modify

| File | Change |
|------|--------|
| `frontend/components/tools/MetaTagAnalyzer/Results.tsx` | Add ExportResultsButton |
| `frontend/components/tools/KeywordDensityChecker/Results.tsx` | Add ExportResultsButton |
| `frontend/components/tools/MobileFriendlyChecker/Results.tsx` | Add ExportResultsButton |
| `frontend/components/tools/SocialMediaPreview/Results.tsx` | Add ExportResultsButton |
| `frontend/lib/api/tool-export.ts` | API helper functions |

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Meta Tag Integration | 100% | 8 tests |
| Keyword Density Integration | 100% | 8 tests |
| Mobile Friendly Integration | 100% | 8 tests |
| Social Media Integration | 100% | 8 tests |
| Consistent UX | 100% | 5 tests |
| Mobile Responsive | 100% | 5 tests |
| Data Mapping | 100% | 7 tests |
| Edge Cases | 100% | 7 tests |
| Accessibility | 100% | 7 tests |
| Integration | 100% | 5 tests |
| Snapshots | 100% | 4 tests |
| **Total** | **100%** | **72+ tests** |

## Test Execution

```bash
# Run all integration tests
npm test -- frontend/__tests__/integration/tool-export-integration.test.tsx

# Run with coverage
npm run test:coverage -- --testPathPattern="tool-export-integration"

# Run specific test category
npm test -- --testNamePattern="Meta Tag Analyzer"
```

## Notes for Developer

### Implementation Requirements

1. **Files to Create/Modify:**
   - `frontend/components/tools/MetaTagAnalyzer/Results.tsx` (or MetaTagResults.tsx)
   - `frontend/components/tools/KeywordDensityChecker/Results.tsx`
   - `frontend/components/tools/MobileFriendlyChecker/Results.tsx`
   - `frontend/components/tools/SocialMediaPreview/Results.tsx`
   - `frontend/lib/api/tool-export.ts` (new - API helper functions)

2. **Integration Pattern:**
   ```tsx
   import { ExportResultsButton } from '@/components/tools/ExportResultsButton';
   
   // In results component
   const exportData = {
     analyzedAt: result.analyzedAt,
     results: { /* tool-specific data */ },
     summary: { score, passCount, warningCount, failCount },
   };
   
   // In JSX
   <div className="flex justify-end mb-4">
     <ExportResultsButton
       toolName="meta-tag-analyzer"
       url={analyzedUrl}
       data={exportData}
     />
   </div>
   ```

3. **Button Positioning:**
   - Place in results header area
   - Use `flex justify-end` for right alignment
   - Add `mb-4` for spacing below button

4. **Show After Analysis Completes:**
   - Condition: `analysisComplete && !isLoading`
   - Button should not appear during loading state

5. **Data Mapping:**
   - Each tool maps its specific data to ToolResultData interface
   - Include `analyzedAt`, `results`, and `summary` fields
   - Status values: 'ok', 'warning', 'error'

6. **Mobile Responsive:**
   - Button should be visible and usable on mobile
   - Minimum touch target: 44x44px
   - Consider `flex-col sm:flex-row` for layout adaptation

### Tool Names (exact values):
- `meta-tag-analyzer`
- `keyword-density-checker`
- `mobile-friendly-checker`
- `social-media-preview`

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| ExportResultsButton | TASK-29-6 | Ready for integration |
| ExportModal | TASK-29-7 | To be implemented |
| Button (shadcn) | Available | ui/button.tsx |

## Comments
_Agent log_

### [2026-03-04 13:58 GMT+2] Agent: PreTester
Created comprehensive integration test suite with 72+ test scenarios covering:
- Meta Tag Analyzer Integration (8 tests)
- Keyword Density Checker Integration (8 tests)
- Mobile-Friendly Checker Integration (8 tests)
- Social Media Preview Integration (8 tests)
- Consistent UX (5 tests)
- Mobile Responsive (5 tests)
- Data Mapping (7 tests)
- Edge Cases (7 tests)
- Accessibility (7 tests)
- Integration (5 tests)
- Snapshots (4 tests)

Key test patterns:
1. Mocked ExportResultsButton with data attributes for verification
2. Mocked ExportModal for integration testing
3. Tested loading vs loaded states for all tools
4. Verified consistent positioning pattern (flex justify-end)
5. Tested data mapping for each tool type
6. Comprehensive accessibility coverage

Status: Tests Coverage Complete → Ready for Developer
