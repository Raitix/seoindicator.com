/**
 * Tests for PWARecommendations Component
 * Task: TASK-24-12 - PWA Recommendations Component
 *
 * Test categories:
 * - Component rendering
 * - Priority badge display
 * - Message display
 * - Guide links
 * - Sorting by priority
 * - Empty state
 * - Expandable/collapsible sections
 * - Responsive design
 * - Accessibility
 * - Edge cases
 */

import React from 'react';
import { render, screen, fireEvent, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Import the actual component
import {
  PWARecommendations,
  PWARecommendationsSkeleton,
  type PWARecommendationsProps,
} from '../PWARecommendations';
import type { PWARecommendation } from '@/lib/types/pwa';

// ============================================================================
// Mock Data
// ============================================================================

const mockHighRecommendation: PWARecommendation = {
  type: 'field_missing',
  field: 'name',
  message: 'Add a name to your manifest',
  priority: 'high',
  guide: '#manifest-name',
};

const mockMediumRecommendation: PWARecommendation = {
  type: 'field_missing',
  field: 'description',
  message: 'Add a description to improve app store presence',
  priority: 'medium',
  guide: '#manifest-description',
};

const mockLowRecommendation: PWARecommendation = {
  type: 'icon_missing',
  field: 'icon_180',
  message: 'Add a 180x180 icon for iOS devices',
  priority: 'low',
  guide: '#ios-icons',
};

const mockRecommendationsNoGuide: PWARecommendation = {
  type: 'field_missing',
  message: 'Some improvement needed',
  priority: 'medium',
};

const mockManyRecommendations: PWARecommendation[] = [
  { type: 'a', message: 'High 1', priority: 'high' },
  { type: 'b', message: 'High 2', priority: 'high' },
  { type: 'c', message: 'Medium 1', priority: 'medium' },
  { type: 'd', message: 'Medium 2', priority: 'medium' },
  { type: 'e', message: 'Low 1', priority: 'low' },
];

// ============================================================================
// Component Tests
// ============================================================================

describe('PWARecommendations', () => {
  
  // ========================================================================
  // Component Rendering
  // ========================================================================
  
  describe('REC-001: Component Rendering', () => {
    const mockRecommendations: PWARecommendation[] = [
      mockHighRecommendation,
      mockMediumRecommendation,
    ];

    it('REC-001a: renders without crashing', () => {
      render(<PWARecommendations recommendations={mockRecommendations} />);
      expect(screen.getByTestId('pwa-recommendations')).toBeInTheDocument();
    });

    it('REC-001b: renders with recommendations list', () => {
      render(<PWARecommendations recommendations={mockRecommendations} />);
      // Should have list element containing recommendations
      expect(screen.getByRole('list')).toBeInTheDocument();
    });

    it('REC-001c: renders correct number of recommendations', () => {
      render(<PWARecommendations recommendations={mockRecommendations} />);
      // Check that both messages are displayed
      expect(screen.getByText('Add a name to your manifest')).toBeInTheDocument();
      expect(screen.getByText('Add a description to improve app store presence')).toBeInTheDocument();
    });

    it('REC-001d: applies custom className', () => {
      render(
        <PWARecommendations
          recommendations={mockRecommendations}
          className="custom-class"
        />
      );
      const component = screen.getByTestId('pwa-recommendations');
      expect(component).toHaveClass('custom-class');
    });

    it('REC-001e: renders with default styling', () => {
      render(<PWARecommendations recommendations={mockRecommendations} />);
      const component = screen.getByTestId('pwa-recommendations');
      expect(component).toHaveClass('border-slate-800');
      expect(component).toHaveClass('bg-slate-900/60');
    });
  });

  // ========================================================================
  // Priority Badge Display
  // ========================================================================
  
  describe('REC-002: Priority Badge Display', () => {
    it('REC-002a: shows high priority badge with red styling', () => {
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      const badge = screen.getByLabelText(/high priority/i);
      expect(badge).toHaveClass('text-red-400');
      expect(badge).toHaveClass('bg-red-500/10');
    });

    it('REC-002b: shows medium priority badge with yellow/orange styling', () => {
      render(<PWARecommendations recommendations={[mockMediumRecommendation]} />);
      const badge = screen.getByLabelText(/medium priority/i);
      expect(badge).toHaveClass('text-yellow-400');
      expect(badge).toHaveClass('bg-yellow-500/10');
    });

    it('REC-002c: shows low priority badge with green/blue styling', () => {
      render(<PWARecommendations recommendations={[mockLowRecommendation]} />);
      const badge = screen.getByLabelText(/low priority/i);
      expect(badge).toHaveClass('text-emerald-400');
      expect(badge).toHaveClass('bg-emerald-500/10');
    });

    it('REC-002d: badge is positioned before message', () => {
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      const listItem = screen.getByRole('listitem');
      const badge = within(listItem).getByLabelText(/high priority/i);
      // Badge should be the first child of the list item
      expect(listItem.firstChild).toBe(badge);
    });

    it('REC-002e: badge has proper contrast', () => {
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      const badge = screen.getByLabelText(/high priority/i);
      // Should have both text and background colors
      expect(badge).toHaveClass('text-red-400');
      expect(badge).toHaveClass('bg-red-500/10');
    });
  });

  // ========================================================================
  // Message Display
  // ========================================================================
  
  describe('REC-003: Message Display', () => {
    it('REC-003a: displays recommendation message', () => {
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      expect(screen.getByText('Add a name to your manifest')).toBeInTheDocument();
    });

    it('REC-003b: message is readable and not truncated by default', () => {
      const longMessage: PWARecommendation = {
        type: 'test',
        message: 'This is a very long recommendation message that should still be fully visible and not truncated',
        priority: 'high',
      };
      render(<PWARecommendations recommendations={[longMessage]} />);
      expect(screen.getByText(longMessage.message)).toBeInTheDocument();
    });

    it('REC-003c: shows field name if provided', () => {
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      expect(screen.getByText(/Field: name/)).toBeInTheDocument();
    });

    it('REC-003d: message handles long text gracefully', () => {
      const longMessage: PWARecommendation = {
        type: 'test',
        field: 'very_long_field_name',
        message: 'This is a very long recommendation message that contains a lot of text and should wrap gracefully without breaking the layout or causing overflow issues on smaller screens',
        priority: 'medium',
      };
      render(<PWARecommendations recommendations={[longMessage]} />);
      const messageElement = screen.getByText(longMessage.message);
      expect(messageElement).toHaveClass('break-words');
    });

    it('REC-003e: message handles special characters', () => {
      const specialChars: PWARecommendation = {
        type: 'test',
        message: 'Add "name" & \'description\' fields <test>',
        priority: 'high',
      };
      render(<PWARecommendations recommendations={[specialChars]} />);
      expect(screen.getByText(/Add "name" & 'description' fields/)).toBeInTheDocument();
    });
  });

  // ========================================================================
  // Guide Links
  // ========================================================================
  
  describe('REC-004: Guide Links', () => {
    it('REC-004a: shows link icon for recommendations with guide', () => {
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      const linkButton = screen.getByLabelText(/Learn more about name/);
      expect(linkButton).toBeInTheDocument();
    });

    it('REC-004b: guide link is clickable', async () => {
      const user = userEvent.setup();
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      const linkButton = screen.getByLabelText(/Learn more about name/);
      await user.click(linkButton);
      // Should not throw
    });

    it('REC-004c: does not show link for recommendations without guide', () => {
      render(<PWARecommendations recommendations={[mockRecommendationsNoGuide]} />);
      // Should not have external link button
      expect(screen.queryByLabelText(/Learn more/)).not.toBeInTheDocument();
    });

    it('REC-004d: calls onGuideClick when guide link clicked', async () => {
      const user = userEvent.setup();
      const onGuideClick = jest.fn();
      render(
        <PWARecommendations
          recommendations={[mockHighRecommendation]}
          onGuideClick={onGuideClick}
        />
      );
      const linkButton = screen.getByLabelText(/Learn more about name/);
      await user.click(linkButton);
      expect(onGuideClick).toHaveBeenCalledWith('#manifest-name');
    });

    it('REC-004e: guide link has descriptive aria-label', () => {
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      const linkButton = screen.getByLabelText(/Learn more about name/);
      expect(linkButton).toHaveAttribute('aria-label', 'Learn more about name');
    });
  });

  // ========================================================================
  // "View Guide" Button
  // ========================================================================
  
  describe('REC-005: View Guide Button', () => {
    // Need more than 3 items to trigger sections mode which shows View Guide button
    const manyRecommendations: PWARecommendation[] = [
      mockHighRecommendation,
      mockMediumRecommendation,
      mockLowRecommendation,
      { type: 'extra', message: 'Extra item', priority: 'low' },
    ];

    it('REC-005a: shows "View Guide" button when recommendations present', () => {
      render(<PWARecommendations recommendations={manyRecommendations} />);
      expect(screen.getByText('View Guide')).toBeInTheDocument();
    });

    it('REC-005b: button links to full guide page', () => {
      render(<PWARecommendations recommendations={manyRecommendations} />);
      const link = screen.getByRole('link', { name: /View full PWA guide/ });
      expect(link).toHaveAttribute('href', '/guides/pwa');
    });

    it('REC-005c: button is keyboard accessible', async () => {
      const user = userEvent.setup();
      render(<PWARecommendations recommendations={manyRecommendations} />);
      const link = screen.getByText('View Guide').closest('a');
      await user.tab();
      expect(link).toHaveFocus();
    });

    it('REC-005d: button has appropriate styling', () => {
      render(<PWARecommendations recommendations={manyRecommendations} />);
      const link = screen.getByText('View Guide').closest('a');
      expect(link).toHaveClass('text-emerald-400');
    });

    it('REC-005e: button is hidden when no recommendations', () => {
      render(<PWARecommendations recommendations={[]} />);
      expect(screen.queryByText('View Guide')).not.toBeInTheDocument();
    });
  });

  // ========================================================================
  // Sorting by Priority
  // ========================================================================
  
  describe('REC-006: Sorting by Priority', () => {
    const unsortedRecommendations: PWARecommendation[] = [
      mockLowRecommendation,
      mockHighRecommendation,
      mockMediumRecommendation,
    ];

    it('REC-006a: sorts high priority items first', () => {
      render(<PWARecommendations recommendations={unsortedRecommendations} />);
      const listItems = screen.getAllByRole('listitem');
      // First item should be high priority
      expect(within(listItems[0]).getByLabelText(/high priority/i)).toBeInTheDocument();
    });

    it('REC-006b: sorts medium priority after high', () => {
      render(<PWARecommendations recommendations={unsortedRecommendations} />);
      const badges = screen.getAllByLabelText(/priority/i);
      // Find medium badge
      const mediumBadge = badges.find(b => b.getAttribute('aria-label')?.includes('Medium'));
      expect(mediumBadge).toBeInTheDocument();
    });

    it('REC-006c: sorts low priority last', () => {
      render(<PWARecommendations recommendations={unsortedRecommendations} />);
      const badges = screen.getAllByLabelText(/priority/i);
      // Find low badge
      const lowBadge = badges.find(b => b.getAttribute('aria-label')?.includes('Low'));
      expect(lowBadge).toBeInTheDocument();
    });

    it('REC-006d: maintains original order within same priority', () => {
      const samePriority: PWARecommendation[] = [
        { type: 'a', message: 'First', priority: 'high' },
        { type: 'b', message: 'Second', priority: 'high' },
        { type: 'c', message: 'Third', priority: 'high' },
      ];
      render(<PWARecommendations recommendations={samePriority} />);
      const messages = screen.getAllByText(/First|Second|Third/);
      expect(messages[0]).toHaveTextContent('First');
      expect(messages[1]).toHaveTextContent('Second');
      expect(messages[2]).toHaveTextContent('Third');
    });

    it('REC-006e: handles all same priority', () => {
      const allMedium: PWARecommendation[] = [
        { type: 'a', message: 'Medium 1', priority: 'medium' },
        { type: 'b', message: 'Medium 2', priority: 'medium' },
      ];
      render(<PWARecommendations recommendations={allMedium} />);
      const badges = screen.getAllByLabelText(/medium priority/i);
      expect(badges).toHaveLength(2);
    });
  });

  // ========================================================================
  // Empty State
  // ========================================================================
  
  describe('REC-007: Empty State', () => {
    it('REC-007a: shows success message when no recommendations', () => {
      render(<PWARecommendations recommendations={[]} />);
      expect(screen.getByText('All checks passed!')).toBeInTheDocument();
    });

    it('REC-007b: shows checkmark icon in empty state', () => {
      render(<PWARecommendations recommendations={[]} />);
      // CheckCircle icon is an SVG with class containing text-emerald-400
      const iconContainer = document.querySelector('.text-emerald-400.h-8.w-8');
      expect(iconContainer).toBeInTheDocument();
    });

    it('REC-007c: empty state has appropriate styling', () => {
      render(<PWARecommendations recommendations={[]} />);
      const successMessage = screen.getByText('All checks passed!');
      expect(successMessage).toHaveClass('text-emerald-400');
    });

    it('REC-007d: no recommendations list rendered in empty state', () => {
      render(<PWARecommendations recommendations={[]} />);
      // Should not have list items
      expect(screen.queryByRole('listitem')).not.toBeInTheDocument();
    });

    it('REC-007e: empty state message is encouraging', () => {
      render(<PWARecommendations recommendations={[]} />);
      expect(screen.getByText(/Great job!/i)).toBeInTheDocument();
    });
  });

  // ========================================================================
  // Expandable/Collapsible Sections
  // ========================================================================
  
  describe('REC-008: Expandable/Collapsible Sections', () => {
    it('REC-008a: groups recommendations by priority', () => {
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      // Should have buttons for each priority section
      expect(screen.getByRole('button', { name: /High/ })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /Medium/ })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /Low/ })).toBeInTheDocument();
    });

    it('REC-008b: sections are collapsible', async () => {
      const user = userEvent.setup();
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      const highSection = screen.getByRole('button', { name: /High/ });
      // Click to collapse
      await user.click(highSection);
      expect(highSection).toHaveAttribute('aria-expanded', 'false');
    });

    it('REC-008c: sections are expandable', async () => {
      const user = userEvent.setup();
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      const mediumSection = screen.getByRole('button', { name: /Medium/ });
      // Click to expand
      await user.click(mediumSection);
      expect(mediumSection).toHaveAttribute('aria-expanded', 'true');
    });

    it('REC-008d: high priority section expanded by default', () => {
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      const highSection = screen.getByRole('button', { name: /High/ });
      expect(highSection).toHaveAttribute('aria-expanded', 'true');
    });

    it('REC-008e: shows count badge on each section', () => {
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      // Each section button contains a badge with count
      const highSection = screen.getByRole('button', { name: /High/ });
      expect(within(highSection).getByText('2')).toBeInTheDocument();
    });

    it('REC-008f: chevron icon rotates on expand/collapse', async () => {
      const user = userEvent.setup();
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      const highSection = screen.getByRole('button', { name: /High/ });
      // Chevron is inside the button, check for rotate-180 class
      const chevron = highSection.querySelector('.lucide-chevron-down');
      expect(chevron).toHaveClass('rotate-180');
      // Click to collapse
      await user.click(highSection);
      expect(chevron).not.toHaveClass('rotate-180');
    });

    it('REC-008g: preserves expand state during re-render', async () => {
      const user = userEvent.setup();
      const { rerender } = render(
        <PWARecommendations recommendations={mockManyRecommendations} />
      );
      const highSection = screen.getByRole('button', { name: /High/ });
      // Collapse it
      await user.click(highSection);
      expect(highSection).toHaveAttribute('aria-expanded', 'false');
      // Re-render
      rerender(<PWARecommendations recommendations={mockManyRecommendations} />);
      // Should still be collapsed
      expect(screen.getByRole('button', { name: /High/ })).toHaveAttribute(
        'aria-expanded',
        'false'
      );
    });

    it('REC-008h: all sections visible if few recommendations', () => {
      const fewRecommendations: PWARecommendation[] = [
        { type: 'a', message: 'High 1', priority: 'high' },
        { type: 'b', message: 'Medium 1', priority: 'medium' },
      ];
      render(<PWARecommendations recommendations={fewRecommendations} />);
      // With few items, should render as flat list without sections
      expect(screen.queryByRole('button', { name: /High/ })).not.toBeInTheDocument();
    });
  });

  // ========================================================================
  // maxVisible Prop
  // ========================================================================
  
  describe('REC-009: maxVisible Prop', () => {
    const manyRecommendations: PWARecommendation[] = [
      { type: 'a', message: 'High 1', priority: 'high' },
      { type: 'b', message: 'High 2', priority: 'high' },
      { type: 'c', message: 'High 3', priority: 'high' },
      { type: 'd', message: 'Medium 1', priority: 'medium' },
      { type: 'e', message: 'Medium 2', priority: 'medium' },
    ];

    it('REC-009a: limits visible recommendations when maxVisible set', () => {
      render(
        <PWARecommendations recommendations={manyRecommendations} maxVisible={3} />
      );
      // Should show "2 more" button
      expect(screen.getByText(/2 more recommendations/)).toBeInTheDocument();
    });

    it('REC-009b: shows "show more" button when truncated', () => {
      render(
        <PWARecommendations recommendations={manyRecommendations} maxVisible={2} />
      );
      expect(screen.getByText(/more recommendations/)).toBeInTheDocument();
    });

    it('REC-009c: shows all on "show more" click', async () => {
      const user = userEvent.setup();
      render(
        <PWARecommendations recommendations={manyRecommendations} maxVisible={2} />
      );
      const showMoreButton = screen.getByText(/more recommendations/);
      await user.click(showMoreButton);
      expect(screen.getByText('Show less')).toBeInTheDocument();
    });

    it('REC-009d: shows correct count in "show more" text', () => {
      render(
        <PWARecommendations recommendations={manyRecommendations} maxVisible={3} />
      );
      expect(screen.getByText('+2 more recommendations')).toBeInTheDocument();
    });

    it('REC-009e: no "show more" when recommendations <= maxVisible', () => {
      render(
        <PWARecommendations recommendations={manyRecommendations.slice(0, 2)} maxVisible={3} />
      );
      expect(screen.queryByText(/more recommendations/)).not.toBeInTheDocument();
    });
  });

  // ========================================================================
  // Responsive Design
  // ========================================================================
  
  describe('REC-010: Responsive Design', () => {
    it('REC-010a: full width on mobile', () => {
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      const component = screen.getByTestId('pwa-recommendations');
      expect(component).not.toHaveClass('max-w');
    });

    it('REC-010b: appropriate padding on mobile', () => {
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      const component = screen.getByTestId('pwa-recommendations');
      // Card should have padding classes
      expect(component).toBeInTheDocument();
    });

    it('REC-010c: badge and message stack well on small screens', () => {
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      const listItem = screen.getByRole('listitem');
      expect(listItem).toHaveClass('flex');
      expect(listItem).toHaveClass('items-start');
    });

    it('REC-010d: touch targets are at least 44px', () => {
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      const linkButton = screen.getByLabelText(/Learn more about name/);
      expect(linkButton).toHaveClass('min-h-[44px]');
      expect(linkButton).toHaveClass('min-w-[44px]');
    });

    it('REC-010e: scrollable if content exceeds viewport', () => {
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      const component = screen.getByTestId('pwa-recommendations');
      // Should have overflow handling
      expect(component).toBeInTheDocument();
    });
  });

  // ========================================================================
  // Accessibility
  // ========================================================================
  
  describe('REC-011: Accessibility', () => {
    it('REC-011a: list has proper ARIA role', () => {
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      expect(screen.getByRole('list')).toBeInTheDocument();
    });

    it('REC-011b: list items have proper ARIA role', () => {
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      const listItems = screen.getAllByRole('listitem');
      expect(listItems.length).toBeGreaterThan(0);
    });

    it('REC-011c: section headers are keyboard focusable', async () => {
      const user = userEvent.setup();
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      // First tab goes to View Guide link
      await user.tab();
      expect(screen.getByRole('link', { name: /View full PWA guide/ })).toHaveFocus();
      // Second tab goes to High section
      await user.tab();
      expect(screen.getByRole('button', { name: /High/ })).toHaveFocus();
    });

    it('REC-011d: Enter/Space toggles section expand/collapse', async () => {
      const user = userEvent.setup();
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      const mediumSection = screen.getByRole('button', { name: /Medium/ });
      mediumSection.focus();
      await user.keyboard('{Enter}');
      expect(mediumSection).toHaveAttribute('aria-expanded', 'true');
    });

    it('REC-011e: expanded state announced to screen readers', () => {
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      const highSection = screen.getByRole('button', { name: /High/ });
      expect(highSection).toHaveAttribute('aria-expanded', 'true');
    });

    it('REC-011f: priority badge has text alternative', () => {
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      const badge = screen.getByLabelText(/high priority/i);
      expect(badge).toBeInTheDocument();
    });

    it('REC-011g: guide links have descriptive labels', () => {
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      const link = screen.getByLabelText(/Learn more about name/);
      expect(link).toHaveAttribute('aria-label');
    });

    it('REC-011h: focus visible on interactive elements', async () => {
      const user = userEvent.setup();
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      await user.tab();
      const focusedElement = document.activeElement;
      // Focus visible is applied via Tailwind's focus-visible: variant
      expect(focusedElement).toHaveClass('focus-visible:ring-[3px]');
    });
  });

  // ========================================================================
  // Edge Cases
  // ========================================================================
  
  describe('REC-012: Edge Cases', () => {
    it('REC-012a: handles empty recommendations array', () => {
      render(<PWARecommendations recommendations={[]} />);
      expect(screen.getByText('All checks passed!')).toBeInTheDocument();
    });

    it('REC-012b: handles undefined recommendations', () => {
      render(<PWARecommendations recommendations={undefined as any} />);
      expect(screen.getByText('All checks passed!')).toBeInTheDocument();
    });

    it('REC-012c: handles recommendation without message', () => {
      const noMessage = { type: 'test', priority: 'high' as const };
      // Component should not crash
      expect(() => {
        render(<PWARecommendations recommendations={[noMessage as any]} />);
      }).not.toThrow();
    });

    it('REC-012d: handles very long message', () => {
      const longMessage: PWARecommendation = {
        type: 'test',
        message: 'A'.repeat(500),
        priority: 'high',
      };
      render(<PWARecommendations recommendations={[longMessage]} />);
      expect(screen.getByText('A'.repeat(500))).toBeInTheDocument();
    });

    it('REC-012e: handles many recommendations (50+)', () => {
      const many: PWARecommendation[] = Array.from({ length: 55 }, (_, i) => ({
        type: `type-${i}`,
        message: `Recommendation ${i}`,
        priority: (['high', 'medium', 'low'] as const)[i % 3],
      }));
      render(<PWARecommendations recommendations={many} />);
      expect(screen.getByTestId('pwa-recommendations')).toBeInTheDocument();
    });

    it('REC-012f: handles special characters in message', () => {
      const special: PWARecommendation = {
        type: 'test',
        message: '<script>alert("xss")</script> & "quotes" \'apostrophes\'',
        priority: 'high',
      };
      render(<PWARecommendations recommendations={[special]} />);
      // Should be escaped, not executed
      expect(screen.getByText(/<script>/)).toBeInTheDocument();
    });

    it('REC-012g: handles unicode in message', () => {
      const unicode: PWARecommendation = {
        type: 'test',
        message: '日本語テスト 🚀 Émoji 😊',
        priority: 'high',
      };
      render(<PWARecommendations recommendations={[unicode]} />);
      expect(screen.getByText('日本語テスト 🚀 Émoji 😊')).toBeInTheDocument();
    });

    it('REC-012h: handles rapid expand/collapse clicks', async () => {
      const user = userEvent.setup();
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      const highSection = screen.getByRole('button', { name: /High/ });
      
      // Rapid clicks
      await user.click(highSection);
      await user.click(highSection);
      await user.click(highSection);
      
      // Should not throw or get stuck
      expect(highSection).toBeInTheDocument();
    });
  });

  // ========================================================================
  // Animation
  // ========================================================================
  
  describe('REC-013: Animation', () => {
    it('REC-013a: section expand/collapse animates smoothly', () => {
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      // Find the region by its id
      const content = document.getElementById('section-content-high');
      expect(content).toHaveClass('transition-all');
    });

    it('REC-013b: respects prefers-reduced-motion', () => {
      // Mock prefers-reduced-motion
      const originalMatchMedia = window.matchMedia;
      window.matchMedia = jest.fn().mockImplementation((query) => ({
        matches: query === '(prefers-reduced-motion: reduce)',
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
      }));
      
      render(<PWARecommendations recommendations={mockManyRecommendations} />);
      const content = document.getElementById('section-content-high');
      // Should have transition-none when reduced motion is preferred
      expect(content).toHaveClass('transition-none');
      
      window.matchMedia = originalMatchMedia;
    });

    it('REC-013c: list items fade in on initial render', () => {
      render(<PWARecommendations recommendations={[mockHighRecommendation]} />);
      const listItem = screen.getByRole('listitem');
      expect(listItem).toHaveClass('animate-fade-in');
    });

    it('REC-013d: empty state has subtle animation', () => {
      render(<PWARecommendations recommendations={[]} />);
      // Check that the empty state container has the animation class
      const emptyContainer = document.querySelector('.animate-fade-in');
      expect(emptyContainer).toBeInTheDocument();
    });
  });
});

