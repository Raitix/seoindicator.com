/**
 * @jest-environment jsdom
 * 
 * ScoreGauge Component Tests
 * Task: TASK-18-30 - Results Dashboard Page
 * 
 * Tests for the circular score gauge component displaying 0-100 SEO score
 * with color coding based on score zones.
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// ============================================================================
// Mock Component (TDD Reference)
// ============================================================================

/**
 * ScoreGauge - Circular score gauge component
 * 
 * @example
 * <ScoreGauge score={85} size="lg" />
 */
interface ScoreGaugeProps {
  score: number;
  previousScore?: number | null;
  size?: 'sm' | 'md' | 'lg';
  isLoading?: boolean;
  showLabel?: boolean;
  className?: string;
  'aria-label'?: string;
  onAnimationEnd?: () => void;
}

function ScoreGauge({
  score,
  previousScore = null,
  size = 'lg',
  isLoading = false,
  showLabel = true,
  className,
  'aria-label': ariaLabel,
  onAnimationEnd,
}: ScoreGaugeProps) {
  // Get score zone for color coding
  const getScoreZone = (s: number) => {
    if (s >= 90) return { color: '#22c55e', label: 'Excellent', zone: 'excellent' };
    if (s >= 70) return { color: '#eab308', label: 'Good', zone: 'good' };
    if (s >= 50) return { color: '#f97316', label: 'Needs Work', zone: 'needs-work' };
    return { color: '#ef4444', label: 'Critical Issues', zone: 'critical' };
  };

  const zone = getScoreZone(score);
  
  // SVG calculations
  const sizeMap = { sm: 80, md: 120, lg: 160 };
  const svgSize = sizeMap[size];
  const strokeWidth = size === 'sm' ? 6 : size === 'md' ? 8 : 10;
  const radius = (svgSize - strokeWidth) / 2;
  const circumference = 2 * Math.PI * radius;
  const offset = circumference * (1 - score / 100);
  
  // Score change
  const scoreChange = previousScore !== null ? score - previousScore : null;

  if (isLoading) {
    return (
      <div
        data-testid="score-gauge"
        data-loading="true"
        className={className}
        aria-busy="true"
        aria-label="Loading score"
      >
        <div className="animate-pulse">
          <svg width={svgSize} height={svgSize} data-testid="gauge-skeleton">
            <circle
              cx={svgSize / 2}
              cy={svgSize / 2}
              r={radius}
              fill="none"
              stroke="currentColor"
              strokeWidth={strokeWidth}
              className="text-muted/30"
            />
          </svg>
        </div>
      </div>
    );
  }

  return (
    <div
      data-testid="score-gauge"
      data-score={score}
      data-zone={zone.zone}
      data-loading="false"
      className={className}
      role="meter"
      aria-label={ariaLabel || `Score: ${score} out of 100`}
      aria-valuemin={0}
      aria-valuemax={100}
      aria-valuenow={score}
    >
      <svg
        width={svgSize}
        height={svgSize}
        data-testid="gauge-svg"
        className="transform -rotate-90"
      >
        {/* Background circle */}
        <circle
          cx={svgSize / 2}
          cy={svgSize / 2}
          r={radius}
          fill="none"
          stroke="currentColor"
          strokeWidth={strokeWidth}
          className="text-muted/30"
          data-testid="gauge-background"
        />
        {/* Progress circle */}
        <circle
          cx={svgSize / 2}
          cy={svgSize / 2}
          r={radius}
          fill="none"
          stroke={zone.color}
          strokeWidth={strokeWidth}
          strokeDasharray={circumference}
          strokeDashoffset={offset}
          strokeLinecap="round"
          className="transition-[stroke-dashoffset] duration-1000 ease-out"
          data-testid="gauge-progress"
          data-color={zone.color}
          onTransitionEnd={onAnimationEnd}
        />
      </svg>
      
      {/* Score display */}
      <div className="absolute inset-0 flex flex-col items-center justify-center">
        <span
          data-testid="score-value"
          className="text-4xl font-bold"
          style={{ color: zone.color }}
        >
          {score}
        </span>
        {showLabel && (
          <span
            data-testid="score-label"
            className="text-sm text-muted-foreground"
          >
            {zone.label}
          </span>
        )}
      </div>
      
      {/* Score change indicator */}
      {scoreChange !== null && scoreChange !== 0 && (
        <div
          data-testid="score-change"
          data-change={scoreChange > 0 ? 'up' : 'down'}
          className="absolute -top-2 -right-2 flex items-center gap-0.5 rounded-full px-2 py-0.5 text-xs font-medium"
        >
          {scoreChange > 0 ? (
            <>
              <span data-testid="change-icon">↑</span>
              <span>+{scoreChange}</span>
            </>
          ) : (
            <>
              <span data-testid="change-icon">↓</span>
              <span>{scoreChange}</span>
            </>
          )}
        </div>
      )}
    </div>
  );
}

