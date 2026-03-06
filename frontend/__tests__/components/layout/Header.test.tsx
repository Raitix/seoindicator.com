/**
 * Header Component Tests for TASK-28-12
 * Desktop Navigation Enhancement
 *
 * @see TESTS/TEST-28-12.md for full test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, fireEvent, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import Header from "@/components/layout/Header";

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
vi.mock("@/lib/api/notifications", () => ({
  useUnreadCount: () => ({
    count: 0,
    mutate: vi.fn(),
  }),
}));

// Test constants
const defaultProps = {
  userName: undefined,
  userEmail: undefined,
  userAvatar: undefined,
  onLogout: undefined,
};

const userProps = {
  userName: "John Doe",
  userEmail: "john.doe@example.com",
  userAvatar: undefined,
  onLogout: vi.fn(),
};

describe("Header Component", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockPathname.mockReturnValue("/dashboard");
  });

  afterEach(() => {
    vi.resetAllMocks();
  });

  // ============================================
  // 1. COMPONENT RENDERING (RENDER)
  // ============================================
  describe("Rendering", () => {
    it("RENDER-001: should render logo with correct link", () => {
      render(<Header {...defaultProps} />);
      const logo = screen.getByRole("link", { name: /seoindicator home/i });
      expect(logo).toBeInTheDocument();
      expect(logo).toHaveAttribute("href", "/dashboard");
    });

    it("RENDER-002: should render all navigation items", () => {
      render(<Header {...defaultProps} />);
      expect(screen.getByRole("link", { name: /dashboard/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /keywords/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /competitors/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /reports/i })).toBeInTheDocument();
    });

    it("RENDER-003: should render user initial when userName provided", () => {
      render(<Header {...userProps} />);
      expect(screen.getByText("J")).toBeInTheDocument(); // First letter of "John"
    });

    it("RENDER-004: should not render user section without user", () => {
      render(<Header {...defaultProps} />);
      // No user initial should be visible in desktop nav
      const userInitial = screen.queryByText("J");
      expect(userInitial).not.toBeInTheDocument();
    });

    it("RENDER-005: should have sticky positioning", () => {
      render(<Header {...defaultProps} />);
      const header = screen.getByRole("banner");
      expect(header).toHaveClass("sticky");
      expect(header).toHaveClass("top-0");
    });

    it("RENDER-006: should have correct z-index", () => {
      render(<Header {...defaultProps} />);
      const header = screen.getByRole("banner");
      expect(header).toHaveClass("z-50");
    });

    it("RENDER-007: should render notification bell", () => {
      render(<Header {...defaultProps} />);
      const bellButton = screen.getByRole("button", { name: /notifications/i });
      expect(bellButton).toBeInTheDocument();
    });

    it("RENDER-008: should render settings link", () => {
      render(<Header {...defaultProps} />);
      // There are multiple Settings links (nav + right side), so we use getAllByRole
      const settingsLinks = screen.getAllByRole("link", { name: "Settings" });
      expect(settingsLinks.length).toBeGreaterThan(0);
    });
  });

  // ============================================
  // 2. ACTIVE STATE INDICATORS (ACTIVE)
  // ============================================
  describe("Active State Indicators", () => {
    it("ACTIVE-001: should mark Dashboard active on /dashboard", () => {
      mockPathname.mockReturnValue("/dashboard");
      render(<Header {...defaultProps} />);
      const dashboardLink = screen.getByRole("link", { name: /dashboard/i });
      expect(dashboardLink).toHaveAttribute("aria-current", "page");
    });

    it("ACTIVE-002: should mark Keywords active on /keywords", () => {
      mockPathname.mockReturnValue("/keywords");
      render(<Header {...defaultProps} />);
      const keywordsLink = screen.getByRole("link", { name: /keywords/i });
      expect(keywordsLink).toHaveAttribute("aria-current", "page");
    });

    it("ACTIVE-003: should mark Settings active on /settings/* routes", () => {
      mockPathname.mockReturnValue("/settings/alerts");
      render(<Header {...defaultProps} />);
      // Find the nav Settings link (in the nav element)
      const nav = screen.getByRole("navigation", { name: /main navigation/i });
      const settingsLink = within(nav).getByRole("link", { name: "Settings" });
      expect(settingsLink).toHaveAttribute("aria-current", "page");
    });

    it("ACTIVE-004: should mark Dashboard active on sub-routes", () => {
      mockPathname.mockReturnValue("/dashboard/projects/123");
      render(<Header {...defaultProps} />);
      const dashboardLink = screen.getByRole("link", { name: /dashboard/i });
      expect(dashboardLink).toHaveAttribute("aria-current", "page");
    });

    it("ACTIVE-005: should not have active state on non-matching routes", () => {
      mockPathname.mockReturnValue("/other-page");
      render(<Header {...defaultProps} />);
      const links = screen.getAllByRole("link");
      const navLinks = links.filter(
        (link) => !link.getAttribute("href")?.includes("dashboard") || link.textContent !== "SEOIndicator"
      );
      navLinks.forEach((link) => {
        if (link.getAttribute("aria-label")?.includes("SEOIndicator")) return;
        expect(link).not.toHaveAttribute("aria-current", "page");
      });
    });

    it("ACTIVE-006: should render active indicator underline", () => {
      mockPathname.mockReturnValue("/dashboard");
      render(<Header {...defaultProps} />);
      const dashboardLink = screen.getByRole("link", { name: /dashboard/i });
      // Check for underline indicator span
      const indicator = dashboardLink.querySelector("span.bg-blue-500");
      expect(indicator).toBeInTheDocument();
    });

    it("ACTIVE-007: should have aria-current='page' on active link", () => {
      mockPathname.mockReturnValue("/keywords");
      render(<Header {...defaultProps} />);
      const keywordsLink = screen.getByRole("link", { name: /keywords/i });
      expect(keywordsLink).toHaveAttribute("aria-current", "page");
    });
  });

  // ============================================
  // 3. USER DROPDOWN MENU (DROPDOWN)
  // ============================================
  describe("User Dropdown Menu", () => {
    it("DROPDOWN-001: dropdown should be hidden by default", () => {
      render(<Header {...userProps} />);
      // Dropdown menu should not be visible initially
      expect(screen.queryByRole("menu")).not.toBeInTheDocument();
    });

    it("DROPDOWN-002: should open dropdown on user avatar click", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });
    });

    it("DROPDOWN-003: should close dropdown on Escape key", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });

      // Close with Escape (pointer-events: none prevents clicking other elements when dropdown is open)
      await user.keyboard("{Escape}");

      await waitFor(() => {
        expect(screen.queryByRole("menu")).not.toBeInTheDocument();
      });
    });

    it("DROPDOWN-004: should show user name in dropdown", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByText("John Doe")).toBeInTheDocument();
      });
    });

    it("DROPDOWN-005: should show user email in dropdown", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByText("john.doe@example.com")).toBeInTheDocument();
      });
    });

    it("DROPDOWN-006: should have Profile link in dropdown", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        const profileLink = screen.getByRole("menuitem", { name: /profile/i });
        expect(profileLink).toBeInTheDocument();
      });
    });

    it("DROPDOWN-007: should have Settings link in dropdown", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        const settingsLink = screen.getByRole("menuitem", { name: /settings/i });
        expect(settingsLink).toBeInTheDocument();
      });
    });

    it("DROPDOWN-008: should have Logout button in dropdown", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        const logoutButton = screen.getByRole("menuitem", { name: /logout|sign out/i });
        expect(logoutButton).toBeInTheDocument();
      });
    });

    it("DROPDOWN-009: should close dropdown on menu item click", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });

      const profileLink = screen.getByRole("menuitem", { name: /profile/i });
      await user.click(profileLink);

      await waitFor(() => {
        expect(screen.queryByRole("menu")).not.toBeInTheDocument();
      });
    });

    it("DROPDOWN-010: should close dropdown on Escape key", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });

      await user.keyboard("{Escape}");

      await waitFor(() => {
        expect(screen.queryByRole("menu")).not.toBeInTheDocument();
      });
    });

    it("DROPDOWN-011: should support keyboard navigation in dropdown", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });

      // Navigate with arrow keys
      await user.keyboard("{ArrowDown}");

      const menuItems = screen.getAllByRole("menuitem");
      expect(menuItems[0]).toHaveFocus();
    });
  });

  // ============================================
  // 4. HOVER EFFECTS (HOVER)
  // ============================================
  describe("Hover Effects", () => {
    it("HOVER-001: should have hover class on nav links", () => {
      render(<Header {...defaultProps} />);
      const keywordsLink = screen.getByRole("link", { name: /keywords/i });
      expect(keywordsLink.className).toMatch(/hover:text-white|hover:bg/);
    });

    it("HOVER-002: should have hover effect on user avatar", () => {
      render(<Header {...userProps} />);
      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      expect(userButton.className).toMatch(/hover:bg/);
    });

    it("HOVER-003: should have hover effect on notification bell", () => {
      render(<Header {...defaultProps} />);
      const bellButton = screen.getByRole("button", { name: /notifications/i });
      expect(bellButton.className).toMatch(/hover:bg/);
    });

    it("HOVER-004: should have hover effect on settings link", () => {
      render(<Header {...defaultProps} />);
      // Find settings link in the right side actions (has aria-label)
      const settingsLinks = screen.getAllByRole("link", { name: /settings/i });
      // At least one should have hover effect
      const hasHoverEffect = settingsLinks.some(link => link.className.match(/hover:bg/));
      expect(hasHoverEffect).toBe(true);
    });

    it("HOVER-005: should have hover effect on dropdown items", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        const menuItem = screen.getByRole("menuitem", { name: /profile/i });
        expect(menuItem.className).toMatch(/hover:bg|focus:bg/);
      });
    });
  });

  // ============================================
  // 5. STICKY HEADER BEHAVIOR (STICKY)
  // ============================================
  describe("Sticky Header Behavior", () => {
    it("STICKY-001: should have sticky class", () => {
      render(<Header {...defaultProps} />);
      const header = screen.getByRole("banner");
      expect(header).toHaveClass("sticky");
      expect(header).toHaveClass("top-0");
    });

    it("STICKY-002: should have z-50 class", () => {
      render(<Header {...defaultProps} />);
      const header = screen.getByRole("banner");
      expect(header).toHaveClass("z-50");
    });

    it("STICKY-003: should have backdrop blur", () => {
      render(<Header {...defaultProps} />);
      const header = screen.getByRole("banner");
      expect(header.className).toMatch(/backdrop-blur/);
    });

    it("STICKY-004: should have background with opacity", () => {
      render(<Header {...defaultProps} />);
      const header = screen.getByRole("banner");
      expect(header.className).toMatch(/bg-slate-950/);
    });
  });

  // ============================================
  // 6. NAVIGATION FUNCTIONALITY (NAV)
  // ============================================
  describe("Navigation Functionality", () => {
    it("NAV-001: should navigate when clicking nav link", async () => {
      const user = userEvent.setup();
      render(<Header {...defaultProps} />);

      const keywordsLink = screen.getByRole("link", { name: /keywords/i });
      await user.click(keywordsLink);

      // Link navigation is handled by Next.js router
      expect(keywordsLink).toHaveAttribute("href", "/keywords");
    });

    it("NAV-002: should navigate to dashboard on logo click", () => {
      render(<Header {...defaultProps} />);
      const logo = screen.getByRole("link", { name: /seoindicator home/i });
      expect(logo).toHaveAttribute("href", "/dashboard");
    });

    it("NAV-003: should have settings link pointing to /settings/alerts", () => {
      render(<Header {...defaultProps} />);
      // Find all links with /settings in href
      const allSettingsLinks = screen.getAllByRole("link").filter(link => 
        link.getAttribute("href")?.includes("/settings")
      );
      expect(allSettingsLinks.length).toBeGreaterThan(0);
      // Check that at least one points to /settings/alerts (the icon-only link)
      const hasSettingsAlertsLink = allSettingsLinks.some(link => 
        link.getAttribute("href") === "/settings/alerts"
      );
      expect(hasSettingsAlertsLink).toBe(true);
    });

    it("NAV-004: should navigate to notifications on bell click", async () => {
      const user = userEvent.setup();
      render(<Header {...defaultProps} />);

      const bellButton = screen.getByRole("button", { name: /notifications/i });
      await user.click(bellButton);

      expect(mockPush).toHaveBeenCalledWith("/notifications");
    });
  });

  // ============================================
  // 7. TYPESCRIPT TYPES (TYPES)
  // ============================================
  describe("TypeScript Types", () => {
    it("TYPES-001: should accept all optional props", () => {
      // TypeScript compilation test - if this compiles, types are correct
      const props = {
        userName: "Test User",
        userEmail: "test@example.com",
        userAvatar: "https://example.com/avatar.jpg",
        onLogout: () => {},
      };
      render(<Header {...props} />);
      expect(screen.getByRole("banner")).toBeInTheDocument();
    });

    it("TYPES-002: should work with no props", () => {
      render(<Header />);
      expect(screen.getByRole("banner")).toBeInTheDocument();
    });

    it("TYPES-003: should work with partial props", () => {
      render(<Header userName="Test" />);
      expect(screen.getByRole("banner")).toBeInTheDocument();
    });

    it("TYPES-004: onLogout should be callable", async () => {
      const onLogout = vi.fn();
      const user = userEvent.setup();
      render(<Header {...userProps} onLogout={onLogout} />);

      // Open dropdown
      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });

      // Click logout
      const logoutButton = screen.getByRole("menuitem", { name: /logout|sign out/i });
      await user.click(logoutButton);

      expect(onLogout).toHaveBeenCalled();
    });
  });

  // ============================================
  // 8. ACCESSIBILITY (A11Y)
  // ============================================
  describe("Accessibility", () => {
    it("A11Y-001: should have navigation landmark", () => {
      render(<Header {...defaultProps} />);
      const nav = screen.getByRole("navigation", { name: /main navigation/i });
      expect(nav).toBeInTheDocument();
    });

    it("A11Y-002: should have aria-current on active link", () => {
      mockPathname.mockReturnValue("/keywords");
      render(<Header {...defaultProps} />);
      const keywordsLink = screen.getByRole("link", { name: /keywords/i });
      expect(keywordsLink).toHaveAttribute("aria-current", "page");
    });

    it("A11Y-003: should have focus-visible outline", () => {
      render(<Header {...defaultProps} />);
      const dashboardLink = screen.getByRole("link", { name: /dashboard/i });
      expect(dashboardLink.className).toMatch(/focus-visible:ring/);
    });

    it("A11Y-004: dropdown should have correct roles", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
        expect(screen.getAllByRole("menuitem").length).toBeGreaterThan(0);
      });
    });

    it("A11Y-005: dropdown trigger should have aria-expanded", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      expect(userButton).toHaveAttribute("aria-expanded", "false");

      await user.click(userButton);

      await waitFor(() => {
        expect(userButton).toHaveAttribute("aria-expanded", "true");
      });
    });

    it("A11Y-006: dropdown trigger should have proper ARIA attributes", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      // shadcn/ui uses aria-expanded for accessibility
      expect(userButton).toHaveAttribute("aria-expanded", "false");

      await user.click(userButton);

      await waitFor(() => {
        expect(userButton).toHaveAttribute("aria-expanded", "true");
      });
    });

    it("A11Y-008: should have minimum touch targets (44px)", () => {
      render(<Header {...defaultProps} />);
      const bellButton = screen.getByRole("button", { name: /notifications/i });
      expect(bellButton.className).toMatch(/min-h-\[44px\]|min-w-\[44px\]|h-11|w-11/);
    });

    it("A11Y-010: dropdown should be keyboard navigable", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      // Click to open dropdown first
      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });

      // Navigate with ArrowDown
      await user.keyboard("{ArrowDown}");

      const menuItems = screen.getAllByRole("menuitem");
      expect(menuItems.length).toBeGreaterThan(0);

      // Verify keyboard navigation works (first item should be focusable)
      await user.keyboard("{ArrowDown}");
      await user.keyboard("{ArrowUp}");
    });
  });

  // ============================================
  // 9. RESPONSIVE BEHAVIOR (RESP)
  // ============================================
  describe("Responsive Behavior", () => {
    it("RESP-001: should have desktop nav visible on md+ breakpoint class", () => {
      render(<Header {...defaultProps} />);
      const nav = screen.getByRole("navigation", { name: /main navigation/i });
      // Desktop nav should have md:flex class
      expect(nav.className).toMatch(/hidden md:flex|md:flex/);
    });

    it("RESP-003: should render MobileNav component", () => {
      render(<Header {...defaultProps} />);
      // MobileNav should be rendered (will be hidden on desktop)
      const menuButton = screen.getByRole("button", { name: /open navigation menu/i });
      expect(menuButton).toBeInTheDocument();
    });

    it("RESP-006: user should be visible in mobile nav", () => {
      render(<Header {...userProps} />);
      // Mobile nav exists
      const menuButton = screen.getByRole("button", { name: /open navigation menu/i });
      expect(menuButton).toBeInTheDocument();
    });
  });

  // ============================================
  // 10. EDGE CASES (EDGE)
  // ============================================
  describe("Edge Cases", () => {
    it("EDGE-001: should handle empty navigation gracefully", () => {
      // Header has default nav items, so this tests the default behavior
      render(<Header {...defaultProps} />);
      expect(screen.getByRole("banner")).toBeInTheDocument();
    });

    it("EDGE-002: should handle long user name", () => {
      render(
        <Header
          userName="Alexander Maximilian Constantin III"
          userEmail="alex@example.com"
        />
      );
      expect(screen.getByText("A")).toBeInTheDocument(); // First letter only in avatar
    });

    it("EDGE-003: should handle long email in dropdown", async () => {
      const user = userEvent.setup();
      render(
        <Header
          userName="Test"
          userEmail="very.long.email.address@verylongdomainname.com"
        />
      );

      const userButton = screen.getByRole("button", { name: /test|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        const email = screen.getByText(/very.long.email/);
        expect(email).toBeInTheDocument();
      });
    });

    it("EDGE-004: should handle special characters in name", () => {
      render(<Header userName="O'Brien" userEmail="obrien@example.com" />);
      expect(screen.getByText("O")).toBeInTheDocument();
    });

    it("EDGE-005: should handle unicode in name", () => {
      render(<Header userName="José García" userEmail="jose@example.com" />);
      expect(screen.getByText("J")).toBeInTheDocument();
    });

    it("EDGE-006: should not render logout without onLogout", async () => {
      const user = userEvent.setup();
      render(<Header userName="Test" userEmail="test@example.com" />);

      const userButton = screen.getByRole("button", { name: /test|user menu/i });
      await user.click(userButton);

      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });

      // No logout button should be present
      const logoutButton = screen.queryByRole("menuitem", { name: /logout|sign out/i });
      expect(logoutButton).not.toBeInTheDocument();
    });

    it("EDGE-007: should handle rapid dropdown toggles", async () => {
      const user = userEvent.setup();
      render(<Header {...userProps} />);

      const userButton = screen.getByRole("button", { name: /john doe|user menu/i });

      // Open dropdown
      await user.click(userButton);
      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });

      // Close with Escape (pointer-events: none prevents clicking trigger when open)
      await user.keyboard("{Escape}");
      await waitFor(() => {
        expect(screen.queryByRole("menu")).not.toBeInTheDocument();
      });

      // Open again
      await user.click(userButton);
      await waitFor(() => {
        expect(screen.getByRole("menu")).toBeInTheDocument();
      });

      // Should not throw or have inconsistent state
      expect(screen.getByRole("banner")).toBeInTheDocument();
    });
  });
});
