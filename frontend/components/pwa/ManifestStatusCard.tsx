"use client";

import * as React from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { CheckCircle, XCircle, AlertCircle, ExternalLink } from "lucide-react";
import { cn } from "@/lib/utils";
import type { ManifestFields } from "@/lib/types/pwa";

export interface ManifestStatusCardProps {
  hasManifest: boolean;
  manifestUrl?: string | null;
  manifestValid?: boolean;
  fields?: ManifestFields;
  className?: string;
}

/**
 * Required field names to display
 */
const REQUIRED_FIELDS: (keyof ManifestFields)[] = [
  "name",
  "short_name",
  "start_url",
  "display",
];

/**
 * Get field display name
 */
function getFieldDisplayName(field: keyof ManifestFields): string {
  switch (field) {
    case "short_name":
      return "Short Name";
    case "start_url":
      return "Start URL";
    default:
      return field.charAt(0).toUpperCase() + field.slice(1);
  }
}

export function ManifestStatusCard({
  hasManifest,
  manifestUrl,
  manifestValid,
  fields,
  className,
}: ManifestStatusCardProps) {
  // Determine validity status for display
  const getValidityStatus = () => {
    if (!hasManifest) {
      return { icon: XCircle, text: "No Manifest", color: "text-red-400", bgColor: "bg-red-500/10" };
    }
    if (manifestValid === true) {
      return { icon: CheckCircle, text: "Valid", color: "text-emerald-400", bgColor: "bg-emerald-500/10" };
    }
    if (manifestValid === false) {
      // Check if it's a warning (has manifest but issues) vs error
      const hasWarnings = fields && Object.values(fields).some(f => f?.present === false);
      if (hasWarnings) {
        return { icon: AlertCircle, text: "Warnings", color: "text-yellow-400", bgColor: "bg-yellow-500/10" };
      }
      return { icon: XCircle, text: "Invalid", color: "text-red-400", bgColor: "bg-red-500/10" };
    }
    return { icon: AlertCircle, text: "Unknown", color: "text-slate-400", bgColor: "bg-slate-500/10" };
  };
  
  const validityStatus = getValidityStatus();
  const ValidityIcon = validityStatus.icon;
  
  return (
    <Card
      className={cn("border-slate-800 bg-slate-900/60", className)}
      aria-label="Manifest Status"
    >
      <CardHeader className="pb-2">
        <CardTitle className="text-sm font-medium text-slate-300">
          Manifest Status
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Manifest Presence & Validity */}
        <div className="flex items-center gap-2">
          <ValidityIcon
            className={cn("h-5 w-5", validityStatus.color)}
            aria-hidden="true"
          />
          <span className={cn("text-sm font-medium", validityStatus.color)}>
            {validityStatus.text}
          </span>
        </div>
        
        {/* Manifest URL */}
        {hasManifest && manifestUrl && (
          <div className="space-y-1">
            <span className="text-xs text-slate-400">Manifest URL</span>
            <a
              href={manifestUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-1 text-sm text-blue-400 hover:text-blue-300 truncate"
              aria-label={`View manifest at ${manifestUrl}`}
            >
              <span className="truncate max-w-[200px]">{manifestUrl}</span>
              <ExternalLink className="h-3 w-3 flex-shrink-0" />
            </a>
          </div>
        )}
        
        {/* Field Presence Badges */}
        {hasManifest && (
          <div className="space-y-2">
            <span className="text-xs text-slate-400">Required Fields</span>
            <div className="flex flex-wrap gap-2">
              {REQUIRED_FIELDS.map((field) => {
                const fieldData = fields?.[field];
                const isPresent = fieldData?.present === true;
                
                return (
                  <span
                    key={field}
                    className={cn(
                      "inline-flex items-center px-2 py-1 text-xs rounded-md border",
                      isPresent
                        ? "bg-emerald-500/10 text-emerald-400 border-emerald-500/20"
                        : "bg-red-500/10 text-red-400 border-red-500/20"
                    )}
                    aria-label={`${getFieldDisplayName(field)}: ${isPresent ? "Present" : "Missing"}`}
                  >
                    {isPresent ? (
                      <CheckCircle className="h-3 w-3 mr-1" />
                    ) : (
                      <XCircle className="h-3 w-3 mr-1" />
                    )}
                    {getFieldDisplayName(field)}
                  </span>
                );
              })}
            </div>
          </div>
        )}
        
        {/* No Manifest CTA */}
        {!hasManifest && (
          <p className="text-sm text-slate-400">
            Add a web app manifest to enable PWA features.
          </p>
        )}
      </CardContent>
    </Card>
  );
}

export function ManifestStatusCardSkeleton() {
  return (
    <Card className="border-slate-800 bg-slate-900/60">
      <CardHeader className="pb-2">
        <div className="h-4 w-24 animate-pulse rounded bg-slate-700" />
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex items-center gap-2">
          <div className="h-5 w-5 animate-pulse rounded bg-slate-700" />
          <div className="h-4 w-16 animate-pulse rounded bg-slate-700" />
        </div>
        <div className="space-y-1">
          <div className="h-3 w-20 animate-pulse rounded bg-slate-700" />
          <div className="h-4 w-32 animate-pulse rounded bg-slate-700" />
        </div>
        <div className="space-y-2">
          <div className="h-3 w-24 animate-pulse rounded bg-slate-700" />
          <div className="flex gap-2">
            <div className="h-6 w-20 animate-pulse rounded bg-slate-700" />
            <div className="h-6 w-20 animate-pulse rounded bg-slate-700" />
            <div className="h-6 w-20 animate-pulse rounded bg-slate-700" />
            <div className="h-6 w-20 animate-pulse rounded bg-slate-700" />
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

export default ManifestStatusCard;
