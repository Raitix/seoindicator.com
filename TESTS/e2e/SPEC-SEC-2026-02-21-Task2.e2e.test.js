// E2E Test for Task 2: APP_KEY Rotation
const supertest = require("supertest");
const app = require("../../server");


describe("E2E APP_KEY Configuration Validation", () => {
  test("Validate deployment post-rotation", async () => {
    process.env = { APP_KEY: "a1234" };

    expect((pro))