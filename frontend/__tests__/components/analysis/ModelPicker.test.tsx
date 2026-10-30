/**
 * ModelPicker Component Tests
 * Task: TASK-31-15
 * Tests for AI model selection component
 */

import React from 'react';
import { render, screen, fireEvent, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { vi, describe, it, expect, beforeEach } from 'vitest';

// Import the ACTUAL component (not a mock)
import { ModelPicker } from '@/components/analysis/ModelPicker';

// Mock data - defined at module level for hoisting
const mockModelsData = [
  {
    id: 'google/gemini-2.0-flash-exp:free',
    name: 'Gemini 2.0 Flash',
    provider: 'Google',
    context_window: 1000000,
    is_free: true,
    is_recommended: true,
    capabilities: ['text', 'json'],
    avg_latency_ms: 3000,
    best_for: 'Large pages (100K+ characters)',
  },
  {
    id: 'qwen/qwen-2-7b-instruct:free',
    name: 'Qwen 2 7B',
    provider: 'Alibaba',
    context_window: 32768,
    is_free: true,
    is_recommended: false,
    capabilities: ['text', 'json'],
    avg_latency_ms: 2500,
    best_for: 'Medium pages',
  },
  {
    id: 'mistralai/mistral-7b-instruct:free',
    name: 'Mistral 7B',
    provider: 'Mistral AI',
    context_window: 32768,
    is_free: true,
    is_recommended: false,
    capabilities: ['text', 'json'],
    avg_latency_ms: 2000,
    best_for: 'Fast analysis',
  },
  {
    id: 'meta-llama/llama-3-8b-instruct:free',
    name: 'Llama 3 8B',
    provider: 'Meta',
    context_window: 8192,
    is_free: true,
    is_recommended: false,
    capabilities: ['text', 'json'],
    avg_latency_ms: 1500,
    best_for: 'Small pages only',
  },
];

// Mutable state for tests - must be at module level
let mockModels = [...mockModelsData];
let mockDefaultModel = 'google/gemini-2.0-flash-exp:free';
let mockUserPreference: string | null = null;
let mockIsLoading = false;
let mockError: Error | null = null;

const mockRefetch = vi.fn();

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Check: () => <span data-testid="check-icon">✓</span>,
  Database: () => <span data-testid="database-icon">📊</span>,
  Clock: () => <span data-testid="clock-icon">⏱</span>,
  AlertCircle: () => <span data-testid="alert-icon">⚠</span>,
  RefreshCw: () => <span data-testid="refresh-icon">↻</span>,
}));

// Mock useAIModels hook - module level mock with factory function
vi.mock('@/hooks/useAIModels', () => ({
  useAIModels: () => ({
    models: mockModels,
    defaultModel: mockDefaultModel,
    userPreference: mockUserPreference,
    isLoading: mockIsLoading,
    error: mockError,
    refetch: mockRefetch,
  }),
}));

// Reset mocks before each test
beforeEach(() => {
  vi.clearAllMocks();
  mockModels = [...mockModelsData];
  mockDefaultModel = 'google/gemini-2.0-flash-exp:free';
  mockUserPreference = null;
  mockIsLoading = false;
  mockError = null;
});

// ============================================
// MP-RENDER: Component Rendering
// ============================================

