'use client';

/**
 * PWA Guide Client Component
 * Task: TASK-24-14 - PWA Guide Page (Frontend)
 * 
 * Interactive client component for PWA implementation guide.
 */

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import {
  Book,
  ChevronDown,
  ChevronUp,
  ArrowLeft,
  Copy,
  Check,
  Menu,
  X,
} from 'lucide-react';

// Types for PWA Guide data
interface TOCItem {
  id: string;
  title: string;
}

interface GuideSection {
  id: string;
  title: string;
  content: string;
}

interface PWAGuideData {
  title: string;
  description: string;
  table_of_contents: TOCItem[];
  sections: GuideSection[];
}

interface PWAGuideResponse {
  success: boolean;
  data?: PWAGuideData;
  error?: {
    code: string;
    message: string;
  };
}

// Loading skeleton component
function LoadingSkeleton() {
  return (
    <div data-testid="loading-skeleton" className="animate-pulse space-y-4 p-6">
      <div className="h-8 bg-gray-200 rounded w-1/2" />
      <div className="h-4 bg-gray-200 rounded w-3/4" />
      <div className="h-4 bg-gray-200 rounded w-1/2" />
    </div>
  );
}

// Error state component
function ErrorState({ message, onRetry }: { message: string; onRetry: () => void }) {
  return (
    <div data-testid="error-state" role="alert" className="p-6 text-center">
      <div className="text-red-500 mb-4">
        <svg className="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <p data-testid="error-message" className="text-lg text-gray-700 mb-4">{message}</p>
      <button
        data-testid="retry-button"
        onClick={onRetry}
        className="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 transition-colors"
      >
        Retry
      </button>
    </div>
  );
}

// Empty state component
function EmptyState() {
  return (
    <div data-testid="empty-state" className="p-6 text-center text-gray-500">
      No guide content available
    </div>
  );
}

// Code block component with syntax highlighting and copy
function CodeBlock({ 
  code, 
  language, 
  testId 
}: { 
  code: string; 
  language: string; 
  testId: string;
}) {
  const [copied, setCopied] = useState(false);

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(code);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  };

  return (
    <div data-testid={testId} className="relative group my-4">
      <div data-testid="code-header" className="flex items-center justify-between bg-gray-800 text-gray-300 text-xs px-4 py-2 rounded-t-lg">
        <span data-testid={`language-badge-${language}`} className="uppercase">{language}</span>
        <button
          data-testid={`copy-button-${testId}`}
          onClick={handleCopy}
          className="flex items-center gap-1 hover:text-white transition-colors"
          aria-label="Copy code"
        >
          {copied ? (
            <>
              <Check className="w-4 h-4" />
              <span>Copied!</span>
            </>
          ) : (
            <>
              <Copy className="w-4 h-4" />
              <span>Copy</span>
            </>
          )}
        </button>
      </div>
      <pre data-testid={`code-block-${language}`} className="bg-gray-900 text-gray-100 p-4 rounded-b-lg overflow-x-auto">
        <code className="text-sm">{code}</code>
      </pre>
    </div>
  );
}

