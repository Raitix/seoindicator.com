'use client';

import React, { useEffect, useRef, useCallback } from 'react';
import {
  X,
  TrendingUp,
  TrendingDown,
  AlertTriangle,
  Meh,
  ExternalLink,
  Check,
  Clock,
  Copy,
  Share2,
  Trash2,
  Loader2,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Alert, AlertType } from '@/lib/ai-visibility/types';
import { generateAlertMessage, generateRecommendations } from '@/lib/ai-visibility/types';

interface AlertDetailModalProps {
  isOpen: boolean;
  alert: Alert | null;
  onClose: () => void;
  onResolve?: (id: number) => void;
  onDismiss?: (id: number) => void;
  onViewProject?: (projectId: number) => void;
  onDelete?: (id: number) => void;
  isLoading?: boolean;
  error?: string | null;
}

const alertTypeConfig: Record<AlertType, {
  icon: React.ReactNode;
  borderClass: string;
  bgClass: string;
  textClass: string;
  label: string;
}> = {
  mention_spike: {
    icon: <TrendingUp className="h-5 w-5" />,
    borderClass: 'border-emerald-200',
    bgClass: 'bg-emerald-50 dark:bg-emerald-950',
    textClass: 'text-emerald-600 dark:text-emerald-400',
    label: 'Mention Spike',
  },
  mention_drop: {
    icon: <TrendingDown className="h-5 w-5" />,
    borderClass: 'border-rose-200',
    bgClass: 'bg-rose-50 dark:bg-rose-950',
    textClass: 'text-rose-600 dark:text-rose-400',
    label: 'Mention Drop',
  },
  competitor_gain: {
    icon: <AlertTriangle className="h-5 w-5" />,
    borderClass: 'border-amber-200',
    bgClass: 'bg-amber-50 dark:bg-amber-950',
    textClass: 'text-amber-600 dark:text-amber-400',
    label: 'Competitor Gain',
  },
  sentiment_change: {
    icon: <Meh className="h-5 w-5" />,
    borderClass: 'border-yellow-200',
    bgClass: 'bg-yellow-50 dark:bg-yellow-950',
    textClass: 'text-yellow-600 dark:text-yellow-400',
    label: 'Sentiment Change',
  },
};

