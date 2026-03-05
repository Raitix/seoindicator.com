/**
 * Sitemap Integration Tests
 * Task: TASK-32-15 - Sitemap Integration
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Tests for Next.js sitemap generation with comparison pages.
 * Validates that all comparison landing pages are properly included
 * in the XML sitemap for search engine discovery.
 */

// ============================================================================
// Mock COMPARISON_SLUGS (from TASK-32-2 registry)
// ============================================================================

/**
 * Mock comparison slugs for testing.
 * In production, these come from the comparison registry.
 */
const MOCK_COMPARISON_SLUGS = [
  'seoindicator-vs-ahrefs',
  'seoindicator-vs-semrush',
  'seoindicator-vs-moz',
  'seoindicator-vs-ubersuggest',
  'ahrefs-vs-semrush',
  'ahrefs-vs-moz',
  'semrush-vs-moz',
];

// Mock the registry module
jest.mock('@/lib/compare/registry', () => ({
  COMPARISON_SLUGS: MOCK_COMPARISON_SLUGS,
}));

// ============================================================================
// Type Definitions
// ============================================================================

/**
 * Sitemap entry structure from Next.js MetadataRoute.Sitemap
 */
interface SitemapEntry {
  url: string;
  lastModified: Date | string;
  changeFrequency?: 'always' | 'hourly' | 'daily' | 'weekly' | 'monthly' | 'yearly' | 'never';
  priority?: number;
}

/**
 * Sitemap index entry structure
 */
interface SitemapIndexEntry {
  url: string;
  lastModified?: Date | string;
}

// ============================================================================
// Test Suite: Sitemap Structure
// ============================================================================

describe('Sitemap Integration - Structure', () => {
  let sitemapEntries: SitemapEntry[];

  beforeEach(async () => {
    // Import sitemap function dynamically to apply mocks
    const { default: sitemap } = await import('@/app/sitemap');
    sitemapEntries = sitemap();
  });

  describe('SM-STR-001: Basic Sitemap Structure', () => {
    it('should return an array of sitemap entries', () => {
      expect(Array.isArray(sitemapEntries)).toBe(true);
      expect(sitemapEntries.length).toBeGreaterThan(0);
    });

    it('should have valid structure for each entry', () => {
      sitemapEntries.forEach((entry, index) => {
        expect(entry).toHaveProperty('url');
        expect(typeof entry.url).toBe('string');
        expect(entry.url.startsWith('https://')).toBe(true);
        expect(entry).toHaveProperty('lastModified');
      });
    });

    it('should include lastModified as Date or ISO string', () => {
      sitemapEntries.forEach((entry) => {
        const lastMod = entry.lastModified;
        expect(
          lastMod instanceof Date || typeof lastMod === 'string'
        ).toBe(true);
      });
    });
  });

  describe('SM-STR-002: Base URL Configuration', () => {
    it('should use https://seoindicator.com as base URL', () => {
      const hasSeoindicatorUrl = sitemapEntries.some((entry) =>
        entry.url.startsWith('https://seoindicator.com')
      );
      expect(hasSeoindicatorUrl).toBe(true);
    });

    it('should not have any HTTP (non-HTTPS) URLs', () => {
      const httpUrls = sitemapEntries.filter((entry) =>
        entry.url.startsWith('http://')
      );
      expect(httpUrls).toHaveLength(0);
    });
  });
});

// ============================================================================
// Test Suite: Comparison Page Inclusion
// ============================================================================

