/**
 * ShareAuditModal Component Tests
 * Task: TASK-33-20
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 *
 * Tests for the share modal that allows users to publish/unpublish audits
 * and get the public URL.
 *
 * Covers:
 * - Modal rendering and visibility
 * - Toggle switch for publish/unpublish
 * - Public URL display with copy functionality
 * - Confirmation dialog for unpublish
 * - Loading and error states
 * - Accessibility requirements
 */

import React from 'react';
import { render, screen, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock the ShareAuditModal component (TDD - will be implemented by Developer)
type ShareAuditModalProps = {
  isOpen: boolean;
  onClose: () => void;
  auditId: number;
  auditHash: string;
  auditUrl: string;
  score: number;
  isPublic: boolean;
  onPublish: () => Promise<void>;
  onUnpublish: () => Promise<void>;
};

// Mock component for TDD - demonstrates expected interface
const ShareAuditModal = ({
  isOpen,
  onClose,
  auditHash,
  auditUrl,
  score,
  isPublic,
  onPublish,
  onUnpublish,
}: ShareAuditModalProps) => {
  const [isLoading, setIsLoading] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [showConfirmUnpublish, setShowConfirmUnpublish] = React.useState(false);
  const [copied, setCopied] = React.useState(false);
  const [localIsPublic, setLocalIsPublic] = React.useState(isPublic);

  // Sync with prop changes
  React.useEffect(() => {
    setLocalIsPublic(isPublic);
  }, [isPublic]);

  if (!isOpen) return null;

  const publicUrl = `${window.location.origin}/audit/${auditHash}`;

  const handleToggle = async () => {
    if (localIsPublic) {
      setShowConfirmUnpublish(true);
    } else {
      setIsLoading(true);
      setError(null);
      try {
        await onPublish();
        setLocalIsPublic(true);
      } catch (e) {
        setError('Failed to publish audit');
        throw e;
      } finally {
        setIsLoading(false);
      }
    }
  };

  const handleConfirmUnpublish = async () => {
    setIsLoading(true);
    setError(null);
    try {
      await onUnpublish();
      setLocalIsPublic(false);
      setShowConfirmUnpublish(false);
    } catch (e) {
      setError('Failed to unpublish audit');
    } finally {
      setIsLoading(false);
    }
  };

  const handleCopy = async () => {
    await navigator.clipboard.writeText(publicUrl);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handleCancelConfirm = () => {
    setShowConfirmUnpublish(false);
  };

  const handleError = () => {
    setError('Network error, try again');
  };

  return (
    <div
      data-testid="share-audit-modal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
    >
      <div data-testid="modal-header">
        <h2 id="modal-title">Share Audit</h2>
        <button
          data-testid="close-button"
          aria-label="Close dialog"
          onClick={onClose}
        >
          ×
        </button>
      </div>

      <div data-testid="modal-content">
        <div data-testid="audit-info">
          <span data-testid="audit-url">{auditUrl}</span>
          <span data-testid="audit-score">Score: {score}/100</span>
        </div>

        <div data-testid="visibility-section">
          <span data-testid="visibility-status" aria-live="polite">
            {localIsPublic ? 'Public' : 'Private'}
          </span>
          <button
            role="switch"
            aria-checked={localIsPublic}
            aria-label="Toggle public visibility"
            data-testid="visibility-toggle"
            onClick={handleToggle}
            disabled={isLoading}
          >
            {localIsPublic ? 'ON' : 'OFF'}
          </button>
        </div>

        {localIsPublic && (
          <div data-testid="public-url-section">
            <input
              type="text"
              data-testid="public-url-input"
              value={publicUrl}
              readOnly
              aria-label="Public audit URL"
            />
            <button
              data-testid="copy-url-button"
              aria-label="Copy public URL"
              onClick={handleCopy}
            >
              {copied ? 'Copied!' : 'Copy'}
            </button>
          </div>
        )}

        {isLoading && (
          <div data-testid="loading-indicator">
            <span>Loading...</span>
          </div>
        )}

        {error && (
          <div data-testid="error-message" role="alert">
            {error}
            <button data-testid="retry-button" onClick={handleToggle}>
              Try again
            </button>
          </div>
        )}
      </div>

      <div data-testid="modal-footer">
        <button data-testid="cancel-button" onClick={onClose}>
          Cancel
        </button>
      </div>

      {/* Confirmation Dialog for Unpublish */}
      {showConfirmUnpublish && (
        <div data-testid="confirm-unpublish-dialog" role="alertdialog">
          <h3>Unpublish Audit?</h3>
          <p>This will make the audit private. Anyone with the link will no longer be able to access it.</p>
          <button data-testid="confirm-unpublish-button" onClick={handleConfirmUnpublish}>
            Unpublish
          </button>
          <button data-testid="cancel-unpublish-button" onClick={handleCancelConfirm}>
            Cancel
          </button>
        </div>
      )}
    </div>
  );
};

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

// Mock analytics
const mockTrackEvent = jest.fn();
jest.mock('@/lib/analytics', () => ({
  trackEvent: (...args: unknown[]) => mockTrackEvent(...args),
}));

// ============================================
// TEST SUITE
// ============================================

describe('ShareAuditModal Component', () => {
  const defaultProps = {
    isOpen: true,
    onClose: jest.fn(),
    auditId: 1,
    auditHash: 'abc123def4567890123456789012',
    auditUrl: 'example.com',
    score: 85,
    isPublic: false,
    onPublish: jest.fn().mockResolvedValue(undefined),
    onUnpublish: jest.fn().mockResolvedValue(undefined),
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockReset();
    mockClipboardWrite.mockReset();
  });

  // ============================================
  // 1. RENDERING TESTS
  // ============================================

  describe('Rendering', () => {
    it('REND-001: renders when isOpen is true', () => {
      render(<ShareAuditModal {...defaultProps} />);
      expect(screen.getByTestId('share-audit-modal')).toBeInTheDocument();
    });

    it('REND-002: does not render when isOpen is false', () => {
      render(<ShareAuditModal {...defaultProps} isOpen={false} />);
      expect(screen.queryByTestId('share-audit-modal')).not.toBeInTheDocument();
    });

    it('REND-003: displays audit URL', () => {
      render(<ShareAuditModal {...defaultProps} />);
      expect(screen.getByTestId('audit-url')).toHaveTextContent('example.com');
    });

    it('REND-004: displays Public status when public', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      expect(screen.getByTestId('visibility-status')).toHaveTextContent('Public');
    });

    it('REND-005: displays Private status when private', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={false} />);
      expect(screen.getByTestId('visibility-status')).toHaveTextContent('Private');
    });

    it('REND-006: renders toggle switch', () => {
      render(<ShareAuditModal {...defaultProps} />);
      expect(screen.getByTestId('visibility-toggle')).toBeInTheDocument();
    });

    it('REND-007: renders public URL input when public', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      expect(screen.getByTestId('public-url-input')).toBeInTheDocument();
    });

    it('REND-008: hides public URL when private', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={false} />);
      expect(screen.queryByTestId('public-url-input')).not.toBeInTheDocument();
    });

    it('REND-009: renders Cancel button', () => {
      render(<ShareAuditModal {...defaultProps} />);
      expect(screen.getByTestId('cancel-button')).toBeInTheDocument();
    });

    it('REND-010: renders close button', () => {
      render(<ShareAuditModal {...defaultProps} />);
      expect(screen.getByTestId('close-button')).toBeInTheDocument();
    });

    it('REND-011: displays score badge', () => {
      render(<ShareAuditModal {...defaultProps} score={85} />);
      expect(screen.getByTestId('audit-score')).toHaveTextContent('85/100');
    });
  });

  // ============================================
  // 2. TOGGLE SWITCH TESTS
  // ============================================

  describe('Toggle Switch', () => {
    it('TOGGLE-001: toggle shows ON when public', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      const toggle = screen.getByTestId('visibility-toggle');
      expect(toggle).toHaveTextContent('ON');
    });

    it('TOGGLE-002: toggle shows OFF when private', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={false} />);
      const toggle = screen.getByTestId('visibility-toggle');
      expect(toggle).toHaveTextContent('OFF');
    });

    it('TOGGLE-003: toggle click triggers publish when private', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockResolvedValue(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(onPublish).toHaveBeenCalled();
      });
    });

    it('TOGGLE-004: toggle click shows confirmation when public', async () => {
      const user = userEvent.setup();
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      expect(screen.getByTestId('confirm-unpublish-dialog')).toBeInTheDocument();
    });

    it('TOGGLE-005: toggle disabled during loading', async () => {
      const user = userEvent.setup();
      let resolvePublish: () => void;
      const onPublish = jest.fn().mockImplementation(() => 
        new Promise<void>(resolve => { resolvePublish = resolve; })
      );
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      expect(screen.getByTestId('visibility-toggle')).toBeDisabled();
      
      resolvePublish!();
    });

    it('TOGGLE-006: toggle has aria-checked attribute', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      const toggle = screen.getByTestId('visibility-toggle');
      expect(toggle).toHaveAttribute('aria-checked', 'true');
    });

    it('TOGGLE-007: toggle keyboard interaction with Space', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockResolvedValue(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      screen.getByTestId('visibility-toggle').focus();
      await user.keyboard(' ');
      
      await waitFor(() => {
        expect(onPublish).toHaveBeenCalled();
      });
    });
  });

  // ============================================
  // 3. PUBLISH FLOW TESTS
  // ============================================

  describe('Publish Flow', () => {
    it('PUB-001: calls onPublish when toggling from private to public', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockResolvedValue(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(onPublish).toHaveBeenCalled();
      });
    });

    it('PUB-002: shows loading state during publish', async () => {
      const user = userEvent.setup();
      let resolvePublish: () => void;
      const onPublish = jest.fn().mockImplementation(() => 
        new Promise<void>(resolve => { resolvePublish = resolve; })
      );
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      expect(screen.getByTestId('loading-indicator')).toBeInTheDocument();
      
      resolvePublish!();
    });

    it('PUB-003: success updates visibility state', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockResolvedValue(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(screen.getByTestId('visibility-status')).toHaveTextContent('Public');
      });
    });

    it('PUB-004: success shows public URL', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockResolvedValue(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(screen.getByTestId('public-url-input')).toBeInTheDocument();
      });
    });

    it('PUB-005: optimistic UI update on publish', async () => {
      const user = userEvent.setup();
      let resolvePublish: () => void;
      const onPublish = jest.fn().mockImplementation(() => 
        new Promise<void>(resolve => { resolvePublish = resolve; })
      );
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      // Optimistic update - shows public immediately
      expect(screen.getByTestId('visibility-status')).toHaveTextContent('Public');
      
      resolvePublish!();
    });

    it('PUB-006: reverts on API failure', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockRejectedValue(new Error('Failed'));
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(screen.getByTestId('visibility-status')).toHaveTextContent('Private');
      });
    });

    it('PUB-007: shows error message on failure', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockRejectedValue(new Error('Failed'));
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(screen.getByTestId('error-message')).toBeInTheDocument();
      });
    });

    it('PUB-008: handles rate limit (429)', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockRejectedValue(new Error('Rate limit'));
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(screen.getByTestId('error-message')).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // 4. UNPUBLISH FLOW TESTS
  // ============================================

  describe('Unpublish Flow', () => {
    it('UNPUB-001: shows confirmation dialog before unpublish', async () => {
      const user = userEvent.setup();
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      expect(screen.getByTestId('confirm-unpublish-dialog')).toBeInTheDocument();
    });

    it('UNPUB-002: confirmation shows warning message', async () => {
      const user = userEvent.setup();
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      expect(screen.getByText(/make the audit private/i)).toBeInTheDocument();
    });

    it('UNPUB-003: cancel closes confirmation without action', async () => {
      const user = userEvent.setup();
      const onUnpublish = jest.fn();
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} onUnpublish={onUnpublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      await user.click(screen.getByTestId('cancel-unpublish-button'));
      
      expect(onUnpublish).not.toHaveBeenCalled();
      expect(screen.queryByTestId('confirm-unpublish-dialog')).not.toBeInTheDocument();
    });

    it('UNPUB-004: confirm calls onUnpublish', async () => {
      const user = userEvent.setup();
      const onUnpublish = jest.fn().mockResolvedValue(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} onUnpublish={onUnpublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      await user.click(screen.getByTestId('confirm-unpublish-button'));
      
      await waitFor(() => {
        expect(onUnpublish).toHaveBeenCalled();
      });
    });

    it('UNPUB-005: loading during unpublish', async () => {
      const user = userEvent.setup();
      let resolveUnpublish: () => void;
      const onUnpublish = jest.fn().mockImplementation(() => 
        new Promise<void>(resolve => { resolveUnpublish = resolve; })
      );
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} onUnpublish={onUnpublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      await user.click(screen.getByTestId('confirm-unpublish-button'));
      
      expect(screen.getByTestId('loading-indicator')).toBeInTheDocument();
      
      resolveUnpublish!();
    });

    it('UNPUB-006: success hides public URL', async () => {
      const user = userEvent.setup();
      const onUnpublish = jest.fn().mockResolvedValue(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} onUnpublish={onUnpublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      await user.click(screen.getByTestId('confirm-unpublish-button'));
      
      await waitFor(() => {
        expect(screen.queryByTestId('public-url-input')).not.toBeInTheDocument();
      });
    });

    it('UNPUB-007: success updates status to Private', async () => {
      const user = userEvent.setup();
      const onUnpublish = jest.fn().mockResolvedValue(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} onUnpublish={onUnpublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      await user.click(screen.getByTestId('confirm-unpublish-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('visibility-status')).toHaveTextContent('Private');
      });
    });

    it('UNPUB-008: error shows message on unpublish failure', async () => {
      const user = userEvent.setup();
      const onUnpublish = jest.fn().mockRejectedValue(new Error('Failed'));
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} onUnpublish={onUnpublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      await user.click(screen.getByTestId('confirm-unpublish-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('error-message')).toHaveTextContent('Failed to unpublish audit');
      });
    });
  });

  // ============================================
  // 5. PUBLIC URL TESTS
  // ============================================

  describe('Public URL', () => {
    it('URL-001: URL input is readonly', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      const input = screen.getByTestId('public-url-input');
      expect(input).toHaveAttribute('readonly');
    });

    it('URL-002: URL format is correct', () => {
      render(<ShareAuditModal {...defaultProps} auditHash="abc123" isPublic={true} />);
      const input = screen.getByTestId('public-url-input') as HTMLInputElement;
      expect(input.value).toContain('/audit/abc123');
    });

    it('URL-003: copy button copies URL to clipboard', async () => {
      const user = userEvent.setup();
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      
      await user.click(screen.getByTestId('copy-url-button'));
      
      expect(mockClipboardWrite).toHaveBeenCalled();
    });

    it('URL-004: copy shows feedback', async () => {
      const user = userEvent.setup();
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      
      await user.click(screen.getByTestId('copy-url-button'));
      
      expect(screen.getByText('Copied!')).toBeInTheDocument();
    });

    it('URL-005: copy button not visible when private', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={false} />);
      expect(screen.queryByTestId('copy-url-button')).not.toBeInTheDocument();
    });

    it('URL-006: full URL includes domain', () => {
      render(<ShareAuditModal {...defaultProps} auditHash="abc123" isPublic={true} />);
      const input = screen.getByTestId('public-url-input') as HTMLInputElement;
      expect(input.value).toMatch(/^https?:\/\//);
    });
  });

  // ============================================
  // 6. MODAL INTERACTION TESTS
  // ============================================

  describe('Modal Interaction', () => {
    it('MODAL-001: Cancel button closes modal', async () => {
      const user = userEvent.setup();
      const onClose = jest.fn();
      
      render(<ShareAuditModal {...defaultProps} onClose={onClose} />);
      
      await user.click(screen.getByTestId('cancel-button'));
      
      expect(onClose).toHaveBeenCalled();
    });

    it('MODAL-002: X button closes modal', async () => {
      const user = userEvent.setup();
      const onClose = jest.fn();
      
      render(<ShareAuditModal {...defaultProps} onClose={onClose} />);
      
      await user.click(screen.getByTestId('close-button'));
      
      expect(onClose).toHaveBeenCalled();
    });

    it('MODAL-003: Escape key closes modal', async () => {
      const user = userEvent.setup();
      const onClose = jest.fn();
      
      render(<ShareAuditModal {...defaultProps} onClose={onClose} />);
      
      await user.keyboard('{Escape}');
      
      expect(onClose).toHaveBeenCalled();
    });

    it('MODAL-004: has dialog role', () => {
      render(<ShareAuditModal {...defaultProps} />);
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('MODAL-005: has aria-modal attribute', () => {
      render(<ShareAuditModal {...defaultProps} />);
      const modal = screen.getByTestId('share-audit-modal');
      expect(modal).toHaveAttribute('aria-modal', 'true');
    });

    it('MODAL-006: has aria-labelledby', () => {
      render(<ShareAuditModal {...defaultProps} />);
      const modal = screen.getByTestId('share-audit-modal');
      expect(modal).toHaveAttribute('aria-labelledby', 'modal-title');
    });

    it('MODAL-007: title has correct id', () => {
      render(<ShareAuditModal {...defaultProps} />);
      const title = screen.getByText('Share Audit');
      expect(title).toHaveAttribute('id', 'modal-title');
    });
  });

  // ============================================
  // 7. ACCESSIBILITY TESTS
  // ============================================

  describe('Accessibility', () => {
    it('A11Y-001: modal has dialog role', () => {
      render(<ShareAuditModal {...defaultProps} />);
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('A11Y-002: toggle has switch role', () => {
      render(<ShareAuditModal {...defaultProps} />);
      expect(screen.getByRole('switch')).toBeInTheDocument();
    });

    it('A11Y-003: toggle aria-checked updates on change', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockResolvedValue(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      const toggle = screen.getByRole('switch');
      expect(toggle).toHaveAttribute('aria-checked', 'false');
      
      await user.click(toggle);
      
      await waitFor(() => {
        expect(toggle).toHaveAttribute('aria-checked', 'true');
      });
    });

    it('A11Y-004: close button has accessible name', () => {
      render(<ShareAuditModal {...defaultProps} />);
      const closeButton = screen.getByTestId('close-button');
      expect(closeButton).toHaveAttribute('aria-label', 'Close dialog');
    });

    it('A11Y-005: visibility status has live region', () => {
      render(<ShareAuditModal {...defaultProps} />);
      const status = screen.getByTestId('visibility-status');
      expect(status).toHaveAttribute('aria-live', 'polite');
    });

    it('A11Y-006: error message has alert role', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockRejectedValue(new Error('Failed'));
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        const error = screen.getByTestId('error-message');
        expect(error).toHaveAttribute('role', 'alert');
      });
    });

    it('A11Y-007: copy button has aria-label', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      const copyButton = screen.getByTestId('copy-url-button');
      expect(copyButton).toHaveAttribute('aria-label');
    });

    it('A11Y-008: keyboard navigation reaches all elements', async () => {
      const user = userEvent.setup();
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      
      await user.tab();
      expect(screen.getByTestId('close-button')).toHaveFocus();
      
      await user.tab();
      expect(screen.getByTestId('visibility-toggle')).toHaveFocus();
    });
  });

  // ============================================
  // 8. ERROR HANDLING TESTS
  // ============================================

  describe('Error Handling', () => {
    it('ERR-001: shows network error', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockRejectedValue(new Error('Network error'));
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(screen.getByTestId('error-message')).toBeInTheDocument();
      });
    });

    it('ERR-002: shows server error', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockRejectedValue(new Error('Server error'));
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(screen.getByTestId('error-message')).toHaveTextContent('Failed');
      });
    });

    it('ERR-007: retry button available on error', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn()
        .mockRejectedValueOnce(new Error('Failed'))
        .mockResolvedValueOnce(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(screen.getByTestId('retry-button')).toBeInTheDocument();
      });
    });

    it('ERR-008: retry repeats action', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn()
        .mockRejectedValueOnce(new Error('Failed'))
        .mockResolvedValueOnce(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(screen.getByTestId('retry-button')).toBeInTheDocument();
      });
      
      await user.click(screen.getByTestId('retry-button'));
      
      await waitFor(() => {
        expect(onPublish).toHaveBeenCalledTimes(2);
      });
    });
  });

  // ============================================
  // 9. EDGE CASES TESTS
  // ============================================

  describe('Edge Cases', () => {
    it('EDGE-001: handles empty auditHash', () => {
      render(<ShareAuditModal {...defaultProps} auditHash="" isPublic={true} />);
      expect(screen.getByTestId('share-audit-modal')).toBeInTheDocument();
    });

    it('EDGE-002: very long audit URL', () => {
      const longUrl = 'a'.repeat(200) + '.com';
      render(<ShareAuditModal {...defaultProps} auditUrl={longUrl} />);
      expect(screen.getByTestId('audit-url')).toBeInTheDocument();
    });

    it('EDGE-003: unicode in audit URL', () => {
      render(<ShareAuditModal {...defaultProps} auditUrl="例え.jp" />);
      expect(screen.getByTestId('audit-url')).toHaveTextContent('例え.jp');
    });

    it('EDGE-004: score of 0', () => {
      render(<ShareAuditModal {...defaultProps} score={0} />);
      expect(screen.getByTestId('audit-score')).toHaveTextContent('0/100');
    });

    it('EDGE-005: score of 100', () => {
      render(<ShareAuditModal {...defaultProps} score={100} />);
      expect(screen.getByTestId('audit-score')).toHaveTextContent('100/100');
    });

    it('EDGE-006: rapid toggle clicks', async () => {
      const user = userEvent.setup();
      let resolvePublish: () => void;
      const onPublish = jest.fn().mockImplementation(() => 
        new Promise<void>(resolve => { resolvePublish = resolve; })
      );
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      const toggle = screen.getByTestId('visibility-toggle');
      
      await user.click(toggle);
      await user.click(toggle); // Second click while first is pending
      
      // Should only call once
      expect(onPublish).toHaveBeenCalledTimes(1);
      
      resolvePublish!();
    });

    it('EDGE-007: modal reopen resets state', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockRejectedValue(new Error('Failed'));
      
      const { rerender } = render(
        <ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />
      );
      
      // Trigger error
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(screen.getByTestId('error-message')).toBeInTheDocument();
      });
      
      // Close and reopen
      rerender(
        <ShareAuditModal {...defaultProps} isOpen={false} onPublish={onPublish} />
      );
      rerender(
        <ShareAuditModal {...defaultProps} isOpen={true} isPublic={false} onPublish={jest.fn()} />
      );
      
      // Error should be cleared
      expect(screen.queryByTestId('error-message')).not.toBeInTheDocument();
    });

    it('EDGE-008: props change updates UI', () => {
      const { rerender } = render(
        <ShareAuditModal {...defaultProps} isPublic={false} />
      );
      
      expect(screen.getByTestId('visibility-status')).toHaveTextContent('Private');
      
      rerender(
        <ShareAuditModal {...defaultProps} isPublic={true} />
      );
      
      expect(screen.getByTestId('visibility-status')).toHaveTextContent('Public');
    });
  });

  // ============================================
  // 10. SNAPSHOT TESTS
  // ============================================

  describe('Snapshots', () => {
    it('matches snapshot for private audit', () => {
      const { container } = render(
        <ShareAuditModal {...defaultProps} isPublic={false} />
      );
      expect(container.firstChild).toMatchSnapshot();
    });

    it('matches snapshot for public audit', () => {
      const { container } = render(
        <ShareAuditModal {...defaultProps} isPublic={true} />
      );
      expect(container.firstChild).toMatchSnapshot();
    });

    it('matches snapshot for loading state', async () => {
      const user = userEvent.setup();
      let resolvePublish: () => void;
      const onPublish = jest.fn().mockImplementation(() => 
        new Promise<void>(resolve => { resolvePublish = resolve; })
      );
      
      const { container } = render(
        <ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />
      );
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      expect(container.firstChild).toMatchSnapshot();
      
      resolvePublish!();
    });
  });
});
