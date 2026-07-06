<script setup>
import { computed, ref, watch } from 'vue';
import BarChart from './charts/BarChart.vue';
import ChartCard from './charts/ChartCard.vue';
import DoughnutChart from './charts/DoughnutChart.vue';
import LineChart from './charts/LineChart.vue';
import { LIFT_COLORS, LIFT_LABELS } from '../utils/chartTheme';
import {
  MAIN_LIFT_FILTER_OPTIONS,
  REP_FORMAT_OPTIONS,
  avgLoadByWeekAndLift,
  flattenBlockItems,
  formatTopsetDayLabel,
  formatTopsetSeriesLabel,
  topsetE1rmByWeek,
  volumeByWeek,
  volumeForDonut,
} from '../utils/trainingVolume';
import { BUILTIN_CHART_KEYS, BUILTIN_CHART_META } from '../config/chartBuilderOptions';

const TOPSET_LIFT_KEYS = ['squat', 'bench', 'deadlift'];

const props = defineProps({
  builtinKey: {
    type: String,
    required: true,
  },
  sessions: {
    type: Object,
    default: () => ({}),
  },
  dateStart: {
    type: String,
    default: '',
  },
  athleteOneRm: {
    type: Object,
    default: () => ({ squat: 0, bench: 0, deadlift: 0 }),
  },
});

const meta = computed(() => BUILTIN_CHART_META[props.builtinKey] ?? { title: '' });

const oneRm = computed(() => ({
  squat: Number(props.athleteOneRm?.squat ?? 0),
  bench: Number(props.athleteOneRm?.bench ?? 0),
  deadlift: Number(props.athleteOneRm?.deadlift ?? 0),
}));

const flatItems = computed(() => flattenBlockItems(props.sessions, props.dateStart));

const donutMainLift = ref('all');
const donutFormat = ref('all');
const selectedTopsetPoint = ref(null);

const weeklyVolume = computed(() => volumeByWeek(flatItems.value, oneRm.value));
const hasWeeklyVolume = computed(() => weeklyVolume.value.labels.length > 0);

const stackedBarData = computed(() => ({
  labels: weeklyVolume.value.labels,
  datasets: TOPSET_LIFT_KEYS.map((lift) => ({
    label: LIFT_LABELS[lift],
    data: weeklyVolume.value[lift],
    backgroundColor: LIFT_COLORS[lift].bg,
    borderColor: LIFT_COLORS[lift].border,
    borderWidth: 1,
  })),
}));

const stackedBarOptions = {
  scales: {
    x: { stacked: true },
    y: {
      stacked: true,
      title: { display: true, text: 'Volume (kg·reps)', color: 'rgb(148, 163, 184)' },
    },
  },
  plugins: {
    tooltip: {
      callbacks: {
        label(context) {
          return `${context.dataset.label}: ${context.parsed.y.toLocaleString('fr-FR')} kg·reps`;
        },
      },
    },
  },
};

const donutTotals = computed(() =>
  volumeForDonut(flatItems.value, oneRm.value, {
    mainLift: donutMainLift.value,
    format: donutFormat.value,
  }),
);

const donutTotalSum = computed(
  () => donutTotals.value.squat + donutTotals.value.bench + donutTotals.value.deadlift,
);

const hasDonutData = computed(() => donutTotalSum.value > 0);

const donutData = computed(() => ({
  labels: TOPSET_LIFT_KEYS.map((lift) => LIFT_LABELS[lift]),
  datasets: [
    {
      data: TOPSET_LIFT_KEYS.map((lift) => donutTotals.value[lift]),
      backgroundColor: TOPSET_LIFT_KEYS.map((lift) => LIFT_COLORS[lift].border),
      borderColor: TOPSET_LIFT_KEYS.map((lift) => LIFT_COLORS[lift].border),
      borderAlign: 'inner',
      borderWidth: 1,
      spacing: 0,
    },
  ],
}));

const topsetE1rm = computed(() => topsetE1rmByWeek(flatItems.value, oneRm.value));

const hasTopsetE1rm = computed(() =>
  topsetE1rm.value.weeks.some((_, index) =>
    TOPSET_LIFT_KEYS.some((lift) => topsetE1rm.value.series[lift][index] != null),
  ),
);

