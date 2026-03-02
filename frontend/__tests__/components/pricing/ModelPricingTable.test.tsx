/**
 * ModelPricingTable Component Tests - TASK-26-20
 *
 * Tests for the AI model pricing comparison table that displays
 * available models with their costs, free tiers, and context windows.
 *
 * @see SPEC-026
 * @see TASK-26-20
 */

import { render, screen, within, fireEvent } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import ModelPricingTable from "@/components/pricing/ModelPricingTable";

// ==================== Mock Data ====================

const mockModels = [
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
  {
    id: "llama-3-8b",
    name: "Llama 3 8B",
    provider: "Meta",
    costPerAnalysis: 0.01,
    freeTier: "20/month",
    contextWindow: "32K",
    description: "Open source efficiency",
  },
];

const mockHandlers = {
  onModelClick: jest.fn(),
  onRetry: jest.fn(),
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
});

// ==================== MT-001: Rendering ====================

describe("Rendering", () => {
  it("renders without crashing", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    expect(screen.getByTestId("model-pricing-table")).toBeInTheDocument();
  });

  it("displays table element", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    expect(screen.getByRole("table")).toBeInTheDocument();
  });

  it("displays table headers", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    const table = screen.getByRole("table");
    expect(within(table).getByText(/model/i)).toBeInTheDocument();
    expect(within(table).getByText(/cost.*analysis/i)).toBeInTheDocument();
    expect(within(table).getByText(/free.*tier/i)).toBeInTheDocument();
    expect(within(table).getByText(/context/i)).toBeInTheDocument();
  });

  it("displays all models from data", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    expect(screen.getByText("GPT-4o Mini")).toBeInTheDocument();
    expect(screen.getByText("Claude 3 Haiku")).toBeInTheDocument();
    expect(screen.getByText("Gemini 2 Flash")).toBeInTheDocument();
    expect(screen.getByText("Llama 3 8B")).toBeInTheDocument();
  });

  it("displays provider badges", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    expect(screen.getByText("OpenAI")).toBeInTheDocument();
    expect(screen.getByText("Anthropic")).toBeInTheDocument();
    expect(screen.getByText("Google")).toBeInTheDocument();
    expect(screen.getByText("Meta")).toBeInTheDocument();
  });
});

// ==================== MT-002: Loading State ====================

describe("Loading State", () => {
  it("shows loading skeleton when isLoading is true", () => {
    render(
      <ModelPricingTable
        models={[]}
        isLoading={true}
        error={null}
      />
    );

    expect(screen.getByTestId("models-loading-skeleton")).toBeInTheDocument();
  });

  it("does not show table while loading", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={true}
        error={null}
      />
    );

    expect(screen.queryByRole("table")).not.toBeInTheDocument();
  });
});

// ==================== MT-003: Error State ====================

describe("Error State", () => {
  it("shows error message when error is present", () => {
    render(
      <ModelPricingTable
        models={[]}
        isLoading={false}
        error={new Error("Failed to fetch models")}
      />
    );

    expect(screen.getByText(/failed to load models/i)).toBeInTheDocument();
  });

  it("shows retry button on error", () => {
    render(
      <ModelPricingTable
        models={[]}
        isLoading={false}
        error={new Error("Failed")}
        onRetry={mockHandlers.onRetry}
      />
    );

    expect(screen.getByRole("button", { name: /retry/i })).toBeInTheDocument();
  });

  it("calls onRetry when retry clicked", async () => {
    const user = userEvent.setup();

    render(
      <ModelPricingTable
        models={[]}
        isLoading={false}
        error={new Error("Failed")}
        onRetry={mockHandlers.onRetry}
      />
    );

    await user.click(screen.getByRole("button", { name: /retry/i }));
    expect(mockHandlers.onRetry).toHaveBeenCalledTimes(1);
  });
});

// ==================== MT-004: Cost Display ====================

