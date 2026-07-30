<script setup>
import { computed } from 'vue';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import AnimatedCounter from '../Dashboard/AnimatedCounter.vue';

const props = defineProps({
  feedbacks: { type: Array, default: () => [] },
});

const stats = computed(() => {
  let pending = 0;
  let done = 0;
  for (const item of props.feedbacks) {
    if (item.status === 'coach_replied') done += 1;
    else pending += 1;
  }
  return {
    total: props.feedbacks.length,
    pending,
    done,
  };
});

const items = computed(() => [
  {
    key: 'total',
    label: 'Mes retours',
    value: stats.value.total,
    icon: 'list',
    color: '#60a5fa',
    iconClass: 'bg-blue-500/15 text-blue-400',
    accentClass: 'border-blue-500/25',
  },
  {
    key: 'pending',
    label: 'En attente',
    value: stats.value.pending,
    icon: 'alert',
    color: '#fbbf24',
    iconClass: 'bg-amber-500/15 text-amber-400',
    accentClass: 'border-amber-500/25',
  },
  {
    key: 'done',
    label: 'Répondus',
    value: stats.value.done,
    icon: 'bolt',
    color: '#34d399',
    iconClass: 'bg-emerald-500/15 text-emerald-400',
    accentClass: 'border-emerald-500/25',
  },
]);
</script>

<template>
  <div class="grid grid-cols-3 gap-3">
    <motion.div
      v-for="(item, index) in items"
      :key="item.key"
      :initial="{ opacity: 0, y: 10 }"
      :animate="{ opacity: 1, y: 0 }"
      :transition="{ duration: 0.28, delay: index * 0.04 }"
      class="relative overflow-hidden rounded-[18px] border bg-gradient-to-br from-slate-900/80 via-slate-900/55 to-slate-950/70 p-4 shadow-lg backdrop-blur-sm transition duration-200 hover:shadow-[0_0_24px_rgba(59,130,246,0.12)]"
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
      <p class="relative mt-0.5 text-2xl font-bold tabular-nums tracking-tight text-white">
        <AnimatedCounter :value="item.value" />
      </p>
    </motion.div>
  </div>
</template>
