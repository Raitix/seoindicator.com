# SPEC-007: SERP Feature Tracking

## Overview
This specification defines the technical implementation for SERP feature tracking. This extends basic rank tracking to include rich results like featured snippets, local pack, image packs, and other SERP features that impact visibility and CTR.

## Status
`To Prioritize`


## Feature Reference
- Feature: [FEAT-007](../FEATURES/FEAT-007.md)
- Created: 2026-02-19
- Architect: Architect
- Depends On: FEAT-001 (Keyword Rank Tracking)

---

## Technical Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                   SERP Feature Tracking                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │  SERP       │────▶│  Feature    │────▶│  Ranking    │   │
│  │  Fetcher    │     │  Detector   │     │  Service    │   │
│  └─────────────┘     └─────────────┘     └──────┬──────┘   │
│                                                 │          │
│  ┌──────────────────────────────────────────────┘          │
│  │                                                          │
│  ▼                                                          │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │  Feature    │     │  Alert      │     │  Report     │   │
│  │  Service    │     │  Generator  │     │  Enhancer   │   │
│  └──────┬──────┘     └──────┬──────┘     └──────┬──────┘   │
│         │                   │                   │          │
│         └───────────────────┼───────────────────┘          │
│                             ▼                              │
│                    ┌─────────────┐                         │
│                    │  Database   │                         │
│                    └─────────────┘                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### SERP Features Table
```sql
CREATE TABLE serp_features (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ranking_id INTEGER NOT NULL,
    keyword_id INTEGER NOT NULL,
    feature_type TEXT NOT NULL,          -- 'featured_snippet', 'local_pack', etc.
    is_present INTEGER DEFAULT 0,        -- Boolean: 0 or 1
    position INTEGER,                    -- Position within feature (e.g., #1 in local pack)
    url TEXT,                            -- URL shown in feature
    title TEXT,                          -- Title shown in feature
    snippet TEXT,                        -- Snippet text (for featured snippets)
    checked_at DATETIME NOT NULL,
    FOREIGN KEY (ranking_id) REFERENCES rankings(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE,
    UNIQUE(ranking_id, feature_type)
);

CREATE INDEX idx_serp_features_keyword ON serp_features(keyword_id);
CREATE INDEX idx_serp_features_type ON serp_features(feature_type);
CREATE INDEX idx_serp_features_present ON serp_features(is_present);
CREATE INDEX idx_serp_features_checked ON serp_features(checked_at);
```

### SERP Feature History Table
```sql
CREATE TABLE serp_feature_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    keyword_id INTEGER NOT NULL,
    feature_type TEXT NOT NULL,
    action TEXT NOT NULL,                -- 'gained', 'lost'
    previous_url TEXT,                   -- URL that had it before
    current_url TEXT,                    -- URL that has it now
    detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE
);

CREATE INDEX idx_feature_history_keyword ON serp_feature_history(keyword_id);
CREATE INDEX idx_feature_history_type ON serp_feature_history(feature_type);
CREATE INDEX idx_feature_history_detected ON serp_feature_history(detected_at);
```

### Extend Rankings Table
```sql
-- Add SERP features JSON column for quick access
ALTER TABLE rankings ADD COLUMN serp_features JSON;
-- Example value: {"featured_snippet": true, "local_pack": false, "sitelinks": true}
```

---

## SERP Feature Types

### Feature Definitions
```typescript
type SERPFeatureType =
  | 'featured_snippet'    // Answer box at top
  | 'local_pack'          // Map + 3 local businesses
  | 'image_pack'          // Row of images
  | 'video'               // Video thumbnail
  | 'people_also_ask'     // Expandable Q&A
  | 'knowledge_panel'     // Entity info sidebar
  | 'sitelinks'           // Additional page links
  | 'reviews'             // Star ratings
  | 'faq'                 // FAQ rich results
  | 'recipe'              // Recipe card
  | 'job_listing'         // Job posting
  | 'event'               // Event listing
  | 'product';            // Product listing

interface SERPFeature {
  type: SERPFeatureType;
  isPresent: boolean;
  position?: number;      // Position within feature
  url?: string;
  title?: string;
  snippet?: string;
}

interface SERPFeatureDetection {
  keywordId: number;
  rankingId: number;
  features: SERPFeature[];
  detectedAt: Date;
}
```

