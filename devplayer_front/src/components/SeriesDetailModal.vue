<template>
  <Teleport to="body">
    <Transition
      name="modal-fade"
      @enter="onEnter"
      @leave="onLeave"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[9999] flex items-center justify-center"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black/80 backdrop-blur-sm"
          @click="close"
        />

        <!-- Modal Content -->
        <div
          class="relative z-10 w-full max-w-4xl max-h-[90vh] mx-4 bg-neutral-900 rounded-lg overflow-hidden shadow-2xl"
          @click.stop
        >
          <!-- Close Button -->
          <button
            @click="close"
            class="absolute top-4 right-4 z-20 bg-black/60 hover:bg-red-600 p-2 rounded-full transition-colors duration-200"
            title="Fechar"
          >
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Loading State -->
          <div v-if="loading" class="flex items-center justify-center h-[90vh]">
            <div class="flex flex-col items-center gap-4">
              <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-red-600"></div>
              <p class="text-gray-300 text-sm">Carregando informações da série...</p>
            </div>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="flex items-center justify-center h-[90vh]">
            <div class="flex flex-col items-center gap-4 px-6 text-center max-w-sm">
              <div class="text-red-500 text-4xl">⚠️</div>
              <h3 class="text-xl font-bold text-white">Erro ao carregar</h3>
              <p class="text-gray-300 text-sm">{{ error }}</p>
              <button
                @click="close"
                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
              >
                Fechar
              </button>
            </div>
          </div>

          <!-- Scrollable Content -->
          <div v-else class="overflow-y-auto max-h-[90vh]">
            <!-- Poster/Backdrop -->
            <div
              v-if="series?.cover || series?.backdrop || series?.backdrop_path || series?.poster"
              class="relative w-full h-64 md:h-96 overflow-hidden"
            >
              <img
                :src="series!.cover || series!.backdrop || series!.backdrop_path || series!.poster"
                :alt="series!.name"
                class="w-full h-full object-cover"
              />
              <!-- Gradient Overlay -->
              <div class="absolute inset-0 bg-gradient-to-t from-neutral-900 via-transparent to-transparent" />
            </div>

            <!-- Series Info -->
            <div v-if="series" class="px-6 md:px-8 py-6 space-y-4">
              <!-- Title -->
              <div>
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                  {{ series.name }}
                </h1>
                <div
                  v-if="series.release_year || seasons.length"
                  class="flex flex-wrap gap-4 text-sm text-gray-400"
                >
                  <span v-if="series.release_year" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z" />
                    </svg>
                    {{ series.release_year }}
                  </span>
                  <span v-if="seasons.length" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a1 1 0 001 1h12a1 1 0 001-1V6a2 2 0 00-2-2H4zm0 4v4a2 2 0 002 2h8a2 2 0 002-2V8H4z" clip-rule="evenodd" />
                    </svg>
                    {{ seasons.length }} {{ seasons.length === 1 ? 'Temporada' : 'Temporadas' }}
                  </span>
                </div>
              </div>

              <!-- Rating -->
              <div v-if="series.rating" class="flex items-center gap-2">
                <div class="flex items-center gap-1">
                  <span v-for="i in 5" :key="i" class="text-yellow-400">
                    <svg
                      :class="i <= Math.round((typeof series.rating === 'string' ? parseFloat(series.rating) : series.rating) / 2) ? 'fill-current' : 'fill-gray-600'"
                      class="w-4 h-4"
                      viewBox="0 0 20 20"
                    >
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  </span>
                </div>
                <span class="text-gray-400 text-sm">{{ series.rating }}/10</span>
              </div>

              <!-- Genre -->
              <div v-if="series.genre" class="flex flex-wrap gap-2">
                <span
                  v-for="genre in formatGenre(series.genre)"
                  :key="genre"
                  class="px-3 py-1 bg-red-600/20 text-red-400 rounded-full text-sm font-medium"
                >
                  {{ genre }}
                </span>
              </div>

              <!-- Description -->
              <div
                v-if="series.description || series.plot"
                class="pt-4 border-t border-gray-700"
              >
                <p class="text-gray-300 leading-relaxed text-sm md:text-base">
                  {{ series.description || series.plot }}
                </p>
              </div>

              <!-- Meta Info -->
              <div v-if="series.director || series.actors" class="pt-4 border-t border-gray-700 space-y-3 text-sm">
                <div v-if="series.director">
                  <p class="text-gray-500 font-semibold mb-1">Diretor</p>
                  <p class="text-gray-300">{{ series.director }}</p>
                </div>
                <div v-if="series.actors">
                  <p class="text-gray-500 font-semibold mb-1">Elenco</p>
                  <p class="text-gray-300">{{ series.actors }}</p>
                </div>
              </div>

              <!-- Category -->
              <div v-if="series.category" class="pt-4 border-t border-gray-700">
                <p class="text-gray-500 font-semibold mb-1 text-sm">Categoria</p>
                <p class="text-gray-300 text-sm">{{ series.category.name }}</p>
              </div>

              <!-- Actions -->
              <div class="pt-6 border-t border-gray-700 flex gap-4">
                <button
                  @click="playSeries"
                  class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition-colors duration-200"
                >
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                  </svg>
                  Assistir
                </button>
                <button
                  @click="toggleFavorite"
                  :class="[
                    'flex-1 font-bold py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition-colors duration-200',
                    isFavorite
                      ? 'bg-red-600/20 text-red-400 hover:bg-red-600/30'
                      : 'bg-gray-700/50 text-gray-300 hover:bg-gray-600/50'
                  ]"
                >
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" />
                  </svg>
                  {{ isFavorite ? 'Favorito' : 'Adicionar' }}
                </button>
              </div>

              <!-- Episodes Section -->
              <div v-if="seasons.length > 0" class="pt-8 border-t border-gray-700">
                <h2 class="text-2xl font-bold text-white mb-4">
                  Episódios
                </h2>

                <!-- Season Selector -->
                <div class="mb-6 flex gap-2 overflow-x-auto pb-2">
                  <button
                    v-for="season in seasons"
                    :key="season.season"
                    @click="selectedSeason = season.season"
                    :class="[
                      'px-4 py-2 rounded-lg font-semibold whitespace-nowrap transition-colors',
                      selectedSeason === season.season
                        ? 'bg-red-600 text-white'
                        : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                    ]"
                  >
                    Temporada {{ season.season }}
                  </button>
                </div>

                <!-- Episodes List -->
                <div v-if="currentSeasonEpisodes" class="space-y-3 max-h-96 overflow-y-auto pr-2">
                  <div
                    v-for="episode in currentSeasonEpisodes.episodes"
                    :key="episode.uuid"
                    @click="playEpisode(episode)"
                    class="p-4 bg-gray-800/50 hover:bg-gray-700/50 rounded-lg cursor-pointer transition-colors duration-200 flex gap-4 group"
                  >
                    <!-- Episode Thumbnail -->
                    <div
                      v-if="episode.thumbnail || episode.cover"
                      class="flex-shrink-0 w-24 h-16 rounded overflow-hidden bg-gray-900"
                    >
                      <img
                        :src="episode.thumbnail || episode.cover"
                        :alt="episode.title"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200"
                      />
                    </div>

                    <!-- Episode Info -->
                    <div class="flex-1 min-w-0">
                      <h3 class="text-sm font-bold text-white group-hover:text-red-400 transition-colors">
                        {{ episode.title || episode.name || `S${String(episode.season).padStart(2, '0')}E${String(episode.episode).padStart(2, '0')}` }}
                      </h3>
                      <p
                        v-if="episode.description || episode.plot"
                        class="text-xs text-gray-400 mt-1 line-clamp-2"
                      >
                        {{ episode.description || episode.plot }}
                      </p>
                      <div class="flex gap-3 mt-2 text-xs text-gray-500">
                        <span v-if="episode.aired_date">{{ formatDate(episode.aired_date) }}</span>
                        <span v-if="episode.duration">{{ episode.duration }}</span>
                      </div>
                    </div>

                    <!-- Play Icon -->
                    <div class="flex-shrink-0 flex items-center justify-center">
                      <svg
                        class="w-8 h-8 text-red-600 group-hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                      </svg>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useContentStore } from '@/stores/content';

