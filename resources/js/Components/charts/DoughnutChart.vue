<script setup>
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { doughnutChartOptions } from '../../utils/chartTheme';

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

const mergedOptions = computed(() => doughnutChartOptions(props.options));
</script>

<template>
  <div class="h-full w-full">
    <Doughnut :data="chartData" :options="mergedOptions" />
  </div>
</template>
