/**
 * Tests for CSS Variables Enhancement
 * Task: TASK-28-1 - CSS Variables Enhancement (Design System)
 *
 * These tests verify that brand colors, semantic colors, and surface layers
 * are properly defined in globals.css with valid oklch color space format.
 */

import * as fs from 'fs';
import * as path from 'path';

// ============================================================================
// CSS Parsing Utilities
// ============================================================================

interface CSSVariable {
  name: string;
  value: string;
  scope: 'root' | 'dark';
}

interface OKLCHValue {
  L: number;  // Lightness (0-1)
  C: number;  // Chroma (0+)
  H: number;  // Hue (0-360)
}

/**
 * Parse CSS file and extract CSS variables
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
 * Parse oklch value from CSS variable
 */
function parseOKLCH(value: string): OKLCHValue | null {
  // Match oklch(L C H) or oklch(L C H / alpha)
  const match = value.match(/oklch\s*\(\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)(?:\s*\/\s*[\d.]+)?\s*\)/i);
  if (match) {
    return {
      L: parseFloat(match[1]),
      C: parseFloat(match[2]),
      H: parseFloat(match[3]),
    };
  }
  return null;
}

/**
 * Check if value is valid oklch format
 */
function isValidOKLCH(value: string): boolean {
  return parseOKLCH(value) !== null;
}

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

// ============================================================================
// Brand Color Variables Tests
// ============================================================================

describe('Brand Color Variables', () => {
  let variables: CSSVariable[];
  let rootVariables: CSSVariable[];
  
  beforeAll(() => {
    const cssContent = getCSSContent();
    variables = parseCSSVariables(cssContent);
    rootVariables = variables.filter(v => v.scope === 'root');
  });

  describe('BC-001: --brand-primary exists', () => {
    it('should define --brand-primary in :root', () => {
      const brandPrimary = rootVariables.find(v => v.name === '--brand-primary');
      expect(brandPrimary).toBeDefined();
    });

    it('should use oklch format for --brand-primary', () => {
      const brandPrimary = rootVariables.find(v => v.name === '--brand-primary');
      expect(brandPrimary).toBeDefined();
      expect(isValidOKLCH(brandPrimary!.value)).toBe(true);
    });
  });

  describe('BC-002: --brand-secondary exists', () => {
    it('should define --brand-secondary in :root', () => {
      const brandSecondary = rootVariables.find(v => v.name === '--brand-secondary');
      expect(brandSecondary).toBeDefined();
    });

    it('should use oklch format for --brand-secondary', () => {
      const brandSecondary = rootVariables.find(v => v.name === '--brand-secondary');
      expect(brandSecondary).toBeDefined();
      expect(isValidOKLCH(brandSecondary!.value)).toBe(true);
    });
  });

  describe('BC-003: --brand-accent exists', () => {
    it('should define --brand-accent in :root', () => {
      const brandAccent = rootVariables.find(v => v.name === '--brand-accent');
      expect(brandAccent).toBeDefined();
    });

    it('should use oklch format for --brand-accent', () => {
      const brandAccent = rootVariables.find(v => v.name === '--brand-accent');
      expect(brandAccent).toBeDefined();
      expect(isValidOKLCH(brandAccent!.value)).toBe(true);
    });
  });

  describe('BC-004 to BC-006: Brand color hues', () => {
    it('BC-004: brand-primary should use emerald green hue (~160)', () => {
      const brandPrimary = rootVariables.find(v => v.name === '--brand-primary');
      expect(brandPrimary).toBeDefined();
      const oklch = parseOKLCH(brandPrimary!.value);
      expect(oklch).not.toBeNull();
      expect(oklch!.H).toBeGreaterThanOrEqual(140);
      expect(oklch!.H).toBeLessThanOrEqual(180);
    });

    it('BC-005: brand-secondary should use teal hue (~200)', () => {
      const brandSecondary = rootVariables.find(v => v.name === '--brand-secondary');
      expect(brandSecondary).toBeDefined();
      const oklch = parseOKLCH(brandSecondary!.value);
      expect(oklch).not.toBeNull();
      expect(oklch!.H).toBeGreaterThanOrEqual(180);
      expect(oklch!.H).toBeLessThanOrEqual(220);
    });

    it('BC-006: brand-accent should use orange hue (~50)', () => {
      const brandAccent = rootVariables.find(v => v.name === '--brand-accent');
      expect(brandAccent).toBeDefined();
      const oklch = parseOKLCH(brandAccent!.value);
      expect(oklch).not.toBeNull();
      expect(oklch!.H).toBeGreaterThanOrEqual(30);
      expect(oklch!.H).toBeLessThanOrEqual(70);
    });
  });
});

