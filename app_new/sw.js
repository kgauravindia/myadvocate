// Service Worker for My Advocate PWA
const CACHE_NAME = 'myadv-cache-v1';
const ASSETS_TO_CACHE = [
  './',
  './index.html',
  './css/app.css',
  './js/app.js',
  './manifest.json',
  './img/logo.png',
  './img/logo-circle.png',
  './img/favicon.ico'
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS_TO_CACHE))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.map((k) => {
        if (k !== CACHE_NAME) return caches.delete(k);
      })
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (e) => {
  // Never cache POST requests or external APIs
  if (e.request.method !== 'GET' || e.request.url.includes('/api/')) {
    return;
  }
  e.respondWith(
    caches.match(e.request).then((res) => res || fetch(e.request))
  );
});