// Markdown components configuration
function createMarkdownComponents(sectionId: string, codeBlockIndex: { current: number }) {
  return {
    code: ({ 
      className, 
      children, 
      ...props 
    }: React.HTMLAttributes<HTMLElement> & { className?: string }) => {
      const match = /language-(\w+)/.exec(className || '');
      const isInline = !match && !String(children).includes('\n');
      
      if (isInline) {
        return (
          <code className="bg-gray-100 text-gray-800 px-1.5 py-0.5 rounded text-sm" {...props}>
            {children}
          </code>
        );
      }
      
      const language = match ? match[1] : 'text';
      const code = String(children).replace(/\n$/, '');
      const testId = `code-container-${sectionId}-${codeBlockIndex.current++}`;
      
      return <CodeBlock code={code} language={language} testId={testId} />;
    },
    pre: ({ children }: React.HTMLAttributes<HTMLPreElement>) => {
      return <>{children}</>;
    },
    h1: ({ children }: React.HTMLAttributes<HTMLHeadingElement>) => (
      <h2 className="text-2xl font-bold mt-6 mb-4">{children}</h2>
    ),
    h2: ({ children }: React.HTMLAttributes<HTMLHeadingElement>) => (
      <h3 className="text-xl font-semibold mt-5 mb-3">{children}</h3>
    ),
    h3: ({ children }: React.HTMLAttributes<HTMLHeadingElement>) => (
      <h4 className="text-lg font-semibold mt-4 mb-2">{children}</h4>
    ),
    p: ({ children }: React.HTMLAttributes<HTMLParagraphElement>) => (
      <p className="mb-4 leading-relaxed">{children}</p>
    ),
    ul: ({ children }: React.HTMLAttributes<HTMLUListElement>) => (
      <ul className="list-disc list-inside mb-4 space-y-1">{children}</ul>
    ),
    ol: ({ children }: React.HTMLAttributes<HTMLOListElement>) => (
      <ol className="list-decimal list-inside mb-4 space-y-1">{children}</ol>
    ),
    li: ({ children }: React.HTMLAttributes<HTMLLIElement>) => (
      <li className="ml-2">{children}</li>
    ),
    table: ({ children }: React.HTMLAttributes<HTMLTableElement>) => (
      <div className="overflow-x-auto my-4">
        <table className="min-w-full border border-gray-200">{children}</table>
      </div>
    ),
    thead: ({ children }: React.HTMLAttributes<HTMLTableSectionElement>) => (
      <thead className="bg-gray-50">{children}</thead>
    ),
    th: ({ children }: React.HTMLAttributes<HTMLTableCellElement>) => (
      <th className="border border-gray-200 px-4 py-2 text-left font-semibold">{children}</th>
    ),
    td: ({ children }: React.HTMLAttributes<HTMLTableCellElement>) => (
      <td className="border border-gray-200 px-4 py-2">{children}</td>
    ),
    a: ({ href, children }: React.AnchorHTMLAttributes<HTMLAnchorElement>) => (
      <a href={href} className="text-primary hover:underline" target="_blank" rel="noopener noreferrer">
        {children}
      </a>
    ),
    blockquote: ({ children }: React.HTMLAttributes<HTMLQuoteElement>) => (
      <blockquote className="border-l-4 border-gray-300 pl-4 italic my-4 text-gray-600">
        {children}
      </blockquote>
    ),
  };
}

// Table of Contents Sidebar (Desktop)
function TOCSidebar({ 
  items, 
  activeSection, 
  onItemClick 
}: { 
  items: TOCItem[]; 
  activeSection: string | null;
  onItemClick: (id: string) => void;
}) {
  return (
    <nav aria-label="Table of contents" data-testid="table-of-contents" className="sticky top-4">
      <h2 data-testid="toc-title" className="text-lg font-semibold mb-4">Table of Contents</h2>
      <ul data-testid="toc-list" className="space-y-2">
        {items.map((item) => (
          <li key={item.id} data-testid={`toc-item-${item.id}`}>
            <a
              href={`#section-${item.id}`}
              data-testid={`toc-link-${item.id}`}
              onClick={(e) => {
                e.preventDefault();
                onItemClick(item.id);
              }}
              aria-current={activeSection === item.id ? 'true' : undefined}
              className={`block px-3 py-2 rounded transition-colors ${
                activeSection === item.id
                  ? 'bg-primary/10 text-primary font-medium'
                  : 'hover:bg-gray-100 text-gray-700'
              }`}
            >
              {item.title}
            </a>
          </li>
        ))}
      </ul>
    </nav>
  );
}

