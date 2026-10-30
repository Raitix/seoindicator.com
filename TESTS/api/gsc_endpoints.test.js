const request = require('supertest');
const app = require('../server'); // Adjust import to match the API server's location

describe('GSC API Endpoints', () => {
  const projectId = '123'; // Replace with dynamic test value if necessary

  describe('Unauthenticated Access', () => {
    it('should return 401 or 403 for unauthenticated /gsc route', async () => {
      const response = await request(app).get(`/api/v1/projects/${projectId}/gsc`);
      expect([401, 403]).toContain(response.status);
    });

    it('should return 401 or 403 for unauthenticated /gsc/opportunities route', async () => {
      const response = await request(app).get(`/api/v1/projects/${projectId}/gsc/opportunities`);
      expect([401, 403]).toContain(response.status);
    });
  });
});