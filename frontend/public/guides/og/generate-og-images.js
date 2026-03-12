const guides = [
  { slug: 'meta-title', title: 'Meta Title Optimization', icon: '📝', category: 'on-page', subtitle: 'Learn how to craft compelling page titles that improve click-through rates and search rankings.' },
  { slug: 'meta-description', title: 'Meta Description Best Practices', icon: '📋', category: 'on-page', subtitle: 'Write descriptions that entice users to click and provide search engines with context.' },
  { slug: 'h1-headings', title: 'H1 Heading Structure', icon: '🔤', category: 'on-page', subtitle: 'Learn how to use H1 tags effectively for SEO, accessibility, and better user experience.' },
  { slug: 'heading-hierarchy', title: 'Heading Hierarchy (H1-H6)', icon: '📑', category: 'on-page', subtitle: 'Create a logical content structure using proper heading order for better UX, accessibility, and SEO.' },
  { slug: 'image-alt-text', title: 'Image Alt Text Optimization', icon: '🖼️', category: 'on-page', subtitle: 'Write descriptive alt text that improves accessibility and image search visibility.' },
  { slug: 'image-optimization', title: 'Image Optimization for Web', icon: '⚡', category: 'on-page', subtitle: 'Optimize images for faster loading without sacrificing quality.' },
  { slug: 'internal-links', title: 'Internal Linking Strategy', icon: '🔗', category: 'on-page', subtitle: 'Build a strong site architecture through strategic internal linking.' },
  { slug: 'external-links', title: 'External Link Best Practices', icon: '🌐', category: 'on-page', subtitle: 'Use outbound links strategically to build authority and provide value.' },
  { slug: 'page-speed', title: 'Page Speed Optimization', icon: '🚀', category: 'technical', subtitle: 'Improve Core Web Vitals and page load times for better user experience.' },
  { slug: 'mobile-friendly', title: 'Mobile-Friendly Design', icon: '📱', category: 'technical', subtitle: 'Ensure your site works flawlessly on all mobile devices with responsive design.' },
  { slug: 'backlinks', title: 'Backlinks Guide', icon: '🔗', category: 'technical', subtitle: 'Learn how to build quality backlinks that boost your search rankings and domain authority.' },
  { slug: 'https-security', title: 'HTTPS & Security Headers', icon: '🔒', category: 'technical', subtitle: 'Secure your site with SSL/TLS encryption and implement security headers.' },
  { slug: 'structured-data', title: 'Structured Data (Schema.org)', icon: '📊', category: 'technical', subtitle: 'Implement Schema.org markup to unlock rich snippets and enhanced search visibility.' },
  { slug: 'canonical-urls', title: 'Canonical URL Implementation', icon: '🎯', category: 'technical', subtitle: 'Prevent duplicate content issues and consolidate ranking signals.' },
  { slug: 'robots-txt', title: 'Robots.txt Configuration', icon: '🤖', category: 'technical', subtitle: 'Control how search engines crawl your site with robots.txt directives.' },
  { slug: 'xml-sitemap', title: 'XML Sitemap Creation', icon: '🗺️', category: 'technical', subtitle: 'Help search engines discover and index all your important pages.' },
  { slug: 'open-graph', title: 'Open Graph Tags', icon: '👍', category: 'content', subtitle: 'Optimize how your content appears when shared on Facebook, LinkedIn, and other platforms.' },
  { slug: 'twitter-cards', title: 'Twitter Cards', icon: '🐦', category: 'content', subtitle: 'Create rich Twitter card previews that drive engagement and clicks.' }
];

const indexGuide = {
  slug: 'index',
  title: 'SEO Guides',
  icon: '📚',
  category: 'index',
  subtitle: 'Master SEO with our comprehensive guides covering on-page optimization, technical SEO, and content best practices.'
};

const categoryLabels = {
  'on-page': 'ON-PAGE SEO',
  'technical': 'TECHNICAL SEO',
  'content': 'CONTENT & SOCIAL',
  'index': 'COMPLETE GUIDE'
};

const categoryClasses = {
  'on-page': 'category-on-page',
  'technical': 'category-technical',
  'content': 'category-content',
  'index': 'category-on-page'
};

function generateHTML(guide) {
  return `<!DOCTYPE html>
<html>
<head>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      width: 1200px;
      height: 630px;
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
      font-family: 'Segoe UI', system-ui, sans-serif;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 60px;
      position: relative;
      overflow: hidden;
    }
    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: 
        radial-gradient(circle at 20% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(16, 185, 129, 0.08) 0%, transparent 50%);
      pointer-events: none;
    }
    .container {
      position: relative;
      z-index: 1;
      text-align: center;
      width: 100%;
    }
    .brand {
      font-size: 18px;
      color: #10b981;
      letter-spacing: 4px;
      text-transform: uppercase;
      margin-bottom: 30px;
      font-weight: 600;
    }
    .icon {
      font-size: 80px;
      margin-bottom: 20px;
      filter: drop-shadow(0 0 30px rgba(16, 185, 129, 0.3));
    }
    h1 {
      font-size: 52px;
      color: white;
      font-weight: 700;
      line-height: 1.2;
      margin-bottom: 20px;
      text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
    }
    .subtitle {
      font-size: 22px;
      color: #94a3b8;
      max-width: 800px;
      line-height: 1.4;
    }
    .category-badge {
      display: inline-block;
      padding: 8px 20px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 25px;
    }
    .category-on-page { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
    .category-technical { background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
    .category-content { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
    .footer {
      position: absolute;
      bottom: 30px;
      left: 0;
      right: 0;
      text-align: center;
      color: #475569;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="brand">SEOIndicator</div>
    <div class="icon">${guide.icon}</div>
    <div class="category-badge ${categoryClasses[guide.category]}">${categoryLabels[guide.category]}</div>
    <h1>${guide.title}</h1>
    <p class="subtitle">${guide.subtitle}</p>
  </div>
  <div class="footer">seoindicator.com/guides</div>
</body>
</html>`;
}

module.exports = { guides, indexGuide, generateHTML };
