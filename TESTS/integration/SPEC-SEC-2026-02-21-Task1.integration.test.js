// Integration tests for Task 1: Remove Hardcoded Secrets
const { loadSecrets } = require("../../lib/appStartup");

describe("Secrets and Application Startup", () => {
  test("Secure failure on missing env vars", () => {
    process.env = { APP_KEY: "" }; // Deliberate invalid config

    expect(() => loadSecrets()).toThrow("APP_KEY is missing");
  });

  test("Application startup with safe config", () => {
    process.env = { APP_KEY: "secureKeyContent" };

    expect(() => loadSecrets()).not.toThrow();
  });
});