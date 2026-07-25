<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { formatMb } from '../utils/compressVideo';

const props = defineProps({
  video: { type: Object, required: true },
});

const videoEl = ref(null);
const canvasEl = ref(null);
const currentMs = ref(0);
const localAnnotations = ref([...(props.video.annotations ?? [])]);
const activeAnnotationId = ref(null);

const playing = ref(false);
const isBuffering = ref(false);
const showBufferOverlay = ref(false);
const bufferPercent = ref(0);
const hasFirstFrame = ref(false);
let bufferOverlayTimer = 0;

const sizeLabel = computed(() => {
  const bytes = props.video?.size_bytes;
  if (!bytes || bytes <= 0) {
    return null;
  }
  return formatMb(bytes);
});

const visibleAnnotations = computed(() =>
  localAnnotations.value.filter((item) => Math.abs(item.timestamp_ms - currentMs.value) < 1500),
);

function formatTime(ms) {
  const totalSeconds = Math.floor(ms / 1000);
  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  return `${minutes}:${String(seconds).padStart(2, '0')}`;
}

function scheduleBufferOverlay() {
  clearTimeout(bufferOverlayTimer);
  if (!isBuffering.value || hasFirstFrame.value) {
    showBufferOverlay.value = false;
    return;
  }
  bufferOverlayTimer = window.setTimeout(() => {
    if (isBuffering.value && !hasFirstFrame.value) {
      showBufferOverlay.value = true;
    }
  }, 400);
}

function markReady() {
  isBuffering.value = false;
  hasFirstFrame.value = true;
  showBufferOverlay.value = false;
  clearTimeout(bufferOverlayTimer);
  onProgress();
}

function startPlayback() {
  if (playing.value) {
    return;
  }
  playing.value = true;
  if (!hasFirstFrame.value) {
    isBuffering.value = true;
    scheduleBufferOverlay();
  }
  nextTick(() => {
    const el = videoEl.value;
    if (!el) {
      return;
    }
    el.muted = false;
    el.play?.().catch(() => {});
  });
}

function onTimeUpdate() {
  if (!videoEl.value) {
    return;
  }
  currentMs.value = Math.round(videoEl.value.currentTime * 1000);
  redrawCanvas();
}

function onLoadedMetadata() {
  resizeCanvas();
  redrawCanvas();
}

function onProgress() {
  const el = videoEl.value;
  if (!el || !el.duration || !Number.isFinite(el.duration)) {
    return;
  }
  if (el.buffered.length === 0) {
    return;
  }
  const end = el.buffered.end(el.buffered.length - 1);
  bufferPercent.value = Math.min(100, Math.round((end / el.duration) * 100));
}

function onWaiting() {
  if (!playing.value) {
    return;
  }
  isBuffering.value = true;
  scheduleBufferOverlay();
}

function onCanPlay() {
  markReady();
}

function onLoadedData() {
  markReady();
}

function resizeCanvas() {
  if (!videoEl.value || !canvasEl.value) {
    return;
  }
  canvasEl.value.width = videoEl.value.clientWidth;
  canvasEl.value.height = videoEl.value.clientHeight;
  redrawCanvas();
}

function redrawCanvas() {
  const canvas = canvasEl.value;
  if (!canvas) {
    return;
  }
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  visibleAnnotations.value
    .flatMap((item) => item.shapes ?? [])
    .forEach((shape) => drawShape(ctx, shape, canvas.width, canvas.height));
}

function drawShape(ctx, shape, width, height) {
  ctx.strokeStyle = '#38bdf8';
  ctx.lineWidth = 2;
  const x1 = shape.x1 * width;
  const y1 = shape.y1 * height;

  if (shape.type === 'circle') {
    ctx.beginPath();
    ctx.arc(x1, y1, (shape.r ?? 0.05) * Math.min(width, height), 0, Math.PI * 2);
    ctx.stroke();
    return;
  }

  const x2 = (shape.x2 ?? shape.x1) * width;
  const y2 = (shape.y2 ?? shape.y1) * height;
  ctx.beginPath();
  ctx.moveTo(x1, y1);
  ctx.lineTo(x2, y2);
  ctx.stroke();

  if (shape.type === 'arrow') {
    const angle = Math.atan2(y2 - y1, x2 - x1);
    const head = 10;
    ctx.beginPath();
    ctx.moveTo(x2, y2);
    ctx.lineTo(x2 - head * Math.cos(angle - 0.4), y2 - head * Math.sin(angle - 0.4));
    ctx.moveTo(x2, y2);
    ctx.lineTo(x2 - head * Math.cos(angle + 0.4), y2 - head * Math.sin(angle + 0.4));
    ctx.stroke();
  }
}