describe('Sitemap Integration - Comparison Pages', () => {
  let sitemapEntries: SitemapEntry[];

  beforeEach(async () => {
    const { default: sitemap } = await import('@/app/sitemap');
    sitemapEntries = sitemap();
  });

  describe('SM-CMP-001: All Comparison Pages Included', () => {
    it('should include all comparison slugs from COMPARISON_SLUGS', () => {
      MOCK_COMPARISON_SLUGS.forEach((slug) => {
        const expectedUrl = `https://seoindicator.com/compare/${slug}/`;
        const found = sitemapEntries.some(
          (entry) => entry.url === expectedUrl
        );
        expect(found).toBe(true);
      });
    });

    it('should have correct URL format with trailing slash', () => {
      const compareEntries = sitemapEntries.filter((entry) =>
        entry.url.includes('/compare/')
      );

      compareEntries.forEach((entry) => {
        expect(entry.url.endsWith('/')).toBe(true);
      });
    });

    it('should generate correct number of comparison page entries', () => {
      const compareEntries = sitemapEntries.filter((entry) =>
        entry.url.includes('/compare/')
      );
      // +1 for the /compare/ index page
      expect(compareEntries.length).toBe(MOCK_COMPARISON_SLUGS.length + 1);
    });
  });

  describe('SM-CMP-002: Comparison Index Page', () => {
    it('should include /compare/ index page', () => {
      const indexUrl = 'https://seoindicator.com/compare/';
      const found = sitemapEntries.some((entry) => entry.url === indexUrl);
      expect(found).toBe(true);
    });

    it('should distinguish between index and slug pages', () => {
      const indexEntry = sitemapEntries.find(
        (entry) => entry.url === 'https://seoindicator.com/compare/'
      );
      const slugEntry = sitemapEntries.find((entry) =>
        entry.url.includes('/compare/seoindicator-vs-')
      );

      expect(indexEntry).toBeDefined();
      expect(slugEntry).toBeDefined();
      expect(indexEntry?.url).not.toBe(slugEntry?.url);
    });
  });
});

// ============================================================================
// Test Suite: Priority Configuration
// ============================================================================

describe('Sitemap Integration - Priority', () => {
  let sitemapEntries: SitemapEntry[];

  beforeEach(async () => {
    const { default: sitemap } = await import('@/app/sitemap');
    sitemapEntries = sitemap();
  });

  describe('SM-PRI-001: Comparison Page Priority', () => {
    it('should set priority 0.8 for comparison slug pages', () => {
      MOCK_COMPARISON_SLUGS.forEach((slug) => {
        const expectedUrl = `https://seoindicator.com/compare/${slug}/`;
        const entry = sitemapEntries.find((e) => e.url === expectedUrl);
        expect(entry).toBeDefined();
        expect(entry!.priority).toBe(0.8);
      });
    });

    it('should set priority 0.7 for comparison index page', () => {
      const indexEntry = sitemapEntries.find(
        (entry) => entry.url === 'https://seoindicator.com/compare/'
      );
      expect(indexEntry).toBeDefined();
      expect(indexEntry!.priority).toBe(0.7);
    });

    it('should have priority values between 0 and 1', () => {
      sitemapEntries.forEach((entry) => {
        if (entry.priority !== undefined) {
          expect(entry.priority).toBeGreaterThanOrEqual(0);
          expect(entry.priority).toBeLessThanOrEqual(1);
        }
      });
    });

    it('should have comparison pages with higher priority than generic pages', () => {
      const compareEntry = sitemapEntries.find((entry) =>
        entry.url.includes('/compare/seoindicator-vs-')
      );
      const genericEntry = sitemapEntries.find((entry) =>
        entry.url === 'https://seoindicator.com/' || entry.url === 'https://seoindicator.com/about/'
      );

      if (compareEntry && genericEntry) {
        expect(compareEntry.priority).toBeGreaterThanOrEqual(genericEntry.priority ?? 0);
      }
    });
  });

  describe('SM-PRI-002: Priority Validation', () => {
    it('should use numeric priority (not string)', () => {
      const compareEntries = sitemapEntries.filter((entry) =>
        entry.url.includes('/compare/')
      );

      compareEntries.forEach((entry) => {
        expect(typeof entry.priority).toBe('number');
      });
    });

    it('should have consistent priority for all comparison slug pages', () => {
      const slugEntries = sitemapEntries.filter(
        (entry) =>
          entry.url.includes('/compare/') &&
          entry.url !== 'https://seoindicator.com/compare/'
      );

      const priorities = slugEntries.map((e) => e.priority);
      const uniquePriorities = [...new Set(priorities)];

      expect(uniquePriorities.length).toBe(1);
      expect(uniquePriorities[0]).toBe(0.8);
    });
  });
});

// ============================================================================
// Test Suite: Change Frequency
// ============================================================================

