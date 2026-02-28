<?php

namespace App\Services\SEO;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Domain Trust Scoring Service.
 *
 * Evaluates domain quality using heuristic analysis to estimate
 * authority score (0-100) for backlink quality assessment.
 *
 * Scoring Components:
 * - HTTPS support (0-20 points)
 * - Domain age estimation (0-30 points)
 * - TLD quality (0-15 points)
 * - Domain length (0-10 points)
 * - Clean pattern (0-10 points)
 * - Spam TLD penalty (-30 points)
 *
 * @see TASK-23-7
 * @see SPEC-023
 */
class DomainTrustScorer
{
    /**
     * Trusted TLDs that typically indicate established organizations.
     */
    private const TRUSTED_TLDS = ['com', 'org', 'net', 'edu', 'gov'];

    /**
     * Moderate TLDs that are somewhat trusted.
     */
    private const MODERATE_TLDS = ['io', 'co', 'dev', 'app'];

    /**
     * Known spam/low-quality TLDs.
     */
    private const SPAM_TLDS = ['xyz', 'top', 'click', 'loan', 'work', 'date'];

    /**
     * Maximum possible score.
     */
    private const MAX_SCORE = 100;

    /**
     * Minimum possible score.
     */
    private const MIN_SCORE = 0;

    /**
     * Penalty applied for spam TLDs.
     */
    private const SPAM_PENALTY = 30;

    /**
     * HTTPS check timeout in seconds.
     */
    private const HTTPS_TIMEOUT = 5;

    /**
     * Calculate domain trust score (0-100).
     *
     * @param string|null $domain Domain to score
     * @return int Trust score between 0-100
     */
    public function score(?string $domain): int
    {
        // Handle null/empty input
        if ($domain === null || $domain === '') {
            return 0;
        }

        // Normalize the domain
        $domain = $this->normalizeDomain($domain);

        // Handle invalid/edge cases
        if ($domain === '' || $this->isIpAddress($domain) || $this->isLocalhost($domain)) {
            return 0;
        }

        $score = 0;

        // HTTPS support (0-20 points)
        if ($this->supportsHttps($domain)) {
            $score += 20;
        }

        // Domain age estimation (0-30 points)
        $score += min(30, $this->estimateAge($domain));

        // TLD quality (0-15 points)
        $score += $this->scoreTld($domain);

        // Domain length (0-10 points)
        $score += $this->scoreLength($domain);

        // Clean pattern (0-10 points)
        if ($this->isCleanPattern($this->getDomainPart($domain))) {
            $score += 10;
        }

        // Spam TLD penalty
        if ($this->isSpamTld($domain)) {
            $score = max(0, $score - self::SPAM_PENALTY);
        }

        // Ensure score is within bounds
        return min(self::MAX_SCORE, max(self::MIN_SCORE, $score));
    }

    /**
     * Check if domain supports HTTPS.
     *
     * @param string $domain Domain to check
     * @return bool True if HTTPS is supported
     */
    public function supportsHttps(string $domain): bool
    {
        // IP addresses don't get HTTPS points
        if ($this->isIpAddress($domain)) {
            return false;
        }

        try {
            $response = Http::timeout(self::HTTPS_TIMEOUT)
                ->withoutVerifying()
                ->head("https://{$domain}/");

            return $response->successful();
        } catch (\Exception $e) {
            // Connection failed, timeout, or other error
            return false;
        }
    }

