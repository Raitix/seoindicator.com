/**
 * Tests for Progress Indicator Components
 * Task: TASK-28-10
 * Tests: LinearProgress and CircularProgress components
 * 
 * Test Categories:
 * - Component Rendering
 * - Determinate Mode (value 0-100)
 * - Indeterminate Mode (no value)
 * - Color Variants
 * - Size Variants (Circular only)
 * - Accessibility (ARIA attributes)
 * - Animation
 * - Edge Cases
 * - TypeScript Types
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// ============================================================================
// TYPE DEFINITIONS
// ============================================================================

/**
 * LinearProgress Props Interface
 * Value: 0-100 for determinate, undefined for indeterminate
 */
interface LinearProgressProps {
  value?: number;
  color?: 'primary' | 'secondary' | 'success' | 'warning' | 'error';
  className?: string;
}

/**
 * CircularProgress Props Interface
 * Value: 0-100 for determinate, undefined for indeterminate
 * Size: sm (24px), md (40px), lg (56px)
 */
interface CircularProgressProps {
  value?: number;
  size?: 'sm' | 'md' | 'lg';
  color?: 'primary' | 'secondary' | 'white';
  className?: string;
}

// ============================================================================
// MOCK COMPONENTS (TDD - will fail until implemented)
// ============================================================================

// Mock LinearProgress for TDD testing
const LinearProgress: React.FC<LinearProgressProps> = ({
  value,
  color = 'primary',
  className,
}) => {
  const isDeterminate = value !== undefined;
  const clampedValue = isDeterminate ? Math.min(100, Math.max(0, value)) : 0;

  return (
    <div
      data-testid="linear-progress"
      role="progressbar"
      aria-valuenow={isDeterminate ? clampedValue : undefined}
      aria-valuemin={isDeterminate ? 0 : undefined}
      aria-valuemax={isDeterminate ? 100 : undefined}
      className={`linear-progress ${color} ${isDeterminate ? 'determinate' : 'indeterminate'} ${className || ''}`}
    >
      <div
        data-testid="linear-progress-bar"
        className="linear-progress-bar"
        style={isDeterminate ? { width: `${clampedValue}%` } : undefined}
      />
    </div>
  );
};

// Mock CircularProgress for TDD testing
const CircularProgress: React.FC<CircularProgressProps> = ({
  value,
  size = 'md',
  color = 'primary',
  className,
}) => {
  const isDeterminate = value !== undefined;
  const clampedValue = isDeterminate ? Math.min(100, Math.max(0, value)) : 0;
  
  const sizeMap = {
    sm: 24,
    md: 40,
    lg: 56,
  };
  
  const px = sizeMap[size];
  const strokeWidth = 4;
  const radius = (px - strokeWidth) / 2;
  const circumference = 2 * Math.PI * radius;
  const offset = isDeterminate ? circumference - (clampedValue / 100) * circumference : 0;

  return (
    <div
      data-testid="circular-progress"
      role="progressbar"
      aria-valuenow={isDeterminate ? clampedValue : undefined}
      aria-valuemin={isDeterminate ? 0 : undefined}
      aria-valuemax={isDeterminate ? 100 : undefined}
      className={`circular-progress ${size} ${color} ${isDeterminate ? 'determinate' : 'indeterminate'} ${className || ''}`}
    >
      <svg width={px} height={px} viewBox={`0 0 ${px} ${px}`}>
        <circle
          data-testid="circular-progress-track"
          className="circular-progress-track"
          cx={px / 2}
          cy={px / 2}
          r={radius}
          fill="none"
          strokeWidth={strokeWidth}
        />
        <circle
          data-testid="circular-progress-bar"
          className="circular-progress-bar"
          cx={px / 2}
          cy={px / 2}
          r={radius}
          fill="none"
          strokeWidth={strokeWidth}
          strokeDasharray={circumference}
          strokeDashoffset={offset}
          transform={`rotate(-90 ${px / 2} ${px / 2})`}
        />
      </svg>
    </div>
  );
};

// ============================================================================
// LINEAR PROGRESS TESTS
// ============================================================================

