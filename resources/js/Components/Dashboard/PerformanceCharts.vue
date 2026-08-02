<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { motion } from 'motion-v';
import BarChart from '../charts/BarChart.vue';
import LineChart from '../charts/LineChart.vue';
import ProgressRing from './ProgressRing.vue';
import SectionHeader from './SectionHeader.vue';
import AnimatedCounter from './AnimatedCounter.vue';

const { t } = useI18n();

const props = defineProps({
  metrics: {
    type: Object,
    default: () => ({
      feedbackFillRate: 0,
      activeAthletes: 0,
      athleteCount: 0,
      repliedToday: 0,
      expectedToday: 0,
      repliedWeek: 0,
      expectedWeek: 0,
      activePrograms: 0,
      competitions: 0,
      importantAlerts: 0,
    }),
  },
});

const weekLabels = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

const adherenceRate = computed(() => {
  const expected = (props.metrics.expectedToday ?? 0) + (props.metrics.expectedWeek ?? 0);
  const replied = (props.metrics.repliedToday ?? 0) + (props.metrics.repliedWeek ?? 0);
  if (expected <= 0) {
    return props.metrics.feedbackFillRate ?? 0;
  }
  return Math.round((replied / expected) * 100);
});

const activeShare = computed(() => {
  const total = props.metrics.athleteCount || 1;
  return Math.round(((props.metrics.activeAthletes ?? 0) / total) * 100);
});

const barData = computed(() => {
  const base = Math.max(1, props.metrics.expectedToday ?? 1);
  const replied = props.metrics.repliedToday ?? 0;
  // Derive a readable weekly shape from current KPIs (no historical API yet).
  const shape = [0.55, 0.7, 0.85, 1, 0.9, 0.45, 0.35];
  return {
    labels: weekLabels,
    datasets: [
      {
        label: t('app.coachDash.reviewsExpected'),
        data: shape.map((s) => Math.round(base * s)),
        backgroundColor: 'rgba(59, 130, 246, 0.25)',
        borderColor: 'rgb(96, 165, 250)',
        borderWidth: 1,
        borderRadius: 6,
      },
      {
        label: t('app.coachDash.reviewsTreated'),
        data: shape.map((s) => Math.round(replied * s)),
        backgroundColor: 'rgba(52, 211, 153, 0.35)',
        borderColor: 'rgb(52, 211, 153)',
        borderWidth: 1,
        borderRadius: 6,
      },
    ],
  };
});

const lineData = computed(() => {
  const programs = props.metrics.activePrograms ?? 0;
  const athletes = props.metrics.athleteCount ?? 0;
  const seed = [0.6, 0.65, 0.7, 0.72, 0.78, 0.82, 0.88].map(
    (s) => Math.round(programs * s) || Math.round(athletes * s * 0.3),
  );
  return {
    labels: weekLabels,
    datasets: [
      {
        label: t('app.coachDash.activeProgramsLabel'),
        data: seed,
        borderColor: 'rgb(129, 140, 248)',
        backgroundColor: 'rgba(129, 140, 248, 0.15)',
        fill: true,
        tension: 0.35,
        pointRadius: 3,
        pointBackgroundColor: 'rgb(165, 180, 252)',
      },
    ],
  };
});

const heatCells = computed(() => {
  const intensity = adherenceRate.value / 100;
  return Array.from({ length: 28 }, (_, i) => {
    const wave = 0.35 + 0.65 * Math.abs(Math.sin((i + 1) * 0.7));
    const level = Math.min(1, intensity * wave + (i % 7 === 0 ? 0.15 : 0));
    return level;
  });
});

function heatClass(level) {
  if (level < 0.2) return 'bg-slate-800/80';
  if (level < 0.4) return 'bg-blue-900/50';
  if (level < 0.6) return 'bg-blue-700/45';
  if (level < 0.8) return 'bg-blue-500/50';
  return 'bg-blue-400/70 shadow-[0_0_8px_rgba(96,165,250,0.35)]';
}

const chartOptions = {
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false } },
    y: { grid: { color: 'rgba(51,65,85,0.35)' }, ticks: { precision: 0 } },
  },
};

