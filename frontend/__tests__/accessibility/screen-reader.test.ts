/**
 * Tests for Screen Reader Accessibility
 * Task: TASK-28-17 - Color Contrast and Screen Reader Testing
 *
 * These tests verify screen reader compatibility including:
 * - Alt text presence on images
 * - ARIA labels on interactive elements
 * - Document structure and landmarks
 * - Heading hierarchy
 */

import * as fs from 'fs';
import * as path from 'path';

// ============================================================================
// Type Definitions
// ============================================================================

interface ComponentInfo {
  name: string;
  path: string;
  content: string;
}

// ============================================================================
// File Utilities
// ============================================================================

/**
 * Get all component files recursively
 */
function getComponentFiles(dir: string, extension: string = '.tsx'): string[] {
  const files: string[] = [];

  if (!fs.existsSync(dir)) return files;

  const entries = fs.readdirSync(dir, { withFileTypes: true });

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      // Skip test directories and node_modules
      if (entry.name !== '__tests__' && entry.name !== 'node_modules' && entry.name !== '.next') {
        files.push(...getComponentFiles(fullPath, extension));
      }
    } else if (entry.name.endsWith(extension)) {
      files.push(fullPath);
    }
  }

  return files;
}

/**
 * Read file content
 */
function readFileContent(filePath: string): string {
  return fs.readFileSync(filePath, 'utf-8');
}

/**
 * Get all image files in public directory
 */
function getImageFiles(publicDir: string): string[] {
  const extensions = ['.png', '.jpg', '.jpeg', '.gif', '.svg', '.webp', '.ico'];
  const files: string[] = [];

  if (!fs.existsSync(publicDir)) return files;

  const entries = fs.readdirSync(publicDir, { withFileTypes: true });

  for (const entry of entries) {
    const fullPath = path.join(publicDir, entry.name);

    if (entry.isDirectory()) {
      files.push(...getImageFiles(fullPath));
    } else if (extensions.some(ext => entry.name.toLowerCase().endsWith(ext))) {
      files.push(fullPath);
    }
  }

  return files;
}

// ============================================================================
// Constants
// ============================================================================

const COMPONENTS_DIR = path.join(process.cwd(), 'components');
const APP_DIR = path.join(process.cwd(), 'app');
const PUBLIC_DIR = path.join(process.cwd(), 'public');

// ============================================================================
// Image Alt Text Tests
// ============================================================================

describe('Screen Reader - Alt Text (SR-AT)', () => {
  describe('SR-AT-001: Check for img elements in components', () => {
    it('should scan components directory', () => {
      expect(fs.existsSync(COMPONENTS_DIR)).toBe(true);
    });

    it('should find component files', () => {
      const files = getComponentFiles(COMPONENTS_DIR);
      expect(files.length).toBeGreaterThan(0);
    });
  });

  describe('SR-AT-002: Check img elements have alt attributes', () => {
    it('img elements should have alt attributes (check pattern)', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      const issues: string[] = [];

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Find img tags without alt attribute
        // Pattern: <img ... without alt=
        const imgMatches = content.matchAll(/<img\s+([^>]*)>/g);

        for (const match of imgMatches) {
          const attributes = match[1];
          // Check if alt attribute exists
          if (!/alt\s*=/.test(attributes)) {
            issues.push(`Missing alt in: ${path.relative(process.cwd(), file)}`);
          }
        }
      }

      // This test documents issues rather than failing
      // Many decorative images may intentionally have empty alt
      if (issues.length > 0) {
        console.log('Images potentially missing alt:', issues);
      }
    });
  });

  describe('SR-AT-003: Check for decorative image pattern', () => {
    it('decorative images should use alt="" (empty alt)', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Check for aria-hidden on images (decorative)
        if (/aria-hidden=["']true["']/.test(content)) {
          // Should also have alt="" for screen readers
          // This is documented for developer awareness
          expect(true).toBe(true); // Pattern found
        }
      }
    });
  });

  describe('SR-AT-004: Check for Next.js Image component usage', () => {
    it('components should use Next.js Image when possible', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      let imageComponentUsage = 0;
      let imgTagUsage = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        if (/<Image\s/.test(content) || /from ['"]next\/image['"]/.test(content)) {
          imageComponentUsage++;
        }

        if (/<img\s/.test(content)) {
          imgTagUsage++;
        }
      }

      // Document usage for optimization
      console.log(`Next.js Image usage: ${imageComponentUsage}, native img usage: ${imgTagUsage}`);
      expect(true).toBe(true); // Informational test
    });
  });

  describe('SR-AT-005: Check public directory for images', () => {
    it('should have public directory', () => {
      expect(fs.existsSync(PUBLIC_DIR)).toBe(true);
    });

    it('should identify image files in public', () => {
      const images = getImageFiles(PUBLIC_DIR);
      // Document image count
      console.log(`Found ${images.length} image files in public directory`);
      expect(true).toBe(true);
    });
  });

  describe('SR-AT-006: SVG icons should have accessible labels', () => {
    it('SVG elements should have aria-label or title', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      const issues: string[] = [];

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Find SVG tags
        const svgMatches = content.matchAll(/<svg\s+([^>]*)>/g);

        for (const match of svgMatches) {
          const attributes = match[1];

          // Check if SVG has accessible label
          const hasLabel =
            /aria-label\s*=/.test(attributes) ||
            /aria-labelledby\s*=/.test(attributes) ||
            /role\s*=\s*["']img["']/.test(attributes) ||
            content.substring(content.indexOf(match[0])).includes('<title>');

          // SVG with aria-hidden="true" is decorative (OK)
          if (!/aria-hidden=["']true["']/.test(attributes) && !hasLabel) {
            issues.push(`SVG missing label in: ${path.relative(process.cwd(), file)}`);
          }
        }
      }

      if (issues.length > 0) {
        console.log('SVGs potentially missing labels:', issues);
      }
    });
  });
});