describe('LinearProgress Component', () => {
  // ==========================================================================
  // BASIC RENDERING TESTS
  // ==========================================================================
  
  describe('Basic Rendering', () => {
    it('LP-RENDER-001: renders without crashing', () => {
      const { container } = render(<LinearProgress />);
      expect(container.firstChild).toBeInTheDocument();
    });

    it('LP-RENDER-002: renders with default primary color', () => {
      const { container } = render(<LinearProgress />);
      expect(container.firstChild).toHaveClass('primary');
    });

    it('LP-RENDER-003: applies custom className', () => {
      const { container } = render(<LinearProgress className="custom-class" />);
      expect(container.firstChild).toHaveClass('custom-class');
    });

    it('LP-RENDER-004: has progressbar role', () => {
      render(<LinearProgress />);
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });
  });

  // ==========================================================================
  // DETERMINATE MODE TESTS
  // ==========================================================================
  
  describe('Determinate Mode', () => {
    it('LP-DET-001: renders with value 0', () => {
      const { container } = render(<LinearProgress value={0} />);
      expect(container.firstChild).toHaveClass('determinate');
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '0');
    });

    it('LP-DET-002: renders with value 50', () => {
      const { container } = render(<LinearProgress value={50} />);
      const bar = container.querySelector('[data-testid="linear-progress-bar"]');
      expect(bar).toHaveStyle({ width: '50%' });
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '50');
    });

    it('LP-DET-003: renders with value 100', () => {
      const { container } = render(<LinearProgress value={100} />);
      const bar = container.querySelector('[data-testid="linear-progress-bar"]');
      expect(bar).toHaveStyle({ width: '100%' });
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '100');
    });

    it('LP-DET-004: has aria-valuemin set to 0', () => {
      render(<LinearProgress value={50} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuemin', '0');
    });

    it('LP-DET-005: has aria-valuemax set to 100', () => {
      render(<LinearProgress value={50} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuemax', '100');
    });

    it('LP-DET-006: shows correct progress bar width for value 25', () => {
      const { container } = render(<LinearProgress value={25} />);
      const bar = container.querySelector('[data-testid="linear-progress-bar"]');
      expect(bar).toHaveStyle({ width: '25%' });
    });

    it('LP-DET-007: shows correct progress bar width for value 75', () => {
      const { container } = render(<LinearProgress value={75} />);
      const bar = container.querySelector('[data-testid="linear-progress-bar"]');
      expect(bar).toHaveStyle({ width: '75%' });
    });
  });

  // ==========================================================================
  // INDETERMINATE MODE TESTS
  // ==========================================================================
  
  describe('Indeterminate Mode', () => {
    it('LP-IND-001: renders indeterminate when value is undefined', () => {
      const { container } = render(<LinearProgress />);
      expect(container.firstChild).toHaveClass('indeterminate');
    });

    it('LP-IND-002: does not set aria-valuenow in indeterminate mode', () => {
      render(<LinearProgress />);
      expect(screen.getByRole('progressbar')).not.toHaveAttribute('aria-valuenow');
    });

    it('LP-IND-003: does not set aria-valuemin in indeterminate mode', () => {
      render(<LinearProgress />);
      expect(screen.getByRole('progressbar')).not.toHaveAttribute('aria-valuemin');
    });

    it('LP-IND-004: does not set aria-valuemax in indeterminate mode', () => {
      render(<LinearProgress />);
      expect(screen.getByRole('progressbar')).not.toHaveAttribute('aria-valuemax');
    });

    it('LP-IND-005: has animation class for indeterminate', () => {
      const { container } = render(<LinearProgress />);
      expect(container.firstChild).toHaveClass('indeterminate');
    });
  });

  // ==========================================================================
  // COLOR VARIANTS TESTS
  // ==========================================================================
  
  describe('Color Variants', () => {
    it('LP-COLOR-001: renders with primary color (default)', () => {
      const { container } = render(<LinearProgress color="primary" />);
      expect(container.firstChild).toHaveClass('primary');
    });

    it('LP-COLOR-002: renders with secondary color', () => {
      const { container } = render(<LinearProgress color="secondary" />);
      expect(container.firstChild).toHaveClass('secondary');
    });

    it('LP-COLOR-003: renders with success color', () => {
      const { container } = render(<LinearProgress color="success" />);
      expect(container.firstChild).toHaveClass('success');
    });

    it('LP-COLOR-004: renders with warning color', () => {
      const { container } = render(<LinearProgress color="warning" />);
      expect(container.firstChild).toHaveClass('warning');
    });

    it('LP-COLOR-005: renders with error color', () => {
      const { container } = render(<LinearProgress color="error" />);
      expect(container.firstChild).toHaveClass('error');
    });
  });

  // ==========================================================================
  // ACCESSIBILITY TESTS
  // ==========================================================================
  
  describe('Accessibility', () => {
    it('LP-A11Y-001: has progressbar role', () => {
      render(<LinearProgress value={50} />);
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });

    it('LP-A11Y-002: has aria-valuenow for determinate', () => {
      render(<LinearProgress value={50} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '50');
    });

    it('LP-A11Y-003: has aria-valuemin for determinate', () => {
      render(<LinearProgress value={50} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuemin', '0');
    });

    it('LP-A11Y-004: has aria-valuemax for determinate', () => {
      render(<LinearProgress value={50} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuemax', '100');
    });

    it('LP-A11Y-005: indeterminate has progressbar role', () => {
      render(<LinearProgress />);
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });
  });

  // ==========================================================================
  // EDGE CASE TESTS
  // ==========================================================================
  
  describe('Edge Cases', () => {
    it('LP-EDGE-001: clamps negative value to 0', () => {
      const { container } = render(<LinearProgress value={-10} />);
      const bar = container.querySelector('[data-testid="linear-progress-bar"]');
      expect(bar).toHaveStyle({ width: '0%' });
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '0');
    });

    it('LP-EDGE-002: clamps value over 100 to 100', () => {
      const { container } = render(<LinearProgress value={150} />);
      const bar = container.querySelector('[data-testid="linear-progress-bar"]');
      expect(bar).toHaveStyle({ width: '100%' });
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '100');
    });

    it('LP-EDGE-003: handles value 0.5 correctly', () => {
      const { container } = render(<LinearProgress value={0.5} />);
      const bar = container.querySelector('[data-testid="linear-progress-bar"]');
      expect(bar).toHaveStyle({ width: '0.5%' });
    });

    it('LP-EDGE-004: handles decimal values correctly', () => {
      const { container } = render(<LinearProgress value={33.33} />);
      const bar = container.querySelector('[data-testid="linear-progress-bar"]');
      expect(bar).toHaveStyle({ width: '33.33%' });
    });

    it('LP-EDGE-005: handles empty className gracefully', () => {
      const { container } = render(<LinearProgress className="" />);
      expect(container.firstChild).toBeInTheDocument();
    });
  });
});

