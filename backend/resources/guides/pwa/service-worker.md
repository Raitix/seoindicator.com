# Service Worker Overview

Service workers are the heart of Progressive Web Apps, enabling offline functionality, push notifications, and background synchronization. This guide provides an overview of key concepts.

## What is a Service Worker?

A service worker is a JavaScript file that runs in the background, separate from your web page. It acts as a network proxy, intercepting requests and enabling features like:

- **Offline access** - Cache resources for offline use
- **Push notifications** - Send messages to users
- **Background sync** - Defer actions until stable connectivity
- **Periodic sync** - Run tasks at intervals

## Service Worker Lifecycle

### Registration

Register the service worker from your main JavaScript:

```javascript
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      const registration = await navigator.serviceWorker.register('/sw.js');
      console.log('Service worker registered:', registration);
    } catch (error) {
      console.error('Registration failed:', error);
    }
  });
}
```

### Installation

The `install` event fires when the service worker is first registered:

```javascript
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open('my-cache-v1').then((cache) => {
      return cache.addAll([
        '/',
        '/styles.css',
        '/app.js',
        '/offline.html'
      ]);
    })
  );
});
```

### Activation

The `activate` event fires after installation, perfect for cleanup:

```javascript
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== 'my-cache-v1')
          .map((name) => caches.delete(name))
      );
    })
  );
});
```

## Caching Strategies

### Cache First

Serve from cache, fall back to network:

```javascript
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});
```

### Network First

Try network, fall back to cache:

```javascript
self.addEventListener('fetch', (event) => {
  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Cache successful responses
        if (response.ok) {
          const responseClone = response.clone();
          caches.open('my-cache').then((cache) => {
            cache.put(event.request, responseClone);
          });
        }
        return response;
      })
      .catch(() => caches.match(event.request))
  );
});
```

### Stale While Revalidate

Serve cache immediately, update in background:

```javascript
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.open('my-cache').then((cache) => {
      return cache.match(event.request).then((response) => {
        const fetchPromise = fetch(event.request).then((networkResponse) => {
          cache.put(event.request, networkResponse.clone());
          return networkResponse;
        });
        return response || fetchPromise;
      });
    })
  );
});
```

## Offline Fallback

Provide a custom offline page:

```javascript
self.addEventListener('fetch', (event) => {
  event.respondWith(
    fetch(event.request).catch(() => {
      if (event.request.mode === 'navigate') {
        return caches.match('/offline.html');
      }
    })
  );
});
```

## Update Strategy

When you update your service worker:

1. The browser detects the file change
2. New service worker installs (waiting state)
3. User closes all tabs with the app
4. New service worker activates

To force immediate activation:

```javascript
self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});
```

## Best Practices

- Keep service worker simple - only handle what's needed
- Use versioned cache names for easy updates
- Test offline scenarios thoroughly
- Consider caching strategy per resource type
- Don't cache sensitive data or authenticated responses

## Next Steps

- Explore Workbox for simplified service worker management
- Implement push notifications for user engagement
- Add background sync for offline form submissions
- Use Lighthouse to audit your PWA implementation
