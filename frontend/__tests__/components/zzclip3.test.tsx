import { describe, expect, it, vi } from "vitest";

const mockWrite = vi.fn();

Object.assign(navigator, { clipboard: { writeText: mockWrite } });

describe("clip probe3", () => {
  it("module-scope assignment visible at test time", () => {
    console.log("identity:", navigator.clipboard?.writeText === mockWrite);
    console.log("own desc:", JSON.stringify(Object.getOwnPropertyDescriptor(navigator, "clipboard")));
    console.log("globalThis.navigator === navigator:", globalThis.navigator === navigator);
    console.log("globalThis.navigator.clipboard:", typeof globalThis.navigator.clipboard);
    expect(true).toBe(true);
  });
});
