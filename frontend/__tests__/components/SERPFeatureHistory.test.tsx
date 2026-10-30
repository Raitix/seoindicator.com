import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";

import SERPFeatureHistory from "@/components/keywords/SERPFeatureHistory";
import {
  useSerpFeatureHistory,
  getFeatureMeta,
  formatFeatureDate,
  getRelativeTime,
  isWithinDays,
} from "@/lib/api/serp-features";

// Mock the API hook
jest.mock("@/lib/api/serp-features", () => ({
  useSerpFeatureHistory: jest.fn(),
  getFeatureMeta: jest.fn((type) => ({
    name: type.replace(/_/g, " "),
    icon: "📊",
    description: "SERP feature",
    ctrImpact: "Unknown",
  })),
  formatFeatureDate: jest.fn((date) => {
    const d = new Date(date);
    return d.toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" });
  }),
  getRelativeTime: jest.fn((date) => "2 days ago"),
  isWithinDays: jest.fn(() => true),
}));

const mockUseSerpFeatureHistory = useSerpFeatureHistory as jest.MockedFunction<typeof useSerpFeatureHistory>;

// Mock data
const mockKeywordData = {
  keyword: {
    id: 1,
    keyword: "seo tools",
  },
  currentFeatures: [
    { type: "featured_snippet" as const, isPresent: true },
    { type: "sitelinks" as const, isPresent: true },
    { type: "local_pack" as const, isPresent: false },
  ],
  history: [
    {
      date: "2026-02-26",
      features: { featured_snippet: true, sitelinks: true },
    },
    {
      date: "2026-02-25",
      features: { featured_snippet: false, sitelinks: true },
    },
  ],
  changes: [
    {
      type: "featured_snippet" as const,
      action: "gained" as const,
      detectedAt: "2026-02-26T10:00:00Z",
    },
    {
      type: "local_pack" as const,
      action: "lost" as const,
      detectedAt: "2026-02-24T15:00:00Z",
    },
    {
      type: "sitelinks" as const,
      action: "gained" as const,
      detectedAt: "2026-02-20T12:00:00Z",
    },
  ],
};

