/**
 * ModelPicker Component Tests - TASK-26-22
 *
 * Tests for the interactive model picker component that allows users
 * to select an AI model with pricing display and cost estimation.
 *
 * @see SPEC-026
 * @see TASK-26-22
 */

import { render, screen, within, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import ModelPicker from "@/components/models/ModelPicker";

// ==================== Mock Data ====================

const mockModels = [
  {
    id: 1,
    provider: "openrouter",
    model_id: "openai/gpt-4-turbo",
    display_name: "GPT-4 Turbo",
    cost_per_1k_input: 0.01,
    cost_per_1k_output: 0.03,
    estimated_cost_per_analysis: 0.05,
    context_window: 128000,
    is_free_tier: false,
    capabilities: ["tools", "vision"],
    is_user_default: true,
  },
  {
    id: 2,
    provider: "openrouter",
    model_id: "openai/gpt-4o-mini",
    display_name: "GPT-4o Mini",
    cost_per_1k_input: 0.00015,
    cost_per_1k_output: 0.0006,
    estimated_cost_per_analysis: 0.02,
    context_window: 128000,
    is_free_tier: true,
    capabilities: ["fast"],
    is_user_default: false,
  },
  {
    id: 3,
    provider: "anthropic",
    model_id: "anthropic/claude-3-haiku",
    display_name: "Claude 3 Haiku",
    cost_per_1k_input: 0.00025,
    cost_per_1k_output: 0.00125,
    estimated_cost_per_analysis: 0.025,
    context_window: 200000,
    is_free_tier: true,
    capabilities: ["fast", "advanced"],
    is_user_default: false,
  },
  {
    id: 4,
    provider: "google",
    model_id: "google/gemini-2-flash",
    display_name: "Gemini 2 Flash",
    cost_per_1k_input: 0.000075,
    cost_per_1k_output: 0.0003,
    estimated_cost_per_analysis: 0.015,
    context_window: 1000000,
    is_free_tier: true,
    capabilities: ["fast", "vision"],
    is_user_default: false,
  },
];

const mockPricing = {
  model: {
    id: 1,
    display_name: "GPT-4 Turbo",
    provider: "openrouter",
  },
  pricing: {
    cost_per_1m_input: 10.0,
    cost_per_1m_output: 30.0,
  },
  estimates: {
    quick_analysis: { tokens: 500, cost: 0.01 },
    standard_analysis: { tokens: 2000, cost: 0.05 },
    deep_analysis: { tokens: 5000, cost: 0.12 },
  },
};

const mockUserBalance = {
  credits: 10.50,
  free_analyses_remaining: 5,
};

const mockHandlers = {
  onSelect: jest.fn(),
  onSetDefault: jest.fn(),
  onClose: jest.fn(),
  onRetry: jest.fn(),
};

// ==================== Mock API Responses ====================

const mockFetchModels = jest.fn();
const mockFetchPricing = jest.fn();
const mockFetchBalance = jest.fn();

// Mock the API hooks
jest.mock("@/lib/hooks/useModels", () => ({
  useModels: () => ({
    models: mockModels,
    isLoading: false,
    error: null,
    refetch: mockFetchModels,
  }),
}));

jest.mock("@/lib/hooks/usePricing", () => ({
  usePricing: (modelId: number) => ({
    pricing: modelId ? mockPricing : null,
    isLoading: false,
    error: null,
  }),
}));

jest.mock("@/lib/hooks/useUserBalance", () => ({
  useUserBalance: () => ({
    balance: mockUserBalance,
    isLoading: false,
    error: null,
  }),
}));

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
});

// ==================== MP-001: Rendering ====================

describe("Rendering", () => {
  it("renders without crashing", () => {
    render(<ModelPicker />);

    expect(screen.getByTestId("model-picker")).toBeInTheDocument();
  });

  it("renders trigger button when closed", () => {
    render(<ModelPicker />);

    expect(screen.getByRole("button", { name: /select model/i })).toBeInTheDocument();
  });

  it("shows currently selected model in trigger", () => {
    render(<ModelPicker selectedModelId={1} />);

    expect(screen.getByText("GPT-4 Turbo")).toBeInTheDocument();
  });

  it("shows placeholder when no model selected", () => {
    render(<ModelPicker />);

    expect(screen.getByText(/select a model/i)).toBeInTheDocument();
  });

  it("shows default badge for user default model", () => {
    render(<ModelPicker selectedModelId={1} />);

    expect(screen.getByText(/default/i)).toBeInTheDocument();
  });
});

