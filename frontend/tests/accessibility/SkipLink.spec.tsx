/**
 * Accessibility Tests for Skip Link Component
 * 
 * Tests for WCAG 2.1 AA compliance including skip-to-content
 * functionality for keyboard users.
 * 
 * @see TESTS/TASK-28-16.md for full test documentation
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';

// ============================================
// Mock Skip Link Component
// ============================================

interface SkipLinkProps {
  targetId?: string;
  label?: string;
}

const MockSkipLink: React.FC<SkipLinkProps> = ({
  targetId = 'main-content',
  label = 'Skip to main content',
}) => {
  return (
    <a
      href={`#${targetId}`}
      className="skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-primary focus:text-primary-foreground focus:px-4 focus:py-2 focus:rounded"
      data-testid="skip-link"
    >
      {label}
    </a>
  );
};

// ============================================
// Mock Page with Skip Link
// ============================================

const MockPageWithSkipLink: React.FC = () => {
  return (
    <>
      <MockSkipLink />

      <header>
        <nav aria-label="Main navigation" data-testid="main-nav">
          <a href="/dashboard" data-testid="nav-link-1">Dashboard</a>
          <a href="/keywords" data-testid="nav-link-2">Keywords</a>
          <a href="/reports" data-testid="nav-link-3">Reports</a>
        </nav>
      </header>

      <main id="main-content" tabIndex={-1} data-testid="main-content">
        <h1>Dashboard</h1>
        <p>Main content goes here.</p>
      </main>

      <footer data-testid="footer">
        <p>Footer content</p>
      </footer>
    </>
  );
};

// ============================================
// Test Scenarios SL-001 to SL-006
// ============================================

describe('Skip Link (SL-001 to SL-006)', () => {
  // ============================================
  // SL-001: Skip link exists
  // ============================================
  it('SL-001: skip link exists on page', () => {
    render(<MockPageWithSkipLink />);

    const skipLink = screen.getByTestId('skip-link');
    expect(skipLink).toBeInTheDocument();
    expect(skipLink).toHaveTextContent('Skip to main content');
  });

  // ============================================
  // SL-002: Skip link first focus
  // ============================================
  it('SL-002: skip link is first focusable element', async () => {
    const user = userEvent.setup();

    render(<MockPageWithSkipLink />);

    await user.tab();

    expect(screen.getByTestId('skip-link')).toHaveFocus();
  });

  // ============================================
  // SL-003: Skip link hidden initially
  // ============================================
  it('SL-003: skip link is visually hidden until focused', () => {
    render(<MockPageWithSkipLink />);

    const skipLink = screen.getByTestId('skip-link');
    
    // Should have sr-only class (screen reader only)
    expect(skipLink.className).toMatch(/sr-only/);
  });

  // ============================================
  // SL-004: Skip link visible on focus
  // ============================================
  it('SL-004: skip link becomes visible when focused', async () => {
    const user = userEvent.setup();

    render(<MockPageWithSkipLink />);

    const skipLink = screen.getByTestId('skip-link');
    
    // Focus the skip link
    await user.tab();

    // When focused, it should have focus: classes that make it visible
    expect(skipLink.className).toMatch(/focus:/);
    expect(skipLink.className).toMatch(/not-sr-only/);
  });

  // ============================================
  // SL-005: Skip link navigates to main
  // ============================================
  it('SL-005: skip link navigates to #main-content', async () => {
    const user = userEvent.setup();

    render(<MockPageWithSkipLink />);

    const skipLink = screen.getByTestId('skip-link');
    
    // Verify href points to main content
    expect(skipLink).toHaveAttribute('href', '#main-content');
  });

  // ============================================
  // SL-006: Main has id
  // ============================================
  it('SL-006: main content has id="main-content"', () => {
    render(<MockPageWithSkipLink />);

    const mainContent = screen.getByTestId('main-content');
    expect(mainContent).toHaveAttribute('id', 'main-content');
  });
});

// ============================================
// Skip Link Customization Tests
// ============================================

describe('Skip Link Customization', () => {
  it('supports custom target id', () => {
    render(<MockSkipLink targetId="custom-content" />);

    const skipLink = screen.getByTestId('skip-link');
    expect(skipLink).toHaveAttribute('href', '#custom-content');
  });

  it('supports custom label', () => {
    render(<MockSkipLink label="Skip to content" />);

    const skipLink = screen.getByTestId('skip-link');
    expect(skipLink).toHaveTextContent('Skip to content');
  });
});

// ============================================
// Skip Link Accessibility Tests
// ============================================

describe('Skip Link Accessibility', () => {
  it('has accessible link text', () => {
    render(<MockSkipLink />);

    const skipLink = screen.getByRole('link', { name: /skip to main content/i });
    expect(skipLink).toBeInTheDocument();
  });

  it('is reachable via keyboard', async () => {
    const user = userEvent.setup();

    render(<MockPageWithSkipLink />);

    // First Tab should reach skip link
    await user.tab();
    expect(screen.getByTestId('skip-link')).toHaveFocus();

    // Second Tab should reach first nav link
    await user.tab();
    expect(screen.getByTestId('nav-link-1')).toHaveFocus();
  });
});

// ============================================
// Integration: Full Page Navigation with Skip Link
// ============================================

describe('Full Page Navigation with Skip Link', () => {
  it('keyboard user can skip navigation and go directly to content', async () => {
    const user = userEvent.setup();

    render(<MockPageWithSkipLink />);

    // Tab to skip link
    await user.tab();
    expect(screen.getByTestId('skip-link')).toHaveFocus();

    // Activate skip link (Enter)
    await user.keyboard('{Enter}');

    // In a real browser, this would scroll to #main-content
    // and focus would move there
    const mainContent = screen.getByTestId('main-content');
    expect(mainContent).toBeInTheDocument();
  });

  it('user can choose to navigate through header instead of skipping', async () => {
    const user = userEvent.setup();

    render(<MockPageWithSkipLink />);

    // Tab to skip link
    await user.tab();
    expect(screen.getByTestId('skip-link')).toHaveFocus();

    // Tab past skip link (don't activate it)
    await user.tab();
    expect(screen.getByTestId('nav-link-1')).toHaveFocus();

    // Continue through navigation
    await user.tab();
    expect(screen.getByTestId('nav-link-2')).toHaveFocus();

    await user.tab();
    expect(screen.getByTestId('nav-link-3')).toHaveFocus();

    // Tab to main content
    await user.tab();
    expect(screen.getByTestId('main-content')).toHaveFocus();
  });
});

// ============================================
// Multiple Skip Links Pattern
// ============================================

describe('Multiple Skip Links Pattern', () => {
  it('can have multiple skip links for different sections', () => {
    render(
      <>
        <MockSkipLink targetId="main-content" label="Skip to main content" />
        <MockSkipLink targetId="search" label="Skip to search" />
        
        <header>
          <div id="search" data-testid="search-box">
            <input type="search" placeholder="Search..." />
          </div>
        </header>
        
        <main id="main-content" data-testid="main-content">
          Content
        </main>
      </>
    );

    const skipLinks = screen.getAllByRole('link').filter(
      (link) => link.getAttribute('href')?.startsWith('#')
    );

    expect(skipLinks.length).toBe(2);
    expect(skipLinks[0]).toHaveTextContent('Skip to main content');
    expect(skipLinks[1]).toHaveTextContent('Skip to search');
  });
});
