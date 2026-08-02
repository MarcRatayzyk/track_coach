<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { filterEntriesByRange } from '../utils/athleteOverviewStats';
import AthletePrForm from './AthletePrForm.vue';
import BodyWeightTrendChart from './charts/BodyWeightTrendChart.vue';
import PrProgressionCharts from './charts/PrProgressionCharts.vue';
import ReadinessWeekTable from './ReadinessWeekTable.vue';
import SbdTonnageDonutChart from './charts/SbdTonnageDonutChart.vue';

const { t } = useI18n();

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
  readinessForm: {
    type: Object,
    default: null,
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
  athleteId: {
    type: Number,
    default: null,
  },
  latestPr: {
    type: Object,
    default: null,
  },
  isCoach: {
    type: Boolean,
    default: false,
  },
  canEditPrs: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:timeRange']);

const wellnessTimeRange = ref('1m');
const showPrForm = ref(false);

const wellnessTimeRangeOptions = computed(() => [
  { value: '7d', label: t('athleteUi.stats.range7d') },
  { value: '1m', label: t('athleteUi.stats.range1m') },
  { value: '3m', label: t('athleteUi.stats.range3m') },
  { value: '6m', label: t('athleteUi.stats.range6m') },
  { value: '1y', label: t('athleteUi.stats.range1y') },
]);

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
    <h2 class="text-sm font-semibold text-white">{{ t('athleteUi.stats.title') }}</h2>

    <article class="mt-3 rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5">
      <p class="text-[10px] uppercase tracking-wide text-slate-500">{{ t('athleteUi.stats.adherence') }}</p>
      <p v-if="adherence?.percentage != null" class="mt-0.5 text-lg font-bold text-white">
        {{ adherence.percentage }}%
      </p>
      <p v-else class="mt-0.5 text-sm text-slate-500">
        {{ programUpcomingLabel ?? (hasActiveProgram ? '—' : t('athleteUi.stats.noActiveProgram')) }}
      </p>
      <p v-if="adherence" class="mt-1 text-xs text-slate-500">
        {{ t('athleteUi.stats.sessionsOnDay', { completed: adherence.completedSessions, planned: adherence.plannedSessions }) }}
        <span v-if="adherence.exactLineCoverage != null" class="text-slate-600">
          · {{ adherence.exactLineCoverage }}% lignes exactes
        </span>
      </p>
    </article>

    <div class="mt-3 grid min-w-0 gap-4 lg:grid-cols-2">
      <SbdTonnageDonutChart :flat-items="stats?.flatItems ?? []" />

      <article class="min-w-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950/50 p-4">
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
          <h3 class="text-sm font-semibold text-white">{{ t('athleteUi.stats.weight') }}</h3>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="option in wellnessTimeRangeOptions"
              :key="`bw-${option.value}`"
              type="button"
              class="rounded-lg border px-2 py-0.5 text-[11px] font-medium transition"
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
        </div>
        <div class="min-w-0 overflow-x-auto">
          <BodyWeightTrendChart :entries="filteredBodyWeight" embedded />
        </div>
      </article>
    </div>

    <article class="mt-3 min-w-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950/50 p-4">
      <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-semibold text-white">{{ t('athleteUi.stats.externalFactors') }}</h3>
        <div class="flex flex-wrap gap-1.5">
          <button
            v-for="option in wellnessTimeRangeOptions"
            :key="`ready-${option.value}`"
            type="button"
            class="rounded-lg border px-2 py-0.5 text-[11px] font-medium transition"
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
      </div>
      <div v-if="filteredReadiness.length || (readinessForm?.fields?.length)" class="min-w-0 overflow-x-auto">
        <ReadinessWeekTable
          :fields="readinessForm?.fields ?? []"
          :entries="filteredReadiness"
          embedded
        />
      </div>
      <p v-else class="text-sm text-slate-500">{{ t('athleteUi.stats.noExternalData') }}</p>
    </article>

    <article
      v-if="showPrChart"
      class="mt-4 min-w-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950/50 p-4"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-sm font-semibold text-white">{{ t('athleteUi.stats.prProgression') }}</h3>
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-if="canEditPrs && athleteId"
            type="button"
            class="rounded-lg border px-2.5 py-1 text-xs font-semibold transition"
            :class="
              showPrForm
                ? 'border-slate-600 text-slate-300 hover:bg-slate-800'
                : 'border-blue-500/50 bg-blue-600/15 text-blue-200 hover:bg-blue-600/25'
            "
            @click="showPrForm = !showPrForm"
          >
            {{ showPrForm ? t('common.close') : t('athleteUi.stats.savePr') }}
          </button>
          <template v-if="timeRangeOptions.length">
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
          </template>
        </div>
      </div>

      <AthletePrForm
        v-if="showPrForm && athleteId"
        class="mt-4"
        :athlete-id="athleteId"
        :latest-pr="latestPr"
        :is-coach="isCoach"
        :title="t('athleteUi.stats.savePrTitle')"
        :description="t('athleteUi.stats.savePrDesc')"
      />

      <div class="mt-4 min-w-0 overflow-x-auto">
        <PrProgressionCharts :records="prRecords" embedded />
      </div>
    </article>
  </section>
</template>