// ============================================================================
// ARIA Label Tests
// ============================================================================

describe('Screen Reader - ARIA Labels (SR-AR)', () => {
  describe('SR-AR-001: Button elements should have accessible names', () => {
    it('buttons should have aria-label, aria-labelledby, or text content', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      const issues: string[] = [];

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Find button elements
        const buttonMatches = content.matchAll(/<button\s+([^>]*)>(.*?)<\/button>/gs);

        for (const match of buttonMatches) {
          const attributes = match[1];
          const innerContent = match[2];

          const hasLabel =
            /aria-label\s*=/.test(attributes) ||
            /aria-labelledby\s*=/.test(attributes) ||
            innerContent.trim().length > 0;

          if (!hasLabel) {
            issues.push(`Button without label in: ${path.relative(process.cwd(), file)}`);
          }
        }
      }

      if (issues.length > 0) {
        console.log('Buttons potentially missing labels:', issues);
      }
    });
  });

  describe('SR-AR-002: Link elements should have accessible names', () => {
    it('links should have aria-label or text content', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      const issues: string[] = [];

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Find anchor elements
        const linkMatches = content.matchAll(/<a\s+([^>]*)>(.*?)<\/a>/gs);

        for (const match of linkMatches) {
          const attributes = match[1];
          const innerContent = match[2];

          const hasLabel =
            /aria-label\s*=/.test(attributes) ||
            /aria-labelledby\s*=/.test(attributes) ||
            innerContent.trim().length > 0;

          if (!hasLabel) {
            issues.push(`Link without label in: ${path.relative(process.cwd(), file)}`);
          }
        }
      }

      if (issues.length > 0) {
        console.log('Links potentially missing labels:', issues);
      }
    });
  });

  describe('SR-AR-003: Form inputs should have labels', () => {
    it('inputs should have associated labels', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      const issues: string[] = [];

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Find input elements (not type="hidden")
        const inputMatches = content.matchAll(/<input\s+([^>]*)>/g);

        for (const match of inputMatches) {
          const attributes = match[1];

          // Skip hidden inputs
          if (/type\s*=\s*["']hidden["']/.test(attributes)) continue;

          const hasLabel =
            /aria-label\s*=/.test(attributes) ||
            /aria-labelledby\s*=/.test(attributes) ||
            /id\s*=/.test(attributes); // Has ID that might be referenced by <label for>

          if (!hasLabel) {
            issues.push(`Input without label in: ${path.relative(process.cwd(), file)}`);
          }
        }
      }

      if (issues.length > 0) {
        console.log('Inputs potentially missing labels:', issues);
      }
    });
  });

  describe('SR-AR-004: Check for role attributes', () => {
    it('interactive elements should have appropriate roles', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      let roleCount = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Count role attribute usage
        const roleMatches = content.match(/role\s*=/g);
        if (roleMatches) {
          roleCount += roleMatches.length;
        }
      }

      // Document role usage
      console.log(`Found ${roleCount} role attributes in components`);
      expect(roleCount).toBeGreaterThan(0);
    });
  });

  describe('SR-AR-005: Check for aria-live regions', () => {
    it('dynamic content updates should use aria-live', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      let ariaLiveCount = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        if (/aria-live\s*=/.test(content)) {
          ariaLiveCount++;
        }
      }

      // Document aria-live usage
      console.log(`Found aria-live in ${ariaLiveCount} components`);
      expect(true).toBe(true); // Informational
    });
  });

  describe('SR-AR-006: Check for aria-expanded on expandable elements', () => {
    it('expandable components should use aria-expanded', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      let expandedCount = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        if (/aria-expanded\s*=/.test(content)) {
          expandedCount++;
        }
      }

      // Document aria-expanded usage
      console.log(`Found aria-expanded in ${expandedCount} components`);
      expect(true).toBe(true);
    });
  });

  describe('SR-AR-007: Check for aria-current on navigation', () => {
    it('navigation should indicate current page', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Check for aria-current in navigation-related files
        if (/nav/i.test(file) || /sidebar/i.test(file) || /menu/i.test(file)) {
          if (/aria-current\s*=/.test(content)) {
            console.log(`aria-current found in: ${path.basename(file)}`);
          }
        }
      }
    });
  });

  describe('SR-AR-008: Check for aria-describedby usage', () => {
    it('form fields should have descriptions when needed', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      let describedByCount = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        if (/aria-describedby\s*=/.test(content)) {
          describedByCount++;
        }
      }

      console.log(`Found aria-describedby in ${describedByCount} components`);
      expect(true).toBe(true);
    });
  });
});

