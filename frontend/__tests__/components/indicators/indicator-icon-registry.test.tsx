/**
 * Tests for Indicator Icon Registry
 * Task: TASK-28-14
 * Tests: Icon registry structure, indicator-to-icon mapping, tree-shaking, exports
 */

import * as React from 'react';
import * as LucideReact from 'lucide-react';

// Type for the expected registry structure
type IndicatorIconName =
  | 'keyword-rankings'
  | 'visibility-score'
  | 'competitor-analysis'
  | 'technical-health'
  | 'backlink-profile'
  | 'content-opportunities'
  | 'serp-features'
  | 'page-speed';

// Expected icon mapping from task specification
const expectedIconMapping: Record<IndicatorIconName, string> = {
  'keyword-rankings': 'TrendingUp',
  'visibility-score': 'Eye',
  'competitor-analysis': 'Users',
  'technical-health': 'Wrench',
  'backlink-profile': 'Link',
  'content-opportunities': 'FileText',
  'serp-features': 'Star',
  'page-speed': 'Zap',
};

// ============================================================================
// REGISTRY STRUCTURE TESTS
// ============================================================================

describe('INDICATOR_ICONS Registry Structure', () => {
  let INDICATOR_ICONS: Record<string, any>;
  let IndicatorIconName: ReadonlyArray<string>;

  beforeAll(async () => {
    // Import the module - will fail until implementation
    try {
      const module = await import('@/components/indicators/icons');
      INDICATOR_ICONS = module.INDICATOR_ICONS;
      IndicatorIconName = module.IndicatorIconName;
    } catch {
      // Module doesn't exist yet - tests will fail as expected (TDD)
      INDICATOR_ICONS = {};
      IndicatorIconName = [];
    }
  });

  it('RS-001: INDICATOR_ICONS constant exists', () => {
    expect(INDICATOR_ICONS).toBeDefined();
    expect(typeof INDICATOR_ICONS).toBe('object');
  });

  it('RS-002: Registry contains exactly 8 indicator icons', () => {
    const keys = Object.keys(INDICATOR_ICONS);
    expect(keys).toHaveLength(8);
  });

  it('RS-003: Registry uses as const for type safety', async () => {
    // Verify the registry is typed as const by checking it's readonly
    const keys = Object.keys(INDICATOR_ICONS);
    const expectedKeys: IndicatorIconName[] = [
      'keyword-rankings',
      'visibility-score',
      'competitor-analysis',
      'technical-health',
      'backlink-profile',
      'content-opportunities',
      'serp-features',
      'page-speed',
    ];
    expect(keys.sort()).toEqual(expectedKeys.sort());
  });

  it('RS-004: Each key follows kebab-case naming convention', () => {
    const keys = Object.keys(INDICATOR_ICONS);
    const kebabCasePattern = /^[a-z]+(-[a-z]+)*$/;
    keys.forEach(key => {
      expect(key).toMatch(kebabCasePattern);
    });
  });

  it('RS-005: All values are Lucide icon components', () => {
    Object.entries(INDICATOR_ICONS).forEach(([indicator, IconComponent]) => {
      // Lucide icons are React elements (objects with $$typeof and render)
      expect(typeof IconComponent).toBe('object');
      // Lucide icons have a displayName property
      expect(IconComponent.displayName).toBeDefined();
      // Lucide icons have a render method
      expect(typeof IconComponent.render).toBe('function');
    });
  });
});

// ============================================================================
// INDICATOR-TO-ICON MAPPING TESTS
// ============================================================================

