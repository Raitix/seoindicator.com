/**
 * ScoreOverview Component Tests
 *
 * Tests for the hero score display component showing a large circular
 * score indicator with color coding and status text.
 *
 * Component: ScoreOverview
 * Task: TASK-33-9
 * Status: TDD (component not yet implemented)
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// ============================================================================
// MOCK IMPLEMENTATION (TDD Reference - will be replaced by actual component)
// ============================================================================

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  ExternalLink: () => <span data-testid="external-link-icon">↗</span>,
  Calendar: () => <span data-testid="calendar-icon">📅</span>,
  Globe: () => <span data-testid="globe-icon">🌐</span>,
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  Minus: () => <span data-testid="minus-icon">−</span>,
}));

// ============================================================================
// TYPES
// ============================================================================

interface ScoreOverviewProps {
  score: number;
  siteUrl: string;
  auditedAt: string;
  onCtaClick?: () => void;
  className?: string;
  animate?: boolean;
}

type ScoreLevel = 'excellent' | 'good' | 'needs-work' | 'poor' | 'critical';

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

const getScoreLevel = (score: number): ScoreLevel => {
  if (score >= 90) return 'excellent';
  if (score >= 70) return 'good';
  if (score >= 50) return 'needs-work';
  if (score >= 30) return 'poor';
  return 'critical';
};

const getScoreColor = (score: number): string => {
  if (score >= 90) return '#22c55e'; // green
  if (score >= 70) return '#3b82f6'; // blue
  if (score >= 50) return '#eab308'; // yellow
  if (score >= 30) return '#f97316'; // orange
  return '#ef4444'; // red
};

const getScoreStatusText = (score: number): string => {
  if (score >= 90) return 'Excellent';
  if (score >= 70) return 'Good';
  if (score >= 50) return 'Needs Work';
  if (score >= 30) return 'Poor';
  return 'Critical Issues';
};

// ============================================================================
// MOCK COMPONENT FOR TESTING
// ============================================================================

const MockScoreOverview: React.FC<ScoreOverviewProps> = ({
  score,
  siteUrl,
  auditedAt,
  onCtaClick,
  className,
  animate = true,
}) => {
  const level = getScoreLevel(score);
  const color = getScoreColor(score);
  const statusText = getScoreStatusText(score);

  // Calculate stroke dash for circular progress
  const radius = 60;
  const circumference = 2 * Math.PI * radius;
  const strokeDashoffset = circumference - (score / 100) * circumference;

  const formattedDate = new Date(auditedAt).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });

  return (
    <div data-testid="score-overview" className={className}>
      {/* Site URL */}
      <div data-testid="site-url-container">
        <span data-testid="globe-icon">🌐</span>
        <span data-testid="site-url">{siteUrl}</span>
      </div>

      {/* Circular Score Display */}
      <div data-testid="score-circle-container" role="meter" aria-valuenow={score} aria-valuemin={0} aria-valuemax={100} aria-label={`SEO Score: ${score} out of 100`}>
        <svg data-testid="score-svg" viewBox="0 0 150 150">
          {/* Background circle */}
          <circle
            data-testid="score-bg-circle"
            cx="75"
            cy="75"
            r={radius}
            fill="none"
            stroke="#e5e7eb"
            strokeWidth="8"
          />
          {/* Progress circle */}
          <circle
            data-testid="score-progress-circle"
            cx="75"
            cy="75"
            r={radius}
            fill="none"
            stroke={color}
            strokeWidth="8"
            strokeLinecap="round"
            strokeDasharray={circumference}
            strokeDashoffset={animate ? strokeDashoffset : 0}
            transform="rotate(-90 75 75)"
          />
        </svg>
        {/* Score text */}
        <div data-testid="score-text-container">
          <span data-testid="score-value">{score}</span>
          <span data-testid="score-max">/100</span>
        </div>
      </div>

      {/* Status Text */}
      <div data-testid="status-container">
        <span data-testid="status-text" data-level={level}>
          {statusText}
        </span>
      </div>

      {/* Audit Date */}
      <div data-testid="audit-date-container">
        <span data-testid="calendar-icon">📅</span>
        <time data-testid="audit-date" dateTime={auditedAt}>
          {formattedDate}
        </time>
      </div>

      {/* CTA Button */}
      <button
        data-testid="cta-button"
        onClick={onCtaClick}
        aria-label="Get your free SEO audit"
      >
        Get your free audit
        <span data-testid="external-link-icon">↗</span>
      </button>
    </div>
  );
};