describe("Cost Display", () => {
  it("displays cost per analysis with correct formatting", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    // Costs should be formatted with $ and correct decimal places
    expect(screen.getByText(/\$0\.02/)).toBeInTheDocument();
    expect(screen.getByText(/\$0\.025/)).toBeInTheDocument();
    expect(screen.getByText(/\$0\.015/)).toBeInTheDocument();
    expect(screen.getByText(/\$0\.01/)).toBeInTheDocument();
  });

  it("displays "per analysis" label", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    // Header should indicate cost per analysis
    expect(screen.getByText(/cost.*analysis/i)).toBeInTheDocument();
  });
});

// ==================== MT-005: Free Tier Display ====================

describe("Free Tier Display", () => {
  it("displays free tier information", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    expect(screen.getByText("10/month")).toBeInTheDocument();
    expect(screen.getByText("15/month")).toBeInTheDocument();
    expect(screen.getByText("20/month")).toBeInTheDocument();
  });

  it("shows "None" for models without free tier", () => {
    const modelsNoFreeTier = [
      { ...mockModels[0], freeTier: null },
    ];

    render(
      <ModelPricingTable
        models={modelsNoFreeTier}
        isLoading={false}
        error={null}
      />
    );

    expect(screen.getByText(/none|-/i)).toBeInTheDocument();
  });
});

// ==================== MT-006: Context Window Display ====================

describe("Context Window Display", () => {
  it("displays context window sizes", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    expect(screen.getByText("128K")).toBeInTheDocument();
    expect(screen.getByText("200K")).toBeInTheDocument();
    expect(screen.getByText("1M")).toBeInTheDocument();
    expect(screen.getByText("32K")).toBeInTheDocument();
  });
});

// ==================== MT-007: Sorting ====================

describe("Sorting by Cost", () => {
  it("has sortable column header for cost", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    const costHeader = screen.getByText(/cost.*analysis/i);
    expect(costHeader).toHaveAttribute("aria-sort");
  });

  it("sorts ascending when cost header clicked first time", async () => {
    const user = userEvent.setup();

    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    const costHeader = screen.getByText(/cost.*analysis/i);
    await user.click(costHeader);

    // Check aria-sort changed
    expect(costHeader).toHaveAttribute("aria-sort", "ascending");
  });

  it("sorts descending when cost header clicked second time", async () => {
    const user = userEvent.setup();

    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    const costHeader = screen.getByText(/cost.*analysis/i);
    
    // First click - ascending
    await user.click(costHeader);
    // Second click - descending
    await user.click(costHeader);

    expect(costHeader).toHaveAttribute("aria-sort", "descending");
  });

  it("reorders rows after sorting", async () => {
    const user = userEvent.setup();

    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    const costHeader = screen.getByText(/cost.*analysis/i);
    await user.click(costHeader);

    // Get all model rows and check order
    const rows = screen.getAllByTestId(/model-row-/);
    const costs = rows.map((row) => {
      const costText = within(row).getByText(/\$0\.\d+/).textContent || "";
      return parseFloat(costText.replace(/[^0-9.]/g, ""));
    });

    // Verify ascending order
    for (let i = 1; i < costs.length; i++) {
      expect(costs[i]).toBeGreaterThanOrEqual(costs[i - 1]);
    }
  });
});

// ==================== MT-008: Filtering ====================

