import { render, screen, waitFor, act } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { ProjectProvider, useProjectContext, useCurrentProject, useProjectActions } from "@/lib/contexts/ProjectContext";
import type { Project } from "@/lib/types/projects";

// Mock next/navigation
const mockPush = jest.fn();
const mockRouter = {
  push: mockPush,
};

jest.mock("next/navigation", () => ({
  useRouter: () => mockRouter,
}));

// Mock the projects API
const mockMutate = jest.fn();
jest.mock("@/lib/api/projects", () => ({
  useProjects: jest.fn(() => ({
    projects: mockProjectsData,
    isLoading: false,
    error: null,
    mutate: mockMutate,
  })),
  createProject: jest.fn(),
  updateProject: jest.fn(),
  deleteProject: jest.fn(),
  archiveProject: jest.fn(),
  restoreProject: jest.fn(),
  getStoredProjectId: jest.fn(() => null),
  storeProjectId: jest.fn(),
  clearStoredProjectId: jest.fn(),
}));

import {
  useProjects,
  createProject,
  updateProject,
  deleteProject,
  archiveProject,
  restoreProject,
  getStoredProjectId,
  storeProjectId,
  clearStoredProjectId,
} from "@/lib/api/projects";

const mockProjectsData: Project[] = [
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
];

// Test component that uses the context
function TestComponent() {
  const {
    projects,
    currentProject,
    isLoading,
    error,
    switchProject,
    createProject: handleCreate,
    updateProject: handleUpdate,
    deleteProject: handleDelete,
  } = useProjectContext();

  return (
    <div>
      <span data-testid="loading">{isLoading.toString()}</span>
      <span data-testid="project-count">{projects.length}</span>
      <span data-testid="current-project">{currentProject?.name ?? "none"}</span>
      <button onClick={() => switchProject(2)}>Switch to Beta</button>
      <button onClick={() => handleCreate({ name: "New Project" })}>Create</button>
      <button onClick={() => handleUpdate(1, { name: "Updated" })}>Update</button>
      <button onClick={() => handleDelete(1)}>Delete</button>
    </div>
  );
}

