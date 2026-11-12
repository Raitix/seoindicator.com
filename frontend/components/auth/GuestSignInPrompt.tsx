"use client";

/**
 * Sign-in prompt for a guest-viewable page whose data request was refused.
 *
 * A guest-viewable page asks the API for data that belongs to an account and
 * gets a 401. That is the expected answer, not a failure - so the page shows
 * this prompt instead of a red error message.
 *
 * Use it wherever a 401 would otherwise render an error string, and pair it
 * with `useGuestMode()` so the branch is made on the same condition
 * everywhere.
 */

import * as React from "react";
import Link from "next/link";
import { Lock } from "lucide-react";

import { cn } from "@/lib/utils";

interface GuestSignInPromptProps {
  /** What the visitor would see after signing in. */
  readonly feature?: string;
  /** Heading text. */
  readonly title?: string;
  /** Extra explanation under the heading. */
  readonly description?: string;
  className?: string;
}

export default function GuestSignInPrompt({
  feature = "this data",
  title,
  description,
  className,
}: GuestSignInPromptProps) {
  return (
    <div
      className={cn(
        "flex flex-col items-center gap-4 rounded-xl border border-slate-700 bg-slate-900 px-6 py-10 text-center",
        className
      )}
      data-testid="guest-sign-in-prompt"
    >
      <Lock className="h-9 w-9 text-slate-400" aria-hidden="true" />
      <div className="space-y-1.5">
        <h2 className="text-lg font-semibold text-white">
          {title ?? "Sign in to see this"}
        </h2>
        <p className="max-w-md text-sm text-slate-400">
          {description ??
            `Sign in to see ${feature}. Everything else on this page is visible without an account.`}
        </p>
      </div>
      <Link
        href="/login"
        className="inline-flex h-10 items-center justify-center rounded-md bg-brand-primary px-5 text-sm font-semibold text-brand-primary-foreground transition-colors hover:bg-brand-primary/90"
      >
        Sign in
      </Link>
    </div>
  );
}
