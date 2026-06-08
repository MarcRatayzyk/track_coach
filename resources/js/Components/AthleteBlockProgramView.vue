<script setup>
import { ref, watch } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import {
  BLOCK_TYPES,
  SECTION_LABELS,
  cellDate,
  formatLineRecap,
  formatPrescription,
  sessionCardTitle,
  weekDaysWithSessions,
} from '../utils/programBuilder';

const props = defineProps({
  activeProgram: {
    type: Object,
    default: null,
  },
});

const expandedWeeks = ref(new Set());

watch(
  () => props.activeProgram?.template?.weeks,
  () => {
    expandedWeeks.value = new Set();
  },
  { immediate: true },
);

function blockTypeLabel(type) {
  return BLOCK_TYPES.find((item) => item.value === type)?.label ?? type;
}

function sortedExercisesForDay(day) {
  return [...(day.exercises ?? [])].sort(
    (a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0),
  );
}

function sessionDateLabel(day, weekNumber, dateStart) {
  if (!dateStart) {
    return null;
  }
  return formatCalendarFr(cellDate(dateStart, weekNumber, day.day_number), 'medium');
}

function exerciseLineText(line) {
  return formatLineRecap(line) || formatPrescription(line);
}

function sectionTextClass(section) {
  if (section === 'topset') {
    return 'text-emerald-400';
  }
  if (section === 'backoff') {
    return 'text-slate-400';
  }
  return 'text-slate-500';
}

function isWeekExpanded(weekId) {
  return expandedWeeks.value.has(weekId);
}

function toggleWeek(weekId) {
  const next = new Set(expandedWeeks.value);
  if (next.has(weekId)) {
    next.delete(weekId);
  } else {
    next.add(weekId);
  }
  expandedWeeks.value = next;
}
</script>

<template>
  <section
    v-if="activeProgram && activeProgram.template"
    class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4"
  >
    <h3 class="text-sm font-semibold text-white">Programme</h3>
    <p class="mt-1 text-xs text-slate-500">
      {{ activeProgram.template.name }} · du
      {{ formatCalendarFr(activeProgram.date_start, 'medium') }}
      <template v-if="activeProgram.date_end">
        au {{ formatCalendarFr(activeProgram.date_end, 'medium') }}
      </template>
    </p>

    <div
      v-for="week in activeProgram.template.weeks ?? []"
      :key="week.id"
      class="mt-3 rounded-xl border border-slate-800 bg-slate-950/60"
    >
      <button
        type="button"
        class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left transition hover:bg-slate-900/60"
        :aria-expanded="isWeekExpanded(week.id)"
        @click="toggleWeek(week.id)"
      >
        <span
          class="inline-block w-3 shrink-0 text-center text-sm text-slate-400 transition-transform duration-200"
          :class="isWeekExpanded(week.id) ? 'rotate-90' : ''"
          aria-hidden="true"
        >
          &gt;
        </span>
        <span class="min-w-0 flex-1 text-sm font-semibold text-white">
          S{{ week.week_number }}
          <span class="text-slate-400">— {{ blockTypeLabel(week.block_type) }}</span>
        </span>
        <span class="shrink-0 text-xs text-slate-500">
          {{ weekDaysWithSessions(week).length }} séance{{ weekDaysWithSessions(week).length > 1 ? 's' : '' }}
        </span>
      </button>

      <div v-show="isWeekExpanded(week.id)" class="border-t border-slate-800/80 px-3 pb-3 pt-1">
        <div
          v-if="weekDaysWithSessions(week).length"
          class="mt-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-3"
        >
          <article
            v-for="day in weekDaysWithSessions(week)"
            :key="day.id"
            class="rounded-lg border border-slate-800 bg-slate-950/50 p-3"
          >
            <h4 class="text-xs font-semibold text-white">
              {{ sessionCardTitle(day, weekDaysWithSessions(week)) }}
            </h4>
            <p
              v-if="sessionDateLabel(day, week.week_number, activeProgram.date_start)"
              class="mt-0.5 text-[11px] text-slate-500"
            >
              {{ sessionDateLabel(day, week.week_number, activeProgram.date_start) }}
            </p>
            <ul class="mt-2 space-y-1 border-t border-slate-800/80 pt-2">
              <li
                v-for="line in sortedExercisesForDay(day)"
                :key="line.id"
                class="text-xs leading-snug text-slate-300"
              >
                <span class="font-medium" :class="sectionTextClass(line.section)">
                  {{ SECTION_LABELS[line.section] ?? line.section }}
                </span>
                — {{ exerciseLineText(line) }}
              </li>
            </ul>
            <p v-if="!sortedExercisesForDay(day).length" class="mt-2 text-xs text-slate-500">
              Aucun exercice.
            </p>
          </article>
        </div>
        <p v-else class="mt-2 text-xs text-slate-500">Aucune séance cette semaine.</p>
      </div>
    </div>
  </section>

  <section
    v-else
    class="rounded-2xl border border-dashed border-slate-700 bg-slate-900/30 p-4 text-center"
  >
    <p class="text-sm text-slate-500">Aucun programme actif assigné.</p>
  </section>
</template>
