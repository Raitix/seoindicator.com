import { render, screen, fireEvent } from '@testing-library/react';
import { OnboardingWizard } from '../OnboardingWizard';
import { useOnboarding } from '../OnboardingProvider';

// Mock the onboarding hook
jest.mock('../OnboardingProvider', () => ({
  useOnboarding: jest.fn(),
}));

// Mock step components
jest.mock('../steps/Step1_Website', () => ({
  Step1_Website: () => <div data-testid="step-1">Step 1: Website</div>,
}));

jest.mock('../steps/Step2_Keyword', () => ({
  Step2_Keyword: () => <div data-testid="step-2">Step 2: Keyword</div>,
}));

jest.mock('../steps/Step3_Preview', () => ({
  Step3_Preview: () => <div data-testid="step-3">Step 3: Preview</div>,
}));

jest.mock('../steps/Step4_Alerts', () => ({
  Step4_Alerts: () => <div data-testid="step-4">Step 4: Alerts</div>,
}));

jest.mock('../steps/Step5_Complete', () => ({
  Step5_Complete: () => <div data-testid="step-5">Step 5: Complete</div>,
}));

const mockedUseOnboarding = useOnboarding as jest.MockedFunction<typeof useOnboarding>;

describe('OnboardingWizard', () => {
  const defaultMockState = {
    isOpen: true,
    currentStep: 1,
    completedSteps: [],
    data: {
      websiteUrl: null,
      firstKeyword: null,
      keywordId: null,
      alertPreferences: null,
    },
    isLoading: false,
    error: null,
    progressPercentage: 20,
  };

  const mockHandlers = {
    startOnboarding: jest.fn(),
    nextStep: jest.fn().mockResolvedValue(undefined),
    prevStep: jest.fn(),
    skipOnboarding: jest.fn(),
    completeOnboarding: jest.fn().mockResolvedValue(undefined),
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockedUseOnboarding.mockReturnValue({
      state: defaultMockState,
      ...mockHandlers,
    });
  });

  // ==================== STEP RENDERING ====================

  it('should render Step1_Website when currentStep is 1', () => {
    render(<OnboardingWizard />);

    expect(screen.getByTestId('step-1')).toBeInTheDocument();
    expect(screen.queryByTestId('step-2')).not.toBeInTheDocument();
  });

  it('should render Step2_Keyword when currentStep is 2', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, currentStep: 2 },
      ...mockHandlers,
    });

    render(<OnboardingWizard />);

    expect(screen.getByTestId('step-2')).toBeInTheDocument();
    expect(screen.queryByTestId('step-1')).not.toBeInTheDocument();
  });

  it('should render Step3_Preview when currentStep is 3', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, currentStep: 3 },
      ...mockHandlers,
    });

    render(<OnboardingWizard />);

    expect(screen.getByTestId('step-3')).toBeInTheDocument();
  });

  it('should render Step4_Alerts when currentStep is 4', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, currentStep: 4 },
      ...mockHandlers,
    });

    render(<OnboardingWizard />);

    expect(screen.getByTestId('step-4')).toBeInTheDocument();
  });

  it('should render Step5_Complete when currentStep is 5', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, currentStep: 5 },
      ...mockHandlers,
    });

    render(<OnboardingWizard />);

    expect(screen.getByTestId('step-5')).toBeInTheDocument();
  });

  // ==================== NAVIGATION BUTTONS ====================

  it('should have Next button that calls nextStep', async () => {
    render(<OnboardingWizard />);

    const nextButton = screen.getByRole('button', { name: /next|continue/i });
    fireEvent.click(nextButton);

    expect(mockHandlers.nextStep).toHaveBeenCalled();
  });

  it('should have Back button that calls prevStep', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, currentStep: 2 },
      ...mockHandlers,
    });

    render(<OnboardingWizard />);

    const backButton = screen.getByRole('button', { name: /back|previous/i });
    fireEvent.click(backButton);

    expect(mockHandlers.prevStep).toHaveBeenCalled();
  });

  it('should disable Back button on step 1', () => {
    render(<OnboardingWizard />);

    const backButton = screen.getByRole('button', { name: /back|previous/i });
    expect(backButton).toBeDisabled();
  });

  it('should enable Back button on step 2+', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, currentStep: 2 },
      ...mockHandlers,
    });

    render(<OnboardingWizard />);

    const backButton = screen.getByRole('button', { name: /back|previous/i });
    expect(backButton).not.toBeDisabled();
  });

  it('should show "Complete" button on step 5', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, currentStep: 5 },
      ...mockHandlers,
    });

    render(<OnboardingWizard />);

    const completeButton = screen.getByRole('button', { name: /complete|finish/i });
    expect(completeButton).toBeInTheDocument();
  });

  it('should call completeOnboarding when Complete button is clicked', async () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, currentStep: 5 },
      ...mockHandlers,
    });

    render(<OnboardingWizard />);

    const completeButton = screen.getByRole('button', { name: /complete|finish/i });
    fireEvent.click(completeButton);

    expect(mockHandlers.completeOnboarding).toHaveBeenCalled();
  });

  // ==================== SKIP FUNCTIONALITY ====================

  it('should have skip link', () => {
    render(<OnboardingWizard />);

    const skipLink = screen.getByRole('button', { name: /skip/i });
    expect(skipLink).toBeInTheDocument();
  });

  it('should call skipOnboarding when skip is clicked', () => {
    render(<OnboardingWizard />);

    const skipLink = screen.getByRole('button', { name: /skip/i });
    fireEvent.click(skipLink);

    expect(mockHandlers.skipOnboarding).toHaveBeenCalled();
  });

  // ==================== LOADING STATE ====================

  it('should disable all buttons when loading', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, isLoading: true },
      ...mockHandlers,
    });

    render(<OnboardingWizard />);

    const buttons = screen.getAllByRole('button');
    buttons.forEach(button => {
      expect(button).toBeDisabled();
    });
  });

  it('should show loading spinner when loading', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, isLoading: true },
      ...mockHandlers,
    });

    render(<OnboardingWizard />);

    expect(screen.getByTestId('loading-spinner')).toBeInTheDocument();
  });

  // ==================== ERROR DISPLAY ====================

  it('should display error message when present', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, error: 'Validation failed' },
      ...mockHandlers,
    });

    render(<OnboardingWizard />);

    expect(screen.getByText('Validation failed')).toBeInTheDocument();
  });

  it('should have error message with alert role', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, error: 'Error occurred' },
      ...mockHandlers,
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
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, currentStep: 3 },
      ...mockHandlers,
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