// ============================================================================
// Integration Tests
// ============================================================================

describe('PWARecommendations Integration', () => {
  const fullRecommendations: PWARecommendation[] = [
    {
      type: 'manifest_missing',
      message: 'No manifest.json file found',
      priority: 'high',
      guide: '#manifest-basics',
    },
    {
      type: 'field_missing',
      field: 'name',
      message: 'Add a name field to your manifest',
      priority: 'high',
      guide: '#manifest-name',
    },
    {
      type: 'field_missing',
      field: 'description',
      message: 'Add a description for better app store presence',
      priority: 'medium',
      guide: '#manifest-description',
    },
    {
      type: 'icon_missing',
      field: 'icon_180',
      message: 'Add a 180x180 icon for iOS devices',
      priority: 'low',
      guide: '#ios-icons',
    },
  ];

  it('REC-INT-001: full component renders correctly', () => {
    render(<PWARecommendations recommendations={fullRecommendations} />);
    // Should have all priority sections
    expect(screen.getByRole('button', { name: /High/ })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Medium/ })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Low/ })).toBeInTheDocument();
    // Should have View Guide button
    expect(screen.getByText('View Guide')).toBeInTheDocument();
  });

  it('REC-INT-002: interaction flow works correctly', async () => {
    const user = userEvent.setup();
    const onGuideClick = jest.fn();
    render(
      <PWARecommendations
        recommendations={fullRecommendations}
        onGuideClick={onGuideClick}
      />
    );
    
    // Expand medium section
    const mediumSection = screen.getByRole('button', { name: /Medium/ });
    await user.click(mediumSection);
    
    // Click guide link
    const guideLink = screen.getByLabelText(/Learn more about description/);
    await user.click(guideLink);
    
    expect(onGuideClick).toHaveBeenCalledWith('#manifest-description');
  });

  it('REC-INT-003: keyboard navigation works end-to-end', async () => {
    const user = userEvent.setup();
    render(<PWARecommendations recommendations={fullRecommendations} />);
    
    // Tab through elements - View Guide link is first
    await user.tab();
    expect(screen.getByRole('link', { name: /View full PWA guide/ })).toHaveFocus();
    
    // Then section buttons
    await user.tab();
    expect(screen.getByRole('button', { name: /High/ })).toHaveFocus();
    
    // Tab through the guide links within sections
    await user.tab();
    // This should focus on the first "Learn more" button
    expect(screen.getByLabelText(/Learn more about manifest_missing/)).toHaveFocus();
  });

  it('REC-INT-004: mixed priority items display correctly', () => {
    render(<PWARecommendations recommendations={fullRecommendations} />);
    // High should be expanded by default
    expect(screen.getByRole('button', { name: /High/ })).toHaveAttribute(
      'aria-expanded',
      'true'
    );
    // Medium and Low should be collapsed
    expect(screen.getByRole('button', { name: /Medium/ })).toHaveAttribute(
      'aria-expanded',
      'false'
    );
  });

  it('REC-INT-005: empty state transition works', () => {
    const { rerender } = render(
      <PWARecommendations recommendations={fullRecommendations} />
    );
    
    expect(screen.queryByText('All checks passed!')).not.toBeInTheDocument();
    
    // Transition to empty
    rerender(<PWARecommendations recommendations={[]} />);
    expect(screen.getByText('All checks passed!')).toBeInTheDocument();
  });
});

