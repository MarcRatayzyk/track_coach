<script setup>
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import AnimatedCounter from './AnimatedCounter.vue';
import MiniSparkline from './MiniSparkline.vue';

defineProps({
  label: { type: String, required: true },
  value: { type: Number, default: 0 },
  suffix: { type: String, default: '' },
  delta: { type: String, default: '' },
  deltaPositive: { type: Boolean, default: true },
  icon: { type: String, default: 'bolt' },
  sparkline: { type: Array, default: () => [] },
  color: { type: String, default: '#60a5fa' },
  accentClass: { type: String, default: 'border-blue-500/25' },
  iconClass: { type: String, default: 'bg-blue-500/15 text-blue-400' },
  clickable: { type: Boolean, default: false },
});

defineEmits(['click']);
</script>

<template>
  <motion.button
    type="button"
    :initial="{ opacity: 0, y: 12 }"
    :whileInView="{ opacity: 1, y: 0 }"
    :viewport="{ once: true, amount: 0.3 }"
    :transition="{ duration: 0.35, ease: [0.22, 1, 0.36, 1] }"
    :whileHover="{ y: -3, scale: 1.02 }"
    class="group relative flex min-h-[9.5rem] w-full flex-col overflow-hidden rounded-[18px] border bg-gradient-to-br from-slate-900/80 via-slate-900/50 to-slate-950/60 p-4 text-left shadow-lg backdrop-blur-sm transition duration-200 hover:shadow-[0_0_28px_rgba(59,130,246,0.14)]"
    :class="[accentClass, clickable ? 'cursor-pointer' : 'cursor-default']"
    @click="$emit('click')"
  >
    <div
      class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full opacity-40 blur-2xl transition duration-200 group-hover:opacity-70"
      :style="{ background: color }"
      aria-hidden="true"
    />

    <div class="relative flex items-start justify-between gap-2">
      <span
        class="flex h-10 w-10 items-center justify-center rounded-[12px] transition duration-200 group-hover:scale-105"
        :class="iconClass"
      >
        <UiIcon :name="icon" class="h-5 w-5" />
      </span>
      <MiniSparkline :points="sparkline" :color="color" />
    </div>

    <p class="relative mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
      {{ label }}
    </p>
    <p class="relative mt-1 flex items-baseline gap-1 text-3xl font-bold tracking-tight text-white">
      <AnimatedCounter :value="value" />
      <span v-if="suffix" class="text-base font-semibold text-slate-500">{{ suffix }}</span>
    </p>
    <p
      v-if="delta"
      class="relative mt-1.5 text-xs font-medium"
      :class="deltaPositive ? 'text-emerald-400' : 'text-amber-400'"
    >
      {{ delta }}
    </p>
  </motion.button>
</template>
