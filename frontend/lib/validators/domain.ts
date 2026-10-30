const LOCAL_BLOCKLIST = [
  "localhost",
  "localhost.localdomain",
  "127.0.0.1",
  "0.0.0.0",
  "::1",
];

const PRIVATE_IP_RANGES = [
  /^10\./,
  /^192\.168\./,
  /^172\.(1[6-9]|2\d|3[0-1])\./,
  /^169\.254\./,
];

const DOMAIN_REGEX = /^(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,63}$/i;

export function sanitizeDomain(input: string): string {
  return input
    .trim()
    .toLowerCase()
    .replace(/^https?:\/\//, "")
    .replace(/^www\./, "")
    .replace(/\/.*$/, "");
}

export function validateDomain(input: string): string | true {
  const value = sanitizeDomain(input);
  if (!value) {
    return "Domain is required";
  }

  if (LOCAL_BLOCKLIST.includes(value)) {
    return "Localhost domains are not allowed";
  }

  if (PRIVATE_IP_RANGES.some((pattern) => pattern.test(value))) {
    return "Private IPs are not allowed";
  }

  if (!DOMAIN_REGEX.test(value)) {
    return "Please enter a valid domain";
  }

  return true;
}
