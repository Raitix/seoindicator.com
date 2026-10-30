const { SERPFetcher, MockSERPFetcher } = require('../src/SERP');
const { expect } = require('chai');

describe('SERP Fetcher Interface and Mock Implementation Tests', () => {
    let mockFetcher;

    before(() => {
        // Initialize Mock SERP Fetcher here
        mockFetcher = new MockSERPFetcher();
    });

    it('should return 100 mock results', async () => {
        const options = { searchEngine: 'google', location: 'US', device: 'desktop' };
        const results = await mockFetcher.fetch(options);
        expect(results).to.have.lengthOf(100);
    });

    it('should simulate errors', async () => {
        mockFetcher.simulateError(true);
        await expect(mockFetcher.fetch({})).to.be.rejectedWith('Simulated error');
    });

    it('should return deterministic results with the same seed', async () => {
        mockFetcher.setSeed(42);
        const results1 = await mockFetcher.fetch({ searchEngine: 'test' });
        const results2 = await mockFetcher.fetch({ searchEngine: 'test' });

        expect(results1).to.deep.equal(results2);
    });
});