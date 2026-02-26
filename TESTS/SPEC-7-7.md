# TESTS/SPEC-7-7.md — Unit Tests for Other SERP Feature Detection

## Task Reference
- Task: [TASK-7-7](../TASKS/TASK-7-7.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Low
- Priority: Medium

---

## Overview

Comprehensive unit tests for remaining SERP feature detection methods: image pack, video, people also ask, knowledge panel, sitelinks, reviews, FAQ, and product listings. These are post-MVP features but should be implemented for completeness. Each detector follows a similar pattern but has unique detection logic.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/unit/serp-feature-detectors/image-pack.test.js` | Image pack detector tests |
| `tests/unit/serp-feature-detectors/video.test.js` | Video detector tests |
| `tests/unit/serp-feature-detectors/people-also-ask.test.js` | People Also Ask detector tests |
| `tests/unit/serp-feature-detectors/knowledge-panel.test.js` | Knowledge panel detector tests |
| `tests/unit/serp-feature-detectors/sitelinks.test.js` | Sitelinks detector tests |
| `tests/unit/serp-feature-detectors/reviews.test.js` | Reviews detector tests |
| `tests/unit/serp-feature-detectors/faq.test.js` | FAQ detector tests |
| `tests/unit/serp-feature-detectors/product.test.js` | Product detector tests |

---

## Test Scenarios

---

# 1. Image Pack Detection (`detectImagePack`)

## Basic Detection

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IP-001 | No images in SERP | `{}` | `{ type: 'image_pack', isPresent: false }` |
| IP-002 | Undefined images | `{ images: undefined }` | `{ type: 'image_pack', isPresent: false }` |
| IP-003 | Empty images array | `{ images: [] }` | `{ type: 'image_pack', isPresent: false }` |
| IP-004 | Images exist, our URL | `{ images: [{ url: 'https://ours.com/img.jpg' }] }`, target: `ours.com` | `{ isPresent: true }` |
| IP-005 | Images exist, competitor | `{ images: [{ url: 'https://theirs.com/img.jpg' }] }`, target: `ours.com` | `{ isPresent: false }` |

## URL Matching

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IP-006 | Multiple images, one ours | 3 images, middle one ours | `{ isPresent: true }` |
| IP-007 | Image with our domain in path | `{ url: 'https://cdn.com/ours.com-image.jpg' }` | Depends on implementation (likely false) |
| IP-008 | Image URL contains target domain | `{ url: 'https://ours.com/path/image.jpg' }` | `{ isPresent: true }` |

## Position Tracking

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IP-009 | First image is ours | `[{ url: 'ours.com/1.jpg' }, { url: 'a.com/2.jpg' }]` | `{ isPresent: true, position: 1 }` |
| IP-010 | Second image is ours | `[{ url: 'a.com/1.jpg' }, { url: 'ours.com/2.jpg' }]` | `{ isPresent: true, position: 2 }` |

---

# 2. Video Detection (`detectVideo`)

## Basic Detection

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| V-001 | No videos in SERP | `{}` | `{ type: 'video', isPresent: false }` |
| V-002 | Undefined videos | `{ videos: undefined }` | `{ type: 'video', isPresent: false }` |
| V-003 | Empty videos array | `{ videos: [] }` | `{ type: 'video', isPresent: false }` |
| V-004 | Video exists, our URL | `{ videos: [{ url: 'https://ours.com/video' }] }`, target: `ours.com` | `{ isPresent: true }` |
| V-005 | Video exists, competitor | `{ videos: [{ url: 'https://youtube.com/watch?v=abc' }] }`, target: `ours.com` | `{ isPresent: false }` |

## Video Platforms

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| V-006 | YouTube video | `{ videos: [{ url: 'https://youtube.com/watch?v=abc', platform: 'youtube' }] }` | Detected, platform info included |
| V-007 | Vimeo video | `{ videos: [{ url: 'https://vimeo.com/123', platform: 'vimeo' }] }` | Detected, platform info included |
| V-008 | Self-hosted video | `{ videos: [{ url: 'https://ours.com/video.mp4' }] }` | `{ isPresent: true }` |

## Video Metadata

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| V-009 | Video with title | `{ videos: [{ url: 'ours.com/v', title: 'How to...' }] }` | title included in result |
| V-010 | Video with duration | `{ videos: [{ url: 'ours.com/v', duration: '5:30' }] }` | duration included in result |
| V-011 | Video thumbnail | `{ videos: [{ url: 'ours.com/v', thumbnail: 'thumb.jpg' }] }` | thumbnail included |

---

# 3. People Also Ask Detection (`detectPeopleAlsoAsk`)

## Basic Detection

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PAA-001 | No PAA in SERP | `{}` | `{ type: 'people_also_ask', isPresent: false }` |
| PAA-002 | Undefined PAA | `{ peopleAlsoAsk: undefined }` | `{ type: 'people_also_ask', isPresent: false }` |
| PAA-003 | Empty PAA array | `{ peopleAlsoAsk: [] }` | `{ type: 'people_also_ask', isPresent: false }` |
| PAA-004 | PAA exists, our URL in answer | `{ peopleAlsoAsk: [{ answer: { url: 'https://ours.com' } }] }` | `{ isPresent: true }` |
| PAA-005 | PAA exists, competitor answer | `{ peopleAlsoAsk: [{ answer: { url: 'https://theirs.com' } }] }` | `{ isPresent: false }` |

## PAA Structure

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PAA-006 | Multiple questions, one ours | 3 questions, 2nd has our URL | `{ isPresent: true }` |
| PAA-007 | PAA with question only | `{ peopleAlsoAsk: [{ question: 'What is SEO?' }] }` | Detected, isPresent based on answer |
| PAA-008 | PAA with answer snippet | `{ question: 'Q', answer: { snippet: 'Answer...', url: 'ours.com' } }` | snippet included |

## Question Tracking

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PAA-009 | Return question count | 5 PAA items | `questionCount: 5` |
| PAA-010 | Return matched questions | 3 PAA, 1 ours | `matchedQuestions: ['Question text']` |

---

# 4. Knowledge Panel Detection (`detectKnowledgePanel`)

## Basic Detection

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KP-001 | No knowledge panel | `{}` | `{ type: 'knowledge_panel', isPresent: false }` |
| KP-002 | Undefined panel | `{ knowledgePanel: undefined }` | `{ type: 'knowledge_panel', isPresent: false }` |
| KP-003 | Empty panel object | `{ knowledgePanel: {} }` | `{ type: 'knowledge_panel', isPresent: false }` |
| KP-004 | Panel for our entity | `{ knowledgePanel: { title: 'Our Brand', url: 'ours.com' } }` | `{ isPresent: true }` |
| KP-005 | Panel for different entity | `{ knowledgePanel: { title: 'Other Brand' } }` | `{ isPresent: false }` |

## Entity Matching

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KP-006 | Brand name match | Panel title matches site name | `{ isPresent: true }` |
| KP-007 | URL in panel | Panel contains our URL | `{ isPresent: true }` |
| KP-008 | Partial name match | Panel: 'Brand Inc.', Site: 'Brand' | Depends on matching logic |

## Panel Types

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KP-009 | Person panel | `{ type: 'person', name: 'John Doe' }` | Detected, type included |
| KP-010 | Organization panel | `{ type: 'organization', name: 'Acme' }` | Detected, type included |
| KP-011 | Topic panel | `{ type: 'topic', title: 'SEO' }` | Detected, type included |

---

# 5. Sitelinks Detection (`detectSitelinks`)

## Basic Detection

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SL-001 | No sitelinks | `{}` | `{ type: 'sitelinks', isPresent: false }` |
| SL-002 | Sitelinks on first result (ours) | `organicResults: [{ url: 'ours.com', sitelinks: [...] }]` | `{ isPresent: true }` |
| SL-003 | Sitelinks on first result (competitor) | `organicResults: [{ url: 'theirs.com', sitelinks: [...] }]` | `{ isPresent: false }` |
| SL-004 | Sitelinks array empty | `sitelinks: []` | `{ isPresent: false }` |

## Sitelink Structure

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SL-005 | Multiple sitelinks | 6 sitelinks under our result | `{ isPresent: true, count: 6 }` |
| SL-006 | Sitelink with title | `{ title: 'About Us', url: 'ours.com/about' }` | Detected |
| SL-007 | Sitelink URLs extracted | Multiple sitelinks | URLs returned in result |

## First Result Requirement

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SL-008 | Sitelinks on position 2 | First result no sitelinks, 2nd has | `{ isPresent: false }` (only position 1) |
| SL-009 | No organic results | `organicResults: undefined` | `{ isPresent: false }` |

---

# 6. Reviews Detection (`detectReviews`)

## Basic Detection

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| R-001 | No reviews | `{}` | `{ type: 'reviews', isPresent: false }` |
| R-002 | Reviews on our result | `organicResults: [{ url: 'ours.com', reviews: { rating: 4.5 } }]` | `{ isPresent: true }` |
| R-003 | Reviews on competitor | `organicResults: [{ url: 'theirs.com', reviews: {...} }]` | `{ isPresent: false }` |
| R-004 | No reviews property | result without reviews | `{ isPresent: false }` |

## Rating Extraction

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| R-005 | Rating extracted | `{ reviews: { rating: 4.7 } }` | `{ isPresent: true, rating: 4.7 }` |
| R-006 | Review count | `{ reviews: { count: 250 } }` | `{ count: 250 }` |
| R-007 | Rating source | `{ reviews: { source: 'Trustpilot' } }` | `{ source: 'Trustpilot' }` |

## Rating Validation

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| R-008 | Rating out of range high | `{ rating: 6.0 }` | Should cap or flag invalid |
| R-009 | Rating out of range low | `{ rating: -1 }` | Should cap or flag invalid |
| R-010 | Zero rating | `{ rating: 0 }` | Should handle (new listing?) |

---

# 7. FAQ Detection (`detectFAQ`)

## Basic Detection

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| F-001 | No FAQ | `{}` | `{ type: 'faq', isPresent: false }` |
| F-002 | FAQ exists, our URL | `{ faqs: [{ url: 'ours.com/faq', question: 'Q?' }] }` | `{ isPresent: true }` |
| F-003 | FAQ exists, competitor | `{ faqs: [{ url: 'theirs.com/faq' }] }` | `{ isPresent: false }` |
| F-004 | Empty FAQs array | `{ faqs: [] }` | `{ isPresent: false }` |

## FAQ Structure

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| F-005 | Multiple FAQs | 3 FAQ items | Detected with count |
| F-006 | FAQ with question and answer | `{ question: 'Q?', answer: 'A...' }` | Both extracted |
| F-007 | FAQ question extracted | `{ question: 'How does it work?' }` | Question text included |

---

# 8. Product Detection (`detectProduct`)

## Basic Detection

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-001 | No product | `{}` | `{ type: 'product', isPresent: false }` |
| PR-002 | Product exists, our URL | `{ products: [{ url: 'ours.com/product', name: 'Item' }] }` | `{ isPresent: true }` |
| PR-003 | Product exists, competitor | `{ products: [{ url: 'theirs.com/product' }] }` | `{ isPresent: false }` |
| PR-004 | Empty products array | `{ products: [] }` | `{ isPresent: false }` |

## Product Details

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-005 | Product with price | `{ price: '$99.00' }` | `{ price: '$99.00' }` |
| PR-006 | Product with availability | `{ availability: 'In Stock' }` | `{ availability: 'In Stock' }` |
| PR-007 | Product with image | `{ image: 'product.jpg' }` | Image URL included |
| PR-008 | Product with rating | `{ rating: 4.5, reviewCount: 100 }` | Rating info included |

## Price Parsing

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-009 | Price with currency | `$99.99` | Parsed correctly |
| PR-010 | Price range | `$99 - $149` | Handled gracefully |
| PR-011 | Sale price | `{ price: '$149', salePrice: '$99' }` | Both prices included |

---

## Shared Test Scenarios (All Detectors)

### URL Matching Consistency

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| S-001 | All detectors use same normalization | Consistent www handling |
| S-002 | All detectors handle invalid URLs | Never throw on bad URL |
| S-003 | All detectors case-insensitive | Hostname case ignored |

### Return Value Structure

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| S-004 | All return type field | `type: SERPFeatureType` |
| S-005 | All return isPresent | `isPresent: boolean` |
| S-006 | Optional fields only when present | undefined when not applicable |

### Error Handling

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| S-007 | Null SERP result | `null` | Returns `{ isPresent: false }` |
| S-008 | Non-object SERP | `"string"` | Returns `{ isPresent: false }` |
| S-009 | Null target URL | SERP valid, target: `null` | Returns `{ isPresent: false }` |
| S-010 | Invalid target URL | SERP valid, target: `not-a-url` | Returns `{ isPresent: false }` |

---

## Mock Data

### Image Pack SERPResult
```javascript
{
  keyword: 'cat pictures',
  targetUrl: 'https://mypet.com',
  images: [
    { url: 'https://mypet.com/cute-cat.jpg', title: 'Cute Cat' },
    { url: 'https://other.com/cat2.jpg', title: 'Another Cat' },
    { url: 'https://example.com/cat3.jpg', title: 'Third Cat' }
  ]
}
```

### Video SERPResult
```javascript
{
  keyword: 'how to tie a tie',
  targetUrl: 'https://ours.com',
  videos: [
    { url: 'https://ours.com/tie-tutorial', title: 'How to Tie a Tie', duration: '3:45', platform: 'self' },
    { url: 'https://youtube.com/watch?v=abc', title: 'Tie Tutorial', duration: '5:00', platform: 'youtube' }
  ]
}
```

### People Also Ask SERPResult
```javascript
{
  keyword: 'what is seo',
  targetUrl: 'https://ours.com',
  peopleAlsoAsk: [
    { question: 'What does SEO mean?', answer: { text: 'Search Engine Optimization...', url: 'https://ours.com/seo-guide' } },
    { question: 'How does SEO work?', answer: { text: 'SEO works by...', url: 'https://other.com/seo' } },
    { question: 'Is SEO free?', answer: { text: 'SEO can be done for free...', url: 'https://third.com/free-seo' } }
  ]
}
```

### Knowledge Panel SERPResult
```javascript
{
  keyword: 'apple',
  targetUrl: 'https://apple.com',
  knowledgePanel: {
    type: 'organization',
    title: 'Apple Inc.',
    description: 'American multinational technology company...',
    url: 'https://apple.com',
    founded: '1976',
    headquarters: 'Cupertino, California'
  }
}
```

### Sitelinks SERPResult
```javascript
{
  keyword: 'example',
  targetUrl: 'https://example.com',
  organicResults: [
    {
      position: 1,
      url: 'https://example.com',
      title: 'Example Domain',
      sitelinks: [
        { title: 'About Us', url: 'https://example.com/about' },
        { title: 'Contact', url: 'https://example.com/contact' },
        { title: 'Products', url: 'https://example.com/products' },
        { title: 'Blog', url: 'https://example.com/blog' }
      ]
    }
  ]
}
```

### Reviews SERPResult
```javascript
{
  keyword: 'best seo tool',
  targetUrl: 'https://ours.com',
  organicResults: [
    {
      position: 1,
      url: 'https://ours.com',
      title: 'Our SEO Tool',
      reviews: {
        rating: 4.8,
        count: 1250,
        source: 'Trustpilot'
      }
    }
  ]
}
```

### FAQ SERPResult
```javascript
{
  keyword: 'how to use seo tool',
  targetUrl: 'https://ours.com',
  faqs: [
    { question: 'How do I get started?', answer: 'Simply sign up...', url: 'https://ours.com/get-started' },
    { question: 'What features are included?', answer: 'Our tool includes...', url: 'https://ours.com/features' }
  ]
}
```

### Product SERPResult
```javascript
{
  keyword: 'buy seo software',
  targetUrl: 'https://ours.com',
  products: [
    {
      name: 'SEO Pro Plan',
      url: 'https://ours.com/pro',
      price: '$99/month',
      availability: 'Available',
      rating: 4.7,
      reviewCount: 450
    }
  ]
}
```

---

## Coverage Targets

| Detector | Target Coverage |
|----------|-----------------|
| detectImagePack | 100% |
| detectVideo | 100% |
| detectPeopleAlsoAsk | 100% |
| detectKnowledgePanel | 100% |
| detectSitelinks | 100% |
| detectReviews | 100% |
| detectFAQ | 100% |
| detectProduct | 100% |
| Shared URL matching | 100% |
| Error handling | 100% |

---

## Test Execution

```bash
# Run all other feature detector tests
npm test -- tests/unit/serp-feature-detectors/image-pack.test.js
npm test -- tests/unit/serp-feature-detectors/video.test.js
npm test -- tests/unit/serp-feature-detectors/people-also-ask.test.js
npm test -- tests/unit/serp-feature-detectors/knowledge-panel.test.js
npm test -- tests/unit/serp-feature-detectors/sitelinks.test.js
npm test -- tests/unit/serp-feature-detectors/reviews.test.js
npm test -- tests/unit/serp-feature-detectors/faq.test.js
npm test -- tests/unit/serp-feature-detectors/product.test.js

# Run all SERP feature tests
npm test -- tests/unit/serp-feature-detectors/

# Run with coverage
npm run test:coverage -- --filter=serp-feature
```

---

## Dependencies

- TASK-7-2: SERP Feature Types and Models
- TASK-7-4: SERP Feature Detector Service
- TASK-7-5: Featured Snippet Detection (pattern reference)
- TASK-7-6: Local Pack Detection (pattern reference)
- `backend/src/models/serp-feature.ts` - Type definitions
- `backend/src/services/serp-feature-detector.ts` - Detector class

---

## Notes for Developer

### Shared Implementation Pattern

All detectors follow this pattern:
```typescript
private detectXxx(serp: SERPResult, targetUrl: string): SERPFeature {
  const feature = serp.xxx; // Get feature data from SERP
  
  // Validate presence
  if (!feature || isEmpty(feature)) {
    return { type: 'xxx', isPresent: false };
  }
  
  // Extract and match
  const targetDomain = this.extractDomain(targetUrl);
  const isOurs = this.checkFeatureOwnership(feature, targetDomain);
  
  return {
    type: 'xxx',
    isPresent: isOurs,
    // Optional fields specific to feature type
  };
}
```

### Feature-Specific Notes

1. **Image Pack:** Check if any image URL contains target domain
2. **Video:** Support multiple platforms (YouTube, Vimeo, self-hosted)
3. **People Also Ask:** Check answer URLs, count questions
4. **Knowledge Panel:** Match entity name or URL to brand
5. **Sitelinks:** Only on first organic result (position 1)
6. **Reviews:** Extract rating, count, source
7. **FAQ:** Check FAQ URLs against target domain
8. **Product:** Extract price, availability, rating

### Error Handling

- Never throw on malformed input
- Return `{ isPresent: false }` for any error
- Use shared URL normalization helper
- Handle missing/null/undefined at each level

---

## Comments

### [2026-02-26 18:25] Agent: PreTester
Created test documentation for TASK-7-7 Other SERP Feature Detection.

Test coverage includes 8 detectors with:
- Image Pack: 10 scenarios
- Video: 11 scenarios
- People Also Ask: 10 scenarios
- Knowledge Panel: 11 scenarios
- Sitelinks: 9 scenarios
- Reviews: 10 scenarios
- FAQ: 7 scenarios
- Product: 11 scenarios
- Shared scenarios: 10 scenarios

Total: 89 test scenarios covering all acceptance criteria.

Key implementation notes:
1. All detectors follow the same pattern
2. Use shared URL normalization from parent class
3. Never throw on malformed input
4. Return consistent structure across all detectors
5. Include feature-specific optional fields

Ready for Developer implementation.
