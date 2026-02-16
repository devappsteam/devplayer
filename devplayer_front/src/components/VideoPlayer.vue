<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon, ArrowsPointingOutIcon } from '@heroicons/vue/24/solid';
import Hls from 'hls.js';
import { getSecureVideoUrl } from '@/composables/useVideoProxy';

const props = defineProps<{
  src: string;
  autoplay?: boolean;
  shouldShowNavigation?: boolean;
  nextChannel?: any;
  prevChannel?: any;
  isSeries?: boolean;
  nextEpisode?: any;
}>();

const emit = defineEmits(['nextFavorite', 'prevFavorite', 'playNextEpisode']);

const videoRef = ref<HTMLVideoElement | null>(null);
let hls: Hls | null = null;
const isPlaying = ref(false);
const isLoading = ref(true);
const currentTime = ref(0);
const duration = ref(0);
const volume = ref(1);
const showControls = ref(true);
const isFullscreen = ref(false);
const isPip = ref(false);
const showNextEpisodeButton = ref(false);
const timeUntilNextEpisode = ref(20);
let controlsTimeout: number | undefined;
let lastClickTime = 0;
let clickTimeout: number | undefined;

// Computed property para URL segura (com proxy se necessário)
const secureVideoUrl = computed(() => getSecureVideoUrl(props.src));

const play = () => {
    videoRef.value?.play();
};
const pause = () => {
    videoRef.value?.pause();
};

const togglePlay = () => {
    if (isPlaying.value) {
        pause();
    } else {
        play();
    }
};

const handleVideoClick = () => {
    const now = Date.now();
    const timeDiff = now - lastClickTime;

    if (timeDiff < 300) {
        // Duplo clique detectado
        clearTimeout(clickTimeout);
        toggleFullscreen();
    } else {
        // Clique simples
        clickTimeout = setTimeout(() => {
            togglePlay();
        }, 150);
    }

    lastClickTime = now;
};

const setupHls = () => {
    if (!videoRef.value || !props.src) return;

    // Usar URL segura (com proxy se necessário)
    const videoUrl = secureVideoUrl.value;

    // Check if URL is a direct MP4/video file or HLS manifest
    const isDirectVideo = videoUrl.includes('.mp4') || videoUrl.includes('.mkv') || videoUrl.includes('.avi');
    const isHlsManifest = videoUrl.includes('.m3u8');

    // For direct video files, use native video element
    if (isDirectVideo && !isHlsManifest) {
        console.log('Using native video player for:', videoUrl);
        if (hls) {
            hls.destroy();
            hls = null;
        }
        videoRef.value.src = videoUrl;
        videoRef.value.addEventListener('loadedmetadata', () => {
            if (props.autoplay) play();
            isLoading.value = false;
        });
        videoRef.value.addEventListener('error', (e) => {
            console.error('Video playback error:', e);
            isLoading.value = false;
        });
        return;
    }

    // For HLS streams, use HLS.js
    if (Hls.isSupported()) {
        if (hls) hls.destroy();
        hls = new Hls({
            autoStartLoad: true,
            startLevel: -1,
            xhrSetup: function(xhr, url) {
                // Disable CORS credentials and remove origin headers to avoid being blocked
                xhr.withCredentials = false;
                // Remove Referer header by setting Referrer-Policy
                xhr.setRequestHeader('Referrer-Policy', 'no-referrer');
            },
            maxBufferLength: 30,
            maxMaxBufferLength: 60,
        });
        hls.loadSource(videoUrl);
        hls.attachMedia(videoRef.value);

        hls.on(Hls.Events.MANIFEST_PARSED, () => {
            if (props.autoplay) {
                play();
            }
            isLoading.value = false;
        });

        hls.on(Hls.Events.ERROR, (event, data) => {
             if (data.fatal) {
                console.error("HLS Error:", data.type, data.details, data);
                switch (data.type) {
                case Hls.ErrorTypes.NETWORK_ERROR:
                    console.error("fatal network error, trying native video fallback");
                    // Try fallback to native video element
                    if (hls) {
                        hls.destroy();
                        hls = null;
                    }
                    videoRef.value!.src = secureVideoUrl.value;
                    if (props.autoplay) play();
                    break;
                case Hls.ErrorTypes.MEDIA_ERROR:
                    console.error("fatal media error, try to recover");
                    hls?.recoverMediaError();
                    break;
                default:
                    console.error("fatal error, cannot recover");
                    hls?.destroy();
                    break;
                }
            }
        });
    } else if (videoRef.value.canPlayType('application/vnd.apple.mpegurl')) {
        videoRef.value.src = secureVideoUrl.value;
        videoRef.value.addEventListener('loadedmetadata', () => {
            if (props.autoplay) play();
            isLoading.value = false;
        });
    }
};

const resetControlsTimer = () => {
    showControls.value = true;
    clearTimeout(controlsTimeout);
    if (isPlaying.value) {
        controlsTimeout = setTimeout(() => {
            showControls.value = false;
        }, 3000);
    }
};

onMounted(() => {
    setupHls();
});

watch(() => props.src, () => {
    isLoading.value = true;
    setupHls();
});

