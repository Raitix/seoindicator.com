"use client";

/**
 * PlatformTabs Component
 * TASK-16-8: Social Media Preview Tool
 *
 * Tab navigation for switching between Facebook, Twitter, and LinkedIn previews.
 */

import * as React from "react";
import { cn } from "@/lib/utils";

export type Platform = "facebook" | "twitter" | "linkedin";

interface PlatformTabsProps {
  activeTab: Platform;
  onTabChange: (tab: Platform) => void;
  className?: string;
}

const PLATFORM_CONFIG: Array<{ id: Platform; label: string; icon: string }> = [
  { id: "facebook", label: "Facebook", icon: "📘" },
  { id: "twitter", label: "Twitter", icon: "🐦" },
  { id: "linkedin", label: "LinkedIn", icon: "💼" },
];

export function PlatformTabs({
  activeTab,
  onTabChange,
  className,
}: PlatformTabsProps) {
  const tabRefs = React.useRef<(HTMLButtonElement | null)[]>([]);

  // Handle keyboard navigation
  const handleKeyDown = (e: React.KeyboardEvent, currentIndex: number) => {
    let newIndex = currentIndex;

    switch (e.key) {
      case "ArrowRight":
        newIndex = (currentIndex + 1) % PLATFORM_CONFIG.length;
        break;
      case "ArrowLeft":
        newIndex =
          (currentIndex - 1 + PLATFORM_CONFIG.length) % PLATFORM_CONFIG.length;
        break;
      case "Home":
        newIndex = 0;
        break;
      case "End":
        newIndex = PLATFORM_CONFIG.length - 1;
        break;
      default:
        return;
    }

    e.preventDefault();
    tabRefs.current[newIndex]?.focus();
    onTabChange(PLATFORM_CONFIG[newIndex].id);
  };

  return (
    <div
      role="tablist"
      data-testid="platform-tabs"
      aria-label="Social media platform previews"
      className={cn("flex border-b border-slate-700", className)}
    >
      {PLATFORM_CONFIG.map((platform, index) => {
        const isActive = activeTab === platform.id;
        return (
          <button
            key={platform.id}
            ref={(el) => {
              tabRefs.current[index] = el;
            }}
            role="tab"
            aria-selected={isActive}
            aria-controls={`${platform.id}-panel`}
            data-testid={`tab-${platform.id}`}
            onClick={() => onTabChange(platform.id)}
            onKeyDown={(e) => handleKeyDown(e, index)}
            tabIndex={isActive ? 0 : -1}
            className={cn(
              "flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors",
              "focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900",
              isActive
                ? "text-emerald-400 border-b-2 border-emerald-500"
                : "text-slate-400 hover:text-slate-300"
            )}
          >
            <span>{platform.icon}</span>
            <span>{platform.label}</span>
          </button>
        );
      })}
    </div>
  );
}

export default PlatformTabs;
