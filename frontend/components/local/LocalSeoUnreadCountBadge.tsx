"use client";

import { Bell } from "lucide-react";

import { cn } from "@/lib/utils";
import type { LocalSeoUnreadCount } from "@/lib/types/location-alerts";

export interface LocalSeoUnreadCountBadgeProps {
  unreadCount: LocalSeoUnreadCount;
  className?: string;
  hideWhenZero?: boolean;
}

const resolveTone = (count: LocalSeoUnreadCount) => {
  if (count.bySeverity.critical > 0) {
    return "border-red-500/30 bg-red-500/10 text-red-100";
  }

  if (count.bySeverity.warning > 0) {
    return "border-amber-500/30 bg-amber-500/10 text-amber-100";
  }

  return "border-sky-500/30 bg-sky-500/10 text-sky-100";
};

export function LocalSeoUnreadCountBadge({
  unreadCount,
  className,
  hideWhenZero = true,
}: LocalSeoUnreadCountBadgeProps) {
  if (hideWhenZero && unreadCount.total <= 0) {
    return null;
  }

  return (
    <div
      role="status"
      aria-live="polite"
      className={cn(
        "inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium",
        resolveTone(unreadCount),
        className
      )}
      data-testid="local-seo-unread-badge"
    >
      <Bell className="h-4 w-4" aria-hidden="true" />
      <span>{unreadCount.total}</span>
      <span>unread alert{unreadCount.total === 1 ? "" : "s"}</span>
    </div>
  );
}

export default LocalSeoUnreadCountBadge;
