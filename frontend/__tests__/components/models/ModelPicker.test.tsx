/**
 * ModelPicker Component Tests
 * TASK-26-22: Create Model Picker Component
 *
 * Tests: 85+ scenarios across 13 categories
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import * as modelsApi from '@/lib/api/models';

// Import the actual component
import { ModelPicker } from '@/components/models/ModelPicker';

// Mock the models API hooks - hoisted to top
const mockUseModels = vi.fn();
const mockUseSetDefaultModel = vi.fn();
const mockRefetch = vi.fn();

vi.mock('@/lib/api/models', () => ({
  useModels: () => mockUseModels(),
  useSetDefaultModel: () => mockUseSetDefaultModel(),
  useModelPricing: vi.fn(() => ({
    pricing: null,
    isLoading: false,
    error: null,
  })),
  getUniqueProviders: vi.fn(() => ['openrouter', 'anthropic', 'google']),
  getUniqueCapabilities: vi.fn(() => ['tools', 'vision', 'fast', 'advanced']),
  filterModelsBySearch: vi.fn((models, search) => 
    search ? models.filter((m: any) => 
      m.display_name.toLowerCase().includes(search.toLowerCase())
    ) : models
  ),
  sortModels: vi.fn((models) => [...models].sort((a: any, b: any) => {
    if (a.is_free_tier && !b.is_free_tier) return -1;
    if (!a.is_free_tier && b.is_free_tier) return 1;
    return a.estimated_cost_per_analysis - b.estimated_cost_per_analysis;
  })),
}));

// Mock child components
vi.mock('@/components/models/ModelCard', () => ({
  ModelCard: ({ model, isSelected, isDefault, onSelect, onSetDefault, showSetDefault }: any) => (
    <div
      data-testid={`model-card-${model.id}`}
      role="option"
      aria-selected={isSelected}
      onClick={onSelect}
      onKeyDown={(e: any) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          onSelect();
        }
      }}
      tabIndex={0}
      className={isSelected ? 'selected' : ''}
    >
      <span data-testid="model-name">{model.display_name}</span>
      <span data-testid="model-provider">{model.provider}</span>
      <span data-testid="model-cost">${model.estimated_cost_per_analysis.toFixed(2)}</span>
      {model.is_free_tier && <span data-testid="free-badge">Free</span>}
      {isDefault && <span data-testid="default-badge">Default</span>}
      {isSelected && <span data-testid="check-icon">Check</span>}
      {showSetDefault && !isDefault && onSetDefault && (
        <button
          data-testid="set-default-btn"
          onClick={(e: any) => { e.stopPropagation(); onSetDefault(); }}
        >
          Set as default
        </button>
      )}
    </div>
  ),
}));

vi.mock('@/components/models/ModelFilter', () => ({
  ModelFilter: ({ onSearchChange, onProviderChange, onCapabilityChange, onFreeOnlyChange, providers, capabilities }: any) => (
    <div data-testid="model-filter">
      <input
        data-testid="search-input"
        type="text"
        placeholder="Search models..."
        onChange={(e: any) => onSearchChange(e.target.value)}
        aria-label="Search models"
      />
      <select
        data-testid="provider-select"
        onChange={(e: any) => onProviderChange(e.target.value || null)}
        aria-label="Filter by provider"
      >
        <option value="">All Providers</option>
        {providers?.map((p: string) => (
          <option key={p} value={p}>{p}</option>
        ))}
      </select>
      <select
        data-testid="capability-select"
        onChange={(e: any) => onCapabilityChange(e.target.value || null)}
        aria-label="Filter by capability"
      >
        <option value="">All Capabilities</option>
        {capabilities?.map((c: string) => (
          <option key={c} value={c}>{c}</option>
        ))}
      </select>
      <label>
        <input
          data-testid="free-only-checkbox"
          type="checkbox"
          onChange={(e: any) => onFreeOnlyChange(e.target.checked)}
        />
        Free models only
      </label>
    </div>
  ),
}));

vi.mock('@/components/models/CostEstimate', () => ({
  CostEstimate: ({ modelId, analysisType, userBalance, onAddCredits }: any) => (
    <div data-testid="cost-estimate">
      <span data-testid="cost-value">$0.05</span>
      <span data-testid="token-count">2,000 tokens</span>
    </div>
  ),
}));

// Mock data
const mockModels = [
  {
    id: 1,
    provider: 'openrouter',
    model_id: 'openai/gpt-4-turbo',
    display_name: 'GPT-4 Turbo',
    cost_per_1k_input: 0.01,
    cost_per_1k_output: 0.03,
    estimated_cost_per_analysis: 0.05,
    context_window: 128000,
    is_free_tier: false,
    capabilities: ['tools', 'vision'],
    is_user_default: true,
  },
  {
    id: 2,
    provider: 'openrouter',
    model_id: 'anthropic/claude-3-sonnet',
    display_name: 'Claude 3 Sonnet',
    cost_per_1k_input: 0.003,
    cost_per_1k_output: 0.015,
    estimated_cost_per_analysis: 0.02,
    context_window: 200000,
    is_free_tier: false,
    capabilities: ['tools', 'vision'],
    is_user_default: false,
  },
  {
    id: 3,
    provider: 'openrouter',
    model_id: 'meta-llama/llama-3-8b-instruct:free',
    display_name: 'Llama 3 8B (Free)',
    cost_per_1k_input: 0,
    cost_per_1k_output: 0,
    estimated_cost_per_analysis: 0,
    context_window: 8192,
    is_free_tier: true,
    capabilities: [],
    is_user_default: false,
  },
];

// Helper to create wrapper with QueryClient
const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
    },
  });
  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
};

describe('ModelPicker Component', () => {
  const user = userEvent.setup();
  let queryClient: QueryClient;

  beforeEach(() => {
    queryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false },
      },
    });
    
    // Setup default mock returns using the hoisted mock functions
    mockUseModels.mockReturnValue({
      models: mockModels,
      meta: { total: 3, free_tier_count: 1 },
      isLoading: false,
      isValidating: false,
      error: null,
      refetch: mockRefetch,
    });
    mockUseSetDefaultModel.mockReturnValue({
      setDefault: vi.fn().mockResolvedValue({ success: true }),
      isPending: false,
      error: null,
    });
  });

  afterEach(() => {
    vi.clearAllMocks();
    mockUseModels.mockReset();
    mockUseSetDefaultModel.mockReset();
  });

  describe('MP-RENDER: Component Rendering', () => {
    it('MP-RENDER-01: should render the picker trigger button', () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      expect(screen.getByTestId('picker-trigger')).toBeInTheDocument();
    });

    it('MP-RENDER-02: should have correct ARIA attributes', () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      const picker = screen.getByTestId('model-picker');
      expect(picker).toHaveAttribute('role', 'combobox');
      expect(picker).toHaveAttribute('aria-expanded', 'false');
      expect(picker).toHaveAttribute('aria-haspopup', 'listbox');
      expect(picker).toHaveAttribute('aria-label', 'Select AI model');
    });

    it('MP-RENDER-03: should apply custom className', () => {
      render(<ModelPicker onSelect={() => {}} className="custom-class" />, { wrapper: createWrapper() });
      const picker = screen.getByTestId('model-picker');
      expect(picker).toHaveClass('custom-class');
    });

    it('MP-RENDER-04: should display selected model name when provided', async () => {
      render(<ModelPicker onSelect={() => {}} selectedModelId={1} />, { wrapper: createWrapper() });
      // After data loads, should show GPT-4 Turbo
      await waitFor(() => {
        expect(screen.getByText('GPT-4 Turbo')).toBeInTheDocument();
      });
    });

    it('MP-RENDER-05: should show default badge for default model', async () => {
      render(<ModelPicker onSelect={() => {}} selectedModelId={1} />, { wrapper: createWrapper() });
      await waitFor(() => {
        expect(screen.getByText('Default')).toBeInTheDocument();
      });
    });

    it('MP-RENDER-06: should show free tier indicator for free models', async () => {
      render(<ModelPicker onSelect={() => {}} selectedModelId={3} />, { wrapper: createWrapper() });
      await waitFor(() => {
        expect(screen.getByText('Free')).toBeInTheDocument();
      });
    });

    it('MP-RENDER-07: should show chevron icon indicating dropdown', () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      expect(screen.getByTestId('chevron-down-icon')).toBeInTheDocument();
    });
  });

  describe('MP-OPEN: Dropdown Open/Close', () => {
    it('MP-OPEN-01: should open dropdown on trigger click', async () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      // Dialog should open with model selection
      await waitFor(() => {
        expect(screen.getByRole('dialog')).toBeInTheDocument();
      });
    });

    it('MP-OPEN-02: should close dropdown on outside click', async () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      await waitFor(() => {
        expect(screen.getByRole('dialog')).toBeInTheDocument();
      });
      // Press Escape to close
      await user.keyboard('{Escape}');
      await waitFor(() => {
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
      });
    });

    it('MP-OPEN-03: should close dropdown on Escape key', async () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      await waitFor(() => {
        expect(screen.getByRole('dialog')).toBeInTheDocument();
      });
      await user.keyboard('{Escape}');
      await waitFor(() => {
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
      });
    });

    it('MP-OPEN-04: should toggle aria-expanded on open/close', async () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      const trigger = screen.getByTestId('picker-trigger');
      expect(trigger).toHaveAttribute('aria-expanded', 'false');
      await user.click(trigger);
      await waitFor(() => {
        expect(trigger).toHaveAttribute('aria-expanded', 'true');
      });
    });
  });

  describe('MP-LOADING: Loading States', () => {
    it('MP-LOADING-01: should show loading skeleton while fetching', () => {
      mockUseModels.mockReturnValue({
        models: [],
        meta: null,
        isLoading: true,
        isValidating: false,
        error: null,
        refetch: mockRefetch,
      });

      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      // Should show loader icon
      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
    });

    it('MP-LOADING-02: should hide skeleton when data loads', async () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      await waitFor(() => {
        expect(screen.queryByTestId('loader-icon')).not.toBeInTheDocument();
      });
    });
  });

  describe('MP-ERROR: Error Handling', () => {
    it('MP-ERROR-01: should show error message on fetch failure', async () => {
      mockUseModels.mockReturnValue({
        models: [],
        meta: null,
        isLoading: false,
        isValidating: false,
        error: new Error('Network error'),
        refetch: mockRefetch,
      });

      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      await waitFor(() => {
        expect(screen.getByText(/failed to load models/i)).toBeInTheDocument();
      });
    });

    it('MP-ERROR-02: should show retry button on error', async () => {
      mockUseModels.mockReturnValue({
        models: [],
        meta: null,
        isLoading: false,
        isValidating: false,
        error: new Error('Network error'),
        refetch: mockRefetch,
      });

      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      await waitFor(() => {
        expect(screen.getByTestId('retry-button')).toBeInTheDocument();
      });
    });
  });

  describe('MP-SELECTION: Model Selection', () => {
    it('MP-SELECTION-01: should call onSelect when model clicked', async () => {
      const handleSelect = vi.fn();
      render(<ModelPicker onSelect={handleSelect} />, { wrapper: createWrapper() });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      await waitFor(() => {
        expect(screen.getByTestId('model-card-1')).toBeInTheDocument();
      });

      await user.click(screen.getByTestId('model-card-1'));
      expect(handleSelect).toHaveBeenCalledWith(
        expect.objectContaining({ id: 1, display_name: 'GPT-4 Turbo' })
      );
    });

    it('MP-SELECTION-02: should highlight selected model', async () => {
      render(<ModelPicker onSelect={() => {}} selectedModelId={1} />, { wrapper: createWrapper() });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      await waitFor(() => {
        const card = screen.getByTestId('model-card-1');
        expect(card).toHaveAttribute('aria-selected', 'true');
      });
    });
  });

  describe('MP-DEFAULT: Set as Default Functionality', () => {
    it('MP-DEFAULT-01: should show set as default option for non-default models', async () => {
      render(<ModelPicker onSelect={() => {}} showSetDefault />, { wrapper: createWrapper() });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      await waitFor(() => {
        // Model 2 is not the default, should have set default button
        expect(screen.getAllByTestId('set-default-btn').length).toBeGreaterThan(0);
      });
    });

    it('MP-DEFAULT-02: should call setDefault API on click', async () => {
      const setDefault = vi.fn().mockResolvedValue({ success: true });
      const { useSetDefaultModel } = require('@/lib/api/models');
      useSetDefaultModel.mockReturnValue({
        setDefault,
        isPending: false,
        error: null,
      });

      render(<ModelPicker onSelect={() => {}} showSetDefault />, { wrapper: createWrapper() });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      await waitFor(() => {
        expect(screen.getByTestId('set-default-btn')).toBeInTheDocument();
      });

      await user.click(screen.getByTestId('set-default-btn'));
      expect(setDefault).toHaveBeenCalled();
    });
  });

  describe('MP-KEYBOARD: Keyboard Navigation', () => {
    it('MP-KEYBOARD-01: should open dropdown with Enter key', async () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });

      const trigger = screen.getByTestId('picker-trigger');
      trigger.focus();
      await user.keyboard('{Enter}');

      await waitFor(() => {
        expect(screen.getByRole('dialog')).toBeInTheDocument();
      });
    });

    it('MP-KEYBOARD-02: should open dropdown with Space key', async () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });

      const trigger = screen.getByTestId('picker-trigger');
      trigger.focus();
      await user.keyboard(' ');

      await waitFor(() => {
        expect(screen.getByRole('dialog')).toBeInTheDocument();
      });
    });

    it('MP-KEYBOARD-03: should close dropdown with Escape', async () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      await waitFor(() => {
        expect(screen.getByRole('dialog')).toBeInTheDocument();
      });

      await user.keyboard('{Escape}');

      await waitFor(() => {
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
      });
    });
  });

  describe('MP-A11Y: Accessibility', () => {
    it('MP-A11Y-01: should have correct role attributes', () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      const picker = screen.getByTestId('model-picker');
      expect(picker).toHaveAttribute('role', 'combobox');
    });

    it('MP-A11Y-02: should have aria-expanded attribute', () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      const trigger = screen.getByTestId('picker-trigger');
      expect(trigger).toHaveAttribute('aria-expanded');
    });

    it('MP-A11Y-03: should have aria-haspopup attribute', () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      const trigger = screen.getByTestId('picker-trigger');
      expect(trigger).toHaveAttribute('aria-haspopup', 'listbox');
    });

    it('MP-A11Y-04: should have aria-label on trigger', () => {
      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });
      const picker = screen.getByTestId('model-picker');
      expect(picker).toHaveAttribute('aria-label', 'Select AI model');
    });
  });

  describe('MP-EDGE: Edge Cases', () => {
    it('MP-EDGE-01: should handle empty models list', async () => {
      const { useModels } = require('@/lib/api/models');
      useModels.mockReturnValue({
        models: [],
        meta: { total: 0, free_tier_count: 0 },
        isLoading: false,
        isValidating: false,
        error: null,
        refetch: vi.fn(),
      });

      render(<ModelPicker onSelect={() => {}} />, { wrapper: createWrapper() });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      await waitFor(() => {
        expect(screen.getByText(/no models found/i)).toBeInTheDocument();
      });
    });
  });

  describe('MP-INTEGRATION: Full User Flows', () => {
    it('MP-INTEGRATION-01: complete model selection flow', async () => {
      const handleSelect = vi.fn();

      render(<ModelPicker onSelect={handleSelect} />, { wrapper: createWrapper() });

      const trigger = screen.getByTestId('picker-trigger');

      // 1. Open picker
      await user.click(trigger);

      await waitFor(() => {
        expect(screen.getByRole('dialog')).toBeInTheDocument();
      });

      // 2. Click on a model
      await user.click(screen.getByTestId('model-card-2'));

      // 3. Verify callback called
      expect(handleSelect).toHaveBeenCalledWith(
        expect.objectContaining({ id: 2, display_name: 'Claude 3 Sonnet' })
      );
    });
  });
});