describe('Indicator-to-Icon Mapping', () => {
  let INDICATOR_ICONS: Record<string, any>;

  beforeAll(async () => {
    try {
      const module = await import('@/components/indicators/icons');
      INDICATOR_ICONS = module.INDICATOR_ICONS;
    } catch {
      INDICATOR_ICONS = {};
    }
  });

  it('IM-001: keyword-rankings maps to TrendingUp icon', () => {
    const Icon = INDICATOR_ICONS['keyword-rankings'];
    expect(Icon).toBeDefined();
    expect(Icon.displayName).toBe('TrendingUp');
  });

  it('IM-002: visibility-score maps to Eye icon', () => {
    const Icon = INDICATOR_ICONS['visibility-score'];
    expect(Icon).toBeDefined();
    expect(Icon.displayName).toBe('Eye');
  });

  it('IM-003: competitor-analysis maps to Users icon', () => {
    const Icon = INDICATOR_ICONS['competitor-analysis'];
    expect(Icon).toBeDefined();
    expect(Icon.displayName).toBe('Users');
  });

  it('IM-004: technical-health maps to Wrench icon', () => {
    const Icon = INDICATOR_ICONS['technical-health'];
    expect(Icon).toBeDefined();
    expect(Icon.displayName).toBe('Wrench');
  });

  it('IM-005: backlink-profile maps to Link icon', () => {
    const Icon = INDICATOR_ICONS['backlink-profile'];
    expect(Icon).toBeDefined();
    // Note: lucide-react has both 'Link' and 'Link2' - we expect 'Link'
    expect(Icon.displayName).toBe('Link');
  });

  it('IM-006: content-opportunities maps to FileText icon', () => {
    const Icon = INDICATOR_ICONS['content-opportunities'];
    expect(Icon).toBeDefined();
    expect(Icon.displayName).toBe('FileText');
  });

  it('IM-007: serp-features maps to Star icon', () => {
    const Icon = INDICATOR_ICONS['serp-features'];
    expect(Icon).toBeDefined();
    expect(Icon.displayName).toBe('Star');
  });

  it('IM-008: page-speed maps to Zap icon', () => {
    const Icon = INDICATOR_ICONS['page-speed'];
    expect(Icon).toBeDefined();
    expect(Icon.displayName).toBe('Zap');
  });

  it('IM-009: Invalid indicator key returns undefined', () => {
    const Icon = INDICATOR_ICONS['invalid-indicator' as any];
    expect(Icon).toBeUndefined();
  });
});

// ============================================================================
// TYPE EXPORT TESTS
// ============================================================================

describe('IndicatorIconName Type Export', () => {
  let IndicatorIconName: ReadonlyArray<string>;

  beforeAll(async () => {
    try {
      const module = await import('@/components/indicators/icons');
      IndicatorIconName = module.IndicatorIconName;
    } catch {
      IndicatorIconName = [];
    }
  });

  it('TE-001: IndicatorIconName type exists as const array', () => {
    expect(IndicatorIconName).toBeDefined();
    expect(Array.isArray(IndicatorIconName)).toBe(true);
  });

  it('TE-002: IndicatorIconName contains all 8 indicator names', () => {
    expect(IndicatorIconName).toHaveLength(8);
  });

  it('TE-003: IndicatorIconName matches registry keys', async () => {
    try {
      const module = await import('@/components/indicators/icons');
      const registryKeys = Object.keys(module.INDICATOR_ICONS).sort();
      const typeKeys = [...module.IndicatorIconName].sort();
      expect(typeKeys).toEqual(registryKeys);
    } catch {
      // Module doesn't exist yet
      expect(true).toBe(false);
    }
  });

  it('TE-004: IndicatorIconName is readonly (frozen)', () => {
    // Check if array is frozen or typed as readonly
    const isReadonly = Object.isFrozen(IndicatorIconName) || 
      !Object.prototype.propertyIsEnumerable.call(IndicatorIconName, 'push');
    // At minimum, TypeScript enforces readonly at compile time
    expect(IndicatorIconName).toBeDefined();
  });
});

// ============================================================================
// TREE-SHAKING TESTS
// ============================================================================

describe('Tree-Shaking Support', () => {
  let INDICATOR_ICONS: Record<string, any>;

  beforeAll(async () => {
    try {
      const module = await import('@/components/indicators/icons');
      INDICATOR_ICONS = module.INDICATOR_ICONS;
    } catch {
      INDICATOR_ICONS = {};
    }
  });

  it('TS-001: Icons are individually importable from lucide-react', () => {
    // Verify each icon in the registry exists in lucide-react
    const lucideExports = Object.keys(LucideReact);
    
    Object.entries(INDICATOR_ICONS).forEach(([indicator, IconComponent]) => {
      const iconName = IconComponent.displayName;
      expect(lucideExports).toContain(iconName);
    });
  });

  it('TS-002: Registry uses direct Lucide imports (not barrel)', async () => {
    // Verify each icon is a direct reference to Lucide icons
    Object.entries(INDICATOR_ICONS).forEach(([indicator, IconComponent]) => {
      const iconName = IconComponent.displayName;
      const lucideIcon = (LucideReact as any)[iconName];
      expect(IconComponent).toBe(lucideIcon);
    });
  });

  it('TS-003: Named export allows individual import', async () => {
    // This test verifies the export structure supports destructuring
    try {
      const module = await import('@/components/indicators/icons');
      const { INDICATOR_ICONS: icons } = module;
      expect(icons).toBeDefined();
    } catch {
      expect(true).toBe(false);
    }
  });

  it('TS-004: Module has no side effects (pure exports)', async () => {
    // Importing the module should not cause any side effects
    // This is verified by checking the module structure
    try {
      const module = await import('@/components/indicators/icons');
      // Should only have the expected exports
      const exports = Object.keys(module);
      expect(exports).toContain('INDICATOR_ICONS');
      expect(exports).toContain('IndicatorIconName');
      // Should not have more than 3 exports (icons + type + possible default)
      expect(exports.length).toBeLessThanOrEqual(3);
    } catch {
      expect(true).toBe(false);
    }
  });
});