describe("Filtering by Provider", () => {
  it("shows provider filter dropdown", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
        showFilter={true}
      />
    );

    expect(screen.getByTestId("provider-filter")).toBeInTheDocument();
  });

  it("filters models by selected provider", async () => {
    const user = userEvent.setup();

    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
        showFilter={true}
      />
    );

    const filter = screen.getByTestId("provider-filter");
    await user.selectOptions(filter, "OpenAI");

    // Should only show OpenAI models
    expect(screen.getByText("GPT-4o Mini")).toBeInTheDocument();
    expect(screen.queryByText("Claude 3 Haiku")).not.toBeInTheDocument();
  });

  it("shows all models when "All" selected", async () => {
    const user = userEvent.setup();

    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
        showFilter={true}
      />
    );

    const filter = screen.getByTestId("provider-filter");
    await user.selectOptions(filter, "All");

    expect(screen.getByText("GPT-4o Mini")).toBeInTheDocument();
    expect(screen.getByText("Claude 3 Haiku")).toBeInTheDocument();
  });

  it("shows unique providers in filter options", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
        showFilter={true}
      />
    );

    const filter = screen.getByTestId("provider-filter");
    const options = within(filter).getAllByRole("option");

    expect(options.length).toBe(5); // All + 4 providers
  });
});

// ==================== MT-009: Model Links ====================

describe("Model Links", () => {
  it("model name is clickable", async () => {
    const user = userEvent.setup();

    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
        onModelClick={mockHandlers.onModelClick}
      />
    );

    const modelName = screen.getByText("GPT-4o Mini");
    await user.click(modelName);

    expect(mockHandlers.onModelClick).toHaveBeenCalledWith("gpt-4o-mini");
  });

  it("model name has hover styling", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
        onModelClick={mockHandlers.onModelClick}
      />
    );

    const modelName = screen.getByText("GPT-4o Mini");
    expect(modelName.className).toMatch(/cursor-pointer|hover/);
  });
});

// ==================== MT-010: Responsive Design ====================

describe("Responsive Design", () => {
  it("table has horizontal scroll wrapper", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    const wrapper = screen.getByTestId("table-wrapper");
    expect(wrapper.className).toMatch(/overflow/);
  });

  it("table has minimum width", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    const table = screen.getByRole("table");
    expect(table.className).toMatch(/min-w/);
  });
});

// ==================== MT-011: Empty State ====================

describe("Empty State", () => {
  it("shows empty message when no models available", () => {
    render(
      <ModelPricingTable
        models={[]}
        isLoading={false}
        error={null}
      />
    );

    expect(screen.getByText(/no models available/i)).toBeInTheDocument();
  });

  it("shows filtered empty state when no models match filter", async () => {
    const user = userEvent.setup();

    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
        showFilter={true}
      />
    );

    // Filter by a provider not in the list
    const filter = screen.getByTestId("provider-filter");
    // Assuming we can add a custom provider for testing
    // Otherwise, we'd test with a real provider that has no models
  });
});

// ==================== MT-012: Accessibility ====================

describe("Accessibility", () => {
  it("table has caption", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    const table = screen.getByRole("table");
    const caption = within(table).getByText(/AI Model Pricing/i);
    expect(caption).toBeInTheDocument();
  });

  it("sortable headers have aria-sort attribute", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    const costHeader = screen.getByText(/cost.*analysis/i);
    expect(costHeader).toHaveAttribute("aria-sort");
  });

  it("table rows have correct scope", () => {
    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    const table = screen.getByRole("table");
    const headers = within(table).getAllByRole("columnheader");
    
    headers.forEach((header) => {
      expect(header).toHaveAttribute("scope", "col");
    });
  });

  it("supports keyboard navigation", async () => {
    const user = userEvent.setup();

    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
        showFilter={true}
      />
    );

    // Tab to filter
    await user.tab();
    const filter = screen.getByTestId("provider-filter");
    expect(filter).toHaveFocus();
  });
});

// ==================== MT-013: Description Tooltip ====================

describe("Model Description", () => {
  it("shows description on hover", async () => {
    const user = userEvent.setup();

    render(
      <ModelPricingTable
        models={mockModels}
        isLoading={false}
        error={null}
      />
    );

    const modelName = screen.getByText("GPT-4o Mini");
    await user.hover(modelName);

    // Tooltip or description should appear
    expect(screen.getByText(/Fast and efficient/)).toBeVisible();
  });
});
