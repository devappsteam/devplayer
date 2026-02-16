<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import Hero from '@/components/Hero.vue';
import ContentRow from '@/components/ContentRow.vue';
import VideoPlayer from '@/components/VideoPlayer.vue';
import SearchBar from '@/components/SearchBar.vue';
import { XMarkIcon, ArrowPathIcon } from '@heroicons/vue/24/solid';
import { useContentStore } from '@/stores/content';
import { useSearch } from '@/composables/useSearch';
import type { ContentItem } from '@/stores/content';

const API_BASE_URL = 'http://localhost:8000/api/v1';
const store = useContentStore();
const { isSearchOpen, closeSearch } = useSearch();

const loading = ref(true);
const error = ref<string | null>(null);
const featured = ref<ContentItem | null>(null);
const categories = ref<any[]>([]);
const favorites = ref<ContentItem[]>([]);

// Player State
const isPlayerOpen = ref(false);
const activeContent = ref<any>(null);
const playerKey = ref(0);

const mapChannelToItem = (channel: any): ContentItem => {
  return {
    id: channel.id,
    uuid: channel.uuid,
    type: 'channel',
    title: channel.name || 'Sem Título',
    description: channel.description || '',
    image: channel.logo_url || channel.poster || 'https://via.placeholder.com/300x450?text=Sem+Imagem',
    url: channel.stream_url || channel.url || '',
    rating: channel.rating || 0,
  };
};

const loadingMore = ref(false);
const currentCategoryIndex = ref(0);
const allCategories = ref<any[]>([]);

const fetchContent = async () => {
  loading.value = true;
  error.value = null;

  try {
    // Buscar canais ao vivo
    const liveRes = await fetch(`${API_BASE_URL}/channels/live?per_page=15`);
    const categoriesRes = await fetch(`${API_BASE_URL}/categories?type=live&per_page=100`);

    const nextCategories: any[] = [];
    let lastWatchedItem: any = null;

    if (liveRes.ok) {
      const data = await liveRes.json();
      const items = (data.data || []).map((c: any) => mapChannelToItem(c));

      if (items.length > 0) {
        // Tentar obter o ID do último assistido
        try {
          const lastRes = await fetch(`${API_BASE_URL}/history/user/1/last/channel`);
          if (lastRes.ok) {
            const lastData = await lastRes.json();
            if (lastData.data?.id) {
              // Procurar o canal na lista já carregada usando ID numérico
              lastWatchedItem = items.find((item: any) => item.id === lastData.data.id);
            }
          }
        } catch (histErr) {
          console.debug('Could not fetch last watched:', histErr);
        }

        // Usar último assistido como featured, ou o primeiro
        featured.value = lastWatchedItem || items[0];

        // Adicionar categoria de canais ao vivo
        nextCategories.push({
          id: 'live',
          title: 'Canais ao Vivo',
          items: items.slice(0, 15),
          hasMore: items.length > 15,
          currentPage: 1,
          totalPages: items.length > 15 ? Math.ceil(items.length / 15) : 1
        });
      }
    }

    // Buscar favoritos
    const favRes = await fetch(`${API_BASE_URL}/favorites/user/1?type=live`);
    if (favRes.ok) {
      const favData = await favRes.json();
      favorites.value = (favData.data || []).map((c: any) => mapChannelToItem(c));
      // Sincronizar com a store
      store.favorites.splice(0, store.favorites.length, ...favorites.value);
    }

    // Buscar categorias
    if (categoriesRes.ok) {
      const catData = await categoriesRes.json();
      allCategories.value = catData.data || [];

      // Carregar apenas 5 PRIMEIRAS categorias (15 itens inicialmente cada)
      const initialCategories = allCategories.value.slice(0, 5);
      for (const cat of initialCategories) {
        const catChannelsRes = await fetch(`${API_BASE_URL}/channels?category_uuid=${cat.uuid}&per_page=15`);
        if (catChannelsRes.ok) {
          const catChannelsData = await catChannelsRes.json();
          const catItems = (catChannelsData.data || []).map((c: any) => mapChannelToItem(c));

          if (catItems.length > 0) {
            nextCategories.push({
              id: cat.uuid,
              title: cat.name,
              items: catItems,
              hasMore: catChannelsData.meta && 1 < catChannelsData.meta.last_page,
              currentPage: 1,
              totalPages: catChannelsData.meta?.last_page || 1
            });
          }
        }
      }
      currentCategoryIndex.value = 5;
    }

    // Adicionar favoritos se existir
    if (favorites.value.length > 0) {
      nextCategories.unshift({
        id: 'favorites',
        title: 'Minha Lista',
        items: favorites.value,
        hasMore: false
      });
    }

    categories.value = nextCategories;
  } catch (err) {
    error.value = 'Erro ao carregar canais. Tente novamente.';
    console.error('Error fetching channels:', err);
  } finally {
    loading.value = false;
  }
};

