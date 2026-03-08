/**
 * generate-audit-og-images.mjs - Pre-generate static OG images for audits
 * Task: TASK-33-27 - OG Image Generation for Audits
 * 
 * This script generates static PNG images for all public audits.
 * Required because ImageResponse from next/og is incompatible with static export.
 * 
 * Output: public/og/audit/{hash}.png
 */

import satori from 'satori';
import { Resvg } from '@resvg/resvg-js';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT_DIR = path.join(__dirname, '..');
const OUT_DIR = path.join(ROOT_DIR, 'public/og/audit');

// OG image dimensions
const WIDTH = 1200;
const HEIGHT = 630;

// Score color mapping
const SCORE_COLORS = {
  excellent: '#22c55e',  // 90-100
  good: '#3b82f6',       // 70-89
  average: '#eab308',    // 50-69
  poor: '#f97316',       // 30-49
  critical: '#ef4444',   // 0-29
};

// Score status labels
const SCORE_STATUS = {
  excellent: 'Excellent',
  good: 'Good',
  average: 'Needs Work',
  poor: 'Poor',
  critical: 'Critical Issues',
};

/**
 * Sanitize input - strip HTML tags and truncate
 */
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

/**
 * Get score color based on value
 */
function getScoreColor(score) {
  if (score >= 90) return SCORE_COLORS.excellent;
  if (score >= 70) return SCORE_COLORS.good;
  if (score >= 50) return SCORE_COLORS.average;
  if (score >= 30) return SCORE_COLORS.poor;
  return SCORE_COLORS.critical;
}

/**
 * Get score status based on value
 */
function getScoreStatus(score) {
  if (score >= 90) return SCORE_STATUS.excellent;
  if (score >= 70) return SCORE_STATUS.good;
  if (score >= 50) return SCORE_STATUS.average;
  if (score >= 30) return SCORE_STATUS.poor;
  return SCORE_STATUS.critical;
}

/**
 * Clamp score to valid range
 */
function clampScore(score) {
  if (typeof score !== 'number' || isNaN(score)) return 0;
  return Math.max(0, Math.min(100, Math.round(score)));
}

/**
 * Truncate URL for display
 */
