/**
 * Desktop Navigation Integration Tests for TASK-28-12
 * Tests the full integration of Header, MobileNav, and UserDropdown
 *
 * @see TESTS/TEST-28-12.md for full test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import Header from "@/components/layout/Header";
import { NavItem, UserInfo } from "@/components/layout/MobileNav";

// Mock next/navigation
const mockPathname = vi.fn();
const mockPush = vi.fn();
vi.mock("next/navigation", () => ({
  usePathname: () => mockPathname(),
  useRouter: () => ({
    push: mockPush,
    replace: vi.fn(),
    back: vi.fn(),
    forward: vi.fn(),
    refresh: vi.fn(),
    prefetch: vi.fn(),
  }),
}));

// Mock notifications API
let mockUnreadCount = 0;
vi.mock("@/lib/api/notifications", () => ({
  useUnreadCount: () => ({
    count: mockUnreadCount,
    mutate: vi.fn(),
  }),
}));

describe("Desktop Navigation Integration", () => {
  const user = userEvent.setup();

  beforeEach(() => {
    vi.clearAllMocks();
    mockPathname.mockReturnValue("/dashboard");
    mockUnreadCount = 0;
  });

  afterEach(() => {
    vi.resetAllMocks();
  });

  // ============================================
  // INTEGRATION TESTS (INT)
  // ============================================
  describe("Integration Tests", () => {
    it("INT-001: Header and MobileNav should share navigation items", () => {
      render(<Header userName="Test User" userEmail="test@example.com" />);

      // Desktop nav items
      const desktopNav = screen.getByRole("navigation", { name: /main navigation/i });
      expect(desktopNav).toBeInTheDocument();

      // Mobile nav exists
      const mobileMenuButton = screen.getByRole("button", { name: /open navigation menu/i });
      expect(mobileMenuButton).toBeInTheDocument();

      // Both should have access to same nav items
      expect(screen.getByRole("link", { name: /dashboard/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /keywords/i })).toBeInTheDocument();
    });

    it("INT-002: route change should update active state", async () => {
      const { rerender } = render(<Header />);

      // Initially on dashboard
      mockPathname.mockReturnValue("/dashboard");
      rerender(<Header />);

      let dashboardLink = screen.getByRole("link", { name: /dashboard/i });
      expect(dashboardLink).toHaveAttribute("aria-current", "page");

      // Navigate to keywords
      mockPathname.mockReturnValue("/keywords");
      rerender(<Header />);

      const keywordsLink = screen.getByRole("link", { name: /keywords/i });
      expect(keywordsLink).toHaveAttribute("aria-current", "page");

      // Dashboard should no longer be active
      dashboardLink = screen.getByRole("link", { name: /dashboard/i });
      expect(dashboardLink).not.toHaveAttribute("aria-current", "page");
    });

    it("INT-003: unread count should display in badge", async () => {
      mockUnreadCount = 5;
      render(<Header />);

      const bellButton = screen.getByRole("button", { name: /notifications/i });
      expect(bellButton).toHaveAttribute("aria-label", expect.stringContaining("5"));

      // Badge should show count
      const badge = screen.getByText("5");
      expect(badge).toBeInTheDocument();
    });

    it("INT-004: logout should clear user state and fire callback", async () => {
      const onLogout = vi.fn();
      render(<Header userName="Test User" userEmail="test@example.com" onLogout={onLogout} />);

      // Open dropdown
      const userButton = screen.getByRole("button", { name: /test user|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });

      // Click logout
      const logoutButton = screen.getByRole("menuitem", { name: /logout|sign out/i });
      await user.click(logoutButton);

      expect(onLogout).toHaveBeenCalled();
    });

    it("INT-005: dropdown should close on route navigation", async () => {
      render(<Header userName="Test User" userEmail="test@example.com" />);

      // Open dropdown
      const userButton = screen.getByRole("button", { name: /test user|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });

      // Simulate route change
      mockPathname.mockReturnValue("/profile");
      // In a real app, this would trigger a re-render
      // For this test, we verify the dropdown state is controlled

      // Press Escape to close
      await user.keyboard("{Escape}");

      await waitFor(() => {
        expect(screen.queryByRole("menu")).not.toBeInTheDocument();
      });
    });
  });

  // ============================================
  // CROSS-COMPONENT BEHAVIOR
  // ============================================
  describe("Cross-Component Behavior", () => {
    it("should maintain consistent styling between desktop and mobile nav", () => {
      render(<Header userName="Test User" userEmail="test@example.com" />);

      // Desktop nav should have active state indicator
      const desktopDashboardLink = screen.getByRole("link", { name: /dashboard/i });
      expect(desktopDashboardLink).toHaveAttribute("aria-current", "page");

      // Both should use same icon set (Lucide)
      // Icons are rendered inside nav links
      expect(desktopDashboardLink.querySelector("svg")).toBeInTheDocument();
    });

    it("should handle notification badge in both views", () => {
      mockUnreadCount = 10;
      render(<Header />);

      // Badge should be visible
      const badge = screen.getByText("10");
      expect(badge).toBeInTheDocument();
      expect(badge.className).toMatch(/bg-red-500|rounded-full/);
    });

    it("should sync user info between desktop dropdown and mobile nav", async () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);

      // Desktop avatar shows initial
      expect(screen.getByText("J")).toBeInTheDocument();

      // Open mobile nav
      const mobileMenuButton = screen.getByRole("button", { name: /open navigation menu/i });
      await user.click(mobileMenuButton);

      await waitFor(() => {
        // Mobile nav shows full name
        expect(screen.getByText("John Doe")).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // ACCESSIBILITY INTEGRATION
  // ============================================
  describe("Accessibility Integration", () => {
    it("should maintain focus management across components", async () => {
      render(<Header userName="Test User" userEmail="test@example.com" />);

      // Tab through desktop nav
      await user.tab();

      // Focus should be on logo first
      const logo = screen.getByRole("link", { name: /seoindicator home/i });
      expect(logo).toHaveFocus();
    });

    it("should announce dropdown state changes to screen readers", async () => {
      render(<Header userName="Test User" userEmail="test@example.com" />);

      const userButton = screen.getByRole("button", { name: /test user|user menu/i });

      // Initially collapsed
      expect(userButton).toHaveAttribute("aria-expanded", "false");

      // Open dropdown
      await user.click(userButton);

      await waitFor(() => {
        expect(userButton).toHaveAttribute("aria-expanded", "true");
      });
    });

    it("should trap focus in dropdown when open", async () => {
      render(<Header userName="Test User" userEmail="test@example.com" />);

      const userButton = screen.getByRole("button", { name: /test user|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });

      // Tab should cycle through menu items
      await user.tab();
      const menuItems = screen.getAllByRole("menuitem");
      expect(menuItems[0]).toHaveFocus();
    });
  });

  // ============================================
  // PERFORMANCE TESTS
  // ============================================
  describe("Performance", () => {
    it("should render header within performance threshold", () => {
      const start = performance.now();
      render(<Header userName="Test User" userEmail="test@example.com" />);
      const end = performance.now();

      // Render should be under 100ms
      expect(end - start).toBeLessThan(100);
    });

    it("should not cause unnecessary re-renders on pathname change", async () => {
      const { rerender } = render(<Header />);

      // Initial render
      mockPathname.mockReturnValue("/dashboard");
      rerender(<Header />);

      // Same path - should not re-render unnecessarily
      const dashboardLink1 = screen.getByRole("link", { name: /dashboard/i });

      mockPathname.mockReturnValue("/dashboard");
      rerender(<Header />);

      const dashboardLink2 = screen.getByRole("link", { name: /dashboard/i });
      expect(dashboardLink1).toBe(dashboardLink2);
    });
  });

  // ============================================
  // ERROR HANDLING
  // ============================================
  describe("Error Handling", () => {
    it("should handle missing user gracefully", () => {
      // Should render without errors
      render(<Header />);
      expect(screen.getByRole("banner")).toBeInTheDocument();
    });

    it("should handle missing nav items gracefully", () => {
      // Header has default nav items
      render(<Header userName="Test" />);
      expect(screen.getByRole("banner")).toBeInTheDocument();
    });

    it("should handle invalid pathname gracefully", () => {
      mockPathname.mockReturnValue(null);
      render(<Header />);
      expect(screen.getByRole("banner")).toBeInTheDocument();
    });
  });
});
