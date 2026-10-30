const { chromium } = require('playwright');
const { guides, indexGuide, generateHTML } = require('./generate-og-images');
const fs = require('fs');
const path = require('path');

const OUTPUT_DIR = 'C:\\Users\\Raitis\\.openclaw\\workspace\\PROJECT-SEOINDICATOR\\frontend\\public\\guides\\og';
const CDP_URL = 'http://127.0.0.1:18800';

async function generateImages() {
  console.log('Connecting to browser via CDP...');
  const browser = await chromium.connectOverCDP(CDP_URL);
  const context = browser.contexts()[0];
  const page = context.pages().find(p => p.url().includes('localhost:8766')) || await context.newPage();
  
  await page.setViewportSize({ width: 1200, height: 630 });
  
  // Generate index image
  console.log('Generating og-index.png...');
  await page.goto('http://localhost:8766/index', { waitUntil: 'networkidle' });
  await page.screenshot({ 
    path: path.join(OUTPUT_DIR, 'og-index.png'),
    type: 'png'
  });
  console.log('  ✓ og-index.png');
  
  // Generate all guide images
  for (const guide of guides) {
    const filename = `og-${guide.slug}.png`;
    console.log(`Generating ${filename}...`);
    await page.goto(`http://localhost:8766/${guide.slug}`, { waitUntil: 'networkidle' });
    await page.screenshot({ 
      path: path.join(OUTPUT_DIR, filename),
      type: 'png'
    });
    console.log(`  ✓ ${filename}`);
  }
  
  console.log('\nAll OG images generated successfully!');
  console.log(`Output directory: ${OUTPUT_DIR}`);
  
  // List generated files
  const files = fs.readdirSync(OUTPUT_DIR).filter(f => f.endsWith('.png'));
  console.log(`\nGenerated ${files.length} PNG files:`);
  files.forEach(f => console.log(`  - ${f}`));
}

generateImages().catch(console.error);
