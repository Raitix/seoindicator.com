/**
 * generate-og-images.mjs - Pre-generate static OG images for comparisons
 * Task: TASK-32-17 - OG Image Generation for Comparisons
 * 
 * This script generates static PNG images for all comparison pages.
 * Required because ImageResponse from next/og is incompatible with static export.
 * 
 * Output: public/og/compare/{slug}.png
 */

import satori from 'satori';
import { Resvg } from '@resvg/resvg-js';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT_DIR = path.join(__dirname, '..');
const OUT_DIR = path.join(ROOT_DIR, 'public/og/compare');

// OG image dimensions
const WIDTH = 1200;
const HEIGHT = 630;

// Sanitize input - strip HTML tags and truncate
function sanitizeInput(input, maxLength = 50) {
  if (!input) return '';
  
  let decoded = input;
  try {
    decoded = decodeURIComponent(input);
  } catch {
    // If decoding fails, use as-is
  }
  
  const stripped = decoded
    .replace(/<script[^>]*>.*?<\/script>/gi, '')
    .replace(/<[^>]*>/g, '')
    .replace(/javascript:/gi, '')
    .replace(/on\w+=/gi, '');
  
  return stripped.trim().slice(0, maxLength);
}

// Get competitor names from slug
function parseSlug(slug) {
  const parts = slug.split('-vs-');
  if (parts.length !== 2) return null;
  return { competitorA: parts[0], competitorB: parts[1] };
}

// Format competitor name for display
function formatName(id) {
  const nameMap = {
    seoindicator: 'SEOIndicator',
    ahrefs: 'Ahrefs',
    semrush: 'SEMrush',
    moz: 'Moz',
    ubersuggest: 'Ubersuggest',
    gsc: 'Google Search Console',
    ga: 'Google Analytics',
    answerthepublic: 'AnswerThePublic',
  };
  return nameMap[id] || id.charAt(0).toUpperCase() + id.slice(1);
}

// Generate SVG using satori
async function generateSVG(competitorA, competitorB, title) {
  const nameA = formatName(competitorA);
  const nameB = formatName(competitorB);
  const displayTitle = title || 'Compare features, pricing, and find the right SEO tool';
  
  // Load a font (use system fonts for simplicity)
  const fontPath = path.join(ROOT_DIR, 'node_modules/@resvg/resvg-js/fonts/LiberationSans-Regular.ttf');
  let fontData;
  try {
    fontData = fs.readFileSync(fontPath);
  } catch {
    // Fallback to system font lookup
    const systemFonts = [
      'C:/Windows/Fonts/arial.ttf',
      'C:/Windows/Fonts/segoeui.ttf',
      '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
      '/System/Library/Fonts/Helvetica.ttc',
    ];
    for (const fp of systemFonts) {
      try {
        fontData = fs.readFileSync(fp);
        break;
      } catch {
        continue;
      }
    }
  }
  
  if (!fontData) {
    throw new Error('No suitable font found for OG image generation');
  }

  const svg = await satori(
    {
      type: 'div',
      props: {
        style: {
          display: 'flex',
          height: '100%',
          width: '100%',
          padding: '60px',
          background: 'linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%)',
          color: 'white',
          fontFamily: 'sans-serif',
        },
        children: {
          type: 'div',
          props: {
            style: {
              display: 'flex',
              flexDirection: 'column',
              justifyContent: 'center',
            },
            children: [
              {
                type: 'h1',
                props: {
                  style: {
                    fontSize: '48px',
                    fontWeight: 'bold',
                    margin: 0,
                    lineHeight: 1.2,
                  },
                  children: `${nameA} vs ${nameB}`,
                },
              },
              {
                type: 'p',
                props: {
                  style: {
                    fontSize: '24px',
                    marginTop: '20px',
                    opacity: 0.9,
                    margin: '20px 0 0 0',
                  },
                  children: displayTitle,
                },
              },
              {
                type: 'div',
                props: {
                  style: {
                    display: 'flex',
                    marginTop: '40px',
                  },
                  children: {
                    type: 'div',
                    props: {
                      style: {
                        padding: '10px 20px',
                        background: '#3b82f6',
                        borderRadius: '8px',
                        fontSize: '18px',
                        fontWeight: 'bold',
                      },
                      children: 'SEOIndicator.com',
                    },
                  },
                },
              },
            ],
          },
        },
      },
    },
    {
      width: WIDTH,
      height: HEIGHT,
      fonts: [
        {
          name: 'sans-serif',
          data: fontData,
          weight: 400,
          style: 'normal',
        },
      ],
    }
  );

  return svg;
}

// Convert SVG to PNG using resvg-js
function svgToPNG(svg) {
  const opts = {
    fitTo: {
      mode: 'width',
      value: WIDTH,
    },
  };
  
  const resvg = new Resvg(svg, opts);
  const pngData = resvg.render();
  return pngData.asPng();
}

// Main function
async function generateOGImages() {
  console.log('Generating OG images for comparison pages...\n');
  
  // Ensure output directory exists
  if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
  }
  
  // Define comparison slugs (must match COMPARISON_SLUGS in registry.ts)
  const COMPARISON_SLUGS = [
    'seoindicator-vs-ahrefs',
    'seoindicator-vs-moz',
    'seoindicator-vs-semrush',
    'seoindicator-vs-ubersuggest',
    'seoindicator-vs-gsc',
    'seoindicator-vs-ga',
    'seoindicator-vs-answerthepublic',
    'ahrefs-vs-moz',
    'ahrefs-vs-semrush',
    'ahrefs-vs-ubersuggest',
    'moz-vs-semrush',
    'moz-vs-ubersuggest',
    'semrush-vs-ubersuggest',
  ];
  
  let successCount = 0;
  let errorCount = 0;
  
  for (const slug of COMPARISON_SLUGS) {
    try {
      const parsed = parseSlug(slug);
      if (!parsed) {
        console.warn(`  ⚠️  Invalid slug format: ${slug}`);
        errorCount++;
        continue;
      }
      
      const { competitorA, competitorB } = parsed;
      const nameA = formatName(competitorA);
      const nameB = formatName(competitorB);
      
      console.log(`  Generating: ${slug}`);
      console.log(`    ${nameA} vs ${nameB}`);
      
      // Generate SVG
      const svg = await generateSVG(competitorA, competitorB);
      
      // Convert to PNG
      const pngBuffer = svgToPNG(svg);
      
      // Save to file
      const outPath = path.join(OUT_DIR, `${slug}.png`);
      fs.writeFileSync(outPath, pngBuffer);
      
      console.log(`    ✓ Saved: ${outPath}`);
      successCount++;
    } catch (err) {
      console.error(`    ✗ Error generating ${slug}:`, err.message);
      errorCount++;
    }
  }
  
  console.log(`\n========================================`);
  console.log(`OG Image Generation Complete`);
  console.log(`  ✓ Success: ${successCount}`);
  console.log(`  ✗ Errors:  ${errorCount}`);
  console.log(`  Output:   ${OUT_DIR}`);
  console.log(`========================================\n`);
  
  // Return exit code
  return errorCount === 0 ? 0 : 1;
}

// Run
generateOGImages()
  .then((code) => process.exit(code))
  .catch((err) => {
    console.error('Fatal error:', err);
    process.exit(1);
  });
