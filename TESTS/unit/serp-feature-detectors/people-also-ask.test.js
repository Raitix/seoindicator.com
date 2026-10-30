// Unit tests for TASK-7-7: People Also Ask Detection
// Tests verify detectPeopleAlsoAsk method behavior for all acceptance criteria

import { expect } from "chai";
import {
  detectPeopleAlsoAsk,
  getPeopleAlsoAskEntries
} from "../../../backend/src/services/detectors/people-also-ask-detector.js";

describe("Unit: People Also Ask Detector", () => {
  // ========================================
  // 1. Basic Detection Logic
  // ========================================
  describe("Basic Detection", () => {
    describe("No PAA Present", () => {
      it("PAA-001: should return isPresent false when no PAA in SERP", () => {
        const result = detectPeopleAlsoAsk({}, "https://example.com");
        expect(result.type).to.equal("people_also_ask");
        expect(result.isPresent).to.be.false;
      });

      it("PAA-002: should return isPresent false when PAA is undefined", () => {
        const result = detectPeopleAlsoAsk(
          { peopleAlsoAsk: undefined },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("PAA-003: should return isPresent false when PAA is empty array", () => {
        const result = detectPeopleAlsoAsk(
          { peopleAlsoAsk: [] },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });
    });

    describe("PAA Present - Our URL", () => {
      it("PAA-004: should return isPresent true when our URL in answer", () => {
        const serp = {
          peopleAlsoAsk: [{ answer: { url: "https://ours.com" } }]
        };
        const result = detectPeopleAlsoAsk(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });
    });

    describe("PAA Present - Competitor URL", () => {
      it("PAA-005: should return isPresent false for competitor answer", () => {
        const serp = {
          peopleAlsoAsk: [{ answer: { url: "https://theirs.com" } }]
        };
        const result = detectPeopleAlsoAsk(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });
    });
  });

  // ========================================
  // 2. PAA Structure
  // ========================================
  describe("PAA Structure", () => {
    it("PAA-006: should match one of multiple questions", () => {
      const serp = {
        peopleAlsoAsk: [
          { question: "Q1?", answer: { url: "https://a.com" } },
          { question: "Q2?", answer: { url: "https://ours.com" } },
          { question: "Q3?", answer: { url: "https://b.com" } }
        ]
      };
      const result = detectPeopleAlsoAsk(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
      expect(result.matchedQuestions).to.include("Q2?");
    });

    it("PAA-007: should handle PAA with question only", () => {
      const serp = {
        peopleAlsoAsk: [{ question: "What is SEO?" }]
      };
      const result = detectPeopleAlsoAsk(serp, "https://example.com");
      expect(result.isPresent).to.be.false;
    });

    it("PAA-008: should include answer snippet", () => {
      const serp = {
        peopleAlsoAsk: [{
          question: "Q?",
          answer: { snippet: "Answer...", url: "https://ours.com" }
        }]
      };
      const result = detectPeopleAlsoAsk(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });
  });

  // ========================================
  // 3. Question Tracking
  // ========================================
  describe("Question Tracking", () => {
    it("PAA-009: should return question count", () => {
      const serp = {
        peopleAlsoAsk: [
          { question: "Q1?", answer: { url: "https://a.com" } },
          { question: "Q2?", answer: { url: "https://b.com" } },
          { question: "Q3?", answer: { url: "https://c.com" } },
          { question: "Q4?", answer: { url: "https://d.com" } },
          { question: "Q5?", answer: { url: "https://e.com" } }
        ]
      };
      const result = detectPeopleAlsoAsk(serp, "https://example.com");
      expect(result.questionCount).to.equal(5);
    });

    it("PAA-010: should return matched questions", () => {
      const serp = {
        peopleAlsoAsk: [
          { question: "Q1?", answer: { url: "https://a.com" } },
          { question: "Our Question?", answer: { url: "https://ours.com" } },
          { question: "Q3?", answer: { url: "https://c.com" } }
        ]
      };
      const result = detectPeopleAlsoAsk(serp, "https://ours.com");
      expect(result.matchedQuestions).to.deep.equal(["Our Question?"]);
      expect(result.matchedCount).to.equal(1);
    });
  });

  // ========================================
  // 4. Return Value Structure
  // ========================================
  describe("Return Value Structure", () => {
    it("PAA-011: should always return type field", () => {
      const result = detectPeopleAlsoAsk({}, "https://example.com");
      expect(result).to.have.property("type");
      expect(result.type).to.equal("people_also_ask");
    });

    it("PAA-012: should always return isPresent field", () => {
      const result = detectPeopleAlsoAsk({}, "https://example.com");
      expect(result).to.have.property("isPresent");
      expect(result.isPresent).to.be.a("boolean");
    });

    it("PAA-013: should include questionCount", () => {
      const serp = {
        peopleAlsoAsk: [
          { question: "Q1?", answer: { url: "https://a.com" } },
          { question: "Q2?", answer: { url: "https://b.com" } }
        ]
      };
      const result = detectPeopleAlsoAsk(serp, "https://example.com");
      expect(result.questionCount).to.equal(2);
    });
  });

  // ========================================
  // 5. Edge Cases
  // ========================================
  describe("Edge Cases", () => {
    it("PAA-014: should handle null SERP result", () => {
      const result = detectPeopleAlsoAsk(null, "https://example.com");
      expect(result.type).to.equal("people_also_ask");
      expect(result.isPresent).to.be.false;
    });

    it("PAA-015: should handle string SERP", () => {
      const result = detectPeopleAlsoAsk("string", "https://example.com");
      expect(result.isPresent).to.be.false;
    });

    it("PAA-016: should handle invalid target URL", () => {
      const serp = {
        peopleAlsoAsk: [{ answer: { url: "https://example.com" } }]
      };
      const result = detectPeopleAlsoAsk(serp, "not-a-url");
      expect(result.isPresent).to.be.false;
    });

    it("PAA-017: should handle PAA item without answer", () => {
      const serp = {
        peopleAlsoAsk: [{ question: "Q?" }]
      };
      const result = detectPeopleAlsoAsk(serp, "https://example.com");
      expect(result.isPresent).to.be.false;
    });
  });

  // ========================================
  // 6. getPeopleAlsoAskEntries Helper
  // ========================================
  describe("getPeopleAlsoAskEntries Helper", () => {
    it("PAA-018: should extract all PAA entries", () => {
      const serp = {
        peopleAlsoAsk: [
          { question: "Q1?", answer: { text: "A1", url: "https://a.com" } },
          { question: "Q2?", answer: { text: "A2", url: "https://b.com" } }
        ]
      };
      const entries = getPeopleAlsoAskEntries(serp);
      expect(entries).to.have.lengthOf(2);
      expect(entries[0].question).to.equal("Q1?");
      expect(entries[1].question).to.equal("Q2?");
    });

    it("PAA-019: should handle empty PAA", () => {
      const entries = getPeopleAlsoAskEntries({});
      expect(entries).to.be.an("array").that.is.empty;
    });
  });
});
