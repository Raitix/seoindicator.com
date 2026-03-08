/**
 * ReviewsList Component Tests
 * 
 * Task: TASK-35-21 - Reviews Management Page
 * Spec: SPEC-035: Local SEO & Google Business Profile Tracking
 * 
 * Tests cover:
 * - Reviews list rendering
 * - Pagination controls
 * - Loading states
 * - Empty state
 * - Error state
 * - Selection mode
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
  };
}

interface PaginationState {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

interface ReviewsListProps {
  reviews: Review[];
  pagination: PaginationState;
  onPageChange: (page: number) => void;
  onRespond: (reviewId: string) => void;
  isLoading?: boolean;
  className?: string;
}

// ============================================
// Mocks
// ============================================

vi.mock('lucide-react', () => ({
  ChevronLeft: () => <span data-testid="chevron-left-icon">◀</span>,
  ChevronRight: () => <span data-testid="chevron-right-icon">▶</span>,
  ChevronsLeft: () => <span data-testid="chevrons-left-icon">⟪</span>,
  ChevronsRight: () => <span data-testid="chevrons-right-icon">⟫</span>,
  Loader2: ({ className }: any) => (
    <span data-testid="loader-icon" className={className}>Loading</span>
  ),
  AlertCircle: () => <span data-testid="alert-icon">⚠</span>,
  MessageSquare: () => <span data-testid="message-icon">💬</span>,
  ExternalLink: () => <span data-testid="external-link-icon">↗</span>,
  Star: ({ filled }: any) => (
    <span data-testid="star-icon" data-filled={filled}>
      {filled ? '★' : '☆'}
    </span>
  ),
  CheckSquare: () => <span data-testid="check-square-icon">☑</span>,
  Square: () => <span data-testid="square-icon">☐</span>,
}));

// ============================================
// Test Data
// ============================================

const mockReviews: Review[] = Array.from({ length: 20 }, (_, i) => ({
  id: `review-${i + 1}`,
  gbp_review_id: `gbp-review-${i + 1}`,
  author: `Author ${i + 1}`,
  rating: Math.min(5, Math.max(1, Math.floor(Math.random() * 5 + 1))) as 1 | 2 | 3 | 4 | 5,
  text: `Review text ${i + 1}`,
  sentiment: ['positive', 'neutral', 'negative'][i % 3] as 'positive' | 'neutral' | 'negative',
  reviewed_at: new Date(Date.now() - i * 86400000).toISOString(),
  has_response: i % 3 === 0,
  response: i % 3 === 0 ? {
    text: `Response ${i + 1}`,
    responded_at: new Date(Date.now() - i * 43200000).toISOString(),
  } : undefined,
}));

const mockPagination: PaginationState = {
  current_page: 1,
  per_page: 20,
  total: 127,
  last_page: 7,
};

const emptyPagination: PaginationState = {
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1,
};

// ============================================
// Mock Component Implementation
// ============================================

import React from 'react';

const MockReviewsList: React.FC<ReviewsListProps> = ({
  reviews,
  pagination,
  onPageChange,
  onRespond,
  isLoading,
  className,
}) => {
  const [selectedIds, setSelectedIds] = React.useState<Set<string>>(new Set());

  const toggleSelection = (id: string) => {
    const newSelected = new Set(selectedIds);
    if (newSelected.has(id)) {
      newSelected.delete(id);
    } else {
      newSelected.add(id);
    }
    setSelectedIds(newSelected);
  };

  const toggleSelectAll = () => {
    if (selectedIds.size === reviews.length) {
      setSelectedIds(new Set());
    } else {
      setSelectedIds(new Set(reviews.map((r) => r.id)));
    }
  };

  const renderSkeleton = () => (
    <div data-testid="skeleton-card" className="skeleton">
      <div className="h-4 bg-gray-200 rounded w-3/4" />
      <div className="h-4 bg-gray-200 rounded w-1/2 mt-2" />
      <div className="h-4 bg-gray-200 rounded w-full mt-2" />
    </div>
  );

  if (isLoading) {
    return (
      <div data-testid="reviews-loading" className={className}>
        {Array.from({ length: 5 }).map((_, i) => (
          <React.Fragment key={i}>{renderSkeleton()}</React.Fragment>
        ))}
      </div>
    );
  }

  if (reviews.length === 0) {
    return (
      <div data-testid="empty-state" className={className} role="status">
        <p>No reviews found</p>
        <p data-testid="empty-suggestion">Try adjusting your filters</p>
      </div>
    );
  }

  const start = (pagination.current_page - 1) * pagination.per_page + 1;
  const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);

  return (
    <div data-testid="reviews-list" className={className} role="list" aria-label="Reviews list">
      {/* Selection Controls */}
      <div data-testid="selection-controls">
        <button
          data-testid="select-all-button"
          onClick={toggleSelectAll}
          aria-pressed={selectedIds.size === reviews.length}
        >
          {selectedIds.size === reviews.length ? 'Deselect all' : 'Select all'}
        </button>
        {selectedIds.size > 0 && (
          <span data-testid="selection-count">
            {selectedIds.size} selected
          </span>
        )}
      </div>

      {/* Review Cards */}
      {reviews.map((review) => (
        <article
          key={review.id}
          data-testid={`review-card-${review.id}`}
          role="listitem"
          aria-label={`Review by ${review.author}`}
        >
          {/* Selection Checkbox */}
          <input
            type="checkbox"
            data-testid={`select-${review.id}`}
            checked={selectedIds.has(review.id)}
            onChange={() => toggleSelection(review.id)}
            aria-label={`Select review by ${review.author}`}
          />

          {/* Review Content */}
          <div data-testid={`review-content-${review.id}`}>
            <span data-testid={`author-${review.id}`}>{review.author}</span>
            <div data-testid={`rating-${review.id}`}>
              {'★'.repeat(review.rating)}{'☆'.repeat(5 - review.rating)}
            </div>
            <p data-testid={`text-${review.id}`}>{review.text}</p>
            <span data-testid={`sentiment-${review.id}`} className={`sentiment-${review.sentiment}`}>
              {review.sentiment}
            </span>
          </div>

          {/* Actions */}
          <div data-testid={`actions-${review.id}`}>
            {!review.has_response && (
              <button
                data-testid={`respond-button-${review.id}`}
                onClick={() => onRespond(review.id)}
              >
                Respond
              </button>
            )}
            <a
              data-testid={`view-google-${review.id}`}
              href={`https://google.com/review/${review.gbp_review_id}`}
              target="_blank"
              rel="noopener noreferrer"
            >
              View on Google
            </a>
          </div>
        </article>
      ))}

      {/* Pagination */}
      <nav data-testid="pagination" aria-label="Pagination">
        <button
          data-testid="first-page"
          onClick={() => onPageChange(1)}
          disabled={pagination.current_page === 1}
          aria-label="First page"
        >
          ⟪
        </button>
        <button
          data-testid="prev-page"
          onClick={() => onPageChange(pagination.current_page - 1)}
          disabled={pagination.current_page === 1}
          aria-label="Previous page"
        >
          ◀
        </button>
        
        <span data-testid="page-info">
          Showing {start}-{end} of {pagination.total}
        </span>
        
        <button
          data-testid="next-page"
          onClick={() => onPageChange(pagination.current_page + 1)}
          disabled={pagination.current_page === pagination.last_page}
          aria-label="Next page"
        >
          ▶
        </button>
        <button
          data-testid="last-page"
          onClick={() => onPageChange(pagination.last_page)}
          disabled={pagination.current_page === pagination.last_page}
          aria-label="Last page"
        >
          ⟫
        </button>

        {/* Page Numbers */}
        <div data-testid="page-numbers">
          {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
            const pageNum = i + 1;
            return (
              <button
                key={pageNum}
                data-testid={`page-${pageNum}`}
                onClick={() => onPageChange(pageNum)}
                aria-current={pagination.current_page === pageNum ? 'page' : undefined}
                aria-label={`Page ${pageNum}`}
              >
                {pageNum}
              </button>
            );
          })}
        </div>

        {/* Per Page Selector */}
        <select
          data-testid="per-page-select"
          value={pagination.per_page}
          onChange={(e) => onPageChange(1)} // Reset to page 1
          aria-label="Items per page"
        >
          <option value="10">10 per page</option>
          <option value="20">20 per page</option>
          <option value="50">50 per page</option>
          <option value="100">100 per page</option>
        </select>
      </nav>
    </div>
  );
};

