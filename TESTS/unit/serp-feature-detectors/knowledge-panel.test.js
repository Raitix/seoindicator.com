// Unit tests for TASK-7-7: Knowledge Panel Detection
// Tests verify detectKnowledgePanel method behavior for all acceptance criteria

import { expect } from "chai";
import {
  detectKnowledgePanel,
  getKnowledgePanelData
} from "../../../backend/src/services/detectors/knowledge-panel-detector.js";

describe("Unit: Knowledge Panel Detector", () => {
  // ========================================
  // 1. Basic Detection Logic
  // ========================================
  describe("Basic Detection", () => {
    describe("No Knowledge Panel Present", () => {
      it("KP-001: should return isPresent false when no panel in SERP", () => {
        const result = detectKnowledgePanel({}, "https://example.com");
        expect(result.type).to.equal("knowledge_panel");
        expect(result.isPresent).to.be.false;
      });

      it("KP-002: should return isPresent false when panel is undefined", () => {
        const result = detectKnowledgePanel(
          { knowledgePanel: undefined },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("KP-003: should return isPresent false when panel is empty object", () => {
        const result = detectKnowledgePanel(
          { knowledgePanel: {} },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Panel for Our Entity", () => {
      it("KP-004: should return isPresent true when URL matches", () => {
        const serp = {
          knowledgePanel: { title: "Our Brand", url: "https://ours.com" }
        };
        const result = detectKnowledgePanel(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });

      it("KP-005: should return isPresent true when name matches", () => {
        const serp = {
          knowledgePanel: { title: "Our Brand" }
        };
        const result = detectKnowledgePanel(serp, "https://example.com", "Our Brand");
        expect(result.isPresent).to.be.true;
      });
    });

    describe("Panel for Different Entity", () => {
      it("KP-006: should return isPresent false for different entity", () => {
        const serp = {
          knowledgePanel: { title: "Other Brand" }
        };
        const result = detectKnowledgePanel(serp, "https://ours.com", "Our Brand");
        expect(result.isPresent).to.be.false;
      });
    });
  });

  // ========================================
  // 2. Entity Matching
  // ========================================
  describe("Entity Matching", () => {
    it("KP-007: should match brand name exactly", () => {
      const serp = {
        knowledgePanel: { title: "Acme Corporation" }
      };
      const result = detectKnowledgePanel(serp, "https://acme.com", "Acme Corporation");
      expect(result.isPresent).to.be.true;
    });

    it("KP-008: should match URL in panel", () => {
      const serp = {
        knowledgePanel: { title: "Brand", url: "https://ours.com/about" }
      };
      const result = detectKnowledgePanel(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });

    it("KP-009: should match partial name", () => {
      const serp = {
        knowledgePanel: { title: "Brand Inc." }
      };
      const result = detectKnowledgePanel(serp, "https://brand.com", "Brand");
      expect(result.isPresent).to.be.true;
    });

    it("KP-010: should match with www prefix", () => {
      const serp = {
        knowledgePanel: { url: "https://www.ours.com" }
      };
      const result = detectKnowledgePanel(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });
  });

  // ========================================
  // 3. Panel Types
  // ========================================
  describe("Panel Types", () => {
    it("KP-011: should detect person panel type", () => {
      const serp = {
        knowledgePanel: { type: "person", name: "John Doe", url: "https://ours.com" }
      };
      const result = detectKnowledgePanel(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
      expect(result.entityType).to.equal("person");
    });

    it("KP-012: should detect organization panel type", () => {
      const serp = {
        knowledgePanel: { type: "organization", name: "Acme", url: "https://ours.com" }
      };
      const result = detectKnowledgePanel(serp, "https://ours.com");
      expect(result.entityType).to.equal("organization");
    });

    it("KP-013: should detect topic panel type", () => {
      const serp = {
        knowledgePanel: { type: "topic", title: "SEO", url: "https://ours.com" }
      };
      const result = detectKnowledgePanel(serp, "https://ours.com");
      expect(result.entityType).to.equal("topic");
    });
  });

  // ========================================
  // 4. Return Value Structure
  // ========================================
  describe("Return Value Structure", () => {
    it("KP-014: should always return type field", () => {
      const result = detectKnowledgePanel({}, "https://example.com");
      expect(result).to.have.property("type");
      expect(result.type).to.equal("knowledge_panel");
    });

    it("KP-015: should always return isPresent field", () => {
      const result = detectKnowledgePanel({}, "https://example.com");
      expect(result).to.have.property("isPresent");
      expect(result.isPresent).to.be.a("boolean");
    });

    it("KP-016: should extract title when present", () => {
      const serp = {
        knowledgePanel: { title: "Brand Name", url: "https://ours.com" }
      };
      const result = detectKnowledgePanel(serp, "https://ours.com");
      expect(result.title).to.equal("Brand Name");
    });

    it("KP-017: should extract description when present", () => {
      const serp = {
        knowledgePanel: { description: "About us...", url: "https://ours.com" }
      };
      const result = detectKnowledgePanel(serp, "https://ours.com");
      expect(result.description).to.equal("About us...");
    });
  });

  // ========================================
  // 5. Edge Cases
  // ========================================
  describe("Edge Cases", () => {
    it("KP-018: should handle null SERP result", () => {
      const result = detectKnowledgePanel(null, "https://example.com");
      expect(result.type).to.equal("knowledge_panel");
      expect(result.isPresent).to.be.false;
    });

    it("KP-019: should handle string SERP", () => {
      const result = detectKnowledgePanel("string", "https://example.com");
      expect(result.isPresent).to.be.false;
    });

    it("KP-020: should handle null target URL", () => {
      const serp = {
        knowledgePanel: { title: "Brand" }
      };
      const result = detectKnowledgePanel(serp, null, "Brand");
      expect(result.isPresent).to.be.true;
    });
  });

  // ========================================
  // 6. getKnowledgePanelData Helper
  // ========================================
  describe("getKnowledgePanelData Helper", () => {
    it("KP-021: should extract panel data", () => {
      const serp = {
        knowledgePanel: {
          type: "organization",
          title: "Acme",
          description: "A company",
          url: "https://acme.com"
        }
      };
      const data = getKnowledgePanelData(serp);
      expect(data.type).to.equal("organization");
      expect(data.title).to.equal("Acme");
      expect(data.description).to.equal("A company");
      expect(data.url).to.equal("https://acme.com");
    });

    it("KP-022: should return null for missing panel", () => {
      const data = getKnowledgePanelData({});
      expect(data).to.be.null;
    });
  });
});
