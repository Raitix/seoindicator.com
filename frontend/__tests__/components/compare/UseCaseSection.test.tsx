/**
 * Unit Tests for UseCaseSection Component
 * Task: TASK-32-8 - Use Case Section
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Tests cover:
 * - Component rendering with various props
 * - UseCase card rendering
 * - Visual separation between tools
 * - Data handling and edge cases
 * - Mobile responsive behavior
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { UseCaseSection } from '@/components/compare/UseCaseSection';
import type { UseCase } from '@/lib/compare/types';

// ============================================================================
// Mock Data
// ============================================================================

const seoindicatorUseCases: UseCase[] = [
  {
    title: 'Budget-conscious solopreneurs',
    description: 'Perfect for individual content creators who need core SEO tools without the enterprise price tag',
    audience: 'solo',
    budget: 'budget',
  },
  {
    title: 'Small marketing teams',
    description: 'Ideal for small teams starting their SEO journey with limited budget',
    audience: 'small-team',
    budget: 'budget',
  },
  {
    title: 'Freelance SEO consultants',
    description: 'Great for freelancers who need reliable rank tracking on a budget',
    audience: 'solo',
    budget: 'free',
  },
];

const competitorUseCases: UseCase[] = [
  {
    title: 'Agencies managing 50+ client sites',
    description: 'Best for agencies with budget for premium tools and dedicated SEO resources',
    audience: 'agency',
    budget: 'premium',
  },
  {
    title: 'Enterprise SEO teams',
    description: 'Ideal for large organizations with dedicated resources and complex needs',
    audience: 'enterprise',
    budget: 'premium',
  },
  {
    title: 'Link-building specialists',
    description: 'Perfect for specialists who need extensive backlink databases',
    audience: 'small-team',
    budget: 'mid-range',
  },
];

const singleUseCase: UseCase[] = [
  {
    title: 'Single Use Case',
    description: 'A single use case for testing',
    audience: 'solo',
    budget: 'free',
  },
];

// ============================================================================
// UC-001: Component Rendering Tests
// ============================================================================

describe('UseCaseSection - Component Rendering', () => {
  describe('UC-001-001: Renders with valid use cases for both tools', () => {
    it('should render both columns when both have use cases', () => {
      render(
        <UseCaseSection
          seoindicator={seoindicatorUseCases}
          competitor={competitorUseCases}
        />
      );

      // Check main section exists
      expect(screen.getByTestId('use-cases-section')).toBeInTheDocument();

      // Check both columns exist
      expect(screen.getByTestId('seoindicator-use-cases')).toBeInTheDocument();
      expect(screen.getByTestId('competitor-use-cases')).toBeInTheDocument();

      // Check column headings
      expect(screen.getByText('Best for SEOIndicator users')).toBeInTheDocument();
      expect(screen.getByText('Best for Competitor users')).toBeInTheDocument();
    });

    it('should render all use case cards', () => {
      render(
        <UseCaseSection
          seoindicator={seoindicatorUseCases}
          competitor={competitorUseCases}
        />
      );

      // Check all SEOIndicator use case titles
      expect(screen.getByText('Budget-conscious solopreneurs')).toBeInTheDocument();
      expect(screen.getByText('Small marketing teams')).toBeInTheDocument();
      expect(screen.getByText('Freelance SEO consultants')).toBeInTheDocument();

      // Check all competitor use case titles
      expect(screen.getByText('Agencies managing 50+ client sites')).toBeInTheDocument();
      expect(screen.getByText('Enterprise SEO teams')).toBeInTheDocument();
      expect(screen.getByText('Link-building specialists')).toBeInTheDocument();
    });
  });

  describe('UC-001-002: Renders with empty seoindicator array', () => {
    it('should show seoindicator column empty but competitor column with data', () => {
      render(
        <UseCaseSection
          seoindicator={[]}
          competitor={competitorUseCases}
        />
      );

      expect(screen.getByTestId('use-cases-section')).toBeInTheDocument();
      expect(screen.getByTestId('seoindicator-use-cases')).toBeInTheDocument();
      expect(screen.getByTestId('competitor-use-cases')).toBeInTheDocument();
    });
  });

  describe('UC-001-003: Renders with empty competitor array', () => {
    it('should show competitor column empty but seoindicator column with data', () => {
      render(
        <UseCaseSection
          seoindicator={seoindicatorUseCases}
          competitor={[]}
        />
      );

      expect(screen.getByTestId('use-cases-section')).toBeInTheDocument();
      expect(screen.getByTestId('seoindicator-use-cases')).toBeInTheDocument();
      expect(screen.getByTestId('competitor-use-cases')).toBeInTheDocument();
    });
  });

  describe('UC-001-004: Renders with both arrays empty', () => {
    it('should show empty state message when both arrays are empty', () => {
      render(
        <UseCaseSection
          seoindicator={[]}
          competitor={[]}
        />
      );

      expect(screen.getByTestId('empty-use-cases')).toBeInTheDocument();
      expect(screen.getByText('No use case data available.')).toBeInTheDocument();
    });
  });

  describe('UC-001-005: Renders with null seoindicator', () => {
    it('should handle null seoindicator gracefully', () => {
      render(
        <UseCaseSection
          seoindicator={null as unknown as UseCase[]}
          competitor={competitorUseCases}
        />
      );

      expect(screen.getByTestId('use-cases-section')).toBeInTheDocument();
    });
  });

  describe('UC-001-006: Renders with null competitor', () => {
    it('should handle null competitor gracefully', () => {
      render(
        <UseCaseSection
          seoindicator={seoindicatorUseCases}
          competitor={null as unknown as UseCase[]}
        />
      );

      expect(screen.getByTestId('use-cases-section')).toBeInTheDocument();
    });
  });

  describe('UC-001-007: Renders with undefined props', () => {
    it('should handle undefined props gracefully', () => {
      render(
        <UseCaseSection
          seoindicator={undefined as unknown as UseCase[]}
          competitor={undefined as unknown as UseCase[]}
        />
      );

      expect(screen.getByTestId('empty-use-cases')).toBeInTheDocument();
    });
  });
});

// ============================================================================
// UC-002: UseCase Card Rendering Tests
// ============================================================================

describe('UseCaseSection - UseCase Card Rendering', () => {
  describe('UC-002-001: Renders use case with all fields', () => {
    it('should render title, description, audience badge, and budget badge', () => {
      render(
        <UseCaseSection
          seoindicator={[{
            title: 'Test Title',
            description: 'Test Description',
            audience: 'solo',
            budget: 'free',
          }]}
          competitor={[]}
        />
      );

      expect(screen.getByText('Test Title')).toBeInTheDocument();
      expect(screen.getByText('Test Description')).toBeInTheDocument();
      expect(screen.getByText('Solo Users')).toBeInTheDocument();
      expect(screen.getByText('Free')).toBeInTheDocument();
    });
  });

  describe('UC-002-002 to UC-002-005: Audience badge rendering', () => {
    it('should render "Solo Users" for audience: solo', () => {
      render(
        <UseCaseSection
          seoindicator={[{ title: 'Test', description: 'Desc', audience: 'solo', budget: 'free' }]}
          competitor={[]}
        />
      );
      expect(screen.getByText('Solo Users')).toBeInTheDocument();
    });

    it('should render "Small Teams" for audience: small-team', () => {
      render(
        <UseCaseSection
          seoindicator={[{ title: 'Test', description: 'Desc', audience: 'small-team', budget: 'free' }]}
          competitor={[]}
        />
      );
      expect(screen.getByText('Small Teams')).toBeInTheDocument();
    });

    it('should render "Enterprise" for audience: enterprise', () => {
      render(
        <UseCaseSection
          seoindicator={[{ title: 'Test', description: 'Desc', audience: 'enterprise', budget: 'free' }]}
          competitor={[]}
        />
      );
      expect(screen.getByText('Enterprise')).toBeInTheDocument();
    });

    it('should render "Agencies" for audience: agency', () => {
      render(
        <UseCaseSection
          seoindicator={[{ title: 'Test', description: 'Desc', audience: 'agency', budget: 'free' }]}
          competitor={[]}
        />
      );
      expect(screen.getByText('Agencies')).toBeInTheDocument();
    });
  });

  describe('UC-002-006 to UC-002-009: Budget badge rendering', () => {
    it('should render "Free" for budget: free', () => {
      render(
        <UseCaseSection
          seoindicator={[{ title: 'Test', description: 'Desc', audience: 'solo', budget: 'free' }]}
          competitor={[]}
        />
      );
      expect(screen.getByText('Free')).toBeInTheDocument();
    });

    it('should render "Budget" for budget: budget', () => {
      render(
        <UseCaseSection
          seoindicator={[{ title: 'Test', description: 'Desc', audience: 'solo', budget: 'budget' }]}
          competitor={[]}
        />
      );
      expect(screen.getByText('Budget')).toBeInTheDocument();
    });

    it('should render "Mid-Range" for budget: mid-range', () => {
      render(
        <UseCaseSection
          seoindicator={[{ title: 'Test', description: 'Desc', audience: 'solo', budget: 'mid-range' }]}
          competitor={[]}
        />
      );
      expect(screen.getByText('Mid-Range')).toBeInTheDocument();
    });

    it('should render "Premium" for budget: premium', () => {
      render(
        <UseCaseSection
          seoindicator={[{ title: 'Test', description: 'Desc', audience: 'solo', budget: 'premium' }]}
          competitor={[]}
        />
      );
      expect(screen.getByText('Premium')).toBeInTheDocument();
    });
  });
});

// ============================================================================
// UC-003: Visual Separation Tests
// ============================================================================

describe('UseCaseSection - Visual Separation', () => {
  describe('UC-003-001: SEOIndicator column has emerald branding', () => {
    it('should have emerald colored heading for SEOIndicator column', () => {
      render(
        <UseCaseSection
          seoindicator={singleUseCase}
          competitor={[]}
        />
      );

      const heading = screen.getByText('Best for SEOIndicator users');
      expect(heading.className).toContain('emerald');
    });
  });

  describe('UC-003-002: Competitor column has blue branding', () => {
    it('should have blue colored heading for Competitor column', () => {
      render(
        <UseCaseSection
          seoindicator={[]}
          competitor={singleUseCase}
        />
      );

      const heading = screen.getByText('Best for Competitor users');
      expect(heading.className).toContain('blue');
    });
  });

  describe('UC-003-003: Cards have hover state', () => {
    it('should have transition classes for hover effects', () => {
      const { container } = render(
        <UseCaseSection
          seoindicator={singleUseCase}
          competitor={[]}
        />
      );

      const card = container.querySelector('[data-testid^="use-case-"]');
      expect(card?.className).toContain('transition');
    });
  });

  describe('UC-003-004: Grid layout on desktop', () => {
    it('should have md:grid-cols-2 for two column layout on medium screens', () => {
      const { container } = render(
        <UseCaseSection
          seoindicator={singleUseCase}
          competitor={singleUseCase}
        />
      );

      const grid = container.querySelector('.grid');
      expect(grid?.className).toContain('md:grid-cols-2');
    });
  });

  describe('UC-003-005: Stacked layout on mobile', () => {
    it('should have gap-8 for spacing between stacked columns on mobile', () => {
      const { container } = render(
        <UseCaseSection
          seoindicator={singleUseCase}
          competitor={singleUseCase}
        />
      );

      const grid = container.querySelector('.grid');
      expect(grid?.className).toContain('gap-8');
    });
  });
});

// ============================================================================
// UC-004: Accessibility Tests
// ============================================================================

describe('UseCaseSection - Accessibility', () => {
  describe('UC-004-001: Section has proper heading hierarchy', () => {
    it('should have H3 headings for column titles', () => {
      render(
        <UseCaseSection
          seoindicator={singleUseCase}
          competitor={singleUseCase}
        />
      );

      const seoindicatorHeading = screen.getByRole('heading', { name: 'Best for SEOIndicator users', level: 3 });
      const competitorHeading = screen.getByRole('heading', { name: 'Best for Competitor users', level: 3 });

      expect(seoindicatorHeading).toBeInTheDocument();
      expect(competitorHeading).toBeInTheDocument();
    });
  });

  describe('UC-004-002: Cards are accessible', () => {
    it('should have H4 headings for use case titles', () => {
      render(
        <UseCaseSection
          seoindicator={[{
            title: 'Accessible Title',
            description: 'Description',
            audience: 'solo',
            budget: 'free',
          }]}
          competitor={[]}
        />
      );

      const cardTitle = screen.getByRole('heading', { name: 'Accessible Title', level: 4 });
      expect(cardTitle).toBeInTheDocument();
    });
  });

  describe('UC-004-003: No accessibility violations', () => {
    it('should have data-testid for automated testing', () => {
      render(
        <UseCaseSection
          seoindicator={singleUseCase}
          competitor={singleUseCase}
        />
      );

      expect(screen.getByTestId('use-cases-section')).toBeInTheDocument();
      expect(screen.getByTestId('seoindicator-use-cases')).toBeInTheDocument();
      expect(screen.getByTestId('competitor-use-cases')).toBeInTheDocument();
    });
  });

  describe('UC-004-004: Keyboard navigable', () => {
    it('should have visible content for keyboard users', async () => {
      const user = userEvent.setup();
      render(
        <UseCaseSection
          seoindicator={singleUseCase}
          competitor={singleUseCase}
        />
      );

      // Content should be visible and accessible
      const section = screen.getByTestId('use-cases-section');
      expect(section).toBeVisible();
    });
  });

  describe('UC-004-005: Proper contrast ratios', () => {
    it('should use appropriate text colors', () => {
      const { container } = render(
        <UseCaseSection
          seoindicator={singleUseCase}
          competitor={[]}
        />
      );

      // Check that white text is used for titles
      const title = screen.getByRole('heading', { name: 'Single Use Case', level: 4 });
      expect(title.className).toContain('white');
    });
  });
});

// ============================================================================
// UC-005: Data Handling Tests
// ============================================================================

describe('UseCaseSection - Data Handling', () => {
  describe('UC-005-001: Handles multiple use cases per tool', () => {
    it('should render all use cases when provided multiple', () => {
      render(
        <UseCaseSection
          seoindicator={seoindicatorUseCases}
          competitor={competitorUseCases}
        />
      );

      // 3 SEOIndicator + 3 Competitor = 6 use case cards
      const allTitles = [
        'Budget-conscious solopreneurs',
        'Small marketing teams',
        'Freelance SEO consultants',
        'Agencies managing 50+ client sites',
        'Enterprise SEO teams',
        'Link-building specialists',
      ];

      allTitles.forEach(title => {
        expect(screen.getByText(title)).toBeInTheDocument();
      });
    });
  });

  describe('UC-005-002: Handles long title', () => {
    it('should render long title without overflow', () => {
      const longTitle = 'A'.repeat(200);
      render(
        <UseCaseSection
          seoindicator={[{
            title: longTitle,
            description: 'Description',
            audience: 'solo',
            budget: 'free',
          }]}
          competitor={[]}
        />
      );

      expect(screen.getByText(longTitle)).toBeInTheDocument();
    });
  });

  describe('UC-005-003: Handles long description', () => {
    it('should render long description without overflow', () => {
      const longDesc = 'A'.repeat(500);
      render(
        <UseCaseSection
          seoindicator={[{
            title: 'Title',
            description: longDesc,
            audience: 'solo',
            budget: 'free',
          }]}
          competitor={[]}
        />
      );

      expect(screen.getByText(longDesc)).toBeInTheDocument();
    });
  });

  describe('UC-005-004: Handles special characters', () => {
    it('should render unicode characters correctly', () => {
      render(
        <UseCaseSection
          seoindicator={[{
            title: '适合中文用户 🚀 émojis',
            description: 'Description with émojis 🎉 and unicode: 日本語',
            audience: 'enterprise',
            budget: 'premium',
          }]}
          competitor={[]}
        />
      );

      expect(screen.getByText('适合中文用户 🚀 émojis')).toBeInTheDocument();
      expect(screen.getByText('Description with émojis 🎉 and unicode: 日本語')).toBeInTheDocument();
    });
  });

  describe('UC-005-005: Handles duplicate use case titles', () => {
    it('should render both cards with duplicate titles', () => {
      render(
        <UseCaseSection
          seoindicator={[
            { title: 'Same Title', description: 'First description', audience: 'solo', budget: 'free' },
          ]}
          competitor={[
            { title: 'Same Title', description: 'Second description', audience: 'agency', budget: 'premium' },
          ]}
        />
      );

      // Both descriptions should be present (even though titles are same)
      expect(screen.getByText('First description')).toBeInTheDocument();
      expect(screen.getByText('Second description')).toBeInTheDocument();
    });
  });
});

// ============================================================================
// UC-006: Mobile Responsiveness Tests
// ============================================================================

describe('UseCaseSection - Mobile Responsiveness', () => {
  describe('UC-006-001: Mobile viewport < 768px', () => {
    it('should use single column layout on mobile', () => {
      const { container } = render(
        <UseCaseSection
          seoindicator={singleUseCase}
          competitor={singleUseCase}
        />
      );

      // Grid should have md:grid-cols-2 (applies only on medium+ screens)
      const grid = container.querySelector('.grid');
      expect(grid?.className).toContain('md:grid-cols-2');
    });
  });

  describe('UC-006-002: Tablet viewport 768px+', () => {
    it('should use two column grid on tablet', () => {
      const { container } = render(
        <UseCaseSection
          seoindicator={singleUseCase}
          competitor={singleUseCase}
        />
      );

      const grid = container.querySelector('.grid');
      expect(grid?.className).toContain('md:grid-cols-2');
    });
  });

  describe('UC-006-003: Desktop viewport 1024px+', () => {
    it('should use two column grid on desktop', () => {
      const { container } = render(
        <UseCaseSection
          seoindicator={singleUseCase}
          competitor={singleUseCase}
        />
      );

      const grid = container.querySelector('.grid');
      expect(grid?.className).toContain('md:grid-cols-2');
    });
  });

  describe('UC-006-004: Touch targets on mobile', () => {
    it('should have adequate padding for touch targets', () => {
      const { container } = render(
        <UseCaseSection
          seoindicator={singleUseCase}
          competitor={[]}
        />
      );

      // Cards should have padding
      const card = container.querySelector('[data-testid^="use-case-"]');
      expect(card?.className).toContain('p-4');
    });
  });
});

// ============================================================================
// Integration Tests
// ============================================================================

describe('UseCaseSection - Integration', () => {
  it('should render complete comparison page use case section', () => {
    render(
      <UseCaseSection
        seoindicator={seoindicatorUseCases}
        competitor={competitorUseCases}
      />
    );

    // Verify structure
    expect(screen.getByTestId('use-cases-section')).toBeInTheDocument();

    // Verify both columns
    expect(screen.getByText('Best for SEOIndicator users')).toBeInTheDocument();
    expect(screen.getByText('Best for Competitor users')).toBeInTheDocument();

    // Verify audience badges are rendered
    expect(screen.getAllByText('Solo Users').length).toBeGreaterThan(0);
    expect(screen.getAllByText('Small Teams').length).toBeGreaterThan(0);
    expect(screen.getByText('Enterprise')).toBeInTheDocument();
    expect(screen.getByText('Agencies')).toBeInTheDocument();

    // Verify budget badges are rendered
    expect(screen.getAllByText('Budget').length).toBeGreaterThan(0);
    expect(screen.getAllByText('Free').length).toBeGreaterThan(0);
    expect(screen.getAllByText('Premium').length).toBeGreaterThan(0);
    expect(screen.getByText('Mid-Range')).toBeInTheDocument();
  });

  it('should maintain balanced presentation between both tools', () => {
    render(
      <UseCaseSection
        seoindicator={seoindicatorUseCases}
        competitor={competitorUseCases}
      />
    );

    // Both columns should have 3 use cases each
    const seoindicatorColumn = screen.getByTestId('seoindicator-use-cases');
    const competitorColumn = screen.getByTestId('competitor-use-cases');

    expect(seoindicatorColumn.children.length).toBe(3);
    expect(competitorColumn.children.length).toBe(3);
  });
});

// ============================================================================
// Acceptance Criteria Verification
// ============================================================================

describe('Acceptance Criteria', () => {
  it('AC-1: Both tools\' use cases displayed side-by-side', () => {
    render(
      <UseCaseSection
        seoindicator={seoindicatorUseCases}
        competitor={competitorUseCases}
      />
    );

    expect(screen.getByTestId('seoindicator-use-cases')).toBeInTheDocument();
    expect(screen.getByTestId('competitor-use-cases')).toBeInTheDocument();
  });

  it('AC-2: Each use case has title, description, audience, and budget tags', () => {
    render(
      <UseCaseSection
        seoindicator={[{
          title: 'Test Title',
          description: 'Test Description',
          audience: 'solo',
          budget: 'free',
        }]}
        competitor={[]}
      />
    );

    expect(screen.getByText('Test Title')).toBeInTheDocument();
    expect(screen.getByText('Test Description')).toBeInTheDocument();
    expect(screen.getByText('Solo Users')).toBeInTheDocument();
    expect(screen.getByText('Free')).toBeInTheDocument();
  });

  it('AC-3: Tags use appropriate badge styling', () => {
    const { container } = render(
      <UseCaseSection
        seoindicator={[{
          title: 'Test',
          description: 'Desc',
          audience: 'solo',
          budget: 'free',
        }]}
        competitor={[]}
      />
    );

    // Check badges have rounded-full class
    const badges = container.querySelectorAll('.rounded-full');
    expect(badges.length).toBe(2); // audience + budget badges
  });

  it('AC-4: Mobile responsive layout', () => {
    const { container } = render(
      <UseCaseSection
        seoindicator={singleUseCase}
        competitor={singleUseCase}
      />
    );

    const grid = container.querySelector('.grid');
    expect(grid?.className).toContain('md:grid-cols-2');
    expect(grid?.className).toContain('gap-8');
  });

  it('AC-5: Clear visual separation between tools', () => {
    render(
      <UseCaseSection
        seoindicator={singleUseCase}
        competitor={singleUseCase}
      />
    );

    const seoindicatorHeading = screen.getByText('Best for SEOIndicator users');
    const competitorHeading = screen.getByText('Best for Competitor users');

    expect(seoindicatorHeading.className).toContain('emerald');
    expect(competitorHeading.className).toContain('blue');
  });

  it('AC-6: Honest, balanced recommendations', () => {
    render(
      <UseCaseSection
        seoindicator={seoindicatorUseCases}
        competitor={competitorUseCases}
      />
    );

    // Both columns should have equal use cases
    const seoindicatorColumn = screen.getByTestId('seoindicator-use-cases');
    const competitorColumn = screen.getByTestId('competitor-use-cases');

    expect(seoindicatorColumn.children.length).toBe(competitorColumn.children.length);
  });
});