interface Episode {
  id: number | string;
  uuid?: string;
  season: number;
  episode: number;
  name?: string;
  title?: string;
  full_title?: string;
  description?: string;
  plot?: string;
  aired_date?: string;
  duration?: string | number;
  thumbnail_url?: string;
  thumbnail?: string;
  cover?: string;
  rating?: number | string;
  stream_url?: string;
  episode_id?: string | number;
  raw_data?: any;
}

interface Season {
  season: number;
  episodes: Episode[];
}

interface SeriesData {
  id: number | string;
  uuid?: string;
  name: string;
  title?: string;
  image?: string;
  type?: 'series';
  logo_url?: string;
  backdrop_url?: string;
  poster_url?: string;
  cover?: string;
  backdrop?: string;
  backdrop_path?: string;
  poster?: string;
  description?: string;
  plot?: string;
  genre?: string | string[];
  director?: string;
  actors?: string;
  cast?: string;
  rating?: number | string;
  release_year?: number;
  release_date?: string;
  category?: {
    id: number;
    uuid: string;
    name: string;
  };
  stream_url?: string;
  url?: string;
  seasons?: Season[];
}

const props = defineProps<{
  isOpen: boolean;
  series: SeriesData | null;
  loading?: boolean;
  error?: string | null;
}>();

