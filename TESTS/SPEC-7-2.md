# TESTS/SPEC-7-2.md — Unit Tests for SERP Feature Model and Types

## Task Reference
- Task: [TASK-7-2](../TASKS/TASK-7-2.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Low
- Priority: High

---

## Overview

Comprehensive unit tests for SERP feature TypeScript types, interfaces, and model class. Tests verify type definitions, interface contracts, constant values, and serialization/deserialization methods.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/unit/serp-feature-types.test.js` | Type definition validation |
| `tests/unit/serp-feature-model.test.js` | Model class serialization tests |

---

## Test Scenarios

### 1. SERPFeatureType Type Definition

#### Type Values

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| T-001 | Valid type: featured_snippet | 'featured_snippet' | Valid SERPFeatureType |
| T-002 | Valid type: local_pack | 'local_pack' | Valid SERPFeatureType |
| T-003 | Valid type: image_pack | 'image_pack' | Valid SERPFeatureType |
| T-004 | Valid type: video | 'video' | Valid SERPFeatureType |
| T-005 | Valid type: people_also_ask | 'people_also_ask' | Valid SERPFeatureType |
| T-006 | Valid type: knowledge_panel | 'knowledge_panel' | Valid SERPFeatureType |
| T-007 | Valid type: sitelinks | 'sitelinks' | Valid SERPFeatureType |
| T-008 | Valid type: reviews | 'reviews' | Valid SERPFeatureType |
| T-009 | Valid type: faq | 'faq' | Valid SERPFeatureType |
| T-010 | Valid type: product | 'product' | Valid SERPFeatureType |
| T-011 | Invalid type rejected | 'invalid_type' | Not in valid types |
| T-012 | Empty string rejected | '' | Not in valid types |
| T-013 | Null rejected | null | Not in valid types |
| T-014 | Undefined rejected | undefined | Not in valid types |
| T-015 | Case sensitive check | 'FEATURED_SNIPPET' | Not in valid types |

---

### 2. SERPFeature Interface

#### Required Fields

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| I-001 | Minimal valid interface | { type, isPresent } | Valid SERPFeature |
| I-002 | All fields populated | { type, isPresent, position, url, title, snippet } | Valid SERPFeature |
| I-003 | Missing type | { isPresent: true } | Invalid - type required |
| I-004 | Missing isPresent | { type: 'featured_snippet' } | Invalid - isPresent required |
| I-005 | Invalid type value | { type: 'invalid', isPresent: true } | Invalid - type must be SERPFeatureType |

#### Optional Fields

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| I-006 | position optional | { type, isPresent } | Valid without position |
| I-007 | url optional | { type, isPresent } | Valid without url |
| I-008 | title optional | { type, isPresent } | Valid without title |
| I-009 | snippet optional | { type, isPresent } | Valid without snippet |
| I-010 | position must be number | { type, isPresent, position: '1' } | Invalid if not number |
| I-011 | url must be string | { type, isPresent, url: 123 } | Invalid if not string |
| I-012 | title must be string | { type, isPresent, title: {} } | Invalid if not string |
| I-013 | snippet must be string | { type, isPresent, snippet: [] } | Invalid if not string |

#### Boolean isPresent

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| I-014 | isPresent true | { type, isPresent: true } | Valid |
| I-015 | isPresent false | { type, isPresent: false } | Valid |
| I-016 | isPresent must be boolean | { type, isPresent: 'true' } | Invalid |
| I-017 | isPresent must be boolean | { type, isPresent: 1 } | Invalid |

---

### 3. SERPFeatureDetection Interface

#### Required Fields

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| D-001 | Valid detection object | { keywordId, rankingId, features, detectedAt } | Valid |
| D-002 | Missing keywordId | { rankingId, features, detectedAt } | Invalid |
| D-003 | Missing rankingId | { keywordId, features, detectedAt } | Invalid |
| D-004 | Missing features | { keywordId, rankingId, detectedAt } | Invalid |
| D-005 | Missing detectedAt | { keywordId, rankingId, features } | Invalid |

#### Field Types

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| D-006 | keywordId must be number | keywordId: '1' | Invalid |
| D-007 | rankingId must be number | rankingId: '1' | Invalid |
| D-008 | features must be array | features: {} | Invalid |
| D-009 | features must contain SERPFeature | features: [{}] | Invalid if missing required |
| D-010 | detectedAt must be Date | detectedAt: '2026-01-01' | Must be Date object |
| D-011 | Empty features array | features: [] | Valid (no features detected) |

---

### 4. SERP_FEATURE_META Constant

#### Structure Validation

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| M-001 | Has all 10 feature types | Keys match SERPFeatureType values |
| M-002 | Each entry has name | typeof meta.name === 'string' |
| M-003 | Each entry has icon | typeof meta.icon === 'string' |
| M-004 | Each entry has description | typeof meta.description === 'string' |
| M-005 | Each entry has ctrImpact | typeof meta.ctrImpact === 'string' |

#### Content Validation

| ID | Feature Type | Expected Name |
|----|--------------|---------------|
| M-006 | featured_snippet | 'Featured Snippet' |
| M-007 | local_pack | 'Local Pack' |
| M-008 | image_pack | 'Image Pack' |
| M-009 | video | 'Video Result' |
| M-010 | people_also_ask | 'People Also Ask' |
| M-011 | knowledge_panel | 'Knowledge Panel' |
| M-012 | sitelinks | 'Sitelinks' |
| M-013 | reviews | 'Reviews/Ratings' |
| M-014 | faq | 'FAQ Rich Result' |
| M-015 | product | 'Product Listing' |

#### Icon Validation

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| M-016 | Icons are emoji strings | 1-2 character emoji strings |
| M-017 | featured_snippet icon | '🏆' |
| M-018 | local_pack icon | '📍' |
| M-019 | reviews icon | '⭐' |

---

### 5. ALL_FEATURE_TYPES Array Constant

#### Array Validation

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| A-001 | Is an array | Array.isArray(ALL_FEATURE_TYPES) |
| A-002 | Has exactly 10 elements | length === 10 |
| A-003 | Contains only valid types | All elements are SERPFeatureType |
| A-004 | No duplicate values | Unique values only |
| A-005 | Frozen/immutable | Object.isFrozen() or readonly |

#### Content Validation

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| A-006 | Contains featured_snippet | Includes 'featured_snippet' |
| A-007 | Contains local_pack | Includes 'local_pack' |
| A-008 | Contains image_pack | Includes 'image_pack' |
| A-009 | Contains video | Includes 'video' |
| A-010 | Contains people_also_ask | Includes 'people_also_ask' |
| A-011 | Contains knowledge_panel | Includes 'knowledge_panel' |
| A-012 | Contains sitelinks | Includes 'sitelinks' |
| A-013 | Contains reviews | Includes 'reviews' |
| A-014 | Contains faq | Includes 'faq' |
| A-015 | Contains product | Includes 'product' |

---

### 6. SERPFeature Model Class

#### Constructor

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| C-001 | Create with minimal data | { type, isPresent } | Instance created |
| C-002 | Create with all fields | { type, isPresent, position, url, title, snippet } | Instance with all fields |
| C-003 | Invalid type throws | { type: 'invalid', isPresent: true } | Throws ValidationError |
| C-004 | Invalid isPresent throws | { type: 'featured_snippet', isPresent: 'yes' } | Throws ValidationError |

#### Serialization (toJSON)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| S-001 | Serialize minimal object | { type, isPresent } | JSON with type and isPresent |
| S-002 | Serialize with optional fields | { type, isPresent, url } | JSON includes url |
| S-003 | Omit undefined optional fields | { type, isPresent, url: undefined } | JSON excludes url |
| S-004 | Serialize for database | model.toJSON() | Valid JSON string |

#### Deserialization (fromJSON)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| D-001 | Deserialize valid JSON | '{"type":"featured_snippet","isPresent":true}' | SERPFeature instance |
| D-002 | Deserialize with all fields | Full JSON | Instance with all fields |
| D-003 | Invalid JSON throws | '{"type":"invalid"}' | Throws ParseError |
| D-004 | Malformed JSON throws | 'not json' | Throws ParseError |
| D-005 | Missing required field throws | '{"url":"https://..."}' | Throws ValidationError |

#### Static Methods

| ID | Scenario | Method | Expected Output |
|----|----------|--------|-----------------|
| ST-001 | Validate type | SERPFeature.isValidType('featured_snippet') | true |
| ST-002 | Validate invalid type | SERPFeature.isValidType('invalid') | false |
| ST-003 | Get all types | SERPFeature.getAllTypes() | Returns ALL_FEATURE_TYPES |
| ST-004 | Get meta for type | SERPFeature.getMeta('featured_snippet') | Returns meta object |
| ST-005 | Get meta for invalid | SERPFeature.getMeta('invalid') | Returns null or throws |

---

### 7. Edge Cases and Adversarial Tests

#### Type Safety

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| E-001 | Prototype pollution attempt | { __proto__: {...} } | Ignored or sanitized |
| E-002 | Extra properties allowed | { type, isPresent, extra: 'data' } | Extra props ignored |
| E-003 | Deep nested object | { type, isPresent, nested: {...} } | Shallow copy only |

#### Boundary Values

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| E-004 | Maximum URL length | 2083 char URL | Stored correctly |
| E-005 | Maximum title length | 500 char title | Stored or truncated |
| E-006 | Maximum snippet length | 10000 char snippet | Stored or truncated |
| E-007 | Position boundary | position: 1 | Valid |
| E-008 | Position boundary | position: 100 | Valid |
| E-009 | Position zero | position: 0 | Valid or error (documented) |
| E-010 | Negative position | position: -1 | Invalid |

#### Unicode and Special Characters

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| E-011 | Emoji in title | 'Best 🏆 SEO Tools' | Stored correctly |
| E-012 | Unicode in snippet | Multi-language text | Stored correctly |
| E-013 | HTML in snippet | '<p>text</p>' | Escaped or stored as-is |
| E-014 | Newlines in snippet | 'line1\nline2' | Preserved or normalized |

---

## Mock Data

### Valid SERPFeature Object
```javascript
{
  type: 'featured_snippet',
  isPresent: true,
  position: 1,
  url: 'https://example.com/seo-tools',
  title: 'Best SEO Tools 2026',
  snippet: 'The best SEO tools for 2026 include comprehensive keyword tracking...'
}
```

### Valid SERPFeatureDetection Object
```javascript
{
  keywordId: 1,
  rankingId: 100,
  features: [
    { type: 'featured_snippet', isPresent: true, url: 'https://example.com' },
    { type: 'sitelinks', isPresent: true },
    { type: 'local_pack', isPresent: false }
  ],
  detectedAt: new Date('2026-02-26T10:00:00Z')
}
```

### Expected SERP_FEATURE_META Entry
```javascript
{
  featured_snippet: {
    name: 'Featured Snippet',
    icon: '🏆',
    description: 'Answer box at top of search results',
    ctrImpact: '+30-50%'
  }
}
```

---

## Coverage Targets

| Component | Target Coverage |
|-----------|-----------------|
| SERPFeatureType validation | 100% |
| SERPFeature interface | 100% |
| SERPFeatureDetection interface | 100% |
| SERP_FEATURE_META constant | 100% |
| ALL_FEATURE_TYPES constant | 100% |
| SERPFeature model class | 100% |

---

## Test Execution

```bash
# Run all SERP feature type tests
npm test -- tests/unit/serp-feature-types.test.js

# Run model tests
npm test -- tests/unit/serp-feature-model.test.js

# Run with coverage
npm run test:coverage -- --filter=serp-feature
```

---

## Dependencies

- None (pure TypeScript/JavaScript definitions)

---

## Notes for Developer

1. Types should be defined in `backend/src/models/serp-feature.ts` or `frontend/lib/types/serp-feature.ts`
2. Use TypeScript `type` for unions and `interface` for object shapes
3. Constants should be `const` with `as const` for type inference
4. Model class should handle validation in constructor
5. Serialization should omit undefined optional fields
6. All exports should be named exports (no default export)

---

## Comments

### [2026-02-26 17:30] Agent: PreTester
Created test documentation for TASK-7-2. Test structure:
- Type definition tests: 15 scenarios
- SERPFeature interface tests: 17 scenarios
- SERPFeatureDetection interface tests: 11 scenarios
- SERP_FEATURE_META constant tests: 19 scenarios
- ALL_FEATURE_TYPES constant tests: 15 scenarios
- Model class tests: 17 scenarios
- Edge cases and adversarial tests: 14 scenarios

Total: 108 test scenarios covering all acceptance criteria.

Tests define expected type behavior and validate:
- All 10 feature types (featured_snippet, local_pack, image_pack, video, people_also_ask, knowledge_panel, sitelinks, reviews, faq, product)
- Required vs optional fields
- Serialization/deserialization contract
- Constant immutability
