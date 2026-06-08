<script setup>
import { computed } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import { BLOCK_TYPES } from '../utils/programBuilder';
import { buildAthleteOverviewStats } from '../utils/athleteOverviewStats';
import AthleteBlockProgramView from './AthleteBlockProgramView.vue';
import AthleteStatsOverview from './AthleteStatsOverview.vue';
import ProgramBlockStatsTab from './ProgramBlockStatsTab.vue';

const props = defineProps({
  activeProgram: {
    type: Object,
    default: null,
  },
  programBlock: {
    type: Object,
    default: null,
  },
  blockProgress: {
    type: Object,
    default: null,
  },
  trainingSessions: {
    type: Array,
    default: () => [],
  },
  latestPr: {
    type: Object,
    default: null,
  },
});

const blockTypeLabel = computed(() => {
  const value = props.blockProgress?.block_type;
  return BLOCK_TYPES.find((item) => item.value === value)?.label ?? value ?? '—';
});

const weekProgressPercent = computed(() => {
  const current = props.blockProgress?.week_current;
  const total = props.blockProgress?.week_count;
  if (!current || !total) {
    return 0;
  }
  return Math.min(100, Math.round((current / total) * 100));
});

const oneRm = computed(() => ({
  squat: Number(props.latestPr?.squat ?? props.programBlock?.athlete_one_rm?.squat ?? 0),
  bench: Number(props.latestPr?.bench ?? props.programBlock?.athlete_one_rm?.bench ?? 0),
  deadlift: Number(props.latestPr?.deadlift ?? props.programBlock?.athlete_one_rm?.deadlift ?? 0),
}));

const overviewStats = computed(() =>
  buildAthleteOverviewStats({
    trainingSessions: props.trainingSessions,
    programBlock: props.programBlock,
    oneRm: oneRm.value,
  }),
);
</script>

<template>
  <div class="space-y-4">
    <section
      v-if="programBlock"
      class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4"
    >
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 class="text-sm font-semibold text-white">{{ programBlock.name }}</h2>
          <p class="mt-1 text-xs text-slate-500">
            Du {{ formatCalendarFr(programBlock.date_start, 'medium') }}
            <template v-if="programBlock.date_end">
              au {{ formatCalendarFr(programBlock.date_end, 'medium') }}
            </template>
          </p>
        </div>
        <span class="rounded-lg border border-slate-700 bg-slate-950/60 px-2.5 py-1 text-xs font-medium text-slate-300">
          {{ blockTypeLabel }}
        </span>
      </div>

      <div v-if="blockProgress?.week_current" class="mt-4">
        <div class="flex items-center justify-between text-xs text-slate-500">
          <span>Semaine {{ blockProgress.week_current }} / {{ blockProgress.week_count }}</span>
          <span>{{ weekProgressPercent }}%</span>
        </div>
        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-800">
          <div
            class="h-full rounded-full bg-blue-500 transition-all"
            :style="{ width: `${weekProgressPercent}%` }"
          />
        </div>
      </div>
    </section>

    <AthleteBlockProgramView :active-program="activeProgram" />

    <AthleteStatsOverview
      v-if="programBlock"
      :stats="overviewStats"
      :has-active-program="true"
    />

    <ProgramBlockStatsTab
      v-if="programBlock"
      :sessions="programBlock.sessions ?? {}"
      :date-start="programBlock.date_start ?? ''"
      :athlete-one-rm="programBlock.athlete_one_rm ?? {}"
      :week-count="programBlock.week_count ?? 0"
    />
  </div>
</template>