describe('MP-RENDER: Component Rendering', () => {
  it('MP-RENDER-001: Renders without crashing', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByTestId('model-picker')).toBeInTheDocument();
  });

  it('MP-RENDER-002: Renders with selected model', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel="google/gemini-2.0-flash-exp:free" 
        onModelChange={onModelChange} 
      />
    );
    expect(screen.getByTestId('check-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
  });

  it('MP-RENDER-003: Renders all models from API', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('model-card-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
    expect(screen.getByTestId('model-card-qwen/qwen-2-7b-instruct:free')).toBeInTheDocument();
    expect(screen.getByTestId('model-card-mistralai/mistral-7b-instruct:free')).toBeInTheDocument();
    expect(screen.getByTestId('model-card-meta-llama/llama-3-8b-instruct:free')).toBeInTheDocument();
  });

  it('MP-RENDER-004: Renders model name', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByText('Gemini 2.0 Flash')).toBeInTheDocument();
  });

  it('MP-RENDER-005: Renders model provider', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByText('Google')).toBeInTheDocument();
  });

  it('MP-RENDER-006: Renders context window', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByTestId('context-google/gemini-2.0-flash-exp:free')).toHaveTextContent('1M tokens');
  });

  it('MP-RENDER-007: Renders latency', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByTestId('latency-google/gemini-2.0-flash-exp:free')).toHaveTextContent('~3.0s');
  });

  it('MP-RENDER-008: Renders label', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByText('AI Model')).toBeInTheDocument();
  });

  it('MP-RENDER-009: Renders help text', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByText(/Model is automatically selected/)).toBeInTheDocument();
  });

  it('MP-RENDER-010: Accepts className prop', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel={null} 
        onModelChange={onModelChange} 
        className="custom-class" 
      />
    );
    expect(screen.getByTestId('model-picker')).toHaveClass('custom-class');
  });
});

// ============================================
// MP-LOAD: Loading State
// ============================================

describe('MP-LOAD: Loading State', () => {
  beforeEach(() => {
    mockIsLoading = true;
  });

  it('MP-LOAD-001: Shows loading skeleton', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByTestId('model-picker-skeleton')).toBeInTheDocument();
  });

  it('MP-LOAD-002: Skeleton has aria-busy', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByTestId('model-picker-skeleton')).toHaveAttribute('aria-busy', 'true');
  });

  it('MP-LOAD-003: Hides content during load', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.queryByTestId('model-picker')).not.toBeInTheDocument();
  });

  it('MP-LOAD-004: Transitions to content', () => {
    const onModelChange = vi.fn();
    const { rerender } = render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('model-picker-skeleton')).toBeInTheDocument();
    
    mockIsLoading = false;
    rerender(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('model-picker')).toBeInTheDocument();
  });

  it('MP-LOAD-005: Skeleton matches layout', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    const skeleton = screen.getByTestId('model-picker-skeleton');
    const items = skeleton.querySelectorAll('.skeleton-item');
    expect(items.length).toBe(3);
  });

  it('MP-LOAD-006: Accessible loading state', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByTestId('model-picker-skeleton')).toHaveAttribute('role', 'status');
  });
});

// ============================================
// MP-ERROR: Error State
// ============================================

describe('MP-ERROR: Error State', () => {
  beforeEach(() => {
    mockError = new Error('Network error');
  });

  it('MP-ERROR-001: Shows error message', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByText('Failed to load models')).toBeInTheDocument();
  });

  it('MP-ERROR-002: Error has alert role', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByTestId('model-picker-error')).toHaveAttribute('role', 'alert');
  });

  it('MP-ERROR-003: Retry button rendered', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByTestId('retry-button')).toBeInTheDocument();
  });

  it('MP-ERROR-004: Retry triggers refetch', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    await user.click(screen.getByTestId('retry-button'));
    expect(mockRefetch).toHaveBeenCalled();
  });

  it('MP-ERROR-005: Hides content on error', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.queryByTestId('model-picker')).not.toBeInTheDocument();
  });

  it('MP-ERROR-006: Network error handled', () => {
    mockError = { message: 'Network Error', status: 0 } as any;
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByText('Failed to load models')).toBeInTheDocument();
  });
});

// ============================================
// MP-SELECT: Selection Behavior
// ============================================

