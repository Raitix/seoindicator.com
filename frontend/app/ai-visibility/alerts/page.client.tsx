/**
 * TASK-34-21: AI Visibility Alerts Page (Client Component)
 * 
 * Client-side logic for the AI Visibility alerts page.
 * Updated to align with backend API contract.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-21.md
 */

'use client';

import * as React from 'react';
import { useRouter, useSearchParams, usePathname } from 'next/navigation';
import { Home, Bell, RefreshCw, Settings, AlertTriangle, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { AlertList } from '@/components/ai-visibility/AlertList';
import { AlertDetailModal } from '@/components/ai-visibility/AlertDetailModal';
import { AlertPreferencesForm } from '@/components/ai-visibility/AlertPreferencesForm';
import { AlertToast } from '@/components/ai-visibility/AlertToast';
import { Button } from '@/components/ui/button';
import {
  useAlerts,
  useAlertPreferences,
  useMarkAlertRead,
  useResolveAlert,
  useMarkAllRead,
  useDismissAlert,
  useDeleteAlert,
  useUpdatePreferences,
  useTestWebhook,
} from '@/hooks/useAlerts';
import type { Alert, AlertPreferences, AlertFilters, AlertType } from '@/lib/ai-visibility/types';

// ============================================
// Client Component
// ============================================

export function AlertsPageClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const pathname = usePathname();

  const [selectedAlertId, setSelectedAlertId] = React.useState<number | null>(null);
  const [page, setPage] = React.useState(1);
  const [filters, setFilters] = React.useState<AlertFilters>({});
  const [isMobile, setIsMobile] = React.useState(false);
  const [showMobileSettings, setShowMobileSettings] = React.useState(false);
  const [toastAlerts, setToastAlerts] = React.useState<Alert[]>([]);
  const [isOffline, setIsOffline] = React.useState(false);

  // Check for mobile viewport
  React.useEffect(() => {
    const checkMobile = () => setIsMobile(window.innerWidth < 640);
    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  // Check for offline status
  React.useEffect(() => {
    const handleOnline = () => setIsOffline(false);
    const handleOffline = () => setIsOffline(true);

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  // Initialize filters from URL params
  React.useEffect(() => {
    const projectId = searchParams.get('project_id');
    const alertTypes = searchParams.get('types');
    const isRead = searchParams.get('read');

    setFilters({
      project_id: projectId || null,
      alert_types: alertTypes ? (alertTypes.split(',') as AlertType[]) : undefined,
      is_read: isRead === 'false' ? false : isRead === 'true' ? true : null,
    });
  }, [searchParams]);

  // Hooks
  const { data: alerts, meta, isLoading, error, refetch } = useAlerts(page, 15, filters);
  const { preferences, isLoading: preferencesLoading } = useAlertPreferences();
  const { mutate: markRead } = useMarkAlertRead();
  const { mutate: resolve } = useResolveAlert();
  const { mutate: markAllRead } = useMarkAllRead();
  const { mutate: dismiss } = useDismissAlert();
  const { mutate: deleteAlert } = useDeleteAlert();
  const { mutate: updatePreferences } = useUpdatePreferences();
  const { test: testWebhook } = useTestWebhook();

  // Polling every 5 minutes
  React.useEffect(() => {
    const interval = setInterval(() => {
      refetch();
    }, 5 * 60 * 1000);

    return () => clearInterval(interval);
  }, [refetch]);

  // Track new alerts for toast notifications
  const prevAlertsRef = React.useRef<Alert[]>([]);
  React.useEffect(() => {
    if (alerts.length > prevAlertsRef.current.length) {
      const newAlerts = alerts.filter(
        (a) => !prevAlertsRef.current.some((pa) => pa.id === a.id)
      );
      if (newAlerts.length > 0) {
        setToastAlerts((prev) => [...newAlerts, ...prev].slice(0, 5));
      }
    }
    prevAlertsRef.current = alerts;
  }, [alerts]);

  // Handlers
  const handleAlertClick = (id: number) => {
    setSelectedAlertId(id);
  };

  const handleMarkRead = async (id: number) => {
    try {
      await markRead(String(id));
    } catch (err) {
      console.error('Failed to mark alert as read:', err);
    }
  };

  const handleResolve = async (id: number) => {
    try {
      await resolve(String(id));
      setSelectedAlertId(null);
    } catch (err) {
      console.error('Failed to resolve alert:', err);
    }
  };

  const handleDismiss = async (id: number) => {
    try {
      await dismiss(String(id));
      setSelectedAlertId(null);
    } catch (err) {
      console.error('Failed to dismiss alert:', err);
    }
  };

  const handleDelete = async (id: number) => {
    try {
      await deleteAlert(String(id));
      setSelectedAlertId(null);
    } catch (err) {
      console.error('Failed to delete alert:', err);
    }
  };

  const handleMarkAllRead = async () => {
    try {
      await markAllRead();
    } catch (err) {
      console.error('Failed to mark all as read:', err);
    }
  };

  const handleFilterChange = (newFilters: AlertFilters) => {
    const params = new URLSearchParams();
    
    if (newFilters.project_id) {
      params.set('project_id', String(newFilters.project_id));
    }
    if (newFilters.alert_types?.length) {
      params.set('types', newFilters.alert_types.join(','));
    }
    if (newFilters.is_read !== null && newFilters.is_read !== undefined) {
      params.set('read', String(newFilters.is_read));
    }

    router.replace(`${pathname}?${params.toString()}`);
    setFilters(newFilters);
    setPage(1);
  };

  const handlePageChange = (newPage: number) => {
    setPage(newPage);
  };

  const handleViewProject = (projectId: number) => {
    router.push(`/ai-visibility/${projectId}`);
    setSelectedAlertId(null);
  };

  const handleSavePreferences = async (prefs: Partial<AlertPreferences>) => {
    await updatePreferences(prefs);
  };

  const handleTestWebhook = async (url: string) => {
    return testWebhook(url);
  };

  const handleDismissToast = (id: number) => {
    setToastAlerts((prev) => prev.filter((a) => a.id !== id));
  };

  const selectedAlert = alerts.find((a) => a.id === selectedAlertId) || null;

  // Extract unique projects from alerts for filter dropdown
  const projects = React.useMemo(() => {
    const uniqueProjects = new Map<number, string>();
    alerts.forEach((a) => {
      uniqueProjects.set(a.project_id, a.project_name);
    });
    return Array.from(uniqueProjects.entries()).map(([id, name]) => ({ id, name }));
  }, [alerts]);

  // Keyboard shortcuts
  React.useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) {
        return;
      }

      if (e.key === 'r' && !e.metaKey && !e.ctrlKey) {
        handleMarkAllRead();
      }

      if (e.key === '?' && !e.metaKey && !e.ctrlKey) {
        // Show shortcuts help - could open a modal
        console.log('Keyboard shortcuts help');
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, []);

  return (
    <main className="min-h-screen bg-slate-50 dark:bg-slate-900" role="main">
      {/* Skip Link */}
      <a
        href="#main-content"
        className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-500 text-white px-4 py-2 rounded-lg z-50"
      >
        Skip to main content
      </a>

      {/* Offline Indicator */}
      {isOffline && (
        <div className="bg-amber-500 text-white text-center py-2 text-sm">
          You are offline. Some features may be unavailable.
        </div>
      )}

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
            <span className="text-slate-900 dark:text-slate-100 font-medium">Alerts</span>
          </li>
        </ol>
      </nav>

      {/* Page Content */}
      <div
        id="main-content"
        data-testid="alert-page"
        className={cn(
          'container mx-auto p-6',
          'dark:bg-slate-900'
        )}
      >
        {/* Page Header */}
        <header className="mb-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <Bell className="h-6 w-6" />
                AI Visibility Alerts
              </h1>
              <p className="text-slate-500 dark:text-slate-400 mt-1">
                Stay informed about important changes in your AI Visibility tracking
              </p>
            </div>
            <div className="flex items-center gap-3">
              {meta && (
                <div className="text-sm text-slate-500 dark:text-slate-400">
                  <span className="font-medium">{meta.total}</span> total
                  {meta.unread_count > 0 && (
                    <>
                      , <span className="font-medium text-blue-500">{meta.unread_count}</span> unread
                    </>
                  )}
                </div>
              )}
              <Button
                onClick={() => refetch()}
                variant="outline"
                size="sm"
                className="inline-flex items-center gap-2"
              >
                <RefreshCw className="h-4 w-4" />
                Refresh
              </Button>
            </div>
          </div>
        </header>

        {/* Main Layout */}
        <div className="flex gap-6">
          {/* Alert List */}
          <div
            data-testid="main-content"
            className={cn(
              'flex-1',
              isMobile && 'w-full'
            )}
          >
            <div className="bg-white dark:bg-slate-800 rounded-lg shadow-sm">
              <AlertList
                alerts={alerts}
                projects={projects}
                onAlertClick={handleAlertClick}
                onMarkRead={handleMarkRead}
                onResolve={handleResolve}
                onMarkAllRead={handleMarkAllRead}
                onFilterChange={handleFilterChange}
                onPageChange={handlePageChange}
                onRetry={() => refetch()}
                filters={filters}
                isLoading={isLoading}
                error={error}
                unreadCount={meta?.unread_count}
                pagination={
                  meta
                    ? {
                        currentPage: page,
                        perPage: meta.per_page,
                        total: meta.total,
                        lastPage: meta.last_page,
                      }
                    : undefined
                }
              />
            </div>
          </div>

          {/* Sidebar with Preferences */}
          {!isMobile && (
            <aside
              role="complementary"
              data-testid="sidebar"
              className="w-80 flex-shrink-0"
            >
              <div className="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 sticky top-6">
                <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4 flex items-center gap-2">
                  <Settings className="h-5 w-5" />
                  Alert Preferences
                </h2>
                <AlertPreferencesForm
                  preferences={preferences || undefined}
                  onSave={handleSavePreferences}
                  onTestWebhook={handleTestWebhook}
                  isLoading={preferencesLoading}
                />
              </div>
            </aside>
          )}

          {/* Mobile Settings Button */}
          {isMobile && (
            <button
              onClick={() => setShowMobileSettings(true)}
              className="fixed bottom-4 right-4 p-3 bg-blue-500 text-white rounded-full shadow-lg"
              aria-label="Settings"
            >
              <Settings className="h-5 w-5" />
            </button>
          )}

          {/* Mobile Settings Drawer */}
          {isMobile && showMobileSettings && (
            <>
              <div
                className="fixed inset-0 bg-black/50 z-40"
                onClick={() => setShowMobileSettings(false)}
              />
              <div className="fixed inset-y-0 right-0 w-full max-w-sm bg-white dark:bg-slate-800 z-50 overflow-y-auto">
                <div className="p-4">
                  <div className="flex items-center justify-between mb-4">
                    <h2 className="text-lg font-semibold">Settings</h2>
                    <button
                      onClick={() => setShowMobileSettings(false)}
                      className="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg"
                    >
                      ×
                    </button>
                  </div>
                  <AlertPreferencesForm
                    preferences={preferences || undefined}
                    onSave={handleSavePreferences}
                    onTestWebhook={handleTestWebhook}
                    onCancel={() => setShowMobileSettings(false)}
                    isLoading={preferencesLoading}
                  />
                </div>
              </div>
            </>
          )}
        </div>
      </div>

      {/* Alert Detail Modal */}
      <AlertDetailModal
        isOpen={!!selectedAlertId}
        alert={selectedAlert}
        onClose={() => setSelectedAlertId(null)}
        onResolve={handleResolve}
        onDismiss={handleDismiss}
        onViewProject={handleViewProject}
        onDelete={handleDelete}
      />

      {/* Toast Notifications */}
      {toastAlerts.map((alert) => (
        <AlertToast
          key={alert.id}
          alert={alert}
          onOpenDetail={handleAlertClick}
          onDismiss={() => handleDismissToast(alert.id)}
        />
      ))}

      {/* OG Tags for SEO - handled by metadata export */}
    </main>
  );
}
