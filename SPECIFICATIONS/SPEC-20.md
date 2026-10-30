# SPEC-020: SEO Guides Library + Clean Routing

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

---

## Overview

Recreate the SEO guide pages for each of 17 SEO indicators under `/seo-guides/*` with clean URLs, consistent navigation, integration with the interactive tools from FEAT-016, and proper routing configuration. Each guide page provides educational content about an SEO indicator and links to the relevant interactive tool for hands-on testing.

---

## SEO Indicators (17 Total)

| # | Indicator | Slug | Related Tool | Description |
|---|-----------|------|--------------|-------------|
| 1 | Meta Title | `meta-title` | Meta Tag Analyzer | Page title optimization for search engines |
| 2 | Meta Description | `meta-description` | Meta Tag Analyzer | Click-worthy description in SERPs |
| 3 | H1 Headings | `h1-headings` | - | Primary heading structure |
| 4 | Heading Hierarchy | `heading-hierarchy` | - | H1-H6 logical structure |
| 5 | Image Alt Text | `image-alt-text` | - | Accessible image descriptions |
| 6 | Image Optimization | `image-optimization` | - | File size, format, lazy loading |
| 7 | Internal Links | `internal-links` | - | Site structure and navigation |
| 8 | External Links | `external-links` | - | Authority and reference links |
| 9 | Page Speed | `page-speed` | - | Load time optimization |
| 10 | Mobile-Friendliness | `mobile-friendly` | Mobile-Friendly Checker | Responsive design |
| 11 | HTTPS/Security | `https-security` | - | SSL certificate and security headers |
| 12 | Structured Data | `structured-data` | - | Schema.org markup |
| 13 | Canonical URLs | `canonical-urls` | - | Duplicate content prevention |
| 14 | Open Graph Tags | `open-graph` | Social Media Preview | Facebook sharing optimization |
| 15 | Twitter Cards | `twitter-cards` | Social Media Preview | Twitter sharing optimization |
| 16 | Robots.txt | `robots-txt` | - | Crawler instructions |
| 17 | XML Sitemap | `xml-sitemap` | - | Search engine discovery |

---

## Architecture

### URL Structure

```
/seo-guides/                    → Guide index page (list all 17 guides)
/seo-guides/meta-title          → Meta Title guide
/seo-guides/meta-description    → Meta Description guide
/seo-guides/h1-headings         → H1 Headings guide
... (all 17 indicators)
```

### Routing Configuration

**Next.js Static Export (`output: 'export'`):**
- Each guide is a static page under `app/seo-guides/[slug]/page.tsx`
- Dynamic routing via `useParams()` for client-side parameter extraction
- All pages pre-rendered at build time

**Apache .htaccess (Production):**
```apache
# SEO Guide routes - static files
RewriteRule ^seo-guides/?$ /seo-guides/index.html [L]
RewriteRule ^seo-guides/([^/]+)/?$ /seo-guides/$1/index.html [L]
```

### Page Structure

Each guide page follows a consistent template:

```
┌─────────────────────────────────────────┐
│  Header: Breadcrumb + Navigation        │
├─────────────────────────────────────────┤
│  Hero: Title + Summary + Key Points     │
├─────────────────────────────────────────┤
│  Content:                               │
│  - What is [Indicator]?                 │
│  - Why it matters for SEO               │
│  - How to optimize                      │
│  - Common mistakes                      │
│  - Best practices checklist             │
├─────────────────────────────────────────┤
│  Tool Embed (if applicable):            │
│  - Interactive tool component           │
│  - CTA to use the tool                  │
├─────────────────────────────────────────┤
│  Related Guides: Links to 2-3 guides    │
├─────────────────────────────────────────┤
│  Footer: Navigation to next/prev guide  │
└─────────────────────────────────────────┘
```

---

## Frontend Components

### Guide Page Template

```tsx
// app/seo-guides/[slug]/page.tsx
interface GuidePageProps {
  params: { slug: string };
}

export default function GuidePage({ params }: GuidePageProps) {
  const guide = getGuideBySlug(params.slug);
  
  return (
    <GuideLayout>
      <GuideBreadcrumb slug={params.slug} />
      <GuideHero guide={guide} />
      <GuideContent guide={guide} />
      {guide.toolSlug && <ToolEmbed toolSlug={guide.toolSlug} />}
      <RelatedGuides guides={guide.relatedGuides} />
      <GuideNavigation prev={guide.prev} next={guide.next} />
    </GuideLayout>
  );
}
```

