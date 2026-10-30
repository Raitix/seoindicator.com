/**
 * GuideSection Component
 * Task: TASK-20-29 - Render Guide Content in Page Component
 * 
 * Renders a guide section with markdown content and code examples.
 */

import React from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import type { GuideSection as GuideSectionType, CodeExample } from '@/lib/guides/types';
import GuideCodeBlock from './GuideCodeBlock';

interface GuideSectionProps {
  section: GuideSectionType;
  index: number;
}

/**
 * Custom heading components that shift heading levels down
 * H1 -> H2 (page title is H1)
 * H2 -> H3
 * H3 -> H4
 * etc.
 */
const headingComponents = {
  h1: ({ children, ...props }: React.HTMLAttributes<HTMLHeadingElement>) => (
    <h2 className="text-2xl font-bold text-white mt-8 mb-4" {...props}>
      {children}
    </h2>
  ),
  h2: ({ children, ...props }: React.HTMLAttributes<HTMLHeadingElement>) => (
    <h3 className="text-xl font-semibold text-white mt-6 mb-3" {...props}>
      {children}
    </h3>
  ),
  h3: ({ children, ...props }: React.HTMLAttributes<HTMLHeadingElement>) => (
    <h4 className="text-lg font-medium text-slate-200 mt-4 mb-2" {...props}>
      {children}
    </h4>
  ),
  h4: ({ children, ...props }: React.HTMLAttributes<HTMLHeadingElement>) => (
    <h5 className="text-base font-medium text-slate-300 mt-3 mb-2" {...props}>
      {children}
    </h5>
  ),
  h5: ({ children, ...props }: React.HTMLAttributes<HTMLHeadingElement>) => (
    <h6 className="text-sm font-medium text-slate-400 mt-2 mb-1" {...props}>
      {children}
    </h6>
  ),
  h6: ({ children, ...props }: React.HTMLAttributes<HTMLHeadingElement>) => (
    <h6 className="text-sm font-medium text-slate-500 mt-2 mb-1" {...props}>
      {children}
    </h6>
  ),
};

/**
 * Custom paragraph component
 */
const p = ({ children, ...props }: React.HTMLAttributes<HTMLParagraphElement>) => (
  <p className="text-slate-300 leading-relaxed mb-4" {...props}>
    {children}
  </p>
);

/**
 * Custom list components
 */
const ul = ({ children, ...props }: React.HTMLAttributes<HTMLUListElement>) => (
  <ul className="list-disc list-inside text-slate-300 mb-4 space-y-2" {...props}>
    {children}
  </ul>
);

const ol = ({ children, ...props }: React.OlHTMLAttributes<HTMLOListElement>) => (
  <ol className="list-decimal list-inside text-slate-300 mb-4 space-y-2" {...props}>
    {children}
  </ol>
);

const li = ({ children, ...props }: React.HTMLAttributes<HTMLLIElement>) => (
  <li className="text-slate-300" {...props}>
    {children}
  </li>
);

/**
 * Custom blockquote component
 */
const blockquote = ({ children, ...props }: React.BlockquoteHTMLAttributes<HTMLQuoteElement>) => (
  <blockquote
    className="border-l-4 border-emerald-500 pl-4 py-2 my-4 bg-slate-800/50 rounded-r text-slate-300 italic"
    {...props}
  >
    {children}
  </blockquote>
);

/**
 * Custom inline code component
 */
const code = ({ children, className, ...props }: React.HTMLAttributes<HTMLElement>) => {
  // If it has a language class, it's a code block (handled by pre)
  // Otherwise it's inline code
  const isInline = !className;
  
  if (isInline) {
    return (
      <code
        className="bg-slate-800 text-emerald-400 px-1.5 py-0.5 rounded text-sm font-mono"
        {...props}
      >
        {children}
      </code>
    );
  }
  
  return (
    <code className={className} {...props}>
      {children}
    </code>
  );
};

/**
 * Custom link component
 */
const a = ({ children, href, ...props }: React.AnchorHTMLAttributes<HTMLAnchorElement>) => (
  <a
    href={href}
    className="text-emerald-400 hover:text-emerald-300 underline underline-offset-2 transition-colors"
    target={href?.startsWith('http') ? '_blank' : undefined}
    rel={href?.startsWith('http') ? 'noopener noreferrer' : undefined}
    {...props}
  >
    {children}
  </a>
);

/**
 * Custom table components
 */
const table = ({ children, ...props }: React.TableHTMLAttributes<HTMLTableElement>) => (
  <div className="overflow-x-auto my-4">
    <table className="min-w-full border border-slate-700 rounded-lg" {...props}>
      {children}
    </table>
  </div>
);

const thead = ({ children, ...props }: React.HTMLAttributes<HTMLTableSectionElement>) => (
  <thead className="bg-slate-800" {...props}>
    {children}
  </thead>
);

const tbody = ({ children, ...props }: React.HTMLAttributes<HTMLTableSectionElement>) => (
  <tbody className="divide-y divide-slate-700" {...props}>
    {children}
  </tbody>
);

const tr = ({ children, ...props }: React.HTMLAttributes<HTMLTableRowElement>) => (
  <tr className="hover:bg-slate-800/50" {...props}>
    {children}
  </tr>
);

const th = ({ children, ...props }: React.ThHTMLAttributes<HTMLTableCellElement>) => (
  <th className="px-4 py-3 text-left text-sm font-medium text-slate-300" {...props}>
    {children}
  </th>
);

const td = ({ children, ...props }: React.TdHTMLAttributes<HTMLTableCellElement>) => (
  <td className="px-4 py-3 text-sm text-slate-400" {...props}>
    {children}
  </td>
);

/**
 * Renders a single guide section
 */
export default function GuideSection({ section, index }: GuideSectionProps) {
  const { title, content, codeExamples } = section;

  return (
    <section
      data-testid={`guide-section-${index}`}
      className="mb-12"
    >
      {/* Section title as H2 */}
      <h2
        data-testid={`section-title-${index}`}
        className="text-2xl font-bold text-white mb-4"
      >
        {title}
      </h2>

      {/* Markdown content */}
      <div data-testid={`section-content-${index}`} className="prose prose-invert max-w-none">
        <ReactMarkdown
          remarkPlugins={[remarkGfm]}
          components={{
            ...headingComponents,
            p,
            ul,
            ol,
            li,
            blockquote,
            code,
            a,
            table,
            thead,
            tbody,
            tr,
            th,
            td,
          }}
        >
          {content}
        </ReactMarkdown>
      </div>

      {/* Code examples */}
      {codeExamples && codeExamples.length > 0 && (
        <div data-testid={`section-code-examples-${index}`} className="mt-6 space-y-4">
          {codeExamples.map((example, codeIndex) => (
            <GuideCodeBlock
              key={codeIndex}
              example={example}
              index={codeIndex}
            />
          ))}
        </div>
      )}
    </section>
  );
}
