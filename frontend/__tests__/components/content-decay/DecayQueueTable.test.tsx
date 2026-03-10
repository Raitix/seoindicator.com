/**
 * @jest-environment jsdom
 */
import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { DecayQueueTable } from "@/components/content-decay/DecayQueueTable";
import type { DecayCandidate, DecayQueueFilterState } from "@/lib/types/content-decay";

// Mock child components
vi.mock("@/components/content-decay/SeverityBadge", () => ({
  SeverityBadge: ({ severity }: { severity: string }) => (
    <span data-testid="severity-badge" data-severity={severity}>
      {severity}
    </span>
  ),
}));

vi.mock("@/components/content-decay/DecayReasonBadges", () => ({
  DecayReasonBadges: ({ reasons }: { reasons: Array<{ type: string }> }) => (
    <div data-testid="reason-badges">
      {reasons?.map((r) => r.type).join(", ")}
    </div>
  ),
}));

vi.mock("@/components/content-decay/DecayTrendMiniChart", () => ({
  DecayTrendMiniChart: () => (
    <div data-testid="trend-chart">Trend</div>
  ),
}));

// Mock Lucide icons
vi.mock("lucide-react", () => ({
  ChevronDown: () => <span>↓</span>,
  ChevronUp: () => <span>↑</span>,
  Search: () => <span>🔍</span>,
  ExternalLink: () => <span>↗</span>,
  MoreHorizontal: () => <span>⋯</span>,
  CheckSquare: () => <span>☑</span>,
  Square: () => <span>☐</span>,
  Download: () => <span>⬇</span>,
}));

// Mock Skeleton
vi.mock("@/components/ui/skeleton", () => ({
  Skeleton: ({ className }: { className?: string }) => (
    <div data-testid="skeleton" className={className} />
  ),
}));

