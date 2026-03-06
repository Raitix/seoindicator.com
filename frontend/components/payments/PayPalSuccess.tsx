"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { CheckCircle2, Mail, ExternalLink, Receipt, Sparkles } from "lucide-react";

// ==================== Types ====================

export interface PayPalSuccessProps {
  credits: number;
  newBalance: number;
  transactionId?: string;
  orderId?: string;
  onDone?: () => void;
  onViewHistory?: () => void;
  autoCloseDelay?: number;
  showConfetti?: boolean;
  className?: string;
}

// ==================== Confetti Animation Component ====================

function Confetti() {
  return (
    <div
      data-testid="confetti-animation"
      className="absolute inset-0 pointer-events-none overflow-hidden"
      aria-hidden="true"
    >
      {[...Array(50)].map((_, i) => (
        <div
          key={i}
          className="absolute w-2 h-2 rounded-full animate-confetti"
          style={{
            left: `${Math.random() * 100}%`,
            backgroundColor: [
              "#10b981", // emerald
              "#3b82f6", // blue
              "#f59e0b", // amber
              "#ec4899", // pink
              "#8b5cf6", // violet
            ][Math.floor(Math.random() * 5)],
            animationDelay: `${Math.random() * 0.5}s`,
            animationDuration: `${1.5 + Math.random()}s`,
          }}
        />
      ))}
    </div>
  );
}

// ==================== Animated Checkmark Component ====================

function AnimatedCheckmark() {
  return (
    <div
      data-testid="animated-checkmark"
      className="relative w-20 h-20 mx-auto mb-6"
    >
      <div className="absolute inset-0 rounded-full bg-emerald-500/20 animate-ping" />
      <div className="absolute inset-2 rounded-full bg-emerald-500/30 animate-pulse" />
      <div className="relative w-full h-full rounded-full bg-emerald-500 flex items-center justify-center">
        <CheckCircle2 className="w-10 h-10 text-white animate-scale-in" />
      </div>
    </div>
  );
}

// ==================== Main Component ====================

export function PayPalSuccess({
  credits,
  newBalance,
  transactionId,
  orderId,
  onDone,
  onViewHistory,
  autoCloseDelay,
  showConfetti = true,
  className,
}: PayPalSuccessProps) {
  const [isHovered, setIsHovered] = React.useState(false);
  const [countdown, setCountdown] = React.useState<number | null>(
    autoCloseDelay ? Math.ceil(autoCloseDelay / 1000) : null
  );

  // Auto-close countdown
  React.useEffect(() => {
    if (!autoCloseDelay || !onDone || isHovered) return;

    const interval = setInterval(() => {
      setCountdown((prev) => {
        if (prev === null || prev <= 1) {
          clearInterval(interval);
          onDone();
          return null;
        }
        return prev - 1;
      });
    }, 1000);

    const timeout = setTimeout(() => {
      if (!isHovered) {
        onDone();
      }
    }, autoCloseDelay);

    return () => {
      clearInterval(interval);
      clearTimeout(timeout);
    };
  }, [autoCloseDelay, onDone, isHovered]);

  // Format currency
  const formattedBalance = new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(newBalance);

  return (
    <div
      data-testid="paypal-success"
      className={cn(
        "relative p-6 rounded-lg bg-slate-900 border border-slate-800 text-center",
        className
      )}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      {/* Confetti Animation */}
      {showConfetti && <Confetti />}

      {/* Animated Checkmark */}
      <AnimatedCheckmark />

      {/* Success Message */}
      <h2
        data-testid="success-heading"
        className="text-2xl font-bold text-white mb-2"
      >
        Payment Successful!
      </h2>

      <p className="text-slate-400 mb-6">
        Your payment has been processed successfully.
      </p>

      {/* Credits Added */}
      <div
        data-testid="credits-added"
        className="p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20 mb-4"
      >
        <div className="flex items-center justify-center gap-2 mb-1">
          <Sparkles className="w-5 h-5 text-emerald-400" />
          <span className="text-emerald-400 font-semibold">
            {credits.toLocaleString()} credits added
          </span>
        </div>
        <p className="text-slate-400 text-sm">
          to your account
        </p>
      </div>

      {/* New Balance */}
      <div
        data-testid="new-balance"
        className="mb-6"
      >
        <p className="text-slate-400 text-sm">New Balance</p>
        <p className="text-3xl font-bold text-white">{formattedBalance}</p>
      </div>

      {/* Transaction Details */}
      {(transactionId || orderId) && (
        <div
          data-testid="transaction-details"
          className="text-xs text-slate-500 space-y-1 mb-6"
        >
          {orderId && (
            <p>Order ID: <span className="text-slate-400 font-mono">{orderId}</span></p>
          )}
          {transactionId && (
            <p>Transaction ID: <span className="text-slate-400 font-mono">{transactionId}</span></p>
          )}
        </div>
      )}

      {/* Email Receipt Note */}
      <div
        data-testid="email-receipt-note"
        className="flex items-center justify-center gap-2 text-sm text-slate-400 mb-6"
      >
        <Mail className="w-4 h-4" />
        <span>Receipt sent to your email</span>
      </div>

      {/* Actions */}
      <div className="flex flex-col gap-3">
        {onDone && (
          <Button
            onClick={onDone}
            className="w-full gap-2"
            aria-label="Complete and close"
          >
            <CheckCircle2 className="w-4 h-4" />
            Done
            {countdown !== null && (
              <span className="ml-2 text-xs opacity-70">
                ({countdown}s)
              </span>
            )}
          </Button>
        )}

        {onViewHistory && (
          <Button
            variant="outline"
            onClick={onViewHistory}
            className="w-full gap-2"
            aria-label="View transaction history"
          >
            <Receipt className="w-4 h-4" />
            View Transaction History
          </Button>
        )}
      </div>

      {/* PayPal Branding */}
      <p className="text-xs text-slate-500 text-center mt-4">
        Powered by PayPal
      </p>
    </div>
  );
}

export default PayPalSuccess;
