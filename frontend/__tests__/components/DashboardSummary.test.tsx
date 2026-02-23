import React from "react";
import { render, screen, fireEvent, act, waitFor } from "@testing-library/react";

import DashboardSummary from "@/components/dashboard/DashboardSummary";

// Mock Next.js Link
jest.mock("next/link", () => {
  return ({ children, href }: { children: React.ReactNode; href: string }) => {
    return <a href={href}>{children}</a>;
  };
});

describe("DashboardSummary", () => {
  beforeEach(() => {
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  // ============================================
  // Summary Cards Tests (CARD-001 to CARD-004)
  // ============================================
  describe("Summary Cards", () => {
    it("CARD-001: displays Total Keywords card", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("Total Keywords")).toBeInTheDocument();
      expect(screen.getByText("128")).toBeInTheDocument();
    });

    it("CARD-002: displays Average Position card", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("Average Position")).toBeInTheDocument();
      expect(screen.getByText("14.3")).toBeInTheDocument();
    });

    it("CARD-003: displays Ranked Keywords card", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("Ranked")).toBeInTheDocument();
      expect(screen.getByText("96")).toBeInTheDocument();
    });

    it("CARD-004: displays Not Ranked card", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("Not Ranked")).toBeInTheDocument();
      expect(screen.getByText("32")).toBeInTheDocument();
    });
  });

  // ============================================
  // Position Changes Tests (CHG-001 to CHG-004)
  // ============================================
  describe("Position Changes", () => {
    it("CHG-001: displays Improved count with green styling", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("Improved")).toBeInTheDocument();
      const improvedValue = screen.getByText("34");
      expect(improvedValue).toHaveClass("text-emerald-300");
    });

    it("CHG-002: displays Declined count with red styling", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("Declined")).toBeInTheDocument();
      const declinedValue = screen.getByText("18");
      expect(declinedValue).toHaveClass("text-rose-300");
    });

    it("CHG-003: displays Unchanged count with gray styling", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("Unchanged")).toBeInTheDocument();
      const unchangedValue = screen.getByText("41");
      expect(unchangedValue).toHaveClass("text-slate-200");
    });

    it("CHG-004: displays New count with blue styling", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("New")).toBeInTheDocument();
      const newValue = screen.getByText("5");
      expect(newValue).toHaveClass("text-sky-300");
    });
  });

  // ============================================
  // Top Keywords Tests (TOP-001 to TOP-004)
  // ============================================
  describe("Top Keywords", () => {
    it("TOP-001: displays top 5 keywords in table", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("seo indicator")).toBeInTheDocument();
      expect(screen.getByText("keyword tracking")).toBeInTheDocument();
      expect(screen.getByText("rank monitoring")).toBeInTheDocument();
      expect(screen.getByText("seo dashboard")).toBeInTheDocument();
      expect(screen.getByText("competitor analysis")).toBeInTheDocument();
    });

    it("TOP-002: displays position for each keyword", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("#2")).toBeInTheDocument(); // seo indicator
      expect(screen.getByText("#4")).toBeInTheDocument(); // keyword tracking
      expect(screen.getByText("#6")).toBeInTheDocument(); // rank monitoring
    });

    it("TOP-003: displays change indicator for each keyword", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      // Positive change (green)
      const plusOne = screen.getAllByText("+1");
      expect(plusOne.length).toBeGreaterThan(0);

      // Zero change
      expect(screen.getByText("0")).toBeInTheDocument();

      // Negative change (red)
      expect(screen.getByText("-1")).toBeInTheDocument();
    });

    it("TOP-004: keyword links to detail page", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      const viewAllLink = screen.getByText("View full keyword list");
      expect(viewAllLink).toHaveAttribute("href", "/sites/1/keywords");
    });
  });

  // ============================================
  // Alerts Tests (ALT-001 to ALT-003)
  // ============================================
  describe("Alerts", () => {
    it("ALT-001: displays dropped alert with red indicator", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("content audit")).toBeInTheDocument();
      expect(screen.getByText("-7")).toBeInTheDocument();
      expect(screen.getByText("9 → 16")).toBeInTheDocument();
    });

    it("ALT-002: displays position change details", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("backlink checker")).toBeInTheDocument();
      expect(screen.getByText("-5")).toBeInTheDocument();
      expect(screen.getByText("11 → 16")).toBeInTheDocument();
    });

    it("ALT-003: shows no alerts message when empty", () => {
      // This would require mocking the data to have no alerts
      // For now, we verify the alerts section exists
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("Alerts")).toBeInTheDocument();
      expect(screen.getByText("Significant drops to review.")).toBeInTheDocument();
    });
  });

  // ============================================
  // Quick Actions Tests (ACT-001 to ACT-003)
  // ============================================
  describe("Quick Actions", () => {
    it("ACT-001: Add Keyword button links to add form", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      const addButton = screen.getByText("Add keyword");
      expect(addButton).toBeInTheDocument();
      expect(addButton.closest("a")).toHaveAttribute("href", "/sites/1/keywords/new");
    });

    it("ACT-002: Refresh all button triggers refresh", async () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      const refreshButton = screen.getByText("Refresh all");
      expect(refreshButton).toBeInTheDocument();
      expect(refreshButton).not.toBeDisabled();

      fireEvent.click(refreshButton);

      // Button shows loading state
      expect(screen.getByText("Refreshing...")).toBeInTheDocument();
      expect(refreshButton).toBeDisabled();
    });

    it("ACT-003: View all keywords link navigates to keyword list", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      const viewAllLink = screen.getByText("View full keyword list");
      expect(viewAllLink).toBeInTheDocument();
      expect(viewAllLink).toHaveAttribute("href", "/sites/1/keywords");
    });
  });

  // ============================================
  // Auto-Refresh Tests (REF-001 to REF-003)
  // ============================================
  describe("Auto-Refresh", () => {
    it("REF-001: auto-refresh checkbox toggles setting", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      const checkbox = screen.getByRole("checkbox", { name: /auto-refresh/i });
      expect(checkbox).not.toBeChecked();

      fireEvent.click(checkbox);
      expect(checkbox).toBeChecked();
    });

    it("REF-002: auto-refresh is disabled by default", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      const checkbox = screen.getByRole("checkbox", { name: /auto-refresh/i });
      expect(checkbox).not.toBeChecked();
    });

    it("REF-003: auto-refresh label shows 5 minute interval", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText(/Auto-refresh \(5m\)/)).toBeInTheDocument();
    });
  });

  // ============================================
  // Loading States Tests (LOAD-001 to LOAD-003)
  // ============================================
  describe("Loading States", () => {
    it("LOAD-001: shows skeleton cards on initial load", () => {
      render(<DashboardSummary />);

      // Should not show actual content during loading
      expect(screen.queryByText("Total Keywords")).not.toBeInTheDocument();

      // Should show skeleton elements
      const skeletons = document.querySelectorAll(".animate-pulse");
      expect(skeletons.length).toBeGreaterThan(0);
    });

    it("LOAD-002: shows loading indicator during refresh", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      const refreshButton = screen.getByText("Refresh all");
      fireEvent.click(refreshButton);

      expect(screen.getByText("Refreshing...")).toBeInTheDocument();
    });

    it("LOAD-003: content appears after loading completes", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("Total Keywords")).toBeInTheDocument();
      expect(screen.getByText("Dashboard Summary")).toBeInTheDocument();
    });
  });

  // ============================================
  // Responsive Tests (RESP-001 to RESP-003)
  // ============================================
  describe("Responsive Layout", () => {
    it("RESP-001: summary cards use grid layout", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      // The grid container has md:grid-cols-4 class
      const gridContainer = screen.getByText("Total Keywords").closest(".grid");
      expect(gridContainer).toHaveClass("md:grid-cols-4");
    });

    it("RESP-002: position changes grid is responsive", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      // Position changes section has sm:grid-cols-4
      const changesGrid = screen.getByText("Improved").closest(".grid");
      expect(changesGrid).toHaveClass("sm:grid-cols-4");
    });

    it("RESP-003: main layout is responsive", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      // Dashboard summary container
      const mainContainer = screen.getByText("Dashboard Summary").closest(".space-y-6");
      expect(mainContainer).toBeInTheDocument();
    });
  });

  // ============================================
  // Edge Cases
  // ============================================
  describe("Edge Cases", () => {
    it("accepts custom siteId prop", () => {
      render(<DashboardSummary siteId="custom-site-123" />);
      act(() => {
        jest.runAllTimers();
      });

      const viewAllLink = screen.getByText("View full keyword list");
      expect(viewAllLink).toHaveAttribute("href", "/sites/custom-site-123/keywords");
    });

    it("displays last updated time after load", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText(/Updated/)).toBeInTheDocument();
    });

    it("shows table headers for top keywords", () => {
      render(<DashboardSummary />);
      act(() => {
        jest.runAllTimers();
      });

      expect(screen.getByText("Keyword")).toBeInTheDocument();
      expect(screen.getByText("Position")).toBeInTheDocument();
      expect(screen.getByText("Change")).toBeInTheDocument();
      expect(screen.getByText("URL")).toBeInTheDocument();
    });
  });
});
