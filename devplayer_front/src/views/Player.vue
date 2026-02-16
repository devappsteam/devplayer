<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import VideoPlayer from '@/components/VideoPlayer.vue';
import { XMarkIcon } from '@heroicons/vue/24/solid';

const route = useRoute();
const router = useRouter();

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1';

// Dados do item atual
const activeContent = ref<any>(null);
const favorites = ref<any[]>([]);

// Carregar favoritos
const loadFavorites = async () => {
  try {
    const favRes = await fetch(`${API_BASE_URL}/favorites/user/1`);
    if (favRes.ok) {
      const favData = await favRes.json();
      favorites.value = favData.data || [];
    }
  } catch (err) {
    console.error('Error loading favorites:', err);
  }
};

// Buscar dados do canal/filme/série
const loadContent = async () => {
  const id = route.params.id;
  const type = route.query.type || 'channel';

  try {
    // Por enquanto, simular com dados da query
    activeContent.value = {
      id: id,
      type: type,
      title: route.query.title || 'Reproduzindo...',
      description: route.query.description || '',
      url: route.query.url || '',
      image: route.query.image || ''
    };
  } catch (err) {
    console.error('Error loading content:', err);
  }
};

// Navegação entre favoritos
const goToNextFavorite = () => {
  if (favorites.value.length === 0) return;
  const currentId = activeContent.value?.id;
  const currentIndex = favorites.value.findIndex(f => f.id === currentId);
  const nextIndex = (currentIndex + 1) % favorites.value.length;
  const next = favorites.value[nextIndex];

  router.push({
    name: 'player',
    params: { id: next.id },
    query: {
      type: next.type || 'channel',
      title: next.name,
      description: next.description || '',
      url: next.stream_url || next.url,
      image: next.logo_url || next.poster
    }
  });
};

const goToPreviousFavorite = () => {
  if (favorites.value.length === 0) return;
  const currentId = activeContent.value?.id;
  const currentIndex = favorites.value.findIndex(f => f.id === currentId);
  const prevIndex = currentIndex === 0 ? favorites.value.length - 1 : currentIndex - 1;
  const prev = favorites.value[prevIndex];

  router.push({
    name: 'player',
    params: { id: prev.id },
    query: {
      type: prev.type || 'channel',
      title: prev.name,
      description: prev.description || '',
      url: prev.stream_url || prev.url,
      image: prev.logo_url || prev.poster
    }
  });
};

const getNextFavorite = () => {
  if (favorites.value.length === 0) return null;
  const currentId = activeContent.value?.id;
  const currentIndex = favorites.value.findIndex(f => f.id === currentId);
  const next = favorites.value[(currentIndex + 1) % favorites.value.length];
  return next ? {
    id: next.id,
    title: next.name,
    image: next.logo_url || next.poster
  } : null;
};

const getPreviousFavorite = () => {
  if (favorites.value.length === 0) return null;
  const currentId = activeContent.value?.id;
  const currentIndex = favorites.value.findIndex(f => f.id === currentId);
  const prev = favorites.value[currentIndex === 0 ? favorites.value.length - 1 : currentIndex - 1];
  return prev ? {
    id: prev.id,
    title: prev.name,
    image: prev.logo_url || prev.poster
  } : null;
};

const shouldShowNavigation = computed(() => favorites.value.length >= 3);

const closePlayer = () => {
  router.back();
};

onMounted(async () => {
  await loadFavorites();
  await loadContent();
});
</script>

<template>
  <div class="fixed inset-0 z-99999 bg-black flex flex-col">
    <!-- Close Button -->
    <button
      @click="closePlayer"
      class="absolute top-4 right-4 z-100000 p-2 bg-black/70 hover:bg-red-600 rounded-full transition-colors cursor-pointer"
    >
      <XMarkIcon class="w-8 h-8 text-white" />
    </button>

    <!-- Video Player -->
    <div class="flex-1 relative">
      <VideoPlayer
        v-if="activeContent?.url"
        :src="activeContent.url"
        :autoplay="true"
        :should-show-navigation="shouldShowNavigation"
        :next-channel="getNextFavorite()"
        :prev-channel="getPreviousFavorite()"
        @next-favorite="goToNextFavorite"
        @prev-favorite="goToPreviousFavorite"
      >
        <template #info>
          <h2 class="text-2xl font-bold mb-2">{{ activeContent.title }}</h2>
          <p class="text-gray-300 text-sm">{{ activeContent.description }}</p>
        </template>
      </VideoPlayer>
    </div>
  </div>
</template>

<style scoped>
/* Sem estilos adicionais necessários */
</style>
