// Integration tests for Task 2: APP_KEY Rotation
const { rotateKeys, validateKeyRotation } = require("../../lib/keyRotation");

describe("Integration: APP_KEY Rotation", () => {
  test("Rotation sequence validation", () => {
    const oldKey = "oldKey!Secret";
    const newKey = rotateKeys(oldKey);

    expect(validateKeyRotation(oldKey, newKey)).toBe(true);
  });
});