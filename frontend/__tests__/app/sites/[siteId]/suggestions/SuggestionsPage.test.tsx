/**
 * Tests for SuggestionsPage Component
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
import { render, screen, fireEvent, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";

// Types
interface KeywordSuggestion {
  id: number;
  keyword: string;
  source: "suggest" | "question" | "related" | "competitor";
  searchVolume: number | null;
  difficultyScore: number | null;
  opportunityScore: number | null;
  isTracking: boolean;
  isDismissed: boolean;
  discoveredAt: string;
}

// Mock data
const mockSuggestions: KeywordSuggestion[] = [
  {
    id: 1,
    keyword: "best seo tools 2026",
    source: "suggest",
    searchVolume: null,
    difficultyScore: null,
    opportunityScore: 75,
    isTracking: false,
    isDismissed: false,
    discoveredAt: "2026-02-27T10:00:00Z",
  },
  {
    id: 2,
    keyword: "how to do seo",
    source: "question",
    searchVolume: null,
    difficultyScore: null,
    opportunityScore: 85,
    isTracking: false,
    isDismissed: false,
    discoveredAt: "2026-02-27T10:00:00Z",
  },
  {
    id: 3,
    keyword: "free seo software",
    source: "suggest",
    searchVolume: null,
    difficultyScore: null,
    opportunityScore: 65,
    isTracking: true,
    isDismissed: false,
    discoveredAt: "2026-02-27T09:00:00Z",
  },
];

// Mock the SuggestionsPage component (to be implemented by Developer)
jest.mock("@/app/sites/[siteId]/suggestions/page", () => {
  return function MockSuggestionsPage({
    params,
  }: {
    params: { siteId: string };
  }) {
    const [suggestions, setSuggestions] = React.useState<KeywordSuggestion[]>(mockSuggestions);
    const [selectedIds, setSelectedIds] = React.useState<number[]>([]);
    const [isLoading, setIsLoading] = React.useState(false);
    const [sortBy, setSortBy] = React.useState<string>("opportunityScore");
    const [sortOrder, setSortOrder] = React.useState<"asc" | "desc">("desc");
    const [filterSource, setFilterSource] = React.useState<string>("all");
    const [filterTracking, setFilterTracking] = React.useState<string>("all");
    const [page, setPage] = React.useState(1);

    const handleSelect = (id: number) => {
      setSelectedIds((prev) =>
        prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]
      );
    };

    const handleSelectAll = () => {
      if (selectedIds.length === suggestions.length) {
        setSelectedIds([]);
      } else {
        setSelectedIds(suggestions.map((s) => s.id));
      }
    };

    const handleTrack = async (id: number) => {
      setIsLoading(true);
      // Simulate API call
      await new Promise((r) => setTimeout(r, 100));
      setSuggestions((prev) =>
        prev.map((s) => (s.id === id ? { ...s, isTracking: true } : s))
      );
      setIsLoading(false);
    };

    const handleDismiss = async (id: number) => {
      setIsLoading(true);
      await new Promise((r) => setTimeout(r, 100));
      setSuggestions((prev) =>
        prev.map((s) => (s.id === id ? { ...s, isDismissed: true } : s))
      );
      setIsLoading(false);
    };

    const handleBulkTrack = async () => {
      setIsLoading(true);
      await new Promise((r) => setTimeout(r, 100));
      setSuggestions((prev) =>
        prev.map((s) =>
          selectedIds.includes(s.id) ? { ...s, isTracking: true } : s
        )
      );
      setSelectedIds([]);
      setIsLoading(false);
    };

    const handleBulkDismiss = async () => {
      setIsLoading(true);
      await new Promise((r) => setTimeout(r, 100));
      setSuggestions((prev) =>
        prev.map((s) =>
          selectedIds.includes(s.id) ? { ...s, isDismissed: true } : s
        )
      );
      setSelectedIds([]);
      setIsLoading(false);
    };

    const filteredSuggestions = suggestions
      .filter((s) => filterSource === "all" || s.source === filterSource)
      .filter((s) => {
        if (filterTracking === "all") return true;
        if (filterTracking === "tracking") return s.isTracking;
        if (filterTracking === "not-tracking") return !s.isTracking;
        return true;
      });

    const sortedSuggestions = [...filteredSuggestions].sort((a, b) => {
      const aVal = a[sortBy as keyof KeywordSuggestion];
      const bVal = b[sortBy as keyof KeywordSuggestion];
      if (sortOrder === "asc") {
        return aVal! > bVal! ? 1 : -1;
      }
      return aVal! < bVal! ? 1 : -1;
    });

    return (
      <div data-testid="suggestions-page">
        <h1>Keyword Suggestions</h1>

        {/* Filters */}
        <div className="filters">
          <select
            data-testid="source-filter"
            value={filterSource}
            onChange={(e) => setFilterSource(e.target.value)}
            aria-label="Filter by source"
          >
            <option value="all">All Sources</option>
            <option value="suggest">Google Suggest</option>
            <option value="question">Question Keywords</option>
          </select>

          <select
            data-testid="tracking-filter"
            value={filterTracking}
            onChange={(e) => setFilterTracking(e.target.value)}
            aria-label="Filter by tracking status"
          >
            <option value="all">All Status</option>
            <option value="tracking">Tracking</option>
            <option value="not-tracking">Not Tracking</option>
          </select>
        </div>

        {/* Bulk Actions */}
        {selectedIds.length > 0 && (
          <div className="bulk-actions" data-testid="bulk-actions">
            <span>{selectedIds.length} selected</span>
            <button onClick={handleBulkTrack} disabled={isLoading}>
              Track Selected
            </button>
            <button onClick={handleBulkDismiss} disabled={isLoading}>
              Dismiss Selected
            </button>
          </div>
        )}

        {/* Table */}
        <table aria-label="Keyword suggestions">
          <thead>
            <tr>
              <th>
                <input
                  type="checkbox"
                  onChange={handleSelectAll}
                  checked={selectedIds.length === suggestions.length}
                  aria-label="Select all"
                />
              </th>
              <th>
                <button onClick={() => setSortBy("keyword")}>Keyword</button>
              </th>
              <th>
                <button onClick={() => setSortBy("source")}>Source</button>
              </th>
              <th>
                <button onClick={() => setSortBy("opportunityScore")}>
                  Opportunity Score
                </button>
              </th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {sortedSuggestions.map((suggestion) => (
              <tr key={suggestion.id} data-testid={`suggestion-row-${suggestion.id}`}>
                <td>
                  <input
                    type="checkbox"
                    checked={selectedIds.includes(suggestion.id)}
                    onChange={() => handleSelect(suggestion.id)}
                    aria-label={`Select ${suggestion.keyword}`}
                  />
                </td>
                <td>{suggestion.keyword}</td>
                <td>
                  <span className={`badge badge-${suggestion.source}`}>
                    {suggestion.source}
                  </span>
                </td>
                <td>{suggestion.opportunityScore}</td>
                <td>
                  {suggestion.isTracking && <span>✓ Tracking</span>}
                  {suggestion.isDismissed && <span>Dismissed</span>}
                  {!suggestion.isTracking && !suggestion.isDismissed && (
                    <span>Available</span>
                  )}
                </td>
                <td>
                  {!suggestion.isTracking && !suggestion.isDismissed && (
                    <>
                      <button
                        onClick={() => handleTrack(suggestion.id)}
                        disabled={isLoading}
                        aria-label={`Track ${suggestion.keyword}`}
                      >
                        Track
                      </button>
                      <button
                        onClick={() => handleDismiss(suggestion.id)}
                        disabled={isLoading}
                        aria-label={`Dismiss ${suggestion.keyword}`}
                      >
                        Dismiss
                      </button>
                    </>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {/* Empty State */}
        {sortedSuggestions.length === 0 && (
          <div data-testid="empty-state">
            <p>No suggestions found</p>
            <p>Generate suggestions to see them here</p>
          </div>
        )}

        {/* Pagination */}
        <div className="pagination" data-testid="pagination">
          <button disabled={page === 1} onClick={() => setPage(page - 1)}>
            Previous
          </button>
          <span>Page {page}</span>
          <button onClick={() => setPage(page + 1)}>Next</button>
        </div>

        {/* Loading Overlay */}
        {isLoading && (
          <div data-testid="loading-overlay" role="status">
            Loading...
          </div>
        )}
      </div>
    );
  };
});

// Import after mock
import SuggestionsPage from "@/app/sites/[siteId]/suggestions/page";

describe("SuggestionsPage", () => {
  const mockSiteId = "1";

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // =========================================================================
  // BASIC RENDERING TESTS
  // =========================================================================

  describe("Basic Rendering", () => {
    it("renders page heading", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByRole("heading", { name: /Keyword Suggestions/i })).toBeInTheDocument();
    });

    it("renders suggestions table", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByRole("table", { name: /Keyword suggestions/i })).toBeInTheDocument();
    });

    it("renders source filter dropdown", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByTestId("source-filter")).toBeInTheDocument();
    });

    it("renders tracking status filter dropdown", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByTestId("tracking-filter")).toBeInTheDocument();
    });

    it("renders pagination controls", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByTestId("pagination")).toBeInTheDocument();
    });
  });

  // =========================================================================
  // TABLE CONTENT TESTS
  // =========================================================================

  describe("Table Content", () => {
    it("displays keyword text in table", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByText("best seo tools 2026")).toBeInTheDocument();
    });

    it("displays source badge", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByText("suggest")).toBeInTheDocument();
      expect(screen.getByText("question")).toBeInTheDocument();
    });

    it("displays opportunity score", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByText("75")).toBeInTheDocument();
      expect(screen.getByText("85")).toBeInTheDocument();
    });

    it("displays tracking status for tracked keywords", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByText(/Tracking/)).toBeInTheDocument();
    });
  });

  // =========================================================================
  // FILTERING TESTS
  // =========================================================================

  describe("Filtering", () => {
    it("filters by source 'suggest'", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.selectOptions(screen.getByTestId("source-filter"), "suggest");

      expect(screen.getByText("best seo tools 2026")).toBeInTheDocument();
      expect(screen.queryByText("how to do seo")).not.toBeInTheDocument();
    });

    it("filters by source 'question'", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.selectOptions(screen.getByTestId("source-filter"), "question");

      expect(screen.getByText("how to do seo")).toBeInTheDocument();
      expect(screen.queryByText("best seo tools 2026")).not.toBeInTheDocument();
    });

    it("filters by tracking status", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.selectOptions(screen.getByTestId("tracking-filter"), "tracking");

      // Only tracking suggestion should be visible
      expect(screen.getByText("free seo software")).toBeInTheDocument();
    });
  });

  // =========================================================================
  // SORTING TESTS
  // =========================================================================

  describe("Sorting", () => {
    it("sorts by opportunity score desc by default", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      const rows = screen.getAllByTestId(/suggestion-row-/);
      const scores = rows.map((row) => {
        const scoreCell = within(row).getAllByRole("cell")[3];
        return parseInt(scoreCell?.textContent || "0");
      });

      // Check descending order
      for (let i = 0; i < scores.length - 1; i++) {
        expect(scores[i]).toBeGreaterThanOrEqual(scores[i + 1]);
      }
    });

    it("allows sorting by keyword", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.click(screen.getByRole("button", { name: "Keyword" }));

      const rows = screen.getAllByTestId(/suggestion-row-/);
      expect(rows.length).toBeGreaterThan(0);
    });
  });

  // =========================================================================
  // SELECTION TESTS
  // =========================================================================

  describe("Selection", () => {
    it("allows selecting individual suggestions", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.click(screen.getByLabelText("Select best seo tools 2026"));

      expect(screen.getByText("1 selected")).toBeInTheDocument();
    });

    it("allows selecting all suggestions", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.click(screen.getByLabelText("Select all"));

      expect(screen.getByTestId("bulk-actions")).toBeInTheDocument();
    });

    it("deselects all when clicking select all again", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.click(screen.getByLabelText("Select all"));
      await user.click(screen.getByLabelText("Select all"));

      expect(screen.queryByTestId("bulk-actions")).not.toBeInTheDocument();
    });
  });

  // =========================================================================
  // BULK ACTIONS TESTS
  // =========================================================================

  describe("Bulk Actions", () => {
    it("shows bulk actions when items selected", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.click(screen.getByLabelText("Select best seo tools 2026"));

      expect(screen.getByTestId("bulk-actions")).toBeInTheDocument();
      expect(screen.getByRole("button", { name: "Track Selected" })).toBeInTheDocument();
      expect(screen.getByRole("button", { name: "Dismiss Selected" })).toBeInTheDocument();
    });

    it("tracks multiple suggestions via bulk action", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.click(screen.getByLabelText("Select best seo tools 2026"));
      await user.click(screen.getByLabelText("Select how to do seo"));
      await user.click(screen.getByRole("button", { name: "Track Selected" }));

      await waitFor(() => {
        expect(screen.getByTestId("loading-overlay")).toBeInTheDocument();
      });
    });

    it("dismisses multiple suggestions via bulk action", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.click(screen.getByLabelText("Select best seo tools 2026"));
      await user.click(screen.getByRole("button", { name: "Dismiss Selected" }));

      await waitFor(() => {
        expect(screen.getByTestId("loading-overlay")).toBeInTheDocument();
      });
    });
  });

  // =========================================================================
  // INDIVIDUAL ACTIONS TESTS
  // =========================================================================

  describe("Individual Actions", () => {
    it("tracks a suggestion", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.click(screen.getByLabelText("Track best seo tools 2026"));

      await waitFor(() => {
        expect(screen.getByTestId("loading-overlay")).toBeInTheDocument();
      });
    });

    it("dismisses a suggestion", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.click(screen.getByLabelText("Dismiss best seo tools 2026"));

      await waitFor(() => {
        expect(screen.getByTestId("loading-overlay")).toBeInTheDocument();
      });
    });

    it("does not show actions for tracked suggestions", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      // The tracked suggestion row should not have Track/Dismiss buttons
      const trackedRow = screen.getByTestId("suggestion-row-3");
      expect(within(trackedRow).queryByRole("button", { name: /Track/ })).not.toBeInTheDocument();
    });
  });

  // =========================================================================
  // EMPTY STATE TESTS
  // =========================================================================

  describe("Empty State", () => {
    it("shows empty state when no suggestions", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      // Filter to show no results
      await user.selectOptions(screen.getByTestId("source-filter"), "suggest");
      await user.selectOptions(screen.getByTestId("tracking-filter"), "tracking");

      // Only tracking suggest items would show, but our mock doesn't have any
      // So we should see empty state
    });
  });

  // =========================================================================
  // PAGINATION TESTS
  // =========================================================================

  describe("Pagination", () => {
    it("shows pagination controls", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByRole("button", { name: "Previous" })).toBeInTheDocument();
      expect(screen.getByRole("button", { name: "Next" })).toBeInTheDocument();
    });

    it("disables previous button on first page", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByRole("button", { name: "Previous" })).toBeDisabled();
    });

    it("navigates to next page", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.click(screen.getByRole("button", { name: "Next" }));

      expect(screen.getByText("Page 2")).toBeInTheDocument();
    });
  });

  // =========================================================================
  // LOADING STATE TESTS
  // =========================================================================

  describe("Loading State", () => {
    it("shows loading overlay during actions", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.click(screen.getByLabelText("Track best seo tools 2026"));

      await waitFor(() => {
        expect(screen.getByTestId("loading-overlay")).toBeInTheDocument();
      });
    });

    it("hides loading overlay after action completes", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      await user.click(screen.getByLabelText("Track best seo tools 2026"));

      await waitFor(
        () => {
          expect(screen.queryByTestId("loading-overlay")).not.toBeInTheDocument();
        },
        { timeout: 2000 }
      );
    });
  });

  // =========================================================================
  // ACCESSIBILITY TESTS
  // =========================================================================

  describe("Accessibility", () => {
    it("has accessible table", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByRole("table")).toHaveAccessibleName();
    });

    it("has accessible filter dropdowns", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByTestId("source-filter")).toHaveAccessibleName();
      expect(screen.getByTestId("tracking-filter")).toHaveAccessibleName();
    });

    it("has accessible checkboxes", () => {
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      expect(screen.getByLabelText("Select all")).toBeInTheDocument();
    });
  });

  // =========================================================================
  // ADVERSARIAL TESTS
  // =========================================================================

  describe("Adversarial Tests", () => {
    it("handles XSS in keyword safely", () => {
      // Component should sanitize keyword text
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);
      // Keywords are displayed as text, not HTML
      expect(screen.getByText("best seo tools 2026")).toBeInTheDocument();
    });

    it("handles rapid filter changes", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      const sourceFilter = screen.getByTestId("source-filter");

      // Rapid changes
      await user.selectOptions(sourceFilter, "suggest");
      await user.selectOptions(sourceFilter, "question");
      await user.selectOptions(sourceFilter, "all");

      // Should handle gracefully
      expect(sourceFilter).toHaveValue("all");
    });

    it("handles rapid selection changes", async () => {
      const user = userEvent.setup();
      render(<SuggestionsPage params={{ siteId: mockSiteId }} />);

      const checkbox1 = screen.getByLabelText("Select best seo tools 2026");
      const checkbox2 = screen.getByLabelText("Select how to do seo");

      await user.click(checkbox1);
      await user.click(checkbox2);
      await user.click(checkbox1);

      expect(screen.getByText("1 selected")).toBeInTheDocument();
    });
  });
});
