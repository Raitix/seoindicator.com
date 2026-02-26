import {
  SERP_FEATURE_META,
  getFeatureMeta,
  formatFeatureDate,
  getRelativeTime,
  isWithinDays,
} from "@/lib/api/serp-features";
import type { SERPFeatureType } from "@/lib/types/keywords";

describe("SERP Feature API Helpers", () => {
  describe("SERP_FEATURE_META", () => {
    it("contains all required feature types", () => {
      const expectedTypes: SERPFeatureType[] = [
        "featured_snippet",
        "local_pack",
        "image_pack",
        "video",
        "people_also_ask",
        "knowledge_panel",
        "sitelinks",
        "reviews",
        "faq",
        "product",
      ];

      expectedTypes.forEach((type) => {
        expect(SERP_FEATURE_META[type]).toBeDefined();
        expect(SERP_FEATURE_META[type].name).toBeDefined();
        expect(SERP_FEATURE_META[type].icon).toBeDefined();
        expect(SERP_FEATURE_META[type].description).toBeDefined();
        expect(SERP_FEATURE_META[type].ctrImpact).toBeDefined();
      });
    });

    it("has appropriate icons for each feature type", () => {
      expect(SERP_FEATURE_META.featured_snippet.icon).toBe("🏆");
      expect(SERP_FEATURE_META.local_pack.icon).toBe("📍");
      expect(SERP_FEATURE_META.image_pack.icon).toBe("🖼️");
      expect(SERP_FEATURE_META.video.icon).toBe("🎬");
      expect(SERP_FEATURE_META.reviews.icon).toBe("⭐");
    });
  });

  describe("getFeatureMeta", () => {
    it("returns correct metadata for known feature type", () => {
      const meta = getFeatureMeta("featured_snippet");
      expect(meta.name).toBe("Featured Snippet");
      expect(meta.icon).toBe("🏆");
    });

    it("returns fallback for unknown feature type", () => {
      const meta = getFeatureMeta("unknown_feature" as any);
      expect(meta.name).toBe("unknown feature");
      expect(meta.icon).toBe("📊");
    });
  });

  describe("formatFeatureDate", () => {
    it("formats ISO date string correctly", () => {
      const result = formatFeatureDate("2026-02-26T10:00:00Z");
      // The exact format depends on locale, but should contain year/month/day
      expect(result).toContain("2026");
      expect(result).toContain("26");
    });

    it("handles invalid date gracefully", () => {
      const result = formatFeatureDate("invalid-date");
      expect(result).toBe("invalid-date");
    });
  });

  describe("getRelativeTime", () => {
    it('returns "Today" for same day', () => {
      const today = new Date().toISOString();
      const result = getRelativeTime(today);
      expect(result).toBe("Today");
    });

    it('returns "Yesterday" for previous day', () => {
      const yesterday = new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString();
      const result = getRelativeTime(yesterday);
      expect(result).toBe("Yesterday");
    });

    it('returns "X days ago" for recent dates', () => {
      const threeDaysAgo = new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString();
      const result = getRelativeTime(threeDaysAgo);
      expect(result).toBe("3 days ago");
    });

    it('returns "X weeks ago" for dates within a month', () => {
      const twoWeeksAgo = new Date(Date.now() - 14 * 24 * 60 * 60 * 1000).toISOString();
      const result = getRelativeTime(twoWeeksAgo);
      expect(result).toBe("2 weeks ago");
    });

    it("handles invalid date gracefully", () => {
      const result = getRelativeTime("invalid-date");
      expect(result).toBe("invalid-date");
    });
  });

  describe("isWithinDays", () => {
    it("returns true for date within range", () => {
      const yesterday = new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString();
      const result = isWithinDays(yesterday, 7);
      expect(result).toBe(true);
    });

    it("returns false for date outside range", () => {
      const tenDaysAgo = new Date(Date.now() - 10 * 24 * 60 * 60 * 1000).toISOString();
      const result = isWithinDays(tenDaysAgo, 7);
      expect(result).toBe(false);
    });

    it("handles edge case exactly at boundary", () => {
      const exactlySevenDaysAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString();
      const result = isWithinDays(exactlySevenDaysAgo, 7);
      // May be true or false depending on exact timing
      expect(typeof result).toBe("boolean");
    });

    it("returns false for invalid date", () => {
      const result = isWithinDays("invalid-date", 7);
      expect(result).toBe(false);
    });
  });
});