// ============================================================================
// Semantic Color Variables Tests
// ============================================================================

describe('Semantic Color Variables', () => {
  let variables: CSSVariable[];
  let rootVariables: CSSVariable[];
  
  beforeAll(() => {
    const cssContent = getCSSContent();
    variables = parseCSSVariables(cssContent);
    rootVariables = variables.filter(v => v.scope === 'root');
  });

  describe('SC-001: --success exists', () => {
    it('should define --success in :root', () => {
      const success = rootVariables.find(v => v.name === '--success');
      expect(success).toBeDefined();
    });

    it('should use oklch format for --success', () => {
      const success = rootVariables.find(v => v.name === '--success');
      expect(success).toBeDefined();
      expect(isValidOKLCH(success!.value)).toBe(true);
    });
  });

  describe('SC-002: --warning exists', () => {
    it('should define --warning in :root', () => {
      const warning = rootVariables.find(v => v.name === '--warning');
      expect(warning).toBeDefined();
    });

    it('should use oklch format for --warning', () => {
      const warning = rootVariables.find(v => v.name === '--warning');
      expect(warning).toBeDefined();
      expect(isValidOKLCH(warning!.value)).toBe(true);
    });
  });

  describe('SC-003: --error exists', () => {
    it('should define --error in :root', () => {
      const error = rootVariables.find(v => v.name === '--error');
      expect(error).toBeDefined();
    });

    it('should use oklch format for --error', () => {
      const error = rootVariables.find(v => v.name === '--error');
      expect(error).toBeDefined();
      expect(isValidOKLCH(error!.value)).toBe(true);
    });
  });

  describe('SC-004: --info exists', () => {
    it('should define --info in :root', () => {
      const info = rootVariables.find(v => v.name === '--info');
      expect(info).toBeDefined();
    });

    it('should use oklch format for --info', () => {
      const info = rootVariables.find(v => v.name === '--info');
      expect(info).toBeDefined();
      expect(isValidOKLCH(info!.value)).toBe(true);
    });
  });

  describe('SC-005 to SC-008: Semantic color hues', () => {
    it('SC-005: success should use green hue (~145)', () => {
      const success = rootVariables.find(v => v.name === '--success');
      expect(success).toBeDefined();
      const oklch = parseOKLCH(success!.value);
      expect(oklch).not.toBeNull();
      expect(oklch!.H).toBeGreaterThanOrEqual(120);
      expect(oklch!.H).toBeLessThanOrEqual(160);
    });

    it('SC-006: warning should use yellow/orange hue (~85)', () => {
      const warning = rootVariables.find(v => v.name === '--warning');
      expect(warning).toBeDefined();
      const oklch = parseOKLCH(warning!.value);
      expect(oklch).not.toBeNull();
      expect(oklch!.H).toBeGreaterThanOrEqual(60);
      expect(oklch!.H).toBeLessThanOrEqual(100);
    });

    it('SC-007: error should use red hue (~25)', () => {
      const error = rootVariables.find(v => v.name === '--error');
      expect(error).toBeDefined();
      const oklch = parseOKLCH(error!.value);
      expect(oklch).not.toBeNull();
      expect(oklch!.H).toBeGreaterThanOrEqual(0);
      expect(oklch!.H).toBeLessThanOrEqual(50);
    });

    it('SC-008: info should use blue hue (~230)', () => {
      const info = rootVariables.find(v => v.name === '--info');
      expect(info).toBeDefined();
      const oklch = parseOKLCH(info!.value);
      expect(oklch).not.toBeNull();
      expect(oklch!.H).toBeGreaterThanOrEqual(200);
      expect(oklch!.H).toBeLessThanOrEqual(260);
    });
  });
});

// ============================================================================
// Surface Layer Variables Tests
// ============================================================================