onUnmounted(() => {
    if (hls) hls.destroy();
    clearTimeout(controlsTimeout);
});

const updateTime = () => {
    if (videoRef.value) {
        currentTime.value = videoRef.value.currentTime;
        duration.value = videoRef.value.duration;

        // Lógica para séries: mostrar botão próximo episódio
        if (props.isSeries && props.nextEpisode && duration.value > 0) {
            const timeRemaining = duration.value - currentTime.value;

            if (timeRemaining <= 20 && timeRemaining > 0) {
                showNextEpisodeButton.value = true;
                timeUntilNextEpisode.value = Math.ceil(timeRemaining);
            } else {
                showNextEpisodeButton.value = false;
            }

            // Auto-play próximo episódio quando acabar
            if (timeRemaining <= 0.5 && !videoRef.value.paused) {
                playNextEpisode();
            }
        }
    }
};

const playNextEpisode = () => {
    if (props.nextEpisode) {
        showNextEpisodeButton.value = false;
        emit('playNextEpisode', props.nextEpisode);
    }
};

const skipToNextEpisode = () => {
    playNextEpisode();
};

const onPlay = () => {
    isPlaying.value = true;
    resetControlsTimer();
};

const onPause = () => {
    isPlaying.value = false;
    showControls.value = true;
    clearTimeout(controlsTimeout);
};

const onWaiting = () => (isLoading.value = true);
const onPlaying = () => (isLoading.value = false);

const toggleFullscreen = async () => {
    const container = document.querySelector('[data-player-container]') as HTMLElement;
    if (!container) return;

    try {
        if (!isFullscreen.value) {
            if (container.requestFullscreen) {
                await container.requestFullscreen();
            } else if ((container as any).webkitRequestFullscreen) {
                await (container as any).webkitRequestFullscreen();
            }
            isFullscreen.value = true;
        } else {
            if (document.fullscreenElement) {
                await document.exitFullscreen();
            }
            isFullscreen.value = false;
        }
    } catch (err) {
        console.error('Fullscreen error:', err);
    }
};

const togglePictureInPicture = async () => {
    if (!videoRef.value) return;

    try {
        if (!isPip.value) {
            await videoRef.value.requestPictureInPicture();
            isPip.value = true;
        } else {
            await document.exitPictureInPicture();
            isPip.value = false;
        }
    } catch (err) {
        console.error('Picture-in-Picture error:', err);
    }
};

// Listen for fullscreen changes
onMounted(() => {
    const handleFullscreenChange = () => {
        isFullscreen.value = !!document.fullscreenElement;
    };
    document.addEventListener('fullscreenchange', handleFullscreenChange);

    // Listen for Picture-in-Picture changes
    const handleEnterPip = () => {
        isPip.value = true;
    };
    const handleExitPip = () => {
        isPip.value = false;
    };
    if (videoRef.value) {
        videoRef.value.addEventListener('enterpictureinpicture', handleEnterPip);
        videoRef.value.addEventListener('leavepictureinpicture', handleExitPip);
    }

    return () => {
        document.removeEventListener('fullscreenchange', handleFullscreenChange);
        if (videoRef.value) {
            videoRef.value.removeEventListener('enterpictureinpicture', handleEnterPip);
            videoRef.value.removeEventListener('leavepictureinpicture', handleExitPip);
        }
    };
});</script>

