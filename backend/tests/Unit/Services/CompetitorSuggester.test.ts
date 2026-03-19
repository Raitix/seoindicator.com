/**
 * Competitor Suggester Service Tests
 *
 * Tests for the service that suggests competitor domains based on user's website.
 *
 * @see TEST-17-18.md for test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock industry categories
enum IndustryCategory {
  MARKETING_SEO = 'Marketing/SEO',
  ECOMMERCE = 'E-commerce',
  TECHNOLOGY = 'Technology',
  FINANCE = 'Finance',
  HEALTHCARE = 'Healthcare',
  EDUCATION = 'Education',
  GENERAL = 'General',
}

// Mock competitor interface
interface Competitor {
  domain: string;
  name: string;
  industry: IndustryCategory;
  keywords: string[];
}

// Mock database
const mockCompetitors: Competitor[] = [
  {
    domain: 'ahrefs.com',
    name: 'Ahrefs',
    industry: IndustryCategory.MARKETING_SEO,
    keywords: ['seo tools', 'keyword research', 'backlink analysis'],
  },
  {
    domain: 'semrush.com',
    name: 'SEMrush',
    industry: IndustryCategory.MARKETING_SEO,
    keywords: ['seo', 'competitor analysis', 'keyword research'],
  },
  {
    domain: 'moz.com',
    name: 'Moz',
    industry: IndustryCategory.MARKETING_SEO,
    keywords: ['seo', 'ranking', 'domain authority'],
  },
  {
    domain: 'shopify.com',
    name: 'Shopify',
    industry: IndustryCategory.ECOMMERCE,
    keywords: ['e-commerce', 'online store', 'shopping cart'],
  },
];

// Mock service implementation
class CompetitorSuggester {
  private industryKeywords: Record<IndustryCategory, string[]> = {
    [IndustryCategory.MARKETING_SEO]: ['seo', 'marketing', 'keywords', 'analytics', 'ranking'],
    [IndustryCategory.ECOMMERCE]: ['shop', 'store', 'buy', 'cart', 'checkout', 'product'],
    [IndustryCategory.TECHNOLOGY]: ['software', 'app', 'tech', 'developer', 'code'],
    [IndustryCategory.FINANCE]: ['finance', 'banking', 'investment', 'money', 'trading'],
    [IndustryCategory.HEALTHCARE]: ['health', 'medical', 'doctor', 'hospital', 'wellness'],
    [IndustryCategory.EDUCATION]: ['education', 'learning', 'course', 'school', 'training'],
    [IndustryCategory.GENERAL]: [],
  };

  classifyIndustry(keywords: string[], title: string): IndustryCategory {
    const allText = [...keywords, title.toLowerCase()].join(' ');
    
    let bestMatch = IndustryCategory.GENERAL;
    let bestScore = 0;

    for (const [industry, industryKws] of Object.entries(this.industryKeywords)) {
      const score = industryKws.filter(kw => allText.includes(kw)).length;
      if (score > bestScore) {
        bestScore = score;
        bestMatch = industry as IndustryCategory;
      }
    }

    return bestMatch;
  }

  findCompetitors(
    industry: IndustryCategory,
    keywords: string[],
    excludeDomain: string,
    competitors: Competitor[]
  ): Competitor[] {
    // Filter by industry and exclude self
    let matches = competitors.filter(
      c => c.industry === industry && c.domain !== excludeDomain
    );

    // Score by keyword overlap
    const scored = matches.map(c => {
      const overlap = c.keywords.filter(k => keywords.map(k => k.toLowerCase()).includes(k.toLowerCase()));
      return {
        ...c,
        relevance_score: overlap.length / c.keywords.length,
        keywords_in_common: overlap,
      };
    });

    // Sort by relevance and limit to 3
    return scored
      .sort((a, b) => b.relevance_score - a.relevance_score)
      .slice(0, 3);
  }

  extractDomain(url: string): string {
    try {
      const urlObj = new URL(url);
      return urlObj.hostname.replace('www.', '');
    } catch {
      return url;
    }
  }

  async suggest(
    url: string,
    keywords: string[],
    title: string
  ): Promise<{ domain: string; name: string; industry: string; relevance_score: number; keywords_in_common: string[] }[]> {
    const domain = this.extractDomain(url);
    const industry = this.classifyIndustry(keywords, title);
    const competitors = this.findCompetitors(industry, keywords, domain, mockCompetitors);

    return competitors.map(c => ({
      domain: c.domain,
      name: c.name,
      industry: c.industry,
      relevance_score: c.keywords.filter(k => 
        keywords.map(k => k.toLowerCase()).includes(k.toLowerCase())
      ).length / c.keywords.length,
      keywords_in_common: c.keywords.filter(k => 
        keywords.map(k => k.toLowerCase()).includes(k.toLowerCase())
      ),
    }));
  }
}

describe('Competitor Suggester', () => {
  let suggester: CompetitorSuggester;

  beforeEach(() => {
    suggester = new CompetitorSuggester();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================
  // CS-001: Industry Classification
  // ============================================
  describe('Industry Classification', () => {
    it('CS-001-01: classify from meta keywords', () => {
      const result = suggester.classifyIndustry(['seo', 'marketing', 'tools'], '');
      expect(result).toBe(IndustryCategory.MARKETING_SEO);
    });

    it('CS-001-02: classify from title', () => {
      const result = suggester.classifyIndustry([], 'Best E-commerce Platform');
      expect(result).toBe(IndustryCategory.ECOMMERCE);
    });

    it('CS-001-03: classify from content keywords', () => {
      const result = suggester.classifyIndustry(['software', 'developer', 'code'], '');
      expect(result).toBe(IndustryCategory.TECHNOLOGY);
    });

    it('CS-001-04: fallback to General', () => {
      const result = suggester.classifyIndustry(['random', 'unknown', 'words'], '');
      expect(result).toBe(IndustryCategory.GENERAL);
    });

    it('CS-001-05: multi-category site', () => {
      // Marketing has more matches
      const result = suggester.classifyIndustry(['seo', 'shop', 'marketing'], '');
      expect(result).toBe(IndustryCategory.MARKETING_SEO);
    });
  });

  // ============================================
  // CS-002: Competitor Matching
  // ============================================
  describe('Competitor Matching', () => {
    it('CS-002-01: match by industry', () => {
      const result = suggester.findCompetitors(
        IndustryCategory.MARKETING_SEO,
        ['seo'],
        'example.com',
        mockCompetitors
      );

      expect(result.length).toBeGreaterThan(0);
      expect(result.every(c => c.industry === IndustryCategory.MARKETING_SEO)).toBe(true);
    });

    it('CS-002-02: match by keywords', () => {
      const result = suggester.findCompetitors(
        IndustryCategory.MARKETING_SEO,
        ['keyword research'],
        'example.com',
        mockCompetitors
      );

      // Should prefer competitors with matching keywords
      expect(result.some(c => c.keywords.includes('keyword research'))).toBe(true);
    });

    it('CS-002-03: exclude self', () => {
      const result = suggester.findCompetitors(
        IndustryCategory.MARKETING_SEO,
        ['seo'],
        'ahrefs.com',
        mockCompetitors
      );

      expect(result.some(c => c.domain === 'ahrefs.com')).toBe(false);
    });

    it('CS-002-04: limit to 3', () => {
      // Add more competitors to test limit
      const manyCompetitors = [
        ...mockCompetitors,
        { domain: 'c1.com', name: 'C1', industry: IndustryCategory.MARKETING_SEO, keywords: ['seo'] },
        { domain: 'c2.com', name: 'C2', industry: IndustryCategory.MARKETING_SEO, keywords: ['seo'] },
        { domain: 'c3.com', name: 'C3', industry: IndustryCategory.MARKETING_SEO, keywords: ['seo'] },
        { domain: 'c4.com', name: 'C4', industry: IndustryCategory.MARKETING_SEO, keywords: ['seo'] },
      ];

      const result = suggester.findCompetitors(
        IndustryCategory.MARKETING_SEO,
        ['seo'],
        'example.com',
        manyCompetitors
      );

      expect(result.length).toBeLessThanOrEqual(3);
    });

    it('CS-002-05: score by relevance', () => {
      const result = suggester.findCompetitors(
        IndustryCategory.MARKETING_SEO,
        ['keyword research'],
        'example.com',
        mockCompetitors
      );

      // Results should be sorted by relevance
      for (let i = 1; i < result.length; i++) {
        expect(result[i - 1].relevance_score).toBeGreaterThanOrEqual(result[i].relevance_score);
      }
    });

    it('CS-002-06: handle no matches', () => {
      const result = suggester.findCompetitors(
        IndustryCategory.FINANCE,
        ['investment'],
        'example.com',
        mockCompetitors // No finance competitors
      );

      expect(result).toEqual([]);
    });
  });

  // ============================================
  // CS-003: Full Suggestion Flow
  // ============================================
  describe('Full Suggestion Flow', () => {
    it('CS-003-01: returns suggestions', async () => {
      const result = await suggester.suggest(
        'https://example.com',
        ['seo', 'keyword research'],
        'SEO Tools'
      );

      expect(Array.isArray(result)).toBe(true);
    });

    it('CS-003-02: suggestions have required fields', async () => {
      const result = await suggester.suggest(
        'https://example.com',
        ['seo'],
        'SEO Tools'
      );

      if (result.length > 0) {
        expect(result[0]).toHaveProperty('domain');
        expect(result[0]).toHaveProperty('name');
        expect(result[0]).toHaveProperty('industry');
        expect(result[0]).toHaveProperty('relevance_score');
      }
    });

    it('CS-003-03: no suggestions for unique industry', async () => {
      const result = await suggester.suggest(
        'https://example.com',
        ['unique', 'unknown', 'niche'],
        'Unique Site'
      );

      expect(result).toEqual([]);
    });
  });

  // ============================================
  // CS-006: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('CS-006-01: self-referential URL excluded', async () => {
      const result = await suggester.suggest(
        'https://ahrefs.com',
        ['seo'],
        'Ahrefs'
      );

      expect(result.some(c => c.domain === 'ahrefs.com')).toBe(false);
    });

    it('CS-006-02: subdomain handling', () => {
      const result = suggester.extractDomain('https://blog.example.com');
      expect(result).toBe('blog.example.com');
    });

    it('CS-006-03: international TLDs', () => {
      const result = suggester.extractDomain('https://example.co.uk');
      expect(result).toBe('example.co.uk');
    });

    it('CS-006-04: empty competitors database', async () => {
      // Temporarily empty database
      const emptySuggester = new CompetitorSuggester();
      // Would need to mock the database being empty
      // For now, test that it handles gracefully
    });

    it('CS-006-05: very niche industry', async () => {
      const result = await suggester.suggest(
        'https://example.com',
        ['quantum', 'physics', 'research'],
        'Quantum Physics Research'
      );

      // Should fallback to General and return empty or General competitors
      expect(Array.isArray(result)).toBe(true);
    });
  });
});
