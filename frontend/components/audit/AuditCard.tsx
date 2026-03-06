/**
 * AuditCard Component
 * Task: TASK-33-20
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 *
 * Card component that displays audit information with a share button
 * for publishing/unpublishing audits.
 *
 * @module components/audit/AuditCard
 */

"use client";

import * as React from 'react';
import { Share2, Globe, Lock, ExternalLink } from 'lucide-react';

import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { ShareAuditModal } from './ShareAuditModal';

/**
 * Props for the AuditCard component
 */
export interface AuditCardProps {
  /** Audit ID */
  id: number;
  /** Audit hash for public URL */
  hash: string;
  /** Audit URL being analyzed */
  url: string;
  /** Audit score (0-100) */
  score: number;
  /** Current visibility status */
  isPublic: boolean;
  /** Whether the current user owns this audit */
  isOwner: boolean;
  /** Callback to publish the audit */
  onPublish?: () => Promise<void>;
  /** Callback to unpublish the audit */
  onUnpublish?: () => Promise<void>;
  /** Click handler for the card */
  onClick?: () => void;
  /** Additional CSS classes */
  className?: string;
}

/**
 * Get score color based on value
 */
function getScoreColor(score: number): string {
  if (score >= 80) return 'text-green-500';
  if (score >= 60) return 'text-blue-500';
  if (score >= 40) return 'text-yellow-500';
  if (score >= 20) return 'text-orange-500';
  return 'text-red-500';
}

/**
 * Get score background color
 */
function getScoreBgColor(score: number): string {
  if (score >= 80) return 'bg-green-500';
  if (score >= 60) return 'bg-blue-500';
  if (score >= 40) return 'bg-yellow-500';
  if (score >= 20) return 'bg-orange-500';
  return 'bg-red-500';
}

/**
 * Truncate URL for display
 */
function truncateUrl(url: string, maxLength: number = 40): string {
  try {
    const urlObj = new URL(url);
    const display = urlObj.hostname + urlObj.pathname;
    if (display.length <= maxLength) {
      return display;
    }
    return display.substring(0, maxLength - 3) + '...';
  } catch {
    return url.length > maxLength ? url.substring(0, maxLength - 3) + '...' : url;
  }
}

/**
 * AuditCard Component
 * 
 * Displays audit information with:
 * - URL and score
 * - Visibility indicator (Public/Private)
 * - Share button for owners
 * - Integration with ShareAuditModal
 * 
 * @param props - Component props
 * @returns JSX element
 */
export function AuditCard({
  id,
  hash,
  url,
  score,
  isPublic,
  isOwner,
  onPublish,
  onUnpublish,
  onClick,
  className,
}: AuditCardProps) {
  // State for share modal
  const [showShareModal, setShowShareModal] = React.useState(false);
  
  // Ref for focus management
  const shareButtonRef = React.useRef<HTMLButtonElement>(null);

  const scoreColor = getScoreColor(score);
  const scoreBgColor = getScoreBgColor(score);
  const displayUrl = truncateUrl(url);

  /**
   * Handle share button click
   */
  const handleShareClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    setShowShareModal(true);
  };

  /**
   * Handle modal close
   */
  const handleModalClose = () => {
    setShowShareModal(false);
    // Return focus to share button
    shareButtonRef.current?.focus();
  };

  /**
   * Default publish handler
   */
  const handlePublish = async () => {
    if (onPublish) {
      await onPublish();
    }
  };

  /**
   * Default unpublish handler
   */
  const handleUnpublish = async () => {
    if (onUnpublish) {
      await onUnpublish();
    }
  };

  return (
    <>
      <div
        data-testid="audit-card"
        data-audit-id={id}
        className={cn(
          'rounded-lg border border-slate-800 bg-slate-900/60 p-4 transition-colors',
          onClick && 'cursor-pointer hover:bg-slate-800/60',
          className
        )}
        onClick={onClick}
        onKeyDown={(e) => {
          if (onClick && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            onClick();
          }
        }}
        tabIndex={onClick ? 0 : undefined}
        role={onClick ? 'button' : undefined}
      >
        <div className="flex items-start justify-between gap-4">
          {/* Left side - URL and info */}
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2">
              <span
                className="text-sm font-medium text-white truncate"
                data-testid="audit-url"
                title={url}
              >
                {displayUrl}
              </span>
              <a
                href={url}
                target="_blank"
                rel="noopener noreferrer"
                className="text-slate-400 hover:text-slate-300"
                onClick={(e) => e.stopPropagation()}
                aria-label={`Open ${url} in new tab`}
              >
                <ExternalLink className="h-3.5 w-3.5" />
              </a>
            </div>

            <div className="flex items-center gap-3 mt-2">
              {/* Score */}
              <span
                className={cn('text-xs font-medium', scoreColor)}
                data-testid="audit-score"
              >
                {score}/100
              </span>

              {/* Visibility indicator */}
              <span
                className="flex items-center gap-1 text-xs text-slate-400"
                data-testid="audit-visibility"
              >
                {isPublic ? (
                  <>
                    <Globe className="h-3 w-3" aria-hidden="true" />
                    Public
                  </>
                ) : (
                  <>
                    <Lock className="h-3 w-3" aria-hidden="true" />
                    Private
                  </>
                )}
              </span>
            </div>
          </div>

          {/* Right side - Score circle and actions */}
          <div className="flex items-center gap-3">
            {/* Score Circle */}
            <div
              className={cn(
                'flex h-12 w-12 items-center justify-center rounded-full text-sm font-bold text-white',
                scoreBgColor
              )}
              aria-label={`Score: ${score} out of 100`}
            >
              {score}
            </div>

            {/* Share button - only for owners */}
            {isOwner && (
              <Button
                ref={shareButtonRef}
                variant="ghost"
                size="sm"
                className="h-8 gap-1.5 text-slate-400 hover:text-white hover:bg-slate-800"
                onClick={handleShareClick}
                data-testid="share-button"
                aria-label="Share audit"
              >
                <Share2 className="h-4 w-4" />
                <span className="hidden sm:inline">Share</span>
              </Button>
            )}
          </div>
        </div>
      </div>

      {/* Share Modal */}
      {isOwner && (
        <ShareAuditModal
          isOpen={showShareModal}
          onClose={handleModalClose}
          auditId={id}
          auditHash={hash}
          auditUrl={url}
          score={score}
          isPublic={isPublic}
          onPublish={handlePublish}
          onUnpublish={handleUnpublish}
        />
      )}
    </>
  );
}

export default AuditCard;
