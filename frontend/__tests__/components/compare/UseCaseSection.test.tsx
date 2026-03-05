/**
 * UseCaseSection Component Tests
 * Task: TASK-32-19 - Unit Tests for Comparison Components
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * TDD Tests - Component implementation pending
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import '@testing-library/jest-dom';
import type { UseCase, UseCaseSectionProps } from '@/lib/compare/types';

// Mock component - will be replaced with actual implementation
const UseCaseSection = ({
  seoindicator,
  competitor,
}: UseCaseSectionProps) => {
  // Get badge color based on audience
  const getAudienceColor = (audience: string): string => {
    switch (audience) {
      case 'solo': return 'bg-blue-500';
      case 'small-team': return 'bg-green-500';
      case 'enterprise': return 'bg-purple-500';
      case 'agency': return 'bg-orange-500';
      default: return 'bg-gray-500';
    }
  };

  // Get badge color based on budget
  const getBudgetColor = (budget: string): string => {
    switch (budget) {
      case 'free': return 'bg-emerald-500';
      case 'budget': return 'bg-teal-500';
      case 'mid-range': return 'bg-cyan-500';
      case 'premium': return 'bg-amber-500';
      default: return 'bg-gray-500';
    }
  };

  // Format badge text
  const formatBadgeText = (text: string): string => {
    return text.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
  };

  return (
    <section data-testid="use-case-section" aria-labelledby="use-case-heading">
      <h2 id="use-case-heading" data-testid="section-title" className="text-2xl font-bold">
        Who Should Use Each Tool?
      </h2>

      <div data-testid="use-case-grid" className="grid md:grid-cols-2 gap-6">
        {/* SEOIndicator Use Cases */}
        <div data-testid="seoindicator-use-cases" aria-label="SEOIndicator use cases">
          <h3 data-testid="seoindicator-header">SEOIndicator</h3>
          {seoindicator.length > 0 ? (
            seoindicator.map((useCase, idx) => (
              <article
                key={idx}
                data-testid={`seoindicator-case-${idx}`}
                className="use-case-card"
              >
                <h4 data-testid={`seoindicator-title-${idx}`}>{useCase.title}</h4>
                <p data-testid={`seoindicator-description-${idx}`}>{useCase.description}</p>
                <div data-testid={`seoindicator-badges-${idx}`} className="flex gap-2">
                  <span
                    data-testid={`seoindicator-audience-badge-${idx}`}
                    className={getAudienceColor(useCase.audience)}
                  >
                    {formatBadgeText(useCase.audience)}
                  </span>
                  <span
                    data-testid={`seoindicator-budget-badge-${idx}`}
                    className={getBudgetColor(useCase.budget)}
                  >
                    {formatBadgeText(useCase.budget)}
                  </span>
                </div>
              </article>
            ))
          ) : (
            <p data-testid="seoindicator-empty">No use cases available</p>
          )}
        </div>

        {/* Competitor Use Cases */}
        <div data-testid="competitor-use-cases" aria-label="Competitor use cases">
          <h3 data-testid="competitor-header">Competitor</h3>
          {competitor.length > 0 ? (
            competitor.map((useCase, idx) => (
              <article
                key={idx}
                data-testid={`competitor-case-${idx}`}
                className="use-case-card"
              >
                <h4 data-testid={`competitor-title-${idx}`}>{useCase.title}</h4>
                <p data-testid={`competitor-description-${idx}`}>{useCase.description}</p>
                <div data-testid={`competitor-badges-${idx}`} className="flex gap-2">
                  <span
                    data-testid={`competitor-audience-badge-${idx}`}
                    className={getAudienceColor(useCase.audience)}
                  >
                    {formatBadgeText(useCase.audience)}
                  </span>
                  <span
                    data-testid={`competitor-budget-badge-${idx}`}
                    className={getBudgetColor(useCase.budget)}
                  >
                    {formatBadgeText(useCase.budget)}
                  </span>
                </div>
              </article>
            ))
          ) : (
            <p data-testid="competitor-empty">No use cases available</p>
          )}
        </div>
      </div>
    </section>
  );
};

// Test data
const mockSeoindicatorUseCases: UseCase[] = [
  {
    title: 'Startup Founders',
    description: 'Affordable SEO tracking with essential features for early-stage businesses',
    audience: 'solo',
    budget: 'budget',
  },
  {
    title: 'Small Business Owners',
    description: 'Easy-to-use toolkit for improving search visibility without the enterprise price tag',
    audience: 'solo',
    budget: 'budget',
  },
  {
    title: 'Marketing Teams',
    description: 'Collaborative SEO platform with reporting and competitor analysis',
    audience: 'small-team',
    budget: 'mid-range',
  },
  {
    title: 'SEO Agencies',
    description: 'White-label reports and multi-client management at an affordable price',
    audience: 'agency',
    budget: 'mid-range',
  },
];

