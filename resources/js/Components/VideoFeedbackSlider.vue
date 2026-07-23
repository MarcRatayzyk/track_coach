<script setup>
import { computed, ref, watch } from 'vue';
import VideoAnnotator from './VideoAnnotator.vue';

const props = defineProps({
  videos: { type: Array, default: () => [] },
});

const currentIndex = ref(0);

const total = computed(() => props.videos.length);
const hasMultiple = computed(() => total.value > 1);
const currentVideo = computed(() => props.videos[currentIndex.value] ?? null);
const currentSeries = computed(() => currentVideo.value?.series ?? null);

const loadLabel = computed(() => {
  const series = currentSeries.value;
  if (!series) {
    return null;
  }
  if (series.load !== null && series.load !== undefined && series.load !== '') {
    return `${series.load} kg`;
  }
  if (series.load_percent !== null && series.load_percent !== undefined && series.load_percent !== '') {
    return `${series.load_percent} %`;
  }
  return null;
});

const metrics = computed(() => {
  const series = currentSeries.value;
  if (!series) {
    return [];
  }
  return [
    { label: 'Séries', value: series.sets ?? '—' },
    { label: 'Reps', value: series.reps ?? '—' },
    { label: 'Charge', value: loadLabel.value ?? '—' },
    { label: 'RPE', value: series.rpe ?? '—' },
  ];
});

function goTo(index) {
  if (index < 0 || index >= total.value) {
    return;
  }
  currentIndex.value = index;
}

function prev() {
  goTo(currentIndex.value - 1);
}

function next() {
  goTo(currentIndex.value + 1);
}

watch(
  () => props.videos,
  () => {
    if (currentIndex.value >= total.value) {
      currentIndex.value = 0;
    }
  },
);
</script>

<template>
  <div v-if="total" class="space-y-4">
    <div class="flex items-center justify-between gap-3">
      <h3 class="text-sm font-medium text-slate-400">
        Vidéos
        <span v-if="hasMultiple" class="ml-1 text-slate-500">
          ({{ currentIndex + 1 }}/{{ total }})
        </span>
      </h3>
      <div v-if="hasMultiple" class="flex items-center gap-2">
        <button
          type="button"
          :disabled="currentIndex === 0"
          class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-700 text-slate-300 transition hover:bg-slate-800 disabled:opacity-40"
          aria-label="Vidéo précédente"
          @click="prev"
        >
          &#8592;
        </button>
        <button
          type="button"
          :disabled="currentIndex === total - 1"
          class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-700 text-slate-300 transition hover:bg-slate-800 disabled:opacity-40"
          aria-label="Vidéo suivante"
          @click="next"
        >
          &#8594;
        </button>
      </div>
    </div>

    <VideoAnnotator v-if="currentVideo" :key="currentVideo.id" :video="currentVideo" />

    <div
      v-if="currentSeries"
      class="rounded-xl border border-blue-500/30 bg-blue-950/20 p-4"
    >
      <p class="text-sm font-semibold text-white">
        {{ currentSeries.exercise_name || 'Série' }}
        <span v-if="currentSeries.section_label" class="text-slate-400">
          — {{ currentSeries.section_label }}
        </span>
      </p>
      <dl class="mt-3 grid grid-cols-4 gap-2 text-center">
        <div
          v-for="metric in metrics"
          :key="metric.label"
          class="rounded-lg bg-slate-950/60 px-2 py-2"
        >
          <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">
            {{ metric.label }}
          </dt>
          <dd class="mt-1 text-sm font-semibold text-white">{{ metric.value }}</dd>
        </div>
      </dl>
    </div>

    <div v-if="hasMultiple" class="flex items-center justify-center gap-1.5">
      <button
        v-for="(video, index) in videos"
        :key="video.id"
        type="button"
        class="h-2 rounded-full transition-all"
        :class="index === currentIndex ? 'w-5 bg-blue-500' : 'w-2 bg-slate-700 hover:bg-slate-600'"
        :aria-label="`Aller à la vidéo ${index + 1}`"
        @click="goTo(index)"
      />
    </div>
  </div>
</template>
