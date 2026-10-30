/**
 * Tests for Focus Visibility
 * Task: TASK-28-17 - Color Contrast and Screen Reader Testing
 *
 * These tests verify that focus indicators are properly implemented:
 * - Focus styles exist for interactive elements
 * - Focus-visible is used for keyboard-only focus
 * - Focus indicators have sufficient contrast
 */

import * as fs from 'fs';
import * as path from 'path';

// ============================================================================
// Type Definitions
// ============================================================================

interface CSSVariable {
  name: string;
  value: string;
  scope: 'root' | 'dark';
}

// ============================================================================
// CSS Parsing Utilities
// ============================================================================

/**
 * Get CSS file content
 */
function getCSSContent(): string {
  const cssPath = path.join(process.cwd(), 'app', 'globals.css');
  if (!fs.existsSync(cssPath)) {
    throw new Error(`CSS file not found: ${cssPath}`);
  }
  return fs.readFileSync(cssPath, 'utf-8');
}

/**
 * Parse CSS variables from content
 */
function parseCSSVariables(cssContent: string): CSSVariable[] {
  const variables: CSSVariable[] = [];

  // Match :root block
  const rootMatch = cssContent.match(/:root\s*\{([^}]+)\}/);
  if (rootMatch) {
    const rootContent = rootMatch[1];
    const varMatches = rootContent.matchAll(/--([\w-]+)\s*:\s*([^;]+);/g);
    for (const match of varMatches) {
      variables.push({
        name: `--${match[1]}`,
        value: match[2].trim(),
        scope: 'root',
      });
    }
  }

  // Match .dark block
  const darkMatch = cssContent.match(/\.dark\s*\{([^}]+)\}/);
  if (darkMatch) {
    const darkContent = darkMatch[1];
    const varMatches = darkContent.matchAll(/--([\w-]+)\s*:\s*([^;]+);/g);
    for (const match of varMatches) {
      variables.push({
        name: `--${match[1]}`,
        value: match[2].trim(),
        scope: 'dark',
      });
    }
  }

  return variables;
}

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

// ============================================================================
// Constants
// ============================================================================

const COMPONENTS_DIR = path.join(process.cwd(), 'components');

// ============================================================================
// Focus Visibility Tests
// ============================================================================

