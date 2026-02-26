import React from "react";
import { render, screen, fireEvent, waitFor, act } from "@testing-library/react";
import { useRouter, useSearchParams } from "next/navigation";

// Mock next/navigation
jest.mock("next/navigation", () => ({
  useRouter: jest.fn(),
  useSearchParams: jest.fn(),
}));

// Mock the keywords API
jest.mock("@/lib/api/keywords", () => ({
  useKeywords: jest.fn(),
  bulkDeleteKeywords: jest.fn(),
  bulkUpdateKeywordStatus: jest.fn(),
  defaultKeywordMeta: { total: 0, page: 1, perPage: 20 },
}));

// Mock the metadata API
jest.mock("@/lib/api/metadata", () => ({
  useSERPFeatureOptions: jest.fn(() => ({
    options: {
      success: true,
      data: [
        { value: "featured_snippet", label: "Featured Snippet", icon: "trophy", description: "Answer box", ctrImpact: "+30-50%" },
        { value: "local_pack", label: "Local Pack", icon: "map-pin", description: "Map listings", ctrImpact: "High" },
      ],
    },
    isLoading: false,
    error: null,
  })),
  FALLBACK_SERP_FEATURES: [
    { value: "all", label: "All Features", icon: "filter", description: "Show all keywords", ctrImpact: "" },
  ],
}));

// Import after mocks
import KeywordList from "@/components/keywords/KeywordList";
import { useKeywords } from "@/lib/api/keywords";

const mockUseKeywords = useKeywords as jest.Mock;
const mockUseRouter = useRouter as jest.Mock;
const mockUseSearchParams = useSearchParams as jest.Mock;

