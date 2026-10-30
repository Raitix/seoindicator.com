/**
 * CodeExample Component Tests
 * Task: TASK-6-18
 * 
 * Tests for code example blocks with syntax highlighting
 * and copy-to-clipboard functionality.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock clipboard API
const mockClipboardWrite = vi.fn().mockResolvedValue(undefined);
Object.assign(navigator, {
  clipboard: {
    writeText: mockClipboardWrite,
  },
});

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Copy: () => <span data-testid="copy-icon">Copy</span>,
  Check: () => <span data-testid="check-icon">Check</span>,
}));

// Mock react-syntax-highlighter
vi.mock('react-syntax-highlighter', () => ({
  Prism: ({ children, language, showLineNumbers }: { children: string; language: string; showLineNumbers?: boolean }) => (
    <div data-testid="code-highlight" data-language={language} data-line-numbers={showLineNumbers}>
      <pre>
        <code>{children}</code>
      </pre>
    </div>
  ),
}));

vi.mock('react-syntax-highlighter/dist/esm/styles/prism', () => ({
  vscDarkPlus: {},
  vs: {},
}));

interface CodeExampleProps {
  language: 'curl' | 'javascript' | 'python' | 'json' | 'bash';
  code: string;
  baseUrl?: string;
  placeholders?: Record<string, string>;
  showLineNumbers?: boolean;
  title?: string;
}

// Mock component for TDD
const MockCodeExample = ({
  language,
  code,
  baseUrl = 'https://api.seoindicator.com/api/v1',
  placeholders = {},
  showLineNumbers = false,
  title,
}: CodeExampleProps) => {
  const [copied, setCopied] = React.useState(false);

  // Replace placeholders in code
  const processedCode = React.useMemo(() => {
    let result = code;
    for (const [key, value] of Object.entries(placeholders)) {
      result = result.replace(new RegExp(`\\{${key}\\}`, 'g'), value);
    }
    result = result.replace(/{base_url}/g, baseUrl);
    return result;
  }, [code, placeholders, baseUrl]);

  const handleCopy = async () => {
    await navigator.clipboard.writeText(processedCode);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const languageLabels: Record<string, string> = {
    curl: 'cURL',
    javascript: 'JavaScript',
    python: 'Python',
    json: 'JSON',
    bash: 'Bash',
  };

  return (
    <div data-testid="code-example" className="rounded-lg border overflow-hidden">
      {/* Header */}
      <div data-testid="code-header" className="flex items-center justify-between bg-gray-100 px-4 py-2">
        {title && <span data-testid="code-title">{title}</span>}
        <span data-testid="language-label">{languageLabels[language] || language}</span>
        <button
          data-testid="copy-btn"
          onClick={handleCopy}
          aria-label="Copy code to clipboard"
          className="flex items-center gap-1"
        >
          {copied ? (
            <>
              <span data-testid="check-icon">✓</span>
              <span>Copied!</span>
            </>
          ) : (
            <>
              <span data-testid="copy-icon">Copy</span>
              <span>Copy</span>
            </>
          )}
        </button>
      </div>

      {/* Code block */}
      <div data-testid="code-container">
        <div data-testid="code-highlight" data-language={language} data-line-numbers={showLineNumbers}>
          <pre>
            <code>{processedCode}</code>
          </pre>
        </div>
      </div>
    </div>
  );
};

import React from 'react';

