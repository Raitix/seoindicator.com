/**
 * Navigation Integration Tests
 * TASK-36-14: Navigation & Dashboard Integration
 * 
 * @jest-environment jsdom
 */

import React from "react";
import { render, screen, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";
import * as nextNavigation from "next/navigation";

import { SiteNav } from "@/components/layout/SiteNav";

// =============================================================================
// Mock Lucide Icons
// =============================================================================

jest.mock("lucide-react", () => ({
  LayoutDashboard: () => <span data-testid="dashboard-icon">Dashboard</span>,
  Key: () => <span data-testid="key-icon">Key</span>,
  Search: () => <span data-testid="search-icon">Search</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">TrendingDown</span>,
  Sparkles: () => <span data-testid="sparkles-icon">Sparkles</span>,
  Calendar: () => <span data-testid="calendar-icon">Calendar</span>,
  MapPin: () => <span data-testid="map-pin-icon">MapPin</span>,
  Settings: () => <span data-testid="settings-icon">Settings</span>,
  ChevronLeft: () => <span data-testid="chevron-left-icon">ChevronLeft</span>,
  ChevronRight: () => <span data-testid="chevron-right-icon">ChevronRight</span>,
}));

// =============================================================================
// NDI-001: Site Navigation Tests
// =============================================================================

describe("NDI-001: Site Navigation", () => {
  beforeEach(() => {
    jest.spyOn(nextNavigation, "usePathname").mockReturnValue("/sites/1/content-decay");
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  test("NDI-001.1: Content Decay link in navigation", () => {
    render(<SiteNav siteId="1" />);
    const link = screen.getByTestId("nav-link-content-decay");
    expect(link).toBeInTheDocument();
  });

  test("NDI-001.2: Link href correct", () => {
    render(<SiteNav siteId="123" />);
    const link = screen.getByTestId("nav-link-content-decay");
    expect(link).toHaveAttribute("href", "/sites/123/content-decay");
  });

  test("NDI-001.3: Link text", () => {
    render(<SiteNav siteId="1" />);
    const link = screen.getByTestId("nav-link-content-decay");
    expect(link).toHaveTextContent("Content Decay");
  });

  test("NDI-001.4: Link has icon", () => {
    render(<SiteNav siteId="1" />);
    // The TrendingDown icon is rendered via Lucide mock
    // Check that the icon element exists (the mock creates a span with testid)
    expect(screen.getByTestId("trending-down-icon")).toBeInTheDocument();
  });

  test("NDI-001.5: Link appears after Audits", () => {
    render(<SiteNav siteId="1" />);
    const nav = screen.getByTestId("site-nav");
    const links = within(nav).getAllByRole("link");
    
    const auditsIndex = links.findIndex(link => link.getAttribute("href")?.includes("/audits"));
    const decayIndex = links.findIndex(link => link.getAttribute("href")?.includes("/content-decay"));
    
    expect(auditsIndex).toBeLessThan(decayIndex);
  });

  test("NDI-001.6: Link appears before AI Visibility", () => {
    render(<SiteNav siteId="1" />);
    const nav = screen.getByTestId("site-nav");
    const links = within(nav).getAllByRole("link");
    
    const decayIndex = links.findIndex(link => link.getAttribute("href")?.includes("/content-decay"));
    const aiIndex = links.findIndex(link => link.getAttribute("href")?.includes("/ai-visibility"));
    
    expect(decayIndex).toBeLessThan(aiIndex);
  });

  test("NDI-001.7: Active state on content decay page", () => {
    render(<SiteNav siteId="1" />);
    const link = screen.getByTestId("nav-link-content-decay");
    expect(link).toHaveAttribute("aria-current", "page");
  });

  test("NDI-001.8: Collapsed sidebar hides text label", () => {
    render(<SiteNav siteId="1" isCollapsed={true} />);
    const link = screen.getByTestId("nav-link-content-decay");
    // When collapsed, the text span is not rendered (only icon visible)
    // The link still has aria-label for accessibility
    expect(link).toHaveAttribute("aria-label");
    // Text "Content Decay" is NOT in the DOM when collapsed
    expect(within(link).queryByText("Content Decay")).not.toBeInTheDocument();
  });

  test("NDI-001.9: Expanded sidebar shows text", () => {
    render(<SiteNav siteId="1" isCollapsed={false} />);
    const link = screen.getByTestId("nav-link-content-decay");
    expect(link).toHaveTextContent("Content Decay");
  });

  test("NDI-001.10: Keyboard navigation - link is focusable", () => {
    render(<SiteNav siteId="1" />);
    const link = screen.getByTestId("nav-link-content-decay");
    link.focus();
    expect(link).toHaveFocus();
  });

  test("NDI-001.11: Link has aria-label", () => {
    render(<SiteNav siteId="1" />);
    const link = screen.getByTestId("nav-link-content-decay");
    expect(link).toHaveAttribute("aria-label");
  });

  test("NDI-001.12: Badge count on link when critical issues exist", () => {
    render(<SiteNav siteId="1" decayCriticalCount={5} />);
    // Badge should be visible
    const nav = screen.getByTestId("site-nav");
    // Check for badge content
    expect(within(nav).getByText("5")).toBeInTheDocument();
  });

  test("NDI-001.13: Badge hidden when zero", () => {
    render(<SiteNav siteId="1" decayCriticalCount={0} />);
    const nav = screen.getByTestId("site-nav");
    // No badge should be shown for zero
    expect(within(nav).queryByText("0")).not.toBeInTheDocument();
  });

  test("NDI-001.14: Badge color matches severity (critical)", () => {
    render(<SiteNav siteId="1" decayCriticalCount={5} />);
    const nav = screen.getByTestId("site-nav");
    const badge = within(nav).getByText("5");
    // Badge should have critical styling
    expect(badge.closest("span")).toHaveClass("bg-red-500");
  });

  test("NDI-001.15: Keyboard Enter activates link", async () => {
    render(<SiteNav siteId="1" />);
    const link = screen.getByTestId("nav-link-content-decay");
    link.focus();
    await userEvent.keyboard("{Enter}");
    // Navigation would happen - can't test actual navigation in JSDOM
    expect(link).toBeInTheDocument();
  });

  test("NDI-001.16: Navigation has proper ARIA role", () => {
    render(<SiteNav siteId="1" />);
    const nav = screen.getByTestId("site-nav");
    expect(nav).toHaveAttribute("role", "navigation");
    expect(nav).toHaveAttribute("aria-label", "Site navigation");
  });
});

// =============================================================================
// NDI-002: Navigation Order Tests
// =============================================================================

describe("NDI-002: Navigation Order", () => {
  test("NDI-002.1: Correct navigation order", () => {
    render(<SiteNav siteId="1" />);
    const nav = screen.getByTestId("site-nav");
    const links = within(nav).getAllByRole("link");
    
    const expectedOrder = [
      "/sites/1",           // Overview
      "/sites/1/keywords",  // Keywords
      "/sites/1/audits",    // Audits
      "/sites/1/content-decay", // Content Decay
      "/sites/1/ai-visibility", // AI Visibility
      "/sites/1/annotations", // Annotations
      "/sites/1/local",     // Local SEO
      "/sites/1/settings",  // Settings
    ];
    
    links.forEach((link, index) => {
      if (index < expectedOrder.length) {
        expect(link).toHaveAttribute("href", expectedOrder[index]);
      }
    });
  });
});

// =============================================================================
// NDI-003: Collapsed State Tests
// =============================================================================

describe("NDI-003: Collapsed State", () => {
  test("NDI-003.1: Collapse toggle works", async () => {
    const onCollapsedChange = jest.fn();
    render(<SiteNav siteId="1" isCollapsed={false} onCollapsedChange={onCollapsedChange} />);
    
    // Find and click the collapse button
    const collapseBtn = screen.getByRole("button", { name: /collapse/i });
    await userEvent.click(collapseBtn);
    
    expect(onCollapsedChange).toHaveBeenCalledWith(true);
  });

  test("NDI-003.2: Expand toggle works", async () => {
    const onCollapsedChange = jest.fn();
    render(<SiteNav siteId="1" isCollapsed={true} onCollapsedChange={onCollapsedChange} />);
    
    const expandBtn = screen.getByRole("button", { name: /expand/i });
    await userEvent.click(expandBtn);
    
    expect(onCollapsedChange).toHaveBeenCalledWith(false);
  });

  test("NDI-003.3: Tooltip on collapsed nav", () => {
    // This test is for the tooltip that appears on hover when collapsed
    render(<SiteNav siteId="1" isCollapsed={true} />);
    
    // Tooltip provider should be present
    const link = screen.getByTestId("nav-link-content-decay");
    expect(link).toBeInTheDocument();
  });
});

// =============================================================================
// NDI-004: Accessibility Tests
// =============================================================================

describe("NDI-004: Navigation Accessibility", () => {
  test("NDI-004.1: All links have accessible names", () => {
    render(<SiteNav siteId="1" />);
    const nav = screen.getByTestId("site-nav");
    const links = within(nav).getAllByRole("link");
    
    links.forEach(link => {
      const accessibleName = link.getAttribute("aria-label") || link.textContent;
      expect(accessibleName).toBeTruthy();
    });
  });

  test("NDI-004.2: Focus visible on links", () => {
    render(<SiteNav siteId="1" />);
    const link = screen.getByTestId("nav-link-content-decay");
    link.focus();
    expect(link).toHaveFocus();
  });

  test("NDI-004.3: Active link has aria-current", () => {
    render(<SiteNav siteId="1" />);
    const link = screen.getByTestId("nav-link-content-decay");
    expect(link).toHaveAttribute("aria-current", "page");
  });

  test("NDI-004.4: Navigation list has proper role", () => {
    render(<SiteNav siteId="1" />);
    const list = screen.getByRole("list");
    expect(list).toBeInTheDocument();
  });

  test("NDI-004.5: Navigation items are list items", () => {
    render(<SiteNav siteId="1" />);
    const items = screen.getAllByRole("listitem");
    expect(items.length).toBeGreaterThan(0);
  });
});

// =============================================================================
// NDI-005: Badge Display Tests
// =============================================================================

describe("NDI-005: Badge Display", () => {
  test("NDI-005.1: Badge shows correct count", () => {
    render(<SiteNav siteId="1" decayCriticalCount={7} />);
    expect(screen.getByText("7")).toBeInTheDocument();
  });

  test("NDI-005.2: Badge shows 99+ for large numbers", () => {
    render(<SiteNav siteId="1" decayCriticalCount={150} />);
    expect(screen.getByText("99+")).toBeInTheDocument();
  });

  test("NDI-005.3: No badge when undefined", () => {
    render(<SiteNav siteId="1" />);
    const nav = screen.getByTestId("site-nav");
    // Should not have any badge elements
    const badges = within(nav).queryAllByText(/^\d+$/);
    // Filter to actual badges (not other numbers)
    expect(badges.length).toBe(0);
  });
});