### Guide Data Structure

```typescript
interface SEOGuide {
  slug: string;
  title: string;
  summary: string;
  category: 'on-page' | 'technical' | 'content';
  toolSlug?: string; // Links to interactive tool
  icon: string; // Emoji or icon name
  readTime: number; // Minutes
  sections: GuideSection[];
  checklist: ChecklistItem[];
  relatedGuides: string[]; // Slugs of related guides
}

interface GuideSection {
  title: string;
  content: string; // Markdown content
  codeExamples?: CodeExample[];
}

interface ChecklistItem {
  text: string;
  priority: 'critical' | 'important' | 'recommended';
}
```

### Shared Components

| Component | Purpose |
|-----------|---------|
| `GuideLayout` | Consistent page wrapper with header/footer |
| `GuideBreadcrumb` | Navigation breadcrumb: Home > SEO Guides > [Guide] |
| `GuideHero` | Title, summary, icon, read time |
| `GuideContent` | Rendered markdown sections |
| `ToolEmbed` | Embed interactive tool from FEAT-016 |
| `RelatedGuides` | Grid of related guide cards |
| `GuideNavigation` | Previous/Next guide links |
| `GuideIndex` | Grid listing all 17 guides |

---

## Content Requirements

### Each Guide Must Include

1. **Definition**: Clear explanation of the indicator
2. **SEO Impact**: Why it matters for search rankings
3. **Optimization Guide**: Step-by-step how to improve
4. **Best Practices**: Do's and don'ts
5. **Checklist**: Actionable items for users
6. **Tool Integration**: Link to interactive tool (where applicable)

### Content Sources

- Legacy guide content (user-provided)
- SEO best practices (industry standard)
- Integration with FEAT-016 tools

### Content Storage

- Static content in TypeScript/JSON files (`lib/guides/`)
- Markdown support for rich content
- Translatable structure for future i18n

---

## Navigation & Discovery

### Guide Index Page

- Grid layout showing all 17 guides
- Filter by category (on-page, technical, content)
- Search by title
- Visual indicator for guides with tools

### Cross-Linking Strategy

- Related guides shown at bottom of each page
- Sequential navigation (prev/next)
- Links from tool results to relevant guides
- Links from guide content to tools

---

## Integration with Existing Features

### FEAT-016: Interactive SEO Tools

| Guide | Embedded Tool |
|-------|---------------|
| Meta Title | Meta Tag Analyzer |
| Meta Description | Meta Tag Analyzer |
| Mobile-Friendliness | Mobile-Friendly Checker |
| Open Graph Tags | Social Media Preview |
| Twitter Cards | Social Media Preview |
| Keyword Density (future) | Keyword Density Checker |

### Tool Results Table Links

The analysis results table (from FEAT-019 progressive analysis) will link each indicator to its corresponding guide page:

```tsx
<Link href={`/seo-guides/${indicatorSlug}`}>
  {indicatorName}
  <ExternalLinkIcon />
</Link>
```

---

## SEO Optimization

### Meta Tags per Guide

```html
<title>{title} - SEO Guide | SEOIndicator</title>
<meta name="description" content="{summary}" />
<meta name="keywords" content="{indicator}, seo, optimization" />
<link rel="canonical" href="https://seoindicator.com/seo-guides/{slug}" />
```

### Schema Markup

```json
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "{title}",
  "description": "{summary}",
  "step": [
    { "@type": "HowToStep", "name": "...", "text": "..." }
  ]
}
```

### Open Graph / Twitter Cards

- Optimized for social sharing
- Custom OG images per guide category
- Consistent branding

---

## Routing Implementation

### Next.js Configuration

```js
// next.config.js
module.exports = {
  output: 'export',
  trailingSlash: true, // For clean URLs
}
```

### Static Generation

```tsx
// app/seo-guides/[slug]/page.tsx
export async function generateStaticParams() {
  return GUIDE_SLUGS.map(slug => ({ slug }));
}
```

### Apache .htaccess (Production)

```apache
# SEO Guides - Static routing with trailing slash
<IfModule mod_rewrite.c>
  RewriteEngine On
  
  # Handle /seo-guides
  RewriteRule ^seo-guides$ /seo-guides/ [R=301,L]
  RewriteRule ^seo-guides/$ /seo-guides/index.html [L]
  
  # Handle /seo-guides/[slug]
  RewriteRule ^seo-guides/([^/]+)$ /seo-guides/$1/ [R=301,L]
  RewriteRule ^seo-guides/([^/]+)/$ /seo-guides/$1/index.html [L]
</IfModule>
```