### Feature Metadata
```typescript
const SERP_FEATURE_META: Record<SERPFeatureType, {
  name: string;
  icon: string;
  description: string;
  ctrImpact: string;
}> = {
  featured_snippet: {
    name: 'Featured Snippet',
    icon: '🏆',
    description: 'Answer box at top of search results',
    ctrImpact: '+30-50%'
  },
  local_pack: {
    name: 'Local Pack',
    icon: '📍',
    description: 'Map with 3 local business listings',
    ctrImpact: 'High for local'
  },
  image_pack: {
    name: 'Image Pack',
    icon: '🖼️',
    description: 'Horizontal row of images in results',
    ctrImpact: 'Visual attraction'
  },
  video: {
    name: 'Video Result',
    icon: '🎬',
    description: 'Video thumbnail in search results',
    ctrImpact: 'High engagement'
  },
  people_also_ask: {
    name: 'People Also Ask',
    icon: '❓',
    description: 'Expandable related questions',
    ctrImpact: 'Secondary visibility'
  },
  knowledge_panel: {
    name: 'Knowledge Panel',
    icon: '📋',
    description: 'Entity information sidebar',
    ctrImpact: 'Brand authority'
  },
  sitelinks: {
    name: 'Sitelinks',
    icon: '🔗',
    description: 'Additional page links under result',
    ctrImpact: '+20-30%'
  },
  reviews: {
    name: 'Reviews/Ratings',
    icon: '⭐',
    description: 'Star ratings displayed with result',
    ctrImpact: '+10-15%'
  },
  faq: {
    name: 'FAQ Rich Result',
    icon: '📝',
    description: 'FAQ accordion in search results',
    ctrImpact: 'Increased visibility'
  },
  product: {
    name: 'Product Listing',
    icon: '🛒',
    description: 'Product rich result with price/availability',
    ctrImpact: 'Shopping intent'
  }
};
```

---

## API Endpoints

### Get SERP Features for Keyword
```http
GET /api/v1/keywords/:id/serp-features

Query Parameters:
  - from: ISO date
  - to: ISO date

Response 200:
{
  "success": true,
  "data": {
    "keyword": {
      "id": 1,
      "keyword": "seo tools"
    },
    "currentFeatures": [
      {
        "type": "featured_snippet",
        "isPresent": true,
        "url": "https://example.com/seo-tools",
        "snippet": "Best SEO tools for 2026..."
      },
      {
        "type": "sitelinks",
        "isPresent": true
      }
    ],
    "history": [
      {
        "date": "2026-02-19",
        "features": {
          "featured_snippet": true,
          "sitelinks": true
        }
      },
      {
        "date": "2026-02-18",
        "features": {
          "featured_snippet": false,
          "sitelinks": true
        }
      }
    ],
    "changes": [
      {
        "type": "featured_snippet",
        "action": "gained",
        "detectedAt": "2026-02-19T10:00:00Z"
      }
    ]
  }
}
```

### Get SERP Feature Summary
```http
GET /api/v1/sites/:siteId/serp-features/summary

Response 200:
{
  "success": true,
  "data": {
    "totalKeywords": 50,
    "keywordsWithFeatures": 25,
    "featureCounts": {
      "featured_snippet": 5,
      "local_pack": 3,
      "sitelinks": 10,
      "reviews": 7
    },
    "recentChanges": {
      "gained": [
        {
          "keyword": "seo tools",
          "feature": "featured_snippet",
          "detectedAt": "2026-02-19T10:00:00Z"
        }
      ],
      "lost": [
        {
          "keyword": "keyword tracker",
          "feature": "local_pack",
          "detectedAt": "2026-02-18T15:00:00Z"
        }
      ]
    }
  }
}
```

### Filter Keywords by SERP Feature
```http
GET /api/v1/sites/:siteId/keywords?serpFeature=featured_snippet

Query Parameters:
  - serpFeature: string (featured_snippet, local_pack, etc.)
  - serpFeaturePresent: boolean

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "keyword": "seo tools",
      "position": 1,
      "serpFeatures": {
        "featured_snippet": true,
        "sitelinks": true
      }
    }
  ]
}
```

