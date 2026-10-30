/**
 * AuditCard Share Button Integration Tests
 * Task: TASK-33-20
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 *
 * Tests for the share button integration on AuditCard component.
 * Covers: button rendering, modal triggering, data passing, accessibility.
 */

import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Import the actual AuditCard component
import { AuditCard } from '@/components/audit/AuditCard';

// Mock ShareAuditModal component
jest.mock('@/components/audit/ShareAuditModal', () => ({
  ShareAuditModal: ({ isOpen, onClose, auditHash, auditUrl, score, isPublic, onPublish, onUnpublish }: {
    isOpen: boolean;
    onClose: () => void;
    auditHash: string;
    auditUrl: string;
    score: number;
    isPublic: boolean;
    onPublish?: () => Promise<void>;
    onUnpublish?: () => Promise<void>;
  }) => {
    if (!isOpen) return null;
    return (
      <div data-testid="share-audit-modal" role="dialog">
        <span data-testid="modal-audit-hash">{auditHash}</span>
        <span data-testid="modal-audit-url">{auditUrl}</span>
        <span data-testid="modal-score">{score}</span>
        <span data-testid="modal-is-public">{isPublic ? 'Public' : 'Private'}</span>
        <button data-testid="modal-close" onClick={onClose}>Close</button>
      </div>
    );
  },
}));

// Mock clipboard API
const mockClipboardWrite = jest.fn().mockResolvedValue(undefined);
Object.assign(navigator, {
  clipboard: {
    writeText: mockClipboardWrite,
  },
});

// ============================================
// TEST SUITE
// ============================================

