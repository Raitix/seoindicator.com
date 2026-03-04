"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useUnreadCount } from "@/lib/api/notifications";
import { MobileNav, NavItem, UserInfo } from "./MobileNav";
import {
  LayoutDashboard,
  FolderKanban,
  Key,
  FileBarChart,
  Settings,
  Bell,
} from "lucide-react";

interface HeaderProps {
  userName?: string;
  userEmail?: string;
  userAvatar?: string;
  onLogout?: () => void;
}

// Navigation items configuration
const navItems: NavItem[] = [
  { label: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
  { label: "Keywords", href: "/keywords", icon: Key },
  { label: "Competitors", href: "/competitors", icon: FolderKanban },
  { label: "Reports", href: "/reports", icon: FileBarChart },
  { label: "Settings", href: "/settings", icon: Settings },
];

export default function Header({
  userName,
  userEmail,
  userAvatar,
  onLogout,
}: HeaderProps) {
  const pathname = usePathname();
  const router = useRouter();
  const { count: unreadCount, mutate: refreshCount } = useUnreadCount();
  const [showDropdown, setShowDropdown] = useState(false);

  // Prepare user info for mobile nav
  const user: UserInfo | null = userName
    ? {
        name: userName,
        email: userEmail || "",
        avatar: userAvatar,
      }
    : null;

  // Refresh unread count on navigation
  useEffect(() => {
    refreshCount();
  }, [pathname, refreshCount]);

  const handleNotificationClick = () => {
    setShowDropdown(false);
    router.push("/notifications");
  };

  const isActive = (path: string) => {
    if (path === "/dashboard") {
      return pathname?.startsWith("/dashboard") && !pathname?.startsWith("/keywords");
    }
    return pathname?.startsWith(path);
  };

  // Update nav items with badge for notifications
  const navItemsWithBadge = navItems.map((item) => {
    if (item.href === "/notifications" && unreadCount > 0) {
      return { ...item, badge: unreadCount };
    }
    return item;
  });

  return (
    <header className="sticky top-0 z-50 bg-slate-950/95 backdrop-blur border-b border-slate-800">
      <div className="container mx-auto px-4">
        <div className="flex items-center justify-between h-14">
          {/* Logo */}
          <Link
            href="/dashboard"
            className="flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 rounded"
            aria-label="SEOIndicator Home"
          >
            <span className="text-xl font-bold text-white">SEOIndicator</span>
          </Link>

          {/* Desktop Navigation */}
          <nav
            className="hidden md:flex items-center gap-6"
            aria-label="Main navigation"
            role="navigation"
          >
            <ul role="list" className="flex items-center gap-6">
              {navItems.map((item) => (
                <li key={item.href}>
                  <Link
                    href={item.href}
                    className={`text-sm transition-colors relative inline-flex items-center gap-1.5 px-1 py-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 rounded ${
                      isActive(item.href)
                        ? "text-white"
                        : "text-slate-400 hover:text-white"
                    }`}
                    aria-current={isActive(item.href) ? "page" : undefined}
                  >
                    {item.icon && (
                      <item.icon
                        className="w-4 h-4"
                        aria-hidden="true"
                      />
                    )}
                    {item.label}
                    {isActive(item.href) && (
                      <span className="absolute -bottom-1 left-0 right-0 h-0.5 bg-blue-500 rounded-full" aria-hidden="true" />
                    )}
                  </Link>
                </li>
              ))}
            </ul>
          </nav>

          {/* Right Side Actions */}
          <div className="flex items-center gap-2">
            {/* Notification Bell */}
            <div className="relative">
              <button
                onClick={handleNotificationClick}
                className="relative p-2 rounded-lg hover:bg-slate-800 transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
                aria-label={`Notifications${unreadCount > 0 ? ` (${unreadCount} unread)` : ""}`}
                aria-live="polite"
                aria-atomic="true"
              >
                <Bell className="w-5 h-5 text-slate-400" aria-hidden="true" />
                {unreadCount > 0 && (
                  <span className="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-red-500 rounded-full animate-pulse" aria-hidden="true">
                    {unreadCount > 99 ? "99+" : unreadCount}
                  </span>
                )}
              </button>
            </div>

            {/* Settings Link */}
            <Link
              href="/settings/alerts"
              className={`hidden md:flex p-2 rounded-lg hover:bg-slate-800 transition-colors min-h-[44px] min-w-[44px] items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 ${
                isActive("/settings") ? "text-white" : "text-slate-400"
              }`}
              aria-label="Settings"
              aria-current={isActive("/settings") ? "page" : undefined}
            >
              <Settings className="w-5 h-5" aria-hidden="true" />
            </Link>

            {/* User Menu (Desktop) */}
            {userName && (
              <div className="hidden md:flex items-center gap-2">
                <div
                  className="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-medium"
                  aria-hidden="true"
                >
                  {userName.charAt(0).toUpperCase()}
                </div>
                <span className="sr-only">{userName}</span>
                {onLogout && (
                  <button
                    onClick={onLogout}
                    className="text-sm text-slate-400 hover:text-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 rounded px-2 py-1"
                    aria-label="Logout"
                  >
                    Logout
                  </button>
                )}
              </div>
            )}

            {/* Mobile Navigation */}
            <MobileNav
              navItems={navItemsWithBadge}
              user={user}
              onLogout={onLogout}
            />
          </div>
        </div>
      </div>
    </header>
  );
}