async function seekTo(annotation) {
  activeAnnotationId.value = annotation.id;
  if (!playing.value) {
    startPlayback();
    await nextTick();
  }
  const applySeek = () => {
    if (videoEl.value) {
      videoEl.value.currentTime = annotation.timestamp_ms / 1000;
    }
  };
  if (videoEl.value && videoEl.value.readyState >= 1) {
    applySeek();
    return;
  }
  const onMeta = () => {
    applySeek();
    videoEl.value?.removeEventListener('loadedmetadata', onMeta);
  };
  videoEl.value?.addEventListener('loadedmetadata', onMeta);
}

function resetState() {
  playing.value = false;
  isBuffering.value = false;
  showBufferOverlay.value = false;
  bufferPercent.value = 0;
  hasFirstFrame.value = false;
  currentMs.value = 0;
  clearTimeout(bufferOverlayTimer);
}

watch(
  () => props.video.id,
  () => {
    resetState();
    localAnnotations.value = [...(props.video.annotations ?? [])];
  },
);

watch(
  () => props.video.annotations,
  (value) => {
    localAnnotations.value = [...(value ?? [])];
  },
  { deep: true },
);

onMounted(() => {
  window.addEventListener('resize', resizeCanvas);
});

onUnmounted(() => {
  window.removeEventListener('resize', resizeCanvas);
  clearTimeout(bufferOverlayTimer);
});
</script>

<template>
  <div class="space-y-3 rounded-xl border border-slate-800 bg-slate-950/40 p-3 sm:p-4">
    <div class="relative mx-auto w-fit max-w-full overflow-hidden rounded-lg bg-black">
      <video
        ref="videoEl"
        :src="video.url"
        :controls="playing"
        playsinline
        preload="auto"
        muted
        class="mx-auto block max-h-[50vh] w-auto max-w-full bg-black"
        @timeupdate="onTimeUpdate"
        @loadedmetadata="onLoadedMetadata"
        @loadeddata="onLoadedData"
        @progress="onProgress"
        @waiting="onWaiting"
        @canplay="onCanPlay"
        @playing="onCanPlay"
      />
      <canvas
        v-if="playing"
        ref="canvasEl"
        class="pointer-events-none absolute inset-0 h-full w-full"
      />

      <div
        v-if="!playing"
        class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/75 px-6"
      >
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-500"
          @click="startPlayback"
        >
          <span aria-hidden="true">&#9654;</span>
          Lire la vidéo
        </button>
        <p v-if="sizeLabel || bufferPercent > 0" class="text-xs text-slate-300">
          <span v-if="sizeLabel">{{ sizeLabel }}</span>
          <span v-if="bufferPercent > 0" class="text-slate-400">
            <span v-if="sizeLabel"> · </span>préchargé {{ bufferPercent }} %
          </span>
        </p>
        <a
          :href="video.url"
          target="_blank"
          rel="noopener"
          class="text-xs font-medium text-blue-400 hover:text-blue-300"
        >
          Ouvrir dans un nouvel onglet
        </a>
      </div>

      <div
        v-if="playing && showBufferOverlay"
        class="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 bg-black/70 px-3 py-2"
      >
        <div
          class="h-4 w-4 animate-spin rounded-full border-2 border-slate-500 border-t-blue-400"
          aria-hidden="true"
        />
        <p class="text-xs text-slate-300">
          Chargement{{ bufferPercent > 0 ? ` ${bufferPercent} %` : '…' }}
        </p>
      </div>
    </div>

    <div v-if="localAnnotations.length">
      <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Annotations</h4>
      <ul class="mt-2 space-y-2">
        <li
          v-for="annotation in localAnnotations"
          :key="annotation.id"
          class="rounded-lg border border-slate-800 bg-slate-900/60 px-3 py-2 text-sm"
        >
          <button type="button" class="text-left text-slate-200 hover:text-white" @click="seekTo(annotation)">
            <span class="font-mono text-blue-400">{{ formatTime(annotation.timestamp_ms) }}</span>
            <span v-if="annotation.body" class="mt-1 block text-slate-300">{{ annotation.body }}</span>
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>
