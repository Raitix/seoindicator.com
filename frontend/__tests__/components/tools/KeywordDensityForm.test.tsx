/**
 * KeywordDensityForm Component Tests
 * Task: TASK-16-2 - Keyword Density Checker Frontend
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { KeywordDensityForm } from '@/components/tools/KeywordDensityForm';

describe('KeywordDensityForm', () => {
  const mockOnAnalyze = jest.fn();

  beforeEach(() => {
    mockOnAnalyze.mockClear();
  });

  // UT-001: Renders form with URL input
  it('should render form with URL input', () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    expect(urlInput).toBeInTheDocument();
    expect(urlInput).toHaveAttribute('type', 'url');
  });

  // UT-002: Renders form with HTML textarea
  it('should render form with HTML textarea', () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const htmlTextarea = screen.getByPlaceholderText('Or paste HTML here');
    expect(htmlTextarea).toBeInTheDocument();
    expect(htmlTextarea.tagName).toBe('TEXTAREA');
  });

  // UT-003: Renders keyword input field
  it('should render keyword input field', () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const keywordInput = screen.getByPlaceholderText('Enter target keyword');
    expect(keywordInput).toBeInTheDocument();
    expect(keywordInput).toHaveAttribute('type', 'text');
  });

  // UT-004: URL input validation - valid URL
  it('should not show error for valid URL', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    await userEvent.type(urlInput, 'https://example.com');

    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  });

  // UT-005: URL input validation - invalid URL
  it('should show error for invalid URL', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    const keywordInput = screen.getByPlaceholderText('Enter target keyword');
    
    await userEvent.type(urlInput, 'not-a-url');
    await userEvent.type(keywordInput, 'test');

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent('Please enter a valid URL');
    });
  });

  // UT-006: URL input validation - empty
  it('should show error for empty URL on submit', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const keywordInput = screen.getByPlaceholderText('Enter target keyword');
    await userEvent.type(keywordInput, 'test');

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent('URL is required');
    });
  });

  // UT-007: HTML textarea accepts valid HTML
  it('should accept valid HTML input', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const htmlTextarea = screen.getByPlaceholderText('Or paste HTML here');
    await userEvent.type(htmlTextarea, '<html><body>Test content</body></html>');

    expect(htmlTextarea).toHaveValue('<html><body>Test content</body></html>');
  });

  // UT-008: HTML textarea empty allowed
  it('should allow empty HTML (optional field)', () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const htmlTextarea = screen.getByPlaceholderText('Or paste HTML here');
    expect(htmlTextarea).toHaveValue('');
  });

  // UT-009: Only one input active at a time
  it('should clear URL when HTML is entered', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    const htmlTextarea = screen.getByPlaceholderText('Or paste HTML here');

    await userEvent.type(urlInput, 'https://example.com');
    expect(urlInput).toHaveValue('https://example.com');

    await userEvent.type(htmlTextarea, '<html></html>');
    expect(urlInput).toHaveValue('');
    expect(htmlTextarea).toHaveValue('<html></html>');
  });

  // UT-010: Keyword input validation - empty
  it('should show error for empty keyword on submit', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent('Keyword is required');
    });
  });

  // UT-011: Keyword input validation - special chars
  it('should accept keywords with special characters', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const keywordInput = screen.getByPlaceholderText('Enter target keyword');
    await userEvent.type(keywordInput, 'test@keyword!');

    expect(keywordInput).toHaveValue('test@keyword!');
  });

  // UT-012: Keyword input validation - max length
  it('should show warning for keyword longer than 100 characters', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const longKeyword = 'a'.repeat(101);
    const keywordInput = screen.getByPlaceholderText('Enter target keyword');
    await userEvent.type(keywordInput, longKeyword);

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent('Keyword is too long');
    });
  });

  // UT-013: Submit button disabled on loading
  it('should disable submit button when loading', () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} isLoading={true} />);

    const submitButton = screen.getByRole('button', { name: /analyzing/i });
    expect(submitButton).toBeDisabled();
  });

  // UT-014: Submit calls onAnalyze with URL
  it('should call onAnalyze with URL and keyword when submitted', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    const keywordInput = screen.getByPlaceholderText('Enter target keyword');

    await userEvent.type(urlInput, 'https://example.com');
    await userEvent.type(keywordInput, 'SEO');

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(mockOnAnalyze).toHaveBeenCalledWith({ url: 'https://example.com', keyword: 'SEO' });
    });
  });

  // UT-014b: Submit calls onAnalyze with HTML
  it('should call onAnalyze with HTML and keyword when submitted', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const htmlTextarea = screen.getByPlaceholderText('Or paste HTML here');
    const keywordInput = screen.getByPlaceholderText('Enter target keyword');

    await userEvent.type(htmlTextarea, '<html><body>SEO content</body></html>');
    await userEvent.type(keywordInput, 'SEO');

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(mockOnAnalyze).toHaveBeenCalledWith({ 
        html: '<html><body>SEO content</body></html>', 
        keyword: 'SEO' 
      });
    });
  });

  // UT-015: Form clears on reset
  it('should clear all fields on reset', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    const keywordInput = screen.getByPlaceholderText('Enter target keyword');

    await userEvent.type(urlInput, 'https://example.com');
    await userEvent.type(keywordInput, 'SEO');

    const resetButton = screen.getByRole('button', { name: /reset/i });
    fireEvent.click(resetButton);

    expect(urlInput).toHaveValue('');
    expect(keywordInput).toHaveValue('');
  });

  // UT-016: Initial values populated
  it('should populate form with initial values', () => {
    render(
      <KeywordDensityForm
        onAnalyze={mockOnAnalyze}
        initialUrl="https://example.com/page"
        initialKeyword="SEO tools"
      />
    );

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    const keywordInput = screen.getByPlaceholderText('Enter target keyword');

    expect(urlInput).toHaveValue('https://example.com/page');
    expect(keywordInput).toHaveValue('SEO tools');
  });

  // Accessibility: Labels present
  it('should have accessible labels for all inputs', () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    expect(screen.getByLabelText('Enter page URL')).toBeInTheDocument();
    expect(screen.getByLabelText('Paste HTML')).toBeInTheDocument();
    expect(screen.getByLabelText('Target Keyword')).toBeInTheDocument();
  });

  // Accessibility: Error associated with inputs
  it('should associate error message with URL input', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const keywordInput = screen.getByPlaceholderText('Enter target keyword');
    await userEvent.type(keywordInput, 'test');

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      const urlInput = screen.getByPlaceholderText('Enter page URL');
      const errorMessage = screen.getByRole('alert');

      expect(urlInput).toHaveAttribute('aria-describedby', 'url-error');
      expect(errorMessage).toHaveAttribute('id', 'url-error');
    });
  });

  // URL max length validation
  it('should reject URLs longer than 2083 characters', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const longUrl = 'https://example.com/' + 'a'.repeat(2100);
    const urlInput = screen.getByPlaceholderText('Enter page URL');
    const keywordInput = screen.getByPlaceholderText('Enter target keyword');

    await userEvent.type(urlInput, longUrl);
    await userEvent.type(keywordInput, 'test');

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent('URL is too long');
    });
  });

  // HTML clears URL
  it('should clear URL when HTML is entered', async () => {
    render(<KeywordDensityForm onAnalyze={mockOnAnalyze} />);

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    const htmlTextarea = screen.getByPlaceholderText('Or paste HTML here');

    await userEvent.type(urlInput, 'https://example.com');
    expect(urlInput).toHaveValue('https://example.com');

    await userEvent.clear(urlInput);
    await userEvent.type(htmlTextarea, '<html></html>');
    expect(urlInput).toHaveValue('');
    expect(htmlTextarea).toHaveValue('<html></html>');
  });
});
