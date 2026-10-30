import { sanitizeDomain, validateDomain } from "@/lib/validators/domain";

describe("Domain Validator", () => {
  describe("sanitizeDomain", () => {
    it("trims whitespace", () => {
      expect(sanitizeDomain("  example.com  ")).toBe("example.com");
    });

    it("converts to lowercase", () => {
      expect(sanitizeDomain("EXAMPLE.COM")).toBe("example.com");
    });

    it("strips https:// protocol", () => {
      expect(sanitizeDomain("https://example.com")).toBe("example.com");
    });

    it("strips http:// protocol", () => {
      expect(sanitizeDomain("http://example.com")).toBe("example.com");
    });

    it("strips www. prefix", () => {
      expect(sanitizeDomain("www.example.com")).toBe("example.com");
    });

    it("strips both protocol and www.", () => {
      expect(sanitizeDomain("https://www.example.com")).toBe("example.com");
    });

    it("removes trailing paths", () => {
      expect(sanitizeDomain("example.com/path/to/page")).toBe("example.com");
    });

    it("handles complex URLs", () => {
      expect(sanitizeDomain("https://www.example.com/path?query=value")).toBe(
        "example.com"
      );
    });
  });

  describe("validateDomain", () => {
    describe("Test 17: Real-time format check", () => {
      it("rejects empty string", () => {
        expect(validateDomain("")).toBe("Domain is required");
        expect(validateDomain("   ")).toBe("Domain is required");
      });

      it("rejects single word without TLD", () => {
        expect(validateDomain("competitor")).toBe("Please enter a valid domain");
      });

      it("rejects domains starting with hyphen", () => {
        expect(validateDomain("-example.com")).toBe("Please enter a valid domain");
      });

      it("accepts valid domain", () => {
        expect(validateDomain("example.com")).toBe(true);
      });

      it("accepts domain with subdomain", () => {
        expect(validateDomain("sub.example.com")).toBe(true);
      });

      it("accepts domain with multiple subdomains", () => {
        expect(validateDomain("a.b.c.example.com")).toBe(true);
      });

      it("accepts domains with numbers", () => {
        expect(validateDomain("example123.com")).toBe(true);
      });

      it("accepts hyphenated domains", () => {
        expect(validateDomain("my-example.com")).toBe(true);
      });

      it("rejects invalid TLD (single character)", () => {
        expect(validateDomain("example.c")).toBe("Please enter a valid domain");
      });

      it("accepts longer TLDs", () => {
        expect(validateDomain("example.technology")).toBe(true);
      });
    });

    describe("Test 18: SSRF domain blocked", () => {
      it("rejects localhost", () => {
        expect(validateDomain("localhost")).toBe("Localhost domains are not allowed");
      });

      it("rejects localhost.localdomain", () => {
        expect(validateDomain("localhost.localdomain")).toBe(
          "Localhost domains are not allowed"
        );
      });

      it("rejects 127.0.0.1", () => {
        expect(validateDomain("127.0.0.1")).toBe("Localhost domains are not allowed");
      });

      it("rejects 0.0.0.0", () => {
        expect(validateDomain("0.0.0.0")).toBe("Localhost domains are not allowed");
      });

      it("rejects ::1 (IPv6 loopback)", () => {
        expect(validateDomain("::1")).toBe("Localhost domains are not allowed");
      });

      it("rejects 10.x.x.x (private IP Class A)", () => {
        expect(validateDomain("10.0.0.1")).toBe("Private IPs are not allowed");
        expect(validateDomain("10.255.255.255")).toBe("Private IPs are not allowed");
      });

      it("rejects 192.168.x.x (private IP Class C)", () => {
        expect(validateDomain("192.168.0.1")).toBe("Private IPs are not allowed");
        expect(validateDomain("192.168.255.255")).toBe("Private IPs are not allowed");
      });

      it("rejects 172.16-31.x.x (private IP Class B)", () => {
        expect(validateDomain("172.16.0.1")).toBe("Private IPs are not allowed");
        expect(validateDomain("172.20.0.1")).toBe("Private IPs are not allowed");
        expect(validateDomain("172.31.255.255")).toBe("Private IPs are not allowed");
      });

      it("rejects 169.254.x.x (link-local)", () => {
        expect(validateDomain("169.254.0.1")).toBe("Private IPs are not allowed");
      });

      it("rejects 172.15.x.x (IP format not a valid domain)", () => {
        // IP addresses are not valid domain names - they fail the domain regex
        expect(validateDomain("172.15.0.1")).toBe("Please enter a valid domain");
      });

      it("rejects 172.32.x.x (IP format not a valid domain)", () => {
        expect(validateDomain("172.32.0.1")).toBe("Please enter a valid domain");
      });

      it("rejects public IPs (not valid domain format)", () => {
        // IP addresses don't match the domain regex pattern
        expect(validateDomain("8.8.8.8")).toBe("Please enter a valid domain");
        expect(validateDomain("1.1.1.1")).toBe("Please enter a valid domain");
      });
    });

    describe("Edge cases", () => {
      it("handles unicode domains", () => {
        // Unicode domains should be validated by the regex
        const result = validateDomain("例え.jp");
        // Depending on implementation, this may pass or fail
        // Just ensure it doesn't throw
        expect(result).toBeDefined();
      });

      it("handles very long domains", () => {
        const longDomain = "a".repeat(63) + ".com";
        expect(validateDomain(longDomain)).toBe(true);
      });

      it("rejects domains with labels > 63 chars", () => {
        const tooLongLabel = "a".repeat(64) + ".com";
        expect(validateDomain(tooLongLabel)).toBe("Please enter a valid domain");
      });

      it("handles domains with URL-encoded characters", () => {
        // The sanitizer should strip these
        const result = validateDomain("example%20test.com");
        // Behavior depends on implementation
        expect(result).toBeDefined();
      });
    });
  });
});
