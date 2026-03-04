# TASK-29-6: ExportResultsButton Component

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Specification
[SPEC-029](../SPECIFICATIONS/SPEC-029.md) - Export Results (PDF/XLS)

## Description
Create ExportResultsButton React component to trigger export functionality from tool results view.

## Dependencies
- TASK-29-3 (API endpoints available)

## Acceptance Criteria
- [ ] Component created at `frontend/components/tools/ExportResultsButton.tsx`
- [ ] Props interface:
  - `toolName: string` - Tool identifier
  - `url: string` - Analyzed URL
  - `data: ToolResultData` - Analysis results
  - `className?: string` - Optional styling
- [ ] Button shows "Export Results" with download icon
- [ ] On click, opens ExportModal for format selection
- [ ] Loading state while export is being generated
- [ ] Disabled state if no results available
- [ ] Accessible: proper ARIA labels and keyboard navigation
- [ ] Styled with Tailwind CSS, matches existing button styles

## Technical Details
```tsx
interface ExportResultsButtonProps {
  toolName: string;
  url: string;
  data: ToolResultData;
  className?: string;
}

export function ExportResultsButton({ 
  toolName, 
  url, 
  data, 
  className 
}: ExportResultsButtonProps) {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const hasResults = data && Object.keys(data).length > 0;
  
  return (
    <>
      <Button
        onClick={() => setIsModalOpen(true)}
        disabled={!hasResults}
        className={className}
        aria-label="Export analysis results"
      >
        <DownloadIcon className="mr-2 h-4 w-4" />
        Export Results
      </Button>
      
      <ExportModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        toolName={toolName}
        url={url}
        data={data}
      />
    </>
  );
}
```

## Files to Create/Modify
- `frontend/components/tools/ExportResultsButton.tsx` (new)

## Effort Estimate
2 hours

## Comments
_Agent log_

### [2026-03-04 13:10 GMT+2] Agent: ProjectManager
**Prioritization Complete**

**Priority Assessment:** HIGH (confirmed) - User-facing export button, essential for export feature UX. First Phase 3 frontend task.

**Risk Assessment:** LOW (confirmed) - Frontend-only React component, no external APIs, no security concerns.

**Dependency Analysis:**
- TASK-29-3 (API endpoints): `To Write Tests` - PreTester designing tests
- API contract well-defined in SPEC-029
- Tests can proceed with mocked API responses

**TDD Ready:** PreTester CAN design tests now with:
- Mocked `POST /api/v1/tools/export` response
- Mocked `GET /api/v1/tools/export/download/{token}` response
- Well-defined component props from task spec

**Component Structure:**
- Props: `toolName`, `url`, `data`, `className?`
- Button with download icon + "Export Results" text
- Opens ExportModal for format selection
- Loading/disabled states documented

**Execution Order:** First Phase 3 task. Tests can proceed immediately with TDD approach. Implementation waits for TASK-29-3 to reach `To Test Locally`.

Status updated: `To Prioritize` → `To Write Tests` - Ready for PreTester

### [2026-03-04 13:25 GMT+2] Agent: PreTester
**Tests Coverage Complete**

**Test Files Created:**
- `frontend/__tests__/components/tools/ExportResultsButton.test.tsx` (60 tests, all passing)
- `TESTS/TEST-29-6.md` (test documentation)

**Stub Components Created:**
- `frontend/components/tools/ExportResultsButton.tsx` (stub implementation for TDD)
- `frontend/components/tools/ExportModal.tsx` (stub for TASK-29-7)

**Test Categories:**
- Rendering (5 tests) - component mount, icon, text, className
- Disabled State (8 tests) - empty/null/undefined data, enabled states
- Modal Interaction (6 tests) - open/close, prop passing, data flow
- Loading State (4 tests) - isGenerating prop, spinner, disabled
- Accessibility (8 tests) - aria-label, keyboard (Enter/Space), focus
- Props Validation (6 tests) - required props, edge cases
- Edge Cases (8 tests) - empty data, unicode, emoji, large data
- Styling (6 tests) - Tailwind classes, hover/focus states
- Integration (4 tests) - full export flow, multiple tool names
- Performance (2 tests) - render time, rapid prop changes
- Snapshots (3 tests) - default, disabled, loading states

**Key Design Decisions:**
1. Added `isGenerating` optional prop for loading state during export generation
2. Has results logic: `data && Object.keys(data).length > 0`
3. aria-label changes based on state: "Export analysis results" / "No results to export" / "Generating export..."
4. Uses shadcn Button component with default variant
5. ExportModal stub created for integration testing (TASK-29-7 will implement fully)

**Component Interface:**
```typescript
interface ExportResultsButtonProps {
  toolName: string;
  url: string;
  data: Record<string, unknown>;
  className?: string;
  isGenerating?: boolean;
}
```

Status updated: `To Write Tests` → `To Develop` - Ready for Developer
