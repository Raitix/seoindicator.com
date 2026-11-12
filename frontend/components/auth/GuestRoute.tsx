"use client";

/**
 * GuestRoute
 *
 * Wraps a page that normally sits behind a login. For a signed-in visitor it
 * renders the page unchanged. For an anonymous visitor it renders the page
 * content plus a banner explaining that the data belongs to an account, and
 * - for pages whose body is entirely personal data - a short guest view
 * instead of a loading spinner that would never resolve.
 *
 * This is what lets the whole site be explored without signing in.
 */

import * as React from "react";

import { useAuth } from "@/lib/contexts/AuthContext";
import GuestPreview from "@/components/auth/GuestPreview";
import { cn } from "@/lib/utils";

interface GuestRouteProps {
  /** What the visitor would see after signing in. */
  readonly feature: string;
  /**
   * Rendered instead of `children` for an anonymous visitor. Use this when
   * the page body would otherwise show an endless loading state.
   */
  readonly guestView?: React.ReactNode;
  /** Optional extra message under the banner. */
  readonly children?: React.ReactNode;
  className?: string;
}

export default function GuestRoute({
  feature,
  guestView,
  children,
  className,
}: GuestRouteProps) {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    // Nothing is known yet; render nothing rather than flashing the guest
    // banner at a visitor who turns out to be signed in.
    return null;
  }

  if (isAuthenticated) {
    return <>{children}</>;
  }

  return (
    <div className={cn("space-y-4", className)}>
      <GuestPreview feature={feature} />
      {guestView}
    </div>
  );
}