    /**
     * Estimate domain age in years for scoring.
     *
     * Returns an estimated age score (0-30 points).
     * Uses conservative heuristics when actual age is unknown.
     *
     * @param string $domain Domain to estimate
     * @return int Age score (0-30)
     */
    public function estimateAge(string $domain): int
    {
        // IP addresses don't get age points
        if ($this->isIpAddress($domain)) {
            return 0;
        }

        try {
            // Try to get DNS SOA record which may contain creation info
            $records = @dns_get_record($domain, DNS_SOA);

            if ($records && isset($records[0]['serial'])) {
                // SOA serial can give us a rough age estimate
                // This is a heuristic - serial numbers vary by registrar
                $serial = $records[0]['serial'];
                $year = (int) substr((string) $serial, 0, 4);

                if ($year >= 1990 && $year <= (int) date('Y')) {
                    // Valid year-like serial, estimate age
                    $estimatedYears = (int) date('Y') - $year;
                    return $this->calculateAgeScore($estimatedYears);
                }
            }

            // Try DNS_NS records - older domains tend to have stable NS records
            $nsRecords = @dns_get_record($domain, DNS_NS);
            if ($nsRecords && count($nsRecords) >= 2) {
                // Has multiple nameservers - likely established
                // Give moderate score as a conservative estimate
                return 10;
            }

            // Check if domain resolves at all
            $aRecords = @dns_get_record($domain, DNS_A);
            if ($aRecords) {
                // Domain exists and resolves - give minimum age score
                return 5;
            }
        } catch (\Exception $e) {
            // DNS lookup failed
        }

        // Unknown age - no points
        return 0;
    }

    /**
     * Score TLD quality.
     *
     * @param string $domain Domain to score
     * @return int TLD score (0-15)
     */
    public function scoreTld(string $domain): int
    {
        $tld = $this->extractTld($domain);

        if ($tld === '') {
            return 0;
        }

        // Trusted TLDs
        if (in_array($tld, self::TRUSTED_TLDS, true)) {
            return 15;
        }

        // Moderate TLDs
        if (in_array($tld, self::MODERATE_TLDS, true)) {
            return 10;
        }

        // Spam TLDs - handled separately with penalty
        // Return 0 base score for spam TLDs
        if (in_array($tld, self::SPAM_TLDS, true)) {
            return 0;
        }

        // Other TLDs
        return 5;
    }

    /**
     * Score domain length.
     *
     * Shorter domains are typically more established/valuable.
     *
     * @param string $domain Domain to score
     * @return int Length score (0-10)
     */
    public function scoreLength(string $domain): int
    {
        $domainPart = $this->getDomainPart($domain);
        $length = strlen($domainPart);

        if ($length <= 8) {
            return 10;
        }

        if ($length <= 15) {
            return 5;
        }

        return 0;
    }

    /**
     * Check if domain has a clean pattern (letters only, no hyphens/numbers).
     *
     * @param string $domainPart Domain name part (without TLD)
     * @return bool True if pattern is clean
     */
    public function isCleanPattern(string $domainPart): bool
    {
        // Only lowercase letters
        return preg_match('/^[a-z]+$/', $domainPart) === 1;
    }

    /**
     * Check if TLD is a known spam TLD.
     *
     * @param string $domain Domain to check
     * @return bool True if spam TLD
     */
    public function isSpamTld(string $domain): bool
    {
        $tld = $this->extractTld($domain);

        return in_array($tld, self::SPAM_TLDS, true);
    }

    /**
     * Extract TLD from domain.
     *
     * @param string $domain Domain to extract from
     * @return string TLD (lowercase) or empty string
     */
    public function extractTld(string $domain): string
    {
        // Handle null bytes and special characters
        $domain = str_replace("\x00", '', $domain);
        $domain = preg_replace('/[^\w\.\-]/', '', $domain);

        $parts = explode('.', $domain);
        $count = count($parts);

        if ($count < 2) {
            return '';
        }

        // Handle known multi-part TLDs (co.uk, com.au, etc.)
        $tld = strtolower($parts[$count - 1]);

        // Check for country code TLDs with second-level domain
        if ($count >= 3) {
            $secondLevel = strtolower($parts[$count - 2]);
            $compoundTlds = ['co.uk', 'com.au', 'org.uk', 'net.au', 'co.nz', 'co.jp'];

            if (in_array("{$secondLevel}.{$tld}", $compoundTlds, true)) {
                return "{$secondLevel}.{$tld}";
            }
        }

        return $tld;
    }

