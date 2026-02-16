<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import {
  UserCircleIcon,
  ArrowPathIcon,
  ArrowRightOnRectangleIcon,
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon
} from '@heroicons/vue/24/outline';

/**
 * ProfileDropdown Component - Real-time Sync Progress
 *
 * Usa o novo sistema de sincronização async com Jobs do Laravel
 * e polling para atualizar progresso em tempo real.
 */

interface SyncTypeProgress {
  type: string;
  status: 'pending' | 'processing' | 'completed' | 'failed';
  current_step: string | null;
  total_items: number;
  processed_items: number;
  progress_percentage: number;
  message: string;
  error_message: string | null;
}

interface SyncTypeStatus {
  status: 'idle' | 'syncing' | 'success' | 'error';
  message: string;
  progress?: number;
}

const isOpen = ref(false);
const isSyncing = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);
const syncId = ref<string | null>(null);
const pollInterval = ref<number | null>(null);
const router = useRouter();
const auth = useAuthStore();

const userName = computed(() => auth.user?.name || auth.user?.email || 'Perfil');

const liveSync = ref<SyncTypeStatus>({ status: 'idle', message: '', progress: 0 });
const vodSync = ref<SyncTypeStatus>({ status: 'idle', message: '', progress: 0 });
const seriesSync = ref<SyncTypeStatus>({ status: 'idle', message: '', progress: 0 });

const API_BASE_URL = 'http://localhost:8000/api/v1';
const IPTV_UUID = '1c1fd6e2-34b9-4257-866b-a3807418cf2e'; // TODO: buscar dinamicamente

const overallStatus = computed(() => {
  if (isSyncing.value) return 'syncing';
  if (liveSync.value.status === 'error' || vodSync.value.status === 'error' || seriesSync.value.status === 'error') {
    return 'error';
  }
  if (liveSync.value.status === 'success' && vodSync.value.status === 'success' && seriesSync.value.status === 'success') {
    return 'success';
  }
  return 'idle';
});

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const handleLogout = async () => {
  await auth.logout();
  isOpen.value = false;
  router.push('/login');
};

const handleClickOutside = (event: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isOpen.value = false;
  }
};

const resetStatuses = () => {
  liveSync.value = { status: 'idle', message: '', progress: 0 };
  vodSync.value = { status: 'idle', message: '', progress: 0 };
  seriesSync.value = { status: 'idle', message: '', progress: 0 };
};

const updateSyncStatus = (typeProgress: SyncTypeProgress) => {
  const targetSync = typeProgress.type === 'live' ? liveSync
    : typeProgress.type === 'vod' ? vodSync
    : seriesSync;

  if (typeProgress.status === 'completed') {
    targetSync.value = {
      status: 'success',
      message: typeProgress.message || 'Concluído',
      progress: 100
    };
  } else if (typeProgress.status === 'failed') {
    targetSync.value = {
      status: 'error',
      message: typeProgress.error_message || 'Erro ao sincronizar',
      progress: 0
    };
  } else if (typeProgress.status === 'processing') {
    targetSync.value = {
      status: 'syncing',
      message: typeProgress.message || 'Sincronizando...',
      progress: typeProgress.progress_percentage
    };
  } else {
    targetSync.value = {
      status: 'syncing',
      message: 'Aguardando...',
      progress: 0
    };
  }
};

const pollProgress = async () => {
  if (!syncId.value) return;

  try {
    const response = await fetch(`${API_BASE_URL}/sync/${syncId.value}/progress`);
    const data = await response.json();

    if (response.ok && data.success) {
      const types = data.data.types || [];

      types.forEach((typeProgress: SyncTypeProgress) => {
        updateSyncStatus(typeProgress);
      });

      // Se todos completaram ou falharam, parar polling
      const allDone = types.every((t: SyncTypeProgress) =>
        t.status === 'completed' || t.status === 'failed'
      );

      if (allDone) {
        stopPolling();
        isSyncing.value = false;

        // Recarregar página após 2 segundos se houver sucesso
        const anySuccess = types.some((t: SyncTypeProgress) => t.status === 'completed');
        if (anySuccess) {
          setTimeout(() => {
            window.location.reload();
          }, 2000);
        }

        // Limpar status após 10 segundos
        setTimeout(() => {
          resetStatuses();
        }, 10000);
      }
    }
  } catch (error) {
    console.error('Error polling progress:', error);
  }
};

