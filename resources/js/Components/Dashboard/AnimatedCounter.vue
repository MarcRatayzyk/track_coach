<script setup>
import { useI18n } from 'vue-i18n';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { localeTag } from '../../i18n';
const { t, locale } = useI18n();

const props = defineProps({
  value: {
    type: Number,
    default: 0,
  },
  duration: {
    type: Number,
    default: 700,
  },
  decimals: {
    type: Number,
    default: 0,
  },
});

const display = ref(0);
let frame = 0;
let startTs = 0;
let from = 0;

function animate(ts) {
  if (!startTs) {
    startTs = ts;
  }
  const progress = Math.min(1, (ts - startTs) / props.duration);
  const eased = 1 - (1 - progress) ** 3;
  display.value = from + (props.value - from) * eased;
  if (progress < 1) {
    frame = requestAnimationFrame(animate);
  } else {
    display.value = props.value;
  }
}

function start() {
  cancelAnimationFrame(frame);
  from = display.value;
  startTs = 0;
  frame = requestAnimationFrame(animate);
}

watch(
  () => props.value,
  () => start(),
);

onMounted(start);
onUnmounted(() => cancelAnimationFrame(frame));

const formatted = () =>
  Number(display.value).toLocaleString(localeTag(locale.value), {
    maximumFractionDigits: props.decimals,
    minimumFractionDigits: props.decimals,
  });
</script>

<template>
  <span class="tabular-nums">{{ formatted() }}</span>
</template>
