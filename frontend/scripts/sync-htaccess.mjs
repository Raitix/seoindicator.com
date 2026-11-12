/**
 * Sync the canonical .htaccess into frontend/public/ before the export.
 *
 * Next.js copies everything in `public/` into the exported site. The deploy
 * then uploads that export over the site root - which means
 * `frontend/public/.htaccess` lands on top of the git-managed root
 * `.htaccess`.
 *
 * Two copies of the same file drifted apart: the root one had the short
 * HTML cache TTL and the public one still had the old one-hour TTL, so every
 * deploy silently overwrote the fix with the stale version. The CDN then
 * kept serving the previous build for an hour after each deploy.
 *
 * This script makes the root `.htaccess` the single source of truth and
 * copies it into place, so there is exactly one version to maintain.
 *
 * Run before `next build`. It is a no-op when the two already agree.
 */

import { readFileSync, writeFileSync, existsSync, copyFileSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const here = dirname(fileURLToPath(import.meta.url));
const frontendDir = join(here, "..");
const repoRoot = join(frontendDir, "..");

const source = join(repoRoot, ".htaccess");
const target = join(frontendDir, "public", ".htaccess");

if (!existsSync(source)) {
  console.error(`[sync-htaccess] canonical .htaccess not found at ${source}`);
  process.exit(1);
}

const sourceContent = readFileSync(source, "utf8");

if (existsSync(target)) {
  const targetContent = readFileSync(target, "utf8");
  if (targetContent === sourceContent) {
    console.log("[sync-htaccess] already in sync");
    process.exit(0);
  }
}

// Copy the file, preserving the exact bytes. writeFileSync with the source
// buffer avoids any newline translation on Windows.
copyFileSync(source, target);
console.log(
  `[sync-htaccess] copied ${source} -> ${target} ` +
  `(${sourceContent.length} bytes)`
);
