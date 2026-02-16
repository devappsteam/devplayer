<script setup lang="ts">
import { ref } from 'vue';
import { ChevronDownIcon, XMarkIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { usePWA } from '@/composables/usePWA';

const { isInstallPromptReady, install, isOnline } = usePWA();
const showPrompt = ref(false);
const showOfflineNotice = ref(false);

const handleInstall = async () => {
  await install();
  showPrompt.value = false;
};

const toggleOfflineNotice = () => {
  showOfflineNotice.value = !showOfflineNotice.value;
};
</script>

<template>
  <div>
    <!-- Install Prompt -->
    <Teleport to="body">
      <transition
        enter-active-class="transition-all duration-300"
        leave-active-class="transition-all duration-300"
        enter-from-class="translate-y-full opacity-0"
        leave-to-class="translate-y-full opacity-0"
      >
        <div
          v-if="isInstallPromptReady && showPrompt"
          class="fixed bottom-0 left-0 right-0 bg-gradient-to-t from-gray-900 to-gray-800 border-t border-gray-700 p-4 shadow-xl z-40"
        >
          <div class="max-w-md mx-auto">
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-3">
                <ArrowDownTrayIcon class="w-6 h-6 text-blue-400" />
                <div>
                  <h3 class="text-white font-semibold text-sm">Instalar DevPlayer</h3>
                  <p class="text-gray-400 text-xs">Acesso rápido na sua tela inicial</p>
                </div>
              </div>
              <button
                @click="showPrompt = false"
                class="text-gray-400 hover:text-white transition"
              >
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <div class="flex gap-2">
              <button
                @click="showPrompt = false"
                class="flex-1 px-4 py-2 rounded bg-gray-700 hover:bg-gray-600 text-white text-sm font-medium transition"
              >
                Agora não
              </button>
              <button
                @click="handleInstall"
                class="flex-1 px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition"
              >
                Instalar
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Install Button (if prompt not shown and available) -->
    <button
      v-if="isInstallPromptReady && !showPrompt"
      @click="showPrompt = true"
      class="fixed bottom-4 right-4 p-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg transition z-30"
      title="Instalar DevPlayer"
    >
      <ArrowDownTrayIcon class="w-6 h-6" />
    </button>

    <!-- Offline Notice -->
    <transition
      enter-active-class="transition-all duration-300"
      leave-active-class="transition-all duration-300"
      enter-from-class="translate-x-full opacity-0"
      leave-to-class="translate-x-full opacity-0"
    >
      <div
        v-if="!isOnline"
        class="fixed top-4 right-4 max-w-sm bg-yellow-900/80 backdrop-blur-sm border border-yellow-700 rounded-lg p-4 shadow-xl z-40"
      >
        <div class="flex items-start gap-3">
          <div class="flex-1">
            <p class="text-white font-semibold text-sm">Você está offline</p>
            <p class="text-yellow-100 text-xs mt-1">
              Algumas funcionalidades podem estar limitadas. Os dados serão sincronizados quando voltar online.
            </p>
          </div>
          <button
            @click="toggleOfflineNotice"
            class="text-yellow-400 hover:text-yellow-300 transition"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>