---

## Feature Detection Service

### Detection Logic
```typescript
class SERPFeatureDetector {
  constructor(private serpFetcher: SERPFetcher) {}
  
  async detectFeatures(
    keyword: string,
    targetUrl: string,
    serpResult: SERPResult
  ): Promise<SERPFeature[]> {
    const features: SERPFeature[] = [];
    
    // Check for each feature type
    features.push(this.detectFeaturedSnippet(serpResult, targetUrl));
    features.push(this.detectLocalPack(serpResult, targetUrl));
    features.push(this.detectImagePack(serpResult, targetUrl));
    features.push(this.detectVideo(serpResult, targetUrl));
    features.push(this.detectPeopleAlsoAsk(serpResult, targetUrl));
    features.push(this.detectKnowledgePanel(serpResult, targetUrl));
    features.push(this.detectSitelinks(serpResult, targetUrl));
    features.push(this.detectReviews(serpResult, targetUrl));
    
    return features;
  }
  
  private detectFeaturedSnippet(serp: SERPResult, targetUrl: string): SERPFeature {
    const snippet = serp.answerBox;
    if (!snippet) {
      return { type: 'featured_snippet', isPresent: false };
    }
    
    const isOurs = snippet.url?.includes(new URL(targetUrl).hostname);
    return {
      type: 'featured_snippet',
      isPresent: isOurs,
      url: snippet.url,
      title: snippet.title,
      snippet: snippet.snippet
    };
  }
  
  private detectLocalPack(serp: SERPResult, targetUrl: string): SERPFeature {
    const localPack = serp.localPack;
    if (!localPack) {
      return { type: 'local_pack', isPresent: false };
    }
    
    const position = localPack.findIndex(item => 
      item.url?.includes(new URL(targetUrl).hostname)
    );
    
    return {
      type: 'local_pack',
      isPresent: position >= 0,
      position: position >= 0 ? position + 1 : undefined
    };
  }
  
  // ... additional detection methods
}
```

---

## Feature Change Detection

### Change Tracker
```typescript
class SERPFeatureChangeTracker {
  constructor(
    private featureRepo: SERPFeatureRepository,
    private historyRepo: SERPFeatureHistoryRepository
  ) {}
  
  async trackChanges(
    keywordId: number,
    currentFeatures: SERPFeature[],
    previousFeatures: SERPFeature[]
  ): Promise<SERPFeatureChange[]> {
    const changes: SERPFeatureChange[] = [];
    
    for (const featureType of ALL_FEATURE_TYPES) {
      const current = currentFeatures.find(f => f.type === featureType);
      const previous = previousFeatures.find(f => f.type === featureType);
      
      // Gained feature
      if (current?.isPresent && !previous?.isPresent) {
        changes.push({
          type: featureType,
          action: 'gained',
          keywordId,
          currentUrl: current.url,
          detectedAt: new Date()
        });
        
        await this.historyRepo.insert({
          keyword_id: keywordId,
          feature_type: featureType,
          action: 'gained',
          current_url: current.url
        });
      }
      
      // Lost feature
      if (!current?.isPresent && previous?.isPresent) {
        changes.push({
          type: featureType,
          action: 'lost',
          keywordId,
          previousUrl: previous.url,
          detectedAt: new Date()
        });
        
        await this.historyRepo.insert({
          keyword_id: keywordId,
          feature_type: featureType,
          action: 'lost',
          previous_url: previous.url
        });
      }
    }
    
    return changes;
  }
}
```

---

## Alert Integration

### SERP Feature Alerts
```typescript
interface SERPFeatureAlert {
  type: 'serp_feature_gained' | 'serp_feature_lost';
  keywordId: number;
  keyword: string;
  featureType: SERPFeatureType;
  featureName: string;
  url?: string;
  detectedAt: Date;
}

// Extend alert types in FEAT-002
const SERP_FEATURE_ALERT_TEMPLATES = {
  serp_feature_gained: {
    subject: '🎉 New SERP Feature: {{featureName}}',
    body: 'Your page now appears in the {{featureName}} for "{{keyword}}'
  },
  serp_feature_lost: {
    subject: '⚠️ Lost SERP Feature: {{featureName}}',
    body: 'Your page no longer appears in the {{featureName}} for "{{keyword}}"'
  }
};
```

