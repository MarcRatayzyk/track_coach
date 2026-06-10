<script setup>
import { computed, ref } from 'vue';
import { filterEntriesByRange } from '../utils/athleteOverviewStats';
import BodyWeightTrendChart from './charts/BodyWeightTrendChart.vue';
import PrProgressionCharts from './charts/PrProgressionCharts.vue';
import ReadinessTrendChart from './charts/ReadinessTrendChart.vue';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
  hasActiveProgram: {
    type: Boolean,
    default: false,
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
});

const emit = defineEmits(['update:timeRange']);

const wellnessTimeRange = ref('1m');

const wellnessTimeRangeOptions = [
  { value: '7d', label: '7 j' },
  { value: '1m', label: '1 mois' },
  { value: '3m', label: '3 mois' },
  { value: '6m', label: '6 mois' },
  { value: '1y', label: '1 an' },
];

const showPrChart = computed(() => props.prRecords !== null);

const filteredReadiness = computed(() =>
  filterEntriesByRange(props.readinessRecent, 'entry_date', wellnessTimeRange.value),
);

const filteredBodyWeight = computed(() =>
  filterEntriesByRange(props.bodyWeightRecent, 'entry_date', wellnessTimeRange.value),
);

const kpis = computed(() => [
  {
    label: 'Tonnage moyen',
    value: props.stats?.tonnageAverages?.averageSessionTonnage,
    suffix: ' kg',
    hint: 'Moyenne par séance sur les 30 derniers jours.',
  },
  {
    label: 'Tonnage moyen par série',
    value: props.stats?.tonnageAverages?.averageSetTonnage,
    suffix: ' kg',
    hint: 'Volume moyen par série sur les 30 derniers jours.',
  },
]);

function formatMetric(value, suffix = '') {
  if (value == null) {
    return '—';
  }

  return `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(value)}${suffix}`;
}
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-sm font-semibold text-white">Overview statistiques</h2>
        <p class="mt-1 text-xs text-slate-500">
          Activité, forme et suivi du corps.
        </p>
      </div>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
      <article
        v-for="kpi in kpis"
        :key="kpi.label"
        class="rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3"
      >
        <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ kpi.label }}</p>
        <p class="mt-1 text-xl font-semibold text-white">{{ formatMetric(kpi.value, kpi.suffix) }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ kpi.hint }}</p>
      </article>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
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

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
      <article class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
        <div class="mb-3">
          <h3 class="text-sm font-semibold text-white">Readiness</h3>
        </div>
        <ReadinessTrendChart v-if="filteredReadiness.length" :entries="filteredReadiness" embedded />
        <p v-else class="text-sm text-slate-500">Aucune saisie readiness sur cette période.</p>
      </article>

      <article class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
        <BodyWeightTrendChart :entries="filteredBodyWeight" embedded />
      </article>
    </div>

    <article
      v-if="showPrChart"
      class="mt-4 rounded-xl border border-slate-800 bg-slate-950/50 p-4"
    >
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 class="text-sm font-semibold text-white">Progression des PR</h3>
          <p class="mt-1 text-xs text-slate-500">Squat, bench, terre et total officiel</p>
        </div>
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

      <div class="mt-4">
        <PrProgressionCharts :records="prRecords" embedded />
      </div>
    </article>
  </section>
</template>
