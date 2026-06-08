<script setup>
import { computed } from 'vue';
import BarChart from './charts/BarChart.vue';
import ChartCard from './charts/ChartCard.vue';
import DoughnutChart from './charts/DoughnutChart.vue';
import LineChart from './charts/LineChart.vue';
import { buildChartFromConfig } from '../utils/chartBuilderEngine';
import { GROUP_BY_OPTIONS, METRIC_OPTIONS, metricLabel } from '../config/chartBuilderOptions';

const props = defineProps({
  name: {
    type: String,
    default: 'Graphique personnalisé',
  },
  config: {
    type: Object,
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

const built = computed(() =>
  buildChartFromConfig(props.config, props.sessions, props.dateStart, props.athleteOneRm),
);

const subtitle = computed(() => {
  const metric = METRIC_OPTIONS.find((option) => option.value === props.config.metric)?.label ?? '';
  const group = GROUP_BY_OPTIONS.find((option) => option.value === props.config.groupBy)?.label ?? '';
  return [metric, group].filter(Boolean).join(' · ');
});
</script>

<template>
  <ChartCard
    :title="name"
    :subtitle="subtitle"
    :has-data="built.hasData"
    :empty-message="`Aucune donnée pour « ${metricLabel(config.metric)} » avec les filtres choisis.`"
    chart-height="h-72"
  >
    <BarChart
      v-if="config.chartType === 'bar'"
      :chart-data="{ labels: built.labels, datasets: built.datasets }"
      :stacked="config.stacked"
      :options="built.chartOptions"
    />
    <LineChart
      v-else-if="config.chartType === 'line'"
      :chart-data="{ labels: built.labels, datasets: built.datasets }"
      :options="built.chartOptions"
    />
    <DoughnutChart
      v-else
      :chart-data="{ labels: built.labels, datasets: built.datasets }"
      :options="built.chartOptions"
    />
  </ChartCard>
</template>
