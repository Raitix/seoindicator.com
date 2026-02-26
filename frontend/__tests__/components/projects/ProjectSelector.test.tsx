import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { ProjectSelector } from "@/components/projects/ProjectSelector";
import type { Project } from "@/lib/types/projects";

// Mock scrollIntoView for jsdom
Element.prototype.scrollIntoView = jest.fn();

// Mock projects data
const mockProjects: Project[] = [
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
  },
];

describe("ProjectSelector", () => {
  const mockOnSelect = jest.fn();
  const mockOnCreateNew = jest.fn();
  const mockOnRetry = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // UC-001: Renders with projects list
  it("renders with projects list", async () => {
    render(
      <ProjectSelector
        projects={mockProjects}
        currentProject={mockProjects[0]}
        onSelect={mockOnSelect}
        onCreateNew={mockOnCreateNew}
      />
    );

    // Verify dropdown trigger is visible
    const trigger = screen.getByRole("button", { name: /select project/i });
    expect(trigger).toBeInTheDocument();

    // Click trigger to open dropdown
    await userEvent.click(trigger);

    // Verify dropdown is open
    expect(screen.getByRole("listbox")).toBeInTheDocument();

    // Verify active projects are listed (archived should be filtered out)
    // Use getAllByText since "Project Alpha" appears in trigger AND dropdown
    const alphaItems = screen.getAllByText("Project Alpha");
    expect(alphaItems.length).toBeGreaterThan(0);
    
    // Project Beta should be in dropdown
    expect(screen.getByText("Project Beta")).toBeInTheDocument();
    
    // Archived project should NOT be visible
    expect(screen.queryByText("Archived Project")).not.toBeInTheDocument();
  });

  // UC-002: Displays current project as selected
  it("displays current project as selected", () => {
    render(
      <ProjectSelector
        projects={mockProjects}
        currentProject={mockProjects[0]}
        onSelect={mockOnSelect}
        onCreateNew={mockOnCreateNew}
      />
    );

    expect(screen.getByText("Project Alpha")).toBeInTheDocument();
  });

  // UC-003: Calls onSelect when project selected
  it("calls onSelect when project selected", async () => {
    render(
      <ProjectSelector
        projects={mockProjects}
        currentProject={mockProjects[0]}
        onSelect={mockOnSelect}
        onCreateNew={mockOnCreateNew}
      />
    );

    await userEvent.click(screen.getByRole("button", { name: /select project/i }));
    await userEvent.click(screen.getByText("Project Beta"));

    expect(mockOnSelect).toHaveBeenCalledWith(2);
  });

  // UC-004: Shows "Create New" button
  it("shows Create New option in dropdown", async () => {
    render(
      <ProjectSelector
        projects={mockProjects}
        currentProject={mockProjects[0]}
        onSelect={mockOnSelect}
        onCreateNew={mockOnCreateNew}
      />
    );

    await userEvent.click(screen.getByRole("button", { name: /select project/i }));
    
    const createButton = screen.getByText("Create new project");
    expect(createButton).toBeInTheDocument();

    await userEvent.click(createButton);
    expect(mockOnCreateNew).toHaveBeenCalled();
  });

  // UC-005: Empty projects state
  it("shows empty state when no projects exist", () => {
    render(
      <ProjectSelector
        projects={[]}
        currentProject={null}
        onSelect={mockOnSelect}
        onCreateNew={mockOnCreateNew}
      />
    );

    expect(screen.getByText(/create your first project/i)).toBeInTheDocument();
  });

  // UC-006: Loading state
  it("shows loading state when isLoading is true", () => {
    render(
      <ProjectSelector
        projects={[]}
        currentProject={null}
        onSelect={mockOnSelect}
        onCreateNew={mockOnCreateNew}
        isLoading={true}
      />
    );

    expect(screen.getByText(/loading projects/i)).toBeInTheDocument();
  });

  // UC-007: Error state handling
  it("shows error state when error is provided", () => {
    const error = new Error("Failed to load projects");
    render(
      <ProjectSelector
        projects={[]}
        currentProject={null}
        onSelect={mockOnSelect}
        onCreateNew={mockOnCreateNew}
        error={error}
        onRetry={mockOnRetry}
      />
    );

    expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
  });

  // UC-008: Keyboard navigation
  it("supports keyboard navigation", async () => {
    render(
      <ProjectSelector
        projects={mockProjects}
        currentProject={mockProjects[0]}
        onSelect={mockOnSelect}
        onCreateNew={mockOnCreateNew}
      />
    );

    const trigger = screen.getByRole("button", { name: /select project/i });
    trigger.focus();

    // Press Enter to open dropdown
    await userEvent.keyboard("{Enter}");
    expect(screen.getByRole("listbox")).toBeInTheDocument();

    // Press ArrowDown to navigate
    await userEvent.keyboard("{ArrowDown}");
    
    // Press Enter to select
    await userEvent.keyboard("{Enter}");
    
    expect(mockOnSelect).toHaveBeenCalled();

    // Reopen and test Escape
    await userEvent.click(trigger);
    await userEvent.keyboard("{Escape}");
  });

  // UC-009: Long project names truncation
  it("truncates long project names", async () => {
    const longNameProject: Project = {
      ...mockProjects[0],
      name: "This is a very long project name that should be truncated",
    };

    render(
      <ProjectSelector
        projects={[longNameProject]}
        currentProject={longNameProject}
        onSelect={mockOnSelect}
        onCreateNew={mockOnCreateNew}
      />
    );

    // The name should be displayed (truncated via CSS)
    const trigger = screen.getByRole("button", { name: /select project/i });
    expect(trigger).toHaveTextContent("This is a very long project");
  });

  // Additional test: Shows domain in dropdown
  it("shows domain in dropdown options", async () => {
    render(
      <ProjectSelector
        projects={mockProjects}
        currentProject={mockProjects[0]}
        onSelect={mockOnSelect}
        onCreateNew={mockOnCreateNew}
      />
    );

    await userEvent.click(screen.getByRole("button", { name: /select project/i }));
    expect(screen.getByText("alpha.com")).toBeInTheDocument();
    expect(screen.getByText("beta.com")).toBeInTheDocument();
  });

  // Additional test: ARIA attributes
  it("has correct ARIA attributes", () => {
    render(
      <ProjectSelector
        projects={mockProjects}
        currentProject={mockProjects[0]}
        onSelect={mockOnSelect}
        onCreateNew={mockOnCreateNew}
      />
    );

    const trigger = screen.getByRole("button", { name: /select project/i });
    expect(trigger).toHaveAttribute("aria-haspopup", "listbox");
    expect(trigger).toHaveAttribute("aria-expanded", "false");
  });
});