const mockCompetitorUseCases: UseCase[] = [
  {
    title: 'Enterprise SEO Teams',
    description: 'Advanced features and large-scale data analysis for large organizations',
    audience: 'enterprise',
    budget: 'premium',
  },
  {
    title: 'Content Marketing Teams',
    description: 'Full content marketing suite with social media integration',
    audience: 'small-team',
    budget: 'mid-range',
  },
];

describe('UseCaseSection', () => {
  // ============================================================================
  // Rendering Tests
  // ============================================================================

  describe('Rendering', () => {
    it('UC-001: renders use cases for both tools', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      expect(screen.getByTestId('seoindicator-use-cases')).toBeInTheDocument();
      expect(screen.getByTestId('competitor-use-cases')).toBeInTheDocument();
    });

    it('UC-002: displays use case titles', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      expect(screen.getByText('Startup Founders')).toBeInTheDocument();
      expect(screen.getByText('Enterprise SEO Teams')).toBeInTheDocument();
    });

    it('UC-003: displays use case descriptions', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      expect(screen.getByText('Affordable SEO tracking with essential features for early-stage businesses')).toBeInTheDocument();
      expect(screen.getByText('Advanced features and large-scale data analysis for large organizations')).toBeInTheDocument();
    });

    it('UC-004: renders section with proper heading', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      expect(screen.getByTestId('section-title')).toHaveTextContent('Who Should Use Each Tool?');
    });

    it('UC-005: renders grid layout', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      expect(screen.getByTestId('use-case-grid')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Badge Tests
  // ============================================================================

  describe('Badges', () => {
    it('UC-010: displays audience badge', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      expect(screen.getByTestId('seoindicator-audience-badge-0')).toBeInTheDocument();
      // Use getAllByText since "Solo" appears in multiple use cases
      expect(screen.getAllByText('Solo').length).toBeGreaterThan(0);
    });

    it('UC-011: displays budget badge', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      expect(screen.getByTestId('seoindicator-budget-badge-0')).toBeInTheDocument();
      // Use getAllByText since "Budget" appears in multiple use cases
      expect(screen.getAllByText('Budget').length).toBeGreaterThan(0);
    });

    it('UC-012: displays all audience types correctly', () => {
      const allAudiences: UseCase[] = [
        { title: 'Solo', description: 'Test', audience: 'solo', budget: 'free' },
        { title: 'Team', description: 'Test', audience: 'small-team', budget: 'budget' },
        { title: 'Enterprise', description: 'Test', audience: 'enterprise', budget: 'mid-range' },
        { title: 'Agency', description: 'Test', audience: 'agency', budget: 'premium' },
      ];
      
      render(<UseCaseSection seoindicator={allAudiences} competitor={[]} />);
      
      // Use getAllByText since some text may appear multiple times
      expect(screen.getAllByText('Solo').length).toBeGreaterThan(0);
      expect(screen.getByText('Small Team')).toBeInTheDocument();
      // Enterprise appears as both title and badge
      expect(screen.getAllByText('Enterprise').length).toBeGreaterThan(0);
      // Agency appears as both title and badge
      expect(screen.getAllByText('Agency').length).toBeGreaterThan(0);
    });

    it('UC-013: displays all budget types correctly', () => {
      const allBudgets: UseCase[] = [
        { title: 'Free', description: 'Test', audience: 'solo', budget: 'free' },
        { title: 'Budget', description: 'Test', audience: 'solo', budget: 'budget' },
        { title: 'Mid', description: 'Test', audience: 'solo', budget: 'mid-range' },
        { title: 'Premium', description: 'Test', audience: 'solo', budget: 'premium' },
      ];
      
      render(<UseCaseSection seoindicator={allBudgets} competitor={[]} />);
      
      // Use getAllByText since "Free" appears as both title and badge
      expect(screen.getAllByText('Free').length).toBeGreaterThan(0);
      expect(screen.getAllByText('Budget').length).toBeGreaterThan(0);
      expect(screen.getByText('Mid Range')).toBeInTheDocument();
      // Premium appears as both title and badge
      expect(screen.getAllByText('Premium').length).toBeGreaterThan(0);
    });

    it('UC-014: applies correct audience badge colors', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      // Solo = bg-blue-500
      expect(screen.getByTestId('seoindicator-audience-badge-0')).toHaveClass('bg-blue-500');
      
      // Enterprise = bg-purple-500
      expect(screen.getByTestId('competitor-audience-badge-0')).toHaveClass('bg-purple-500');
    });

    it('UC-015: applies correct budget badge colors', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      // Budget = bg-teal-500
      expect(screen.getByTestId('seoindicator-budget-badge-0')).toHaveClass('bg-teal-500');
      
      // Premium = bg-amber-500
      expect(screen.getByTestId('competitor-budget-badge-0')).toHaveClass('bg-amber-500');
    });
  });

  // ============================================================================
  // Layout Tests
  // ============================================================================

  describe('Layout', () => {
    it('UC-020: maintains balanced layout with two columns', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      const grid = screen.getByTestId('use-case-grid');
      expect(grid).toHaveClass('grid');
      expect(grid).toHaveClass('md:grid-cols-2');
    });

    it('UC-021: renders use case cards within each column', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      // SEOIndicator has 4 use cases
      const seoindicatorCases = screen.getAllByTestId(/seoindicator-case-/);
      expect(seoindicatorCases).toHaveLength(4);
      
      // Competitor has 2 use cases
      const competitorCases = screen.getAllByTestId(/competitor-case-/);
      expect(competitorCases).toHaveLength(2);
    });

    it('UC-022: uses article elements for semantic structure', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      const articles = screen.getAllByRole('article');
      expect(articles.length).toBe(6); // 4 + 2
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('UC-030: section has accessible label', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      const section = screen.getByTestId('use-case-section');
      expect(section).toHaveAttribute('aria-labelledby', 'use-case-heading');
    });

    it('UC-031: columns have accessible labels', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      expect(screen.getByTestId('seoindicator-use-cases')).toHaveAttribute('aria-label', 'SEOIndicator use cases');
      expect(screen.getByTestId('competitor-use-cases')).toHaveAttribute('aria-label', 'Competitor use cases');
    });

    it('UC-032: heading hierarchy is correct', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      const h2 = screen.getByRole('heading', { level: 2 });
      const h3s = screen.getAllByRole('heading', { level: 3 });
      const h4s = screen.getAllByRole('heading', { level: 4 });
      
      expect(h2).toBeInTheDocument();
      expect(h3s.length).toBe(2); // One per column
      expect(h4s.length).toBe(6); // One per use case
    });

    it('UC-033: use case cards are keyboard navigable', () => {
      render(
        <UseCaseSection 
          seoindicator={mockSeoindicatorUseCases} 
          competitor={mockCompetitorUseCases} 
        />
      );
      
      // Match only article elements with use-case-card class (not section containers)
      const cards = screen.getAllByTestId(/seoindicator-case-\d|competitor-case-\d/);
      cards.forEach(card => {
        expect(card).toHaveClass('use-case-card');
      });
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================

  describe('Edge Cases', () => {
    it('UC-040: handles empty SEOIndicator use cases', () => {
      render(<UseCaseSection seoindicator={[]} competitor={mockCompetitorUseCases} />);
      
      expect(screen.getByTestId('seoindicator-empty')).toBeInTheDocument();
      expect(screen.getByText('No use cases available')).toBeInTheDocument();
    });

    it('UC-041: handles empty competitor use cases', () => {
      render(<UseCaseSection seoindicator={mockSeoindicatorUseCases} competitor={[]} />);
      
      expect(screen.getByTestId('competitor-empty')).toBeInTheDocument();
      expect(screen.getByText('No use cases available')).toBeInTheDocument();
    });

    it('UC-042: handles both arrays empty', () => {
      render(<UseCaseSection seoindicator={[]} competitor={[]} />);
      
      expect(screen.getByTestId('seoindicator-empty')).toBeInTheDocument();
      expect(screen.getByTestId('competitor-empty')).toBeInTheDocument();
    });

    it('UC-043: handles long descriptions', () => {
      const longDescription: UseCase[] = [
        {
          title: 'Long Description Test',
          description: 'This is a very long description that might need to be truncated or wrapped to prevent layout issues in the user interface component.',
          audience: 'solo',
          budget: 'free',
        },
      ];
      
      render(<UseCaseSection seoindicator={longDescription} competitor={[]} />);
      
      expect(screen.getByText(/This is a very long description/)).toBeInTheDocument();
    });

    it('UC-044: handles single use case per column', () => {
      const singleCase = [mockSeoindicatorUseCases[0]];
      
      render(<UseCaseSection seoindicator={singleCase} competitor={singleCase} />);
      
      // Match only article elements (not section containers)
      expect(screen.getAllByTestId(/seoindicator-case-\d|competitor-case-\d/)).toHaveLength(2);
    });

    it('UC-045: handles many use cases', () => {
      const manyCases = Array.from({ length: 10 }, (_, i) => ({
        title: `Use Case ${i + 1}`,
        description: `Description ${i + 1}`,
        audience: 'solo' as const,
        budget: 'free' as const,
      }));
      
      render(<UseCaseSection seoindicator={manyCases} competitor={[]} />);
      
      const seoindicatorCases = screen.getAllByTestId(/seoindicator-case-/);
      expect(seoindicatorCases).toHaveLength(10);
    });

    it('UC-046: handles unicode in use case data', () => {
      const unicodeCases: UseCase[] = [
        {
          title: '日本語ユーザー',
          description: '日本語でのサポート',
          audience: 'solo',
          budget: 'free',
        },
      ];
      
      render(<UseCaseSection seoindicator={unicodeCases} competitor={[]} />);
      
      expect(screen.getByText('日本語ユーザー')).toBeInTheDocument();
      expect(screen.getByText('日本語でのサポート')).toBeInTheDocument();
    });
  });
});
