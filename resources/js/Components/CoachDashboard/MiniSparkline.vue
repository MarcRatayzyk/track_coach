<script setup>
import { computed } from 'vue';

const props = defineProps({
  values: { type: Array, default: () => [] },
  color: { type: String, default: '#3b82f6' },
  height: { type: Number, default: 36 },
});

const points = computed(() => {
  const vals = (props.values ?? []).map((v) => Number(v) || 0);
  if (!vals.length) {
    return '';
  }
  const max = Math.max(...vals, 1);
  const min = Math.min(...vals, 0);
  const range = Math.max(max - min, 1);
  const w = 100;
  const h = props.height;
  return vals
    .map((v, i) => {
      const x = vals.length === 1 ? w / 2 : (i / (vals.length - 1)) * w;
      const y = h - ((v - min) / range) * (h - 4) - 2;
      return `${x},${y}`;
    })
    .join(' ');
});
</script>

<template>
  <svg
    :viewBox="`0 0 100 ${height}`"
    class="h-9 w-full overflow-visible"
    preserveAspectRatio="none"
    aria-hidden="true"
  >
    <polyline
      fill="none"
      :stroke="color"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      :points="points"
      class="opacity-90"
    />
  </svg>
</template>
