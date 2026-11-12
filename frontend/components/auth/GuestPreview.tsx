"use client";

/**
 * GuestPreview
 *
 * A banner shown to anonymous visitors on pages that are normally behind a
 * login. The page itself still renders - layout, explanatory copy and
 * structure are all visible - so the whole site can be explored without an
 * account. Where the page would show personal data, this banner explains
 * what signing in would add.
 *
 * `useAuth()` is used rather than a prop so the banner disappears on its own
 * as soon as the visitor signs in.
 */

import * as React from "react";
import Link from "next/link";
import { Eye, X } from "lucide-react";

import { useAuth } from "@/lib/contexts/AuthContext";
import { cn } from "@/lib/utils";

interface GuestPreviewProps {
  /** What the visitor would see after signing in. */
  readonly feature?: string;
  /** Optional extra message rendered under the main line. */
  readonly children?: React.ReactNode;
  /** Allow the visitor to dismiss the banner for this session. */
  readonly dismissible?: boolean;
  className?: string;
}

const DISMISS_KEY = "seoindicator_guest_preview_dismissed";

export default function GuestPreview({
  feature = "your own data",
  children,
  dismissible = true,
  className,
}: GuestPreviewProps) {
  const { isAuthenticated, isLoading } = useAuth();
  const [dismissed, setDismissed] = React.useState(false);

  // Only the client knows whether the banner was dismissed earlier.
  React.useEffect(() => {
    try {
      setDismissed(window.sessionStorage.getItem(DISMISS_KEY) === "1");
    } catch {
      // sessionStorage can be unavailable in private mode - not worth failing over
    }
  }, []);

  const handleDismiss = React.useCallback(() => {
    setDismissed(true);
    try {
      window.sessionStorage.setItem(DISMISS_KEY, "1");
    } catch {
      // ignore
    }
  }, []);

  // Nothing to show for a signed-in visitor, or before we know the auth state.
  if (isLoading || isAuthenticated || dismissed) {
    return null;
  }

  return (
    <div
      role="status"
      data-testid="guest-preview"
      className={cn(
        "flex items-start gap-3 rounded-lg border border-emerald-500/30 bg-slate-900 px-4 py-3",
        className
      )}
    >
      <Eye className="mt-0.5 h-5 w-5 shrink-0 text-emerald-400" aria-hidden="true" />
      <div className="flex-1 text-sm">
        <p className="text-emerald-100">
          You are browsing in <strong>guest preview</strong>. Everything on this
          page is visible without an account, but signing in lets you keep{" "}
          {feature}.
        </p>
        {children ? <div className="mt-1 text-slate-300">{children}</div> : null}
        <p className="mt-2 flex flex-wrap items-center gap-2">
          <Link
            href="/register/"
            className="font-medium text-emerald-300 underline underline-offset-4 hover:text-emerald-200"
          >
            Create a free account
          </Link>
          <span className="text-slate-400">or</span>
          <Link
            href="/login/"
            className="font-medium text-emerald-300 underline underline-offset-4 hover:text-emerald-200"
          >
            sign in
          </Link>
        </p>
      </div>
      {dismissible ? (
        <button
          type="button"
          onClick={handleDismiss}
          aria-label="Dismiss guest preview notice"
          className="shrink-0 rounded p-1 text-slate-400 transition-colors hover:text-white"
        >
          <X className="h-4 w-4" aria-hidden="true" />
        </button>
      ) : null}
    </div>
  );
}
