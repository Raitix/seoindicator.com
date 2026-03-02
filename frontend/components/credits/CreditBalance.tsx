"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  Wallet,
  Plus,
  History,
  ChevronDown,
  ChevronUp,
  AlertTriangle,
  RefreshCw,
  LogIn,
} from "lucide-react";
import type { BalanceResponse } from "@/lib/api/credits";
import { LowBalanceWarning } from "./LowBalanceWarning";

// ==================== Types ====================

export interface CreditBalanceProps {
  balance: BalanceResponse | null;
  isLoading: boolean;
  error: Error | null;
  isAuthenticated: boolean;
  onAddCredits: () => void;
  onViewHistory: () => void;
  onRetry?: () => void;
  loginUrl?: string;
  showQuickAddModal?: boolean;
  optimisticBalance?: number;
  mutate?: () => void;
  trackEvent?: (event: string) => void;
  useSWR?: boolean;
  className?: string;
}

// ==================== Skeleton Component ====================

function BalanceSkeleton() {
  return (
    <div
      data-testid="balance-skeleton"
      className="animate-pulse space-y-3 p-4 rounded-lg bg-slate-800/50"
    >
      <div className="h-4 bg-slate-700 rounded w-1/3" />
      <div className="h-8 bg-slate-700 rounded w-1/2" />
      <div className="flex gap-2">
        <div className="h-9 bg-slate-700 rounded w-24" />
        <div className="h-9 bg-slate-700 rounded w-20" />
      </div>
    </div>
  );
}

// ==================== Unauthenticated State ====================

function UnauthenticatedState({ loginUrl = "/login" }: { loginUrl?: string }) {
  return (
    <div
      data-testid="credit-balance"
      className="p-4 rounded-lg bg-slate-800/50 border border-slate-700"
    >
      <div className="flex items-center gap-3 mb-3">
        <div className="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center">
          <Wallet className="w-5 h-5 text-slate-400" />
        </div>
        <div>
          <h3 className="text-sm font-medium text-white">Credit Balance</h3>
          <p className="text-xs text-slate-400">Sign in to view balance</p>
        </div>
      </div>
      <a
        href={loginUrl}
        className="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
      >
        <LogIn className="w-4 h-4" />
        Sign In
      </a>
    </div>
  );
}

// ==================== Error State ====================

interface ErrorStateProps {
  error: Error;
  onRetry?: () => void;
}

function ErrorState({ error, onRetry }: ErrorStateProps) {
  const isNetworkError = error.message.toLowerCase().includes("network");
  const isUnauthorized = error.message.toLowerCase().includes("unauthorized") || 
    (error as any).status === 401;

  return (
    <div
      data-testid="credit-balance"
      className="p-4 rounded-lg bg-red-900/20 border border-red-800"
    >
      <div className="flex items-center gap-3 mb-3">
        <div className="w-10 h-10 rounded-full bg-red-900/50 flex items-center justify-center">
          <AlertTriangle className="w-5 h-5 text-red-400" />
        </div>
        <div>
          <h3 className="text-sm font-medium text-white">Error Loading Balance</h3>
          <p className="text-xs text-slate-400">
            {isNetworkError
              ? "Network error. Please check your connection."
              : isUnauthorized
              ? "Session expired. Please sign in again."
              : "Failed to load balance. Please try again."}
          </p>
        </div>
      </div>
      {onRetry && (
        <Button
          variant="outline"
          size="sm"
          onClick={onRetry}
          className="gap-2"
        >
          <RefreshCw className="w-4 h-4" />
          Retry
        </Button>
      )}
    </div>
  );
}

// ==================== Main Component ====================

