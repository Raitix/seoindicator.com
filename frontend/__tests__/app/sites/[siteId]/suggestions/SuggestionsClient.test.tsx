/**
 * Unit tests for the SuggestionsClient component
 *
 * The route module (app/sites/[siteId]/suggestions/page.tsx) is a thin async
 * server-component wrapper; the markup under test lives in the
 * SuggestionsClient component it renders, so the client component is
 * rendered directly.
 *
 * @see TASK-5-10
 * @see SPEC-005
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
const mockSearchParams = new URLSearchParams();

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

vi.mock("next/navigation", () => ({
  useRouter: () => ({
    push: vi.fn(),
    replace: mockReplace,
  }),
  useSearchParams: () => mockSearchParams,
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

const createSuggestion = (overrides: Record<string, unknown> = {}) => ({
  id: 1,
  keyword: "best seo tools 2026",
  source: "suggest" as const,
  searchVolume: 1000,
  difficultyScore: 45,
  opportunityScore: 85,
  isTracking: false,
  isDismissed: false,
  discoveredAt: "2026-02-27T10:00:00Z",
  ...overrides,
});

const defaultUseSuggestionsReturn = {
  suggestions: [
    createSuggestion(),
    createSuggestion({
      id: 2,
      keyword: "how to do seo",
      source: "question" as const,
      opportunityScore: 75,
      isTracking: true,
    }),
    createSuggestion({
      id: 3,
      keyword: "seo software free",
      opportunityScore: 65,
      isDismissed: true,
    }),
  ],
  meta: { total: 50, page: 1, perPage: 20 },
  isLoading: false,
  error: null,
  mutate: mockMutate,
};

describe("SuggestionsClient", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockSearchParams.delete("source");
    mockSearchParams.delete("status");
    mockSearchParams.delete("sort");
    mockSearchParams.delete("order");
    mockUseSuggestions.mockReturnValue(defaultUseSuggestionsReturn);
    mockTrackSuggestion.mockResolvedValue({});
    mockBulkTrackSuggestions.mockResolvedValue({});
    mockDismissSuggestion.mockResolvedValue({});
  });

  describe("Rendering", () => {
    it("renders the page heading", () => {
      render(<SuggestionsClient siteId="1" />);
      expect(
        screen.getByRole("heading", { name: /keyword suggestions/i, level: 1 })
      ).toBeInTheDocument();
    });

    it("renders the total suggestion count", () => {
      render(<SuggestionsClient siteId="1" />);
      expect(screen.getByText("50 total suggestions")).toBeInTheDocument();
    });

    it("renders the suggestions table", () => {
      render(<SuggestionsClient siteId="1" />);
      expect(screen.getByTestId("suggestions-table")).toBeInTheDocument();
    });

    it("requests the suggestions for the given site", () => {
      render(<SuggestionsClient siteId="1" />);
      expect(mockUseSuggestions).toHaveBeenCalledWith(
        "1",
        expect.objectContaining({ page: 1, perPage: 20 })
      );
    });
  });

  describe("Default Filters", () => {
    it("hides tracked and dismissed rows with the default status filter", () => {
      render(<SuggestionsClient siteId="1" />);

      expect(screen.getByTestId("suggestion-row-1")).toBeInTheDocument();
      expect(screen.queryByTestId("suggestion-row-2")).not.toBeInTheDocument();
      expect(screen.queryByTestId("suggestion-row-3")).not.toBeInTheDocument();
    });

    it("asks the API for tracked suggestions once the status filter is widened", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId="1" />);

      await user.selectOptions(screen.getByTestId("status-filter"), "all");

      await waitFor(() => {
        expect(mockUseSuggestions).toHaveBeenCalledWith(
          "1",
          expect.objectContaining({ includeTracking: true, includeDismissed: true })
        );
      });
    });
  });

  describe("Filters", () => {
    it("applies the source filter", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId="1" />);

      await user.selectOptions(screen.getByTestId("source-filter"), "question");

      await waitFor(() => {
        expect(mockUseSuggestions).toHaveBeenCalledWith(
          "1",
          expect.objectContaining({ source: "question" })
        );
      });
    });

    it("reflects the source filter in the url", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId="1" />);

      await user.selectOptions(screen.getByTestId("source-filter"), "question");

      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalledWith(
          expect.stringContaining("source=question"),
          { scroll: false }
        );
      });
    });
  });

  describe("Loading, Error and Empty States", () => {
    it("shows the loading overlay while the suggestions load", () => {
      mockUseSuggestions.mockReturnValue({
        suggestions: [],
        meta: { total: 0, page: 1, perPage: 20 },
        isLoading: true,
        error: null,
        mutate: mockMutate,
      });

      render(<SuggestionsClient siteId="1" />);
      expect(screen.getByTestId("loading-overlay")).toBeInTheDocument();
    });

    it("shows the error state when the request fails", () => {
      mockUseSuggestions.mockReturnValue({
        suggestions: [],
        meta: { total: 0, page: 1, perPage: 20 },
        isLoading: false,
        error: new Error("boom"),
        mutate: mockMutate,
      });

      render(<SuggestionsClient siteId="1" />);
      expect(screen.getByText(/failed to load suggestions/i)).toBeInTheDocument();
    });

    it("shows the empty state when there are no suggestions", () => {
      mockUseSuggestions.mockReturnValue({
        suggestions: [],
        meta: { total: 0, page: 1, perPage: 20 },
        isLoading: false,
        error: null,
        mutate: mockMutate,
      });

      render(<SuggestionsClient siteId="1" />);
      expect(screen.getByTestId("empty-state")).toBeInTheDocument();
    });
  });

  describe("Actions", () => {
    it("tracks a suggestion from its row", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId="1" />);

      await user.click(screen.getByTestId("track-btn-1"));

      await waitFor(() => {
        expect(mockTrackSuggestion).toHaveBeenCalledWith(1, 1);
      });
    });

    it("dismisses a suggestion from its row", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId="1" />);

      await user.click(screen.getByTestId("dismiss-btn-1"));

      await waitFor(() => {
        expect(mockDismissSuggestion).toHaveBeenCalledWith(1, 1);
      });
    });

    it("bulk tracks the selected suggestions", async () => {
      const user = userEvent.setup();
      render(<SuggestionsClient siteId="1" />);

      await user.click(screen.getByTestId("select-1"));
      await user.click(screen.getByText("Track Selected"));

      await waitFor(() => {
        expect(mockBulkTrackSuggestions).toHaveBeenCalledWith(1, [1]);
      });
    });
  });
});
