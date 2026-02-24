/**
 * TASK-12-12: Production API Routes Tests
 * 
 * Tests for verifying that /api/health and GSC endpoints are correctly routed.
 * 
 * Issue: Production endpoints returning 404 due to incorrect .htaccess routing.
 * 
 * @see TASK-12-12.md
 * @see tests/TASK-12-12.md
 */

const request = require('supertest');

// Configuration - can be overridden via environment variables
const PROD_BASE_URL = process.env.PROD_BASE_URL || 'https://seoindicator.com';
const LOCAL_BASE_URL = process.env.LOCAL_BASE_URL || 'http://localhost:8000';

describe('TASK-12-12: API Health Endpoint', () => {
  describe('Production', () => {
    const baseURL = PROD_BASE_URL;

    test('GET /api/health should return 200 with status ok', async () => {
      const response = await request(baseURL)
        .get('/api/health')
        .timeout(10000);

      // Acceptance Criteria: Returns 200 with JSON status
      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('status', 'ok');
    });

    test('GET /api/health should return JSON content type', async () => {
      const response = await request(baseURL)
        .get('/api/health')
        .timeout(10000);

      expect(response.status).toBe(200);
      expect(response.headers['content-type']).toMatch(/application\/json/);
    });

    test('GET /api/health should return database status', async () => {
      const response = await request(baseURL)
        .get('/api/health')
        .timeout(10000);

      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('database');
      expect(['connected', 'disconnected', 'unknown']).toContain(response.body.database);
    });

    test('GET /api/health should return timestamp', async () => {
      const response = await request(baseURL)
        .get('/api/health')
        .timeout(10000);

      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('timestamp');
      
      // Verify ISO8601 format
      const timestamp = response.body.timestamp;
      const parsed = new Date(timestamp);
      expect(parsed.toString()).not.toBe('Invalid Date');
    });

    test('GET /api/health should not return 404', async () => {
      const response = await request(baseURL)
        .get('/api/health')
        .timeout(10000);

      // The bug manifests as 404 - this should NOT happen
      expect(response.status).not.toBe(404);
    });
  });

  describe('Local Development', () => {
    const baseURL = LOCAL_BASE_URL;

    test('GET /api/health should return 200 (local)', async () => {
      const response = await request(baseURL)
        .get('/api/health')
        .timeout(5000);

      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('status', 'ok');
    });
  });
});

describe('TASK-12-12: GSC API Endpoints', () => {
  describe('Production - Unauthenticated Access', () => {
    const baseURL = PROD_BASE_URL;
    const testProjectId = '1';

    test('GET /api/v1/projects/{projectId}/gsc should return 401/403 (not 404)', async () => {
      const response = await request(baseURL)
        .get(`/api/v1/projects/${testProjectId}/gsc`)
        .timeout(10000);

      // Acceptance Criteria: Returns 401/403 when unauthenticated (route exists)
      // 404 means the route doesn't exist (BUG)
      expect([401, 403]).toContain(response.status);
      expect(response.status).not.toBe(404);
    });

    test('GET /api/v1/projects/{projectId}/gsc/opportunities should return 401/403 (not 404)', async () => {
      const response = await request(baseURL)
        .get(`/api/v1/projects/${testProjectId}/gsc/opportunities`)
        .timeout(10000);

      // Acceptance Criteria: Returns 401/403 when unauthenticated (route exists)
      expect([401, 403]).toContain(response.status);
      expect(response.status).not.toBe(404);
    });

    test('GSC endpoints should return JSON error response', async () => {
      const response = await request(baseURL)
        .get(`/api/v1/projects/${testProjectId}/gsc`)
        .timeout(10000);

      expect(response.headers['content-type']).toMatch(/application\/json/);
    });
  });

  describe('Local Development - Unauthenticated Access', () => {
    const baseURL = LOCAL_BASE_URL;
    const testProjectId = '1';

    test('GET /api/v1/projects/{projectId}/gsc should return 401/403 (local)', async () => {
      const response = await request(baseURL)
        .get(`/api/v1/projects/${testProjectId}/gsc`)
        .timeout(5000);

      expect([401, 403]).toContain(response.status);
    });

    test('GET /api/v1/projects/{projectId}/gsc/opportunities should return 401/403 (local)', async () => {
      const response = await request(baseURL)
        .get(`/api/v1/projects/${testProjectId}/gsc/opportunities`)
        .timeout(5000);

      expect([401, 403]).toContain(response.status);
    });
  });
});

describe('TASK-12-12: Routing Configuration Tests', () => {
  describe('Edge Cases', () => {
    const baseURL = PROD_BASE_URL;

    test('Non-existent API route should return 404', async () => {
      const response = await request(baseURL)
        .get('/api/nonexistent-route-12345')
        .timeout(10000);

      expect(response.status).toBe(404);
    });

    test('Invalid project ID should be handled gracefully', async () => {
      const response = await request(baseURL)
        .get('/api/v1/projects/invalid-uuid-xyz/gsc')
        .timeout(10000);

      // Should not be 500 server error, should be 401/403/404/422
      expect([401, 403, 404, 422]).toContain(response.status);
    });
  });
});

/**
 * Test Runner Instructions:
 * 
 * Run against production:
 *   PROD_BASE_URL=https://seoindicator.com npx jest tests/api/TASK-12-12-production-api-routes.test.js
 * 
 * Run against local:
 *   LOCAL_BASE_URL=http://localhost:8000 npx jest tests/api/TASK-12-12-production-api-routes.test.js
 * 
 * Run all:
 *   npx jest tests/api/TASK-12-12-production-api-routes.test.js
 */
