<script setup>
import { computed, ref } from 'vue';
import { filterEntriesByRange } from '../utils/athleteOverviewStats';
import { buildRpeTrendSeries, filterRpeTrendByRange, RPE_EXERCISE_FILTERS } from '../utils/rpeTrend';
import BodyWeightTrendChart from './charts/BodyWeightTrendChart.vue';
import PrProgressionCharts from './charts/PrProgressionCharts.vue';
import ReadinessTrendChart from './charts/ReadinessTrendChart.vue';
import RpeTrendChart from './charts/RpeTrendChart.vue';
import SbdTonnageDonutChart from './charts/SbdTonnageDonutChart.vue';
import AthleteFunStatsPanel from './AthleteFunStatsPanel.vue';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
  hasActiveProgram: {
    type: Boolean,
    default: false,
  },
  programUpcomingLabel: {
    type: String,
    default: null,
  },
  prRecords: {
    type: Array,
    default: null,
  },
  readinessRecent: {
    type: Array,
    default: () => [],
  },
  bodyWeightRecent: {
    type: Array,
    default: () => [],
  },
  timeRange: {
    type: String,
    default: '6m',
  },
  timeRangeOptions: {
    type: Array,
    default: () => [],
  },
  trainingSessions: {
    type: Array,
    default: () => [],
  },
  funStats: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['update:timeRange']);

const wellnessTimeRange = ref('1m');
const rpeTimeRange = ref('3m');
const rpeExerciseFilter = ref('all');

const rpePoints = computed(() => {
  const series = buildRpeTrendSeries(props.trainingSessions, {
    exerciseFilter: rpeExerciseFilter.value,
  });
  return filterRpeTrendByRange(series, rpeTimeRange.value);
});

const wellnessTimeRangeOptions = [
  { value: '7d', label: '7 j' },
  { value: '1m', label: '1 mois' },
  { value: '3m', label: '3 mois' },
  { value: '6m', label: '6 mois' },
  { value: '1y', label: '1 an' },
];

const showPrChart = computed(() => props.prRecords !== null);

const adherence = computed(() => props.stats?.adherence ?? null);

const filteredReadiness = computed(() =>
  filterEntriesByRange(props.readinessRecent, 'entry_date', wellnessTimeRange.value),
);

const filteredBodyWeight = computed(() =>
  filterEntriesByRange(props.bodyWeightRecent, 'entry_date', wellnessTimeRange.value),
);
</script>

<template>
  <section class="tc-athlete-stats-panel rounded-xl border border-slate-800 bg-slate-900/50 p-3 shadow-lg">
    <h2 class="text-sm font-semibold text-white">Statistiques</h2>

    <article class="mt-3 rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5">
      <p class="text-[10px] uppercase tracking-wide text-slate-500">Adhérence</p>
      <p v-if="adherence?.percentage != null" class="mt-0.5 text-lg font-bold text-white">
        {{ adherence.percentage }}%
      </p>
      <p v-else class="mt-0.5 text-sm text-slate-500">
        {{ programUpcomingLabel ?? (hasActiveProgram ? '—' : 'Aucun programme actif') }}
      </p>
      <p v-if="adherence" class="mt-1 text-xs text-slate-500">
        {{ adherence.completedSessions }}/{{ adherence.plannedSessions }} séances au bon jour
        <span v-if="adherence.exactLineCoverage != null" class="text-slate-600">
          · {{ adherence.exactLineCoverage }}% lignes exactes
        </span>
      </p>
    </article>

    <SbdTonnageDonutChart class="mt-3" :flat-items="stats?.flatItems ?? []" />

    <AthleteFunStatsPanel v-if="funStats" class="mt-3" :stats="funStats" />

    <article class="mt-3 min-w-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950/50 p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-sm font-semibold text-white">Évolution RPE (réalisé)</h3>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="option in wellnessTimeRangeOptions.filter((item) => ['7d', '1m', '3m', '6m'].includes(item.value))"
            :key="`rpe-range-${option.value}`"
            type="button"
            class="rounded-lg border px-2.5 py-1 text-xs font-medium transition"
            :class="
              rpeTimeRange === option.value
                ? 'border-amber-400/70 bg-amber-500/20 text-amber-200'
                : 'border-slate-700 text-slate-400 hover:bg-slate-800'
            "
            @click="rpeTimeRange = option.value"
          >
            {{ option.label }}
          </button>
        </div>
      </div>
      <div class="mt-2 flex flex-wrap gap-2">
        <button
          v-for="option in RPE_EXERCISE_FILTERS"
          :key="option.value"
          type="button"
          class="rounded-lg border px-2.5 py-1 text-xs font-medium transition"
          :class="
            rpeExerciseFilter === option.value
              ? 'border-amber-400/70 bg-amber-500/20 text-amber-200'
              : 'border-slate-700 text-slate-400 hover:bg-slate-800'
          "
          @click="rpeExerciseFilter = option.value"
        >
          {{ option.label }}
        </button>
      </div>
      <div class="mt-3 min-w-0 overflow-x-auto">
        <RpeTrendChart :points="rpePoints" embedded />
      </div>
    </article>

    <div class="mt-3 flex flex-wrap gap-2">
      <button
        v-for="option in wellnessTimeRangeOptions"
        :key="option.value"
        type="button"
        class="rounded-lg border px-2.5 py-1 text-xs font-medium transition"
        :class="
          wellnessTimeRange === option.value
            ? 'border-violet-400/70 bg-violet-500/20 text-violet-200'
            : 'border-slate-700 text-slate-400 hover:bg-slate-800'
        "
        @click="wellnessTimeRange = option.value"
      >
        {{ option.label }}
      </button>
    </div>

    <div class="mt-3 grid min-w-0 gap-4 lg:grid-cols-2">
      <article class="min-w-0 overflow-hidden">
        <h3 class="mb-3 text-sm font-semibold text-white">Readiness</h3>
        <div v-if="filteredReadiness.length" class="min-w-0 overflow-x-auto">
          <ReadinessTrendChart :entries="filteredReadiness" embedded />
        </div>
        <p v-else class="text-sm text-slate-500">Aucune saisie readiness sur cette période.</p>
      </article>

      <article class="min-w-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950/50 p-4">
        <div class="min-w-0 overflow-x-auto">
          <BodyWeightTrendChart :entries="filteredBodyWeight" embedded />
        </div>
      </article>
    </div>

    <article
      v-if="showPrChart"
      class="mt-4 min-w-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950/50 p-4"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-sm font-semibold text-white">Progression des PR</h3>
        <div v-if="timeRangeOptions.length" class="flex flex-wrap gap-2">
          <button
            v-for="option in timeRangeOptions"
            :key="option.value"
            type="button"
            class="rounded-lg border px-2.5 py-1 text-xs font-medium transition"
            :class="
              timeRange === option.value
                ? 'border-blue-400/70 bg-blue-500/20 text-blue-200'
                : 'border-slate-700 text-slate-400 hover:bg-slate-800'
            "
            @click="emit('update:timeRange', option.value)"
          >
            {{ option.label }}
          </button>
        </div>
      </div>

      <div class="mt-4 min-w-0 overflow-x-auto">
        <PrProgressionCharts :records="prRecords" embedded />
      </div>
    </article>
  </section>
</template>