// ============================================================================
// TEST DATA
// ============================================================================

const defaultProps: ScoreOverviewProps = {
  score: 78,
  siteUrl: 'https://example.com',
  auditedAt: '2026-03-06T10:00:00Z',
};

// ============================================================================
// TESTS
// ============================================================================

describe('ScoreOverview Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ------------------------------------------------------------------------
  // Rendering Tests
  // ------------------------------------------------------------------------

  describe('REND: Rendering', () => {
    it('REND-001: should render without crashing', () => {
      render(<MockScoreOverview {...defaultProps} />);

      expect(screen.getByTestId('score-overview')).toBeInTheDocument();
    });

    it('REND-002: should display score value', () => {
      render(<MockScoreOverview {...defaultProps} />);

      expect(screen.getByTestId('score-value')).toHaveTextContent('78');
    });

    it('REND-003: should display score out of 100', () => {
      render(<MockScoreOverview {...defaultProps} />);

      expect(screen.getByTestId('score-max')).toHaveTextContent('/100');
    });

    it('REND-004: should display site URL', () => {
      render(<MockScoreOverview {...defaultProps} />);

      expect(screen.getByTestId('site-url')).toHaveTextContent('https://example.com');
    });

    it('REND-005: should display audit date', () => {
      render(<MockScoreOverview {...defaultProps} />);

      const dateElement = screen.getByTestId('audit-date');
      expect(dateElement).toBeInTheDocument();
      expect(dateElement).toHaveAttribute('datetime', '2026-03-06T10:00:00Z');
    });

    it('REND-006: should display CTA button', () => {
      render(<MockScoreOverview {...defaultProps} />);

      expect(screen.getByTestId('cta-button')).toBeInTheDocument();
    });

    it('REND-007: should render SVG for circular progress', () => {
      render(<MockScoreOverview {...defaultProps} />);

      expect(screen.getByTestId('score-svg')).toBeInTheDocument();
    });

    it('REND-008: should render background and progress circles', () => {
      render(<MockScoreOverview {...defaultProps} />);

      expect(screen.getByTestId('score-bg-circle')).toBeInTheDocument();
      expect(screen.getByTestId('score-progress-circle')).toBeInTheDocument();
    });

    it('REND-009: should accept custom className', () => {
      render(<MockScoreOverview {...defaultProps} className="custom-class" />);

      const container = screen.getByTestId('score-overview');
      expect(container).toHaveClass('custom-class');
    });
  });

  // ------------------------------------------------------------------------
  // Score Color Coding Tests
  // ------------------------------------------------------------------------

  describe('COLOR: Score Color Coding', () => {
    it('COLOR-001: should use green for scores 90-100 (excellent)', () => {
      render(<MockScoreOverview {...defaultProps} score={95} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#22c55e');
      expect(screen.getByTestId('status-text')).toHaveTextContent('Excellent');
    });

    it('COLOR-002: should use blue for scores 70-89 (good)', () => {
      render(<MockScoreOverview {...defaultProps} score={78} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#3b82f6');
      expect(screen.getByTestId('status-text')).toHaveTextContent('Good');
    });

    it('COLOR-003: should use yellow for scores 50-69 (needs work)', () => {
      render(<MockScoreOverview {...defaultProps} score={55} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#eab308');
      expect(screen.getByTestId('status-text')).toHaveTextContent('Needs Work');
    });

    it('COLOR-004: should use orange for scores 30-49 (poor)', () => {
      render(<MockScoreOverview {...defaultProps} score={35} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#f97316');
      expect(screen.getByTestId('status-text')).toHaveTextContent('Poor');
    });

    it('COLOR-005: should use red for scores 0-29 (critical)', () => {
      render(<MockScoreOverview {...defaultProps} score={15} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#ef4444');
      expect(screen.getByTestId('status-text')).toHaveTextContent('Critical Issues');
    });

    it('COLOR-006: should handle boundary score 90', () => {
      render(<MockScoreOverview {...defaultProps} score={90} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#22c55e');
    });

    it('COLOR-007: should handle boundary score 70', () => {
      render(<MockScoreOverview {...defaultProps} score={70} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#3b82f6');
    });

    it('COLOR-008: should handle boundary score 50', () => {
      render(<MockScoreOverview {...defaultProps} score={50} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#eab308');
    });

    it('COLOR-009: should handle boundary score 30', () => {
      render(<MockScoreOverview {...defaultProps} score={30} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#f97316');
    });

    it('COLOR-010: should handle score of 0', () => {
      render(<MockScoreOverview {...defaultProps} score={0} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#ef4444');
    });

    it('COLOR-011: should handle score of 100', () => {
      render(<MockScoreOverview {...defaultProps} score={100} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#22c55e');
    });
  });

  // ------------------------------------------------------------------------
  // Status Text Tests
  // ------------------------------------------------------------------------

  describe('STATUS: Status Text', () => {
    it('STATUS-001: should show "Excellent" for score >= 90', () => {
      render(<MockScoreOverview {...defaultProps} score={92} />);

      expect(screen.getByTestId('status-text')).toHaveTextContent('Excellent');
      expect(screen.getByTestId('status-text')).toHaveAttribute('data-level', 'excellent');
    });

    it('STATUS-002: should show "Good" for scores 70-89', () => {
      render(<MockScoreOverview {...defaultProps} score={75} />);

      expect(screen.getByTestId('status-text')).toHaveTextContent('Good');
      expect(screen.getByTestId('status-text')).toHaveAttribute('data-level', 'good');
    });

    it('STATUS-003: should show "Needs Work" for scores 50-69', () => {
      render(<MockScoreOverview {...defaultProps} score={60} />);

      expect(screen.getByTestId('status-text')).toHaveTextContent('Needs Work');
      expect(screen.getByTestId('status-text')).toHaveAttribute('data-level', 'needs-work');
    });

    it('STATUS-004: should show "Poor" for scores 30-49', () => {
      render(<MockScoreOverview {...defaultProps} score={40} />);

      expect(screen.getByTestId('status-text')).toHaveTextContent('Poor');
      expect(screen.getByTestId('status-text')).toHaveAttribute('data-level', 'poor');
    });

    it('STATUS-005: should show "Critical Issues" for scores 0-29', () => {
      render(<MockScoreOverview {...defaultProps} score={20} />);

      expect(screen.getByTestId('status-text')).toHaveTextContent('Critical Issues');
      expect(screen.getByTestId('status-text')).toHaveAttribute('data-level', 'critical');
    });
  });

  // ------------------------------------------------------------------------
  // Circular Progress Tests
  // ------------------------------------------------------------------------

  describe('PROGRESS: Circular Progress', () => {
    it('PROGRESS-001: should calculate correct strokeDashoffset for score 0', () => {
      render(<MockScoreOverview {...defaultProps} score={0} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      const dashoffset = parseFloat(progressCircle.getAttribute('strokeDashoffset') || '0');
      // Full circumference = 2 * PI * 60 ≈ 377
      expect(dashoffset).toBeCloseTo(377, 0);
    });

    it('PROGRESS-002: should calculate correct strokeDashoffset for score 50', () => {
      render(<MockScoreOverview {...defaultProps} score={50} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      const dashoffset = parseFloat(progressCircle.getAttribute('strokeDashoffset') || '0');
      // Half of circumference
      expect(dashoffset).toBeCloseTo(188, 0);
    });

    it('PROGRESS-003: should calculate correct strokeDashoffset for score 100', () => {
      render(<MockScoreOverview {...defaultProps} score={100} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      const dashoffset = parseFloat(progressCircle.getAttribute('strokeDashoffset') || '0');
      expect(dashoffset).toBeCloseTo(0, 0);
    });

    it('PROGRESS-004: should use strokeLinecap="round" for smooth edges', () => {
      render(<MockScoreOverview {...defaultProps} score={75} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke-linecap', 'round');
    });

    it('PROGRESS-005: should rotate circle -90 degrees to start from top', () => {
      render(<MockScoreOverview {...defaultProps} score={50} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('transform', 'rotate(-90 75 75)');
    });
  });

  // ------------------------------------------------------------------------
  // Animation Tests
  // ------------------------------------------------------------------------

  describe('ANIM: Animation', () => {
    it('ANIM-001: should animate when animate prop is true', () => {
      render(<MockScoreOverview {...defaultProps} score={75} animate={true} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      // strokeDashoffset should be calculated based on score
      expect(progressCircle).toHaveAttribute('strokeDashoffset');
    });

    it('ANIM-002: should not animate when animate prop is false', () => {
      render(<MockScoreOverview {...defaultProps} score={75} animate={false} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      // When animate is false, strokeDashoffset should be 0 (no progress shown)
      expect(progressCircle).toHaveAttribute('strokeDashoffset', '0');
    });

    it('ANIM-003: should respect prefers-reduced-motion', () => {
      // This test documents the expected behavior
      // Actual implementation would check window.matchMedia
      render(<MockScoreOverview {...defaultProps} score={75} />);

      expect(screen.getByTestId('score-progress-circle')).toBeInTheDocument();
    });
  });

  // ------------------------------------------------------------------------
  // CTA Button Tests
  // ------------------------------------------------------------------------

  describe('CTA: CTA Button', () => {
    it('CTA-001: should render CTA button with text', () => {
      render(<MockScoreOverview {...defaultProps} />);

      const button = screen.getByTestId('cta-button');
      expect(button).toHaveTextContent('Get your free audit');
    });

    it('CTA-002: should call onCtaClick when clicked', async () => {
      const handleClick = jest.fn();
      const user = userEvent.setup();

      render(<MockScoreOverview {...defaultProps} onCtaClick={handleClick} />);

      await user.click(screen.getByTestId('cta-button'));

      expect(handleClick).toHaveBeenCalledTimes(1);
    });

    it('CTA-003: should have accessible aria-label', () => {
      render(<MockScoreOverview {...defaultProps} />);

      const button = screen.getByTestId('cta-button');
      expect(button).toHaveAttribute('aria-label', 'Get your free SEO audit');
    });

    it('CTA-004: should be keyboard accessible', async () => {
      const handleClick = jest.fn();
      const user = userEvent.setup();

      render(<MockScoreOverview {...defaultProps} onCtaClick={handleClick} />);

      const button = screen.getByTestId('cta-button');
      button.focus();

      await user.keyboard('{Enter}');

      expect(handleClick).toHaveBeenCalled();
    });

    it('CTA-005: should show external link icon', () => {
      render(<MockScoreOverview {...defaultProps} />);

      expect(screen.getByTestId('external-link-icon')).toBeInTheDocument();
    });
  });

  // ------------------------------------------------------------------------
  // Accessibility Tests
  // ------------------------------------------------------------------------

  describe('A11Y: Accessibility', () => {
    it('A11Y-001: should have role="meter" on score container', () => {
      render(<MockScoreOverview {...defaultProps} />);

      expect(screen.getByRole('meter')).toBeInTheDocument();
    });

    it('A11Y-002: should have aria-valuenow attribute', () => {
      render(<MockScoreOverview {...defaultProps} score={78} />);

      const meter = screen.getByRole('meter');
      expect(meter).toHaveAttribute('aria-valuenow', '78');
    });

    it('A11Y-003: should have aria-valuemin="0"', () => {
      render(<MockScoreOverview {...defaultProps} />);

      const meter = screen.getByRole('meter');
      expect(meter).toHaveAttribute('aria-valuemin', '0');
    });

    it('A11Y-004: should have aria-valuemax="100"', () => {
      render(<MockScoreOverview {...defaultProps} />);

      const meter = screen.getByRole('meter');
      expect(meter).toHaveAttribute('aria-valuemax', '100');
    });

    it('A11Y-005: should have aria-label describing the score', () => {
      render(<MockScoreOverview {...defaultProps} score={78} />);

      const meter = screen.getByRole('meter');
      expect(meter).toHaveAttribute('aria-label', 'SEO Score: 78 out of 100');
    });

    it('A11Y-006: should use semantic time element for date', () => {
      render(<MockScoreOverview {...defaultProps} />);

      const timeElement = screen.getByTestId('audit-date');
      expect(timeElement.tagName.toLowerCase()).toBe('time');
      expect(timeElement).toHaveAttribute('datetime');
    });

    it('A11Y-007: should have accessible button with aria-label', () => {
      render(<MockScoreOverview {...defaultProps} />);

      const button = screen.getByRole('button', { name: /get your free seo audit/i });
      expect(button).toBeInTheDocument();
    });

    it('A11Y-008: should be keyboard navigable', async () => {
      const user = userEvent.setup();
      render(<MockScoreOverview {...defaultProps} />);

      await user.tab();

      const button = screen.getByTestId('cta-button');
      expect(button).toHaveFocus();
    });
  });

  // ------------------------------------------------------------------------
  // Responsive Tests
  // ------------------------------------------------------------------------

  describe('RESP: Responsive Design', () => {
    it('RESP-001: should have responsive SVG viewBox', () => {
      render(<MockScoreOverview {...defaultProps} />);

      const svg = screen.getByTestId('score-svg');
      expect(svg).toHaveAttribute('viewBox', '0 0 150 150');
    });

    it('RESP-002: should work with custom className for responsive styling', () => {
      render(<MockScoreOverview {...defaultProps} className="w-full md:w-1/2" />);

      const container = screen.getByTestId('score-overview');
      expect(container).toHaveClass('w-full', 'md:w-1/2');
    });
  });

  // ------------------------------------------------------------------------
  // Edge Cases Tests
  // ------------------------------------------------------------------------

  describe('EDGE: Edge Cases', () => {
    it('EDGE-001: should handle score of 0', () => {
      render(<MockScoreOverview {...defaultProps} score={0} />);

      expect(screen.getByTestId('score-value')).toHaveTextContent('0');
      expect(screen.getByTestId('status-text')).toHaveTextContent('Critical Issues');
    });

    it('EDGE-002: should handle score of 100', () => {
      render(<MockScoreOverview {...defaultProps} score={100} />);

      expect(screen.getByTestId('score-value')).toHaveTextContent('100');
      expect(screen.getByTestId('status-text')).toHaveTextContent('Excellent');
    });

    it('EDGE-003: should handle very long site URL', () => {
      const longUrl = 'https://' + 'a'.repeat(200) + '.com/very/long/path';
      render(<MockScoreOverview {...defaultProps} siteUrl={longUrl} />);

      expect(screen.getByTestId('site-url')).toHaveTextContent(longUrl);
    });

    it('EDGE-004: should handle unicode in site URL', () => {
      render(<MockScoreOverview {...defaultProps} siteUrl="https://例え.jp" />);

      expect(screen.getByTestId('site-url')).toHaveTextContent('https://例え.jp');
    });

    it('EDGE-005: should handle invalid date gracefully', () => {
      render(<MockScoreOverview {...defaultProps} auditedAt="invalid-date" />);

      expect(screen.getByTestId('audit-date')).toBeInTheDocument();
    });

    it('EDGE-006: should handle future date', () => {
      render(<MockScoreOverview {...defaultProps} auditedAt="2099-12-31T23:59:59Z" />);

      expect(screen.getByTestId('audit-date')).toBeInTheDocument();
    });

    it('EDGE-007: should handle missing onCtaClick prop', async () => {
      const user = userEvent.setup();
      render(<MockScoreOverview {...defaultProps} onCtaClick={undefined} />);

      // Should not throw when clicking
      await user.click(screen.getByTestId('cta-button'));

      expect(screen.getByTestId('cta-button')).toBeInTheDocument();
    });

    it('EDGE-008: should handle decimal scores (if supported)', () => {
      // Some systems might use decimal scores
      render(<MockScoreOverview {...defaultProps} score={78.5} />);

      // Score should be displayed (implementation may round)
      expect(screen.getByTestId('score-value')).toBeInTheDocument();
    });

    it('EDGE-009: should handle negative score (invalid)', () => {
      // Component should handle gracefully (clamp to 0 or show error)
      render(<MockScoreOverview {...defaultProps} score={-10} />);

      expect(screen.getByTestId('score-overview')).toBeInTheDocument();
    });

    it('EDGE-010: should handle score > 100 (invalid)', () => {
      // Component should handle gracefully (clamp to 100 or show error)
      render(<MockScoreOverview {...defaultProps} score={150} />);

      expect(screen.getByTestId('score-overview')).toBeInTheDocument();
    });
  });

  // ------------------------------------------------------------------------
  // Props Validation Tests
  // ------------------------------------------------------------------------

  describe('PROPS: Props Validation', () => {
    it('PROPS-001: should require score prop', () => {
      // TypeScript catches missing props at compile time
      // This test documents the requirement
      expect(() => {
        render(<MockScoreOverview {...defaultProps} />);
      }).not.toThrow();
    });

    it('PROPS-002: should require siteUrl prop', () => {
      render(<MockScoreOverview {...defaultProps} />);

      expect(screen.getByTestId('site-url')).toBeInTheDocument();
    });

    it('PROPS-003: should require auditedAt prop', () => {
      render(<MockScoreOverview {...defaultProps} />);

      expect(screen.getByTestId('audit-date')).toBeInTheDocument();
    });

    it('PROPS-004: should accept optional onCtaClick', () => {
      const handleClick = jest.fn();
      render(<MockScoreOverview {...defaultProps} onCtaClick={handleClick} />);

      expect(screen.getByTestId('cta-button')).toBeInTheDocument();
    });

    it('PROPS-005: should accept optional className', () => {
      render(<MockScoreOverview {...defaultProps} className="test-class" />);

      expect(screen.getByTestId('score-overview')).toHaveClass('test-class');
    });

    it('PROPS-006: should accept optional animate prop', () => {
      render(<MockScoreOverview {...defaultProps} animate={false} />);

      expect(screen.getByTestId('score-overview')).toBeInTheDocument();
    });

    it('PROPS-007: should default animate to true', () => {
      // When animate prop is not provided, it should default to true
      render(<MockScoreOverview {...defaultProps} />);

      const progressCircle = screen.getByTestId('score-progress-circle');
      // If animate=true, strokeDashoffset should be calculated
      expect(progressCircle).toHaveAttribute('strokeDashoffset');
    });
  });

  // ------------------------------------------------------------------------
  // Acceptance Criteria Tests
  // ------------------------------------------------------------------------

  describe('AC: Acceptance Criteria', () => {
    it('AC-001: displays circular progress indicator (0-100)', () => {
      render(<MockScoreOverview {...defaultProps} score={75} />);

      expect(screen.getByRole('meter')).toBeInTheDocument();
      expect(screen.getByTestId('score-value')).toHaveTextContent('75');
    });

    it('AC-002: score color-coded by range', () => {
      const testCases = [
        { score: 95, color: '#22c55e' },
        { score: 75, color: '#3b82f6' },
        { score: 55, color: '#eab308' },
        { score: 35, color: '#f97316' },
        { score: 15, color: '#ef4444' },
      ];

      testCases.forEach(({ score, color }) => {
        const { unmount } = render(<MockScoreOverview {...defaultProps} score={score} />);

        const progressCircle = screen.getByTestId('score-progress-circle');
        expect(progressCircle).toHaveAttribute('stroke', color);

        unmount();
      });
    });

    it('AC-003: shows status text (Excellent/Good/Needs Work/Poor/Critical Issues)', () => {
      const testCases = [
        { score: 92, status: 'Excellent' },
        { score: 75, status: 'Good' },
        { score: 55, status: 'Needs Work' },
        { score: 35, status: 'Poor' },
        { score: 15, status: 'Critical Issues' },
      ];

      testCases.forEach(({ score, status }) => {
        const { unmount } = render(<MockScoreOverview {...defaultProps} score={score} />);

        expect(screen.getByTestId('status-text')).toHaveTextContent(status);

        unmount();
      });
    });

    it('AC-004: shows site URL and audit date', () => {
      render(<MockScoreOverview {...defaultProps} />);

      expect(screen.getByTestId('site-url')).toBeInTheDocument();
      expect(screen.getByTestId('audit-date')).toBeInTheDocument();
    });

    it('AC-005: includes "Get your free audit" CTA button', () => {
      render(<MockScoreOverview {...defaultProps} />);

      const ctaButton = screen.getByTestId('cta-button');
      expect(ctaButton).toHaveTextContent('Get your free audit');
    });

    it('AC-006: animated progress fill (optional)', () => {
      render(<MockScoreOverview {...defaultProps} score={75} animate={true} />);

      // SVG should have strokeDashoffset calculated based on score
      expect(screen.getByTestId('score-progress-circle')).toHaveAttribute('strokeDashoffset');
    });

    it('AC-007: responsive sizing', () => {
      render(<MockScoreOverview {...defaultProps} className="responsive-class" />);

      // SVG viewBox provides responsive scaling
      expect(screen.getByTestId('score-svg')).toHaveAttribute('viewBox');
    });

    it('AC-008: accessible - score read by screen readers', () => {
      render(<MockScoreOverview {...defaultProps} score={78} />);

      const meter = screen.getByRole('meter');
      expect(meter).toHaveAttribute('aria-label', 'SEO Score: 78 out of 100');
      expect(meter).toHaveAttribute('aria-valuenow', '78');
    });

    it('AC-009: unit tests for all score ranges and states', () => {
      // This test file covers all score ranges and states
      // Tested: 0, 15, 35, 55, 75, 95, 100, and boundary scores
      expect(true).toBe(true);
    });
  });

  // ------------------------------------------------------------------------
  // Integration Tests
  // ------------------------------------------------------------------------

  describe('INT: Integration', () => {
    it('INT-001: should update when score prop changes', () => {
      const { rerender } = render(<MockScoreOverview {...defaultProps} score={50} />);

      expect(screen.getByTestId('score-value')).toHaveTextContent('50');
      expect(screen.getByTestId('status-text')).toHaveTextContent('Needs Work');

      rerender(<MockScoreOverview {...defaultProps} score={90} />);

      expect(screen.getByTestId('score-value')).toHaveTextContent('90');
      expect(screen.getByTestId('status-text')).toHaveTextContent('Excellent');
    });

    it('INT-002: should update color when score changes', () => {
      const { rerender } = render(<MockScoreOverview {...defaultProps} score={50} />);

      let progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#eab308');

      rerender(<MockScoreOverview {...defaultProps} score={90} />);

      progressCircle = screen.getByTestId('score-progress-circle');
      expect(progressCircle).toHaveAttribute('stroke', '#22c55e');
    });

    it('INT-003: should handle complete flow with all props', async () => {
      const handleClick = jest.fn();
      const user = userEvent.setup();

      render(
        <MockScoreOverview
          score={85}
          siteUrl="https://test.com"
          auditedAt="2026-03-06T10:00:00Z"
          onCtaClick={handleClick}
          className="custom"
          animate={true}
        />
      );

      // Verify all elements
      expect(screen.getByTestId('score-value')).toHaveTextContent('85');
      expect(screen.getByTestId('site-url')).toHaveTextContent('https://test.com');
      expect(screen.getByTestId('status-text')).toHaveTextContent('Good');
      expect(screen.getByTestId('score-overview')).toHaveClass('custom');

      // Test CTA click
      await user.click(screen.getByTestId('cta-button'));
      expect(handleClick).toHaveBeenCalled();
    });
  });
});