---

## Asset Management

### Image Assets

| Asset Type | Location | Purpose |
|------------|----------|---------|
| Guide icons | `/public/guides/icons/` | Category icons |
| OG images | `/public/guides/og/` | Social sharing |
| Diagrams | `/public/guides/diagrams/` | Visual explanations |

### CSS/Styling

- Uses existing Shadcn/UI components
- Consistent with app styling
- Mobile-responsive by default

---

## Tasks Breakdown

### Phase 1: Foundation
1. **TASK-20-1**: Create guide data structure and TypeScript types
2. **TASK-20-2**: Create guide index page component
3. **TASK-20-3**: Create guide page template component
4. **TASK-20-4**: Set up routing with static params generation

### Phase 2: Content
5. **TASK-20-5**: Write content for Meta Title guide
6. **TASK-20-6**: Write content for Meta Description guide
7. **TASK-20-7**: Write content for H1 Headings guide
8. **TASK-20-8**: Write content for Heading Hierarchy guide
9. **TASK-20-9**: Write content for Image Alt Text guide
10. **TASK-20-10**: Write content for Image Optimization guide
11. **TASK-20-11**: Write content for Internal Links guide
12. **TASK-20-12**: Write content for External Links guide
13. **TASK-20-13**: Write content for Page Speed guide
14. **TASK-20-14**: Write content for Mobile-Friendliness guide
15. **TASK-20-15**: Write content for HTTPS/Security guide
16. **TASK-20-16**: Write content for Structured Data guide
17. **TASK-20-17**: Write content for Canonical URLs guide
18. **TASK-20-18**: Write content for Open Graph Tags guide
19. **TASK-20-19**: Write content for Twitter Cards guide
20. **TASK-20-20**: Write content for Robots.txt guide
21. **TASK-20-21**: Write content for XML Sitemap guide

### Phase 3: Integration
22. **TASK-20-22**: Integrate tool embedding for Meta Tag Analyzer guides
23. **TASK-20-23**: Integrate tool embedding for Mobile-Friendly guide
24. **TASK-20-24**: Integrate tool embedding for Social Media Preview guides
25. **TASK-20-25**: Add links from results table to guides

### Phase 4: Production
26. **TASK-20-26**: Configure Apache .htaccess for clean URLs
27. **TASK-20-27**: Add schema markup to all guides
28. **TASK-20-28**: Create OG images for guides
29. **TASK-20-29**: Unit tests for guide components
30. **TASK-20-30**: Integration tests for routing

---

## Dependencies

### Existing
- FEAT-016: Interactive SEO Tools (tool embedding)
- FEAT-019: Progressive Analysis (results table links)
- Next.js static export configuration
- Shadcn/UI component library

### External
- None (all content is original or user-provided)

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Content quality inconsistency | Medium | Medium | Create content template, review process |
| Routing issues on production | Low | Medium | Test .htaccess thoroughly, provide fallbacks |
| Broken tool integration | Low | Medium | Graceful fallback if tool unavailable |
| SEO duplicate content | Low | Low | Canonical URLs, unique content per guide |

---

## Testing Strategy

### Unit Tests
- Guide data loading
- Component rendering
- Navigation links
- Tool embedding

### Integration Tests
- Route accessibility (all 17 guides)
- Static generation
- Cross-linking
- Mobile responsiveness

### E2E Tests
- Full navigation flow
- Tool interaction from guide
- Results table link to guide

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Guide pages accessible | 17/17 |
| Avg time on guide page | >60 seconds |
| Tool usage from guides | >10% of tool traffic |
| Bounce rate from guides | <50% |
| Internal link clicks | >20% of guide visitors |

---

## Status History

| Date | Status | Agent | Notes |
|------|--------|-------|-------|
| 2026-02-28 | Specification In Progress | Architect | Initial specification created |
| 2026-02-28 | To Break Into Tasks | Architect | Specification complete, 30 tasks defined, ready for SpecSystemAnalyst |
| 2026-03-01 | To Prioritize | SpecSystemAnalyst | Task breakdown complete - 30 tasks created across 4 phases |

---

*Created by Architect from FEAT-020*