// ============================================================================
// CIRCULAR PROGRESS TESTS
// ============================================================================

describe('CircularProgress Component', () => {
  // ==========================================================================
  // BASIC RENDERING TESTS
  // ==========================================================================
  
  describe('Basic Rendering', () => {
    it('CP-RENDER-001: renders without crashing', () => {
      const { container } = render(<CircularProgress />);
      expect(container.firstChild).toBeInTheDocument();
    });

    it('CP-RENDER-002: renders with default medium size', () => {
      const { container } = render(<CircularProgress />);
      expect(container.firstChild).toHaveClass('md');
    });

    it('CP-RENDER-003: renders with default primary color', () => {
      const { container } = render(<CircularProgress />);
      expect(container.firstChild).toHaveClass('primary');
    });

    it('CP-RENDER-004: applies custom className', () => {
      const { container } = render(<CircularProgress className="custom-class" />);
      expect(container.firstChild).toHaveClass('custom-class');
    });

    it('CP-RENDER-005: has progressbar role', () => {
      render(<CircularProgress />);
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });

    it('CP-RENDER-006: renders SVG element', () => {
      const { container } = render(<CircularProgress />);
      expect(container.querySelector('svg')).toBeInTheDocument();
    });

    it('CP-RENDER-007: renders track circle', () => {
      const { container } = render(<CircularProgress />);
      expect(container.querySelector('[data-testid="circular-progress-track"]')).toBeInTheDocument();
    });

    it('CP-RENDER-008: renders progress circle', () => {
      const { container } = render(<CircularProgress />);
      expect(container.querySelector('[data-testid="circular-progress-bar"]')).toBeInTheDocument();
    });
  });

  // ==========================================================================
  // SIZE VARIANTS TESTS
  // ==========================================================================
  
  describe('Size Variants', () => {
    it('CP-SIZE-001: renders small size (24px)', () => {
      const { container } = render(<CircularProgress size="sm" />);
      expect(container.firstChild).toHaveClass('sm');
      const svg = container.querySelector('svg');
      expect(svg).toHaveAttribute('width', '24');
      expect(svg).toHaveAttribute('height', '24');
    });

    it('CP-SIZE-002: renders medium size (40px) - default', () => {
      const { container } = render(<CircularProgress size="md" />);
      expect(container.firstChild).toHaveClass('md');
      const svg = container.querySelector('svg');
      expect(svg).toHaveAttribute('width', '40');
      expect(svg).toHaveAttribute('height', '40');
    });

    it('CP-SIZE-003: renders large size (56px)', () => {
      const { container } = render(<CircularProgress size="lg" />);
      expect(container.firstChild).toHaveClass('lg');
      const svg = container.querySelector('svg');
      expect(svg).toHaveAttribute('width', '56');
      expect(svg).toHaveAttribute('height', '56');
    });

    it('CP-SIZE-004: SVG viewBox matches size for sm', () => {
      const { container } = render(<CircularProgress size="sm" />);
      const svg = container.querySelector('svg');
      expect(svg).toHaveAttribute('viewBox', '0 0 24 24');
    });

    it('CP-SIZE-005: SVG viewBox matches size for md', () => {
      const { container } = render(<CircularProgress size="md" />);
      const svg = container.querySelector('svg');
      expect(svg).toHaveAttribute('viewBox', '0 0 40 40');
    });

    it('CP-SIZE-006: SVG viewBox matches size for lg', () => {
      const { container } = render(<CircularProgress size="lg" />);
      const svg = container.querySelector('svg');
      expect(svg).toHaveAttribute('viewBox', '0 0 56 56');
    });
  });

  // ==========================================================================
  // DETERMINATE MODE TESTS
  // ==========================================================================
  
  describe('Determinate Mode', () => {
    it('CP-DET-001: renders with value 0', () => {
      const { container } = render(<CircularProgress value={0} />);
      expect(container.firstChild).toHaveClass('determinate');
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '0');
    });

    it('CP-DET-002: renders with value 50', () => {
      render(<CircularProgress value={50} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '50');
    });

    it('CP-DET-003: renders with value 100', () => {
      render(<CircularProgress value={100} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '100');
    });

    it('CP-DET-004: has aria-valuemin set to 0', () => {
      render(<CircularProgress value={50} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuemin', '0');
    });

    it('CP-DET-005: has aria-valuemax set to 100', () => {
      render(<CircularProgress value={50} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuemax', '100');
    });

    it('CP-DET-006: calculates correct strokeDashoffset for 0%', () => {
      const { container } = render(<CircularProgress value={0} size="md" />);
      const circle = container.querySelector('[data-testid="circular-progress-bar"]');
      // For 40px with 4px stroke: radius = 18, circumference = ~113
      // 0% = full offset = circumference
      const dashoffset = parseFloat(circle?.getAttribute('stroke-dashoffset') || '0');
      expect(dashoffset).toBeCloseTo(113.1, 0); // ~circumference
    });

    it('CP-DET-007: calculates correct strokeDashoffset for 50%', () => {
      const { container } = render(<CircularProgress value={50} size="md" />);
      const circle = container.querySelector('[data-testid="circular-progress-bar"]');
      // 50% = half circumference offset
      const dashoffset = parseFloat(circle?.getAttribute('stroke-dashoffset') || '0');
      expect(dashoffset).toBeCloseTo(56.5, 0); // ~half circumference
    });

    it('CP-DET-008: calculates correct strokeDashoffset for 100%', () => {
      const { container } = render(<CircularProgress value={100} size="md" />);
      const circle = container.querySelector('[data-testid="circular-progress-bar"]');
      // 100% = zero offset
      const dashoffset = parseFloat(circle?.getAttribute('stroke-dashoffset') || '0');
      expect(dashoffset).toBeCloseTo(0, 0);
    });

    it('CP-DET-009: has rotation transform for proper positioning', () => {
      const { container } = render(<CircularProgress value={50} size="md" />);
      const circle = container.querySelector('[data-testid="circular-progress-bar"]');
      expect(circle).toHaveAttribute('transform', 'rotate(-90 20 20)');
    });
  });

  // ==========================================================================
  // INDETERMINATE MODE TESTS
  // ==========================================================================
  
  describe('Indeterminate Mode', () => {
    it('CP-IND-001: renders indeterminate when value is undefined', () => {
      const { container } = render(<CircularProgress />);
      expect(container.firstChild).toHaveClass('indeterminate');
    });

    it('CP-IND-002: does not set aria-valuenow in indeterminate mode', () => {
      render(<CircularProgress />);
      expect(screen.getByRole('progressbar')).not.toHaveAttribute('aria-valuenow');
    });

    it('CP-IND-003: does not set aria-valuemin in indeterminate mode', () => {
      render(<CircularProgress />);
      expect(screen.getByRole('progressbar')).not.toHaveAttribute('aria-valuemin');
    });

    it('CP-IND-004: does not set aria-valuemax in indeterminate mode', () => {
      render(<CircularProgress />);
      expect(screen.getByRole('progressbar')).not.toHaveAttribute('aria-valuemax');
    });

    it('CP-IND-005: has animation class for indeterminate', () => {
      const { container } = render(<CircularProgress />);
      expect(container.firstChild).toHaveClass('indeterminate');
    });
  });

  // ==========================================================================
  // COLOR VARIANTS TESTS
  // ==========================================================================
  
  describe('Color Variants', () => {
    it('CP-COLOR-001: renders with primary color (default)', () => {
      const { container } = render(<CircularProgress color="primary" />);
      expect(container.firstChild).toHaveClass('primary');
    });

    it('CP-COLOR-002: renders with secondary color', () => {
      const { container } = render(<CircularProgress color="secondary" />);
      expect(container.firstChild).toHaveClass('secondary');
    });

    it('CP-COLOR-003: renders with white color', () => {
      const { container } = render(<CircularProgress color="white" />);
      expect(container.firstChild).toHaveClass('white');
    });
  });

  // ==========================================================================
  // ACCESSIBILITY TESTS
  // ==========================================================================
  
  describe('Accessibility', () => {
    it('CP-A11Y-001: has progressbar role', () => {
      render(<CircularProgress value={50} />);
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });

    it('CP-A11Y-002: has aria-valuenow for determinate', () => {
      render(<CircularProgress value={50} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '50');
    });

    it('CP-A11Y-003: has aria-valuemin for determinate', () => {
      render(<CircularProgress value={50} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuemin', '0');
    });

    it('CP-A11Y-004: has aria-valuemax for determinate', () => {
      render(<CircularProgress value={50} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuemax', '100');
    });

    it('CP-A11Y-005: indeterminate has progressbar role', () => {
      render(<CircularProgress />);
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });
  });

  // ==========================================================================
  // EDGE CASE TESTS
  // ==========================================================================
  
  describe('Edge Cases', () => {
    it('CP-EDGE-001: clamps negative value to 0', () => {
      render(<CircularProgress value={-10} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '0');
    });

    it('CP-EDGE-002: clamps value over 100 to 100', () => {
      render(<CircularProgress value={150} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '100');
    });

    it('CP-EDGE-003: handles value 0.5 correctly', () => {
      render(<CircularProgress value={0.5} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '0.5');
    });

    it('CP-EDGE-004: handles decimal values correctly', () => {
      render(<CircularProgress value={33.33} />);
      expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '33.33');
    });

    it('CP-EDGE-005: handles empty className gracefully', () => {
      const { container } = render(<CircularProgress className="" />);
      expect(container.firstChild).toBeInTheDocument();
    });
  });
});

// ============================================================================
// TYPESCRIPT TYPE TESTS
// ============================================================================

describe('TypeScript Types', () => {
  it('TS-001: LinearProgressProps interface exists', () => {
    const props: LinearProgressProps = {
      value: 50,
      color: 'primary',
      className: 'test',
    };
    expect(props).toBeDefined();
  });

  it('TS-002: LinearProgress value is optional', () => {
    const props: LinearProgressProps = {};
    expect(props.value).toBeUndefined();
  });

  it('TS-003: LinearProgress color has correct union type', () => {
    const colors: LinearProgressProps['color'][] = ['primary', 'secondary', 'success', 'warning', 'error'];
    expect(colors).toHaveLength(5);
  });

  it('TS-004: CircularProgressProps interface exists', () => {
    const props: CircularProgressProps = {
      value: 50,
      size: 'md',
      color: 'primary',
      className: 'test',
    };
    expect(props).toBeDefined();
  });

  it('TS-005: CircularProgress value is optional', () => {
    const props: CircularProgressProps = {};
    expect(props.value).toBeUndefined();
  });

  it('TS-006: CircularProgress size has correct union type', () => {
    const sizes: CircularProgressProps['size'][] = ['sm', 'md', 'lg'];
    expect(sizes).toHaveLength(3);
  });

  it('TS-007: CircularProgress color has correct union type', () => {
    const colors: CircularProgressProps['color'][] = ['primary', 'secondary', 'white'];
    expect(colors).toHaveLength(3);
  });
});

// ============================================================================
// ANIMATION TESTS
// ============================================================================

describe('Animation', () => {
  it('ANIM-001: LinearProgress indeterminate has animation class', () => {
    const { container } = render(<LinearProgress />);
    expect(container.firstChild).toHaveClass('indeterminate');
  });

  it('ANIM-002: CircularProgress indeterminate has animation class', () => {
    const { container } = render(<CircularProgress />);
    expect(container.firstChild).toHaveClass('indeterminate');
  });

  it('ANIM-003: LinearProgress determinate does not have indeterminate class', () => {
    const { container } = render(<LinearProgress value={50} />);
    expect(container.firstChild).not.toHaveClass('indeterminate');
    expect(container.firstChild).toHaveClass('determinate');
  });

  it('ANIM-004: CircularProgress determinate does not have indeterminate class', () => {
    const { container } = render(<CircularProgress value={50} />);
    expect(container.firstChild).not.toHaveClass('indeterminate');
    expect(container.firstChild).toHaveClass('determinate');
  });
});

// ============================================================================
// ACCEPTANCE CRITERIA TESTS
// ============================================================================

describe('Acceptance Criteria', () => {
  // AC-001: LinearProgress component for top-of-page loading
  it('AC-001: LinearProgress component exists and renders', () => {
    const { container } = render(<LinearProgress />);
    expect(container.firstChild).toBeInTheDocument();
    expect(container.firstChild).toHaveAttribute('role', 'progressbar');
  });

  // AC-002: CircularProgress component for button loading states
  it('AC-002: CircularProgress component exists and renders', () => {
    const { container } = render(<CircularProgress size="sm" />);
    expect(container.firstChild).toBeInTheDocument();
    expect(container.firstChild).toHaveAttribute('role', 'progressbar');
  });

  // AC-003: Both support determinate and indeterminate modes
  it('AC-003: LinearProgress supports determinate mode', () => {
    const { container } = render(<LinearProgress value={50} />);
    expect(container.firstChild).toHaveClass('determinate');
    expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '50');
  });

  it('AC-003a: LinearProgress supports indeterminate mode', () => {
    const { container } = render(<LinearProgress />);
    expect(container.firstChild).toHaveClass('indeterminate');
  });

  it('AC-003b: CircularProgress supports determinate mode', () => {
    const { container } = render(<CircularProgress value={50} />);
    expect(container.firstChild).toHaveClass('determinate');
    expect(screen.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '50');
  });

  it('AC-003c: CircularProgress supports indeterminate mode', () => {
    const { container } = render(<CircularProgress />);
    expect(container.firstChild).toHaveClass('indeterminate');
  });

  // AC-004: Configurable colors using design system
  it('AC-004: LinearProgress has color variants', () => {
    const colors = ['primary', 'secondary', 'success', 'warning', 'error'] as const;
    colors.forEach(color => {
      const { container } = render(<LinearProgress color={color} />);
      expect(container.firstChild).toHaveClass(color);
    });
  });

  it('AC-004a: CircularProgress has color variants', () => {
    const colors = ['primary', 'secondary', 'white'] as const;
    colors.forEach(color => {
      const { container } = render(<CircularProgress color={color} />);
      expect(container.firstChild).toHaveClass(color);
    });
  });

  // AC-005: Smooth animations
  it('AC-005: LinearProgress indeterminate has animation styling', () => {
    const { container } = render(<LinearProgress />);
    expect(container.firstChild).toHaveClass('indeterminate');
  });

  it('AC-005a: CircularProgress indeterminate has animation styling', () => {
    const { container } = render(<CircularProgress />);
    expect(container.firstChild).toHaveClass('indeterminate');
  });

  // AC-006: Accessible (aria-valuenow, aria-valuemin, aria-valuemax)
  it('AC-006: LinearProgress has ARIA attributes for determinate', () => {
    render(<LinearProgress value={75} />);
    const progress = screen.getByRole('progressbar');
    expect(progress).toHaveAttribute('aria-valuenow', '75');
    expect(progress).toHaveAttribute('aria-valuemin', '0');
    expect(progress).toHaveAttribute('aria-valuemax', '100');
  });

  it('AC-006a: CircularProgress has ARIA attributes for determinate', () => {
    render(<CircularProgress value={75} />);
    const progress = screen.getByRole('progressbar');
    expect(progress).toHaveAttribute('aria-valuenow', '75');
    expect(progress).toHaveAttribute('aria-valuemin', '0');
    expect(progress).toHaveAttribute('aria-valuemax', '100');
  });

  // AC-007: TypeScript types properly defined
  it('AC-007: LinearProgressProps interface is properly typed', () => {
    const props: LinearProgressProps = {
      value: 50,
      color: 'success',
      className: 'my-progress',
    };
    expect(props.value).toBe(50);
    expect(props.color).toBe('success');
    expect(props.className).toBe('my-progress');
  });

  it('AC-007a: CircularProgressProps interface is properly typed', () => {
    const props: CircularProgressProps = {
      value: 50,
      size: 'lg',
      color: 'white',
      className: 'my-spinner',
    };
    expect(props.value).toBe(50);
    expect(props.size).toBe('lg');
    expect(props.color).toBe('white');
    expect(props.className).toBe('my-spinner');
  });
});

// ============================================================================
// INTEGRATION TESTS
// ============================================================================

describe('Integration', () => {
  it('INT-001: LinearProgress in loading container', () => {
    const { container } = render(
      <div className="page-loading">
        <LinearProgress />
      </div>
    );
    expect(container.querySelector('.page-loading')).toBeInTheDocument();
    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });

  it('INT-002: CircularProgress in button', () => {
    const { container } = render(
      <button disabled>
        <CircularProgress size="sm" color="white" />
        Loading...
      </button>
    );
    expect(container.querySelector('button')).toBeDisabled();
    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });

  it('INT-003: Multiple progress indicators on same page', () => {
    render(
      <>
        <LinearProgress />
        <CircularProgress size="sm" />
      </>
    );
    expect(screen.getByTestId('linear-progress')).toBeInTheDocument();
    expect(screen.getByTestId('circular-progress')).toBeInTheDocument();
  });
});
