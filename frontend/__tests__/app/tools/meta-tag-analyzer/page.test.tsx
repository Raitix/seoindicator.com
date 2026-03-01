/**
 * Meta Tag Analyzer Page Tests
 * Task: TASK-16-9 - Tools Landing Page
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import MetaTagAnalyzerPage from '@/app/tools/meta-tag-analyzer/page';

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

describe('Meta Tag Analyzer Page', () => {
  // TL-007: Individual Tool Page - Meta Tag Analyzer
  describe('TL-007: Page Rendering', () => {
    // TL-007-01: Page renders correctly
    it('should render without errors', () => {
      render(<MetaTagAnalyzerPage />);

      expect(screen.getByText('Meta Tag Analyzer')).toBeInTheDocument();
    });

    // TL-007-02: Breadcrumb navigation
    it('should have "All Tools" breadcrumb link', () => {
      render(<MetaTagAnalyzerPage />);

      const allToolsLink = screen.getByRole('link', { name: /All Tools/ });
      expect(allToolsLink).toHaveAttribute('href', '/tools');
    });

    // TL-007-03: ToolWidget configured correctly
    it('should render ToolWidget with correct configuration', () => {
      render(<MetaTagAnalyzerPage />);

      const widget = screen.getByTestId('tool-widget');
      expect(widget).toHaveAttribute('data-tool', 'meta-tag-analyzer');
      expect(widget).toHaveAttribute('data-show-help', 'true');
    });

    // TL-007-04: "What We Analyze" section
    it('should display "What We Analyze" section with 6 items', () => {
      render(<MetaTagAnalyzerPage />);

      expect(screen.getByText('What We Analyze')).toBeInTheDocument();
      expect(screen.getByText('Title Tag')).toBeInTheDocument();
      expect(screen.getByText('Meta Description')).toBeInTheDocument();
      expect(screen.getByText('Open Graph Tags')).toBeInTheDocument();
      expect(screen.getByText('Twitter Card Tags')).toBeInTheDocument();
      expect(screen.getByText('Canonical URL')).toBeInTheDocument();
      expect(screen.getByText('Robots Directives')).toBeInTheDocument();
    });

    // TL-007-05: "Quick Tips" section
    it('should display "Quick Tips" section with tips', () => {
      render(<MetaTagAnalyzerPage />);

      expect(screen.getByText(/Quick Tips/)).toBeInTheDocument();
      expect(screen.getByText(/Keep your title tag between 50-60 characters/)).toBeInTheDocument();
      expect(screen.getByText(/Write compelling meta descriptions/)).toBeInTheDocument();
    });

    // TL-007-08: Link to /tools works
    it('should have link back to tools page', () => {
      render(<MetaTagAnalyzerPage />);

      const allToolsLink = screen.getByRole('link', { name: /All Tools/ });
      expect(allToolsLink).toHaveAttribute('href', '/tools');
    });

    // TL-007-09: Dashboard link works
    it('should have Dashboard navigation link', () => {
      render(<MetaTagAnalyzerPage />);

      const dashboardLink = screen.getByRole('link', { name: 'Dashboard' });
      expect(dashboardLink).toHaveAttribute('href', '/dashboard');
    });
  });

  describe('Page Structure', () => {
    it('should have proper heading hierarchy', () => {
      const { container } = render(<MetaTagAnalyzerPage />);

      const h1 = container.querySelector('h1');
      expect(h1).toBeInTheDocument();
      expect(h1?.textContent).toContain('Meta Tag Analyzer');
    });

    it('should have header with logo', () => {
      render(<MetaTagAnalyzerPage />);

      expect(screen.getByText('SI')).toBeInTheDocument();
      expect(screen.getByText('SEOIndicator')).toBeInTheDocument();
    });

    it('should have footer with copyright', () => {
      render(<MetaTagAnalyzerPage />);

      expect(screen.getByText(/© 2026 SEOIndicator/)).toBeInTheDocument();
    });

    it('should display page description', () => {
      render(<MetaTagAnalyzerPage />);

      expect(screen.getByText(/Enter any URL to instantly analyze its meta tags/)).toBeInTheDocument();
    });

    it('should show tool icon', () => {
      render(<MetaTagAnalyzerPage />);

      expect(screen.getByText('🏷️')).toBeInTheDocument();
    });
  });

  describe('Content Sections', () => {
    it('should explain what Title Tag analysis includes', () => {
      render(<MetaTagAnalyzerPage />);

      expect(screen.getByText(/Length check \(30-60 characters recommended\)/)).toBeInTheDocument();
    });

    it('should explain what Meta Description analysis includes', () => {
      render(<MetaTagAnalyzerPage />);

      expect(screen.getByText(/Length check \(120-160 characters recommended\)/)).toBeInTheDocument();
    });

    it('should have Google SERP Preview section', () => {
      render(<MetaTagAnalyzerPage />);

      expect(screen.getByText('Google SERP Preview')).toBeInTheDocument();
      expect(screen.getByText(/See how your page will appear in Google search results/)).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    it('should have header landmark', () => {
      const { container } = render(<MetaTagAnalyzerPage />);

      const header = container.querySelector('header');
      expect(header).toBeInTheDocument();
    });

    it('should have main landmark', () => {
      const { container } = render(<MetaTagAnalyzerPage />);

      const main = container.querySelector('main');
      expect(main).toBeInTheDocument();
    });

    it('should have footer landmark', () => {
      const { container } = render(<MetaTagAnalyzerPage />);

      const footer = container.querySelector('footer');
      expect(footer).toBeInTheDocument();
    });

    it('should have accessible links', () => {
      render(<MetaTagAnalyzerPage />);

      const links = screen.getAllByRole('link');
      links.forEach(link => {
        expect(link.textContent).not.toBe('');
      });
    });
  });

  describe('Security', () => {
    it('should not expose sensitive data', () => {
      const { container } = render(<MetaTagAnalyzerPage />);

      const html = container.innerHTML;
      expect(html).not.toContain('api_key');
      expect(html).not.toContain('token');
      expect(html).not.toContain('password');
    });
  });
});
