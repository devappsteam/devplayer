<script setup lang="ts">
import { TvIcon, FilmIcon, MagnifyingGlassIcon, BellIcon } from '@heroicons/vue/24/outline';
import ProfileDropdown from '@/components/ProfileDropdown.vue';

const emit = defineEmits<{
  (e: 'openSearch'): void;
}>();

const links = [
    { name: 'Canais', icon: TvIcon, to: '/canais' },
    { name: 'Filmes', icon: FilmIcon, to: '/filmes' },
    { name: 'Séries', icon: FilmIcon, to: '/series' },
];
</script>

<template>
  <header
    class="fixed top-0 w-full z-50 transition-all duration-300 ease-in-out px-4 md:px-12 py-3 flex items-center justify-between bg-gray-950/90 backdrop-blur-md shadow-lg"
  >
    <!-- Logo -->
    <div class="flex items-center">
        <img src="/logo.png" alt="DevPlayer Logo" class="h-16 w-auto mr-4" />
    </div>

    <!-- Center Nav -->
    <nav class="flex space-x-8 md:space-x-12 text-base md:text-lg font-semibold text-gray-200">
        <router-link
            v-for="link in links"
            :key="link.name"
            :to="link.to"
            class="hover:text-white transition-colors relative group cursor-pointer"
            active-class="text-white"
        >
            {{ link.name }}
            <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-red-600 transition-all group-hover:w-full"></span>
        </router-link>
    </nav>

    <!-- Right Actions -->
    <div class="flex items-center space-x-4 md:space-x-6 text-gray-200">
        <button
          @click="emit('openSearch')"
          class="hover:text-white transition transform hover:scale-110 cursor-pointer"
        >
            <MagnifyingGlassIcon class="w-6 h-6" />
        </button>
        <button class="hover:text-white transition transform hover:scale-110 hidden md:block relative cursor-pointer">
            <BellIcon class="w-6 h-6" />
            <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
        </button>
        <ProfileDropdown />
    </div>
  </header>
</template>
