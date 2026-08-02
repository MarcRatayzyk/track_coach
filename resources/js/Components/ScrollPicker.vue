<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  options: {
    type: Array,
    required: true,
  },
  itemHeight: {
    type: Number,
    default: 32,
  },
  visibleCount: {
    type: Number,
    default: 3,
  },
  format: {
    type: Function,
    default: (v) => String(v),
  },
  ariaLabel: {
    type: String,
    default: '',
  },
  /** Si true, aligne sur modelValue sans forcer une valeur par défaut */
  allowEmpty: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);

const scroller = ref(null);
const isUserScrolling = ref(false);
let scrollEndTimer = 0;
let syncing = false;

const paddedOptions = computed(() => props.options);

const viewportHeight = computed(() => props.itemHeight * props.visibleCount);
const padCount = computed(() => Math.floor(props.visibleCount / 2));
const fadeHeight = computed(() => Math.max(12, props.itemHeight * 0.75));

const spacerStyle = computed(() => ({
  height: `${padCount.value * props.itemHeight}px`,
}));

function nearestIndex(value) {
  const opts = paddedOptions.value;
  if (!opts.length) {
    return 0;
  }
  if (value == null || value === '') {
    return 0;
  }
  const num = Number(value);
  if (!Number.isFinite(num)) {
    return 0;
  }
  let best = 0;
  let bestDiff = Infinity;
  for (let i = 0; i < opts.length; i += 1) {
    const diff = Math.abs(Number(opts[i]) - num);
    if (diff < bestDiff) {
      bestDiff = diff;
      best = i;
    }
  }
  return best;
}

function scrollToIndex(index, smooth = false) {
  const el = scroller.value;
  if (!el) {
    return;
  }
  const clamped = Math.max(0, Math.min(index, paddedOptions.value.length - 1));
  syncing = true;
  el.scrollTo({
    top: clamped * props.itemHeight,
    behavior: smooth ? 'smooth' : 'auto',
  });
  window.setTimeout(() => {
    syncing = false;
  }, smooth ? 280 : 40);
}

function scrollToValue(value, smooth = false) {
  scrollToIndex(nearestIndex(value), smooth);
}

function commitFromScroll() {
  const el = scroller.value;
  if (!el || !paddedOptions.value.length) {
    return;
  }
  const index = Math.round(el.scrollTop / props.itemHeight);
  const clamped = Math.max(0, Math.min(index, paddedOptions.value.length - 1));
  const next = paddedOptions.value[clamped];
  scrollToIndex(clamped, true);
  if (Number(props.modelValue) !== Number(next)) {
    emit('update:modelValue', next);
  }
}

function onScroll() {
  if (syncing) {
    return;
  }
  isUserScrolling.value = true;
  window.clearTimeout(scrollEndTimer);
  scrollEndTimer = window.setTimeout(() => {
    isUserScrolling.value = false;
    commitFromScroll();
  }, 80);
}

function selectOption(index) {
  const next = paddedOptions.value[index];
  if (next == null) {
    return;
  }
  emit('update:modelValue', next);
  nextTick(() => scrollToIndex(index, true));
}

onMounted(() => {
  nextTick(() => {
    scrollToValue(props.modelValue, false);
    if (!props.allowEmpty && (props.modelValue == null || props.modelValue === '')) {
      const nearest = paddedOptions.value[nearestIndex(props.modelValue)];
      if (nearest != null) {
        emit('update:modelValue', nearest);
      }
    }
  });
});

watch(
  () => props.modelValue,
  (value) => {
    if (isUserScrolling.value) {
      return;
    }
    nextTick(() => scrollToValue(value, true));
  },
);

watch(
  () => props.options,
  () => {
    nextTick(() => scrollToValue(props.modelValue, false));
  },
);

onUnmounted(() => {
  window.clearTimeout(scrollEndTimer);
});
</script>

<template>
  <div
    class="relative overflow-hidden rounded-lg border border-slate-700 bg-slate-950"
    :style="{ height: `${viewportHeight}px` }"
    :aria-label="ariaLabel"
  >
    <div
      class="pointer-events-none absolute inset-x-0 top-0 z-10 bg-gradient-to-b from-slate-950 via-slate-950/85 to-transparent"
      :style="{ height: `${fadeHeight}px` }"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute inset-x-0 bottom-0 z-10 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent"
      :style="{ height: `${fadeHeight}px` }"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute inset-x-0.5 z-10 rounded-md border border-blue-500/40 bg-blue-500/10"
      :style="{
        top: `${padCount * itemHeight}px`,
        height: `${itemHeight}px`,
      }"
      aria-hidden="true"
    />

    <div
      ref="scroller"
      class="h-full overflow-y-auto overscroll-contain [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
      style="scroll-snap-type: y mandatory; -webkit-overflow-scrolling: touch"
      @scroll.passive="onScroll"
    >
      <div :style="spacerStyle" aria-hidden="true" />
      <button
        v-for="(option, index) in paddedOptions"
        :key="`${option}-${index}`"
        type="button"
        class="flex w-full shrink-0 items-center justify-center font-mono text-sm transition"
        :class="
          nearestIndex(modelValue) === index
            ? 'font-semibold text-white'
            : 'text-slate-500'
        "
        :style="{
          height: `${itemHeight}px`,
          scrollSnapAlign: 'center',
        }"
        @click="selectOption(index)"
      >
        {{ format(option) }}
      </button>
      <div :style="spacerStyle" aria-hidden="true" />
    </div>
  </div>
</template>