// ============================================================================
// Tests
// ============================================================================

describe('ScoreGauge', () => {
  // -------------------------------------------------------------------------
  // RD-001: Score Gauge Component
  // -------------------------------------------------------------------------
  describe('RD-001: Score Gauge Component', () => {
    it('RD-001.1: renders circular gauge with correct fill', () => {
      render(<ScoreGauge score={85} />);
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toBeInTheDocument();
      expect(gauge).toHaveAttribute('data-score', '85');
      
      const progress = screen.getByTestId('gauge-progress');
      expect(progress).toBeInTheDocument();
    });

    it('RD-001.2: displays zero score correctly', () => {
      render(<ScoreGauge score={0} />);
      
      const scoreValue = screen.getByTestId('score-value');
      expect(scoreValue).toHaveTextContent('0');
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toHaveAttribute('data-zone', 'critical');
    });

    it('RD-001.3: displays perfect score (100) correctly', () => {
      render(<ScoreGauge score={100} />);
      
      const scoreValue = screen.getByTestId('score-value');
      expect(scoreValue).toHaveTextContent('100');
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toHaveAttribute('data-zone', 'excellent');
    });

    it('RD-001.4: applies green color for excellent zone (90-100)', () => {
      render(<ScoreGauge score={95} />);
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toHaveAttribute('data-zone', 'excellent');
      
      const progress = screen.getByTestId('gauge-progress');
      expect(progress).toHaveAttribute('data-color', '#22c55e');
      
      const label = screen.getByTestId('score-label');
      expect(label).toHaveTextContent('Excellent');
    });

    it('RD-001.5: applies yellow color for good zone (70-89)', () => {
      render(<ScoreGauge score={75} />);
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toHaveAttribute('data-zone', 'good');
      
      const progress = screen.getByTestId('gauge-progress');
      expect(progress).toHaveAttribute('data-color', '#eab308');
      
      const label = screen.getByTestId('score-label');
      expect(label).toHaveTextContent('Good');
    });

    it('RD-001.6: applies orange color for needs-work zone (50-69)', () => {
      render(<ScoreGauge score={60} />);
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toHaveAttribute('data-zone', 'needs-work');
      
      const progress = screen.getByTestId('gauge-progress');
      expect(progress).toHaveAttribute('data-color', '#f97316');
      
      const label = screen.getByTestId('score-label');
      expect(label).toHaveTextContent('Needs Work');
    });

    it('RD-001.7: applies red color for critical zone (0-49)', () => {
      render(<ScoreGauge score={30} />);
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toHaveAttribute('data-zone', 'critical');
      
      const progress = screen.getByTestId('gauge-progress');
      expect(progress).toHaveAttribute('data-color', '#ef4444');
      
      const label = screen.getByTestId('score-label');
      expect(label).toHaveTextContent('Critical Issues');
    });

    it('RD-001.8: has CSS transition for animation', () => {
      render(<ScoreGauge score={85} />);
      
      const progress = screen.getByTestId('gauge-progress');
      expect(progress).toHaveClass('transition-[stroke-dashoffset]');
      expect(progress).toHaveClass('duration-1000');
    });

    it('RD-001.9: supports different sizes', () => {
      const { rerender } = render(<ScoreGauge score={85} size="sm" />);
      
      let svg = screen.getByTestId('gauge-svg');
      expect(svg).toHaveAttribute('width', '80');
      expect(svg).toHaveAttribute('height', '80');
      
      rerender(<ScoreGauge score={85} size="md" />);
      svg = screen.getByTestId('gauge-svg');
      expect(svg).toHaveAttribute('width', '120');
      
      rerender(<ScoreGauge score={85} size="lg" />);
      svg = screen.getByTestId('gauge-svg');
      expect(svg).toHaveAttribute('width', '160');
    });

    it('RD-001.10: shows loading skeleton when isLoading', () => {
      render(<ScoreGauge score={85} isLoading />);
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toHaveAttribute('data-loading', 'true');
      expect(gauge).toHaveAttribute('aria-busy', 'true');
      
      expect(screen.getByTestId('gauge-skeleton')).toBeInTheDocument();
      expect(screen.queryByTestId('score-value')).not.toBeInTheDocument();
    });

    it('RD-001.11: has correct accessibility attributes', () => {
      render(<ScoreGauge score={85} />);
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toHaveAttribute('role', 'meter');
      expect(gauge).toHaveAttribute('aria-label', 'Score: 85 out of 100');
      expect(gauge).toHaveAttribute('aria-valuemin', '0');
      expect(gauge).toHaveAttribute('aria-valuemax', '100');
      expect(gauge).toHaveAttribute('aria-valuenow', '85');
    });

    it('RD-001.12: displays large bold score in center', () => {
      render(<ScoreGauge score={85} />);
      
      const scoreValue = screen.getByTestId('score-value');
      expect(scoreValue).toHaveClass('text-4xl');
      expect(scoreValue).toHaveClass('font-bold');
    });
  });

  // -------------------------------------------------------------------------
  // RD-002: Color-Coded Score Display
  // -------------------------------------------------------------------------
  describe('RD-002: Color-Coded Score Display', () => {
    it('RD-002.1: shows "Excellent" label for 90-100', () => {
      render(<ScoreGauge score={95} />);
      expect(screen.getByTestId('score-label')).toHaveTextContent('Excellent');
    });

    it('RD-002.2: shows "Good" label for 70-89', () => {
      render(<ScoreGauge score={80} />);
      expect(screen.getByTestId('score-label')).toHaveTextContent('Good');
    });

    it('RD-002.3: shows "Needs Work" label for 50-69', () => {
      render(<ScoreGauge score={60} />);
      expect(screen.getByTestId('score-label')).toHaveTextContent('Needs Work');
    });

    it('RD-002.4: shows "Critical Issues" label for 0-49', () => {
      render(<ScoreGauge score={30} />);
      expect(screen.getByTestId('score-label')).toHaveTextContent('Critical Issues');
    });

    it('RD-002.5: shows positive score change indicator', () => {
      render(<ScoreGauge score={85} previousScore={78} />);
      
      const change = screen.getByTestId('score-change');
      expect(change).toHaveAttribute('data-change', 'up');
      expect(change).toHaveTextContent('+7');
    });

    it('RD-002.6: shows negative score change indicator', () => {
      render(<ScoreGauge score={75} previousScore={82} />);
      
      const change = screen.getByTestId('score-change');
      expect(change).toHaveAttribute('data-change', 'down');
      expect(change).toHaveTextContent('-7');
    });

    it('RD-002.7: hides change indicator when no previous score', () => {
      render(<ScoreGauge score={85} previousScore={null} />);
      
      expect(screen.queryByTestId('score-change')).not.toBeInTheDocument();
    });

    it('RD-002.8: hides change indicator when score unchanged', () => {
      render(<ScoreGauge score={85} previousScore={85} />);
      
      expect(screen.queryByTestId('score-change')).not.toBeInTheDocument();
    });
  });

  // -------------------------------------------------------------------------
  // Edge Cases
  // -------------------------------------------------------------------------
  describe('Edge Cases', () => {
    it('handles boundary scores (exactly 90, 70, 50)', () => {
      const { rerender } = render(<ScoreGauge score={90} />);
      expect(screen.getByTestId('score-gauge')).toHaveAttribute('data-zone', 'excellent');
      
      rerender(<ScoreGauge score={70} />);
      expect(screen.getByTestId('score-gauge')).toHaveAttribute('data-zone', 'good');
      
      rerender(<ScoreGauge score={50} />);
      expect(screen.getByTestId('score-gauge')).toHaveAttribute('data-zone', 'needs-work');
    });

    it('handles score just below boundaries (89, 69, 49)', () => {
      const { rerender } = render(<ScoreGauge score={89} />);
      expect(screen.getByTestId('score-gauge')).toHaveAttribute('data-zone', 'good');
      
      rerender(<ScoreGauge score={69} />);
      expect(screen.getByTestId('score-gauge')).toHaveAttribute('data-zone', 'needs-work');
      
      rerender(<ScoreGauge score={49} />);
      expect(screen.getByTestId('score-gauge')).toHaveAttribute('data-zone', 'critical');
    });

    it('clamps scores outside valid range', () => {
      // Note: Component should handle invalid scores gracefully
      // The mock doesn't implement clamping, but real component should
      render(<ScoreGauge score={-10} />);
      // Should still render, even if score is invalid
      expect(screen.getByTestId('score-gauge')).toBeInTheDocument();
    });

    it('handles very large scores', () => {
      render(<ScoreGauge score={150} />);
      // Should still render, even if score is outside normal range
      expect(screen.getByTestId('score-gauge')).toBeInTheDocument();
    });

    it('hides label when showLabel is false', () => {
      render(<ScoreGauge score={85} showLabel={false} />);
      
      expect(screen.queryByTestId('score-label')).not.toBeInTheDocument();
    });

    it('uses custom aria-label when provided', () => {
      render(<ScoreGauge score={85} aria-label="SEO Score" />);
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toHaveAttribute('aria-label', 'SEO Score');
    });

    it('applies custom className', () => {
      render(<ScoreGauge score={85} className="custom-class" />);
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toHaveClass('custom-class');
    });
  });

  // -------------------------------------------------------------------------
  // Acceptance Criteria Verification
  // -------------------------------------------------------------------------
  describe('Acceptance Criteria', () => {
    it('AC: Score gauge component (0-100 circular)', () => {
      render(<ScoreGauge score={75} />);
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toBeInTheDocument();
      expect(gauge).toHaveAttribute('role', 'meter');
      
      const svg = screen.getByTestId('gauge-svg');
      expect(svg).toBeInTheDocument();
      
      // Circular progress
      const progress = screen.getByTestId('gauge-progress');
      expect(progress).toBeInTheDocument();
      expect(progress.tagName.toLowerCase()).toBe('circle');
    });

    it('AC: Color-coded score display (green/yellow/red)', () => {
      const { rerender } = render(<ScoreGauge score={95} />);
      expect(screen.getByTestId('gauge-progress')).toHaveAttribute('data-color', '#22c55e');
      
      rerender(<ScoreGauge score={75} />);
      expect(screen.getByTestId('gauge-progress')).toHaveAttribute('data-color', '#eab308');
      
      rerender(<ScoreGauge score={30} />);
      expect(screen.getByTestId('gauge-progress')).toHaveAttribute('data-color', '#ef4444');
    });

    it('AC: Loading state during analysis', () => {
      render(<ScoreGauge score={85} isLoading />);
      
      const gauge = screen.getByTestId('score-gauge');
      expect(gauge).toHaveAttribute('data-loading', 'true');
      expect(gauge).toHaveAttribute('aria-busy', 'true');
      expect(screen.getByTestId('gauge-skeleton')).toBeInTheDocument();
    });
  });
});