describe('Surface Layer Variables', () => {
  let variables: CSSVariable[];
  let rootVariables: CSSVariable[];
  
  beforeAll(() => {
    const cssContent = getCSSContent();
    variables = parseCSSVariables(cssContent);
    rootVariables = variables.filter(v => v.scope === 'root');
  });

  describe('SL-001 to SL-004: Surface variables exist', () => {
    it('SL-001: should define --surface-0 in :root', () => {
      const surface = rootVariables.find(v => v.name === '--surface-0');
      expect(surface).toBeDefined();
    });

    it('SL-002: should define --surface-1 in :root', () => {
      const surface = rootVariables.find(v => v.name === '--surface-1');
      expect(surface).toBeDefined();
    });

    it('SL-003: should define --surface-2 in :root', () => {
      const surface = rootVariables.find(v => v.name === '--surface-2');
      expect(surface).toBeDefined();
    });

    it('SL-004: should define --surface-3 in :root', () => {
      const surface = rootVariables.find(v => v.name === '--surface-3');
      expect(surface).toBeDefined();
    });
  });

  describe('SL-005: Surface layers progress in lightness', () => {
    it('surface-0 < surface-1 < surface-2 < surface-3 in lightness', () => {
      const surfaces = ['--surface-0', '--surface-1', '--surface-2', '--surface-3'];
      const lightnessValues: number[] = [];
      
      for (const name of surfaces) {
        const surface = rootVariables.find(v => v.name === name);
        expect(surface).toBeDefined();
        const oklch = parseOKLCH(surface!.value);
        expect(oklch).not.toBeNull();
        lightnessValues.push(oklch!.L);
      }
      
      // Verify ascending order
      for (let i = 0; i < lightnessValues.length - 1; i++) {
        expect(lightnessValues[i]).toBeLessThan(lightnessValues[i + 1]);
      }
    });
  });

  describe('SL-006: Surface layers use neutral chroma', () => {
    it('surface-0 should have chroma = 0 (neutral)', () => {
      const surface = rootVariables.find(v => v.name === '--surface-0');
      expect(surface).toBeDefined();
      const oklch = parseOKLCH(surface!.value);
      expect(oklch).not.toBeNull();
      expect(oklch!.C).toBe(0);
    });

    it('surface-1 should have chroma = 0 (neutral)', () => {
      const surface = rootVariables.find(v => v.name === '--surface-1');
      expect(surface).toBeDefined();
      const oklch = parseOKLCH(surface!.value);
      expect(oklch).not.toBeNull();
      expect(oklch!.C).toBe(0);
    });

    it('surface-2 should have chroma = 0 (neutral)', () => {
      const surface = rootVariables.find(v => v.name === '--surface-2');
      expect(surface).toBeDefined();
      const oklch = parseOKLCH(surface!.value);
      expect(oklch).not.toBeNull();
      expect(oklch!.C).toBe(0);
    });

    it('surface-3 should have chroma = 0 (neutral)', () => {
      const surface = rootVariables.find(v => v.name === '--surface-3');
      expect(surface).toBeDefined();
      const oklch = parseOKLCH(surface!.value);
      expect(oklch).not.toBeNull();
      expect(oklch!.C).toBe(0);
    });
  });
});

// ============================================================================
// oklch Format Validation Tests
// ============================================================================

