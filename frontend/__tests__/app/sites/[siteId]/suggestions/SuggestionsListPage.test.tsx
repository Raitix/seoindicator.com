/**
 * Tests for Suggestions List Page
 *
 * @see TASK-5-10
 * @see SPEC-005
 *
 * Acceptance Criteria:
 * - Page: /sites/:siteId/suggestions
 * - Table showing: keyword, source, opportunity score, tracking status
 * - Sortable by: opportunity score, keyword, source
 * - Filterable by: source (suggest, question), tracking status
 * - Pagination with 20 items per page
 * - Bulk actions: Track Selected, Dismiss Selected
 * - Individual actions: Track, Dismiss
 * - Empty state when no suggestions
 * - Loading state during API calls
 */

import React from "react";
import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";

const mockMutate = vi.fn();
const mockUseSuggestions = vi.fn();
const mockTrackSuggestion = vi.fn();
const mockBulkTrackSuggestions = vi.fn();
const mockDismissSuggestion = vi.fn();
const mockReplace = vi.fn();

vi.mock("@/lib/api/suggestions", () => ({
  useSuggestions: (siteId: string, params: any) => mockUseSuggestions(siteId, params),
  trackSuggestion: (...args: any[]) => mockTrackSuggestion(...args),
  bulkTrackSuggestions: (...args: any[]) => mockBulkTrackSuggestions(...args),
  dismissSuggestion: (...args: any[]) => mockDismissSuggestion(...args),
  defaultSuggestionMeta: {
    total: 0,
    page: 1,
    perPage: 20,
  },
}));

// The route module is a thin async server-component wrapper; the markup under
// test lives in the SuggestionsClient component it renders.
vi.mock("next/navigation", () => ({
  useRouter: () => ({
    push: vi.fn(),
    replace: mockReplace,
  }),
  useSearchParams: () => new URLSearchParams(),
  useParams: () => ({
    siteId: "1",
  }),
  usePathname: () => "/sites/1/suggestions",
}));

vi.mock("next/link", () => ({
  default: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => (
    <a href={href} {...props}>
      {children}
    </a>
  ),
}));

import SuggestionsClient from "@/app/sites/[siteId]/suggestions/SuggestionsClient";

const mockSuggestions = [
  {
    id: 1,
    keyword: "best seo tools 2026",
    source: "suggest" as const,
    searchVolume: 1000,
    difficultyScore: 45,
    opportunityScore: 85,
    isTracking: false,
    isDismissed: false,
    discoveredAt: "2026-02-27T10:00:00Z",
  },
  {
    id: 2,
    keyword: "how to do seo",
    source: "question" as const,
    searchVolume: 800,
    difficultyScore: 30,
    opportunityScore: 75,
    isTracking: true,
    isDismissed: false,
    discoveredAt: "2026-02-27T09:00:00Z",
  },
  {
    id: 3,
    keyword: "seo software free",
    source: "suggest" as const,
    searchVolume: 500,
    difficultyScore: 60,
    opportunityScore: 65,
    isTracking: false,
    isDismissed: true,
    discoveredAt: "2026-02-26T15:00:00Z",
  },
];

const defaultUseSuggestionsReturn = {
  suggestions: mockSuggestions,
  meta: { total: 50, page: 1, perPage: 20 },
  isLoading: false,
  error: null,
  mutate: mockMutate,
};

