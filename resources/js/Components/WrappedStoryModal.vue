<script setup>
import { computed, ref, watch } from 'vue';
import { motion } from 'motion-v';
import AnimatedCounter from './Dashboard/AnimatedCounter.vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  wrapped: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'share']);

const slideIndex = ref(0);
/** Index de la métrique révélée sur un écran lift (0 = barre, 1 = e1RM, 2 = tonnage). */
const liftStep = ref(0);

const LIFT_METRICS = [
  { key: 'heaviest_bar', headline: 'Barre la plus lourde', unit: 'kg', hint: 'Le max mis sur la barre' },
  { key: 'tonnage', headline: 'Tonnage', unit: 'kg', hint: 'Volume total sur le mouvement' },
  { key: 'top_e1rm', headline: 'e1RM le plus élevé', unit: 'kg', hint: 'Estimation 1RM' },
];

const LIFT_THEMES = {
  squat: {
    accent: 'from-blue-950 via-slate-950 to-slate-950',
    glow: 'bg-blue-500/30',
    chip: 'border-blue-500/30 bg-blue-500/15 text-blue-200',
    button: 'bg-blue-600 hover:bg-blue-500 shadow-blue-900/40',
  },
  bench: {
    accent: 'from-amber-950 via-slate-950 to-slate-950',
    glow: 'bg-amber-500/30',
    chip: 'border-amber-500/30 bg-amber-500/15 text-amber-200',
    button: 'bg-amber-600 hover:bg-amber-500 shadow-amber-900/40',
  },
  deadlift: {
    accent: 'from-rose-950 via-slate-950 to-slate-950',
    glow: 'bg-rose-500/30',
    chip: 'border-rose-500/30 bg-rose-500/15 text-rose-200',
    button: 'bg-rose-600 hover:bg-rose-500 shadow-rose-900/40',
  },
};

const slides = computed(() => {
  const data = props.wrapped;
  if (!data) {
    return [];
  }

  const list = [
    {
      id: 'intro',
      kind: 'intro',
      title: data.label,
      subtitle: `${data.period_start} → ${data.period_end}`,
    },
    {
      id: 'overview',
      kind: 'overview',
      title: 'Ton volume',
      metrics: data.overview ?? {},
    },
  ];

  for (const lift of data.lifts ?? []) {
    list.push({
      id: `lift-${lift.key}`,
      kind: 'lift',
      lift,
      title: lift.label,
    });
  }

  list.push({
    id: 'outro',
    kind: 'outro',
    title: data.label,
    subtitle: 'Continue comme ça',
  });

  return list;
});

const currentSlide = computed(() => slides.value[slideIndex.value] ?? null);
const isFirst = computed(() => slideIndex.value <= 0);
const isLast = computed(() => slideIndex.value >= slides.value.length - 1);
const isLift = computed(() => currentSlide.value?.kind === 'lift');

const liftTheme = computed(() => {
  const key = currentSlide.value?.lift?.key;
  return LIFT_THEMES[key] ?? {
    accent: 'from-violet-950 via-slate-950 to-slate-950',
    glow: 'bg-violet-500/30',
    chip: 'border-violet-500/30 bg-violet-500/15 text-violet-200',
    button: 'bg-violet-600 hover:bg-violet-500 shadow-violet-900/40',
  };
});

const revealedLiftMetrics = computed(() => {
  if (!isLift.value) return [];
  return LIFT_METRICS.slice(0, liftStep.value + 1);
});

const currentLiftMetric = computed(() => LIFT_METRICS[liftStep.value] ?? null);
const canRevealMoreLift = computed(() => isLift.value && liftStep.value < LIFT_METRICS.length - 1);

const bgClass = computed(() => {
  if (isLift.value) return liftTheme.value.accent;
  return 'from-violet-950 via-slate-950 to-slate-950';
});

const primaryButtonClass = computed(() => {
  if (isLift.value) return liftTheme.value.button;
  return 'bg-violet-600 hover:bg-violet-500 shadow-violet-900/40';
});

watch(
  () => props.open,
  (open) => {
    if (open) {
      slideIndex.value = 0;
      liftStep.value = 0;
    }
  },
);

watch(slideIndex, () => {
  liftStep.value = 0;
});

function close() {
  emit('close');
}

function nextSlide() {
  if (isLast.value) {
    close();
    return;
  }
  slideIndex.value += 1;
}

