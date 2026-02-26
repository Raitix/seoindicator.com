"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import type { DensityStatus } from "@/lib/utils/keywordDensityAnalyzer";

export interface WarningData {
  id?: string;
  type: "error" | "warning" | "info" | "success";
  message: string;
  recommendation?: string;
  dismissible?: boolean;
}

interface KeywordDensityWarningProps {
  warning: WarningData;
  onDismiss?: (id: string) => void;
  className?: string;
}

export function KeywordDensityWarning({
  warning,
  onDismiss,
  className,
}: KeywordDensityWarningProps) {
  const [dismissed, setDismissed] = React.useState(false);

  if (dismissed) return null;

  const handleDismiss = () => {
    setDismissed(true);
    if (onDismiss && warning.id) {
      onDismiss(warning.id);
    }
  };

  const getIcon = () => {
    switch (warning.type) {
      case "error":
        return "❌";
      case "warning":
        return "⚠️";
      case "info":
        return "ℹ️";
      case "success":
        return "✅";
      default:
        return "ℹ️";
    }
  };

  const getStyles = () => {
    switch (warning.type) {
      case "error":
        return "bg-red-500/10 border-red-500/30 text-red-400";
      case "warning":
        return "bg-yellow-500/10 border-yellow-500/30 text-yellow-400";
      case "info":
        return "bg-blue-500/10 border-blue-500/30 text-blue-400";
      case "success":
        return "bg-green-500/10 border-green-500/30 text-green-400";
      default:
        return "bg-slate-500/10 border-slate-500/30 text-slate-400";
    }
  };

  return (
    <div
      className={cn(
        "flex items-start gap-3 p-4 rounded-lg border",
        getStyles(),
        className
      )}
      role="alert"
    >
      <span className="text-lg mt-0.5">{getIcon()}</span>
      <div className="flex-1">
        <p className="font-medium">{warning.message}</p>
        {warning.recommendation && (
          <p className="text-sm opacity-80 mt-1">{warning.recommendation}</p>
        )}
      </div>
      {warning.dismissible && onDismiss && warning.id && (
        <button
          onClick={handleDismiss}
          className="text-current opacity-60 hover:opacity-100 transition-opacity"
          aria-label="Dismiss warning"
        >
          ✕
        </button>
      )}
    </div>
  );
}

interface KeywordDensityWarningListProps {
  warnings: WarningData[];
  onDismiss?: (id: string) => void;
  className?: string;
}

export function KeywordDensityWarningList({
  warnings,
  onDismiss,
  className,
}: KeywordDensityWarningListProps) {
  if (warnings.length === 0) return null;

  return (
    <div className={cn("space-y-3", className)}>
      {warnings.map((warning, index) => (
        <KeywordDensityWarning
          key={warning.id || index}
          warning={warning}
          onDismiss={onDismiss}
        />
      ))}
    </div>
  );
}

interface DensityStatusBadgeProps {
  status: DensityStatus;
  density: number;
  className?: string;
}

export function DensityStatusBadge({
  status,
  density,
  className,
}: DensityStatusBadgeProps) {
  const getStyles = () => {
    switch (status) {
      case "good":
        return "bg-green-500/20 text-green-400 border-green-500/30";
      case "low":
        return "bg-yellow-500/20 text-yellow-400 border-yellow-500/30";
      case "over-optimized":
        return "bg-red-500/20 text-red-400 border-red-500/30";
      default:
        return "bg-slate-500/20 text-slate-400 border-slate-500/30";
    }
  };

  const getLabel = () => {
    switch (status) {
      case "good":
        return "Good";
      case "low":
        return "Too low";
      case "over-optimized":
        return "Over-optimized";
      default:
        return "Unknown";
    }
  };

  return (
    <span
      className={cn(
        "inline-flex items-center gap-2 px-3 py-1 rounded-full border text-sm font-medium",
        getStyles(),
        className
      )}
    >
      <span>{density.toFixed(1)}%</span>
      <span className="opacity-60">•</span>
      <span>{getLabel()}</span>
    </span>
  );
}

interface HeadingKeywordIndicatorProps {
  found: boolean;
  className?: string;
}

export function HeadingKeywordIndicator({
  found,
  className,
}: HeadingKeywordIndicatorProps) {
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1 text-sm",
        found ? "text-green-400" : "text-yellow-400",
        className
      )}
      data-testid={found ? "keyword-found" : "keyword-missing"}
    >
      {found ? "✓ Keyword found" : "⚠ Not found"}
    </span>
  );
}

export default KeywordDensityWarning;
