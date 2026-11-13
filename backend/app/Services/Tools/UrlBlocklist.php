<?php

namespace App\Services\Tools;

/**
 * Blocklist for URLs the SEO tools must not fetch.
 *
 * The tools ask the server to fetch a page the visitor names. Without a
 * blocklist that turns the server into a proxy for the visitor's own
 * network: a request to http://127.0.0.1 reaches the server's own
 * services, and http://169.254.169.254 reaches the cloud metadata
 * endpoint, which hands out credentials.
 *
 * The checks here are deliberately redundant, because each one alone can
 * be bypassed:
 *   - a host may be written as a name, an IPv4 literal, or an IPv6
 *     literal, and an IPv4 literal may be written in decimal, hex or
 *     octal (2130706433, 0x7f000001 and 017700000001 are all 127.0.0.1)
 *   - a name may resolve to a private address, so the resolved address is
 *     checked too
 *   - an IPv4 address may be mapped into IPv6 (::ffff:127.0.0.1)
 *
 * This class was deleted when the SSRF hardening work moved the general
 * case into URLValidationService, but the tool endpoint's contract - which
 * hosts it refuses - is this one.
 */
class UrlBlocklist
{
    /**
     * Host names that must never be fetched.
     */
    private const BLOCKED_HOSTNAMES = [
        'localhost',
        'localhost.localdomain',
        'ip6-localhost',
        'ip6-loopback',
        'broadcasthost',
    ];

    /**
     * Host name fragments that indicate an internal machine.
     */
    private const BLOCKED_HOSTNAME_PATTERNS = [
        // A label that names an internal machine, wherever it sits in the
        // host: "internal.example.com", "intranet.example.com",
        // "foo.internal" all name something inside a network.
        '/(^|\.)internal($|\.)/i',
        '/(^|\.)intranet($|\.)/i',
        '/(^|\.)local($|\.)/i',
        '/(^|\.)lan($|\.)/i',
        '/(^|\.)home($|\.)/i',
    ];

    /**
     * URL schemes the tools never fetch.
     */
    private const BLOCKED_PROTOCOLS = [
        'file', 'ftp', 'sftp', 'ssh', 'telnet', 'gopher', 'dict',
        'ldap', 'ldaps', 'tftp', 'sftp+ssh', 'smb', 'nfs',
    ];

    /**
     * IPv4 ranges that are not routable on the public internet.
     */
    private const PRIVATE_IPV4_RANGES = [
        ['0.0.0.0', '0.255.255.255'],       // "this" network
        ['10.0.0.0', '10.255.255.255'],     // private
        ['100.64.0.0', '100.127.255.255'],  // carrier-grade NAT
        ['127.0.0.0', '127.255.255.255'],   // loopback
        ['169.254.0.0', '169.254.255.255'], // link-local, incl. cloud metadata
        ['172.16.0.0', '172.31.255.255'],   // private
        ['192.0.0.0', '192.0.0.255'],       // IETF protocol assignments
        ['192.168.0.0', '192.168.255.255'], // private
        ['198.18.0.0', '198.19.255.255'],   // benchmarking
        ['224.0.0.0', '239.255.255.255'],   // multicast
        ['240.0.0.0', '255.255.255.255'],   // reserved, incl. broadcast
    ];

    /**
     * Extra patterns added at runtime.
     *
     * @var string[]
     */
    private array $patterns = [];

    /**
     * Whether a resolved hostname is checked against the blocklist.
     *
     * Resolving DNS on every request costs a round trip, but it is the
     * only defence against DNS rebinding, where a name that resolved to a
     * public address during validation resolves to a private one by the
     * time the server fetches it.
     */
    private bool $resolveDns = true;

    public function __construct(bool $resolveDns = true)
    {
        $this->resolveDns = $resolveDns;
    }

    /**
     * Should the server resolve DNS before deciding a host is safe?
     */
    public function shouldResolveDns(): bool
    {
        return $this->resolveDns;
    }

