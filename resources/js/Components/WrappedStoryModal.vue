<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { motion } from 'motion-v';
import AnimatedCounter from './Dashboard/AnimatedCounter.vue';

const { t } = useI18n();

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  wrapped: {
    type: Object,
    default: null,
  },
  theme: {
    type: Object,
    default: null,
  },
  copy: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'share']);

const slideIndex = ref(0);
/** Index de la métrique révélée sur un écran lift (0 = barre, 1 = e1RM, 2 = tonnage). */
const liftStep = ref(0);

const LIFT_METRICS = computed(() => [
  { key: 'heaviest_bar', headline: t('modals.wrapped.heaviestBar'), unit: 'kg', hint: t('modals.wrapped.heaviestBarHint'), decimals: 0 },
  { key: 'tonnage', headline: t('modals.wrapped.tonnage'), unit: 'kg', hint: t('modals.wrapped.tonnageHint'), decimals: 0 },
  { key: 'top_e1rm', headline: t('modals.wrapped.topE1rm'), unit: 'kg', hint: t('modals.wrapped.topE1rmHint'), decimals: 1 },
]);

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

function liftLabel(key) {
  const map = {
    squat: t('modals.wrapped.lifts.squat'),
    bench: t('modals.wrapped.lifts.bench'),
    deadlift: t('modals.wrapped.lifts.deadlift'),
  };
  return map[key] || key;
}

function wrappedTitle(data) {
  if (data?.variant === 'monthly_wrapped') {
    return t('modals.wrapped.monthlyTitle');
  }
  if (data?.variant === 'weekly_wrapped') {
    return t('modals.wrapped.weeklyTitle');
  }
  return data?.label || '';
}

function parseHex(hex) {
  if (!hex || typeof hex !== 'string') {
    return null;
  }
  let h = hex.trim().replace('#', '');
  if (h.length === 3) {
    h = h.split('').map((c) => c + c).join('');
  }
  if (!/^[0-9a-fA-F]{6}$/.test(h)) {
    return null;
  }
  const n = Number.parseInt(h, 16);
  return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255, hex: `#${h}` };
}

function gradientFromHex(hex) {
  const rgb = parseHex(hex);
  if (!rgb) {
    return null;
  }
  return `linear-gradient(to bottom, rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.92), #020617 52%, #020617)`;
}

