<script setup>
import { computed, ref, watch } from 'vue';
import AthleteProgramTableDayCard from './AthleteProgramTableDayCard.vue';
import { sessionDayOrdinalInWeek } from '../utils/programBuilder';

const props = defineProps({
  programBlock: {
    type: Object,
    required: true,
  },
});

const activeWeek = ref(1);

const weekNumbers = computed(() =>
  Array.from({ length: Number(props.programBlock?.week_count ?? 0) }, (_, index) => index + 1),
);

watch(
  weekNumbers,
  (weeks) => {
    if (!weeks.includes(activeWeek.value)) {
      activeWeek.value = weeks[0] ?? 1;
    }
  },
  { immediate: true },
);

function dayNumbersForWeek(weekNumber) {
  const days = new Set();

  for (const key of Object.keys(props.programBlock?.sessions ?? {})) {
    const [week, day] = key.split('-').map(Number);
    if (week === weekNumber && Number.isFinite(day) && day > 0) {
      days.add(day);
    }
  }

  return [...days].sort((a, b) => a - b);
}

function sessionFor(weekNumber, weekday) {
  return props.programBlock?.sessions?.[`${weekNumber}-${weekday}`] ?? null;
}

function dayOrdinal(weekNumber, weekday) {
  return sessionDayOrdinalInWeek(props.programBlock?.sessions, weekNumber, weekday);
}

const activeWeekDays = computed(() => dayNumbersForWeek(activeWeek.value));

const daysPerWeek = computed(() =>
  weekNumbers.value.reduce((max, weekNumber) => Math.max(max, dayNumbersForWeek(weekNumber).length), 0),
);
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3 sm:p-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h2 class="text-sm font-semibold text-white">Vue tableur</h2>
      <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-1.5 text-xs text-slate-400">
        <span class="font-medium text-white">{{ daysPerWeek }}</span>
        jour{{ daysPerWeek > 1 ? 's' : '' }} max / semaine
      </div>
    </div>

    <div class="tc-scrollbar mt-3 flex gap-1 overflow-x-auto border-b border-slate-800">
      <button
        v-for="weekNumber in weekNumbers"
        :key="weekNumber"
        type="button"
        class="shrink-0 border-b-2 px-3 py-2 text-sm font-medium transition"
        :class="
          activeWeek === weekNumber
            ? 'border-blue-500 text-blue-300'
            : 'border-transparent text-slate-400 hover:text-white'
        "
        @click="activeWeek = weekNumber"
      >
        S{{ weekNumber }}
      </button>
    </div>

    <div v-if="activeWeekDays.length" class="mt-4 space-y-4">
      <AthleteProgramTableDayCard
        v-for="weekday in activeWeekDays"
        :key="`${activeWeek}-${weekday}`"
        :week-number="activeWeek"
        :weekday="weekday"
        :day-ordinal="dayOrdinal(activeWeek, weekday)"
        :session="sessionFor(activeWeek, weekday)"
        :table-layout="programBlock.table_layout"
      />
    </div>

    <p v-else class="mt-4 text-center text-xs text-slate-500">Aucune séance cette semaine.</p>
  </section>
</template>