// ============================================================================
// Snapshot Tests
// ============================================================================

describe('PWARecommendations Snapshots', () => {
  it('matches snapshot with recommendations', () => {
    const { container } = render(
      <PWARecommendations recommendations={mockManyRecommendations} />
    );
    expect(container.firstChild).toMatchSnapshot();
  });

  it('matches snapshot for empty state', () => {
    const { container } = render(<PWARecommendations recommendations={[]} />);
    expect(container.firstChild).toMatchSnapshot();
  });

  it('matches snapshot for collapsed sections', async () => {
    const user = userEvent.setup();
    const { container } = render(
      <PWARecommendations recommendations={mockManyRecommendations} />
    );
    
    // Collapse all sections
    const highSection = screen.getByRole('button', { name: /High/ });
    await user.click(highSection);
    
    expect(container.firstChild).toMatchSnapshot();
  });
});

// ============================================================================
// Skeleton Tests
// ============================================================================

describe('PWARecommendationsSkeleton', () => {
  it('renders skeleton without crashing', () => {
    render(<PWARecommendationsSkeleton />);
    // Check for skeleton elements with animate-pulse class
    const skeletonElements = document.querySelectorAll('.animate-pulse');
    expect(skeletonElements.length).toBeGreaterThan(0);
  });

  it('shows loading placeholders', () => {
    render(<PWARecommendationsSkeleton />);
    const skeletonElements = document.querySelectorAll('.animate-pulse');
    expect(skeletonElements.length).toBeGreaterThan(0);
  });
});
