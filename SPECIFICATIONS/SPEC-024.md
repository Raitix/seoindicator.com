# SPEC-024: PWA / Web App Manifest Indicator

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Feature: [FEAT-024](../FEATURES/FEAT-024.md)
- Tasks:
  - TASK-24-1: Database Migration - PWA Analyses Table
  - TASK-24-2: PWAAnalysis Model and Repository
  - TASK-24-3: ManifestFetcherService
  - TASK-24-4: ManifestValidatorService
  - TASK-24-5: IconValidatorService
  - TASK-24-6: PWAScorerService
  - TASK-24-7: PWASuggestionService
  - TASK-24-8: PWA Analysis API Endpoints
  - TASK-24-9: PWA Guide API Endpoint
  - TASK-24-10: PWA Dashboard Components
  - TASK-24-11: PWA Score Gauge and Icon Validation Table
  - TASK-24-12: PWA Recommendations Component
  - TASK-24-13: Visibility Score Integration
  - TASK-24-14: PWA Guide Page (Frontend)
- Integrates with: SPEC-010 (Visibility Scores), SPEC-016 (Tools Infrastructure)

---

## Overview

Implement a PWA (Progressive Web App) indicator that analyzes web app manifest presence, validates required icons and metadata, calculates a PWA readiness score, and provides actionable suggestions for improvement. This helps users understand their site's PWA capabilities which impact mobile UX and SEO.

---

## Architecture

### System Design
```
┌───────────────────────────────────────────────────────────┐
│                    Frontend (Next.js)                     │
├───────────────────────────────────────────────────────────┤
│   Components:                                             │
│   ├── PWADashboard                                       │
│   ├── ManifestStatusCard                                 │
│   ├── IconValidationTable                                │
│   ├── PWAScoreGauge                                      │
│   ├── PWARecommendations                                 │
│   └── PWAGuidePage                                       │
└───────────────────────────────────────────────────────────┘
                            │
                            ▼
┌───────────────────────────────────────────────────────────┐
│                   Backend API (Laravel)                   │
├───────────────────────────────────────────────────────────┤
│    Controllers:                                           │
│    ├── PWAController                                     │
│    └── PWAGuideController                                │
├───────────────────────────────────────────────────────────┤
│    Services:                                              │
│    ├── ManifestFetcherService                            │
│    ├── ManifestValidatorService                          │
│    ├── IconValidatorService                              │
│    ├── PWAScorerService                                  │
│    └── PWASuggestionService                              │
└───────────────────────────────────────────────────────────┘
                            │
                            ▼
┌───────────────────────────────────────────────────────────┐
│                    Database (MySQL)                       │
├───────────────────────────────────────────────────────────┤
│   Tables:                                                 │
│   └── pwa_analyses (cached analysis results)             │
└───────────────────────────────────────────────────────────┘
                            │
                            ▼
┌───────────────────────────────────────────────────────────┐
│               External Resources (FREE)                   │
├───────────────────────────────────────────────────────────┤
│   - HTTP fetch of manifest.json from target URL          │
│   - Icon URL validation (HEAD requests)                  │
│   - No external APIs required                            │
└───────────────────────────────────────────────────────────┘
```

---

## PWA Manifest Validation Criteria

### Core Manifest Fields (Required for PWA)

| Field | Required | Description |
|-------|----------|-------------|
| `name` | Yes | Full name of the app |
| `short_name` | Yes | Short name for home screen |
| `start_url` | Yes | Entry point URL |
| `display` | Yes | Display mode: `standalone`, `fullscreen`, `minimal-ui`, `browser` |
| `icons` | Yes | Array of icon objects |
| `background_color` | Recommended | Splash screen background |
| `theme_color` | Recommended | Browser UI color |
| `description` | Recommended | App description |
| `categories` | Optional | App store categories |
| `screenshots` | Optional | App store screenshots |

### Icon Requirements

| Size | Purpose | Required |
|------|---------|----------|
| 192x192 | Android home screen | Yes |
| 512x512 | Android splash screen | Yes |
| 180x180 | iOS (apple-touch-icon) | Recommended |
| 96x96 | Small favicon | Recommended |
| Any maskable | Adaptive icons (Android) | Recommended |

### Icon Object Structure

```json
{
  "src": "/icons/icon-192.png",
  "sizes": "192x192",
  "type": "image/png",
  "purpose": "any maskable"
}
```

---

## Database Schema

### Table: pwa_analyses

