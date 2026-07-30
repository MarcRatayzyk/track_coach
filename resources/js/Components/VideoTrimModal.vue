<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import UiIcon from './UiIcon.vue';
import { formatMb } from '../utils/compressVideo';
import {
  getVideoPreviewUrl,
  normalizeTrimRange,
  preloadTrimEngine,
  trimVideo,
} from '../utils/trimVideo';

const props = defineProps({
  open: { type: Boolean, default: false },
  source: { type: Object, default: null },
  index: { type: Number, default: 0 },
  total: { type: Number, default: 1 },
});

const emit = defineEmits(['confirm', 'use-full', 'cancel']);

const videoEl = ref(null);
const stripEl = ref(null);
const previewUrl = ref(null);
const ownsPreviewUrl = ref(false);
const duration = ref(0);
const startSec = ref(0);
const endSec = ref(0);
const currentSec = ref(0);
const playingSelection = ref(false);
const trimming = ref(false);
const trimProgress = ref(0);
const errorMessage = ref('');
const metadataReady = ref(false);
const thumbnails = ref([]);
const thumbsLoading = ref(false);

/** @type {'start'|'end'|'move'|null} */
const dragMode = ref(null);
const dragOriginX = ref(0);
const dragStartAtPointer = ref(0);
const dragEndAtPointer = ref(0);

let selectionStopTimer = 0;
let thumbToken = 0;

const title = computed(() => {
  if (props.total > 1) {
    return `Rogner ${props.index + 1}/${props.total}`;
  }
  return 'Rogner la vidéo';
});

const fileLabel = computed(() => props.source?.name || 'vidéo');
const sizeLabel = computed(() => {
  const bytes = props.source?.size ?? props.source?.file?.size ?? 0;
  return bytes > 0 ? formatMb(bytes) : null;
});

const selectionDuration = computed(() => Math.max(0, endSec.value - startSec.value));

const canConfirm = computed(() => {
  if (!metadataReady.value || trimming.value) {
    return false;
  }
  return Boolean(normalizeTrimRange(startSec.value, endSec.value, duration.value));
});

const isFullSelection = computed(() => {
  if (!metadataReady.value || duration.value <= 0) {
    return true;
  }
  return startSec.value <= 0.05 && endSec.value >= duration.value - 0.05;
});

const startPct = computed(() =>
  duration.value > 0 ? (startSec.value / duration.value) * 100 : 0,
);
const endPct = computed(() =>
  duration.value > 0 ? (endSec.value / duration.value) * 100 : 100,
);
const playheadPct = computed(() =>
  duration.value > 0 ? (currentSec.value / duration.value) * 100 : 0,
);

function formatTime(sec) {
  const total = Math.max(0, Math.floor(Number(sec) || 0));
  const m = Math.floor(total / 60);
  const s = total % 60;
  return `${m}:${String(s).padStart(2, '0')}`;
}

function revokePreview() {
  if (ownsPreviewUrl.value && previewUrl.value?.startsWith('blob:')) {
    try {
      URL.revokeObjectURL(previewUrl.value);
    } catch {
      // ignore
    }
  }
  previewUrl.value = null;
  ownsPreviewUrl.value = false;
}

function resetState() {
  clearTimeout(selectionStopTimer);
  playingSelection.value = false;
  trimming.value = false;
  trimProgress.value = 0;
  errorMessage.value = '';
  metadataReady.value = false;
  duration.value = 0;
  startSec.value = 0;
  endSec.value = 0;
  currentSec.value = 0;
  thumbnails.value = [];
  thumbsLoading.value = false;
  dragMode.value = null;
  thumbToken += 1;
  if (videoEl.value) {
    try {
      videoEl.value.pause();
    } catch {
      // ignore
    }
  }
}

function loadPreview() {
  revokePreview();
  resetState();
  if (!props.source) {
    return;
  }
  preloadTrimEngine();
  const url = getVideoPreviewUrl(props.source);
  previewUrl.value = url;
  ownsPreviewUrl.value =
    Boolean(url?.startsWith('blob:')) && props.source.file instanceof Blob;
}

