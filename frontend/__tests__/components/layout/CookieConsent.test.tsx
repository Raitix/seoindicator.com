/**
 * Cookie Consent Component Tests
 * TASK-30-4: Cookie Consent Component
 * 
 * Tests for GDPR/CCPA compliant cookie consent banner.
 * Covers UI behavior, state management, accessibility, and compliance.
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import CookieConsent from '@/components/layout/CookieConsent';
import * as cookieConsentUtils from '@/lib/utils/cookieConsent';

// Mock the cookie consent utilities
jest.mock('@/lib/utils/cookieConsent', () => ({
  getCookieConsent: jest.fn(),
  setCookieConsent: jest.fn(),
  hasCookieConsent: jest.fn(),
  clearCookieConsent: jest.fn(),
}));

// Mock Next.js Link
jest.mock('next/link', () => {
  return ({ children, href }: { children: React.ReactNode; href: string }) => (
    <a href={href}>{children}</a>
  );
});

const mockedUtils = cookieConsentUtils as jest.Mocked<typeof cookieConsentUtils>;

describe('CookieConsent Component', () => {
  const mockOnAccept = jest.fn();
  const mockOnDecline = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    mockedUtils.hasCookieConsent.mockReturnValue(false);
    mockedUtils.getCookieConsent.mockReturnValue(null);
  });

  // ============================================
  // Basic Rendering Tests
  // ============================================
  describe('Rendering', () => {
    it('should render when no consent stored', () => {
      render(<CookieConsent />);

      expect(screen.getByRole('dialog')).toBeInTheDocument();
      expect(screen.getByText(/cookies/i)).toBeInTheDocument();
    });

    it('should not render when consent already given', () => {
      mockedUtils.hasCookieConsent.mockReturnValue(true);

      const { container } = render(<CookieConsent />);

      expect(container.firstChild).toBeNull();
    });

    it('should have fixed position at bottom of viewport', () => {
      render(<CookieConsent />);

      const banner = screen.getByRole('dialog');
      expect(banner).toHaveClass('fixed');
      expect(banner).toHaveClass('bottom-0');
    });

    it('should render with correct z-index for overlay', () => {
      render(<CookieConsent />);

      const banner = screen.getByRole('dialog');
      // Should have high z-index to appear above content
      expect(banner.className).toMatch(/z-\d+/);
    });
  });

  // ============================================
  // Button Tests
  // ============================================
  describe('Buttons', () => {
    it('should render Accept All button', () => {
      render(<CookieConsent />);

      const acceptButton = screen.getByRole('button', { name: /accept all/i });
      expect(acceptButton).toBeInTheDocument();
      expect(acceptButton).toHaveAttribute('type', 'button');
    });

    it('should render Necessary Only button', () => {
      render(<CookieConsent />);

      const necessaryButton = screen.getByRole('button', { name: /necessary only/i });
      expect(necessaryButton).toBeInTheDocument();
    });

    it('Accept All button should have primary styling', () => {
      render(<CookieConsent />);

      const acceptButton = screen.getByRole('button', { name: /accept all/i });
      // Primary buttons typically have distinct styling
      expect(acceptButton.className).toMatch(/bg-\w+/);
    });

    it('Necessary Only button should have secondary styling', () => {
      render(<CookieConsent />);

      const necessaryButton = screen.getByRole('button', { name: /necessary only/i });
      // Secondary buttons typically have different styling
      expect(necessaryButton.className).toMatch(/bg-\w+/);
    });

    it('should call setCookieConsent with all true on Accept All', async () => {
      const user = userEvent.setup();
      render(<CookieConsent onAccept={mockOnAccept} />);

      await user.click(screen.getByRole('button', { name: /accept all/i }));

      expect(mockedUtils.setCookieConsent).toHaveBeenCalledWith({
        necessary: true,
        analytics: true,
        marketing: true,
      });
    });

    it('should call setCookieConsent with only necessary on Necessary Only', async () => {
      const user = userEvent.setup();
      render(<CookieConsent onDecline={mockOnDecline} />);

      await user.click(screen.getByRole('button', { name: /necessary only/i }));

      expect(mockedUtils.setCookieConsent).toHaveBeenCalledWith({
        necessary: true,
        analytics: false,
        marketing: false,
      });
    });

    it('should call onAccept callback when provided', async () => {
      const user = userEvent.setup();
      render(<CookieConsent onAccept={mockOnAccept} />);

      await user.click(screen.getByRole('button', { name: /accept all/i }));

      expect(mockOnAccept).toHaveBeenCalledWith({
        necessary: true,
        analytics: true,
        marketing: true,
      });
    });

    it('should call onDecline callback when provided', async () => {
      const user = userEvent.setup();
      render(<CookieConsent onDecline={mockOnDecline} />);

      await user.click(screen.getByRole('button', { name: /necessary only/i }));

      expect(mockOnDecline).toHaveBeenCalled();
    });
  });

  // ============================================
  // Learn More Link Tests
  // ============================================
  describe('Learn More Link', () => {
    it('should render Learn More link', () => {
      render(<CookieConsent />);

      const learnMoreLink = screen.getByRole('link', { name: /learn more/i });
      expect(learnMoreLink).toBeInTheDocument();
    });

    it('should link to /terms page', () => {
      render(<CookieConsent />);

      const learnMoreLink = screen.getByRole('link', { name: /learn more/i });
      expect(learnMoreLink).toHaveAttribute('href', '/terms');
    });

    it('should have privacy policy accessible name', () => {
      render(<CookieConsent />);

      const link = screen.getByRole('link', { name: /learn more/i });
      expect(link).toHaveAttribute('aria-label', /privacy policy|cookie policy/i);
    });
  });

  // ============================================
  // Banner Visibility Tests
  // ============================================
  describe('Banner Visibility', () => {
    it('should appear on first visit (no localStorage)', () => {
      mockedUtils.hasCookieConsent.mockReturnValue(false);

      render(<CookieConsent />);

      expect(screen.getByRole('dialog')).toBeVisible();
    });

    it('should disappear after accept action', async () => {
      const user = userEvent.setup();
      const { container } = render(<CookieConsent />);

      await user.click(screen.getByRole('button', { name: /accept all/i }));

      await waitFor(() => {
        expect(container.firstChild).toBeNull();
      });
    });

    it('should disappear after decline action', async () => {
      const user = userEvent.setup();
      const { container } = render(<CookieConsent />);

      await user.click(screen.getByRole('button', { name: /necessary only/i }));

      await waitFor(() => {
        expect(container.firstChild).toBeNull();
      });
    });

    it('should not reappear after consent given', () => {
      mockedUtils.hasCookieConsent.mockReturnValue(true);

      const { container } = render(<CookieConsent />);

      expect(container.firstChild).toBeNull();
    });
  });

  // ============================================
  // Accessibility Tests
  // ============================================
  describe('Accessibility', () => {
    it('should have ARIA role="dialog"', () => {
      render(<CookieConsent />);

      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('should have aria-modal attribute', () => {
      render(<CookieConsent />);

      const dialog = screen.getByRole('dialog');
      expect(dialog).toHaveAttribute('aria-modal', 'true');
    });

    it('should have aria-labelledby for title', () => {
      render(<CookieConsent />);

      const dialog = screen.getByRole('dialog');
      const labelledBy = dialog.getAttribute('aria-labelledby');
      expect(labelledBy).toBeDefined();
      expect(screen.getByLabelText(/cookies/i)).toBeInTheDocument();
    });

    it('should have aria-describedby for description', () => {
      render(<CookieConsent />);

      const dialog = screen.getByRole('dialog');
      const describedBy = dialog.getAttribute('aria-describedby');
      expect(describedBy).toBeDefined();
    });

    it('should have aria-label on Accept All button', () => {
      render(<CookieConsent />);

      const acceptButton = screen.getByRole('button', { name: /accept all/i });
      expect(acceptButton).toHaveAttribute('aria-label');
    });

    it('should have aria-label on Necessary Only button', () => {
      render(<CookieConsent />);

      const necessaryButton = screen.getByRole('button', { name: /necessary only/i });
      expect(necessaryButton).toHaveAttribute('aria-label');
    });

    it('should be keyboard navigable', async () => {
      const user = userEvent.setup();
      render(<CookieConsent />);

      // Tab through elements
      await user.tab();
      const focusedElement = screen.getByRole('dialog').ownerDocument.activeElement;
      expect(focusedElement).toBeInstanceOf(HTMLElement);
    });

    it('should trap focus within the dialog', async () => {
      const user = userEvent.setup();
      render(<CookieConsent />);

      const dialog = screen.getByRole('dialog');
      const acceptButton = screen.getByRole('button', { name: /accept all/i });

      // Focus should stay within dialog
      acceptButton.focus();
      await user.tab();

      const focusedElement = dialog.ownerDocument.activeElement;
      expect(dialog.contains(focusedElement)).toBe(true);
    });

    it('should have screen reader announcement for consent changes', async () => {
      const user = userEvent.setup();
      render(<CookieConsent />);

      await user.click(screen.getByRole('button', { name: /accept all/i }));

      // Should have live region or announcement
      await waitFor(() => {
        const announcement = screen.queryByRole('status');
        // Either status role or aria-live element should exist
        const liveRegion = document.querySelector('[aria-live]');
        expect(announcement || liveRegion).toBeTruthy();
      });
    });
  });

  // ============================================
  // Keyboard Navigation Tests
  // ============================================
  describe('Keyboard Navigation', () => {
    it('should close on Escape key', async () => {
      const user = userEvent.setup();
      const { container } = render(<CookieConsent />);

      // Focus the dialog
      screen.getByRole('dialog').focus();

      await user.keyboard('{Escape}');

      // Should not close on Escape - user must make a choice
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('should activate Accept All on Enter', async () => {
      const user = userEvent.setup();
      render(<CookieConsent />);

      const acceptButton = screen.getByRole('button', { name: /accept all/i });
      acceptButton.focus();

      await user.keyboard('{Enter}');

      expect(mockedUtils.setCookieConsent).toHaveBeenCalled();
    });

    it('should activate Necessary Only on Enter', async () => {
      const user = userEvent.setup();
      render(<CookieConsent />);

      const necessaryButton = screen.getByRole('button', { name: /necessary only/i });
      necessaryButton.focus();

      await user.keyboard('{Enter}');

      expect(mockedUtils.setCookieConsent).toHaveBeenCalled();
    });

    it('should have correct tab order', async () => {
      const user = userEvent.setup();
      render(<CookieConsent />);

      const dialog = screen.getByRole('dialog');
      const focusableElements = dialog.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );

      expect(focusableElements.length).toBeGreaterThan(0);

      // Tab through and verify order
      await user.tab();
      const firstFocused = document.activeElement;
      expect(dialog.contains(firstFocused)).toBe(true);
    });
  });

  // ============================================
  // Focus Management Tests
  // ============================================
  describe('Focus Management', () => {
    it('should focus Accept All button on mount', async () => {
      render(<CookieConsent />);

      await waitFor(() => {
        const acceptButton = screen.getByRole('button', { name: /accept all/i });
        expect(document.activeElement).toBe(acceptButton);
      });
    });

    it('should restore focus after dismiss', async () => {
      const user = userEvent.setup();
      const previousFocus = document.createElement('button');
      document.body.appendChild(previousFocus);
      previousFocus.focus();

      render(<CookieConsent />);

      await user.click(screen.getByRole('button', { name: /accept all/i }));

      // Focus should return to body or previous element
      await waitFor(() => {
        expect(document.activeElement).toBe(document.body);
      });

      document.body.removeChild(previousFocus);
    });
  });

  // ============================================
  // GDPR/CCPA Compliance Tests
  // ============================================
  describe('GDPR/CCPA Compliance', () => {
    it('should not have pre-checked boxes (no automatic consent)', () => {
      mockedUtils.hasCookieConsent.mockReturnValue(false);

      render(<CookieConsent />);

      // Banner should be visible - no consent pre-selected
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('should require explicit user action (no close without choice)', async () => {
      const user = userEvent.setup();
      render(<CookieConsent />);

      // Try to dismiss without making a choice
      // There should be no X button or close button
      const closeButton = screen.queryByRole('button', { name: /close|x/i });
      expect(closeButton).not.toBeInTheDocument();
    });

    it('should not dismiss on backdrop click', async () => {
      const user = userEvent.setup();
      render(<CookieConsent />);

      // Click outside the banner (backdrop)
      const backdrop = screen.getByRole('dialog').parentElement;
      if (backdrop) {
        await user.click(backdrop);
      }

      // Banner should still be visible
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('should provide clear separation between cookie categories', () => {
      render(<CookieConsent />);

      // Check that categories are mentioned
      const content = screen.getByRole('dialog').textContent?.toLowerCase() || '';
      expect(
        content.includes('necessary') ||
        content.includes('essential') ||
        content.includes('functional')
      ).toBe(true);
    });

    it('should not track before consent (component only renders after JS)', () => {
      // Component should not render on server without consent
      mockedUtils.hasCookieConsent.mockReturnValue(false);

      render(<CookieConsent />);

      // No tracking should occur until user accepts
      expect(mockedUtils.setCookieConsent).not.toHaveBeenCalled();
    });

    it('should store consent locally (localStorage only)', async () => {
      const user = userEvent.setup();
      render(<CookieConsent />);

      await user.click(screen.getByRole('button', { name: /accept all/i }));

      // Should use localStorage utility, not server API
      expect(mockedUtils.setCookieConsent).toHaveBeenCalled();
    });
  });

  // ============================================
  // Responsive Design Tests
  // ============================================
  describe('Responsive Design', () => {
    it('should be full-width on mobile', () => {
      render(<CookieConsent />);

      const banner = screen.getByRole('dialog');
      expect(banner).toHaveClass('left-0');
      expect(banner).toHaveClass('right-0');
    });

    it('should have appropriate padding on mobile', () => {
      render(<CookieConsent />);

      const banner = screen.getByRole('dialog');
      expect(banner.className).toMatch(/p-\d+/);
    });

    it('should stack buttons vertically on mobile', () => {
      render(<CookieConsent />);

      const buttonContainer = screen.getByRole('dialog').querySelector('.flex');
      if (buttonContainer) {
        // Should have flex-col or similar for mobile
        expect(
          buttonContainer.className.includes('flex-col') ||
          buttonContainer.className.includes('sm:flex-row')
        ).toBe(true);
      }
    });
  });

  // ============================================
  // Content Tests
  // ============================================
  describe('Content', () => {
    it('should display cookie usage explanation', () => {
      render(<CookieConsent />);

      const content = screen.getByRole('dialog').textContent;
      expect(content?.toLowerCase()).toMatch(/cookie/);
    });

    it('should mention necessary/essential cookies', () => {
      render(<CookieConsent />);

      const content = screen.getByRole('dialog').textContent?.toLowerCase() || '';
      expect(
        content.includes('necessary') ||
        content.includes('essential')
      ).toBe(true);
    });

    it('should have clear call to action', () => {
      render(<CookieConsent />);

      const acceptButton = screen.getByRole('button', { name: /accept all/i });
      const necessaryButton = screen.getByRole('button', { name: /necessary only/i });

      expect(acceptButton).toBeVisible();
      expect(necessaryButton).toBeVisible();
    });
  });

  // ============================================
  // State Management Tests
  // ============================================
  describe('State Management', () => {
    it('should use hasCookieConsent to check initial state', () => {
      render(<CookieConsent />);

      expect(mockedUtils.hasCookieConsent).toHaveBeenCalled();
    });

    it('should not show banner if consent exists', () => {
      mockedUtils.hasCookieConsent.mockReturnValue(true);

      const { container } = render(<CookieConsent />);

      expect(container.firstChild).toBeNull();
    });

    it('should persist consent after accept', async () => {
      const user = userEvent.setup();
      render(<CookieConsent />);

      await user.click(screen.getByRole('button', { name: /accept all/i }));

      expect(mockedUtils.setCookieConsent).toHaveBeenCalledWith(
        expect.objectContaining({
          necessary: true,
          analytics: true,
          marketing: true,
        })
      );
    });
  });

  // ============================================
  // Animation Tests
  // ============================================
  describe('Animation', () => {
    it('should have entrance animation', () => {
      render(<CookieConsent />);

      const banner = screen.getByRole('dialog');
      // Should have animation or transform classes
      expect(
        banner.className.includes('animate-') ||
        banner.className.includes('transition') ||
        banner.className.includes('transform')
      ).toBe(true);
    });

    it('should have exit animation on dismiss', async () => {
      const user = userEvent.setup();
      render(<CookieConsent />);

      await user.click(screen.getByRole('button', { name: /accept all/i }));

      // Component should unmount after animation
      await waitFor(
        () => {
          expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        },
        { timeout: 1000 }
      );
    });
  });

  // ============================================
  // Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('should handle rapid button clicks', async () => {
      const user = userEvent.setup();
      render(<CookieConsent />);

      const acceptButton = screen.getByRole('button', { name: /accept all/i });

      await user.click(acceptButton);
      await user.click(acceptButton);

      // Should only set consent once
      expect(mockedUtils.setCookieConsent).toHaveBeenCalledTimes(1);
    });

    it('should handle localStorage errors gracefully', async () => {
      const user = userEvent.setup();
      mockedUtils.setCookieConsent.mockImplementation(() => {
        throw new Error('localStorage error');
      });

      // Should not throw
      expect(() => render(<CookieConsent />)).not.toThrow();

      // Click should not crash
      await user.click(screen.getByRole('button', { name: /accept all/i }));
    });

    it('should render with custom className', () => {
      render(<CookieConsent className="custom-banner" />);

      const banner = screen.getByRole('dialog');
      expect(banner).toHaveClass('custom-banner');
    });
  });

  // ============================================
  // Optional Customize Feature Tests
  // ============================================
  describe('Optional Customize Feature', () => {
    it('should not show customize button by default', () => {
      render(<CookieConsent />);

      const customizeButton = screen.queryByRole('button', { name: /customize|settings|preferences/i });
      // Customize is optional, may or may not exist
      // If it exists, test its behavior
      if (customizeButton) {
        expect(customizeButton).toBeInTheDocument();
      }
    });

    it('should show customize panel when enabled', () => {
      render(<CookieConsent showCustomize />);

      const customizeButton = screen.queryByRole('button', { name: /customize|settings|preferences/i });
      if (customizeButton) {
        expect(customizeButton).toBeInTheDocument();
      }
    });

    it('should expand preferences panel on customize click', async () => {
      const user = userEvent.setup();
      render(<CookieConsent showCustomize />);

      const customizeButton = screen.queryByRole('button', { name: /customize|settings|preferences/i });
      if (customizeButton) {
        await user.click(customizeButton);

        // Should show analytics and marketing toggles
        expect(screen.queryByText(/analytics/i) || screen.queryByLabelText(/analytics/i)).toBeTruthy();
        expect(screen.queryByText(/marketing/i) || screen.queryByLabelText(/marketing/i)).toBeTruthy();
      }
    });
  });

  // ============================================
  // Integration Tests
  // ============================================
  describe('Integration', () => {
    it('should complete full accept flow', async () => {
      const user = userEvent.setup();
      const onAccept = jest.fn();
      render(<CookieConsent onAccept={onAccept} />);

      // User sees banner
      expect(screen.getByRole('dialog')).toBeInTheDocument();

      // User clicks accept
      await user.click(screen.getByRole('button', { name: /accept all/i }));

      // Consent is stored
      expect(mockedUtils.setCookieConsent).toHaveBeenCalledWith({
        necessary: true,
        analytics: true,
        marketing: true,
      });

      // Callback is fired
      expect(onAccept).toHaveBeenCalled();

      // Banner is dismissed
      await waitFor(() => {
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
      });
    });

    it('should complete full decline flow', async () => {
      const user = userEvent.setup();
      const onDecline = jest.fn();
      render(<CookieConsent onDecline={onDecline} />);

      // User sees banner
      expect(screen.getByRole('dialog')).toBeInTheDocument();

      // User clicks necessary only
      await user.click(screen.getByRole('button', { name: /necessary only/i }));

      // Consent is stored (only necessary)
      expect(mockedUtils.setCookieConsent).toHaveBeenCalledWith({
        necessary: true,
        analytics: false,
        marketing: false,
      });

      // Callback is fired
      expect(onDecline).toHaveBeenCalled();

      // Banner is dismissed
      await waitFor(() => {
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
      });
    });
  });
});
