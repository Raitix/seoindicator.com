import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { OnboardingProvider, useOnboarding } from '../OnboardingProvider';
import * as api from '@/lib/api';

// Mock the API module
jest.mock('@/lib/api', () => ({
  onboardingApi: {
    getProgress: jest.fn(),
    start: jest.fn(),
    updateStep: jest.fn(),
    complete: jest.fn(),
    skip: jest.fn(),
  },
}));

const mockedApi = api as jest.Mocked<typeof api>;

// Test component to access context
const TestComponent = () => {
  const {
    state,
    startOnboarding,
    nextStep,
    prevStep,
    skipOnboarding,
    completeOnboarding,
  } = useOnboarding();

  return (
    <div>
      <div data-testid="is-open">{state.isOpen.toString()}</div>
      <div data-testid="current-step">{state.currentStep}</div>
      <div data-testid="is-loading">{state.isLoading.toString()}</div>
      <div data-testid="error">{state.error || 'no-error'}</div>
      <div data-testid="website-url">{state.data.websiteUrl || 'no-url'}</div>
      <div data-testid="progress">{state.progressPercentage}%</div>
      <button onClick={startOnboarding} data-testid="start-btn">Start</button>
      <button onClick={() => nextStep({ websiteUrl: 'https://example.com' })} data-testid="next-btn">Next</button>
      <button onClick={prevStep} data-testid="prev-btn">Prev</button>
      <button onClick={skipOnboarding} data-testid="skip-btn">Skip</button>
      <button onClick={completeOnboarding} data-testid="complete-btn">Complete</button>
    </div>
  );
};

const renderWithProviders = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
    },
  });

  return render(
    <QueryClientProvider client={queryClient}>
      <OnboardingProvider>
        <TestComponent />
      </OnboardingProvider>
    </QueryClientProvider>
  );
};

