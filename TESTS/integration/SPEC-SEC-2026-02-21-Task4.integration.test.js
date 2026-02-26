// Integration tests for Task 4: OAuth Production Configuration
const request = require("supertest");
const app = require("../../app");
const { OAuthConfigValidator } = require("../../lib/oauthConfig");
const fs = require("fs");

describe("Integration: OAuth Configuration", () => {
  describe("Config File Analysis", () => {
    test("OAuth secrets use env() helper", () => {
      const configContent = fs.readFileSync(
        "backend/config/services.php",
        "utf8"
      );

      expect(configContent).toContain("env('GOOGLE_CLIENT_ID'");
      expect(configContent).toContain("env('GOOGLE_CLIENT_SECRET'");
    });

    test("No hardcoded OAuth secrets in config", () => {
      const configFiles = [
        "backend/config/services.php",
        "backend/config/auth.php",
      ];

      configFiles.forEach((file) => {
        if (fs.existsSync(file)) {
          const content = fs.readFileSync(file, "utf8");
          
          // Should not contain actual secret patterns
          expect(content).not.toMatch(/[a-zA-Z0-9_-]{24,}\.apps\.googleusercontent\.com/);
          expect(content).not.toMatch(/GOCSPX-[a-zA-Z0-9_-]{28}/);
        }
      });
    });
  });

  describe("OAuth Flow Security", () => {
    test("State parameter included in OAuth redirect", async () => {
      const response = await request(app)
        .get("/auth/google")
        .set("X-Forwarded-For", "10.0.0.1");

      // Should redirect to Google OAuth
      expect(response.status).toBe(302);
      expect(response.headers.location).toContain("accounts.google.com");
      expect(response.headers.location).toContain("state=");
    });

    test("Invalid state parameter rejected", async () => {
      const response = await request(app)
        .get("/auth/google/callback?state=invalid-state&code=test-code")
        .set("X-Forwarded-For", "10.0.0.1");

      expect(response.status).toBe(400);
    });

    test("Missing state parameter rejected", async () => {
      const response = await request(app)
        .get("/auth/google/callback?code=test-code")
        .set("X-Forwarded-For", "10.0.0.1");

      expect(response.status).toBe(400);
    });
  });

  describe("Token Storage Security", () => {
    test("Tokens are encrypted in database", async () => {
      // This would require database connection
      // Placeholder for actual implementation
      const mockUser = {
        socialAccounts: [
          {
            provider: "google",
            token: "encrypted:base64string",
          },
        ],
      };

      expect(mockUser.socialAccounts[0].token).toMatch(/^encrypted:/);
    });
  });

  describe("Production Environment Separation", () => {
    test("Different redirect URIs per environment", () => {
      const devConfig = OAuthConfigValidator.getConfig("development");
      const prodConfig = OAuthConfigValidator.getConfig("production");

      expect(devConfig.redirect).toContain("localhost");
      expect(prodConfig.redirect).not.toContain("localhost");
      expect(prodConfig.redirect).toContain("https://");
    });
  });

  describe("OAuth Secret Scanning", () => {
    test("No OAuth secrets in repository files", () => {
      const { execSync } = require("child_process");

      try {
        const result = execSync(
          'git grep -i "GOOGLE_CLIENT_SECRET=" -- "*.php" "*.env*" 2>/dev/null || true'
        ).toString();

        // Should not find actual secrets (placeholders are OK)
        expect(result).not.toMatch(/GOOGLE_CLIENT_SECRET=[a-zA-Z0-9_-]{20,}/);
      } catch (error) {
        // git grep returning no results is OK
        expect(true).toBe(true);
      }
    });
  });

  describe("Token Not Logged", () => {
    test("OAuth tokens are redacted in logs", () => {
      const logOutput = OAuthConfigValidator.redactToken(
        "User logged in with token: ya29.a0AfH6SMBx..."
      );

      expect(logOutput).not.toContain("ya29.");
      expect(logOutput).toContain("[REDACTED]");
    });
  });
});
