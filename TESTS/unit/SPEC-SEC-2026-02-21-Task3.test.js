// Unit tests for Task 3: Deploy Endpoint Rate Limiting
const { RateLimiter } = require("../../lib/rateLimiter");

describe("Unit: Deploy Rate Limiting", () => {
  beforeEach(() => {
    // Reset rate limiter state
    RateLimiter.reset();
  });

  test("Rate limiter allows up to 5 requests per 15 minutes", () => {
    const ip = "192.168.1.1";
    
    for (let i = 0; i < 5; i++) {
      expect(RateLimiter.check(ip, "deploy")).toBe(true);
      RateLimiter.increment(ip, "deploy");
    }
    
    // 6th request should be blocked
    expect(RateLimiter.check(ip, "deploy")).toBe(false);
  });

  test("Rate limits are independent per endpoint", () => {
    const ip = "192.168.1.1";
    
    // Exhaust migrate endpoint
    for (let i = 0; i < 5; i++) {
      RateLimiter.increment(ip, "deploy/migrate");
    }
    
    // Rollback endpoint should still be accessible
    expect(RateLimiter.check(ip, "deploy/rollback")).toBe(true);
  });

  test("Rate limits are independent per IP", () => {
    const ip1 = "192.168.1.1";
    const ip2 = "192.168.1.2";
    
    // Exhaust IP1
    for (let i = 0; i < 5; i++) {
      RateLimiter.increment(ip1, "deploy");
    }
    
    // IP2 should still be allowed
    expect(RateLimiter.check(ip2, "deploy")).toBe(true);
  });

  test("Rate limit resets after window expires", () => {
    const ip = "192.168.1.1";
    
    // Exhaust limit
    for (let i = 0; i < 5; i++) {
      RateLimiter.increment(ip, "deploy");
    }
    
    // Simulate time passing (15 minutes)
    RateLimiter.expireWindow(ip, "deploy");
    
    // Should be allowed again
    expect(RateLimiter.check(ip, "deploy")).toBe(true);
  });

  test("Retry-After header calculation", () => {
    const ip = "192.168.1.1";
    
    for (let i = 0; i < 5; i++) {
      RateLimiter.increment(ip, "deploy");
    }
    
    const retryAfter = RateLimiter.getRetryAfter(ip, "deploy");
    expect(retryAfter).toBeGreaterThan(0);
    expect(retryAfter).toBeLessThanOrEqual(900); // 15 minutes in seconds
  });
});
