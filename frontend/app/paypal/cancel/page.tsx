"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import { PayPalCancel } from "@/components/payments/PayPalCancel";
import { getStoredOrderInfo, clearStoredOrderInfo } from "@/lib/api/paypal";

// ==================== Main Component ====================

export default function PayPalCancelPage() {
  const router = useRouter();
  const [storedPackageId, setStoredPackageId] = React.useState<number | null>(null);

  // Get stored order info on mount
  React.useEffect(() => {
    const storedOrder = getStoredOrderInfo();
    if (storedOrder) {
      setStoredPackageId(storedOrder.packageId);
    }
    // Clear stored order info since payment was cancelled
    clearStoredOrderInfo();
  }, []);

  const handleRetry = () => {
    router.push("/pricing");
  };

  const handleReturnToPricing = () => {
    router.push("/pricing");
  };

  const handlePayWithCard = () => {
    // For future card payment implementation
    router.push("/pricing?method=card");
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-950">
      <div className="max-w-md w-full p-8">
        <PayPalCancel
          onRetry={handleRetry}
          onReturnToPricing={handleReturnToPricing}
          onPayWithCard={handlePayWithCard}
        />
      </div>
    </div>
  );
}
