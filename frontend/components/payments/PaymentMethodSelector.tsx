"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  CreditCard,
  Plus,
  MoreVertical,
  Check,
  AlertTriangle,
  Loader2,
  Trash2,
  Star,
} from "lucide-react";
import {
  usePaymentMethods,
  useDeletePaymentMethod,
  useSetDefaultPaymentMethod,
} from "@/lib/api/payments";
import {
  getCardBrandIcon,
  getCardBrandName,
  isCardExpired,
  isCardExpiringSoon,
} from "@/lib/api/payments";

// ==================== Types ====================

export interface PaymentMethodSelectorProps {
  methods?: Array<{
    id: string;
    type: "card" | "paypal" | "apple_pay" | "google_pay";
    card?: {
      brand: string;
      last4: string;
      exp_month: number;
      exp_year: number;
    };
    is_default: boolean;
  }>;
  selectedId?: string;
  onMethodSelect: (method: {
    id: string;
    type: string;
    card?: { brand: string; last4: string };
  }) => void;
  onAddNew: () => void;
  isLoading?: boolean;
  error?: string;
  compact?: boolean;
  isModal?: boolean;
  className?: string;
}

// ==================== Card Item Component ====================

interface CardItemProps {
  method: {
    id: string;
    type: string;
    card?: {
      brand: string;
      last4: string;
      exp_month: number;
      exp_year: number;
    };
    is_default: boolean;
  };
  isSelected: boolean;
  onSelect: () => void;
  onDelete: () => void;
  onSetDefault: () => void;
  compact?: boolean;
}

function CardItem({
  method,
  isSelected,
  onSelect,
  onDelete,
  onSetDefault,
  compact,
}: CardItemProps) {
  const [showMenu, setShowMenu] = React.useState(false);

  if (method.type !== "card" || !method.card) {
    return null;
  }

  const { brand, last4, exp_month, exp_year } = method.card;
  const isExpired = isCardExpired(exp_month, exp_year);
  const expiringSoon = isCardExpiringSoon(exp_month, exp_year);

  return (
    <div
      data-testid={`method-${method.id}`}
      role="radio"
      aria-checked={isSelected}
      aria-label={`${getCardBrandName(brand)} ending in ${last4}${isExpired ? " (expired)" : ""}${method.is_default ? " (default)" : ""}`}
      className={cn(
        "relative p-4 rounded-lg border-2 cursor-pointer transition-all",
        isSelected && "selected border-blue-500 bg-blue-500/10",
        !isSelected && "border-slate-700 bg-slate-800/50 hover:border-slate-600",
        isExpired && "opacity-60"
      )}
      onClick={onSelect}
    >
      <div className="flex items-center gap-4">
        {/* Card Icon */}
        <div className="shrink-0">
          <img
            src={getCardBrandIcon(brand)}
            alt={getCardBrandName(brand)}
            className="w-10 h-6 object-contain"
          />
        </div>

        {/* Card Info */}
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <span className="text-white font-medium">
              {getCardBrandName(brand)}
            </span>
            <span className="text-slate-400">****{last4}</span>
          </div>

          {!compact && (
            <div className="flex items-center gap-2 mt-1 text-sm">
              <span className="text-slate-400">
                Expires {String(exp_month).padStart(2, "0")}/{exp_year}
              </span>
              {isExpired && (
                <span className="text-red-400 flex items-center gap-1">
                  <AlertTriangle className="w-3 h-3" />
                  Expired
                </span>
              )}
              {expiringSoon && !isExpired && (
                <span className="text-yellow-400 flex items-center gap-1">
                  <AlertTriangle className="w-3 h-3" />
                  Expiring soon
                </span>
              )}
            </div>
          )}
        </div>

        {/* Selection Indicator */}
        {isSelected && (
          <div className="shrink-0 w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center">
            <Check className="w-4 h-4 text-white" />
          </div>
        )}

        {/* Default Badge */}
        {method.is_default && (
          <div className="shrink-0 px-2 py-0.5 text-xs bg-slate-700 text-slate-300 rounded-full">
            Default
          </div>
        )}

        {/* Menu */}
        {!compact && (
          <div className="relative shrink-0">
            <Button
              variant="ghost"
              size="icon"
              className="h-8 w-8 text-slate-400 hover:text-white"
              onClick={(e) => {
                e.stopPropagation();
                setShowMenu(!showMenu);
              }}
            >
              <MoreVertical className="w-4 h-4" />
            </Button>

            {showMenu && (
              <>
                <div
                  className="fixed inset-0 z-10"
                  onClick={() => setShowMenu(false)}
                />
                <div className="absolute right-0 top-full mt-1 z-20 w-40 bg-slate-800 border border-slate-700 rounded-lg shadow-lg overflow-hidden">
                  {!method.is_default && (
                    <button
                      className="w-full px-4 py-2 text-left text-sm text-slate-300 hover:bg-slate-700 flex items-center gap-2"
                      onClick={(e) => {
                        e.stopPropagation();
                        setShowMenu(false);
                        onSetDefault();
                      }}
                    >
                      <Star className="w-4 h-4" />
                      Set as default
                    </button>
                  )}
                  <button
                    className="w-full px-4 py-2 text-left text-sm text-red-400 hover:bg-slate-700 flex items-center gap-2"
                    onClick={(e) => {
                      e.stopPropagation();
                      setShowMenu(false);
                      onDelete();
                    }}
                  >
                    <Trash2 className="w-4 h-4" />
                    Delete
                  </button>
                </div>
              </>
            )}
          </div>
        )}
      </div>
    </div>
  );
}

