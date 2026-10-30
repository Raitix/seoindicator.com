import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { OnboardingProvider, useOnboardingContext } from '@/lib/contexts/OnboardingContext';

// Mock the API module
const mockMutate = jest.fn();

jest.mock('@/lib/api/onboarding', () => ({
  useOnboarding: jest.fn(),
  startOnboarding: jest.fn(),
  updateOnboardingStep: jest.fn(),
  completeOnboarding: jest.fn(),
  skipOnboarding: jest.fn(),
}));

const mockedApi = require('@/lib/api/onboarding');

// Test component to access context
const TestComponent = () => {
  const {
    onboarding,
    isLoading,
    error,
    isActive,
    startOnboarding,
    updateStep,
    skipOnboarding,
    completeOnboarding,
  } = useOnboardingContext();

  return (
    <div>
      <div data-testid="is-active">{isActive.toString()}</div>
      <div data-testid="current-step">{onboarding?.currentStep ?? 1}</div>
      <div data-testid="is-loading">{isLoading.toString()}</div>
      <div data-testid="error">{error || 'no-error'}</div>
      <div data-testid="website-url">{onboarding?.websiteUrl || 'no-url'}</div>
      <div data-testid="progress">{onboarding?.progressPercentage ?? 0}%</div>
      <button onClick={startOnboarding} data-testid="start-btn">Start</button>
      <button onClick={() => updateStep(1, { website_url: 'https://example.com' })} data-testid="next-btn">Next</button>
      <button onClick={skipOnboarding} data-testid="skip-btn">Skip</button>
      <button onClick={completeOnboarding} data-testid="complete-btn">Complete</button>
    </div>
  );
};

const renderWithProviders = () => {
  return render(
    <OnboardingProvider>
      <TestComponent />
    </OnboardingProvider>
  );
};