const openPlayer = (item: any) => {
  activeContent.value = item;
  isPlayerOpen.value = true;
  playerKey.value++;
  document.body.style.overflow = 'hidden';
  document.documentElement.classList.add('player-open');

  // Adicionar ao histórico e atualizar featured
  addToHistory(item.id, 'channel').then(() => {
    featured.value = item;
  });
};

const addToHistory = async (channelId: string, contentType: string): Promise<void> => {
  try {
    const response = await fetch(`${API_BASE_URL}/history/user/1`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ channel_id: channelId, content_type: contentType })
    });

    if (!response.ok) {
      console.error('Error adding to history:', response.statusText);
    }
  } catch (err) {
    console.error('Error adding to history:', err);
  }
};

const closePlayer = () => {
  isPlayerOpen.value = false;
  activeContent.value = null;
  document.body.style.overflow = '';
  document.documentElement.classList.remove('player-open');
};

const refreshPlayer = () => {
  playerKey.value++;
};

// Navigate favorites
const goToNextFavorite = () => {
  if (favorites.value.length === 0) return;
  const currentId = activeContent.value?.id;
  const currentIndex = favorites.value.findIndex(f => f.id === currentId);
  const nextIndex = (currentIndex + 1) % favorites.value.length;
  activeContent.value = favorites.value[nextIndex];
  playerKey.value++;
};

const goToPreviousFavorite = () => {
  if (favorites.value.length === 0) return;
  const currentId = activeContent.value?.id;
  const currentIndex = favorites.value.findIndex(f => f.id === currentId);
  const prevIndex = currentIndex === 0 ? favorites.value.length - 1 : currentIndex - 1;
  activeContent.value = favorites.value[prevIndex];
  playerKey.value++;
};

const getNextFavorite = () => {
  if (favorites.value.length === 0) return null;
  const currentId = activeContent.value?.id;
  const currentIndex = favorites.value.findIndex(f => f.id === currentId);
  return favorites.value[(currentIndex + 1) % favorites.value.length] || null;
};

const getPreviousFavorite = () => {
  if (favorites.value.length === 0) return null;
  const currentId = activeContent.value?.id;
  const currentIndex = favorites.value.findIndex(f => f.id === currentId);
  return favorites.value[currentIndex === 0 ? favorites.value.length - 1 : currentIndex - 1] || null;
};

const shouldShowNavigation = computed(() => favorites.value.length >= 3);

const loadMoreItemsInCategory = async (categoryId: string) => {
  const categoryIndex = categories.value.findIndex((cat) => cat.id === categoryId);
  if (categoryIndex === -1) return;

  const category = categories.value[categoryIndex];
  if (!category || !category.hasMore || !category.currentPage) return;

  const nextPage = category.currentPage + 1;

  try {
    const response = await fetch(`${API_BASE_URL}/channels?category_uuid=${categoryId}&page=${nextPage}&per_page=15`);
    if (!response.ok) return;

    const result = await response.json();
    const newItems = (result.data || []).map((c: any) => mapChannelToItem(c));

    const updatedCategory = categories.value[categoryIndex];
    if (updatedCategory) {
      updatedCategory.items.push(...newItems);
      updatedCategory.currentPage = nextPage;
      updatedCategory.hasMore = result.meta && nextPage < result.meta.last_page;
      updatedCategory.totalPages = result.meta?.last_page || updatedCategory.totalPages;
    }
  } catch (err) {
    console.error(`Error loading more items for category ${categoryId}:`, err);
  }
};

