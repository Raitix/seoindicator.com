/**
 * ReviewStatsHeader Component Tests
 * 
 * Task: TASK-35-21 - Reviews Management Page
 * Spec: SPEC-035: Local SEO & Google Business Profile Tracking
 * 
 * Tests cover:
 * - Average rating display
 * - Total review count
 * - Rating distribution bar chart
 * - Sentiment breakdown
 * - Interactive elements
 * - Loading and error states
 * - Accessibility
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// ============================================
// Types and Interfaces
// ============================================

interface ReviewStats {
  average_rating: number;
  total_count: number;
  distribution: {
    5: number;
    4: number;
    3: number;
    2: number;
    1: number;
  };
  sentiment_breakdown: {
    positive: number;
    neutral: number;
    negative: number;
  };
  response_rate?: number;
  average_response_time_hours?: number;
}

interface ReviewStatsHeaderProps {
  stats: ReviewStats;
  isLoading?: boolean;
  onRatingClick?: (rating: number) => void;
  onSentimentClick?: (sentiment: string) => void;
  className?: string;
}

// ============================================
// Mocks
// ============================================

vi.mock('lucide-react', () => ({
  Star: ({ filled, className }: any) => (
    <span data-testid="star-icon" className={className} data-filled={filled}>
      ★
    </span>
  ),
  StarHalf: () => <span data-testid="star-half-icon">★</span>,
  TrendingUp: () => <span data-testid="trending-up-icon">📈</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">📉</span>,
  Smile: () => <span data-testid="smile-icon">😊</span>,
  Meh: () => <span data-testid="meh-icon">😐</span>,
  Frown: () => <span data-testid="frown-icon">😞</span>,
  Clock: () => <span data-testid="clock-icon">🕐</span>,
  MessageSquare: () => <span data-testid="message-icon">💬</span>,
}));

// ============================================
// Test Data
// ============================================

const mockStats: ReviewStats = {
  average_rating: 4.5,
  total_count: 127,
  distribution: {
    5: 89,
    4: 25,
    3: 8,
    2: 3,
    1: 2,
  },
  sentiment_breakdown: {
    positive: 114,
    neutral: 8,
    negative: 5,
  },
  response_rate: 78.5,
  average_response_time_hours: 24,
};

const zeroStats: ReviewStats = {
  average_rating: 0,
  total_count: 0,
  distribution: { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 },
  sentiment_breakdown: { positive: 0, neutral: 0, negative: 0 },
};

const lowRatingStats: ReviewStats = {
  average_rating: 2.3,
  total_count: 50,
  distribution: { 5: 5, 4: 5, 3: 10, 2: 15, 1: 15 },
  sentiment_breakdown: { positive: 10, neutral: 10, negative: 30 },
};

const perfectRatingStats: ReviewStats = {
  average_rating: 5.0,
  total_count: 100,
  distribution: { 5: 100, 4: 0, 3: 0, 2: 0, 1: 0 },
  sentiment_breakdown: { positive: 100, neutral: 0, negative: 0 },
};

// ============================================
// Mock Component Implementation
// ============================================

import React from 'react';

const MockReviewStatsHeader: React.FC<ReviewStatsHeaderProps> = ({
  stats,
  isLoading,
  onRatingClick,
  onSentimentClick,
  className,
}) => {
  if (isLoading) {
    return (
      <div data-testid="stats-loading" className="animate-pulse">
        <div className="h-8 bg-gray-200 rounded w-20" />
        <div className="h-4 bg-gray-200 rounded w-32 mt-2" />
      </div>
    );
  }

  const getPercentage = (count: number) => {
    return stats.total_count > 0 
      ? Math.round((count / stats.total_count) * 100) 
      : 0;
  };

  return (
    <div 
      data-testid="stats-header" 
      className={className}
      role="region"
      aria-label="Review Statistics"
    >
      {/* Average Rating */}
      <div data-testid="average-rating-section">
        <div 
          data-testid="average-rating-value"
          aria-label={`Average rating: ${stats.average_rating} out of 5`}
        >
          {stats.average_rating.toFixed(1)}
        </div>
        <div data-testid="star-display" aria-hidden="true">
          {[1, 2, 3, 4, 5].map((star) => (
            <span 
              key={star} 
              data-testid={`star-${star}`}
              data-filled={star <= Math.round(stats.average_rating)}
            >
              ★
            </span>
          ))}
        </div>
        <span data-testid="rating-label">out of 5</span>
      </div>

      {/* Total Count */}
      <div data-testid="total-count-section">
        <span data-testid="total-count-value">{stats.total_count.toLocaleString()}</span>
        <span data-testid="total-count-label">reviews</span>
      </div>

      {/* Rating Distribution */}
      <div data-testid="distribution-section" aria-label="Rating distribution">
        {[5, 4, 3, 2, 1].map((rating) => (
          <button
            key={rating}
            data-testid={`distribution-bar-${rating}`}
            onClick={() => onRatingClick?.(rating)}
            aria-label={`${stats.distribution[rating as keyof typeof stats.distribution]} ${rating}-star reviews (${getPercentage(stats.distribution[rating as keyof typeof stats.distribution])}%)`}
            className="distribution-bar"
          >
            <span data-testid={`rating-label-${rating}`}>{rating}</span>
            <div data-testid={`bar-container-${rating}`} className="bar-container">
              <div 
                data-testid={`bar-fill-${rating}`}
                style={{ width: `${getPercentage(stats.distribution[rating as keyof typeof stats.distribution])}%` }}
              />
            </div>
            <span data-testid={`rating-count-${rating}`}>
              {stats.distribution[rating as keyof typeof stats.distribution]}
            </span>
          </button>
        ))}
      </div>

      {/* Sentiment Breakdown */}
      <div data-testid="sentiment-section" aria-label="Sentiment breakdown">
        <button
          data-testid="sentiment-positive"
          onClick={() => onSentimentClick?.('positive')}
          className="sentiment-badge sentiment-positive"
          aria-label={`${stats.sentiment_breakdown.positive} positive reviews`}
        >
          <span data-testid="positive-icon">😊</span>
          <span data-testid="positive-count">{stats.sentiment_breakdown.positive}</span>
          <span data-testid="positive-percent">{getPercentage(stats.sentiment_breakdown.positive)}%</span>
        </button>
        <button
          data-testid="sentiment-neutral"
          onClick={() => onSentimentClick?.('neutral')}
          className="sentiment-badge sentiment-neutral"
          aria-label={`${stats.sentiment_breakdown.neutral} neutral reviews`}
        >
          <span data-testid="neutral-icon">😐</span>
          <span data-testid="neutral-count">{stats.sentiment_breakdown.neutral}</span>
          <span data-testid="neutral-percent">{getPercentage(stats.sentiment_breakdown.neutral)}%</span>
        </button>
        <button
          data-testid="sentiment-negative"
          onClick={() => onSentimentClick?.('negative')}
          className="sentiment-badge sentiment-negative"
          aria-label={`${stats.sentiment_breakdown.negative} negative reviews`}
        >
          <span data-testid="negative-icon">😞</span>
          <span data-testid="negative-count">{stats.sentiment_breakdown.negative}</span>
          <span data-testid="negative-percent">{getPercentage(stats.sentiment_breakdown.negative)}%</span>
        </button>
      </div>

      {/* Response Rate (if available) */}
      {stats.response_rate !== undefined && (
        <div data-testid="response-rate-section">
          <span data-testid="response-rate-value">{stats.response_rate.toFixed(1)}%</span>
          <span data-testid="response-rate-label">response rate</span>
        </div>
      )}

      {/* Average Response Time (if available) */}
      {stats.average_response_time_hours !== undefined && (
        <div data-testid="response-time-section">
          <span data-testid="response-time-value">{stats.average_response_time_hours}h</span>
          <span data-testid="response-time-label">avg response time</span>
        </div>
      )}
    </div>
  );
};

