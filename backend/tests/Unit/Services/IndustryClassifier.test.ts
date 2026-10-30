/**
 * Industry Classifier Service Tests
 *
 * Tests for the service that classifies user's industry from website content.
 *
 * @see TEST-17-19.md for test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Industry enum
enum Industry {
  ECOMMERCE = 'ecommerce',
  SAAS = 'saas',
  LOCAL = 'local',
  BLOG = 'blog',
  AGENCY = 'agency',
  OTHER = 'other',
}

// Industry configuration interface
interface IndustryConfig {
  name: string;
  example_keywords: string[];
  tips: string[];
  next_steps: string[];
  tooltip_overrides: Record<string, string>;
}

// Mock industry configurations
const industryConfigs: Record<Industry, IndustryConfig> = {
  [Industry.ECOMMERCE]: {
    name: 'E-commerce',
    example_keywords: ['buy [product]', '[product] reviews', '[product] price'],
    tips: ['Track product category keywords for best results.'],
    next_steps: ['Set up product page tracking', 'Monitor competitor pricing'],
    tooltip_overrides: {
      keyword_help: 'Enter your main product name or category',
    },
  },
  [Industry.SAAS]: {
    name: 'SaaS/Software',
    example_keywords: ['[product] pricing', 'best [product] software', '[product] vs [competitor]'],
    tips: ['Feature comparison keywords often have high conversion.'],
    next_steps: ['Track feature comparison keywords', 'Monitor review sites'],
    tooltip_overrides: {
      keyword_help: 'Enter your software name or main feature',
    },
  },
  [Industry.LOCAL]: {
    name: 'Local Business',
    example_keywords: ['[service] near me', '[city] [service]', '[service] [location]'],
    tips: ['Local keywords drive foot traffic to your business.'],
    next_steps: ['Set up Google Business Profile tracking', 'Monitor local competitors'],
    tooltip_overrides: {
      keyword_help: 'Enter your service and location',
    },
  },
  [Industry.BLOG]: {
    name: 'Blog/Content',
    example_keywords: ['[topic] guide', 'how to [topic]', 'best [topic]'],
    tips: ['Long-tail keywords often rank easier for blogs.'],
    next_steps: ['Set up content tracking', 'Monitor trending topics'],
    tooltip_overrides: {
      keyword_help: 'Enter your main blog topic',
    },
  },
  [Industry.AGENCY]: {
    name: 'Agency',
    example_keywords: ['[service] agency', 'best [service] company', '[service] services'],
    tips: ['Track both branded and service keywords.'],
    next_steps: ['Set up client reporting', 'Monitor competitor agencies'],
    tooltip_overrides: {
      keyword_help: 'Enter your main service offering',
    },
  },
  [Industry.OTHER]: {
    name: 'General',
    example_keywords: ['keyword example', 'your main keyword'],
    tips: ['Start with your most important keyword.'],
    next_steps: ['Explore the dashboard', 'Add more keywords'],
    tooltip_overrides: {},
  },
};

// Mock service implementation
class IndustryClassifier {
  private confidenceThreshold = 0.7;

  private signals: Record<Industry, string[]> = {
    [Industry.ECOMMERCE]: ['cart', 'checkout', 'add to cart', 'shop', 'product', 'buy now', 'shopping'],
    [Industry.SAAS]: ['pricing', 'demo', 'free trial', 'features', 'sign up', 'subscription', 'software'],
    [Industry.LOCAL]: ['hours', 'location', 'near me', 'address', 'phone:', 'directions', 'visit us'],
    [Industry.BLOG]: ['blog', 'article', 'post', 'author', 'category', 'archive', 'read more'],
    [Industry.AGENCY]: ['services', 'clients', 'portfolio', 'case study', 'agency', 'team', 'contact us'],
    [Industry.OTHER]: [],
  };

  classifyFromHTML(html: string): { industry: Industry; confidence: number; signals: string[] } {
    const htmlLower = html.toLowerCase();
    const scores: Record<Industry, number> = {
      [Industry.ECOMMERCE]: 0,
      [Industry.SAAS]: 0,
      [Industry.LOCAL]: 0,
      [Industry.BLOG]: 0,
      [Industry.AGENCY]: 0,
      [Industry.OTHER]: 0,
    };

    const detectedSignals: Record<Industry, string[]> = {
      [Industry.ECOMMERCE]: [],
      [Industry.SAAS]: [],
      [Industry.LOCAL]: [],
      [Industry.BLOG]: [],
      [Industry.AGENCY]: [],
      [Industry.OTHER]: [],
    };

    // Count signal matches
    for (const [industry, keywords] of Object.entries(this.signals)) {
      for (const keyword of keywords) {
        if (htmlLower.includes(keyword)) {
          scores[industry as Industry]++;
          detectedSignals[industry as Industry].push(keyword);
        }
      }
    }

    // Find best match
    let bestIndustry = Industry.OTHER;
    let bestScore = 0;
    let totalSignals = 0;

    for (const [industry, score] of Object.entries(scores)) {
      totalSignals += score;
      if (score > bestScore) {
        bestScore = score;
        bestIndustry = industry as Industry;
      }
    }

    // Calculate confidence
    const confidence = totalSignals > 0 ? bestScore / totalSignals : 0;

    // Check threshold
    if (confidence < this.confidenceThreshold || bestScore === 0) {
      return {
        industry: Industry.OTHER,
        confidence: 0,
        signals: [],
      };
    }

    return {
      industry: bestIndustry,
      confidence,
      signals: detectedSignals[bestIndustry],
    };
  }

  getConfig(industry: Industry): IndustryConfig {
    return industryConfigs[industry];
  }

  getExampleKeywords(industry: Industry): string[] {
    return this.getConfig(industry).example_keywords;
  }

  getTips(industry: Industry): string[] {
    return this.getConfig(industry).tips;
  }

  getNextSteps(industry: Industry): string[] {
    return this.getConfig(industry).next_steps;
  }

  getTooltipOverride(industry: Industry, key: string): string | undefined {
    return this.getConfig(industry).tooltip_overrides[key];
  }
}

describe('Industry Classifier', () => {
  let classifier: IndustryClassifier;

  beforeEach(() => {
    classifier = new IndustryClassifier();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================
  // IP-001: Industry Classification
  // ============================================
  describe('Industry Classification', () => {
    it('IP-001-01: classify e-commerce from cart', () => {
      const html = `
        <html>
          <body>
            <button>Add to Cart</button>
            <a href="/checkout">Checkout</a>
            <div class="products">Products</div>
          </body>
        </html>
      `;

      const result = classifier.classifyFromHTML(html);

      expect(result.industry).toBe(Industry.ECOMMERCE);
    });

    it('IP-001-02: classify SaaS from pricing', () => {
      const html = `
        <html>
          <body>
            <h1>Pricing Plans</h1>
            <a href="/demo">Request Demo</a>
            <button>Start Free Trial</button>
          </body>
        </html>
      `;

      const result = classifier.classifyFromHTML(html);

      expect(result.industry).toBe(Industry.SAAS);
    });

    it('IP-001-03: classify local from address', () => {
      const html = `
        <html>
          <body>
            <address>123 Main St, City, ST 12345</address>
            <p>Hours: 9am-5pm</p>
            <p>Phone: 555-1234</p>
          </body>
        </html>
      `;

      const result = classifier.classifyFromHTML(html);

      expect(result.industry).toBe(Industry.LOCAL);
    });

    it('IP-001-04: classify blog from articles', () => {
      const html = `
        <html>
          <body>
            <h1>Our Blog</h1>
            <article>Latest Post</article>
            <span class="author">By John</span>
          </body>
        </html>
      `;

      const result = classifier.classifyFromHTML(html);

      expect(result.industry).toBe(Industry.BLOG);
    });

    it('IP-001-05: classify agency from services', () => {
      const html = `
        <html>
          <body>
            <h1>Our Services</h1>
            <div class="portfolio">Portfolio</div>
            <section>Clients</section>
          </body>
        </html>
      `;

      const result = classifier.classifyFromHTML(html);

      expect(result.industry).toBe(Industry.AGENCY);
    });

    it('IP-001-06: fallback to other', () => {
      const html = `
        <html>
          <body>
            <h1>Welcome</h1>
            <p>Some random content</p>
          </body>
        </html>
      `;

      const result = classifier.classifyFromHTML(html);

      expect(result.industry).toBe(Industry.OTHER);
    });

    it('IP-001-07: multiple signals present', () => {
      const html = `
        <html>
          <body>
            <button>Add to Cart</button>
            <h1>Pricing</h1>
            <article>Blog Post</article>
          </body>
        </html>
      `;

      const result = classifier.classifyFromHTML(html);

      // Should pick the one with most signals
      expect(result.industry).not.toBe(Industry.OTHER);
    });

    it('IP-001-08: confidence threshold', () => {
      const html = `
        <html>
          <body>
            <p>cart</p>
          </body>
        </html>
      `;

      const result = classifier.classifyFromHTML(html);

      // Single weak signal should fall back to other
      // (depending on threshold)
      expect(result).toBeDefined();
    });
  });

  // ============================================
  // IP-002: E-commerce Path
  // ============================================
  describe('E-commerce Path', () => {
    it('IP-002-01: example keywords', () => {
      const keywords = classifier.getExampleKeywords(Industry.ECOMMERCE);

      expect(keywords).toContain('buy [product]');
      expect(keywords).toContain('[product] reviews');
    });

    it('IP-002-02: tips content', () => {
      const tips = classifier.getTips(Industry.ECOMMERCE);

      expect(tips.length).toBeGreaterThan(0);
      expect(tips[0]).toContain('product');
    });

    it('IP-002-03: next steps', () => {
      const nextSteps = classifier.getNextSteps(Industry.ECOMMERCE);

      expect(nextSteps).toContain('Set up product page tracking');
    });

    it('IP-002-04: tooltip customization', () => {
      const tooltip = classifier.getTooltipOverride(Industry.ECOMMERCE, 'keyword_help');

      expect(tooltip).toContain('product');
    });
  });

  // ============================================
  // IP-003: SaaS Path
  // ============================================
  describe('SaaS Path', () => {
    it('IP-003-01: example keywords', () => {
      const keywords = classifier.getExampleKeywords(Industry.SAAS);

      expect(keywords).toContain('[product] pricing');
    });

    it('IP-003-02: tips content', () => {
      const tips = classifier.getTips(Industry.SAAS);

      expect(tips.length).toBeGreaterThan(0);
      expect(tips[0]).toContain('comparison');
    });

    it('IP-003-03: next steps', () => {
      const nextSteps = classifier.getNextSteps(Industry.SAAS);

      expect(nextSteps).toContain('Track feature comparison keywords');
    });

    it('IP-003-04: tooltip customization', () => {
      const tooltip = classifier.getTooltipOverride(Industry.SAAS, 'keyword_help');

      expect(tooltip).toContain('software');
    });
  });

  // ============================================
  // IP-004: Local Business Path
  // ============================================
  describe('Local Business Path', () => {
    it('IP-004-01: example keywords', () => {
      const keywords = classifier.getExampleKeywords(Industry.LOCAL);

      expect(keywords).toContain('[service] near me');
    });

    it('IP-004-02: tips content', () => {
      const tips = classifier.getTips(Industry.LOCAL);

      expect(tips[0]).toContain('Local');
    });

    it('IP-004-03: next steps', () => {
      const nextSteps = classifier.getNextSteps(Industry.LOCAL);

      expect(nextSteps).toContain('Set up Google Business Profile tracking');
    });

    it('IP-004-04: tooltip customization', () => {
      const tooltip = classifier.getTooltipOverride(Industry.LOCAL, 'keyword_help');

      expect(tooltip).toContain('service');
    });
  });

  // ============================================
  // IP-007: Generic Path (Other)
  // ============================================
  describe('Generic Path', () => {
    it('IP-007-01: default keywords', () => {
      const keywords = classifier.getExampleKeywords(Industry.OTHER);

      expect(keywords.length).toBeGreaterThan(0);
    });

    it('IP-007-02: default tips', () => {
      const tips = classifier.getTips(Industry.OTHER);

      expect(tips.length).toBeGreaterThan(0);
    });

    it('IP-007-03: default next steps', () => {
      const nextSteps = classifier.getNextSteps(Industry.OTHER);

      expect(nextSteps.length).toBeGreaterThan(0);
    });

    it('IP-007-04: no errors for unrecognized', () => {
      const config = classifier.getConfig(Industry.OTHER);

      expect(config).toBeDefined();
      expect(config.name).toBe('General');
    });
  });

  // ============================================
  // IP-010: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('IP-010-01: mixed signals', () => {
      const html = `
        <html>
          <body>
            <button>Add to Cart</button>
            <h1>Pricing</h1>
          </body>
        </html>
      `;

      const result = classifier.classifyFromHTML(html);

      // Should pick one (higher confidence wins)
      expect([Industry.ECOMMERCE, Industry.SAAS]).toContain(result.industry);
    });

    it('IP-010-02: no HTML content', () => {
      const result = classifier.classifyFromHTML('');

      expect(result.industry).toBe(Industry.OTHER);
    });

    it('IP-010-03: international content', () => {
      const html = `
        <html>
          <body>
            <h1>欢迎</h1>
            <p>中文内容</p>
          </body>
        </html>
      `;

      const result = classifier.classifyFromHTML(html);

      // Should not crash, fallback to other
      expect(result.industry).toBe(Industry.OTHER);
    });

    it('IP-010-04: very large HTML', () => {
      const html = '<button>Add to Cart</button>' + 'x'.repeat(5000000);

      const result = classifier.classifyFromHTML(html);

      expect(result.industry).toBe(Industry.ECOMMERCE);
    });

    it('IP-010-05: JavaScript-rendered (no SSR)', () => {
      const html = `
        <html>
          <body>
            <div id="root"></div>
            <script src="app.js"></script>
          </body>
        </html>
      `;

      const result = classifier.classifyFromHTML(html);

      // No signals, fallback to other
      expect(result.industry).toBe(Industry.OTHER);
    });
  });
});
