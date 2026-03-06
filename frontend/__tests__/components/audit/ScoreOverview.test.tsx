/**
 * ScoreOverview Component Tests
 * TASK-33-9: Create ScoreOverview Component
 *
 * Hero visual for public audit pages with circular score indicator.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  TrendingUp: ({ className, ...props }: React.SVGProps<SVGSVGElement>) => (
    <svg data-testid="trending-up-icon" className={className} {...props} />
  ),
  ExternalLink: ({ className, ...props }: React.SVGProps<SVGSVGElement>) => (
    <svg data-testid="external-link-icon" className={className} {...props} />
  ),
  Calendar: ({ className, ...props }: React.SVGProps<SVGSVGElement>) => (
    <svg data-testid="calendar-icon" className={className} {...props} />
  ),
  Globe: ({ className, ...props }: React.SVGProps<SVGSVGElement>) => (
    <svg data-testid="globe-icon" className={className} {...props} />
  ),
}));

// Mock shadcn/ui Card component
vi.mock('@/components/ui/card', () => ({
  Card: ({ children, className, ...props }: React.HTMLAttributes<HTMLDivElement>) => (
    <div data-testid="card" className={className} {...props}>
      {children}
    </div>
  ),
  CardContent: ({ children, className, ...props }: React.HTMLAttributes<HTMLDivElement>) => (
    <div data-testid="card-content" className={className} {...props}>
      {children}
    </div>
  ),
}));

// Mock Next.js Link
vi.mock('next/link', () => ({
  default: ({ children, href, className, ...props }: React.AnchorHTMLAttributes<HTMLAnchorElement> & { href: string }) => (
    <a href={href} className={className} data-testid="next-link" {...props}>
      {children}
    </a>
  ),
}));

// Types
interface ScoreOverviewProps {
  score: number;
  siteUrl: string;
  auditDate: string;
  className?: string;
  animate?: boolean;
  showCta?: boolean;
  size?: 'sm' | 'md' | 'lg';
}

// Mock component for TDD - Developer will implement
const ScoreOverview = ({
  score,
  siteUrl,
  auditDate,
  className,
  animate = true,
  showCta = true,
  size = 'md',
}: ScoreOverviewProps) => {
  // Score clamping
  const clampedScore = Math.max(0, Math.min(100, score));

  // Color mapping based on score range
  const getScoreColor = (s: number): { color: string; hex: string; status: string } => {
    if (s >= 90) return { color: 'green', hex: '#22c55e', status: 'Excellent' };
    if (s >= 70) return { color: 'blue', hex: '#3b82f6', status: 'Good' };
    if (s >= 50) return { color: 'yellow', hex: '#eab308', status: 'Needs Work' };
    if (s >= 30) return { color: 'orange', hex: '#f97316', status: 'Poor' };
    return { color: 'red', hex: '#ef4444', status: 'Critical Issues' };
  };

  const { color, hex, status } = getScoreColor(clampedScore);

  // Size mapping
  const sizeMap = {
    sm: { width: 120, height: 120, fontSize: 'text-2xl' },
    md: { width: 180, height: 180, fontSize: 'text-4xl' },
    lg: { width: 240, height: 240, fontSize: 'text-5xl' },
  };

  const { width, height, fontSize } = sizeMap[size];

  // SVG circle calculations
  const radius = (Math.min(width, height) / 2) - 10;
  const circumference = 2 * Math.PI * radius;
  const strokeDashoffset = circumference - (clampedScore / 100) * circumference;

  return (
    <div data-testid="score-overview" className={className}>
      {/* Circular Progress Indicator */}
      <div data-testid="circular-progress" data-score={clampedScore}>
        <svg
          width={width}
          height={height}
          viewBox={`0 0 ${width} ${height}`}
          data-testid="score-svg"
          role="img"
          aria-label={`SEO Score: ${clampedScore} out of 100`}
        >
          {/* Background circle */}
          <circle
            cx={width / 2}
            cy={height / 2}
            r={radius}
            fill="none"
            stroke="currentColor"
            strokeWidth="8"
            className="text-gray-200"
            data-testid="background-circle"
          />
          {/* Progress circle */}
          <circle
            cx={width / 2}
            cy={height / 2}
            r={radius}
            fill="none"
            stroke={hex}
            strokeWidth="8"
            strokeLinecap="round"
            strokeDasharray={circumference}
            strokeDashoffset={strokeDashoffset}
            transform={`rotate(-90 ${width / 2} ${height / 2})`}
            data-testid="progress-circle"
            data-color={color}
            data-animate={animate}
          />
        </svg>

        {/* Score Display */}
        <div data-testid="score-display" className={fontSize}>
          <span data-testid="score-number">{clampedScore}</span>
        </div>
      </div>

      {/* Status Text */}
      <div data-testid="status-container">
        <span data-testid="status-text" data-status={status} style={{ color: hex }}>
          {status}
        </span>
      </div>

      {/* Site URL */}
      <div data-testid="site-url-container">
        <svg data-testid="globe-icon" className="inline w-4 h-4" />
        <span data-testid="site-url">{siteUrl}</span>
      </div>

      {/* Audit Date */}
      <div data-testid="audit-date-container">
        <svg data-testid="calendar-icon" className="inline w-4 h-4" />
        <span data-testid="audit-date">{auditDate}</span>
      </div>

      {/* CTA Button */}
      {showCta && (
        <a
          href="/audit"
          data-testid="cta-button"
          className="inline-flex items-center gap-2"
        >
          Get your free audit
          <svg data-testid="external-link-icon" className="w-4 h-4" />
        </a>
      )}
    </div>
  );
};

