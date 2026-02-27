// Unit tests for TASK-7-7: Product Detection
// Tests verify detectProduct method behavior for all acceptance criteria

import { expect } from "chai";
import {
  detectProduct,
  getProductEntries
} from "../../../backend/src/services/detectors/product-detector.js";

describe("Unit: Product Detector", () => {
  // ========================================
  // 1. Basic Detection Logic
  // ========================================
  describe("Basic Detection", () => {
    describe("No Product Present", () => {
      it("PR-001: should return isPresent false when no product in SERP", () => {
        const result = detectProduct({}, "https://example.com");
        expect(result.type).to.equal("product");
        expect(result.isPresent).to.be.false;
      });

      it("PR-002: should return isPresent false when products is undefined", () => {
        const result = detectProduct(
          { products: undefined },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("PR-003: should return isPresent false when products is empty array", () => {
        const result = detectProduct(
          { products: [] },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Product Present - Our URL", () => {
      it("PR-004: should return isPresent true when our URL in products", () => {
        const serp = {
          products: [{ url: "https://ours.com/product", name: "Item" }]
        };
        const result = detectProduct(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });
    });

    describe("Product Present - Competitor URL", () => {
      it("PR-005: should return isPresent false for competitor product", () => {
        const serp = {
          products: [{ url: "https://theirs.com/product" }]
        };
        const result = detectProduct(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });
    });
  });

  // ========================================
  // 2. Product Details
  // ========================================
  describe("Product Details", () => {
    it("PR-006: should extract product name", () => {
      const serp = {
        products: [{
          url: "https://ours.com/product",
          name: "SEO Pro Plan"
        }]
      };
      const result = detectProduct(serp, "https://ours.com");
      expect(result.name).to.equal("SEO Pro Plan");
    });

    it("PR-007: should extract price", () => {
      const serp = {
        products: [{
          url: "https://ours.com/product",
          price: "$99.00"
        }]
      };
      const result = detectProduct(serp, "https://ours.com");
      expect(result.price).to.equal("$99.00");
    });

    it("PR-008: should extract availability", () => {
      const serp = {
        products: [{
          url: "https://ours.com/product",
          availability: "In Stock"
        }]
      };
      const result = detectProduct(serp, "https://ours.com");
      expect(result.availability).to.equal("In Stock");
    });

    it("PR-009: should extract image URL", () => {
      const serp = {
        products: [{
          url: "https://ours.com/product",
          image: "https://ours.com/images/product.jpg"
        }]
      };
      const result = detectProduct(serp, "https://ours.com");
      expect(result.image).to.equal("https://ours.com/images/product.jpg");
    });

    it("PR-010: should extract rating and review count", () => {
      const serp = {
        products: [{
          url: "https://ours.com/product",
          rating: 4.5,
          reviewCount: 100
        }]
      };
      const result = detectProduct(serp, "https://ours.com");
      expect(result.rating).to.equal(4.5);
      expect(result.reviewCount).to.equal(100);
    });
  });

  // ========================================
  // 3. Price Parsing
  // ========================================
  describe("Price Parsing", () => {
    it("PR-011: should handle price with currency symbol", () => {
      const serp = {
        products: [{
          url: "https://ours.com/product",
          price: "$99.99"
        }]
      };
      const result = detectProduct(serp, "https://ours.com");
      expect(result.price).to.equal("$99.99");
    });

    it("PR-012: should handle sale price", () => {
      const serp = {
        products: [{
          url: "https://ours.com/product",
          price: "$149",
          salePrice: "$99"
        }]
      };
      const result = detectProduct(serp, "https://ours.com");
      expect(result.price).to.equal("$149");
      expect(result.salePrice).to.equal("$99");
    });

    it("PR-013: should handle various price formats", () => {
      const serp = {
        products: [{
          url: "https://ours.com/product",
          price: "€79.99"
        }]
      };
      const result = detectProduct(serp, "https://ours.com");
      expect(result.price).to.equal("€79.99");
    });
  });

  // ========================================
  // 4. Rating Validation
  // ========================================
  describe("Rating Validation", () => {
    it("PR-014: should clamp rating over 5 to 5", () => {
      const serp = {
        products: [{
          url: "https://ours.com/product",
          rating: 6.0
        }]
      };
      const result = detectProduct(serp, "https://ours.com");
      expect(result.rating).to.equal(5);
    });

    it("PR-015: should clamp negative rating to 0", () => {
      const serp = {
        products: [{
          url: "https://ours.com/product",
          rating: -1
        }]
      };
      const result = detectProduct(serp, "https://ours.com");
      expect(result.rating).to.equal(0);
    });

    it("PR-016: should handle string rating", () => {
      const serp = {
        products: [{
          url: "https://ours.com/product",
          rating: "4.5"
        }]
      };
      const result = detectProduct(serp, "https://ours.com");
      expect(result.rating).to.equal(4.5);
    });
  });

  // ========================================
  // 5. Return Value Structure
  // ========================================
  describe("Return Value Structure", () => {
    it("PR-017: should always return type field", () => {
      const result = detectProduct({}, "https://example.com");
      expect(result).to.have.property("type");
      expect(result.type).to.equal("product");
    });

    it("PR-018: should always return isPresent field", () => {
      const result = detectProduct({}, "https://example.com");
      expect(result).to.have.property("isPresent");
      expect(result.isPresent).to.be.a("boolean");
    });

    it("PR-019: should include position when present", () => {
      const serp = {
        products: [{ url: "https://ours.com/product" }]
      };
      const result = detectProduct(serp, "https://ours.com");
      expect(result.position).to.equal(1);
    });
  });

  // ========================================
  // 6. Edge Cases
  // ========================================
  describe("Edge Cases", () => {
    it("PR-020: should handle null SERP result", () => {
      const result = detectProduct(null, "https://example.com");
      expect(result.type).to.equal("product");
      expect(result.isPresent).to.be.false;
    });

    it("PR-021: should handle string SERP", () => {
      const result = detectProduct("string", "https://example.com");
      expect(result.isPresent).to.be.false;
    });

    it("PR-022: should handle invalid target URL", () => {
      const serp = {
        products: [{ url: "https://example.com/product" }]
      };
      const result = detectProduct(serp, "not-a-url");
      expect(result.isPresent).to.be.false;
    });
  });

  // ========================================
  // 7. getProductEntries Helper
  // ========================================
  describe("getProductEntries Helper", () => {
    it("PR-023: should extract all product entries", () => {
      const serp = {
        products: [
          { url: "https://a.com/p1", name: "Product 1", price: "$10" },
          { url: "https://b.com/p2", name: "Product 2", price: "$20" }
        ]
      };
      const entries = getProductEntries(serp);
      expect(entries).to.have.lengthOf(2);
      expect(entries[0].position).to.equal(1);
      expect(entries[1].position).to.equal(2);
    });

    it("PR-024: should handle empty products", () => {
      const entries = getProductEntries({});
      expect(entries).to.be.an("array").that.is.empty;
    });
  });
});
