/**
 * Parse every test file with the project's own esbuild.
 *
 * Vitest reports a syntax error as a failed test file, which hides it in
 * the failure count. This finds them directly.
 *
 * Usage: node parse_check.mjs <frontend-dir>
 */
import fs from 'fs';
import path from 'path';
import { createRequire } from 'module';

const require = createRequire(import.meta.url);
const esbuild = require('esbuild');

const FE = process.argv[2];
const pats = ['.test.ts', '.test.tsx', '.spec.ts', '.spec.tsx'];

function walk(dir, out) {
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    if (e.name === 'node_modules' || e.name === '.next' || e.name === 'out') continue;
    const p = path.join(dir, e.name);
    if (e.isDirectory()) walk(p, out);
    else if (pats.some((x) => e.name.endsWith(x))) out.push(p);
  }
  return out;
}

const files = walk(FE, []);
const bad = [];

for (const f of files) {
  const loader = f.endsWith('.tsx') ? 'tsx' : 'ts';
  try {
    await esbuild.transform(fs.readFileSync(f, 'utf8'), { loader, jsx: 'automatic' });
  } catch (e) {
    const err = (e.errors && e.errors[0]) || {};
    bad.push([
      path.relative(FE, f).replace(/\\/g, '/'),
      err.text || String(e),
      err.location ? (err.location.lineText || '').trim() : '',
    ]);
  }
}

console.log(JSON.stringify({ total: files.length, bad }));
