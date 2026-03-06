"use client";

import * as React from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { PayPalSuccess } from "@/components/payments";
import { capturePayPalOrder, parseReturnParams, getStoredOrderInfo, clearStoredOrderInfo, PayPalError } from "@/lib/api/paypal";

export default function PayPalSuccessClient() {
  const router = useRouter();
  const searchParams = useSearchParams();

  const [isLoading, setIsLoading] = React.useState(true);
  const [error, setError] = React.useState<Error | null>(null);
  const [captureData, setCaptureData] = React.useState<{
    captureId: string;
    credits: number;
    newBalance: number;
    transactionId: string;
    orderId: string;
  } | null>(null);

  // Capture the order on mount
  React.useEffect(() => {
    const { token, payerId } = parseReturnParams(searchParams.toString());

    if (!token) {
      setError(new PayPalError("Invalid return URL - missing order token", 0, "INVALID_RETURN"));
      setIsLoading(false);
      return;
    }

    // Verify stored order info matches
    const storedInfo = getStoredOrderInfo();
    if (storedInfo && storedInfo.orderId !== token) {
      // Order ID mismatch - could be stale or tampered
      console.warn("Order ID mismatch between stored and returned");
    }

    // Capture the order
    capturePayPalOrder(token)
      .then((data) => {
        setCaptureData({
          captureId: data.capture_id,
          credits: data.credits,
          newBalance: data.new_balance,
          transactionId: data.transaction_id,
          orderId: data.order_id,
        });
        clearStoredOrderInfo();
      })
      .catch((err) => {
        setError(err instanceof Error ? err : new Error("Failed to capture payment"));
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, [searchParams]);

  const handleDone = React.useCallback(() => {
    router.push("/pricing");
  }, [router]);

  const handleViewHistory = React.useCallback(() => {
    router.push("/settings?tab=billing");
  }, [router]);

  // Loading state
  if (isLoading) {
    return (
      <div className="min-h-screen bg-slate-950 flex items-center justify-center p-4">
        <div className="text-center">
          <div className="w-12 h-12 text-emerald-500 animate-spin mx-auto mb-4">Loading...</div>
          <p className="text-white text-lg font-medium">Processing your payment...</p>
          <p className="text-slate-400 text-sm mt-2">Please wait while we confirm your purchase.</p>
        </div>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="min-h-screen bg-slate-950 flex items-center justify-center p-4">
        <div className="max-w-md w-full">
          <div className="p-6 rounded-lg bg-red-900/20 border border-red-800 text-center">
            <h2 className="text-xl font-bold text-white mb-2">Payment Error</h2>
            <p className="text-slate-400 mb-4">
              {error.message || "An error occurred while processing your payment."}
            </p>
            <button
              onClick={() => router.push("/pricing")}
              className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-md transition-colors"
            >
              Return to Pricing
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Success state
  if (captureData) {
    return (
      <div className="min-h-screen bg-slate-950 flex items-center justify-center p-4">
        <div className="max-w-md w-full">
          <PayPalSuccess
            credits={captureData.credits}
            newBalance={captureData.newBalance}
            transactionId={captureData.transactionId}
            orderId={captureData.orderId}
            onDone={handleDone}
            onViewHistory={handleViewHistory}
          />
        </div>
      </div>
    );
  }

  return null;
}
