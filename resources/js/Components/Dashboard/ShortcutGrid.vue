<script setup>
import { Link } from '@inertiajs/vue3';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import SectionHeader from './SectionHeader.vue';

defineProps({
  items: {
    type: Array,
    default: () => [],
  },
});

defineEmits(['action']);
</script>

<template>
  <section>
    <SectionHeader
      eyebrow="Raccourcis"
      title="Actions rapides"
    />

    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
      <motion.div
        v-for="(item, index) in items"
        :key="item.key"
        :initial="{ opacity: 0, y: 10 }"
        :whileInView="{ opacity: 1, y: 0 }"
        :viewport="{ once: true }"
        :transition="{ delay: index * 0.04, duration: 0.28 }"
        :whileHover="{ y: -3, scale: 1.03 }"
      >
        <component
          :is="item.href ? Link : 'button'"
          :href="item.href"
          type="button"
          class="flex h-full w-full flex-col items-start gap-3 rounded-[18px] border border-slate-800/80 bg-slate-900/50 p-4 text-left shadow-lg backdrop-blur-sm transition duration-200 hover:border-blue-500/35 hover:shadow-[0_0_24px_rgba(59,130,246,0.16)]"
          @click="!item.href && $emit('action', item.key)"
        >
          <span
            class="flex h-10 w-10 items-center justify-center rounded-[12px]"
            :class="item.iconClass || 'bg-blue-500/15 text-blue-400'"
          >
            <UiIcon :name="item.icon" class="h-5 w-5" />
          </span>
          <span class="text-sm font-semibold text-white">{{ item.label }}</span>
        </component>
      </motion.div>
    </div>
  </section>
</template>