describe('oklch Format Validation', () => {
  let variables: CSSVariable[];
  let rootVariables: CSSVariable[];
  
  beforeAll(() => {
    const cssContent = getCSSContent();
    variables = parseCSSVariables(cssContent);
    rootVariables = variables.filter(v => v.scope === 'root');
  });

  describe('OK-001 to OK-003: All new variables use oklch format', () => {
    const brandVariables = ['--brand-primary', '--brand-secondary', '--brand-accent'];
    const semanticVariables = ['--success', '--warning', '--error', '--info'];
    const surfaceVariables = ['--surface-0', '--surface-1', '--surface-2', '--surface-3'];

    it('OK-001: all brand colors use oklch format', () => {
      for (const name of brandVariables) {
        const variable = rootVariables.find(v => v.name === name);
        expect(variable).toBeDefined();
        expect(isValidOKLCH(variable!.value)).toBe(true);
      }
    });

    it('OK-002: all semantic colors use oklch format', () => {
      for (const name of semanticVariables) {
        const variable = rootVariables.find(v => v.name === name);
        expect(variable).toBeDefined();
        expect(isValidOKLCH(variable!.value)).toBe(true);
      }
    });

    it('OK-003: all surface layers use oklch format', () => {
      for (const name of surfaceVariables) {
        const variable = rootVariables.find(v => v.name === name);
        expect(variable).toBeDefined();
        expect(isValidOKLCH(variable!.value)).toBe(true);
      }
    });
  });

  describe('OK-004 to OK-006: oklch value ranges', () => {
    const allNewVariables = [
      '--brand-primary', '--brand-secondary', '--brand-accent',
      '--success', '--warning', '--error', '--info',
      '--surface-0', '--surface-1', '--surface-2', '--surface-3',
    ];

    it('OK-004: lightness values in valid range (0-1)', () => {
      for (const name of allNewVariables) {
        const variable = rootVariables.find(v => v.name === name);
        expect(variable).toBeDefined();
        const oklch = parseOKLCH(variable!.value);
        expect(oklch).not.toBeNull();
        expect(oklch!.L).toBeGreaterThanOrEqual(0);
        expect(oklch!.L).toBeLessThanOrEqual(1);
      }
    });

    it('OK-005: chroma values are non-negative', () => {
      for (const name of allNewVariables) {
        const variable = rootVariables.find(v => v.name === name);
        expect(variable).toBeDefined();
        const oklch = parseOKLCH(variable!.value);
        expect(oklch).not.toBeNull();
        expect(oklch!.C).toBeGreaterThanOrEqual(0);
      }
    });

    it('OK-006: hue values in valid range (0-360)', () => {
      for (const name of allNewVariables) {
        const variable = rootVariables.find(v => v.name === name);
        expect(variable).toBeDefined();
        const oklch = parseOKLCH(variable!.value);
        expect(oklch).not.toBeNull();
        expect(oklch!.H).toBeGreaterThanOrEqual(0);
        expect(oklch!.H).toBeLessThanOrEqual(360);
      }
    });
  });
});

// ============================================================================
// Dark Mode Support Tests
// ============================================================================

describe('Dark Mode Support', () => {
  let variables: CSSVariable[];
  let darkVariables: CSSVariable[];
  
  beforeAll(() => {
    const cssContent = getCSSContent();
    variables = parseCSSVariables(cssContent);
    darkVariables = variables.filter(v => v.scope === 'dark');
  });

  describe('DM-001: Brand colors defined in .dark', () => {
    it('should define --brand-primary in .dark', () => {
      const brandPrimary = darkVariables.find(v => v.name === '--brand-primary');
      expect(brandPrimary).toBeDefined();
    });

    it('should define --brand-secondary in .dark', () => {
      const brandSecondary = darkVariables.find(v => v.name === '--brand-secondary');
      expect(brandSecondary).toBeDefined();
    });

    it('should define --brand-accent in .dark', () => {
      const brandAccent = darkVariables.find(v => v.name === '--brand-accent');
      expect(brandAccent).toBeDefined();
    });
  });

  describe('DM-002: Semantic colors defined in .dark', () => {
    it('should define --success in .dark', () => {
      const success = darkVariables.find(v => v.name === '--success');
      expect(success).toBeDefined();
    });

    it('should define --warning in .dark', () => {
      const warning = darkVariables.find(v => v.name === '--warning');
      expect(warning).toBeDefined();
    });

    it('should define --error in .dark', () => {
      const error = darkVariables.find(v => v.name === '--error');
      expect(error).toBeDefined();
    });

    it('should define --info in .dark', () => {
      const info = darkVariables.find(v => v.name === '--info');
      expect(info).toBeDefined();
    });
  });

  describe('DM-003: Surface layers defined in .dark', () => {
    it('should define --surface-0 in .dark', () => {
      const surface = darkVariables.find(v => v.name === '--surface-0');
      expect(surface).toBeDefined();
    });

    it('should define --surface-1 in .dark', () => {
      const surface = darkVariables.find(v => v.name === '--surface-1');
      expect(surface).toBeDefined();
    });

    it('should define --surface-2 in .dark', () => {
      const surface = darkVariables.find(v => v.name === '--surface-2');
      expect(surface).toBeDefined();
    });

    it('should define --surface-3 in .dark', () => {
      const surface = darkVariables.find(v => v.name === '--surface-3');
      expect(surface).toBeDefined();
    });
  });

  describe('DM-004: Dark mode surfaces progress correctly', () => {
    it('surface layers maintain ascending lightness order in dark mode', () => {
      const surfaces = ['--surface-0', '--surface-1', '--surface-2', '--surface-3'];
      const lightnessValues: number[] = [];
      
      for (const name of surfaces) {
        const surface = darkVariables.find(v => v.name === name);
        expect(surface).toBeDefined();
        const oklch = parseOKLCH(surface!.value);
        expect(oklch).not.toBeNull();
        lightnessValues.push(oklch!.L);
      }
      
      // Verify ascending order (dark mode surfaces should still be ordered)
      for (let i = 0; i < lightnessValues.length - 1; i++) {
        expect(lightnessValues[i]).toBeLessThan(lightnessValues[i + 1]);
      }
    });
  });
});

