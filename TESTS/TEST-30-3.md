# TEST-30-3: How It Works Page

## Task Reference
- **Task:** TASK-30-3
- **Feature:** FEAT-030 (Legal + About + How It Works + Cookie Consent)
- **Specification:** SPEC-030
- **Agent:** PreTester
- **Created:** 2026-03-04

## Overview

Comprehensive test suite for the How It Works page, covering user journey steps, indicator categories, scoring methodology, FAQ section, SEO metadata, and accessibility requirements.

## Test Files

| File | Tests | Assertions | Description |
|------|-------|------------|-------------|
| `frontend/__tests__/app/how-it-works/page.test.tsx` | 45+ | 90+ | Page component tests |
| `frontend/__tests__/components/pages/HowItWorksContent.test.tsx` | 60+ | 120+ | Content component tests |
| `frontend/__tests__/app/how-it-works/seo.test.tsx` | 30+ | 60+ | SEO metadata tests |
| `frontend/__tests__/a11y/how-it-works.test.tsx` | 25+ | 50+ | Accessibility tests |
| **Total** | **160+** | **320+** | |

---

## Test Scenarios

### 1. Page Component Tests (page.test.tsx)

#### 1.1 Page Rendering
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PAGE-001 | Page renders without errors | Render `/how-it-works` | Component mounts successfully |
| PAGE-002 | Page has correct route | Access route | Route is `/how-it-works` |
| PAGE-003 | Page renders HowItWorksContent | Render page | HowItWorksContent component present |
| PAGE-004 | Page has static generation | Check generateStaticParams | Returns single entry |
| PAGE-005 | Page container has correct classes | Render page | Container has proper Tailwind classes |

#### 1.2 Content Structure
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PAGE-010 | Hero section present | Render page | H1 with "How It Works" |
| PAGE-011 | Steps section present | Render page | Section with user journey steps |
| PAGE-012 | Categories section present | Render page | Section with indicator categories |
| PAGE-013 | Scoring section present | Render page | Section with scoring methodology |
| PAGE-014 | FAQ section present | Render page | Section with FAQ accordion |

#### 1.3 Responsive Design
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PAGE-020 | Mobile layout | Viewport 375px | Single column, touch-friendly |
| PAGE-021 | Tablet layout | Viewport 768px | Two columns where applicable |
| PAGE-022 | Desktop layout | Viewport 1024px+ | Full layout with side elements |
| PAGE-023 | Touch targets | Mobile view | Min 44px touch targets |

---

### 2. Content Component Tests (HowItWorksContent.test.tsx)

#### 2.1 User Journey Steps
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| STEP-001 | Steps section renders | Render component | Steps section present |
| STEP-002 | 4-5 steps displayed | Render component | 4 or 5 steps visible |
| STEP-003 | Each step has icon | Render steps | Icon element in each step |
| STEP-004 | Each step has title | Render steps | Title text in each step |
| STEP-005 | Each step has description | Render steps | Description text in each step |
| STEP-006 | Steps are numbered | Render steps | Numbers 1-4 or 1-5 visible |
| STEP-007 | Step icons are accessible | Render steps | aria-label on icons |

#### 2.2 Indicator Categories
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CAT-001 | Categories section renders | Render component | Categories section present |
| CAT-002 | 4 categories displayed | Render component | Technical, Content, Performance, UX |
| CAT-003 | Technical category weight | Render category | 30% weight displayed |
| CAT-004 | Content category weight | Render category | 40% weight displayed |
| CAT-005 | Performance category weight | Render category | 20% weight displayed |
| CAT-006 | UX category weight | Render category | 10% weight displayed |
| CAT-007 | Weights sum to 100% | Check weights | 30+40+20+10 = 100% |
| CAT-008 | Technical indicators listed | Render category | robots.txt, sitemap, SSL, mobile-friendly, canonical, hreflang |
| CAT-009 | Content indicators listed | Render category | title tag, meta description, H1, keyword density, images |
| CAT-010 | Performance indicators listed | Render category | page speed, Core Web Vitals |
| CAT-011 | UX indicators listed | Render category | mobile viewport, font readability |

#### 2.3 Scoring Methodology
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SCORE-001 | Scoring section renders | Render component | Scoring methodology section |
| SCORE-002 | Score range explained | Render section | 0-100 scale mentioned |
| SCORE-003 | Weight system explained | Render section | How weights affect scores |
| SCORE-004 | Visual score representation | Render section | Progress bars or charts |
| SCORE-005 | Score categories explained | Render section | Good/Warning/Error thresholds |

