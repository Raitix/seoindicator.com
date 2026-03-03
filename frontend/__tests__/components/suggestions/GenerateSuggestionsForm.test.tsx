/**
 * GenerateSuggestionsForm Component Tests
 * Task: TASK-5-11
 * Specification: SPEC-005
 *
 * Tests for the form component that generates new keyword suggestions.
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock the generateSuggestions API function
const mockGenerateSuggestions = jest.fn();
jest.mock('@/lib/api/suggestions', () => ({
  generateSuggestions: (...args: any[]) => mockGenerateSuggestions(...args),
}));

// Mock Radix-based UI components that cause act issues
jest.mock('@/components/ui/checkbox', () => ({
  Checkbox: React.forwardRef<HTMLInputElement, { checked?: boolean; onCheckedChange?: (checked: boolean) => void; disabled?: boolean; 'data-testid'?: string; id?: string }>(
    ({ checked, onCheckedChange, disabled, 'data-testid': testId, id }, ref) => (
      <input
        ref={ref}
        type="checkbox"
        data-testid={testId}
        id={id}
        checked={checked}
        onChange={(e) => onCheckedChange?.(e.target.checked)}
        disabled={disabled}
      />
    )
  ),
}));

jest.mock('@/components/ui/label', () => ({
  Label: React.forwardRef<HTMLLabelElement, { children: React.ReactNode; htmlFor?: string; className?: string }>(
    ({ children, htmlFor, className }, ref) => (
      <label ref={ref} htmlFor={htmlFor} className={className}>
        {children}
      </label>
    )
  ),
}));

// Mock fetch for any direct API calls
global.fetch = jest.fn();

// Import the real component after mocks are set up
import GenerateSuggestionsForm from '@/components/suggestions/GenerateSuggestionsForm';

describe('GenerateSuggestionsForm', () => {
  const mockSiteId = '1';
  const mockOnSuccess = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    mockGenerateSuggestions.mockReset();
    global.fetch = jest.fn();
  });

  // ============================================
  // Basic Rendering Tests
  // ============================================
  
  describe('Basic Rendering', () => {
    it('should render the seed keyword input', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByTestId('seed-keyword-input')).toBeInTheDocument();
    });

    it('should render the source checkboxes', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByTestId('source-suggest')).toBeInTheDocument();
      expect(screen.getByTestId('source-question')).toBeInTheDocument();
    });

    it('should render the max results slider', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByTestId('max-results-slider')).toBeInTheDocument();
    });

    it('should render the generate button', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByTestId('generate-button')).toBeInTheDocument();
    });

    it('should render the rate limit indicator', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByTestId('rate-limit')).toBeInTheDocument();
    });
  });

  // ============================================
  // Default Values Tests
  // ============================================
  
  describe('Default Values', () => {
    it('should have Google Suggest checked by default', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByTestId('source-suggest')).toBeChecked();
    });

    it('should have Question Keywords checked by default', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByTestId('source-question')).toBeChecked();
    });

    it('should have maxResults set to 50 by default', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByTestId('max-results-slider')).toHaveValue('50');
      expect(screen.getByTestId('max-results-value')).toHaveTextContent('50');
    });
  });

  // ============================================
  // Seed Keyword Input Tests
  // ============================================
  
  describe('Seed Keyword Input', () => {
    it('should accept input', async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const input = screen.getByTestId('seed-keyword-input');
      await user.type(input, 'seo tools');
      
      expect(input).toHaveValue('seo tools');
    });

    it('should be required', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const input = screen.getByTestId('seed-keyword-input');
      expect(input).toHaveAttribute('aria-required', 'true');
    });

    it('should clear after successful submission', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockResolvedValueOnce({ jobId: '123', status: 'queued', count: 10 });
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} onSuccess={mockOnSuccess} />);
      
      const input = screen.getByTestId('seed-keyword-input');
      await user.type(input, 'seo tools');
      
      const button = screen.getByTestId('generate-button');
      await user.click(button);
      
      await waitFor(() => {
        expect(input).toHaveValue('');
      });
    });
  });

  // ============================================
  // Source Checkboxes Tests
  // ============================================
  
  describe('Source Checkboxes', () => {
    it('should toggle Google Suggest', async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const checkbox = screen.getByTestId('source-suggest');
      expect(checkbox).toBeChecked();
      
      await user.click(checkbox);
      expect(checkbox).not.toBeChecked();
    });

    it('should toggle Question Keywords', async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const checkbox = screen.getByTestId('source-question');
      expect(checkbox).toBeChecked();
      
      await user.click(checkbox);
      expect(checkbox).not.toBeChecked();
    });

    it('should disable submit when no sources selected', async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      // Uncheck both default sources
      await user.click(screen.getByTestId('source-suggest'));
      await user.click(screen.getByTestId('source-question'));
      
      // Button should be disabled
      const button = screen.getByTestId('generate-button');
      expect(button).toBeDisabled();
    });

    it('should have related searches disabled (coming soon)', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      expect(screen.getByTestId('source-related')).toBeDisabled();
    });
  });

  // ============================================
  // Max Results Slider Tests
  // ============================================
  
  describe('Max Results Slider', () => {
    it('should have minimum value of 10', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const slider = screen.getByTestId('max-results-slider');
      expect(slider).toHaveAttribute('min', '10');
    });

    it('should have maximum value of 100', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const slider = screen.getByTestId('max-results-slider');
      expect(slider).toHaveAttribute('max', '100');
    });

    it('should update displayed value when slider changes', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const slider = screen.getByTestId('max-results-slider');
      fireEvent.change(slider, { target: { value: '75' } });
      
      expect(screen.getByTestId('max-results-value')).toHaveTextContent('75');
    });
  });

  // ============================================
  // Form Submission Tests
  // ============================================
  
  describe('Form Submission', () => {
    it('should call API with form data', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockResolvedValueOnce({ jobId: '123', status: 'queued', count: 10 });
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByTestId('seed-keyword-input'), 'seo tools');
      await user.click(screen.getByTestId('generate-button'));
      
      await waitFor(() => {
        expect(mockGenerateSuggestions).toHaveBeenCalledWith(
          1,
          expect.objectContaining({
            seedKeyword: 'seo tools',
            sources: expect.arrayContaining(['suggest', 'question']),
            maxResults: 50,
          })
        );
      });
    });

    it('should validate seed keyword is not empty', async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      // Button should be disabled when keyword is empty
      const button = screen.getByTestId('generate-button');
      expect(button).toBeDisabled();
      
      // Enter only whitespace
      const input = screen.getByTestId('seed-keyword-input');
      await user.type(input, '   ');
      
      // Button should still be disabled (trimmed value is empty)
      expect(button).toBeDisabled();
    });

    it('should send selected sources only', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockResolvedValueOnce({ jobId: '123', status: 'queued', count: 10 });
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByTestId('seed-keyword-input'), 'seo');
      await user.click(screen.getByTestId('source-question')); // Uncheck question
      
      await user.click(screen.getByTestId('generate-button'));
      
      await waitFor(() => {
        expect(mockGenerateSuggestions).toHaveBeenCalledWith(
          1,
          expect.objectContaining({
            sources: ['suggest'], // Only suggest should be sent
          })
        );
      });
    });
  });

  // ============================================
  // Loading State Tests
  // ============================================
  
  describe('Loading State', () => {
    it('should show loading text during generation', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockImplementation(() => new Promise(resolve => setTimeout(() => resolve({ jobId: '123', status: 'queued', count: 10 }), 100)));
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByTestId('seed-keyword-input'), 'seo tools');
      await user.click(screen.getByTestId('generate-button'));
      
      // Should show loading indicator
      expect(screen.getByTestId('loading-indicator')).toBeInTheDocument();
      expect(screen.getByTestId('loading-indicator')).toHaveTextContent('Generating suggestions...');
    });

    it('should disable button during loading', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockImplementation(() => new Promise(resolve => setTimeout(() => resolve({ jobId: '123', status: 'queued', count: 10 }), 100)));
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByTestId('seed-keyword-input'), 'seo tools');
      await user.click(screen.getByTestId('generate-button'));
      
      const button = screen.getByTestId('generate-button');
      expect(button).toBeDisabled();
    });
  });

  // ============================================
  // Success Message Tests
  // ============================================
  
  describe('Success Message', () => {
    it('should display success message after generation', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockResolvedValueOnce({ jobId: '123', status: 'queued', count: 10 });
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} onSuccess={mockOnSuccess} />);
      
      await user.type(screen.getByTestId('seed-keyword-input'), 'seo tools');
      await user.click(screen.getByTestId('generate-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('success-message')).toBeInTheDocument();
      });
    });

    it('should show count of new suggestions', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockResolvedValueOnce({ jobId: '123', status: 'queued', count: 25 });
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} onSuccess={mockOnSuccess} />);
      
      await user.type(screen.getByTestId('seed-keyword-input'), 'seo tools');
      await user.click(screen.getByTestId('generate-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('success-count')).toHaveTextContent('25');
      });
    });

    it('should call onSuccess callback with count', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockResolvedValueOnce({ jobId: '123', status: 'queued', count: 15 });
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} onSuccess={mockOnSuccess} />);
      
      await user.type(screen.getByTestId('seed-keyword-input'), 'seo tools');
      await user.click(screen.getByTestId('generate-button'));
      
      await waitFor(() => {
        expect(mockOnSuccess).toHaveBeenCalledWith(15);
      });
    });
  });

  // ============================================
  // Error Handling Tests
  // ============================================
  
  describe('Error Handling', () => {
    it('should display API error message', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockRejectedValueOnce(new Error('Rate limit exceeded'));
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByTestId('seed-keyword-input'), 'seo tools');
      await user.click(screen.getByTestId('generate-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('error-message')).toBeInTheDocument();
        expect(screen.getByTestId('error-text')).toHaveTextContent('Rate limit exceeded');
      });
    });

    it('should handle network errors', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockRejectedValueOnce(new Error('Network error'));
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByTestId('seed-keyword-input'), 'seo tools');
      await user.click(screen.getByTestId('generate-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('error-message')).toBeInTheDocument();
        expect(screen.getByTestId('error-text')).toHaveTextContent('Network error');
      });
    });

    it('should have ARIA role for error messages', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockRejectedValueOnce(new Error('Test error'));
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByTestId('seed-keyword-input'), 'seo tools');
      await user.click(screen.getByTestId('generate-button'));
      
      await waitFor(() => {
        const errorDiv = screen.getByTestId('error-message');
        expect(errorDiv).toHaveAttribute('role', 'alert');
      });
    });
  });

  // ============================================
  // Rate Limit Tests
  // ============================================
  
  describe('Rate Limit', () => {
    it('should display remaining API calls', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const remaining = screen.getByTestId('rate-limit-remaining');
      expect(remaining).toHaveTextContent('10');
    });

    it('should update remaining after generation', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockResolvedValueOnce({ jobId: '123', status: 'queued', count: 10 });
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByTestId('seed-keyword-input'), 'seo tools');
      await user.click(screen.getByTestId('generate-button'));
      
      await waitFor(() => {
        const remaining = screen.getByTestId('rate-limit-remaining');
        expect(remaining).toHaveTextContent('9');
      });
    });
  });

  // ============================================
  // Adversarial Cases Tests
  // ============================================
  
  describe('Adversarial Cases', () => {
    it('should handle XSS in seed keyword', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockResolvedValueOnce({ jobId: '123', status: 'queued', count: 10 });
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const input = screen.getByTestId('seed-keyword-input');
      await user.type(input, '<script>alert("xss")</script>');
      
      // Input should accept the value (React escapes it)
      expect(input).toHaveValue('<script>alert("xss")</script>');
      
      await user.click(screen.getByTestId('generate-button'));
      
      await waitFor(() => {
        expect(mockGenerateSuggestions).toHaveBeenCalledWith(
          1,
          expect.objectContaining({
            seedKeyword: '<script>alert("xss")</script>',
          })
        );
      });
    });

    it('should handle very long keywords', () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const input = screen.getByTestId('seed-keyword-input');
      
      // Input should have maxLength attribute set to 255
      expect(input).toHaveAttribute('maxlength', '255');
      
      // Note: fireEvent.change bypasses maxLength browser validation
      // In real browser, user input would be truncated to 255 chars
    });

    it('should handle special characters', async () => {
      mockGenerateSuggestions.mockResolvedValueOnce({ jobId: '123', status: 'queued', count: 10 });
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const input = screen.getByTestId('seed-keyword-input');
      const specialKeyword = 'seo & tools $100 @gmail.com';
      
      // Use fireEvent to set value directly (handles special chars better)
      fireEvent.change(input, { target: { value: specialKeyword } });
      
      expect(input).toHaveValue(specialKeyword);
      
      // Enable the button by having a valid keyword
      const button = screen.getByTestId('generate-button');
      expect(button).not.toBeDisabled();
      
      // Submit the form directly
      const form = screen.getByTestId('generate-form');
      fireEvent.submit(form);
      
      await waitFor(() => {
        expect(mockGenerateSuggestions).toHaveBeenCalledWith(
          1,
          expect.objectContaining({
            seedKeyword: specialKeyword,
          })
        );
      });
    });

    it('should handle rapid button clicks', async () => {
      const user = userEvent.setup();
      mockGenerateSuggestions.mockImplementation(() => new Promise(resolve => setTimeout(() => resolve({ jobId: '123', status: 'queued', count: 10 }), 50)));
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByTestId('seed-keyword-input'), 'seo tools');
      
      // Rapidly click button multiple times
      const button = screen.getByTestId('generate-button');
      await user.click(button);
      
      // Button should now be disabled, preventing double submission
      expect(button).toBeDisabled();
      
      // Wait for the first call to complete
      await waitFor(() => {
        expect(mockGenerateSuggestions).toHaveBeenCalledTimes(1);
      });
    });
  });

  // ============================================
  // Integration Tests
  // ============================================
  
  describe('Integration', () => {
    it('should complete full generation flow', async () => {
      mockGenerateSuggestions.mockResolvedValueOnce({ jobId: 'job-123', status: 'queued', count: 42 });
      
      render(<GenerateSuggestionsForm siteId={mockSiteId} onSuccess={mockOnSuccess} />);
      
      // 1. Enter seed keyword
      const input = screen.getByTestId('seed-keyword-input');
      fireEvent.change(input, { target: { value: 'seo tools' } });
      expect(input).toHaveValue('seo tools');
      
      // 2. Toggle sources - uncheck question (keep suggest checked)
      const questionCheckbox = screen.getByTestId('source-question');
      fireEvent.click(questionCheckbox);
      expect(questionCheckbox).not.toBeChecked();
      
      // 3. Adjust max results
      const slider = screen.getByTestId('max-results-slider');
      fireEvent.change(slider, { target: { value: '75' } });
      expect(screen.getByTestId('max-results-value')).toHaveTextContent('75');
      
      // 4. Submit form
      const form = screen.getByTestId('generate-form');
      fireEvent.submit(form);
      
      // 5. Verify API call
      await waitFor(() => {
        expect(mockGenerateSuggestions).toHaveBeenCalledWith(
          1,
          expect.objectContaining({
            seedKeyword: 'seo tools',
            sources: ['suggest'],
            maxResults: 75,
          })
        );
      });
      
      // 6. Verify success state
      await waitFor(() => {
        expect(screen.getByTestId('success-message')).toBeInTheDocument();
        expect(screen.getByTestId('success-count')).toHaveTextContent('42');
      });
      
      // 7. Verify callback
      expect(mockOnSuccess).toHaveBeenCalledWith(42);
      
      // 8. Verify form reset
      expect(screen.getByTestId('seed-keyword-input')).toHaveValue('');
    });
  });
});
