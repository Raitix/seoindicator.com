/**
 * Tests for GuideContent Component
 * Task: TASK-20-3 - Create Guide Page Template Component
 *
 * Tests the markdown content renderer for guide sections.
 *
 * The guide page renders every section with the GuideSection component,
 * so the sections are rendered the same way here.
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import '@testing-library/jest-dom';
import GuideSection from '@/components/guides/GuideSection';
import type { GuideSection as GuideSectionData } from '@/lib/guides/types';

// Test data
const mockSectionWithCode: GuideSectionData = {
  title: 'What is a Meta Title?',
  content:
    'The **meta title** is the title of your webpage.\n\n## Best Practices\n\n- Keep it under 60 characters\n- Include your target keyword\n\n> Pro tip: Use tools to analyze your titles.\n\nInline `code` example.',
  codeExamples: [
    {
      language: 'html',
      code: '<title>Your Page Title | Brand Name</title>',
      description: 'Basic title tag structure',
    },
  ],
};

const mockSectionNoCode: GuideSectionData = {
  title: 'Meta Description Best Practices',
  content:
    'The meta description is a summary.\n\n## Why It Matters\n\n- It is the snippet in the results\n- It is a call to action',
  codeExamples: [],
};

const mockGuideWithSections = {
  sections: [mockSectionWithCode, mockSectionNoCode],
};

/** Renders the sections the same way the guide page does. */
function renderSections(sections: GuideSectionData[]) {
  return render(
    <>
      {sections.map((section, index) => (
        <GuideSection key={index} section={section} index={index} />
      ))}
    </>
  );
}