function onLoadedMetadata() {
  const el = videoEl.value;
  if (!el) {
    return;
  }
  const d = Number(el.duration);
  if (!Number.isFinite(d) || d <= 0) {
    errorMessage.value = 'Impossible de lire la durée de cette vidéo.';
    return;
  }
  duration.value = d;
  startSec.value = 0;
  endSec.value = d;
  currentSec.value = 0;
  metadataReady.value = true;
  errorMessage.value = '';
  buildFilmstrip(el, d);
}

async function buildFilmstrip(el, d) {
  const token = ++thumbToken;
  thumbsLoading.value = true;
  const count = d > 45 ? 12 : d > 20 ? 10 : 8;
  const canvas = document.createElement('canvas');
  const w = 72;
  const h = 96;
  canvas.width = w;
  canvas.height = h;
  const ctx = canvas.getContext('2d', { alpha: false });
  if (!ctx) {
    thumbsLoading.value = false;
    return;
  }

  const frames = [];
  const prevTime = el.currentTime;

  try {
    for (let i = 0; i < count; i += 1) {
      if (token !== thumbToken) {
        return;
      }
      const t = count === 1 ? 0 : (i / (count - 1)) * Math.max(0, d - 0.05);
      // eslint-disable-next-line no-await-in-loop -- seek séquentiel pour le filmstrip
      await seekQuiet(el, t);
      if (token !== thumbToken) {
        return;
      }
      try {
        ctx.fillStyle = '#0f172a';
        ctx.fillRect(0, 0, w, h);
        const vw = el.videoWidth || w;
        const vh = el.videoHeight || h;
        const scale = Math.max(w / vw, h / vh);
        const dw = vw * scale;
        const dh = vh * scale;
        ctx.drawImage(el, (w - dw) / 2, (h - dh) / 2, dw, dh);
        frames.push(canvas.toDataURL('image/jpeg', 0.55));
      } catch {
        frames.push('');
      }
    }
    if (token === thumbToken) {
      thumbnails.value = frames;
    }
  } finally {
    if (token === thumbToken) {
      thumbsLoading.value = false;
      try {
        el.currentTime = prevTime || 0;
      } catch {
        // ignore
      }
    }
  }
}

function seekQuiet(el, time) {
  return new Promise((resolve) => {
    const done = () => {
      el.removeEventListener('seeked', done);
      resolve();
    };
    el.addEventListener('seeked', done);
    try {
      el.currentTime = Math.min(Math.max(0, time), duration.value || time);
    } catch {
      resolve();
      return;
    }
    window.setTimeout(done, 450);
  });
}

function onTimeUpdate() {
  const el = videoEl.value;
  if (!el) {
    return;
  }
  currentSec.value = el.currentTime;
  if (playingSelection.value && el.currentTime >= endSec.value - 0.04) {
    stopSelectionPlayback();
  }
}

function clampStart(value) {
  const maxStart = Math.max(0, endSec.value - 0.5);
  startSec.value = Math.min(Math.max(0, value), maxStart);
}

function clampEnd(value) {
  const minEnd = Math.min(duration.value, startSec.value + 0.5);
  endSec.value = Math.max(minEnd, Math.min(duration.value, value));
}

function seekPreview(sec) {
  const el = videoEl.value;
  if (!el || !metadataReady.value) {
    return;
  }
  try {
    el.currentTime = Math.min(Math.max(0, sec), duration.value || sec);
    currentSec.value = el.currentTime;
  } catch {
    // ignore seek errors while loading
  }
}

function stopSelectionPlayback() {
  clearTimeout(selectionStopTimer);
  playingSelection.value = false;
  const el = videoEl.value;
  if (el) {
    el.pause();
  }
}

async function playSelection() {
  const el = videoEl.value;
  if (!el || !canConfirm.value) {
    return;
  }
  stopSelectionPlayback();
  seekPreview(startSec.value);
  playingSelection.value = true;
  await nextTick();
  try {
    await el.play();
  } catch {
    playingSelection.value = false;
  }
}

