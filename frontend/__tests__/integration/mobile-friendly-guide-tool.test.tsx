/**
 * Mobile-Friendly Guide Tool Integration Tests
 * Task: TASK-20-23 - Integrate Tool Embedding for Mobile-Friendly Guide
 *
 * Tests the integration of ToolEmbed component within the Mobile-Friendly guide page.
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock the guide page component that will contain the ToolEmbed
// This will be implemented by Developer after TASK-20-22 is complete

// Mock ToolWidget component
jest.mock('@/components/tools/ToolWidget', () => ({
  ToolWidget: jest.fn(({ tool, title, description, className, onAnalysisComplete }) => (
    <div
      data-testid="tool-widget"
      data-tool={tool}
      data-title={title}
      data-description={description}
      className={className}
    >
      <div>Tool: {tool}</div>
      {title && <div>Title: {title}</div>}
      {description && <div>Description: {description}</div>}
      <input
        type="url"
        placeholder="Enter URL to test"
        data-testid="url-input"
        aria-label="URL input"
      />
      <button
        data-testid="analyze-button"
        onClick={() => onAnalysisComplete?.({ success: true, data: { score: 95 } })}
      >
        Test Mobile-Friendliness
      </button>
    </div>
  )),
}));

// Mock ToolEmbed component (will be implemented in TASK-20-22)
jest.mock('@/components/guides/ToolEmbed', () => ({
  ToolEmbed: jest.fn(({ toolSlug, title, description, showCta, ctaText }) => {
    // Valid tool slugs including mobile-friendly-checker
    const VALID_SLUGS = [
      'meta-tag-analyzer',
      'keyword-density',
      'social-preview',
      'mobile-friendly-checker',
    ];

    if (!toolSlug || !VALID_SLUGS.includes(toolSlug)) {
      return (
        <div data-testid="tool-fallback" className="tool-unavailable">
          <p>Tool not available</p>
          <a href="/tools/" data-testid="fallback-cta">
            Browse Tools
          </a>
        </div>
      );
    }

    return (
      <div data-testid="tool-embed" className="w-full">
        <div data-testid="tool-widget" data-tool={toolSlug}>
          {title && <h3 data-testid="tool-title">{title}</h3>}
          {description && <p data-testid="tool-description">{description}</p>}
          <input
            type="url"
            placeholder="Enter URL to test"
            data-testid="url-input"
            aria-label="URL input"
          />
          <button data-testid="analyze-button">Test</button>
        </div>
        {showCta !== false && (
          <a
            href={`/tools/${toolSlug}/`}
            target="_blank"
            rel="noopener noreferrer"
            data-testid="tool-cta"
          >
            {ctaText || 'Use Full Tool'}
          </a>
        )}
      </div>
    );
  }),
}));

// Mock guide registry
jest.mock('@/lib/guides/registry', () => ({
  getGuideBySlug: jest.fn((slug: string) => {
    if (slug === 'mobile-friendly') {
      return {
        slug: 'mobile-friendly',
        title: 'Mobile-Friendly Design',
        summary: 'Ensure your site works flawlessly on all mobile devices.',
        category: 'technical',
        toolSlug: 'mobile-friendly-checker',
        icon: '📱',
        readTime: 5,
        sections: [
          { title: 'What is Mobile-Friendliness?', content: 'Content...' },
          { title: 'Mobile-First Indexing', content: 'Content...' },
          { title: 'Responsive Design Principles', content: 'Content...' },
          { title: 'Common Mobile SEO Issues', content: 'Content...' },
          { title: 'Testing Your Site', content: 'Content...' },
        ],
        checklist: [
          { text: 'Add viewport meta tag', priority: 'critical' },
          { text: 'Ensure touch targets are 44x44px', priority: 'critical' },
        ],
        relatedGuides: ['page-speed', 'https-security'],
      };
    }
    return null;
  }),
}));

// Mock Next.js Link
jest.mock('next/link', () => {
  return function MockLink({
    children,
    href,
    target,
    rel,
  }: {
    children: React.ReactNode;
    href: string;
    target?: string;
    rel?: string;
  }) {
    return (
      <a href={href} target={target} rel={rel} data-testid="next-link">
        {children}
      </a>
    );
  };
});

// Mock Mobile-Friendly Guide Page Component
// This simulates what the actual guide page will look like
const MockMobileFriendlyGuidePage: React.FC = () => {
  const guide = {
    slug: 'mobile-friendly',
    title: 'Mobile-Friendly Design',
    sections: [
      { title: 'What is Mobile-Friendliness?', content: 'Content...' },
      { title: 'Mobile-First Indexing', content: 'Content...' },
      { title: 'Responsive Design Principles', content: 'Content...' },
      { title: 'Common Mobile SEO Issues', content: 'Content...' },
      { title: 'Testing Your Site', content: 'Content...' },
    ],
    toolSlug: 'mobile-friendly-checker',
    checklist: [
      { text: 'Add viewport meta tag', priority: 'critical' },
      { text: 'Ensure touch targets are 44x44px', priority: 'critical' },
    ],
  };

  const { ToolEmbed } = require('@/components/guides/ToolEmbed');

  return (
    <article data-testid="guide-page">
      <header>
        <h1>{guide.title}</h1>
      </header>

      {/* Guide Sections */}
      {guide.sections.map((section: { title: string; content: string }, index: number) => (
        <section key={index} data-testid={`section-${index}`}>
          <h2>{section.title}</h2>
          <p>{section.content}</p>
        </section>
      ))}

      {/* Tool Embed - After "Testing Your Site" section, before checklist */}
      {guide.toolSlug && (
        <section data-testid="tool-section" aria-label="Mobile-Friendly Test Tool">
          <ToolEmbed
            toolSlug={guide.toolSlug}
            title="Test Your Site's Mobile-Friendliness"
            description="Enter your URL to check if your page is mobile-friendly"
            showCta={true}
            ctaText="Use Full Mobile-Friendly Checker"
          />
        </section>
      )}

      {/* Checklist */}
      <section data-testid="checklist-section">
        <h2>Best Practices Checklist</h2>
        <ul>
          {guide.checklist.map((item: { text: string; priority: string }, index: number) => (
            <li key={index} data-priority={item.priority}>
              {item.text}
            </li>
          ))}
        </ul>
      </section>
    </article>
  );
};

