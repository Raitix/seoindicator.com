"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  CheckCircle,
  XCircle,
  Loader2,
  Download,
  RefreshCw,
  Mail,
  ArrowLeft,
  ExternalLink,
  AlertTriangle,
} from "lucide-react";
import { useRouter } from "next/navigation";

// ==================== Types ====================

export interface PaymentConfirmationProps {
  status: "success" | "error" | "pending" | "cancelled";
  transaction?: {
    id: string;
    amount: number;
    currency: string;
    credits: number;
    status: string;
    payment_method: string;
    created_at: string;
    receipt_url?: string;
  };
  error?: string;
  errorCode?: string;
  errorDetails?: string;
  errorReference?: string;
  onRetry?: (data?: { packageId?: string }) => void;
  onBackToDashboard?: () => void;
  emailSent?: boolean;
  newBalance?: number;
  showShare?: boolean;
  allowNewCard?: boolean;
  retryCount?: number;
  maxRetries?: number;
  retryAfter?: number;
  packageId?: string;
  savedCards?: Array<{ id: string; last4: string }>;
  trackEvent?: (event: string, data: Record<string, unknown>) => void;
  className?: string;
}

// ==================== Error Messages ====================

const ERROR_MESSAGES: Record<string, string> = {
  card_declined: "Your card was declined.",
  insufficient_funds: "Insufficient funds in your account.",
  expired_card: "Your card has expired.",
  incorrect_cvc: "Invalid security code.",
  processing_error: "Processing error. Please try again.",
  network: "Network error. Please check your connection.",
  rate_limit: "Too many attempts. Please wait.",
  timeout: "Payment timed out.",
  duplicate: "Payment already processed.",
  fraud: "Payment blocked. Please contact support.",
  currency_not_supported: "Currency not supported.",
  amount_too_large: "Amount exceeds limit.",
};

// ==================== Main Component ====================

