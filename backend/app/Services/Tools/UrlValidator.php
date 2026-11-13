<?php

namespace App\Services\Tools;

/**
 * URL validation for the SEO tools.
 *
 * This service existed before the SSRF-hardening work split URL checking
 * across URLValidationService (crawler) and UrlValidatorService (general).
 * The class was deleted, but the tool contract it defined - what a visitor
 * may type into a tool form - is still the contract the frontend enforces
 * in lib/utils/urlValidation.ts. This class implements that same contract
 * on the server side, so the server can reject a URL the browser accepted
 * without the two disagreeing.
 *
 * The rules, in order:
 *   - empty / non-string -> invalid
 *   - a blocked protocol (javascript:, data:, vbscript:, file:, ftp: ...)
 *     -> invalid
 *   - a protocol that is neither http: nor https: -> invalid
 *   - a URL with no host -> invalid
 *   - a URL longer than 2048 characters -> invalid
 *   - otherwise -> valid, and normalize() returns a cleaned-up form
 */
class UrlValidator
{
    /**
     * Protocols the tools will fetch.
     */
    private const ALLOWED_PROTOCOLS = ['http', 'https'];

    /**
     * Protocols that are never fetched. Blocking them prevents a tool from
     * being used to probe local resources (file://) or to run script in a
     * visitor's browser (javascript:).
     */
    private const BLOCKED_PROTOCOLS = [
        'javascript', 'data', 'vbscript', 'file', 'ftp', 'sftp', 'ssh',
        'telnet', 'gopher', 'dict',
    ];

    /**
     * Maximum URL length, matching the API request validation.
     */
    private const MAX_LENGTH = 2048;

    /**
     * Query parameters that are tracking noise rather than part of the page.
     */
    private const TRACKING_PARAMS = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'gclid', 'fbclid', 'msclkid', 'mc_cid', 'mc_eid', 'ref', 'referrer',
    ];

    /**
     * Is this URL one the tools may fetch?
     */
    public function isValid(mixed $url): bool
    {
        return $this->validate($url)['valid'];
    }

    /**
     * Validate a URL and report why it was rejected.
     *
     * @return array{valid: bool, url: ?string, error: ?string}
     */
    public function validate(mixed $url): array
    {
        $result = ['valid' => false, 'url' => null, 'error' => null];

        if (!is_string($url)) {
            $result['error'] = 'URL must be a string';
            return $result;
        }

        $trimmed = trim($url);

        if ($trimmed === '') {
            $result['error'] = 'URL is required';
            return $result;
        }

        if (strlen($trimmed) > self::MAX_LENGTH) {
            $result['error'] = 'URL is too long';
            return $result;
        }

        // A null byte can truncate a path and smuggle a second request.
        if (str_contains($trimmed, "\x00")) {
            $result['error'] = 'URL contains invalid characters';
            return $result;
        }

        // Reject a blocked protocol before parsing: parse_url treats
        // "javascript:alert(1)" as a path, not a scheme.
        $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));
        if ($scheme !== '' && in_array($scheme, self::BLOCKED_PROTOCOLS, true)) {
            $result['error'] = 'URL protocol is not allowed';
            return $result;
        }

        // No scheme at all is treated as https, matching the frontend.
        $candidate = preg_match('#^[a-z][a-z0-9+.-]*://#i', $trimmed) === 1
            ? $trimmed
            : 'https://' . ltrim($trimmed, '/');

        $parsed = parse_url($candidate);
        if ($parsed === false || empty($parsed['host'])) {
            $result['error'] = 'URL format invalid';
            return $result;
        }

        if (!in_array(strtolower((string) ($parsed['scheme'] ?? '')), self::ALLOWED_PROTOCOLS, true)) {
            $result['error'] = 'URL protocol is not allowed';
            return $result;
        }

        $result['valid'] = true;
        $result['url'] = $candidate;

        return $result;
    }

    /**
     * Clean a URL up: trim it, force https, drop tracking parameters,
     * collapse duplicate slashes, and normalise an internationalised host
     * to its punycode form.
     *
     * @param array{removeWww?: bool, removeTracking?: bool} $options
     */
    public function sanitize(string $url, array $options = []): string
    {
        $removeWww = $options['removeWww'] ?? false;
        $removeTracking = $options['removeTracking'] ?? true;

        $trimmed = trim($url);

        // Strip control characters. A CR or LF in a URL lets an attacker
        // append a header to the request the server makes when it fetches
        // the page ("https://example.com\r\nSet-Cookie: evil").
        $trimmed = preg_replace('/[\x00-\x1F\x7F]/', '', $trimmed);

        $hasScheme = preg_match('#^https?://#i', $trimmed) === 1;
        $candidate = $hasScheme ? $trimmed : 'https://' . ltrim($trimmed, '/');

        // Force https. A tool fetches the page; the scheme the visitor
        // typed should not decide whether the fetch is encrypted.
        $candidate = preg_replace('#^http://#i', 'https://', $candidate);

        $parsed = parse_url($candidate);
        if ($parsed === false || empty($parsed['host'])) {
            return $trimmed;
        }

        $scheme = strtolower((string) ($parsed['scheme'] ?? 'https'));
        $host = (string) $parsed['host'];
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $user = isset($parsed['user']) ? $parsed['user'] : '';
        $pass = isset($parsed['pass']) ? ':' . $parsed['pass'] : '';
        $auth = ($user !== '' || $pass !== '') ? $user . $pass . '@' : '';

        if ($removeWww && preg_match('/^www\./i', $host)) {
            $host = substr($host, 4);
        }

        // An internationalised host is stored punycode.
        if (preg_match('/[^\x20-\x7f]/', $host)) {
            $idn = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($idn !== false && $idn !== '') {
                $host = $idn;
            }
        }

        $path = (string) ($parsed['path'] ?? '');
        // Percent-decode the path so "%70ath" and "path" are the same
        // page. A fetch of one and a report of the other would not match.
        $decodedPath = rawurldecode($path);
        if ($decodedPath !== false && !str_contains($decodedPath, "\x00")) {
            $path = $decodedPath;
        }
        // Collapse duplicate slashes, but keep a single leading one.
        $path = '/' . trim(preg_replace('#/{2,}#', '/', $path), '/');
        if ($path === '' || $path === '/') {
            $path = '';
        }

        $query = '';
        if (!empty($parsed['query'])) {
            $pairs = [];
            parse_str((string) $parsed['query'], $params);
            foreach ($params as $key => $value) {
                if ($removeTracking && in_array(strtolower((string) $key), self::TRACKING_PARAMS, true)) {
                    continue;
                }
                $pairs[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
            }
            if ($pairs !== []) {
                $query = '?' . implode('&', $pairs);
            }
        }

        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $scheme . '://' . $auth . $host . $port . $path . $query . $fragment;
    }

    /**
     * Rewrite an http URL to https.
     *
     * Returns the URL unchanged if it is not an http URL at all.
     */
    public function ensureHttps(string $url): string
    {
        return preg_replace('#^http://#i', 'https://', trim($url));
    }
}
