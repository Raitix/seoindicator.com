"use client";

/**
 * MissingTagsList Component
 * TASK-16-8: Social Media Preview Tool
 *
 * Displays a list of missing social media meta tags.
 */

import * as React from "react";
import { cn } from "@/lib/utils";

interface MissingTagsListProps {
  missing: string[];
  className?: string;
}

/**
 * Get description for a missing tag
 */
function getTagDescription(tag: string): string {
  const descriptions: Record<string, string> = {
    "og:title": "The title shown when shared on Facebook/LinkedIn",
    "og:description": "The description shown when shared on Facebook/LinkedIn",
    "og:image": "The preview image for Facebook/LinkedIn shares",
    "og:url": "The canonical URL for the page",
    "og:image:width": "Image width helps social platforms render correctly",
    "og:image:height": "Image height helps social platforms render correctly",
    "og:site_name": "The name of your site for branding",
    "twitter:card": "Defines the card type (summary, summary_large_image, etc.)",
    "twitter:title": "The title shown in Twitter cards",
    "twitter:description": "The description shown in Twitter cards",
    "twitter:site": "Your Twitter @username for attribution",
    "twitter:creator": "Content author's Twitter @username",
    "twitter:image": "The preview image for Twitter cards",
  };

  return descriptions[tag] || "Recommended for better social sharing";
}

export function MissingTagsList({ missing, className }: MissingTagsListProps) {
  if (missing.length === 0) return null;

  return (
    <div
      data-testid="missing-tags"
      className={cn(
        "bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-4",
        className
      )}
    >
      <h4 className="text-sm font-medium text-yellow-400 mb-3 flex items-center gap-2">
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
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        Missing Tags ({missing.length})
      </h4>
      <ul className="space-y-2">
        {missing.map((tag) => (
          <li
            key={tag}
            data-testid={`missing-${tag}`}
            className="flex items-start gap-2 text-sm text-slate-300"
          >
            <span className="text-yellow-400 mt-0.5">•</span>
            <div>
              <code className="text-yellow-300 bg-yellow-500/10 px-1.5 py-0.5 rounded text-xs">
                {tag}
              </code>
              <p className="text-xs text-slate-400 mt-1">
                {getTagDescription(tag)}
              </p>
            </div>
          </li>
        ))}
      </ul>
    </div>
  );
}

export default MissingTagsList;
