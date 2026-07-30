<script setup>
import { computed } from 'vue';

const props = defineProps({
  value: { type: Number, default: 0 },
  total: { type: Number, default: 100 },
  size: { type: Number, default: 56 },
  stroke: { type: Number, default: 5 },
  color: { type: String, default: '#3b82f6' },
  trackColor: { type: String, default: 'rgba(51, 65, 85, 0.55)' },
});

const radius = computed(() => (props.size - props.stroke) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const progress = computed(() => {
  if (!props.total) {
    return 0;
  }
  return Math.max(0, Math.min(1, props.value / props.total));
});
const offset = computed(() => circumference.value * (1 - progress.value));
</script>

<template>
  <svg :width="size" :height="size" class="shrink-0 -rotate-90" aria-hidden="true">
    <circle
      :cx="size / 2"
      :cy="size / 2"
      :r="radius"
      fill="none"
      :stroke="trackColor"
      :stroke-width="stroke"
    />
    <circle
      :cx="size / 2"
      :cy="size / 2"
      :r="radius"
      fill="none"
      :stroke="color"
      :stroke-width="stroke"
      stroke-linecap="round"
      :stroke-dasharray="circumference"
      :stroke-dashoffset="offset"
      class="transition-[stroke-dashoffset] duration-700 ease-out"
    />
  </svg>
</template>
