# TESTS/SPEC-7-6.md — Unit Tests for Local Pack Detection

## Task Reference
- Task: [TASK-7-6](../TASKS/TASK-7-6.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Low
- Priority: Medium

---

## Overview

Comprehensive unit tests for the `detectLocalPack` method in the SERP Feature Detector. Local pack appears for queries with local intent and shows 3 businesses on a map. Tracking this helps users understand local SEO performance. Tests verify local pack detection, position tracking, and edge case handling.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/unit/serp-feature-detectors/local-pack.test.js` | Local pack detector unit tests |

---

## Test Scenarios

### 1. Basic Detection Logic

#### No Local Pack Present

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| B-001 | No local pack in SERP | `{}` | `{ type: 'local_pack', isPresent: false }` |
| B-002 | Undefined local pack | `{ localPack: undefined }` | `{ type: 'local_pack', isPresent: false }` |
| B-003 | Null local pack | `{ localPack: null }` | `{ type: 'local_pack', isPresent: false }` |
| B-004 | Empty local pack array | `{ localPack: [] }` | `{ type: 'local_pack', isPresent: false }` |

#### Local Pack Present - Our URL

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| B-005 | Our URL in position 1 | `localPack: [{ url: 'https://ours.com' }]`, target: `ours.com` | `{ isPresent: true, position: 1 }` |
| B-006 | Our URL in position 2 | `localPack: [{}, { url: 'https://ours.com' }]`, target: `ours.com` | `{ isPresent: true, position: 2 }` |
| B-007 | Our URL in position 3 | `localPack: [{}, {}, { url: 'https://ours.com' }]`, target: `ours.com` | `{ isPresent: true, position: 3 }` |
| B-008 | Our URL with www prefix | `localPack: [{ url: 'https://www.ours.com' }]`, target: `ours.com` | `{ isPresent: true, position: 1 }` |
| B-009 | Our URL with path | `localPack: [{ url: 'https://ours.com/contact' }]`, target: `ours.com` | `{ isPresent: true, position: 1 }` |
| B-010 | Our URL with query params | `localPack: [{ url: 'https://ours.com?loc=nyc' }]`, target: `ours.com` | `{ isPresent: true, position: 1 }` |

#### Local Pack Present - Competitor URL

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| B-011 | All 3 are competitors | `localPack: [{ url: 'a.com' }, { url: 'b.com' }, { url: 'c.com' }]`, target: `ours.com` | `{ isPresent: false }` |
| B-012 | Similar domain (not match) | `localPack: [{ url: 'https://ours-compare.com' }]`, target: `ours.com` | `{ isPresent: false }` |
| B-013 | Subdomain of competitor | `localPack: [{ url: 'https://blog.theirs.com' }]`, target: `ours.com` | `{ isPresent: false }` |

---

### 2. Position Tracking

#### Position Within Local Pack

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| P-001 | Position 1 returned correctly | Our URL at index 0 | `position: 1` |
| P-002 | Position 2 returned correctly | Our URL at index 1 | `position: 2` |
| P-003 | Position 3 returned correctly | Our URL at index 2 | `position: 3` |
| P-004 | No position when not present | Our URL not in local pack | `position: undefined` |
| P-005 | Position is 1-indexed | Array index 0 | Returns position: 1 (not 0) |

#### Position with Mixed Results

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| P-006 | Our URL first of three | `[{ url: 'ours' }, { url: 'a' }, { url: 'b' }]` | `{ position: 1 }` |
| P-007 | Our URL second of three | `[{ url: 'a' }, { url: 'ours' }, { url: 'b' }]` | `{ position: 2 }` |
| P-008 | Our URL last of three | `[{ url: 'a' }, { url: 'b' }, { url: 'ours' }]` | `{ position: 3 }` |

---

### 3. URL Matching Logic

#### Hostname Extraction

| ID | Scenario | Input Target URL | Expected Hostname |
|----|----------|------------------|-------------------|
| U-001 | Standard HTTPS | `https://example.com` | `example.com` |
| U-002 | Standard HTTP | `http://example.com` | `example.com` |
| U-003 | With www prefix | `https://www.example.com` | `www.example.com` |
| U-004 | With subdomain | `https://blog.example.com` | `blog.example.com` |
| U-005 | With path | `https://example.com/page` | `example.com` |
| U-006 | With query params | `https://example.com?a=1` | `example.com` |
| U-007 | With port | `https://example.com:443` | `example.com` |

#### Domain Matching in Local Pack

| ID | Scenario | Target | Local Pack URL | Expected |
|----|----------|--------|----------------|----------|
| U-008 | Exact match | `example.com` | `https://example.com` | `true` |
| U-009 | Target with www, entry without | `www.example.com` | `https://example.com` | `true` (normalized) |
| U-010 | Target without www, entry with | `example.com` | `https://www.example.com` | `true` (normalized) |
| U-011 | Case insensitive hostname | `Example.COM` | `https://example.com` | `true` |
| U-012 | Protocol difference | `http://example.com` | `https://example.com` | `true` |
| U-013 | Different paths same domain | `example.com/page1` | `example.com/contact` | `true` (same domain) |
| U-014 | Subdomain matches main domain | `example.com` | `blog.example.com` | `false` (not same) |
| U-015 | Main domain matches subdomain | `blog.example.com` | `example.com` | `false` (not same) |

---

### 4. Partial Local Pack Data

#### Fewer Than 3 Results

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PL-001 | Single result, ours | `localPack: [{ url: 'https://ours.com' }]` | `{ isPresent: true, position: 1 }` |
| PL-002 | Single result, competitor | `localPack: [{ url: 'https://theirs.com' }]` | `{ isPresent: false }` |
| PL-003 | Two results, ours first | `localPack: [{ url: 'ours' }, { url: 'a' }]` | `{ isPresent: true, position: 1 }` |
| PL-004 | Two results, ours second | `localPack: [{ url: 'a' }, { url: 'ours' }]` | `{ isPresent: true, position: 2 }` |
| PL-005 | Two results, neither ours | `localPack: [{ url: 'a' }, { url: 'b' }]` | `{ isPresent: false }` |

#### Missing URL in Entry

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PL-006 | Entry without URL | `localPack: [{ name: 'Business' }]` | Skipped in matching |
| PL-007 | Entry with null URL | `localPack: [{ url: null }]` | Skipped in matching |
| PL-008 | Entry with empty URL | `localPack: [{ url: '' }]` | Skipped in matching |
| PL-009 | Mixed entries (some without URL) | `[{ name: 'A' }, { url: 'ours' }]` | Matches at position 2 |

---

### 5. Local Pack Entry Structure

#### Entry Fields

| ID | Scenario | Input Entry | Expected Behavior |
|----|----------|-------------|-------------------|
| E-001 | Entry with name and URL | `{ name: 'Business', url: 'https://ours.com' }` | URL used for matching |
| E-002 | Entry with rating | `{ url: 'ours', rating: 4.5 }` | URL used, rating ignored |
| E-003 | Entry with address | `{ url: 'ours', address: '123 St' }` | URL used, address ignored |
| E-004 | Entry with phone | `{ url: 'ours', phone: '555-1234' }` | URL used, phone ignored |
| E-005 | Entry with all fields | Full entry object | URL used for matching |

---

### 6. Return Value Structure

#### Required Fields

| ID | Scenario | Expected Fields |
|----|----------|-----------------|
| R-001 | Always returns type | `{ type: 'local_pack', ... }` |
| R-002 | Always returns isPresent | `{ ..., isPresent: boolean }` |

#### Optional Position Field

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| R-003 | Position when present | Our URL in local pack | `position: 1 or 2 or 3` |
| R-004 | No position when absent | Our URL not in local pack | `position: undefined` |
| R-005 | Position is integer | Any valid position | `typeof position === 'number'` |

---

### 7. Edge Cases and Error Handling

#### Malformed SERP Data

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| EC-001 | Missing SERP result | `null` | Returns `{ type: 'local_pack', isPresent: false }` |
| EC-002 | SERP is not object | `"string"` | Returns `{ type: 'local_pack', isPresent: false }` |
| EC-003 | localPack is string | `{ localPack: "invalid" }` | Returns `{ type: 'local_pack', isPresent: false }` |
| EC-004 | localPack is number | `{ localPack: 123 }` | Returns `{ type: 'local_pack', isPresent: false }` |
| EC-005 | localPack is object (not array) | `{ localPack: {} }` | Returns `{ type: 'local_pack', isPresent: false }` |

#### Malformed URLs

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| EC-006 | Invalid URL in local pack | `{ url: 'not-a-url' }` | Gracefully handles, skipped |
| EC-007 | Relative URL in local pack | `{ url: '/page' }` | Cannot extract domain, skipped |
| EC-008 | Empty URL string | `{ url: '' }` | Skipped in matching |
| EC-009 | Invalid target URL | Valid local pack, target: `not-a-url` | Gracefully handles, returns isPresent: false |
| EC-010 | Empty target URL | Valid local pack, target: `''` | Returns isPresent: false |

#### Unicode and International Domains

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| EC-011 | Unicode URL | `{ url: 'https://例え.jp' }` | Correctly parses hostname |
| EC-012 | Punycode URL | `{ url: 'https://xn--r8jz45g.jp' }` | Correctly parses hostname |
| EC-013 | Unicode target matches Unicode entry | Target: `例え.jp`, Entry: `例え.jp` | `isPresent: true` |
| EC-014 | Business name with emoji | `{ name: '🍕 Pizza Place', url: 'ours.com' }` | URL matched correctly |

---

### 8. Integration with SERPFeatureDetector

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| I-001 | Called via detectFeatures | Returns SERPFeature object |
| I-002 | Feature type is correct | `type: 'local_pack'` |
| I-003 | Compatible with feature array | Can be combined with other features |
| I-004 | Works alongside featured snippet | Both detected independently |

---

### 9. Adversarial Tests

#### Security

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| S-001 | XSS in name | `{ name: '<script>alert(1)</script>' }` | Stored as-is (sanitization at display) |
| S-002 | XSS in URL | `{ url: "javascript:alert(1)" }` | Treated as URL string, no execution |
| S-003 | SQL injection in URL | `{ url: "https://example.com'; DROP TABLE--" }` | Treated as URL string, no execution |

#### Injection Attempts

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| S-004 | Prototype pollution in entry | `{ __proto__: { polluted: true } }` | Ignored, no pollution |
| S-005 | Prototype pollution in SERP | `{ __proto__: { polluted: true } }` | Ignored, no pollution |
| S-006 | Very long URL (buffer overflow) | 10000+ char URL | Handled without error |

---

## Mock Data

### Full SERPResult with Local Pack (Our URL at Position 1)
```javascript
{
  keyword: 'pizza near me',
  targetUrl: 'https://mypizza.com',
  localPack: [
    {
      name: 'My Pizza Place',
      url: 'https://mypizza.com',
      rating: 4.8,
      reviewCount: 250,
      address: '123 Main St, New York, NY',
      phone: '(555) 123-4567'
    },
    {
      name: 'Other Pizza',
      url: 'https://otherpizza.com',
      rating: 4.5,
      reviewCount: 180
    },
    {
      name: 'Third Pizza',
      url: 'https://thirdpizza.com',
      rating: 4.3,
      reviewCount: 120
    }
  ]
}
```

### Expected Output (Our URL at Position 1)
```javascript
{
  type: 'local_pack',
  isPresent: true,
  position: 1
}
```

### Full SERPResult with Local Pack (Our URL at Position 2)
```javascript
{
  keyword: 'best coffee shop',
  targetUrl: 'https://mycafe.com',
  localPack: [
    {
      name: 'Starbucks',
      url: 'https://starbucks.com',
      rating: 4.2
    },
    {
      name: 'My Cafe',
      url: 'https://mycafe.com',
      rating: 4.7
    },
    {
      name: 'Another Cafe',
      url: 'https://anothercafe.com',
      rating: 4.4
    }
  ]
}
```

### Expected Output (Our URL at Position 2)
```javascript
{
  type: 'local_pack',
  isPresent: true,
  position: 2
}
```

### SERPResult Without Local Pack
```javascript
{
  keyword: 'seo tools',
  targetUrl: 'https://example.com',
  // No localPack property
  results: [
    { position: 1, url: 'https://competitor.com', title: '...' },
    { position: 2, url: 'https://example.com', title: '...' }
  ]
}
```

### Expected Output (No Local Pack)
```javascript
{
  type: 'local_pack',
  isPresent: false
}
```

---

## Coverage Targets

| Component | Target Coverage |
|-----------|-----------------|
| detectLocalPack method | 100% |
| URL hostname extraction | 100% |
| Position tracking | 100% |
| Error handling paths | 100% |
| Partial data handling | 100% |

---

## Test Execution

```bash
# Run local pack detector tests
npm test -- tests/unit/serp-feature-detectors/local-pack.test.js

# Run with coverage
npm run test:coverage -- --filter=local-pack

# Run all SERP feature tests
npm test -- tests/unit/serp-feature-detectors/
```

---

## Dependencies

- TASK-7-2: SERP Feature Types and Models (must be implemented)
- TASK-7-4: SERP Feature Detector Service (parent detector class)
- `backend/src/models/serp-feature.ts` - Type definitions
- `backend/src/services/serp-feature-detector.ts` - Detector class

---

## Notes for Developer

### Method Signature
```typescript
private detectLocalPack(serp: SERPResult, targetUrl: string): SERPFeature {
  const localPack = serp.localPack;
  if (!localPack || !Array.isArray(localPack) || localPack.length === 0) {
    return { type: 'local_pack', isPresent: false };
  }
  
  const targetDomain = this.extractDomain(targetUrl);
  const position = localPack.findIndex(item => {
    if (!item?.url) return false;
    const entryDomain = this.extractDomain(item.url);
    return this.domainsMatch(targetDomain, entryDomain);
  });
  
  return {
    type: 'local_pack',
    isPresent: position >= 0,
    position: position >= 0 ? position + 1 : undefined
  };
}
```

### Implementation Notes

1. **Array Validation:**
   - Check `Array.isArray()` before iterating
   - Handle empty arrays gracefully
   - Handle entries without URLs

2. **URL Matching:**
   - Use shared `extractDomain()` helper from detector class
   - Compare normalized hostnames
   - Handle www prefix normalization
   - Case-insensitive comparison

3. **Position Tracking:**
   - Return 1-indexed position (not 0-indexed)
   - Only include position when `isPresent: true`
   - Position is undefined when not present

4. **Error Handling:**
   - Invalid URLs should not throw - skip entry
   - Missing data should not throw - return defaults
   - Array index access should check bounds

5. **Integration:**
   - This method is called from `SERPFeatureDetector.detectFeatures()`
   - Should not have side effects
   - Should be deterministic (same input = same output)

---

## Comments

### [2026-02-26 18:15] Agent: PreTester
Created test documentation for TASK-7-6 Local Pack Detection.

Test coverage includes:
- Basic detection logic: 13 scenarios
- Position tracking: 8 scenarios
- URL matching logic: 15 scenarios
- Partial local pack data: 9 scenarios
- Local pack entry structure: 5 scenarios
- Return value structure: 5 scenarios
- Edge cases and error handling: 14 scenarios
- Integration tests: 4 scenarios
- Adversarial tests: 6 scenarios

Total: 79 test scenarios covering all acceptance criteria.

Key implementation notes:
1. Validate that localPack is an array before iterating
2. Handle entries without URLs gracefully (skip them)
3. Position is 1-indexed (not 0-indexed from array)
4. Use shared URL normalization from parent detector
5. Position only included when isPresent is true

Ready for Developer implementation.
