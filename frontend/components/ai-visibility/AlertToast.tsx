'use client';

import React, { useEffect, useRef, useState, useCallback } from 'react';
import {
  TrendingUp,
  TrendingDown,
  AlertTriangle,
  Meh,
  X,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Alert, AlertType } from '@/lib/ai-visibility/types';
import { generateAlertMessage } from '@/lib/ai-visibility/types';

interface AlertToastProps {
  alert: Alert;
  onOpenDetail?: (id: number) => void;
  onDismiss: () => void;
  autoDismissMs?: number;
  position?: 'bottom-right' | 'bottom-left' | 'top-right' | 'top-left';
}

const alertTypeConfig: Record<AlertType, {
  icon: React.ReactNode;
  borderClass: string;
}> = {
  mention_spike: {
    icon: <TrendingUp className="h-4 w-4" />,
    borderClass: 'border-emerald-500',
  },
  mention_drop: {
    icon: <TrendingDown className="h-4 w-4" />,
    borderClass: 'border-rose-500',
  },
  competitor_gain: {
    icon: <AlertTriangle className="h-4 w-4" />,
    borderClass: 'border-amber-500',
  },
  sentiment_change: {
    icon: <Meh className="h-4 w-4" />,
    borderClass: 'border-yellow-500',
  },
};

function formatTimeAgo(dateString: string | null): string {
  if (!dateString) return 'Unknown';
  
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();

  if (diffMs < 0) return 'Just now';

  const diffMinutes = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMinutes / 60);

  if (diffHours > 0) {
    return `${diffHours}h ago`;
  }
  if (diffMinutes > 0) {
    return `${diffMinutes}m ago`;
  }
  return 'Just now';
}

export function AlertToast({
  alert,
  onOpenDetail,
  onDismiss,
  autoDismissMs = 10000,
  position = 'bottom-right',
}: AlertToastProps) {
  const [isPaused, setIsPaused] = useState(false);
  const [progress, setProgress] = useState(100);
  const [isDismissed, setIsDismissed] = useState(false);
  const timerRef = useRef<NodeJS.Timeout | null>(null);
  const startTimeRef = useRef<number>(Date.now());
  const remainingRef = useRef<number>(autoDismissMs);

  const config = alertTypeConfig[alert.alert_type];
  
  // Generate message from alert data
  const message = generateAlertMessage(alert);

  const dismiss = useCallback(() => {
    setIsDismissed(true);
    setTimeout(() => {
      onDismiss();
    }, 300);
  }, [onDismiss]);

  // Auto-dismiss timer
  useEffect(() => {
    if (isPaused) return;

    const updateProgress = () => {
      const elapsed = Date.now() - startTimeRef.current;
      const remaining = Math.max(0, remainingRef.current - elapsed);
      setProgress((remaining / autoDismissMs) * 100);

      if (remaining <= 0) {
        dismiss();
      }
    };

    timerRef.current = setInterval(updateProgress, 50);

    return () => {
      if (timerRef.current) {
        clearInterval(timerRef.current);
      }
    };
  }, [isPaused, autoDismissMs, dismiss]);

  // Pause on hover
  const handleMouseEnter = useCallback(() => {
    if (timerRef.current) {
      clearInterval(timerRef.current);
    }
    const elapsed = Date.now() - startTimeRef.current;
    remainingRef.current = Math.max(0, remainingRef.current - elapsed);
    setIsPaused(true);
  }, []);

  // Resume on mouse leave
  const handleMouseLeave = useCallback(() => {
    startTimeRef.current = Date.now();
    setIsPaused(false);
  }, []);

  // Keyboard dismiss
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        dismiss();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [dismiss]);

  const handleClick = useCallback(() => {
    onOpenDetail?.(alert.id);
    dismiss();
  }, [onOpenDetail, alert.id, dismiss]);

  const positionClasses = {
    'bottom-right': 'bottom-4 right-4',
    'bottom-left': 'bottom-4 left-4',
    'top-right': 'top-4 right-4',
    'top-left': 'top-4 left-4',
  };

  if (isDismissed) {
    return null;
  }

  return (
    <div
      role="alert"
      aria-live="assertive"
      data-testid="alert-toast"
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
      onClick={handleClick}
      className={cn(
        'fixed z-50 w-80 max-w-[calc(100vw-2rem)]',
        'bg-white dark:bg-slate-800 rounded-lg shadow-lg border-l-4',
        config.borderClass,
        'cursor-pointer animate-slide-in',
        positionClasses[position]
      )}
    >
      {/* Content */}
      <div className="p-3">
        <div className="flex items-start gap-3">
          {/* Icon */}
          <div className="flex-shrink-0 text-slate-600 dark:text-slate-400">
            {config.icon}
          </div>

          {/* Text */}
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">
              {alert.project_name}
            </p>
            <p className="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 mt-0.5">
              {message}
            </p>
            <p className="text-xs text-slate-400 dark:text-slate-500 mt-1">
              {formatTimeAgo(alert.triggered_at || alert.created_at)}
            </p>
          </div>

          {/* Dismiss Button */}
          <button
            onClick={(e) => {
              e.stopPropagation();
              dismiss();
            }}
            className="flex-shrink-0 p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded"
            aria-label="Dismiss"
          >
            <X className="h-4 w-4 text-slate-400" />
          </button>
        </div>
      </div>

      {/* Progress Bar */}
      <div
        className="h-1 bg-slate-200 dark:bg-slate-700"
        style={{ borderRadius: '0 0 0.5rem 0.5rem' }}
      >
        <div
          data-testid="toast-progress"
          className="h-full bg-blue-500 transition-all duration-100"
          style={{ width: `${progress}%`, borderRadius: '0 0 0 0.5rem' }}
        />
      </div>
    </div>
  );
}

// Toast Container for managing multiple toasts
interface AlertToastContainerProps {
  toasts: Alert[];
  onOpenDetail?: (id: number) => void;
  onDismiss: (id: number) => void;
  maxToasts?: number;
}

export function AlertToastContainer({
  toasts,
  onOpenDetail,
  onDismiss,
  maxToasts = 5,
}: AlertToastContainerProps) {
  const visibleToasts = toasts.slice(0, maxToasts);

  return (
    <div className="fixed z-50 bottom-4 right-4 space-y-2">
      {visibleToasts.map((alert, index) => (
        <div
          key={alert.id}
          style={{ transform: `translateY(${index * 4}px)` }}
        >
          <AlertToast
            alert={alert}
            onOpenDetail={onOpenDetail}
            onDismiss={() => onDismiss(alert.id)}
          />
        </div>
      ))}
    </div>
  );
}
