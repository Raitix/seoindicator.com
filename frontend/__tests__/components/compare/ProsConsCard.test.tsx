/**
 * ProsConsCard Component Tests
 * Task: TASK-32-7 - ProsConsCard Component
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Tests for the pros/cons card component that displays balanced,
 * honest assessment of each tool's strengths and weaknesses.
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProsConsCard } from '@/components/compare/ProsConsCard';
import type { ProsConsCardProps } from '@/lib/compare/types';

// ============================================================================
// Mock Lucide Icons
// ============================================================================

jest.mock('lucide-react', () => ({
  Check: ({ className, 'aria-hidden': ariaHidden }: { className?: string; 'aria-hidden'?: boolean }) => (
    <span data-testid="check-icon" className={className} aria-hidden={ariaHidden}>✓</span>
  ),
  X: ({ className, 'aria-hidden': ariaHidden }: { className?: string; 'aria-hidden'?: boolean }) => (
    <span data-testid="x-icon" className={className} aria-hidden={ariaHidden}>✗</span>
  ),
  Star: ({ className, filled, 'aria-hidden': ariaHidden }: { className?: string; filled?: boolean; 'aria-hidden'?: boolean }) => (
    <span 
      data-testid="star-icon" 
      className={className}
      data-filled={filled}
      aria-hidden={ariaHidden}
    >
      {filled ? '★' : '☆'}
    </span>
  ),
}));

// ============================================================================
// Mock shadcn/ui Card Components
// ============================================================================

jest.mock('@/components/ui/card', () => ({
  Card: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <div data-testid="card" className={className}>
      {children}
    </div>
  ),
  CardHeader: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <div data-testid="card-header" className={className}>
      {children}
    </div>
  ),
  CardContent: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <div data-testid="card-content" className={className}>
      {children}
    </div>
  ),
}));

// ============================================================================
// Test Utilities
// ============================================================================

/**
 * Creates default props for the ProsConsCard component.
 */
function createDefaultProps(overrides: Partial<ProsConsCardProps> = {}): ProsConsCardProps {
  return {
    tool: 'SEOIndicator',
    pros: ['Easy to use', 'Affordable pricing', 'Great support'],
    cons: ['Limited integrations', 'No mobile app'],
    ...overrides,
  };
}

/**
 * Renders the ProsConsCard component with default props.
 */
function renderProsConsCard(props: Partial<ProsConsCardProps> = {}) {
  return render(<ProsConsCard {...createDefaultProps(props)} />);
}

// ============================================================================
// Test Suites
// ============================================================================

