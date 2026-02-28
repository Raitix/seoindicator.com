# TASK-24-3: ManifestFetcherService

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create service to fetch and parse web app manifest from target URLs. Handles HTML parsing to find manifest link and JSON retrieval.

## Acceptance Criteria
- [ ] ManifestFetcherService created with methods:
  - fetch(string $siteUrl): ?array - fetches manifest from URL
  - extractManifestUrl(string $html, string $baseUrl): ?string - parses <link rel="manifest">
  - resolveManifestUrl(string $manifestUrl, string $baseUrl): string - resolves relative URLs
- [ ] Handles edge cases:
  - No manifest link in HTML (returns null)
  - Invalid JSON response (returns null, logs error)
  - Relative vs absolute manifest URLs
  - Cross-origin manifest URLs (with validation)
- [ ] Reuses URL validation from TASK-16-1 (SSRF protection)
- [ ] Request timeout: 30 seconds total
- [ ] Manifest size limit: 10KB max
- [ ] Unit tests for all methods with various HTML/URL scenarios

## Dependencies
- TASK-16-1 (Tools infrastructure - URL validation, SSRF protection)
- TASK-24-1 (Database migration - for storing results)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use Laravel HTTP Client for requests
- Use Symfony DomCrawler for HTML parsing
- Log warnings for missing manifests (not errors)
- Consider caching manifest fetch results temporarily

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for manifest fetching. Core service that discovers and retrieves manifest.json. Medium risk due to external URL fetching - mitigated by reusing SSRF protection from TASK-16-1.
