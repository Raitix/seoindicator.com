/**
 * @jest-environment jsdom
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import { Step3_Preview } from '../Step3_Preview';
import { OnboardingContext } from '@/lib/contexts/OnboardingContext';
import type { OnboardingContextValue, UserOnboarding } from '@/lib/types/onboarding';

// Mock MockSERPFetcher
const mockFetchRanking = vi.fn();
vi.mock('@/lib/services/mockSERPFetcher', () => ({
  mockSERPFetcher: {
    fetch: () => mockFetchRanking(),
  },
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Loader2: () => <span data-testid="loader-icon">Loading</span>,
  TrendingUp: () => <span data-testid="trending-up-icon">TrendingUp</span>,
  AlertCircle: () => <span data-testid="alert-icon">Alert</span>,
  Info: () => <span data-testid="info-icon">Info</span>,
  ChevronRight: () => <span data-testid="chevron-icon">→</span>,
  RefreshCw: () => <span data-testid="refresh-icon">Refresh</span>,
}));

// Helper to create wrapper with context
const createWrapper = (contextValue: OnboardingContextValue) => {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  
  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={queryClient}>
      <OnboardingContext.Provider value={contextValue}>
        {children}
      </OnboardingContext.Provider>
    </QueryClientProvider>
  );
};

// Helper to create mock onboarding data
const createMockOnboarding = (overrides: Partial<UserOnboarding> = {}): UserOnboarding => ({
  id: 1,
  userId: 1,
  currentStep: 3,
  completedSteps: [1, 2],
  progressPercentage: 50,
  websiteUrl: 'https://example.com',
  firstKeyword: 'best seo tools',
  keywordId: null,
  alertPreferences: null,
  completedAt: null,
  skippedAt: null,
  createdAt: '2026-03-01T00:00:00Z',
  updatedAt: '2026-03-01T00:00:00Z',
  ...overrides,
});

// Helper to create context value
const createMockContext = (overrides: Partial<OnboardingContextValue> = {}): OnboardingContextValue => ({
  onboarding: createMockOnboarding(),
  isLoading: false,
  error: null,
  isActive: true,
  startOnboarding: vi.fn(),
  updateStep: vi.fn().mockResolvedValue(undefined),
  completeOnboarding: vi.fn().mockResolvedValue(undefined),
  skipOnboarding: vi.fn().mockResolvedValue(undefined),
  refreshOnboarding: vi.fn().mockResolvedValue(undefined),
  ...overrides,
});

describe('Step3_Preview - Ranking Preview Component', () => {
  let mockContext: OnboardingContextValue;

  beforeEach(() => {
    vi.clearAllMocks();
    mockContext = createMockContext();
    // Default mock response - ranking at position 15
    mockFetchRanking.mockResolvedValue({
      position: 15,
      url: 'https://example.com',
      keyword: 'best seo tools',
    });
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  describe('RP-001: Loading State', () => {
    it('RP-001-01: displays loading state while fetching rank', () => {
      const Wrapper = createWrapper(createMockContext({ isLoading: true }));
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      expect(screen.getByText(/checking your ranking/i)).toBeInTheDocument();
    });

    it('RP-001-02: loading state has aria-busy attribute', () => {
      const Wrapper = createWrapper(createMockContext({ isLoading: true }));
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      const loadingElement = screen.getByText(/checking your ranking/i).closest('div');
      expect(loadingElement).toHaveAttribute('aria-busy', 'true');
    });

    it('RP-001-03: buttons disabled during loading', () => {
      const Wrapper = createWrapper(createMockContext({ isLoading: true }));
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).toBeDisabled();
      });
    });

    it('RP-001-04: loading state transitions to result', async () => {
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      // Initially should show loading or fetch result
      await waitFor(() => {
        expect(mockFetchRanking).toHaveBeenCalled();
      });
    });
  });

  describe('RP-002: Position Display (Success)', () => {
    it('RP-002-01: shows position number when ranking', async () => {
      mockFetchRanking.mockResolvedValue({ position: 15 });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByText(/position 15/i)).toBeInTheDocument();
      });
    });

    it('RP-002-02: shows position 0 (featured snippet)', async () => {
      mockFetchRanking.mockResolvedValue({ position: 0, isFeaturedSnippet: true });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByText(/featured snippet/i)).toBeInTheDocument();
      });
    });

    it('RP-002-03: shows top 10 position with emphasis', async () => {
      mockFetchRanking.mockResolvedValue({ position: 5 });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        const positionElement = screen.getByText(/position 5/i);
        expect(positionElement).toBeInTheDocument();
        // Check for emphasis class
        expect(positionElement.closest('div')).toHaveClass(/top|highlight/i);
      });
    });

    it('RP-002-04: shows brief explanation of position', async () => {
      mockFetchRanking.mockResolvedValue({ position: 15 });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByText(/page 2/i)).toBeInTheDocument();
      });
    });

    it('RP-002-05: shows keyword being tracked', () => {
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      expect(screen.getByText('best seo tools')).toBeInTheDocument();
    });
  });

  describe('RP-003: Not Ranking Display', () => {
    it('RP-003-01: shows "Not in top 100" message', async () => {
      mockFetchRanking.mockResolvedValue({ position: null });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByText(/not in top 100/i)).toBeInTheDocument();
      });
    });

    it('RP-003-02: shows encouraging message when not ranking', async () => {
      mockFetchRanking.mockResolvedValue({ position: null });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByText(/don't worry|improve|help you/i)).toBeInTheDocument();
      });
    });

    it('RP-003-03: shows next steps when not ranking', async () => {
      mockFetchRanking.mockResolvedValue({ position: null });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByText(/add more keywords|optimize/i)).toBeInTheDocument();
      });
    });
  });

  describe('RP-004: Error Handling', () => {
    it('RP-004-01: shows error message on fetch failure', async () => {
      mockFetchRanking.mockRejectedValue(new Error('Network timeout'));
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByText(/error|failed|network timeout/i)).toBeInTheDocument();
      });
    });

    it('RP-004-02: shows retry button on error', async () => {
      mockFetchRanking.mockRejectedValue(new Error('Network timeout'));
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
      });
    });

    it('RP-004-03: retry button triggers new fetch', async () => {
      mockFetchRanking.mockRejectedValueOnce(new Error('Network timeout'));
      mockFetchRanking.mockResolvedValueOnce({ position: 10 });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
      });
      
      const retryButton = screen.getByRole('button', { name: /retry/i });
      fireEvent.click(retryButton);
      
      expect(mockFetchRanking).toHaveBeenCalledTimes(2);
    });

    it('RP-004-04: shows continue option on error', async () => {
      mockFetchRanking.mockRejectedValue(new Error('Network timeout'));
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByText(/continue anyway|skip/i)).toBeInTheDocument();
      });
    });

    it('RP-004-05: error message is dismissible', async () => {
      mockFetchRanking.mockRejectedValue(new Error('Network timeout'));
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /dismiss|x/i })).toBeInTheDocument();
      });
    });
  });

  describe('RP-005: Navigation Buttons', () => {
    it('RP-005-01: continue button advances to step 4', async () => {
      const onNext = vi.fn();
      mockFetchRanking.mockResolvedValue({ position: 15 });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview onNext={onNext} />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /continue/i })).toBeInTheDocument();
      });
      
      const continueButton = screen.getByRole('button', { name: /continue/i });
      fireEvent.click(continueButton);
      
      expect(onNext).toHaveBeenCalled();
    });

    it('RP-005-02: skip link available', () => {
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      expect(screen.getByText(/skip for now/i)).toBeInTheDocument();
    });

    it('RP-005-03: skip advances to next step', async () => {
      const onNext = vi.fn();
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview onNext={onNext} />, { wrapper: Wrapper });
      
      const skipButton = screen.getByRole('button', { name: /skip/i });
      fireEvent.click(skipButton);
      
      await waitFor(() => {
        expect(mockContext.updateStep).toHaveBeenCalledWith(3, {});
      });
    });

    it('RP-005-04: back button returns to step 2', () => {
      const onBack = vi.fn();
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview onBack={onBack} />, { wrapper: Wrapper });
      
      const backButton = screen.queryByRole('button', { name: /back/i });
      if (backButton) {
        fireEvent.click(backButton);
        expect(onBack).toHaveBeenCalled();
      }
    });
  });

  describe('RP-006: Preview Indicator', () => {
    it('RP-006-01: shows preview indicator', () => {
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      expect(screen.getByText(/preview|sample data/i)).toBeInTheDocument();
    });

    it('RP-006-02: preview indicator has info icon', () => {
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      expect(screen.getByTestId('info-icon')).toBeInTheDocument();
    });

    it('RP-006-03: preview indicator explains data source', () => {
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      const previewText = screen.getByText(/preview|sample data/i);
      // Check for tooltip or title attribute
      const container = previewText.closest('div');
      expect(container).toHaveAttribute('title');
    });
  });

  describe('RP-007: Add More Keywords', () => {
    it('RP-007-01: shows "Add more keywords" button', async () => {
      mockFetchRanking.mockResolvedValue({ position: 15 });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /add more keywords/i })).toBeInTheDocument();
      });
    });

    it('RP-007-02: button navigates to keywords page', async () => {
      mockFetchRanking.mockResolvedValue({ position: 15 });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        const button = screen.getByRole('button', { name: /add more keywords/i });
        expect(button).toHaveAttribute('href', '/keywords');
      });
    });

    it('RP-007-03: button disabled during loading', () => {
      const Wrapper = createWrapper(createMockContext({ isLoading: true }));
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      const button = screen.queryByRole('button', { name: /add more keywords/i });
      if (button) {
        expect(button).toBeDisabled();
      }
    });
  });

  describe('RP-008: Accessibility', () => {
    it('RP-008-01: loading spinner has aria-label', () => {
      const Wrapper = createWrapper(createMockContext({ isLoading: true }));
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      const spinner = screen.getByTestId('loader-icon').closest('div');
      expect(spinner).toHaveAttribute('aria-label', /checking ranking/i);
    });

    it('RP-008-02: position display has aria-live', async () => {
      mockFetchRanking.mockResolvedValue({ position: 15 });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        const positionContainer = screen.getByText(/position 15/i).closest('div');
        expect(positionContainer).toHaveAttribute('aria-live', 'polite');
      });
    });

    it('RP-008-03: error has role="alert"', async () => {
      mockFetchRanking.mockRejectedValue(new Error('Network timeout'));
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        const errorElement = screen.getByText(/error|failed|network timeout/i).closest('div');
        expect(errorElement).toHaveAttribute('role', 'alert');
      });
    });

    it('RP-008-04: keyboard navigation works', () => {
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).not.toHaveAttribute('tabindex', '-1');
      });
    });

    it('RP-008-05: screen reader announces result', async () => {
      mockFetchRanking.mockResolvedValue({ position: 15 });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        const liveRegion = document.querySelector('[aria-live="polite"]');
        expect(liveRegion).toBeInTheDocument();
      });
    });
  });

  describe('RP-009: MockSERPFetcher Integration', () => {
    it('RP-009-01: fetches ranking on mount', async () => {
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(mockFetchRanking).toHaveBeenCalled();
      });
    });

    it('RP-009-02: passes keyword and URL to fetcher', async () => {
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(mockFetchRanking).toHaveBeenCalledWith(
          expect.objectContaining({
            keyword: 'best seo tools',
            targetUrl: 'https://example.com',
          })
        );
      });
    });

    it('RP-009-03: handles fetcher response correctly', async () => {
      mockFetchRanking.mockResolvedValue({ position: 25 });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByText(/position 25/i)).toBeInTheDocument();
      });
    });

    it('RP-009-04: handles fetcher error', async () => {
      mockFetchRanking.mockRejectedValue(new Error('Fetch failed'));
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByText(/error|failed/i)).toBeInTheDocument();
      });
    });
  });

  describe('RP-010: Edge Cases', () => {
    it('RP-010-01: handles empty keyword gracefully', () => {
      const emptyKeywordContext = createMockContext({
        onboarding: createMockOnboarding({ firstKeyword: '' }),
      });
      const Wrapper = createWrapper(emptyKeywordContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      // Should show message about missing keyword or handle gracefully
      expect(screen.getByText(/keyword|enter|provide/i)).toBeInTheDocument();
    });

    it('RP-010-02: handles empty URL gracefully', () => {
      const emptyUrlContext = createMockContext({
        onboarding: createMockOnboarding({ websiteUrl: '' }),
      });
      const Wrapper = createWrapper(emptyUrlContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      // Should show message about missing URL or handle gracefully
      expect(screen.getByText(/website|url|enter|provide/i)).toBeInTheDocument();
    });

    it('RP-010-03: handles very high position', async () => {
      mockFetchRanking.mockResolvedValue({ position: 99 });
      const Wrapper = createWrapper(mockContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      await waitFor(() => {
        expect(screen.getByText(/position 99/i)).toBeInTheDocument();
      });
    });

    it('RP-010-04: handles special characters in keyword', () => {
      const specialKeywordContext = createMockContext({
        onboarding: createMockOnboarding({ firstKeyword: "what's the best seo?" }),
      });
      const Wrapper = createWrapper(specialKeywordContext);
      render(<Step3_Preview />, { wrapper: Wrapper });
      
      expect(screen.getByText("what's the best seo?")).toBeInTheDocument();
    });
  });
});
