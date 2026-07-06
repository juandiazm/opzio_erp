// Service Worker para PWA
// Este es un service worker básico que permite la instalación PWA

const CACHE_NAME = 'opzio-erp-v1';
const urlsToCache = [
  '/',
  '/css/app.css',
  '/js/app.js'
];

// Instalación del Service Worker
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Caching app shell');
        return cache.addAll(urlsToCache);
      })
      .catch((error) => {
        console.log('[Service Worker] Cache failed:', error);
      })
  );
  self.skipWaiting();
});

// Activación del Service Worker
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('[Service Worker] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Estrategia: Network First, Cache Fallback
self.addEventListener('fetch', (event) => {
  // Parsear la URL de forma segura
  let requestURL;
  try {
    requestURL = new URL(event.request.url);
  } catch (e) {
    // Si no se puede parsear la URL, ignorar este request
    return;
  }
  
  // Filtrar requests que no se pueden cachear:
  // 1. Solo HTTP/HTTPS (no chrome-extension://, chrome://, about://, etc.)
  // 2. Solo método GET
  // 3. No requests de extensiones del navegador
  const isHttpScheme = requestURL.protocol === 'http:' || requestURL.protocol === 'https:';
  
  if (
    event.request.method !== 'GET' ||
    !isHttpScheme
  ) {
    // Para requests no cacheables, simplemente dejar que el navegador las maneje
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Si la respuesta es válida, clonamos y guardamos en cache
        // Solo cacheamos respuestas exitosas y del mismo origen
        if (
          response && 
          response.status === 200 && 
          (response.type === 'basic' || response.type === 'cors')
        ) {
          // Verificar nuevamente el protocolo antes de cachear
          if (isHttpScheme) {
            const responseToCache = response.clone();
            caches.open(CACHE_NAME)
              .then((cache) => {
                try {
                  cache.put(event.request, responseToCache);
                } catch (error) {
                  console.log('[Service Worker] Cache put error:', error.message);
                }
              })
              .catch((error) => {
                console.log('[Service Worker] Cache open failed:', error.message);
              });
          }
        }
        return response;
      })
      .catch(() => {
        // Si falla la red, intentamos con cache
        return caches.match(event.request).then((response) => {
          if (response) {
            return response;
          }
          // Si no hay cache, devolvemos una respuesta básica
          return new Response('Offline - No cached version available', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({
              'Content-Type': 'text/plain'
            })
          });
        });
      })
  );
});
