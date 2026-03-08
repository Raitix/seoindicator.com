/**
 * VisibilitySettings Component
 *
 * Task: TASK-33-22 - Add Visibility Toggle to Audit Settings
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 *
 * Component that provides a visibility toggle for publishing/unpublishing
 * audits from the settings page.
 *
 * @module components/audit/VisibilitySettings
 */

"use client";

import * as React from 'react';
import { Globe, Lock, Copy, Check, Loader2, AlertTriangle } from 'lucide-react';
import { toast } from 'sonner';

import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { trackEvent } from '@/lib/analytics';

// ============================================================
// Types
// ============================================================

export interface VisibilitySettingsProps {
  /** Audit ID */
  auditId: number;
  /** Audit hash for public URL */
  auditHash: string;
  /** Current visibility status */
  isPublic: boolean;
  /** Callback to publish the audit */
  onPublish: () => Promise<void>;
  /** Callback to unpublish the audit */
  onUnpublish: () => Promise<void>;
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Component
// ============================================================

/**
 * VisibilitySettings Component
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
export function VisibilitySettings({
  auditId,
  auditHash,
  isPublic: initialIsPublic,
  onPublish,
  onUnpublish,
  className,
}: VisibilitySettingsProps) {
  // State management
  const [localIsPublic, setLocalIsPublic] = React.useState(initialIsPublic);
  const [isLoading, setIsLoading] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [showConfirmUnpublish, setShowConfirmUnpublish] = React.useState(false);
  const [copied, setCopied] = React.useState(false);

  // Refs for focus management
  const toggleRef = React.useRef<HTMLButtonElement>(null);
  const confirmButtonRef = React.useRef<HTMLButtonElement>(null);

  // Generate unique ID for accessibility
  const headingId = React.useId();
  const statusId = React.useId();

  // Sync with prop changes
  React.useEffect(() => {
    setLocalIsPublic(initialIsPublic);
  }, [initialIsPublic]);

  // Focus confirm button when dialog opens
  React.useEffect(() => {
    if (showConfirmUnpublish && confirmButtonRef.current) {
      confirmButtonRef.current.focus();
    }
  }, [showConfirmUnpublish]);

  // Generate public URL
  const publicUrl = typeof window !== 'undefined'
    ? `${window.location.origin}/audit/${auditHash}`
    : `/audit/${auditHash}`;

  /**
   * Handle toggle switch click
   */
  const handleToggle = async () => {
    if (isLoading) return;

    if (localIsPublic) {
      // Show confirmation dialog before unpublishing
      setShowConfirmUnpublish(true);
    } else {
      // Publish immediately
      await performPublish();
    }
  };

  /**
   * Perform publish operation
   */
  const performPublish = async () => {
    setIsLoading(true);
    setError(null);

    try {
      await onPublish();
      setLocalIsPublic(true);
      toast.success('Audit published successfully');
      trackEvent('audit_published', { auditId, auditHash });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to publish audit';
      setError(message);
      toast.error('Failed to publish audit');
      trackEvent('audit_publish_error', { auditId, error: message });
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
      toast.success('Audit is now private');
      trackEvent('audit_unpublished', { auditId, auditHash });
      // Return focus to toggle
      if (toggleRef.current) {
        toggleRef.current.focus();
      }
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to unpublish audit';
      setError(message);
      toast.error('Failed to unpublish audit');
      trackEvent('audit_unpublish_error', { auditId, error: message });
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
      toast.error('Failed to copy URL');
    }
  };

