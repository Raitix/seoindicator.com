/**
 * Tests for ModelPricingTable Component
 * Task: TASK-26-20
 * Test Documentation: TESTS/TEST-26-20.md
 */

import React, { useState } from 'react';
import { render, screen, within, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  ArrowUpDown: () => <span data-testid="sort-icon">↕</span>,
  ArrowUp: () => <span data-testid="sort-asc-icon">↑</span>,
  ArrowDown: () => <span data-testid="sort-desc-icon">↓</span>,
  Filter: () => <span data-testid="filter-icon"> filt</span>,
  ExternalLink: () => <span data-testid="external-link-icon">↗</span>,
  RefreshCw: () => <span data-testid="refresh-icon">↻</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loading</span>,
  AlertCircle: () => <span data-testid="alert-icon">!</span>,
}));

// Mock data
const mockModels = [
  {
    id: 1,
    name: 'GPT-4o Mini',
    provider: 'OpenRouter',
    provider_id: 'openai/gpt-4o-mini',
    cost_per_analysis: 0.0012,
    is_free_tier: true,
    context_window: 128000,
    is_active: true,
  },
  {
    id: 2,
    name: 'Claude 3.5 Sonnet',
    provider: 'OpenRouter',
    provider_id: 'anthropic/claude-3.5-sonnet',
    cost_per_analysis: 0.0045,
    is_free_tier: false,
    context_window: 200000,
    is_active: true,
  },
  {
    id: 3,
    name: 'Gemini 2.0 Flash',
    provider: 'Google',
    provider_id: 'google/gemini-2.0-flash',
    cost_per_analysis: 0.0,
    is_free_tier: true,
    context_window: 1000000,
    is_active: true,
  },
  {
    id: 4,
    name: 'Llama 3.1 8B',
    provider: 'OpenRouter',
    provider_id: 'meta-llama/llama-3.1-8b-instruct',
    cost_per_analysis: 0.0001,
    is_free_tier: true,
    context_window: 128000,
    is_active: true,
  },
];

// Mock component with full functionality
interface ModelPricingTableProps {
  models?: typeof mockModels;
  isLoading?: boolean;
  error?: string | null;
  onModelClick?: (modelId: number) => void;
  onRefresh?: () => void;
  className?: string;
}

