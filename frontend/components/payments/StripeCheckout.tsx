"use client";

import * as React from "react";
import { loadStripe } from "@stripe/stripe-js";
import {
  Elements,
  PaymentElement,
  useStripe,
  useElements,
} from "@stripe/react-stripe-js";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Loader2, Lock, AlertCircle, Shield } from "lucide-react";
import { toast } from "sonner";

// ==================== Types ====================

export interface StripeCheckoutProps {
  creditPackage: {
    id: string | number;
    name: string;
    credits: number;
    price: number;
    bonus_percent?: number;
  };
  paymentIntent: {
    client_secret: string;
    payment_intent_id: string;
    amount: number;
    credits: number;
  };
  onSuccess: (data: { paymentIntentId: string; amount: number; credits: number }) => void;
  onError: (error: { type: string; message: string }) => void;
  onCancel: () => void;
  isProcessing?: boolean;
  isLoading?: boolean;
  loadError?: boolean;
  hasValidationError?: boolean;
  className?: string;
}

interface StripeCheckoutInnerProps extends StripeCheckoutProps {
  clientSecret: string;
}

// ==================== Stripe Configuration ====================

const STRIPE_PUBLIC_KEY = process.env.NEXT_PUBLIC_STRIPE_PUBLIC_KEY || "";

const stripePromise = STRIPE_PUBLIC_KEY
  ? loadStripe(STRIPE_PUBLIC_KEY)
  : Promise.reject(new Error("Stripe public key not configured"));

const appearance = {
  theme: "night" as const,
  variables: {
    colorPrimary: "#3b82f6",
    colorBackground: "#0f172a",
    colorText: "#e2e8f0",
    colorDanger: "#ef4444",
    borderRadius: "8px",
    fontFamily: "system-ui, sans-serif",
  },
  rules: {
    ".Input": {
      backgroundColor: "#1e293b",
      border: "1px solid #334155",
      padding: "12px",
      borderRadius: "8px",
    },
    ".Input:focus": {
      border: "1px solid #3b82f6",
      boxShadow: "0 0 0 2px rgba(59, 130, 246, 0.2)",
    },
    ".Label": {
      color: "#94a3b8",
      marginBottom: "8px",
    },
  },
};

// ==================== Checkout Form Component ====================

function CheckoutForm({
  creditPackage,
  paymentIntent,
  onSuccess,
  onError,
  onCancel,
  isProcessing: externalProcessing,
  hasValidationError,
  className,
}: StripeCheckoutInnerProps) {
  const stripe = useStripe();
  const elements = useElements();
  const [isProcessing, setIsProcessing] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [saveCard, setSaveCard] = React.useState(false);

  const processing = externalProcessing || isProcessing;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!stripe || !elements) {
      return;
    }

    setIsProcessing(true);
    setError(null);

    try {
      // Submit the form elements first
      const submitResult = await elements.submit();
      
      if (submitResult.error) {
        setError(submitResult.error.message || "Payment validation failed");
        setIsProcessing(false);
        return;
      }

      // Confirm the payment
      const { error: confirmError, paymentIntent: result } = await stripe.confirmPayment({
        elements,
        confirmParams: {
          return_url: `${window.location.origin}/payments/return`,
        },
        redirect: "if_required",
      });

      if (confirmError) {
        setError(confirmError.message || "Payment failed");
        onError({
          type: confirmError.type || "validation_error",
          message: confirmError.message || "Payment failed",
        });
        setIsProcessing(false);
        return;
      }

      if (result && result.status === "succeeded") {
        toast.success("Payment successful!");
        onSuccess({
          paymentIntentId: result.id,
          amount: paymentIntent.amount,
          credits: paymentIntent.credits,
        });
      } else if (result && result.status === "requires_action") {
        // 3D Secure - will be handled by redirect
        // The redirect will happen automatically
      }
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : "An unexpected error occurred";
      setError(errorMessage);
      onError({
        type: "api_error",
        message: errorMessage,
      });
    } finally {
      setIsProcessing(false);
    }
  };

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
    }).format(price);
  };

  return (
    <form
      data-testid="stripe-checkout-form"
      onSubmit={handleSubmit}
      className={cn("space-y-6", className)}
    >
      {/* Package Summary */}
      <div className="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
        <div className="flex justify-between items-start">
          <div>
            <h3 className="font-semibold text-white">{creditPackage.name}</h3>
            <p className="text-slate-400 text-sm">
              {creditPackage.credits.toLocaleString()} credits
              {creditPackage.bonus_percent && (
                <span className="text-green-400 ml-1">
                  (+{Math.floor(creditPackage.credits * creditPackage.bonus_percent / 100)} bonus)
                </span>
              )}
            </p>
          </div>
          <div className="text-right">
            <p className="text-2xl font-bold text-white">
              {formatPrice(creditPackage.price)}
            </p>
          </div>
        </div>
      </div>

      {/* Payment Element */}
      <div className="space-y-4">
        <label className="block text-sm font-medium text-slate-300">
          Payment Details
        </label>
        <div className="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
          <PaymentElement
            options={{
              layout: "accordion",
              wallets: {
                applePay: "auto",
                googlePay: "auto",
              },
            }}
          />
        </div>
      </div>

      {/* Save Card Checkbox */}
      <label className="flex items-center gap-2 cursor-pointer">
        <input
          type="checkbox"
          checked={saveCard}
          onChange={(e) => setSaveCard(e.target.checked)}
          className="rounded border-slate-600 bg-slate-800 text-blue-500 focus:ring-blue-500"
        />
        <span className="text-sm text-slate-300">
          Save card for future purchases
        </span>
      </label>

      {/* Error Message */}
      {error && (
        <div
          data-testid="stripe-error"
          className="p-4 rounded-lg bg-red-500/10 border border-red-500/20"
          role="alert"
        >
          <div className="flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-red-400 mt-0.5 shrink-0" />
            <div>
              <p className="text-red-400 font-medium">Payment Error</p>
              <p className="text-red-400/80 text-sm mt-1">{error}</p>
            </div>
          </div>
        </div>
      )}

      {/* Submit Button */}
      <Button
        type="submit"
        disabled={!stripe || processing || hasValidationError}
        className="w-full h-12 text-base font-medium"
        size="lg"
      >
        {processing ? (
          <>
            <Loader2 className="w-5 h-5 mr-2 animate-spin" />
            Processing...
          </>
        ) : (
          `Pay ${formatPrice(creditPackage.price)}`
        )}
      </Button>

      {/* Security Note */}
      <div className="flex items-center justify-center gap-2 text-slate-400 text-xs">
        <Lock className="w-3 h-3" />
        <span>Secured by Stripe</span>
        <Shield className="w-3 h-3" />
      </div>
    </form>
  );
}