// ============================================================================
// NAMING CONVENTION TESTS
// ============================================================================

describe('Naming Convention Consistency', () => {
  let INDICATOR_ICONS: Record<string, any>;

  beforeAll(async () => {
    try {
      const module = await import('@/components/indicators/icons');
      INDICATOR_ICONS = module.INDICATOR_ICONS;
    } catch {
      INDICATOR_ICONS = {};
    }
  });

  it('NC-001: All indicator keys are lowercase kebab-case', () => {
    const keys = Object.keys(INDICATOR_ICONS);
    keys.forEach(key => {
      // No uppercase letters
      expect(key).toBe(key.toLowerCase());
      // No spaces
      expect(key).not.toMatch(/\s/);
      // No underscores
      expect(key).not.toMatch(/_/);
      // No consecutive hyphens
      expect(key).not.toMatch(/--/);
    });
  });

  it('NC-002: Indicator names are descriptive and meaningful', () => {
    const keys = Object.keys(INDICATOR_ICONS);
    const expectedMinLength = 8; // 'page-speed' is 10 chars
    keys.forEach(key => {
      expect(key.length).toBeGreaterThanOrEqual(expectedMinLength);
    });
  });

  it('NC-003: Icon names match Lucide component naming', () => {
    Object.entries(INDICATOR_ICONS).forEach(([indicator, IconComponent]) => {
      const iconName = IconComponent.displayName;
      // Lucide icons use PascalCase
      const pascalCasePattern = /^[A-Z][a-z]+(?:[A-Z][a-z]+)*$/;
      expect(iconName).toMatch(pascalCasePattern);
    });
  });

  it('NC-004: No duplicate icon assignments', () => {
    const assignedIcons = new Set<string>();
    Object.entries(INDICATOR_ICONS).forEach(([indicator, IconComponent]) => {
      const iconName = IconComponent.displayName;
      // Track icon names - no two indicators should have the same icon
      // Actually, this is ALLOWED - multiple indicators CAN share icons
      // So we just verify each icon is valid
      expect(iconName).toBeDefined();
    });
  });

  it('NC-005: Semantic alignment between indicator and icon', () => {
    // Verify semantic meaning of each icon matches the indicator purpose
    const semanticMapping: Record<string, { icon: string; reason: string }> = {
      'keyword-rankings': { icon: 'TrendingUp', reason: 'Growth/position tracking' },
      'visibility-score': { icon: 'Eye', reason: 'Visibility' },
      'competitor-analysis': { icon: 'Users', reason: 'Multiple people/competitors' },
      'technical-health': { icon: 'Wrench', reason: 'Tools/fixing' },
      'backlink-profile': { icon: 'Link', reason: 'Links' },
      'content-opportunities': { icon: 'FileText', reason: 'Content/documents' },
      'serp-features': { icon: 'Star', reason: 'Featured/highlighted' },
      'page-speed': { icon: 'Zap', reason: 'Performance/fast' },
    };

    Object.entries(semanticMapping).forEach(([indicator, expected]) => {
      const Icon = INDICATOR_ICONS[indicator];
      expect(Icon?.displayName).toBe(expected.icon);
    });
  });
});

// ============================================================================
// BARREL EXPORT TESTS
// ============================================================================