// ==================== MP-002: Opening/Closing ====================

describe("Opening and Closing", () => {
  it("opens picker when trigger clicked", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByTestId("model-picker-dropdown")).toBeInTheDocument();
  });

  it("closes picker when clicking outside", async () => {
    const user = userEvent.setup();

    render(
      <div>
        <ModelPicker />
        <div data-testid="outside">Outside</div>
      </div>
    );

    await user.click(screen.getByRole("button", { name: /select model/i }));
    expect(screen.getByTestId("model-picker-dropdown")).toBeInTheDocument();

    await user.click(screen.getByTestId("outside"));
    expect(screen.queryByTestId("model-picker-dropdown")).not.toBeInTheDocument();
  });

  it("closes picker when Escape pressed", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));
    expect(screen.getByTestId("model-picker-dropdown")).toBeInTheDocument();

    await user.keyboard("{Escape}");
    expect(screen.queryByTestId("model-picker-dropdown")).not.toBeInTheDocument();
  });

  it("calls onClose when picker closes", async () => {
    const user = userEvent.setup();

    render(<ModelPicker onClose={mockHandlers.onClose} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));
    await user.keyboard("{Escape}");

    expect(mockHandlers.onClose).toHaveBeenCalled();
  });
});

// ==================== MP-003: Model List Display ====================

describe("Model List Display", () => {
  it("displays all models when opened", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByText("GPT-4 Turbo")).toBeInTheDocument();
    expect(screen.getByText("GPT-4o Mini")).toBeInTheDocument();
    expect(screen.getByText("Claude 3 Haiku")).toBeInTheDocument();
    expect(screen.getByText("Gemini 2 Flash")).toBeInTheDocument();
  });

  it("displays model cards with correct information", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    // Check provider badges
    expect(screen.getByText("OpenAI")).toBeInTheDocument();
    expect(screen.getByText("Anthropic")).toBeInTheDocument();
    expect(screen.getByText("Google")).toBeInTheDocument();
  });

  it("displays cost per analysis for each model", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByText(/\$0\.05/)).toBeInTheDocument();
    expect(screen.getByText(/\$0\.02/)).toBeInTheDocument();
  });

  it("displays free tier indicator for free models", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const freeBadges = screen.getAllByText(/free/i);
    expect(freeBadges.length).toBeGreaterThan(0);
  });

  it("displays capability badges", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByText("tools")).toBeInTheDocument();
    expect(screen.getByText("vision")).toBeInTheDocument();
    expect(screen.getByText("fast")).toBeInTheDocument();
  });
});

// ==================== MP-004: Search Functionality ====================

describe("Search Functionality", () => {
  it("shows search input when picker is open", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByPlaceholderText(/search models/i)).toBeInTheDocument();
  });

  it("filters models by name when searching", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const searchInput = screen.getByPlaceholderText(/search models/i);
    await user.type(searchInput, "GPT");

    expect(screen.getByText("GPT-4 Turbo")).toBeInTheDocument();
    expect(screen.getByText("GPT-4o Mini")).toBeInTheDocument();
    expect(screen.queryByText("Claude 3 Haiku")).not.toBeInTheDocument();
  });

  it("filters models by provider when searching", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const searchInput = screen.getByPlaceholderText(/search models/i);
    await user.type(searchInput, "Claude");

    expect(screen.getByText("Claude 3 Haiku")).toBeInTheDocument();
    expect(screen.queryByText("GPT-4 Turbo")).not.toBeInTheDocument();
  });

  it("shows no results message when search has no matches", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const searchInput = screen.getByPlaceholderText(/search models/i);
    await user.type(searchInput, "NonExistentModel");

    expect(screen.getByText(/no models found/i)).toBeInTheDocument();
  });

  it("clears search when X button clicked", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const searchInput = screen.getByPlaceholderText(/search models/i);
    await user.type(searchInput, "GPT");

    const clearButton = screen.getByRole("button", { name: /clear search/i });
    await user.click(clearButton);

    expect(searchInput).toHaveValue("");
  });
});