describe("ProjectContext", () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (useProjects as jest.Mock).mockReturnValue({
      projects: mockProjectsData,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });
    (getStoredProjectId as jest.Mock).mockReturnValue(null);
  });

  // UC-034: Provides projects list
  it("provides projects list", async () => {
    render(
      <ProjectProvider>
        <TestComponent />
      </ProjectProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("project-count")).toHaveTextContent("2");
    });
  });

  // UC-035: Provides current project
  it("provides current project", async () => {
    render(
      <ProjectProvider>
        <TestComponent />
      </ProjectProvider>
    );

    await waitFor(() => {
      // Should auto-select the default project
      expect(screen.getByTestId("current-project")).toHaveTextContent("Project Alpha");
    });
  });

  // UC-036: switchProject method
  it("switches project correctly", async () => {
    render(
      <ProjectProvider>
        <TestComponent />
      </ProjectProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("current-project")).toHaveTextContent("Project Alpha");
    });

    await act(async () => {
      screen.getByText("Switch to Beta").click();
    });

    expect(storeProjectId).toHaveBeenCalledWith(2);
    expect(mockPush).toHaveBeenCalledWith("/projects/2/dashboard");
  });

  // UC-037: createProject method
  it("creates project and navigates", async () => {
    const newProject: Project = {
      id: 3,
      name: "New Project",
      domain: null,
      notes: null,
      status: "active",
      isDefault: false,
      archivedAt: null,
      createdAt: "2026-01-03",
      updatedAt: "2026-01-03",
    };
    (createProject as jest.Mock).mockResolvedValue(newProject);

    render(
      <ProjectProvider>
        <TestComponent />
      </ProjectProvider>
    );

    await act(async () => {
      screen.getByText("Create").click();
    });

    expect(createProject).toHaveBeenCalledWith({ name: "New Project" });
    expect(mockPush).toHaveBeenCalledWith("/projects/3/dashboard");
  });

  // UC-038: updateProject method
  it("updates project correctly", async () => {
    const updatedProject = { ...mockProjectsData[0], name: "Updated" };
    (updateProject as jest.Mock).mockResolvedValue(updatedProject);

    render(
      <ProjectProvider>
        <TestComponent />
      </ProjectProvider>
    );

    await act(async () => {
      screen.getByText("Update").click();
    });

    expect(updateProject).toHaveBeenCalledWith(1, { name: "Updated" });
    expect(mockMutate).toHaveBeenCalled();
  });

  // UC-039: deleteProject method
  it("deletes project and switches to another", async () => {
    (deleteProject as jest.Mock).mockResolvedValue(undefined);

    render(
      <ProjectProvider>
        <TestComponent />
      </ProjectProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("current-project")).toHaveTextContent("Project Alpha");
    });

    await act(async () => {
      screen.getByText("Delete").click();
    });

    expect(deleteProject).toHaveBeenCalledWith(1);
    expect(mockPush).toHaveBeenCalledWith("/projects");
  });

  // UC-041: Fetch projects on mount (handled by useProjects mock)

  // Test useCurrentProject hook
  it("useCurrentProject hook returns current project", async () => {
    function CurrentProjectTest() {
      const { project, switchProject } = useCurrentProject();
      return (
        <div>
          <span data-testid="project">{project?.name ?? "none"}</span>
          <button onClick={() => switchProject(2)}>Switch</button>
        </div>
      );
    }

    render(
      <ProjectProvider>
        <CurrentProjectTest />
      </ProjectProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("project")).toHaveTextContent("Project Alpha");
    });
  });

  // Test useProjectActions hook
  it("useProjectActions hook returns action methods", async () => {
    function ActionsTest() {
      const { createProject, updateProject, deleteProject, archiveProject, restoreProject } = useProjectActions();
      return (
        <div>
          <button onClick={() => createProject({ name: "Test" })}>Create</button>
          <button onClick={() => updateProject(1, { name: "Updated" })}>Update</button>
          <button onClick={() => deleteProject(1)}>Delete</button>
          <button onClick={() => archiveProject(1)}>Archive</button>
          <button onClick={() => restoreProject(1)}>Restore</button>
        </div>
      );
    }

    (createProject as jest.Mock).mockResolvedValue({ id: 3, name: "Test" });
    (updateProject as jest.Mock).mockResolvedValue({ id: 1, name: "Updated" });
    (deleteProject as jest.Mock).mockResolvedValue(undefined);
    (archiveProject as jest.Mock).mockResolvedValue({ id: 1 });
    (restoreProject as jest.Mock).mockResolvedValue({ id: 1 });

    render(
      <ProjectProvider>
        <ActionsTest />
      </ProjectProvider>
    );

    await act(async () => {
      screen.getByText("Create").click();
    });
    expect(createProject).toHaveBeenCalled();

    await act(async () => {
      screen.getByText("Update").click();
    });
    expect(updateProject).toHaveBeenCalled();

    await act(async () => {
      screen.getByText("Delete").click();
    });
    expect(deleteProject).toHaveBeenCalled();

    await act(async () => {
      screen.getByText("Archive").click();
    });
    expect(archiveProject).toHaveBeenCalled();

    await act(async () => {
      screen.getByText("Restore").click();
    });
    expect(restoreProject).toHaveBeenCalled();
  });

  // Test error when used outside provider
  it("throws error when used outside provider", () => {
    const consoleError = jest.spyOn(console, "error").mockImplementation(() => {});

    function ErrorComponent() {
      useProjectContext();
      return null;
    }

    expect(() => render(<ErrorComponent />)).toThrow(
      "useProjectContext must be used within a ProjectProvider"
    );

    consoleError.mockRestore();
  });
});