  /**
   * Handle cancel unpublish
   */
  const handleCancelUnpublish = () => {
    setShowConfirmUnpublish(false);
    // Return focus to toggle
    if (toggleRef.current) {
      toggleRef.current.focus();
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

  return (
    <div
      className={cn('space-y-4', className)}
      data-testid="visibility-settings"
      aria-labelledby={headingId}
    >
      {/* Visibility Toggle Section */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          {localIsPublic ? (
            <Globe className="h-5 w-5 text-emerald-400" data-testid="globe-icon" aria-hidden="true" />
          ) : (
            <Lock className="h-5 w-5 text-slate-400" data-testid="lock-icon" aria-hidden="true" />
          )}
          <div>
            <h3 id={headingId} className="text-sm font-medium text-white">Visibility</h3>
            <p
              className="text-xs text-slate-400"
              id={statusId}
              aria-live="polite"
            >
              {localIsPublic ? 'Public - Anyone with the link can view' : 'Private - Only you can view'}
            </p>
          </div>
        </div>
        <div className="flex items-center gap-3">
          {/* Status Badge */}
          <span
            className={cn(
              'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
              localIsPublic
                ? 'bg-emerald-500/20 text-emerald-400'
                : 'bg-slate-700 text-slate-300'
            )}
            data-testid="visibility-badge"
          >
            {localIsPublic ? 'Public' : 'Private'}
          </span>
          {/* Toggle Switch */}
          <Switch
            ref={toggleRef}
            checked={localIsPublic}
            onCheckedChange={handleToggle}
            disabled={isLoading}
            aria-label="Toggle public visibility"
          />
        </div>
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
              className="bg-slate-800/50 border-slate-700 text-slate-200"
              data-testid="public-url-input"
              aria-label="Public audit URL"
            />
            <Button
              type="button"
              variant="outline"
              size="icon"
              onClick={handleCopy}
              className="shrink-0 border-slate-700 hover:bg-slate-800"
              data-testid="copy-url-button"
              aria-label="Copy public URL"
            >
              {copied ? (
                <Check className="h-4 w-4 text-emerald-400" data-testid="check-icon" />
              ) : (
                <Copy className="h-4 w-4" data-testid="copy-icon" />
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
          className="flex items-center gap-2 py-2 text-sm text-slate-400"
          data-testid="loading-indicator"
        >
          <Loader2 className="h-4 w-4 animate-spin" data-testid="loader-icon" />
          <span>Updating...</span>
        </div>
      )}

      {/* Error Message */}
      {error && (
        <div
          className="flex items-start gap-2 rounded-lg border border-rose-800 bg-rose-900/20 p-3 text-sm text-rose-400"
          role="alert"
          data-testid="error-message"
        >
          <AlertTriangle className="h-4 w-4 mt-0.5 shrink-0" data-testid="alert-icon" />
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

      {/* Status Live Region */}
      <div role="status" className="sr-only">
        {localIsPublic ? 'Audit is public' : 'Audit is private'}
      </div>

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

            {/* Warning about embedded badges */}
            <div
              className="mt-3 flex items-start gap-2 rounded-lg border border-yellow-500/50 bg-yellow-500/10 p-3 text-sm text-yellow-400"
              role="alert"
            >
              <AlertTriangle className="h-4 w-4 mt-0.5 shrink-0" />
              <p>
                <strong>Warning:</strong> Any embedded badges will no longer work after unpublishing.
              </p>
            </div>

            <div className="mt-4 flex justify-end gap-2">
              <Button
                type="button"
                variant="outline"
                onClick={handleCancelUnpublish}
                disabled={isLoading}
                className="border-slate-700 text-slate-200 hover:bg-slate-800"
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
                ref={confirmButtonRef}
              >
                {isLoading ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" data-testid="loader-icon" />
                    Unpublishing...
                  </>
                ) : (
                  'Unpublish'
                )}
              </Button>
            </div>

            {/* Error in dialog */}
            {error && showConfirmUnpublish && (
              <div
                className="mt-3 flex items-start gap-2 rounded-lg border border-rose-800 bg-rose-900/20 p-3 text-sm text-rose-400"
                role="alert"
              >
                <AlertTriangle className="h-4 w-4 mt-0.5 shrink-0" />
                <p>Failed to unpublish. Please try again.</p>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

export default VisibilitySettings;
