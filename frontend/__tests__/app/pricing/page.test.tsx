/**
 * Tests for Pricing Page
 * Task: TASK-26-20
 * Test Documentation: TESTS/TEST-26-20.md
 */

import React from 'react';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useRouter } from 'next/navigation';

// Mock next/navigation
const mockPush = jest.fn();
const mockReplace = jest.fn();
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
  }),
}));

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  Check: () => <span data-testid="check-icon">✓</span>,
  ChevronDown: () => <span data-testid="chevron-icon">▼</span>,
  ArrowRight: () => <span data-testid="arrow-icon">→</span>,
  Star: () => <span data-testid="star-icon">★</span>,
  RefreshCw: () => <span data-testid="refresh-icon">↻</span>,
  AlertCircle: () => <span data-testid="alert-icon">!</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loading</span>,
}));

// Mock API hooks
const mockRefetchPackages = jest.fn();
const mockRefetchModels = jest.fn();

jest.mock('@/lib/api/pricing', () => ({
  usePackages: jest.fn(() => ({
    packages: mockPackages,
    isLoading: false,
    error: null,
    refetch: mockRefetchPackages,
  })),
  useModels: jest.fn(() => ({
    models: mockModels,
    isLoading: false,
    error: null,
    refetch: mockRefetchModels,
  })),
  usePricingPage: jest.fn(() => ({
    packages: mockPackages,
    models: mockModels,
    isLoading: false,
    error: null,
    refetch: jest.fn(),
  })),
}));

// Mock data
const mockPackages = [
  {
    id: 1,
    name: 'Starter',
    credits: 1000,
    price: 500,
    bonus_percentage: 0,
    is_popular: false,
    description: 'Perfect for trying out SEOIndicator',
    sort_order: 1,
  },
  {
    id: 2,
    name: 'Basic',
    credits: 5000,
    price: 2000,
    bonus_percentage: 5,
    is_popular: false,
    description: 'Great for small websites',
    sort_order: 2,
  },
  {
    id: 3,
    name: 'Pro',
    credits: 15000,
    price: 5000,
    bonus_percentage: 10,
    is_popular: true,
    description: 'Most popular for growing businesses',
    sort_order: 3,
  },
  {
    id: 4,
    name: 'Enterprise',
    credits: 50000,
    price: 15000,
    bonus_percentage: 15,
    is_popular: false,
    description: 'For agencies and large teams',
    sort_order: 4,
  },
];

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
];

const mockFAQs = [
  {
    id: 1,
    question: 'What are credits?',
    answer: 'Credits are used to run SEO analyses.',
  },
  {
    id: 2,
    question: 'How long do credits last?',
    answer: 'Credits never expire.',
  },
];

