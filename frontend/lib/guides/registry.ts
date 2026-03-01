/**
 * SEO Guide Registry and Helper Functions
 * Task: TASK-20-1 - Create Guide Data Structure and TypeScript Types
 * 
 * This module provides the central registry for all SEO guides and helper functions
 * for accessing guide data.
 */

import {
  SEOGuide,
  GuideCategory,
} from './types';

// ============================================================================
// Guide Definitions
// ============================================================================

/**
 * All 17 SEO Guide definitions
 * Content sections will be populated in Phase 2 tasks
 */
const GUIDES: SEOGuide[] = [
  // On-Page SEO Guides
  {
    slug: 'meta-title',
    title: 'Meta Title Optimization',
    summary: 'Learn how to craft compelling page titles that improve click-through rates and search rankings.',
    category: 'on-page',
    toolSlug: 'meta-tag-analyzer',
    icon: '📝',
    readTime: 5,
    sections: [],
    checklist: [],
    relatedGuides: ['meta-description', 'h1-headings', 'open-graph'],
  },
  {
    slug: 'meta-description',
    title: 'Meta Description Best Practices',
    summary: 'Write descriptions that entice users to click and provide search engines with context.',
    category: 'on-page',
    toolSlug: 'meta-tag-analyzer',
    icon: '📋',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['meta-title', 'open-graph', 'twitter-cards'],
  },
  {
    slug: 'h1-headings',
    title: 'H1 Heading Structure',
    summary: 'Understand how to use H1 tags effectively for SEO and accessibility.',
    category: 'on-page',
    icon: '🔤',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['heading-hierarchy', 'meta-title'],
  },
  {
    slug: 'heading-hierarchy',
    title: 'Heading Hierarchy (H1-H6)',
    summary: 'Create a logical content structure using proper heading order for better UX and SEO.',
    category: 'on-page',
    icon: '📑',
    readTime: 5,
    sections: [],
    checklist: [],
    relatedGuides: ['h1-headings', 'structured-data'],
  },
  {
    slug: 'image-alt-text',
    title: 'Image Alt Text Optimization',
    summary: 'Write descriptive alt text that improves accessibility and image search visibility.',
    category: 'on-page',
    icon: '🖼️',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['image-optimization', 'structured-data'],
  },
  {
    slug: 'image-optimization',
    title: 'Image Optimization for Web',
    summary: 'Optimize images for faster loading without sacrificing quality.',
    category: 'on-page',
    icon: '⚡',
    readTime: 4,
    sections: [
      {
        title: 'What is Image Optimization?',
        content: `**Image optimization** is the process of delivering high-quality images in the right format, size, and resolution while keeping file sizes minimal. Images typically account for **50%+ of a webpage's total size**, making optimization critical for performance.

The goal: **reduce file size without sacrificing visual quality**. Modern techniques include:

- **Choosing the right format** (WebP, AVIF vs JPEG, PNG)
- **Compression** (lossy vs lossless)
- **Resizing** to match display dimensions
- **Lazy loading** to defer off-screen images

When done correctly, image optimization can reduce image weight by **70-90%** while maintaining visual fidelity.`,
        codeExamples: [],
      },
      {
        title: 'Why Image Optimization Matters',
        content: `Image optimization directly impacts your **Core Web Vitals** scores and SEO performance.

### Core Web Vitals Impact

**Largest Contentful Paint (LCP)** measures when the largest content element becomes visible. Images are often the LCP element—unoptimized images delay this metric.

**Cumulative Layout Shift (CLS)** occurs when images load without reserved space. Setting explicit width and height attributes prevents layout shifts.

### Key Benefits

- **Faster crawl rates** and improved rankings
- **Better user engagement** with lower bounce rates
- **Mobile performance** essential for mobile-first indexing
- **53% of mobile users abandon sites** that take longer than 3 seconds to load

Optimizing images is one of the **highest-impact, lowest-effort** improvements you can make.`,
        codeExamples: [],
      },
      {
        title: 'Image File Formats',
        content: `Choosing the right format is the foundation of image optimization.

### Modern Formats

**WebP** — Best all-around choice
- **25-35% smaller** than JPEG at equivalent quality
- Supports lossy, lossless, and transparency
- **Browser support**: 97%+ globally

**AVIF** — Next-generation format
- **50% smaller** than JPEG at same quality
- Supports HDR and wide color gamut
- **Browser support**: 90%+ and growing

### Legacy Formats

**JPEG** — Best for photographs with many colors (when WebP/AVIF unavailable)

**PNG** — Best for graphics with transparency (logos, icons, screenshots)

**Pro tip**: Use the &lt;picture&gt; element to serve modern formats with fallbacks.`,
        codeExamples: [
          {
            language: 'html',
            code: `<picture>
  <source srcset="image.avif" type="image/avif">
  <source srcset="image.webp" type="image/webp">
  <img src="image.jpg" alt="Description" width="800" height="600">
</picture>`,
            description: 'Picture element with AVIF/WebP fallback to JPEG',
          },
        ],
      },
      {
        title: 'Compression and Resizing',
        content: `Proper compression and resizing dramatically reduce file sizes.

### Lossy vs Lossless

**Lossy compression** permanently removes data for smaller files (60-80% savings). Best for photos.

**Lossless compression** reduces size without quality loss (10-50% savings). Best for graphics and logos.

### Best Practices

1. **Match display dimensions** — Don't upload 4000px images for 400px displays
2. **Set explicit dimensions** — Prevent CLS with width/height attributes
3. **Use responsive images** — Serve different sizes with srcset

### Recommended Quality Settings

| Format | Quality |
|--------|---------|
| WebP | 75-85% |
| AVIF | 50-65% |
| JPEG | 75-85% |

### Free Tools

**Squoosh** (squoosh.app), **TinyPNG**, **ImageMagick**, **Sharp** (Node.js)`,
        codeExamples: [],
      },
      {
        title: 'Lazy Loading Implementation',
        content: `Lazy loading defers off-screen images until the user scrolls near them, improving initial page load.

### Native Lazy Loading

The simplest approach uses the HTML \`loading="lazy"\` attribute. Supported by all modern browsers with zero JavaScript overhead.

### Next.js Image Component

If using Next.js, the &lt;Image&gt; component handles optimization automatically:
- Automatic format selection (WebP/AVIF)
- Responsive sizing and lazy loading
- Prevents layout shifts

**Important:** Never lazy load above-fold images (hero, LCP candidates)—this delays critical content.`,
        codeExamples: [
          {
            language: 'html',
            code: `<img 
  src="gallery-image.webp" 
  alt="Gallery image description" 
  width="800" 
  height="600"
  loading="lazy"
>`,
            description: 'Native lazy loading with WebP format',
          },
          {
            language: 'jsx',
            code: `import Image from 'next/image';

<Image
  src="/gallery-image.webp"
  alt="Gallery image description"
  width={800}
  height={600}
  loading="lazy"
/>`,
            description: 'Next.js Image component with lazy loading',
          },
        ],
      },
    ],
    checklist: [
      { text: 'Use WebP or AVIF format for all images', priority: 'critical' },
      { text: 'Implement lazy loading for below-fold images', priority: 'critical' },
      { text: 'Compress images to 75-85% quality for optimal size', priority: 'important' },
      { text: 'Set explicit width and height attributes to prevent CLS', priority: 'important' },
      { text: 'Use responsive images with srcset for different devices', priority: 'important' },
      { text: 'Add descriptive alt text to all images for accessibility', priority: 'important' },
      { text: 'Consider Next.js Image component for automatic optimization', priority: 'recommended' },
    ],
    relatedGuides: ['image-alt-text', 'page-speed'],
  },
  {
    slug: 'internal-links',
    title: 'Internal Linking Strategy',
    summary: 'Build a strong site architecture through strategic internal linking to improve crawlability, distribute page authority, and enhance user navigation.',
    category: 'on-page',
    icon: '🔗',
    readTime: 5,
    sections: [
      {
        title: 'What are Internal Links?',
        content: `**Internal links** are hyperlinks that connect one page of your website to another page on the same domain. Unlike external links that point to other websites, internal links keep users within your site's ecosystem, guiding them through your content journey.

Every internal link consists of two key components: the **destination URL** (where the link points) and the **anchor text** (the clickable text users see). These elements work together to create pathways through your website.

### Types of Internal Links

**Navigational links** appear in your main menu, footer, and sidebar—helping users find key pages.

**Contextual links** appear within your content, connecting related articles and topics. These carry the most SEO weight because they're surrounded by relevant content.

**Footer links** provide quick access to important pages like privacy policies, contact info, and sitemap.

**Breadcrumb links** show users their location within your site hierarchy and help search engines understand your site structure.

Internal linking is one of the few SEO strategies you have complete control over—use it wisely.`,
        codeExamples: [],
      },
      {
        title: 'Why Internal Links Matter for SEO',
        content: `Internal linking is one of the most powerful yet overlooked SEO strategies. Here's why it matters:

### Search Engine Crawlability

Search engines like Google use links to discover and index pages. A strong internal linking structure ensures crawlers can find all your important content. Pages without any internal links pointing to them are called **orphan pages**—they're nearly invisible to search engines.

### Page Authority Distribution

When one page earns backlinks from external sites, it accumulates **link equity** (also called "link juice"). Internal links pass this authority to other pages on your site. Strategic internal linking helps distribute authority from your strongest pages to those that need a boost.

### User Navigation and Engagement

Clear internal linking helps users discover related content, increasing time on site and reducing bounce rates. When visitors can easily find what they're looking for, they're more likely to convert.

### Ranking Power

Pages with more internal links tend to rank higher. Google's algorithms use internal link structure to understand which pages are most important on your site. Your most-linked pages signal topical relevance and priority.

### Site Architecture Clarity

A logical linking structure helps search engines understand your site's hierarchy and content relationships—key for topical authority.`,
        codeExamples: [],
      },
      {
        title: 'Site Architecture and Link Equity',
        content: `Your internal linking structure defines how **link equity** flows through your website. Understanding this concept is essential for maximizing your SEO potential.

### The Hub-and-Spoke Model

The **hub-and-spoke** (or pillar-cluster) model organizes content around central "hub" pages that link to related "spoke" pages. Hub pages target broad keywords; spoke pages target specific long-tail keywords. All spoke pages link back to the hub, creating a tightly connected content cluster.

\`\`\`
Hub Page (Comprehensive Guide)
    ├── Spoke Page 1 (Specific Topic)
    ├── Spoke Page 2 (Specific Topic)
    └── Spoke Page 3 (Specific Topic)
\`\`\`

This structure:
- Establishes topical authority
- Distributes link equity efficiently
- Helps search engines understand content relationships
- Improves user navigation between related topics

### Link Depth and Crawl Budget

**Link depth** refers to how many clicks it takes to reach a page from your homepage. Keep important pages within **3 clicks** of the homepage. Pages buried deeper may be crawled less frequently and carry less authority.

### URL Structure Alignment

Your URL structure should reflect your internal linking hierarchy:
- \`/blog/\` — Blog hub
- \`/blog/seo-tips/\` — SEO category
- \`/blog/seo-tips/internal-linking/\` — Specific article

Consistent URLs reinforce your site architecture for both users and search engines.`,
        codeExamples: [
          {
            language: 'text',
            code: `SEO Guide Hub (/guides/)
    ├── Meta Title Guide (/guides/meta-title/)
    ├── Internal Links Guide (/guides/internal-links/)
    └── Page Speed Guide (/guides/page-speed/)

Each spoke links back to the hub and to related spokes.`,
            description: 'Hub-and-spoke internal linking model example',
          },
        ],
      },
      {
        title: 'Anchor Text Best Practices',
        content: `**Anchor text** is the visible, clickable text in a hyperlink. It's one of the strongest signals search engines use to understand what the linked page is about.

### Use Descriptive, Relevant Anchor Text

Good anchor text clearly indicates what users will find on the destination page. This helps both users and search engines understand the link's context.

**Good examples:**
- "meta title optimization guide"
- "our SEO tools"
- "WordPress image optimization"

**Avoid generic text:**
- "click here"
- "read more"
- "learn more"
- "this article"

### Balance Keyword Usage

Using keywords in anchor text helps SEO, but overdoing it can trigger spam filters. Aim for natural, varied anchor text:

- **Exact match**: "internal linking strategy" (use sparingly)
- **Partial match**: "improve your internal linking" (good balance)
- **Branded**: "SEOIndicator's guide" (natural and safe)
- **Generic**: "this guide" (use occasionally, not for key pages)

### Keep It Concise

Anchor text should be brief but descriptive—typically 2-5 words. Long anchor text can dilute the keyword signal and looks unnatural.

### Surround with Context

Place internal links within relevant paragraphs where the surrounding text supports the link's purpose. Context helps both users and search engines understand the relationship between pages.`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Good: Descriptive anchor text -->
<p>Learn how to <a href="/guides/meta-title/">optimize your meta titles</a> for better click-through rates.</p>

<!-- Bad: Generic anchor text -->
<p>Learn how to optimize your meta titles <a href="/guides/meta-title/">here</a>.</p>`,
            description: 'Good vs bad anchor text examples',
          },
        ],
      },
      {
        title: 'Common Internal Linking Mistakes',
        content: `Avoid these common pitfalls that can hurt your SEO performance:

### 1. Orphan Pages

**Orphan pages** have no internal links pointing to them. They're isolated from your site's link structure, making them hard for search engines to find and users to discover.

**Solution**: Regularly audit your site for orphan pages using Google Search Console or crawling tools. Add contextual links from relevant pages.

### 2. Over-Optimization

Stuffing too many internal links with exact-match anchor text looks spammy and can trigger penalties. Keep it natural—vary your anchor text and link only when relevant.

**Solution**: Limit links to what's genuinely helpful. If a paragraph has 5+ internal links, you're probably overdoing it.

### 3. Broken Internal Links

Links pointing to deleted or moved pages (404 errors) waste crawl budget and hurt user experience. They also prevent link equity from flowing properly.

**Solution**: Run monthly broken link audits. Set up 301 redirects for moved pages.

### 4. Excessive Links Per Page

Google has historically recommended keeping links under 100 per page. While not a strict limit, excessive links dilute the value passed by each link.

**Solution**: Focus on quality over quantity. Link to your most important pages. Keep total links (internal + external) under 150 for most pages.

### 5. Ignoring Breadcrumbs

Breadcrumbs improve navigation and help search engines understand site structure. They also create additional internal links automatically.

**Solution**: Implement breadcrumb navigation with schema markup for enhanced search results.`,
        codeExamples: [],
      },
      {
        title: 'Breadcrumbs and Navigation',
        content: `**Breadcrumbs** are secondary navigation elements that show users their location within your site hierarchy. They provide crucial internal linking benefits:

### Benefits of Breadcrumbs

- **User experience**: Users can easily navigate back to parent categories
- **Internal linking**: Automatically creates links to parent pages
- **Search results**: Google may display breadcrumbs in search snippets
- **Site structure**: Reinforces your content hierarchy

### Implementation Best Practices

Use semantic HTML with proper ARIA labels for accessibility. Include breadcrumb schema (JSON-LD) for rich results in Google search.

The example below shows both HTML and JSON-LD implementation for maximum SEO benefit.`,
        codeExamples: [
          {
            language: 'html',
            code: `<nav aria-label="Breadcrumb">
  <ol class="breadcrumb">
    <li><a href="/">Home</a></li>
    <li><a href="/guides/">Guides</a></li>
    <li><a href="/guides/internal-links/" aria-current="page">Internal Links</a></li>
  </ol>
</nav>`,
            description: 'Breadcrumb navigation HTML with accessibility attributes',
          },
          {
            language: 'json',
            code: `{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://example.com/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Guides",
      "item": "https://example.com/guides/"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Internal Links",
      "item": "https://example.com/guides/internal-links/"
    }
  ]
}`,
            description: 'JSON-LD breadcrumb schema for rich search results',
          },
        ],
      },
    ],
    checklist: [
      { text: 'Use descriptive, keyword-rich anchor text (never "click here")', priority: 'critical' },
      { text: 'Implement hub-and-spoke content structure for topical authority', priority: 'important' },
      { text: 'Audit and fix orphan pages regularly (at least monthly)', priority: 'important' },
      { text: 'Keep important pages within 3 clicks from homepage', priority: 'important' },
      { text: 'Add breadcrumb navigation with JSON-LD schema markup', priority: 'recommended' },
      { text: 'Check and fix broken internal links monthly', priority: 'important' },
      { text: 'Limit internal links to relevant, valuable connections', priority: 'recommended' },
    ],
    relatedGuides: ['external-links', 'xml-sitemap'],
  },
  {
    slug: 'external-links',
    title: 'External Link Best Practices',
    summary: 'Use outbound links strategically to build authority and provide value.',
    category: 'on-page',
    icon: '🌐',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['internal-links', 'https-security'],
  },

  // Technical SEO Guides
  {
    slug: 'page-speed',
    title: 'Page Speed Optimization',
    summary: 'Improve Core Web Vitals and page load times for better user experience.',
    category: 'technical',
    icon: '🚀',
    readTime: 7,
    sections: [],
    checklist: [],
    relatedGuides: ['mobile-friendly', 'image-optimization'],
  },
  {
    slug: 'mobile-friendly',
    title: 'Mobile-Friendly Design',
    summary: 'Ensure your site works flawlessly on all mobile devices.',
    category: 'technical',
    toolSlug: 'mobile-friendly-checker',
    icon: '📱',
    readTime: 5,
    sections: [],
    checklist: [],
    relatedGuides: ['page-speed', 'https-security'],
  },
  {
    slug: 'https-security',
    title: 'HTTPS & Security Headers',
    summary: 'Secure your site with SSL and implement security best practices.',
    category: 'technical',
    icon: '🔒',
    readTime: 5,
    sections: [],
    checklist: [],
    relatedGuides: ['mobile-friendly', 'canonical-urls'],
  },
  {
    slug: 'structured-data',
    title: 'Structured Data (Schema.org)',
    summary: 'Implement rich snippets and structured data for enhanced search results.',
    category: 'technical',
    icon: '📊',
    readTime: 6,
    sections: [],
    checklist: [],
    relatedGuides: ['open-graph', 'canonical-urls'],
  },
  {
    slug: 'canonical-urls',
    title: 'Canonical URL Implementation',
    summary: 'Prevent duplicate content issues with proper canonical tags.',
    category: 'technical',
    icon: '🎯',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['https-security', 'xml-sitemap'],
  },
  {
    slug: 'robots-txt',
    title: 'Robots.txt Configuration',
    summary: 'Control how search engines crawl and index your site.',
    category: 'technical',
    icon: '🤖',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['xml-sitemap', 'canonical-urls'],
  },
  {
    slug: 'xml-sitemap',
    title: 'XML Sitemap Creation',
    summary: 'Help search engines discover and index all your important pages.',
    category: 'technical',
    icon: '🗺️',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['robots-txt', 'internal-links'],
  },

  // Content & Social SEO Guides
  {
    slug: 'open-graph',
    title: 'Open Graph Tags',
    summary: 'Optimize how your content appears when shared on Facebook and other platforms.',
    category: 'content',
    toolSlug: 'social-media-preview',
    icon: '👍',
    readTime: 5,
    sections: [],
    checklist: [],
    relatedGuides: ['twitter-cards', 'meta-description', 'structured-data'],
  },
  {
    slug: 'twitter-cards',
    title: 'Twitter Cards',
    summary: 'Create rich Twitter card previews that drive engagement and clicks.',
    category: 'content',
    toolSlug: 'social-media-preview',
    icon: '🐦',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['open-graph', 'meta-description'],
  },
];

// ============================================================================
// Constants
// ============================================================================

/**
 * All guide slugs as a readonly array
 */
export const GUIDE_SLUGS: readonly string[] = GUIDES.map((g) => g.slug);

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Get a guide by its slug
 * @param slug - The guide slug (e.g., 'meta-title')
 * @returns The guide object or null if not found
 */
export function getGuideBySlug(slug: string): SEOGuide | null {
  if (!slug || typeof slug !== 'string') {
    return null;
  }

  // Exact match only - no trimming, case-sensitive
  if (slug === '') {
    return null;
  }

  const guide = GUIDES.find((g) => g.slug === slug);
  return guide ? { ...guide } : null;
}

/**
 * Get all guides (returns copies to ensure immutability)
 * @returns Array of all guides
 */
export function getAllGuides(): SEOGuide[] {
  return GUIDES.map((guide) => ({ ...guide }));
}

/**
 * Get related guides for a given slug
 * @param slug - The guide slug
 * @returns Array of related guide objects (max 3)
 */
export function getRelatedGuides(slug: string): SEOGuide[] {
  if (!slug || typeof slug !== 'string') {
    return [];
  }

  const guide = getGuideBySlug(slug.trim());
  if (!guide) {
    return [];
  }

  // Get related guides based on the guide's relatedGuides array
  const relatedGuides = guide.relatedGuides
    .map((relatedSlug) => getGuideBySlug(relatedSlug))
    .filter((g): g is SEOGuide => g !== null)
    .slice(0, 3);

  return relatedGuides;
}

/**
 * Get guides filtered by category
 * @param category - The category to filter by
 * @returns Array of guides in the specified category
 */
export function getGuidesByCategory(category: GuideCategory): SEOGuide[] {
  return GUIDES.filter((g) => g.category === category).map((guide) => ({ ...guide }));
}

/**
 * Get guides that are linked to a specific tool
 * @param toolSlug - The tool slug to search for
 * @returns Array of guides linked to the specified tool
 */
export function getGuidesByToolSlug(toolSlug: string): SEOGuide[] {
  if (!toolSlug || typeof toolSlug !== 'string') {
    return [];
  }

  return GUIDES.filter((g) => g.toolSlug === toolSlug.trim()).map((guide) => ({ ...guide }));
}

/**
 * Get all guides that have an associated tool
 * @returns Array of guides with toolSlug defined
 */
export function getGuidesWithTools(): SEOGuide[] {
  return GUIDES.filter((g) => g.toolSlug !== undefined).map((guide) => ({ ...guide }));
}

/**
 * Get all guides that do NOT have an associated tool
 * @returns Array of guides without toolSlug
 */
export function getGuidesWithoutTools(): SEOGuide[] {
  return GUIDES.filter((g) => g.toolSlug === undefined).map((guide) => ({ ...guide }));
}
