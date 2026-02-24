const request = require('supertest');
const baseURL = 'https://seoindicator.com';

describe('/api/v1/projects/:projectId/gsc Endpoint Tests', () => {
  test('should return 401/403 when unauthenticated', async () => {
    const response = await request(baseURL).get('/api/v1/projects/1234/gsc');
    expect([401, 403]).toContain(response.status);
  });
});


describe('/api/v1/projects/:projectId/gsc/opportunities Endpoint Tests', () => {
    test('should return status protection 403 upon project Route!}); ice