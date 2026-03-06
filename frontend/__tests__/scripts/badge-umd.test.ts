/**
 * badge-umd.test.ts - UMD compatibility tests for badge.js embed script
 * Task: TASK-33-16 - Implement badge.js Embed Script
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// ============================================================================
// BDG-UMD: UMD Compatibility Tests
// ============================================================================

describe('BDG-UMD: UMD Compatibility', () => {
  beforeEach(() => {
    vi.resetModules();
    delete (window as unknown as Record<string, unknown>).SEOIndicatorBadge;
    delete (window as unknown as Record<string, unknown>).SEOINDICATOR_NO_AUTO_INIT;
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('BDG-UMD-02: Module has all required methods', async () => {
    const api = await import('@/lib/scripts/badge');
    
    expect(typeof api.init).toBe('function');
    expect(typeof api.refresh).toBe('function');
    expect(typeof api.setTheme).toBe('function');
    expect(typeof api.destroy).toBe('function');
    expect(typeof api.noConflict).toBe('function');
  });

  it('BDG-UMD-03: Default export available', async () => {
    const { default: api } = await import('@/lib/scripts/badge');
    
    expect(api).toBeDefined();
    expect(typeof api.init).toBe('function');
  });

  it('BDG-UMD-04: Named exports available', async () => {
    const { init, refresh, setTheme, destroy, noConflict, VERSION } = 
      await import('@/lib/scripts/badge');
    
    expect(init).toBeDefined();
    expect(refresh).toBeDefined();
    expect(setTheme).toBeDefined();
    expect(destroy).toBeDefined();
    expect(noConflict).toBeDefined();
    expect(VERSION).toBeDefined();
  });

  it('BDG-UMD-06: Version property available', async () => {
    const api = await import('@/lib/scripts/badge');
    
    expect(api.VERSION).toBeDefined();
    expect(typeof api.VERSION).toBe('string');
    expect(api.VERSION).toMatch(/^\d+\.\d+\.\d+$/);
  });
});
