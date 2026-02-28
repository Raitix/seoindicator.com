"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useUnreadCount } from "@/lib/api/notifications";

interface HeaderProps {
  userName?: string;
  onLogout?: () => void;
}

export default function Header({ userName, onLogout }: HeaderProps) {
  const pathname = usePathname();
  const router = useRouter();
  const { count: unreadCount, mutate: refreshCount } = useUnreadCount();
  const [showDropdown, setShowDropdown] = useState(false);

  // Refresh unread count on navigation
  useEffect(() => {
    refreshCount();
  }, [pathname, refreshCount]);

  const handleNotificationClick = () => {
    setShowDropdown(false);
    router.push("/notifications");
  };

  const isActive = (path: string) => pathname?.startsWith(path);

  return (
    <header className="sticky top-0 z-50 bg-slate-950/95 backdrop-blur border-b border-slate-800">
      <div className="container mx-auto px-4">
        <div className="flex items-center justify-between h-14">
          {/* Logo */}
          <Link href="/dashboard" className="flex items-center gap-2">
            <span className="text-xl font-bold text-white">SEOIndicator</span>
          </Link>

          {/* Navigation */}
          <nav className="hidden md:flex items-center gap-6">
            <Link
              href="/dashboard"
              className={`text-sm transition-colors ${
                isActive("/dashboard") && !isActive("/keywords")
                  ? "text-white"
                  : "text-slate-400 hover:text-white"
              }`}
            >
              Dashboard
            </Link>
            <Link
              href="/keywords"
              className={`text-sm transition-colors ${
                isActive("/keywords")
                  ? "text-white"
                  : "text-slate-400 hover:text-white"
              }`}
            >
              Keywords
            </Link>
            <Link
              href="/competitors"
              className={`text-sm transition-colors ${
                isActive("/competitors")
                  ? "text-white"
                  : "text-slate-400 hover:text-white"
              }`}
            >
              Competitors
            </Link>
            <Link
              href="/tools"
              className={`text-sm transition-colors ${
                isActive("/tools")
                  ? "text-white"
                  : "text-slate-400 hover:text-white"
              }`}
            >
              Tools
            </Link>
          </nav>

          {/* Right Side Actions */}
          <div className="flex items-center gap-4">
            {/* Notification Bell */}
            <div className="relative">
              <button
                onClick={handleNotificationClick}
                className="relative p-2 rounded-lg hover:bg-slate-800 transition-colors"
                aria-label={`Notifications${unreadCount > 0 ? ` (${unreadCount} unread)` : ""}`}
              >
                <svg
                  className="w-5 h-5 text-slate-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                  />
                </svg>
                {unreadCount > 0 && (
                  <span className="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-red-500 rounded-full animate-pulse">
                    {unreadCount > 99 ? "99+" : unreadCount}
                  </span>
                )}
              </button>
            </div>

            {/* Settings Link */}
            <Link
              href="/settings/alerts"
              className={`p-2 rounded-lg hover:bg-slate-800 transition-colors ${
                isActive("/settings") ? "text-white" : "text-slate-400"
              }`}
              aria-label="Settings"
            >
              <svg
                className="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                />
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
            </Link>

            {/* User Menu */}
            {userName && (
              <div className="flex items-center gap-2">
                <div className="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-medium">
                  {userName.charAt(0).toUpperCase()}
                </div>
                {onLogout && (
                  <button
                    onClick={onLogout}
                    className="text-sm text-slate-400 hover:text-white transition-colors"
                  >
                    Logout
                  </button>
                )}
              </div>
            )}
          </div>
        </div>
      </div>
    </header>
  );
}