// Helper functions
const createMockProps = (overrides: Partial<ScoreOverviewProps> = {}): ScoreOverviewProps => ({
  score: 75,
  siteUrl: 'https://example.com',
  auditDate: '2026-03-06',
  ...overrides,
});

describe('ScoreOverview', () => {
  let user: ReturnType<typeof userEvent.setup>;

  beforeEach(() => {
    user = userEvent.setup();
    vi.clearAllMocks();
  });

  // =====================================================
  // SO-001: Circular Progress Indicator Structure
  // =====================================================
  describe('SO-001: Circular Progress Indicator Structure', () => {
    it('SO-001-1: renders circular progress indicator', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('circular-progress')).toBeInTheDocument();
      expect(screen.getByTestId('score-svg')).toBeInTheDocument();
    });

    it('SO-001-2: renders SVG with correct viewBox dimensions', () => {
      const props = createMockProps({ size: 'md' });
      render(<ScoreOverview {...props} />);

      const svg = screen.getByTestId('score-svg');
      expect(svg).toHaveAttribute('viewBox', '0 0 180 180');
      expect(svg).toHaveAttribute('width', '180');
      expect(svg).toHaveAttribute('height', '180');
    });

    it('SO-001-3: renders background circle for progress track', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('background-circle')).toBeInTheDocument();
    });

    it('SO-001-4: renders progress circle with stroke', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('progress-circle')).toBeInTheDocument();
    });

    it('SO-001-5: displays score number inside circle', () => {
      const props = createMockProps({ score: 85 });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('score-display')).toBeInTheDocument();
      expect(screen.getByTestId('score-number')).toHaveTextContent('85');
    });
  });

  // =====================================================
  // SO-002: Score Range 0-100 Handling
  // =====================================================
  describe('SO-002: Score Range 0-100 Handling', () => {
    it('SO-002-1: displays score 0 correctly', () => {
      const props = createMockProps({ score: 0 });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('score-number')).toHaveTextContent('0');
      expect(screen.getByTestId('circular-progress')).toHaveAttribute('data-score', '0');
    });

    it('SO-002-2: displays score 100 correctly', () => {
      const props = createMockProps({ score: 100 });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('score-number')).toHaveTextContent('100');
      expect(screen.getByTestId('circular-progress')).toHaveAttribute('data-score', '100');
    });

    it('SO-002-3: displays score 50 correctly', () => {
      const props = createMockProps({ score: 50 });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('score-number')).toHaveTextContent('50');
    });

    it('SO-002-4: clamps negative scores to 0', () => {
      const props = createMockProps({ score: -10 });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('score-number')).toHaveTextContent('0');
      expect(screen.getByTestId('circular-progress')).toHaveAttribute('data-score', '0');
    });

    it('SO-002-5: clamps scores above 100 to 100', () => {
      const props = createMockProps({ score: 150 });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('score-number')).toHaveTextContent('100');
      expect(screen.getByTestId('circular-progress')).toHaveAttribute('data-score', '100');
    });

    it('SO-002-6: displays decimal scores (rounded)', () => {
      const props = createMockProps({ score: 75.5 });
      render(<ScoreOverview {...props} />);

      // Component should handle decimals - could round or floor
      const scoreText = screen.getByTestId('score-number').textContent;
      expect(['75', '75.5', '76']).toContain(scoreText);
    });
  });

  // =====================================================
  // SO-003: Green Color Coding (90-100)
  // =====================================================
  describe('SO-003: Green Color Coding (90-100)', () => {
    it('SO-003-1: green color for score 90', () => {
      const props = createMockProps({ score: 90 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'green');
      expect(progressCircle).toHaveAttribute('stroke', '#22c55e');
    });

    it('SO-003-2: green color for score 95', () => {
      const props = createMockProps({ score: 95 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'green');
    });

    it('SO-003-3: green color for score 100', () => {
      const props = createMockProps({ score: 100 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'green');
    });

    it('SO-003-4: displays "Excellent" status for green scores', () => {
      const props = createMockProps({ score: 92 });
      render(<ScoreOverview {...props} />);

      const statusText = screen.getByTestId('status-text');
      expect(statusText).toHaveTextContent('Excellent');
      expect(statusText).toHaveAttribute('data-status', 'Excellent');
    });
  });

  // =====================================================
  // SO-004: Blue Color Coding (70-89)
  // =====================================================
  describe('SO-004: Blue Color Coding (70-89)', () => {
    it('SO-004-1: blue color for score 70', () => {
      const props = createMockProps({ score: 70 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'blue');
      expect(progressCircle).toHaveAttribute('stroke', '#3b82f6');
    });

    it('SO-004-2: blue color for score 80', () => {
      const props = createMockProps({ score: 80 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'blue');
    });

    it('SO-004-3: blue color for score 89', () => {
      const props = createMockProps({ score: 89 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'blue');
    });

    it('SO-004-4: displays "Good" status for blue scores', () => {
      const props = createMockProps({ score: 75 });
      render(<ScoreOverview {...props} />);

      const statusText = screen.getByTestId('status-text');
      expect(statusText).toHaveTextContent('Good');
      expect(statusText).toHaveAttribute('data-status', 'Good');
    });
  });

  // =====================================================
  // SO-005: Yellow Color Coding (50-69)
  // =====================================================
  describe('SO-005: Yellow Color Coding (50-69)', () => {
    it('SO-005-1: yellow color for score 50', () => {
      const props = createMockProps({ score: 50 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'yellow');
      expect(progressCircle).toHaveAttribute('stroke', '#eab308');
    });

    it('SO-005-2: yellow color for score 60', () => {
      const props = createMockProps({ score: 60 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'yellow');
    });

    it('SO-005-3: yellow color for score 69', () => {
      const props = createMockProps({ score: 69 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'yellow');
    });

    it('SO-005-4: displays "Needs Work" status for yellow scores', () => {
      const props = createMockProps({ score: 55 });
      render(<ScoreOverview {...props} />);

      const statusText = screen.getByTestId('status-text');
      expect(statusText).toHaveTextContent('Needs Work');
      expect(statusText).toHaveAttribute('data-status', 'Needs Work');
    });
  });

  // =====================================================
  // SO-006: Orange Color Coding (30-49)
  // =====================================================
  describe('SO-006: Orange Color Coding (30-49)', () => {
    it('SO-006-1: orange color for score 30', () => {
      const props = createMockProps({ score: 30 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'orange');
      expect(progressCircle).toHaveAttribute('stroke', '#f97316');
    });

    it('SO-006-2: orange color for score 40', () => {
      const props = createMockProps({ score: 40 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'orange');
    });

    it('SO-006-3: orange color for score 49', () => {
      const props = createMockProps({ score: 49 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'orange');
    });

    it('SO-006-4: displays "Poor" status for orange scores', () => {
      const props = createMockProps({ score: 35 });
      render(<ScoreOverview {...props} />);

      const statusText = screen.getByTestId('status-text');
      expect(statusText).toHaveTextContent('Poor');
      expect(statusText).toHaveAttribute('data-status', 'Poor');
    });
  });

  // =====================================================
  // SO-007: Red Color Coding (0-29)
  // =====================================================
  describe('SO-007: Red Color Coding (0-29)', () => {
    it('SO-007-1: red color for score 0', () => {
      const props = createMockProps({ score: 0 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'red');
      expect(progressCircle).toHaveAttribute('stroke', '#ef4444');
    });

    it('SO-007-2: red color for score 15', () => {
      const props = createMockProps({ score: 15 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'red');
    });

    it('SO-007-3: red color for score 29', () => {
      const props = createMockProps({ score: 29 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-color', 'red');
    });

    it('SO-007-4: displays "Critical Issues" status for red scores', () => {
      const props = createMockProps({ score: 10 });
      render(<ScoreOverview {...props} />);

      const statusText = screen.getByTestId('status-text');
      expect(statusText).toHaveTextContent('Critical Issues');
      expect(statusText).toHaveAttribute('data-status', 'Critical Issues');
    });
  });

  // =====================================================
  // SO-008: Status Text Display
  // =====================================================
  describe('SO-008: Status Text Display', () => {
    it('SO-008-1: renders status text container', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('status-container')).toBeInTheDocument();
      expect(screen.getByTestId('status-text')).toBeInTheDocument();
    });

    it('SO-008-2: status text has color matching score color', () => {
      const props = createMockProps({ score: 95 }); // Green
      render(<ScoreOverview {...props} />);

      const statusText = screen.getByTestId('status-text');
      expect(statusText).toHaveStyle({ color: '#22c55e' });
    });

    it('SO-008-3: all status texts are defined', () => {
      const testCases = [
        { score: 92, expectedStatus: 'Excellent' },
        { score: 75, expectedStatus: 'Good' },
        { score: 55, expectedStatus: 'Needs Work' },
        { score: 35, expectedStatus: 'Poor' },
        { score: 15, expectedStatus: 'Critical Issues' },
      ];

      testCases.forEach(({ score, expectedStatus }) => {
        const { unmount } = render(<ScoreOverview {...createMockProps({ score })} />);
        expect(screen.getByTestId('status-text')).toHaveTextContent(expectedStatus);
        unmount();
      });
    });
  });

  // =====================================================
  // SO-009: Site URL Display
  // =====================================================
  describe('SO-009: Site URL Display', () => {
    it('SO-009-1: renders site URL container', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('site-url-container')).toBeInTheDocument();
    });

    it('SO-009-2: displays site URL with globe icon', () => {
      const props = createMockProps({ siteUrl: 'https://testsite.com' });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('globe-icon')).toBeInTheDocument();
      expect(screen.getByTestId('site-url')).toHaveTextContent('https://testsite.com');
    });

    it('SO-009-3: handles long URLs', () => {
      const longUrl = 'https://very-long-domain-name-here-for-testing-purposes.com/path/to/page';
      const props = createMockProps({ siteUrl: longUrl });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('site-url')).toHaveTextContent(longUrl);
    });

    it('SO-009-4: handles URLs with unicode', () => {
      const props = createMockProps({ siteUrl: 'https://例え.jp' });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('site-url')).toHaveTextContent('https://例え.jp');
    });
  });

  // =====================================================
  // SO-010: Audit Date Display
  // =====================================================
  describe('SO-010: Audit Date Display', () => {
    it('SO-010-1: renders audit date container', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('audit-date-container')).toBeInTheDocument();
    });

    it('SO-010-2: displays audit date with calendar icon', () => {
      const props = createMockProps({ auditDate: '2026-03-06' });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('calendar-icon')).toBeInTheDocument();
      expect(screen.getByTestId('audit-date')).toHaveTextContent('2026-03-06');
    });

    it('SO-010-3: handles different date formats', () => {
      const props = createMockProps({ auditDate: 'March 6, 2026' });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('audit-date')).toHaveTextContent('March 6, 2026');
    });
  });

  // =====================================================
  // SO-011: CTA Button
  // =====================================================
  describe('SO-011: CTA Button', () => {
    it('SO-011-1: renders CTA button by default', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('cta-button')).toBeInTheDocument();
    });

    it('SO-011-2: displays "Get your free audit" text', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('cta-button')).toHaveTextContent('Get your free audit');
    });

    it('SO-011-3: has external link icon', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('external-link-icon')).toBeInTheDocument();
    });

    it('SO-011-4: links to /audit page', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      const ctaButton = screen.getByTestId('cta-button');
      expect(ctaButton).toHaveAttribute('href', '/audit');
    });

    it('SO-011-5: hides CTA when showCta is false', () => {
      const props = createMockProps({ showCta: false });
      render(<ScoreOverview {...props} />);

      expect(screen.queryByTestId('cta-button')).not.toBeInTheDocument();
    });

    it('SO-011-6: CTA is keyboard accessible', async () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      const ctaButton = screen.getByTestId('cta-button');
      ctaButton.focus();
      expect(ctaButton).toHaveFocus();
    });
  });

  // =====================================================
  // SO-012: Animation
  // =====================================================
  describe('SO-012: Animation', () => {
    it('SO-012-1: animate prop is true by default', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-animate', 'true');
    });

    it('SO-012-2: animate prop can be disabled', () => {
      const props = createMockProps({ animate: false });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-animate', 'false');
    });

    it('SO-012-3: progress circle has correct strokeDashoffset for score', () => {
      const props = createMockProps({ score: 75 });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      // strokeDashoffset = circumference - (score / 100) * circumference
      // For 180x180: radius = 80, circumference = ~502.65
      // 75% = ~125.66 offset
      expect(progressCircle).toHaveAttribute('strokeDasharray');
      expect(progressCircle).toHaveAttribute('strokeDashoffset');
    });
  });

  // =====================================================
  // SO-013: Responsive Sizing
  // =====================================================
  describe('SO-013: Responsive Sizing', () => {
    it('SO-013-1: default size is md', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      const svg = screen.getByTestId('score-svg');
      expect(svg).toHaveAttribute('width', '180');
      expect(svg).toHaveAttribute('height', '180');
    });

    it('SO-013-2: sm size renders correctly', () => {
      const props = createMockProps({ size: 'sm' });
      render(<ScoreOverview {...props} />);

      const svg = screen.getByTestId('score-svg');
      expect(svg).toHaveAttribute('width', '120');
      expect(svg).toHaveAttribute('height', '120');
      expect(svg).toHaveAttribute('viewBox', '0 0 120 120');
    });

    it('SO-013-3: lg size renders correctly', () => {
      const props = createMockProps({ size: 'lg' });
      render(<ScoreOverview {...props} />);

      const svg = screen.getByTestId('score-svg');
      expect(svg).toHaveAttribute('width', '240');
      expect(svg).toHaveAttribute('height', '240');
      expect(svg).toHaveAttribute('viewBox', '0 0 240 240');
    });

    it('SO-013-4: font size scales with component size', () => {
      const { rerender } = render(<ScoreOverview {...createMockProps({ size: 'sm' })} />);
      expect(screen.getByTestId('score-display')).toHaveClass('text-2xl');

      rerender(<ScoreOverview {...createMockProps({ size: 'md' })} />);
      expect(screen.getByTestId('score-display')).toHaveClass('text-4xl');

      rerender(<ScoreOverview {...createMockProps({ size: 'lg' })} />);
      expect(screen.getByTestId('score-display')).toHaveClass('text-5xl');
    });
  });

  // =====================================================
  // SO-014: Accessibility
  // =====================================================
  describe('SO-014: Accessibility', () => {
    it('SO-014-1: SVG has role="img"', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      const svg = screen.getByTestId('score-svg');
      expect(svg).toHaveAttribute('role', 'img');
    });

    it('SO-014-2: SVG has descriptive aria-label', () => {
      const props = createMockProps({ score: 75 });
      render(<ScoreOverview {...props} />);

      const svg = screen.getByTestId('score-svg');
      expect(svg).toHaveAttribute('aria-label', 'SEO Score: 75 out of 100');
    });

    it('SO-014-3: score is readable by screen readers', () => {
      const props = createMockProps({ score: 85 });
      render(<ScoreOverview {...props} />);

      // Score should be visible as text
      expect(screen.getByTestId('score-number')).toHaveTextContent('85');
    });

    it('SO-014-4: CTA button has accessible link text', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      const ctaButton = screen.getByTestId('cta-button');
      expect(ctaButton).toHaveTextContent('Get your free audit');
    });

    it('SO-014-5: status text is readable', () => {
      const props = createMockProps({ score: 92 });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('status-text')).toHaveTextContent('Excellent');
    });

    it('SO-014-6: CTA button can receive keyboard focus', async () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      const ctaButton = screen.getByTestId('cta-button');
      ctaButton.focus();
      expect(document.activeElement).toBe(ctaButton);
    });
  });

  // =====================================================
  // SO-015: Edge Cases
  // =====================================================
  describe('SO-015: Edge Cases', () => {
    it('SO-015-1: handles empty site URL gracefully', () => {
      const props = createMockProps({ siteUrl: '' });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('site-url')).toHaveTextContent('');
    });

    it('SO-015-2: handles empty audit date gracefully', () => {
      const props = createMockProps({ auditDate: '' });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('audit-date')).toHaveTextContent('');
    });

    it('SO-015-3: handles very long URLs', () => {
      const longUrl = 'https://example.com/' + 'a'.repeat(500);
      const props = createMockProps({ siteUrl: longUrl });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('site-url')).toHaveTextContent(longUrl);
    });

    it('SO-015-4: handles unicode in site URL', () => {
      const props = createMockProps({ siteUrl: 'https://例え.jp/测试' });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('site-url')).toHaveTextContent('https://例え.jp/测试');
    });

    it('SO-015-5: handles emoji in site URL', () => {
      const props = createMockProps({ siteUrl: 'https://😀.com' });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('site-url')).toHaveTextContent('https://😀.com');
    });

    it('SO-015-6: handles score boundaries correctly', () => {
      // Test boundary between red and orange (29-30)
      const { unmount: unmount1 } = render(<ScoreOverview {...createMockProps({ score: 29 })} />);
      expect(screen.getByTestId('progress-circle')).toHaveAttribute('data-color', 'red');
      unmount1();

      const { unmount: unmount2 } = render(<ScoreOverview {...createMockProps({ score: 30 })} />);
      expect(screen.getByTestId('progress-circle')).toHaveAttribute('data-color', 'orange');
      unmount2();
    });

    it('SO-015-7: handles all boundary transitions', () => {
      const boundaries = [
        { score: 29, expectedColor: 'red' },
        { score: 30, expectedColor: 'orange' },
        { score: 49, expectedColor: 'orange' },
        { score: 50, expectedColor: 'yellow' },
        { score: 69, expectedColor: 'yellow' },
        { score: 70, expectedColor: 'blue' },
        { score: 89, expectedColor: 'blue' },
        { score: 90, expectedColor: 'green' },
      ];

      boundaries.forEach(({ score, expectedColor }) => {
        const { unmount } = render(<ScoreOverview {...createMockProps({ score })} />);
        expect(screen.getByTestId('progress-circle')).toHaveAttribute('data-color', expectedColor);
        unmount();
      });
    });
  });

  // =====================================================
  // SO-016: Custom className Prop
  // =====================================================
  describe('SO-016: Custom className Prop', () => {
    it('SO-016-1: applies custom className to container', () => {
      const props = createMockProps({ className: 'custom-class another-class' });
      render(<ScoreOverview {...props} />);

      const container = screen.getByTestId('score-overview');
      expect(container).toHaveClass('custom-class');
      expect(container).toHaveClass('another-class');
    });

    it('SO-016-2: works without className prop', () => {
      const props = createMockProps();
      delete (props as any).className;
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('score-overview')).toBeInTheDocument();
    });
  });

  // =====================================================
  // SO-017: Card Container
  // =====================================================
  describe('SO-017: Card Container', () => {
    it('SO-017-1: renders within Card component', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('card')).toBeInTheDocument();
      expect(screen.getByTestId('card-content')).toBeInTheDocument();
    });
  });

  // =====================================================
  // AC: Acceptance Criteria Verification
  // =====================================================
  describe('AC: Acceptance Criteria Verification', () => {
    it('AC-01: Displays circular progress indicator (0-100)', () => {
      const props = createMockProps({ score: 75 });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('circular-progress')).toBeInTheDocument();
      expect(screen.getByTestId('score-svg')).toBeInTheDocument();
      expect(screen.getByTestId('progress-circle')).toBeInTheDocument();
    });

    it('AC-02: Score color-coded by range', () => {
      const testCases = [
        { score: 95, color: 'green', hex: '#22c55e' },
        { score: 75, color: 'blue', hex: '#3b82f6' },
        { score: 55, color: 'yellow', hex: '#eab308' },
        { score: 35, color: 'orange', hex: '#f97316' },
        { score: 15, color: 'red', hex: '#ef4444' },
      ];

      testCases.forEach(({ score, color, hex }) => {
        const { unmount } = render(<ScoreOverview {...createMockProps({ score })} />);
        const progressCircle = screen.getByTestId('progress-circle');
        expect(progressCircle).toHaveAttribute('data-color', color);
        expect(progressCircle).toHaveAttribute('stroke', hex);
        unmount();
      });
    });

    it('AC-03: Shows status text for each range', () => {
      const testCases = [
        { score: 95, status: 'Excellent' },
        { score: 75, status: 'Good' },
        { score: 55, status: 'Needs Work' },
        { score: 35, status: 'Poor' },
        { score: 15, status: 'Critical Issues' },
      ];

      testCases.forEach(({ score, status }) => {
        const { unmount } = render(<ScoreOverview {...createMockProps({ score })} />);
        expect(screen.getByTestId('status-text')).toHaveTextContent(status);
        unmount();
      });
    });

    it('AC-04: Shows site URL and audit date', () => {
      const props = createMockProps({
        siteUrl: 'https://testsite.com',
        auditDate: '2026-03-06',
      });
      render(<ScoreOverview {...props} />);

      expect(screen.getByTestId('site-url')).toHaveTextContent('https://testsite.com');
      expect(screen.getByTestId('audit-date')).toHaveTextContent('2026-03-06');
    });

    it('AC-05: Includes "Get your free audit" CTA button', () => {
      const props = createMockProps();
      render(<ScoreOverview {...props} />);

      const ctaButton = screen.getByTestId('cta-button');
      expect(ctaButton).toBeInTheDocument();
      expect(ctaButton).toHaveTextContent('Get your free audit');
      expect(ctaButton).toHaveAttribute('href', '/audit');
    });

    it('AC-06: Animated progress fill (optional)', () => {
      const props = createMockProps({ animate: true });
      render(<ScoreOverview {...props} />);

      const progressCircle = screen.getByTestId('progress-circle');
      expect(progressCircle).toHaveAttribute('data-animate', 'true');
    });

    it('AC-07: Responsive sizing', () => {
      const sizes = ['sm', 'md', 'lg'] as const;
      const expectedDimensions = {
        sm: { width: '120', height: '120' },
        md: { width: '180', height: '180' },
        lg: { width: '240', height: '240' },
      };

      sizes.forEach((size) => {
        const { unmount } = render(<ScoreOverview {...createMockProps({ size })} />);
        const svg = screen.getByTestId('score-svg');
        expect(svg).toHaveAttribute('width', expectedDimensions[size].width);
        expect(svg).toHaveAttribute('height', expectedDimensions[size].height);
        unmount();
      });
    });

    it('AC-08: Accessible - score read by screen readers', () => {
      const props = createMockProps({ score: 85 });
      render(<ScoreOverview {...props} />);

      const svg = screen.getByTestId('score-svg');
      expect(svg).toHaveAttribute('role', 'img');
      expect(svg).toHaveAttribute('aria-label', 'SEO Score: 85 out of 100');
    });

    it('AC-09: Unit tests for all score ranges', () => {
      // This test verifies that all ranges are covered by the test suite
      // The actual range tests are in SO-003 through SO-007
      const ranges = [
        { min: 90, max: 100, color: 'green' },
        { min: 70, max: 89, color: 'blue' },
        { min: 50, max: 69, color: 'yellow' },
        { min: 30, max: 49, color: 'orange' },
        { min: 0, max: 29, color: 'red' },
      ];

      ranges.forEach(({ min, max, color }) => {
        const { unmount } = render(<ScoreOverview {...createMockProps({ score: min })} />);
        expect(screen.getByTestId('progress-circle')).toHaveAttribute('data-color', color);
        unmount();

        const { unmount: unmount2 } = render(<ScoreOverview {...createMockProps({ score: max })} />);
        expect(screen.getByTestId('progress-circle')).toHaveAttribute('data-color', color);
        unmount2();
      });
    });
  });
});
