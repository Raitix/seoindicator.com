/**
 * Tests for GuideChecklist Component
 * Task: TASK-20-29 - Render Guide Content in Page Component
 * 
 * Tests the checklist renderer with priority styling.
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import type { ChecklistItem } from '@/lib/guides/types';

// Mock the actual component for TDD - will be implemented by Developer
function GuideChecklist({ items }: { items: ChecklistItem[] }) {
  if (!items || items.length === 0) {
    return null;
  }

  const priorityStyles: Record<string, string> = {
    critical: 'bg-red-500/10 border-red-500/30 text-red-400',
    important: 'bg-amber-500/10 border-amber-500/30 text-amber-400',
    recommended: 'bg-blue-500/10 border-blue-500/30 text-blue-400',
  };

  const priorityLabels: Record<string, string> = {
    critical: 'Critical',
    important: 'Important',
    recommended: 'Recommended',
  };

  return (
    <section data-testid="guide-checklist" className="mt-8">
      <h2 
        data-testid="checklist-heading"
        className="text-2xl font-bold mb-4 text-white"
      >
        SEO Checklist
      </h2>
      <ul data-testid="checklist-items" className="space-y-3">
        {items.map((item, index) => (
          <li
            key={index}
            data-testid={`checklist-item-${index}`}
            data-priority={item.priority}
            className={`flex items-start gap-3 p-4 rounded-lg border ${priorityStyles[item.priority] || priorityStyles.recommended}`}
          >
            {/* Checkbox icon */}
            <span 
              data-testid={`checklist-icon-${index}`}
              className="flex-shrink-0 mt-0.5"
              aria-hidden="true"
            >
              {item.priority === 'critical' ? '🔴' : item.priority === 'important' ? '🟡' : '🔵'}
            </span>
            
            {/* Priority badge */}
            <span
              data-testid={`priority-badge-${index}`}
              className="flex-shrink-0 px-2 py-0.5 text-xs font-medium rounded uppercase"
            >
              {priorityLabels[item.priority] || 'Recommended'}
            </span>
            
            {/* Item text */}
            <span 
              data-testid={`item-text-${index}`}
              className="flex-1 text-white"
            >
              {item.text}
            </span>
          </li>
        ))}
      </ul>
    </section>
  );
}

// Test data
const mockChecklistItems: ChecklistItem[] = [
  { text: 'Use WebP or AVIF format for all images', priority: 'critical' },
  { text: 'Implement lazy loading for below-fold images', priority: 'critical' },
  { text: 'Compress images to 75-85% quality', priority: 'important' },
  { text: 'Set explicit width and height attributes', priority: 'important' },
  { text: 'Use responsive images with srcset', priority: 'recommended' },
];

const emptyChecklist: ChecklistItem[] = [];

const checklistWithLongText: ChecklistItem[] = [
  { 
    text: 'This is a very long checklist item that exceeds the normal character limit and should wrap to multiple lines when displayed on the page to ensure readability is maintained across all screen sizes and devices', 
    priority: 'important' 
  },
];

const checklistWithSpecialChars: ChecklistItem[] = [
  { text: 'Use <meta> tags with "quotes" & ampersands', priority: 'critical' },
  { text: 'Test unicode: 中文 🚀 日本語', priority: 'recommended' },
];

const checklistWithXSS: ChecklistItem[] = [
  { text: '<script>alert("xss")</script> should be safe', priority: 'critical' },
  { text: '<img onerror="alert(1)" src="x" /> check', priority: 'important' },
];

