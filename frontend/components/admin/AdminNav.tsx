"use client";

import * as React from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { cn } from "@/lib/utils";

// ==================== Types ====================

export interface NavItem {
  id: string;
  label: string;
  href: string;
  icon: React.ReactNode;
}

export interface AdminNavProps {
  activePath?: string;
  collapsed?: boolean;
  onToggle?: () => void;
  className?: string;
}

// ==================== Navigation Items ====================

const NAV_ITEMS: NavItem[] = [
  {
    id: "overview",
    label: "Overview",
    href: "/admin/overview",
    icon: (
      <svg
        className="h-5 w-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
        />
      </svg>
    ),
  },
  {
    id: "users",
    label: "Users",
    href: "/admin/users",
    icon: (
      <svg
        className="h-5 w-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
        />
      </svg>
    ),
  },
  {
    id: "usage",
    label: "Usage",
    href: "/admin/usage",
    icon: (
      <svg
        className="h-5 w-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
        />
      </svg>
    ),
  },
  {
    id: "credits",
    label: "Credits/Billing",
    href: "/admin/credits",
    icon: (
      <svg
        className="h-5 w-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
        />
      </svg>
    ),
  },
  {
    id: "health",
    label: "System Health",
    href: "/admin/health",
    icon: (
      <svg
        className="h-5 w-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
        />
      </svg>
    ),
  },
];

// ==================== Component ====================

export function AdminNav({
  activePath: activePathProp,
  collapsed = false,
  onToggle,
  className,
}: AdminNavProps) {
  const pathname = usePathname();
  const router = useRouter();
  const activePath = activePathProp || pathname;

  const isActive = React.useCallback(
    (href: string) => {
      return activePath === href || activePath.startsWith(href + "/");
    },
    [activePath]
  );

  const handleNavClick = React.useCallback(
    (e: React.MouseEvent<HTMLAnchorElement>, href: string) => {
      e.preventDefault();
      router.push(href);
    },
    [router]
  );

  return (
    <nav
      data-testid="admin-nav"
      className={cn("flex flex-col h-full", className)}
      role="navigation"
      aria-label="Admin navigation"
    >
      {/* Toggle Button */}
      {onToggle && (
        <button
          data-testid="nav-toggle"
          onClick={onToggle}
          aria-expanded={!collapsed}
          aria-label={collapsed ? "Expand navigation" : "Collapse navigation"}
          className="mb-4 flex h-8 w-full items-center justify-end px-2 text-slate-400 hover:text-white"
        >
          {collapsed ? (
            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 5l7 7-7 7M5 5l7 7-7 7" />
            </svg>
          ) : (
            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
          )}
        </button>
      )}

      {/* Navigation Items */}
      <ul data-testid="nav-list" role="list" className="flex-1 space-y-1">
        {NAV_ITEMS.map((item) => (
          <li key={item.id} data-testid={`nav-item-${item.id}`}>
            <Link
              href={item.href}
              data-testid={`nav-link-${item.id}`}
              data-active={isActive(item.href)}
              onClick={(e) => handleNavClick(e, item.href)}
              className={cn(
                "flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors",
                isActive(item.href)
                  ? "bg-blue-600 text-white"
                  : "text-slate-300 hover:bg-slate-800 hover:text-white"
              )}
              aria-current={isActive(item.href) ? "page" : undefined}
            >
              <span
                data-testid={`nav-icon-${item.id}`}
                className="nav-icon flex-shrink-0"
              >
                {item.icon}
              </span>
              {!collapsed && (
                <span
                  data-testid={`nav-label-${item.id}`}
                  className="nav-label truncate"
                >
                  {item.label}
                </span>
              )}
            </Link>
          </li>
        ))}
      </ul>

      {/* Back to Dashboard Link */}
      <div data-testid="nav-footer" className="mt-auto border-t border-slate-700 pt-4">
        <Link
          href="/dashboard"
          data-testid="back-to-dashboard"
          onClick={(e) => {
            e.preventDefault();
            router.push("/dashboard");
          }}
          className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white"
        >
          <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          {!collapsed && <span>Back to Dashboard</span>}
        </Link>
      </div>
    </nav>
  );
}

// ==================== Exports ====================

export default AdminNav;
