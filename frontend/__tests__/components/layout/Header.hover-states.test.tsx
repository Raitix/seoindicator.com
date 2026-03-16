/**
 * Header Hover States Tests - TASK-37-2
 * Tests for hover states, cursor styles, and visual feedback on navigation
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, it, expect, jest, beforeEach } from '@jest/globals';

// Mock Next.js navigation
const mockPathname = jest.fn(() => '/');

jest.mock('next/navigation', () => ({
  usePathname: () => mockPathname(),
  useRouter: () => ({
    push: jest.fn(),
    refresh: jest.fn(),
    back: jest.fn(),
    forward: jest.fn(),
  }),
}));

// Mock Next.js Link
jest.mock('next/link', () => {
  const MockLink = ({ children, href, className, ...props }: { 
    children: React.ReactNode; 
    href: string; 
    className?: string;
  }) => (
    <a href={href} className={className} {...props}>
      {children}
    </a>
  );
  MockLink.displayName = 'MockLink';
  return MockLink;
});

// Mock notification hooks
jest.mock('@/lib/api/notifications', () => ({
  useUnreadCount: () => ({ count: 0, mutate: jest.fn() }),
}));

jest.mock('@/hooks/useAIVisibilityUnreadCount', () => ({
  useAIVisibilityUnreadCount: () => ({ count: 0, mutate: jest.fn() }),
}));

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  LayoutDashboard: () => <span data-testid="dashboard-icon">Dashboard</span>,
  FolderKanban: () => <span data-testid="competitors-icon">Competitors</span>,
  Key: () => <span data-testid="keywords-icon">Keywords</span>,
  FileBarChart: () => <span data-testid="reports-icon">Reports</span>,
  Settings: () => <span data-testid="settings-icon">Settings</span>,
  Bell: () => <span data-testid="bell-icon">Bell</span>,
  User: () => <span data-testid="user-icon">User</span>,
  LogOut: () => <span data-testid="logout-icon">Logout</span>,
  ChevronDown: () => <span data-testid="chevron-icon">Chevron</span>,
  Eye: () => <span data-testid="eye-icon">Eye</span>,
}));

// Mock dropdown menu
jest.mock('@/components/ui/dropdown-menu', () => ({
  DropdownMenu: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  DropdownMenuTrigger: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  DropdownMenuContent: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  DropdownMenuItem: ({ children, onClick }: { children: React.ReactNode; onClick?: () => void }) => (
    <button onClick={onClick}>{children}</button>
  ),
  DropdownMenuLabel: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  DropdownMenuSeparator: () => <hr />,
}));

// Mock MobileNav component
jest.mock('./MobileNav', () => ({
  MobileNav: () => <div data-testid="mobile-nav">Mobile Nav</div>,
  NavItem: {},
  UserInfo: {},
}));

// Import Header after mocks
import Header from '@/components/layout/Header';

describe('Header Hover States - TASK-37-2', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockPathname.mockReturnValue('/');
  });

  // ============================================================
  // HOV-001: Pointer Cursor on Navigation
  // ============================================================
  describe('HOV-001: Pointer Cursor', () => {
    it('HOV-001a: should have cursor-pointer class on Dashboard link', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink.className).toMatch(/cursor-pointer/);
    });

    it('HOV-001b: should have cursor-pointer class on Keywords link', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const keywordsLink = screen.getByRole('link', { name: /keywords/i });
      expect(keywordsLink.className).toMatch(/cursor-pointer/);
    });

    it('HOV-001c: should have cursor-pointer class on Competitors link', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const competitorsLink = screen.getByRole('link', { name: /competitors/i });
      expect(competitorsLink.className).toMatch(/cursor-pointer/);
    });

    it('HOV-001d: should have cursor-pointer class on Reports link', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const reportsLink = screen.getByRole('link', { name: /reports/i });
      expect(reportsLink.className).toMatch(/cursor-pointer/);
    });

    it('HOV-001e: should have cursor-pointer class on Settings link', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const settingsLink = screen.getByRole('link', { name: /settings/i });
      expect(settingsLink.className).toMatch(/cursor-pointer/);
    });

    it('HOV-001f: should have cursor-pointer class on AI Visibility link', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const aiVisibilityLink = screen.getByRole('link', { name: /ai visibility/i });
      expect(aiVisibilityLink.className).toMatch(/cursor-pointer/);
    });

    it('HOV-001g: should have cursor-pointer class on Sign In link (guest)', () => {
      render(<Header />);
      
      const signInLink = screen.getByRole('link', { name: /sign in/i });
      expect(signInLink.className).toMatch(/cursor-pointer/);
    });

    it('HOV-001h: should have cursor-pointer class on Indicators link (guest)', () => {
      render(<Header />);
      
      const indicatorsLink = screen.getByRole('link', { name: /indicators/i });
      expect(indicatorsLink.className).toMatch(/cursor-pointer/);
    });

    it('HOV-001i: should have cursor-pointer class on logo link', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const logoLink = screen.getByRole('link', { name: /seoindicator home/i });
      expect(logoLink.className).toMatch(/cursor-pointer/);
    });
  });

  // ============================================================
  // HOV-002: Hover State Backgrounds
  // ============================================================
  describe('HOV-002: Hover State Backgrounds', () => {
    it('HOV-002a: should have dark hover background on nav links', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      // Should have hover:bg-slate-700 or similar dark background
      expect(dashboardLink.className).toMatch(/hover:bg-slate-\d+/);
    });

    it('HOV-002b: should NOT have white hover background (white-on-white bug)', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      // Should NOT have hover:bg-white which causes white-on-white
      expect(dashboardLink.className).not.toMatch(/hover:bg-white(?!\S)/);
    });

    it('HOV-002c: should have white hover text on nav links', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink.className).toMatch(/hover:text-white/);
    });

    it('HOV-002d: should have dark hover background on Sign In link', () => {
      render(<Header />);
      
      const signInLink = screen.getByRole('link', { name: /sign in/i });
      expect(signInLink.className).toMatch(/hover:bg-slate-\d+/);
    });

    it('HOV-002e: should NOT have white hover background on Sign In link', () => {
      render(<Header />);
      
      const signInLink = screen.getByRole('link', { name: /sign in/i });
      expect(signInLink.className).not.toMatch(/hover:bg-white(?!\S)/);
    });

    it('HOV-002f: should have dark hover background on Indicators link', () => {
      render(<Header />);
      
      const indicatorsLink = screen.getByRole('link', { name: /indicators/i });
      expect(indicatorsLink.className).toMatch(/hover:bg-slate-\d+/);
    });

    it('HOV-002g: should NOT have white hover background on Indicators link', () => {
      render(<Header />);
      
      const indicatorsLink = screen.getByRole('link', { name: /indicators/i });
      expect(indicatorsLink.className).not.toMatch(/hover:bg-white(?!\S)/);
    });
  });

  // ============================================================
  // HOV-003: Active State Styling
  // ============================================================
  describe('HOV-003: Active State Styling', () => {
    it('HOV-003a: should have active background style when active', () => {
      mockPathname.mockReturnValue('/dashboard');
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      // Active state should have visible background
      expect(dashboardLink.className).toMatch(/bg-slate-\d+/);
    });

    it('HOV-003b: should have white text when active', () => {
      mockPathname.mockReturnValue('/dashboard');
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      // Active state should have white text
      expect(dashboardLink.className).toMatch(/text-white/);
    });

    it('HOV-003c: should have slate-400 text when not active', () => {
      mockPathname.mockReturnValue('/other');
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      // Non-active state should have slate-400 text
      expect(dashboardLink.className).toMatch(/text-slate-400/);
    });

    it('HOV-003d: should have transition-all for smooth hover effects', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink.className).toMatch(/transition/);
    });
  });

  // ============================================================
  // HOV-004: Focus States
  // ============================================================
  describe('HOV-004: Focus States', () => {
    it('HOV-004a: should have focus-visible outline', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink.className).toMatch(/focus-visible:outline/);
    });

    it('HOV-004b: should have focus-visible ring', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink.className).toMatch(/focus-visible:ring/);
    });

    it('HOV-004c: should have blue focus ring color', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink.className).toMatch(/ring-blue/);
    });

    it('HOV-004d: should have focus-visible ring offset', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink.className).toMatch(/ring-offset/);
    });
  });

  // ============================================================
  // HOV-005: Button vs Link Elements
  // ============================================================
  describe('HOV-005: Element Type Tests', () => {
    it('HOV-005a: nav items should be anchor elements (not buttons)', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink.tagName.toLowerCase()).toBe('a');
    });

    it('HOV-005b: Sign In should be anchor element (not button)', () => {
      render(<Header />);
      
      const signInLink = screen.getByRole('link', { name: /sign in/i });
      expect(signInLink.tagName.toLowerCase()).toBe('a');
    });

    it('HOV-005c: Indicators should be anchor element (not button)', () => {
      render(<Header />);
      
      const indicatorsLink = screen.getByRole('link', { name: /indicators/i });
      expect(indicatorsLink.tagName.toLowerCase()).toBe('a');
    });

    it('HOV-005d: logo should be anchor element', () => {
      render(<Header />);
      
      const logoLink = screen.getByRole('link', { name: /seoindicator home/i });
      expect(logoLink.tagName.toLowerCase()).toBe('a');
    });
  });

  // ============================================================
  // HOV-006: Notification Bell Buttons
  // ============================================================
  describe('HOV-006: Notification Bell Buttons', () => {
    it('HOV-006a: notification bell should have cursor-pointer', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const bellButton = screen.getByRole('button', { name: /notifications/i });
      expect(bellButton.className).toMatch(/cursor-pointer/);
    });

    it('HOV-006b: notification bell should have dark hover background', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const bellButton = screen.getByRole('button', { name: /notifications/i });
      expect(bellButton.className).toMatch(/hover:bg-slate-\d+/);
    });

    it('HOV-006c: AI Visibility alerts bell should have cursor-pointer', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const eyeButton = screen.getByRole('button', { name: /ai visibility alerts/i });
      expect(eyeButton.className).toMatch(/cursor-pointer/);
    });

    it('HOV-006d: AI Visibility alerts bell should have dark hover background', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const eyeButton = screen.getByRole('button', { name: /ai visibility alerts/i });
      expect(eyeButton.className).toMatch(/hover:bg-slate-\d+/);
    });
  });

  // ============================================================
  // HOV-007: Responsive Hover States
  // ============================================================
  describe('HOV-007: Responsive Hover States', () => {
    it('HOV-007a: nav links should have consistent padding for click targets', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      // Should have adequate padding for click target (min 44px tap target)
      expect(dashboardLink.className).toMatch(/px-\d+\s+py-\d+/);
    });

    it('HOV-007b: nav links should have rounded corners', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink.className).toMatch(/rounded/);
    });
  });

  // ============================================================
  // Visual Contrast Tests
  // ============================================================
  describe('Visual Contrast', () => {
    it('should have sufficient contrast between text and background on hover', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      const classes = dashboardLink.className;
      
      // Dark background + white text = good contrast
      expect(classes).toMatch(/hover:text-white/);
      expect(classes).toMatch(/hover:bg-slate-\d+/);
    });

    it('should have visible active state indicator bar', () => {
      mockPathname.mockReturnValue('/dashboard');
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      // Active state should have blue indicator bar
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      // Check that active state styling is applied
      expect(dashboardLink.className).toMatch(/bg-slate-\d+/);
    });
  });

  // ============================================================
  // Guest Navigation Hover States
  // ============================================================
  describe('Guest Navigation Hover States', () => {
    it('Sign In link should have proper hover transition', () => {
      render(<Header />);
      
      const signInLink = screen.getByRole('link', { name: /sign in/i });
      expect(signInLink.className).toMatch(/transition/);
    });

    it('Indicators link should have proper hover transition', () => {
      render(<Header />);
      
      const indicatorsLink = screen.getByRole('link', { name: /indicators/i });
      expect(indicatorsLink.className).toMatch(/transition/);
    });

    it('Sign In link should have rounded corners', () => {
      render(<Header />);
      
      const signInLink = screen.getByRole('link', { name: /sign in/i });
      expect(signInLink.className).toMatch(/rounded/);
    });

    it('Indicators link should have rounded corners', () => {
      render(<Header />);
      
      const indicatorsLink = screen.getByRole('link', { name: /indicators/i });
      expect(indicatorsLink.className).toMatch(/rounded/);
    });
  });
});
