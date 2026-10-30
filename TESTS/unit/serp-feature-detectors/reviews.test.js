// Unit tests for TASK-7-7: Reviews Detection
// Tests verify detectReviews method behavior for all acceptance criteria

import { expect } from "chai";
import {
  detectReviews,
  getResultsWithReviews
} from "../../../backend/src/services/detectors/reviews-detector.js";

describe("Unit: Reviews Detector", () => {
  // ========================================
  // 1. Basic Detection Logic
  // ========================================
  describe("Basic Detection", () => {
    describe("No Reviews Present", () => {
      it("R-001: should return isPresent false when no reviews in SERP", () => {
        const result = detectReviews({}, "https://example.com");
        expect(result.type).to.equal("reviews");
        expect(result.isPresent).to.be.false;
      });

      it("R-002: should return isPresent false when no organic results", () => {
        const result = detectReviews(
          { organicResults: [] },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("R-003: should return isPresent false when result has no reviews", () => {
        const serp = {
          organicResults: [{ url: "https://ours.com", title: "Our Site" }]
        };
        const result = detectReviews(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Reviews Present - Our URL", () => {
      it("R-004: should return isPresent true when our result has reviews", () => {
        const serp = {
          organicResults: [{
            url: "https://ours.com",
            reviews: { rating: 4.5 }
          }]
        };
        const result = detectReviews(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });
    });

    describe("Reviews Present - Competitor URL", () => {
      it("R-005: should return isPresent false for competitor reviews", () => {
        const serp = {
          organicResults: [{
            url: "https://theirs.com",
            reviews: { rating: 4.8 }
          }]
        };
        const result = detectReviews(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });
    });
  });

  // ========================================
  // 2. Rating Extraction
  // ========================================
  describe("Rating Extraction", () => {
    it("R-006: should extract rating value", () => {
      const serp = {
        organicResults: [{
          url: "https://ours.com",
          reviews: { rating: 4.7 }
        }]
      };
      const result = detectReviews(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
      expect(result.rating).to.equal(4.7);
    });

    it("R-007: should extract review count", () => {
      const serp = {
        organicResults: [{
          url: "https://ours.com",
          reviews: { rating: 4.5, count: 250 }
        }]
      };
      const result = detectReviews(serp, "https://ours.com");
      expect(result.count).to.equal(250);
    });

    it("R-008: should extract rating source", () => {
      const serp = {
        organicResults: [{
          url: "https://ours.com",
          reviews: { rating: 4.5, source: "Trustpilot" }
        }]
      };
      const result = detectReviews(serp, "https://ours.com");
      expect(result.source).to.equal("Trustpilot");
    });
  });

  // ========================================
  // 3. Rating Validation
  // ========================================
  describe("Rating Validation", () => {
    it("R-009: should clamp rating over 5 to 5", () => {
      const serp = {
        organicResults: [{
          url: "https://ours.com",
          reviews: { rating: 6.0 }
        }]
      };
      const result = detectReviews(serp, "https://ours.com");
      expect(result.rating).to.equal(5);
    });

    it("R-010: should clamp negative rating to 0", () => {
      const serp = {
        organicResults: [{
          url: "https://ours.com",
          reviews: { rating: -1 }
        }]
      };
      const result = detectReviews(serp, "https://ours.com");
      expect(result.rating).to.equal(0);
    });

    it("R-011: should handle zero rating", () => {
      const serp = {
        organicResults: [{
          url: "https://ours.com",
          reviews: { rating: 0 }
        }]
      };
      const result = detectReviews(serp, "https://ours.com");
      expect(result.rating).to.equal(0);
    });

    it("R-012: should handle string rating", () => {
      const serp = {
        organicResults: [{
          url: "https://ours.com",
          reviews: { rating: "4.5" }
        }]
      };
      const result = detectReviews(serp, "https://ours.com");
      expect(result.rating).to.equal(4.5);
    });
  });

  // ========================================
  // 4. Return Value Structure
  // ========================================
  describe("Return Value Structure", () => {
    it("R-013: should always return type field", () => {
      const result = detectReviews({}, "https://example.com");
      expect(result).to.have.property("type");
      expect(result.type).to.equal("reviews");
    });

    it("R-014: should always return isPresent field", () => {
      const result = detectReviews({}, "https://example.com");
      expect(result).to.have.property("isPresent");
      expect(result.isPresent).to.be.a("boolean");
    });

    it("R-015: should include position when present", () => {
      const serp = {
        organicResults: [{
          position: 1,
          url: "https://ours.com",
          reviews: { rating: 4.5 }
        }]
      };
      const result = detectReviews(serp, "https://ours.com");
      expect(result.position).to.equal(1);
    });
  });

  // ========================================
  // 5. Edge Cases
  // ========================================
  describe("Edge Cases", () => {
    it("R-016: should handle null SERP result", () => {
      const result = detectReviews(null, "https://example.com");
      expect(result.type).to.equal("reviews");
      expect(result.isPresent).to.be.false;
    });

    it("R-017: should handle string SERP", () => {
      const result = detectReviews("string", "https://example.com");
      expect(result.isPresent).to.be.false;
    });

    it("R-018: should handle invalid target URL", () => {
      const serp = {
        organicResults: [{
          url: "https://example.com",
          reviews: { rating: 4.5 }
        }]
      };
      const result = detectReviews(serp, "not-a-url");
      expect(result.isPresent).to.be.false;
    });

    it("R-019: should handle NaN rating", () => {
      const serp = {
        organicResults: [{
          url: "https://ours.com",
          reviews: { rating: "not-a-number" }
        }]
      };
      const result = detectReviews(serp, "https://ours.com");
      expect(result.isPresent).to.be.false;
    });
  });

  // ========================================
  // 6. getResultsWithReviews Helper
  // ========================================
  describe("getResultsWithReviews Helper", () => {
    it("R-020: should extract all results with reviews", () => {
      const serp = {
        organicResults: [
          { url: "https://a.com", reviews: { rating: 4.5, count: 100 } },
          { url: "https://b.com" },
          { url: "https://c.com", reviews: { rating: 4.0, count: 50 } }
        ]
      };
      const results = getResultsWithReviews(serp);
      expect(results).to.have.lengthOf(2);
      expect(results[0].reviews.rating).to.equal(4.5);
      expect(results[1].reviews.rating).to.equal(4.0);
    });

    it("R-021: should handle empty organic results", () => {
      const results = getResultsWithReviews({});
      expect(results).to.be.an("array").that.is.empty;
    });
  });
});
