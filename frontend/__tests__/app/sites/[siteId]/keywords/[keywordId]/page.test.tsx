/**
 * Unit tests for the Keyword Detail Page
 * Route: /sites/[siteId]/keywords/[keywordId]
 *
 * @see SPEC-002
 */

import React from "react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import KeywordPage from "@/app/sites/[siteId]/keywords/[keywordId]/page";
import {
  triggerRankCheck,
  useKeyword,
  useKeywordHistory,
} from "@/lib/api/keywords";
import type { Keyword } from "@/lib/types/keywords";

const mockPush = vi.fn();
const mockMutate = vi.fn();
const mockTriggerRankCheck = vi.fn();

vi.mock("next/navigation", () => ({
  useParams: () => ({ siteId: "1", keywordId: "42" }),
  useRouter: () => ({
    push: mockPush,
  }),
}));

vi.mock("next/link", () => ({
  default: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => (
    <a href={href} {...props}>
      {children}
    </a>
  ),
}));

vi.mock("@/lib/api/keywords", () => ({
  useKeyword: (id: number) => mockUseKeyword(id),
  useKeywordHistory: (id: number) => mockUseKeywordHistory(id),
  triggerRankCheck: (id: number) => mockTriggerRankCheck(id),
}));

// Recharts needs a real layout engine; stub the chart so the page markup
// is what gets asserted.
vi.mock("@/components/charts/RankingHistoryChart", () => ({
  default: ({ keywordName }: { keywordName: string }) => (
    <div data-testid="ranking-history-chart">{keywordName}</div>
  ),
}));

const mockUseKeyword = vi.fn();
const mockUseKeywordHistory = vi.fn();

const mockKeyword: Keyword = {
  id: 42,
  keyword: "technical seo audit",
  status: "active",
  currentPosition: 5,
  previousPosition: 9,
  positionChange: -4,
  lastCheckedAt: "2026-03-10T10:00:00Z",
  targetUrl: "https://example.com/audit",
  searchEngine: "google",
  location: "us",
  device: "desktop",
};

const defaultKeywordReturn = {
  keyword: mockKeyword,
  isLoading: false,
  error: null,
  mutate: mockMutate,
};

const defaultHistoryReturn = {
  history: [
    { date: "2026-03-08T10:00:00Z", position: 7 },
    { date: "2026-03-09T10:00:00Z", position: 6 },
    { date: "2026-03-10T10:00:00Z", position: 5 },
  ],
  isLoading: false,
  error: null,
  mutate: vi.fn(),
};

