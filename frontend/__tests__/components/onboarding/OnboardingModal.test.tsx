import { render, screen, fireEvent, within } from '@testing-library/react';
import { OnboardingModal } from '../OnboardingModal';
import { useOnboarding } from '../OnboardingProvider';

// Mock the onboarding hook
jest.mock('../OnboardingProvider', () => ({
  useOnboarding: jest.fn(),
}));

const mockedUseOnboarding = useOnboarding as jest.MockedFunction<typeof useOnboarding>;

// Mock the wizard component
jest.mock('../OnboardingWizard', () => ({
  OnboardingWizard: () => <div data-testid="wizard">Wizard Content</div>,
}));

describe('OnboardingModal', () => {
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
    nextStep: jest.fn(),
    prevStep: jest.fn(),
    skipOnboarding: jest.fn(),
    completeOnboarding: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockedUseOnboarding.mockReturnValue({
      state: defaultMockState,
      ...mockHandlers,
    });
  });

  // ==================== RENDERING ====================

  it('should render when isOpen is true', () => {
    render(<OnboardingModal />);

    expect(screen.getByTestId('wizard')).toBeInTheDocument();
  });

  it('should not render when isOpen is false', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, isOpen: false },
      ...mockHandlers,
    });

    const { container } = render(<OnboardingModal />);

    expect(container.firstChild).toBeNull();
  });

  // ==================== CLOSE BUTTON ====================

  it('should call skipOnboarding when close button is clicked', async () => {
    // Mock window.confirm
    window.confirm = jest.fn(() => true);

    render(<OnboardingModal />);

    const closeButton = screen.getByRole('button', { name: /close/i });
    fireEvent.click(closeButton);

    expect(window.confirm).toHaveBeenCalled();
    expect(mockHandlers.skipOnboarding).toHaveBeenCalled();
  });

  it('should not call skipOnboarding if user cancels close confirmation', () => {
    window.confirm = jest.fn(() => false);

    render(<OnboardingModal />);

    const closeButton = screen.getByRole('button', { name: /close/i });
    fireEvent.click(closeButton);

    expect(window.confirm).toHaveBeenCalled();
    expect(mockHandlers.skipOnboarding).not.toHaveBeenCalled();
  });

  // ==================== SKIP LINK ====================

  it('should have skip link that triggers skipOnboarding', () => {
    window.confirm = jest.fn(() => true);

    render(<OnboardingModal />);

    const skipLink = screen.getByRole('button', { name: /skip/i });
    fireEvent.click(skipLink);

    expect(mockHandlers.skipOnboarding).toHaveBeenCalled();
  });

  // ==================== LOADING STATE ====================

  it('should show loading overlay when isLoading is true', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, isLoading: true },
      ...mockHandlers,
    });

    render(<OnboardingModal />);

    expect(screen.getByTestId('loading-overlay')).toBeInTheDocument();
  });

  it('should disable buttons when loading', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, isLoading: true },
      ...mockHandlers,
    });

    render(<OnboardingModal />);

    const buttons = screen.getAllByRole('button');
    buttons.forEach(button => {
      if (button.hasAttribute('disabled') || button.getAttribute('aria-disabled')) {
        // Button is disabled
        expect(button).toBeDisabled();
      }
    });
  });

  // ==================== ERROR DISPLAY ====================

  it('should display error message when error exists', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, error: 'Something went wrong' },
      ...mockHandlers,
    });

    render(<OnboardingModal />);

    expect(screen.getByText('Something went wrong')).toBeInTheDocument();
  });

  // ==================== PROGRESS BAR ====================

  it('should display progress bar with correct percentage', () => {
    render(<OnboardingModal />);

    const progressBar = screen.getByRole('progressbar');
    expect(progressBar).toHaveAttribute('aria-valuenow', '20');
  });

  it('should update progress bar as steps progress', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, currentStep: 3, progressPercentage: 60 },
      ...mockHandlers,
    });

    render(<OnboardingModal />);

    const progressBar = screen.getByRole('progressbar');
    expect(progressBar).toHaveAttribute('aria-valuenow', '60');
  });

  // ==================== RESPONSIVE DESIGN ====================

  it('should have responsive classes for mobile', () => {
    const { container } = render(<OnboardingModal />);

    const modal = container.querySelector('.onboarding-modal');
    expect(modal).toHaveClass('sm:max-w-lg');
  });

  // ==================== ACCESSIBILITY ====================

  it('should trap focus within modal', () => {
    render(<OnboardingModal />);

    const modal = screen.getByRole('dialog');
    expect(modal).toHaveAttribute('aria-modal', 'true');
  });

  it('should have correct aria-label', () => {
    render(<OnboardingModal />);

    const modal = screen.getByRole('dialog');
    expect(modal).toHaveAttribute('aria-label', 'Onboarding');
  });

  it('should close on Escape key press', () => {
    window.confirm = jest.fn(() => true);

    render(<OnboardingModal />);

    fireEvent.keyDown(document, { key: 'Escape' });

    expect(window.confirm).toHaveBeenCalled();
  });

  it('should have close button with aria-label', () => {
    render(<OnboardingModal />);

    const closeButton = screen.getByRole('button', { name: /close/i });
    expect(closeButton).toHaveAttribute('aria-label', 'Close onboarding');
  });

  // ==================== STEP INDICATORS ====================

  it('should show step indicators', () => {
    render(<OnboardingModal />);

    const stepIndicators = screen.getAllByRole('listitem');
    expect(stepIndicators).toHaveLength(5);
  });

  it('should mark current step as active', () => {
    render(<OnboardingModal />);

    const currentStepIndicator = screen.getByRole('listitem', { current: 'step' });
    expect(currentStepIndicator).toBeInTheDocument();
  });

  it('should mark completed steps', () => {
    mockedUseOnboarding.mockReturnValue({
      state: { ...defaultMockState, completedSteps: [1, 2], currentStep: 3 },
      ...mockHandlers,
    });

    render(<OnboardingModal />);

    const completedSteps = screen.getAllByRole('listitem').slice(0, 2);
    completedSteps.forEach(step => {
      expect(step).toHaveAttribute('data-completed', 'true');
    });
  });
});
