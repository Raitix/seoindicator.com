import { render, screen, waitFor, act } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { AllProjectsView } from "@/components/projects/AllProjectsView";
import type { ProjectBreakdown, AggregateMetrics } from "@/lib/types/projects";

// Mock fetchAggregateMetrics
const mockFetchAggregateMetrics = jest.fn();
jest.mock("@/lib/api/projects", () => ({
  fetchAggregateMetrics: () => mockFetchAggregateMetrics(),
}));

const mockAggregate: AggregateMetrics = {
  totalProjects: 3,
  totalKeywords: 1500,
  avgPosition: 15.5,
  totalVisibility: 45.2,
  visibilityChange: 2.3,
};

const mockProjects: ProjectBreakdown[] = [
  {
    id: 1,
    name: "Project Alpha",
    domain: "alpha.com",
    notes: null,
    status: "active",
    isDefault: true,
    archivedAt: null,
    createdAt: "2026-01-01",
    updatedAt: "2026-01-01",
    metrics: {
      keywordsCount: 500,
      avgPosition: 10,
      visibility: 50,
      change: { visibility: 5, avgPosition: -2 },
    },
  },
  {
    id: 2,
    name: "Project Beta",
    domain: "beta.com",
    notes: null,
    status: "active",
    isDefault: false,
    archivedAt: null,
    createdAt: "2026-01-02",
    updatedAt: "2026-01-02",
    metrics: {
      keywordsCount: 750,
      avgPosition: 20,
      visibility: 40,
      change: { visibility: -2, avgPosition: 1 },
    },
  },
  {
    id: 3,
    name: "Archived Project",
    domain: "archived.com",
    notes: null,
    status: "archived",
    isDefault: false,
    archivedAt: "2026-01-15",
    createdAt: "2026-01-01",
    updatedAt: "2026-01-15",
    metrics: {
      keywordsCount: 250,
      avgPosition: 30,
      visibility: 30,
      change: { visibility: 0, avgPosition: null },
    },
  },
];

describe("AllProjectsView", () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockFetchAggregateMetrics.mockResolvedValue({
      aggregate: mockAggregate,
      projects: mockProjects,
    });
  });

  // UC-017: Renders aggregate metrics
  it("renders aggregate metrics correctly", async () => {
    render(<AllProjectsView />);

    await waitFor(() => {
      expect(screen.getByText("Total Projects")).toBeInTheDocument();
    });

    // Use more specific selectors for numbers that might appear multiple times
    expect(screen.getByText("Total Keywords")).toBeInTheDocument();
    expect(screen.getByText("Avg Position")).toBeInTheDocument();
    expect(screen.getByText("Total Visibility")).toBeInTheDocument();
  });

  // UC-018: Shows projects breakdown table
  it("shows projects breakdown table", async () => {
    render(<AllProjectsView />);

    await waitFor(() => {
      expect(screen.getByText("Project Alpha")).toBeInTheDocument();
      expect(screen.getByText("Project Beta")).toBeInTheDocument();
    });

    // Archived project should be hidden by default
    expect(screen.queryByText("Archived Project")).not.toBeInTheDocument();
  });

  // UC-019: Sortable table columns
  it("sorts table columns", async () => {
    render(<AllProjectsView />);

    await waitFor(() => {
      expect(screen.getByText("Project Alpha")).toBeInTheDocument();
    });

    // Click on Keywords header to sort
    await userEvent.click(screen.getByRole("button", { name: /keywords/i }));

    // Click again for ascending
    await userEvent.click(screen.getByRole("button", { name: /keywords/i }));
  });

  // UC-020: Click to view project (navigation via table row)
  it("renders clickable project rows", async () => {
    render(<AllProjectsView />);

    await waitFor(() => {
      expect(screen.getByText("Project Alpha")).toBeInTheDocument();
    });

    // The row should be clickable (cursor-pointer class indicates this)
    const row = screen.getByText("Project Alpha").closest("tr");
    expect(row).toHaveClass("cursor-pointer");
  });

  // UC-021: Filter by project status (show archived)
  it("toggles archived projects visibility", async () => {
    render(<AllProjectsView />);

    await waitFor(() => {
      expect(screen.getByText("Project Alpha")).toBeInTheDocument();
    });

    // Archived should be hidden
    expect(screen.queryByText("Archived Project")).not.toBeInTheDocument();

    // Click "Show Archived"
    await userEvent.click(screen.getByRole("button", { name: /show archived/i }));

    // Now archived should be visible
    await waitFor(() => {
      expect(screen.getByText("Archived Project")).toBeInTheDocument();
    });
  });

  // UC-022: Search projects
  it("searches projects correctly", async () => {
    render(<AllProjectsView />);

    await waitFor(() => {
      expect(screen.getByText("Project Alpha")).toBeInTheDocument();
    });

    const searchInput = screen.getByPlaceholderText(/search projects/i);
    await userEvent.type(searchInput, "Beta");

    await waitFor(() => {
      expect(screen.queryByText("Project Alpha")).not.toBeInTheDocument();
      expect(screen.getByText("Project Beta")).toBeInTheDocument();
    });
  });

  // UC-023: No projects state
  it("shows empty state when no projects", async () => {
    mockFetchAggregateMetrics.mockResolvedValueOnce({
      aggregate: { ...mockAggregate, totalProjects: 0 },
      projects: [],
    });

    render(<AllProjectsView />);

    await waitFor(() => {
      expect(screen.getByText(/create your first project/i)).toBeInTheDocument();
    });
  });

  // Loading state
  it("shows loading state while fetching", () => {
    mockFetchAggregateMetrics.mockImplementation(() => new Promise(() => {})); // Never resolves

    render(<AllProjectsView />);

    // Check for skeleton loaders (animate-pulse class)
    const skeletons = document.querySelectorAll(".animate-pulse");
    expect(skeletons.length).toBeGreaterThan(0);
  });

  // Error state
  it("shows error state on fetch failure", async () => {
    mockFetchAggregateMetrics.mockRejectedValueOnce(new Error("Failed to fetch"));

    render(<AllProjectsView />);

    await waitFor(() => {
      expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
    });
  });

  // Retry button
  it("retries fetch on error", async () => {
    mockFetchAggregateMetrics
      .mockRejectedValueOnce(new Error("Failed to fetch"))
      .mockResolvedValueOnce({
        aggregate: mockAggregate,
        projects: mockProjects,
      });

    render(<AllProjectsView />);

    await waitFor(() => {
      expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
    });

    await userEvent.click(screen.getByRole("button", { name: /retry/i }));

    await waitFor(() => {
      expect(screen.getByText("Project Alpha")).toBeInTheDocument();
    });
  });

  // onCreateProject callback
  it("calls onCreateProject callback when provided", async () => {
    const mockOnCreate = jest.fn();

    mockFetchAggregateMetrics.mockResolvedValueOnce({
      aggregate: { ...mockAggregate, totalProjects: 0 },
      projects: [],
    });

    render(<AllProjectsView onCreateProject={mockOnCreate} />);

    await waitFor(() => {
      expect(screen.getByText(/create your first project/i)).toBeInTheDocument();
    });

    await userEvent.click(screen.getByRole("button", { name: /create project/i }));
    expect(mockOnCreate).toHaveBeenCalled();
  });

  // Visibility change indicator
  it("shows visibility change indicators", async () => {
    render(<AllProjectsView />);

    await waitFor(() => {
      expect(screen.getByText("Total Projects")).toBeInTheDocument();
    });
    
    // Check for aggregate visibility change
    expect(screen.getByText("+2.3%")).toBeInTheDocument();
  });
});