const loadMoreCategories = async () => {
  if (loadingMore.value || currentCategoryIndex.value >= allCategories.value.length) return;

  loadingMore.value = true;
  const nextBatch = allCategories.value.slice(currentCategoryIndex.value, currentCategoryIndex.value + 10);

  for (const cat of nextBatch) {
    try {
      const catChannelsRes = await fetch(`${API_BASE_URL}/channels?category_uuid=${cat.uuid}&per_page=15`);
      if (catChannelsRes.ok) {
        const catChannelsData = await catChannelsRes.json();
        const catItems = (catChannelsData.data || []).map((c: any) => mapChannelToItem(c));

        if (catItems.length > 0) {
          categories.value.push({
            id: cat.uuid,
            title: cat.name,
            items: catItems,
            hasMore: catChannelsData.meta && 1 < catChannelsData.meta.last_page,
            currentPage: 1,
            totalPages: catChannelsData.meta?.last_page || 1
          });
        }
      }
    } catch (err) {
      console.error(`Error loading category ${cat.name}:`, err);
    }
  }

  currentCategoryIndex.value += 10;
  loadingMore.value = false;
};

const handleScroll = () => {
  const scrollHeight = document.documentElement.scrollHeight;
  const scrollTop = document.documentElement.scrollTop;
  const clientHeight = document.documentElement.clientHeight;

  if (scrollTop + clientHeight >= scrollHeight - 500) {
    loadMoreCategories();
  }
};

onMounted(() => {
  fetchContent();
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
  <div>
    <!-- Loading State -->
    <div v-if="loading" class="min-h-screen flex items-center justify-center">
      <div class="text-center space-y-4">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-red-600 mx-auto"></div>
        <p class="text-gray-400 text-lg">Carregando canais...</p>
      </div>
    </div>

    <!-- Content Loaded -->
    <template v-else>
      <!-- Hero Section -->
      <Hero
        v-if="featured"
        :item="featured"
        @play="openPlayer"
        class="mb-8"
      />

      <!-- Content Rows -->
      <div class="relative z-10 px-4 md:px-12 -mt-24 md:-mt-32 space-y-8 pb-20">
        <ContentRow
          v-for="category in categories"
          :key="category.id"
          :category-id="category.id"
          :title="category.title"
          :items="category.items"
          :has-more="category.hasMore || false"
          :load-more-callback="loadMoreItemsInCategory"
          @play="openPlayer"
        />
      </div>

      <!-- Loading More Categories -->
      <div v-if="loadingMore" class="flex items-center justify-center py-12">
        <div class="text-center space-y-4">
          <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-red-600 mx-auto"></div>
          <p class="text-gray-400 text-sm">Carregando mais categorias...</p>
        </div>
      </div>
    </template>

    <!-- Error State -->
    <div v-if="error" class="min-h-[50vh] flex items-center justify-center px-4">
      <div class="text-center space-y-4 max-w-md">
        <div class="text-red-500 text-5xl">⚠️</div>
        <h3 class="text-2xl font-bold text-white">Erro ao Carregar</h3>
        <p class="text-gray-400">{{ error }}</p>
        <button
          @click="fetchContent"
          class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded text-white font-semibold transition cursor-pointer"
        >
          Tentar Novamente
        </button>
      </div>
    </div>

    <!-- Video Player Modal -->
    <Transition name="fade">
      <div
        v-if="isPlayerOpen"
        class="fixed inset-0 z-99999 bg-black flex flex-col"
      >
        <!-- Top Controls -->
        <div class="absolute top-4 right-4 z-100000 flex gap-2">
          <!-- Refresh Button -->
          <button
            @click="refreshPlayer"
            class="p-2 bg-black/70 hover:bg-blue-600 rounded-full transition-colors cursor-pointer"
            title="Atualizar reprodutor"
          >
            <ArrowPathIcon class="w-8 h-8 text-white" />
          </button>

          <!-- Close Button -->
          <button
            @click="closePlayer"
            class="p-2 bg-black/70 hover:bg-red-600 rounded-full transition-colors cursor-pointer"
            title="Fechar"
          >
            <XMarkIcon class="w-8 h-8 text-white" />
          </button>
        </div>

        <!-- Video Player -->
        <div class="flex-1 relative">
          <VideoPlayer
            v-if="activeContent?.url"
            :key="playerKey"
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
    </Transition>

    <!-- Search Bar -->
    <SearchBar
      :is-open="isSearchOpen"
      stream-type="live"
      @close="closeSearch"
      @play="openPlayer"
    />
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

<style scoped>
/* Estilos adicionais se necessário */
</style>
