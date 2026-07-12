<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import {
  buildTrainingYearGridFromProgramBlock,
  indexBlockBoundariesByDate,
  indexCompetitionsByDate,
  indexProgramSessionsByDate,
  indexTrainingSessionsByDate,
} from '../utils/monthCalendar';
import { formatLineRecap, SECTION_LABELS } from '../utils/programBuilder';

const props = defineProps({
  programBlock: {
    type: Object,
    default: null,
  },
  trainingSessions: {
    type: Array,
    default: () => [],
  },
  competitions: {
    type: Array,
    default: () => [],
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  mode: {
    type: String,
    default: 'full',
    validator: (value) => ['full', 'overview'].includes(value),
  },
});

const emit = defineEmits(['edit-session']);

const today = new Date().toISOString().slice(0, 10);
const selectedDate = ref(null);
const scrollContainerRef = ref(null);

const currentMonthKey = `${new Date().getFullYear()}-${new Date().getMonth()}`;

const grid = computed(() => buildTrainingYearGridFromProgramBlock());

const isOverview = computed(() => props.mode === 'overview');

const programByDate = computed(() =>
  isOverview.value ? {} : indexProgramSessionsByDate(props.programBlock),
);

const trainingByDate = computed(() =>
  isOverview.value ? {} : indexTrainingSessionsByDate(props.trainingSessions),
);

const blockByDate = computed(() => indexBlockBoundariesByDate(props.programBlock));

const competitionByDate = computed(() => indexCompetitionsByDate(props.competitions));

const selectedDetail = computed(() => {
  if (!selectedDate.value) {
    return null;
  }

  const competition = competitionByDate.value[selectedDate.value] ?? null;
  const program = programByDate.value[selectedDate.value] ?? null;
  const training = trainingByDate.value[selectedDate.value] ?? [];

  const blockBoundary = blockByDate.value[selectedDate.value] ?? null;

  return {
    date: selectedDate.value,
    label: formatCalendarFr(selectedDate.value),
    competition,
    program,
    training,
    blockBoundary,
    isToday: selectedDate.value === today,
    isEmpty: !competition && !program && !training.length && !blockBoundary,
  };
});

function programLabel(session) {
  const label = String(session?.session_label ?? '').trim();
  if (label) {
    return label;
  }
  const count = (session?.items ?? session?.exercises ?? []).length;
  return count > 0 ? `${count} exercice${count > 1 ? 's' : ''}` : 'Séance programmée';
}

function programItems(session) {
  return session?.items ?? session?.exercises ?? [];
}

function formatLift(value) {
  const numeric = Number(value ?? 0);
  return numeric > 0 ? `${numeric} kg` : null;
}

function trainingSummary(session) {
  const lifts = ['squat', 'bench', 'deadlift']
    .map((lift) => {
      const value = formatLift(session[lift]);
      if (!value) {
        return null;
      }
      const label = lift === 'deadlift' ? 'Terre' : lift.charAt(0).toUpperCase() + lift.slice(1);
      return `${label} : ${value}`;
    })
    .filter(Boolean);

  return lifts.length ? lifts.join(' · ') : 'Séance enregistrée';
}

function hasProgram(date) {
  return Boolean(date && programByDate.value[date]);
}

function hasTraining(date) {
  return Boolean(date && trainingByDate.value[date]?.length);
}

function isToday(date) {
  return date === today;
}

function isCompetition(date) {
  return Boolean(date && competitionByDate.value[date]);
}

function isSelected(date) {
  return selectedDate.value === date;
}

function monthBandClass(monthIndex) {
  return monthIndex % 2 === 0 ? 'bg-slate-950/50' : 'bg-slate-800/40';
}

function formatDay(dayNumber) {
  return String(dayNumber).padStart(2, '0');
}

function hasBlockBoundary(date) {
  return Boolean(date && blockByDate.value[date]);
}

function isBlockStart(date) {
  return blockByDate.value[date]?.type === 'block_start';
}

function isBlockEnd(date) {
  return blockByDate.value[date]?.type === 'block_end';
}

function cellClass(date) {
  const classes = ['w-full'];

  if (isSelected(date)) {
    classes.push('ring-2 ring-blue-400/70 ring-inset');
  }

  if (isToday(date)) {
    classes.push('bg-amber-500/90 font-semibold text-slate-950');
    return classes.join(' ');
  }

  if (isCompetition(date)) {
    classes.push(
      'bg-rose-500/20 font-semibold text-rose-100 ring-1 ring-inset ring-rose-400/45 hover:bg-rose-500/30',
    );
    return classes.join(' ');
  }

  if (isBlockStart(date) || isBlockEnd(date)) {
    classes.push('bg-sky-500/15 font-semibold text-sky-100 ring-1 ring-inset ring-sky-400/40 hover:bg-sky-500/25');
    return classes.join(' ');
  }

  if (!isOverview.value && hasTraining(date)) {
    classes.push('bg-violet-500/10 text-violet-200 hover:bg-violet-500/20');
    return classes.join(' ');
  }

  if (!isOverview.value && hasProgram(date)) {
    classes.push('bg-emerald-500/10 text-emerald-100 hover:bg-emerald-500/20');
    return classes.join(' ');
  }

  classes.push('text-slate-300 hover:bg-slate-700/30');
  return classes.join(' ');
}

function onCellClick(date) {
  selectedDate.value = date;
}

function editTrainingSession(session) {
  emit('edit-session', session);
}

function scrollToCurrentMonth() {
  nextTick(() => {
    const container = scrollContainerRef.value;
    if (!container) {
      return;
    }

    const stickyWidth = 0;
    const monthHeader = container.querySelector(`[data-month-key="${currentMonthKey}"]`);
    const todayCell = container.querySelector(`[data-date="${today}"]`);
    const target = monthHeader ?? todayCell;

    if (!target) {
      return;
    }

    const targetLeft = target.offsetLeft;
    const centered =
      targetLeft - stickyWidth - Math.max(0, (container.clientWidth - stickyWidth) / 2 - target.offsetWidth / 2);

    container.scrollLeft = Math.max(0, centered);
  });
}

onMounted(() => {
  scrollToCurrentMonth();
});
</script>

<template>
  <div class="w-full">
    <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-400">
        <template v-if="!isOverview">
          <span class="inline-flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-full bg-emerald-400" />
            Programme
          </span>
          <span class="inline-flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-full bg-violet-400" />
            Séance réalisée
          </span>
        </template>
        <span v-if="isOverview" class="inline-flex items-center gap-1.5">
          <span class="h-2 w-2 rounded-full bg-sky-400" />
          Bloc
        </span>
        <span class="inline-flex items-center gap-1.5">
          <span class="h-2.5 w-2.5 rounded bg-amber-500/80" />
          Aujourd'hui
        </span>
        <span class="inline-flex items-center gap-1.5">
          <span class="h-2.5 w-2.5 rounded bg-rose-500/25 ring-1 ring-inset ring-rose-400/45" />
          Compétition
        </span>
      </div>
      <span class="text-[11px] text-slate-500">{{ grid.rangeLabel }}</span>
    </div>

    <div class="flex w-full overflow-hidden rounded-lg border border-slate-800/80">
      <div class="w-9 min-w-[2.25rem] shrink-0 border-r border-slate-800 bg-slate-900">
        <div
          class="flex h-[1.625rem] items-end border-b border-slate-700/80 px-1 pb-2 sm:h-[1.75rem]"
          aria-hidden="true"
        />
        <div
          v-for="row in grid.rows"
          :key="`label-${row.weekday}`"
          class="flex h-10 items-center justify-end border-b border-slate-800/60 px-1 pr-2 text-[10px] font-medium leading-none text-slate-500 last:border-b-0 lg:h-8"
        >
          {{ row.label }}
        </div>
      </div>

      <div ref="scrollContainerRef" class="tc-scrollbar min-w-0 flex-1 overflow-x-auto">
        <table class="w-max border-collapse text-[11px]">
          <thead>
            <tr>
              <th
                v-for="(header, index) in grid.monthHeaders"
                :key="`${header.year}-${header.month}-${index}`"
                :data-month-key="`${header.year}-${header.month}`"
                :colspan="header.colSpan"
                class="min-w-[1.75rem] border-b border-slate-700/80 px-1 pb-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-400"
                :class="monthBandClass(header.monthIndex)"
              >
                {{ header.label }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in grid.rows" :key="row.weekday">
              <td
                v-for="(cell, columnIndex) in row.cells"
                :key="`${row.weekday}-${columnIndex}`"
                class="w-7 min-w-[1.75rem] border border-slate-800/60 p-0"
                :class="cell.inRange && cell.date ? monthBandClass(cell.monthIndex) : ''"
              >
                <button
                  v-if="cell.inRange && cell.date"
                  type="button"
                  :data-date="cell.date"
                  class="flex h-10 w-full min-w-[1.75rem] flex-col items-center justify-center px-0.5 lg:h-8"
                  :class="cellClass(cell.date)"
                  @click="onCellClick(cell.date)"
                >
                  <span>{{ formatDay(cell.dayNumber) }}</span>
                  <span
                    v-if="
                      hasBlockBoundary(cell.date) ||
                      (!isOverview && (hasProgram(cell.date) || hasTraining(cell.date))) ||
                      isCompetition(cell.date)
                    "
                    class="flex gap-px"
                  >
                    <span v-if="isBlockStart(cell.date)" class="h-1 w-1 rounded-full bg-sky-300" />
                    <span v-if="isBlockEnd(cell.date)" class="h-1 w-1 rounded-full bg-sky-500" />
                    <span v-if="!isOverview && hasProgram(cell.date)" class="h-1 w-1 rounded-full bg-emerald-400" />
                    <span v-if="!isOverview && hasTraining(cell.date)" class="h-1 w-1 rounded-full bg-violet-300" />
                    <span v-if="isCompetition(cell.date)" class="h-1 w-1 rounded-full bg-rose-400" />
                  </span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <article
      v-if="selectedDetail"
      class="mt-4 rounded-xl border border-slate-800 bg-slate-950/50 p-4"
    >
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 class="text-sm font-semibold text-white">{{ selectedDetail.label }}</h3>
          <p v-if="selectedDetail.isToday" class="mt-0.5 text-xs text-amber-400">Aujourd'hui</p>
        </div>
        <button
          type="button"
          class="rounded-lg px-2 py-1 text-xs text-slate-500 hover:bg-slate-800 hover:text-slate-300"
          @click="selectedDate = null"
        >
          Fermer
        </button>
      </div>

      <p v-if="selectedDetail.isEmpty" class="mt-3 text-sm text-slate-500">
        Aucune séance ni compétition ce jour-là.
      </p>

      <div v-else class="mt-3 space-y-3">
        <section
          v-if="selectedDetail.blockBoundary"
          class="rounded-lg border border-sky-500/30 bg-sky-500/10 px-3 py-2.5"
        >
          <p class="text-[10px] font-semibold uppercase tracking-wide text-sky-300">Bloc</p>
          <p class="mt-1 text-sm font-semibold text-white">{{ selectedDetail.blockBoundary.label }}</p>
        </section>

        <section
          v-if="selectedDetail.competition"
          class="rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2.5"
        >
          <p class="text-[10px] font-semibold uppercase tracking-wide text-rose-300">Compétition</p>
          <p class="mt-1 text-sm font-semibold text-white">{{ selectedDetail.competition.name }}</p>
          <p v-if="selectedDetail.competition.goal" class="mt-0.5 text-xs text-slate-400">
            Objectif : {{ selectedDetail.competition.goal }}
          </p>
          <p v-if="selectedDetail.competition.location" class="mt-0.5 text-xs text-slate-500">
            {{ selectedDetail.competition.location }}
          </p>
        </section>

        <section
          v-if="selectedDetail.program"
          class="rounded-lg border border-emerald-500/25 bg-emerald-500/5 px-3 py-2.5"
        >
          <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-300">
            Séance programmée
          </p>
          <p class="mt-1 text-sm font-semibold text-white">{{ programLabel(selectedDetail.program) }}</p>
          <ul v-if="programItems(selectedDetail.program).length" class="mt-2 space-y-1">
            <li
              v-for="(item, index) in programItems(selectedDetail.program)"
              :key="index"
              class="text-xs text-slate-300"
            >
              <span class="font-medium text-emerald-400/90">
                {{ SECTION_LABELS[item.section] ?? item.section }}
              </span>
              <span v-if="formatLineRecap(item)"> — {{ formatLineRecap(item) }}</span>
            </li>
          </ul>
        </section>

        <section
          v-for="session in selectedDetail.training"
          :key="session.id"
          class="rounded-lg border border-violet-500/25 bg-violet-500/5 px-3 py-2.5"
        >
          <div class="flex flex-wrap items-start justify-between gap-2">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-violet-300">
              Séance réalisée
            </p>
            <button
              v-if="canEdit"
              type="button"
              class="rounded-lg border border-violet-500/40 px-2 py-0.5 text-[10px] font-semibold text-violet-200 hover:bg-violet-500/10"
              @click="editTrainingSession(session)"
            >
              Modifier
            </button>
          </div>
          <p class="mt-1 text-sm text-slate-200">{{ trainingSummary(session) }}</p>
          <p v-if="session.notes" class="mt-1 text-xs text-slate-500">{{ session.notes }}</p>
        </section>
      </div>
    </article>
  </div>
</template>
