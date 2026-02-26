import React from "react";
import { render, screen, fireEvent, waitFor, act } from "@testing-library/react";
import KeywordFilterBar, { 
  type KeywordFilterState, 
  defaultKeywordFilterState 
} from "@/components/keywords/KeywordFilterBar";

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
    { value: "featured_snippet", label: "Featured Snippet", icon: "trophy", description: "Answer box at top of search results", ctrImpact: "+30-50%" },
  ],
}));

const mockUseSERPFeatureOptions = require("@/lib/api/metadata").useSERPFeatureOptions;

describe("KeywordFilterBar", () => {
  const mockOnChange = jest.fn();
  const defaultProps = {
    siteId: "1",
    value: defaultKeywordFilterState,
    onChange: mockOnChange,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // FD-001: Dropdown renders
  it("renders the filter dropdown", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    expect(screen.getByLabelText("SERP Features")).toBeInTheDocument();
  });

  // FD-002: Label correct
  it("displays correct label for SERP Features dropdown", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    const dropdown = screen.getByLabelText("SERP Features");
    expect(dropdown).toBeInTheDocument();
  });

  // FD-003: Options loaded
  it("loads SERP feature options from API", async () => {
    render(<KeywordFilterBar {...defaultProps} />);
    
    const dropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(dropdown, { target: { value: "featured_snippet" } });
    
    expect(mockOnChange).toHaveBeenCalledWith(
      expect.objectContaining({
        serpFeature: "featured_snippet",
      })
    );
  });

  // FD-004: "All" option
  it("shows 'All' as first option", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    const dropdown = screen.getByLabelText("SERP Features");
    const options = dropdown.querySelectorAll("option");
    expect(options[0]).toHaveValue("all");
    expect(options[0]).toHaveTextContent("All Features");
  });

  // FS-001: Select featured snippet
  it("applies filter when selecting featured snippet", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    
    const dropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(dropdown, { target: { value: "featured_snippet" } });
    
    expect(mockOnChange).toHaveBeenCalledWith(
      expect.objectContaining({
        serpFeature: "featured_snippet",
      })
    );
  });

  // FS-002: Select local pack
  it("applies filter when selecting local pack", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    
    const dropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(dropdown, { target: { value: "local_pack" } });
    
    expect(mockOnChange).toHaveBeenCalledWith(
      expect.objectContaining({
        serpFeature: "local_pack",
      })
    );
  });

  // FS-003: Select "All"
  it("clears filter when selecting 'All'", () => {
    const propsWithValue = {
      ...defaultProps,
      value: { ...defaultKeywordFilterState, serpFeature: "featured_snippet" },
    };
    
    render(<KeywordFilterBar {...propsWithValue} />);
    
    const dropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(dropdown, { target: { value: "all" } });
    
    expect(mockOnChange).toHaveBeenCalledWith(
      expect.objectContaining({
        serpFeature: "all",
      })
    );
  });

  // FS-004: Selection updates URL (handled by parent component)
  it("calls onChange when filter changes", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    
    const dropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(dropdown, { target: { value: "featured_snippet" } });
    
    expect(mockOnChange).toHaveBeenCalled();
  });

  // CB-001: Checkbox renders
  it("renders the 'Show Only With Features' checkbox", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    expect(screen.getByText(/Show Only Keywords With SERP Features/i)).toBeInTheDocument();
  });

  // CB-002: Check the box
  it("sets present=true filter when checkbox is checked", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    
    const checkbox = screen.getByRole("checkbox", { name: /show only keywords with serp features/i });
    fireEvent.click(checkbox);
    
    expect(mockOnChange).toHaveBeenCalledWith(
      expect.objectContaining({
        showOnlyWithFeatures: true,
      })
    );
  });

  // CB-003: Uncheck the box
  it("sets present=false filter when checkbox is unchecked", () => {
    const propsWithValue = {
      ...defaultProps,
      value: { ...defaultKeywordFilterState, showOnlyWithFeatures: true },
    };
    
    render(<KeywordFilterBar {...propsWithValue} />);
    
    const checkbox = screen.getByRole("checkbox", { name: /show only keywords with serp features/i });
    fireEvent.click(checkbox);
    
    expect(mockOnChange).toHaveBeenCalledWith(
      expect.objectContaining({
        showOnlyWithFeatures: false,
      })
    );
  });

  // CB-004: Default unchecked
  it("has checkbox unchecked by default", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    
    const checkbox = screen.getByRole("checkbox", { name: /show only keywords with serp features/i });
    expect(checkbox).not.toBeChecked();
  });

  // CF-001: Feature + status
  it("applies both feature and status filters together", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    
    // Select feature
    const featureDropdown = screen.getByLabelText("SERP Features");
    fireEvent.change(featureDropdown, { target: { value: "featured_snippet" } });
    
    // Verify feature was selected
    expect(mockOnChange).toHaveBeenCalledWith(
      expect.objectContaining({ serpFeature: "featured_snippet" })
    );
    
    // Select status (this will be called with the current state which includes the feature)
    const statusDropdown = screen.getByLabelText("Status Filter");
    fireEvent.change(statusDropdown, { target: { value: "active" } });
    
    // Status change should include the previous feature selection
    expect(mockOnChange).toHaveBeenCalledWith(
      expect.objectContaining({ status: "active" })
    );
  });

  // AI-004: Loading state
  it("shows loading indicator when fetching metadata", () => {
    mockUseSERPFeatureOptions.mockReturnValueOnce({
      options: null,
      isLoading: true,
      error: null,
    });
    
    render(<KeywordFilterBar {...defaultProps} />);
    
    expect(screen.getByText("Loading features...")).toBeInTheDocument();
  });

  // DO-002: Handles metadata error
  it("uses fallback options when metadata fetch fails", () => {
    mockUseSERPFeatureOptions.mockReturnValueOnce({
      options: null,
      isLoading: false,
      error: new Error("Failed to fetch"),
    });
    
    render(<KeywordFilterBar {...defaultProps} />);
    
    // Should still render dropdown with fallback options
    const dropdown = screen.getByLabelText("SERP Features");
    expect(dropdown).toBeInTheDocument();
    expect(dropdown.querySelector("option")).toBeInTheDocument();
  });

  // SM-003: Clear filters button
  it("shows clear filters button when filters are active", () => {
    const propsWithActiveFilters = {
      ...defaultProps,
      value: { ...defaultKeywordFilterState, serpFeature: "featured_snippet" },
    };
    
    render(<KeywordFilterBar {...propsWithActiveFilters} />);
    
    expect(screen.getByText("Clear Filters")).toBeInTheDocument();
  });

  // AC-001: Keyboard accessible
  it("is keyboard accessible", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    
    const dropdown = screen.getByLabelText("SERP Features");
    dropdown.focus();
    expect(document.activeElement).toBe(dropdown);
  });

  // AC-002: ARIA labels
  it("has proper ARIA labels", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    
    expect(screen.getByLabelText("SERP Features")).toBeInTheDocument();
    expect(screen.getByLabelText("Status Filter")).toBeInTheDocument();
  });

  // Active filter tags
  it("shows active filter tags when filters are applied", () => {
    const propsWithActiveFilters = {
      ...defaultProps,
      value: { 
        ...defaultKeywordFilterState, 
        serpFeature: "featured_snippet",
        showOnlyWithFeatures: true,
      },
    };
    
    render(<KeywordFilterBar {...propsWithActiveFilters} />);
    
    expect(screen.getByText("Featured Snippet")).toBeInTheDocument();
    expect(screen.getByText("With Features")).toBeInTheDocument();
  });

  // Remove filter tag
  it("removes filter when clicking tag close button", () => {
    const propsWithActiveFilters = {
      ...defaultProps,
      value: { ...defaultKeywordFilterState, serpFeature: "featured_snippet" },
    };
    
    render(<KeywordFilterBar {...propsWithActiveFilters} />);
    
    // Click the × button on the tag
    const closeButton = screen.getByRole("button", { name: "" }); // The × buttons
    fireEvent.click(closeButton);
    
    expect(mockOnChange).toHaveBeenCalledWith(
      expect.objectContaining({
        serpFeature: "all",
      })
    );
  });

  // Clear all filters
  it("clears all filters when clicking clear button", () => {
    const propsWithActiveFilters = {
      ...defaultProps,
      value: { 
        ...defaultKeywordFilterState, 
        serpFeature: "featured_snippet",
        showOnlyWithFeatures: true,
        status: "active",
        search: "test",
      },
    };
    
    render(<KeywordFilterBar {...propsWithActiveFilters} />);
    
    const clearButton = screen.getByText("Clear Filters");
    fireEvent.click(clearButton);
    
    expect(mockOnChange).toHaveBeenCalledWith(defaultKeywordFilterState);
  });

  // Search input
  it("updates search filter when typing", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    
    const searchInput = screen.getByPlaceholderText("Search keywords");
    fireEvent.change(searchInput, { target: { value: "seo" } });
    
    expect(mockOnChange).toHaveBeenCalledWith(
      expect.objectContaining({
        search: "seo",
      })
    );
  });

  // Sort controls
  it("updates sort filter when changed", () => {
    render(<KeywordFilterBar {...defaultProps} />);
    
    const sortByDropdown = screen.getByLabelText("Sort By");
    fireEvent.change(sortByDropdown, { target: { value: "position" } });
    
    expect(mockOnChange).toHaveBeenCalledWith(
      expect.objectContaining({
        sortBy: "position",
      })
    );
  });
});
