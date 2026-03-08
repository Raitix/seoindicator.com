/**
 * BadgeManagementRow Component - TASK-33-24
 *
 * Table row for displaying a single public audit with badge preview,
 * analytics, visibility toggle, and embed code access.
 *
 * @module components/dashboard/BadgeManagementRow
 */

'use client';

import * as React from 'react';
import { Globe, Lock, Code, ExternalLink, Loader2 } from 'lucide-react';

import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { BadgePreview } from '@/components/audit/BadgePreview';
import type { PublicAudit } from '@/lib/types/badges';

// ============================================================
// Types
// ============================================================

export interface BadgeManagementRowProps {
  /** Audit data */
  audit: PublicAudit;
  /** Callback when visibility is toggled */
  onToggleVisibility: (auditId: number, isPublic: boolean) => Promise<void>;
  /** Callback when embed code is requested */
  onGetEmbedCode: (hash: string) => void;
}

// ============================================================
// Component
// ============================================================

export function BadgeManagementRow({
  audit,
  onToggleVisibility,
  onGetEmbedCode,
}: BadgeManagementRowProps) {
  const [isToggling, setIsToggling] = React.useState(false);

  // Handle visibility toggle
  const handleToggle = async () => {
    setIsToggling(true);
    try {
      await onToggleVisibility(audit.id, !audit.isPublic);
    } finally {
      setIsToggling(false);
    }
  };

  return (
    <tr data-testid={`badge-row-${audit.id}`} className="border-b border-slate-700/50 hover:bg-slate-800/50">
      {/* Site URL */}
      <td className="py-4 pr-4">
        <a
          href={`/audit/${audit.hash}`}
          data-testid={`audit-link-${audit.id}`}
          className="text-blue-400 hover:text-blue-300 hover:underline"
        >
          {audit.siteUrl}
        </a>
      </td>

      {/* Badge Preview */}
      <td className="py-4 pr-4">
        <div data-testid={`badge-preview-${audit.hash}`}>
          <BadgePreview
            auditHash={audit.hash}
            score={audit.score}
            siteUrl={audit.siteUrl}
          />
        </div>
      </td>

      {/* Impressions */}
      <td className="py-4 pr-4 text-slate-300" data-testid={`impressions-${audit.id}`}>
        {audit.analytics.impressions.toLocaleString()}
      </td>

      {/* Clicks */}
      <td className="py-4 pr-4 text-slate-300" data-testid={`clicks-${audit.id}`}>
        {audit.analytics.clicks.toLocaleString()}
      </td>

      {/* CTR */}
      <td className="py-4 pr-4 text-slate-300" data-testid={`ctr-${audit.id}`}>
        {audit.analytics.ctr.toFixed(2)}%
      </td>

      {/* Visibility Toggle */}
      <td className="py-4 pr-4">
        <button
          data-testid={`toggle-visibility-${audit.id}`}
          onClick={handleToggle}
          disabled={isToggling}
          aria-label={audit.isPublic ? 'Make private' : 'Make public'}
          aria-pressed={audit.isPublic}
          className={cn(
            'inline-flex items-center justify-center rounded-md p-2 transition-colors',
            'hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500',
            'disabled:cursor-not-allowed disabled:opacity-50',
            'min-h-[44px] min-w-[44px]' // Touch-friendly size
          )}
        >
          {isToggling ? (
            <Loader2 className="h-5 w-5 animate-spin text-slate-400" data-testid="loader-icon" />
          ) : audit.isPublic ? (
            <Globe className="h-5 w-5 text-green-400" data-testid="globe-icon" />
          ) : (
            <Lock className="h-5 w-5 text-slate-400" data-testid="lock-icon" />
          )}
        </button>
      </td>

      {/* Embed Code */}
      <td className="py-4 pr-4">
        <Button
          variant="outline"
          size="sm"
          data-testid={`get-embed-${audit.id}`}
          onClick={() => onGetEmbedCode(audit.hash)}
          aria-label="Get embed code"
          className="border-slate-700 text-slate-300 hover:bg-slate-700 hover:text-white"
        >
          <Code className="h-4 w-4 mr-2" />
          Embed Code
        </Button>
      </td>

      {/* Actions */}
      <td className="py-4">
        <a
          href={`/dashboard/audits/${audit.id}`}
          data-testid={`view-audit-${audit.id}`}
          className="inline-flex items-center gap-1 text-sm text-blue-400 hover:text-blue-300 hover:underline"
        >
          View Audit
          <ExternalLink className="h-3 w-3" data-testid="external-link-icon" />
        </a>
      </td>
    </tr>
  );
}

export default BadgeManagementRow;
