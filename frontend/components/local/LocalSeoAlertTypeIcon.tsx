"use client";

import {
  AlertTriangle,
  ArrowDown,
  ArrowUp,
  Link2Off,
  MessageSquare,
  Star,
  Users,
} from "lucide-react";

import { cn } from "@/lib/utils";
import { LOCAL_SEO_ALERT_TYPE_CONFIG, type LocalSeoAlertType } from "@/lib/types/location-alerts";

export interface LocalSeoAlertTypeIconProps {
  type: LocalSeoAlertType;
  className?: string;
}

export function LocalSeoAlertTypeIcon({ type, className }: LocalSeoAlertTypeIconProps) {
  const config = LOCAL_SEO_ALERT_TYPE_CONFIG[type];
  const sharedProps = {
    className: cn("h-4 w-4", config.colorClass, className),
    "aria-hidden": true,
  };

  const icon = (() => {
    switch (type) {
      case "ranking_improved":
        return <ArrowUp {...sharedProps} />;
      case "ranking_dropped":
        return <ArrowDown {...sharedProps} />;
      case "new_review":
        return <MessageSquare {...sharedProps} />;
      case "negative_review":
        return <AlertTriangle {...sharedProps} />;
      case "rating_dropped":
        return <Star {...sharedProps} />;
      case "competitor_overtake":
        return <Users {...sharedProps} />;
      case "gbp_disconnected":
        return <Link2Off {...sharedProps} />;
      default:
        return <AlertTriangle {...sharedProps} />;
    }
  })();

  return (
    <span
      className="inline-flex items-center gap-2"
      data-testid={`local-seo-alert-icon-${type}`}
      aria-label={`${config.label} alert`}
      title={config.iconLabel}
    >
      {icon}
      <span className="sr-only">{config.label}</span>
    </span>
  );
}

export default LocalSeoAlertTypeIcon;