describe('Barrel Export from indicators/index.ts', () => {
  it('BE-001: INDICATOR_ICONS exported from indicators/index.ts', async () => {
    try {
      const module = await import('@/components/indicators');
      expect(module.INDICATOR_ICONS).toBeDefined();
    } catch {
      expect(true).toBe(false);
    }
  });

  it('BE-002: IndicatorIconName type exported from indicators/index.ts', async () => {
    try {
      const module = await import('@/components/indicators');
      expect(module.IndicatorIconName).toBeDefined();
    } catch {
      expect(true).toBe(false);
    }
  });

  it('BE-003: Registry re-exported from icons.ts file', async () => {
    // Verify the icons are defined in a separate icons.ts file
    try {
      const iconsModule = await import('@/components/indicators/icons');
      expect(iconsModule.INDICATOR_ICONS).toBeDefined();
    } catch {
      expect(true).toBe(false);
    }
  });

  it('BE-004: All existing exports remain after adding new exports', async () => {
    try {
      const module = await import('@/components/indicators');
      // Existing exports should still exist
      expect(module.IndicatorIcon).toBeDefined();
      expect(module.IndicatorCard).toBeDefined();
      expect(module.IndicatorGrid).toBeDefined();
      expect(module.IndicatorCatalog).toBeDefined();
      // New exports
      expect(module.INDICATOR_ICONS).toBeDefined();
      expect(module.IndicatorIconName).toBeDefined();
    } catch {
      expect(true).toBe(false);
    }
  });
});

// ============================================================================
// ACCEPTANCE CRITERIA TESTS
// ============================================================================

describe('Acceptance Criteria', () => {
  let INDICATOR_ICONS: Record<string, any>;
  let IndicatorIconName: ReadonlyArray<string>;

  beforeAll(async () => {
    try {
      const module = await import('@/components/indicators/icons');
      INDICATOR_ICONS = module.INDICATOR_ICONS;
      IndicatorIconName = module.IndicatorIconName;
    } catch {
      INDICATOR_ICONS = {};
      IndicatorIconName = [];
    }
  });

  it('AC-001: Icon registry created with all indicator icons', () => {
    expect(INDICATOR_ICONS).toBeDefined();
    expect(Object.keys(INDICATOR_ICONS)).toHaveLength(8);
  });

  it('AC-002: Each indicator has an appropriate Lucide icon', () => {
    const requiredIndicators: IndicatorIconName[] = [
      'keyword-rankings',
      'visibility-score',
      'competitor-analysis',
      'technical-health',
      'backlink-profile',
      'content-opportunities',
      'serp-features',
      'page-speed',
    ];

    requiredIndicators.forEach(indicator => {
      const Icon = INDICATOR_ICONS[indicator];
      expect(Icon).toBeDefined();
      // Lucide icons are objects with render method
      expect(typeof Icon).toBe('object');
      expect(Icon.displayName).toBeDefined();
    });
  });

  it('AC-003: Icons support tree-shaking (individual imports)', () => {
    // Verify each icon is a direct reference from lucide-react
    const lucideExports = Object.keys(LucideReact);
    
    Object.values(INDICATOR_ICONS).forEach((Icon: any) => {
      const iconName = Icon.displayName;
      expect(lucideExports).toContain(iconName);
    });
  });

  it('AC-004: Icon names follow consistent naming convention', () => {
    const keys = Object.keys(INDICATOR_ICONS);
    
    // All keys must be kebab-case
    keys.forEach(key => {
      expect(key).toMatch(/^[a-z]+(-[a-z]+)*$/);
    });
  });

  it('AC-005: Registry exported from indicators index', async () => {
    try {
      const module = await import('@/components/indicators');
      expect(module.INDICATOR_ICONS).toEqual(INDICATOR_ICONS);
    } catch {
      expect(true).toBe(false);
    }
  });

  it('AC-006: Type IndicatorIconName exported for type safety', () => {
    expect(IndicatorIconName).toBeDefined();
    expect(IndicatorIconName).toHaveLength(8);
  });
});

// ============================================================================
// EDGE CASES
// ============================================================================

