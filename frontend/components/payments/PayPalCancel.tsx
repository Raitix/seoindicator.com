"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { XCircle, RefreshCw, CreditCard, HelpCircle, ArrowLeft } from "lucide-react";
import type { CreditPackage } from "@/lib/api/paypal";

// ==================== Types ====================

export interface PayPalCancelProps {
  package?: CreditPackage;
  onRetry?: () => void;
  onReturnToPricing?: () => void;
  onPayWithCard?: () => void;
  supportEmail?: string;
  className?: string;
}

// ==================== Package Summary ====================

interface PackageSummaryProps {
  pkg: CreditPackage;
}

function PackageSummary({ pkg }: PackageSummaryProps) {
  const totalCredits = pkg.credits + Math.floor(pkg.credits * (pkg.bonus_percent / 100));

  return (
    <div
      data-testid="cancelled-package-summary"
      className="p-3 rounded-lg bg-slate-800/50 border border-slate-700 mb-4"
    >
      <div className="flex justify-between items-center">
        <div>
          <span className="text-slate-400 text-sm">You selected:</span>
          <p className="text-white font-medium">{pkg.name}</p>
        </div>
        <span className="text-white font-bold">
          ${pkg.price.toFixed(2)}
        </span>
      </div>
      <p className="text-slate-400 text-sm mt-1">
        {totalCredits.toLocaleString()} credits
      </p>
    </div>
  );
}

// ==================== Main Component ====================

export function PayPalCancel({
  package: pkg,
  onRetry,
  onReturnToPricing,
  onPayWithCard,
  supportEmail = "support@seoindicator.com",
  className,
}: PayPalCancelProps) {
  // Track cancel event (analytics)
  React.useEffect(() => {
    // Analytics event tracking
    if (typeof window !== "undefined" && (window as any).gtag) {
      (window as any).gtag("event", "paypal_cancel", {
        package_id: pkg?.id,
        package_name: pkg?.name,
      });
    }
  }, [pkg?.id, pkg?.name]);

  return (
    <div
      data-testid="paypal-cancel"
      className={cn(
        "p-6 rounded-lg bg-slate-900 border border-slate-800 text-center",
        className
      )}
    >
      {/* Cancel Icon */}
      <div
        data-testid="cancel-icon"
        className="w-16 h-16 mx-auto mb-6 rounded-full bg-amber-500/20 flex items-center justify-center"
      >
        <XCircle className="w-8 h-8 text-amber-400" />
      </div>

      {/* Cancel Message */}
      <h2
        data-testid="cancel-heading"
        className="text-2xl font-bold text-white mb-2"
      >
        Payment Cancelled
      </h2>

      <p className="text-slate-400 mb-6">
        You cancelled the PayPal payment. No charge has been made to your account.
      </p>

      {/* Package Summary (if available) */}
      {pkg && <PackageSummary pkg={pkg} />}

      {/* No Charge Confirmation */}
      <div
        data-testid="no-charge-confirmation"
        className="p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20 mb-6"
      >
        <p className="text-emerald-400 text-sm">
          Your card has not been charged
        </p>
      </div>

      {/* Actions */}
      <div className="flex flex-col gap-3 mb-6">
        {onRetry && (
          <Button
            onClick={onRetry}
            className="w-full gap-2"
            aria-label="Try payment again"
          >
            <RefreshCw className="w-4 h-4" />
            Try Again
          </Button>
        )}

        {onPayWithCard && (
          <Button
            variant="outline"
            onClick={onPayWithCard}
            className="w-full gap-2"
            aria-label="Pay with card instead"
          >
            <CreditCard className="w-4 h-4" />
            Pay with Card Instead
          </Button>
        )}

        {onReturnToPricing && (
          <Button
            variant="ghost"
            onClick={onReturnToPricing}
            className="w-full gap-2 text-slate-400 hover:text-white"
            aria-label="Return to pricing page"
          >
            <ArrowLeft className="w-4 h-4" />
            Return to Pricing
          </Button>
        )}
      </div>

      {/* Support Contact */}
      <div
        data-testid="support-contact"
        className="text-sm text-slate-500"
      >
        <div className="flex items-center justify-center gap-2 mb-1">
          <HelpCircle className="w-4 h-4" />
          <span>Need help?</span>
        </div>
        <a
          href={`mailto:${supportEmail}`}
          className="text-blue-400 hover:text-blue-300 transition-colors"
        >
          Contact Support
        </a>
      </div>
    </div>
  );
}

export default PayPalCancel;