const topsetE1rmChartData = computed(() => ({
  labels: topsetE1rm.value.labels,
  datasets: TOPSET_LIFT_KEYS.map((lift) => ({
    label: LIFT_LABELS[lift],
    data: topsetE1rm.value.series[lift],
    borderColor: LIFT_COLORS[lift].border,
    backgroundColor: LIFT_COLORS[lift].bg,
    tension: 0.25,
    spanGaps: true,
    pointRadius: 4,
    pointHoverRadius: 6,
  })),
}));

function handleTopsetChartClick(_event, elements) {
  if (!elements?.length) {
    return;
  }

  const { datasetIndex, index } = elements[0];
  const lift = TOPSET_LIFT_KEYS[datasetIndex];
  const detail = topsetE1rm.value.details?.[lift]?.[index];

  if (!detail) {
    return;
  }

  selectedTopsetPoint.value = {
    liftLabel: LIFT_LABELS[lift],
    weekLabel: topsetE1rm.value.labels[index] ?? '',
    dayLabel: formatTopsetDayLabel(detail),
    seriesLabel: formatTopsetSeriesLabel(detail),
    sessionLabel: detail.sessionLabel?.trim() ?? '',
    e1rm: detail.e1rm,
  };
}

const topsetE1rmOptions = computed(() => ({
  scales: {
    y: {
      title: { display: true, text: 'e1RM (kg)', color: 'rgb(148, 163, 184)' },
    },
  },
  plugins: {
    tooltip: {
      callbacks: {
        label(context) {
          const value = context.parsed.y;
          if (value == null) {
            return `${context.dataset.label}: —`;
          }

          const lift = TOPSET_LIFT_KEYS[context.datasetIndex];
          const detail = topsetE1rm.value.details?.[lift]?.[context.dataIndex];
          const lines = [`${context.dataset.label}: ${value} kg (e1RM)`];

          if (detail) {
            lines.push(`  ${formatTopsetDayLabel(detail)}`);
            lines.push(`  ${formatTopsetSeriesLabel(detail)}`);
          }

          return lines;
        },
      },
    },
  },
  onClick: handleTopsetChartClick,
  onHover(event, elements) {
    const target = event.native?.target;
    if (target) {
      target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
    }
  },
}));

watch(topsetE1rm, () => {
  selectedTopsetPoint.value = null;
});

const avgLoad = computed(() => avgLoadByWeekAndLift(flatItems.value, oneRm.value));

const hasAvgLoad = computed(() =>
  avgLoad.value.weeks.some((_, index) =>
    TOPSET_LIFT_KEYS.some((lift) => avgLoad.value.series[lift][index] != null),
  ),
);

const avgLoadChartData = computed(() => ({
  labels: avgLoad.value.labels,
  datasets: TOPSET_LIFT_KEYS.map((lift) => ({
    label: LIFT_LABELS[lift],
    data: avgLoad.value.series[lift],
    borderColor: LIFT_COLORS[lift].border,
    backgroundColor: LIFT_COLORS[lift].bg,
    tension: 0.25,
    spanGaps: true,
    pointRadius: 4,
  })),
}));

const avgLoadOptions = {
  scales: {
    y: {
      title: { display: true, text: 'Charge moyenne (kg)', color: 'rgb(148, 163, 184)' },
    },
  },
  plugins: {
    tooltip: {
      callbacks: {
        label(context) {
          const value = context.parsed.y;
          return value != null ? `${context.dataset.label}: ${value} kg` : `${context.dataset.label}: —`;
        },
      },
    },
  },
};

const hasData = computed(() => {
  switch (props.builtinKey) {
    case BUILTIN_CHART_KEYS.VOLUME_WEEKLY:
      return hasWeeklyVolume.value;
    case BUILTIN_CHART_KEYS.TOPSET_E1RM:
      return hasTopsetE1rm.value;
    case BUILTIN_CHART_KEYS.VOLUME_DISTRIBUTION:
      return hasDonutData.value;
    case BUILTIN_CHART_KEYS.AVG_LOAD_WEEKLY:
      return hasAvgLoad.value;
    default:
      return false;
  }
});