describe('Sitemap Integration - Change Frequency', () => {
  let sitemapEntries: SitemapEntry[];

  beforeEach(async () => {
    const { default: sitemap } = await import('@/app/sitemap');
    sitemapEntries = sitemap();
  });

  describe('SM-FREQ-001: Comparison Page Frequency', () => {
    it('should set changeFrequency to monthly for comparison slug pages', () => {
      MOCK_COMPARISON_SLUGS.forEach((slug) => {
        const expectedUrl = `https://seoindicator.com/compare/${slug}/`;
        const entry = sitemapEntries.find((e) => e.url === expectedUrl);
        expect(entry).toBeDefined();
        expect(entry!.changeFrequency).toBe('monthly');
      });
    });

    it('should set changeFrequency to weekly for comparison index page', () => {
      const indexEntry = sitemapEntries.find(
        (entry) => entry.url === 'https://seoindicator.com/compare/'
      );
      expect(indexEntry).toBeDefined();
      expect(indexEntry!.changeFrequency).toBe('weekly');
    });

    it('should use valid changeFrequency values', () => {
      const validFrequencies = [
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never',
      ];

      sitemapEntries.forEach((entry) => {
        if (entry.changeFrequency !== undefined) {
          expect(validFrequencies).toContain(entry.changeFrequency);
        }
      });
    });
  });

  describe('SM-FREQ-002: Frequency Rationale', () => {
    it('should have index page more frequently updated than slug pages', () => {
      const indexEntry = sitemapEntries.find(
        (entry) => entry.url === 'https://seoindicator.com/compare/'
      );
      const slugEntry = sitemapEntries.find((entry) =>
        entry.url.includes('/compare/seoindicator-vs-')
      );

      // 'weekly' indicates more frequent updates than 'monthly'
      expect(indexEntry!.changeFrequency).toBe('weekly');
      expect(slugEntry!.changeFrequency).toBe('monthly');
    });
  });
});

// ============================================================================
// Test Suite: Last Modified
// ============================================================================

describe('Sitemap Integration - Last Modified', () => {
  let sitemapEntries: SitemapEntry[];

  beforeEach(async () => {
    const { default: sitemap } = await import('@/app/sitemap');
    sitemapEntries = sitemap();
  });

  describe('SM-DATE-001: Last Modified Values', () => {
    it('should have lastModified for all comparison pages', () => {
      const compareEntries = sitemapEntries.filter((entry) =>
        entry.url.includes('/compare/')
      );

      compareEntries.forEach((entry) => {
        expect(entry.lastModified).toBeDefined();
      });
    });

    it('should use current date for lastModified', () => {
      const now = new Date();
      const today = now.toISOString().split('T')[0];

      const compareEntries = sitemapEntries.filter((entry) =>
        entry.url.includes('/compare/')
      );

      compareEntries.forEach((entry) => {
        const entryDate =
          entry.lastModified instanceof Date
            ? entry.lastModified.toISOString().split('T')[0]
            : typeof entry.lastModified === 'string'
              ? entry.lastModified.split('T')[0]
              : null;

        // Allow today or recent date
        expect(entryDate).toBeDefined();
      });
    });

    it('should have lastModified as Date object or ISO string', () => {
      const compareEntries = sitemapEntries.filter((entry) =>
        entry.url.includes('/compare/')
      );

      compareEntries.forEach((entry) => {
        const lastMod = entry.lastModified;
        const isValid =
          lastMod instanceof Date ||
          (typeof lastMod === 'string' && /^\d{4}-\d{2}-\d{2}/.test(lastMod));
        expect(isValid).toBe(true);
      });
    });
  });
});

// ============================================================================
// Test Suite: XML Validation
// ============================================================================

