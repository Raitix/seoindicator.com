# TASK-31-1: Fix Tool Analysis API 404

## STATUS
`New Feature`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
New issue discovered during production testing of TASK-29-8.

## Description
The `/api/tools/analyze` endpoint returns 404 in production, preventing all SEO tools from analyzing URLs.

## Impact
- Meta Tag Analyzer: Cannot analyze URLs
- Keyword Density Checker: Cannot analyze URLs
- All other tools relying on this endpoint are non-functional

## Error Details
```
Failed to load resource: the server responded with a status of 404
URL: https://seoindicator.com/api/tools/analyze
```

## Root Cause Analysis
- Backend API route `/api/tools/analyze` not registered or incorrect route
- Possible controller missing or misconfigured

## Acceptance Criteria
- [ ] `/api/tools/analyze` endpoint returns 200 for valid requests
- [ ] Tool analysis works for Meta Tag Analyzer
- [ ] Tool analysis works for Keyword Density Checker
- [ ] Error handling for invalid URLs

## Effort Estimate
2 hours

## Comments
_Agent log_

### [2026-03-05 10:42 GMT+2] Agent: ProdTester
**Issue Created**

Discovered during production testing of TASK-29-8 (Tool Page Integration). The ExportResultsButton integration is correct, but the backend API for tool analysis is broken.

**Evidence:**
- Browser console shows 404 for `/api/tools/analyze`
- Both Meta Tag Analyzer and Keyword Density Checker fail with "Could not access the page"
- Error occurs for all tested URLs (example.com, wikipedia.org, seoindicator.com)

This is a critical production issue blocking all tool functionality.
