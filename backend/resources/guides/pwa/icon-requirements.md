# Icon Requirements

Icons are critical for PWA user experience. They appear on the home screen, in the app launcher, and in the splash screen. This guide covers the sizes, formats, and purposes you need to know.

## Standard Sizes

### Required Icons

These sizes are required for PWA installation eligibility:

| Size | Purpose | Required |
|------|---------|----------|
| 192x192 | Android home screen | Yes |
| 512x512 | Android splash screen | Yes |

### Recommended Icons

For broader compatibility, also include:

| Size | Purpose |
|------|---------|
| 72x72 | Small Android devices |
| 96x96 | Android notification icon |
| 128x128 | Chrome Web Store |
| 144x144 | iOS devices (via apple-touch-icon) |
| 152x152 | iPad |
| 180x180 | iPhone |
| 256x256 | Desktop shortcuts |
| 384x384 | High-DPI Android |

## Icon Formats

### PNG (Recommended)

- Most widely supported format
- Supports transparency
- Works across all platforms

```json
{
  "icons": [
    {
      "src": "/icons/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    }
  ]
}
```

### SVG (Scalable)

- Single file scales to any size
- Smaller file size for simple icons
- Limited browser support for manifest icons

```json
{
  "icons": [
    {
      "src": "/icons/icon.svg",
      "sizes": "any",
      "type": "image/svg+xml"
    }
  ]
}
```

### WebP

- Better compression than PNG
- Supported in modern browsers
- Consider fallbacks for older browsers

## Icon Purposes

The `purpose` field controls how icons are used:

### `any` (Default)

Standard icon used in most contexts.

```json
{
  "src": "/icons/icon-192.png",
  "sizes": "192x192",
  "type": "image/png",
  "purpose": "any"
}
```

### `maskable`

Icons that can be masked by the OS. Must be designed with a "safe zone" - content should be within the center 80% of the icon. The outer 10% on all sides may be masked.

```json
{
  "src": "/icons/icon-maskable-192.png",
  "sizes": "192x192",
  "type": "image/png",
  "purpose": "maskable"
}
```

**Maskable Icon Design Tips:**
- Keep all important content in the center 80%
- Use a solid background color
- Avoid text and intricate details near edges

### `monochrome`

Single-color icons for themed contexts (Android 13+).

```json
{
  "src": "/icons/icon-mono-192.png",
  "sizes": "192x192",
  "type": "image/png",
  "purpose": "monochrome"
}
```

## Complete Icon Configuration

```json
{
  "icons": [
    {
      "src": "/icons/icon-192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any"
    },
    {
      "src": "/icons/icon-512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any"
    },
    {
      "src": "/icons/icon-maskable-192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "maskable"
    },
    {
      "src": "/icons/icon-maskable-512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "maskable"
    }
  ]
}
```

## Icon Generation Tools

- **PWA Asset Generator** - Generate all sizes from one image
- **RealFaviconGenerator** - Web-based icon generator
- **Sharp (Node.js)** - Programmatic icon generation
- **ImageMagick** - Command-line batch processing

## Testing Your Icons

1. Check Chrome DevTools → Application → Manifest
2. Use Lighthouse audit for PWA compliance
3. Test on actual devices for accurate appearance
