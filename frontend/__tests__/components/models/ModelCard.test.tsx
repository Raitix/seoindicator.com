/**
 * ModelCard Component Tests
 * TASK-26-22: Create Model Picker Component
 *
 * Tests the ACTUAL ModelCard component, not a mock.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Import the ACTUAL component
import { ModelCard } from '@/components/models/ModelCard';

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
  capabilities: [] as const,
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
  Loader2: () => <span data-testid="loader-icon">Loader2</span>,
}));

describe('ModelCard Component', () => {
  const user = userEvent.setup();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('MC-RENDER: Card Rendering', () => {
    it('MC-RENDER-01: should render model card with all elements', () => {
      const handleSelect = vi.fn();

      render(
        <ModelCard
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
        <ModelCard
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
        <ModelCard
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
        <ModelCard
          model={mockModel}
          isSelected={true}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveClass('ring-2');
    });

    it('MC-RENDER-05: should be clickable with cursor-pointer', () => {
      const handleSelect = vi.fn();

      render(
        <ModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveClass('cursor-pointer');
    });
  });

  describe('MC-DATA: Data Display', () => {
    it('MC-DATA-01: should display model name correctly', () => {
      const handleSelect = vi.fn();

      render(
        <ModelCard
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
        <ModelCard
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
        <ModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('model-cost')).toHaveTextContent('$0.05');
    });

    it('MC-DATA-04: should show Free badge for free tier models', () => {
      const handleSelect = vi.fn();

      render(
        <ModelCard
          model={freeModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('free-badge')).toBeInTheDocument();
    });
  });

  describe('MC-BADGES: Capability Badges', () => {
    it('MC-BADGES-01: should render tools badge', () => {
      const handleSelect = vi.fn();

      render(
        <ModelCard
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
        <ModelCard
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
        <ModelCard
          model={freeModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.queryByTestId('capability-tools')).not.toBeInTheDocument();
      expect(screen.queryByTestId('capability-vision')).not.toBeInTheDocument();
    });
  });

  describe('MC-FREE: Free Tier Indicator', () => {
    it('MC-FREE-01: should show "Free" badge for free tier models', () => {
      const handleSelect = vi.fn();

      render(
        <ModelCard
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
        <ModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.queryByTestId('free-badge')).not.toBeInTheDocument();
    });
  });

  describe('MC-SELECTED: Selected State', () => {
    it('MC-SELECTED-01: should have selected class when selected', () => {
      const handleSelect = vi.fn();

      render(
        <ModelCard
          model={mockModel}
          isSelected={true}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveClass('ring-2');
    });

    it('MC-SELECTED-02: should show check icon when selected', () => {
      const handleSelect = vi.fn();

      render(
        <ModelCard
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
        <ModelCard
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
        <ModelCard
          model={mockModel}
          isSelected={true}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveAttribute('aria-selected', 'true');
    });
  });

  describe('MC-CLICK: Click Interactions', () => {
    it('MC-CLICK-01: should call onSelect when clicked', async () => {
      const handleSelect = vi.fn();

      render(
        <ModelCard
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
        <ModelCard
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
        <ModelCard
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
  });

  describe('MC-A11Y: Accessibility', () => {
    it('MC-A11Y-01: should have role="option"', () => {
      const handleSelect = vi.fn();

      render(
        <ModelCard
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
        <ModelCard
          model={mockModel}
          isSelected={true}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveAttribute('aria-selected', 'true');
    });

    it('MC-A11Y-03: should be focusable with tabIndex', () => {
      const handleSelect = vi.fn();

      render(
        <ModelCard
          model={mockModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const card = screen.getByTestId('model-card-1');
      expect(card).toHaveAttribute('tabIndex', '0');
    });
  });

  describe('MC-EDGE: Edge Cases', () => {
    it('MC-EDGE-01: should handle very long model name', () => {
      const longNameModel = {
        ...mockModel,
        display_name: 'This is an extremely long model name that goes on and on',
      };

      const handleSelect = vi.fn();

      render(
        <ModelCard
          model={longNameModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('model-name')).toBeInTheDocument();
    });

    it('MC-EDGE-02: should handle unicode in model name', () => {
      const unicodeModel = {
        ...mockModel,
        display_name: 'GPT-4 ツ German-Deutsch',
      };

      const handleSelect = vi.fn();

      render(
        <ModelCard
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
        <ModelCard
          model={manyCapsModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      expect(screen.getByTestId('capability-tools')).toBeInTheDocument();
      expect(screen.getByTestId('capability-vision')).toBeInTheDocument();
      expect(screen.getByTestId('capability-fast')).toBeInTheDocument();
      expect(screen.getByTestId('capability-advanced')).toBeInTheDocument();
      expect(screen.getByTestId('capability-streaming')).toBeInTheDocument();
    });

    it('MC-EDGE-04: should handle provider with special characters', () => {
      const specialProviderModel = {
        ...mockModel,
        provider: 'provider-with-dashes',
      };

      const handleSelect = vi.fn();

      render(
        <ModelCard
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
    it('MC-SECURITY-01: should sanitize script tags in model name', () => {
      const xssModel = {
        ...mockModel,
        display_name: '<script>alert("xss")</script>GPT-4',
      };

      const handleSelect = vi.fn();

      render(
        <ModelCard
          model={xssModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // Script tags should be removed by sanitizeText
      // React escapes text content, so XSS is prevented even if text contains suspicious content
      const name = screen.getByTestId('model-name');
      expect(name.textContent).not.toContain('<script>');
      expect(name.textContent).not.toContain('</script>');
      // The content inside script tags becomes plain text (safe in React)
      expect(name.textContent).toContain('GPT-4');
    });

    it('MC-SECURITY-02: should sanitize HTML in provider name', () => {
      const xssModel = {
        ...mockModel,
        provider: '<img src=x onerror=alert(1)>',
      };

      const handleSelect = vi.fn();

      render(
        <ModelCard
          model={xssModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      const provider = screen.getByTestId('model-provider');
      expect(provider.textContent).not.toContain('<img');
    });

    it('MC-SECURITY-03: should handle onclick in data safely', () => {
      const xssModel = {
        ...mockModel,
        display_name: 'GPT-4" onclick="alert(1)" data-foo="bar',
      };

      const handleSelect = vi.fn();

      render(
        <ModelCard
          model={xssModel}
          isSelected={false}
          isDefault={false}
          onSelect={handleSelect}
        />
      );

      // The text is sanitized - HTML tags removed, but plain text remains
      // React escapes text content, so onclick as text is safe (not an event handler)
      const name = screen.getByTestId('model-name');
      expect(name.textContent).not.toContain('<');
      expect(name.textContent).toContain('GPT-4');
      // Note: 'onclick' as plain text is safe - it's not an actual event handler
    });
  });
});
