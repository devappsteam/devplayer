<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { PlayIcon, PlusIcon, CheckIcon } from '@heroicons/vue/24/solid';
import { useContentStore } from '@/stores/content';

const props = defineProps<{
  isOpen: boolean;
  streamType: 'live' | 'vod' | 'series';
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'play', item: any): void;
}>();

const store = useContentStore();
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const isSearching = ref(false);
const API_BASE_URL = 'http://localhost:8000/api/v1';

const placeholder = computed(() => {
  switch (props.streamType) {
    case 'live':
      return 'Buscar canais...';
    case 'vod':
      return 'Buscar filmes...';
    case 'series':
      return 'Buscar séries...';
    default:
      return 'Buscar...';
  }
});

const favoriteIds = computed(() => new Set(store.favorites.map(f => f.id)));

const isItemFavorite = (itemId: number): boolean => {
  return favoriteIds.value.has(itemId);
};

const performSearch = async () => {
  if (!searchQuery.value.trim()) {
    searchResults.value = [];
    return;
  }

  isSearching.value = true;

  try {
    const params = new URLSearchParams({
      q: searchQuery.value,
      stream_type: props.streamType,
      per_page: '50'
    });

    const response = await fetch(`${API_BASE_URL}/channels/search?${params}`);
    if (response.ok) {
      const data = await response.json();
      searchResults.value = (data.data || []).map((c: any) => ({
        id: c.id,
        uuid: c.uuid,
        title: c.name || 'Sem Título',
        description: c.category?.name || '',
        image: c.logo_url || c.stream_icon || c.logo || c.poster || 'https://via.placeholder.com/300x450?text=No+Image',
        url: c.stream_url || c.url || '',
        rating: c.metadata?.rating_5based || c.metadata?.rating || 0,
        type: props.streamType === 'live' ? 'channel' : props.streamType === 'vod' ? 'movie' : 'series'
      }));
    }
  } catch (err) {
    console.error('Error searching:', err);
  } finally {
    isSearching.value = false;
  }
};

// Debounce search
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    performSearch();
  }, 300);
});

const handleClose = () => {
  searchQuery.value = '';
  searchResults.value = [];
  emit('close');
};

const handlePlay = (item: any) => {
  console.log('Playing item from search:', item);
  console.log('Item URL:', item.url);
  emit('play', item);
  handleClose();
};

const toggleFavorite = async (item: any) => {
  console.log('Toggling favorite:', item);
  await store.toggleFavorite(item);
  console.log('Favorites after toggle:', store.favorites.map(f => f.id));
};

// Close on Escape key
const handleKeyDown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') {
    handleClose();
  }
};

watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    document.addEventListener('keydown', handleKeyDown);
    document.body.style.overflow = 'hidden';
  } else {
    document.removeEventListener('keydown', handleKeyDown);
    document.body.style.overflow = '';
  }
});
</script>

<template>
  <Transition name="fade">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-[10000] bg-black/90 backdrop-blur-md flex items-start justify-center pt-32 px-4"
      @click.self="handleClose"
    >
      <div class="w-full max-w-4xl">
        <!-- Search Input -->
        <div class="relative mb-8">
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-6 h-6 text-gray-400" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="placeholder"
              class="w-full bg-gray-900/80 text-white text-xl pl-14 pr-14 py-4 rounded-lg border-2 border-gray-700 focus:border-red-600 focus:outline-none transition-colors"
              autofocus
            />
            <button
              @click="handleClose"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors"
            >
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>
        </div>

        <!-- Search Results -->
        <div class="max-h-[calc(100vh-200px)] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-transparent">
          <!-- Loading State -->
          <div v-if="isSearching" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-red-600"></div>
          </div>

          <!-- Results -->
          <div v-else-if="searchResults.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div
              v-for="item in searchResults"
              :key="item.id"
              class="group relative cursor-pointer transform transition-transform hover:scale-105"
            >
              <div class="relative aspect-[2/3] rounded-lg overflow-hidden bg-gray-800">
                <img
                  :src="item.image"
                  :alt="item.title"
                  class="w-full h-full object-cover"
                  @error="(e) => (e.target as HTMLImageElement).src = 'https://via.placeholder.com/300x450?text=No+Image'"
                />

                <!-- Favorite Button -->
                <button
                  @click.stop="toggleFavorite(item)"
                  class="absolute top-2 right-2 bg-black/60 hover:bg-black/80 backdrop-blur-sm p-2 rounded-full transition-all z-10"
                >
                  <component :is="isItemFavorite(item.id) ? CheckIcon : PlusIcon" class="w-4 h-4 text-white" />
                </button>

                <!-- Title -->
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 to-transparent p-3">
                  <h3 class="text-xs font-bold text-white line-clamp-2">{{ item.title }}</h3>
                </div>

                <!-- Play Overlay -->
                <div
                  @click="handlePlay(item)"
                  class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity"
                >
                  <div class="bg-red-600 rounded-full p-3">
                    <PlayIcon class="w-8 h-8 text-white" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- No Results -->
          <div v-else-if="searchQuery.trim()" class="text-center py-12">
            <MagnifyingGlassIcon class="w-16 h-16 text-gray-600 mx-auto mb-4" />
            <p class="text-gray-400 text-lg">Nenhum resultado encontrado</p>
            <p class="text-gray-500 text-sm mt-2">Tente buscar com outros termos</p>
          </div>

          <!-- Initial State -->
          <div v-else class="text-center py-12">
            <MagnifyingGlassIcon class="w-16 h-16 text-gray-600 mx-auto mb-4" />
            <p class="text-gray-400 text-lg">Digite para buscar</p>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Custom scrollbar */
.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background: #374151;
  border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: #4b5563;
}
</style>
