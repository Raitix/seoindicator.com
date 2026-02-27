// Unit tests for TASK-7-7: Sitelinks Detection
// Tests verify detectSitelinks method behavior for all acceptance criteria

import { expect } from "chai";
import {
  detectSitelinks,
  getSitelinks
} from "../../../backend/src/services/detectors/sitelinks-detector.js";

describe("Unit: Sitelinks Detector", () => {
  // ========================================
  // 1. Basic Detection Logic
  // ========================================
  describe("Basic Detection", () => {
    describe("No Sitelinks Present", () => {
      it("SL-001: should return isPresent false when no sitelinks in SERP", () => {
        const result = detectSitelinks({}, "https://example.com");
        expect(result.type).to.equal("sitelinks");
        expect(result.isPresent).to.be.false;
      });

      it("SL-002: should return isPresent false when no organic results", () => {
        const result = detectSitelinks(
          { organicResults: [] },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("SL-003: should return isPresent false when first result is competitor", () => {
        const serp = {
          organicResults: [{
            url: "https://theirs.com",
            sitelinks: [{ title: "About", url: "https://theirs.com/about" }]
          }]
        };
        const result = detectSitelinks(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });

      it("SL-004: should return isPresent false when sitelinks is empty", () => {
        const serp = {
          organicResults: [{
            url: "https://ours.com",
            sitelinks: []
          }]
        };
        const result = detectSitelinks(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Sitelinks Present - Our URL", () => {
      it("SL-005: should return isPresent true when first result has sitelinks", () => {
        const serp = {
          organicResults: [{
            url: "https://ours.com",
            sitelinks: [
              { title: "About Us", url: "https://ours.com/about" },
              { title: "Contact", url: "https://ours.com/contact" }
            ]
          }]
        };
        const result = detectSitelinks(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });
    });
  });

  // ========================================
  // 2. Sitelink Structure
  // ========================================
  describe("Sitelink Structure", () => {
    it("SL-006: should return sitelink count", () => {
      const serp = {
        organicResults: [{
          url: "https://ours.com",
          sitelinks: [
            { title: "About", url: "https://ours.com/about" },
            { title: "Contact", url: "https://ours.com/contact" },
            { title: "Products", url: "https://ours.com/products" },
            { title: "Blog", url: "https://ours.com/blog" },
            { title: "FAQ", url: "https://ours.com/faq" },
            { title: "Shop", url: "https://ours.com/shop" }
          ]
        }]
      };
      const result = detectSitelinks(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
      expect(result.count).to.equal(6);
    });

    it("SL-007: should extract sitelink titles and URLs", () => {
      const serp = {
        organicResults: [{
          url: "https://ours.com",
          sitelinks: [
            { title: "About Us", url: "https://ours.com/about" },
            { title: "Contact", url: "https://ours.com/contact" }
          ]
        }]
      };
      const result = detectSitelinks(serp, "https://ours.com");
      expect(result.sitelinks).to.have.lengthOf(2);
      expect(result.sitelinks[0].title).to.equal("About Us");
      expect(result.sitelinks[1].url).to.equal("https://ours.com/contact");
    });
  });

  // ========================================
  // 3. First Result Requirement
  // ========================================
  describe("First Result Requirement", () => {
    it("SL-008: should not detect sitelinks on position 2", () => {
      const serp = {
        organicResults: [
          { url: "https://competitor.com" },
          {
            url: "https://ours.com",
            sitelinks: [{ title: "About", url: "https://ours.com/about" }]
          }
        ]
      };
      const result = detectSitelinks(serp, "https://ours.com");
      expect(result.isPresent).to.be.false;
    });

    it("SL-009: should only check first result", () => {
      const serp = {
        organicResults: [
          {
            url: "https://ours.com",
            sitelinks: [{ title: "About", url: "https://ours.com/about" }]
          },
          { url: "https://other.com" }
        ]
      };
      const result = detectSitelinks(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });
  });

  // ========================================
  // 4. Return Value Structure
  // ========================================
  describe("Return Value Structure", () => {
    it("SL-010: should always return type field", () => {
      const result = detectSitelinks({}, "https://example.com");
      expect(result).to.have.property("type");
      expect(result.type).to.equal("sitelinks");
    });

    it("SL-011: should always return isPresent field", () => {
      const result = detectSitelinks({}, "https://example.com");
      expect(result).to.have.property("isPresent");
      expect(result.isPresent).to.be.a("boolean");
    });
  });

  // ========================================
  // 5. Edge Cases
  // ========================================
  describe("Edge Cases", () => {
    it("SL-012: should handle null SERP result", () => {
      const result = detectSitelinks(null, "https://example.com");
      expect(result.type).to.equal("sitelinks");
      expect(result.isPresent).to.be.false;
    });

    it("SL-013: should handle string SERP", () => {
      const result = detectSitelinks("string", "https://example.com");
      expect(result.isPresent).to.be.false;
    });

    it("SL-014: should handle invalid target URL", () => {
      const serp = {
        organicResults: [{
          url: "https://example.com",
          sitelinks: [{ title: "About", url: "https://example.com/about" }]
        }]
      };
      const result = detectSitelinks(serp, "not-a-url");
      expect(result.isPresent).to.be.false;
    });

    it("SL-015: should handle missing URL in first result", () => {
      const serp = {
        organicResults: [{
          sitelinks: [{ title: "About", url: "https://example.com/about" }]
        }]
      };
      const result = detectSitelinks(serp, "https://example.com");
      expect(result.isPresent).to.be.false;
    });
  });

  // ========================================
  // 6. getSitelinks Helper
  // ========================================
  describe("getSitelinks Helper", () => {
    it("SL-016: should extract sitelinks from first result", () => {
      const serp = {
        organicResults: [{
          url: "https://example.com",
          sitelinks: [
            { title: "About", url: "https://example.com/about" },
            { title: "Contact", url: "https://example.com/contact" }
          ]
        }]
      };
      const sitelinks = getSitelinks(serp);
      expect(sitelinks).to.have.lengthOf(2);
      expect(sitelinks[0].position).to.equal(1);
      expect(sitelinks[1].position).to.equal(2);
    });

    it("SL-017: should handle empty organic results", () => {
      const sitelinks = getSitelinks({});
      expect(sitelinks).to.be.an("array").that.is.empty;
    });
  });
});