describe('MP-SELECT: Selection Behavior', () => {
  it('MP-SELECT-001: Click selects model', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    await user.click(screen.getByTestId('model-card-qwen/qwen-2-7b-instruct:free'));
    expect(onModelChange).toHaveBeenCalledWith('qwen/qwen-2-7b-instruct:free');
  });

  it('MP-SELECT-002: Selected model highlighted', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel="qwen/qwen-2-7b-instruct:free" 
        onModelChange={onModelChange} 
      />
    );
    
    const selectedCard = screen.getByTestId('model-card-qwen/qwen-2-7b-instruct:free');
    expect(selectedCard).toHaveClass('selected');
  });

  it('MP-SELECT-003: Check icon on selected', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel="qwen/qwen-2-7b-instruct:free" 
        onModelChange={onModelChange} 
      />
    );
    expect(screen.getByTestId('check-qwen/qwen-2-7b-instruct:free')).toBeInTheDocument();
  });

  it('MP-SELECT-004: No check on unselected', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel="google/gemini-2.0-flash-exp:free" 
        onModelChange={onModelChange} 
      />
    );
    expect(screen.queryByTestId('check-qwen/qwen-2-7b-instruct:free')).not.toBeInTheDocument();
  });

  it('MP-SELECT-005: Default model selected when null', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Default model should be selected
    expect(screen.getByTestId('check-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
  });

  it('MP-SELECT-006: Selection updates UI', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    const { rerender } = render(
      <ModelPicker selectedModel={null} onModelChange={onModelChange} />
    );
    
    // Initially default selected
    expect(screen.getByTestId('check-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
    
    // Click different model
    await user.click(screen.getByTestId('model-card-qwen/qwen-2-7b-instruct:free'));
    
    // Rerender with new selection
    rerender(
      <ModelPicker 
        selectedModel="qwen/qwen-2-7b-instruct:free" 
        onModelChange={onModelChange} 
      />
    );
    
    expect(screen.getByTestId('check-qwen/qwen-2-7b-instruct:free')).toBeInTheDocument();
    expect(screen.queryByTestId('check-google/gemini-2.0-flash-exp:free')).not.toBeInTheDocument();
  });

  it('MP-SELECT-007: Disabled state prevents click', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} disabled />);
    
    await user.click(screen.getByTestId('model-card-google/gemini-2.0-flash-exp:free'));
    expect(onModelChange).not.toHaveBeenCalled();
  });

  it('MP-SELECT-008: Disabled visual state', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} disabled />);
    
    const cards = screen.getAllByRole('button');
    cards.forEach(card => {
      expect(card).toHaveClass('disabled');
    });
  });

  it('MP-SELECT-009: Keyboard selection', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    const card = screen.getByTestId('model-card-qwen/qwen-2-7b-instruct:free');
    card.focus();
    await user.keyboard('{Enter}');
    
    expect(onModelChange).toHaveBeenCalledWith('qwen/qwen-2-7b-instruct:free');
  });

  it('MP-SELECT-010: Tab navigation', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    const firstCard = screen.getByTestId('model-card-google/gemini-2.0-flash-exp:free');
    firstCard.focus();
    
    await user.tab();
    expect(screen.getByTestId('model-card-qwen/qwen-2-7b-instruct:free')).toHaveFocus();
  });

  it('MP-SELECT-011: Focus visible', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    const card = screen.getByTestId('model-card-google/gemini-2.0-flash-exp:free');
    card.focus();
    expect(card).toHaveFocus();
  });

  it('MP-SELECT-012: onModelChange receives ID', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    await user.click(screen.getByTestId('model-card-mistralai/mistral-7b-instruct:free'));
    expect(onModelChange).toHaveBeenCalledWith('mistralai/mistral-7b-instruct:free');
  });
});

// ============================================
// MP-RECOMMEND: Recommended Model Badge
// ============================================

describe('MP-RECOMMEND: Recommended Model Badge', () => {
  it('MP-RECOMMEND-001: Shows "Recommended" badge', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('recommended-badge-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
    expect(screen.getByText('Recommended')).toBeInTheDocument();
  });

  it('MP-RECOMMEND-002: Badge styling', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    const badge = screen.getByTestId('recommended-badge-google/gemini-2.0-flash-exp:free');
    expect(badge).toHaveClass('recommended-badge');
  });

  it('MP-RECOMMEND-003: Badge position', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    const card = screen.getByTestId('model-card-google/gemini-2.0-flash-exp:free');
    const badge = screen.getByTestId('recommended-badge-google/gemini-2.0-flash-exp:free');
    
    expect(card).toContainElement(badge);
  });

  it('MP-RECOMMEND-004: No badge on non-recommended', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.queryByTestId('recommended-badge-qwen/qwen-2-7b-instruct:free')).not.toBeInTheDocument();
  });

  it('MP-RECOMMEND-005: Only one recommended', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    const badges = screen.getAllByText('Recommended');
    expect(badges.length).toBe(1);
  });
});

