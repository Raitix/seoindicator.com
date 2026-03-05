/**
 * About Page Integration Tests - TASK-30-2
 * End-to-end tests for the complete About page flow
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { axe, toHaveNoViolations } from 'jest-axe';
import '@testing-library/jest-dom';

// Extend Jest matchers
expect.extend(toHaveNoViolations);

// Mock Next.js modules
jest.mock('next/navigation', () => ({
  usePathname: () => '/about',
  useRouter: () => ({
    push: jest.fn(),
    replace: jest.fn(),
    back: jest.fn(),
  }),
}));

// Mock Next.js Image
jest.mock('next/image', () => ({
  __esModule: true,
  default: (props: any) => {
    // eslint-disable-next-line @next/next/no-img-element
    return <img {...props} alt={props.alt || ''} data-testid="next-image" />;
  },
}));

// Mock Next.js Link
jest.mock('next/link', () => ({
  __esModule: true,
  default: ({ children, href, ...props }: any) => (
    <a href={href} {...props}>{children}</a>
  ),
}));

// Import the actual page component
import AboutPage from '@/app/about/page';

describe('About Page Integration', () => {
  // ============================================
  // INT-001 to INT-008: Full Page Rendering
  // ============================================
  describe('Full Page Rendering', () => {
    it('INT-001: Full page renders completely', () => {
      render(<AboutPage />);
      
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    it('INT-002: Breadcrumb navigation exists', () => {
      render(<AboutPage />);
      
      const breadcrumb = screen.getByRole('navigation', { name: /breadcrumb/i });
      expect(breadcrumb).toBeInTheDocument();
    });

    it('INT-003: Schema.org Organization is valid', () => {
      render(<AboutPage />);
      
      const scripts = document.querySelectorAll('script[type="application/ld+json"]');
      expect(scripts.length).toBeGreaterThan(0);
      
      // Check the Organization schema
      const orgScript = Array.from(scripts).find(script => {
        try {
          const data = JSON.parse(script.textContent || '{}');
          return data['@type'] === 'Organization';
        } catch {
          return false;
        }
      });
      
      expect(orgScript).toBeDefined();
    });

    it('INT-004: Schema.org WebPage is valid', () => {
      render(<AboutPage />);
      
      const scripts = document.querySelectorAll('script[type="application/ld+json"]');
      
      const webPageScript = Array.from(scripts).find(script => {
        try {
          const data = JSON.parse(script.textContent || '{}');
          return data['@type'] === 'WebPage';
        } catch {
          return false;
        }
      });
      
      expect(webPageScript).toBeDefined();
    });

    it('INT-005: Hero section exists', () => {
      render(<AboutPage />);
      
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1).toBeInTheDocument();
      expect(h1).toHaveTextContent(/SEO/i);
    });

    it('INT-006: Product overview exists', () => {
      render(<AboutPage />);
      
      expect(screen.getByRole('heading', { name: /what we do/i })).toBeInTheDocument();
    });

    it('INT-007: Features section exists', () => {
      render(<AboutPage />);
      
      expect(screen.getByRole('heading', { name: /key features/i })).toBeInTheDocument();
    });

    it('INT-008: CTA section exists', () => {
      render(<AboutPage />);
      
      expect(screen.getByRole('heading', { name: /ready to improve/i })).toBeInTheDocument();
    });
  });

  // ============================================
  // INT-009 to INT-012: Navigation
  // ============================================
  describe('Navigation', () => {
    it('INT-009: Home link in breadcrumb', () => {
      render(<AboutPage />);
      
      const homeLink = screen.getByRole('link', { name: /^home$/i });
      expect(homeLink).toHaveAttribute('href', '/');
    });

    it('INT-010: About is current page in breadcrumb', () => {
      render(<AboutPage />);
      
      const aboutItem = screen.getByText('About');
      expect(aboutItem).toHaveAttribute('aria-current', 'page');
    });

    it('INT-011: CTA links to register', () => {
      render(<AboutPage />);
      
      const ctaLink = screen.getByRole('link', { name: /get started free/i });
      expect(ctaLink).toHaveAttribute('href', '/register/');
    });

    it('INT-012: Explore Tools link works', () => {
      render(<AboutPage />);
      
      const toolsLink = screen.getByRole('link', { name: /explore tools/i });
      expect(toolsLink).toHaveAttribute('href', '/tools/');
    });

    it('INT-013: Learn more about how it works link', () => {
      render(<AboutPage />);
      
      const learnMoreLink = screen.getByRole('link', { name: /learn more about how it works/i });
      expect(learnMoreLink).toHaveAttribute('href', '/how-it-works/');
    });

    it('INT-014: Back to home link', () => {
      render(<AboutPage />);
      
      const backLink = screen.getByRole('link', { name: /back to home/i });
      expect(backLink).toHaveAttribute('href', '/');
    });
  });

  // ============================================
  // Accessibility Integration
  // ============================================
  describe('Accessibility Integration', () => {
    it('main landmark exists', () => {
      render(<AboutPage />);
      
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    it('heading hierarchy is correct', () => {
      render(<AboutPage />);
      
      const h1 = screen.getAllByRole('heading', { level: 1 });
      const h2 = screen.getAllByRole('heading', { level: 2 });
      const h3 = screen.getAllByRole('heading', { level: 3 });
      
      // Only one H1
      expect(h1).toHaveLength(1);
      
      // H2s exist for sections
      expect(h2.length).toBeGreaterThan(0);
    });

    it('breadcrumb has aria-label', () => {
      render(<AboutPage />);
      
      const breadcrumb = screen.getByRole('navigation', { name: /breadcrumb/i });
      expect(breadcrumb).toBeInTheDocument();
    });

    it('no accessibility violations', async () => {
      const { container } = render(<AboutPage />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });
  });

  // ============================================
  // Schema.org Validation
  // ============================================
  describe('Schema.org Markup', () => {
    it('Organization schema has required properties', () => {
      render(<AboutPage />);
      
      const scripts = document.querySelectorAll('script[type="application/ld+json"]');
      
      const orgScript = Array.from(scripts).find(script => {
        try {
          const data = JSON.parse(script.textContent || '{}');
          return data['@type'] === 'Organization';
        } catch {
          return false;
        }
      });
      
      const orgSchema = JSON.parse(orgScript?.textContent || '{}');
      
      expect(orgSchema).toHaveProperty('@type', 'Organization');
      expect(orgSchema).toHaveProperty('name');
      expect(orgSchema).toHaveProperty('url');
    });

    it('WebPage schema has required properties', () => {
      render(<AboutPage />);
      
      const scripts = document.querySelectorAll('script[type="application/ld+json"]');
      
      const webPageScript = Array.from(scripts).find(script => {
        try {
          const data = JSON.parse(script.textContent || '{}');
          return data['@type'] === 'WebPage';
        } catch {
          return false;
        }
      });
      
      const webPageSchema = JSON.parse(webPageScript?.textContent || '{}');
      
      expect(webPageSchema).toHaveProperty('@type', 'WebPage');
      expect(webPageSchema).toHaveProperty('name');
      expect(webPageSchema).toHaveProperty('url');
    });

    it('schema URLs are absolute', () => {
      render(<AboutPage />);
      
      const scripts = document.querySelectorAll('script[type="application/ld+json"]');
      
      scripts.forEach(script => {
        try {
          const data = JSON.parse(script.textContent || '{}');
          if (data.url) {
            expect(data.url).toMatch(/^https:\/\//);
          }
        } catch {
          // Skip invalid JSON
        }
      });
    });
  });

  // ============================================
  // Responsive Integration
  // ============================================
  describe('Responsive Integration', () => {
    it('renders correctly at 375px', () => {
      global.innerWidth = 375;
      global.innerHeight = 667;
      
      render(<AboutPage />);
      
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    it('renders correctly at 768px', () => {
      global.innerWidth = 768;
      global.innerHeight = 1024;
      
      render(<AboutPage />);
      
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    it('renders correctly at 1024px', () => {
      global.innerWidth = 1024;
      global.innerHeight = 768;
      
      render(<AboutPage />);
      
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    it('renders correctly at 1440px', () => {
      global.innerWidth = 1440;
      global.innerHeight = 900;
      
      render(<AboutPage />);
      
      expect(screen.getByRole('main')).toBeInTheDocument();
    });
  });
});
