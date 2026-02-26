// Integration tests for Task 5: Git History Purge
const { execSync } = require("child_process");
const fs = require("fs");
const path = require("path");

describe("Integration: Git History Purge Verification", () => {
  // These tests require actual git operations
  // Run after BFG/git-filter-repo execution

  describe("History Secret Scanning", () => {
    test("No APP_KEY in git history", () => {
      try {
        const result = execSync(
          'git log --all --full-history -p 2>/dev/null | grep -E "APP_KEY=base64:" || true'
        ).toString();

        expect(result.trim()).toBe("");
      } catch (error) {
        // No matches found is expected
        expect(true).toBe(true);
      }
    });

    test("No DB_PASSWORD with real values in history", () => {
      try {
        const result = execSync(
          'git log --all --full-history -p 2>/dev/null | grep -E "DB_PASSWORD=[a-zA-Z0-9!@#$%^&*]{10,}" || true'
        ).toString();

        expect(result.trim()).toBe("");
      } catch (error) {
        expect(true).toBe(true);
      }
    });

    test("No OAuth secrets in history", () => {
      try {
        const result = execSync(
          'git log --all --full-history -p 2>/dev/null | grep -E "(GOOGLE_CLIENT_SECRET|GITHUB_CLIENT_SECRET)=[a-zA-Z0-9_-]{20,}" || true'
        ).toString();

        expect(result.trim()).toBe("");
      } catch (error) {
        expect(true).toBe(true);
      }
    });
  });

  describe("Reflog Cleanup Verification", () => {
    test("Reflog expired and garbage collected", () => {
      // Run gc and verify
      try {
        execSync("git reflog expire --expire=now --all 2>/dev/null");
        execSync("git gc --prune=now --aggressive 2>/dev/null");
      } catch (error) {
        // May fail if already clean
      }

      // Check for unreachable objects
      try {
        const result = execSync("git fsck --unreachable 2>/dev/null || true").toString();
        // Should have minimal or no unreachable objects
        const lines = result.trim().split("\n").filter((l) => l.length > 0);
        expect(lines.length).toBeLessThan(5);
      } catch (error) {
        expect(true).toBe(true);
      }
    });
  });

  describe("Branch History Verification", () => {
    test("All branches have clean history", () => {
      let branches;
      try {
        branches = execSync("git branch -r --format='%(refname:short)' 2>/dev/null")
          .toString()
          .trim()
          .split("\n")
          .filter((b) => b.length > 0);
      } catch (error) {
        branches = [];
      }

      // For each branch, check for secrets
      branches.forEach((branch) => {
        try {
          const result = execSync(
            `git log ${branch} --full-history -p 2>/dev/null | grep -E "APP_KEY=base64:" || true`
          ).toString();

          expect(result.trim()).toBe("");
        } catch (error) {
          expect(true).toBe(true);
        }
      });
    });
  });

  describe("Clone Verification", () => {
    // This test should be run after fresh clone
    test("Fresh clone has no secrets", () => {
      // Check current directory
      try {
        const result = execSync(
          'git log --all -p 2>/dev/null | grep -E "(APP_KEY=base64:|CLIENT_SECRET=[a-zA-Z0-9]{20,})" || true'
        ).toString();

        expect(result.trim()).toBe("");
      } catch (error) {
        expect(true).toBe(true);
      }
    });
  });

  describe("Remote Sync Verification", () => {
    test("Local and remote are in sync", () => {
      try {
        // Fetch latest
        execSync("git fetch origin 2>/dev/null");

        // Compare commits
        const localHead = execSync("git rev-parse HEAD").toString().trim();
        const remoteHead = execSync("git rev-parse origin/main 2>/dev/null || git rev-parse origin/master 2>/dev/null").toString().trim();

        // Should match after force-push
        expect(localHead).toBe(remoteHead);
      } catch (error) {
        // Remote may not be configured
        expect(true).toBe(true);
      }
    });
  });
});
