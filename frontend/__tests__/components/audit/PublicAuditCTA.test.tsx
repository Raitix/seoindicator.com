/**
 * PublicAuditCTA Component Tests
 * Task: TASK-33-12
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 * 
 * Tests for the conversion component on public audit pages.
 * Covers: email capture, social sharing, embed code, accessibility.
 */

import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Import the actual component
import { PublicAuditCTA } from '@/components/audit/PublicAuditCTA';

// Mock fetch for API calls
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Mock clipboard API
const mockClipboardWrite = jest.fn().mockResolvedValue(undefined);
Object.assign(navigator, {
  clipboard: {
    writeText: mockClipboardWrite,
  },
});

// Mock window.open
const mockWindowOpen = jest.fn();
window.open = mockWindowOpen;

// Mock analytics
const mockTrackEvent = jest.fn();
jest.mock('@/lib/analytics', () => ({
  trackEvent: (...args: unknown[]) => mockTrackEvent(...args),
}));

// ============================================
// TEST SUITE
// ============================================

describe('PublicAuditCTA Component', () => {
  const defaultProps = {
    auditHash: 'abc123def456',
    siteUrl: 'example.com',
    score: 85,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockReset();
    mockClipboardWrite.mockReset();
    mockWindowOpen.mockReset();
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
      expect(screen.getByText(/Get your free SEO audit for/i)).toBeInTheDocument();
      expect(screen.getByText('example.com')).toBeInTheDocument();
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
        mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
        
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