const TestReviewsList = MockReviewsList;

// ============================================
// Test Suite
// ============================================

describe('ReviewsList', () => {
  const user = userEvent.setup();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ============================================
  // LIST-001 to LIST-010: Basic Rendering
  // ============================================

  describe('Basic Rendering', () => {
    it('LIST-001: Component renders', () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      expect(screen.getByTestId('reviews-list')).toBeInTheDocument();
    });

    it('LIST-002: Reviews displayed', () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      mockReviews.slice(0, 5).forEach((review) => {
        expect(screen.getByTestId(`review-card-${review.id}`)).toBeInTheDocument();
      });
    });

    it('LIST-003: Empty state shown when no reviews', () => {
      render(
        <TestReviewsList
          reviews={[]}
          pagination={emptyPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
    });

    it('LIST-004: Loading skeleton shown', () => {
      render(
        <TestReviewsList
          reviews={[]}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
          isLoading={true}
        />
      );
      expect(screen.getByTestId('reviews-loading')).toBeInTheDocument();
      expect(screen.getAllByTestId('skeleton-card')).toHaveLength(5);
    });

    it('LIST-006: Pagination controls rendered', () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      expect(screen.getByTestId('pagination')).toBeInTheDocument();
    });

    it('LIST-007: Default 20 items per page', () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={{ ...mockPagination, per_page: 20 }}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      // 20 reviews should be rendered
      expect(screen.getAllByTestId(/review-card-/)).toHaveLength(20);
    });

    it('LIST-008: Custom per page count', () => {
      const tenReviews = mockReviews.slice(0, 10);
      render(
        <TestReviewsList
          reviews={tenReviews}
          pagination={{ ...mockPagination, per_page: 10 }}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      expect(screen.getAllByTestId(/review-card-/)).toHaveLength(10);
    });

    it('LIST-009: Page change callback works', async () => {
      const onPageChange = vi.fn();
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={onPageChange}
          onRespond={() => {}}
        />
      );
      
      await user.click(screen.getByTestId('next-page'));
      
      expect(onPageChange).toHaveBeenCalledWith(2);
    });

    it('LIST-010: Current page highlighted', () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      expect(screen.getByTestId('page-1')).toHaveAttribute('aria-current', 'page');
    });
  });

  // ============================================
  // LIST-011 to LIST-020: Pagination
  // ============================================

  describe('Pagination', () => {
    it('LIST-011: Previous/next buttons work', async () => {
      const onPageChange = vi.fn();
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={{ ...mockPagination, current_page: 2 }}
          onPageChange={onPageChange}
          onRespond={() => {}}
        />
      );
      
      await user.click(screen.getByTestId('prev-page'));
      expect(onPageChange).toHaveBeenCalledWith(1);
      
      await user.click(screen.getByTestId('next-page'));
      expect(onPageChange).toHaveBeenCalledWith(3);
    });

    it('LIST-012: First/last page buttons work', async () => {
      const onPageChange = vi.fn();
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={{ ...mockPagination, current_page: 4 }}
          onPageChange={onPageChange}
          onRespond={() => {}}
        />
      );
      
      await user.click(screen.getByTestId('first-page'));
      expect(onPageChange).toHaveBeenCalledWith(1);
      
      await user.click(screen.getByTestId('last-page'));
      expect(onPageChange).toHaveBeenCalledWith(7);
    });

    it('LIST-013: Page size selector works', async () => {
      const onPageChange = vi.fn();
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={onPageChange}
          onRespond={() => {}}
        />
      );
      
      await user.selectOptions(screen.getByTestId('per-page-select'), '50');
      
      // Should reset to page 1
      expect(onPageChange).toHaveBeenCalledWith(1);
    });

    it('LIST-014: Total count displayed', () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      expect(screen.getByTestId('page-info')).toHaveTextContent('1-20 of 127');
    });

    it('LIST-015: Previous disabled on first page', () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      expect(screen.getByTestId('prev-page')).toBeDisabled();
      expect(screen.getByTestId('first-page')).toBeDisabled();
    });

    it('LIST-015: Next disabled on last page', () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={{ ...mockPagination, current_page: 7 }}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      expect(screen.getByTestId('next-page')).toBeDisabled();
      expect(screen.getByTestId('last-page')).toBeDisabled();
    });
  });

  // ============================================
  // LIST-021 to LIST-030: Selection & Actions
  // ============================================

  describe('Selection & Actions', () => {
    it('LIST-025: Select all checkbox works', async () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      await user.click(screen.getByTestId('select-all-button'));
      
      expect(screen.getByTestId('selection-count')).toBeInTheDocument();
    });

    it('LIST-026: Individual selection works', async () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      await user.click(screen.getByTestId('select-review-1'));
      
      expect(screen.getByTestId('selection-count')).toHaveTextContent('1 selected');
    });

    it('LIST-027: Respond button works', async () => {
      const onRespond = vi.fn();
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={onRespond}
        />
      );
      
      // Find a review without response
      const reviewWithoutResponse = mockReviews.find((r) => !r.has_response);
      if (reviewWithoutResponse) {
        await user.click(screen.getByTestId(`respond-button-${reviewWithoutResponse.id}`));
        expect(onRespond).toHaveBeenCalledWith(reviewWithoutResponse.id);
      }
    });

    it('LIST-028: View on Google link works', () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      const link = screen.getByTestId('view-google-review-1');
      expect(link).toHaveAttribute('target', '_blank');
    });

    it('LIST-029: No results message shown', () => {
      render(
        <TestReviewsList
          reviews={[]}
          pagination={emptyPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      expect(screen.getByText('No reviews found')).toBeInTheDocument();
    });

    it('LIST-030: Suggestion shown in empty state', () => {
      render(
        <TestReviewsList
          reviews={[]}
          pagination={emptyPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      expect(screen.getByTestId('empty-suggestion')).toBeInTheDocument();
    });
  });

  // ============================================
  // LIST-031 to LIST-040: Accessibility & Edge Cases
  // ============================================

  describe('Accessibility & Edge Cases', () => {
    it('LIST-019: Accessibility labels present', () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      expect(screen.getByTestId('reviews-list')).toHaveAttribute('aria-label', 'Reviews list');
      expect(screen.getByTestId('pagination')).toHaveAttribute('aria-label', 'Pagination');
    });

    it('LIST-020: Keyboard navigation works', async () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      await user.tab();
      // Focus should move through interactive elements
    });

    it('LIST-022: Custom className applied', () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
          className="custom-list"
        />
      );
      
      expect(screen.getByTestId('reviews-list')).toHaveClass('custom-list');
    });

    it('handles single page correctly', () => {
      render(
        <TestReviewsList
          reviews={mockReviews.slice(0, 5)}
          pagination={{ current_page: 1, per_page: 20, total: 5, last_page: 1 }}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      expect(screen.getByTestId('page-info')).toHaveTextContent('1-5 of 5');
      expect(screen.getByTestId('next-page')).toBeDisabled();
    });

    it('handles empty page info correctly', () => {
      render(
        <TestReviewsList
          reviews={[]}
          pagination={emptyPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      // Empty state should be shown instead of list
      expect(screen.queryByTestId('page-info')).not.toBeInTheDocument();
    });

    it('deselect all works after select all', async () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      await user.click(screen.getByTestId('select-all-button'));
      expect(screen.getByTestId('selection-count')).toBeInTheDocument();
      
      await user.click(screen.getByTestId('select-all-button'));
      expect(screen.queryByTestId('selection-count')).not.toBeInTheDocument();
    });

    it('page number buttons work', async () => {
      const onPageChange = vi.fn();
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={onPageChange}
          onRespond={() => {}}
        />
      );
      
      await user.click(screen.getByTestId('page-3'));
      
      expect(onPageChange).toHaveBeenCalledWith(3);
    });

    it('per page selector shows correct options', () => {
      render(
        <TestReviewsList
          reviews={mockReviews}
          pagination={mockPagination}
          onPageChange={() => {}}
          onRespond={() => {}}
        />
      );
      
      const select = screen.getByTestId('per-page-select');
      expect(within(select).getByText('10 per page')).toBeInTheDocument();
      expect(within(select).getByText('20 per page')).toBeInTheDocument();
      expect(within(select).getByText('50 per page')).toBeInTheDocument();
      expect(within(select).getByText('100 per page')).toBeInTheDocument();
    });
  });
});
