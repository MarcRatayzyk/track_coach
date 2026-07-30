<script setup>
import { computed } from 'vue';

const props = defineProps({
  value: { type: Number, default: 0 },
  max: { type: Number, default: 100 },
  size: { type: Number, default: 72 },
  stroke: { type: Number, default: 6 },
  color: { type: String, default: '#3b82f6' },
  trackColor: { type: String, default: 'rgba(51, 65, 85, 0.55)' },
  label: { type: String, default: '' },
});

const radius = computed(() => (props.size - props.stroke) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const percent = computed(() => {
  if (props.max <= 0) {
    return 0;
  }
  return Math.min(1, Math.max(0, props.value / props.max));
});
const offset = computed(() => circumference.value * (1 - percent.value));
const display = computed(() => Math.round(percent.value * 100));
</script>

<template>
  <div class="relative inline-flex items-center justify-center" :style="{ width: `${size}px`, height: `${size}px` }">
    <svg :width="size" :height="size" class="-rotate-90" aria-hidden="true">
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
    <div class="absolute inset-0 flex flex-col items-center justify-center">
      <span class="text-sm font-bold tabular-nums text-white">{{ display }}%</span>
      <span v-if="label" class="text-[9px] font-medium uppercase tracking-wide text-slate-500">{{ label }}</span>
    </div>
  </div>
</template>
