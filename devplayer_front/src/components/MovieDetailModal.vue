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
          class="relative z-10 w-full max-w-2xl max-h-[90vh] mx-4 bg-neutral-900 rounded-lg overflow-hidden shadow-2xl"
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
              <p class="text-gray-300 text-sm">Carregando informações do filme...</p>
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
              v-if="movie?.logo_url || movie?.poster_url || movie?.cover || movie?.backdrop"
              class="relative w-full h-64 md:h-96 overflow-hidden"
            >
              <img
                :src="movie.logo_url || movie.poster_url || movie.cover || movie.backdrop"
                :alt="movie.name"
                class="w-full h-full object-cover"
              />
              <!-- Gradient Overlay -->
              <div class="absolute inset-0 bg-gradient-to-t from-neutral-900 via-transparent to-transparent" />
            </div>

            <!-- Movie Info -->
            <div v-if="movie" class="px-6 md:px-8 py-6 space-y-4">
              <!-- Title -->
              <div>
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                  {{ movie.name }}
                </h1>
                <div
                  v-if="movie.release_year || movie.duration"
                  class="flex flex-wrap gap-4 text-sm text-gray-400"
                >
                  <span v-if="movie.release_year" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z" />
                    </svg>
                    {{ movie.release_year }}
                  </span>
                  <span v-if="movie.duration" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd" />
                    </svg>
                    {{ formatDuration(movie.duration) }}
                  </span>
                </div>
              </div>

              <!-- Rating -->
              <div v-if="movie?.rating" class="flex items-center gap-2">
                <div class="flex items-center gap-1">
                  <span v-for="i in 5" :key="i" class="text-yellow-400">
                    <svg
                      :class="i <= Math.round((typeof movie!.rating === 'string' ? parseFloat(movie!.rating) : movie!.rating) / 2) ? 'fill-current' : 'fill-gray-600'"
                      class="w-4 h-4"
                      viewBox="0 0 20 20"
                    >
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  </span>
                </div>
                <span class="text-gray-400 text-sm">{{ movie!.rating }}/10</span>
              </div>

              <!-- Genre -->
              <div v-if="movie?.genre" class="flex flex-wrap gap-2">
                <span
                  v-for="genre in formatGenre(movie!.genre)"
                  :key="genre"
                  class="px-3 py-1 bg-red-600/20 text-red-400 rounded-full text-sm font-medium"
                >
                  {{ genre }}
                </span>
              </div>

              <!-- Description -->
              <div
                v-if="movie?.description || movie?.plot"
                class="pt-4 border-t border-gray-700"
              >
                <p class="text-gray-300 leading-relaxed text-sm md:text-base">
                  {{ movie!.description || movie!.plot }}
                </p>
              </div>

              <!-- Meta Info -->
              <div v-if="movie?.director || movie?.actors" class="pt-4 border-t border-gray-700 space-y-3 text-sm">
                <div v-if="movie?.director">
                  <p class="text-gray-500 font-semibold mb-1">Diretor</p>
                  <p class="text-gray-300">{{ movie!.director }}</p>
                </div>
                <div v-if="movie?.actors">
                  <p class="text-gray-500 font-semibold mb-1">Elenco</p>
                  <p class="text-gray-300">{{ movie!.actors }}</p>
                </div>
              </div>

              <!-- Category -->
              <div v-if="movie?.category" class="pt-4 border-t border-gray-700">
                <p class="text-gray-500 font-semibold mb-1 text-sm">Categoria</p>
                <p class="text-gray-300 text-sm">{{ movie!.category.name }}</p>
              </div>

              <!-- Actions -->
              <div class="pt-6 border-t border-gray-700 flex gap-4">
                <button
                  @click="playMovie"
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

interface MovieData {
  id: number | string;
  uuid: string;
  name: string;
  logo_url?: string;
  backdrop_url?: string;
  poster_url?: string;
  cover?: string;
  backdrop?: string;
  description?: string;
  plot?: string;
  genre?: string | string[];
  director?: string;
  actors?: string;
  duration?: number;
  rating?: number;
  release_year?: number;
  category?: {
    id: number;
    uuid: string;
    name: string;
  };
  stream_url?: string;
}

const props = defineProps<{
  isOpen: boolean;
  movie: MovieData | null;
  loading?: boolean;
  error?: string | null;
}>();

const emit = defineEmits<{
  close: [];
  play: [any];
}>();

const store = useContentStore();
const isFavorite = ref(false);

watch(
  () => props.movie?.id,
  (newId) => {
    if (newId) {
      isFavorite.value = store.isFavorite(typeof newId === 'string' ? parseInt(newId, 10) : newId);
    }
  },
  { immediate: true }
);

const close = () => {
  emit('close');
};

const playMovie = () => {
  const movieWithUrl = {
    ...props.movie,
    title: props.movie?.name,
    description: props.movie?.description || props.movie?.plot,
    url: props.movie?.stream_url,
  };
  emit('play', movieWithUrl);
  close();
};

const toggleFavorite = () => {
  if (props.movie) {
    const numericId = typeof props.movie.id === 'string' ? parseInt(props.movie.id, 10) : props.movie.id;
    const numericRating = props.movie.rating ? (typeof props.movie.rating === 'string' ? parseFloat(props.movie.rating) : props.movie.rating) : undefined;
    const contentItem = {
      id: numericId,
      uuid: props.movie.uuid,
      title: props.movie.name,
      image: props.movie.logo_url || props.movie.poster_url || '',
      type: 'movie' as const,
      url: props.movie.stream_url,
      description: props.movie.description || props.movie.plot,
      rating: numericRating,
    };
    store.toggleFavorite(contentItem);
    isFavorite.value = !isFavorite.value;
  }
};

const formatDuration = (minutes: number): string => {
  if (!minutes) return '';
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  if (hours > 0) {
    return `${hours}h ${mins}m`;
  }
  return `${mins}m`;
};

const formatGenre = (genre: string | string[] | undefined): string[] => {
  if (!genre) return [];
  if (Array.isArray(genre)) return genre;
  if (typeof genre === 'string') {
    // Try to parse comma-separated or pipe-separated genres
    return genre.split(/[,|]/).map(g => g.trim()).filter(Boolean);
  }
  return [];
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
