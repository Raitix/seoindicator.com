/**
 * GuideChecklist Component
 * Task: TASK-20-29 - Render Guide Content in Page Component
 * 
 * Renders checklist items with priority-based styling.
 */

import React from 'react';
import type { ChecklistItem } from '@/lib/guides/types';

interface GuideChecklistProps {
  items: ChecklistItem[];
}

const priorityStyles: Record<string, string> = {
  critical: 'bg-red-500/10 border-red-500/30 text-red-400',
  important: 'bg-amber-500/10 border-amber-500/30 text-amber-400',
  recommended: 'bg-blue-500/10 border-blue-500/30 text-blue-400',
};

const priorityLabels: Record<string, string> = {
  critical: 'Critical',
  important: 'Important',
  recommended: 'Recommended',
};

const priorityIcons: Record<string, string> = {
  critical: '🔴',
  important: '🟡',
  recommended: '🔵',
};

/**
 * Renders a list of checklist items with priority-based styling
 */
export default function GuideChecklist({ items }: GuideChecklistProps) {
  if (!items || items.length === 0) {
    return null;
  }

  return (
    <section data-testid="guide-checklist" className="mt-8">
      <h2
        data-testid="checklist-heading"
        className="text-2xl font-bold mb-4 text-white"
      >
        SEO Checklist
      </h2>
      <ul data-testid="checklist-items" className="space-y-3">
        {items.map((item, index) => {
          const priority = item.priority || 'recommended';
          const styleClass = priorityStyles[priority] || priorityStyles.recommended;
          const icon = priorityIcons[priority] || priorityIcons.recommended;
          const label = priorityLabels[priority] || 'Recommended';

          return (
            <li
              key={index}
              data-testid={`checklist-item-${index}`}
              data-priority={priority}
              className={`flex items-start gap-3 p-4 rounded-lg border ${styleClass}`}
            >
              {/* Priority icon */}
              <span
                data-testid={`checklist-icon-${index}`}
                className="flex-shrink-0 mt-0.5"
                aria-hidden="true"
              >
                {icon}
              </span>

              {/* Priority badge */}
              <span
                data-testid={`priority-badge-${index}`}
                className="flex-shrink-0 px-2 py-0.5 text-xs font-medium rounded uppercase bg-slate-800/50"
              >
                {label}
              </span>

              {/* Item text */}
              <span
                data-testid={`item-text-${index}`}
                className="flex-1 text-white"
              >
                {item.text}
              </span>
            </li>
          );
        })}
      </ul>
    </section>
  );
}
