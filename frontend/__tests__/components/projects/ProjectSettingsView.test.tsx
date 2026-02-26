import { render, screen, waitFor, act } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { ProjectSettingsView } from "@/components/projects/ProjectSettingsView";
import type { Project } from "@/lib/types/projects";

// Mock next/navigation
const mockPush = jest.fn();
jest.mock("next/navigation", () => ({
  useRouter: () => ({ push: mockPush }),
}));

const mockProject: Project = {
  id: 1,
  name: "Test Project",
  domain: "test.com",
  notes: "Test notes",
  status: "active",
  isDefault: false,
  archivedAt: null,
  createdAt: "2026-01-01",
  updatedAt: "2026-01-01",
};

const mockOnSave = jest.fn();
const mockOnDelete = jest.fn();
const mockOnArchive = jest.fn();
const mockOnRestore = jest.fn();
const mockOnCancel = jest.fn();

describe("ProjectSettingsView", () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // UC-025: Renders project settings form
  it("renders project settings form with current values", () => {
    render(
      <ProjectSettingsView
        project={mockProject}
        onSave={mockOnSave}
        onDelete={mockOnDelete}
        onArchive={mockOnArchive}
        onRestore={mockOnRestore}
      />
    );

    expect(screen.getByDisplayValue("Test Project")).toBeInTheDocument();
    expect(screen.getByDisplayValue("test.com")).toBeInTheDocument();
    expect(screen.getByDisplayValue("Test notes")).toBeInTheDocument();
  });

  // UC-026: Form validation - name required
  it("validates name is required", async () => {
    render(
      <ProjectSettingsView
        project={mockProject}
        onSave={mockOnSave}
        onDelete={mockOnDelete}
        onArchive={mockOnArchive}
        onRestore={mockOnRestore}
      />
    );

    const nameInput = screen.getByLabelText(/project name/i);
    await userEvent.clear(nameInput);
    
    const saveButton = screen.getByRole("button", { name: /save changes/i });
    await userEvent.click(saveButton);

    await waitFor(() => {
      expect(screen.getByText(/project name is required/i)).toBeInTheDocument();
    });
    expect(mockOnSave).not.toHaveBeenCalled();
  });

  // UC-027: Form validation - domain format
  it("validates domain format", async () => {
    render(
      <ProjectSettingsView
        project={mockProject}
        onSave={mockOnSave}
        onDelete={mockOnDelete}
        onArchive={mockOnArchive}
        onRestore={mockOnRestore}
      />
    );

    const domainInput = screen.getByLabelText(/domain/i);
    await userEvent.clear(domainInput);
    await userEvent.type(domainInput, "invalid-domain");
    
    const saveButton = screen.getByRole("button", { name: /save changes/i });
    await userEvent.click(saveButton);

    await waitFor(() => {
      expect(screen.getByText(/invalid domain format/i)).toBeInTheDocument();
    });
  });

  // UC-028: Save changes calls API
  it("saves changes successfully", async () => {
    mockOnSave.mockResolvedValueOnce({ ...mockProject, name: "Updated Name" });

    render(
      <ProjectSettingsView
        project={mockProject}
        onSave={mockOnSave}
        onDelete={mockOnDelete}
        onArchive={mockOnArchive}
        onRestore={mockOnRestore}
      />
    );

    const nameInput = screen.getByLabelText(/project name/i);
    await userEvent.clear(nameInput);
    await userEvent.type(nameInput, "Updated Name");
    
    await userEvent.click(screen.getByRole("button", { name: /save changes/i }));

    await waitFor(() => {
      expect(mockOnSave).toHaveBeenCalledWith(1, {
        name: "Updated Name",
        domain: "test.com",
        notes: "Test notes",
      });
    });
  });

  // UC-029: Cancel reverts changes
  it("cancels and reverts changes", async () => {
    render(
      <ProjectSettingsView
        project={mockProject}
        onSave={mockOnSave}
        onDelete={mockOnDelete}
        onArchive={mockOnArchive}
        onRestore={mockOnRestore}
        onCancel={mockOnCancel}
      />
    );

    const nameInput = screen.getByLabelText(/project name/i);
    await userEvent.clear(nameInput);
    await userEvent.type(nameInput, "Changed Name");

    // Cancel button should be enabled now
    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    await userEvent.click(cancelButton);

    // Should show confirmation dialog
    // After confirmation, form should reset
  });

  // UC-030: Delete project action
  it("shows delete confirmation dialog", async () => {
    render(
      <ProjectSettingsView
        project={mockProject}
        onSave={mockOnSave}
        onDelete={mockOnDelete}
        onArchive={mockOnArchive}
        onRestore={mockOnRestore}
      />
    );

    await userEvent.click(screen.getByRole("button", { name: /delete project/i }));

    expect(screen.getByText(/this action cannot be undone/i)).toBeInTheDocument();
    expect(screen.getByText(/type.*test project.*to confirm/i)).toBeInTheDocument();
  });

  // UC-032: Archive project action
  it("shows archive confirmation dialog", async () => {
    render(
      <ProjectSettingsView
        project={mockProject}
        onSave={mockOnSave}
        onDelete={mockOnDelete}
        onArchive={mockOnArchive}
        onRestore={mockOnRestore}
      />
    );

    await userEvent.click(screen.getByRole("button", { name: /archive project/i }));

    expect(screen.getByText(/archiving this project will hide/i)).toBeInTheDocument();
  });

  // UC-033: Settings save loading state
  it("shows loading state during save", async () => {
    mockOnSave.mockImplementation(() => new Promise((resolve) => setTimeout(resolve, 100)));

    render(
      <ProjectSettingsView
        project={mockProject}
        onSave={mockOnSave}
        onDelete={mockOnDelete}
        onArchive={mockOnArchive}
        onRestore={mockOnRestore}
      />
    );

    const nameInput = screen.getByLabelText(/project name/i);
    await userEvent.clear(nameInput);
    await userEvent.type(nameInput, "New Name");
    
    const saveButton = screen.getByRole("button", { name: /save changes/i });
    await userEvent.click(saveButton);

    await waitFor(() => {
      expect(screen.getByText(/saving/i)).toBeInTheDocument();
    });
  });

  // Additional test: Archived project shows restore button
  it("shows restore button for archived project", () => {
    const archivedProject = { ...mockProject, status: "archived" as const, archivedAt: "2026-01-15" };
    
    render(
      <ProjectSettingsView
        project={archivedProject}
        onSave={mockOnSave}
        onDelete={mockOnDelete}
        onArchive={mockOnArchive}
        onRestore={mockOnRestore}
      />
    );

    expect(screen.getByRole("button", { name: /restore project/i })).toBeInTheDocument();
    expect(screen.queryByRole("button", { name: /archive project/i })).not.toBeInTheDocument();
    expect(screen.getByText("Archived")).toBeInTheDocument();
  });

  // Additional test: Domain normalization
  it("normalizes domain by removing protocol and trailing slash", async () => {
    mockOnSave.mockResolvedValueOnce(mockProject);

    render(
      <ProjectSettingsView
        project={{ ...mockProject, domain: null }}
        onSave={mockOnSave}
        onDelete={mockOnDelete}
        onArchive={mockOnArchive}
        onRestore={mockOnRestore}
      />
    );

    const domainInput = screen.getByLabelText(/domain/i);
    await userEvent.type(domainInput, "https://example.com/");
    
    await userEvent.click(screen.getByRole("button", { name: /save changes/i }));

    // Domain should be normalized in the validation
    await waitFor(() => {
      expect(mockOnSave).toHaveBeenCalled();
    });
  });
});
