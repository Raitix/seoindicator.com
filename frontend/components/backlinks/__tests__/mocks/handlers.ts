/**
 * MSW Handlers for Backlinks API
 * Task: TASK-23-13 - Backlinks Dashboard Components
 *
 * Mock Service Worker handlers for testing backlinks API calls.
 */

import { http, HttpResponse, delay } from 'msw';

// ============================================================================
// Mock Data
// ============================================================================

export const mockBacklinkSummary = {
  totalBacklinks: 1250,
  referringDomains: 89,
  dofollowRatio: 0.72,
  avgDomainTrust: 45.2,
  growthRate: 12.5,
  profileScore: 68.5,
};

export const mockReferringDomains = [
  {
    domain: 'example.com',
    backlinks_count: 45,
    domain_trust_score: 72,
    first_seen_at: '2026-01-15T10:00:00Z',
    last_seen_at: '2026-02-28T15:30:00Z',
  },
  {
    domain: 'blog.example.org',
    backlinks_count: 23,
    domain_trust_score: 55,
    first_seen_at: '2026-02-01T08:00:00Z',
    last_seen_at: null,
  },
  {
    domain: 'techblog.io',
    backlinks_count: 18,
    domain_trust_score: 88,
    first_seen_at: '2025-12-01T12:00:00Z',
    last_seen_at: '2026-02-15T09:00:00Z',
  },
  {
    domain: 'news.site',
    backlinks_count: 12,
    domain_trust_score: 45,
    first_seen_at: '2026-01-20T14:00:00Z',
    last_seen_at: '2026-02-10T11:00:00Z',
  },
  {
    domain: 'forum.community',
    backlinks_count: 8,
    domain_trust_score: 32,
    first_seen_at: '2026-02-05T16:00:00Z',
    last_seen_at: null,
  },
];

export const mockAnchorTexts = [
  { anchor_text: 'Brand Name', backlinks_count: 280, percentage: 22.4, is_branded: true, is_exact_match: false },
  { anchor_text: 'seo tools', backlinks_count: 150, percentage: 12.0, is_branded: false, is_exact_match: true },
  { anchor_text: 'click here', backlinks_count: 100, percentage: 8.0, is_branded: false, is_exact_match: false },
  { anchor_text: 'www.example.com', backlinks_count: 85, percentage: 6.8, is_branded: true, is_exact_match: false },
  { anchor_text: 'best seo software', backlinks_count: 75, percentage: 6.0, is_branded: false, is_exact_match: true },
];

export const mockBacklinkHistory = [
  { date: '2026-01-01', backlinks: 1100, domains: 75 },
  { date: '2026-01-08', backlinks: 1120, domains: 77 },
  { date: '2026-01-15', backlinks: 1150, domains: 80 },
  { date: '2026-01-22', backlinks: 1170, domains: 82 },
  { date: '2026-02-01', backlinks: 1200, domains: 85 },
  { date: '2026-02-08', backlinks: 1220, domains: 87 },
  { date: '2026-02-15', backlinks: 1240, domains: 88 },
  { date: '2026-02-22', backlinks: 1250, domains: 89 },
];

export const mockSyncResponse = {
  jobId: 'backlink-sync-abc123',
  status: 'queued',
  estimatedTime: 30,
};

// ============================================================================
// Handlers
// ============================================================================

export const backlinksHandlers = [
  // Get overview/summary
  http.get('/api/v1/projects/:projectId/backlinks', async ({ params }) => {
    await delay(100);
    const { projectId } = params;
    
    // Return 404 for non-existent project
    if (projectId === '999999') {
      return new HttpResponse(null, { status: 404 });
    }
    
    return HttpResponse.json({
      success: true,
      data: {
        summary: mockBacklinkSummary,
        recentBacklinks: mockReferringDomains.slice(0, 3),
        topReferringDomains: mockReferringDomains.slice(0, 5),
      },
    });
  }),

  // Get referring domains with pagination
  http.get('/api/v1/projects/:projectId/backlinks/domains', async ({ request, params }) => {
    await delay(50);
    const url = new URL(request.url);
    const page = parseInt(url.searchParams.get('page') || '1');
    const perPage = parseInt(url.searchParams.get('perPage') || '20');
    const sort = url.searchParams.get('sort') || 'backlinks_count';
    const order = url.searchParams.get('order') || 'desc';

    let sortedDomains = [...mockReferringDomains];
    
    // Sort
    if (sort === 'domain') {
      sortedDomains.sort((a, b) => a.domain.localeCompare(b.domain));
    } else if (sort === 'domain_trust_score') {
      sortedDomains.sort((a, b) => b.domain_trust_score - a.domain_trust_score);
    } else {
      sortedDomains.sort((a, b) => b.backlinks_count - a.backlinks_count);
    }
    
    if (order === 'asc') {
      sortedDomains.reverse();
    }

    // Paginate
    const start = (page - 1) * perPage;
    const paginatedDomains = sortedDomains.slice(start, start + perPage);

    return HttpResponse.json({
      data: paginatedDomains,
      meta: {
        total: mockReferringDomains.length,
        page,
        perPage,
        totalPages: Math.ceil(mockReferringDomains.length / perPage),
      },
    });
  }),

  // Get anchor text distribution
  http.get('/api/v1/projects/:projectId/backlinks/anchors', async () => {
    await delay(50);
    return HttpResponse.json({
      data: mockAnchorTexts,
    });
  }),

  // Get backlink history
  http.get('/api/v1/projects/:projectId/backlinks/history', async ({ request }) => {
    await delay(50);
    const url = new URL(request.url);
    const from = url.searchParams.get('from') || '2026-01-01';
    const to = url.searchParams.get('to') || '2026-02-28';

    // Filter by date range
    const filtered = mockBacklinkHistory.filter(item => {
      return item.date >= from && item.date <= to;
    });

    return HttpResponse.json({
      data: filtered,
      meta: { from, to },
    });
  }),

  // Trigger sync
  http.post('/api/v1/projects/:projectId/backlinks/sync', async () => {
    await delay(100);
    return HttpResponse.json(mockSyncResponse);
  }),
];

// Error handlers for testing error states
export const backlinksErrorHandlers = [
  http.get('/api/v1/projects/:projectId/backlinks', () => {
    return new HttpResponse(null, { status: 500 });
  }),
];

// Empty data handlers for testing empty states
export const backlinksEmptyHandlers = [
  http.get('/api/v1/projects/:projectId/backlinks', () => {
    return HttpResponse.json({
      success: true,
      data: {
        summary: {
          totalBacklinks: 0,
          referringDomains: 0,
          dofollowRatio: 0,
          avgDomainTrust: 0,
          growthRate: 0,
          profileScore: 0,
        },
        recentBacklinks: [],
        topReferringDomains: [],
      },
    });
  }),
  http.get('/api/v1/projects/:projectId/backlinks/domains', () => {
    return HttpResponse.json({
      data: [],
      meta: { total: 0, page: 1, perPage: 20, totalPages: 0 },
    });
  }),
  http.get('/api/v1/projects/:projectId/backlinks/anchors', () => {
    return HttpResponse.json({ data: [] });
  }),
];