// ==================== MP-005: Filtering ====================

describe("Filtering by Provider/Capability", () => {
  it("shows provider filter dropdown", async () => {
    const user = userEvent.setup();

    render(<ModelPicker showFilters={true} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByTestId("provider-filter")).toBeInTheDocument();
  });

  it("filters by selected provider", async () => {
    const user = userEvent.setup();

    render(<ModelPicker showFilters={true} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const providerFilter = screen.getByTestId("provider-filter");
    await user.selectOptions(providerFilter, "openrouter");

    expect(screen.getByText("GPT-4 Turbo")).toBeInTheDocument();
    expect(screen.getByText("GPT-4o Mini")).toBeInTheDocument();
    expect(screen.queryByText("Claude 3 Haiku")).not.toBeInTheDocument();
  });

  it("shows capability filter dropdown", async () => {
    const user = userEvent.setup();

    render(<ModelPicker showFilters={true} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByTestId("capability-filter")).toBeInTheDocument();
  });

  it("filters by selected capability", async () => {
    const user = userEvent.setup();

    render(<ModelPicker showFilters={true} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const capabilityFilter = screen.getByTestId("capability-filter");
    await user.selectOptions(capabilityFilter, "vision");

    // Only models with vision capability should show
    expect(screen.getByText("GPT-4 Turbo")).toBeInTheDocument();
    expect(screen.getByText("Gemini 2 Flash")).toBeInTheDocument();
    expect(screen.queryByText("Claude 3 Haiku")).not.toBeInTheDocument();
  });

  it("combines provider and capability filters", async () => {
    const user = userEvent.setup();

    render(<ModelPicker showFilters={true} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const providerFilter = screen.getByTestId("provider-filter");
    await user.selectOptions(providerFilter, "openrouter");

    const capabilityFilter = screen.getByTestId("capability-filter");
    await user.selectOptions(capabilityFilter, "vision");

    // GPT-4 Turbo has vision and is from OpenAI
    expect(screen.getByText("GPT-4 Turbo")).toBeInTheDocument();
    // GPT-4o Mini doesn't have vision
    expect(screen.queryByText("GPT-4o Mini")).not.toBeInTheDocument();
  });

  it("shows all models when filters cleared", async () => {
    const user = userEvent.setup();

    render(<ModelPicker showFilters={true} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const providerFilter = screen.getByTestId("provider-filter");
    await user.selectOptions(providerFilter, "openrouter");

    // Clear filter
    await user.selectOptions(providerFilter, "all");

    expect(screen.getByText("GPT-4 Turbo")).toBeInTheDocument();
    expect(screen.getByText("Claude 3 Haiku")).toBeInTheDocument();
  });
});

// ==================== MP-006: Selection ====================

describe("Model Selection", () => {
  it("highlights currently selected model", async () => {
    const user = userEvent.setup();

    render(<ModelPicker selectedModelId={1} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const selectedCard = screen.getByTestId("model-card-1");
    expect(selectedCard).toHaveClass(/selected|ring|border/);
  });

  it("calls onSelect when model is clicked", async () => {
    const user = userEvent.setup();

    render(<ModelPicker onSelect={mockHandlers.onSelect} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const modelCard = screen.getByTestId("model-card-2");
    await user.click(modelCard);

    expect(mockHandlers.onSelect).toHaveBeenCalledWith(2);
  });

  it("closes picker after selection", async () => {
    const user = userEvent.setup();

    render(<ModelPicker onSelect={mockHandlers.onSelect} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const modelCard = screen.getByTestId("model-card-2");
    await user.click(modelCard);

    expect(screen.queryByTestId("model-picker-dropdown")).not.toBeInTheDocument();
  });

  it("shows confirmation before selecting expensive model", async () => {
    const user = userEvent.setup();

    // User has low balance
    const lowBalance = { credits: 0.01, free_analyses_remaining: 0 };
    
    render(
      <ModelPicker
        onSelect={mockHandlers.onSelect}
        analysisType="deep"
      />
    );

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const expensiveModel = screen.getByTestId("model-card-1"); // GPT-4 Turbo at $0.05
    await user.click(expensiveModel);

    // Should show insufficient balance warning
    expect(screen.getByText(/insufficient.*balance/i)).toBeInTheDocument();
  });
});

// ==================== MP-007: Set as Default ====================

describe("Set as Default", () => {
  it("shows 'Set as default' option for non-default models", async () => {
    const user = userEvent.setup();

    render(<ModelPicker onSetDefault={mockHandlers.onSetDefault} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    // Hover over a non-default model
    const modelCard = screen.getByTestId("model-card-2");
    await user.hover(modelCard);

    expect(screen.getByText(/set as default/i)).toBeInTheDocument();
  });

  it("calls onSetDefault when 'Set as default' clicked", async () => {
    const user = userEvent.setup();

    render(<ModelPicker onSetDefault={mockHandlers.onSetDefault} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const setDefaultButton = screen.getByTestId("set-default-2");
    await user.click(setDefaultButton);

    expect(mockHandlers.onSetDefault).toHaveBeenCalledWith(2);
  });

  it("shows 'Default' badge for current default model", async () => {
    const user = userEvent.setup();

    render(<ModelPicker selectedModelId={1} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const defaultCard = screen.getByTestId("model-card-1");
    expect(within(defaultCard).getByText(/default/i)).toBeInTheDocument();
  });
});

// ==================== MP-008: Cost Estimation ====================

describe("Cost Estimation", () => {
  it("shows cost estimate before selection", async () => {
    const user = userEvent.setup();

    render(<ModelPicker analysisType="standard" />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    // Cost should be visible on each model card
    expect(screen.getByText(/\$0\.05/)).toBeInTheDocument();
  });

  it("compares cost to user balance", async () => {
    const user = userEvent.setup();

    render(<ModelPicker analysisType="deep" />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    // Should show balance comparison
    expect(screen.getByText(/balance.*\$10\.50/i)).toBeInTheDocument();
  });

  it("shows warning for insufficient balance", async () => {
    const user = userEvent.setup();

    render(
      <ModelPicker
        analysisType="deep"
        userBalance={{ credits: 0.001, free_analyses_remaining: 0 }}
      />
    );

    await user.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByText(/insufficient balance/i)).toBeInTheDocument();
  });

  it("shows link to add credits when balance low", async () => {
    const user = userEvent.setup();

    render(
      <ModelPicker
        analysisType="deep"
        userBalance={{ credits: 0.001, free_analyses_remaining: 0 }}
      />
    );

    await user.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByRole("link", { name: /add credits/i })).toBeInTheDocument();
  });

  it("shows free analysis count", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByText(/5.*free.*remaining/i)).toBeInTheDocument();
  });
});

// ==================== MP-009: Keyboard Navigation ====================

describe("Keyboard Navigation", () => {
  it("opens picker with Enter key", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    const trigger = screen.getByRole("button", { name: /select model/i });
    trigger.focus();
    await user.keyboard("{Enter}");

    expect(screen.getByTestId("model-picker-dropdown")).toBeInTheDocument();
  });

  it("opens picker with Space key", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    const trigger = screen.getByRole("button", { name: /select model/i });
    trigger.focus();
    await user.keyboard(" ");

    expect(screen.getByTestId("model-picker-dropdown")).toBeInTheDocument();
  });

  it("navigates models with arrow keys", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const firstCard = screen.getByTestId("model-card-1");
    firstCard.focus();

    await user.keyboard("{ArrowDown}");

    const secondCard = screen.getByTestId("model-card-2");
    expect(secondCard).toHaveFocus();
  });

  it("selects model with Enter key", async () => {
    const user = userEvent.setup();

    render(<ModelPicker onSelect={mockHandlers.onSelect} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const firstCard = screen.getByTestId("model-card-1");
    firstCard.focus();
    await user.keyboard("{Enter}");

    expect(mockHandlers.onSelect).toHaveBeenCalledWith(1);
  });

  it("wraps navigation at list boundaries", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    // Navigate to last item
    const cards = screen.getAllByTestId(/model-card-/);
    const lastCard = cards[cards.length - 1];
    lastCard.focus();

    await user.keyboard("{ArrowDown}");

    // Should wrap to first
    const firstCard = screen.getByTestId("model-card-1");
    expect(firstCard).toHaveFocus();
  });

  it("focuses search input with keyboard shortcut", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    await user.keyboard("/");

    const searchInput = screen.getByPlaceholderText(/search models/i);
    expect(searchInput).toHaveFocus();
  });
});

// ==================== MP-010: Loading State ====================

describe("Loading State", () => {
  it("shows loading skeleton while fetching models", () => {
    // Override the mock for this test
    jest.spyOn(require("@/lib/hooks/useModels"), "useModels").mockReturnValue({
      models: [],
      isLoading: true,
      error: null,
    });

    render(<ModelPicker />);

    // Open picker
    fireEvent.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByTestId("models-loading-skeleton")).toBeInTheDocument();
  });

  it("disables trigger while loading", () => {
    jest.spyOn(require("@/lib/hooks/useModels"), "useModels").mockReturnValue({
      models: [],
      isLoading: true,
      error: null,
    });

    render(<ModelPicker />);

    const trigger = screen.getByRole("button", { name: /select model/i });
    expect(trigger).toBeDisabled();
  });
});

// ==================== MP-011: Error State ====================

describe("Error State", () => {
  it("shows error message when models fail to load", () => {
    jest.spyOn(require("@/lib/hooks/useModels"), "useModels").mockReturnValue({
      models: [],
      isLoading: false,
      error: new Error("Failed to fetch models"),
    });

    render(<ModelPicker />);

    fireEvent.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByText(/failed to load models/i)).toBeInTheDocument();
  });

  it("shows retry button on error", () => {
    jest.spyOn(require("@/lib/hooks/useModels"), "useModels").mockReturnValue({
      models: [],
      isLoading: false,
      error: new Error("Failed to fetch"),
    });

    render(<ModelPicker onRetry={mockHandlers.onRetry} />);

    fireEvent.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByRole("button", { name: /retry/i })).toBeInTheDocument();
  });

  it("calls onRetry when retry clicked", async () => {
    const user = userEvent.setup();

    jest.spyOn(require("@/lib/hooks/useModels"), "useModels").mockReturnValue({
      models: [],
      isLoading: false,
      error: new Error("Failed"),
    });

    render(<ModelPicker onRetry={mockHandlers.onRetry} />);

    fireEvent.click(screen.getByRole("button", { name: /select model/i }));

    await user.click(screen.getByRole("button", { name: /retry/i }));

    expect(mockHandlers.onRetry).toHaveBeenCalled();
  });
});

// ==================== MP-012: Empty State ====================

describe("Empty State", () => {
  it("shows empty message when no models available", () => {
    jest.spyOn(require("@/lib/hooks/useModels"), "useModels").mockReturnValue({
      models: [],
      isLoading: false,
      error: null,
    });

    render(<ModelPicker />);

    fireEvent.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByText(/no models available/i)).toBeInTheDocument();
  });

  it("shows appropriate message when filters return no results", async () => {
    const user = userEvent.setup();

    render(<ModelPicker showFilters={true} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const searchInput = screen.getByPlaceholderText(/search models/i);
    await user.type(searchInput, "NonExistentModel");

    expect(screen.getByText(/no models match.*filter/i)).toBeInTheDocument();
  });
});

// ==================== MP-013: Accessibility ====================

describe("Accessibility", () => {
  it("has correct ARIA attributes on trigger", () => {
    render(<ModelPicker />);

    const trigger = screen.getByRole("button", { name: /select model/i });
    expect(trigger).toHaveAttribute("aria-haspopup", "listbox");
    expect(trigger).toHaveAttribute("aria-expanded", "false");
  });

  it("updates aria-expanded when opened", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    const trigger = screen.getByRole("button", { name: /select model/i });
    await user.click(trigger);

    expect(trigger).toHaveAttribute("aria-expanded", "true");
  });

  it("has role listbox on dropdown", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    expect(screen.getByRole("listbox")).toBeInTheDocument();
  });

  it("has role option on model cards", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const options = screen.getAllByRole("option");
    expect(options.length).toBe(mockModels.length);
  });

  it("has aria-selected on selected model", async () => {
    const user = userEvent.setup();

    render(<ModelPicker selectedModelId={1} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const selectedOption = screen.getByRole("option", { selected: true });
    expect(selectedOption).toHaveAttribute("data-id", "1");
  });

  it("announces selection to screen readers", async () => {
    const user = userEvent.setup();

    render(<ModelPicker onSelect={mockHandlers.onSelect} />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const firstCard = screen.getByTestId("model-card-1");
    await user.click(firstCard);

    // Live region should announce selection
    await waitFor(() => {
      expect(screen.getByRole("status")).toHaveTextContent(/selected.*GPT-4 Turbo/i);
    });
  });

  it("has aria-label on search input", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const searchInput = screen.getByPlaceholderText(/search models/i);
    expect(searchInput).toHaveAttribute("aria-label");
  });

  it("supports focus trap within dropdown", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const searchInput = screen.getByPlaceholderText(/search models/i);
    searchInput.focus();

    // Tab through all focusable elements
    for (let i = 0; i < 10; i++) {
      await user.tab();
    }

    // Focus should still be within dropdown
    const dropdown = screen.getByTestId("model-picker-dropdown");
    expect(dropdown).toContainElement(document.activeElement);
  });
});

