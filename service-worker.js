// service-worker.js
// Cache-first strategy for static assets and offline page fallback

const CACHE_NAME = 'palghar-live-cache-v1';
const ASSETS = [
  '/index.php',
  '/assets/css/style.min.css',
  '/assets/js/app.min.js',
  '/assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg',
  '/manifest.json'
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS);
    })
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
});

self.addEventListener('fetch', (e) => {
  // Avoid post requests or external APIs
  if (e.request.method !== 'GET' || !e.request.url.startsWith(self.location.origin)) {
    return;
  }
  
  e.respondWith(
    caches.match(e.request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }
      
      return fetch(e.request).then((networkResponse) => {
        if (!networkResponse || networkResponse.status !== 200) {
          return networkResponse;
        }
        
        // Cache static files dynamically
        const url = new URL(e.request.url);
        if (url.pathname.match(/\.(css|js|woff2|png|jpg|jpeg|svg|webp|avif)$/)) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(e.request, responseToCache);
          });
        }
        
        return networkResponse;
      }).catch(() => {
        // Fallback for offline page
        return caches.match('/index.php');
      });
    })
  );
});
