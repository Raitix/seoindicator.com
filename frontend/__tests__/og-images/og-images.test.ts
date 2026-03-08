/**
 * OG Images Test Suite
 * Task: TASK-20-28 - Create OG Images for Guides
 * 
 * Tests for Open Graph images for all 17 SEO guide pages and the guide index page.
 */

import fs from 'fs';
import path from 'path';
import sizeOf from 'image-size';

// ============================================================================
// Test Configuration
// ============================================================================

const PUBLIC_DIR = path.join(__dirname, '../../public');
const OG_IMAGES_DIR = path.join(PUBLIC_DIR, 'guides', 'og');

// Expected OG image dimensions (1200x630 is optimal for social sharing)
const EXPECTED_WIDTH = 1200;
const EXPECTED_HEIGHT = 630;

// Valid image extensions
const VALID_EXTENSIONS = ['.png', '.jpg', '.jpeg', '.webp'];

// Expected file size range (in bytes)
const MIN_FILE_SIZE = 10 * 1024; // 10KB
const MAX_FILE_SIZE = 500 * 1024; // 500KB

// All expected guide slugs from registry
const EXPECTED_GUIDE_SLUGS = [
  'meta-title',
  'meta-description',
  'h1-headings',
  'heading-hierarchy',
  'image-alt-text',
  'image-optimization',
  'internal-links',
  'external-links',
  'page-speed',
  'mobile-friendly',
  'backlinks',
  'https-security',
  'structured-data',
  'canonical-urls',
  'robots-txt',
  'xml-sitemap',
  'open-graph',
  'twitter-cards',
];

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Get expected OG image filename for a guide slug
 */
function getExpectedFilename(slug: string): string {
  return `og-${slug}.png`;
}

/**
 * Check if a file is a valid image
 */
function isValidImageFile(filePath: string): boolean {
  const ext = path.extname(filePath).toLowerCase();
  return VALID_EXTENSIONS.includes(ext);
}

/**
 * Get image dimensions safely
 */
function getImageDimensions(filePath: string): { width: number; height: number } | null {
  try {
    const dimensions = sizeOf(filePath);
    if (dimensions.width && dimensions.height) {
      return { width: dimensions.width, height: dimensions.height };
    }
    return null;
  } catch {
    return null;
  }
}

// ============================================================================
// Test Suite: OG Images Directory
// ============================================================================

describe('OG Images Directory', () => {
  
  test('TC-001.1: OG images directory exists', () => {
    expect(fs.existsSync(OG_IMAGES_DIR)).toBe(true);
  });

  test('TC-001.2: OG images directory is not empty', () => {
    if (!fs.existsSync(OG_IMAGES_DIR)) {
      throw new Error('OG images directory does not exist');
    }
    const files = fs.readdirSync(OG_IMAGES_DIR);
    expect(files.length).toBeGreaterThan(0);
  });

});

// ============================================================================
// Test Suite: File Existence
// ============================================================================

describe('OG Image File Existence (TC-001)', () => {
  
  test('Index page OG image exists', () => {
    const indexPath = path.join(OG_IMAGES_DIR, 'og-index.png');
    expect(fs.existsSync(indexPath)).toBe(true);
  });

  test.each(EXPECTED_GUIDE_SLUGS)('Guide "%s" has OG image', (slug) => {
    const expectedFile = getExpectedFilename(slug);
    const filePath = path.join(OG_IMAGES_DIR, expectedFile);
    expect(fs.existsSync(filePath)).toBe(true);
  });

  test('All 18 OG images exist (17 guides + index)', () => {
    if (!fs.existsSync(OG_IMAGES_DIR)) {
      throw new Error('OG images directory does not exist');
    }
    
    const files = fs.readdirSync(OG_IMAGES_DIR).filter(f => 
      f.startsWith('og-') && isValidImageFile(path.join(OG_IMAGES_DIR, f))
    );
    
    // 17 guides + 1 index = 18 images
    expect(files.length).toBe(18);
  });

  test('No zero-byte image files', () => {
    if (!fs.existsSync(OG_IMAGES_DIR)) {
      throw new Error('OG images directory does not exist');
    }
    
    const files = fs.readdirSync(OG_IMAGES_DIR).filter(f => 
      f.startsWith('og-') && isValidImageFile(path.join(OG_IMAGES_DIR, f))
    );
    
    files.forEach(file => {
      const filePath = path.join(OG_IMAGES_DIR, file);
      const stats = fs.statSync(filePath);
      expect(stats.size).toBeGreaterThan(0);
    });
  });

});

// ============================================================================
// Test Suite: Image Dimensions
// ============================================================================

