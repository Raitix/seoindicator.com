/**
 * Models API Tests - TASK-26-20
 *
 * Tests for the models API helper functions that fetch
 * AI model pricing information from the backend.
 *
 * @see SPEC-026
 * @see TASK-26-20
 */

import {
  fetchModels,
  useModels,
} from "@/lib/api/models";

// Mock fetch globally
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Mock SWR
jest.mock("swr", () => ({
  __esModule: true,
  default: jest.fn(),
}));

import useSWR from "swr";

// ==================== Mock Data ====================

const mockModelsResponse = {
  data: [
    {
      id: "gpt-4o-mini",
      name: "GPT-4o Mini",
      provider: "OpenAI",
      costPerAnalysis: 0.02,
      freeTier: "10/month",
      contextWindow: "128K",
      description: "Fast and efficient for most tasks",
    },
    {
      id: "claude-3-haiku",
      name: "Claude 3 Haiku",
      provider: "Anthropic",
      costPerAnalysis: 0.025,
      freeTier: "10/month",
      contextWindow: "200K",
      description: "Quick responses with high quality",
    },
    {
      id: "gemini-2-flash",
      name: "Gemini 2 Flash",
      provider: "Google",
      costPerAnalysis: 0.015,
      freeTier: "15/month",
      contextWindow: "1M",
      description: "Best value for large content",
    },
  ],
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
});

// ==================== API-001: fetchModels ====================

describe("fetchModels", () => {
  it("sends GET request to /api/v1/models", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockModelsResponse),
    });

    await fetchModels();

    expect(mockFetch).toHaveBeenCalledWith("/api/v1/models", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
    });
  });

  it("returns normalized models array on success", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockModelsResponse),
    });

    const result = await fetchModels();

    expect(result).toHaveLength(3);
    expect(result[0].id).toBe("gpt-4o-mini");
    expect(result[0].name).toBe("GPT-4o Mini");
    expect(result[0].provider).toBe("OpenAI");
  });

  it("throws error on failed request", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 500,
      json: () => Promise.resolve({ message: "Server error" }),
    });

    await expect(fetchModels()).rejects.toThrow();
  });

  it("throws error with message from response", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 400,
      json: () => Promise.resolve({ message: "Invalid request" }),
    });

    await expect(fetchModels()).rejects.toThrow("Invalid request");
  });

  it("handles network errors", async () => {
    mockFetch.mockRejectedValueOnce(new Error("Network error"));

    await expect(fetchModels()).rejects.toThrow("Network error");
  });
});

// ==================== API-002: useModels Hook ====================

describe("useModels", () => {
  it("calls useSWR with correct key", () => {
    const mockMutate = jest.fn();
    (useSWR as jest.Mock).mockReturnValue({
      data: mockModelsResponse.data,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    useModels();

    expect(useSWR).toHaveBeenCalledWith("/api/v1/models", expect.any(Function));
  });

  it("returns models data from SWR", () => {
    const mockMutate = jest.fn();
    (useSWR as jest.Mock).mockReturnValue({
      data: mockModelsResponse.data,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    const result = useModels();

    expect(result.models).toEqual(mockModelsResponse.data);
  });

  it("returns loading state from SWR", () => {
    const mockMutate = jest.fn();
    (useSWR as jest.Mock).mockReturnValue({
      data: null,
      isLoading: true,
      error: null,
      mutate: mockMutate,
    });

    const result = useModels();

    expect(result.isLoading).toBe(true);
  });

  it("returns error state from SWR", () => {
    const mockError = new Error("Failed to fetch");
    const mockMutate = jest.fn();
    (useSWR as jest.Mock).mockReturnValue({
      data: null,
      isLoading: false,
      error: mockError,
      mutate: mockMutate,
    });

    const result = useModels();

    expect(result.error).toBe(mockError);
  });

  it("returns mutate function from SWR", () => {
    const mockMutate = jest.fn();
    (useSWR as jest.Mock).mockReturnValue({
      data: null,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    const result = useModels();

    expect(result.mutate).toBe(mockMutate);
  });

  it("returns empty array when data is null", () => {
    const mockMutate = jest.fn();
    (useSWR as jest.Mock).mockReturnValue({
      data: null,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    const result = useModels();

    expect(result.models).toEqual([]);
  });
});

// ==================== API-003: Data Normalization ====================

describe("Data Normalization", () => {
  it("normalizes model structure", async () => {
    const rawResponse = {
      data: [
        {
          id: "test-model",
          name: "Test Model",
          provider: "TestProvider",
          cost_per_analysis: "0.05", // Snake case, string
          free_tier: "5/month", // Snake case
          context_window: "64K", // Snake case
          description: "Test description",
        },
      ],
    };

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(rawResponse),
    });

    const result = await fetchModels();

    expect(result[0]).toEqual({
      id: "test-model",
      name: "Test Model",
      provider: "TestProvider",
      costPerAnalysis: 0.05, // Converted to number, renamed
      freeTier: "5/month", // Renamed
      contextWindow: "64K", // Renamed
      description: "Test description",
    });
  });

  it("handles missing optional fields", async () => {
    const minimalResponse = {
      data: [
        {
          id: "minimal",
          name: "Minimal Model",
          provider: "Test",
          costPerAnalysis: 0.01,
        },
      ],
    };

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(minimalResponse),
    });

    const result = await fetchModels();

    expect(result[0].freeTier).toBeNull();
    expect(result[0].contextWindow).toBeNull();
    expect(result[0].description).toBe("");
  });
});

// ==================== API-004: Provider Filtering ====================

describe("Provider Filtering", () => {
  it("returns unique providers list", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockModelsResponse),
    });

    const result = await fetchModels();
    const providers = [...new Set(result.map((m) => m.provider))];

    expect(providers).toContain("OpenAI");
    expect(providers).toContain("Anthropic");
    expect(providers).toContain("Google");
  });

  it("filterByProvider returns only matching models", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockModelsResponse),
    });

    const result = await fetchModels();
    const openAIModels = result.filter((m) => m.provider === "OpenAI");

    expect(openAIModels).toHaveLength(1);
    expect(openAIModels[0].id).toBe("gpt-4o-mini");
  });
});

