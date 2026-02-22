import { keywordCreateSchema, keywordEditSchema } from "@/lib/validators/keyword";

describe("keywordCreateSchema", () => {
  it("requires a keyword", () => {
    const result = keywordCreateSchema.safeParse({
      keyword: "",
      targetUrl: "",
      searchEngine: "google",
      location: "us",
      device: "desktop",
    });

    expect(result.success).toBe(false);
  });

  it("rejects invalid URLs", () => {
    const result = keywordCreateSchema.safeParse({
      keyword: "seo audit",
      targetUrl: "not-a-url",
      searchEngine: "google",
      location: "us",
      device: "desktop",
    });

    expect(result.success).toBe(false);
  });

  it("accepts a valid payload", () => {
    const result = keywordCreateSchema.safeParse({
      keyword: "seo audit",
      targetUrl: "https://example.com",
      searchEngine: "google",
      location: "us",
      device: "desktop",
    });

    expect(result.success).toBe(true);
  });
});

describe("keywordEditSchema", () => {
  it("requires status", () => {
    const result = keywordEditSchema.safeParse({
      targetUrl: "https://example.com",
    });

    expect(result.success).toBe(false);
  });

  it("allows empty target URL", () => {
    const result = keywordEditSchema.safeParse({
      targetUrl: "",
      status: "active",
    });

    expect(result.success).toBe(true);
  });
});
