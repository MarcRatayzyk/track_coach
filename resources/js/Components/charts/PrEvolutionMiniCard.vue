<script setup>
import { computed } from 'vue';
import LineChart from './LineChart.vue';
import { formatCalendarFr } from '../../utils/formatDates';
import { glowCardStyle, LIFT_COLORS, prGlowCardStyle } from '../../utils/chartTheme';
import { theme } from '../../composables/useTheme';
import { currentValueFromSeries, seriesGain } from '../../utils/prEvolution';

const props = defineProps({
  liftKey: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  series: {
    type: Array,
    default: () => [],
  },
  usePrGlow: {
    type: Boolean,
    default: false,
  },
  colorKey: {
    type: String,
    default: null,
  },
});

const liftColors = computed(() => LIFT_COLORS[props.colorKey ?? props.liftKey]);

const currentValue = computed(() => currentValueFromSeries(props.series));

const gainKg = computed(() => seriesGain(props.series));

const hasData = computed(() => props.series.some((point) => Number(point.value) > 0));

const cardStyle = computed(() => {
  void theme.value;

  if (props.usePrGlow) {
    return prGlowCardStyle();
  }

  return glowCardStyle(liftColors.value);
});

function rgbaFromRgb(rgb, alpha) {
  return String(rgb).replace('rgb(', 'rgba(').replace(')', `, ${alpha})`);
}

const fillColor = computed(() => rgbaFromRgb(liftColors.value.border, 0.32));

const chartData = computed(() => ({
  labels: props.series.map((point) => formatCalendarFr(point.date, 'short')),
  datasets: [
    {
      label: props.label,
      data: props.series.map((point) => point.value),
      borderColor: liftColors.value.border,
      backgroundColor: fillColor.value,
      tension: 0.3,
      fill: 'origin',
      pointRadius: props.series.length <= 3 ? 3 : 0,
      pointHoverRadius: 4,
      pointBackgroundColor: liftColors.value.border,
      borderWidth: 2,
    },
  ],
}));

const chartOptions = computed(() => {
  const values = props.series.map((point) => Number(point.value ?? 0));
  const min = values.length ? Math.min(...values) : 0;
  const max = values.length ? Math.max(...values) : 0;
  const padding = max > min ? Math.max(2, Math.round((max - min) * 0.12)) : 5;

  return {
    maintainAspectRatio: false,
    layout: {
      padding: { top: 2, bottom: 0, left: 0, right: 0 },
    },
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          title(items) {
            const index = items[0]?.dataIndex ?? 0;
            const point = props.series[index];
            return point ? formatCalendarFr(point.date, 'medium') : '';
          },
          label(context) {
            return `${props.label}: ${context.parsed.y} kg`;
          },
        },
      },
    },
    scales: {
      x: {
        display: true,
        grid: { display: false },
        border: { display: false },
        ticks: {
          maxTicksLimit: 3,
          maxRotation: 0,
          autoSkip: true,
          color: 'rgb(100, 116, 139)',
          font: { size: 9, weight: '500' },
          padding: 2,
        },
      },
      y: {
        display: false,
        min: Math.max(0, min - padding),
        max: max + padding,
        grid: { display: false },
      },
    },
    interaction: {
      intersect: false,
      mode: 'index',
    },
  };
});

function formatKgCompact(value) {
  const numeric = Number(value ?? 0);
  return numeric > 0 ? String(numeric) : '—';
}
</script>

<template>
  <article
    class="glow-card flex min-h-[112px] flex-col rounded-lg px-2.5 py-2 transition-shadow duration-300"
    :style="cardStyle"
  >
    <div class="flex items-start justify-between gap-2">
      <p
        class="text-[9px] font-semibold uppercase tracking-wide"
        :style="{ color: liftColors.border }"
      >
        {{ label }}
      </p>
      <div class="text-right">
        <p class="text-base font-bold tabular-nums leading-tight text-white">
          {{ formatKgCompact(currentValue) }}
        </p>
        <p
          v-if="gainKg > 0"
          class="text-[10px] font-semibold tabular-nums leading-tight text-emerald-400"
        >
          +{{ gainKg }} kg
        </p>
      </div>
    </div>

    <div v-if="hasData && series.length >= 2" class="relative mt-1 h-[4.75rem] w-full">
      <LineChart :chart-data="chartData" :options="chartOptions" />
    </div>
    <div
      v-else-if="hasData"
      class="mt-1 flex h-[4.75rem] items-center justify-center rounded-md border border-dashed border-slate-700/80 bg-slate-950/30"
    >
      <p class="text-[10px] text-slate-500">Premier repère enregistré</p>
    </div>
    <p v-else class="mt-auto pt-2 text-center text-[10px] text-slate-400">Pas encore de données</p>
  </article>
</template>

<style scoped>
.glow-card:hover {
  filter: brightness(1.04);
}
</style>
