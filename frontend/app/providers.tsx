"use client";

import * as React from "react";
import { AuthProvider } from "@/lib/contexts/AuthContext";

export function Providers({ children }: { children: React.ReactNode }) {
  return <AuthProvider>{children}</AuthProvider>;
}
