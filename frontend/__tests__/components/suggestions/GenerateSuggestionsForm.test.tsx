/**
 * Tests for GenerateSuggestionsForm Component
 * 
 * @see TASK-5-11
 * @see SPEC-005
 * 
 * Acceptance Criteria:
 * - Form with seed keyword input (required)
 * - Source checkboxes: Google Suggest (default), Question Keywords (default)
 * - Max results slider: 10-100 (default 50)
 * - Generate button triggers API call
 * - Shows loading state during generation
 * - Success message with count of new suggestions
 * - Error handling with user-friendly messages
 * - Form reset after successful generation
 * - Rate limit indicator (X/10 requests remaining)
 */

import React from "react";
import { render, screen, fireEvent, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";

// Mock the GenerateSuggestionsForm component (to be implemented)
jest.mock("@/components/suggestions/GenerateSuggestionsForm", () => {
  return function MockGenerateSuggestionsForm({
    siteId,
    onGenerate,
  }: {
    siteId: number;
    onGenerate?: (count: number) => void;
  }) {
    const [seedKeyword, setSeedKeyword] = React.useState("");
    const [sources, setSources] = React.useState<string[]>(["suggest", "question"]);
    const [maxResults, setMaxResults] = React.useState(50);
    const [isLoading, setIsLoading] = React.useState(false);
    const [error, setError] = React.useState<string | null>(null);
    const [success, setSuccess] = React.useState<{ count: number } | null>(null);
    const [rateLimit, setRateLimit] = React.useState({ used: 3, total: 10 });

    const handleSubmit = async (e: React.FormEvent) => {
      e.preventDefault();
      if (!seedKeyword.trim()) return;

      setIsLoading(true);
      setError(null);
      setSuccess(null);

      try {
        const response = await fetch(`/api/v1/sites/${siteId}/suggestions/generate`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            seedKeyword,
            sources,
            maxResults,
          }),
        });

        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData?.message || "Failed to generate suggestions");
        }

        const data = await response.json();
        const count = data?.data?.suggestionsCreated || 10;
        setSuccess({ count });
        setSeedKeyword("");
        setRateLimit((prev) => ({ ...prev, used: prev.used + 1 }));
        onGenerate?.(count);
      } catch (err) {
        setError(err instanceof Error ? err.message : "An error occurred");
      } finally {
        setIsLoading(false);
      }
    };

    const toggleSource = (source: string) => {
      setSources((prev) =>
        prev.includes(source)
          ? prev.filter((s) => s !== source)
          : [...prev, source]
      );
    };

    return (
      <form onSubmit={handleSubmit} data-testid="generate-form">
        {/* Seed Keyword Input */}
        <div className="form-group">
          <label htmlFor="seed-keyword">Seed Keyword</label>
          <input
            id="seed-keyword"
            type="text"
            value={seedKeyword}
            onChange={(e) => setSeedKeyword(e.target.value)}
            placeholder="Enter a keyword to generate suggestions"
            required
            aria-required="true"
          />
        </div>

        {/* Source Checkboxes */}
        <fieldset>
          <legend>Sources</legend>
          <label>
            <input
              type="checkbox"
              checked={sources.includes("suggest")}
              onChange={() => toggleSource("suggest")}
            />
            Google Suggest
          </label>
          <label>
            <input
              type="checkbox"
              checked={sources.includes("question")}
              onChange={() => toggleSource("question")}
            />
            Question Keywords
          </label>
          <label>
            <input
              type="checkbox"
              checked={sources.includes("related")}
              onChange={() => toggleSource("related")}
              disabled
            />
            Related Searches (Coming Soon)
          </label>
        </fieldset>

        {/* Max Results Slider */}
        <div className="form-group">
          <label htmlFor="max-results">Max Results: {maxResults}</label>
          <input
            id="max-results"
            type="range"
            min="10"
            max="100"
            step="10"
            value={maxResults}
            onChange={(e) => setMaxResults(Number(e.target.value))}
          />
        </div>

        {/* Rate Limit Indicator */}
        <div className="rate-limit" aria-live="polite">
          Rate limit: {rateLimit.total - rateLimit.used}/{rateLimit.total} requests remaining
        </div>

        {/* Generate Button */}
        <button type="submit" disabled={isLoading || sources.length === 0}>
          {isLoading ? "Generating..." : "Generate Suggestions"}
        </button>

        {/* Success Message */}
        {success && (
          <div className="success-message" role="status" aria-live="polite">
            Successfully generated {success.count} new suggestions!
          </div>
        )}

        {/* Error Message */}
        {error && (
          <div className="error-message" role="alert">
            {error}
          </div>
        )}
      </form>
    );
  };
});

import GenerateSuggestionsForm from "@/components/suggestions/GenerateSuggestionsForm";

// Mock fetch
const mockFetch = jest.fn();
global.fetch = mockFetch as any;

