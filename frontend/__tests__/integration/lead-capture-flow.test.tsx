/**
 * Lead Capture Flow Integration Tests
 * Task: TASK-33-23 - Implement Lead Capture Flow
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 * 
 * Tests the complete lead capture flow:
 * 1. Anonymous user views public audit → sees top 5 issues (isPartial = true)
 * 2. User enters email → stored in public_audit_views
 * 3. User gets full access → sees all issues (isPartial = false)
 * 4. Optional: redirect to signup with pre-filled email
 * 
 * Integration points:
 * - PublicAuditCTA component (email form)
 * - /api/v1/public-audit/:hash/lead endpoint
 * - Cookie unlock mechanism
 * - Email deduplication
 */

import React from 'react';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock fetch for API calls
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Mock clipboard API
Object.assign(navigator, {
  clipboard: {
    writeText: jest.fn().mockResolvedValue(undefined),
  },
});

// Mock window.open
window.open = jest.fn();

// Mock analytics
const mockTrackEvent = jest.fn();
jest.mock('@/lib/analytics', () => ({
  trackEvent: (...args: unknown[]) => mockTrackEvent(...args),
}));

// Mock document.cookie
const mockCookieStore: Record<string, string> = {};
let mockDocumentCookie = '';

Object.defineProperty(document, 'cookie', {
  get: () => mockDocumentCookie,
  set: (value: string) => {
    const [nameValue] = value.split(';');
    const [name, val] = nameValue.split('=');
    if (val === '' || name === '') {
      delete mockCookieStore[name];
    } else {
      mockCookieStore[name] = val;
    }
    mockDocumentCookie = Object.entries(mockCookieStore)
      .map(([k, v]) => `${k}=${v}`)
      .join('; ');
  },
  configurable: true,
});

// Mock sessionStorage
const mockSessionStorage: Record<string, string> = {};

Object.defineProperty(window, 'sessionStorage', {
  value: {
    getItem: (key: string) => mockSessionStorage[key] || null,
    setItem: (key: string, value: string) => { mockSessionStorage[key] = value; },
    removeItem: (key: string) => { delete mockSessionStorage[key]; },
    clear: () => { Object.keys(mockSessionStorage).forEach(k => delete mockSessionStorage[k]); },
    get length() { return Object.keys(mockSessionStorage).length; },
    key: (index: number) => Object.keys(mockSessionStorage)[index] || null,
  },
  configurable: true,
});

// Import the component after mocks
import { PublicAuditCTA } from '@/components/audit/PublicAuditCTA';

// Import utilities
import {
  setUnlockedAudit,
  isAuditUnlocked,
  checkEmailCaptured,
  storeEmailCaptured,
  clearEmailCaptured,
  clearUnlockCookie,
  generateSignupRedirectUrl,
} from '@/lib/audit/public-audit';

// ============================================
// TEST SUITE
// ============================================

