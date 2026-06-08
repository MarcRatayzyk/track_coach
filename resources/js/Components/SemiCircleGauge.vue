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

const percent = computed(() => {
  if (props.total <= 0) {
    return props.value > 0 ? 1 : 0;
  }
  return Math.min(props.value / props.total, 1);
});

const dashOffset = computed(() => arcLength * (1 - percent.value));

const arcPath = `M 14 50 A ${radius} ${radius} 0 0 1 86 50`;
</script>

<template>
  <div class="relative inline-flex flex-col items-center" :style="{ width: `${size}px` }">
    <svg
      :viewBox="'0 0 100 56'"
      class="w-full"
      :style="{ height: `${size * 0.58}px` }"
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
    <div
      class="absolute inset-x-0 bottom-0 flex flex-col items-center justify-end pb-0.5"
      :style="{ top: `${size * 0.22}px` }"
    >
      <p class="text-xl font-bold tabular-nums leading-none text-white">
        {{ value }}
        <span class="text-base font-semibold text-slate-400">/ {{ total }}</span>
      </p>
    </div>
  </div>
</template>