const stopPolling = () => {
  if (pollInterval.value) {
    clearInterval(pollInterval.value);
    pollInterval.value = null;
  }
};

const startPolling = () => {
  stopPolling();
  pollInterval.value = window.setInterval(pollProgress, 1000); // Poll every 1 second
};

const syncIPTV = async () => {
  if (isSyncing.value) return;

  isSyncing.value = true;
  resetStatuses();

  // Iniciar todos como "aguardando"
  liveSync.value.status = 'syncing';
  liveSync.value.message = 'Iniciando...';

  vodSync.value.status = 'syncing';
  vodSync.value.message = 'Aguardando...';

  seriesSync.value.status = 'syncing';
  seriesSync.value.message = 'Aguardando...';

  try {
    const response = await fetch(`${API_BASE_URL}/iptvs/${IPTV_UUID}/sync`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    const data = await response.json();

    if (response.ok && data.success) {
      syncId.value = data.data.sync_id;

      // Iniciar polling para atualizar progresso
      startPolling();

      // Fazer primeira checagem imediatamente
      await pollProgress();
    } else {
      liveSync.value.status = 'error';
      liveSync.value.message = data.message || data.error || 'Erro ao iniciar sincronização';

      vodSync.value.status = 'error';
      vodSync.value.message = 'Cancelado';

      seriesSync.value.status = 'error';
      seriesSync.value.message = 'Cancelado';

      isSyncing.value = false;
    }
  } catch (error) {
    liveSync.value.status = 'error';
    liveSync.value.message = 'Erro de conexão';

    vodSync.value.status = 'error';
    vodSync.value.message = 'Cancelado';

    seriesSync.value.status = 'error';
    seriesSync.value.message = 'Cancelado';

    isSyncing.value = false;
    console.error('Sync error:', error);
  }
};

onMounted(() => {
  //document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  //document.removeEventListener('click', handleClickOutside);
  stopPolling();
});
</script>

<template>
  <div ref="dropdownRef" class="relative">
    <!-- Profile Button -->
    <button
      @click="toggleDropdown"
      class="flex items-center space-x-2 hover:text-white transition group cursor-pointer"
    >
      <UserCircleIcon class="w-8 h-8 text-white group-hover:text-gray-300" />
      <span class="hidden md:block text-sm font-medium group-hover:underline">{{ userName }}</span>
    </button>

    <!-- Dropdown Menu -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-96 bg-gray-900 rounded-lg shadow-xl border border-gray-700 py-2 z-50"
      >
        <!-- Sync Section -->
        <div class="px-4 py-3">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-white font-semibold">Sincronizar IPTV</h3>
            <button
              @click="syncIPTV"
              :disabled="isSyncing"
              class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded text-white text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="!isSyncing">Iniciar</span>
              <span v-else class="flex items-center space-x-1">
                <ArrowPathIcon class="w-4 h-4 animate-spin" />
                <span>Sincronizando...</span>
              </span>
            </button>
          </div>

          <!-- TV Ao Vivo -->
          <div class="mb-2 p-2 rounded bg-gray-800/50">
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <ArrowPathIcon
                  v-if="liveSync.status === 'syncing'"
                  class="w-5 h-5 text-blue-400 animate-spin"
                />
                <CheckCircleIcon
                  v-else-if="liveSync.status === 'success'"
                  class="w-5 h-5 text-green-500"
                />
                <XCircleIcon
                  v-else-if="liveSync.status === 'error'"
                  class="w-5 h-5 text-red-500"
                />
                <ClockIcon
                  v-else
                  class="w-5 h-5 text-gray-500"
                />
              </div>
              <div class="flex-1">
                <div class="text-white text-sm font-medium">📺 TV Ao Vivo</div>
                <div
                  class="text-xs mt-0.5"
                  :class="{
                    'text-blue-400': liveSync.status === 'syncing',
                    'text-green-400': liveSync.status === 'success',
                    'text-red-400': liveSync.status === 'error',
                    'text-gray-500': liveSync.status === 'idle'
                  }"
                >
                  {{ liveSync.message || 'Aguardando...' }}
                </div>
              </div>
            </div>
            <!-- Progress Bar -->
            <div v-if="liveSync.status === 'syncing' && liveSync.progress && liveSync.progress > 0" class="mt-2 bg-gray-700 rounded-full h-1.5 overflow-hidden">
              <div
                class="bg-blue-500 h-full transition-all duration-300"
                :style="{ width: `${liveSync.progress}%` }"
              ></div>
            </div>
          </div>

          <!-- Filmes -->
          <div class="mb-2 p-2 rounded bg-gray-800/50">
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <ArrowPathIcon
                  v-if="vodSync.status === 'syncing'"
                  class="w-5 h-5 text-blue-400 animate-spin"
                />
                <CheckCircleIcon
                  v-else-if="vodSync.status === 'success'"
                  class="w-5 h-5 text-green-500"
                />
                <XCircleIcon
                  v-else-if="vodSync.status === 'error'"
                  class="w-5 h-5 text-red-500"
                />
                <ClockIcon
                  v-else
                  class="w-5 h-5 text-gray-500"
                />
              </div>
              <div class="flex-1">
                <div class="text-white text-sm font-medium">🎬 Filmes</div>
                <div
                  class="text-xs mt-0.5"
                  :class="{
                    'text-blue-400': vodSync.status === 'syncing',
                    'text-green-400': vodSync.status === 'success',
                    'text-red-400': vodSync.status === 'error',
                    'text-gray-500': vodSync.status === 'idle'
                  }"
                >
                  {{ vodSync.message || 'Aguardando...' }}
                </div>
              </div>
            </div>
            <!-- Progress Bar -->
            <div v-if="vodSync.status === 'syncing' && vodSync.progress && vodSync.progress > 0" class="mt-2 bg-gray-700 rounded-full h-1.5 overflow-hidden">
              <div
                class="bg-blue-500 h-full transition-all duration-300"
                :style="{ width: `${vodSync.progress}%` }"
              ></div>
            </div>
          </div>

          <!-- Séries -->
          <div class="mb-1 p-2 rounded bg-gray-800/50">
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <ArrowPathIcon
                  v-if="seriesSync.status === 'syncing'"
                  class="w-5 h-5 text-blue-400 animate-spin"
                />
                <CheckCircleIcon
                  v-else-if="seriesSync.status === 'success'"
                  class="w-5 h-5 text-green-500"
                />
                <XCircleIcon
                  v-else-if="seriesSync.status === 'error'"
                  class="w-5 h-5 text-red-500"
                />
                <ClockIcon
                  v-else
                  class="w-5 h-5 text-gray-500"
                />
              </div>
              <div class="flex-1">
                <div class="text-white text-sm font-medium">📺 Séries</div>
                <div
                  class="text-xs mt-0.5"
                  :class="{
                    'text-blue-400': seriesSync.status === 'syncing',
                    'text-green-400': seriesSync.status === 'success',
                    'text-red-400': seriesSync.status === 'error',
                    'text-gray-500': seriesSync.status === 'idle'
                  }"
                >
                  {{ seriesSync.message || 'Aguardando...' }}
                </div>
              </div>
            </div>
            <!-- Progress Bar -->
            <div v-if="seriesSync.status === 'syncing' && seriesSync.progress && seriesSync.progress > 0" class="mt-2 bg-gray-700 rounded-full h-1.5 overflow-hidden">
              <div
                class="bg-blue-500 h-full transition-all duration-300"
                :style="{ width: `${seriesSync.progress}%` }"
              ></div>
            </div>
          </div>
        </div>

        <div class="border-t border-gray-700 my-2"></div>

        <!-- Logout -->
        <button
          @click="handleLogout"
          class="w-full px-4 py-3 text-left hover:bg-gray-800 transition flex items-center space-x-3 text-red-400 hover:text-red-300 cursor-pointer"
        >
          <ArrowRightOnRectangleIcon class="w-5 h-5" />
          <span>Sair</span>
        </button>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* Transitions handled by Transition component */
</style>
