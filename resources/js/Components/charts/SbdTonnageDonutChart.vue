<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { localeTag } from '../../i18n';
import { buildDistribution } from '../../utils/athleteOverviewStats';
import { LIFT_COLORS, LIFT_LABELS } from '../../utils/chartTheme';
import DoughnutChart from './DoughnutChart.vue';

const { t, locale } = useI18n();

const LIFTS = ['squat', 'bench', 'deadlift'];

const props = defineProps({
  flatItems: {
    type: Array,
    default: () => [],
  },
});

const timeRangeOptions = computed(() => [
  { value: '7d', label: t('charts.range7d'), days: 7 },
  { value: '1m', label: t('charts.range1m'), days: 30 },
  { value: '3m', label: t('charts.range3m'), days: 90 },
  { value: '6m', label: t('charts.range6m'), days: 180 },
  { value: '1y', label: t('charts.range1y'), days: 365 },
]);

const timeRange = ref('1m');

const recentDays = computed(
  () => timeRangeOptions.value.find((option) => option.value === timeRange.value)?.days ?? 30,
);

const distribution = computed(() => buildDistribution(props.flatItems, recentDays.value));

const hasData = computed(() => distribution.value.items.some((item) => item.value > 0));

const chartData = computed(() => ({
  labels: LIFTS.map((lift) => LIFT_LABELS[lift]),
  datasets: [
    {
      data: LIFTS.map((lift) => {
        const item = distribution.value.items.find((entry) => entry.lift === lift);
        return item?.value ?? 0;
      }),
      backgroundColor: LIFTS.map((lift) => LIFT_COLORS[lift].border),
      borderColor: LIFTS.map((lift) => LIFT_COLORS[lift].border),
      borderAlign: 'inner',
      borderWidth: 1,
      spacing: 0,
    },
  ],
}));

const totalTonnage = computed(() =>
  distribution.value.items.reduce((sum, item) => sum + item.value, 0),
);

const tonnageLabel = computed(() =>
  t('charts.tonnagePeriod', {
    value: totalTonnage.value.toLocaleString(localeTag(locale.value)),
  }),
);
</script>

<template>
  <article class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h3 class="text-sm font-semibold text-white">{{ t('charts.sbdDistribution') }}</h3>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="option in timeRangeOptions"
          :key="option.value"
          type="button"
          class="rounded-lg border px-2.5 py-1 text-xs font-medium transition"
          :class="
            timeRange === option.value
              ? 'border-violet-400/70 bg-violet-500/20 text-violet-200'
              : 'border-slate-700 text-slate-400 hover:bg-slate-800'
          "
          @click="timeRange = option.value"
        >
          {{ option.label }}
        </button>
      </div>
    </div>

    <div v-if="hasData" class="mt-4">
      <p class="mb-2 text-center text-xs text-slate-500">
        {{ tonnageLabel }}
      </p>
      <div class="mx-auto h-56 max-w-xs">
        <DoughnutChart :chart-data="chartData" />
      </div>
      <ul class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
        <li v-for="item in distribution.items" :key="item.lift">
          <span class="block text-slate-500">{{ item.label }}</span>
          <span class="font-semibold tabular-nums text-white">{{ item.share }} %</span>
        </li>
      </ul>
    </div>
    <p v-else class="mt-6 text-center text-sm text-slate-500">—</p>
  </article>
</template>
