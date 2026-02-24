const request = require('supertest');
const app = require('../server'); // Adjust import to match the API server's location

describe('API Health Check Endpoint', () => {
  it('should return 200 and status JSON object', async () => {
    const response = await request(app).get('/api/health').expect(200);
    expect(response.body).toEqual(expect.objectContaining({ status: expect.any(String) }));
  });
});