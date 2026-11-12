const CACHE_NAME = 'jastipkuy-pwa-v2';
const STATIC_ASSETS = [
  '/',
  '/login',
  '/manifest.json',
  '/images/icons/icon-192x192.png',
  '/images/icons/icon-512x512.png',
  '/images/icons/icon.svg',
  '/images/services/beli-antar.png',
  '/images/services/ambil-antar.png',
  '/images/services/kirim-pihak-ketiga.png',
  '/images/services/toko-kirim.png',
  '/images/services/multi-stop.png',
  '/images/services/dokumen.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS).catch((err) => {
        console.warn('PWA: Some assets could not be precached', err);
      });
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
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
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  // Only handle GET requests and non-API/non-livewire requests
  if (event.request.method !== 'GET') return;
  const url = new URL(event.request.url);

  // Skip Livewire and API endpoints from caching to ensure real-time data
  if (url.pathname.startsWith('/livewire') || url.pathname.includes('/api/') || url.pathname.includes('/customer/orders/active-feed')) {
    return;
  }

  // Network-first strategy for navigation and HTML, fallback to cache
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));
          }
          return response;
        })
        .catch(() => caches.match(event.request).then((cached) => cached || caches.match('/')))
    );
    return;
  }

  // Cache-first for images and icons, network fallback
  if (event.request.destination === 'image' || url.pathname.startsWith('/images/')) {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        if (cached) return cached;
        return fetch(event.request).then((response) => {
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));
          }
          return response;
        });
      })
    );
    return;
  }

  // Standard network with cache fallback
  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request))
  );
});