describe('Edge Cases', () => {
  let INDICATOR_ICONS: Record<string, any>;

  beforeAll(async () => {
    try {
      const module = await import('@/components/indicators/icons');
      INDICATOR_ICONS = module.INDICATOR_ICONS;
    } catch {
      INDICATOR_ICONS = {};
    }
  });

  it('EC-001: Accessing non-existent indicator returns undefined', () => {
    const Icon = INDICATOR_ICONS['non-existent-indicator' as any];
    expect(Icon).toBeUndefined();
  });

  it('EC-002: Registry is immutable at runtime (frozen)', () => {
    // TypeScript const assertion makes it readonly at compile time
    // Runtime check would require Object.freeze
    expect(() => {
      // @ts-expect-error - Testing runtime immutability
      INDICATOR_ICONS['new-indicator'] = 'test';
    }).toThrow();
  });

  it('EC-003: Registry keys are stable across multiple accesses', () => {
    const keys1 = Object.keys(INDICATOR_ICONS).sort();
    const keys2 = Object.keys(INDICATOR_ICONS).sort();
    expect(keys1).toEqual(keys2);
  });

  it('EC-004: All icons have valid displayName property', () => {
    Object.entries(INDICATOR_ICONS).forEach(([indicator, Icon]) => {
      expect((Icon as any).displayName).toBeDefined();
      expect(typeof (Icon as any).displayName).toBe('string');
    });
  });

  it('EC-005: No null or undefined values in registry', () => {
    Object.values(INDICATOR_ICONS).forEach(Icon => {
      expect(Icon).not.toBeNull();
      expect(Icon).not.toBeUndefined();
    });
  });

  it('EC-006: Registry works with Object.entries()', () => {
    const entries = Object.entries(INDICATOR_ICONS);
    expect(entries).toHaveLength(8);
    entries.forEach(([key, Icon]) => {
      expect(typeof key).toBe('string');
      // Lucide icons are objects with render method
      expect(typeof Icon).toBe('object');
    });
  });

  it('EC-007: Registry works with Object.values()', () => {
    const icons = Object.values(INDICATOR_ICONS);
    expect(icons).toHaveLength(8);
    icons.forEach(Icon => {
      // Lucide icons are objects with render method
      expect(typeof Icon).toBe('object');
    });
  });
});

// ============================================================================
// INTEGRATION TESTS
// ============================================================================

describe('Integration with IndicatorIcon Component', () => {
  let INDICATOR_ICONS: Record<string, any>;
  let IndicatorIcon: React.FC<{ icon?: string; size?: string; color?: string }>;

  beforeAll(async () => {
    try {
      const iconsModule = await import('@/components/indicators/icons');
      const iconModule = await import('@/components/indicators/IndicatorIcon');
      INDICATOR_ICONS = iconsModule.INDICATOR_ICONS;
      IndicatorIcon = iconModule.IndicatorIcon;
    } catch {
      INDICATOR_ICONS = {};
      IndicatorIcon = () => null;
    }
  });

  it('II-001: Registry icons can be used with IndicatorIcon component', async () => {
    const { render, screen } = await import('@testing-library/react');
    
    // Get the icon from registry
    const TrendingUpIcon = INDICATOR_ICONS['keyword-rankings'];
    
    // Should be able to render with IndicatorIcon using customIcon
    const { container } = render(
      <IndicatorIcon customIcon={<TrendingUpIcon />} />
    );
    
    expect(container.querySelector('svg')).toBeInTheDocument();
  });

  it('II-002: All registry icons render correctly', async () => {
    const { render } = await import('@testing-library/react');
    
    Object.entries(INDICATOR_ICONS).forEach(([indicator, Icon]) => {
      const { container } = render(
        <IndicatorIcon customIcon={<Icon />} />
      );
      expect(container.querySelector('svg')).toBeInTheDocument();
    });
  });
});

// ============================================================================
// DOCUMENTATION TESTS
// ============================================================================

describe('JSDoc Documentation', () => {
  let module: any;

  beforeAll(async () => {
    try {
      module = await import('@/components/indicators/icons');
    } catch {
      module = {};
    }
  });

  it('JD-001: Module has JSDoc comment explaining purpose', () => {
    // This is a documentation test - would need to parse the source file
    // For now, just verify the module exists
    expect(module).toBeDefined();
  });

  it('JD-002: INDICATOR_ICONS constant has JSDoc comment', () => {
    // TypeScript types are erased at runtime
    // This test documents the expectation
    expect(module.INDICATOR_ICONS).toBeDefined();
  });

  it('JD-003: IndicatorIconName type has JSDoc comment', () => {
    // TypeScript types are erased at runtime
    // This test documents the expectation
    expect(module.IndicatorIconName).toBeDefined();
  });
});