describe("KeywordPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseKeyword.mockReturnValue(defaultKeywordReturn);
    mockUseKeywordHistory.mockReturnValue(defaultHistoryReturn);
    mockTriggerRankCheck.mockResolvedValue({});
  });

  describe("Page Rendering", () => {
    it("renders the keyword as the page heading", () => {
      render(<KeywordPage />);
      expect(
        screen.getByRole("heading", { name: "technical seo audit", level: 1 })
      ).toBeInTheDocument();
    });

    it("renders the page description", () => {
      render(<KeywordPage />);
      expect(screen.getByText(/keyword details and ranking history/i)).toBeInTheDocument();
    });

    it("renders a back button to the keyword list", () => {
      render(<KeywordPage />);
      expect(screen.getByRole("button", { name: /back/i })).toBeInTheDocument();
    });

    it("renders an edit link to the keyword edit page", () => {
      render(<KeywordPage />);
      expect(screen.getByRole("link", { name: /edit/i })).toHaveAttribute(
        "href",
        "/sites/1/keywords/42/edit"
      );
    });
  });

  describe("Position Card", () => {
    it("renders the current position card", () => {
      render(<KeywordPage />);
      expect(screen.getByText("Current Position")).toBeInTheDocument();
    });

    it("renders the current position badge", () => {
      render(<KeywordPage />);
      expect(screen.getByText("#5")).toBeInTheDocument();
    });

    it("renders the position change indicator", () => {
      render(<KeywordPage />);
      expect(screen.getByText(/↑ 4/)).toBeInTheDocument();
    });

    it("renders the target url", () => {
      render(<KeywordPage />);
      expect(screen.getByText("https://example.com/audit")).toBeInTheDocument();
    });

    it("renders the last checked timestamp", () => {
      render(<KeywordPage />);
      expect(screen.getByText("Last checked")).toBeInTheDocument();
    });

    it("shows a not-ranked badge when the keyword has no position", () => {
      mockUseKeyword.mockReturnValue({
        ...defaultKeywordReturn,
        keyword: { ...mockKeyword, currentPosition: null, positionChange: null },
      });

      render(<KeywordPage />);
      expect(screen.getByText("Not ranked")).toBeInTheDocument();
    });
  });

  describe("Targeting Info", () => {
    it("renders the search engine", () => {
      render(<KeywordPage />);
      expect(screen.getByText("Search Engine")).toBeInTheDocument();
      // The value is rendered as-is and capitalized through CSS.
      expect(screen.getByText(/^google$/i)).toBeInTheDocument();
    });

    it("renders the location", () => {
      render(<KeywordPage />);
      expect(screen.getByText("Location")).toBeInTheDocument();
      // The value is rendered as-is and uppercased through CSS.
      expect(screen.getByText(/^us$/i)).toBeInTheDocument();
    });

    it("renders the device", () => {
      render(<KeywordPage />);
      expect(screen.getByText("Device")).toBeInTheDocument();
      // The value is rendered as-is and capitalized through CSS.
      expect(screen.getByText(/^desktop$/i)).toBeInTheDocument();
    });
  });

  describe("Ranking History", () => {
    it("renders the ranking history chart when history exists", () => {
      render(<KeywordPage />);
      expect(screen.getByTestId("ranking-history-chart")).toBeInTheDocument();
    });

    it("renders an empty state when there is no ranking history", () => {
      mockUseKeywordHistory.mockReturnValue({
        ...defaultHistoryReturn,
        history: [],
      });

      render(<KeywordPage />);
      expect(screen.getByText(/no ranking history available yet/i)).toBeInTheDocument();
      expect(screen.queryByTestId("ranking-history-chart")).not.toBeInTheDocument();
    });
  });

  describe("User Actions", () => {
    it("triggers a rank check when the check now button is clicked", async () => {
      const user = userEvent.setup();
      render(<KeywordPage />);

      await user.click(screen.getByRole("button", { name: /check now/i }));

      expect(mockTriggerRankCheck).toHaveBeenCalledWith(42);
    });

    it("navigates back to the keyword list when the back button is clicked", async () => {
      const user = userEvent.setup();
      render(<KeywordPage />);

      await user.click(screen.getByRole("button", { name: /back/i }));

      expect(mockPush).toHaveBeenCalledWith("/sites/1/keywords");
    });
  });

  describe("Loading States", () => {
    it("renders a skeleton while the keyword is loading", () => {
      mockUseKeyword.mockReturnValue({
        keyword: undefined,
        isLoading: true,
        error: null,
        mutate: mockMutate,
      });

      render(<KeywordPage />);

      expect(screen.queryByRole("heading", { level: 1 })).not.toBeInTheDocument();
      expect(screen.queryByText("Keyword not found")).not.toBeInTheDocument();
    });
  });

  describe("Error States", () => {
    it("renders a not-found message when the keyword does not exist", () => {
      mockUseKeyword.mockReturnValue({
        keyword: undefined,
        isLoading: false,
        error: new Error("Not found"),
        mutate: mockMutate,
      });

      render(<KeywordPage />);

      expect(screen.getByText("Keyword not found")).toBeInTheDocument();
    });

    it("offers a way back to the keyword list from the not-found state", async () => {
      mockUseKeyword.mockReturnValue({
        keyword: undefined,
        isLoading: false,
        error: new Error("Not found"),
        mutate: mockMutate,
      });

      const user = userEvent.setup();
      render(<KeywordPage />);

      await user.click(screen.getByRole("button", { name: /back to keywords/i }));

      expect(mockPush).toHaveBeenCalledWith("/sites/1/keywords");
    });
  });
});
