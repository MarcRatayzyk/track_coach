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
        label: 'Poids (kg)',
        data: sortedEntries.value.map((entry) => {
          const n = Number(entry.weight_kg);
          return Number.isFinite(n) ? Math.round(n * 100) / 100 : null;
        }),
        borderColor: 'rgb(168, 85, 247)',
        backgroundColor: 'rgba(168, 85, 247, 0.12)',
        fill: true,
        tension: 0.35,
        pointRadius: 3,
        borderWidth: 2,
      },
    ],
  };
});

const chartOptions = {
  maintainAspectRatio: false,
  scales: {
    y: {
      ticks: {
        callback(value) {
          const n = Number(value);
          if (!Number.isFinite(n)) {
            return value;
          }
          const rounded = Math.round(n * 100) / 100;
          return `${rounded.toLocaleString('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
          })} kg`;
        },
      },
    },
  },
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label(context) {
          const n = Number(context.parsed.y);
          return `${n.toLocaleString('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
          })} kg`;
        },
      },
    },
  },
};

const hasData = computed(() => sortedEntries.value.length > 0);
</script>

<template>
  <div v-if="hasData" :class="embedded ? '' : 'h-64 rounded-xl border border-slate-800 bg-slate-950/40 p-4'">
    <p
      v-if="!embedded"
      class="mb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500"
    >
      Poids du corps
    </p>
    <p
      v-else
      class="mb-3 text-sm font-semibold text-white"
    >
      Poids du corps
    </p>
    <div :class="embedded ? 'h-52' : 'h-48'">
      <LineChart :chart-data="chartData" :options="chartOptions" />
    </div>
  </div>
  <p v-else class="text-sm text-slate-500">
    Aucune saisie de poids sur cette période.
  </p>
</template>