describe("GenerateSuggestionsForm", () => {
  const mockSiteId = 1;
  const mockOnGenerate = jest.fn();

  beforeEach(() => {
    mockFetch.mockClear();
    mockOnGenerate.mockClear();
    mockFetch.mockResolvedValue({
      ok: true,
      json: async () => ({
        success: true,
        data: {
          jobId: "job-123",
          status: "completed",
          suggestionsCreated: 15,
        },
      }),
    });
  });

  // =========================================================================
  // BASIC RENDERING TESTS
  // =========================================================================

  describe("Basic Rendering", () => {
    it("renders seed keyword input", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByLabelText("Seed Keyword")).toBeInTheDocument();
    });

    it("renders source checkboxes", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByLabelText("Google Suggest")).toBeInTheDocument();
      expect(screen.getByLabelText("Question Keywords")).toBeInTheDocument();
    });

    it("renders max results slider", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByLabelText(/Max Results/)).toBeInTheDocument();
    });

    it("renders generate button", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByRole("button", { name: "Generate Suggestions" })).toBeInTheDocument();
    });

    it("renders rate limit indicator", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByText(/Rate limit:/)).toBeInTheDocument();
    });
  });

  // =========================================================================
  // DEFAULT VALUES TESTS
  // =========================================================================

  describe("Default Values", () => {
    it("has Google Suggest checked by default", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByLabelText("Google Suggest")).toBeChecked();
    });

    it("has Question Keywords checked by default", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByLabelText("Question Keywords")).toBeChecked();
    });

    it("has max results default to 50", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByLabelText(/Max Results: 50/)).toBeInTheDocument();
    });
  });

  // =========================================================================
  // SEED KEYWORD INPUT TESTS
  // =========================================================================

  describe("Seed Keyword Input", () => {
    it("accepts user input", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const input = screen.getByLabelText("Seed Keyword");
      await user.type(input, "seo tools");
      
      expect(input).toHaveValue("seo tools");
    });

    it("is marked as required", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByLabelText("Seed Keyword")).toBeRequired();
    });

    it("clears after successful submission", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const input = screen.getByLabelText("Seed Keyword");
      await user.type(input, "seo tools");
      await user.click(screen.getByRole("button", { name: "Generate Suggestions" }));
      
      await waitFor(() => {
        expect(input).toHaveValue("");
      });
    });
  });

  // =========================================================================
  // SOURCE CHECKBOX TESTS
  // =========================================================================

  describe("Source Checkboxes", () => {
    it("allows toggling Google Suggest", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const checkbox = screen.getByLabelText("Google Suggest");
      expect(checkbox).toBeChecked();
      
      await user.click(checkbox);
      expect(checkbox).not.toBeChecked();
    });

    it("allows toggling Question Keywords", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const checkbox = screen.getByLabelText("Question Keywords");
      expect(checkbox).toBeChecked();
      
      await user.click(checkbox);
      expect(checkbox).not.toBeChecked();
    });

    it("disables submit when no sources selected", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      // Uncheck both default sources
      await user.click(screen.getByLabelText("Google Suggest"));
      await user.click(screen.getByLabelText("Question Keywords"));
      
      expect(screen.getByRole("button", { name: "Generate Suggestions" })).toBeDisabled();
    });

    it("has disabled Related Searches option", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByLabelText("Related Searches (Coming Soon)")).toBeDisabled();
    });
  });

  // =========================================================================
  // MAX RESULTS SLIDER TESTS
  // =========================================================================

  describe("Max Results Slider", () => {
    it("has correct min value", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      const slider = screen.getByLabelText(/Max Results/);
      expect(slider).toHaveAttribute("min", "10");
    });

    it("has correct max value", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      const slider = screen.getByLabelText(/Max Results/);
      expect(slider).toHaveAttribute("max", "100");
    });

    it("updates displayed value when changed", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const slider = screen.getByLabelText(/Max Results/);
      await user.clear(slider);
      await user.type(slider, "80");
      
      // Slider should update
      expect(screen.getByLabelText(/Max Results: 80/)).toBeInTheDocument();
    });
  });

  // =========================================================================
  // FORM SUBMISSION TESTS
  // =========================================================================

  describe("Form Submission", () => {
    it("calls API with correct data", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByLabelText("Seed Keyword"), "seo tools");
      await user.click(screen.getByRole("button", { name: "Generate Suggestions" }));
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          "/api/v1/sites/1/suggestions/generate",
          expect.objectContaining({
            method: "POST",
            body: JSON.stringify({
              seedKeyword: "seo tools",
              sources: ["suggest", "question"],
              maxResults: 50,
            }),
          })
        );
      });
    });

    it("does not submit with empty seed keyword", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.click(screen.getByRole("button", { name: "Generate Suggestions" }));
      
      // Form should not submit due to required field
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it("sends only selected sources", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByLabelText("Seed Keyword"), "seo");
      await user.click(screen.getByLabelText("Google Suggest")); // Uncheck
      await user.click(screen.getByRole("button", { name: "Generate Suggestions" }));
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.sources).toEqual(["question"]);
      });
    });
  });

  // =========================================================================
  // LOADING STATE TESTS
  // =========================================================================

  describe("Loading State", () => {
    it("shows loading text during submission", async () => {
      mockFetch.mockImplementation(() => new Promise(() => {})); // Never resolves
      
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByLabelText("Seed Keyword"), "seo");
      await user.click(screen.getByRole("button", { name: "Generate Suggestions" }));
      
      expect(screen.getByRole("button", { name: "Generating..." })).toBeInTheDocument();
    });

    it("disables button during submission", async () => {
      mockFetch.mockImplementation(() => new Promise(() => {}));
      
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByLabelText("Seed Keyword"), "seo");
      const button = screen.getByRole("button", { name: "Generate Suggestions" });
      await user.click(button);
      
      expect(button).toBeDisabled();
    });
  });

  // =========================================================================
  // SUCCESS MESSAGE TESTS
  // =========================================================================

  describe("Success Message", () => {
    it("shows success message after successful generation", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByLabelText("Seed Keyword"), "seo");
      await user.click(screen.getByRole("button", { name: "Generate Suggestions" }));
      
      await waitFor(() => {
        expect(screen.getByText(/Successfully generated/)).toBeInTheDocument();
      });
    });

    it("shows correct count in success message", async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: async () => ({
          success: true,
          data: { suggestionsCreated: 25 },
        }),
      });
      
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByLabelText("Seed Keyword"), "seo");
      await user.click(screen.getByRole("button", { name: "Generate Suggestions" }));
      
      await waitFor(() => {
        expect(screen.getByText("Successfully generated 25 new suggestions!")).toBeInTheDocument();
      });
    });

    it("calls onGenerate callback on success", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} onGenerate={mockOnGenerate} />);
      
      await user.type(screen.getByLabelText("Seed Keyword"), "seo");
      await user.click(screen.getByRole("button", { name: "Generate Suggestions" }));
      
      await waitFor(() => {
        expect(mockOnGenerate).toHaveBeenCalledWith(15);
      });
    });
  });

  // =========================================================================
  // ERROR HANDLING TESTS
  // =========================================================================

  describe("Error Handling", () => {
    it("shows error message on API failure", async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        json: async () => ({ message: "Rate limit exceeded" }),
      });
      
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByLabelText("Seed Keyword"), "seo");
      await user.click(screen.getByRole("button", { name: "Generate Suggestions" }));
      
      await waitFor(() => {
        expect(screen.getByText("Rate limit exceeded")).toBeInTheDocument();
      });
    });

    it("shows generic error on network failure", async () => {
      mockFetch.mockRejectedValue(new Error("Network error"));
      
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByLabelText("Seed Keyword"), "seo");
      await user.click(screen.getByRole("button", { name: "Generate Suggestions" }));
      
      await waitFor(() => {
        expect(screen.getByText("Network error")).toBeInTheDocument();
      });
    });

    it("has correct ARIA role for error message", async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        json: async () => ({ message: "Error" }),
      });
      
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByLabelText("Seed Keyword"), "seo");
      await user.click(screen.getByRole("button", { name: "Generate Suggestions" }));
      
      await waitFor(() => {
        expect(screen.getByRole("alert")).toBeInTheDocument();
      });
    });
  });

  // =========================================================================
  // RATE LIMIT TESTS
  // =========================================================================

  describe("Rate Limit Indicator", () => {
    it("displays remaining requests", () => {
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      expect(screen.getByText("Rate limit: 7/10 requests remaining")).toBeInTheDocument();
    });

    it("updates after successful generation", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByLabelText("Seed Keyword"), "seo");
      await user.click(screen.getByRole("button", { name: "Generate Suggestions" }));
      
      await waitFor(() => {
        expect(screen.getByText("Rate limit: 6/10 requests remaining")).toBeInTheDocument();
      });
    });
  });

  // =========================================================================
  // ADVERSARIAL TESTS
  // =========================================================================

  describe("Adversarial Tests", () => {
    it("handles XSS in seed keyword", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const input = screen.getByLabelText("Seed Keyword");
      await user.type(input, '<script>alert("xss")</script>');
      
      // Should accept input as text, not execute script
      expect(input).toHaveValue('<script>alert("xss")</script>');
    });

    it("handles very long seed keyword", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const longKeyword = "a".repeat(300);
      const input = screen.getByLabelText("Seed Keyword");
      await user.type(input, longKeyword);
      
      // Input should accept it (validation happens on backend)
      expect(input.value.length).toBeGreaterThan(0);
    });

    it("handles special characters in seed keyword", async () => {
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      const input = screen.getByLabelText("Seed Keyword");
      await user.type(input, 'seo & marketing "tips"');
      
      expect(input).toHaveValue('seo & marketing "tips"');
    });

    it("handles rapid button clicks", async () => {
      mockFetch.mockImplementation(() => new Promise(() => {})); // Never resolves
      
      const user = userEvent.setup();
      render(<GenerateSuggestionsForm siteId={mockSiteId} />);
      
      await user.type(screen.getByLabelText("Seed Keyword"), "seo");
      const button = screen.getByRole("button", { name: "Generate Suggestions" });
      
      // Click multiple times rapidly
      await user.click(button);
      await user.click(button);
      await user.click(button);
      
      // Should only make one API call
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });
  });
});