// ==================== MP-014: Animations ====================

describe("Animations", () => {
  it("has smooth open animation", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));

    const dropdown = screen.getByTestId("model-picker-dropdown");
    expect(dropdown.className).toMatch(/animate|transition/);
  });

  it("has smooth close animation", async () => {
    const user = userEvent.setup();

    render(<ModelPicker />);

    await user.click(screen.getByRole("button", { name: /select model/i }));
    await user.keyboard("{Escape}");

    // Animation class should be present during exit
    const dropdown = screen.queryByTestId("model-picker-dropdown");
    if (dropdown) {
      expect(dropdown.className).toMatch(/exit|close|leave/);
    }
  });
});

// ==================== MP-015: Responsive Design ====================

describe("Responsive Design", () => {
  it("renders as modal on mobile screens", () => {
    // Mock mobile viewport
    window.innerWidth = 375;
    window.dispatchEvent(new Event("resize"));

    render(<ModelPicker />);

    fireEvent.click(screen.getByRole("button", { name: /select model/i }));

    const dropdown = screen.getByTestId("model-picker-dropdown");
    expect(dropdown.className).toMatch(/modal|fullscreen|bottom-sheet/);
  });

  it("renders as dropdown on desktop screens", () => {
    // Mock desktop viewport
    window.innerWidth = 1024;
    window.dispatchEvent(new Event("resize"));

    render(<ModelPicker />);

    fireEvent.click(screen.getByRole("button", { name: /select model/i }));

    const dropdown = screen.getByTestId("model-picker-dropdown");
    expect(dropdown.className).toMatch(/dropdown|popover/);
  });

  it("is scrollable on mobile", async () => {
    window.innerWidth = 375;

    render(<ModelPicker />);

    await userEvent.setup().click(screen.getByRole("button", { name: /select model/i }));

    const dropdown = screen.getByTestId("model-picker-dropdown");
    expect(dropdown.className).toMatch(/overflow.*scroll/);
  });
});

