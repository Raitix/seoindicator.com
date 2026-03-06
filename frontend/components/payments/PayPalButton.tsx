"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Loader2, AlertCircle, RefreshCw, CreditCard } from "lucide-react";
import {
  CreditPackage,
  createPayPalOrder,
  capturePayPalOrder,
  loadPayPalSDK,
  getPayPalClientId,
  isPayPalSDKLoaded,
  getReturnUrl,
  getCancelUrl,
  PayPalError,
  getPayPalErrorMessage,
  storeOrderInfo,
  clearStoredOrderInfo,
} from "@/lib/api/paypal";
import { useBalance } from "@/lib/api/credits";

// ==================== Types ====================

export interface PayPalButtonProps {
  package: CreditPackage;
  onSuccess?: (data: {
    captureId: string;
    credits: number;
    newBalance: number;
    transactionId: string;
    orderId: string;
  }) => void;
  onCancel?: () => void;
  onError?: (error: Error) => void;
  onRedirect?: (url: string) => void;
  flow?: "popup" | "redirect";
  className?: string;
  disabled?: boolean;
  showCardOption?: boolean;
}

interface PayPalSDK {
  Buttons: {
    (config: {
      createOrder: () => Promise<string>;
      onApprove: (data: { orderID: string }) => Promise<void>;
      onCancel?: () => void;
      onError?: (err: Error) => void;
      style?: {
        layout?: "vertical" | "horizontal";
        color?: "gold" | "blue" | "silver" | "white" | "black";
        shape?: "rect" | "pill";
        label?: "paypal" | "checkout" | "buynow" | "pay";
        height?: number;
      };
    }): {
      render: (selector: string) => Promise<void>;
      close: () => void;
    };
  };
}

// ==================== Constants ====================

const PAYPAL_BUTTON_CONTAINER_ID = "paypal-button-container";

// ==================== Main Component ====================