```sql
CREATE TABLE pwa_analyses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    has_manifest TINYINT(1) DEFAULT 0,
    manifest_url VARCHAR(500) NULL,
    manifest_valid TINYINT(1) DEFAULT 0,
    manifest_json JSON NULL,
    
    -- Field presence flags
    has_name TINYINT(1) DEFAULT 0,
    has_short_name TINYINT(1) DEFAULT 0,
    has_start_url TINYINT(1) DEFAULT 0,
    has_display TINYINT(1) DEFAULT 0,
    has_background_color TINYINT(1) DEFAULT 0,
    has_theme_color TINYINT(1) DEFAULT 0,
    has_description TINYINT(1) DEFAULT 0,
    
    -- Icon validation
    icons_total INT UNSIGNED DEFAULT 0,
    icons_valid INT UNSIGNED DEFAULT 0,
    icons_192_present TINYINT(1) DEFAULT 0,
    icons_512_present TINYINT(1) DEFAULT 0,
    icons_180_present TINYINT(1) DEFAULT 0,
    icon_errors JSON NULL,
    
    -- Overall score
    pwa_score TINYINT UNSIGNED DEFAULT 0, -- 0-100
    readiness_level ENUM('none', 'basic', 'enhanced', 'full') DEFAULT 'none',
    
    -- Metadata
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site_analyzed (site_id, analyzed_at),
    INDEX idx_pwa_score (pwa_score)
);
```

---

## API Endpoints

### Get PWA Analysis
`GET /api/v1/sites/{siteId}/pwa`

**Response:**
```json
{
  "success": true,
  "data": {
    "hasManifest": true,
    "manifestUrl": "/manifest.json",
    "manifestValid": true,
    "pwaScore": 85,
    "readinessLevel": "enhanced",
    "fields": {
      "name": { "present": true, "value": "SEO Indicator" },
      "short_name": { "present": true, "value": "SEOIndicator" },
      "start_url": { "present": true, "value": "/" },
      "display": { "present": true, "value": "standalone" },
      "background_color": { "present": true, "value": "#ffffff" },
      "theme_color": { "present": true, "value": "#2563eb" },
      "description": { "present": false, "value": null }
    },
    "icons": {
      "total": 4,
      "valid": 3,
      "required": {
        "192x192": { "present": true, "url": "/icons/icon-192.png", "valid": true },
        "512x512": { "present": true, "url": "/icons/icon-512.png", "valid": true },
        "180x180": { "present": false, "url": null, "valid": false }
      },
      "errors": [
        { "size": "180x180", "error": "Icon not found", "severity": "warning" }
      ]
    },
    "recommendations": [
      {
        "priority": "medium",
        "field": "description",
        "message": "Add a description to improve app store presence",
        "guide": "#manifest-description"
      },
      {
        "priority": "low",
        "field": "icon_180",
        "message": "Add a 180x180 icon for iOS devices",
        "guide": "#ios-icons"
      }
    ],
    "analyzedAt": "2026-02-28T11:00:00Z"
  }
}
```

### Trigger PWA Analysis
`POST /api/v1/sites/{siteId}/pwa/analyze`

**Response (202):**
```json
{
  "jobId": "pwa-analysis-abc123",
  "status": "queued"
}
```

### Get Guide Page
`GET /api/v1/pwa/guide`

**Response:**
```json
{
  "content": "# PWA Implementation Guide\n\n...",
  "sections": [
    { "id": "manifest-basics", "title": "Manifest Basics" },
    { "id": "icon-requirements", "title": "Icon Requirements" },
    { "id": "ios-support", "title": "iOS Support" },
    { "id": "service-worker", "title": "Service Worker" }
  ]
}
```

---

## Scoring Algorithm

### PWA Score Calculation (0-100)

```php
class PWAScorerService
{
    public function calculateScore(PWAAnalysis $analysis): int
    {
        $score = 0;
        
        // Manifest presence (20 points)
        if ($analysis->has_manifest) {
            $score += 10;
            if ($analysis->manifest_valid) {
                $score += 10;
            }
        }
        
        // Required fields (30 points)
        $requiredFields = ['name', 'short_name', 'start_url', 'display'];
        $presentRequired = 0;
        foreach ($requiredFields as $field) {
            if ($analysis->{"has_$field"}) $presentRequired++;
        }
        $score += ($presentRequired / count($requiredFields)) * 15;
        
        // Recommended fields (15 points)
        $recommendedFields = ['background_color', 'theme_color', 'description'];
        $presentRecommended = 0;
        foreach ($recommendedFields as $field) {
            if ($analysis->{"has_$field"}) $presentRecommended++;
        }
        $score += ($presentRecommended / count($recommendedFields)) * 15;
        
        // Required icons (25 points)
        if ($analysis->icons_192_present) $score += 10;
        if ($analysis->icons_512_present) $score += 10;
        if ($analysis->icons_180_present) $score += 5;
        
        // Icon validity bonus (10 points)
        if ($analysis->icons_total > 0) {
            $iconValidityRatio = $analysis->icons_valid / $analysis->icons_total;
            $score += $iconValidityRatio * 10;
        }
        
        return min(100, (int) round($score));
    }
    
    public function determineReadinessLevel(int $score): string
    {
        if ($score >= 90) return 'full';
        if ($score >= 70) return 'enhanced';
        if ($score >= 40) return 'basic';
        return 'none';
    }
}
```