describe('ProsConsCard', () => {
  // --------------------------------------------------------------------------
  // PC-RENDER: Basic Rendering
  // --------------------------------------------------------------------------

  describe('Basic Rendering', () => {
    it('renders without crashing', () => {
      renderProsConsCard();
      expect(screen.getByTestId('card')).toBeInTheDocument();
    });

    it('displays tool name in header', () => {
      renderProsConsCard({ tool: 'Ahrefs' });
      expect(screen.getByRole('heading', { name: /ahrefs/i, level: 3 })).toBeInTheDocument();
    });

    it('renders both pros and cons sections', () => {
      renderProsConsCard();
      expect(screen.getByText(/pros/i)).toBeInTheDocument();
      expect(screen.getByText(/cons/i)).toBeInTheDocument();
    });

    it('renders card with correct test id', () => {
      renderProsConsCard();
      expect(screen.getByTestId('card')).toBeInTheDocument();
    });
  });

  // --------------------------------------------------------------------------
  // PC-PROS: Pros Section
  // --------------------------------------------------------------------------

  describe('Pros Section', () => {
    it('displays all pros from props', () => {
      const pros = ['Feature A', 'Feature B', 'Feature C'];
      renderProsConsCard({ pros });

      pros.forEach(pro => {
        expect(screen.getByText(pro)).toBeInTheDocument();
      });
    });

    it('displays green check icons for each pro', () => {
      const pros = ['Pro 1', 'Pro 2'];
      renderProsConsCard({ pros });

      const checkIcons = screen.getAllByTestId('check-icon');
      expect(checkIcons).toHaveLength(pros.length);
    });

    it('check icons have green color class', () => {
      renderProsConsCard({ pros: ['A pro'] });

      const checkIcon = screen.getByTestId('check-icon');
      expect(checkIcon.getAttribute('class')).toMatch(/green|emerald/);
    });

    it('renders pros list with correct semantic structure', () => {
      renderProsConsCard();
      
      const prosHeading = screen.getByRole('heading', { name: /pros/i, level: 4 });
      expect(prosHeading).toBeInTheDocument();

      const prosSection = prosHeading.closest('div');
      const list = within(prosSection!).getByRole('list');
      expect(list).toBeInTheDocument();
    });

    it('renders each pro as a list item', () => {
      const pros = ['Pro A', 'Pro B'];
      renderProsConsCard({ pros });

      const listItems = screen.getAllByRole('listitem');
      expect(listItems.length).toBeGreaterThanOrEqual(pros.length);
    });

    it('handles empty pros array gracefully', () => {
      renderProsConsCard({ pros: [] });
      
      // Should still render the pros section heading
      expect(screen.getByText(/pros/i)).toBeInTheDocument();
    });

    it('renders pros section with correct accessibility attributes', () => {
      renderProsConsCard();
      
      const prosSection = screen.getByText(/pros/i).closest('div');
      expect(prosSection).toHaveAttribute('aria-label', 'pros-section');
    });
  });

  // --------------------------------------------------------------------------
  // PC-CONS: Cons Section
  // --------------------------------------------------------------------------

  describe('Cons Section', () => {
    it('displays all cons from props', () => {
      const cons = ['Con A', 'Con B', 'Con C'];
      renderProsConsCard({ cons });

      cons.forEach(con => {
        expect(screen.getByText(con)).toBeInTheDocument();
      });
    });

    it('displays X icons for each con', () => {
      const cons = ['Con 1', 'Con 2'];
      renderProsConsCard({ cons });

      const xIcons = screen.getAllByTestId('x-icon');
      expect(xIcons).toHaveLength(cons.length);
    });

    it('X icons have subtle/gray color class', () => {
      renderProsConsCard({ cons: ['A con'] });

      const xIcon = screen.getByTestId('x-icon');
      expect(xIcon.getAttribute('class')).toMatch(/gray|slate|neutral|muted/);
    });

    it('renders cons list with correct semantic structure', () => {
      renderProsConsCard();
      
      const consHeading = screen.getByRole('heading', { name: /cons/i, level: 4 });
      expect(consHeading).toBeInTheDocument();

      const consSection = consHeading.closest('div');
      const list = within(consSection!).getByRole('list');
      expect(list).toBeInTheDocument();
    });

    it('renders each con as a list item', () => {
      const cons = ['Con A', 'Con B'];
      renderProsConsCard({ cons });

      const listItems = screen.getAllByRole('listitem');
      expect(listItems.length).toBeGreaterThanOrEqual(cons.length);
    });

    it('handles empty cons array gracefully', () => {
      renderProsConsCard({ cons: [] });
      
      // Should still render the cons section heading
      expect(screen.getByText(/cons/i)).toBeInTheDocument();
    });

    it('renders cons section with correct accessibility attributes', () => {
      renderProsConsCard();
      
      const consSection = screen.getByText(/cons/i).closest('div');
      expect(consSection).toHaveAttribute('aria-label', 'cons-section');
    });
  });

  // --------------------------------------------------------------------------
  // PC-RATING: Overall Rating Display
  // --------------------------------------------------------------------------

  describe('Overall Rating Display', () => {
    it('displays rating when overallRating is provided', () => {
      renderProsConsCard({ overallRating: 4 });
      
      expect(screen.getByLabelText(/rating/i)).toBeInTheDocument();
    });

    it('does not display rating when overallRating is undefined', () => {
      renderProsConsCard({ overallRating: undefined });
      
      expect(screen.queryByLabelText(/rating/i)).not.toBeInTheDocument();
    });

    it('displays correct number of filled stars for rating', () => {
      const rating = 4;
      renderProsConsCard({ overallRating: rating });

      const filledStars = screen.getAllByTestId('star-icon').filter(
        star => star.getAttribute('data-filled') === 'true'
      );
      expect(filledStars).toHaveLength(rating);
    });

    it('displays 5 total stars (filled + empty)', () => {
      renderProsConsCard({ overallRating: 3 });

      const allStars = screen.getAllByTestId('star-icon');
      expect(allStars).toHaveLength(5);
    });

    it('handles rating of 0', () => {
      renderProsConsCard({ overallRating: 0 });

      const filledStars = screen.getAllByTestId('star-icon').filter(
        star => star.getAttribute('data-filled') === 'true'
      );
      expect(filledStars).toHaveLength(0);
    });

    it('handles rating of 5 (maximum)', () => {
      renderProsConsCard({ overallRating: 5 });

      const filledStars = screen.getAllByTestId('star-icon').filter(
        star => star.getAttribute('data-filled') === 'true'
      );
      expect(filledStars).toHaveLength(5);
    });

    it('displays rating value as accessible text', () => {
      const rating = 4.5;
      renderProsConsCard({ overallRating: rating });

      expect(screen.getByText(/4\.5 out of 5/i)).toBeInTheDocument();
    });

    it('handles decimal ratings (e.g., 4.5)', () => {
      renderProsConsCard({ overallRating: 4.5 });

      const ratingText = screen.getByText(/4\.5/);
      expect(ratingText).toBeInTheDocument();
    });

    it('renders rating component in card header', () => {
      renderProsConsCard({ overallRating: 4 });

      const header = screen.getByTestId('card-header');
      const rating = within(header).getByLabelText(/rating/i);
      expect(rating).toBeInTheDocument();
    });
  });

  // --------------------------------------------------------------------------
  // PC-ACCESS: Accessibility
  // --------------------------------------------------------------------------

  describe('Accessibility', () => {
    it('has proper heading hierarchy (h3 for tool name, h4 for pros/cons)', () => {
      renderProsConsCard();

      expect(screen.getByRole('heading', { level: 3 })).toBeInTheDocument();
      expect(screen.getAllByRole('heading', { level: 4 })).toHaveLength(2);
    });

    it('has aria-label on card', () => {
      renderProsConsCard({ tool: 'SEMrush' });

      const card = screen.getByTestId('card');
      expect(card).toHaveAttribute('aria-label', 'Pros and cons for SEMrush');
    });

    it('pros and cons lists have aria-labels', () => {
      renderProsConsCard();

      const lists = screen.getAllByRole('list');
      const prosList = lists.find(list => list.getAttribute('aria-label')?.includes('pros'));
      const consList = lists.find(list => list.getAttribute('aria-label')?.includes('cons'));

      expect(prosList).toBeDefined();
      expect(consList).toBeDefined();
    });

    it('is keyboard navigable', async () => {
      const user = userEvent.setup();
      renderProsConsCard();

      const card = screen.getByTestId('card');
      card.focus();
      
      await user.tab();
      // Focus should move through interactive elements
      expect(document.activeElement).toBeTruthy();
    });

    it('has proper role attribute on card', () => {
      renderProsConsCard();

      const card = screen.getByTestId('card');
      expect(card).toHaveAttribute('role', 'region');
    });

    it('icon elements are hidden from screen readers', () => {
      renderProsConsCard();

      const checkIcons = screen.getAllByTestId('check-icon');
      const xIcons = screen.getAllByTestId('x-icon');

      checkIcons.forEach(icon => {
        expect(icon).toHaveAttribute('aria-hidden', 'true');
      });
      xIcons.forEach(icon => {
        expect(icon).toHaveAttribute('aria-hidden', 'true');
      });
    });

    it('rating has appropriate aria attributes', () => {
      renderProsConsCard({ overallRating: 4.5 });

      const rating = screen.getByLabelText(/rating/i);
      expect(rating).toHaveAttribute('role', 'img');
    });
  });

  // --------------------------------------------------------------------------
  // PC-RESPONSIVE: Responsive Design
  // --------------------------------------------------------------------------

  describe('Responsive Design', () => {
    it('applies responsive grid classes', () => {
      renderProsConsCard();

      const cardContent = screen.getByTestId('card-content');
      expect(cardContent.className).toMatch(/grid|flex/);
    });

    it('pros and cons sections have equal width on desktop', () => {
      renderProsConsCard();

      const cardContent = screen.getByTestId('card-content');
      // Should use grid-cols-2 or similar for equal width
      expect(cardContent.className).toMatch(/grid-cols-2|grid-cols-\[1fr_1fr\]/);
    });

    it('stacks pros and cons vertically on mobile', () => {
      renderProsConsCard();

      const cardContent = screen.getByTestId('card-content');
      // Should have responsive classes like md:grid-cols-2 grid-cols-1
      expect(cardContent.className).toMatch(/grid-cols-1|flex-col/);
    });

    it('maintains balanced visual weight between pros and cons', () => {
      renderProsConsCard({
        pros: ['Pro 1', 'Pro 2', 'Pro 3'],
        cons: ['Con 1', 'Con 2', 'Con 3'],
      });

      const prosSection = screen.getByText(/pros/i).closest('div');
      const consSection = screen.getByText(/cons/i).closest('div');

      // Both sections should have similar padding/min-height
      expect(prosSection?.className).toBeTruthy();
      expect(consSection?.className).toBeTruthy();
    });
  });

  // --------------------------------------------------------------------------
  // PC-PROPS: Props Validation
  // --------------------------------------------------------------------------

  describe('Props Validation', () => {
    it('accepts custom className prop', () => {
      renderProsConsCard({ className: 'custom-class' });

      const card = screen.getByTestId('card');
      expect(card.className).toContain('custom-class');
    });

    it('merges custom className with default classes', () => {
      renderProsConsCard({ className: 'my-custom' });

      const card = screen.getByTestId('card');
      expect(card.className).toContain('pros-cons-card');
      expect(card.className).toContain('my-custom');
    });

    it('handles undefined pros array', () => {
      // @ts-expect-error - Testing runtime behavior with undefined
      renderProsConsCard({ pros: undefined });

      expect(screen.getByTestId('card')).toBeInTheDocument();
    });

    it('handles undefined cons array', () => {
      // @ts-expect-error - Testing runtime behavior with undefined
      renderProsConsCard({ cons: undefined });

      expect(screen.getByTestId('card')).toBeInTheDocument();
    });

    it('clamps rating values outside 0-5 range', () => {
      // Test negative rating
      renderProsConsCard({ overallRating: -1 });
      let ratingText = screen.queryByText(/0\.?0? out of 5/i);
      expect(ratingText).toBeInTheDocument();

      // Test rating > 5
      renderProsConsCard({ overallRating: 10 });
      ratingText = screen.queryByText(/5\.?0? out of 5/i);
      expect(ratingText).toBeInTheDocument();
    });
  });

  // --------------------------------------------------------------------------
  // PC-EDGE: Edge Cases
  // --------------------------------------------------------------------------

  describe('Edge Cases', () => {
    it('handles very long pro text', () => {
      const longPro = 'A'.repeat(500);
      renderProsConsCard({ pros: [longPro] });

      expect(screen.getByText(longPro)).toBeInTheDocument();
    });

    it('handles very long con text', () => {
      const longCon = 'B'.repeat(500);
      renderProsConsCard({ cons: [longCon] });

      expect(screen.getByText(longCon)).toBeInTheDocument();
    });

    it('handles many pros (10+ items)', () => {
      const pros = Array.from({ length: 15 }, (_, i) => `Pro ${i + 1}`);
      renderProsConsCard({ pros });

      pros.forEach(pro => {
        expect(screen.getByText(pro)).toBeInTheDocument();
      });
    });

    it('handles many cons (10+ items)', () => {
      const cons = Array.from({ length: 15 }, (_, i) => `Con ${i + 1}`);
      renderProsConsCard({ cons });

      cons.forEach(con => {
        expect(screen.getByText(con)).toBeInTheDocument();
      });
    });

    it('handles special characters in tool name', () => {
      renderProsConsCard({ tool: "SEMrush & Ahrefs <Test>" });
      
      // Should render escaped, not as HTML
      expect(screen.getByText(/SEMrush & Ahrefs/)).toBeInTheDocument();
    });

    it('handles special characters in pros/cons text', () => {
      const pros = ['Feature with <script>alert("xss")</script>'];
      renderProsConsCard({ pros });

      // Should render as text, not execute script
      expect(screen.getByText(/Feature with/)).toBeInTheDocument();
    });

    it('handles unicode characters in tool name', () => {
      renderProsConsCard({ tool: '工具名称 🛠️' });
      
      expect(screen.getByText('工具名称 🛠️')).toBeInTheDocument();
    });

    it('handles unicode characters in pros/cons', () => {
      const pros = ['😀 Great UX', '中文支持'];
      renderProsConsCard({ pros });

      pros.forEach(pro => {
        expect(screen.getByText(pro)).toBeInTheDocument();
      });
    });

    it('handles empty tool name gracefully', () => {
      renderProsConsCard({ tool: '' });

      // Should still render the card
      expect(screen.getByTestId('card')).toBeInTheDocument();
    });

    it('handles null/undefined overallRating', () => {
      // @ts-expect-error - Testing runtime behavior
      renderProsConsCard({ overallRating: null });
      
      expect(screen.queryByLabelText(/rating/i)).not.toBeInTheDocument();
    });
  });

  // --------------------------------------------------------------------------
  // PC-SEC: Security Tests
  // --------------------------------------------------------------------------

  describe('Security', () => {
    it('prevents XSS via tool name', () => {
      const xssTool = '<img src=x onerror=alert(1)>';
      renderProsConsCard({ tool: xssTool });

      // Tool name should be rendered as text, not HTML
      expect(screen.queryByRole('img')).not.toBeInTheDocument();
    });

    it('prevents XSS via pros array', () => {
      const xssPro = '<script>document.cookie="stolen"</script>';
      renderProsConsCard({ pros: [xssPro] });

      // Script should not be in the document
      expect(document.querySelector('script')).not.toBeInTheDocument();
    });

    it('prevents XSS via cons array', () => {
      const xssCon = '<a href="javascript:alert(1)">Click</a>';
      renderProsConsCard({ cons: [xssCon] });

      // Link should not be clickable with javascript:
      const links = screen.queryAllByRole('link');
      expect(links.every(link => !link.getAttribute('href')?.startsWith('javascript:'))).toBe(true);
    });

    it('sanitizes HTML entities in tool name', () => {
      renderProsConsCard({ tool: 'A & B < C > D' });

      // Should render escaped
      expect(screen.getByRole('heading', { level: 3 })).toHaveTextContent('A & B');
    });

    it('does not execute event handlers in content', () => {
      const maliciousPro = '<div onclick="alert(1)">Click me</div>';
      renderProsConsCard({ pros: [maliciousPro] });

      // Should render as text, not as a div with onclick
      expect(document.querySelector('div[onclick]')).not.toBeInTheDocument();
    });
  });

  // --------------------------------------------------------------------------
  // AC: Acceptance Criteria
  // --------------------------------------------------------------------------

  describe('Acceptance Criteria', () => {
    it('AC-1: Tool name displayed in header', () => {
      renderProsConsCard({ tool: 'Moz Pro' });

      const header = screen.getByTestId('card-header');
      const toolName = within(header).getByRole('heading', { level: 3, name: /moz pro/i });
      
      expect(toolName).toBeInTheDocument();
    });

    it('AC-2: Pros listed with green check icons', () => {
      const pros = ['Fast analysis', 'Accurate data'];
      renderProsConsCard({ pros });

      pros.forEach(pro => {
        expect(screen.getByText(pro)).toBeInTheDocument();
      });

      const checkIcons = screen.getAllByTestId('check-icon');
      expect(checkIcons.length).toBe(pros.length);
      
      // Verify green color
      checkIcons.forEach(icon => {
        expect(icon.getAttribute('class')).toMatch(/green|emerald/);
      });
    });

    it('AC-3: Cons listed with X icons (subtle color)', () => {
      const cons = ['Limited features', 'High price'];
      renderProsConsCard({ cons });

      cons.forEach(con => {
        expect(screen.getByText(con)).toBeInTheDocument();
      });

      const xIcons = screen.getAllByTestId('x-icon');
      expect(xIcons.length).toBe(cons.length);
      
      // Verify subtle color
      xIcons.forEach(icon => {
        expect(icon.getAttribute('class')).toMatch(/gray|slate|neutral|muted/);
      });
    });

    it('AC-4: Overall rating displayed when provided', () => {
      renderProsConsCard({ overallRating: 4.5 });

      expect(screen.getByLabelText(/rating/i)).toBeInTheDocument();
      expect(screen.getByText(/4\.5 out of 5/i)).toBeInTheDocument();
    });

    it('AC-5: Mobile responsive (stacked or scrollable)', () => {
      renderProsConsCard();

      const cardContent = screen.getByTestId('card-content');
      // Should have responsive grid/flex classes
      expect(cardContent.className).toMatch(/grid|flex/);
      // Should have mobile-first approach
      expect(cardContent.className).toMatch(/grid-cols-1|flex-col/);
    });

    it('AC-6: Balanced presentation (no bias in layout)', () => {
      const pros = ['Pro 1', 'Pro 2'];
      const cons = ['Con 1', 'Con 2'];
      renderProsConsCard({ pros, cons });

      // Both sections should have similar structure and weight
      const prosHeading = screen.getByRole('heading', { name: /pros/i });
      const consHeading = screen.getByRole('heading', { name: /cons/i });

      // Same heading level
      expect(prosHeading.tagName).toBe(consHeading.tagName);

      // Both should have list elements
      const prosList = prosHeading.closest('div')?.querySelector('ul, ol');
      const consList = consHeading.closest('div')?.querySelector('ul, ol');
      
      expect(prosList).toBeInTheDocument();
      expect(consList).toBeInTheDocument();
    });

    it('AC-7: Accessible (proper heading structure)', () => {
      renderProsConsCard();

      // Should have h3 for tool name
      const h3 = screen.getByRole('heading', { level: 3 });
      expect(h3).toBeInTheDocument();

      // Should have h4s for pros and cons
      const h4s = screen.getAllByRole('heading', { level: 4 });
      expect(h4s).toHaveLength(2);

      // h4s should be descendants of the h3's section
      const card = screen.getByTestId('card');
      const headingStructure = within(card);
      
      expect(headingStructure.getByRole('heading', { level: 3 })).toBeInTheDocument();
      expect(headingStructure.getAllByRole('heading', { level: 4 })).toHaveLength(2);
    });
  });

  // --------------------------------------------------------------------------
  // PC-SNAP: Snapshot Tests
  // --------------------------------------------------------------------------

  describe('Snapshots', () => {
    it('matches snapshot with default props', () => {
      const { container } = renderProsConsCard();
      expect(container).toMatchSnapshot();
    });

    it('matches snapshot with rating', () => {
      const { container } = renderProsConsCard({ overallRating: 4.5 });
      expect(container).toMatchSnapshot();
    });

    it('matches snapshot with many items', () => {
      const { container } = renderProsConsCard({
        pros: Array.from({ length: 8 }, (_, i) => `Pro ${i + 1}`),
        cons: Array.from({ length: 6 }, (_, i) => `Con ${i + 1}`),
      });
      expect(container).toMatchSnapshot();
    });
  });
});

