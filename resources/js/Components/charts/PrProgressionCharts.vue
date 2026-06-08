<script setup>
import { computed, ref } from 'vue';
import ChartCard from './ChartCard.vue';
import LineChart from './LineChart.vue';
import { LIFT_COLORS, LIFT_LABELS } from '../../utils/chartTheme';
import { formatCalendarFr } from '../../utils/formatDates';

const LIFT_KEYS = ['squat', 'bench', 'deadlift', 'total'];
const selectedLift = ref('total');

const props = defineProps({
  records: {
    type: Array,
    default: () => [],
  },
  compact: {
    type: Boolean,
    default: false,
  },
  embedded: {
    type: Boolean,
    default: false,
  },
});

const chartHeight = computed(() => {
  if (props.embedded) {
    return 'h-64';
  }

  return props.compact ? 'h-44' : 'h-80';
});

const sortedRecords = computed(() =>
  [...props.records].sort((a, b) =>
    String(a.reference_date ?? '').localeCompare(String(b.reference_date ?? '')),
  ),
);

const hasData = computed(() => sortedRecords.value.length > 0);

const labels = computed(() =>
  sortedRecords.value.map((r) => formatCalendarFr(r.reference_date, 'medium')),
);

function buildDataset(key) {
  const colors = LIFT_COLORS[key];
  return {
    label: LIFT_LABELS[key],
    data: sortedRecords.value.map((r) => {
      if (key === 'total') {
        return (
          Number(r.squat ?? 0) + Number(r.bench ?? 0) + Number(r.deadlift ?? 0)
        );
      }
      return Number(r[key] ?? 0);
    }),
    borderColor: colors.border,
    backgroundColor: colors.bg,
    tension: 0.25,
    fill: false,
    pointRadius: 4,
    pointHoverRadius: 6,
  };
}

const chartData = computed(() => ({
  labels: labels.value,
  datasets: [buildDataset(selectedLift.value)],
}));

function liftButtonStyle(key) {
  const { border, bg } = LIFT_COLORS[key];
  const active = selectedLift.value === key;

  return {
    borderColor: border,
    backgroundColor: active ? border : bg,
    color: active ? 'rgb(15, 23, 42)' : border,
  };
}

function liftValue(record, key) {
  if (key === 'total') {
    return Number(record.squat ?? 0) + Number(record.bench ?? 0) + Number(record.deadlift ?? 0);
  }

  return Number(record[key] ?? 0);
}

const liftStats = computed(() => {
  const key = selectedLift.value;

  if (!sortedRecords.value.length) {
    return { current: 0, diff: 0, label: LIFT_LABELS[key] };
  }

  const first = sortedRecords.value[0];
  const last = sortedRecords.value[sortedRecords.value.length - 1];
  const start = liftValue(first, key);
  const current = liftValue(last, key);

  return { current, diff: current - start, label: LIFT_LABELS[key] };
});

const chartOptions = {
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label(context) {
          return `${context.dataset.label}: ${context.parsed.y} kg`;
        },
      },
    },
  },
  scales: {
    y: {
      title: { display: true, text: 'kg', color: 'rgb(148, 163, 184)' },
    },
  },
};
</script>

<template>
  <ChartCard
    v-if="!embedded"
    title="Évolution des PR"
    subtitle="Squat, bench, terre et total officiel"
    :has-data="hasData"
    empty-message="Aucun PR enregistré sur cette période."
    :chart-height="chartHeight"
  >
    <template #header>
      <div
        v-if="hasData"
        class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-blue-500/30 bg-blue-950/20 px-3 py-2"
      >
        <div>
          <p class="text-xs uppercase tracking-wide text-blue-300/80">{{ liftStats.label }} actuel</p>
          <p class="text-xl font-bold text-white">{{ liftStats.current }} kg</p>
        </div>
        <p
          class="rounded-full px-2.5 py-1 text-xs font-semibold"
          :class="
            liftStats.diff >= 0
              ? 'bg-emerald-500/15 text-emerald-300'
              : 'bg-red-500/15 text-red-300'
          "
        >
          {{ liftStats.diff >= 0 ? '+' : '' }}{{ liftStats.diff }} kg
          <span class="font-normal text-slate-500"> sur la période</span>
        </p>
      </div>
      <div v-if="hasData" class="mb-3 flex flex-wrap gap-2">
        <button
          v-for="key in LIFT_KEYS"
          :key="key"
          type="button"
          class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors"
          :style="liftButtonStyle(key)"
          :aria-pressed="selectedLift === key"
          @click="selectedLift = key"
        >
          {{ LIFT_LABELS[key] }}
        </button>
      </div>
    </template>
    <LineChart :chart-data="chartData" :options="chartOptions" />
  </ChartCard>

  <div v-else>
    <div
      v-if="hasData"
      class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-blue-500/30 bg-blue-950/20 px-3 py-2"
    >
      <div>
        <p class="text-xs uppercase tracking-wide text-blue-300/80">{{ liftStats.label }} actuel</p>
        <p class="text-xl font-bold text-white">{{ liftStats.current }} kg</p>
      </div>
      <p
        class="rounded-full px-2.5 py-1 text-xs font-semibold"
        :class="
          liftStats.diff >= 0
            ? 'bg-emerald-500/15 text-emerald-300'
            : 'bg-red-500/15 text-red-300'
        "
      >
        {{ liftStats.diff >= 0 ? '+' : '' }}{{ liftStats.diff }} kg
        <span class="font-normal text-slate-500"> sur la période</span>
      </p>
    </div>
    <div v-if="hasData" class="mt-3 flex flex-wrap gap-2">
      <button
        v-for="key in LIFT_KEYS"
        :key="key"
        type="button"
        class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors"
        :style="liftButtonStyle(key)"
        :aria-pressed="selectedLift === key"
        @click="selectedLift = key"
      >
        {{ LIFT_LABELS[key] }}
      </button>
    </div>
    <div v-if="hasData" :class="[chartHeight, 'relative mt-3 w-full overflow-hidden']">
      <div class="absolute inset-0">
        <LineChart :chart-data="chartData" :options="chartOptions" />
      </div>
    </div>
    <p v-else class="py-8 text-center text-sm text-slate-500">
      Aucun PR enregistré sur cette période.
    </p>
  </div>
</template>
