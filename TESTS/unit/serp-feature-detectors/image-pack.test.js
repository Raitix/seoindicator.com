// Unit tests for TASK-7-7: Image Pack Detection
// Tests verify detectImagePack method behavior for all acceptance criteria

import { expect } from "chai";
import {
  detectImagePack,
  getImagePackEntries,
  extractHostname,
  normalizeHostname
} from "../../../backend/src/services/detectors/image-pack-detector.js";

describe("Unit: Image Pack Detector", () => {
  // ========================================
  // 1. Basic Detection Logic
  // ========================================
  describe("Basic Detection", () => {
    describe("No Image Pack Present", () => {
      it("IP-001: should return isPresent false when no images in SERP", () => {
        const result = detectImagePack({}, "https://example.com");
        expect(result.type).to.equal("image_pack");
        expect(result.isPresent).to.be.false;
      });

      it("IP-002: should return isPresent false when images is undefined", () => {
        const result = detectImagePack(
          { images: undefined },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("IP-003: should return isPresent false when images is empty array", () => {
        const result = detectImagePack(
          { images: [] },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Image Pack Present - Our URL", () => {
      it("IP-004: should return isPresent true when our URL in images", () => {
        const serp = {
          images: [{ url: "https://ours.com/img.jpg" }]
        };
        const result = detectImagePack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });

      it("IP-005: should match URL with www prefix", () => {
        const serp = {
          images: [{ url: "https://www.ours.com/img.jpg" }]
        };
        const result = detectImagePack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });

      it("IP-006: should match URL with path", () => {
        const serp = {
          images: [{ url: "https://ours.com/images/photo.jpg" }]
        };
        const result = detectImagePack(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });
    });

    describe("Image Pack Present - Competitor URL", () => {
      it("IP-007: should return isPresent false for competitor URL", () => {
        const serp = {
          images: [{ url: "https://theirs.com/img.jpg" }]
        };
        const result = detectImagePack(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });
    });
  });

  // ========================================
  // 2. Position Tracking
  // ========================================
  describe("Position Tracking", () => {
    it("IP-008: should return position 1 for first image match", () => {
      const serp = {
        images: [
          { url: "https://ours.com/1.jpg" },
          { url: "https://a.com/2.jpg" }
        ]
      };
      const result = detectImagePack(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
      expect(result.position).to.equal(1);
    });

    it("IP-009: should return correct position for second image match", () => {
      const serp = {
        images: [
          { url: "https://a.com/1.jpg" },
          { url: "https://ours.com/2.jpg" }
        ]
      };
      const result = detectImagePack(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
      expect(result.position).to.equal(2);
    });
  });

  // ========================================
  // 3. Return Value Structure
  // ========================================
  describe("Return Value Structure", () => {
    it("IP-010: should always return type field", () => {
      const result = detectImagePack({}, "https://example.com");
      expect(result).to.have.property("type");
      expect(result.type).to.equal("image_pack");
    });

    it("IP-011: should always return isPresent field", () => {
      const result = detectImagePack({}, "https://example.com");
      expect(result).to.have.property("isPresent");
      expect(result.isPresent).to.be.a("boolean");
    });

    it("IP-012: should extract title when present", () => {
      const serp = {
        images: [{ url: "https://ours.com/img.jpg", title: "Cat Picture" }]
      };
      const result = detectImagePack(serp, "https://ours.com");
      expect(result.title).to.equal("Cat Picture");
    });
  });

  // ========================================
  // 4. Edge Cases
  // ========================================
  describe("Edge Cases", () => {
    it("IP-013: should handle null SERP result", () => {
      const result = detectImagePack(null, "https://example.com");
      expect(result.type).to.equal("image_pack");
      expect(result.isPresent).to.be.false;
    });

    it("IP-014: should handle string SERP", () => {
      const result = detectImagePack("string", "https://example.com");
      expect(result.isPresent).to.be.false;
    });

    it("IP-015: should handle invalid URL in image", () => {
      const serp = {
        images: [{ url: "not-a-url" }]
      };
      const result = detectImagePack(serp, "https://example.com");
      expect(result.isPresent).to.be.false;
    });

    it("IP-016: should handle invalid target URL", () => {
      const serp = {
        images: [{ url: "https://example.com/img.jpg" }]
      };
      const result = detectImagePack(serp, "not-a-url");
      expect(result.isPresent).to.be.false;
    });
  });

  // ========================================
  // 5. getImagePackEntries Helper
  // ========================================
  describe("getImagePackEntries Helper", () => {
    it("IP-017: should extract all image entries", () => {
      const serp = {
        images: [
          { url: "https://a.com/1.jpg", title: "First" },
          { url: "https://b.com/2.jpg", title: "Second" }
        ]
      };
      const entries = getImagePackEntries(serp);
      expect(entries).to.have.lengthOf(2);
      expect(entries[0].position).to.equal(1);
      expect(entries[1].position).to.equal(2);
    });

    it("IP-018: should handle empty images", () => {
      const entries = getImagePackEntries({});
      expect(entries).to.be.an("array").that.is.empty;
    });
  });

  // ========================================
  // 6. URL Matching Logic
  // ========================================
  describe("URL Matching Logic", () => {
    it("IP-019: should be case insensitive for hostname", () => {
      const serp = {
        images: [{ url: "https://Example.COM/img.jpg" }]
      };
      const result = detectImagePack(serp, "https://example.com");
      expect(result.isPresent).to.be.true;
    });

    it("IP-020: should not match subdomain with main domain", () => {
      const serp = {
        images: [{ url: "https://blog.example.com/img.jpg" }]
      };
      const result = detectImagePack(serp, "https://example.com");
      expect(result.isPresent).to.be.false;
    });
  });
});
