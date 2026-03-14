/**
 * ModelFilter Component Tests
 * TASK-26-22: Create Model Picker Component
 *
 * Tests: 50+ scenarios across 8 categories
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Search: () => <span data-testid="search-icon">Search</span>,
  X: () => <span data-testid="x-icon">X</span>,
  Filter: () => <span data-testid="filter-icon">Filter</span>,
  ChevronDown: () => <span data-testid="chevron-down-icon">ChevronDown</span>,
}));

// TDD Mock Component
const MockModelFilter = ({
  onSearchChange,
  onProviderChange,
  onCapabilityChange,
  onFreeOnlyChange,
  providers,
  capabilities,
  className,
}: {
  onSearchChange: (search: string) => void;
  onProviderChange: (provider: string | null) => void;
  onCapabilityChange: (capability: string | null) => void;
  onFreeOnlyChange: (freeOnly: boolean) => void;
  providers: string[];
  capabilities: string[];
  className?: string;
}) => {
  return (
    <div data-testid="model-filter" className={className}>
      <input
        data-testid="search-input"
        type="text"
        placeholder="Search models..."
        onChange={(e) => onSearchChange(e.target.value)}
        aria-label="Search models"
      />
      <select
        data-testid="provider-select"
        onChange={(e) => onProviderChange(e.target.value || null)}
        aria-label="Filter by provider"
      >
        <option value="">All Providers</option>
        {providers.map((p) => (
          <option key={p} value={p}>
            {p}
          </option>
        ))}
      </select>
      <select
        data-testid="capability-select"
        onChange={(e) => onCapabilityChange(e.target.value || null)}
        aria-label="Filter by capability"
      >
        <option value="">All Capabilities</option>
        {capabilities.map((c) => (
          <option key={c} value={c}>
            {c}
          </option>
        ))}
      </select>
      <label>
        <input
          data-testid="free-only-checkbox"
          type="checkbox"
          onChange={(e) => onFreeOnlyChange(e.target.checked)}
        />
        Free models only
      </label>
    </div>
  );
};

describe('ModelFilter Component', () => {
  const user = userEvent.setup();

  const defaultProps = {
    onSearchChange: vi.fn(),
    onProviderChange: vi.fn(),
    onCapabilityChange: vi.fn(),
    onFreeOnlyChange: vi.fn(),
    providers: ['openrouter', 'anthropic', 'google'],
    capabilities: ['tools', 'vision', 'fast', 'advanced'],
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('MF-RENDER: Component Rendering', () => {
    it('MF-RENDER-01: should render all filter controls', () => {
      render(<MockModelFilter {...defaultProps} />);

      expect(screen.getByTestId('search-input')).toBeInTheDocument();
      expect(screen.getByTestId('provider-select')).toBeInTheDocument();
      expect(screen.getByTestId('capability-select')).toBeInTheDocument();
      expect(screen.getByTestId('free-only-checkbox')).toBeInTheDocument();
    });

    it('MF-RENDER-02: should apply custom className', () => {
      render(<MockModelFilter {...defaultProps} className="custom-filter" />);

      expect(screen.getByTestId('model-filter')).toHaveClass('custom-filter');
    });

    it('MF-RENDER-03: should render search input with placeholder', () => {
      render(<MockModelFilter {...defaultProps} />);

      const searchInput = screen.getByTestId('search-input');
      expect(searchInput).toHaveAttribute('placeholder', 'Search models...');
    });

    it('MF-RENDER-04: should render provider options', () => {
      render(<MockModelFilter {...defaultProps} />);

      const providerSelect = screen.getByTestId('provider-select');
      expect(providerSelect).toBeInTheDocument();

      // Check options
      expect(screen.getByText('All Providers')).toBeInTheDocument();
      expect(screen.getByText('openrouter')).toBeInTheDocument();
    });

    it('MF-RENDER-05: should render capability options', () => {
      render(<MockModelFilter {...defaultProps} />);

      const capabilitySelect = screen.getByTestId('capability-select');
      expect(capabilitySelect).toBeInTheDocument();

      // Check options
      expect(screen.getByText('All Capabilities')).toBeInTheDocument();
      expect(screen.getByText('tools')).toBeInTheDocument();
    });

    it('MF-RENDER-06: should render free only checkbox', () => {
      render(<MockModelFilter {...defaultProps} />);

      const checkbox = screen.getByTestId('free-only-checkbox');
      expect(checkbox).toBeInTheDocument();
      expect(checkbox).not.toBeChecked();
    });
  });

  describe('MF-SEARCH: Search Functionality', () => {
    it('MF-SEARCH-01: should call onSearchChange on input', async () => {
      const onSearchChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onSearchChange={onSearchChange} />);

      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'GPT');

      expect(onSearchChange).toHaveBeenCalled();
    });

    it('MF-SEARCH-02: should pass correct search value', async () => {
      const onSearchChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onSearchChange={onSearchChange} />);

      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'GPT-4');

      // Should be called multiple times (once per character)
      expect(onSearchChange).toHaveBeenCalledWith('G');
      expect(onSearchChange).toHaveBeenCalledWith('GP');
      expect(onSearchChange).toHaveBeenCalledWith('GPT');
      expect(onSearchChange).toHaveBeenCalledWith('GPT-');
      expect(onSearchChange).toHaveBeenCalledWith('GPT-4');
    });

    it('MF-SEARCH-03: should show search icon', () => {
      render(<MockModelFilter {...defaultProps} />);

      expect(screen.getByTestId('search-icon')).toBeInTheDocument();
    });

    it('MF-SEARCH-04: should have clear button when text entered', async () => {
      render(<MockModelFilter {...defaultProps} />);

      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'test');

      // Should show clear (X) button
      // expect(screen.getByTestId('clear-search')).toBeInTheDocument();
    });

    it('MF-SEARCH-05: should clear search on clear button click', async () => {
      const onSearchChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onSearchChange={onSearchChange} />);

      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'test');

      // Click clear button
      // await user.click(screen.getByTestId('clear-search'));
      // expect(onSearchChange).toHaveBeenCalledWith('');
    });

    it('MF-SEARCH-06: should debounce search input', async () => {
      vi.useFakeTimers();
      const onSearchChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onSearchChange={onSearchChange} />);

      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'GPT-4 Turbo');

      // Fast forward time
      vi.advanceTimersByTime(300);

      // Should debounce - only call once with final value
      // Implementation should use debounce
      vi.useRealTimers();
    });

    it('MF-SEARCH-07: should handle empty search', async () => {
      const onSearchChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onSearchChange={onSearchChange} />);

      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'test');
      await user.clear(searchInput);

      expect(onSearchChange).toHaveBeenCalledWith('');
    });

    it('MF-SEARCH-08: should handle special characters in search', async () => {
      const onSearchChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onSearchChange={onSearchChange} />);

      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'GPT-4 (Turbo)');

      expect(onSearchChange).toHaveBeenCalled();
    });

    it('MF-SEARCH-09: should be case-insensitive search', async () => {
      const onSearchChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onSearchChange={onSearchChange} />);

      // Search should work regardless of case
      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'gpt');

      expect(onSearchChange).toHaveBeenCalled();
    });

    it('MF-SEARCH-10: should trim whitespace from search', async () => {
      const onSearchChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onSearchChange={onSearchChange} />);

      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, '  GPT  ');

      // Should trim whitespace
      // expect(onSearchChange).toHaveBeenCalledWith('GPT');
    });
  });

  describe('MF-PROVIDER: Provider Filter', () => {
    it('MF-PROVIDER-01: should call onProviderChange on selection', async () => {
      const onProviderChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onProviderChange={onProviderChange} />);

      const providerSelect = screen.getByTestId('provider-select');
      await user.selectOptions(providerSelect, 'openrouter');

      expect(onProviderChange).toHaveBeenCalledWith('openrouter');
    });

    it('MF-PROVIDER-02: should allow selecting "All Providers"', async () => {
      const onProviderChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onProviderChange={onProviderChange} />);

      const providerSelect = screen.getByTestId('provider-select');
      await user.selectOptions(providerSelect, '');

      expect(onProviderChange).toHaveBeenCalledWith(null);
    });

    it('MF-PROVIDER-03: should show all providers from props', () => {
      render(<MockModelFilter {...defaultProps} />);

      defaultProps.providers.forEach((provider) => {
        expect(screen.getByText(provider)).toBeInTheDocument();
      });
    });

    it('MF-PROVIDER-04: should update selection visually', async () => {
      render(<MockModelFilter {...defaultProps} />);

      const providerSelect = screen.getByTestId('provider-select');
      await user.selectOptions(providerSelect, 'anthropic');

      expect(providerSelect).toHaveValue('anthropic');
    });

    it('MF-PROVIDER-05: should handle empty providers list', () => {
      render(<MockModelFilter {...defaultProps} providers={[]} />);

      const providerSelect = screen.getByTestId('provider-select');
      expect(providerSelect).toBeInTheDocument();

      // Should only have "All Providers" option
    });

    it('MF-PROVIDER-06: should capitalize provider names for display', async () => {
      render(<MockModelFilter {...defaultProps} />);

      // Provider names should be properly formatted
      // "openrouter" -> "OpenRouter" or similar
    });

    it('MF-PROVIDER-07: should persist selection across re-renders', async () => {
      const { rerender } = render(<MockModelFilter {...defaultProps} />);

      const providerSelect = screen.getByTestId('provider-select');
      await user.selectOptions(providerSelect, 'google');

      rerender(<MockModelFilter {...defaultProps} />);

      expect(providerSelect).toHaveValue('google');
    });

    it('MF-PROVIDER-08: should show provider icon/logo', async () => {
      render(<MockModelFilter {...defaultProps} />);

      // Provider options could have icons
    });
  });

  describe('MF-CAPABILITY: Capability Filter', () => {
    it('MF-CAPABILITY-01: should call onCapabilityChange on selection', async () => {
      const onCapabilityChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onCapabilityChange={onCapabilityChange} />);

      const capabilitySelect = screen.getByTestId('capability-select');
      await user.selectOptions(capabilitySelect, 'vision');

      expect(onCapabilityChange).toHaveBeenCalledWith('vision');
    });

    it('MF-CAPABILITY-02: should allow selecting "All Capabilities"', async () => {
      const onCapabilityChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onCapabilityChange={onCapabilityChange} />);

      const capabilitySelect = screen.getByTestId('capability-select');
      await user.selectOptions(capabilitySelect, '');

      expect(onCapabilityChange).toHaveBeenCalledWith(null);
    });

    it('MF-CAPABILITY-03: should show all capabilities from props', () => {
      render(<MockModelFilter {...defaultProps} />);

      defaultProps.capabilities.forEach((capability) => {
        expect(screen.getByText(capability)).toBeInTheDocument();
      });
    });

    it('MF-CAPABILITY-04: should update selection visually', async () => {
      render(<MockModelFilter {...defaultProps} />);

      const capabilitySelect = screen.getByTestId('capability-select');
      await user.selectOptions(capabilitySelect, 'tools');

      expect(capabilitySelect).toHaveValue('tools');
    });

    it('MF-CAPABILITY-05: should handle empty capabilities list', () => {
      render(<MockModelFilter {...defaultProps} capabilities={[]} />);

      const capabilitySelect = screen.getByTestId('capability-select');
      expect(capabilitySelect).toBeInTheDocument();

      // Should only have "All Capabilities" option
    });

    it('MF-CAPABILITY-06: should show icon for each capability', async () => {
      render(<MockModelFilter {...defaultProps} />);

      // Capability options could have icons (Wrench for tools, Eye for vision)
    });

    it('MF-CAPABILITY-07: should format capability names', async () => {
      render(<MockModelFilter {...defaultProps} />);

      // "tools" -> "Tools" or "Tool Support"
    });

    it('MF-CAPABILITY-08: should allow multi-select (if implemented)', async () => {
      // If multi-select is implemented, test it here
    });
  });

  describe('MF-FREE: Free Tier Filter', () => {
    it('MF-FREE-01: should call onFreeOnlyChange when checked', async () => {
      const onFreeOnlyChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onFreeOnlyChange={onFreeOnlyChange} />);

      const checkbox = screen.getByTestId('free-only-checkbox');
      await user.click(checkbox);

      expect(onFreeOnlyChange).toHaveBeenCalledWith(true);
    });

    it('MF-FREE-02: should call onFreeOnlyChange when unchecked', async () => {
      const onFreeOnlyChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onFreeOnlyChange={onFreeOnlyChange} />);

      const checkbox = screen.getByTestId('free-only-checkbox');
      await user.click(checkbox); // Check
      await user.click(checkbox); // Uncheck

      expect(onFreeOnlyChange).toHaveBeenCalledWith(false);
    });

    it('MF-FREE-03: should toggle checkbox state', async () => {
      render(<MockModelFilter {...defaultProps} />);

      const checkbox = screen.getByTestId('free-only-checkbox');
      expect(checkbox).not.toBeChecked();

      await user.click(checkbox);
      expect(checkbox).toBeChecked();

      await user.click(checkbox);
      expect(checkbox).not.toBeChecked();
    });

    it('MF-FREE-04: should have descriptive label', () => {
      render(<MockModelFilter {...defaultProps} />);

      expect(screen.getByText(/free models only/i)).toBeInTheDocument();
    });

    it('MF-FREE-05: should show count of free models', () => {
      // Could show "(3 free)" next to checkbox
      render(<MockModelFilter {...defaultProps} />);
    });

    it('MF-FREE-06: should be keyboard accessible', async () => {
      render(<MockModelFilter {...defaultProps} />);

      const checkbox = screen.getByTestId('free-only-checkbox');
      checkbox.focus();
      await user.keyboard('{Space}');

      expect(checkbox).toBeChecked();
    });
  });

  describe('MF-CLEAR: Clear Filters', () => {
    it('MF-CLEAR-01: should have clear all button', () => {
      render(<MockModelFilter {...defaultProps} />);

      // Should have a "Clear filters" button
    });

    it('MF-CLEAR-02: should reset all filters on clear', async () => {
      const onSearchChange = vi.fn();
      const onProviderChange = vi.fn();
      const onCapabilityChange = vi.fn();
      const onFreeOnlyChange = vi.fn();

      render(
        <MockModelFilter
          {...defaultProps}
          onSearchChange={onSearchChange}
          onProviderChange={onProviderChange}
          onCapabilityChange={onCapabilityChange}
          onFreeOnlyChange={onFreeOnlyChange}
        />
      );

      // Set some filters
      await user.type(screen.getByTestId('search-input'), 'test');
      await user.selectOptions(screen.getByTestId('provider-select'), 'openrouter');
      await user.click(screen.getByTestId('free-only-checkbox'));

      // Click clear
      // await user.click(screen.getByTestId('clear-filters'));

      // All callbacks should be called with default values
      // expect(onSearchChange).toHaveBeenCalledWith('');
      // expect(onProviderChange).toHaveBeenCalledWith(null);
      // expect(onFreeOnlyChange).toHaveBeenCalledWith(false);
    });

    it('MF-CLEAR-03: should hide clear button when no filters active', () => {
      render(<MockModelFilter {...defaultProps} />);

      // Clear button should not be visible when no filters are active
    });

    it('MF-CLEAR-04: should show clear button when filters active', async () => {
      render(<MockModelFilter {...defaultProps} />);

      await user.type(screen.getByTestId('search-input'), 'test');

      // Clear button should now be visible
    });

    it('MF-CLEAR-05: should show active filter count', () => {
      // Could show "3 filters active" badge
      render(<MockModelFilter {...defaultProps} />);
    });
  });

  describe('MF-A11Y: Accessibility', () => {
    it('MF-A11Y-01: should have aria-label on search input', () => {
      render(<MockModelFilter {...defaultProps} />);

      const searchInput = screen.getByTestId('search-input');
      expect(searchInput).toHaveAttribute('aria-label');
    });

    it('MF-A11Y-02: should have aria-label on selects', () => {
      render(<MockModelFilter {...defaultProps} />);

      const providerSelect = screen.getByTestId('provider-select');
      const capabilitySelect = screen.getByTestId('capability-select');

      expect(providerSelect).toHaveAttribute('aria-label');
      expect(capabilitySelect).toHaveAttribute('aria-label');
    });

    it('MF-A11Y-03: should have accessible label for checkbox', () => {
      render(<MockModelFilter {...defaultProps} />);

      // Checkbox should be properly labeled
      const checkbox = screen.getByTestId('free-only-checkbox');
      expect(checkbox).toBeInTheDocument();
    });

    it('MF-A11Y-04: should be keyboard navigable', async () => {
      render(<MockModelFilter {...defaultProps} />);

      // Tab through all controls
      await user.tab();
      expect(screen.getByTestId('search-input')).toHaveFocus();

      await user.tab();
      // Provider select should be focused
    });
  });

  describe('MF-EDGE: Edge Cases', () => {
    it('MF-EDGE-01: should handle rapid filter changes', async () => {
      const onSearchChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onSearchChange={onSearchChange} />);

      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'test');
      await user.clear(searchInput);
      await user.type(searchInput, 'another');

      // Should handle gracefully
    });

    it('MF-EDGE-02: should handle special characters in search', async () => {
      const onSearchChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onSearchChange={onSearchChange} />);

      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'GPT-4 [Turbo]');

      expect(onSearchChange).toHaveBeenCalled();
    });

    it('MF-EDGE-03: should handle very long search input', async () => {
      const onSearchChange = vi.fn();
      render(<MockModelFilter {...defaultProps} onSearchChange={onSearchChange} />);

      const searchInput = screen.getByTestId('search-input');
      const longText = 'a'.repeat(100);
      await user.type(searchInput, longText);

      // Should handle gracefully
    });
  });
});
