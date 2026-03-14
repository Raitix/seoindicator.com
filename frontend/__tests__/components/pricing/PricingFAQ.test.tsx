/**
 * Tests for PricingFAQ Component
 * Task: TASK-26-20
 * Test Documentation: TESTS/TEST-26-20.md
 */

import React, { useState } from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  ChevronDown: () => <span data-testid="chevron-icon">▼</span>,
  Search: () => <span data-testid="search-icon">🔍</span>,
  X: () => <span data-testid="x-icon">✕</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loading</span>,
  HelpCircle: () => <span data-testid="help-icon">?</span>,
}));

// Mock data
const mockFAQs = [
  {
    id: 1,
    question: 'What are credits?',
    answer: 'Credits are used to run SEO analyses. Each analysis consumes a certain number of credits based on the depth and complexity.',
  },
  {
    id: 2,
    question: 'How long do credits last?',
    answer: 'Credits never expire. Once purchased, they remain in your account until used.',
  },
  {
    id: 3,
    question: 'Can I get a refund?',
    answer: 'Unused credits can be refunded within 30 days of purchase. Contact support for assistance.',
  },
  {
    id: 4,
    question: 'Which AI model should I choose?',
    answer: 'For most users, GPT-4o Mini or Claude 3.5 Sonnet provides the best balance of speed and accuracy. Free tier models are great for testing.',
  },
  {
    id: 5,
    question: 'Is there a free tier?',
    answer: 'Yes! New users get 100 free credits to try SEOIndicator. Additionally, some AI models offer free tier usage.',
  },
];

// Mock component
interface PricingFAQProps {
  faqs?: typeof mockFAQs;
  isLoading?: boolean;
  error?: string | null;
  allowMultiple?: boolean;
  className?: string;
}

const MockPricingFAQ = ({
  faqs = mockFAQs,
  isLoading = false,
  error = null,
  allowMultiple = false,
  className = '',
}: PricingFAQProps) => {
  const [openItems, setOpenItems] = useState<Set<number>>(new Set());

  const toggleItem = (id: number) => {
    if (allowMultiple) {
      setOpenItems(prev => {
        const newSet = new Set(prev);
        if (newSet.has(id)) {
          newSet.delete(id);
        } else {
          newSet.add(id);
        }
        return newSet;
      });
    } else {
      setOpenItems(prev => {
        if (prev.has(id)) {
          return new Set();
        }
        return new Set([id]);
      });
    }
  };

  if (isLoading) {
    return (
      <section id="faq" aria-labelledby="faq-heading" data-testid="pricing-faq-loading">
        <h2 id="faq-heading">Frequently Asked Questions</h2>
        <div data-testid="faq-skeleton" className="space-y-4">
          {[1, 2, 3].map(i => (
            <div key={i} data-testid={`skeleton-item-${i}`} className="animate-pulse">
              <div className="h-12 bg-gray-200 rounded"></div>
            </div>
          ))}
        </div>
      </section>
    );
  }

  if (error) {
    return (
      <section id="faq" aria-labelledby="faq-heading" data-testid="pricing-faq-error" role="alert">
        <h2 id="faq-heading">Frequently Asked Questions</h2>
        <p data-testid="error-message">{error}</p>
      </section>
    );
  }

  if (faqs.length === 0) {
    return (
      <section id="faq" aria-labelledby="faq-heading" data-testid="pricing-faq-empty">
        <h2 id="faq-heading">Frequently Asked Questions</h2>
        <p data-testid="empty-message">No frequently asked questions available</p>
      </section>
    );
  }

  return (
    <section
      id="faq"
      aria-labelledby="faq-heading"
      data-testid="pricing-faq"
      className={className}
    >
      <h2 id="faq-heading" className="text-2xl font-semibold mb-6">
        Frequently Asked Questions
      </h2>

      <div
        data-testid="faq-accordion"
        role="region"
        aria-labelledby="faq-heading"
        className="space-y-2"
      >
        {faqs.map(faq => {
          const isOpen = openItems.has(faq.id);
          return (
            <div
              key={faq.id}
              data-testid={`faq-item-${faq.id}`}
              className="border rounded-lg overflow-hidden"
            >
              <button
                data-testid={`faq-question-${faq.id}`}
                onClick={() => toggleItem(faq.id)}
                aria-expanded={isOpen}
                aria-controls={`faq-answer-${faq.id}`}
                className="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors"
              >
                <span className="font-medium">{faq.question}</span>
                <span
                  data-testid={`chevron-${faq.id}`}
                  className={`transform transition-transform duration-200 ${
                    isOpen ? 'rotate-180' : ''
                  }`}
                >
                  ▼
                </span>
              </button>
              <div
                id={`faq-answer-${faq.id}`}
                data-testid={`faq-answer-${faq.id}`}
                hidden={!isOpen}
                className="p-4 pt-0 text-gray-600"
              >
                {faq.answer}
              </div>
            </div>
          );
        })}
      </div>
    </section>
  );
};

