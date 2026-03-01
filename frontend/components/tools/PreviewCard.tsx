"use client";

/**
 * PreviewCard Component
 * TASK-16-8: Social Media Preview Tool
 *
 * Renders a social media share preview card for a specific platform.
 */

import * as React from "react";
import { cn } from "@/lib/utils";
import type { Platform } from "./PlatformTabs";

export interface PlatformPreview {
  title: string | null;
  description: string | null;
  image: string | null;
  domain?: string;
  card?: string;
}

interface PreviewCardProps {
  platform: Platform;
  data: PlatformPreview;
  className?: string;
}

/**
 * Get platform-specific styling classes
 */
function getPlatformStyles(platform: Platform): {
  cardClass: string;
  imageAspect: string;
  maxTitleLength: number;
  maxDescriptionLength: number;
} {
  switch (platform) {
    case "facebook":
      return {
        cardClass: "bg-white text-gray-900",
        imageAspect: "aspect-[1.91/1]",
        maxTitleLength: 100,
        maxDescriptionLength: 150,
      };
    case "twitter":
      return {
        cardClass: "bg-white text-gray-900",
        imageAspect: "aspect-[2/1]",
        maxTitleLength: 70,
        maxDescriptionLength: 100,
      };
    case "linkedin":
      return {
        cardClass: "bg-white text-gray-900",
        imageAspect: "aspect-[1.91/1]",
        maxTitleLength: 100,
        maxDescriptionLength: 150,
      };
  }
}

/**
 * Truncate text to specified length
 */
function truncate(text: string | null, maxLength: number): string {
  if (!text) return "";
  if (text.length <= maxLength) return text;
  return text.slice(0, maxLength - 3) + "...";
}

export function PreviewCard({ platform, data, className }: PreviewCardProps) {
  const styles = getPlatformStyles(platform);

  // Get title with fallback
  const title = data.title || "No title";
  const description = data.description || "No description";

  return (
    <div
      data-testid={`${platform}-preview-card`}
      className={cn(
        "rounded-lg overflow-hidden border border-slate-600 shadow-lg",
        styles.cardClass,
        className
      )}
    >
      {/* Image Section */}
      {data.image && (
        <div
          className={cn(
            "relative bg-gray-200 overflow-hidden",
            styles.imageAspect
          )}
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={data.image}
            alt={`${platform} preview`}
            data-testid="preview-image"
            className="w-full h-full object-cover"
            onError={(e) => {
              // Hide broken image
              e.currentTarget.style.display = "none";
            }}
          />
        </div>
      )}

      {/* Content Section */}
      <div className="p-4 bg-gray-100">
        {/* Domain */}
        {data.domain && (
          <span
            data-testid="preview-domain"
            className="text-xs text-gray-500 uppercase tracking-wide"
          >
            {data.domain}
          </span>
        )}

        {/* Title */}
        <h3
          data-testid="preview-title"
          className="font-semibold text-gray-900 mt-1 leading-tight"
          title={title}
        >
          {truncate(title, styles.maxTitleLength)}
        </h3>

        {/* Description */}
        <p
          data-testid="preview-description"
          className="text-sm text-gray-600 mt-1 leading-snug"
          title={description}
        >
          {truncate(description, styles.maxDescriptionLength)}
        </p>

        {/* Twitter card type indicator */}
        {platform === "twitter" && data.card && (
          <span className="inline-block mt-2 text-xs text-gray-400">
            Card type: {data.card}
          </span>
        )}
      </div>
    </div>
  );
}

export default PreviewCard;