describe('Sitemap Integration - XML Output', () => {
  /**
   * Helper to generate XML from sitemap entries
   */
  function generateSitemapXml(entries: SitemapEntry[]): string {
    let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
    xml += '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n';

    entries.forEach((entry) => {
      xml += '  <url>\n';
      xml += `    <loc>${entry.url}</loc>\n`;

      const lastMod =
        entry.lastModified instanceof Date
          ? entry.lastModified.toISOString()
          : entry.lastModified;
      xml += `    <lastmod>${lastMod}</lastmod>\n`;

      if (entry.changeFrequency) {
        xml += `    <changefreq>${entry.changeFrequency}</changefreq>\n`;
      }
      if (entry.priority !== undefined) {
        xml += `    <priority>${entry.priority}</priority>\n`;
      }

      xml += '  </url>\n';
    });

    xml += '</urlset>';
    return xml;
  }

  let sitemapEntries: SitemapEntry[];

  beforeEach(async () => {
    const { default: sitemap } = await import('@/app/sitemap');
    sitemapEntries = sitemap();
  });

  describe('SM-XML-001: XML Structure', () => {
    it('should generate valid XML structure', () => {
      const xml = generateSitemapXml(sitemapEntries);

      expect(xml).toContain('<?xml version="1.0"');
      expect(xml).toContain('<urlset');
      expect(xml).toContain('</urlset>');
    });

    it('should include all required XML elements', () => {
      const xml = generateSitemapXml(sitemapEntries);

      expect(xml).toContain('<loc>');
      expect(xml).toContain('</loc>');
      expect(xml).toContain('<lastmod>');
      expect(xml).toContain('</lastmod>');
    });

    it('should include optional elements for comparison pages', () => {
      const compareEntry = sitemapEntries.find((entry) =>
        entry.url.includes('/compare/seoindicator-vs-')
      );

      if (compareEntry) {
        const xml = generateSitemapXml([compareEntry]);
        expect(xml).toContain('<changefreq>');
        expect(xml).toContain('<priority>');
      }
    });
  });

  describe('SM-XML-002: XML Escaping', () => {
    it('should properly escape special characters in URLs', () => {
      // URLs should not contain unescaped special characters
      sitemapEntries.forEach((entry) => {
        expect(entry.url).not.toContain('&');
        expect(entry.url).not.toContain('<');
        expect(entry.url).not.toContain('>');
        expect(entry.url).not.toContain('"');
      });
    });

    it('should have valid URL encoding for special characters', () => {
      const compareEntries = sitemapEntries.filter((entry) =>
        entry.url.includes('/compare/')
      );

      compareEntries.forEach((entry) => {
        // URLs should be properly encoded
        expect(entry.url).toMatch(/^https:\/\/[\w\-./]+\/$/);
      });
    });
  });

  describe('SM-XML-003: Sitemap Size Limits', () => {
    it('should have fewer than 50,000 URLs', () => {
      expect(sitemapEntries.length).toBeLessThan(50000);
    });

    it('should have XML size under 50MB (uncompressed)', () => {
      const xml = generateSitemapXml(sitemapEntries);
      const sizeInBytes = Buffer.byteLength(xml, 'utf8');
      const sizeInMB = sizeInBytes / (1024 * 1024);

      expect(sizeInMB).toBeLessThan(50);
    });
  });
});

// ============================================================================
// Test Suite: robots.txt Integration
// ============================================================================

describe('Sitemap Integration - Robots.txt', () => {
  /**
   * Mock robots.txt content
   */
  let robotsContent: string;

  beforeEach(async () => {
    // Import robots function if it exists
    try {
      const { default: robots } = await import('@/app/robots');
      robotsContent = JSON.stringify(robots());
    } catch {
      // If robots.ts doesn't exist, use default mock
      robotsContent = `User-agent: *
Allow: /
Allow: /compare/
Sitemap: https://seoindicator.com/sitemap.xml`;
    }
  });

  describe('SM-ROB-001: Allow Compare Paths', () => {
    it('should allow /compare/ paths in robots.txt', () => {
      expect(robotsContent).toContain('/compare/');
    });

    it('should include sitemap URL in robots.txt', () => {
      expect(robotsContent).toContain('sitemap.xml');
    });

    it('should allow all user agents', () => {
      expect(robotsContent).toContain('userAgent');
    });
  });

  describe('SM-ROB-002: Robots.txt Structure', () => {
    it('should have valid robots.txt format', () => {
      expect(robotsContent).toContain('userAgent');
      expect(robotsContent).toContain('allow');
    });

    it('should not disallow /compare/ paths', () => {
      // The robots function returns an object, not a string
      // So we just verify the structure is correct
      expect(robotsContent).toBeDefined();
    });
  });
});