function next() {
  if (canRevealMoreLift.value) {
    liftStep.value += 1;
    return;
  }
  nextSlide();
}

function prev() {
  if (isLift.value && liftStep.value > 0) {
    liftStep.value -= 1;
    return;
  }
  if (!isFirst.value) {
    slideIndex.value -= 1;
  }
}

function formatDelta(delta) {
  if (!delta) {
    return null;
  }

  const sign = delta.direction === 'up' ? '+' : delta.direction === 'down' ? '−' : '';
  const value = Math.abs(delta.absolute ?? 0);
  const pct = delta.percent;

  if (pct === null) {
    return `${sign}${value}`;
  }

  const pctSign = delta.direction === 'down' ? '−' : '+';
  const pctValue = Math.abs(pct);

  return `${sign}${value} (${pctSign}${pctValue}%)`;
}

function deltaClass(delta) {
  if (!delta) {
    return 'text-slate-400';
  }
  if (delta.direction === 'up') {
    return 'text-emerald-400';
  }
  if (delta.direction === 'down') {
    return 'text-rose-400';
  }
  return 'text-slate-400';
}

function metricValue(metric) {
  if (!metric || metric.value === null || metric.value === undefined) {
    return null;
  }
  return Number(metric.value);
}

function nextLabel() {
  if (canRevealMoreLift.value) {
    const nextMetric = LIFT_METRICS[liftStep.value + 1];
    return nextMetric?.headline ?? 'Suivant';
  }
  if (isLast.value) {
    return 'Fermer';
  }
  if (isLift.value) {
    return 'Mouvement suivant';
  }
  return 'Suivant';
}