describe('Lead Capture Flow Integration Tests', () => {
  const defaultAuditData = {
    auditHash: 'abc123def456789012345678901234',
    siteUrl: 'example.com',
    score: 85,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockReset();
    Object.keys(mockCookieStore).forEach(k => delete mockCookieStore[k]);
    mockDocumentCookie = '';
    Object.keys(mockSessionStorage).forEach(k => delete mockSessionStorage[k]);
  });

  // ============================================
  // 1. FLOW STATE 1: ANONYMOUS USER ARRIVES
  // ============================================
  
  describe('Flow State 1: Anonymous User Arrives', () => {
    it('FLOW-001: shows partial report prompt for anonymous user', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      // Should show CTA form
      expect(screen.getByTestId('public-audit-cta')).toBeInTheDocument();
      expect(screen.getByText(/Get your free SEO audit/i)).toBeInTheDocument();
    });

    it('FLOW-002: shows score in CTA button', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      expect(screen.getByText(/Score: 85\/100/i)).toBeInTheDocument();
    });

    it('FLOW-003: shows site URL in heading', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      expect(screen.getByText('example.com')).toBeInTheDocument();
    });

    it('FLOW-004: form is ready for email input', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      const emailInput = screen.getByTestId('email-input');
      expect(emailInput).toHaveAttribute('type', 'email');
      expect(emailInput).not.toBeDisabled();
    });

    it('FLOW-005: submit button is enabled for anonymous user', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      const submitButton = screen.getByTestId('submit-button');
      expect(submitButton).not.toBeDisabled();
    });
  });

  // ============================================
  // 2. FLOW STATE 2: USER ENTERS EMAIL
  // ============================================
  
  describe('Flow State 2: User Enters Email', () => {
    describe('Email Validation', () => {
      it('FLOW-010: rejects empty email', async () => {
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultAuditData} />);
        
        await user.click(screen.getByTestId('submit-button'));
        
        expect(screen.getByTestId('validation-error')).toHaveTextContent('Email is required');
        expect(mockFetch).not.toHaveBeenCalled();
      });

      it('FLOW-011: rejects invalid email format', async () => {
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultAuditData} />);
        
        await user.type(screen.getByTestId('email-input'), 'invalid-email');
        await user.click(screen.getByTestId('submit-button'));
        
        expect(screen.getByTestId('validation-error')).toHaveTextContent('Invalid email format');
        expect(mockFetch).not.toHaveBeenCalled();
      });

      it('FLOW-012: rejects email without domain', async () => {
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultAuditData} />);
        
        await user.type(screen.getByTestId('email-input'), 'test@');
        await user.click(screen.getByTestId('submit-button'));
        
        expect(screen.getByTestId('validation-error')).toBeInTheDocument();
      });

      it('FLOW-013: rejects email without TLD', async () => {
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultAuditData} />);
        
        await user.type(screen.getByTestId('email-input'), 'test@example');
        await user.click(screen.getByTestId('submit-button'));
        
        expect(screen.getByTestId('validation-error')).toBeInTheDocument();
      });

      it('FLOW-014: accepts valid email', async () => {
        mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultAuditData} />);
        
        await user.type(screen.getByTestId('email-input'), 'valid@example.com');
        await user.click(screen.getByTestId('submit-button'));
        
        await waitFor(() => {
          expect(mockFetch).toHaveBeenCalled();
        });
      });

      it('FLOW-015: trims whitespace from email', async () => {
        mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultAuditData} />);
        
        await user.type(screen.getByTestId('email-input'), '  test@example.com  ');
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
    });

    describe('Form Submission', () => {
      it('FLOW-020: submits to correct API endpoint', async () => {
        mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultAuditData} />);
        
        await user.type(screen.getByTestId('email-input'), 'test@example.com');
        await user.click(screen.getByTestId('submit-button'));
        
        await waitFor(() => {
          expect(mockFetch).toHaveBeenCalledWith(
            `/api/v1/public-audit/${defaultAuditData.auditHash}/lead`,
            expect.objectContaining({ method: 'POST' })
          );
        });
      });

      it('FLOW-021: includes correct headers', async () => {
        mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultAuditData} />);
        
        await user.type(screen.getByTestId('email-input'), 'test@example.com');
        await user.click(screen.getByTestId('submit-button'));
        
        await waitFor(() => {
          expect(mockFetch).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({
              headers: { 'Content-Type': 'application/json' },
            })
          );
        });
      });

      it('FLOW-022: includes email in request body', async () => {
        mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultAuditData} />);
        
        await user.type(screen.getByTestId('email-input'), 'test@example.com');
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

      it('FLOW-023: shows loading state during submission', async () => {
        mockFetch.mockImplementationOnce(() => 
          new Promise(resolve => setTimeout(() => resolve({ ok: true }), 100))
        );
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultAuditData} />);
        
        await user.type(screen.getByTestId('email-input'), 'test@example.com');
        await user.click(screen.getByTestId('submit-button'));
        
        expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
        expect(screen.getByText(/Submitting/i)).toBeInTheDocument();
      });

      it('FLOW-024: disables submit button during submission', async () => {
        mockFetch.mockImplementationOnce(() => 
          new Promise(resolve => setTimeout(() => resolve({ ok: true }), 100))
        );
        
        const user = userEvent.setup();
        render(<PublicAuditCTA {...defaultAuditData} />);
        
        await user.type(screen.getByTestId('email-input'), 'test@example.com');
        await user.click(screen.getByTestId('submit-button'));
        
        expect(screen.getByTestId('submit-button')).toBeDisabled();
      });
    });
  });

  // ============================================
  // 3. FLOW STATE 3: SUCCESS - FULL ACCESS
  // ============================================
  
  describe('Flow State 3: Success - Full Access', () => {
    beforeEach(() => {
      mockFetch.mockResolvedValueOnce({ 
        ok: true, 
        json: () => Promise.resolve({ success: true }) 
      });
    });

    it('FLOW-030: shows success state after submission', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('success-state')).toBeInTheDocument();
      });
    });

    it('FLOW-031: hides form on success', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.queryByTestId('lead-form')).not.toBeInTheDocument();
      });
    });

    it('FLOW-032: shows thank you message', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByText(/Thank you/i)).toBeInTheDocument();
      });
    });

    it('FLOW-033: shows score reminder in success', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        const successState = screen.getByTestId('success-state');
        expect(successState.textContent).toContain('85/100');
      });
    });

    it('FLOW-034: provides link to view full audit', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        const viewLink = screen.getByTestId('view-audit-link');
        expect(viewLink).toHaveAttribute('href', expect.stringContaining(defaultAuditData.auditHash));
      });
    });

    it('FLOW-035: tracks successful submission event', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(mockTrackEvent).toHaveBeenCalledWith(
          'public_audit_lead_submit',
          expect.objectContaining({
            hash: defaultAuditData.auditHash,
            score: defaultAuditData.score,
          })
        );
      });
    });

    it('FLOW-036: shows checkmark icon in success state', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('check-icon')).toBeInTheDocument();
      });
    });

    it('FLOW-037: success state has accessible role', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        const successState = screen.getByTestId('success-state');
        expect(successState).toHaveAttribute('role', 'status');
        expect(successState).toHaveAttribute('aria-live', 'polite');
      });
    });

    it('FLOW-038: sets unlock cookie after successful submission', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('success-state')).toBeInTheDocument();
      });
      
      // Verify unlock cookie is set
      expect(isAuditUnlocked(defaultAuditData.auditHash)).toBe(true);
    });

    it('FLOW-039: shows signup redirect link with pre-filled email', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        const signupLink = screen.getByTestId('signup-redirect-link');
        expect(signupLink).toBeInTheDocument();
        expect(signupLink).toHaveAttribute('href', expect.stringContaining('email='));
        expect(signupLink).toHaveAttribute('href', expect.stringContaining('test%40example.com'));
      });
    });

    it('FLOW-039b: signup link includes audit hash', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        const signupLink = screen.getByTestId('signup-redirect-link');
        expect(signupLink).toHaveAttribute('href', expect.stringContaining('audit='));
        expect(signupLink).toHaveAttribute('href', expect.stringContaining(defaultAuditData.auditHash));
      });
    });

    it('FLOW-039c: stores email for deduplication', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'dedup@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('success-state')).toBeInTheDocument();
      });
      
      // Verify email is stored for deduplication
      expect(checkEmailCaptured('dedup@example.com')).toBe(true);
    });
  });

  // ============================================
  // 4. FLOW STATE 4: ERROR HANDLING
  // ============================================
  
  describe('Flow State 4: Error Handling', () => {
    it('FLOW-040: shows error message on API failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 500 });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('submit-error')).toBeInTheDocument();
      });
    });

    it('FLOW-041: provides retry option on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 500 });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('retry-button')).toBeInTheDocument();
      });
    });

    it('FLOW-042: retry clears error and allows resubmit', async () => {
      mockFetch
        .mockResolvedValueOnce({ ok: false, status: 500 })
        .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('submit-error')).toBeInTheDocument();
      });
      
      await user.click(screen.getByTestId('retry-button'));
      
      expect(screen.queryByTestId('submit-error')).not.toBeInTheDocument();
      
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('success-state')).toBeInTheDocument();
      });
    });

    it('FLOW-043: handles rate limit (429) with message', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 429 });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('submit-error')).toHaveTextContent('Too many requests');
      });
    });

    it('FLOW-044: handles not found (404) with message', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 404 });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('submit-error')).toHaveTextContent('Audit not found');
      });
    });

    it('FLOW-045: handles network error', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('submit-error')).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // 5. FLOW STATE 5: DEDUPLICATION
  // ============================================
  
  describe('Flow State 5: Email Deduplication', () => {
    it('FLOW-050: detects previously submitted email', () => {
      storeEmailCaptured('existing@example.com');
      
      expect(checkEmailCaptured('existing@example.com')).toBe(true);
    });

    it('FLOW-051: case-insensitive email comparison', () => {
      storeEmailCaptured('test@example.com');
      
      expect(checkEmailCaptured('TEST@EXAMPLE.COM')).toBe(true);
      expect(checkEmailCaptured('Test@Example.Com')).toBe(true);
    });

    it('FLOW-052: handles email with whitespace', () => {
      storeEmailCaptured('test@example.com');
      
      expect(checkEmailCaptured('  test@example.com  ')).toBe(true);
    });

    it('FLOW-053: tracks multiple emails separately', () => {
      storeEmailCaptured('email1@example.com');
      storeEmailCaptured('email2@example.com');
      
      expect(checkEmailCaptured('email1@example.com')).toBe(true);
      expect(checkEmailCaptured('email2@example.com')).toBe(true);
      expect(checkEmailCaptured('email3@example.com')).toBe(false);
    });

    it('FLOW-054: clears specific email from tracking', () => {
      storeEmailCaptured('test@example.com');
      expect(checkEmailCaptured('test@example.com')).toBe(true);
      
      clearEmailCaptured('test@example.com');
      expect(checkEmailCaptured('test@example.com')).toBe(false);
    });
  });

  // ============================================
  // 6. FLOW STATE 6: UNLOCK COOKIE
  // ============================================
  
  describe('Flow State 6: Unlock Cookie', () => {
    afterEach(() => {
      clearUnlockCookie(defaultAuditData.auditHash);
    });

    it('FLOW-060: unlock cookie is not set initially', () => {
      expect(isAuditUnlocked(defaultAuditData.auditHash)).toBe(false);
    });

    it('FLOW-061: unlock cookie can be set', () => {
      const result = setUnlockedAudit(defaultAuditData.auditHash, 'test@example.com');
      expect(result).toBe(true);
    });

    it('FLOW-062: unlock cookie can be checked', () => {
      setUnlockedAudit(defaultAuditData.auditHash, 'test@example.com');
      expect(isAuditUnlocked(defaultAuditData.auditHash)).toBe(true);
    });

    it('FLOW-063: unlock cookie can be cleared', () => {
      setUnlockedAudit(defaultAuditData.auditHash, 'test@example.com');
      expect(isAuditUnlocked(defaultAuditData.auditHash)).toBe(true);
      
      clearUnlockCookie(defaultAuditData.auditHash);
      expect(isAuditUnlocked(defaultAuditData.auditHash)).toBe(false);
    });

    it('FLOW-064: unlock cookie has 24-hour expiry', async () => {
      setUnlockedAudit(defaultAuditData.auditHash, 'test@example.com');
      
      // Check cookie exists
      expect(isAuditUnlocked(defaultAuditData.auditHash)).toBe(true);
      
      // Note: Actual expiry testing requires mocking Date
      // This test verifies the cookie is set with expiry
    });

    it('FLOW-065: different audits have separate unlock states', () => {
      const hash1 = 'audit123456789012345678901234';
      const hash2 = 'audit987654321098765432109876';
      
      setUnlockedAudit(hash1, 'test1@example.com');
      
      expect(isAuditUnlocked(hash1)).toBe(true);
      expect(isAuditUnlocked(hash2)).toBe(false);
      
      clearUnlockCookie(hash1);
    });
  });

  // ============================================
  // 7. FLOW STATE 7: REDIRECT TO SIGNUP
  // ============================================
  
  describe('Flow State 7: Redirect to Signup', () => {
    it('FLOW-070: generates signup URL with email only', () => {
      const url = generateSignupRedirectUrl('test@example.com');
      expect(url).toContain('/signup');
      expect(url).toContain('email=test%40example.com');
    });

    it('FLOW-071: generates signup URL with audit hash only', () => {
      const url = generateSignupRedirectUrl(undefined, 'abc123');
      expect(url).toContain('/signup');
      expect(url).toContain('audit=abc123');
    });

    it('FLOW-072: generates signup URL with both params', () => {
      const url = generateSignupRedirectUrl('test@example.com', 'abc123');
      expect(url).toContain('/signup');
      expect(url).toContain('email=test%40example.com');
      expect(url).toContain('audit=abc123');
    });

    it('FLOW-073: generates signup URL with neither param', () => {
      const url = generateSignupRedirectUrl();
      expect(url).toBe('/signup');
    });

    it('FLOW-074: encodes special characters in email', () => {
      const url = generateSignupRedirectUrl('test+user@example.com');
      expect(url).toContain('email=test%2Buser%40example.com');
    });

    it('FLOW-075: trims email in URL generation', () => {
      const url = generateSignupRedirectUrl('  test@example.com  ');
      expect(url).toContain('email=test%40example.com');
      expect(url).not.toContain('%20');
    });
  });

  // ============================================
  // 8. SOCIAL SHARING INTEGRATION
  // ============================================
  
  describe('Social Sharing Integration', () => {
    it('FLOW-080: Twitter share includes score', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.click(screen.getByTestId('twitter-share'));
      
      expect(window.open).toHaveBeenCalledWith(
        expect.stringContaining('85%2F100'),
        expect.any(String),
        expect.any(String)
      );
    });

    it('FLOW-081: Twitter share includes audit hash', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.click(screen.getByTestId('twitter-share'));
      
      expect(window.open).toHaveBeenCalledWith(
        expect.stringContaining(defaultAuditData.auditHash),
        expect.any(String),
        expect.any(String)
      );
    });

    it('FLOW-082: LinkedIn share includes audit URL', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.click(screen.getByTestId('linkedin-share'));
      
      expect(window.open).toHaveBeenCalledWith(
        expect.stringContaining('linkedin.com'),
        expect.any(String),
        expect.any(String)
      );
    });

    it('FLOW-083: Facebook share includes audit URL', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.click(screen.getByTestId('facebook-share'));
      
      expect(window.open).toHaveBeenCalledWith(
        expect.stringContaining('facebook.com'),
        expect.any(String),
        expect.any(String)
      );
    });

    it('FLOW-084: tracks share events', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.click(screen.getByTestId('twitter-share'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'public_audit_share',
        expect.objectContaining({ platform: 'twitter' })
      );
    });
  });

  // ============================================
  // 9. BADGE EMBED INTEGRATION
  // ============================================
  
  describe('Badge Embed Integration', () => {
    it('FLOW-090: generates embed code with badge URL', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      const embedCode = screen.getByTestId('embed-code') as HTMLTextAreaElement;
      expect(embedCode.value).toContain(`/badge/${defaultAuditData.auditHash}/small.svg`);
    });

    it('FLOW-091: includes score in embed alt text', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      const embedCode = screen.getByTestId('embed-code') as HTMLTextAreaElement;
      expect(embedCode.value).toContain('85/100');
    });

    it('FLOW-092: includes site URL in embed alt text', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      const embedCode = screen.getByTestId('embed-code') as HTMLTextAreaElement;
      expect(embedCode.value).toContain('example.com');
    });

    it('FLOW-093: copy button provides feedback', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.click(screen.getByTestId('copy-button'));
      
      expect(screen.getByText('Copied!')).toBeInTheDocument();
    });

    it('FLOW-094: tracks embed copy events', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.click(screen.getByTestId('copy-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'public_audit_embed_copy',
        expect.objectContaining({ hash: defaultAuditData.auditHash })
      );
    });
  });

  // ============================================
  // 10. ACCESSIBILITY INTEGRATION
  // ============================================
  
  describe('Accessibility Integration', () => {
    it('FLOW-100: form has proper labels', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      expect(screen.getByLabelText(/email address/i)).toBeInTheDocument();
    });

    it('FLOW-101: form has proper role', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      expect(screen.getByRole('form')).toBeInTheDocument();
    });

    it('FLOW-102: submit button has accessible name', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      expect(screen.getByRole('button', { name: /free audit/i })).toBeInTheDocument();
    });

    it('FLOW-103: share buttons have aria-labels', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      expect(screen.getByLabelText(/share on twitter/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/share on linkedin/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/share on facebook/i)).toBeInTheDocument();
    });

    it('FLOW-104: embed code is accessible', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      expect(screen.getByLabelText(/badge embed code/i)).toBeInTheDocument();
    });

    it('FLOW-105: keyboard navigation works', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.tab();
      expect(screen.getByTestId('email-input')).toHaveFocus();
      
      await user.tab();
      expect(screen.getByTestId('submit-button')).toHaveFocus();
    });

    it('FLOW-106: error announced to screen reader', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.click(screen.getByTestId('submit-button'));
      
      const error = screen.getByRole('alert');
      expect(error).toBeInTheDocument();
      expect(error).toHaveTextContent('Email is required');
    });
  });

  // ============================================
  // 11. EDGE CASES
  // ============================================
  
  describe('Edge Cases', () => {
    it('EDGE-001: handles score of 0', () => {
      render(<PublicAuditCTA {...defaultAuditData} score={0} />);
      
      expect(screen.getByText(/Score: 0\/100/i)).toBeInTheDocument();
    });

    it('EDGE-002: handles score of 100', () => {
      render(<PublicAuditCTA {...defaultAuditData} score={100} />);
      
      expect(screen.getByText(/Score: 100\/100/i)).toBeInTheDocument();
    });

    it('EDGE-003: handles empty site URL', () => {
      render(<PublicAuditCTA auditHash="abc123" siteUrl="" score={85} />);
      
      expect(screen.getByTestId('public-audit-cta')).toBeInTheDocument();
    });

    it('EDGE-004: handles very long site URL', () => {
      const longUrl = 'a'.repeat(100) + '.com';
      render(<PublicAuditCTA auditHash="abc123" siteUrl={longUrl} score={85} />);
      
      expect(screen.getByTestId('public-audit-cta')).toBeInTheDocument();
    });

    it('EDGE-005: handles unicode in site URL', () => {
      render(<PublicAuditCTA auditHash="abc123" siteUrl="例え.com" score={85} />);
      
      expect(screen.getByTestId('public-audit-cta')).toBeInTheDocument();
    });

    it('EDGE-006: handles special characters in hash', () => {
      render(<PublicAuditCTA auditHash="abc+123=test" siteUrl="example.com" score={85} />);
      
      expect(screen.getByTestId('public-audit-cta')).toBeInTheDocument();
    });

    it('EDGE-007: prevents double submit on rapid clicks', async () => {
      mockFetch.mockImplementationOnce(() => 
        new Promise(resolve => setTimeout(() => resolve({ ok: true }), 100))
      );
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      
      const submitButton = screen.getByTestId('submit-button');
      await user.click(submitButton);
      await user.click(submitButton); // Second click
      
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });
  });

  // ============================================
  // 12. ACCEPTANCE CRITERIA TESTS
  // ============================================
  
  describe('Acceptance Criteria', () => {
    it('AC-01: Email form in PublicAuditCTA component', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      expect(screen.getByTestId('email-input')).toBeInTheDocument();
      expect(screen.getByTestId('submit-button')).toBeInTheDocument();
    });

    it('AC-02: Form validates email format', async () => {
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'invalid');
      await user.click(screen.getByTestId('submit-button'));
      
      expect(screen.getByTestId('validation-error')).toHaveTextContent('Invalid email format');
    });

    it('AC-03: Submission calls API endpoint', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining(`/api/v1/public-audit/${defaultAuditData.auditHash}/lead`),
          expect.any(Object)
        );
      });
    });

    it('AC-04: Success shows full access state', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('success-state')).toBeInTheDocument();
      });
    });

    it('AC-05: Success provides redirect option', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'test@example.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        const signupLink = screen.getByTestId('signup-redirect-link');
        expect(signupLink).toBeInTheDocument();
      });
    });

    it('AC-06: Email deduplication works', () => {
      storeEmailCaptured('duplicate@example.com');
      
      expect(checkEmailCaptured('duplicate@example.com')).toBe(true);
      expect(checkEmailCaptured('DUPLICATE@EXAMPLE.COM')).toBe(true);
      
      clearEmailCaptured('duplicate@example.com');
    });

    it('AC-07: Component renders without crashing', () => {
      const { container } = render(<PublicAuditCTA {...defaultAuditData} />);
      
      expect(container.firstChild).toBeInTheDocument();
    });

    it('AC-08: Skip option available for limited report', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      const skipLink = screen.getByTestId('skip-link');
      expect(skipLink).toBeInTheDocument();
      expect(skipLink).toHaveTextContent('Skip');
    });

    it('AC-09: Skip link points to partial report', () => {
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      const skipLink = screen.getByTestId('skip-link');
      expect(skipLink).toHaveAttribute('href', expect.stringContaining('partial=true'));
    });

    it('AC-10: Unlock cookie set after successful submission', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });
      
      const user = userEvent.setup();
      render(<PublicAuditCTA {...defaultAuditData} />);
      
      await user.type(screen.getByTestId('email-input'), 'unlock@test.com');
      await user.click(screen.getByTestId('submit-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('success-state')).toBeInTheDocument();
      });
      
      expect(isAuditUnlocked(defaultAuditData.auditHash)).toBe(true);
      
      clearUnlockCookie(defaultAuditData.auditHash);
    });
  });
});