### Readiness Levels

| Level | Score Range | Description |
|-------|-------------|-------------|
| `none` | 0-39 | No manifest or critically incomplete |
| `basic` | 40-69 | Manifest present with basic fields |
| `enhanced` | 70-89 | Most fields and icons present |
| `full` | 90-100 | Complete PWA with all recommended elements |

---

## Validation Services

### ManifestFetcherService

```php
class ManifestFetcherService
{
    /**
     * Fetch and parse manifest.json from URL
     */
    public function fetch(string $siteUrl): ?array
    {
        // 1. Fetch HTML from site URL
        // 2. Parse <link rel="manifest"> href
        // 3. Fetch manifest.json content
        // 4. Parse JSON
        // 5. Return array or null
    }
    
    /**
     * Extract manifest URL from HTML
     */
    public function extractManifestUrl(string $html): ?string
    {
        $crawler = new Crawler($html);
        $link = $crawler->filter('link[rel="manifest"]')->first();
        return $link ? $link->attr('href') : null;
    }
}
```

### ManifestValidatorService

```php
class ManifestValidatorService
{
    /**
     * Validate manifest structure and fields
     */
    public function validate(array $manifest): ManifestValidationResult
    {
        $errors = [];
        
        // Check required fields
        foreach (['name', 'short_name', 'start_url', 'display'] as $field) {
            if (empty($manifest[$field])) {
                $errors[] = new ManifestError($field, 'required', 'Field is required');
            }
        }
        
        // Validate display enum
        if (!in_array($manifest['display'] ?? '', ['standalone', 'fullscreen', 'minimal-ui', 'browser'])) {
            $errors[] = new ManifestError('display', 'invalid', 'Invalid display mode');
        }
        
        // Validate icons array structure
        if (isset($manifest['icons']) && !is_array($manifest['icons'])) {
            $errors[] = new ManifestError('icons', 'invalid', 'Icons must be an array');
        }
        
        return new ManifestValidationResult(
            valid: empty($errors),
            errors: $errors
        );
    }
}
```

### IconValidatorService

```php
class IconValidatorService
{
    /**
     * Validate icon URLs are accessible and correct size
     */
    public function validateIcons(array $icons, string $baseUrl): IconValidationResult
    {
        $results = [];
        
        foreach ($icons as $icon) {
            $url = $this->resolveUrl($icon['src'] ?? '', $baseUrl);
            
            // Check if URL is accessible
            $response = Http::timeout(10)->head($url);
            $accessible = $response->successful();
            
            // Parse expected size
            $size = $this->parseSize($icon['sizes'] ?? '');
            
            $results[] = new IconResult(
                url: $url,
                size: $size,
                accessible: $accessible,
                type: $icon['type'] ?? null,
                purpose: $icon['purpose'] ?? 'any'
            );
        }
        
        return new IconValidationResult(
            total: count($icons),
            valid: count(array_filter($results, fn($r) => $r->accessible)),
            icons: $results
        );
    }
}
```

---

## Integration with Visibility Scores (FEAT-010)

The PWA indicator contributes to the overall visibility score:

```php
// In VisibilityScoreService
protected function calculatePWAComponent(Project $project): float
{
    $pwaScore = $project->pwaAnalysis?->pwa_score ?? 0;
    
    // Weight: 10% of overall visibility score
    // PWA readiness improves mobile UX and SEO signals
    return ($pwaScore / 100) * 10;
}
```

---

## Suggestions Engine

### PWA Improvement Suggestions

| Trigger | Suggestion | Priority |
|---------|------------|----------|
| No manifest | "Add a web app manifest to enable PWA features" | High |
| Missing name | "Add 'name' field to your manifest" | High |
| Missing short_name | "Add 'short_name' for home screen display" | High |
| Missing start_url | "Add 'start_url' to define app entry point" | High |
| Missing display | "Set 'display' mode (standalone recommended)" | High |
| No 192x192 icon | "Add a 192x192 icon for Android devices" | High |
| No 512x512 icon | "Add a 512x512 icon for splash screens" | High |
| No 180x180 icon | "Add a 180x180 icon for iOS devices" | Medium |
| No theme_color | "Add 'theme_color' for browser UI theming" | Medium |
| No background_color | "Add 'background_color' for splash screen" | Medium |
| No description | "Add 'description' for app store presence" | Low |
| Broken icon URL | "Fix broken icon URL: {url}" | High |

