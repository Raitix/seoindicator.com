// Unit tests for Task 5: Git History Purge
const { GitHistoryScanner } = require("../../lib/gitHistoryScanner");
const { execSync } = require("child_process");

describe("Unit: Git History Purge Verification", () => {
  describe("Secret Pattern Detection", () => {
    test("Detects APP_KEY pattern", () => {
      const content = "APP_KEY=base64:abcd1234efgh5678ijkl9012mnop3456";
      expect(GitHistoryScanner.containsSecretPattern(content)).toBe(true);
    });

    test("Detects DB_PASSWORD pattern", () => {
      const content = "DB_PASSWORD=SuperSecretPassword123!";
      expect(GitHistoryScanner.containsSecretPattern(content)).toBe(true);
    });

    test("Detects OAuth secret pattern", () => {
      const content = "GOOGLE_CLIENT_SECRET=GOCSPX-abcdefghijklmnopqrstuvwx";
      expect(GitHistoryScanner.containsSecretPattern(content)).toBe(true);
    });

    test("Ignores placeholder values", () => {
      const content = "DB_PASSWORD=your_db_password_here";
      expect(GitHistoryScanner.containsSecretPattern(content)).toBe(false);
    });
  });

  describe("BFG Report Parsing", () => {
    test("Parses successful BFG output", () => {
      const bfgOutput = `
        Cleaning...
        Found 15 commits to update
        Updated 15 commits
        Tree cleaned successfully
      `;

      const result = GitHistoryScanner.parseBFGReport(bfgOutput);
      expect(result.commitsUpdated).toBe(15);
      expect(result.success).toBe(true);
    });

    test("Detects BFG warnings", () => {
      const bfgOutput = `
        Cleaning...
        Warning: protected commits still contain secrets
        Found 5 commits to update
      `;

      const result = GitHistoryScanner.parseBFGReport(bfgOutput);
      expect(result.warnings).toContain("protected commits");
    });
  });

  describe("Reflog Verification", () => {
    test("Checks for unreachable objects", () => {
      // Mock git fsck output
      const fsckOutput = "dangling commit abc123\nunreachable blob def456";
      const result = GitHistoryScanner.parseFsckOutput(fsckOutput);

      expect(result.unreachableCount).toBe(2);
    });
  });
});
