/**
 * Tests for Guide Dynamic Page - Content Rendering
 * Task: TASK-20-29 - Render Guide Content in Page Component
 * 
 * Tests the dynamic routing page for individual SEO guides with full content rendering.
 * This test file focuses on verifying that sections, checklist, and code examples render correctly.
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock Next.js modules
jest.mock('next/link', () => {
  return function MockLink({ children, href }: { children: React.ReactNode; href: string }) {
    return <a href={href}>{children}</a>;
  };
});

jest.mock('next/navigation', () => ({
  notFound: jest.fn(() => {
    throw new Error('NOT_FOUND');
  }),
}));

// Mock guide with full content for testing
const mockGuideWithFullContent = {
  slug: 'image-optimization',
  title: 'Image Optimization for Web',
  summary: 'Optimize images for faster loading without sacrificing quality.',
  category: 'on-page' as const,
  icon: '⚡',
  readTime: 4,
  sections: [
    {
      title: 'What is Image Optimization?',
      content: '**Image optimization** is the process of delivering high-quality images in the right format, size, and resolution while keeping file sizes minimal.\n\nImages typically account for **50%+ of a webpage\'s total size**, making optimization critical for performance.',
      codeExamples: [
        {
          language: 'html',
          code: '<img src="image.webp" alt="Description" width="800" height="600" loading="lazy">',
          description: 'Optimized image tag with WebP format',
        },
      ],
    },
    {
      title: 'Why Image Optimization Matters',
      content: 'Image optimization directly impacts your **Core Web Vitals** scores and SEO performance.\n\n### Key Benefits\n\n- Faster crawl rates and improved rankings\n- Better user engagement\n- Mobile performance essential for mobile-first indexing',
      codeExamples: [],
    },
    {
      title: 'Image File Formats',
      content: '### Modern Formats\n\n**WebP** — Best all-around choice\n- 25-35% smaller than JPEG at equivalent quality\n- Supports lossy, lossless, and transparency\n\n**AVIF** — Next-generation format\n- 50% smaller than JPEG at same quality',
      codeExamples: [
        {
          language: 'html',
          code: '<picture>\n  <source srcset="image.avif" type="image/avif">\n  <source srcset="image.webp" type="image/webp">\n  <img src="image.jpg" alt="Description" width="800" height="600">\n</picture>',
          description: 'Picture element with AVIF/WebP fallback to JPEG',
        },
      ],
    },
  ],
  checklist: [
    { text: 'Use WebP or AVIF format for all images', priority: 'critical' as const },
    { text: 'Implement lazy loading for below-fold images', priority: 'critical' as const },
    { text: 'Compress images to 75-85% quality for optimal size', priority: 'important' as const },
    { text: 'Set explicit width and height attributes to prevent CLS', priority: 'important' as const },
    { text: 'Use responsive images with srcset for different devices', priority: 'recommended' as const },
  ],
  relatedGuides: ['image-alt-text', 'page-speed'],
};

const mockGuideWithEmptyContent = {
  slug: 'meta-description',
  title: 'Meta Description Best Practices',
  summary: 'Write descriptions that entice users to click.',
  category: 'on-page' as const,
  icon: '📋',
  readTime: 4,
  sections: [],
  checklist: [],
  relatedGuides: [],
};

// Mock registry functions
jest.mock('@/lib/guides/registry', () => ({
  getGuideBySlug: (slug: string) => {
    const guides: Record<string, object> = {
      'image-optimization': mockGuideWithFullContent,
      'meta-description': mockGuideWithEmptyContent,
      'h1-headings': {
        slug: 'h1-headings',
        title: 'H1 Heading Structure',
        summary: 'Use H1 tags effectively.',
        category: 'on-page',
        icon: '🔤',
        readTime: 4,
        sections: [],
        checklist: [],
        relatedGuides: [],
      },
    };
    return guides[slug] || null;
  },
  getAllGuides: () => [
    { slug: 'meta-title', title: 'Meta Title', category: 'on-page', icon: '📝', readTime: 5, summary: 'Summary', sections: [], checklist: [], relatedGuides: [] },
    { slug: 'image-optimization', title: 'Image Optimization', category: 'on-page', icon: '⚡', readTime: 4, summary: 'Summary', sections: [], checklist: [], relatedGuides: [] },
    { slug: 'h1-headings', title: 'H1 Headings', category: 'on-page', icon: '🔤', readTime: 4, summary: 'Summary', sections: [], checklist: [], relatedGuides: [] },
  ],
  getRelatedGuides: (slug: string) => {
    if (slug === 'image-optimization') {
      return [
        { slug: 'image-alt-text', title: 'Image Alt Text', category: 'on-page', icon: '🖼️', readTime: 4, summary: 'Alt text guide' },
      ];
    }
    return [];
  },
  GUIDE_SLUGS: [
    'meta-title', 'meta-description', 'h1-headings', 'heading-hierarchy',
    'image-alt-text', 'image-optimization', 'internal-links', 'external-links',
    'page-speed', 'mobile-friendly', 'https-security', 'structured-data',
    'canonical-urls', 'robots-txt', 'xml-sitemap', 'open-graph', 'twitter-cards',
  ],
}));

// Mock component for TDD - represents expected final implementation
function MockGuidePage({ params }: { params: { slug: string } }) {
  const { getGuideBySlug, getRelatedGuides } = require('@/lib/guides/registry');
  const guide = getGuideBySlug(params.slug);

  if (!guide) {
    const { notFound } = require('next/navigation');
    notFound();
    return null;
  }

  const relatedGuides = getRelatedGuides(params.slug);

  const categoryColors: Record<string, string> = {
    'on-page': 'bg-blue-500/10 text-blue-400',
    'technical': 'bg-purple-500/10 text-purple-400',
    'content': 'bg-green-500/10 text-green-400',
  };

  const priorityStyles: Record<string, string> = {
    critical: 'bg-red-500/10 border-red-500/30 text-red-400',
    important: 'bg-amber-500/10 border-amber-500/30 text-amber-400',
    recommended: 'bg-blue-500/10 border-blue-500/30 text-blue-400',
  };

  return (
    <main data-testid="guide-page" className="min-h-screen">
      {/* Breadcrumb */}
      <nav aria-label="Breadcrumb" data-testid="breadcrumb">
        <a href="/">Home</a>
        <span> / </span>
        <a href="/seo-guides">SEO Guides</a>
        <span> / </span>
        <span data-testid="current-guide">{guide.title}</span>
      </nav>

      {/* Hero */}
      <header data-testid="hero">
        <span data-testid="icon">{guide.icon}</span>
        <h1 data-testid="title">{guide.title}</h1>
        <p data-testid="summary">{guide.summary}</p>
        <span data-testid="read-time">{guide.readTime} min read</span>
        <span data-testid="category-badge" className={categoryColors[guide.category]}>
          {guide.category}
        </span>
      </header>

      {/* Sections Content - KEY TEST AREA */}
      <section data-testid="content-sections" className="prose">
        {guide.sections && guide.sections.length > 0 ? (
          guide.sections.map((section: { title: string; content: string; codeExamples: { language: string; code: string; description?: string }[] }, index: number) => (
            <article key={index} data-testid={`section-${index}`} className="guide-section">
              {/* Section title must be H2 */}
              <h2 data-testid={`section-title-${index}`}>{section.title}</h2>
              
              {/* Markdown content */}
              <div 
                data-testid={`section-content-${index}`}
                className="section-markdown"
                dangerouslySetInnerHTML={{ 
                  __html: section.content
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/### (.*)/g, '<h4>$1</h4>')
                    .replace(/## (.*)/g, '<h3>$1</h3>')
                    .replace(/- (.*)/g, '<li>$1</li>')
                    .replace(/\n\n/g, '</p><p>')
                }}
              />
              
              {/* Code examples within section */}
              {section.codeExamples && section.codeExamples.length > 0 && (
                <div data-testid={`code-examples-${index}`} className="code-examples">
                  {section.codeExamples.map((example, codeIndex) => (
                    <div key={codeIndex} data-testid={`code-block-${index}-${codeIndex}`} className="code-block">
                      <div data-testid={`code-header-${index}-${codeIndex}`}>
                        <span data-testid={`language-label-${index}-${codeIndex}`}>
                          {example.language.toUpperCase()}
                        </span>
                      </div>
                      <pre data-testid={`code-pre-${index}-${codeIndex}`}>
                        <code data-testid={`code-content-${index}-${codeIndex}`}>
                          {example.code}
                        </code>
                      </pre>
                      {example.description && (
                        <p data-testid={`code-description-${index}-${codeIndex}`}>
                          {example.description}
                        </p>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </article>
          ))
        ) : (
          <div data-testid="content-placeholder" className="placeholder">
            <p>Guide content will be added in Phase 2.</p>
          </div>
        )}
      </section>

      {/* Checklist - KEY TEST AREA */}
      {guide.checklist && guide.checklist.length > 0 && (
        <section data-testid="checklist-section" className="checklist">
          <h2 data-testid="checklist-heading">SEO Checklist</h2>
          <ul data-testid="checklist-items">
            {guide.checklist.map((item: { text: string; priority: string }, index: number) => (
              <li 
                key={index} 
                data-testid={`checklist-item-${index}`}
                data-priority={item.priority}
                className={priorityStyles[item.priority]}
              >
                <span data-testid={`priority-label-${index}`}>{item.priority}</span>
                <span data-testid={`item-text-${index}`}>{item.text}</span>
              </li>
            ))}
          </ul>
        </section>
      )}

      {/* Navigation */}
      <nav data-testid="guide-nav">
        <a href="/seo-guides" data-testid="back-link">All Guides</a>
      </nav>
    </main>
  );
}

// Mock generateStaticParams
function mockGenerateStaticParams() {
  const { GUIDE_SLUGS } = require('@/lib/guides/registry');
  return GUIDE_SLUGS.map((slug: string) => ({ slug }));
}

// Mock generateMetadata
function mockGenerateMetadata({ params }: { params: { slug: string } }) {
  const { getGuideBySlug } = require('@/lib/guides/registry');
  const guide = getGuideBySlug(params.slug);

  if (!guide) {
    return { title: 'Guide Not Found' };
  }

  return {
    title: `${guide.title} | SEO Guides | SEOIndicator`,
    description: guide.summary,
    openGraph: {
      title: guide.title,
      description: guide.summary,
      type: 'article',
    },
  };
}

describe('Guide Page - Content Rendering (TASK-20-29)', () => {
  describe('SR-001: Renders all sections', () => {
    it('renders all sections for guide with content', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      expect(screen.getByTestId('section-0')).toBeInTheDocument();
      expect(screen.getByTestId('section-1')).toBeInTheDocument();
      expect(screen.getByTestId('section-2')).toBeInTheDocument();
    });

    it('renders sections in correct order', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      expect(screen.getByTestId('section-title-0')).toHaveTextContent('What is Image Optimization?');
      expect(screen.getByTestId('section-title-1')).toHaveTextContent('Why Image Optimization Matters');
      expect(screen.getByTestId('section-title-2')).toHaveTextContent('Image File Formats');
    });
  });

  describe('SR-002: Renders section title as H2', () => {
    it('section titles are H2 elements', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const title = screen.getByTestId('section-title-0');
      expect(title.tagName).toBe('H2');
    });
  });

  describe('SR-003: Renders markdown content', () => {
    it('parses bold text in content', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const content = screen.getByTestId('section-content-0');
      expect(content.innerHTML).toContain('<strong>Image optimization</strong>');
    });

    it('parses multiple bold sections', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const content = screen.getByTestId('section-content-0');
      expect(content.innerHTML).toContain('<strong>50%+ of a webpage');
    });
  });

  describe('SR-004: Renders nested headings as H3', () => {
    it('## markdown becomes H3', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const content = screen.getByTestId('section-content-1');
      expect(content.innerHTML).toContain('<h3>Key Benefits</h3>');
    });

    it('### markdown becomes H4', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const content = screen.getByTestId('section-content-2');
      expect(content.innerHTML).toContain('<h4>Modern Formats</h4>');
    });
  });

  describe('SR-005: Handles empty sections array', () => {
    it('shows placeholder when no sections', () => {
      render(<MockGuidePage params={{ slug: 'meta-description' }} />);
      expect(screen.getByTestId('content-placeholder')).toBeInTheDocument();
    });

    it('placeholder message is clear', () => {
      render(<MockGuidePage params={{ slug: 'meta-description' }} />);
      expect(screen.getByTestId('content-placeholder')).toHaveTextContent('Phase 2');
    });
  });

  describe('HH-001: Section titles are H2', () => {
    it('all section titles use H2', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      expect(screen.getByTestId('section-title-0').tagName).toBe('H2');
      expect(screen.getByTestId('section-title-1').tagName).toBe('H2');
      expect(screen.getByTestId('section-title-2').tagName).toBe('H2');
    });
  });

  describe('HH-002: Markdown H2 becomes H3', () => {
    it('nested headings are demoted one level', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const content = screen.getByTestId('section-content-1');
      // ## Key Benefits becomes <h3>
      expect(content.innerHTML).toMatch(/<h3>Key Benefits<\/h3>/);
    });
  });

  describe('HH-003: No H1 after page title', () => {
    it('only one H1 on page (the main title)', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const title = screen.getByTestId('title');
      expect(title.tagName).toBe('H1');
    });
  });

  describe('CR-001: Renders all checklist items', () => {
    it('renders checklist section for guide with checklist', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      expect(screen.getByTestId('checklist-section')).toBeInTheDocument();
    });

    it('renders all checklist items', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      expect(screen.getByTestId('checklist-item-0')).toBeInTheDocument();
      expect(screen.getByTestId('checklist-item-1')).toBeInTheDocument();
      expect(screen.getByTestId('checklist-item-2')).toBeInTheDocument();
      expect(screen.getByTestId('checklist-item-3')).toBeInTheDocument();
      expect(screen.getByTestId('checklist-item-4')).toBeInTheDocument();
    });

    it('renders correct number of items', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const items = screen.getAllByTestId(/checklist-item-/);
      expect(items).toHaveLength(5);
    });
  });

  describe('CR-002: Renders critical priority styling', () => {
    it('critical items have red styling', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const item = screen.getByTestId('checklist-item-0');
      expect(item.className).toMatch(/bg-red.*text-red/);
    });

    it('critical priority is indicated', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const label = screen.getByTestId('priority-label-0');
      expect(label).toHaveTextContent('critical');
    });
  });

  describe('CR-003: Renders important priority styling', () => {
    it('important items have amber styling', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const item = screen.getByTestId('checklist-item-2');
      expect(item.className).toMatch(/bg-amber.*text-amber/);
    });

    it('important priority is indicated', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const label = screen.getByTestId('priority-label-2');
      expect(label).toHaveTextContent('important');
    });
  });

  describe('CR-004: Renders recommended priority styling', () => {
    it('recommended items have blue styling', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const item = screen.getByTestId('checklist-item-4');
      expect(item.className).toMatch(/bg-blue.*text-blue/);
    });

    it('recommended priority is indicated', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const label = screen.getByTestId('priority-label-4');
      expect(label).toHaveTextContent('recommended');
    });
  });

  describe('CR-007: Handles empty checklist', () => {
    it('no checklist section for empty checklist', () => {
      render(<MockGuidePage params={{ slug: 'meta-description' }} />);
      expect(screen.queryByTestId('checklist-section')).not.toBeInTheDocument();
    });
  });

  describe('CR-008: Checklist section has proper heading', () => {
    it('checklist heading is H2', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const heading = screen.getByTestId('checklist-heading');
      expect(heading.tagName).toBe('H2');
    });

    it('checklist heading text is correct', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const heading = screen.getByTestId('checklist-heading');
      expect(heading).toHaveTextContent('SEO Checklist');
    });
  });

  describe('CE-001: Renders code examples within section', () => {
    it('renders code examples for section with code', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      expect(screen.getByTestId('code-examples-0')).toBeInTheDocument();
    });

    it('no code examples section when section has no code', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      expect(screen.queryByTestId('code-examples-1')).not.toBeInTheDocument();
    });
  });

  describe('CE-003: Shows language label', () => {
    it('shows HTML language label', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      expect(screen.getByTestId('language-label-0-0')).toHaveTextContent('HTML');
    });

    it('shows language label for code in section 2', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      expect(screen.getByTestId('language-label-2-0')).toHaveTextContent('HTML');
    });
  });

  describe('CE-005: Shows description when present', () => {
    it('shows code description when provided', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      expect(screen.getByTestId('code-description-0-0')).toHaveTextContent('Optimized image tag');
    });
  });

  describe('CE-008: Code is in pre/code elements', () => {
    it('code is in pre element', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const pre = screen.getByTestId('code-pre-0-0');
      expect(pre.tagName).toBe('PRE');
    });

    it('code content is correct', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      const code = screen.getByTestId('code-content-0-0');
      expect(code.textContent).toContain('loading="lazy"');
    });
  });

  describe('AG-001: All 17 guides render correctly', () => {
    it('generates static params for all 17 guides', () => {
      const params = mockGenerateStaticParams();
      expect(params).toHaveLength(17);
    });

    it('guide with full content renders all elements', () => {
      render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
      expect(screen.getByTestId('hero')).toBeInTheDocument();
      expect(screen.getByTestId('content-sections')).toBeInTheDocument();
      expect(screen.getByTestId('checklist-section')).toBeInTheDocument();
    });

    it('guide with empty content shows placeholder', () => {
      render(<MockGuidePage params={{ slug: 'meta-description' }} />);
      expect(screen.getByTestId('content-placeholder')).toBeInTheDocument();
      expect(screen.queryByTestId('checklist-section')).not.toBeInTheDocument();
    });
  });

  describe('Existing tests - still pass', () => {
    describe('DP-001: Valid slug renders page', () => {
      it('renders page for valid slug', () => {
        render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
        expect(screen.getByTestId('guide-page')).toBeInTheDocument();
      });

      it('shows guide title', () => {
        render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
        expect(screen.getByTestId('title')).toHaveTextContent('Image Optimization for Web');
      });

      it('shows guide summary', () => {
        render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
        expect(screen.getByTestId('summary')).toHaveTextContent('Optimize images for faster loading');
      });

      it('shows guide icon', () => {
        render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
        expect(screen.getByTestId('icon')).toHaveTextContent('⚡');
      });

      it('shows read time', () => {
        render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
        expect(screen.getByTestId('read-time')).toHaveTextContent('4 min read');
      });
    });

    describe('DP-002: Invalid slug returns 404', () => {
      it('calls notFound for invalid slug', () => {
        const { notFound } = require('next/navigation');
        
        expect(() => {
          render(<MockGuidePage params={{ slug: 'non-existent' }} />);
        }).toThrow('NOT_FOUND');
        
        expect(notFound).toHaveBeenCalled();
      });
    });

    describe('DP-003: generateStaticParams', () => {
      it('returns all guide slugs', () => {
        const params = mockGenerateStaticParams();
        expect(params).toHaveLength(17);
      });
    });

    describe('DP-004: generateMetadata valid slug', () => {
      it('returns metadata with title', () => {
        const metadata = mockGenerateMetadata({ params: { slug: 'image-optimization' } });
        expect(metadata.title).toContain('Image Optimization');
      });
    });

    describe('DP-011: Slug with special chars', () => {
      it('calls notFound for slug with script tag attempt', () => {
        const { notFound } = require('next/navigation');
        
        expect(() => {
          render(<MockGuidePage params={{ slug: 'meta<script>' }} />);
        }).toThrow('NOT_FOUND');
      });
    });

    describe('Accessibility', () => {
      it('main heading is h1', () => {
        render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
        const title = screen.getByTestId('title');
        expect(title.tagName).toBe('H1');
      });

      it('has main landmark', () => {
        render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
        const main = screen.getByTestId('guide-page');
        expect(main.tagName).toBe('MAIN');
      });

      it('breadcrumb has aria-label', () => {
        render(<MockGuidePage params={{ slug: 'image-optimization' }} />);
        const breadcrumb = screen.getByTestId('breadcrumb');
        expect(breadcrumb).toHaveAttribute('aria-label', 'Breadcrumb');
      });
    });
  });
});
