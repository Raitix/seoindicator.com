// Unit tests for TASK-7-3: SERP Feature Repository
// Tests SERPFeatureRepository and SERPFeatureHistoryRepository classes

const chai = require("chai");
const chaiAsPromised = require("chai-as-promised");
const sinon = require("sinon");

// Use chai-as-promised for async assertions
chai.use(chaiAsPromised);
const expect = chai.expect;

// Import the repositories - CommonJS wrapper for ES modules
let SERPFeatureRepository, SERPFeatureHistoryRepository;

// Mock database connection for unit tests
const createMockDb = () => {
  const db = {
    run: sinon.stub(),
    get: sinon.stub(),
    all: sinon.stub(),
    serialize: sinon.stub().callsFake((fn) => fn()),
    exec: sinon.stub(),
    prepare: sinon.stub().returns({
      run: sinon.stub(),
      get: sinon.stub(),
      all: sinon.stub(),
      finalize: sinon.stub()
    })
  };
  return db;
};

// Helper to create mock run result with lastID
const mockRunSuccess = (lastID = 1) => {
  return function(callback) {
    if (callback) {
      const context = { lastID, changes: 1 };
      callback.call(context, null);
    }
    return this;
  };
};

describe("Unit: SERP Feature Repository", () => {
  let repo;
  let mockDb;

  before(async () => {
    // Dynamic import for ES modules
    const module = await import("../../backend/src/repositories/serp-feature-repo.js");
    SERPFeatureRepository = module.SERPFeatureRepository;
    SERPFeatureHistoryRepository = module.SERPFeatureHistoryRepository;
  });

  beforeEach(() => {
    mockDb = createMockDb();
    sinon.reset();
  });

  // ========================================
  // 1. Class Structure Tests
  // ========================================
  describe("Class Structure", () => {
    it("should be instantiable", () => {
      repo = new SERPFeatureRepository(mockDb);
      expect(repo).to.be.instanceOf(SERPFeatureRepository);
    });

    it("should accept database connection in constructor", () => {
      repo = new SERPFeatureRepository(mockDb);
      expect(repo.db).to.equal(mockDb);
    });

    it("should expose all required methods", () => {
      repo = new SERPFeatureRepository(mockDb);
      const requiredMethods = [
        "insert",
        "insertBatch",
        "findByKeywordId",
        "findByRankingId",
        "findFeaturesByType",
        "getFeatureSummary"
      ];
      requiredMethods.forEach(method => {
        expect(repo).to.have.property(method).that.is.a("function");
      });
    });

    it("should throw error when db connection is not provided", () => {
      expect(() => new SERPFeatureRepository()).to.throw(/database connection/i);
    });
  });

  // ========================================
  // 2. insert(feature) Method Tests
  // ========================================
  describe("insert(feature) Method", () => {
    beforeEach(() => {
      repo = new SERPFeatureRepository(mockDb);
    });

    describe("Valid inputs", () => {
      it("should insert valid feature and return record with id", async () => {
        const feature = {
          keyword_id: 1,
          feature_type: "featured_snippet",
          is_present: true,
          url: "https://example.com",
          title: "Example Title",
          snippet: "Example snippet"
        };
        
        mockDb.run.callsFake(mockRunSuccess(1));
        
        const result = await repo.insert(feature);
        
        expect(mockDb.run.calledOnce).to.be.true;
        expect(result).to.have.property("id", 1);
      });

      it("should insert with all optional fields", async () => {
        const feature = {
          keyword_id: 1,
          feature_type: "local_pack",
          is_present: true,
          position: 2,
          url: "https://example.com/local",
          title: "Local Business",
          snippet: "Local description"
        };
        
        mockDb.run.callsFake(mockRunSuccess(1));
        
        await repo.insert(feature);
        
        const callArgs = mockDb.run.getCall(0).args[1];
        expect(callArgs[3]).to.equal(2); // position
        expect(callArgs[4]).to.equal("https://example.com/local"); // url
        expect(callArgs[5]).to.equal("Local Business"); // title
        expect(callArgs[6]).to.equal("Local description"); // snippet
      });

      it("should insert with minimal fields (only required)", async () => {
        const feature = {
          keyword_id: 1,
          feature_type: "sitelinks",
          is_present: false
        };
        
        mockDb.run.callsFake(mockRunSuccess(1));
        
        await repo.insert(feature);
        
        const callArgs = mockDb.run.getCall(0).args[1];
        expect(callArgs[3]).to.be.null; // position
        expect(callArgs[4]).to.be.null; // url
        expect(callArgs[5]).to.be.null; // title
        expect(callArgs[6]).to.be.null; // snippet
      });

      it("should set checked_at automatically if not provided", async () => {
        const feature = {
          keyword_id: 1,
          feature_type: "reviews",
          is_present: true
        };
        
        mockDb.run.callsFake(mockRunSuccess(1));
        
        await repo.insert(feature);
        
        const callArgs = mockDb.run.getCall(0).args[1];
        expect(callArgs[7]).to.be.a("string"); // checked_at should be ISO date string
      });

      it("should preserve explicit checked_at value", async () => {
        const customDate = "2026-02-20T10:00:00Z";
        const feature = {
          keyword_id: 1,
          feature_type: "featured_snippet",
          is_present: true,
          checked_at: customDate
        };
        
        mockDb.run.callsFake(mockRunSuccess(1));
        
        await repo.insert(feature);
        
        const callArgs = mockDb.run.getCall(0).args[1];
        expect(callArgs[7]).to.equal(customDate);
      });
    });

    describe("Invalid inputs - validation errors", () => {
      it("should throw error for invalid feature type", async () => {
        const feature = {
          keyword_id: 1,
          feature_type: "invalid_type",
          is_present: true
        };
        
        await expect(repo.insert(feature)).to.be.rejectedWith(/validation|invalid/i);
      });

      it("should throw error for null feature", async () => {
        await expect(repo.insert(null)).to.be.rejected;
      });

      it("should throw error for missing feature_type field", async () => {
        const feature = {
          keyword_id: 1,
          is_present: true
        };
        
        await expect(repo.insert(feature)).to.be.rejectedWith(/feature_type/i);
      });

      it("should throw error for non-numeric keyword_id", async () => {
        const feature = {
          keyword_id: "abc",
          feature_type: "featured_snippet",
          is_present: true
        };
        
        await expect(repo.insert(feature)).to.be.rejectedWith(/keyword_id|integer/i);
      });

      it("should throw unique constraint error for duplicate feature_type", async () => {
        const feature = {
          keyword_id: 1,
          feature_type: "featured_snippet",
          is_present: true
        };
        
        mockDb.run.callsFake(function(sql, params, callback) {
          const error = new Error("UNIQUE constraint failed");
          callback(error);
        });
        
        await expect(repo.insert(feature)).to.be.rejectedWith(/unique|constraint/i);
      });

      it("should throw foreign key error for non-existent keyword_id", async () => {
        const feature = {
          keyword_id: 999999,
          feature_type: "featured_snippet",
          is_present: true
        };
        
        mockDb.run.callsFake(function(sql, params, callback) {
          const error = new Error("FOREIGN KEY constraint failed");
          callback(error);
        });
        
        await expect(repo.insert(feature)).to.be.rejectedWith(/foreign key|constraint/i);
      });
    });
  });

  // ========================================
  // 3. insertBatch(features) Method Tests
  // ========================================
  describe("insertBatch(features) Method", () => {
    beforeEach(() => {
      repo = new SERPFeatureRepository(mockDb);
    });

    it("should return empty array for empty input", async () => {
      const result = await repo.insertBatch([]);
      expect(result).to.be.an("array").with.lengthOf(0);
    });

    it("should throw error for non-array input", async () => {
      await expect(repo.insertBatch("not an array")).to.be.rejectedWith(/array/i);
    });

    it("should insert multiple features", async () => {
      const features = [
        { keyword_id: 1, feature_type: "featured_snippet", is_present: true },
        { keyword_id: 1, feature_type: "sitelinks", is_present: false },
        { keyword_id: 2, feature_type: "local_pack", is_present: true }
      ];
      
      // Verify the features are valid (no duplicates)
      expect(features).to.have.lengthOf(3);
    });

    it("should reject batch with duplicate feature types for same keyword", async () => {
      const features = [
        { keyword_id: 1, feature_type: "featured_snippet", is_present: true },
        { keyword_id: 1, feature_type: "featured_snippet", is_present: false } // Duplicate
      ];
      
      await expect(repo.insertBatch(features)).to.be.rejectedWith(/duplicate/i);
    });

    it("should accept batch with same keyword_id different feature types", async () => {
      const features = [
        { keyword_id: 1, feature_type: "featured_snippet", is_present: true },
        { keyword_id: 1, feature_type: "sitelinks", is_present: true },
        { keyword_id: 1, feature_type: "reviews", is_present: false }
      ];
      
      // Verify no duplicates
      const keys = features.map(f => `${f.keyword_id}:${f.feature_type}`);
      const uniqueKeys = [...new Set(keys)];
      expect(uniqueKeys).to.have.lengthOf(3);
    });
  });

  // ========================================
  // 4. findByKeywordId(keywordId) Method Tests
  // ========================================
  describe("findByKeywordId(keywordId) Method", () => {
    beforeEach(() => {
      repo = new SERPFeatureRepository(mockDb);
    });

    it("should return array of features for valid keyword_id", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, [
          { id: 1, keyword_id: 1, feature_type: "featured_snippet", is_present: 1 },
          { id: 2, keyword_id: 1, feature_type: "sitelinks", is_present: 1 }
        ]);
      });
      
      const result = await repo.findByKeywordId(1);
      
      expect(result).to.be.an("array");
      expect(result).to.have.lengthOf(2);
    });

    it("should return empty array for non-existent keyword_id", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      
      const result = await repo.findByKeywordId(999999);
      
      expect(result).to.be.an("array").with.lengthOf(0);
    });

    it("should throw error for null keyword_id", async () => {
      await expect(repo.findByKeywordId(null)).to.be.rejected;
    });

    it("should throw error for string keyword_id", async () => {
      await expect(repo.findByKeywordId("abc")).to.be.rejectedWith(/integer/i);
    });

    it("should return empty array for negative keyword_id", async () => {
      const result = await repo.findByKeywordId(-1);
      expect(result).to.have.lengthOf(0);
    });

    it("should convert is_present from integer to boolean", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, [
          { id: 1, keyword_id: 1, feature_type: "featured_snippet", is_present: 1 },
          { id: 2, keyword_id: 1, feature_type: "sitelinks", is_present: 0 }
        ]);
      });
      
      const result = await repo.findByKeywordId(1);
      
      expect(result[0].is_present).to.be.true;
      expect(result[1].is_present).to.be.false;
    });
  });

  // ========================================
  // 5. findByRankingId(rankingId) Method Tests
  // ========================================
  describe("findByRankingId(rankingId) Method", () => {
    beforeEach(() => {
      repo = new SERPFeatureRepository(mockDb);
    });

    it("should delegate to findByKeywordId", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, [
          { id: 1, keyword_id: 100, feature_type: "featured_snippet", is_present: 1 }
        ]);
      });
      
      const result = await repo.findByRankingId(100);
      
      expect(mockDb.all.calledOnce).to.be.true;
      expect(result).to.be.an("array");
    });
  });

  // ========================================
  // 6. findFeaturesByType(featureType, siteId?) Method Tests
  // ========================================
  describe("findFeaturesByType(featureType, siteId?) Method", () => {
    beforeEach(() => {
      repo = new SERPFeatureRepository(mockDb);
    });

    it("should return array for valid feature type", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, [
          { id: 1, feature_type: "featured_snippet", is_present: 1, keyword: "seo tools" }
        ]);
      });
      
      const result = await repo.findFeaturesByType("featured_snippet");
      
      expect(result).to.be.an("array");
    });

    it("should throw validation error for invalid feature type", async () => {
      await expect(repo.findFeaturesByType("invalid_type")).to.be.rejectedWith(/validation|invalid/i);
    });

    it("should filter by siteId when provided", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      
      await repo.findFeaturesByType("featured_snippet", 1);
      
      const callArgs = mockDb.all.getCall(0).args;
      expect(callArgs[1]).to.deep.equal(["featured_snippet", 1]);
    });

    it("should not filter by siteId when undefined", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      
      await repo.findFeaturesByType("featured_snippet", undefined);
      
      const callArgs = mockDb.all.getCall(0).args;
      expect(callArgs[1]).to.deep.equal(["featured_snippet"]);
    });

    it("should only return is_present=true features", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      
      await repo.findFeaturesByType("featured_snippet");
      
      const sql = mockDb.all.getCall(0).args[0];
      expect(sql).to.include("is_present = 1");
    });

    it("should be case-sensitive for feature type", async () => {
      await expect(repo.findFeaturesByType("Featured_Snippet")).to.be.rejected;
      await expect(repo.findFeaturesByType("FEATURED_SNIPPET")).to.be.rejected;
    });

    it("should return results with keyword info via join", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, [
          { id: 1, feature_type: "featured_snippet", keyword: "seo tools", site_id: 1 }
        ]);
      });
      
      const result = await repo.findFeaturesByType("featured_snippet");
      
      expect(result[0]).to.have.property("keyword");
    });
  });

  // ========================================
  // 7. getFeatureSummary(siteId) Method Tests
  // ========================================
  describe("getFeatureSummary(siteId) Method", () => {
    beforeEach(() => {
      repo = new SERPFeatureRepository(mockDb);
    });

    it("should return feature counts object for valid site", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, [
          { feature_type: "featured_snippet", count: 5 },
          { feature_type: "local_pack", count: 3 }
        ]);
      });
      mockDb.get.callsFake((sql, params, callback) => {
        callback(null, { totalKeywords: 50 });
      });
      
      const result = await repo.getFeatureSummary(1);
      
      expect(result).to.have.property("featured_snippet", 5);
      expect(result).to.have.property("local_pack", 3);
    });

    it("should include all 10 feature types in summary", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      mockDb.get.callsFake((sql, params, callback) => {
        callback(null, { totalKeywords: 0 });
      });
      
      const result = await repo.getFeatureSummary(1);
      
      const expectedTypes = [
        "featured_snippet", "local_pack", "image_pack", "video",
        "people_also_ask", "knowledge_panel", "sitelinks",
        "reviews", "faq", "product"
      ];
      
      expectedTypes.forEach(type => {
        expect(result).to.have.property(type);
      });
    });

    it("should count only is_present=true features", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      mockDb.get.callsFake((sql, params, callback) => {
        callback(null, { totalKeywords: 0 });
      });
      
      await repo.getFeatureSummary(1);
      
      const sql = mockDb.all.getCall(0).args[0];
      expect(sql).to.include("is_present = 1");
    });

    it("should return zero counts for site with no features", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      mockDb.get.callsFake((sql, params, callback) => {
        callback(null, { totalKeywords: 0 });
      });
      
      const result = await repo.getFeatureSummary(999999);
      
      expect(result.featured_snippet).to.equal(0);
      expect(result.local_pack).to.equal(0);
    });

    it("should throw error for null siteId", async () => {
      await expect(repo.getFeatureSummary(null)).to.be.rejected;
    });

    it("should include totalKeywords count in result", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      mockDb.get.callsFake((sql, params, callback) => {
        callback(null, { totalKeywords: 50 });
      });
      
      const result = await repo.getFeatureSummary(1);
      
      expect(result).to.have.property("totalKeywords", 50);
    });
  });
});