    /**
     * Add a regular expression that blocks matching hosts.
     */
    public function addPattern(string $pattern): void
    {
        if (!in_array($pattern, $this->patterns, true)) {
            $this->patterns[] = $pattern;
        }
    }

    /**
     * Remove a previously added pattern.
     */
    public function removePattern(string $pattern): void
    {
        $this->patterns = array_values(
            array_filter($this->patterns, fn ($p) => $p !== $pattern)
        );
    }

    /**
     * Every active pattern: the built-in hostname patterns plus any that
     * were added at runtime.
     *
     * @return string[]
     */
    public function getPatterns(): array
    {
        return array_merge(self::BLOCKED_HOSTNAME_PATTERNS, $this->patterns);
    }

    /**
     * Is this URL one the tools must not fetch?
     */
    public function isBlocked(mixed $url): bool
    {
        return $this->getBlockReason($url) !== null;
    }

    /**
     * Why is this URL blocked, or null when it is allowed.
     *
     * The reason names the category - "protocol", "hostname", "private
     * ip" - so a caller can log something useful without re-deriving it.
     */
    public function getBlockReason(mixed $url): ?string
    {
        if (!is_string($url) || trim($url) === '') {
            return 'invalid url';
        }

        $url = trim($url);
        $parts = parse_url($url);

        if ($parts === false) {
            return 'invalid url';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme !== '' && in_array($scheme, self::BLOCKED_PROTOCOLS, true)) {
            return 'blocked protocol: ' . $scheme;
        }

        $host = $this->extractHost($parts);
        if ($host === null || $host === '') {
            return 'invalid url';
        }

        // The host may be written in a non-dotted form that still names a
        // private address.
        $normalised = $this->normaliseIpLiteral($host);
        if ($normalised !== null) {
            if ($this->isPrivateIp($normalised)) {
                return 'private ip address';
            }
            return null;
        }

        $lower = strtolower($host);

        if (in_array($lower, self::BLOCKED_HOSTNAMES, true)) {
            return 'localhost is not allowed';
        }

        foreach (self::BLOCKED_HOSTNAME_PATTERNS as $pattern) {
            if (preg_match($pattern, $lower)) {
                return 'internal hostname is not allowed';
            }
        }

        foreach ($this->patterns as $pattern) {
            if ($this->matchesPattern($pattern, $lower)) {
                return 'host matches a blocked pattern';
            }
        }

        // A name that resolves to a private address is just as dangerous
        // as a literal private address.
        if ($this->resolveDns) {
            $resolved = $this->resolveHost($host);
            foreach ($resolved as $address) {
                $normalised = $this->normaliseIpLiteral($address);
                if ($normalised !== null && $this->isPrivateIp($normalised)) {
                    return 'private ip address';
                }
            }
        }

        return null;
    }

    /**
     * Match a host against a pattern.
     *
     * A pattern may be written with PCRE delimiters ('/evil\\.com$/i') or
     * without them ('evil\\.com$'). preg_match rejects an undelimited
     * pattern outright, so one is added when it is missing.
     */
    private function matchesPattern(string $pattern, string $host): bool
    {
        $delimited = strlen($pattern) > 1
            && !in_array($pattern[0], ['\\', '#', '~', '/', '%'], true)
            && $pattern[0] !== preg_quote($pattern[0], '/')
            && $this->hasClosingDelimiter($pattern);

        if (!$delimited) {
            $pattern = '/' . str_replace('/', '\\/', $pattern) . '/';
        }

        return @preg_match($pattern, $host) === 1;
    }

    /**
     * Does the pattern start with a delimiter that is closed at the end?
     */
    private function hasClosingDelimiter(string $pattern): bool
    {
        $first = $pattern[0];
        if (!in_array($first, ['#', '~', '/', '%', '!', '@'], true)) {
            return false;
        }
        // The closing delimiter is the last character, or the one before
        // any modifier flags.
        $last = substr($pattern, -1);
        if ($last === $first) {
            return true;
        }
        return preg_match('/^' . preg_quote($first, '/') . '.*' . preg_quote($first, '/') . '[imsxuADSUXJn]*$/', $pattern) === 1;
    }

