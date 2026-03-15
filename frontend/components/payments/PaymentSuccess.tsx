"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Check, ArrowRight, Mail, ExternalLink } from "lucide-react";
import { useRouter } from "next/navigation";

// ==================== Types ====================

export interface PaymentSuccessProps {
  credits: number;
  newBalance: number;
  transactionId: string;
  onDone: () => void;
  autoCloseDelay?: number;
  className?: string;
}

// ==================== Main Component ====================

export function PaymentSuccess({
  credits,
  newBalance,
  transactionId,
  onDone,
  autoCloseDelay,
  className,
}: PaymentSuccessProps) {
  const router = useRouter();
  const containerRef = React.useRef<HTMLDivElement>(null);
  const [isPaused, setIsPaused] = React.useState(false);

  // Confetti effect
  React.useEffect(() => {
    if (typeof window !== "undefined") {
      import("canvas-confetti")
        .then((confetti) => {
          confetti.default({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 },
          });
        })
        .catch(() => {});
    }
  }, []);

  // Auto-close with pause on hover
  React.useEffect(() => {
    if (autoCloseDelay && !isPaused) {
      const timer = setTimeout(onDone, autoCloseDelay);
      return () => clearTimeout(timer);
    }
  }, [autoCloseDelay, isPaused, onDone]);

  const handleMouseEnter = () => setIsPaused(true);
  const handleMouseLeave = () => setIsPaused(false);

  const handleViewHistory = () => {
    router.push("/payments/history");
  };

  const handleDone = () => {
    onDone();
  };

  return (
    <div
      ref={containerRef}
      data-testid="success-container"
      className={cn(
        "w-full max-w-md mx-auto p-8 rounded-xl bg-slate-900 border border-slate-700 text-center",
        className
      )}
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
    >
      {/* Confetti placeholder */}
      <div data-testid="confetti" className="hidden" />

      {/* Checkmark Icon */}
      <div
        data-testid="checkmark-icon"
        className={cn(
          "mx-auto w-20 h-20 rounded-full bg-green-500/20 flex items-center justify-center mb-6",
          "animate-scale-in"
        )}
      >
        <Check className="w-12 h-12 text-green-400" />
      </div>

      {/* Success Message */}
      <div role="alert" className="space-y-2">
        <h2 className="text-2xl font-bold text-white">Payment Successful!</h2>
        <p className="text-green-400 text-lg">
          {credits.toLocaleString()} credits added
        </p>
      </div>

      {/* Transaction ID */}
      <p className="text-slate-500 text-sm mt-4 font-mono">
        {transactionId}
      </p>

      {/* New Balance */}
      <div className="mt-6 p-4 bg-slate-800/50 rounded-lg">
        <p className="text-slate-400 text-sm">New Balance</p>
        <p className="text-3xl font-bold text-white mt-1">
          ${newBalance.toFixed(2)}
        </p>
      </div>

      {/* Email Receipt Note */}
      <div className="flex items-center justify-center gap-2 text-slate-400 text-sm mt-6">
        <Mail className="w-4 h-4" />
        <span>Receipt sent to your email</span>
      </div>

      {/* Actions */}
      <div className="mt-8 space-y-3">
        <Button
          onClick={handleDone}
          className="w-full h-12"
          size="lg"
        >
          Done
        </Button>

        <Button
          variant="ghost"
          onClick={handleViewHistory}
          className="w-full text-slate-400 hover:text-white"
        >
          Transaction History
          <ExternalLink className="w-4 h-4 ml-2" />
        </Button>
      </div>
    </div>
  );
}

export default PaymentSuccess;
