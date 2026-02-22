# SPEC-004: Automated SEO Reports

## Overview
This specification defines the technical implementation for automated SEO report generation. Reports transform raw ranking data into client-ready deliverables in PDF and CSV formats with scheduling and branding capabilities.

## Status
`To Develop`

## Feature Reference
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Created: 2026-02-20
- Architect: Architect
- Depends On: FEAT-001 (Keyword Rank Tracking)

---

## Technical Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                   Report Generation System                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │  Scheduler  │────▶│  Generator  │────▶│   Exporter  │   │
│  │  (Cron)     │     │  Service    │     │  (PDF/CSV)  │   │
│  └─────────────┘     └─────────────┘     └──────┬──────┘   │
│                                                 │          │
│  ┌──────────────────────────────────────────────┘          │
│  │                                                          │
│  ▼                                                          │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │   Report    │     │   Email     │     │   Storage   │   │
│  │   Templates │     │   Sender    │     │   Service   │   │
│  └─────────────┘     └─────────────┘     └─────────────┘   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Reports Table
```sql
CREATE TABLE reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    type TEXT NOT NULL,               -- 'daily', 'weekly', 'monthly', 'custom', 'one-time'
    format TEXT NOT NULL DEFAULT 'pdf',
    status TEXT NOT NULL DEFAULT 'pending',
    date_range_start DATE NOT NULL,
    date_range_end DATE NOT NULL,
    compare_range_start DATE,
    compare_range_end DATE,
    sections TEXT,                    -- JSON array of section names
    generated_at DATETIME,
    file_path TEXT,
    file_size INTEGER,
    error TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

CREATE INDEX idx_reports_site ON reports(site_id);
CREATE INDEX idx_reports_status ON reports(status);
CREATE INDEX idx_reports_generated ON reports(generated_at);
```

### Report Templates Table
```sql
CREATE TABLE report_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER,
    name TEXT NOT NULL,
    is_default INTEGER DEFAULT 0,
    sections TEXT NOT NULL,           -- JSON array
    branding TEXT,                    -- JSON: {logo_url, primary_color, font}
    cover_page_text TEXT,
    executive_summary TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_templates_site ON report_templates(site_id);
CREATE INDEX idx_templates_default ON report_templates(is_default);
```

### Scheduled Reports Table
```sql
CREATE TABLE scheduled_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL,
    template_id INTEGER,
    name TEXT NOT NULL,
    frequency TEXT NOT NULL,          -- 'daily', 'weekly', 'biweekly', 'monthly'
    format TEXT NOT NULL DEFAULT 'pdf',
    recipients TEXT NOT NULL,         -- JSON array of emails
    enabled INTEGER DEFAULT 1,
    last_run DATETIME,
    next_run DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES report_templates(id)
);

CREATE INDEX idx_scheduled_site ON scheduled_reports(site_id);
CREATE INDEX idx_scheduled_next_run ON scheduled_reports(next_run);
CREATE INDEX idx_scheduled_enabled ON scheduled_reports(enabled);
```

### Report Branding Table
```sql
CREATE TABLE report_branding (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL UNIQUE,
    logo_url TEXT,
    logo_file TEXT,                   -- Local file path if uploaded
    primary_color TEXT DEFAULT '#2563eb',
    secondary_color TEXT DEFAULT '#64748b',
    font_family TEXT DEFAULT 'Inter',
    company_name TEXT,
    company_website TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

CREATE INDEX idx_branding_site ON report_branding(site_id);
```

---

## API Endpoints

### Generate Report
```http
POST /api/v1/sites/:siteId/reports/generate
Content-Type: application/json

{
  "name": "Weekly SEO Report",
  "type": "weekly",
  "format": "pdf",
  "dateRange": {
    "start": "2026-02-13",
    "end": "2026-02-20"
  },
  "compareRange": {
    "start": "2026-02-06",
    "end": "2026-02-13"
  },
  "sections": ["summary", "keywords", "changes", "trends"]
}

Response 202:
{
  "success": true,
  "data": {
    "reportId": "rpt-abc123",
    "status": "generating",
    "estimatedTime": "30 seconds"
  }
}
```