// ==================== Main Component ====================

export function PaymentMethodSelector({
  methods: externalMethods,
  selectedId,
  onMethodSelect,
  onAddNew,
  isLoading: externalLoading = false,
  error: externalError,
  compact = false,
  isModal = false,
  className,
}: PaymentMethodSelectorProps) {
  const {
    methods: fetchedMethods,
    isLoading: isLoadingMethods,
    error: fetchError,
  } = usePaymentMethods();

  const { deleteMethod, isLoading: isDeleting } = useDeletePaymentMethod();
  const { setDefault, isLoading: isSettingDefault } = useSetDefaultPaymentMethod();

  const methods = externalMethods || fetchedMethods;
  const isLoading = externalLoading || isLoadingMethods || isDeleting || isSettingDefault;
  const error = externalError || fetchError?.message;

  const handleDelete = async (methodId: string) => {
    if (confirm("Are you sure you want to delete this payment method?")) {
      await deleteMethod(methodId);
    }
  };

  const handleSetDefault = async (methodId: string) => {
    await setDefault(methodId);
  };

  // Loading state
  if (isLoading && methods.length === 0) {
    return (
      <div
        data-testid="loading-skeleton"
        className={cn("space-y-3", className)}
      >
        {[1, 2].map((i) => (
          <div
            key={i}
            className="h-20 rounded-lg bg-slate-800 animate-pulse"
          />
        ))}
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div
        className={cn("p-4 rounded-lg bg-red-500/10 border border-red-500/20", className)}
      >
        <p className="text-red-400">{error}</p>
        <Button
          variant="ghost"
          size="sm"
          className="mt-2"
          onClick={() => window.location.reload()}
        >
          <Loader2 className="w-4 h-4 mr-2" />
          Retry
        </Button>
      </div>
    );
  }

  // Empty state
  if (methods.length === 0) {
    return (
      <div className={cn("text-center py-8", className)}>
        <CreditCard className="w-12 h-12 text-slate-500 mx-auto mb-4" />
        <p className="text-slate-400 mb-4">No saved payment methods</p>
        <Button onClick={onAddNew}>
          <Plus className="w-4 h-4 mr-2" />
          Add payment method
        </Button>
      </div>
    );
  }

  return (
    <div
      role="radiogroup"
      aria-label="Select payment method"
      className={cn("space-y-3", className)}
    >
      {/* Saved Methods */}
      {methods.map((method) => (
        <CardItem
          key={method.id}
          method={method}
          isSelected={selectedId === method.id}
          onSelect={() => onMethodSelect(method)}
          onDelete={() => handleDelete(method.id)}
          onSetDefault={() => handleSetDefault(method.id)}
          compact={compact}
        />
      ))}

      {/* Add New Button */}
      <button
        type="button"
        onClick={onAddNew}
        className={cn(
          "w-full p-4 rounded-lg border-2 border-dashed border-slate-700",
          "hover:border-slate-600 hover:bg-slate-800/50 transition-colors",
          "flex items-center justify-center gap-2 text-slate-400 hover:text-white"
        )}
      >
        <Plus className="w-5 h-5" />
        <span>Add new card</span>
      </button>
    </div>
  );
}

export default PaymentMethodSelector;
