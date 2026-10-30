/**
 * CostEstimate Component Tests
 * TASK-26-22: Create Model Picker Component
 *
 * Tests the ACTUAL CostEstimate component, not a mock.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Import the ACTUAL component
import { CostEstimate } from '@/components/models/CostEstimate';

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
  Brain: () => <span data-testid="brain-icon">Brain</span>,
}));

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
    it('CE-RENDER-01: should render cost estimate display', async () => {
      render(<CostEstimate modelId={1} />);

      await waitFor(() => {
        expect(screen.getByTestId('cost-estimate')).toBeInTheDocument();
      });
    });

    it('CE-RENDER-02: should show token count with proper formatting', async () => {
      render(<CostEstimate modelId={1} analysisType="standard" />);

      await waitFor(() => {
        const tokenCount = screen.getByTestId('token-count');
        expect(tokenCount).toBeInTheDocument();
        // toLocaleString() should add commas: "2,000 tokens"
        expect(tokenCount.textContent).toContain('2');
        expect(tokenCount.textContent).toContain('tokens');
      });
    });

    it('CE-RENDER-03: should apply custom className', async () => {
      render(<CostEstimate modelId={1} className="custom-class" />);

      await waitFor(() => {
        expect(screen.getByTestId('cost-estimate')).toHaveClass('custom-class');
      });
    });
  });

  describe('CE-CALC: Cost Calculations', () => {
    it('CE-CALC-01: should calculate cost for quick analysis', async () => {
      render(<CostEstimate modelId={1} analysisType="quick" />);

      await waitFor(() => {
        const costValue = screen.getByTestId('cost-value');
        expect(costValue).toBeInTheDocument();
        expect(costValue.textContent).toContain('0.01');
      });
    });

    it('CE-CALC-02: should calculate cost for standard analysis', async () => {
      render(<CostEstimate modelId={1} analysisType="standard" />);

      await waitFor(() => {
        const costValue = screen.getByTestId('cost-value');
        expect(costValue).toBeInTheDocument();
        expect(costValue.textContent).toContain('0.05');
      });
    });

    it('CE-CALC-03: should calculate cost for deep analysis', async () => {
      render(<CostEstimate modelId={1} analysisType="deep" />);

      await waitFor(() => {
        const costValue = screen.getByTestId('cost-value');
        expect(costValue).toBeInTheDocument();
        expect(costValue.textContent).toContain('0.12');
      });
    });

    it('CE-CALC-04: should show zero cost for free models', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(freePricingResponse),
      });

      render(<CostEstimate modelId={3} />);

      await waitFor(() => {
        expect(screen.getByTestId('free-label')).toBeInTheDocument();
      });
    });
  });

  describe('CE-BALANCE: Balance Comparison', () => {
    it('CE-BALANCE-01: should show insufficient balance warning', async () => {
      render(<CostEstimate modelId={1} userBalance={0.01} />);

      await waitFor(() => {
        expect(screen.getByTestId('insufficient-warning')).toBeInTheDocument();
      });
    });

    it('CE-BALANCE-02: should not show warning when balance sufficient', async () => {
      render(<CostEstimate modelId={1} userBalance={1.0} />);

      await waitFor(() => {
        expect(screen.queryByTestId('insufficient-warning')).not.toBeInTheDocument();
      });
    });

    it('CE-BALANCE-03: should show warning for zero balance', async () => {
      render(<CostEstimate modelId={1} userBalance={0} />);

      await waitFor(() => {
        expect(screen.getByTestId('insufficient-warning')).toBeInTheDocument();
      });
    });
  });

  describe('CE-WARNING: Insufficient Balance Warning', () => {
    it('CE-WARNING-01: should show alert icon in warning', async () => {
      render(<CostEstimate modelId={1} userBalance={0.01} />);

      await waitFor(() => {
        expect(screen.getByTestId('alert-icon')).toBeInTheDocument();
      });
    });

    it('CE-WARNING-02: should show add credits button when callback provided', async () => {
      const handleAddCredits = vi.fn();
      render(<CostEstimate modelId={1} userBalance={0.01} onAddCredits={handleAddCredits} />);

      await waitFor(() => {
        expect(screen.getByTestId('add-credits-btn')).toBeInTheDocument();
      });
    });

    it('CE-WARNING-03: should call onAddCredits when clicked', async () => {
      const handleAddCredits = vi.fn();
      render(<CostEstimate modelId={1} userBalance={0.01} onAddCredits={handleAddCredits} />);

      await waitFor(() => {
        expect(screen.getByTestId('add-credits-btn')).toBeInTheDocument();
      });

      const btn = screen.getByTestId('add-credits-btn');
      await user.click(btn);

      expect(handleAddCredits).toHaveBeenCalledTimes(1);
    });
  });

  describe('CE-ERROR: Error Handling', () => {
    it('CE-ERROR-01: should show error on fetch failure', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      render(<CostEstimate modelId={1} />);

      await waitFor(() => {
        expect(screen.getByTestId('cost-estimate')).toBeInTheDocument();
      });
    });
  });
});
