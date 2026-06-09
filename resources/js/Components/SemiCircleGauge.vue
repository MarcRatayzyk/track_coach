<script setup>
import { computed } from 'vue';

const props = defineProps({
  value: {
    type: Number,
    default: 0,
  },
  total: {
    type: Number,
    default: 0,
  },
  color: {
    type: String,
    default: '#f59e0b',
  },
  trackColor: {
    type: String,
    default: 'rgba(51, 65, 85, 0.8)',
  },
  size: {
    type: Number,
    default: 88,
  },
});

const radius = 36;
const arcLength = Math.PI * radius;
const arcPath = `M 14 50 A ${radius} ${radius} 0 0 1 86 50`;

const gaugeValue = computed(() => {
  if (props.total <= 0) {
    return 0;
  }

  return Math.min(props.value, props.total);
});

const percent = computed(() => {
  if (props.total <= 0) {
    return props.value > 0 ? 1 : 0;
  }

  return gaugeValue.value / props.total;
});

const dashOffset = computed(() => arcLength * (1 - percent.value));

const containerHeight = computed(() => Math.round(props.size * 0.72));
</script>

<template>
  <div
    class="relative mx-auto"
    :style="{ width: `${size}px`, height: `${containerHeight}px` }"
  >
    <svg
      :viewBox="'0 0 100 56'"
      class="absolute inset-0 h-full w-full"
      aria-hidden="true"
    >
      <path
        :d="arcPath"
        fill="none"
        :stroke="trackColor"
        stroke-width="7"
        stroke-linecap="round"
      />
      <path
        :d="arcPath"
        fill="none"
        :stroke="color"
        stroke-width="7"
        stroke-linecap="round"
        :stroke-dasharray="`${arcLength} ${arcLength}`"
        :stroke-dashoffset="dashOffset"
        class="transition-[stroke-dashoffset] duration-500 ease-out"
      />
    </svg>

    <div class="absolute inset-x-0 bottom-0 flex items-end justify-center pb-0.5">
      <p class="flex items-baseline justify-center gap-0.5 leading-none">
        <span class="text-lg font-bold tabular-nums text-white">{{ value }}</span>
        <span class="text-sm font-semibold text-slate-500">/</span>
        <span class="text-lg font-bold tabular-nums text-slate-300">{{ total }}</span>
      </p>
    </div>
  </div>
</template>
