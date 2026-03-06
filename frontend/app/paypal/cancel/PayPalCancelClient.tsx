"use client";

import * as React from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { PayPalCancel } from "@/components/payments";
import { parseReturnParams, getStoredOrderInfo, clearStoredOrderInfo } from "@/lib/api/paypal";

export default function PayPalCancelClient() {
  const router = useRouter();
  const searchParams = useSearchParams();

  // Get stored order info for package details
  const storedInfo = React.useMemo(() => getStoredOrderInfo(), []);

  // Clear stored order info on cancel
  React.useEffect(() => {
    clearStoredOrderInfo();
  }, []);

  const handleRetry = React.useCallback(() => {
    router.push("/pricing");
  }, [router]);

  const handleReturnToPricing = React.useCallback(() => {
    router.push("/pricing");
  }, [router]);

  // Get package from stored info
  const pkg = storedInfo ? {
    id: storedInfo.packageId,
    name: "Selected Package",
    credits: 0,
    price: 0,
    bonus_percent: 0,
    is_popular: false,
  } : undefined;

  return (
    <div className="min-h-screen bg-slate-950 flex items-center justify-center p-4">
      <div className="max-w-md w-full">
        <PayPalCancel
          package={pkg}
          onRetry={handleRetry}
          onReturnToPricing={handleReturnToPricing}
        />
      </div>
    </div>
  );
}
