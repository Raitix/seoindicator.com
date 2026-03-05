# iOS Support

iOS has unique requirements for Progressive Web Apps. While Safari doesn't fully support the web app manifest, you can achieve a great PWA experience using Apple-specific meta tags and icons.

## Apple Touch Icon

iOS uses `apple-touch-icon` instead of manifest icons. Add this to your HTML `<head>`:

```html
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
```

### Recommended Sizes

| Device | Size |
|--------|------|
| iPhone | 180x180 |
| iPad Pro | 167x167 |
| iPad | 152x152 |

For simplicity, use a 180x180 icon - iOS will scale it appropriately.

```html
<link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon-180.png">
```

### Apple Touch Icon Design

- iOS rounds corners automatically
- No transparency support - use solid backgrounds
- Don't include the rounded corners in your source image
- Avoid text and intricate details

## Apple Mobile Web App Meta Tags

### Enable Standalone Mode

```html
<meta name="apple-mobile-web-app-capable" content="yes">
```

This makes your PWA open in standalone mode when added to the home screen, hiding Safari's browser UI.

### Status Bar Style

Control the status bar appearance:

```html
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
```

Options:
- `default` - Standard white background
- `black` - Black background
- `black-translucent` - Transparent, content extends behind status bar

### App Title

Set the title shown under the home screen icon:

```html
<meta name="apple-mobile-web-app-title" content="My PWA">
```

## Viewport Configuration

Essential for proper mobile rendering:

```html
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
```

The `viewport-fit=cover` is important for devices with notches (iPhone X and later).

## Splash Screens

### Automatic Splash Screens (iOS 12.2+)

iOS automatically generates splash screens using your theme color and icon.

### Manual Splash Screens (Legacy)

For older iOS versions, you can specify splash screen images:

```html
<link rel="apple-touch-startup-image" href="/splash/iphone5.png" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)">
```

## Theme Color

Safari 15+ supports `theme-color`:

```html
<meta name="theme-color" content="#4285f4">
```

## Complete iOS Head Section

```html
<head>
  <!-- Viewport -->
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  
  <!-- iOS Standalone Mode -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="My PWA">
  
  <!-- Icons -->
  <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon-180.png">
  
  <!-- Theme Color -->
  <meta name="theme-color" content="#4285f4">
  
  <!-- Manifest -->
  <link rel="manifest" href="/manifest.json">
</head>
```

## iOS Limitations

- No push notifications (Safari on iOS)
- No background sync
- Limited service worker support
- Storage limits are lower than Android
- No maskable icon support

## Testing on iOS

1. Open Safari on an iOS device
2. Tap the Share button
3. Select "Add to Home Screen"
4. Verify the app opens in standalone mode
5. Check the icon and splash screen appearance
