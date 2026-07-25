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
        <span
          v-if="currentVideo?.series?.exercise_name"
          class="ml-2 font-normal normal-case text-slate-500"
        >
          · {{ currentVideo.series.exercise_name }}
          <template v-if="currentVideo.series.section_label">
            — {{ currentVideo.series.section_label }}
          </template>
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