function rgbaFromHex(hex, alpha) {
  const rgb = parseHex(hex);
  if (!rgb) {
    return null;
  }
  return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`;
}

const brandLabel = computed(() => props.copy?.brand_label || t('modals.wrapped.brand'));
const keepGoingLabel = computed(() => props.copy?.keep_going || t('modals.wrapped.keepGoing'));

const slides = computed(() => {
  const data = props.wrapped;
  if (!data) {
    return [];
  }

  const list = [
    {
      id: 'intro',
      kind: 'intro',
      title: wrappedTitle(data),
      subtitle: `${data.period_start} → ${data.period_end}`,
    },
    {
      id: 'overview',
      kind: 'overview',
      title: t('modals.wrapped.yourVolume'),
      metrics: data.overview ?? {},
    },
  ];

  for (const lift of data.lifts ?? []) {
    list.push({
      id: `lift-${lift.key}`,
      kind: 'lift',
      lift,
      title: liftLabel(lift.key),
    });
  }

  if (data.recap) {
    list.push({
      id: 'recap',
      kind: 'recap',
      title: t('modals.wrapped.recapTitle'),
      subtitle: t('modals.wrapped.recapHint'),
      metrics: data.recap,
    });
  }

  list.push({
    id: 'outro',
    kind: 'outro',
    title: wrappedTitle(data),
    subtitle: keepGoingLabel.value,
  });

  return list;
});

const comparisonLabel = computed(() => {
  if (props.wrapped?.variant === 'monthly_wrapped') {
    return t('modals.wrapped.previousMonth');
  }
  return t('modals.wrapped.previousWeek');
});

const vsLabel = computed(() => t('modals.wrapped.vsPrevious', { label: comparisonLabel.value }));

const currentSlide = computed(() => slides.value[slideIndex.value] ?? null);
const isFirst = computed(() => slideIndex.value <= 0);
const isLast = computed(() => slideIndex.value >= slides.value.length - 1);
const isLift = computed(() => currentSlide.value?.kind === 'lift');

const stackedLiftMetrics = computed(() => {
  if (!isLift.value || liftStep.value <= 0) {
    return [];
  }
  return LIFT_METRICS.value.slice(0, liftStep.value);
});

const currentLiftMetric = computed(() => LIFT_METRICS.value[liftStep.value] ?? null);
const canRevealMoreLift = computed(() => isLift.value && liftStep.value < LIFT_METRICS.value.length - 1);

const activeAccentHex = computed(() => {
  if (!props.theme) {
    return null;
  }
  if (isLift.value) {
    const key = currentSlide.value?.lift?.key;
    return props.theme[key] || props.theme.default_accent || null;
  }
  return props.theme.default_accent || null;
});

const liftTheme = computed(() => {
  const key = currentSlide.value?.lift?.key;
  return LIFT_THEMES[key] ?? {
    accent: 'from-violet-950 via-slate-950 to-slate-950',
    glow: 'bg-violet-500/30',
    chip: 'border-violet-500/30 bg-violet-500/15 text-violet-200',
    button: 'bg-violet-600 hover:bg-violet-500 shadow-violet-900/40',
  };
});

const bgClass = computed(() => {
  if (activeAccentHex.value) {
    return '';
  }
  if (isLift.value) return liftTheme.value.accent;
  return 'from-violet-950 via-slate-950 to-slate-950';
});

const bgStyle = computed(() => {
  const gradient = gradientFromHex(activeAccentHex.value);
  return gradient ? { backgroundImage: gradient } : null;
});

const glowStyle = computed(() => {
  const color = rgbaFromHex(activeAccentHex.value, 0.35);
  return color ? { backgroundColor: color } : null;
});

const primaryButtonClass = computed(() => {
  if (activeAccentHex.value) {
    return 'shadow-black/40 hover:brightness-110';
  }
  if (isLift.value) return liftTheme.value.button;
  return 'bg-violet-600 hover:bg-violet-500 shadow-violet-900/40';
});

const primaryButtonStyle = computed(() => {
  const hex = parseHex(activeAccentHex.value);
  return hex ? { backgroundColor: hex.hex } : null;
});

const accentTextStyle = computed(() => {
  const hex = parseHex(activeAccentHex.value);
  return hex ? { color: hex.hex } : null;
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
    const nextMetric = LIFT_METRICS.value[liftStep.value + 1];
    return nextMetric?.headline ?? t('common.next');
  }
  if (isLast.value) {
    return t('common.close');
  }
  if (isLift.value) {
    const next = slides.value[slideIndex.value + 1];
    if (next?.kind === 'recap') {
      return t('modals.wrapped.seeRecap');
    }
    return t('modals.wrapped.nextLift');
  }
  return t('common.next');
}

function formatMetricDisplay(value, decimals = 0) {
  if (value === null || value === undefined) {
    return null;
  }
  return Number(value).toLocaleString(undefined, {
    maximumFractionDigits: decimals,
    minimumFractionDigits: decimals > 0 ? Math.min(decimals, 1) : 0,
  });
}

const heaviestRecap = computed(() => {
  const metrics = currentSlide.value?.kind === 'recap' ? currentSlide.value.metrics : null;
  if (!metrics?.heaviest_bar) {
    return null;
  }
  return {
    byLift: metrics.heaviest_bar.by_lift ?? [],
    total: metrics.heaviest_bar.total ?? null,
  };
});

const e1rmRecap = computed(() => {
  const metrics = currentSlide.value?.kind === 'recap' ? currentSlide.value.metrics : null;
  if (!metrics?.top_e1rm) {
    return null;
  }
  return {
    byLift: metrics.top_e1rm.by_lift ?? [],
    total: metrics.top_e1rm.total ?? null,
  };
});

const featuredRecapStats = computed(() => {
  const metrics = currentSlide.value?.kind === 'recap' ? currentSlide.value.metrics : null;
  if (!metrics) {
    return [];
  }

  return [
    {
      key: 'total_tonnage',
      title: t('modals.wrapped.tonnage'),
      unit: 'kg',
      decimals: 0,
      metric: metrics.total_tonnage,
    },
    {
      key: 'total_sets',
      title: t('modals.wrapped.sets'),
      unit: '',
      decimals: 0,
      metric: metrics.total_sets,
    },
    {
      key: 'total_training_minutes',
      title: t('modals.wrapped.trainingTime'),
      unit: t('modals.wrapped.trainingTimeUnit'),
      decimals: 0,
      metric: metrics.total_training_minutes,
    },
  ];
});

const overviewItems = computed(() => [
  { key: 'total_tonnage', label: t('modals.wrapped.tonnage'), suffix: 'kg' },
  { key: 'adherence_percent', label: t('modals.wrapped.adherence'), suffix: '%' },
  { key: 'total_sets', label: t('modals.wrapped.sets'), suffix: '' },
  { key: 'total_reps', label: t('modals.wrapped.reps'), suffix: '' },
  { key: 'tonnage_per_set', label: t('modals.wrapped.tonnagePerSet'), suffix: 'kg' },
]);
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && wrapped"
      class="fixed inset-0 z-[70] flex flex-col bg-gradient-to-b text-white transition-[background] duration-500"
      :class="bgClass"
      :style="bgStyle"
      role="dialog"
      aria-modal="true"
    >
      <div
        class="pointer-events-none absolute inset-x-0 top-0 h-56 opacity-70 blur-3xl transition-colors duration-500"
        :class="activeAccentHex ? '' : (isLift ? liftTheme.glow : 'bg-violet-500/25')"
        :style="glowStyle"
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
          :aria-label="t('common.close')"
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
              :style="accentTextStyle"
              :initial="{ opacity: 0, y: 10 }"
              :animate="{ opacity: 1, y: 0 }"
              :transition="{ delay: 0.1, duration: 0.35 }"
            >
              {{ brandLabel }}
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
              {{ t('modals.wrapped.discover') }}
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
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-300" :style="accentTextStyle">{{ t('modals.wrapped.volume') }}</p>
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

          <!-- LIFT (même écran : au clic la métrique remonte, la suivante s’affiche en dessous) -->
          <div
            v-else-if="currentSlide?.kind === 'lift'"
            :key="`lift-${currentSlide.lift.key}-${slideIndex}`"
            class="relative cursor-pointer"
            @click="next"
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

              <!-- Précédentes : remontent en haut ; nouvelle métrique en dessous (hero) -->
              <div v-if="stackedLiftMetrics.length" class="mt-5 space-y-2.5">
                <motion.div
                  v-for="(metric, index) in stackedLiftMetrics"
                  :key="`stack-${metric.key}-${liftStep}`"
                  class="rounded-2xl bg-white/8 px-4 py-3"
                  :initial="{ opacity: 0, y: 24, scale: 0.97 }"
                  :animate="{ opacity: 1, y: 0, scale: 1 }"
                  :transition="{ delay: index * 0.05, duration: 0.35, ease: [0.22, 1, 0.36, 1] }"
                >
                  <p class="text-xs font-medium text-slate-400">{{ metric.headline }}</p>
                  <div class="mt-1 flex items-end justify-between gap-3">
                    <p class="text-2xl font-bold tabular-nums tracking-tight text-white">
                      <template v-if="metricValue(currentSlide.lift?.[metric.key]) !== null">
                        {{ formatMetricDisplay(metricValue(currentSlide.lift[metric.key]), metric.decimals) }}
                        <span class="ml-1 text-sm font-semibold text-slate-400">{{ metric.unit }}</span>
                      </template>
                      <template v-else>—</template>
                    </p>
                    <p
                      v-if="currentSlide.lift?.[metric.key]?.delta"
                      class="max-w-[55%] text-right text-[11px] font-semibold leading-snug"
                      :class="deltaClass(currentSlide.lift[metric.key].delta)"
                    >
                      {{ vsLabel }}
                      {{ formatDelta(currentSlide.lift[metric.key].delta) }}
                    </p>
                  </div>
                </motion.div>
              </div>

              <motion.div
                :key="`hero-block-${currentSlide.lift.key}-${liftStep}`"
                class="mt-6"
                :initial="{ opacity: 0, y: 36, scale: 0.94 }"
                :animate="{ opacity: 1, y: 0, scale: 1 }"
                :transition="{ duration: 0.45, ease: [0.22, 1, 0.36, 1] }"
              >
                <h2 class="text-2xl font-bold sm:text-3xl">{{ currentLiftMetric?.headline }}</h2>
                <p class="mt-1.5 text-sm text-slate-400">{{ currentLiftMetric?.hint }}</p>

                <div class="mt-6">
                  <p class="text-6xl font-bold tracking-tight tabular-nums sm:text-7xl">
                    <template
                      v-if="metricValue(currentSlide.lift?.[currentLiftMetric?.key]) !== null"
                    >
                      <AnimatedCounter
                        :value="metricValue(currentSlide.lift[currentLiftMetric.key])"
                        :decimals="currentLiftMetric.decimals ?? 0"
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
                    {{ vsLabel }}
                    {{ formatDelta(currentSlide.lift[currentLiftMetric.key].delta) }}
                  </p>
                </div>
              </motion.div>
            </motion.div>
          </div>

          <!-- RECAP : barre par lift, e1RM discret, tonnage / séries / temps en avant -->
          <motion.div
            v-else-if="currentSlide?.kind === 'recap'"
            :key="`recap-${slideIndex}`"
            class="cursor-pointer"
            :initial="{ opacity: 0, y: 20 }"
            :animate="{ opacity: 1, y: 0 }"
            :transition="{ duration: 0.4, ease: [0.22, 1, 0.36, 1] }"
            @click="next"
          >
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-300" :style="accentTextStyle">
              {{ brandLabel }}
            </p>
            <h2 class="mt-2 text-2xl font-bold sm:text-3xl">{{ currentSlide.title }}</h2>
            <p class="mt-1 text-sm text-slate-400">{{ currentSlide.subtitle }}</p>

            <div class="mt-5 space-y-5">
              <!-- Stats mises en avant -->
              <motion.div
                class="grid grid-cols-3 gap-2"
                :initial="{ opacity: 0, y: 14 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ duration: 0.35 }"
              >
                <div
                  v-for="item in featuredRecapStats"
                  :key="item.key"
                  class="rounded-2xl bg-white/10 px-2.5 py-3 text-center"
                >
                  <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-300">{{ item.title }}</p>
                  <p class="mt-1.5 text-xl font-bold tabular-nums tracking-tight text-white sm:text-2xl">
                    <template v-if="metricValue(item.metric) !== null">
                      {{ formatMetricDisplay(metricValue(item.metric), item.decimals) }}
                      <span v-if="item.unit" class="text-[11px] font-medium text-slate-400">{{ item.unit }}</span>
                    </template>
                    <template v-else>—</template>
                  </p>
                  <p
                    v-if="item.metric?.delta"
                    class="mt-1 text-[10px] font-semibold"
                    :class="deltaClass(item.metric.delta)"
                  >
                    {{ formatDelta(item.metric.delta) }}
                  </p>
                </div>
              </motion.div>

              <!-- Barre la plus lourde -->
              <motion.section
                v-if="heaviestRecap"
                :initial="{ opacity: 0, y: 12 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ delay: 0.08, duration: 0.35 }"
              >
                <div class="mb-2.5 flex items-baseline justify-between gap-2">
                  <h3 class="text-sm font-semibold text-white">{{ t('modals.wrapped.heaviestBar') }}</h3>
                  <span class="text-[10px] uppercase tracking-wider text-slate-500">{{ t('modals.wrapped.perLift') }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <div
                    v-for="lift in heaviestRecap.byLift"
                    :key="`bar-${lift.key}`"
                    class="rounded-xl bg-white/8 px-2.5 py-2.5"
                  >
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ liftLabel(lift.key) }}</p>
                    <p class="mt-1 text-xl font-bold tabular-nums text-white">
                      <template v-if="lift.value !== null && lift.value !== undefined">
                        {{ formatMetricDisplay(lift.value, 0) }}
                        <span class="text-[11px] font-medium text-slate-400">kg</span>
                      </template>
                      <template v-else>—</template>
                    </p>
                    <p
                      v-if="lift.delta"
                      class="mt-1 text-[10px] font-semibold"
                      :class="deltaClass(lift.delta)"
                    >
                      {{ formatDelta(lift.delta) }}
                    </p>
                  </div>
                </div>
                <div
                  v-if="heaviestRecap.total"
                  class="mt-2 flex items-center justify-between rounded-xl bg-white/10 px-3 py-2.5"
                >
                  <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-300">{{ t('modals.wrapped.total') }}</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-white">
                      <template v-if="heaviestRecap.total.value !== null && heaviestRecap.total.value !== undefined">
                        {{ formatMetricDisplay(heaviestRecap.total.value, 0) }}
                        <span class="text-sm font-medium text-slate-400">kg</span>
                      </template>
                      <template v-else>—</template>
                    </p>
                  </div>
                  <p
                    v-if="heaviestRecap.total.delta"
                    class="max-w-[45%] text-right text-[11px] font-semibold leading-snug"
                    :class="deltaClass(heaviestRecap.total.delta)"
                  >
                    {{ vsLabel }}
                    {{ formatDelta(heaviestRecap.total.delta) }}
                  </p>
                </div>
              </motion.section>

              <!-- e1RM discret -->
              <motion.section
                v-if="e1rmRecap"
                class="rounded-xl bg-white/[0.03] px-3 py-2.5"
                :initial="{ opacity: 0 }"
                :animate="{ opacity: 1 }"
                :transition="{ delay: 0.14, duration: 0.3 }"
              >
                <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">{{ t('modals.wrapped.topE1rm') }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400">
                  <span
                    v-for="lift in e1rmRecap.byLift"
                    :key="`e1rm-${lift.key}`"
                    class="tabular-nums"
                  >
                    <span class="text-slate-500">{{ liftLabel(lift.key) }}</span>
                    {{ ' ' }}
                    <span class="font-semibold text-slate-300">
                      <template v-if="lift.value !== null && lift.value !== undefined">
                        {{ formatMetricDisplay(lift.value, 1) }} kg
                      </template>
                      <template v-else>—</template>
                    </span>
                  </span>
                  <span v-if="e1rmRecap.total" class="ml-auto font-semibold tabular-nums text-slate-300">
                    {{ t('modals.wrapped.total') }}
                    {{ ' ' }}
                    <template v-if="e1rmRecap.total.value !== null && e1rmRecap.total.value !== undefined">
                      {{ formatMetricDisplay(e1rmRecap.total.value, 1) }} kg
                    </template>
                    <template v-else>—</template>
                  </span>
                </div>
              </motion.section>
            </div>
          </motion.div>

          <!-- OUTRO -->
          <motion.div
            v-else-if="currentSlide?.kind === 'outro'"
            :key="`outro-${slideIndex}`"
            class="text-center"
            :initial="{ opacity: 0, y: 24, scale: 0.96 }"
            :animate="{ opacity: 1, y: 0, scale: 1 }"
            :transition="{ duration: 0.45, ease: [0.22, 1, 0.36, 1] }"
          >
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-300" :style="accentTextStyle">{{ t('modals.wrapped.bravo') }}</p>
            <h2 class="mt-4 text-3xl font-bold sm:text-4xl">{{ currentSlide.title }}</h2>
            <p class="mt-3 text-lg text-slate-300">{{ currentSlide.subtitle }}</p>
            <motion.button
              type="button"
              class="mt-10 w-full rounded-2xl px-4 py-3.5 text-sm font-semibold text-white shadow-lg transition hover:brightness-110"
              :class="primaryButtonStyle ? 'shadow-black/40' : 'bg-violet-600 shadow-violet-900/40 hover:bg-violet-500'"
              :style="primaryButtonStyle"
              :initial="{ opacity: 0, y: 12 }"
              :animate="{ opacity: 1, y: 0 }"
              :transition="{ delay: 0.25, duration: 0.35 }"
              @click="emit('share', wrapped.share_payload)"
            >
              {{ t('modals.wrapped.share') }}
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
            {{ t('common.back') }}
          </button>

          <motion.button
            :key="`next-${slideIndex}-${liftStep}`"
            type="button"
            class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition"
            :class="primaryButtonClass"
            :style="primaryButtonStyle"
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
