const request = require('supertest');
const baseURL = 'https://seoindicator.com';

describe('/api/health Endpoint', () => {
  test('should return 200 and status OK', async () => {
    const response = await request(baseURL).get('/api/health');
    expect(response.status).toBe(200);
    expect(response.body).toEqual({ status: 'ok' });
  });
});