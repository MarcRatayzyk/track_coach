<script setup>
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
  value: { type: [Number, String], default: 0 },
  duration: { type: Number, default: 700 },
  decimals: { type: Number, default: 0 },
});

const display = ref(0);

function animateTo(target) {
  const end = Number(target) || 0;
  const start = display.value;
  const startTime = performance.now();

  function tick(now) {
    const progress = Math.min(1, (now - startTime) / props.duration);
    const eased = 1 - (1 - progress) ** 3;
    display.value = start + (end - start) * eased;
    if (progress < 1) {
      requestAnimationFrame(tick);
    } else {
      display.value = end;
    }
  }

  requestAnimationFrame(tick);
}

onMounted(() => animateTo(props.value));
watch(() => props.value, (next) => animateTo(next));

const formatted = computed(() =>
  Number(display.value).toLocaleString('fr-FR', {
    maximumFractionDigits: props.decimals,
    minimumFractionDigits: props.decimals,
  }),
);
</script>

<template>
  <span class="tabular-nums">{{ formatted }}</span>
</template>