// ==================== MP-016: Integration ====================

describe("Integration Scenarios", () => {
  it("completes full selection flow", async () => {
    const user = userEvent.setup();

    render(
      <ModelPicker
        onSelect={mockHandlers.onSelect}
        onSetDefault={mockHandlers.onSetDefault}
      />
    );

    // Open picker
    await user.click(screen.getByRole("button", { name: /select model/i }));

    // Search for model
    const searchInput = screen.getByPlaceholderText(/search models/i);
    await user.type(searchInput, "Claude");

    // Select model
    const claudeCard = screen.getByTestId("model-card-3");
    await user.click(claudeCard);

    // Verify selection
    expect(mockHandlers.onSelect).toHaveBeenCalledWith(3);
    expect(screen.queryByTestId("model-picker-dropdown")).not.toBeInTheDocument();
  });

  it("handles filter then select flow", async () => {
    const user = userEvent.setup();

    render(
      <ModelPicker
        onSelect={mockHandlers.onSelect}
        showFilters={true}
      />
    );

    // Open picker
    await user.click(screen.getByRole("button", { name: /select model/i }));

    // Filter by capability
    const capabilityFilter = screen.getByTestId("capability-filter");
    await user.selectOptions(capabilityFilter, "vision");

    // Select from filtered results
    const geminiCard = screen.getByTestId("model-card-4");
    await user.click(geminiCard);

    expect(mockHandlers.onSelect).toHaveBeenCalledWith(4);
  });
});
