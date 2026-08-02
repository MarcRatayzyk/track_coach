<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { localizedExerciseName } from '../utils/exerciseNames';

const { t } = useI18n();

const props = defineProps({
  exercises: {
    type: Array,
    default: () => [],
  },
  /** Rétrocompat : une seule série (ex. sous une vidéo). */
  series: {
    type: Object,
    default: null,
  },
});

const rows = computed(() => {
  if (props.exercises?.length) {
    return props.exercises;
  }
  return props.series ? [props.series] : [];
});

function formatLoad(metrics) {
  if (!metrics) {
    return null;
  }
  if (metrics.load !== null && metrics.load !== undefined && metrics.load !== '') {
    return `${metrics.load} kg`;
  }
  if (
    metrics.load_percent !== null &&
    metrics.load_percent !== undefined &&
    metrics.load_percent !== ''
  ) {
    return `${metrics.load_percent} %`;
  }
  return null;
}

function formatMetric(value) {
  if (value === null || value === undefined || value === '') {
    return null;
  }
  return String(value);
}

function title(row) {
  const name = localizedExerciseName(row.exercise_name) || t('app.feedbacks.exercise');
  return row.section_label ? `${name} — ${row.section_label}` : name;
}

function plannedParts(row) {
  const m = row.planned ?? row;
  return {
    sets: formatMetric(m?.sets) ?? '—',
    reps: formatMetric(m?.reps) ?? '—',
    load: formatLoad(m) ?? '—',
    rpe: formatMetric(m?.rpe) ?? '—',
  };
}

function actualParts(row) {
  if (!row.actual) {
    return null;
  }
  return {
    sets: formatMetric(row.actual.sets) ?? '—',
    reps: formatMetric(row.actual.reps) ?? '—',
    load: formatLoad(row.actual) ?? '—',
    rpe: formatMetric(row.actual.rpe) ?? '—',
  };
}

function matchClass(row, key) {
  if (!row.actual) {
    return 'text-slate-500';
  }
  const match = row.matches?.[key];
  if (match === true) {
    return 'text-emerald-400';
  }
  if (match === false) {
    return 'text-red-400';
  }
  return 'text-slate-300';
}
</script>

<template>
  <div
    v-if="rows.length"
    class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3"
  >
    <!-- Colonne prévue -->
    <div class="min-w-0 rounded-xl border border-slate-800 bg-slate-950/40">
      <div class="border-b border-slate-800 px-3 py-2">
        <h4 class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
          {{ t('athleteUi.seriesComparison.plannedSession') }}
        </h4>
      </div>
      <ul class="divide-y divide-slate-800/80">
        <li
          v-for="(row, index) in rows"
          :key="`planned-${row.id ?? index}`"
          class="px-3 py-2"
        >
          <p class="truncate text-xs font-medium text-slate-200">{{ title(row) }}</p>
          <p class="mt-0.5 font-mono text-xs tabular-nums text-slate-400">
            <span>{{ plannedParts(row).sets }}</span>
            <span class="text-slate-600">×</span>
            <span>{{ plannedParts(row).reps }}</span>
            <span class="text-slate-600"> · </span>
            <span>{{ plannedParts(row).load }}</span>
            <span class="text-slate-600"> · RPE </span>
            <span>{{ plannedParts(row).rpe }}</span>
          </p>
        </li>
      </ul>
    </div>

    <!-- Colonne réalisée -->
    <div class="min-w-0 rounded-xl border border-slate-800 bg-slate-950/40">
      <div class="border-b border-slate-800 px-3 py-2">
        <h4 class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
          {{ t('athleteUi.seriesComparison.performedSession') }}
        </h4>
      </div>
      <ul class="divide-y divide-slate-800/80">
        <li
          v-for="(row, index) in rows"
          :key="`actual-${row.id ?? index}`"
          class="px-3 py-2"
        >
          <p class="truncate text-xs font-medium text-slate-200">{{ title(row) }}</p>
          <p
            v-if="!actualParts(row)"
            class="mt-0.5 text-xs text-slate-500"
          >
            {{ t('app.feedbacks.notLogged') }}
          </p>
          <p
            v-else
            class="mt-0.5 font-mono text-xs tabular-nums"
          >
            <span :class="matchClass(row, 'sets')">{{ actualParts(row).sets }}</span>
            <span class="text-slate-600">×</span>
            <span :class="matchClass(row, 'reps')">{{ actualParts(row).reps }}</span>
            <span class="text-slate-600"> · </span>
            <span :class="matchClass(row, 'load')">{{ actualParts(row).load }}</span>
            <span class="text-slate-600"> · RPE </span>
            <span :class="matchClass(row, 'rpe')">{{ actualParts(row).rpe }}</span>
          </p>
        </li>
      </ul>
    </div>
  </div>
</template>
