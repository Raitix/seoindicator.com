"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { AlertTriangle, Plus, X, Undo2 } from "lucide-react";

// ==================== Types ====================

export interface LowBalanceWarningProps {
  balance: number;
  threshold?: number;
  onAddCredits: () => void;
  onDismiss?: () => void;
  showToast?: boolean;
  autoDismissMs?: number;
  showUndo?: boolean;
  id?: string;
}

// ==================== Storage Key ====================

const DISMISSED_KEY = "seoindicator_low_balance_dismissed";

// ==================== Main Component ====================

export function LowBalanceWarning({
  balance,
  threshold = 2.0,
  onAddCredits,
  onDismiss,
  showToast = false,
  autoDismissMs,
  showUndo = false,
  id,
}: LowBalanceWarningProps) {
  const [isVisible, setIsVisible] = React.useState(true);
  const [showUndoButton, setShowUndoButton] = React.useState(false);

  // Check if balance is below threshold
  const isLowBalance = balance < threshold;

  // Auto-dismiss for toast variant
  React.useEffect(() => {
    if (showToast && autoDismissMs && isVisible) {
      const timer = setTimeout(() => {
        setIsVisible(false);
      }, autoDismissMs);
      return () => clearTimeout(timer);
    }
  }, [showToast, autoDismissMs, isVisible]);

  // Handle dismiss
  const handleDismiss = () => {
    setIsVisible(false);
    onDismiss?.();

    // Store dismissal timestamp
    if (typeof window !== "undefined") {
      try {
        localStorage.setItem(DISMISSED_KEY, Date.now().toString());
      } catch {
        // Ignore storage errors
      }
    }

    if (showUndo) {
      setShowUndoButton(true);
    }
  };

  // Handle undo
  const handleUndo = () => {
    setIsVisible(true);
    setShowUndoButton(false);

    if (typeof window !== "undefined") {
      try {
        localStorage.removeItem(DISMISSED_KEY);
      } catch {
        // Ignore storage errors
      }
    }
  };

  // Handle add credits
  const handleAddCredits = () => {
    onAddCredits();
  };

  // Don't render if balance is sufficient
  if (!isLowBalance) {
    return null;
  }

  // Show undo button if dismissed with showUndo enabled
  if (!isVisible && showUndoButton) {
    return (
      <button
        onClick={handleUndo}
        className="inline-flex items-center gap-1 text-xs text-amber-300 hover:text-amber-200 transition-colors"
        aria-label="Undo dismiss warning"
      >
        <Undo2 className="w-3 h-3" />
        Undo
      </button>
    );
  }

  // Don't render if not visible
  if (!isVisible) {
    return null;
  }

  // Toast variant
  if (showToast) {
    return (
      <div
        role="alert"
        id={id}
        data-testid="low-balance-warning"
        className={cn(
          "flex items-center gap-3 p-3 rounded-lg",
          "bg-amber-900/30 border border-amber-700/50",
          "text-amber-200"
        )}
      >
        <AlertTriangle className="w-5 h-5 text-amber-400 flex-shrink-0" />
        <div className="flex-1 min-w-0">
          <p className="text-sm font-medium">Low Balance</p>
          <p className="text-xs text-amber-300/80">
            Only ${balance.toFixed(2)} remaining. Add credits to continue.
          </p>
        </div>
        <button
          onClick={handleAddCredits}
          className="flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-amber-600 hover:bg-amber-500 text-white rounded-md transition-colors"
        >
          <Plus className="w-3 h-3" />
          Add Credits
        </button>
      </div>
    );
  }

  // Default inline warning
  return (
    <div
      role="alert"
      aria-live="polite"
      data-testid="low-balance-warning"
      className={cn(
        "flex items-start gap-3 p-3 rounded-lg",
        "bg-amber-900/20 border border-amber-700/50"
      )}
    >
      <AlertTriangle className="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" />
      <div className="flex-1 min-w-0">
        <p className="text-sm font-medium text-amber-200">
          Your balance is running low
        </p>
        <p className="text-xs text-amber-300/70 mt-1">
          Current balance: ${balance.toFixed(2)}. Add credits to avoid interruptions.
        </p>
        <button
          onClick={handleAddCredits}
          className="mt-2 inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-amber-600 hover:bg-amber-500 text-white rounded-md transition-colors"
        >
          <Plus className="w-3 h-3" />
          Add Credits
        </button>
      </div>
      <button
        onClick={handleDismiss}
        className="p-1 text-amber-400/60 hover:text-amber-300 transition-colors"
        aria-label="Dismiss warning"
      >
        <X className="w-4 h-4" />
      </button>
    </div>
  );
}

export default LowBalanceWarning;
