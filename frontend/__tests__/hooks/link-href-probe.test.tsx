import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import Link from 'next/link';

describe('probe: real next/link href rendering', () => {
  it('renders the href verbatim or normalized?', () => {
    render(<Link href="/register/">Get Started Free</Link>);
    const el = screen.getByRole('link', { name: /get started free/i });
    console.log('PROBE href attribute =', JSON.stringify(el.getAttribute('href')));
    expect(true).toBe(true);
  });
});