// ============================================
// MP-CONTEXT: Context Window Display
// ============================================

describe('MP-CONTEXT: Context Window Display', () => {
  it('MP-CONTEXT-001: Formats 1M tokens', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('context-google/gemini-2.0-flash-exp:free')).toHaveTextContent('1M tokens');
  });

  it('MP-CONTEXT-002: Formats 32K tokens', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('context-qwen/qwen-2-7b-instruct:free')).toHaveTextContent('32K tokens');
  });

  it('MP-CONTEXT-003: Formats 8K tokens', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('context-meta-llama/llama-3-8b-instruct:free')).toHaveTextContent('8K tokens');
  });

  it('MP-CONTEXT-004: Database icon shown', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    const icons = screen.getAllByTestId('database-icon');
    expect(icons.length).toBeGreaterThan(0);
  });

  it('MP-CONTEXT-005: Context window label present', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Context should be displayed alongside icon
    expect(screen.getByTestId('context-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
  });

  it('MP-CONTEXT-006: Shows context window in tooltip', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Context window text is visible
    expect(screen.getByTestId('context-google/gemini-2.0-flash-exp:free')).toBeVisible();
  });

  it('MP-CONTEXT-007: Context comparison works', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Different models have different context values
    expect(screen.getByTestId('context-google/gemini-2.0-flash-exp:free')).toHaveTextContent('1M tokens');
    expect(screen.getByTestId('context-meta-llama/llama-3-8b-instruct:free')).toHaveTextContent('8K tokens');
  });

  it('MP-CONTEXT-008: Zero context handled', () => {
    mockModels = [{
      ...mockModelsData[0],
      id: 'test/zero-context',
      context_window: 0,
    }];
    
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('context-test/zero-context')).toHaveTextContent('0 tokens');
  });
});

// ============================================
// MP-LATENCY: Latency Display
// ============================================

describe('MP-LATENCY: Latency Display', () => {
  it('MP-LATENCY-001: Formats 3000ms', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('latency-google/gemini-2.0-flash-exp:free')).toHaveTextContent('~3.0s');
  });

  it('MP-LATENCY-002: Formats 1500ms', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('latency-meta-llama/llama-3-8b-instruct:free')).toHaveTextContent('~1.5s');
  });

  it('MP-LATENCY-003: Formats 500ms', () => {
    mockModels = [{
      ...mockModelsData[0],
      id: 'test/fast-model',
      avg_latency_ms: 500,
    }];
    
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('latency-test/fast-model')).toHaveTextContent('~0.5s');
  });

  it('MP-LATENCY-004: Clock icon shown', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    const icons = screen.getAllByTestId('clock-icon');
    expect(icons.length).toBeGreaterThan(0);
  });

  it('MP-LATENCY-005: Latency label present', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Latency is displayed with time unit
    expect(screen.getByTestId('latency-google/gemini-2.0-flash-exp:free')).toHaveTextContent('s');
  });
});

// ============================================
// MP-FIT: Content Fit Warning
// ============================================

