/**
 * MetaTagToolForm Component Tests
 * Task: TASK-16-1 - Meta Tag Analyzer Frontend
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock component - will be replaced with actual implementation
interface MetaTagToolFormProps {
  onAnalyze: (data: { url?: string; html?: string }) => void;
  isLoading?: boolean;
  initialUrl?: string;
  initialHtml?: string;
}

const MetaTagToolForm: React.FC<MetaTagToolFormProps> = ({
  onAnalyze,
  isLoading = false,
  initialUrl = '',
  initialHtml = '',
}) => {
  const [url, setUrl] = React.useState(initialUrl);
  const [html, setHtml] = React.useState(initialHtml);
  const [urlError, setUrlError] = React.useState<string | null>(null);
  const [activeInput, setActiveInput] = React.useState<'url' | 'html'>('url');

  const validateUrl = (value: string): string | null => {
    if (!value) return 'URL is required';
    try {
      new URL(value);
      if (value.length > 2083) return 'URL is too long (max 2083 characters)';
      return null;
    } catch {
      return 'Please enter a valid URL';
    }
  };

  const handleUrlChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setUrl(value);
    setHtml(''); // Clear HTML when URL is entered
    setActiveInput('url');
    if (value) {
      setUrlError(validateUrl(value));
    } else {
      setUrlError(null);
    }
  };

  const handleHtmlChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    const value = e.target.value;
    setHtml(value);
    setUrl(''); // Clear URL when HTML is entered
    setActiveInput('html');
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (activeInput === 'url') {
      const error = validateUrl(url);
      if (error) {
        setUrlError(error);
        return;
      }
      onAnalyze({ url });
    } else {
      onAnalyze({ html });
    }
  };

  return (
    <form onSubmit={handleSubmit} data-testid="meta-tag-form">
      <div className="input-group">
        <label htmlFor="url-input">Enter page URL</label>
        <input
          id="url-input"
          type="url"
          placeholder="Enter page URL"
          value={url}
          onChange={handleUrlChange}
          disabled={isLoading}
          aria-describedby={urlError ? 'url-error' : undefined}
          aria-invalid={!!urlError}
        />
        {urlError && (
          <span id="url-error" role="alert" className="error-message">
            {urlError}
          </span>
        )}
      </div>
      <div className="divider">Or</div>
      <div className="input-group">
        <label htmlFor="html-input">Paste HTML</label>
        <textarea
          id="html-input"
          placeholder="Or paste HTML here"
          value={html}
          onChange={handleHtmlChange}
          disabled={isLoading}
          rows={10}
        />
      </div>
      <button type="submit" disabled={isLoading} aria-busy={isLoading}>
        {isLoading ? 'Analyzing...' : 'Analyze'}
      </button>
    </form>
  );
};

describe('MetaTagToolForm', () => {
  const mockOnAnalyze = jest.fn();

  beforeEach(() => {
    mockOnAnalyze.mockClear();
  });

  // UT-001: Renders form with URL input
  it('should render form with URL input', () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    expect(urlInput).toBeInTheDocument();
    expect(urlInput).toHaveAttribute('type', 'url');
  });

  // UT-002: Renders form with HTML textarea
  it('should render form with HTML textarea', () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

    const htmlTextarea = screen.getByPlaceholderText('Or paste HTML here');
    expect(htmlTextarea).toBeInTheDocument();
    expect(htmlTextarea.tagName).toBe('TEXTAREA');
  });

  // UT-003: URL input validation - valid URL
  it('should not show error for valid URL', async () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    await userEvent.type(urlInput, 'https://example.com');

    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  });

  // UT-004: URL input validation - invalid URL
  it('should show error for invalid URL', async () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    await userEvent.type(urlInput, 'not-a-url');
    fireEvent.blur(urlInput);

    // Trigger validation by submitting
    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent('Please enter a valid URL');
    });
  });

  // UT-005: URL input validation - empty
  it('should show error for empty URL on submit', async () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent('URL is required');
    });
  });

  // UT-006: HTML textarea validation - valid HTML
  it('should accept valid HTML input', async () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

    const htmlTextarea = screen.getByPlaceholderText('Or paste HTML here');
    await userEvent.type(htmlTextarea, '<html><head><title>Test</title></head></html>');

    expect(htmlTextarea).toHaveValue('<html><head><title>Test</title></head></html>');
  });

  // UT-007: HTML textarea validation - empty
  it('should allow empty HTML (optional field)', () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

    const htmlTextarea = screen.getByPlaceholderText('Or paste HTML here');
    expect(htmlTextarea).toHaveValue('');
  });

  // UT-008: Only one input active at a time
  it('should clear URL when HTML is entered', async () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    const htmlTextarea = screen.getByPlaceholderText('Or paste HTML here');

    await userEvent.type(urlInput, 'https://example.com');
    expect(urlInput).toHaveValue('https://example.com');

    await userEvent.type(htmlTextarea, '<html></html>');
    expect(urlInput).toHaveValue('');
    expect(htmlTextarea).toHaveValue('<html></html>');
  });

  // UT-009: Submit button disabled on loading
  it('should disable submit button when loading', () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} isLoading={true} />);

    const submitButton = screen.getByRole('button', { name: /analyzing/i });
    expect(submitButton).toBeDisabled();
  });

  // UT-010: Submit button calls onAnalyze
  it('should call onAnalyze with URL when submitted', async () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    await userEvent.type(urlInput, 'https://example.com');

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(mockOnAnalyze).toHaveBeenCalledWith({ url: 'https://example.com' });
    });
  });

  // Additional: Initial values
  it('should populate form with initial URL', () => {
    render(
      <MetaTagToolForm
        onAnalyze={mockOnAnalyze}
        initialUrl="https://example.com/page"
      />
    );

    const urlInput = screen.getByPlaceholderText('Enter page URL');
    expect(urlInput).toHaveValue('https://example.com/page');
  });

  // Additional: Initial HTML
  it('should populate form with initial HTML', () => {
    render(
      <MetaTagToolForm
        onAnalyze={mockOnAnalyze}
        initialHtml="<html><body>Test</body></html>"
      />
    );

    const htmlTextarea = screen.getByPlaceholderText('Or paste HTML here');
    expect(htmlTextarea).toHaveValue('<html><body>Test</body></html>');
  });

  // Accessibility: Labels present
  it('should have accessible labels for inputs', () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

    expect(screen.getByLabelText('Enter page URL')).toBeInTheDocument();
    expect(screen.getByLabelText('Paste HTML')).toBeInTheDocument();
  });

  // Accessibility: Error associated with input
  it('should associate error message with URL input', async () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

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
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

    const longUrl = 'https://example.com/' + 'a'.repeat(2100);
    const urlInput = screen.getByPlaceholderText('Enter page URL');
    await userEvent.type(urlInput, longUrl);

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent('URL is too long');
    });
  });

  // Submit with HTML
  it('should call onAnalyze with HTML when submitted', async () => {
    render(<MetaTagToolForm onAnalyze={mockOnAnalyze} />);

    const htmlTextarea = screen.getByPlaceholderText('Or paste HTML here');
    await userEvent.type(htmlTextarea, '<html><head><title>Test</title></head></html>');

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(mockOnAnalyze).toHaveBeenCalledWith({
        html: '<html><head><title>Test</title></head></html>',
      });
    });
  });
});