### Get Report Status
```http
GET /api/v1/reports/:reportId

Response 200:
{
  "success": true,
  "data": {
    "id": "rpt-abc123",
    "name": "Weekly SEO Report",
    "status": "completed",
    "format": "pdf",
    "generatedAt": "2026-02-20T09:00:00Z",
    "downloadUrl": "/api/v1/reports/rpt-abc123/download",
    "fileSize": 245000
  }
}
```

### Download Report
```http
GET /api/v1/reports/:reportId/download

Response 200 (PDF):
Content-Type: application/pdf
Content-Disposition: attachment; filename="weekly-seo-report-2026-02-20.pdf"
[binary PDF data]

Response 200 (CSV):
Content-Type: text/csv
Content-Disposition: attachment; filename="weekly-seo-report-2026-02-20.csv"
[binary CSV data]
```

### List Reports
```http
GET /api/v1/sites/:siteId/reports

Query Parameters:
  - status: string (pending, generating, completed, failed)
  - type: string (daily, weekly, monthly)
  - page: number
  - perPage: number

Response 200:
{
  "success": true,
  "data": [
    {
      "id": "rpt-abc123",
      "name": "Weekly SEO Report",
      "type": "weekly",
      "status": "completed",
      "generatedAt": "2026-02-20T09:00:00Z",
      "fileSize": 245000
    }
  ],
  "meta": {
    "total": 10,
    "page": 1,
    "perPage": 20
  }
}
```

### Manage Templates

#### List Templates
```http
GET /api/v1/sites/:siteId/report-templates

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Default Template",
      "isDefault": true,
      "sections": ["summary", "keywords", "changes", "trends"]
    }
  ]
}
```

#### Create Template
```http
POST /api/v1/sites/:siteId/report-templates
Content-Type: application/json

{
  "name": "Client Report",
  "sections": ["summary", "keywords", "top-performers", "needs-attention"],
  "coverPageText": "Prepared for ACME Corp",
  "executiveSummary": "Custom summary text..."
}

Response 201:
{
  "success": true,
  "data": { ... }
}
```

### Manage Scheduled Reports

#### List Scheduled Reports
```http
GET /api/v1/sites/:siteId/scheduled-reports

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Weekly Client Report",
      "frequency": "weekly",
      "enabled": true,
      "nextRun": "2026-02-27T09:00:00Z",
      "recipients": ["client@example.com"]
    }
  ]
}
```

#### Create Scheduled Report
```http
POST /api/v1/sites/:siteId/scheduled-reports
Content-Type: application/json

{
  "name": "Weekly Client Report",
  "templateId": 1,
  "frequency": "weekly",
  "format": "pdf",
  "recipients": ["client@example.com", "manager@example.com"]
}

Response 201:
{
  "success": true,
  "data": {
    "id": 1,
    "nextRun": "2026-02-27T09:00:00Z",
    ...
  }
}
```

### Manage Branding

#### Get Branding
```http
GET /api/v1/sites/:siteId/report-branding

Response 200:
{
  "success": true,
  "data": {
    "logoUrl": "https://example.com/logo.png",
    "primaryColor": "#2563eb",
    "secondaryColor": "#64748b",
    "fontFamily": "Inter",
    "companyName": "ACME Corp"
  }
}
```

#### Update Branding
```http
PUT /api/v1/sites/:siteId/report-branding
Content-Type: application/json

{
  "primaryColor": "#10b981",
  "companyName": "ACME Inc"
}

Response 200:
{
  "success": true,
  "data": { ... }
}
```

#### Upload Logo
```http
POST /api/v1/sites/:siteId/report-branding/logo
Content-Type: multipart/form-data

file: [binary image data]

Response 200:
{
  "success": true,
  "data": {
    "logoUrl": "/uploads/logos/site-1-logo.png"
  }
}
```

---

## Report Sections

