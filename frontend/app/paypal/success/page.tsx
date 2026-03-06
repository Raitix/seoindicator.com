"use client";

import * as React from "react";
import { Suspense } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { PayPalSuccess } from "@/components/payments/PayPalSuccess";
import { PayPalCancel } from "@/components/payments/PayPalCancel";
import {
  capturePayPalOrder,
  parseReturnParams,
  getStoredOrderInfo,
  clearStoredOrderInfo,
  PayPalError,
  getPayPalErrorMessage,
} from "@/lib/api/paypal";
import { useBalance } from "@/lib/api/credits";
import { Loader2, AlertCircle } from "lucide-react";
import { Button } from "@/components/ui/button";

// ==================== Types ====================

interface CaptureState {
  status: "loading" | "success" | "error";
  error?: string;
  data?: {
    captureId: string;
    credits: number;
    newBalance: number;
    transactionId: string;
    orderId: string;
  };
}

// ==================== Main Content Component ====================

function PayPalSuccessContent() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { refresh: refreshBalance } = useBalance();
  const [captureState, setCaptureState] = React.useState<CaptureState>({
    status: "loading",
  });
  const hasCaptured = React.useRef(false);

  // Parse PayPal return parameters
  const { token, payerId } = parseReturnParams(searchParams.toString());

  // Auto-capture on page load
  React.useEffect(() => {
    if (hasCaptured.current) return;
    hasCaptured.current = true;

    async function captureOrder() {
      if (!token) {
        setCaptureState({
          status: "error",
          error: "Invalid return URL. No order token found.",
        });
        return;
      }

      try {
        // Get stored order info for validation
        const storedOrder = getStoredOrderInfo();
        
        // Validate order ID matches
        if (storedOrder && storedOrder.orderId !== token) {
          console.warn("Order ID mismatch:", {
            stored: storedOrder.orderId,
            returned: token,
          });
        }

        // Capture the order
        const captureData = await capturePayPalOrder(token);

        if (!captureData) {
          throw new PayPalError("Capture returned no data", undefined, "CAPTURE_FAILED");
        }

        // Clear stored order info
        clearStoredOrderInfo();

        // Refresh balance
        await refreshBalance();

        setCaptureState({
          status: "success",
          data: {
            captureId: captureData.capture_id,
            credits: captureData.credits,
            newBalance: captureData.new_balance,
            transactionId: captureData.transaction_id,
            orderId: captureData.order_id,
          },
        });
      } catch (err) {
        const errorMessage = getPayPalErrorMessage(err as Error);
        
        // Check for specific error codes
        if (err instanceof PayPalError && err.code === "ORDER_ALREADY_CAPTURED") {
          // Order was already captured - treat as success
          setCaptureState({
            status: "success",
            data: {
              captureId: "",
              credits: 0,
              newBalance: 0,
              transactionId: "",
              orderId: token,
            },
          });
          clearStoredOrderInfo();
          return;
        }

        setCaptureState({
          status: "error",
          error: errorMessage,
        });
      }
    }

    captureOrder();
  }, [token, refreshBalance]);

  // Loading state
  if (captureState.status === "loading") {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-950">
        <div className="max-w-md w-full p-8 text-center">
          <Loader2 className="w-12 h-12 animate-spin text-blue-500 mx-auto mb-4" />
          <h1 className="text-2xl font-bold text-white mb-2">
            Processing Payment
          </h1>
          <p className="text-slate-400">
            Please wait while we confirm your payment...
          </p>
        </div>
      </div>
    );
  }

  // Error state
  if (captureState.status === "error") {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-950">
        <div className="max-w-md w-full p-8">
          <div className="p-6 rounded-lg bg-slate-900 border border-slate-800 text-center">
            <div className="w-16 h-16 mx-auto mb-6 rounded-full bg-red-500/20 flex items-center justify-center">
              <AlertCircle className="w-8 h-8 text-red-400" />
            </div>

            <h1 className="text-2xl font-bold text-white mb-2">
              Payment Error
            </h1>

            <p className="text-slate-400 mb-6">
              {captureState.error || "An error occurred while processing your payment."}
            </p>

            <div className="flex flex-col gap-3">
              <Button
                onClick={() => router.push("/pricing")}
                className="w-full"
              >
                Return to Pricing
              </Button>

              <Button
                variant="outline"
                onClick={() => router.push("/dashboard")}
              >
                Go to Dashboard
              </Button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Success state
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-950">
      <div className="max-w-md w-full p-8">
        <PayPalSuccess
          credits={captureState.data?.credits || 0}
          newBalance={captureState.data?.newBalance || 0}
          transactionId={captureState.data?.transactionId}
          orderId={captureState.data?.orderId}
          onDone={() => router.push("/dashboard")}
          onViewHistory={() => router.push("/dashboard/history")}
          showConfetti={true}
        />
      </div>
    </div>
  );
}

// ==================== Page with Suspense ====================

export default function PayPalSuccessPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen flex items-center justify-center bg-slate-950">
          <div className="max-w-md w-full p-8 text-center">
            <Loader2 className="w-12 h-12 animate-spin text-blue-500 mx-auto mb-4" />
            <h1 className="text-2xl font-bold text-white mb-2">
              Loading...
            </h1>
          </div>
        </div>
      }
    >
      <PayPalSuccessContent />
    </Suspense>
  );
}
