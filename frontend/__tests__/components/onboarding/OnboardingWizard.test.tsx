import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { OnboardingWizard } from '@/components/onboarding/OnboardingWizard';
import { useOnboardingContext } from '@/lib/contexts/OnboardingContext';

// Mock the onboarding hook
jest.mock('@/lib/contexts/OnboardingContext', () => ({
  useOnboardingContext: jest.fn(),
}));

// Mock step components
jest.mock('@/components/onboarding/steps/Step1_Website', () => ({
  Step1_Website: () => <div data-testid="step-1">Step 1: Website</div>,
}));

jest.mock('@/components/onboarding/steps/Step2_Keyword', () => ({
  Step2_Keyword: () => <div data-testid="step-2">Step 2: Keyword</div>,
}));

jest.mock('@/components/onboarding/steps/Step3_Preview', () => ({
  Step3_Preview: () => <div data-testid="step-3">Step 3: Preview</div>,
}));

jest.mock('@/components/onboarding/steps/Step4_Alerts', () => ({
  Step4_Alerts: () => <div data-testid="step-4">Step 4: Alerts</div>,
}));

jest.mock('@/components/onboarding/steps/Step5_Complete', () => ({
  Step5_Complete: () => <div data-testid="step-5">Step 5: Complete</div>,
}));

const mockedUseOnboardingContext = useOnboardingContext as jest.MockedFunction<typeof useOnboardingContext>;

describe('OnboardingWizard', () => {
  const defaultOnboarding = {
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

  const mockHandlers = {
    onboarding: defaultOnboarding,
    isLoading: false,
    error: null,
    isActive: true,
    startOnboarding: jest.fn().mockResolvedValue(undefined),
    updateStep: jest.fn().mockResolvedValue(undefined),
    skipOnboarding: jest.fn().mockResolvedValue(undefined),
    completeOnboarding: jest.fn().mockResolvedValue(undefined),
    refreshOnboarding: jest.fn().mockResolvedValue(undefined),
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockedUseOnboardingContext.mockReturnValue(mockHandlers);
  });

  // ==================== STEP RENDERING ====================

  it('should render Step1_Website when currentStep is 1', () => {
    render(<OnboardingWizard />);

    expect(screen.getByTestId('step-1')).toBeInTheDocument();
    expect(screen.queryByTestId('step-2')).not.toBeInTheDocument();
  });

  it('should render Step2_Keyword when currentStep is 2', () => {
    mockedUseOnboardingContext.mockReturnValue({
      ...mockHandlers,
      onboarding: { ...defaultOnboarding, currentStep: 2 },
    });

    render(<OnboardingWizard />);

    expect(screen.getByTestId('step-2')).toBeInTheDocument();
    expect(screen.queryByTestId('step-1')).not.toBeInTheDocument();
  });

  it('should render Step3_Preview when currentStep is 3', () => {
    mockedUseOnboardingContext.mockReturnValue({
      ...mockHandlers,
      onboarding: { ...defaultOnboarding, currentStep: 3 },
    });

    render(<OnboardingWizard />);

    expect(screen.getByTestId('step-3')).toBeInTheDocument();
  });

  it('should render Step4_Alerts when currentStep is 4', () => {
    mockedUseOnboardingContext.mockReturnValue({
      ...mockHandlers,
      onboarding: { ...defaultOnboarding, currentStep: 4 },
    });

    render(<OnboardingWizard />);

    expect(screen.getByTestId('step-4')).toBeInTheDocument();
  });

  it('should render Step5_Complete when currentStep is 5', () => {
    mockedUseOnboardingContext.mockReturnValue({
      ...mockHandlers,
      onboarding: { ...defaultOnboarding, currentStep: 5 },
    });

    render(<OnboardingWizard />);

    expect(screen.getByTestId('step-5')).toBeInTheDocument();
  });

  // ==================== NAVIGATION BUTTONS ====================

  it('should have Back button that is disabled on step 1', () => {
    render(<OnboardingWizard />);

    const backButton = screen.getByRole('button', { name: /back/i });
    expect(backButton).toBeDisabled();
  });

  it('should enable Back button on step 2+', () => {
    mockedUseOnboardingContext.mockReturnValue({
      ...mockHandlers,
      onboarding: { ...defaultOnboarding, currentStep: 2 },
    });

    render(<OnboardingWizard />);

    const backButton = screen.getByRole('button', { name: /back/i });
    expect(backButton).not.toBeDisabled();
  });

  // ==================== SKIP FUNCTIONALITY ====================

  it('should have skip button', () => {
    render(<OnboardingWizard />);

    const skipButton = screen.getByRole('button', { name: /skip/i });
    expect(skipButton).toBeInTheDocument();
  });

  it('should call skipOnboarding when skip is clicked', async () => {
    render(<OnboardingWizard />);

    const skipButton = screen.getByRole('button', { name: /skip/i });
    fireEvent.click(skipButton);

    await waitFor(() => {
      expect(mockHandlers.skipOnboarding).toHaveBeenCalled();
    });
  });

  // ==================== LOADING STATE ====================

  it('should disable all buttons when loading', () => {
    mockedUseOnboardingContext.mockReturnValue({
      ...mockHandlers,
      isLoading: true,
    });

    render(<OnboardingWizard />);

    const buttons = screen.getAllByRole('button');
    buttons.forEach(button => {
      expect(button).toBeDisabled();
    });
  });

  it('should show loading spinner when loading', () => {
    mockedUseOnboardingContext.mockReturnValue({
      ...mockHandlers,
      isLoading: true,
    });

    render(<OnboardingWizard />);

    expect(screen.getByTestId('loading-spinner')).toBeInTheDocument();
  });

  // ==================== ERROR DISPLAY ====================

  it('should display error message when present', () => {
    mockedUseOnboardingContext.mockReturnValue({
      ...mockHandlers,
      error: 'Validation failed',
    });

    render(<OnboardingWizard />);

    expect(screen.getByText('Validation failed')).toBeInTheDocument();
  });

  it('should have error message with alert role', () => {
    mockedUseOnboardingContext.mockReturnValue({
      ...mockHandlers,
      error: 'Error occurred',
    });

    render(<OnboardingWizard />);

    const errorMessage = screen.getByRole('alert');
    expect(errorMessage).toHaveTextContent('Error occurred');
  });

  // ==================== PROGRESS INDICATOR ====================

  it('should show step count', () => {
    render(<OnboardingWizard />);

    expect(screen.getByText(/step 1 of 5/i)).toBeInTheDocument();
  });

  it('should update step count based on currentStep', () => {
    mockedUseOnboardingContext.mockReturnValue({
      ...mockHandlers,
      onboarding: { ...defaultOnboarding, currentStep: 3 },
    });

    render(<OnboardingWizard />);

    expect(screen.getByText(/step 3 of 5/i)).toBeInTheDocument();
  });

  // ==================== TRANSITION ANIMATIONS ====================

  it('should have transition animation class', () => {
    const { container } = render(<OnboardingWizard />);

    const wizardContent = container.querySelector('.wizard-content');
    expect(wizardContent).toHaveClass('transition');
  });
});
