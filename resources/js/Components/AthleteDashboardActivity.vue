<script setup>
import { computed } from 'vue';
import { buildAthleteOverviewStats } from '../utils/athleteOverviewStats';
import AthleteTrainingPrCard from './AthleteTrainingPrCard.vue';
import PrProgressionCharts from './charts/PrProgressionCharts.vue';

const props = defineProps({
  trainingSessions: {
    type: Array,
    default: () => [],
  },
  programBlock: {
    type: Object,
    default: null,
  },
  oneRm: {
    type: Object,
    default: () => ({ squat: 0, bench: 0, deadlift: 0 }),
  },
  personalRecords: {
    type: Array,
    default: () => [],
  },
});

const stats = computed(() =>
  buildAthleteOverviewStats({
    trainingSessions: props.trainingSessions,
    programBlock: props.programBlock,
    oneRm: props.oneRm,
  }),
);

const adherence = computed(() => stats.value?.adherence ?? null);
const recentActivity = computed(() => stats.value?.recentActivity ?? null);
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4 shadow-lg">
    <h2 class="text-sm font-semibold text-white">Progression</h2>

    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-2.5">
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Adhérence</p>
        <p v-if="adherence?.percentage != null" class="mt-0.5 text-lg font-bold text-white">
          {{ adherence.percentage }}%
        </p>
        <p v-else class="mt-0.5 text-lg font-bold text-slate-500">—</p>
        <p v-if="adherence" class="mt-1 text-xs text-slate-500">
          {{ adherence.completedSessions }}/{{ adherence.plannedSessions }} séances au bon jour
        </p>
      </div>

      <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-2.5">
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Dernière séance</p>
        <template v-if="recentActivity">
          <p class="mt-0.5 text-sm font-semibold text-white">
            {{ recentActivity.sessionLabel }}
          </p>
          <p class="mt-0.5 text-xs text-slate-400">{{ recentActivity.dateLabel }}</p>
          <p v-if="recentActivity.tonnage" class="mt-1 text-xs text-slate-500">
            {{ recentActivity.tonnage.toLocaleString('fr-FR') }} kg·reps
          </p>
        </template>
        <p v-else class="mt-0.5 text-sm text-slate-500">Aucune séance enregistrée</p>
      </div>

      <AthleteTrainingPrCard :training-sessions="trainingSessions" />
    </div>

    <div v-if="personalRecords.length" class="mt-4 border-t border-slate-800 pt-4">
      <PrProgressionCharts :records="personalRecords" compact />
    </div>
  </section>
</template>
