<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { localeTag } from '../../i18n';
import LineChart from './LineChart.vue';
import { formatCalendarDate } from '../../utils/formatDates';

const { t, locale } = useI18n();

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
    formatCalendarDate(entry.entry_date, 'medium', locale.value).split(' ').slice(0, 2).join(' '),
  );

  return {
    labels,
    datasets: [
      {
        label: t('charts.weightKg'),
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

const chartOptions = computed(() => {
  const tag = localeTag(locale.value);
  return {
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
            return `${rounded.toLocaleString(tag, {
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
            return `${n.toLocaleString(tag, {
              minimumFractionDigits: 0,
              maximumFractionDigits: 2,
            })} kg`;
          },
        },
      },
    },
  };
});

const hasData = computed(() => sortedEntries.value.length > 0);
</script>

<template>
  <div v-if="hasData" :class="embedded ? '' : 'h-64 rounded-xl border border-slate-800 bg-slate-950/40 p-4'">
    <p
      v-if="!embedded"
      class="mb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500"
    >
      {{ t('charts.bodyWeight') }}
    </p>
    <p
      v-else
      class="mb-3 text-sm font-semibold text-white"
    >
      {{ t('charts.bodyWeight') }}
    </p>
    <div :class="embedded ? 'h-52' : 'h-48'">
      <LineChart :chart-data="chartData" :options="chartOptions" />
    </div>
  </div>
  <p v-else class="text-sm text-slate-500">
    {{ t('charts.noBodyWeight') }}
  </p>
</template>
