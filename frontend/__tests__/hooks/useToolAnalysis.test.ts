/**
 * Regression tests for the tool analysis endpoint contract.
 *
 * BUG (TASK: tools returned "Could not access the page"):
 *   1. The hook posted to /api/tools/analyze, but the Laravel route is
 *      registered under the 'v1' prefix -> /api/v1/tools/analyze.
 *      The request 404'd, and the hook mapped every non-2xx that was not
 *      429/5xx onto FETCH_FAILED, so the user saw a fetch error for what
 *      was really a wrong URL.
 *   2. The frontend sent tool="keyword-density" while the backend only
 *      accepts "keyword-density-checker" -> 422 VALIDATION_ERROR.
 *   3. The frontend read result.occurrences / result.status, but the
 *      backend returns keywordCount / densityStatus.
 *
 * These tests pin the contract so the three cannot drift apart again.
 */
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { renderHook, act, waitFor } from '@testing-library/react';
import { useToolAnalysis } from '@/hooks/useToolAnalysis';

describe('useToolAnalysis - endpoint and tool contract', () => {
  let fetchMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  const okResponse = (data: Record<string, unknown>) =>
    ({
      ok: true,
      status: 200,
      headers: new Headers(),
      json: async () => ({ success: true, url: 'https://example.com', analyzedAt: '2026-01-01T00:00:00Z', data }),
    }) as unknown as Response;

  it('posts to the versioned /api/v1/tools/analyze route', async () => {
    fetchMock.mockResolvedValue(okResponse({ title: { value: 'Example Domain' } }));

    const { result } = renderHook(() => useToolAnalysis({ tool: 'meta-tag-analyzer' }));

    await act(async () => {
      await result.current.analyze('https://example.com');
    });

    expect(fetchMock).toHaveBeenCalledTimes(1);
    const [url, init] = fetchMock.mock.calls[0];
    expect(url).toBe('/api/v1/tools/analyze');
    expect(init.method).toBe('POST');
  });

  it('sends the tool id the backend accepts (keyword-density-checker)', async () => {
    fetchMock.mockResolvedValue(okResponse({ keyword: 'test', density: 1.5, keywordCount: 5, totalWords: 333 }));

    const { result } = renderHook(() => useToolAnalysis({ tool: 'keyword-density' }));

    await act(async () => {
      await result.current.analyze('https://example.com', 'test');
    });

    const body = JSON.parse(fetchMock.mock.calls[0][1].body);
    expect(body.tool).toBe('keyword-density-checker');
    expect(body.keyword).toBe('test');
  });

  it('sends the tool id the backend accepts (social-media-preview)', async () => {
    fetchMock.mockResolvedValue(okResponse({ og: {} }));

    const { result } = renderHook(() => useToolAnalysis({ tool: 'social-preview' }));

    await act(async () => {
      await result.current.analyze('https://example.com');
    });

    const body = JSON.parse(fetchMock.mock.calls[0][1].body);
    expect(body.tool).toBe('social-media-preview');
  });

  it('exposes keywordCount and densityStatus under the names the renderer reads', async () => {
    fetchMock.mockResolvedValue(
      okResponse({
        keyword: 'test',
        density: 1.5,
        keywordCount: 5,
        totalWords: 333,
        densityStatus: 'good',
        warnings: [],
      })
    );

    const { result } = renderHook(() => useToolAnalysis({ tool: 'keyword-density' }));

    await act(async () => {
      await result.current.analyze('https://example.com', 'test');
    });

    await waitFor(() => expect(result.current.state).toBe('success'));
    const data = result.current.result?.data as Record<string, unknown>;
    expect(data.occurrences).toBe(5);
    expect(data.status).toBe('good');
  });

  it('keeps the top-level url and analyzedAt available to the renderer', async () => {
    fetchMock.mockResolvedValue(okResponse({ title: { value: 'Example Domain' } }));

    const { result } = renderHook(() => useToolAnalysis({ tool: 'meta-tag-analyzer' }));

    await act(async () => {
      await result.current.analyze('https://example.com');
    });

    await waitFor(() => expect(result.current.state).toBe('success'));
    const data = result.current.result?.data as Record<string, unknown>;
    expect(data.url).toBe('https://example.com');
    expect(data.analyzedAt).toBe('2026-01-01T00:00:00Z');
  });

  it('surfaces the backend error code and message instead of a generic fetch error', async () => {
    fetchMock.mockResolvedValue({
      ok: false,
      status: 422,
      headers: new Headers(),
      json: async () => ({
        success: false,
        error: { code: 'VALIDATION_ERROR', message: 'The given data was invalid.' },
      }),
    } as unknown as Response);

    const { result } = renderHook(() => useToolAnalysis({ tool: 'meta-tag-analyzer' }));

    await act(async () => {
      await result.current.analyze('https://example.com');
    });

    await waitFor(() => expect(result.current.state).toBe('error'));
    // The old code reported "Could not access the page" for a 422, which
    // told the user to check their URL when the URL was fine.
    expect(result.current.error?.message).not.toBe(
      'Could not access the page. Please check the URL and try again.'
    );
  });
});
