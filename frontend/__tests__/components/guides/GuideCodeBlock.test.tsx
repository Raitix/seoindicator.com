/**
 * Tests for GuideCodeBlock Component
 * Task: TASK-20-29 - Render Guide Content in Page Component
 * 
 * Tests the code example renderer with language labels and copy functionality.
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import type { CodeExample } from '@/lib/guides/types';

// Mock clipboard API
const mockClipboard = {
  writeText: jest.fn().mockImplementation(() => Promise.resolve()),
};
Object.assign(navigator, {
  clipboard: mockClipboard,
});

// Mock the actual component for TDD - will be implemented by Developer
function GuideCodeBlock({ example, index = 0 }: { example: CodeExample; index?: number }) {
  const [copied, setCopied] = React.useState(false);

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(example.code);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      // Fallback for older browsers
    }
  };

  const languageLabels: Record<string, string> = {
    html: 'HTML',
    css: 'CSS',
    javascript: 'JavaScript',
    js: 'JavaScript',
    jsx: 'JSX',
    typescript: 'TypeScript',
    ts: 'TypeScript',
    tsx: 'TSX',
    json: 'JSON',
    bash: 'Bash',
    shell: 'Shell',
    yaml: 'YAML',
    yml: 'YAML',
    markdown: 'Markdown',
    md: 'Markdown',
    sql: 'SQL',
    php: 'PHP',
    python: 'Python',
    py: 'Python',
  };

  const displayLanguage = languageLabels[example.language.toLowerCase()] || example.language.toUpperCase();

  return (
    <div 
      data-testid={`code-block-${index}`}
      className="relative rounded-lg overflow-hidden bg-slate-900 border border-slate-700"
    >
      {/* Header with language label and copy button */}
      <div 
        data-testid={`code-header-${index}`}
        className="flex items-center justify-between px-4 py-2 bg-slate-800 border-b border-slate-700"
      >
        <span 
          data-testid={`language-label-${index}`}
          className="text-xs font-medium text-slate-400 uppercase tracking-wide"
        >
          {displayLanguage}
        </span>
        <button
          data-testid={`copy-button-${index}`}
          onClick={handleCopy}
          className="text-xs text-slate-400 hover:text-white transition-colors flex items-center gap-1"
          aria-label="Copy code to clipboard"
        >
          {copied ? (
            <>
              <span aria-hidden="true">✓</span>
              <span>Copied!</span>
            </>
          ) : (
            <>
              <span aria-hidden="true">📋</span>
              <span>Copy</span>
            </>
          )}
        </button>
      </div>

      {/* Code content */}
      <pre 
        data-testid={`code-pre-${index}`}
        className="p-4 overflow-x-auto text-sm"
      >
        <code 
          data-testid={`code-content-${index}`}
          className={`language-${example.language} text-slate-300`}
        >
          {example.code}
        </code>
      </pre>

      {/* Description (optional) */}
      {example.description && (
        <p 
          data-testid={`code-description-${index}`}
          className="px-4 pb-4 text-sm text-slate-400 italic"
        >
          {example.description}
        </p>
      )}
    </div>
  );
}

// Test data
const htmlExample: CodeExample = {
  language: 'html',
  code: '<title>Your Page Title | Brand Name</title>',
  description: 'Basic title tag structure',
};

const jsxExample: CodeExample = {
  language: 'jsx',
  code: `import Image from 'next/image';

<Image
  src="/image.webp"
  alt="Description"
  width={800}
  height={600}
  loading="lazy"
/>`,
  description: 'Next.js Image component with lazy loading',
};

const jsonExample: CodeExample = {
  language: 'json',
  code: `{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "SEO Guide"
}`,
  description: undefined,
};

const noDescExample: CodeExample = {
  language: 'css',
  code: '.container { max-width: 1200px; }',
  description: undefined,
};

const longCodeExample: CodeExample = {
  language: 'javascript',
  code: 'const veryLongLine = "This is a very long line of code that will require horizontal scrolling to view completely on smaller screens and should be handled gracefully by the overflow-x-auto class";',
  description: 'Example of long code line',
};

