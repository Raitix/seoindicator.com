// Unit tests for Task 4: OAuth Production Configuration
const { OAuthConfigValidator } = require("../../lib/oauthConfig");

describe("Unit: OAuth Configuration Validation", () => {
  describe("Redirect URI Validation", () => {
    test("Accepts valid HTTPS redirect URIs", () => {
      const validUris = [
        "https://seoindicator.com/auth/google/callback",
        "https://seoindicator.com/auth/github/callback",
      ];

      validUris.forEach((uri) => {
        expect(OAuthConfigValidator.isValidRedirectUri(uri)).toBe(true);
      });
    });

    test("Rejects HTTP redirect URIs in production", () => {
      const invalidUris = [
        "http://seoindicator.com/auth/callback",
        "http://localhost/auth/callback",
      ];

      invalidUris.forEach((uri) => {
        expect(OAuthConfigValidator.isValidRedirectUri(uri, "production")).toBe(false);
      });
    });

    test("Allows HTTP for local development", () => {
      const devUri = "http://localhost:3000/auth/callback";
      expect(OAuthConfigValidator.isValidRedirectUri(devUri, "development")).toBe(true);
    });
  });

  describe("Environment Variable Checks", () => {
    test("Detects missing OAuth secrets", () => {
      const env = {
        GOOGLE_CLIENT_ID: "test-id",
        // Missing GOOGLE_CLIENT_SECRET
      };

      const missing = OAuthConfigValidator.findMissingSecrets(env);
      expect(missing).toContain("GOOGLE_CLIENT_SECRET");
    });

    test("Returns empty array when all secrets present", () => {
      const env = {
        GOOGLE_CLIENT_ID: "test-id",
        GOOGLE_CLIENT_SECRET: "test-secret",
        GITHUB_CLIENT_ID: "github-id",
        GITHUB_CLIENT_SECRET: "github-secret",
      };

      const missing = OAuthConfigValidator.findMissingSecrets(env);
      expect(missing).toHaveLength(0);
    });
  });

  describe("Token Encryption Verification", () => {
    test("Validates encrypted cast on token field", () => {
      const model = {
        casts: {
          token: "encrypted",
          refresh_token: "encrypted",
        },
      };

      expect(OAuthConfigValidator.hasEncryptedTokens(model)).toBe(true);
    });

    test("Fails when tokens are not encrypted", () => {
      const model = {
        casts: {
          token: "string",
        },
      };

      expect(OAuthConfigValidator.hasEncryptedTokens(model)).toBe(false);
    });
  });

  describe("State Parameter Validation", () => {
    test("Generates secure state parameter", () => {
      const state1 = OAuthConfigValidator.generateState();
      const state2 = OAuthConfigValidator.generateState();

      expect(state1).not.toBe(state2);
      expect(state1.length).toBeGreaterThanOrEqual(32);
    });

    test("Validates state parameter matches", () => {
      const state = OAuthConfigValidator.generateState();
      OAuthConfigValidator.storeState("session-1", state);

      expect(OAuthConfigValidator.validateState("session-1", state)).toBe(true);
    });

    test("Rejects invalid state parameter", () => {
      const state = OAuthConfigValidator.generateState();
      OAuthConfigValidator.storeState("session-1", state);

      expect(OAuthConfigValidator.validateState("session-1", "wrong-state")).toBe(false);
    });
  });

  describe("Scope Validation", () => {
    test("Identifies minimal required scopes", () => {
      const minimalScopes = ["openid", "profile", "email"];
      expect(OAuthConfigValidator.areScopesMinimal(minimalScopes)).toBe(true);
    });

    test("Flags excessive scopes", () => {
      const excessiveScopes = ["openid", "profile", "email", "drive", "calendar"];
      expect(OAuthConfigValidator.areScopesMinimal(excessiveScopes)).toBe(false);
    });
  });
});
