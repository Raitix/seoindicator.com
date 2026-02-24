const request = require('supertest');
const baseUrl = 'https://seoindicator.com';

describe('API Health & GSC Routes', () => {
  describe('/api/health', () => {
    it('should return HTTP 200 with JSON payload', async () => {
      const res = await request(baseUrl).get('/api/health');
      expect(res.status).toBe(200);
      expect(res.body).toHaveProperty('status', 'ok');
    });
  });

  describe('/api/v1/projects/:projectId/gsc', () => {
    const projectId = 'test-project-123';

    it('should return 401 or 403 when unauthenticated', async () => {
      const res = await request(baseUrl).get(`/api/v1/projects/${projectId}/gsc`);
      expect([401, 403]).toContain(res.status);
    });
    
    it('should return JSON error message for unauthenticated access', async () => {
      const res = await request(baseUrl).get(`/api/v1/projects/${projectId}/gsc`);
      expect(res.body).toHaveProperty('error');
    });
  });
});