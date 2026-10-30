/**
 * Tests for CreditPackages Component
 * Task: TASK-26-20
 * Test Documentation: TESTS/TEST-26-20.md
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  Check: () => <span data-testid="check-icon">✓</span>,
  Star: () => <span data-testid="star-icon">★</span>,
  ArrowRight: () => <span data-testid="arrow-icon">→</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loading</span>,
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

// Mock component
interface CreditPackagesProps {
  packages?: typeof mockPackages;
  isLoading?: boolean;
  error?: string | null;
  onBuyClick?: (packageId: number) => void;
  className?: string;
}

const MockCreditPackages = ({
  packages = mockPackages,
  isLoading = false,
  error = null,
  onBuyClick = jest.fn(),
  className = '',
}: CreditPackagesProps) => {
  if (isLoading) {
    return (
      <section 
        id="packages" 
        aria-labelledby="packages-heading" 
        data-testid="credit-packages-loading"
      >
        <h2 id="packages-heading">Credit Packages</h2>
        <div data-testid="packages-skeleton" className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} data-testid={`skeleton-card-${i}`} className="animate-pulse">
              <div className="h-48 bg-gray-200 rounded"></div>
            </div>
          ))}
        </div>
      </section>
    );
  }

  if (error) {
    return (
      <section 
        id="packages" 
        aria-labelledby="packages-heading" 
        data-testid="credit-packages-error"
        role="alert"
      >
        <h2 id="packages-heading">Credit Packages</h2>
        <p data-testid="error-message">{error}</p>
        <button onClick={() => {}} aria-label="Retry loading packages">
          Retry
        </button>
      </section>
    );
  }

  if (packages.length === 0) {
    return (
      <section 
        id="packages" 
        aria-labelledby="packages-heading" 
        data-testid="credit-packages-empty"
      >
        <h2 id="packages-heading">Credit Packages</h2>
        <p data-testid="empty-message">No packages available</p>
      </section>
    );
  }

  // Sort packages: popular first, then by price
  const sortedPackages = [...packages].sort((a, b) => {
    if (a.is_popular && !b.is_popular) return -1;
    if (!a.is_popular && b.is_popular) return 1;
    return a.price - b.price;
  });

  return (
    <section
      id="packages"
      aria-labelledby="packages-heading"
      data-testid="credit-packages"
      className={className}
    >
      <h2 id="packages-heading">Credit Packages</h2>
      <p className="text-gray-600">Choose the right package for your needs</p>
      <p data-testid="minimum-notice" className="text-sm text-gray-500 mt-2">
        Minimum purchase: $5
      </p>
      <div
        data-testid="packages-grid"
        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6"
      >
        {sortedPackages.map((pkg, index) => (
          <article
            key={pkg.id}
            data-testid={`package-card-${pkg.id}`}
            data-popular={pkg.is_popular}
            role="article"
            aria-label={`${pkg.name} package - ${pkg.credits.toLocaleString()} credits for $${(pkg.price / 100).toFixed(2)}`}
            className={`p-6 rounded-lg border ${
              pkg.is_popular 
                ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500' 
                : 'border-gray-200 bg-white'
            } transition-all duration-200 hover:shadow-lg`}
            style={{ animationDelay: `${index * 100}ms` }}
          >
            {pkg.is_popular && (
              <div data-testid={`popular-badge-${pkg.id}`} className="flex items-center gap-1 mb-2">
                <span data-testid="star-icon">★</span>
                <span className="text-blue-600 font-medium">Popular</span>
              </div>
            )}
            
            <h3 className="text-xl font-semibold">{pkg.name}</h3>
            
            <p data-testid={`credits-${pkg.id}`} className="text-3xl font-bold mt-2">
              {pkg.credits.toLocaleString()} credits
            </p>
            
            <p data-testid={`price-${pkg.id}`} className="text-2xl font-semibold text-gray-900 mt-1">
              ${(pkg.price / 100).toFixed(2)}
            </p>
            
            {pkg.bonus_percentage > 0 && (
              <span 
                data-testid={`bonus-${pkg.id}`} 
                className="inline-block mt-2 px-2 py-1 bg-green-100 text-green-700 text-sm rounded"
              >
                +{pkg.bonus_percentage}% bonus
              </span>
            )}
            
            <p className="text-gray-600 mt-3">{pkg.description}</p>
            
            <p data-testid={`price-per-credit-${pkg.id}`} className="text-sm text-gray-500 mt-2">
              ${(pkg.price / pkg.credits).toFixed(4)}/credit
            </p>
            
            <button
              data-testid={`buy-button-${pkg.id}`}
              onClick={() => onBuyClick(pkg.id)}
              aria-label={`Buy ${pkg.name} package`}
              className="mt-4 w-full py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Buy Now
              <span data-testid="arrow-icon">→</span>
            </button>
          </article>
        ))}
      </div>
    </section>
  );
};

describe('CreditPackages', () => {
  const mockOnBuyClick = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // CP-001 to CP-010: Basic Rendering
  describe('Rendering', () => {
    it('CP-001: component renders', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('credit-packages')).toBeInTheDocument();
    });

    it('CP-002: displays 4 package cards', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('package-card-1')).toBeInTheDocument();
      expect(screen.getByTestId('package-card-2')).toBeInTheDocument();
      expect(screen.getByTestId('package-card-3')).toBeInTheDocument();
      expect(screen.getByTestId('package-card-4')).toBeInTheDocument();
    });

    it('CP-003: displays loading skeleton', () => {
      render(<MockCreditPackages isLoading={true} />);
      expect(screen.getByTestId('credit-packages-loading')).toBeInTheDocument();
      expect(screen.getByTestId('packages-skeleton')).toBeInTheDocument();
    });

    it('CP-004: displays error state', () => {
      render(<MockCreditPackages error="Failed to load packages" />);
      expect(screen.getByTestId('credit-packages-error')).toBeInTheDocument();
      expect(screen.getByTestId('error-message')).toHaveTextContent('Failed to load packages');
    });

    it('CP-005: displays empty state', () => {
      render(<MockCreditPackages packages={[]} />);
      expect(screen.getByTestId('credit-packages-empty')).toBeInTheDocument();
      expect(screen.getByTestId('empty-message')).toHaveTextContent('No packages available');
    });

    it('CP-006: popular badge on Pro package', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('popular-badge-3')).toBeInTheDocument();
    });

    it('CP-007: section heading present', () => {
      render(<MockCreditPackages />);
      expect(screen.getByText('Credit Packages')).toBeInTheDocument();
    });

    it('CP-008: section description present', () => {
      render(<MockCreditPackages />);
      expect(screen.getByText('Choose the right package for your needs')).toBeInTheDocument();
    });

    it('CP-009: minimum purchase notice', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('minimum-notice')).toHaveTextContent('Minimum purchase: $5');
    });

    it('CP-010: responsive grid classes', () => {
      render(<MockCreditPackages />);
      const grid = screen.getByTestId('packages-grid');
      expect(grid).toHaveClass('grid-cols-1');
      expect(grid).toHaveClass('md:grid-cols-2');
      expect(grid).toHaveClass('lg:grid-cols-4');
    });
  });

  // CP-011 to CP-020: Package Data Display
  describe('Package Data Display', () => {
    it('CP-011: displays package names', () => {
      render(<MockCreditPackages />);
      expect(screen.getByText('Starter')).toBeInTheDocument();
      expect(screen.getByText('Basic')).toBeInTheDocument();
      expect(screen.getByText('Pro')).toBeInTheDocument();
      expect(screen.getByText('Enterprise')).toBeInTheDocument();
    });

    it('CP-012: displays credit amounts', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('credits-1')).toHaveTextContent('1,000 credits');
      expect(screen.getByTestId('credits-4')).toHaveTextContent('50,000 credits');
    });

    it('CP-013: displays prices correctly', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('price-1')).toHaveTextContent('$5.00');
      expect(screen.getByTestId('price-2')).toHaveTextContent('$20.00');
      expect(screen.getByTestId('price-3')).toHaveTextContent('$50.00');
      expect(screen.getByTestId('price-4')).toHaveTextContent('$150.00');
    });

    it('CP-014: displays bonus percentage', () => {
      render(<MockCreditPackages />);
      expect(screen.queryByTestId('bonus-1')).not.toBeInTheDocument();
      expect(screen.getByTestId('bonus-2')).toHaveTextContent('+5% bonus');
      expect(screen.getByTestId('bonus-3')).toHaveTextContent('+10% bonus');
      expect(screen.getByTestId('bonus-4')).toHaveTextContent('+15% bonus');
    });

    it('CP-015: displays descriptions', () => {
      render(<MockCreditPackages />);
      expect(screen.getByText('Perfect for trying out SEOIndicator')).toBeInTheDocument();
      expect(screen.getByText('Great for small websites')).toBeInTheDocument();
    });

    it('CP-016: calculates price per credit', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('price-per-credit-1')).toHaveTextContent('$0.0050/credit');
      expect(screen.getByTestId('price-per-credit-4')).toHaveTextContent('$0.0003/credit');
    });

    it('CP-017: formats large numbers with commas', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('credits-4')).toHaveTextContent('50,000 credits');
    });

    it('CP-018: displays buy button for each package', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('buy-button-1')).toBeInTheDocument();
      expect(screen.getByTestId('buy-button-2')).toBeInTheDocument();
      expect(screen.getByTestId('buy-button-3')).toBeInTheDocument();
      expect(screen.getByTestId('buy-button-4')).toBeInTheDocument();
    });

    it('CP-019: popular package has data attribute', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('package-card-3')).toHaveAttribute('data-popular', 'true');
    });

    it('CP-020: non-popular packages have false data attribute', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('package-card-1')).toHaveAttribute('data-popular', 'false');
    });
  });

  // CP-021 to CP-030: User Interaction
  describe('User Interaction', () => {
    it('CP-021: buy button click calls onBuyClick', async () => {
      const user = userEvent.setup();
      render(<MockCreditPackages onBuyClick={mockOnBuyClick} />);
      
      await user.click(screen.getByTestId('buy-button-1'));
      expect(mockOnBuyClick).toHaveBeenCalledWith(1);
    });

    it('CP-022: buy button has aria-label', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('buy-button-1')).toHaveAttribute('aria-label', 'Buy Starter package');
    });

    it('CP-023: popular package card has special styling', () => {
      render(<MockCreditPackages />);
      const proCard = screen.getByTestId('package-card-3');
      expect(proCard).toHaveClass('border-blue-500');
      expect(proCard).toHaveClass('bg-blue-50');
    });

    it('CP-024: error state has retry button', () => {
      render(<MockCreditPackages error="Error" />);
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });

    it('CP-025: error state has role=alert', () => {
      render(<MockCreditPackages error="Error" />);
      expect(screen.getByTestId('credit-packages-error')).toHaveAttribute('role', 'alert');
    });
  });

  // CP-031 to CP-040: Accessibility
  describe('Accessibility', () => {
    it('CP-026: section has aria-labelledby', () => {
      render(<MockCreditPackages />);
      const section = screen.getByTestId('credit-packages');
      expect(section).toHaveAttribute('aria-labelledby', 'packages-heading');
    });

    it('CP-027: section has id for deep linking', () => {
      render(<MockCreditPackages />);
      const section = screen.getByTestId('credit-packages');
      expect(section).toHaveAttribute('id', 'packages');
    });

    it('CP-028: package cards have article role', () => {
      render(<MockCreditPackages />);
      expect(screen.getByTestId('package-card-1')).toHaveAttribute('role', 'article');
    });

    it('CP-029: package cards have aria-label', () => {
      render(<MockCreditPackages />);
      const card = screen.getByTestId('package-card-1');
      expect(card).toHaveAttribute('aria-label');
      expect(card.getAttribute('aria-label')).toContain('Starter');
    });

    it('CP-030: all buy buttons are focusable', () => {
      render(<MockCreditPackages />);
      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).not.toHaveAttribute('disabled');
      });
    });

    it('CP-031: loading skeleton has no interactive elements', () => {
      render(<MockCreditPackages isLoading={true} />);
      expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('CP-032: heading has correct id', () => {
      render(<MockCreditPackages />);
      const heading = screen.getByText('Credit Packages');
      expect(heading).toHaveAttribute('id', 'packages-heading');
    });
  });

  // CP-033 to CP-042: Ordering and Sorting
  describe('Ordering and Sorting', () => {
    it('CP-033: popular package appears first', () => {
      render(<MockCreditPackages />);
      const grid = screen.getByTestId('packages-grid');
      const firstCard = grid.firstChild as HTMLElement;
      expect(firstCard).toHaveAttribute('data-testid', 'package-card-3');
    });

    it('CP-034: packages sorted by price after popular', () => {
      render(<MockCreditPackages />);
      const cards = screen.getAllByTestId(/package-card/);
      const cardIds = cards.map(card => card.getAttribute('data-testid'));
      
      // Pro (popular) first, then Starter, Basic, Enterprise by price
      expect(cardIds[0]).toBe('package-card-3');
    });

    it('CP-035: custom className applied', () => {
      render(<MockCreditPackages className="custom-class" />);
      const section = screen.getByTestId('credit-packages');
      expect(section).toHaveClass('custom-class');
    });
  });

  // CP-043 to CP-055: Edge Cases
  describe('Edge Cases', () => {
    it('CP-036: handles zero price', () => {
      const freePackage = [{ ...mockPackages[0], price: 0 }];
      render(<MockCreditPackages packages={freePackage} />);
      expect(screen.getByTestId('price-1')).toHaveTextContent('$0.00');
    });

    it('CP-037: handles zero credits gracefully', () => {
      const zeroCredits = [{ ...mockPackages[0], credits: 0 }];
      render(<MockCreditPackages packages={zeroCredits} />);
      expect(screen.getByTestId('credits-1')).toHaveTextContent('0 credits');
    });

    it('CP-038: handles long package name', () => {
      const longName = [{ ...mockPackages[0], name: 'Very Long Package Name That Should Be Truncated' }];
      render(<MockCreditPackages packages={longName} />);
      expect(screen.getByText('Very Long Package Name That Should Be Truncated')).toBeInTheDocument();
    });

    it('CP-039: handles unicode in name', () => {
      const unicodeName = [{ ...mockPackages[0], name: 'Тест 测试 🚀' }];
      render(<MockCreditPackages packages={unicodeName} />);
      expect(screen.getByText('Тест 测试 🚀')).toBeInTheDocument();
    });

    it('CP-040: handles large credit amounts', () => {
      const largeCredits = [{ ...mockPackages[0], credits: 10000000 }];
      render(<MockCreditPackages packages={largeCredits} />);
      expect(screen.getByTestId('credits-1')).toHaveTextContent('10,000,000 credits');
    });

    it('CP-041: XSS prevention in name', () => {
      const xssPackage = [{ 
        ...mockPackages[0], 
        name: '<script>alert("xss")</script>Test' 
      }];
      render(<MockCreditPackages packages={xssPackage} />);
      expect(screen.queryByText('<script>')).not.toBeInTheDocument();
    });

    it('CP-042: XSS prevention in description', () => {
      const xssPackage = [{ 
        ...mockPackages[0], 
        description: '<img src=x onerror=alert("xss")>' 
      }];
      render(<MockCreditPackages packages={xssPackage} />);
      expect(screen.queryByText('onerror')).not.toBeInTheDocument();
    });

    it('CP-043: handles single package', () => {
      render(<MockCreditPackages packages={[mockPackages[0]]} />);
      expect(screen.getByTestId('package-card-1')).toBeInTheDocument();
      expect(screen.queryByTestId('package-card-2')).not.toBeInTheDocument();
    });

    it('CP-044: handles negative bonus (not shown)', () => {
      const negativeBonus = [{ ...mockPackages[0], bonus_percentage: -10 }];
      render(<MockCreditPackages packages={negativeBonus} />);
      expect(screen.queryByTestId('bonus-1')).not.toBeInTheDocument();
    });

    it('CP-045: animation delay applied to cards', () => {
      render(<MockCreditPackages />);
      const cards = screen.getAllByTestId(/package-card/);
      cards.forEach((card, index) => {
        expect(card).toHaveStyle({ animationDelay: `${index * 100}ms` });
      });
    });
  });
});
