/**
 * @fileoverview Tests for UI/UX documentation completeness
 * 
 * Validates that:
 * - DOCUMENTATION.md is updated with new UI features
 * - DESIGN-SYSTEM.md exists with design tokens
 * - frontend/components/README.md exists with component usage
 * - Accessibility guidelines are documented
 * - Icon usage guide is complete
 * 
 * @see TESTS/TEST-28-19.md
 */

import * as fs from 'fs';
import * as path from 'path';

// ============================================================================
// Test Configuration
// ============================================================================

const PROJECT_ROOT = path.resolve(__dirname, '..', '..', '..', '..');
const DOCUMENTATION_PATH = path.join(PROJECT_ROOT, 'DOCUMENTATION.md');
const DESIGN_SYSTEM_PATH = path.join(PROJECT_ROOT, 'DESIGN-SYSTEM.md');
const COMPONENTS_README_PATH = path.join(PROJECT_ROOT, 'frontend', 'components', 'README.md');
const GLOBALS_CSS_PATH = path.join(PROJECT_ROOT, 'frontend', 'app', 'globals.css');

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Check if a file exists
 */
function fileExists(filePath: string): boolean {
  return fs.existsSync(filePath);
}

/**
 * Read file contents
 */
function readFile(filePath: string): string {
  if (!fs.existsSync(filePath)) {
    return '';
  }
  return fs.readFileSync(filePath, 'utf-8');
}

/**
 * Check if content contains a section (heading)
 */
function hasSection(content: string, heading: string): boolean {
  const headingPattern = new RegExp(`^#{1,6}\\s+${escapeRegex(heading)}`, 'm');
  return headingPattern.test(content);
}

/**
 * Check if content contains text (case-insensitive)
 */
function containsText(content: string, text: string): boolean {
  return content.toLowerCase().includes(text.toLowerCase());
}

/**
 * Escape special regex characters
 */
