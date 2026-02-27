// Unit tests for TASK-7-6: Local Pack Detection
// Tests verify detectLocalPack method behavior for all acceptance criteria

import { expect } from "chai";
import {
  detectLocalPack,
  getLocalPackEntries,
  extractHostname,
  normalizeHostname
} from "../../../backend/src/services/detectors/local-pack-detector.js";

describe("Unit: Local Pack Detector", () => {
  // ========================================
  // 1. Basic Detection Logic
  // ========================================
  describe("Basic Detection", () => {
    describe("No Local Pack Present", () => {
      it("B-001: should return isPresent false when no local pack in SERP", () => {
        const result = detectLocalPack({}, "https://example.com");
        expect(result.type).to.equal("local_pack");
        expect(result.isPresent).to.be.false;
      });

      it("B-002: should return isPresent false when localPack is undefined", () => {
        const result = detectLocalPack(
          { localPack: undefined },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("B-003: should return isPresent false when localPack is null", () => {
        const result = detectLocalPack(
          { localPack: null },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("B-004: should return isPresent false when localPack is empty array", () => {
        const result = detectLocalPack(
          { localPack: [] },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Local Pack Present - Our URL", () => {
      it("B-005: should return isPresent true when our URL in position 1", () => {
        const serp = {
          localPack: [{ url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
        expect(result.position).to.equal(1);
      });

      it("B-006: should return isPresent true when our URL in position 2", () => {
        const serp = {
          localPack: [{ url: "https://other.com" }, { url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
        expect(result.position).to.equal(2);
      });

      it("B-007: should return isPresent true when our URL in position 3", () => {
        const serp = {
          localPack: [
            { url: "https://a.com" },
            { url: "https://b.com" },
            { url: "https://ours.com" }
          ]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
        expect(result.position).to.equal(3);
      });

      it("B-008: should match URL with www prefix", () => {
        const serp = {
          localPack: [{ url: "https://www.ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });

      it("B-009: should match URL with path", () => {
        const serp = {
          localPack: [{ url: "https://ours.com/contact" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });

      it("B-010: should match URL with query params", () => {
        const serp = {
          localPack: [{ url: "https://ours.com?loc=nyc" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });
    });

    describe("Local Pack Present - Competitor URL", () => {
      it("B-011: should return isPresent false when all 3 are competitors", () => {
        const serp = {
          localPack: [
            { url: "https://a.com" },
            { url: "https://b.com" },
            { url: "https://c.com" }
          ]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });

      it("B-012: should not match similar domain names", () => {
        const serp = {
          localPack: [{ url: "https://ours-compare.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });

      it("B-013: should not match subdomain of competitor", () => {
        const serp = {
          localPack: [{ url: "https://blog.theirs.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });
    });
  });

  // ========================================
  // 2. Position Tracking
  // ========================================
  describe("Position Tracking", () => {
    describe("Position Within Local Pack", () => {
      it("P-001: should return position 1 for first entry", () => {
        const serp = {
          localPack: [{ url: "https://ours.com" }, {}, {}]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.position).to.equal(1);
      });

      it("P-002: should return position 2 for second entry", () => {
        const serp = {
          localPack: [{ url: "https://a.com" }, { url: "https://ours.com" }, {}]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.position).to.equal(2);
      });

      it("P-003: should return position 3 for third entry", () => {
        const serp = {
          localPack: [
            { url: "https://a.com" },
            { url: "https://b.com" },
            { url: "https://ours.com" }
          ]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.position).to.equal(3);
      });

      it("P-004: should return no position when not present", () => {
        const serp = {
          localPack: [{ url: "https://a.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.position).to.be.undefined;
      });

      it("P-005: should return 1-indexed position (not 0-indexed)", () => {
        const serp = {
          localPack: [{ url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.position).to.equal(1);
        expect(result.position).to.not.equal(0);
      });
    });

    describe("Position with Mixed Results", () => {
      it("P-006: should return position 1 when our URL is first of three", () => {
        const serp = {
          localPack: [
            { url: "https://ours.com" },
            { url: "https://a.com" },
            { url: "https://b.com" }
          ]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.position).to.equal(1);
      });

      it("P-007: should return position 2 when our URL is second of three", () => {
        const serp = {
          localPack: [
            { url: "https://a.com" },
            { url: "https://ours.com" },
            { url: "https://b.com" }
          ]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.position).to.equal(2);
      });

      it("P-008: should return position 3 when our URL is last of three", () => {
        const serp = {
          localPack: [
            { url: "https://a.com" },
            { url: "https://b.com" },
            { url: "https://ours.com" }
          ]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.position).to.equal(3);
      });
    });
  });

  // ========================================
  // 3. URL Matching Logic
  // ========================================
  describe("URL Matching Logic", () => {
    describe("Hostname Extraction", () => {
      it("U-001: should extract hostname from standard HTTPS URL", () => {
        expect(extractHostname("https://example.com")).to.equal("example.com");
      });

      it("U-002: should extract hostname from HTTP URL", () => {
        expect(extractHostname("http://example.com")).to.equal("example.com");
      });

      it("U-003: should handle www prefix", () => {
        expect(normalizeHostname("www.example.com")).to.equal("example.com");
      });

      it("U-004: should preserve subdomain in extraction", () => {
        expect(extractHostname("https://blog.example.com")).to.equal("blog.example.com");
      });

      it("U-005: should ignore path", () => {
        expect(extractHostname("https://example.com/page")).to.equal("example.com");
      });

      it("U-006: should ignore query params", () => {
        expect(extractHostname("https://example.com?a=1")).to.equal("example.com");
      });

      it("U-007: should ignore port", () => {
        expect(extractHostname("https://example.com:443")).to.equal("example.com");
      });
    });

    describe("Domain Matching in Local Pack", () => {
      it("U-008: should match exact domains", () => {
        const serp = {
          localPack: [{ url: "https://example.com" }]
        };
        const result = detectLocalPack(serp, "https://example.com");
        expect(result.isPresent).to.be.true;
      });

      it("U-009: should match target with www and entry without", () => {
        const serp = {
          localPack: [{ url: "https://example.com" }]
        };
        const result = detectLocalPack(serp, "https://www.example.com");
        expect(result.isPresent).to.be.true;
      });

      it("U-010: should match target without www and entry with", () => {
        const serp = {
          localPack: [{ url: "https://www.example.com" }]
        };
        const result = detectLocalPack(serp, "https://example.com");
        expect(result.isPresent).to.be.true;
      });

      it("U-011: should be case insensitive for hostname", () => {
        const serp = {
          localPack: [{ url: "https://Example.COM" }]
        };
        const result = detectLocalPack(serp, "https://example.com");
        expect(result.isPresent).to.be.true;
      });

      it("U-012: should ignore protocol difference", () => {
        const serp = {
          localPack: [{ url: "http://example.com" }]
        };
        const result = detectLocalPack(serp, "https://example.com");
        expect(result.isPresent).to.be.true;
      });

      it("U-013: should match same domain with different paths", () => {
        const serp = {
          localPack: [{ url: "https://example.com/contact" }]
        };
        const result = detectLocalPack(serp, "https://example.com/page1");
        expect(result.isPresent).to.be.true;
      });

      it("U-014: should not match subdomain with main domain", () => {
        const serp = {
          localPack: [{ url: "https://blog.example.com" }]
        };
        const result = detectLocalPack(serp, "https://example.com");
        expect(result.isPresent).to.be.false;
      });

      it("U-015: should not match main domain with subdomain", () => {
        const serp = {
          localPack: [{ url: "https://example.com" }]
        };
        const result = detectLocalPack(serp, "https://blog.example.com");
        expect(result.isPresent).to.be.false;
      });
    });
  });

  // ========================================
  // 4. Partial Local Pack Data
  // ========================================
  describe("Partial Local Pack Data", () => {
    describe("Fewer Than 3 Results", () => {
      it("PL-001: should detect single result with our URL", () => {
        const serp = {
          localPack: [{ url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
        expect(result.position).to.equal(1);
      });

      it("PL-002: should detect single result with competitor URL", () => {
        const serp = {
          localPack: [{ url: "https://theirs.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });

      it("PL-003: should detect our URL first of two", () => {
        const serp = {
          localPack: [{ url: "https://ours.com" }, { url: "https://a.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
        expect(result.position).to.equal(1);
      });

      it("PL-004: should detect our URL second of two", () => {
        const serp = {
          localPack: [{ url: "https://a.com" }, { url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
        expect(result.position).to.equal(2);
      });

      it("PL-005: should return false when neither is ours", () => {
        const serp = {
          localPack: [{ url: "https://a.com" }, { url: "https://b.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Missing URL in Entry", () => {
      it("PL-006: should skip entry without URL property", () => {
        const serp = {
          localPack: [{ name: "Business" }, { url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
        expect(result.position).to.equal(2);
      });

      it("PL-007: should skip entry with null URL", () => {
        const serp = {
          localPack: [{ url: null }, { url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
        expect(result.position).to.equal(2);
      });

      it("PL-008: should skip entry with empty URL string", () => {
        const serp = {
          localPack: [{ url: "" }, { url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
        expect(result.position).to.equal(2);
      });

      it("PL-009: should match at correct position with mixed entries", () => {
        const serp = {
          localPack: [{ name: "Business A" }, { url: "https://ours.com" }, { url: "https://b.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
        expect(result.position).to.equal(2);
      });
    });
  });

  // ========================================
  // 5. Local Pack Entry Structure
  // ========================================
  describe("Local Pack Entry Structure", () => {
    it("E-001: should use URL for matching, ignore other fields", () => {
      const serp = {
        localPack: [{ name: "Business", url: "https://ours.com" }]
      };
      const result = detectLocalPack(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });

    it("E-002: should ignore rating field", () => {
      const serp = {
        localPack: [{ url: "https://ours.com", rating: 4.5 }]
      };
      const result = detectLocalPack(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });

    it("E-003: should ignore address field", () => {
      const serp = {
        localPack: [{ url: "https://ours.com", address: "123 St" }]
      };
      const result = detectLocalPack(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });

    it("E-004: should ignore phone field", () => {
      const serp = {
        localPack: [{ url: "https://ours.com", phone: "555-1234" }]
      };
      const result = detectLocalPack(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });

    it("E-005: should handle entry with all fields", () => {
      const serp = {
        localPack: [{
          name: "Business",
          url: "https://ours.com",
          rating: 4.5,
          reviewCount: 250,
          address: "123 Main St",
          phone: "(555) 123-4567"
        }]
      };
      const result = detectLocalPack(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });
  });

  // ========================================
  // 6. Return Value Structure
  // ========================================
  describe("Return Value Structure", () => {
    describe("Required Fields", () => {
      it("R-001: should always return type field", () => {
        const result = detectLocalPack({}, "https://example.com");
        expect(result).to.have.property("type");
        expect(result.type).to.equal("local_pack");
      });

      it("R-002: should always return isPresent field", () => {
        const result = detectLocalPack({}, "https://example.com");
        expect(result).to.have.property("isPresent");
        expect(result.isPresent).to.be.a("boolean");
      });
    });

    describe("Optional Position Field", () => {
      it("R-003: should include position when present", () => {
        const serp = {
          localPack: [{ url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.position).to.be.a("number");
        expect(result.position).to.be.at.least(1);
        expect(result.position).to.be.at.most(3);
      });

      it("R-004: should not include position when absent", () => {
        const serp = {
          localPack: [{ url: "https://theirs.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.position).to.be.undefined;
      });

      it("R-005: should return integer position", () => {
        const serp = {
          localPack: [{ url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(Number.isInteger(result.position)).to.be.true;
      });
    });
  });

  // ========================================
  // 7. Edge Cases and Error Handling
  // ========================================
  describe("Edge Cases and Error Handling", () => {
    describe("Malformed SERP Data", () => {
      it("EC-001: should handle null SERP result", () => {
        const result = detectLocalPack(null, "https://example.com");
        expect(result.type).to.equal("local_pack");
        expect(result.isPresent).to.be.false;
      });

      it("EC-002: should handle SERP as string", () => {
        const result = detectLocalPack("string", "https://example.com");
        expect(result.isPresent).to.be.false;
      });

      it("EC-003: should handle localPack as string", () => {
        const result = detectLocalPack(
          { localPack: "invalid" },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("EC-004: should handle localPack as number", () => {
        const result = detectLocalPack(
          { localPack: 123 },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("EC-005: should handle localPack as object (not array)", () => {
        const result = detectLocalPack(
          { localPack: {} },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Malformed URLs", () => {
      it("EC-006: should handle invalid URL in local pack entry", () => {
        const serp = {
          localPack: [{ url: "not-a-url" }]
        };
        const result = detectLocalPack(serp, "https://example.com");
        expect(result.isPresent).to.be.false;
      });

      it("EC-007: should handle relative URL in local pack entry", () => {
        const serp = {
          localPack: [{ url: "/page" }]
        };
        const result = detectLocalPack(serp, "https://example.com");
        expect(result.isPresent).to.be.false;
      });

      it("EC-008: should handle empty URL string", () => {
        const serp = {
          localPack: [{ url: "" }]
        };
        const result = detectLocalPack(serp, "https://example.com");
        expect(result.isPresent).to.be.false;
      });

      it("EC-009: should handle invalid target URL", () => {
        const serp = {
          localPack: [{ url: "https://example.com" }]
        };
        const result = detectLocalPack(serp, "not-a-url");
        expect(result.isPresent).to.be.false;
      });

      it("EC-010: should handle empty target URL", () => {
        const serp = {
          localPack: [{ url: "https://example.com" }]
        };
        const result = detectLocalPack(serp, "");
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Unicode and International Domains", () => {
      it("EC-011: should handle Unicode URL", () => {
        const serp = {
          localPack: [{ url: "https://例え.jp" }]
        };
        const result = detectLocalPack(serp, "https://例え.jp");
        expect(result.isPresent).to.be.true;
      });

      it("EC-012: should handle Punycode URL", () => {
        const serp = {
          localPack: [{ url: "https://xn--r8jz45g.jp" }]
        };
        const result = detectLocalPack(serp, "https://xn--r8jz45g.jp");
        expect(result.isPresent).to.be.true;
      });

      it("EC-013: should match Unicode target with Unicode entry", () => {
        const serp = {
          localPack: [{ url: "https://例え.jp/page" }]
        };
        const result = detectLocalPack(serp, "https://例え.jp");
        expect(result.isPresent).to.be.true;
      });

      it("EC-014: should handle business name with emoji", () => {
        const serp = {
          localPack: [{ name: "🍕 Pizza Place", url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });
    });
  });

  // ========================================
  // 8. Integration with SERPFeatureDetector
  // ========================================
  describe("Integration with SERPFeatureDetector", () => {
    it("I-001: should return SERPFeature object", () => {
      const result = detectLocalPack({}, "https://example.com");
      expect(result).to.have.property("type");
      expect(result).to.have.property("isPresent");
    });

    it("I-002: should have correct feature type", () => {
      const result = detectLocalPack({}, "https://example.com");
      expect(result.type).to.equal("local_pack");
    });

    it("I-003: should be compatible with feature array", () => {
      const result1 = detectLocalPack({}, "https://example.com");
      const result2 = detectLocalPack(
        { localPack: [{ url: "https://example.com" }] },
        "https://example.com"
      );
      const features = [result1, result2];
      expect(features).to.have.lengthOf(2);
      expect(features[0].type).to.equal("local_pack");
      expect(features[1].type).to.equal("local_pack");
    });

    it("I-004: should work alongside featured snippet", () => {
      // Simulate both detectors running
      const featuredResult = { type: "featured_snippet", isPresent: true };
      const localPackResult = detectLocalPack(
        { localPack: [{ url: "https://ours.com" }] },
        "https://ours.com"
      );
      const features = [featuredResult, localPackResult];
      expect(features).to.have.lengthOf(2);
      expect(features[0].type).to.equal("featured_snippet");
      expect(features[1].type).to.equal("local_pack");
    });
  });

  // ========================================
  // 9. Adversarial Tests
  // ========================================
  describe("Adversarial Tests", () => {
    describe("Security", () => {
      it("S-001: should handle XSS in name (stored as-is)", () => {
        const serp = {
          localPack: [{ name: "<script>alert(1)</script>", url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });

      it("S-002: should handle javascript: URL (treated as string)", () => {
        const serp = {
          localPack: [{ url: "javascript:alert(1)" }]
        };
        const result = detectLocalPack(serp, "https://example.com");
        expect(result.isPresent).to.be.false;
      });

      it("S-003: should handle SQL injection in URL (treated as string)", () => {
        const serp = {
          localPack: [{ url: "https://example.com'; DROP TABLE--" }]
        };
        const result = detectLocalPack(serp, "https://example.com");
        // Should not throw, just treat as URL string
        expect(result.type).to.equal("local_pack");
      });
    });

    describe("Injection Attempts", () => {
      it("S-004: should not allow prototype pollution in entry", () => {
        const serp = {
          localPack: [{ __proto__: { polluted: true }, url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result).to.not.have.property("polluted");
        expect(result.isPresent).to.be.true;
      });

      it("S-005: should not allow prototype pollution in SERP", () => {
        const serp = {
          __proto__: { polluted: true },
          localPack: [{ url: "https://ours.com" }]
        };
        const result = detectLocalPack(serp, "https://ours.com");
        expect(result).to.not.have.property("polluted");
      });

      it("S-006: should handle very long URL without error", () => {
        const longUrl = "https://example.com/" + "a".repeat(10000);
        const serp = {
          localPack: [{ url: longUrl }]
        };
        const result = detectLocalPack(serp, "https://example.com");
        expect(result.isPresent).to.be.true;
      });
    });
  });

  // ========================================
  // 10. getLocalPackEntries Helper
  // ========================================
  describe("getLocalPackEntries Helper", () => {
    it("should return empty array for invalid SERP", () => {
      expect(getLocalPackEntries(null)).to.deep.equal([]);
      expect(getLocalPackEntries({})).to.deep.equal([]);
      expect(getLocalPackEntries({ localPack: null })).to.deep.equal([]);
    });

    it("should extract all entries with positions", () => {
      const serp = {
        localPack: [
          { name: "Biz A", url: "https://a.com" },
          { name: "Biz B", url: "https://b.com" }
        ]
      };
      const entries = getLocalPackEntries(serp);
      expect(entries).to.have.lengthOf(2);
      expect(entries[0].position).to.equal(1);
      expect(entries[1].position).to.equal(2);
    });

    it("should include all available fields", () => {
      const serp = {
        localPack: [{
          name: "Business",
          url: "https://example.com",
          rating: 4.5,
          reviewCount: 100,
          address: "123 St",
          phone: "555-1234"
        }]
      };
      const entries = getLocalPackEntries(serp);
      expect(entries[0].name).to.equal("Business");
      expect(entries[0].url).to.equal("https://example.com");
      expect(entries[0].rating).to.equal(4.5);
      expect(entries[0].reviewCount).to.equal(100);
      expect(entries[0].address).to.equal("123 St");
      expect(entries[0].phone).to.equal("555-1234");
    });
  });
});
