<script setup>
import { computed } from 'vue';
import LineChart from './LineChart.vue';
import { formatCalendarFr } from '../../utils/formatDates';

const props = defineProps({
  points: {
    type: Array,
    default: () => [],
  },
  embedded: {
    type: Boolean,
    default: false,
  },
});

const chartData = computed(() => {
  const sorted = [...props.points].sort((a, b) =>
    String(a.session_date).localeCompare(String(b.session_date)),
  );

  return {
    labels: sorted.map((point) =>
      formatCalendarFr(point.session_date, 'medium').split(' ').slice(0, 2).join(' '),
    ),
    datasets: [
      {
        label: 'RPE moyen',
        data: sorted.map((point) => point.average_rpe),
        borderColor: 'rgb(251, 191, 36)',
        backgroundColor: 'rgba(251, 191, 36, 0.12)',
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
      min: 1,
      max: 10,
      ticks: { stepSize: 0.5 },
    },
  },
};
</script>

<template>
  <div :class="embedded ? 'h-56' : 'h-64'">
    <LineChart v-if="points.length" :data="chartData" :options="chartOptions" />
    <p v-else class="flex h-full items-center justify-center text-sm text-slate-500">
      Aucune séance avec RPE sur cette période.
    </p>
  </div>
</template>