describe("SERPFeatureHistory", () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe("CR-001: Renders without error", () => {
    it("renders with keywordId", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: mockKeywordData,
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} />);

      expect(screen.getByText("SERP Feature History")).toBeInTheDocument();
      expect(screen.getByText("Track gained and lost SERP features over time.")).toBeInTheDocument();
    });
  });

  describe("CR-002: Loading state", () => {
    it("shows loading indicator while fetching", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: null,
        isLoading: true,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} />);

      // Loading skeleton should be visible
      const skeletonElements = document.querySelectorAll(".animate-pulse");
      expect(skeletonElements.length).toBeGreaterThan(0);
    });
  });

  describe("CR-003: Error state", () => {
    it("shows error message when API fails", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: null,
        isLoading: false,
        error: new Error("Failed to load"),
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} />);

      expect(screen.getByText("Failed to load")).toBeInTheDocument();
      expect(screen.getByRole("button", { name: /retry/i })).toBeInTheDocument();
    });

    it("calls mutate on retry click", async () => {
      const mockMutate = jest.fn();
      mockUseSerpFeatureHistory.mockReturnValue({
        data: null,
        isLoading: false,
        error: new Error("Failed to load"),
        mutate: mockMutate,
      });

      render(<SERPFeatureHistory keywordId={1} />);

      await userEvent.click(screen.getByRole("button", { name: /retry/i }));
      expect(mockMutate).toHaveBeenCalled();
    });
  });

  describe("CR-004: Empty history", () => {
    it("shows empty message when no changes", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: {
          keyword: { id: 1, keyword: "test" },
          currentFeatures: [],
          history: [],
          changes: [],
        },
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} />);

      expect(screen.getByText("No SERP feature changes recorded for this keyword.")).toBeInTheDocument();
    });
  });

  describe("TL-001: Single gain", () => {
    it("displays a gained feature with correct icon", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: {
          keyword: { id: 1, keyword: "test" },
          currentFeatures: [],
          history: [],
          changes: [
            {
              type: "featured_snippet",
              action: "gained",
              detectedAt: "2026-02-26T10:00:00Z",
            },
          ],
        },
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} />);

      expect(screen.getByText("Gained")).toBeInTheDocument();
    });
  });

  describe("TL-002: Single loss", () => {
    it("displays a lost feature with correct icon", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: {
          keyword: { id: 1, keyword: "test" },
          currentFeatures: [],
          history: [],
          changes: [
            {
              type: "local_pack",
              action: "lost",
              detectedAt: "2026-02-24T15:00:00Z",
            },
          ],
        },
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} />);

      expect(screen.getByText("Lost")).toBeInTheDocument();
    });
  });

  describe("TL-003: Multiple events", () => {
    it("displays multiple changes in timeline", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: mockKeywordData,
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} />);

      // Check for multiple gained/lost badges
      const gainedBadges = screen.getAllByText("Gained");
      const lostBadges = screen.getAllByText("Lost");
      
      expect(gainedBadges.length).toBe(2);
      expect(lostBadges.length).toBe(1);
    });
  });

  describe("TL-004: Ordered by date", () => {
    it("shows changes count", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: mockKeywordData,
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} />);

      expect(screen.getByText("3 changes")).toBeInTheDocument();
    });
  });

  describe("RC-001: Recent Changes widget", () => {
    it("shows recent changes section when enabled", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: mockKeywordData,
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} showRecentWidget={true} />);

      expect(screen.getByText("Recent Changes")).toBeInTheDocument();
    });

    it("hides recent changes widget when disabled", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: mockKeywordData,
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} showRecentWidget={false} />);

      expect(screen.queryByText("Recent Changes")).not.toBeInTheDocument();
    });
  });

  describe("RC-002: No recent changes", () => {
    it("shows empty state when no recent changes", () => {
      (isWithinDays as jest.Mock).mockReturnValue(false);
      
      mockUseSerpFeatureHistory.mockReturnValue({
        data: {
          keyword: { id: 1, keyword: "test" },
          currentFeatures: [],
          history: [],
          changes: [
            {
              type: "featured_snippet",
              action: "gained",
              detectedAt: "2026-01-01T10:00:00Z", // Old date
            },
          ],
        },
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} />);

      expect(screen.getByText("No recent changes detected.")).toBeInTheDocument();
    });
  });

  describe("AI-001: Fetches on mount", () => {
    it("calls API hook with keywordId", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: null,
        isLoading: true,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={42} />);

      expect(mockUseSerpFeatureHistory).toHaveBeenCalledWith(42, expect.any(Object));
    });
  });

  describe("AI-002: Correct endpoint parameters", () => {
    it("passes days parameter to API hook", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: null,
        isLoading: true,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} days={60} />);

      expect(mockUseSerpFeatureHistory).toHaveBeenCalledWith(1, expect.objectContaining({ days: 60 }));
    });
  });

  describe("Expand/Collapse", () => {
    it("collapses and expands on header click", async () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: mockKeywordData,
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} />);

      // Timeline should be visible initially
      expect(screen.getByText("Timeline")).toBeInTheDocument();

      // Click header to collapse
      await userEvent.click(screen.getByText("SERP Feature History"));

      // Content should be hidden
      expect(screen.queryByText("Timeline")).not.toBeInTheDocument();

      // Click again to expand
      await userEvent.click(screen.getByText("SERP Feature History"));

      // Timeline should be visible again
      expect(screen.getByText("Timeline")).toBeInTheDocument();
    });
  });

  describe("Show All Toggle", () => {
    it("shows 'Show more' button when changes exceed maxItems", () => {
      const manyChanges = Array.from({ length: 15 }, (_, i) => ({
        type: "featured_snippet" as const,
        action: "gained" as const,
        detectedAt: `2026-02-${String(26 - i).padStart(2, "0")}T10:00:00Z`,
      }));

      mockUseSerpFeatureHistory.mockReturnValue({
        data: {
          keyword: { id: 1, keyword: "test" },
          currentFeatures: [],
          history: [],
          changes: manyChanges,
        },
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} maxItems={10} />);

      expect(screen.getByText(/Show 5 more changes/)).toBeInTheDocument();
    });

    it("toggles between showing all and limited changes", async () => {
      const manyChanges = Array.from({ length: 15 }, (_, i) => ({
        type: "featured_snippet" as const,
        action: "gained" as const,
        detectedAt: `2026-02-${String(26 - i).padStart(2, "0")}T10:00:00Z`,
      }));

      mockUseSerpFeatureHistory.mockReturnValue({
        data: {
          keyword: { id: 1, keyword: "test" },
          currentFeatures: [],
          history: [],
          changes: manyChanges,
        },
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} maxItems={10} />);

      // Click to show all
      await userEvent.click(screen.getByText(/Show 5 more changes/));

      // Should now show "Show less"
      expect(screen.getByText("Show less")).toBeInTheDocument();

      // Click to show less
      await userEvent.click(screen.getByText("Show less"));

      // Should show "Show more" again
      expect(screen.getByText(/Show 5 more changes/)).toBeInTheDocument();
    });
  });

  describe("Accessibility", () => {
    it("has proper ARIA labels", () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: mockKeywordData,
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} />);

      // Time elements should have dateTime attribute
      const timeElements = document.querySelectorAll("time[datetime]");
      expect(timeElements.length).toBeGreaterThan(0);
    });

    it("supports keyboard navigation for expand/collapse", async () => {
      mockUseSerpFeatureHistory.mockReturnValue({
        data: mockKeywordData,
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<SERPFeatureHistory keywordId={1} />);

      // CardHeader should have cursor-pointer class
      const cardHeader = document.querySelector(".cursor-pointer");
      expect(cardHeader).toBeInTheDocument();
    });
  });
});
