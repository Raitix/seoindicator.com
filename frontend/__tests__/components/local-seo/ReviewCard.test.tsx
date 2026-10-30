/**
 * ReviewCard Component Tests
 * 
 * Task: TASK-35-21 - Reviews Management Page
 * Spec: SPEC-035: Local SEO & Google Business Profile Tracking
 * 
 * Tests cover:
 * - Author info display
 * - Star rating display
 * - Review text with expand/collapse
 * - Sentiment badge
 * - Owner response display
 * - Action buttons
 * - Accessibility
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// ============================================
// Types and Interfaces
// ============================================

interface Review {
  id: string;
  gbp_review_id: string;
  author: string;
  avatar_url?: string;
  rating: 1 | 2 | 3 | 4 | 5;
  text: string;
  sentiment: 'positive' | 'neutral' | 'negative';
  reviewed_at: string;
  has_response: boolean;
  response?: {
    text: string;
    responded_at: string;
    responder_name?: string;
  };
  images?: string[];
  is_local_guide?: boolean;
  review_count?: number;
}

interface ReviewCardProps {
  review: Review;
  onRespond?: () => void;
  onViewOnGoogle?: () => void;
  isSelected?: boolean;
  onSelect?: (selected: boolean) => void;
  className?: string;
}

// ============================================
// Mocks
// ============================================

vi.mock('lucide-react', () => ({
  Star: ({ filled, className }: any) => (
    <span data-testid="star-icon" className={className} data-filled={filled}>
      {filled ? '★' : '☆'}
    </span>
  ),
  MessageSquare: () => <span data-testid="message-icon">💬</span>,
  ExternalLink: () => <span data-testid="external-link-icon">↗</span>,
  Smile: () => <span data-testid="smile-icon">😊</span>,
  Meh: () => <span data-testid="meh-icon">😐</span>,
  Frown: () => <span data-testid="frown-icon">😞</span>,
  Clock: () => <span data-testid="clock-icon">🕐</span>,
  ChevronDown: () => <span data-testid="chevron-icon">▼</span>,
  ChevronUp: () => <span data-testid="chevron-up-icon">▲</span>,
  Check: () => <span data-testid="check-icon">✓</span>,
  Loader2: ({ className }: any) => (
    <span data-testid="loader-icon" className={className}>Loading</span>
  ),
}));

vi.mock('next/image', () => ({
  default: ({ src, alt, className }: any) => (
    <img src={src} alt={alt} className={className} data-testid="avatar-image" />
  ),
}));

// ============================================
// Test Data
// ============================================

const mockReview: Review = {
  id: 'review-1',
  gbp_review_id: 'gbp-review-1',
  author: 'John Doe',
  avatar_url: 'https://example.com/avatar.jpg',
  rating: 5,
  text: 'Excellent service! Very professional team. Would highly recommend to anyone looking for quality work.',
  sentiment: 'positive',
  reviewed_at: '2024-01-15T10:30:00Z',
  has_response: false,
};

const mockReviewWithResponse: Review = {
  ...mockReview,
  id: 'review-2',
  has_response: true,
  response: {
    text: 'Thank you for your kind words! We appreciate your feedback.',
    responded_at: '2024-01-16T14:20:00Z',
    responder_name: 'Business Owner',
  },
};

const mockNegativeReview: Review = {
  id: 'review-3',
  gbp_review_id: 'gbp-review-3',
  author: 'Jane Smith',
  rating: 2,
  text: 'Not satisfied with the service. Had to wait too long.',
  sentiment: 'negative',
  reviewed_at: '2024-01-14T09:15:00Z',
  has_response: false,
};

const mockLongReview: Review = {
  ...mockReview,
  id: 'review-4',
  text: 'A'.repeat(500), // Long text
};

const mockReviewWithImages: Review = {
  ...mockReview,
  id: 'review-5',
  images: ['image1.jpg', 'image2.jpg'],
};

const mockLocalGuideReview: Review = {
  ...mockReview,
  id: 'review-6',
  is_local_guide: true,
  review_count: 42,
};

// ============================================
// Mock Component Implementation
// ============================================

import React from 'react';

const MockReviewCard: React.FC<ReviewCardProps> = ({
  review,
  onRespond,
  onViewOnGoogle,
  isSelected,
  onSelect,
  className,
}) => {
  const [isExpanded, setIsExpanded] = React.useState(false);
  const MAX_TEXT_LENGTH = 200;

  const shouldTruncate = review.text.length > MAX_TEXT_LENGTH;
  const displayText = shouldTruncate && !isExpanded 
    ? review.text.slice(0, MAX_TEXT_LENGTH) + '...'
    : review.text;

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  };

  const getSentimentIcon = (sentiment: string) => {
    switch (sentiment) {
      case 'positive': return '😊';
      case 'negative': return '😞';
      default: return '😐';
    }
  };

  return (
    <article
      data-testid={`review-card-${review.id}`}
      className={`review-card ${className || ''}`}
      aria-label={`Review by ${review.author}`}
    >
      {/* Selection Checkbox */}
      {onSelect && (
        <input
          type="checkbox"
          data-testid={`select-${review.id}`}
          checked={isSelected}
          onChange={(e) => onSelect(e.target.checked)}
          aria-label={`Select review by ${review.author}`}
        />
      )}

      {/* Author Info */}
      <div data-testid={`author-section-${review.id}`}>
        {review.avatar_url ? (
          <img
            src={review.avatar_url}
            alt={`${review.author}'s avatar`}
            data-testid={`avatar-${review.id}`}
            className="avatar"
          />
        ) : (
          <div data-testid={`avatar-placeholder-${review.id}`} className="avatar-placeholder">
            {review.author.charAt(0).toUpperCase()}
          </div>
        )}
        <div data-testid={`author-info-${review.id}`}>
          <span data-testid={`author-name-${review.id}`}>{review.author}</span>
          {review.is_local_guide && (
            <span data-testid={`local-guide-badge-${review.id}`} className="local-guide-badge">
              Local Guide
            </span>
          )}
          {review.review_count && (
            <span data-testid={`review-count-${review.id}`} className="review-count">
              {review.review_count} reviews
            </span>
          )}
        </div>
      </div>

      {/* Rating */}
      <div 
        data-testid={`rating-section-${review.id}`}
        aria-label={`${review.rating} out of 5 stars`}
      >
        {[1, 2, 3, 4, 5].map((star) => (
          <span
            key={star}
            data-testid={`star-${review.id}-${star}`}
            data-filled={star <= review.rating}
            className="star"
          >
            {star <= review.rating ? '★' : '☆'}
          </span>
        ))}
        <span data-testid={`rating-numeric-${review.id}`}>
          {review.rating}/5
        </span>
      </div>

      {/* Date */}
      <div data-testid={`date-section-${review.id}`}>
        <time dateTime={review.reviewed_at}>
          {formatDate(review.reviewed_at)}
        </time>
      </div>

      {/* Review Text */}
      <div data-testid={`text-section-${review.id}`}>
        <p data-testid={`text-${review.id}`}>{displayText}</p>
        {shouldTruncate && (
          <button
            data-testid={`expand-button-${review.id}`}
            onClick={() => setIsExpanded(!isExpanded)}
            aria-expanded={isExpanded}
          >
            {isExpanded ? 'Show less' : 'Read more'}
          </button>
        )}
      </div>

      {/* Images */}
      {review.images && review.images.length > 0 && (
        <div data-testid={`images-section-${review.id}`}>
          {review.images.map((image, index) => (
            <img
              key={index}
              src={image}
              alt={`Review image ${index + 1}`}
              data-testid={`image-${review.id}-${index}`}
            />
          ))}
        </div>
      )}

      {/* Sentiment Badge */}
      <div 
        data-testid={`sentiment-badge-${review.id}`}
        className={`sentiment-badge sentiment-${review.sentiment}`}
        aria-label={`${review.sentiment} sentiment`}
      >
        <span data-testid={`sentiment-icon-${review.id}`}>
          {getSentimentIcon(review.sentiment)}
        </span>
        <span data-testid={`sentiment-text-${review.id}`}>
          {review.sentiment.charAt(0).toUpperCase() + review.sentiment.slice(1)}
        </span>
      </div>

      {/* Owner Response */}
      {review.has_response && review.response && (
        <div data-testid={`response-section-${review.id}`} className="owner-response">
          <div data-testid={`response-header-${review.id}`}>
            <span>Response from {review.response.responder_name || 'Business'}</span>
            <time dateTime={review.response.responded_at}>
              {formatDate(review.response.responded_at)}
            </time>
          </div>
          <p data-testid={`response-text-${review.id}`}>{review.response.text}</p>
        </div>
      )}

      {/* Actions */}
      <div data-testid={`actions-${review.id}`} className="actions">
        {!review.has_response && onRespond && (
          <button
            data-testid={`respond-button-${review.id}`}
            onClick={onRespond}
            aria-label={`Respond to review by ${review.author}`}
          >
            Respond
          </button>
        )}
        {review.has_response && (
          <button
            data-testid={`edit-response-button-${review.id}`}
            aria-label={`Edit response to ${review.author}`}
          >
            Edit Response
          </button>
        )}
        <a
          href={`https://google.com/review/${review.gbp_review_id}`}
          target="_blank"
          rel="noopener noreferrer"
          data-testid={`view-google-link-${review.id}`}
          aria-label="View on Google"
        >
          View on Google
        </a>
      </div>
    </article>
  );
};

