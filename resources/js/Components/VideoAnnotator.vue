<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
  video: { type: Object, required: true },
  readonly: { type: Boolean, default: false },
});

const emit = defineEmits(['updated']);

const videoEl = ref(null);
const canvasEl = ref(null);
const currentMs = ref(0);
const durationMs = ref(0);
const draftBody = ref('');
const drawingMode = ref('line');
const draftShapes = ref([]);
const isDrawing = ref(false);
const drawStart = ref(null);
const localAnnotations = ref([...(props.video.annotations ?? [])]);
const activeAnnotationId = ref(null);

const activeAnnotation = computed(() =>
  localAnnotations.value.find((item) => item.id === activeAnnotationId.value) ?? null,
);

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
  if (!videoEl.value) {
    return;
  }
  durationMs.value = Math.round(videoEl.value.duration * 1000);
  resizeCanvas();
  redrawCanvas();
}

function resizeCanvas() {
  if (!videoEl.value || !canvasEl.value) {
    return;
  }
  canvasEl.value.width = videoEl.value.clientWidth;
  canvasEl.value.height = videoEl.value.clientHeight;
}

function normalizedPoint(event) {
  const rect = canvasEl.value.getBoundingClientRect();
  return {
    x: (event.clientX - rect.left) / rect.width,
    y: (event.clientY - rect.top) / rect.height,
  };
}

function startDraw(event) {
  if (props.readonly) {
    return;
  }
  isDrawing.value = true;
  drawStart.value = normalizedPoint(event);
}

function moveDraw(event) {
  if (!isDrawing.value || !drawStart.value) {
    return;
  }
  const end = normalizedPoint(event);
  const start = drawStart.value;
  if (drawingMode.value === 'line' || drawingMode.value === 'arrow') {
    draftShapes.value = [{ type: drawingMode.value, x1: start.x, y1: start.y, x2: end.x, y2: end.y }];
  } else {
    const dx = end.x - start.x;
    const dy = end.y - start.y;
    const r = Math.sqrt(dx * dx + dy * dy);
    draftShapes.value = [{ type: 'circle', x1: start.x, y1: start.y, r }];
  }
  redrawCanvas();
}

function endDraw() {
  isDrawing.value = false;
}

function redrawCanvas() {
  const canvas = canvasEl.value;
  if (!canvas) {
    return;
  }
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  const shapesToDraw = [
    ...visibleAnnotations.value.flatMap((item) => item.shapes ?? []),
    ...draftShapes.value,
  ];

  shapesToDraw.forEach((shape) => drawShape(ctx, shape, canvas.width, canvas.height));
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

async function apiRequest(url, method, body = null) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
  const response = await fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrf,
    },
    body: body ? JSON.stringify(body) : undefined,
  });

  if (!response.ok) {
    throw new Error('Request failed');
  }

  return response.json();
}

async function saveAnnotation() {
  const payload = {
    timestamp_ms: currentMs.value,
    body: draftBody.value || null,
    shapes: draftShapes.value,
  };

  const data = await apiRequest(`/coach/session-feedback-media/${props.video.id}/annotations`, 'POST', payload);
  localAnnotations.value.push(data);
  draftBody.value = '';
  draftShapes.value = [];
  emit('updated', localAnnotations.value);
}

async function removeAnnotation(annotation) {
  await apiRequest(`/coach/session-feedback-annotations/${annotation.id}`, 'DELETE');
  localAnnotations.value = localAnnotations.value.filter((item) => item.id !== annotation.id);
  if (activeAnnotationId.value === annotation.id) {
    activeAnnotationId.value = null;
  }
  emit('updated', localAnnotations.value);
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
    <div class="relative">
      <video
        ref="videoEl"
        :src="video.url"
        controls
        class="w-full rounded-lg bg-black"
        @timeupdate="onTimeUpdate"
        @loadedmetadata="onLoadedMetadata"
      />
      <canvas
        ref="canvasEl"
        class="pointer-events-none absolute inset-0 h-full w-full rounded-lg"
        :class="{ 'pointer-events-auto cursor-crosshair': !readonly }"
        @mousedown="startDraw"
        @mousemove="moveDraw"
        @mouseup="endDraw"
        @mouseleave="endDraw"
      />
    </div>

    <div v-if="!readonly" class="flex flex-wrap items-center gap-2 text-sm">
      <span class="text-slate-400">Outil :</span>
      <button
        v-for="tool in ['line', 'arrow', 'circle']"
        :key="tool"
        type="button"
        class="rounded-lg border px-2 py-1 capitalize"
        :class="drawingMode === tool ? 'border-blue-500 text-blue-300' : 'border-slate-700 text-slate-400'"
        @click="drawingMode = tool"
      >
        {{ tool }}
      </button>
      <span class="ml-auto text-slate-500">{{ formatTime(currentMs) }}</span>
    </div>

    <div v-if="!readonly" class="space-y-2">
      <textarea
        v-model="draftBody"
        rows="2"
        placeholder="Commentaire à ce timecode…"
        class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-white"
      />
      <button
        type="button"
        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500"
        @click="saveAnnotation"
      >
        Enregistrer l’annotation
      </button>
    </div>

    <div v-if="localAnnotations.length">
      <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Annotations</h4>
      <ul class="mt-2 space-y-2">
        <li
          v-for="annotation in localAnnotations"
          :key="annotation.id"
          class="flex items-start justify-between gap-3 rounded-lg border border-slate-800 bg-slate-900/60 px-3 py-2 text-sm"
        >
          <button type="button" class="text-left text-slate-200 hover:text-white" @click="seekTo(annotation)">
            <span class="font-mono text-blue-400">{{ formatTime(annotation.timestamp_ms) }}</span>
            <span v-if="annotation.body" class="mt-1 block text-slate-300">{{ annotation.body }}</span>
          </button>
          <button
            v-if="!readonly"
            type="button"
            class="text-xs text-red-400 hover:text-red-300"
            @click="removeAnnotation(annotation)"
          >
            Supprimer
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>
