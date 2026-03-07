/**
 * Audit Settings Page
 * 
 * Task: TASK-33-22 - Add Visibility Toggle to Audit Settings
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 *
 * Settings page for managing audit configuration, including visibility toggle.
 *
 * @module app/sites/[siteId]/audits/[auditId]/settings/page
 */

"use client";

import * as React from 'react';
import { useParams, useRouter } from 'next/navigation';
import { ArrowLeft, Loader2, AlertCircle, Settings } from 'lucide-react';

import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { VisibilitySettings } from '@/components/audit/VisibilitySettings';
import {
  useAuditSettings,
  usePublishAudit,
  useUnpublishAudit,
} from '@/lib/api/audit-settings';

// ============================================================
// Types
// ============================================================

export interface AuditSettingsPageProps {
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Component
// ============================================================

export function AuditSettingsPage({ className }: AuditSettingsPageProps) {
  const params = useParams();
  const router = useRouter();

  // Get route params
  const siteId = params.siteId as string;
  const auditId = params.auditId as string;

  // State
  const [isRedirecting, setIsRedirecting] = React.useState(false);

  // API hooks
  const {
    audit,
    isLoading,
    error,
    isOwner,
    mutate,
  } = useAuditSettings(auditId);

  const { publish } = usePublishAudit();
  const { unpublish } = useUnpublishAudit();

  /**
   * Handle publish action
   */
  const handlePublish = async () => {
    await publish(auditId);
    mutate();
  };

  /**
   * Handle unpublish action
   */
  const handleUnpublish = async () => {
    await unpublish(auditId);
    mutate();
  };

  /**
   * Handle retry after error
   */
  const handleRetry = () => {
    mutate();
  };

  /**
   * Handle back navigation
   */
  const handleBack = () => {
    router.push(`/sites/${siteId}/audits/${auditId}`);
  };

  // Loading state
  if (isLoading) {
    return (
      <div className={cn('min-h-screen bg-slate-950 p-4 sm:p-6 lg:p-8', className)}>
        <div className="max-w-3xl mx-auto space-y-6">
          {/* Header skeleton */}
          <div className="flex items-center gap-4">
            <Skeleton className="h-10 w-10 rounded-full bg-slate-800" />
            <div className="space-y-2">
              <Skeleton className="h-6 w-48 bg-slate-800" />
              <Skeleton className="h-4 w-32 bg-slate-800" />
            </div>
          </div>

          {/* Content skeleton */}
          <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6">
            <div className="space-y-4">
              <Skeleton className="h-5 w-32 bg-slate-800" />
              <Skeleton className="h-10 w-full bg-slate-800" />
              <Skeleton className="h-20 w-full bg-slate-800" />
            </div>
          </div>
        </div>

        {/* Loading indicator (for tests) */}
        <div data-testid="loader-icon" className="hidden">
          <Loader2 className="h-4 w-4 animate-spin" />
        </div>
      </div>
    );
  }

  // Error state
  if (error && !isLoading) {
    const isNotFound = error.message === 'Not found';
    const isUnauthorized = error.message === 'Unauthorized';
    const isAccessDenied = error.message === 'Access denied';

    // Redirect to login for unauthorized users
    if (isUnauthorized) {
      React.useEffect(() => {
        router.push('/login');
      }, [router]);
      
      return (
        <div className={cn('min-h-screen bg-slate-950 flex items-center justify-center', className)}>
          <div data-testid="loader-icon">
            <Loader2 className="h-6 w-6 animate-spin text-slate-400" />
          </div>
        </div>
      );
    }

    return (
      <div className={cn('min-h-screen bg-slate-950 flex items-center justify-center p-4', className)}>
        <div className="text-center max-w-md">
          <AlertCircle className="h-12 w-12 text-red-400 mx-auto mb-4" aria-hidden="true" />
          <h1 className="text-xl font-semibold text-white mb-2">
            {isNotFound ? 'Audit Not Found' : isAccessDenied ? 'Access Denied' : 'Error Loading Audit'}
          </h1>
          <p className="text-slate-400 mb-4">
            {isNotFound
              ? 'The audit you are looking for does not exist or has been deleted.'
              : isAccessDenied
              ? 'You do not have permission to view this audit.'
              : error.message}
          </p>
          <div className="flex gap-3 justify-center">
            <Button
              variant="outline"
              onClick={() => router.push(`/sites/${siteId}/audits`)}
              className="border-slate-700 hover:bg-slate-800"
            >
              Back to Audits
            </Button>
            {!isNotFound && !isAccessDenied && (
              <Button onClick={handleRetry} variant="default">
                <Loader2 className="h-4 w-4 mr-2" aria-hidden="true" />
                Retry
              </Button>
            )}
          </div>
        </div>
      </div>
    );
  }

  // Access denied for non-owners
  if (!isOwner) {
    return (
      <div className={cn('min-h-screen bg-slate-950 flex items-center justify-center p-4', className)}>
        <div className="text-center max-w-md">
          <AlertCircle className="h-12 w-12 text-yellow-400 mx-auto mb-4" aria-hidden="true" />
          <h1 className="text-xl font-semibold text-white mb-2">Access Denied</h1>
          <p className="text-slate-400 mb-4">
            You do not have permission to modify this audit's settings.
          </p>
          <Button
            variant="outline"
            onClick={() => router.push(`/sites/${siteId}/audits/${auditId}`)}
            className="border-slate-700 hover:bg-slate-800"
          >
            Back to Audit
          </Button>
        </div>
      </div>
    );
  }

  // No audit data
  if (!audit) {
    return (
      <div className={cn('min-h-screen bg-slate-950 flex items-center justify-center p-4', className)}>
        <div className="text-center max-w-md">
          <AlertCircle className="h-12 w-12 text-slate-400 mx-auto mb-4" aria-hidden="true" />
          <h1 className="text-xl font-semibold text-white mb-2">No Audit Data</h1>
          <p className="text-slate-400 mb-4">
            Unable to load audit data.
          </p>
          <Button
            variant="outline"
            onClick={handleRetry}
            className="border-slate-700 hover:bg-slate-800"
          >
            Retry
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className={cn('min-h-screen bg-slate-950', className)}>
      <main className="max-w-3xl mx-auto px-4 py-6 sm:px-6 lg:px-8" role="main">
        {/* Header */}
        <header className="mb-6">
          <div className="flex items-center gap-4 mb-4">
            <Button
              variant="ghost"
              size="icon"
              onClick={handleBack}
              className="text-slate-400 hover:text-white hover:bg-slate-800"
              aria-label="Back to audit"
            >
              <ArrowLeft className="h-5 w-5" />
            </Button>
            <div>
              <h1 className="text-2xl font-bold text-white flex items-center gap-2">
                <Settings className="h-6 w-6" aria-hidden="true" />
                Audit Settings
              </h1>
              <p className="text-sm text-slate-400 mt-1">
                {audit.url}
              </p>
            </div>
          </div>

          {/* Back link for accessibility */}
          <a
            href={`/sites/${siteId}/audits/${auditId}`}
            onClick={(e) => {
              e.preventDefault();
              handleBack();
            }}
            className="sr-only"
            aria-label="Back to audit detail"
          >
            Back to audit
          </a>
        </header>

        {/* Settings Sections */}
        <div className="space-y-6">
          {/* Visibility Section */}
          <section
            className="rounded-lg border border-slate-800 bg-slate-900/60 p-6"
            data-testid="visibility-settings-wrapper"
            aria-labelledby="visibility-heading"
          >
            <VisibilitySettings
              auditId={audit.id}
              auditHash={audit.hash || audit.publicHash || ''}
              isPublic={audit.isPublic}
              onPublish={handlePublish}
              onUnpublish={handleUnpublish}
            />
          </section>

          {/* Additional settings sections can be added here */}
        </div>
      </main>
    </div>
  );
}

export default AuditSettingsPage;
