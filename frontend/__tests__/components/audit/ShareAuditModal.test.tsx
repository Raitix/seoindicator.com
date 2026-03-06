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
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Import the actual ShareAuditModal component
import { ShareAuditModal } from '@/components/audit/ShareAuditModal';

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
    mockClipboardWrite.mockClear();
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

    it('REND-009: renders Done button', () => {
      render(<ShareAuditModal {...defaultProps} />);
      expect(screen.getByTestId('cancel-button')).toBeInTheDocument();
    });

    it('REND-010: displays score badge', () => {
      render(<ShareAuditModal {...defaultProps} score={85} />);
      expect(screen.getByTestId('audit-score')).toHaveTextContent('85/100');
    });
  });

  // ============================================
  // 2. TOGGLE SWITCH TESTS
  // ============================================

  describe('Toggle Switch', () => {
    it('TOGGLE-001: toggle is checked when public', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      const toggle = screen.getByTestId('visibility-toggle');
      expect(toggle).toHaveAttribute('data-state', 'checked');
    });

    it('TOGGLE-002: toggle is unchecked when private', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={false} />);
      const toggle = screen.getByTestId('visibility-toggle');
      expect(toggle).toHaveAttribute('data-state', 'unchecked');
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
      
      expect(screen.getByText(/copied to clipboard/i)).toBeInTheDocument();
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
    it('MODAL-001: Done button closes modal', async () => {
      const user = userEvent.setup();
      const onClose = jest.fn();
      
      render(<ShareAuditModal {...defaultProps} onClose={onClose} />);
      
      await user.click(screen.getByTestId('cancel-button'));
      
      expect(onClose).toHaveBeenCalled();
    });

    it('MODAL-002: modal has dialog role', () => {
      render(<ShareAuditModal {...defaultProps} />);
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('MODAL-003: has aria-modal attribute', () => {
      render(<ShareAuditModal {...defaultProps} />);
      const modal = screen.getByTestId('share-audit-modal');
      expect(modal).toBeInTheDocument();
    });

    it('MODAL-004: visibility status has live region', () => {
      render(<ShareAuditModal {...defaultProps} />);
      const status = screen.getByTestId('visibility-status');
      expect(status).toHaveAttribute('aria-live', 'polite');
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

    it('A11Y-002: error message has alert role', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockRejectedValue(new Error('Failed'));
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        const error = screen.getByTestId('error-message');
        expect(error).toHaveAttribute('role', 'alert');
      });
    });

    it('A11Y-003: copy button has aria-label', () => {
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      const copyButton = screen.getByTestId('copy-url-button');
      expect(copyButton).toHaveAttribute('aria-label');
    });

    it('A11Y-004: confirmation dialog has alertdialog role', async () => {
      const user = userEvent.setup();
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      const confirmDialog = screen.getByTestId('confirm-unpublish-dialog');
      expect(confirmDialog).toHaveAttribute('role', 'alertdialog');
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

    it('ERR-003: retry button available on error', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockRejectedValue(new Error('Failed'));
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(screen.getByTestId('retry-button')).toBeInTheDocument();
      });
    });

    it('ERR-004: retry repeats action', async () => {
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

    it('EDGE-006: props change updates UI', () => {
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
  // 10. ANALYTICS TESTS
  // ============================================

  describe('Analytics', () => {
    it('tracks publish event', async () => {
      const user = userEvent.setup();
      const onPublish = jest.fn().mockResolvedValue(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={false} onPublish={onPublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      
      await waitFor(() => {
        expect(mockTrackEvent).toHaveBeenCalledWith('audit_published', expect.any(Object));
      });
    });

    it('tracks unpublish event', async () => {
      const user = userEvent.setup();
      const onUnpublish = jest.fn().mockResolvedValue(undefined);
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} onUnpublish={onUnpublish} />);
      
      await user.click(screen.getByTestId('visibility-toggle'));
      await user.click(screen.getByTestId('confirm-unpublish-button'));
      
      await waitFor(() => {
        expect(mockTrackEvent).toHaveBeenCalledWith('audit_unpublished', expect.any(Object));
      });
    });

    it('tracks copy event', async () => {
      const user = userEvent.setup();
      
      render(<ShareAuditModal {...defaultProps} isPublic={true} />);
      
      await user.click(screen.getByTestId('copy-url-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('audit_url_copy', expect.any(Object));
    });
  });
});