---

## Security Considerations

| Concern | Mitigation |
|---------|------------|
| **SSRF Prevention** | Reuse URL validation from TASK-16-1, block private IPs |
| **Malicious Manifests** | Validate JSON structure, limit size (10KB max) |
| **Icon URL Injection** | Sanitize URLs, validate against base domain |
| **Request Timeouts** | 30s total timeout, 10s per icon check |
| **Rate Limiting** | Max 10 analyses per site per hour |

---

## Tests

### Unit Tests
- Manifest URL extraction from HTML
- Manifest JSON validation
- Icon size parsing
- PWA score calculation
- Readiness level determination

### Integration Tests
- Full manifest fetch and parse
- Icon URL validation
- Visibility score integration

### Feature Tests
- API endpoint responses
- Authorization (user owns site)
- Error handling for invalid URLs

---

## Rollout Plan

### Phase 1: Core Infrastructure
1. Database migration (pwa_analyses table)
2. PWAAnalysis model and repository
3. ManifestFetcherService

### Phase 2: Validation
4. ManifestValidatorService
5. IconValidatorService
6. PWAScorerService

### Phase 3: API & Frontend
7. PWA analysis API endpoints
8. PWA dashboard components
9. Score gauge and status cards
10. Recommendations component

### Phase 4: Integration & Documentation
11. Visibility score integration
12. Guide page creation
13. Unit and feature tests

---

## Dependencies

### Existing
- TASK-16-1: Tools infrastructure (URL validation, SSRF protection)
- FEAT-010: Visibility Scores (integration target)
- Laravel HTTP Client

### External
- None (no external APIs required)

---

## Risk Assessment

| Risk | Level | Mitigation |
|------|-------|------------|
| **Invalid manifest URLs** | Low | Graceful error handling, suggestions provided |
| **Large manifests** | Low | Size limit (10KB), streaming parser if needed |
| **Slow icon validation** | Medium | Concurrent validation, timeout limits |
| **Broken external icons** | Low | Mark as invalid, provide specific suggestions |

---

## Comments
_Agent communication log_

### [2026-02-28 11:00 GMT+2] Agent: Architect
Created specification SPEC-024 for PWA / Web App Manifest Indicator (FEAT-024).

**Key Architectural Decisions:**
1. **FREE Implementation**: No external APIs required - all validation done via HTTP fetch and JSON parsing.
2. **Comprehensive Validation**: Validates manifest presence, required fields, recommended fields, and icon accessibility.
3. **Scoring System**: 0-100 score with four readiness levels (none, basic, enhanced, full).
4. **Visibility Score Integration**: PWA contributes 10% to overall visibility score.
5. **Reuses Infrastructure**: Leverages URL validation and SSRF protection from TASK-16-1.

**Risk Confirmed**: Medium risk due to:
- External URL fetching (SSRF concerns mitigated by existing protection)
- Icon validation latency (mitigated by concurrent checks and timeouts)

Ready for SpecSystemAnalyst to break into tasks.

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Broke down SPEC-024 into 14 tasks following the rollout plan:

**Phase 1 - Core Infrastructure (3 tasks):**
- TASK-24-1: Database Migration (pwa_analyses table) - High priority
- TASK-24-2: PWAAnalysis Model and Repository - High priority
- TASK-24-3: ManifestFetcherService - High priority, Medium risk

**Phase 2 - Validation (4 tasks):**
- TASK-24-4: ManifestValidatorService - High priority
- TASK-24-5: IconValidatorService - High priority, Medium risk
- TASK-24-6: PWAScorerService - High priority
- TASK-24-7: PWASuggestionService - Medium priority

**Phase 3 - API (2 tasks):**
- TASK-24-8: PWA Analysis API Endpoints - High priority, Medium risk
- TASK-24-9: PWA Guide API Endpoint - Medium priority

**Phase 4 - Frontend (3 tasks):**
- TASK-24-10: PWA Dashboard Components - Medium priority
- TASK-24-11: PWA Score Gauge and Icon Validation Table - Medium priority
- TASK-24-12: PWA Recommendations Component - Medium priority

**Phase 5 - Integration (2 tasks):**
- TASK-24-13: Visibility Score Integration - High priority, Medium risk
- TASK-24-14: PWA Guide Page (Frontend) - Low priority

**Key Decisions:**
- Reused SSRF protection from TASK-16-1 for security
- Icons validated concurrently (up to 5 simultaneous) for performance
- Guide content separated into standalone API for reusability
- PWA contributes 10% to visibility score as per architecture

All tasks ready for ProjectManager prioritization.

---

*Created by Architect from FEAT-024*
