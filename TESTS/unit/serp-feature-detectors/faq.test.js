// Unit tests for TASK-7-7: FAQ Detection
// Tests verify detectFAQ method behavior for all acceptance criteria

import { expect } from "chai";
import {
  detectFAQ,
  getFAQEntries
} from "../../../backend/src/services/detectors/faq-detector.js";

describe("Unit: FAQ Detector", () => {
  // ========================================
  // 1. Basic Detection Logic
  // ========================================
  describe("Basic Detection", () => {
    describe("No FAQ Present", () => {
      it("F-001: should return isPresent false when no FAQ in SERP", () => {
        const result = detectFAQ({}, "https://example.com");
        expect(result.type).to.equal("faq");
        expect(result.isPresent).to.be.false;
      });

      it("F-002: should return isPresent false when FAQ is undefined", () => {
        const result = detectFAQ(
          { faqs: undefined },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("F-003: should return isPresent false when FAQ is empty array", () => {
        const result = detectFAQ(
          { faqs: [] },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });
    });

    describe("FAQ Present - Our URL", () => {
      it("F-004: should return isPresent true when our URL in FAQ", () => {
        const serp = {
          faqs: [{ url: "https://ours.com/faq", question: "How does it work?" }]
        };
        const result = detectFAQ(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });
    });

    describe("FAQ Present - Competitor URL", () => {
      it("F-005: should return isPresent false for competitor FAQ", () => {
        const serp = {
          faqs: [{ url: "https://theirs.com/faq", question: "What is this?" }]
        };
        const result = detectFAQ(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });
    });
  });

  // ========================================
  // 2. FAQ Structure
  // ========================================
  describe("FAQ Structure", () => {
    it("F-006: should return FAQ count", () => {
      const serp = {
        faqs: [
          { url: "https://a.com/1", question: "Q1?" },
          { url: "https://b.com/2", question: "Q2?" },
          { url: "https://c.com/3", question: "Q3?" }
        ]
      };
      const result = detectFAQ(serp, "https://ours.com");
      expect(result.count).to.equal(3);
    });

    it("F-007: should extract question and answer", () => {
      const serp = {
        faqs: [{
          question: "How does it work?",
          answer: "Simply sign up...",
          url: "https://ours.com/faq"
        }]
      };
      const result = detectFAQ(serp, "https://ours.com");
      expect(result.matchedFaqs).to.have.lengthOf(1);
      expect(result.matchedFaqs[0].question).to.equal("How does it work?");
      expect(result.matchedFaqs[0].answer).to.equal("Simply sign up...");
    });

    it("F-008: should track multiple matched FAQs", () => {
      const serp = {
        faqs: [
          { url: "https://ours.com/1", question: "Q1?" },
          { url: "https://a.com/2", question: "Q2?" },
          { url: "https://ours.com/3", question: "Q3?" }
        ]
      };
      const result = detectFAQ(serp, "https://ours.com");
      expect(result.matchedCount).to.equal(2);
    });
  });

  // ========================================
  // 3. Return Value Structure
  // ========================================
  describe("Return Value Structure", () => {
    it("F-009: should always return type field", () => {
      const result = detectFAQ({}, "https://example.com");
      expect(result).to.have.property("type");
      expect(result.type).to.equal("faq");
    });

    it("F-010: should always return isPresent field", () => {
      const result = detectFAQ({}, "https://example.com");
      expect(result).to.have.property("isPresent");
      expect(result.isPresent).to.be.a("boolean");
    });

    it("F-011: should include count field", () => {
      const serp = {
        faqs: [
          { url: "https://a.com/1", question: "Q1?" },
          { url: "https://b.com/2", question: "Q2?" }
        ]
      };
      const result = detectFAQ(serp, "https://example.com");
      expect(result.count).to.equal(2);
    });
  });

  // ========================================
  // 4. Edge Cases
  // ========================================
  describe("Edge Cases", () => {
    it("F-012: should handle null SERP result", () => {
      const result = detectFAQ(null, "https://example.com");
      expect(result.type).to.equal("faq");
      expect(result.isPresent).to.be.false;
    });

    it("F-013: should handle string SERP", () => {
      const result = detectFAQ("string", "https://example.com");
      expect(result.isPresent).to.be.false;
    });

    it("F-014: should handle invalid target URL", () => {
      const serp = {
        faqs: [{ url: "https://example.com/faq" }]
      };
      const result = detectFAQ(serp, "not-a-url");
      expect(result.isPresent).to.be.false;
    });

    it("F-015: should handle FAQ without URL", () => {
      const serp = {
        faqs: [{ question: "Q?" }]
      };
      const result = detectFAQ(serp, "https://example.com");
      expect(result.isPresent).to.be.false;
    });
  });

  // ========================================
  // 5. getFAQEntries Helper
  // ========================================
  describe("getFAQEntries Helper", () => {
    it("F-016: should extract all FAQ entries", () => {
      const serp = {
        faqs: [
          { question: "Q1?", answer: "A1", url: "https://a.com/1" },
          { question: "Q2?", answer: "A2", url: "https://b.com/2" }
        ]
      };
      const entries = getFAQEntries(serp);
      expect(entries).to.have.lengthOf(2);
      expect(entries[0].question).to.equal("Q1?");
      expect(entries[1].question).to.equal("Q2?");
    });

    it("F-017: should handle empty FAQs", () => {
      const entries = getFAQEntries({});
      expect(entries).to.be.an("array").that.is.empty;
    });
  });
});
