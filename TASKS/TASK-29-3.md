# TASK-29-3: ToolExportService & Controller

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
[SPEC-029](../SPECIFICATIONS/SPEC-029.md) - Export Results (PDF/XLS)

## Description
Create ToolExportService to coordinate export generation and ToolExportController for API endpoints.

## Dependencies
- TASK-29-1 (PdfToolExporter)
- TASK-29-2 (XlsToolExporter)

## Acceptance Criteria
- [ ] ToolExportService created at `app/Services/Tools/ToolExportService.php`
- [ ] Service methods:
  - `createExport(string $tool, string $url, string $format, array $data, int $userId): string` - returns download URL
  - `downloadExport(string $token, int $userId): Response` - returns file download response
- [ ] ToolExportController created at `app/Http/Controllers/Api/V1/ToolExportController.php`
- [ ] API endpoints:
  - `POST /api/v1/tools/export` - create export, returns download URL
  - `GET /api/v1/tools/export/download/{token}` - download file
- [ ] Request validation for export endpoint:
  - `tool`: required, string
  - `url`: required, url
  - `format`: required, in:pdf,xls
  - `data`: required, array
- [ ] Error responses:
  - 401: Unauthenticated
  - 404: Invalid/expired token
  - 422: Invalid format or missing data

## Technical Details
```php
class ToolExportService
{
    public function createExport(
        string $tool,
        string $url,
        string $format,
        array $data,
        int $userId
    ): string {
        // Build ToolResult DTO
        // Generate file using appropriate exporter
        // Store in temp location
        // Generate download token
        // Return download URL
    }
    
    public function downloadExport(string $token, int $userId): Response
    {
        // Validate token and ownership
        // Stream file to response
        // Delete file and token
    }
}
```

## Files to Create/Modify
- `backend/app/Services/Tools/ToolExportService.php` (new)
- `backend/app/Http/Controllers/Api/V1/ToolExportController.php` (new)
- `backend/routes/api.php` (add routes)

## Effort Estimate
3 hours

## Comments
_Agent log_
