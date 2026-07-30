<script setup>
import { computed } from 'vue';

const props = defineProps({
  points: {
    type: Array,
    default: () => [],
  },
  color: {
    type: String,
    default: '#60a5fa',
  },
  height: {
    type: Number,
    default: 36,
  },
  width: {
    type: Number,
    default: 96,
  },
});

const path = computed(() => {
  const values = props.points.length ? props.points : [0, 0];
  const min = Math.min(...values);
  const max = Math.max(...values);
  const range = max - min || 1;
  const pad = 2;
  const w = props.width - pad * 2;
  const h = props.height - pad * 2;

  return values
    .map((v, i) => {
      const x = pad + (i / Math.max(values.length - 1, 1)) * w;
      const y = pad + h - ((v - min) / range) * h;
      return `${i === 0 ? 'M' : 'L'}${x.toFixed(1)} ${y.toFixed(1)}`;
    })
    .join(' ');
});

const area = computed(() => {
  if (!path.value) {
    return '';
  }
  return `${path.value} L${props.width - 2} ${props.height - 2} L2 ${props.height - 2} Z`;
});
</script>

<template>
  <svg
    :width="width"
    :height="height"
    :viewBox="`0 0 ${width} ${height}`"
    class="overflow-visible"
    aria-hidden="true"
  >
    <path :d="area" :fill="color" fill-opacity="0.12" />
    <path
      :d="path"
      fill="none"
      :stroke="color"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
  </svg>
</template>
