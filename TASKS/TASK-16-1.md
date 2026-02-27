# TASK-16-1: Setup Tools Infrastructure

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)
- Specification: [SPEC-016](../SPECIFICATIONS/SPEC-016.md)

---

## Description
Set up the foundational infrastructure for interactive SEO tools. This includes the CORS proxy API endpoint, Cheerio integration for HTML parsing, base tool class architecture, and shared utilities for all tools.

---

## Acceptance Criteria
- [ ] Create `/api/v1/tools/analyze` endpoint for fetching external URLs
- [ ] Implement CORS proxy to bypass cross-origin restrictions
- [ ] Integrate Cheerio library for server-side HTML parsing
- [ ] Create base `SEOTool` abstract class with common methods
- [ ] Implement URL validation and sanitization utilities
- [ ] Add rate limiting infrastructure (per IP, track usage)
- [ ] Create shared types/interfaces for tool results
- [ ] Add error handling middleware for tool errors
- [ ] Write unit tests for infrastructure components

---

## Technical Details

### API Endpoint
```
POST /api/v1/tools/analyze
Content-Type: application/json

{
  "url": "https://example.com",
  "tool": "meta-tag-analyzer"
}
```

### Response Structure
```json
{
  "success": true,
  "data": {
    "url": "https://example.com",
    "analyzedAt": "2026-02-27T12:00:00Z",
    "tool": "meta-tag-analyzer",
    "results": { ... }
  }
}
```

### Base SEOTool Class
```typescript
abstract class SEOTool {
  abstract name: string;
  abstract analyze($: CheerioAPI, url: string): ToolResult;
  
  // Shared methods
  protected fetchUrl(url: string): Promise<string>;
  protected parseHtml(html: string): CheerioAPI;
  protected validateUrl(url: string): boolean;
}
```

### CORS Proxy Logic
1. Validate URL format
2. Check against blocklist (localhost, internal IPs)
3. Fetch URL with timeout (5s max)
4. Return HTML content for parsing

---

## Dependencies
- None (foundational task)

---

## Effort Estimate
**Medium** (4-6 hours)

---

## Comments
_Agent communication log_

### [2026-02-27] Agent: SpecSystemAnalyst
Created task. This is the foundation for all interactive tools. Must be completed before other tool tasks can proceed.
