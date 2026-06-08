<script setup>
import { computed } from 'vue';
import AthleteProgramTableDayCard from './AthleteProgramTableDayCard.vue';

const props = defineProps({
  programBlock: {
    type: Object,
    required: true,
  },
});

const weekNumbers = computed(() =>
  Array.from({ length: Number(props.programBlock?.week_count ?? 0) }, (_, index) => index + 1),
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

const daysPerWeek = computed(() =>
  weekNumbers.value.reduce((max, weekNumber) => Math.max(max, dayNumbersForWeek(weekNumber).length), 0),
);
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-sm font-semibold text-white">Vue tableur</h2>
        <p class="mt-1 text-xs text-slate-500">Lecture seule — une carte par jour programmé.</p>
      </div>
      <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2 text-xs text-slate-400">
        <span class="font-medium text-white">{{ daysPerWeek }}</span>
        jour{{ daysPerWeek > 1 ? 's' : '' }} max / semaine
      </div>
    </div>

    <div class="mt-4 space-y-6">
      <section
        v-for="weekNumber in weekNumbers"
        :key="weekNumber"
        class="rounded-xl border border-slate-800 bg-slate-950/30 p-3"
      >
        <h3 class="text-sm font-semibold text-white">Semaine {{ weekNumber }}</h3>

        <div
          v-if="dayNumbersForWeek(weekNumber).length"
          class="tc-scrollbar mt-3 overflow-x-auto pb-2"
        >
          <div class="flex min-w-max flex-nowrap items-stretch gap-3">
            <AthleteProgramTableDayCard
              v-for="weekday in dayNumbersForWeek(weekNumber)"
              :key="`${weekNumber}-${weekday}`"
              :week-number="weekNumber"
              :weekday="weekday"
              :session="sessionFor(weekNumber, weekday)"
              :table-layout="programBlock.table_layout"
            />
          </div>
        </div>

        <p v-else class="mt-3 text-xs text-slate-500">Aucune séance cette semaine.</p>
      </section>
    </div>
  </section>
</template>
