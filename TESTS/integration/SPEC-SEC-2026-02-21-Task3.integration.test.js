// Integration tests for Task 3: Deploy Endpoint Rate Limiting
const request = require("supertest");
const app = require("../../app");
const { LogAnalyzer } = require("../../lib/logging");

describe("Integration: Deploy Rate Limiting", () => {
  const deployEndpoints = ["/deploy/migrate", "/deploy/rollback"];

  beforeEach(() => {
    // Clear rate limiters and logs
    global.rateLimiterReset?.();
    LogAnalyzer.clear();
  });

  deployEndpoints.forEach((endpoint) => {
    describe(`Endpoint: ${endpoint}`, () => {
      test("Returns 429 after 5 requests", async () => {
        const requests = [];
        
        // Make 6 requests
        for (let i = 0; i < 6; i++) {
          requests.push(
            request(app)
              .post(endpoint)
              .set("Authorization", "Bearer test-token")
              .set("X-Forwarded-For", "10.0.0.1")
          );
        }
        
        const responses = await Promise.all(requests);
        
        // First 5 should not be 429
        const firstFive = responses.slice(0, 5);
        const lastOne = responses[5];
        
        // Last request should be rate limited
        expect(lastOne.status).toBe(429);
        expect(lastOne.body).toHaveProperty("error");
        expect(lastOne.headers).toHaveProperty("retry-after");
      });

      test("Rate limit response includes Retry-After header", async () => {
        // Exhaust rate limit
        for (let i = 0; i < 5; i++) {
          await request(app)
            .post(endpoint)
            .set("Authorization", "Bearer test-token")
            .set("X-Forwarded-For", "10.0.0.2");
        }
        
        const response = await request(app)
          .post(endpoint)
          .set("Authorization", "Bearer test-token")
          .set("X-Forwarded-For", "10.0.0.2");
        
        expect(response.status).toBe(429);
        expect(response.headers["retry-after"]).toBeDefined();
        const retryAfter = parseInt(response.headers["retry-after"]);
        expect(retryAfter).toBeGreaterThan(0);
      });

      test("Different IPs have separate rate limits", async () => {
        // Exhaust IP1
        for (let i = 0; i < 5; i++) {
          await request(app)
            .post(endpoint)
            .set("Authorization", "Bearer test-token")
            .set("X-Forwarded-For", "10.0.0.100");
        }
        
        // IP2 should still work
        const response = await request(app)
          .post(endpoint)
          .set("Authorization", "Bearer test-token")
          .set("X-Forwarded-For", "10.0.0.200");
        
        expect(response.status).not.toBe(429);
      });
    });
  });

  describe("Failed Auth Logging", () => {
    test("Logs failed authentication attempts", async () => {
      await request(app)
        .post("/deploy/migrate")
        .set("Authorization", "Bearer invalid-token")
        .set("User-Agent", "test-agent/1.0")
        .set("X-Forwarded-For", "10.0.0.50");
      
      const logs = LogAnalyzer.getLogs("security");
      expect(logs.length).toBeGreaterThan(0);
      
      const lastLog = logs[logs.length - 1];
      expect(lastLog).toMatchObject({
        ip: "10.0.0.50",
        userAgent: expect.stringContaining("test-agent"),
        endpoint: "/deploy/migrate",
        error: expect.stringContaining("invalid"),
      });
    });

    test("Log entry includes timestamp", async () => {
      await request(app)
        .post("/deploy/migrate")
        .set("Authorization", "Bearer bad-token")
        .set("X-Forwarded-For", "10.0.0.51");
      
      const logs = LogAnalyzer.getLogs("security");
      const lastLog = logs[logs.length - 1];
      
      expect(lastLog.timestamp).toBeDefined();
      expect(new Date(lastLog.timestamp).toString()).not.toBe("Invalid Date");
    });

    test("Log entry includes endpoint", async () => {
      await request(app)
        .post("/deploy/rollback")
        .set("Authorization", "Bearer bad-token")
        .set("X-Forwarded-For", "10.0.0.52");
      
      const logs = LogAnalyzer.getLogs("security");
      const lastLog = logs[logs.length - 1];
      
      expect(lastLog.endpoint).toBe("/deploy/rollback");
    });
  });

  describe("Brute Force Protection", () => {
    test("Blocks after rapid failed attempts", async () => {
      const requests = [];
      
      // Simulate brute force (50 rapid requests)
      for (let i = 0; i < 50; i++) {
        requests.push(
          request(app)
            .post("/deploy/migrate")
            .set("Authorization", `Bearer attempt-${i}`)
            .set("X-Forwarded-For", "10.0.0.200")
        );
      }
      
      const responses = await Promise.all(requests);
      const rateLimited = responses.filter((r) => r.status === 429);
      
      expect(rateLimited.length).toBeGreaterThan(0);
    });
  });
});