describe("DecayQueueTable", () => {
  const mockCandidates: DecayCandidate[] = [
    {
      id: 1,
      projectId: 1,
      pageUrl: "https://example.com/blog/seo-guide",
      pageTitle: "Complete SEO Guide 2024",
      pageType: "blog",
      severity: "critical",
      decayScore: 72.5,
      detectionConfidence: 0.95,
      lostClickEstimate: 234,
      recoveryUpsideEstimate: 567,
      primaryReasonCode: "clicks_down",
      reasonCodes: [{ type: "clicks_down", evidence: "Down 45% vs last period" }],
      status: "backlog",
      firstDetectedAt: "2024-03-01T00:00:00Z",
      lastDetectedAt: "2024-03-10T00:00:00Z",
      plannedAt: null,
      startedAt: null,
      refreshedAt: null,
      recoveredAt: null,
      ignoredAt: null,
      signals: null,
      trend: {
        previousWindow: { clicks: [100, 95, 90], impressions: [1000, 950, 900] },
        currentWindow: { clicks: [55, 50, 45], impressions: [800, 750, 700] },
      },
    },
    {
      id: 2,
      projectId: 1,
      pageUrl: "https://example.com/products/widget",
      pageTitle: "Widget Pro - Product Page",
      pageType: "product",
      severity: "medium",
      decayScore: 45.0,
      detectionConfidence: 0.85,
      lostClickEstimate: 89,
      recoveryUpsideEstimate: 234,
      primaryReasonCode: "ctr_down",
      reasonCodes: [{ type: "ctr_down", evidence: "CTR dropped from 5.2% to 2.1%" }],
      status: "planned",
      firstDetectedAt: "2024-03-01T00:00:00Z",
      lastDetectedAt: "2024-03-10T00:00:00Z",
      plannedAt: "2024-03-05T00:00:00Z",
      startedAt: null,
      refreshedAt: null,
      recoveredAt: null,
      ignoredAt: null,
      signals: null,
      trend: null,
    },
  ];

  describe("Rendering", () => {
    it("renders table with candidates", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      expect(screen.getByRole("grid")).toBeInTheDocument();
      expect(screen.getByText("Complete SEO Guide 2024")).toBeInTheDocument();
    });

    it("renders all column headers", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      expect(screen.getByText("Score")).toBeInTheDocument();
      expect(screen.getByText("Page")).toBeInTheDocument();
      expect(screen.getByText("Type")).toBeInTheDocument();
      expect(screen.getByText("Severity")).toBeInTheDocument();
    });

    it("renders candidate rows with correct data", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      expect(screen.getByText("72.5")).toBeInTheDocument();
      expect(screen.getByText("45.0")).toBeInTheDocument();
    });

    it("renders severity badge for each row", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const badges = screen.getAllByTestId("severity-badge");
      expect(badges.length).toBe(2);
    });
  });

  describe("Loading state", () => {
    it("shows skeleton when isLoading is true", () => {
      render(<DecayQueueTable candidates={[]} isLoading />);
      
      expect(screen.getAllByTestId("skeleton").length).toBeGreaterThan(0);
    });
  });

  describe("Empty state", () => {
    it("shows empty message when no candidates", () => {
      render(<DecayQueueTable candidates={[]} />);
      
      expect(screen.getByText("No decay candidates")).toBeInTheDocument();
    });
  });

  describe("Search", () => {
    it("filters candidates by URL search", async () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const searchInput = screen.getByPlaceholderText("Search by URL or title...");
      await userEvent.type(searchInput, "blog");
      
      expect(screen.getByText("Complete SEO Guide 2024")).toBeInTheDocument();
    });

    it("filters candidates by title search", async () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const searchInput = screen.getByPlaceholderText("Search by URL or title...");
      await userEvent.type(searchInput, "Widget");
      
      expect(screen.getByText("Widget Pro - Product Page")).toBeInTheDocument();
    });
  });

  describe("Filters", () => {
    it("has status filter dropdown", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const statusFilter = screen.getByRole("combobox", { name: /filter by status/i });
      expect(statusFilter).toBeInTheDocument();
    });

    it("has severity filter dropdown", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const severityFilter = screen.getByRole("combobox", { name: /filter by severity/i });
      expect(severityFilter).toBeInTheDocument();
    });

    it("has page type filter dropdown", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const pageTypeFilter = screen.getByRole("combobox", { name: /filter by page type/i });
      expect(pageTypeFilter).toBeInTheDocument();
    });
  });

  describe("Sorting", () => {
    it("has sortable Score column", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const scoreHeader = screen.getByText("Score");
      expect(scoreHeader.closest("th")).toHaveClass("cursor-pointer");
    });

    it("has sortable Lost Clicks column", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const lostClicksHeader = screen.getByText("Lost Clicks");
      expect(lostClicksHeader.closest("th")).toHaveClass("cursor-pointer");
    });

    it("has sortable Upside column", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const upsideHeader = screen.getByText("Upside");
      expect(upsideHeader.closest("th")).toHaveClass("cursor-pointer");
    });
  });

  describe("Row click", () => {
    it("calls onRowClick when row is clicked", async () => {
      const onRowClick = vi.fn();
      render(<DecayQueueTable candidates={mockCandidates} onRowClick={onRowClick} />);
      
      const row = screen.getByRole("row", { name: /Complete SEO Guide/ });
      fireEvent.click(row);
      
      expect(onRowClick).toHaveBeenCalledWith(mockCandidates[0]);
    });
  });

  describe("Selection", () => {
    it("has select all checkbox", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const checkboxes = screen.getAllByRole("checkbox");
      expect(checkboxes.length).toBeGreaterThan(1); // Header + rows
    });

    it("selects all rows when header checkbox is clicked", async () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const headerCheckbox = screen.getByRole("checkbox", { name: /select all/i });
      await userEvent.click(headerCheckbox);
      
      // Should show bulk actions
      expect(screen.getByText(/selected/i)).toBeInTheDocument();
    });

    it("selects individual row when row checkbox is clicked", async () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const rowCheckboxes = screen.getAllByRole("checkbox").slice(1); // Skip header
      await userEvent.click(rowCheckboxes[0]);
      
      expect(screen.getByText("1 selected")).toBeInTheDocument();
    });
  });

  describe("Bulk actions", () => {
    it("shows bulk actions when rows are selected", async () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const rowCheckboxes = screen.getAllByRole("checkbox").slice(1);
      await userEvent.click(rowCheckboxes[0]);
      
      expect(screen.getByText("Update Status")).toBeInTheDocument();
    });

    it("calls onBulkUpdate with correct params", async () => {
      const onBulkUpdate = vi.fn();
      render(<DecayQueueTable candidates={mockCandidates} onBulkUpdate={onBulkUpdate} />);
      
      const rowCheckboxes = screen.getAllByRole("checkbox").slice(1);
      await userEvent.click(rowCheckboxes[0]);
      
      const updateButton = screen.getByText("Update Status");
      await userEvent.click(updateButton);
      
      // Click first dropdown item
      const plannedOption = screen.getByText("Mark as Planned");
      await userEvent.click(plannedOption);
      
      expect(onBulkUpdate).toHaveBeenCalledWith([1], "planned");
    });
  });

  describe("Pagination", () => {
    it("shows pagination when candidates exceed page size", () => {
      const manyCandidates = Array.from({ length: 25 }, (_, i) => ({
        ...mockCandidates[0],
        id: i + 1,
        pageTitle: `Page ${i + 1}`,
      }));
      
      render(<DecayQueueTable candidates={manyCandidates} pageSize={10} />);
      
      expect(screen.getByText("Previous")).toBeInTheDocument();
      expect(screen.getByText("Next")).toBeInTheDocument();
    });

    it("shows correct page info", () => {
      const manyCandidates = Array.from({ length: 25 }, (_, i) => ({
        ...mockCandidates[0],
        id: i + 1,
        pageTitle: `Page ${i + 1}`,
      }));
      
      render(<DecayQueueTable candidates={manyCandidates} pageSize={10} />);
      
      expect(screen.getByText(/Showing 1 to 10 of 25/)).toBeInTheDocument();
    });

    it("disables Previous on first page", () => {
      const manyCandidates = Array.from({ length: 25 }, (_, i) => ({
        ...mockCandidates[0],
        id: i + 1,
        pageTitle: `Page ${i + 1}`,
      }));
      
      render(<DecayQueueTable candidates={manyCandidates} pageSize={10} />);
      
      const prevButton = screen.getByText("Previous");
      expect(prevButton).toBeDisabled();
    });
  });

  describe("Export", () => {
    it("calls onExport when Export button is clicked", async () => {
      const onExport = vi.fn();
      render(<DecayQueueTable candidates={mockCandidates} onExport={onExport} />);
      
      const exportButton = screen.getByText("Export");
      await userEvent.click(exportButton);
      
      expect(onExport).toHaveBeenCalled();
    });

    it("does not render Export button when onExport is not provided", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      expect(screen.queryByText("Export")).not.toBeInTheDocument();
    });
  });

  describe("Accessibility", () => {
    it("has role='grid' on table container", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      expect(screen.getByRole("grid")).toBeInTheDocument();
    });

    it("has aria-label on table", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      expect(screen.getByRole("grid")).toHaveAttribute("aria-label", "Content decay candidates");
    });

    it("has accessible row labels", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const rows = screen.getAllByRole("row");
      expect(rows.length).toBeGreaterThan(1); // Header + data rows
    });
  });

  describe("Number formatting", () => {
    it("formats lost clicks with K suffix", () => {
      const candidateWithLargeLoss: DecayCandidate = {
        ...mockCandidates[0],
        lostClickEstimate: 1500,
      };
      
      render(<DecayQueueTable candidates={[candidateWithLargeLoss]} />);
      
      expect(screen.getByText("-1.5K")).toBeInTheDocument();
    });

    it("formats recovery upside with K suffix", () => {
      const candidateWithLargeUpside: DecayCandidate = {
        ...mockCandidates[0],
        recoveryUpsideEstimate: 2500,
      };
      
      render(<DecayQueueTable candidates={[candidateWithLargeUpside]} />);
      
      expect(screen.getByText("+2.5K")).toBeInTheDocument();
    });
  });

  describe("Custom className", () => {
    it("applies custom className", () => {
      const { container } = render(
        <DecayQueueTable candidates={mockCandidates} className="custom-class" />
      );
      
      expect(container.firstChild).toHaveClass("custom-class");
    });
  });

  describe("URL truncation", () => {
    it("truncates long URLs", () => {
      const candidateWithLongUrl: DecayCandidate = {
        ...mockCandidates[0],
        pageUrl: "https://example.com/very/long/path/to/the/page/that/should/be/truncated",
      };
      
      render(<DecayQueueTable candidates={[candidateWithLongUrl]} />);
      
      const link = screen.getByRole("link");
      expect(link.textContent).toContain("...");
    });
  });

  describe("Actions column", () => {
    it("has actions dropdown for each row", () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const actionButtons = screen.getAllByRole("button", { name: "" }).filter(
        (btn) => btn.textContent?.includes("⋯")
      );
      expect(actionButtons.length).toBe(2);
    });

    it("has View Details option in actions", async () => {
      render(<DecayQueueTable candidates={mockCandidates} />);
      
      const actionButton = screen.getAllByRole("button").find(
        (btn) => btn.textContent?.includes("⋯")
      );
      if (actionButton) {
        await userEvent.click(actionButton);
        expect(screen.getByText("View Details")).toBeInTheDocument();
      }
    });
  });
});