export function PayPalButton({
  package: pkg,
  onSuccess,
  onCancel,
  onError,
  onRedirect,
  flow = "popup",
  className,
  disabled = false,
  showCardOption = false,
}: PayPalButtonProps) {
  const [isLoading, setIsLoading] = React.useState(true);
  const [isProcessing, setIsProcessing] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [sdkError, setSdkError] = React.useState<string | null>(null);
  const containerRef = React.useRef<HTMLDivElement>(null);
  const buttonsRef = React.useRef<ReturnType<PayPalSDK["Buttons"]> | null>(null);
  const { mutate: mutateBalance } = useBalance();

  // Load PayPal SDK
  React.useEffect(() => {
    let mounted = true;

    async function initPayPal() {
      try {
        const clientId = getPayPalClientId();
        
        if (!clientId) {
          if (mounted) {
            setSdkError("PayPal client ID not configured");
            setIsLoading(false);
          }
          return;
        }

        if (!isPayPalSDKLoaded()) {
          await loadPayPalSDK(clientId);
        }

        if (mounted) {
          setIsLoading(false);
          setSdkError(null);
        }
      } catch (err) {
        if (mounted) {
          setSdkError(err instanceof Error ? err.message : "Failed to load PayPal");
          setIsLoading(false);
        }
      }
    }

    initPayPal();

    return () => {
      mounted = false;
    };
  }, []);

  // Render PayPal Buttons
  React.useEffect(() => {
    if (isLoading || sdkError || disabled || isProcessing) {
      return;
    }

    const paypal = (window as any).paypal as PayPalSDK | undefined;
    
    if (!paypal || !paypal.Buttons) {
      setSdkError("PayPal SDK not available");
      return;
    }

    // Clear any existing buttons
    if (buttonsRef.current) {
      try {
        buttonsRef.current.close();
      } catch {
        // Ignore close errors
      }
    }

    // Clear container
    if (containerRef.current) {
      containerRef.current.innerHTML = "";
    }

    // Create buttons
    const buttons = paypal.Buttons({
      style: {
        layout: "vertical",
        color: "gold",
        shape: "rect",
        label: "paypal",
        height: 48,
      },

      createOrder: async () => {
        setError(null);
        setIsProcessing(true);

        try {
          // For redirect flow, include return/cancel URLs
          const returnUrl = flow === "redirect" ? getReturnUrl() : undefined;
          const cancelUrl = flow === "redirect" ? getCancelUrl() : undefined;

          const orderData = await createPayPalOrder(
            pkg.id,
            returnUrl,
            cancelUrl
          );

          // Store order info for redirect flow
          if (flow === "redirect" && orderData) {
            storeOrderInfo(orderData.order_id, pkg.id);
            
            // For redirect flow, redirect to PayPal
            if (orderData.approval_url && onRedirect) {
              onRedirect(orderData.approval_url);
            }
          }

          if (!orderData) {
            throw new Error("Failed to create PayPal order");
          }

          return orderData.order_id;
        } catch (err) {
          setIsProcessing(false);
          const errorMessage = getPayPalErrorMessage(err as Error);
          setError(errorMessage);
          onError?.(err as Error);
          throw err;
        }
      },

      onApprove: async (data: { orderID: string }) => {
        setIsProcessing(true);

        try {
          const captureData = await capturePayPalOrder(data.orderID);

          // Clear stored order info
          clearStoredOrderInfo();

          // Refresh balance
          await mutateBalance();

          setIsProcessing(false);

          onSuccess?.({
            captureId: captureData.capture_id,
            credits: captureData.credits,
            newBalance: captureData.new_balance,
            transactionId: captureData.transaction_id,
            orderId: captureData.order_id,
          });
        } catch (err) {
          setIsProcessing(false);
          const errorMessage = getPayPalErrorMessage(err as Error);
          setError(errorMessage);
          onError?.(err as Error);
        }
      },

      onCancel: () => {
        setIsProcessing(false);
        clearStoredOrderInfo();
        onCancel?.();
      },

      onError: (err) => {
        setIsProcessing(false);
        clearStoredOrderInfo();
        const errorMessage = getPayPalErrorMessage(err as Error);
        setError(errorMessage);
        onError?.(err as Error);
      },
    });

    buttonsRef.current = buttons;

    // Render buttons
    buttons.render(`#${PAYPAL_BUTTON_CONTAINER_ID}`).catch((err) => {
      console.error("Failed to render PayPal buttons:", err);
      setSdkError("Failed to render PayPal buttons");
    });

    return () => {
      if (buttonsRef.current) {
        try {
          buttonsRef.current.close();
        } catch {
          // Ignore close errors
        }
      }
    };
  }, [isLoading, sdkError, disabled, isProcessing, pkg.id, flow, onSuccess, onCancel, onError, onRedirect, mutateBalance]);

  // Loading state
  if (isLoading) {
    return (
      <div
        data-testid="paypal-loading"
        className={cn("flex flex-col items-center justify-center py-8", className)}
      >
        <Loader2 className="w-8 h-8 animate-spin text-blue-500 mb-4" />
        <p className="text-slate-400 text-sm">Loading PayPal...</p>
      </div>
    );
  }

  // SDK Error state
  if (sdkError) {
    return (
      <div
        data-testid="paypal-sdk-error"
        className={cn("p-4 rounded-lg bg-red-500/10 border border-red-500/20", className)}
      >
        <div className="flex items-start gap-3">
          <AlertCircle className="w-5 h-5 text-red-400 mt-0.5" />
          <div>
            <p className="text-red-400 font-medium">PayPal Unavailable</p>
            <p className="text-red-400/80 text-sm mt-1">{sdkError}</p>
          </div>
        </div>
      </div>
    );
  }

  // Main render
  return (
    <div className={cn("space-y-4", className)}>
      {/* Error Message */}
      {error && (
        <div
          data-testid="paypal-error"
          className="p-4 rounded-lg bg-red-500/10 border border-red-500/20"
        >
          <div className="flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-red-400 mt-0.5 shrink-0" />
            <div className="flex-1">
              <p className="text-red-400 font-medium">Payment Error</p>
              <p className="text-red-400/80 text-sm mt-1">{error}</p>
            </div>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setError(null)}
              className="text-red-400 hover:text-red-300"
            >
              <RefreshCw className="w-4 h-4" />
            </Button>
          </div>
        </div>
      )}

      {/* Processing Overlay */}
      {isProcessing && (
        <div
          data-testid="paypal-processing"
          className="flex items-center justify-center py-4"
        >
          <Loader2 className="w-6 h-6 animate-spin text-blue-500 mr-2" />
          <span className="text-slate-400">Processing payment...</span>
        </div>
      )}

      {/* PayPal Button Container */}
      <div
        id={PAYPAL_BUTTON_CONTAINER_ID}
        ref={containerRef}
        data-testid="paypal-button-container"
        className={cn(
          "min-h-[48px]",
          (disabled || isProcessing) && "opacity-50 pointer-events-none"
        )}
      />

      {/* Card Payment Option */}
      {showCardOption && (
        <div className="relative">
          <div className="absolute inset-0 flex items-center">
            <span className="w-full border-t border-slate-700" />
          </div>
          <div className="relative flex justify-center text-xs uppercase">
            <span className="bg-slate-900 px-2 text-slate-500">or</span>
          </div>
        </div>
      )}
    </div>
  );
}

export default PayPalButton;