#### 2.4 FAQ Section
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FAQ-001 | FAQ section renders | Render component | FAQ section present |
| FAQ-002 | 5-10 questions displayed | Render FAQ | 5-10 FAQ items |
| FAQ-003 | FAQ accordion collapsible | Click question | Expands/collapses |
| FAQ-004 | FAQ accordion keyboard nav | Press Enter/Space | Toggles accordion |
| FAQ-005 | FAQ aria-expanded attribute | Render FAQ | aria-expanded on triggers |
| FAQ-006 | FAQ aria-controls attribute | Render FAQ | aria-controls points to panel |
| FAQ-007 | Only one FAQ open | Open multiple | Only last one open (or multiple allowed) |
| FAQ-008 | FAQ answers visible | Expand FAQ | Answer text visible |

#### 2.5 Visual Elements
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VIS-001 | Icons for each step | Render steps | Icon elements present |
| VIS-002 | Progress bars for weights | Render categories | Visual weight bars |
| VIS-003 | Charts render correctly | Render section | SVG or canvas charts |
| VIS-004 | Animations not excessive | Render component | No jarring animations |
| VIS-005 | Reduced motion support | prefers-reduced-motion | Animations disabled/reduced |

---

### 3. SEO Metadata Tests (seo.test.tsx)

#### 3.1 Meta Tags
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEO-001 | Meta title correct | Render page | "How It Works \| SEOIndicator SEO Analysis" |
| SEO-002 | Meta description present | Render page | Meta description tag |
| SEO-003 | Meta description length | Check length | 150-160 characters |
| SEO-004 | Robots meta tag | Render page | index, follow |
| SEO-005 | Canonical URL | Render page | Canonical link present |

#### 3.2 Open Graph Tags
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEO-010 | og:title present | Render page | og:title meta tag |
| SEO-011 | og:description present | Render page | og:description meta tag |
| SEO-012 | og:url present | Render page | og:url with correct URL |
| SEO-013 | og:type present | Render page | og:type = "website" |
| SEO-014 | og:image present | Render page | og:image meta tag |

#### 3.3 Schema.org Markup
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEO-020 | WebPage schema present | Render page | JSON-LD with @type: WebPage |
| SEO-021 | FAQPage schema present | Render page | JSON-LD with @type: FAQPage |
| SEO-022 | FAQPage has questions | Check schema | 5-10 Question items |
| FAQPage schema valid | Validate schema | Correct structure per schema.org |
| SEO-024 | Schema in script tag | Render page | type="application/ld+json" |

---

### 4. Accessibility Tests (how-it-works.test.tsx)

#### 4.1 Heading Hierarchy
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| A11Y-001 | Single H1 on page | Render page | Exactly one h1 element |
| A11Y-002 | H1 content correct | Check h1 | "How It Works" text |
| A11Y-003 | H2 elements follow H1 | Check hierarchy | No h3 without h2 parent |
| A11Y-004 | No skipped heading levels | Check hierarchy | h1 → h2 → h3 order |

#### 4.2 Keyboard Navigation
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| A11Y-010 | Tab navigation works | Press Tab | Focus moves through elements |
| A11Y-011 | FAQ accordion keyboard | Tab to FAQ, Enter | Opens/closes accordion |
| A11Y-012 | Focus visible | Tab through | Focus indicator visible |
| A11Y-013 | No keyboard trap | Tab through | Can exit all elements |

#### 4.3 Screen Reader Support
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| A11Y-020 | Icons have aria-label | Render icons | aria-label on icon elements |
| A11Y-021 | FAQ aria-expanded | Render FAQ | aria-expanded on triggers |
| A11Y-022 | FAQ aria-controls | Render FAQ | aria-controls on triggers |
| A11Y-023 | Regions have landmarks | Render page | main, article, section landmarks |
| A11Y-024 | Skip link present | Render page | Skip to main content link |

#### 4.4 Color Contrast
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| A11Y-030 | Text contrast sufficient | Check contrast | ≥ 4.5:1 for normal text |
| A11Y-031 | Large text contrast | Check contrast | ≥ 3:1 for large text |
| A11Y-032 | Interactive element contrast | Check contrast | ≥ 3:1 for UI components |

---

## Mock Data

### User Journey Steps
```typescript
const mockSteps = [
  {
    number: 1,
    icon: 'search',
    title: 'Enter Your URL',
    description: 'Enter the URL of the page you want to analyze for SEO performance.'
  },
  {
    number: 2,
    icon: 'settings',
    title: 'Configure Analysis',
    description: 'Select the type of analysis: quick, standard, or deep scan.'
  },
  {
    number: 3,
    icon: 'chart',
    title: 'Get Results',
    description: 'Receive a comprehensive SEO score with detailed breakdown.'
  },
  {
    number: 4,
    icon: 'lightbulb',
    title: 'Implement Fixes',
    description: 'Follow actionable recommendations to improve your score.'
  },
  {
    number: 5,
    icon: 'refresh',
    title: 'Track Progress',
    description: 'Monitor improvements over time with historical tracking.'
  }
];
```

