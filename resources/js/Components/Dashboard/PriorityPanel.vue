<script setup>
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import SectionHeader from './SectionHeader.vue';
import AnimatedCounter from './AnimatedCounter.vue';

const { t } = useI18n();

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
});

const accentMap = {
  critical: {
    border: 'border-rose-500/35',
    glow: 'hover:shadow-[0_0_28px_rgba(244,63,94,0.18)]',
    bar: 'bg-rose-500',
    badge: 'border-rose-500/40 bg-rose-500/15 text-rose-200',
    iconBg: 'bg-rose-500/15 text-rose-400',
    progress: 'bg-rose-500',
  },
  high: {
    border: 'border-amber-500/35',
    glow: 'hover:shadow-[0_0_28px_rgba(245,158,11,0.18)]',
    bar: 'bg-amber-500',
    badge: 'border-amber-500/40 bg-amber-500/15 text-amber-200',
    iconBg: 'bg-amber-500/15 text-amber-400',
    progress: 'bg-amber-500',
  },
  medium: {
    border: 'border-indigo-500/30',
    glow: 'hover:shadow-[0_0_28px_rgba(99,102,241,0.16)]',
    bar: 'bg-indigo-400',
    badge: 'border-indigo-500/40 bg-indigo-500/15 text-indigo-200',
    iconBg: 'bg-indigo-500/15 text-indigo-400',
    progress: 'bg-indigo-400',
  },
  low: {
    border: 'border-blue-500/25',
    glow: 'hover:shadow-[0_0_28px_rgba(59,130,246,0.16)]',
    bar: 'bg-blue-500',
    badge: 'border-blue-500/35 bg-blue-500/10 text-blue-200',
    iconBg: 'bg-blue-500/15 text-blue-400',
    progress: 'bg-blue-500',
  },
};

function accent(item) {
  return accentMap[item.priority] ?? accentMap.low;
}
</script>

<template>
  <section>
    <SectionHeader
      :eyebrow="t('app.coachDash.priority')"
      :title="t('app.coachDash.priorityActions')"
    />

    <div class="-mx-1 mt-4 flex gap-3 overflow-x-auto px-1 pb-1 sm:grid sm:grid-cols-2 sm:overflow-visible xl:grid-cols-4">
      <motion.div
        v-for="(item, index) in items"
        :key="item.key"
        :initial="{ opacity: 0, y: 14 }"
        :animate="{ opacity: 1, y: 0 }"
        :transition="{ duration: 0.35, delay: index * 0.06, ease: [0.22, 1, 0.36, 1] }"
        :whileHover="{ y: -3, scale: 1.015 }"
        class="min-w-[16.5rem] flex-1 sm:min-w-0"
      >
        <component
          :is="item.href ? Link : 'button'"
          :href="item.href"
          type="button"
          class="group relative flex h-full w-full flex-col overflow-hidden rounded-[18px] border bg-slate-900/50 p-4 text-left shadow-lg backdrop-blur-sm transition duration-200"
          :class="[accent(item).border, accent(item).glow]"
          @click="item.onClick?.()"
        >
          <div class="absolute inset-x-0 top-0 h-0.5 opacity-80" :class="accent(item).bar" />

          <div class="flex items-start">
            <span
              class="flex h-10 w-10 items-center justify-center rounded-[12px] transition duration-200 group-hover:scale-105"
              :class="accent(item).iconBg"
            >
              <UiIcon :name="item.icon" class="h-5 w-5" />
            </span>
          </div>

          <p class="mt-3 text-sm font-medium text-slate-400">{{ item.label }}</p>
          <p class="mt-1 text-3xl font-bold tracking-tight text-white">
            <AnimatedCounter :value="item.value" />
            <span v-if="item.suffix" class="ml-1 text-base font-semibold text-slate-500">{{ item.suffix }}</span>
          </p>

          <p class="mt-4 text-xs font-semibold text-blue-400 transition group-hover:text-blue-300">
            {{ item.cta }} →
          </p>
        </component>
      </motion.div>
    </div>
  </section>
</template>
