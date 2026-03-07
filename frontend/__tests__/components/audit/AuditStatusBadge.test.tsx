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

// Mock lucide-react icons
vi.mock('lucide-react', () => ({
  Clock: () => <span data-testid="clock-icon">Clock</span>,
  Loader: () => <span data-testid="loader-icon">Loader</span>,
  CheckCircle: () => <span data-testid="check-circle-icon">CheckCircle</span>,
  XCircle: () => <span data-testid="x-circle-icon">XCircle</span>,
}));

// Import component (will be created by Developer)
// import { AuditStatusBadge } from '@/components/audit/AuditStatusBadge';

// Mock component for testing structure
const MockAuditStatusBadge = ({ status }: { status: string }) => {
  const statusConfig: Record<string, { icon: string; label: string; className: string }> = {
    pending: { icon: 'Clock', label: 'Pending', className: 'bg-gray-100 text-gray-700 animate-pulse' },
    running: { icon: 'Loader', label: 'Running', className: 'bg-blue-100 text-blue-700 animate-spin' },
    completed: { icon: 'CheckCircle', label: 'Completed', className: 'bg-green-100 text-green-700' },
    failed: { icon: 'XCircle', label: 'Failed', className: 'bg-red-100 text-red-700' },
    cancelled: { icon: 'XCircle', label: 'Cancelled', className: 'bg-gray-100 text-gray-500' },
  };

  const config = statusConfig[status] || statusConfig.pending;

  return (
    <span
      data-testid={`status-badge-${status}`}
      className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-sm font-medium ${config.className}`}
      aria-label={`Audit status: ${config.label}`}
    >
      <span data-testid={`${config.icon.toLowerCase()}-icon`}>{config.icon}</span>
      {config.label}
    </span>
  );
};

describe('AuditStatusBadge', () => {
  // ============================================
  // ASB-BADGE-RENDER: Badge Rendering
  // ============================================
  
  describe('ASB-BADGE-RENDER: Badge Rendering', () => {
    it('ASB-BDG-RN-001: Renders pending badge with gray background and Clock icon', () => {
      render(<MockAuditStatusBadge status="pending" />);
      
      const badge = screen.getByTestId('status-badge-pending');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Pending');
      expect(screen.getByTestId('clock-icon')).toBeInTheDocument();
      expect(badge.className).toContain('bg-gray-100');
    });

    it('ASB-BDG-RN-002: Renders running badge with blue background and Loader icon', () => {
      render(<MockAuditStatusBadge status="running" />);
      
      const badge = screen.getByTestId('status-badge-running');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Running');
      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
      expect(badge.className).toContain('bg-blue-100');
    });

    it('ASB-BDG-RN-003: Renders completed badge with green background and CheckCircle icon', () => {
      render(<MockAuditStatusBadge status="completed" />);
      
      const badge = screen.getByTestId('status-badge-completed');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Completed');
      expect(screen.getByTestId('check-circle-icon')).toBeInTheDocument();
      expect(badge.className).toContain('bg-green-100');
    });

    it('ASB-BDG-RN-004: Renders failed badge with red background and XCircle icon', () => {
      render(<MockAuditStatusBadge status="failed" />);
      
      const badge = screen.getByTestId('status-badge-failed');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Failed');
      expect(screen.getByTestId('x-circle-icon')).toBeInTheDocument();
      expect(badge.className).toContain('bg-red-100');
    });

    it('ASB-BDG-RN-005: Renders cancelled badge with gray background and XCircle icon', () => {
      render(<MockAuditStatusBadge status="cancelled" />);
      
      const badge = screen.getByTestId('status-badge-cancelled');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Cancelled');
      expect(screen.getByTestId('x-circle-icon')).toBeInTheDocument();
      expect(badge.className).toContain('bg-gray-100');
    });
  });

  // ============================================
  // ASB-BADGE-ANIMATION: Animations
  // ============================================
  
  describe('ASB-BADGE-ANIMATION: Animations', () => {
    it('ASB-BDG-AN-001: Pending badge has pulse animation', () => {
      render(<MockAuditStatusBadge status="pending" />);
      
      const badge = screen.getByTestId('status-badge-pending');
      expect(badge.className).toContain('animate-pulse');
    });

    it('ASB-BDG-AN-002: Running badge has spin animation', () => {
      render(<MockAuditStatusBadge status="running" />);
      
      const badge = screen.getByTestId('status-badge-running');
      expect(badge.className).toContain('animate-spin');
    });

    it('ASB-BDG-AN-003: Completed badge has no animation', () => {
      render(<MockAuditStatusBadge status="completed" />);
      
      const badge = screen.getByTestId('status-badge-completed');
      expect(badge.className).not.toContain('animate-pulse');
      expect(badge.className).not.toContain('animate-spin');
    });

    it('ASB-BDG-AN-004: Failed badge has no animation', () => {
      render(<MockAuditStatusBadge status="failed" />);
      
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
      render(<MockAuditStatusBadge status="completed" />);
      
      const badge = screen.getByTestId('status-badge-completed');
      expect(badge).toHaveAttribute('aria-label', 'Audit status: Completed');
    });

    it('pending badge has accessible label', () => {
      render(<MockAuditStatusBadge status="pending" />);
      
      const badge = screen.getByTestId('status-badge-pending');
      expect(badge).toHaveAttribute('aria-label', 'Audit status: Pending');
    });

    it('running badge has accessible label', () => {
      render(<MockAuditStatusBadge status="running" />);
      
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
      render(<MockAuditStatusBadge status="unknown" />);
      
      // Should fallback to pending style
      expect(screen.getByText('Pending')).toBeInTheDocument();
    });
  });
});
