/**
 * Keyword Density Checker Page Tests
 * Task: TASK-16-9 - Tools Landing Page
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import KeywordDensityPage from '@/app/tools/keyword-density/page';

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
  Button: ({ children, className, variant, ...props }: { children: React.ReactNode; className?: string; variant?: string }) => (
    <button className={className} data-variant={variant} {...props}>
      {children}
    </button>
  ),
}));

// Mock ToolWidget component
jest.mock('@/components/tools/ToolWidget', () => ({
  ToolWidget: ({ tool, title, description, placeholder, showHelp }: {
    tool: string;
    title: string;
    description: string;
    placeholder?: string;
    showHelp?: boolean;
  }) => (
    <div data-testid="tool-widget" data-tool={tool} data-show-help={showHelp}>
      <h2>{title}</h2>
      <p>{description}</p>
      {placeholder && <span data-placeholder={placeholder} />}
    </div>
  ),
}));

describe('Keyword Density Page', () => {
  // TL-008: Individual Tool Page - Keyword Density
  describe('TL-008: Page Rendering', () => {
    // TL-008-01: Page renders correctly
    it('should render without errors', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText('Keyword Density Checker')).toBeInTheDocument();
    });

    // TL-008-02: Breadcrumb navigation
    it('should have "All Tools" breadcrumb link', () => {
      render(<KeywordDensityPage />);

      const allToolsLink = screen.getByRole('link', { name: /All Tools/ });
      expect(allToolsLink).toHaveAttribute('href', '/tools');
    });

    // TL-008-03: ToolWidget configured correctly
    it('should render ToolWidget with correct configuration', () => {
      render(<KeywordDensityPage />);

      const widget = screen.getByTestId('tool-widget');
      expect(widget).toHaveAttribute('data-tool', 'keyword-density');
      expect(widget).toHaveAttribute('data-show-help', 'true');
    });

    // TL-008-04: Keyword input visible
    it('should indicate keyword-density tool for keyword input', () => {
      render(<KeywordDensityPage />);

      const widget = screen.getByTestId('tool-widget');
      expect(widget).toHaveAttribute('data-tool', 'keyword-density');
    });

    // TL-008-05: "What is Keyword Density?" section
    it('should display "What is Keyword Density?" section', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText('What is Keyword Density?')).toBeInTheDocument();
      expect(screen.getByText(/percentage of times a keyword appears/)).toBeInTheDocument();
    });

    // TL-008-06: "What We Analyze" section
    it('should display "What We Analyze" section with 6 items', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText('What We Analyze')).toBeInTheDocument();
      expect(screen.getByText('Keyword Frequency')).toBeInTheDocument();
      expect(screen.getByText('Density Percentage')).toBeInTheDocument();
      expect(screen.getByText('Heading Placement')).toBeInTheDocument();
      expect(screen.getByText('Title & Description')).toBeInTheDocument();
      expect(screen.getByText('First Paragraph')).toBeInTheDocument();
      expect(screen.getByText('Over-Optimization')).toBeInTheDocument();
    });

    // TL-008-07: "Best Practices" section
    it('should display "Best Practices" section', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText(/Best Practices/)).toBeInTheDocument();
      expect(screen.getByText(/Focus on natural language/)).toBeInTheDocument();
    });

    // TL-008-09: Density range indicators
    it('should display density range indicators with correct values', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText('< 1%')).toBeInTheDocument();
      expect(screen.getByText('1-3%')).toBeInTheDocument();
      expect(screen.getByText('> 3%')).toBeInTheDocument();
    });
  });

  describe('Page Structure', () => {
    it('should have proper heading hierarchy', () => {
      const { container } = render(<KeywordDensityPage />);

      const h1 = container.querySelector('h1');
      expect(h1).toBeInTheDocument();
      expect(h1?.textContent).toContain('Keyword Density Checker');
    });

    it('should have header with logo', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText('SI')).toBeInTheDocument();
      expect(screen.getByText('SEOIndicator')).toBeInTheDocument();
    });

    it('should have footer with copyright', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText(/© 2026 SEOIndicator/)).toBeInTheDocument();
    });

    it('should display page description', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText(/Enter a URL and target keyword to analyze/)).toBeInTheDocument();
    });

    it('should show tool icon', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText('📊')).toBeInTheDocument();
    });
  });

  describe('Density Range Indicators', () => {
    it('should show "Too low" for < 1%', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText(/Too low - consider adding more mentions/)).toBeInTheDocument();
    });

    it('should show "Optimal range" for 1-3%', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText(/Optimal range for most content/)).toBeInTheDocument();
    });

    it('should show "Risk of keyword stuffing" for > 3%', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText(/Risk of keyword stuffing penalty/)).toBeInTheDocument();
    });
  });

  describe('Content Sections', () => {
    it('should explain Keyword Frequency analysis', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText(/Count of keyword occurrences in visible content/)).toBeInTheDocument();
    });

    it('should explain Density Percentage analysis', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText(/Ratio of keyword to total word count/)).toBeInTheDocument();
    });

    it('should explain Heading Placement analysis', () => {
      render(<KeywordDensityPage />);

      expect(screen.getByText(/Check if keyword appears in H1, H2, etc./)).toBeInTheDocument();
    });
  });

  describe('Navigation', () => {
    it('should have link back to tools page', () => {
      render(<KeywordDensityPage />);

      const allToolsLink = screen.getByRole('link', { name: /All Tools/ });
      expect(allToolsLink).toHaveAttribute('href', '/tools');
    });

    it('should have Dashboard navigation link', () => {
      render(<KeywordDensityPage />);

      const dashboardLink = screen.getByRole('link', { name: 'Dashboard' });
      expect(dashboardLink).toHaveAttribute('href', '/dashboard');
    });
  });

  describe('Accessibility', () => {
    it('should have header landmark', () => {
      const { container } = render(<KeywordDensityPage />);

      const header = container.querySelector('header');
      expect(header).toBeInTheDocument();
    });

    it('should have main landmark', () => {
      const { container } = render(<KeywordDensityPage />);

      const main = container.querySelector('main');
      expect(main).toBeInTheDocument();
    });

    it('should have footer landmark', () => {
      const { container } = render(<KeywordDensityPage />);

      const footer = container.querySelector('footer');
      expect(footer).toBeInTheDocument();
    });

    it('should have accessible links', () => {
      render(<KeywordDensityPage />);

      const links = screen.getAllByRole('link');
      links.forEach(link => {
        expect(link.textContent).not.toBe('');
      });
    });
  });

  describe('Security', () => {
    it('should not expose sensitive data', () => {
      const { container } = render(<KeywordDensityPage />);

      const html = container.innerHTML;
      expect(html).not.toContain('api_key');
      expect(html).not.toContain('token');
      expect(html).not.toContain('password');
    });
  });
});
