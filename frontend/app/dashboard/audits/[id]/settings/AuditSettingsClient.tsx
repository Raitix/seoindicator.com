/**
 * Audit Settings Client Component
 *
 * Client-side component for audit settings page.
 *
 * @module app/dashboard/audits/[id]/settings/AuditSettingsClient
 */

"use client";

import * as React from 'react';
import { useRouter, useParams } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Loader2, AlertCircle, Settings } from 'lucide-react';

import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { VisibilitySettings } from '@/components/audit/VisibilitySettings';
import {
  useAuditSettings,
  usePublishAudit,
  useUnpublishAudit,
} from '@/lib/api/audit-settings';
import { trackEvent } from '@/lib/analytics';

// ============================================================
// Types
// ============================================================

interface AuditSettingsClientProps {
  className?: string;
}

// ============================================================
// Component
// ============================================================

/**
 * AuditSettingsClient Component
 *
 * Provides a settings page for individual audits with:
 * - Visibility toggle (Public/Private)
 * - Public URL display when published
 * - Loading and error states
 * - Permission-based access control
 *
 * @param props - Component props
 * @returns JSX element
 */
export function AuditSettingsClient({ className }: AuditSettingsClientProps) {
  const router = useRouter();
  const params = useParams();
  const auditId = params?.id as string;

  // API hooks
  const { audit, isLoading, error, isOwner, mutate } = useAuditSettings(auditId);
  const { publish } = usePublishAudit();
  const { unpublish } = useUnpublishAudit();

  // Local state
  const [isUpdating, setIsUpdating] = React.useState(false);
  const [updateError, setUpdateError] = React.useState<string | null>(null);

  // Redirect unauthorized users
  React.useEffect(() => {
    if (error?.message === 'Unauthorized') {
      router.push('/login');
    }
  }, [error, router]);

  /**
   * Handle publish action
   */
  const handlePublish = async () => {
    if (!audit) return;

    setIsUpdating(true);
    setUpdateError(null);

    try {
      await publish(audit.id);
      await mutate();
      trackEvent('audit_published', { auditId: audit.id, auditHash: audit.hash });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to publish audit';
      setUpdateError(message);
      trackEvent('audit_publish_error', { auditId: audit.id, error: message });
    } finally {
      setIsUpdating(false);
    }
  };

  /**
   * Handle unpublish action
   */
  const handleUnpublish = async () => {
    if (!audit) return;

    setIsUpdating(true);
    setUpdateError(null);

    try {
      await unpublish(audit.id);
      await mutate();
      trackEvent('audit_unpublished', { auditId: audit.id, auditHash: audit.hash });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to unpublish audit';
      setUpdateError(message);
      trackEvent('audit_unpublish_error', { auditId: audit.id, error: message });
    } finally {
      setIsUpdating(false);
    }
  };

  /**
   * Handle retry on error
   */
  const handleRetry = () => {
    mutate();
  };

  // Loading state
  if (isLoading) {
    return (
      <div
        className={cn('min-h-screen bg-slate-950 flex items-center justify-center', className)}
        data-testid="loading-state"
      >
        <div className="text-center space-y-4">
          <Loader2 className="h-8 w-8 animate-spin text-slate-400 mx-auto" data-testid="loader-icon" />
          <p className="text-slate-400">Loading settings...</p>
        </div>
      </div>
    );
  }

  // Error state
  if (error) {
    const isNotFound = error.message === 'Not found';
    const isUnauthorized = error.message === 'Unauthorized';

    // Don't render for unauthorized (redirecting)
    if (isUnauthorized) {
      return null;
    }

    return (
      <div
        className={cn('min-h-screen bg-slate-950 flex items-center justify-center p-4', className)}
        data-testid="error-state"
      >
        <div className="text-center space-y-4 max-w-md">
          <AlertCircle className="h-12 w-12 text-rose-400 mx-auto" data-testid="alert-icon" />
          <h1 className="text-xl font-semibold text-white">
            {isNotFound ? 'Audit Not Found' : 'Error Loading Settings'}
          </h1>
          <p className="text-slate-400">
            {isNotFound
              ? 'The audit you are looking for does not exist or has been deleted.'
              : error.message}
          </p>
          <div className="flex gap-2 justify-center">
            <Button
              onClick={handleRetry}
              variant="outline"
              className="border-slate-700 text-slate-200"
            >
              Retry
            </Button>
            <Link href="/dashboard/audits">
              <Button
                variant="outline"
                className="border-slate-700 text-slate-200"
              >
                Back to Audits
              </Button>
            </Link>
          </div>
        </div>
      </div>
    );
  }

  // No data state
  if (!audit) {
    return (
      <div
        className={cn('min-h-screen bg-slate-950 flex items-center justify-center p-4', className)}
        data-testid="no-data-state"
      >
        <div className="text-center space-y-4">
          <AlertCircle className="h-12 w-12 text-slate-400 mx-auto" />
          <h1 className="text-xl font-semibold text-white">No Audit Data</h1>
          <p className="text-slate-400">Unable to load audit settings.</p>
          <Link href="/dashboard/audits">
            <Button
              variant="outline"
              className="border-slate-700 text-slate-200"
            >
              Back to Audits
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  // Access denied for non-owners
  if (!isOwner) {
    return (
      <div
        className={cn('min-h-screen bg-slate-950 flex items-center justify-center p-4', className)}
        data-testid="access-denied-state"
      >
        <div className="text-center space-y-4">
          <AlertCircle className="h-12 w-12 text-amber-400 mx-auto" />
          <h1 className="text-xl font-semibold text-white">Access Denied</h1>
          <p className="text-slate-400">
            You do not have permission to view these settings.
            Only the audit owner can manage settings.
          </p>
          <Link href="/dashboard/audits">
            <Button
              variant="outline"
              className="border-slate-700 text-slate-200"
            >
              Back to Audits
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  // Generate public hash for URL
  const auditHash = audit.hash || audit.publicHash || '';

  return (
    <main
      className={cn('min-h-screen bg-slate-950 p-4 sm:p-6 lg:p-8', className)}
      role="main"
    >
      <div className="max-w-3xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex items-center gap-4">
          <Link
            href={`/dashboard/audits/${auditId}`}
            className="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-slate-200 transition-colors"
            aria-label="Back to audit details"
          >
            <ArrowLeft className="h-4 w-4" data-testid="arrow-left-icon" />
            Back
          </Link>
        </div>

        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="text-2xl font-semibold text-white flex items-center gap-2">
              <Settings className="h-6 w-6" data-testid="settings-icon" />
              Audit Settings
            </h1>
            <p className="text-sm text-slate-400 mt-1">
              {audit.url}
            </p>
          </div>
        </div>

        {/* Update Error */}
        {updateError && (
          <div
            className="flex items-center gap-2 rounded-lg border border-rose-800 bg-rose-900/20 p-4 text-sm text-rose-400"
            role="alert"
          >
            <AlertCircle className="h-4 w-4 shrink-0" />
            <p>{updateError}</p>
          </div>
        )}

        {/* Visibility Section */}
        <Card
          className="border-slate-800 bg-slate-900/60"
          aria-labelledby="visibility-heading"
        >
          <CardHeader>
            <CardTitle id="visibility-heading" className="text-white">
              Visibility
            </CardTitle>
            <CardDescription className="text-slate-400">
              Control who can view this audit
            </CardDescription>
          </CardHeader>
          <CardContent>
            <VisibilitySettings
              auditId={audit.id}
              auditHash={auditHash}
              isPublic={audit.isPublic}
              onPublish={handlePublish}
              onUnpublish={handleUnpublish}
            />
          </CardContent>
        </Card>

        {/* Additional settings sections can be added here */}

        {/* Loading overlay for updates */}
        {isUpdating && (
          <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            aria-busy="true"
            aria-label="Updating settings"
          >
            <div className="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-3 text-slate-200">
              <Loader2 className="h-4 w-4 animate-spin" />
              Updating...
            </div>
          </div>
        )}
      </div>
    </main>
  );
}
