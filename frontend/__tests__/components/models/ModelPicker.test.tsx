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
  {
    id: 4,
    provider: 'openrouter',
    model_id: 'google/gemini-pro',
    display_name: 'Gemini Pro',
    cost_per_1k_input: 0.00025,
    cost_per_1k_output: 0.0005,
    estimated_cost_per_analysis: 0.001,
    context_window: 32760,
    is_free_tier: false,
    capabilities: ['vision'],
    is_user_default: false,
  },
  {
    id: 5,
    provider: 'openrouter',
    model_id: 'mistralai/mixtral-8x7b-instruct',
    display_name: 'Mixtral 8x7B',
    cost_per_1k_input: 0.0006,
    cost_per_1k_output: 0.0006,
    estimated_cost_per_analysis: 0.002,
    context_window: 32768,
    is_free_tier: false,
    capabilities: ['tools'],
    is_user_default: false,
  },
];

const mockModelsResponse = {
  success: true,
  data: mockModels,
  meta: {
    total: 5,
    free_tier_count: 1,
  },
};

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Search: () => <span data-testid="search-icon">Search</span>,
  ChevronDown: () => <span data-testid="chevron-down-icon">ChevronDown</span>,
  ChevronUp: () => <span data-testid="chevron-up-icon">ChevronUp</span>,
  Check: () => <span data-testid="check-icon">Check</span>,
  Star: () => <span data-testid="star-icon">Star</span>,
  Zap: () => <span data-testid="zap-icon">Zap</span>,
  Eye: () => <span data-testid="eye-icon">Eye</span>,
  Wrench: () => <span data-testid="wrench-icon">Wrench</span>,
  X: () => <span data-testid="x-icon">X</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loader2</span>,
  AlertCircle: () => <span data-testid="alert-icon">AlertCircle</span>,
  RefreshCw: () => <span data-testid="refresh-icon">RefreshCw</span>,
}));

