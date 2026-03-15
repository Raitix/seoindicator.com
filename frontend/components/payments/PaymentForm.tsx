"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  CreditCard,
  Lock,
  AlertCircle,
  Check,
  Loader2,
  ChevronRight,
} from "lucide-react";
import { toast } from "sonner";
import { useRouter } from "next/navigation";

// ==================== Types ====================

export interface PaymentFormProps {
  selectedPackage: {
    id: string | number;
    name: string;
    credits: number;
    price: number;
    currency?: string;
    bonus_credits?: number;
    popular?: boolean;
    type?: "one-time" | "subscription";
  };
  onSuccess: (result: { success: boolean; credits?: number }) => void;
  onCancel: () => void;
  isProcessing?: boolean;
  isLoading?: boolean;
  cardError?: string | null;
  hasSavedCard?: boolean;
  savedCardId?: string;
  termsRequired?: boolean;
  requireEmail?: boolean;
  requireName?: boolean;
  requireAddress?: boolean;
  csrfToken?: string;
  currency?: string;
  className?: string;
  isModal?: boolean;
  saveCard?: boolean;
}

interface FormState {
  email: string;
  name: string;
  address: string;
  country: string;
  postalCode: string;
  useSavedCard: boolean;
  termsAccepted: boolean;
  saveCard: boolean;
  cardComplete: boolean;
}

interface FormErrors {
  email?: string;
  name?: string;
  address?: string;
  country?: string;
  postalCode?: string;
  card?: string;
  terms?: string;
}

// ==================== Constants ====================

const CARD_BRAND_PATTERNS: Record<string, RegExp> = {
  visa: /^4/,
  mastercard: /^5[1-5]/,
  amex: /^3[47]/,
  discover: /^6(?:011|5)/,
};

const CARD_BRAND_ICONS: Record<string, string> = {
  visa: "/icons/cards/visa.svg",
  mastercard: "/icons/cards/mastercard.svg",
  amex: "/icons/cards/amex.svg",
  discover: "/icons/cards/discover.svg",
};

// ==================== Mock Card Element (for testing without actual Stripe) ====================

function MockCardElement({
  onChange,
  disabled,
}: {
  onChange?: (event: { complete: boolean; error: string | null; empty: boolean }) => void;
  disabled?: boolean;
}) {
  const [value, setValue] = React.useState("");
  const [focused, setFocused] = React.useState(false);
  const [touched, setTouched] = React.useState(false);

  const getCardBrand = (val: string): string | null => {
    const cleanVal = val.replace(/\s/g, "");
    for (const [brand, pattern] of Object.entries(CARD_BRAND_PATTERNS)) {
      if (pattern.test(cleanVal)) return brand;
    }
    return null;
  };

  const brand = getCardBrand(value);
  const isComplete = value.replace(/\s/g, "").length >= 16;

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const val = e.target.value.replace(/\D/g, "").slice(0, 16);
    // Format with spaces
    const formatted = val.replace(/(.{4})/g, "$1 ").trim();
    setValue(formatted);

    if (onChange) {
      onChange({
        complete: val.length >= 16,
        error: null,
        empty: val.length === 0,
      });
    }
  };

  const handleBlur = () => {
    setFocused(false);
    setTouched(true);
  };

  return (
    <div
      data-testid="card-element"
      data-complete={!disabled}
      className="relative"
    >
      <div className="flex items-center gap-2 bg-slate-800 border border-slate-700 rounded-lg p-3 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20">
        {brand && (
          <img
            src={CARD_BRAND_ICONS[brand]}
            alt={`${brand} card`}
            className="w-8 h-5 object-contain"
          />
        )}
        <input
          data-testid="card-number-input"
          type="password"
          placeholder="Card number"
          value={value}
          onChange={handleChange}
          onFocus={() => setFocused(true)}
          onBlur={handleBlur}
          disabled={disabled}
          className="flex-1 bg-transparent border-none outline-none text-white placeholder-slate-400"
          aria-label="Card number"
        />
        <CreditCard className="w-5 h-5 text-slate-400" />
      </div>
    </div>
  );
}