const emptyMessage = computed(() => {
  switch (props.builtinKey) {
    case BUILTIN_CHART_KEYS.VOLUME_WEEKLY:
      return 'Programme des séances pour voir le volume par semaine.';
    case BUILTIN_CHART_KEYS.TOPSET_E1RM:
      return 'Programme des topsets en kg ou % du 1RM pour voir l’évolution.';
    case BUILTIN_CHART_KEYS.VOLUME_DISTRIBUTION:
      return 'Aucun volume calculable avec les filtres choisis.';
    case BUILTIN_CHART_KEYS.AVG_LOAD_WEEKLY:
      return 'Aucune charge en kg sur ce bloc.';
    default:
      return 'Aucune donnée.';
  }
});

const chartHeight = computed(() =>
  props.builtinKey === BUILTIN_CHART_KEYS.VOLUME_DISTRIBUTION ? 'h-56' : 'h-72',
);
</script>

<template>
  <ChartCard
    :title="meta.title"
    :has-data="hasData"
    :empty-message="emptyMessage"
    :chart-height="chartHeight"
  >
    <template v-if="builtinKey === BUILTIN_CHART_KEYS.VOLUME_DISTRIBUTION" #header>
      <div class="mb-4 grid gap-3 sm:grid-cols-2">
        <label class="block text-xs text-slate-400">
          Main lift
          <select
            v-model="donutMainLift"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white"
          >
            <option v-for="opt in MAIN_LIFT_FILTER_OPTIONS" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </label>
        <label class="block text-xs text-slate-400">
          Format
          <select
            v-model="donutFormat"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white"
          >
            <option v-for="opt in REP_FORMAT_OPTIONS" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </label>
      </div>
    </template>

    <BarChart
      v-if="builtinKey === BUILTIN_CHART_KEYS.VOLUME_WEEKLY"
      :chart-data="stackedBarData"
      stacked
      :options="stackedBarOptions"
    />

    <LineChart
      v-else-if="builtinKey === BUILTIN_CHART_KEYS.TOPSET_E1RM"
      :chart-data="topsetE1rmChartData"
      :options="topsetE1rmOptions"
    />

    <DoughnutChart
      v-else-if="builtinKey === BUILTIN_CHART_KEYS.VOLUME_DISTRIBUTION"
      :chart-data="donutData"
    />

    <LineChart
      v-else-if="builtinKey === BUILTIN_CHART_KEYS.AVG_LOAD_WEEKLY"
      :chart-data="avgLoadChartData"
      :options="avgLoadOptions"
    />

    <template v-if="builtinKey === BUILTIN_CHART_KEYS.TOPSET_E1RM" #footer>
      <div
        v-if="selectedTopsetPoint"
        class="mt-3 rounded-lg border border-slate-700/80 bg-slate-900/60 px-3 py-2.5 text-xs"
      >
        <p class="font-semibold text-slate-200">
          {{ selectedTopsetPoint.liftLabel }} · {{ selectedTopsetPoint.weekLabel }}
          <span class="font-normal text-slate-500">· e1RM {{ selectedTopsetPoint.e1rm }} kg</span>
        </p>
        <p class="mt-1 text-slate-300">{{ selectedTopsetPoint.dayLabel }}</p>
        <p class="mt-0.5 text-slate-400">{{ selectedTopsetPoint.seriesLabel }}</p>
        <p v-if="selectedTopsetPoint.sessionLabel" class="mt-1 text-slate-500">
          Séance : {{ selectedTopsetPoint.sessionLabel }}
        </p>
      </div>
      <p v-else class="mt-2 text-center text-xs text-slate-500">
        Clique sur un point pour afficher le jour et la série du topset.
      </p>
    </template>

    <template v-if="builtinKey === BUILTIN_CHART_KEYS.VOLUME_DISTRIBUTION" #footer>
      <p class="mt-2 text-center text-xs text-slate-500">
        Total :
        <span class="font-semibold text-slate-300">
          {{ Math.round(donutTotalSum).toLocaleString('fr-FR') }} kg·reps
        </span>
      </p>
    </template>
  </ChartCard>
</template>