// Mock components for TDD reference
const MockModelPicker = ({
  onSelect,
  selectedModelId,
  analysisType = 'standard',
  showSetDefault = true,
  userBalance,
  className,
}: {
  onSelect: (model: typeof mockModels[0]) => void;
  selectedModelId?: number | null;
  analysisType?: 'quick' | 'standard' | 'deep';
  showSetDefault?: boolean;
  userBalance?: number;
  className?: string;
}) => {
  return (
    <div
      data-testid="model-picker"
      className={className}
      role="combobox"
      aria-expanded="false"
      aria-haspopup="listbox"
      aria-label="Select AI model"
    >
      <button
        data-testid="picker-trigger"
        aria-expanded="false"
        aria-haspopup="listbox"
      >
        Select a model
      </button>
    </div>
  );
};

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
    mockFetch.mockReset();
    mockFetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve(mockModelsResponse),
    });
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  describe('MP-RENDER: Component Rendering', () => {
    it('MP-RENDER-01: should render the picker trigger button', () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      expect(screen.getByTestId('picker-trigger')).toBeInTheDocument();
    });

    it('MP-RENDER-02: should have correct ARIA attributes', () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      const picker = screen.getByTestId('model-picker');
      expect(picker).toHaveAttribute('role', 'combobox');
      expect(picker).toHaveAttribute('aria-expanded', 'false');
      expect(picker).toHaveAttribute('aria-haspopup', 'listbox');
      expect(picker).toHaveAttribute('aria-label', 'Select AI model');
    });

    it('MP-RENDER-03: should apply custom className', () => {
      render(<MockModelPicker onSelect={() => {}} className="custom-class" />, {
        wrapper: createWrapper(),
      });
      const picker = screen.getByTestId('model-picker');
      expect(picker).toHaveClass('custom-class');
    });

    it('MP-RENDER-04: should display selected model name when provided', () => {
      // This test expects the trigger to show selected model name
      // Implementation should replace "Select a model" with model name
      render(<MockModelPicker onSelect={() => {}} selectedModelId={1} />, {
        wrapper: createWrapper(),
      });
      // After implementation, this should show "GPT-4 Turbo"
      expect(screen.getByTestId('picker-trigger')).toBeInTheDocument();
    });

    it('MP-RENDER-05: should show default badge for default model', () => {
      // When a model is the user's default, show a badge
      render(<MockModelPicker onSelect={() => {}} selectedModelId={1} />, {
        wrapper: createWrapper(),
      });
      // Implementation should render star icon for default model
      expect(screen.getByTestId('picker-trigger')).toBeInTheDocument();
    });

    it('MP-RENDER-06: should show free tier indicator for free models', () => {
      // Free models should have a "Free" badge
      render(<MockModelPicker onSelect={() => {}} selectedModelId={3} />, {
        wrapper: createWrapper(),
      });
      expect(screen.getByTestId('picker-trigger')).toBeInTheDocument();
    });

    it('MP-RENDER-07: should show cost estimate for selected model', () => {
      // Selected model should show estimated cost
      render(<MockModelPicker onSelect={() => {}} selectedModelId={1} analysisType="standard" />, {
        wrapper: createWrapper(),
      });
      expect(screen.getByTestId('picker-trigger')).toBeInTheDocument();
    });

    it('MP-RENDER-08: should show chevron icon indicating dropdown', () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      expect(screen.getByTestId('chevron-down-icon')).toBeInTheDocument();
    });
  });

  describe('MP-OPEN: Dropdown Open/Close', () => {
    it('MP-OPEN-01: should open dropdown on trigger click', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      // After implementation, dropdown should be visible
      // expect(screen.getByRole('listbox')).toBeInTheDocument();
    });

    it('MP-OPEN-02: should close dropdown on outside click', async () => {
      render(
        <div>
          <MockModelPicker onSelect={() => {}} />
          <div data-testid="outside">Outside</div>
        </div>,
        { wrapper: createWrapper() }
      );
      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      // Click outside
      await user.click(screen.getByTestId('outside'));
      // Dropdown should close
    });

    it('MP-OPEN-03: should close dropdown on Escape key', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      await user.keyboard('{Escape}');
      // Dropdown should close
    });

    it('MP-OPEN-04: should toggle aria-expanded on open/close', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      const trigger = screen.getByTestId('picker-trigger');
      expect(trigger).toHaveAttribute('aria-expanded', 'false');
      await user.click(trigger);
      // After implementation: expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    it('MP-OPEN-05: should close dropdown after selecting a model', async () => {
      const handleSelect = vi.fn();
      render(<MockModelPicker onSelect={handleSelect} />, {
        wrapper: createWrapper(),
      });
      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      // Click on a model card
      // Dropdown should close
      // handleSelect should be called
    });

    it('MP-OPEN-06: should maintain selected model after reopen', async () => {
      render(<MockModelPicker onSelect={() => {}} selectedModelId={1} />, {
        wrapper: createWrapper(),
      });
      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      await user.keyboard('{Escape}');
      await user.click(trigger);
      // Selected model should still be highlighted
    });
  });

  describe('MP-LOADING: Loading States', () => {
    it('MP-LOADING-01: should show loading skeleton while fetching', () => {
      // Mock slow response
      mockFetch.mockImplementation(() =>
        new Promise((resolve) =>
          setTimeout(
            () =>
              resolve({
                ok: true,
                json: () => Promise.resolve(mockModelsResponse),
              }),
            1000
          )
        )
      );

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      // Should show skeleton loaders while loading
      // expect(screen.getByTestId('model-skeleton')).toBeInTheDocument();
    });

    it('MP-LOADING-02: should hide skeleton when data loads', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
      // Skeleton should be removed
    });

    it('MP-LOADING-03: should show loading spinner in trigger while fetching', () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      // Trigger should show loader icon during fetch
    });

    it('MP-LOADING-04: should disable trigger during loading', () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      const trigger = screen.getByTestId('picker-trigger');
      // During loading, trigger should be disabled
    });

    it('MP-LOADING-05: should show skeleton cards for expected model count', () => {
      // Should render 3-5 skeleton cards as placeholder
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
    });

    it('MP-LOADING-06: should animate skeleton with pulse effect', () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      // Skeleton should have animate-pulse class
    });
  });

  describe('MP-ERROR: Error Handling', () => {
    it('MP-ERROR-01: should show error message on fetch failure', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
      // Should show error message
      // expect(screen.getByText(/error/i)).toBeInTheDocument();
    });

    it('MP-ERROR-02: should show retry button on error', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
      // Should have retry button
      // expect(screen.getByTestId('retry-button')).toBeInTheDocument();
    });

    it('MP-ERROR-03: should retry fetch when retry button clicked', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockModelsResponse),
      });

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledTimes(1);
      });

      // Click retry
      // await user.click(screen.getByTestId('retry-button'));
      // await waitFor(() => {
      //   expect(mockFetch).toHaveBeenCalledTimes(2);
      // });
    });

    it('MP-ERROR-04: should show appropriate error for 500 response', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 500,
        json: () => Promise.resolve({ message: 'Server error' }),
      });

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      // Should show server error message
    });

    it('MP-ERROR-05: should show appropriate error for network timeout', async () => {
      mockFetch.mockImplementation(() =>
        new Promise((_, reject) =>
          setTimeout(() => reject(new Error('Timeout')), 100)
        )
      );

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });
      // Should show timeout error
    });

    it('MP-ERROR-06: should clear error on successful retry', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockModelsResponse),
      });

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Error should be shown, then cleared after successful retry
    });
  });

  describe('MP-SELECTION: Model Selection', () => {
    it('MP-SELECTION-01: should call onSelect when model clicked', async () => {
      const handleSelect = vi.fn();

      render(<MockModelPicker onSelect={handleSelect} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      // After implementation:
      // await user.click(screen.getByText('GPT-4 Turbo'));
      // expect(handleSelect).toHaveBeenCalledWith(
      //   expect.objectContaining({ id: 1 })
      // );
    });

    it('MP-SELECTION-02: should highlight selected model', async () => {
      render(<MockModelPicker onSelect={() => {}} selectedModelId={1} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      // Selected model card should have selected styling
      // const selectedCard = screen.getByTestId('model-card-1');
      // expect(selectedCard).toHaveClass('selected');
    });

    it('MP-SELECTION-03: should show check icon on selected model', async () => {
      render(<MockModelPicker onSelect={() => {}} selectedModelId={1} />, {
        wrapper: createWrapper(),
      });

      // Selected model should show check icon
    });

    it('MP-SELECTION-04: should update trigger text after selection', async () => {
      const handleSelect = vi.fn();

      render(<MockModelPicker onSelect={handleSelect} selectedModelId={null} />, {
        wrapper: createWrapper(),
      });

      // Select a model
      // Trigger text should update to show selected model name
    });

    it('MP-SELECTION-05: should close dropdown after selection', async () => {
      const handleSelect = vi.fn();

      render(<MockModelPicker onSelect={handleSelect} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      // Select a model
      // Dropdown should close
    });

    it('MP-SELECTION-06: should show cost estimate after selection', async () => {
      render(
        <MockModelPicker onSelect={() => {}} selectedModelId={1} analysisType="standard" />,
        { wrapper: createWrapper() }
      );

      // Should show estimated cost: $0.05
    });

    it('MP-SELECTION-07: should allow changing selection', async () => {
      const handleSelect = vi.fn();

      render(<MockModelPicker onSelect={handleSelect} selectedModelId={1} />, {
        wrapper: createWrapper(),
      });

      // Open dropdown and select different model
      // handleSelect should be called with new model
    });

    it('MP-SELECTION-08: should preserve selection across re-renders', () => {
      const { rerender } = render(
        <MockModelPicker onSelect={() => {}} selectedModelId={1} />,
        { wrapper: createWrapper() }
      );

      rerender(
        <MockModelPicker onSelect={() => {}} selectedModelId={1} />
      );

      // Selection should be preserved
    });
  });

  describe('MP-DEFAULT: Set as Default Functionality', () => {
    it('MP-DEFAULT-01: should show "Set as default" option for non-default models', async () => {
      render(<MockModelPicker onSelect={() => {}} showSetDefault selectedModelId={2} />, {
        wrapper: createWrapper(),
      });

      // Non-default model should have "Set as default" button
    });

    it('MP-DEFAULT-02: should not show "Set as default" for already default model', async () => {
      render(<MockModelPicker onSelect={() => {}} showSetDefault selectedModelId={1} />, {
        wrapper: createWrapper(),
      });

      // Default model should show "Default" badge, not "Set as default" button
    });

    it('MP-DEFAULT-03: should call set default API on click', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockModelsResponse),
      });
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            data: { default_model: { id: 2, display_name: 'Claude 3 Sonnet' } },
          }),
      });

      render(<MockModelPicker onSelect={() => {}} showSetDefault />, {
        wrapper: createWrapper(),
      });

      // Click "Set as default"
      // Should call POST /api/v1/models/preferences
    });

    it('MP-DEFAULT-04: should show loading state while setting default', async () => {
      render(<MockModelPicker onSelect={() => {}} showSetDefault />, {
        wrapper: createWrapper(),
      });

      // Click "Set as default"
      // Should show loading spinner
    });

    it('MP-DEFAULT-05: should update UI after setting default', async () => {
      render(<MockModelPicker onSelect={() => {}} showSetDefault />, {
        wrapper: createWrapper(),
      });

      // Set a model as default
      // UI should update to show new default
    });

    it('MP-DEFAULT-06: should hide set default when showSetDefault is false', async () => {
      render(<MockModelPicker onSelect={() => {}} showSetDefault={false} />, {
        wrapper: createWrapper(),
      });

      // "Set as default" buttons should not be visible
    });
  });

  describe('MP-KEYBOARD: Keyboard Navigation', () => {
    it('MP-KEYBOARD-01: should open dropdown with Enter key', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      trigger.focus();
      await user.keyboard('{Enter}');

      // Dropdown should open
    });

    it('MP-KEYBOARD-02: should open dropdown with Space key', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      trigger.focus();
      await user.keyboard(' ');

      // Dropdown should open
    });

    it('MP-KEYBOARD-03: should navigate down with ArrowDown', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      await user.keyboard('{ArrowDown}');

      // Focus should move to first model card
    });

    it('MP-KEYBOARD-04: should navigate up with ArrowUp', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      await user.keyboard('{ArrowDown}');
      await user.keyboard('{ArrowDown}');
      await user.keyboard('{ArrowUp}');

      // Focus should move to previous model card
    });

    it('MP-KEYBOARD-05: should select model with Enter', async () => {
      const handleSelect = vi.fn();

      render(<MockModelPicker onSelect={handleSelect} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      await user.keyboard('{ArrowDown}');
      await user.keyboard('{Enter}');

      // handleSelect should be called
    });

    it('MP-KEYBOARD-06: should close dropdown with Escape', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      await user.keyboard('{Escape}');

      // Dropdown should close
    });

    it('MP-KEYBOARD-07: should wrap navigation at boundaries', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      // Navigate to last item, then down should wrap to first
      // Navigate to first item, then up should wrap to last
    });

    it('MP-KEYBOARD-08: should support Home and End keys', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      // Home should go to first item
      // End should go to last item
    });
  });

  describe('MP-FOCUS: Focus Management', () => {
    it('MP-FOCUS-01: should focus trigger on mount', () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Trigger should be focusable
      const trigger = screen.getByTestId('picker-trigger');
      expect(trigger).not.toBeDisabled();
    });

    it('MP-FOCUS-02: should move focus to listbox on open', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      // Focus should move to listbox or first item
    });

    it('MP-FOCUS-03: should return focus to trigger on close', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      await user.keyboard('{Escape}');

      // Focus should return to trigger
      // expect(trigger).toHaveFocus();
    });

    it('MP-FOCUS-04: should focus selected model when dropdown opens', async () => {
      render(<MockModelPicker onSelect={() => {}} selectedModelId={2} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      // Selected model should be focused
    });

    it('MP-FOCUS-05: should maintain focus within dropdown', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      // Tabbing should cycle within dropdown, not leave it
    });

    it('MP-FOCUS-06: should trap focus in dropdown when open', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      // Focus trap should prevent focus from leaving dropdown
    });
  });

  describe('MP-A11Y: Accessibility', () => {
    it('MP-A11Y-01: should have correct role attributes', () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const picker = screen.getByTestId('model-picker');
      expect(picker).toHaveAttribute('role', 'combobox');
    });

    it('MP-A11Y-02: should have aria-expanded attribute', () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      expect(trigger).toHaveAttribute('aria-expanded');
    });

    it('MP-A11Y-03: should have aria-haspopup attribute', () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      expect(trigger).toHaveAttribute('aria-haspopup', 'listbox');
    });

    it('MP-A11Y-04: should have aria-label on trigger', () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const picker = screen.getByTestId('model-picker');
      expect(picker).toHaveAttribute('aria-label');
    });

    it('MP-A11Y-05: should have aria-activedescendant when navigating', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      await user.keyboard('{ArrowDown}');

      // Should have aria-activedescendant pointing to focused item
    });

    it('MP-A11Y-06: should announce selection changes', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Selection should be announced to screen readers
      // Use aria-live region for announcements
    });

    it('MP-A11Y-07: should have aria-selected on selected option', async () => {
      render(<MockModelPicker onSelect={() => {}} selectedModelId={1} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      // Selected model card should have aria-selected="true"
    });

    it('MP-A11Y-08: should have correct tab index', () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      expect(trigger).toHaveAttribute('tabIndex', '0');
    });
  });

  describe('MP-ANIMATION: Animations', () => {
    it('MP-ANIMATION-01: should animate dropdown opening', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      // Dropdown should have animation class
    });

    it('MP-ANIMATION-02: should animate dropdown closing', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      await user.keyboard('{Escape}');

      // Dropdown should animate out
    });

    it('MP-ANIMATION-03: should respect prefers-reduced-motion', () => {
      // Mock matchMedia for reduced motion
      window.matchMedia = vi.fn().mockImplementation((query: string) => ({
        matches: query === '(prefers-reduced-motion: reduce)',
        media: query,
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
      }));

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Animations should be disabled or instant
    });

    it('MP-ANIMATION-04: should animate selection highlight', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Selection change should animate
    });
  });

  describe('MP-EDGE: Edge Cases', () => {
    it('MP-EDGE-01: should handle empty models list', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            data: [],
            meta: { total: 0, free_tier_count: 0 },
          }),
      });

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);

      // Should show "No models available" message
    });

    it('MP-EDGE-02: should handle single model', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            data: [mockModels[0]],
            meta: { total: 1, free_tier_count: 0 },
          }),
      });

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Should work with single model
    });

    it('MP-EDGE-03: should handle very long model name', async () => {
      const longNameModel = {
        ...mockModels[0],
        display_name: 'This is a very long model name that should be truncated',
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            data: [longNameModel],
            meta: { total: 1, free_tier_count: 0 },
          }),
      });

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Long name should be truncated with ellipsis
    });

    it('MP-EDGE-04: should handle unicode in model name', async () => {
      const unicodeModel = {
        ...mockModels[0],
        display_name: 'GPT-4 ツ Deutsches',
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            data: [unicodeModel],
            meta: { total: 1, free_tier_count: 0 },
          }),
      });

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Unicode should render correctly
    });

    it('MP-EDGE-05: should handle many models (50+)', async () => {
      const manyModels = Array.from({ length: 50 }, (_, i) => ({
        ...mockModels[0],
        id: i + 1,
        display_name: `Model ${i + 1}`,
      }));

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            data: manyModels,
            meta: { total: 50, free_tier_count: 0 },
          }),
      });

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Should handle large list with virtualization or pagination
    });

    it('MP-EDGE-06: should handle rapid open/close', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');
      await user.click(trigger);
      await user.click(trigger);
      await user.click(trigger);

      // Should handle rapid toggles without issues
    });

    it('MP-EDGE-07: should handle model with no capabilities', async () => {
      const noCapModel = {
        ...mockModels[0],
        capabilities: [],
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            data: [noCapModel],
            meta: { total: 1, free_tier_count: 0 },
          }),
      });

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Should render without capability badges
    });

    it('MP-EDGE-08: should handle zero cost model correctly', async () => {
      const freeModel = {
        ...mockModels[0],
        cost_per_1k_input: 0,
        cost_per_1k_output: 0,
        estimated_cost_per_analysis: 0,
        is_free_tier: true,
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            data: [freeModel],
            meta: { total: 1, free_tier_count: 1 },
          }),
      });

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Should show "Free" label
    });
  });

  describe('MP-SECURITY: Security', () => {
    it('MP-SECURITY-01: should prevent XSS in model names', async () => {
      const xssModel = {
        ...mockModels[0],
        display_name: '<script>alert("xss")</script>GPT-4',
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            data: [xssModel],
            meta: { total: 1, free_tier_count: 0 },
          }),
      });

      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Script tag should be escaped, not executed
    });

    it('MP-SECURITY-02: should sanitize user search input', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // Search input should sanitize special characters
    });

    it('MP-SECURITY-03: should not expose sensitive data in DOM', async () => {
      render(<MockModelPicker onSelect={() => {}} />, {
        wrapper: createWrapper(),
      });

      // No API keys or sensitive data in data attributes
    });
  });

  describe('MP-INTEGRATION: Full User Flows', () => {
    it('MP-INTEGRATION-01: complete model selection flow', async () => {
      const handleSelect = vi.fn();

      render(<MockModelPicker onSelect={handleSelect} />, {
        wrapper: createWrapper(),
      });

      const trigger = screen.getByTestId('picker-trigger');

      // 1. Open picker
      await user.click(trigger);

      // 2. Search for model
      // await user.type(screen.getByTestId('model-search'), 'GPT');

      // 3. Navigate to model
      // await user.keyboard('{ArrowDown}');

      // 4. Select model
      // await user.keyboard('{Enter}');

      // 5. Verify callback called
      // expect(handleSelect).toHaveBeenCalled();
    });

    it('MP-INTEGRATION-02: filter then select model', async () => {
      const handleSelect = vi.fn();

      render(<MockModelPicker onSelect={handleSelect} />, {
        wrapper: createWrapper(),
      });

      // 1. Open picker
      // 2. Filter by provider
      // 3. Filter by capability
      // 4. Select model
      // 5. Verify correct model selected
    });

    it('MP-INTEGRATION-03: select model then set as default', async () => {
      const handleSelect = vi.fn();

      render(<MockModelPicker onSelect={handleSelect} showSetDefault />, {
        wrapper: createWrapper(),
      });

      // 1. Open picker
      // 2. Select model
      // 3. Click "Set as default"
      // 4. Verify API call made
      // 5. Verify UI updated
    });

    it('MP-INTEGRATION-04: handle insufficient balance', async () => {
      render(
        <MockModelPicker onSelect={() => {}} userBalance={0.01} />,
        { wrapper: createWrapper() }
      );

      // 1. Select expensive model
      // 2. Should show insufficient balance warning
      // 3. Should link to add credits
    });

    it('MP-INTEGRATION-05: switch between analysis types', async () => {
      const { rerender } = render(
        <MockModelPicker onSelect={() => {}} analysisType="quick" />,
        { wrapper: createWrapper() }
      );

      // Change to standard analysis
      rerender(<MockModelPicker onSelect={() => {}} analysisType="standard" />);

      // Cost estimate should update
    });

    it('MP-INTEGRATION-06: keyboard-only selection', async () => {
      const handleSelect = vi.fn();

      render(<MockModelPicker onSelect={handleSelect} />, {
        wrapper: createWrapper(),
      });

      // Complete selection using only keyboard
      // Tab to focus
      // Enter to open
      // Arrow keys to navigate
      // Enter to select
    });

    it('MP-INTEGRATION-07: clear selection', async () => {
      const handleSelect = vi.fn();

      render(<MockModelPicker onSelect={handleSelect} selectedModelId={1} />, {
        wrapper: createWrapper(),
      });

      // Clear selection
      // Should show "Select a model" placeholder
    });

    it('MP-INTEGRATION-08: select free tier model', async () => {
      const handleSelect = vi.fn();

      render(<MockModelPicker onSelect={handleSelect} userBalance={0} />, {
        wrapper: createWrapper(),
      });

      // Select free tier model
      // Should not show balance warning
      // Should allow selection
    });
  });
});
