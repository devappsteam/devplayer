<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useContentStore } from '@/stores/content';
import { useSearch } from '@/composables/useSearch';
import { usePWA } from '@/composables/usePWA';
import NavBar from '@/components/NavBar.vue';
import PWAInstallPrompt from '@/components/PWAInstallPrompt.vue';

const route = useRoute();
const store = useContentStore();
const { openSearch } = useSearch();
const { } = usePWA(); // Initialize PWA
const showNavbar = computed(() => !route.meta.hideNavbar);
const isPlayerOpen = ref(false);

const handleOpenSearch = () => {
  openSearch();
};

onMounted(async () => {
  // Carregar favoritos ao iniciar a aplicação
  await store.initializeFavorites();

  // Watch for player-open class changes
  const observer = new MutationObserver(() => {
    isPlayerOpen.value = document.documentElement.classList.contains('player-open');
  });

  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
  });
});
</script>

<template>
  <div class="bg-[#141414] min-h-screen text-white font-sans antialiased overflow-x-hidden relative selection:bg-red-600 selection:text-white">
    <!-- PWA Install Prompt -->
    <PWAInstallPrompt />

    <!-- Navigation -->
    <NavBar
      v-if="showNavbar"
      :class="{ 'hidden': isPlayerOpen }"
      @open-search="handleOpenSearch"
    />

    <!-- Main Content Router View -->
    <main class="relative z-0">
      <RouterView />
    </main>
  </div>
</template>

<style>
/* Global Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Hide navbar when player is open */
.player-open nav,
.player-open header {
  display: none !important;
}
</style>
