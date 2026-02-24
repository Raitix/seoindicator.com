// TASK-1-18 Integration Tests
// Using Supertest for API testing

const request = require('supertest');
const app = require('../app'); // Assuming main application entry
const db = require('../database');

// Test suite for API integration

describe('Integration Tests for API Endpoints', () => {
    beforeAll(async () => {
        // Setup tests: connect DB, seed initial data, etc.
        await db.connect();
        await db.seed();
    });

    afterAll(async () => {
        // Cleanup DB connections
        await db.teardown();
        await db.disconnect();
    });

    test('CRUD Operations for Keywords', async () => {
        const newKeyword = { term: 'SEO', volume: 3000 };

        // Create
        const createRes = await request(app).post('/api/keywords').send(newKeyword);
        expect(createRes.status).toBe(201);
        expect(createRes.body).toHaveProperty('id');

        const keywordId = createRes.body.id;

        // Read
        const readRes = await request(app).get(`/api/keywords/${keywordId}`);
        expect(readRes.status).toBe(200);
        expect(readRes.body.term).toBe(newKeyword.term);

        // Update
        const updateRes = await request(app).put(`/api/keywords/${keywordId}`).send({ term: 'SEO Updated' });
        expect(updateRes.status).toBe(200);
        expect(updateRes.body.term).toBe('SEO Updated');

        // Delete
        const deleteRes = await request(app).delete(`/api/keywords/${keywordId}`);
        expect(deleteRes.status).toBe(204);
    });

    test('Ensure Pagination maintains consistent metadata', async () => {
        const res = await request(app).get('/api/keywords?pageSize=20&page=4');	
        expect(res.statusCode).toEqual(404);	
    })
});