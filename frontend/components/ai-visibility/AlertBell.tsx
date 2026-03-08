'use client';

import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Bell, X, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { AlertNotification } from './AlertNotification';
import type { Alert } from '@/lib/ai-visibility/types';

interface AlertBellProps {
  alerts?: Alert[];
  unreadCount?: number;
  onViewAll?: () => void;
  onAlertClick?: (id: string) => void;
  onMarkRead?: (id: string) => void;
  onRefetch?: () => void;
  isLoading?: boolean;
  pollingInterval?: number;
  soundEnabled?: boolean;
  desktopNotificationsEnabled?: boolean;
}

export function AlertBell({
  alerts = [],
  unreadCount = 0,
  onViewAll,
  onAlertClick,
  onMarkRead,
  onRefetch,
  isLoading = false,
  pollingInterval,
  soundEnabled = false,
  desktopNotificationsEnabled = false,
}: AlertBellProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [prevAlertsLength, setPrevAlertsLength] = useState(0);
  const [badgeAnimating, setBadgeAnimating] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const buttonRef = useRef<HTMLButtonElement>(null);

  // Polling
  useEffect(() => {
    if (pollingInterval && onRefetch) {
      const interval = setInterval(onRefetch, pollingInterval);
      return () => clearInterval(interval);
    }
  }, [pollingInterval, onRefetch]);

  // Badge animation on count change
  useEffect(() => {
    if (unreadCount !== prevAlertsLength) {
      setBadgeAnimating(true);
      const timer = setTimeout(() => setBadgeAnimating(false), 500);
      setPrevAlertsLength(unreadCount);
      return () => clearTimeout(timer);
    }
  }, [unreadCount, prevAlertsLength]);

  // New alert notification
  useEffect(() => {
    if (alerts.length > prevAlertsLength && alerts.length > 0) {
      const newAlert = alerts[0];
      
      // Sound notification
      if (soundEnabled) {
        try {
          const audio = new Audio('/sounds/notification.mp3');
          audio.play().catch(() => {
            // Ignore autoplay errors
          });
        } catch {
          // Ignore audio errors
        }
      }

      // Desktop notification
      if (desktopNotificationsEnabled && typeof Notification !== 'undefined' && Notification.permission === 'granted') {
        try {
          new Notification('New Alert', {
            body: newAlert.message,
            icon: '/favicon.ico',
          });
        } catch {
          // Ignore notification errors
        }
      }
    }
  }, [alerts.length, prevAlertsLength, soundEnabled, desktopNotificationsEnabled]);

  // Click outside to close
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(e.target as Node) &&
        buttonRef.current &&
        !buttonRef.current.contains(e.target as Node)
      ) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isOpen]);

  // Escape to close
  useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        setIsOpen(false);
        buttonRef.current?.focus();
      }
    };

    if (isOpen) {
      document.addEventListener('keydown', handleEscape);
    }

    return () => {
      document.removeEventListener('keydown', handleEscape);
    };
  }, [isOpen]);

  const handleToggle = useCallback(() => {
    setIsOpen((prev) => !prev);
  }, []);

  const handleAlertClick = useCallback(
    (id: string) => {
      onAlertClick?.(id);
      setIsOpen(false);
    },
    [onAlertClick]
  );

  const handleViewAll = useCallback(() => {
    onViewAll?.();
    setIsOpen(false);
  }, [onViewAll]);

  // Get recent alerts (last 5)
  const recentAlerts = alerts.slice(0, 5);

  // Check for mobile
  const isMobile = typeof window !== 'undefined' && window.innerWidth < 640;

  // Format aria-label
  const ariaLabel =
    unreadCount > 0 ? `${unreadCount} unread alerts` : 'No unread alerts';

  // Format badge count
  const displayCount = unreadCount > 99 ? '99+' : unreadCount;

  // Check if button is near bottom of screen
  const [dropdownPosition, setDropdownPosition] = useState<'bottom' | 'top'>('bottom');
  useEffect(() => {
    if (isOpen && buttonRef.current) {
      const rect = buttonRef.current.getBoundingClientRect();
      const spaceBelow = window.innerHeight - rect.bottom;
      setDropdownPosition(spaceBelow < 400 ? 'top' : 'bottom');
    }
  }, [isOpen]);

  return (
    <div className="relative">
      {/* Bell Button */}
      <button
        ref={buttonRef}
        onClick={handleToggle}
        className={cn(
          'relative p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors',
          'focus:outline-none focus:ring-2 focus:ring-blue-500'
        )}
        aria-label={ariaLabel}
        aria-expanded={isOpen}
        aria-haspopup="true"
      >
        <Bell className="h-5 w-5 text-slate-600 dark:text-slate-400" data-testid="bell-icon" />

        {/* Unread Badge */}
        {unreadCount > 0 && (
          <span
            data-testid="unread-badge"
            className={cn(
              'absolute -top-1 -right-1 min-w-[18px] h-[18px] flex items-center justify-center',
              'bg-blue-500 text-white text-xs font-medium rounded-full px-1',
              badgeAnimating && 'animate-bounce'
            )}
          >
            {displayCount}
          </span>
        )}
      </button>

      {/* Dropdown */}
      {isOpen && (
        <div
          ref={dropdownRef}
          data-testid="alert-dropdown"
          className={cn(
            'absolute z-50 w-80 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700',
            'animate-fade-in',
            dropdownPosition === 'bottom' ? 'top-full mt-2' : 'bottom-full mb-2',
            isMobile && 'left-0 right-0 w-full'
          )}
          role="menu"
        >
          {/* Header */}
          <div className="flex items-center justify-between p-3 border-b border-slate-200 dark:border-slate-700">
            <h3 className="font-medium text-slate-900 dark:text-slate-100">Recent Alerts</h3>
            <button
              onClick={() => setIsOpen(false)}
              className="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded"
            >
              <X className="h-4 w-4" />
            </button>
          </div>

          {/* Content */}
          <div className="max-h-[400px] overflow-y-auto">
            {isLoading ? (
              <div className="p-4 space-y-3">
                {Array.from({ length: 3 }).map((_, i) => (
                  <div
                    key={i}
                    data-testid={`skeleton-${i}`}
                    className="animate-pulse h-16 bg-slate-100 dark:bg-slate-700 rounded"
                  />
                ))}
              </div>
            ) : recentAlerts.length === 0 ? (
              <div className="p-8 text-center text-slate-500 dark:text-slate-400">
                <Bell className="h-8 w-8 mx-auto mb-2 opacity-50" />
                <p>No new alerts</p>
              </div>
            ) : (
              <div className="divide-y divide-slate-100 dark:divide-slate-700">
                {recentAlerts.map((alert) => (
                  <div
                    key={alert.id}
                    data-testid={`dropdown-alert-${alert.id}`}
                    className="p-2"
                  >
                    <AlertNotification
                      alert={alert}
                      onClick={handleAlertClick}
                      onMarkRead={onMarkRead}
                      variant="compact"
                    />
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="p-3 border-t border-slate-200 dark:border-slate-700">
            <button
              onClick={handleViewAll}
              className="w-full inline-flex items-center justify-center gap-2 px-4 py-2 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg"
              role="link"
            >
              View All
              <ChevronRight className="h-4 w-4" />
            </button>
          </div>
        </div>
      )}

      {/* Toast for new alerts */}
      {isOpen && alerts.length > prevAlertsLength && (
        <div
          role="alert"
          className="absolute top-full mt-2 right-0 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg animate-slide-in"
        >
          New alert received!
        </div>
      )}
    </div>
  );
}
