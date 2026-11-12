"use client";

import * as React from "react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { SWRConfig } from "swr";
import { AuthProvider } from "@/lib/contexts/AuthContext";
import { TooltipProvider } from "@/components/ui/tooltip";
import { Toaster } from "sonner";
import { swrAuthOptions } from "@/lib/api/swr-auth-options";

// Create a client
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 60 * 1000, // 1 minute
      refetchOnWindowFocus: false,
    },
  },
});

export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <QueryClientProvider client={queryClient}>
      {/*
        A 401 is the expected answer when a guest asks for account data, so
        it must not be retried. SWR retries errors indefinitely by default,
        which would reissue the request forever and trip the 401 interceptor
        on every attempt. Setting the policy once here covers every hook.
      */}
      <SWRConfig value={swrAuthOptions}>
        <TooltipProvider>
          <AuthProvider>{children}</AuthProvider>
        </TooltipProvider>
      </SWRConfig>
      <Toaster position="top-right" richColors />
    </QueryClientProvider>
  );
}