const overviewItems = [
  { key: 'total_tonnage', label: 'Tonnage', suffix: 'kg' },
  { key: 'adherence_percent', label: 'Adhérence', suffix: '%' },
  { key: 'total_sets', label: 'Séries', suffix: '' },
  { key: 'total_reps', label: 'Reps', suffix: '' },
  { key: 'tonnage_per_set', label: 'Tonnage / série', suffix: 'kg' },
];
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && wrapped"
      class="fixed inset-0 z-[70] flex flex-col bg-gradient-to-b text-white transition-[background] duration-500"
      :class="bgClass"
      role="dialog"
      aria-modal="true"
    >
      <div
        class="pointer-events-none absolute inset-x-0 top-0 h-56 opacity-70 blur-3xl transition-colors duration-500"
        :class="isLift ? liftTheme.glow : 'bg-violet-500/25'"
        aria-hidden="true"
      />

      <div class="relative flex items-center justify-between px-4 pb-2 pt-[max(0.75rem,env(safe-area-inset-top))]">
        <div class="flex min-w-0 flex-1 gap-1 pr-3">
          <span
            v-for="(slide, index) in slides"
            :key="slide.id"
            class="h-1 flex-1 rounded-full transition-all duration-300"
            :class="index <= slideIndex ? 'bg-white/80' : 'bg-white/15'"
          />
        </div>
        <button
          type="button"
          class="rounded-lg p-2 text-slate-400 transition hover:bg-white/10 hover:text-white"
          aria-label="Fermer"
          @click="close"
        >
          ✕
        </button>
      </div>

      <div class="relative flex min-h-0 flex-1 flex-col px-5 pb-[max(1rem,env(safe-area-inset-bottom))] sm:px-6">
        <div class="flex min-h-0 flex-1 flex-col justify-center overflow-y-auto py-4">
          <!-- INTRO -->
          <motion.div
            v-if="currentSlide?.kind === 'intro'"
            :key="`intro-${slideIndex}`"
            :initial="{ opacity: 0, y: 28, scale: 0.96 }"
            :animate="{ opacity: 1, y: 0, scale: 1 }"
            :transition="{ duration: 0.45, ease: [0.22, 1, 0.36, 1] }"
            class="text-center"
          >
            <motion.p
              class="text-xs font-semibold uppercase tracking-[0.25em] text-violet-300"
              :initial="{ opacity: 0, y: 10 }"
              :animate="{ opacity: 1, y: 0 }"
              :transition="{ delay: 0.1, duration: 0.35 }"
            >
              Wrapped
            </motion.p>
            <motion.h2
              class="mt-5 text-4xl font-bold leading-tight sm:text-5xl"
              :initial="{ opacity: 0, y: 18 }"
              :animate="{ opacity: 1, y: 0 }"
              :transition="{ delay: 0.2, duration: 0.45, ease: [0.22, 1, 0.36, 1] }"
            >
              {{ currentSlide.title }}
            </motion.h2>
            <motion.p
              class="mt-4 text-lg text-slate-300"
              :initial="{ opacity: 0 }"
              :animate="{ opacity: 1 }"
              :transition="{ delay: 0.35, duration: 0.4 }"
            >
              {{ currentSlide.subtitle }}
            </motion.p>
            <motion.p
              class="mt-10 text-sm text-slate-400"
              :initial="{ opacity: 0 }"
              :animate="{ opacity: 1 }"
              :transition="{ delay: 0.55, duration: 0.4 }"
            >
              Découvre ton récap lift par lift →
            </motion.p>
          </motion.div>

          <!-- OVERVIEW -->
          <motion.div
            v-else-if="currentSlide?.kind === 'overview'"
            :key="`overview-${slideIndex}`"
            :initial="{ opacity: 0, y: 20 }"
            :animate="{ opacity: 1, y: 0 }"
            :transition="{ duration: 0.4, ease: [0.22, 1, 0.36, 1] }"
          >
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-300">Volume</p>
            <h2 class="mt-3 text-2xl font-bold sm:text-3xl">{{ currentSlide.title }}</h2>
            <div class="mt-7 grid gap-2.5">
              <motion.div
                v-for="(item, index) in overviewItems"
                :key="item.key"
                class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur-sm"
                :initial="{ opacity: 0, x: -16 }"
                :animate="{ opacity: 1, x: 0 }"
                :transition="{ delay: 0.08 + index * 0.07, duration: 0.35, ease: [0.22, 1, 0.36, 1] }"
              >
                <p class="text-xs text-slate-400">{{ item.label }}</p>
                <div class="mt-1 flex items-end justify-between gap-3">
                  <p class="text-2xl font-bold tabular-nums">
                    <template v-if="metricValue(currentSlide.metrics[item.key]) !== null">
                      <AnimatedCounter
                        :value="metricValue(currentSlide.metrics[item.key])"
                        :decimals="item.key === 'tonnage_per_set' ? 1 : 0"
                      />
                      <span
                        v-if="item.suffix"
                        class="ml-1 text-sm font-medium text-slate-400"
                      >{{ item.suffix }}</span>
                    </template>
                    <template v-else>—</template>
                  </p>
                  <p
                    v-if="currentSlide.metrics[item.key]?.delta"
                    class="text-xs font-semibold"
                    :class="deltaClass(currentSlide.metrics[item.key].delta)"
                  >
                    {{ formatDelta(currentSlide.metrics[item.key].delta) }}
                  </p>
                </div>
              </motion.div>
            </div>
          </motion.div>

          <!-- LIFT (1 screen / mouvement, métriques en cascade) -->
          <div
            v-else-if="currentSlide?.kind === 'lift'"
            :key="`lift-${currentSlide.lift.key}-${slideIndex}`"
            class="relative"
          >
            <motion.div
              :initial="{ opacity: 0, y: 24, scale: 0.97 }"
              :animate="{ opacity: 1, y: 0, scale: 1 }"
              :transition="{ duration: 0.45, ease: [0.22, 1, 0.36, 1] }"
            >
              <div class="flex items-center gap-2">
                <span
                  class="rounded-full border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]"
                  :class="liftTheme.chip"
                >
                  {{ currentSlide.title }}
                </span>
                <span class="text-[11px] text-slate-500">
                  {{ liftStep + 1 }}/{{ LIFT_METRICS.length }}
                </span>
              </div>

              <motion.h2
                :key="`headline-${liftStep}`"
                class="mt-4 text-2xl font-bold sm:text-3xl"
                :initial="{ opacity: 0, y: 14 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ duration: 0.35, ease: [0.22, 1, 0.36, 1] }"
              >
                {{ currentLiftMetric?.headline }}
              </motion.h2>
              <motion.p
                :key="`hint-${liftStep}`"
                class="mt-1.5 text-sm text-slate-400"
                :initial="{ opacity: 0 }"
                :animate="{ opacity: 1 }"
                :transition="{ delay: 0.1, duration: 0.3 }"
              >
                {{ currentLiftMetric?.hint }}
              </motion.p>

              <motion.div
                :key="`hero-${currentSlide.lift.key}-${liftStep}`"
                class="mt-8"
                :initial="{ opacity: 0, scale: 0.85, y: 20 }"
                :animate="{ opacity: 1, scale: 1, y: 0 }"
                :transition="{ duration: 0.5, ease: [0.22, 1, 0.36, 1] }"
              >
                <p class="text-6xl font-bold tracking-tight tabular-nums sm:text-7xl">
                  <template
                    v-if="metricValue(currentSlide.lift?.[currentLiftMetric?.key]) !== null"
                  >
                    <AnimatedCounter
                      :value="metricValue(currentSlide.lift[currentLiftMetric.key])"
                      :decimals="currentLiftMetric.key === 'top_e1rm' ? 1 : 0"
                      :duration="900"
                    />
                    <span class="ml-2 text-2xl font-semibold text-slate-400">{{
                      currentLiftMetric.unit
                    }}</span>
                  </template>
                  <template v-else>—</template>
                </p>
                <p
                  v-if="currentSlide.lift?.[currentLiftMetric?.key]?.delta"
                  class="mt-4 text-sm font-semibold"
                  :class="deltaClass(currentSlide.lift[currentLiftMetric.key].delta)"
                >
                  vs {{ wrapped?.comparison_label ?? 'la semaine précédente' }}
                  {{ formatDelta(currentSlide.lift[currentLiftMetric.key].delta) }}
                </p>
              </motion.div>

              <!-- Historique des métriques déjà révélées (sauf la courante) -->
              <div
                v-if="revealedLiftMetrics.length > 1"
                class="mt-8 space-y-2 border-t border-white/10 pt-5"
              >
                <motion.div
                  v-for="(metric, index) in revealedLiftMetrics.slice(0, -1)"
                  :key="metric.key"
                  class="flex items-center justify-between rounded-xl border border-white/8 bg-white/5 px-3 py-2.5"
                  :initial="{ opacity: 0, y: 8 }"
                  :animate="{ opacity: 0.85, y: 0 }"
                  :transition="{ delay: index * 0.05, duration: 0.25 }"
                >
                  <span class="text-xs text-slate-400">{{ metric.headline }}</span>
                  <span class="text-sm font-semibold tabular-nums text-slate-200">
                    <template v-if="metricValue(currentSlide.lift?.[metric.key]) !== null">
                      {{ metricValue(currentSlide.lift[metric.key]) }}
                      {{ metric.unit }}
                    </template>
                    <template v-else>—</template>
                  </span>
                </motion.div>
              </div>
            </motion.div>
          </div>

          <!-- OUTRO -->
          <motion.div
            v-else-if="currentSlide?.kind === 'outro'"
            :key="`outro-${slideIndex}`"
            class="text-center"
            :initial="{ opacity: 0, y: 24, scale: 0.96 }"
            :animate="{ opacity: 1, y: 0, scale: 1 }"
            :transition="{ duration: 0.45, ease: [0.22, 1, 0.36, 1] }"
          >
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-300">Bravo</p>
            <h2 class="mt-4 text-3xl font-bold sm:text-4xl">{{ currentSlide.title }}</h2>
            <p class="mt-3 text-lg text-slate-300">{{ currentSlide.subtitle }}</p>
            <motion.button
              type="button"
              class="mt-10 w-full rounded-2xl bg-violet-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-violet-900/40 transition hover:bg-violet-500"
              :initial="{ opacity: 0, y: 12 }"
              :animate="{ opacity: 1, y: 0 }"
              :transition="{ delay: 0.25, duration: 0.35 }"
              @click="emit('share', wrapped.share_payload)"
            >
              Partager mon recap
            </motion.button>
          </motion.div>
        </div>

        <div class="flex shrink-0 items-center justify-between gap-3 pt-3">
          <button
            type="button"
            class="rounded-xl border border-white/15 px-4 py-2.5 text-sm text-slate-300 transition hover:bg-white/5 disabled:opacity-30"
            :disabled="isFirst && liftStep === 0"
            @click="prev"
          >
            Retour
          </button>

          <motion.button
            :key="`next-${slideIndex}-${liftStep}`"
            type="button"
            class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition"
            :class="primaryButtonClass"
            :initial="{ opacity: 0.6, scale: 0.94 }"
            :animate="{ opacity: 1, scale: 1 }"
            :transition="{ duration: 0.25 }"
            @click="next"
          >
            <span>{{ nextLabel() }}</span>
            <svg
              v-if="!isLast || canRevealMoreLift"
              class="h-4 w-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="2.25"
              stroke="currentColor"
              aria-hidden="true"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
          </motion.button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
