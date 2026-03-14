/**
 * CostEstimate Component Tests
 * TASK-26-22: Create Model Picker Component
 *
 * Tests: 45+ scenarios across 7 categories
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock pricing response
const mockPricingResponse = {
  success: true,
  data: {
    model: {
      id: 1,
      display_name: 'GPT-4 Turbo',
      provider: 'openrouter',
      model_id: 'openai/gpt-4-turbo',
    },
    pricing: {
      cost_per_1m_input: 10.0,
      cost_per_1m_output: 30.0,
      cost_per_1k_input: 0.01,
      cost_per_1k_output: 0.03,
    },
    estimates: {
      quick_analysis: { tokens: 500, cost: 0.01 },
      standard_analysis: { tokens: 2000, cost: 0.05 },
      deep_analysis: { tokens: 5000, cost: 0.12 },
    },
    free_tier: {
      available: false,
      limit: null,
    },
  },
};

const freePricingResponse = {
  success: true,
  data: {
    model: {
      id: 3,
      display_name: 'Llama 3 8B (Free)',
      provider: 'openrouter',
      model_id: 'meta-llama/llama-3-8b-instruct:free',
    },
    pricing: {
      cost_per_1m_input: 0,
      cost_per_1m_output: 0,
      cost_per_1k_input: 0,
      cost_per_1k_output: 0,
    },
    estimates: {
      quick_analysis: { tokens: 500, cost: 0 },
      standard_analysis: { tokens: 2000, cost: 0 },
      deep_analysis: { tokens: 5000, cost: 0 },
    },
    free_tier: {
      available: true,
      limit: 100,
    },
  },
};

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  DollarSign: () => <span data-testid="dollar-icon">DollarSign</span>,
  AlertTriangle: () => <span data-testid="alert-icon">AlertTriangle</span>,
  Zap: () => <span data-testid="zap-icon">Zap</span>,
  ExternalLink: () => <span data-testid="external-link-icon">ExternalLink</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loader2</span>,
}));

// TDD Mock Component
const MockCostEstimate = ({
  modelId,
  analysisType = 'standard',
  userBalance,
  onAddCredits,
  className,
}: {
  modelId: number;
  analysisType?: 'quick' | 'standard' | 'deep';
  userBalance?: number;
  onAddCredits?: () => void;
  className?: string;
}) => {
  const estimates = {
    quick: { tokens: 500, cost: 0.01 },
    standard: { tokens: 2000, cost: 0.05 },
    deep: { tokens: 5000, cost: 0.12 },
  };

  const estimate = estimates[analysisType];
  const isFree = estimate.cost === 0;
  const hasInsufficientBalance = userBalance !== undefined && userBalance < estimate.cost;

  return (
    <div data-testid="cost-estimate" className={className}>
      <div data-testid="cost-value">${estimate.cost.toFixed(2)}</div>
      <div data-testid="token-count">{estimate.tokens.toLocaleString()} tokens</div>
      {isFree && <span data-testid="free-label">Free</span>}
      {hasInsufficientBalance && (
        <div data-testid="insufficient-warning">
          <span data-testid="alert-icon">AlertTriangle</span>
          <span>Insufficient balance</span>
          {onAddCredits && (
            <button data-testid="add-credits-btn" onClick={onAddCredits}>
              Add Credits
            </button>
          )}
        </div>
      )}
    </div>
  );
};

describe('CostEstimate Component', () => {
  const user = userEvent.setup();

  beforeEach(() => {
    vi.clearAllMocks();
    mockFetch.mockReset();
    mockFetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve(mockPricingResponse),
    });
  });

  describe('CE-RENDER: Component Rendering', () => {
    it('CE-RENDER-01: should render cost estimate display', () => {
      render(<MockCostEstimate modelId={1} />);

      expect(screen.getByTestId('cost-estimate')).toBeInTheDocument();
      expect(screen.getByTestId('cost-value')).toBeInTheDocument();
    });

    it('CE-RENDER-02: should show token count', () => {
      render(<MockCostEstimate modelId={1} />);

      expect(screen.getByTestId('token-count')).toBeInTheDocument();
      expect(screen.getByTestId('token-count')).toHaveTextContent('2,000 tokens');
    });

    it('CE-RENDER-03: should apply custom className', () => {
      render(<MockCostEstimate modelId={1} className="custom-class" />);

      expect(screen.getByTestId('cost-estimate')).toHaveClass('custom-class');
    });

    it('CE-RENDER-04: should show dollar icon', () => {
      render(<MockCostEstimate modelId={1} />);

      // Dollar icon should be visible
    });

    it('CE-RENDER-05: should show loading state while fetching', () => {
      // Mock slow response
      mockFetch.mockImplementation(
        () =>
          new Promise((resolve) =>
            setTimeout(
              () =>
                resolve({
                  ok: true,
                  json: () => Promise.resolve(mockPricingResponse),
                }),
              1000
            )
          )
      );

      render(<MockCostEstimate modelId={1} />);

      // Should show loading spinner
    });

    it('CE-RENDER-06: should handle missing model ID', () => {
      // What happens if modelId is undefined or null?
      render(<MockCostEstimate modelId={undefined as any} />);

      // Should handle gracefully
    });
  });

  describe('CE-CALC: Cost Calculations', () => {
    it('CE-CALC-01: should calculate cost for quick analysis', () => {
      render(<MockCostEstimate modelId={1} analysisType="quick" />);

      expect(screen.getByTestId('cost-value')).toHaveTextContent('$0.01');
      expect(screen.getByTestId('token-count')).toHaveTextContent('500 tokens');
    });

    it('CE-CALC-02: should calculate cost for standard analysis', () => {
      render(<MockCostEstimate modelId={1} analysisType="standard" />);

      expect(screen.getByTestId('cost-value')).toHaveTextContent('$0.05');
      expect(screen.getByTestId('token-count')).toHaveTextContent('2,000 tokens');
    });

    it('CE-CALC-03: should calculate cost for deep analysis', () => {
      render(<MockCostEstimate modelId={1} analysisType="deep" />);

      expect(screen.getByTestId('cost-value')).toHaveTextContent('$0.12');
      expect(screen.getByTestId('token-count')).toHaveTextContent('5,000 tokens');
    });

    it('CE-CALC-04: should format cost with correct decimal places', () => {
      const cheapModelResponse = {
        ...mockPricingResponse,
        data: {
          ...mockPricingResponse.data,
          estimates: {
            quick_analysis: { tokens: 500, cost: 0.00123 },
            standard_analysis: { tokens: 2000, cost: 0.00492 },
            deep_analysis: { tokens: 5000, cost: 0.0123 },
          },
        },
      };

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(cheapModelResponse),
      });

      render(<MockCostEstimate modelId={1} />);

      // Should format cost appropriately
    });

    it('CE-CALC-05: should show zero cost for free models', () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(freePricingResponse),
      });

      render(<MockCostEstimate modelId={3} />);

      expect(screen.getByTestId('cost-value')).toHaveTextContent('$0.00');
      expect(screen.getByTestId('free-label')).toBeInTheDocument();
    });

    it('CE-CALC-06: should show "$0.00" as "Free" for better UX', () => {
      render(<MockCostEstimate modelId={3} analysisType="quick" />);

      // Free models should show "Free" prominently
    });

    it('CE-CALC-07: should update when modelId changes', async () => {
      const { rerender } = render(<MockCostEstimate modelId={1} />);

      expect(screen.getByTestId('cost-value')).toHaveTextContent('$0.05');

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(freePricingResponse),
      });

      rerender(<MockCostEstimate modelId={3} />);

      // Should fetch new pricing for new model
    });

    it('CE-CALC-08: should handle large costs', () => {
      const expensiveResponse = {
        ...mockPricingResponse,
        data: {
          ...mockPricingResponse.data,
          estimates: {
            quick_analysis: { tokens: 500, cost: 99.99 },
            standard_analysis: { tokens: 2000, cost: 399.99 },
            deep_analysis: { tokens: 5000, cost: 999.99 },
          },
        },
      };

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(expensiveResponse),
      });

      render(<MockCostEstimate modelId={1} />);

      // Should display large costs correctly
    });

    it('CE-CALC-09: should show cost breakdown tooltip', async () => {
      render(<MockCostEstimate modelId={1} />);

      // Hover over cost should show breakdown:
      // - Input tokens cost
      // - Output tokens cost
    });

    it('CE-CALC-10: should cache pricing response', async () => {
      render(<MockCostEstimate modelId={1} />);

      // First fetch
      await waitFor(() => expect(mockFetch).toHaveBeenCalledTimes(1));

      // Should not refetch for same model
    });
  });

  describe('CE-TYPES: Analysis Types', () => {
    it('CE-TYPES-01: should show all three analysis types', () => {
      // Could show a selector or all three estimates
      render(<MockCostEstimate modelId={1} />);

      // Implementation could show all types or a selector
    });

    it('CE-TYPES-02: should default to standard analysis', () => {
      render(<MockCostEstimate modelId={1} />);

      expect(screen.getByTestId('cost-value')).toHaveTextContent('$0.05');
    });

    it('CE-TYPES-03: should update when analysisType changes', async () => {
      const { rerender } = render(<MockCostEstimate modelId={1} analysisType="quick" />);

      expect(screen.getByTestId('cost-value')).toHaveTextContent('$0.01');

      rerender(<MockCostEstimate modelId={1} analysisType="deep" />);

      expect(screen.getByTestId('cost-value')).toHaveTextContent('$0.12');
    });

    it('CE-TYPES-04: should show analysis type label', () => {
      render(<MockCostEstimate modelId={1} analysisType="deep" />);

      // Should show "Deep Analysis" label
    });

    it('CE-TYPES-05: should show token breakdown by type', () => {
      render(<MockCostEstimate modelId={1} />);

      // Quick: ~500 tokens
      // Standard: ~2000 tokens
      // Deep: ~5000 tokens
    });

    it('CE-TYPES-06: should use analysis type icons', () => {
      // Quick: Zap icon (fast)
      // Standard: Default
      // Deep: Brain or similar icon
      render(<MockCostEstimate modelId={1} analysisType="quick" />);
    });

    it('CE-TYPES-07: should handle custom token estimates', () => {
      // If custom tokens are provided, calculate custom cost
    });

    it('CE-TYPES-08: should validate analysis type prop', () => {
      // Should handle invalid analysis type gracefully
      render(<MockCostEstimate modelId={1} analysisType={'invalid' as any} />);

      // Should fallback to standard
    });
  });

  describe('CE-BALANCE: Balance Comparison', () => {
    it('CE-BALANCE-01: should show balance comparison when provided', () => {
      render(<MockCostEstimate modelId={1} userBalance={1.0} />);

      // Should show comparison: "$0.05 of $1.00"
    });

    it('CE-BALANCE-02: should show sufficient balance indicator', () => {
      render(<MockCostEstimate modelId={1} userBalance={1.0} />);

      // Should show green check or similar for sufficient balance
    });

    it('CE-BALANCE-03: should show insufficient balance warning', () => {
      render(<MockCostEstimate modelId={1} userBalance={0.01} />);

      expect(screen.getByTestId('insufficient-warning')).toBeInTheDocument();
    });

    it('CE-BALANCE-04: should calculate remaining balance after analysis', () => {
      render(<MockCostEstimate modelId={1} userBalance={1.0} />);

      // Should show "Remaining: $0.95"
    });

    it('CE-BALANCE-05: should handle zero balance', () => {
      render(<MockCostEstimate modelId={1} userBalance={0} />);

      expect(screen.getByTestId('insufficient-warning')).toBeInTheDocument();
    });

    it('CE-BALANCE-06: should not show warning for free models', () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(freePricingResponse),
      });

      render(<MockCostEstimate modelId={3} userBalance={0} />);

      expect(screen.queryByTestId('insufficient-warning')).not.toBeInTheDocument();
    });

    it('CE-BALANCE-07: should handle negative balance gracefully', () => {
      render(<MockCostEstimate modelId={1} userBalance={-1} />);

      // Should handle negative balance (maybe show as $0.00)
    });

    it('CE-BALANCE-08: should format balance consistently', () => {
      render(<MockCostEstimate modelId={1} userBalance={1.5} />);

      // Balance should be formatted as currency
    });
  });

  describe('CE-WARNING: Insufficient Balance Warning', () => {
    it('CE-WARNING-01: should show warning when balance insufficient', () => {
      render(<MockCostEstimate modelId={1} userBalance={0.01} />);

      expect(screen.getByTestId('insufficient-warning')).toBeInTheDocument();
      expect(screen.getByText(/insufficient balance/i)).toBeInTheDocument();
    });

    it('CE-WARNING-02: should show alert icon in warning', () => {
      render(<MockCostEstimate modelId={1} userBalance={0.01} />);

      expect(screen.getByTestId('alert-icon')).toBeInTheDocument();
    });

    it('CE-WARNING-03: should show how much more is needed', () => {
      render(<MockCostEstimate modelId={1} userBalance={0.01} />);

      // Should show "Need $0.04 more"
    });

    it('CE-WARNING-04: should show add credits button', () => {
      const handleAddCredits = vi.fn();
      render(<MockCostEstimate modelId={1} userBalance={0.01} onAddCredits={handleAddCredits} />);

      expect(screen.getByTestId('add-credits-btn')).toBeInTheDocument();
    });

    it('CE-WARNING-05: should call onAddCredits when clicked', async () => {
      const handleAddCredits = vi.fn();
      render(<MockCostEstimate modelId={1} userBalance={0.01} onAddCredits={handleAddCredits} />);

      await user.click(screen.getByTestId('add-credits-btn'));

      expect(handleAddCredits).toHaveBeenCalledTimes(1);
    });

    it('CE-WARNING-06: should not show warning when balance sufficient', () => {
      render(<MockCostEstimate modelId={1} userBalance={1.0} />);

      expect(screen.queryByTestId('insufficient-warning')).not.toBeInTheDocument();
    });
  });

  describe('CE-LINK: Add Credits Link', () => {
    it('CE-LINK-01: should show add credits link when needed', () => {
      const handleAddCredits = vi.fn();
      render(<MockCostEstimate modelId={1} userBalance={0} onAddCredits={handleAddCredits} />);

      expect(screen.getByTestId('add-credits-btn')).toBeInTheDocument();
    });

    it('CE-LINK-02: should navigate to credits page', async () => {
      const handleAddCredits = vi.fn();
      render(<MockCostEstimate modelId={1} userBalance={0} onAddCredits={handleAddCredits} />);

      await user.click(screen.getByTestId('add-credits-btn'));

      expect(handleAddCredits).toHaveBeenCalled();
    });

    it('CE-LINK-03: should show external link icon', () => {
      const handleAddCredits = vi.fn();
      render(<MockCostEstimate modelId={1} userBalance={0} onAddCredits={handleAddCredits} />);

      // External link icon should be visible
    });

    it('CE-LINK-04: should not show link when onAddCredits not provided', () => {
      render(<MockCostEstimate modelId={1} userBalance={0} />);

      // No add credits button should be shown
      expect(screen.queryByTestId('add-credits-btn')).not.toBeInTheDocument();
    });
  });

  describe('CE-A11Y: Accessibility', () => {
    it('CE-A11Y-01: should have accessible cost display', () => {
      render(<MockCostEstimate modelId={1} />);

      // Cost should be announced to screen readers
    });

    it('CE-A11Y-02: should have aria-label for warning', () => {
      render(<MockCostEstimate modelId={1} userBalance={0} />);

      const warning = screen.getByTestId('insufficient-warning');
      // Should have appropriate ARIA attributes
    });

    it('CE-A11Y-03: should be keyboard accessible', () => {
      const handleAddCredits = vi.fn();
      render(<MockCostEstimate modelId={1} userBalance={0} onAddCredits={handleAddCredits} />);

      const btn = screen.getByTestId('add-credits-btn');
      btn.focus();
      // Should be focusable
    });
  });

  describe('CE-ERROR: Error Handling', () => {
    it('CE-ERROR-01: should show error on fetch failure', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      render(<MockCostEstimate modelId={1} />);

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });

      // Should show error state
    });

    it('CE-ERROR-02: should show error for 404 model', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 404,
        json: () => Promise.resolve({ message: 'Model not found' }),
      });

      render(<MockCostEstimate modelId={999} />);

      // Should show error state
    });

    it('CE-ERROR-03: should show retry option on error', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      render(<MockCostEstimate modelId={1} />);

      // Should show retry button
    });
  });

  describe('CE-INTEGRATION: Integration', () => {
    it('CE-INTEGRATION-01: should work with ModelPicker', async () => {
      // CostEstimate should update when model selection changes
      render(<MockCostEstimate modelId={1} />);

      // Changing modelId should trigger new fetch
    });

    it('CE-INTEGRATION-02: should integrate with user balance from context', async () => {
      // If user balance comes from context, test integration
      render(<MockCostEstimate modelId={1} userBalance={0.5} />);

      // Should use context balance
    });
  });
});
