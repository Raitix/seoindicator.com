# Manifest Basics

The Web App Manifest is a JSON file that provides metadata about your Progressive Web App. It's the foundation that enables your web app to be installed on devices and appear as a standalone application.

## What is a Web App Manifest?

A web app manifest is a JSON file that tells the browser about your PWA's name, icons, colors, and other properties. It's linked from your HTML using a `<link>` tag:

```html
<link rel="manifest" href="/manifest.json">
```

## Required Fields

Every PWA manifest must include these essential fields:

### `name`

The full name of your application. This appears in app stores and install prompts.

```json
{
  "name": "My Awesome PWA Application"
}
```

### `short_name`

A shorter version of the name. Used on the home screen, launcher, and places with limited space.

```json
{
  "short_name": "My PWA"
}
```

### `start_url`

The URL that opens when the user launches the PWA. This should be a relative URL.

```json
{
  "start_url": "/",
  "start_url": "/index.html"
}
```

### `display`

Controls how the PWA appears to users. Options include:

- **`standalone`** - App-like experience without browser UI
- **`fullscreen`** - Takes the entire screen (good for games)
- **`minimal-ui`** - Minimal browser controls
- **`browser`** - Standard browser tab

```json
{
  "display": "standalone"
}
```

## Recommended Fields

### `description`

A brief description of what your app does.

```json
{
  "description": "A fast, offline-capable task management application"
}
```

### `theme_color` and `background_color`

These colors customize the browser UI and splash screen.

```json
{
  "theme_color": "#4285f4",
  "background_color": "#ffffff"
}
```

### `icons`

Array of icon objects with different sizes for various contexts.

```json
{
  "icons": [
    {
      "src": "/icons/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/icons/icon-512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

## Complete Example

```json
{
  "name": "My Awesome PWA",
  "short_name": "MyPWA",
  "description": "A powerful progressive web application",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#4285f4",
  "icons": [
    {
      "src": "/icons/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/icons/icon-512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

## Validation

Use Chrome DevTools or online tools to validate your manifest:

1. Open Chrome DevTools → Application tab
2. Click "Manifest" in the sidebar
3. Review any warnings or errors

A valid manifest is required for PWA installation eligibility.