    /**
     * Pull the host out of the parsed URL, dropping any userinfo.
     *
     * @param array<string, mixed> $parts
     */
    private function extractHost(array $parts): ?string
    {
        $host = $parts['host'] ?? null;
        if (!is_string($host) || $host === '') {
            return null;
        }

        // parse_url puts the port in its own key, but a URL written with
        // credentials can leave "user:pass@" in the host.
        $at = strrpos($host, '@');
        if ($at !== false) {
            $host = substr($host, $at + 1);
        }

        // An IPv6 literal is written in brackets.
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        return $host;
    }

    /**
     * Turn a host that is really an IP literal into its canonical dotted
     * form, or return null when it is a name.
     *
     * Handles the decimal, hexadecimal and octal spellings of an IPv4
     * address, and the IPv4-mapped IPv6 form.
     */
    private function normaliseIpLiteral(string $host): ?string
    {
        $host = trim($host);

        // An IPv4-mapped IPv6 address (::ffff:127.0.0.1) names an IPv4
        // address.
        if (stripos($host, '::ffff:') === 0) {
            $mapped = substr($host, 7);
            if (filter_var($mapped, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $mapped;
            }
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $host;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // An IPv6 address is converted to its packed form and compared
            // against the private ranges below.
            return $host;
        }

        // A name that is only digits is a decimal IPv4 address - UNLESS it
        // starts with a zero, in which case it is an octal address.
        // 017700000001 is 127.0.0.1 in octal.
        if ($host !== '' && ctype_digit($host)) {
            if (strlen($host) > 1 && $host[0] === '0') {
                $long = octdec(substr($host, 1));
                if ($long <= 4294967295) {
                    return long2ip((int) $long);
                }
                return null;
            }
            $value = (float) $host;
            if ($value <= 4294967295) {
                $long = (int) $value;
                return long2ip($long);
            }
            return null;
        }

        // 0x7f000001 and 0X7F000001 are hexadecimal IPv4 addresses.
        if (preg_match('/^0x[0-9a-f]+$/i', $host)) {
            $long = hexdec(substr($host, 2));
            if ($long <= 4294967295) {
                return long2ip((int) $long);
            }
            return null;
        }

        return null;
    }

    /**
     * Is this address one that must not be reachable from the internet?
     */
    private function isPrivateIp(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->isPrivateIpv6($address);
        }

        $long = ip2long($address);
        if ($long === false) {
            return true;
        }

        foreach (self::PRIVATE_IPV4_RANGES as [$start, $end]) {
            $startLong = ip2long($start);
            $endLong = ip2long($end);
            if ($startLong === false || $endLong === false) {
                continue;
            }
            if ($long >= $startLong && $long <= $endLong) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is this IPv6 address in a range that must not be reachable?
     */
    private function isPrivateIpv6(string $address): bool
    {
        $packed = @inet_pton($address);
        if ($packed === false) {
            return true;
        }

        // ::1 loopback
        if ($packed === str_repeat("\x00", 15) . "\x01") {
            return true;
        }

        // :: unspecified
        if ($packed === str_repeat("\x00", 16)) {
            return true;
        }

        $first = ord($packed[0]);
        $second = ord($packed[1]);

        // fc00::/7 unique local
        if (($first & 0xFE) === 0xFC) {
            return true;
        }

        // fe80::/10 link local
        if ($first === 0xFE && ($second & 0xC0) === 0x80) {
            return true;
        }

        // ff00::/8 multicast
        if ($first === 0xFF) {
            return true;
        }

        return false;
    }

    /**
     * Resolve a hostname to its addresses.
     *
     * @return string[]
     */
    private function resolveHost(string $host): array
    {
        $addresses = @gethostbynamel($host);
        if (!is_array($addresses)) {
            return [];
        }
        return $addresses;
    }
}
