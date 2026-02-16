import { ref, onMounted } from 'vue';

interface PWAInstallPrompt extends Event {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

interface SyncManager {
  register(tag: string): Promise<void>;
}

interface PeriodicSyncManager {
  register(tag: string, options: { minInterval: number }): Promise<void>;
}

interface ExtendedServiceWorkerRegistration extends ServiceWorkerRegistration {
  sync?: SyncManager;
  periodicSync?: PeriodicSyncManager;
}

const isInstalled = ref(false);
const isInstallPromptReady = ref(false);
const installPrompt = ref<PWAInstallPrompt | null>(null);
const isOnline = ref(navigator.onLine);
const swRegistration = ref<ExtendedServiceWorkerRegistration | null>(null);

export const usePWA = () => {
  /**
   * Register service worker
   */
  const registerServiceWorker = async () => {
    if (!('serviceWorker' in navigator)) {
      console.log('[PWA] Service Workers not supported');
      return;
    }

    try {
      const registration = (await navigator.serviceWorker.register('/sw.js', {
        scope: '/',
      })) as ExtendedServiceWorkerRegistration;

      swRegistration.value = registration;
      console.log('[PWA] Service Worker registered', registration);

      // Listen for updates
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing;
        if (newWorker) {
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              console.log('[PWA] New service worker available');
              // Notify user about update (optional)
              notifyUpdate();
            }
          });
        }
      });

      // Check for updates periodically
      setInterval(() => {
        registration.update();
      }, 60000); // Every 60 seconds
    } catch (error) {
      console.error('[PWA] Service Worker registration failed:', error);
    }
  };

  /**
   * Handle install prompt
   */
  const handleInstallPrompt = (event: Event) => {
    event.preventDefault();
    installPrompt.value = event as PWAInstallPrompt;
    isInstallPromptReady.value = true;
    console.log('[PWA] Install prompt ready');
  };

  /**
   * Trigger installation
   */
  const install = async () => {
    if (!installPrompt.value) {
      console.warn('[PWA] Install prompt not available');
      return;
    }

    try {
      await installPrompt.value.prompt();
      const { outcome } = await installPrompt.value.userChoice;

      if (outcome === 'accepted') {
        console.log('[PWA] User accepted install');
        isInstalled.value = true;
      } else {
        console.log('[PWA] User dismissed install');
      }

      installPrompt.value = null;
      isInstallPromptReady.value = false;
    } catch (error) {
      console.error('[PWA] Installation failed:', error);
    }
  };

  /**
   * Check if app is installed
   */
  const checkIfInstalled = () => {
    // Check multiple ways app might be installed
    const isInStandaloneMode =
      window.matchMedia('(display-mode: standalone)').matches ||
      (window.navigator as any).standalone === true ||
      document.referrer.includes('android-app://');

    isInstalled.value = isInStandaloneMode;
    console.log('[PWA] Installation status:', isInstalled.value);
  };

  /**
   * Handle online/offline status
   */
  const setupOnlineStatus = () => {
    window.addEventListener('online', () => {
      isOnline.value = true;
      console.log('[PWA] Online');
      syncData();
    });

    window.addEventListener('offline', () => {
      isOnline.value = false;
      console.log('[PWA] Offline');
    });
  };

  /**
   * Register background sync
   */
  const setupBackgroundSync = async () => {
    if (!('serviceWorker' in navigator) || !('SyncManager' in window)) {
      console.log('[PWA] Background Sync not supported');
      return;
    }

    try {
      const registration = (await navigator.serviceWorker.ready) as ExtendedServiceWorkerRegistration;

      if (registration.sync) {
        // Register sync for history
        await registration.sync.register('sync-history');
        console.log('[PWA] Background sync registered for history');

        // Register sync for favorites
        await registration.sync.register('sync-favorites');
        console.log('[PWA] Background sync registered for favorites');
      }
    } catch (error) {
      console.error('[PWA] Background sync registration failed:', error);
    }
  };

  /**
   * Sync data when back online
   */
  const syncData = async () => {
    console.log('[PWA] Syncing data...');

    try {
      // Trigger background sync
      const registration = (await navigator.serviceWorker.ready) as ExtendedServiceWorkerRegistration;
      if (registration.sync) {
        await registration.sync.register('sync-history');
        await registration.sync.register('sync-favorites');
      }
    } catch (error) {
      console.error('[PWA] Sync failed:', error);
    }
  };

  /**
   * Request periodic background sync
   */
  const setupPeriodicSync = async () => {
    if (!('serviceWorker' in navigator) || !('PeriodicSyncManager' in window)) {
      console.log('[PWA] Periodic Sync not supported');
      return;
    }

    try {
      const registration = (await navigator.serviceWorker.ready) as ExtendedServiceWorkerRegistration;

      if (registration.periodicSync) {
        // Request permission first
        if ('permissions' in navigator) {
          const permission = await (navigator.permissions as any).query({
            name: 'periodic-background-sync',
          });

          if (permission.state === 'granted') {
            await registration.periodicSync.register('update-content', {
              minInterval: 24 * 60 * 60 * 1000, // 24 hours
            });
            console.log('[PWA] Periodic sync registered');
          }
        }
      }
    } catch (error) {
      console.error('[PWA] Periodic sync registration failed:', error);
    }
  };

  /**
   * Request notification permission
   */
  const requestNotificationPermission = async () => {
    if (!('Notification' in window)) {
      console.log('[PWA] Notifications not supported');
      return false;
    }

    try {
      const permission = await Notification.requestPermission();
      console.log('[PWA] Notification permission:', permission);
      return permission === 'granted';
    } catch (error) {
      console.error('[PWA] Notification permission request failed:', error);
      return false;
    }
  };

  /**
   * Send notification
   */
  const sendNotification = async (title: string, options?: NotificationOptions) => {
    if (!('serviceWorker' in navigator)) {
      console.log('[PWA] Service Workers not supported for notifications');
      return;
    }

    try {
      const registration = await navigator.serviceWorker.ready;
      await registration.showNotification(title, {
        badge: '/favicon.png',
        icon: '/favicon.png',
        ...options,
      });
    } catch (error) {
      console.error('[PWA] Failed to send notification:', error);
    }
  };

  /**
   * Notify about service worker update
   */
  const notifyUpdate = async () => {
    const permission = await requestNotificationPermission();
    if (permission) {
      await sendNotification('DevPlayer atualizado!', {
        body: 'Uma nova versão está disponível. Recarregue para atualizar.',
        tag: 'update-notification',
        requireInteraction: true,
      });
    }
  };

  /**
   * Clear service worker cache
   */
  const clearCache = async () => {
    if (!('serviceWorker' in navigator)) {
      return;
    }

    const registration = await navigator.serviceWorker.ready;

    // Send message to service worker
    registration.active?.postMessage({
      type: 'CLEAR_CACHE',
    });

    console.log('[PWA] Cache cleared');
  };

  /**
   * Skip waiting and activate new service worker
   */
  const skipWaiting = () => {
    if (!swRegistration.value?.waiting) {
      return;
    }

    swRegistration.value.waiting.postMessage({
      type: 'SKIP_WAITING',
    });

    // Reload after new service worker is activated
    let refreshing = false;
    navigator.serviceWorker.oncontrollerchange = () => {
      if (refreshing) return;
      refreshing = true;
      window.location.reload();
    };
  };

  /**
   * Initialize PWA
   */
  onMounted(() => {
    console.log('[PWA] Initializing...');

    // Check installation status
    checkIfInstalled();

    // Register service worker
    registerServiceWorker();

    // Setup online/offline status
    setupOnlineStatus();

    // Setup background sync
    setupBackgroundSync();

    // Setup periodic sync
    setupPeriodicSync();

    // Listen for install prompt
    window.addEventListener('beforeinstallprompt', handleInstallPrompt);

    // Listen for app installed
    window.addEventListener('appinstalled', () => {
      console.log('[PWA] App installed');
      isInstalled.value = true;
      isInstallPromptReady.value = false;
    });
  });

  return {
    isInstalled,
    isInstallPromptReady,
    install,
    isOnline,
    requestNotificationPermission,
    sendNotification,
    clearCache,
    skipWaiting,
  };
};
