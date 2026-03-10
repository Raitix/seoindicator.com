/**
 * @jest-environment jsdom
 */
import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import { DecaySummaryCards } from "@/components/content-decay/DecaySummaryCards";
import type { DecaySummaryData } from "@/lib/types/content-decay";

// Mock Lucide icons
vi.mock("lucide-react", () => ({
  AlertTriangle: () => <span data-testid="alert-triangle-icon">AlertTriangle</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">TrendingDown</span>,
  TrendingUp: () => <span data-testid="trending-up-icon">TrendingUp</span>,
  RefreshCw: () => <span data-testid="refresh-cw-icon">RefreshCw</span>,
  FileText: () => <span data-testid="file-text-icon">FileText</span>,
}));

// Mock Skeleton component
vi.mock("@/components/ui/skeleton", () => ({
  Skeleton: ({ className }: { className?: string }) => (
    <div data-testid="skeleton" className={className} />
  ),
}));

describe("DecaySummaryCards", () => {
  const mockSummary: DecaySummaryData = {
    totalCandidates: 45,
    criticalCount: 8,
    highCount: 12,
    mediumCount: 15,
    lowCount: 10,
    estimatedLostClicks: 1234,
    recoveryUpside: 5678,
    recoveredCount: 12,
    lastScannedAt: "2024-03-10T12:00:00Z",
  };

  describe("Rendering with data", () => {
    it("renders all 5 cards with correct values", () => {
      render(<DecaySummaryCards summary={mockSummary} />);
      
      expect(screen.getByText("Total Candidates")).toBeInTheDocument();
      expect(screen.getByText("Critical")).toBeInTheDocument();
      expect(screen.getByText("Lost Clicks")).toBeInTheDocument();
      expect(screen.getByText("Recovery Upside")).toBeInTheDocument();
      expect(screen.getByText("Recovered")).toBeInTheDocument();
    });

    it("shows total candidates count", () => {
      render(<DecaySummaryCards summary={mockSummary} />);
      expect(screen.getByText("45")).toBeInTheDocument();
    });

    it("shows critical count", () => {
      render(<DecaySummaryCards summary={mockSummary} />);
      expect(screen.getByText("8")).toBeInTheDocument();
    });

    it("shows formatted lost clicks (K suffix)", () => {
      render(<DecaySummaryCards summary={mockSummary} />);
      expect(screen.getByText("1.2K")).toBeInTheDocument();
    });

    it("shows formatted recovery upside (K suffix)", () => {
      render(<DecaySummaryCards summary={mockSummary} />);
      expect(screen.getByText("5.7K")).toBeInTheDocument();
    });

    it("shows recovered pages count", () => {
      render(<DecaySummaryCards summary={mockSummary} />);
      expect(screen.getByText("12")).toBeInTheDocument();
    });
  });

  describe("Number formatting", () => {
    it("formats millions with M suffix", () => {
      const largeSummary: DecaySummaryData = {
        ...mockSummary,
        estimatedLostClicks: 1500000,
        recoveryUpside: 2500000,
      };
      render(<DecaySummaryCards summary={largeSummary} />);
      
      expect(screen.getByText("1.5M")).toBeInTheDocument();
      expect(screen.getByText("2.5M")).toBeInTheDocument();
    });

    it("displays small numbers without suffix", () => {
      const smallSummary: DecaySummaryData = {
        ...mockSummary,
        estimatedLostClicks: 500,
        recoveryUpside: 800,
      };
      render(<DecaySummaryCards summary={smallSummary} />);
      
      expect(screen.getByText("500")).toBeInTheDocument();
      expect(screen.getByText("800")).toBeInTheDocument();
    });
  });

  describe("Loading state", () => {
    it("shows loading skeleton when isLoading is true", () => {
      render(<DecaySummaryCards summary={mockSummary} isLoading />);
      
      const skeletons = screen.getAllByTestId("skeleton");
      expect(skeletons.length).toBe(5); // One skeleton per card
    });

    it("does not show skeleton when isLoading is false", () => {
      render(<DecaySummaryCards summary={mockSummary} isLoading={false} />);
      
      expect(screen.queryByTestId("skeleton")).not.toBeInTheDocument();
    });
  });

  describe("Empty/Zero data handling", () => {
    it("handles all zeros gracefully", () => {
      const emptySummary: DecaySummaryData = {
        totalCandidates: 0,
        criticalCount: 0,
        highCount: 0,
        mediumCount: 0,
        lowCount: 0,
        estimatedLostClicks: 0,
        recoveryUpside: 0,
        recoveredCount: 0,
        lastScannedAt: null,
      };
      render(<DecaySummaryCards summary={emptySummary} />);
      
      const zeros = screen.getAllByText("0");
      expect(zeros.length).toBeGreaterThanOrEqual(3);
    });

    it("handles null summary gracefully", () => {
      render(<DecaySummaryCards summary={null} />);
      
      // Should show zeros for all values
      const zeros = screen.getAllByText("0");
      expect(zeros.length).toBeGreaterThanOrEqual(3);
    });
  });

  describe("Custom className", () => {
    it("applies custom className", () => {
      const { container } = render(
        <DecaySummaryCards summary={mockSummary} className="custom-class" />
      );
      expect(container.firstChild).toHaveClass("custom-class");
    });
  });

  describe("Accessibility", () => {
    it("has aria-label on each card", () => {
      render(<DecaySummaryCards summary={mockSummary} />);
      
      const cards = screen.getAllByRole("article");
      expect(cards.length).toBe(5);
    });

    it("has descriptive aria-labels for each metric", () => {
      render(<DecaySummaryCards summary={mockSummary} />);
      
      expect(screen.getByLabelText(/Total candidates: 45/)).toBeInTheDocument();
      expect(screen.getByLabelText(/Critical severity: 8/)).toBeInTheDocument();
      expect(screen.getByLabelText(/Estimated lost clicks/)).toBeInTheDocument();
      expect(screen.getByLabelText(/Potential recovery upside/)).toBeInTheDocument();
      expect(screen.getByLabelText(/Successfully recovered pages: 12/)).toBeInTheDocument();
    });

    it("has region role for the container", () => {
      const { container } = render(<DecaySummaryCards summary={mockSummary} />);
      
      const region = container.querySelector('[role="region"]');
      expect(region).toHaveAttribute("aria-label", "Content decay summary statistics");
    });
  });

  describe("Icon indicators", () => {
    it("shows correct icons for each card", () => {
      render(<DecaySummaryCards summary={mockSummary} />);
      
      expect(screen.getByTestId("file-text-icon")).toBeInTheDocument();
      expect(screen.getByTestId("alert-triangle-icon")).toBeInTheDocument();
      expect(screen.getByTestId("trending-down-icon")).toBeInTheDocument();
      expect(screen.getByTestId("trending-up-icon")).toBeInTheDocument();
      expect(screen.getByTestId("refresh-cw-icon")).toBeInTheDocument();
    });

    it("shows alert icon for critical count", () => {
      render(<DecaySummaryCards summary={mockSummary} />);
      expect(screen.getByTestId("alert-triangle-icon")).toBeInTheDocument();
    });
  });

  describe("Critical count highlighting", () => {
    it("shows critical card when criticalCount > 0", () => {
      render(<DecaySummaryCards summary={mockSummary} />);
      expect(screen.getByText("8")).toBeInTheDocument();
    });

    it("shows zero critical when criticalCount is 0", () => {
      const noCritical: DecaySummaryData = { ...mockSummary, criticalCount: 0 };
      render(<DecaySummaryCards summary={noCritical} />);
      expect(screen.getByText("0")).toBeInTheDocument();
    });
  });

  describe("Grid layout", () => {
    it("has responsive grid classes", () => {
      const { container } = render(<DecaySummaryCards summary={mockSummary} />);
      
      const grid = container.firstChild;
      expect(grid).toHaveClass("grid");
      expect(grid).toHaveClass("gap-4");
    });
  });
});