### Indicator Categories
```typescript
const mockCategories = [
  {
    name: 'Technical',
    weight: 30,
    indicators: ['robots.txt', 'sitemap', 'SSL', 'mobile-friendly', 'canonical', 'hreflang'],
    color: 'blue'
  },
  {
    name: 'Content',
    weight: 40,
    indicators: ['title tag', 'meta description', 'H1', 'keyword density', 'images'],
    color: 'green'
  },
  {
    name: 'Performance',
    weight: 20,
    indicators: ['page speed', 'Core Web Vitals'],
    color: 'yellow'
  },
  {
    name: 'UX',
    weight: 10,
    indicators: ['mobile viewport', 'font readability'],
    color: 'purple'
  }
];
```

### FAQ Items
```typescript
const mockFAQItems = [
  {
    question: 'How is my SEO score calculated?',
    answer: 'Your SEO score is calculated based on 17 different indicators across 4 categories...'
  },
  {
    question: 'How often should I run an SEO analysis?',
    answer: 'We recommend running an analysis at least once a month or after significant content changes...'
  },
  {
    question: 'What is the difference between quick, standard, and deep analysis?',
    answer: 'Quick analysis checks essential indicators in under a minute...'
  },
  {
    question: 'Can I analyze multiple pages at once?',
    answer: 'Yes, you can add multiple pages to a project and analyze them together...'
  },
  {
    question: 'How accurate are the SEO recommendations?',
    answer: 'Our recommendations are based on Google\'s official guidelines and industry best practices...'
  },
  {
    question: 'What are Core Web Vitals?',
    answer: 'Core Web Vitals are specific metrics that measure user experience...'
  },
  {
    question: 'Do I need technical knowledge to use SEOIndicator?',
    answer: 'No, our tool is designed for users of all skill levels...'
  },
  {
    question: 'How do I fix the issues found?',
    answer: 'Each issue includes detailed recommendations with step-by-step guides...'
  },
  {
    question: 'Can I export my analysis results?',
    answer: 'Yes, you can export results in PDF, CSV, or XLS format...'
  },
  {
    question: 'Is my data secure?',
    answer: 'Yes, we use industry-standard encryption and never share your data...'
  }
];
```

---

## Coverage Targets

| Category | Target | Notes |
|----------|--------|-------|
| Statements | ≥ 80% | All major code paths |
| Branches | ≥ 75% | Conditional logic |
| Functions | ≥ 80% | All exported functions |
| Lines | ≥ 80% | All significant lines |

---

## Test Execution

```bash
# Run all How It Works tests
npm test -- --testPathPattern="how-it-works"

# Run specific test file
npm test -- frontend/__tests__/app/how-it-works/page.test.tsx

# Run with coverage
npm test -- --coverage --testPathPattern="how-it-works"

# Run accessibility tests only
npm test -- --testPathPattern="a11y/how-it-works"
```

---

## Dependencies

- **None** (standalone page)
- Icon library (Lucide recommended)
- Accordion component (shadcn/ui)

---

## Notes for Developer

1. **Route Structure:**
   - File: `frontend/app/how-it-works/page.tsx`
   - Component: `frontend/components/pages/HowItWorksContent.tsx`

2. **Icons:**
   - Use Lucide icons: Search, Settings, BarChart, Lightbulb, RefreshCw
   - Ensure all icons have aria-label

3. **FAQ Accordion:**
   - Use shadcn/ui Accordion component
   - Implement keyboard navigation
   - Support prefers-reduced-motion

4. **Schema.org:**
   - Include both WebPage and FAQPage schemas
   - FAQPage schema required for rich results

5. **Performance:**
   - Static generation preferred
   - Lazy load below-fold content
   - Optimize images

---

## Comments

### [2026-03-04 PreTester]
**Test Design Analysis:**

| Aspect | Assessment | Details |
|--------|------------|---------|
| **Complexity** | Medium | Multiple sections with interactive elements |
| **Test Coverage** | 160+ tests | Comprehensive coverage of all acceptance criteria |
| **Key Challenges** | FAQ accordion, Schema.org | Keyboard navigation, valid JSON-LD |
| **Test Priority** | High | Core educational page for user onboarding |

**Test Files Created:**
- `frontend/__tests__/app/how-it-works/page.test.tsx` - Page component tests
- `frontend/__tests__/components/pages/HowItWorksContent.test.tsx` - Content component tests
- `frontend/__tests__/app/how-it-works/seo.test.tsx` - SEO metadata tests
- `frontend/__tests__/a11y/how-it-works.test.tsx` - Accessibility tests

**Key Implementation Notes:**
1. 4 indicator categories with specific weights (must sum to 100%)
2. 4-5 user journey steps with icons
3. 5-10 FAQ items with accordion behavior
4. FAQPage schema.org markup for SEO
5. Full keyboard accessibility for FAQ accordion

**Status:** Ready for Developer implementation