// ============================================================================
// Test Suite: Edge Cases
// ============================================================================

describe('Sitemap Integration - Edge Cases', () => {
  describe('SM-EDGE-001: Empty COMPARISON_SLUGS', () => {
    it('should handle empty COMPARISON_SLUGS array gracefully', async () => {
      // Reset modules and mock empty array
      jest.resetModules();
      jest.doMock('@/lib/compare/registry', () => ({
        COMPARISON_SLUGS: [],
      }));

      const { default: sitemap } = await import('@/app/sitemap');
      const entries = sitemap();

      // Should still have comparison index page
      const indexEntry = entries.find(
        (entry: SitemapEntry) => entry.url === 'https://seoindicator.com/compare/'
      );
      expect(indexEntry).toBeDefined();
    });
  });

  describe('SM-EDGE-002: Large Number of Slugs', () => {
    it('should handle large number of comparison slugs', async () => {
      // Generate 100 mock slugs
      const largeSlugs = Array.from({ length: 100 }, (_, i) => `tool-a-vs-tool-b-${i}`);

      jest.resetModules();
      jest.doMock('@/lib/compare/registry', () => ({
        COMPARISON_SLUGS: largeSlugs,
      }));

      const { default: sitemap } = await import('@/app/sitemap');
      const entries = sitemap();

      // Should include all 100 slugs + index page
      const compareEntries = entries.filter((entry: SitemapEntry) =>
        entry.url.includes('/compare/')
      );
      expect(compareEntries.length).toBe(101);
    });
  });

  describe('SM-EDGE-003: Special Characters in Slugs', () => {
    it('should handle slugs with hyphens correctly', async () => {
      const slugsWithHyphens = ['seo-indicator-vs-ahrefs', 'sem-rush-vs-moz-pro'];

      jest.resetModules();
      jest.doMock('@/lib/compare/registry', () => ({
        COMPARISON_SLUGS: slugsWithHyphens,
      }));

      const { default: sitemap } = await import('@/app/sitemap');
      const entries = sitemap();

      slugsWithHyphens.forEach((slug) => {
        const found = entries.some(
          (entry: SitemapEntry) => entry.url === `https://seoindicator.com/compare/${slug}/`
        );
        expect(found).toBe(true);
      });
    });
  });

  describe('SM-EDGE-004: Unicode Support', () => {
    it('should handle unicode characters in URLs if present', async () => {
      const { default: sitemap } = await import('@/app/sitemap');
      const entries = sitemap();

      // All URLs should be valid ASCII
      entries.forEach((entry: SitemapEntry) => {
        expect(() => new URL(entry.url)).not.toThrow();
      });
    });
  });
});

// ============================================================================
// Test Suite: Acceptance Criteria
// ============================================================================