// Mobile Accordion Section
function MobileAccordion({ 
  sections, 
  expandedSection, 
  onToggle 
}: { 
  sections: GuideSection[]; 
  expandedSection: string | null;
  onToggle: (id: string) => void;
}) {
  const codeBlockIndex = { current: 0 };
  
  return (
    <div data-testid="mobile-accordion" className="md:hidden space-y-2">
      {sections.map((section) => (
        <div key={section.id} data-testid={`accordion-item-${section.id}`} className="border rounded-lg">
          <button
            data-testid={`accordion-trigger-${section.id}`}
            onClick={() => onToggle(section.id)}
            aria-expanded={expandedSection === section.id}
            aria-controls={`accordion-content-${section.id}`}
            className="w-full flex items-center justify-between p-4 text-left font-medium hover:bg-gray-50"
          >
            <span>{section.title}</span>
            <span data-testid={`accordion-icon-${section.id}`}>
              {expandedSection === section.id ? (
                <ChevronUp className="w-5 h-5" />
              ) : (
                <ChevronDown className="w-5 h-5" />
              )}
            </span>
          </button>
          {expandedSection === section.id && (
            <div
              data-testid={`accordion-content-${section.id}`}
              id={`accordion-content-${section.id}`}
              className="p-4 border-t prose prose-sm max-w-none"
            >
              <ReactMarkdown
                remarkPlugins={[remarkGfm]}
                components={createMarkdownComponents(section.id, codeBlockIndex)}
              >
                {section.content}
              </ReactMarkdown>
            </div>
          )}
        </div>
      ))}
    </div>
  );
}

// Desktop Sections
function DesktopSections({ 
  sections, 
  activeSection,
  onSectionClick 
}: { 
  sections: GuideSection[];
  activeSection: string | null;
  onSectionClick: (id: string) => void;
}) {
  return (
    <div data-testid="desktop-sections" className="hidden md:block space-y-8">
      {sections.map((section) => {
        const codeBlockIndex = { current: 0 };
        return (
          <section
            key={section.id}
            id={`section-${section.id}`}
            data-testid={`section-${section.id}`}
            className="scroll-mt-20"
          >
            <h2
              data-testid={`section-title-${section.id}`}
              className="text-2xl font-bold mb-4"
            >
              {section.title}
            </h2>
            <div data-testid="markdown-content" className="prose prose-lg max-w-none">
              <ReactMarkdown
                remarkPlugins={[remarkGfm]}
                components={createMarkdownComponents(section.id, codeBlockIndex)}
              >
                {section.content}
              </ReactMarkdown>
            </div>
          </section>
        );
      })}
    </div>
  );
}

