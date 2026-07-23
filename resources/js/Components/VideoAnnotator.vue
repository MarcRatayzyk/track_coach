<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
  video: { type: Object, required: true },
});

const videoEl = ref(null);
const canvasEl = ref(null);
const currentMs = ref(0);
const localAnnotations = ref([...(props.video.annotations ?? [])]);
const activeAnnotationId = ref(null);

const visibleAnnotations = computed(() =>
  localAnnotations.value.filter((item) => Math.abs(item.timestamp_ms - currentMs.value) < 1500),
);

function formatTime(ms) {
  const totalSeconds = Math.floor(ms / 1000);
  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  return `${minutes}:${String(seconds).padStart(2, '0')}`;
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

function seekTo(annotation) {
  activeAnnotationId.value = annotation.id;
  if (videoEl.value) {
    videoEl.value.currentTime = annotation.timestamp_ms / 1000;
  }
}

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
});
</script>

<template>
  <div class="space-y-4 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
    <div class="relative mx-auto w-fit max-w-full">
      <video
        ref="videoEl"
        :src="video.url"
        controls
        preload="metadata"
        class="mx-auto block max-h-[55vh] w-auto max-w-full rounded-lg bg-black"
        @timeupdate="onTimeUpdate"
        @loadedmetadata="onLoadedMetadata"
      />
      <canvas
        ref="canvasEl"
        class="pointer-events-none absolute inset-0 h-full w-full rounded-lg"
      />
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
