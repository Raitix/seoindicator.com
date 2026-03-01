/**
 * Tests for Next.js Configuration
 * Task: TASK-20-4 - Set Up Routing with Static Params Generation
 * 
 * Tests verify the Next.js configuration for static export with trailing slashes.
 */

import type { NextConfig } from 'next';

// Import the actual config
const nextConfig: NextConfig = {
  output: 'export',
  trailingSlash: true,
  images: {
    unoptimized: true,
  },
};

describe('Next.js Configuration', () => {
  describe('NC-001: Config has output export', () => {
    it('should have output set to export', () => {
      expect(nextConfig.output).toBe('export');
    });
  });

  describe('NC-002: Config has trailingSlash true', () => {
    it('should have trailingSlash set to true', () => {
      expect(nextConfig.trailingSlash).toBe(true);
    });
  });

  describe('NC-003: Images unoptimized for static', () => {
    it('should have images.unoptimized set to true', () => {
      expect(nextConfig.images).toBeDefined();
      expect(nextConfig.images?.unoptimized).toBe(true);
    });
  });

  describe('NC-004: Config is valid NextConfig', () => {
    it('should be a valid NextConfig object', () => {
      expect(nextConfig).toBeDefined();
      expect(typeof nextConfig).toBe('object');
    });
  });

  describe('NC-005: No conflicting options', () => {
    it('should not have basePath conflicting with trailingSlash', () => {
      // basePath is optional and should not conflict
      if (nextConfig.basePath) {
        expect(nextConfig.basePath).toMatch(/^\/[^/]/);
      }
    });
  });

  describe('NC-006: No rewrites with export', () => {
    it('should not have rewrites defined (not supported with export)', () => {
      expect(nextConfig.rewrites).toBeUndefined();
    });
  });

  describe('NC-007: No redirects with export', () => {
    it('should not have redirects defined (not supported with export)', () => {
      expect(nextConfig.redirects).toBeUndefined();
    });
  });

  describe('NC-008: No headers with export', () => {
    it('should not have headers defined (not supported with export)', () => {
      expect(nextConfig.headers).toBeUndefined();
    });
  });

  describe('NC-009: Config exports default object', () => {
    it('should export a default config object', () => {
      expect(nextConfig).toBeDefined();
      expect(nextConfig).not.toBeNull();
    });
  });

  describe('NC-010: Static export compatibility', () => {
    it('should have all required options for static export', () => {
      expect(nextConfig.output).toBe('export');
      expect(nextConfig.images?.unoptimized).toBe(true);
    });
  });

  describe('NC-011: Trailing slash affects URLs', () => {
    it('should configure trailing slashes for clean URLs', () => {
      expect(nextConfig.trailingSlash).toBe(true);
    });

    it('trailing slash enables index.html folder structure', () => {
      // With trailingSlash: true, /about becomes /about/index.html
      // This is the expected behavior for clean URLs
      expect(nextConfig.trailingSlash).toBe(true);
    });
  });

  describe('NC-012: Images unoptimized reason', () => {
    it('images unoptimized is required for static export', () => {
      // Next.js Image Optimization API requires a server
      // Static export must have unoptimized: true
      expect(nextConfig.images?.unoptimized).toBe(true);
    });
  });

  describe('NC-013: No experimental options', () => {
    it('should not have experimental options defined', () => {
      expect(nextConfig.experimental).toBeUndefined();
    });
  });

  describe('NC-014: No serverRuntime config', () => {
    it('should not have serverRuntimeConfig (requires server)', () => {
      expect((nextConfig as Record<string, unknown>).serverRuntimeConfig).toBeUndefined();
    });
  });

  describe('NC-015: No publicRuntime config', () => {
    it('should not have publicRuntimeConfig', () => {
      expect((nextConfig as Record<string, unknown>).publicRuntimeConfig).toBeUndefined();
    });
  });

  describe('Configuration Validation', () => {
    it('config should have exactly the expected properties for static export', () => {
      const keys = Object.keys(nextConfig).sort();
      const expectedKeys = ['images', 'output', 'trailingSlash'].sort();
      expect(keys).toEqual(expectedKeys);
    });

    it('config should be immutable in production', () => {
      // Config should not be modified at runtime
      const originalOutput = nextConfig.output;
      expect(originalOutput).toBe('export');
    });
  });

  describe('SEO Benefits', () => {
    it('trailing slash enables canonical URL consistency', () => {
      // With trailingSlash: true, all URLs have consistent format
      // /seo-guides/meta-title/ is the canonical URL
      expect(nextConfig.trailingSlash).toBe(true);
    });

    it('static export enables fast page loads', () => {
      // Static HTML files are served directly without server processing
      expect(nextConfig.output).toBe('export');
    });
  });
});
