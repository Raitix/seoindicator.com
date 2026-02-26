// Unit tests for Task 1: Remove Hardcoded Secrets
const { scanForSecrets, validateEnvVars } = require("../../lib/secrets");

test("Verify no hardcoded secrets", () => {
  const repoFiles = ["file1.js", "file2.env"];
  for (const file of repoFiles) {
    expect(scanForSecrets(file)).toBe(0); // No secrets should be found
  }
});

test("Validate environment variable loading", () => {
  const validEnv = { "APP_KEY": "validKey" };
  process.env = validEnv; // Mock environment vars

  expect(validateEnvVars()).toBe(true);
});