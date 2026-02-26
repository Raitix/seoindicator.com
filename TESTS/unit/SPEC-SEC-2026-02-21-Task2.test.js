// Unit tests for Task 2: APP_KEY Rotation and Secrets Management
const { rotateKeys } = require("../../lib/keyRotation");

test("Verify APP_KEY rotation generates unique keys", () => {
  const oldKey = "my-old-key";
  const newKey = rotateKeys(oldKey);

  expect(newKey).not.toBe(oldKey);
  expect(typeof newKey).toBe("string");
  expect(newKey.length).toBeGreaterThan(10);
});