// ==================== Main Component ====================

export function PaymentForm({
  selectedPackage,
  onSuccess,
  onCancel,
  isProcessing: externalProcessing = false,
  isLoading = false,
  cardError: externalCardError,
  hasSavedCard = false,
  savedCardId,
  termsRequired = false,
  requireEmail = false,
  requireName = false,
  requireAddress = false,
  csrfToken,
  currency = "USD",
  className,
  isModal = false,
  saveCard: initialSaveCard = false,
}: PaymentFormProps) {
  const router = useRouter();
  const [formState, setFormState] = React.useState<FormState>({
    email: "",
    name: "",
    address: "",
    country: "",
    postalCode: "",
    useSavedCard: hasSavedCard && !!savedCardId,
    termsAccepted: false,
    saveCard: initialSaveCard,
    cardComplete: false,
  });

  const [errors, setErrors] = React.useState<FormErrors>({});
  const [isProcessing, setIsProcessing] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [cardBrand, setCardBrand] = React.useState<string | null>(null);

  const processing = externalProcessing || isProcessing;

  // Validation
  const validateForm = React.useCallback((): boolean => {
    const newErrors: FormErrors = {};

    if (requireEmail) {
      if (!formState.email) {
        newErrors.email = "Email is required";
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formState.email)) {
        newErrors.email = "Invalid email format";
      }
    }

    if (requireName) {
      if (!formState.name) {
        newErrors.name = "Name is required";
      } else if (formState.name.length < 2) {
        newErrors.name = "Name too short";
      } else if (formState.name.length > 100) {
        newErrors.name = "Name too long";
      }
    }

    if (requireAddress) {
      if (!formState.address) {
        newErrors.address = "Address is required";
      }
      if (!formState.country) {
        newErrors.country = "Country is required";
      }
      if (!formState.postalCode) {
        newErrors.postalCode = "Postal code is required";
      }
    }

    if (!formState.useSavedCard && !formState.cardComplete) {
      newErrors.card = "Card details required";
    }

    if (termsRequired && !formState.termsAccepted) {
      newErrors.terms = "You must accept the terms and conditions";
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  }, [formState, requireEmail, requireName, requireAddress, termsRequired]);

  // Check if form is valid
  const isFormValid = React.useMemo(() => {
    if (requireEmail && (!formState.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formState.email))) {
      return false;
    }
    if (requireName && (!formState.name || formState.name.length < 2)) {
      return false;
    }
    if (requireAddress && (!formState.address || !formState.country || !formState.postalCode)) {
      return false;
    }
    if (!formState.useSavedCard && !formState.cardComplete) {
      return false;
    }
    if (termsRequired && !formState.termsAccepted) {
      return false;
    }
    return true;
  }, [formState, requireEmail, requireName, requireAddress, termsRequired]);

  // Handle card element change
  const handleCardChange = React.useCallback(
    (event: { complete: boolean; error: string | null; empty: boolean }) => {
      setFormState((prev) => ({ ...prev, cardComplete: event.complete }));
      if (event.error) {
        setErrors((prev) => ({ ...prev, card: event.error || undefined }));
      } else {
        setErrors((prev) => {
          const { card, ...rest } = prev;
          return rest;
        });
      }
    },
    []
  );

  // Handle form submission
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validateForm()) {
      // Focus first error field
      const firstErrorField = Object.keys(errors)[0];
      if (firstErrorField) {
        const element = document.querySelector(`[name="${firstErrorField}"]`) as HTMLElement;
        element?.focus();
      }
      return;
    }

    setIsProcessing(true);
    setError(null);

    const idempotencyKey = crypto.randomUUID();

    try {
      // Create payment intent
      const response = await fetch("/api/v1/payments/create-intent", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken || "",
          "Idempotency-Key": idempotencyKey,
        },
        body: JSON.stringify({
          packageId: selectedPackage.id,
          email: formState.email || undefined,
          name: formState.name || undefined,
          savedCardId: formState.useSavedCard ? savedCardId : null,
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        if (response.status === 401) {
          router.replace("/login");
          return;
        }
        if (response.status === 429) {
          setError("Too many attempts. Please wait and try again.");
          return;
        }
        setError(data.error?.message || "Payment failed");
        return;
      }

      // Confirm payment
      const confirmResponse = await fetch("/api/v1/payments/confirm", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken || "",
          "Idempotency-Key": `${idempotencyKey}-confirm`,
        },
        body: JSON.stringify({
          payment_intent_id: data.payment_intent_id,
          save_card: formState.saveCard,
        }),
      });

      const confirmData = await confirmResponse.json();

      if (!confirmResponse.ok) {
        setError(confirmData.error?.message || "Payment confirmation failed");
        return;
      }

      toast.success("Payment successful!");
      onSuccess({
        success: true,
        credits: confirmData.credits,
      });

      if (data.redirect) {
        router.push(data.redirect);
      }
    } catch (err) {
      setError("Network error. Please check your connection and try again.");
    } finally {
      setIsProcessing(false);
    }
  };

  // Clear errors when form changes
  React.useEffect(() => {
    if (error) {
      setError(null);
    }
  }, [formState]);

  // Format currency
  const formatPrice = (price: number) => {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency,
    }).format(price);
  };

  // Loading state
  if (isLoading) {
    return (
      <div
        data-testid="payment-form-container"
        className={cn("flex flex-col items-center justify-center py-12", className)}
      >
        <Loader2 data-testid="loader-icon" className="w-8 h-8 animate-spin text-blue-500 mb-4" />
        <p className="text-slate-400">Loading payment form...</p>
      </div>
    );
  }

  const displayError = error || externalCardError;

  return (
    <div
      data-testid="payment-form-container"
      className={cn("w-full", className)}
    >
      <form
        data-testid="payment-form"
        className={cn(
          "flex flex-col gap-6",
          isModal && "max-w-md mx-auto"
        )}
        onSubmit={handleSubmit}
        aria-busy={processing}
      >
        {/* Package Summary */}
        <div className="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
          <div className="flex justify-between items-start">
            <div>
              <h3 className="font-semibold text-white">{selectedPackage.name}</h3>
              <p className="text-slate-400 text-sm">
                {selectedPackage.credits.toLocaleString()} credits
                {selectedPackage.bonus_credits && (
                  <span className="text-green-400 ml-1">
                    (+{selectedPackage.bonus_credits} bonus)
                  </span>
                )}
              </p>
            </div>
            <div className="text-right">
              <p data-testid="total-amount" className="text-2xl font-bold text-white">
                {formatPrice(selectedPackage.price)}
              </p>
            </div>
          </div>
        </div>

        {/* Error Message */}
        {displayError && (
          <div
            role="alert"
            aria-live="assertive"
            className="p-4 rounded-lg bg-red-500/10 border border-red-500/20"
          >
            <div className="flex items-start gap-3">
              <AlertCircle className="w-5 h-5 text-red-400 mt-0.5 shrink-0" />
              <div className="flex-1">
                <p className="text-red-400 font-medium">Payment Error</p>
                <p className="text-red-400/80 text-sm mt-1">{displayError}</p>
              </div>
            </div>
            <Button
              type="button"
              variant="outline"
              size="sm"
              className="mt-3"
              onClick={() => setError(null)}
            >
              Try again
            </Button>
          </div>
        )}

        {/* Email Field */}
        {requireEmail && (
          <div className="space-y-2">
            <label htmlFor="email" className="block text-sm font-medium text-slate-300">
              Email
            </label>
            <input
              id="email"
              name="email"
              type="email"
              aria-label="Email"
              aria-required="true"
              value={formState.email}
              onChange={(e) =>
                setFormState((prev) => ({ ...prev, email: e.target.value }))
              }
              className={cn(
                "w-full px-4 py-3 bg-slate-800 border rounded-lg text-white placeholder-slate-400",
                "focus:outline-none focus:ring-2 focus:ring-blue-500/20",
                errors.email ? "border-red-500" : "border-slate-700"
              )}
              placeholder="you@example.com"
            />
            {errors.email && (
              <p className="text-red-400 text-sm">{errors.email}</p>
            )}
          </div>
        )}

        {/* Name Field */}
        {requireName && (
          <div className="space-y-2">
            <label htmlFor="name" className="block text-sm font-medium text-slate-300">
              Name
            </label>
            <input
              id="name"
              name="name"
              type="text"
              aria-label="Name"
              aria-required="true"
              value={formState.name}
              onChange={(e) =>
                setFormState((prev) => ({ ...prev, name: e.target.value }))
              }
              className={cn(
                "w-full px-4 py-3 bg-slate-800 border rounded-lg text-white placeholder-slate-400",
                "focus:outline-none focus:ring-2 focus:ring-blue-500/20",
                errors.name ? "border-red-500" : "border-slate-700"
              )}
              placeholder="John Doe"
            />
            {errors.name && (
              <p className="text-red-400 text-sm">{errors.name}</p>
            )}
          </div>
        )}

        {/* Address Fields */}
        {requireAddress && (
          <div className="space-y-4">
            <div className="space-y-2">
              <label htmlFor="address" className="block text-sm font-medium text-slate-300">
                Address
              </label>
              <input
                id="address"
                name="address"
                type="text"
                aria-label="Address"
                aria-required="true"
                value={formState.address}
                onChange={(e) =>
                  setFormState((prev) => ({ ...prev, address: e.target.value }))
                }
                className={cn(
                  "w-full px-4 py-3 bg-slate-800 border rounded-lg text-white placeholder-slate-400",
                  "focus:outline-none focus:ring-2 focus:ring-blue-500/20",
                  errors.address ? "border-red-500" : "border-slate-700"
                )}
                placeholder="123 Main St"
              />
              {errors.address && (
                <p className="text-red-400 text-sm">{errors.address}</p>
              )}
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <label htmlFor="country" className="block text-sm font-medium text-slate-300">
                  Country
                </label>
                <select
                  id="country"
                  name="country"
                  aria-label="Country"
                  aria-required="true"
                  value={formState.country}
                  onChange={(e) =>
                    setFormState((prev) => ({ ...prev, country: e.target.value }))
                  }
                  className={cn(
                    "w-full px-4 py-3 bg-slate-800 border rounded-lg text-white",
                    "focus:outline-none focus:ring-2 focus:ring-blue-500/20",
                    errors.country ? "border-red-500" : "border-slate-700"
                  )}
                >
                  <option value="">Select country</option>
                  <option value="US">United States</option>
                  <option value="CA">Canada</option>
                  <option value="GB">United Kingdom</option>
                  <option value="DE">Germany</option>
                  <option value="FR">France</option>
                  <option value="AU">Australia</option>
                </select>
                {errors.country && (
                  <p className="text-red-400 text-sm">{errors.country}</p>
                )}
              </div>

              <div className="space-y-2">
                <label htmlFor="postalCode" className="block text-sm font-medium text-slate-300">
                  Postal Code
                </label>
                <input
                  id="postalCode"
                  name="postalCode"
                  type="text"
                  aria-label="Postal code"
                  aria-required="true"
                  value={formState.postalCode}
                  onChange={(e) =>
                    setFormState((prev) => ({ ...prev, postalCode: e.target.value }))
                  }
                  className={cn(
                    "w-full px-4 py-3 bg-slate-800 border rounded-lg text-white placeholder-slate-400",
                    "focus:outline-none focus:ring-2 focus:ring-blue-500/20",
                    errors.postalCode ? "border-red-500" : "border-slate-700"
                  )}
                  placeholder="12345"
                />
                {errors.postalCode && (
                  <p className="text-red-400 text-sm">{errors.postalCode}</p>
                )}
              </div>
            </div>
          </div>
        )}

        {/* Saved Card Option */}
        {hasSavedCard && (
          <div className="space-y-3">
            <label className="flex items-center gap-3 cursor-pointer">
              <input
                type="radio"
                name="paymentMethod"
                checked={formState.useSavedCard}
                onChange={() =>
                  setFormState((prev) => ({ ...prev, useSavedCard: true }))
                }
                className="text-blue-500 focus:ring-blue-500"
              />
              <span className="text-slate-300">Use saved card</span>
            </label>
            <label className="flex items-center gap-3 cursor-pointer">
              <input
                type="radio"
                name="paymentMethod"
                checked={!formState.useSavedCard}
                onChange={() =>
                  setFormState((prev) => ({ ...prev, useSavedCard: false }))
                }
                className="text-blue-500 focus:ring-blue-500"
              />
              <span className="text-slate-300">New card</span>
            </label>
          </div>
        )}

        {/* Card Element */}
        {!formState.useSavedCard && (
          <div className="space-y-2">
            <label className="block text-sm font-medium text-slate-300">
              Card Details
            </label>
            <MockCardElement
              onChange={handleCardChange}
              disabled={processing}
            />
            {errors.card && (
              <p className="text-red-400 text-sm">{errors.card}</p>
            )}
          </div>
        )}

        {/* Save Card Checkbox */}
        {!formState.useSavedCard && (
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={formState.saveCard}
              onChange={(e) =>
                setFormState((prev) => ({ ...prev, saveCard: e.target.checked }))
              }
              className="rounded border-slate-600 bg-slate-800 text-blue-500 focus:ring-blue-500"
            />
            <span className="text-sm text-slate-300">Save card for future purchases</span>
          </label>
        )}

        {/* Terms Checkbox */}
        {termsRequired && (
          <div className="space-y-2">
            <label className="flex items-start gap-2 cursor-pointer">
              <input
                type="checkbox"
                aria-label="Accept terms"
                checked={formState.termsAccepted}
                onChange={(e) =>
                  setFormState((prev) => ({ ...prev, termsAccepted: e.target.checked }))
                }
                className="mt-1 rounded border-slate-600 bg-slate-800 text-blue-500 focus:ring-blue-500"
              />
              <span className="text-sm text-slate-300">
                I agree to the{" "}
                <a href="/terms" className="text-blue-400 hover:underline">
                  Terms of Service
                </a>{" "}
                and{" "}
                <a href="/privacy" className="text-blue-400 hover:underline">
                  Privacy Policy
                </a>
              </span>
            </label>
            {errors.terms && (
              <p className="text-red-400 text-sm">{errors.terms}</p>
            )}
          </div>
        )}

        {/* Submit Button */}
        <Button
          type="submit"
          disabled={processing || !isFormValid}
          className="w-full h-12 text-base font-medium"
          size="lg"
        >
          {processing ? (
            <>
              <Loader2 className="w-5 h-5 mr-2 animate-spin" />
              Processing...
            </>
          ) : selectedPackage.type === "subscription" ? (
            <>
              Subscribe
              <ChevronRight className="w-5 h-5 ml-2" />
            </>
          ) : (
            <>
              Pay {formatPrice(selectedPackage.price)}
              <ChevronRight className="w-5 h-5 ml-2" />
            </>
          )}
        </Button>

        {/* Cancel Button */}
        <Button
          type="button"
          variant="ghost"
          onClick={onCancel}
          disabled={processing}
          className="w-full"
        >
          Cancel
        </Button>

        {/* Security Note */}
        <div className="flex items-center justify-center gap-2 text-slate-400 text-xs">
          <Lock className="w-3 h-3" />
          <span>Payments secured by Stripe. Your card details are never stored.</span>
        </div>
      </form>
    </div>
  );
}

export default PaymentForm;
