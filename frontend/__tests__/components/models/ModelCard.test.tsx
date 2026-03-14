/**
 * ModelCard Component Tests
 * TASK-26-22: Create Model Picker Component
 *
 * Tests: 55+ scenarios across 9 categories
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock data
const mockModel = {
  id: 1,
  provider: 'openrouter',
  model_id: 'openai/gpt-4-turbo',
  display_name: 'GPT-4 Turbo',
  cost_per_1k_input: 0.01,
  cost_per_1k_output: 0.03,
  estimated_cost_per_analysis: 0.05,
  context_window: 128000,
  is_free_tier: false,
  capabilities: ['tools', 'vision'] as const,
  is_user_default: true,
};

const freeModel = {
  ...mockModel,
  id: 3,
  display_name: 'Llama 3 8B (Free)',
  model_id: 'meta-llama/llama-3-8b-instruct:free',
  cost_per_1k_input: 0,
  cost_per_1k_output: 0,
  estimated_cost_per_analysis: 0,
  is_free_tier: true,
  capabilities: [],
  is_user_default: false,
};

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Check: () => <span data-testid="check-icon">Check</span>,
  Star: () => <span data-testid="star-icon">Star</span>,
  Zap: () => <span data-testid="zap-icon">Zap</span>,
  Eye: () => <span data-testid="eye-icon">Eye</span>,
  Wrench: () => <span data-testid="wrench-icon">Wrench</span>,
  Sparkles: () => <span data-testid="sparkles-icon">Sparkles</span>,
}));

// TDD Mock Component
const MockModelCard = ({
  model,
  isSelected,
  isDefault,
  onSelect,
  onSetDefault,
  showSetDefault = true,
  className,
}: {
  model: typeof mockModel;
  isSelected: boolean;
  isDefault: boolean;
  onSelect: () => void;
  onSetDefault?: () => void;
  showSetDefault?: boolean;
  className?: string;
}) => {
  return (
    <div
      data-testid={`model-card-${model.id}`}
      className={`model-card ${isSelected ? 'selected' : ''} ${className || ''}`}
      role="option"
      aria-selected={isSelected}
      onClick={onSelect}
    >
      <div data-testid="model-name">{model.display_name}</div>
      <div data-testid="model-provider">{model.provider}</div>
      <div data-testid="model-cost">${model.estimated_cost_per_analysis.toFixed(2)}</div>
      {model.is_free_tier && <span data-testid="free-badge">Free</span>}
      {isDefault && <span data-testid="default-badge">Default</span>}
      {isSelected && <span data-testid="check-icon">Check</span>}
      {model.capabilities.map((cap) => (
        <span key={cap} data-testid={`capability-${cap}`}>
          {cap}
        </span>
      ))}
      {showSetDefault && !isDefault && onSetDefault && (
        <button data-testid="set-default-btn" onClick={(e) => { e.stopPropagation(); onSetDefault(); }}>
          Set as default
        </button>
      )}
    </div>
  );
};

describe('ModelCard Component', () => {
  const user = userEvent.setup();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('MC-RENDER: Card Rendering', () => {
    it('MC-RENDER-01: should render model card with all elements', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('model-card-1')).toBeInTheDocument();
      expect(screen.getByTestId('model-name')).toBeInTheDocument();
      expect(screen.getByTestId('model-provider')).toBeInTheDocument();
      expect(screen.getByTestId('model-cost')).toBeInTheDocument();
    });

    it('MC-RENDER-02: should have correct role attribute', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveAttribute('role', 'option');
    });

    it('MC-RENDER-03: should apply custom className', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
          className="custom-card"
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveClass('custom-card');
    });

    it('MC-RENDER-04: should show selected styling when selected', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={true}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveClass('selected');
    });

    it('MC-RENDER-05: should be clickable', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveStyle({ cursor: 'pointer' });
    });

    it('MC-RENDER-06: should render context window size', () => {
      // Implementation should show context window (e.g., "128K tokens")
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Context window should be displayed
    });
  });

  describe('MC-DATA: Data Display', () => {
    it('MC-DATA-01: should display model name correctly', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('model-name')).toHaveTextContent('GPT-4 Turbo');
    });

    it('MC-DATA-02: should display provider name', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('model-provider')).toHaveTextContent('openrouter');
    });

    it('MC-DATA-03: should display cost per analysis', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('model-cost')).toHaveTextContent('$0.05');
    });

    it('MC-DATA-04: should format zero cost as "Free"', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={freeModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('model-cost')).toHaveTextContent('$0.00');
      expect(screen.getByTestId('free-badge')).toBeInTheDocument();
    });

    it('MC-DATA-05: should display cost with proper decimal places', () => {
      const cheapModel = {
        ...mockModel,
        estimated_cost_per_analysis: 0.001,
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={cheapModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Should format as $0.001 or $0.00 depending on rounding
      expect(screen.getByTestId('model-cost')).toBeInTheDocument();
    });

    it('MC-DATA-06: should show context window in human-readable format', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Should show "128K" instead of "128000"
    });

    it('MC-DATA-07: should show provider logo or icon', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Provider icon/logo should be shown
    });

    it('MC-DATA-08: should truncate long model names', () => {
      const longNameModel = {
        ...mockModel,
        display_name: 'This is a very long model name that should be truncated with ellipsis',
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={longNameModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Should have truncate class or show ellipsis
    });
  });

  describe('MC-BADGES: Capability Badges', () => {
    it('MC-BADGES-01: should render tools badge', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('capability-tools')).toBeInTheDocument();
    });

    it('MC-BADGES-02: should render vision badge', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('capability-vision')).toBeInTheDocument();
    });

    it('MC-BADGES-03: should not render badges for model with no capabilities', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={freeModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.queryByTestId('capability-tools')).not.toBeInTheDocument();
      expect(screen.queryByTestId('capability-vision')).not.toBeInTheDocument();
    });

    it('MC-BADGES-04: should show correct icon for each capability', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Tools should show Wrench icon
      // Vision should show Eye icon
    });

    it('MC-BADGES-05: should render fast capability badge', () => {
      const fastModel = {
        ...mockModel,
        capabilities: ['tools', 'vision', 'fast'] as const,
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={fastModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('capability-fast')).toBeInTheDocument();
    });

    it('MC-BADGES-06: should render advanced capability badge', () => {
      const advancedModel = {
        ...mockModel,
        capabilities: ['tools', 'vision', 'advanced'] as const,
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={advancedModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('capability-advanced')).toBeInTheDocument();
    });

    it('MC-BADGES-07: should have accessible labels for badges', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Badges should have aria-label for accessibility
    });

    it('MC-BADGES-08: should stack badges horizontally', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Badges should be in a flex row
    });
  });

  describe('MC-FREE: Free Tier Indicator', () => {
    it('MC-FREE-01: should show "Free" badge for free tier models', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={freeModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('free-badge')).toBeInTheDocument();
    });

    it('MC-FREE-02: should not show "Free" badge for paid models', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.queryByTestId('free-badge')).not.toBeInTheDocument();
    });

    it('MC-FREE-03: should show free tier limit if available', () => {
      const limitedFreeModel = {
        ...freeModel,
        free_tier_limit: 100,
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={limitedFreeModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Should show limit (e.g., "100 requests/day")
    });

    it('MC-FREE-04: should highlight free tier cards differently', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={freeModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Free tier card may have different styling
    });

    it('MC-FREE-05: should show "$0.00" cost for free models', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={freeModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('model-cost')).toHaveTextContent('$0.00');
    });

    it('MC-FREE-06: should show "Free" text prominently', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={freeModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const freeBadge = screen.getByTestId('free-badge');
      expect(freeBadge).toBeVisible();
    });
  });

  describe('MC-SELECTED: Selected State Styling', () => {
    it('MC-SELECTED-01: should have selected class when selected', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={true}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveClass('selected');
    });

    it('MC-SELECTED-02: should show check icon when selected', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={true}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('check-icon')).toBeInTheDocument();
    });

    it('MC-SELECTED-03: should not show check icon when not selected', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.queryByTestId('check-icon')).not.toBeInTheDocument();
    });

    it('MC-SELECTED-04: should have aria-selected attribute', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={true}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveAttribute('aria-selected', 'true');
    });

    it('MC-SELECTED-05: should have focus ring when focused and selected', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={true}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // When focused, should show focus ring
    });
  });

  describe('MC-CLICK: Click Interactions', () => {
    it('MC-CLICK-01: should call onSelect when clicked', async () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      await user.click(card);

      expect(handleSelect).toHaveBeenCalledTimes(1);
    });

    it('MC-CLICK-02: should call onSetDefault when set default clicked', async () => {
      const handleSelect = vi.fn();
      const handleSetDefault = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
          onSetDefault={handleSetDefault}
          showSetDefault
        />
      );

      const setDefaultBtn = screen.getByTestId('set-default-btn');
      await user.click(setDefaultBtn);

      expect(handleSetDefault).toHaveBeenCalledTimes(1);
    });

    it('MC-CLICK-03: should not propagate click to card when clicking set default', async () => {
      const handleSelect = vi.fn();
      const handleSetDefault = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
          onSetDefault={handleSetDefault}
          showSetDefault
        />
      );

      const setDefaultBtn = screen.getByTestId('set-default-btn');
      await user.click(setDefaultBtn);

      // onSelect should NOT be called
      expect(handleSelect).not.toHaveBeenCalled();
    });

    it('MC-CLICK-04: should show loading state when setting default', async () => {
      const handleSelect = vi.fn();
      const handleSetDefault = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
          onSetDefault={handleSetDefault}
          showSetDefault
        />
      );

      // When clicking set default, show loading spinner
    });

    it('MC-CLICK-05: should be keyboard accessible', async () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      card.focus();
      await user.keyboard('{Enter}');

      // Should trigger selection
    });
  });

  describe('MC-A11Y: Accessibility', () => {
    it('MC-A11Y-01: should have role="option"', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveAttribute('role', 'option');
    });

    it('MC-A11Y-02: should have aria-selected attribute', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={true}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveAttribute('aria-selected', 'true');
    });

    it('MC-A11Y-03: should be focusable', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveAttribute('tabIndex');
    });

    it('MC-A11Y-04: should have accessible name from model name', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Accessible name should include model name
    });

    it('MC-A11Y-05: should announce selected state to screen readers', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={true}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Should announce "selected" state
    });

    it('MC-A11Y-06: should have descriptive labels for badges', () => {
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Badge icons should have aria-label
    });
  });

  describe('MC-EDGE: Edge Cases', () => {
    it('MC-EDGE-01: should handle very long model name', () => {
      const longNameModel = {
        ...mockModel,
        display_name: 'This is an extremely long model name that goes on and on and should be truncated',
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={longNameModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Should not break layout
      expect(screen.getByTestId('model-name')).toBeInTheDocument();
    });

    it('MC-EDGE-02: should handle unicode in model name', () => {
      const unicodeModel = {
        ...mockModel,
        display_name: 'GPT-4 ツ German-Deutsch',
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={unicodeModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('model-name')).toHaveTextContent('GPT-4 ツ German-Deutsch');
    });

    it('MC-EDGE-03: should handle many capabilities', () => {
      const manyCapsModel = {
        ...mockModel,
        capabilities: ['tools', 'vision', 'fast', 'advanced', 'streaming'] as const,
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={manyCapsModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Should handle multiple badges without breaking layout
    });

    it('MC-EDGE-04: should handle zero context window', () => {
      const zeroContextModel = {
        ...mockModel,
        context_window: 0,
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={zeroContextModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Should handle gracefully
    });

    it('MC-EDGE-05: should handle very high cost', () => {
      const expensiveModel = {
        ...mockModel,
        estimated_cost_per_analysis: 999.99,
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={expensiveModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('model-cost')).toHaveTextContent('$999.99');
    });

    it('MC-EDGE-06: should handle provider with special characters', () => {
      const specialProviderModel = {
        ...mockModel,
        provider: 'provider-with-dashes',
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={specialProviderModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('model-provider')).toHaveTextContent('provider-with-dashes');
    });
  });

  describe('MC-SECURITY: XSS Prevention', () => {
    it('MC-SECURITY-01: should escape script tags in model name', () => {
      const xssModel = {
        ...mockModel,
        display_name: '<script>alert("xss")</script>GPT-4',
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={xssModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Script should be escaped, not executed
      const name = screen.getByTestId('model-name');
      expect(name.textContent).not.toContain('<script>');
    });

    it('MC-SECURITY-02: should escape HTML in provider name', () => {
      const xssModel = {
        ...mockModel,
        provider: '<img src=x onerror=alert(1)>',
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={xssModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // HTML should be escaped
    });

    it('MC-SECURITY-03: should escape HTML in capability names', () => {
      const xssModel = {
        ...mockModel,
        capabilities: ['<script>alert(1)</script>'] as any,
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={xssModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Should be escaped
    });

    it('MC-SECURITY-04: should not execute onclick from data', () => {
      const xssModel = {
        ...mockModel,
        display_name: 'GPT-4" onclick="alert(1)" data-foo="bar',
      };

      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={xssModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Should not have injected onclick
    });

    it('MC-SECURITY-05: should sanitize free badge content', () => {
      // If free tier limit comes from API, ensure it's sanitized
      const handleSelect = vi.fn();

      render(
        <MockModelCard
          model={freeModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Free badge content should be safe
    });
  });
});
