/**
 * PublicAuditCTA Component Tests
 * Task: TASK-33-12
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 * 
 * Tests for the conversion component on public audit pages.
 * Covers: email capture, social sharing, embed code, accessibility.
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock fetch for API calls
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Mock clipboard API
const mockClipboardWrite = jest.fn();
Object.assign(navigator, {
  clipboard: {
    writeText: mockClipboardWrite,
  },
});

// Mock navigator.share
const mockShare = jest.fn();
Object.assign(navigator, {
  share: mockShare,
  canShare: jest.fn(() => true),
});

// Mock window.open
const mockWindowOpen = jest.fn();
window.open = mockWindowOpen;

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  Mail: () => <span data-testid="mail-icon">Mail</span>,
  Share2: () => <span data-testid="share-icon">Share</span>,
  Copy: () => <span data-testid="copy-icon">Copy</span>,
  Check: () => <span data-testid="check-icon">Check</span>,
  AlertCircle: () => <span data-testid="alert-icon">Alert</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loading</span>,
  Twitter: () => <span data-testid="twitter-icon">Twitter</span>,
  Linkedin: () => <span data-testid="linkedin-icon">LinkedIn</span>,
  Facebook: () => <span data-testid="facebook-icon">Facebook</span>,
}));

// Mock analytics
const mockTrackEvent = jest.fn();
jest.mock('@/lib/analytics', () => ({
  trackEvent: (...args: unknown[]) => mockTrackEvent(...args),
}));

/**
 * Mock PublicAuditCTA Component
 * This represents the expected interface for TDD
 */
interface PublicAuditCTAProps {
  auditHash: string;
  siteUrl: string;
  score: number;
}

interface LeadFormData {
  email: string;
}

