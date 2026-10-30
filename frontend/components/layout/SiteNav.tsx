/**
 * Site Navigation Component
 * 
 * Task: TASK-36-14 - Navigation & Dashboard Integration
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 * 
 * Navigation sidebar for site-specific pages with Content Decay integration.
 */

"use client";

import * as React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  LayoutDashboard,
  Key,
  Search,
  TrendingDown,
  Sparkles,
  Calendar,
  MapPin,
  Settings,
  ChevronLeft,
  ChevronRight,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";

// ============================================================
// Types
// ============================================================

export interface SiteNavItem {
  label: string;
  href: string;
  icon: React.ComponentType<{ className?: string }>;
  badge?: number;
  badgeSeverity?: "critical" | "high" | "medium" | "low";
  ariaLabel?: string;
}

export interface SiteNavProps {
  /** Site ID for navigation */
  siteId: string;
  /** Content decay critical count for badge */
  decayCriticalCount?: number;
  /** Whether nav is collapsed */
  isCollapsed?: boolean;
  /** Callback when collapse state changes */
  onCollapsedChange?: (collapsed: boolean) => void;
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Navigation Items Configuration
// ============================================================

function getNavItems(siteId: string, decayCriticalCount?: number): SiteNavItem[] {
  return [
    {
      label: "Overview",
      href: `/sites/${siteId}`,
      icon: LayoutDashboard,
      ariaLabel: "Site overview",
    },
    {
      label: "Keywords",
      href: `/sites/${siteId}/keywords`,
      icon: Key,
      ariaLabel: "Keyword rankings",
    },
    {
      label: "Audits",
      href: `/sites/${siteId}/audits`,
      icon: Search,
      ariaLabel: "Site audits",
    },
    {
      label: "Content Decay",
      href: `/sites/${siteId}/content-decay`,
      icon: TrendingDown,
      badge: decayCriticalCount && decayCriticalCount > 0 ? decayCriticalCount : undefined,
      badgeSeverity: "critical",
      ariaLabel: decayCriticalCount && decayCriticalCount > 0 
        ? `Content decay, ${decayCriticalCount} critical issues` 
        : "Content decay",
    },
    {
      label: "AI Visibility",
      href: `/sites/${siteId}/ai-visibility`,
      icon: Sparkles,
      ariaLabel: "AI visibility tracking",
    },
    {
      label: "Annotations",
      href: `/sites/${siteId}/annotations`,
      icon: Calendar,
      ariaLabel: "Site annotations",
    },
    {
      label: "Local SEO",
      href: `/sites/${siteId}/local`,
      icon: MapPin,
      ariaLabel: "Local SEO",
    },
    {
      label: "Settings",
      href: `/sites/${siteId}/settings`,
      icon: Settings,
      ariaLabel: "Site settings",
    },
  ];
}

// ============================================================
// Badge Component
// ============================================================

interface NavBadgeProps {
  count: number;
  severity?: "critical" | "high" | "medium" | "low";
}

const BADGE_COLORS = {
  critical: "bg-red-500 text-white",
  high: "bg-orange-500 text-white",
  medium: "bg-yellow-500 text-black",
  low: "bg-slate-500 text-white",
};

function NavBadge({ count, severity = "critical" }: NavBadgeProps) {
  if (count <= 0) return null;

  return (
    <span
      className={cn(
        "ml-auto text-xs font-bold px-1.5 py-0.5 rounded-full min-w-[20px] text-center",
        BADGE_COLORS[severity]
      )}
      aria-hidden="true"
    >
      {count > 99 ? "99+" : count}
    </span>
  );
}

// ============================================================
// Navigation Item Component
// ============================================================

interface NavItemProps {
  item: SiteNavItem;
  isActive: boolean;
  isCollapsed: boolean;
}

function NavItem({ item, isActive, isCollapsed }: NavItemProps) {
  const { icon: Icon, label, href, badge, badgeSeverity, ariaLabel } = item;

  const linkContent = (
    <Link
      href={href}
      data-testid={`nav-link-${label.toLowerCase().replace(/\s+/g, "-")}`}
      aria-label={ariaLabel}
      aria-current={isActive ? "page" : undefined}
      className={cn(
        "flex items-center gap-3 px-3 py-2 rounded-lg transition-colors",
        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900",
        isActive
          ? "bg-slate-800 text-white"
          : "text-slate-400 hover:text-white hover:bg-slate-800/50"
      )}
    >
      <Icon className="h-5 w-5 flex-shrink-0" aria-hidden="true" />
      {!isCollapsed && (
        <>
          <span className="flex-1 truncate">{label}</span>
          {badge !== undefined && <NavBadge count={badge} severity={badgeSeverity} />}
        </>
      )}
      {isCollapsed && badge !== undefined && badge > 0 && (
        <span className="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full" />
      )}
    </Link>
  );

  if (isCollapsed) {
    return (
      <Tooltip delayDuration={0}>
        <TooltipTrigger asChild>
          <div className="relative">
            {linkContent}
            {badge !== undefined && badge > 0 && (
              <span
                className={cn(
                  "absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold rounded-full",
                  BADGE_COLORS[badgeSeverity || "critical"]
                )}
              >
                {badge > 9 ? "9+" : badge}
              </span>
            )}
          </div>
        </TooltipTrigger>
        <TooltipContent side="right" className="bg-slate-800 border-slate-700">
          <p className="text-white">{label}</p>
          {badge !== undefined && badge > 0 && (
            <p className="text-xs text-slate-400">{badge} issues</p>
          )}
        </TooltipContent>
      </Tooltip>
    );
  }

  return linkContent;
}

// ============================================================
// Main Component
// ============================================================

export function SiteNav({
  siteId,
  decayCriticalCount,
  isCollapsed = false,
  onCollapsedChange,
  className,
}: SiteNavProps) {
  const pathname = usePathname();
  const navItems = getNavItems(siteId, decayCriticalCount);

  const isActive = (href: string) => {
    // Exact match for overview, prefix match for others
    if (href === `/sites/${siteId}`) {
      return pathname === href;
    }
    return pathname?.startsWith(href);
  };

  return (
    <TooltipProvider>
      <nav
        data-testid="site-nav"
        role="navigation"
        aria-label="Site navigation"
        className={cn(
          "flex flex-col h-full bg-slate-900/50 border-r border-slate-800 transition-all duration-300",
          isCollapsed ? "w-16" : "w-56",
          className
        )}
      >
        {/* Navigation Items */}
        <ul role="list" className="flex-1 space-y-1 p-2">
          {navItems.map((item) => (
            <li key={item.href} className="relative">
              <NavItem
                item={item}
                isActive={isActive(item.href)}
                isCollapsed={isCollapsed}
              />
            </li>
          ))}
        </ul>

        {/* Collapse Toggle */}
        <div className="border-t border-slate-800 p-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => onCollapsedChange?.(!isCollapsed)}
            className="w-full text-slate-400 hover:text-white hover:bg-slate-800"
            aria-label={isCollapsed ? "Expand navigation" : "Collapse navigation"}
          >
            {isCollapsed ? (
              <ChevronRight className="h-4 w-4" />
            ) : (
              <>
                <ChevronLeft className="h-4 w-4 mr-2" />
                <span>Collapse</span>
              </>
            )}
          </Button>
        </div>
      </nav>
    </TooltipProvider>
  );
}

export default SiteNav;