export function CreditBalance({
  balance,
  isLoading,
  error,
  isAuthenticated,
  onAddCredits,
  onViewHistory,
  onRetry,
  loginUrl = "/login",
  showQuickAddModal = false,
  optimisticBalance,
  mutate,
  trackEvent,
  useSWR = true,
  className,
}: CreditBalanceProps) {
  const [isExpanded, setIsExpanded] = React.useState(false);
  const [dismissedWarning, setDismissedWarning] = React.useState(false);
  const [showBalanceAnimation, setShowBalanceAnimation] = React.useState(false);
  const prevBalanceRef = React.useRef<number | null>(null);

  // Track balance changes for animation
  React.useEffect(() => {
    const currentBalance = optimisticBalance ?? balance?.balance ?? null;
    if (prevBalanceRef.current !== null && currentBalance !== null && prevBalanceRef.current !== currentBalance) {
      setShowBalanceAnimation(true);
      const timer = setTimeout(() => setShowBalanceAnimation(false), 500);
      return () => clearTimeout(timer);
    }
    prevBalanceRef.current = currentBalance;
  }, [optimisticBalance, balance?.balance]);

  // Handle add credits click
  const handleAddCredits = React.useCallback(() => {
    trackEvent?.("add_credits_clicked");
    onAddCredits();
  }, [onAddCredits, trackEvent]);

  // Handle history click
  const handleViewHistory = React.useCallback(() => {
    onViewHistory();
  }, [onViewHistory]);

  // Handle warning dismiss
  const handleDismissWarning = React.useCallback(() => {
    setDismissedWarning(true);
  }, []);

  // Loading state
  if (isLoading) {
    return (
      <div data-testid="credit-balance" className={className}>
        <BalanceSkeleton />
      </div>
    );
  }

  // Error state
  if (error && !balance) {
    return (
      <div className={className}>
        <ErrorState error={error} onRetry={onRetry} />
      </div>
    );
  }

  // Unauthenticated state
  if (!isAuthenticated) {
    return (
      <div className={className}>
        <UnauthenticatedState loginUrl={loginUrl} />
      </div>
    );
  }

  // Determine displayed balance
  const displayedBalance = optimisticBalance ?? balance?.balance ?? 0;
  const formattedBalance = optimisticBalance !== undefined && balance
    ? `$${optimisticBalance.toFixed(2)}`
    : balance?.formatted_balance ?? "$0.00";

  // Check for low balance
  const hasLowBalance = balance?.has_low_balance ?? displayedBalance < (balance?.low_balance_threshold ?? 2.0);
  const showLowBalanceWarning = hasLowBalance && !dismissedWarning;

  return (
    <div data-testid="credit-balance" className={className}>
      <div className="p-4 rounded-lg bg-slate-800/50 border border-slate-700">
        {/* Header */}
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-blue-600/20 flex items-center justify-center">
              <Wallet className="w-5 h-5 text-blue-400" />
            </div>
            <div>
              <h3 className="text-sm font-medium text-white">Credit Balance</h3>
              <p className="text-xs text-slate-400">Available credits</p>
            </div>
          </div>
        </div>

        {/* Balance Display */}
        <div className="mb-4">
          <div
            className={cn(
              "text-3xl font-bold text-white transition-all duration-300",
              showBalanceAnimation && "scale-105 text-green-400"
            )}
            aria-label={`Current balance: ${formattedBalance}`}
            aria-live="polite"
          >
            {formattedBalance}
          </div>
          {balance?.currency && balance.currency !== "USD" && (
            <p className="text-xs text-slate-400 mt-1">
              Currency: {balance.currency}
            </p>
          )}
        </div>

        {/* Low Balance Warning */}
        {showLowBalanceWarning && (
          <div className="mb-4">
            <LowBalanceWarning
              balance={displayedBalance}
              threshold={balance?.low_balance_threshold ?? 2.0}
              onAddCredits={handleAddCredits}
              onDismiss={handleDismissWarning}
            />
          </div>
        )}

        {/* Actions */}
        <div className="flex flex-wrap gap-2 mb-3">
          <Button
            onClick={handleAddCredits}
            className="gap-2"
            aria-label="Add credits to your account"
          >
            <Plus className="w-4 h-4" />
            Add Credits
          </Button>
          <Button
            variant="outline"
            onClick={handleViewHistory}
            className="gap-2"
            aria-label="View transaction history"
          >
            <History className="w-4 h-4" />
            History
          </Button>
        </div>

        {/* Expandable Details */}
        <button
          onClick={() => setIsExpanded(!isExpanded)}
          className="flex items-center gap-1 text-xs text-slate-400 hover:text-white transition-colors"
          aria-expanded={isExpanded}
          aria-controls="balance-details"
        >
          {isExpanded ? (
            <>
              <ChevronUp className="w-4 h-4" />
              Hide details
            </>
          ) : (
            <>
              <ChevronDown className="w-4 h-4" />
              Show details
            </>
          )}
        </button>

        {/* Details Panel */}
        {isExpanded && (
          <div
            id="balance-details"
            data-testid="balance-details"
            className="mt-3 pt-3 border-t border-slate-700 space-y-2"
          >
            <div className="flex justify-between text-sm">
              <span className="text-slate-400">Total Purchased</span>
              <span className="text-white font-medium">
                {balance?.total_purchased !== undefined
                  ? `$${balance.total_purchased.toFixed(2)}`
                  : "$0.00"}
              </span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-slate-400">Total Spent</span>
              <span className="text-white font-medium">
                {balance?.total_spent !== undefined
                  ? `$${balance.total_spent.toFixed(2)}`
                  : "$0.00"}
              </span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-slate-400">Low Balance Threshold</span>
              <span className="text-white font-medium">
                {balance?.low_balance_threshold !== undefined
                  ? `$${balance.low_balance_threshold.toFixed(2)}`
                  : "$2.00"}
              </span>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default CreditBalance;