<template>
  <div
    data-player-container
    class="relative w-full h-full bg-black group overflow-hidden"
    @mousemove="resetControlsTimer"
    @click="resetControlsTimer"
    @mouseleave="() => { if(isPlaying) showControls = false; }"
  >
    <!-- Video Element -->
    <video
      ref="videoRef"
      class="absolute inset-0 w-full h-full object-contain cursor-pointer"
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
      @timeupdate="updateTime"
      @play="onPlay"
      @pause="onPause"
      @waiting="onWaiting"
      @playing="onPlaying"
      @click="handleVideoClick"
    ></video>

    <!-- Next Episode Button (Series Only) -->
    <Transition name="slide-fade">
      <div
        v-if="showNextEpisodeButton && nextEpisode"
        class="absolute bottom-24 right-8 z-50 pointer-events-auto"
      >
        <button
          @click="skipToNextEpisode"
          class="flex flex-col items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-4 rounded-lg shadow-2xl transition-all duration-300 hover:scale-105 cursor-pointer border-2 border-white/20"
        >
          <div class="flex items-center gap-3">
            <div class="text-left">
              <p class="text-xs font-semibold uppercase tracking-wide opacity-90">Próximo Episódio</p>
              <p class="text-sm font-bold line-clamp-1">{{ nextEpisode.title || nextEpisode.name }}</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 bg-white/20 rounded-full">
              <span class="text-2xl font-bold">{{ timeUntilNextEpisode }}</span>
            </div>
          </div>
          <div class="w-full h-1 bg-white/30 rounded-full overflow-hidden">
            <div
              class="h-full bg-white transition-all duration-1000 ease-linear"
              :style="{ width: `${(timeUntilNextEpisode / 20) * 100}%` }"
            ></div>
          </div>
        </button>
      </div>
    </Transition>

    <!-- Side Panels - Navigation Info -->
    <div v-if="shouldShowNavigation" class="absolute inset-0 flex items-center justify-between pointer-events-none px-4 z-40">
      <!-- Previous Channel (Left) -->
      <div v-if="prevChannel" class="flex flex-col items-center gap-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300 cursor-pointer pointer-events-auto">
        <div class="text-center">
          <img
            :src="prevChannel.image"
            :alt="prevChannel.title"
            class="w-24 h-32 object-cover rounded-lg shadow-xl border-2 border-red-600 group-hover:border-red-500 transition-colors hover:scale-105"
            @click.stop="emit('prevFavorite')"
          />
        </div>
        <div class="text-center max-w-xs">
          <p class="text-xs text-gray-400 font-semibold">ANTERIOR</p>
          <p class="text-sm text-white font-bold line-clamp-2">{{ prevChannel.title }}</p>
        </div>
      </div>

      <!-- Next Channel (Right) -->
      <div v-if="nextChannel" class="flex flex-col items-center gap-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300 cursor-pointer pointer-events-auto">
        <div class="text-center">
          <img
            :src="nextChannel.image"
            :alt="nextChannel.title"
            class="w-24 h-32 object-contain rounded-lg shadow-xl border-2 border-red-600 group-hover:border-red-500 transition-colors hover:scale-105"
            @click.stop="emit('nextFavorite')"
          />
        </div>
        <div class="text-center max-w-xs">
          <p class="text-xs text-gray-400 font-semibold">PRÓXIMO</p>
          <p class="text-sm text-white font-bold line-clamp-2">{{ nextChannel.title }}</p>
        </div>
      </div>
    </div>

    <!-- Overlay Controls -->
    <div
        class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 via-transparent to-transparent px-6 pb-4 pt-16 transition-opacity duration-500 ease-in-out pointer-events-none z-50"
        :class="{ 'opacity-0': !showControls, 'opacity-100': showControls }"
        @click.stop
    >
      <!-- Title/Info (Optional slot) -->
      <div class="mb-4">
        <slot name="info"></slot>
      </div>

      <!-- Controls -->
      <div class="flex items-center gap-4 pointer-events-auto">
        <button @click="togglePlay" class="text-white hover:text-red-400 focus:outline-none transition transform hover:scale-110 cursor-pointer">
          <svg v-if="!isPlaying" xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z" />
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="currentColor" viewBox="0 0 24 24">
            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
          </svg>
        </button>

        <!-- Progress Bar -->
        <input
            type="range"
            min="0"
            :max="duration || 100"
            :value="currentTime"
            class="w-full h-1 bg-gray-600 rounded-lg appearance-none cursor-pointer accent-red-500 hover:h-2 transition-all"
            @input="(e) => { if(videoRef) videoRef.currentTime = parseFloat((e.target as HTMLInputElement).value) }"
        />

        <div class="text-sm font-medium text-gray-200 tabular-nums font-mono">
             {{ Math.floor(currentTime / 60) }}:{{ Math.floor(currentTime % 60).toString().padStart(2, '0') }} /
             {{ Math.floor(duration / 60) }}:{{ Math.floor(duration % 60).toString().padStart(2, '0') }}
        </div>

        <!-- Navigation Buttons (Favorites) -->
        <div v-if="shouldShowNavigation" class="flex items-center gap-2 border-l border-gray-600 pl-4">
          <button
            @click="emit('prevFavorite')"
            class="text-white hover:text-red-400 focus:outline-none transition transform hover:scale-110 cursor-pointer"
            title="Anterior (Favorito)"
          >
            <ChevronLeftIcon class="h-6 w-6" />
          </button>
          <button
            @click="emit('nextFavorite')"
            class="text-white hover:text-red-400 focus:outline-none transition transform hover:scale-110 cursor-pointer"
            title="Próximo (Favorito)"
          >
            <ChevronRightIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Fullscreen Button -->
        <button
          @click="toggleFullscreen"
          class="text-white hover:text-red-400 focus:outline-none transition transform hover:scale-110 border-l border-gray-600 pl-4 cursor-pointer"
          title="Tela Cheia"
        >
          <ArrowsPointingOutIcon class="h-6 w-6" />
        </button>

        <!-- Picture-in-Picture Button -->
        <button
          @click="togglePictureInPicture"
          class="text-white hover:text-red-400 focus:outline-none transition transform hover:scale-110 cursor-pointer"
          title="Picture-in-Picture"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
            <rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
            <rect x="12" y="12" width="10" height="9" rx="1" fill="none" stroke="currentColor" stroke-width="2"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Loading Spinner -->
    <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-black/50 z-60 pointer-events-none">
        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-red-500"></div>
    </div>
  </div>
</template>

<style scoped>
/* Custom range slider styling if needed */

/* Slide fade animation for next episode button */
.slide-fade-enter-active {
  transition: all 0.4s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.3s ease-in;
}

.slide-fade-enter-from {
  transform: translateX(100px);
  opacity: 0;
}

.slide-fade-leave-to {
  transform: translateX(100px);
  opacity: 0;
}
</style>

