// E2E tests for Task 1: Remove Hardcoded Secrets
const supertest = require("supertest");
const app = require("../../server"); // Assuming Express.js or similar

describe("E2E: Secrets Hardening", () => {
  test("Application boots successfully", async () => {
    process.env = { APP_KEY: "my-super-secure-key" }; // Simulated safe config

    const response = await supertest(app).get("/");
    expect(response.statusCode).toBe(200);
    expect(response.body.message).toBe("Welcome");
  });
});