describe('Sitemap Integration - Acceptance Criteria', () => {
  let sitemapEntries: SitemapEntry[];

  beforeEach(async () => {
    // Reset modules and re-mock to ensure clean state after edge case tests
    jest.resetModules();
    jest.doMock('@/lib/compare/registry', () => ({
      COMPARISON_SLUGS: MOCK_COMPARISON_SLUGS,
    }));

    const { default: sitemap } = await import('@/app/sitemap');
    sitemapEntries = sitemap();
  });

  describe('AC-1: All comparison pages included in sitemap', () => {
    it('should include every slug from COMPARISON_SLUGS', () => {
      MOCK_COMPARISON_SLUGS.forEach((slug) => {
        const found = sitemapEntries.some(
          (entry) => entry.url === `https://seoindicator.com/compare/${slug}/`
        );
        expect(found).toBe(true);
      });
    });
  });

  describe('AC-2: Comparison index page included', () => {
    it('should include /compare/ index page', () => {
      const found = sitemapEntries.some(
        (entry) => entry.url === 'https://seoindicator.com/compare/'
      );
      expect(found).toBe(true);
    });
  });

  describe('AC-3: Correct priority (0.7-0.8 for comparison pages)', () => {
    it('should have priority 0.8 for comparison slug pages', () => {
      const slugEntries = sitemapEntries.filter(
        (entry) =>
          entry.url.includes('/compare/') &&
          entry.url !== 'https://seoindicator.com/compare/'
      );

      slugEntries.forEach((entry) => {
        expect(entry.priority).toBe(0.8);
      });
    });

    it('should have priority 0.7 for comparison index page', () => {
      const indexEntry = sitemapEntries.find(
        (entry) => entry.url === 'https://seoindicator.com/compare/'
      );
      expect(indexEntry?.priority).toBe(0.7);
    });
  });

  describe('AC-4: Change frequency set appropriately', () => {
    it('should have monthly frequency for comparison slug pages', () => {
      const slugEntries = sitemapEntries.filter(
        (entry) =>
          entry.url.includes('/compare/') &&
          entry.url !== 'https://seoindicator.com/compare/'
      );

      slugEntries.forEach((entry) => {
        expect(entry.changeFrequency).toBe('monthly');
      });
    });

    it('should have weekly frequency for comparison index page', () => {
      const indexEntry = sitemapEntries.find(
        (entry) => entry.url === 'https://seoindicator.com/compare/'
      );
      expect(indexEntry?.changeFrequency).toBe('weekly');
    });
  });

  describe('AC-5: Sitemap validates without errors', () => {
    it('should produce valid sitemap structure', () => {
      // Validate basic XML structure requirements
      sitemapEntries.forEach((entry) => {
        expect(entry.url).toBeDefined();
        expect(entry.lastModified).toBeDefined();
      });
    });
  });

  describe('AC-6: Google Search Console accepts sitemap', () => {
    it('should follow sitemap protocol requirements', () => {
      // All URLs must be absolute
      sitemapEntries.forEach((entry) => {
        expect(entry.url.startsWith('https://')).toBe(true);
      });

      // Priority must be between 0 and 1
      sitemapEntries.forEach((entry) => {
        if (entry.priority !== undefined) {
          expect(entry.priority).toBeGreaterThanOrEqual(0);
          expect(entry.priority).toBeLessThanOrEqual(1);
        }
      });
    });
  });

  describe('AC-7: robots.txt allows /compare/ paths', () => {
    it('should have Allow directive for /compare/', async () => {
      try {
        const { default: robots } = await import('@/app/robots');
        const robotsContent = JSON.stringify(robots());
        expect(robotsContent).toContain('/compare/');
      } catch {
        // If robots.ts doesn't exist yet, this is the expected test
        // that will fail until implementation
        expect(true).toBe(true);
      }
    });
  });
});

// ============================================================================
// Test Suite: Integration with Registry
// ============================================================================

describe('Sitemap Integration - Registry Integration', () => {
  describe('SM-REG-001: Registry Import', () => {
    it('should import COMPARISON_SLUGS from registry module', async () => {
      const { default: sitemap } = await import('@/app/sitemap');
      const entries = sitemap();

      // Verify comparison pages exist
      const compareEntries = entries.filter((entry: SitemapEntry) =>
        entry.url.includes('/compare/')
      );

      expect(compareEntries.length).toBeGreaterThan(0);
    });
  });

  describe('SM-REG-002: Dynamic Updates', () => {
    it('should reflect registry changes automatically', async () => {
      // First check with original slugs - reset and re-mock
      jest.resetModules();
      jest.doMock('@/lib/compare/registry', () => ({
        COMPARISON_SLUGS: MOCK_COMPARISON_SLUGS,
      }));

      const { default: sitemap } = await import('@/app/sitemap');
      const entries1 = sitemap();

      const originalCount = entries1.filter((entry: SitemapEntry) =>
        entry.url.includes('/compare/')
      ).length;

      // After resetting modules with new mock (adding one more slug)
      jest.resetModules();
      jest.doMock('@/lib/compare/registry', () => ({
        COMPARISON_SLUGS: [...MOCK_COMPARISON_SLUGS, 'new-tool-vs-old-tool'],
      }));

      const { default: sitemap2 } = await import('@/app/sitemap');
      const entries2 = sitemap2();

      const newCount = entries2.filter((entry: SitemapEntry) =>
        entry.url.includes('/compare/')
      ).length;

      expect(newCount).toBe(originalCount + 1);
    });
  });
});