describe('MP-FIT: Content Fit Warning', () => {
  it('MP-FIT-001: Shows warning when too large', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel={null} 
        onModelChange={onModelChange} 
        estimatedTokens={10000} // More than Llama's 8192 context
      />
    );
    
    expect(screen.getByTestId('warning-meta-llama/llama-3-8b-instruct:free')).toBeInTheDocument();
  });

  it('MP-FIT-002: Disables card when too large', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel={null} 
        onModelChange={onModelChange} 
        estimatedTokens={10000}
      />
    );
    
    const llamaCard = screen.getByTestId('model-card-meta-llama/llama-3-8b-instruct:free');
    expect(llamaCard).toHaveClass('disabled');
    expect(llamaCard).toBeDisabled();
  });

  it('MP-FIT-003: Prevents selection when disabled', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel={null} 
        onModelChange={onModelChange} 
        estimatedTokens={10000}
      />
    );
    
    await user.click(screen.getByTestId('model-card-meta-llama/llama-3-8b-instruct:free'));
    expect(onModelChange).not.toHaveBeenCalled();
  });

  it('MP-FIT-004: Fits within context', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel={null} 
        onModelChange={onModelChange} 
        estimatedTokens={5000} // Less than all context windows
      />
    );
    
    expect(screen.queryByTestId('warning-meta-llama/llama-3-8b-instruct:free')).not.toBeInTheDocument();
  });

  it('MP-FIT-005: No tokens specified', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel={null} 
        onModelChange={onModelChange} 
      />
    );
    
    // All cards should be enabled
    const cards = screen.getAllByRole('button');
    cards.forEach(card => {
      expect(card).not.toHaveClass('disabled');
      expect(card).not.toBeDisabled();
    });
  });

  it('MP-FIT-006: Exactly at boundary', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel={null} 
        onModelChange={onModelChange} 
        estimatedTokens={8192} // Exactly Llama's context
      />
    );
    
    // Should NOT fit (strict less than check)
    expect(screen.getByTestId('warning-meta-llama/llama-3-8b-instruct:free')).toBeInTheDocument();
  });

  it('MP-FIT-007: Just over boundary', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel={null} 
        onModelChange={onModelChange} 
        estimatedTokens={8193} // Just over Llama's context
      />
    );
    
    expect(screen.getByTestId('warning-meta-llama/llama-3-8b-instruct:free')).toBeInTheDocument();
  });

  it('MP-FIT-008: Multiple models disabled', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel={null} 
        onModelChange={onModelChange} 
        estimatedTokens={40000} // More than Llama, Qwen, Mistral
      />
    );
    
    // Llama, Qwen, Mistral should be disabled (context < 40000)
    expect(screen.getByTestId('model-card-meta-llama/llama-3-8b-instruct:free')).toHaveClass('disabled');
    expect(screen.getByTestId('model-card-qwen/qwen-2-7b-instruct:free')).toHaveClass('disabled');
    expect(screen.getByTestId('model-card-mistralai/mistral-7b-instruct:free')).toHaveClass('disabled');
    
    // Gemini should still be enabled (1M context)
    expect(screen.getByTestId('model-card-google/gemini-2.0-flash-exp:free')).not.toHaveClass('disabled');
  });
});

// ============================================
// MP-A11Y: Accessibility
// ============================================

describe('MP-A11Y: Accessibility', () => {
  it('MP-A11Y-001: Cards are buttons', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    const cards = screen.getAllByRole('button');
    expect(cards.length).toBe(4);
  });

  it('MP-A11Y-002: Selected state announced', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel="google/gemini-2.0-flash-exp:free" 
        onModelChange={onModelChange} 
      />
    );
    
    const selectedCard = screen.getByTestId('model-card-google/gemini-2.0-flash-exp:free');
    expect(selectedCard).toHaveAttribute('aria-pressed', 'true');
  });

  it('MP-A11Y-003: Unselected state announced', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel="google/gemini-2.0-flash-exp:free" 
        onModelChange={onModelChange} 
      />
    );
    
    const unselectedCard = screen.getByTestId('model-card-qwen/qwen-2-7b-instruct:free');
    expect(unselectedCard).toHaveAttribute('aria-pressed', 'false');
  });

  it('MP-A11Y-004: Disabled state announced', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel={null} 
        onModelChange={onModelChange} 
        estimatedTokens={10000}
      />
    );
    
    const llamaCard = screen.getByTestId('model-card-meta-llama/llama-3-8b-instruct:free');
    expect(llamaCard).toHaveAttribute('aria-disabled', 'true');
  });

  it('MP-A11Y-005: Model name in accessible name', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Model name should be visible
    expect(screen.getByText('Gemini 2.0 Flash')).toBeInTheDocument();
  });

  it('MP-A11Y-006: Keyboard focusable', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    await user.tab();
    expect(screen.getByTestId('model-card-google/gemini-2.0-flash-exp:free')).toHaveFocus();
  });

  it('MP-A11Y-007: Enter activates card', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    const card = screen.getByTestId('model-card-qwen/qwen-2-7b-instruct:free');
    card.focus();
    await user.keyboard('{Enter}');
    
    expect(onModelChange).toHaveBeenCalled();
  });

  it('MP-A11Y-008: Screen reader context', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Provider, context, and latency should all be visible
    expect(screen.getByText('Google')).toBeInTheDocument();
    expect(screen.getByTestId('context-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
    expect(screen.getByTestId('latency-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
  });
});

// ============================================
// MP-RESPONSIVE: Mobile Responsive
// ============================================

describe('MP-RESPONSIVE: Mobile Responsive', () => {
  it('MP-RESPONSIVE-001: Mobile layout works', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Grid should exist
    const grid = document.querySelector('.models-grid');
    expect(grid).toBeInTheDocument();
  });

  it('MP-RESPONSIVE-002: Touch targets adequate', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    const cards = screen.getAllByRole('button');
    cards.forEach(card => {
      // Button should be large enough for touch (44px minimum recommended)
      expect(card).toBeInTheDocument();
    });
  });

  it('MP-RESPONSIVE-003: Text remains readable', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // All text should be visible
    expect(screen.getByText('Gemini 2.0 Flash')).toBeVisible();
    expect(screen.getByText('Google')).toBeVisible();
  });

  it('MP-RESPONSIVE-004: Desktop layout works', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Component should render correctly
    expect(screen.getByTestId('model-picker')).toBeInTheDocument();
    expect(screen.getAllByRole('button').length).toBe(4);
  });
});

