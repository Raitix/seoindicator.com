# DOCUMENTATION.md

---
## Report Generation API (TASK-4-8)

### Endpoint:
`POST /api/v1/sites/:siteId/reports/generate`

### Description:
Triggers the generation of site analysis reports (PDF/CSV formats). Supports asynchronous execution and status tracking for scalability.

### Request:
- **Method**: POST
- **Endpoint**: /api/v1/sites/{siteId}/reports/generate
- **Headers:**
  - Authorization: `Bearer <token>`
- **Request Body:**
  ```json
  {
    "name": "Traffic Analysis Report",
    "type": "analysis",
    "format": "pdf",
    "dateRange": ["2026-01-01", "2026-01-31"],
    "compareRange": ["2025-01-01", "2025-01-31"],
    "sections": ["traffic_sources", "conversion_rates"]
  }
  ```
  - `name`: (required) Report title.
  - `type`: (required) Type of report.
  - `format`: (required) Format (`pdf`, `csv`).
  - `dateRange`: ISO dates for data range.
  - `compareRange`: Optional, comparison timeframe.
  - `sections`: Optional, array of report sections.

### Response:
- **Success (202):**
  ```json
  {
    "reportId": "UUID",
    "status": "generating"
  }
  ```
  - `reportId`: Unique identifier for the report.
  - `status`: Initial processing state.

- **Error (4xx/5xx):**
  ```json
  {
    "error": "ValidationException",
    "message": "Invalid date range."
  }
  ```
  - Structured and informative error message.

### Dependencies:
- Data Aggregation Module: Consolidates analytics data.
- CSV/Excel Exporters: For generating tabular data
- Synchronous execution designed for MVP staging.

---
