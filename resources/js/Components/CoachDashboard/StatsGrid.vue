<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import AnimatedCounter from './AnimatedCounter.vue';
import SectionHeader from './SectionHeader.vue';
import { cardHover, cardShell } from './dashboardUi';

const props = defineProps({
  activePrograms: { type: Number, default: 0 },
  athleteCount: { type: Number, default: 0 },
  competitions: { type: Number, default: 0 },
  upcomingCompetitions: { type: Array, default: () => [] },
  onOpenCompetitions: { type: Function, default: null },
});

const period = ref('daily');

function competitionDate(comp) {
  return String(comp?.competition_date ?? comp?.date ?? '').slice(0, 10);
}

function startOfWeek(d) {
  const date = new Date(d);
  const day = (date.getDay() + 6) % 7;
  date.setDate(date.getDate() - day);
  date.setHours(0, 0, 0, 0);
  return date;
}

const filteredCompetitionCount = computed(() => {
  const list = props.upcomingCompetitions ?? [];
  if (!list.length) {
    return props.competitions ?? 0;
  }

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const todayKey = today.toISOString().slice(0, 10);

  if (period.value === 'daily') {
    return list.filter((c) => competitionDate(c) === todayKey).length;
  }

  const weekStart = startOfWeek(today);
  const weekEnd = new Date(weekStart);
  weekEnd.setDate(weekStart.getDate() + 6);

  return list.filter((c) => {
    const key = competitionDate(c);
    if (!key) return false;
    const d = new Date(`${key}T12:00:00`);
    return d >= weekStart && d <= weekEnd;
  }).length;
});

const cards = computed(() => [
  {
    key: 'programs',
    label: 'Programmes actifs',
    value: props.activePrograms,
    delta: period.value === 'daily' ? 'Aujourd’hui' : 'Cette semaine',
    icon: 'bolt',
    color: '#818cf8',
    onClick: () => router.visit('/program-builder'),
  },
  {
    key: 'athletes',
    label: 'Athlètes suivis',
    value: props.athleteCount,
    delta: period.value === 'daily' ? 'Roster du jour' : 'Roster de la semaine',
    icon: 'users',
    color: '#38bdf8',
    onClick: () => router.visit('/athletes'),
  },
  {
    key: 'comps',
    label: 'Compétitions',
    value: filteredCompetitionCount.value,
    delta: period.value === 'daily' ? 'Aujourd’hui' : 'Cette semaine',
    icon: 'calendar',
    color: '#fb7185',
    onClick: () => props.onOpenCompetitions?.(),
  },
]);
</script>

<template>
  <section>
    <SectionHeader
      eyebrow="Vue d’ensemble"
      title="Indicateurs clés"
    >
      <template #actions>
        <div class="flex shrink-0 rounded-full border border-slate-700 bg-slate-950/50 p-1">
          <button
            type="button"
            class="rounded-full px-2.5 py-1.5 text-[11px] font-semibold transition duration-200 sm:px-3 sm:text-xs"
            :class="
              period === 'daily'
                ? 'bg-blue-600 text-white shadow-md shadow-blue-900/40'
                : 'text-slate-400 hover:text-white'
            "
            @click="period = 'daily'"
          >
            Jour
          </button>
          <button
            type="button"
            class="rounded-full px-2.5 py-1.5 text-[11px] font-semibold transition duration-200 sm:px-3 sm:text-xs"
            :class="
              period === 'weekly'
                ? 'bg-blue-600 text-white shadow-md shadow-blue-900/40'
                : 'text-slate-400 hover:text-white'
            "
            @click="period = 'weekly'"
          >
            Hebdo
          </button>
        </div>
      </template>
    </SectionHeader>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
      <motion.button
        v-for="(card, index) in cards"
        :key="card.key"
        type="button"
        :initial="{ opacity: 0, y: 14 }"
        :animate="{ opacity: 1, y: 0 }"
        :transition="{ delay: 0.06 * index, duration: 0.4 }"
        :class="[cardShell, cardHover, 'w-full text-left']"
        @click="card.onClick?.()"
      >
        <div class="flex items-start justify-between gap-2">
          <span
            class="flex h-9 w-9 items-center justify-center rounded-xl"
            :style="{ backgroundColor: `${card.color}22`, color: card.color }"
          >
            <UiIcon :name="card.icon" class="h-4 w-4" />
          </span>
          <span class="text-[11px] font-medium text-slate-500">{{ card.delta }}</span>
        </div>
        <p class="mt-4 text-xs font-medium text-slate-400">{{ card.label }}</p>
        <p class="mt-1 text-3xl font-bold tracking-tight text-white">
          <AnimatedCounter :value="card.value" />
        </p>
      </motion.button>
    </div>
  </section>
</template>
