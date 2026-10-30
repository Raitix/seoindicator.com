/**
 * Accessibility Tests for Skip Link Component
 * 
 * Tests for WCAG 2.1 AA compliance including skip-to-content
 * functionality for keyboard users.
 * 
 * @see TESTS/TASK-28-16.md for full test documentation
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { SkipLink, SkipToMain } from '@/components/layout/SkipLink';

// ============================================
// Test Scenarios SL-001 to SL-006
// ============================================

describe('Skip Link (SL-001 to SL-006)', () => {
  // ============================================
  // SL-001: Skip link exists
  // ============================================
  it('SL-001: skip link exists on page', () => {
    render(<SkipLink />);

    const skipLink = screen.getByTestId('skip-link');
    expect(skipLink).toBeInTheDocument();
    expect(skipLink).toHaveTextContent('Skip to main content');
  });

  // ============================================
  // SL-002: Skip link first focus
  // ============================================
  it('SL-002: skip link is focusable element', async () => {
    const user = userEvent.setup();

    render(<SkipLink />);

    await user.tab();

    expect(screen.getByTestId('skip-link')).toHaveFocus();
  });

  // ============================================
  // SL-003: Skip link hidden initially
  // ============================================
  it('SL-003: skip link is visually hidden until focused', () => {
    render(<SkipLink />);

    const skipLink = screen.getByTestId('skip-link');
    
    // Should have sr-only class (screen reader only)
    expect(skipLink.className).toMatch(/sr-only/);
  });

  // ============================================
  // SL-004: Skip link visible on focus
  // ============================================
  it('SL-004: skip link becomes visible when focused', async () => {
    const user = userEvent.setup();

    render(<SkipLink />);

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
    render(<SkipLink />);

    const skipLink = screen.getByTestId('skip-link');
    
    // Verify href points to main content
    expect(skipLink).toHaveAttribute('href', '#main-content');
  });

  // ============================================
  // SL-006: Main has id
  // ============================================
  it('SL-006: SkipToMain has id="main-content"', () => {
    render(<SkipToMain>Content</SkipToMain>);

    const mainContent = screen.getByTestId('main-content');
    expect(mainContent).toHaveAttribute('id', 'main-content');
  });
});

// ============================================
// Skip Link Customization Tests
// ============================================

describe('Skip Link Customization', () => {
  it('supports custom target id', () => {
    render(<SkipLink targetId="custom-content" />);

    const skipLink = screen.getByTestId('skip-link');
    expect(skipLink).toHaveAttribute('href', '#custom-content');
  });

  it('supports custom label', () => {
    render(<SkipLink label="Skip to content" />);

    const skipLink = screen.getByTestId('skip-link');
    expect(skipLink).toHaveTextContent('Skip to content');
  });
});

// ============================================
// Skip Link Accessibility Tests
// ============================================

describe('Skip Link Accessibility', () => {
  it('has accessible link text', () => {
    render(<SkipLink />);

    const skipLink = screen.getByRole('link', { name: /skip to main content/i });
    expect(skipLink).toBeInTheDocument();
  });

  it('has aria-label attribute', () => {
    render(<SkipLink />);

    const skipLink = screen.getByTestId('skip-link');
    expect(skipLink).toHaveAttribute('aria-label');
  });
});

// ============================================
// SkipToMain Component Tests
// ============================================

describe('SkipToMain Component', () => {
  it('renders children correctly', () => {
    render(
      <SkipToMain>
        <h1>Dashboard</h1>
        <p>Main content</p>
      </SkipToMain>
    );

    expect(screen.getByRole('main')).toBeInTheDocument();
    expect(screen.getByText('Dashboard')).toBeInTheDocument();
    expect(screen.getByText('Main content')).toBeInTheDocument();
  });

  it('has role="main"', () => {
    render(<SkipToMain>Content</SkipToMain>);

    expect(screen.getByRole('main')).toBeInTheDocument();
  });

  it('has aria-label', () => {
    render(<SkipToMain>Content</SkipToMain>);

    const main = screen.getByRole('main');
    expect(main).toHaveAttribute('aria-label', 'Main content');
  });

  it('supports custom id', () => {
    render(<SkipToMain id="custom-main">Content</SkipToMain>);

    const main = screen.getByRole('main');
    expect(main).toHaveAttribute('id', 'custom-main');
  });
});