// ============================================================================
// Tailwind Integration Tests
// ============================================================================

describe('Tailwind Integration', () => {
  let cssContent: string;
  
  beforeAll(() => {
    cssContent = getCSSContent();
  });

  describe('TW-001 to TW-004: Variables in @theme block', () => {
    it('TW-001: @theme block should exist', () => {
      expect(cssContent).toContain('@theme inline');
    });

    it('TW-002: brand-primary should be mapped in @theme', () => {
      const themeMatch = cssContent.match(/@theme\s+inline\s*\{([^}]+)\}/);
      expect(themeMatch).not.toBeNull();
      expect(themeMatch![1]).toContain('--color-brand-primary');
    });

    it('TW-003: success should be mapped in @theme', () => {
      const themeMatch = cssContent.match(/@theme\s+inline\s*\{([^}]+)\}/);
      expect(themeMatch).not.toBeNull();
      expect(themeMatch![1]).toContain('--color-success');
    });

    it('TW-004: surface-0 should be mapped in @theme', () => {
      const themeMatch = cssContent.match(/@theme\s+inline\s*\{([^}]+)\}/);
      expect(themeMatch).not.toBeNull();
      expect(themeMatch![1]).toContain('--color-surface-0');
    });
  });
});

// ============================================================================
// Edge Cases Tests
// ============================================================================

