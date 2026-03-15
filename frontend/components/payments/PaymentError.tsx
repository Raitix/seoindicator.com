"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { AlertCircle, RefreshCw, HelpCircle, ExternalLink } from "lucide-react";

// ==================== Types ====================

export interface PaymentErrorProps {
  errorType?: 'card_declined' | 'insufficient_funds' | 'network_error' | 'timeout' | 'fraud' | 'expired_card' | 'incorrect_cvc' | 'processing_error' | 'rate_limit' | 'unknown';
  error?: string;
  errorMessage?: string;
  errorCode?: string;
  errorDetails?: { type?: string; message?: string };
  onRetry?: () => void;
  onCancel?: () => void;
  onDismiss?: () => void;
  retryAfter?: number;
  showContactSupport?: boolean;
  className?: string;
}

// ==================== Error Messages ====================

const ERROR_MESSAGES: Record<string, { title: string; message: string }> = {
  card_declined: {
    title: "Card Declined",
    message: "Your card was declined. Please try a different payment method.",
  },
  insufficient_funds: {
    title: "Insufficient Funds",
    message: "Insufficient funds. Please check your balance or try a different card.",
  },
  expired_card: {
    title: "Card Expired",
    message: "Your card has expired. Please use a different payment method.",
  },
  incorrect_cvc: {
    title: "Incorrect Security Code",
    message: "The security code is incorrect. Please check and try again.",
  },
  processing_error: {
    title: "Processing Error",
    message: "A processing error occurred. Please try again.",
  },
  rate_limit: {
    title: "Too Many Attempts",
    message: "Too many attempts. Please wait before trying again.",
  },
  timeout: {
    title: "Payment Timed Out",
    message: "The payment timed out. Please try again.",
  },
  fraud: {
    title: "Payment Blocked",
    message: "Payment blocked for security reasons. Please contact support.",
  },
  network_error: {
    title: "Network Error",
    message: "Network error. Please check your connection and try again.",
  },
  network: {
    title: "Network Error",
    message: "Network error. Please check your connection and try again.",
  },
  unknown: {
    title: "Payment Failed",
    message: "An error occurred while processing your payment.",
  },
};

// ==================== Main Component ====================

export function PaymentError({
  errorType = 'unknown',
  error,
  errorMessage,
  errorCode,
  errorDetails,
  onRetry,
  onCancel,
  onDismiss,
  retryAfter,
  showContactSupport = true,
  className,
}: PaymentErrorProps) {
  const [countdown, setCountdown] = React.useState(retryAfter || 0);
  const [retryCount, setRetryCount] = React.useState(0);

  // Log error in development
  React.useEffect(() => {
    if (process.env.NODE_ENV === 'development' && errorDetails) {
      console.error('[PaymentError]', errorDetails);
    }
  }, [errorDetails]);

  // Countdown timer
  React.useEffect(() => {
    if (retryAfter && countdown > 0) {
      const timer = setTimeout(() => setCountdown((c) => c - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [retryAfter, countdown]);

  const getErrorInfo = () => {
    const key = errorType || 'unknown';
    return ERROR_MESSAGES[key] || ERROR_MESSAGES.unknown;
  };

  const errorInfo = getErrorInfo();
  
  // Use provided errorMessage, error, or default from error type
  const displayMessage = errorMessage || error || errorInfo.message;

  // Mask sensitive card data in message
  const maskedMessage = displayMessage?.replace(/\d{13,16}/g, '****');

  const handleRetry = () => {
    setRetryCount((c) => c + 1);
    onRetry?.();
  };

  const handleDismiss = () => {
    (onCancel || onDismiss)?.();
  };

  const canRetry = !retryAfter || countdown === 0;

  return (
    <div
      role="alert"
      className={cn(
        "w-full max-w-md mx-auto p-6 rounded-xl bg-slate-900 border border-red-500/30",
        className
      )}
    >
      {/* Icon */}
      <div className="flex items-start gap-4">
        <div 
          className="shrink-0 w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center"
          data-testid="error-icon"
          aria-label="Error"
        >
          <AlertCircle className="w-6 h-6 text-red-400" />
        </div>

        <div className="flex-1">
          {/* Title */}
          <h3 className="text-lg font-semibold text-white">Payment Failed</h3>

          {/* Message */}
          <p className="text-slate-400 text-sm mt-2">
            {maskedMessage}
          </p>

          {/* Error Code */}
          {errorCode && (
            <p className="text-slate-500 text-xs mt-2 font-mono">
              Error code: {errorCode}
            </p>
          )}
        </div>
      </div>

      {/* Retry Countdown */}
      {retryAfter && countdown > 0 && (
        <div className="mt-4 p-3 bg-yellow-500/10 rounded-lg">
          <p className="text-yellow-400 text-sm text-center">
            Please wait <span className="font-bold">{countdown}</span> seconds before retrying
          </p>
        </div>
      )}

      {/* Actions */}
      <div className="mt-6 space-y-3">
        {onRetry && canRetry && (
          <Button
            onClick={handleRetry}
            className="w-full"
            variant="default"
          >
            <RefreshCw className="w-4 h-4 mr-2" />
            Try Again
          </Button>
        )}

        {handleDismiss && (
          <Button
            onClick={handleDismiss}
            variant="ghost"
            className="w-full text-slate-400"
          >
            Cancel
          </Button>
        )}
      </div>

      {/* Contact Support */}
      {showContactSupport && (
        <div className="mt-4 pt-4 border-t border-slate-700">
          <a
            href="/support"
            className="inline-flex items-center gap-1 text-blue-400 hover:underline text-sm"
          >
            <HelpCircle className="w-4 h-4" />
            Need help? Contact support
            <ExternalLink className="w-3 h-3" />
          </a>
        </div>
      )}

      {/* Retry Count Warning */}
      {retryCount >= 3 && (
        <div className="mt-4 p-3 bg-yellow-500/10 rounded-lg">
          <p className="text-yellow-400 text-sm text-center">
            Multiple failed attempts. Please verify your payment details or contact support.
          </p>
        </div>
      )}
    </div>
  );
}

export default PaymentError;