describe('OnboardingProvider', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ==================== INITIAL STATE ====================

  it('should have correct initial state', () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({ data: null });

    renderWithProviders();

    expect(screen.getByTestId('is-open')).toHaveTextContent('false');
    expect(screen.getByTestId('current-step')).toHaveTextContent('1');
    expect(screen.getByTestId('is-loading')).toHaveTextContent('false');
    expect(screen.getByTestId('error')).toHaveTextContent('no-error');
  });

  // ==================== START ONBOARDING ====================

  it('should start onboarding when startOnboarding is called', async () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({ data: null });
    mockedApi.onboardingApi.start.mockResolvedValue({
      data: { id: 1, current_step: 1, completed_steps: [] },
    });

    renderWithProviders();

    await act(async () => {
      fireEvent.click(screen.getByTestId('start-btn'));
    });

    await waitFor(() => {
      expect(screen.getByTestId('is-open')).toHaveTextContent('true');
    });
    expect(mockedApi.onboardingApi.start).toHaveBeenCalled();
  });

  it('should set loading state while starting', async () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({ data: null });
    mockedApi.onboardingApi.start.mockImplementation(() => 
      new Promise(resolve => setTimeout(() => resolve({ data: { id: 1, current_step: 1 } }), 100))
    );

    renderWithProviders();

    fireEvent.click(screen.getByTestId('start-btn'));

    expect(screen.getByTestId('is-loading')).toHaveTextContent('true');

    await waitFor(() => {
      expect(screen.getByTestId('is-loading')).toHaveTextContent('false');
    });
  });

  // ==================== NEXT STEP ====================

  it('should call nextStep API and update state', async () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({ data: null });
    mockedApi.onboardingApi.updateStep.mockResolvedValue({
      data: { id: 1, current_step: 2, completed_steps: [1], website_url: 'https://example.com' },
    });

    renderWithProviders();

    await act(async () => {
      fireEvent.click(screen.getByTestId('next-btn'));
    });

    await waitFor(() => {
      expect(mockedApi.onboardingApi.updateStep).toHaveBeenCalledWith(
        expect.objectContaining({
          step: 1,
          data: { websiteUrl: 'https://example.com' },
        })
      );
    });
  });

  // ==================== PREVIOUS STEP ====================

  it('should decrease current step when prevStep is called', async () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({
      data: { id: 1, current_step: 2, completed_steps: [1] },
    });

    renderWithProviders();

    await waitFor(() => {
      expect(screen.getByTestId('current-step')).toHaveTextContent('2');
    });

    fireEvent.click(screen.getByTestId('prev-btn'));

    await waitFor(() => {
      expect(screen.getByTestId('current-step')).toHaveTextContent('1');
    });
  });

  it('should not go below step 1', async () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({ data: null });

    renderWithProviders();

    await waitFor(() => {
      expect(screen.getByTestId('current-step')).toHaveTextContent('1');
    });

    fireEvent.click(screen.getByTestId('prev-btn'));

    expect(screen.getByTestId('current-step')).toHaveTextContent('1');
  });

  // ==================== SKIP ONBOARDING ====================

  it('should call skip API and close modal', async () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({ data: null });
    mockedApi.onboardingApi.skip.mockResolvedValue({
      data: { skipped_at: '2026-02-28T10:00:00Z' },
    });

    renderWithProviders();

    await act(async () => {
      fireEvent.click(screen.getByTestId('skip-btn'));
    });

    await waitFor(() => {
      expect(mockedApi.onboardingApi.skip).toHaveBeenCalled();
      expect(screen.getByTestId('is-open')).toHaveTextContent('false');
    });
  });

  // ==================== COMPLETE ONBOARDING ====================

  it('should call complete API and close modal', async () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({
      data: { id: 1, current_step: 5 },
    });
    mockedApi.onboardingApi.complete.mockResolvedValue({
      data: { completed_at: '2026-02-28T10:00:00Z', keyword_id: 1 },
    });

    renderWithProviders();

    await act(async () => {
      fireEvent.click(screen.getByTestId('complete-btn'));
    });

    await waitFor(() => {
      expect(mockedApi.onboardingApi.complete).toHaveBeenCalled();
      expect(screen.getByTestId('is-open')).toHaveTextContent('false');
    });
  });

  // ==================== ERROR HANDLING ====================

  it('should set error state when API fails', async () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({ data: null });
    mockedApi.onboardingApi.start.mockRejectedValue(new Error('API Error'));

    renderWithProviders();

    await act(async () => {
      fireEvent.click(screen.getByTestId('start-btn'));
    });

    await waitFor(() => {
      expect(screen.getByTestId('error')).toHaveTextContent('API Error');
    });
  });

  // ==================== PROGRESS LOADING ====================

  it('should load existing progress on mount', async () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({
      data: {
        id: 1,
        current_step: 3,
        completed_steps: [1, 2],
        website_url: 'https://example.com',
        progress_percentage: 60,
      },
    });

    renderWithProviders();

    await waitFor(() => {
      expect(screen.getByTestId('current-step')).toHaveTextContent('3');
      expect(screen.getByTestId('website-url')).toHaveTextContent('https://example.com');
      expect(screen.getByTestId('progress')).toHaveTextContent('60%');
    });
  });

  it('should not open modal if user already completed', async () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({
      data: {
        id: 1,
        current_step: 5,
        completed_at: '2026-02-28T10:00:00Z',
      },
    });

    renderWithProviders();

    await waitFor(() => {
      expect(screen.getByTestId('is-open')).toHaveTextContent('false');
    });
  });

  it('should not open modal if user skipped', async () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({
      data: {
        id: 1,
        current_step: 2,
        skipped_at: '2026-02-28T10:00:00Z',
      },
    });

    renderWithProviders();

    await waitFor(() => {
      expect(screen.getByTestId('is-open')).toHaveTextContent('false');
    });
  });

  // ==================== DATA PERSISTENCE ====================

  it('should persist step data', async () => {
    mockedApi.onboardingApi.getProgress.mockResolvedValue({
      data: {
        id: 1,
        current_step: 2,
        completed_steps: [1],
        website_url: 'https://test.com',
        first_keyword: 'test keyword',
      },
    });

    renderWithProviders();

    await waitFor(() => {
      expect(screen.getByTestId('website-url')).toHaveTextContent('https://test.com');
    });
  });
});

// ==================== CONTEXT HOOK TESTS ====================

describe('useOnboarding hook', () => {
  it('should throw error when used outside provider', () => {
    const consoleError = jest.spyOn(console, 'error').mockImplementation(() => {});

    expect(() => {
      render(<TestComponent />);
    }).toThrow('useOnboarding must be used within an OnboardingProvider');

    consoleError.mockRestore();
  });
});
