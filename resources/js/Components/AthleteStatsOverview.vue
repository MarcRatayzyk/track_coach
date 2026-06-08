<script setup>
import { computed } from 'vue';
import BarChart from './charts/BarChart.vue';
import DoughnutChart from './charts/DoughnutChart.vue';
import PrProgressionCharts from './charts/PrProgressionCharts.vue';
import { LIFT_COLORS } from '../utils/chartTheme';

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

const showPrChart = computed(() => props.prRecords !== null);

const weeklyPoints = computed(() => props.stats?.weeklyTonnage?.points ?? []);
const hasWeeklyTonnage = computed(() => weeklyPoints.value.length > 0);

const weeklyBarData = computed(() => ({
  labels: weeklyPoints.value.map((point) => point.label),
  datasets: [
    {
      label: 'Tonnage',
      data: weeklyPoints.value.map((point) => point.total),
      backgroundColor: 'rgba(59, 130, 246, 0.75)',
      borderColor: 'rgb(59, 130, 246)',
      borderWidth: 1,
      borderRadius: 4,
    },
  ],
}));

const weeklyBarOptions = {
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        title(items) {
          const point = weeklyPoints.value[items[0]?.dataIndex];
          return point ? `Semaine du ${point.label}` : '';
        },
        label(context) {
          return `${Number(context.parsed.y).toLocaleString('fr-FR')} kg`;
        },
      },
    },
  },
  scales: {
    y: {
      title: { display: true, text: 'Tonnage (kg)', color: 'rgb(148, 163, 184)' },
    },
  },
};

const distributionItems = computed(() => props.stats?.distribution30d?.items ?? []);
const distributionBasis = computed(() => props.stats?.distribution30d?.basis ?? 'volume');
const hasDistribution = computed(() =>
  distributionItems.value.some((item) => Number(item.value ?? 0) > 0),
);

const sbdDonutData = computed(() => ({
  labels: distributionItems.value.map((item) => item.label),
  datasets: [
    {
      data: distributionItems.value.map((item) => item.value),
      backgroundColor: [
        LIFT_COLORS.squat.border,
        LIFT_COLORS.bench.border,
        LIFT_COLORS.deadlift.border,
      ],
      borderColor: 'rgb(15, 23, 42)',
      borderWidth: 2,
    },
  ],
}));

const sbdDonutOptions = computed(() => ({
  plugins: {
    tooltip: {
      callbacks: {
        label(context) {
          const value = context.parsed ?? 0;
          const total = context.dataset.data.reduce((a, b) => a + b, 0);
          const pct = total > 0 ? Math.round((value / total) * 100) : 0;
          const unit = distributionBasis.value === 'volume' ? ' kg' : '';
          return `${context.label}: ${Math.round(value).toLocaleString('fr-FR')}${unit} (${pct} %)`;
        },
      },
    },
  },
}));

const kpis = computed(() => [
  {
    label: 'Séances 7 jours',
    value: props.stats?.sessionCount7d ?? 0,
    suffix: '',
    hint: 'Activité sur la dernière semaine.',
  },
  {
    label: 'Séances 30 jours',
    value: props.stats?.sessionCount30d ?? 0,
    suffix: '',
    hint: 'Volume de travail récent.',
  },
  {
    label: 'Tonnage semaine',
    value: props.stats?.weeklyTonnage?.currentWeekTotal ?? 0,
    suffix: ' kg',
    hint: 'Somme charge × reps × séries de la semaine en cours.',
  },
  {
    label: 'Adhérence',
    value: props.stats?.adherence?.percentage,
    suffix: '%',
    hint: props.stats?.adherence
      ? `${props.stats.adherence.completedSessions}/${props.stats.adherence.plannedSessions} séances au bon jour`
      : 'Aucun programme actif comparable.',
  },
]);

const tonnageNote = computed(() => {
  const notes = [];

  if (props.stats?.excludedRpeLines) {
    notes.push(`${props.stats.excludedRpeLines} ligne${props.stats.excludedRpeLines > 1 ? 's' : ''} en RPE exclue${props.stats.excludedRpeLines > 1 ? 's' : ''}`);
  }

  if (props.stats?.hasPercentWithoutRm) {
    notes.push('certaines lignes en %1RM sont partielles');
  }

  return notes.join(' · ');
});

function formatMetric(value, suffix = '') {
  if (value == null) {
    return '—';
  }

  return `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(value)}${suffix}`;
}