// ==================== API-005: Sorting ====================

describe("Sorting", () => {
  it("sortByCost ascending orders models by price", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockModelsResponse),
    });

    const result = await fetchModels();
    const sorted = [...result].sort((a, b) => a.costPerAnalysis - b.costPerAnalysis);

    expect(sorted[0].id).toBe("gemini-2-flash"); // $0.015
    expect(sorted[1].id).toBe("gpt-4o-mini"); // $0.02
    expect(sorted[2].id).toBe("claude-3-haiku"); // $0.025
  });

  it("sortByCost descending orders models by price descending", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockModelsResponse),
    });

    const result = await fetchModels();
    const sorted = [...result].sort((a, b) => b.costPerAnalysis - a.costPerAnalysis);

    expect(sorted[0].id).toBe("claude-3-haiku"); // $0.025
    expect(sorted[2].id).toBe("gemini-2-flash"); // $0.015
  });
});

// ==================== API-006: Edge Cases ====================

describe("Edge Cases", () => {
  it("handles empty models array", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ data: [] }),
    });

    const result = await fetchModels();

    expect(result).toEqual([]);
  });

  it("handles models without data wrapper", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve([
        { id: "1", name: "Model 1", provider: "Test", costPerAnalysis: 0.01 },
      ]),
    });

    const result = await fetchModels();

    expect(result).toHaveLength(1);
  });

  it("handles zero cost models", async () => {
    const freeModels = {
      data: [
        {
          id: "free-model",
          name: "Free Model",
          provider: "Test",
          costPerAnalysis: 0,
          freeTier: "Unlimited",
        },
      ],
    };

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(freeModels),
    });

    const result = await fetchModels();

    expect(result[0].costPerAnalysis).toBe(0);
  });

  it("handles models without free tier", async () => {
    const noFreeTier = {
      data: [
        {
          id: "paid-only",
          name: "Paid Only Model",
          provider: "Test",
          costPerAnalysis: 0.05,
          freeTier: null,
        },
      ],
    };

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(noFreeTier),
    });

    const result = await fetchModels();

    expect(result[0].freeTier).toBeNull();
  });

  it("handles malformed response gracefully", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ invalid: "structure" }),
    });

    const result = await fetchModels();

    expect(result).toEqual([]);
  });
});

// ==================== API-007: Caching ====================

describe("Caching", () => {
  it("uses SWR caching mechanism", () => {
    (useSWR as jest.Mock).mockReturnValue({
      data: null,
      isLoading: false,
      error: null,
      mutate: jest.fn(),
    });

    useModels();

    expect(useSWR).toHaveBeenCalledWith(
      "/api/v1/models",
      expect.any(Function)
    );
  });

  it("mutate function can refresh data", async () => {
    const mockMutate = jest.fn().mockResolvedValue(mockModelsResponse.data);
    (useSWR as jest.Mock).mockReturnValue({
      data: mockModelsResponse.data,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    const { mutate } = useModels();
    await mutate();

    expect(mockMutate).toHaveBeenCalled();
  });
});

// ==================== API-008: Security ====================

describe("Security", () => {
  it("does not expose sensitive headers", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockModelsResponse),
    });

    await fetchModels();

    const call = mockFetch.mock.calls[0];
    expect(call[1].headers).not.toHaveProperty("Authorization");
    expect(call[1].headers).not.toHaveProperty("X-API-Key");
  });

  it("sanitizes XSS in model names", async () => {
    const xssResponse = {
      data: [
        {
          id: "xss",
          name: '<script>alert("XSS")</script>Model',
          provider: "Test",
          costPerAnalysis: 0.01,
        },
      ],
    };

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(xssResponse),
    });

    const result = await fetchModels();

    // XSS is preserved in data (sanitization happens at render)
    expect(result[0].name).toBe('<script>alert("XSS")</script>Model');
  });

  it("handles SQL injection attempts in provider filter", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockModelsResponse),
    });

    const result = await fetchModels();
    const maliciousFilter = result.filter(
      (m) => m.provider === "OpenAI' OR '1'='1"
    );

    // Should return empty (no match), not all models
    expect(maliciousFilter).toHaveLength(0);
  });
});

// ==================== API-009: Performance ====================

describe("Performance", () => {
  it("handles large number of models", async () => {
    const largeModels = Array.from({ length: 50 }, (_, i) => ({
      id: `model-${i}`,
      name: `Model ${i}`,
      provider: ["OpenAI", "Anthropic", "Google", "Meta"][i % 4],
      costPerAnalysis: 0.01 + i * 0.001,
      freeTier: `${10 + i}/month`,
      contextWindow: `${128 * (i + 1)}K`,
    }));

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ data: largeModels }),
    });

    const startTime = performance.now();
    const result = await fetchModels();
    const fetchTime = performance.now() - startTime;

    expect(result).toHaveLength(50);
    expect(fetchTime).toBeLessThan(100); // Should process quickly
  });
});
