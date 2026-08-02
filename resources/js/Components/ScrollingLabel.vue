<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  text: {
    type: String,
    required: true,
  },
});

const viewport = ref(null);
const content = ref(null);
const needsScroll = ref(false);
const distance = ref(0);
let resizeObserver = null;

function measure() {
  const box = viewport.value;
  const el = content.value;
  if (!box || !el) {
    needsScroll.value = false;
    distance.value = 0;
    return;
  }

  const overflow = el.scrollWidth - box.clientWidth;
  needsScroll.value = overflow > 1;
  distance.value = Math.max(0, overflow);
}

onMounted(() => {
  nextTick(measure);
  if (typeof ResizeObserver !== 'undefined' && viewport.value) {
    resizeObserver = new ResizeObserver(() => measure());
    resizeObserver.observe(viewport.value);
    if (content.value) {
      resizeObserver.observe(content.value);
    }
  }
});

onBeforeUnmount(() => {
  resizeObserver?.disconnect();
  resizeObserver = null;
});

watch(
  () => props.text,
  () => nextTick(measure),
);
</script>

<template>
  <div
    ref="viewport"
    class="max-w-full overflow-hidden"
  >
    <span
      ref="content"
      class="inline-block whitespace-nowrap"
      :class="needsScroll ? 'tc-scroll-label__text' : ''"
      :style="needsScroll ? { '--tc-scroll-distance': `${distance}px` } : undefined"
    >
      {{ text }}
    </span>
  </div>
</template>
