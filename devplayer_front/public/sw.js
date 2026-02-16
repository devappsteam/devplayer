const CACHE_NAME = 'devplayer-v1';
const RUNTIME_CACHE = 'devplayer-runtime-v1';
const API_CACHE = 'devplayer-api-v1';

const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/manifest.json',
  '/favicon.png',
  '/logo.png'
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
  console.log('[ServiceWorker] Installing...');

  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[ServiceWorker] Caching static assets');
      return cache.addAll(STATIC_ASSETS);
    }).then(() => {
      console.log('[ServiceWorker] Skipping waiting');
      return self.skipWaiting();
    })
  );
});

// Ativação do Service Worker
self.addEventListener('activate', (event) => {
  console.log('[ServiceWorker] Activating...');

  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME &&
              cacheName !== RUNTIME_CACHE &&
              cacheName !== API_CACHE) {
            console.log('[ServiceWorker] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('[ServiceWorker] Claiming clients');
      return self.clients.claim();
    })
  );
});

// Interceptar requisições
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // API requests - Network first, fallback to cache
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Clone the response
          const clonedResponse = response.clone();

          // Cache successful API responses
          if (response.status === 200) {
            caches.open(API_CACHE).then((cache) => {
              cache.put(request, clonedResponse);
            });
          }

          return response;
        })
        .catch(() => {
          // Return cached API response if offline
          return caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
              console.log('[ServiceWorker] API request from cache:', request.url);
              return cachedResponse;
            }

            // Return offline response
            return new Response(
              JSON.stringify({
                success: false,
                message: 'You are offline. Some features may be unavailable.',
                offline: true
              }),
              {
                status: 503,
                statusText: 'Service Unavailable',
                headers: new Headers({
                  'Content-Type': 'application/json'
                })
              }
            );
          });
        })
    );
    return;
  }

  // Static assets - Cache first, fallback to network
  if (isStaticAsset(request)) {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        if (cachedResponse) {
          console.log('[ServiceWorker] Static asset from cache:', request.url);
          return cachedResponse;
        }

        return fetch(request).then((response) => {
          // Cache successful responses
          if (response.status === 200) {
            const clonedResponse = response.clone();
            caches.open(RUNTIME_CACHE).then((cache) => {
              cache.put(request, clonedResponse);
            });
          }
          return response;
        });
      }).catch(() => {
        // Return offline page for document requests
        if (request.destination === 'document') {
          return caches.match('/index.html');
        }
      })
    );
    return;
  }

  // For everything else, use network first strategy
  event.respondWith(
    fetch(request)
      .then((response) => {
        // Cache successful responses
        if (response.status === 200 && response.type !== 'error') {
          const clonedResponse = response.clone();
          caches.open(RUNTIME_CACHE).then((cache) => {
            cache.put(request, clonedResponse);
          });
        }
        return response;
      })
      .catch(() => {
        return caches.match(request).catch(() => {
          // Return offline page as last resort
          return caches.match('/index.html');
        });
      })
  );
});

// Handle messages from clients
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    console.log('[ServiceWorker] SKIP_WAITING called');
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'CLEAR_CACHE') {
    console.log('[ServiceWorker] Clearing caches...');
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          return caches.delete(cacheName);
        })
      );
    });
  }
});

// Handle background sync
self.addEventListener('sync', (event) => {
  console.log('[ServiceWorker] Background sync:', event.tag);

  if (event.tag === 'sync-history') {
    event.waitUntil(syncHistory());
  }

  if (event.tag === 'sync-favorites') {
    event.waitUntil(syncFavorites());
  }
});

async function syncHistory() {
  try {
    // Attempt to sync history with server
    const response = await fetch('/api/v1/history/sync', {
      method: 'POST'
    });
    console.log('[ServiceWorker] History synced:', response.ok);
    return response.ok;
  } catch (error) {
    console.log('[ServiceWorker] History sync failed:', error);
    // Retry later
    return false;
  }
}

async function syncFavorites() {
  try {
    // Attempt to sync favorites with server
    const response = await fetch('/api/v1/favorites/sync', {
      method: 'POST'
    });
    console.log('[ServiceWorker] Favorites synced:', response.ok);
    return response.ok;
  } catch (error) {
    console.log('[ServiceWorker] Favorites sync failed:', error);
    // Retry later
    return false;
  }
}

// Helper function to check if request is a static asset
function isStaticAsset(request) {
  const url = new URL(request.url);

  // Check if it's a known static asset or common static file extension
  return (
    STATIC_ASSETS.includes(url.pathname) ||
    /\.(js|css|png|jpg|jpeg|svg|gif|webp|woff|woff2|ttf|eot)$/i.test(url.pathname)
  );
}