function truncateUrl(url, maxLength = 50) {
  if (!url) return '';
  
  // Remove protocol
  let display = url.replace(/^https?:\/\//, '');
  
  // Remove trailing slash
  display = display.replace(/\/$/, '');
  
  // Truncate if too long
  if (display.length > maxLength) {
    return display.substring(0, maxLength - 3) + '...';
  }
  
  return display;
}

/**
 * Generate SVG using satori
 */
async function generateSVG(audit) {
  const { hash, site_url, overall_score, category_scores } = audit;
  
  const score = clampScore(overall_score);
  const scoreColor = getScoreColor(score);
  const scoreStatus = getScoreStatus(score);
  const displayUrl = truncateUrl(sanitizeInput(site_url, 60));
  
  // Category scores with defaults
  const categories = {
    technical: clampScore(category_scores?.technical || 0),
    on_page: clampScore(category_scores?.on_page || category_scores?.onpage || 0),
    content: clampScore(category_scores?.content || 0),
    mobile: clampScore(category_scores?.mobile || 0),
  };
  
  // Load a font
  const fontPath = path.join(ROOT_DIR, 'node_modules/@resvg/resvg-js/fonts/LiberationSans-Regular.ttf');
  const fontBoldPath = path.join(ROOT_DIR, 'node_modules/@resvg/resvg-js/fonts/LiberationSans-Bold.ttf');
  
  let fontData, fontBoldData;
  
  try {
    fontData = fs.readFileSync(fontPath);
    fontBoldData = fs.readFileSync(fontBoldPath);
  } catch {
    // Fallback to system fonts
    const systemFonts = [
      { regular: 'C:/Windows/Fonts/arial.ttf', bold: 'C:/Windows/Fonts/arialbd.ttf' },
      { regular: 'C:/Windows/Fonts/segoeui.ttf', bold: 'C:/Windows/Fonts/seguisb.ttf' },
      { regular: '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', bold: '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf' },
      { regular: '/System/Library/Fonts/Helvetica.ttc', bold: '/System/Library/Fonts/Helvetica.ttc' },
    ];
    
    for (const fonts of systemFonts) {
      try {
        fontData = fs.readFileSync(fonts.regular);
        fontBoldData = fs.readFileSync(fonts.bold);
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
          padding: '40px 60px',
          background: '#0f172a',
          color: 'white',
          fontFamily: 'Inter, sans-serif',
        },
        children: {
          type: 'div',
          props: {
            style: {
              display: 'flex',
              flexDirection: 'column',
              justifyContent: 'space-between',
              height: '100%',
            },
            children: [
              // Header with logo
              {
                type: 'div',
                props: {
                  style: {
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                  },
                  children: [
                    {
                      type: 'div',
                      props: {
                        style: {
                          fontSize: '24px',
                          fontWeight: 'bold',
                          color: '#10b981',
                        },
                        children: 'SEOIndicator',
                      },
                    },
                    {
                      type: 'div',
                      props: {
                        style: {
                          fontSize: '16px',
                          color: '#94a3b8',
                        },
                        children: displayUrl,
                      },
                    },
                  ],
                },
              },
              
              // Main content - Score
              {
                type: 'div',
                props: {
                  style: {
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                  },
                  children: [
                    // Score circle
                    {
                      type: 'div',
                      props: {
                        style: {
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          width: '180px',
                          height: '180px',
                          borderRadius: '50%',
                          background: `linear-gradient(135deg, ${scoreColor} 0%, ${scoreColor}dd 100%)`,
                          boxShadow: `0 0 60px ${scoreColor}40`,
                        },
                        children: {
                          type: 'div',
                          props: {
                            style: {
                              display: 'flex',
                              flexDirection: 'column',
                              alignItems: 'center',
                            },
                            children: [
                              {
                                type: 'div',
                                props: {
                                  style: {
                                    fontSize: '72px',
                                    fontWeight: 'bold',
                                    color: 'white',
                                  },
                                  children: String(score),
                                },
                              },
                              {
                                type: 'div',
                                props: {
                                  style: {
                                    fontSize: '16px',
                                    color: 'white',
                                    opacity: 0.9,
                                  },
                                  children: 'SEO Score',
                                },
                              },
                            ],
                          },
                        },
                      },
                    },
                    // Status
                    {
                      type: 'div',
                      props: {
                        style: {
                          marginTop: '16px',
                          fontSize: '24px',
                          fontWeight: 'bold',
                          color: scoreColor,
                        },
                        children: scoreStatus,
                      },
                    },
                  ],
                },
              },
              
              // Category scores
              {
                type: 'div',
                props: {
                  style: {
                    display: 'flex',
                    justifyContent: 'space-between',
                    marginBottom: '20px',
                  },
                  children: [
                    // Technical
                    {
                      type: 'div',
                      props: {
                        style: {
                          display: 'flex',
                          flexDirection: 'column',
                          alignItems: 'center',
                          width: '25%',
                        },
                        children: [
                          {
                            type: 'div',
                            props: {
                              style: {
                                fontSize: '28px',
                                fontWeight: 'bold',
                                color: '#10b981',
                              },
                              children: String(categories.technical),
                            },
                          },
                          {
                            type: 'div',
                            props: {
                              style: {
                                fontSize: '14px',
                                color: '#94a3b8',
                                marginTop: '4px',
                              },
                              children: 'Technical',
                            },
                          },
                        ],
                      },
                    },
                    // On-Page
                    {
                      type: 'div',
                      props: {
                        style: {
                          display: 'flex',
                          flexDirection: 'column',
                          alignItems: 'center',
                          width: '25%',
                        },
                        children: [
                          {
                            type: 'div',
                            props: {
                              style: {
                                fontSize: '28px',
                                fontWeight: 'bold',
                                color: '#3b82f6',
                              },
                              children: String(categories.on_page),
                            },
                          },
                          {
                            type: 'div',
                            props: {
                              style: {
                                fontSize: '14px',
                                color: '#94a3b8',
                                marginTop: '4px',
                              },
                              children: 'On-Page',
                            },
                          },
                        ],
                      },
                    },
                    // Content
                    {
                      type: 'div',
                      props: {
                        style: {
                          display: 'flex',
                          flexDirection: 'column',
                          alignItems: 'center',
                          width: '25%',
                        },
                        children: [
                          {
                            type: 'div',
                            props: {
                              style: {
                                fontSize: '28px',
                                fontWeight: 'bold',
                                color: '#f59e0b',
                              },
                              children: String(categories.content),
                            },
                          },
                          {
                            type: 'div',
                            props: {
                              style: {
                                fontSize: '14px',
                                color: '#94a3b8',
                                marginTop: '4px',
                              },
                              children: 'Content',
                            },
                          },
                        ],
                      },
                    },
                    // Mobile
                    {
                      type: 'div',
                      props: {
                        style: {
                          display: 'flex',
                          flexDirection: 'column',
                          alignItems: 'center',
                          width: '25%',
                        },
                        children: [
                          {
                            type: 'div',
                            props: {
                              style: {
                                fontSize: '28px',
                                fontWeight: 'bold',
                                color: '#8b5cf6',
                              },
                              children: String(categories.mobile),
                            },
                          },
                          {
                            type: 'div',
                            props: {
                              style: {
                                fontSize: '14px',
                                color: '#94a3b8',
                                marginTop: '4px',
                              },
                              children: 'Mobile',
                            },
                          },
                        ],
                      },
                    },
                  ],
                },
              },
              
              // Footer with CTA
              {
                type: 'div',
                props: {
                  style: {
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    borderTop: '1px solid #1e293b',
                    paddingTop: '20px',
                  },
                  children: [
                    {
                      type: 'div',
                      props: {
                        style: {
                          fontSize: '14px',
                          color: '#64748b',
                        },
                        children: 'Get your free SEO audit →',
                      },
                    },
                    {
                      type: 'div',
                      props: {
                        style: {
                          fontSize: '12px',
                          color: '#475569',
                        },
                        children: 'seoindicator.com',
                      },
                    },
                  ],
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
          name: 'Inter',
          data: fontData,
          weight: 400,
          style: 'normal',
        },
        fontBoldData ? {
          name: 'Inter',
          data: fontBoldData,
          weight: 700,
          style: 'normal',
        } : null,
      ].filter(Boolean),
    }
  );

  return svg;
}

/**
 * Convert SVG to PNG using resvg-js
 */
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

/**
 * Fetch public audits from API
 */
async function fetchPublicAudits() {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'https://api.seoindicator.com';
  
  try {
    // This endpoint would need to be created or use existing badge endpoint
    // For now, we'll use a placeholder that would be replaced with actual data
    const res = await fetch(`${apiUrl}/api/v1/audits/public`);
    
    if (!res.ok) {
      console.warn(`Warning: Could not fetch public audits from API (${res.status})`);
      return [];
    }
    
    const data = await res.json();
    return data.data || [];
  } catch (error) {
    console.warn('Warning: Could not fetch public audits:', error.message);
    return [];
  }
}

/**
 * Main function
 */
async function generateAuditOGImages() {
  console.log('Generating OG images for public audits...\n');
  
  // Ensure output directory exists
  if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
  }
  
  // Generate a default/fallback image
  console.log('  Generating default OG image...');
  try {
    const defaultSvg = await generateSVG({
      hash: 'default',
      site_url: 'yourwebsite.com',
      overall_score: 75,
      category_scores: {
        technical: 75,
        on_page: 70,
        content: 65,
        mobile: 80,
      },
    });
    const defaultPng = svgToPNG(defaultSvg);
    const defaultPath = path.join(OUT_DIR, 'default.png');
    fs.writeFileSync(defaultPath, defaultPng);
    console.log(`    ✓ Saved: ${defaultPath}`);
  } catch (err) {
    console.error('    ✗ Error generating default image:', err.message);
  }
  
  // Fetch public audits from API
  const audits = await fetchPublicAudits();
  
  if (audits.length === 0) {
    console.log('\n  No public audits found. Only default image generated.');
    console.log('  To generate images for specific audits, ensure the API endpoint returns audit data.\n');
  }
  
  let successCount = 0;
  let errorCount = 0;
  
  for (const audit of audits) {
    try {
      const hash = audit.hash || audit.public_hash;
      
      if (!hash || !/^[a-zA-Z0-9]{32}$/.test(hash)) {
        console.warn(`  ⚠️  Invalid hash format: ${hash}`);
        errorCount++;
        continue;
      }
      
      console.log(`  Generating: ${hash}`);
      console.log(`    Site: ${audit.site_url}`);
      console.log(`    Score: ${audit.overall_score}`);
      
      // Generate SVG
      const svg = await generateSVG(audit);
      
      // Convert to PNG
      const pngBuffer = svgToPNG(svg);
      
      // Save to file
      const outPath = path.join(OUT_DIR, `${hash}.png`);
      fs.writeFileSync(outPath, pngBuffer);
      
      console.log(`    ✓ Saved: ${outPath}`);
      successCount++;
    } catch (err) {
      console.error(`    ✗ Error:`, err.message);
      errorCount++;
    }
  }
  
  console.log(`\n========================================`);
  console.log(`Audit OG Image Generation Complete`);
  console.log(`  ✓ Success: ${successCount}`);
  console.log(`  ✗ Errors:  ${errorCount}`);
  console.log(`  Output:   ${OUT_DIR}`);
  console.log(`========================================\n`);
  
  // Return exit code
  return errorCount === 0 ? 0 : 1;
}

// Run
generateAuditOGImages()
  .then((code) => process.exit(code))
  .catch((err) => {
    console.error('Fatal error:', err);
    process.exit(1);
  });