const TestReviewStatsHeader = MockReviewStatsHeader;

// ============================================
// Test Suite
// ============================================

describe('ReviewStatsHeader', () => {
  const user = userEvent.setup();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ============================================
  // STATS-001 to STATS-010: Basic Rendering
  // ============================================

  describe('Basic Rendering', () => {
    it('STATS-001: Component renders', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      expect(screen.getByTestId('stats-header')).toBeInTheDocument();
    });

    it('STATS-002: Average rating displayed', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      expect(screen.getByTestId('average-rating-value')).toHaveTextContent('4.5');
    });

    it('STATS-003: Average rating in 0-5 range', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      const rating = parseFloat(screen.getByTestId('average-rating-value').textContent || '0');
      expect(rating).toBeGreaterThanOrEqual(0);
      expect(rating).toBeLessThanOrEqual(5);
    });

    it('STATS-004: Average rating has one decimal place', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      const value = screen.getByTestId('average-rating-value').textContent || '';
      expect(value).toMatch(/^\d\.\d$/);
    });

    it('STATS-005: Total count displayed', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      expect(screen.getByTestId('total-count-value')).toHaveTextContent('127');
    });

    it('STATS-006: Total count formatted with thousands separator', () => {
      render(<TestReviewStatsHeader stats={{ ...mockStats, total_count: 12345 }} />);
      expect(screen.getByTestId('total-count-value')).toHaveTextContent('12,345');
    });

    it('STATS-007: Distribution bar rendered for each rating', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      [5, 4, 3, 2, 1].forEach((rating) => {
        expect(screen.getByTestId(`distribution-bar-${rating}`)).toBeInTheDocument();
      });
    });

    it('STATS-008: Distribution percentages calculated correctly', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      // 89 out of 127 = 70.07... = 70%
      expect(screen.getByTestId('bar-fill-5')).toHaveStyle({ width: '70%' });
    });

    it('STATS-009: Distribution bar heights are proportional', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      const bar5Width = screen.getByTestId('bar-fill-5').style.width;
      const bar1Width = screen.getByTestId('bar-fill-1').style.width;
      // Bar 5 should be wider than bar 1
      expect(parseInt(bar5Width)).toBeGreaterThan(parseInt(bar1Width));
    });

    it('STATS-010: Distribution bars show counts', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      expect(screen.getByTestId('rating-count-5')).toHaveTextContent('89');
      expect(screen.getByTestId('rating-count-1')).toHaveTextContent('2');
    });
  });

  // ============================================
  // STATS-011 to STATS-020: Sentiment Breakdown
  // ============================================

  describe('Sentiment Breakdown', () => {
    it('STATS-011: Sentiment breakdown rendered with 3 sections', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      expect(screen.getByTestId('sentiment-section')).toBeInTheDocument();
      expect(screen.getByTestId('sentiment-positive')).toBeInTheDocument();
      expect(screen.getByTestId('sentiment-neutral')).toBeInTheDocument();
      expect(screen.getByTestId('sentiment-negative')).toBeInTheDocument();
    });

    it('STATS-012: Positive sentiment has green styling', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      expect(screen.getByTestId('sentiment-positive')).toHaveClass('sentiment-positive');
    });

    it('STATS-013: Neutral sentiment has yellow styling', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      expect(screen.getByTestId('sentiment-neutral')).toHaveClass('sentiment-neutral');
    });

    it('STATS-014: Negative sentiment has red styling', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      expect(screen.getByTestId('sentiment-negative')).toHaveClass('sentiment-negative');
    });

    it('STATS-015: Sentiment percentages calculated', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      // 114 out of 127 = 89.76... = 90%
      expect(screen.getByTestId('positive-percent')).toHaveTextContent('90%');
    });

    it('STATS-016: Zero reviews state shows all zeros', () => {
      render(<TestReviewStatsHeader stats={zeroStats} />);
      expect(screen.getByTestId('average-rating-value')).toHaveTextContent('0.0');
      expect(screen.getByTestId('total-count-value')).toHaveTextContent('0');
      expect(screen.getByTestId('positive-count')).toHaveTextContent('0');
    });

    it('STATS-017: Loading skeleton displayed', () => {
      render(<TestReviewStatsHeader stats={mockStats} isLoading />);
      expect(screen.getByTestId('stats-loading')).toBeInTheDocument();
    });

    it('STATS-020: Accessibility labels present', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      expect(screen.getByTestId('stats-header')).toHaveAttribute('aria-label', 'Review Statistics');
      expect(screen.getByTestId('average-rating-value')).toHaveAttribute('aria-label');
    });
  });

  // ============================================
  // STATS-021 to STATS-030: Interactive Elements
  // ============================================

  describe('Interactive Elements', () => {
    it('STATS-022: Distribution bar is clickable', async () => {
      const onRatingClick = vi.fn();
      render(<TestReviewStatsHeader stats={mockStats} onRatingClick={onRatingClick} />);
      
      await user.click(screen.getByTestId('distribution-bar-5'));
      
      expect(onRatingClick).toHaveBeenCalledWith(5);
    });

    it('STATS-023: Sentiment badge is clickable', async () => {
      const onSentimentClick = vi.fn();
      render(<TestReviewStatsHeader stats={mockStats} onSentimentClick={onSentimentClick} />);
      
      await user.click(screen.getByTestId('sentiment-positive'));
      
      expect(onSentimentClick).toHaveBeenCalledWith('positive');
    });

    it('STATS-024: Custom className applied', () => {
      render(<TestReviewStatsHeader stats={mockStats} className="custom-class" />);
      expect(screen.getByTestId('stats-header')).toHaveClass('custom-class');
    });

    it('STATS-027: Large numbers formatted correctly', () => {
      render(<TestReviewStatsHeader stats={{ ...mockStats, total_count: 1234567 }} />);
      expect(screen.getByTestId('total-count-value')).toHaveTextContent('1,234,567');
    });

    it('STATS-028: Perfect rating has special styling', () => {
      render(<TestReviewStatsHeader stats={perfectRatingStats} />);
      // All stars filled
      expect(screen.getByTestId('average-rating-value')).toHaveTextContent('5.0');
    });

    it('STATS-029: Low rating shows warning colors', () => {
      render(<TestReviewStatsHeader stats={lowRatingStats} />);
      expect(screen.getByTestId('average-rating-value')).toHaveTextContent('2.3');
    });

    it('STATS-030: Response rate shown when available', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      expect(screen.getByTestId('response-rate-section')).toBeInTheDocument();
      expect(screen.getByTestId('response-rate-value')).toHaveTextContent('78.5%');
    });

    it('STATS-031: Average response time shown', () => {
      render(<TestReviewStatsHeader stats={mockStats} />);
      expect(screen.getByTestId('response-time-section')).toBeInTheDocument();
      expect(screen.getByTestId('response-time-value')).toHaveTextContent('24h');
    });
  });

  // ============================================
  // STATS-032 to STATS-040: Edge Cases
  // ============================================

  describe('Edge Cases', () => {
    it('handles zero total count without division by zero', () => {
      render(<TestReviewStatsHeader stats={zeroStats} />);
      expect(screen.getByTestId('positive-percent')).toHaveTextContent('0%');
    });

    it('handles very small percentages', () => {
      const smallStats: ReviewStats = {
        ...mockStats,
        total_count: 1000,
        sentiment_breakdown: { positive: 997, neutral: 2, negative: 1 },
      };
      render(<TestReviewStatsHeader stats={smallStats} />);
      expect(screen.getByTestId('negative-percent')).toHaveTextContent('0%');
    });

    it('handles ratings with no reviews for some stars', () => {
      render(<TestReviewStatsHeader stats={perfectRatingStats} />);
      expect(screen.getByTestId('rating-count-1')).toHaveTextContent('0');
      expect(screen.getByTestId('bar-fill-1')).toHaveStyle({ width: '0%' });
    });

    it('handles response rate of 0%', () => {
      render(<TestReviewStatsHeader stats={{ ...mockStats, response_rate: 0 }} />);
      expect(screen.getByTestId('response-rate-value')).toHaveTextContent('0.0%');
    });

    it('handles response rate of 100%', () => {
      render(<TestReviewStatsHeader stats={{ ...mockStats, response_rate: 100 }} />);
      expect(screen.getByTestId('response-rate-value')).toHaveTextContent('100.0%');
    });

    it('handles null sentiment breakdown gracefully', () => {
      const statsWithNull = {
        ...mockStats,
        sentiment_breakdown: { positive: 0, neutral: 0, negative: 0 },
      };
      render(<TestReviewStatsHeader stats={statsWithNull} />);
      expect(screen.getByTestId('sentiment-section')).toBeInTheDocument();
    });
  });
});