// ==================== Main Component ====================

export function StripeCheckout({
  creditPackage,
  paymentIntent,
  onSuccess,
  onError,
  onCancel,
  isProcessing,
  isLoading,
  loadError,
  hasValidationError,
  className,
}: StripeCheckoutProps) {
  const [stripeLoadError, setStripeLoadError] = React.useState<string | null>(null);
  const [isStripeLoading, setIsStripeLoading] = React.useState(true);

  React.useEffect(() => {
    stripePromise
      .then(() => {
        setIsStripeLoading(false);
      })
      .catch((err) => {
        setStripeLoadError(err.message);
        setIsStripeLoading(false);
      });
  }, []);

  // Loading state
  if (isLoading || isStripeLoading) {
    return (
      <div
        data-testid="skeleton-loader"
        className={cn("flex flex-col items-center justify-center py-12", className)}
      >
        <Loader2 className="w-8 h-8 animate-spin text-blue-500 mb-4" />
        <p className="text-slate-400">Loading payment form...</p>
      </div>
    );
  }

  // Error state
  if (loadError || stripeLoadError || !paymentIntent?.client_secret) {
    return (
      <div
        data-testid="stripe-load-error"
        className={cn("p-4 rounded-lg bg-red-500/10 border border-red-500/20", className)}
      >
        <div className="flex items-start gap-3">
          <AlertCircle className="w-5 h-5 text-red-400 mt-0.5" />
          <div>
            <p className="text-red-400 font-medium">Payment Unavailable</p>
            <p className="text-red-400/80 text-sm mt-1">
              {stripeLoadError || "Failed to load payment form. Please refresh and try again."}
            </p>
          </div>
        </div>
        <Button
          variant="outline"
          className="mt-4"
          onClick={() => window.location.reload()}
        >
          Retry
        </Button>
      </div>
    );
  }

  return (
    <Elements
      stripe={stripePromise}
      options={{
        clientSecret: paymentIntent.client_secret,
        appearance,
      }}
    >
      <CheckoutForm
        creditPackage={creditPackage}
        paymentIntent={paymentIntent}
        onSuccess={onSuccess}
        onError={onError}
        onCancel={onCancel}
        isProcessing={isProcessing}
        hasValidationError={hasValidationError}
        className={className}
      />
    </Elements>
  );
}

export default StripeCheckout;
