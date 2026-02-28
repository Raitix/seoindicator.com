import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { OnboardingModal } from '@/components/onboarding/OnboardingModal';
import { useOnboardingContext } from '@/lib/contexts/OnboardingContext';

// Mock the onboarding hook
const mockSkipOnboarding = jest.fn().mockResolvedValue(undefined);
const mockUpdateStep = jest.fn().mockResolvedValue(undefined);
const mockCompleteOnboarding = jest.fn().mockResolvedValue(undefined);

const createMockContext = (overrides = {}) => ({
  onboarding: {
    id: 1,
    currentStep: 1,
    completedSteps: [],
    websiteUrl: null,
    firstKeyword: null,
    keywordId: null,
    alertPreferences: null,
    completedAt: null,
    progressPercentage: 20,
  },
  isLoading: false,
  error: null,
  isActive: true,
  skipOnboarding: mockSkipOnboarding,
  updateStep: mockUpdateStep,
  completeOnboarding: mockCompleteOnboarding,
  startOnboarding: jest.fn().mockResolvedValue(undefined),
  refreshOnboarding: jest.fn().mockResolvedValue(undefined),
  ...overrides,
});

jest.mock('@/lib/contexts/OnboardingContext', () => ({
  useOnboardingContext: jest.fn(),
}));

const mockedUseOnboardingContext = require('@/lib/contexts/OnboardingContext').useOnboardingContext;

describe('OnboardingModal', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockedUseOnboardingContext.mockReturnValue(createMockContext());
  });

  // ==================== RENDERING ====================

  it('should render the modal with title', () => {
    render(<OnboardingModal />);

    expect(screen.getByText(/welcome to seoindicator/i)).toBeInTheDocument();
  });

  it('should show progress bar', () => {
    render(<OnboardingModal />);

    expect(screen.getByText(/step 1 of 5/i)).toBeInTheDocument();
  });

  it('should render skip button', () => {
    render(<OnboardingModal />);

    const skipButton = screen.getByRole('button', { name: /skip for now/i });
    expect(skipButton).toBeInTheDocument();
  });

  // ==================== SKIP FUNCTIONALITY ====================

  it('should call skipOnboarding when skip is clicked', async () => {
    render(<OnboardingModal />);

    const skipButton = screen.getByRole('button', { name: /skip for now/i });
    fireEvent.click(skipButton);

    await waitFor(() => {
      expect(mockSkipOnboarding).toHaveBeenCalled();
    });
  });

  // ==================== STEP NAVIGATION ====================

  it('should show step 1 (website input) by default', () => {
    render(<OnboardingModal />);

    expect(screen.getByLabelText(/website url/i)).toBeInTheDocument();
  });

  it('should show step 2 when currentStep is 2', () => {
    mockedUseOnboardingContext.mockReturnValue(createMockContext({
      onboarding: {
        id: 1,
        currentStep: 2,
        completedSteps: [1],
        websiteUrl: 'https://example.com',
        firstKeyword: null,
        keywordId: null,
        alertPreferences: null,
        completedAt: null,
        progressPercentage: 40,
      },
    }));

    render(<OnboardingModal />);

    expect(screen.getByLabelText(/keyword/i)).toBeInTheDocument();
  });

  it('should show step 5 (completion) when currentStep is 5', () => {
    mockedUseOnboardingContext.mockReturnValue(createMockContext({
      onboarding: {
        id: 1,
        currentStep: 5,
        completedSteps: [1, 2, 3, 4],
        websiteUrl: 'https://example.com',
        firstKeyword: 'test keyword',
        keywordId: 1,
        alertPreferences: { email: true, weekly: true },
        completedAt: null,
        progressPercentage: 100,
      },
    }));

    render(<OnboardingModal />);

    expect(screen.getByText(/you're all set/i)).toBeInTheDocument();
  });

  // ==================== LOADING STATE ====================

  it('should disable skip button when loading', () => {
    mockedUseOnboardingContext.mockReturnValue(createMockContext({ isLoading: true }));

    render(<OnboardingModal />);

    const skipButton = screen.getByRole('button', { name: /skip for now/i });
    expect(skipButton).toBeDisabled();
  });

  // ==================== ERROR DISPLAY ====================

  it('should display error message when present', () => {
    mockedUseOnboardingContext.mockReturnValue(createMockContext({ error: 'Server error' }));

    render(<OnboardingModal />);

    expect(screen.getByText('Server error')).toBeInTheDocument();
  });

  // ==================== PROGRESS INDICATOR ====================

  it('should show correct step count', () => {
    render(<OnboardingModal />);

    expect(screen.getByText(/step 1 of 5/i)).toBeInTheDocument();
  });

  it('should show completed steps', () => {
    mockedUseOnboardingContext.mockReturnValue(createMockContext({
      onboarding: {
        id: 1,
        currentStep: 3,
        completedSteps: [1, 2],
        websiteUrl: 'https://example.com',
        firstKeyword: 'test',
        keywordId: null,
        alertPreferences: null,
        completedAt: null,
        progressPercentage: 60,
      },
    }));

    render(<OnboardingModal />);

    expect(screen.getByText(/step 3 of 5/i)).toBeInTheDocument();
  });
});