describe('Edge Cases', () => {
  let variables: CSSVariable[];
  let rootVariables: CSSVariable[];
  let cssContent: string;
  
  beforeAll(() => {
    cssContent = getCSSContent();
    variables = parseCSSVariables(cssContent);
    rootVariables = variables.filter(v => v.scope === 'root');
  });

  describe('EC-001: No duplicate variable names', () => {
    it('should not have duplicate variable names in :root', () => {
      const names = rootVariables.map(v => v.name);
      const uniqueNames = new Set(names);
      expect(names.length).toBe(uniqueNames.size);
    });
  });

  describe('EC-002: Variables not using deprecated formats', () => {
    const newVariables = [
      '--brand-primary', '--brand-secondary', '--brand-accent',
      '--success', '--warning', '--error', '--info',
      '--surface-0', '--surface-1', '--surface-2', '--surface-3',
    ];

    it('should not use rgb() format for new variables', () => {
      for (const name of newVariables) {
        const variable = rootVariables.find(v => v.name === name);
        if (variable) {
          expect(variable.value.toLowerCase()).not.toMatch(/^rgb\s*\(/);
        }
      }
    });

    it('should not use hsl() format for new variables', () => {
      for (const name of newVariables) {
        const variable = rootVariables.find(v => v.name === name);
        if (variable) {
          expect(variable.value.toLowerCase()).not.toMatch(/^hsl\s*\(/);
        }
      }
    });

    it('should not use hex format for new variables', () => {
      for (const name of newVariables) {
        const variable = rootVariables.find(v => v.name === name);
        if (variable) {
          expect(variable.value).not.toMatch(/^#[0-9a-fA-F]+$/);
        }
      }
    });
  });

  describe('EC-003: No syntax errors in CSS', () => {
    it('CSS file should be parseable (basic check)', () => {
      // Basic syntax checks
      expect(() => parseCSSVariables(cssContent)).not.toThrow();
      
      // Check for matching braces in :root
      const rootMatch = cssContent.match(/:root\s*\{([^}]*)\}/);
      expect(rootMatch).not.toBeNull();
      
      // Check for matching braces in .dark
      const darkMatch = cssContent.match(/\.dark\s*\{([^}]*)\}/);
      expect(darkMatch).not.toBeNull();
    });
  });
});

// ============================================================================
// Acceptance Criteria Summary
// ============================================================================

describe('Acceptance Criteria Summary', () => {
  let variables: CSSVariable[];
  let rootVariables: CSSVariable[];
  let darkVariables: CSSVariable[];
  let cssContent: string;
  
  beforeAll(() => {
    cssContent = getCSSContent();
    variables = parseCSSVariables(cssContent);
    rootVariables = variables.filter(v => v.scope === 'root');
    darkVariables = variables.filter(v => v.scope === 'dark');
  });

  it('AC-1: Add brand color variables (primary, secondary, accent)', () => {
    const brandPrimary = rootVariables.find(v => v.name === '--brand-primary');
    const brandSecondary = rootVariables.find(v => v.name === '--brand-secondary');
    const brandAccent = rootVariables.find(v => v.name === '--brand-accent');
    
    expect(brandPrimary).toBeDefined();
    expect(brandSecondary).toBeDefined();
    expect(brandAccent).toBeDefined();
    
    expect(isValidOKLCH(brandPrimary!.value)).toBe(true);
    expect(isValidOKLCH(brandSecondary!.value)).toBe(true);
    expect(isValidOKLCH(brandAccent!.value)).toBe(true);
  });

  it('AC-2: Add semantic color variables (success, warning, error, info)', () => {
    const success = rootVariables.find(v => v.name === '--success');
    const warning = rootVariables.find(v => v.name === '--warning');
    const error = rootVariables.find(v => v.name === '--error');
    const info = rootVariables.find(v => v.name === '--info');
    
    expect(success).toBeDefined();
    expect(warning).toBeDefined();
    expect(error).toBeDefined();
    expect(info).toBeDefined();
    
    expect(isValidOKLCH(success!.value)).toBe(true);
    expect(isValidOKLCH(warning!.value)).toBe(true);
    expect(isValidOKLCH(error!.value)).toBe(true);
    expect(isValidOKLCH(info!.value)).toBe(true);
  });

  it('AC-3: Add surface layer variables (surface-0 through surface-3)', () => {
    const surface0 = rootVariables.find(v => v.name === '--surface-0');
    const surface1 = rootVariables.find(v => v.name === '--surface-1');
    const surface2 = rootVariables.find(v => v.name === '--surface-2');
    const surface3 = rootVariables.find(v => v.name === '--surface-3');
    
    expect(surface0).toBeDefined();
    expect(surface1).toBeDefined();
    expect(surface2).toBeDefined();
    expect(surface3).toBeDefined();
    
    expect(isValidOKLCH(surface0!.value)).toBe(true);
    expect(isValidOKLCH(surface1!.value)).toBe(true);
    expect(isValidOKLCH(surface2!.value)).toBe(true);
    expect(isValidOKLCH(surface3!.value)).toBe(true);
  });

  it('AC-4: Variables use oklch color space for consistency', () => {
    const allNewVariables = [
      '--brand-primary', '--brand-secondary', '--brand-accent',
      '--success', '--warning', '--error', '--info',
      '--surface-0', '--surface-1', '--surface-2', '--surface-3',
    ];
    
    for (const name of allNewVariables) {
      const variable = rootVariables.find(v => v.name === name);
      expect(variable).toBeDefined();
      expect(isValidOKLCH(variable!.value)).toBe(true);
    }
  });

  it('AC-5: Variables integrate with existing Tailwind CSS theme', () => {
    const themeMatch = cssContent.match(/@theme\s+inline\s*\{([^}]+)\}/);
    expect(themeMatch).not.toBeNull();
    
    // Check that at least one new variable is mapped
    expect(
      themeMatch![1].includes('--color-brand-primary') ||
      themeMatch![1].includes('--color-success') ||
      themeMatch![1].includes('--color-surface-0')
    ).toBe(true);
  });

  it('AC-6: Dark mode values properly defined', () => {
    const brandPrimary = darkVariables.find(v => v.name === '--brand-primary');
    const success = darkVariables.find(v => v.name === '--success');
    const surface0 = darkVariables.find(v => v.name === '--surface-0');
    
    expect(brandPrimary).toBeDefined();
    expect(success).toBeDefined();
    expect(surface0).toBeDefined();
    
    expect(isValidOKLCH(brandPrimary!.value)).toBe(true);
    expect(isValidOKLCH(success!.value)).toBe(true);
    expect(isValidOKLCH(surface0!.value)).toBe(true);
  });
});
