import { readFileSync } from "node:fs";
import { join } from "node:path";

import { describe, expect, it } from "vitest";

const htaccessPath = join(process.cwd(), "public", ".htaccess");
const htaccessContents = readFileSync(htaccessPath, "utf8");

describe("frontend/public/.htaccess static-export local rankings fallbacks", () => {
  it("rewrites standalone numeric rankings routes to the exported preview shell", () => {
    expect(htaccessContents).toContain("RewriteRule ^locations/[0-9]+/rankings/?$ /locations/preview/rankings/ [L]");
  });

  it("rewrites site-scoped numeric rankings routes to the exported preview shell", () => {
    expect(htaccessContents).toContain("RewriteRule ^sites/[0-9]+/local/[0-9]+/rankings/?$ /sites/preview/local/preview/rankings/ [L]");
  });
});