const emit = defineEmits<{
  close: [];
  play: [SeriesData];
  playEpisode: [Episode];
}>();

const store = useContentStore();
const isFavorite = ref(false);
const selectedSeason = ref(1);

watch(
  () => props.series?.id,
  (newId) => {
    if (newId && props.series) {
      isFavorite.value = store.isFavorite(typeof newId === 'string' ? parseInt(newId, 10) : newId);
      // Select first season by default
      if (props.series?.seasons?.[0]) {
        selectedSeason.value = props.series.seasons[0].season;
      }
    }
  },
  { immediate: true }
);

const seasons = computed(() => props.series?.seasons || []);

const currentSeasonEpisodes = computed(() => {
  return seasons.value.find(s => s.season === selectedSeason.value) || null;
});

const close = () => {
  emit('close');
};

const playSeries = () => {
  emit('play', props.series!);
  close();
};

const playEpisode = (episode: Episode) => {
  emit('playEpisode', episode);
  close();
};

const toggleFavorite = () => {
  if (props.series) {
    const numericId = typeof props.series.id === 'string' ? parseInt(props.series.id, 10) : props.series.id;
    const numericRating = props.series.rating ? (typeof props.series.rating === 'string' ? parseFloat(props.series.rating) : props.series.rating) : undefined;
    const contentItem = {
      id: numericId,
      uuid: props.series.uuid,
      title: props.series.name,
      image: props.series.logo_url || props.series.poster_url || '',
      type: 'series' as const,
      url: props.series.stream_url,
      description: props.series.description || props.series.plot,
      rating: numericRating,
    };
    store.toggleFavorite(contentItem);
    isFavorite.value = !isFavorite.value;
  }
};

const formatGenre = (genre: string | string[] | undefined): string[] => {
  if (!genre) return [];
  if (Array.isArray(genre)) return genre;
  if (typeof genre === 'string') {
    return genre.split(/[,|]/).map(g => g.trim()).filter(Boolean);
  }
  return [];
};

const formatDate = (dateStr: string): string => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('pt-BR');
};

const onEnter = (el: Element) => {
  (el as HTMLElement).classList.add('modal-entering');
};

const onLeave = (el: Element) => {
  (el as HTMLElement).classList.add('modal-leaving');
};
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 300ms ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-entering :deep(.relative) {
  animation: slideUp 300ms ease-out;
}

.modal-leaving :deep(.relative) {
  animation: slideDown 300ms ease-in;
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@keyframes slideDown {
  from {
    transform: translateY(0);
    opacity: 1;
  }
  to {
    transform: translateY(20px);
    opacity: 0;
  }
}
</style>
