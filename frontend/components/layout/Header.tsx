"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useUnreadCount } from "@/lib/api/notifications";
import { useAIVisibilityUnreadCount } from "@/hooks/useAIVisibilityUnreadCount";
import { MobileNav, NavItem, UserInfo } from "./MobileNav";
import {
  LayoutDashboard,
  FolderKanban,
  Key,
  FileBarChart,
  Settings,
  Bell,
  User,
  LogOut,
  ChevronDown,
  Eye,
} from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

/**
 * Navigation item interface
 */
export interface HeaderNavItem {
  label: string;
  href: string;
  icon?: React.ComponentType<{ className?: string }>;
  badge?: string | number;
}

/**
 * User information for header
 */
export interface HeaderUser {
  name: string;
  email: string;
  avatar?: string;
}

/**
 * Header props interface
 */
export interface HeaderProps {
  userName?: string;
  userEmail?: string;
  userAvatar?: string;
  onLogout?: () => void;
}

// Navigation items configuration (protected - only for authenticated users)
const protectedNavItems: NavItem[] = [
  { label: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
  { label: "AI Visibility", href: "/ai-visibility", icon: Eye },
  { label: "Keywords", href: "/keywords", icon: Key },
  { label: "Competitors", href: "/competitors", icon: FolderKanban },
  { label: "Reports", href: "/reports", icon: FileBarChart },
];

// Navigation items for guests
const guestNavItems: NavItem[] = [
  { label: "Indicators", href: "/dashboard", icon: LayoutDashboard },
  { label: "Sign In", href: "/login", icon: LogOut },
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
  const { count: aiVisibilityUnreadCount, mutate: refreshAIVisibilityCount } = useAIVisibilityUnreadCount();

  // Determine if user is authenticated
  const isAuthenticated = !!userName;

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
    refreshAIVisibilityCount();
  }, [pathname, refreshCount, refreshAIVisibilityCount]);

  const handleNotificationClick = () => {
    router.push("/notifications");
  };

  const handleAIVisibilityAlertsClick = () => {
    router.push("/ai-visibility/alerts");
  };

  const isActive = (path: string) => {
    if (path === "/dashboard") {
      return pathname?.startsWith("/dashboard") && !pathname?.startsWith("/keywords") && !pathname?.startsWith("/ai-visibility");
    }
    return pathname?.startsWith(path);
  };

  // Select nav items based on authentication status
  const navItems = isAuthenticated ? protectedNavItems : guestNavItems;

  // Update nav items with badge for AI Visibility alerts (only for authenticated users)
  const navItemsWithBadge = navItems.map((item) => {
    if (item.href === "/ai-visibility" && aiVisibilityUnreadCount > 0) {
      return { ...item, badge: aiVisibilityUnreadCount };
    }
    if (item.href === "/notifications" && unreadCount > 0) {
      return { ...item, badge: unreadCount };
    }
    return item;
  });

  const handleLogout = () => {
    onLogout?.();
  };

  return (
    <header className="sticky top-0 z-50 bg-slate-950/95 backdrop-blur border-b border-slate-800">
      <div className="container mx-auto px-4">
        <div className="flex items-center justify-between h-14">
          {/* Logo */}
          <Link
            href={isAuthenticated ? "/dashboard" : "/"}
            className="flex items-center gap-2 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 rounded"
            aria-label="SEOIndicator Home"
          >
            <span className="text-xl font-bold text-white">SEOIndicator</span>
          </Link>

          {/* Desktop Navigation */}
          <nav
            className="hidden md:flex items-center gap-1"
            aria-label="Main navigation"
            role="navigation"
          >
            <ul role="list" className="flex items-center gap-1">
              {navItems.map((item) => (
                <li key={item.href}>
                  <Link
                    href={item.href}
                    className={`text-sm transition-all duration-200 relative inline-flex items-center gap-1.5 px-3 py-2 rounded-lg cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 ${
                      isActive(item.href)
                        ? "text-white bg-slate-800/50"
                        : "text-slate-400 hover:text-white hover:bg-slate-700"
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
                      <span className="absolute bottom-0 left-3 right-3 h-0.5 bg-blue-500 rounded-full" aria-hidden="true" />
                    )}
                  </Link>
                </li>
              ))}
            </ul>
          </nav>

          {/* Right Side Actions */}
          <div className="flex items-center gap-1">
            {/* AI Visibility Alerts Bell - Only for authenticated users */}
            {isAuthenticated && (
              <div className="relative">
                <button
                  onClick={handleAIVisibilityAlertsClick}
                  className="relative p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700 cursor-pointer transition-all duration-200 min-h-[44px] min-w-[44px] flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
                  aria-label={`AI Visibility Alerts${aiVisibilityUnreadCount > 0 ? ` (${aiVisibilityUnreadCount} unread)` : ""}`}
                  aria-live="polite"
                  aria-atomic="true"
                >
                  <Eye className="w-5 h-5" aria-hidden="true" />
                  {aiVisibilityUnreadCount > 0 && (
                    <span className="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-blue-500 rounded-full" aria-hidden="true">
                      {aiVisibilityUnreadCount > 99 ? "99+" : aiVisibilityUnreadCount}
                    </span>
                  )}
                </button>
              </div>
            )}

            {/* Notification Bell - Only for authenticated users */}
            {isAuthenticated && (
              <div className="relative">
                <button
                  onClick={handleNotificationClick}
                  className="relative p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700 cursor-pointer transition-all duration-200 min-h-[44px] min-w-[44px] flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
                  aria-label={`Notifications${unreadCount > 0 ? ` (${unreadCount} unread)` : ""}`}
                  aria-live="polite"
                  aria-atomic="true"
                >
                  <Bell className="w-5 h-5" aria-hidden="true" />
                  {unreadCount > 0 && (
                    <span className="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-red-500 rounded-full" aria-hidden="true">
                      {unreadCount > 99 ? "99+" : unreadCount}
                    </span>
                  )}
                </button>
              </div>
            )}

            {/* Settings Link - Desktop - Only for authenticated users */}
            {isAuthenticated && (
              <Link
                href="/settings"
                className={`hidden md:flex p-2 rounded-lg cursor-pointer transition-all duration-200 min-h-[44px] min-w-[44px] items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 ${
                  isActive("/settings") 
                    ? "text-white bg-slate-800/50" 
                    : "text-slate-400 hover:text-white hover:bg-slate-700"
                }`}
                aria-label="Settings"
                aria-current={isActive("/settings") ? "page" : undefined}
              >
                <Settings className="w-5 h-5" aria-hidden="true" />
              </Link>
            )}

            {/* User Menu Dropdown (Desktop) - Only for authenticated users */}
            {isAuthenticated && (
              <div className="hidden md:block">
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <button
                      className="flex items-center gap-2 p-1.5 pr-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700 cursor-pointer transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 min-h-[44px]"
                      aria-label="User menu"
                    >
                      {userAvatar ? (
                        <img
                          src={userAvatar}
                          alt=""
                          className="w-8 h-8 rounded-full object-cover"
                        />
                      ) : (
                        <div className="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-medium">
                          {userName.charAt(0).toUpperCase()}
                        </div>
                      )}
                      <ChevronDown className="w-4 h-4 text-slate-500" aria-hidden="true" />
                    </button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent
                    align="end"
                    sideOffset={8}
                    className="w-56 bg-slate-900 border-slate-700"
                  >
                    {/* User Info Header */}
                    <DropdownMenuLabel className="font-normal">
                      <div className="flex flex-col space-y-1">
                        <p className="text-sm font-medium text-white truncate">
                          {userName}
                        </p>
                        {userEmail && (
                          <p className="text-xs text-slate-400 truncate">
                            {userEmail}
                          </p>
                        )}
                      </div>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator className="bg-slate-800" />
                    
                    {/* Profile Link */}
                    <DropdownMenuItem
                      className="text-slate-300 focus:text-white cursor-pointer"
                      onClick={() => router.push("/profile")}
                    >
                      <User className="w-4 h-4 mr-2" aria-hidden="true" />
                      Profile
                    </DropdownMenuItem>
                    
                    {/* Settings Link */}
                    <DropdownMenuItem
                      className="text-slate-300 focus:text-white cursor-pointer"
                      onClick={() => router.push("/settings")}
                    >
                      <Settings className="w-4 h-4 mr-2" aria-hidden="true" />
                      Settings
                    </DropdownMenuItem>
                    
                    {onLogout && (
                      <>
                        <DropdownMenuSeparator className="bg-slate-800" />
                        <DropdownMenuItem
                          className="text-slate-300 focus:text-white cursor-pointer"
                          onClick={handleLogout}
                        >
                          <LogOut className="w-4 h-4 mr-2" aria-hidden="true" />
                          Sign out
                        </DropdownMenuItem>
                      </>
                    )}
                  </DropdownMenuContent>
                </DropdownMenu>
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
