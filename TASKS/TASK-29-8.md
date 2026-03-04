# TASK-29-8: Tool Page Integration

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
[SPEC-029](../SPECIFICATIONS/SPEC-029.md) - Export Results (PDF/XLS)

## Description
Integrate ExportResultsButton into tool result pages (Meta Tag Analyzer, Keyword Density Checker, Mobile-Friendly Checker, Social Media Preview).

## Dependencies
- TASK-29-6 (ExportResultsButton component)
- TASK-29-7 (ExportModal component)

## Acceptance Criteria
- [ ] ExportResultsButton added to all 4 tool result pages:
  - Meta Tag Analyzer
  - Keyword Density Checker
  - Mobile-Friendly Checker
  - Social Media Preview
- [ ] Button positioned in results header area (consistent placement)
- [ ] Button appears after analysis completes (not during loading)
- [ ] Tool result data structure matches expected ToolResultData interface
- [ ] Export works for each tool type with correct data mapping
- [ ] Consistent UX across all tools (same button style, same modal)
- [ ] Mobile responsive: button visible and usable on mobile devices

## Technical Details

### Tool Result Data Structure
```tsx
interface ToolResultData {
  analyzedAt: string;  // ISO timestamp
  results: {
    [indicatorName: string]: {
      value: string | number | boolean;
      status: 'ok' | 'warning' | 'error';
      recommendation?: string;
    };
  };
  summary?: {
    score?: number;
    passed: number;
    warnings: number;
    failed: number;
  };
}
```

### Integration Pattern
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

## Files to Create/Modify
- `frontend/components/tools/MetaTagAnalyzer/Results.tsx` (add button)
- `frontend/components/tools/KeywordDensityChecker/Results.tsx` (add button)
- `frontend/components/tools/MobileFriendlyChecker/Results.tsx` (add button)
- `frontend/components/tools/SocialMediaPreview/Results.tsx` (add button)
- `frontend/lib/api/tool-export.ts` (new - API helper functions)

## Effort Estimate
2 hours

## Comments
_Agent log_
