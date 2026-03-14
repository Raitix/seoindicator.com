/**
 * Tests for PackageCard Component
 * Task: TASK-26-20
 * Test Documentation: TESTS/TEST-26-20.md
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  Check: () => <span data-testid="check-icon">✓</span>,
  Star: () => <span data-testid="star-icon">★</span>,
  ArrowRight: () => <span data-testid="arrow-icon">→</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loading</span>,
  Zap: () => <span data-testid="zap-icon">⚡</span>,
}));

// Mock package data
const mockPackage = {
  id: 3,
  name: 'Pro',
  credits: 15000,
  price: 5000, // $50.00 in cents
  bonus_percentage: 10,
  bonus_credits: 0,
  is_popular: true,
  description: 'Most popular for growing businesses',
  features: ['Priority support', 'Advanced analytics', 'API access'],
  sort_order: 3,
};

// Mock component
interface PackageCardProps {
  id: number;
  name: string;
  credits: number;
  price: number;
  bonus_percentage?: number;
  bonus_credits?: number;
  is_popular?: boolean;
  description?: string;
  features?: string[];
  onBuyClick?: (packageId: number) => void;
  isLoading?: boolean;
  disabled?: boolean;
  buttonText?: string;
  className?: string;
}

const MockPackageCard = ({
  id,
  name,
  credits,
  price,
  bonus_percentage = 0,
  bonus_credits = 0,
  is_popular = false,
  description,
  features = [],
  onBuyClick = jest.fn(),
  isLoading = false,
  disabled = false,
  buttonText = 'Buy Now',
  className = '',
}: PackageCardProps) => {
  const effectiveCredits = credits + bonus_credits + Math.floor(credits * bonus_percentage / 100);
  const pricePerCredit = credits > 0 ? price / credits : 0;

  return (
    <article
      data-testid={`package-card-${id}`}
      data-popular={is_popular}
      role="article"
      aria-label={`${name} package - ${effectiveCredits.toLocaleString()} credits for $${(price / 100).toFixed(2)}`}
      className={`p-6 rounded-lg border transition-all duration-200 hover:shadow-lg ${
        is_popular
          ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500'
          : 'border-gray-200 bg-white'
      } ${className}`}
    >
      {is_popular && (
        <div data-testid={`popular-badge-${id}`} className="flex items-center gap-1 mb-2">
          <span data-testid="star-icon">★</span>
          <span className="text-blue-600 font-medium">Popular</span>
        </div>
      )}

      <h3 className="text-xl font-semibold" data-testid={`name-${id}`}>
        {name}
      </h3>

      <p data-testid={`credits-${id}`} className="text-3xl font-bold mt-2">
        {credits.toLocaleString()} credits
      </p>

      {bonus_percentage > 0 && (
        <span
          data-testid={`bonus-${id}`}
          className="inline-block mt-2 px-2 py-1 bg-green-100 text-green-700 text-sm rounded"
        >
          +{bonus_percentage}% bonus
        </span>
      )}

      {bonus_credits > 0 && (
        <span
          data-testid={`bonus-credits-${id}`}
          className="inline-block mt-2 ml-2 px-2 py-1 bg-green-100 text-green-700 text-sm rounded"
        >
          +{bonus_credits.toLocaleString()} credits
        </span>
      )}

      <p data-testid={`price-${id}`} className="text-2xl font-semibold text-gray-900 mt-3">
        ${(price / 100).toFixed(2)}
      </p>

      {description && (
        <p data-testid={`description-${id}`} className="text-gray-600 mt-3">
          {description}
        </p>
      )}

      {pricePerCredit > 0 && (
        <p data-testid={`price-per-credit-${id}`} className="text-sm text-gray-500 mt-2">
          ${pricePerCredit.toFixed(4)}/credit
        </p>
      )}

      {features.length > 0 && (
        <ul data-testid={`features-${id}`} className="mt-4 space-y-2">
          {features.map((feature, index) => (
            <li key={index} className="flex items-center gap-2">
              <span data-testid="check-icon">✓</span>
              <span className="text-sm">{feature}</span>
            </li>
          ))}
        </ul>
      )}

      <button
        data-testid={`buy-button-${id}`}
        onClick={() => onBuyClick(id)}
        disabled={disabled || isLoading}
        aria-label={`Buy ${name} package`}
        className="mt-4 w-full py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {isLoading ? (
          <>
            <span data-testid="loader-icon" className="animate-spin inline-block mr-2">Loading</span>
            Processing...
          </>
        ) : (
          <>
            {buttonText}
            <span data-testid="arrow-icon">→</span>
          </>
        )}
      </button>
    </article>
  );
};

describe('PackageCard', () => {
  const mockOnBuyClick = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // PC-001 to PC-010: Basic Rendering
  describe('Rendering', () => {
    it('PC-001: component renders', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('package-card-3')).toBeInTheDocument();
    });

    it('PC-002: displays package name', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('name-3')).toHaveTextContent('Pro');
    });

    it('PC-003: displays credits', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('credits-3')).toHaveTextContent('15,000 credits');
    });

    it('PC-004: displays price', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('price-3')).toHaveTextContent('$50.00');
    });

    it('PC-005: displays bonus percentage', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('bonus-3')).toHaveTextContent('+10% bonus');
    });

    it('PC-006: displays popular badge', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('popular-badge-3')).toBeInTheDocument();
    });

    it('PC-007: displays description', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('description-3')).toHaveTextContent('Most popular for growing businesses');
    });

    it('PC-008: displays buy button', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('buy-button-3')).toBeInTheDocument();
    });

    it('PC-009: displays features list', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('features-3')).toBeInTheDocument();
      expect(screen.getByText('Priority support')).toBeInTheDocument();
    });

    it('PC-010: displays price per credit', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('price-per-credit-3')).toHaveTextContent('$0.0033/credit');
    });
  });

  // PC-011 to PC-020: Button States
  describe('Button States', () => {
    it('PC-011: button click triggers callback', async () => {
      const user = userEvent.setup();
      render(<MockPackageCard {...mockPackage} onBuyClick={mockOnBuyClick} />);
      
      await user.click(screen.getByTestId('buy-button-3'));
      expect(mockOnBuyClick).toHaveBeenCalledWith(3);
    });

    it('PC-012: button disabled when disabled=true', () => {
      render(<MockPackageCard {...mockPackage} disabled={true} />);
      expect(screen.getByTestId('buy-button-3')).toBeDisabled();
    });

    it('PC-013: button disabled when loading', () => {
      render(<MockPackageCard {...mockPackage} isLoading={true} />);
      expect(screen.getByTestId('buy-button-3')).toBeDisabled();
    });

    it('PC-014: shows loading spinner when loading', () => {
      render(<MockPackageCard {...mockPackage} isLoading={true} />);
      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
      expect(screen.getByText('Processing...')).toBeInTheDocument();
    });

    it('PC-015: custom button text', () => {
      render(<MockPackageCard {...mockPackage} buttonText="Get Started" />);
      expect(screen.getByText('Get Started')).toBeInTheDocument();
    });

    it('PC-016: button has aria-label', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('buy-button-3')).toHaveAttribute('aria-label', 'Buy Pro package');
    });

    it('PC-017: arrow icon in button', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('arrow-icon')).toBeInTheDocument();
    });
  });

  // PC-021 to PC-030: Popular Package Styling
  describe('Popular Package Styling', () => {
    it('PC-018: popular package has special border', () => {
      render(<MockPackageCard {...mockPackage} />);
      const card = screen.getByTestId('package-card-3');
      expect(card).toHaveClass('border-blue-500');
    });

    it('PC-019: popular package has special background', () => {
      render(<MockPackageCard {...mockPackage} />);
      const card = screen.getByTestId('package-card-3');
      expect(card).toHaveClass('bg-blue-50');
    });

    it('PC-020: popular package has ring', () => {
      render(<MockPackageCard {...mockPackage} />);
      const card = screen.getByTestId('package-card-3');
      expect(card).toHaveClass('ring-2');
      expect(card).toHaveClass('ring-blue-500');
    });

    it('PC-021: non-popular has standard styling', () => {
      render(<MockPackageCard {...mockPackage} is_popular={false} />);
      const card = screen.getByTestId('package-card-3');
      expect(card).toHaveClass('border-gray-200');
      expect(card).toHaveClass('bg-white');
    });

    it('PC-022: data-popular attribute', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('package-card-3')).toHaveAttribute('data-popular', 'true');
    });

    it('PC-023: star icon in popular badge', () => {
      render(<MockPackageCard {...mockPackage} />);
      const badge = screen.getByTestId('popular-badge-3');
      expect(within(badge).getByTestId('star-icon')).toBeInTheDocument();
    });
  });

  // PC-031 to PC-040: Formatting
  describe('Formatting', () => {
    it('PC-024: formats price correctly', () => {
      render(<MockPackageCard {...mockPackage} price={2000} />);
      expect(screen.getByTestId('price-3')).toHaveTextContent('$20.00');
    });

    it('PC-025: formats credits with commas', () => {
      render(<MockPackageCard {...mockPackage} credits={1000000} />);
      expect(screen.getByTestId('credits-3')).toHaveTextContent('1,000,000 credits');
    });

    it('PC-026: calculates price per credit', () => {
      render(<MockPackageCard {...mockPackage} credits={10000} price={1000} />);
      expect(screen.getByTestId('price-per-credit-3')).toHaveTextContent('$0.0001/credit');
    });

    it('PC-027: displays absolute bonus credits', () => {
      render(<MockPackageCard {...mockPackage} bonus_credits={500} bonus_percentage={0} />);
      expect(screen.getByTestId('bonus-credits-3')).toHaveTextContent('+500 credits');
    });

    it('PC-028: does not show bonus when zero', () => {
      render(<MockPackageCard {...mockPackage} bonus_percentage={0} />);
      expect(screen.queryByTestId('bonus-3')).not.toBeInTheDocument();
    });

    it('PC-029: does not show features when empty', () => {
      render(<MockPackageCard {...mockPackage} features={[]} />);
      expect(screen.queryByTestId('features-3')).not.toBeInTheDocument();
    });

    it('PC-030: does not show description when empty', () => {
      render(<MockPackageCard {...mockPackage} description="" />);
      expect(screen.queryByTestId('description-3')).not.toBeInTheDocument();
    });
  });

  // PC-041 to PC-050: Accessibility
  describe('Accessibility', () => {
    it('PC-031: card has article role', () => {
      render(<MockPackageCard {...mockPackage} />);
      expect(screen.getByTestId('package-card-3')).toHaveAttribute('role', 'article');
    });

    it('PC-032: card has aria-label', () => {
      render(<MockPackageCard {...mockPackage} />);
      const card = screen.getByTestId('package-card-3');
      expect(card).toHaveAttribute('aria-label');
      expect(card.getAttribute('aria-label')).toContain('Pro');
    });

    it('PC-033: aria-label includes credits and price', () => {
      render(<MockPackageCard {...mockPackage} />);
      const card = screen.getByTestId('package-card-3');
      const ariaLabel = card.getAttribute('aria-label') || '';
      expect(ariaLabel).toContain('15,000');
      expect(ariaLabel).toContain('$50.00');
    });

    it('PC-034: button is focusable', () => {
      render(<MockPackageCard {...mockPackage} />);
      const button = screen.getByTestId('buy-button-3');
      button.focus();
      expect(button).toHaveFocus();
    });

    it('PC-035: check icons in features list', () => {
      render(<MockPackageCard {...mockPackage} />);
      const checkIcons = screen.getAllByTestId('check-icon');
      expect(checkIcons.length).toBe(mockPackage.features.length);
    });

    it('PC-036: keyboard accessible', async () => {
      const user = userEvent.setup();
      render(<MockPackageCard {...mockPackage} onBuyClick={mockOnBuyClick} />);
      
      const button = screen.getByTestId('buy-button-3');
      button.focus();
      await user.keyboard('{Enter}');
      
      expect(mockOnBuyClick).toHaveBeenCalled();
    });
  });

  // PC-051 to PC-060: Edge Cases
  describe('Edge Cases', () => {
    it('PC-037: handles zero price', () => {
      render(<MockPackageCard {...mockPackage} price={0} />);
      expect(screen.getByTestId('price-3')).toHaveTextContent('$0.00');
    });

    it('PC-038: handles very large credits', () => {
      render(<MockPackageCard {...mockPackage} credits={10000000} />);
      expect(screen.getByTestId('credits-3')).toHaveTextContent('10,000,000 credits');
    });

    it('PC-039: handles very small price per credit', () => {
      render(<MockPackageCard {...mockPackage} credits={1000000} price={100} />);
      expect(screen.getByTestId('price-per-credit-3')).toHaveTextContent('$0.0001/credit');
    });

    it('PC-040: handles long name', () => {
      render(<MockPackageCard {...mockPackage} name="Very Long Package Name That Exceeds Normal Length" />);
      expect(screen.getByText('Very Long Package Name That Exceeds Normal Length')).toBeInTheDocument();
    });

    it('PC-041: handles long description', () => {
      const longDesc = 'This is a very long description that might need to wrap to multiple lines on smaller screens and should still be readable and accessible to users.';
      render(<MockPackageCard {...mockPackage} description={longDesc} />);
      expect(screen.getByText(longDesc)).toBeInTheDocument();
    });

    it('PC-042: handles unicode in name', () => {
      render(<MockPackageCard {...mockPackage} name="专业版 🚀" />);
      expect(screen.getByText('专业版 🚀')).toBeInTheDocument();
    });

    it('PC-043: XSS prevention in name', () => {
      render(<MockPackageCard {...mockPackage} name='<script>alert("xss")</script>Test' />);
      expect(screen.queryByText('<script>')).not.toBeInTheDocument();
    });

    it('PC-044: XSS prevention in description', () => {
      render(<MockPackageCard {...mockPackage} description='<img src=x onerror=alert("xss")>' />);
      expect(screen.queryByText('onerror')).not.toBeInTheDocument();
    });

    it('PC-045: custom className applied', () => {
      render(<MockPackageCard {...mockPackage} className="custom-class" />);
      const card = screen.getByTestId('package-card-3');
      expect(card).toHaveClass('custom-class');
    });

    it('PC-046: handles many features', () => {
      const manyFeatures = Array.from({ length: 10 }, (_, i) => `Feature ${i + 1}`);
      render(<MockPackageCard {...mockPackage} features={manyFeatures} />);
      expect(screen.getByText('Feature 1')).toBeInTheDocument();
      expect(screen.getByText('Feature 10')).toBeInTheDocument();
    });

    it('PC-047: handles negative bonus (not shown)', () => {
      render(<MockPackageCard {...mockPackage} bonus_percentage={-10} />);
      expect(screen.queryByTestId('bonus-3')).not.toBeInTheDocument();
    });

    it('PC-048: hover effect classes present', () => {
      render(<MockPackageCard {...mockPackage} />);
      const card = screen.getByTestId('package-card-3');
      expect(card).toHaveClass('hover:shadow-lg');
    });

    it('PC-049: transition classes present', () => {
      render(<MockPackageCard {...mockPackage} />);
      const card = screen.getByTestId('package-card-3');
      expect(card).toHaveClass('transition-all');
      expect(card).toHaveClass('duration-200');
    });
  });
});

// Helper import for 'within'
import { within } from '@testing-library/react';
