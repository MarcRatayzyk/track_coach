<script setup>
import { useI18n } from 'vue-i18n';
import { computed, ref, watch } from 'vue';
import VideoAnnotator from './VideoAnnotator.vue';

const { t } = useI18n();

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

function onKeydown(event) {
  if (event.key === 'ArrowLeft') {
    event.preventDefault();
    prev();
  } else if (event.key === 'ArrowRight') {
    event.preventDefault();
    next();
  }
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
  <div
    v-if="total"
    class="space-y-4"
    tabindex="0"
    @keydown="onKeydown"
  >
    <div class="flex items-center justify-between gap-3">
      <h3 class="text-sm font-medium text-slate-400">
        {{ t('app.feedbacks.videos') }}
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
          class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-700 text-slate-300 transition duration-200 hover:bg-slate-800 disabled:opacity-40"
          :aria-label="t('app.feedbacks.prevVideo')"
          @click="prev"
        >
          &#8592;
        </button>
        <button
          type="button"
          :disabled="currentIndex === total - 1"
          class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-700 text-slate-300 transition duration-200 hover:bg-slate-800 disabled:opacity-40"
          :aria-label="t('app.feedbacks.nextVideo')"
          @click="next"
        >
          &#8594;
        </button>
      </div>
    </div>

    <div
      v-if="hasMultiple"
      class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:thin]"
    >
      <button
        v-for="(video, index) in videos"
        :key="video.id"
        type="button"
        class="group relative h-16 w-24 shrink-0 overflow-hidden rounded-[12px] border transition duration-200"
        :class="
          index === currentIndex
            ? 'border-blue-500/70 shadow-[0_0_16px_rgba(59,130,246,0.25)]'
            : 'border-slate-800 hover:border-slate-600'
        "
        :aria-label="t('app.feedbacks.videoN', { n: index + 1 })"
        @click="goTo(index)"
      >
        <video
          :src="video.url"
          muted
          preload="metadata"
          class="h-full w-full object-cover opacity-80 transition duration-200 group-hover:opacity-100"
        />
        <span
          class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/90 to-transparent px-1.5 py-1 text-[9px] font-medium text-slate-200"
        >
          {{ index + 1 }}
          <template v-if="video.series?.exercise_name">
            · {{ video.series.exercise_name }}
          </template>
        </span>
      </button>
    </div>

    <VideoAnnotator v-if="currentVideo" :key="currentVideo.id" :video="currentVideo" />

    <div v-if="hasMultiple" class="flex items-center justify-center gap-1.5">
      <button
        v-for="(video, index) in videos"
        :key="`dot-${video.id}`"
        type="button"
        class="h-2 rounded-full transition-all duration-200"
        :class="index === currentIndex ? 'w-5 bg-blue-500' : 'w-2 bg-slate-700 hover:bg-slate-600'"
        :aria-label="t('app.feedbacks.goToVideo', { n: index + 1 })"
        @click="goTo(index)"
      />
    </div>
  </div>
</template>