function clientXToTime(clientX) {
  const strip = stripEl.value;
  if (!strip || duration.value <= 0) {
    return 0;
  }
  const rect = strip.getBoundingClientRect();
  const ratio = Math.min(1, Math.max(0, (clientX - rect.left) / rect.width));
  return ratio * duration.value;
}

function onHandlePointerDown(mode, event) {
  if (trimming.value || !metadataReady.value) {
    return;
  }
  stopSelectionPlayback();
  dragMode.value = mode;
  dragOriginX.value = event.clientX;
  dragStartAtPointer.value = startSec.value;
  dragEndAtPointer.value = endSec.value;
  event.currentTarget.setPointerCapture?.(event.pointerId);
}

function onStripPointerDown(event) {
  if (trimming.value || !metadataReady.value || dragMode.value) {
    return;
  }
  // Ignore if clicking a handle (handled separately)
  if (event.target?.closest?.('[data-trim-handle]')) {
    return;
  }
  stopSelectionPlayback();
  const t = clientXToTime(event.clientX);
  // Drag middle of selection, or set playhead outside
  if (t >= startSec.value && t <= endSec.value) {
    dragMode.value = 'move';
    dragOriginX.value = event.clientX;
    dragStartAtPointer.value = startSec.value;
    dragEndAtPointer.value = endSec.value;
    stripEl.value?.setPointerCapture?.(event.pointerId);
  } else {
    seekPreview(Math.min(Math.max(t, startSec.value), endSec.value));
  }
}

function onStripPointerMove(event) {
  if (!dragMode.value || duration.value <= 0) {
    return;
  }
  const strip = stripEl.value;
  if (!strip) {
    return;
  }
  const rect = strip.getBoundingClientRect();
  const deltaSec = ((event.clientX - dragOriginX.value) / rect.width) * duration.value;

  if (dragMode.value === 'start') {
    clampStart(dragStartAtPointer.value + deltaSec);
    seekPreview(startSec.value);
  } else if (dragMode.value === 'end') {
    clampEnd(dragEndAtPointer.value + deltaSec);
    seekPreview(Math.max(startSec.value, endSec.value - 0.01));
  } else if (dragMode.value === 'move') {
    const len = dragEndAtPointer.value - dragStartAtPointer.value;
    let nextStart = dragStartAtPointer.value + deltaSec;
    nextStart = Math.min(Math.max(0, nextStart), duration.value - len);
    startSec.value = nextStart;
    endSec.value = nextStart + len;
    seekPreview(startSec.value);
  }
}

function onStripPointerUp() {
  dragMode.value = null;
}

async function confirmTrim() {
  if (!props.source || !canConfirm.value || trimming.value) {
    return;
  }

  if (isFullSelection.value) {
    emit('use-full');
    return;
  }

  trimming.value = true;
  trimProgress.value = 0;
  errorMessage.value = '';
  stopSelectionPlayback();

  try {
    const result = await trimVideo(props.source, {
      startSec: startSec.value,
      endSec: endSec.value,
      videoEl: videoEl.value,
      onProgress: (ratio) => {
        trimProgress.value = Math.round(Math.min(100, Math.max(0, ratio * 100)));
      },
    });
    emit('confirm', result);
  } catch (error) {
    errorMessage.value =
      error?.message ||
      'Rognage impossible. Vous pouvez envoyer toute la vidéo, ou réessayer.';
    trimming.value = false;
    trimProgress.value = 0;
  }
}

function useFull() {
  if (trimming.value) {
    return;
  }
  stopSelectionPlayback();
  emit('use-full');
}

function cancel() {
  if (trimming.value) {
    return;
  }
  stopSelectionPlayback();
  emit('cancel');
}

watch(
  () => [props.open, props.source],
  ([open]) => {
    if (open && props.source) {
      loadPreview();
    } else {
      stopSelectionPlayback();
      revokePreview();
      resetState();
    }
  },
  { immediate: true },
);