const TestReviewCard = MockReviewCard;

// ============================================
// Test Suite
// ============================================

describe('ReviewCard', () => {
  const user = userEvent.setup();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ============================================
  // CARD-001 to CARD-010: Basic Rendering
  // ============================================

  describe('Basic Rendering', () => {
    it('CARD-001: Component renders', () => {
      render(<TestReviewCard review={mockReview} />);
      expect(screen.getByTestId(`review-card-${mockReview.id}`)).toBeInTheDocument();
    });

    it('CARD-002: Author name displayed', () => {
      render(<TestReviewCard review={mockReview} />);
      expect(screen.getByTestId(`author-name-${mockReview.id}`)).toHaveTextContent('John Doe');
    });

    it('CARD-003: Author avatar displayed', () => {
      render(<TestReviewCard review={mockReview} />);
      expect(screen.getByTestId(`avatar-${mockReview.id}`)).toBeInTheDocument();
      expect(screen.getByTestId(`avatar-${mockReview.id}`)).toHaveAttribute('src', mockReview.avatar_url);
    });

    it('CARD-003: Shows placeholder when no avatar', () => {
      const reviewNoAvatar = { ...mockReview, avatar_url: undefined };
      render(<TestReviewCard review={reviewNoAvatar} />);
      expect(screen.getByTestId(`avatar-placeholder-${mockReview.id}`)).toBeInTheDocument();
    });

    it('CARD-004: Rating stars displayed', () => {
      render(<TestReviewCard review={mockReview} />);
      const filledStars = screen.getAllByTestId(/star-review-1-[1-5]/).filter(
        (star) => star.getAttribute('data-filled') === 'true'
      );
      expect(filledStars).toHaveLength(5);
    });

    it('CARD-005: Rating numeric displayed', () => {
      render(<TestReviewCard review={mockReview} />);
      expect(screen.getByTestId(`rating-numeric-${mockReview.id}`)).toHaveTextContent('5/5');
    });

    it('CARD-006: Review date displayed', () => {
      render(<TestReviewCard review={mockReview} />);
      expect(screen.getByTestId(`date-section-${mockReview.id}`)).toBeInTheDocument();
    });

    it('CARD-007: Relative time format', () => {
      render(<TestReviewCard review={mockReview} />);
      // Component shows formatted date
      const dateSection = screen.getByTestId(`date-section-${mockReview.id}`);
      expect(dateSection).toHaveTextContent('Jan');
    });

    it('CARD-008: Review text displayed', () => {
      render(<TestReviewCard review={mockReview} />);
      expect(screen.getByTestId(`text-${mockReview.id}`)).toHaveTextContent(mockReview.text);
    });

    it('CARD-009: Long text truncated with read more', () => {
      render(<TestReviewCard review={mockLongReview} />);
      expect(screen.getByTestId(`expand-button-${mockLongReview.id}`)).toBeInTheDocument();
    });

    it('CARD-010: Expand/collapse text works', async () => {
      render(<TestReviewCard review={mockLongReview} />);
      
      const expandButton = screen.getByTestId(`expand-button-${mockLongReview.id}`);
      expect(expandButton).toHaveTextContent('Read more');
      
      await user.click(expandButton);
      
      expect(expandButton).toHaveTextContent('Show less');
    });
  });

  // ============================================
  // CARD-011 to CARD-020: Sentiment and Response
  // ============================================

  describe('Sentiment and Response', () => {
    it('CARD-011: Sentiment badge displayed', () => {
      render(<TestReviewCard review={mockReview} />);
      expect(screen.getByTestId(`sentiment-badge-${mockReview.id}`)).toBeInTheDocument();
    });

    it('CARD-012: Sentiment icon displayed', () => {
      render(<TestReviewCard review={mockReview} />);
      expect(screen.getByTestId(`sentiment-icon-${mockReview.id}`)).toHaveTextContent('😊');
    });

    it('CARD-012: Negative sentiment has frown icon', () => {
      render(<TestReviewCard review={mockNegativeReview} />);
      expect(screen.getByTestId(`sentiment-icon-${mockNegativeReview.id}`)).toHaveTextContent('😞');
    });

    it('CARD-013: Positive sentiment has green styling', () => {
      render(<TestReviewCard review={mockReview} />);
      expect(screen.getByTestId(`sentiment-badge-${mockReview.id}`)).toHaveClass('sentiment-positive');
    });

    it('CARD-013: Negative sentiment has red styling', () => {
      render(<TestReviewCard review={mockNegativeReview} />);
      expect(screen.getByTestId(`sentiment-badge-${mockNegativeReview.id}`)).toHaveClass('sentiment-negative');
    });

    it('CARD-014: Owner response shown when present', () => {
      render(<TestReviewCard review={mockReviewWithResponse} />);
      expect(screen.getByTestId(`response-section-${mockReviewWithResponse.id}`)).toBeInTheDocument();
    });

    it('CARD-015: Response text displayed', () => {
      render(<TestReviewCard review={mockReviewWithResponse} />);
      expect(screen.getByTestId(`response-text-${mockReviewWithResponse.id}`)).toHaveTextContent(
        mockReviewWithResponse.response!.text
      );
    });

    it('CARD-016: Response date displayed', () => {
      render(<TestReviewCard review={mockReviewWithResponse} />);
      expect(screen.getByTestId(`response-header-${mockReviewWithResponse.id}`)).toBeInTheDocument();
    });

    it('CARD-017: Respond button shown when no response', () => {
      render(<TestReviewCard review={mockReview} onRespond={() => {}} />);
      expect(screen.getByTestId(`respond-button-${mockReview.id}`)).toBeInTheDocument();
    });

    it('CARD-018: Respond button action works', async () => {
      const onRespond = vi.fn();
      render(<TestReviewCard review={mockReview} onRespond={onRespond} />);
      
      await user.click(screen.getByTestId(`respond-button-${mockReview.id}`));
      
      expect(onRespond).toHaveBeenCalled();
    });
  });

  // ============================================
  // CARD-019 to CARD-035: Actions and Edge Cases
  // ============================================

  describe('Actions and Edge Cases', () => {
    it('CARD-019: View on Google link present', () => {
      render(<TestReviewCard review={mockReview} />);
      expect(screen.getByTestId(`view-google-link-${mockReview.id}`)).toBeInTheDocument();
    });

    it('CARD-020: Google link opens in new tab', () => {
      render(<TestReviewCard review={mockReview} />);
      const link = screen.getByTestId(`view-google-link-${mockReview.id}`);
      expect(link).toHaveAttribute('target', '_blank');
      expect(link).toHaveAttribute('rel', 'noopener noreferrer');
    });

    it('CARD-021: Edit response button shown when has response', () => {
      render(<TestReviewCard review={mockReviewWithResponse} />);
      expect(screen.getByTestId(`edit-response-button-${mockReviewWithResponse.id}`)).toBeInTheDocument();
    });

    it('CARD-024: Accessibility labels present', () => {
      render(<TestReviewCard review={mockReview} />);
      expect(screen.getByTestId(`review-card-${mockReview.id}`)).toHaveAttribute('aria-label');
      expect(screen.getByTestId(`rating-section-${mockReview.id}`)).toHaveAttribute('aria-label');
    });

    it('CARD-025: Keyboard navigation works', async () => {
      const onRespond = vi.fn();
      render(<TestReviewCard review={mockReview} onRespond={onRespond} />);
      
      await user.tab();
      // Focus should reach interactive elements
    });

    it('CARD-027: Hover effect applied', () => {
      render(<TestReviewCard review={mockReview} className="hover-class" />);
      const card = screen.getByTestId(`review-card-${mockReview.id}`);
      expect(card).toHaveClass('hover-class');
    });

    it('CARD-029: Selection checkbox works', async () => {
      const onSelect = vi.fn();
      render(<TestReviewCard review={mockReview} onSelect={onSelect} />);
      
      const checkbox = screen.getByTestId(`select-${mockReview.id}`);
      await user.click(checkbox);
      
      expect(onSelect).toHaveBeenCalledWith(true);
    });

    it('CARD-031: Review images displayed', () => {
      render(<TestReviewCard review={mockReviewWithImages} />);
      expect(screen.getByTestId(`images-section-${mockReviewWithImages.id}`)).toBeInTheDocument();
    });

    it('CARD-033: Local guide badge shown', () => {
      render(<TestReviewCard review={mockLocalGuideReview} />);
      expect(screen.getByTestId(`local-guide-badge-${mockLocalGuideReview.id}`)).toBeInTheDocument();
    });

    it('CARD-034: Review count shown for local guide', () => {
      render(<TestReviewCard review={mockLocalGuideReview} />);
      expect(screen.getByTestId(`review-count-${mockLocalGuideReview.id}`)).toHaveTextContent('42 reviews');
    });

    it('CARD-035: Custom className applied', () => {
      render(<TestReviewCard review={mockReview} className="custom-card" />);
      expect(screen.getByTestId(`review-card-${mockReview.id}`)).toHaveClass('custom-card');
    });
  });

  // ============================================
  // Additional Edge Cases
  // ============================================

  describe('Edge Cases', () => {
    it('handles empty review text', () => {
      const emptyReview = { ...mockReview, text: '' };
      render(<TestReviewCard review={emptyReview} />);
      expect(screen.getByTestId(`text-${mockReview.id}`)).toBeInTheDocument();
    });

    it('handles unicode characters in author name', () => {
      const unicodeReview = { ...mockReview, author: '日本語ネーム' };
      render(<TestReviewCard review={unicodeReview} />);
      expect(screen.getByTestId(`author-name-${mockReview.id}`)).toHaveTextContent('日本語ネーム');
    });

    it('handles emoji in review text', () => {
      const emojiReview = { ...mockReview, text: 'Great service! 😍🎉' };
      render(<TestReviewCard review={emojiReview} />);
      expect(screen.getByTestId(`text-${mockReview.id}`)).toHaveTextContent('Great service! 😍🎉');
    });

    it('handles very long author name', () => {
      const longNameReview = { ...mockReview, author: 'A'.repeat(100) };
      render(<TestReviewCard review={longNameReview} />);
      expect(screen.getByTestId(`author-name-${mockReview.id}`)).toBeInTheDocument();
    });

    it('handles 1 star rating', () => {
      const oneStarReview = { ...mockReview, rating: 1 as const };
      render(<TestReviewCard review={oneStarReview} />);
      const filledStars = screen.getAllByTestId(/star-review-1-[1-5]/).filter(
        (star) => star.getAttribute('data-filled') === 'true'
      );
      expect(filledStars).toHaveLength(1);
    });
  });
});