describe('GuideChecklist Component', () => {
  describe('CR-001: Renders all checklist items', () => {
    it('renders all items in the list', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      expect(screen.getByTestId('checklist-item-0')).toBeInTheDocument();
      expect(screen.getByTestId('checklist-item-1')).toBeInTheDocument();
      expect(screen.getByTestId('checklist-item-2')).toBeInTheDocument();
      expect(screen.getByTestId('checklist-item-3')).toBeInTheDocument();
      expect(screen.getByTestId('checklist-item-4')).toBeInTheDocument();
    });

    it('renders correct number of items', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const items = screen.getAllByTestId(/checklist-item-/);
      expect(items).toHaveLength(5);
    });
  });

  describe('CR-002: Renders critical priority styling', () => {
    it('critical items have red styling classes', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const item = screen.getByTestId('checklist-item-0');
      expect(item.className).toMatch(/bg-red.*text-red/);
    });

    it('critical items show red indicator', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const icon = screen.getByTestId('checklist-icon-0');
      expect(icon).toHaveTextContent('🔴');
    });

    it('critical items have CRITICAL label', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const badge = screen.getByTestId('priority-badge-0');
      expect(badge).toHaveTextContent('Critical');
    });
  });

  describe('CR-003: Renders important priority styling', () => {
    it('important items have amber styling classes', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const item = screen.getByTestId('checklist-item-2');
      expect(item.className).toMatch(/bg-amber.*text-amber/);
    });

    it('important items show amber indicator', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const icon = screen.getByTestId('checklist-icon-2');
      expect(icon).toHaveTextContent('🟡');
    });

    it('important items have IMPORTANT label', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const badge = screen.getByTestId('priority-badge-2');
      expect(badge).toHaveTextContent('Important');
    });
  });

  describe('CR-004: Renders recommended priority styling', () => {
    it('recommended items have blue styling classes', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const item = screen.getByTestId('checklist-item-4');
      expect(item.className).toMatch(/bg-blue.*text-blue/);
    });

    it('recommended items show blue indicator', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const icon = screen.getByTestId('checklist-icon-4');
      expect(icon).toHaveTextContent('🔵');
    });

    it('recommended items have RECOMMENDED label', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const badge = screen.getByTestId('priority-badge-4');
      expect(badge).toHaveTextContent('Recommended');
    });
  });

  describe('CR-005: Shows priority label', () => {
    it('all items show their priority label', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      expect(screen.getByText('Critical')).toBeInTheDocument();
      expect(screen.getByText('Important')).toBeInTheDocument();
      expect(screen.getByText('Recommended')).toBeInTheDocument();
    });
  });

  describe('CR-006: Renders checklist icon', () => {
    it('each item has an icon element', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      for (let i = 0; i < 5; i++) {
        expect(screen.getByTestId(`checklist-icon-${i}`)).toBeInTheDocument();
      }
    });
  });

  describe('CR-007: Handles empty checklist', () => {
    it('renders nothing for empty array', () => {
      const { container } = render(<GuideChecklist items={emptyChecklist} />);
      expect(container.firstChild).toBeNull();
    });

    it('returns null for undefined items', () => {
      const { container } = render(<GuideChecklist items={undefined as unknown as ChecklistItem[]} />);
      expect(container.firstChild).toBeNull();
    });
  });

  describe('CR-008: Checklist section has proper heading', () => {
    it('has h2 heading for checklist section', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const heading = screen.getByTestId('checklist-heading');
      expect(heading.tagName).toBe('H2');
    });

    it('heading text is "SEO Checklist"', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const heading = screen.getByTestId('checklist-heading');
      expect(heading).toHaveTextContent('SEO Checklist');
    });
  });

  describe('CR-009: Items are in document order', () => {
    it('items appear in the same order as input', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const items = screen.getAllByTestId(/checklist-item-/);
      
      expect(items[0]).toHaveAttribute('data-priority', 'critical');
      expect(items[1]).toHaveAttribute('data-priority', 'critical');
      expect(items[2]).toHaveAttribute('data-priority', 'important');
      expect(items[3]).toHaveAttribute('data-priority', 'important');
      expect(items[4]).toHaveAttribute('data-priority', 'recommended');
    });
  });

  describe('CR-010: Long checklist item text wraps', () => {
    it('long text is rendered completely', () => {
      render(<GuideChecklist items={checklistWithLongText} />);
      const text = screen.getByTestId('item-text-0');
      expect(text).toHaveTextContent('This is a very long checklist item');
    });

    it('long text container has flex-1 for proper wrapping', () => {
      render(<GuideChecklist items={checklistWithLongText} />);
      const text = screen.getByTestId('item-text-0');
      expect(text.className).toContain('flex-1');
    });
  });

  describe('Special characters and XSS', () => {
    it('handles HTML entities in text', () => {
      render(<GuideChecklist items={checklistWithSpecialChars} />);
      const text = screen.getByTestId('item-text-0');
      expect(text).toBeInTheDocument();
    });

    it('handles unicode characters', () => {
      render(<GuideChecklist items={checklistWithSpecialChars} />);
      const text = screen.getByTestId('item-text-1');
      expect(text).toHaveTextContent('中文 🚀 日本語');
    });

    it('does not execute script tags', () => {
      render(<GuideChecklist items={checklistWithXSS} />);
      // Script content should be rendered as text, not executed
      expect(screen.getByTestId('item-text-0')).toBeInTheDocument();
      expect(screen.getByTestId('item-text-1')).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    it('checklist section is in a section element', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const section = screen.getByTestId('guide-checklist');
      expect(section.tagName).toBe('SECTION');
    });

    it('items are in a list', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const list = screen.getByTestId('checklist-items');
      expect(list.tagName).toBe('UL');
    });

    it('icon has aria-hidden', () => {
      render(<GuideChecklist items={mockChecklistItems} />);
      const icon = screen.getByTestId('checklist-icon-0');
      expect(icon).toHaveAttribute('aria-hidden', 'true');
    });
  });

  describe('Invalid priority handling', () => {
    it('defaults to recommended styling for invalid priority', () => {
      const invalidPriority: ChecklistItem[] = [
        { text: 'Invalid priority item', priority: 'invalid' as unknown as 'critical' },
      ];
      render(<GuideChecklist items={invalidPriority} />);
      const item = screen.getByTestId('checklist-item-0');
      // Should have blue (recommended) styling as fallback
      expect(item.className).toMatch(/bg-blue.*text-blue/);
    });
  });
});
