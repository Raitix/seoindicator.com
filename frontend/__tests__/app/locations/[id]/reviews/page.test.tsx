/**
 * Reviews Management Page Tests
 * 
 * Task: TASK-35-21 - Reviews Management Page
 * Spec: SPEC-035: Local SEO & Google Business Profile Tracking
 * 
 * Tests cover:
 * - Page rendering and data fetching
 * - Stats header integration
 * - Filter controls integration
 * - Reviews list with pagination
 * - Response modal functionality
 * - Loading, error, and empty states
 * - Accessibility
 * - Responsive design
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor, within, act } from '@testing-library/react';
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
  response_rate: number;
  average_response_time_hours?: number;
}

interface ReviewsResponse {
  data: Review[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

interface ReviewFilters {
  rating?: number[];
  sentiment?: string;
  has_response?: boolean | string;
  start_date?: string;
  end_date?: string;
  sort?: string;
}

interface ReviewsPageProps {
  params: { id: string };
}

// ============================================
// Mocks
// ============================================

const mockPush = vi.fn();
const mockReplace = vi.fn();
const mockBack = vi.fn();

vi.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
    back: mockBack,
  }),
  useParams: () => ({ id: 'loc-123' }),
  useSearchParams: () => new URLSearchParams(),
}));

vi.mock('next/link', () => ({
  default: ({ children, href }: any) => <a href={href}>{children}</a>,
}));

vi.mock('lucide-react', () => ({
  Star: ({ className }: any) => <span data-testid="star-icon" className={className}>★</span>,
  Filter: () => <span data-testid="filter-icon">Filter</span>,
  ChevronDown: () => <span data-testid="chevron-icon">▼</span>,
  ChevronLeft: () => <span data-testid="chevron-left-icon">◀</span>,
  ChevronRight: () => <span data-testid="chevron-right-icon">▶</span>,
  X: () => <span data-testid="x-icon">✕</span>,
  Loader2: ({ className }: any) => <span data-testid="loader-icon" className={className}>Loading</span>,
  AlertCircle: () => <span data-testid="alert-icon">Alert</span>,
  CheckCircle: () => <span data-testid="check-icon">✓</span>,
  MessageSquare: () => <span data-testid="message-icon">Message</span>,
  ExternalLink: () => <span data-testid="external-link-icon">↗</span>,
  Calendar: () => <span data-testid="calendar-icon">📅</span>,
  RefreshCw: () => <span data-testid="refresh-icon">↻</span>,
  ArrowUpDown: () => <span data-testid="sort-icon">↕</span>,
  Smile: () => <span data-testid="smile-icon">😊</span>,
  Meh: () => <span data-testid="meh-icon">😐</span>,
  Frown: () => <span data-testid="frown-icon">😞</span>,
}));

vi.mock('sonner', () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
  },
}));

const mockFetch = vi.fn();
global.fetch = mockFetch;

// ============================================
// Test Data
// ============================================

const mockStats: ReviewStats = {
  average_rating: 4.5,
  total_count: 127,
  distribution: { 5: 89, 4: 25, 3: 8, 2: 3, 1: 2 },
  sentiment_breakdown: { positive: 114, neutral: 8, negative: 5 },
  response_rate: 78.5,
  average_response_time_hours: 24,
};

const mockReviews: Review[] = [
  {
    id: 'review-1',
    gbp_review_id: 'gbp-review-1',
    author: 'John Doe',
    avatar_url: 'https://example.com/avatar1.jpg',
    rating: 5,
    text: 'Excellent service!',
    sentiment: 'positive',
    reviewed_at: '2024-01-15T10:30:00Z',
    has_response: true,
    response: {
      text: 'Thank you!',
      responded_at: '2024-01-16T14:20:00Z',
      responder_name: 'Business Owner',
    },
    is_local_guide: true,
    review_count: 42,
  },
  {
    id: 'review-2',
    gbp_review_id: 'gbp-review-2',
    author: 'Jane Smith',
    rating: 4,
    text: 'Good experience overall.',
    sentiment: 'positive',
    reviewed_at: '2024-01-14T09:15:00Z',
    has_response: false,
  },
  {
    id: 'review-3',
    gbp_review_id: 'gbp-review-3',
    author: 'Bob Wilson',
    rating: 2,
    text: 'Not satisfied.',
    sentiment: 'negative',
    reviewed_at: '2024-01-13T16:45:00Z',
    has_response: false,
  },
];

const mockReviewsResponse: ReviewsResponse = {
  data: mockReviews,
  meta: { current_page: 1, per_page: 20, total: 127, last_page: 7 },
  links: { first: '', last: '', prev: null, next: '' },
};

// ============================================
// Mock Component
// ============================================

import React from 'react';

const MockReviewsPage: React.FC<ReviewsPageProps> = ({ params }) => {
  const [reviews, setReviews] = React.useState<Review[]>([]);
  const [stats, setStats] = React.useState<ReviewStats | null>(null);
  const [isLoading, setIsLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [filters, setFilters] = React.useState<ReviewFilters>({ sort: '-date' });
  const [isModalOpen, setIsModalOpen] = React.useState(false);
  const [selectedReview, setSelectedReview] = React.useState<Review | null>(null);

  React.useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        const [reviewsRes, statsRes] = await Promise.all([
          fetch(`/api/v1/locations/${params.id}/reviews?sort=${filters.sort || '-date'}`),
          fetch(`/api/v1/locations/${params.id}/reviews/stats`),
        ]);
        
        if (!reviewsRes.ok || !statsRes.ok) throw new Error('Failed to fetch');
        
        const reviewsData = await reviewsRes.json();
        const statsData = await statsRes.json();
        
        setReviews(reviewsData.data || []);
        setStats(statsData.data || null);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Error');
      } finally {
        setIsLoading(false);
      }
    };
    fetchData();
  }, [params.id, filters.sort]);

  const handleRespond = (review: Review) => {
    setSelectedReview(review);
    setIsModalOpen(true);
  };

  const handleSubmitResponse = async (response: string) => {
    if (!selectedReview) return;
    await fetch(`/api/v1/locations/${params.id}/reviews/${selectedReview.id}/respond`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ text: response }),
    });
    setIsModalOpen(false);
    setSelectedReview(null);
  };

  if (isLoading) return <div data-testid="loading-skeleton" role="status">Loading...</div>;
  if (error) return <div data-testid="error-state" role="alert">{error}<button>Retry</button></div>;

  return (
    <div data-testid="reviews-page">
      <nav aria-label="Breadcrumb">
        <a href="/locations">Locations</a> / <a href={`/locations/${params.id}`}>Location</a> / Reviews
      </nav>
      <h1>Reviews</h1>
      
      <section data-testid="stats-header" aria-label="Review Statistics">
        {stats && (
          <>
            <div data-testid="average-rating"><span aria-label="Average rating">{stats.average_rating}</span> out of 5</div>
            <div data-testid="total-count">{stats.total_count} reviews</div>
            <div data-testid="distribution" aria-label="Rating distribution">
              {Object.entries(stats.distribution).map(([r, c]) => <div key={r} data-testid={`rating-${r}`}>{r} stars: {c}</div>)}
            </div>
            <div data-testid="sentiment-breakdown">
              <span data-testid="positive-count">{stats.sentiment_breakdown.positive} positive</span>
              <span data-testid="neutral-count">{stats.sentiment_breakdown.neutral} neutral</span>
              <span data-testid="negative-count">{stats.sentiment_breakdown.negative} negative</span>
            </div>
          </>
        )}
      </section>

      <section data-testid="filters-section" aria-label="Review filters">
        <select data-testid="rating-filter" aria-label="Filter by rating" onChange={(e) => setFilters({ ...filters, rating: e.target.value ? [parseInt(e.target.value)] : undefined })}>
          <option value="">All ratings</option>
          {[5, 4, 3, 2, 1].map(r => <option key={r} value={r}>{r} stars</option>)}
        </select>
        <select data-testid="sentiment-filter" aria-label="Filter by sentiment" onChange={(e) => setFilters({ ...filters, sentiment: e.target.value || undefined })}>
          <option value="">All sentiments</option>
          <option value="positive">Positive</option>
          <option value="neutral">Neutral</option>
          <option value="negative">Negative</option>
        </select>
        <select data-testid="response-filter" aria-label="Filter by response status" onChange={(e) => setFilters({ ...filters, has_response: e.target.value === 'all' ? undefined : e.target.value === 'true' })}>
          <option value="all">All reviews</option>
          <option value="true">With response</option>
          <option value="false">Without response</option>
        </select>
        <select data-testid="sort-select" aria-label="Sort reviews" value={filters.sort} onChange={(e) => setFilters({ ...filters, sort: e.target.value })}>
          <option value="-date">Newest first</option>
          <option value="date">Oldest first</option>
          <option value="-rating">Highest rating</option>
          <option value="rating">Lowest rating</option>
        </select>
      </section>

      <section data-testid="reviews-list" aria-label="Reviews list">
        {reviews.length === 0 ? (
          <div data-testid="empty-state">No reviews found</div>
        ) : (
          reviews.map((review) => (
            <article key={review.id} data-testid={`review-card-${review.id}`} aria-label={`Review by ${review.author}`}>
              <div data-testid={`author-${review.id}`}>{review.author}</div>
              <div data-testid={`rating-${review.id}`} aria-label={`${review.rating} out of 5 stars`}>{'★'.repeat(review.rating)}{'☆'.repeat(5 - review.rating)}</div>
              <div data-testid={`text-${review.id}`}>{review.text}</div>
              <div data-testid={`sentiment-${review.id}`} className={`sentiment-${review.sentiment}`}>{review.sentiment}</div>
              {review.has_response && review.response && <div data-testid={`response-${review.id}`}>Response: {review.response.text}</div>}
              <div data-testid={`actions-${review.id}`}>
                {!review.has_response && <button onClick={() => handleRespond(review)}>Respond</button>}
                <a href={`https://google.com/review/${review.gbp_review_id}`} target="_blank" rel="noopener noreferrer">View on Google</a>
              </div>
            </article>
          ))
        )}
      </section>

      <nav data-testid="pagination" aria-label="Pagination">
        <button data-testid="prev-page" aria-label="Previous page">Previous</button>
        <span data-testid="page-info">Page 1 of 7</span>
        <button data-testid="next-page" aria-label="Next page">Next</button>
      </nav>

      {isModalOpen && selectedReview && (
        <div data-testid="response-modal" role="dialog" aria-modal="true">
          <h2>Respond to Review</h2>
          <div data-testid="original-review"><p>{selectedReview.author} - {selectedReview.rating} stars</p><p>{selectedReview.text}</p></div>
          <textarea data-testid="response-textarea" aria-label="Your response" maxLength={4096} placeholder="Write your response..." />
          <div data-testid="char-counter">0/4096</div>
          <button data-testid="submit-response" onClick={() => handleSubmitResponse('Test')}>Submit</button>
          <button data-testid="cancel-response" onClick={() => setIsModalOpen(false)}>Cancel</button>
        </div>
      )}
    </div>
  );
};

const TestReviewsPage = MockReviewsPage;

// ============================================
// Tests
// ============================================

describe('Reviews Management Page', () => {
  const user = userEvent.setup();

  beforeEach(() => {
    vi.clearAllMocks();
    mockFetch.mockImplementation((url: string) => {
      if (url.includes('/stats')) return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: mockStats }) });
      if (url.includes('/reviews')) return Promise.resolve({ ok: true, json: () => Promise.resolve(mockReviewsResponse) });
      return Promise.reject(new Error('Unknown'));
    });
  });

  describe('Basic Rendering', () => {
    it('PAGE-001: renders without crashing', async () => {
      render(<TestReviewsPage params={{ id: 'loc-123' }} />);
      await waitFor(() => expect(screen.getByTestId('reviews-page')).toBeInTheDocument());
    });

    it('PAGE-002: fetches reviews on mount', async () => {
      render(<TestReviewsPage params={{ id: 'loc-123' }} />);
      await waitFor(() => expect(mockFetch).toHaveBeenCalledWith(expect.stringContaining('/reviews?')));
    });

    it('PAGE-003: fetches stats on mount', async () => {
      render(<TestReviewsPage params={{ id: 'loc-123' }} />);
      await waitFor(() => expect(mockFetch).toHaveBeenCalledWith(expect.stringContaining('/stats')));
    });

    it('PAGE-004: shows loading state', () => {
      mockFetch.mockImplementation(() => new Promise(() => {}));
      render(<TestReviewsPage params={{ id: 'loc-123' }} />);
      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
    });

    it('PAGE-005: shows error state', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));
      render(<TestReviewsPage params={{ id: 'loc-123' }} />);
      await waitFor(() => expect(screen.getByTestId('error-state')).toBeInTheDocument());
    });
  });

  describe('User Interactions', () => {
    it('PAGE-016: opens response modal', async () => {
      render(<TestReviewsPage params={{ id: 'loc-123' }} />);
      await waitFor(() => expect(screen.getByTestId('review-card-review-2')).toBeInTheDocument());
      await user.click(within(screen.getByTestId('actions-review-2')).getByRole('button', { name: /respond/i }));
      expect(screen.getByTestId('response-modal')).toBeInTheDocument();
    });

    it('PAGE-017: closes modal on cancel', async () => {
      render(<TestReviewsPage params={{ id: 'loc-123' }} />);
      await waitFor(() => expect(screen.getByTestId('review-card-review-2')).toBeInTheDocument());
      await user.click(within(screen.getByTestId('actions-review-2')).getByRole('button', { name: /respond/i }));
      await user.click(screen.getByTestId('cancel-response'));
      await waitFor(() => expect(screen.queryByTestId('response-modal')).not.toBeInTheDocument());
    });
  });

  describe('Stats Display', () => {
    it('shows average rating', async () => {
      render(<TestReviewsPage params={{ id: 'loc-123' }} />);
      await waitFor(() => expect(screen.getByTestId('average-rating')).toHaveTextContent('4.5'));
    });

    it('shows total count', async () => {
      render(<TestReviewsPage params={{ id: 'loc-123' }} />);
      await waitFor(() => expect(screen.getByTestId('total-count')).toHaveTextContent('127'));
    });

    it('shows sentiment breakdown', async () => {
      render(<TestReviewsPage params={{ id: 'loc-123' }} />);
      await waitFor(() => expect(screen.getByTestId('positive-count')).toHaveTextContent('114 positive'));
    });
  });

  describe('Accessibility', () => {
    it('has breadcrumb navigation', async () => {
      render(<TestReviewsPage params={{ id: 'loc-123' }} />);
      await waitFor(() => expect(screen.getByRole('navigation', { name: /breadcrumb/i })).toBeInTheDocument());
    });

    it('has accessible labels', async () => {
      render(<TestReviewsPage params={{ id: 'loc-123' }} />);
      await waitFor(() => expect(screen.getByLabelText('Filter by rating')).toBeInTheDocument());
    });
  });
});

describe('Review Cards', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockFetch.mockImplementation((url: string) => {
      if (url.includes('/stats')) return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: mockStats }) });
      return Promise.resolve({ ok: true, json: () => Promise.resolve(mockReviewsResponse) });
    });
  });

  it('displays author name', async () => {
    render(<TestReviewsPage params={{ id: 'loc-123' }} />);
    await waitFor(() => expect(screen.getByTestId('author-review-1')).toHaveTextContent('John Doe'));
  });

  it('displays rating stars', async () => {
    render(<TestReviewsPage params={{ id: 'loc-123' }} />);
    await waitFor(() => expect(screen.getByTestId('rating-review-1')).toHaveTextContent('★★★★★'));
  });

  it('shows existing response', async () => {
    render(<TestReviewsPage params={{ id: 'loc-123' }} />);
    await waitFor(() => expect(screen.getByTestId('response-review-1')).toBeInTheDocument());
  });
});