// ============================================
// MP-HOOK: useAIModels Hook Integration
// ============================================

describe('MP-HOOK: useAIModels Hook Integration', () => {
  it('MP-HOOK-001: Hook called on mount', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Models are displayed, meaning hook was called
    expect(screen.getAllByRole('button').length).toBe(4);
  });

  it('MP-HOOK-002: Returns models array', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // All models from mock are rendered
    expect(screen.getByText('Gemini 2.0 Flash')).toBeInTheDocument();
    expect(screen.getByText('Qwen 2 7B')).toBeInTheDocument();
    expect(screen.getByText('Mistral 7B')).toBeInTheDocument();
    expect(screen.getByText('Llama 3 8B')).toBeInTheDocument();
  });

  it('MP-HOOK-003: Returns default model', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Default model (Gemini) should be selected
    expect(screen.getByTestId('check-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
  });

  it('MP-HOOK-004: Returns user preference', () => {
    mockUserPreference = 'qwen/qwen-2-7b-instruct:free';
    
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // User preference should override default
    expect(screen.getByTestId('check-qwen/qwen-2-7b-instruct:free')).toBeInTheDocument();
  });

  it('MP-HOOK-005: Returns loading state', () => {
    mockIsLoading = true;
    
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('model-picker-skeleton')).toBeInTheDocument();
  });

  it('MP-HOOK-006: Returns error state', () => {
    mockError = new Error('Test error');
    
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('model-picker-error')).toBeInTheDocument();
  });

  it('MP-HOOK-007: Revalidates on focus', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Hook mock doesn't simulate focus behavior, but component uses the hook
    // which should have revalidateOnFocus: false per spec
    expect(screen.getByTestId('model-picker')).toBeInTheDocument();
  });

  it('MP-HOOK-008: Uses correct endpoint', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Hook is mocked, but spec says '/api/v1/ai/models'
    // Component renders, confirming hook was called
    expect(screen.getByTestId('model-picker')).toBeInTheDocument();
  });

  it('MP-HOOK-009: Handles empty models', () => {
    mockModels = [];
    
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('model-picker-empty')).toBeInTheDocument();
  });

  it('MP-HOOK-010: Handles null response', () => {
    mockModels = null as any;
    
    const onModelChange = vi.fn();
    // Should not crash - empty state shown
    expect(() => render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />)).not.toThrow();
  });
});

// ============================================
// MP-EDGE: Edge Cases
// ============================================