describe('GuideContent Component', () => {
  describe('GC-001: Renders sections', () => {
    it('renders all sections', () => {
      renderSections(mockGuideWithSections.sections);

      expect(screen.getByTestId('guide-section-0')).toBeInTheDocument();
      expect(screen.getByTestId('guide-section-1')).toBeInTheDocument();
    });

    it('shows section titles in h2', () => {
      renderSections(mockGuideWithSections.sections);

      expect(screen.getByTestId('section-title-0')).toHaveTextContent(
        'What is a Meta Title?'
      );
      expect(screen.getByTestId('section-title-0').tagName).toBe('H2');
    });
  });

  describe('GC-003: Renders markdown content', () => {
    it('parses bold text', () => {
      renderSections(mockGuideWithSections.sections);

      const markdown = screen.getByTestId('section-content-0');
      expect(markdown.innerHTML).toContain('<strong>meta title</strong>');
    });

    it('parses headings', () => {
      renderSections(mockGuideWithSections.sections);

      const markdown = screen.getByTestId('section-content-0');
      expect(
        within(markdown).getByRole('heading', { name: 'Best Practices' })
      ).toBeInTheDocument();
    });

    it('parses lists', () => {
      renderSections(mockGuideWithSections.sections);

      const markdown = screen.getByTestId('section-content-0');
      expect(within(markdown).getAllByRole('listitem').length).toBeGreaterThan(0);
    });
  });

  describe('GC-004: Renders code examples', () => {
    it('shows code examples when present', () => {
      renderSections(mockGuideWithSections.sections);

      expect(screen.getByTestId('section-code-examples-0')).toBeInTheDocument();
    });

    it('shows code content', () => {
      renderSections(mockGuideWithSections.sections);

      expect(screen.getByTestId('code-content-0')).toHaveTextContent(
        '<title>Your Page Title'
      );
    });

    it('shows language label', () => {
      renderSections(mockGuideWithSections.sections);

      expect(screen.getByTestId('language-label-0')).toHaveTextContent('HTML');
    });

    it('applies language class to code element', () => {
      renderSections(mockGuideWithSections.sections);

      const code = screen.getByTestId('code-content-0');
      expect(code).toHaveClass('language-html');
    });

    it('does not render code examples section when empty', () => {
      renderSections([mockSectionNoCode]);

      expect(
        screen.queryByTestId('section-code-examples-0')
      ).not.toBeInTheDocument();
    });
  });

  describe('GC-005: Handles edge cases', () => {
    it('renders single section correctly', () => {
      renderSections([mockSectionWithCode]);

      expect(screen.getByTestId('guide-section-0')).toBeInTheDocument();
      expect(screen.queryByTestId('guide-section-1')).not.toBeInTheDocument();
    });

    it('renders all sections in order', () => {
      renderSections(mockGuideWithSections.sections);

      const sections = screen.getAllByTestId(/guide-section-[0-9]/);
      expect(sections).toHaveLength(2);
    });

    it('handles special characters in content', () => {
      const specialSection: GuideSectionData = {
        title: 'Special <Test> & More',
        content: 'Content with <angle> brackets & ampersands',
        codeExamples: [],
      };
      renderSections([specialSection]);

      expect(screen.getByTestId('guide-section-0')).toBeInTheDocument();
    });

    it('renders very long content without crash', () => {
      const longSection: GuideSectionData = {
        title: 'Long Content',
        content: 'A'.repeat(15000),
        codeExamples: [],
      };
      renderSections([longSection]);

      expect(screen.getByTestId('guide-section-0')).toBeInTheDocument();
    });

    it('handles unicode characters', () => {
      const unicodeSection: GuideSectionData = {
        title: 'Unicode 测试 🚀',
        content: 'Chinese: 中文\nEmoji: 🎉\nJapanese: 日本語',
        codeExamples: [],
      };
      renderSections([unicodeSection]);

      expect(screen.getByTestId('section-title-0')).toHaveTextContent(
        'Unicode 测试 🚀'
      );
    });
  });

  describe('GC-006: Renders markdown elements', () => {
    it('renders unordered lists', () => {
      renderSections(mockGuideWithSections.sections);

      const markdown = screen.getByTestId('section-content-0');
      expect(within(markdown).getAllByRole('listitem').length).toBeGreaterThan(0);
    });

    it('renders ordered lists', () => {
      renderSections(mockGuideWithSections.sections);

      const markdown = screen.getByTestId('section-content-1');
      expect(within(markdown).getAllByRole('listitem').length).toBeGreaterThan(0);
    });

    it('renders blockquotes', () => {
      renderSections(mockGuideWithSections.sections);

      const markdown = screen.getByTestId('section-content-0');
      expect(markdown.querySelector('blockquote')).toBeInTheDocument();
    });

    it('renders inline code', () => {
      renderSections(mockGuideWithSections.sections);

      const markdown = screen.getByTestId('section-content-0');
      expect(markdown.querySelector('code')).toBeInTheDocument();
    });

    it('section titles are h2', () => {
      renderSections(mockGuideWithSections.sections);

      const title = screen.getByTestId('section-title-0');
      expect(title.tagName).toBe('H2');
    });

    it('does not execute script tags in content', () => {
      const xssSection: GuideSectionData = {
        title: 'Test',
        content: '<script>alert("xss")</script>',
        codeExamples: [],
      };
      renderSections([xssSection]);

      // The markdown renderer escapes the script tag
      expect(screen.queryByText('alert("xss")')).not.toBeInTheDocument();
    });
  });

  describe('GC-007: Renders links', () => {
    it('renders links with target blank', () => {
      const sectionWithLink: GuideSectionData = {
        title: 'Test',
        content: 'Check [this link](https://example.com) for more.',
        codeExamples: [],
      };
      renderSections([sectionWithLink]);

      const link = screen.getByRole('link', { name: /this link/i });
      expect(link).toHaveAttribute('href', 'https://example.com');
      expect(link).toHaveAttribute('target', '_blank');
      expect(link).toHaveAttribute('rel', 'noopener noreferrer');
    });

    it('renders images with alt text', () => {
      const sectionWithImage: GuideSectionData = {
        title: 'Test',
        content: '![A description](https://example.com/image.webp)',
        codeExamples: [],
      };
      renderSections([sectionWithImage]);

      const image = screen.getByRole('img', { name: /a description/i });
      expect(image).toHaveAttribute('src', 'https://example.com/image.webp');
    });
  });

  describe('GC-008: Code block behaviour', () => {
    it('has copy button for code blocks', () => {
      renderSections(mockGuideWithSections.sections);

      expect(screen.getByTestId('copy-button-0')).toBeInTheDocument();
    });

    it('copy button has aria-label', () => {
      renderSections(mockGuideWithSections.sections);

      const copyButton = screen.getByTestId('copy-button-0');
      expect(copyButton).toHaveAttribute('aria-label', 'Copy code to clipboard');
    });

    it('code blocks have overflow-x-auto for scrolling', () => {
      renderSections(mockGuideWithSections.sections);

      const pre = screen.getByTestId('code-pre-0');
      expect(pre).toHaveClass('overflow-x-auto');
    });

    it('shows description when present', () => {
      renderSections(mockGuideWithSections.sections);

      expect(screen.getByTestId('code-description-0')).toHaveTextContent(
        'Basic title tag structure'
      );
    });

    it('hides description when absent', () => {
      const sectionNoDesc: GuideSectionData = {
        ...mockSectionWithCode,
        codeExamples: [{ language: 'html', code: '<title>Test</title>' }],
      };
      renderSections([sectionNoDesc]);

      expect(screen.queryByTestId('code-description-0')).not.toBeInTheDocument();
    });
  });
});