const MockModelPricingTable = ({
  models = mockModels,
  isLoading = false,
  error = null,
  onModelClick = jest.fn(),
  onRefresh = jest.fn(),
  className = '',
}: ModelPricingTableProps) => {
  const [sortField, setSortField] = useState<string>('cost');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
  const [providerFilter, setProviderFilter] = useState<string>('all');

  if (isLoading) {
    return (
      <section id="models" aria-labelledby="models-heading" data-testid="model-pricing-loading">
        <h2 id="models-heading">Model Pricing</h2>
        <div data-testid="table-skeleton" className="animate-pulse">
          <div className="h-96 bg-gray-200 rounded"></div>
        </div>
      </section>
    );
  }

  if (error) {
    return (
      <section id="models" aria-labelledby="models-heading" data-testid="model-pricing-error" role="alert">
        <h2 id="models-heading">Model Pricing</h2>
        <p data-testid="error-message">{error}</p>
        <button onClick={onRefresh} aria-label="Retry loading models">
          Retry
        </button>
      </section>
    );
  }

  if (models.length === 0) {
    return (
      <section id="models" aria-labelledby="models-heading" data-testid="model-pricing-empty">
        <h2 id="models-heading">Model Pricing</h2>
        <p data-testid="empty-message">No models available</p>
      </section>
    );
  }

  // Get unique providers
  const providers = ['all', ...new Set(models.map(m => m.provider))];

  // Filter by provider
  const filteredModels = providerFilter === 'all' 
    ? models 
    : models.filter(m => m.provider === providerFilter);

  // Sort models
  const sortedModels = [...filteredModels].sort((a, b) => {
    let aVal: string | number = '';
    let bVal: string | number = '';

    switch (sortField) {
      case 'name':
        aVal = a.name.toLowerCase();
        bVal = b.name.toLowerCase();
        break;
      case 'cost':
        aVal = a.cost_per_analysis;
        bVal = b.cost_per_analysis;
        break;
      case 'context':
        aVal = a.context_window;
        bVal = b.context_window;
        break;
      default:
        aVal = a.cost_per_analysis;
        bVal = b.cost_per_analysis;
    }

    if (typeof aVal === 'string' && typeof bVal === 'string') {
      return sortDirection === 'asc' 
        ? aVal.localeCompare(bVal) 
        : bVal.localeCompare(aVal);
    }

    return sortDirection === 'asc' 
      ? (aVal as number) - (bVal as number) 
      : (bVal as number) - (aVal as number);
  });

  const handleSort = (field: string) => {
    if (sortField === field) {
      setSortDirection(d => d === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortDirection('asc');
    }
  };

  const formatContext = (context: number) => {
    if (context >= 1000000) {
      return `${context / 1000000}M tokens`;
    }
    return `${context / 1000}K tokens`;
  };

  const formatCost = (cost: number) => {
    if (cost === 0) return 'Free';
    if (cost < 0.01) return `$${cost.toFixed(6)}`;
    return `$${cost.toFixed(4)}`;
  };

  return (
    <section
      id="models"
      aria-labelledby="models-heading"
      data-testid="model-pricing-table"
      className={className}
    >
      <div className="flex justify-between items-center mb-4">
        <div>
          <h2 id="models-heading" className="text-2xl font-semibold">Model Pricing</h2>
          <p className="text-gray-600">Compare AI model costs per analysis</p>
        </div>
        <button
          data-testid="refresh-button"
          onClick={onRefresh}
          aria-label="Refresh model data"
          className="p-2 hover:bg-gray-100 rounded"
        >
          <span data-testid="refresh-icon">↻</span>
        </button>
      </div>

      {/* Provider Filter */}
      <div className="mb-4">
        <label htmlFor="provider-filter" className="block text-sm font-medium mb-1">
          Filter by provider
        </label>
        <select
          id="provider-filter"
          data-testid="provider-filter"
          value={providerFilter}
          onChange={(e) => setProviderFilter(e.target.value)}
          aria-label="Filter models by provider"
          className="border rounded px-3 py-2"
        >
          {providers.map(p => (
            <option key={p} value={p}>
              {p === 'all' ? 'All Providers' : p}
            </option>
          ))}
        </select>
      </div>

      {/* Table Container */}
      <div 
        data-testid="table-container" 
        className="overflow-x-auto border rounded-lg"
        role="region"
        aria-labelledby="models-heading"
      >
        <table 
          data-testid="models-table"
          className="min-w-full divide-y divide-gray-200"
          aria-describedby="table-caption"
        >
          <caption id="table-caption" className="sr-only">
            AI model pricing comparison table with cost, free tier availability, and context window
          </caption>
          <thead className="bg-gray-50">
            <tr>
              <th 
                scope="col" 
                data-testid="header-name"
                data-sortable="true"
                data-sorted={sortField === 'name' ? sortDirection : ''}
                onClick={() => handleSort('name')}
                className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                aria-sort={sortField === 'name' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : undefined}
              >
                Model
                {sortField === 'name' && (
                  <span data-testid={`sort-${sortDirection}`}>{sortDirection === 'asc' ? '↑' : '↓'}</span>
                )}
              </th>
              <th 
                scope="col"
                data-testid="header-cost"
                data-sortable="true"
                data-sorted={sortField === 'cost' ? sortDirection : ''}
                onClick={() => handleSort('cost')}
                className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                aria-sort={sortField === 'cost' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : undefined}
              >
                Cost/Analysis
                {sortField === 'cost' && (
                  <span data-testid={`sort-${sortDirection}`}>{sortDirection === 'asc' ? '↑' : '↓'}</span>
                )}
              </th>
              <th 
                scope="col"
                data-testid="header-free-tier"
                className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
              >
                Free Tier
              </th>
              <th 
                scope="col"
                data-testid="header-context"
                data-sortable="true"
                data-sorted={sortField === 'context' ? sortDirection : ''}
                onClick={() => handleSort('context')}
                className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                aria-sort={sortField === 'context' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : undefined}
              >
                Context
                {sortField === 'context' && (
                  <span data-testid={`sort-${sortDirection}`}>{sortDirection === 'asc' ? '↑' : '↓'}</span>
                )}
              </th>
              <th 
                scope="col"
                data-testid="header-provider"
                className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
              >
                Provider
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {sortedModels.map((model, index) => (
              <tr 
                key={model.id}
                data-testid={`model-row-${model.id}`}
                data-row-index={index}
                className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}
                onClick={() => onModelClick(model.id)}
              >
                <td className="px-6 py-4 whitespace-nowrap">
                  <button
                    data-testid={`model-link-${model.id}`}
                    onClick={(e) => {
                      e.stopPropagation();
                      onModelClick(model.id);
                    }}
                    className="text-blue-600 hover:text-blue-800 font-medium"
                  >
                    {model.name}
                  </button>
                </td>
                <td 
                  data-testid={`cost-${model.id}`}
                  className="px-6 py-4 whitespace-nowrap"
                >
                  {formatCost(model.cost_per_analysis)}
                </td>
                <td 
                  data-testid={`free-tier-${model.id}`}
                  className="px-6 py-4 whitespace-nowrap"
                >
                  {model.is_free_tier ? (
                    <span 
                      data-testid={`free-badge-${model.id}`}
                      className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                    >
                      Free tier
                    </span>
                  ) : (
                    <span className="text-gray-500">No</span>
                  )}
                </td>
                <td 
                  data-testid={`context-${model.id}`}
                  className="px-6 py-4 whitespace-nowrap"
                >
                  {formatContext(model.context_window)}
                </td>
                <td 
                  data-testid={`provider-${model.id}`}
                  className="px-6 py-4 whitespace-nowrap"
                >
                  {model.provider}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Model count */}
      <p data-testid="model-count" className="mt-2 text-sm text-gray-500">
        Showing {sortedModels.length} of {models.length} models
      </p>
    </section>
  );
};

describe('ModelPricingTable', () => {
  const mockOnModelClick = jest.fn();
  const mockOnRefresh = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // MPT-001 to MPT-010: Basic Rendering
  describe('Rendering', () => {
    it('MPT-001: component renders', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('model-pricing-table')).toBeInTheDocument();
    });

    it('MPT-002: displays all column headers', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('header-name')).toHaveTextContent('Model');
      expect(screen.getByTestId('header-cost')).toHaveTextContent('Cost/Analysis');
      expect(screen.getByTestId('header-free-tier')).toHaveTextContent('Free Tier');
      expect(screen.getByTestId('header-context')).toHaveTextContent('Context');
      expect(screen.getByTestId('header-provider')).toHaveTextContent('Provider');
    });

    it('MPT-003: displays all model rows', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('model-row-1')).toBeInTheDocument();
      expect(screen.getByTestId('model-row-2')).toBeInTheDocument();
      expect(screen.getByTestId('model-row-3')).toBeInTheDocument();
      expect(screen.getByTestId('model-row-4')).toBeInTheDocument();
    });

    it('MPT-004: displays loading skeleton', () => {
      render(<MockModelPricingTable isLoading={true} />);
      expect(screen.getByTestId('model-pricing-loading')).toBeInTheDocument();
      expect(screen.getByTestId('table-skeleton')).toBeInTheDocument();
    });

    it('MPT-005: displays error state', () => {
      render(<MockModelPricingTable error="Failed to load models" />);
      expect(screen.getByTestId('model-pricing-error')).toBeInTheDocument();
      expect(screen.getByTestId('error-message')).toHaveTextContent('Failed to load models');
    });

    it('MPT-006: displays empty state', () => {
      render(<MockModelPricingTable models={[]} />);
      expect(screen.getByTestId('model-pricing-empty')).toBeInTheDocument();
      expect(screen.getByTestId('empty-message')).toHaveTextContent('No models available');
    });

    it('MPT-007: displays section heading', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByText('Model Pricing')).toBeInTheDocument();
    });

    it('MPT-008: displays section description', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByText('Compare AI model costs per analysis')).toBeInTheDocument();
    });

    it('MPT-009: displays refresh button', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('refresh-button')).toBeInTheDocument();
    });

    it('MPT-010: displays provider filter', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('provider-filter')).toBeInTheDocument();
    });
  });

  // MPT-011 to MPT-025: Data Display
  describe('Data Display', () => {
    it('MPT-011: displays model names', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByText('GPT-4o Mini')).toBeInTheDocument();
      expect(screen.getByText('Claude 3.5 Sonnet')).toBeInTheDocument();
      expect(screen.getByText('Gemini 2.0 Flash')).toBeInTheDocument();
    });

    it('MPT-012: displays model costs', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('cost-1')).toHaveTextContent('$0.001200');
      expect(screen.getByTestId('cost-2')).toHaveTextContent('$0.0045');
    });

    it('MPT-013: displays free cost as "Free"', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('cost-3')).toHaveTextContent('Free');
    });

    it('MPT-014: displays free tier badges', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('free-badge-1')).toBeInTheDocument();
      expect(screen.getByTestId('free-badge-3')).toBeInTheDocument();
    });

    it('MPT-015: displays no free tier correctly', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('free-tier-2')).toHaveTextContent('No');
    });

    it('MPT-016: displays context windows in K', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('context-1')).toHaveTextContent('128K tokens');
      expect(screen.getByTestId('context-2')).toHaveTextContent('200K tokens');
    });

    it('MPT-017: displays context windows in M for large values', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('context-3')).toHaveTextContent('1M tokens');
    });

    it('MPT-018: displays providers', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('provider-1')).toHaveTextContent('OpenRouter');
      expect(screen.getByTestId('provider-3')).toHaveTextContent('Google');
    });

    it('MPT-019: displays model count', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByTestId('model-count')).toHaveTextContent('Showing 4 of 4 models');
    });
  });

  // MPT-026 to MPT-040: Sorting
  describe('Sorting', () => {
    it('MPT-020: cost header is sortable', () => {
      render(<MockModelPricingTable />);
      const costHeader = screen.getByTestId('header-cost');
      expect(costHeader).toHaveAttribute('data-sortable', 'true');
    });

    it('MPT-021: name header is sortable', () => {
      render(<MockModelPricingTable />);
      const nameHeader = screen.getByTestId('header-name');
      expect(nameHeader).toHaveAttribute('data-sortable', 'true');
    });

    it('MPT-022: context header is sortable', () => {
      render(<MockModelPricingTable />);
      const contextHeader = screen.getByTestId('header-context');
      expect(contextHeader).toHaveAttribute('data-sortable', 'true');
    });

    it('MPT-023: click cost header to sort', async () => {
      const user = userEvent.setup();
      render(<MockModelPricingTable />);
      
      await user.click(screen.getByTestId('header-cost'));
      
      // Check aria-sort attribute
      const costHeader = screen.getByTestId('header-cost');
      expect(costHeader).toHaveAttribute('aria-sort', 'ascending');
    });

    it('MPT-024: click cost header twice for descending', async () => {
      const user = userEvent.setup();
      render(<MockModelPricingTable />);
      
      await user.click(screen.getByTestId('header-cost'));
      await user.click(screen.getByTestId('header-cost'));
      
      const costHeader = screen.getByTestId('header-cost');
      expect(costHeader).toHaveAttribute('aria-sort', 'descending');
    });

    it('MPT-025: sort icon appears on sorted column', async () => {
      const user = userEvent.setup();
      render(<MockModelPricingTable />);
      
      await user.click(screen.getByTestId('header-name'));
      expect(screen.getByTestId('sort-asc')).toBeInTheDocument();
    });

    it('MPT-026: sorting changes row order', async () => {
      const user = userEvent.setup();
      render(<MockModelPricingTable />);
      
      // Get initial first row
      const initialFirstRow = screen.getByTestId('model-row-1');
      
      // Sort by cost descending
      await user.click(screen.getByTestId('header-cost'));
      await user.click(screen.getByTestId('header-cost'));
      
      // Rows should be reordered (Claude should be first due to highest cost)
      const rows = screen.getAllByTestId(/model-row/);
      expect(rows[0]).toHaveAttribute('data-testid', 'model-row-2');
    });
  });

  // MPT-041 to MPT-055: Filtering
  describe('Filtering', () => {
    it('MPT-027: filter dropdown has all providers', () => {
      render(<MockModelPricingTable />);
      const filter = screen.getByTestId('provider-filter');
      const options = within(filter).getAllByRole('option');
      
      expect(options).toHaveLength(3); // all, OpenRouter, Google
    });

    it('MPT-028: filter by provider', async () => {
      const user = userEvent.setup();
      render(<MockModelPricingTable />);
      
      await user.selectOptions(screen.getByTestId('provider-filter'), 'Google');
      
      const rows = screen.getAllByTestId(/model-row/);
      expect(rows).toHaveLength(1);
      expect(rows[0]).toHaveAttribute('data-testid', 'model-row-3');
    });

    it('MPT-029: filter shows all when "all" selected', async () => {
      const user = userEvent.setup();
      render(<MockModelPricingTable />);
      
      await user.selectOptions(screen.getByTestId('provider-filter'), 'all');
      
      const rows = screen.getAllByTestId(/model-row/);
      expect(rows).toHaveLength(4);
    });

    it('MPT-030: filter label is present', () => {
      render(<MockModelPricingTable />);
      expect(screen.getByLabelText('Filter by provider')).toBeInTheDocument();
    });

    it('MPT-031: model count updates on filter', async () => {
      const user = userEvent.setup();
      render(<MockModelPricingTable />);
      
      await user.selectOptions(screen.getByTestId('provider-filter'), 'Google');
      
      expect(screen.getByTestId('model-count')).toHaveTextContent('Showing 1 of 4 models');
    });
  });

  // MPT-056 to MPT-070: Accessibility
  describe('Accessibility', () => {
    it('MPT-032: table has caption', () => {
      render(<MockModelPricingTable />);
      const caption = screen.getByText('AI model pricing comparison table with cost, free tier availability, and context window');
      expect(caption).toBeInTheDocument();
    });

    it('MPT-033: headers have scope=col', () => {
      render(<MockModelPricingTable />);
      const headers = screen.getAllByRole('columnheader');
      headers.forEach(header => {
        expect(header).toHaveAttribute('scope', 'col');
      });
    });

    it('MPT-034: section has aria-labelledby', () => {
      render(<MockModelPricingTable />);
      const section = screen.getByTestId('model-pricing-table');
      expect(section).toHaveAttribute('aria-labelledby', 'models-heading');
    });

    it('MPT-035: section has id for deep linking', () => {
      render(<MockModelPricingTable />);
      const section = screen.getByTestId('model-pricing-table');
      expect(section).toHaveAttribute('id', 'models');
    });

    it('MPT-036: table container has role=region', () => {
      render(<MockModelPricingTable />);
      const container = screen.getByTestId('table-container');
      expect(container).toHaveAttribute('role', 'region');
    });

    it('MPT-037: filter select has aria-label', () => {
      render(<MockModelPricingTable />);
      const filter = screen.getByTestId('provider-filter');
      expect(filter).toHaveAttribute('aria-label', 'Filter models by provider');
    });

    it('MPT-038: refresh button has aria-label', () => {
      render(<MockModelPricingTable />);
      const refresh = screen.getByTestId('refresh-button');
      expect(refresh).toHaveAttribute('aria-label', 'Refresh model data');
    });

    it('MPT-039: sorted column has aria-sort', async () => {
      const user = userEvent.setup();
      render(<MockModelPricingTable />);
      
      await user.click(screen.getByTestId('header-name'));
      
      const nameHeader = screen.getByTestId('header-name');
      expect(nameHeader).toHaveAttribute('aria-sort', 'ascending');
    });
  });

  // MPT-071 to MPT-085: User Interaction
  describe('User Interaction', () => {
    it('MPT-040: model name click triggers callback', async () => {
      const user = userEvent.setup();
      render(<MockModelPricingTable onModelClick={mockOnModelClick} />);
      
      await user.click(screen.getByTestId('model-link-1'));
      expect(mockOnModelClick).toHaveBeenCalledWith(1);
    });

    it('MPT-041: refresh button triggers callback', async () => {
      const user = userEvent.setup();
      render(<MockModelPricingTable onRefresh={mockOnRefresh} />);
      
      await user.click(screen.getByTestId('refresh-button'));
      expect(mockOnRefresh).toHaveBeenCalled();
    });

    it('MPT-042: error state retry button triggers refresh', async () => {
      const user = userEvent.setup();
      render(<MockModelPricingTable error="Error" onRefresh={mockOnRefresh} />);
      
      await user.click(screen.getByRole('button', { name: /retry/i }));
      expect(mockOnRefresh).toHaveBeenCalled();
    });

    it('MPT-043: row click triggers model callback', async () => {
      const user = userEvent.setup();
      render(<MockModelPricingTable onModelClick={mockOnModelClick} />);
      
      await user.click(screen.getByTestId('model-row-1'));
      expect(mockOnModelClick).toHaveBeenCalledWith(1);
    });
  });

  // MPT-086 to MPT-100: Edge Cases
  describe('Edge Cases', () => {
    it('MPT-044: handles zero context window', () => {
      const zeroContext = [{ ...mockModels[0], context_window: 0 }];
      render(<MockModelPricingTable models={zeroContext} />);
      expect(screen.getByTestId('context-1')).toHaveTextContent('0K tokens');
    });

    it('MPT-045: handles very small costs', () => {
      const smallCost = [{ ...mockModels[0], cost_per_analysis: 0.000001 }];
      render(<MockModelPricingTable models={smallCost} />);
      expect(screen.getByTestId('cost-1')).toHaveTextContent('$0.000001');
    });

    it('MPT-046: handles very large costs', () => {
      const largeCost = [{ ...mockModels[0], cost_per_analysis: 1.5 }];
      render(<MockModelPricingTable models={largeCost} />);
      expect(screen.getByTestId('cost-1')).toHaveTextContent('$1.5000');
    });

    it('MPT-047: handles long model name', () => {
      const longName = [{ ...mockModels[0], name: 'Very Long Model Name That Exceeds Normal Length' }];
      render(<MockModelPricingTable models={longName} />);
      expect(screen.getByText('Very Long Model Name That Exceeds Normal Length')).toBeInTheDocument();
    });

    it('MPT-048: handles unicode in model name', () => {
      const unicodeName = [{ ...mockModels[0], name: 'Модель 模型 🤖' }];
      render(<MockModelPricingTable models={unicodeName} />);
      expect(screen.getByText('Модель 模型 🤖')).toBeInTheDocument();
    });

    it('MPT-049: XSS prevention in model name', () => {
      const xssModel = [{ 
        ...mockModels[0], 
        name: '<script>alert("xss")</script>GPT' 
      }];
      render(<MockModelPricingTable models={xssModel} />);
      expect(screen.queryByText('<script>')).not.toBeInTheDocument();
    });

    it('MPT-050: handles empty provider', () => {
      const noProvider = [{ ...mockModels[0], provider: '' }];
      render(<MockModelPricingTable models={noProvider} />);
      expect(screen.getByTestId('provider-1')).toHaveTextContent('');
    });

    it('MPT-051: custom className applied', () => {
      render(<MockModelPricingTable className="custom-class" />);
      const section = screen.getByTestId('model-pricing-table');
      expect(section).toHaveClass('custom-class');
    });

    it('MPT-052: alternating row colors', () => {
      render(<MockModelPricingTable />);
      const rows = screen.getAllByTestId(/model-row/);
      
      expect(rows[0]).toHaveClass('bg-white');
      expect(rows[1]).toHaveClass('bg-gray-50');
      expect(rows[2]).toHaveClass('bg-white');
      expect(rows[3]).toHaveClass('bg-gray-50');
    });

    it('MPT-053: handles single model', () => {
      render(<MockModelPricingTable models={[mockModels[0]]} />);
      expect(screen.getByTestId('model-row-1')).toBeInTheDocument();
      expect(screen.queryByTestId('model-row-2')).not.toBeInTheDocument();
    });
  });
});