// Main Client Component
export function PWAGuideClient() {
  const searchParams = useSearchParams();
  const sectionParam = searchParams.get('section');
  
  const [guide, setGuide] = useState<PWAGuideData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeSection, setActiveSection] = useState<string | null>(sectionParam);
  const [expandedAccordion, setExpandedAccordion] = useState<string | null>(sectionParam);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  // Fetch guide data
  useEffect(() => {
    const fetchGuide = async () => {
      try {
        setLoading(true);
        setError(null);
        
        const response = await fetch('/api/v1/pwa/guide');
        const json: PWAGuideResponse = await response.json();
        
        if (json.success === false || json.error) {
          setError(json.error?.message || 'Failed to load guide');
        } else if (json.data) {
          setGuide(json.data);
        }
      } catch (err) {
        setError('Failed to load guide');
      } finally {
        setLoading(false);
      }
    };

    fetchGuide();
  }, []);

  // Handle section click
  const handleSectionClick = useCallback((id: string) => {
    setActiveSection(id);
    setIsMobileMenuOpen(false);
    
    // Scroll to section on desktop
    const element = document.getElementById(`section-${id}`);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, []);

  // Handle accordion toggle
  const handleAccordionToggle = useCallback((id: string) => {
    setExpandedAccordion(current => current === id ? null : id);
  }, []);

  // Handle retry
  const handleRetry = useCallback(() => {
    window.location.reload();
  }, []);

  // Loading state
  if (loading) {
    return (
      <main data-testid="pwa-guide-page" className="min-h-screen">
        <LoadingSkeleton />
      </main>
    );
  }

  // Error state
  if (error) {
    return (
      <main data-testid="pwa-guide-page" className="min-h-screen">
        <ErrorState message={error} onRetry={handleRetry} />
      </main>
    );
  }

  // Empty state
  if (!guide) {
    return (
      <main data-testid="pwa-guide-page" className="min-h-screen">
        <EmptyState />
      </main>
    );
  }

  return (
    <main data-testid="pwa-guide-page" className="min-h-screen bg-gray-50">
      {/* Breadcrumb Navigation */}
      <nav aria-label="Breadcrumb" data-testid="breadcrumb" className="bg-white border-b">
        <div className="container mx-auto px-4 py-3">
          <ol className="flex items-center space-x-2 text-sm">
            <li>
              <a href="/" data-testid="breadcrumb-home" className="text-gray-500 hover:text-gray-700">
                Home
              </a>
            </li>
            <li className="text-gray-400">/</li>
            <li>
              <a href="/dashboard" data-testid="breadcrumb-dashboard" className="text-gray-500 hover:text-gray-700">
                Dashboard
              </a>
            </li>
            <li className="text-gray-400">/</li>
            <li>
              <span data-testid="breadcrumb-current" className="text-gray-900 font-medium">
                PWA Guide
              </span>
            </li>
          </ol>
        </div>
      </nav>

      <div className="container mx-auto px-4 py-6">
        {/* Back to Dashboard Link */}
        <Link
          href="/dashboard"
          data-testid="back-to-dashboard"
          className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6"
        >
          <ArrowLeft className="w-4 h-4 mr-2" data-testid="back-arrow-icon" />
          Back to Dashboard
        </Link>

        {/* Header */}
        <header data-testid="guide-header" className="mb-8">
          <div className="flex items-center gap-3 mb-4">
            <span data-testid="guide-icon" className="text-4xl">
              <Book className="w-10 h-10 text-primary" />
            </span>
            <h1 data-testid="guide-title" className="text-3xl md:text-4xl font-bold">
              {guide.title}
            </h1>
          </div>
          <p data-testid="guide-description" className="text-lg text-gray-600 max-w-3xl">
            {guide.description}
          </p>
        </header>

        {/* Mobile Menu Toggle */}
        <button
          data-testid="mobile-menu-toggle"
          className="md:hidden mb-4 flex items-center gap-2 px-4 py-2 bg-white border rounded-lg"
          onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
          aria-expanded={isMobileMenuOpen}
          aria-controls="toc-container"
        >
          {isMobileMenuOpen ? (
            <X className="w-5 h-5" data-testid="x-icon" />
          ) : (
            <Menu className="w-5 h-5" data-testid="menu-icon" />
          )}
          Table of Contents
        </button>

        {/* Main Content Grid */}
        <div data-testid="content-grid" className="grid grid-cols-1 md:grid-cols-4 gap-8">
          {/* Table of Contents - Sidebar */}
          <aside
            data-testid="toc-container"
            id="toc-container"
            className={`${isMobileMenuOpen ? 'block' : 'hidden md:block'}`}
          >
            <div className="bg-white rounded-lg border p-4">
              <TOCSidebar
                items={guide.table_of_contents}
                activeSection={activeSection}
                onItemClick={handleSectionClick}
              />
            </div>
          </aside>

          {/* Main Content */}
          <article data-testid="guide-content" className="md:col-span-3">
            {/* Mobile Accordion */}
            <MobileAccordion
              sections={guide.sections}
              expandedSection={expandedAccordion}
              onToggle={handleAccordionToggle}
            />

            {/* Desktop Sections */}
            <div className="bg-white rounded-lg border p-6 md:p-8">
              <DesktopSections
                sections={guide.sections}
                activeSection={activeSection}
                onSectionClick={handleSectionClick}
              />
            </div>

            {/* Code Sections Container (for test visibility) */}
            <div data-testid="code-sections" className="hidden">
              {guide.sections.map((section, index) => {
                const codeMatches = section.content.match(/```(\w+)?\n([\s\S]*?)```/g) || [];
                return codeMatches.map((codeBlock, codeIndex) => {
                  const match = codeBlock.match(/```(\w+)?\n([\s\S]*?)```/);
                  if (!match) return null;
                  const language = match[1] || 'text';
                  return (
                    <div key={`${section.id}-${index}-${codeIndex}`}>
                      <pre data-testid={`code-block-${language}`}>
                        <code>{match[2].trim()}</code>
                      </pre>
                    </div>
                  );
                });
              })}
            </div>
          </article>
        </div>
      </div>
    </main>
  );
}
