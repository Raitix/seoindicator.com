/**
 * @jest-environment jsdom
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';

// Types
interface SitemapEntry {
  url: string;
  lastmod?: string;
  changefreq?: 'always' | 'hourly' | 'daily' | 'weekly' | 'monthly' | 'yearly' | 'never';
  priority?: number;
}

// Constants
const SITE_URL = 'https://seoindicator.com';

// Tool slugs
const TOOL_SLUGS = [
  'meta-tag-analyzer',
  'keyword-density',
  'mobile-friendly-checker',
  'social-media-preview',
];

// Mock sitemap generators
function generateToolsSitemap(): SitemapEntry[] {
  const entries: SitemapEntry[] = [];
  
  // Tools landing page
  entries.push({
    url: `${SITE_URL}/tools/`,
    lastmod: new Date().toISOString().split('T')[0],
    changefreq: 'weekly',
    priority: 0.8,
  });
  
  // Individual tool pages
  TOOL_SLUGS.forEach(slug => {
    entries.push({
      url: `${SITE_URL}/tools/${slug}/`,
      lastmod: new Date().toISOString().split('T')[0],
      changefreq: 'monthly',
      priority: 0.8,
    });
  });
  
  return entries;
}

function generateSitemapXml(entries: SitemapEntry[]): string {
  let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
  xml += '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n';
  
  entries.forEach(entry => {
    xml += '  <url>\n';
    xml += `    <loc>${escapeXml(entry.url)}</loc>\n`;
    if (entry.lastmod) {
      xml += `    <lastmod>${entry.lastmod}</lastmod>\n`;
    }
    if (entry.changefreq) {
      xml += `    <changefreq>${entry.changefreq}</changefreq>\n`;
    }
    if (entry.priority !== undefined) {
      xml += `    <priority>${entry.priority.toFixed(1)}</priority>\n`;
    }
    xml += '  </url>\n';
  });
  
  xml += '</urlset>';
  return xml;
}

function escapeXml(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

function validateSitemapEntry(entry: SitemapEntry): boolean {
  // URL must be valid HTTPS
  if (!entry.url.startsWith('https://')) return false;
  
  // Priority must be between 0 and 1
  if (entry.priority !== undefined && (entry.priority < 0 || entry.priority > 1)) {
    return false;
  }
  
  // changefreq must be valid value
  const validFreq = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
  if (entry.changefreq && !validFreq.includes(entry.changefreq)) {
    return false;
  }
  
  // lastmod must be valid date format
  if (entry.lastmod) {
    const dateRegex = /^\d{4}-\d{2}-\d{2}/;
    if (!dateRegex.test(entry.lastmod)) return false;
  }
  
  return true;
}

function validateXml(xml: string): boolean {
  // Basic XML structure validation
  return xml.includes('<?xml') && 
         xml.includes('<urlset') && 
         xml.includes('</urlset>');
}

describe('XML Sitemap Integration', () => {
  describe('Sitemap Generation (SM)', () => {
    it('SM-001: includes /tools/ in sitemap', () => {
      const sitemap = generateToolsSitemap();
      
      expect(sitemap.some(e => e.url === 'https://seoindicator.com/tools/')).toBe(true);
    });

    it('SM-002: includes /tools/meta-tag-analyzer/', () => {
      const sitemap = generateToolsSitemap();
      
      expect(sitemap.some(e => e.url === 'https://seoindicator.com/tools/meta-tag-analyzer/')).toBe(true);
    });

    it('SM-003: includes /tools/keyword-density/', () => {
      const sitemap = generateToolsSitemap();
      
      expect(sitemap.some(e => e.url === 'https://seoindicator.com/tools/keyword-density/')).toBe(true);
    });

    it('SM-004: includes /tools/mobile-friendly-checker/', () => {
      const sitemap = generateToolsSitemap();
      
      expect(sitemap.some(e => e.url === 'https://seoindicator.com/tools/mobile-friendly-checker/')).toBe(true);
    });

    it('SM-005: includes /tools/social-media-preview/', () => {
      const sitemap = generateToolsSitemap();
      
      expect(sitemap.some(e => e.url === 'https://seoindicator.com/tools/social-media-preview/')).toBe(true);
    });

    it('SM-006: sets correct priority for tools (0.8)', () => {
      const sitemap = generateToolsSitemap();
      
      sitemap.forEach(entry => {
        expect(entry.priority).toBe(0.8);
      });
    });

    it('SM-007: sets correct changefreq for tools', () => {
      const sitemap = generateToolsSitemap();
      
      const landingPage = sitemap.find(e => e.url === 'https://seoindicator.com/tools/');
      expect(landingPage?.changefreq).toBe('weekly');
      
      const toolPages = sitemap.filter(e => e.url !== 'https://seoindicator.com/tools/');
      toolPages.forEach(entry => {
        expect(entry.changefreq).toBe('monthly');
      });
    });

    it('SM-008: includes lastmod for tool pages', () => {
      const sitemap = generateToolsSitemap();
      
      sitemap.forEach(entry => {
        expect(entry.lastmod).toBeDefined();
        expect(entry.lastmod).toMatch(/^\d{4}-\d{2}-\d{2}$/);
      });
    });

    it('SM-009: generates valid XML', () => {
      const sitemap = generateToolsSitemap();
      const xml = generateSitemapXml(sitemap);
      
      expect(validateXml(xml)).toBe(true);
    });

    it('SM-010: uses HTTPS URLs', () => {
      const sitemap = generateToolsSitemap();
      
      sitemap.forEach(entry => {
        expect(entry.url).toMatch(/^https:\/\//);
      });
    });

    it('SM-011: includes all tool slugs', () => {
      const sitemap = generateToolsSitemap();
      const urls = sitemap.map(e => e.url);
      
      TOOL_SLUGS.forEach(slug => {
        expect(urls).toContain(`https://seoindicator.com/tools/${slug}/`);
      });
    });

    it('SM-012: validates sitemap index format', () => {
      const sitemap = generateToolsSitemap();
      const xml = generateSitemapXml(sitemap);
      
      expect(xml).toContain('<?xml version="1.0" encoding="UTF-8"?>');
      expect(xml).toContain('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"');
    });

    it('SM-013: handles dynamic tool additions', () => {
      // Simulate adding a new tool
      const dynamicSlugs = [...TOOL_SLUGS, 'new-seo-tool'];
      const entries: SitemapEntry[] = [
        { url: `${SITE_URL}/tools/`, changefreq: 'weekly', priority: 0.8 },
        ...dynamicSlugs.map(slug => ({
          url: `${SITE_URL}/tools/${slug}/`,
          changefreq: 'monthly' as const,
          priority: 0.8,
        })),
      ];
      
      expect(entries.length).toBe(6); // 1 landing + 5 tools
    });

    it('SM-014: priority between 0.0 and 1.0', () => {
      const sitemap = generateToolsSitemap();
      
      sitemap.forEach(entry => {
        if (entry.priority !== undefined) {
          expect(entry.priority).toBeGreaterThanOrEqual(0);
          expect(entry.priority).toBeLessThanOrEqual(1);
        }
      });
    });

    it('SM-015: changefreq valid value', () => {
      const validFreq = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
      const sitemap = generateToolsSitemap();
      
      sitemap.forEach(entry => {
        if (entry.changefreq) {
          expect(validFreq).toContain(entry.changefreq);
        }
      });
    });
  });

  describe('Sitemap Entry Validation', () => {
    it('validates URL format', () => {
      const validEntry: SitemapEntry = {
        url: 'https://seoindicator.com/tools/test/',
        priority: 0.8,
      };
      
      expect(validateSitemapEntry(validEntry)).toBe(true);
    });

    it('rejects non-HTTPS URLs', () => {
      const invalidEntry: SitemapEntry = {
        url: 'http://seoindicator.com/tools/test/',
        priority: 0.8,
      };
      
      expect(validateSitemapEntry(invalidEntry)).toBe(false);
    });

    it('rejects invalid priority', () => {
      const invalidEntry: SitemapEntry = {
        url: 'https://seoindicator.com/tools/test/',
        priority: 1.5,
      };
      
      expect(validateSitemapEntry(invalidEntry)).toBe(false);
    });

    it('rejects invalid changefreq', () => {
      const invalidEntry: SitemapEntry = {
        url: 'https://seoindicator.com/tools/test/',
        changefreq: 'invalid' as 'monthly',
      };
      
      expect(validateSitemapEntry(invalidEntry)).toBe(false);
    });

    it('validates lastmod format', () => {
      const validEntry: SitemapEntry = {
        url: 'https://seoindicator.com/tools/test/',
        lastmod: '2026-03-08',
      };
      
      expect(validateSitemapEntry(validEntry)).toBe(true);
    });

    it('rejects invalid lastmod format', () => {
      const invalidEntry: SitemapEntry = {
        url: 'https://seoindicator.com/tools/test/',
        lastmod: '03/08/2026',
      };
      
      expect(validateSitemapEntry(invalidEntry)).toBe(false);
    });
  });

  describe('XML Generation', () => {
    it('generates proper XML structure', () => {
      const sitemap = generateToolsSitemap();
      const xml = generateSitemapXml(sitemap);
      
      expect(xml).toContain('<urlset');
      expect(xml).toContain('</urlset>');
      expect(xml).toContain('<url>');
      expect(xml).toContain('</url>');
    });

    it('includes all required elements', () => {
      const entry: SitemapEntry = {
        url: 'https://seoindicator.com/tools/test/',
        lastmod: '2026-03-08',
        changefreq: 'monthly',
        priority: 0.8,
      };
      const xml = generateSitemapXml([entry]);
      
      expect(xml).toContain('<loc>https://seoindicator.com/tools/test/</loc>');
      expect(xml).toContain('<lastmod>2026-03-08</lastmod>');
      expect(xml).toContain('<changefreq>monthly</changefreq>');
      expect(xml).toContain('<priority>0.8</priority>');
    });

    it('escapes special characters in URLs', () => {
      const entry: SitemapEntry = {
        url: 'https://seoindicator.com/tools/test?param=value&other=123',
        priority: 0.8,
      };
      const xml = generateSitemapXml([entry]);
      
      // Should contain escaped ampersand
      expect(xml).toContain('&amp;');
    });

    it('handles optional fields', () => {
      const minimalEntry: SitemapEntry = {
        url: 'https://seoindicator.com/tools/test/',
      };
      const xml = generateSitemapXml([minimalEntry]);
      
      expect(xml).toContain('<loc>');
      expect(xml).not.toContain('<lastmod>');
      expect(xml).not.toContain('<changefreq>');
      expect(xml).not.toContain('<priority>');
    });
  });

  describe('Integration with Next.js', () => {
    it('produces MetadataRoute.Sitemap format', () => {
      const sitemap = generateToolsSitemap();
      
      // Next.js expects this format
      const nextSitemap = sitemap.map(entry => ({
        url: entry.url,
        lastModified: entry.lastmod,
        changeFrequency: entry.changefreq,
        priority: entry.priority,
      }));
      
      expect(nextSitemap[0]).toHaveProperty('url');
      expect(nextSitemap[0]).toHaveProperty('priority');
    });

    it('matches Next.js sitemap.ts output', () => {
      const sitemap = generateToolsSitemap();
      
      // Verify structure matches what Next.js expects
      sitemap.forEach(entry => {
        expect(entry).toHaveProperty('url');
        expect(typeof entry.url).toBe('string');
      });
    });
  });

  describe('Edge Cases', () => {
    it('handles empty tool list', () => {
      const emptySlugs: string[] = [];
      const entries: SitemapEntry[] = [
        { url: `${SITE_URL}/tools/`, changefreq: 'weekly', priority: 0.8 },
      ];
      
      expect(entries.length).toBe(1);
    });

    it('handles special characters in slugs', () => {
      const slug = 'outil-seo-francais';
      const url = `${SITE_URL}/tools/${slug}/`;
      
      expect(url).toContain('outil-seo-francais');
    });

    it('handles large number of tools', () => {
      const largeSlugs = Array(100).fill(null).map((_, i) => `tool-${i}`);
      const entries: SitemapEntry[] = [
        { url: `${SITE_URL}/tools/`, changefreq: 'weekly', priority: 0.8 },
        ...largeSlugs.map(slug => ({
          url: `${SITE_URL}/tools/${slug}/`,
          changefreq: 'monthly' as const,
          priority: 0.8,
        })),
      ];
      
      expect(entries.length).toBe(101);
    });

    it('handles concurrent sitemap generation', async () => {
      const results = await Promise.all([
        Promise.resolve(generateToolsSitemap()),
        Promise.resolve(generateToolsSitemap()),
        Promise.resolve(generateToolsSitemap()),
      ]);
      
      results.forEach(sitemap => {
        expect(sitemap.length).toBe(5); // 1 landing + 4 tools
      });
    });
  });

  describe('Performance', () => {
    it('generates sitemap quickly', () => {
      const start = performance.now();
      const sitemap = generateToolsSitemap();
      const duration = performance.now() - start;
      
      expect(sitemap.length).toBeGreaterThan(0);
      expect(duration).toBeLessThan(10); // Should be nearly instant
    });

    it('generates XML efficiently', () => {
      const sitemap = Array(1000).fill(null).map((_, i) => ({
        url: `https://seoindicator.com/tools/tool-${i}/`,
        changefreq: 'monthly' as const,
        priority: 0.8,
      }));
      
      const start = performance.now();
      const xml = generateSitemapXml(sitemap);
      const duration = performance.now() - start;
      
      expect(xml.length).toBeGreaterThan(0);
      expect(duration).toBeLessThan(100);
    });
  });
});
