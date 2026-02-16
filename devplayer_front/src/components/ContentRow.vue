<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon, CheckIcon } from '@heroicons/vue/24/solid';
import { useContentStore } from '@/stores/content';
import type { ContentItem } from '@/stores/content';

const props = defineProps<{
  categoryId: string;
  title: string;
  items: any[];
  hasMore?: boolean;
  loadMoreCallback?: (categoryId: string) => Promise<void>;
}>();

const emit = defineEmits(['play']);
const store = useContentStore();
const scrollContainer = ref<HTMLElement | null>(null);
const loadingMore = ref(false);
const canScrollLeft = ref(false);
const canScrollRight = ref(true);

// Computed para rastrear IDs de favoritos (otimiza performance)
const favoriteIds = computed(() => new Set(store.favorites.map(f => f.id)));

const isItemFavorite = (itemId: number): boolean => {
  return favoriteIds.value.has(itemId);
};

const extractBadges = (title?: string): string[] => {
  if (!title) return [];

  const badges: string[] = [];
  const upperTitle = title.toUpperCase();

  // Detectar badges entre colchetes [H265], [L], [4K]
  const bracketMatches = title.match(/\[([A-Z0-9]+)\]/gi);
  if (bracketMatches) {
    bracketMatches.forEach(match => {
      const badge = match.replace(/[\[\]]/g, '').toUpperCase();
      badges.push(badge);
    });
  }

  // Detectar qualidades: FHD, HD, SD (ordem importante: FHD antes de HD)
  if (upperTitle.includes('FHD')) badges.push('FHD');
  else if (upperTitle.includes('HD')) badges.push('HD');
  else if (upperTitle.includes('SD')) badges.push('SD');

  return Array.from(new Set(badges)); // Remove duplicados
};

const normalizeTitle = (title?: string): string => {
  if (!title) return 'Sem Título';

  let normalized = title;

  // Remover badges entre colchetes
  normalized = normalized.replace(/\[([A-Z0-9]+)\]/gi, '');

  // Remover qualidades soltas
  normalized = normalized.replace(/\bFHD\b/gi, '');
  normalized = normalized.replace(/\bHD\b/gi, '');
  normalized = normalized.replace(/\bSD\b/gi, '');

  // Limpar espaços múltiplos e trimmar
  normalized = normalized.replace(/\s+/g, ' ').trim();

  return normalized;
};

const updateScrollButtons = () => {
  if (!scrollContainer.value) return;

  const { scrollLeft, scrollWidth, clientWidth } = scrollContainer.value;
  canScrollLeft.value = scrollLeft > 0;

  // Pode scrollar para direita se ainda não chegou ao fim OU se tem mais itens para carregar
  const isAtEnd = scrollLeft + clientWidth >= scrollWidth - 10;
  canScrollRight.value = !isAtEnd || (props.hasMore === true);
};

const scroll = (direction: 'left' | 'right') => {
  if (scrollContainer.value) {
    const { scrollLeft, clientWidth } = scrollContainer.value;
    const scrollTo = direction === 'left' ? scrollLeft - clientWidth : scrollLeft + clientWidth;
    scrollContainer.value.scrollTo({ left: scrollTo, behavior: 'smooth' });
    setTimeout(updateScrollButtons, 300);
  }
};

const handleScroll = async () => {
  updateScrollButtons();

  if (!scrollContainer.value || loadingMore.value || !props.hasMore) return;

  const { scrollLeft, scrollWidth, clientWidth } = scrollContainer.value;

  if (scrollLeft + clientWidth >= scrollWidth - 100) {
    loadingMore.value = true;

    // Use callback custom se disponível, senão use a função da store
    if (props.loadMoreCallback) {
      await props.loadMoreCallback(props.categoryId);
    } else {
      await store.loadMoreItems(props.categoryId);
    }

    loadingMore.value = false;
    setTimeout(updateScrollButtons, 100);
  }
};

onMounted(() => {
  scrollContainer.value?.addEventListener('scroll', handleScroll);
  setTimeout(updateScrollButtons, 100);
});

onUnmounted(() => {
  scrollContainer.value?.removeEventListener('scroll', handleScroll);
});
</script>