onUnmounted(() => {
  stopSelectionPlayback();
  revokePreview();
  thumbToken += 1;
});
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && source"
      class="fixed inset-0 z-[90] flex items-end justify-center sm:items-center"
      role="dialog"
      aria-modal="true"
      :aria-label="title"
    >
      <button
        type="button"
        class="absolute inset-0 bg-slate-950/85 backdrop-blur-sm"
        aria-label="Fermer"
        :disabled="trimming"
        @click="cancel"
      />

      <div
        class="relative z-10 flex max-h-[min(96vh,44rem)] w-full flex-col overflow-hidden rounded-t-3xl border border-slate-700/80 bg-slate-950 shadow-2xl shadow-black/50 sm:mx-4 sm:max-w-lg sm:rounded-3xl"
      >
        <div class="flex shrink-0 items-center justify-between gap-3 px-5 pb-2 pt-4">
          <div class="min-w-0">
            <h2 class="truncate text-base font-semibold text-white">
              {{ title }}
            </h2>
            <p class="mt-0.5 truncate text-xs text-slate-500">
              {{ fileLabel }}
              <span v-if="sizeLabel"> · {{ sizeLabel }}</span>
            </p>
          </div>
          <button
            type="button"
            class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white disabled:opacity-40"
            aria-label="Annuler"
            :disabled="trimming"
            @click="cancel"
          >
            <UiIcon name="x-mark" class="h-5 w-5" />
          </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-3 pt-1">
          <!-- Aperçu vidéo plein cadre -->
          <div class="relative overflow-hidden rounded-2xl bg-black">
            <video
              v-if="previewUrl"
              ref="videoEl"
              :src="previewUrl"
              playsinline
              preload="auto"
              class="mx-auto max-h-[min(52vh,28rem)] w-full object-contain"
              @loadedmetadata="onLoadedMetadata"
              @timeupdate="onTimeUpdate"
              @ended="stopSelectionPlayback"
              @click="playingSelection ? stopSelectionPlayback() : playSelection()"
            />
            <button
              v-if="metadataReady && !trimming"
              type="button"
              class="absolute bottom-3 left-3 inline-flex h-10 w-10 items-center justify-center rounded-full bg-black/55 text-white backdrop-blur-sm transition hover:bg-black/70"
              :aria-label="playingSelection ? 'Pause' : 'Lire le clip'"
              @click.stop="playingSelection ? stopSelectionPlayback() : playSelection()"
            >
              <svg
                v-if="playingSelection"
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
              >
                <path d="M6.75 5.25h3v13.5h-3V5.25zm7.5 0h3v13.5h-3V5.25z" />
              </svg>
              <svg
                v-else
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
              >
                <path d="M8.25 5.25v13.5l11.25-6.75L8.25 5.25z" />
              </svg>
            </button>
            <div
              v-if="metadataReady"
              class="pointer-events-none absolute bottom-3 right-3 rounded-lg bg-black/55 px-2 py-1 text-[11px] font-medium tabular-nums text-white backdrop-blur-sm"
            >
              {{ formatTime(currentSec) }}
            </div>
          </div>

          <!-- Timeline style CapCut -->
          <div v-if="metadataReady" class="mt-4 space-y-2">
            <div class="flex items-center justify-between text-xs tabular-nums text-slate-400">
              <span>{{ formatTime(startSec) }}</span>
              <span class="font-semibold text-sky-300">Clip {{ formatTime(selectionDuration) }}</span>
              <span>{{ formatTime(endSec) }}</span>
            </div>

            <div
              ref="stripEl"
              class="relative select-none touch-none overflow-hidden rounded-xl bg-slate-900 ring-1 ring-slate-700"
              style="height: 4.5rem"
              @pointerdown="onStripPointerDown"
              @pointermove="onStripPointerMove"
              @pointerup="onStripPointerUp"
              @pointercancel="onStripPointerUp"
            >
              <!-- Filmstrip -->
              <div class="absolute inset-0 flex">
                <div
                  v-for="(thumb, i) in thumbnails"
                  :key="i"
                  class="h-full flex-1 bg-slate-800 bg-cover bg-center"
                  :style="thumb ? { backgroundImage: `url(${thumb})` } : undefined"
                />
                <div
                  v-if="thumbsLoading && thumbnails.length === 0"
                  class="flex h-full w-full items-center justify-center text-[11px] text-slate-500"
                >
                  Aperçu…
                </div>
              </div>

              <!-- Zones hors sélection -->
              <div
                class="pointer-events-none absolute inset-y-0 left-0 bg-slate-950/70"
                :style="{ width: `${startPct}%` }"
              />
              <div
                class="pointer-events-none absolute inset-y-0 right-0 bg-slate-950/70"
                :style="{ width: `${100 - endPct}%` }"
              />

              <!-- Fenêtre sélection -->
              <div
                class="pointer-events-none absolute inset-y-0 border-y-2 border-sky-400"
                :style="{ left: `${startPct}%`, width: `${endPct - startPct}%` }"
              />

              <!-- Playhead -->
              <div
                class="pointer-events-none absolute inset-y-0 z-10 w-0.5 bg-white shadow"
                :style="{ left: `${playheadPct}%` }"
              />

              <!-- Poignée début -->
              <button
                type="button"
                data-trim-handle="start"
                class="absolute inset-y-0 z-20 flex w-5 -translate-x-1/2 cursor-ew-resize items-center justify-center touch-none"
                :style="{ left: `${startPct}%` }"
                :disabled="trimming"
                aria-label="Début du clip"
                @pointerdown.stop="onHandlePointerDown('start', $event)"
                @pointermove="onStripPointerMove"
                @pointerup="onStripPointerUp"
              >
                <span class="h-full w-1.5 rounded-full bg-sky-400 shadow-lg shadow-sky-500/40" />
              </button>

              <!-- Poignée fin -->
              <button
                type="button"
                data-trim-handle="end"
                class="absolute inset-y-0 z-20 flex w-5 -translate-x-1/2 cursor-ew-resize items-center justify-center touch-none"
                :style="{ left: `${endPct}%` }"
                :disabled="trimming"
                aria-label="Fin du clip"
                @pointerdown.stop="onHandlePointerDown('end', $event)"
                @pointermove="onStripPointerMove"
                @pointerup="onStripPointerUp"
              >
                <span class="h-full w-1.5 rounded-full bg-sky-400 shadow-lg shadow-sky-500/40" />
              </button>
            </div>

            <p class="text-center text-[11px] text-slate-500">
              Glissez les poignées pour couper · touchez la vidéo pour lire
            </p>
          </div>

          <div v-if="trimming" class="mt-4">
            <div class="h-2 overflow-hidden rounded-full bg-slate-800">
              <div
                class="h-full rounded-full bg-sky-500 transition-all duration-200"
                :style="{ width: `${trimProgress}%` }"
              />
            </div>
            <p class="mt-1 text-xs text-slate-400">Rognage… {{ trimProgress }}%</p>
          </div>

          <p v-if="errorMessage" class="mt-3 text-sm text-amber-300/95">
            {{ errorMessage }}
          </p>
        </div>

        <div class="shrink-0 space-y-2 border-t border-slate-800 px-5 py-4">
          <button
            type="button"
            class="w-full rounded-xl bg-sky-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-400 disabled:opacity-50"
            :disabled="!canConfirm || trimming"
            @click="confirmTrim"
          >
            {{ isFullSelection ? 'Continuer (vidéo entière)' : 'Confirmer le clip' }}
          </button>
          <button
            type="button"
            class="w-full rounded-xl border border-slate-700 bg-transparent px-4 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-slate-800 disabled:opacity-50"
            :disabled="trimming"
            @click="useFull"
          >
            Utiliser toute la vidéo
          </button>
          <button
            type="button"
            class="w-full px-2 py-1 text-center text-xs text-slate-500 transition hover:text-slate-300 disabled:opacity-40"
            :disabled="trimming"
            @click="cancel"
          >
            Annuler la sélection
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
