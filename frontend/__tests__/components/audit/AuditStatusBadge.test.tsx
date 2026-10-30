/**
 * AuditStatusBadge Component Tests
 * 
 * Task: TASK-14-15 - Create Audit List Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Tests the status badge component with icons and animations
 */

import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock lucide-react icons with test IDs matching component expectations
vi.mock('lucide-react', () => ({
  Clock: () => <span data-testid="clock-icon">Clock</span>,
  Loader2: () => <span data-testid="loader-icon">Loader</span>,
  CheckCircle: () => <span data-testid="check-circle-icon">CheckCircle</span>,
  XCircle: () => <span data-testid="x-circle-icon">XCircle</span>,
}));

// Import actual component
import { AuditStatusBadge } from '@/components/audit/AuditStatusBadge';

describe('AuditStatusBadge', () => {
  // ============================================
  // ASB-BADGE-RENDER: Badge Rendering
  // ============================================
  
  describe('ASB-BADGE-RENDER: Badge Rendering', () => {
    it('ASB-BDG-RN-001: Renders pending badge with gray background and Clock icon', () => {
      render(<AuditStatusBadge status="pending" />);
      
      const badge = screen.getByTestId('status-badge-pending');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Pending');
      expect(screen.getByTestId('clock-icon')).toBeInTheDocument();
      expect(badge.className).toContain('bg-gray');
    });

    it('ASB-BDG-RN-002: Renders running badge with blue background and Loader icon', () => {
      render(<AuditStatusBadge status="running" />);
      
      const badge = screen.getByTestId('status-badge-running');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Running');
      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
      expect(badge.className).toContain('bg-blue');
    });

    it('ASB-BDG-RN-003: Renders completed badge with green background and CheckCircle icon', () => {
      render(<AuditStatusBadge status="completed" />);
      
      const badge = screen.getByTestId('status-badge-completed');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Completed');
      expect(screen.getByTestId('check-circle-icon')).toBeInTheDocument();
      expect(badge.className).toContain('bg-green');
    });

    it('ASB-BDG-RN-004: Renders failed badge with red background and XCircle icon', () => {
      render(<AuditStatusBadge status="failed" />);
      
      const badge = screen.getByTestId('status-badge-failed');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Failed');
      expect(screen.getByTestId('x-circle-icon')).toBeInTheDocument();
      expect(badge.className).toContain('bg-red');
    });

    it('ASB-BDG-RN-005: Renders cancelled badge with gray background and XCircle icon', () => {
      render(<AuditStatusBadge status="cancelled" />);
      
      const badge = screen.getByTestId('status-badge-cancelled');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Cancelled');
      expect(screen.getByTestId('x-circle-icon')).toBeInTheDocument();
      expect(badge.className).toContain('bg-gray');
    });
  });

  // ============================================
  // ASB-BADGE-ANIMATION: Animations
  // ============================================
  
  describe('ASB-BADGE-ANIMATION: Animations', () => {
    it('ASB-BDG-AN-001: Pending badge has pulse animation', () => {
      render(<AuditStatusBadge status="pending" />);
      
      const badge = screen.getByTestId('status-badge-pending');
      expect(badge.className).toContain('animate-pulse');
    });

    it('ASB-BDG-AN-002: Running badge has spin animation', () => {
      render(<AuditStatusBadge status="running" />);
      
      const badge = screen.getByTestId('status-badge-running');
      expect(badge.className).toContain('animate-spin');
    });

    it('ASB-BDG-AN-003: Completed badge has no animation', () => {
      render(<AuditStatusBadge status="completed" />);
      
      const badge = screen.getByTestId('status-badge-completed');
      expect(badge.className).not.toContain('animate-pulse');
      expect(badge.className).not.toContain('animate-spin');
    });

    it('ASB-BDG-AN-004: Failed badge has no animation', () => {
      render(<AuditStatusBadge status="failed" />);
      
      const badge = screen.getByTestId('status-badge-failed');
      expect(badge.className).not.toContain('animate-pulse');
      expect(badge.className).not.toContain('animate-spin');
    });
  });

  // ============================================
  // ACCESSIBILITY: ARIA Tests
  // ============================================
  
  describe('ACCESSIBILITY: ARIA Attributes', () => {
    it('has aria-label describing status', () => {
      render(<AuditStatusBadge status="completed" />);
      
      const badge = screen.getByTestId('status-badge-completed');
      expect(badge).toHaveAttribute('aria-label', 'Audit status: Completed');
    });

    it('pending badge has accessible label', () => {
      render(<AuditStatusBadge status="pending" />);
      
      const badge = screen.getByTestId('status-badge-pending');
      expect(badge).toHaveAttribute('aria-label', 'Audit status: Pending');
    });

    it('running badge has accessible label', () => {
      render(<AuditStatusBadge status="running" />);
      
      const badge = screen.getByTestId('status-badge-running');
      expect(badge).toHaveAttribute('aria-label', 'Audit status: Running');
    });
  });

  // ============================================
  // EDGE CASES: Unknown Status
  // ============================================
  
  describe('EDGE CASES: Unknown Status', () => {
    it('renders pending badge for unknown status as fallback', () => {
      // @ts-expect-error - Testing unknown status
      render(<AuditStatusBadge status="unknown" />);
      
      // Should fallback to pending style
      expect(screen.getByText('Pending')).toBeInTheDocument();
    });
  });
});