function formatDate(dateString: string | null): string {
  if (!dateString) return 'Unknown';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function AlertDetailModal({
  isOpen,
  alert,
  onClose,
  onResolve,
  onDismiss,
  onViewProject,
  onDelete,
  isLoading = false,
  error = null,
}: AlertDetailModalProps) {
  const modalRef = useRef<HTMLDivElement>(null);
  const closeButtonRef = useRef<HTMLButtonElement>(null);
  const previousFocusRef = useRef<HTMLElement | null>(null);
  const [showShareDialog, setShowShareDialog] = React.useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = React.useState(false);
  const [copied, setCopied] = React.useState(false);

  // Focus management
  useEffect(() => {
    if (isOpen) {
      previousFocusRef.current = document.activeElement as HTMLElement;
      // Focus close button when modal opens
      setTimeout(() => closeButtonRef.current?.focus(), 0);

      // Trap focus within modal
      const handleKeyDown = (e: KeyboardEvent) => {
        if (e.key === 'Escape') {
          onClose();
          return;
        }

        if (e.key === 'Tab' && modalRef.current) {
          const focusableElements = modalRef.current.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
          );
          const firstElement = focusableElements[0] as HTMLElement;
          const lastElement = focusableElements[focusableElements.length - 1] as HTMLElement;

          if (e.shiftKey && document.activeElement === firstElement) {
            e.preventDefault();
            lastElement?.focus();
          } else if (!e.shiftKey && document.activeElement === lastElement) {
            e.preventDefault();
            firstElement?.focus();
          }
        }
      };

      document.addEventListener('keydown', handleKeyDown);
      return () => document.removeEventListener('keydown', handleKeyDown);
    } else {
      // Restore focus when modal closes
      previousFocusRef.current?.focus();
    }
  }, [isOpen, onClose]);

  // Check for mobile
  const isMobile = typeof window !== 'undefined' && window.innerWidth < 640;

  const handleCopyRecommendations = useCallback(() => {
    if (!alert) return;
    const recommendations = generateRecommendations(alert);
    const text = recommendations.map((r, i) => `${i + 1}. ${r}`).join('\n');
    navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }, [alert]);

  const handleResolve = useCallback(() => {
    if (alert && onResolve) {
      onResolve(alert.id);
    }
  }, [alert, onResolve]);

  const handleDismiss = useCallback(() => {
    if (alert && onDismiss) {
      onDismiss(alert.id);
    }
  }, [alert, onDismiss]);

  const handleViewProject = useCallback(() => {
    if (alert && onViewProject) {
      onViewProject(alert.project_id);
    }
  }, [alert, onViewProject]);

  const handleDelete = useCallback(() => {
    if (alert && onDelete) {
      onDelete(alert.id);
      setShowDeleteConfirm(false);
    }
  }, [alert, onDelete]);

  const handleOverlayClick = useCallback(
    (e: React.MouseEvent) => {
      if (e.target === e.currentTarget) {
        onClose();
      }
    },
    [onClose]
  );

  // Swipe handling for mobile
  const touchStartY = React.useRef(0);
  const handleTouchStart = (e: React.TouchEvent) => {
    touchStartY.current = e.touches[0].clientY;
  };
  const handleTouchEnd = (e: React.TouchEvent) => {
    const diff = e.changedTouches[0].clientY - touchStartY.current;
    if (diff > 100) {
      onClose();
    }
  };

  if (!isOpen) return null;

  const config = alert ? alertTypeConfig[alert.alert_type] : null;
  
  // Use change_percent from backend
  const percentageChangeClass =
    alert && alert.change_percent > 0
      ? 'text-emerald-600'
      : alert && alert.change_percent < 0
        ? 'text-rose-600'
        : 'text-slate-500';
  const percentagePrefix = alert && alert.change_percent > 0 ? '+' : '';

  // Generate message and recommendations from alert data
  const message = alert ? generateAlertMessage(alert) : '';
  const recommendations = alert ? generateRecommendations(alert) : [];

  return (
    <div
      data-testid="modal-overlay"
      className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50"
      onClick={handleOverlayClick}
    >
      <div
        ref={modalRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
        data-testid="alert-detail-modal"
        className={cn(
          'w-full max-w-lg bg-white dark:bg-slate-800 shadow-xl animate-fade-in',
          isMobile
            ? 'bottom-0 rounded-t-2xl max-h-[85vh]'
            : 'rounded-2xl max-h-[90vh]',
          'overflow-hidden flex flex-col'
        )}
        onTouchStart={handleTouchStart}
        onTouchEnd={handleTouchEnd}
      >
        {/* Mobile Swipe Handle */}
        {isMobile && (
          <div
            data-testid="swipe-handle"
            className="flex justify-center py-2"
          >
            <div className="w-10 h-1 bg-slate-300 dark:bg-slate-600 rounded-full" />
          </div>
        )}

        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
          {config && (
            <div className={cn('flex items-center gap-2', config.textClass)}>
              {config.icon}
              <span id="modal-title" className="font-medium">
                {config.label}
              </span>
            </div>
          )}
          <button
            ref={closeButtonRef}
            onClick={onClose}
            className="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg"
            aria-label="Close"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-4">
          {isLoading ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-slate-400" />
            </div>
          ) : error ? (
            <div className="text-center py-8">
              <p className="text-red-600 dark:text-red-400 mb-4">{error}</p>
              <button
                onClick={() => window.location.reload()}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg"
              >
                Retry
              </button>
            </div>
          ) : alert ? (
            <>
              {/* Resolved Badge */}
              {alert.is_resolved && (
                <div className="mb-4">
                  <span className="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded text-sm">
                    <Check className="h-4 w-4" />
                    Resolved
                  </span>
                </div>
              )}

              {/* Message */}
              <p className="text-lg text-slate-900 dark:text-slate-100 mb-4">
                {message}
              </p>

              {/* Values */}
              <div className="grid grid-cols-3 gap-4 mb-6">
                <div className="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                  <p className="text-xs text-slate-500 dark:text-slate-400 mb-1">Previous</p>
                  <p className="text-xl font-semibold">{alert.previous_value}</p>
                </div>
                <div className="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                  <p className="text-xs text-slate-500 dark:text-slate-400 mb-1">Current</p>
                  <p className="text-xl font-semibold">{alert.current_value}</p>
                </div>
                <div className="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                  <p className="text-xs text-slate-500 dark:text-slate-400 mb-1">Change</p>
                  <p
                    data-testid="percentage-change"
                    className={cn('text-xl font-semibold', percentageChangeClass)}
                  >
                    {percentagePrefix}{alert.change_percent.toFixed(1)}%
                  </p>
                </div>
              </div>

              {/* Threshold (if applicable) */}
              {alert.threshold && (
                <div className="mb-6 text-sm text-slate-500 dark:text-slate-400">
                  <span className="font-medium">Threshold:</span> {alert.threshold}%
                </div>
              )}

              {/* Project Link */}
              <div className="mb-6">
                <a
                  href={`/ai-visibility/projects/${alert.project_id}`}
                  onClick={(e) => {
                    e.preventDefault();
                    handleViewProject();
                  }}
                  className="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                >
                  <ExternalLink className="h-4 w-4" />
                  View Project: {alert.project_name}
                </a>
              </div>

              {/* Timestamp */}
              <div className="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-6">
                <Clock className="h-4 w-4" />
                Triggered: {formatDate(alert.triggered_at || alert.created_at)}
              </div>

              {/* Recommendations */}
              {recommendations.length > 0 && (
                <div className="mb-6">
                  <div className="flex items-center justify-between mb-2">
                    <h4 className="font-medium text-slate-900 dark:text-slate-100">
                      Recommendations
                    </h4>
                    <button
                      onClick={handleCopyRecommendations}
                      className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                    >
                      <Copy className="h-4 w-4" />
                      {copied ? 'Copied!' : 'Copy'}
                    </button>
                  </div>
                  <ul
                    data-testid="recommendations-list"
                    className="space-y-2 max-h-40 overflow-y-auto"
                  >
                    {recommendations.map((rec, i) => (
                      <li
                        key={i}
                        className="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-400"
                      >
                        <span className="flex-shrink-0 w-5 h-5 flex items-center justify-center bg-slate-100 dark:bg-slate-700 rounded text-xs">
                          {i + 1}
                        </span>
                        {rec}
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </>
          ) : null}
        </div>

        {/* Footer Actions */}
        {!isLoading && !error && alert && (
          <div className="p-4 border-t border-slate-200 dark:border-slate-700 space-y-2">
            <div className="flex flex-wrap gap-2">
              {onResolve && !alert.is_resolved && (
                <button
                  onClick={handleResolve}
                  disabled={alert.is_resolved}
                  className="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg disabled:opacity-50"
                >
                  <Check className="h-4 w-4" />
                  Resolve
                </button>
              )}
              {onDismiss && (
                <button
                  onClick={handleDismiss}
                  className="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg"
                >
                  Dismiss
                </button>
              )}
            </div>
            <div className="flex items-center justify-between">
              <div className="flex gap-2">
                <button
                  onClick={() => setShowShareDialog(true)}
                  className="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg"
                  aria-label="Share"
                >
                  <Share2 className="h-4 w-4" />
                </button>
                {onDelete && (
                  <button
                    onClick={() => setShowDeleteConfirm(true)}
                    className="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg text-red-500"
                    aria-label="Delete"
                  >
                    <Trash2 className="h-4 w-4" />
                  </button>
                )}
              </div>
              <button
                onClick={handleViewProject}
                className="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg"
              >
                <ExternalLink className="h-4 w-4" />
                View Project
              </button>
            </div>
          </div>
        )}

        {/* Share Dialog */}
        {showShareDialog && (
          <div
            data-testid="share-dialog"
            className="absolute inset-0 bg-black/50 flex items-center justify-center"
          >
            <div className="bg-white dark:bg-slate-800 p-4 rounded-lg max-w-sm w-full mx-4">
              <h4 className="font-medium mb-2">Share Alert</h4>
              <p className="text-sm text-slate-500 mb-4">
                Copy the link to share this alert with your team.
              </p>
              <input
                type="text"
                readOnly
                value={typeof window !== 'undefined' ? `${window.location.origin}/ai-visibility/alerts/${alert?.id}` : ''}
                className="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-sm"
              />
              <div className="flex gap-2 mt-4">
                <button
                  onClick={() => setShowShareDialog(false)}
                  className="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg"
                >
                  Cancel
                </button>
                <button
                  onClick={() => {
                    navigator.clipboard.writeText(
                      `${window.location.origin}/ai-visibility/alerts/${alert?.id}`
                    );
                    setShowShareDialog(false);
                  }}
                  className="flex-1 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg"
                >
                  Copy Link
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Delete Confirmation Dialog */}
        {showDeleteConfirm && (
          <div
            role="alertdialog"
            className="absolute inset-0 bg-black/50 flex items-center justify-center"
          >
            <div className="bg-white dark:bg-slate-800 p-4 rounded-lg max-w-sm w-full mx-4">
              <h4 className="font-medium mb-2">Delete Alert</h4>
              <p className="text-sm text-slate-500 mb-4">
                Are you sure you want to delete this alert? This action cannot be undone.
              </p>
              <div className="flex gap-2">
                <button
                  onClick={() => setShowDeleteConfirm(false)}
                  className="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg"
                >
                  Cancel
                </button>
                <button
                  onClick={handleDelete}
                  className="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