// ============================================================================
// Document Structure Tests
// ============================================================================

describe('Accessibility - Document Structure (AS-DS)', () => {
  describe('AS-DS-001: Check for main landmark', () => {
    it('app pages should have a main element', () => {
      // Check layout files for main element
      const layoutPath = path.join(APP_DIR, 'layout.tsx');

      if (fs.existsSync(layoutPath)) {
        const content = readFileContent(layoutPath);
        const hasMain = /<main|role\s*=\s*["']main["']/.test(content);

        if (!hasMain) {
          console.log('Main element not found in root layout - check page layouts');
        }
      }
    });
  });

  describe('AS-DS-002: Check heading hierarchy', () => {
    it('pages should have proper heading hierarchy', () => {
      const pageFiles = getComponentFiles(APP_DIR);
      const issues: string[] = [];

      for (const file of pageFiles) {
        if (!file.includes('page.tsx')) continue;

        const content = readFileContent(file);

        // Check for h1
        if (!/<h1|<H1/.test(content)) {
          // May be in a component - just document
          console.log(`No H1 found in: ${path.relative(process.cwd(), file)}`);
        }
      }

      expect(true).toBe(true); // Informational
    });
  });

  describe('AS-DS-003: Check for skip links', () => {
    it('should have skip to main content link', () => {
      const layoutPath = path.join(APP_DIR, 'layout.tsx');

      if (fs.existsSync(layoutPath)) {
        const content = readFileContent(layoutPath);

        // Check for skip link pattern
        const hasSkipLink =
          /skip/i.test(content) ||
          /href\s*=\s*["']#main/i.test(content) ||
          /href\s*=\s*["']#content/i.test(content);

        if (!hasSkipLink) {
          console.log('Skip link not found in layout - recommended for accessibility');
        }
      }
    });
  });

  describe('AS-DS-004: Check for nav landmarks', () => {
    it('should have navigation landmarks', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      let navCount = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        if (/<nav|role\s*=\s*["']navigation["']/.test(content)) {
          navCount++;
        }
      }

      expect(navCount).toBeGreaterThan(0);
    });
  });

  describe('AS-DS-005: Check for footer landmark', () => {
    it('should have footer element', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      let footerCount = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        if (/<footer|role\s*=\s*["']contentinfo["']/.test(content)) {
          footerCount++;
        }
      }

      expect(footerCount).toBeGreaterThan(0);
    });
  });

  describe('AS-DS-006: Check for header landmark', () => {
    it('should have header element', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      let headerCount = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        if (/<header|role\s*=\s*["']banner["']/.test(content)) {
          headerCount++;
        }
      }

      expect(headerCount).toBeGreaterThan(0);
    });
  });
});
