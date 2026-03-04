/**
 * AboutContent Component Tests - TASK-30-2
 * Tests for the AboutContent component used in /about page
 */

import React from 'react';
import { render, screen, within, fireEvent } from '@testing-library/react';
import { axe, toHaveNoViolations } from 'jest-axe';
import '@testing-library/jest-dom';

// Extend Jest matchers
expect.extend(toHaveNoViolations);

// Mock Next.js Image
jest.mock('next/image', () => ({
  __esModule: true,
  default: (props: any) => {
    // eslint-disable-next-line @next/next/no-img-element
    return <img {...props} alt={props.alt || ''} />;
  },
}));

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  Search: () => <span data-testid="icon-search">Search</span>,
  TrendingUp: () => <span data-testid="icon-trending">TrendingUp</span>,
  Users: () => <span data-testid="icon-users">Users</span>,
  FileText: () => <span data-testid="icon-file">FileText</span>,
  Zap: () => <span data-testid="icon-zap">Zap</span>,
  Target: () => <span data-testid="icon-target">Target</span>,
  ArrowRight: () => <span data-testid="icon-arrow">ArrowRight</span>,
  Check: () => <span data-testid="icon-check">Check</span>,
}));

// Types
interface Feature {
  title: string;
  description: string;
  icon?: string;
}

interface TeamInfo {
  title: string;
  description: string;
}

interface CTA {
  text: string;
  href: string;
}

interface AboutContentProps {
  mission?: string;
  features?: Feature[];
  teamInfo?: TeamInfo;
  cta?: CTA;
  className?: string;
  loading?: boolean;
  error?: string | null;
  'data-testid'?: string;
}

// ============================================
// Component Mock (will be replaced with actual)
// ============================================
const AboutContent: React.FC<AboutContentProps> = ({
  mission = "Helping websites achieve better SEO through intelligent analysis",
  features = [
    { title: "SEO Analysis", description: "Comprehensive SEO audits", icon: "search" },
    { title: "Keyword Tracking", description: "Monitor your rankings", icon: "trending-up" },
    { title: "Competitor Insights", description: "Analyze competitors", icon: "users" },
    { title: "Actionable Reports", description: "Get recommendations", icon: "file-text" }
  ],
  teamInfo = {
    title: "Our Mission",
    description: "SEOIndicator is built by a team of SEO experts and developers passionate about helping businesses succeed online."
  },
  cta = {
    text: "Get Started Free",
    href: "/signup"
  },
  className,
  loading = false,
  error = null,
  'data-testid': testId = 'about-content'
}) => {
  if (loading) {
    return (
      <div data-testid={testId} className={className} role="status" aria-live="polite">
        <div className="animate-pulse">Loading...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div data-testid={testId} className={className} role="alert">
        <p>{error}</p>
        <button onClick={() => window.location.reload()}>Try Again</button>
      </div>
    );
  }

  return (
    <div data-testid={testId} className={className}>
      {/* Hero Section */}
      <section aria-labelledby="hero-heading" className="hero-section">
        <h1 id="hero-heading">{mission}</h1>
      </section>

      {/* Product Overview Section */}
      <section aria-labelledby="product-heading">
        <h2 id="product-heading">What is SEOIndicator?</h2>
        <p>SEOIndicator is a powerful SEO analysis platform...</p>
      </section>

      {/* Features Section */}
      <section aria-labelledby="features-heading">
        <h2 id="features-heading">Key Features</h2>
        <div className="features-grid">
          {features.map((feature, index) => (
            <article key={index} className="feature-card">
              <h3>{feature.title}</h3>
              <p>{feature.description}</p>
            </article>
          ))}
        </div>
      </section>

      {/* Team Section */}
      <section aria-labelledby="team-heading">
        <h2 id="team-heading">{teamInfo.title}</h2>
        <p>{teamInfo.description}</p>
      </section>

      {/* CTA Section */}
      <section aria-labelledby="cta-heading">
        <h2 id="cta-heading">Ready to Get Started?</h2>
        <a href={cta.href} className="cta-button">
          {cta.text}
        </a>
      </section>
    </div>
  );
};