describe("Keyword Filter Integration", () => {
  const mockPush = jest.fn();
  const mockReplace = jest.fn();

  const mockKeywords = [
    {
      id: 1,
      keyword: "seo tools",
      status: "active",
      currentPosition: 5,
      previousPosition: 7,
      positionChange: -2,
      lastCheckedAt: "2026-02-26T12:00:00Z",
      targetUrl: "https://example.com/seo",
      serpFeatures: { featured_snippet: true, local_pack: false },
    },
    {
      id: 2,
      keyword: "keyword research",
      status: "active",
      currentPosition: 12,
      previousPosition: 10,
      positionChange: 2,
      lastCheckedAt: "2026-02-26T12:00:00Z",
      targetUrl: "https://example.com/keywords",
      serpFeatures: { featured_snippet: false, local_pack: true },
    },
    {
      id: 3,
      keyword: "backlink checker",
      status: "paused",
      currentPosition: null,
      previousPosition: null,
      positionChange: null,
      lastCheckedAt: null,
      targetUrl: null,
      serpFeatures: {},
    },
  ];

  beforeEach(() => {
    jest.clearAllMocks();
    
    mockUseRouter.mockReturnValue({
      push: mockPush,
      replace: mockReplace,
    });
    
    mockUseSearchParams.mockReturnValue({
      get: jest.fn(() => null),
    });
    
    mockUseKeywords.mockReturnValue({
      keywords: mockKeywords,
      meta: { total: 3, page: 1, perPage: 20 },
      isLoading: false,
      error: null,
      mutate: jest.fn(),
    });
  });

  // AI-001: Fetches filtered results
  it("fetches keywords with SERP feature filter applied", async () => {
    render(<KeywordList siteId="1" />);
    
    // Select featured snippet filter
    const serpDropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(serpDropdown, { target: { value: "featured_snippet" } });
    
    // Verify API was called with filter
    await waitFor(() => {
      expect(mockUseKeywords).toHaveBeenCalledWith(
        "1",
        expect.objectContaining({
          serpFeature: "featured_snippet",
        })
      );
    });
  });

  // AI-002: Correct endpoint
  it("uses correct API endpoint structure", async () => {
    render(<KeywordList siteId="1" />);
    
    // The hook should be called with siteId
    expect(mockUseKeywords).toHaveBeenCalledWith(
      "1",
      expect.any(Object)
    );
  });

  // AI-003: Correct params
  it("passes serpFeature in query params", async () => {
    render(<KeywordList siteId="1" />);
    
    const serpDropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(serpDropdown, { target: { value: "local_pack" } });
    
    await waitFor(() => {
      expect(mockUseKeywords).toHaveBeenCalledWith(
        "1",
        expect.objectContaining({
          serpFeature: "local_pack",
        })
      );
    });
  });

  // AI-005: Results displayed
  it("displays filtered keywords in table", () => {
    render(<KeywordList siteId="1" />);
    
    expect(screen.getByText("seo tools")).toBeInTheDocument();
    expect(screen.getByText("keyword research")).toBeInTheDocument();
  });

  // CF-001: Feature + position
  it("combines SERP feature filter with sort by position", async () => {
    render(<KeywordList siteId="1" />);
    
    const serpDropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(serpDropdown, { target: { value: "featured_snippet" } });
    
    const sortDropdown = screen.getByLabelText("Sort By");
    fireEvent.change(sortDropdown, { target: { value: "position" } });
    
    await waitFor(() => {
      expect(mockUseKeywords).toHaveBeenCalledWith(
        "1",
        expect.objectContaining({
          serpFeature: "featured_snippet",
          sortBy: "position",
        })
      );
    });
  });

  // CF-002: Feature + search engine (status in this case)
  it("combines SERP feature filter with status filter", async () => {
    render(<KeywordList siteId="1" />);
    
    const serpDropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(serpDropdown, { target: { value: "featured_snippet" } });
    
    const statusDropdown = screen.getByLabelText("Status Filter");
    fireEvent.change(statusDropdown, { target: { value: "active" } });
    
    await waitFor(() => {
      expect(mockUseKeywords).toHaveBeenCalledWith(
        "1",
        expect.objectContaining({
          serpFeature: "featured_snippet",
          status: "active",
        })
      );
    });
  });

  // SM-002: URL reflects state
  it("updates URL params when filter changes", async () => {
    render(<KeywordList siteId="1" />);
    
    const serpDropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(serpDropdown, { target: { value: "featured_snippet" } });
    
    await waitFor(() => {
      expect(mockReplace).toHaveBeenCalled();
    });
  });

  // SM-001: Persists across navigation (URL sync)
  it("reads initial filter state from URL params", () => {
    mockUseSearchParams.mockReturnValue({
      get: (param: string) => {
        if (param === "serpFeature") return "local_pack";
        if (param === "status") return "active";
        return null;
      },
    });
    
    render(<KeywordList siteId="1" />);
    
    expect(mockUseKeywords).toHaveBeenCalledWith(
      "1",
      expect.objectContaining({
        serpFeature: "local_pack",
        status: "active",
      })
    );
  });

  // AI-004: Loading state
  it("shows loading indicator while fetching", () => {
    mockUseKeywords.mockReturnValue({
      keywords: [],
      meta: { total: 0, page: 1, perPage: 20 },
      isLoading: true,
      error: null,
      mutate: jest.fn(),
    });
    
    render(<KeywordList siteId="1" />);
    
    expect(screen.getByText("Loading keywords...")).toBeInTheDocument();
  });

  // Error handling
  it("shows error message when API fails", () => {
    mockUseKeywords.mockReturnValue({
      keywords: [],
      meta: { total: 0, page: 1, perPage: 20 },
      isLoading: false,
      error: new Error("Failed to fetch"),
      mutate: jest.fn(),
    });
    
    render(<KeywordList siteId="1" />);
    
    expect(screen.getByText(/Failed to load keywords/i)).toBeInTheDocument();
  });

  // Empty state
  it("shows empty state when no keywords match filter", () => {
    mockUseKeywords.mockReturnValue({
      keywords: [],
      meta: { total: 0, page: 1, perPage: 20 },
      isLoading: false,
      error: null,
      mutate: jest.fn(),
    });
    
    render(<KeywordList siteId="1" />);
    
    expect(screen.getByText("No keywords found")).toBeInTheDocument();
  });

  // SERP features display
  it("displays SERP feature badges in table", () => {
    render(<KeywordList siteId="1" />);
    
    // Check that SERP features column exists
    expect(screen.getByText("SERP Features")).toBeInTheDocument();
  });

  // Checkbox integration
  it("applies present=true filter when checkbox is checked", async () => {
    render(<KeywordList siteId="1" />);
    
    const checkbox = screen.getByRole("checkbox", { name: /show only keywords with serp features/i });
    fireEvent.click(checkbox);
    
    await waitFor(() => {
      expect(mockUseKeywords).toHaveBeenCalledWith(
        "1",
        expect.objectContaining({
          present: true,
        })
      );
    });
  });

  // Combined filters
  it("applies multiple filters together", async () => {
    render(<KeywordList siteId="1" />);
    
    // Apply SERP feature filter
    const serpDropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(serpDropdown, { target: { value: "featured_snippet" } });
    
    // Check "with features" checkbox
    const checkbox = screen.getByRole("checkbox", { name: /show only keywords with serp features/i });
    fireEvent.click(checkbox);
    
    // Apply status filter
    const statusDropdown = screen.getByLabelText("Status Filter");
    fireEvent.change(statusDropdown, { target: { value: "active" } });
    
    await waitFor(() => {
      expect(mockUseKeywords).toHaveBeenCalledWith(
        "1",
        expect.objectContaining({
          serpFeature: "featured_snippet",
          present: true,
          status: "active",
        })
      );
    });
  });

  // Clear filters
  it("clears all filters when clear button clicked", async () => {
    mockUseSearchParams.mockReturnValue({
      get: (param: string) => {
        if (param === "serpFeature") return "featured_snippet";
        return null;
      },
    });
    
    render(<KeywordList siteId="1" />);
    
    const clearButton = screen.getByText("Clear Filters");
    fireEvent.click(clearButton);
    
    await waitFor(() => {
      expect(mockUseKeywords).toHaveBeenCalledWith(
        "1",
        expect.objectContaining({
          serpFeature: undefined,
          status: "all",
          sortBy: "keyword",
          sortOrder: "asc",
        })
      );
    });
  });

  // Pagination with filters
  it("resets to page 1 when filter changes", async () => {
    const mockMutate = jest.fn();
    mockUseKeywords.mockReturnValue({
      keywords: mockKeywords,
      meta: { total: 50, page: 2, perPage: 20 },
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });
    
    render(<KeywordList siteId="1" />);
    
    // Change filter
    const serpDropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(serpDropdown, { target: { value: "featured_snippet" } });
    
    // Should reset to page 1
    await waitFor(() => {
      expect(mockUseKeywords).toHaveBeenCalledWith(
        "1",
        expect.objectContaining({
          page: 1,
          serpFeature: "featured_snippet",
        })
      );
    });
  });
});
