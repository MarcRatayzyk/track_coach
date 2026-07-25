<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import UiIcon from './UiIcon.vue';
import { formatMb } from '../utils/compressVideo';
import { getVideoPreviewUrl, normalizeTrimRange, trimVideo } from '../utils/trimVideo';

const props = defineProps({
  open: { type: Boolean, default: false },
  source: { type: Object, default: null },
  index: { type: Number, default: 0 },
  total: { type: Number, default: 1 },
});

const emit = defineEmits(['confirm', 'use-full', 'cancel']);

const videoEl = ref(null);
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

let selectionStopTimer = 0;

const title = computed(() => {
  if (props.total > 1) {
    return `Rogner la vidéo ${props.index + 1}/${props.total}`;
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

function onStartInput(event) {
  clampStart(Number(event.target.value));
  seekPreview(startSec.value);
}

function onEndInput(event) {
  clampEnd(Number(event.target.value));
  seekPreview(Math.max(startSec.value, endSec.value - 0.01));
}

function seekPreview(sec) {
  const el = videoEl.value;
  if (!el || !metadataReady.value) {
    return;
  }
  try {
    el.currentTime = Math.min(Math.max(0, sec), duration.value || sec);
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
    return;
  }
  const remainMs = Math.max(200, (endSec.value - startSec.value) * 1000);
  selectionStopTimer = window.setTimeout(() => {
    stopSelectionPlayback();
  }, remainMs + 80);
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
        class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"
        aria-label="Fermer"
        :disabled="trimming"
        @click="cancel"
      />

      <div
        class="relative z-10 flex max-h-[min(92vh,40rem)] w-full flex-col overflow-hidden rounded-t-3xl border border-slate-700/80 bg-slate-900 shadow-2xl shadow-black/50 sm:mx-4 sm:max-w-lg sm:rounded-3xl"
      >
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-800 px-5 pb-3 pt-4">
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">
              Avant envoi
            </p>
            <h2 class="mt-0.5 truncate text-base font-semibold text-white">
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

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
          <div class="overflow-hidden rounded-2xl bg-black">
            <video
              v-if="previewUrl"
              ref="videoEl"
              :src="previewUrl"
              playsinline
              preload="metadata"
              class="mx-auto max-h-56 w-full object-contain"
              @loadedmetadata="onLoadedMetadata"
              @timeupdate="onTimeUpdate"
              @ended="stopSelectionPlayback"
            />
          </div>

          <div v-if="metadataReady" class="mt-4 space-y-3">
            <div class="flex items-center justify-between text-xs text-slate-400">
              <span>Début {{ formatTime(startSec) }}</span>
              <span class="font-medium text-slate-200">
                Clip {{ formatTime(selectionDuration) }}
              </span>
              <span>Fin {{ formatTime(endSec) }}</span>
            </div>

            <div class="space-y-2">
              <label class="block text-[11px] font-medium uppercase tracking-wide text-slate-500">
                Début
              </label>
              <input
                type="range"
                min="0"
                :max="duration"
                step="0.05"
                :value="startSec"
                :disabled="trimming"
                class="w-full accent-blue-500"
                @input="onStartInput"
              />
              <label class="block text-[11px] font-medium uppercase tracking-wide text-slate-500">
                Fin
              </label>
              <input
                type="range"
                min="0"
                :max="duration"
                step="0.05"
                :value="endSec"
                :disabled="trimming"
                class="w-full accent-blue-500"
                @input="onEndInput"
              />
            </div>

            <button
              type="button"
              class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm font-medium text-slate-200 transition hover:border-slate-600 hover:bg-slate-900 disabled:opacity-50"
              :disabled="!canConfirm || trimming"
              @click="playSelection"
            >
              {{ playingSelection ? 'Lecture…' : 'Prévisualiser le clip' }}
            </button>
          </div>

          <div v-if="trimming" class="mt-4">
            <div class="h-2 overflow-hidden rounded-full bg-slate-800">
              <div
                class="h-full rounded-full bg-blue-500 transition-all duration-200"
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
            class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:opacity-50"
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
