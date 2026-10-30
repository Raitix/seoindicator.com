/**
 * build-badge.mjs - Build script for badge.js UMD bundle
 * Task: TASK-33-16 - Implement badge.js Embed Script
 * 
 * Compiles TypeScript to UMD JavaScript bundle
 * Output: public/badge.js and public/badge.min.js
 */

import * as esbuild from 'esbuild';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';
import { gzipSync } from 'zlib';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT_DIR = path.join(__dirname, '..');
const SRC_FILE = path.join(ROOT_DIR, 'lib/scripts/badge.ts');
const OUT_FILE = path.join(ROOT_DIR, 'public/badge.js');
const OUT_MIN_FILE = path.join(ROOT_DIR, 'public/badge.min.js');
const OUT_MAP_FILE = path.join(ROOT_DIR, 'public/badge.min.js.map');

async function build() {
  console.log('Building badge.js...\n');
  
  // Development build (unminified)
  console.log('Building unminified version...');
  await esbuild.build({
    entryPoints: [SRC_FILE],
    bundle: true,
    outfile: OUT_FILE,
    format: 'iife',
    platform: 'browser',
    target: ['es2017'],
    globalName: 'SEOIndicatorBadge',
    banner: {
      js: `/**
 * badge.js - SEO Score Badge Embed Script
 * Version: ${process.env.npm_package_version || '1.0.0'}
 * (c) SEOIndicator
 * Released under the MIT License
 */`
    },
    footer: {
      js: `// Auto-init on DOM ready
if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      if (window.SEOIndicatorBadge && window.SEOIndicatorBadge.init) {
        window.SEOIndicatorBadge.init();
      }
    });
  }
}`
    }
  });
  console.log(`✓ Created: ${OUT_FILE}`);
  
  // Production build (minified with source map)
  console.log('\nBuilding minified version...');
  await esbuild.build({
    entryPoints: [SRC_FILE],
    bundle: true,
    outfile: OUT_MIN_FILE,
    minify: true,
    format: 'iife',
    platform: 'browser',
    target: ['es2017'],
    globalName: 'SEOIndicatorBadge',
    sourcemap: true,
    banner: {
      js: `/* badge.js v${process.env.npm_package_version || '1.0.0'} - (c) SEOIndicator */`
    }
  });
  console.log(`✓ Created: ${OUT_MIN_FILE}`);
  console.log(`✓ Created: ${OUT_MAP_FILE}`);
  
  // Report sizes
  console.log('\nBuild sizes:');
  
  const unminified = fs.statSync(OUT_FILE).size;
  console.log(`  Unminified: ${(unminified / 1024).toFixed(2)} KB`);
  
  const minified = fs.statSync(OUT_MIN_FILE).size;
  console.log(`  Minified:   ${(minified / 1024).toFixed(2)} KB`);
  
  const gzipped = gzipSync(fs.readFileSync(OUT_MIN_FILE)).length;
  console.log(`  Gzipped:    ${(gzipped / 1024).toFixed(2)} KB`);
  
  // Check size constraint
  const MAX_GZIP_SIZE = 10 * 1024; // 10KB
  if (gzipped > MAX_GZIP_SIZE) {
    console.warn(`\n⚠️  Warning: Gzipped size (${(gzipped / 1024).toFixed(2)} KB) exceeds 10KB target`);
  } else {
    console.log(`\n✅ Gzipped size is under 10KB target`);
  }
  
  console.log('\nBuild complete!');
}

build().catch((err) => {
  console.error('Build failed:', err);
  process.exit(1);
});
