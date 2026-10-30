/**
 * GuideCodeBlock Component
 * Task: TASK-20-29 - Render Guide Content in Page Component
 * 
 * Renders code examples with language labels and copy functionality.
 */

'use client';

import React, { useState } from 'react';
import type { CodeExample } from '@/lib/guides/types';

interface GuideCodeBlockProps {
  example: CodeExample;
  index?: number;
}

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
  apache: 'Apache',
  nginx: 'Nginx',
  text: 'Text',
};

/**
 * Renders a code example with language label and copy button
 */
export default function GuideCodeBlock({ example, index = 0 }: GuideCodeBlockProps) {
  const [copied, setCopied] = useState(false);

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(example.code);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      // Fallback for older browsers - silently fail
    }
  };

  const displayLanguage =
    languageLabels[example.language.toLowerCase()] ||
    example.language.toUpperCase();

  return (
    <div
      data-testid={`code-block-${index}`}
      className="relative rounded-lg overflow-hidden bg-slate-900 border border-slate-700 my-4"
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
