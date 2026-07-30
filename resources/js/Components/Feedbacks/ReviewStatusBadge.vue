<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: 'submitted' },
  urgency: { type: String, default: 'normal' },
  mode: { type: String, default: 'coach' }, // coach | athlete
});

const badge = computed(() => {
  if (props.status === 'coach_replied') {
    return {
      label: 'Répondu',
      class: 'border-emerald-500/35 bg-emerald-500/15 text-emerald-300',
    };
  }
  if (props.mode === 'athlete') {
    return {
      label: 'En attente',
      class: 'border-amber-500/40 bg-amber-500/15 text-amber-200',
    };
  }
  if (props.urgency === 'overdue') {
    return {
      label: 'En retard',
      class: 'border-rose-500/40 bg-rose-500/15 text-rose-200',
    };
  }
  if (props.urgency === 'today') {
    return {
      label: 'Aujourd’hui',
      class: 'border-amber-500/40 bg-amber-500/15 text-amber-200',
    };
  }
  return {
    label: 'En attente',
    class: 'border-slate-600 bg-slate-800/50 text-slate-400',
  };
});
</script>

<template>
  <span
    class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide transition duration-200"
    :class="badge.class"
  >
    {{ badge.label }}
  </span>
</template>
