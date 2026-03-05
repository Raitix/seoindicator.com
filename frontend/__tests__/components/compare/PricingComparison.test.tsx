/**
 * PricingComparison Component Tests
 * Task: TASK-32-19 - Unit Tests for Comparison Components
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * TDD Tests - Component implementation pending
 */

import React from 'react';
import { render, screen, within, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import type { PricingTier, PricingComparisonProps } from '@/lib/compare/types';

// Mock Lucide React icons
jest.mock('lucide-react', () => ({
  Check: () => <span data-testid="check-icon">✓</span>,
  Star: () => <span data-testid="star-icon">★</span>,
  Sparkles: () => <span data-testid="sparkles-icon">✨</span>,
}));

// Mock component - will be replaced with actual implementation
const PricingComparison = ({
  seoindicator,
  competitor,
  showFreeTier = true,
}: PricingComparisonProps) => {
  const [isAnnual, setIsAnnual] = React.useState(false);

  // Calculate savings for annual pricing
  const calculateSavings = (monthly: number, annual: number): number => {
    if (monthly === 0 || annual === 0) return 0;
    return Math.round(((monthly * 12 - annual * 12) / (monthly * 12)) * 100);
  };

  // Format price
  const formatPrice = (price: number | 'Free'): string => {
    if (price === 'Free') return 'Free';
    return `$${price}`;
  };

  const seoindicatorTiers = seoindicator || [];
  const competitorTiers = competitor || [];

  // Filter free tier if not shown
  const filteredSeoindicator = showFreeTier 
    ? seoindicatorTiers 
    : seoindicatorTiers.filter(t => t.monthlyPrice !== 'Free');
  const filteredCompetitor = showFreeTier 
    ? competitorTiers 
    : competitorTiers.filter(t => t.monthlyPrice !== 'Free');

  return (
    <div data-testid="pricing-comparison" aria-label="Pricing comparison">
      {/* Toggle */}
      <div data-testid="pricing-toggle" role="group" aria-label="Billing period">
        <button
          data-testid="monthly-toggle"
          onClick={() => setIsAnnual(false)}
          aria-pressed={!isAnnual}
          className={!isAnnual ? 'active' : ''}
        >
          Monthly
        </button>
        <button
          data-testid="annual-toggle"
          onClick={() => setIsAnnual(true)}
          aria-pressed={isAnnual}
          className={isAnnual ? 'active' : ''}
        >
          Annual
        </button>
      </div>

      {/* Pricing Cards */}
      <div data-testid="pricing-grid" className="grid grid-cols-2 gap-4">
        {/* SEOIndicator Column */}
        <div data-testid="seoindicator-pricing" aria-label="SEOIndicator pricing tiers">
          <h3 data-testid="seoindicator-header">SEOIndicator</h3>
          {filteredSeoindicator.map((tier, idx) => (
            <div
              key={tier.name}
              data-testid={`seoindicator-tier-${idx}`}
              className={tier.highlighted ? 'highlighted' : ''}
            >
              <h4 data-testid={`tier-name-${idx}`}>{tier.name}</h4>
              <div data-testid={`tier-price-${idx}`}>
                {formatPrice(isAnnual ? tier.annualPrice : tier.monthlyPrice)}
                {isAnnual && tier.monthlyPrice !== 'Free' && typeof tier.monthlyPrice === 'number' && typeof tier.annualPrice === 'number' && (
                  <span data-testid={`savings-badge-${idx}`}>
                    Save {calculateSavings(tier.monthlyPrice, tier.annualPrice)}%
                  </span>
                )}
              </div>
              {tier.monthlyPrice === 'Free' && (
                <span data-testid={`free-badge-${idx}`} className="free-tier-badge">
                  Free Forever
                </span>
              )}
              {tier.highlighted && (
                <span data-testid={`popular-badge-${idx}`}>Popular</span>
              )}
              <ul data-testid={`tier-features-${idx}`}>
                {tier.features.map((feature, fIdx) => (
                  <li key={fIdx} data-testid={`feature-${idx}-${fIdx}`}>
                    <span data-testid="check-icon">✓</span> {feature}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        {/* Competitor Column */}
        <div data-testid="competitor-pricing" aria-label="Competitor pricing tiers">
          <h3 data-testid="competitor-header">Competitor</h3>
          {filteredCompetitor.map((tier, idx) => (
            <div
              key={tier.name}
              data-testid={`competitor-tier-${idx}`}
              className={tier.highlighted ? 'highlighted' : ''}
            >
              <h4 data-testid={`competitor-tier-name-${idx}`}>{tier.name}</h4>
              <div data-testid={`competitor-tier-price-${idx}`}>
                {formatPrice(isAnnual ? tier.annualPrice : tier.monthlyPrice)}
                {isAnnual && tier.monthlyPrice !== 'Free' && typeof tier.monthlyPrice === 'number' && typeof tier.annualPrice === 'number' && (
                  <span data-testid={`competitor-savings-badge-${idx}`}>
                    Save {calculateSavings(tier.monthlyPrice, tier.annualPrice)}%
                  </span>
                )}
              </div>
              {tier.highlighted && (
                <span data-testid={`competitor-popular-badge-${idx}`}>Popular</span>
              )}
              <ul data-testid={`competitor-tier-features-${idx}`}>
                {tier.features.map((feature, fIdx) => (
                  <li key={fIdx} data-testid={`competitor-feature-${idx}-${fIdx}`}>
                    <span data-testid="check-icon">✓</span> {feature}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

// Test data
const mockSeoindicatorTiers: PricingTier[] = [
  {
    name: 'Free',
    monthlyPrice: 'Free',
    annualPrice: 'Free',
    features: ['1 project', '10 keywords', 'Basic tracking'],
  },
  {
    name: 'Pro',
    monthlyPrice: 19,
    annualPrice: 15,
    highlighted: true,
    features: ['5 projects', '100 keywords', 'Daily tracking', 'Competitor analysis'],
  },
  {
    name: 'Business',
    monthlyPrice: 49,
    annualPrice: 39,
    features: ['20 projects', '500 keywords', 'Team collaboration'],
  },
];

const mockCompetitorTiers: PricingTier[] = [
  {
    name: 'Lite',
    monthlyPrice: 29,
    annualPrice: 24,
    features: ['1 project', '25 keywords', 'Weekly tracking'],
  },
  {
    name: 'Standard',
    monthlyPrice: 99,
    annualPrice: 79,
    highlighted: true,
    features: ['5 projects', '200 keywords', 'Daily tracking'],
  },
  {
    name: 'Enterprise',
    monthlyPrice: 199,
    annualPrice: 159,
    features: ['Unlimited projects', 'API access', 'Priority support'],
  },
];

describe('PricingComparison', () => {
  // ============================================================================
  // Rendering Tests
  // ============================================================================

  describe('Rendering', () => {
    it('PC-001: renders pricing tiers for both tools', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      expect(screen.getByTestId('seoindicator-pricing')).toBeInTheDocument();
      expect(screen.getByTestId('competitor-pricing')).toBeInTheDocument();
    });

    it('PC-002: displays tier names for SEOIndicator', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      expect(screen.getByText('Free')).toBeInTheDocument();
      expect(screen.getByText('Pro')).toBeInTheDocument();
      expect(screen.getByText('Business')).toBeInTheDocument();
    });

    it('PC-003: displays tier names for competitor', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      expect(screen.getByText('Lite')).toBeInTheDocument();
      expect(screen.getByText('Standard')).toBeInTheDocument();
      expect(screen.getByText('Enterprise')).toBeInTheDocument();
    });

    it('PC-004: renders pricing toggle', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      expect(screen.getByTestId('pricing-toggle')).toBeInTheDocument();
      expect(screen.getByTestId('monthly-toggle')).toBeInTheDocument();
      expect(screen.getByTestId('annual-toggle')).toBeInTheDocument();
    });

    it('PC-005: renders grid layout for pricing cards', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      expect(screen.getByTestId('pricing-grid')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Toggle Tests
  // ============================================================================

  describe('Toggle', () => {
    it('PC-010: toggles between monthly and annual view', async () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      // Default: Monthly
      expect(screen.getByTestId('monthly-toggle')).toHaveAttribute('aria-pressed', 'true');
      expect(screen.getByTestId('annual-toggle')).toHaveAttribute('aria-pressed', 'false');
      
      // Click annual toggle
      await userEvent.click(screen.getByTestId('annual-toggle'));
      
      expect(screen.getByTestId('annual-toggle')).toHaveAttribute('aria-pressed', 'true');
      expect(screen.getByTestId('monthly-toggle')).toHaveAttribute('aria-pressed', 'false');
    });

    it('PC-011: shows monthly prices by default', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      // Pro tier: monthly $19, annual $15
      expect(screen.getByTestId('tier-price-1')).toHaveTextContent('$19');
    });

    it('PC-012: shows annual prices after toggle', async () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      await userEvent.click(screen.getByTestId('annual-toggle'));
      
      // Pro tier: annual $15
      expect(screen.getByTestId('tier-price-1')).toHaveTextContent('$15');
    });

    it('PC-013: toggle buttons have accessible aria-pressed', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      const monthlyBtn = screen.getByTestId('monthly-toggle');
      const annualBtn = screen.getByTestId('annual-toggle');
      
      expect(monthlyBtn).toHaveAttribute('aria-pressed', 'true');
      expect(annualBtn).toHaveAttribute('aria-pressed', 'false');
    });
  });

  // ============================================================================
  // Free Tier Tests
  // ============================================================================

  describe('Free Tier', () => {
    it('PC-020: highlights free tier with badge', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
          showFreeTier={true}
        />
      );
      
      expect(screen.getByTestId('free-badge-0')).toBeInTheDocument();
      expect(screen.getByText('Free Forever')).toBeInTheDocument();
    });

    it('PC-021: shows "Free" text for free tiers', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      // Free tier price should show "Free" not "$0"
      expect(screen.getByTestId('tier-price-0')).toHaveTextContent('Free');
    });

    it('PC-022: hides free tier when showFreeTier is false', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
          showFreeTier={false}
        />
      );
      
      // Free tier should not be visible
      expect(screen.queryByTestId('free-badge-0')).not.toBeInTheDocument();
      // Pro should be first tier (index 0)
      expect(screen.getByTestId('tier-name-0')).toHaveTextContent('Pro');
    });

    it('PC-023: free tier does not show savings badge', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      // Free tier is index 0, should not have savings badge
      expect(screen.queryByTestId('savings-badge-0')).not.toBeInTheDocument();
    });
  });

  // ============================================================================
  // Calculations Tests
  // ============================================================================

  describe('Calculations', () => {
    it('PC-030: calculates annual savings correctly', async () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      await userEvent.click(screen.getByTestId('annual-toggle'));
      
      // Pro tier: monthly $19, annual $15
      // Savings: (19*12 - 15*12) / (19*12) = (228 - 180) / 228 = 21%
      expect(screen.getByTestId('savings-badge-1')).toHaveTextContent('Save 21%');
    });

    it('PC-031: shows savings badge only in annual mode', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      // Monthly mode: no savings badge
      expect(screen.queryByTestId('savings-badge-1')).not.toBeInTheDocument();
    });

    it('PC-032: calculates competitor savings correctly', async () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      await userEvent.click(screen.getByTestId('annual-toggle'));
      
      // Lite tier: monthly $29, annual $24
      // Savings: (29*12 - 24*12) / (29*12) = (348 - 288) / 348 = 17%
      expect(screen.getByTestId('competitor-savings-badge-0')).toHaveTextContent('Save 17%');
    });
  });

  // ============================================================================
  // Formatting Tests
  // ============================================================================

  describe('Formatting', () => {
    it('PC-040: formats prices with dollar sign', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      expect(screen.getByTestId('tier-price-1')).toHaveTextContent('$19');
      expect(screen.getByTestId('competitor-tier-price-0')).toHaveTextContent('$29');
    });

    it('PC-041: handles "Free" price correctly', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      // Should show "Free" not "$Free" or "$0"
      expect(screen.getByTestId('tier-price-0')).toHaveTextContent('Free');
    });

    it('PC-042: formats prices consistently across both columns', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      // Both should use same format ($X)
      const seoindicatorPrice = screen.getByTestId('tier-price-1').textContent;
      const competitorPrice = screen.getByTestId('competitor-tier-price-0').textContent;
      
      expect(seoindicatorPrice).toMatch(/^\$\d+/);
      expect(competitorPrice).toMatch(/^\$\d+/);
    });
  });

  // ============================================================================
  // Features Tests
  // ============================================================================

  describe('Features', () => {
    it('PC-050: lists tier features', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      const proFeatures = screen.getByTestId('tier-features-1');
      expect(within(proFeatures).getAllByRole('listitem')).toHaveLength(4);
    });

    it('PC-051: highlights popular tier with badge', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      // Pro tier (index 1) is highlighted
      expect(screen.getByTestId('popular-badge-1')).toBeInTheDocument();
      expect(screen.getByText('Popular')).toBeInTheDocument();
    });

    it('PC-052: applies highlighted class to popular tier', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      const proTier = screen.getByTestId('seoindicator-tier-1');
      expect(proTier).toHaveClass('highlighted');
    });

    it('PC-053: displays features with check icons', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      const proFeatures = screen.getByTestId('tier-features-1');
      const checkIcons = within(proFeatures).getAllByTestId('check-icon');
      
      expect(checkIcons.length).toBe(4);
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('PC-060: toggle buttons are accessible', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      const toggleGroup = screen.getByTestId('pricing-toggle');
      expect(toggleGroup).toHaveAttribute('role', 'group');
      expect(toggleGroup).toHaveAttribute('aria-label', 'Billing period');
    });

    it('PC-061: toggle buttons can be activated by keyboard', async () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      const annualBtn = screen.getByTestId('annual-toggle');
      annualBtn.focus();
      
      await userEvent.keyboard('{Enter}');
      
      expect(annualBtn).toHaveAttribute('aria-pressed', 'true');
    });

    it('PC-062: pricing columns have accessible labels', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      expect(screen.getByTestId('seoindicator-pricing')).toHaveAttribute('aria-label', 'SEOIndicator pricing tiers');
      expect(screen.getByTestId('competitor-pricing')).toHaveAttribute('aria-label', 'Competitor pricing tiers');
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================

  describe('Edge Cases', () => {
    it('PC-070: handles empty pricing arrays', () => {
      render(
        <PricingComparison 
          seoindicator={[]} 
          competitor={[]} 
        />
      );
      
      // Should render structure but no tiers
      expect(screen.getByTestId('pricing-comparison')).toBeInTheDocument();
    });

    it('PC-071: handles zero prices', () => {
      const zeroPriceTiers: PricingTier[] = [
        {
          name: 'Zero',
          monthlyPrice: 0,
          annualPrice: 0,
          features: ['Free stuff'],
        },
      ];
      
      render(
        <PricingComparison 
          seoindicator={zeroPriceTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      expect(screen.getByTestId('tier-price-0')).toHaveTextContent('$0');
    });

    it('PC-072: handles large prices with proper formatting', () => {
      const enterpriseTiers: PricingTier[] = [
        {
          name: 'Enterprise',
          monthlyPrice: 1000,
          annualPrice: 800,
          features: ['Everything'],
        },
      ];
      
      render(
        <PricingComparison 
          seoindicator={enterpriseTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      expect(screen.getByTestId('tier-price-0')).toHaveTextContent('$1000');
    });

    it('PC-073: handles missing features array', () => {
      const noFeaturesTiers: PricingTier[] = [
        {
          name: 'Basic',
          monthlyPrice: 10,
          annualPrice: 8,
          features: [],
        },
      ];
      
      render(
        <PricingComparison 
          seoindicator={noFeaturesTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      const featuresList = screen.getByTestId('tier-features-0');
      expect(within(featuresList).queryAllByRole('listitem')).toHaveLength(0);
    });

    it('PC-074: handles single tier', () => {
      const singleTier = [mockSeoindicatorTiers[0]];
      
      render(
        <PricingComparison 
          seoindicator={singleTier} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      expect(screen.getByTestId('seoindicator-tier-0')).toBeInTheDocument();
      expect(screen.queryByTestId('seoindicator-tier-1')).not.toBeInTheDocument();
    });

    it('PC-075: handles same monthly and annual prices', async () => {
      const samePriceTiers: PricingTier[] = [
        {
          name: 'Same Price',
          monthlyPrice: 50,
          annualPrice: 50,
          features: ['Feature'],
        },
      ];
      
      render(
        <PricingComparison 
          seoindicator={samePriceTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      await userEvent.click(screen.getByTestId('annual-toggle'));
      
      // 0% savings when prices are same
      expect(screen.getByTestId('savings-badge-0')).toHaveTextContent('Save 0%');
    });
  });

  // ============================================================================
  // Props Tests
  // ============================================================================

  describe('Props', () => {
    it('PC-080: uses default value for showFreeTier', () => {
      render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
        />
      );
      
      // Default: showFreeTier=true
      expect(screen.getByTestId('free-badge-0')).toBeInTheDocument();
    });

    it('PC-081: respects showFreeTier prop', () => {
      const { rerender } = render(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
          showFreeTier={true}
        />
      );
      
      expect(screen.getByTestId('free-badge-0')).toBeInTheDocument();
      
      rerender(
        <PricingComparison 
          seoindicator={mockSeoindicatorTiers} 
          competitor={mockCompetitorTiers} 
          showFreeTier={false}
        />
      );
      
      expect(screen.queryByTestId('free-badge-0')).not.toBeInTheDocument();
    });
  });
});
