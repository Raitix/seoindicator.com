import { renderHook, waitFor, act } from "@testing-library/react";
import {
  useProjects,
  useProject,
  useProjectMetrics,
  createProject,
  updateProject,
  deleteProject,
  archiveProject,
  restoreProject,
  fetchAggregateMetrics,
  getStoredProjectId,
  storeProjectId,
  clearStoredProjectId,
} from "@/lib/api/projects";

// Mock fetch globally
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: jest.fn((key: string) => store[key] ?? null),
    setItem: jest.fn((key: string, value: string) => {
      store[key] = value;
    }),
    removeItem: jest.fn((key: string) => {
      delete store[key];
    }),
    clear: jest.fn(() => {
      store = {};
    }),
  };
})();
Object.defineProperty(window, "localStorage", { value: localStorageMock });

// Mock SWR
jest.mock("swr", () => ({
  __esModule: true,
  default: jest.fn((key, fetcher) => {
    if (key && fetcher) {
      // Return a simple implementation for testing
      return {
        data: null,
        error: null,
        isLoading: true,
        mutate: jest.fn(),
      };
    }
    return {
      data: null,
      error: null,
      isLoading: false,
      mutate: jest.fn(),
    };
  }),
  mutate: jest.fn(),
}));

describe("Projects API", () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  describe("LocalStorage helpers", () => {
    it("getStoredProjectId returns null when not set", () => {
      const result = getStoredProjectId();
      expect(result).toBeNull();
    });

    it("storeProjectId saves to localStorage", () => {
      storeProjectId(42);
      expect(localStorageMock.setItem).toHaveBeenCalledWith(
        "seoindicator_current_project",
        "42"
      );
    });

    it("clearStoredProjectId removes from localStorage", () => {
      clearStoredProjectId();
      expect(localStorageMock.removeItem).toHaveBeenCalledWith(
        "seoindicator_current_project"
      );
    });
  });

  describe("createProject", () => {
    it("sends POST request with correct data", async () => {
      const mockResponse = {
        id: 1,
        name: "Test Project",
        domain: "test.com",
        notes: null,
        archived_at: null,
        created_at: "2026-01-01",
        updated_at: "2026-01-01",
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ data: mockResponse }),
      });

      const result = await createProject({
        name: "Test Project",
        domain: "test.com",
      });

      expect(mockFetch).toHaveBeenCalledWith("/api/v1/projects", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: "Test Project",
          domain: "test.com",
          notes: null,
        }),
      });

      expect(result.id).toBe(1);
      expect(result.name).toBe("Test Project");
    });

    it("throws error on failed request", async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        statusText: "Bad Request",
        json: () => Promise.resolve({ message: "Name is required" }),
      });

      await expect(createProject({ name: "" })).rejects.toThrow("Name is required");
    });
  });

  describe("updateProject", () => {
    it("sends PUT request with correct data", async () => {
      const mockResponse = {
        id: 1,
        name: "Updated Name",
        domain: "test.com",
        archived_at: null,
        created_at: "2026-01-01",
        updated_at: "2026-01-02",
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ data: mockResponse }),
      });

      const result = await updateProject(1, { name: "Updated Name" });

      expect(mockFetch).toHaveBeenCalledWith("/api/v1/projects/1", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: "Updated Name" }),
      });

      expect(result.name).toBe("Updated Name");
    });
  });

  describe("deleteProject", () => {
    it("sends DELETE request", async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({}),
      });

      await deleteProject(1);

      expect(mockFetch).toHaveBeenCalledWith("/api/v1/projects/1?confirm=true", {
        method: "DELETE",
      });
    });

    it("throws error on failed delete", async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        json: () => Promise.resolve({ message: "Project not found" }),
      });

      await expect(deleteProject(999)).rejects.toThrow("Project not found");
    });
  });

  describe("archiveProject", () => {
    it("sends POST to archive endpoint", async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          data: { id: 1, archived_at: "2026-01-15" },
        }),
      });

      const result = await archiveProject(1);

      expect(mockFetch).toHaveBeenCalledWith("/api/v1/projects/1/archive", {
        method: "POST",
      });
      expect(result.status).toBe("archived");
    });
  });

  describe("restoreProject", () => {
    it("sends POST to restore endpoint", async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          data: { id: 1, archived_at: null },
        }),
      });

      const result = await restoreProject(1);

      expect(mockFetch).toHaveBeenCalledWith("/api/v1/projects/1/restore", {
        method: "POST",
      });
      expect(result.status).toBe("active");
    });
  });

  describe("fetchAggregateMetrics", () => {
    it("fetches and normalizes aggregate metrics", async () => {
      const mockResponse = {
        aggregate: {
          total_projects: 5,
          total_keywords: 1000,
          avg_position: 12.5,
          total_visibility: 45.2,
          visibility_change: 2.3,
        },
        projects: [
          {
            id: 1,
            name: "Project A",
            keywords_count: 500,
            avg_position: 10,
            visibility: 50,
            change: { visibility: 5 },
          },
        ],
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      });

      const result = await fetchAggregateMetrics();

      expect(mockFetch).toHaveBeenCalledWith("/api/v1/projects/aggregate");
      expect(result.aggregate.totalProjects).toBe(5);
      expect(result.aggregate.totalKeywords).toBe(1000);
      expect(result.projects).toHaveLength(1);
      expect(result.projects[0].metrics.keywordsCount).toBe(500);
    });

    it("throws error on failed request", async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        statusText: "Internal Server Error",
      });

      await expect(fetchAggregateMetrics()).rejects.toThrow("Failed to fetch aggregate metrics");
    });
  });
});
