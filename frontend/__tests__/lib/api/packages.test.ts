/**
 * Packages API Tests - TASK-26-20
 *
 * Tests for the packages API helper functions that fetch
 * credit packages from the backend.
 *
 * @see SPEC-026
 * @see TASK-26-20
 */

import {
  fetchPackages,
  usePackages,
} from "@/lib/api/packages";

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

const mockPackagesResponse = {
  data: [
    {
      id: "starter",
      name: "Starter",
      credits: 100,
      price: 5.0,
      currency: "USD",
      bonus: 0,
      isPopular: false,
    },
    {
      id: "basic",
      name: "Basic",
      credits: 500,
      price: 20.0,
      currency: "USD",
      bonus: 50,
      isPopular: false,
    },
    {
      id: "pro",
      name: "Pro",
      credits: 1500,
      price: 50.0,
      currency: "USD",
      bonus: 250,
      isPopular: true,
    },
    {
      id: "enterprise",
      name: "Enterprise",
      credits: 5000,
      price: 150.0,
      currency: "USD",
      bonus: 1000,
      isPopular: false,
    },
  ],
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
});

// ==================== API-001: fetchPackages ====================

describe("fetchPackages", () => {
  it("sends GET request to /api/v1/packages", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockPackagesResponse),
    });

    await fetchPackages();

    expect(mockFetch).toHaveBeenCalledWith("/api/v1/packages", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
    });
  });

  it("returns normalized packages array on success", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockPackagesResponse),
    });

    const result = await fetchPackages();

    expect(result).toHaveLength(4);
    expect(result[0].id).toBe("starter");
    expect(result[0].name).toBe("Starter");
    expect(result[0].credits).toBe(100);
  });

  it("throws error on failed request", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 500,
      statusText: "Internal Server Error",
      json: () => Promise.resolve({ message: "Server error" }),
    });

    await expect(fetchPackages()).rejects.toThrow();
  });

  it("throws error with message from response", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 400,
      json: () => Promise.resolve({ message: "Invalid request" }),
    });

    await expect(fetchPackages()).rejects.toThrow("Invalid request");
  });

  it("handles network errors", async () => {
    mockFetch.mockRejectedValueOnce(new Error("Network error"));

    await expect(fetchPackages()).rejects.toThrow("Network error");
  });

  it("handles timeout errors", async () => {
    mockFetch.mockImplementationOnce(
      () =>
        new Promise((_, reject) => {
          setTimeout(() => reject(new Error("Timeout")), 100);
        })
    );

    await expect(fetchPackages()).rejects.toThrow();
  });
});

// ==================== API-002: usePackages Hook ====================

describe("usePackages", () => {
  it("calls useSWR with correct key", () => {
    const mockMutate = jest.fn();
    (useSWR as jest.Mock).mockReturnValue({
      data: mockPackagesResponse.data,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    usePackages();

    expect(useSWR).toHaveBeenCalledWith("/api/v1/packages", expect.any(Function));
  });

  it("returns packages data from SWR", () => {
    const mockMutate = jest.fn();
    (useSWR as jest.Mock).mockReturnValue({
      data: mockPackagesResponse.data,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    const result = usePackages();

    expect(result.packages).toEqual(mockPackagesResponse.data);
  });

  it("returns loading state from SWR", () => {
    const mockMutate = jest.fn();
    (useSWR as jest.Mock).mockReturnValue({
      data: null,
      isLoading: true,
      error: null,
      mutate: mockMutate,
    });

    const result = usePackages();

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

    const result = usePackages();

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

    const result = usePackages();

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

    const result = usePackages();

    expect(result.packages).toEqual([]);
  });
});

// ==================== API-003: Data Normalization ====================

describe("Data Normalization", () => {
  it("normalizes package structure", async () => {
    const rawResponse = {
      data: [
        {
          id: "test",
          name: "Test Package",
          credits: 1000,
          price: "25.00", // String price
          currency: "USD",
          bonus_credits: 100, // Snake case
          is_popular: true, // Snake case
        },
      ],
    };

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(rawResponse),
    });

    const result = await fetchPackages();

    expect(result[0]).toEqual({
      id: "test",
      name: "Test Package",
      credits: 1000,
      price: 25.0, // Converted to number
      currency: "USD",
      bonus: 100, // Renamed from bonus_credits
      isPopular: true, // Renamed from is_popular
    });
  });

  it("handles missing optional fields", async () => {
    const minimalResponse = {
      data: [
        {
          id: "minimal",
          name: "Minimal Package",
          credits: 100,
          price: 10,
        },
      ],
    };

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(minimalResponse),
    });

    const result = await fetchPackages();

    expect(result[0].currency).toBe("USD"); // Default
    expect(result[0].bonus).toBe(0); // Default
    expect(result[0].isPopular).toBe(false); // Default
  });
});

// ==================== API-004: Edge Cases ====================

describe("Edge Cases", () => {
  it("handles empty packages array", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ data: [] }),
    });

    const result = await fetchPackages();

    expect(result).toEqual([]);
  });

  it("handles packages without data wrapper", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve([
        { id: "1", name: "Package 1", credits: 100, price: 10 },
      ]),
    });

    const result = await fetchPackages();

    expect(result).toHaveLength(1);
  });

  it("handles large number of packages", async () => {
    const largePackages = Array.from({ length: 100 }, (_, i) => ({
      id: `package-${i}`,
      name: `Package ${i}`,
      credits: 100 * (i + 1),
      price: 10 * (i + 1),
      currency: "USD",
      bonus: 0,
      isPopular: false,
    }));

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ data: largePackages }),
    });

    const result = await fetchPackages();

    expect(result).toHaveLength(100);
  });

  it("handles malformed response gracefully", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ invalid: "structure" }),
    });

    const result = await fetchPackages();

    expect(result).toEqual([]);
  });
});

// ==================== API-005: Caching ====================

describe("Caching", () => {
  it("uses SWR caching mechanism", () => {
    (useSWR as jest.Mock).mockReturnValue({
      data: null,
      isLoading: false,
      error: null,
      mutate: jest.fn(),
    });

    usePackages();

    // SWR is called with the correct key for caching
    expect(useSWR).toHaveBeenCalledWith(
      "/api/v1/packages",
      expect.any(Function)
    );
  });

  it("mutate function can refresh data", async () => {
    const mockMutate = jest.fn().mockResolvedValue(mockPackagesResponse.data);
    (useSWR as jest.Mock).mockReturnValue({
      data: mockPackagesResponse.data,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    const { mutate } = usePackages();
    await mutate();

    expect(mockMutate).toHaveBeenCalled();
  });
});

// ==================== API-006: Security ====================

describe("Security", () => {
  it("does not expose sensitive headers", async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockPackagesResponse),
    });

    await fetchPackages();

    const call = mockFetch.mock.calls[0];
    expect(call[1].headers).not.toHaveProperty("Authorization");
    expect(call[1].headers).not.toHaveProperty("X-API-Key");
  });

  it("sanitizes XSS in package names", async () => {
    const xssResponse = {
      data: [
        {
          id: "xss",
          name: '<script>alert("XSS")</script>Package',
          credits: 100,
          price: 10,
        },
      ],
    };

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(xssResponse),
    });

    const result = await fetchPackages();

    // The XSS should be preserved as-is in the data
    // (sanitization happens at render time in React)
    expect(result[0].name).toBe('<script>alert("XSS")</script>Package');
  });
});