<template>
  <div class="mb-8 px-4 md:px-12 group/row relative z-10">
    <h2 class="text-xl md:text-2xl font-bold mb-4 text-white font-display group-hover/row:text-red-500 transition-colors duration-300 pl-2 border-l-4 border-transparent group-hover/row:border-red-600">
      {{ title }}
    </h2>

    <div class="relative group">
       <!-- Scroll Left Button -->
       <button
         v-if="canScrollLeft"
         class="absolute left-4 top-1/2 -translate-y-1/2 z-9999 bg-red-600 hover:bg-red-700 text-white p-2 hidden group-hover:flex items-center justify-center transition-all rounded-full opacity-0 group-hover:opacity-100 hover:scale-110 w-10 h-10 cursor-pointer"
         @click="scroll('left')"
       >
         <ChevronLeftIcon class="h-6 w-6" />
       </button>

       <!-- Scroll Container -->
       <div
         ref="scrollContainer"
         class="flex space-x-4 overflow-x-auto scrollbar-hide pb-8 pt-4 px-2 snap-x snap-mandatory"
         style="scroll-behavior: smooth;"
       >
         <div
           v-for="item in items"
           :key="`item-${item.id}-${categoryId}`"
           class="flex-none w-[160px] md:w-[220px] snap-center transform transition-transform duration-300 hover:scale-110 hover:z-9999 cursor-pointer group/card"
         >
           <div class="relative aspect-[2/3] md:aspect-video rounded-md overflow-hidden shadow-lg bg-gray-800">
             <img
               :src="item.image"
               :alt="item.title"
               loading="lazy"
               class="w-full h-full object-cover transition-opacity duration-300 group-hover/card:opacity-80"
             />

             <!-- Favorite Button (Top Right) - Always Visible - 30% menor -->
             <button
               @click.stop="store.toggleFavorite(item)"
               :class="[
                 'absolute top-2 right-2 z-9999 w-6 h-6 md:w-7 md:h-7 rounded-full flex items-center justify-center transition-all border-2 cursor-pointer',
                 isItemFavorite(item.id)
                   ? 'bg-red-600 border-red-600 hover:bg-red-700 hover:border-red-700'
                   : 'bg-black/80 border-white/60 hover:bg-red-600 hover:border-red-600'
               ]"
               :title="isItemFavorite(item.id) ? 'Remover dos favoritos' : 'Adicionar aos favoritos'"
             >
               <component :is="isItemFavorite(item.id) ? CheckIcon : PlusIcon" class="w-3.5 h-3.5 md:w-4 md:h-4 text-white" />
             </button>

             <!-- Always Visible Title & Quality Badges -->
             <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-3">
                <h3 class="text-xs md:text-sm font-bold text-white mb-1.5 line-clamp-2">{{ normalizeTitle(item.title) }}</h3>
                <div v-if="extractBadges(item.title).length > 0" class="flex flex-wrap gap-1">
                   <span
                     v-for="badge in extractBadges(item.title)"
                     :key="badge"
                     class="border border-white/70 px-1.5 py-0.5 rounded text-[10px] md:text-xs font-semibold text-white"
                   >
                     {{ badge }}
                   </span>
                </div>
             </div>

             <!-- Hover Overlay with Play Button -->
             <div
               @click="emit('play', item)"
               class="absolute inset-0 bg-black/60 opacity-0 group-hover/card:opacity-100 flex flex-col justify-center items-center transition-opacity duration-300"
             >
                <div class="text-red-500">
                   <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" viewBox="0 0 20 20" fill="currentColor">
                     <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                   </svg>
                </div>
             </div>
           </div>
         </div>

         <!-- Loading More Items -->
         <div v-if="loadingMore" class="flex-none w-[220px] flex items-center justify-center">
           <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-red-600"></div>
         </div>
       </div>

       <!-- Scroll Right Button -->
       <button
         v-if="canScrollRight"
         class="absolute right-4 top-1/2 -translate-y-1/2 z-9999 bg-red-600 hover:bg-red-700 text-white p-2 hidden group-hover:flex items-center justify-center transition-all rounded-full opacity-0 group-hover:opacity-100 hover:scale-110 w-10 h-10 cursor-pointer"
         @click="scroll('right')"
       >
         <ChevronRightIcon class="h-6 w-6" />
       </button>
    </div>
  </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
