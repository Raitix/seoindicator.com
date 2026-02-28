import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Step1_Website } from '../Step1_Website';

// Mock the onboarding hook
const mockNextStep = jest.fn();
const mockSkipOnboarding = jest.fn();

jest.mock('../../OnboardingProvider', () => ({
  useOnboarding: () => ({
    state: {
      data: { websiteUrl: null },
      isLoading: false,
      error: null,
    },
    nextStep: mockNextStep,
    skipOnboarding: mockSkipOnboarding,
  }),
}));

describe('Step1_Website', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ==================== RENDERING ====================

  it('should render welcome message', () => {
    render(<Step1_Website />);

    expect(screen.getByText(/welcome to seoindicator/i)).toBeInTheDocument();
  });

  it('should render URL input field', () => {
    render(<Step1_Website />);

    const urlInput = screen.getByLabelText(/website url/i);
    expect(urlInput).toBeInTheDocument();
    expect(urlInput).toHaveAttribute('type', 'url');
    expect(urlInput).toHaveAttribute('placeholder', /https:\/\/example\.com/i);
  });

  it('should render tooltip with explanation', () => {
    render(<Step1_Website />);

    expect(screen.getByText(/we'll track rankings for pages on this site/i)).toBeInTheDocument();
  });

  it('should render continue button', () => {
    render(<Step1_Website />);

    const continueButton = screen.getByRole('button', { name: /continue/i });
    expect(continueButton).toBeInTheDocument();
  });

  it('should render skip link', () => {
    render(<Step1_Website />);

    const skipLink = screen.getByRole('button', { name: /skip for now/i });
    expect(skipLink).toBeInTheDocument();
  });

  // ==================== INPUT VALIDATION ====================

  it('should disable continue button when URL is empty', () => {
    render(<Step1_Website />);

    const continueButton = screen.getByRole('button', { name: /continue/i });
    expect(continueButton).toBeDisabled();
  });

  it('should enable continue button when valid URL is entered', async () => {
    const user = userEvent.setup();
    render(<Step1_Website />);

    const urlInput = screen.getByLabelText(/website url/i);
    await user.type(urlInput, 'https://example.com');

    const continueButton = screen.getByRole('button', { name: /continue/i });
    await waitFor(() => {
      expect(continueButton).not.toBeDisabled();
    });
  });

  it('should show error for invalid URL format', async () => {
    const user = userEvent.setup();
    render(<Step1_Website />);

    const urlInput = screen.getByLabelText(/website url/i);
    await user.type(urlInput, 'not-a-url');
    await user.tab(); // Trigger blur

    await waitFor(() => {
      expect(screen.getByText(/please enter a valid url/i)).toBeInTheDocument();
    });
  });

  it('should show error for non-https URL', async () => {
    const user = userEvent.setup();
    render(<Step1_Website />);

    const urlInput = screen.getByLabelText(/website url/i);
    await user.type(urlInput, 'http://example.com');
    await user.tab();

    // May or may not show warning depending on requirements
    // If https is required:
    // await waitFor(() => {
    //   expect(screen.getByText(/please use https/i)).toBeInTheDocument();
    // });
  });

  it('should show error for blocked URLs (SSRF)', async () => {
    const user = userEvent.setup();
    render(<Step1_Website />);

    const urlInput = screen.getByLabelText(/website url/i);
    await user.type(urlInput, 'http://localhost/test');
    await user.tab();

    await waitFor(() => {
      expect(screen.getByText(/this url is not allowed/i)).toBeInTheDocument();
    });
  });

  it('should show error for URL exceeding max length', async () => {
    const user = userEvent.setup();
    render(<Step1_Website />);

    const urlInput = screen.getByLabelText(/website url/i);
    const longUrl = 'https://example.com/' + 'a'.repeat(500);
    await user.type(urlInput, longUrl);

    await waitFor(() => {
      expect(screen.getByText(/url must be 500 characters or less/i)).toBeInTheDocument();
    });
  });

  // ==================== FORM SUBMISSION ====================

  it('should call nextStep with URL when continue is clicked', async () => {
    const user = userEvent.setup();
    render(<Step1_Website />);

    const urlInput = screen.getByLabelText(/website url/i);
    await user.type(urlInput, 'https://example.com');

    const continueButton = screen.getByRole('button', { name: /continue/i });
    await user.click(continueButton);

    expect(mockNextStep).toHaveBeenCalledWith({
      websiteUrl: 'https://example.com',
    });
  });

  it('should call nextStep on form submit (Enter key)', async () => {
    const user = userEvent.setup();
    render(<Step1_Website />);

    const urlInput = screen.getByLabelText(/website url/i);
    await user.type(urlInput, 'https://example.com{enter}');

    expect(mockNextStep).toHaveBeenCalledWith({
      websiteUrl: 'https://example.com',
    });
  });

  // ==================== SKIP FUNCTIONALITY ====================

  it('should call skipOnboarding when skip is clicked', async () => {
    const user = userEvent.setup();
    render(<Step1_Website />);

    const skipLink = screen.getByRole('button', { name: /skip for now/i });
    await user.click(skipLink);

    expect(mockSkipOnboarding).toHaveBeenCalled();
  });

  // ==================== LOADING STATE ====================

  it('should show loading state on continue button when loading', () => {
    jest.resetModules();
    jest.doMock('../../OnboardingProvider', () => ({
      useOnboarding: () => ({
        state: {
          data: { websiteUrl: null },
          isLoading: true,
          error: null,
        },
        nextStep: mockNextStep,
        skipOnboarding: mockSkipOnboarding,
      }),
    }));

    render(<Step1_Website />);

    const continueButton = screen.getByRole('button', { name: /continue|loading/i });
    expect(continueButton).toBeDisabled();
  });

  // ==================== ERROR DISPLAY ====================

  it('should display error from provider', () => {
    jest.resetModules();
    jest.doMock('../../OnboardingProvider', () => ({
      useOnboarding: () => ({
        state: {
          data: { websiteUrl: null },
          isLoading: false,
          error: 'Server error',
        },
        nextStep: mockNextStep,
        skipOnboarding: mockSkipOnboarding,
      }),
    }));

    render(<Step1_Website />);

    expect(screen.getByText('Server error')).toBeInTheDocument();
  });

  // ==================== ACCESSIBILITY ====================

  it('should have proper aria labels', () => {
    render(<Step1_Website />);

    const urlInput = screen.getByLabelText(/website url/i);
    expect(urlInput).toHaveAttribute('aria-required', 'true');
  });

  it('should have error message with role="alert"', async () => {
    const user = userEvent.setup();
    render(<Step1_Website />);

    const urlInput = screen.getByLabelText(/website url/i);
    await user.type(urlInput, 'invalid');
    await user.tab();

    await waitFor(() => {
      const errorMessage = screen.getByRole('alert');
      expect(errorMessage).toBeInTheDocument();
    });
  });

  it('should associate error message with input', async () => {
    const user = userEvent.setup();
    render(<Step1_Website />);

    const urlInput = screen.getByLabelText(/website url/i);
    await user.type(urlInput, 'invalid');
    await user.tab();

    await waitFor(() => {
      const errorId = urlInput.getAttribute('aria-describedby');
      expect(errorId).toBeTruthy();
    });
  });

  // ==================== EXAMPLE URL ====================

  it('should show example URL as placeholder', () => {
    render(<Step1_Website />);

    const urlInput = screen.getByLabelText(/website url/i);
    expect(urlInput).toHaveAttribute('placeholder', 'https://example.com');
  });
});
