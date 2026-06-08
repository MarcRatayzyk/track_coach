<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import { baseChartOptions } from '../../utils/chartTheme';

const props = defineProps({
  chartData: {
    type: Object,
    required: true,
  },
  options: {
    type: Object,
    default: () => ({}),
  },
});

const mergedOptions = computed(() =>
  baseChartOptions({
    plugins: {
      legend: { display: props.chartData?.datasets?.length > 1 },
    },
    ...props.options,
  }),
);
</script>

<template>
  <div class="h-full w-full">
    <Line :data="chartData" :options="mergedOptions" />
  </div>
</template>
