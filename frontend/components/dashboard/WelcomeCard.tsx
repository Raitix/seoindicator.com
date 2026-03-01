"use client";

import * as React from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Sun, Moon, CloudSun } from "lucide-react";
import type { DashboardUser } from "@/lib/types/dashboard";

export interface WelcomeCardProps {
  user: DashboardUser | null;
  lastLoginAt?: string | null;
}

function getGreeting(): { text: string; icon: React.ReactNode } {
  const hour = new Date().getHours();

  if (hour >= 5 && hour < 12) {
    return {
      text: "Good morning",
      icon: <Sun className="h-5 w-5 text-amber-400" />,
    };
  } else if (hour >= 12 && hour < 17) {
    return {
      text: "Good afternoon",
      icon: <CloudSun className="h-5 w-5 text-sky-400" />,
    };
  } else {
    return {
      text: "Good evening",
      icon: <Moon className="h-5 w-5 text-indigo-400" />,
    };
  }
}

function formatLastLogin(isoString: string): string {
  const date = new Date(isoString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

  if (diffDays === 0) {
    return "Today";
  } else if (diffDays === 1) {
    return "Yesterday";
  } else if (diffDays < 7) {
    return `${diffDays} days ago`;
  } else {
    return date.toLocaleDateString();
  }
}

export function WelcomeCard({ user, lastLoginAt }: WelcomeCardProps) {
  const greeting = getGreeting();

  // Escape HTML entities in user name to prevent XSS
  const sanitizeName = (name: string): string => {
    return name
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  };

  return (
    <Card className="border-slate-800 bg-slate-900/60">
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div className="space-y-1">
            <div className="flex items-center gap-2">
              {greeting.icon}
              <p className="text-sm text-slate-400">{greeting.text}</p>
            </div>
            <h2 className="text-2xl font-bold text-white">
              Welcome, {user?.name || "User"}
            </h2>
            {lastLoginAt && (
              <p className="text-xs text-slate-500">
                Last login: {formatLastLogin(lastLoginAt)}
              </p>
            )}
          </div>
          {user?.avatar && (
            <div className="h-12 w-12 rounded-full overflow-hidden bg-slate-700">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src={user.avatar}
                alt={`${user.name}'s avatar`}
                className="h-full w-full object-cover"
              />
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