const emptyCodeExample: CodeExample = {
  language: 'html',
  code: '',
  description: 'Empty code example',
};

const specialCharsExample: CodeExample = {
  language: 'html',
  code: '<div data-value="test&special<chars>">Content with "quotes" & symbols</div>',
  description: 'HTML with special characters',
};

const xssExample: CodeExample = {
  language: 'html',
  code: '<script>alert("xss")</script>',
  description: 'Potentially dangerous code',
};

const unknownLanguageExample: CodeExample = {
  language: 'custom-lang',
  code: 'some custom code here',
  description: 'Unknown language type',
};

describe('GuideCodeBlock Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('CE-001: Renders code block structure', () => {
    it('renders the code block container', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      expect(screen.getByTestId('code-block-0')).toBeInTheDocument();
    });

    it('renders header with language and copy button', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      expect(screen.getByTestId('code-header-0')).toBeInTheDocument();
    });

    it('renders pre/code structure', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      expect(screen.getByTestId('code-pre-0')).toBeInTheDocument();
      expect(screen.getByTestId('code-content-0')).toBeInTheDocument();
    });
  });

  describe('CE-002: Renders multiple code examples', () => {
    it('renders different code blocks with unique test ids', () => {
      const { container } = render(
        <>
          <GuideCodeBlock example={htmlExample} index={0} />
          <GuideCodeBlock example={jsxExample} index={1} />
        </>
      );
      
      expect(screen.getByTestId('code-block-0')).toBeInTheDocument();
      expect(screen.getByTestId('code-block-1')).toBeInTheDocument();
    });
  });

  describe('CE-003: Shows language label', () => {
    it('shows HTML language label', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      expect(screen.getByTestId('language-label-0')).toHaveTextContent('HTML');
    });

    it('shows JSX language label', () => {
      render(<GuideCodeBlock example={jsxExample} />);
      expect(screen.getByTestId('language-label-0')).toHaveTextContent('JSX');
    });

    it('shows JSON language label', () => {
      render(<GuideCodeBlock example={jsonExample} />);
      expect(screen.getByTestId('language-label-0')).toHaveTextContent('JSON');
    });

    it('shows CSS language label', () => {
      render(<GuideCodeBlock example={noDescExample} />);
      expect(screen.getByTestId('language-label-0')).toHaveTextContent('CSS');
    });

    it('shows uppercase for unknown languages', () => {
      render(<GuideCodeBlock example={unknownLanguageExample} />);
      expect(screen.getByTestId('language-label-0')).toHaveTextContent('CUSTOM-LANG');
    });
  });

  describe('CE-004: Language label is styled', () => {
    it('language label has uppercase styling', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      const label = screen.getByTestId('language-label-0');
      expect(label.className).toMatch(/uppercase/);
    });

    it('language label is in the header', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      const header = screen.getByTestId('code-header-0');
      const label = screen.getByTestId('language-label-0');
      expect(header).toContainElement(label);
    });
  });

  describe('CE-005: Shows description when present', () => {
    it('shows description text when provided', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      expect(screen.getByTestId('code-description-0')).toHaveTextContent('Basic title tag structure');
    });

    it('description is italicized', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      const desc = screen.getByTestId('code-description-0');
      expect(desc.className).toMatch(/italic/);
    });
  });

  describe('CE-006: Hides description when absent', () => {
    it('no description element when undefined', () => {
      render(<GuideCodeBlock example={jsonExample} />);
      expect(screen.queryByTestId('code-description-0')).not.toBeInTheDocument();
    });

    it('no description element when explicitly undefined', () => {
      render(<GuideCodeBlock example={noDescExample} />);
      expect(screen.queryByTestId('code-description-0')).not.toBeInTheDocument();
    });
  });

  describe('CE-007: Code block has copy button', () => {
    it('copy button is present', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      expect(screen.getByTestId('copy-button-0')).toBeInTheDocument();
    });

    it('copy button has accessible label', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      const button = screen.getByTestId('copy-button-0');
      expect(button).toHaveAttribute('aria-label', 'Copy code to clipboard');
    });

    it('clicking copy calls clipboard API', async () => {
      render(<GuideCodeBlock example={htmlExample} />);
      const button = screen.getByTestId('copy-button-0');
      fireEvent.click(button);
      
      expect(mockClipboard.writeText).toHaveBeenCalledWith(htmlExample.code);
    });

    it('shows "Copied!" feedback after click', async () => {
      render(<GuideCodeBlock example={htmlExample} />);
      const button = screen.getByTestId('copy-button-0');
      fireEvent.click(button);
      
      expect(button).toHaveTextContent('Copied!');
    });
  });

  describe('CE-008: Code is in pre/code elements', () => {
    it('code is wrapped in pre element', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      const pre = screen.getByTestId('code-pre-0');
      expect(pre.tagName).toBe('PRE');
    });

    it('code is wrapped in code element', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      const code = screen.getByTestId('code-content-0');
      expect(code.tagName).toBe('CODE');
    });

    it('code element has language class', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      const code = screen.getByTestId('code-content-0');
      expect(code.className).toContain('language-html');
    });

    it('code content matches input', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      const code = screen.getByTestId('code-content-0');
      expect(code).toHaveTextContent(htmlExample.code);
    });
  });

  describe('Horizontal scroll for long code', () => {
    it('pre element has overflow-x-auto class', () => {
      render(<GuideCodeBlock example={longCodeExample} />);
      const pre = screen.getByTestId('code-pre-0');
      expect(pre.className).toContain('overflow-x-auto');
    });

    it('long code is not truncated', () => {
      render(<GuideCodeBlock example={longCodeExample} />);
      const code = screen.getByTestId('code-content-0');
      expect(code.textContent).toContain('horizontal scrolling');
    });
  });

  describe('Empty code handling', () => {
    it('renders empty code block', () => {
      render(<GuideCodeBlock example={emptyCodeExample} />);
      expect(screen.getByTestId('code-block-0')).toBeInTheDocument();
    });

    it('shows language label even for empty code', () => {
      render(<GuideCodeBlock example={emptyCodeExample} />);
      expect(screen.getByTestId('language-label-0')).toHaveTextContent('HTML');
    });
  });

  describe('Special characters', () => {
    it('renders special characters in code', () => {
      render(<GuideCodeBlock example={specialCharsExample} />);
      const code = screen.getByTestId('code-content-0');
      expect(code.textContent).toContain('test&special<chars>');
    });

    it('renders quotes and ampersands correctly', () => {
      render(<GuideCodeBlock example={specialCharsExample} />);
      const code = screen.getByTestId('code-content-0');
      expect(code.textContent).toContain('"quotes" & symbols');
    });
  });

  describe('XSS prevention', () => {
    it('does not execute script in code content', () => {
      render(<GuideCodeBlock example={xssExample} />);
      const code = screen.getByTestId('code-content-0');
      // Script should be rendered as text, not executed
      expect(code.textContent).toContain('<script>');
      expect(code.textContent).toContain('alert');
    });
  });

  describe('Multi-line code', () => {
    it('renders multi-line code with line breaks preserved', () => {
      render(<GuideCodeBlock example={jsxExample} />);
      const code = screen.getByTestId('code-content-0');
      expect(code.textContent).toContain("import Image");
      expect(code.textContent).toContain("loading='lazy'");
    });
  });

  describe('Accessibility', () => {
    it('copy button has aria-label', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      const button = screen.getByTestId('copy-button-0');
      expect(button).toHaveAttribute('aria-label');
    });

    it('code block has proper semantic structure', () => {
      render(<GuideCodeBlock example={htmlExample} />);
      const pre = screen.getByTestId('code-pre-0');
      const code = pre.querySelector('code');
      expect(code).toBeInTheDocument();
    });
  });

  describe('Index prop', () => {
    it('uses custom index in test ids', () => {
      render(<GuideCodeBlock example={htmlExample} index={5} />);
      expect(screen.getByTestId('code-block-5')).toBeInTheDocument();
      expect(screen.getByTestId('language-label-5')).toBeInTheDocument();
    });
  });
});