---

## Implementation Files

### Backend Structure
```
backend/
├── src/
│   ├── api/
│   │   └── routes/
│   │       └── serp-features.ts
│   │
│   ├── services/
│   │   ├── serp-feature-service.ts
│   │   └── serp-feature-detector.ts
│   │
│   ├── repositories/
│   │   ├── serp-feature-repo.ts
│   │   └── serp-feature-history-repo.ts
│   │
│   └── models/
│       └── serp-feature.ts
│
└── tests/
    ├── unit/
    │   ├── serp-feature-detector.test.ts
    │   └── serp-feature-service.test.ts
    │
    └── integration/
        └── serp-features-api.test.ts
```

### Frontend Components
```
frontend/
├── src/
│   └── components/
│       ├── serp-feature-icon.tsx
│       ├── serp-feature-badge.tsx
│       ├── serp-feature-history.tsx
│       └── serp-feature-summary.tsx
```

---

## Task Breakdown

### Phase 1: Database & Models
1. **TASK-007-01**: Database schema - serp_features, serp_feature_history tables
2. **TASK-007-02**: SERP feature model and types
3. **TASK-007-03**: SERP feature repository

### Phase 2: Detection Logic
4. **TASK-007-04**: SERP feature detector service
5. **TASK-007-05**: Featured snippet detection
6. **TASK-007-06**: Local pack detection
7. **TASK-007-07**: Other feature detection (images, video, etc.)

### Phase 3: Change Tracking
8. **TASK-007-08**: Feature change tracker
9. **TASK-007-09**: Feature history repository

### Phase 4: API Layer
10. **TASK-007-10**: SERP features API endpoints
11. **TASK-007-11**: Feature summary endpoint
12. **TASK-007-12**: Filter by feature endpoint

### Phase 5: Integration
13. **TASK-007-13**: Integrate with ranking check workflow
14. **TASK-007-14**: Alert integration for feature changes
15. **TASK-007-15**: Report enhancement for SERP features

### Phase 6: Frontend
16. **TASK-007-16**: SERP feature icons/badges
17. **TASK-007-17**: Feature history component
18. **TASK-007-18**: Feature filter UI

### Phase 7: Testing
19. **TASK-007-19**: Unit tests
20. **TASK-007-20**: Integration tests

---

## MVP Scope

### In Scope (MVP)
- Featured snippet tracking only
- Basic icon display in keyword list
- Gained/lost alerts for featured snippets
- Feature presence in ranking data

### Post-MVP
- All SERP feature types
- Feature history charts
- Competitor feature comparison
- Advanced feature analytics
- Position within features (e.g., #2 in local pack)

---

## Mock SERP Feature Data

For MVP development with mock fetcher:

```typescript
function generateMockSERPFeatures(keyword: string, position: number): SERPFeature[] {
  const features: SERPFeature[] = [];
  
  // Higher chance of featured snippet for position 1-3
  if (position <= 3 && Math.random() > 0.5) {
    features.push({
      type: 'featured_snippet',
      isPresent: position === 1 && Math.random() > 0.3,
      snippet: `This is a mock featured snippet for ${keyword}...`
    });
  }
  
  // Sitelinks for top results
  if (position === 1) {
    features.push({
      type: 'sitelinks',
      isPresent: Math.random() > 0.3
    });
  }
  
  // Random other features
  features.push({
    type: 'local_pack',
    isPresent: Math.random() > 0.8
  });
  
  features.push({
    type: 'reviews',
    isPresent: Math.random() > 0.7
  });
  
  return features;
}
```

---

## Comments

### [2026-02-19 23:43] Architect: Specification Created
- Database schema: serp_features, serp_feature_history tables
- 10 SERP feature types defined with metadata
- Feature detection service architecture
- Change tracking for gained/lost features
- Alert integration for feature changes
- 20 implementation tasks across 7 phases
- Status: To Break Into Tasks
- Depends on: FEAT-001
- MVP: Featured snippet tracking only