### Section Definitions
```typescript
type ReportSection = 
  | 'cover'              // Cover page with logo and title
  | 'summary'            // Executive summary with key metrics
  | 'keywords'           // Full keyword table
  | 'changes'            // Position changes highlights
  | 'top-performers'     // Best improving keywords
  | 'needs-attention'    // Declining keywords
  | 'trends'             // Position history charts
  | 'competitors'        // Competitor comparison (if available)
  | 'serp-features';     // SERP feature summary (if available)

interface SectionConfig {
  name: ReportSection;
  title: string;
  required: boolean;
  defaultEnabled: boolean;
}

const SECTIONS: SectionConfig[] = [
  { name: 'cover', title: 'Cover Page', required: true, defaultEnabled: true },
  { name: 'summary', title: 'Executive Summary', required: false, defaultEnabled: true },
  { name: 'keywords', title: 'Keyword Rankings', required: true, defaultEnabled: true },
  { name: 'changes', title: 'Position Changes', required: false, defaultEnabled: true },
  { name: 'top-performers', title: 'Top Performers', required: false, defaultEnabled: true },
  { name: 'needs-attention', title: 'Needs Attention', required: false, defaultEnabled: true },
  { name: 'trends', title: 'Trend Charts', required: false, defaultEnabled: true },
  { name: 'competitors', title: 'Competitor Analysis', required: false, defaultEnabled: false },
  { name: 'serp-features', title: 'SERP Features', required: false, defaultEnabled: false }
];
```

---

## Report Generator Service

### PDF Generation (MVP - Free)
```typescript
// Using PDFKit (free, no external dependencies)
import PDFDocument from 'pdfkit';

class PDFReportGenerator {
  async generate(report: Report, data: ReportData): Promise<Buffer> {
    const doc = new PDFDocument({ size: 'A4', margin: 50 });
    const chunks: Buffer[] = [];
    
    doc.on('data', chunk => chunks.push(chunk));
    
    // Cover Page
    await this.renderCover(doc, report, data);
    
    // Executive Summary
    if (report.sections.includes('summary')) {
      doc.addPage();
      await this.renderSummary(doc, data);
    }
    
    // Keyword Rankings
    doc.addPage();
    await this.renderKeywords(doc, data);
    
    // Position Changes
    if (report.sections.includes('changes')) {
      doc.addPage();
      await this.renderChanges(doc, data);
    }
    
    // Trend Charts
    if (report.sections.includes('trends')) {
      doc.addPage();
      await this.renderTrends(doc, data);
    }
    
    doc.end();
    
    return Buffer.concat(chunks);
  }
  
  private async renderCover(doc: PDFDocument, report: Report, data: ReportData): Promise<void> {
    // Logo
    if (data.branding?.logoUrl) {
      // Fetch and embed logo
    }
    
    // Title
    doc.fontSize(24).text(report.name, { align: 'center' });
    doc.moveDown();
    doc.fontSize(12).text(`${report.dateRangeStart} - ${report.dateRangeEnd}`, { align: 'center' });
    
    // Company name
    if (data.branding?.companyName) {
      doc.moveDown(2);
      doc.fontSize(14).text(`Prepared for ${data.branding.companyName}`, { align: 'center' });
    }
  }
}
```

### CSV Generation
```typescript
class CSVReportGenerator {
  generate(data: ReportData): string {
    const rows: string[][] = [];
    
    // Header
    rows.push([
      'Keyword',
      'Current Position',
      'Previous Position',
      'Change',
      'Search Engine',
      'Location',
      'Last Checked'
    ]);
    
    // Data rows
    for (const kw of data.keywords) {
      rows.push([
        kw.keyword,
        kw.currentPosition?.toString() || 'Not ranked',
        kw.previousPosition?.toString() || 'N/A',
        kw.change?.toString() || 'N/A',
        kw.searchEngine,
        kw.location,
        kw.lastCheckedAt
      ]);
    }
    
    return rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
  }
}
```

---

## Scheduler Service

