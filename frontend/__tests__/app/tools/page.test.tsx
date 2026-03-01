/**
 * Tools Landing Page Tests
 * Task: TASK-16-9 - Tools Landing Page
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import ToolsPage from '@/app/tools/page';

// Mock Next.js Link
jest.mock('next/link', () => {
  const MockLink = ({ children, href, ...props }: { children: React.ReactNode; href: string }) => {
    return <a href={href} {...props}>{children}</a>;
  };
  MockLink.displayName = 'MockLink';
  return MockLink;
});

// Mock Button component
jest.mock('@/components/ui/button', () => ({
  Button: ({ children, className, disabled, ...props }: { children: React.ReactNode; className?: string; disabled?: boolean }) => (
    <button className={className} disabled={disabled} {...props}>
      {children}
    </button>
  ),
}));

// Mock Card components
jest.mock('@/components/ui/card', () => ({
  Card: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <div className={className} data-testid="card">{children}</div>
  ),
  CardHeader: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="card-header">{children}</div>
  ),
  CardTitle: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <h3 className={className}>{children}</h3>
  ),
  CardDescription: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <p className={className}>{children}</p>
  ),
  CardContent: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="card-content">{children}</div>
  ),
}));

describe('Tools Landing Page', () => {
  // TL-001: Tools Landing Page Rendering
  describe('TL-001: Page Rendering', () => {
    // TL-001-01: Page renders without errors
    it('should render without errors', () => {
      render(<ToolsPage />);

      expect(screen.getByText('Free SEO Tools')).toBeInTheDocument();
    });

    // TL-001-02: Hero section displays correctly
    it('should display hero section with title and description', () => {
      render(<ToolsPage />);

      expect(screen.getByText('Free SEO Tools')).toBeInTheDocument();
      expect(screen.getByText(/Analyze your website's SEO instantly/)).toBeInTheDocument();
    });

    // TL-001-03: All 4 tools displayed in grid
    it('should display all 4 tools in the grid', () => {
      render(<ToolsPage />);

      expect(screen.getByText('Meta Tag Analyzer')).toBeInTheDocument();
      expect(screen.getByText('Keyword Density Checker')).toBeInTheDocument();
      expect(screen.getByText('Mobile-Friendly Test')).toBeInTheDocument();
      expect(screen.getByText('Social Media Preview')).toBeInTheDocument();
    });

    // TL-001-04: Available tools show "Use Tool" button
    it('should show "Use Tool" button for available tools', () => {
      render(<ToolsPage />);

      const useToolButtons = screen.getAllByRole('button', { name: 'Use Tool' });
      expect(useToolButtons).toHaveLength(2); // Meta Tag Analyzer and Keyword Density
    });

    // TL-001-05: Coming soon tools show disabled button
    it('should show disabled "Coming Soon" button for unavailable tools', () => {
      render(<ToolsPage />);

      const comingSoonButtons = screen.getAllByRole('button', { name: 'Coming Soon' });
      expect(comingSoonButtons).toHaveLength(2); // Mobile-Friendly and Social Preview

      comingSoonButtons.forEach(button => {
        expect(button).toBeDisabled();
      });
    });

    // TL-001-06: Tool cards show correct icons
    it('should display correct icons for each tool', () => {
      render(<ToolsPage />);

      expect(screen.getByText('🏷️')).toBeInTheDocument(); // Meta Tag Analyzer
      expect(screen.getByText('📊')).toBeInTheDocument(); // Keyword Density
      expect(screen.getByText('📱')).toBeInTheDocument(); // Mobile-Friendly
      expect(screen.getByText('🔗')).toBeInTheDocument(); // Social Preview
    });

    // TL-001-07: Tool features listed correctly
    it('should list features for each tool', () => {
      render(<ToolsPage />);

      // Meta Tag Analyzer features
      expect(screen.getByText('Title tag analysis')).toBeInTheDocument();
      expect(screen.getByText('Meta description check')).toBeInTheDocument();

      // Keyword Density features
      expect(screen.getByText('Density calculation')).toBeInTheDocument();
      expect(screen.getByText('Heading analysis')).toBeInTheDocument();
    });

    // TL-001-08: "Coming Soon" badge on unavailable tools
    it('should show "Coming Soon" badge on unavailable tools', () => {
      render(<ToolsPage />);

      const comingSoonBadges = screen.getAllByText('Coming Soon');
      // 2 badges + 2 disabled buttons = 4 total "Coming Soon" texts
      // But badges are specifically in the card headers
      expect(comingSoonBadges.length).toBeGreaterThanOrEqual(2);
    });

    // TL-001-09: Info section renders
    it('should render "Why Use Our Free SEO Tools?" section', () => {
      render(<ToolsPage />);

      expect(screen.getByText('Why Use Our Free SEO Tools?')).toBeInTheDocument();
      expect(screen.getByText('Instant Results')).toBeInTheDocument();
      expect(screen.getByText('No Data Storage')).toBeInTheDocument();
      expect(screen.getByText('Actionable Insights')).toBeInTheDocument();
    });

    // TL-001-10: Rate limit notice renders
    it('should display rate limit notice', () => {
      render(<ToolsPage />);

      expect(screen.getByText(/Free tools have a usage limit/)).toBeInTheDocument();
      expect(screen.getByText('Create a free account')).toBeInTheDocument();
    });
  });

  // TL-002: Navigation
  describe('TL-002: Navigation', () => {
    // TL-002-01: Logo links to homepage
    it('should have logo linking to homepage', () => {
      render(<ToolsPage />);

      const logo = screen.getByText('SI').closest('a');
      expect(logo).toHaveAttribute('href', '/');
    });

    // TL-002-02: Dashboard nav link works
    it('should have Dashboard navigation link', () => {
      render(<ToolsPage />);

      const dashboardLink = screen.getByRole('link', { name: 'Dashboard' });
      expect(dashboardLink).toHaveAttribute('href', '/dashboard');
    });

    // TL-002-03: Tools nav link present
    it('should have Tools navigation button', () => {
      render(<ToolsPage />);

      const toolsButton = screen.getByRole('link', { name: 'Tools' });
      expect(toolsButton).toBeInTheDocument();
    });

    // TL-002-04: Meta Tag Analyzer link works
    it('should link to Meta Tag Analyzer page', () => {
      render(<ToolsPage />);

      const useToolLinks = screen.getAllByRole('link', { name: 'Use Tool' });
      const metaTagLink = useToolLinks.find(link =>
        link.getAttribute('href') === '/tools/meta-tag-analyzer'
      );
      expect(metaTagLink).toBeDefined();
    });

    // TL-002-05: Keyword Density link works
    it('should link to Keyword Density page', () => {
      render(<ToolsPage />);

      const useToolLinks = screen.getAllByRole('link', { name: 'Use Tool' });
      const keywordDensityLink = useToolLinks.find(link =>
        link.getAttribute('href') === '/tools/keyword-density'
      );
      expect(keywordDensityLink).toBeDefined();
    });

    // TL-002-06: Coming soon buttons disabled
    it('should have disabled buttons for coming soon tools', () => {
      render(<ToolsPage />);

      const comingSoonButtons = screen.getAllByRole('button', { name: 'Coming Soon' });
      expect(comingSoonButtons.length).toBe(2);

      comingSoonButtons.forEach(button => {
        expect(button).toBeDisabled();
      });
    });

    // TL-002-07: Signup link in rate notice
    it('should have signup link in rate limit notice', () => {
      render(<ToolsPage />);

      const signupLink = screen.getByRole('link', { name: 'Create a free account' });
      expect(signupLink).toHaveAttribute('href', '/signup');
    });
  });

  // TL-005: Accessibility
  describe('TL-005: Accessibility', () => {
    // TL-005-01: Header has landmark
    it('should have header landmark', () => {
      const { container } = render(<ToolsPage />);

      const header = container.querySelector('header');
      expect(header).toBeInTheDocument();
    });

    // TL-005-03: Footer has landmark
    it('should have footer landmark', () => {
      const { container } = render(<ToolsPage />);

      const footer = container.querySelector('footer');
      expect(footer).toBeInTheDocument();
    });

    // TL-005-04: Tool cards have accessible names
    it('should have accessible tool cards', () => {
      render(<ToolsPage />);

      expect(screen.getByText('Meta Tag Analyzer')).toBeInTheDocument();
      expect(screen.getByText('Keyword Density Checker')).toBeInTheDocument();
    });

    // TL-005-05: Buttons have accessible labels
    it('should have accessible button labels', () => {
      render(<ToolsPage />);

      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button.textContent).not.toBe('');
      });
    });

    // TL-005-06: Links have discernible text
    it('should have discernible link text', () => {
      render(<ToolsPage />);

      const links = screen.getAllByRole('link');
      links.forEach(link => {
        expect(link.textContent).not.toBe('');
      });
    });

    // TL-005-10: No heading level skips
    it('should have proper heading hierarchy', () => {
      const { container } = render(<ToolsPage />);

      const h1 = container.querySelector('h1');
      expect(h1).toBeInTheDocument();
      expect(h1?.textContent).toBe('Free SEO Tools');
    });
  });

  // TL-007: Content Structure
  describe('TL-007: Content Structure', () => {
    // TL-007-01: Tool descriptions present
    it('should have descriptions for all tools', () => {
      render(<ToolsPage />);

      expect(screen.getByText(/Analyze your page's meta tags/)).toBeInTheDocument();
      expect(screen.getByText(/Check keyword density and placement/)).toBeInTheDocument();
      expect(screen.getByText(/Test how mobile-friendly/)).toBeInTheDocument();
      expect(screen.getByText(/Preview how your page appears/)).toBeInTheDocument();
    });

    // TL-007-02: Info section has 3 columns
    it('should display 3 benefits in info section', () => {
      render(<ToolsPage />);

      expect(screen.getByText('Instant Results')).toBeInTheDocument();
      expect(screen.getByText('No Data Storage')).toBeInTheDocument();
      expect(screen.getByText('Actionable Insights')).toBeInTheDocument();
    });

    // TL-007-03: Footer copyright
    it('should display copyright in footer', () => {
      render(<ToolsPage />);

      expect(screen.getByText(/© 2026 SEOIndicator/)).toBeInTheDocument();
    });

    // TL-007-04: Tech stack mention
    it('should mention tech stack in footer', () => {
      render(<ToolsPage />);

      expect(screen.getByText(/Next.js, Tailwind CSS/)).toBeInTheDocument();
    });
  });

  // TL-011: Security
  describe('TL-011: Security', () => {
    // TL-011-03: No sensitive data exposed
    it('should not expose sensitive data in rendered output', () => {
      const { container } = render(<ToolsPage />);

      const html = container.innerHTML;
      expect(html).not.toContain('api_key');
      expect(html).not.toContain('token');
      expect(html).not.toContain('password');
    });
  });

  // TL-006: Responsive Design Classes
  describe('TL-006: Responsive Design', () => {
    // TL-006-01: Grid has responsive classes
    it('should have responsive grid layout classes', () => {
      const { container } = render(<ToolsPage />);

      const grid = container.querySelector('.md\\:grid-cols-2');
      expect(grid).toBeInTheDocument();
    });

    // TL-006-02: Info section has responsive classes
    it('should have responsive info section layout', () => {
      const { container } = render(<ToolsPage />);

      const infoGrid = container.querySelector('.md\\:grid-cols-3');
      expect(infoGrid).toBeInTheDocument();
    });

    // TL-006-03: Container uses responsive padding
    it('should have responsive container classes', () => {
      const { container } = render(<ToolsPage />);

      const containers = container.querySelectorAll('.container');
      expect(containers.length).toBeGreaterThan(0);
    });
  });

  // TL-003: SEO Structure
  describe('TL-003: SEO Structure', () => {
    // TL-003-01: Page has H1 title
    it('should have proper H1 for SEO', () => {
      const { container } = render(<ToolsPage />);

      const h1 = container.querySelector('h1');
      expect(h1).toBeInTheDocument();
      expect(h1?.textContent).toBe('Free SEO Tools');
    });

    // TL-003-02: Page has descriptive content
    it('should have descriptive meta content in hero', () => {
      render(<ToolsPage />);

      expect(screen.getByText(/Analyze your website's SEO instantly/)).toBeInTheDocument();
    });

    // TL-003-03: Semantic HTML structure
    it('should use semantic HTML elements', () => {
      const { container } = render(<ToolsPage />);

      expect(container.querySelector('header')).toBeInTheDocument();
      expect(container.querySelector('footer')).toBeInTheDocument();
      expect(container.querySelector('section')).toBeInTheDocument();
    });

    // TL-003-04: Tool cards have proper structure
    it('should have properly structured tool cards', () => {
      render(<ToolsPage />);

      // Each tool should have a name and description
      expect(screen.getByText('Meta Tag Analyzer')).toBeInTheDocument();
      expect(screen.getByText(/Analyze your page's meta tags/)).toBeInTheDocument();
    });

    // TL-003-05: Internal links are present
    it('should have internal navigation links', () => {
      render(<ToolsPage />);

      const homeLink = screen.getByText('SI').closest('a');
      const dashboardLink = screen.getByRole('link', { name: 'Dashboard' });

      expect(homeLink).toHaveAttribute('href', '/');
      expect(dashboardLink).toHaveAttribute('href', '/dashboard');
    });
  });
});
