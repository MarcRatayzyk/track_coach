<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import CompetitionDetailPanel from './CompetitionDetailPanel.vue';
import { formatCalendarFr } from '../utils/formatDates';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  competitions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close']);

const view = ref('calendar');
const selectedDay = ref(null);
const selectedCompetition = ref(null);

const weekdayLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

function dateKey(value) {
  const s = String(value ?? '');
  const m = s.match(/^(\d{4}-\d{2}-\d{2})/);
  return m ? m[1] : null;
}

function parseYmd(key) {
  const [y, mo, d] = key.split('-').map(Number);
  return new Date(y, mo - 1, d);
}

const competitionsByDay = computed(() => {
  const map = {};
  for (const c of props.competitions) {
    const key = dateKey(c.competition_date);
    if (!key) {
      continue;
    }
    if (!map[key]) {
      map[key] = [];
    }
    map[key].push(c);
  }
  return map;
});

function initialMonth() {
  const first = props.competitions[0];
  if (first?.competition_date) {
    const key = dateKey(first.competition_date);
    if (key) {
      const d = parseYmd(key);
      return { year: d.getFullYear(), month: d.getMonth() };
    }
  }
  const now = new Date();
  return { year: now.getFullYear(), month: now.getMonth() };
}

const calendarMonth = ref(initialMonth().month);
const calendarYear = ref(initialMonth().year);

const monthLabel = computed(() => {
  const d = new Date(calendarYear.value, calendarMonth.value, 1);
  return d.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
});

const calendarCells = computed(() => {
  const year = calendarYear.value;
  const month = calendarMonth.value;
  const first = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0).getDate();
  const startOffset = (first.getDay() + 6) % 7;
  const cells = [];

  for (let i = 0; i < startOffset; i++) {
    cells.push({ key: `pad-${i}`, empty: true });
  }

  for (let day = 1; day <= lastDay; day++) {
    const key = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    const items = competitionsByDay.value[key] ?? [];
    cells.push({
      key,
      empty: false,
      day,
      dateKey: key,
      hasCompetition: items.length > 0,
      count: items.length,
    });
  }

  return cells;
});

const dayCompetitions = computed(() => {
  if (!selectedDay.value) {
    return [];
  }
  return competitionsByDay.value[selectedDay.value] ?? [];
});

function resetState() {
  view.value = 'calendar';
  selectedDay.value = null;
  selectedCompetition.value = null;
  const init = initialMonth();
  calendarMonth.value = init.month;
  calendarYear.value = init.year;
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      resetState();
    }
  },
);

function close() {
  emit('close');
}

function prevMonth() {
  if (calendarMonth.value === 0) {
    calendarMonth.value = 11;
    calendarYear.value -= 1;
  } else {
    calendarMonth.value -= 1;
  }
}

function nextMonth() {
  if (calendarMonth.value === 11) {
    calendarMonth.value = 0;
    calendarYear.value += 1;
  } else {
    calendarMonth.value += 1;
  }
}

function selectDay(cell) {
  if (cell.empty || !cell.hasCompetition) {
    return;
  }
  selectedDay.value = cell.dateKey;
}

function openCompetition(comp) {
  selectedCompetition.value = comp;
  view.value = 'detail';
}

function backToCalendar() {
  view.value = 'calendar';
  selectedCompetition.value = null;
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      @click.self="close"
    >
      <div
        class="tc-scrollbar max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl"
        @click.stop
      >
        <div class="flex items-start justify-between gap-4">
          <h2 class="text-base font-semibold text-white">
            {{
              view === 'detail'
                ? selectedCompetition?.name ?? 'Compétition'
                : 'Compétitions à venir'
            }}
          </h2>
          <button
            type="button"
            class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
            aria-label="Fermer"
            @click="close"
          >
            ✕
          </button>
        </div>

        <CompetitionDetailPanel
          v-if="view === 'detail' && selectedCompetition"
          :competition="selectedCompetition"
          show-back
          class="mt-4"
          @back="backToCalendar"
        />
        <Link
          v-if="view === 'detail' && selectedCompetition?.athlete_id"
          :href="`/athletes/${selectedCompetition.athlete_id}?competition=${selectedCompetition.id}&edit=1`"
          class="mt-4 inline-flex rounded-xl border border-blue-500/50 bg-blue-600/20 px-3 py-2 text-sm font-medium text-blue-300 transition hover:bg-blue-600/30 hover:text-blue-200"
        >
          Modifier le plan de match
        </Link>

        <template v-else>
          <div class="mt-4 flex items-center justify-between gap-3">
            <button
              type="button"
              class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-800"
              @click="prevMonth"
            >
              ←
            </button>
            <p class="text-sm font-medium capitalize text-white">{{ monthLabel }}</p>
            <button
              type="button"
              class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-800"
              @click="nextMonth"
            >
              →
            </button>
          </div>

          <div class="mt-4 grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-500">
            <span v-for="label in weekdayLabels" :key="label">{{ label }}</span>
          </div>

          <div class="mt-2 grid grid-cols-7 gap-1">
            <template v-for="cell in calendarCells" :key="cell.key">
              <div v-if="cell.empty" class="aspect-square" />
              <button
                v-else
                type="button"
                :disabled="!cell.hasCompetition"
                class="relative flex aspect-square flex-col items-center justify-center rounded-lg text-sm transition"
                :class="
                  cell.hasCompetition
                    ? selectedDay === cell.dateKey
                      ? 'bg-rose-600 text-white'
                      : 'bg-rose-500/20 text-rose-100 hover:bg-rose-500/35'
                    : 'text-slate-500'
                "
                @click="selectDay(cell)"
              >
                {{ cell.day }}
                <span
                  v-if="cell.hasCompetition"
                  class="absolute bottom-1 h-1 w-1 rounded-full bg-rose-400"
                  :class="selectedDay === cell.dateKey ? 'bg-white' : ''"
                />
              </button>
            </template>
          </div>

          <div v-if="selectedDay" class="mt-6 border-t border-slate-800 pt-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
              {{ formatCalendarFr(selectedDay, 'medium') }}
            </p>
            <ul class="mt-3 space-y-2">
              <li v-for="comp in dayCompetitions" :key="comp.id">
                <button
                  type="button"
                  class="w-full rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3 text-left transition hover:border-rose-500/40 hover:bg-slate-800/50"
                  @click="openCompetition(comp)"
                >
                  <p class="text-sm font-semibold text-white">{{ comp.name }}</p>
                  <p class="mt-0.5 text-xs text-slate-400">
                    {{ comp.athlete?.name ?? 'Athlète' }}
                    <span v-if="comp.location"> · {{ comp.location }}</span>
                  </p>
                </button>
              </li>
            </ul>
          </div>

          <p
            v-else-if="!competitions.length"
            class="mt-6 text-center text-sm text-slate-500"
          >
            Aucune compétition à venir.
          </p>
          <p v-else class="mt-6 text-center text-sm text-slate-500">
            Sélectionne un jour marqué pour voir les compétitions.
          </p>
        </template>
      </div>
    </div>
  </Teleport>
</template>