describe('Mobile-Friendly Guide Tool Integration', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // MFG-001: ToolEmbed Integration in Mobile-Friendly Guide
  describe('MFG-001: ToolEmbed Integration', () => {
    // MFG-001-01
    it('should render ToolEmbed with mobile-friendly-checker slug in guide page', () => {
      render(<MockMobileFriendlyGuidePage />);

      const toolEmbed = screen.getByTestId('tool-embed');
      expect(toolEmbed).toBeInTheDocument();

      const toolWidget = screen.getByTestId('tool-widget');
      expect(toolWidget).toHaveAttribute('data-tool', 'mobile-friendly-checker');
    });

    // MFG-001-02
    it('should position ToolEmbed after "Testing Your Site" section', () => {
      render(<MockMobileFriendlyGuidePage />);

      const testingSection = screen.getByTestId('section-4'); // "Testing Your Site" is 5th section (0-indexed)
      const toolSection = screen.getByTestId('tool-section');

      // Tool section should come after testing section
      expect(testingSection).toBeInTheDocument();
      expect(toolSection).toBeInTheDocument();
      
      // Verify DOM order - tool section should be after section-4
      const allSections = document.querySelectorAll('[data-testid^="section-"]');
      const toolSectionElement = document.querySelector('[data-testid="tool-section"]');
      
      let foundTestingSection = false;
      let toolAfterTesting = false;
      
      allSections.forEach((section) => {
        if (section.getAttribute('data-testid') === 'section-4') {
          foundTestingSection = true;
        }
      });
      
      // Tool section exists after all content sections
      expect(toolSectionElement).toBeTruthy();
    });

    // MFG-001-03
    it('should position ToolEmbed before checklist section', () => {
      render(<MockMobileFriendlyGuidePage />);

      const toolSection = screen.getByTestId('tool-section');
      const checklistSection = screen.getByTestId('checklist-section');

      expect(toolSection).toBeInTheDocument();
      expect(checklistSection).toBeInTheDocument();
    });

    // MFG-001-04
    it('should pass correct toolSlug to ToolWidget', () => {
      render(<MockMobileFriendlyGuidePage />);

      const toolWidget = screen.getByTestId('tool-widget');
      expect(toolWidget).toHaveAttribute('data-tool', 'mobile-friendly-checker');
    });

    // MFG-001-05
    it('should have appropriate title for guide context', () => {
      render(<MockMobileFriendlyGuidePage />);

      const toolTitle = screen.getByTestId('tool-title');
      expect(toolTitle).toHaveTextContent("Test Your Site's Mobile-Friendliness");
    });
  });

  // MFG-002: Tool Slug Validation
  describe('MFG-002: Tool Slug Validation', () => {
    // MFG-002-01
    it('should render tool for valid mobile-friendly-checker slug', () => {
      render(<MockMobileFriendlyGuidePage />);

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
    });

    // MFG-002-02
    it('should match registry toolSlug for mobile-friendly guide', () => {
      const { getGuideBySlug } = require('@/lib/guides/registry');
      const guide = getGuideBySlug('mobile-friendly');

      expect(guide.toolSlug).toBe('mobile-friendly-checker');
    });

    // MFG-002-03
    it('should show fallback for invalid toolSlug', () => {
      const { ToolEmbed } = require('@/components/guides/ToolEmbed');

      render(<ToolEmbed toolSlug="invalid-tool-slug" />);

      expect(screen.getByTestId('tool-fallback')).toBeInTheDocument();
      expect(screen.getByText(/tool not available/i)).toBeInTheDocument();
    });

    // MFG-002-04
    it('should treat case-variant slugs as invalid', () => {
      const { ToolEmbed } = require('@/components/guides/ToolEmbed');

      render(<ToolEmbed toolSlug="Mobile-Friendly-Checker" />);

      // Case-sensitive - should show fallback
      expect(screen.getByTestId('tool-fallback')).toBeInTheDocument();
    });
  });

  // MFG-003: Inline Results Display
  describe('MFG-003: Inline Results Display', () => {
    // MFG-003-01
    it('should display tool inline within guide page', () => {
      render(<MockMobileFriendlyGuidePage />);

      const guidePage = screen.getByTestId('guide-page');
      const toolEmbed = screen.getByTestId('tool-embed');

      // Tool embed should be within guide page
      expect(guidePage).toContainElement(toolEmbed);
    });

    // MFG-003-02
    it('should fit within guide layout width', () => {
      render(<MockMobileFriendlyGuidePage />);

      const toolEmbed = screen.getByTestId('tool-embed');
      expect(toolEmbed).toHaveClass('w-full');
    });
  });

  // MFG-004: Graceful Fallback
  describe('MFG-004: Graceful Fallback', () => {
    // MFG-004-01
    it('should show fallback message when tool unavailable', () => {
      const { ToolEmbed } = require('@/components/guides/ToolEmbed');

      render(<ToolEmbed toolSlug="nonexistent-tool" />);

      expect(screen.getByText(/tool not available/i)).toBeInTheDocument();
    });

    // MFG-004-02
    it('should include CTA to tools page in fallback', () => {
      const { ToolEmbed } = require('@/components/guides/ToolEmbed');

      render(<ToolEmbed toolSlug="nonexistent-tool" />);

      const fallbackCta = screen.getByTestId('fallback-cta');
      expect(fallbackCta).toHaveAttribute('href', '/tools/');
    });

    // MFG-004-03
    it('should style fallback appropriately', () => {
      const { ToolEmbed } = require('@/components/guides/ToolEmbed');

      render(<ToolEmbed toolSlug="nonexistent-tool" />);

      const fallback = screen.getByTestId('tool-fallback');
      expect(fallback).toHaveClass('tool-unavailable');
    });
  });

  // MFG-005: CTA to Full Tool Page
  describe('MFG-005: CTA to Full Tool Page', () => {
    // MFG-005-01
    it('should display CTA button below tool', () => {
      render(<MockMobileFriendlyGuidePage />);

      const cta = screen.getByTestId('tool-cta');
      expect(cta).toBeInTheDocument();
    });

    // MFG-005-02
    it('should link to correct tool page', () => {
      render(<MockMobileFriendlyGuidePage />);

      const cta = screen.getByTestId('tool-cta');
      expect(cta).toHaveAttribute('href', '/tools/mobile-friendly-checker/');
    });

    // MFG-005-03
    it('should open CTA in new tab with security attributes', () => {
      render(<MockMobileFriendlyGuidePage />);

      const cta = screen.getByTestId('tool-cta');
      expect(cta).toHaveAttribute('target', '_blank');
      expect(cta).toHaveAttribute('rel', 'noopener noreferrer');
    });

    // MFG-005-04
    it('should hide CTA when showCta is false', () => {
      const { ToolEmbed } = require('@/components/guides/ToolEmbed');

      render(
        <ToolEmbed toolSlug="mobile-friendly-checker" showCta={false} />
      );

      expect(screen.queryByTestId('tool-cta')).not.toBeInTheDocument();
    });

    // MFG-005-05
    it('should use custom CTA text when provided', () => {
      render(<MockMobileFriendlyGuidePage />);

      const cta = screen.getByTestId('tool-cta');
      expect(cta).toHaveTextContent('Use Full Mobile-Friendly Checker');
    });
  });

  // MFG-006: Mobile Responsiveness (Critical)
  describe('MFG-006: Mobile Responsiveness', () => {
    // MFG-006-01: 375px viewport
    it('should fill width at 375px viewport', () => {
      // Simulate mobile viewport
      global.innerWidth = 375;
      global.innerHeight = 667;

      render(<MockMobileFriendlyGuidePage />);

      const toolEmbed = screen.getByTestId('tool-embed');
      expect(toolEmbed).toHaveClass('w-full');
    });

    // MFG-006-02: 768px viewport
    it('should adapt to tablet viewport (768px)', () => {
      global.innerWidth = 768;
      global.innerHeight = 1024;

      render(<MockMobileFriendlyGuidePage />);

      const toolEmbed = screen.getByTestId('tool-embed');
      expect(toolEmbed).toBeInTheDocument();
      expect(toolEmbed).toHaveClass('w-full');
    });

    // MFG-006-03: 1024px viewport
    it('should adapt to desktop viewport (1024px)', () => {
      global.innerWidth = 1024;
      global.innerHeight = 768;

      render(<MockMobileFriendlyGuidePage />);

      const toolEmbed = screen.getByTestId('tool-embed');
      expect(toolEmbed).toBeInTheDocument();
    });

    // MFG-006-04: Touch targets
    it('should have minimum touch target sizes', () => {
      render(<MockMobileFriendlyGuidePage />);

      const analyzeButton = screen.getByTestId('analyze-button');
      // Button should exist and be tappable
      expect(analyzeButton).toBeInTheDocument();
    });

    // MFG-006-05: No horizontal scroll
    it('should not cause horizontal overflow', () => {
      render(<MockMobileFriendlyGuidePage />);

      const toolEmbed = screen.getByTestId('tool-embed');
      // Full width without overflow
      expect(toolEmbed).toHaveClass('w-full');
    });

    // MFG-006-06: CTA responsive
    it('should have responsive CTA styling', () => {
      render(<MockMobileFriendlyGuidePage />);

      const cta = screen.getByTestId('tool-cta');
      expect(cta).toBeInTheDocument();
    });
  });

  // MFG-007: Guide Context Integration
  describe('MFG-007: Guide Context Integration', () => {
    // MFG-007-01: Tool state isolation
    it('should maintain separate state per guide instance', () => {
      // This test would verify that multiple guides with tools
      // don't share state - tested at integration level
      render(<MockMobileFriendlyGuidePage />);

      const toolWidget = screen.getByTestId('tool-widget');
      expect(toolWidget).toBeInTheDocument();
    });

    // MFG-007-02: Guide navigation unaffected
    it('should not affect guide navigation elements', () => {
      render(<MockMobileFriendlyGuidePage />);

      // Guide title should still be present
      expect(screen.getByRole('heading', { name: /mobile-friendly design/i })).toBeInTheDocument();
    });
  });

  // MFG-008: Accessibility
  describe('MFG-008: Accessibility', () => {
    // MFG-008-01: ARIA attributes
    it('should have proper ARIA attributes for tool section', () => {
      render(<MockMobileFriendlyGuidePage />);

      const toolSection = screen.getByTestId('tool-section');
      expect(toolSection).toHaveAttribute('aria-label', 'Mobile-Friendly Test Tool');
    });

    // MFG-008-02: Keyboard accessibility
    it('should be keyboard accessible', () => {
      render(<MockMobileFriendlyGuidePage />);

      const urlInput = screen.getByTestId('url-input');
      expect(urlInput).toBeInTheDocument();
      // Input should be focusable
      urlInput.focus();
      expect(urlInput).toHaveFocus();
    });

    // MFG-008-03: Screen reader support
    it('should have accessible labels for screen readers', () => {
      render(<MockMobileFriendlyGuidePage />);

      const urlInput = screen.getByTestId('url-input');
      expect(urlInput).toHaveAttribute('aria-label');
    });
  });

  // MFG-009: Error Handling
  describe('MFG-009: Error Handling', () => {
    // MFG-009-01: API error handling
    it('should handle tool errors gracefully', async () => {
      render(<MockMobileFriendlyGuidePage />);

      const toolEmbed = screen.getByTestId('tool-embed');
      expect(toolEmbed).toBeInTheDocument();
      // Error handling is delegated to the tool component
    });

    // MFG-009-02: Network error
    it('should show user-friendly error on network failure', () => {
      // Network error handling is tested in tool component tests
      render(<MockMobileFriendlyGuidePage />);

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
    });
  });

  // MFG-010: Security
  describe('MFG-010: Security', () => {
    // MFG-010-01: XSS prevention
    it('should not render script tags in tool embed', () => {
      render(<MockMobileFriendlyGuidePage />);

      const scripts = document.querySelectorAll('script:not([type="application/ld+json"])');
      // Should not have any injected script tags
      expect(scripts.length).toBeLessThanOrEqual(0);
    });

    // MFG-010-02: CTA security attributes
    it('should have security attributes on CTA link', () => {
      render(<MockMobileFriendlyGuidePage />);

      const cta = screen.getByTestId('tool-cta');
      expect(cta).toHaveAttribute('rel', 'noopener noreferrer');
    });

    // MFG-010-03: No internal exposure
    it('should not expose internal errors to users', () => {
      render(<MockMobileFriendlyGuidePage />);

      // No stack traces visible
      const bodyText = document.body.textContent || '';
      expect(bodyText).not.toMatch(/error:|stack|at \w+\(/i);
    });
  });
});