### Cron Configuration
```typescript
class ReportScheduler {
  private jobs: Map<number, ScheduledJob> = new Map();
  
  async start(): Promise<void> {
    const scheduled = await this.getScheduledReports();
    
    for (const report of scheduled.filter(r => r.enabled)) {
      this.scheduleReport(report);
    }
  }
  
  private scheduleReport(report: ScheduledReport): void {
    const cronExpression = this.getCronExpression(report.frequency);
    
    const job = schedule.scheduleJob(cronExpression, async () => {
      await this.generateAndSend(report);
    });
    
    this.jobs.set(report.id, job);
  }
  
  private getCronExpression(frequency: string): string {
    switch (frequency) {
      case 'daily':    return '0 9 * * *';    // 9 AM daily
      case 'weekly':   return '0 9 * * 1';    // 9 AM Monday
      case 'biweekly': return '0 9 1,15 * *'; // 9 AM on 1st and 15th
      case 'monthly':  return '0 9 1 * *';    // 9 AM on 1st
      default:         return '0 9 * * 1';
    }
  }
}
```

---

## Email Service (FREE Options)

### Free Email Options
```typescript
// Option 1: Gmail SMTP (free, 500 emails/day limit)
const gmailTransport = {
  host: 'smtp.gmail.com',
  port: 587,
  secure: false,
  auth: {
    user: process.env.GMAIL_USER,
    pass: process.env.GMAIL_APP_PASSWORD
  }
};

// Option 2: Brevo (formerly Sendinblue) - 300 emails/day free
const brevoTransport = {
  host: 'smtp-relay.brevo.com',
  port: 587,
  auth: {
    user: process.env.BREVO_USER,
    pass: process.env.BREVO_KEY
  }
};

// Option 3: Mailgun - 5000 emails/month free tier
// Option 4: SendGrid - 100 emails/day free tier
```

### Report Email Template
```typescript
interface ReportEmailData {
  to: string[];
  reportName: string;
  siteName: string;
  dateRange: string;
  downloadUrl: string;
  previewUrl: string;
}

const emailTemplate = (data: ReportEmailData) => `
<!DOCTYPE html>
<html>
<body>
  <h1>${data.reportName}</h1>
  <p>Your SEO report for <strong>${data.siteName}</strong> is ready.</p>
  <p>Report period: ${data.dateRange}</p>
  
  <a href="${data.downloadUrl}" style="padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px;">
    Download Report
  </a>
  
  <p>Or <a href="${data.previewUrl}">view in dashboard</a></p>
  
  <hr>
  <p style="color: #666; font-size: 12px;">
    You received this email because you're subscribed to this report.
    <a href="{{unsubscribe_url}}">Unsubscribe</a>
  </p>
</body>
</html>
`;
```

---

## Implementation Files

### Backend Structure
```
backend/
├── src/
│   ├── api/
│   │   └── routes/
│   │       ├── reports.ts
│   │       ├── report-templates.ts
│   │       ├── scheduled-reports.ts
│   │       └── report-branding.ts
│   │
│   ├── services/
│   │   ├── report-generator.ts
│   │   ├── report-scheduler.ts
│   │   └── email-sender.ts
│   │
│   ├── exporters/
│   │   ├── pdf-exporter.ts
│   │   └── csv-exporter.ts
│   │
│   ├── repositories/
│   │   ├── report-repo.ts
│   │   ├── template-repo.ts
│   │   └── scheduled-report-repo.ts
│   │
│   └── models/
│       ├── report.ts
│       └── template.ts
│
└── tests/
    ├── unit/
    │   ├── pdf-exporter.test.ts
    │   └── csv-exporter.test.ts
    │
    └── integration/
        └── reports-api.test.ts
```

---

## Task Breakdown

### Phase 1: Core Infrastructure
1. **TASK-004-01**: Database schema - reports, templates, scheduled_reports, branding tables
2. **TASK-004-02**: Report model and repository
3. **TASK-004-03**: Template model and repository

### Phase 2: Exporters
4. **TASK-004-04**: CSV exporter
5. **TASK-004-05**: PDF exporter with PDFKit
6. **TASK-004-06**: Report data aggregation service

### Phase 3: API Layer
7. **TASK-004-07**: Reports CRUD API
8. **TASK-004-08**: Report generation endpoint
9. **TASK-004-09**: Download endpoint
10. **TASK-004-10**: Template management API