function formatKg(value) {
  const numeric = Number(value ?? 0);
  return numeric > 0 ? `${numeric} kg` : '—';
}
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-sm font-semibold text-white">Overview statistiques</h2>
        <p class="mt-1 text-xs text-slate-500">
          Vue coach basée sur les séances réalisées.
        </p>
      </div>
      <p v-if="tonnageNote" class="text-xs text-slate-500">
        {{ tonnageNote }}
      </p>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
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

    <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.3fr)_minmax(18rem,0.9fr)]">
      <article class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
        <div v-if="showPrChart" class="pb-6">
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
        </div>

        <div :class="showPrChart ? 'border-t border-slate-800 pt-6' : ''">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold text-white">Tonnage hebdo</h3>
              <p class="mt-1 text-xs text-slate-500">6 dernières semaines glissantes</p>
            </div>
            <p class="text-xs text-slate-500">
              {{ weeklyPoints.length ? `${weeklyPoints.length} semaine${weeklyPoints.length > 1 ? 's' : ''}` : 'Aucune donnée' }}
            </p>
          </div>

          <div v-if="hasWeeklyTonnage" class="mt-4 h-64">
            <BarChart :chart-data="weeklyBarData" :options="weeklyBarOptions" />
          </div>

          <p v-else class="mt-4 text-sm text-slate-500">
            Aucune charge exploitable pour calculer le tonnage hebdomadaire.
          </p>
        </div>
      </article>

      <div class="space-y-4">
        <article class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-white">Répartition SBD</h3>
            <span class="text-xs text-slate-500">
              {{ stats.distribution30d?.basis === 'volume' ? '30 derniers jours · volume' : '30 derniers jours · lignes' }}
            </span>
          </div>

          <div v-if="hasDistribution" class="mt-4 h-64">
            <DoughnutChart :chart-data="sbdDonutData" :options="sbdDonutOptions" />
          </div>

          <p v-else class="mt-4 text-sm text-slate-500">
            Aucune donnée SBD sur les 30 derniers jours.
          </p>
        </article>

        <article class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-white">Activité récente</h3>
            <span class="text-xs text-slate-500">
              {{ stats.recentActivity?.dateLabel ?? '—' }}
            </span>
          </div>

          <div v-if="stats.recentActivity" class="mt-4 space-y-3 text-sm">
            <div>
              <p class="font-semibold text-slate-100">{{ stats.recentActivity.sessionLabel }}</p>
              <p class="mt-1 text-slate-500">
                {{ stats.recentActivity.mainLift }} · {{ stats.recentActivity.exerciseCount }} exercice{{ stats.recentActivity.exerciseCount > 1 ? 's' : '' }}
              </p>
            </div>

            <div class="rounded-lg border border-slate-800 bg-slate-950/70 px-3 py-2">
              <p class="text-[11px] uppercase tracking-wide text-slate-500">Tonnage séance</p>
              <p class="mt-1 font-semibold text-white">{{ formatMetric(stats.recentActivity.tonnage, ' kg') }}</p>
            </div>

            <ul class="space-y-2">
              <li
                v-for="lift in stats.recentActivity.topLoads"
                :key="lift.label"
                class="flex items-center justify-between gap-3"
              >
                <span class="text-slate-400">{{ lift.label }}</span>
                <span class="font-medium text-slate-100">{{ formatKg(lift.value) }}</span>
              </li>
            </ul>
          </div>

          <p v-else class="mt-4 text-sm text-slate-500">
            Aucune séance récente enregistrée.
          </p>
        </article>

        <article
          v-if="stats.adherence || hasActiveProgram"
          class="rounded-xl border border-slate-800 bg-slate-950/50 p-4"
        >
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-white">Lecture adhérence</h3>
            <span class="text-xs text-slate-500">
              {{ stats.adherence?.exactLineCoverage != null ? `${stats.adherence.exactLineCoverage}% lignes exactes` : 'Programme requis' }}
            </span>
          </div>

          <div v-if="stats.adherence" class="mt-4 space-y-2 text-sm text-slate-300">
            <p>
              <span class="font-semibold text-white">{{ stats.adherence.percentage }}%</span>
              de concordance sur l'exercice, les séries, les reps et la charge.
            </p>
            <p class="text-slate-500">
              {{ stats.adherence.completedSessions }}/{{ stats.adherence.plannedSessions }}
              séances planifiées ont une séance réalisée le bon jour.
            </p>
          </div>

          <p v-else class="mt-4 text-sm text-slate-500">
            Aucun programme actif disponible pour calculer l'adhérence.
          </p>
        </article>
      </div>
    </div>
  </section>
</template>