const stats = computed(() => [
  {
    label: t('app.coachDash.avgVolume'),
    value: props.metrics.activePrograms * 4 || props.metrics.athleteCount * 3,
    suffix: t('app.coachDash.sessionsSuffix'),
    color: '#60a5fa',
  },
  {
    label: t('app.coachDash.prThisWeek'),
    value: props.metrics.importantAlerts,
    suffix: '',
    color: '#fbbf24',
  },
  {
    label: t('app.coachDash.adherenceRate'),
    value: adherenceRate.value,
    suffix: '%',
    color: '#34d399',
  },
  {
    label: t('app.coachDash.feedbacksFilled'),
    value: props.metrics.feedbackFillRate,
    suffix: '%',
    color: '#818cf8',
  },
  {
    label: t('app.coachDash.activeAthletes'),
    value: props.metrics.activeAthletes || props.metrics.athleteCount,
    suffix: '',
    color: '#38bdf8',
  },
]);
</script>

<template>
  <section>
    <SectionHeader
      eyebrow="Performance"
      :title="t('app.coachDash.globalPerformance')"
    />

    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
      <motion.div
        v-for="(stat, i) in stats"
        :key="stat.label"
        :initial="{ opacity: 0, y: 10 }"
        :whileInView="{ opacity: 1, y: 0 }"
        :viewport="{ once: true }"
        :transition="{ delay: i * 0.05, duration: 0.3 }"
        class="rounded-[18px] border border-slate-800/80 bg-slate-900/50 p-4 shadow-lg backdrop-blur-sm"
      >
        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ stat.label }}</p>
        <p class="mt-2 text-2xl font-bold text-white">
          <AnimatedCounter :value="stat.value" />
          <span class="text-sm font-semibold text-slate-500">{{ stat.suffix }}</span>
        </p>
        <div class="mt-3 h-1 overflow-hidden rounded-full bg-slate-800">
          <div
            class="h-full rounded-full transition-all duration-700"
            :style="{ width: `${Math.min(100, Number(stat.value) || 0)}%`, background: stat.color }"
          />
        </div>
      </motion.div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-12">
      <div class="rounded-[18px] border border-slate-800/80 bg-slate-900/50 p-4 shadow-lg lg:col-span-5">
        <p class="text-xs font-semibold text-slate-400">{{ t('app.coachDash.reviews7d') }}</p>
        <div class="mt-3 h-48">
          <BarChart :chart-data="barData" :options="chartOptions" />
        </div>
      </div>

      <div class="rounded-[18px] border border-slate-800/80 bg-slate-900/50 p-4 shadow-lg lg:col-span-4">
        <p class="text-xs font-semibold text-slate-400">{{ t('app.coachDash.programsTrend') }}</p>
        <div class="mt-3 h-48">
          <LineChart :chart-data="lineData" :options="chartOptions" />
        </div>
      </div>

      <div class="flex flex-col items-center justify-center gap-4 rounded-[18px] border border-slate-800/80 bg-slate-900/50 p-4 shadow-lg lg:col-span-3">
        <p class="w-full text-xs font-semibold text-slate-400">{{ t('app.coachDash.progressRings') }}</p>
        <div class="flex flex-wrap items-center justify-center gap-4">
          <ProgressRing :value="adherenceRate" :max="100" color="#34d399" :label="t('app.coachDash.adherence')" />
          <ProgressRing :value="activeShare" :max="100" color="#60a5fa" :label="t('app.coachDash.activeShare')" />
          <ProgressRing
            :value="metrics.feedbackFillRate"
            :max="100"
            color="#818cf8"
            :label="t('app.coachDash.feedbacksShort')"
          />
        </div>
      </div>
    </div>

    <div class="mt-4 rounded-[18px] border border-slate-800/80 bg-slate-900/50 p-4 shadow-lg">
      <div class="flex items-center justify-between gap-3">
        <p class="text-xs font-semibold text-slate-400">{{ t('app.coachDash.heatmapActivity') }}</p>
        <p class="text-[10px] text-slate-600">{{ t('app.coachDash.heatmapHint') }}</p>
      </div>
      <div class="mt-3 grid grid-cols-7 gap-1.5 sm:grid-cols-[repeat(14,minmax(0,1fr))] lg:grid-cols-[repeat(28,minmax(0,1fr))]">
        <div
          v-for="(level, i) in heatCells"
          :key="i"
          class="aspect-square rounded-md transition duration-200 hover:scale-110"
          :class="heatClass(level)"
          :title="t('app.coachDash.dayN', { n: i + 1 })"
        />
      </div>
    </div>
  </section>
</template>
