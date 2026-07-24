// Service Worker para PWA
// Este es un service worker basico que permite la instalacion PWA

const CACHE_NAME = 'opzio-erp-v4';

// Instalacion del Service Worker - sin precachear nada
self.addEventListener('install', (event) => {
  self.skipWaiting();
});

// Activacion - borrar TODOS los caches sin excepcion
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)));
    })
  );
  return self.clients.claim();
});

// Sin fetch handler - el navegador gestiona todas las peticiones directamente.
// El service worker solo existe para habilitar el PWA install prompt.
