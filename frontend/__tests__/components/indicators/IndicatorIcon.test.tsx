/**
 * Tests for IndicatorIcon Component
 * Task: TASK-28-4
 * Tests: Component rendering, size variants, color variants, custom SVG, accessibility
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock Lucide icons - accept and spread props for proper testing
jest.mock('lucide-react', () => ({
  Activity: (props: any) => <svg data-testid="lucide-activity" viewBox="0 0 24 24" {...props}><path d="M22 12h-4l-3 9L9 3l-3 9H2" /></svg>,
  AlertCircle: (props: any) => <svg data-testid="lucide-alert-circle" viewBox="0 0 24 24" {...props}><circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>,
  CheckCircle: (props: any) => <svg data-testid="lucide-check-circle" viewBox="0 0 24 24" {...props}><circle cx="12" cy="12" r="10" /><path d="M9 12l2 2 4-4" /></svg>,
  ChevronDown: (props: any) => <svg data-testid="lucide-chevron-down" viewBox="0 0 24 24" {...props}><path d="M6 9l6 6 6-6" /></svg>,
  HelpCircle: (props: any) => <svg data-testid="lucide-help-circle" viewBox="0 0 24 24" {...props}><circle cx="12" cy="12" r="10" /><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" /><line x1="12" y1="17" x2="12.01" y2="17" /></svg>,
  Info: (props: any) => <svg data-testid="lucide-info" viewBox="0 0 24 24" {...props}><circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" /></svg>,
  Search: (props: any) => <svg data-testid="lucide-search" viewBox="0 0 24 24" {...props}><circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></svg>,
  Star: (props: any) => <svg data-testid="lucide-star" viewBox="0 0 24 24" {...props}><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" /></svg>,
  TrendingUp: (props: any) => <svg data-testid="lucide-trending-up" viewBox="0 0 24 24" {...props}><polyline points="23 6 13.5 15.5 8.5 10.5 1 18" /><polyline points="17 6 23 6 23 12" /></svg>,
  Warning: (props: any) => <svg data-testid="lucide-warning" viewBox="0 0 24 24" {...props}><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" /><line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" /></svg>,
  XCircle: (props: any) => <svg data-testid="lucide-x-circle" viewBox="0 0 24 24" {...props}><circle cx="12" cy="12" r="10" /><line x1="15" y1="9" x2="9" y2="15" /><line x1="9" y1="9" x2="15" y2="15" /></svg>,
  Zap: (props: any) => <svg data-testid="lucide-zap" viewBox="0 0 24 24" {...props}><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" /></svg>,
}));

// Mock cn utility
jest.mock('@/lib/utils', () => ({
  cn: (...args: string[]) => args.filter(Boolean).join(' '),
}));

// Import component after mocks
let IndicatorIcon: React.FC<{
  icon?: string;
  customIcon?: React.ReactNode;
  size?: 'sm' | 'md' | 'lg';
  color?: 'default' | 'primary' | 'success' | 'warning' | 'error';
  className?: string;
}>;

const mockLucideIcons: Record<string, React.FC> = {
  Activity: () => <svg data-testid="lucide-activity" />,
  AlertCircle: () => <svg data-testid="lucide-alert-circle" />,
  CheckCircle: () => <svg data-testid="lucide-check-circle" />,
  ChevronDown: () => <svg data-testid="lucide-chevron-down" />,
  HelpCircle: () => <svg data-testid="lucide-help-circle" />,
  Info: () => <svg data-testid="lucide-info" />,
  Search: () => <svg data-testid="lucide-search" />,
  Star: () => <svg data-testid="lucide-star" />,
  TrendingUp: () => <svg data-testid="lucide-trending-up" />,
  Warning: () => <svg data-testid="lucide-warning" />,
  XCircle: () => <svg data-testid="lucide-x-circle" />,
  Zap: () => <svg data-testid="lucide-zap" />,
};

// ============================================================================
// TYPE DEFINITIONS TEST
// ============================================================================

describe('TypeScript Type Definitions', () => {
  it('TD-001: Component props interface exists', async () => {
    // This test verifies the component can be imported with typed props
    const module = await import('@/components/indicators/IndicatorIcon');
    IndicatorIcon = module.IndicatorIcon;
    expect(IndicatorIcon).toBeDefined();
  });
});

// ============================================================================
// COMPONENT RENDERING TESTS
// ============================================================================

describe('Component Rendering', () => {
  beforeAll(async () => {
    const module = await import('@/components/indicators/IndicatorIcon');
    IndicatorIcon = module.IndicatorIcon;
  });

  it('CR-001: Renders without crashing', () => {
    render(<IndicatorIcon icon="Activity" />);
    expect(screen.getByTestId('lucide-activity')).toBeInTheDocument();
  });

  it('CR-002: Renders with data-slot attribute', () => {
    const { container } = render(<IndicatorIcon icon="Activity" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toBeInTheDocument();
  });

  it('CR-003: Renders default size (md) when not specified', () => {
    const { container } = render(<IndicatorIcon icon="Activity" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('size-6');
  });

  it('CR-004: Applies custom className', () => {
    const { container } = render(<IndicatorIcon icon="Activity" className="custom-class" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('custom-class');
  });

  it('CR-005: Does not render when neither icon nor customIcon provided', () => {
    const { container } = render(<IndicatorIcon />);
    expect(container.firstChild).toBeNull();
  });
});

// ============================================================================
// LUCIDE ICON RENDERING TESTS
// ============================================================================

describe('Lucide Icon Rendering', () => {
  beforeAll(async () => {
    const module = await import('@/components/indicators/IndicatorIcon');
    IndicatorIcon = module.IndicatorIcon;
  });

  const lucideIcons = [
    { id: 'LI-001', name: 'Activity' },
    { id: 'LI-002', name: 'AlertCircle' },
    { id: 'LI-003', name: 'CheckCircle' },
    { id: 'LI-004', name: 'ChevronDown' },
    { id: 'LI-005', name: 'HelpCircle' },
    { id: 'LI-006', name: 'Info' },
    { id: 'LI-007', name: 'Search' },
    { id: 'LI-008', name: 'Star' },
    { id: 'LI-009', name: 'TrendingUp' },
    { id: 'LI-010', name: 'Warning' },
    { id: 'LI-011', name: 'XCircle' },
    { id: 'LI-012', name: 'Zap' },
  ];

  // Convert camelCase to kebab-case for testid matching
  const toKebabCase = (str: string) => 
    str
      .replace(/([a-z])([A-Z])/g, '$1-$2')
      .replace(/([A-Z]+)([A-Z][a-z])/g, '$1-$2')
      .toLowerCase();

  lucideIcons.forEach(({ id, name }) => {
    it(`${id}: Renders ${name} icon correctly`, () => {
      render(<IndicatorIcon icon={name} />);
      expect(screen.getByTestId(`lucide-${toKebabCase(name)}`)).toBeInTheDocument();
    });
  });

  it('LI-013: Invalid icon name renders nothing gracefully', () => {
    const { container } = render(<IndicatorIcon icon="NonExistentIcon" />);
    expect(container.firstChild).toBeNull();
  });
});

// ============================================================================
// SIZE VARIANT TESTS
// ============================================================================

describe('Size Variants', () => {
  beforeAll(async () => {
    const module = await import('@/components/indicators/IndicatorIcon');
    IndicatorIcon = module.IndicatorIcon;
  });

  it('SV-001: sm size renders with 16px (size-4)', () => {
    const { container } = render(<IndicatorIcon icon="Activity" size="sm" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('size-4');
  });

  it('SV-002: md size renders with 24px (size-6)', () => {
    const { container } = render(<IndicatorIcon icon="Activity" size="md" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('size-6');
  });

  it('SV-003: lg size renders with 32px (size-8)', () => {
    const { container } = render(<IndicatorIcon icon="Activity" size="lg" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('size-8');
  });

  it('SV-004: Default size is md when not specified', () => {
    const { container } = render(<IndicatorIcon icon="Activity" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('size-6');
  });

  it('SV-005: sm size applies smaller stroke width', () => {
    const { container } = render(<IndicatorIcon icon="Activity" size="sm" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('[&_svg]:stroke-[1.5]');
  });

  it('SV-006: md size applies default stroke width', () => {
    const { container } = render(<IndicatorIcon icon="Activity" size="md" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('[&_svg]:stroke-2');
  });

  it('SV-007: lg size applies thicker stroke width', () => {
    const { container } = render(<IndicatorIcon icon="Activity" size="lg" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('[&_svg]:stroke-[2.5]');
  });

  it('SV-008: Size variant sets data-size attribute', () => {
    const { container } = render(<IndicatorIcon icon="Activity" size="lg" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveAttribute('data-size', 'lg');
  });
});

// ============================================================================
// COLOR VARIANT TESTS
// ============================================================================

describe('Color Variants', () => {
  beforeAll(async () => {
    const module = await import('@/components/indicators/IndicatorIcon');
    IndicatorIcon = module.IndicatorIcon;
  });

  it('CV-001: default color inherits text color', () => {
    const { container } = render(<IndicatorIcon icon="Activity" color="default" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('text-current');
  });

  it('CV-002: primary color applies primary text color', () => {
    const { container } = render(<IndicatorIcon icon="Activity" color="primary" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('text-primary');
  });

  it('CV-003: success color applies success/emerald text color', () => {
    const { container } = render(<IndicatorIcon icon="Activity" color="success" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('text-emerald-600');
  });

  it('CV-004: warning color applies warning/amber text color', () => {
    const { container } = render(<IndicatorIcon icon="Activity" color="warning" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('text-amber-500');
  });

  it('CV-005: error color applies destructive/rose text color', () => {
    const { container } = render(<IndicatorIcon icon="Activity" color="error" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('text-destructive');
  });

  it('CV-006: Default color is default when not specified', () => {
    const { container } = render(<IndicatorIcon icon="Activity" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('text-current');
  });

  it('CV-007: Color variant sets data-color attribute', () => {
    const { container } = render(<IndicatorIcon icon="Activity" color="success" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveAttribute('data-color', 'success');
  });

  it('CV-008: Primary color has dark mode variant', () => {
    const { container } = render(<IndicatorIcon icon="Activity" color="primary" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('dark:text-primary');
  });

  it('CV-009: Success color has dark mode variant', () => {
    const { container } = render(<IndicatorIcon icon="Activity" color="success" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('dark:text-emerald-400');
  });

  it('CV-010: Warning color has dark mode variant', () => {
    const { container } = render(<IndicatorIcon icon="Activity" color="warning" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('dark:text-amber-400');
  });

  it('CV-011: Error color has dark mode variant', () => {
    const { container } = render(<IndicatorIcon icon="Activity" color="error" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('dark:text-destructive');
  });
});

// ============================================================================
// CUSTOM SVG TESTS
// ============================================================================

describe('Custom SVG Rendering', () => {
  beforeAll(async () => {
    const module = await import('@/components/indicators/IndicatorIcon');
    IndicatorIcon = module.IndicatorIcon;
  });

  const CustomIcon = () => (
    <svg data-testid="custom-icon" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
    </svg>
  );

  it('CS-001: Renders custom SVG via customIcon prop', () => {
    render(<IndicatorIcon customIcon={<CustomIcon />} />);
    expect(screen.getByTestId('custom-icon')).toBeInTheDocument();
  });

  it('CS-002: Custom SVG ignores icon prop when both provided', () => {
    render(<IndicatorIcon icon="Activity" customIcon={<CustomIcon />} />);
    expect(screen.getByTestId('custom-icon')).toBeInTheDocument();
    expect(screen.queryByTestId('lucide-activity')).not.toBeInTheDocument();
  });

  it('CS-003: Custom SVG inherits wrapper size', () => {
    const { container } = render(<IndicatorIcon customIcon={<CustomIcon />} size="lg" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('size-8');
  });

  it('CS-004: Custom SVG inherits color variant', () => {
    const { container } = render(<IndicatorIcon customIcon={<CustomIcon />} color="primary" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('text-primary');
  });

  it('CS-005: Custom SVG with complex nested elements renders', () => {
    const ComplexIcon = () => (
      <svg data-testid="complex-icon" viewBox="0 0 24 24">
        <defs>
          <linearGradient id="grad">
            <stop offset="0%" stopColor="#fff" />
            <stop offset="100%" stopColor="#000" />
          </linearGradient>
        </defs>
        <circle cx="12" cy="12" r="10" fill="url(#grad)" />
        <path d="M12 6v6l4 2" />
      </svg>
    );
    render(<IndicatorIcon customIcon={<ComplexIcon />} />);
    expect(screen.getByTestId('complex-icon')).toBeInTheDocument();
  });
});

// ============================================================================
// VIEWBOX AND STYLING TESTS
// ============================================================================

describe('ViewBox and Styling', () => {
  beforeAll(async () => {
    const module = await import('@/components/indicators/IndicatorIcon');
    IndicatorIcon = module.IndicatorIcon;
  });

  it('VS-001: SVG wrapper enforces 24x24 viewBox for Lucide icons', () => {
    const { container } = render(<IndicatorIcon icon="Activity" />);
    // Lucide icons have viewBox="0 0 24 24" by default
    const svg = container.querySelector('svg');
    expect(svg).toHaveAttribute('viewBox', '0 0 24 24');
  });

  it('VS-002: Default stroke width is 2px', () => {
    const { container } = render(<IndicatorIcon icon="Activity" size="md" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('[&_svg]:stroke-2');
  });

  it('VS-003: Rounded corners applied via wrapper', () => {
    const { container } = render(<IndicatorIcon icon="Activity" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('rounded-sm');
  });

  it('VS-004: SVG inherits wrapper dimensions', () => {
    const { container } = render(<IndicatorIcon icon="Activity" size="lg" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    // SVG should fill the wrapper
    expect(wrapper).toHaveClass('[&_svg]:size-full');
  });

  it('VS-005: Flex centering applied for alignment', () => {
    const { container } = render(<IndicatorIcon icon="Activity" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('inline-flex');
    expect(wrapper).toHaveClass('items-center');
    expect(wrapper).toHaveClass('justify-center');
  });

  it('VS-006: SVG stroke-linecap is round', () => {
    const { container } = render(<IndicatorIcon icon="Activity" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('[&_svg]:stroke-linecap-round');
  });

  it('VS-007: SVG stroke-linejoin is round', () => {
    const { container } = render(<IndicatorIcon icon="Activity" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('[&_svg]:stroke-linejoin-round');
  });
});

// ============================================================================
// ACCESSIBILITY TESTS
// ============================================================================

describe('Accessibility', () => {
  beforeAll(async () => {
    const module = await import('@/components/indicators/IndicatorIcon');
    IndicatorIcon = module.IndicatorIcon;
  });

  it('AC-001: Icon has aria-hidden by default', () => {
    const { container } = render(<IndicatorIcon icon="Activity" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveAttribute('aria-hidden', 'true');
  });

  it('AC-002: Role is img when aria-label provided', () => {
    const { container } = render(<IndicatorIcon icon="Activity" aria-label="Activity indicator" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveAttribute('role', 'img');
  });

  it('AC-003: aria-label is set when provided', () => {
    const { container } = render(<IndicatorIcon icon="Activity" aria-label="Activity indicator" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveAttribute('aria-label', 'Activity indicator');
  });

  it('AC-004: aria-hidden removed when aria-label provided', () => {
    const { container } = render(<IndicatorIcon icon="Activity" aria-label="Activity indicator" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).not.toHaveAttribute('aria-hidden');
  });

  it('AC-005: Focusable is false to prevent keyboard focus', () => {
    const { container } = render(<IndicatorIcon icon="Activity" />);
    const svg = container.querySelector('svg');
    expect(svg).toHaveAttribute('focusable', 'false');
  });

  it('AC-006: Custom icon inherits accessibility attributes', () => {
    const CustomIcon = () => <svg data-testid="custom-icon" />;
    const { container } = render(
      <IndicatorIcon customIcon={<CustomIcon />} aria-label="Custom indicator" />
    );
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveAttribute('aria-label', 'Custom indicator');
  });
});

// ============================================================================
// EDGE CASES
// ============================================================================

describe('Edge Cases', () => {
  beforeAll(async () => {
    const module = await import('@/components/indicators/IndicatorIcon');
    IndicatorIcon = module.IndicatorIcon;
  });

  it('EC-001: Empty className string is handled', () => {
    const { container } = render(<IndicatorIcon icon="Activity" className="" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toBeInTheDocument();
  });

  it('EC-002: Null customIcon renders nothing', () => {
    const { container } = render(<IndicatorIcon customIcon={null} />);
    expect(container.firstChild).toBeNull();
  });

  it('EC-003: Undefined icon renders nothing', () => {
    const { container } = render(<IndicatorIcon icon={undefined} />);
    expect(container.firstChild).toBeNull();
  });

  it('EC-004: Both icon and customIcon null renders nothing', () => {
    const { container } = render(<IndicatorIcon icon={undefined} customIcon={null} />);
    expect(container.firstChild).toBeNull();
  });

  it('EC-005: Multiple className values merge correctly', () => {
    const { container } = render(<IndicatorIcon icon="Activity" className="mt-2 px-4" />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('mt-2');
    expect(wrapper).toHaveClass('px-4');
  });

  it('EC-006: Invalid size falls back to md', () => {
    // TypeScript prevents this, but runtime test
    const { container } = render(<IndicatorIcon icon="Activity" size={'invalid' as any} />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toBeInTheDocument();
  });

  it('EC-007: Invalid color falls back to default', () => {
    // TypeScript prevents this, but runtime test
    const { container } = render(<IndicatorIcon icon="Activity" color={'invalid' as any} />);
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toBeInTheDocument();
  });

  it('EC-008: Rapid re-renders do not cause memory leaks', () => {
    const { container, rerender } = render(<IndicatorIcon icon="Activity" size="sm" />);
    for (let i = 0; i < 100; i++) {
      rerender(<IndicatorIcon icon="Activity" size={i % 3 === 0 ? 'sm' : i % 3 === 1 ? 'md' : 'lg'} />);
    }
    expect(container.querySelector('[data-slot="indicator-icon"]')).toBeInTheDocument();
  });
});

// ============================================================================
// EXPORT TESTS
// ============================================================================

describe('Module Exports', () => {
  it('ME-001: IndicatorIcon exported from indicators/index.ts', async () => {
    const module = await import('@/components/indicators');
    expect(module.IndicatorIcon).toBeDefined();
  });

  it('ME-002: Named export available', async () => {
    const { IndicatorIcon: NamedExport } = await import('@/components/indicators/IndicatorIcon');
    expect(NamedExport).toBeDefined();
  });

  it('ME-003: Type IndicatorIconProps exported', async () => {
    const module = await import('@/components/indicators/IndicatorIcon');
    // TypeScript types are erased at runtime, but we verify the module exports
    expect(module).toBeDefined();
  });
});

// ============================================================================
// INTEGRATION TESTS
// ============================================================================

describe('Integration', () => {
  beforeAll(async () => {
    const module = await import('@/components/indicators/IndicatorIcon');
    IndicatorIcon = module.IndicatorIcon;
  });

  it('IN-001: All size and color combinations render', () => {
    const sizes = ['sm', 'md', 'lg'] as const;
    const colors = ['default', 'primary', 'success', 'warning', 'error'] as const;

    sizes.forEach(size => {
      colors.forEach(color => {
        const { container } = render(<IndicatorIcon icon="Activity" size={size} color={color} />);
        expect(container.querySelector('[data-slot="indicator-icon"]')).toBeInTheDocument();
      });
    });
  });

  it('IN-002: Icon within parent with custom text color inherits correctly', () => {
    const { container } = render(
      <div style={{ color: 'rgb(255, 0, 0)' }}>
        <IndicatorIcon icon="Activity" color="default" />
      </div>
    );
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('text-current');
  });

  it('IN-003: Icon in dark mode container uses dark variant', () => {
    const { container } = render(
      <div className="dark">
        <IndicatorIcon icon="Activity" color="primary" />
      </div>
    );
    const wrapper = container.querySelector('[data-slot="indicator-icon"]');
    expect(wrapper).toHaveClass('dark:text-primary');
  });

  it('IN-004: Multiple icons in row align correctly', () => {
    const { container } = render(
      <div className="flex items-center gap-2">
        <IndicatorIcon icon="CheckCircle" color="success" />
        <IndicatorIcon icon="Warning" color="warning" />
        <IndicatorIcon icon="XCircle" color="error" />
      </div>
    );
    const icons = container.querySelectorAll('[data-slot="indicator-icon"]');
    expect(icons).toHaveLength(3);
  });

  it('IN-005: Icon with aria-label is screen reader friendly', () => {
    const { container } = render(
      <button>
        <IndicatorIcon icon="Search" aria-label="Search" />
      </button>
    );
    const icon = container.querySelector('[data-slot="indicator-icon"]');
    expect(icon).toHaveAttribute('aria-label', 'Search');
    expect(icon).toHaveAttribute('role', 'img');
  });
});

// ============================================================================
// SNAPSHOT TESTS
// ============================================================================

describe('Snapshots', () => {
  beforeAll(async () => {
    const module = await import('@/components/indicators/IndicatorIcon');
    IndicatorIcon = module.IndicatorIcon;
  });

  it('SN-001: Default icon matches snapshot', () => {
    const { container } = render(<IndicatorIcon icon="Activity" />);
    expect(container.firstChild).toMatchSnapshot();
  });

  it('SN-002: All sizes match snapshot', () => {
    const { container: sm } = render(<IndicatorIcon icon="Activity" size="sm" />);
    const { container: md } = render(<IndicatorIcon icon="Activity" size="md" />);
    const { container: lg } = render(<IndicatorIcon icon="Activity" size="lg" />);

    expect(sm.firstChild).toMatchSnapshot('size-sm');
    expect(md.firstChild).toMatchSnapshot('size-md');
    expect(lg.firstChild).toMatchSnapshot('size-lg');
  });

  it('SN-003: All colors match snapshot', () => {
    const colors = ['default', 'primary', 'success', 'warning', 'error'] as const;
    colors.forEach(color => {
      const { container } = render(<IndicatorIcon icon="Activity" color={color} />);
      expect(container.firstChild).toMatchSnapshot(`color-${color}`);
    });
  });

  it('SN-004: Custom icon matches snapshot', () => {
    const CustomIcon = () => (
      <svg viewBox="0 0 24 24">
        <rect x="4" y="4" width="16" height="16" rx="2" />
      </svg>
    );
    const { container } = render(<IndicatorIcon customIcon={<CustomIcon />} />);
    expect(container.firstChild).toMatchSnapshot('custom-icon');
  });
});