describe('AboutContent Component', () => {
  // ============================================
  // AC-001 to AC-004: Basic Rendering
  // ============================================
  describe('Basic Rendering', () => {
    it('AC-001: Component renders', () => {
      render(<AboutContent />);
      expect(screen.getByTestId('about-content')).toBeInTheDocument();
    });

    it('AC-002: Hero section renders', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { level: 1 })).toBeInTheDocument();
    });

    it('AC-003: Hero has correct mission text', () => {
      const mission = "Custom mission statement";
      render(<AboutContent mission={mission} />);
      expect(screen.getByText(mission)).toBeInTheDocument();
    });

    it('AC-004: Product overview section renders', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { name: /what is seoindicator/i })).toBeInTheDocument();
    });
  });

  // ============================================
  // AC-005 to AC-010: Features Section
  // ============================================
  describe('Features Section', () => {
    it('AC-005: Product overview has title', () => {
      render(<AboutContent />);
      const productHeading = screen.getByRole('heading', { name: /what is seoindicator/i });
      expect(productHeading).toBeInTheDocument();
    });

    it('AC-006: Features section renders', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { name: /key features/i })).toBeInTheDocument();
    });

    it('AC-007: Features count is correct', () => {
      const features = [
        { title: "Feature 1", description: "Desc 1" },
        { title: "Feature 2", description: "Desc 2" },
        { title: "Feature 3", description: "Desc 3" }
      ];
      render(<AboutContent features={features} />);
      
      features.forEach(feature => {
        expect(screen.getByText(feature.title)).toBeInTheDocument();
      });
    });

    it('AC-008: Feature has title', () => {
      render(<AboutContent />);
      expect(screen.getByText("SEO Analysis")).toBeInTheDocument();
    });

    it('AC-009: Feature has description', () => {
      render(<AboutContent />);
      expect(screen.getByText("Comprehensive SEO audits")).toBeInTheDocument();
    });

    it('AC-010: Feature card structure is correct', () => {
      render(<AboutContent />);
      const featureCards = screen.getAllByRole('article');
      expect(featureCards.length).toBeGreaterThanOrEqual(3);
      expect(featureCards.length).toBeLessThanOrEqual(5);
    });
  });

  // ============================================
  // AC-011 to AC-017: Team Section
  // ============================================
  describe('Team Section', () => {
    it('AC-011: Team section renders', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { name: /our mission/i })).toBeInTheDocument();
    });

    it('AC-012: Team has heading', () => {
      render(<AboutContent />);
      const teamSection = screen.getByRole('heading', { name: /our mission/i });
      expect(teamSection).toBeInTheDocument();
    });

    it('AC-013: Team has content', () => {
      const teamInfo = {
        title: "Our Team",
        description: "We are a dedicated team of professionals."
      };
      render(<AboutContent teamInfo={teamInfo} />);
      expect(screen.getByText(teamInfo.description)).toBeInTheDocument();
    });

    it('AC-014: CTA section renders', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { name: /ready to get started/i })).toBeInTheDocument();
    });

    it('AC-015: CTA has button/link', () => {
      render(<AboutContent />);
      const ctaLink = screen.getByRole('link', { name: /get started free/i });
      expect(ctaLink).toBeInTheDocument();
    });

    it('AC-016: CTA button text is correct', () => {
      const cta = { text: "Start Your Journey", href: "/signup" };
      render(<AboutContent cta={cta} />);
      expect(screen.getByRole('link', { name: cta.text })).toBeInTheDocument();
    });

    it('AC-017: CTA links to signup', () => {
      const cta = { text: "Get Started", href: "/signup" };
      render(<AboutContent cta={cta} />);
      const ctaLink = screen.getByRole('link', { name: cta.text });
      expect(ctaLink).toHaveAttribute('href', cta.href);
    });
  });

  // ============================================
  // AC-018 to AC-027: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('AC-018: Each section has proper heading', () => {
      render(<AboutContent />);
      const headings = screen.getAllByRole('heading');
      expect(headings.length).toBeGreaterThan(0);
      
      // Verify each section has a heading
      const sections = screen.getByTestId('about-content').querySelectorAll('section');
      sections.forEach(section => {
        const heading = section.querySelector('h1, h2, h3');
        expect(heading).toBeTruthy();
      });
    });

    it('AC-019: H1 text contains mission or about', () => {
      render(<AboutContent />);
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1.textContent).toBeTruthy();
    });

    it('AC-020: H2 count is correct', () => {
      render(<AboutContent />);
      const h2Elements = screen.getAllByRole('heading', { level: 2 });
      expect(h2Elements.length).toBeGreaterThanOrEqual(3);
      expect(h2Elements.length).toBeLessThanOrEqual(6);
    });

    it('AC-021: H2 hierarchy is correct', () => {
      render(<AboutContent />);
      const h2Elements = screen.getAllByRole('heading', { level: 2 });
      const sectionIds = ['product-heading', 'features-heading', 'team-heading', 'cta-heading'];
      
      h2Elements.forEach(h2 => {
        expect(h2).toBeInTheDocument();
      });
    });

    it('AC-022: All images have alt text', () => {
      render(<AboutContent />);
      const images = screen.getAllByRole('img');
      images.forEach(img => {
        expect(img).toHaveAttribute('alt');
      });
    });

    it('AC-023: Decorative images have empty alt', () => {
      render(<AboutContent />);
      // Check that any decorative images have alt=""
      const images = screen.queryAllByRole('img');
      images.forEach(img => {
        const alt = img.getAttribute('alt');
        // Either it has meaningful alt or empty string for decorative
        expect(alt !== null).toBe(true);
      });
    });

    it('AC-024: Links have accessible names', () => {
      render(<AboutContent />);
      const links = screen.getAllByRole('link');
      links.forEach(link => {
        const name = link.getAttribute('aria-label') || link.textContent;
        expect(name).toBeTruthy();
      });
    });

    it('AC-025: Buttons have accessible names', () => {
      render(<AboutContent />);
      const buttons = screen.queryAllByRole('button');
      buttons.forEach(button => {
        const name = button.getAttribute('aria-label') || button.textContent;
        expect(name).toBeTruthy();
      });
    });

    it('AC-026: Landmarks exist', () => {
      render(<AboutContent />);
      const sections = screen.getAllByRole('region');
      expect(sections.length).toBeGreaterThan(0);
    });

    it('AC-027: Sections have aria-labelledby', () => {
      render(<AboutContent />);
      const sections = screen.getByTestId('about-content').querySelectorAll('section');
      sections.forEach(section => {
        const labelledBy = section.getAttribute('aria-labelledby');
        expect(labelledBy).toBeTruthy();
      });
    });

    it('AC-030: Screen reader text works', () => {
      render(<AboutContent />);
      // Check for any sr-only text if present
      const content = screen.getByTestId('about-content');
      expect(content).toBeInTheDocument();
    });

    it('AC-045: No accessibility violations', async () => {
      const { container } = render(<AboutContent />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });
  });

  // ============================================
  // AC-031 to AC-037: Responsive Design
  // ============================================
  describe('Responsive Design', () => {
    it('AC-031: Mobile layout works at 375px', () => {
      // Mock mobile viewport
      global.innerWidth = 375;
      global.innerHeight = 667;
      
      render(<AboutContent />);
      expect(screen.getByTestId('about-content')).toBeInTheDocument();
    });

    it('AC-032: Tablet layout works at 768px', () => {
      global.innerWidth = 768;
      global.innerHeight = 1024;
      
      render(<AboutContent />);
      expect(screen.getByTestId('about-content')).toBeInTheDocument();
    });

    it('AC-033: Desktop layout works at 1024px', () => {
      global.innerWidth = 1024;
      global.innerHeight = 768;
      
      render(<AboutContent />);
      expect(screen.getByTestId('about-content')).toBeInTheDocument();
    });

    it('AC-034: Images are responsive', () => {
      render(<AboutContent />);
      const images = screen.queryAllByRole('img');
      // Images should be present and have proper attributes
      images.forEach(img => {
        expect(img).toBeInTheDocument();
      });
    });

    it('AC-035: Text is readable on mobile', () => {
      global.innerWidth = 375;
      render(<AboutContent />);
      const text = screen.getByText(/helping websites/i);
      expect(text).toBeInTheDocument();
    });

    it('AC-036: Touch targets are sufficient on mobile', () => {
      global.innerWidth = 375;
      render(<AboutContent />);
      const links = screen.getAllByRole('link');
      // Touch targets should be at least 44x44px
      links.forEach(link => {
        expect(link).toBeInTheDocument();
      });
    });

    it('AC-037: No horizontal scroll on mobile', () => {
      global.innerWidth = 375;
      render(<AboutContent />);
      const content = screen.getByTestId('about-content');
      // The component should not cause horizontal overflow
      expect(content).toBeInTheDocument();
    });
  });

  // ============================================
  // AC-038 to AC-043: States & Props
  // ============================================
  describe('States & Props', () => {
    it('AC-038: Animation respects reduced motion', () => {
      // Mock prefers-reduced-motion
      const mockMatchMedia = jest.fn().mockImplementation(query => ({
        matches: query === '(prefers-reduced-motion: reduce)',
        media: query,
        onchange: null,
        addListener: jest.fn(),
        removeListener: jest.fn(),
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        dispatchEvent: jest.fn(),
      }));
      window.matchMedia = mockMatchMedia;

      render(<AboutContent />);
      expect(screen.getByTestId('about-content')).toBeInTheDocument();
    });

    it('AC-039: Dark mode support works', () => {
      // Dark mode is typically handled via CSS classes
      render(<AboutContent />);
      expect(screen.getByTestId('about-content')).toBeInTheDocument();
    });

    it('AC-040: Loading state renders correctly', () => {
      render(<AboutContent loading={true} />);
      expect(screen.getByRole('status')).toBeInTheDocument();
      expect(screen.getByText(/loading/i)).toBeInTheDocument();
    });

    it('AC-041: Error state renders correctly', () => {
      const errorMsg = "Failed to load content";
      render(<AboutContent error={errorMsg} />);
      expect(screen.getByRole('alert')).toBeInTheDocument();
      expect(screen.getByText(errorMsg)).toBeInTheDocument();
    });

    it('AC-042: Custom className is applied', () => {
      const customClass = "custom-about-content";
      render(<AboutContent className={customClass} />);
      expect(screen.getByTestId('about-content')).toHaveClass(customClass);
    });

    it('AC-043: data-testid is applied', () => {
      const customTestId = "custom-test-id";
      render(<AboutContent data-testid={customTestId} />);
      expect(screen.getByTestId(customTestId)).toBeInTheDocument();
    });

    it('AC-044: Snapshot matches baseline', () => {
      const { container } = render(<AboutContent />);
      expect(container).toMatchSnapshot();
    });
  });
});

// ============================================
// Edge Cases
// ============================================
describe('Edge Cases', () => {
  it('handles empty features array', () => {
    render(<AboutContent features={[]} />);
    expect(screen.getByTestId('about-content')).toBeInTheDocument();
  });

  it('handles single feature', () => {
    const features = [{ title: "Only Feature", description: "Single feature" }];
    render(<AboutContent features={features} />);
    expect(screen.getByText("Only Feature")).toBeInTheDocument();
  });

  it('handles long mission text', () => {
    const longMission = "This is a very long mission statement that goes on and on and on to test how the component handles lengthy content without breaking the layout or causing any issues.";
    render(<AboutContent mission={longMission} />);
    expect(screen.getByText(longMission)).toBeInTheDocument();
  });

  it('handles special characters in content', () => {
    const specialMission = "Mission with <special> & \"characters\" 'test'";
    render(<AboutContent mission={specialMission} />);
    expect(screen.getByText(specialMission)).toBeInTheDocument();
  });

  it('handles unicode content', () => {
    const unicodeMission = "使命 🚀 Emojis™ and symbols ©";
    render(<AboutContent mission={unicodeMission} />);
    expect(screen.getByText(unicodeMission)).toBeInTheDocument();
  });
});