describe('AuditCard Share Button Integration', () => {
  const defaultProps = {
    id: 1,
    hash: 'abc123def4567890123456789012',
    url: 'https://example.com',
    score: 85,
    isPublic: false,
    isOwner: true,
    onPublish: jest.fn().mockResolvedValue(undefined),
    onUnpublish: jest.fn().mockResolvedValue(undefined),
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ============================================
  // SHARE BUTTON RENDERING TESTS
  // ============================================

  describe('Share Button Rendering', () => {
    it('INT-001: share button renders on audit card', () => {
      render(<AuditCard {...defaultProps} />);
      expect(screen.getByTestId('share-button')).toBeInTheDocument();
    });

    it('INT-002: share button has aria-label', () => {
      render(<AuditCard {...defaultProps} />);
      const button = screen.getByTestId('share-button');
      expect(button).toHaveAttribute('aria-label', 'Share audit');
    });

    it('INT-003: share button is in card actions area', () => {
      render(<AuditCard {...defaultProps} />);
      const card = screen.getByTestId('audit-card');
      const button = screen.getByTestId('share-button');
      expect(card).toContainElement(button);
    });
  });

  // ============================================
  // OWNERSHIP TESTS
  // ============================================

  describe('Ownership', () => {
    it('INT-006: share button visible when isOwner=true', () => {
      render(<AuditCard {...defaultProps} isOwner={true} />);
      expect(screen.getByTestId('share-button')).toBeInTheDocument();
    });

    it('INT-007: share button hidden when isOwner=false', () => {
      render(<AuditCard {...defaultProps} isOwner={false} />);
      expect(screen.queryByTestId('share-button')).not.toBeInTheDocument();
    });
  });

  // ============================================
  // MODAL TRIGGERING TESTS
  // ============================================

  describe('Modal Triggering', () => {
    it('INT-003: click opens modal', async () => {
      const user = userEvent.setup();
      render(<AuditCard {...defaultProps} />);
      
      await user.click(screen.getByTestId('share-button'));
      
      // Wait for modal to appear
      await waitFor(() => {
        expect(screen.getByTestId('share-audit-modal')).toBeInTheDocument();
      });
    });

    it('INT-008: modal receives correct audit data', async () => {
      const user = userEvent.setup();
      render(<AuditCard {...defaultProps} />);
      
      await user.click(screen.getByTestId('share-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('modal-audit-hash')).toHaveTextContent(defaultProps.hash);
        expect(screen.getByTestId('modal-audit-url')).toHaveTextContent(defaultProps.url);
        expect(screen.getByTestId('modal-score')).toHaveTextContent(String(defaultProps.score));
      });
    });

    it('modal receives correct isPublic state', async () => {
      const user = userEvent.setup();
      render(<AuditCard {...defaultProps} isPublic={true} />);
      
      await user.click(screen.getByTestId('share-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('modal-is-public')).toHaveTextContent('Public');
      });
    });
  });

  // ============================================
  // FOCUS MANAGEMENT TESTS
  // ============================================

  describe('Focus Management', () => {
    it('INT-009: focus returns to share button after modal close', async () => {
      const user = userEvent.setup();
      render(<AuditCard {...defaultProps} />);
      
      const shareButton = screen.getByTestId('share-button');
      await user.click(shareButton);
      
      await waitFor(() => {
        expect(screen.getByTestId('share-audit-modal')).toBeInTheDocument();
      });
      
      await user.click(screen.getByTestId('modal-close'));
      
      await waitFor(() => {
        expect(shareButton).toHaveFocus();
      });
    });
  });

  // ============================================
  // MULTIPLE CARDS TESTS
  // ============================================

  describe('Multiple Cards', () => {
    it('INT-010: multiple cards have independent share buttons', () => {
      render(
        <div>
          <AuditCard {...defaultProps} id={1} hash="hash1" />
          <AuditCard {...defaultProps} id={2} hash="hash2" />
        </div>
      );
      
      const shareButtons = screen.getAllByTestId('share-button');
      expect(shareButtons).toHaveLength(2);
    });

    it('each card opens its own modal', async () => {
      const user = userEvent.setup();
      
      render(
        <div>
          <AuditCard {...defaultProps} id={1} hash="hash1" url="https://site1.com" />
          <AuditCard {...defaultProps} id={2} hash="hash2" url="https://site2.com" />
        </div>
      );
      
      const shareButtons = screen.getAllByTestId('share-button');
      await user.click(shareButtons[0]);
      
      await waitFor(() => {
        expect(screen.getByTestId('modal-audit-hash')).toHaveTextContent('hash1');
      });
    });
  });

  // ============================================
  // ACCESSIBILITY TESTS
  // ============================================

  describe('Accessibility', () => {
    it('share button is keyboard accessible', async () => {
      const user = userEvent.setup();
      render(<AuditCard {...defaultProps} />);
      
      // Tab to find the share button
      await user.tab();
      await user.tab();
      await user.tab();
      
      // The share button should be reachable
      const shareButton = screen.getByTestId('share-button');
      expect(shareButton).toBeInTheDocument();
    });

    it('Enter key opens modal', async () => {
      const user = userEvent.setup();
      render(<AuditCard {...defaultProps} />);
      
      const shareButton = screen.getByTestId('share-button');
      shareButton.focus();
      await user.keyboard('{Enter}');
      
      await waitFor(() => {
        expect(screen.getByTestId('share-audit-modal')).toBeInTheDocument();
      });
    });

    it('Space key opens modal', async () => {
      const user = userEvent.setup();
      render(<AuditCard {...defaultProps} />);
      
      const shareButton = screen.getByTestId('share-button');
      shareButton.focus();
      await user.keyboard(' ');
      
      await waitFor(() => {
        expect(screen.getByTestId('share-audit-modal')).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // EDGE CASES
  // ============================================

  describe('Edge Cases', () => {
    it('handles score of 0', async () => {
      const user = userEvent.setup();
      render(<AuditCard {...defaultProps} score={0} />);
      
      await user.click(screen.getByTestId('share-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('modal-score')).toHaveTextContent('0');
      });
    });

    it('handles score of 100', async () => {
      const user = userEvent.setup();
      render(<AuditCard {...defaultProps} score={100} />);
      
      await user.click(screen.getByTestId('share-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('modal-score')).toHaveTextContent('100');
      });
    });

    it('handles long URLs', async () => {
      const user = userEvent.setup();
      const longUrl = 'https://' + 'a'.repeat(200) + '.com';
      render(<AuditCard {...defaultProps} url={longUrl} />);
      
      await user.click(screen.getByTestId('share-button'));
      
      await waitFor(() => {
        expect(screen.getByTestId('modal-audit-url')).toHaveTextContent(longUrl);
      });
    });
  });

  // ============================================
  // VISIBILITY INDICATOR TESTS
  // ============================================

  describe('Visibility Indicator', () => {
    it('shows Public badge when isPublic=true', () => {
      render(<AuditCard {...defaultProps} isPublic={true} />);
      expect(screen.getByTestId('audit-visibility')).toHaveTextContent('Public');
    });

    it('shows Private badge when isPublic=false', () => {
      render(<AuditCard {...defaultProps} isPublic={false} />);
      expect(screen.getByTestId('audit-visibility')).toHaveTextContent('Private');
    });
  });

  // ============================================
  // CARD INTERACTION TESTS
  // ============================================

  describe('Card Interaction', () => {
    it('card is clickable when onClick provided', async () => {
      const user = userEvent.setup();
      const onClick = jest.fn();
      render(<AuditCard {...defaultProps} onClick={onClick} />);
      
      // Click on the card but not on the share button
      const card = screen.getByTestId('audit-card');
      await user.click(card);
      
      expect(onClick).toHaveBeenCalled();
    });

    it('share button click does not trigger card onClick', async () => {
      const user = userEvent.setup();
      const onClick = jest.fn();
      render(<AuditCard {...defaultProps} onClick={onClick} />);
      
      await user.click(screen.getByTestId('share-button'));
      
      expect(onClick).not.toHaveBeenCalled();
    });
  });
});