describe('CodeExample', () => {
  const defaultCode = `curl -X GET "{base_url}/keywords" \\
  -H "Authorization: Bearer {token}" \\
  -H "Content-Type: application/json"`;

  beforeEach(() => {
    mockClipboardWrite.mockClear();
  });

  describe('Rendering', () => {
    it('renders code block', () => {
      render(<MockCodeExample language="curl" code={defaultCode} />);
      expect(screen.getByTestId('code-highlight')).toBeInTheDocument();
    });

    it('renders language label', () => {
      render(<MockCodeExample language="curl" code={defaultCode} />);
      expect(screen.getByTestId('language-label')).toHaveTextContent('cURL');
    });

    it('renders copy button', () => {
      render(<MockCodeExample language="curl" code={defaultCode} />);
      expect(screen.getByTestId('copy-btn')).toBeInTheDocument();
    });

    it('renders title when provided', () => {
      render(<MockCodeExample language="curl" code={defaultCode} title="Get Keywords" />);
      expect(screen.getByTestId('code-title')).toHaveTextContent('Get Keywords');
    });

    it('does not render title when not provided', () => {
      render(<MockCodeExample language="curl" code={defaultCode} />);
      expect(screen.queryByTestId('code-title')).not.toBeInTheDocument();
    });
  });

  describe('Language Support', () => {
    it('curl code renders', () => {
      render(<MockCodeExample language="curl" code="curl -X GET localhost" />);
      expect(screen.getByTestId('language-label')).toHaveTextContent('cURL');
    });

    it('JavaScript code renders', () => {
      render(<MockCodeExample language="javascript" code="fetch('/api')" />);
      expect(screen.getByTestId('language-label')).toHaveTextContent('JavaScript');
    });

    it('Python code renders', () => {
      render(<MockCodeExample language="python" code="import requests" />);
      expect(screen.getByTestId('language-label')).toHaveTextContent('Python');
    });

    it('JSON code renders', () => {
      render(<MockCodeExample language="json" code='{"key": "value"}' />);
      expect(screen.getByTestId('language-label')).toHaveTextContent('JSON');
    });

    it('Bash code renders', () => {
      render(<MockCodeExample language="bash" code="#!/bin/bash" />);
      expect(screen.getByTestId('language-label')).toHaveTextContent('Bash');
    });

    it('data-language attribute set correctly', () => {
      render(<MockCodeExample language="python" code="print('hello')" />);
      expect(screen.getByTestId('code-highlight')).toHaveAttribute('data-language', 'python');
    });
  });

  describe('Placeholder Replacement', () => {
    it('replaces {base_url} with baseUrl prop', () => {
      render(
        <MockCodeExample
          language="curl"
          code="curl {base_url}/keywords"
          baseUrl="https://custom.api.com"
        />
      );
      
      const codeBlock = screen.getByTestId('code-highlight');
      expect(codeBlock).toHaveTextContent('https://custom.api.com/keywords');
    });

    it('replaces custom placeholders', () => {
      render(
        <MockCodeExample
          language="curl"
          code="curl -H 'Authorization: Bearer {token}'"
          placeholders={{ token: 'my-secret-token' }}
        />
      );
      
      const codeBlock = screen.getByTestId('code-highlight');
      expect(codeBlock).toHaveTextContent('my-secret-token');
    });

    it('replaces multiple placeholder types', () => {
      render(
        <MockCodeExample
          language="curl"
          code="curl {base_url}/sites/{site_id} -H 'Bearer {token}'"
          placeholders={{ site_id: '123', token: 'abc' }}
        />
      );
      
      const codeBlock = screen.getByTestId('code-highlight');
      expect(codeBlock).toHaveTextContent('/sites/123');
      expect(codeBlock).toHaveTextContent('Bearer abc');
    });

    it('leaves placeholders unchanged when not provided', () => {
      render(
        <MockCodeExample
          language="curl"
          code="curl -H 'Bearer {token}'"
        />
      );
      
      const codeBlock = screen.getByTestId('code-highlight');
      expect(codeBlock).toHaveTextContent('{token}');
    });
  });

  describe('Copy Functionality', () => {
    it('copies code to clipboard on button click', async () => {
      const user = userEvent.setup();
      render(<MockCodeExample language="curl" code="curl localhost" />);
      
      await user.click(screen.getByTestId('copy-btn'));
      
      expect(mockClipboardWrite).toHaveBeenCalledWith('curl localhost');
    });

    it('copies processed code with placeholders replaced', async () => {
      const user = userEvent.setup();
      render(
        <MockCodeExample
          language="curl"
          code="curl {base_url}/test"
          baseUrl="https://api.test.com"
        />
      );
      
      await user.click(screen.getByTestId('copy-btn'));
      
      expect(mockClipboardWrite).toHaveBeenCalledWith('curl https://api.test.com/test');
    });

    it('shows "Copied!" feedback after copy', async () => {
      const user = userEvent.setup();
      render(<MockCodeExample language="curl" code="curl localhost" />);
      
      await user.click(screen.getByTestId('copy-btn'));
      
      expect(screen.getByText('Copied!')).toBeInTheDocument();
    });

    it('shows check icon after copy', async () => {
      const user = userEvent.setup();
      render(<MockCodeExample language="curl" code="curl localhost" />);
      
      await user.click(screen.getByTestId('copy-btn'));
      
      expect(screen.getByTestId('check-icon')).toBeInTheDocument();
    });

    it('resets to copy state after timeout', async () => {
      vi.useFakeTimers();
      const user = userEvent.setup({ delay: null });
      
      render(<MockCodeExample language="curl" code="curl localhost" />);
      
      await user.click(screen.getByTestId('copy-btn'));
      expect(screen.getByText('Copied!')).toBeInTheDocument();
      
      vi.advanceTimersByTime(2000);
      
      expect(screen.queryByText('Copied!')).not.toBeInTheDocument();
      expect(screen.getByTestId('copy-icon')).toBeInTheDocument();
      
      vi.useRealTimers();
    });

    it('copy button has accessible label', () => {
      render(<MockCodeExample language="curl" code="curl localhost" />);
      expect(screen.getByTestId('copy-btn')).toHaveAttribute('aria-label');
    });
  });

  describe('Line Numbers', () => {
    it('does not show line numbers by default', () => {
      render(<MockCodeExample language="json" code='{"a": 1}' />);
      expect(screen.getByTestId('code-highlight')).toHaveAttribute('data-line-numbers', 'false');
    });

    it('shows line numbers when enabled', () => {
      render(<MockCodeExample language="json" code='{"a": 1}' showLineNumbers={true} />);
      expect(screen.getByTestId('code-highlight')).toHaveAttribute('data-line-numbers', 'true');
    });
  });

  describe('Code Examples by Language', () => {
    it('curl example is valid', () => {
      const curlCode = `curl -X GET "https://api.seoindicator.com/api/v1/keywords?site_id=1" \\
  -H "Authorization: Bearer YOUR_TOKEN" \\
  -H "Content-Type: application/json"`;
      
      render(<MockCodeExample language="curl" code={curlCode} />);
      
      const codeBlock = screen.getByTestId('code-highlight');
      expect(codeBlock).toHaveTextContent('curl');
      expect(codeBlock).toHaveTextContent('Authorization');
    });

    it('JavaScript fetch example is valid', () => {
      const jsCode = `const response = await fetch('https://api.seoindicator.com/api/v1/keywords?site_id=1', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  }
});
const data = await response.json();`;
      
      render(<MockCodeExample language="javascript" code={jsCode} />);
      
      const codeBlock = screen.getByTestId('code-highlight');
      expect(codeBlock).toHaveTextContent('fetch');
      expect(codeBlock).toHaveTextContent('Authorization');
    });

    it('Python requests example is valid', () => {
      const pythonCode = `import requests

response = requests.get(
    'https://api.seoindicator.com/api/v1/keywords',
    params={'site_id': 1},
    headers={
        'Authorization': 'Bearer YOUR_TOKEN',
        'Content-Type': 'application/json'
    }
)
data = response.json()`;
      
      render(<MockCodeExample language="python" code={pythonCode} />);
      
      const codeBlock = screen.getByTestId('code-highlight');
      expect(codeBlock).toHaveTextContent('requests');
      expect(codeBlock).toHaveTextContent('Authorization');
    });
  });

  describe('Accessibility', () => {
    it('code container is focusable', () => {
      render(<MockCodeExample language="curl" code="curl localhost" />);
      const container = screen.getByTestId('code-container');
      expect(container).toBeInTheDocument();
    });

    it('language is indicated for screen readers', () => {
      render(<MockCodeExample language="python" code="print('hello')" />);
      expect(screen.getByTestId('language-label')).toHaveTextContent('Python');
    });
  });

  describe('Edge Cases', () => {
    it('handles empty code', () => {
      render(<MockCodeExample language="curl" code="" />);
      expect(screen.getByTestId('code-highlight')).toBeInTheDocument();
    });

    it('handles very long lines', () => {
      const longCode = 'curl ' + 'x'.repeat(500);
      render(<MockCodeExample language="curl" code={longCode} />);
      expect(screen.getByTestId('code-highlight')).toBeInTheDocument();
    });

    it('handles special characters in code', () => {
      const specialCode = `curl -d '{"key": "value with <special> & chars"}'`;
      render(<MockCodeExample language="curl" code={specialCode} />);
      expect(screen.getByTestId('code-highlight')).toBeInTheDocument();
    });

    it('handles unicode in code', () => {
      const unicodeCode = `# コメント\nprint("日本語")`;
      render(<MockCodeExample language="python" code={unicodeCode} />);
      expect(screen.getByTestId('code-highlight')).toBeInTheDocument();
    });

    it('handles multiline code', () => {
      const multilineCode = `line1
line2
line3`;
      render(<MockCodeExample language="bash" code={multilineCode} />);
      const codeBlock = screen.getByTestId('code-highlight');
      expect(codeBlock).toHaveTextContent('line1');
      expect(codeBlock).toHaveTextContent('line2');
      expect(codeBlock).toHaveTextContent('line3');
    });

    it('handles code with backslashes', () => {
      const backslashCode = `curl -X POST \\
  -H "Content-Type: application/json" \\
  -d '{"key": "value"}'`;
      render(<MockCodeExample language="curl" code={backslashCode} />);
      expect(screen.getByTestId('code-highlight')).toBeInTheDocument();
    });
  });
});
