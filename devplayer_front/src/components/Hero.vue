<script setup lang="ts">
import { computed } from 'vue';
import { PlayIcon, PlusIcon, CheckIcon } from '@heroicons/vue/24/solid';
import type { ContentItem } from '@/stores/content';
import { useContentStore } from '@/stores/content';

const props = defineProps<{
  item: ContentItem;
}>();

const emit = defineEmits(['play']);
const store = useContentStore();

// Debug: Log the item when received
console.log('Hero component received item:', JSON.stringify(props.item, null, 2));

// Computed para reatividade otimizada
const isItemFavorite = computed(() => store.isFavorite(props.item.id));

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
</script>

<template>
  <div class="relative w-full h-[85vh] md:h-[90vh] overflow-hidden">
    <!-- Background Image -->
    <div class="absolute inset-0">
      <img
        :src="item.image"
        :alt="item.title"
        class="w-full h-full object-cover object-center transform scale-105"
      />
      <!-- Gradient Overlay -->
      <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40 to-transparent"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-900/60 to-transparent w-2/3"></div>
    </div>

    <!-- Content -->
    <div class="absolute inset-0 flex items-center px-4 md:px-16 pb-16">
      <div class="max-w-2xl space-y-6 animate-fade-in-up">
        <!-- Badge/Rating -->
        <div class="flex items-center flex-wrap gap-3 text-sm font-semibold text-gray-300">
             <span
               v-for="badge in extractBadges(item.title)"
               :key="badge"
               class="border border-gray-500 px-2 rounded text-xs"
             >
               {{ badge }}
             </span>
        </div>

        <!-- Title -->
        <h1 class="text-4xl md:text-6xl font-bold leading-tight font-display tracking-wide drop-shadow-2xl">
          {{ normalizeTitle(item.title) }}
        </h1>

        <!-- Description -->
        <p class="text-lg text-gray-200 line-clamp-3 md:line-clamp-4 drop-shadow-md leading-relaxed">
          {{ item.description }}
        </p>

        <!-- Actions -->
        <div class="flex items-center space-x-4 pt-4">
          <button
            @click="emit('play', item)"
            class="flex items-center space-x-2 bg-white text-black px-8 py-3 rounded hover:bg-opacity-90 transition transform hover:scale-105 font-bold text-lg shadow-lg cursor-pointer"
          >
            <PlayIcon class="w-7 h-7" />
            <span>Continuar Assistindo</span>
          </button>

          <button
            @click.stop="store.toggleFavorite(props.item)"
            :class="[
              'flex items-center space-x-2 px-6 py-3 rounded transition transform hover:scale-105 font-medium text-lg cursor-pointer',
              isItemFavorite
                ? 'bg-red-600 text-white hover:bg-red-700'
                : 'bg-gray-600/80 backdrop-blur-sm text-white hover:bg-gray-500/80'
            ]"
          >
            <component :is="isItemFavorite ? CheckIcon : PlusIcon" class="w-7 h-7" />
            <span>{{ isItemFavorite ? 'Remover' : 'Adicionar' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.8s ease-out forwards;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