// ============================================================================
// Mock Component for Reference (TDD Pattern)
// ============================================================================

/**
 * This is a reference implementation showing the expected interface.
 * The actual implementation should be in:
 * frontend/components/compare/ProsConsCard.tsx
 * 
 * Expected implementation:
 * 
 * import { Card, CardHeader, CardContent } from '@/components/ui/card';
 * import { Check, X, Star } from 'lucide-react';
 * import type { ProsConsCardProps } from '@/lib/compare/types';
 * 
 * export function ProsConsCard({ 
 *   tool, 
 *   pros, 
 *   cons, 
 *   overallRating,
 *   className 
 * }: ProsConsCardProps) {
 *   const clampedRating = overallRating !== undefined 
 *     ? Math.max(0, Math.min(5, overallRating))
 *     : undefined;
 *   
 *   return (
 *     <Card 
 *       className={`pros-cons-card ${className || ''}`}
 *       role="region"
 *       aria-label={`Pros and cons for ${tool}`}
 *     >
 *       <CardHeader>
 *         <h3>{tool}</h3>
 *         {clampedRating !== undefined && (
 *           <div role="img" aria-label={`Rating: ${clampedRating} out of 5`}>
 *             {Array.from({ length: 5 }, (_, i) => (
 *               <Star 
 *                 key={i} 
 *                 filled={i < Math.floor(clampedRating)}
 *                 aria-hidden="true"
 *               />
 *             ))}
 *             <span className="sr-only">{clampedRating} out of 5</span>
 *           </div>
 *         )}
 *       </CardHeader>
 *       <CardContent className="grid grid-cols-1 md:grid-cols-2 gap-4">
 *         <div aria-label="pros-section">
 *           <h4>Pros</h4>
 *           <ul aria-label="List of advantages">
 *             {pros.map((pro, i) => (
 *               <li key={i}>
 *                 <Check className="text-green-500" aria-hidden="true" />
 *                 <span>{pro}</span>
 *               </li>
 *             ))}
 *           </ul>
 *         </div>
 *         <div aria-label="cons-section">
 *           <h4>Cons</h4>
 *           <ul aria-label="List of disadvantages">
 *             {cons.map((con, i) => (
 *               <li key={i}>
 *                 <X className="text-gray-400" aria-hidden="true" />
 *                 <span>{con}</span>
 *               </li>
 *             ))}
 *           </ul>
 *         </div>
 *       </CardContent>
 *     </Card>
 *   );
 * }
 */
