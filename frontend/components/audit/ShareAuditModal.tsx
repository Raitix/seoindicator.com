/**
 * ShareAuditModal Component
 * Task: TASK-33-20
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 *
 * Modal component that allows users to publish/unpublish audits
 * and get the public URL for sharing.
 *
 * @module components/audit/ShareAuditModal
 */

"use client";

import * as React from 'react';
import { Share2, Copy, Check, X, Loader2, AlertCircle, Globe, Lock } from 'lucide-react';

import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog';
import { trackEvent } from '@/lib/analytics';

/**
 * Props for the ShareAuditModal component
 */
export interface ShareAuditModalProps {
  /** Whether the modal is open */
  isOpen: boolean;
  /** Callback when modal closes */
  onClose: () => void;
  /** Audit ID */
  auditId: number;
  /** Audit hash for public URL */
  auditHash: string;
  /** Audit URL being analyzed */
  auditUrl: string;
  /** Audit score (0-100) */
  score: number;
  /** Current visibility status */
  isPublic: boolean;
  /** Callback to publish the audit */
  onPublish: () => Promise<void>;
  /** Callback to unpublish the audit */
  onUnpublish: () => Promise<void>;
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
 * ShareAuditModal Component
 * 
 * Provides UI for:
 * - Viewing current visibility status (Public/Private)
 * - Toggling visibility with confirmation for unpublish
 * - Copying public URL when published
 * - Loading states and error handling
 * 
 * @param props - Component props
 * @returns JSX element
 */
export function ShareAuditModal({
  isOpen,
  onClose,
  auditId,
  auditHash,
  auditUrl,
  score,
  isPublic: initialIsPublic,
  onPublish,
  onUnpublish,
  className,
}: ShareAuditModalProps) {
  // State management
  const [localIsPublic, setLocalIsPublic] = React.useState(initialIsPublic);
  const [isLoading, setIsLoading] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [showConfirmUnpublish, setShowConfirmUnpublish] = React.useState(false);
  const [copied, setCopied] = React.useState(false);
  
  // Ref for focus management
  const closeButtonRef = React.useRef<HTMLButtonElement>(null);
  const toggleRef = React.useRef<HTMLButtonElement>(null);

  // Sync with prop changes
  React.useEffect(() => {
    setLocalIsPublic(initialIsPublic);
  }, [initialIsPublic]);

  // Reset state when modal closes
  React.useEffect(() => {
    if (!isOpen) {
      setError(null);
      setShowConfirmUnpublish(false);
      setCopied(false);
    }
  }, [isOpen]);

  // Generate public URL
  const publicUrl = typeof window !== 'undefined' 
    ? `${window.location.origin}/audit/${auditHash}`
    : `/audit/${auditHash}`;

  /**
   * Handle toggle switch click
   */
  const handleToggle = async () => {
    if (localIsPublic) {
      // Show confirmation dialog before unpublishing
      setShowConfirmUnpublish(true);
    } else {
      // Publish immediately with optimistic update
      await performPublish();
    }
  };

  /**
   * Perform publish operation
   */
  const performPublish = async () => {
    setIsLoading(true);
    setError(null);
    
    // Optimistic UI update
    setLocalIsPublic(true);
    
    try {
      await onPublish();
      trackEvent('audit_published', { auditId, auditHash });
    } catch (err) {
      // Revert optimistic update
      setLocalIsPublic(false);
      setError('Failed to publish audit. Please try again.');
      trackEvent('audit_publish_error', { auditId, error: String(err) });
    } finally {
      setIsLoading(false);
    }
  };

  /**
   * Perform unpublish operation
   */
  const performUnpublish = async () => {
    setIsLoading(true);
    setError(null);
    
    try {
      await onUnpublish();
      setLocalIsPublic(false);
      setShowConfirmUnpublish(false);
      trackEvent('audit_unpublished', { auditId, auditHash });
    } catch (err) {
      setError('Failed to unpublish audit. Please try again.');
      trackEvent('audit_unpublish_error', { auditId, error: String(err) });
    } finally {
      setIsLoading(false);
    }
  };

  /**
   * Handle copy URL to clipboard
   */
  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(publicUrl);
      setCopied(true);
      trackEvent('audit_url_copy', { auditId, auditHash });
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      setError('Failed to copy URL');
    }
  };

  /**
   * Handle modal close
   */
  const handleClose = () => {
    if (!isLoading) {
      onClose();
    }
  };

  /**
   * Handle retry after error
   */
  const handleRetry = async () => {
    setError(null);
    if (localIsPublic) {
      await performUnpublish();
    } else {
      await performPublish();
    }
  };

  const scoreColor = getScoreColor(score);
  const scoreBgColor = getScoreBgColor(score);

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && handleClose()}>
      <DialogContent
        className={cn(
          'sm:max-w-md',
          className
        )}
        data-testid="share-audit-modal"
        onPointerDownOutside={(e) => {
          if (isLoading) e.preventDefault();
        }}
        onEscapeKeyDown={(e) => {
          if (isLoading) e.preventDefault();
        }}
      >
        {/* Modal Header */}
        <DialogHeader>
          <DialogTitle id="modal-title" className="flex items-center gap-2">
            <Share2 className="h-5 w-5" aria-hidden="true" />
            Share Audit
          </DialogTitle>
          <DialogDescription>
            Share your SEO audit results with others
          </DialogDescription>
        </DialogHeader>

        {/* Modal Content */}
        <div className="space-y-4 py-4" data-testid="modal-content">
          {/* Audit Info */}
          <div className="flex items-center justify-between rounded-lg border border-slate-700 bg-slate-800/50 p-3" data-testid="audit-info">
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-white truncate" data-testid="audit-url">
                {auditUrl}
              </p>
              <p className={cn('text-xs', scoreColor)} data-testid="audit-score">
                Score: {score}/100
              </p>
            </div>
            <div
              className={cn(
                'ml-3 flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white',
                scoreBgColor
              )}
              aria-label={`Score: ${score} out of 100`}
            >
              {score}
            </div>
          </div>

          {/* Visibility Toggle */}
          <div className="flex items-center justify-between rounded-lg border border-slate-700 bg-slate-800/50 p-4">
            <div className="flex items-center gap-3">
              {localIsPublic ? (
                <Globe className="h-5 w-5 text-emerald-400" aria-hidden="true" />
              ) : (
                <Lock className="h-5 w-5 text-slate-400" aria-hidden="true" />
              )}
              <div>
                <p className="text-sm font-medium text-white">Visibility</p>
                <p
                  className="text-xs text-slate-400"
                  data-testid="visibility-status"
                  aria-live="polite"
                >
                  {localIsPublic ? 'Public - Anyone with the link can view' : 'Private - Only you can view'}
                </p>
              </div>
            </div>
            <Switch
              ref={toggleRef}
              checked={localIsPublic}
              onCheckedChange={handleToggle}
              disabled={isLoading}
              aria-label="Toggle public visibility"
              data-testid="visibility-toggle"
            />
          </div>

          {/* Public URL Section */}
          {localIsPublic && (
            <div className="space-y-2" data-testid="public-url-section">
              <label className="text-sm font-medium text-slate-300">
                Public URL
              </label>
              <div className="flex gap-2">
                <Input
                  type="text"
                  value={publicUrl}
                  readOnly
                  className="bg-slate-800/50 border-slate-700"
                  data-testid="public-url-input"
                  aria-label="Public audit URL"
                />
                <Button
                  type="button"
                  variant="outline"
                  size="icon"
                  onClick={handleCopy}
                  className="shrink-0"
                  data-testid="copy-url-button"
                  aria-label="Copy public URL"
                >
                  {copied ? (
                    <Check className="h-4 w-4 text-emerald-400" />
                  ) : (
                    <Copy className="h-4 w-4" />
                  )}
                </Button>
              </div>
              {copied && (
                <p className="text-xs text-emerald-400">Copied to clipboard!</p>
              )}
            </div>
          )}

          {/* Loading Indicator */}
          {isLoading && (
            <div
              className="flex items-center justify-center gap-2 py-2 text-sm text-slate-400"
              data-testid="loading-indicator"
            >
              <Loader2 className="h-4 w-4 animate-spin" />
              {localIsPublic ? 'Publishing...' : 'Unpublishing...'}
            </div>
          )}

          {/* Error Message */}
          {error && (
            <div
              className="flex items-start gap-2 rounded-lg border border-rose-800 bg-rose-900/20 p-3 text-sm text-rose-400"
              role="alert"
              data-testid="error-message"
            >
              <AlertCircle className="h-4 w-4 mt-0.5 shrink-0" />
              <div className="flex-1">
                <p>{error}</p>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={handleRetry}
                  className="mt-2 h-7 px-2 text-xs text-rose-300 hover:text-rose-200 hover:bg-rose-900/30"
                  data-testid="retry-button"
                >
                  Try again
                </Button>
              </div>
            </div>
          )}
        </div>

        {/* Modal Footer */}
        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            onClick={handleClose}
            disabled={isLoading}
            data-testid="cancel-button"
            ref={closeButtonRef}
          >
            Done
          </Button>
        </DialogFooter>

        {/* Confirmation Dialog for Unpublish */}
        {showConfirmUnpublish && (
          <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
            data-testid="confirm-unpublish-dialog"
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="unpublish-title"
            aria-describedby="unpublish-description"
          >
            <div className="w-full max-w-sm rounded-lg border border-slate-700 bg-slate-900 p-6 shadow-xl">
              <h3 id="unpublish-title" className="text-lg font-semibold text-white">
                Unpublish Audit?
              </h3>
              <p id="unpublish-description" className="mt-2 text-sm text-slate-400">
                This will make the audit private. Anyone with the link will no longer be able to access it.
              </p>
              <div className="mt-4 flex justify-end gap-2">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setShowConfirmUnpublish(false)}
                  disabled={isLoading}
                  data-testid="cancel-unpublish-button"
                >
                  Cancel
                </Button>
                <Button
                  type="button"
                  variant="destructive"
                  onClick={performUnpublish}
                  disabled={isLoading}
                  data-testid="confirm-unpublish-button"
                >
                  {isLoading ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Unpublishing...
                    </>
                  ) : (
                    'Unpublish'
                  )}
                </Button>
              </div>
            </div>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}

export default ShareAuditModal;