### Phase 4: Scheduling
11. **TASK-004-11**: Scheduled reports API
12. **TASK-004-12**: Scheduler service
13. **TASK-004-13**: Cron job integration

### Phase 5: Branding
14. **TASK-004-14**: Branding API
15. **TASK-004-15**: Logo upload handling
16. **TASK-004-16**: Branding in PDF generation

### Phase 6: Email
17. **TASK-004-17**: Email service (free SMTP)
18. **TASK-004-18**: Email templates
19. **TASK-004-19**: Report delivery

### Phase 7: Testing
20. **TASK-004-20**: Unit tests
21. **TASK-004-21**: Integration tests

---

## MVP Scope

### In Scope (MVP)
- PDF format only (PDFKit - free)
- CSV format
- Manual generation
- Standard template
- Basic branding (colors, logo)
- No scheduling (manual only)
- Email via free SMTP (Gmail/Brevo)

### Post-MVP
- Scheduled reports
- Custom templates
- Advanced branding
- Multiple email recipients
- Report preview
- White-label options

---

## Comments

### [2026-02-20 09:48] Architect: Specification Created
- Database schema: reports, templates, scheduled_reports, branding tables
- PDF generation with PDFKit (free)
- CSV generation built-in
- FREE email: Gmail SMTP or Brevo (300/day free)
- 21 implementation tasks across 7 phases
- Status: To Break Into Tasks
- Depends on: FEAT-001
- MVP: Manual generation, PDF/CSV, basic branding, free email

### [2026-02-20 17:15] ProjectManager: Prioritization Complete
Reviewed all 21 tasks across 7 phases:
- **Phase 1 (Infrastructure):** TASK-4-1, TASK-4-2, TASK-4-3
  - High Priority: Foundational elements needed for all other tasks.
  - Dependencies: FEAT-001 must be complete.

- **Phase 2 (Exporters):** TASK-4-4, TASK-4-5, TASK-4-6
  - Medium Priority: Core functionality built after infrastructure.

- **Phase 3 (API Layer):** TASK-4-7, TASK-4-8, TASK-4-9, TASK-4-10
  - Medium Priority: API readiness for integration.

- **Phase 4+ (Additive Features):** TASK-4-11 onward
  - Low Priority: Post-MVP improvements (scheduling, branding, email).

Updated status to begin handing off for development.

### [2026-02-20 10:35] SpecSystemAnalyst: Task Breakdown Complete
Created 21 tasks across 7 phases:
- **Phase 1 (Infrastructure):** TASK-4-1, TASK-4-2, TASK-4-3 - High priority, foundation tasks
- **Phase 2 (Exporters):** TASK-4-4, TASK-4-5, TASK-4-6 - Medium priority, core functionality
- **Phase 3 (API Layer):** TASK-4-7, TASK-4-8, TASK-4-9, TASK-4-10 - Medium priority
- **Phase 4 (Scheduling):** TASK-4-11, TASK-4-12, TASK-4-13 - Low priority, post-MVP
- **Phase 5 (Branding):** TASK-4-14, TASK-4-15, TASK-4-16 - Low priority, post-MVP
- **Phase 6 (Email):** TASK-4-17, TASK-4-18, TASK-4-19 - Low priority, post-MVP
- **Phase 7 (Testing):** TASK-4-20, TASK-4-21 - Medium priority, essential

**MVP Scope (Tasks 1-10):** Database, models, exporters, basic API - no scheduling, branding, or email
**Post-MVP Scope (Tasks 11-19):** Scheduling, branding, email delivery

**Dependencies:**
- All tasks depend on FEAT-001 (keyword rankings data)
- FEAT-003 integration optional (competitor comparison in reports)

**Handoff:** → ProjectManager for prioritization

### [2026-02-22 18:22] Agent: PreTester
- Wrote test documentation TEST-004-1..21 covering all tasks and edge cases.
- Moved TASK-4-1..4-21 to `To Develop`; spec set to `To Develop`.

### [2026-02-22 18:30] Agent: Developer
- Implemented TASK-4-1..4-4 (migrations, Report/ReportTemplate models+repositories, CSV exporter)
- Updated task statuses to `To Test Locally`