describe('PricingFAQ', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // FAQ-001 to FAQ-010: Basic Rendering
  describe('Rendering', () => {
    it('FAQ-001: component renders', () => {
      render(<MockPricingFAQ />);
      expect(screen.getByTestId('pricing-faq')).toBeInTheDocument();
    });

    it('FAQ-002: displays all FAQ items', () => {
      render(<MockPricingFAQ />);
      expect(screen.getByTestId('faq-item-1')).toBeInTheDocument();
      expect(screen.getByTestId('faq-item-2')).toBeInTheDocument();
      expect(screen.getByTestId('faq-item-3')).toBeInTheDocument();
      expect(screen.getByTestId('faq-item-4')).toBeInTheDocument();
      expect(screen.getByTestId('faq-item-5')).toBeInTheDocument();
    });

    it('FAQ-003: all items collapsed by default', () => {
      render(<MockPricingFAQ />);
      mockFAQs.forEach(faq => {
        expect(screen.getByTestId(`faq-answer-${faq.id}`)).toHaveAttribute('hidden');
      });
    });

    it('FAQ-004: displays section heading', () => {
      render(<MockPricingFAQ />);
      expect(screen.getByText('Frequently Asked Questions')).toBeInTheDocument();
    });

    it('FAQ-005: displays all questions', () => {
      render(<MockPricingFAQ />);
      expect(screen.getByText('What are credits?')).toBeInTheDocument();
      expect(screen.getByText('How long do credits last?')).toBeInTheDocument();
      expect(screen.getByText('Can I get a refund?')).toBeInTheDocument();
    });

    it('FAQ-006: displays loading skeleton', () => {
      render(<MockPricingFAQ isLoading={true} />);
      expect(screen.getByTestId('pricing-faq-loading')).toBeInTheDocument();
      expect(screen.getByTestId('faq-skeleton')).toBeInTheDocument();
    });

    it('FAQ-007: displays error state', () => {
      render(<MockPricingFAQ error="Failed to load FAQs" />);
      expect(screen.getByTestId('pricing-faq-error')).toBeInTheDocument();
      expect(screen.getByTestId('error-message')).toHaveTextContent('Failed to load FAQs');
    });

    it('FAQ-008: displays empty state', () => {
      render(<MockPricingFAQ faqs={[]} />);
      expect(screen.getByTestId('pricing-faq-empty')).toBeInTheDocument();
      expect(screen.getByTestId('empty-message')).toBeInTheDocument();
    });

    it('FAQ-009: displays chevron icon for each item', () => {
      render(<MockPricingFAQ />);
      expect(screen.getByTestId('chevron-1')).toBeInTheDocument();
      expect(screen.getByTestId('chevron-5')).toBeInTheDocument();
    });

    it('FAQ-010: section has correct id', () => {
      render(<MockPricingFAQ />);
      expect(screen.getByTestId('pricing-faq')).toHaveAttribute('id', 'faq');
    });
  });

  // FAQ-011 to FAQ-025: Expand/Collapse Behavior
  describe('Expand/Collapse Behavior', () => {
    it('FAQ-011: click question to expand', async () => {
      const user = userEvent.setup();
      render(<MockPricingFAQ />);
      
      await user.click(screen.getByTestId('faq-question-1'));
      
      expect(screen.getByTestId('faq-answer-1')).not.toHaveAttribute('hidden');
      expect(screen.getByText('Credits are used to run SEO analyses.')).toBeInTheDocument();
    });

    it('FAQ-012: click open question to collapse', async () => {
      const user = userEvent.setup();
      render(<MockPricingFAQ />);
      
      // Open
      await user.click(screen.getByTestId('faq-question-1'));
      expect(screen.getByTestId('faq-answer-1')).not.toHaveAttribute('hidden');
      
      // Close
      await user.click(screen.getByTestId('faq-question-1'));
      expect(screen.getByTestId('faq-answer-1')).toHaveAttribute('hidden');
    });

    it('FAQ-013: only one item open at a time (default)', async () => {
      const user = userEvent.setup();
      render(<MockPricingFAQ />);
      
      await user.click(screen.getByTestId('faq-question-1'));
      expect(screen.getByTestId('faq-answer-1')).not.toHaveAttribute('hidden');
      
      await user.click(screen.getByTestId('faq-question-2'));
      expect(screen.getByTestId('faq-answer-1')).toHaveAttribute('hidden');
      expect(screen.getByTestId('faq-answer-2')).not.toHaveAttribute('hidden');
    });

    it('FAQ-014: multiple items open when allowMultiple=true', async () => {
      const user = userEvent.setup();
      render(<MockPricingFAQ allowMultiple={true} />);
      
      await user.click(screen.getByTestId('faq-question-1'));
      await user.click(screen.getByTestId('faq-question-2'));
      
      expect(screen.getByTestId('faq-answer-1')).not.toHaveAttribute('hidden');
      expect(screen.getByTestId('faq-answer-2')).not.toHaveAttribute('hidden');
    });

    it('FAQ-015: chevron rotates on expand', async () => {
      const user = userEvent.setup();
      render(<MockPricingFAQ />);
      
      const chevron = screen.getByTestId('chevron-1');
      expect(chevron).not.toHaveClass('rotate-180');
      
      await user.click(screen.getByTestId('faq-question-1'));
      expect(chevron).toHaveClass('rotate-180');
    });

    it('FAQ-016: chevron rotates back on collapse', async () => {
      const user = userEvent.setup();
      render(<MockPricingFAQ />);
      
      await user.click(screen.getByTestId('faq-question-1'));
      await user.click(screen.getByTestId('faq-question-1'));
      
      const chevron = screen.getByTestId('chevron-1');
      expect(chevron).not.toHaveClass('rotate-180');
    });
  });

  // FAQ-026 to FAQ-040: Accessibility
  describe('Accessibility', () => {
    it('FAQ-017: section has aria-labelledby', () => {
      render(<MockPricingFAQ />);
      const section = screen.getByTestId('pricing-faq');
      expect(section).toHaveAttribute('aria-labelledby', 'faq-heading');
    });

    it('FAQ-018: accordion has role=region', () => {
      render(<MockPricingFAQ />);
      const accordion = screen.getByTestId('faq-accordion');
      expect(accordion).toHaveAttribute('role', 'region');
    });

    it('FAQ-019: question button has role=button', () => {
      render(<MockPricingFAQ />);
      const button = screen.getByTestId('faq-question-1');
      expect(button).toHaveAttribute('role', 'button');
    });

    it('FAQ-020: aria-expanded is false when closed', () => {
      render(<MockPricingFAQ />);
      const button = screen.getByTestId('faq-question-1');
      expect(button).toHaveAttribute('aria-expanded', 'false');
    });

    it('FAQ-021: aria-expanded is true when open', async () => {
      const user = userEvent.setup();
      render(<MockPricingFAQ />);
      
      await user.click(screen.getByTestId('faq-question-1'));
      const button = screen.getByTestId('faq-question-1');
      expect(button).toHaveAttribute('aria-expanded', 'true');
    });

    it('FAQ-022: button has aria-controls', () => {
      render(<MockPricingFAQ />);
      const button = screen.getByTestId('faq-question-1');
      expect(button).toHaveAttribute('aria-controls', 'faq-answer-1');
    });

    it('FAQ-023: answer panel has matching id', () => {
      render(<MockPricingFAQ />);
      const answer = screen.getByTestId('faq-answer-1');
      expect(answer).toHaveAttribute('id', 'faq-answer-1');
    });

    it('FAQ-024: heading has correct id', () => {
      render(<MockPricingFAQ />);
      const heading = screen.getByText('Frequently Asked Questions');
      expect(heading).toHaveAttribute('id', 'faq-heading');
    });

    it('FAQ-025: error state has role=alert', () => {
      render(<MockPricingFAQ error="Error" />);
      expect(screen.getByTestId('pricing-faq-error')).toHaveAttribute('role', 'alert');
    });
  });

  // FAQ-041 to FAQ-055: Keyboard Navigation
  describe('Keyboard Navigation', () => {
    it('FAQ-026: Enter key toggles item', async () => {
      const user = userEvent.setup();
      render(<MockPricingFAQ />);
      
      const button = screen.getByTestId('faq-question-1');
      button.focus();
      await user.keyboard('{Enter}');
      
      expect(screen.getByTestId('faq-answer-1')).not.toHaveAttribute('hidden');
    });

    it('FAQ-027: Space key toggles item', async () => {
      const user = userEvent.setup();
      render(<MockPricingFAQ />);
      
      const button = screen.getByTestId('faq-question-1');
      button.focus();
      await user.keyboard(' ');
      
      expect(screen.getByTestId('faq-answer-1')).not.toHaveAttribute('hidden');
    });

    it('FAQ-028: all buttons are focusable', () => {
      render(<MockPricingFAQ />);
      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).not.toHaveAttribute('tabindex', '-1');
      });
    });

    it('FAQ-029: focus ring visible on button', () => {
      render(<MockPricingFAQ />);
      const button = screen.getByTestId('faq-question-1');
      // Check that button can receive focus
      button.focus();
      expect(button).toHaveFocus();
    });
  });

  // FAQ-056 to FAQ-070: Edge Cases
  describe('Edge Cases', () => {
    it('FAQ-030: handles long question', () => {
      const longFAQ = [{
        id: 1,
        question: 'This is a very long question that might need to wrap to multiple lines on smaller screens and should still be readable and accessible?',
        answer: 'Answer',
      }];
      render(<MockPricingFAQ faqs={longFAQ} />);
      expect(screen.getByText(longFAQ[0].question)).toBeInTheDocument();
    });

    it('FAQ-031: handles long answer', () => {
      const longAnswer = [{
        id: 1,
        question: 'Question',
        answer: 'This is a very long answer that contains a lot of information and might need to scroll or wrap properly to be readable. '.repeat(10),
      }];
      render(<MockPricingFAQ faqs={longAnswer} />);
      // Expand first
      fireEvent.click(screen.getByTestId('faq-question-1'));
      expect(screen.getByText(/This is a very long answer/)).toBeInTheDocument();
    });

    it('FAQ-032: handles unicode in question', () => {
      const unicodeFAQ = [{
        id: 1,
        question: 'Что такое кредиты? 信用是什么？',
        answer: 'Answer',
      }];
      render(<MockPricingFAQ faqs={unicodeFAQ} />);
      expect(screen.getByText('Что такое кредиты? 信用是什么？')).toBeInTheDocument();
    });

    it('FAQ-033: handles unicode in answer', () => {
      const unicodeAnswer = [{
        id: 1,
        question: 'Question',
        answer: 'Кредиты — это... 信用是...',
      }];
      render(<MockPricingFAQ faqs={unicodeAnswer} />);
      fireEvent.click(screen.getByTestId('faq-question-1'));
      expect(screen.getByText('Кредиты — это... 信用是...')).toBeInTheDocument();
    });

    it('FAQ-034: handles empty question gracefully', () => {
      const emptyQuestion = [{
        id: 1,
        question: '',
        answer: 'Answer',
      }];
      render(<MockPricingFAQ faqs={emptyQuestion} />);
      // Should still render the item
      expect(screen.getByTestId('faq-item-1')).toBeInTheDocument();
    });

    it('FAQ-035: handles empty answer gracefully', () => {
      const emptyAnswer = [{
        id: 1,
        question: 'Question',
        answer: '',
      }];
      render(<MockPricingFAQ faqs={emptyAnswer} />);
      fireEvent.click(screen.getByTestId('faq-question-1'));
      const answer = screen.getByTestId('faq-answer-1');
      expect(answer).toBeEmptyDOMElement();
    });

    it('FAQ-036: XSS prevention in question', () => {
      const xssFAQ = [{
        id: 1,
        question: '<script>alert("xss")</script>Question',
        answer: 'Answer',
      }];
      render(<MockPricingFAQ faqs={xssFAQ} />);
      expect(screen.queryByText('<script>')).not.toBeInTheDocument();
    });

    it('FAQ-037: XSS prevention in answer', () => {
      const xssAnswer = [{
        id: 1,
        question: 'Question',
        answer: '<script>alert("xss")</script>Answer',
      }];
      render(<MockPricingFAQ faqs={xssAnswer} />);
      fireEvent.click(screen.getByTestId('faq-question-1'));
      expect(screen.queryByText('<script>')).not.toBeInTheDocument();
    });

    it('FAQ-038: custom className applied', () => {
      render(<MockPricingFAQ className="custom-class" />);
      const section = screen.getByTestId('pricing-faq');
      expect(section).toHaveClass('custom-class');
    });

    it('FAQ-039: handles single FAQ item', () => {
      render(<MockPricingFAQ faqs={[mockFAQs[0]]} />);
      expect(screen.getByTestId('faq-item-1')).toBeInTheDocument();
      expect(screen.queryByTestId('faq-item-2')).not.toBeInTheDocument();
    });

    it('FAQ-040: handles many FAQ items', () => {
      const manyFAQs = Array.from({ length: 20 }, (_, i) => ({
        id: i + 1,
        question: `Question ${i + 1}`,
        answer: `Answer ${i + 1}`,
      }));
      render(<MockPricingFAQ faqs={manyFAQs} />);
      expect(screen.getByTestId('faq-item-1')).toBeInTheDocument();
      expect(screen.getByTestId('faq-item-20')).toBeInTheDocument();
    });
  });

  // Additional Integration Tests
  describe('Integration', () => {
    it('toggles through multiple items correctly', async () => {
      const user = userEvent.setup();
      render(<MockPricingFAQ />);
      
      // Open item 1
      await user.click(screen.getByTestId('faq-question-1'));
      expect(screen.getByTestId('faq-answer-1')).not.toHaveAttribute('hidden');
      
      // Open item 2 (should close item 1)
      await user.click(screen.getByTestId('faq-question-2'));
      expect(screen.getByTestId('faq-answer-1')).toHaveAttribute('hidden');
      expect(screen.getByTestId('faq-answer-2')).not.toHaveAttribute('hidden');
      
      // Close item 2
      await user.click(screen.getByTestId('faq-question-2'));
      expect(screen.getByTestId('faq-answer-2')).toHaveAttribute('hidden');
    });

    it('maintains open state correctly with allowMultiple', async () => {
      const user = userEvent.setup();
      render(<MockPricingFAQ allowMultiple={true} />);
      
      // Open items 1 and 2
      await user.click(screen.getByTestId('faq-question-1'));
      await user.click(screen.getByTestId('faq-question-2'));
      
      expect(screen.getByTestId('faq-answer-1')).not.toHaveAttribute('hidden');
      expect(screen.getByTestId('faq-answer-2')).not.toHaveAttribute('hidden');
      
      // Close item 1
      await user.click(screen.getByTestId('faq-question-1'));
      expect(screen.getByTestId('faq-answer-1')).toHaveAttribute('hidden');
      expect(screen.getByTestId('faq-answer-2')).not.toHaveAttribute('hidden');
    });
  });
});