describe('OnboardingProvider', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    // Default mock for useOnboarding hook
    mockedApi.useOnboarding.mockReturnValue({
      onboarding: null,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });
  });

  afterEach(() => {
    jest.resetAllMocks();
  });

  // ==================== INITIAL STATE ====================

  it('should have correct initial state', () => {
    renderWithProviders();

    expect(screen.getByTestId('is-active')).toHaveTextContent('false');
    expect(screen.getByTestId('current-step')).toHaveTextContent('1');
    expect(screen.getByTestId('is-loading')).toHaveTextContent('false');
    expect(screen.getByTestId('error')).toHaveTextContent('no-error');
  });

  // ==================== START ONBOARDING ====================

  it('should start onboarding when startOnboarding is called', async () => {
    mockedApi.startOnboarding.mockResolvedValue({
      id: 1,
      currentStep: 1,
      completedSteps: [],
    });

    renderWithProviders();

    await act(async () => {
      fireEvent.click(screen.getByTestId('start-btn'));
    });

    expect(mockedApi.startOnboarding).toHaveBeenCalled();
    expect(mockMutate).toHaveBeenCalled();
  });

  it('should set loading state while starting', async () => {
    mockedApi.useOnboarding.mockReturnValue({
      onboarding: null,
      isLoading: true,
      error: null,
      mutate: mockMutate,
    });

    renderWithProviders();

    expect(screen.getByTestId('is-loading')).toHaveTextContent('true');
  });

  // ==================== UPDATE STEP ====================

  it('should call updateStep API and update state', async () => {
    const mockOnboarding = {
      id: 1,
      currentStep: 1,
      completedSteps: [],
      websiteUrl: null,
      firstKeyword: null,
      keywordId: null,
      alertPreferences: null,
      completedAt: null,
      progressPercentage: 20,
    };

    mockedApi.useOnboarding.mockReturnValue({
      onboarding: mockOnboarding,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    mockedApi.updateOnboardingStep.mockResolvedValue({
      id: 1,
      currentStep: 2,
      completedSteps: [1],
      websiteUrl: 'https://example.com',
    });

    renderWithProviders();

    await act(async () => {
      fireEvent.click(screen.getByTestId('next-btn'));
    });

    expect(mockedApi.updateOnboardingStep).toHaveBeenCalledWith(
      1,
      { website_url: 'https://example.com' }
    );
  });

  // ==================== SKIP ONBOARDING ====================

  it('should call skip API', async () => {
    const mockOnboarding = {
      id: 1,
      currentStep: 1,
      completedSteps: [],
      websiteUrl: null,
      firstKeyword: null,
      keywordId: null,
      alertPreferences: null,
      completedAt: null,
      progressPercentage: 20,
    };

    mockedApi.useOnboarding.mockReturnValue({
      onboarding: mockOnboarding,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    mockedApi.skipOnboarding.mockResolvedValue('2026-02-28T10:00:00Z');

    renderWithProviders();

    await act(async () => {
      fireEvent.click(screen.getByTestId('skip-btn'));
    });

    expect(mockedApi.skipOnboarding).toHaveBeenCalled();
  });

  // ==================== COMPLETE ONBOARDING ====================

  it('should call complete API', async () => {
    const mockOnboarding = {
      id: 1,
      currentStep: 5,
      completedSteps: [1, 2, 3, 4],
      websiteUrl: 'https://example.com',
      firstKeyword: 'test',
      keywordId: 1,
      alertPreferences: { email: true, weekly: true },
      completedAt: null,
      progressPercentage: 100,
    };

    mockedApi.useOnboarding.mockReturnValue({
      onboarding: mockOnboarding,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    mockedApi.completeOnboarding.mockResolvedValue({
      completedAt: '2026-02-28T10:00:00Z',
      keywordId: 1,
    });

    renderWithProviders();

    await act(async () => {
      fireEvent.click(screen.getByTestId('complete-btn'));
    });

    expect(mockedApi.completeOnboarding).toHaveBeenCalled();
  });

  // ==================== ERROR HANDLING ====================

  it('should set error state when API fails', async () => {
    mockedApi.useOnboarding.mockReturnValue({
      onboarding: null,
      isLoading: false,
      error: 'API Error',
      mutate: mockMutate,
    });

    renderWithProviders();

    expect(screen.getByTestId('error')).toHaveTextContent('API Error');
  });

  // ==================== PROGRESS LOADING ====================

  it('should load existing progress on mount', async () => {
    const mockOnboarding = {
      id: 1,
      currentStep: 3,
      completedSteps: [1, 2],
      websiteUrl: 'https://example.com',
      firstKeyword: null,
      keywordId: null,
      alertPreferences: null,
      completedAt: null,
      progressPercentage: 60,
    };

    mockedApi.useOnboarding.mockReturnValue({
      onboarding: mockOnboarding,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    renderWithProviders();

    expect(screen.getByTestId('current-step')).toHaveTextContent('3');
    expect(screen.getByTestId('website-url')).toHaveTextContent('https://example.com');
    expect(screen.getByTestId('progress')).toHaveTextContent('60%');
  });

  it('should not be active if user already completed', async () => {
    const mockOnboarding = {
      id: 1,
      currentStep: 5,
      completedSteps: [1, 2, 3, 4, 5],
      websiteUrl: 'https://example.com',
      firstKeyword: 'test',
      keywordId: 1,
      alertPreferences: { email: true, weekly: true },
      completedAt: '2026-02-28T10:00:00Z',
      progressPercentage: 100,
    };

    mockedApi.useOnboarding.mockReturnValue({
      onboarding: mockOnboarding,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    renderWithProviders();

    expect(screen.getByTestId('is-active')).toHaveTextContent('false');
  });

  // ==================== DATA PERSISTENCE ====================

  it('should persist step data', async () => {
    const mockOnboarding = {
      id: 1,
      currentStep: 2,
      completedSteps: [1],
      websiteUrl: 'https://test.com',
      firstKeyword: 'test keyword',
      keywordId: null,
      alertPreferences: null,
      completedAt: null,
      progressPercentage: 40,
    };

    mockedApi.useOnboarding.mockReturnValue({
      onboarding: mockOnboarding,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    renderWithProviders();

    expect(screen.getByTestId('website-url')).toHaveTextContent('https://test.com');
  });
});

// ==================== CONTEXT HOOK TESTS ====================

describe('useOnboardingContext hook', () => {
  it('should throw error when used outside provider', () => {
    const consoleError = jest.spyOn(console, 'error').mockImplementation(() => {});

    expect(() => {
      render(<TestComponent />);
    }).toThrow('useOnboardingContext must be used within an OnboardingProvider');

    consoleError.mockRestore();
  });
});
