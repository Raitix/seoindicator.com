/**
 * TASK-34-21: AI Visibility Alert Detail Page (Client Component)
 * 
 * Client-side logic for the AI Visibility alert detail page.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-21.md
 */

'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { ChevronRight, ArrowLeft, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import { AlertDetailModal } from '@/components/ai-visibility/AlertDetailModal';
import { Button } from '@/components/ui/button';
import {
  useAlert,
  useMarkAlertRead,
  useResolveAlert,
  useDismissAlert,
  useDeleteAlert,
} from '@/hooks/useAlerts';

interface AlertDetailPageClientProps {
  alertId: string;
}

export function AlertDetailPageClient({ alertId }: AlertDetailPageClientProps) {
  const router = useRouter();
  
  const { alert, isLoading, isError, error, refetch } = useAlert(alertId);
  const { mutate: markRead } = useMarkAlertRead();
  const { mutate: resolve } = useResolveAlert();
  const { mutate: dismiss } = useDismissAlert();
  const { mutate: deleteAlert } = useDeleteAlert();

  const handleResolve = async (id: number) => {
    try {
      await resolve(String(id));
      // Navigate back to alerts list
      router.push('/ai-visibility/alerts');
    } catch (err) {
      console.error('Failed to resolve alert:', err);
    }
  };

  const handleDismiss = async (id: number) => {
    try {
      await dismiss(String(id));
      router.push('/ai-visibility/alerts');
    } catch (err) {
      console.error('Failed to dismiss alert:', err);
    }
  };

  const handleDelete = async (id: number) => {
    try {
      await deleteAlert(String(id));
      router.push('/ai-visibility/alerts');
    } catch (err) {
      console.error('Failed to delete alert:', err);
    }
  };

  const handleViewProject = (projectId: number) => {
    router.push(`/ai-visibility/${projectId}`);
  };

  const handleBack = () => {
    router.back();
  };

  return (
    <main className="min-h-screen bg-slate-50 dark:bg-slate-900" role="main">
      {/* Breadcrumbs */}
      <nav className="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-6 py-3">
        <ol className="flex items-center gap-2 text-sm">
          <li>
            <a href="/" className="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
              Home
            </a>
          </li>
          <li>
            <ChevronRight className="h-4 w-4 text-slate-400" />
          </li>
          <li>
            <a href="/ai-visibility" className="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
              AI Visibility
            </a>
          </li>
          <li>
            <ChevronRight className="h-4 w-4 text-slate-400" />
          </li>
          <li>
            <a href="/ai-visibility/alerts" className="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
              Alerts
            </a>
          </li>
          <li>
            <ChevronRight className="h-4 w-4 text-slate-400" />
          </li>
          <li>
            <span className="text-slate-900 dark:text-slate-100 font-medium">#{alertId}</span>
          </li>
        </ol>
      </nav>

      {/* Page Content */}
      <div className="container mx-auto p-6">
        {/* Header */}
        <div className="mb-6">
          <Button
            onClick={handleBack}
            variant="ghost"
            size="sm"
            className="inline-flex items-center gap-2 mb-4"
          >
            <ArrowLeft className="h-4 w-4" />
            Back to Alerts
          </Button>
        </div>

        {/* Loading State */}
        {isLoading && (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="h-8 w-8 animate-spin text-slate-400" />
          </div>
        )}

        {/* Error State */}
        {isError && !isLoading && (
          <div className="text-center py-12">
            <p className="text-red-600 dark:text-red-400 mb-4">{error || 'Failed to load alert'}</p>
            <Button onClick={() => refetch()} variant="outline">
              Try Again
            </Button>
          </div>
        )}

        {/* Alert Not Found */}
        {!isLoading && !isError && !alert && (
          <div className="text-center py-12">
            <p className="text-slate-600 dark:text-slate-400 mb-4">Alert not found</p>
            <Button onClick={() => router.push('/ai-visibility/alerts')}>
              Back to Alerts
            </Button>
          </div>
        )}
      </div>

      {/* Alert Detail Modal (always open when alert exists) */}
      {alert && (
        <AlertDetailModal
          isOpen={true}
          alert={alert}
          onClose={() => router.push('/ai-visibility/alerts')}
          onResolve={handleResolve}
          onDismiss={handleDismiss}
          onViewProject={handleViewProject}
          onDelete={handleDelete}
          isLoading={isLoading}
          error={error}
        />
      )}
    </main>
  );
}
