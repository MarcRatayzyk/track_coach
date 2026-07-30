<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import SectionHeader from './SectionHeader.vue';
import { messagingInitials, messagingRelativeTime } from '../../utils/messagingFormat';

const props = defineProps({
  events: {
    type: Array,
    default: () => [],
  },
});

const toneMap = {
  pr: { icon: 'trophy', color: 'text-amber-400', bg: 'bg-amber-500/15', ring: 'ring-amber-500/30' },
  feedback: { icon: 'video', color: 'text-blue-400', bg: 'bg-blue-500/15', ring: 'ring-blue-500/30' },
  program: { icon: 'clipboard', color: 'text-indigo-400', bg: 'bg-indigo-500/15', ring: 'ring-indigo-500/30' },
  competition: { icon: 'calendar', color: 'text-rose-400', bg: 'bg-rose-500/15', ring: 'ring-rose-500/30' },
  message: { icon: 'chat', color: 'text-sky-400', bg: 'bg-sky-500/15', ring: 'ring-sky-500/30' },
  login: { icon: 'users', color: 'text-emerald-400', bg: 'bg-emerald-500/15', ring: 'ring-emerald-500/30' },
  alert: { icon: 'alert', color: 'text-orange-400', bg: 'bg-orange-500/15', ring: 'ring-orange-500/30' },
};

function tone(type) {
  return toneMap[type] ?? toneMap.alert;
}

const items = computed(() => props.events ?? []);
</script>

<template>
  <section class="rounded-[20px] border border-slate-800/80 bg-slate-900/50 p-4 shadow-lg backdrop-blur-sm sm:p-5">
    <SectionHeader
      eyebrow="Flux"
      title="Activité récente"
    />

    <p
      v-if="!items.length"
      class="mt-6 rounded-[16px] border border-dashed border-slate-700 px-4 py-8 text-center text-sm text-slate-500"
    >
      L’activité apparaîtra ici au fil de la journée.
    </p>

    <ol v-else class="relative mt-5 space-y-0">
      <div
        class="absolute bottom-3 left-[1.35rem] top-3 w-px bg-gradient-to-b from-blue-500/40 via-slate-700 to-transparent"
        aria-hidden="true"
      />
      <motion.li
        v-for="(event, index) in items"
        :key="event.id"
        :initial="{ opacity: 0, x: -8 }"
        :whileInView="{ opacity: 1, x: 0 }"
        :viewport="{ once: true }"
        :transition="{ duration: 0.3, delay: index * 0.04 }"
        class="relative flex gap-3 py-2.5 pl-1"
      >
        <div class="relative z-10 flex shrink-0 flex-col items-center">
          <span
            class="flex h-10 w-10 items-center justify-center rounded-full ring-1"
            :class="[tone(event.type).bg, tone(event.type).ring]"
          >
            <span
              v-if="event.athleteName"
              class="flex h-full w-full items-center justify-center rounded-full bg-slate-900 text-[11px] font-semibold text-white"
            >
              {{ messagingInitials(event.athleteName) }}
            </span>
            <UiIcon
              v-else
              :name="tone(event.type).icon"
              class="h-4 w-4"
              :class="tone(event.type).color"
            />
          </span>
        </div>
        <component
          :is="event.href ? Link : 'div'"
          :href="event.href"
          class="min-w-0 flex-1 rounded-[14px] border border-transparent px-3 py-2 transition duration-200 hover:border-slate-700/80 hover:bg-slate-950/50"
        >
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-white">{{ event.title }}</p>
              <p class="mt-0.5 truncate text-xs text-slate-500">
                <span v-if="event.athleteName" class="text-slate-400">{{ event.athleteName }}</span>
                <span v-if="event.athleteName && event.body"> · </span>
                <span v-if="event.body">{{ event.body }}</span>
              </p>
            </div>
            <span class="shrink-0 text-[11px] font-medium text-slate-500">
              {{ messagingRelativeTime(event.at) || event.timeLabel }}
            </span>
          </div>
          <span
            class="mt-1.5 inline-flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide"
            :class="tone(event.type).color"
          >
            <UiIcon :name="tone(event.type).icon" class="h-3 w-3" />
            {{ event.typeLabel }}
          </span>
        </component>
      </motion.li>
    </ol>
  </section>
</template>
