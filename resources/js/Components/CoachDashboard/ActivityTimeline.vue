<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import SectionHeader from './SectionHeader.vue';
import {
  athleteInitials,
  cardHover,
  cardShell,
  relativeTimeFr,
  timeOfDayFr,
} from './dashboardUi';

const props = defineProps({
  items: { type: Array, default: () => [] },
});

const colorMap = {
  amber: 'bg-amber-500/15 text-amber-300 border-amber-500/30',
  emerald: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
  rose: 'bg-rose-500/15 text-rose-300 border-rose-500/30',
  violet: 'bg-violet-500/15 text-violet-300 border-violet-500/30',
  blue: 'bg-blue-500/15 text-blue-300 border-blue-500/30',
  sky: 'bg-sky-500/15 text-sky-300 border-sky-500/30',
};

const feed = computed(() => props.items ?? []);
</script>

<template>
  <section :class="[cardShell, 'p-5']">
    <SectionHeader
      eyebrow="Flux"
      title="Activité récente"
    />

    <p
      v-if="!feed.length"
      class="mt-6 rounded-xl border border-dashed border-slate-700 bg-slate-950/40 px-4 py-10 text-center text-sm text-slate-500"
    >
      Aucune activité récente pour le moment.
    </p>

    <ol v-else class="relative mt-6 space-y-0">
      <li
        v-for="(event, index) in feed"
        :key="event.id"
        class="relative flex gap-4 pb-5 last:pb-0"
      >
        <div class="flex flex-col items-center">
          <span
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border"
            :class="colorMap[event.color] ?? colorMap.blue"
          >
            <UiIcon :name="event.icon || 'list'" class="h-4 w-4" />
          </span>
          <span
            v-if="index < feed.length - 1"
            class="mt-1 w-px flex-1 bg-gradient-to-b from-slate-700 to-transparent"
          />
        </div>

        <motion.div
          :initial="{ opacity: 0, x: 8 }"
          :whileInView="{ opacity: 1, x: 0 }"
          :viewport="{ once: true, amount: 0.4 }"
          :transition="{ delay: index * 0.04, duration: 0.35 }"
          class="min-w-0 flex-1"
        >
          <Link
            :href="event.href || '#'"
            :class="[
              'block rounded-xl border border-transparent px-3 py-2 transition duration-200',
              cardHover,
              'hover:border-slate-700/80 hover:bg-slate-950/50',
            ]"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="text-sm font-semibold text-white">{{ event.title }}</p>
              <span class="text-[11px] text-slate-500">
                {{ timeOfDayFr(event.occurred_at) || relativeTimeFr(event.occurred_at) }}
              </span>
            </div>
            <div class="mt-1.5 flex items-center gap-2">
              <span
                class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-[10px] font-semibold text-slate-200"
              >
                {{ athleteInitials(event.athlete_name || event.body) }}
              </span>
              <p class="truncate text-xs text-slate-400">{{ event.body }}</p>
            </div>
            <p class="mt-1 text-[11px] text-slate-600">
              {{ relativeTimeFr(event.occurred_at) }}
            </p>
          </Link>
        </motion.div>
      </li>
    </ol>
  </section>
</template>
