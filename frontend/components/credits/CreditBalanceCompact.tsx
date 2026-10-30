"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import {
  Wallet,
  Plus,
  RefreshCw,
  LogIn,
  AlertCircle,
} from "lucide-react";
import type { BalanceResponse } from "@/lib/api/credits";

// ==================== Types ====================

export interface CreditBalanceCompactProps {
  balance: BalanceResponse | null;
  isLoading: boolean;
  error: Error | null;
  isAuthenticated: boolean;
  onAddCredits: () => void;
  onRetry?: () => void;
  loginUrl?: string;
  showQuickAddModal?: boolean;
  showTooltip?: boolean;
  expandable?: boolean;
  placement?: "navbar" | "sidebar";
}

// ==================== Mini Skeleton ====================

function MiniSkeleton() {
  return (
    <div
      data-testid="balance-mini-skeleton"
      className="flex items-center gap-2 animate-pulse"
    >
      <div className="w-8 h-8 bg-slate-700 rounded-full" />
      <div className="h-4 bg-slate-700 rounded w-16" />
    </div>
  );
}

// ==================== Low Balance Indicator ====================

interface LowBalanceIndicatorProps {
  onClick?: () => void;
}

function LowBalanceIndicator({ onClick }: LowBalanceIndicatorProps) {
  return (
    <button
      data-testid="low-balance-indicator"
      onClick={onClick}
      className={cn(
        "absolute -top-0.5 -right-0.5 w-3 h-3 rounded-full",
        "bg-amber-500 animate-pulse",
        "cursor-pointer hover:bg-amber-400 transition-colors"
      )}
      aria-label="Low balance warning. Click to add credits."
      title="Low balance - add credits"
    />
  );
}

// ==================== Tooltip Component ====================

interface TooltipProps {
  balance: BalanceResponse;
  children: React.ReactNode;
}

function Tooltip({ balance, children }: TooltipProps) {
  return (
    <div className="relative group">
      {children}
      <div
        role="tooltip"
        className={cn(
          "absolute top-full mt-2 right-0 z-50",
          "w-48 p-3 rounded-lg",
          "bg-slate-800 border border-slate-700",
          "opacity-0 invisible group-hover:opacity-100 group-hover:visible",
          "transition-all duration-200",
          "shadow-lg"
        )}
      >
        <div className="text-xs text-slate-400 space-y-1">
          <div className="flex justify-between">
            <span>Purchased</span>
            <span className="text-white">${balance.total_purchased.toFixed(2)}</span>
          </div>
          <div className="flex justify-between">
            <span>Spent</span>
            <span className="text-white">${balance.total_spent.toFixed(2)}</span>
          </div>
        </div>
      </div>
    </div>
  );
}

// ==================== Main Component ====================

export function CreditBalanceCompact({
  balance,
  isLoading,
  error,
  isAuthenticated,
  onAddCredits,
  onRetry,
  loginUrl = "/login",
  showQuickAddModal = false,
  showTooltip = false,
  expandable = false,
  placement = "navbar",
}: CreditBalanceCompactProps) {
  const [isExpanded, setIsExpanded] = React.useState(false);
  const [showTooltipState, setShowTooltipState] = React.useState(false);

  // Loading state
  if (isLoading) {
    return (
      <div data-testid="credit-balance-compact" className="flex items-center">
        <MiniSkeleton />
      </div>
    );
  }

  // Error state
  if (error && !balance) {
    return (
      <div data-testid="credit-balance-compact" className="flex items-center">
        {onRetry && (
          <button
            onClick={onRetry}
            className="flex items-center gap-1 px-2 py-1 text-xs text-slate-400 hover:text-white transition-colors"
            aria-label="Retry loading balance"
          >
            <RefreshCw className="w-4 h-4" />
            <span className="hidden sm:inline">Retry</span>
          </button>
        )}
      </div>
    );
  }

  // Unauthenticated state
  if (!isAuthenticated) {
    return (
      <div data-testid="credit-balance-compact" className="flex items-center">
        <a
          href={loginUrl}
          className="flex items-center gap-2 px-3 py-1.5 text-sm text-slate-400 hover:text-white transition-colors"
        >
          <LogIn className="w-4 h-4" />
          <span className="hidden sm:inline">Sign In</span>
        </a>
      </div>
    );
  }

  // Balance display
  const hasLowBalance = balance?.has_low_balance ?? false;
  const formattedBalance = balance?.formatted_balance ?? "$0.00";

  const content = (
    <div
      className={cn(
        "flex items-center gap-2",
        "text-sm",
        placement === "navbar" && "text-slate-300",
        placement === "sidebar" && "text-slate-300"
      )}
    >
      {/* Balance Icon */}
      <div className="relative">
        <div
          className={cn(
            "flex items-center justify-center",
            "w-8 h-8 rounded-full",
            hasLowBalance
              ? "bg-amber-600/20 text-amber-400"
              : "bg-slate-700 text-slate-300"
          )}
        >
          <Wallet className="w-4 h-4" />
        </div>
        {hasLowBalance && (
          <LowBalanceIndicator onClick={onAddCredits} />
        )}
      </div>

      {/* Balance Text */}
      <span
        className={cn(
          "font-medium",
          "hidden sm:inline",
          hasLowBalance && "text-amber-400"
        )}
        aria-label={`Credit balance: ${formattedBalance}`}
      >
        {formattedBalance}
      </span>

      {/* Add Button */}
      <button
        onClick={onAddCredits}
        className={cn(
          "flex items-center justify-center",
          "w-6 h-6 rounded-md",
          "bg-blue-600 hover:bg-blue-700",
          "text-white transition-colors"
        )}
        aria-label="Add credits"
      >
        <Plus className="w-4 h-4" />
      </button>
    </div>
  );

  // Wrap with tooltip if enabled
  if (showTooltip && balance) {
    return (
      <div
        data-testid="credit-balance-compact"
        className="relative"
        onFocus={() => setShowTooltipState(true)}
        onBlur={() => setShowTooltipState(false)}
        tabIndex={0}
      >
        <Tooltip balance={balance}>
          {content}
        </Tooltip>
      </div>
    );
  }

  return (
    <div
      data-testid="credit-balance-compact"
      className={cn(
        "flex items-center",
        expandable && "cursor-pointer"
      )}
      onClick={expandable ? () => setIsExpanded(!isExpanded) : undefined}
      tabIndex={0}
      aria-label="Credit balance"
    >
      {content}

      {/* Expanded View */}
      {expandable && isExpanded && (
        <div
          data-testid="credit-balance-expanded"
          className={cn(
            "absolute top-full mt-2 right-0 z-50",
            "w-64 p-4 rounded-lg",
            "bg-slate-800 border border-slate-700",
            "shadow-lg"
          )}
        >
          <div className="text-lg font-bold text-white mb-2">
            {formattedBalance}
          </div>
          <div className="text-xs text-slate-400 space-y-1 mb-3">
            <div className="flex justify-between">
              <span>Purchased</span>
              <span className="text-white">
                ${balance?.total_purchased.toFixed(2) ?? "0.00"}
              </span>
            </div>
            <div className="flex justify-between">
              <span>Spent</span>
              <span className="text-white">
                ${balance?.total_spent.toFixed(2) ?? "0.00"}
              </span>
            </div>
          </div>
          <button
            onClick={(e) => {
              e.stopPropagation();
              onAddCredits();
            }}
            className="w-full py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors"
          >
            Add Credits
          </button>
        </div>
      )}
    </div>
  );
}

export default CreditBalanceCompact;
