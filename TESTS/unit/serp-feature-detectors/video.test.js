// Unit tests for TASK-7-7: Video Detection
// Tests verify detectVideo method behavior for all acceptance criteria

import { expect } from "chai";
import {
  detectVideo,
  getVideoEntries,
  detectPlatform
} from "../../../backend/src/services/detectors/video-detector.js";

describe("Unit: Video Detector", () => {
  // ========================================
  // 1. Basic Detection Logic
  // ========================================
  describe("Basic Detection", () => {
    describe("No Videos Present", () => {
      it("V-001: should return isPresent false when no videos in SERP", () => {
        const result = detectVideo({}, "https://example.com");
        expect(result.type).to.equal("video");
        expect(result.isPresent).to.be.false;
      });

      it("V-002: should return isPresent false when videos is undefined", () => {
        const result = detectVideo(
          { videos: undefined },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });

      it("V-003: should return isPresent false when videos is empty array", () => {
        const result = detectVideo(
          { videos: [] },
          "https://example.com"
        );
        expect(result.isPresent).to.be.false;
      });
    });

    describe("Video Present - Our URL", () => {
      it("V-004: should return isPresent true when our URL in videos", () => {
        const serp = {
          videos: [{ url: "https://ours.com/video" }]
        };
        const result = detectVideo(serp, "https://ours.com");
        expect(result.isPresent).to.be.true;
      });
    });

    describe("Video Present - Competitor URL", () => {
      it("V-005: should return isPresent false for competitor URL", () => {
        const serp = {
          videos: [{ url: "https://youtube.com/watch?v=abc" }]
        };
        const result = detectVideo(serp, "https://ours.com");
        expect(result.isPresent).to.be.false;
      });
    });
  });

  // ========================================
  // 2. Video Platforms
  // ========================================
  describe("Video Platforms", () => {
    it("V-006: should detect YouTube video", () => {
      const serp = {
        videos: [{ url: "https://youtube.com/watch?v=abc", platform: "youtube" }]
      };
      const entries = getVideoEntries(serp);
      expect(entries[0].platform).to.equal("youtube");
    });

    it("V-007: should detect Vimeo video", () => {
      const serp = {
        videos: [{ url: "https://vimeo.com/123", platform: "vimeo" }]
      };
      const entries = getVideoEntries(serp);
      expect(entries[0].platform).to.equal("vimeo");
    });

    it("V-008: should detect self-hosted video", () => {
      const serp = {
        videos: [{ url: "https://ours.com/video.mp4" }]
      };
      const result = detectVideo(serp, "https://ours.com");
      expect(result.isPresent).to.be.true;
    });

    it("V-009: should auto-detect YouTube platform from URL", () => {
      const platform = detectPlatform("https://www.youtube.com/watch?v=abc");
      expect(platform).to.equal("youtube");
    });

    it("V-010: should auto-detect Vimeo platform from URL", () => {
      const platform = detectPlatform("https://vimeo.com/123456");
      expect(platform).to.equal("vimeo");
    });
  });

  // ========================================
  // 3. Video Metadata
  // ========================================
  describe("Video Metadata", () => {
    it("V-011: should extract video title", () => {
      const serp = {
        videos: [{ url: "https://ours.com/v", title: "How to..." }]
      };
      const result = detectVideo(serp, "https://ours.com");
      expect(result.title).to.equal("How to...");
    });

    it("V-012: should extract video duration", () => {
      const serp = {
        videos: [{ url: "https://ours.com/v", duration: "5:30" }]
      };
      const result = detectVideo(serp, "https://ours.com");
      expect(result.duration).to.equal("5:30");
    });

    it("V-013: should extract video thumbnail", () => {
      const serp = {
        videos: [{ url: "https://ours.com/v", thumbnail: "thumb.jpg" }]
      };
      const result = detectVideo(serp, "https://ours.com");
      expect(result.thumbnail).to.equal("thumb.jpg");
    });

    it("V-014: should extract all metadata fields", () => {
      const serp = {
        videos: [{
          url: "https://ours.com/v",
          title: "Tutorial",
          duration: "10:00",
          thumbnail: "thumb.jpg",
          platform: "self"
        }]
      };
      const result = detectVideo(serp, "https://ours.com");
      expect(result.title).to.equal("Tutorial");
      expect(result.duration).to.equal("10:00");
      expect(result.thumbnail).to.equal("thumb.jpg");
      expect(result.platform).to.equal("self");
    });
  });

  // ========================================
  // 4. Return Value Structure
  // ========================================
  describe("Return Value Structure", () => {
    it("V-015: should always return type field", () => {
      const result = detectVideo({}, "https://example.com");
      expect(result).to.have.property("type");
      expect(result.type).to.equal("video");
    });

    it("V-016: should always return isPresent field", () => {
      const result = detectVideo({}, "https://example.com");
      expect(result).to.have.property("isPresent");
      expect(result.isPresent).to.be.a("boolean");
    });

    it("V-017: should include position when present", () => {
      const serp = {
        videos: [{ url: "https://ours.com/v" }]
      };
      const result = detectVideo(serp, "https://ours.com");
      expect(result.position).to.equal(1);
    });
  });

  // ========================================
  // 5. Edge Cases
  // ========================================
  describe("Edge Cases", () => {
    it("V-018: should handle null SERP result", () => {
      const result = detectVideo(null, "https://example.com");
      expect(result.type).to.equal("video");
      expect(result.isPresent).to.be.false;
    });

    it("V-019: should handle string SERP", () => {
      const result = detectVideo("string", "https://example.com");
      expect(result.isPresent).to.be.false;
    });

    it("V-020: should handle invalid target URL", () => {
      const serp = {
        videos: [{ url: "https://example.com/video" }]
      };
      const result = detectVideo(serp, "not-a-url");
      expect(result.isPresent).to.be.false;
    });
  });

  // ========================================
  // 6. getVideoEntries Helper
  // ========================================
  describe("getVideoEntries Helper", () => {
    it("V-021: should extract all video entries", () => {
      const serp = {
        videos: [
          { url: "https://a.com/v1", title: "First" },
          { url: "https://b.com/v2", title: "Second" }
        ]
      };
      const entries = getVideoEntries(serp);
      expect(entries).to.have.lengthOf(2);
      expect(entries[0].position).to.equal(1);
      expect(entries[1].position).to.equal(2);
    });

    it("V-022: should handle empty videos", () => {
      const entries = getVideoEntries({});
      expect(entries).to.be.an("array").that.is.empty;
    });
  });
});