export function PaymentConfirmation({
  status,
  transaction,
  error,
  errorCode,
  errorDetails,
  errorReference,
  onRetry,
  onBackToDashboard,
  emailSent = false,
  newBalance,
  showShare = false,
  allowNewCard = false,
  retryCount = 0,
  maxRetries = 3,
  retryAfter,
  packageId,
  savedCards,
  trackEvent,
  className,
}: PaymentConfirmationProps) {
  const router = useRouter();
  const [showDetails, setShowDetails] = React.useState(false);
  const [countdown, setCountdown] = React.useState(retryAfter || 0);
  const [selectedCard, setSelectedCard] = React.useState<string | null>(null);

  // Countdown timer for retry-after
  React.useEffect(() => {
    if (retryAfter && countdown > 0) {
      const timer = setTimeout(() => setCountdown((c) => c - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [retryAfter, countdown]);

  // Confetti effect on success
  React.useEffect(() => {
    if (status === "success" && typeof window !== "undefined") {
      // Dynamic import of confetti
      import("canvas-confetti")
        .then((confetti) => {
          confetti.default({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 },
          });
        })
        .catch(() => {
          // Confetti failed to load, ignore
        });
    }
  }, [status]);

  // Focus management
  React.useEffect(() => {
    const focusTarget = status === "error" ? "error-message" : "success-message";
    const element = document.getElementById(focusTarget);
    element?.focus();
  }, [status]);

  const canRetry = retryCount < maxRetries && countdown === 0;

  const getErrorMessage = () => {
    if (errorCode && ERROR_MESSAGES[errorCode]) {
      return ERROR_MESSAGES[errorCode];
    }
    return error || "Something went wrong. Please try again.";
  };

  const handleRetry = () => {
    trackEvent?.("payment_retry", { packageId });
    onRetry?.({ packageId });
  };

  const handleBackToDashboard = () => {
    onBackToDashboard?.();
    router.push("/dashboard");
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
      hour: "numeric",
      minute: "2-digit",
    });
  };

  const formatCurrency = (amount: number, currency = "USD") => {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency,
    }).format(amount);
  };

  return (
    <div
      data-testid="confirmation-container"
      className={cn(
        "w-full max-w-md mx-auto p-6 rounded-xl bg-slate-900 border border-slate-700",
        className
      )}
    >
      {/* Success State */}
      {status === "success" && (
        <div className="text-center space-y-6">
          {/* Icon */}
          <div
            data-testid="check-circle-icon"
            aria-label="Success"
            className="mx-auto w-16 h-16 rounded-full bg-green-500/20 flex items-center justify-center"
          >
            <CheckCircle className="w-10 h-10 text-green-400" />
          </div>

          {/* Message */}
          <div
            id="success-message"
            role="status"
            aria-live="polite"
            tabIndex={-1}
          >
            <h2 className="text-2xl font-bold text-white">Payment successful!</h2>
            {transaction && (
              <p className="text-green-400 text-lg mt-2">
                +{transaction.credits} credits added
              </p>
            )}
          </div>

          {/* Transaction Details */}
          {transaction && (
            <section
              role="region"
              aria-label="Transaction details"
              className="bg-slate-800/50 rounded-lg p-4 text-left space-y-2"
            >
              <p className="text-slate-400 text-sm">Transaction ID</p>
              <p className="text-white font-mono">{transaction.id}</p>

              <p className="text-slate-400 text-sm mt-4">Amount Charged</p>
              <p className="text-white">
                {formatCurrency(transaction.amount, transaction.currency)} charged
              </p>

              <p className="text-slate-400 text-sm mt-4">Payment Method</p>
              <p className="text-white">{transaction.payment_method}</p>

              <p className="text-slate-400 text-sm mt-4">Date</p>
              <p className="text-white">{formatDate(transaction.created_at)}</p>

              {newBalance !== undefined && (
                <>
                  <p className="text-slate-400 text-sm mt-4">New Balance</p>
                  <p className="text-white">{newBalance} credits</p>
                </>
              )}
            </section>
          )}

          {/* Email Confirmation */}
          {emailSent && (
            <div className="flex items-center justify-center gap-2 text-slate-400 text-sm">
              <Mail className="w-4 h-4" />
              <span>Confirmation email sent</span>
            </div>
          )}

          {/* Receipt Button */}
          {transaction?.receipt_url && (
            <Button
              variant="outline"
              className="w-full"
              aria-label={`Download receipt for transaction ${transaction.id}`}
              onClick={() => window.open(transaction.receipt_url, "_blank")}
            >
              <Download className="w-4 h-4 mr-2" />
              Receipt
            </Button>
          )}

          {/* Share Button */}
          {showShare && (
            <Button variant="ghost" className="w-full">
              Share
            </Button>
          )}

          {/* View Credits Link */}
          <a
            href="/credits"
            className="block text-blue-400 hover:underline text-sm"
          >
            View credits
          </a>

          {/* Back to Dashboard */}
          <Button
            className="w-full"
            onClick={handleBackToDashboard}
          >
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back to Dashboard
          </Button>
        </div>
      )}

      {/* Error State */}
      {status === "error" && (
        <div className="text-center space-y-6">
          {/* Icon */}
          <div
            data-testid="x-circle-icon"
            aria-label="Error"
            className="mx-auto w-16 h-16 rounded-full bg-red-500/20 flex items-center justify-center"
          >
            <XCircle className="w-10 h-10 text-red-400" />
          </div>

          {/* Error Message */}
          <div
            id="error-message"
            role="alert"
            aria-live="assertive"
            tabIndex={-1}
          >
            <h2 className="text-xl font-bold text-white">{getErrorMessage()}</h2>
          </div>

          {/* Error Details Toggle */}
          {errorDetails && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setShowDetails(!showDetails)}
              className="text-slate-400"
            >
              {showDetails ? "Hide" : "Show"} details
            </Button>
          )}
          {showDetails && (
            <p className="text-slate-400 text-sm bg-slate-800/50 rounded-lg p-3">
              {errorDetails}
            </p>
          )}

          {/* Error Reference */}
          {errorReference && (
            <p className="text-slate-400 text-sm">
              Reference: <span className="font-mono">{errorReference}</span>
            </p>
          )}

          {/* Retry Options */}
          {canRetry ? (
            <div className="space-y-4">
              <Button
                onClick={handleRetry}
                className="w-full"
              >
                <RefreshCw className="w-4 h-4 mr-2" />
                Try again
              </Button>

              {allowNewCard && (
                <Button variant="outline" className="w-full">
                  Use different card
                </Button>
              )}

              {savedCards && savedCards.length > 0 && (
                <div className="space-y-2">
                  <label className="block text-sm text-slate-400 text-left">
                    Select card
                  </label>
                  <select
                    aria-label="Select card"
                    value={selectedCard || ""}
                    onChange={(e) => setSelectedCard(e.target.value)}
                    className="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white"
                  >
                    {savedCards.map((card) => (
                      <option key={card.id} value={card.id}>
                        ****{card.last4}
                      </option>
                    ))}
                  </select>
                </div>
              )}
            </div>
          ) : (
            retryCount >= maxRetries && (
              <p className="text-red-400 text-sm">
                Too many attempts. Please contact support.
              </p>
            )
          )}

          {/* Countdown */}
          {countdown > 0 && (
            <p className="text-slate-400 text-sm">
              Wait {countdown} seconds before retrying
            </p>
          )}

          {/* Cancel */}
          <Button
            variant="ghost"
            onClick={handleBackToDashboard}
            className="w-full text-slate-400"
          >
            Cancel
          </Button>

          {/* Support Link */}
          <a
            href="/support"
            className="inline-flex items-center gap-1 text-blue-400 hover:underline text-sm"
          >
            Contact support
            <ExternalLink className="w-3 h-3" />
          </a>
        </div>
      )}

      {/* Pending State */}
      {status === "pending" && (
        <div className="text-center space-y-6">
          <div
            data-testid="loader-icon"
            className="mx-auto w-16 h-16 rounded-full bg-blue-500/20 flex items-center justify-center animate-spin"
          >
            <Loader2 className="w-10 h-10 text-blue-400" />
          </div>
          <div>
            <h2 className="text-xl font-bold text-white">Processing...</h2>
            <p className="text-slate-400 mt-2">
              Please wait while we process your payment.
            </p>
          </div>
        </div>
      )}

      {/* Cancelled State */}
      {status === "cancelled" && (
        <div className="text-center space-y-6">
          <div className="mx-auto w-16 h-16 rounded-full bg-yellow-500/20 flex items-center justify-center">
            <AlertTriangle className="w-10 h-10 text-yellow-400" />
          </div>
          <div>
            <h2 className="text-xl font-bold text-white">Payment Cancelled</h2>
            <p className="text-slate-400 mt-2">
              Your payment was cancelled. You have not been charged.
            </p>
          </div>
          <Button
            className="w-full"
            onClick={handleBackToDashboard}
          >
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back to Dashboard
          </Button>
        </div>
      )}
    </div>
  );
}

export default PaymentConfirmation;