    /**
     * Extract apex domain from full domain/subdomain.
     *
     * @param string $domain Full domain
     * @return string Apex domain
     */
    public function extractApexDomain(string $domain): string
    {
        // Normalize first
        $domain = $this->normalizeDomain($domain);

        $parts = explode('.', $domain);
        $count = count($parts);

        if ($count <= 2) {
            return $domain;
        }

        // Handle compound TLDs
        $tld = strtolower($parts[$count - 1]);
        $secondLevel = strtolower($parts[$count - 2] ?? '');
        $compoundTlds = ['co.uk', 'com.au', 'org.uk', 'net.au', 'co.nz', 'co.jp'];

        if (in_array("{$secondLevel}.{$tld}", $compoundTlds, true)) {
            // compound TLD: return last 3 parts
            return implode('.', array_slice($parts, -3));
        }

        // Standard TLD: return last 2 parts
        return implode('.', array_slice($parts, -2));
    }

    /**
     * Get domain part (without TLD).
     *
     * @param string $domain Full domain
     * @return string Domain name part
     */
    private function getDomainPart(string $domain): string
    {
        $apexDomain = $this->extractApexDomain($domain);
        $parts = explode('.', $apexDomain);

        if (count($parts) < 2) {
            return $apexDomain;
        }

        // For compound TLDs, return the third-to-last part
        $tld = $this->extractTld($domain);
        if (substr_count($tld, '.') > 0) {
            // Compound TLD
            return $parts[count($parts) - 3] ?? $parts[0];
        }

        // Standard TLD - return the first part of apex domain
        return $parts[0];
    }

    /**
     * Normalize domain string.
     *
     * - Strips protocol, path, query, port, fragment
     * - Converts to lowercase
     * - Handles IDN (punycode)
     *
     * @param string $domain Domain to normalize
     * @return string Normalized domain
     */
    private function normalizeDomain(string $domain): string
    {
        // Handle null bytes
        $domain = str_replace("\x00", '', $domain);

        // Trim whitespace
        $domain = trim($domain);

        // Strip protocol
        $domain = preg_replace('!^https?://!i', '', $domain);

        // Strip path, query, fragment (use ! delimiter to avoid conflict with #)
        $domain = preg_replace('![/?#].*$!', '', $domain);

        // Strip port
        $domain = preg_replace('!:\d+$!', '', $domain);

        // Convert to lowercase
        $domain = strtolower($domain);

        // Handle IDN conversion (Unicode to Punycode)
        if (preg_match('/[^\x00-\x7F]/', $domain)) {
            $domain = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $domain;
        }

        return $domain;
    }

    /**
     * Check if string is an IP address.
     *
     * @param string $domain Domain to check
     * @return bool True if IP address
     */
    private function isIpAddress(string $domain): bool
    {
        // IPv4
        if (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        // IPv6 (with brackets stripped)
        $domain = trim($domain, '[]');
        if (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        return false;
    }

    /**
     * Check if domain is localhost or local address.
     *
     * @param string $domain Domain to check
     * @return bool True if localhost
     */
    private function isLocalhost(string $domain): bool
    {
        $localDomains = ['localhost', 'local', 'localhost.localdomain', '127.0.0.1', '::1'];

        return in_array(strtolower($domain), $localDomains, true);
    }

    /**
     * Calculate age score from estimated years.
     *
     * @param int $years Estimated age in years
     * @return int Age score (0-30)
     */
    private function calculateAgeScore(int $years): int
    {
        if ($years >= 10) {
            return 30;
        }
        if ($years >= 5) {
            return 20;
        }
        if ($years >= 2) {
            return 10;
        }
        if ($years >= 1) {
            return 5;
        }

        return 0;
    }
}