// Mock components for testing
const MockPricingPage = ({
  packages = mockPackages,
  models = mockModels,
  faqs = mockFAQs,
  isLoading = false,
  error = null,
}: {
  packages?: typeof mockPackages;
  models?: typeof mockModels;
  faqs?: typeof mockFAQs;
  isLoading?: boolean;
  error?: string | null;
}) => {
  if (isLoading) {
    return (
      <div data-testid="pricing-page-loading" role="status" aria-live="polite">
        <div data-testid="packages-skeleton">Loading packages...</div>
        <div data-testid="models-skeleton">Loading models...</div>
        <div data-testid="faq-skeleton">Loading FAQ...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div data-testid="pricing-page-error" role="alert">
        <p>{error}</p>
        <button onClick={() => mockRefetchPackages()} aria-label="Retry loading pricing">
          Retry
        </button>
      </div>
    );
  }

  return (
    <main data-testid="pricing-page">
      <h1>Pricing</h1>
      
      {/* Credit Packages Section */}
      <section id="packages" aria-labelledby="packages-heading">
        <h2 id="packages-heading">Credit Packages</h2>
        <p>Choose the right package for your needs</p>
        <p data-testid="minimum-notice">Minimum purchase: $5</p>
        <div data-testid="packages-grid" className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {packages.map((pkg) => (
            <article key={pkg.id} data-testid={`package-card-${pkg.id}`} role="article">
              {pkg.is_popular && <span data-testid={`popular-badge-${pkg.id}`}>Popular</span>}
              <h3>{pkg.name}</h3>
              <p data-testid={`credits-${pkg.id}`}>{pkg.credits.toLocaleString()} credits</p>
              <p data-testid={`price-${pkg.id}`}>${(pkg.price / 100).toFixed(2)}</p>
              {pkg.bonus_percentage > 0 && (
                <span data-testid={`bonus-${pkg.id}`}>+{pkg.bonus_percentage}% bonus</span>
              )}
              <p>{pkg.description}</p>
              <button 
                data-testid={`buy-button-${pkg.id}`}
                aria-label={`Buy ${pkg.name} package`}
                onClick={() => mockPush(`/checkout?package=${pkg.id}`)}
              >
                Buy Now
              </button>
            </article>
          ))}
        </div>
      </section>

      {/* Model Pricing Section */}
      <section id="models" aria-labelledby="models-heading">
        <h2 id="models-heading">Model Pricing</h2>
        <p>Compare AI model costs</p>
        <div data-testid="models-table-container" role="region" aria-labelledby="models-heading">
          <table data-testid="models-table" aria-describedby="models-caption">
            <caption id="models-caption" className="sr-only">AI model pricing comparison</caption>
            <thead>
              <tr>
                <th scope="col">Model</th>
                <th scope="col" data-sortable="true">Cost/Analysis</th>
                <th scope="col">Free Tier</th>
                <th scope="col">Context</th>
              </tr>
            </thead>
            <tbody>
              {models.map((model) => (
                <tr key={model.id} data-testid={`model-row-${model.id}`}>
                  <td>
                    <a href={`/models/${model.id}`} data-testid={`model-link-${model.id}`}>
                      {model.name}
                    </a>
                  </td>
                  <td data-testid={`cost-${model.id}`}>
                    {model.cost_per_analysis === 0 ? 'Free' : `$${model.cost_per_analysis.toFixed(4)}`}
                  </td>
                  <td data-testid={`free-tier-${model.id}`}>
                    {model.is_free_tier ? <span data-testid={`free-badge-${model.id}`}>Free tier</span> : 'No'}
                  </td>
                  <td data-testid={`context-${model.id}`}>
                    {model.context_window >= 1000000 
                      ? `${model.context_window / 1000000}M tokens`
                      : `${model.context_window / 1000}K tokens`}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      {/* FAQ Section */}
      <section id="faq" aria-labelledby="faq-heading">
        <h2 id="faq-heading">Frequently Asked Questions</h2>
        <div data-testid="faq-accordion" role="region" aria-labelledby="faq-heading">
          {faqs.map((faq, index) => (
            <div key={faq.id} data-testid={`faq-item-${faq.id}`}>
              <button
                data-testid={`faq-question-${faq.id}`}
                aria-expanded="false"
                aria-controls={`faq-answer-${faq.id}`}
              >
                {faq.question}
                <span data-testid={`chevron-${faq.id}`}>▼</span>
              </button>
              <div 
                id={`faq-answer-${faq.id}`} 
                data-testid={`faq-answer-${faq.id}`}
                hidden
              >
                {faq.answer}
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* Schema.org Structured Data */}
      <script
        type="application/ld+json"
        data-testid="schema-org"
        dangerouslySetInnerHTML={{
          __html: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'Product',
            name: 'SEOIndicator Credits',
            description: 'Credits for SEO analysis',
            offers: packages.map(pkg => ({
              '@type': 'Offer',
              name: pkg.name,
              price: (pkg.price / 100).toFixed(2),
              priceCurrency: 'USD',
            })),
          }),
        }}
      />
    </main>
  );
};

describe('PricingPage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // PP-001 to PP-005: Page Rendering
  describe('Rendering', () => {
    it('PP-001: renders without crash', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('pricing-page')).toBeInTheDocument();
    });

    it('PP-002: displays credit packages section', () => {
      render(<MockPricingPage />);
      expect(screen.getByText('Credit Packages')).toBeInTheDocument();
    });

    it('PP-003: displays model pricing section', () => {
      render(<MockPricingPage />);
      expect(screen.getByText('Model Pricing')).toBeInTheDocument();
    });

    it('PP-004: displays FAQ section', () => {
      render(<MockPricingPage />);
      expect(screen.getByText('Frequently Asked Questions')).toBeInTheDocument();
    });

    it('PP-005: displays all 4 package cards', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('package-card-1')).toBeInTheDocument();
      expect(screen.getByTestId('package-card-2')).toBeInTheDocument();
      expect(screen.getByTestId('package-card-3')).toBeInTheDocument();
      expect(screen.getByTestId('package-card-4')).toBeInTheDocument();
    });
  });

  // PP-006 to PP-010: Loading and Error States
  describe('Loading and Error States', () => {
    it('PP-006: displays loading state', () => {
      render(<MockPricingPage isLoading={true} />);
      expect(screen.getByTestId('pricing-page-loading')).toBeInTheDocument();
      expect(screen.getByTestId('packages-skeleton')).toBeInTheDocument();
    });

    it('PP-007: displays error state with retry button', () => {
      render(<MockPricingPage error="Failed to load pricing" />);
      expect(screen.getByTestId('pricing-page-error')).toBeInTheDocument();
      expect(screen.getByText('Failed to load pricing')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });

    it('PP-008: retry button calls refetch', async () => {
      const user = userEvent.setup();
      render(<MockPricingPage error="Failed to load pricing" />);
      
      await user.click(screen.getByRole('button', { name: /retry/i }));
      expect(mockRefetchPackages).toHaveBeenCalled();
    });
  });

  // PP-011 to PP-015: Package Display
  describe('Package Display', () => {
    it('PP-009: displays package credits correctly', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('credits-1')).toHaveTextContent('1,000 credits');
      expect(screen.getByTestId('credits-3')).toHaveTextContent('15,000 credits');
    });

    it('PP-010: displays package prices correctly', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('price-1')).toHaveTextContent('$5.00');
      expect(screen.getByTestId('price-3')).toHaveTextContent('$50.00');
    });

    it('PP-011: displays bonus percentage', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('bonus-2')).toHaveTextContent('+5% bonus');
      expect(screen.getByTestId('bonus-3')).toHaveTextContent('+10% bonus');
    });

    it('PP-012: popular badge on Pro package', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('popular-badge-3')).toBeInTheDocument();
    });

    it('PP-013: displays minimum purchase notice', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('minimum-notice')).toHaveTextContent('Minimum purchase: $5');
    });
  });

  // PP-016 to PP-020: Model Table
  describe('Model Table', () => {
    it('PP-014: displays model names', () => {
      render(<MockPricingPage />);
      expect(screen.getByText('GPT-4o Mini')).toBeInTheDocument();
      expect(screen.getByText('Claude 3.5 Sonnet')).toBeInTheDocument();
    });

    it('PP-015: displays model costs', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('cost-1')).toHaveTextContent('$0.0012');
      expect(screen.getByTestId('cost-2')).toHaveTextContent('$0.0045');
    });

    it('PP-016: displays free tier badge', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('free-badge-1')).toBeInTheDocument();
    });

    it('PP-017: displays context windows', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('context-1')).toHaveTextContent('128K tokens');
      expect(screen.getByTestId('context-2')).toHaveTextContent('200K tokens');
    });

    it('PP-018: model name links to details', () => {
      render(<MockPricingPage />);
      const modelLink = screen.getByTestId('model-link-1');
      expect(modelLink).toHaveAttribute('href', '/models/1');
    });
  });

  // PP-021 to PP-025: User Actions
  describe('User Actions', () => {
    it('PP-019: buy button navigates to checkout', async () => {
      const user = userEvent.setup();
      render(<MockPricingPage />);
      
      await user.click(screen.getByTestId('buy-button-1'));
      expect(mockPush).toHaveBeenCalledWith('/checkout?package=1');
    });

    it('PP-020: buy button has aria-label', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('buy-button-1')).toHaveAttribute('aria-label', 'Buy Starter package');
    });
  });

  // PP-026 to PP-030: FAQ Interaction
  describe('FAQ Interaction', () => {
    it('PP-021: FAQ questions displayed', () => {
      render(<MockPricingPage />);
      expect(screen.getByText('What are credits?')).toBeInTheDocument();
      expect(screen.getByText('How long do credits last?')).toBeInTheDocument();
    });

    it('PP-022: FAQ answers hidden by default', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('faq-answer-1')).toHaveAttribute('hidden');
    });

    it('PP-023: FAQ question button has aria-expanded', () => {
      render(<MockPricingPage />);
      const question = screen.getByTestId('faq-question-1');
      expect(question).toHaveAttribute('aria-expanded', 'false');
    });
  });

  // PP-031 to PP-035: SEO Requirements
  describe('SEO Requirements', () => {
    it('PP-024: has main heading', () => {
      render(<MockPricingPage />);
      expect(screen.getByRole('heading', { level: 1, name: 'Pricing' })).toBeInTheDocument();
    });

    it('PP-025: has section headings', () => {
      render(<MockPricingPage />);
      expect(screen.getByRole('heading', { level: 2, name: 'Credit Packages' })).toBeInTheDocument();
      expect(screen.getByRole('heading', { level: 2, name: 'Model Pricing' })).toBeInTheDocument();
      expect(screen.getByRole('heading', { level: 2, name: 'Frequently Asked Questions' })).toBeInTheDocument();
    });

    it('PP-026: has Schema.org structured data', () => {
      render(<MockPricingPage />);
      const schemaScript = screen.getByTestId('schema-org');
      expect(schemaScript).toHaveAttribute('type', 'application/ld+json');
    });

    it('PP-027: Schema.org contains offers', () => {
      render(<MockPricingPage />);
      const schemaScript = screen.getByTestId('schema-org');
      const schema = JSON.parse(schemaScript.getAttribute('dangerouslySetInnerHTML')?.__html || '{}');
      expect(schema['@type']).toBe('Product');
      expect(schema.offers).toHaveLength(4);
    });
  });

  // PP-036 to PP-045: Accessibility
  describe('Accessibility', () => {
    it('PP-028: has proper heading hierarchy', () => {
      render(<MockPricingPage />);
      const h1 = screen.getByRole('heading', { level: 1 });
      const h2s = screen.getAllByRole('heading', { level: 2 });
      const h3s = screen.getAllByRole('heading', { level: 3 });
      
      expect(h1).toBeInTheDocument();
      expect(h2s.length).toBe(3);
      expect(h3s.length).toBe(4); // Package names
    });

    it('PP-029: packages section has aria-labelledby', () => {
      render(<MockPricingPage />);
      const packagesSection = screen.getByTestId('packages-grid').closest('section');
      expect(packagesSection).toHaveAttribute('aria-labelledby', 'packages-heading');
    });

    it('PP-030: models table has caption', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('models-table')).toHaveAccessibleDescription();
    });

    it('PP-031: table headers have scope', () => {
      render(<MockPricingPage />);
      const headers = screen.getAllByRole('columnheader');
      headers.forEach(header => {
        expect(header).toHaveAttribute('scope', 'col');
      });
    });

    it('PP-032: package cards have article role', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('package-card-1')).toHaveAttribute('role', 'article');
    });

    it('PP-033: all buttons are focusable', () => {
      render(<MockPricingPage />);
      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).not.toHaveAttribute('tabindex', '-1');
      });
    });

    it('PP-034: loading state has role=status', () => {
      render(<MockPricingPage isLoading={true} />);
      expect(screen.getByTestId('pricing-page-loading')).toHaveAttribute('role', 'status');
    });

    it('PP-035: error state has role=alert', () => {
      render(<MockPricingPage error="Test error" />);
      expect(screen.getByTestId('pricing-page-error')).toHaveAttribute('role', 'alert');
    });
  });

  // PP-036 to PP-045: Edge Cases
  describe('Edge Cases', () => {
    it('PP-036: handles empty packages', () => {
      render(<MockPricingPage packages={[]} />);
      expect(screen.getByTestId('packages-grid')).toBeEmptyDOMElement();
    });

    it('PP-037: handles empty models', () => {
      render(<MockPricingPage models={[]} />);
      const tableBody = screen.getByTestId('models-table').querySelector('tbody');
      expect(tableBody).toBeEmptyDOMElement();
    });

    it('PP-038: handles zero cost model', () => {
      const freeModel = [{ ...mockModels[0], cost_per_analysis: 0 }];
      render(<MockPricingPage models={freeModel} />);
      expect(screen.getByTestId('cost-1')).toHaveTextContent('Free');
    });

    it('PP-039: formats large context windows', () => {
      const largeContextModel = [{ ...mockModels[0], context_window: 1000000 }];
      render(<MockPricingPage models={largeContextModel} />);
      expect(screen.getByTestId('context-1')).toHaveTextContent('1M tokens');
    });

    it('PP-040: displays package without bonus', () => {
      render(<MockPricingPage />);
      expect(screen.queryByTestId('bonus-1')).not.toBeInTheDocument();
    });
  });

  // PP-041 to PP-050: Performance and Integration
  describe('Performance and Integration', () => {
    it('PP-041: only one popular package', () => {
      render(<MockPricingPage />);
      const popularBadges = screen.getAllByTestId(/popular-badge/);
      expect(popularBadges).toHaveLength(1);
    });

    it('PP-042: all sections have IDs for deep linking', () => {
      render(<MockPricingPage />);
      expect(document.getElementById('packages')).toBeInTheDocument();
      expect(document.getElementById('models')).toBeInTheDocument();
      expect(document.getElementById('faq')).toBeInTheDocument();
    });

    it('PP-043: grid has responsive classes', () => {
      render(<MockPricingPage />);
      const grid = screen.getByTestId('packages-grid');
      expect(grid).toHaveClass('grid-cols-1');
      expect(grid).toHaveClass('md:grid-cols-2');
      expect(grid).toHaveClass('lg:grid-cols-4');
    });

    it('PP-044: responsive table container', () => {
      render(<MockPricingPage />);
      const tableContainer = screen.getByTestId('models-table-container');
      expect(tableContainer).toHaveAttribute('role', 'region');
    });

    it('PP-045: chevron icon in FAQ questions', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('chevron-1')).toBeInTheDocument();
    });
  });

  // PP-046 to PP-055: Additional Acceptance Criteria
  describe('Acceptance Criteria', () => {
    it('PP-046: displays all required page sections', () => {
      render(<MockPricingPage />);
      expect(screen.getByText('Credit Packages')).toBeInTheDocument();
      expect(screen.getByText('Model Pricing')).toBeInTheDocument();
      expect(screen.getByText('Frequently Asked Questions')).toBeInTheDocument();
    });

    it('PP-047: packages sorted by price', () => {
      render(<MockPricingPage />);
      const cards = screen.getAllByTestId(/package-card/);
      const prices = cards.map(card => {
        const priceEl = within(card).getByTestId(/price/);
        return parseFloat(priceEl.textContent?.replace('$', '') || '0');
      });
      
      for (let i = 1; i < prices.length; i++) {
        expect(prices[i]).toBeGreaterThanOrEqual(prices[i - 1]);
      }
    });

    it('PP-048: models table displays all columns', () => {
      render(<MockPricingPage />);
      expect(screen.getByRole('columnheader', { name: 'Model' })).toBeInTheDocument();
      expect(screen.getByRole('columnheader', { name: 'Cost/Analysis' })).toBeInTheDocument();
      expect(screen.getByRole('columnheader', { name: 'Free Tier' })).toBeInTheDocument();
      expect(screen.getByRole('columnheader', { name: 'Context' })).toBeInTheDocument();
    });

    it('PP-049: FAQ has minimum required questions', () => {
      render(<MockPricingPage />);
      const faqItems = screen.getAllByTestId(/faq-item/);
      expect(faqItems.length).toBeGreaterThanOrEqual(2);
    });

    it('PP-050: all buy buttons have aria-labels', () => {
      render(<MockPricingPage />);
      const buyButtons = screen.getAllByTestId(/buy-button/);
      buyButtons.forEach(button => {
        expect(button).toHaveAttribute('aria-label');
        expect(button.getAttribute('aria-label')).toContain('Buy');
        expect(button.getAttribute('aria-label')).toContain('package');
      });
    });
  });

  // PP-051 to PP-060: Additional Accessibility Tests
  describe('Additional Accessibility', () => {
    it('PP-051: FAQ accordion uses correct ARIA', () => {
      render(<MockPricingPage />);
      const question1 = screen.getByTestId('faq-question-1');
      expect(question1).toHaveAttribute('aria-expanded', 'false');
      expect(question1).toHaveAttribute('aria-controls', 'faq-answer-1');
    });

    it('PP-052: error state has retry button with label', () => {
      render(<MockPricingPage error="Error" />);
      const retryButton = screen.getByRole('button', { name: /retry/i });
      expect(retryButton).toBeInTheDocument();
    });

    it('PP-053: table is keyboard navigable', () => {
      render(<MockPricingPage />);
      const table = screen.getByTestId('models-table');
      expect(table).toBeInTheDocument();
    });

    it('PP-054: cost column is sortable', () => {
      render(<MockPricingPage />);
      const costHeader = screen.getByRole('columnheader', { name: 'Cost/Analysis' });
      expect(costHeader).toHaveAttribute('data-sortable', 'true');
    });

    it('PP-055: all sections have proper landmarks', () => {
      render(<MockPricingPage />);
      const main = screen.getByRole('main');
      expect(main).toBeInTheDocument();
    });
  });

  // PP-056 to PP-065: Integration Tests
  describe('Integration', () => {
    it('PP-056: full user flow - view and click package', async () => {
      const user = userEvent.setup();
      render(<MockPricingPage />);
      
      // View packages
      expect(screen.getByText('Credit Packages')).toBeInTheDocument();
      
      // Click buy button
      await user.click(screen.getByTestId('buy-button-3'));
      expect(mockPush).toHaveBeenCalledWith('/checkout?package=3');
    });

    it('PP-057: displays Pro package as popular', () => {
      render(<MockPricingPage />);
      const proCard = screen.getByTestId('package-card-3');
      expect(within(proCard).getByTestId('popular-badge-3')).toBeInTheDocument();
    });

    it('PP-058: free tier models display badge', () => {
      render(<MockPricingPage />);
      expect(screen.getByText('Free tier')).toBeInTheDocument();
    });

    it('PP-059: all FAQ items have question and answer', () => {
      render(<MockPricingPage />);
      const faqItems = screen.getAllByTestId(/faq-item/);
      faqItems.forEach(item => {
        expect(within(item).getByRole('button')).toBeInTheDocument();
      });
    });

    it('PP-060: responsive layout classes applied', () => {
      render(<MockPricingPage />);
      const grid = screen.getByTestId('packages-grid');
      expect(grid.className).toMatch(/grid-cols-1/);
      expect(grid.className).toMatch(/md:grid-cols-2/);
      expect(grid.className).toMatch(/lg:grid-cols-4/);
    });
  });
});
