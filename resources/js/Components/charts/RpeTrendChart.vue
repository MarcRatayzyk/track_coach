<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { localeTag } from '../../i18n';
import LineChart from './LineChart.vue';
import { formatCalendarDate } from '../../utils/formatDates';

const { t, locale } = useI18n();

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
      formatCalendarDate(point.session_date, 'medium', locale.value).split(' ').slice(0, 2).join(' '),
    ),
    datasets: [
      {
        label: t('charts.avgRpe'),
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

const chartOptions = computed(() => {
  const tag = localeTag(locale.value);
  return {
    maintainAspectRatio: false,
    scales: {
      y: {
        min: 1,
        max: 10,
        ticks: {
          stepSize: 0.5,
          callback(value) {
            const n = Number(value);
            if (!Number.isFinite(n)) {
              return value;
            }
            return (Math.round(n * 100) / 100).toLocaleString(tag, {
              maximumFractionDigits: 2,
            });
          },
        },
      },
    },
  };
});
</script>

<template>
  <div :class="embedded ? 'h-56' : 'h-64'">
    <LineChart v-if="points.length" :chart-data="chartData" :options="chartOptions" />
    <p v-else class="flex h-full items-center justify-center text-sm text-slate-500">
      {{ t('charts.noRpeSessions') }}
    </p>
  </div>
</template>
