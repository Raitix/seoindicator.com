# TASK-29-6: ExportResultsButton Component

## STATUS
`To Prioritize`

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
