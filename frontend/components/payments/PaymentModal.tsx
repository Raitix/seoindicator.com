"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { X, Loader2, Check, CreditCard, FileText } from "lucide-react";
import { StripeCheckout } from "./StripeCheckout";
import {
  useCreatePaymentIntent,
  usePaymentMethods,
} from "@/lib/api/payments";

// ==================== Types ====================

export interface PaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: (data: {
    paymentIntentId: string;
    amount: number;
    credits: number;
  }) => void;
  creditPackage: {
    id: string | number;
    name: string;
    credits: number;
    price: number;
    bonus_percent?: number;
    is_popular?: boolean;
  };
  isProcessing?: boolean;
  isLoading?: boolean;
  currentStep?: number;
  className?: string;
}

// ==================== Constants ====================

const STEPS = [
  { id: 0, name: "Package", icon: FileText },
  { id: 1, name: "Payment", icon: CreditCard },
  { id: 2, name: "Confirm", icon: Check },
];

// ==================== Main Component ====================

export function PaymentModal({
  isOpen,
  onClose,
  onSuccess,
  creditPackage,
  isProcessing: externalProcessing = false,
  isLoading: externalLoading = false,
  currentStep = 1,
  className,
}: PaymentModalProps) {
  const modalRef = React.useRef<HTMLDivElement>(null);
  const previousActiveElement = React.useRef<HTMLElement | null>(null);
  
  const [isProcessing, setIsProcessing] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  
  const { createIntent, isLoading: isCreatingIntent, error: intentError } = useCreatePaymentIntent();
  const { methods: savedMethods } = usePaymentMethods();
  
  const [paymentIntent, setPaymentIntent] = React.useState<{
    client_secret: string;
    payment_intent_id: string;
    amount: number;
    credits: number;
  } | null>(null);

  const processing = externalProcessing || isProcessing;

  // Create payment intent when modal opens
  React.useEffect(() => {
    if (isOpen && !paymentIntent && !isCreatingIntent) {
      createIntent({
        packageId: creditPackage.id,
      }).then((intent) => {
        if (intent) {
          setPaymentIntent(intent);
        }
      });
    }
  }, [isOpen, creditPackage.id, createIntent, paymentIntent, isCreatingIntent]);

  // Focus trap and accessibility
  React.useEffect(() => {
    if (isOpen) {
      previousActiveElement.current = document.activeElement as HTMLElement;
      modalRef.current?.focus();
      document.body.style.overflow = "hidden";
    }

    return () => {
      document.body.style.overflow = "";
      previousActiveElement.current?.focus();
    };
  }, [isOpen]);

  // Handle escape key
  React.useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === "Escape" && isOpen && !processing) {
        onClose();
      }
    };

    document.addEventListener("keydown", handleEscape);
    return () => document.removeEventListener("keydown", handleEscape);
  }, [isOpen, onClose, processing]);

  // Handle backdrop click
  const handleBackdropClick = (e: React.MouseEvent) => {
    if (e.target === e.currentTarget && !processing) {
      onClose();
    }
  };

  // Handle success
  const handleSuccess = (data: { paymentIntentId: string; amount: number; credits: number }) => {
    setIsProcessing(false);
    onSuccess(data);
  };

  // Handle error
  const handleError = (error: { type: string; message: string }) => {
    setIsProcessing(false);
    setError(error.message);
  };

  // Calculate bonus credits
  const bonusCredits = creditPackage.bonus_percent
    ? Math.floor(creditPackage.credits * (creditPackage.bonus_percent / 100))
    : 0;

  if (!isOpen) return null;

  const isLoading = externalLoading || isCreatingIntent;

  return (
    <div
      data-testid="modal-backdrop"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
      onClick={handleBackdropClick}
      role="presentation"
    >
      <div
        ref={modalRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="payment-modal-title"
        tabIndex={-1}
        className={cn(
          "relative w-full max-w-lg mx-4 bg-slate-900 rounded-xl shadow-xl border border-slate-700",
          "max-h-[90vh] overflow-y-auto",
          className
        )}
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="sticky top-0 z-10 bg-slate-900 border-b border-slate-700 px-6 py-4">
          <div className="flex items-center justify-between">
            <h2
              id="payment-modal-title"
              className="text-xl font-semibold text-white"
            >
              Complete Purchase
            </h2>
            <Button
              variant="ghost"
              size="icon"
              onClick={onClose}
              disabled={processing}
              aria-label="Close modal"
              className="text-slate-400 hover:text-white"
            >
              <X className="w-5 h-5" />
            </Button>
          </div>

          {/* Progress Steps */}
          <div className="mt-4 flex items-center gap-2">
            {STEPS.map((step, index) => {
              const Icon = step.icon;
              const isActive = index === currentStep;
              const isCompleted = index < currentStep;

              return (
                <React.Fragment key={step.id}>
                  <div
                    data-testid={`step-${step.id}`}
                    className={cn(
                      "flex items-center gap-2 px-3 py-1.5 rounded-full text-sm transition-colors",
                      isActive && "bg-blue-500/20 text-blue-400",
                      isCompleted && "bg-green-500/20 text-green-400",
                      !isActive && !isCompleted && "bg-slate-800 text-slate-400"
                    )}
                  >
                    {isCompleted ? (
                      <Check className="w-4 h-4" />
                    ) : (
                      <Icon className="w-4 h-4" />
                    )}
                    <span>{step.name}</span>
                  </div>
                  {index < STEPS.length - 1 && (
                    <div className="flex-1 h-px bg-slate-700" />
                  )}
                </React.Fragment>
              );
            })}
          </div>
        </div>

        {/* Content */}
        <div className="p-6">
          {/* Package Info */}
          <div className="mb-6 p-4 bg-slate-800/50 rounded-lg border border-slate-700">
            <div className="flex justify-between items-start">
              <div>
                <div className="flex items-center gap-2">
                  <h3 className="font-semibold text-white">{creditPackage.name}</h3>
                  {creditPackage.is_popular && (
                    <span className="px-2 py-0.5 text-xs bg-blue-500/20 text-blue-400 rounded-full">
                      Popular
                    </span>
                  )}
                </div>
                <p className="text-slate-400 text-sm mt-1">
                  {creditPackage.credits.toLocaleString()} credits
                  {bonusCredits > 0 && (
                    <span className="text-green-400 ml-1">
                      (+{bonusCredits} bonus)
                    </span>
                  )}
                </p>
              </div>
              <p className="text-2xl font-bold text-white">
                ${creditPackage.price.toFixed(2)}
              </p>
            </div>
          </div>

          {/* Loading State */}
          {isLoading && (
            <div
              data-testid="loading-spinner"
              className="flex flex-col items-center justify-center py-12"
            >
              <Loader2 className="w-8 h-8 animate-spin text-blue-500 mb-4" />
              <p className="text-slate-400">Preparing payment...</p>
            </div>
          )}

          {/* Error State */}
          {(error || intentError) && (
            <div className="p-4 rounded-lg bg-red-500/10 border border-red-500/20 mb-4">
              <p className="text-red-400">
                {error || intentError?.message || "Failed to initialize payment"}
              </p>
              <Button
                variant="outline"
                size="sm"
                className="mt-3"
                onClick={() => {
                  setError(null);
                  setPaymentIntent(null);
                }}
              >
                Try again
              </Button>
            </div>
          )}

          {/* Stripe Checkout */}
          {!isLoading && !error && !intentError && paymentIntent && (
            <StripeCheckout
              creditPackage={creditPackage}
              paymentIntent={paymentIntent}
              onSuccess={handleSuccess}
              onError={handleError}
              onCancel={onClose}
              isProcessing={processing}
            />
          )}
        </div>

        {/* Footer */}
        <div className="border-t border-slate-700 px-6 py-4">
          <p className="text-xs text-slate-400 text-center">
            Payments are processed securely by Stripe. Your payment information is encrypted and never stored on our servers.
          </p>
        </div>
      </div>
    </div>
  );
}

export default PaymentModal;
