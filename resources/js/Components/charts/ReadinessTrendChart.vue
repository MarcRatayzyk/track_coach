<script setup>
import { computed } from 'vue';
import LineChart from './LineChart.vue';
import { formatCalendarFr } from '../../utils/formatDates';

const props = defineProps({
  entries: {
    type: Array,
    default: () => [],
  },
  embedded: {
    type: Boolean,
    default: false,
  },
});

const sortedEntries = computed(() =>
  [...props.entries].sort((a, b) => String(a.entry_date).localeCompare(String(b.entry_date))),
);

const chartData = computed(() => {
  const labels = sortedEntries.value.map((entry) =>
    formatCalendarFr(entry.entry_date, 'medium').split(' ').slice(0, 2).join(' '),
  );

  return {
    labels,
    datasets: [
      {
        label: 'Readiness',
        data: sortedEntries.value.map((entry) => entry.score),
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.15)',
        fill: true,
        tension: 0.35,
        pointRadius: 3,
        borderWidth: 2,
      },
      {
        label: 'Sommeil',
        data: sortedEntries.value.map((entry) => entry.sleep_score),
        borderColor: 'rgba(148, 163, 184, 0.6)',
        borderDash: [4, 4],
        pointRadius: 0,
        borderWidth: 1,
        fill: false,
        tension: 0.35,
      },
      {
        label: 'Détente',
        data: sortedEntries.value.map((entry) => entry.stress_score),
        borderColor: 'rgba(52, 211, 153, 0.6)',
        borderDash: [4, 4],
        pointRadius: 0,
        borderWidth: 1,
        fill: false,
        tension: 0.35,
      },
      {
        label: 'Motivation',
        data: sortedEntries.value.map((entry) => entry.motivation_score),
        borderColor: 'rgba(251, 191, 36, 0.6)',
        borderDash: [4, 4],
        pointRadius: 0,
        borderWidth: 1,
        fill: false,
        tension: 0.35,
      },
    ],
  };
});

const chartOptions = {
  maintainAspectRatio: false,
  scales: {
    y: {
      min: 1,
      max: 10,
      ticks: { stepSize: 1 },
    },
  },
  plugins: {
    legend: {
      display: true,
      labels: { boxWidth: 10, font: { size: 10 } },
    },
  },
};

const hasData = computed(() => sortedEntries.value.length > 0);
</script>

<template>
  <div
    v-if="hasData"
    :class="embedded ? 'h-64' : 'h-36 rounded-xl border border-slate-800 bg-slate-950/40 p-3'"
  >
    <p
      v-if="!embedded"
      class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500"
    >
      Tendance 7 jours
    </p>
    <div :class="embedded ? 'h-full' : 'h-24'">
      <LineChart :chart-data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>