describe("SuggestionsListPage", () => {
  const mockSiteId = "1";

  beforeEach(() => {
    vi.clearAllMocks();
    mockUseSuggestions.mockReturnValue(defaultUseSuggestionsReturn);
    mockTrackSuggestion.mockResolvedValue({});
    mockBulkTrackSuggestions.mockResolvedValue({});
    mockDismissSuggestion.mockResolvedValue({});
  });

  // The client defaults the status filter to "new", which hides the tracked
  // and dismissed rows. Switch to "All Status" whenever every row is needed.
  const showAllStatuses = async (user: ReturnType<typeof userEvent.setup>) => {
    await user.selectOptions(screen.getByTestId("status-filter"), "all");
  };

  // =========================================================================
  // BASIC RENDERING TESTS
  // =========================================================================

  describe("Basic Rendering", () => {
    it("renders page title", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByText("Keyword Suggestions")).toBeInTheDocument();
    });

    it("renders suggestions table", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByTestId("suggestions-table")).toBeInTheDocument();
    });

    it("renders table headers", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByRole("columnheader", { name: /keyword/i })).toBeInTheDocument();
      expect(screen.getByRole("columnheader", { name: /source/i })).toBeInTheDocument();
      expect(screen.getByRole("columnheader", { name: /opportunity score/i })).toBeInTheDocument();
    });

    it("renders pagination controls", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByTestId("pagination")).toBeInTheDocument();
    });
  });

  // =========================================================================
  // FILTER TESTS
  // =========================================================================

  describe("Filters", () => {
    it("renders source filter dropdown", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByTestId("source-filter")).toBeInTheDocument();
    });

    it("renders tracking status filter dropdown", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByTestId("status-filter")).toBeInTheDocument();
    });

    it("source filter has correct options", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      const select = screen.getByTestId("source-filter");
      const options = within(select).getAllByRole("option");

      expect(options).toHaveLength(5);
      expect(options[0]).toHaveValue("all");
      expect(options[1]).toHaveValue("suggest");
      expect(options[2]).toHaveValue("question");
    });

    it("tracking filter has correct options", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      const select = screen.getByTestId("status-filter");
      const options = within(select).getAllByRole("option");

      expect(options).toHaveLength(4);
      expect(options[0]).toHaveValue("all");
      expect(options[1]).toHaveValue("new");
      expect(options[2]).toHaveValue("tracking");
      expect(options[3]).toHaveValue("dismissed");
    });
  });

  // =========================================================================
  // SORT TESTS
  // =========================================================================

  describe("Sorting", () => {
    it("allows sorting by keyword", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      await user.click(screen.getByRole("columnheader", { name: /keyword/i }));

      expect(mockReplace).toHaveBeenCalledWith(
        expect.stringContaining("sort=keyword"),
        { scroll: false }
      );
    });

    it("allows sorting by source", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      await user.click(screen.getByRole("columnheader", { name: /source/i }));

      expect(mockReplace).toHaveBeenCalledWith(
        expect.stringContaining("sort=source"),
        { scroll: false }
      );
    });

    it("allows sorting by opportunity score", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      await user.click(screen.getByRole("columnheader", { name: /opportunity score/i }));

      // The opportunity score is the default sort, so clicking it flips the
      // order and the URL carries order=asc.
      expect(mockReplace).toHaveBeenCalledWith(
        expect.stringContaining("order=asc"),
        { scroll: false }
      );
    });

    it("toggles sort order on second click", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      const keywordHeader = screen.getByRole("columnheader", { name: /keyword/i });
      await user.click(keywordHeader);
      expect(mockReplace).toHaveBeenCalledWith(
        expect.stringContaining("sort=keyword"),
        { scroll: false }
      );

      await user.click(keywordHeader);
      expect(mockReplace).toHaveBeenCalledWith(
        expect.stringContaining("order=asc"),
        { scroll: false }
      );
    });
  });

  // =========================================================================
  // TABLE ROW TESTS
  // =========================================================================

  describe("Table Rows", () => {
    it("displays keyword text", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByText("best seo tools 2026")).toBeInTheDocument();
    });

    it("displays source badge", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);
      await showAllStatuses(user);

      // Multiple rows may have a source badge - use getAllByText or check within row
      const row = screen.getByTestId("suggestion-row-1");
      expect(within(row).getByText("Google Suggest")).toBeInTheDocument();
    });

    it("displays opportunity score", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByText("85")).toBeInTheDocument();
    });

    it("displays tracking status correctly", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);
      await showAllStatuses(user);

      const row = screen.getByTestId("suggestion-row-2");
      expect(within(row).getByText("Tracking")).toBeInTheDocument();
    });

    it("displays dismissed status correctly", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);
      await showAllStatuses(user);

      const row = screen.getByTestId("suggestion-row-3");
      expect(within(row).getByText("Dismissed")).toBeInTheDocument();
    });

    it("displays available status correctly", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);
      await showAllStatuses(user);

      const row = screen.getByTestId("suggestion-row-1");
      expect(within(row).getByText("New")).toBeInTheDocument();
    });
  });

  // =========================================================================
  // SELECTION TESTS
  // =========================================================================

  describe("Selection", () => {
    it("renders checkbox for each row", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByTestId("suggestion-row-1")).toBeInTheDocument();
      expect(screen.getByTestId("select-1")).toBeInTheDocument();
    });

    it("allows selecting individual rows", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      const checkbox = screen.getByTestId("select-1");
      await user.click(checkbox);

      expect(checkbox).toBeChecked();
    });

    it("shows bulk actions when rows selected", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      await user.click(screen.getByTestId("select-1"));

      expect(screen.getByText("1 selected")).toBeInTheDocument();
    });

    it("allows select all", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);
      await showAllStatuses(user);

      const selectAll = screen.getByTestId("select-all");
      await user.click(selectAll);

      expect(screen.getByText("3 selected")).toBeInTheDocument();
    });

    it("allows deselecting all", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);
      await showAllStatuses(user);

      const selectAll = screen.getByTestId("select-all");
      await user.click(selectAll);
      expect(screen.getByText("3 selected")).toBeInTheDocument();

      await user.click(selectAll);
      expect(screen.queryByText("3 selected")).not.toBeInTheDocument();
    });
  });

  // =========================================================================
  // BULK ACTIONS TESTS
  // =========================================================================

  describe("Bulk Actions", () => {
    it("renders Track Selected button", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      await user.click(screen.getByTestId("select-1"));
      expect(screen.getByText("Track Selected")).toBeInTheDocument();
    });

    it("renders Dismiss Selected button", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      await user.click(screen.getByTestId("select-1"));
      expect(screen.getByText("Dismiss Selected")).toBeInTheDocument();
    });

    it("clears selection after bulk track", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      await user.click(screen.getByTestId("select-1"));
      await user.click(screen.getByText("Track Selected"));

      await waitFor(() => {
        expect(mockBulkTrackSuggestions).toHaveBeenCalledWith(1, [1]);
      });
      expect(screen.queryByText("1 selected")).not.toBeInTheDocument();
    });

    it("clears selection after bulk dismiss", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      await user.click(screen.getByTestId("select-1"));
      await user.click(screen.getByText("Dismiss Selected"));

      await waitFor(() => {
        expect(mockDismissSuggestion).toHaveBeenCalledWith(1, 1);
      });
      expect(screen.queryByText("1 selected")).not.toBeInTheDocument();
    });
  });

  // =========================================================================
  // INDIVIDUAL ACTIONS TESTS
  // =========================================================================

  describe("Individual Actions", () => {
    it("renders Track button for available suggestions", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByTestId("track-btn-1")).toBeInTheDocument();
    });

    it("renders Dismiss button for available suggestions", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByTestId("dismiss-btn-1")).toBeInTheDocument();
    });

    it("does not render action buttons for tracking suggestions", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);
      await showAllStatuses(user);

      // Row 2 is tracking
      const row = screen.getByTestId("suggestion-row-2");
      expect(within(row).queryByRole("button", { name: /Track/ })).not.toBeInTheDocument();
      expect(within(row).queryByRole("button", { name: /Dismiss/ })).not.toBeInTheDocument();
    });

    it("does not render action buttons for dismissed suggestions", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);
      await showAllStatuses(user);

      // Row 3 is dismissed
      const row = screen.getByTestId("suggestion-row-3");
      expect(within(row).queryByRole("button", { name: /Dismiss/ })).not.toBeInTheDocument();
    });

    it("tracks a suggestion when the Track button is clicked", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      await user.click(screen.getByTestId("track-btn-1"));

      await waitFor(() => {
        expect(mockTrackSuggestion).toHaveBeenCalledWith(1, 1);
      });
    });

    it("dismisses a suggestion when the Dismiss button is clicked", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      await user.click(screen.getByTestId("dismiss-btn-1"));

      await waitFor(() => {
        expect(mockDismissSuggestion).toHaveBeenCalledWith(1, 1);
      });
    });
  });

  // =========================================================================
  // PAGINATION TESTS
  // =========================================================================

  describe("Pagination", () => {
    it("displays current page info", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByText("Page 1 of 3")).toBeInTheDocument();
    });

    it("disables previous button on first page", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByTestId("prev-page")).toBeDisabled();
    });

    it("enables next button when not on last page", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByTestId("next-page")).not.toBeDisabled();
    });

    it("shows total count", () => {
      render(<SuggestionsClient siteId={mockSiteId} />);
      // The text shows "Showing X of Y suggestions" or similar
      const totalCount = screen.getByText(/50 total suggestions/i);
      expect(totalCount).toBeInTheDocument();
    });
  });

  // =========================================================================
  // LOADING STATE TESTS
  // =========================================================================

  describe("Loading State", () => {
    it("shows loading state when data is loading", () => {
      mockUseSuggestions.mockReturnValue({
        suggestions: [],
        meta: { total: 0, page: 1, perPage: 20 },
        isLoading: true,
        error: null,
        mutate: mockMutate,
      });

      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByTestId("loading-overlay")).toBeInTheDocument();
    });
  });

  // =========================================================================
  // ERROR STATE TESTS
  // =========================================================================

  describe("Error State", () => {
    it("shows error state when API fails", () => {
      mockUseSuggestions.mockReturnValue({
        suggestions: [],
        meta: { total: 0, page: 1, perPage: 20 },
        isLoading: false,
        error: new Error("Failed to fetch suggestions"),
        mutate: mockMutate,
      });

      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByText(/failed to load suggestions/i)).toBeInTheDocument();
    });
  });

  // =========================================================================
  // EMPTY STATE TESTS
  // =========================================================================

  describe("Empty State", () => {
    it("shows empty state when no suggestions", () => {
      mockUseSuggestions.mockReturnValue({
        suggestions: [],
        meta: { total: 0, page: 1, perPage: 20 },
        isLoading: false,
        error: null,
        mutate: mockMutate,
      });

      render(<SuggestionsClient siteId={mockSiteId} />);
      expect(screen.getByTestId("empty-state")).toBeInTheDocument();
    });
  });

  // =========================================================================
  // ADVERSARIAL TESTS
  // =========================================================================

  describe("Adversarial Tests", () => {
    it("handles XSS in keyword text safely", () => {
      mockUseSuggestions.mockReturnValue({
        suggestions: [
          {
            id: 1,
            keyword: '<script>alert("xss")</script>test keyword',
            source: "suggest" as const,
            searchVolume: 100,
            difficultyScore: 50,
            opportunityScore: 75,
            isTracking: false,
            isDismissed: false,
            discoveredAt: "2026-02-27T10:00:00Z",
          },
        ],
        meta: { total: 1, page: 1, perPage: 20 },
        isLoading: false,
        error: null,
        mutate: mockMutate,
      });

      render(<SuggestionsClient siteId={mockSiteId} />);

      // The text should be escaped, not executed as script
      expect(screen.getByText('<script>alert("xss")</script>test keyword')).toBeInTheDocument();
      expect(screen.queryByTestId("xss-script")).not.toBeInTheDocument();
    });

    it("handles rapid pagination clicks", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      const nextButton = screen.getByTestId("next-page");

      // Rapid clicks should not cause issues
      await user.click(nextButton);
      await user.click(nextButton);
      expect(screen.getByTestId("page-info")).toBeInTheDocument();
    });

    it("handles filter change during selection", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId={mockSiteId} />);

      await user.click(screen.getByTestId("select-1"));

      // Changing filter should not crash
      const sourceFilter = screen.getByTestId("source-filter");
      await user.selectOptions(sourceFilter, "suggest");
      expect(sourceFilter).toHaveValue("suggest");
    });
  });
});