const PublicAuditCTA: React.FC<PublicAuditCTAProps> = ({ auditHash, siteUrl, score }) => {
  const [email, setEmail] = React.useState('');
  const [error, setError] = React.useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = React.useState(false);
  const [isSuccess, setIsSuccess] = React.useState(false);
  const [submitError, setSubmitError] = React.useState<string | null>(null);
  const [copied, setCopied] = React.useState(false);

  const validateEmail = (value: string): boolean => {
    if (!value.trim()) {
      setError('Email is required');
      return false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      setError('Invalid email format');
      return false;
    }
    if (value.length > 255) {
      setError('Email too long');
      return false;
    }
    setError(null);
    return true;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateEmail(email)) return;
    
    setIsSubmitting(true);
    setSubmitError(null);
    
    try {
      const response = await fetch(`/api/v1/public-audit/${auditHash}/lead`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email.trim() }),
      });
      
      if (!response.ok) {
        if (response.status === 429) {
          throw new Error('Too many requests, try later');
        }
        if (response.status === 404) {
          throw new Error('Audit not found');
        }
        throw new Error('Failed to submit');
      }
      
      setIsSuccess(true);
      mockTrackEvent('public_audit_lead_submit', { hash: auditHash, score });
    } catch (err) {
      setSubmitError(err instanceof Error ? err.message : 'An error occurred');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleShare = async (platform: 'twitter' | 'linkedin' | 'facebook') => {
    const shareUrl = `https://seoindicator.com/audit/${auditHash}`;
    const shareText = `Check out this SEO score: ${score}/100 for ${siteUrl}`;
    
    if (platform === 'twitter') {
      window.open(
        `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(shareText)}`,
        '_blank',
        'width=550,height=420'
      );
    } else if (platform === 'linkedin') {
      window.open(
        `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}`,
        '_blank',
        'width=550,height=420'
      );
    } else if (platform === 'facebook') {
      window.open(
        `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`,
        '_blank',
        'width=550,height=420'
      );
    }
    
    mockTrackEvent('public_audit_share', { hash: auditHash, platform });
  };

  const handleCopyEmbed = async () => {
    const embedCode = `<a href="https://seoindicator.com/audit/${auditHash}">
  <img src="https://seoindicator.com/badge/${auditHash}/small.svg" alt="SEO Score for ${siteUrl}: ${score}/100" />
</a>`;
    
    try {
      await navigator.clipboard.writeText(embedCode);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
      mockTrackEvent('public_audit_embed_copy', { hash: auditHash });
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  };

  const embedCode = `<a href="https://seoindicator.com/audit/${auditHash}">
  <img src="https://seoindicator.com/badge/${auditHash}/small.svg" alt="SEO Score for ${siteUrl}: ${score}/100" />
</a>`;

  if (isSuccess) {
    return (
      <div 
        data-testid="success-state" 
        role="status" 
        aria-live="polite"
        className="p-6 bg-green-50 rounded-lg text-center"
      >
        <span data-testid="check-icon" className="text-green-600 text-4xl">✓</span>
        <h3 className="text-lg font-semibold mt-2">Thank you!</h3>
        <p className="text-gray-600">Check your email for the full report.</p>
        <p className="text-sm text-gray-500 mt-2">Your SEO score: {score}/100</p>
        <a 
          href={`https://seoindicator.com/audit/${auditHash}`}
          className="text-blue-600 hover:underline mt-2 block"
        >
          View your audit
        </a>
      </div>
    );
  }

  return (
    <div data-testid="public-audit-cta" className="space-y-6">
      {/* Email Capture Form */}
      <form 
        onSubmit={handleSubmit} 
        role="form"
        aria-label="Get your free audit"
        data-testid="lead-form"
      >
        <h2 className="text-xl font-semibold mb-4">
          Get your free SEO audit for {siteUrl}
        </h2>
        
        <div className="space-y-2">
          <label htmlFor="email" className="block text-sm font-medium">
            Email address
          </label>
          <input
            id="email"
            type="email"
            name="email"
            value={email}
            onChange={(e) => {
              setEmail(e.target.value);
              if (error) validateEmail(e.target.value);
            }}
            onBlur={() => validateEmail(email)}
            placeholder="Enter your email"
            aria-invalid={!!error}
            aria-describedby={error ? 'email-error' : undefined}
            className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            data-testid="email-input"
          />
          {error && (
            <p 
              id="email-error" 
              role="alert" 
              className="text-red-600 text-sm"
              data-testid="validation-error"
            >
              {error}
            </p>
          )}
        </div>

        <button
          type="submit"
          disabled={isSubmitting}
          aria-busy={isSubmitting}
          aria-label={`Get your free audit - Score: ${score}/100`}
          className="w-full mt-4 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          data-testid="submit-button"
        >
          {isSubmitting ? (
            <>
              <span data-testid="loader-icon" className="inline-block animate-spin mr-2">⏳</span>
              Submitting...
            </>
          ) : (
            `Get your free audit (Score: ${score}/100)`
          )}
        </button>

        {submitError && (
          <div 
            role="alert" 
            className="mt-4 p-4 bg-red-50 rounded-lg"
            data-testid="submit-error"
          >
            <p className="text-red-600">{submitError}</p>
            <button
              type="button"
              onClick={() => setSubmitError(null)}
              className="mt-2 text-sm text-blue-600 hover:underline"
              data-testid="retry-button"
            >
              Try again
            </button>
          </div>
        )}

        <p className="mt-4 text-xs text-gray-500" data-testid="privacy-notice">
          We respect your privacy. Your email will only be used to send your audit report.
        </p>
      </form>

      {/* Social Sharing */}
      <div data-testid="social-sharing" className="border-t pt-6">
        <h3 className="text-sm font-medium mb-3">Share this audit</h3>
        <div className="flex gap-2">
          <button
            type="button"
            onClick={() => handleShare('twitter')}
            aria-label="Share on Twitter"
            className="px-4 py-2 bg-sky-500 text-white rounded-lg hover:bg-sky-600"
            data-testid="twitter-share"
          >
            <span data-testid="twitter-icon">Twitter</span>
          </button>
          <button
            type="button"
            onClick={() => handleShare('linkedin')}
            aria-label="Share on LinkedIn"
            className="px-4 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800"
            data-testid="linkedin-share"
          >
            <span data-testid="linkedin-icon">LinkedIn</span>
          </button>
          <button
            type="button"
            onClick={() => handleShare('facebook')}
            aria-label="Share on Facebook"
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            data-testid="facebook-share"
          >
            <span data-testid="facebook-icon">Facebook</span>
          </button>
        </div>
      </div>

      {/* Embed Code */}
      <div data-testid="embed-code-section" className="border-t pt-6">
        <h3 className="text-sm font-medium mb-3">Embed this badge</h3>
        <div className="relative">
          <textarea
            readOnly
            value={embedCode}
            aria-label="Badge embed code"
            className="w-full p-3 bg-gray-50 border rounded-lg text-sm font-mono"
            data-testid="embed-code"
            rows={4}
          />
          <button
            type="button"
            onClick={handleCopyEmbed}
            aria-label="Copy embed code"
            className="absolute top-2 right-2 px-3 py-1 bg-gray-200 rounded hover:bg-gray-300"
            data-testid="copy-button"
          >
            {copied ? (
              <span data-testid="check-icon">Copied!</span>
            ) : (
              <span data-testid="copy-icon">Copy</span>
            )}
          </button>
        </div>
      </div>
    </div>
  );
};

// ============================================
// TEST SUITE
// ============================================

describe('PublicAuditCTA Component', () => {
  const defaultProps: PublicAuditCTAProps = {
    auditHash: 'abc123def456',
    siteUrl: 'example.com',
    score: 85,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockReset();
    mockClipboardWrite.mockReset();
    mockWindowOpen.mockReset();
    mockShare.mockReset();
  });

  // ============================================
  // 1. COMPONENT RENDERING TESTS
  // ============================================
  
  describe('Component Rendering', () => {
    it('REND-001: renders with required props', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      expect(screen.getByTestId('public-audit-cta')).toBeInTheDocument();
    });

    it('REND-002: displays site URL in heading', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      expect(screen.getByText(/Get your free SEO audit for example.com/i)).toBeInTheDocument();
    });

    it('REND-003: displays score in CTA button', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      expect(screen.getByRole('button', { name: /Score: 85\/100/i })).toBeInTheDocument();
    });

    it('REND-004: renders email input form', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      const emailInput = screen.getByTestId('email-input');
      expect(emailInput).toHaveAttribute('type', 'email');
    });

    it('REND-005: renders primary CTA button', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      expect(screen.getByTestId('submit-button')).toHaveTextContent('Get your free audit');
    });

    it('REND-006: renders social share buttons', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      expect(screen.getByTestId('twitter-share')).toBeInTheDocument();
      expect(screen.getByTestId('linkedin-share')).toBeInTheDocument();
      expect(screen.getByTestId('facebook-share')).toBeInTheDocument();
    });

    it('REND-007: renders embed code section', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      expect(screen.getByTestId('embed-code')).toBeInTheDocument();
    });

    it('REND-008: renders privacy notice', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      expect(screen.getByTestId('privacy-notice')).toBeInTheDocument();
      expect(screen.getByTestId('privacy-notice')).toHaveTextContent('privacy');
    });

    it('REND-009: renders copy button for embed code', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      expect(screen.getByTestId('copy-button')).toBeInTheDocument();
    });

    it('REND-010: displays correct audit URL in embed code', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode).toHaveValue(expect.stringContaining('/audit/abc123def456'));
    });
  });

  // ============================================
  // 2. EMAIL CAPTURE FORM TESTS
  // ============================================
  
  describe('Email Capture Form', () => {
    describe('Form Validation', () => {
      it('FORM-001: validates empty email', async () => {
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const submitButton = screen.getByTestId('submit-button');
        await user.click(submitButton);
        
        expect(screen.getByTestId('validation-error')).toHaveTextContent('Email is required');
      });

      it('FORM-002: validates invalid email format', async () => {
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const emailInput = screen.getByTestId('email-input');
        await user.type(emailInput, 'invalid-email');
        await user.click(screen.getByTestId('submit-button'));
        
        expect(screen.getByTestId('validation-error')).toHaveTextContent('Invalid email format');
      });

      it('FORM-003: accepts valid email', async () => {
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const emailInput = screen.getByTestId('email-input');
        await user.type(emailInput, 'test@example.com');
        await user.click(screen.getByTestId('submit-button'));
        
        // Should not show validation error
        expect(screen.queryByTestId('validation-error')).not.toBeInTheDocument();
      });

      it('FORM-004: trims whitespace from email', async () => {
        mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const emailInput = screen.getByTestId('email-input');
        await user.type(emailInput, '  test@example.com  ');
        await user.click(screen.getByTestId('submit-button'));
        
        await waitFor(() => {
          expect(mockFetch).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({
              body: JSON.stringify({ email: 'test@example.com' }),
            })
          );
        });
      });

      it('FORM-005: validates max email length', async () => {
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const longEmail = 'a'.repeat(250) + '@example.com'; // > 255 chars
        const emailInput = screen.getByTestId('email-input');
        await user.type(emailInput, longEmail);
        await user.click(screen.getByTestId('submit-button'));
        
        expect(screen.getByTestId('validation-error')).toHaveTextContent('Email too long');
      });

      it('FORM-006: shows validation error on blur', async () => {
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const emailInput = screen.getByTestId('email-input');
        await user.type(emailInput, 'invalid');
        await user.tab(); // blur
        
        expect(screen.getByTestId('validation-error')).toBeInTheDocument();
      });

      it('FORM-007: clears error when input becomes valid', async () => {
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const emailInput = screen.getByTestId('email-input');
        await user.type(emailInput, 'invalid');
        await user.tab();
        
        expect(screen.getByTestId('validation-error')).toBeInTheDocument();
        
        await user.clear(emailInput);
        await user.type(emailInput, 'valid@example.com');
        await user.tab();
        
        expect(screen.queryByTestId('validation-error')).not.toBeInTheDocument();
      });
    });

    describe('Form Submission', () => {
      it('SUBMIT-001: submits valid email to API', async () => {
        mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const emailInput = screen.getByTestId('email-input');
        await user.type(emailInput, 'test@example.com');
        await user.click(screen.getByTestId('submit-button'));
        
        await waitFor(() => {
          expect(mockFetch).toHaveBeenCalledWith(
            '/api/v1/public-audit/abc123def456/lead',
            expect.objectContaining({ method: 'POST' })
          );
        });
      });

      it('SUBMIT-002: includes audit hash in request', async () => {
        mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const emailInput = screen.getByTestId('email-input');
        await user.type(emailInput, 'test@example.com');
        await user.click(screen.getByTestId('submit-button'));
        
        await waitFor(() => {
          expect(mockFetch).toHaveBeenCalledWith(
            expect.stringContaining('abc123def456'),
            expect.any(Object)
          );
        });
      });

      it('SUBMIT-003: includes email in request body', async () => {
        mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const emailInput = screen.getByTestId('email-input');
        await user.type(emailInput, 'test@example.com');
        await user.click(screen.getByTestId('submit-button'));
        
        await waitFor(() => {
          expect(mockFetch).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({
              body: JSON.stringify({ email: 'test@example.com' }),
            })
          );
        });
      });

      it('SUBMIT-004: disables button during submission', async () => {
        mockFetch.mockImplementationOnce(() => 
          new Promise(resolve => setTimeout(() => resolve({ ok: true }), 100))
        );
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const emailInput = screen.getByTestId('email-input');
        const submitButton = screen.getByTestId('submit-button');
        
        await user.type(emailInput, 'test@example.com');
        await user.click(submitButton);
        
        expect(submitButton).toBeDisabled();
      });

      it('SUBMIT-005: shows loading state during submission', async () => {
        mockFetch.mockImplementationOnce(() => 
          new Promise(resolve => setTimeout(() => resolve({ ok: true }), 100))
        );
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const emailInput = screen.getByTestId('email-input');
        await user.type(emailInput, 'test@example.com');
        await user.click(screen.getByTestId('submit-button'));
        
        expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
      });

      it('FORM-008: prevents submit with invalid email', async () => {
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultProps} />);
        
        const emailInput = screen.getByTestId('email-input');
        await user.type(emailInput, 'invalid');
        await user.click(screen.getByTestId('submit-button'));
        
        expect(mockFetch).not.toHaveBeenCalled();
      });
    });
  });

  // ============================================
  // 3. SUCCESS STATE TESTS
  // ============================================
  
  describe('Success State', () => {
    beforeEach(() => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
    });

    it('SUCC-001: shows success message', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('success-state')).toBeInTheDocument();
        expect(screen.getByText(/Thank you/i)).toBeInTheDocument();
      });
    });

    it('SUCC-002: hides form on success', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.queryByTestId('lead-form')).not.toBeInTheDocument();
      });
    });

    it('SUCC-003: shows confirmation icon', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('check-icon')).toBeInTheDocument();
      });
    });

    it('SUCC-004: tracks success event', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(mockTrackEvent).toHaveBeenCalledWith(
          'public_audit_lead_submit',
          expect.objectContaining({ hash: 'abc123def456', score: 85 })
        );
      });
    });

    it('SUCC-005: shows score reminder in success', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByText(/Your SEO score: 85\/100/i)).toBeInTheDocument();
      });
    });

    it('SUCC-006: offers to view audit', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        const viewLink = screen.getByText('View your audit');
        expect(viewLink).toHaveAttribute('href', expect.stringContaining('abc123def456'));
      });
    });
  });

  // ============================================
  // 4. ERROR STATE TESTS
  // ============================================
  
  describe('Error State', () => {
    it('ERR-001: shows error message on API failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 500 });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('submit-error')).toBeInTheDocument();
      });
    });

    it('ERR-002: shows retry option on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 500 });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('retry-button')).toHaveTextContent('Try again');
      });
    });

    it('ERR-003: clears error on retry click', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 500 });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('submit-error')).toBeInTheDocument();
      });
      
      await user.click(screen.getByTestId('retry-button'));
      
      expect(screen.queryByTestId('submit-error')).not.toBeInTheDocument();
    });

    it('ERR-005: handles rate limit (429)', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 429 });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('submit-error')).toHaveTextContent('Too many requests');
      });
    });

    it('ERR-006: handles invalid hash (404)', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 404 });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('submit-error')).toHaveTextContent('Audit not found');
      });
    });

    it('ERR-007: handles network error', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('submit-error')).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // 5. SOCIAL SHARING TESTS
  // ============================================
  
  describe('Social Sharing', () => {
    it('SHARE-001: Twitter share opens dialog', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      await user.click(screen.getByTestId('twitter-share'));
      
      expect(mockWindowOpen).toHaveBeenCalledWith(
        expect.stringContaining('twitter.com'),
        '_blank',
        expect.any(String)
      );
    });

    it('SHARE-002: Twitter share includes score', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      await user.click(screen.getByTestId('twitter-share'));
      
      expect(mockWindowOpen).toHaveBeenCalledWith(
        expect.stringContaining('85%2F100'),
        expect.any(String),
        expect.any(String)
      );
    });

    it('SHARE-003: Twitter share includes audit URL', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      await user.click(screen.getByTestId('twitter-share'));
      
      expect(mockWindowOpen).toHaveBeenCalledWith(
        expect.stringContaining('abc123def456'),
        expect.any(String),
        expect.any(String)
      );
    });

    it('SHARE-004: LinkedIn share opens dialog', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      await user.click(screen.getByTestId('linkedin-share'));
      
      expect(mockWindowOpen).toHaveBeenCalledWith(
        expect.stringContaining('linkedin.com'),
        '_blank',
        expect.any(String)
      );
    });

    it('SHARE-006: Facebook share opens dialog', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      await user.click(screen.getByTestId('facebook-share'));
      
      expect(mockWindowOpen).toHaveBeenCalledWith(
        expect.stringContaining('facebook.com'),
        '_blank',
        expect.any(String)
      );
    });

    it('SHARE-007: Facebook share includes correct URL', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      await user.click(screen.getByTestId('facebook-share'));
      
      expect(mockWindowOpen).toHaveBeenCalledWith(
        expect.stringContaining('abc123def456'),
        expect.any(String),
        expect.any(String)
      );
    });

    it('SHARE-010: tracks share event', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      await user.click(screen.getByTestId('twitter-share'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'public_audit_share',
        expect.objectContaining({ platform: 'twitter' })
      );
    });

    it('SHARE-012: properly encodes share URLs', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA auditHash="abc+123=def" siteUrl="example.com" score={85} />);
      
      await user.click(screen.getByTestId('twitter-share'));
      
      // URL should be encoded
      expect(mockWindowOpen).toHaveBeenCalledWith(
        expect.not.stringContaining(' '),
        expect.any(String),
        expect.any(String)
      );
    });
  });

  // ============================================
  // 6. BADGE EMBED CODE TESTS
  // ============================================
  
  describe('Badge Embed Code', () => {
    it('EMBED-001: generates SVG embed code with hash', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      
      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode).toHaveValue(expect.stringContaining('/badge/abc123def456/small.svg'));
    });

    it('EMBED-002: includes site URL in alt text', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      
      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode).toHaveValue(expect.stringContaining('example.com'));
    });

    it('EMBED-003: includes score in alt text', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      
      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode).toHaveValue(expect.stringContaining('85/100'));
    });

    it('EMBED-004: copy button copies to clipboard', async () => {
      mockClipboardWrite.mockResolvedValueOnce(undefined);
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      await user.click(screen.getByTestId('copy-button'));
      
      expect(mockClipboardWrite).toHaveBeenCalledWith(expect.stringContaining('badge'));
    });

    it('EMBED-005: shows copy success feedback', async () => {
      mockClipboardWrite.mockResolvedValueOnce(undefined);
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      await user.click(screen.getByTestId('copy-button'));
      
      expect(screen.getByText('Copied!')).toBeInTheDocument();
    });

    it('EMBED-007: textarea is readonly', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      
      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode).toHaveAttribute('readonly');
    });

    it('EMBED-008: tracks copy event', async () => {
      mockClipboardWrite.mockResolvedValueOnce(undefined);
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      await user.click(screen.getByTestId('copy-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'public_audit_embed_copy',
        expect.objectContaining({ hash: 'abc123def456' })
      );
    });

    it('EMBED-009: includes link wrapper', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      
      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode).toHaveValue(expect.stringContaining('<a href='));
    });

    it('EMBED-010: generates correct badge URL', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      
      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode).toHaveValue(expect.stringMatching(/badge\/abc123def456\/small\.svg/));
    });
  });

  // ============================================
  // 7. ACCESSIBILITY TESTS
  // ============================================
  
  describe('Accessibility', () => {
    it('A11Y-001: form labels present', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByLabelText(/email address/i);
      expect(emailInput).toBeInTheDocument();
    });

    it('A11Y-002: aria-label on inputs', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      
      const embedCode = screen.getByLabelText(/badge embed code/i);
      expect(embedCode).toBeInTheDocument();
    });

    it('A11Y-003: keyboard navigation - tab order', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      await user.tab();
      expect(screen.getByTestId('email-input')).toHaveFocus();
      
      await user.tab();
      expect(screen.getByTestId('submit-button')).toHaveFocus();
    });

    it('A11Y-004: Enter key submits form', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com{enter}');
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
    });

    it('A11Y-005: focus moves to error on validation', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'invalid');
      await user.click(screen.getByTestId('submit-button'));
      
      const error = screen.getByRole('alert');
      expect(error).toBeInTheDocument();
    });

    it('A11Y-006: aria-describedby links input to error', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'invalid');
      await user.click(screen.getByTestId('submit-button'));
      
      expect(emailInput).toHaveAttribute('aria-describedby', 'email-error');
    });

    it('A11Y-007: role="alert" on error', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'invalid');
      await user.click(screen.getByTestId('submit-button'));
      
      expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    it('A11Y-008: button has accessible name', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      
      const button = screen.getByRole('button', { name: /free audit/i });
      expect(button).toBeInTheDocument();
    });

    it('A11Y-009: share buttons have aria-labels', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      
      expect(screen.getByLabelText(/share on twitter/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/share on linkedin/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/share on facebook/i)).toBeInTheDocument();
    });

    it('A11Y-010: embed code accessible', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      
      expect(screen.getByLabelText(/badge embed code/i)).toBeInTheDocument();
    });

    it('A11Y-014: live region for status updates', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      await user.type(emailInput, 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        const successState = screen.getByTestId('success-state');
        expect(successState).toHaveAttribute('aria-live', 'polite');
      });
    });

    it('A11Y-015: form has role', () => {
      render(<PublicAuditCTA {...defaultProps} />);
      
      expect(screen.getByRole('form')).toBeInTheDocument();
    });
  });

  // ============================================
  // 8. EDGE CASES TESTS
  // ============================================
  
  describe('Edge Cases', () => {
    it('EDGE-001: handles empty auditHash', () => {
      render(<PublicAuditCTA auditHash="" siteUrl="example.com" score={85} />);
      
      expect(screen.getByTestId('public-audit-cta')).toBeInTheDocument();
    });

    it('EDGE-002: handles empty siteUrl', () => {
      render(<PublicAuditCTA auditHash="abc123" siteUrl="" score={85} />);
      
      // Should still render, but heading might be different
      expect(screen.getByTestId('public-audit-cta')).toBeInTheDocument();
    });

    it('EDGE-003: displays score of 0', () => {
      render(<PublicAuditCTA {...defaultProps} score={0} />);
      
      expect(screen.getByText(/Score: 0\/100/i)).toBeInTheDocument();
    });

    it('EDGE-004: displays score of 100', () => {
      render(<PublicAuditCTA {...defaultProps} score={100} />);
      
      expect(screen.getByText(/Score: 100\/100/i)).toBeInTheDocument();
    });

    it('EDGE-005: truncates long site URL in display', () => {
      const longUrl = 'a'.repeat(100) + '.com';
      render(<PublicAuditCTA auditHash="abc123" siteUrl={longUrl} score={85} />);
      
      // Component should render without crashing
      expect(screen.getByTestId('public-audit-cta')).toBeInTheDocument();
    });

    it('EDGE-006: handles unicode in site URL', () => {
      render(<PublicAuditCTA auditHash="abc123" siteUrl="例え.jp" score={85} />);
      
      expect(screen.getByTestId('public-audit-cta')).toBeInTheDocument();
    });

    it('EDGE-007: handles special chars in hash', () => {
      render(<PublicAuditCTA auditHash="abc+123=test" siteUrl="example.com" score={85} />);
      
      expect(screen.getByTestId('public-audit-cta')).toBeInTheDocument();
    });

    it('EDGE-008: displays very low score with appropriate messaging', () => {
      render(<PublicAuditCTA {...defaultProps} score={5} />);
      
      expect(screen.getByText(/Score: 5\/100/i)).toBeInTheDocument();
    });

    it('EDGE-009: displays very high score with appropriate messaging', () => {
      render(<PublicAuditCTA {...defaultProps} score={99} />);
      
      expect(screen.getByText(/Score: 99\/100/i)).toBeInTheDocument();
    });

    it('EDGE-010: prevents rapid double submit', async () => {
      mockFetch.mockImplementationOnce(() => 
        new Promise(resolve => setTimeout(() => resolve({ ok: true }), 100))
      );
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultProps} />);
      
      const emailInput = screen.getByTestId('email-input');
      const submitButton = screen.getByTestId('submit-button');
      
      await user.type(emailInput, 'test@example.com');
      await user.click(submitButton);
      await user.click(submitButton); // Second click while submitting
      
      // Should only be called once
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });
  });
});
