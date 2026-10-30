// Unit tests for TASK-7-2: SERP Feature Model and Types
// Tests validate SERPFeatureType definitions, interfaces, and model class

import { expect } from "chai";
import {
  ALL_FEATURE_TYPES,
  SERP_FEATURE_META,
  isValidFeatureType,
  getFeatureMeta,
  validateSERPFeature,
  validateSERPFeatureDetection,
  SERPFeatureModel,
  SERPFeatureValidationError,
  SERPFeatureParseError
} from "../../backend/src/models/serp-feature.js";

describe("Unit: SERP Feature Types and Model", () => {
  // ========================================
  // 1. SERPFeatureType Validation Tests
  // ========================================
  describe("SERPFeatureType Validation", () => {
    describe("Valid type values", () => {
      ALL_FEATURE_TYPES.forEach((type) => {
        it(`should accept valid type: ${type}`, () => {
          expect(isValidFeatureType(type)).to.be.true;
        });
      });
    });

    describe("Invalid type values", () => {
      it("should reject invalid type string", () => {
        expect(isValidFeatureType("invalid_type")).to.be.false;
      });

      it("should reject empty string", () => {
        expect(isValidFeatureType("")).to.be.false;
      });

      it("should reject null", () => {
        expect(isValidFeatureType(null)).to.be.false;
      });

      it("should reject undefined", () => {
        expect(isValidFeatureType(undefined)).to.be.false;
      });

      it("should be case-sensitive (uppercase rejected)", () => {
        expect(isValidFeatureType("FEATURED_SNIPPET")).to.be.false;
      });

      it("should be case-sensitive (mixed case rejected)", () => {
        expect(isValidFeatureType("Featured_Snippet")).to.be.false;
      });
    });
  });

  // ========================================
  // 2. SERPFeature Interface Tests
  // ========================================
  describe("SERPFeature Interface", () => {
    describe("Required fields", () => {
      it("should have type field", () => {
        const feature = { type: "featured_snippet", isPresent: true };
        expect(feature).to.have.property("type");
      });

      it("should have isPresent field", () => {
        const feature = { type: "featured_snippet", isPresent: true };
        expect(feature).to.have.property("isPresent");
      });

      it("should fail validation without type", () => {
        const validation = validateSERPFeature({ isPresent: true });
        expect(validation.valid).to.be.false;
      });

      it("should fail validation without isPresent", () => {
        const validation = validateSERPFeature({ type: "featured_snippet" });
        expect(validation.valid).to.be.false;
      });
    });

    describe("Optional fields", () => {
      it("should accept position as optional number", () => {
        const validation = validateSERPFeature({
          type: "local_pack",
          isPresent: true,
          position: 1
        });
        expect(validation.valid).to.be.true;
      });

      it("should accept url as optional string", () => {
        const validation = validateSERPFeature({
          type: "featured_snippet",
          isPresent: true,
          url: "https://example.com"
        });
        expect(validation.valid).to.be.true;
      });

      it("should accept title as optional string", () => {
        const validation = validateSERPFeature({
          type: "featured_snippet",
          isPresent: true,
          title: "Example Title"
        });
        expect(validation.valid).to.be.true;
      });

      it("should accept snippet as optional string", () => {
        const validation = validateSERPFeature({
          type: "featured_snippet",
          isPresent: true,
          snippet: "Example snippet text..."
        });
        expect(validation.valid).to.be.true;
      });

      it("should work without any optional fields", () => {
        const validation = validateSERPFeature({
          type: "sitelinks",
          isPresent: true
        });
        expect(validation.valid).to.be.true;
      });
    });

    describe("isPresent boolean validation", () => {
      it("should accept isPresent: true", () => {
        const validation = validateSERPFeature({
          type: "featured_snippet",
          isPresent: true
        });
        expect(validation.valid).to.be.true;
      });

      it("should accept isPresent: false", () => {
        const validation = validateSERPFeature({
          type: "featured_snippet",
          isPresent: false
        });
        expect(validation.valid).to.be.true;
      });

      it("should reject non-boolean string 'true'", () => {
        const validation = validateSERPFeature({
          type: "featured_snippet",
          isPresent: "true"
        });
        expect(validation.valid).to.be.false;
      });

      it("should reject non-boolean number 1", () => {
        const validation = validateSERPFeature({
          type: "featured_snippet",
          isPresent: 1
        });
        expect(validation.valid).to.be.false;
      });
    });
  });

  // ========================================
  // 3. SERPFeatureDetection Interface Tests
  // ========================================
  describe("SERPFeatureDetection Interface", () => {
    describe("Required fields", () => {
      it("should have keywordId (number)", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: 1,
          rankingId: 100,
          features: [],
          detectedAt: new Date()
        });
        expect(validation.valid).to.be.true;
      });

      it("should have rankingId (number)", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: 1,
          rankingId: 100,
          features: [],
          detectedAt: new Date()
        });
        expect(validation.valid).to.be.true;
      });

      it("should have features (array)", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: 1,
          rankingId: 100,
          features: [],
          detectedAt: new Date()
        });
        expect(validation.valid).to.be.true;
      });

      it("should have detectedAt (Date)", () => {
        const detection = {
          keywordId: 1,
          rankingId: 100,
          features: [],
          detectedAt: new Date()
        };
        expect(detection.detectedAt).to.be.instanceOf(Date);
      });

      it("should fail validation without keywordId", () => {
        const validation = validateSERPFeatureDetection({
          rankingId: 100,
          features: [],
          detectedAt: new Date()
        });
        expect(validation.valid).to.be.false;
      });

      it("should fail validation without rankingId", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: 1,
          features: [],
          detectedAt: new Date()
        });
        expect(validation.valid).to.be.false;
      });

      it("should fail validation without features", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: 1,
          rankingId: 100,
          detectedAt: new Date()
        });
        expect(validation.valid).to.be.false;
      });

      it("should fail validation without detectedAt", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: 1,
          rankingId: 100,
          features: []
        });
        expect(validation.valid).to.be.false;
      });
    });

    describe("Field type validation", () => {
      it("should reject keywordId as string", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: "1",
          rankingId: 100,
          features: [],
          detectedAt: new Date()
        });
        expect(validation.valid).to.be.false;
      });

      it("should reject rankingId as string", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: 1,
          rankingId: "100",
          features: [],
          detectedAt: new Date()
        });
        expect(validation.valid).to.be.false;
      });

      it("should reject features as object", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: 1,
          rankingId: 100,
          features: {},
          detectedAt: new Date()
        });
        expect(validation.valid).to.be.false;
      });

      it("should reject detectedAt as string", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: 1,
          rankingId: 100,
          features: [],
          detectedAt: "2026-01-01"
        });
        expect(validation.valid).to.be.false;
      });
    });

    describe("Features array validation", () => {
      it("should accept empty features array", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: 1,
          rankingId: 100,
          features: [],
          detectedAt: new Date()
        });
        expect(validation.valid).to.be.true;
      });

      it("should accept features array with valid SERPFeature objects", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: 1,
          rankingId: 100,
          features: [
            { type: "featured_snippet", isPresent: true },
            { type: "sitelinks", isPresent: false }
          ],
          detectedAt: new Date()
        });
        expect(validation.valid).to.be.true;
      });

      it("should reject features array with invalid SERPFeature objects", () => {
        const validation = validateSERPFeatureDetection({
          keywordId: 1,
          rankingId: 100,
          features: [{ type: "invalid", isPresent: true }],
          detectedAt: new Date()
        });
        expect(validation.valid).to.be.false;
      });
    });
  });

  // ========================================
  // 4. SERP_FEATURE_META Constant Tests
  // ========================================
  describe("SERP_FEATURE_META Constant", () => {
    describe("Structure validation", () => {
      it("should have all 10 feature types as keys", () => {
        ALL_FEATURE_TYPES.forEach((type) => {
          expect(SERP_FEATURE_META).to.have.property(type);
        });
      });

      it("each entry should have name (string)", () => {
        Object.values(SERP_FEATURE_META).forEach((meta) => {
          expect(meta).to.have.property("name");
          expect(meta.name).to.be.a("string");
        });
      });

      it("each entry should have icon (string)", () => {
        Object.values(SERP_FEATURE_META).forEach((meta) => {
          expect(meta).to.have.property("icon");
          expect(meta.icon).to.be.a("string");
        });
      });

      it("each entry should have description (string)", () => {
        Object.values(SERP_FEATURE_META).forEach((meta) => {
          expect(meta).to.have.property("description");
          expect(meta.description).to.be.a("string");
        });
      });

      it("each entry should have ctrImpact (string)", () => {
        Object.values(SERP_FEATURE_META).forEach((meta) => {
          expect(meta).to.have.property("ctrImpact");
          expect(meta.ctrImpact).to.be.a("string");
        });
      });
    });

    describe("Content validation", () => {
      it("featured_snippet should have correct name", () => {
        expect(SERP_FEATURE_META.featured_snippet.name).to.equal("Featured Snippet");
      });

      it("local_pack should have correct name", () => {
        expect(SERP_FEATURE_META.local_pack.name).to.equal("Local Pack");
      });

      it("video should have correct name", () => {
        expect(SERP_FEATURE_META.video.name).to.equal("Video Result");
      });

      it("people_also_ask should have correct name", () => {
        expect(SERP_FEATURE_META.people_also_ask.name).to.equal("People Also Ask");
      });

      it("reviews should have correct name", () => {
        expect(SERP_FEATURE_META.reviews.name).to.equal("Reviews/Ratings");
      });
    });

    describe("Icon validation", () => {
      it("featured_snippet icon should be trophy", () => {
        expect(SERP_FEATURE_META.featured_snippet.icon).to.equal("🏆");
      });

      it("local_pack icon should be pin", () => {
        expect(SERP_FEATURE_META.local_pack.icon).to.equal("📍");
      });

      it("reviews icon should be star", () => {
        expect(SERP_FEATURE_META.reviews.icon).to.equal("⭐");
      });
    });
  });

  // ========================================
  // 5. ALL_FEATURE_TYPES Array Tests
  // ========================================
  describe("ALL_FEATURE_TYPES Array", () => {
    describe("Array structure", () => {
      it("should be an array", () => {
        expect(Array.isArray(ALL_FEATURE_TYPES)).to.be.true;
      });

      it("should have exactly 10 elements", () => {
        expect(ALL_FEATURE_TYPES).to.have.lengthOf(10);
      });

      it("should contain only string values", () => {
        ALL_FEATURE_TYPES.forEach((type) => {
          expect(type).to.be.a("string");
        });
      });

      it("should have no duplicate values", () => {
        const unique = [...new Set(ALL_FEATURE_TYPES)];
        expect(unique).to.have.lengthOf(ALL_FEATURE_TYPES.length);
      });

      it("should be frozen/immutable", () => {
        expect(Object.isFrozen(ALL_FEATURE_TYPES)).to.be.true;
      });
    });

    describe("Content validation", () => {
      it("should contain featured_snippet", () => {
        expect(ALL_FEATURE_TYPES).to.include("featured_snippet");
      });

      it("should contain local_pack", () => {
        expect(ALL_FEATURE_TYPES).to.include("local_pack");
      });

      it("should contain image_pack", () => {
        expect(ALL_FEATURE_TYPES).to.include("image_pack");
      });

      it("should contain video", () => {
        expect(ALL_FEATURE_TYPES).to.include("video");
      });

      it("should contain people_also_ask", () => {
        expect(ALL_FEATURE_TYPES).to.include("people_also_ask");
      });

      it("should contain knowledge_panel", () => {
        expect(ALL_FEATURE_TYPES).to.include("knowledge_panel");
      });

      it("should contain sitelinks", () => {
        expect(ALL_FEATURE_TYPES).to.include("sitelinks");
      });

      it("should contain reviews", () => {
        expect(ALL_FEATURE_TYPES).to.include("reviews");
      });

      it("should contain faq", () => {
        expect(ALL_FEATURE_TYPES).to.include("faq");
      });

      it("should contain product", () => {
        expect(ALL_FEATURE_TYPES).to.include("product");
      });
    });
  });

  // ========================================
  // 6. Model Class Serialization Tests
  // ========================================
  describe("SERPFeatureModel Class", () => {
    describe("Constructor", () => {
      it("should create instance with minimal data", () => {
        const feature = new SERPFeatureModel({
          type: "featured_snippet",
          isPresent: true
        });
        expect(feature.type).to.equal("featured_snippet");
        expect(feature.isPresent).to.be.true;
      });

      it("should create instance with all fields", () => {
        const feature = new SERPFeatureModel({
          type: "featured_snippet",
          isPresent: true,
          position: 1,
          url: "https://example.com",
          title: "Example Title",
          snippet: "Example snippet"
        });
        expect(feature.type).to.equal("featured_snippet");
        expect(feature.position).to.equal(1);
        expect(feature.url).to.equal("https://example.com");
        expect(feature.title).to.equal("Example Title");
        expect(feature.snippet).to.equal("Example snippet");
      });

      it("should throw ValidationError for invalid type", () => {
        expect(() => {
          new SERPFeatureModel({ type: "invalid", isPresent: true });
        }).to.throw(SERPFeatureValidationError);
      });

      it("should throw ValidationError for invalid isPresent", () => {
        expect(() => {
          new SERPFeatureModel({ type: "featured_snippet", isPresent: "yes" });
        }).to.throw(SERPFeatureValidationError);
      });

      it("should throw ValidationError for negative position", () => {
        expect(() => {
          new SERPFeatureModel({
            type: "featured_snippet",
            isPresent: true,
            position: -1
          });
        }).to.throw(SERPFeatureValidationError);
      });
    });

    describe("toObject serialization", () => {
      it("should return plain object", () => {
        const feature = new SERPFeatureModel({
          type: "sitelinks",
          isPresent: true
        });
        const obj = feature.toObject();
        expect(obj).to.deep.equal({ type: "sitelinks", isPresent: true });
      });

      it("should include optional fields when present", () => {
        const feature = new SERPFeatureModel({
          type: "featured_snippet",
          isPresent: true,
          url: "https://example.com"
        });
        const obj = feature.toObject();
        expect(obj.url).to.equal("https://example.com");
      });
    });

    describe("toJSON serialization", () => {
      it("should serialize minimal object", () => {
        const feature = new SERPFeatureModel({
          type: "sitelinks",
          isPresent: true
        });
        const json = feature.toJSON();
        const parsed = JSON.parse(json);
        expect(parsed.type).to.equal("sitelinks");
        expect(parsed.isPresent).to.be.true;
      });

      it("should serialize with optional fields", () => {
        const feature = new SERPFeatureModel({
          type: "featured_snippet",
          isPresent: true,
          url: "https://example.com"
        });
        const json = feature.toJSON();
        const parsed = JSON.parse(json);
        expect(parsed.url).to.equal("https://example.com");
      });

      it("should omit undefined optional fields", () => {
        const feature = new SERPFeatureModel({
          type: "featured_snippet",
          isPresent: true
        });
        const json = feature.toJSON();
        const parsed = JSON.parse(json);
        expect(parsed).to.not.have.property("url");
        expect(parsed).to.not.have.property("position");
        expect(parsed).to.not.have.property("title");
        expect(parsed).to.not.have.property("snippet");
      });
    });

    describe("fromJSON deserialization", () => {
      it("should deserialize valid JSON", () => {
        const json = '{"type":"featured_snippet","isPresent":true}';
        const feature = SERPFeatureModel.fromJSON(json);
        expect(feature.type).to.equal("featured_snippet");
        expect(feature.isPresent).to.be.true;
      });

      it("should deserialize with all fields", () => {
        const json =
          '{"type":"featured_snippet","isPresent":true,"position":1,"url":"https://example.com","title":"Title","snippet":"Snippet"}';
        const feature = SERPFeatureModel.fromJSON(json);
        expect(feature.position).to.equal(1);
        expect(feature.url).to.equal("https://example.com");
      });

      it("should throw ParseError on malformed JSON", () => {
        expect(() => SERPFeatureModel.fromJSON("not json")).to.throw(
          SERPFeatureParseError
        );
      });

      it("should throw ValidationError on missing required field", () => {
        const json = '{"url":"https://example.com"}';
        expect(() => SERPFeatureModel.fromJSON(json)).to.throw(
          SERPFeatureValidationError
        );
      });

      it("should throw ValidationError on invalid type", () => {
        const json = '{"type":"invalid","isPresent":true}';
        expect(() => SERPFeatureModel.fromJSON(json)).to.throw(
          SERPFeatureValidationError
        );
      });
    });

    describe("Static methods", () => {
      it("isValidType should return true for valid type", () => {
        expect(SERPFeatureModel.isValidType("featured_snippet")).to.be.true;
      });

      it("isValidType should return false for invalid type", () => {
        expect(SERPFeatureModel.isValidType("invalid")).to.be.false;
      });

      it("getAllTypes should return ALL_FEATURE_TYPES", () => {
        expect(SERPFeatureModel.getAllTypes()).to.equal(ALL_FEATURE_TYPES);
      });

      it("getMeta should return metadata for valid type", () => {
        const meta = SERPFeatureModel.getMeta("featured_snippet");
        expect(meta).to.deep.equal(SERP_FEATURE_META.featured_snippet);
      });

      it("getMeta should return null for invalid type", () => {
        expect(SERPFeatureModel.getMeta("invalid")).to.be.null;
      });
    });
  });

  // ========================================
  // 7. Helper Function Tests
  // ========================================
  describe("Helper Functions", () => {
    describe("isValidFeatureType", () => {
      it("should return true for valid types", () => {
        ALL_FEATURE_TYPES.forEach((type) => {
          expect(isValidFeatureType(type)).to.be.true;
        });
      });

      it("should return false for invalid types", () => {
        expect(isValidFeatureType("invalid")).to.be.false;
        expect(isValidFeatureType("")).to.be.false;
        expect(isValidFeatureType(null)).to.be.false;
        expect(isValidFeatureType(undefined)).to.be.false;
        expect(isValidFeatureType(123)).to.be.false;
      });
    });

    describe("getFeatureMeta", () => {
      it("should return metadata for valid types", () => {
        const meta = getFeatureMeta("featured_snippet");
        expect(meta).to.have.property("name");
        expect(meta).to.have.property("icon");
        expect(meta).to.have.property("description");
        expect(meta).to.have.property("ctrImpact");
      });

      it("should return null for invalid types", () => {
        expect(getFeatureMeta("invalid")).to.be.null;
        expect(getFeatureMeta(null)).to.be.null;
      });
    });
  });

  // ========================================
  // 8. Edge Cases and Adversarial Tests
  // ========================================
  describe("Edge Cases", () => {
    describe("Boundary values", () => {
      it("should handle maximum URL length (2083 chars)", () => {
        const longUrl = "https://example.com/" + "a".repeat(2063);
        expect(longUrl.length).to.equal(2083);
        const feature = new SERPFeatureModel({
          type: "featured_snippet",
          isPresent: true,
          url: longUrl
        });
        expect(feature.url).to.equal(longUrl);
      });

      it("should handle position 1", () => {
        const feature = new SERPFeatureModel({
          type: "local_pack",
          isPresent: true,
          position: 1
        });
        expect(feature.position).to.equal(1);
      });

      it("should handle position 100", () => {
        const feature = new SERPFeatureModel({
          type: "local_pack",
          isPresent: true,
          position: 100
        });
        expect(feature.position).to.equal(100);
      });

      it("should handle position 0", () => {
        const feature = new SERPFeatureModel({
          type: "local_pack",
          isPresent: true,
          position: 0
        });
        expect(feature.position).to.equal(0);
      });
    });

    describe("Unicode and special characters", () => {
      it("should handle emoji in title", () => {
        const feature = new SERPFeatureModel({
          type: "featured_snippet",
          isPresent: true,
          title: "Best 🏆 SEO Tools"
        });
        expect(feature.title).to.include("🏆");
      });

      it("should handle multi-language text", () => {
        const feature = new SERPFeatureModel({
          type: "featured_snippet",
          isPresent: true,
          snippet: "日本語 中文 العربية"
        });
        expect(feature.snippet).to.include("日本語");
      });

      it("should preserve newlines in snippet", () => {
        const feature = new SERPFeatureModel({
          type: "featured_snippet",
          isPresent: true,
          snippet: "line1\nline2\nline3"
        });
        expect(feature.snippet).to.include("\n");
      });

      it("should handle HTML in snippet", () => {
        const feature = new SERPFeatureModel({
          type: "featured_snippet",
          isPresent: true,
          snippet: "<p>text</p>"
        });
        expect(feature.snippet).to.equal("<p>text</p>");
      });
    });

    describe("Extra properties", () => {
      it("should handle extra properties gracefully", () => {
        const feature = new SERPFeatureModel({
          type: "featured_snippet",
          isPresent: true,
          extraField: "should be ignored"
        });
        expect(feature.type).to.equal("featured_snippet");
        expect(feature).to.not.have.property("extraField");
      });
    });

    describe("Prototype pollution protection", () => {
      it("should not allow prototype pollution via constructor", () => {
        // Attempting to pass __proto__ in data should not pollute prototype
        const maliciousData = {
          type: "featured_snippet",
          isPresent: true,
          __proto__: { polluted: true }
        };
        const feature = new SERPFeatureModel(maliciousData);
        // The feature should only have valid properties
        expect(feature.type).to.equal("featured_snippet");
        expect(feature).to.not.have.property("polluted");
      });
    });
  });
});
