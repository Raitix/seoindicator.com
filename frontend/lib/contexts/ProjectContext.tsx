"use client";

import * as React from "react";
import { useRouter } from "next/navigation";

import type {
  Project,
  ProjectContextValue,
  ProjectCreatePayload,
  ProjectUpdatePayload,
} from "@/lib/types/projects";
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

const ProjectContext = React.createContext<ProjectContextValue | null>(null);

export function ProjectProvider({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const { projects, isLoading, error, mutate } = useProjects();
  const [currentProjectId, setCurrentProjectId] = React.useState<number | null>(null);
  const [isInitialized, setIsInitialized] = React.useState(false);

  // Initialize current project from localStorage on mount
  React.useEffect(() => {
    if (!isInitialized && typeof window !== "undefined") {
      const storedId = getStoredProjectId();
      if (storedId) {
        setCurrentProjectId(storedId);
      }
      setIsInitialized(true);
    }
  }, [isInitialized]);

  // When projects load, ensure we have a valid current project
  React.useEffect(() => {
    if (projects.length > 0 && currentProjectId === null) {
      // Try to find a default project first
      const defaultProject = projects.find((p) => p.isDefault && p.status === "active");
      const firstActive = projects.find((p) => p.status === "active");
      const projectToSelect = defaultProject ?? firstActive ?? projects[0];
      
      if (projectToSelect) {
        setCurrentProjectId(projectToSelect.id);
        storeProjectId(projectToSelect.id);
      }
    }
  }, [projects, currentProjectId]);

  // Get current project from projects list
  const currentProject = React.useMemo(() => {
    if (!currentProjectId) return null;
    return projects.find((p) => p.id === currentProjectId) ?? null;
  }, [projects, currentProjectId]);

  // Switch to a different project
  const handleSwitchProject = React.useCallback(async (projectId: number) => {
    const project = projects.find((p) => p.id === projectId);
    if (!project) {
      throw new Error("Project not found");
    }

    setCurrentProjectId(projectId);
    storeProjectId(projectId);

    // Navigate to the project dashboard
    router.push(`/projects/${projectId}/dashboard`);
  }, [projects, router]);

  // Create a new project
  const handleCreateProject = React.useCallback(async (payload: ProjectCreatePayload): Promise<Project> => {
    const newProject = await createProject(payload);
    
    // Mutate the projects list
    await mutate();
    
    // Set as current project and navigate
    setCurrentProjectId(newProject.id);
    storeProjectId(newProject.id);
    router.push(`/projects/${newProject.id}/dashboard`);
    
    return newProject;
  }, [mutate, router]);

  // Update a project
  const handleUpdateProject = React.useCallback(async (id: number, payload: ProjectUpdatePayload): Promise<Project> => {
    // Optimistic update
    const previousProjects = [...projects];
    
    try {
      const updatedProject = await updateProject(id, payload);
      await mutate();
      return updatedProject;
    } catch (error) {
      // Rollback on error
      await mutate(previousProjects, false);
      throw error;
    }
  }, [projects, mutate]);

  // Delete a project
  const handleDeleteProject = React.useCallback(async (id: number): Promise<void> => {
    const previousProjects = [...projects];
    const previousCurrentId = currentProjectId;
    
    try {
      await deleteProject(id);
      await mutate();
      
      // If deleted project was current, switch to another
      if (previousCurrentId === id) {
        const remaining = projects.filter((p) => p.id !== id && p.status === "active");
        if (remaining.length > 0) {
          setCurrentProjectId(remaining[0].id);
          storeProjectId(remaining[0].id);
        } else {
          setCurrentProjectId(null);
          clearStoredProjectId();
        }
        router.push("/projects");
      }
    } catch (error) {
      // Rollback on error
      await mutate(previousProjects, false);
      throw error;
    }
  }, [projects, currentProjectId, mutate, router]);

  // Archive a project
  const handleArchiveProject = React.useCallback(async (id: number): Promise<void> => {
    const previousProjects = [...projects];
    
    try {
      await archiveProject(id);
      await mutate();
      
      // If archived project was current, switch to another active project
      if (currentProjectId === id) {
        const activeProjects = projects.filter((p) => p.id !== id && p.status === "active");
        if (activeProjects.length > 0) {
          setCurrentProjectId(activeProjects[0].id);
          storeProjectId(activeProjects[0].id);
        } else {
          setCurrentProjectId(null);
          clearStoredProjectId();
        }
        router.push("/projects");
      }
    } catch (error) {
      await mutate(previousProjects, false);
      throw error;
    }
  }, [projects, currentProjectId, mutate, router]);

  // Restore an archived project
  const handleRestoreProject = React.useCallback(async (id: number): Promise<void> => {
    const previousProjects = [...projects];
    
    try {
      await restoreProject(id);
      await mutate();
    } catch (error) {
      await mutate(previousProjects, false);
      throw error;
    }
  }, [projects, mutate]);

  // Refresh projects list
  const handleRefreshProjects = React.useCallback(async (): Promise<void> => {
    await mutate();
  }, [mutate]);

  const value: ProjectContextValue = {
    projects,
    currentProject,
    isLoading,
    error,
    switchProject: handleSwitchProject,
    createProject: handleCreateProject,
    updateProject: handleUpdateProject,
    deleteProject: handleDeleteProject,
    archiveProject: handleArchiveProject,
    restoreProject: handleRestoreProject,
    refreshProjects: handleRefreshProjects,
  };

  return (
    <ProjectContext.Provider value={value}>
      {children}
    </ProjectContext.Provider>
  );
}

export function useProjectContext(): ProjectContextValue {
  const context = React.useContext(ProjectContext);
  if (!context) {
    throw new Error("useProjectContext must be used within a ProjectProvider");
  }
  return context;
}

// Hook for accessing just the current project
export function useCurrentProject() {
  const { currentProject, switchProject, isLoading, error } = useProjectContext();
  return {
    project: currentProject,
    switchProject,
    isLoading,
    error,
  };
}

// Hook for project CRUD operations
export function useProjectActions() {
  const {
    createProject,
    updateProject,
    deleteProject,
    archiveProject,
    restoreProject,
    refreshProjects,
  } = useProjectContext();
  
  return {
    createProject,
    updateProject,
    deleteProject,
    archiveProject,
    restoreProject,
    refreshProjects,
  };
}

export { ProjectContext };
