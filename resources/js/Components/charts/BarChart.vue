<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { baseChartOptions } from '../../utils/chartTheme';

const props = defineProps({
  chartData: {
    type: Object,
    required: true,
  },
  stacked: {
    type: Boolean,
    default: false,
  },
  options: {
    type: Object,
    default: () => ({}),
  },
});

const mergedOptions = computed(() =>
  baseChartOptions({
    scales: {
      x: { stacked: props.stacked },
      y: {
        stacked: props.stacked,
        beginAtZero: true,
        title: props.options?.scales?.y?.title,
      },
    },
    ...props.options,
  }),
);
</script>

<template>
  <div class="h-full w-full">
    <Bar :data="chartData" :options="mergedOptions" />
  </div>
</template>
