// Unit tests for TASK-7-5: Featured Snippet Detection
// Tests verify detectFeaturedSnippet method behavior for all acceptance criteria

import { expect } from "chai";
import {
  detectFeaturedSnippet,
  normalizeHostname
} from "../../../backend/src/services/detectors/featured-snippet-detector.js";

describe("Unit: Featured Snippet Detector", () => {
  // ========================================
  // 1. Basic Detection Logic
  // ========================================
  describe("Basic Detection", () => {
    describe("No Featured Snippet Present", () => {
      it("B-001: should return isPresent false when no answer box in SERP", () => {
        const result = detectFeaturedSnippet({}, "https://example.com");
        expect(result.type).to.equal("featured_snippet");
        expect(result.isPresent).to.be.false;
      });

      it("B-002: should return isPresent false when answerBox is undefined", () => {
        const result = detectFeaturedSnippet(
          { answerBox: undefined },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("B-003: should return isPresent false when answerBox is null", () => {
        const result = detectFeaturedSnippet(
          { answerBox: null },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("B-004: should return isPresent false when answerBox is empty object", () => {
        const result = detectFeaturedSnippet(
          { answerBox: {} },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Featured Snippet Present - Our URL", () => {
      it("B-005: should return isPresent true when our URL in answer box", () => {
        const serp = {
          answerBox: { url: "https://ours.com/page" }
        };
        const result = detectFeaturedSnippet(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });

      it("B-006: should match URL with www prefix", () => {
        const serp = {
          answerBox: { url: "https://www.ours.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });

      it("B-007: should match URL with path", () => {
        const serp = {
          answerBox: { url: "https://ours.com/seo-tools" }
        };
        const result = detectFeaturedSnippet(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });

      it("B-008: should match URL with query params", () => {
        const serp = {
          answerBox: { url: "https://ours.com/page?ref=1" }
        };
        const result = detectFeaturedSnippet(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });

      it("B-009: should match URL with trailing slash", () => {
        const serp = {
          answerBox: { url: "https://ours.com/" }
        };
        const result = detectFeaturedSnippet(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });

      it("B-010: should match when target has www but answer box doesn't", () => {
        const serp = {
          answerBox: { url: "https://ours.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://www.ours.com");
        expect(result.isPresent).to.be.true;
      });
    });

    describe("Featured Snippet Present - Competitor URL", () => {
      it("B-011: should return isPresent false for competitor URL", () => {
        const serp = {
          answerBox: { url: "https://theirs.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });

      it("B-012: should not match similar domain names", () => {
        const serp = {
          answerBox: { url: "https://ours-compare.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });

      it("B-013: should not match subdomain of competitor", () => {
        const serp = {
          answerBox: { url: "https://blog.theirs.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });
    });
  });

  // ========================================
  // 2. URL Matching Logic
  // ========================================
  describe("URL Matching Logic", () => {
    describe("Hostname Extraction", () => {
      it("U-001: should extract hostname from standard HTTPS URL", () => {
        expect(normalizeHostname("https://example.com")).to.equal("example.com");
      });

      it("U-002: should extract hostname from HTTP URL", () => {
        expect(normalizeHostname("http://example.com")).to.equal("example.com");
      });

      it("U-003: should handle www prefix", () => {
        expect(normalizeHostname("https://www.example.com")).to.equal("example.com");
      });

      it("U-004: should preserve subdomain", () => {
        expect(normalizeHostname("https://blog.example.com")).to.equal("blog.example.com");
      });

      it("U-005: should ignore path", () => {
        expect(normalizeHostname("https://example.com/page")).to.equal("example.com");
      });

      it("U-006: should ignore query params", () => {
        expect(normalizeHostname("https://example.com?a=1")).to.equal("example.com");
      });

      it("U-007: should ignore fragment", () => {
        expect(normalizeHostname("https://example.com#section")).to.equal("example.com");
      });

      it("U-008: should ignore port", () => {
        expect(normalizeHostname("https://example.com:443")).to.equal("example.com");
      });

      it("U-009: should ignore trailing slash", () => {
        expect(normalizeHostname("https://example.com/")).to.equal("example.com");
      });
    });

    describe("Domain Matching", () => {
      it("U-010: should match exact hostnames", () => {
        const serp = {
          answerBox: { url: "https://example.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.isPresent).to.be.true;
      });

      it("U-011: should match target with www and answer box without", () => {
        const serp = {
          answerBox: { url: "https://example.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://www.example.com");
        expect(result.isPresent).to.be.true;
      });

      it("U-012: should match target without www and answer box with", () => {
        const serp = {
          answerBox: { url: "https://www.example.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.isPresent).to.be.true;
      });

      it("U-013: should be case insensitive for hostname", () => {
        const serp = {
          answerBox: { url: "https://Example.COM" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.isPresent).to.be.true;
      });

      it("U-014: should ignore protocol difference", () => {
        const serp = {
          answerBox: { url: "http://example.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.isPresent).to.be.true;
      });

      it("U-015: should match same domain with different paths", () => {
        const serp = {
          answerBox: { url: "https://example.com/page2" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com/page1");
        expect(result.isPresent).to.be.true;
      });

      it("U-016: should not match subdomain with main domain", () => {
        const serp = {
          answerBox: { url: "https://blog.example.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.isPresent).to.be.false;
      });

      it("U-017: should not match main domain with subdomain", () => {
        const serp = {
          answerBox: { url: "https://example.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://blog.example.com");
        expect(result.isPresent).to.be.false;
      });
    });
  });

  // ========================================
  // 3. Answer Box Types
  // ========================================
  describe("Answer Box Types", () => {
    it("A-001: should extract all fields from paragraph snippet", () => {
      const serp = {
        answerBox: {
          type: "organic",
          url: "https://example.com",
          title: "Page Title",
          snippet: "Snippet text..."
        }
      };
      const result = detectFeaturedSnippet(serp, "https://example.com");
      expect(result.isPresent).to.be.true;
      expect(result.url).to.equal("https://example.com");
      expect(result.title).to.equal("Page Title");
      expect(result.snippet).to.equal("Snippet text...");
    });

    it("A-002: should handle paragraph snippet text", () => {
      const serp = {
        answerBox: {
          snippet: "Long paragraph text..."
        }
      };
      const result = detectFeaturedSnippet(serp, "https://example.com");
      expect(result.snippet).to.equal("Long paragraph text...");
    });

    it("A-003: should detect list type answer box", () => {
      const serp = {
        answerBox: {
          type: "list",
          list: ["item1", "item2"]
        }
      };
      const result = detectFeaturedSnippet(serp, "https://example.com");
      expect(result.type).to.equal("featured_snippet");
    });

    it("A-004: should detect list with URL", () => {
      const serp = {
        answerBox: {
          type: "list",
          url: "https://ours.com"
        }
      };
      const result = detectFeaturedSnippet(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });

    it("A-005: should detect table type answer box", () => {
      const serp = {
        answerBox: {
          type: "table",
          data: [{ col1: "val1" }]
        }
      };
      const result = detectFeaturedSnippet(serp, "https://example.com");
      expect(result.type).to.equal("featured_snippet");
    });

    it("A-006: should detect table with URL", () => {
      const serp = {
        answerBox: {
          type: "table",
          url: "https://ours.com"
        }
      };
      const result = detectFeaturedSnippet(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });

    it("A-007: should detect unknown type with URL", () => {
      const serp = {
        answerBox: {
          type: "unknown",
          url: "https://ours.com"
        }
      };
      const result = detectFeaturedSnippet(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });

    it("A-008: should detect without type field", () => {
      const serp = {
        answerBox: {
          url: "https://ours.com",
          snippet: "Text..."
        }
      };
      const result = detectFeaturedSnippet(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });
  });

  // ========================================
  // 4. Return Value Structure
  // ========================================
  describe("Return Value Structure", () => {
    describe("Required Fields", () => {
      it("R-001: should always return type field", () => {
        const result = detectFeaturedSnippet({}, "https://example.com");
        expect(result).to.have.property("type");
        expect(result.type).to.equal("featured_snippet");
      });

      it("R-002: should always return isPresent field", () => {
        const result = detectFeaturedSnippet({}, "https://example.com");
        expect(result).to.have.property("isPresent");
        expect(result.isPresent).to.be.a("boolean");
      });
    });

    describe("Optional Fields When Present", () => {
      it("R-003: should extract URL when present", () => {
        const serp = {
          answerBox: { url: "https://example.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.url).to.equal("https://example.com");
      });

      it("R-004: should extract title when present", () => {
        const serp = {
          answerBox: { title: "Page Title" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.title).to.equal("Page Title");
      });

      it("R-005: should extract snippet when present", () => {
        const serp = {
          answerBox: { snippet: "Text..." }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.snippet).to.equal("Text...");
      });

      it("R-006: should extract all optional fields", () => {
        const serp = {
          answerBox: {
            url: "https://example.com",
            title: "Title",
            snippet: "Snippet"
          }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.url).to.equal("https://example.com");
        expect(result.title).to.equal("Title");
        expect(result.snippet).to.equal("Snippet");
      });
    });

    describe("Optional Fields When Absent", () => {
      it("R-007: should not include url when absent", () => {
        const serp = {
          answerBox: { title: "Title" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.url).to.be.undefined;
      });

      it("R-008: should not include title when absent", () => {
        const serp = {
          answerBox: { url: "https://example.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.title).to.be.undefined;
      });

      it("R-009: should not include snippet when absent", () => {
        const serp = {
          answerBox: { url: "https://example.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.snippet).to.be.undefined;
      });
    });
  });

  // ========================================
  // 5. Edge Cases and Error Handling
  // ========================================
  describe("Edge Cases and Error Handling", () => {
    describe("Malformed SERP Data", () => {
      it("E-001: should handle null SERP result", () => {
        const result = detectFeaturedSnippet(null, "https://example.com");
        expect(result.type).to.equal("featured_snippet");
        expect(result.isPresent).to.be.false;
      });

      it("E-002: should handle string SERP", () => {
        const result = detectFeaturedSnippet("string", "https://example.com");
        expect(result.isPresent).to.be.false;
      });

      it("E-003: should handle string answerBox", () => {
        const result = detectFeaturedSnippet(
          { answerBox: "invalid" },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("E-004: should handle number answerBox", () => {
        const result = detectFeaturedSnippet(
          { answerBox: 123 },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("E-005: should handle array answerBox", () => {
        const result = detectFeaturedSnippet(
          { answerBox: [] },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Malformed URLs", () => {
      it("E-006: should handle invalid URL in answer box", () => {
        const serp = {
          answerBox: { url: "not-a-url" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.isPresent).to.be.false;
      });

      it("E-007: should handle relative URL in answer box", () => {
        const serp = {
          answerBox: { url: "/page" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.isPresent).to.be.false;
      });

      it("E-008: should handle empty URL string", () => {
        const serp = {
          answerBox: { url: "" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.isPresent).to.be.false;
      });

      it("E-009: should handle invalid target URL", () => {
        const serp = {
          answerBox: { url: "https://example.com" }
        };
        const result = detectFeaturedSnippet(serp, "not-a-url");
        expect(result.isPresent).to.be.false;
      });

      it("E-010: should handle empty target URL", () => {
        const serp = {
          answerBox: { url: "https://example.com" }
        };
        const result = detectFeaturedSnippet(serp, "");
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Unicode and International Domains", () => {
      it("E-011: should handle Unicode URL", () => {
        const serp = {
          answerBox: { url: "https://例え.jp" }
        };
        const result = detectFeaturedSnippet(serp, "https://例え.jp");
        expect(result.isPresent).to.be.true;
      });

      it("E-012: should handle Punycode URL", () => {
        const serp = {
          answerBox: { url: "https://xn--r8jz45g.jp" }
        };
        const result = detectFeaturedSnippet(serp, "https://xn--r8jz45g.jp");
        expect(result.isPresent).to.be.true;
      });

      it("E-013: should match Unicode target with Unicode answer", () => {
        const serp = {
          answerBox: { url: "https://例え.jp/page" }
        };
        const result = detectFeaturedSnippet(serp, "https://例え.jp");
        expect(result.isPresent).to.be.true;
      });

      it("E-014: should preserve Unicode snippet text", () => {
        const serp = {
          answerBox: { snippet: "日本語テキスト" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.snippet).to.equal("日本語テキスト");
      });
    });

    describe("Very Long Content", () => {
      it("E-015: should handle very long snippet", () => {
        const longSnippet = "a".repeat(10000);
        const serp = {
          answerBox: { snippet: longSnippet }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.snippet).to.equal(longSnippet);
      });

      it("E-016: should handle very long title", () => {
        const longTitle = "a".repeat(500);
        const serp = {
          answerBox: { title: longTitle }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.title).to.equal(longTitle);
      });

      it("E-017: should handle very long URL", () => {
        const longUrl = "https://example.com/" + "a".repeat(1950);
        const serp = {
          answerBox: { url: longUrl }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.isPresent).to.be.true;
        expect(result.url).to.equal(longUrl);
      });
    });
  });

  // ========================================
  // 6. Integration Tests
  // ========================================
  describe("Integration with SERPFeatureDetector", () => {
    it("I-001: should return SERPFeature object", () => {
      const result = detectFeaturedSnippet({}, "https://example.com");
      expect(result).to.have.property("type");
      expect(result).to.have.property("isPresent");
    });

    it("I-002: should have correct feature type", () => {
      const result = detectFeaturedSnippet({}, "https://example.com");
      expect(result.type).to.equal("featured_snippet");
    });

    it("I-003: should be compatible with feature array", () => {
      const result1 = detectFeaturedSnippet({}, "https://example.com");
      const result2 = detectFeaturedSnippet(
        { answerBox: { url: "https://example.com" } },
        "https://example.com"
      );
      const features = [result1, result2];
      expect(features).to.have.lengthOf(2);
      expect(features[0].type).to.equal("featured_snippet");
      expect(features[1].type).to.equal("featured_snippet");
    });
  });

  // ========================================
  // 7. Adversarial Tests
  // ========================================
  describe("Adversarial Tests", () => {
    describe("Security", () => {
      it("S-001: should handle XSS in snippet", () => {
        const serp = {
          answerBox: { snippet: "<script>alert(1)</script>" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.snippet).to.equal("<script>alert(1)</script>");
      });

      it("S-002: should handle XSS in title", () => {
        const serp = {
          answerBox: { title: "<img src=x onerror=alert(1)>" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result.title).to.equal("<img src=x onerror=alert(1)>");
      });

      it("S-003: should handle SQL injection in URL", () => {
        const serp = {
          answerBox: { url: "https://example.com'; DROP TABLE--" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        // Should not throw, just treat as URL string
        expect(result.type).to.equal("featured_snippet");
      });
    });

    describe("Injection Attempts", () => {
      it("S-004: should not allow prototype pollution in answerBox", () => {
        const serp = {
          answerBox: { __proto__: { polluted: true } }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result).to.not.have.property("polluted");
      });

      it("S-005: should not allow prototype pollution in SERP", () => {
        const serp = {
          __proto__: { polluted: true },
          answerBox: { url: "https://example.com" }
        };
        const result = detectFeaturedSnippet(serp, "https://example.com");
        expect(result).to.not.have.property("polluted");
      });
    });
  });
});
