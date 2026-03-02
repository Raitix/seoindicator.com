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
    sections: [
      {
        title: 'What are External Links?',
        content: `**External links** are hyperlinks that point from your website to pages on a different domain. Unlike internal links that connect pages within your own site, external links send visitors to other websites—providing additional resources, citations, and references.

Every external link consists of the destination URL and the anchor text (clickable text). When you link to another site, you're essentially vouching for that content and directing your users—and search engine crawlers—to that resource.

### External vs Internal Links

| Aspect | External Links | Internal Links |
|--------|----------------|----------------|
| Destination | Different domain | Same domain |
| Purpose | Cite sources, provide resources | Navigation, distribute authority |
| Link equity | Passes to external site | Stays within your site |
| User behavior | Opens new tab (recommended) | Same tab |

External links signal that your content is well-researched and connected to the broader web ecosystem. They're a natural part of a healthy link profile.`,
        codeExamples: [],
      },
      {
        title: 'Why External Links Matter for SEO',
        content: `External linking might seem counterintuitive—why send visitors away? But strategic external linking actually benefits your SEO and user experience in several ways.

### Authority and Credibility

Linking to authoritative sources demonstrates that your content is well-researched and trustworthy. Search engines like Google use external links to understand the context and quality of your content. When you cite reputable sources, you strengthen your **E-E-A-T** signals (Experience, Expertise, Authoritativeness, Trustworthiness).

### User Value

External links provide additional value to your readers by:
- Offering deeper dives on complex topics
- Providing evidence and citations for claims
- Connecting users to tools, resources, and further reading

### Citation and Reference Value

Just as academic papers cite sources, web content benefits from linking to original research, data sources, and authoritative references. This citation behavior helps search engines understand your content's place in the information ecosystem.

### Relationship Building

Linking to other websites can foster relationships with peers in your industry. Many site owners track their backlinks and may reciprocate with links or social shares, expanding your reach.`,
        codeExamples: [],
      },
      {
        title: 'Security Attributes (noopener, noreferrer, nofollow)',
        content: `When linking to external sites, especially with \`target="_blank"\`, security attributes protect your users and your site from potential vulnerabilities.

### The target="_blank" Security Risk

Links that open in new tabs using \`target="_blank"\` create a potential security vulnerability called **tabnabbing**. The opened page gains access to your page via \`window.opener\`, allowing it to:
- Redirect your page to a phishing site
- Access information about your users

### noopener and noreferrer

**noopener** prevents the opened page from accessing your page's \`window.opener\` property, blocking tabnabbing attacks. **noreferrer** goes further by also hiding the referrer information—the external site won't know where the traffic came from.

**Best practice:** Always use both \`noopener\` and \`noreferrer\` together with \`target="_blank"\` for maximum security.

### When to Use Each Attribute

| Attribute | Purpose | When to Use |
|-----------|---------|-------------|
| noopener | Blocks tabnabbing | All target="_blank" links |
| noreferrer | Hides referrer | When you don't want to reveal your URL |
| nofollow | Prevents link equity passing | Sponsored/UGC content |

The code example below shows the correct, secure pattern.`,
        codeExamples: [
          {
            language: 'html',
            code: `<a href="https://example.com" target="_blank" rel="noopener noreferrer">
  Visit Example Site
</a>`,
            description: 'Secure external link with noopener and noreferrer attributes',
          },
        ],
      },
      {
        title: 'When to Use Nofollow',
        content: `The \`nofollow\` attribute tells search engines not to pass link equity (sometimes called "link juice") to the linked page. It's essentially saying "I'm linking here, but I'm not endorsing this content."

### Google's Link Attribute Guidelines

Google recognizes three link attribute types:

**rel="sponsored"** — For paid links, sponsorships, and advertising relationships. Required for affiliate links and paid placements.

**rel="ugc"** — For User-Generated Content like comments, forum posts, and user-submitted content. Helps manage links you don't directly control.

**rel="nofollow"** — For cases where you want to link but not endorse. This is also appropriate when you're unsure about the quality of the destination.

### When to Use Each

| Scenario | Attribute |
|----------|-----------|
| Paid/affiliate links | \`rel="sponsored"\` or \`rel="nofollow sponsored"\` |
| User comments/forums | \`rel="ugc"\` or \`rel="nofollow ugc"\` |
| Untrusted links | \`rel="nofollow"\` |
| Editorial links to trusted sites | No special attribute needed |

You can combine attributes—for example, \`rel="nofollow noopener noreferrer"\` for sponsored links that open in new tabs.`,
        codeExamples: [
          {
            language: 'html',
            code: `<a href="https://sponsored-site.com" rel="nofollow sponsored">
  Sponsored Partner
</a>

<a href="https://user-comment-link.com" rel="ugc">
  User Submitted Link
</a>`,
            description: 'Sponsored link and UGC link with appropriate attributes',
          },
        ],
      },
      {
        title: 'Common External Linking Mistakes',
        content: `Avoid these common pitfalls when linking to external sites:

### 1. Missing Security Attributes on target="_blank"

Opening external links in new tabs without \`noopener\` and \`noreferrer\` creates a security vulnerability. Always include these attributes with \`target="_blank"\`.

**Impact:** Users could be exposed to tabnabbing attacks.

### 2. Excessive External Links

Too many external links can dilute your page's value and send users away from your content. Keep external links relevant and purposeful.

**Guideline:** Focus on quality over quantity. 3-5 well-chosen external links are better than 20 random ones.

### 3. Broken External Links (Link Rot)

External pages move, change, or disappear. Broken links (404 errors) hurt user experience and waste crawl budget.

**Solution:** Periodically audit external links using tools like Screaming Frog, Ahrefs, or Google Search Console. Fix or remove broken links.

### 4. Linking to Low-Quality or Spammy Sites

Linking to untrustworthy sites can harm your own site's reputation. Search engines may associate your site with "bad neighborhoods."

**Guideline:** Before linking, check the destination site's quality, reputation, and security (HTTPS). Avoid linking to:
- Sites with excessive ads or pop-ups
- Known spam or malware sites
- Sites with poor content quality

### 5. Not Opening External Links in New Tabs

For user experience, external links should typically open in new tabs so users don't lose their place on your site.`,
        codeExamples: [],
      },
    ],
    checklist: [
      { text: 'Add rel="noopener noreferrer" to all target="_blank" links', priority: 'critical' },
      { text: 'Use rel="nofollow sponsored" for paid and affiliate links', priority: 'critical' },
      { text: 'Link to reputable, authoritative sources only', priority: 'important' },
      { text: 'Check external links periodically for broken pages (404s)', priority: 'important' },
      { text: 'Use rel="ugc" for user-generated content links', priority: 'important' },
      { text: 'Open external links in new tabs for better UX', priority: 'recommended' },
      { text: 'Avoid excessive external links on a single page', priority: 'recommended' },
    ],
    relatedGuides: ['internal-links', 'https-security'],
  },

  // Technical SEO Guides
  {
    slug: 'page-speed',
    title: 'Page Speed Optimization',
    summary: 'Improve Core Web Vitals and page load times for better user experience, higher search rankings, and increased conversions.',
    category: 'technical',
    icon: '🚀',
    readTime: 7,
    sections: [
      {
        title: 'What is Page Speed?',
        content: `**Page speed** measures how quickly a web page loads and becomes interactive for users. It encompasses multiple metrics that capture different aspects of the loading experience—from the initial server response to when the page is fully usable.

Page speed isn't a single number. It's a collection of metrics that together paint a complete picture of performance:

### Key Page Load Metrics

**First Contentful Paint (FCP)** — When the first piece of content (text, image, or canvas) appears on screen. A fast FCP gives users confidence the page is loading.

**Largest Contentful Paint (LCP)** — When the largest content element becomes visible. This is often a hero image or main heading. LCP is a Core Web Vital.

**Time to Interactive (TTI)** — When the page becomes fully interactive and responsive to user input. A page can appear loaded but still be unresponsive if JavaScript is still processing.

**Total Blocking Time (TBT)** — The total time the page was blocked from responding to user input due to long JavaScript tasks.

**Speed Index** — How quickly content is visually displayed during page load. A lower score means users see content faster.

### Why Seconds Matter

Studies consistently show that users expect pages to load in **2 seconds or less**. Every additional second of delay increases bounce rates and decreases conversions. In the era of mobile-first indexing, page speed is both a ranking factor and a critical user experience element.`,
        codeExamples: [],
      },
      {
        title: 'Core Web Vitals',
        content: `**Core Web Vitals** are Google's standardized metrics for measuring user experience. They focus on three key aspects: loading, interactivity, and visual stability. These metrics directly impact search rankings and should be at the heart of your optimization efforts.

### Largest Contentful Paint (LCP)

LCP measures when the largest visible content element renders on screen. It should occur within **2.5 seconds** of the page starting to load.

**Common causes of poor LCP:**
- Slow server response times (TTFB)
- Large, unoptimized images
- Render-blocking JavaScript and CSS
- Client-side rendering delays

**How to improve LCP:**
- Optimize server response time to under 600ms
- Use a CDN for faster content delivery
- Preload critical resources like hero images
- Optimize and compress images

### First Input Delay (FID)

FID measures the time from when a user first interacts with your page (clicks a link, taps a button) to when the browser can respond. A good FID is **100 milliseconds or less**.

**Common causes of poor FID:**
- Long JavaScript tasks blocking the main thread
- Heavy third-party scripts
- Excessive event handlers

**How to improve FID:**
- Break up long JavaScript tasks
- Use async/defer for non-critical scripts
- Minimize main thread work
- Remove unused JavaScript

### Cumulative Layout Shift (CLS)

CLS measures visual stability—how much elements shift around during loading. A good CLS score is **0.1 or less**.

**Common causes of poor CLS:**
- Images without explicit dimensions
- Ads or embeds that push content
- Dynamically injected content
- Web fonts causing FOUT/FOIT

**How to improve CLS:**
- Always set width and height on images
- Reserve space for ad slots and embeds
- Use font-display: swap for web fonts
- Avoid inserting content above existing content

### Interaction to Next Paint (INP)

INP is the newest Core Web Vital, replacing FID in March 2024. It measures responsiveness throughout the entire page session, not just the first interaction. A good INP is **200 milliseconds or less**.

INP captures the worst-case interaction latency, ensuring your page remains responsive even during complex operations.`,
        codeExamples: [
          {
            language: 'text',
            code: `Core Web Vitals Thresholds (Good / Needs Improvement / Poor)

LCP: < 2.5s / 2.5s–4s / > 4s
FID: < 100ms / 100ms–300ms / > 300ms
INP: < 200ms / 200ms–500ms / > 500ms
CLS: < 0.1 / 0.1–0.25 / > 0.25`,
            description: 'Core Web Vitals threshold reference table',
          },
        ],
      },
      {
        title: 'Why Speed Matters',
        content: `Page speed isn't just a technical metric—it directly impacts your bottom line. From search rankings to user satisfaction to conversion rates, speed touches every aspect of online success.

### SEO and Search Rankings

Google has used page speed as a ranking factor since 2010 for desktop and 2018 for mobile searches. With the Page Experience Update in 2021, Core Web Vitals became official ranking signals.

**Key SEO impacts:**
- Faster pages are crawled more efficiently
- Good Core Web Vitals scores improve visibility
- Poor speed can trigger mobile-first indexing issues
- Google's crawl budget is wasted on slow pages

### User Experience and Engagement

Speed shapes user perception from the first impression. A fast-loading site signals professionalism and reliability.

**User behavior statistics:**
- **53% of mobile users abandon sites** that take longer than 3 seconds to load
- **Every 1-second delay** reduces page views by 11%
- **Bounce rates increase** by 32% when page load time goes from 1 to 3 seconds
- **79% of shoppers** who experience dissatisfaction with website performance are less likely to buy again

### Conversion and Revenue Impact

The relationship between speed and conversions is well-documented. Faster pages convert better across industries.

**Real-world conversion data:**
- Walmart found that **every 1-second improvement** in load time increased conversions by 2%
- Amazon calculated that a **100ms delay** cost 1% in sales
- Pinterest reduced perceived wait time by 40% and saw a **15% increase in sign-ups**
- BBC found they lost **10% of users** for every additional second of load time

### Mobile Performance

With mobile-first indexing, your mobile page speed directly impacts rankings. Mobile networks (3G, 4G, 5G) introduce latency that desktop connections don't face. Optimizing for mobile means:

- Minimizing JavaScript payload
- Using responsive images
- Implementing aggressive caching
- Reducing server round trips`,
        codeExamples: [],
      },
      {
        title: 'Optimization Techniques',
        content: `Improving page speed requires a multi-faceted approach. Here are the most effective optimization techniques, organized by impact and implementation complexity.

### 1. Browser Caching

Caching stores copies of your site's resources locally on users' devices, eliminating the need to re-download them on repeat visits. Set appropriate cache-control headers for static assets.

**Recommended cache durations:**
- HTML pages: 0–1 hour (dynamic content)
- CSS/JavaScript: 1 year (with versioned filenames)
- Images: 1 year (with versioned filenames)
- Fonts: 1 year (rarely change)

### 2. Content Delivery Network (CDN)

A CDN distributes your content across multiple servers worldwide, serving users from the nearest location. This dramatically reduces latency and server load.

**Popular CDN options:**
- Cloudflare (free tier available)
- AWS CloudFront
- Fastly
- BunnyCDN

### 3. Code Minification and Compression

Minification removes whitespace, comments, and unnecessary characters from code. Compression (Gzip or Brotli) further reduces file sizes during transfer.

**Impact:** Minification typically reduces JavaScript/CSS by 20-30%; compression adds another 60-70% reduction.

### 4. Image Optimization

Images often account for 50%+ of page weight. Optimization includes:
- Using modern formats (WebP, AVIF)
- Proper compression (75-85% quality)
- Responsive images with srcset
- Lazy loading for below-fold images

### 5. JavaScript and CSS Optimization

Render-blocking resources delay page display. Optimize by:
- Using defer/async on scripts
- Inlining critical CSS
- Removing unused CSS/JavaScript
- Code splitting for large bundles

### 6. Server Optimization

Backend improvements yield significant gains:
- Reduce Time to First Byte (TTFB) to under 600ms
- Enable HTTP/2 or HTTP/3
- Implement server-side caching
- Use efficient database queries`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Preload critical resources -->
<link rel="preload" href="/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin>

<!-- Prefetch resources for next navigation -->
<link rel="prefetch" href="/about" as="document">`,
            description: 'Resource hints: preload critical assets, prefetch next pages',
          },
          {
            language: 'html',
            code: `<!-- Defer non-critical JavaScript -->
<script src="analytics.js" defer></script>

<!-- Async for independent scripts -->
<script src="ads.js" async></script>

<!-- Difference: defer executes in order, async executes ASAP when ready -->`,
            description: 'Script loading with defer (ordered) and async (independent)',
          },
          {
            language: 'html',
            code: `<img 
  src="hero-image.webp" 
  alt="Hero image description" 
  width="1200" 
  height="630"
  loading="lazy"
  decoding="async"
>`,
            description: 'Image with lazy loading and explicit dimensions',
          },
          {
            language: 'apache',
            code: `# .htaccess - Browser caching configuration
<IfModule mod_expires.c>
  ExpiresActive On
  
  # Images
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  
  # CSS and JavaScript
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
  
  # Fonts
  ExpiresByType font/woff2 "access plus 1 year"
</IfModule>

# Enable Gzip compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/css application/javascript
</IfModule>`,
            description: 'Apache .htaccess for browser caching and Gzip compression',
          },
          {
            language: 'nginx',
            code: `# nginx.conf - Gzip and Brotli compression
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml;
gzip_min_length 256;

# Brotli (if installed)
brotli on;
brotli_types text/plain text/css application/javascript;
brotli_comp_level 4;`,
            description: 'Nginx configuration for Gzip and Brotli compression',
          },
        ],
      },
      {
        title: 'Measurement Tools',
        content: `Regularly measuring page speed ensures your optimizations are effective and helps identify new opportunities for improvement. Use multiple tools for a complete picture.

### Google PageSpeed Insights

The official Google tool that analyzes page speed and provides both lab and field data. Shows Core Web Vitals status and specific optimization recommendations.

**URL:** pagespeed.web.dev

**Best for:** Quick audits, Core Web Vitals verification, mobile vs desktop comparison

### Google Lighthouse

An open-source automated tool built into Chrome DevTools. Runs comprehensive audits on performance, accessibility, SEO, and best practices.

**How to use:**
1. Open Chrome DevTools (F12)
2. Navigate to Lighthouse tab
3. Select categories and run audit

**Best for:** Detailed technical analysis, local testing, CI/CD integration

### GTmetrix

A popular third-party tool that combines Lighthouse data with historical tracking and waterfall charts. Offers free and paid tiers.

**URL:** gtmetrix.com

**Features:**
- Historical performance tracking
- Waterfall visualization
- Video playback of page load
- Regional testing locations

### WebPageTest

A sophisticated testing tool that provides detailed waterfall charts, video capture, and content breakdown. Excellent for deep performance analysis.

**URL:** webpagetest.org

**Features:**
- Test from multiple locations and devices
- Connection speed throttling (3G, 4G, etc.)
- Detailed waterfall charts
- Filmstrip view of loading progress

### Web.dev Measure

Google's developer-focused tool that provides actionable recommendations based on Lighthouse audits.

**Best for:** Developer-focused recommendations, implementation guidance

### Interpreting Scores

When reviewing performance reports, focus on:

1. **Core Web Vitals status** — Are all metrics in the "good" range?
2. **Opportunities** — High-impact fixes with estimated savings
3. **Diagnostics** — Technical issues affecting performance
4. **Field data vs Lab data** — Real user data vs simulated tests

**Pro tip:** Run tests multiple times and across different locations. Performance can vary significantly based on server load and network conditions.`,
        codeExamples: [],
      },
    ],
    checklist: [
      { text: 'Achieve LCP under 2.5 seconds on mobile and desktop', priority: 'critical' },
      { text: 'Achieve FID under 100ms and INP under 200ms for responsiveness', priority: 'critical' },
      { text: 'Achieve CLS under 0.1 by setting image dimensions', priority: 'critical' },
      { text: 'Implement browser caching with appropriate cache-control headers', priority: 'important' },
      { text: 'Use a CDN to serve content from edge locations near users', priority: 'important' },
      { text: 'Optimize images with WebP/AVIF format and lazy loading', priority: 'important' },
      { text: 'Minify and compress CSS/JavaScript with Gzip or Brotli', priority: 'important' },
      { text: 'Use defer/async for non-critical JavaScript to prevent render blocking', priority: 'important' },
      { text: 'Reduce server TTFB to under 600ms with caching and optimization', priority: 'recommended' },
      { text: 'Enable HTTP/2 or HTTP/3 for multiplexed requests', priority: 'recommended' },
    ],
    relatedGuides: ['image-optimization', 'mobile-friendly'],
  },
  {
    slug: 'mobile-friendly',
    title: 'Mobile-Friendly Design',
    summary: 'Ensure your site works flawlessly on all mobile devices with responsive design, proper touch targets, and mobile-first optimization.',
    category: 'technical',
    toolSlug: 'mobile-friendly-checker',
    icon: '📱',
    readTime: 5,
    sections: [
      {
        title: 'What is Mobile-Friendliness?',
        content: `**Mobile-friendliness** refers to how well your website functions and displays on mobile devices like smartphones and tablets. A mobile-friendly site adapts its layout, content, and functionality to provide an optimal user experience regardless of screen size.

With over **60% of web traffic** now coming from mobile devices, mobile-friendliness isn't optional—it's essential. A site that works well on mobile delivers:

- **Readable text** without zooming or horizontal scrolling
- **Tappable elements** sized for touch interaction
- **Fast loading** on mobile networks
- **Proper layout** that adapts to any screen width

Mobile-friendliness is also a **Google ranking factor**. Since 2015, Google has boosted mobile-friendly sites in mobile search results, and since 2018, it has used **mobile-first indexing**—crawling and indexing the mobile version of your site first.

A truly mobile-friendly site goes beyond just "working" on mobile. It should feel native and intuitive, with touch-friendly navigation, appropriately sized content, and fast performance even on slower cellular connections.`,
        codeExamples: [],
      },
      {
        title: 'Mobile-First Indexing',
        content: `**Mobile-first indexing** means Google predominantly uses the mobile version of your website for indexing and ranking. This shift reflects the reality that most users now search on mobile devices.

### How It Works

Before mobile-first indexing, Google primarily crawled the desktop version of sites. Now, Google's crawler behaves like a mobile user:

1. **Crawls the mobile version** of your site first
2. **Indexes content** from the mobile page
3. **Ranks based on mobile experience** including speed and usability

### What This Means for Your Site

If your mobile and desktop versions have identical content, you're in good shape. But many sites have issues:

- **Missing content on mobile**: Some sites show less content on mobile, which won't be indexed
- **Structured data differences**: Schema markup should match between versions
- **Metadata mismatches**: Title tags and meta descriptions should be consistent
- **Different URLs**: If using m-dot subdomains, ensure proper canonicalization

### Ranking Impact

Google explicitly states that **mobile-first indexing affects rankings**. Sites with:
- Poor mobile usability → Lower rankings
- Slow mobile performance → Lower rankings
- Missing mobile content → Reduced visibility
- Strong mobile experience → Ranking boost

The solution is simple: treat your mobile site as your primary site. Build for mobile first, then enhance for desktop. This "mobile-first" approach ensures you're optimized for how Google sees your content and how most users access it.`,
        codeExamples: [],
      },
      {
        title: 'Responsive Design Principles',
        content: `**Responsive design** ensures your website automatically adapts to any screen size—from small phones to large desktop monitors. It's the foundation of mobile-friendly web development.

### The Viewport Meta Tag

The viewport meta tag is the most critical piece of mobile-friendly code. Without it, mobile browsers assume a desktop width and scale the page down, resulting in tiny, unreadable text.

The viewport tag tells the browser to match the screen width and set an initial zoom level of 100%. This is essential for responsive design—without it, media queries won't work as expected.

### CSS Media Queries

Media queries let you apply different styles based on screen characteristics like width, height, and orientation. They're the building blocks of responsive layouts.

**Mobile-first approach**: Start with mobile styles as your base, then add complexity for larger screens using \`min-width\` media queries. This ensures mobile users get the fastest, most focused experience.

### Flexible Layouts

Responsive design uses fluid layouts that adapt to screen size:

- **Percentages over pixels** for widths
- **Flexbox and CSS Grid** for flexible arrangements
- **Relative units** (rem, em, vh/vw) for typography and spacing
- **Max-width constraints** to prevent content from becoming too wide on large screens

### The Responsive Mindset

Think of your design as a fluid continuum rather than fixed breakpoints. Content should reflow naturally, images should scale proportionally, and navigation should remain accessible at every size.`,
        codeExamples: [
          {
            language: 'html',
            code: `<meta name="viewport" content="width=device-width, initial-scale=1.0">`,
            description: 'Essential viewport meta tag for responsive design - must be in every page <head>',
          },
          {
            language: 'css',
            code: `/* Mobile-first base styles */
.container {
  width: 100%;
  padding: 1rem;
}

/* Tablet and up */
@media (min-width: 768px) {
  .container {
    max-width: 750px;
    margin: 0 auto;
  }
}

/* Desktop and up */
@media (min-width: 1024px) {
  .container {
    max-width: 960px;
  }
}

/* Large desktop */
@media (min-width: 1280px) {
  .container {
    max-width: 1200px;
  }
}`,
            description: 'Mobile-first responsive container with progressive breakpoints',
          },
        ],
      },
      {
        title: 'Common Mobile SEO Issues',
        content: `Many websites still suffer from mobile usability problems that hurt both user experience and search rankings. Here are the most common issues and how to fix them.

### Text Too Small

Illegible text frustrates users and violates Google's mobile usability guidelines. **Minimum font size: 16px** for body text.

**Why 16px?** iOS Safari automatically zooms when users focus on inputs with font-size under 16px, creating a jarring experience. Using 16px or larger prevents this zoom behavior.

### Touch Targets Too Small

Buttons, links, and interactive elements must be large enough to tap accurately. **Minimum size: 44x44 pixels** (Apple's guideline, also recommended by Google).

Small touch targets cause mis-taps and frustration. Add padding or use larger font sizes to ensure tapability.

### Horizontal Scrolling

Users shouldn't need to scroll horizontally to see content. This usually indicates:

- Missing viewport meta tag
- Fixed-width elements wider than the screen
- Images without responsive sizing
- Long unbreakable text (URLs, code snippets)

**Fix:** Ensure all content fits within the viewport width using \`overflow-x: hidden\` as a safety net, but fix the root cause.

### Intrusive Interstitials

Pop-ups that cover content on mobile can trigger Google penalties. **Avoid:**

- Full-screen overlays that appear immediately
- Large banners that push content below the fold
- App download interstitials that hide content

**Acceptable alternatives:**

- Smaller banners that are easily dismissible
- Interstitials triggered by user action (exit intent, button click)
- Cookie consent notices (temporarily allowed)

### Unplayable Content

Avoid Flash, certain video formats, or interactive elements that don't work on mobile. Use HTML5, modern video formats (MP4, WebM), and touch-friendly interactions.`,
        codeExamples: [
          {
            language: 'css',
            code: `/* Mobile-friendly typography */
body {
  font-size: 16px; /* Prevents iOS zoom on focus */
  line-height: 1.6;
}

/* Touch-friendly targets */
button,
a,
input[type="submit"] {
  min-height: 44px;
  min-width: 44px;
  padding: 12px 16px;
}

/* Prevent horizontal scroll */
html, body {
  overflow-x: hidden;
  max-width: 100vw;
}

/* Responsive images (never overflow) */
img {
  max-width: 100%;
  height: auto;
}`,
            description: 'CSS fixes for common mobile issues: font size, touch targets, overflow, and responsive images',
          },
        ],
      },
      {
        title: 'Testing Your Site',
        content: `Regular testing ensures your site delivers a great mobile experience. Use these free tools to identify and fix issues.

### Google Mobile-Friendly Test

Google's official tool analyzes any URL and reports mobile usability issues. Simply enter your URL at **search.google.com/test/mobile-friendly** or use our integrated Mobile-Friendly Checker tool.

**What it checks:**
- Text readability and size
- Touch target spacing
- Viewport configuration
- Horizontal scrolling issues
- Plugin compatibility

### Google Search Console

The **Mobile Usability report** in Search Console shows issues Google found across your site. It's essential for ongoing monitoring.

**Common reports:**
- "Text too small to read"
- "Clickable elements too close together"
- "Content wider than screen"
- "Viewport not configured"

### Chrome DevTools Device Mode

For development testing, Chrome DevTools includes a powerful device emulator:

1. Open DevTools (F12 or right-click → Inspect)
2. Click the device toolbar icon (or press Ctrl+Shift+M)
3. Select a device or responsive mode
4. Test different screen sizes and network speeds

### Real Device Testing

Emulators aren't perfect. Test on actual devices when possible:

- Different screen sizes (small phone, large phone, tablet)
- Different operating systems (iOS, Android)
- Different browsers (Safari, Chrome, Firefox)
- Different network speeds (WiFi, 4G, 3G)

### Using Our Mobile-Friendly Checker

Our integrated tool combines Google's mobile test with additional analysis. Enter any URL to get:

- Mobile-friendliness score
- Specific issues and recommendations
- Code examples for fixes
- Comparison with best practices

Regular testing—ideally after any design or content change—keeps your site mobile-optimized.`,
        codeExamples: [],
      },
    ],
    checklist: [
      { text: 'Add viewport meta tag to all pages: <meta name="viewport" content="width=device-width, initial-scale=1.0">', priority: 'critical' },
      { text: 'Ensure touch targets are at least 44x44 pixels for all interactive elements', priority: 'critical' },
      { text: 'Use minimum 16px font size for body text to prevent iOS zoom issues', priority: 'critical' },
      { text: 'Eliminate horizontal scrolling by using responsive layouts and max-width: 100%', priority: 'critical' },
      { text: 'Avoid intrusive interstitials and popups that cover content on mobile', priority: 'important' },
      { text: 'Implement responsive images with max-width: 100% and srcset for different sizes', priority: 'important' },
      { text: 'Test your site with Google Mobile-Friendly Test and fix reported issues', priority: 'important' },
      { text: 'Use mobile-first CSS approach: write mobile styles first, then add breakpoints for larger screens', priority: 'recommended' },
      { text: 'Monitor Mobile Usability report in Google Search Console regularly', priority: 'important' },
    ],
    relatedGuides: ['page-speed', 'https-security'],
  },
  {
    slug: 'https-security',
    title: 'HTTPS & Security Headers',
    summary: 'Secure your site with SSL/TLS encryption and implement security headers to protect users, improve SEO rankings, and build trust with visitors.',
    category: 'technical',
    icon: '🔒',
    readTime: 5,
    sections: [
      {
        title: 'What is HTTPS?',
        content: `**HTTPS** (Hypertext Transfer Protocol Secure) is the secure version of HTTP, the protocol used to transfer data between a web browser and a website. HTTPS encrypts all communications using **SSL** (Secure Sockets Layer) or **TLS** (Transport Layer Security) protocols, ensuring that data cannot be intercepted or tampered with during transmission.

### How HTTPS Works

When a user connects to an HTTPS website, a secure connection is established through a process called the **SSL/TLS handshake**:

1. **Browser requests secure connection** — The browser connects to the server and requests identification
2. **Server presents SSL certificate** — A digital certificate from a trusted **Certificate Authority (CA)** proves the site's identity
3. **Browser verifies certificate** — The browser checks if the certificate is valid, trusted, and matches the domain
4. **Encrypted session established** — Both parties generate session keys for encrypted communication

### Encryption, Authentication, Integrity

HTTPS provides three key security guarantees:

- **Encryption** — Data is scrambled so interceptors cannot read it
- **Authentication** — Users can verify they're connected to the legitimate site
- **Integrity** — Data cannot be modified during transfer without detection

Without HTTPS, all data sent between a user's browser and your server—including passwords, credit card numbers, and personal information—travels in plaintext that can be intercepted by anyone on the network.`,
        codeExamples: [],
      },
      {
        title: 'SEO Benefits of HTTPS',
        content: `HTTPS is no longer optional for SEO—it's a baseline requirement. Google has been pushing for a more secure web since 2014, and HTTPS provides tangible ranking benefits.

### Google's Ranking Signal

In 2014, Google announced HTTPS as a **ranking signal**. While it's a relatively lightweight factor compared to content quality, it can be a tiebreaker when competing sites are otherwise equal. More importantly, Google has consistently increased the importance of security signals over time.

### Browser Trust Indicators

Modern browsers prominently display security status in the address bar:

- **Secure sites**: Show a padlock icon and "Secure" label
- **Non-HTTPS sites**: Display "Not Secure" warnings that alarm users

These warnings significantly impact user trust and can dramatically increase bounce rates. Users are far less likely to enter personal information or complete transactions on sites marked as insecure.

### Mobile-First Indexing

With mobile-first indexing, Google predominantly crawls the mobile version of your site. Mobile browsers are even more aggressive about security warnings, making HTTPS essential for mobile SEO performance.

### Referrer Data Preservation

HTTPS preserves referrer data when users click from HTTPS sites. When a user clicks from an HTTPS site to an HTTP site, the referrer information is stripped for security. This means you lose valuable analytics data about where your traffic comes from if your site isn't HTTPS.

### Google Search Console Requirements

Some Google Search Console features require HTTPS:
- **Core Web Vitals** reporting works best on HTTPS
- **AMP** pages require HTTPS
- **PWA** (Progressive Web Apps) require HTTPS

The bottom line: HTTPS is table stakes for modern SEO. There's no legitimate reason to avoid it.`,
        codeExamples: [],
      },
      {
        title: 'SSL Certificate Types',
        content: `SSL certificates come in several types, differing by validation level and the number of domains they cover. Choosing the right certificate depends on your needs and budget.

### Validation Levels

**Domain Validated (DV) Certificates**
- **Validation**: Email or DNS record control
- **Issuance time**: Minutes to hours
- **Cost**: Free to low cost
- **Best for**: Personal sites, blogs, small businesses
- **Example**: Let's Encrypt (free)

**Organization Validated (OV) Certificates**
- **Validation**: Organization identity verified by CA
- **Issuance time**: 1-3 days
- **Cost**: $50-200/year
- **Best for**: Businesses, organizations, e-commerce
- **Browser display**: Shows organization name in certificate details

**Extended Validation (EV) Certificates**
- **Validation**: Extensive verification of legal entity
- **Issuance time**: 1-2 weeks
- **Cost**: $150-500/year
- **Best for**: Banks, financial institutions, large e-commerce
- **Browser display**: Shows organization name in address bar (historically)

### Free SSL with Let's Encrypt

**Let's Encrypt** is a free, automated, and open Certificate Authority that provides DV certificates at no cost. It's the most popular choice for websites of all sizes, trusted by millions of domains worldwide.

**Advantages:**
- Completely free (no cost ever)
- Automated renewal via ACME protocol
- 90-day certificates with automatic renewal
- Supported by all major hosting providers
- Use **Certbot** for easy installation

**Most hosting providers** now offer one-click Let's Encrypt installation through their control panels (cPanel, Plesk, etc.), making free SSL accessible to everyone.

### Certificate Scope

| Type | Coverage |
|------|----------|
| Single-domain | One domain (example.com) |
| Wildcard | Domain + all subdomains (*.example.com) |
| Multi-domain (SAN) | Multiple domains on one certificate |

For most sites, a free Let's Encrypt DV certificate is the best choice. It provides the same encryption strength as paid certificates.`,
        codeExamples: [],
      },
      {
        title: 'Implementing HTTPS & Redirects',
        content: `Installing an SSL certificate is just the first step. You must also configure your server to redirect all HTTP traffic to HTTPS and update your internal links.

### HTTPS Redirect Configuration

After installing your SSL certificate, configure your server to automatically redirect all HTTP requests to HTTPS. This ensures users and search engines always access the secure version of your site.

**Critical**: Use **301 (permanent) redirects**, not 302 (temporary). A 301 redirect passes link equity to the HTTPS version and signals to search engines that the move is permanent.

### Update Internal Links

After enabling HTTPS, audit your site for mixed content:
- Update all internal links from \`http://\` to \`https://\`
- Update canonical tags to HTTPS URLs
- Update sitemap URLs to HTTPS
- Update Open Graph and Twitter Card URLs
- Update structured data URLs

### Update Google Search Console

Add your HTTPS property to Google Search Console and submit your updated sitemap. This ensures Google indexes the HTTPS version.`,
        codeExamples: [
          {
            language: 'apache',
            code: `# .htaccess - Force HTTPS with 301 redirect
RewriteEngine On

# Redirect HTTP to HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Alternative for specific domain
RewriteCond %{HTTP_HOST} ^example\.com [NC]
RewriteCond %{SERVER_PORT} 80
RewriteRule ^(.*)$ https://example.com/$1 [L,R=301]`,
            description: 'Apache .htaccess configuration for HTTPS redirect with 301 permanent status',
          },
          {
            language: 'nginx',
            code: `# nginx - HTTP to HTTPS redirect
server {
    listen 80;
    server_name example.com www.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name example.com www.example.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;
}`,
            description: 'Nginx server block for HTTPS redirect with modern TLS configuration',
          },
        ],
      },
      {
        title: 'Security Headers',
        content: `Security headers are HTTP response headers that instruct browsers to enable built-in security protections. They help prevent common attacks like XSS, clickjacking, and content injection. Implementing these headers is essential for any production website.

### HTTP Strict Transport Security (HSTS)

**HSTS** forces browsers to always connect to your site via HTTPS, even if the user types \`http://\` or clicks an HTTP link. This prevents downgrade attacks where attackers try to force an insecure connection.

**Key directives:**
- \`max-age\` — How long to remember this setting (recommend 1 year: 31536000 seconds)
- \`includeSubDomains\` — Apply to all subdomains
- \`preload\` — Submit to browser preload lists

### X-Frame-Options

**X-Frame-Options** prevents your site from being embedded in an iframe on other domains, protecting against **clickjacking attacks**.

**Values:**
- \`DENY\` — Block all framing
- \`SAMEORIGIN\` — Allow only from your domain

**Note**: \`ALLOW-FROM\` is deprecated and should not be used.

### X-Content-Type-Options

**X-Content-Type-Options: nosniff** prevents browsers from MIME-type sniffing. Without this header, browsers may execute malicious scripts disguised as images or other file types.

### Content-Security-Policy (CSP)

**CSP** is a powerful header that controls which resources can be loaded. It's the most complex but provides the strongest XSS protection.

**Start simple and expand**: CSP can break functionality if misconfigured. Start with a permissive policy and tighten gradually.

### Testing Security Headers

Use **SSL Labs** (ssllabs.com/ssltest/) to test your HTTPS configuration and **Security Headers** (securityheaders.com) to check your header implementation. Both are free tools that provide detailed recommendations.`,
        codeExamples: [
          {
            language: 'apache',
            code: `# .htaccess - Security Headers Configuration
<IfModule mod_headers.c>
    # HTTP Strict Transport Security (HSTS)
    # 1 year max-age, include subdomains, enable preload
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    
    # Prevent clickjacking
    Header always set X-Frame-Options "SAMEORIGIN"
    
    # Prevent MIME-sniffing
    Header always set X-Content-Type-Options "nosniff"
    
    # XSS Protection (legacy but still useful)
    Header always set X-XSS-Protection "1; mode=block"
    
    # Referrer Policy
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Content Security Policy (adjust for your needs)
    # Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
</IfModule>`,
            description: 'Complete Apache .htaccess security headers configuration with HSTS, X-Frame-Options, and X-Content-Type-Options',
          },
          {
            language: 'nginx',
            code: `# nginx - Security Headers Configuration
server {
    # ... SSL and other config ...
    
    # HTTP Strict Transport Security (HSTS)
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    
    # Prevent clickjacking
    add_header X-Frame-Options "SAMEORIGIN" always;
    
    # Prevent MIME-sniffing
    add_header X-Content-Type-Options "nosniff" always;
    
    # XSS Protection
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Referrer Policy
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Content Security Policy (adjust for your needs)
    # add_header Content-Security-Policy "default-src 'self';" always;
}`,
            description: 'Complete Nginx security headers configuration with HSTS includeSubDomains',
          },
        ],
      },
      {
        title: 'Mixed Content Issues',
        content: `**Mixed content** occurs when a secure HTTPS page loads insecure HTTP resources like images, scripts, or stylesheets. This creates security vulnerabilities and triggers browser warnings.

### Types of Mixed Content

**Mixed Passive Content** (images, audio, video)
- Considered "passive" because it doesn't execute code
- Browsers may still display a "Not Secure" warning
- Less severe but should still be fixed

**Mixed Active Content** (scripts, stylesheets, iframes, AJAX requests)
- Can execute code and modify the page
- Browsers often **block** this content entirely
- Breaks functionality and creates security risks

### How to Fix Mixed Content

1. **Update internal links** — Change all \`http://\` URLs to \`https://\` in your HTML, CSS, and JavaScript
2. **Use protocol-relative URLs** — Replace \`http://\` with \`//\` for external resources (browser uses current protocol)
3. **Use upgrade-insecure-requests** — A CSP directive that automatically upgrades HTTP requests to HTTPS

### Finding Mixed Content

**Browser DevTools**: Open Chrome DevTools (F12) → Console. Mixed content warnings appear in red.

**Google Search Console**: The Security Issues report shows mixed content problems.

**Online tools**: Why No Padlock? (whynopadlock.com) scans your site for insecure resources.

### Prevention

The \`upgrade-insecure-requests\` CSP directive tells browsers to automatically upgrade all HTTP requests to HTTPS. This is a safety net that prevents mixed content issues.

Add this to your security headers:
\`Content-Security-Policy: upgrade-insecure-requests\``,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Mixed content example (BAD) -->
<img src="http://example.com/image.jpg" alt="Image">
<script src="http://example.com/script.js"></script>

<!-- Fixed: Use HTTPS (GOOD) -->
<img src="https://example.com/image.jpg" alt="Image">
<script src="https://example.com/script.js"></script>

<!-- Fixed: Protocol-relative URL (also good) -->
<img src="//example.com/image.jpg" alt="Image">`,
            description: 'Examples of mixed content issues and how to fix them by changing HTTP to HTTPS',
          },
          {
            language: 'apache',
            code: `# .htaccess - Upgrade insecure requests automatically
<IfModule mod_headers.c>
    # Force browsers to upgrade HTTP requests to HTTPS
    Header always set Content-Security-Policy "upgrade-insecure-requests"
</IfModule>`,
            description: 'CSP directive to automatically upgrade insecure HTTP requests to HTTPS',
          },
        ],
      },
    ],
    checklist: [
      { text: 'Install SSL certificate from Let\'s Encrypt (free) or your preferred CA', priority: 'critical' },
      { text: 'Configure 301 permanent redirect from HTTP to HTTPS', priority: 'critical' },
      { text: 'Implement HSTS header with includeSubDomains and at least 1 year max-age', priority: 'critical' },
      { text: 'Add X-Frame-Options: SAMEORIGIN to prevent clickjacking', priority: 'important' },
      { text: 'Add X-Content-Type-Options: nosniff to prevent MIME sniffing', priority: 'important' },
      { text: 'Fix all mixed content by updating HTTP resources to HTTPS', priority: 'important' },
      { text: 'Update internal links and canonical tags to HTTPS URLs', priority: 'important' },
      { text: 'Set up automatic SSL certificate renewal (Let\'s Encrypt auto-renews)', priority: 'recommended' },
      { text: 'Test configuration with SSL Labs (ssllabs.com/ssltest/)', priority: 'recommended' },
      { text: 'Add HTTPS property to Google Search Console and submit updated sitemap', priority: 'recommended' },
    ],
    relatedGuides: ['mobile-friendly', 'canonical-urls'],
  },
  {
    slug: 'structured-data',
    title: 'Structured Data (Schema.org)',
    summary: 'Implement Schema.org markup to unlock rich snippets, knowledge panels, and enhanced search visibility.',
    category: 'technical',
    icon: '📊',
    readTime: 6,
    sections: [
      {
        title: 'What is Structured Data?',
        content: `**Structured data** is a standardized format for providing information about a page and classifying its content. Using the **Schema.org** vocabulary (a collaboration between Google, Microsoft, Yahoo, and Yandex), you can explicitly tell search engines what your content means—not just what it says.

Think of structured data as a translation layer between your website and search engines. While humans can easily understand that "4.8 stars from 2,500 reviews" indicates product quality, search engines need help interpreting this context. Structured data provides that translation.

### How It Works

Structured data uses a defined vocabulary to label content elements:

- **Items** — Things like products, articles, events, or organizations
- **Properties** — Attributes like name, price, rating, or date
- **Types** — Categories defined in Schema.org (Article, Product, FAQPage, etc.)

When you mark up a page with structured data, search engines can:

1. **Understand your content** more precisely
2. **Create rich snippets** in search results
3. **Enable voice search** answers
4. **Power knowledge panels** and entity panels

The result? Your search listings become more informative, attractive, and clickable—driving higher click-through rates and better user engagement.`,
        codeExamples: [],
      },
      {
        title: 'SEO Benefits of Structured Data',
        content: `Structured data directly impacts your search visibility and click-through rates. Here's why it matters for SEO:

### Rich Snippets

Rich snippets are enhanced search results that display additional information beyond the standard title, URL, and meta description. They make your listing stand out and provide users with valuable information before they click.

**Common rich snippet types:**
- **Review stars** — Display average ratings and review counts
- **Product information** — Show price, availability, and product images
- **FAQ accordions** — Expandable Q&A pairs directly in search results
- **Recipe details** — Cooking time, calories, and star ratings
- **Event listings** — Date, time, location, and ticket availability
- **Job postings** — Salary range, location, and application deadline

### Knowledge Panels

Structured data helps Google build **Knowledge Graph** entities. When Google understands your organization, products, or content as distinct entities, it can display knowledge panels—information boxes that appear on the right side of search results for branded queries.

### Improved Click-Through Rates

Studies consistently show that rich snippets increase click-through rates by **20-40%** on average. Users are more likely to click on results that provide additional context:

- Star ratings signal quality and trust
- Pricing information helps qualified buyers self-select
- FAQ snippets answer questions before the click
- Event dates help users find relevant occasions

### Voice Search Optimization

Voice assistants (Google Assistant, Alexa, Siri) rely heavily on structured data to answer questions. When users ask "What time does the event start?" or "How much does this product cost?", the answers often come from structured markup.

### Future-Proofing

Structured data is increasingly important as search evolves. Google's AI-powered features like **AI Overviews** and **Search Generative Experience (SGE)** use structured data to understand content and generate accurate responses.`,
        codeExamples: [],
      },
      {
        title: 'Common Schema.org Types',
        content: `Schema.org defines hundreds of types, but most websites only need a handful. Here are the most valuable types for SEO:

### Article

Mark up news articles, blog posts, and editorial content. Helps search engines understand headline, author, publication date, and article body.

**Use for:** Blog posts, news articles, opinion pieces, tutorials

### Product

Essential for e-commerce. Displays price, availability, ratings, and product images in search results.

**Use for:** Product pages, item listings, catalog pages

### FAQPage

Displays FAQ content as expandable accordions in search results—taking up more screen space and answering user questions directly.

**Use for:** FAQ sections, help pages, Q&A content

### BreadcrumbList

Helps search engines understand your site navigation and can display breadcrumb trails in search results instead of URLs.

**Use for:** All pages with breadcrumb navigation

### Organization

Defines your company or organization as an entity. Powers knowledge panels for branded searches.

**Use for:** Homepage, about page, contact page

### LocalBusiness

Combines organization data with location, hours, and contact information. Essential for local SEO and Google Business Profile.

**Use for:** Physical business locations, store pages, restaurant pages

### HowTo

Step-by-step instructions with images and time estimates. Can appear as rich results with expandable steps.

**Use for:** Tutorials, guides, recipes, DIY content

### Event

Displays event details (date, time, location, ticket availability) directly in search results.

**Use for:** Conferences, webinars, concerts, workshops

### Review and AggregateRating

Enables star ratings in search results. Can be standalone or nested within other types (Product, LocalBusiness).

**Use for:** Product reviews, business reviews, service reviews`,
        codeExamples: [],
      },
      {
        title: 'Implementation Methods',
        content: `There are three primary methods to add structured data to your pages. Google recommends **JSON-LD** as the preferred format.

### JSON-LD (Recommended)

**JSON-LD** (JavaScript Object Notation for Linked Data) is a JavaScript notation embedded in a \`<script>\` tag in your page's \`<head>\` or \`<body>\`. It's Google's preferred and recommended format.

**Advantages:**
- Separate from visible HTML content
- Easy to maintain and update
- Can be dynamically generated
- Readable by all major search engines
- Easy to validate and debug

**Implementation:** Place a \`<script type="application/ld+json">\` block containing your structured data anywhere in the page HTML.

### Microdata (Legacy)

Microdata uses HTML attributes (\`itemscope\`, \`itemtype\`, \`itemprop\`) directly on existing elements. It's more complex to maintain because structured data is mixed with visible content.

**Disadvantages:**
- Tightly coupled to HTML structure
- Harder to modify without breaking display
- Can complicate HTML markup
- Less commonly used today

### RDFa (Legacy)

RDFa (Resource Description Framework in Attributes) is similar to Microdata but uses different attribute names. Rarely used for SEO purposes.

### Why JSON-LD is Preferred

Google explicitly recommends JSON-LD because:

1. **Separation of concerns** — Data structure is independent of display
2. **Easier implementation** — No need to modify existing HTML elements
3. **Dynamic generation** — CMS and frameworks can easily generate JSON-LD
4. **Better debugging** — Cleaner separation makes validation easier

**Note:** Google can read JSON-LD even when it's dynamically generated via JavaScript, though static server-side rendering is preferred for faster discovery.`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- JSON-LD structured data in <head> -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Complete Guide to Structured Data for SEO",
  "author": {
    "@type": "Person",
    "name": "Jane Doe"
  },
  "datePublished": "2024-01-15",
  "dateModified": "2024-01-20",
  "publisher": {
    "@type": "Organization",
    "name": "SEOIndicator",
    "logo": {
      "@type": "ImageObject",
      "url": "https://example.com/logo.png"
    }
  }
}
</script>`,
            description: 'JSON-LD Article schema in a script tag (Google\'s preferred format)',
          },
          {
            language: 'html',
            code: `<!-- Microdata format (legacy, not recommended) -->
<div itemscope itemtype="https://schema.org/Article">
  <h1 itemprop="headline">Complete Guide to Structured Data</h1>
  <span itemprop="author" itemscope itemtype="https://schema.org/Person">
    By <span itemprop="name">Jane Doe</span>
  </span>
  <time itemprop="datePublished" datetime="2024-01-15">January 15, 2024</time>
</div>`,
            description: 'Microdata format for comparison (legacy approach, use JSON-LD instead)',
          },
        ],
      },
      {
        title: 'Testing and Validation',
        content: `Always validate your structured data before deploying. Errors can prevent rich snippets from appearing, and incorrect markup may confuse search engines.

### Google Rich Results Test

The **Google Rich Results Test** is the official tool for checking if your structured data qualifies for rich results. It shows exactly which rich result types your markup can generate.

**URL:** search.google.com/test/rich-results

**Features:**
- Tests by URL or code snippet
- Shows which rich result types are detected
- Highlights errors and warnings
- Validates against Google's requirements

### Schema Markup Validator

The **Schema Markup Validator** (from schema.org) checks your markup against the full Schema.org vocabulary. It catches syntax errors and structural issues that the Rich Results Test might miss.

**URL:** validator.schema.org

**Features:**
- Validates against full Schema.org specification
- Shows nested types and properties
- Identifies missing recommended properties

### Google Search Console

The **Enhancements** section in Google Search Console shows structured data errors and warnings detected across your site. It's essential for ongoing monitoring.

**Key reports:**
- Product markup errors
- FAQ and Q&A issues
- Article and breadcrumb warnings
- Manual actions for spammy structured data

### Chrome DevTools

For local development, Chrome DevTools shows JSON-LD in the Elements panel. You can also use the Console to parse and inspect your structured data.

### Testing Best Practices

1. **Test before deploying** — Validate in staging environments
2. **Check all pages** — Different page types may have different markup
3. **Monitor Search Console** — Google may find issues after deployment
4. **Fix errors promptly** — Errors can block rich results entirely
5. **Use both tools** — Rich Results Test for rich results eligibility, Schema Validator for syntax`,
        codeExamples: [
          {
            language: 'javascript',
            code: `// Quick test: Parse JSON-LD in browser console
const scripts = document.querySelectorAll('script[type="application/ld+json"]');
scripts.forEach((script, i) => {
  try {
    const data = JSON.parse(script.textContent);
    console.log('Schema ' + (i + 1) + ':', data['@type'], data);
  } catch (e) {
    console.error('Schema ' + (i + 1) + ': Invalid JSON');
  }
});`,
            description: 'Browser console snippet to inspect and validate JSON-LD on any page',
          },
        ],
      },
      {
        title: 'JSON-LD Code Examples',
        content: `Here are practical JSON-LD examples for the most common schema types. Copy and adapt these templates for your pages.

### Product Schema

Essential for e-commerce. Displays price, availability, ratings, and images in search results.

### Article Schema

For blog posts and news articles. Includes author, publication dates, and publisher information.

### FAQPage Schema

Creates expandable FAQ accordions in search results. Each question-answer pair must be properly formatted.

### Organization Schema

Defines your business entity. Place on your homepage to enable knowledge panel eligibility.

### BreadcrumbList Schema

Helps search engines understand site structure. Often generated dynamically based on page location.

**Implementation Tips:**

1. Place JSON-LD in the \`<head>\` or at the end of \`<body>\`
2. Use dynamic generation from your CMS when possible
3. Include all required properties for each schema type
4. Nest related types (e.g., Organization inside Article as publisher)
5. Keep data synchronized with visible page content`,
        codeExamples: [
          {
            language: 'json',
            code: `{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Wireless Bluetooth Headphones",
  "image": [
    "https://example.com/photos/headphones-1x.jpg",
    "https://example.com/photos/headphones-2x.jpg"
  ],
  "description": "Premium wireless headphones with 30-hour battery life.",
  "sku": "WBH-12345",
  "brand": {
    "@type": "Brand",
    "name": "AudioTech"
  },
  "offers": {
    "@type": "Offer",
    "url": "https://example.com/headphones",
    "priceCurrency": "USD",
    "price": "149.99",
    "priceValidUntil": "2024-12-31",
    "availability": "https://schema.org/InStock",
    "seller": {
      "@type": "Organization",
      "name": "Example Store"
    }
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "reviewCount": "2450"
  }
}`,
            description: 'Complete Product schema with offers, brand, and aggregate ratings',
          },
          {
            language: 'json',
            code: `{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "How to Implement Structured Data for SEO",
  "image": [
    "https://example.com/photos/article-1x.jpg",
    "https://example.com/photos/article-2x.jpg"
  ],
  "datePublished": "2024-01-15T08:00:00+00:00",
  "dateModified": "2024-01-20T10:30:00+00:00",
  "author": [{
    "@type": "Person",
    "name": "Jane Doe",
    "url": "https://example.com/authors/jane-doe"
  }],
  "publisher": {
    "@type": "Organization",
    "name": "SEOIndicator Blog",
    "logo": {
      "@type": "ImageObject",
      "url": "https://example.com/logo.png"
    }
  },
  "description": "A comprehensive guide to implementing Schema.org structured data for better search visibility."
}`,
            description: 'Article schema with author, publisher, and date information',
          },
          {
            language: 'json',
            code: `{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is structured data?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Structured data is a standardized format for providing information about a page and classifying its content using the Schema.org vocabulary."
      }
    },
    {
      "@type": "Question",
      "name": "Which format does Google recommend?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Google recommends JSON-LD as the preferred format for structured data because it's easier to implement and maintain."
      }
    }
  ]
}`,
            description: 'FAQPage schema with question-answer pairs for rich FAQ snippets',
          },
          {
            language: 'json',
            code: `{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "SEOIndicator",
  "url": "https://example.com",
  "logo": "https://example.com/logo.png",
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+1-800-555-0123",
    "contactType": "customer service"
  },
  "sameAs": [
    "https://twitter.com/seoindicator",
    "https://www.linkedin.com/company/seoindicator",
    "https://www.facebook.com/seoindicator"
  ]
}`,
            description: 'Organization schema for knowledge panel eligibility',
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
      "name": "Structured Data",
      "item": "https://example.com/guides/structured-data/"
    }
  ]
}`,
            description: 'BreadcrumbList schema for navigation breadcrumbs in search results',
          },
        ],
      },
    ],
    checklist: [
      { text: 'Use JSON-LD format (Google\'s preferred method) in a <script type="application/ld+json"> tag', priority: 'critical' },
      { text: 'Include all required properties for your schema type (name, description, etc.)', priority: 'critical' },
      { text: 'Test all structured data with Google Rich Results Test before deployment', priority: 'critical' },
      { text: 'Implement schema types that match your content: Article, Product, FAQPage, Organization, LocalBusiness', priority: 'important' },
      { text: 'Keep structured data synchronized with visible page content to avoid manual actions', priority: 'important' },
      { text: 'Add Organization schema to your homepage for knowledge panel eligibility', priority: 'important' },
      { text: 'Implement BreadcrumbList schema on all pages with navigation hierarchy', priority: 'important' },
      { text: 'Monitor Google Search Console Enhancement reports for errors and warnings', priority: 'important' },
      { text: 'Include AggregateRating and Review schema only if you have genuine reviews', priority: 'recommended' },
      { text: 'Use valid ISO 8601 date formats (YYYY-MM-DD) for all date properties', priority: 'recommended' },
    ],
    relatedGuides: ['open-graph', 'canonical-urls'],
  },
  {
    slug: 'canonical-urls',
    title: 'Canonical URL Implementation',
    summary: 'Prevent duplicate content issues and consolidate ranking signals with proper canonical tag implementation.',
    category: 'technical',
    icon: '🎯',
    readTime: 5,
    sections: [
      {
        title: 'What are Canonical URLs?',
        content: `A **canonical URL** is the preferred version of a web page—the "master copy" that you want search engines to index and rank. When multiple pages have identical or very similar content, the canonical tag tells search engines which version should be treated as the original.

The canonical tag is implemented using the \`<link rel="canonical">\` HTML element placed in the \`<head>\` section of a webpage. It looks like this:

\`<link rel="canonical" href="https://example.com/page/" />\`

### The Problem Canonical Tags Solve

Without canonical tags, search engines may encounter multiple URLs with the same or similar content:

- \`https://example.com/products\`
- \`https://example.com/products?sort=price\`
- \`https://www.example.com/products\`
- \`http://example.com/products\`

To search engines, these are **four different pages** with duplicate content. This creates several problems:

1. **Crawl budget waste** — Search engines spend time crawling multiple versions of the same content
2. **Diluted link equity** — Backlinks may point to different URLs instead of consolidating on one
3. **Ranking confusion** — Search engines may rank the wrong version or split ranking signals
4. **Duplicate content penalties** — In extreme cases, Google may filter out pages it considers duplicates

The canonical tag solves this by explicitly declaring: "This is the authoritative version. Consolidate all signals here."`,
        codeExamples: [
          {
            language: 'html',
            code: `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product Category</title>
  <!-- Canonical tag pointing to the preferred URL -->
  <link rel="canonical" href="https://example.com/products/" />
</head>
<body>
  <!-- Page content -->
</body>
</html>`,
            description: 'Canonical tag in the <head> section pointing to the preferred URL version',
          },
        ],
      },
      {
        title: 'Why Canonical Tags Matter',
        content: `Canonical tags are essential for managing duplicate content and protecting your SEO performance. Without them, you risk confusing search engines and fragmenting your ranking potential.

### Duplicate Content Penalties

When Google finds multiple pages with similar content, it must decide which version to show in search results. Without canonical guidance, Google may:

- **Choose the wrong version** — Perhaps an HTTP URL instead of HTTPS, or a parameterized URL instead of the clean one
- **Filter out pages** — Exclude pages from the index entirely if they appear to be duplicates
- **Split ranking signals** — Divide link equity, engagement metrics, and relevance across multiple URLs

### Link Equity Consolidation

Backlinks are one of the most valuable SEO assets. If three different URLs each earn backlinks, those signals remain fragmented. A canonical tag consolidates all those signals onto the preferred URL, maximizing ranking potential.

**Example:**
- \`example.com/page\` earns 10 backlinks
- \`example.com/page?utm_source=newsletter\` earns 5 backlinks
- \`www.example.com/page\` earns 8 backlinks

Without canonicalization, these 23 links are split. With proper canonicals, all 23 links consolidate onto one URL.

### Crawl Budget Optimization

Google allocates a limited **crawl budget** to each site—the number of pages it will crawl per visit. When duplicate URLs consume that budget, important new content may not get discovered. Canonical tags help search engines focus on unique, valuable content.

### Improved User Experience

Canonical tags ensure users land on the cleanest, most functional version of your page. Parameters like tracking codes or session IDs can cause issues—canonicals guide users to the optimal experience.`,
        codeExamples: [],
      },
      {
        title: 'When to Use Canonical Tags',
        content: `Canonical tags are necessary whenever multiple URLs serve identical or substantially similar content. Here are the most common scenarios:

### URL Parameters

E-commerce sites and filters often generate multiple URLs for the same content:

- \`/products?category=shoes\`
- \`/products?category=shoes&sort=price\`
- \`/products?category=shoes&color=blue\`

Each URL shows the same core products but with different filters. Set the main category page as canonical for all filtered versions.

### WWW vs Non-WWW

Your site should be accessible via both \`www.example.com\` and \`example.com\`, but only one should be canonical. Choose one and canonicalize all pages to that version.

### HTTP vs HTTPS

After migrating to HTTPS, the HTTP version may still be accessible. Ensure all pages canonicalize to their HTTPS equivalents. This complements your 301 redirects—not all links may redirect properly.

### Trailing Slash Variations

Some servers treat \`/page/\` and \`/page\` as different URLs:

- \`https://example.com/blog/\`
- \`https://example.com/blog\`

Choose one format (trailing slash is recommended for directories) and canonicalize to it.

### Syndicated Content

When republishing content across domains (guest posts, partner sites, content syndication), use canonical tags to indicate the original source:

- Original: \`yourdomain.com/article\`
- Syndicated: \`partner.com/your-article\` → canonicalize to original

### Print-Friendly Versions

If you offer print-friendly pages (\`/page/print/\`), canonicalize them to the main page version.

### Session IDs and Tracking Parameters

URLs with session identifiers, UTM parameters, or other tracking codes should canonicalize to the clean URL:
- \`/page?utm_source=email\` → canonical: \`/page\``,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Parameter URL: example.com/products?sort=price -->
<head>
  <!-- Canonical points to the clean, unparameterized URL -->
  <link rel="canonical" href="https://example.com/products/" />
</head>

<!-- WWW variant: www.example.com/products -->
<head>
  <!-- Canonical points to non-www (your preferred version) -->
  <link rel="canonical" href="https://example.com/products/" />
</head>

<!-- HTTP variant: http://example.com/products -->
<head>
  <!-- Canonical points to HTTPS version -->
  <link rel="canonical" href="https://example.com/products/" />
</head>`,
            description: 'Canonical tags for parameter URLs, www variants, and HTTP/HTTPS variations',
          },
        ],
      },
      {
        title: 'Implementation Methods',
        content: `There are three primary methods to implement canonical tags. The HTML link tag is most common and recommended for most websites.

### 1. HTML Link Tag (Recommended)

Place a \`<link rel="canonical">\` element in the \`<head>\` section of your page. This is the most widely supported and easiest method.

**Key requirements:**
- Must be in the \`<head>\` section
- Use absolute URLs (include protocol and domain)
- Only one canonical tag per page
- Must point to an indexable URL (not blocked by robots.txt or noindex)

### 2. HTTP Header

For non-HTML content like PDFs, images, or API responses, you can specify canonical URLs via HTTP headers.

This method is useful when you can't add HTML to the content, such as PDF documents that exist at multiple URLs.

### 3. XML Sitemap

Including URLs in your XML sitemap signals to Google which URLs you consider important. While not a direct canonical signal, Google treats sitemap URLs as preferred versions when choosing between duplicates.

**Best practice:** Use the HTML link tag as your primary method, supplemented by sitemap inclusion.

### Implementation Checklist

- Use absolute URLs (include \`https://\` and full domain)
- Ensure canonical URLs are accessible (return 200 status)
- Don't canonicalize to redirected or noindexed pages
- Match canonical URL to your sitemap URLs
- Implement at scale with CMS or server configuration`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Method 1: HTML Link Tag (Most Common) -->
<head>
  <link rel="canonical" href="https://example.com/page/" />
</head>`,
            description: 'HTML link tag method for canonical URL implementation',
          },
          {
            language: 'apache',
            code: `# Method 2: HTTP Header (Apache)
# For PDFs and non-HTML files
<FilesMatch "\\.pdf$">
  Header set Link '<https://example.com/document.pdf>; rel="canonical"'
</FilesMatch>`,
            description: 'HTTP header method for canonical URLs (useful for PDFs and non-HTML content)',
          },
          {
            language: 'xml',
            code: `<!-- Method 3: XML Sitemap -->
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://example.com/products/</loc>
    <lastmod>2024-01-15</lastmod>
  </url>
</urlset>`,
            description: 'XML sitemap signals preferred URLs to search engines',
          },
        ],
      },
      {
        title: 'Common Canonical Mistakes',
        content: `Implementing canonical tags incorrectly can harm your SEO more than having no canonical tags at all. Avoid these common pitfalls:

### Canonical Chains

A canonical chain occurs when URL A points to URL B, which points to URL C. Google may not follow the chain all the way through, leaving your intended canonical unrecognized.

**Bad:** \`/page?sort=price\` → \`/page?\` → \`/page\`

**Good:** All variants point directly to \`/page\`

### Self-Referencing Issues

While self-referencing canonicals (a page canonicalizing to itself) are actually **good practice**, problems arise when:

- The self-referencing URL doesn't match the actual page URL
- Protocol mismatches (HTTP page canonicalizing to HTTPS of itself)
- Trailing slash inconsistencies

**Best practice:** Every page should have a self-referencing canonical that matches its actual URL exactly. This protects against scrapers and parameter-based duplicates.

### Cross-Domain Canonical Mistakes

Cross-domain canonicals are valid for syndicated content, but mistakes include:

- Canonicalizing to an external page you don't control
- Both sites canonicalizing to each other (circular reference)
- Forgetting to update canonicals when content relationships change

### Conflicting Signals

Mixed signals confuse search engines:

- **Canonical + noindex** — A page with both a canonical tag and noindex directive sends contradictory signals. Remove one.
- **Canonical to redirected URL** — Canonicals should point to the final destination URL, not one that redirects.
- **Canonical to 404 page** — Always verify canonical URLs return 200 status.
- **Robots.txt blocking canonical URL** — Search engines can't crawl the canonical URL, making the tag useless.

### Multiple Canonical Tags

Having more than one canonical tag on a page causes Google to ignore them all. This often happens when CMS plugins or themes add canonicals automatically.`,
        codeExamples: [
          {
            language: 'text',
            code: `# Common Mistakes Examples

❌ Canonical Chain:
/page?filter=red → canonical: /page? → canonical: /page
(Google may not follow the chain)

✅ Direct Canonical:
/page?filter=red → canonical: /page
/page? → canonical: /page
(All variants point directly to preferred URL)

❌ Conflicting Signals:
<link rel="canonical" href="https://example.com/page/" />
<meta name="robots" content="noindex" />
(Contradictory: "index this page" + "don't index this page")

✅ Clear Signal:
<link rel="canonical" href="https://example.com/page/" />
(Only the canonical directive)

❌ Multiple Canonicals:
<link rel="canonical" href="https://example.com/page/" />
<link rel="canonical" href="https://example.com/other-page/" />
(Google ignores both)

✅ Single Canonical:
<link rel="canonical" href="https://example.com/page/" />
(One clear directive)`,
            description: 'Common canonical tag mistakes and their correct implementations',
          },
        ],
      },
      {
        title: 'Pagination and Canonicals',
        content: `Paginated content (like blog archives and product listings) requires special canonical handling. The approach depends on your content structure and SEO goals.

### The Pagination Problem

A paginated series creates multiple URLs:

- \`/blog/\` — Page 1
- \`/blog/page/2/\` — Page 2
- \`/blog/page/3/\` — Page 3

Each page contains different content, so they shouldn't canonicalize to page 1. But they're part of a series, so they need special handling.

### Recommended Approach: Self-Referencing Canonicals

For most pagination scenarios, use **self-referencing canonicals** on each page:

- Page 1: \`<link rel="canonical" href="/blog/" />\`
- Page 2: \`<link rel="canonical" href="/blog/page/2/" />\`
- Page 3: \`<link rel="canonical" href="/blog/page/3/" />\`

This tells search engines each page is its own canonical version while using \`rel="prev"\` and \`rel="next"\` to indicate the series relationship.

### View-All Pages

If you have a "view all" page that shows all paginated content on a single URL, you can canonicalize all paginated pages to it:

- \`/blog/page/2/\` → canonical: \`/blog/all/\`
- \`/blog/page/3/\` → canonical: \`/blog/all/\`

**Note:** Google no longer officially supports \`rel="prev"\` and \`rel="next"\` link attributes, but self-referencing canonicals remain the recommended approach for pagination.`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Pagination: Self-referencing canonicals -->
<!-- Page 1: /blog/ -->
<head>
  <link rel="canonical" href="https://example.com/blog/" />
  <link rel="next" href="https://example.com/blog/page/2/" />
</head>

<!-- Page 2: /blog/page/2/ -->
<head>
  <link rel="canonical" href="https://example.com/blog/page/2/" />
  <link rel="prev" href="https://example.com/blog/" />
  <link rel="next" href="https://example.com/blog/page/3/" />
</head>

<!-- Page 3 (last): /blog/page/3/ -->
<head>
  <link rel="canonical" href="https://example.com/blog/page/3/" />
  <link rel="prev" href="https://example.com/blog/page/2/" />
</head>`,
            description: 'Self-referencing canonicals for paginated content with prev/next links',
          },
        ],
      },
      {
        title: 'Testing and Best Practices',
        content: `Canonical tag implementation requires careful testing to avoid SEO issues. Follow these best practices and use Google's tools to verify your setup.

### Google's Official Guidelines

According to Google's official documentation on canonicalization:

1. **Use absolute URLs** — Always include the full URL including protocol and domain
2. **Don't canonicalize to 404 pages** — Ensure canonical URLs are accessible
3. **Don't create chains** — All pages should point directly to the final canonical
4. **Keep canonicals in sitemaps** — List only canonical URLs in your XML sitemap
5. **Implement redirects when possible** — 301 redirects are stronger signals than canonicals

### Testing Tools

**Google Search Console URL Inspection** — Enter any URL to see what Google considers its canonical. Shows if Google respects your canonical tag or chose a different URL.

**Google Search Console Index Coverage Report** — Shows "Duplicate without canonical" errors and which URLs Google chose as canonicals.

**Third-party crawlers** — Tools like Screaming Frog can extract and validate canonical tags across your site.

### Best Practices Checklist

1. **Every page needs a canonical** — Even if self-referencing
2. **Use absolute URLs** — \`https://example.com/page/\` not \`/page/\`
3. **Verify canonicals resolve** — They must return 200 status
4. **Match sitemap URLs** — Your sitemap should list canonical URLs only
5. **Monitor via Search Console** — Check for canonical conflicts regularly
6. **Implement at the CMS level** — Automate canonical generation to avoid human error`,
        codeExamples: [],
      },
    ],
    checklist: [
      { text: 'Add self-referencing canonical tag to every page on your site', priority: 'critical' },
      { text: 'Use absolute URLs in canonical tags (include https:// and full domain)', priority: 'critical' },
      { text: 'Canonicalize parameter URLs to clean versions (remove tracking codes, filters)', priority: 'critical' },
      { text: 'Ensure canonical URLs return 200 status and are not blocked by robots.txt', priority: 'important' },
      { text: 'Avoid canonical chains—all pages should point directly to the final canonical', priority: 'important' },
      { text: 'Match canonical URLs with your XML sitemap entries', priority: 'important' },
      { text: 'Use HTTP headers for canonicals on non-HTML content (PDFs, images)', priority: 'recommended' },
      { text: 'Verify canonicals via Google Search Console URL Inspection tool', priority: 'recommended' },
    ],
    relatedGuides: ['https-security', 'xml-sitemap'],
  },
  {
    slug: 'robots-txt',
    title: 'Robots.txt Configuration',
    summary: 'Control how search engines crawl your site with robots.txt directives to optimize crawl budget and protect sensitive paths.',
    category: 'technical',
    icon: '🤖',
    readTime: 5,
    sections: [
      {
        title: 'What is Robots.txt?',
        content: `**Robots.txt** is a plain text file placed at the root of your website that instructs search engine crawlers (also called bots, spiders, or user-agents) which pages or files they can or cannot request from your site. It's the first file search engines look for when they visit your domain.

Located at \`https://yourdomain.com/robots.txt\`, this file acts as a gatekeeper—telling crawlers like Googlebot, Bingbot, and others which areas of your site are open for exploration and which should be left alone.

### The Purpose of Robots.txt

The primary purposes of a robots.txt file are:

1. **Manage crawl budget** — Prevent crawlers from wasting time on unimportant pages
2. **Protect sensitive paths** — Keep private areas like admin panels out of search indexes
3. **Control server load** — Reduce the number of requests bots make to your server
4. **Guide crawlers to important content** — Direct attention to pages that matter most

### Where to Place Robots.txt

The robots.txt file **must be placed at the root of your domain**. There can only be one robots.txt file per domain, and it must be accessible at the exact URL:

\`https://example.com/robots.txt\`

**Important placement rules:**
- Must be at the domain root (not in a subdirectory)
- Must be accessible via HTTP with a 200 status code
- Only one robots.txt file per domain (subdomains can have their own)
- File name must be exactly \`robots.txt\` (lowercase)

If your site uses subdomains (like \`blog.example.com\` or \`shop.example.com\`), each subdomain needs its own robots.txt file at its respective root.`,
        codeExamples: [],
      },
      {
        title: 'Why Robots.txt Matters for SEO',
        content: `While robots.txt doesn't directly improve rankings, it plays a crucial role in how search engines discover and crawl your content. Proper configuration can significantly impact your SEO performance.

### Crawl Budget Optimization

**Crawl budget** is the number of pages Google allocates to crawl on your site within a given timeframe. For large sites (thousands of pages), crawl budget matters—you want Google spending its limited time on your most important content.

Without robots.txt guidance, crawlers may waste valuable crawl budget on:
- Duplicate content pages (print versions, sort orders)
- Auto-generated pages (calendar views, search results)
- Admin and backend pages
- Low-value content archives

By blocking these paths, you ensure crawlers focus on pages that actually drive traffic and conversions.

### Indexation Control

While robots.txt doesn't directly prevent pages from being indexed (use \`noindex\` meta tags for that), it influences which pages search engines discover and crawl. Pages blocked by robots.txt won't be crawled, which typically means they won't be indexed.

### Server Performance

Aggressive crawling can strain server resources. Robots.txt helps manage the pace at which bots access your site, protecting server performance for real users.

### Preventing Sensitive Content Exposure

While robots.txt is not a security measure, it helps keep sensitive paths out of search results. However, **never rely on robots.txt for security**—anyone can view the file and see which paths you're hiding.

### Directing Crawlers to Your Sitemap

Robots.txt is the standard place to declare your XML sitemap location, helping search engines discover all your important pages efficiently.`,
        codeExamples: [],
      },
      {
        title: 'Allow and Disallow Directives',
        content: `The **Disallow** and **Allow** directives are the core building blocks of robots.txt. They tell search engine crawlers which paths they can or cannot access.

### User-agent Directive

Every set of directives starts with a \`User-agent\` line that specifies which crawler the rules apply to. You can target specific bots or use the wildcard \`*\` to apply rules to all crawlers.

**Syntax:** \`User-agent: [bot-name-or-*]\`

### Disallow Directive

The \`Disallow\` directive specifies which paths are off-limits to the specified user-agent.

**Syntax:** \`Disallow: [path]\`

**Examples:**
- \`Disallow: /\` — Blocks the entire site
- \`Disallow: /admin/\` — Blocks the /admin/ directory
- \`Disallow: /private-page.html\` — Blocks a specific page
- \`Disallow:\` (empty) — Allows everything (no restrictions)

### Allow Directive

The \`Allow\` directive overrides \`Disallow\` for specific paths. This is useful when you've blocked a directory but want to allow access to specific files within it.

**Syntax:** \`Allow: [path]\`

**Examples:**
- \`Allow: /assets/images/\` — Explicitly allows a specific path
- \`Allow: /public/file.html\` — Allows a specific file

### Rule Priority and Order

When both Allow and Disapply apply to a URL, Google uses the **more specific rule** (the longer path). However, the order of rules also matters—place more specific rules after general ones for clarity.`,
        codeExamples: [
          {
            language: 'text',
            code: `# Block all crawlers from the entire site
User-agent: *
Disallow: /

# Block all crawlers from specific directories
User-agent: *
Disallow: /admin/
Disallow: /private/
Disallow: /temp/

# Allow a specific file in a blocked directory
User-agent: *
Disallow: /private/
Allow: /public-info.html`,
            description: 'Basic Allow and Disallow directive examples',
          },
          {
            language: 'text',
            code: `# Block everything except images
User-agent: *
Disallow: /
Allow: /images/
Allow: /assets/*.jpg$
Allow: /assets/*.png$

# In this example:
# - All paths are blocked by default
# - /images/ directory is allowed
# - .jpg and .png files in /assets/ are allowed`,
            description: 'Using Allow to override Disallow for specific paths',
          },
        ],
      },
      {
        title: 'Sitemap Reference in Robots.txt',
        content: `The \`Sitemap\` directive tells search engines where to find your XML sitemap. While not required, including your sitemap location in robots.txt is a best practice that helps crawlers discover all your important pages.

### Sitemap Directive Syntax

The sitemap directive is simple: just provide the full URL to your XML sitemap file.

**Syntax:** \`Sitemap: [full-url]\`

### Benefits of Declaring Your Sitemap

1. **Discovery** — Search engines automatically find your sitemap without manual submission
2. **Centralized information** — All crawl guidance in one file
3. **Multiple sitemaps** — You can declare multiple sitemaps for different content types
4. **Cross-subdomain sitemaps** — Reference sitemaps on different domains

### Where to Place Sitemap Directives

Sitemap directives can appear anywhere in your robots.txt file—typically at the beginning or end. Unlike User-agent groups, sitemap directives are independent and apply globally.

### Multiple Sitemaps

Large sites often split sitemaps by content type (pages, images, videos, news). You can declare all of them in your robots.txt:`,
        codeExamples: [
          {
            language: 'text',
            code: `# robots.txt with sitemap declaration
User-agent: *
Disallow: /admin/
Disallow: /search/

Sitemap: https://example.com/sitemap.xml

# Multiple sitemaps for different content types
Sitemap: https://example.com/sitemap-pages.xml
Sitemap: https://example.com/sitemap-images.xml
Sitemap: https://example.com/sitemap-news.xml`,
            description: 'Robots.txt with single and multiple sitemap declarations',
          },
          {
            language: 'text',
            code: `# Sitemap on a different domain (useful for cross-subdomain)
User-agent: *
Disallow: /private/

Sitemap: https://sitemaps.example.com/main-sitemap.xml`,
            description: 'Sitemap directive can reference sitemaps on external domains',
          },
        ],
      },
      {
        title: 'Wildcards and Patterns',
        content: `Robots.txt supports two wildcard characters that give you powerful pattern-matching capabilities: the asterisk \`*\` and the dollar sign \`$\`.

### Asterisk Wildcard (*)

The \`*\` matches any sequence of characters (including zero characters). Use it to block or allow groups of similar URLs.

**Common use cases:**
- Blocking URLs with specific query parameters
- Blocking file types across all directories
- Blocking auto-generated pages with patterns

### Dollar Sign Wildcard ($)

The \`$\` matches the end of a URL. Use it to target specific file extensions or exact URL endings.

**Common use cases:**
- Blocking all PDF files
- Blocking specific file types (\`.pdf\`, \`.xls\`)
- Blocking pages with specific endings

### Combining Wildcards

You can combine \`*\` and \`$\` in a single rule for precise targeting.

**Pattern matching examples:**
- \`/*.pdf$\` — Any path ending in .pdf
- \`/page*/\` — Any path starting with "page"
- \`/*?print=\` — Any URL containing "?print="
- \`/*.js$\` — Any JavaScript file at any path depth`,
        codeExamples: [
          {
            language: 'text',
            code: `# Block all PDF files from being crawled
User-agent: *
Disallow: /*.pdf$

# Block all URLs with "print" parameter
User-agent: *
Disallow: /*?print=

# Block all files in any "tmp" directory at any level
User-agent: *
Disallow: /*/tmp/

# Block specific file types
User-agent: *
Disallow: /*.xls$
Disallow: /*.doc$
Disallow: /*.pdf$

# Block search results pages with query parameters
User-agent: *
Disallow: /search?
Disallow: /*?sort=
Disallow: /*?filter=`,
            description: 'Wildcard patterns for blocking specific URL patterns',
          },
          {
            language: 'text',
            code: `# Allow images but block other assets
User-agent: *
Disallow: /assets/
Allow: /assets/*.jpg$
Allow: /assets/*.png$
Allow: /assets/*.gif$
Allow: /assets/*.webp$

# Block admin and test pages
User-agent: *
Disallow: /admin*
Disallow: /*test/
Disallow: /staging/`,
            description: 'Combining wildcards with Allow and Disallow directives',
          },
        ],
      },
      {
        title: 'Common Robots.txt Mistakes',
        content: `Even experienced developers make mistakes with robots.txt. Understanding these common pitfalls helps you avoid SEO problems.

### 1. Blocking CSS and JavaScript

One of the most damaging mistakes is blocking CSS and JavaScript files. Modern search engines need to render pages to understand them fully—if you block these resources, Google can't see your page as users do.

**Problem:** Blocking \`/assets/\` or \`/js/\` directories prevents Google from rendering your pages correctly.

**Solution:** Allow CSS and JavaScript files explicitly.

### 2. Blocking Your Entire Site

A single \`Disallow: /\` directive blocks your entire site from being crawled. This is sometimes done accidentally during development or staging and forgotten when going live.

**Impact:** Your site disappears from search results entirely.

**Prevention:** Always check your robots.txt before going live. Use Google Search Console to test.

### 3. Wrong Path Format

Paths in robots.txt are **case-sensitive** and must match your actual URL structure exactly.

**Common errors:**
- Wrong case: \`/Admin/\` vs \`/admin/\`
- Missing trailing slash: \`/admin\` vs \`/admin/\`
- Not matching URL-encoded characters

### 4. Using Robots.txt for Security

**Critical misconception:** Robots.txt is NOT a security measure. It's merely a suggestion to well-behaved crawlers.

**Problems with relying on robots.txt for security:**
- Malicious bots ignore robots.txt completely
- Anyone can view your robots.txt and see "hidden" paths
- Disallowed pages can still be indexed if linked from other sites

**Solution:** Use proper authentication, authorization, and \`noindex\` tags for sensitive content.

### 5. Conflicting Rules

Multiple rules can conflict, causing unexpected behavior. Always test your robots.txt with Google's testing tools.

### 6. Forgetting About Crawl-Delay

Some crawlers respect a \`Crawl-delay\` directive that slows their requests. While not part of the official standard, it can help manage server load—though Google doesn't support it.`,
        codeExamples: [
          {
            language: 'text',
            code: `# MISTAKE: Blocking CSS/JS prevents proper rendering
User-agent: *
Disallow: /assets/    # This blocks CSS and JS!
Disallow: /js/        # Also blocks JavaScript

# CORRECT: Allow CSS and JS for search engines
User-agent: *
Disallow: /assets/
Allow: /assets/*.css$
Allow: /assets/*.js$
Allow: /assets/*.woff2$

# Or better: Only block truly private paths
User-agent: *
Disallow: /admin/
Disallow: /private/
# Don't block CSS/JS at all`,
            description: 'Mistake: blocking CSS and JavaScript prevents Google from rendering pages',
          },
          {
            language: 'text',
            code: `# DANGEROUS: This blocks your entire site!
User-agent: *
Disallow: /

# This is useful for staging/development sites
# but catastrophic if left on a production site

# Always verify your robots.txt doesn't contain this
# unless you intentionally want to block everything`,
            description: 'The most dangerous mistake: blocking your entire site from crawlers',
          },
        ],
      },
      {
        title: 'User-Agent Targeting',
        content: `Different search engines use different crawler bots, each with its own user-agent name. You can create specific rules for individual crawlers or use the wildcard \`*\` to apply rules to all bots.

### The Wildcard User-agent (*)

Using \`User-agent: *\` applies rules to all crawlers that respect robots.txt. This is the most common approach.

### Targeting Specific Bots

For more granular control, target specific crawlers by name:

| Search Engine | User-agent |
|---------------|------------|
| Google | \`Googlebot\` |
| Google Images | \`Googlebot-Image\` |
| Bing | \`bingbot\` |
| Yahoo | \`Slurp\` |
| DuckDuckGo | \`DuckDuckBot\` |
| Baidu | \`Baiduspider\` |
| Yandex | \`Yandex\` |

### Rule Group Order

When you have multiple user-agent groups, crawlers look for their specific user-agent first. If none exists, they fall back to \`*\` rules.

**Best practice:** Place specific user-agent rules before generic \`*\` rules for clarity.`,
        codeExamples: [
          {
            language: 'text',
            code: `# Rules for all crawlers (default)
User-agent: *
Disallow: /admin/
Disallow: /private/
Disallow: /search?

# Specific rules for Googlebot only
User-agent: Googlebot
Disallow: /temp/
Allow: /special-google-content/

# Specific rules for Bingbot only
User-agent: bingbot
Disallow: /heavy-pages/
Crawl-delay: 10

# Block an aggressive bot completely
User-agent: BadBot
Disallow: /`,
            description: 'Targeting specific user-agents with custom rules for each crawler',
          },
          {
            language: 'text',
            code: `# Allow all bots to access everything
User-agent: *
Disallow:

# This empty Disallow means "disallow nothing"
# Equivalent to allowing access to everything

# A completely permissive robots.txt
User-agent: *
Allow: /
Disallow:`,
            description: 'Permissive robots.txt that allows all crawlers full access',
          },
        ],
      },
      {
        title: 'Security Considerations',
        content: `A critical misconception about robots.txt is that it provides security. It does not. Understanding what robots.txt can and cannot do is essential for protecting your site.

### Robots.txt is NOT a Security Measure

The robots.txt file is publicly viewable—anyone can access \`https://yourdomain.com/robots.txt\` and see exactly which paths you're trying to hide. Malicious bots and scrapers completely ignore robots.txt directives.

**Never use robots.txt to hide:**
- Admin panel URLs
- User data directories
- API endpoints
- Configuration files
- Any truly sensitive information

### Disallowed Pages Can Still Be Indexed

Blocking a page in robots.txt prevents crawling, but doesn't prevent indexing. Google can still index a blocked URL if:

1. It's linked from another site
2. It appears in your sitemap
3. Google discovered it before you added the block

### The noindex Alternative

For pages you want to keep out of search results, use \`noindex\` directives instead of (or in addition to) robots.txt:

**Meta tag approach:** \`<meta name="robots" content="noindex">\`

**HTTP header approach:** \`X-Robots-Tag: noindex\`

The \`noindex\` directive explicitly tells search engines not to include a page in their index—much more reliable than robots.txt for preventing indexing.

### When to Use Each Approach

| Goal | Solution |
|------|----------|
| Prevent crawling | robots.txt Disallow |
| Prevent indexing | noindex meta tag or header |
| Block from search results | noindex (not robots.txt) |
| Manage crawl budget | robots.txt |
| Hide sensitive content | Proper authentication (not robots.txt) |

### Best Practice: Defense in Depth

For maximum protection:
1. Use **authentication** for truly sensitive areas
2. Use **noindex** to prevent indexing
3. Use **robots.txt** to prevent crawling
4. Never rely on robots.txt alone for security`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Meta tag for noindex (in the <head> section) -->
<meta name="robots" content="noindex, nofollow">

<!-- This tells search engines:
     - noindex: Don't include this page in search results
     - nofollow: Don't follow links on this page -->

<!-- For specific search engines -->
<meta name="googlebot" content="noindex">`,
            description: 'Using noindex meta tag to prevent indexing (more reliable than robots.txt)',
          },
          {
            language: 'apache',
            code: `# .htaccess - Add X-Robots-Tag header for non-HTML files
<FilesMatch "\\.pdf$">
  Header set X-Robots-Tag "noindex, nofollow"
</FilesMatch>

# Block indexing of all PDF files
# This works for non-HTML content where you can't add meta tags`,
            description: 'X-Robots-Tag HTTP header for preventing indexing of non-HTML files',
          },
        ],
      },
      {
        title: 'Technical Specifications',
        content: `Understanding the technical constraints of robots.txt ensures your implementation works correctly across all search engines.

### File Location and Access

- **Location:** Must be at the domain root (\`/robots.txt\`)
- **Protocol:** Accessible via HTTP or HTTPS
- **Status code:** Must return HTTP 200 (OK)
- **Content type:** Should be served as \`text/plain\`

### File Size Limits

Google limits the processing of robots.txt files:

- **Maximum file size:** 500 KiB (approximately 500KB)
- **Behavior:** Google truncates processing at 500 KiB
- **Recommendation:** Keep files well under this limit

For complex sites, use pattern matching and wildcard rules rather than listing every path individually.

### Case Sensitivity

Both the file name and paths are **case-sensitive**:

- File must be named exactly \`robots.txt\` (lowercase)
- Paths must match your actual URL case exactly
- \`/Admin/\` and \`/admin/\` are different paths

### Character Encoding

- Robots.txt should be encoded in **UTF-8**
- Characters outside the ASCII range should be URL-encoded
- Google recommends using Punycode for international domain names

### Crawl-Delay Directive

The \`Crawl-delay\` directive is **not part of the official standard** but is supported by some crawlers (Bing, Yandex, Baidu). Google ignores this directive.

**Syntax:** \`Crawl-delay: [seconds]\`

**Purpose:** Requests the crawler to wait X seconds between requests.

### Comments in Robots.txt

Use \`#\` to add comments. Everything after \`#\` on a line is ignored.

**Note:** Comments cannot be placed on the same line as a directive. Place them on their own lines.`,
        codeExamples: [
          {
            language: 'text',
            code: `# Robots.txt technical example
# Last updated: 2024-01-15
# This file must be at https://example.com/robots.txt

# All bots - block admin and private areas
User-agent: *
Disallow: /admin/      # Case-sensitive: must match actual path
Disallow: /Private/    # Different from /private/
Disallow: /temp/

# Bing-specific: request slower crawling
User-agent: bingbot
Disallow: /admin/
Crawl-delay: 10        # Bing supports this; Google ignores it

# Sitemap declaration
Sitemap: https://example.com/sitemap.xml

# File size recommendation: keep under 500KB
# This example is well under the limit`,
            description: 'Technical robots.txt example with comments and proper formatting',
          },
        ],
      },
      {
        title: 'Testing Your Robots.txt',
        content: `Always test your robots.txt implementation before relying on it. Google provides free tools to validate and debug your configuration.

### Google Search Console Robots.txt Report

Google Search Console includes a dedicated robots.txt report that shows:
- Current robots.txt content
- Any parsing errors
- When Google last fetched it
- HTTP status code

**Access:** Search Console → Settings → robots.txt Report

### Google Robots Testing Tool

For quick testing, use Google's online robots.txt testing tool:

**URL:** support.google.com/webmasters/answer/6062598

**Features:**
- Test if a URL is blocked or allowed
- See which rule applies
- Identify syntax errors
- Test changes before publishing

### Manual Testing Steps

1. **Check file accessibility:** Visit \`yourdomain.com/robots.txt\` in a browser
2. **Verify HTTP status:** Ensure it returns 200 OK
3. **Test with Google's tool:** Validate rules work as expected
4. **Monitor Search Console:** Check for errors after deployment
5. **Test specific URLs:** Verify important pages aren't accidentally blocked

### Common Testing Scenarios

**Test if Google can access your CSS/JS:**
- Enter the CSS/JS file URL in the testing tool
- Verify it shows "Allowed"

**Test if admin pages are blocked:**
- Enter \`/admin/\` URLs
- Verify they show "Blocked"

**Test sitemap discovery:**
- Ensure sitemap directive is present
- Verify sitemap URL is correct and accessible`,
        codeExamples: [],
      },
    ],
    checklist: [
      { text: 'Place robots.txt at the domain root (https://example.com/robots.txt)', priority: 'critical' },
      { text: 'Never block CSS or JavaScript files that affect page rendering', priority: 'critical' },
      { text: 'Test with Google Search Console robots.txt testing tool before deploying', priority: 'critical' },
      { text: 'Use noindex meta tags for pages you want excluded from search results', priority: 'important' },
      { text: 'Include Sitemap directive to help search engines discover your pages', priority: 'important' },
      { text: 'Remember robots.txt is not a security measure—use proper authentication for sensitive content', priority: 'important' },
      { text: 'Keep file size under 500KB to ensure Google processes it completely', priority: 'important' },
      { text: 'Use wildcards (* and $) for pattern matching instead of listing every URL', priority: 'recommended' },
      { text: 'Reference Google\'s official robots.txt specifications for complex implementations', priority: 'recommended' },
    ],
    relatedGuides: ['xml-sitemap', 'https-security'],
  },
  {
    slug: 'xml-sitemap',
    title: 'XML Sitemap Creation',
    summary: 'Help search engines discover and index all your important pages with a properly structured XML sitemap.',
    category: 'technical',
    icon: '🗺️',
    readTime: 5,
    sections: [
      {
        title: 'What is an XML Sitemap?',
        content: `An **XML sitemap** is a file that lists all the important URLs on your website, helping search engines like Google and Bing discover and crawl your content more efficiently. Think of it as a roadmap that guides search engine crawlers to every valuable page on your site.

The sitemap uses a specific XML format defined by the **sitemaps.org protocol**, a standard jointly created by Google, Yahoo, and Microsoft. This structured format makes it easy for search engines to parse and understand your site's content hierarchy.

### Key Information in a Sitemap

- **URL locations** — The web addresses of your pages
- **Last modification dates** — When content was last updated
- **Change frequency** — How often the page typically changes
- **Priority** — Relative importance of pages on your site

### When You Need a Sitemap

Google recommends using a sitemap when your site:

- Has many pages that aren't well-linked together
- Has pages with few or no internal links pointing to them (orphan pages)
- Has a large archive of content pages that are isolated from each other
- Is new and has few external links pointing to it
- Uses rich media content (images, video) that appears in Google search

Even for smaller sites, having a sitemap ensures search engines know about all your important pages.`,
        codeExamples: [],
      },
      {
        title: 'Why XML Sitemaps Matter for SEO',
        content: `XML sitemaps are a powerful tool for improving how search engines discover, crawl, and index your website. While not required for SEO, they provide significant benefits for most websites.

### Improved Crawlability

Sitemaps help search engines find pages they might otherwise miss. This is especially important for:

- **Large websites** with thousands of pages where some might be buried deep in the site architecture
- **New websites** with few external backlinks pointing to them
- **Sites with poor internal linking** where some pages have few incoming links
- **Dynamic sites** where content changes frequently

### Faster Indexation

When you publish new content, a sitemap helps search engines discover it quickly. Google can use your sitemap to identify new or updated pages and prioritize crawling them, reducing the time between publishing and appearing in search results.

### Better Content Discovery

Sitemaps tell search engines which pages are important. By including priority values and modification dates, you signal which content deserves attention and which pages have been recently updated.

### Control Over What Gets Indexed

While robots.txt tells search engines what NOT to crawl, sitemaps tell them what SHOULD be crawled. This positive guidance helps ensure your most important content gets discovered and indexed.

### SEO Impact on Large Sites

For large e-commerce sites or content-heavy platforms, sitemaps are critical. They help search engines:

- Discover deep pages that might be missed during normal crawling
- Understand the relationship between pages
- Allocate crawl budget more efficiently
- Find new content faster after updates

### Ranking Considerations

While having a sitemap doesn't directly improve rankings, it indirectly benefits SEO by ensuring all your important pages are discoverable and indexed. Pages that aren't indexed can't rank.`,
        codeExamples: [],
      },
      {
        title: 'XML Sitemap Structure',
        content: `The XML sitemap follows a specific structure defined by the sitemaps.org protocol. Understanding this structure helps you create valid, effective sitemaps.

### Required Elements

Every sitemap must include these elements:

- **\`<urlset>\`** — The container for all URL entries
- **\`<url>\`** — Individual page entry
- **\`<loc>\`** — The URL of the page (absolute URL required)

### Optional Elements

You can include additional information for each URL:

- **\`<lastmod>\`** — When the page was last modified (W3C Datetime format)
- **\`<changefreq>\` — How often the page changes (always, hourly, daily, weekly, monthly, yearly, never)
- **\`<priority>\` — Relative priority from 0.0 to 1.0 (default is 0.5)

### Sitemap Protocol Details

The xmlns attribute specifies the sitemap protocol version. Always use the sitemaps.org schema URL.

**Key rules:**
- Maximum 50,000 URLs per sitemap file
- Maximum 50MB uncompressed file size
- Use UTF-8 encoding
- Escape special characters in URLs (&, ', ", <, >)
- Use absolute URLs (include protocol and domain)

### Understanding Priority Values

The priority value is **relative to other pages on your site**, not an absolute measure. A priority of 1.0 means "most important on this site," not "most important on the entire web."

**Priority guidelines:**
- **1.0** — Homepage and critical landing pages
- **0.8-0.9** — Important category and product pages
- **0.5-0.7** — Standard content pages
- **0.0-0.4** — Low-priority or outdated content

**Note:** Google states that priority values are suggestions and may not significantly affect crawl frequency or rankings.`,
        codeExamples: [
          {
            language: 'xml',
            code: `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://example.com/</loc>
    <lastmod>2024-01-15</lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://example.com/about/</loc>
    <lastmod>2024-01-10</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc>https://example.com/blog/</loc>
    <lastmod>2024-01-14</lastmod>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
</urlset>`,
            description: 'Basic XML sitemap with required and optional elements',
          },
        ],
      },
      {
        title: 'Submitting to Google Search Console',
        content: `Once you've created your sitemap, you need to tell search engines where to find it. Google Search Console is the primary tool for managing sitemaps with Google.

### Submitting via Google Search Console

1. **Open Google Search Console** and select your property
2. **Navigate to Sitemaps** in the left sidebar
3. **Enter your sitemap URL** (e.g., \`sitemap.xml\` or \`sitemap-index.xml\`)
4. **Click Submit**

Google will validate your sitemap and begin processing it. You can view the status, including any errors or warnings, in the Sitemaps report.

### Ping Method

You can also notify Google about your sitemap using a simple HTTP request:

\`https://www.google.com/ping?sitemap=https://example.com/sitemap.xml\`

This method is useful for automated sitemap updates but doesn't replace Search Console submission.

### Sitemap Status in Search Console

After submission, monitor these metrics:

- **Discovered URLs** — How many URLs Google found in your sitemap
- **Indexed URLs** — How many of those URLs are actually indexed
- **Coverage warnings** — Issues like redirecting URLs or pages with errors
- **Errors** — Problems that prevent processing

### Why URLs Might Not Be Indexed

Not all submitted URLs get indexed. Common reasons include:

- **Duplicate content** — Page is similar to another indexed page
- **Low quality** — Content doesn't meet Google's quality standards
- **Blocked by robots.txt** — Sitemap URLs are blocked by robots directives
- **Redirects or errors** — URL redirects or returns error status
- **Canonical issues** — A different URL is specified as canonical

### Resubmitting After Updates

When you add new content or update existing pages significantly, you can resubmit your sitemap in Search Console to notify Google of the changes. However, Google will eventually recrawl your sitemap automatically.`,
        codeExamples: [],
      },
      {
        title: 'Dynamic Sitemap Generation',
        content: `For websites with frequently changing content, manually updating sitemaps isn't practical. Dynamic sitemap generation automatically updates your sitemap whenever content changes.

### Why Dynamic Sitemaps?

- **E-commerce sites** — Products added and removed daily
- **Blogs** — New posts published regularly
- **News sites** — Multiple articles published per day
- **User-generated content** — Forums, directories, classifieds

### Implementation Approaches

**Server-Side Generation**: Your server generates the sitemap on demand or via scheduled tasks. When Google requests \`/sitemap.xml\`, your application queries the database for all URLs and renders the XML response.

**CMS Plugins**: Most content management systems have plugins that generate sitemaps automatically:
- **WordPress**: Yoast SEO, Rank Math, Google XML Sitemaps
- **Shopify**: Built-in sitemap generation
- **Drupal**: XML Sitemap module
- **Magento**: Native sitemap functionality

**Build-Time Generation**: Static site generators like Next.js, Gatsby, and Hugo can generate sitemaps during the build process.

### Update Frequency

How often should your sitemap update?

| Site Type | Recommended Update Frequency |
|-----------|------------------------------|
| News sites | Real-time or hourly |
| E-commerce | Daily or with each product change |
| Blogs | With each new post |
| Corporate sites | Weekly or monthly |

### Performance Considerations

For large sites, generating sitemaps on every request can strain your server. Consider:

- **Caching** — Cache generated sitemaps and regenerate periodically
- **Incremental updates** — Only update changed portions
- **Pre-generation** — Generate during off-peak hours
- **CDN delivery** — Serve cached sitemaps from a CDN`,
        codeExamples: [
          {
            language: 'javascript',
            code: `// Next.js sitemap generation (pages/api/sitemap.ts)
import { GetServerSideProps } from 'next';

export const getServerSideProps: GetServerSideProps = async ({ res }) => {
  // Fetch URLs from database or CMS
  const pages = await getPages(); // Your data fetching
  
  const sitemap = \`<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
\${pages.map((page) => \`  <url>
    <loc>https://example.com\${page.slug}</loc>
    <lastmod>\${page.updatedAt}</lastmod>
  </url>\`).join('\\n')}
</urlset>\`;

  res.setHeader('Content-Type', 'application/xml');
  res.write(sitemap);
  res.end();
  
  return { props: {} };
};`,
            description: 'Next.js dynamic sitemap generation example',
          },
        ],
      },
      {
        title: 'Sitemap Index Files for Large Sites',
        content: `When your website exceeds the sitemap limits (50,000 URLs or 50MB), you need to split your sitemap into multiple files and use a **sitemap index file** to list them all.

### Sitemap Limits

A single sitemap file can contain:
- **Maximum 50,000 URLs**
- **Maximum 50MB uncompressed**

If you exceed either limit, you must use a sitemap index.

### Sitemap Index Structure

The sitemap index file uses \`<sitemapindex>\` instead of \`<urlset>\` and lists individual sitemap files with their locations and last modification dates.

### When to Use a Sitemap Index

Consider splitting your sitemap when:

- Your site has more than 50,000 indexable URLs
- Your sitemap file exceeds 50MB uncompressed
- You want to organize sitemaps by content type (pages, products, images)
- Different content types have different update frequencies

### Organizing Multiple Sitemaps

Common strategies for organizing split sitemaps:

**By content type:**
- \`sitemap-pages.xml\` — Static pages
- \`sitemap-products.xml\` — Product pages
- \`sitemap-blog.xml\` — Blog posts
- \`sitemap-images.xml\` — Image sitemap

**By category:**
- \`sitemap-electronics.xml\`
- \`sitemap-clothing.xml\`
- \`sitemap-furniture.xml\`

**By date:**
- \`sitemap-2024.xml\`
- \`sitemap-2023.xml\`
- \`sitemap-archive.xml\`

### Submitting Sitemap Indexes

Submit your sitemap index file to Google Search Console just like a regular sitemap. Google will automatically discover and process all referenced sitemaps.`,
        codeExamples: [
          {
            language: 'xml',
            code: `<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <sitemap>
    <loc>https://example.com/sitemap-pages.xml</loc>
    <lastmod>2024-01-15</lastmod>
  </sitemap>
  <sitemap>
    <loc>https://example.com/sitemap-products.xml</loc>
    <lastmod>2024-01-14</lastmod>
  </sitemap>
  <sitemap>
    <loc>https://example.com/sitemap-blog.xml</loc>
    <lastmod>2024-01-15</lastmod>
  </sitemap>
</sitemapindex>`,
            description: 'Sitemap index file listing multiple sitemaps for large sites',
          },
        ],
      },
    ],
    checklist: [
      { text: 'Submit your sitemap to Google Search Console', priority: 'critical' },
      { text: 'Include only canonical URLs (no duplicates, redirects, or 404s)', priority: 'critical' },
      { text: 'Keep sitemap updated when content changes', priority: 'important' },
      { text: 'Reference sitemap in robots.txt file', priority: 'important' },
      { text: 'Use sitemap index for sites with 50,000+ URLs', priority: 'important' },
      { text: 'Set appropriate lastmod dates for modified content', priority: 'recommended' },
      { text: 'Use changefreq and priority judiciously (Google may ignore)', priority: 'recommended' },
    ],
    relatedGuides: ['robots-txt', 'internal-links'],
  },

  // Content & Social SEO Guides
  {
    slug: 'open-graph',
    title: 'Open Graph Tags',
    summary: 'Optimize how your content appears when shared on Facebook, LinkedIn, and other social platforms with Open Graph meta tags.',
    category: 'content',
    toolSlug: 'social-media-preview',
    icon: '👍',
    readTime: 5,
    sections: [
      {
        title: 'What is Open Graph?',
        content: `**Open Graph tags** are meta tags that control how your content appears when shared on social media platforms like Facebook, LinkedIn, and Twitter. Originally created by Facebook in 2010, the Open Graph protocol enables any web page to become a rich object in a social graph.

When someone shares a URL on Facebook or LinkedIn, the platform crawls the page looking for Open Graph (OG) tags to determine what title, description, image, and other metadata to display in the preview card. Without OG tags, platforms guess what to show—often with poor results.

### The og: Namespace

All Open Graph tags use the \`og:\` namespace prefix within the \`<meta>\` tag's \`property\` attribute:

\`<meta property="og:title" content="Your Page Title" />\`

This prefix tells social platforms that these are Open Graph properties, distinct from standard HTML meta tags.

### Platforms Using Open Graph

- **Facebook** — Primary user of OG tags; displays rich preview cards in posts
- **LinkedIn** — Uses OG tags for article previews and shared links
- **Twitter** — Falls back to OG tags when Twitter Card tags aren't present
- **WhatsApp, Telegram, iMessage** — Use OG tags for link previews
- **Slack, Discord** — Display OG previews for shared links

Open Graph is the universal standard for social sharing optimization. Even platforms with their own protocols (like Twitter Cards) will fall back to OG tags when their specific tags are missing.`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Basic Open Graph meta tags -->
<head>
  <meta property="og:title" content="Complete Guide to SEO Meta Tags" />
  <meta property="og:description" content="Learn how to optimize your meta tags for better search rankings and social sharing." />
  <meta property="og:image" content="https://example.com/images/seo-guide.jpg" />
  <meta property="og:url" content="https://example.com/guides/seo-meta-tags/" />
  <meta property="og:type" content="article" />
</head>`,
            description: 'Basic Open Graph meta tags in the HTML head section',
          },
        ],
      },
      {
        title: 'Why Open Graph Matters',
        content: `Open Graph tags directly impact how your content performs on social media. A well-optimized social preview can dramatically increase click-through rates and engagement.

### Control Your Social Appearance

Without OG tags, social platforms scrape your page and make their best guess at what to show. They might pick the wrong title, use an irrelevant image, or show an incomplete description. OG tags give you **complete control** over how your content appears when shared.

### Increase Click-Through Rates (CTR)

Studies show that posts with rich, well-crafted previews get **2-3x higher CTR** than plain links. An eye-catching image paired with a compelling title and description makes users want to click.

**What makes a compelling preview:**
- Clear, descriptive title (under 60 characters)
- Engaging description that creates curiosity
- High-quality image that stands out in the feed
- Relevant, trustworthy appearance

### Build Brand Recognition

Consistent OG images and titles help users recognize your content across platforms. When your preview looks professional, it builds trust and credibility before users even visit your site.

### Improve Social Engagement

Rich previews encourage users to:
- Share your content more often
- Spend more time engaging with the preview
- Remember your brand
- Click through to read more

### SEO Benefits

While OG tags don't directly impact search rankings, they indirectly benefit SEO:

- **Social signals** — More shares can lead to more backlinks
- **Traffic** — Higher CTR from social drives more visitors
- **Brand awareness** — Increased visibility across platforms

The bottom line: OG tags are essential for maximizing the value of every social share.`,
        codeExamples: [],
      },
      {
        title: 'Required Open Graph Tags',
        content: `Facebook requires four OG tags for a valid preview. Including these ensures your content displays correctly across all platforms.

### og:title

The title of your content as it should appear in social previews. This is often different from your page's \`<title>\` tag—OG titles can be more engaging or specific.

**Best practices:**
- Keep under 60 characters (Facebook truncates longer titles)
- Include your brand name for recognition
- Make it compelling and click-worthy
- Don't use all caps or excessive punctuation

### og:description

A brief description of your content, typically 1-2 sentences. This appears below the title in social previews.

**Best practices:**
- Keep under 200 characters (150-160 ideal for full display)
- Write a clear, engaging summary
- Include relevant keywords naturally
- Avoid duplicate content from meta description

### og:image

The URL of the image to display in social previews. This is the most impactful OG tag—a good image can dramatically increase engagement.

**Best practices:**
- Use absolute URLs with \`https://\`
- Recommended size: 1200×630 pixels
- Use high-quality images (no pixelation or blurriness)
- Test how your image looks at different crop ratios

### og:url

The canonical URL of the page. This helps platforms consolidate engagement metrics when the same content is shared from different URLs.

**Best practices:**
- Always use the canonical URL
- Include protocol (\`https://\`)
- Avoid tracking parameters in the OG URL

### og:type

The type of content being shared. This affects how Facebook displays the preview and what additional properties are available.

**Common values:**
- \`website\` — Default for most pages
- \`article\` — News articles, blog posts
- \`product\` — E-commerce product pages
- \`profile\` — User profiles`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Required Open Graph Tags -->
<meta property="og:title" content="How to Build a Successful Blog in 2024" />
<meta property="og:description" content="Learn the essential strategies for creating a blog that attracts readers, builds authority, and generates income." />
<meta property="og:image" content="https://example.com/images/blog-success-guide.jpg" />
<meta property="og:url" content="https://example.com/guides/build-successful-blog/" />
<meta property="og:type" content="article" />

<!-- Additional recommended tags -->
<meta property="og:site_name" content="SEOIndicator" />
<meta property="og:locale" content="en_US" />`,
            description: 'Complete set of required and recommended Open Graph tags',
          },
        ],
      },
      {
        title: 'Image Best Practices',
        content: `The \`og:image\` tag is the most critical factor in social preview engagement. A well-chosen image can dramatically increase clicks and shares.

### Recommended Image Dimensions

The ideal OG image size depends on the platform, but **1200×630 pixels** (1.91:1 aspect ratio) is the recommended standard that works well across Facebook, LinkedIn, and Twitter.

**Size breakdown by platform:**

| Platform | Recommended Size | Aspect Ratio |
|----------|------------------|--------------|
| Facebook | 1200×630px | 1.91:1 |
| LinkedIn | 1200×627px | 1.91:1 |
| Twitter | 1200×600px | 2:1 |

**Pro tip:** Use 1200×630px as your standard—it works great everywhere.

### Image Format

- **JPEG** — Best for photographs and complex images
- **PNG** — Best for graphics with text, logos, or transparency
- **WebP** — Modern format with excellent compression (check platform support)

Avoid GIFs for static images—they're larger and may not animate in previews.

### File Size Considerations

Keep image file sizes reasonable for fast loading:

- **Target:** Under 1MB for optimal performance
- **Maximum:** 8MB (Facebook's limit)
- **Recommended:** 100-500KB for best balance of quality and speed

Large images slow down social platform crawlers and may not load in time for the preview.

### Content Guidelines

**Do:**
- Include clear, readable text (large font, high contrast)
- Use brand colors and logos for recognition
- Feature faces or people for emotional connection
- Test how the image looks cropped and uncropped

**Avoid:**
- Too much text (Facebook may flag as low quality)
- Small text that's unreadable when scaled down
- Busy, cluttered images
- Generic stock photos

### Image Dimensions with Meta Tags

For best results, specify image dimensions explicitly:

\`<meta property="og:image:width" content="1200" />\`
\`<meta property="og:image:height" content="630" />\`

This helps platforms render the image immediately without fetching it first.`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- og:image with dimensions for optimal rendering -->
<meta property="og:image" content="https://example.com/images/og-preview.jpg" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:image:alt" content="Guide cover showing SEO tips and strategies" />`,
            description: 'Open Graph image tag with explicit dimensions and alt text for accessibility',
          },
        ],
      },
      {
        title: 'Testing and Debugging',
        content: `Always test your Open Graph tags before publishing. Each platform has tools to preview and debug your social previews.

### Social Media Preview Tool

Use our integrated **Social Media Preview** tool to test how your page will appear across multiple platforms. Simply enter your URL to see real-time previews for Facebook, Twitter, LinkedIn, and more.

### Facebook Sharing Debugger

Facebook's official debugging tool shows exactly how Facebook sees your OG tags:

**URL:** developers.facebook.com/tools/debug/

**What it shows:**
- Current OG tag values
- Missing or invalid tags
- Preview of how your link will appear
- Any warnings or errors

**Pro tip:** Use the "Scrape Again" button to force Facebook to refresh its cache after updating your OG tags.

### LinkedIn Post Inspector

LinkedIn's tool previews how your content will appear in posts:

**URL:** linkedin.com/post-inspector/

**Features:**
- Real-time preview
- Cache clearing
- Recommendations for improvement

### Common Debugging Scenarios

**Image not showing:**
- Check that the image URL is absolute (starts with \`https://\`)
- Verify the image is publicly accessible (not blocked by robots.txt or auth)
- Ensure the image meets size requirements (minimum 200×200px)

**Old preview showing:**
- Use the platform's debugger to force a cache refresh
- Facebook caches for 30+ days unless manually cleared
- Add a query parameter to the URL to test changes quickly

**Missing tags detected:**
- Verify tags are in the \`<head>\` section
- Check for \`property\` attribute (not \`name\`)
- Ensure tags are properly formatted HTML`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Debugging Quick Reference

Facebook Sharing Debugger:
https://developers.facebook.com/tools/debug/

LinkedIn Post Inspector:
https://linkedin.com/post-inspector/

Twitter Card Validator:
https://cards-dev.twitter.com/card-validator

# Common Issues & Fixes

Issue: Image not loading
Fix: Use absolute URL with https://

Issue: Old preview showing
Fix: Use debugger's "Scrape Again" button

Issue: Missing og:title
Fix: Add <meta property="og:title" content="Your Title" />

Issue: Wrong image aspect ratio
Fix: Resize to 1200×630px before uploading`,
            description: 'Quick reference for debugging Open Graph tags across platforms',
          },
        ],
      },
    ],
    checklist: [
      { text: 'Include all required OG tags: og:title, og:description, og:image, og:url', priority: 'critical' },
      { text: 'Use images at 1200×630 pixels for optimal display across platforms', priority: 'critical' },
      { text: 'Use absolute URLs starting with https:// for og:image', priority: 'critical' },
      { text: 'Keep og:title under 60 characters for full display', priority: 'important' },
      { text: 'Keep og:description between 150-200 characters', priority: 'important' },
      { text: 'Test OG tags with Facebook Sharing Debugger before publishing', priority: 'important' },
      { text: 'Add og:image:width and og:image:height for faster rendering', priority: 'important' },
      { text: 'Include og:type to specify content type (article, product, website)', priority: 'recommended' },
      { text: 'Add og:site_name for brand recognition in previews', priority: 'recommended' },
    ],
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
    sections: [
      {
        title: 'What are Twitter Cards?',
        content: `**Twitter Cards** are meta tags that control how your content appears when shared on X (formerly Twitter). They enable rich media previews—complete with images, videos, and detailed descriptions—that make your tweets stand out in the feed and drive significantly higher engagement.

When someone shares a link on X, the platform crawls the page looking for Twitter Card meta tags. These tags determine what title, description, image, and media appear in the preview card attached to the tweet. Without Twitter Card tags, links appear as plain text with minimal preview information.

### How Twitter Cards Work

Twitter Cards use HTML \`<meta>\` tags with the \`name\` attribute set to Twitter-specific properties like \`twitter:card\`, \`twitter:title\`, and \`twitter:image\`. When a user tweets a link, X's crawler reads these tags and generates a rich preview.

### Twitter Cards vs Open Graph

While Open Graph tags work for Facebook, LinkedIn, and other platforms, **Twitter has its own meta tag system**. However, there's a useful fallback behavior:

1. **Twitter-specific tags take priority** — If \`twitter:title\` exists, X uses it
2. **Open Graph fallback** — If Twitter tags are missing, X falls back to \`og:title\`, \`og:image\`, etc.
3. **Best practice: Implement both** — Use Twitter Cards for X control, OG tags for other platforms

This fallback means you can get basic previews with just Open Graph tags, but dedicated Twitter Card tags give you precise control over how your content appears on X.

### Platforms Using Twitter Cards

- **X (Twitter)** — Primary platform for Twitter Cards
- **TweetDeck** — Shows rich previews using Card data
- **Twitter mobile apps** — Display Cards in the timeline
- **Embedded tweets** — Cards appear on websites embedding tweets`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Basic Twitter Card meta tags -->
<head>
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:site" content="@yourusername" />
  <meta name="twitter:title" content="Your Page Title" />
  <meta name="twitter:description" content="A compelling description of your content." />
  <meta name="twitter:image" content="https://example.com/image.jpg" />
</head>`,
            description: 'Basic Twitter Card meta tags in the HTML head section',
          },
        ],
      },
      {
        title: 'Twitter Card Types',
        content: `Twitter offers four card types, each designed for different content scenarios. Choosing the right card type ensures your content displays optimally in the Twitter feed.

### Summary Card

The **summary card** is the default and most versatile option. It displays a small square thumbnail on the right side with title and description on the left. Perfect for blog posts, articles, and general web pages.

**Best for:** Articles, blog posts, landing pages, general content

**Layout:** Small thumbnail (120×120px display), title, description, and domain name

### Summary Large Image Card

The **summary_large_image** card features a prominent full-width photo above the title and description. This card type commands more attention in the feed and typically achieves higher engagement rates.

**Best for:** Visual content, featured articles, product pages, photography

**Layout:** Large landscape photo (up to 506px wide), title, description below

**Pro tip:** Use summary_large_image for most content—larger visuals significantly increase click-through rates.

### Player Card

The **player card** allows you to embed video or audio players directly in tweets. Users can watch videos or listen to audio without leaving Twitter. Requires additional approval from Twitter.

**Best for:** Videos, podcasts, music, live streams, interactive media

**Required additional tags:**
- \`twitter:player\` — URL to the iframe player
- \`twitter:player:width\` — Player width in pixels
- \`twitter:player:height\` — Player height in pixels

**Note:** Player cards require domain approval from Twitter/X before they'll display.

### App Card

The **app card** promotes mobile app downloads with install buttons directly in the tweet. Used primarily by app developers and marketers.

**Best for:** Mobile app promotion (iOS, Android)

**Choosing the Right Card Type:**

| Content Type | Recommended Card |
|--------------|------------------|
| Blog posts | summary_large_image |
| News articles | summary_large_image |
| Product pages | summary_large_image |
| Videos | player |
| Podcasts | player |
| App download pages | app |
| Text-heavy content | summary |`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Summary Card (small thumbnail) -->
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@yourbrand" />
<meta name="twitter:title" content="10 SEO Tips for 2024" />
<meta name="twitter:description" content="Discover the most effective SEO strategies for modern websites." />
<meta name="twitter:image" content="https://example.com/seo-tips-thumb.jpg" />`,
            description: 'Summary card with small thumbnail on the right side',
          },
          {
            language: 'html',
            code: `<!-- Summary Large Image Card (full-width image) -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:site" content="@yourbrand" />
<meta name="twitter:title" content="10 SEO Tips for 2024" />
<meta name="twitter:description" content="Discover the most effective SEO strategies for modern websites." />
<meta name="twitter:image" content="https://example.com/seo-tips-hero.jpg" />`,
            description: 'Summary large image card with prominent full-width image',
          },
          {
            language: 'html',
            code: `<!-- Player Card for video/audio -->
<meta name="twitter:card" content="player" />
<meta name="twitter:site" content="@yourbrand" />
<meta name="twitter:title" content="Watch: SEO Tutorial" />
<meta name="twitter:description" content="Learn SEO fundamentals in 10 minutes." />
<meta name="twitter:image" content="https://example.com/video-thumbnail.jpg" />
<meta name="twitter:player" content="https://example.com/embed/video" />
<meta name="twitter:player:width" content="640" />
<meta name="twitter:player:height" content="360" />`,
            description: 'Player card for embedded video with player URL and dimensions',
          },
        ],
      },
      {
        title: 'Required Twitter Card Tags',
        content: `Twitter requires specific meta tags for Cards to function properly. Understanding each tag's purpose and format ensures your previews display correctly.

### twitter:card (Required)

Specifies the card type. This is the only required Twitter Card tag, but others are strongly recommended.

**Valid values:**
- \`summary\` — Small thumbnail card
- \`summary_large_image\` — Large image card
- \`player\` — Video/audio player card
- \`app\` — App promotion card

**Character limit:** 25 characters (card type names are much shorter)

### twitter:site (Recommended)

The Twitter username of the website or organization. Must include the \`@\` symbol.

**Format:** \`@username\` (with @ symbol)
**Example:** \`@twitterdev\` or \`@nytimes\`

### twitter:title (Recommended)

The title to display in the Card. Should match or complement your page title but can be optimized for Twitter's audience.

**Character limit:** 70 characters (truncation after 70)
**Best practice:** Keep under 60 characters for full visibility

### twitter:description (Recommended)

A description of the content. Should be concise and compelling—this is your chance to entice clicks.

**Character limit:** 200 characters (truncation after 200)
**Best practice:** Keep under 150 characters for optimal display

### twitter:image (Recommended)

The URL of the image to display in the Card. Use absolute URLs with \`https://\`.

**Requirements:**
- **Format:** JPG, PNG, GIF, WebP
- **Size:** Minimum 144×144 pixels, maximum 4096×4096 pixels
- **Recommended:** 1200×628 pixels for summary_large_image
- **File size:** Maximum 5MB for photos, 15MB for GIFs
- **URL:** Must be absolute (https://example.com/image.jpg)

### twitter:creator (Optional)

The Twitter username of the content author. Useful for attributing articles to specific writers.

**Format:** \`@username\` (with @ symbol)

### twitter:image:alt (Optional but Recommended)

Alt text describing the image for accessibility. Screen readers use this to describe the image to visually impaired users.

**Character limit:** 420 characters
**Best practice:** Describe the image content clearly and concisely`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Complete Twitter Card implementation -->
<head>
  <!-- Required: Card type -->
  <meta name="twitter:card" content="summary_large_image" />
  
  <!-- Site/brand username -->
  <meta name="twitter:site" content="@seoindicator" />
  
  <!-- Author username (for articles) -->
  <meta name="twitter:creator" content="@authorname" />
  
  <!-- Title (max 70 characters) -->
  <meta name="twitter:title" content="Complete Guide to Twitter Cards for SEO" />
  
  <!-- Description (max 200 characters) -->
  <meta name="twitter:description" content="Learn how to implement Twitter Cards to create rich previews that drive clicks and engagement on X (Twitter)." />
  
  <!-- Image (1200×628px recommended) -->
  <meta name="twitter:image" content="https://example.com/images/twitter-cards-guide.jpg" />
  
  <!-- Image alt text (accessibility) -->
  <meta name="twitter:image:alt" content="Twitter Cards Guide cover showing card preview examples" />
</head>`,
            description: 'Complete Twitter Card implementation with all required and recommended tags',
          },
          {
            language: 'html',
            code: `<!-- Combined Twitter Card + Open Graph implementation -->
<head>
  <!-- Twitter Card tags -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:site" content="@yourbrand" />
  <meta name="twitter:title" content="Your Page Title" />
  <meta name="twitter:description" content="Your page description." />
  <meta name="twitter:image" content="https://example.com/image.jpg" />
  
  <!-- Open Graph tags (for Facebook, LinkedIn, etc.) -->
  <meta property="og:title" content="Your Page Title" />
  <meta property="og:description" content="Your page description." />
  <meta property="og:image" content="https://example.com/image.jpg" />
  <meta property="og:url" content="https://example.com/page/" />
  <meta property="og:type" content="article" />
</head>`,
            description: 'Best practice: Combine Twitter Card and Open Graph tags for cross-platform support',
          },
        ],
      },
      {
        title: 'Image Best Practices for Twitter',
        content: `Images are the most impactful element of Twitter Cards. A well-chosen image can dramatically increase click-through rates and engagement. Follow these best practices to optimize your Card images.

### Recommended Image Dimensions

For \`summary_large_image\` cards (recommended for most content):

- **Recommended size:** 1200×628 pixels (1.91:1 aspect ratio)
- **Minimum size:** 144×144 pixels
- **Maximum size:** 4096×4096 pixels
- **Display width:** Up to 506 pixels on desktop

For \`summary\` cards (small thumbnail):

- **Recommended size:** 120×120 pixels minimum
- **Display size:** Approximately 120×120 pixels

### File Format and Size

- **Image format:** JPG, PNG, GIF, or WebP
- **Maximum file size:** 5MB for photos, 15MB for GIFs
- **Recommendation:** Compress images to under 1MB for faster loading

### Image Content Guidelines

**Do:**
- Use high-quality, professional images
- Include clear, readable text if relevant
- Use brand colors for recognition
- Feature faces or people for emotional connection
- Ensure good contrast and visibility

**Avoid:**
- Pixelated or blurry images
- Too much text (Twitter may flag as low quality)
- Misleading images that don't match content
- Images with logos only (boring and easily skipped)

### Aspect Ratio Considerations

Twitter crops images to fit the Card format. The 1.91:1 aspect ratio (1200×628) ensures your image displays without cropping on both desktop and mobile.

**Important:** Keep key visual elements centered. Twitter may crop edges differently on various devices.

### Testing Your Images

Always test how your images appear:
1. Use Twitter Card Validator (cards-dev.twitter.com/validator)
2. Check both desktop and mobile previews
3. Verify the image loads correctly
4. Ensure text is readable at Card size`,
        codeExamples: [
          {
            language: 'html',
            code: `<!-- Image with dimensions specified -->
<meta name="twitter:image" content="https://example.com/images/hero-1200x628.jpg" />

<!-- Image alt text for accessibility -->
<meta name="twitter:image:alt" content="Guide cover showing SEO tips infographic" />`,
            description: 'Twitter Card image tag with alt text for accessibility',
          },
          {
            language: 'text',
            code: `# Twitter Card Image Specifications

## Large Image Card (Recommended)
- Size: 1200×628 pixels (1.91:1 aspect ratio)
- Format: JPG, PNG, WebP
- Max file size: 5MB (aim for <1MB)

## Small Thumbnail Card
- Size: Minimum 120×120 pixels
- Display: Square thumbnail on right side

## Player Card
- Thumbnail: 1200×628 pixels recommended
- Video player dimensions: Set via twitter:player:width/height

## Common Issues
❌ Image not loading → Check HTTPS and public accessibility
❌ Wrong aspect ratio → Crop to 1.91:1 before uploading
❌ Blurry preview → Use higher resolution source image`,
            description: 'Quick reference for Twitter Card image specifications',
          },
        ],
      },
      {
        title: 'Testing with Social Media Preview Tool',
        content: `Always test your Twitter Cards before sharing links publicly. Testing ensures your tags are correctly implemented and your previews look great.

### Twitter Card Validator

Twitter's official Card Validator shows exactly how your Card will appear:

**URL:** cards-dev.twitter.com/validator

**What it does:**
- Displays a preview of your Card
- Shows detected meta tag values
- Reports errors and warnings
- Refreshes Twitter's cache for your URL

**How to use:**
1. Enter your page URL
2. Click "Preview card"
3. Review the preview and any errors
4. Fix issues and re-test

### Social Media Preview Tool (SEOIndicator)

Use our integrated **Social Media Preview** tool to test how your page appears across multiple platforms at once:

- Twitter/X preview
- Facebook preview
- LinkedIn preview
- WhatsApp and other platforms

**Benefits:**
- One tool for all platforms
- Side-by-side comparison
- Cache refresh for all platforms

### Common Issues and Fixes

**Card not showing:**
- Verify \`twitter:card\` tag is present
- Check for HTML errors (unclosed tags, typos)
- Ensure tags are in the \`<head>\` section

**Image not loading:**
- Use absolute URLs with \`https://\`
- Verify image is publicly accessible (not behind login)
- Check file size is under 5MB
- Ensure image meets minimum dimensions (144×144px)

**Old preview showing:**
- Use Card Validator to refresh cache
- Twitter caches Cards for extended periods
- Add a query parameter to test changes: \`?v=2\`

**"No card found" error:**
- Verify meta tags use \`name\` attribute (not \`property\`)
- Check for JavaScript rendering issues (Twitter may not execute JS)
- Ensure server returns proper HTTP headers

### Best Practice: Test Every New Page

Make Card testing part of your publishing workflow:
1. Implement tags before publishing
2. Test with Card Validator
3. Check image quality and cropping
4. Verify on mobile devices
5. Fix any errors before promoting the link`,
        codeExamples: [
          {
            language: 'text',
            code: `# Twitter Card Testing Checklist

## Before Publishing
☐ Add all required meta tags
☐ Use large image card type for visual content
☐ Verify image is 1200×628 pixels
☐ Include twitter:image:alt for accessibility
☐ Test with Card Validator

## Testing URLs
- Twitter Card Validator: https://cards-dev.twitter.com/validator
- SEOIndicator Social Media Preview: /tools/social-media-preview

## Common Fixes
Issue: Image not found
Fix: Use absolute https:// URL, check public access

Issue: Old image cached
Fix: Re-run Card Validator to refresh cache

Issue: Card type wrong
Fix: Check the card type value matches your content`,
            description: 'Checklist for testing Twitter Cards before publishing',
          },
        ],
      },
    ],
    checklist: [
      { text: 'Include twitter:card tag with appropriate type (summary_large_image recommended)', priority: 'critical' },
      { text: 'Add twitter:site with @username for brand attribution', priority: 'critical' },
      { text: 'Use images at 1200×628 pixels for summary_large_image cards', priority: 'critical' },
      { text: 'Keep twitter:title under 60 characters for full visibility', priority: 'important' },
      { text: 'Keep twitter:description under 150 characters for optimal display', priority: 'important' },
      { text: 'Test Cards with Twitter Card Validator before publishing', priority: 'important' },
      { text: 'Add twitter:image:alt for accessibility', priority: 'important' },
      { text: 'Use absolute https:// URLs for all images', priority: 'important' },
      { text: 'Implement both Twitter Card and Open Graph tags for cross-platform support', priority: 'recommended' },
    ],
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
