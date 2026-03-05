# TASK-29-7: ExportModal Component

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Specification
[SPEC-029](../SPECIFICATIONS/SPEC-029.md) - Export Results (PDF/XLS)

## Description
Create ExportModal React component for format selection (PDF/XLS) and download initiation.

## Dependencies
- TASK-29-6 (ExportResultsButton triggers modal)

## Acceptance Criteria
- [ ] Component created at `frontend/components/tools/ExportModal.tsx`
- [ ] Modal displays:
  - Title: "Export Results"
  - Format selection: PDF or XLS radio buttons
  - Preview of what will be included
  - Cancel and Export buttons
- [ ] Format descriptions:
  - PDF: "Branded document with summary, indicators, and recommendations"
  - XLS: "Spreadsheet with indicator data for further analysis"
- [ ] Export button triggers API call to `/api/v1/tools/export`
- [ ] Shows loading spinner during export generation
- [ ] On success: triggers file download automatically
- [ ] On error: shows error message with retry option
- [ ] Rate limit error: shows time until next available export
- [ ] Modal closes after successful download
- [ ] Uses existing Modal component from shadcn/ui

## Technical Details
```tsx
interface ExportModalProps {
  isOpen: boolean;
  onClose: () => void;
  toolName: string;
  url: string;
  data: ToolResultData;
}

type ExportFormat = 'pdf' | 'xls';

export function ExportModal({ 
  isOpen, 
  onClose, 
  toolName, 
  url, 
  data 
}: ExportModalProps) {
  const [format, setFormat] = useState<ExportFormat>('pdf');
  const [isExporting, setIsExporting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  const handleExport = async () => {
    setIsExporting(true);
    setError(null);
    
    try {
      const response = await exportToolResults({
        tool: toolName,
        url,
        format,
        data,
      });
      
      // Trigger download
      window.location.href = response.downloadUrl;
      onClose();
    } catch (err) {
      if (err.status === 429) {
        setError('Rate limit exceeded. Please wait before exporting again.');
      } else {
        setError('Failed to generate export. Please try again.');
      }
    } finally {
      setIsExporting(false);
    }
  };
  
  // ... render modal
}
```

## Files to Create/Modify
- `frontend/components/tools/ExportModal.tsx` (new)

## Effort Estimate
2 hours

## Comments
_Agent log_

### [2026-03-05 14:45 GMT+2] Agent: ProjectManager
**Prioritization Complete**

**Priority Assessment:** HIGH (upgraded from Medium) - User-facing modal essential for complete export UX flow. The ExportResultsButton (TASK-29-6) is Done and opens this modal, but current stub is non-functional. This is the actual export functionality.

**Risk Assessment:** LOW (confirmed) - Frontend-only React component, no external APIs, uses existing export endpoint from TASK-29-3.

**Dependency Analysis:**
- TASK-29-6 (ExportResultsButton): Done ✅ - Button triggers this modal
- TASK-29-3 (ToolExportService API): Done ✅ - Endpoint ready
- ExportModal stub exists from TASK-29-6 TDD phase

**TDD Ready:** PreTester CAN design tests immediately:
- Mocked `POST /api/v1/tools/export` response
- Mocked file download behavior
- Well-defined props interface from task spec
- Existing stub component for reference

**Component Structure:**
- Props: `isOpen`, `onClose`, `toolName`, `url`, `data`
- Format selection: PDF/XLS radio buttons with descriptions
- Preview section showing what will be exported
- Cancel and Export buttons
- Loading spinner during generation
- Error handling with retry option
- Rate limit error display with time until next available

**Execution Order:** All dependencies satisfied - tests can proceed immediately. Implementation follows test design.

Status updated: `To Prioritize` → `To Write Tests` - Ready for PreTester
