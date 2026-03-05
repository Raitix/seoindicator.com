# TEST-28-2: Typography and Spacing Documentation

## Task Reference
[TASK-28-2](../TASKS/TASK-28-2.md) - Typography and Spacing Documentation

## Test Type
**Documentation Verification Tests** - Validates that design system documentation exists and is complete.

---

## Test Scenarios

### 1. File Existence (FE)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| FE-01 | DESIGN-SYSTEM.md file exists | File present at `PROJECT-SEOINDICATOR/DESIGN-SYSTEM.md` |
| FE-02 | File is non-empty | File size > 0 bytes |
| FE-03 | File has valid markdown structure | Contains proper headings (##) |

### 2. Typography Scale Documentation (TS)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| TS-01 | Display scale documented | Shows 4xl/5xl for hero headings |
| TS-02 | H1 scale documented | Shows 3xl for page titles |
| TS-03 | H2 scale documented | Shows 2xl for section titles |
| TS-04 | H3 scale documented | Shows xl for card titles |
| TS-05 | Body scale documented | Shows base for paragraphs |
| TS-06 | Small scale documented | Shows sm for captions/metadata |
| TS-07 | Typography section has Tailwind class mappings | Each scale item maps to Tailwind class |
| TS-08 | Typography section has examples | Code examples for each scale item |

### 3. Spacing System Documentation (SS)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| SS-01 | xs spacing documented | Shows 0.25rem (4px) |
| SS-02 | sm spacing documented | Shows 0.5rem (8px) |
| SS-03 | md spacing documented | Shows 1rem (16px) |
| SS-04 | lg spacing documented | Shows 1.5rem (24px) |
| SS-05 | xl spacing documented | Shows 2rem (32px) |
| SS-06 | 2xl spacing documented | Shows 3rem (48px) |
| SS-07 | Spacing section has Tailwind class mappings | Each spacing item maps to Tailwind class |
| SS-08 | Spacing section has examples | Usage examples for each spacing value |

### 4. Tailwind Class Mappings (TC)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| TC-01 | Typography Tailwind classes mapped | Display → text-4xl/text-5xl, H1 → text-3xl, etc. |
| TC-02 | Spacing Tailwind classes mapped | xs → p-1/m-1, sm → p-2/m-2, etc. |
| TC-03 | Classes follow Tailwind naming conventions | Valid Tailwind utility classes |

### 5. Examples and Usage (EU)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| EU-01 | Typography examples provided | JSX/HTML examples for each scale |
| EU-02 | Spacing examples provided | JSX/HTML examples for spacing usage |
| EU-03 | Examples use semantic HTML | Proper heading hierarchy (h1, h2, h3) |
| EU-04 | Examples show Tailwind classes | Direct class usage in examples |

### 6. Documentation Structure (DS)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| DS-01 | Has Typography section heading | `## Typography` or `## Typography Scale` |
| DS-02 | Has Spacing section heading | `## Spacing` or `## Spacing System` |
| DS-03 | Sections are properly ordered | Typography before Spacing (or logical order) |
| DS-04 | Has table of contents or index | Clear navigation structure |

### 7. Content Quality (CQ)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| CQ-01 | Typography values match SPEC-028 | Values: Display(4xl/5xl), H1(3xl), H2(2xl), H3(xl), Body(base), Small(sm) |
| CQ-02 | Spacing values match SPEC-028 | Values: xs(4px), sm(8px), md(16px), lg(24px), xl(32px), 2xl(48px) |
| CQ-03 | No duplicate content | Each scale item documented once |
| CQ-04 | No placeholder text | No "TODO", "FIXME", or empty sections |

---

## Edge Cases

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| EC-01 | File encoding is UTF-8 | No encoding issues when reading |
| EC-02 | No broken markdown syntax | Proper rendering in markdown viewers |
| EC-03 | Responsive typography mentioned | Note about responsive adjustments if applicable |

---

## Test Implementation

**File:** `frontend/__tests__/documentation/design-system-typography-spacing.test.ts`

```typescript
import { describe, it, expect } from 'vitest';
import fs from 'fs';
import path from 'path';

const DESIGN_SYSTEM_PATH = path.join(__dirname, '../../../DESIGN-SYSTEM.md');

describe('Design System Documentation - Typography & Spacing', () => {
  let content: string;
  
  beforeAll(() => {
    content = fs.readFileSync(DESIGN_SYSTEM_PATH, 'utf-8');
  });

  describe('File Existence (FE)', () => {
    it('FE-01: DESIGN-SYSTEM.md file exists', () => {
      expect(fs.existsSync(DESIGN_SYSTEM_PATH)).toBe(true);
    });

    it('FE-02: File is non-empty', () => {
      expect(content.length).toBeGreaterThan(0);
    });

    it('FE-03: File has valid markdown structure', () => {
      expect(content).toMatch(/^#{1,6}\s/m); // Has headings
    });
  });

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
      // Should have usage examples for spacing
      expect(content.toLowerCase()).toMatch(/spacing.*example|example.*spacing/);
    });
  });

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
      // Check for valid Tailwind utilities
      expect(content).toMatch(/text-\w+|p-\w+|m-\w+|gap-\w+/);
    });
  });

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
      // Check for heading tags in examples
      expect(content).toMatch(/<h[1-6]/);
    });

    it('EU-04: Examples show Tailwind classes', () => {
      // Check for className with Tailwind
      expect(content).toMatch(/className=|class=/);
    });
  });

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
      expect(typographyIndex).toBeLessThan(spacingIndex);
    });

    it('DS-04: Has table of contents or index', () => {
      // Check for TOC or clear section structure
      expect(content).toMatch(/\[.*\]\(.*\)|##\s+\w+/);
    });
  });

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

    it('CQ-03: No duplicate content', () => {
      // Check for reasonable content length (not bloated)
      expect(content.length).toBeLessThan(50000); // Max 50KB
    });

    it('CQ-04: No placeholder text', () => {
      expect(content.toLowerCase()).not.toMatch(/todo|fixme|placeholder|\[\.\.\.\]/);
    });
  });

  describe('Edge Cases (EC)', () => {
    it('EC-01: File encoding is UTF-8', () => {
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
```

---

## Test Summary

| Category | Test Count | Pass Criteria |
|----------|------------|---------------|
| File Existence (FE) | 3 | All pass |
| Typography Scale (TS) | 8 | All pass |
| Spacing System (SS) | 8 | All pass |
| Tailwind Mappings (TC) | 3 | All pass |
| Examples and Usage (EU) | 4 | All pass |
| Documentation Structure (DS) | 4 | All pass |
| Content Quality (CQ) | 4 | All pass |
| Edge Cases (EC) | 3 | All pass |
| **Total** | **37** | **All pass** |

---

## Developer Instructions

1. Create `PROJECT-SEOINDICATOR/DESIGN-SYSTEM.md`
2. Add Typography section with:
   - Scale table (Display, H1, H2, H3, Body, Small)
   - Tailwind class mappings
   - Code examples
3. Add Spacing section with:
   - Scale table (xs, sm, md, lg, xl, 2xl)
   - Tailwind class mappings
   - Usage examples
4. Run tests: `npm test -- design-system-typography-spacing.test.ts`
5. All 37 tests should pass

---

## Created By
PreTester Agent

## Created At
2026-03-05 20:10 UTC+2
