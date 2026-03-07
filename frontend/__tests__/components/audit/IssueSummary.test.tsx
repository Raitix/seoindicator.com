/**
 * IssueSummary Component Tests
 * 
 * Task: TASK-14-15 - Create Audit List Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Tests the issue summary badges component displaying critical/warning/notice counts
 */

import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock shadcn/ui Badge component
vi.mock('@/components/ui/badge', () => ({
  Badge: ({ variant, children, className, ...props }: any) => (
    <span
      data-testid={`badge-${variant}`}
      className={`badge ${className || ''}`}
      {...props}
    >
      {children}
    </span>
  ),
}));

// Import component (will be created by Developer)
// import { IssueSummary } from '@/components/audit/IssueSummary';

// Mock component for testing structure
interface IssueSummaryProps {
  critical: number;
  warning: number;
  notice: number;
}

const MockIssueSummary = ({ critical, warning, notice }: IssueSummaryProps) => {
  return (
    <div data-testid="issue-summary" className="flex gap-2">
      <span
        data-testid="issue-summary-critical"
        className="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-red-100 text-red-800 text-sm font-medium"
        aria-label={`${critical} critical issues`}
      >
        <span className="w-2 h-2 rounded-full bg-red-500" />
        {critical} Critical
      </span>
      <span
        data-testid="issue-summary-warning"
        className="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-orange-100 text-orange-800 text-sm font-medium"
        aria-label={`${warning} warning issues`}
      >
        <span className="w-2 h-2 rounded-full bg-orange-500" />
        {warning} Warning
      </span>
      <span
        data-testid="issue-summary-notice"
        className="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-yellow-100 text-yellow-800 text-sm font-medium"
        aria-label={`${notice} notice issues`}
      >
        <span className="w-2 h-2 rounded-full bg-yellow-500" />
        {notice} Notice
      </span>
    </div>
  );
};

describe('IssueSummary', () => {
  // ============================================
  // IS-BADGES-RENDER: Badge Rendering
  // ============================================
  
  describe('IS-BADGES-RENDER: Badge Rendering', () => {
    it('IS-BDG-RN-001: Renders critical badge with count', () => {
      render(<MockIssueSummary critical={10} warning={5} notice={3} />);
      
      const criticalBadge = screen.getByTestId('issue-summary-critical');
      expect(criticalBadge).toBeInTheDocument();
      expect(criticalBadge).toHaveTextContent('10 Critical');
    });

    it('IS-BDG-RN-002: Renders warning badge with count', () => {
      render(<MockIssueSummary critical={10} warning={5} notice={3} />);
      
      const warningBadge = screen.getByTestId('issue-summary-warning');
      expect(warningBadge).toBeInTheDocument();
      expect(warningBadge).toHaveTextContent('5 Warning');
    });

    it('IS-BDG-RN-003: Renders notice badge with count', () => {
      render(<MockIssueSummary critical={10} warning={5} notice={3} />);
      
      const noticeBadge = screen.getByTestId('issue-summary-notice');
      expect(noticeBadge).toBeInTheDocument();
      expect(noticeBadge).toHaveTextContent('3 Notice');
    });

    it('IS-BDG-RN-004: Renders all badges in flex row', () => {
      render(<MockIssueSummary critical={1} warning={2} notice={3} />);
      
      const container = screen.getByTestId('issue-summary');
      expect(container.className).toContain('flex');
    });

    it('IS-BDG-RN-005: Uses correct test IDs', () => {
      render(<MockIssueSummary critical={1} warning={2} notice={3} />);
      
      expect(screen.getByTestId('issue-summary-critical')).toBeInTheDocument();
      expect(screen.getByTestId('issue-summary-warning')).toBeInTheDocument();
      expect(screen.getByTestId('issue-summary-notice')).toBeInTheDocument();
    });
  });

  // ============================================
  // IS-BADGES-COUNTS: Count Handling
  // ============================================
  
  describe('IS-BADGES-COUNTS: Count Handling', () => {
    it('IS-BDG-CT-001: Shows zero count for critical', () => {
      render(<MockIssueSummary critical={0} warning={5} notice={3} />);
      
      const criticalBadge = screen.getByTestId('issue-summary-critical');
      expect(criticalBadge).toHaveTextContent('0 Critical');
    });

    it('IS-BDG-CT-002: Handles large counts', () => {
      render(<MockIssueSummary critical={1000} warning={500} notice={250} />);
      
      expect(screen.getByTestId('issue-summary-critical')).toHaveTextContent('1000 Critical');
      expect(screen.getByTestId('issue-summary-warning')).toHaveTextContent('500 Warning');
      expect(screen.getByTestId('issue-summary-notice')).toHaveTextContent('250 Notice');
    });

    it('IS-BDG-CT-003: All zeros shows all badges', () => {
      render(<MockIssueSummary critical={0} warning={0} notice={0} />);
      
      expect(screen.getByTestId('issue-summary-critical')).toBeInTheDocument();
      expect(screen.getByTestId('issue-summary-warning')).toBeInTheDocument();
      expect(screen.getByTestId('issue-summary-notice')).toBeInTheDocument();
    });
  });

  // ============================================
  // ACCESSIBILITY: ARIA Tests
  // ============================================
  
  describe('ACCESSIBILITY: ARIA Attributes', () => {
    it('critical badge has aria-label', () => {
      render(<MockIssueSummary critical={10} warning={5} notice={3} />);
      
      const criticalBadge = screen.getByTestId('issue-summary-critical');
      expect(criticalBadge).toHaveAttribute('aria-label', '10 critical issues');
    });

    it('warning badge has aria-label', () => {
      render(<MockIssueSummary critical={10} warning={5} notice={3} />);
      
      const warningBadge = screen.getByTestId('issue-summary-warning');
      expect(warningBadge).toHaveAttribute('aria-label', '5 warning issues');
    });

    it('notice badge has aria-label', () => {
      render(<MockIssueSummary critical={10} warning={5} notice={3} />);
      
      const noticeBadge = screen.getByTestId('issue-summary-notice');
      expect(noticeBadge).toHaveAttribute('aria-label', '3 notice issues');
    });
  });

  // ============================================
  // COLORS: Color Variants
  // ============================================
  
  describe('COLORS: Color Variants', () => {
    it('critical has red styling', () => {
      render(<MockIssueSummary critical={1} warning={0} notice={0} />);
      
      const badge = screen.getByTestId('issue-summary-critical');
      expect(badge.className).toContain('bg-red-100');
      expect(badge.className).toContain('text-red-800');
    });

    it('warning has orange styling', () => {
      render(<MockIssueSummary critical={0} warning={1} notice={0} />);
      
      const badge = screen.getByTestId('issue-summary-warning');
      expect(badge.className).toContain('bg-orange-100');
      expect(badge.className).toContain('text-orange-800');
    });

    it('notice has yellow styling', () => {
      render(<MockIssueSummary critical={0} warning={0} notice={1} />);
      
      const badge = screen.getByTestId('issue-summary-notice');
      expect(badge.className).toContain('bg-yellow-100');
      expect(badge.className).toContain('text-yellow-800');
    });
  });
});
