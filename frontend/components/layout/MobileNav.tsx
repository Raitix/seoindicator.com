"use client";

import * as React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { Menu, X } from "lucide-react";
import { cn } from "@/lib/utils";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription,
  SheetTrigger,
  SheetClose,
} from "@/components/ui/sheet";

/**
 * Navigation item interface
 */
export interface NavItem {
  label: string;
  href: string;
  icon?: React.ComponentType<{ className?: string }>;
  badge?: string | number;
}

/**
 * User information interface
 */
export interface UserInfo {
  name: string;
  email: string;
  avatar?: string;
}

/**
 * MobileNav props interface
 */
export interface MobileNavProps {
  navItems: NavItem[];
  currentPath?: string;
  user?: UserInfo | null;
  onLogout?: () => void;
  className?: string;
}

/**
 * Mobile Navigation Component
 * 
 * A slide-in sheet navigation for mobile devices with:
 * - Hamburger menu trigger (visible on mobile only)
 * - Touch-friendly tap targets (min 44px)
 * - Close on route change
 * - Close on backdrop click
 * - Animated open/close transitions
 * - Accessible ARIA labels
 */
export function MobileNav({
  navItems,
  currentPath,
  user,
  onLogout,
  className,
}: MobileNavProps) {
  const pathname = usePathname();
  const activePath = currentPath ?? pathname;
  const [isOpen, setIsOpen] = React.useState(false);

  // Close sheet on route change
  React.useEffect(() => {
    setIsOpen(false);
  }, [pathname]);

  // Handle Escape key press
  React.useEffect(() => {
    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === "Escape" && isOpen) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener("keydown", handleEscape);
      // Lock body scroll when sheet is open
      document.body.style.overflow = "hidden";
    }

    return () => {
      document.removeEventListener("keydown", handleEscape);
      document.body.style.overflow = "";
    };
  }, [isOpen]);

  const isActive = (href: string) => {
    if (href === "/dashboard") {
      return activePath === "/dashboard" || (activePath?.startsWith("/dashboard") && !activePath.startsWith("/keywords"));
    }
    return activePath?.startsWith(href);
  };

  const handleNavItemClick = () => {
    setIsOpen(false);
  };

  const handleLogout = () => {
    setIsOpen(false);
    onLogout?.();
  };

  return (
    <div className={cn("md:hidden", className)}>
      <Sheet open={isOpen} onOpenChange={setIsOpen}>
        <SheetTrigger asChild>
          <button
            type="button"
            className="inline-flex items-center justify-center w-11 h-11 rounded-lg hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-950"
            aria-label="Open navigation menu"
            aria-expanded={isOpen}
            aria-controls="mobile-nav-sheet"
          >
            <Menu className="w-6 h-6 text-slate-400" aria-hidden="true" />
          </button>
        </SheetTrigger>

        <SheetContent
          id="mobile-nav-sheet"
          side="left"
          className="w-[280px] sm:w-[320px] bg-slate-950 border-r border-slate-800 p-0"
          showCloseButton={false}
        >
          <SheetHeader className="p-4 border-b border-slate-800">
            <div className="flex items-center justify-between">
              <SheetTitle className="text-lg font-bold text-white">
                SEOIndicator
              </SheetTitle>
              <SheetClose asChild>
                <button
                  type="button"
                  className="inline-flex items-center justify-center w-11 h-11 rounded-lg hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                  aria-label="Close navigation menu"
                >
                  <X className="w-5 h-5 text-slate-400" aria-hidden="true" />
                </button>
              </SheetClose>
            </div>
            <SheetDescription className="sr-only">
              Main navigation menu
            </SheetDescription>
          </SheetHeader>

          <nav className="flex-1 overflow-y-auto py-2" role="navigation" aria-label="Main navigation">
            <ul className="space-y-1 px-2">
              {navItems.map((item) => {
                const Icon = item.icon;
                const active = isActive(item.href);

                return (
                  <li key={item.href}>
                    <SheetClose asChild>
                      <Link
                        href={item.href}
                        onClick={handleNavItemClick}
                        className={cn(
                          "flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors min-h-[44px]",
                          active
                            ? "bg-blue-600/10 text-blue-400 border-l-2 border-blue-500"
                            : "text-slate-400 hover:text-white hover:bg-slate-800"
                        )}
                        aria-current={active ? "page" : undefined}
                      >
                        {Icon && (
                          <Icon
                            className={cn(
                              "w-5 h-5 flex-shrink-0",
                              active ? "text-blue-400" : "text-slate-500"
                            )}
                            aria-hidden="true"
                          />
                        )}
                        <span className="flex-1">{item.label}</span>
                        {item.badge !== undefined && item.badge !== 0 && (
                          <span className="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-red-500 rounded-full">
                            {typeof item.badge === "number" && item.badge > 99 ? "99+" : item.badge}
                          </span>
                        )}
                      </Link>
                    </SheetClose>
                  </li>
                );
              })}
            </ul>
          </nav>

          {/* User section in footer */}
          {user && (
            <div className="border-t border-slate-800 p-4">
              <div className="flex items-center gap-3 mb-3">
                {user.avatar ? (
                  <img
                    src={user.avatar}
                    alt=""
                    className="w-10 h-10 rounded-full object-cover"
                  />
                ) : (
                  <div className="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-medium">
                    {user.name.charAt(0).toUpperCase()}
                  </div>
                )}
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-white truncate">
                    {user.name}
                  </p>
                  <p className="text-xs text-slate-500 truncate">{user.email}</p>
                </div>
              </div>
              {onLogout && (
                <button
                  onClick={handleLogout}
                  className="w-full px-3 py-2.5 text-sm text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors min-h-[44px] text-left"
                >
                  Sign out
                </button>
              )}
            </div>
          )}
        </SheetContent>
      </Sheet>
    </div>
  );
}

export default MobileNav;
