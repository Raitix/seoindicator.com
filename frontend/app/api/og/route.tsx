import { ImageResponse } from 'next/og';

// Helper to sanitize input - strip HTML tags and truncate
function sanitizeInput(input: string | null, maxLength: number = 50): string {
  if (!input) return '';
  
  // Decode URL encoding
  let decoded = input;
  try {
    decoded = decodeURIComponent(input);
  } catch {
    // If decoding fails, use as-is
  }
  
  // Strip HTML/script tags
  const stripped = decoded
    .replace(/<script[^>]*>.*?<\/script>/gi, '')
    .replace(/<[^>]*>/g, '')
    .replace(/javascript:/gi, '')
    .replace(/on\w+=/gi, '');
  
  // Truncate to max length
  return stripped.trim().slice(0, maxLength);
}

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  
  // Extract and sanitize query parameters
  const competitorA = sanitizeInput(searchParams.get('a')) || 'SEOIndicator';
  const competitorB = sanitizeInput(searchParams.get('b')) || 'Competitor';
  const title = sanitizeInput(searchParams.get('title'), 100);
  
  // Generate OG image with JSX
  return new ImageResponse(
    (
      <div
        style={{
          display: 'flex',
          height: '100%',
          width: '100%',
          padding: '60px',
          background: 'linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%)',
          color: 'white',
          fontFamily: 'system-ui, sans-serif',
        }}
      >
        <div style={{ display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
          <h1
            style={{
              fontSize: '48px',
              fontWeight: 'bold',
              margin: 0,
              lineHeight: 1.2,
            }}
          >
            {competitorA} vs {competitorB}
          </h1>
          <p
            style={{
              fontSize: '24px',
              marginTop: '20px',
              opacity: 0.9,
              margin: '20px 0 0 0',
            }}
          >
            {title || 'Compare features, pricing, and find the right SEO tool'}
          </p>
          <div style={{ display: 'flex', marginTop: '40px', gap: '20px' }}>
            <div
              style={{
                padding: '10px 20px',
                background: '#3b82f6',
                borderRadius: '8px',
                fontSize: '18px',
                fontWeight: 'bold',
              }}
            >
              SEOIndicator.com
            </div>
          </div>
        </div>
      </div>
    ),
    {
      width: 1200,
      height: 630,
      headers: {
        'Content-Type': 'image/png',
        'Cache-Control': 'public, max-age=86400, immutable',
      },
    }
  );
}
