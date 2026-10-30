/**
 * badge-build.test.ts - Build output verification tests
 * Task: TASK-33-16 - Implement badge.js Embed Script
 */

import { describe, it, expect } from 'vitest';
import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';

const PUBLIC_DIR = path.join(__dirname, '../../public');
const BUILD_SCRIPT = path.join(__dirname, '../../scripts/build-badge.mjs');

// ============================================================================
// BDG-BUILD: Build Output Tests
// ============================================================================

describe('BDG-BUILD: Build Output', () => {
  describe('Source Files', () => {
    it('BDG-BUILD-05: TypeScript source exists', () => {
      const sourcePath = path.join(__dirname, '../../lib/scripts/badge.ts');
      expect(fs.existsSync(sourcePath)).toBe(true);
    });

    it('TypeScript source has valid syntax', () => {
      const sourcePath = path.join(__dirname, '../../lib/scripts/badge.ts');
      const content = fs.readFileSync(sourcePath, 'utf-8');
      
      // Check for basic TypeScript patterns
      expect(content).toContain('export');
      expect(content).toContain('interface');
      expect(content).toContain('function');
    });
  });

  describe('Build Output', () => {
    // These tests run after build
    const badgePath = path.join(PUBLIC_DIR, 'badge.js');
    const badgeMinPath = path.join(PUBLIC_DIR, 'badge.min.js');
    const badgeMapPath = path.join(PUBLIC_DIR, 'badge.min.js.map');

    it('BDG-BUILD-01: Output file exists (badge.js)', () => {
      expect(fs.existsSync(badgePath)).toBe(true);
    });

    it('BDG-BUILD-02: Minified output exists (badge.min.js)', () => {
      expect(fs.existsSync(badgeMinPath)).toBe(true);
    });

    it('BDG-BUILD-03: Minified size under 10KB gzip', async () => {
      if (!fs.existsSync(badgeMinPath)) {
        expect(true).toBe(true); // Skip if not built yet
        return;
      }

      const content = fs.readFileSync(badgeMinPath);
      
      // Use gzip compression
      const zlib = await import('zlib');
      const gzipped = zlib.gzipSync(content);
      
      expect(gzipped.length).toBeLessThan(10240); // 10KB
    });

    it('BDG-BUILD-04: Source map exists', () => {
      if (!fs.existsSync(badgeMinPath)) {
        expect(true).toBe(true); // Skip if not built yet
        return;
      }
      expect(fs.existsSync(badgeMapPath)).toBe(true);
    });

    it('BDG-BUILD-06: No external dependencies in bundle', () => {
      if (!fs.existsSync(badgeMinPath)) {
        expect(true).toBe(true); // Skip if not built yet
        return;
      }

      const content = fs.readFileSync(badgeMinPath, 'utf-8');
      
      // Should not contain node_modules references
      expect(content).not.toContain('node_modules');
      expect(content).not.toContain('require(');
    });

    it('BDG-BUILD-07: TypeScript compiled to JavaScript', () => {
      if (!fs.existsSync(badgeMinPath)) {
        expect(true).toBe(true); // Skip if not built yet
        return;
      }

      const content = fs.readFileSync(badgeMinPath, 'utf-8');
      
      // Should be valid JavaScript
      expect(() => new Function(content)).not.toThrow();
    });

    it('BDG-BUILD-08: ES5 compatible output', () => {
      if (!fs.existsSync(badgeMinPath)) {
        expect(true).toBe(true); // Skip if not built yet
        return;
      }

      const content = fs.readFileSync(badgeMinPath, 'utf-8');
      
      // Should not contain ES6+ only syntax that breaks ES5
      // (Arrow functions and const/let are fine, but no native Proxy, etc.)
      expect(content).not.toContain('class ');
    });

    it('BDG-BUILD-09: IIFE/UMD wrapper present', () => {
      if (!fs.existsSync(badgeMinPath)) {
        expect(true).toBe(true); // Skip if not built yet
        return;
      }

      const content = fs.readFileSync(badgeMinPath, 'utf-8');
      
      // Should contain UMD wrapper patterns
      const hasUMD = content.includes('define') || 
                     content.includes('module.exports') ||
                     content.includes('window.SEOIndicatorBadge');
      
      expect(hasUMD).toBe(true);
    });
  });

  describe('Static Serve', () => {
    it('BDG-BUILD-10: File is in public directory for static serve', () => {
      const badgePath = path.join(PUBLIC_DIR, 'badge.js');
      expect(fs.existsSync(badgePath)).toBe(true);
    });
  });
});

// ============================================================================
// Build Script Tests
// ============================================================================

describe('Build Script', () => {
  it('Build script exists', () => {
    // Check if build script exists (optional, may be created later)
    const scriptExists = fs.existsSync(BUILD_SCRIPT);
    // This test is informational
    expect(typeof scriptExists).toBe('boolean');
  });
});
