"use client";

/**
 * WarningsList Component
 * TASK-16-8: Social Media Preview Tool
 *
 * Displays a list of warnings about social media tag issues.
 */

import * as React from "react";
import { cn } from "@/lib/utils";

export interface SocialWarning {
  field: string;
  message: string;
}

interface WarningsListProps {
  warnings: SocialWarning[];
  className?: string;
}

/**
 * Get severity based on warning message content
 */
function getSeverity(message: string): "warning" | "info" {
  const warningKeywords = ["should", "required", "missing", "invalid", "blocked"];
  const lowerMessage = message.toLowerCase();
  
  for (const keyword of warningKeywords) {
    if (lowerMessage.includes(keyword)) {
      return "warning";
    }
  }
  
  return "info";
}

export function WarningsList({ warnings, className }: WarningsListProps) {
  if (warnings.length === 0) return null;

  return (
    <div
      data-testid="warnings-list"
      className={cn(
        "bg-blue-500/10 border border-blue-500/20 rounded-lg p-4",
        className
      )}
    >
      <h4 className="text-sm font-medium text-blue-400 mb-3 flex items-center gap-2">
        <svg
          className="h-4 w-4"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        Recommendations ({warnings.length})
      </h4>
      <ul className="space-y-2">
        {warnings.map((warning, index) => {
          const severity = getSeverity(warning.message);
          return (
            <li
              key={index}
              data-testid={`warning-${index}`}
              className={cn(
                "flex items-start gap-2 text-sm",
                severity === "warning" ? "text-yellow-300" : "text-blue-300"
              )}
            >
              <span className={severity === "warning" ? "text-yellow-400" : "text-blue-400"}>
                {severity === "warning" ? "⚠" : "ℹ"}
              </span>
              <div>
                <code
                  className={cn(
                    "px-1.5 py-0.5 rounded text-xs",
                    severity === "warning"
                      ? "text-yellow-300 bg-yellow-500/10"
                      : "text-blue-300 bg-blue-500/10"
                  )}
                >
                  {warning.field}
                </code>
                <p className="text-slate-300 mt-1">{warning.message}</p>
              </div>
            </li>
          );
        })}
      </ul>
    </div>
  );
}

export default WarningsList;
