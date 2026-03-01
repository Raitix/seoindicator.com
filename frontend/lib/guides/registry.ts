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
