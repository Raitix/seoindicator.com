const request = require('supertest');
const assert = require('chai').assert;
const app = require('../../src/app');

describe('POST /api/v1/sites/:siteId/reports/generate', function() {
  it('should validate required fields and return 400 for missing data', async function() {
    const res = await request(app)
      .post('/api/v1/sites/123/reports/generate')
      .send({});

    assert.equal(res.status, 400);
    assert.include(res.body.errors, 'Missing required fields');
  });

  it('should create report and return 202 Accepted', async function() {
    const res = await request(app)
      .post('/api/v1/sites/123/reports/generate')
      .send({
        name: 'Site Traffic',
        type: 'traffic',
        format: 'pdf',
        dateRange: '2023-01-01to2023-01-31',
      });

    assert.equal(res.status, 202);
    assert.isString(res.body.reportId);
    assert.equal(res.body.status, 'generating');
    assert.match(res.body.downloadUrl, /\/storage\/reports\/\w+.pdf/);
  });

  it('should handle internal errors gracefully', async function() {
    const res = await request(app)
      .post('/api/v1/sites/invalid-id/reports/generate')
      .send({
        name: 'Site Traffic',
        type: 'traffic',
        format: 'pdf',
        dateRange: '2023-01-01to2023-01-31',
      });

    assert.equal(res.status, 500);
    assert.include(res.body.error, 'Internal Server Error');
  });
});