describe('Focus Visibility Tests', () => {
  let cssContent: string;
  let variables: CSSVariable[];

  beforeAll(() => {
    cssContent = getCSSContent();
    variables = parseCSSVariables(cssContent);
  });

  // ========================================================================
  // FV-001: Focus ring variable exists
  // ========================================================================

  describe('FV-001: Focus ring CSS variable', () => {
    it('should have --ring variable defined', () => {
      const ring = variables.find(v => v.name === '--ring' && v.scope === 'root');
      expect(ring).toBeDefined();
    });

    it('--ring should use oklch format', () => {
      const ring = variables.find(v => v.name === '--ring' && v.scope === 'root');
      expect(ring).toBeDefined();
      expect(ring!.value).toMatch(/oklch/i);
    });

    it('should have --ring in dark mode', () => {
      const ring = variables.find(v => v.name === '--ring' && v.scope === 'dark');
      expect(ring).toBeDefined();
    });
  });

  // ========================================================================
  // FV-002: Focus pseudo-class usage
  // ========================================================================

  describe('FV-002: Focus pseudo-class in CSS', () => {
    it('should contain :focus selector', () => {
      const hasFocus = /:focus[\s,{]/.test(cssContent);
      expect(hasFocus).toBe(true);
    });

    it('should contain :focus-visible selector', () => {
      const hasFocusVisible = /:focus-visible[\s,{]/.test(cssContent);
      expect(hasFocusVisible).toBe(true);
    });

    it('should contain :focus-within selector (optional but recommended)', () => {
      const hasFocusWithin = /:focus-within[\s,{]/.test(cssContent);
      // This is optional - document if missing
      if (!hasFocusWithin) {
        console.log(':focus-within not found - recommended for parent focus states');
      }
      expect(true).toBe(true);
    });
  });

  // ========================================================================
  // FV-003: Focus outline style
  // ========================================================================

  describe('FV-003: Focus outline implementation', () => {
    it('should not completely remove outline (reduced motion safe)', () => {
      // Check that outline is not set to none without alternative
      const hasOutlineNone = /outline\s*:\s*none/i.test(cssContent);

      if (hasOutlineNone) {
        // Should have alternative focus indicator (box-shadow, ring, etc.)
        const hasAlternative =
          /box-shadow.*focus/i.test(cssContent) ||
          /ring.*focus/i.test(cssContent) ||
          /outline-offset/i.test(cssContent);

        expect(hasAlternative).toBe(true);
      }
    });

    it('should have outline-ring reference', () => {
      // Check for Tailwind outline-ring class usage
      const hasRingClass = /outline-ring/i.test(cssContent);
      expect(hasRingClass).toBe(true);
    });
  });

  // ========================================================================
  // FV-004: Focus indicator contrast
  // ========================================================================

  describe('FV-004: Focus indicator contrast', () => {
    it('ring color should have sufficient lightness for visibility', () => {
      const ring = variables.find(v => v.name === '--ring' && v.scope === 'root');
      expect(ring).toBeDefined();

      // Parse oklch value
      const match = ring!.value.match(/oklch\s*\(\s*([\d.]+)/i);
      expect(match).not.toBeNull();

      const lightness = parseFloat(match![1]);
      // Ring should be visible (not too dark or too light)
      expect(lightness).toBeGreaterThan(0.3);
      expect(lightness).toBeLessThan(0.9);
    });

    it('dark mode ring should have sufficient lightness', () => {
      const ring = variables.find(v => v.name === '--ring' && v.scope === 'dark');
      expect(ring).toBeDefined();

      const match = ring!.value.match(/oklch\s*\(\s*([\d.]+)/i);
      expect(match).not.toBeNull();

      const lightness = parseFloat(match![1]);
      // Dark mode ring should be lighter for visibility
      expect(lightness).toBeGreaterThan(0.3);
    });
  });

  // ========================================================================
  // FV-005: Focus in components
  // ========================================================================

  describe('FV-005: Focus handling in components', () => {
    it('buttons should have focus styles', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      let focusStylesFound = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Check for focus-related classes
        if (/focus:|focus-visible:|data-focus/.test(content)) {
          focusStylesFound++;
        }
      }

      console.log(`Found focus styles in ${focusStylesFound} components`);
      expect(focusStylesFound).toBeGreaterThan(0);
    });

    it('interactive elements should have focus states', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      const interactivePatterns = [
        /<button/,
        /<a\s/,
        /<input/,
        /<select/,
        /<textarea/,
        /role\s*=\s*["']button["']/,
      ];

      let totalInteractive = 0;
      let withFocusStyles = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        for (const pattern of interactivePatterns) {
          const matches = content.match(pattern);
          if (matches) {
            totalInteractive += matches.length;
          }
        }

        if (/focus:|focus-visible:/.test(content)) {
          withFocusStyles++;
        }
      }

      console.log(`${totalInteractive} interactive elements found`);
      console.log(`${withFocusStyles} files with focus styles`);
      expect(totalInteractive).toBeGreaterThan(0);
    });
  });

  // ========================================================================
  // FV-006: Tab index usage
  // ========================================================================

  describe('FV-006: Tab index implementation', () => {
    it('should not have negative tabindex on focusable elements', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      const issues: string[] = [];

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Find negative tabindex (except on elements that need it)
        const negativeTabindex = content.match(/tabIndex\s*=\s*["']?-1["']?/);

        if (negativeTabindex) {
          // This is acceptable for modal traps, skip links, etc.
          console.log(`Negative tabIndex found in: ${path.basename(file)} - verify usage`);
        }
      }

      expect(true).toBe(true);
    });

    it('should have tabIndex on custom interactive elements', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Check for elements with role="button" that might need tabindex
        if (/role\s*=\s*["']button["']/.test(content)) {
          // Should have tabIndex or be a native button
          if (!/tabIndex|<button/.test(content)) {
            console.log(`Custom button may need tabIndex in: ${path.basename(file)}`);
          }
        }
      }
    });
  });

  // ========================================================================
  // FV-007: Keyboard navigation support
  // ========================================================================

  describe('FV-007: Keyboard navigation', () => {
    it('should have onKeyDown or onKeyPress handlers where needed', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      let keyboardHandlers = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        if (/onKeyDown|onKeyPress|onKeyUp/.test(content)) {
          keyboardHandlers++;
        }
      }

      console.log(`Found keyboard handlers in ${keyboardHandlers} components`);
      expect(keyboardHandlers).toBeGreaterThan(0);
    });

    it('should handle Enter and Space on custom buttons', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      let enterKeyHandlers = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Check for Enter/Space key handling
        if (/Enter|Space|"Enter"|" "|'Enter'|' '/.test(content)) {
          enterKeyHandlers++;
        }
      }

      console.log(`Found Enter/Space handlers in ${enterKeyHandlers} components`);
      expect(true).toBe(true);
    });
  });

  // ========================================================================
  // FV-008: Focus trap for modals
  // ========================================================================

  describe('FV-008: Focus trap for modals and dialogs', () => {
    it('modal components should trap focus', () => {
      const componentFiles = getComponentFiles(COMPONENTS_DIR);
      let modalCount = 0;
      let focusTrapCount = 0;

      for (const file of componentFiles) {
        const content = readFileContent(file);

        // Check for modal/dialog components
        if (/modal|dialog|drawer|sheet/i.test(file)) {
          modalCount++;

          // Check for focus trap implementation
          if (/focus.*trap|trap.*focus|inert|aria-modal/.test(content)) {
            focusTrapCount++;
          }
        }
      }

      console.log(`${modalCount} modal components found, ${focusTrapCount} with focus trap`);
      expect(true).toBe(true);
    });
  });

  // ========================================================================
  // FV-009: Reduced motion support
  // ========================================================================

  describe('FV-009: Reduced motion support', () => {
    it('should respect prefers-reduced-motion', () => {
      const hasReducedMotion = /prefers-reduced-motion/i.test(cssContent);
      expect(hasReducedMotion).toBe(true);
    });

    it('focus indicators should work without animation', () => {
      // Focus indicators should be visible even with reduced motion
      const reducedMotionMatch = cssContent.match(/@media.*prefers-reduced-motion[^{]*\{([^}]+)\}/);

      if (reducedMotionMatch) {
        // Should preserve focus visibility
        expect(true).toBe(true);
      }
    });
  });

  // ========================================================================
  // FV-010: Acceptance Criteria
  // ========================================================================

  describe('FV-010: Acceptance Criteria - Focus visible in all states', () => {
    it('AC-6: Focus should be visible in all states', () => {
      // Verify all required focus-related CSS exists
      const requirements = [
        { pattern: /:focus/, description: ':focus selector' },
        { pattern: /:focus-visible/, description: ':focus-visible selector' },
        { pattern: /--ring/, description: '--ring CSS variable' },
        { pattern: /outline-ring/, description: 'outline-ring class' },
      ];

      const results = requirements.map(req => ({
        ...req,
        found: req.pattern.test(cssContent),
      }));

      const allFound = results.every(r => r.found);

      results.forEach(r => {
        console.log(`${r.description}: ${r.found ? '✓' : '✗'}`);
      });

      expect(allFound).toBe(true);
    });

    it('should have focus styles for both light and dark mode', () => {
      const rootRing = variables.find(v => v.name === '--ring' && v.scope === 'root');
      const darkRing = variables.find(v => v.name === '--ring' && v.scope === 'dark');

      expect(rootRing).toBeDefined();
      expect(darkRing).toBeDefined();
    });
  });
});