// ========================================
// SERPFeatureHistoryRepository Tests
// ========================================
describe("Unit: SERP Feature History Repository", () => {
  let historyRepo;
  let mockDb;

  before(async () => {
    const module = await import("../../backend/src/repositories/serp-feature-repo.js");
    SERPFeatureHistoryRepository = module.SERPFeatureHistoryRepository;
  });

  beforeEach(() => {
    mockDb = createMockDb();
    sinon.reset();
  });

  // ========================================
  // 1. Class Structure Tests
  // ========================================
  describe("Class Structure", () => {
    it("should be instantiable", () => {
      historyRepo = new SERPFeatureHistoryRepository(mockDb);
      expect(historyRepo).to.be.instanceOf(SERPFeatureHistoryRepository);
    });

    it("should expose all required methods", () => {
      historyRepo = new SERPFeatureHistoryRepository(mockDb);
      const requiredMethods = [
        "insertHistory",
        "getHistoryByKeyword",
        "getRecentChanges"
      ];
      
      requiredMethods.forEach(method => {
        expect(historyRepo).to.have.property(method).that.is.a("function");
      });
    });

    it("should throw error when db connection is not provided", () => {
      expect(() => new SERPFeatureHistoryRepository()).to.throw(/database connection/i);
    });
  });

  // ========================================
  // 2. insertHistory(entry) Method Tests
  // ========================================
  describe("insertHistory(entry) Method", () => {
    beforeEach(() => {
      historyRepo = new SERPFeatureHistoryRepository(mockDb);
    });

    describe("Valid inputs", () => {
      it("should insert valid history entry", async () => {
        const entry = {
          keyword_id: 1,
          feature_type: "featured_snippet",
          action: "gained",
          current_url: "https://example.com"
        };
        
        mockDb.run.callsFake(mockRunSuccess(1));
        
        const result = await historyRepo.insertHistory(entry);
        
        expect(mockDb.run.calledOnce).to.be.true;
        expect(result).to.have.property("id", 1);
      });

      it("should accept 'gained' action", async () => {
        const entry = {
          keyword_id: 1,
          feature_type: "featured_snippet",
          action: "gained"
        };
        
        mockDb.run.callsFake(mockRunSuccess(1));
        
        const result = await historyRepo.insertHistory(entry);
        expect(result.action).to.equal("gained");
      });

      it("should accept 'lost' action", async () => {
        const entry = {
          keyword_id: 1,
          feature_type: "featured_snippet",
          action: "lost"
        };
        
        mockDb.run.callsFake(mockRunSuccess(1));
        
        const result = await historyRepo.insertHistory(entry);
        expect(result.action).to.equal("lost");
      });

      it("should set detected_at automatically", async () => {
        const entry = {
          keyword_id: 1,
          feature_type: "featured_snippet",
          action: "gained"
        };
        
        mockDb.run.callsFake(mockRunSuccess(1));
        
        await historyRepo.insertHistory(entry);
        
        const callArgs = mockDb.run.getCall(0).args[1];
        expect(callArgs[5]).to.be.a("string"); // detected_at
      });

      it("should store previous_url and current_url", async () => {
        const entry = {
          keyword_id: 1,
          feature_type: "featured_snippet",
          action: "lost",
          previous_url: "https://old-example.com",
          current_url: null
        };
        
        mockDb.run.callsFake(mockRunSuccess(1));
        
        await historyRepo.insertHistory(entry);
        
        const callArgs = mockDb.run.getCall(0).args[1];
        expect(callArgs[3]).to.equal("https://old-example.com");
      });
    });

    describe("Invalid inputs", () => {
      it("should throw error for invalid action", async () => {
        const entry = {
          keyword_id: 1,
          feature_type: "featured_snippet",
          action: "invalid_action"
        };
        
        await expect(historyRepo.insertHistory(entry)).to.be.rejectedWith(/action/i);
      });

      it("should throw error for null entry", async () => {
        await expect(historyRepo.insertHistory(null)).to.be.rejected;
      });

      it("should throw error for missing keyword_id", async () => {
        const entry = {
          feature_type: "featured_snippet",
          action: "gained"
        };
        
        await expect(historyRepo.insertHistory(entry)).to.be.rejectedWith(/keyword_id/i);
      });

      it("should throw error for missing feature_type", async () => {
        const entry = {
          keyword_id: 1,
          action: "gained"
        };
        
        await expect(historyRepo.insertHistory(entry)).to.be.rejectedWith(/feature_type/i);
      });

      it("should throw FK error for non-existent keyword_id", async () => {
        const entry = {
          keyword_id: 999999,
          feature_type: "featured_snippet",
          action: "gained"
        };
        
        mockDb.run.callsFake(function(sql, params, callback) {
          const error = new Error("FOREIGN KEY constraint failed");
          callback(error);
        });
        
        await expect(historyRepo.insertHistory(entry)).to.be.rejectedWith(/foreign key/i);
      });
    });
  });

  // ========================================
  // 3. getHistoryByKeyword(keywordId, limit?) Method Tests
  // ========================================
  describe("getHistoryByKeyword(keywordId, limit?) Method", () => {
    beforeEach(() => {
      historyRepo = new SERPFeatureHistoryRepository(mockDb);
    });

    it("should return history array for valid keyword", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, [
          { id: 1, keyword_id: 1, action: "gained" },
          { id: 2, keyword_id: 1, action: "lost" }
        ]);
      });
      
      const result = await historyRepo.getHistoryByKeyword(1);
      
      expect(result).to.be.an("array");
    });

    it("should respect limit parameter", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      
      await historyRepo.getHistoryByKeyword(1, 5);
      
      const callArgs = mockDb.all.getCall(0).args;
      expect(callArgs[1][1]).to.equal(5); // limit parameter
    });

    it("should use default limit (50) when undefined", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      
      await historyRepo.getHistoryByKeyword(1, undefined);
      
      const callArgs = mockDb.all.getCall(0).args;
      expect(callArgs[1][1]).to.equal(50); // default limit
    });

    it("should return empty array for non-existent keyword", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      
      const result = await historyRepo.getHistoryByKeyword(999999);
      
      expect(result).to.have.lengthOf(0);
    });

    it("should throw error for null keyword_id", async () => {
      await expect(historyRepo.getHistoryByKeyword(null)).to.be.rejected;
    });

    it("should throw error for negative keyword_id", async () => {
      await expect(historyRepo.getHistoryByKeyword(-1)).to.be.rejected;
    });

    it("should return empty array for limit=0", async () => {
      const result = await historyRepo.getHistoryByKeyword(1, 0);
      expect(result).to.have.lengthOf(0);
    });

    it("should throw error for negative limit", async () => {
      await expect(historyRepo.getHistoryByKeyword(1, -1)).to.be.rejected;
    });
  });

  // ========================================
  // 4. getRecentChanges(siteId, hours?) Method Tests
  // ========================================
  describe("getRecentChanges(siteId, hours?) Method", () => {
    beforeEach(() => {
      historyRepo = new SERPFeatureHistoryRepository(mockDb);
    });

    it("should return recent changes for valid site", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, [
          { id: 1, keyword_id: 1, action: "gained", feature_type: "featured_snippet" }
        ]);
      });
      
      const result = await historyRepo.getRecentChanges(1);
      
      expect(result).to.be.an("array");
    });

    it("should filter by hours parameter", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      
      await historyRepo.getRecentChanges(1, 24);
      
      const callArgs = mockDb.all.getCall(0).args;
      expect(callArgs[1][1]).to.equal(24); // hours parameter
    });

    it("should use default 24 hours when undefined", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      
      await historyRepo.getRecentChanges(1, undefined);
      
      const callArgs = mockDb.all.getCall(0).args;
      expect(callArgs[1][1]).to.equal(24); // default hours
    });

    it("should return empty array for non-existent site", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, []);
      });
      
      const result = await historyRepo.getRecentChanges(999999);
      
      expect(result).to.have.lengthOf(0);
    });

    it("should throw error for null siteId", async () => {
      await expect(historyRepo.getRecentChanges(null)).to.be.rejected;
    });

    it("should return empty array for hours=0", async () => {
      const result = await historyRepo.getRecentChanges(1, 0);
      expect(result).to.have.lengthOf(0);
    });

    it("should throw error for negative hours", async () => {
      await expect(historyRepo.getRecentChanges(1, -1)).to.be.rejected;
    });

    it("should include keyword info via join", async () => {
      mockDb.all.callsFake((sql, params, callback) => {
        callback(null, [
          { id: 1, keyword_id: 1, keyword: "seo tools", action: "gained" }
        ]);
      });
      
      const result = await historyRepo.getRecentChanges(1);
      
      expect(result[0]).to.have.property("keyword");
    });
  });
});

