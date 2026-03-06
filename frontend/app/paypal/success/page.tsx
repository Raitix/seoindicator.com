"use client";

import * as React from "react";
import { Suspense } from "react";
import PayPalSuccessClient from "./PayPalSuccessClient";

function LoadingState() {
  return (
    <div className="min-h-screen bg-slate-950 flex items-center justify-center p-4">
      <div className="text-center">
        <div className="w-12 h-12 text-emerald-500 animate-spin mx-auto mb-4">Loading...</div>
        <p className="text-white text-lg font-medium">Loading...</p>
      </div>
    </div>
  );
}

export default function PayPalSuccessPage() {
  return (
    <Suspense fallback={<LoadingState />}>
      <PayPalSuccessClient />
    </Suspense>
  );
}