describe('OG Image Dimensions (TC-002)', () => {
  
  test('Index page OG image has correct dimensions (1200x630)', () => {
    const indexPath = path.join(OG_IMAGES_DIR, 'og-index.png');
    if (!fs.existsSync(indexPath)) {
      throw new Error('Index OG image does not exist');
    }
    
    const dimensions = getImageDimensions(indexPath);
    expect(dimensions).not.toBeNull();
    expect(dimensions!.width).toBe(EXPECTED_WIDTH);
    expect(dimensions!.height).toBe(EXPECTED_HEIGHT);
  });

  test.each(EXPECTED_GUIDE_SLUGS)('Guide "%s" OG image has correct dimensions', (slug) => {
    const expectedFile = getExpectedFilename(slug);
    const filePath = path.join(OG_IMAGES_DIR, expectedFile);
    
    if (!fs.existsSync(filePath)) {
      throw new Error(`OG image for ${slug} does not exist`);
    }
    
    const dimensions = getImageDimensions(filePath);
    expect(dimensions).not.toBeNull();
    expect(dimensions!.width).toBe(EXPECTED_WIDTH);
    expect(dimensions!.height).toBe(EXPECTED_HEIGHT);
  });

  test('All OG images have correct aspect ratio (1.91:1)', () => {
    if (!fs.existsSync(OG_IMAGES_DIR)) {
      throw new Error('OG images directory does not exist');
    }
    
    const files = fs.readdirSync(OG_IMAGES_DIR).filter(f => 
      f.startsWith('og-') && isValidImageFile(path.join(OG_IMAGES_DIR, f))
    );
    
    files.forEach(file => {
      const filePath = path.join(OG_IMAGES_DIR, file);
      const dimensions = getImageDimensions(filePath);
      if (dimensions) {
        const aspectRatio = dimensions.width / dimensions.height;
        // 1200/630 ≈ 1.9047... (OG standard is 1.91:1)
        expect(aspectRatio).toBeCloseTo(1.91, 1);
      }
    });
  });

});

// ============================================================================
// Test Suite: Image Format
// ============================================================================

describe('OG Image Format (TC-003)', () => {
  
  test('All OG images have valid image extensions', () => {
    if (!fs.existsSync(OG_IMAGES_DIR)) {
      throw new Error('OG images directory does not exist');
    }
    
    const files = fs.readdirSync(OG_IMAGES_DIR).filter(f => f.startsWith('og-'));
    
    files.forEach(file => {
      const ext = path.extname(file).toLowerCase();
      expect(VALID_EXTENSIONS).toContain(ext);
    });
  });

  test('All OG images are parseable as images', () => {
    if (!fs.existsSync(OG_IMAGES_DIR)) {
      throw new Error('OG images directory does not exist');
    }
    
    const files = fs.readdirSync(OG_IMAGES_DIR).filter(f => 
      f.startsWith('og-') && isValidImageFile(path.join(OG_IMAGES_DIR, f))
    );
    
    files.forEach(file => {
      const filePath = path.join(OG_IMAGES_DIR, file);
      const dimensions = getImageDimensions(filePath);
      expect(dimensions).not.toBeNull();
    });
  });

});

// ============================================================================
// Test Suite: File Naming Convention
// ============================================================================

describe('OG Image File Naming (TC-004)', () => {
  
  test('Index page uses og-index.png naming', () => {
    const indexPath = path.join(OG_IMAGES_DIR, 'og-index.png');
    expect(fs.existsSync(indexPath)).toBe(true);
  });

  test('All guide OG images follow og-{slug}.png pattern', () => {
    if (!fs.existsSync(OG_IMAGES_DIR)) {
      throw new Error('OG images directory does not exist');
    }
    
    const files = fs.readdirSync(OG_IMAGES_DIR).filter(f => 
      f.startsWith('og-') && f !== 'og-index.png'
    );
    
    files.forEach(file => {
      // Extract slug from filename (og-{slug}.png)
      const match = file.match(/^og-(.+)\.(png|jpg|jpeg|webp)$/i);
      expect(match).not.toBeNull();
      
      const slug = match![1];
      expect(EXPECTED_GUIDE_SLUGS).toContain(slug);
    });
  });

  test('No unexpected files in OG images directory', () => {
    if (!fs.existsSync(OG_IMAGES_DIR)) {
      throw new Error('OG images directory does not exist');
    }
    
    const files = fs.readdirSync(OG_IMAGES_DIR);
    const validNames = ['og-index.png', ...EXPECTED_GUIDE_SLUGS.map(s => `og-${s}.png`)];
    
    files.forEach(file => {
      // Allow hidden files and valid OG images
      const isHidden = file.startsWith('.');
      const isOgImage = validNames.some(name => 
        file === name || file === name.replace('.png', '.jpg') || file === name.replace('.png', '.webp')
      );
      expect(isHidden || isOgImage).toBe(true);
    });
  });

});

// ============================================================================
// Test Suite: Image Quality
// ============================================================================

describe('OG Image Quality (TC-007)', () => {
  
  test('All OG images have reasonable file size (10KB - 500KB)', () => {
    if (!fs.existsSync(OG_IMAGES_DIR)) {
      throw new Error('OG images directory does not exist');
    }
    
    const files = fs.readdirSync(OG_IMAGES_DIR).filter(f => 
      f.startsWith('og-') && isValidImageFile(path.join(OG_IMAGES_DIR, f))
    );
    
    files.forEach(file => {
      const filePath = path.join(OG_IMAGES_DIR, file);
      const stats = fs.statSync(filePath);
      expect(stats.size).toBeGreaterThanOrEqual(MIN_FILE_SIZE);
      expect(stats.size).toBeLessThanOrEqual(MAX_FILE_SIZE);
    });
  });

  test('All OG images have valid pixel dimensions', () => {
    if (!fs.existsSync(OG_IMAGES_DIR)) {
      throw new Error('OG images directory does not exist');
    }
    
    const files = fs.readdirSync(OG_IMAGES_DIR).filter(f => 
      f.startsWith('og-') && isValidImageFile(path.join(OG_IMAGES_DIR, f))
    );
    
    files.forEach(file => {
      const filePath = path.join(OG_IMAGES_DIR, file);
      const dimensions = getImageDimensions(filePath);
      expect(dimensions).not.toBeNull();
      expect(dimensions!.width).toBeGreaterThan(0);
      expect(dimensions!.height).toBeGreaterThan(0);
    });
  });

});
