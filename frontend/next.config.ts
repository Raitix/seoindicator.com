import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Only use static export for production builds, not for dev/testing
  ...(process.env.NODE_ENV === 'production' && !process.env.PLAYWRIGHT_TEST ? { output: 'export' as const } : {}),
  trailingSlash: true,
  typescript: {
    ignoreBuildErrors: true,
  },
  images: {
    unoptimized: true, // Required for static export
  },
  
  // Cache headers configuration for CDN and browser caching
  // These headers are applied when deployed to Vercel or similar platforms
  async headers() {
    return [
      {
        // Public audit pages - ISR-like caching with stale-while-revalidate
        source: '/audit/:hash/',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=3600, stale-while-revalidate=60',
          },
        ],
      },
      {
        // Badge SVG images - long cache with immutable
        source: '/badge/:hash/:size.svg',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=86400, immutable',
          },
          {
            key: 'Content-Type',
            value: 'image/svg+xml',
          },
        ],
      },
      {
        // Badge PNG images
        source: '/badge/:hash/:size.png',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=86400, immutable',
          },
        ],
      },
      {
        // OG images for social sharing
        source: '/badge/:hash/og.png',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=86400, immutable',
          },
        ],
      },
      {
        // Static assets (JS, CSS, images) - long cache
        source: '/_next/static/:path*',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=31536000, immutable',
          },
        ],
      },
      {
        // Public assets
        source: '/public/:path*',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=86400',
          },
        ],
      },
    ];
  },
  
  // Compress responses
  compress: true,
  
  // Enable strict mode for better React behavior
  reactStrictMode: true,
  
  // Power by header removal for security
  poweredByHeader: false,
};

export default nextConfig;
