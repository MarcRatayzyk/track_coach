<script setup>
import { computed } from 'vue';

const props = defineProps({
  frequency: { type: String, default: null },
  compact: { type: Boolean, default: false },
});

const meta = computed(() => {
  if (props.frequency === 'daily') {
    return {
      label: props.compact ? 'Jour' : 'Journalier',
      class: 'border-sky-500/35 bg-sky-500/10 text-sky-300',
    };
  }
  if (props.frequency === 'weekly') {
    return {
      label: props.compact ? 'Hebdo' : 'Hebdomadaire',
      class: 'border-violet-500/35 bg-violet-500/10 text-violet-300',
    };
  }
  return null;
});
</script>

<template>
  <span
    v-if="meta"
    class="inline-flex shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
    :class="meta.class"
  >
    {{ meta.label }}
  </span>
</template>