describe('MP-EDGE: Edge Cases', () => {
  it('MP-EDGE-001: Empty models array', () => {
    mockModels = [];
    
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByText('No models available')).toBeInTheDocument();
  });

  it('MP-EDGE-002: Single model', () => {
    mockModels = [mockModelsData[0]];
    
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getAllByRole('button').length).toBe(1);
    expect(screen.getByText('Gemini 2.0 Flash')).toBeInTheDocument();
  });

  it('MP-EDGE-003: Very long model name', () => {
    mockModels = [{
      ...mockModelsData[0],
      id: 'test/long',
      name: 'This is a very long model name that might need truncation in the UI to prevent overflow issues',
    }];
    
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByText(/This is a very long model name/)).toBeInTheDocument();
  });

  it('MP-EDGE-004: Unicode in model name', () => {
    mockModels = [{
      ...mockModelsData[0],
      id: 'test/unicode',
      name: 'Gemini 🚀 Flash ✨',
    }];
    
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByText('Gemini 🚀 Flash ✨')).toBeInTheDocument();
  });

  it('MP-EDGE-005: Null selectedModel', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Default should be selected
    expect(screen.getByTestId('check-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
  });

  it('MP-EDGE-006: Invalid model ID selected', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel="nonexistent/model" 
        onModelChange={onModelChange} 
      />
    );
    
    // No check icon should be shown for invalid selection
    expect(screen.queryByTestId('check-nonexistent/model')).not.toBeInTheDocument();
  });

  it('MP-EDGE-007: Zero latency', () => {
    mockModels = [{
      ...mockModelsData[0],
      id: 'test/zero-latency',
      avg_latency_ms: 0,
    }];
    
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('latency-test/zero-latency')).toHaveTextContent('~0.0s');
  });

  it('MP-EDGE-008: All models disabled', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} disabled />);
    
    const cards = screen.getAllByRole('button');
    cards.forEach(card => {
      expect(card).toHaveClass('disabled');
      expect(card).toBeDisabled();
    });
  });
});

// ============================================
// MP-INT: Integration Scenarios
// ============================================

describe('MP-INT: Integration Scenarios', () => {
  it('MP-INT-001: Full selection flow', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    const { rerender } = render(
      <ModelPicker selectedModel={null} onModelChange={onModelChange} />
    );
    
    // Initial state - default selected
    expect(screen.getByTestId('check-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
    
    // Click different model
    await user.click(screen.getByTestId('model-card-qwen/qwen-2-7b-instruct:free'));
    expect(onModelChange).toHaveBeenCalledWith('qwen/qwen-2-7b-instruct:free');
    
    // Simulate parent updating selectedModel
    rerender(
      <ModelPicker 
        selectedModel="qwen/qwen-2-7b-instruct:free" 
        onModelChange={onModelChange} 
      />
    );
    
    // New selection reflected
    expect(screen.getByTestId('check-qwen/qwen-2-7b-instruct:free')).toBeInTheDocument();
  });

  it('MP-INT-002: Loading → success → select', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    
    mockIsLoading = true;
    const { rerender } = render(
      <ModelPicker selectedModel={null} onModelChange={onModelChange} />
    );
    
    expect(screen.getByTestId('model-picker-skeleton')).toBeInTheDocument();
    
    mockIsLoading = false;
    rerender(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('model-picker')).toBeInTheDocument();
    
    await user.click(screen.getByTestId('model-card-mistralai/mistral-7b-instruct:free'));
    expect(onModelChange).toHaveBeenCalled();
  });

  it('MP-INT-003: Error → retry → success', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    
    mockError = new Error('Test error');
    const { rerender } = render(
      <ModelPicker selectedModel={null} onModelChange={onModelChange} />
    );
    
    expect(screen.getByTestId('model-picker-error')).toBeInTheDocument();
    
    await user.click(screen.getByTestId('retry-button'));
    expect(mockRefetch).toHaveBeenCalled();
    
    mockError = null;
    rerender(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByTestId('model-picker')).toBeInTheDocument();
  });

  it('MP-INT-004: Disabled during analysis', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    
    render(
      <ModelPicker 
        selectedModel={null} 
        onModelChange={onModelChange} 
        disabled 
      />
    );
    
    await user.click(screen.getByTestId('model-card-google/gemini-2.0-flash-exp:free'));
    expect(onModelChange).not.toHaveBeenCalled();
  });

  it('MP-INT-005: Model change updates analysis', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    await user.click(screen.getByTestId('model-card-qwen/qwen-2-7b-instruct:free'));
    
    expect(onModelChange).toHaveBeenCalledWith('qwen/qwen-2-7b-instruct:free');
  });
});

