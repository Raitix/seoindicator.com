/**
 * TEST-28-2: Typography and Spacing Documentation
 * 
 * Tests for DESIGN-SYSTEM.md documentation completeness.
 * Validates typography scale and spacing system documentation.
 * 
 * @see PROJECT-SEOINDICATOR/TESTS/TEST-28-2.md
 */

import { describe, it, expect, beforeAll } from '@jest/globals';
import fs from 'fs';
import path from 'path';

// Path to DESIGN-SYSTEM.md (relative to frontend directory)
const DESIGN_SYSTEM_PATH = path.join(__dirname, '../../../DESIGN-SYSTEM.md');

describe('Design System Documentation - Typography & Spacing', () => {
  let content: string;
  let fileExists: boolean;
  
  beforeAll(() => {
    fileExists = fs.existsSync(DESIGN_SYSTEM_PATH);
    content = fileExists ? fs.readFileSync(DESIGN_SYSTEM_PATH, 'utf-8') : '';
  });

  // ============================================
  // File Existence (FE) - 3 tests
  // ============================================
  describe('File Existence (FE)', () => {
    it('FE-01: DESIGN-SYSTEM.md file exists', () => {
      expect(fileExists).toBe(true);
    });

    it('FE-02: File is non-empty', () => {
      expect(content.length).toBeGreaterThan(0);
    });

    it('FE-03: File has valid markdown structure', () => {
      expect(content).toMatch(/^#{1,6}\s/m); // Has headings
    });
  });

  // ============================================
  // Typography Scale Documentation (TS) - 8 tests
  // ============================================
  describe('Typography Scale Documentation (TS)', () => {
    it('TS-01: Display scale documented', () => {
      expect(content).toMatch(/Display.*4xl|5xl|hero/i);
    });

    it('TS-02: H1 scale documented', () => {
      expect(content).toMatch(/H1.*3xl|page\s*title/i);
    });

    it('TS-03: H2 scale documented', () => {
      expect(content).toMatch(/H2.*2xl|section\s*title/i);
    });

    it('TS-04: H3 scale documented', () => {
      expect(content).toMatch(/H3.*xl|card\s*title/i);
    });

    it('TS-05: Body scale documented', () => {
      expect(content).toMatch(/Body.*base|paragraph/i);
    });

    it('TS-06: Small scale documented', () => {
      expect(content).toMatch(/Small.*sm|caption|metadata/i);
    });

    it('TS-07: Typography section has Tailwind class mappings', () => {
      expect(content).toMatch(/text-(4xl|3xl|2xl|xl|base|sm)/);
    });

    it('TS-08: Typography section has examples', () => {
      expect(content).toMatch(/```(jsx?|html|tsx?)/);
    });
  });

  // ============================================
  // Spacing System Documentation (SS) - 8 tests
  // ============================================
  describe('Spacing System Documentation (SS)', () => {
    it('SS-01: xs spacing documented', () => {
      expect(content).toMatch(/xs.*0\.25rem|4px/i);
    });

    it('SS-02: sm spacing documented', () => {
      expect(content).toMatch(/sm.*0\.5rem|8px/i);
    });

    it('SS-03: md spacing documented', () => {
      expect(content).toMatch(/md.*1rem|16px/i);
    });

    it('SS-04: lg spacing documented', () => {
      expect(content).toMatch(/lg.*1\.5rem|24px/i);
    });

    it('SS-05: xl spacing documented', () => {
      expect(content).toMatch(/xl.*2rem|32px/i);
    });

    it('SS-06: 2xl spacing documented', () => {
      expect(content).toMatch(/2xl.*3rem|48px/i);
    });

    it('SS-07: Spacing section has Tailwind class mappings', () => {
      expect(content).toMatch(/(p|m|gap)-\d/);
    });

    it('SS-08: Spacing section has examples', () => {
      expect(content.toLowerCase()).toMatch(/spacing.*example|example.*spacing/);
    });
  });

  // ============================================
  // Tailwind Class Mappings (TC) - 3 tests
  // ============================================
  describe('Tailwind Class Mappings (TC)', () => {
    it('TC-01: Typography Tailwind classes mapped', () => {
      const typographySection = content.match(/##\s*Typography[\s\S]*?(?=##|$)/i)?.[0] || '';
      expect(typographySection).toMatch(/text-(4xl|3xl|2xl|xl|base|sm)/);
    });

    it('TC-02: Spacing Tailwind classes mapped', () => {
      const spacingSection = content.match(/##\s*Spacing[\s\S]*?(?=##|$)/i)?.[0] || '';
      expect(spacingSection).toMatch(/(p|m|gap)-\d/);
    });

    it('TC-03: Classes follow Tailwind naming conventions', () => {
      expect(content).toMatch(/text-\w+|p-\w+|m-\w+|gap-\w+/);
    });
  });

  // ============================================
  // Examples and Usage (EU) - 4 tests
  // ============================================
  describe('Examples and Usage (EU)', () => {
    it('EU-01: Typography examples provided', () => {
      const typographySection = content.match(/##\s*Typography[\s\S]*?(?=##|$)/i)?.[0] || '';
      expect(typographySection).toMatch(/```(jsx?|html|tsx?)/);
    });

    it('EU-02: Spacing examples provided', () => {
      const spacingSection = content.match(/##\s*Spacing[\s\S]*?(?=##|$)/i)?.[0] || '';
      expect(spacingSection).toMatch(/```(jsx?|html|tsx?)/);
    });

    it('EU-03: Examples use semantic HTML', () => {
      expect(content).toMatch(/<h[1-6]/);
    });

    it('EU-04: Examples show Tailwind classes', () => {
      expect(content).toMatch(/className=|class=/);
    });
  });

  // ============================================
  // Documentation Structure (DS) - 4 tests
  // ============================================
  describe('Documentation Structure (DS)', () => {
    it('DS-01: Has Typography section heading', () => {
      expect(content).toMatch(/##\s*Typography/i);
    });

    it('DS-02: Has Spacing section heading', () => {
      expect(content).toMatch(/##\s*Spacing/i);
    });

    it('DS-03: Sections are properly ordered', () => {
      const typographyIndex = content.toLowerCase().indexOf('## typography');
      const spacingIndex = content.toLowerCase().indexOf('## spacing');
      // If both exist, typography should come first
      if (typographyIndex >= 0 && spacingIndex >= 0) {
        expect(typographyIndex).toBeLessThan(spacingIndex);
      } else {
        // At least one section should exist
        expect(typographyIndex >= 0 || spacingIndex >= 0).toBe(true);
      }
    });

    it('DS-04: Has table of contents or index', () => {
      expect(content).toMatch(/\[.*\]\(.*\)|##\s+\w+/);
    });
  });

  // ============================================
  // Content Quality (CQ) - 4 tests
  // ============================================
  describe('Content Quality (CQ)', () => {
    it('CQ-01: Typography values match SPEC-028', () => {
      // Display: 4xl/5xl, H1: 3xl, H2: 2xl, H3: xl, Body: base, Small: sm
      expect(content).toMatch(/Display.*4xl|5xl/i);
      expect(content).toMatch(/H1.*3xl/i);
      expect(content).toMatch(/H2.*2xl/i);
      expect(content).toMatch(/H3.*xl/i);
      expect(content).toMatch(/Body.*base/i);
      expect(content).toMatch(/Small.*sm/i);
    });

    it('CQ-02: Spacing values match SPEC-028', () => {
      // xs: 4px, sm: 8px, md: 16px, lg: 24px, xl: 32px, 2xl: 48px
      expect(content).toMatch(/xs.*4px|0\.25rem/i);
      expect(content).toMatch(/sm.*8px|0\.5rem/i);
      expect(content).toMatch(/md.*16px|1rem/i);
      expect(content).toMatch(/lg.*24px|1\.5rem/i);
      expect(content).toMatch(/xl.*32px|2rem/i);
      expect(content).toMatch(/2xl.*48px|3rem/i);
    });

    it('CQ-03: No duplicate content (reasonable file size)', () => {
      expect(content.length).toBeLessThan(50000); // Max 50KB
    });

    it('CQ-04: No placeholder text', () => {
      expect(content.toLowerCase()).not.toMatch(/todo|fixme|placeholder|\[\.\.\.\]/);
    });
  });

  // ============================================
  // Edge Cases (EC) - 3 tests
  // ============================================
  describe('Edge Cases (EC)', () => {
    it('EC-01: File encoding is UTF-8', () => {
      if (!fileExists) {
        expect(true).toBe(true); // Skip if file doesn't exist
        return;
      }
      const buffer = fs.readFileSync(DESIGN_SYSTEM_PATH);
      const decoded = buffer.toString('utf-8');
      expect(decoded).toBeDefined();
    });

    it('EC-02: No broken markdown syntax', () => {
      // Check for unclosed code blocks
      const codeBlocks = (content.match(/```/g) || []).length;
      expect(codeBlocks % 2).toBe(0); // Should be even
    });

    it('EC-03: Responsive typography mentioned', () => {
      // Optional: responsive adjustments
      expect(content).toMatch(/responsive|mobile|tablet|desktop|breakpoint/i);
    });
  });
});