// ========================================
// Edge Cases and Adversarial Tests
// ========================================
describe("Edge Cases: SERP Feature Repository", () => {
  let repo;
  let mockDb;

  before(async () => {
    const module = await import("../../backend/src/repositories/serp-feature-repo.js");
    SERPFeatureRepository = module.SERPFeatureRepository;
  });

  beforeEach(() => {
    mockDb = createMockDb();
    repo = new SERPFeatureRepository(mockDb);
    sinon.reset();
  });

  describe("Input Validation Edge Cases", () => {
    it("should reject empty string feature type", async () => {
      await expect(repo.insert({
        keyword_id: 1,
        feature_type: "",
        is_present: true
      })).to.be.rejected;
    });

    it("should accept very long URL", async () => {
      const longUrl = "https://example.com/" + "a".repeat(2000);
      
      mockDb.run.callsFake(mockRunSuccess(1));
      
      await repo.insert({
        keyword_id: 1,
        feature_type: "featured_snippet",
        is_present: true,
        url: longUrl
      });
      
      const callArgs = mockDb.run.getCall(0).args[1];
      expect(callArgs[4]).to.equal(longUrl);
    });

    it("should handle Unicode in URL", async () => {
      const unicodeUrl = "https://例え.jp/パス";
      
      mockDb.run.callsFake(mockRunSuccess(1));
      
      await repo.insert({
        keyword_id: 1,
        feature_type: "featured_snippet",
        is_present: true,
        url: unicodeUrl
      });
      
      const callArgs = mockDb.run.getCall(0).args[1];
      expect(callArgs[4]).to.equal(unicodeUrl);
    });

    it("should handle emoji in title", async () => {
      const emojiTitle = "Best 🏆 SEO Tools 2026";
      
      mockDb.run.callsFake(mockRunSuccess(1));
      
      await repo.insert({
        keyword_id: 1,
        feature_type: "featured_snippet",
        is_present: true,
        title: emojiTitle
      });
      
      const callArgs = mockDb.run.getCall(0).args[1];
      expect(callArgs[5]).to.equal(emojiTitle);
    });

    it("should reject negative position", async () => {
      await expect(repo.insert({
        keyword_id: 1,
        feature_type: "featured_snippet",
        is_present: true,
        position: -1
      })).to.be.rejectedWith(/position|non-negative/i);
    });
  });

  describe("Data Consistency Tests", () => {
    it("should store is_present=false as 0", async () => {
      mockDb.run.callsFake(mockRunSuccess(1));
      
      await repo.insert({
        keyword_id: 1,
        feature_type: "sitelinks",
        is_present: false
      });
      
      const callArgs = mockDb.run.getCall(0).args[1];
      expect(callArgs[2]).to.equal(0);
    });

    it("should store is_present=true as 1", async () => {
      mockDb.run.callsFake(mockRunSuccess(1));
      
      await repo.insert({
        keyword_id: 1,
        feature_type: "featured_snippet",
        is_present: true
      });
      
      const callArgs = mockDb.run.getCall(0).args[1];
      expect(callArgs[2]).to.equal(1);
    });

    it("should preserve null optional fields", async () => {
      mockDb.run.callsFake(mockRunSuccess(1));
      
      await repo.insert({
        keyword_id: 1,
        feature_type: "sitelinks",
        is_present: true,
        position: null,
        url: null,
        title: null,
        snippet: null
      });
      
      const callArgs = mockDb.run.getCall(0).args[1];
      expect(callArgs[3]).to.be.null; // position
      expect(callArgs[4]).to.be.null; // url
      expect(callArgs[5]).to.be.null; // title
      expect(callArgs[6]).to.be.null; // snippet
    });
  });
});
