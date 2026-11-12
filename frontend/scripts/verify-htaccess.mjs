/**
 * Fails the build if the two .htaccess copies have drifted apart.
 *
 * The canonical file is the one at the repository root. Next.js copies
 * `frontend/public/` into the export, and the deploy uploads that export over
 * the site root - so `frontend/public/.htaccess` overwrites the git-managed
 * root `.htaccess` on every deploy.
 *
 * The two copies drifted once: the root had a short HTML cache TTL and the
 * public copy still had the old one-hour TTL, so each deploy silently
 * reverted the fix. This check turns that silent failure into a build error.
 *
 * Run as part of `npm run build`, after the sync step.
 */

import { readFileSync, existsSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const here = dirname(fileURLToPath(import.meta.url));
const frontendDir = join(here, "..");
const repoRoot = join(frontendDir, "..");

const source = join(repoRoot, ".htaccess");
const target = join(frontendDir, "public", ".htaccess");

if (!existsSync(source)) {
  console.error(`[verify-htaccess] canonical .htaccess not found at ${source}`);
  process.exit(1);
}

if (!existsSync(target)) {
  console.error(
    `[verify-htaccess] ${target} is missing. It must exist, because Next.js ` +
    "copies public/ into the export and the deploy uploads that over the site root."
  );
  process.exit(1);
}

const sourceContent = readFileSync(source, "utf8");
const targetContent = readFileSync(target, "utf8");

if (sourceContent !== targetContent) {
  console.error(
    "[verify-htaccess] the two .htaccess copies differ.\n" +
      `  canonical: ${source}\n` +
      `  deployed : ${target}\n` +
      "  Run `npm run build:htaccess` to sync them."
  );
  process.exit(1);
}

console.log("[verify-htaccess] the two copies are identical");