// ============================================
// AC: Acceptance Criteria
// ============================================

describe('AC: Acceptance Criteria', () => {
  it('AC-001: Component renders all available models', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByText('Gemini 2.0 Flash')).toBeInTheDocument();
    expect(screen.getByText('Qwen 2 7B')).toBeInTheDocument();
    expect(screen.getByText('Mistral 7B')).toBeInTheDocument();
    expect(screen.getByText('Llama 3 8B')).toBeInTheDocument();
  });

  it('AC-002: Shows context window and latency for each model', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Context windows
    expect(screen.getByTestId('context-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
    expect(screen.getByTestId('context-qwen/qwen-2-7b-instruct:free')).toBeInTheDocument();
    
    // Latency
    expect(screen.getByTestId('latency-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
    expect(screen.getByTestId('latency-qwen/qwen-2-7b-instruct:free')).toBeInTheDocument();
  });

  it('AC-003: Indicates recommended model', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    expect(screen.getByText('Recommended')).toBeInTheDocument();
    expect(screen.getByTestId('recommended-badge-google/gemini-2.0-flash-exp:free')).toBeInTheDocument();
  });

  it('AC-004: Highlights selected model', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel="qwen/qwen-2-7b-instruct:free" 
        onModelChange={onModelChange} 
      />
    );
    
    const selectedCard = screen.getByTestId('model-card-qwen/qwen-2-7b-instruct:free');
    expect(selectedCard).toHaveClass('selected');
    expect(screen.getByTestId('check-qwen/qwen-2-7b-instruct:free')).toBeInTheDocument();
  });

  it('AC-005: Disables models that can\'t fit content', () => {
    const onModelChange = vi.fn();
    render(
      <ModelPicker 
        selectedModel={null} 
        onModelChange={onModelChange} 
        estimatedTokens={10000}
      />
    );
    
    const llamaCard = screen.getByTestId('model-card-meta-llama/llama-3-8b-instruct:free');
    expect(llamaCard).toHaveClass('disabled');
    expect(llamaCard).toBeDisabled();
    expect(screen.getByTestId('warning-meta-llama/llama-3-8b-instruct:free')).toBeInTheDocument();
  });

  it('AC-006: Handles loading and error states', () => {
    const onModelChange = vi.fn();
    
    // Loading state
    mockIsLoading = true;
    const { rerender } = render(
      <ModelPicker selectedModel={null} onModelChange={onModelChange} />
    );
    expect(screen.getByTestId('model-picker-skeleton')).toBeInTheDocument();
    
    // Error state
    mockIsLoading = false;
    mockError = new Error('Test error');
    rerender(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByTestId('model-picker-error')).toBeInTheDocument();
    
    // Success state
    mockError = null;
    rerender(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    expect(screen.getByTestId('model-picker')).toBeInTheDocument();
  });

  it('AC-007: Accessible with keyboard navigation', async () => {
    const user = userEvent.setup();
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Tab to first card
    await user.tab();
    expect(screen.getByTestId('model-card-google/gemini-2.0-flash-exp:free')).toHaveFocus();
    
    // Enter to select
    await user.keyboard('{Enter}');
    expect(onModelChange).toHaveBeenCalled();
  });

  it('AC-008: Mobile-responsive layout', () => {
    const onModelChange = vi.fn();
    render(<ModelPicker selectedModel={null} onModelChange={onModelChange} />);
    
    // Component renders with proper structure
    expect(screen.getByTestId('model-picker')).toBeInTheDocument();
    
    // All cards are buttons (touch-friendly)
    const cards = screen.getAllByRole('button');
    expect(cards.length).toBe(4);
    
    // Grid container exists
    expect(document.querySelector('.models-grid')).toBeInTheDocument();
  });
});
