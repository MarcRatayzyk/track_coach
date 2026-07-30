<script setup>
import { computed } from 'vue';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import AnimatedCounter from '../Dashboard/AnimatedCounter.vue';

const props = defineProps({
  metrics: {
    type: Object,
    default: () => ({
      expected: { daily: 0, weekly: 0, total: 0 },
      pending: { daily: 0, weekly: 0, total: 0 },
      done: { daily: 0, weekly: 0, total: 0 },
      overdue: { daily: 0, weekly: 0, total: 0 },
    }),
  },
});

const items = computed(() => {
  const m = props.metrics ?? {};
  const expected = m.expected ?? {};
  const pending = m.pending ?? {};
  const done = m.done ?? {};
  const overdue = m.overdue ?? {};

  return [
    {
      key: 'expected',
      label: 'Attendu au total',
      hint: 'Cette semaine',
      value: expected.total ?? 0,
      daily: expected.daily ?? 0,
      weekly: expected.weekly ?? 0,
      icon: 'list',
      color: '#60a5fa',
      iconClass: 'bg-blue-500/15 text-blue-400',
      accentClass: 'border-blue-500/25',
    },
    {
      key: 'pending',
      label: 'En attente',
      hint: '',
      value: pending.total ?? 0,
      daily: pending.daily ?? 0,
      weekly: pending.weekly ?? 0,
      icon: 'alert',
      color: '#fbbf24',
      iconClass: 'bg-amber-500/15 text-amber-400',
      accentClass: 'border-amber-500/25',
    },
    {
      key: 'done',
      label: 'Traités',
      hint: '',
      value: done.total ?? 0,
      daily: done.daily ?? 0,
      weekly: done.weekly ?? 0,
      icon: 'bolt',
      color: '#34d399',
      iconClass: 'bg-emerald-500/15 text-emerald-400',
      accentClass: 'border-emerald-500/25',
    },
    {
      key: 'overdue',
      label: 'En retard',
      hint: '',
      value: overdue.total ?? 0,
      daily: overdue.daily ?? 0,
      weekly: overdue.weekly ?? 0,
      icon: 'calendar',
      color: '#fb7185',
      iconClass: 'bg-rose-500/15 text-rose-400',
      accentClass: 'border-rose-500/25',
    },
  ];
});
</script>

<template>
  <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    <motion.div
      v-for="(item, index) in items"
      :key="item.key"
      :initial="{ opacity: 0, y: 10 }"
      :animate="{ opacity: 1, y: 0 }"
      :transition="{ duration: 0.28, delay: index * 0.04 }"
      class="relative overflow-hidden rounded-[18px] border bg-gradient-to-br from-slate-900/80 via-slate-900/50 to-slate-950/60 p-4 shadow-lg backdrop-blur-sm transition duration-200 hover:shadow-[0_0_24px_rgba(59,130,246,0.12)]"
      :class="item.accentClass"
    >
      <div
        class="pointer-events-none absolute -right-5 -top-5 h-20 w-20 rounded-full opacity-30 blur-2xl"
        :style="{ background: item.color }"
        aria-hidden="true"
      />
      <span
        class="relative flex h-9 w-9 items-center justify-center rounded-[12px]"
        :class="item.iconClass"
      >
        <UiIcon :name="item.icon" class="h-4 w-4" />
      </span>
      <p class="relative mt-2.5 text-[10px] font-semibold uppercase tracking-wide text-slate-300">
        {{ item.label }}
      </p>
      <p v-if="item.hint" class="relative text-[10px] text-slate-400">{{ item.hint }}</p>
      <p class="relative mt-0.5 text-2xl font-bold tabular-nums tracking-tight text-white">
        <AnimatedCounter :value="item.value" />
      </p>
      <div class="relative mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-300">
        <span class="tabular-nums">
          <span class="text-slate-300">Journalier</span>
          <span class="ml-1 font-semibold text-white">{{ item.daily }}</span>
        </span>
        <span class="text-slate-500">·</span>
        <span class="tabular-nums">
          <span class="text-slate-300">Hebdo</span>
          <span class="ml-1 font-semibold text-white">{{ item.weekly }}</span>
        </span>
      </div>
    </motion.div>
  </div>
</template>
