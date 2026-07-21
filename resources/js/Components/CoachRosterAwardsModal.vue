<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  awards: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const slideIndex = ref(0);

const slides = computed(() => {
  const data = props.awards;
  if (!data?.screens?.length) {
    return [];
  }

  return [
    {
      id: 'intro',
      kind: 'intro',
      title: 'Roster Awards',
      subtitle: data.month_label ?? 'Ce mois',
      hint: 'Les podiums un peu décalés de ton groupe →',
    },
    ...data.screens,
    {
      id: 'outro',
      kind: 'outro',
      title: 'Fin du podium',
      subtitle: 'On remet ça le mois prochain. (Avec un peu de sommeil, promis.)',
    },
  ];
});

const currentSlide = computed(() => slides.value[slideIndex.value] ?? null);
const isFirst = computed(() => slideIndex.value <= 0);
const isLast = computed(() => slideIndex.value >= slides.value.length - 1);

watch(
  () => props.open,
  (open) => {
    if (open) {
      slideIndex.value = 0;
    }
  },
);

function close() {
  emit('close');
}

function next() {
  if (isLast.value) {
    close();
    return;
  }
  slideIndex.value += 1;
}

function prev() {
  if (!isFirst.value) {
    slideIndex.value -= 1;
  }
}

const awardAccent = computed(() => {
  const key = currentSlide.value?.award_key;
  if (key === 'steps') {
    return 'from-emerald-900/80 via-slate-950 to-slate-950';
  }
  if (key === 'kcal') {
    return 'from-amber-900/80 via-slate-950 to-slate-950';
  }
  if (key === 'sommeil') {
    return 'from-sky-900/80 via-slate-950 to-slate-950';
  }
  return 'from-violet-950 via-slate-950 to-slate-950';
});
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && awards"
      class="fixed inset-0 z-[70] flex flex-col text-white"
      :class="['bg-gradient-to-b', awardAccent]"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex items-center justify-between px-4 pb-2 pt-[max(0.75rem,env(safe-area-inset-top))]">
        <div class="flex gap-1">
          <span
            v-for="(slide, index) in slides"
            :key="slide.id"
            class="h-1 rounded-full transition-all"
            :class="index === slideIndex ? 'w-6 bg-white' : 'w-2 bg-white/30'"
          />
        </div>
        <button
          type="button"
          class="rounded-lg p-2 text-slate-300 hover:bg-white/10 hover:text-white"
          aria-label="Fermer"
          @click="close"
        >
          ✕
        </button>
      </div>

      <div class="relative flex flex-1 flex-col px-6 pb-6" @click="next">
        <div class="flex flex-1 flex-col justify-center">
          <template v-if="currentSlide?.kind === 'intro'">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-200">Monthly Wrapped</p>
            <h2 class="mt-4 text-4xl font-bold leading-tight">{{ currentSlide.title }}</h2>
            <p class="mt-3 text-lg text-slate-200">{{ currentSlide.subtitle }}</p>
            <p class="mt-8 text-sm text-slate-400">{{ currentSlide.hint }}</p>
          </template>

          <template v-else-if="currentSlide?.kind === 'roster_award'">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">
              {{ currentSlide.eyebrow }}
            </p>
            <h2 class="mt-4 text-3xl font-bold leading-tight sm:text-4xl">
              {{ currentSlide.title }}
            </h2>
            <p class="mt-10 text-5xl font-bold tracking-tight sm:text-6xl">
              {{ currentSlide.athlete_name }}
            </p>
            <p class="mt-4 text-2xl font-semibold text-white/90">
              {{ currentSlide.value_label }}
            </p>
            <p class="mt-8 text-base text-slate-200/90">
              {{ currentSlide.punchline }}
            </p>
            <p class="mt-4 text-xs text-slate-400">
              {{ currentSlide.footnote }}
            </p>
          </template>

          <template v-else-if="currentSlide?.kind === 'outro'">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-200">À suivre</p>
            <h2 class="mt-4 text-3xl font-bold">{{ currentSlide.title }}</h2>
            <p class="mt-3 text-lg text-slate-300">{{ currentSlide.subtitle }}</p>
          </template>
        </div>

        <div class="flex items-center justify-between gap-3 pt-4">
          <button
            type="button"
            class="rounded-xl border border-white/20 px-4 py-2 text-sm text-slate-200 hover:bg-white/10 disabled:opacity-30"
            :disabled="isFirst"
            @click.stop="prev"
          >
            Retour
          </button>
          <button
            type="button"
            class="rounded-xl bg-white/15 px-5 py-2 text-sm font-semibold text-white hover:bg-white/25"
            @click.stop="next"
          >
            {{ isLast ? 'Fermer' : 'Suivant' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