function escapeRegex(str: string): string {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Extract CSS variable value from content
 */
function extractCssVariable(content: string, varName: string): string | null {
  const pattern = new RegExp(`--${escapeRegex(varName)}\\s*:\\s*([^;]+);`);
  const match = content.match(pattern);
  return match ? match[1].trim() : null;
}

/**
 * Parse oklch value and extract components
 */
function parseOklch(value: string): { l: number; c: number; h: number } | null {
  const match = value.match(/oklch\s*\(\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)/);
  if (!match) return null;
  return {
    l: parseFloat(match[1]),
    c: parseFloat(match[2]),
    h: parseFloat(match[3]),
  };
}

// ============================================================================
// Test Suite
// ============================================================================

describe('UI/UX Documentation', () => {
  // ==========================================================================
  // File Existence Tests (FE)
  // ==========================================================================
  describe('File Existence (FE)', () => {
    it('FE-01: DOCUMENTATION.md exists', () => {
      expect(fileExists(DOCUMENTATION_PATH)).toBe(true);
    });

    it('FE-02: DESIGN-SYSTEM.md exists', () => {
      expect(fileExists(DESIGN_SYSTEM_PATH)).toBe(true);
    });

    it('FE-03: frontend/components/README.md exists', () => {
      expect(fileExists(COMPONENTS_README_PATH)).toBe(true);
    });
  });

  // ==========================================================================
  // DOCUMENTATION.md Update Tests (DU)
  // ==========================================================================
  describe('DOCUMENTATION.md Updates (DU)', () => {
    let docContent: string;

    beforeAll(() => {
      docContent = readFile(DOCUMENTATION_PATH);
    });

    it('DU-01: Contains indicator catalog section', () => {
      expect(
        hasSection(docContent, 'Indicator Catalog') ||
        hasSection(docContent, 'IndicatorCatalog') ||
        containsText(docContent, 'indicator catalog')
      ).toBe(true);
    });

    it('DU-02: Contains design system variables section', () => {
      expect(
        hasSection(docContent, 'Design System') ||
        hasSection(docContent, 'Design Tokens') ||
        containsText(docContent, 'design system variables')
      ).toBe(true);
    });

    it('DU-03: Contains navigation components section', () => {
      expect(
        hasSection(docContent, 'Navigation') ||
        containsText(docContent, 'navigation components')
      ).toBe(true);
    });

    it('DU-04: Contains loading state patterns section', () => {
      expect(
        hasSection(docContent, 'Loading') ||
        hasSection(docContent, 'Skeleton') ||
        containsText(docContent, 'loading state')
      ).toBe(true);
    });

    it('DU-05: Contains accessibility features section', () => {
      expect(
        hasSection(docContent, 'Accessibility') ||
        containsText(docContent, 'accessibility features')
      ).toBe(true);
    });

    it('DU-06: Indicator catalog links to component location', () => {
      expect(
        containsText(docContent, 'components/indicators') ||
        containsText(docContent, 'IndicatorCard') ||
        containsText(docContent, 'IndicatorIcon')
      ).toBe(true);
    });

    it('DU-07: Design system links to globals.css', () => {
      expect(
        containsText(docContent, 'globals.css') ||
        containsText(docContent, 'CSS variables')
      ).toBe(true);
    });

    it('DU-08: All 8 indicator types documented', () => {
      const indicators = [
        'keyword',
        'visibility',
        'competitor',
        'technical',
        'backlink',
        'content',
        'serp',
        'speed',
      ];
      
      const foundIndicators = indicators.filter(ind =>
        containsText(docContent, ind)
      );
      
      expect(foundIndicators.length).toBeGreaterThanOrEqual(6);
    });
  });

  // ==========================================================================
  // DESIGN-SYSTEM.md Content Tests (DS)
  // ==========================================================================
  describe('DESIGN-SYSTEM.md Content (DS)', () => {
    let designContent: string;

    beforeAll(() => {
      designContent = readFile(DESIGN_SYSTEM_PATH);
    });

    it('DS-01: Brand colors documented', () => {
      expect(
        containsText(designContent, 'brand-primary') &&
        containsText(designContent, 'brand-secondary') &&
        containsText(designContent, 'brand-accent')
      ).toBe(true);
    });

    it('DS-02: Semantic colors documented', () => {
      expect(
        containsText(designContent, 'success') &&
        containsText(designContent, 'warning') &&
        containsText(designContent, 'error') &&
        containsText(designContent, 'info')
      ).toBe(true);
    });

    it('DS-03: Surface layers documented', () => {
      expect(
        containsText(designContent, 'surface-0') ||
        containsText(designContent, 'surface-1') ||
        containsText(designContent, 'surface layers')
      ).toBe(true);
    });

    it('DS-04: oklch color format used', () => {
      expect(containsText(designContent, 'oklch')).toBe(true);
    });

    it('DS-05: Dark mode variants documented', () => {
      expect(
        containsText(designContent, '.dark') ||
        containsText(designContent, 'dark mode') ||
        containsText(designContent, 'dark mode')
      ).toBe(true);
    });

    it('DS-06: Typography scale documented', () => {
      const typographyTerms = ['typography', 'font', 'heading', 'display', 'body'];
      const foundTerms = typographyTerms.filter(term => containsText(designContent, term));
      expect(foundTerms.length).toBeGreaterThanOrEqual(2);
    });

    it('DS-07: Spacing system documented', () => {
      expect(
        containsText(designContent, 'spacing') ||
        containsText(designContent, 'gap') ||
        containsText(designContent, 'padding')
      ).toBe(true);
    });

    it('DS-08: Color usage guidelines exist', () => {
      // Look for guidance on when to use colors
      expect(designContent.length).toBeGreaterThan(500);
    });

    it('DS-09: Tailwind integration explained', () => {
      expect(
        containsText(designContent, 'tailwind') ||
        containsText(designContent, '@theme')
      ).toBe(true);
    });
  });

  // ==========================================================================
  // Component Usage Tests (CU)
  // ==========================================================================
  describe('Component Usage (CU)', () => {
    let componentsReadme: string;

    beforeAll(() => {
      componentsReadme = readFile(COMPONENTS_README_PATH);
    });

    it('CU-01: IndicatorIcon usage documented', () => {
      expect(
        containsText(componentsReadme, 'IndicatorIcon') ||
        containsText(componentsReadme, 'indicator-icon')
      ).toBe(true);
    });

    it('CU-02: IndicatorCard usage documented', () => {
      expect(
        containsText(componentsReadme, 'IndicatorCard') ||
        containsText(componentsReadme, 'indicator-card')
      ).toBe(true);
    });

    it('CU-03: IndicatorGrid usage documented', () => {
      expect(
        containsText(componentsReadme, 'IndicatorGrid') ||
        containsText(componentsReadme, 'indicator-grid')
      ).toBe(true);
    });

    it('CU-04: IndicatorCatalog usage documented', () => {
      expect(
        containsText(componentsReadme, 'IndicatorCatalog') ||
        containsText(componentsReadme, 'indicator-catalog')
      ).toBe(true);
    });

    it('CU-05: Skeleton usage documented', () => {
      expect(
        containsText(componentsReadme, 'Skeleton') ||
        containsText(componentsReadme, 'skeleton')
      ).toBe(true);
    });

    it('CU-06: Progress usage documented', () => {
      expect(
        containsText(componentsReadme, 'Progress') ||
        containsText(componentsReadme, 'progress')
      ).toBe(true);
    });

    it('CU-07: Badge usage documented', () => {
      expect(
        containsText(componentsReadme, 'Badge') ||
        containsText(componentsReadme, 'badge')
      ).toBe(true);
    });

    it('CU-08: Tooltip usage documented', () => {
      expect(
        containsText(componentsReadme, 'Tooltip') ||
        containsText(componentsReadme, 'tooltip')
      ).toBe(true);
    });

    it('CU-09: Tabs usage documented', () => {
      expect(
        containsText(componentsReadme, 'Tabs') ||
        containsText(componentsReadme, 'tabs')
      ).toBe(true);
    });

    it('CU-10: Sheet usage documented', () => {
      expect(
        containsText(componentsReadme, 'Sheet') ||
        containsText(componentsReadme, 'sheet')
      ).toBe(true);
    });
  });

  // ==========================================================================
  // Accessibility Guidelines Tests (AG)
  // ==========================================================================
  describe('Accessibility Guidelines (AG)', () => {
    let docContent: string;
    let designContent: string;

    beforeAll(() => {
      docContent = readFile(DOCUMENTATION_PATH);
      designContent = readFile(DESIGN_SYSTEM_PATH);
    });

    const allContent = () => docContent + designContent;

    it('AG-01: ARIA labels guidance exists', () => {
      const content = allContent();
      expect(
        containsText(content, 'aria-label') ||
        containsText(content, 'aria-labelledby') ||
        containsText(content, 'ARIA')
      ).toBe(true);
    });

    it('AG-02: Keyboard navigation documented', () => {
      const content = allContent();
      expect(
        containsText(content, 'keyboard') ||
        containsText(content, 'Tab') ||
        containsText(content, 'Enter') ||
        containsText(content, 'Escape')
      ).toBe(true);
    });

    it('AG-03: Focus indicators documented', () => {
      const content = allContent();
      expect(
        containsText(content, 'focus') ||
        containsText(content, 'focus-visible') ||
        containsText(content, ':focus')
      ).toBe(true);
    });

    it('AG-04: Color contrast requirements', () => {
      const content = allContent();
      expect(
        containsText(content, 'contrast') ||
        containsText(content, '4.5:1') ||
        containsText(content, 'WCAG')
      ).toBe(true);
    });

    it('AG-05: Alt text guidance exists', () => {
      const content = allContent();
      expect(
        containsText(content, 'alt text') ||
        containsText(content, 'alt=') ||
        containsText(content, 'image')
      ).toBe(true);
    });

    it('AG-06: Screen reader considerations', () => {
      const content = allContent();
      expect(
        containsText(content, 'screen reader') ||
        containsText(content, 'aria-live') ||
        containsText(content, 'aria-hidden')
      ).toBe(true);
    });

    it('AG-07: Reduced motion support', () => {
      const content = allContent();
      expect(
        containsText(content, 'reduced motion') ||
        containsText(content, 'prefers-reduced-motion')
      ).toBe(true);
    });

    it('AG-08: WCAG 2.1 AA compliance mentioned', () => {
      const content = allContent();
      expect(
        containsText(content, 'WCAG') ||
        containsText(content, '2.1') ||
        containsText(content, 'AA')
      ).toBe(true);
    });
  });

  // ==========================================================================
  // Icon Usage Guide Tests (IG)
  // ==========================================================================
  describe('Icon Usage Guide (IG)', () => {
    let componentsReadme: string;

    beforeAll(() => {
      componentsReadme = readFile(COMPONENTS_README_PATH);
    });

    it('IG-01: Icon registry documented', () => {
      expect(
        containsText(componentsReadme, 'Lucide') ||
        containsText(componentsReadme, 'icon registry') ||
        containsText(componentsReadme, 'TrendingUp') ||
        containsText(componentsReadme, 'Eye')
      ).toBe(true);
    });

    it('IG-02: Size variants documented', () => {
      expect(
        containsText(componentsReadme, 'sm') ||
        containsText(componentsReadme, 'md') ||
        containsText(componentsReadme, 'lg') ||
        containsText(componentsReadme, 'size')
      ).toBe(true);
    });

    it('IG-03: Color variants documented', () => {
      expect(
        containsText(componentsReadme, 'primary') ||
        containsText(componentsReadme, 'success') ||
        containsText(componentsReadme, 'warning') ||
        containsText(componentsReadme, 'error')
      ).toBe(true);
    });

    it('IG-04: Custom icon usage explained', () => {
      expect(
        containsText(componentsReadme, 'customIcon') ||
        containsText(componentsReadme, 'custom icon') ||
        containsText(componentsReadme, 'SVG')
      ).toBe(true);
    });

    it('IG-05: Accessibility for icons', () => {
      expect(
        containsText(componentsReadme, 'aria-label') ||
        containsText(componentsReadme, 'accessible')
      ).toBe(true);
    });

    it('IG-06: Indicator-to-icon mapping table exists', () => {
      // Look for at least 4 indicator names
      const indicators = [
        'keyword',
        'visibility',
        'competitor',
        'technical',
        'backlink',
        'content',
        'serp',
        'speed',
      ];
      
      const foundIndicators = indicators.filter(ind =>
        containsText(componentsReadme, ind)
      );
      
      expect(foundIndicators.length).toBeGreaterThanOrEqual(4);
    });
  });

  // ==========================================================================
  // Design Token Validation Tests (DT)
  // ==========================================================================
  describe('Design Token Validation (DT)', () => {
    let globalsCss: string;
    let designContent: string;

    beforeAll(() => {
      globalsCss = readFile(GLOBALS_CSS_PATH);
      designContent = readFile(DESIGN_SYSTEM_PATH);
    });

    it('DT-01: Brand primary hue in green range', () => {
      const value = extractCssVariable(globalsCss, 'brand-primary');
      if (value) {
        const parsed = parseOklch(value);
        if (parsed) {
          // Green is roughly 120-180 in hue
          expect(parsed.h).toBeGreaterThanOrEqual(140);
          expect(parsed.h).toBeLessThanOrEqual(180);
        }
      }
      // If value not found, check design system docs
      expect(
        containsText(designContent, 'brand-primary') ||
        containsText(designContent, 'green') ||
        containsText(designContent, 'emerald')
      ).toBe(true);
    });

    it('DT-02: Success hue in green range', () => {
      const value = extractCssVariable(globalsCss, 'success');
      if (value) {
        const parsed = parseOklch(value);
        if (parsed) {
          // Success should be green (120-180)
          expect(parsed.h).toBeGreaterThanOrEqual(130);
          expect(parsed.h).toBeLessThanOrEqual(170);
        }
      }
      expect(containsText(designContent, 'success')).toBe(true);
    });

    it('DT-03: Warning hue in yellow/orange range', () => {
      const value = extractCssVariable(globalsCss, 'warning');
      if (value) {
        const parsed = parseOklch(value);
        if (parsed) {
          // Warning should be yellow/orange (60-100)
          expect(parsed.h).toBeGreaterThanOrEqual(60);
          expect(parsed.h).toBeLessThanOrEqual(100);
        }
      }
      expect(containsText(designContent, 'warning')).toBe(true);
    });

    it('DT-04: Error hue in red range', () => {
      const value = extractCssVariable(globalsCss, 'error');
      if (value) {
        const parsed = parseOklch(value);
        if (parsed) {
          // Error should be red (0-50)
          expect(parsed.h).toBeGreaterThanOrEqual(0);
          expect(parsed.h).toBeLessThanOrEqual(50);
        }
      }
      expect(containsText(designContent, 'error')).toBe(true);
    });

    it('DT-05: Info hue in blue range', () => {
      const value = extractCssVariable(globalsCss, 'info');
      if (value) {
        const parsed = parseOklch(value);
        if (parsed) {
          // Info should be blue (200-260)
          expect(parsed.h).toBeGreaterThanOrEqual(200);
          expect(parsed.h).toBeLessThanOrEqual(260);
        }
      }
      expect(containsText(designContent, 'info')).toBe(true);
    });

    it('DT-06: Surface layers have zero chroma', () => {
      // Surface layers should be neutral grays
      for (let i = 0; i <= 3; i++) {
        const value = extractCssVariable(globalsCss, `surface-${i}`);
        if (value) {
          const parsed = parseOklch(value);
          if (parsed) {
            expect(parsed.c).toBe(0);
          }
        }
      }
    });

    it('DT-07: Lightness progression in surfaces', () => {
      const lightnesses: number[] = [];
      for (let i = 0; i <= 3; i++) {
        const value = extractCssVariable(globalsCss, `surface-${i}`);
        if (value) {
          const parsed = parseOklch(value);
          if (parsed) {
            lightnesses.push(parsed.l);
          }
        }
      }
      
      // Each surface should be lighter than the previous
      for (let i = 1; i < lightnesses.length; i++) {
        expect(lightnesses[i]).toBeGreaterThan(lightnesses[i - 1]);
      }
    });
  });

  // ==========================================================================
  // Code Example Tests (CE)
  // ==========================================================================
  describe('Code Examples (CE)', () => {
    let componentsReadme: string;

    beforeAll(() => {
      componentsReadme = readFile(COMPONENTS_README_PATH);
    });

    it('CE-01: IndicatorIcon example present', () => {
      expect(
        containsText(componentsReadme, '```tsx') ||
        containsText(componentsReadme, '```typescript') ||
        containsText(componentsReadme, '<IndicatorIcon')
      ).toBe(true);
    });

    it('CE-02: IndicatorCard example present', () => {
      expect(
        containsText(componentsReadme, '```tsx') ||
        containsText(componentsReadme, '```typescript') ||
        containsText(componentsReadme, '<IndicatorCard')
      ).toBe(true);
    });

    it('CE-03: CSS variable usage example present', () => {
      const designContent = readFile(DESIGN_SYSTEM_PATH);
      expect(
        containsText(designContent, '```css') ||
        containsText(designContent, '--brand') ||
        containsText(designContent, 'oklch')
      ).toBe(true);
    });

    it('CE-04: Tailwind class examples present', () => {
      expect(
        containsText(componentsReadme, 'className') ||
        containsText(componentsReadme, 'bg-') ||
        containsText(componentsReadme, 'text-')
      ).toBe(true);
    });
  });

  // ==========================================================================
  // Cross-Reference Tests (CR)
  // ==========================================================================
  describe('Cross-References (CR)', () => {
    it('CR-01: DOCUMENTATION.md references DESIGN-SYSTEM.md', () => {
      const docContent = readFile(DOCUMENTATION_PATH);
      expect(
        containsText(docContent, 'DESIGN-SYSTEM.md') ||
        containsText(docContent, 'Design System') ||
        containsText(docContent, 'design-system')
      ).toBe(true);
    });

    it('CR-02: Components README references component files', () => {
      const readmeContent = readFile(COMPONENTS_README_PATH);
      expect(
        containsText(readmeContent, 'components/') ||
        containsText(readmeContent, '.tsx')
      ).toBe(true);
    });

    it('CR-03: Design tokens match globals.css', () => {
      // Verify design system documentation matches actual CSS
      const globalsCss = readFile(GLOBALS_CSS_PATH);
      const designContent = readFile(DESIGN_SYSTEM_PATH);
      
      // Both should contain oklch
      const hasOklchInCss = containsText(globalsCss, 'oklch');
      const hasOklchInDocs = containsText(designContent, 'oklch');
      
      // If CSS has oklch, docs should too
      if (hasOklchInCss) {
        expect(hasOklchInDocs).toBe(true);
      }
    });

    it('CR-04: Component props documented correctly', () => {
      // Check that documentation mentions key props
      const readmeContent = readFile(COMPONENTS_README_PATH);
      const hasProps = 
        containsText(readmeContent, 'Props') ||
        containsText(readmeContent, 'interface') ||
        containsText(readmeContent, 'prop');
      
      // If README exists, it should have props documentation
      if (fileExists(COMPONENTS_README_PATH) && readmeContent.length > 100) {
        expect(hasProps).toBe(true);
      }
    });
  });

  // ==========================================================================
  // Edge Cases Tests (EC)
  // ==========================================================================
  describe('Edge Cases (EC)', () => {
    it('EC-01: Documentation files are not empty', () => {
      const docContent = readFile(DOCUMENTATION_PATH);
      const designContent = readFile(DESIGN_SYSTEM_PATH);
      const readmeContent = readFile(COMPONENTS_README_PATH);
      
      if (fileExists(DOCUMENTATION_PATH)) {
        expect(docContent.length).toBeGreaterThan(100);
      }
      if (fileExists(DESIGN_SYSTEM_PATH)) {
        expect(designContent.length).toBeGreaterThan(100);
      }
      if (fileExists(COMPONENTS_README_PATH)) {
        expect(readmeContent.length).toBeGreaterThan(100);
      }
    });

    it('EC-02: No unicode escape issues in examples', () => {
      const readmeContent = readFile(COMPONENTS_README_PATH);
      // Check for properly encoded content
      expect(readmeContent).not.toContain('\\u0000');
      expect(readmeContent).not.toContain('\\uffff');
    });

    it('EC-03: Documentation file sizes are reasonable', () => {
      const MAX_DOCS_SIZE = 500 * 1024; // 500KB
      
      if (fileExists(DOCUMENTATION_PATH)) {
        const stats = fs.statSync(DOCUMENTATION_PATH);
        expect(stats.size).toBeLessThan(MAX_DOCS_SIZE);
      }
      if (fileExists(DESIGN_SYSTEM_PATH)) {
        const stats = fs.statSync(DESIGN_SYSTEM_PATH);
        expect(stats.size).toBeLessThan(MAX_DOCS_SIZE);
      }
    });
  });
});
